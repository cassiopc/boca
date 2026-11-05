"""Exercise the actual operator HTTP API against the inactive SI preparation DB.

Creates a private backup and verifies its restoration. Never activates a contest,
changes institutional networking or runs the Windows firewall.
"""
import datetime as dt
import json
from pathlib import Path
import secrets
import sys
import threading
import time
import urllib.error
import urllib.request

sys.path.insert(0,str(Path(__file__).resolve().parents[1]))
import manage as kit
import organizer
from admission import Server
from admission_setup import authenticated_opener


def main():
    cfg=kit.config('si'); before=kit.config_hash(cfg)
    status=organizer.database_status('si')
    assert not status['active'] and status['runs']==0, 'Only an unused preparation contest may be tested'
    app=organizer.Organizer()
    original_profile=app.profile()
    app.select('si')
    server=Server(('127.0.0.1',0),organizer.Panel,None)
    server.app,server.token=app,secrets.token_urlsafe(32)
    t=threading.Thread(target=server.serve_forever,daemon=True);t.start()
    base=f'http://127.0.0.1:{server.server_port}'
    opener=authenticated_opener()
    def post(path,data):
        request=urllib.request.Request(base+path,json.dumps(data).encode(),headers={
            'Content-Type':'application/json','Origin':base,'X-SEMINFO-CSRF':server.token})
        with opener.open(request,timeout=30) as response:return json.load(response)
    def state():
        with opener.open(base+'/api/state',timeout=30) as response:return json.load(response)
    def job(action):
        post('/api/job',{'action':action})
        for _ in range(240):
            current=state()['job']
            if current['status']!='running':
                assert current['status']=='done', current['message']
                return current
            time.sleep(.5)
        raise AssertionError('Job did not complete within two minutes')
    try:
        with opener.open(base+'/login?token='+server.token) as response:
            assert 'Central SEMINFO' in response.read().decode()
        for asset in ('/app.js','/style.css'):
            with opener.open(base+asset) as response:assert response.status==200 and len(response.read())>1000
        try:post('/api/job',{'action':'activate'})
        except urllib.error.HTTPError as error:assert error.code==400
        else:raise AssertionError('Activation without confirmation was accepted')
        assert kit.config_hash(kit.config('si'))==before
        verification=job('verify')
        backup=job('backup')
        latest=state()['backups'][0]
        assert latest['verified']
        manifest=json.loads((kit.HERE/'backups'/latest['id']/'manifest.json').read_text())
        checked=json.loads((kit.HERE/'backups'/latest['id']/'restore-verified.json').read_text())
        assert manifest['fingerprint']==checked['fingerprint']
        after=organizer.database_status('si')
        assert after==status and kit.config_hash(kit.config('si'))==before
        kit.write_json(kit.HERE/'reports/organizer-http.json',{
            'passed':True,'utc':dt.datetime.now(dt.timezone.utc).isoformat(),
            'server_source_sha256':kit.source_hash(),'assets_http':True,'activation_without_confirmation_rejected':True,
            'actual_verification_job':verification['status'],'actual_backup_restore_job':backup['status'],
            'backup':latest['id'],'database_unchanged':True,'configuration_unchanged':True,
            'browser_visual_test':{'passed':False,'not_run':True,'reason':'This script checks HTTP only; graphical review requires a separate observed check'},
            'windows_uac_and_firewall_tested':False})
        print('PASSOU: central HTTP, tarefas reais de verificacao e backup/restauracao, dados e configuracao preservados.')
    finally:
        server.shutdown();server.server_close();t.join()
        if app.job and app.job['status']=='running':
            raise RuntimeError('Wait for the owned background job before closing this test')
        app.select(original_profile)


if __name__=='__main__':main()
