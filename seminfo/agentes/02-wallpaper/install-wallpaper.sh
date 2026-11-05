#!/bin/bash
# Backdrop do xfdesktop na Debian Live 13.7 XFCE já bootada.
# Repetir o comando não duplica arquivo nem chave do xfconf.
if [[ -z "${BASH_VERSION:-}" ]]; then
    echo "use bash: sudo bash $0 lab3|lab4" >&2
    exit 1
fi
set -euo pipefail

DEST_DIR=/usr/share/backgrounds/seminfo
CONF=/etc/seminfo/wallpaper.conf
HELPER=/usr/local/sbin/seminfo-wallpaper.sh
AUTOSTART=/etc/xdg/autostart/seminfo-wallpaper.desktop
CHANNEL=xfce4-desktop
IMAGE_STYLE=5

usage() {
    echo "Uso: sudo bash $0 lab3|lab4" >&2
    echo "     $0 --apply" >&2
    echo "lab3 = Lab 3, Sistemas de Informação (lab3-si.png)" >&2
    echo "lab4 = Lab 4, Informática para a Internet (lab4-tii.png)" >&2
    echo "O PNG é procurado ao lado deste script e em /opt/seminfo/banners/." >&2
    exit 2
}

png_name_for_lab() {
    case "$1" in
        lab3) printf '%s\n' lab3-si.png ;;
        lab4) printf '%s\n' lab4-tii.png ;;
        *) return 1 ;;
    esac
}

lab_label() {
    case "$1" in
        lab3) printf '%s\n' "Lab 3, Sistemas de Informação" ;;
        lab4) printf '%s\n' "Lab 4, Informática para a Internet" ;;
        *) return 1 ;;
    esac
}

is_png() {
    local f="$1" magic
    [[ -f "$f" ]] || return 1
    magic=$(dd if="$f" bs=1 count=8 2>/dev/null | od -An -tx1 | tr -d ' \n')
    [[ "$magic" == "89504e470d0a1a0a" ]]
}

find_png() {
    local name="$1" dir cand
    dir=$(cd "$(dirname "$(readlink -f "$0")")" && pwd)
    for cand in "$dir/$name" "/opt/seminfo/banners/$name" "$DEST_DIR/$name"; do
        if [[ -f "$cand" ]]; then
            readlink -f "$cand"
            return 0
        fi
    done
    echo "PNG não encontrado. Procurei:" >&2
    echo "  $dir/$name" >&2
    echo "  /opt/seminfo/banners/$name" >&2
    echo "  $DEST_DIR/$name" >&2
    return 1
}

write_if_changed() {
    local dest="$1" tmp
    tmp=$(mktemp)
    cat >"$tmp"
    if [[ -f "$dest" ]] && cmp -s "$dest" "$tmp"; then
        rm -f "$tmp"
        return 0
    fi
    mkdir -p "$(dirname "$dest")"
    cat "$tmp" >"$dest"
    rm -f "$tmp"
}

# Grava KEY=VALUE na seção [SECTION]. Não cria o arquivo se ele não existe.
ini_set() {
    local file="$1" section="$2" key="$3" value="$4" tmp
    [[ -f "$file" ]] || return 0
    tmp=$(mktemp)
    awk -v section="$section" -v key="$key" -v value="$value" '
        BEGIN { in_section = 0; done = 0; found = 0 }
        /^[ \t]*\[/ {
            if (in_section && !done) {
                print key "=" value
                done = 1
            }
            hdr = $0
            sub(/^[ \t]*\[/, "", hdr)
            sub(/\].*$/, "", hdr)
            gsub(/\r/, "", hdr)
            in_section = (hdr == section)
            if (in_section) found = 1
            print
            next
        }
        {
            if (in_section) {
                pat = "^[ \t]*#?[ \t]*" key "[ \t]*="
                if ($0 ~ pat) {
                    if (!done) {
                        print key "=" value
                        done = 1
                    }
                    next
                }
            }
            print
        }
        END {
            if (!found) {
                if (NR > 0) print ""
                print "[" section "]"
                print key "=" value
            } else if (in_section && !done) {
                print key "=" value
            }
        }
    ' "$file" >"$tmp"
    if cmp -s "$file" "$tmp"; then
        rm -f "$tmp"
        return 0
    fi
    cat "$tmp" >"$file"
    rm -f "$tmp"
    echo "atualizado: $file [$section] $key"
}

patch_desktop_xml() {
    local file="$1" image="$2" tmp esc
    [[ -f "$file" ]] || return 0
    case "$image" in
        *\"*|*\&*|*\<*|\>*|*$'\n'*) echo "caminho recusado no XML: $image" >&2; return 1 ;;
    esac
    esc=$(printf '%s\n' "$image" | sed -e 's/[|&\\]/\\&/g')
    tmp=$(mktemp)
    # Delimitador # : o | quebraria o tipo int|uint dentro do s///.
    sed -E \
        -e "s#(name=\"last-image\"[[:space:]]+type=\"string\"[[:space:]]+value=\")[^\"]*#\1${esc}#g" \
        -e "s#(name=\"image-path\"[[:space:]]+type=\"string\"[[:space:]]+value=\")[^\"]*#\1${esc}#g" \
        -e "s#(name=\"backdrop-cycle-enable\"[[:space:]]+type=\"bool\"[[:space:]]+value=\")[^\"]*#\1false#g" \
        -e "s#(name=\"image-style\"[[:space:]]+type=\"int\"[[:space:]]+value=\")[^\"]*#\1${IMAGE_STYLE}#g" \
        -e "s#(name=\"image-style\"[[:space:]]+type=\"uint\"[[:space:]]+value=\")[^\"]*#\1${IMAGE_STYLE}#g" \
        "$file" >"$tmp"
    if cmp -s "$file" "$tmp"; then
        rm -f "$tmp"
        return 0
    fi
    cat "$tmp" >"$file"
    rm -f "$tmp"
    echo "xml sem sessão ativa: $file"
}

xfconf_set() {
    local prop="$1" type="$2" value="$3"
    if xfconf-query -c "$CHANNEL" -p "$prop" >/dev/null 2>&1; then
        xfconf-query -c "$CHANNEL" -p "$prop" -s "$value" >/dev/null
    else
        xfconf-query -c "$CHANNEL" -p "$prop" --create -t "$type" -s "$value" >/dev/null
    fi
}

read_installed_image() {
    local line
    [[ -f "$CONF" ]] || return 1
    line=$(sed -n 's/^IMAGE=//p' "$CONF" | head -n 1 || true)
    [[ -n "$line" && -f "$line" ]] || return 1
    printf '%s\n' "$line"
}

apply_session() {
    local image="$1" strict="${2:-0}" ws_count single n mon prop base
    local -A bases=()
    local ready=0 i

    if ! command -v xfconf-query >/dev/null 2>&1; then
        echo "seminfo-wallpaper: xfconf-query ausente" >&2
        [[ "$strict" -eq 1 ]] && return 1
        return 0
    fi

    for i in 1 2 3 4 5 6 7 8 9 10; do
        if xfconf-query -c "$CHANNEL" -l >/dev/null 2>&1; then
            ready=1
            break
        fi
        sleep 0.3
    done
    if [[ "$ready" -ne 1 ]]; then
        echo "seminfo-wallpaper: canal $CHANNEL indisponível nesta sessão" >&2
        [[ "$strict" -eq 1 ]] && return 1
        return 0
    fi

    add_base() {
        local b="$1"
        [[ "$b" =~ ^/backdrop/screen[0-9]+/monitor[^/]+/workspace[0-9]+$ ]] || return 0
        bases["$b"]=1
    }

    ws_count=4
    n=$(xfconf-query -c xfwm4 -p /general/workspace_count 2>/dev/null || true)
    if [[ "$n" =~ ^[0-9]+$ ]] && [[ "$n" -ge 1 && "$n" -le 36 ]]; then
        ws_count=$n
    fi
    single=$(xfconf-query -c "$CHANNEL" -p /backdrop/single-workspace-number 2>/dev/null || true)
    if [[ "$single" =~ ^[0-9]+$ ]] && [[ "$single" -ge "$ws_count" && "$single" -le 36 ]]; then
        ws_count=$((single + 1))
    fi

    if [[ -n "${DISPLAY:-}" ]] && command -v xrandr >/dev/null 2>&1; then
        while read -r mon; do
            [[ -n "$mon" && "$mon" != */* ]] || continue
            for ((i = 0; i < ws_count; i++)); do
                add_base "/backdrop/screen0/monitor${mon}/workspace${i}"
            done
        done < <(xrandr --query 2>/dev/null | awk '/ connected/{print $1}' || true)
    fi

    while read -r prop; do
        case "$prop" in
            /backdrop/screen*/monitor*/workspace*/last-image|\
            /backdrop/screen*/monitor*/workspace*/image-path|\
            /backdrop/screen*/monitor*/workspace*/backdrop-cycle-enable|\
            /backdrop/screen*/monitor*/workspace*/image-style)
                add_base "${prop%/*}"
                ;;
        esac
    done < <(xfconf-query -c "$CHANNEL" -l 2>/dev/null || true)

    if [[ "${#bases[@]}" -eq 0 ]]; then
        echo "seminfo-wallpaper: nenhum monitor; o login seguinte tenta de novo" >&2
        [[ "$strict" -eq 1 ]] && return 1
        return 0
    fi

    for base in "${!bases[@]}"; do
        xfconf_set "$base/last-image" string "$image"
        xfconf_set "$base/image-path" string "$image"
        xfconf_set "$base/backdrop-cycle-enable" bool false
        xfconf_set "$base/image-style" int "$IMAGE_STYLE"
    done

    if pgrep -x xfdesktop >/dev/null 2>&1; then
        xfdesktop --reload >/dev/null 2>&1 || true
    fi
    echo "seminfo-wallpaper: backdrop $image (${#bases[@]} prefixos, ciclo desligado)"
}

session_env_of() {
    local name="$1" pid line
    SESSION_DISPLAY=":0"
    SESSION_XAUTH=""
    pid=$(pgrep -u "$name" -x xfdesktop | head -n 1 || true)
    if [[ -z "$pid" ]]; then
        pid=$(pgrep -u "$name" -x xfce4-session | head -n 1 || true)
    fi
    if [[ -n "$pid" && -r "/proc/$pid/environ" ]]; then
        while IFS= read -r line; do
            case "$line" in
                DISPLAY=*) SESSION_DISPLAY="${line#DISPLAY=}" ;;
                XAUTHORITY=*) SESSION_XAUTH="${line#XAUTHORITY=}" ;;
            esac
        done < <(tr '\0' '\n' <"/proc/$pid/environ" || true)
    fi
}

apply_for_logged_in_user() {
    local name="$1" uid="$2" bus envcmd
    bus="/run/user/$uid/bus"
    [[ -S "$bus" ]] || return 1
    if ! command -v runuser >/dev/null 2>&1; then
        echo "runuser ausente; não deu para aplicar na sessão de $name" >&2
        return 1
    fi
    session_env_of "$name"
    envcmd=(env "DBUS_SESSION_BUS_ADDRESS=unix:path=$bus" "DISPLAY=$SESSION_DISPLAY")
    if [[ -n "$SESSION_XAUTH" ]]; then
        envcmd+=("XAUTHORITY=$SESSION_XAUTH")
    fi
    runuser -u "$name" -- "${envcmd[@]}" "$HELPER" --apply-strict
}

each_human_user() {
    local name _x uid gid gecos home shell
    while IFS=: read -r name _x uid gid gecos home shell; do
        [[ "$uid" =~ ^[0-9]+$ ]] || continue
        [[ "$uid" -ge 1000 && "$uid" -lt 65534 ]] || continue
        [[ -d "$home" ]] || continue
        case "$shell" in
            */nologin|*/false) continue ;;
        esac
        printf '%s\t%s\t%s\n' "$name" "$uid" "$home"
    done </etc/passwd
}

cmd_apply() {
    local image strict=0
    [[ "${1:-}" == "--apply-strict" ]] && strict=1
    image=$(read_installed_image) || {
        echo "seminfo-wallpaper: $CONF sem um PNG instalado" >&2
        [[ "$strict" -eq 1 ]] && exit 1
        exit 0
    }
    apply_session "$image" "$strict"
}

cmd_install() {
    local lab="$1" png src dest image name uid home
    local -a failed=()
    png=$(png_name_for_lab "$lab")
    src=$(find_png "$png") || exit 1
    if ! is_png "$src"; then
        echo "arquivo não é PNG: $src" >&2
        exit 1
    fi
    if [[ "$(id -u)" -ne 0 ]]; then
        echo "rode como root: sudo bash $0 $lab" >&2
        exit 1
    fi

    install -d -m 0755 "$DEST_DIR" /etc/seminfo /etc/xdg/autostart /usr/local/sbin
    dest="$DEST_DIR/$png"
    if [[ -L "$dest" ]]; then
        rm -f "$dest"
    fi
    if [[ -f "$dest" ]] && cmp -s "$src" "$dest"; then
        echo "PNG já instalado: $dest"
    else
        install -m 0644 "$src" "$dest"
        echo "copiado: $dest"
    fi
    image=$dest

    write_if_changed "$CONF" <<EOF
LAB=$lab
IMAGE=$image
EOF
    chmod 0644 "$CONF"

    if [[ "$(readlink -f "$0")" != "$(readlink -f "$HELPER")" ]]; then
        install -m 0755 "$(readlink -f "$0")" "$HELPER"
    fi
    chmod 0755 "$HELPER"

    write_if_changed "$AUTOSTART" <<EOF
[Desktop Entry]
Type=Application
Version=1.0
Name=Wallpaper SEMINFO
Comment=Define o backdrop do xfdesktop para o lab da SEMINFO
Exec=$HELPER --apply
OnlyShowIn=XFCE;
Terminal=false
StartupNotify=false
X-GNOME-Autostart-enabled=true
EOF
    chmod 0644 "$AUTOSTART"

    ini_set /etc/lightdm/lightdm-gtk-greeter.conf greeter background "$image"
    ini_set /etc/lightdm/lightdm-gtk-greeter.conf greeter user-background false
    ini_set /etc/lightdm/slick-greeter.conf Greeter background "$image"
    if [[ ! -f /etc/lightdm/lightdm-gtk-greeter.conf && ! -f /etc/lightdm/slick-greeter.conf ]]; then
        echo "tela de login: nenhum greeter em /etc/lightdm; caminho ausente, nada criado"
    fi

    while IFS=$'\t' read -r name uid home; do
        local_accounts="/var/lib/AccountsService/users/$name"
        if [[ -f "$local_accounts" ]]; then
            ini_set "$local_accounts" User BackgroundFile "$image"
        fi
        if [[ -S "/run/user/$uid/bus" ]]; then
            if ! apply_for_logged_in_user "$name" "$uid"; then
                failed+=("$name")
            fi
        else
            patch_desktop_xml "$home/.config/xfce4/xfconf/xfce-perchannel-xml/xfce4-desktop.xml" "$image"
            echo "sem sessão gráfica de $name; o autostart aplica no próximo login"
        fi
    done < <(each_human_user)

    patch_desktop_xml /etc/skel/.config/xfce4/xfconf/xfce-perchannel-xml/xfce4-desktop.xml "$image"
    patch_desktop_xml /etc/xdg/xfce4/xfconf/xfce-perchannel-xml/xfce4-desktop.xml "$image"

    echo "lab: $lab ($(lab_label "$lab"))"
    echo "backdrop: $image"
    echo "As artes ainda esperam validação visual do Vitor antes de gravar o pendrive."
    if [[ "${#failed[@]}" -gt 0 ]]; then
        echo "falhou a sessão de: ${failed[*]}" >&2
        exit 1
    fi
}

main() {
    case "${1:-}" in
        lab3|lab4) cmd_install "$1" ;;
        --apply|--apply-strict) cmd_apply "$1" ;;
        *) usage ;;
    esac
}

main "$@"
