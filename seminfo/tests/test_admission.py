import copy
import hashlib
import importlib.util
import json
import os
from pathlib import Path
import secrets
import shutil
import subprocess
import threading
import urllib.request
import urllib.error
from urllib.parse import urlencode
import sys
import tempfile
import time
import unittest
from unittest.mock import patch

HERE=Path(__file__).resolve().parents[1]
sys.path.insert(0,str(HERE))
from admission import Registry,Server,Admin
from client_config import rules,generate
from hub import Hub, Panel, normalize_catalog, import_desk
spec=importlib.util.spec_from_file_location('admission_agent',HERE/'client/admission-agent.py')
module=importlib.util.module_from_spec(spec);spec.loader.exec_module(module)
Agent=module.Agent


def config():
    c=json.loads((HERE/'labs/si.json').read_text())
    return c|{'server_ip':'192.168.50.2','subnet':'192.168.50.0/24','admission_fingerprint':'a'*64}


class ApprovalTests(unittest.TestCase):
    def setUp(self):
        self.now=100
        self.reg=Registry(config(),clock=lambda:self.now)
        self.data={'profile':'si','device':secrets.token_hex(32),'secret':secrets.token_hex(32),'nonce':secrets.token_hex(32),'team':1}
        self.ip='192.168.50.10'

    def approve(self,data=None,ip=None):
        data,ip=data or self.data,ip or self.ip
        r=self.reg.register(data,ip)
        self.reg.decide(r['id'],'approve',r['code'])
        return r

    def test_pending_cannot_access_and_wrong_code_cannot_approve(self):
        r=self.reg.register(self.data,self.ip)
        self.assertFalse(self.reg.allowed(self.ip))
        with self.assertRaises(ValueError):self.reg.decide(r['id'],'approve','WRONG')
        self.assertFalse(self.reg.allowed(self.ip))
        self.approve();self.assertTrue(self.reg.allowed(self.ip))

    def test_identity_and_lab_and_observed_address(self):
        for data,ip in ((self.data|{'profile':'tii'},self.ip),(self.data,'192.168.51.10'),(self.data|{'team':11},self.ip)):
            with self.assertRaises(ValueError):self.reg.register(data,ip)
        r=self.approve()
        for data,ip in ((r|{'secret':'wrong'},self.ip),(r|{'secret':self.data['secret']},'192.168.50.11')):
            with self.assertRaises(ValueError):self.reg.poll(data,ip)

    def test_lease_renewal_expiry_and_no_resurrection(self):
        r=self.approve();auth={'id':r['id'],'secret':self.data['secret']}
        self.now+=50;self.reg.poll(auth,self.ip)
        self.now+=50;self.assertTrue(self.reg.allowed(self.ip))
        self.now+=11;self.assertFalse(self.reg.allowed(self.ip))
        self.assertEqual(self.reg.poll(auth,self.ip)['status'],'expired')
        self.assertFalse(self.reg.allowed(self.ip))

    def test_reboot_ip_change_and_revocation_need_new_approval(self):
        r=self.approve()
        self.reg.decide(r['id'],'revoke')
        self.assertFalse(self.reg.allowed(self.ip))
        self.approve()
        newer=self.data|{'nonce':secrets.token_hex(32)}
        self.reg.register(newer,'192.168.50.11')
        self.assertFalse(self.reg.allowed(self.ip))
        self.assertFalse(self.reg.allowed('192.168.50.11'))

    def test_duplicate_team_or_ip_requires_explicit_revoke(self):
        self.approve()
        other=self.data|{'device':secrets.token_hex(32),'nonce':secrets.token_hex(32)}
        r=self.reg.register(other,'192.168.50.11')
        with self.assertRaises(ValueError):self.reg.decide(r['id'],'approve',r['code'])
        other2=other|{'team':2,'nonce':secrets.token_hex(32)}
        r=self.reg.register(other2,self.ip)
        with self.assertRaises(ValueError):self.reg.decide(r['id'],'approve',r['code'])

    def test_firewall_failure_does_not_grant_access(self):
        self.reg.on_change=lambda ips:(_ for _ in ()).throw(OSError('simulated'))
        r=self.reg.register(self.data,self.ip)
        with self.assertRaises(RuntimeError):self.reg.decide(r['id'],'approve',r['code'])
        self.assertFalse(self.reg.allowed(self.ip))


class ClientTests(unittest.TestCase):
    def test_incomplete_administrator_key_is_not_accepted(self):
        import manage
        from admission_setup import ensure_keys
        with tempfile.TemporaryDirectory() as tmp,patch.object(manage,'LOCAL',Path(tmp)):
            folder=Path(tmp)/'si';folder.mkdir()
            (folder/'admission-cert.pem').write_text('placeholder')
            (folder/'admission-key.pem').write_text('placeholder')
            (folder/'admission-control.txt').write_text('')
            (folder/'admission-score.txt').write_text('s'*43)
            with self.assertRaisesRegex(ValueError,'Credencial da banca incompleta'):ensure_keys('si')

    def test_root_accepts_only_fresh_bound_grant(self):
        applied=[]
        agent=Agent(config(),apply=applied.append)
        agent.identity={'device':'a'*64,'secret':'b'*64}
        agent.server='192.168.50.2';agent.team=1
        agent.request={'nonce':'c'*64}
        grant={'id':hashlib.sha256(('a'*64+'c'*64).encode()).hexdigest(),'device':'a'*64,'nonce':'c'*64,'ip':'192.168.50.10','server_ip':agent.server,'profile':'si','team':1,'code':'12345678','status':'approved','lease_seconds':60}
        for key,value in [('ip','192.168.50.11'),('profile','tii'),('nonce','d'*64),('team',2),('lease_seconds',10000)]:
            with self.assertRaises(ValueError):agent.accept(grant|{key:value},'192.168.50.10',time.monotonic())
        agent.accept(grant,'192.168.50.10',time.monotonic())
        self.assertIn('flags timeout',applied[-1]);self.assertIn('@boca_lease',applied[-1])
        agent.close();self.assertNotIn('tcp dport 8000',applied[-1])

    def test_transport_failure_closes_previous_allow(self):
        applied=[]
        def fail(*args):raise ValueError('wrong TLS pin')
        agent=Agent(config(),apply=applied.append,transport=fail)
        agent.identity={'device':'a'*64,'secret':'b'*64}
        agent.submit('192.168.50.2',1);agent.tick()
        self.assertNotIn('tcp dport 8000',applied[-1])
        self.assertEqual(agent.until,0)

    def test_overlay_only_contains_public_trust_and_new_original_wallpapers(self):
        with tempfile.TemporaryDirectory() as tmp:
            overlay=generate(config(),Path(tmp))
            public=json.loads((overlay/'etc/seminfo/lab.json').read_text())
            self.assertEqual(len(public['admission_fingerprint']),64)
            for name in ('caramel-claro.png','caramel-espresso.png'):
                self.assertEqual((overlay/'usr/share/maratona-background'/name).read_bytes(),(HERE/'banners'/name).read_bytes())
            self.assertFalse(any('key.pem' in str(p) or 'control.txt' in str(p) for p in overlay.rglob('*')))
            self.assertIn('Before=display-manager.service',(overlay/'etc/systemd/system/seminfo-wallpaper.service').read_text())


class TeacherPanelTests(unittest.TestCase):
    def test_native_service_probe_preserves_login_cookie_and_rejects_csrf(self):
        import manage
        from admission_setup import running,authenticated_opener
        registry=Registry(config())
        request={'profile':'si','device':'a'*64,'secret':'b'*64,'nonce':'c'*64,'team':1}
        record=registry.register(request,'192.168.50.10')
        server=Server(('127.0.0.1',0),Admin,registry)
        server.token=secrets.token_urlsafe(32);server.stop_event=threading.Event()
        thread=threading.Thread(target=server.serve_forever,daemon=True);thread.start()
        try:
            with tempfile.TemporaryDirectory() as tmp,patch.object(manage,'LOCAL',Path(tmp)):
                folder=Path(tmp)/'si';folder.mkdir()
                base=f'http://127.0.0.1:{server.server_port}'
                login=base+'/login?token='+server.token
                (folder/'admission-process.json').write_text(json.dumps({'url':login}))
                self.assertTrue(running('si'),'O supervisor precisa reconhecer o proprio servico autenticado')
                opener=authenticated_opener()
                with opener.open(login) as response:self.assertIn(b'Banca SEMINFO',response.read())
                data={'id':record['id'],'code':record['code'],'action':'approve','csrf':'wrong'}
                with self.assertRaises(urllib.error.HTTPError):opener.open(base+'/decision',urlencode(data).encode())
                self.assertFalse(registry.allowed('192.168.50.10'))
                data['csrf']=server.token
                with opener.open(base+'/decision',urlencode(data).encode()) as response:self.assertEqual(response.status,200)
                self.assertTrue(registry.allowed('192.168.50.10'))
                with self.assertRaises(urllib.error.HTTPError):urllib.request.urlopen(base+'/')
        finally:
            server.shutdown();server.server_close();thread.join()


@unittest.skipUnless(os.name=='nt','Dry-run do firewall requer PowerShell Windows')
class DeskFirewallTests(unittest.TestCase):
    def test_windows_effective_ipv4_ranges_without_changing_host(self):
        with tempfile.TemporaryDirectory() as tmp:
            root=Path(tmp)
            shutil.copy2(HERE/'firewall-mesa.ps1',root/'firewall-mesa.ps1')
            (root/'manage.py').write_text('raise SystemExit(0)\n')
            folder=root/'local/si';folder.mkdir(parents=True)
            cfg=config()|{'control_ips':['172.16.5.10'],'team_ips':['192.168.50.11']}
            (folder/'lab.json').write_text(json.dumps(cfg))
            for approved in ([], ['192.168.50.10'], ['192.168.50.10', '192.168.50.12']):
                with self.subTest(approved=approved):
                    (folder/'admission-approved.json').write_text(json.dumps(approved))
                    run=subprocess.run(['powershell','-NoProfile','-ExecutionPolicy','Bypass','-File',
                        str(HERE/'tests/firewall-ranges.ps1'),'-Script',str(root/'firewall-mesa.ps1')],capture_output=True,text=True)
                    self.assertEqual(run.returncode,0,run.stdout+run.stderr)
                    self.assertIn('PASSOU',run.stdout)


if __name__=='__main__':unittest.main()
