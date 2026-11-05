"""Run with local/animeitor-venv/Scripts/python.exe, headless SDL."""
import json
import os
from pathlib import Path
import subprocess
import sys
sys.path.insert(0,str(Path(__file__).resolve().parents[1]))
import manage
from webcast import prepare_export

def main():
    cfg=json.loads((manage.HERE/'labs/si.json').read_text(encoding='utf-8'))
    cfg.update(duration_minutes=100,freeze_minutes_before_end=20)
    data={'ok':True,'contest_id':1,'site_id':1,'duration_seconds':6000,'elapsed_seconds':6000,'name':'SEMINFO — ensaio de revelação',
          'teams':[{'id':'si01','name':'Equipe Açúcar','number':2001,'site':1},{'id':'si02','name':'Equipe Caramelo','number':2002,'site':1}],
          'problems':[{'name':'A'}],
          'runs':[{'id':1,'seconds':300,'team':'si01','problem':'A','answer':2,'yes':'f','status':'judged'},
                  {'id':2,'seconds':600,'team':'si01','problem':'A','answer':6,'yes':'f','status':'judged'},
                  {'id':3,'seconds':5400,'team':'si01','problem':'A','answer':1,'yes':'t','status':'judged'},
                  {'id':4,'seconds':1200,'team':'si02','problem':'A','answer':1,'yes':'t','status':'judged'}],
          'official':[{'team':'si02','solved':1,'penalty':20},{'team':'si01','solved':1,'penalty':110}]}
    import secrets
    folder=manage.HERE/'reports'/('animeitor-smoke-'+secrets.token_hex(3))
    prepare_export(data,cfg,folder)
    env={**os.environ,'SDL_VIDEODRIVER':'dummy','SDL_AUDIODRIVER':'dummy','PYGAME_HIDE_SUPPORT_PROMPT':'1'}
    subprocess.run([sys.executable,manage.HERE/'animeitor_runner.py',folder,'--smoke-test'],env=env,check=True,timeout=30)
    print(folder)


if __name__=="__main__":main()
