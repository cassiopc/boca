"""Teacher-side enrollment and BOCA access gate. Python standard library only.

TLS is pinned by the participant image. No administrator credential travels to a
participant. Approval is deliberately volatile: restarting the desk closes access.
"""
from __future__ import annotations

import argparse
import hashlib
import hmac
import html
import http.client
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
import ipaddress
import json
import os
import re
import secrets
import ssl
import threading
import time
from pathlib import Path
from urllib.parse import parse_qs, urlsplit

LEASE_SECONDS = 60
PENDING_SECONDS = 600
HEX = re.compile(r'^[0-9a-f]{64}$')


class Registry:
    def __init__(self, config, audit=None, clock=time.monotonic, on_change=None):
        self.config, self.clock, self.audit, self.on_change = config, clock, audit, on_change
        self.lock = threading.RLock()
        self.records = {}

    def log(self, action, record):
        if self.audit:
            with Path(self.audit).open('a', encoding='utf-8') as out:
                out.write(json.dumps({'at': time.time(), 'action': action,
                    **{k: record[k] for k in ('id', 'device', 'team', 'ip', 'code')}}, ensure_ascii=False)+'\n')

    def register(self, data, address):
        with self.lock:
            ip = ipaddress.IPv4Address(address)
            net = ipaddress.IPv4Network(self.config['subnet'])
            if ip not in net or str(ip) == self.config['server_ip'] or ip in (net.network_address, net.broadcast_address):
                raise ValueError('IP fora da rede da banca')
            if data.get('profile') != self.config['profile']:
                raise ValueError('Banca de outro laboratório')
            if any(not HEX.fullmatch(str(data.get(k, ''))) for k in ('device', 'secret', 'nonce')):
                raise ValueError('Identidade inválida')
            if type(data.get('team')) is not int or not 1 <= data['team'] <= self.config['teams']:
                raise ValueError('Equipe inválida')
            now = self.clock()
            self.records = {k: r for k, r in self.records.items() if now-r['seen'] < PENDING_SECONDS}
            key = hashlib.sha256((data['device']+data['nonce']).encode()).hexdigest()
            old = self.records.get(key)
            secret_hash = hashlib.sha256(data['secret'].encode()).hexdigest()
            if old:
                if old['ip'] != str(ip) or old['team'] != data['team'] or not hmac.compare_digest(old['secret_hash'], secret_hash):
                    raise ValueError('Solicitação mudou; solicite novamente')
                old['seen'] = now
                return self.public(old)
            if len(self.records) >= 128:
                raise ValueError('Fila cheia; procure a banca')
            # A new boot/IP never inherits an approval, even for the same USB.
            for record in self.records.values():
                if record['device'] == data['device']:
                    record['status'] = 'revoked'
                    record['until'] = 0
            record = dict(id=key, device=data['device'], nonce=data['nonce'], secret_hash=secret_hash,
                ip=str(ip), team=data['team'], code=secrets.token_hex(4).upper(),
                status='pending', seen=now, until=0)
            self.records[key] = record
            self.log('request', record)
            return self.public(record)

    def public(self, record):
        now = self.clock()
        status = record['status']
        if status == 'approved' and record['until'] <= now:
            status = record['status'] = 'expired'
        return {k: record[k] for k in ('id', 'device', 'nonce', 'ip', 'team', 'code')} | {
            'status': status, 'profile': self.config['profile'],
            'server_ip': self.config['server_ip'], 'lease_seconds': LEASE_SECONDS if status == 'approved' else 0}

    def poll(self, data, address):
        with self.lock:
            record = self.records.get(str(data.get('id', '')))
            secret_hash = hashlib.sha256(str(data.get('secret', '')).encode()).hexdigest()
            if not record or record['ip'] != address or not hmac.compare_digest(record['secret_hash'], secret_hash):
                raise ValueError('Solicitação não autenticada')
            now = self.clock()
            record['seen'] = now
            if record['status'] == 'approved' and record['until'] > now:
                record['until'] = now+LEASE_SECONDS
            return self.public(record)

    def decide(self, key, action, code=''):
        with self.lock:
            record = self.records.get(key)
            if not record:
                raise ValueError('Solicitação não existe')
            if action == 'approve':
                if self.clock()-record['seen'] > 30:
                    raise ValueError('Dispositivo desconectado; aguarde uma consulta recente')
                if not hmac.compare_digest(record['code'], code.strip().upper()):
                    raise ValueError('Confira o código exibido no PC da equipe')
                for other in self.records.values():
                    if other['id'] != key and other['status'] == 'approved' and other['until'] > self.clock():
                        if other['team'] == record['team'] or other['ip'] == record['ip']:
                            raise ValueError('Equipe/IP já aprovado; revogue o anterior primeiro')
                record['status'], record['until'] = 'approved', self.clock()+LEASE_SECONDS
            elif action == 'revoke':
                record['status'], record['until'] = 'revoked', 0
            else:
                raise ValueError('Ação inválida')
            self.log(action, record)
            if self.on_change:
                try:
                    self.on_change([r['ip'] for r in self.records.values() if r['status']=='approved' and r['until']>self.clock()])
                except Exception:
                    record['status'],record['until']='revoked',0
                    self.log('firewall-failed',record)
                    raise RuntimeError('Firewall da mesa recusou a alteração; acesso bloqueado')

    def allowed(self, address):
        with self.lock:
            if address in ('127.0.0.1', self.config['server_ip']):
                return True
            return any(r['ip'] == address and r['status'] == 'approved' and r['until'] > self.clock()
                       for r in self.records.values())


class Server(ThreadingHTTPServer):
    daemon_threads = True
    allow_reuse_address = True

    def __init__(self, address, handler, registry):
        self.registry = registry
        self.slots = threading.BoundedSemaphore(48)
        super().__init__(address, handler)

    def process_request(self, request, address):
        if not self.slots.acquire(blocking=False):
            request.close()
            return
        try:
            super().process_request(request, address)
        except Exception:
            self.slots.release()
            raise

    def process_request_thread(self, request, address):
        try:
            super().process_request_thread(request, address)
        finally:
            self.slots.release()


class Handler(BaseHTTPRequestHandler):
    def setup(self):
        super().setup()
        self.connection.settimeout(10)

    def log_message(self, *args):
        pass  # Never log client secrets, session cookies or the admin login URL.

    def reply(self, status, data, content_type='application/json; charset=utf-8', cookie=None):
        payload = json.dumps(data, ensure_ascii=False).encode() if isinstance(data, dict) else data.encode()
        self.send_response(status)
        self.send_header('Content-Type', content_type)
        self.send_header('Content-Length', str(len(payload)))
        self.send_header('Cache-Control', 'no-store')
        self.send_header('X-Frame-Options', 'DENY')
        self.send_header('X-Content-Type-Options', 'nosniff')
        self.send_header('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; frame-ancestors 'none'")
        if cookie:
            self.send_header('Set-Cookie', cookie)
        self.end_headers()
        self.wfile.write(payload)

    def body(self, maximum=8192):
        if self.headers.get('Transfer-Encoding') or len(self.headers.get_all('Content-Length', [])) > 1:
            raise ValueError('Enquadramento HTTP inválido')
        length = int(self.headers.get('Content-Length', '0'))
        if not 0 < length <= maximum:
            raise ValueError('Tamanho inválido')
        payload = self.rfile.read(length)
        if len(payload) != length:
            raise ValueError('Corpo incompleto')
        return payload


class Enrollment(Handler):
    def do_POST(self):
        try:
            data = json.loads(self.body())
            if not isinstance(data, dict):
                raise ValueError('JSON inválido')
            reg = self.server.registry
            if self.path.startswith('/control/'):
                auth = self.headers.get('Authorization', '')
                control = hmac.compare_digest(auth, 'Bearer '+self.server.control_token)
                score_only = hmac.compare_digest(auth, 'Bearer '+self.server.score_token)
                if data.get('profile') != reg.config['profile'] or not (control or (score_only and self.path == '/control/score')):
                    self.reply(403, {'error': 'Autorização administrativa inválida'})
                    return
                if self.path == '/control/list':
                    with reg.lock:
                        result = {'records': [reg.public(r) for r in reg.records.values()]}
                elif self.path == '/control/decision':
                    reg.decide(data.get('id'), data.get('action'), data.get('code', ''))
                    result = {'ok': True}
                elif self.path == '/control/score':
                    result = self.server.score_provider()
                else:
                    raise ValueError('Rota inválida')
            elif self.path == '/request':
                result = reg.register(data, self.client_address[0])
            elif self.path == '/lease':
                result = reg.poll(data, self.client_address[0])
            else:
                raise ValueError('Rota inválida')
            self.reply(200, result)
        except (ValueError, TypeError, KeyError, RuntimeError, OSError):
            self.reply(400, {'error': 'Pedido recusado; confira laboratório, equipe e rede com a banca'})


PAGE = '''<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width">
<title>Banca SEMINFO</title><style>body{font:18px/1.5 system-ui;max-width:1250px;margin:32px auto;padding:20px;background:#faf8f4;color:#30261e}
h1{font-size:30px}a{color:#794512}table{border-collapse:collapse;width:100%;background:white}td,th{padding:14px;text-align:left;border-bottom:1px solid #dfd7cc}th{background:#eee3d5}button,input{padding:10px;font:inherit;border:1px solid #ad9271;border-radius:6px}button{background:#875018;color:white;cursor:pointer}input{max-width:150px}small{display:block;color:#675d52}.notice{padding:18px;background:#fff0db;border-left:4px solid #a9671b;margin:20px 0}.badge{display:inline-block;border-radius:6px;background:#f3e5cf;padding:4px 10px;font-size:16px}.approved{background:#e6f3e9;color:#256342}.quiet{background:white;color:#794512;margin-top:8px}.empty{padding:30px;text-align:center;color:#675d52}button:focus-visible,input:focus-visible,a:focus-visible{outline:3px solid #287bc0;outline-offset:3px}@media(max-width:850px){body{font-size:16px;padding:12px}td,th{padding:8px}form button{display:block;margin-top:8px}}</style>'''

STATUS_LABELS = {'pending': 'Aguardando aprovação', 'approved': 'Acesso liberado', 'revoked': 'Acesso revogado', 'expired': 'Autorização expirada'}


def decision_form(record, token, desk=None):
    form = '<form method="post" action="/decision"><input type="hidden" name="csrf" value="'+html.escape(token)+'"><input type="hidden" name="id" value="'+html.escape(record['id'])+'">'
    if desk is not None:
        form += '<input type="hidden" name="desk" value="'+html.escape(desk)+'">'
    if record['status'] != 'approved':
        form += '<label><small>Código exibido no computador da equipe</small><input name="code" size="9" maxlength="8" placeholder="8 caracteres" autocomplete="off" aria-label="Código de conferência"></label> <button name="action" value="approve">Conferir código e liberar</button>'
    else:
        form += '<button class="quiet" name="action" value="revoke">Revogar acesso…</button>'
    return form+'</form>'


def revoke_confirmation(record, token, desk=None):
    fields = {'csrf': token, 'id': record['id'], 'action': 'revoke', 'confirmed': 'yes'}
    if desk is not None:fields['desk'] = desk
    form = '<form method="post" action="/decision">'+''.join('<input type="hidden" name="'+k+'" value="'+html.escape(v)+'">' for k,v in fields.items())
    return PAGE+'<h1>Revogar acesso da equipe '+str(int(record['team']))+'?</h1><p class="notice">O computador '+html.escape(record['ip'])+' perderá o acesso ao BOCA desta sala. Para voltar, a equipe precisará solicitar e receber uma nova aprovação.</p>'+form+'<button>Confirmar revogação</button></form><p><a href="/">Cancelar e voltar às solicitações</a></p>'


class Admin(Handler):
    def authorized(self):
        cookies = self.headers.get('Cookie', '').split(';')
        return any(hmac.compare_digest(c.strip(), 'seminfo_admin='+self.server.token) for c in cookies)

    def do_GET(self):
        parsed = urlsplit(self.path)
        token = parse_qs(parsed.query).get('token', [''])[0]
        if parsed.path == '/login' and hmac.compare_digest(token, self.server.token):
            self.send_response(303)
            self.send_header('Location', '/')
            self.send_header('Set-Cookie', 'seminfo_admin='+token+'; HttpOnly; SameSite=Strict; Path=/')
            self.send_header('Cache-Control', 'no-store')
            self.end_headers()
            return
        if not self.authorized():
            self.reply(403, {'error': 'Abra Central SEMINFO nesta mesa e clique em Abrir solicitações da sala.'})
            return
        reg = self.server.registry
        with reg.lock:
            records = [reg.public(r) for r in reg.records.values()]
        rows = ''
        for r in sorted(records, key=lambda r: (r['status'] != 'pending', r['team'])):
            form = decision_form(r, self.server.token)
            rows += '<tr>'+''.join('<td>'+html.escape(str(v))+'</td>' for v in (r['team'], r['ip'], r['device'][:12]))+'<td><span class="badge '+r['status']+'">'+STATUS_LABELS.get(r['status'], 'Conferir estado')+'</span></td><td>'+form+'</td></tr>'
        if not records:rows='<tr><td colspan="5" class="empty">Nenhuma solicitação recebida. No computador da equipe, informe o IP da mesa e clique em Solicitar à banca. Depois atualize esta página.</td></tr>'
        pending=sum(r['status']=='pending' for r in records)
        approved=sum(r['status']=='approved' for r in records)
        self.reply(200, PAGE+f'<h1>Banca — Lab {reg.config["lab"]} / {reg.config["profile"].upper()}</h1><p>1. Confira a sala e o número da equipe. 2. Peça o código mostrado no PC. 3. Digite o código e libere o acesso.</p><p><a href="/">Atualizar solicitações</a> · <a href="http://127.0.0.1:17862/">Voltar à Central SEMINFO</a></p><p class="notice">{pending} aguardando aprovação · {approved} com acesso liberado. A autorização expira em até 60 segundos sem contato com o computador.</p><table><tr><th>Equipe</th><th>IP observado</th><th>Dispositivo</th><th>Situação</th><th>Ação</th></tr>'+rows+'</table>', 'text/html; charset=utf-8')

    def do_POST(self):
        try:
            if self.path not in ('/decision','/shutdown') or not self.authorized():
                raise ValueError('Sessão inválida')
            if self.headers.get('Origin') not in (None, f'http://127.0.0.1:{self.server.server_port}'):
                raise ValueError('Origem inválida')
            data = parse_qs(self.body().decode())
            if not hmac.compare_digest(data.get('csrf', [''])[0], self.server.token):
                raise ValueError('Formulário inválido')
            if self.path == '/shutdown':
                self.reply(200, {'ok': True})
                self.server.stop_event.set()
                return
            if data.get('action', [''])[0] == 'revoke' and data.get('confirmed', [''])[0] != 'yes':
                with self.server.registry.lock:
                    record = self.server.registry.records[data['id'][0]].copy()
                self.reply(200, revoke_confirmation(record, self.server.token), 'text/html; charset=utf-8')
                return
            self.server.registry.decide(data.get('id', [''])[0], data.get('action', [''])[0], data.get('code', [''])[0])
            self.send_response(303)
            self.send_header('Location', '/')
            self.end_headers()
        except (ValueError, KeyError, RuntimeError) as exc:
            self.reply(400, PAGE+'<p>'+html.escape(str(exc))+'</p><a href="/">Voltar</a>', 'text/html; charset=utf-8')


HOP = {'connection', 'proxy-connection', 'keep-alive', 'transfer-encoding', 'upgrade', 'te', 'trailer', 'proxy-authorization', 'proxy-authenticate'}


class Gate(Handler):
    def do_HEAD(self):
        self.proxy(head=True)

    def do_GET(self):
        self.proxy()

    def do_POST(self):
        self.proxy()

    def proxy(self, head=False):
        if not self.server.registry.allowed(self.client_address[0]):
            self.reply(403, {'error': 'Acesso pendente ou revogado. Solicite autorização na janela SEMINFO.'})
            return
        parsed = urlsplit(self.path)
        if parsed.scheme or parsed.netloc or not (parsed.path == '/boca' or parsed.path.startswith('/boca/')):
            self.reply(404, {'error': 'Rota indisponível'})
            return
        upstream = http.client.HTTPConnection('127.0.0.1', self.server.backend_port, timeout=10)
        started = False
        try:
            payload = self.body(128*1024*1024) if self.command == 'POST' else None
            excluded = HOP | {'host', 'content-length', 'expect', 'forwarded', 'x-forwarded-for'}
            excluded |= {p.strip().lower() for p in self.headers.get('Connection', '').split(',')}
            headers = {k: v for k, v in self.headers.items() if k.lower() not in excluded}
            headers['Host'] = self.server.registry.config['server_ip']+':8000'
            upstream.request(self.command, self.path, body=payload, headers=headers)
            response = upstream.getresponse()
            self.send_response(response.status)
            for k, v in response.getheaders():
                if k.lower() not in HOP:
                    self.send_header(k, v)
            self.end_headers()
            started = True
            if not head:
                while self.server.registry.allowed(self.client_address[0]):
                    chunk = response.read(65536)
                    if not chunk:
                        break
                    self.wfile.write(chunk)
        except (OSError, ValueError, http.client.HTTPException):
            if not started:
                self.reply(502, {'error': 'BOCA indisponível; procure a banca'})
        finally:
            upstream.close()


def serve(folder, *, bind='0.0.0.0', enrollment_port=8443, gate_port=8000, admin_port=17860, backend_port=18000):
    folder = Path(folder)
    config = json.loads((folder/'lab.json').read_text(encoding='utf-8-sig'))
    import manage
    manage.validate(config, network=True)
    def firewall(approved):
        manage.write_json(folder/'admission-approved.json',approved)
        if os.name=='nt':
            manage.run(['powershell','-NoProfile','-ExecutionPolicy','Bypass','-File',
                manage.HERE/'firewall-mesa.ps1','-Profile',config['profile']],timeout=40)
    firewall([])
    registry = Registry(config, folder/'admission-audit.jsonl',on_change=firewall)
    servers,started = [],[]
    try:
        enroll = Server((bind, enrollment_port), Enrollment, registry)
        enroll.control_token = (folder/'admission-control.txt').read_text().strip()
        enroll.score_token = (folder/'admission-score.txt').read_text().strip()
        if min(len(enroll.control_token),len(enroll.score_token))<32:
            raise ValueError('Credenciais da banca incompletas; acesso permanece fechado')
        enroll.score_provider = lambda: manage.control(config['profile'], 'scoreboard')
        servers.append(enroll)
        context = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
        context.minimum_version = ssl.TLSVersion.TLSv1_2
        context.load_cert_chain(folder/'admission-cert.pem', folder/'admission-key.pem')
        # Wrap accepted sockets in their worker, so a silent TLS client cannot
        # block accept() for every other team.
        original = enroll.finish_request
        def finish(request, address):
            request.settimeout(5)
            with context.wrap_socket(request, server_side=True) as secure:
                original(secure, address)
        enroll.finish_request = finish
        gate = Server((bind, gate_port), Gate, registry)
        gate.backend_port = backend_port
        servers.append(gate)
        admin = Server(('127.0.0.1', admin_port), Admin, registry)
        admin.token = secrets.token_urlsafe(32)
        admin.stop_event = threading.Event()
        servers.append(admin)
        (folder/'admission-panel.txt').write_text(f'http://127.0.0.1:{admin_port}/login?token={admin.token}', encoding='utf-8')
        for server in servers:
            threading.Thread(target=server.serve_forever, daemon=True).start()
            started.append(server)
        print('Banca pronta; BOCA protegido, fila TLS e painel somente local.', flush=True)
        while not admin.stop_event.wait(1):
            pass
    finally:
        firewall([])
        for server in servers:
            if server in started:
                server.shutdown()
            server.server_close()


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--folder', required=True, type=Path)
    args = parser.parse_args()
    serve(args.folder)
