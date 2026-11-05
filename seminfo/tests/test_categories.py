"""Category boundaries: no merged ranking or cross-category approvals."""
import copy
import json
from pathlib import Path
import secrets
import sys
import tempfile
import threading
import time
import unittest
import urllib.error
from urllib.parse import urlencode
from unittest.mock import patch

sys.path.insert(0,str(Path(__file__).resolve().parents[1]))
from admission import Server
from admission_setup import authenticated_opener
from hub import Hub, Panel, normalize_catalog, import_desk, LABELS
import manage as kit


def catalog(owner='si'):
    return {'manager_profile':owner,'general':{'expected_desks':['si','tii']},'desks':{
        profile:{'profile':profile,'id':profile,'label':profile,'server_ip':ip,'fingerprint':'a'*64,
                 'score_token':'score-'+profile+'x'*32,'control_token':'admin-'+profile+'x'*32}
        for profile,ip in [('si','192.0.2.3'),('tii','192.0.2.4')]}}


class CategoryTests(unittest.TestCase):
    def test_old_catalog_migrates_and_other_admin_tokens_are_removed(self):
        before=catalog(); normalized=normalize_catalog(before)
        self.assertNotIn('general',normalized)
        self.assertEqual(normalized['mode'],'independent_categories')
        self.assertIn('control_token',normalized['desks']['si'])
        self.assertNotIn('control_token',normalized['desks']['tii'])
        self.assertIn('control_token',before['desks']['tii'],'Do not mutate shared input')
        legacy=catalog();legacy.pop('manager_profile')
        self.assertTrue(all('control_token' not in d for d in normalize_catalog(legacy)['desks'].values()))

    def test_each_manager_cannot_approve_the_other_category_even_with_old_tokens(self):
        for owner in ('si','tii'):
            calls=[]
            def transport(*args,**kwargs):calls.append((args,kwargs));return {'ok':True},None
            hub=Hub(catalog(owner),transport=transport)
            other='tii' if owner=='si' else 'si'
            with self.assertRaises(ValueError):hub.call(other,'/control/decision',{'action':'approve'},control=True)
            self.assertEqual(calls,[])
            hub.call(owner,'/control/decision',{'action':'approve'},control=True)
            self.assertTrue(calls[-1][1]['token'].startswith('admin-'+owner))
            hub.call(other,'/control/score')
            self.assertTrue(calls[-1][1]['token'].startswith('score-'+other))

    def test_pairing_preserves_only_local_administration(self):
        with tempfile.TemporaryDirectory() as tmp,patch.object(kit,'LOCAL',Path(tmp)/'local'),patch.object(kit,'private_directory',side_effect=lambda p:p.mkdir(parents=True,exist_ok=True)):
            for profile,desk in catalog()['desks'].items():
                path=Path(tmp)/(profile+'.json');path.write_text(json.dumps(desk))
                import_desk(path,manager_profile='si')
            saved=json.loads((kit.LOCAL/'hub/desks.json').read_text())
            self.assertNotIn('control_token',saved['desks']['tii'])
            self.assertIn('control_token',saved['desks']['si'])
            self.assertEqual(saved['manager_profile'],'si')

    def test_independent_questions_timing_freeze_final_and_unavailable_other_room(self):
        hub=Hub(catalog())
        si={'ok':True,'profile':'si','common_ready':True,'common':{'problems_sha256':'si-only','duration':180},'frozen':False,'public_final':True,
            'teams':[{'name':'SI Equipe','team':'si01','solved':1,'penalty':30}]}
        tii={'ok':True,'profile':'tii','common_ready':True,'common':{'problems_sha256':'tii-only','duration':240},'frozen':True,'public_final':False,
             'teams':[{'name':'TII Equipe','team':'tii01','solved':9,'penalty':1}]}
        hub.snapshot={p:{'score':score,'received':time.monotonic()} for p,score in [('si',si),('tii',tii)]}
        server=Server(('127.0.0.1',0),Panel,None);server.hub=hub;server.token=secrets.token_hex(32)
        t=threading.Thread(target=server.serve_forever,daemon=True);t.start()
        base=f'http://127.0.0.1:{server.server_port}'
        opener=authenticated_opener()
        def get(path):
            with opener.open(base+path) as response:return response.read().decode()
        try:
            si_page=get('/projector/si');tii_page=get('/projector/tii')
            self.assertIn('SI Equipe',si_page);self.assertNotIn('TII Equipe',si_page)
            self.assertIn('TII Equipe',tii_page);self.assertNotIn('SI Equipe',tii_page)
            self.assertIn('Graduação',si_page);self.assertIn('Médio/Técnico',tii_page)
            self.assertIn('final publicado',si_page);self.assertNotIn('congelado',si_page)
            self.assertIn('congelado',tii_page);self.assertNotIn('final publicado',tii_page)
            hub.snapshot['tii']['received']-=31
            self.assertIn('SI Equipe',get('/projector/si'))
            stale=get('/projector/tii');self.assertNotIn('TII Equipe',stale);self.assertIn('indisponível',stale)
            with self.assertRaises(urllib.error.HTTPError) as error:get('/projector/general')
            self.assertEqual(error.exception.code,410)
            self.assertNotIn('SI Equipe',error.exception.read().decode())
            get('/login?token='+server.token)
            admin=get('/?desk=tii');self.assertNotIn('name="action"',admin)
            self.assertNotIn('href="/projector/general"',admin)
            request={'desk':'tii','id':'anything','action':'approve','code':'12345678','csrf':server.token}
            with self.assertRaises(urllib.error.HTTPError) as error:
                opener.open(base+'/decision',urlencode(request).encode())
            self.assertEqual(error.exception.code,400)
        finally:server.shutdown();server.server_close();t.join()


if __name__=='__main__':unittest.main()
