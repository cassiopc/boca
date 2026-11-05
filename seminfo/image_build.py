"""Download verified Ubuntu media and invoke the Linux remaster builder."""
import hashlib
import json
import os
from pathlib import Path
import urllib.request
from concurrent.futures import ThreadPoolExecutor, as_completed

HERE = Path(__file__).resolve().parent
FILENAME = 'ubuntu-22.04.5-desktop-amd64.iso'
BASE = 'https://releases.ubuntu.com/22.04/'


def download(url, part, expected):
    """Resume verified ranges; interrupted/incomplete ranges are downloaded again."""
    probe = urllib.request.urlopen(urllib.request.Request(url,headers={'Range':'bytes=0-0'}),timeout=30)
    if probe.status != 206:
        probe.close()
        raise RuntimeError('Servidor nao oferece ranges; download paralelo indisponivel')
    total = int(probe.headers['Content-Range'].split('/')[-1]);probe.close()
    progress = part.with_suffix('.ranges.json')
    if progress.exists():
        state=json.loads(progress.read_text())
        if state['sha256'] != expected or state['size'] != total:
            raise ValueError('Metadados do download parcial divergem')
    else:
        prefix=part.stat().st_size if part.exists() else 0
        state={'sha256':expected,'size':total,'prefix':prefix,'completed':[]}
        if prefix > total:raise ValueError('Arquivo parcial maior que a origem')
        with part.open('ab') as f:f.truncate(total)
    chunk=64*1024*1024
    ranges=[(start,min(start+chunk,total)-1) for start in range(state['prefix'],total,chunk)]
    pending=[span for span in ranges if list(span) not in state['completed']]
    progress.write_text(json.dumps(state))
    def fetch(span):
        start,end=span
        for attempt in range(3):
            try:
                request=urllib.request.Request(url,headers={'Range':f'bytes={start}-{end}'})
                with urllib.request.urlopen(request,timeout=90) as response,part.open('r+b') as out:
                    if response.status!=206 or response.headers.get('Content-Range')!=f'bytes {start}-{end}/{total}':
                        raise ValueError('Range HTTP inesperado')
                    out.seek(start);remaining=end-start+1
                    while remaining:
                        data=response.read(min(4*1024*1024,remaining))
                        if not data:raise OSError('Download truncado')
                        out.write(data);remaining-=len(data)
                return span
            except OSError:
                if attempt==2:raise
        return span
    completed=state['prefix']+sum(b-a+1 for a,b in state['completed'])
    with ThreadPoolExecutor(max_workers=8) as pool:
        for future in as_completed([pool.submit(fetch,span) for span in pending]):
            span=future.result();state['completed'].append(list(span));completed+=span[1]-span[0]+1
            temporary=progress.with_suffix('.tmp');temporary.write_text(json.dumps(state));temporary.replace(progress)
            print(f'Ubuntu: {completed*100//total}% ({completed//1048576} MiB)',flush=True)


def fetch_ubuntu():
    from manage import DIST, digest, write_json
    destination = DIST / 'ubuntu'
    destination.mkdir(parents=True, exist_ok=True)
    sums = urllib.request.urlopen(BASE + 'SHA256SUMS', timeout=30).read().decode('ascii')
    expected = next(line.split()[0] for line in sums.splitlines() if line.endswith(FILENAME))
    target = destination / FILENAME
    if target.exists() and digest(target) == expected:
        print('Ubuntu oficial ja baixado e checksum conferido.')
    else:
        part = target.with_suffix('.iso.part')
        download(BASE+FILENAME,part,expected)
        if digest(part) != expected:
            raise ValueError('Download do Ubuntu corrompido; arquivo parcial preservado para diagnostico')
        part.replace(target)
    write_json(destination / 'source.json', {'url': BASE+FILENAME,'sha256':expected,'checksum_source':BASE+'SHA256SUMS'})
    return target, expected


def build_client(profile, *, allow_incomplete=False):
    from manage import DIST, ROOT, LOCAL, run, initialize, config, config_hash, client_source_hash, write_json, digest
    from client_config import generate, wallpaper_for_profile
    initialize(profile)
    c=config(profile)
    destination=DIST / 'clients' / profile
    generate(c,destination,allow_incomplete=allow_incomplete)
    iso,expected=fetch_ubuntu()
    output=destination / f'ml-seminfo-lab{c["lab"]}-{profile}.iso'
    if output.exists():
        raise ValueError('Imagem de saida ja existe. Mova-a para arquivo antes de reconstruir.')
    run(['docker','build','-t','seminfo/client-builder:22.04','-f',HERE/'client/builder.Dockerfile',HERE/'client'],capture=False,timeout=1800)
    run(['docker','volume','create','seminfo-client-build'])
    work = os.environ.get('SEMINFO_RESUME_WORK')
    cached = DIST / 'client-work.json'
    if not work and cached.exists():
        previous = json.loads(cached.read_text())
        if previous['base_sha256'] == expected:
            work = previous['work']
    resume = ['--env', 'SEMINFO_RESUME_WORK='+work] if work else []
    run(['docker','run','--rm','--privileged','--name',f'seminfo-build-{profile}',*resume,
         '--mount',f'type=bind,source={ROOT},target=/kit,readonly',
         '--mount',f'type=bind,source={DIST},target=/out',
         '--mount','type=volume,source=seminfo-client-build,target=/work',
         'seminfo/client-builder:22.04','bash','/kit/seminfo/client/build-image.sh',
         '/out/ubuntu/'+iso.name,expected,f'/out/clients/{profile}/overlay',
         f'/out/clients/{profile}/{output.name}',f'/kit/seminfo/local/{profile}/maintenance-password.txt'],capture=False,timeout=7200)
    write_json(cached,json.loads(output.with_suffix('.build.json').read_text()))
    write_json(destination/'image.json',{'profile':profile,'base_sha256':expected,'sha256':digest(output),
               'config_sha256':config_hash(c),'client_source_sha256':client_source_hash(),
               'package_manifest_sha256':digest(output.with_suffix('.packages.txt')),
               'network_configured':bool(c['server_ip'] and c['subnet']),'hardware_boot_verified':False,
               'wallpaper_approved':True,'wallpaper_mode':'fixed_by_profile',
               'wallpaper':wallpaper_for_profile(profile),
               'wallpaper_sha256':{name:digest(HERE/'banners'/name) for name in ('caramel-claro.png','caramel-espresso.png')},
               'admission_required':True,'admission_fingerprint':json.loads((destination/'overlay/etc/seminfo/lab.json').read_text())['admission_fingerprint'],
               'usb_written':False})
    print(f'Imagem gerada: {output}. Boot/persistencia ainda exigem ensaio presencial.')
