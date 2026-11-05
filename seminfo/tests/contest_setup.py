"""Real native author/import/edit + isolated reference judge, synthetic data only."""
import argparse
import base64
import datetime as dt
import hashlib
import io
import json
from pathlib import Path
import secrets
import subprocess
import sys
import tempfile
import time
import urllib.request
import urllib.parse
from html.parser import HTMLParser
from unittest.mock import patch
from zipfile import ZipFile

sys.path.insert(0,str(Path(__file__).resolve().parents[1]))
import manage as kit
import organizer
import problems
from integration import fixtures, login


def multipart(fields,files):
    boundary='seminfo-'+secrets.token_hex(16)
    data=b''
    for name,value in fields.items():
        data+=f'--{boundary}\r\nContent-Disposition: form-data; name="{name}"\r\n\r\n{value}\r\n'.encode()
    for name,(filename,raw) in files.items():
        data+=f'--{boundary}\r\nContent-Disposition: form-data; name="{name}"; filename="{filename}"\r\nContent-Type: application/octet-stream\r\n\r\n'.encode()+raw+b'\r\n'
    return data+f'--{boundary}--\r\n'.encode(),'multipart/form-data; boundary='+boundary


def main(profile="si"):
    tag='seminfo-author-'+secrets.token_hex(5)
    names={k:tag+'-'+k for k in ('db','web','jail')};network=tag+'-net'
    lock=json.loads((kit.DIST/'server.lock.json').read_text())
    assert lock['source_sha256']==kit.source_hash()
    raw_run,raw_exists=kit.run,kit.exists
    actual_before={p:kit.fingerprint(f'boca-{p}-boca-db-1') for p in ('si','tii')}
    with tempfile.TemporaryDirectory() as temp:
        root=Path(temp);local=root/'local';(local/profile).mkdir(parents=True)
        cfg=json.loads((kit.HERE/'labs'/f'{profile}.json').read_text())
        cfg.update(start=(dt.datetime.now(dt.timezone.utc)-dt.timedelta(minutes=5)).isoformat(),
                   duration_minutes=120,freeze_minutes_before_end=0,timing_confirmed=True)
        credentials={key:secrets.token_hex(24) for key in ('POSTGRES_PASSWORD','BOCA_DB_PASSWORD','BOCA_PASSWORD','BOCA_KEY','SYSTEM_PASSWORD','SCORE_PASSWORD')}
        credentials.update({f'TEAM_{i:02}':secrets.token_hex(16) for i in range(1,11)})
        # Keep the saved user config on the real event date; only the disposable judge starts today.
        kit.write_json(local/profile/'lab.json',json.loads((kit.HERE/'labs'/f'{profile}.json').read_text()))
        kit.write_json(local/profile/'credentials.json',credentials)
        env=root/'isolated.env'
        env.write_text(''.join(k+'='+v+'\n' for k,v in credentials.items())+'SEMINFO_PROFILE='+profile+'\n')
        def control(selected,action,extra=None):
            assert selected==profile
            return json.loads(raw_run(['docker','exec','-i',names['web'],'php','/opt/seminfo/control.php',action],
                             data=json.dumps({'config':cfg,'credentials':credentials,**(extra or {})}).encode()))
        mapping={f'boca-{profile}-boca-db-1':names['db'],f'boca-{profile}-boca-web-1':names['web']}
        def mapped(args,**kwargs):
            def target(arg):
                value=str(arg)
                for old,new in mapping.items():
                    if value==old or value.startswith(old+':'):return new+value[len(old):]
                return arg
            return raw_run([target(a) for a in args],**kwargs)
        raw_run(['docker','network','create',network])
        try:
            raw_run(['docker','run','-d','--name',names['db'],'--network',network,'--network-alias','boca-db',
                     '--health-cmd','pg_isready -U postgres','--health-interval','2s','--health-retries','20',
                     '--env-file',env,lock['images']['db']['reference']])
            kit.wait_database(names['db'])
            raw_run(['docker','run','-d','--name',names['web'],'--network',network,'--env-file',env,
                     '-e','BOCA_DB_HOST=boca-db','-e','BOCA_DB_SUPER_USER=postgres','-p','127.0.0.1::80',
                     '--entrypoint','/bin/bash',lock['images']['web']['reference'],'-c',
                     'export BOCA_DB_SUPER_PASSWORD="$POSTGRES_PASSWORD"; exec /opt/seminfo/server-init.sh'])
            for _ in range(90):
                if kit.inspect(names['web'])['State'].get('Health',{}).get('Status')=='healthy':break
                time.sleep(1)
            else:raise RuntimeError('Authoring test web not healthy')
            assert control(profile,'prepare')['ok']
            port=kit.inspect(names['web'])['NetworkSettings']['Ports']['80/tcp'][0]['HostPort']
            base=f'http://127.0.0.1:{port}/boca/'
            # Probe the actual localized login page through the same HTTP check
            # used before activation; only the disposable port is redirected.
            real_opener=urllib.request.build_opener(urllib.request.ProxyHandler({}))
            class ProbeOpener:
                def open(self,url,timeout):
                    assert url=='http://127.0.0.1:8000/boca/'
                    return real_opener.open(base,timeout=timeout)
            with patch.object(kit.urllib.request,'build_opener',return_value=ProbeOpener()):
                kit.probe_web('127.0.0.1')
            print('Verificação HTTP do BOCA traduzido: OK',flush=True)
            client=login(base,'admin',credentials['BOCA_PASSWORD'],'admin',contest=1)
            with client.open(base+'index.php?contest=1&destination=builder') as response:
                assert response.url.endswith('admin/buildproblem.php')
                assert b'Gerar ZIP' in response.read()
            with client.open(base+'index.php?contest=1&destination=problems') as response:
                assert response.url.endswith('admin/problem.php')
            # Exercise the translated buttons using the actual name/value emitted
            # by the rendered form; preserve the site's initial login setting.
            class Buttons(HTMLParser):
                def __init__(self):
                    super().__init__(); self.active=None; self.labels={}
                def handle_starttag(self,tag,attrs):
                    if tag=='button': self.active=(dict(attrs),[])
                def handle_data(self,data):
                    if self.active: self.active[1].append(data)
                def handle_endtag(self,tag):
                    if tag=='button' and self.active:
                        attrs,parts=self.active; self.labels[''.join(parts).strip()]=attrs; self.active=None
            page=client.open(base+'admin/site.php?site=1').read().decode('utf-8')
            assert 'Encerramento automático:' in page and 'Ativo:' in page
            assert 'Use este botão apenas quando necessário' in page
            user_sql="SELECT usernumber FROM usertable WHERE contestnumber=1 AND username='"+profile+"01'"
            team_number=raw_run(['docker','exec',names['db'],'psql','-U','postgres','-d','bocadb','-Atc',user_sql]).decode().strip()
            assert team_number.isdecimal(),team_number
            user_page=client.open(base+'admin/user.php?site=1&user='+team_number).read().decode('utf-8')
            assert 'value=\"team\">Equipe</option>' in user_page and 'value=\"judge\">Juiz</option>' in user_page
            assert '>Equipe&nbsp;</td>' in user_page and '>Administrador&nbsp;</td>' in user_page
            assert 'Para importar usuários, selecione o arquivo de importação.' in user_page
            buttons=Buttons(); buttons.feed(page)
            login_sql="SELECT sitepermitlogins FROM sitetable WHERE contestnumber=1 AND sitenumber=1"
            def login_state():
                return raw_run(['docker','exec',names['db'],'psql','-U','postgres','-d','bocadb','-Atc',login_sql]).decode().strip()
            initial=login_state()
            assert initial in ('t','f'),initial
            # Force the stored version ahead of the current second to reproduce
            # consecutive local changes deterministically, even on slower hosts.
            raw_run(['docker','exec',names['db'],'psql','-U','postgres','-d','bocadb','-c',
                     "UPDATE sitetable SET updatetime=extract(epoch from now())::bigint+10 WHERE contestnumber=1 AND sitenumber=1"])
            for label,expected in [('Desabilitar acessos','f'),('Habilitar acessos','t')]+([('Desabilitar acessos','f')] if initial=='f' else []):
                button=buttons.labels[label]
                assert button['name']=='Logins'
                data=urllib.parse.urlencode({'site':'1','confirmation':'confirm',button['name']:button['value']}).encode()
                client.open(urllib.request.Request(base+'admin/site.php?site=1',data),timeout=30).read()
                assert login_state()==expected,(label,login_state())
            print('Botões traduzidos de acesso: desabilitar e habilitar OK',flush=True)
            def post(fields,files):
                data,kind=multipart(fields,files)
                with client.open(urllib.request.Request(base+'admin/problem.php',data,{'Content-Type':kind}),timeout=30) as response:return response.read()
            def build(answer=b'42\n'):
                fields={'SubmitWizard':'Gerar ZIP','fullname':'Soma sintetica','basename':'A',
                        'time_limit':'2','memory_limit':'512','output_limit':'1024','repetitions':'1',
                        'statement_mode':'latex','latex_title':'Soma sintética',
                        'latex_description':'Some dois números.', 'latex_input':'Dois inteiros.',
                        'latex_output':'A soma.', 'case_input[]':'20 22\n',
                        'case_output[]':answer.decode(), 'case_visibility[]':'public'}
                data,kind=multipart(fields,{})
                with client.open(urllib.request.Request(base+'admin/buildproblem.php',data,{'Content-Type':kind}),timeout=60) as response:
                    package=response.read()
                with ZipFile(io.BytesIO(package)) as archive:
                    assert archive.read('description/statement.pdf').startswith(b'%PDF')
                    for ext in ('c','cpp','java','py3'):
                        for stage in ('compile','run','compare','limits'):assert stage+'/'+ext in archive.namelist()
                return package
            def publish(package):
                post({'Submit3':'Send','confirmation':'confirm','noflush':'true','problemnumber':'1','problemname':'A',
                      'colorname':'Amarelo','color':'ffcc00','autojudge_new_sel':'all'}, {'probleminput':('A.zip',package)})
                # BOCA fills metadata by reading problem.info when the list is rendered.
                client.open(base+'admin/problem.php').close()
            package=build()
            def sql(query):
                return raw_run(['docker','exec',names['db'],'psql','-U','postgres','-d','bocadb','-Atc',query]).decode().strip()
            # Exercise sparse problem and answer numbers against the real admin form.
            sql("INSERT INTO answertable(contestnumber,answernumber,runanswer,shortname,yes,fake) VALUES (1,10,'Resposta de ensaio','EX','f','f')")
            post({'Submit3':'Send','confirmation':'confirm','noflush':'true','problemnumber':'7','problemname':'B',
                  'colorname':'Amarelo','color':'ffcc00','autojudge_new_sel':'custom','autojudge_chc_new_10':'1'},
                 {'probleminput':('B.zip',package)})
            class Inputs(HTMLParser):
                def __init__(self):
                    super().__init__(); self.inputs=[]
                def handle_starttag(self,tag,attrs):
                    if tag=='input': self.inputs.append(dict(attrs))
            page=client.open(base+'admin/problem.php').read().decode('utf-8')
            inputs=Inputs(); inputs.feed(page)
            for prefix in ('7','new'):
                checkbox=next(item for item in inputs.inputs if item.get('name')==f'autojudge_chc_{prefix}_10')
                assert checkbox['id'].startswith(f'autojudge_chc_{prefix}_')
                assert f'for="{checkbox["id"]}"' in page
            assert 'name="SubmitProblemAJ7" value="Update">Atualizar</button>' in page
            assert all(label in page for label in ('Todas as respostas','Personalizado','Nenhuma'))
            mask_sql='SELECT problemautojudge FROM problemtable WHERE contestnumber=1 AND problemnumber=7'
            assert int(sql(mask_sql))==1<<10
            sql('UPDATE problemtable SET updatetime=extract(epoch from now())::bigint+10 WHERE contestnumber=1 AND problemnumber=7')
            answer_numbers=sql("SELECT answernumber FROM answertable WHERE contestnumber=1 AND fake='f' AND runanswer !~ '(DEL)'").splitlines()
            for mode,expected in [('none',0),('custom',1<<10),('all',sum(1<<int(n) for n in answer_numbers))]:
                post({'SubmitProblemAJ7':'Update','autojudge_7_sel':mode,'autojudge_chc_7_10':'1'}, {})
                assert int(sql(mask_sql))==expected,(mode,sql(mask_sql),expected)
            sql('DELETE FROM problemtable WHERE contestnumber=1 AND problemnumber=7')
            sql('DELETE FROM answertable WHERE contestnumber=1 AND answernumber=10')
            print('Julgamento automático: rótulos, numeração não sequencial e alterações rápidas OK',flush=True)
            publish(package)
            with patch.object(kit,'LOCAL',local),patch.object(kit,'run',mapped),patch.object(kit,'exists',side_effect=lambda x:raw_exists(mapping.get(str(x),x))),patch.object(kit,'control',side_effect=control),patch('admission_setup.running',return_value=False):
                problem=problems.catalog(profile)[0]
                assert problem['basename']=='A' and problem['title']=='Soma sintetica'
                fixtures(root)
                refs=[{'name':name,'content':base64.b64encode((root/name).read_bytes()).decode()} for name in ('Main.c','Main.cpp','A.java','Main.py3')]
                before=kit.fingerprint(names['db'])
                good=problems.validate(profile,{'problem':1,'revision':problem['revision'],'sources':refs})
                if not good['passed']:kit.write_json(kit.HERE/'reports/contest-setup-reference-debug.json',good)
                assert good['passed'] and good['complete'],good['results']
                assert kit.fingerprint(names['db'])==before,'Reference run changed preparation database'
                assert problems.assert_reviewable(profile)
                print('Native builder/import and four accepted reference languages: OK',flush=True)
                # Edit the package through the same admin importer; prior success must expire.
                time.sleep(1.1)
                publish(build(b'99\n'))
                changed=problems.catalog(profile)[0]
                assert changed['revision']!=problem['revision']
                assert not problems.read_report(profile,changed)['current']
                wrong=[{'name':'Wrong.c','content':base64.b64encode((root/'Wrong.c').read_bytes()).decode()}]
                before=kit.fingerprint(names['db'])
                bad=problems.validate(profile,{'problem':1,'revision':changed['revision'],'sources':wrong})
                assert not bad['passed'] and not bad['complete'],bad
                assert kit.fingerprint(names['db'])==before
                # The normal bank backup must recover reference sources/reports too.
                with patch.object(kit,'HERE',root/'backup-kit'):
                    backup=kit.backup(profile)
                    kit.check_restore(backup)
                    manifest=json.loads((backup/'manifest.json').read_text())
                    assert set(manifest['reference_files_sha256'])=={'1/sources.json','1/validation.json'}
                    checked=json.loads((backup/'restore-verified.json').read_text())
                    assert checked['reference_files_sha256']==manifest['reference_files_sha256']
                # Restore a valid reference set for the edited synthetic package.
                synthetic_sources={
                    'Main.c':'#include <stdio.h>\nint main(){puts("99");}\n',
                    'Main.cpp':'#include <iostream>\nint main(){std::cout << 99 << "\\n";}\n',
                    'A.java':'public class A {public static void main(String[] args){System.out.println(99);}}\n',
                    'Main.py3':'print(99)\n',
                }
                refs99=[{'name':name,'content':base64.b64encode(code.encode()).decode()} for name,code in synthetic_sources.items()]
                approved=problems.validate(profile,{'problem':1,'revision':changed['revision'],'sources':refs99})
                assert approved['passed'] and approved['complete'],approved['results']
                reviewed=problems.assert_reviewable(profile)
                raw_run(['docker','run','-d','--privileged','--name',names['jail'],
                         '--network',network,'--network-alias','boca-jail','--env-file',env,
                         '-e','BOCA_DB_HOST=boca-db','-e','BOCA_JAIL_HOST=boca-jail',
                         lock['images']['jail']['reference']])
                mapping[f'boca-{profile}-boca-jail-1']=names['jail']
                for _ in range(90):
                    if all(kit.inspect(names[k])['State'].get('Health',{}).get('Status')=='healthy' for k in ('db','web','jail')):break
                    time.sleep(1)
                else:raise RuntimeError('Serviços isolados não ficaram saudáveis')
                # These attestations exist solely under the temporary test directory.
                # They simulate the physical prerequisites; they do not homologate a lab.
                synthetic_cfg=json.loads((local/profile/'lab.json').read_text(encoding='utf-8'))
                synthetic_cfg.update(server_ip='192.0.2.2',subnet='192.0.2.0/24',
                                     timing_confirmed=True,duration_minutes=120,
                                     freeze_minutes_before_end=0,stop_answering_minutes_before_end=0)
                records={key:{'config_sha256':kit.config_hash(synthetic_cfg),
                              'server_source_sha256':kit.source_hash(),
                              'client_source_sha256':kit.client_source_hash(),
                              'operator':'ensaio isolado'} for key in kit.CHECKS}
                records['problems_reviewed']['problems_sha256']=reviewed
                kit.write_json(local/profile/'attestations.json',records)
                port=kit.inspect(names['web'])['NetworkSettings']['Ports']['80/tcp'][0]['HostPort']
                base=f'http://127.0.0.1:{port}/boca/'
                class ActivationProbeOpener:
                    def open(self,url,timeout):
                        assert url=='http://192.0.2.2:8000/boca/'
                        return real_opener.open(base,timeout=timeout)
                with patch.object(kit,'config',return_value=synthetic_cfg), \
                     patch.object(kit.urllib.request,'build_opener',return_value=ActivationProbeOpener()):
                    kit.activate(profile)
                active=raw_run(['docker','exec',names['db'],'psql','-U','postgres','-d','bocadb','-Atc',"SELECT contestactive FROM contesttable WHERE contestnumber=1"]).decode().strip()
                assert active=='t',active
                print('Ativação pela Central, com pré-requisitos sintéticos: OK',flush=True)
                for _ in range(60):
                    if kit.inspect(names['web'])['State'].get('Health',{}).get('Status')=='healthy':break
                    time.sleep(1)
                port=kit.inspect(names['web'])['NetworkSettings']['Ports']['80/tcp'][0]['HostPort']
                base=f'http://127.0.0.1:{port}/boca/'
                client=login(base,'admin',credentials['BOCA_PASSWORD'],'admin',contest=1)
                # A mutation attempted against an active event must be ignored by the native page.
                before=problems.catalog(profile)
                publish(package)
                assert problems.catalog(profile)==before,'Active contest package was modified'
                # Exercise the same multipart form used by a real team, not
                # the organizer-only reference seeding API. The edited package
                # expects 99; send one correct and one incorrect C solution.
                team=login(base,profile+'01',credentials['TEAM_01'],'team')
                temporary_password=secrets.token_hex(16)
                password_hash='!'+hashlib.sha256(temporary_password.encode()).hexdigest()
                for number,username,role in ((3001,'juizteste','judge'),(3002,'apoioteste','staff')):
                    query=("INSERT INTO usertable(contestnumber,usersitenumber,usernumber,username,userfullname,userdesc,usertype,userenabled,usermultilogin,userpassword,usersession,usersessionextra,usericpcid,userinfo) "
                           f"VALUES(1,1,{number},'{username}','Ensaio','','{role}',true,false,'{password_hash}','','','','')")
                    raw_run(['docker','exec',names['db'],'psql','-U','postgres','-d','bocadb','-c',query])
                raw_run(['docker','exec',names['db'],'psql','-U','postgres','-d','bocadb','-c',
                         "UPDATE sitetable SET sitechiefname='juizteste' WHERE contestnumber=1 AND sitenumber=1"])
                judge=login(base,'juizteste',temporary_password,'judge')
                staff=login(base,'apoioteste',temporary_password,'staff')
                # Visit read-only pages with real authenticated sessions. Ignore
                # scripts and styles: internal form values and protocol strings
                # are deliberately kept in their original language.
                class VisibleText(HTMLParser):
                    def __init__(self):
                        super().__init__(); self.hidden=0; self.parts=[]
                    def handle_starttag(self,tag,attrs):
                        if tag in ('script','style'): self.hidden+=1
                    def handle_endtag(self,tag):
                        if tag in ('script','style') and self.hidden: self.hidden-=1
                    def handle_data(self,data):
                        if not self.hidden: self.parts.append(data)
                pages={client:['admin/contest.php','admin/site.php','admin/user.php','admin/language.php',
                               'admin/answer.php','admin/problem.php','admin/buildproblem.php','admin/run.php',
                               'admin/clar.php','admin/task.php','admin/files.php','admin/report.php',
                               'admin/misc.php','admin/option.php'],
                       team:['team/problem.php','team/run.php','team/clar.php','team/task.php',
                             'team/files.php','team/option.php','team/score.php']}
                pages[login(base,'system',credentials['SYSTEM_PASSWORD'],'system')]=['system/contest.php','system/importxml.php','system/option.php']
                pages[login(base,'placar',credentials['SCORE_PASSWORD'],'score')]=['score/index.php','score/score.php']
                pages[judge]=['judge/run.php','judge/runchief.php','judge/allrunlist.php','judge/clar.php',
                              'judge/history.php','judge/team.php','judge/option.php','judge/score.php']
                pages[staff]=['staff/run.php','staff/task.php','staff/option.php','staff/score.php']
                english=('still open','auto-ended','in progress','runs renewed for autojudging',
                         're-run autojudge for selected runs','news (last','as of 1 minutes',
                         'clarifications (','runs (','chief (')
                checked=0; text_audit={}
                for browser,paths in pages.items():
                    for path in paths:
                        response=browser.open(base+path,timeout=30)
                        html=response.read().decode('utf-8')
                        assert response.url.endswith(path), (path,response.url)
                        visible=VisibleText(); visible.feed(html); text=' '.join(visible.parts).casefold()
                        assert 'fatal error' not in text,(path,text[:500])
                        assert 'usuário:' in text,(path,'Cabeçalho autenticado ausente')
                        assert not any(fragment in text for fragment in english),(path,text[:500])
                        if path.endswith('/option.php'):
                            assert 'repita a nova senha:' in text and 'retype' not in text, path
                        if path=='admin/language.php':
                            assert 'extensão:' in text and 'extension:' not in text
                        if path=='admin/site.php':
                            assert 'sede local' in text and 'local site' not in text
                        if path=='admin/user.php':
                            assert 'tenha cuidado ao usar um número de usuário já existente' in text
                            assert 'specially care' not in text
                        if path in ('admin/task.php','team/task.php','staff/task.php'):
                            assert 'ajuda e impressão' in text,(path,'Menu de pedidos ausente')
                        if path=='admin/contest.php':
                            assert 'a configuração do php permite no máximo:' in text and 'expiração da sessão:' in text
                            assert 'your php config' not in text and 'session expiration' not in text
                        if path=='admin/problem.php':
                            assert '(interno)' in text and '(fake)' not in text and 'geral' in text
                        if path in ('team/clar.php','judge/clar.php'):
                            assert '<option value="0">Geral</option>' in html
                            assert '<option value="1">A</option>' in html
                        if path=='judge/runchief.php':
                            assert 'juiz chefe (' in text and 'dúvidas (' in text and 'submissões (' in text
                        text_audit[path]=' '.join(' '.join(visible.parts).split())
                        checked+=1
                kit.write_json(kit.HERE/'reports'/f'ui-text-synthetic-{profile}.json',
                               {'profile':profile,'synthetic_environment':True,'pages':text_audit})
                print(f'Navegação autenticada e textos visíveis: {checked} páginas conferidas',flush=True)
                # Create a real support request and verify both the translated
                # display and unchanged internal states through completion.
                request=urllib.parse.urlencode({'Submit':'S.O.S.','confirmation':'confirm'}).encode()
                team.open(urllib.request.Request(base+'team/task.php',request),timeout=30).read()
                sql="SELECT tasknumber FROM tasktable WHERE contestnumber=1 AND taskdesc='Staff assistance' ORDER BY tasknumber DESC LIMIT 1"
                number=raw_run(['docker','exec',names['db'],'psql','-U','postgres','-d','bocadb','-Atc',sql]).decode().strip()
                assert number.isdecimal(),number
                page=team.open(base+'team/task.php').read().decode('utf-8')
                assert 'Solicitação de ajuda' in page and 'Aguardando' in page
                page=client.open(base+'admin/task.php').read().decode('utf-8')
                assert 'Solicitação de ajuda' in page and 'Aguardando' in page
                client.open(base+'admin/task.php?get='+number+'&site=1').read()
                page=team.open(base+'team/task.php').read().decode('utf-8')
                assert 'Em atendimento' in page
                client.open(base+'admin/task.php?done='+number+'&site=1').read()
                page=team.open(base+'team/task.php').read().decode('utf-8')
                assert 'Concluído' in page
                print('Pedido de ajuda: equipe, atendimento e conclusão OK',flush=True)
                # Clarification status labels must not alter the stored BOCA code.
                payload=urllib.parse.urlencode({'message':'Dúvida geral de teste','problem':'0','Submit':'Enviar','confirmation':'confirm'}).encode()
                team.open(urllib.request.Request(base+'team/clar.php',payload),timeout=30).read()
                sql="SELECT clarnumber FROM clartable WHERE contestnumber=1 AND clarproblem=0 ORDER BY clarnumber DESC LIMIT 1"
                number=raw_run(['docker','exec',names['db'],'psql','-U','postgres','-d','bocadb','-Atc',sql]).decode().strip()
                assert number.isdecimal(),number
                page=client.open(base+'admin/clar.php').read().decode('utf-8')
                assert 'Aguardando resposta' in page and 'Ainda sem resposta' in page and '>Geral</td>' in page
                assert '>Geral</td>' in team.open(base+'team/clar.php').read().decode('utf-8')
                assert '>Geral</td>' in judge.open(base+'judge/clar.php').read().decode('utf-8')
                assert '>Geral</td>' in client.open(base+'admin/report/clar.php').read().decode('utf-8')
                payload=urllib.parse.urlencode({'answer':'Resposta de teste','Submit':'Answer','number':number,'sitenumber':'1','confirmation':'confirm'}).encode()
                client.open(urllib.request.Request(base+'admin/claredit.php',payload),timeout=30).read()
                page=team.open(base+'team/clar.php').read().decode('utf-8')
                assert 'Resposta de teste' in page and '>Geral</td>' in page
                page=client.open(base+'admin/clar.php').read().decode('utf-8')
                assert 'Respondida' in page
                print('Dúvida: envio, estado e resposta OK',flush=True)
                # A team backup must be downloadable and removable; the admin
                # view translates its status without changing the stored value.
                content=b'backup sintetico da equipe\n'
                payload,kind=multipart({'Submit':'Enviar','confirmation':'confirm'}, {'sourcefile':('equipe.txt',content)})
                team.open(urllib.request.Request(base+'team/files.php',payload,{'Content-Type':kind}),timeout=30).read()
                page=team.open(base+'team/files.php').read().decode('utf-8')
                from html import unescape
                import re
                links=re.findall(r'href="([^"]*filedownload\.php\?[^"]*)"',page)
                assert len(links)==1,links
                assert team.open(urllib.parse.urljoin(base+'team/files.php',unescape(links[0]))).read()==content
                sql="SELECT bkpnumber FROM bkptable WHERE contestnumber=1 AND bkpfilename='equipe.txt'"
                number=raw_run(['docker','exec',names['db'],'psql','-U','postgres','-d','bocadb','-Atc',sql]).decode().strip()
                assert number.isdecimal(),number
                page=client.open(base+'admin/files.php').read().decode('utf-8')
                assert 'equipe.txt' in page and '<td>Ativo</td>' in page
                team.open(base+'team/files.php?delete='+number).read()
                page=client.open(base+'admin/files.php').read().decode('utf-8')
                assert '<td>Excluído</td>' in page
                print('Backup da equipe: envio, download e exclusão OK',flush=True)
                for filename,source in [('correta.c',b'#include <stdio.h>\nint main(){puts("99");}\n'),
                                        ('errada.c',b'#include <stdio.h>\nint main(){puts("0");}\n')]:
                    payload,kind=multipart({'problem':'1','language':'1','Submit':'Send','confirmation':'confirm'},
                                           {'sourcefile':(filename,source)})
                    team.open(urllib.request.Request(base+'team/run.php',payload,{'Content-Type':kind}),timeout=30).read()
                query="SELECT coalesce(json_agg(r ORDER BY runnumber),'[]'::json) FROM (SELECT runnumber,runfilename,runstatus,runanswer FROM runtable WHERE contestnumber=1 ORDER BY runnumber) r"
                for _ in range(120):
                    rows=json.loads(raw_run(['docker','exec',names['db'],'psql','-U','postgres','-d','bocadb','-Atc',query]))
                    if len(rows)==2 and all(r['runstatus']=='judged' for r in rows): break
                    time.sleep(2)
                else: raise AssertionError('Submissões da equipe não foram julgadas: '+str(rows))
                assert [r['runfilename'] for r in rows]==['correta.c','errada.c'],rows
                answer_query="SELECT shortname FROM answertable WHERE contestnumber=1 AND answernumber="
                verdicts=[raw_run(['docker','exec',names['db'],'psql','-U','postgres','-d','bocadb','-Atc',answer_query+str(int(r['runanswer']))]).decode().strip() for r in rows]
                print('Envio HTTP da equipe: '+str(verdicts),flush=True)
                assert 'Julgada' in client.open(base+'admin/run.php').read().decode('utf-8')
                assert int(rows[0]['runanswer']) == 1 and int(rows[1]['runanswer']) == 6, rows
                # Read the native detail page while an automatic judge is in progress.
                # The temporary database is isolated, and the completed timestamp is restored.
                runnumber=int(rows[0]['runnumber'])
                def detail_sql(query):
                    return raw_run(['docker','exec',names['db'],'psql','-U','postgres','-d','bocadb','-Atc',query]).decode().strip()
                completed=detail_sql(f'SELECT autoenddate FROM runtable WHERE contestnumber=1 AND runsitenumber=1 AND runnumber={runnumber}')
                assert completed.isdecimal(),completed
                detail_sql(f'UPDATE runtable SET autoenddate=NULL WHERE contestnumber=1 AND runsitenumber=1 AND runnumber={runnumber}')
                detail=client.open(base+f'admin/runedit.php?runsitenumber=1&runnumber={runnumber}').read().decode('utf-8')
                assert 'em andamento' in detail and ' desde ' in detail
                assert 'in progress' not in detail and ' since ' not in detail
                detail_sql(f'UPDATE runtable SET autoenddate={completed} WHERE contestnumber=1 AND runsitenumber=1 AND runnumber={runnumber}')
                team_page=team.open(base+'team/run.php').read().decode('utf-8')
                assert 'correta.c' in team_page and 'errada.c' in team_page
                assert 'Sim (YES)' in team_page and 'resposta incorreta (WA)' in team_page
                report_page=client.open(base+'admin/report/stat.php',timeout=120).read().decode('utf-8')
                assert 'Sim (YES)' in report_page and 'resposta incorreta (WA)' in report_page
                icpc_page=client.open(base+'admin/report/icpc.php',timeout=30).read().decode('utf-8')
                assert 'Saída ICPC' in icpc_page and 'Fatal error' not in icpc_page
                assert '<pre>' in icpc_page and '</pre>' in icpc_page
                assert re.search(r'<pre>\s*,1,1,\d+,\d+',icpc_page), 'Resultado sintético ausente da saída ICPC'
                score_page=client.open(base+'admin/report/score.php?p=0&hor=1',timeout=120).read().decode('utf-8')
                assert 'Placar aos 1 minuto. Outros horários:' in score_page
                assert 'As of 1 minutes' not in score_page
                score_page=client.open(base+'admin/report/score.php?p=0&hor=10',timeout=120).read().decode('utf-8')
                assert 'Placar aos 10 minutos. Outros horários:' in score_page
                assert 'Placar aos 1 minuto.' not in score_page
                # Feed both native remote-score formats into the disposable web
                # container. Never write remote-score data to either real event.
                old_global=detail_sql('SELECT siteglobalscore FROM sitetable WHERE contestnumber=1 AND sitenumber=1')
                detail_sql("UPDATE sitetable SET siteglobalscore='0' WHERE contestnumber=1 AND sitenumber=1")
                remote_file='/var/www/boca/src/private/remotescores/score_site2_2_test.dat'
                groups_file='/var/www/boca/src/private/score.sep'
                groups_original=raw_run(['docker','exec',names['web'],'php','-r','echo base64_encode(file_get_contents($argv[1]));',groups_file]).decode()
                raw_run(['docker','exec',names['web'],'php','-r','file_put_contents($argv[1],"Global 1 2\\n");',groups_file])
                remote_php=r'''$p=$argv[1]; $mode=$argv[2];
                $team=['site'=>2,'user'=>4000,'username'=>'remototeste','usertype'=>'team',
                  'userfullname'=>'Equipe remota de ensaio','usershortinstitution'=>'','userflag'=>'',
                  'usersitename'=>'Sede 2','totaltime'=>0,'totalcount'=>0,'first'=>0,'problem'=>[]];
                if ($mode==='legacy') {
                  $team['totalcount']=1; $team['totaltime']=1; $team['first']=1;
                  $team['problem']=[1=>['name'=>'A','color'=>'ffffff','colorname'=>'remota',
                    'count'=>1,'solved'=>true,'time'=>1,'penalty'=>1,'judging'=>false]];
                  $duplicate=$team; $duplicate['site']=1; $duplicate['userfullname']='Equipe duplicada de ensaio';
                  $data=[$team,$duplicate];
                } else {
                  $run=['user'=>4000,'problem'=>1,'problemname'=>'A','color'=>'ffffff','colorname'=>'remota',
                    'time'=>60,'anstime'=>60,'yes'=>'t','answer'=>1];
                  $data=['site'=>2,'resp'=>['4000-2'=>$team],'n'=>1,'a'=>[$run],'aa'=>[]];
                }
                file_put_contents($p,base64_encode(serialize($data)));'''
                try:
                    for mode in ('legacy','current'):
                        raw_run(['docker','exec',names['web'],'php','-r',remote_php,remote_file,mode])
                        remote_page=client.open(base+'admin/report/score.php?p=0&hor=10',timeout=120).read().decode('utf-8')
                        if 'Equipe remota de ensaio' not in remote_page or 'Fatal error' in remote_page:
                            (kit.HERE/'reports'/f'remote-score-{mode}-debug.html').write_text(remote_page,encoding='utf-8')
                        assert 'Equipe remota de ensaio' in remote_page and 'Fatal error' not in remote_page, mode
                        assert 'Equipe duplicada de ensaio' not in remote_page, mode
                        assert remote_page.count('alt="Amarelo:"') >= 2, mode
                finally:
                    raw_run(['docker','exec',names['web'],'php','-r','unlink($argv[1]);',remote_file])
                    raw_run(['docker','exec',names['web'],'php','-r','file_put_contents($argv[1],base64_decode($argv[2]));',groups_file,groups_original])
                    detail_sql("UPDATE sitetable SET siteglobalscore='"+old_global.replace("'","''")+"' WHERE contestnumber=1 AND sitenumber=1")
                buttons=Buttons(); buttons.feed(client.open(base+'admin/run.php').read().decode('utf-8'))
                button=buttons.labels['Reexecutar julgamento automático das submissões selecionadas']
                assert button['name']=='auto' and button['value']=='Re-run autojudge for selected runs'
                payload=urllib.parse.urlencode({button['name']:button['value'],f'cbox_{runnumber}_1':'1','confirmation':'confirm'}).encode()
                result=client.open(urllib.request.Request(base+'admin/run.php',payload),timeout=30).read().decode('utf-8')
                assert 'submissão(ões) enviada(s) novamente ao julgamento automático.' in result
                # Import sites only in this disposable database. Invalid TSV must
                # fail cleanly; both supported formats must reveal the password
                # actually stored for the generated site connection account.
                for filename,content,site in [('invalido.tsv',b'cabecalho invalido\n',None),
                                              ('sedes.tsv',b'File_Version\t1\n91\tSede sintetica TSV\n',91),
                                              ('sedes.tab',b'92\tx\tSede sintetica TAB\tx\tx\tx\tx\tx\n',92)]:
                    payload,kind=multipart({'Submit':'Import','confirmation':'confirm'}, {'importfile':(filename,content)})
                    page=client.open(urllib.request.Request(base+'admin/site.php',payload,{'Content-Type':kind}),timeout=30).read().decode('utf-8')
                    assert 'Fatal error' not in page and 'Warning:' not in page,filename
                    if site is None:
                        assert 'Formato de arquivo não reconhecido' in page
                        continue
                    match=re.search(r'<tr><td>site'+str(site)+r'</td><td>([^<]+)</td></tr>',page)
                    assert match and 'ANOTE OS USUÁRIOS E AS SENHAS' in page,filename
                    stored=detail_sql(f"SELECT userpassword FROM usertable WHERE contestnumber=1 AND usersitenumber=1 AND username='site{site}'")
                    assert stored.lstrip('!')==hashlib.sha256(match.group(1).encode()).hexdigest(),filename
                    assert detail_sql(f'SELECT count(*) FROM sitetable WHERE contestnumber=1 AND sitenumber={site}')=='1'
                print('Importação de sedes: TSV inválido e credenciais de TSV/TAB OK',flush=True)
                print('Relatório apresenta resultados em português: OK',flush=True)
                print('Submissões e resultados visíveis para a equipe: OK',flush=True)
                report={'passed':True,'profile':profile,'synthetic_environment':True,
                        'authenticated_pages_checked':checked,'team_verdicts':verdicts,
                        'server_source_sha256':kit.source_hash(),'native_create_import_edit':True,
                        'cpp20_template':True,'references_four_languages':good['results'],
                        'wrong_reference_rejected':True,'changed_package_invalidates_validation':True,
                        'active_native_problem_edit_rejected':True,'disposable_preparation_database_unchanged_by_references':True,
                        'reference_sources_and_reports_backed_up_and_restore_checked':True,
                        'real_event_databases_unchanged':True,'utc':dt.datetime.now(dt.timezone.utc).isoformat()}
            assert all(kit.fingerprint(f'boca-{p}-boca-db-1')==actual_before[p] for p in ('si','tii'))
            kit.write_json(kit.HERE/'reports/contest-setup.json',report)
            kit.write_json(kit.HERE/'reports'/f'contest-setup-{profile}.json',report)
            print('PASSOU: criacao, importacao, edicao, referencias AC/WA, revisoes e bancos reais intactos.',flush=True)
        finally:
            for name in names.values():subprocess.run(['docker','rm','-f','-v',name],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
            subprocess.run(['docker','network','rm',network],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)


if __name__=='__main__':
    parser=argparse.ArgumentParser()
    parser.add_argument('--profile',choices=('si','tii'),default='si')
    main(parser.parse_args().profile)
