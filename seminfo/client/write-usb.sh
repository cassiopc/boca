#!/bin/bash
# Explicit physical-device opt-in. Never called by build-image.sh.
set -euo pipefail
[[ $# -eq 3 && $1 == --device ]] || { echo 'Uso: sudo write-usb.sh --device /dev/sdX IMAGEM.iso' >&2; exit 2; }
device=$(readlink -f "$2"); image=$(readlink -f "$3")
[[ $EUID -eq 0 && -b $device && -f $image ]] || exit 1
[[ $(lsblk -dn -o TYPE "$device") == disk ]] || { echo 'Informe o disco USB inteiro' >&2; exit 1; }
[[ $(lsblk -dn -o TRAN "$device") == usb ]] || { echo 'Disco nao identificado como USB; recusado' >&2; exit 1; }
[[ $(lsblk -bdn -o SIZE "$device") -ge 30000000000 ]] || { echo 'Use pendrive de pelo menos 32 GB' >&2; exit 1; }
if lsblk -nr -o MOUNTPOINT "$device" | grep -q '[^[:space:]]'; then echo 'Desmonte todas as particoes deste USB' >&2; exit 1; fi
lsblk -d -o NAME,MODEL,SERIAL,SIZE,TRAN "$device"
read -r -p "Para apagar somente este USB, digite o caminho $device: " confirm
[[ $confirm == "$device" ]] || exit 1
(cd "$(dirname "$image")"; sha256sum --check "$(basename "$image").sha256")
dd if="$image" of="$device" bs=8M conv=fsync status=progress
cmp -n "$(stat -c %s "$image")" "$image" "$device"
# The replayed Ubuntu image has an ISO-hybrid partition map. Relocate its
# backup GPT, then allocate only the unused tail for /home persistence.
sgdisk -e "$device"
end=$(parted -ms "$device" unit s print | awk -F: '$1 ~ /^[0-9]+$/ {gsub("s","",$3); if ($3>m) m=$3} END{print m}')
start=$(( ((end+2048)/2048)*2048 ))
parted -s "$device" unit s mkpart seminfo-home ext4 "${start}s" 100%
partprobe "$device"; udevadm settle
part=$(lsblk -nrpo NAME,PARTLABEL "$device" | awk '$2=="seminfo-home"{print $1}')
[[ -n $part && $(lsblk -dn -o PKNAME "$part") == "$(basename "$device")" ]] || { echo 'Particao de persistencia nao confirmada' >&2; exit 1; }
mkfs.ext4 -F -L home-rw "$part"
sync
echo 'USB conferido. Teste boot, regras e persistencia no hardware antes de replicar.'
