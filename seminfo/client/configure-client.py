#!/usr/bin/env python3
"""Apply a local lab JSON as root; keep output blocked if validation fails."""
import ipaddress
import json
import os
from pathlib import Path
import subprocess
import sys
sys.path.insert(0,'/usr/local/lib/seminfo')
from client_config import normalize_wallpaper, rules

restoring=len(sys.argv)==3 and sys.argv[1]=='--restore'
if os.geteuid() != 0 or not (len(sys.argv)==2 or restoring):
    raise SystemExit('Uso como administrador: seminfo-configure /caminho/lab.json')
subprocess.run(['nft','-f','/etc/seminfo/deny.nft'],check=True)
old=json.loads(Path('/etc/seminfo/lab.json').read_text())
source=Path(sys.argv[-1])
if restoring and (source.stat().st_uid!=0 or source.stat().st_mode & 0o022):
    raise SystemExit('Configuracao persistente sem propriedade/permissoes de administrador')
new=json.loads(source.read_text(encoding='utf-8-sig'))
if (new['profile'],new['lab']) != (old['profile'],old['lab']):
    raise SystemExit('Perfil/laboratorio errado; navegacao continua bloqueada')
new['wallpaper']=normalize_wallpaper(new['profile'],new.get('wallpaper'))
ip=ipaddress.IPv4Address(new['server_ip'])
net=ipaddress.IPv4Network(new['subnet'],strict=True)
if ip not in net or net.prefixlen == 0 or ip in (net.network_address,net.broadcast_address):
    raise SystemExit('IP/rede invalido; navegacao continua bloqueada')
text=rules(str(ip),enrollment=old.get('admission',False),approved=not old.get('admission',False))
temp=Path('/etc/seminfo/pending.nft');temp.write_text(text)
subprocess.run(['nft','-c','-f',str(temp)],check=True)
Path('/etc/nftables.conf').write_text(text)
old.update({k:new[k] for k in ('profile','lab','server_ip','subnet','wallpaper')})
Path('/etc/seminfo/lab.json').write_text(json.dumps(old,indent=2))
if not restoring:
    persistent=Path('/home/.seminfo-lab.json')
    # /home itself is root-owned; icpc owns only /home/icpc.
    persistent.write_text(Path('/etc/seminfo/lab.json').read_text())
    persistent.chmod(0o600)
url='about:blank' if old.get('admission') else f'http://{ip}:8000/boca/'
desktop=Path('/etc/xdg/autostart/seminfo-boca.desktop')
if not old.get('admission'):
    desktop.write_text('[Desktop Entry]\nType=Application\nName=BOCA SEMINFO\nExec=epiphany '+url+'\nX-GNOME-Autostart-enabled=true\n')
dconf=Path('/etc/dconf/db/local.d/99-seminfo')
current=dconf.read_text()
import re
current=re.sub(r"homepage-url='[^']*'","homepage-url='"+url+"'",current)
dconf.write_text(current)
subprocess.run(['dconf','update'],check=True)
subprocess.run(['nft','-f','/etc/nftables.conf'],check=True)
print('Configuracao aplicada. Refaca login na sessao icpc e execute seminfo-verify.')
