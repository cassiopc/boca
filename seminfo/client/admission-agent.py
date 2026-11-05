#!/usr/bin/env python3
"""Privileged local bridge. Never trusts an approval supplied by the desktop UI."""
import hashlib
import hmac
import json
import os
from pathlib import Path
import secrets
import socket
import subprocess
import sys
import threading
import time
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

sys.path.insert(0, '/usr/local/lib/seminfo')
from client_config import rules
from admission_wire import address, post


class Agent:
    def __init__(self, config, *, identity_path=Path('/home/.seminfo-device.json'), apply=None, transport=post):
        self.config, self.transport = config, transport
        self.apply = apply or self.nft
        self.lock = threading.RLock()
        self.identity = None
        self.identity_path = Path(identity_path)
        self.ip, self.server, self.request, self.team = '', config.get('server_ip'), None, None
        self.until = 0
        self.status, self.code = 'Informe a equipe e solicite acesso', ''
        self.pending = None

    @staticmethod
    def nft(text):
        subprocess.run(['nft', '-f', '-'], input=text.encode(), check=True, timeout=4)

    def identity_load(self):
        path = self.identity_path
        if path.exists():
            st = path.stat()
            if path.is_symlink() or st.st_uid != os.geteuid() or st.st_mode & 0o077:
                raise ValueError('Identidade persistente sem protecao de administrador')
            data = json.loads(path.read_text())
        else:
            data = {'device': secrets.token_hex(32), 'secret': secrets.token_hex(32)}
            fd = os.open(path, os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600)
            with os.fdopen(fd, 'w') as out:
                json.dump(data, out)
        for key in ('device', 'secret'):
            if len(data[key]) != 64 or any(c not in '0123456789abcdef' for c in data[key]):
                raise ValueError('Identidade invalida')
        self.identity = data

    def close(self):
        self.until = 0
        self.apply(rules(self.server, enrollment=True, approved=False) if self.server else rules())

    def submit(self, server, team):
        server = address(server)
        if type(team) is not int or not 1 <= team <= self.config['teams']:
            raise ValueError('Equipe invalida')
        with self.lock:
            self.pending = (server, team)
        return {'ok': True}

    def snapshot(self):
        with self.lock:
            if not self.ip:
                try:
                    addresses = json.loads(subprocess.check_output(['ip', '-j', '-4', 'addr', 'show', 'scope', 'global'], timeout=1))
                    self.ip = ', '.join(a['local'] for interface in addresses for a in interface.get('addr_info', []))
                except (OSError, ValueError, subprocess.SubprocessError):
                    pass
            return {'ip': self.ip or 'Aguardando rede', 'server_ip': self.server or '',
                'profile': self.config['profile'], 'lab': self.config['lab'],
                'teams': self.config['teams'], 'device': (self.identity or {}).get('device', '')[:12],
                'status': self.status, 'code': self.code,
                'approved': self.until > time.monotonic(),
                'url': f'http://{self.server}:8000/boca/' if self.until > time.monotonic() else ''}

    def accept(self, result, local_ip, sent_at):
        if not self.request:
            raise ValueError('Sem solicitacao local')
        expected_id = hashlib.sha256((self.identity['device']+self.request['nonce']).encode()).hexdigest()
        expected = {'id': expected_id, 'device': self.identity['device'], 'nonce': self.request['nonce'],
                    'profile': self.config['profile'], 'server_ip': self.server,
                    'team': self.team, 'ip': local_ip}
        if any(result.get(k) != v for k, v in expected.items()):
            raise ValueError('Autorizacao nao corresponde ao dispositivo, IP e laboratorio')
        self.ip, self.code = local_ip, result['code']
        self.request['id'] = expected_id
        status = result['status']
        labels = {'pending': 'Aguardando aprovacao da banca', 'approved': 'Acesso autorizado',
                  'revoked': 'Acesso revogado; procure a banca', 'expired': 'Autorizacao expirou; solicite novamente'}
        if status not in labels:
            raise ValueError('Resposta invalida')
        self.status = labels[status]
        if status == 'approved':
            seconds = result.get('lease_seconds')
            if type(seconds) is not int or not 1 <= seconds <= 60:
                raise ValueError('Prazo de autorizacao invalido')
            remaining = int(sent_at+seconds-time.monotonic())
            if remaining <= 0:
                raise ValueError('Autorizacao recebida fora do prazo')
            self.apply(rules(self.server, enrollment=True, approved=True, lease_seconds=remaining))
            self.until = sent_at+seconds
        else:
            self.close()

    def tick(self):
        with self.lock:
            if self.pending:
                self.server, self.team = self.pending
                self.pending = None
                self.close()
                self.request = {'profile': self.config['profile'], **self.identity,
                                'nonce': secrets.token_hex(32), 'team': self.team}
                self.code = ''
            request, server = self.request, self.server
        if not request:
            return
        try:
            sent = time.monotonic()
            path = '/lease' if 'id' in request else '/request'
            result, local_ip = self.transport(server, self.config['admission_fingerprint'], path, request)
            with self.lock:
                self.accept(result, local_ip, sent)
        except (OSError, ValueError, KeyError, TypeError):
            with self.lock:
                self.close()
                self.status = 'Sem autorizacao valida; confira a rede ou solicite novamente'


class LocalUI(BaseHTTPRequestHandler):
    def log_message(self, *args):
        pass

    def reply(self, status, value):
        data = json.dumps(value, ensure_ascii=False).encode()
        self.send_response(status)
        self.send_header('Content-Type', 'application/json')
        self.send_header('Content-Length', str(len(data)))
        self.send_header('Cache-Control', 'no-store')
        self.end_headers()
        self.wfile.write(data)

    def valid_host(self):
        return self.headers.get('Host') == '127.0.0.1:17631'

    def do_GET(self):
        if self.path != '/status' or not self.valid_host():
            return self.reply(403, {'error': 'Acesso local somente'})
        self.reply(200, self.server.agent.snapshot() | {'csrf': self.server.csrf})

    def do_POST(self):
        try:
            if self.path != '/request' or not self.valid_host() or self.headers.get('Origin'):
                raise ValueError('Origem invalida')
            if not hmac.compare_digest(self.headers.get('X-SEMINFO-CSRF', ''), self.server.csrf):
                raise ValueError('Pedido invalido')
            length = int(self.headers.get('Content-Length', '0'))
            if not 0 < length <= 1024 or self.headers.get('Transfer-Encoding'):
                raise ValueError('Tamanho invalido')
            data = json.loads(self.rfile.read(length))
            self.reply(200, self.server.agent.submit(data['server_ip'], data['team']))
        except (ValueError, KeyError, TypeError):
            self.reply(400, {'error': 'Informe o IP da banca e o numero da equipe'})


def main():
    if os.geteuid() != 0:
        raise SystemExit('Servico exclusivo do administrador')
    config = json.loads(Path('/etc/seminfo/lab.json').read_text())
    agent = Agent(config)
    agent.close()
    agent.identity_load()
    server = ThreadingHTTPServer(('127.0.0.1', 17631), LocalUI)
    server.daemon_threads = True
    server.csrf, server.agent = secrets.token_urlsafe(32), agent
    threading.Thread(target=server.serve_forever, daemon=True).start()
    try:
        while True:
            agent.tick()
            time.sleep(2)
    finally:
        agent.close()


if __name__ == '__main__':
    main()
