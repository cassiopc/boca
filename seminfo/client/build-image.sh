#!/bin/bash
# Run in a Linux host (or the privileged Linux builder container).
# Source ISO is never modified. No physical disks are opened by this script.
set -euo pipefail
if [[ $# -ne 5 ]]; then
  echo 'Uso: build-image.sh BASE.iso SHA256 OVERLAY OUTPUT.iso SENHA-MANUTENCAO.txt' >&2; exit 2
fi
[[ $EUID -eq 0 ]] || { echo 'Builder requer root em Linux' >&2; exit 1; }
iso=$(readlink -f "$1"); expected=$2; overlay=$(readlink -f "$3"); output=$(readlink -m "$4"); passfile=$(readlink -f "$5")
[[ $expected =~ ^[0-9a-f]{64}$ ]] || { echo 'SHA256 oficial obrigatorio' >&2; exit 1; }
test -s "$iso"; test -d "$overlay"; test -s "$passfile"
printf '%s  %s\n' "$expected" "$iso" | sha256sum --check --status
test ! -e "$output" || { echo 'Saida ja existe; escolha outro nome' >&2; exit 1; }
if [[ -n ${SEMINFO_RESUME_WORK:-} ]]; then
  work=$(readlink -f "$SEMINFO_RESUME_WORK")
  [[ $work == /work/seminfo-build.* && -d $work/root && -d $work/iso ]] || { echo 'Diretorio de retomada invalido' >&2; exit 1; }
else
  work=$(mktemp -d /work/seminfo-build.XXXXXX)
  xorriso -osirrox on -indev "$iso" -extract / "$work/iso"
  chmod -R u+w "$work/iso"
  unsquashfs -d "$work/root" "$work/iso/casper/filesystem.squashfs"
fi
cleanup() {
  for point in run dev/pts dev sys proc; do mountpoint -q "$work/root/$point" && umount -l "$work/root/$point" || true; done
  # Preserve work on failure for diagnosis; no blanket recursive deletion.
}
trap cleanup EXIT
mount -t proc proc "$work/root/proc"
mount --rbind /sys "$work/root/sys"; mount --make-rslave "$work/root/sys"
mount --rbind /dev "$work/root/dev"; mount --make-rslave "$work/root/dev"
rm -f "$work/root/etc/resolv.conf"
cp -L /etc/resolv.conf "$work/root/etc/resolv.conf"
printf '#!/bin/sh\nexit 101\n' > "$work/root/usr/sbin/policy-rc.d"
chmod 755 "$work/root/usr/sbin/policy-rc.d"
chroot "$work/root" /bin/bash -e <<'CHROOT'
export DEBIAN_FRONTEND=noninteractive
if [ -f /var/lib/seminfo-packages-ready ]; then exit 0; fi
# Official preinst uses systemctl mask --now. In chroot only the offline mask
# is meaningful; suppress --now temporarily and preserve the real executable.
if ! dpkg-divert --list /usr/bin/systemctl | grep -q .; then
  dpkg-divert --local --rename --add /usr/bin/systemctl
fi
cat > /usr/bin/systemctl <<'SHIM'
#!/bin/bash
args=()
for arg in "$@"; do [[ $arg == --now ]] || args+=("$arg"); done
exec /usr/bin/systemctl.distrib "${args[@]}"
SHIM
chmod 755 /usr/bin/systemctl
if ! dpkg-divert --list /usr/sbin/ufw | grep -q .; then
  dpkg-divert --local --rename --add /usr/sbin/ufw
fi
printf '#!/bin/sh\nexit 0\n' > /usr/sbin/ufw
chmod 755 /usr/sbin/ufw
apt-get update
apt-get -f install -y
apt-get install -y software-properties-common ca-certificates ufw
add-apt-repository -y universe
add-apt-repository -y ppa:icpc-latam/maratona-linux
apt-get update
# The official Maratona conflict package replaces snapd and timesyncd with chrony.
apt-get install -y --purge maratona-desktop gcc g++ python3 openjdk-17-jdk nftables epiphany-browser snapd- systemd-timesyncd-
update-alternatives --set java /usr/lib/jvm/java-17-openjdk-amd64/bin/java
update-alternatives --set javac /usr/lib/jvm/java-17-openjdk-amd64/bin/javac
rm -f /usr/sbin/ufw
dpkg-divert --local --rename --remove /usr/sbin/ufw
rm -f /usr/bin/systemctl
dpkg-divert --local --rename --remove /usr/bin/systemctl
apt-get purge -y ubiquity ubiquity-frontend-gtk || true
systemctl disable ufw.service maratona-firewall.service 2>/dev/null || true
systemctl mask ufw.service maratona-firewall.service
systemctl enable nftables.service
systemctl disable ssh.service 2>/dev/null || true
systemctl mask ssh.service
systemctl mask icpc-clean-homed.service maratona-usuario-icpc.service
groupadd -f users
id icpc >/dev/null 2>&1 || useradd -m -s /bin/bash -g users icpc
gpasswd -d icpc sudo 2>/dev/null || true
gpasswd -d icpc admin 2>/dev/null || true
gpasswd -d icpc docker 2>/dev/null || true
passwd -l root
id seminfo-admin >/dev/null 2>&1 || useradd -m -s /bin/bash -G sudo seminfo-admin
mkdir -p /etc/casper.conf.d /etc/sudoers.d /etc/gdm3
printf '\nUSERNAME=icpc\nUSERFULLNAME="Equipe SEMINFO"\nHOST=seminfo\n' >> /etc/casper.conf
printf 'icpc ALL=(ALL:ALL) !ALL\n' > /etc/sudoers.d/99-seminfo
chmod 440 /etc/sudoers.d/99-seminfo
printf '[daemon]\nAutomaticLoginEnable=true\nAutomaticLogin=icpc\n' > /etc/gdm3/custom.conf
apt-get clean
touch /var/lib/seminfo-packages-ready
CHROOT
# The enrollment window uses the GTK runtime already included by Ubuntu Desktop.
chroot "$work/root" python3 -c 'import gi; gi.require_version("Gtk", "3.0"); from gi.repository import Gtk'
# The only privileged password in the image belongs to maintenance, never BOCA.
hash=$(python3 "$(dirname "$0")/hash-maintenance-password.py" "$passfile")
printf 'seminfo-admin:%s\n' "$hash" | chroot "$work/root" chpasswd -e
unset hash
chroot "$work/root" groupadd -f icpc
chroot "$work/root" usermod -a -G icpc icpc
chroot "$work/root" cp -a /usr/share/maratona-usuario-icpc/editores-config/. /home/icpc/
chroot "$work/root" chown -hR icpc:users /home/icpc
rsync -a "$overlay/" "$work/root/"
chmod 755 "$work/root/usr/local/sbin/seminfo-network" "$work/root/usr/local/sbin/seminfo-verify" "$work/root/usr/local/sbin/seminfo-configure"
rm -f "$work/root/home/icpc/.clean-home"
python3 "$(dirname "$0")/patch-casper.py" "$work/root"
chroot "$work/root" dconf update
chroot "$work/root" systemctl enable seminfo-network.service
chroot "$work/root" systemctl enable seminfo-admission.service seminfo-wallpaper.service
chroot "$work/root" runuser -u icpc -- /usr/local/sbin/seminfo-verify --offline
# Casper's adduser hook may grant sudo after the image was built: remove that
# grant in a later initramfs hook, and keep persistence limited to /home.
install -m 755 "$(dirname "$0")/99-seminfo-casper" "$work/root/usr/share/initramfs-tools/scripts/casper-bottom/99-seminfo"
version=$(find "$work/root/lib/modules" -mindepth 1 -maxdepth 1 -type d -printf '%f\n' | sort -V | tail -1)
[[ -n $version ]] || { echo 'Modulos do kernel ausentes' >&2; exit 1; }
kernel="$work/root/boot/vmlinuz-$version"
initrd="$work/root/boot/initrd.img-$version"
# Ubuntu Desktop keeps these large files outside its compressed root.
test -s "$kernel" || cp "$work/iso/casper/vmlinuz" "$kernel"
if [[ -s $initrd ]]; then mode=-u; else mode=-c; fi
chroot "$work/root" update-initramfs "$mode" -k "$version"
test -s "$kernel"; test -s "$initrd"
cp "$kernel" "$work/iso/casper/vmlinuz"
cp "$initrd" "$work/iso/casper/initrd"
chroot "$work/root" dpkg-query -W --showformat='${Package} ${Version}\n' > "$work/iso/casper/filesystem.manifest"
cp "$work/iso/casper/filesystem.manifest" "${output%.iso}.packages.txt"
rm -f "$work/root/usr/sbin/policy-rc.d"
cleanup
# The selected Ubuntu kernel has CONFIG_SQUASHFS_ZSTD=y. Zstd keeps remastering
# and live decompression practical with the complete offline IDE collection.
grep -q '^CONFIG_SQUASHFS_ZSTD=y' "$work/root/boot/config-$version"
mksquashfs "$work/root" "$work/filesystem.squashfs" -noappend -comp zstd -Xcompression-level 9 -no-progress -processors 4
mv "$work/filesystem.squashfs" "$work/iso/casper/filesystem.squashfs"
du -sx --block-size=1 "$work/root" | cut -f1 > "$work/iso/casper/filesystem.size"
python3 "$(dirname "$0")/patch-boot.py" "$work/iso" "$work/root/etc/seminfo/lab.json"
python3 "$(dirname "$0")/generate-iso-md5.py" "$work/iso" "$iso" --report "$work/md5-exclusions.json"
# Replay the original hybrid boot setup; import only the modified payload.
xorriso -indev "$iso" -outdev "$output" -boot_image any replay -update_r "$work/iso" / -commit -end
(cd "$(dirname "$output")"; sha256sum "$(basename "$output")") > "$output.sha256"
python3 - "$work" "$expected" "${output%.iso}.build.json" <<'PY'
import json,sys
from pathlib import Path
Path(sys.argv[3]).write_text(json.dumps({'work':sys.argv[1], 'base_sha256':sys.argv[2]}))
PY
echo "Imagem criada: $output"
echo "Diretorio de trabalho preservado: $work (remova depois da verificacao)"
