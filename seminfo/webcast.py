"""Immutable teacher-side exports and a pinned, local Animeitor installation."""
import json
from pathlib import Path
import subprocess
import sys
import venv
import hashlib
from zipfile import ZipFile

from scoring import standings, public_rows

HERE = Path(__file__).resolve().parent
SEP = '\x1c'


def prepare_export(data, cfg, folder):
    from manage import write_json
    if not data.get('ok') or (data['contest_id'], data['site_id']) != (cfg['contest_id'], cfg['site_id']):
        raise ValueError('Webcast de outro contest/site')
    duration = cfg['duration_minutes']
    if data['duration_seconds'] != duration * 60:
        raise ValueError('Duracao do BOCA diverge; prepare novamente antes da prova')
    teams = data['teams']
    problems = [p['name'] for p in data['problems']]
    if not teams or problems != [chr(65+i) for i in range(len(problems))]:
        raise ValueError('Webcast exige equipes que fizeram login e problemas consecutivos A, B, C...')
    if len({t['id'] for t in teams}) != len(teams):
        raise ValueError('IDs de equipes duplicados')
    for text in [data['name'], *[v for t in teams for v in (t['id'], t['name'])]]:
        if any(ch in text for ch in ('\x1c', '\r', '\n')):
            raise ValueError('Nome contem separador de webcast')
    runs = []
    elapsed = min(max(data['elapsed_seconds'], 0), duration * 60)
    for raw in data['runs']:
        seconds = int(raw['seconds'])
        if seconds < 0 or seconds > elapsed:
            continue
        answer = int(raw['answer'])
        verdict = 'Y' if raw['yes'] == 't' else ('X' if answer in (2, 7, 8) else ('?' if answer == 0 or 'judged' not in raw['status'] else 'N'))
        if raw['problem'] not in problems:
            raise ValueError('Run aponta para problema ausente')
        runs.append({'id': int(raw['id']), 'seconds': seconds, 'team': raw['team'], 'problem': raw['problem'], 'answer': verdict})
    actual = public_rows(standings(teams, runs, cfg['penalty_minutes']))
    if actual != data['official']:
        raise ValueError('Pontuacao/desempate da animacao diverge do BOCA. Use o placar oficial; exportacao recusada.')
    folder = Path(folder)
    folder.mkdir(parents=True, exist_ok=False)
    write_json(folder / 'official.json', data)
    freeze = duration - cfg['freeze_minutes_before_end']
    lines = [data['name'], SEP.join(map(str, (duration, freeze, duration, cfg['penalty_minutes']))),
             SEP.join(map(str, (len(teams), len(problems))))]
    lines += [SEP.join((t['id'], 'Nucleo de Informatica', t['name'])) for t in teams]
    lines += [SEP.join(('1', '1')), SEP.join((str(len(problems)), 'Y'))]
    (folder / 'contest').write_text('\n'.join(lines) + '\n', encoding='utf-8')
    (folder / 'runs').write_text(''.join(SEP.join(map(str, (r['id'], r['seconds']//60, r['team'], r['problem'], r['answer']))) + '\n' for r in runs), encoding='utf-8')
    (folder / 'time').write_text(str(elapsed) + '\n', encoding='ascii')
    pending = max(data.get('pending_runs',0),sum(r.get('status') not in ('judged','deleted') for r in data['runs']))
    write_json(folder / 'metadata.json', {'teams': teams, 'parity_verified': True, 'pending_runs':pending,
        'final': elapsed >= duration*60 and pending==0 and not any(r['answer']=='?' for r in runs), 'standings': actual})
    return folder


def install_animeitor():
    from manage import run, write_json
    target = HERE / 'vendor' / 'maratona-animeitor'
    lock = HERE / 'animeitor.lock.json'
    pin = json.loads(lock.read_text(encoding='utf-8'))
    if not target.exists():
        target.parent.mkdir(parents=True, exist_ok=True)
        archive=HERE/'dist/animeitor-source.zip'
        manifest=HERE/'dist/animeitor-assets.json'
        if archive.exists() and manifest.exists():
            from manage import digest
            assets=json.loads(manifest.read_text(encoding='utf-8'))
            if assets['commit']!=pin['commit'] or digest(archive)!=assets['source_sha256']:
                raise ValueError('Pacote offline do Animeitor diverge do manifesto')
            with ZipFile(archive) as z:
                for name in z.namelist():
                    if Path(name).is_absolute() or '..' in Path(name).parts:
                        raise ValueError('Caminho inseguro no pacote Animeitor')
                z.extractall(target)
        else:
            run(['git', 'clone', 'https://github.com/maratona-linux/maratona-animeitor.git', target])
    if (target/'.git').exists():
        run(['git', '-C', target, 'checkout', '--detach', pin['commit']])
    env = HERE / 'local' / 'animeitor-venv'
    venv.EnvBuilder(with_pip=True).create(env)
    python = env / ('Scripts/python.exe' if sys.platform == 'win32' else 'bin/python')
    wheels=HERE/'dist/wheels'
    options=['--no-index','--find-links',str(wheels)] if wheels.exists() else ['--only-binary=:all:']
    run([python, '-m', 'pip', 'install', *options, 'pygame==' + pin['pygame']], capture=False)
    write_json(HERE / 'local' / 'animeitor-installed.json', pin)
    print('Animeitor local instalado e fixado; execute reveal apenas apos o encerramento.')


def bundle_animeitor():
    from manage import DIST,run,digest,write_json
    pin=json.loads((HERE/'animeitor.lock.json').read_text(encoding='utf-8'))
    target=HERE/'vendor/maratona-animeitor'
    if not (target/'.git').exists():
        raise ValueError('Para criar o pacote offline, instale Animeitor a partir do Git antes de bundle')
    archive=DIST/'animeitor-source.zip'
    run(['git','-C',target,'archive','--format=zip','--output',archive,pin['commit']])
    wheels=DIST/'wheels';wheels.mkdir(parents=True,exist_ok=True)
    run([sys.executable,'-m','pip','download','--only-binary=:all:','--no-deps','--dest',wheels,'pygame=='+pin['pygame']],capture=False)
    write_json(DIST/'animeitor-assets.json',{'commit':pin['commit'],'source_sha256':digest(archive),
               'wheels':{p.name:digest(p) for p in wheels.glob('*.whl')},'python':sys.version,'platform':sys.platform})


def reveal(folder):
    from manage import run
    folder = Path(folder).resolve()
    meta = json.loads((folder / 'metadata.json').read_text(encoding='utf-8'))
    if not meta.get('final') or not meta.get('parity_verified'):
        raise ValueError('Revelacao bloqueada: prova nao terminou, juiz pendente ou resultado nao conferido')
    env = HERE / 'local' / 'animeitor-venv'
    python = env / ('Scripts/python.exe' if sys.platform == 'win32' else 'bin/python')
    run([python, HERE / 'animeitor_runner.py', folder], capture=False, timeout=7200)
