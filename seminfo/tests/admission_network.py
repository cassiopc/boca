"""Real isolated TLS, native gate, root bridge and kernel lease expiration."""
import hashlib
import json
from pathlib import Path
import secrets
import subprocess
import sys
import tempfile
import time

HERE=Path(__file__).resolve().parents[1]
sys.path.insert(0,str(HERE))
import manage as kit
from admission_setup import ensure_keys
from client_config import rules


def main():
    tag='seminfo-admission-'+secrets.token_hex(4)
    network=tag
    names=[tag+'-'+p for p in ('si','tii','client','native')]
    image='seminfo/client-builder:22.04'
    pins={p:ensure_keys(p) for p in ('si','tii')}
    try:
        kit.run(['docker','network','create',network])
        net=json.loads(kit.run(['docker','network','inspect',network]))[0]['IPAM']['Config'][0]['Subnet']
        for name in names:
            kit.run(['docker','run','-d','--name',name,'--network',network,'--cap-add','NET_ADMIN',
                '--mount',f'type=bind,source={kit.ROOT},target=/kit,readonly',image,'sleep','1200'])
        ips=[kit.inspect(n)['NetworkSettings']['Networks'][network]['IPAddress'] for n in names]
        def execute(index,code,*args):
            return kit.run(['docker','exec','-i',names[index],'python3','-c',code,*args],timeout=90).decode()
        for index,p in enumerate(('si','tii')):
            kit.run(['docker','exec','-d',names[index],'python3','/kit/seminfo/tests/admission_fixture.py',p,ips[index],net])
        time.sleep(2)
        cfg=json.loads((HERE/'labs/si.json').read_text())|{'server_ip':None,'subnet':None,'admission':True,'admission_fingerprint':pins['si']}
        execute(2,"""import json,pathlib,shutil,sys
folder=pathlib.Path('/etc/seminfo');folder.mkdir(parents=True)
(folder/'lab.json').write_text(sys.argv[1])
dest=pathlib.Path('/usr/local/lib/seminfo');dest.mkdir(parents=True)
for name in ('client_config.py','admission_wire.py'):shutil.copy2('/kit/seminfo/'+name,dest/name)
""",json.dumps(cfg))
        kit.run(['docker','exec','-d',names[2],'python3','/kit/seminfo/client/admission-agent.py'])
        def status():
            return json.loads(execute(2,"import urllib.request;print(urllib.request.urlopen('http://127.0.0.1:17631/status').read().decode())"))
        def request(ip):
            execute(2,"""import json,urllib.request,sys
base='http://127.0.0.1:17631'
state=json.load(urllib.request.urlopen(base+'/status'))
req=urllib.request.Request(base+'/request',json.dumps({'server_ip':sys.argv[1],'team':1}).encode(),{'X-SEMINFO-CSRF':state['csrf']})
assert urllib.request.urlopen(req).status==200
""",ip)
        def wait_for(predicate):
            for _ in range(50):
                state=status()
                if predicate(state):return state
                time.sleep(.2)
            raise AssertionError('Tempo esgotado: '+state['status'])
        def control(index,path,payload=None,score=False):
            profile=('si','tii')[index]
            return json.loads(execute(index,"""import json,pathlib,sys
sys.path.insert(0,'/kit/seminfo')
from admission_wire import post
p,ip,pin,path,payload,kind=sys.argv[1:]
token=pathlib.Path('/kit/seminfo/local/'+p+'/admission-'+kind+'.txt').read_text().strip()
result,_=post(ip,pin,path,{'profile':p,**json.loads(payload)},token=token)
print(json.dumps(result))
""",profile,ips[index],pins[profile],path,json.dumps(payload or {}),'score' if score else 'control'))
        def probe(ip,expected,port=8000):
            execute(2,"""import socket,sys
try:s=socket.create_connection((sys.argv[1],int(sys.argv[2])),timeout=.7);s.close();ok=True
except OSError:ok=False
assert ok==(sys.argv[3]=='yes'),(sys.argv[1],sys.argv[2],ok)
""",ip,str(port),'yes' if expected else 'no')
        time.sleep(.5)
        # The desk itself must pass the activation probe through the real gate
        # with the translated BOCA login fixture.
        execute(0,"import sys;sys.path.insert(0,'/kit/seminfo');import manage;manage.probe_web(sys.argv[1])",ips[0])
        probe(ips[0],False)
        request(ips[0]);state=wait_for(lambda s:bool(s['code']))
        assert state['ip']==ips[2] and not state['approved']
        probe(ips[0],True,8443);probe(ips[0],False)
        kit.run(['docker','exec','-u','1000',names[2],'python3','-c',
            "import socket,sys\ntry:s=socket.create_connection((sys.argv[1],8443),timeout=.7)\nexcept OSError:sys.exit(0)\nsys.exit(1)",ips[0]])
        records=control(0,'/control/list')['records'];record=records[-1]
        assert record['ip']==ips[2] and record['code']==state['code']
        control(0,'/control/decision',{'id':record['id'],'code':state['code'],'action':'approve'})
        wait_for(lambda s:s['approved']);probe(ips[0],True)
        probe(ips[1],False);probe(ips[0],False,18000)
        # A participant can submit through the gate, including POST payloads.
        execute(2,"""import urllib.request,sys
url='http://'+sys.argv[1]+':8000/boca/'
assert b'Acesso ao BOCA' in urllib.request.urlopen(url).read()
assert urllib.request.urlopen(url,b'source-code').read()==b'source-code'
""",ips[0])
        control(0,'/control/decision',{'id':record['id'],'action':'revoke'})
        wait_for(lambda s:not s['approved']);probe(ips[0],False)
        # A readonly projector token must not be able to approve anyone.
        try:control(0,'/control/decision',{'id':record['id'],'code':state['code'],'action':'approve'},score=True)
        except RuntimeError:pass
        else:raise AssertionError('Token de projetor aprovou equipe')
        assert control(0,'/control/score',score=True)['public_final']
        assert control(1,'/control/score',score=True)['frozen']
        # Wrong desk is reachable for enrollment but fails the pinned certificate
        # before registration credentials can be transmitted.
        request(ips[1]);time.sleep(2.5)
        assert not status()['approved']
        assert control(1,'/control/list')['records']==[]
        probe(ips[1],False)
        # Generate a genuine kernel lease, then kill the agent without cleanup.
        execute(2,"""import os,signal
from pathlib import Path
for p in Path('/proc').iterdir():
 if p.name.isdigit() and int(p.name)!=os.getpid():
  try:
   cmd=(p/'cmdline').read_bytes()
   if cmd.startswith(b'python3\\0/kit/seminfo/client/admission-agent.py\\0'):os.kill(int(p.name),signal.SIGKILL)
  except (FileNotFoundError,ProcessLookupError):pass
""")
        kit.run(['docker','exec','-i',names[2],'nft','-f','-'],data=rules(ips[0],enrollment=True,lease_seconds=2).encode())
        probe(ips[0],True)
        time.sleep(2.3);probe(ips[0],False)
        # The UI is unprivileged; identities remain inaccessible to UID 1000.
        p=subprocess.run(['docker','exec','-u','1000',names[2],'cat','/home/.seminfo-device.json'],capture_output=True)
        assert p.returncode!=0
        # Distinct questions/times and publication phases stay independent.
        result=execute(0,"""import pathlib,json,sys,threading,urllib.request,urllib.error
sys.path.insert(0,'/kit/seminfo')
from hub import Hub,Panel
from admission import Server
from admission_setup import ensure_keys
desks={}
for p,ip,pin in zip(('si','tii'),json.loads(sys.argv[1]),json.loads(sys.argv[2])):
 base=pathlib.Path('/kit/seminfo/local')/p
 desks[p]={'id':p,'profile':p,'label':'Lab '+p,'server_ip':ip,'fingerprint':pin,'score_token':(base/'admission-score.txt').read_text().strip(),'control_token':(base/'admission-control.txt').read_text().strip()}
hub=Hub({'desks':desks,'manager_profile':'si','general':{'expected_desks':['si','tii']}});hub.refresh()
try:hub.call('tii','/control/decision',{'id':'anything','action':'approve'},control=True)
except ValueError:pass
else:raise AssertionError('Banca SI nao pode aprovar TII')
server=Server(('127.0.0.1',17861),Panel,None);server.hub=hub;server.token='test-token'
threading.Thread(target=server.serve_forever,daemon=True).start()
pages={}
for page in ('si','tii'):
 with urllib.request.urlopen('http://127.0.0.1:17861/projector/'+page) as r:pages[page]=r.read().decode()
assert 'SI Equipe' in pages['si'] and 'TII Equipe' not in pages['si']
assert 'TII Equipe' in pages['tii'] and 'SI Equipe' not in pages['tii']
assert 'congelado' not in pages['si'] and 'final publicado' in pages['si']
assert 'congelado' in pages['tii'] and 'final publicado' not in pages['tii']
try:urllib.request.urlopen('http://127.0.0.1:17861/projector/general')
except urllib.error.HTTPError as e:assert e.code==410
else:raise AssertionError('Ranking unico deveria estar removido')
server.shutdown();print(json.dumps({'independent_categories':True,'cross_category_approval_denied':True,'per_room':True,'independent_freeze_and_final':True}))
""",json.dumps(ips[:2]),json.dumps([pins['si'],pins['tii']]))
        # Exercise the real launcher/probe/panel/stop lifecycle as well as the
        # web handlers. No host scheduler/firewall is touched by this container.
        native=execute(3,"""import json,pathlib,shutil,sys,time
sys.path.insert(0,'/kit/seminfo')
import manage as kit
import admission_setup as setup
kit.LOCAL=pathlib.Path('/tmp/native-fixture/local')
folder=kit.LOCAL/'si';folder.mkdir(parents=True)
c=json.loads(pathlib.Path('/kit/seminfo/labs/si.json').read_text());c.update(server_ip=sys.argv[1],subnet=sys.argv[2])
(folder/'lab.json').write_text(json.dumps(c))
for name in ('admission-cert.pem','admission-key.pem','admission-control.txt','admission-score.txt'):shutil.copy2(pathlib.Path('/kit/seminfo/local/si')/name,folder/name)
setup.start('si');assert setup.running('si')
first=json.loads((folder/'admission-process.json').read_text())
setup.start('si');assert first['pid']==json.loads((folder/'admission-process.json').read_text())['pid']
opened=[];setup.webbrowser.open=opened.append;setup.panel('si');assert opened
first['source_sha256']='outdated';kit.write_json(folder/'admission-process.json',first)
setup.start('si');assert setup.running('si')
assert first['pid']!=json.loads((folder/'admission-process.json').read_text())['pid']
setup.stop('si')
for _ in range(40):
 if not setup.running('si'):break
 time.sleep(.1)
assert not setup.running('si');setup.stop('si')
print('NATIVE_LIFECYCLE_OK')
""",ips[3],net)
        assert 'NATIVE_LIFECYCLE_OK' in native
        kit.write_json(HERE/'reports/admission-network.json',{'passed':True,'checks':[
            'pending deny','source IP observed','pinned TLS approval','BOCA GET/POST','other room denied',
            'backend denied','revocation','score token cannot approve','wrong certificate sends no credentials',
            'kernel expiration after SIGKILL','root-only USB identity','independent questions, times, freeze and final; cross-category administration denied; merged rank retired',
            'native launcher cookie session, idempotent start, source update restart and stop'],
            'hub':json.loads(result),'client_source_sha256':kit.client_source_hash(),'server_source_sha256':kit.source_hash()})
        print('PASSOU: autorizacao TLS, whitelist, revogacao, nftables e bancas/placares independentes SI/TII.')
    finally:
        for name in reversed(names):subprocess.run(['docker','rm','-f',name],capture_output=True)
        subprocess.run(['docker','network','rm',network],capture_output=True)


if __name__=='__main__':main()
