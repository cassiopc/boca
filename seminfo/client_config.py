"""Generate a lab-specific overlay, containing no BOCA credentials."""
import ipaddress
import json
from pathlib import Path
import shutil

HERE = Path(__file__).resolve().parent
LEGACY_WALLPAPERS = {'si': 'lab3-si.png', 'tii': 'lab4-tii.png'}


def wallpaper_for_profile(profile):
    """Return the artwork assigned to a category; never choose a fallback."""
    if profile == 'tii':
        return 'caramel-claro.png'
    if profile == 'si':
        return 'caramel-espresso.png'
    raise ValueError('Perfil de wallpaper invalido')


def normalize_wallpaper(profile, name):
    """Accept the category's old alias while emitting its current artwork."""
    expected = wallpaper_for_profile(profile)
    if name not in (expected, LEGACY_WALLPAPERS[profile]):
        raise ValueError('Wallpaper nao corresponde ao perfil')
    return expected


def rules(server_ip=None, *, enrollment=False, approved=True, lease_seconds=60):
    allow = ''
    lease = ''
    if server_ip:
        address = ipaddress.IPv4Address(server_ip)
        if address.is_unspecified or address.is_loopback or address.is_multicast:
            raise ValueError('IPv4 da mesa invalido')
        if enrollment:
            # Only the root agent may use the bootstrap channel. Otherwise a
            # participant could type an internet IP and browse its port 8443.
            allow = f'    meta skuid 0 ip daddr {address} tcp dport 8443 accept\n'
            if approved:
                if type(lease_seconds) is not int or not 1 <= lease_seconds <= 60:
                    raise ValueError('Autorizacao entre 1 e 60 segundos')
                # Kernel expiration closes even if the root agent is killed or
                # frozen. No established-output exception may bypass this set.
                lease = f'  set boca_lease {{ type ipv4_addr; flags timeout; elements = {{ {address} timeout {lease_seconds}s }}; }}\n'
                allow += '    ip daddr @boca_lease tcp dport 8000 accept\n'
        else:
            allow = f'    ip daddr {address} tcp dport 8000 accept\n'
    return '''flush ruleset
table inet seminfo {
''' + lease + '''\
  chain input {
    type filter hook input priority 0; policy drop;
    iifname "lo" accept
    ct state established,related accept
    meta nfproto ipv4 udp sport 67 udp dport 68 accept
  }
  chain forward { type filter hook forward priority 0; policy drop; }
  chain output {
    type filter hook output priority 0; policy drop;
    oifname "lo" accept
    meta nfproto ipv4 udp sport 68 udp dport 67 accept
''' + allow + '''  }
}
'''


def generate(cfg, destination, *, allow_incomplete=False):
    from manage import validate, write_json, digest
    from admission_setup import ensure_keys
    cfg = dict(cfg)
    wallpaper = normalize_wallpaper(cfg.get('profile'), cfg.get('wallpaper'))
    # The server's administrative schema retains the historical lab aliases.
    cfg['wallpaper'] = LEGACY_WALLPAPERS[cfg['profile']]
    validate(cfg, network=not allow_incomplete)
    cfg['wallpaper'] = wallpaper
    destination = Path(destination)
    destination.mkdir(parents=True, exist_ok=True)
    overlay = destination / 'overlay'
    for name in ('etc/seminfo', 'etc/systemd/system', 'usr/local/sbin', 'usr/local/lib/seminfo', 'etc/xdg/autostart', 'usr/share/applications', 'usr/share/maratona-background', 'etc/dconf/db/local.d', 'etc/dconf/db/local.d/locks'):
        (overlay / name).mkdir(parents=True, exist_ok=True)
    trust = ensure_keys(cfg['profile'])
    (overlay / 'etc/nftables.conf').write_text(rules(cfg['server_ip'], enrollment=True, approved=False), encoding='utf-8', newline='\n')
    (overlay / 'etc/seminfo/deny.nft').write_text(rules(), encoding='utf-8', newline='\n')
    public = {key: cfg[key] for key in ('profile','lab','server_ip','subnet','wallpaper','teams')}
    public.update(admission=True, admission_fingerprint=trust)
    (overlay / 'etc/seminfo/lab.json').write_text(json.dumps(public, indent=2), encoding='utf-8', newline='\n')
    url = 'about:blank'
    (overlay / 'etc/xdg/autostart/seminfo-boca.desktop').write_text('[Desktop Entry]\nType=Application\nName=Autorizacao BOCA SEMINFO\nExec=/usr/local/sbin/seminfo-admission-window\nX-GNOME-Autostart-enabled=true\n', encoding='utf-8', newline='\n')
    shutil.copy2(overlay/'etc/xdg/autostart/seminfo-boca.desktop',overlay/'usr/share/applications/seminfo-boca.desktop')
    (overlay / 'etc/dconf/db/local.d/99-seminfo').write_text("[org/gnome/desktop/background]\npicture-uri='file:///usr/share/maratona-background/maratona-common-wallpaper.png'\npicture-uri-dark='file:///usr/share/maratona-background/maratona-common-wallpaper.png'\npicture-options='scaled'\n\n[org/gnome/epiphany]\nhomepage-url='" + url + "'\n", encoding='utf-8', newline='\n')
    (overlay / 'etc/dconf/db/local.d/locks/99-seminfo').write_text('/org/gnome/desktop/background/picture-uri\n/org/gnome/desktop/background/picture-uri-dark\n/org/gnome/epiphany/homepage-url\n', encoding='utf-8', newline='\n')
    for name in ('caramel-claro.png', 'caramel-espresso.png'):
        shutil.copy2(HERE / 'banners' / name, overlay / 'usr/share/maratona-background' / name)
    shutil.copy2(HERE / 'banners' / wallpaper, overlay / 'usr/share/maratona-background/maratona-common-wallpaper.png')
    shutil.copy2(HERE / 'client/network.sh', overlay / 'usr/local/sbin/seminfo-network')
    shutil.copy2(HERE / 'client/verify-client.sh', overlay / 'usr/local/sbin/seminfo-verify')
    shutil.copy2(HERE / 'client/configure-client.py', overlay / 'usr/local/sbin/seminfo-configure')
    shutil.copy2(HERE / 'client_config.py', overlay / 'usr/local/lib/seminfo/client_config.py')
    shutil.copy2(HERE / 'client/seminfo-network.service', overlay / 'etc/systemd/system/seminfo-network.service')
    for source, target in (('admission_wire.py','usr/local/lib/seminfo/admission_wire.py'),
            ('client/admission-agent.py','usr/local/sbin/seminfo-admission-agent'),
            ('client/admission-window.py','usr/local/sbin/seminfo-admission-window'),
            ('client/seminfo-admission.service','etc/systemd/system/seminfo-admission.service'),
            ('client/wallpaper.py','usr/local/sbin/seminfo-wallpaper'),
            ('client/seminfo-wallpaper.service','etc/systemd/system/seminfo-wallpaper.service')):
        shutil.copy2(HERE/source, overlay/target)
    manifest = {str(p.relative_to(overlay)).replace('\\','/'): digest(p) for p in sorted(overlay.rglob('*')) if p.is_file()}
    write_json(destination / 'overlay.sha256.json', manifest)
    print(f'Overlay {cfg["profile"]}: {destination} (sem credenciais de prova)')
    return overlay
