#!/usr/bin/env python3
"""Kit SEMINFO. Python 3.10+, standard library; never prints credentials."""
from __future__ import annotations

import argparse
import datetime as dt
import hashlib
import ipaddress
import json
import os
from pathlib import Path
import secrets
import shutil
import subprocess
import sys
import tempfile
import time
import urllib.request

HERE = Path(__file__).resolve().parent
ROOT = HERE.parent
LOCAL = HERE / 'local'
DIST = HERE / 'dist'
CHECKS = ('client_boot', 'client_persistence', 'network_isolation', 'projector', 'judge_load', 'problems_reviewed')
BASES = {'web': 'ghcr.io/joaofazolo/boca-docker/boca-web:latest',
         'jail': 'ghcr.io/joaofazolo/boca-docker/boca-jail:latest',
         'db': 'postgres:14-alpine', 'grafana': 'grafana/grafana:latest'}


def hidden_process_options():
    """Console helpers must not create Windows Terminal windows from pythonw."""
    return {'creationflags': subprocess.CREATE_NO_WINDOW} if os.name == 'nt' else {}


def run(args, *, data=None, timeout=600, capture=True):
    p = subprocess.run([str(a) for a in args], input=data, stdout=subprocess.PIPE if capture else None,
                       stderr=subprocess.PIPE if capture else None, timeout=timeout, **hidden_process_options())
    if p.returncode:
        # Error output can contain SQL, connection strings or environment values.
        detail = (p.stderr or b'').decode('utf-8', 'replace')[-2000:]
        if not detail and p.stdout:
            try:
                error = json.loads(p.stdout)
                detail = '; '.join(error.get('errors', [])) if isinstance(error, dict) else ''
            except (json.JSONDecodeError, TypeError):
                pass
        for secret in current_secrets():
            detail = detail.replace(secret, '[REDACTED]')
        raise RuntimeError(f'{args[0]} {args[1] if len(args)>1 else ""} falhou ({p.returncode}): {detail}')
    return p.stdout or b''


def current_secrets():
    if not LOCAL.exists():
        return []
    values = []
    for path in LOCAL.glob('*/credentials.json'):
        for value in json.loads(path.read_text(encoding='utf-8')).values():
            if isinstance(value, str) and len(value) >= 12:
                values.append(value)
    return values


def write_json(path, value):
    path = Path(path)
    path.parent.mkdir(parents=True, exist_ok=True)
    tmp = path.with_suffix(path.suffix + '.tmp')
    tmp.write_text(json.dumps(value, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    os.replace(tmp, path)


def private_directory(path):
    path.mkdir(parents=True, exist_ok=True)
    if os.name == 'nt':
        identity = run(['whoami']).decode().strip()
        run(['icacls', path, '/inheritance:r', '/grant:r', f'{identity}:(OI)(CI)F', 'SYSTEM:(OI)(CI)F'])
    else:
        path.chmod(0o700)


def digest(path):
    h = hashlib.sha256()
    with Path(path).open('rb') as f:
        for chunk in iter(lambda: f.read(1024 * 1024), b''):
            h.update(chunk)
    return h.hexdigest()


def config(profile):
    path = LOCAL / profile / 'lab.json'
    if not path.exists():
        raise ValueError(f'Execute primeiro: python seminfo/manage.py init {profile}')
    return json.loads(path.read_text(encoding='utf-8-sig'))


def validate(c, *, network=False, timing=False):
    if c.get('profile') not in ('si', 'tii'):
        raise ValueError('Perfil inválido')
    expected_lab, expected_wallpaper = {'si': (3, 'lab3-si.png'), 'tii': (4, 'lab4-tii.png')}[c['profile']]
    if (c.get('lab'), c.get('wallpaper')) != (expected_lab, expected_wallpaper):
        raise ValueError('Laboratório/banner não corresponde ao perfil')
    for key in ('contest_id', 'site_id', 'teams', 'penalty_minutes'):
        if type(c.get(key)) is not int or c[key] < 1:
            raise ValueError(f'{key}: inteiro positivo obrigatório')
    if c['teams'] > 10:
        raise ValueError('Limite de dez equipes')
    if c.get('languages') != ['C', 'C++20', 'Java', 'Python3']:
        raise ValueError('Linguagens do evento: C, C++20, Java, Python3')
    if c.get('wallpaper') not in ('lab3-si.png', 'lab4-tii.png'):
        raise ValueError('Wallpaper inválido')
    start = dt.datetime.fromisoformat(c['start'])
    if start.utcoffset() != dt.timedelta(hours=-3) or start.date() != dt.date(2026, 10, 29):
        raise ValueError('Início deve ser 29/10/2026 com fuso -03:00')
    if network:
        if not c.get('server_ip') or not c.get('subnet'):
            raise ValueError('Preencha server_ip e subnet com os endereços reais do laboratório')
        ip = ipaddress.IPv4Address(c.get('server_ip') or '')
        subnet = ipaddress.IPv4Network(c.get('subnet') or '', strict=True)
        if ip.is_loopback or ip.is_multicast or ip.is_unspecified or subnet.prefixlen == 0 or ip not in subnet:
            raise ValueError('IPv4 da mesa/sub-rede inválido')
        if ip in (subnet.network_address, subnet.broadcast_address):
            raise ValueError('A mesa precisa de um endereço de host')
        ips = c.get('team_ips', [])
        if c.get('admission', True) is False and c.get('shared_subnet', True) and len(ips) != c['teams']:
            raise ValueError('Rede compartilhada exige um IPv4 reservado por equipe')
        if len(set(ips)) != len(ips):
            raise ValueError('IPs de equipes duplicados')
        for value in ips:
            team = ipaddress.IPv4Address(value)
            if team not in subnet or team == ip or team in (subnet.network_address, subnet.broadcast_address):
                raise ValueError('IPv4 de equipe fora da sala ou igual ao da mesa')
        for value in c.get('control_ips',[]):
            remote=ipaddress.IPv4Address(value)
            if remote.is_unspecified or remote.is_multicast or remote.is_loopback:
                raise ValueError('IP da central inválido')
        other = LOCAL / ('tii' if c['profile']=='si' else 'si') / 'lab.json'
        if other.exists():
            paired = json.loads(other.read_text(encoding='utf-8-sig'))
            if paired.get('server_ip') == str(ip) or str(ip) in paired.get('team_ips', []):
                raise ValueError('IP da mesa conflita com o outro laboratório')
            if set(ips).intersection(paired.get('team_ips', [])) or paired.get('server_ip') in ips:
                raise ValueError('IPs de equipes conflitam com o outro laboratório')
    if timing or c.get('timing_confirmed') is True:
        duration, freeze = c.get('duration_minutes'), c.get('freeze_minutes_before_end')
        stop = c.get('stop_answering_minutes_before_end')
        if c.get('timing_confirmed') is not True:
            raise ValueError('Confirme timing_confirmed após definir os tempos')
        if type(duration) is not int or duration <= 0:
            raise ValueError('Duração obrigatória, sem valor automático')
        if type(freeze) is not int or not 0 <= freeze < duration:
            raise ValueError('O congelamento deve estar entre zero e duração menos um')
        if type(stop) is not int or not 0 <= stop < duration:
            raise ValueError('Tempo de encerramento das respostas inválido')
    return c


def config_hash(c):
    return hashlib.sha256(json.dumps(c, sort_keys=True).encode()).hexdigest()


def initialize(profile):
    folder = LOCAL / profile
    private_directory(folder)
    if not (folder / 'lab.json').exists():
        shutil.copy2(HERE / 'labs' / f'{profile}.json', folder / 'lab.json')
    creds_path = folder / 'credentials.json'
    if not creds_path.exists():
        creds = {key: secrets.token_urlsafe(30) for key in
                 ('POSTGRES_PASSWORD', 'BOCA_DB_PASSWORD', 'BOCA_PASSWORD', 'BOCA_KEY', 'GRAFANA_PASSWORD', 'SYSTEM_PASSWORD', 'SCORE_PASSWORD', 'MAINTENANCE_PASSWORD')}
        creds.update({f'TEAM_{i:02d}': secrets.token_urlsafe(15) for i in range(1, 11)})
        write_json(creds_path, creds)
    creds = json.loads(creds_path.read_text(encoding='utf-8'))
    if 'MAINTENANCE_PASSWORD' not in creds:
        creds['MAINTENANCE_PASSWORD'] = secrets.token_urlsafe(24)
        write_json(creds_path, creds)
    (folder / 'maintenance-password.txt').write_text(creds['MAINTENANCE_PASSWORD'] + '\n', encoding='utf-8')
    from admission_setup import ensure_keys
    ensure_keys(profile)
    sync_env(profile)
    print(f'Configuracao local: {folder / "lab.json"}; credenciais privadas geradas/preservadas.')


def sync_env(profile):
    folder = LOCAL / profile
    creds = json.loads((folder / 'credentials.json').read_text(encoding='utf-8'))
    env = {k: v for k, v in creds.items() if not k.startswith('TEAM_') and k != 'MAINTENANCE_PASSWORD'}
    env['SEMINFO_PROFILE'] = profile
    lock_path = DIST / 'server.lock.json'
    if lock_path.exists():
        lock = json.loads(lock_path.read_text(encoding='utf-8'))
        for kind in ('web', 'jail', 'db', 'grafana'):
            env[f'SEMINFO_{kind.upper()}_IMAGE'] = lock['images'][kind]['reference']
    (folder / '.env').write_text(''.join(f"{k}='{v}'\n" for k, v in env.items()), encoding='utf-8')


def compose(profile, *args, data=None):
    sync_env(profile)
    return run(['docker', 'compose', '--project-name', f'boca-{profile}', '--env-file', LOCAL / profile / '.env',
                '-f', HERE / 'compose.yml', *args], data=data)


def inspect(target):
    return json.loads(run(['docker', 'inspect', target]))[0]


def exists(target):
    return subprocess.run(['docker', 'inspect', target], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL,
                          **hidden_process_options()).returncode == 0


def fingerprint(container):
    sql = """SELECT tablename FROM pg_tables WHERE schemaname='public' ORDER BY tablename;"""
    tables = run(['docker', 'exec', container, 'psql', '-U', 'postgres', '-d', 'bocadb', '-Atc', sql]).decode().splitlines()
    result = {}
    for table in tables:
        if not table.replace('_', '').isalnum():
            raise ValueError('Nome de tabela inesperado')
        sql = f'''SELECT count(*)::text || ':' || coalesce(md5(string_agg(row::text,E'\\n' ORDER BY row::text)),'') FROM (SELECT to_jsonb(t) AS row FROM "{table}" t) s;'''
        result[table] = run(['docker', 'exec', container, 'psql', '-U', 'postgres', '-d', 'bocadb', '-Atc', sql]).decode().strip()
    sql = "SELECT count(*)::text || ':' || coalesce(md5(string_agg(loid::text || ':' || pageno::text || ':' || encode(data,'hex'),'' ORDER BY loid,pageno)),'') FROM pg_largeobject"
    result['largeobjects'] = run(['docker', 'exec', container, 'psql', '-U', 'postgres', '-d', 'bocadb', '-Atc', sql]).decode().strip()
    return result


def reference_files(root):
    """Only recoverable source/report JSON; exclude temporary judge passwords."""
    result = {}
    for path in Path(root).glob('*/*.json'):
        if path.parent.name.isdecimal() and 1 <= int(path.parent.name) <= 9999 and path.name in ('sources.json','validation.json'):
            if path.is_symlink() or path.parent.is_symlink():
                raise ValueError('Pasta de referências inválida')
            result[path.relative_to(root).as_posix()] = digest(path)
    return result


def check_reference_backup(folder, manifest):
    expected = manifest.get('reference_files_sha256',{})
    actual = reference_files(Path(folder)/'reference-solutions')
    if expected != actual:
        raise ValueError('Checksum das soluções de referência do backup diverge')
    return actual


def backup(profile, *, resume=True):
    folder = HERE / 'backups' / (profile + '-' + dt.datetime.now(dt.timezone.utc).strftime('%Y%m%dT%H%M%S%fZ'))
    private_directory(folder)
    db = f'boca-{profile}-boca-db-1'
    web = f'boca-{profile}-boca-web-1'
    running = []
    for name in (web, f'boca-{profile}-boca-jail-1'):
        if exists(name) and inspect(name)['State']['Running']:
            running.append(name)
    if running:
        run(['docker', 'stop', *running])
    try:
        before = fingerprint(db)
        run(['docker', 'exec', db, 'pg_dump', '-U', 'postgres', '-d', 'bocadb', '-Fc', '-b', '-f', '/tmp/seminfo.dump'])
        run(['docker', 'cp', f'{db}:/tmp/seminfo.dump', folder / 'database.dump'])
        run(['docker', 'exec', db, 'rm', '-f', '/tmp/seminfo.dump'])
        if exists(web):
            run(['docker', 'cp', f'{web}:/var/www/boca/src/private', folder / 'private'])
        # Preserve only the old stack environment needed for recovery, never print it.
        env = inspect(web)['Config']['Env'] if exists(web) else []
        write_json(folder / 'legacy-environment.json', [x for x in env if x.startswith(('BOCA_', 'POSTGRES_'))])
        if any(x.get('Name') == f'boca-{profile}_db-data' for x in inspect(db)['Mounts']):
            shutil.copy2(LOCAL / profile / 'credentials.json', folder / 'event-credentials.json')
            shutil.copy2(LOCAL / profile / 'lab.json', folder / 'lab.json')
            for name in ('admission-cert.pem','admission-key.pem','admission-control.txt','admission-score.txt','admission-audit.jsonl'):
                if (LOCAL/profile/name).exists():
                    shutil.copy2(LOCAL/profile/name,folder/name)
        references = reference_files(LOCAL/profile/'reference-solutions')
        for relative in references:
            path = folder/'reference-solutions'/relative
            path.parent.mkdir(parents=True,exist_ok=True)
            shutil.copy2(LOCAL/profile/'reference-solutions'/relative,path)
        write_json(folder / 'manifest.json', {'profile': profile, 'fingerprint': before,
                   'dump_sha256': digest(folder / 'database.dump'), 'database_image': inspect(db)['Image'],
                   'reference_files_sha256': references,
                   'original_mounts': inspect(db)['Mounts'], 'created_utc': dt.datetime.now(dt.timezone.utc).isoformat()})
    finally:
        if running and resume:
            run(['docker', 'start', *running])
    print(f'Backup: {folder}')
    return folder


def sql(container, text, database='postgres'):
    return run(['docker', 'exec', '-i', container, 'psql', '-v', 'ON_ERROR_STOP=1', '-U', 'postgres', '-d', database], data=text.encode())


def wait_database(container):
    for _ in range(60):
        p = subprocess.run(['docker', 'exec', container, 'pg_isready', '-U', 'postgres'], stdout=subprocess.DEVNULL,
                           stderr=subprocess.DEVNULL, **hidden_process_options())
        if p.returncode == 0:
            return
        time.sleep(1)
    raise RuntimeError('PostgreSQL não ficou pronto')


def restore_into(container, folder, password):
    # Fresh database only. Never --clean an existing contest.
    result = sql(container, "SELECT datname FROM pg_database WHERE datname='bocadb';")
    if b'bocadb' in result:
        raise RuntimeError('Destino já contém bocadb; restauração recusada')
    escaped = password.replace("'", "''")
    sql(container, f"CREATE ROLE bocauser LOGIN PASSWORD '{escaped}';\nCREATE DATABASE bocadb OWNER bocauser;")
    run(['docker', 'cp', folder / 'database.dump', f'{container}:/tmp/seminfo.dump'])
    run(['docker', 'exec', container, 'pg_restore', '--exit-on-error', '--no-owner', '--no-acl', '--role=bocauser', '-U', 'postgres', '-d', 'bocadb', '/tmp/seminfo.dump'])
    run(['docker', 'exec', container, 'rm', '-f', '/tmp/seminfo.dump'])
    sql(container, 'ALTER DATABASE bocadb SET lo_compat_privileges=on;')


def check_restore(folder):
    folder = Path(folder).resolve()
    m = json.loads((folder / 'manifest.json').read_text(encoding='utf-8'))
    if digest(folder / 'database.dump') != m['dump_sha256']:
        raise ValueError('Checksum do backup diverge')
    references = check_reference_backup(folder,m)
    name = 'seminfo-restore-' + secrets.token_hex(5)
    volume = name + '-data'
    run(['docker', 'volume', 'create', volume])
    try:
        run(['docker', 'run', '-d', '--name', name, '--network', 'none', '-e', 'POSTGRES_PASSWORD=' + secrets.token_urlsafe(24),
             '-v', f'{volume}:/var/lib/postgresql/data', m['database_image']])
        wait_database(name)
        restore_into(name, folder, secrets.token_urlsafe(24))
        actual = fingerprint(name)
        if actual != m['fingerprint']:
            raise RuntimeError('Restauração diverge: contagens/hashes ou large objects diferentes')
        write_json(folder / 'restore-verified.json', {'dump_sha256': m['dump_sha256'], 'fingerprint': actual,
                   'reference_files_sha256': references,
                   'verified_utc': dt.datetime.now(dt.timezone.utc).isoformat()})
        print('Restauração isolada aprovada: todas as tabelas e large objects conferem.')
    finally:
        subprocess.run(['docker', 'rm', '-f', name], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, **hidden_process_options())
        subprocess.run(['docker', 'volume', 'rm', volume], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, **hidden_process_options())


def source_hash_bytes(path):
    """Hash source text independently of Git's Windows checkout line endings.

    Binary assets remain byte-exact. Known source files, sample text inputs
    and executable scripts are normalized to match Git checkouts.
    """
    data = path.read_bytes()
    suffix = path.suffix.lower()
    text_source = suffix in {'.php', '.py', '.js', '.css', '.html', '.sh', '.ps1',
                             '.vbs', '.yml', '.yaml', '.json', '.md', '.dockerfile', '.c', '.h',
                             '.conf', '.conf-ssl', '.txt', '.info', '.in', '.out',
                             '.sample', '.config', '.sep'}
    text_source = (text_source or path.name.lower() in {'dockerfile', '.htaccess', 'version',
                   'changelog', 'copying', 'readme', 'file1'} or path.name.startswith('cron-')
                   or data.startswith(b'#!'))
    if text_source and b'\x00' not in data:
        return data.replace(b'\r\n', b'\n')
    return data


def source_hash():
    h = hashlib.sha256()
    for folder in (ROOT / 'src', ROOT / 'tools', HERE / 'server', ROOT/'doc/problemexamples/problemtemplate'):
        for p in sorted(folder.rglob('*'), key=lambda item: item.relative_to(ROOT).as_posix()):
            if p.is_file():
                h.update(p.relative_to(ROOT).as_posix().encode())
                h.update(source_hash_bytes(p))
    for name in ('manage.py','admission.py','admission_wire.py','admission_setup.py','hub.py','webcast.py','scoring.py','compose.yml','firewall-mesa.ps1','install-admission.ps1','stop-admission.ps1',
                 'organizer.py','problems.py','launch-organizer.ps1','install-organizer-shortcut.ps1','Abrir Central SEMINFO.vbs'):
        path = HERE/name
        if path.exists():
            h.update(name.encode()); h.update(source_hash_bytes(path))
    for path in sorted((HERE/'operator-ui').glob('*')):
        if path.is_file():
            h.update(path.relative_to(HERE).as_posix().encode()); h.update(source_hash_bytes(path))
    return h.hexdigest()


def client_source_hash():
    h = hashlib.sha256()
    files = [HERE/'client_config.py', HERE/'admission_wire.py', *(HERE/'client').glob('*'), *(HERE/'banners').glob('*.png')]
    for path in sorted(p for p in files if p.is_file()):
        h.update(path.relative_to(HERE).as_posix().encode())
        h.update(path.read_bytes())
    return h.hexdigest()


def build_server():
    DIST.mkdir(parents=True, exist_ok=True)
    src_hash = source_hash()
    images, bases = {}, {}
    for kind, reference in BASES.items():
        if not exists(reference):
            run(['docker', 'pull', reference], capture=False)
        info = inspect(reference)
        pinned = f'seminfo/base-{kind}:{info["Id"].split(":")[1][:16]}'
        run(['docker', 'tag', info['Id'], pinned])
        bases[kind] = {'id': info['Id'], 'digests': info.get('RepoDigests', [])}
        images[kind] = {'reference': pinned, 'id': info['Id']}
        if kind in ('web', 'jail'):
            tag = f'seminfo/boca-{kind}:2026-{src_hash[:12]}'
            run(['docker', 'build', '--pull=false', '--build-arg', 'BASE=' + pinned, '-t', tag,
                 '-f', HERE / 'server' / f'{kind}.Dockerfile', ROOT], capture=False, timeout=1800)
            images[kind] = {'reference': tag, 'id': inspect(tag)['Id']}
    write_json(DIST / 'server.lock.json', {'source_sha256': src_hash, 'bases': bases, 'images': images})
    for profile in ('si', 'tii'):
        if (LOCAL / profile / 'credentials.json').exists():
            sync_env(profile)
    print(f'Imagens prontas. Manifesto: {DIST / "server.lock.json"}')


def wait_web(profile):
    name = f'boca-{profile}-boca-web-1'
    for _ in range(60):
        state = inspect(name)['State']
        if state.get('Health', {}).get('Status') == 'healthy':
            return
        time.sleep(2)
    raise RuntimeError('BOCA não ficou saudável; consulte os logs locais')


def migrate(profile):
    db = f'boca-{profile}-boca-db-1'
    if not exists(db):
        start(profile)
        return
    if any(x.get('Name') == f'boca-{profile}_db-data' for x in inspect(db)['Mounts']):
        print('Banco já usa volume nomeado. Migração desnecessária.')
        start(profile)
        return
    folder = backup(profile, resume=False)
    try:
        check_restore(folder)
    except Exception:
        for service in ('boca-web', 'boca-jail'):
            name = f'boca-{profile}-{service}-1'
            if exists(name): run(['docker', 'start', name])
        raise
    names = [f'boca-{profile}-{service}-1' for service in ('boca-jail', 'boca-web', 'grafana', 'boca-db')]
    names = [n for n in names if exists(n)]
    run(['docker', 'stop', *names])
    # Anonymous volumes are intentionally retained as a second recovery path.
    run(['docker', 'rm', *names])
    compose(profile, 'up', '-d', 'boca-db')
    wait_database(db)
    creds = json.loads((LOCAL / profile / 'credentials.json').read_text(encoding='utf-8'))
    restore_into(db, folder, creds['BOCA_DB_PASSWORD'])
    if fingerprint(db) != json.loads((folder / 'manifest.json').read_text())['fingerprint']:
        raise RuntimeError(f'Migracao divergente. Pare aqui; backup preservado em {folder}')
    compose(profile, 'create', 'boca-web')
    if (folder / 'private').exists():
        run(['docker', 'cp', str(folder / 'private') + '/.', f'boca-{profile}-boca-web-1:/var/www/boca/src/private'])
    start(profile)
    print('Migração concluída; volumes antigos e backup mantidos.')


def restore(profile, folder):
    folder = Path(folder).resolve()
    manifest = json.loads((folder / 'manifest.json').read_text(encoding='utf-8'))
    if manifest['profile'] != profile:
        raise ValueError('Backup de outro perfil; restauração recusada')
    check_restore(folder)
    db = f'boca-{profile}-boca-db-1'
    # Refuse even to replace credentials if this destination already has data.
    if exists(db):
        if not inspect(db)['State']['Running']:
            raise ValueError('Destino tem container parado; examine seus dados antes da recuperação')
        if b'bocadb' in sql(db,"SELECT datname FROM pg_database WHERE datname='bocadb';"):
            raise ValueError('Destino já contém bocadb; use uma mesa vazia')
    references = check_reference_backup(folder,manifest)
    for relative, checksum in references.items():
        existing = LOCAL/profile/'reference-solutions'/relative
        if existing.exists() and digest(existing) != checksum:
            raise ValueError('Existem referências diferentes nesta pasta privada; preserve-as antes de restaurar')
    initialize(profile)
    if (folder / 'event-credentials.json').exists():
        shutil.copy2(folder / 'event-credentials.json', LOCAL / profile / 'credentials.json')
        shutil.copy2(folder / 'lab.json', LOCAL / profile / 'lab.json')
        for name in ('admission-cert.pem','admission-key.pem','admission-control.txt','admission-score.txt'):
            if (folder/name).exists():
                shutil.copy2(folder/name,LOCAL/profile/name)
        initialize(profile)
    for relative in references:
        target = LOCAL/profile/'reference-solutions'/relative
        target.parent.mkdir(parents=True,exist_ok=True)
        shutil.copy2(folder/'reference-solutions'/relative,target)
    compose(profile, 'up', '-d', 'boca-db')
    wait_database(db)
    creds=json.loads((LOCAL / profile / 'credentials.json').read_text(encoding='utf-8'))
    restore_into(db,folder,creds['BOCA_DB_PASSWORD'])
    if fingerprint(db) != manifest['fingerprint']:
        raise RuntimeError('Recuperação diverge; web/juiz permanecem parados')
    compose(profile,'create','boca-web')
    if (folder / 'private').exists():
        run(['docker','cp',str(folder/'private')+'/.',f'boca-{profile}-boca-web-1:/var/www/boca/src/private'])
    start(profile)
    print('Recuperação aprovada. Confira IP, relógio e placar antes de retomar.')


def start(profile):
    db = f'boca-{profile}-boca-db-1'
    if exists(db) and not any(x.get('Name') == f'boca-{profile}_db-data' for x in inspect(db)['Mounts']):
        raise RuntimeError('Instalação antiga encontrada: execute migrate antes de start')
    lock = json.loads((DIST / 'server.lock.json').read_text(encoding='utf-8'))
    if lock['source_sha256'] != source_hash():
        raise RuntimeError('Código mudou depois do build; execute build-server')
    for image in lock['images'].values():
        if inspect(image['reference'])['Id'] != image['id']:
            raise RuntimeError('Imagem local diverge do manifesto')
    compose(profile, 'up', '-d', '--no-build', '--pull', 'never')
    wait_web(profile)
    if config(profile).get('server_ip') and config(profile).get('subnet'):
        from admission_setup import start as admission_start
        admission_start(profile)
    else:
        print('Rede pendente: BOCA somente em 127.0.0.1:18000; equipes permanecem bloqueadas.')
        return
    print(f'{profile}: http://localhost:8000/boca/ (projetor: usuario placar)')
    try:
        probe_web(config(profile).get('server_ip') or '127.0.0.1')
    except ValueError as exc:
        print('AVISO: '+str(exc)+' Confira conflito de porta/proxy antes de usar o navegador.')


def probe_web(address):
    url = f'http://{address}:8000/boca/'
    try:
        opener = urllib.request.build_opener(urllib.request.ProxyHandler({}))
        with opener.open(url, timeout=10) as response:
            body = response.read(131072)
        # Use stable form fields, independent of the login page's language.
        markers = (b'BOCA Online Contest Administrator', b'name="name"', b'name="password"')
        if not all(marker in body for marker in markers):
            raise ValueError('Resposta HTTP não corresponde ao login BOCA em '+url)
    except OSError as exc:
        raise ValueError('BOCA não acessível em '+url+': '+str(exc)) from exc


def control(profile, action, extra=None):
    c = validate(config(profile))
    creds = json.loads((LOCAL / profile / 'credentials.json').read_text(encoding='utf-8'))
    payload = {'config': c, 'credentials': creds, **(extra or {})}
    if action=='scoreboard':
        release=LOCAL/profile/'score-release.json'
        if release.exists():
            record=json.loads(release.read_text())
            payload['public_final']=record.get('config_sha256')==config_hash(c) and record.get('server_source_sha256')==source_hash()
    web = f'boca-{profile}-boca-web-1'
    if exists(web) and inspect(web)['State']['Running']:
        output = run(['docker', 'exec', '-i', web, 'php', '/opt/seminfo/control.php', action], data=json.dumps(payload).encode('utf-8'))
    else:
        output = compose(profile, 'run', '--rm', '--no-deps', '-T', '--entrypoint', 'php', 'boca-web', '/opt/seminfo/control.php', action, data=json.dumps(payload).encode('utf-8'))
    try:
        return json.loads(output)
    except json.JSONDecodeError:
        raise RuntimeError('BOCA não devolveu JSON válido; consulte o log privado do container') from None


def verify(profile):
    status = control(profile, 'verify')
    if not status.get('ok'):
        raise RuntimeError('BOCA: ' + '; '.join(status.get('errors', [])))
    jail = f'boca-{profile}-boca-jail-1'
    checks = ['gcc --version', 'g++ --version', 'python3 --version', 'java -version', 'javac -version']
    versions = {}
    for command in checks:
        if exists(jail) and inspect(jail)['State']['Running']:
            output=run(['docker', 'exec', jail, 'chroot', '/home/bocajail', '/bin/sh', '-c', command+' 2>&1'])
        else:
            output=compose(profile,'run','--rm','--no-deps','-T','--entrypoint','chroot','boca-jail','/home/bocajail','/bin/sh','-c',command+' 2>&1')
        versions[command.split()[0]]=output.decode('utf-8','replace').strip()
    report = {'config_sha256': config_hash(config(profile)), 'server_source_sha256': source_hash(),
              'ok': True, 'database': status, 'compiler_versions': versions,
              'verified_utc': dt.datetime.now(dt.timezone.utc).isoformat()}
    write_json(LOCAL / profile / 'verification.json', report)
    print('Verificação automática aprovada: equipes, usuários, linguagens e cinco executáveis do juiz.')
    return report


def attest(profile, check, operator, note):
    path = LOCAL / profile / 'attestations.json'
    data = json.loads(path.read_text(encoding='utf-8')) if path.exists() else {}
    data[check] = {'operator': operator, 'note': note, 'config_sha256': config_hash(config(profile)),
                   'server_source_sha256': source_hash(), 'client_source_sha256': client_source_hash(),
                   'at_utc': dt.datetime.now(dt.timezone.utc).isoformat()}
    if check == 'problems_reviewed':
        from problems import assert_reviewable
        data[check]['problems_sha256'] = assert_reviewable(profile)
    write_json(path, data)
    print('Validação presencial registrada; não substitui a execução do teste.')


def activate(profile):
    c = validate(config(profile), network=True, timing=True)
    for service in ('boca-db','boca-web','boca-jail'):
        name=f'boca-{profile}-{service}-1'
        if not exists(name) or not inspect(name)['State']['Running'] or inspect(name)['State'].get('Health',{}).get('Status')!='healthy':
            raise ValueError('Inicie a stack e aguarde banco, web e juiz saudáveis antes de ativar')
    verify(profile)
    path = LOCAL / profile / 'attestations.json'
    records = json.loads(path.read_text(encoding='utf-8')) if path.exists() else {}
    missing = [key for key in CHECKS if records.get(key, {}).get('config_sha256') != config_hash(c)
               or records.get(key, {}).get('server_source_sha256') != source_hash()
               or records.get(key, {}).get('client_source_sha256') != client_source_hash()]
    if missing:
        raise ValueError('Ativação bloqueada. Ensaios pendentes/desatualizados: ' + ', '.join(missing))
    from problems import assert_reviewable
    if records['problems_reviewed'].get('problems_sha256') != assert_reviewable(profile):
        raise ValueError('As questões mudaram desde a revisão. Valide as referências e registre a revisão atual.')
    probe_web(c['server_ip'])
    result = control(profile, 'activate')
    if not result.get('ok'):
        raise RuntimeError(str(result))
    print('Prova ativada com os tempos confirmados. Início: ' + c['start'])


def export(profile):
    from webcast import prepare_export
    c = validate(config(profile), timing=True)
    data = control(profile, 'export')
    folder = HERE / 'reports' / profile / dt.datetime.now(dt.timezone.utc).strftime('%Y%m%dT%H%M%S%fZ')
    prepare_export(data, c, folder)
    print(f'Exportacao local e conferencia: {folder}')
    return folder


def publish_score(profile):
    folder=export(profile)
    metadata=json.loads((folder/'metadata.json').read_text())
    if not metadata['final']:
        raise ValueError('Publicação final exige prova encerrada, juiz sem pendências e paridade BOCA')
    write_json(LOCAL/profile/'score-release.json',{'config_sha256':config_hash(config(profile)),
        'server_source_sha256':source_hash(),'export':str(folder),'published_utc':dt.datetime.now(dt.timezone.utc).isoformat()})
    print('Placar final desta sala liberado para projetores. Publique todas as salas para liberar o geral final.')


def bundle():
    lock = json.loads((DIST / 'server.lock.json').read_text(encoding='utf-8'))
    references = [entry['reference'] for entry in lock['images'].values()]
    target = DIST / 'seminfo-server-images.tar'
    run(['docker', 'image', 'save', '-o', target, *references], timeout=1800)
    write_json(DIST / 'server-bundle.json', {'file': target.name, 'sha256': digest(target), 'lock_sha256': digest(DIST / 'server.lock.json')})
    from webcast import bundle_animeitor
    bundle_animeitor()
    print(f'Pacote offline: {target}')


def main():
    p = argparse.ArgumentParser(description=__doc__)
    p.add_argument('action', choices=['init', 'check', 'build-server', 'install', 'stage', 'start', 'stop', 'status', 'logs', 'backup',
                   'restore-check', 'restore', 'migrate', 'prepare', 'verify', 'attest', 'activate', 'export', 'bundle', 'client-config', 'animeitor', 'reveal', 'fetch-ubuntu', 'build-client',
                   'admission-panel','admission-export','hub-import','hub','hub-stop','desk-service','publish-score'])
    p.add_argument('profile', nargs='?', choices=['si', 'tii'])
    p.add_argument('--backup', type=Path)
    p.add_argument('--check', choices=CHECKS)
    p.add_argument('--operator')
    p.add_argument('--note')
    p.add_argument('--network', action='store_true')
    p.add_argument('--timing', action='store_true')
    p.add_argument('--allow-incomplete', action='store_true', help='Gerar imagem bloqueada, ainda sem IP do laboratorio')
    p.add_argument('--desk',type=Path,help='Descritor privado da banca para parear a central')
    args = p.parse_args()
    if args.action in ('hub','hub-import','hub-stop'):
        import hub
        if args.action == 'hub-import':
            if not args.desk:p.error('hub-import exige --desk ARQUIVO-PRIVADO')
            hub.import_desk(args.desk, manager_profile=args.profile)
        elif args.action == 'hub-stop':hub.stop()
        else:hub.start()
        return
    if args.action in ('build-server', 'bundle', 'animeitor', 'fetch-ubuntu'):
        if args.action == 'animeitor':
            from webcast import install_animeitor
            install_animeitor()
        elif args.action == 'fetch-ubuntu':
            from image_build import fetch_ubuntu
            fetch_ubuntu()
        else:
            (build_server if args.action == 'build-server' else bundle)()
        return
    if args.action == 'restore-check':
        if not args.backup:
            p.error('restore-check exige --backup CAMINHO')
        check_restore(args.backup)
        return
    if not args.profile:
        p.error('Informe si ou tii')
    profile = args.profile
    if args.action == 'init': initialize(profile)
    elif args.action == 'desk-service':
        # Foreground supervisor lets Task Scheduler restart after a crash. Wait
        # for Docker Desktop rather than silently abandoning a logon startup.
        for attempt in range(120):
            try:
                run(['docker','info'],timeout=10)
                break
            except (RuntimeError,OSError,subprocess.TimeoutExpired):
                time.sleep(5)
        else:raise RuntimeError('Docker não iniciou; tarefa agendada tentará novamente')
        from admission_setup import running
        start(profile)
        if not running(profile):raise RuntimeError('Banca não iniciou')
        while running(profile):time.sleep(5)
        raise RuntimeError('Banca encerrou; tarefa agendada tentará novamente')
    elif args.action == 'stage':
        initialize(profile)
        db=f'boca-{profile}-boca-db-1'
        if exists(db) and not any(x.get('Name')==f'boca-{profile}_db-data' for x in inspect(db)['Mounts']):
            raise ValueError('Instalação antiga encontrada; use migrate')
        compose(profile,'up','-d','boca-db')
        wait_database(db)
        compose(profile,'run','--rm','--no-deps','-T','--entrypoint','php','boca-web','/opt/seminfo/bootstrap.php')
        result=control(profile,'prepare')
        if not result.get('ok'):raise RuntimeError(str(result))
        verify(profile)
        print(f'{profile}: banco/usuarios preparados, sem publicar porta web nesta maquina.')
    elif args.action == 'install':
        initialize(profile)
        build_server()
        migrate(profile)
        result = control(profile, 'prepare')
        if not result.get('ok'): raise RuntimeError(str(result))
        if os.name=='nt' and config(profile).get('server_ip') and config(profile).get('subnet'):
            run(['powershell','-NoProfile','-ExecutionPolicy','Bypass','-File',HERE/'install-admission.ps1','-Profile',profile],capture=False)
        else:
            print('Autoinício/firewall da mesa aguardam IP/sub-rede confirmados; use install-admission.ps1 na mesa final.')
        print('Usuários preparados; contest permanece inativo.')
    elif args.action == 'check':
        validate(config(profile), network=args.network, timing=args.timing)
        print('Configuração válida para os requisitos solicitados.')
    elif args.action == 'start': start(profile)
    elif args.action == 'admission-panel':
        from admission_setup import panel
        panel(profile)
    elif args.action == 'admission-export':
        from admission_setup import descriptor
        descriptor(profile)
    elif args.action in ('stop', 'status', 'logs'):
        if args.action == 'stop':
            if os.name=='nt':
                run(['powershell','-NoProfile','-ExecutionPolicy','Bypass','-File',HERE/'stop-admission.ps1','-Profile',profile])
            from admission_setup import stop
            stop(profile)
        command = {'stop': ['stop'], 'status': ['ps'], 'logs': ['logs', '--tail=100']}[args.action]
        print(compose(profile, *command).decode('utf-8', 'replace'))
    elif args.action == 'backup': backup(profile)
    elif args.action == 'migrate': migrate(profile)
    elif args.action == 'restore':
        if not args.backup:p.error('restore exige --backup CAMINHO')
        restore(profile,args.backup)
    elif args.action == 'prepare':
        result = control(profile, 'prepare')
        if not result.get('ok'): raise RuntimeError(str(result))
        print('Contest preparado/inativo; credenciais em seminfo/local/' + profile + '/credentials.json')
    elif args.action == 'verify': verify(profile)
    elif args.action == 'activate': activate(profile)
    elif args.action == 'export': export(profile)
    elif args.action == 'publish-score':publish_score(profile)
    elif args.action == 'attest':
        if not all((args.check, args.operator, args.note)):
            p.error('attest exige --check, --operator e --note')
        attest(profile, args.check, args.operator, args.note)
    elif args.action == 'client-config':
        from client_config import generate
        generate(validate(config(profile), network=not args.allow_incomplete), DIST / 'clients' / profile, allow_incomplete=args.allow_incomplete)
    elif args.action == 'build-client':
        from image_build import build_client
        build_client(profile, allow_incomplete=args.allow_incomplete)
    elif args.action == 'reveal':
        from webcast import reveal
        reveal(export(profile))


if __name__ == '__main__':
    try:
        main()
    except (ValueError, RuntimeError, OSError, subprocess.TimeoutExpired) as exc:
        print(f'ERRO: {exc}', file=sys.stderr)
        sys.exit(1)
