"""Two independent categories. Optional remote score viewing never merges ranks."""
from __future__ import annotations
import concurrent.futures
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
from urllib.parse import parse_qs, urlsplit, urlencode

from admission import Handler, PAGE, Server, STATUS_LABELS, decision_form, revoke_confirmation
from admission_wire import address, post


LABELS = {'si': 'Graduação / SI — Lab 3', 'tii': 'Médio/Técnico / TII — Lab 4'}


def normalize_catalog(catalog, manager_profile=None):
    """Retire legacy merged rankings and cross-category administrative tokens."""
    owner = manager_profile or catalog.get('manager_profile')
    if owner not in LABELS:
        owner = None  # A legacy/unscoped catalog is read-only until paired locally.
    desks = {}
    for key, desk in catalog.get('desks', {}).items():
        if desk.get('profile') not in LABELS:
            raise ValueError('Categoria da banca inválida')
        desk = dict(desk, label=LABELS[desk['profile']])
        if desk['profile'] != owner:
            desk.pop('control_token', None)
        desks[key] = desk
    return {'mode': 'independent_categories', 'manager_profile': owner, 'desks': desks}


def import_desk(path, manager_profile=None):
    import manage as kit
    data = json.loads(Path(path).read_text(encoding='utf-8-sig'))
    if not isinstance(data,dict) or data.get('profile') not in ('si','tii') or not isinstance(data.get('label'),str) or not 1<=len(data['label'])<=120:
        raise ValueError('Arquivo de pareamento inválido; exporte o descritor pela central da banca')
    if not re.fullmatch('[a-z0-9-]{1,32}', data['id']):
        raise ValueError('Identificador de laboratório inválido')
    address(data['server_ip'])
    if not isinstance(data.get('fingerprint'),str) or not re.fullmatch('[0-9a-f]{64}', data['fingerprint']) or not isinstance(data.get('score_token'),str) or len(data['score_token'])<32:
        raise ValueError('Credencial da banca inválida')
    if 'control_token' in data and (not isinstance(data['control_token'],str) or len(data['control_token'])<32):
        raise ValueError('Credencial administrativa incompleta')
    folder = kit.LOCAL/'hub'
    kit.private_directory(folder)
    catalog_path = folder/'desks.json'
    catalog = json.loads(catalog_path.read_text()) if catalog_path.exists() else {'desks': {}}
    owner = manager_profile or catalog.get('manager_profile')
    if manager_profile is not None and manager_profile not in LABELS:
        raise ValueError('Banca gerenciadora inválida')
    catalog['desks'][data['id']] = {k: data[k] for k in
        ('id','label','profile','server_ip','fingerprint','score_token','control_token') if k in data}
    catalog = normalize_catalog(catalog, owner)
    kit.write_json(catalog_path,catalog)
    print(f'Banca {data["id"]} pareada. Reinicie a central se estiver aberta. Credenciais ficam privadas.')


class Hub:
    def __init__(self,catalog,transport=post):
        self.catalog,self.transport=normalize_catalog(catalog),transport
        self.lock=threading.RLock()
        self.snapshot={}
        self.stop_event=threading.Event()

    def call(self,desk,path,data=None,*,control=False):
        d=self.catalog['desks'][desk]
        if control and d['profile'] != self.catalog['manager_profile']:
            raise ValueError('Esta banca gerencia somente sua categoria; a outra é apenas para consulta de placar')
        token=d.get('control_token') if control else d['score_token']
        if not token:
            raise ValueError('Este painel tem permissão somente para projetores')
        result,_=self.transport(d['server_ip'],d['fingerprint'],path,
            {'profile':d['profile'],**(data or {})},token=token)
        return result

    def refresh(self):
        def fetch(desk):
            try:
                score=self.call(desk,'/control/score')
                if score.get('profile')!=self.catalog['desks'][desk]['profile'] or not score.get('ok'):
                    raise ValueError('Placar de outra banca')
                return desk,{'score':score,'received':time.monotonic()}
            except (OSError,ValueError,KeyError):
                return desk,{'error':'Banca indisponível; classificação não atualizada'}
        with concurrent.futures.ThreadPoolExecutor(max_workers=8) as pool:
            snapshot=dict(pool.map(fetch,self.catalog['desks']))
        with self.lock:
            self.snapshot=snapshot

    def loop(self):
        while not self.stop_event.is_set():
            self.refresh()
            self.stop_event.wait(10)


def score_table(rows,desks):
    output='<table><tr><th>#</th><th>Equipe</th><th>Laboratório</th><th>Resolvidos</th><th>Penalidade</th></tr>'
    for position,team in enumerate(rows,1):
        values=(position,team['name']+' ('+team['team']+')',desks[team['desk']]['label'],team['solved'],team['penalty'])
        output+='<tr>'+''.join('<td>'+html.escape(str(v))+'</td>' for v in values)+'</tr>'
    return output+'</table>'


class Panel(Handler):
    def authorized(self):
        return any(hmac.compare_digest(c.strip(),'seminfo_hub='+self.server.token)
                   for c in self.headers.get('Cookie','').split(';'))

    def do_GET(self):
        parsed=urlsplit(self.path)
        query=parse_qs(parsed.query)
        if parsed.path=='/login' and hmac.compare_digest(query.get('token',[''])[0],self.server.token):
            self.send_response(303)
            self.send_header('Location','/')
            self.send_header('Set-Cookie','seminfo_hub='+self.server.token+'; HttpOnly; SameSite=Strict; Path=/')
            self.send_header('Cache-Control','no-store')
            self.end_headers()
            return
        hub=self.server.hub
        desks=hub.catalog['desks']
        if parsed.path.startswith('/projector/'):
            # Read-only pages are safe to project, with no approval controls or
            # private tokens. The HTTP listener itself is loopback-only.
            selected=parsed.path.removeprefix('/projector/')
            if selected == 'general':
                self.reply(410, PAGE+'<h1>Classificações separadas por categoria</h1><p>Médio/Técnico e Graduação têm questões diferentes. O ranking único foi retirado.</p>'+''.join('<p><a href="/projector/'+html.escape(k)+'">'+html.escape(d['label'])+'</a></p>' for k,d in desks.items()), 'text/html; charset=utf-8')
                return
            with hub.lock:
                snapshot=dict(hub.snapshot)
            scores={k:v['score'] for k,v in snapshot.items() if 'score' in v and time.monotonic()-v['received']<30}
            try:
                title='Placar — '+desks[selected]['label']
                if selected not in scores:
                    raise ValueError('Banca indisponível; aguardando placar atualizado desta categoria')
                rows=[t|{'desk':selected} for t in scores[selected]['teams']]
                body='<h1>'+html.escape(title)+'</h1>'+score_table(rows,desks)
                body+='<p>Placar congelado nesta categoria.</p>' if scores[selected].get('frozen') else ''
                body+='<p>Resultado final publicado nesta categoria.</p>' if scores[selected].get('public_final') else ''
                body+='<p>Atualizado às '+time.strftime('%H:%M:%S')+' · Próxima consulta em 10 s · Último aceite desempata.</p>'
            except (ValueError,KeyError) as exc:
                body='<h1>Placar aguardando validação</h1><p>'+html.escape(str(exc))+'</p>'
            projection_style='''<style>body{max-width:none;margin:12px 24px;padding:0}
                h1{font-size:30px;margin:0 0 12px}td,th{padding:4px 10px}
                p{font-size:14px;margin:8px 0}
                @media(max-height:800px){body{font-size:16px}h1{font-size:24px}td,th{padding:3px 8px}}
                </style>'''
            self.reply(200,PAGE+projection_style+'<meta http-equiv="refresh" content="10">'+body,'text/html; charset=utf-8')
            return
        if not self.authorized():
            self.reply(403,{'error':'Abra Central SEMINFO e clique em Abrir central de salas.'})
            return
        owner=hub.catalog['manager_profile']
        body='<h1>Central SEMINFO — bancas independentes</h1><p><a href="http://127.0.0.1:17862/">Voltar à operação da maratona</a> · <a href="/">Atualizar solicitações</a></p><p class="notice">Banca gerenciadora: '+html.escape(LABELS.get(owner,'Somente consulta de placares'))+'. Questões e classificações separadas; cada banca aprova apenas sua categoria.</p>'
        default=next((k for k,d in desks.items() if d['profile']==owner),next(iter(desks),''))
        selected=query.get('desk',[default])[0]
        body+='<p>Categoria: '+' · '.join('<a href="/?desk='+html.escape(k)+'">'+html.escape(d['label'])+'</a>' for k,d in desks.items())+'</p>'
        for k,d in desks.items():
            body+='<p><a target="_blank" href="/projector/'+html.escape(k)+'">Projetar '+html.escape(d['label'])+'</a></p>'
        if selected in desks and desks[selected].get('control_token'):
            try:
                records=hub.call(selected,'/control/list',control=True)['records']
                body+='<h2>'+html.escape(desks[selected]['label'])+'</h2><p class="notice">Você está autorizando computadores somente desta sala. Confira a equipe, o IP e o código exibido no computador.</p><table><tr><th>Equipe</th><th>IP observado</th><th>Dispositivo</th><th>Situação</th><th>Ação</th></tr>'
                for r in sorted(records,key=lambda r:(r['status']!='pending',r['team'])):
                    body+='<tr>'+''.join('<td>'+html.escape(str(v))+'</td>' for v in (r['team'],r['ip'],r['device'][:12]))
                    body+='<td><span class="badge '+html.escape(r['status'])+'">'+STATUS_LABELS.get(r['status'],'Conferir estado')+'</span></td><td>'+decision_form(r,self.server.token,selected)+'</td></tr>'
                if not records:body+='<tr><td colspan="5" class="empty">Nenhuma solicitação nesta sala. Peça à equipe para solicitar acesso no computador e atualize esta página.</td></tr>'
                body+='</table>'
            except (ValueError,OSError,KeyError):
                body+='<p>Banca indisponível. Nenhuma aprovação foi aplicada.</p>'
        elif selected in desks:
            body+='<p>Esta categoria está disponível somente para consulta de placar. As aprovações são feitas pela banca responsável por ela.</p>'
        self.reply(200,PAGE+body,'text/html; charset=utf-8')

    def do_POST(self):
        try:
            if not self.authorized() or self.path not in ('/decision','/shutdown'):
                raise ValueError('Sessão inválida')
            if self.headers.get('Origin') not in (None,'http://127.0.0.1:17861'):
                raise ValueError('Origem inválida')
            data=parse_qs(self.body().decode())
            if not hmac.compare_digest(data.get('csrf',[''])[0],self.server.token):
                raise ValueError('Formulário inválido')
            if self.path=='/shutdown':
                self.reply(200,{'ok':True})
                self.server.hub.stop_event.set()
                return
            desk=data['desk'][0]
            if desk not in self.server.hub.catalog['desks']:
                raise ValueError('Laboratório inválido')
            if data.get('action',[''])[0]=='revoke' and data.get('confirmed',[''])[0]!='yes':
                records=self.server.hub.call(desk,'/control/list',control=True)['records']
                record=next((r for r in records if r['id']==data.get('id',[''])[0]),None)
                if record is None:raise ValueError('Solicitação não encontrada. Atualize a lista da sala.')
                self.reply(200,revoke_confirmation(record,self.server.token,desk),'text/html; charset=utf-8')
                return
            self.server.hub.call(desk,'/control/decision',{k:data.get(k,[''])[0] for k in ('id','action','code')},control=True)
            self.send_response(303)
            self.send_header('Location','/?desk='+desk)
            self.end_headers()
        except (OSError,ValueError,KeyError) as exc:
            self.reply(400,PAGE+'<p>'+html.escape(str(exc))+'</p><a href="/">Voltar</a>','text/html; charset=utf-8')


def serve(folder):
    folder=Path(folder)
    catalog=normalize_catalog(json.loads((folder/'desks.json').read_text()))
    import manage as kit
    kit.write_json(folder/'desks.json',catalog)
    hub=Hub(catalog)
    server=Server(('127.0.0.1',17861),Panel,None)
    server.hub,server.token=hub,secrets.token_urlsafe(32)
    (folder/'panel.txt').write_text('http://127.0.0.1:17861/login?token='+server.token)
    (folder/'source-sha256.txt').write_text(kit.source_hash())
    threading.Thread(target=hub.loop,daemon=True).start()
    threading.Thread(target=server.serve_forever,daemon=True).start()
    hub.stop_event.wait()
    server.shutdown()
    server.server_close()


def start(open_browser=True):
    import manage as kit
    folder=kit.LOCAL/'hub'
    if not (folder/'desks.json').exists():
        raise ValueError('Em Preparação da sala, adicione os arquivos de pareamento das bancas antes de abrir a central de salas.')
    original=json.loads((folder/'desks.json').read_text())
    normalized=normalize_catalog(original)
    if original!=normalized:
        stop()
        kit.write_json(folder/'desks.json',normalized)
    panel=folder/'panel.txt'
    if panel.exists():
        import urllib.request
        try:
            from admission_setup import authenticated_opener
            opener=authenticated_opener()
            with opener.open(panel.read_text(),timeout=2) as response:
                if response.status==200 and b'Central SEMINFO' in response.read():
                    version=folder/'source-sha256.txt'
                    if version.exists() and version.read_text()==kit.source_hash():
                        if open_browser:webbrowser.open(panel.read_text())
                        return
                    stop()
                    for _ in range(50):
                        try:
                            opener.open(panel.read_text(),timeout=1).close()
                        except OSError:break
                        time.sleep(.1)
                    else:raise ValueError('A central anterior não encerrou. Aguarde antes de abrir a nova versão.')
        except OSError:
            pass
    if panel.exists():panel.unlink()
    options={'creationflags':subprocess.CREATE_NO_WINDOW} if os.name=='nt' else {'start_new_session':True}
    with (folder/'service.log').open('ab') as log:
        process=subprocess.Popen([sys.executable,str(kit.HERE/'hub.py'),'--serve',str(folder.resolve())],stdout=log,stderr=log,stdin=subprocess.DEVNULL,**options)
    for _ in range(50):
        if process.poll() is not None:raise ValueError('Central não iniciou; confira local/hub/service.log')
        if panel.exists():
            if open_browser:webbrowser.open(panel.read_text())
            return
        time.sleep(.1)
    process.terminate()
    raise ValueError('Central não respondeu')


def stop():
    import manage as kit
    import http.cookiejar
    import urllib.request
    path=kit.LOCAL/'hub/panel.txt'
    if not path.exists():return
    url=path.read_text()
    opener=urllib.request.build_opener(urllib.request.ProxyHandler({}),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    try:opener.open(url,timeout=2).close()
    except OSError:return
    request=urllib.request.Request('http://127.0.0.1:17861/shutdown',urlencode({'csrf':url.split('token=')[1]}).encode())
    opener.open(request,timeout=2).close()


if __name__=='__main__':
    if len(sys.argv)==3 and sys.argv[1]=='--serve':serve(sys.argv[2])
    else:start()
