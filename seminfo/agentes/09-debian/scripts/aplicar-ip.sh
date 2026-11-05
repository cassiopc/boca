#!/bin/sh
set -eu

if [ "$#" -ne 2 ]; then
  echo "uso: sh scripts/aplicar-ip.sh IPV4 DIRETORIO_DE_SAIDA" >&2
  exit 1
fi

ip=$1
dest=$2

if [ -z "$ip" ] || [ -z "$dest" ]; then
  echo "argumento vazio" >&2
  exit 1
fi

if [ "$ip" = "IP_DA_MESA" ]; then
  echo "recusado: IP_DA_MESA" >&2
  exit 1
fi

# Octeto 0-255, sem zero a esquerda. Evita teste numerico, que le 08 como octal.
octet_ok() {
  case $1 in
    0|[1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5]) return 0 ;;
    *) return 1 ;;
  esac
}

old_ifs=${IFS-}
set -f
IFS=.
set -- $ip
IFS=$old_ifs
set +f

if [ "$#" -ne 4 ] \
  || ! octet_ok "$1" \
  || ! octet_ok "$2" \
  || ! octet_ok "$3" \
  || ! octet_ok "$4"
then
  echo "IPV4 invalido" >&2
  exit 1
fi

root=$(CDPATH= cd -P -- "$(dirname -- "$0")/.." && pwd -P)
nft_tpl=$root/templates/nftables.conf
pol_tpl=$root/templates/policies.json

for tpl in "$nft_tpl" "$pol_tpl"; do
  if [ ! -f "$tpl" ]; then
    echo "template ausente: $tpl" >&2
    exit 1
  fi
  if ! grep -q -F 'IP_DA_MESA' "$tpl"; then
    echo "token IP_DA_MESA ausente: $tpl" >&2
    exit 1
  fi
done

tpl_dir=$(CDPATH= cd -P -- "$root/templates" && pwd -P)
mkdir -p -- "$dest"
dest_abs=$(CDPATH= cd -P -- "$dest" && pwd -P)

case $dest_abs/ in
  "$tpl_dir"/*)
    echo "diretorio de saida recusado" >&2
    exit 1
    ;;
esac

gravar() {
  src=$1
  dst=$2
  sed "s|IP_DA_MESA|$ip|g" "$src" > "$dst"
  if grep -q -F 'IP_DA_MESA' "$dst"; then
    rm -f -- "$dst"
    echo "token IP_DA_MESA permaneceu na saida" >&2
    exit 1
  fi
  if ! grep -q -F "$ip" "$dst"; then
    rm -f -- "$dst"
    echo "IPV4 ausente na saida" >&2
    exit 1
  fi
}

gravar "$nft_tpl" "$dest_abs/nftables.conf"
gravar "$pol_tpl" "$dest_abs/policies.json"
