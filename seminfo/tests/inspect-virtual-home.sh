#!/bin/bash
# Pause the disposable QEMU guest first. Read-only loop; never mount its FS.
set -euo pipefail
image=$(readlink -f "$1")
[[ -f $image && $image == /work/seminfo-vm.*/usb.img ]] || exit 2
start=$(parted -ms "$image" unit B print | awk -F: '$1==4 {gsub("B","",$2);print $2}')
[[ $start =~ ^[0-9]+$ ]] || exit 2
loop=$(losetup --find --show --read-only --offset "$start" "$image")
trap 'losetup -d "$loop"' EXIT
# Live journal transactions may not yet be checkpointed; this is diagnostic
# only, so ignore metadata checksums without repairing or writing the image.
debugfs -n -R "${2:-ls -l /icpc}" "$loop"
