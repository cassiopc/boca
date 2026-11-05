"""Organizer-only reference runs in a disposable BOCA, never in the event DB."""
from __future__ import annotations

import base64
import datetime as dt
import hashlib
import json
from pathlib import Path
import re
import secrets
import subprocess
import time

import manage as kit

LANGUAGES = {'c': (1, 'C'), 'cpp': (2, 'C++20'), 'java': (3, 'Java'), 'py3': (5, 'Python3')}
MAX_SOURCE = 128 * 1024


def folder(profile, number):
    if profile not in ('si', 'tii') or type(number) is not int or not 1 <= number <= 9999:
        raise ValueError('Selecione uma questão desta categoria.')
    return kit.LOCAL/profile/'reference-solutions'/str(number)


def catalog(profile):
    c = kit.validate(kit.config(profile))
    query = f"""SELECT coalesce(json_agg(p ORDER BY number),'[]'::json) FROM (
      SELECT problemnumber AS number, problemname AS name, problemfullname AS title,
      problembasefilename AS basename, probleminputfilehash AS package_hash,
      problemautojudge AS autojudge, updatetime AS updated_at FROM problemtable
      WHERE contestnumber={c['contest_id']} AND problemnumber>0 AND fake=false
      AND problemfullname !~ '(DEL)') p"""
    raw = kit.run(['docker','exec',f'boca-{profile}-boca-db-1','psql','-U','postgres',
                   '-d','bocadb','-Atc',query], timeout=20)
    values = json.loads(raw)
    for value in values:
        value['number'] = int(value['number'])
        value['revision'] = hashlib.sha256(json.dumps(value,sort_keys=True).encode()).hexdigest()
    return values


def report_path(profile, number):
    return folder(profile, number)/'validation.json'


def read_report(profile, problem):
    path = report_path(profile, problem['number'])
    report = json.loads(path.read_text(encoding='utf-8')) if path.exists() else {}
    current = (report.get('revision') == problem['revision'] and report.get('profile') == profile
               and report.get('server_source_sha256') == kit.source_hash())
    return {'current': current, 'passed': current and report.get('passed') is True,
            'complete': current and report.get('complete') is True,
            'utc': report.get('utc'), 'results': report.get('results', []) if current else [],
            'missing_languages': report.get('missing_languages',list(v[1] for v in LANGUAGES.values())) if current else list(v[1] for v in LANGUAGES.values())}


def overview(profile, values=None):
    values = catalog(profile) if values is None else values
    return [p | {'validation': read_report(profile,p)} for p in values]


def digest(values):
    return hashlib.sha256(json.dumps(values,sort_keys=True).encode()).hexdigest()


def assert_reviewable(profile):
    values = catalog(profile)
    if not values or not all(read_report(profile,p)['passed'] and read_report(profile,p)['complete'] for p in values):
        raise ValueError('Valide as soluções de referência em C, C++20, Java e Python3 de cada questão na aba Prova e questões antes de registrar a revisão.')
    return digest(values)


def parse_sources(data):
    number = data.get('problem')
    if type(number) is not int or not 1 <= number <= 9999:
        raise ValueError('Selecione uma questão importada nesta categoria.')
    revision = data.get('revision')
    if not isinstance(revision,str) or not re.fullmatch('[a-f0-9]{64}',revision):
        raise ValueError('Atualize a lista de questões antes de enviar as soluções.')
    sources = data.get('sources')
    if not isinstance(sources,list) or not 1 <= len(sources) <= 4:
        raise ValueError('Selecione de uma a quatro soluções, uma por linguagem.')
    result, extensions = [], set()
    for source in sources:
        if not isinstance(source,dict):
            raise ValueError('Solução inválida.')
        name, encoded = source.get('name'), source.get('content')
        if not isinstance(name,str) or not re.fullmatch(r'[A-Za-z][A-Za-z0-9_-]{0,79}\.(c|cpp|java|py|py3)',name):
            raise ValueError('Use nomes simples de arquivos .c, .cpp, .java, .py ou .py3.')
        extension = name.rsplit('.',1)[1]
        if extension == 'py':
            extension, name = 'py3', name[:-3]+'.py3'
        if extension in extensions:
            raise ValueError('Selecione somente uma solução por linguagem em cada ensaio.')
        if not isinstance(encoded,str) or len(encoded) > (MAX_SOURCE+2)//3*4:
            raise ValueError('Cada solução deve ter até 128 KiB.')
        try:
            raw = base64.b64decode(encoded,validate=True)
            raw.decode('utf-8-sig')
        except (ValueError,UnicodeError):
            raise ValueError('Envie código-fonte UTF-8 válido.') from None
        if not raw or len(raw) > MAX_SOURCE or b'\x00' in raw:
            raise ValueError('Arquivo de código vazio, binário ou grande demais.')
        extensions.add(extension)
        result.append({'name':name,'lang':LANGUAGES[extension][0],'language':LANGUAGES[extension][1],
                       'content':encoded,'sha256':hashlib.sha256(raw).hexdigest()})
    return number, revision, result


def validate(profile, data):
    number, revision, sources = parse_sources(data)
    from organizer import editable
    editable(profile)
    values = catalog(profile)
    problem = next((p for p in values if p['number']==number),None)
    if problem is None or revision != problem['revision']:
        raise ValueError('A questão foi alterada ou removida. Atualize a lista e envie as soluções novamente.')
    target = folder(profile,number)
    kit.private_directory(target)
    # Persist references privately for recovery. They are not part of client ISOs.
    kit.write_json(target/'sources.json',{'profile':profile,'revision':revision,'sources':sources})
    # Retire the previous success before a retry: interruptions must not reuse it.
    kit.write_json(report_path(profile,number),{'profile':profile,'revision':revision,'passed':False,
                   'complete':False,'status':'running','server_source_sha256':kit.source_hash()})
    payload = kit.control(profile,'problem-package',{'number':number})
    if payload.get('revision_data') != {k:problem[k] for k in ('number','name','title','basename','package_hash','autojudge','updated_at')}:
        raise ValueError('A questão mudou durante a leitura. Atualize a lista.')
    results = isolated_judge(profile,problem,payload['package'],sources,target)
    fresh = next((p for p in catalog(profile) if p['number']==number),None)
    if not fresh or fresh['revision'] != revision:
        raise ValueError('A questão mudou durante o ensaio. O resultado não foi homologado; execute novamente.')
    missing = [label for _,label in LANGUAGES.values() if label not in {s['language'] for s in sources}]
    report = {'profile':profile,'problem':number,'revision':revision,'server_source_sha256':kit.source_hash(),
              'package_sha256':hashlib.sha256(base64.b64decode(payload['package'])).hexdigest(),
              'utc':dt.datetime.now(dt.timezone.utc).isoformat(),'status':'done',
              'passed':bool(results) and all(r['accepted'] for r in results),'complete':not missing,
              'missing_languages':missing,'results':results,'event_database_untouched':True}
    kit.write_json(report_path(profile,number),report)
    return report


def isolated_judge(profile, problem, package, sources, target):
    lock = json.loads((kit.DIST/'server.lock.json').read_text())
    if lock['source_sha256'] != kit.source_hash():
        raise ValueError('O kit foi atualizado. Peça ao apoio técnico para reconstruir as imagens dos servidores.')
    tag = 'seminfo-reference-'+secrets.token_hex(6)
    network, names = tag+'-net', {k:tag+'-'+k for k in ('db','web','jail')}
    cfg = dict(kit.config(profile))
    cfg.update(start=(dt.datetime.now(dt.timezone.utc)-dt.timedelta(minutes=5)).isoformat(),
               duration_minutes=120,freeze_minutes_before_end=0,stop_answering_minutes_before_end=0,timing_confirmed=True)
    credentials = {k:secrets.token_hex(24) for k in ('POSTGRES_PASSWORD','BOCA_DB_PASSWORD','BOCA_PASSWORD','BOCA_KEY','SYSTEM_PASSWORD','SCORE_PASSWORD')}
    credentials.update({f'TEAM_{i:02}':secrets.token_hex(16) for i in range(1,cfg['teams']+1)})
    envfile = target/(tag+'.env')
    envfile.write_text(''.join(k+'='+v+'\n' for k,v in credentials.items())+'SEMINFO_PROFILE='+profile+'\n',encoding='utf-8')
    def control(action, extra=None):
        return json.loads(kit.run(['docker','exec','-i',names['web'],'php','/opt/seminfo/control.php',action],
               data=json.dumps({'config':cfg,'credentials':credentials,**(extra or {})}).encode(),timeout=120))
    created_network = False
    created = []
    try:
        kit.run(['docker','network','create','--internal',network]); created_network=True
        kit.run(['docker','run','-d','--name',names['db'],'--network',network,'--network-alias','boca-db',
                 '--memory','512m','--env-file',envfile,lock['images']['db']['reference']]); created.append(names['db'])
        kit.wait_database(names['db'])
        kit.run(['docker','run','-d','--name',names['web'],'--network',network,'--memory','1g','--env-file',envfile,
                 '-e','BOCA_DB_HOST=boca-db','-e','BOCA_DB_SUPER_USER=postgres','-e','SEMINFO_REFERENCE=1',
                 # Supply the generated superuser password by a private env file.
                 '--entrypoint','/bin/bash',lock['images']['web']['reference'],'-c',
                 'export BOCA_DB_SUPER_PASSWORD="$POSTGRES_PASSWORD"; exec /opt/seminfo/server-init.sh']); created.append(names['web'])
        for _ in range(90):
            status = kit.inspect(names['web'])['State']
            if not status['Running']:
                raise RuntimeError('O BOCA de ensaio não iniciou. Confira as imagens dos servidores.')
            if status.get('Health',{}).get('Status') == 'healthy':break
            time.sleep(1)
        else:raise RuntimeError('O BOCA de ensaio não ficou pronto em 90 segundos.')
        if not control('prepare').get('ok'):raise RuntimeError('Falha ao preparar o BOCA de ensaio.')
        seeded = control('reference-seed',{'problem':problem,'package':package,'sources':sources})
        if not seeded.get('ok'):raise RuntimeError('Falha ao cadastrar referências no ensaio.')
        if not control('activate').get('ok'):raise RuntimeError('Falha ao iniciar somente a prova de ensaio.')
        kit.run(['docker','run','-d','--privileged','--name',names['jail'],'--network',network,'--memory','3g',
                 '--cpus','2','--pids-limit','512','--env-file',envfile,'-e','BOCA_DB_HOST=boca-db',
                 '-e','BOCA_JAIL_HOST=boca-jail',lock['images']['jail']['reference']]); created.append(names['jail'])
        for _ in range(300):
            results = control('reference-results')
            if len(results)==len(sources) and all(r['status']=='judged' for r in results):
                return [dict(name=s['name'],language=s['language'],source_sha256=s['sha256'],
                             verdict=r['verdict'],accepted=r['accepted'],
                             detail=base64.b64decode(r.get('detail','')).decode('utf-8','replace')) for s,r in zip(sources,results)]
            time.sleep(2)
        raise RuntimeError('O juiz não concluiu as referências em dez minutos. Confira o pacote e os limites; nenhum resultado foi aprovado.')
    finally:
        envfile.unlink(missing_ok=True)
        for name in reversed(created):
            subprocess.run(['docker','rm','-f','-v',name],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL,timeout=60,**kit.hidden_process_options())
        if created_network:
            subprocess.run(['docker','network','rm',network],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL,timeout=60,**kit.hidden_process_options())
