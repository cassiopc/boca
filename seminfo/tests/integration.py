"""Run real BOCA/judge with synthetic data in a separate Docker network."""
import argparse
import datetime as dt
import hashlib
import http.cookiejar
import json
from pathlib import Path
import re
import secrets
import sys
import tempfile
import time
import urllib.request
import urllib.parse
from zipfile import ZipFile,ZIP_DEFLATED
sys.path.insert(0,str(Path(__file__).resolve().parents[1]))
import manage as kit


def fixtures(folder):
    # Reuse GPL execution harness only. No statements or datasets from A.zip.
    with ZipFile(kit.ROOT/'doc/problemsCaramel/A.zip') as original,ZipFile(folder/'A.zip','w',ZIP_DEFLATED) as target:
        for ext in ('c','cpp','java','py3'):
            for stage in ('compile','run','compare'):
                name=f'{stage}/{ext}';target.writestr(name,original.read(name))
            target.writestr('limits/'+ext,'#!/bin/sh\nprintf "2\\n1\\n2048\\n1024\\n"\n')
        target.writestr('input/A_1','20 22\n');target.writestr('output/A_1','42\n')
        target.writestr('description/problem.info','basename=A\nfullname="Ensaio sintetico: soma"\ndescfile=desc.txt\n')
        target.writestr('description/desc.txt','Ensaio sintetico: leia dois inteiros e imprima a soma.\n')
    sources={'Main.c':'#include <stdio.h>\nint main(){int a,b;scanf("%d%d",&a,&b);printf("%d\\n",a+b);}\n',
             'Main.cpp':'#include <iostream>\n#include <concepts>\nint main(){int a,b;std::cin>>a>>b;std::cout<<a+b<<"\\n";}\n',
             'A.java':'import java.util.*; public class A {public static void main(String[]args){Scanner s=new Scanner(System.in);System.out.println(s.nextInt()+s.nextInt());}}\n',
             'Main.py3':'a,b=map(int,input().split());print(a+b)\n',
             'Wrong.c':'#include <stdio.h>\nint main(){puts("0");}\n','CompileError.c':'invalid C source !\n',
             'Timeout.c':'int main(){volatile unsigned long i=0;while(1)i++;}\n',
             'WrongJava.java':'public class A {public static void main(String[]args){System.out.println(0);}}\n',
             'CompileErrorJava.java':'invalid Java source !\n',
             'TimeoutJava.java':'public class A {public static void main(String[]args){while(true){}}}\n'}
    for name,text in sources.items():(folder/name).write_text(text,encoding='utf-8')
    with ZipFile(folder/'Multi.zip','w',ZIP_DEFLATED) as bundle:
        bundle.writestr('A.java','import java.util.*; public class A {public static void main(String[]args){Scanner s=new Scanner(System.in);System.out.println(Sum.add(s.nextInt(),s.nextInt()));}}\n')
        bundle.writestr('Sum.java','class Sum {static int add(int a,int b){return a+b;}}\n')
    return [*sources,'Multi.zip']


def login(base,username,password,role,contest=None):
    cookies=http.cookiejar.CookieJar();client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cookies))
    extra={'contest':contest} if contest is not None else {}
    session=client.open(base+'index.php?'+urllib.parse.urlencode({'getsessionid':1,**extra})).read().decode().strip()
    hashed=hashlib.sha256((hashlib.sha256(password.encode()).hexdigest()+session).encode()).hexdigest()
    response=client.open(base+'index.php?'+urllib.parse.urlencode({'name':username,'password':hashed,**extra})).read().decode()
    if f"{role}/index.php" not in response:raise AssertionError('Login HTTP falhou: '+role)
    page=client.open(base+role+'/index.php').read().decode()
    if any(message in page for message in ('Fatal error','Session expired','A sessão expirou')):raise AssertionError('Sessão HTTP inválida: '+role)
    return client


def main(profile='si'):
    lock=json.loads((kit.DIST/'server.lock.json').read_text())
    tag='seminfo-test-'+secrets.token_hex(4);network=tag+'-net'
    names={kind:tag+'-'+kind for kind in ('db','web','jail')}
    cfg=json.loads((kit.HERE/'labs'/f'{profile}.json').read_text(encoding='utf-8'))
    cfg.update(start=(dt.datetime.now(dt.timezone(dt.timedelta(hours=-3)))-dt.timedelta(minutes=10)).isoformat(),timing_confirmed=True,duration_minutes=100,freeze_minutes_before_end=20)
    # Test instances must never reuse the credentials of either real contest.
    credentials={key:secrets.token_urlsafe(30) for key in
                 ('POSTGRES_PASSWORD','BOCA_DB_PASSWORD','BOCA_PASSWORD','BOCA_KEY','SYSTEM_PASSWORD','SCORE_PASSWORD')}
    credentials.update({f'TEAM_{i:02d}':secrets.token_urlsafe(15) for i in range(1,11)})
    envdir=tempfile.TemporaryDirectory(prefix=tag+'-')
    envfile=Path(envdir.name)/'test.env'
    envfile.write_text(''.join(f'{k}={v}\n' for k,v in credentials.items() if k.startswith('BOCA_'))+f'SEMINFO_PROFILE={profile}\n',encoding='utf-8')
    def control(action,extra=None):
        output=kit.run(['docker','exec','-i',names['web'],'php','/opt/seminfo/control.php',action],data=json.dumps({'config':cfg,'credentials':credentials,**(extra or {})}).encode())
        try:return json.loads(output)
        except json.JSONDecodeError:
            target=kit.HERE/'reports'/f'{tag}-{action}.txt';target.parent.mkdir(parents=True,exist_ok=True);target.write_bytes(output)
            raise RuntimeError(f'Controle sem JSON; consulte {target}') from None
    def fixture(action):
        return json.loads(kit.run(['docker','exec',names['web'],'php','/tmp/fixture.php',action]))
    kit.run(['docker','network','create',network])
    try:
        kit.run(['docker','run','-d','--name',names['db'],'--network',network,'--network-alias','boca-db','-e','POSTGRES_PASSWORD='+credentials['POSTGRES_PASSWORD'],lock['images']['db']['reference']])
        kit.wait_database(names['db'])
        kit.run(['docker','run','-d','--name',names['web'],'--network',network,'--env-file',envfile,'-e','BOCA_DB_HOST=boca-db','-e','BOCA_DB_SUPER_USER=postgres','-e','BOCA_DB_SUPER_PASSWORD='+credentials['POSTGRES_PASSWORD'],'-p','127.0.0.1::80',lock['images']['web']['reference']])
        for _ in range(60):
            state=kit.inspect(names['web'])['State']
            if state['Status']=='exited':raise RuntimeError('Web isolado encerrou no bootstrap; consulte reports')
            if state.get('Health',{}).get('Status')=='healthy':break
            time.sleep(1)
        else:raise RuntimeError('Web isolado nao ficou saudavel')
        assert control('prepare')['ok']
        port=kit.inspect(names['web'])['NetworkSettings']['Ports']['80/tcp'][0]['HostPort']
        base=f'http://127.0.0.1:{port}/boca/'
        login(base,'system',credentials['SYSTEM_PASSWORD'],'system')
        login(base,'admin',credentials['BOCA_PASSWORD'],'admin',contest=1)
        try:login(base,profile+'01',credentials['TEAM_01'],'team',contest=1)
        except AssertionError:pass
        else:raise AssertionError('Equipe entrou em contest inativo pela selecao explicita')
        with tempfile.TemporaryDirectory() as tmp:
            folder=Path(tmp);sources=fixtures(folder)
            for name in ['A.zip',*sources]:kit.run(['docker','cp',folder/name,f'{names["web"]}:/tmp/{name}'])
            kit.run(['docker','cp',kit.HERE/'tests/fixture.php',f'{names["web"]}:/tmp/fixture.php'])
            assert fixture('seed')['ok']
            assert control('activate')['ok']
            port=kit.inspect(names['web'])['NetworkSettings']['Ports']['80/tcp'][0]['HostPort']
            base=f'http://127.0.0.1:{port}/boca/'
            login(base,'admin',credentials['BOCA_PASSWORD'],'admin')
            login(base,'placar',credentials['SCORE_PASSWORD'],'score')
            for i in range(1,11):login(base,f'{profile}{i:02d}',credentials[f'TEAM_{i:02d}'],'team')
            for url in ('admin/report/webcast.php?webcastcode=allUsers','admin/report/webcast.php?full=1'):
                try:urllib.request.urlopen(base+url)
                except urllib.error.HTTPError as exc:assert exc.code==403
                else:raise AssertionError('Webcast completo acessivel pela rede')
            expected=fixture('submit')['expected']
            kit.run(['docker','run','-d','--privileged','--name',names['jail'],'--network',network,'--env-file',envfile,'-e','BOCA_DB_HOST=boca-db','-e','BOCA_JAIL_HOST=boca-jail',lock['images']['jail']['reference']])
            previous=-1
            for _ in range(240):
                rows=fixture('results')
                completed=sum(row['runstatus']=='judged' for row in rows)
                if completed!=previous:print(f'Juiz: {completed}/{len(expected)}',flush=True);previous=completed
                if completed==len(expected):break
                time.sleep(2)
            else:
                kit.write_json(kit.HERE/'reports/integration-failed.json',rows)
                raise RuntimeError('Fila nao concluiu; resultados em reports/integration-failed.json')
            for row in rows:
                if int(row['runanswer'])!=expected[str(row['runnumber'])]:
                    kit.write_json(kit.HERE/'reports/integration-diagnostic.json',fixture('diagnostic'))
                    raise AssertionError('Veredito inesperado: '+json.dumps(row))
            data=control('export')
            public_score=control('scoreboard')
            assert public_score['ok'] and public_score['common_ready']
            assert [(r['team'],r['solved'],r['penalty']) for r in public_score['teams']]==[(r['team'],r['solved'],r['penalty']) for r in data['official']]
            from webcast import prepare_export
            report=kit.HERE/'reports'/tag
            prepare_export(data,cfg,report)
            kit.write_json(report/'java-diagnostic.json',fixture('diagnostic'))
            assert fixture('freeze')['ok']
            frozen=control('scoreboard')
            assert frozen['frozen'] and all(t['solved']==0 for t in frozen['teams'])
            assert not control('scoreboard',{'public_final':True})['public_final'], 'Resultado final publicado antes do fim'
            full=control('export')
            assert any(t['solved']>0 for t in full['official']), 'Fixture sem resultado oculto para conferir o freeze'
            kit.run(['docker','stop',names['jail']])
            assert fixture('end')['ok']
            final=control('scoreboard',{'public_final':True})
            assert final['public_final'] and not final['frozen'] and any(t['solved']>0 for t in final['teams'])
            assert fixture('pending')['ok']
            assert not control('scoreboard',{'public_final':True})['public_final'], 'Pendencia deveria fechar publicacao final'
            assert control('export')['pending_runs']==1
            assert fixture('rejudge')['ok']
            assert not control('scoreboard',{'public_final':True})['public_final'], 'Rejulgamento judged+ nao e resultado final'
            assert control('export')['pending_runs']==1
            assert fixture('deleted')['ok']
            assert control('scoreboard',{'public_final':True})['public_final'], 'Submissao excluida nao e pendencia'
            assert fixture('judged')['ok']
            # Prove recovery with actual source/output large objects, not just
            # an empty administrative database.
            kit.run(['docker','stop',names['web'],names['jail']])
            backup=report/'recovery';kit.private_directory(backup)
            fingerprint=kit.fingerprint(names['db'])
            kit.run(['docker','exec',names['db'],'pg_dump','-U','postgres','-d','bocadb','-Fc','-b','-f','/tmp/test.dump'])
            kit.run(['docker','cp',f'{names["db"]}:/tmp/test.dump',backup/'database.dump'])
            kit.write_json(backup/'manifest.json',{'profile':profile,'dump_sha256':kit.digest(backup/'database.dump'),'fingerprint':fingerprint,'database_image':kit.inspect(names['db'])['Image']})
            kit.check_restore(backup)
            kit.write_json(kit.HERE/'reports/integration.json',{'ok':True,'profile':profile,'submissions':len(expected),'teams':10,'languages':4,'http_logins':14,'http_users':13,'system_login':True,'inactive_admin_login':True,'inactive_team_rejected':True,'webcast_http_blocked':True,'scoreboard_parity':True,'scoreboard_freeze_no_leak':True,'final_rejects_rejudge':True,'recovery_with_largeobjects':True,'source_sha256':lock['source_sha256'],'utc':dt.datetime.now(dt.timezone.utc).isoformat()})
            print('PASSOU: 10 equipes, 4 linguagens, AC/WA/CE/TLE, login HTTP, placar e paridade.',flush=True)
    except Exception:
        for kind in ('web','jail'):
            if kit.exists(names[kind]):
                output=kit.run(['docker','logs','--tail','120',names[kind]])
                target=kit.HERE/'reports'/f'{tag}-{kind}.log';target.parent.mkdir(parents=True,exist_ok=True);target.write_bytes(output)
        raise
    finally:
        envfile.unlink(missing_ok=True)
        envdir.cleanup()
        for name in names.values():
            subprocess=__import__('subprocess')
            subprocess.run(['docker','rm','-f','-v',name],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL,**kit.hidden_process_options())
        __import__('subprocess').run(['docker','network','rm',network],stdout=__import__('subprocess').DEVNULL,stderr=__import__('subprocess').DEVNULL,**kit.hidden_process_options())


if __name__=='__main__':
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--profile',choices=('si','tii'),default='si')
    main(parser.parse_args().profile)
