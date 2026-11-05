"""Central local dos organizadores. Biblioteca padrão, sem serviços externos."""
from __future__ import annotations

import argparse
import datetime as dt
import hmac
import html
import json
import os
from pathlib import Path
import re
import secrets
import subprocess
import sys
import threading
import time
import webbrowser
from urllib.parse import parse_qs, urlsplit

import manage as kit
from admission import Handler, Server

PORT = 17862
PREPARATION_PORT = 17864
ORIGIN = f'http://127.0.0.1:{PORT}'
LABELS = {'si': 'Graduação / SI · Lab 3', 'tii': 'Médio/Técnico / TII · Lab 4'}
CHECKS = {
    'client_boot': ('Pendrive e linguagens', 'Inicie um USB em um computador real da sala, confira o fundo e execute C, C++20, Python 3 e Java.'),
    'client_persistence': ('Trabalhos preservados', 'Salve um arquivo no USB, desligue o computador e confira o arquivo após iniciar novamente.'),
    'network_isolation': ('Rede e autorização', 'Teste pendente, aprovação, revogação, internet bloqueada, outra equipe, outro BOCA e IPv6 externo bloqueados.'),
    'projector': ('Projetores e placares', 'Confira o placar da própria categoria, freeze, revelação, empates e igualdade com o resultado oficial do BOCA. A outra banca pode estar indisponível ou em outra fase.'),
    'judge_load': ('Juiz e dez equipes', 'Teste as quatro linguagens, acerto, resposta errada, erro de compilação e tempo excedido; simule dez equipes e observe a fila.'),
    'problems_reviewed': ('Questões revisadas', 'Confira os pacotes da Cristiane destinados a esta categoria, limites, respostas e tempos confirmados. Médio/Técnico e Graduação têm questões diferentes; problemas reais não entram nas imagens das equipes.'),
}
ACTIONS = {'start', 'stop', 'prepare', 'verify', 'backup', 'restore-check', 'restore',
           'activate', 'publish-score', 'install-autostart', 'initialize', 'attest', 'pair', 'pair-local', 'open-hub',
           'start-preparation', 'stop-preparation', 'validate-solutions'}
PREPARATION_ACTIONS = {'initialize','start-preparation','stop-preparation','prepare','verify','validate-solutions','backup','restore-check'}
CONFIRM = {'stop': 'PARAR', 'prepare': 'PREPARAR', 'activate': 'ATIVAR',
           'publish-score': 'PUBLICAR', 'restore': 'RESTAURAR', 'install-autostart': 'INSTALAR'}


def read_json(path, default=None):
    path = Path(path)
    return json.loads(path.read_text(encoding='utf-8-sig')) if path.exists() else default


def safe_error(exc):
    text = str(exc)
    values = kit.current_secrets()
    for pattern in ('*/admission-control.txt', '*/admission-score.txt', '*/admission-panel.txt', 'hub/panel.txt', 'organizer/panel.txt'):
        for path in kit.LOCAL.glob(pattern):
            values.append(path.read_text(encoding='utf-8').strip())
    for value in values:
        if len(value) >= 12:
            text = text.replace(value, '[credencial omitida]')
    text = re.sub(r'(token=)[^\s&<>"\']+', r'\1[omitido]', text)
    if isinstance(exc, subprocess.TimeoutExpired):
        return 'O serviço demorou a responder. Confira o Docker Desktop e atualize o diagnóstico antes de tentar novamente.'
    return text[-1600:]


def database_status(profile):
    """Read the database even if web is stopped; never infer empty from a failure."""
    c = kit.validate(kit.config(profile))
    name = f'boca-{profile}-boca-db-1'
    try:
        info = kit.inspect(name)
    except RuntimeError:
        # Missing container is insufficient: a named volume may still have data.
        volumes = kit.run(['docker', 'volume', 'ls', '--format', '{{.Name}}'], timeout=15).decode().splitlines()
        if f'boca-{profile}_db-data' in volumes:
            raise ValueError('O banco está parado ou sem container. Inicie a mesa para conferir os dados antes de editar.')
        return {'prepared': False, 'active': False, 'runs': 0, 'problems': 0}
    if not info['State']['Running']:
        raise ValueError('O banco está parado. Inicie a mesa antes de editar sua configuração.')
    base = ['docker', 'exec', '-i', name, 'psql', '-U', 'postgres', '-At', '-v', 'ON_ERROR_STOP=1']
    exists = kit.run(base + ['-d', 'postgres', '-c', "SELECT count(*) FROM pg_database WHERE datname='bocadb'"], timeout=15).strip()
    if exists == b'0':
        return {'prepared': False, 'active': False, 'runs': 0, 'problems': 0}
    n, s = c['contest_id'], c['site_id']  # validated integers, never browser SQL
    query = f"""SELECT json_build_object('prepared', EXISTS(SELECT 1 FROM contesttable WHERE contestnumber={n}),
      'active', COALESCE((SELECT contestactive FROM contesttable WHERE contestnumber={n}),false),
      'runs', (SELECT count(*) FROM runtable WHERE contestnumber={n}),
      'problems', (SELECT count(*) FROM problemtable WHERE contestnumber={n} AND problemnumber>0 AND problemfullname !~ '(DEL)'),
      'teams', (SELECT count(*) FROM usertable WHERE contestnumber={n} AND usersitenumber={s} AND usertype='team' AND userenabled))"""
    return json.loads(kit.run(base + ['-d', 'bocadb', '-c', query], timeout=15))


def current_web_image(profile):
    """Only expose authoring links when the running image matches this kit."""
    lock = read_json(kit.DIST/'server.lock.json', {})
    if lock.get('source_sha256') != kit.source_hash():
        return False
    expected = lock.get('images', {}).get('web', {}).get('id')
    name = f'boca-{profile}-boca-web-1'
    return bool(expected and kit.exists(name) and kit.inspect(name).get('Image') == expected)


def editable(profile):
    from admission_setup import running
    if running(profile):
        raise ValueError('Pare a banca pelo botão Parar mesa antes de editar rede ou horários. As equipes perderão o acesso durante a parada.')
    status = database_status(profile)
    if status['active'] or status['runs']:
        raise ValueError('Configuração bloqueada: esta prova está ativa ou já recebeu submissões. Solicite apoio técnico; nenhum dado foi alterado.')


def backup_path(name, profile):
    if not isinstance(name, str) or not re.fullmatch(r'[a-zA-Z0-9-]{1,90}', name):
        raise ValueError('Selecione um backup listado nesta central.')
    root = (kit.HERE/'backups').resolve()
    path = (root/name).resolve()
    if path.parent != root or not path.is_dir() or path.is_symlink():
        raise ValueError('Backup fora da pasta autorizada.')
    if read_json(path/'manifest.json', {}).get('profile') != profile:
        raise ValueError('O backup pertence a outro laboratório.')
    return path


class Organizer:
    def __init__(self, preparation_only=False):
        self.preparation_only = preparation_only
        self.folder = kit.LOCAL/('organizer-preparation' if preparation_only else 'organizer')
        kit.private_directory(self.folder)
        self.lock = threading.RLock()
        self.job = read_json(self.folder/'last-job.json')
        if self.job and self.job['status'] == 'running':
            self.job.update(status='failed', message='A central foi encerrada durante a tarefa anterior. O resultado precisa ser conferido no diagnóstico; não repita uma ativação ou restauração sem verificar.', recovery='Confira os serviços e chame o apoio técnico se houver dúvida.')
            kit.write_json(self.folder/'last-job.json', self.job)
        self.cache = {}
        self.wake = threading.Event()

    def profile(self):
        value = read_json(self.folder/'settings.json', {}).get('profile', 'si')
        return value if value in LABELS else 'si'

    def idle(self):
        if self.job and self.job['status'] == 'running':
            raise ValueError('Há uma tarefa em andamento. Aguarde sua conclusão antes de iniciar outra.')

    def select(self, profile):
        if profile not in LABELS:
            raise ValueError('Escolha o laboratório desta mesa.')
        with self.lock:
            self.idle()
            kit.write_json(self.folder/'settings.json', {'profile': profile})
            self.wake.set()

    def save_config(self, data, *, timing_only=False):
        with self.lock:
            self.idle()
            profile = self.profile()
            if data.get('profile',profile) != profile:
                raise ValueError('A categoria selecionada mudou. Recarregue o formulário.')
            editable(profile)
            original = kit.config(profile)
            c = dict(original)
            for key in (() if timing_only else ('server_ip', 'subnet')):
                value = data.get(key, '')
                if not isinstance(value, str):
                    raise ValueError('Endereço de rede inválido.')
                c[key] = value.strip() or None
            if not timing_only:
                controls = data.get('control_ips', '')
                if not isinstance(controls, str):
                    raise ValueError('IPs das centrais inválidos.')
                c['control_ips'] = [s.strip() for s in controls.replace('\n', ',').split(',') if s.strip()]
            update_timing = timing_only or 'start' in data
            for key in (('duration_minutes', 'freeze_minutes_before_end') if update_timing else ()):
                value = str(data.get(key, '')).strip()
                if value and not re.fullmatch(r'\d{1,4}', value):
                    raise ValueError('Duração e congelamento devem ser informados em minutos inteiros.')
                c[key] = int(value) if value else None
            if update_timing:
                start = data.get('start', '')
                if not isinstance(start, str) or not re.fullmatch(r'2026-10-29T\d\d:\d\d', start):
                    raise ValueError('Escolha o horário em 29/10/2026.')
                c['start'] = start + ':00-03:00'
                c['timing_confirmed'] = data.get('timing_confirmed') is True
                duration, freeze = c['duration_minutes'], c['freeze_minutes_before_end']
                if duration is not None and duration < 1:
                    raise ValueError('A duração deve ser maior que zero.')
                if duration is not None and freeze is not None and freeze >= duration:
                    raise ValueError('O congelamento deve ser menor que a duração; zero significa sem congelamento.')
            if bool(c['server_ip']) != bool(c['subnet']):
                raise ValueError('Informe o IP e a sub-rede juntos, ou deixe ambos pendentes.')
            kit.validate(c, network=bool(c['server_ip']))
            kit.write_json(kit.LOCAL/profile/'lab.json', c)
            try:
                if (kit.LOCAL/profile/'credentials.json').exists():
                    kit.sync_env(profile)
            except Exception:
                kit.write_json(kit.LOCAL/profile/'lab.json', original)
                raise
            self.wake.set()
            return {'message': 'Configuração salva. Use Preparar prova para aplicar os horários no BOCA. Validações anteriores precisam ser conferidas novamente.'}

    def state(self):
        with self.lock:
            profile = self.profile()
            c = read_json(kit.LOCAL/profile/'lab.json', read_json(kit.HERE/'labs'/f'{profile}.json'))
            records = read_json(kit.LOCAL/profile/'attestations.json', {})
            src, cli, cfg = kit.source_hash(), kit.client_source_hash(), kit.config_hash(c)
            from problems import digest
            problem_values = self.cache.get(profile, {}).get('problems', [])
            checklist = [{'id': key, 'label': value[0], 'instructions': value[1],
                          'current': all(records.get(key, {}).get(k) == v for k, v in
                              [('config_sha256', cfg), ('server_source_sha256', src), ('client_source_sha256', cli)]),
                          'operator': records.get(key, {}).get('operator', ''), 'at': records.get(key, {}).get('at_utc', '')}
                         for key, value in CHECKS.items()]
            for check in checklist:
                if check['id']=='problems_reviewed':
                    plain = [{k:v for k,v in p.items() if k!='validation'} for p in problem_values]
                    check['current'] = check['current'] and bool(plain) and records.get('problems_reviewed',{}).get('problems_sha256') == digest(plain) and all(p['validation']['passed'] and p['validation']['complete'] for p in problem_values)
            backups = []
            for path in sorted((kit.HERE/'backups').glob('*'), reverse=True):
                try:
                    manifest = read_json(path/'manifest.json', {})
                    if manifest.get('profile') == profile:
                        checked = read_json(path/'restore-verified.json', {})
                        backups.append({'id': path.name, 'created': manifest['created_utc'],
                                        'verified': checked.get('dump_sha256') == manifest['dump_sha256'] and checked.get('reference_files_sha256',{}) == manifest.get('reference_files_sha256',{})})
                except (OSError, ValueError, KeyError):
                    continue
            from hub import normalize_catalog
            catalog = normalize_catalog(read_json(kit.LOCAL/'hub/desks.json', {'desks': {}}), profile)
            verification = read_json(kit.LOCAL/profile/'verification.json', {})
            return {'profile': profile, 'label': LABELS[profile], 'config': c,
                    'preparation_only':self.preparation_only,
                    'runtime': self.cache.get(profile, {'checking': True}),
                    'verification_current': verification.get('ok') is True and verification.get('config_sha256') == cfg and verification.get('server_source_sha256') == src,
                    'checklist': checklist, 'backups': backups[:40], 'job': self.job,
                    'desks': [{'id': k, 'label': d['label'], 'can_approve': bool(d.get('control_token'))} for k, d in catalog['desks'].items()]}

    def refresh(self):
        while True:
            profile = self.profile()
            result = {'checked_at': dt.datetime.now(dt.timezone.utc).isoformat()}
            try:
                # Read only. No compose up or downloads in the status monitor.
                output = kit.run(['docker', 'ps', '-a', '--format', '{{json .}}'], timeout=15)
                containers = [json.loads(line) for line in output.decode().splitlines()]
                result['docker'] = True
                result['services'] = {k: next((v['Status'] for v in containers if v['Names'] == f'boca-{profile}-{k}-1'), 'Não instalado')
                                      for k in ('boca-db', 'boca-web', 'boca-jail')}
                from admission_setup import running
                result['admission'] = running(profile)
                if (kit.LOCAL/profile/'lab.json').exists():
                    result['database'] = database_status(profile)
                    if result['database']['prepared']:
                        from problems import overview
                        result['problems'] = overview(profile)
                    result['preparation_ready'] = (result['services']['boca-web'].startswith('Up') and '(healthy)' in result['services']['boca-web']
                        and result['database']['prepared'] and not result['database']['active']
                        and not result['database']['runs'] and not result['admission'] and current_web_image(profile))
            except (OSError, ValueError, RuntimeError, subprocess.TimeoutExpired) as exc:
                result['error'] = safe_error(exc)
            with self.lock:
                self.cache[profile] = result
            self.wake.wait(20)
            self.wake.clear()

    def submit(self, action, data):
        if action not in ACTIONS:
            raise ValueError('Tarefa desconhecida.')
        if self.preparation_only and action not in PREPARATION_ACTIONS:
            raise ValueError('Esta janela prepara provas. Use o atalho Central SEMINFO para operar a rede, aprovar equipes ou ativar a maratona.')
        with self.lock:
            self.idle()
            profile = self.profile()
            if data.get('profile',profile) != profile:
                raise ValueError('A categoria selecionada mudou. Atualize a página antes de continuar.')
            expected = f'{CONFIRM[action]} LAB {3 if profile == "si" else 4}' if action in CONFIRM else None
            if expected and data.get('confirmation') != expected:
                raise ValueError(f'Para confirmar esta ação, digite {expected}.')
            # Validate bounded inputs before handing work to a background thread.
            if action == 'attest':
                if data.get('check') not in CHECKS or data.get('performed') is not True:
                    raise ValueError('Selecione um ensaio que você realmente executou.')
                if not isinstance(data.get('operator'), str) or not 3 <= len(data['operator'].strip()) <= 120:
                    raise ValueError('Informe o nome do responsável pelo teste.')
                if not isinstance(data.get('note'), str) or not 12 <= len(data['note'].strip()) <= 1500:
                    raise ValueError('Descreva o computador usado e o resultado observado (mínimo de 12 caracteres).')
            if action == 'pair':
                descriptor = data.get('descriptor')
                if not isinstance(descriptor, str) or len(descriptor) > 16000:
                    raise ValueError('Arquivo de pareamento inválido.')
                # Parse now; credentials are never retained in the public job object.
                parsed = json.loads(descriptor)
                if not isinstance(parsed, dict):
                    raise ValueError('O arquivo precisa ser um descritor de banca.')
            if action in ('restore', 'restore-check'):
                backup_path(data.get('backup'), profile)
            if action == 'validate-solutions':
                from problems import parse_sources
                parse_sources(data)
            self.job = {'id': secrets.token_hex(8), 'action': action, 'profile': profile,
                        'status': 'running', 'message': 'Executando. Aguarde; não feche o Docker Desktop.',
                        'started': dt.datetime.now(dt.timezone.utc).isoformat()}
            kit.write_json(self.folder/'last-job.json', self.job)
            threading.Thread(target=self.work, args=(action, profile, data), daemon=True).start()
            return {'message': 'Tarefa iniciada.'}

    def command(self, *args):
        # Fixed executable/arguments only; never invoke a command from the browser.
        return kit.run([sys.executable, kit.HERE/'manage.py', *args], timeout=2400)

    def work(self, action, profile, data):
        try:
            message = self.execute(action, profile, data)
            update = {'status': 'done', 'message': message or 'Tarefa concluída.'}
        except Exception as exc:
            update = {'status': 'failed', 'message': safe_error(exc),
                      'recovery': 'Nenhuma etapa seguinte foi iniciada. Confira o diagnóstico e tente novamente; se persistir, chame o apoio técnico.'}
        with self.lock:
            self.job.update(update, finished=dt.datetime.now(dt.timezone.utc).isoformat())
            kit.write_json(self.folder/'last-job.json', self.job)
            # Keep a private trace without credentials or raw uploaded descriptors.
            with (self.folder/'operations.jsonl').open('a', encoding='utf-8') as log:
                log.write(json.dumps(self.job, ensure_ascii=False)+'\n')
            self.wake.set()

    def execute(self, action, profile, data):
        if self.preparation_only and action not in PREPARATION_ACTIONS:
            raise ValueError('Ação indisponível no modo de preparação.')
        if action in ('start', 'install-autostart', 'activate'):
            kit.validate(kit.config(profile), network=True, timing=action == 'activate')
        if action in ('start', 'start-preparation', 'install-autostart', 'restore'):
            other = 'tii' if profile == 'si' else 'si'
            if kit.exists(f'boca-{other}-boca-web-1') and kit.inspect(f'boca-{other}-boca-web-1')['State']['Running']:
                raise ValueError('A outra sala está em execução neste computador. Cada BOCA deve funcionar na sua própria mesa.')
        if action == 'start-preparation':
            editable(profile)
            lock = read_json(kit.DIST/'server.lock.json',{})
            if lock.get('source_sha256') != kit.source_hash():
                raise ValueError('As imagens precisam ser atualizadas pelo apoio técnico antes de preparar a prova.')
            kit.compose(profile,'up','-d','--no-build','--pull','never','boca-db','boca-web')
            kit.wait_web(profile)
            return 'BOCA de preparação aberto somente nesta mesa. Em Prova e questões, prepare a prova e entre como admin para criar, importar ou editar questões. As equipes continuam sem acesso.'
        if action == 'stop-preparation':
            editable(profile)
            kit.compose(profile,'stop','boca-web','boca-jail')
            return 'BOCA de preparação fechado. O banco e as questões foram preservados; você pode selecionar outra categoria.'
        if action == 'validate-solutions':
            from problems import validate
            report = validate(profile,data)
            if not report['passed']:
                verdicts = ', '.join(r['language']+': '+r['verdict'] for r in report['results'])
                raise ValueError('Referências reprovadas: '+verdicts+'. Confira o pacote, os limites e as soluções; veja os resultados na lista de questões.')
            return ('Referências aceitas pelo juiz isolado. '+ ('As quatro linguagens foram conferidas.' if report['complete'] else 'Ainda faltam: '+', '.join(report['missing_languages'])+'.'))
        if action == 'initialize':
            # The organizer must not accidentally replace the identity pinned in USBs.
            folder = kit.LOCAL/profile
            required = ('admission-cert.pem', 'admission-key.pem', 'admission-control.txt', 'admission-score.txt', 'credentials.json', 'lab.json')
            if not all((folder/name).exists() for name in required):
                raise ValueError('Peça ao apoio técnico para trazer o kit e a pasta privada desta banca. A identidade dos pendrives precisa ser preservada.')
            self.command('init', profile)
            return 'Pasta privada conferida. A identidade e as senhas existentes foram preservadas.'
        if action == 'install-autostart':
            status = database_status(profile)
            if status['active'] or status['runs']:
                raise ValueError('Instalação bloqueada durante uma prova ativa ou com submissões. Solicite apoio técnico para manutenção.')
            if os.name != 'nt':
                raise ValueError('A instalação do início automático é destinada ao Windows da mesa.')
            kit.run(['powershell', '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', kit.HERE/'install-admission.ps1', '-Profile', profile], timeout=300)
            return 'Início automático e firewall instalados. A banca iniciará no logon deste organizador; aguarde o diagnóstico confirmar que está funcionando.'
        if action == 'attest':
            kit.attest(profile, data['check'], data['operator'].strip(), data['note'].strip())
            return 'Ensaio registrado com responsável, data e versão desta configuração.'
        if action == 'pair':
            import hub
            path = self.folder/'pairing-import.json'
            try:
                kit.write_json(path, json.loads(data['descriptor']))
                hub.import_desk(path, manager_profile=profile)
            finally:
                path.unlink(missing_ok=True)
            hub.stop()
            return 'Placar adicionado. A outra categoria é somente consulta; as aprovações continuam com a banca responsável.'
        if action == 'pair-local':
            from admission_setup import descriptor
            import hub
            for name in ('admission-cert.pem', 'admission-key.pem', 'admission-control.txt', 'admission-score.txt'):
                if not (kit.LOCAL/profile/name).exists():
                    raise ValueError('Identidade privada incompleta. Solicite o kit correspondente aos pendrives.')
            hub.import_desk(descriptor(profile), manager_profile=profile)
            hub.stop()
            return 'Banca gerenciadora da própria categoria adicionada. O placar da outra categoria é opcional.'
        if action == 'open-hub':
            # Open in the browser that requested the action, not an elevated worker.
            import hub
            path=kit.LOCAL/'hub/desks.json'
            if not path.exists():
                raise ValueError('Em Preparação da sala, clique em Adicionar esta sala à central antes de abrir os placares.')
            catalog=read_json(path)
            normalized=hub.normalize_catalog(catalog, profile)
            if catalog!=normalized:
                hub.stop()
                kit.write_json(path,normalized)
            hub.start(open_browser=False)
            return 'Central de salas pronta. Use o botão Abrir central de salas.'
        if action in ('restore', 'restore-check'):
            path = backup_path(data['backup'], profile)
            if action == 'restore':
                # A used or even stopped database is not an empty recovery target.
                status = database_status(profile)
                if status['prepared'] or status['runs'] or status['active']:
                    raise ValueError('Esta mesa já contém uma prova. A restauração só é permitida em destino vazio; use outra instalação com apoio técnico.')
            self.command(action, profile, '--backup', path)
            return 'Restauração isolada conferida: tabelas e arquivos do banco coincidem.' if action == 'restore-check' else 'Backup restaurado em destino vazio. Confira rede, relógio e placar antes de retomar.'
        if action == 'backup':
            # One click includes the actual isolated restore test.
            folder = kit.backup(profile)
            kit.check_restore(folder)
            return f'Backup criado e restauração isolada aprovada: {folder.name}. Guarde também uma cópia em outra mídia com acesso restrito.'
        if action == 'prepare':
            status = database_status(profile)
            if status['active'] or status['runs']:
                raise ValueError('A prova está ativa ou recebeu submissões. Preparação bloqueada para preservar os dados.')
        self.command(action, profile)
        return {'start': 'Mesa iniciada. Confira o diagnóstico antes de liberar as equipes.',
                'stop': 'Mesa parada. O acesso das equipes foi interrompido.',
                'prepare': 'Prova preparada com a configuração atual; permanece inativa.',
                'verify': 'Verificação automática aprovada: contas, linguagens e compiladores.',
                'activate': 'Prova ativada no BOCA para o início confirmado.',
                'publish-score': 'Resultado final conferido e publicado somente para esta categoria. A outra banca publica de forma independente.'}.get(action)


class Panel(Handler):
    def valid_host(self):
        return self.headers.get('Host') == f'127.0.0.1:{self.server.server_port}'

    def authorized(self):
        return any(hmac.compare_digest(c.strip(), self.session_cookie()+'='+self.server.token)
                   for c in self.headers.get('Cookie', '').split(';'))

    def session_cookie(self):
        return 'seminfo_organizer_'+str(self.server.server_port)

    def reply(self, status, data, content_type='application/json; charset=utf-8', **kwargs):
        payload = json.dumps(data, ensure_ascii=False).encode() if isinstance(data, dict) else data if isinstance(data, bytes) else data.encode()
        self.send_response(status)
        self.send_header('Content-Type', content_type)
        self.send_header('Content-Length', str(len(payload)))
        self.send_header('Cache-Control', 'no-store')
        self.send_header('X-Content-Type-Options', 'nosniff')
        self.send_header('Referrer-Policy', 'no-referrer')
        self.send_header('Content-Security-Policy', "default-src 'none'; script-src 'self'; style-src 'self'; connect-src 'self'; img-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'")
        self.end_headers()
        self.wfile.write(payload)

    def redirect(self, url, *, cookie=False):
        self.send_response(303)
        self.send_header('Location', url)
        self.send_header('Cache-Control', 'no-store')
        self.send_header('Referrer-Policy', 'no-referrer')
        if cookie:
            self.send_header('Set-Cookie', f'{self.session_cookie()}={self.server.token}; HttpOnly; SameSite=Strict; Path=/')
        self.end_headers()

    def do_GET(self):
        parsed = None
        try:
            if not self.valid_host():
                self.reply(403, {'error': 'Endereço local inválido.'}); return
            parsed = urlsplit(self.path)
            token = parse_qs(parsed.query).get('token', [''])[0]
            if parsed.path == '/login' and hmac.compare_digest(token, self.server.token):
                self.redirect('/', cookie=True); return
            if not self.authorized():
                self.reply(403, 'Abra o atalho “Central SEMINFO” nesta mesa para entrar.', 'text/plain; charset=utf-8'); return
            app = self.server.app
            if parsed.path in ('/', '/app.js', '/style.css'):
                name = {'/': 'index.html', '/app.js': 'app.js', '/style.css': 'style.css'}[parsed.path]
                kind = {'index.html': 'text/html', 'app.js': 'text/javascript', 'style.css': 'text/css'}[name]
                self.reply(200, (kit.HERE/'operator-ui'/name).read_bytes(), kind+'; charset=utf-8')
            elif parsed.path == '/api/state':
                self.reply(200, app.state() | {'csrf': self.server.token})
            elif parsed.path.startswith('/open/'):
                dest = parsed.path.removeprefix('/open/')
                if app.preparation_only and dest not in ('boca','problems','problem-builder'):
                    raise ValueError('Use a Central SEMINFO completa para aprovar equipes e operar os placares.')
                if dest == 'boca':
                    self.redirect('http://127.0.0.1:18000/boca/')
                elif dest in ('problems','problem-builder'):
                    with app.lock:
                        app.idle()
                        profile=app.profile()
                        editable(profile)
                        if not current_web_image(profile):
                            raise ValueError('As imagens do BOCA estão desatualizadas. Solicite a reconstrução e reinicie o BOCA de preparação.')
                        name=f'boca-{profile}-boca-web-1'
                        if not kit.exists(name) or kit.inspect(name)['State'].get('Health',{}).get('Status')!='healthy':
                            raise ValueError('Abra o BOCA de preparação pela aba Prova e questões antes de continuar.')
                        destination='problems' if dest=='problems' else 'builder'
                        contest=kit.config(profile)['contest_id']
                        self.redirect(f'http://127.0.0.1:18000/boca/index.php?contest={contest}&destination={destination}')
                elif dest == 'admission':
                    from admission_setup import running
                    if not running(app.profile()):
                        raise ValueError('A banca ainda não está funcionando. Inicie a mesa e confira o diagnóstico.')
                    self.redirect((kit.LOCAL/app.profile()/'admission-panel.txt').read_text().strip())
                elif dest == 'hub':
                    path = kit.LOCAL/'hub/panel.txt'
                    if not path.exists():
                        raise ValueError('Primeiro clique em Preparar central de salas.')
                    self.redirect(path.read_text().strip())
                elif dest == 'general':
                    self.reply(410, 'O ranking único foi retirado. Use o placar da Graduação ou do Médio/Técnico.', 'text/plain; charset=utf-8')
                elif dest in ('si', 'tii'):
                    self.redirect('http://127.0.0.1:17861/projector/'+dest)
                else:
                    self.reply(404, {'error': 'Página não encontrada.'})
            else:
                self.reply(404, {'error': 'Página não encontrada.'})
        except (OSError, ValueError, RuntimeError, KeyError) as exc:
            if parsed is not None and parsed.path in ('/open/problems','/open/problem-builder'):
                self.reply(400,'<!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Preparar BOCA</title><link rel="stylesheet" href="/style.css"><main><article class="card"><h1>Prepare o BOCA desta categoria</h1><p>'+html.escape(safe_error(exc))+'</p><p>Na aba Prova e questões, use Abrir BOCA de preparação e aguarde a confirmação. Depois entre como admin para criar ou importar questões.</p><a class="button" href="/?tab=contest">Voltar à configuração da prova</a></article></main></html>','text/html; charset=utf-8')
            else:
                self.reply(400, safe_error(exc)+'\nVolte à Central SEMINFO para conferir o diagnóstico.', 'text/plain; charset=utf-8')

    def do_POST(self):
        try:
            # Consume only the bounded body before an early rejection. Closing
            # with unread request bytes can reset the TCP connection on Windows
            # and discard the HTTP error. Authorization still precedes parsing.
            payload = self.body(maximum=800000 if self.path=='/api/job' else 32000)
            if not self.valid_host() or not self.authorized():
                self.reply(403, {'error': 'Sessão encerrada. Abra novamente o atalho Central SEMINFO.'}); return
            if self.headers.get('Origin') != f'http://127.0.0.1:{self.server.server_port}' or self.headers.get('Content-Type') != 'application/json':
                self.reply(403, {'error': 'Origem inválida.'}); return
            if not hmac.compare_digest(self.headers.get('X-SEMINFO-CSRF', ''), self.server.token):
                self.reply(403, {'error': 'Atualize a página antes de continuar.'}); return
            data = json.loads(payload)
            if not isinstance(data, dict):
                raise ValueError('Formulário inválido.')
            app = self.server.app
            if self.path == '/api/select':
                app.select(data.get('profile')); result = {'message': 'Laboratório selecionado.'}
            elif self.path == '/api/config':
                result = app.save_config(data)
            elif self.path == '/api/timing':
                result = app.save_config(data,timing_only=True)
            elif self.path == '/api/job':
                result = app.submit(data.get('action'), data)
            elif self.path == '/api/refresh':
                app.wake.set(); result = {'message': 'Conferindo os serviços. O diagnóstico será atualizado em instantes.'}
            elif self.path == '/api/credentials':
                if data.get('confirmation') != 'MOSTRAR SENHAS':
                    raise ValueError('Confirme que está em ambiente reservado, sem o projetor exibindo esta tela.')
                c = read_json(kit.LOCAL/app.profile()/'credentials.json', {})
                if not c:
                    raise ValueError('Pasta privada ausente. Solicite o kit à equipe técnica.')
                result = {'accounts': [{'user': user, 'password': c[key]} for user, key in
                    [('admin', 'BOCA_PASSWORD'), ('placar', 'SCORE_PASSWORD')]+
                    [(f'{app.profile()}{i:02}', f'TEAM_{i:02}') for i in range(1, 11)]]}
            elif self.path == '/api/descriptor':
                if app.preparation_only:
                    raise ValueError('Use a Central SEMINFO completa para parear as bancas.')
                if data.get('confirmation') != 'EXPORTAR PAREAMENTO':
                    raise ValueError('Confirme que o arquivo será entregue somente à organização.')
                from admission_setup import descriptor
                for name in ('admission-cert.pem', 'admission-key.pem', 'admission-control.txt', 'admission-score.txt', 'credentials.json'):
                    if not (kit.LOCAL/app.profile()/name).exists():
                        raise ValueError('Identidade privada incompleta. Solicite o kit correspondente aos pendrives antes de exportar.')
                descriptor(app.profile())
                path = kit.LOCAL/app.profile()/'projector-desk.json'
                result = {'filename': path.name, 'content': path.read_text()}
            else:
                self.reply(404, {'error': 'Tarefa não encontrada.'}); return
            self.reply(200, result)
        except (OSError, ValueError, RuntimeError, KeyError, TypeError, subprocess.TimeoutExpired) as exc:
            self.reply(400, {'error': safe_error(exc)})


def serve(open_browser=False, preparation_only=False):
    if os.name == 'nt' and not preparation_only:
        import ctypes
        if not ctypes.windll.shell32.IsUserAnAdmin():
            raise ValueError('Abra pelo atalho Central SEMINFO; o Windows solicitará permissão para gerenciar o firewall da mesa.')
    app = Organizer(preparation_only=preparation_only)
    port = PREPARATION_PORT if preparation_only else PORT
    server = Server(('127.0.0.1', port), Panel, None)
    server.app, server.token = app, secrets.token_urlsafe(32)
    url = f'http://127.0.0.1:{port}/login?token='+server.token
    (app.folder/'panel.txt').write_text(url, encoding='utf-8')
    threading.Thread(target=app.refresh, daemon=True).start()
    if open_browser:
        threading.Timer(.5, lambda: webbrowser.open(url)).start()
    server.serve_forever()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--serve', action='store_true')
    parser.add_argument('--open', action='store_true')
    parser.add_argument('--prepare', action='store_true',help='Configuração local de provas, sem alterar firewall e sem exigir administrador do Windows')
    args = parser.parse_args()
    if args.serve:
        serve(args.open,preparation_only=args.prepare); return
    path = kit.LOCAL/('organizer-preparation/panel.txt' if args.prepare else 'organizer/panel.txt')
    if path.exists():
        from admission_setup import authenticated_opener
        try:
            with authenticated_opener().open(path.read_text(), timeout=2) as response:
                if response.status == 200 and b'Central SEMINFO' in response.read():
                    webbrowser.open(path.read_text()); return
        except OSError:
            pass
    if args.prepare:
        serve(open_browser=True,preparation_only=True)
    elif os.name == 'nt':
        # UAC stays with Windows; no passwords are collected by the application.
        import ctypes
        if ctypes.windll.shell32.IsUserAnAdmin():
            serve(open_browser=True)
        else:
            kit.run(['powershell', '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', kit.HERE/'launch-organizer.ps1'], timeout=180)
    else:
        serve(open_browser=True)


if __name__ == '__main__':
    try:
        main()
    except Exception as exc:
        message = 'Não foi possível abrir a Central SEMINFO. '+safe_error(exc)
        if os.name == 'nt':
            import ctypes
            ctypes.windll.user32.MessageBoxW(None, message, 'Central SEMINFO', 16)
        else:
            print(message, file=sys.stderr)
        sys.exit(1)
