#!/bin/bash
# Optional rehearsal using a sparse regular file, never a physical block device.
set -euo pipefail
[[ $# -eq 1 && -f $1 ]] || { echo 'Uso no container client-vm: virtual-usb.sh IMAGEM.iso' >&2; exit 2; }
iso=$(readlink -f "$1")
work=$(mktemp -d /work/seminfo-vm.XXXXXX)
image="$work/usb.img"
# Stream bounded reads: copy_file_range used by cp can fail with ENOMEM for
# large Windows bind-mounted ISOs under Docker Desktop. Verify the ISO prefix
# before modifying the disposable file's partition table.
dd if="$iso" of="$image" bs=4M iflag=fullblock conv=sparse status=progress
cmp -n "$(stat -c %s "$iso")" "$iso" "$image"
truncate -s 32G "$image"
sgdisk -e "$image"
sgdisk -n 4:0:0 -c 4:seminfo-home -t 4:8300 "$image"
read -r start end < <(parted -ms "$image" unit s print | awk -F: '$1==4 {gsub("s","",$2);gsub("s","",$3);print $2,$3}')
[[ $start =~ ^[0-9]+$ && $end =~ ^[0-9]+$ && $end -gt $start ]]
# Explicit size prevents mkfs from extending beyond the selected file partition.
mkfs.ext4 -F -b 4096 -L home-rw -E "offset=$((start*512))" "$image" "$(((end-start+1)*512/4096))"
printf '%s\n' "$work" > /work/seminfo-vm-current
echo "VM: $work; monitor Unix $work/monitor.sock"
firmware=()
if [[ ${SEMINFO_VM_UEFI:-0} == 1 ]]; then firmware=(-bios /usr/share/ovmf/OVMF.fd); fi
exec qemu-system-x86_64 -machine accel=tcg -cpu max -smp 4 -m 8192 \
  "${firmware[@]}" \
  -device qemu-xhci -drive "if=none,id=stick,format=raw,file=$image" \
  -device usb-storage,drive=stick,bootindex=1 \
  -nic user,model=e1000 -vga std -display none \
  -serial "file:$work/serial.log" -monitor "unix:$work/monitor.sock,server=on,wait=off" \
  -no-reboot
