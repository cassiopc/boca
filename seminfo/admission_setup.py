"""Keys, desk service, pairing descriptors and local launchers; no pip needed."""
import hashlib
import http.cookiejar
import json
import os
from pathlib import Path
import secrets
import ssl
import subprocess
import sys
import time
import urllib.request
import webbrowser


def authenticated_opener():
    # /login redirects to /. Keep the HttpOnly cookie during that redirect,
    # just as the organizer's browser does when opening the private panel.
    return urllib.request.build_opener(urllib.request.ProxyHandler({}),
        urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))


def ensure_keys(profile):
    import manage as kit
    folder = kit.LOCAL/profile
    if not folder.exists():
        kit.private_directory(folder)
    cert, key = folder/'admission-cert.pem', folder/'admission-key.pem'
    if cert.exists() != key.exists():
        raise ValueError('Par de chaves incompleto; restaure o backup da banca')
    if not cert.exists():
        if not kit.exists('seminfo/client-builder:22.04'):
            kit.run(['docker','build','-t','seminfo/client-builder:22.04','-f',
                kit.HERE/'client/builder.Dockerfile',kit.HERE/'client'],capture=False,timeout=1800)
        kit.run(['docker', 'run', '--rm', '--network', 'none', '--mount',
            f'type=bind,source={folder.resolve()},target=/keys', 'seminfo/client-builder:22.04',
            'openssl', 'req', '-x509', '-newkey', 'rsa:3072', '-nodes', '-days', '3650',
            '-keyout', '/keys/admission-key.pem', '-out', '/keys/admission-cert.pem',
            '-subj', '/CN=SEMINFO-'+profile, '-addext', 'basicConstraints=critical,CA:FALSE'])
    token = folder/'admission-control.txt'
    if not token.exists():
        token.write_text(secrets.token_urlsafe(32), encoding='utf-8')
    score = folder/'admission-score.txt'
    if not score.exists():
        score.write_text(secrets.token_urlsafe(32), encoding='utf-8')
    for path in (token,score):
        if len(path.read_text().strip())<32:
            raise ValueError('Credencial da banca incompleta; restaure a identidade privada')
    if os.name != 'nt':
        key.chmod(0o600)
        token.chmod(0o600)
        score.chmod(0o600)
    return hashlib.sha256(ssl.PEM_cert_to_DER_cert(cert.read_text())).hexdigest()


def descriptor(profile):
    import manage as kit
    cfg = kit.validate(kit.config(profile), network=True)
    fingerprint = ensure_keys(profile)
    output = kit.LOCAL/profile/'admission-desk.json'
    from hub import LABELS
    common = {'id': profile, 'label': LABELS[profile],
        'profile': profile, 'server_ip': cfg['server_ip'], 'fingerprint': fingerprint,
        'score_token': (kit.LOCAL/profile/'admission-score.txt').read_text().strip()}
    kit.write_json(output, common | {'control_token': (kit.LOCAL/profile/'admission-control.txt').read_text().strip()})
    kit.write_json(kit.LOCAL/profile/'projector-desk.json',common)
    print(f'Arquivo PRIVADO para parear a central: {output}')
    return output


def running(profile):
    import manage as kit
    folder = kit.LOCAL/profile
    path = folder/'admission-process.json'
    if not path.exists():
        return False
    info = json.loads(path.read_text())
    # Admin URL is an unguessable per-process session; a stale PID is not enough.
    try:
        opener = authenticated_opener()
        with opener.open(info['url'], timeout=2) as response:
            return response.status == 200 and b'Banca SEMINFO' in response.read()
    except OSError:
        return False


def start(profile):
    import manage as kit
    cfg = kit.validate(kit.config(profile), network=True)
    ensure_keys(profile)
    if running(profile):
        info=json.loads((kit.LOCAL/profile/'admission-process.json').read_text())
        if info.get('source_sha256')==kit.source_hash():
            return
        stop(profile)
        for _ in range(600):
            if not running(profile):break
            time.sleep(.1)
        else:raise ValueError('Banca anterior não encerrou; inicie novamente depois de parar a instância antiga')
    folder = kit.LOCAL/profile
    panel = folder/'admission-panel.txt'
    if panel.exists():
        panel.unlink()
    options = {'creationflags': subprocess.CREATE_NO_WINDOW} if os.name == 'nt' else {'start_new_session': True}
    with (folder/'admission-service.log').open('ab') as log:
        process = subprocess.Popen([sys.executable, str(kit.HERE/'admission.py'), '--folder', str(folder.resolve())],
            stdout=log, stderr=log, stdin=subprocess.DEVNULL, **options)
    # Windows Firewall can take tens of seconds to load/apply. Its own timeout
    # is 40 s; do not kill a healthy startup after only five seconds.
    for _ in range(600):
        if process.poll() is not None:
            raise ValueError('Serviço da banca não iniciou. Confira portas 8000/8443/17860 e admission-service.log')
        if panel.exists():
            kit.write_json(folder/'admission-process.json', {'pid': process.pid, 'url': panel.read_text(),'source_sha256':kit.source_hash()})
            if running(profile):
                print('Banca iniciada; abra admission-panel para aprovar equipes.')
                return
        time.sleep(.1)
    process.terminate()
    raise ValueError('Serviço da banca não respondeu')


def panel(profile):
    import manage as kit
    if not running(profile):
        raise ValueError('Inicie a mesa com start antes de abrir o painel')
    webbrowser.open((kit.LOCAL/profile/'admission-panel.txt').read_text())


def stop(profile):
    import manage as kit
    if running(profile):
        path = kit.LOCAL/profile/'admission-process.json'
        info = json.loads(path.read_text())
        # Authenticated local request makes the owned service stop itself; never
        # terminate a PID that may have been reused by another application.
        import http.cookiejar
        opener = urllib.request.build_opener(urllib.request.ProxyHandler({}), urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        opener.open(info['url'], timeout=2).close()
        token = info['url'].split('token=', 1)[1]
        from urllib.parse import urlencode
        request = urllib.request.Request('http://127.0.0.1:17860/shutdown',
            urlencode({'csrf': token}).encode())
        opener.open(request, timeout=2).close()
