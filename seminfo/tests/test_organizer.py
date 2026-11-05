import http.cookiejar
import json
from pathlib import Path
import secrets
import sys
import tempfile
import threading
import time
import unittest
from unittest.mock import patch
import urllib.error
import urllib.request

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
import manage as kit
import organizer
from admission import Admin, Registry, Server


class OrganizerTests(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.folder = Path(self.tmp.name)
        self.patches = [patch.object(kit, 'LOCAL', self.folder/'local'),
                        patch.object(kit, 'private_directory', lambda p: p.mkdir(parents=True, exist_ok=True)),
                        patch.object(kit, 'current_secrets', return_value=[])]
        for p in self.patches:p.start()
        for profile in organizer.LABELS:
            kit.write_json(kit.LOCAL/profile/'lab.json', json.loads((kit.HERE/'labs'/f'{profile}.json').read_text()))
        self.app = organizer.Organizer()

    def tearDown(self):
        for p in reversed(self.patches):p.stop()
        self.tmp.cleanup()

    def config_data(self):
        return {'server_ip': '192.168.50.2', 'subnet': '192.168.50.0/24', 'control_ips': '',
                'start': '2026-10-29T13:00', 'duration_minutes': '180',
                'freeze_minutes_before_end': '30', 'timing_confirmed': True}

    def test_config_cannot_change_active_used_disconnected_or_open_bank(self):
        before = kit.config('si')
        with patch('admission_setup.running', return_value=False):
            for status in ({'active': True, 'runs': 0}, {'active': False, 'runs': 1}):
                with patch.object(organizer, 'database_status', return_value=status):
                    with self.assertRaises(ValueError):self.app.save_config(self.config_data())
            with patch.object(organizer, 'database_status', side_effect=RuntimeError('database offline')):
                with self.assertRaises(RuntimeError):self.app.save_config(self.config_data())
        with patch('admission_setup.running', return_value=True):
            with self.assertRaises(ValueError):self.app.save_config(self.config_data())
        self.assertEqual(before, kit.config('si'))

    def test_save_validates_unknowns_without_guessing_and_preserves_identity(self):
        with patch('admission_setup.running', return_value=False), patch.object(organizer,'database_status',return_value={'active':False,'runs':0}):
            for data in (self.config_data() | {'duration_minutes': ''}, self.config_data() | {'freeze_minutes_before_end': '180'}, self.config_data() | {'subnet': ''}):
                with self.assertRaises(ValueError):self.app.save_config(data)
            with self.assertRaises(ValueError):self.app.save_config(self.config_data() | {'profile': 'tii'})
            self.app.save_config(self.config_data() | {'contest_id': 99})
            self.assertEqual(kit.config('si')['contest_id'],1)
            self.assertEqual(kit.config('si')['profile'],'si')
            data = self.config_data() | {'server_ip':'','subnet':'','duration_minutes':'','freeze_minutes_before_end':'','timing_confirmed':False}
            self.app.save_config(data)
        c=kit.config('si')
        self.assertIsNone(c['server_ip']); self.assertIsNone(c['duration_minutes'])
        self.assertFalse(c['timing_confirmed'])

    def test_category_times_are_editable_without_network_and_do_not_overwrite_other_category(self):
        other=kit.config('tii')
        data=self.config_data() | {'profile':'si'}
        with patch('admission_setup.running',return_value=False), patch.object(organizer,'database_status',return_value={'active':False,'runs':0}):
            self.app.save_config(data,timing_only=True)
            c=kit.config('si')
            self.assertEqual(c['duration_minutes'],180)
            self.assertEqual(c['freeze_minutes_before_end'],30)
            self.assertIsNone(c['server_ip'])
            self.app.save_config({'server_ip':'','subnet':'','control_ips':''})
            self.assertEqual(kit.config('si')['duration_minutes'],180)
            self.app.save_config(data | {'freeze_minutes_before_end':'0'},timing_only=True)
            self.assertEqual(kit.config('si')['freeze_minutes_before_end'],0)
            before=kit.config('si')
            for changes in ({'duration_minutes':'0'}, {'freeze_minutes_before_end':'180'}, {'start':'2026-10-29T25:00'}):
                with self.assertRaises(ValueError):self.app.save_config(data|changes,timing_only=True)
                self.assertEqual(kit.config('si'),before)
        self.assertEqual(kit.config('tii'),other)

    def test_jobs_cannot_bypass_confirmations_change_room_or_overlap(self):
        for action in organizer.CONFIRM:
            with self.assertRaises(ValueError):self.app.submit(action,{'confirmation':'ATIVAR LAB 4'})
        with self.assertRaises(ValueError):self.app.submit('shell',{})
        done=threading.Event()
        with patch.object(self.app,'execute',side_effect=lambda *a: done.wait(2)):
            self.app.submit('verify',{})
            with self.assertRaises(ValueError):self.app.submit('verify',{})
            with self.assertRaises(ValueError):self.app.select('tii')
            done.set()
            for _ in range(100):
                if self.app.job['status']!='running':break
                time.sleep(.01)
        self.assertEqual(self.app.job['status'],'done')
        self.assertTrue((self.app.folder/'last-job.json').exists())

    def test_backup_sequence_includes_real_restore_checker_and_stops_on_copy_failure(self):
        with patch.object(kit,'backup',return_value=self.folder/'copy') as backup, patch.object(kit,'check_restore') as check:
            self.app.execute('backup','si',{})
            backup.assert_called_once_with('si'); check.assert_called_once_with(self.folder/'copy')
        with patch.object(kit,'backup',side_effect=RuntimeError('copy failed')), patch.object(kit,'check_restore') as check:
            with self.assertRaises(RuntimeError):self.app.execute('backup','si',{})
            check.assert_not_called()

    def test_backup_paths_and_occupied_restore_destination_are_rejected(self):
        with patch.object(kit,'HERE',self.folder):
            p=self.folder/'backups'/'si-20261015T120000Z'; p.mkdir(parents=True)
            kit.write_json(p/'manifest.json',{'profile':'si'})
            self.assertEqual(organizer.backup_path(p.name,'si'),p)
            for name in ('../local','C:\\private',p.name+'/../escape'):
                with self.assertRaises(ValueError):organizer.backup_path(name,'si')
            with self.assertRaises(ValueError):organizer.backup_path(p.name,'tii')
            with patch.object(organizer,'database_status',return_value={'prepared':True,'active':False,'runs':0}), patch.object(kit,'exists',return_value=False), patch.object(self.app,'command') as command:
                with self.assertRaises(ValueError):self.app.execute('restore','si',{'backup':p.name})
                command.assert_not_called()

    def test_attestation_requires_actual_test_responsible_and_note(self):
        for data in ({}, {'check':'client_boot','performed':False}, {'check':'client_boot','performed':True,'operator':'','note':'Uma observação de teste'}):
            with self.assertRaises(ValueError):self.app.submit('attest',data)

    def test_interrupted_job_is_never_claimed_successful(self):
        kit.write_json(self.app.folder/'last-job.json',{'status':'running','action':'backup','profile':'si'})
        app=organizer.Organizer()
        self.assertEqual(app.job['status'],'failed')

    def test_local_preparation_cannot_operate_firewall_activate_or_approve(self):
        app=organizer.Organizer(preparation_only=True)
        self.assertNotEqual(app.folder,self.app.folder)
        self.assertTrue(app.state()['preparation_only'])
        for action in ('start','stop','activate','install-autostart','pair','open-hub'):
            with self.assertRaises(ValueError):app.submit(action,{'profile':'si'})
            with self.assertRaises(ValueError):app.execute(action,'si',{})
        with patch.object(organizer,'editable') as edit,patch.object(kit,'compose') as compose:
            app.execute('stop-preparation','si',{})
            edit.assert_called_once_with('si')
            compose.assert_called_once_with('si','stop','boca-web','boca-jail')

    def test_preparation_and_full_central_sessions_do_not_overwrite_each_other(self):
        servers=[];threads=[]
        opener=urllib.request.build_opener(urllib.request.ProxyHandler({}),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        try:
            for app in (self.app,organizer.Organizer(preparation_only=True)):
                server=Server(('127.0.0.1',0),organizer.Panel,None)
                server.app,server.token=app,secrets.token_urlsafe(32)
                thread=threading.Thread(target=server.serve_forever,daemon=True);thread.start()
                servers.append(server);threads.append(thread)
                opener.open(f'http://127.0.0.1:{server.server_port}/login?token='+server.token).close()
            for server in servers:
                with opener.open(f'http://127.0.0.1:{server.server_port}/api/state') as response:
                    self.assertEqual(json.load(response)['preparation_only'],server.app.preparation_only)
            server=servers[-1]
            with self.assertRaises(urllib.error.HTTPError):opener.open(f'http://127.0.0.1:{server.server_port}/open/hub')
            with patch.object(organizer,'editable'),patch.object(kit,'exists',return_value=False):
                with opener.open(f'http://127.0.0.1:{server.server_port}/open/problem-builder') as response:
                    self.fail('Editor indisponível deveria devolver orientação com erro HTTP')
        except urllib.error.HTTPError as error:
            page=error.read().decode()
            self.assertIn('Voltar à configuração da prova',page)
            self.assertIn('text/html',error.headers['Content-Type'])
        finally:
            for server in servers:server.shutdown();server.server_close()
            for thread in threads:thread.join()

    def test_editor_routes_require_current_image_for_each_profile(self):
        class NoRedirect(urllib.request.HTTPRedirectHandler):
            def redirect_request(self, req, fp, code, msg, headers, newurl):
                return None
        server=Server(('127.0.0.1',0),organizer.Panel,None)
        server.app,server.token=self.app,secrets.token_urlsafe(32)
        thread=threading.Thread(target=server.serve_forever,daemon=True);thread.start()
        base=f'http://127.0.0.1:{server.server_port}'
        opener=urllib.request.build_opener(urllib.request.ProxyHandler({}),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()),NoRedirect())
        try:
            try:opener.open(base+'/login?token='+server.token)
            except urllib.error.HTTPError as error:self.assertEqual(error.code,303)
            with patch.object(organizer,'editable'),patch.object(kit,'exists',return_value=True),patch.object(kit,'inspect',return_value={'State':{'Health':{'Status':'healthy'}}}):
                for profile in ('si','tii'):
                    self.app.select(profile)
                    for path,destination in (('problem-builder','builder'),('problems','problems')):
                        with patch.object(organizer,'current_web_image',return_value=True) as current:
                            with self.assertRaises(urllib.error.HTTPError) as result:opener.open(base+'/open/'+path)
                            self.assertEqual(result.exception.code,303)
                            self.assertEqual(result.exception.headers['Location'],f'http://127.0.0.1:18000/boca/index.php?contest=1&destination={destination}')
                            current.assert_called_once_with(profile)
                        with patch.object(organizer,'current_web_image',return_value=False):
                            with self.assertRaises(urllib.error.HTTPError) as result:opener.open(base+'/open/'+path)
                            self.assertEqual(result.exception.code,400)
                            self.assertIn('desatualizadas',result.exception.read().decode())
        finally:
            server.shutdown();server.server_close();thread.join()

    def test_http_cookie_csrf_origin_host_allowlist_and_no_secrets_in_state(self):
        body_reads=[]
        class ObservedPanel(organizer.Panel):
            def body(self, maximum=8192):
                payload=super().body(maximum)
                self.body_consumed=True
                return payload
            def reply(self, *args, **kwargs):
                if self.command=='POST':
                    body_reads.append(getattr(self,'body_consumed',False))
                return super().reply(*args, **kwargs)
        server=Server(('127.0.0.1',0),ObservedPanel,None)
        server.app,server.token=self.app,secrets.token_urlsafe(32)
        thread=threading.Thread(target=server.serve_forever,daemon=True); thread.start()
        base=f'http://127.0.0.1:{server.server_port}'
        opener=urllib.request.build_opener(urllib.request.ProxyHandler({}),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        def post(data, **headers):
            request=urllib.request.Request(base+'/api/select',json.dumps(data).encode(),headers={'Content-Type':'application/json',**headers})
            return opener.open(request,timeout=5)
        try:
            with self.assertRaises(urllib.error.HTTPError):opener.open(base+'/api/state')
            with opener.open(base+'/login?token='+server.token) as response:
                page=response.read().decode(); self.assertIn('Central SEMINFO',page)
                self.assertNotIn(server.token,page)
                self.assertIn("script-src 'self'",response.headers['Content-Security-Policy'])
            for headers in ({}, {'Origin':'https://hostil.example','X-SEMINFO-CSRF':server.token},{'Origin':base,'X-SEMINFO-CSRF':'wrong'},{'Origin':base,'X-SEMINFO-CSRF':server.token,'Host':'hostil.example'}):
                with self.assertRaises(urllib.error.HTTPError):post({'profile':'tii'},**headers)
            self.assertEqual(body_reads,[True]*4,'Rejeicoes devem consumir o corpo limitado para entregar o erro HTTP sem reset no Windows')
            with post({'profile':'tii'},Origin=base,**{'X-SEMINFO-CSRF':server.token}):pass
            with opener.open(base+'/api/state') as response:
                raw=response.read().decode(); state=json.loads(raw)
                self.assertEqual(state['profile'],'tii')
                self.assertNotIn('score_token',raw);self.assertNotIn('control_token',raw);self.assertNotIn('BOCA_PASSWORD',raw)
            with self.assertRaises(urllib.error.HTTPError):opener.open(base+'/../local/si/credentials.json')
            folder=kit.LOCAL/'tii'
            for name in ('admission-cert.pem','admission-key.pem','admission-control.txt','admission-score.txt','credentials.json'):
                (folder/name).write_text('{}' if name.endswith('.json') else 'existing-private-identity')
            kit.write_json(folder/'projector-desk.json',{'profile':'tii','score_token':'read-only-token'})
            kit.write_json(folder/'admission-desk.json',{'profile':'tii','score_token':'read-only-token','control_token':'never-export-admin'})
            request=urllib.request.Request(base+'/api/descriptor',json.dumps({'confirmation':'EXPORTAR PAREAMENTO'}).encode(),headers={
                'Content-Type':'application/json','Origin':base,'X-SEMINFO-CSRF':server.token})
            with patch('admission_setup.descriptor',return_value=folder/'admission-desk.json'):
                with opener.open(request) as response:
                    exported=json.load(response)
            self.assertEqual(exported['filename'],'projector-desk.json')
            self.assertNotIn('control_token',exported['content'])
            self.assertNotIn('never-export-admin',exported['content'])
        finally:
            server.shutdown();server.server_close();thread.join()

    def test_revocation_requires_review_and_confirmation(self):
        c=kit.config('si')|{'server_ip':'192.168.50.2','subnet':'192.168.50.0/24'}
        reg=Registry(c)
        record=reg.register({'profile':'si','device':'a'*64,'secret':'b'*64,'nonce':'c'*64,'team':1},'192.168.50.10')
        reg.decide(record['id'],'approve',record['code'])
        server=Server(('127.0.0.1',0),Admin,reg);server.token=secrets.token_hex(32)
        t=threading.Thread(target=server.serve_forever,daemon=True);t.start()
        from urllib.parse import urlencode
        from admission_setup import authenticated_opener
        opener=authenticated_opener();base=f'http://127.0.0.1:{server.server_port}'
        try:
            opener.open(base+'/login?token='+server.token).close()
            data={'id':record['id'],'action':'revoke','csrf':server.token}
            with opener.open(base+'/decision',urlencode(data).encode()) as response:self.assertIn('Confirmar revogação',response.read().decode())
            self.assertTrue(reg.allowed('192.168.50.10'))
            opener.open(base+'/decision',urlencode(data|{'confirmed':'yes'}).encode()).close()
            self.assertFalse(reg.allowed('192.168.50.10'))
        finally:server.shutdown();server.server_close();t.join()


if __name__=='__main__':unittest.main()
