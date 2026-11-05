"""Exercise real nftables in isolated Docker network namespaces, never the host."""
import json
from pathlib import Path
import secrets
import subprocess
import sys
import tempfile

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
import manage as kit
from client_config import rules


def main():
    tag = 'seminfo-net-' + secrets.token_hex(4)
    names = [tag + suffix for suffix in ('-own', '-other', '-team', '-client')]
    image = 'seminfo/client-builder:22.04'
    network = tag
    # ULA prefix is unique to this throwaway network; no production addresses.
    prefix = 'fd' + secrets.token_hex(1) + ':' + secrets.token_hex(2) + ':1::/64'
    try:
        kit.run(['docker', 'network', 'create', '--ipv6', '--subnet', prefix, network])
        for name in names[:3]:
            kit.run(['docker', 'run', '-d', '--name', name, '--network', network,
                     image, 'python3', '-m', 'http.server', '8000', '--bind', '::'])
        kit.run(['docker', 'run', '-d', '--name', names[3], '--network', network,
                 '--cap-add', 'NET_ADMIN', image, 'sleep', '600'])
        addresses = [kit.inspect(n)['NetworkSettings']['Networks'][network] for n in names]
        own, other, team = [a['IPAddress'] for a in addresses[:3]]
        ipv6 = addresses[0]['GlobalIPv6Address']
        def client(*args, **kw):
            return kit.run(['docker', 'exec', '-i', names[3], *args], **kw)
        def probe(address, expected, port=8000):
            script = '''import socket,sys
try:
    s=socket.create_connection((sys.argv[1],int(sys.argv[3])),timeout=1);s.close();ok=True
except OSError:ok=False
assert ok == (sys.argv[2]=='yes'), (sys.argv[1],ok)
'''
            client('python3', '-c', script, address, 'yes' if expected else 'no', str(port))
        kit.run(['docker','exec','-d',names[0],'python3','-m','http.server','8001','--bind','::'])
        kit.run(['docker','exec','-d',names[3],'python3','-m','http.server','8000','--bind','127.0.0.1'])
        # Prove that the blocked destinations were reachable before filtering.
        for address in (own, other, team, ipv6):
            probe(address, True)
        probe(own, True, 8001)
        def apply(text):
            client('nft', '-f', '-', data=text.encode())
        apply(rules())
        probe('127.0.0.1', True)
        for address in (own, other, team, ipv6):
            probe(address, False)
        for _ in range(2):
            apply(rules(own))
            probe(own, True)
            probe(own, False, 8001)
            for address in (other, team, ipv6):
                probe(address, False)
        # Configuration parser must close an existing allow policy before failing.
        with tempfile.TemporaryDirectory() as tmp:
            folder = Path(tmp)
            config = {'profile': 'si', 'lab': 3, 'server_ip': own,
                      'subnet': '192.0.2.0/24', 'wallpaper': 'lab3-si.png'}
            for name, data in [('lab.json', json.dumps(config)), ('deny.nft', rules()),
                               ('invalid.json', '{invalid')]:
                (folder/name).write_text(data, newline='\n')
            client('mkdir', '-p', '/etc/seminfo', '/usr/local/lib/seminfo')
            for source, target in [(folder/'lab.json', '/etc/seminfo/lab.json'),
                                   (folder/'deny.nft', '/etc/seminfo/deny.nft'),
                                   (folder/'invalid.json', '/tmp/invalid.json'),
                                   (kit.HERE/'client_config.py', '/usr/local/lib/seminfo/client_config.py'),
                                   (kit.HERE/'client/configure-client.py', '/tmp/configure.py')]:
                kit.run(['docker', 'cp', source, names[3]+':'+target])
            try:
                client('python3', '/tmp/configure.py', '/tmp/invalid.json')
            except RuntimeError as exc:
                if 'JSONDecodeError' not in str(exc):
                    raise AssertionError('Parser nao chegou a validar o JSON: '+str(exc)) from exc
            else:
                raise AssertionError('Configuracao invalida aceita')
            probe(own, False)
        kit.write_json(kit.HERE/'reports/network.json', {
            'passed': True, 'checks': ['default deny', 'own BOCA only', 'other BOCA blocked',
                'peer blocked', 'external IPv6 blocked', 'idempotent rules', 'invalid config closes navigation']})
        print('PASSOU: nftables real; BOCA correto, isolamento IPv4/IPv6 e falha fechada.')
    finally:
        for name in reversed(names):
            subprocess.run(['docker', 'rm', '-f', name], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        subprocess.run(['docker', 'network', 'rm', network], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)


if __name__ == '__main__':
    main()
