import copy
import json
from pathlib import Path
import sys
import tempfile
import subprocess
import unittest
from unittest.mock import patch, MagicMock

HERE=Path(__file__).resolve().parents[1]
sys.path.insert(0,str(HERE))
import manage
from client_config import generate,rules
from scoring import standings,public_rows
from webcast import prepare_export


def config():
    c=json.loads((HERE/'labs/si.json').read_text(encoding='utf-8'))
    c.update(server_ip='192.168.50.2',subnet='192.168.50.0/24',team_ips=[f'192.168.50.{i}' for i in range(10,20)],timing_confirmed=True,duration_minutes=100,freeze_minutes_before_end=20)
    return c


class ConnectivityTests(unittest.TestCase):
    def test_probe_rejects_a_page_without_the_boca_login_form(self):
        response=MagicMock()
        response.__enter__.return_value.read.return_value=b'BOCA Online Contest Administrator'
        opener=MagicMock()
        opener.open.return_value=response
        with patch.object(manage.urllib.request, 'build_opener', return_value=opener):
            with self.assertRaisesRegex(ValueError, 'não corresponde'):
                manage.probe_web('127.0.0.1')


class ConfigurationTests(unittest.TestCase):
    def test_source_hash_normalizes_source_line_endings_but_not_assets(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            script = root / 'page.php'
            script.write_bytes(b'<?php\r\necho 1;\r\n')
            self.assertEqual(manage.source_hash_bytes(script), b'<?php\necho 1;\n')
            script.write_bytes(b'<?php\necho 1;\n')
            self.assertEqual(manage.source_hash_bytes(script), b'<?php\necho 1;\n')
            asset = root / 'input.dat'
            asset.write_bytes(b'a\r\nb\n')
            self.assertEqual(manage.source_hash_bytes(asset), b'a\r\nb\n')
            image = root / 'image.png'
            image.write_bytes(b'\x89PNG\r\n\x00\r\n')
            self.assertEqual(manage.source_hash_bytes(image), b'\x89PNG\r\n\x00\r\n')

    def test_missing_ip_and_times_block_activation(self):
        c=json.loads((HERE/'labs/si.json').read_text(encoding='utf-8'))
        with self.assertRaises(ValueError): manage.validate(c,network=True,timing=True)
        with self.assertRaises(ValueError): manage.validate(c,timing=True)

    def test_enrollment_allows_dhcp_but_reserved_addresses_must_be_valid(self):
        c=config();manage.validate(c,network=True,timing=True)
        c['team_ips']=[];manage.validate(c,network=True)
        for ips in (['192.168.50.10']*10,['192.168.50.2']+c['team_ips'][1:]):
            bad=copy.deepcopy(c);bad['team_ips']=ips
            with self.assertRaises(ValueError): manage.validate(bad,network=True)

    def test_invalid_times(self):
        for field,value in [('duration_minutes',0),('freeze_minutes_before_end',100),('timing_confirmed',False),('stop_answering_minutes_before_end',-1)]:
            c=config();c[field]=value
            with self.assertRaises(ValueError): manage.validate(c,timing=True)

    def test_overlay_has_no_contest_secrets_and_denies_ipv6(self):
        with tempfile.TemporaryDirectory() as tmp:
            overlay=generate(config(),Path(tmp)/'si')
            text=(overlay/'etc/nftables.conf').read_text()
            self.assertIn('ip daddr 192.168.50.2 tcp dport 8443 accept',text)
            self.assertIn('meta skuid 0',text)
            self.assertNotIn('tcp dport 8000',text)
            self.assertNotIn('ip6 daddr',text)
            self.assertNotIn('ct state established,related accept',text.split('chain output')[1])
            self.assertFalse(any('credentials' in str(p) for p in overlay.rglob('*')))
            self.assertEqual((overlay/'usr/share/maratona-background/maratona-common-wallpaper.png').read_bytes(),(HERE/'banners/caramel-espresso.png').read_bytes())

    def test_incomplete_overlay_is_closed(self):
        c=config();c['server_ip']=None;c['subnet']=None
        with tempfile.TemporaryDirectory() as tmp:
            overlay=generate(c,Path(tmp),allow_incomplete=True)
            text=(overlay/'etc/nftables.conf').read_text()
            self.assertNotIn('tcp dport',text)
            self.assertIn('seminfo-admission-window',(overlay/'etc/xdg/autostart/seminfo-boca.desktop').read_text())
            self.assertIn('about:blank',(overlay/'etc/dconf/db/local.d/99-seminfo').read_text())
            self.assertNotIn(b'\r', (overlay/'etc/seminfo/deny.nft').read_bytes())

    def test_stale_attestations_cannot_activate(self):
        c=config()
        with tempfile.TemporaryDirectory() as tmp, patch.object(manage,'LOCAL',Path(tmp)),patch.object(manage,'config',return_value=c),patch.object(manage,'verify'),patch.object(manage,'exists',return_value=True),patch.object(manage,'inspect',return_value={'State':{'Running':True,'Health':{'Status':'healthy'}}}),patch.object(manage,'control') as control:
            with self.assertRaisesRegex(ValueError,'Ensaios pendentes'):
                manage.activate('si')
            control.assert_not_called()


class ServerBuildTests(unittest.TestCase):
    def test_problem_template_tree_changes_server_fingerprint(self):
        with tempfile.TemporaryDirectory() as tmp:
            root=Path(tmp);here=root/'seminfo'
            template=root/'doc/problemexamples/problemtemplate'
            (template/'compile').mkdir(parents=True)
            script=template/'compile/java';script.write_bytes(b'first compiler\n')
            with patch.object(manage,'ROOT',root),patch.object(manage,'HERE',here):
                first=manage.source_hash()
                script.write_bytes(b'updated compiler\n')
                second=manage.source_hash()
                self.assertNotEqual(first,second)
                (template/'description').mkdir()
                (template/'description/problem.info').write_text('basename=A\n')
                self.assertNotEqual(second,manage.source_hash())


class ClientBuildTests(unittest.TestCase):
    def test_ubuntu_boot_without_casper_argument_gets_persistence(self):
        with tempfile.TemporaryDirectory() as tmp:
            root=Path(tmp);grub=root/'boot/grub';grub.mkdir(parents=True)
            file=grub/'grub.cfg'
            file.write_text('set timeout=30\nmenuentry "Try or Install Ubuntu" {\n linux /casper/vmlinuz maybe-ubiquity quiet splash ---\n}\nmenuentry "Ubuntu (safe graphics)" {\n linux /casper/vmlinuz nomodeset ---\n}\nmenuentry "OEM install (for manufacturers)" {\n linux /casper/vmlinuz only-ubiquity ---\n}\n')
            cfg=root/'lab.json';cfg.write_text(json.dumps(config()))
            command=[sys.executable,str(HERE/'client/patch-boot.py'),str(root),str(cfg)]
            subprocess.run(command,check=True)
            text=file.read_text()
            self.assertEqual(text.count('username=icpc persistent noprompt'),2)
            self.assertIn('nomodeset',text)
            self.assertNotIn('ubiquity',text)
            self.assertNotIn('OEM install',text)
            self.assertIn('Lab 3 SI',text)
            subprocess.run(command,check=True)
            self.assertEqual(text,file.read_text())


class ScoreTests(unittest.TestCase):
    def teams(self):
        return [{'id':'si01','number':2001,'site':1,'name':'Equipe 01'},{'id':'si02','number':2002,'site':1,'name':'Equipe 02'}]

    def runrow(self,n,minute,team,problem,answer):
        return {'id':n,'seconds':minute*60,'team':team,'problem':problem,'answer':answer}

    def test_compile_error_has_no_penalty(self):
        runs=[self.runrow(1,5,'si01','A','X'),self.runrow(2,10,'si01','A','N'),self.runrow(3,20,'si01','A','Y')]
        score=standings(self.teams(),runs,20)
        self.assertEqual((score[0]['solved'],score[0]['penalty']),(1,40))

    def test_last_acceptance_tiebreak_and_user_number(self):
        runs=[self.runrow(1,10,'si01','A','Y'),self.runrow(2,30,'si01','B','Y'),self.runrow(3,20,'si02','A','Y'),self.runrow(4,20,'si02','B','Y')]
        self.assertEqual(standings(self.teams(),runs,20)[0]['team'],'si02')
        self.assertEqual(standings(self.teams(),[],20)[0]['team'],'si01')

    def test_pending_does_not_score_and_penalty_configurable(self):
        runs=[self.runrow(1,2,'si01','A','?'),self.runrow(2,5,'si01','A','N'),self.runrow(3,10,'si01','A','Y')]
        self.assertEqual(standings(self.teams(),runs,7)[0]['penalty'],17)

    def test_export_parity_and_freeze_mapping(self):
        teams=self.teams()
        raw={'ok':True,'contest_id':1,'site_id':1,'duration_seconds':6000,'elapsed_seconds':6000,'name':'Ensaio','teams':teams,'problems':[{'name':'A'}],
             'runs':[{'id':1,'seconds':5400,'team':'si01','problem':'A','answer':1,'yes':'t','status':'judged'}],
             'official':[{'team':'si01','solved':1,'penalty':90},{'team':'si02','solved':0,'penalty':0}]}
        with tempfile.TemporaryDirectory() as tmp:
            folder=Path(tmp)/'export'
            prepare_export(raw,config(),folder)
            self.assertEqual((folder/'contest').read_text().split('\n')[1],'100\x1c80\x1c100\x1c20')
            self.assertTrue(json.loads((folder/'metadata.json').read_text())['final'])
            self.assertEqual(json.loads((folder/'official.json').read_text())['runs'],raw['runs'])
            for status in ('openrun','judged+'):
                raw['runs'][0]['status']=status
                raw['pending_runs']=0 # A stale positive verdict/count must not bypass a rejudge.
                another=Path(tmp)/status
                prepare_export(raw,config(),another)
                self.assertFalse(json.loads((another/'metadata.json').read_text())['final'])
            raw['official'][0]['penalty']=91
            with self.assertRaisesRegex(ValueError,'diverge'):
                prepare_export(raw,config(),Path(tmp)/'bad')

    def test_wrong_contest_rejected(self):
        with self.assertRaises(ValueError):prepare_export({'ok':True,'contest_id':2,'site_id':1},config(),Path('unused'))


if __name__=='__main__':unittest.main()
