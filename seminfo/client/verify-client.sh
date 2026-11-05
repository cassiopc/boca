#!/bin/bash
set -euo pipefail
[[ $(id -un) == icpc ]] || { echo 'Verifique a sessao icpc, sem privilegios' >&2; exit 1; }
if sudo -n true 2>/dev/null; then echo 'FALHA: participante tem sudo' >&2; exit 1; fi
[[ -w $HOME ]] || { echo 'FALHA: home do participante nao e gravavel' >&2; exit 1; }
for path in /etc /etc/nftables.conf /etc/systemd/system /usr/local/sbin/seminfo-network; do
  [[ ! -w $path ]] || { echo "FALHA: participante pode alterar $path" >&2; exit 1; }
done
if [[ ${1:-} != --offline && -S /run/dbus/system_bus_socket ]]; then
  if pkexec --disable-internal-agent /usr/bin/id >/dev/null 2>&1; then
    echo 'FALHA: participante pode elevar privilegios com PolicyKit' >&2; exit 1
  fi
fi
work=$(mktemp -d)
trap 'rm -rf -- "$work"' EXIT
cd "$work"
printf '#include <stdio.h>\nint main(void){puts("42");}\n' > Main.c
gcc Main.c -o c
[[ $(./c) == 42 ]]
printf '#include <iostream>\n#include <concepts>\nint main(){std::cout<<42;}\n' > Main.cpp
g++ -std=c++20 Main.cpp -o cpp
[[ $(./cpp) == 42 ]]
[[ $(python3 -c 'print(42)') == 42 ]]
echo 'public class Main { public static void main(String[] args) { System.out.println(42); } }' > Main.java
javac --release 17 Main.java
[[ $(java Main) == 42 ]]
if [[ ${1:-} == --offline ]]; then
  echo 'PASSOU: participante sem sudo; C, C++20, Java 17 e Python 3 compilam/executam offline.'
  exit 0
fi
python3 - <<'PY'
import json,urllib.request
c=json.load(open('/etc/seminfo/lab.json'))
if c.get('admission'):
    opener=urllib.request.build_opener(urllib.request.ProxyHandler({}))
    with opener.open('http://127.0.0.1:17631/status',timeout=3) as response:
        admission=json.load(response)
    assert admission['approved'], 'Solicite/aprove este PC na banca antes de verificar a rede'
    c['server_ip']=admission['server_ip']
url=f'http://{c["server_ip"]}:8000/boca/'
with urllib.request.urlopen(url,timeout=10) as r:
    assert b'BOCA' in r.read()
for url in ('http://1.1.1.1','https://example.com'):
    try:
        urllib.request.urlopen(url,timeout=3)
    except Exception:
        continue
    raise SystemExit('FALHA: acesso externo permitido')
PY
echo 'PASSOU: C, C++20, Java 17, Python 3, BOCA e bloqueio externo.'
echo 'Ainda testar outro BOCA, PCs vizinhos e persistencia apos reboot.'
