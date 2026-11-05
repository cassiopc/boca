"""Isolated dummy desk for transport/gate tests; no real contest database."""
import http.server
import json
from pathlib import Path
import shutil
import sys
import threading

HERE=Path('/kit/seminfo')
sys.path.insert(0,str(HERE))
import admission
import manage

profile,ip,subnet=sys.argv[1:]
manage.LOCAL=Path('/tmp/admission-fixture')
folder=manage.LOCAL/profile
folder.mkdir(parents=True)
config=json.loads((HERE/'labs'/f'{profile}.json').read_text())
config.update(server_ip=ip,subnet=subnet)
(folder/'lab.json').write_text(json.dumps(config))
for name in ('admission-cert.pem','admission-key.pem','admission-control.txt','admission-score.txt'):
    shutil.copy2(HERE/'local'/profile/name,folder/name)

class Backend(http.server.BaseHTTPRequestHandler):
    def do_GET(self):
        payload=b'BOCA Online Contest Administrator - Acesso ao BOCA <input name="name"><input name="password">'
        self.send_response(200);self.send_header('Content-Length',str(len(payload)));self.end_headers();self.wfile.write(payload)
    def do_POST(self):
        payload=self.rfile.read(int(self.headers.get('Content-Length','0')))
        self.send_response(200);self.send_header('Content-Length',str(len(payload)));self.end_headers();self.wfile.write(payload)
    def log_message(self,*args):pass

backend=http.server.ThreadingHTTPServer(('127.0.0.1',18000),Backend)
threading.Thread(target=backend.serve_forever,daemon=True).start()
def score(*args):
    return {'ok':True,'profile':profile,'lab':config['lab'],'name':'Fixture',
        'common_ready':True,'common':{'start':1,'duration':100 if profile=='si' else 200,'freeze':80,'penalty':1200,'problems_sha256':profile+'-different'},
        'frozen':profile=='tii','public_final':profile=='si','teams':[{'name':profile.upper()+' Equipe','team':profile+'01','site':1,'user':2001,'solved':1,'penalty':30 if profile=='si' else 20,'last_accept':20}]}
manage.control=score
admission.serve(folder)
