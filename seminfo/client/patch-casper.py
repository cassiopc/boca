"""Limit Casper persistence to home-rw on the same USB as the boot medium."""
from pathlib import Path
import sys

root = Path(sys.argv[1])
# Windows bind mounts expose overlay files as 0777. Normalize every copied
# directory (including parents), not just executables, before making the ISO.
directories = ('etc', 'etc/seminfo', 'etc/systemd', 'etc/systemd/system', 'etc/xdg',
    'etc/xdg/autostart', 'etc/dconf', 'etc/dconf/db', 'etc/dconf/db/local.d',
    'etc/dconf/db/local.d/locks', 'usr', 'usr/local', 'usr/local/sbin',
    'usr/local/lib', 'usr/local/lib/seminfo', 'usr/share', 'usr/share/applications', 'usr/share/maratona-background')
files = ('etc/nftables.conf', 'etc/seminfo/deny.nft', 'etc/seminfo/lab.json',
    'etc/systemd/system/seminfo-network.service', 'etc/xdg/autostart/seminfo-boca.desktop',
    'etc/dconf/db/local.d/99-seminfo', 'etc/dconf/db/local.d/locks/99-seminfo',
    'usr/local/lib/seminfo/client_config.py',
    'usr/share/maratona-background/maratona-common-wallpaper.png')
files += ('etc/systemd/system/seminfo-admission.service', 'etc/systemd/system/seminfo-wallpaper.service', 'usr/share/applications/seminfo-boca.desktop',
    'usr/local/lib/seminfo/admission_wire.py', 'usr/share/maratona-background/caramel-claro.png',
    'usr/share/maratona-background/caramel-espresso.png')
import os
os.chown(root, 0, 0)
root.chmod(0o755)
for name in directories + files:
    path = root/name
    os.chown(path, 0, 0)
    path.chmod(0o755 if name in directories else 0o644)
for name in ('seminfo-network', 'seminfo-verify', 'seminfo-configure',
        'seminfo-admission-agent', 'seminfo-admission-window', 'seminfo-wallpaper'):
    path = root/'usr/local/sbin'/name
    os.chown(path, 0, 0)
    path.chmod(0o755)
helpers = root / 'usr/share/initramfs-tools/scripts/casper-helpers'
marker = '# SEMINFO: only the boot USB can provide persistent home'
text = helpers.read_text()
if marker not in text:
    text += r'''
# SEMINFO: only the boot USB can provide persistent home
find_cow_device() {
    [ "$1" = home-rw ] || return 1
    udevadm settle
    boot_device=$(what_is_mounted "$mountpoint")
    boot_sys=$(readlink -f "/sys/class/block/$(basename "$boot_device")")
    [ -f "$boot_sys/partition" ] || return 1
    disk_sys=$(dirname "$boot_sys")
    for partition in "$disk_sys"/*; do
        [ -f "$partition/partition" ] || continue
        candidate="/dev/$(basename "$partition")"
        [ "$(blkid -s LABEL -o value "$candidate")" = home-rw ] || continue
        [ "$(get_fstype "$candidate")" = ext4 ] || continue
        echo "$candidate"
        return 0
    done
    return 1
}
# Never mount snapshots from other disks or create a writable root partition.
find_files() { return 1; }
find_or_create_persistent_partition() { return 0; }
'''
    helpers.write_text(text)
