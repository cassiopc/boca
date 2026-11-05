#!/bin/bash
# Run as the maintenance administrator. Never grant sudo to icpc.
set -euo pipefail
[[ $EUID -eq 0 ]] || { echo 'Execute como administrador da manutencao' >&2; exit 1; }
case "${1:-apply}" in
  apply)
    install -d -m 700 /var/lib/seminfo
    if [[ ! -e /var/lib/seminfo/before.nft ]]; then
      { echo 'flush ruleset'; nft list ruleset; } > /var/lib/seminfo/before.nft
      chmod 600 /var/lib/seminfo/before.nft
    fi
    # Load a valid fail-closed policy before parsing any lab configuration.
    nft -f /etc/seminfo/deny.nft
    if [[ -f /home/.seminfo-lab.json ]]; then
      /usr/local/sbin/seminfo-configure --restore /home/.seminfo-lab.json
    fi
    python3 - <<'PY'
import ipaddress,json
c=json.load(open('/etc/seminfo/lab.json'))
if c.get('admission'):
    from pathlib import Path
    import sys
    sys.path.insert(0,'/usr/local/lib/seminfo')
    from client_config import rules
    Path('/etc/nftables.conf').write_text(rules(c.get('server_ip'),enrollment=True,approved=False))
    sys.exit(0)
ip=ipaddress.IPv4Address(c['server_ip'])
assert not (ip.is_loopback or ip.is_multicast or ip.is_unspecified)
ipaddress.IPv4Network(c['subnet'],strict=True)
PY
    nft -c -f /etc/nftables.conf
    nft -f /etc/nftables.conf
    ;;
  maintenance)
    # Stop renewal first; otherwise the root admission agent would reinstall
    # its restricted policy during an administrator's maintenance session.
    systemctl stop seminfo-admission.service
    if nft list table inet seminfo >/dev/null 2>&1; then
      nft delete table inet seminfo
    fi
    echo 'Manutencao temporaria. Execute seminfo-network resume antes de liberar a equipe.'
    ;;
  resume)
    "$0" apply
    systemctl start seminfo-admission.service
    ;;
  *) echo 'Uso: seminfo-network apply|maintenance|resume' >&2; exit 2 ;;
esac
