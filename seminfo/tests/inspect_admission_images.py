"""Read-only inspection of actual client ISOs, including externally remastered ISOs.

Run in the Linux client-builder with /kit read-only and /reports writable.
Static boot/persistence checks do not replace a boot and reboot on a real USB.
"""
import argparse
import hashlib
import hmac
import json
import os
from pathlib import Path
import re
import shutil
import ssl
import subprocess
import tempfile
import warnings

HERE = Path(__file__).resolve().parents[1]
LABS = {'si': 3, 'tii': 4}
WALLPAPERS = {'si': 'caramel-espresso.png', 'tii': 'caramel-claro.png'}
# Public package test/example keys, verified byte for byte against upstream
# release files and the installed packages' dpkg md5sums. An entire directory
# or package is never exempt: both the exact path and SHA256 must match.
PUBLIC_KEY_FIXTURE_GROUPS = (
    ('usr/lib/python3/dist-packages/future/backports/test/', 'python3-future',
     'https://raw.githubusercontent.com/PythonCharmers/python-future/v0.18.2/src/future/backports/test/', {
         'ssl_key.pem': '54a194f91dd461a6695535e5edc856978bc46040dea1bebd49fbd14d0f1aabfe',
         'ssl_key.passwd.pem': '23f587e2c070f55b3d67e06f9ae5d8d1ac3cb71f1abefeab9b950be12fe93f4d',
         'keycert.pem': 'c4f5e2e6274f71055bae183106a1764cd1a6eac499d9a2d5544b7a0d6ce994bf',
         'keycert.passwd.pem': '6417e754ba5b06d00e7ffda009d890fb2ac11e646c37349ff150b75294192238',
         'badkey.pem': '81a04af69c7f806ec39ab2cac5f0fa7fa8beb6802601105355fb3e606151405d',
         'badcert.pem': '262a107916641c7f211ac5898c0177535cd0bdc5aa872cc6e883842694d8f521',
         'keycert2.pem': '0c1e3a144018bfc056c1027ee51cc2ebde85c4f37b08e37e42c8be478a682607',
     }),
    ('usr/share/doc/openvpn/examples/sample-keys/', 'openvpn',
     'https://raw.githubusercontent.com/OpenVPN/openvpn/v2.5.9/sample/sample-keys/', {
         'server-ec.key': '13bd26195ad751522771b5d1b46278938347532162a3b1581c7ec694c7e2e256',
         'client-ec.key': 'bfab4297f53c732a6959110b05953a31ba2ee682615d95399af6921334b6e16f',
         'client.key': '5f360847b8306c074e4ffd030aa36ad1de431970f83394f92d2e60903ce2806d',
         'client-pass.key': '3f2b4fe18f380d5e2c9590f0535288ff69a556c40d2a8fdebc56e65c72e290d2',
         'ca.key': '9cde07280573666d9ffe7d29f64561643f1d1086dd425503968a1485c5334e12',
         'server.key': 'c7abdcabff545dda4480fafd5ec96ee70629aeed4de2df2e890277678e5c17a0',
     }),
    ('usr/share/doc/libnet-ssleay-perl/examples/', 'libnet-ssleay-perl',
     'https://raw.githubusercontent.com/radiator-software/p5-net-ssleay/1.92/examples/', {
         'server_key.pem': '0e13f274a94db44c94ced16b2332fdbc9ff770eae88bdec9c0347695c7c99ee2',
     }),
)
PUBLIC_KEY_FIXTURES = {
    prefix + name: {'sha256': digest, 'package': package, 'source_url': source + name}
    for prefix, package, source, files in PUBLIC_KEY_FIXTURE_GROUPS for name, digest in files.items()
}


def require(condition, message):
    if not condition:
        raise ValueError(message)


def command(args):
    return subprocess.check_output(args, stderr=subprocess.PIPE)


def extract_iso(iso, source, destination):
    command(['xorriso', '-osirrox', 'on', '-indev', str(iso),
             '-extract', source, str(destination)])


def sha256_file(path):
    digest = hashlib.sha256()
    advise = getattr(os, 'posix_fadvise', None)
    discard_hint = getattr(os, 'POSIX_FADV_DONTNEED', None)
    offset = 0
    with path.open('rb') as stream:
        while data := stream.read(8 * 1024 * 1024):
            digest.update(data)
            # Large ISOs need not remain in the Linux page cache after hashing;
            # retaining them can make the subsequent Windows export thrash.
            if advise is not None and discard_hint is not None:
                try:
                    advise(stream.fileno(), offset, len(data), discard_hint)
                except OSError:
                    # Advisory cache management is optional on other filesystems.
                    advise = None
            offset += len(data)
    return digest.hexdigest()


def squashfs_entries(fs):
    # Preserve spaces in filenames; do not rely on the column count of ls.
    listing = command(['unsquashfs', '-ll', str(fs)]).decode()
    entries = {}
    for line in listing.splitlines():
        match = re.match(r'^(\S+)\s+(\S+)\s+.*?\s+squashfs-root/(.+)$', line)
        if match:
            mode, owner, name = match.groups()
            entries[name.split(' -> ', 1)[0]] = (mode, owner)
    require(entries, 'Nao foi possivel ler a listagem squashfs')
    return entries


def inspect_accounts(read, entries, private, limitations, reference=None):
    passwd = {row.split(':')[0]: row.split(':') for row in read('etc/passwd').decode().splitlines()}
    shadow = {row.split(':')[0]: row.split(':') for row in read('etc/shadow').decode().splitlines()}
    groups = [row.split(':') for row in read('etc/group').decode().splitlines()]
    require('icpc' in passwd and int(passwd['icpc'][2]) != 0, 'Conta icpc ausente ou privilegiada')
    memberships = {g[0] for g in groups if g[2] == passwd['icpc'][3] or 'icpc' in g[3].split(',')}
    require(not memberships.intersection({'sudo', 'admin', 'wheel', 'docker', 'lxd', 'root'}),
            'Conta icpc pertence a grupo privilegiado')
    require(shadow.get('root', ['', ''])[1].startswith(('!', '*')), 'Senha de root nao esta bloqueada')
    require(read('etc/sudoers.d/99-seminfo').decode().strip() == 'icpc ALL=(ALL:ALL) !ALL',
            'Regra de bloqueio sudo de icpc ausente')
    sudo_paths = ['etc/sudoers'] + [p for p, (mode, _) in entries.items()
                                   if p.startswith('etc/sudoers.d/') and mode.startswith('-')]
    subjects = {'icpc', 'ALL'} | {'%' + group for group in memberships}
    for path in sudo_paths:
        for line in read(path).decode(errors='replace').splitlines():
            text = line.split('#', 1)[0].strip()
            if not text or text.startswith(('Defaults', '@', 'User_Alias', 'Runas_Alias', 'Host_Alias', 'Cmnd_Alias')):
                continue
            if set(text.split(None, 1)[0].split(',')) & subjects:
                require(text == 'icpc ALL=(ALL:ALL) !ALL', 'Possivel concessao sudo para icpc em ' + path)
    require('seminfo-admin' in passwd and 'seminfo-admin' in shadow, 'Conta de manutencao ausente')
    if reference is not None:
        # The reference contains a digest of the original password hash, never
        # the password itself. Match only shadow's password field (column 2),
        # independently of password-age timestamps.
        stored_digest = hashlib.sha256(shadow['seminfo-admin'][1].encode()).hexdigest()
        require(hmac.compare_digest(stored_digest, reference['maintenance_shadow_sha256']),
                'Hash da senha de manutencao mudou em relacao a ISO de origem')
        return None
    password_file = private / 'maintenance-password.txt'
    maintenance_verified = False
    if password_file.is_file():
        with warnings.catch_warnings():
            warnings.simplefilter('ignore', DeprecationWarning)
            try:
                import crypt
            except ImportError:
                crypt = None
        if crypt is None:
            limitations.append('Modulo crypt indisponivel; senha de manutencao nao comparada.')
        else:
            password = password_file.read_text().rstrip('\r\n')
            stored = shadow['seminfo-admin'][1]
            calculated = crypt.crypt(password, stored)
            require(bool(calculated) and hmac.compare_digest(calculated, stored),
                    'Senha de manutencao da ISO difere da identidade local')
            maintenance_verified = True
    else:
        limitations.append('maintenance-password.txt local ausente; senha de manutencao nao comparada.')
    return maintenance_verified


def inspect_secrets(read, entries):
    forbidden = {'admission-key.pem', 'credentials.json', 'maintenance-password.txt',
                 'admission-control.txt', 'admission-score.txt', 'admission-desk.json', 'projector-desk.json'}
    exceptions = []
    package_manifests = {}
    for path, (mode, _) in entries.items():
        require(Path(path).name not in forbidden, 'Artefato privado encontrado na ISO: ' + path)
        require(path not in {'home/.seminfo-lab.json', 'home/.seminfo-device.json'},
                'Estado persistente individual nao pode ser clonado na ISO: ' + path)
        if not mode.startswith('-'):
            continue
        # These locations contain the client configuration; also inspect every
        # PEM/key file, wherever a package may have placed it.
        if path.startswith(('etc/seminfo/', 'root/', 'home/')) or Path(path).suffix.lower() in {'.pem', '.key'}:
            data = read(path)
            if re.search(rb'-----BEGIN (?:[A-Z0-9]+ )*PRIVATE KEY-----', data):
                fixture = PUBLIC_KEY_FIXTURES.get(path)
                require(fixture is not None and hashlib.sha256(data).hexdigest() == fixture['sha256'],
                        'Chave privada nao reconhecida encontrada na ISO: ' + path)
                package = fixture['package']
                if package not in package_manifests:
                    metadata = [name for name in entries if name in {
                        'var/lib/dpkg/info/' + package + '.md5sums',
                        'var/lib/dpkg/info/' + package + ':amd64.md5sums'}]
                    require(len(metadata) == 1, 'Manifesto dpkg ausente/ambiguo para fixture: ' + package)
                    package_manifests[package] = {
                        line.split(maxsplit=1)[1].lstrip('/'): line.split(maxsplit=1)[0]
                        for line in read(metadata[0]).decode().splitlines() if len(line.split(maxsplit=1)) == 2}
                require(package_manifests[package].get(path) == hashlib.md5(data).hexdigest(),
                        'Fixture publica difere do manifesto dpkg: ' + path)
                exceptions.append({'path': path, **fixture, 'dpkg_md5_matches': True})
    return exceptions


def inspect_catalog(iso, work, boot_report):
    match = re.search(r'^\s*El Torito cat path\s*:\s*(\S+)\s*$', boot_report, flags=re.M)
    # Some Ubuntu ISOs keep the boot catalog hidden, accessible only by LBA.
    # Such a catalog has no filesystem path and cannot occur in md5sum.txt.
    catalog_path = match.group(1) if match else None
    require(catalog_path is None or catalog_path.startswith('/'), 'Caminho do catalogo El Torito invalido')
    checksums = work / 'md5sum.txt'
    extract_iso(iso, '/md5sum.txt', checksums)
    manifest = {}
    for line in checksums.read_text().splitlines():
        entry = re.match(r'^([0-9a-fA-F]{32}) [ *](.+)$', line)
        if entry:
            name = entry.group(2)
            name = name[2:] if name.startswith('./') else name.lstrip('/')
            manifest.setdefault(name, []).append(entry.group(1).lower())

    def verify(path, destination):
        matches = manifest.get(path.lstrip('/'), [])
        if not matches:
            return 'excluded'
        extract_iso(iso, path, destination)
        actual = hashlib.md5(destination.read_bytes()).hexdigest()
        require(all(expected == actual for expected in matches),
                'MD5 de arquivo El Torito diverge da ISO final: ' + path)
        return 'matches'

    # A catalog is rewritten by xorriso during replay. Excluding it from
    # md5sum.txt is legitimate; retaining an obsolete checksum is not.
    catalog_status = verify(catalog_path, work / 'boot-catalog') if catalog_path else 'hidden_not_in_tree'
    images = {}
    for number, path in re.findall(r'^\s*El Torito img path\s*:\s*(\d+)\s+(\S+)\s*$', boot_report, flags=re.M):
        require(path.startswith('/'), 'Caminho da imagem El Torito invalido')
        images[path] = verify(path, work / ('boot-image-' + number))
    return catalog_path, catalog_status, images


def inspect_md5_manifest(tree):
    manifest = tree / 'md5sum.txt'
    require(manifest.is_file(), 'Manifesto md5sum.txt ausente')
    names = set()
    for line in manifest.read_text().splitlines():
        match = re.match(r'^[0-9a-fA-F]{32} [ *](.+)$', line)
        if match:
            name = match.group(1)
            names.add(name[2:] if name.startswith('./') else name)
    require({'casper/filesystem.squashfs', 'casper/vmlinuz', 'casper/initrd'} <= names,
            'Manifesto MD5 nao cobre squashfs/kernel/initramfs')
    result = subprocess.run(['md5sum', '--quiet', '--check', 'md5sum.txt'], cwd=tree,
                            stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    require(result.returncode == 0, 'Manifesto MD5 da ISO final diverge: ' +
            (result.stdout + result.stderr).decode(errors='replace')[:2000].strip())


def inspect_boot(iso, work, read, limitations):
    report = subprocess.run(['xorriso', '-indev', str(iso), '-report_el_torito', 'plain',
                             '-report_system_area', 'plain'], stdout=subprocess.PIPE,
                            stderr=subprocess.PIPE, check=True)
    boot = (report.stdout + report.stderr).decode(errors='replace')
    require(re.search(r'El Torito boot img\s*:\s*\d+\s+BIOS\s+y', boot), 'Boot BIOS El Torito ausente')
    require(re.search(r'El Torito boot img\s*:\s*\d+\s+UEFI\s+y', boot), 'Boot UEFI El Torito ausente')
    require('MBR' in boot and 'GPT' in boot, 'Mapa hibrido MBR/GPT ausente')
    catalog_path, catalog_checksum, image_checksums = inspect_catalog(iso, work, boot)
    grub = work / 'grub'
    extract_iso(iso, '/boot/grub', grub)
    kernel_lines = []
    for path in grub.rglob('*.cfg'):
        kernel_lines.extend(re.findall(r'^\s*(?:linux|linuxefi)\s+/casper/vmlinuz\s+([^\n]+)',
                                       path.read_text(errors='replace'), flags=re.M))
    require(kernel_lines, 'Nenhuma entrada de boot Casper encontrada')
    for line in kernel_lines:
        args = line.split()
        require({'boot=casper', 'username=icpc', 'persistent', 'noprompt', '---'} <= set(args),
                'Entrada de boot sem isolamento/persistencia esperados')
        require(not any('ubiquity' in arg or arg == 'oem-config/enable=true' for arg in args),
                'Entrada de instalacao Ubuntu ainda habilitada')
    helpers = read('usr/share/initramfs-tools/scripts/casper-helpers').decode()
    for snippet in ('# SEMINFO: only the boot USB can provide persistent home',
                    '[ "$1" = home-rw ] || return 1', 'disk_sys=$(dirname "$boot_sys")',
                    '[ "$(get_fstype "$candidate")" = ext4 ] || continue',
                    'find_files() { return 1; }', 'find_or_create_persistent_partition() { return 0; }'):
        require(snippet in helpers, 'Politica de persistencia Casper incompleta')
    hook_path = 'usr/share/initramfs-tools/scripts/casper-bottom/99-seminfo'
    hook = read(hook_path)
    require(hook == (HERE / 'client/99-seminfo-casper').read_bytes(), 'Hook de boot difere do codigo atual')
    initrd_verified = False
    if shutil.which('unmkinitramfs'):
        initrd = work / 'initrd'
        extract_iso(iso, '/casper/initrd', initrd)
        unpacked = work / 'initrd-content'
        command(['unmkinitramfs', str(initrd), str(unpacked)])
        # unmkinitramfs places the compressed main archive either directly in
        # the destination or in main/, depending on the initrd format.
        pairs = [(root / 'scripts/casper-bottom/99-seminfo', root / 'scripts/casper-helpers')
                 for root in (unpacked, unpacked / 'main')]
        require(any(h.is_file() and c.is_file() and h.stat().st_mode & 0o777 == 0o755
                    and h.read_bytes() == hook and c.read_text() == helpers
                    for h, c in pairs), 'Initramfs final nao contem os hooks Casper atuais')
        initrd_verified = True
    else:
        limitations.append('unmkinitramfs indisponivel; politica Casper conferida na squashfs, mas nao no initramfs final.')
    limitations.append('Boot BIOS/UEFI e persistencia foram inspecionados estaticamente; falta ensaio de boot/reboot em USB real.')
    return initrd_verified, catalog_path, catalog_checksum, image_checksums


def load_reference(path, profile):
    reference = json.loads(path.read_text(encoding='utf-8-sig'))
    require(isinstance(reference, dict) and isinstance(reference.get('config'), dict),
            'Referencia deve conter config extraida da ISO de origem')
    config = reference['config']
    require(config.get('profile') == profile and config.get('lab') == LABS[profile]
            and config.get('admission') is True, 'Perfil da referencia incorreto')
    for name in ('source_sha256', 'maintenance_shadow_sha256'):
        require(isinstance(reference.get(name), str) and
                re.fullmatch(r'[0-9a-f]{64}', reference[name]) is not None,
                'Digest de referencia ausente/invalido: ' + name)
    require(isinstance(config.get('admission_fingerprint'), str) and
            re.fullmatch(r'[0-9a-f]{64}', config['admission_fingerprint']) is not None,
            'Pin de referencia ausente/invalido')
    require(all(key in config for key in ('server_ip', 'subnet')),
            'Referencia deve informar IP/rede, inclusive null quando nao configurados')
    return reference


def inspect(profile, iso, reference_image_json=None):
    original_stat = iso.stat()
    lab = LABS[profile]
    private = HERE / 'local' / profile
    reference = load_reference(reference_image_json, profile) if reference_image_json else None
    local = reference['config'] if reference else json.loads((private / 'lab.json').read_text(encoding='utf-8-sig'))
    pin = local['admission_fingerprint'] if reference else hashlib.sha256(
        ssl.PEM_cert_to_DER_cert((private / 'admission-cert.pem').read_text())).hexdigest()
    limitations = []
    with tempfile.TemporaryDirectory(prefix='seminfo-inspect-') as tmp:
        work = Path(tmp)
        tree = work / 'iso'
        extract_iso(iso, '/', tree)
        fs = tree / 'casper/filesystem.squashfs'

        def read(name):
            return command(['unsquashfs', '-cat', str(fs), name])

        config = json.loads(read('etc/seminfo/lab.json'))
        require(config.get('profile') == profile and config.get('lab') == lab and config.get('admission') is True,
                'Perfil/laboratorio/admissao incorreto')
        require(config.get('admission_fingerprint') == pin, 'Certificado da ISO difere da referencia de identidade')
        require(all(config.get(key) == local[key] for key in ('server_ip', 'subnet')),
                'IP/rede da ISO difere da referencia de identidade')
        if reference:
            require({key: value for key, value in config.items() if key != 'wallpaper'} ==
                    {key: value for key, value in local.items() if key != 'wallpaper'},
                    'Configuracao original mudou alem do wallpaper')
        bootstrap = read('etc/nftables.conf').decode()
        require('tcp dport 8000' not in bootstrap, 'BOCA liberado antes da aprovacao')
        import sys
        sys.path.insert(0, str(HERE))
        from client_config import rules
        require(bootstrap == rules(local['server_ip'], enrollment=True, approved=False), 'Firewall inicial difere do modelo seguro atual')
        require(read('etc/seminfo/deny.nft').decode() == rules(), 'Politica de falha fechada incorreta')
        for source, target in [('client/admission-agent.py', 'usr/local/sbin/seminfo-admission-agent'),
                               ('client/admission-window.py', 'usr/local/sbin/seminfo-admission-window'),
                               ('client/network.sh', 'usr/local/sbin/seminfo-network'),
                               ('client/configure-client.py', 'usr/local/sbin/seminfo-configure'),
                               ('client/verify-client.sh', 'usr/local/sbin/seminfo-verify'),
                               ('client/wallpaper.py', 'usr/local/sbin/seminfo-wallpaper'),
                               ('client_config.py', 'usr/local/lib/seminfo/client_config.py'),
                               ('admission_wire.py', 'usr/local/lib/seminfo/admission_wire.py'),
                               ('client/seminfo-network.service', 'etc/systemd/system/seminfo-network.service'),
                               ('client/seminfo-admission.service', 'etc/systemd/system/seminfo-admission.service'),
                               ('client/seminfo-wallpaper.service', 'etc/systemd/system/seminfo-wallpaper.service')]:
            require(read(target) == (HERE / source).read_bytes(), 'Cliente desatualizado: ' + target)
        assets = {}
        selected_wallpaper = WALLPAPERS[profile]
        require(config.get('wallpaper') == selected_wallpaper,
                'Wallpaper configurado nao corresponde ao perfil: ' + selected_wallpaper)
        for name in ('caramel-claro.png', 'caramel-espresso.png'):
            actual = read('usr/share/maratona-background/' + name)
            require(actual == (HERE / 'banners' / name).read_bytes(), 'Arte alterada: ' + name)
            assets[name] = hashlib.sha256(actual).hexdigest()
        require(read('usr/share/maratona-background/maratona-common-wallpaper.png') ==
                (HERE / 'banners' / selected_wallpaper).read_bytes(),
                'Alias do wallpaper nao tem os mesmos bytes da arte selecionada')
        dconf = read('etc/dconf/db/local.d/99-seminfo').decode()
        alias_uri = 'file:///usr/share/maratona-background/maratona-common-wallpaper.png'
        for key in ('picture-uri', 'picture-uri-dark'):
            require(re.search(r'^' + re.escape(key) + '=' + re.escape(repr(alias_uri)) + r'$',
                              dconf, flags=re.M) is not None,
                    'Preferencia GNOME nao usa o alias fixo: ' + key)
        entries = squashfs_entries(fs)
        for name in ('seminfo-admission-agent', 'seminfo-admission-window', 'seminfo-network',
                     'seminfo-configure', 'seminfo-verify', 'seminfo-wallpaper'):
            require(entries.get('usr/local/sbin/' + name) == ('-rwxr-xr-x', 'root/root'),
                    'Executavel sem propriedade/permissoes root 0755: ' + name)
        for path in ('etc', 'etc/seminfo', 'etc/systemd/system', 'usr/local/sbin', 'usr/local/lib/seminfo'):
            require(entries.get(path) == ('drwxr-xr-x', 'root/root'), 'Diretorio do cliente sem protecao: ' + path)
        public_fixtures = inspect_secrets(read, entries)
        maintenance_verified = inspect_accounts(read, entries, private, limitations, reference)
        initrd_verified, catalog_path, catalog_checksum, image_checksums = inspect_boot(iso, work, read, limitations)
        inspect_md5_manifest(tree)
        iso_sha256 = sha256_file(iso)
        final_stat = iso.stat()
        require((original_stat.st_ino, original_stat.st_size, original_stat.st_mtime_ns) ==
                (final_stat.st_ino, final_stat.st_size, final_stat.st_mtime_ns),
                'ISO alterada durante a inspecao; execute novamente sobre arquivo estavel')
        return {'passed': True, 'profile': profile, 'lab': lab, 'iso': str(iso), 'sha256': iso_sha256,
                'pin_matches_desk': None if reference else True,
                'network_matches_desk': None if reference else True, 'bootstrap_closed': True,
                'identity_reference': {'type': 'source_iso' if reference else 'local_desk',
                                       'source_sha256': reference['source_sha256'] if reference else None},
                'pin_matches_reference': True, 'network_matches_reference': True,
                'original_config_preserved_except_wallpaper': True if reference else None,
                'executables_root_755': True, 'runtime_matches_source': True, 'wallpapers': assets,
                'fixed_wallpaper': selected_wallpaper, 'wallpaper_alias_matches_selected': True,
                'gnome_light_dark_use_fixed_alias': True,
                'no_desk_private_artifacts': True, 'no_unrecognized_private_keys': True,
                'public_private_key_fixtures': public_fixtures,
                'private_key_scan_scope': ['*.pem', '*.key', 'etc/seminfo/', 'root/', 'home/'],
                'icpc_unprivileged': True, 'root_password_locked': True,
                'maintenance_password_matches': maintenance_verified,
                'maintenance_password_hash_preserved': True if reference else None,
                'hybrid_bios_uefi_structure': True, 'persistence_static_policy': True,
                'boot_catalog_path': catalog_path, 'boot_catalog_md5': catalog_checksum,
                'boot_images_md5': image_checksums, 'full_md5_manifest_verified': True,
                'initramfs_hooks_verified': initrd_verified, 'physical_boot_and_persistence_verified': False,
                'limitations': limitations}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--profile', choices=LABS, help='Inspecionar apenas este perfil (padrao: si e tii)')
    parser.add_argument('--iso', type=Path, help='ISO externa; exige --profile')
    parser.add_argument('--reference-image-json', type=Path,
                        help='Identidade extraida da ISO de origem: source_sha256, config e '
                             'maintenance_shadow_sha256 (SHA256 do campo de senha shadow); exige --profile')
    parser.add_argument('--report', type=Path, default=Path('/reports/iso-admission-inspection.json'))
    args = parser.parse_args()
    if args.iso and not args.profile:
        parser.error('--iso exige --profile')
    if args.reference_image_json and not args.profile:
        parser.error('--reference-image-json exige --profile')
    profiles = [args.profile] if args.profile else list(LABS)
    results = {}
    args.report.parent.mkdir(parents=True, exist_ok=True)
    for profile in profiles:
        iso = args.iso or HERE / 'dist/clients' / profile / f'ml-seminfo-lab{LABS[profile]}-{profile}.iso'
        try:
            require(iso.is_file(), 'ISO nao encontrada: ' + str(iso))
            results[profile] = inspect(profile, iso.resolve(), args.reference_image_json)
        except Exception as exc:
            # A failed recheck must replace any earlier success report.
            results[profile] = {'passed': False, 'profile': profile, 'iso': str(iso), 'error': str(exc)}
            args.report.write_text(json.dumps(results, indent=2), encoding='utf-8')
            raise
    args.report.write_text(json.dumps(results, indent=2), encoding='utf-8')
    print('PASSOU: inspecao das ISOs ' + ', '.join(profiles) + '. Relatorio: ' + str(args.report))
    for profile, result in results.items():
        for limitation in result['limitations']:
            print(profile + ': ' + limitation)


if __name__ == '__main__':
    main()
