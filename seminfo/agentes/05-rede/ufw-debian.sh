#!/bin/sh
# Nao usar no pendrive da SEMINFO. O arquivo que vale e
# seminfo/agentes/09-debian/templates/nftables.conf.
#
# SEMINFO 2026 — firewall da equipe na Debian Live XFCE, fora desse caminho.
# Nao usa o pacote maratona-firewall. Nao rode no pendrive da prova.
# Nao rode no Windows. O corpo abaixo fica no repositorio e nao entra na imagem.
#
#   sh ufw-debian.sh hosts-boca.lab3
#   DNS_LOCAL=10.1.3.1 sh ufw-debian.sh hosts-boca.lab4
#
# O arquivo de hosts e o mesmo formato de /etc/maratona-firewall/hosts/boca:
# uma linha, um endereco. La o nome do arquivo viraria o hostname "boca" e o
# pacote liberaria TCP/UDP para o IP inteiro. Aqui a saida e so TCP nesse
# IP, porta 8000 (o compose publica 8000:80). Loopback entra e sai.
# NTP (UDP 123) fica de fora: a prova nao precisa e a porta seria outra saida.
#
# DNS da rede local, so se DNS_LOCAL for passado. A URL da prova e
# http://IP:8000/boca, entao nao ha nome para resolver e o padrao e a porta
# 53 fechada. Se o lab anuncia um resolver privado no DHCP, o NetworkManager
# e o systemd-resolved encaminham para ele. Liberar so TCP/UDP 53 para esse
# IPv4 RFC1918 nao deixa o navegador abrir outro host: o nome ate pode
# resolver, a conexao seguinte continua negada, inclusive a mesa do outro
# lab e a internet. Nao use 8.8.8.8, 1.1.1.1 nem o IP da outra mesa.

set -eu

usage() {
    echo "Uso: sh ufw-debian.sh hosts-boca.lab3|hosts-boca.lab4" >&2
    echo "Opcional, resolver privado do lab: DNS_LOCAL=10.x.x.x sh ufw-debian.sh hosts-boca.lab3" >&2
    exit 1
}

split_ipv4() {
    _ip=$1
    _a=${_ip%%.*}
    _rest=${_ip#*.}
    _b=${_rest%%.*}
    _rest=${_rest#*.}
    _c=${_rest%%.*}
    _d=${_rest#*.}
}

valid_ipv4() {
    split_ipv4 "$1"
    for _o in "$_a" "$_b" "$_c" "$_d"; do
        case $_o in
            ''|*[!0-9]*|0[0-9]*) return 1 ;;
        esac
        if [ "$_o" -gt 255 ]; then
            return 1
        fi
    done
    [ "${_a}.${_b}.${_c}.${_d}" = "$1" ]
}

is_rfc1918() {
    split_ipv4 "$1"
    if [ "$_a" -eq 10 ]; then
        return 0
    fi
    if [ "$_a" -eq 192 ] && [ "$_b" -eq 168 ]; then
        return 0
    fi
    if [ "$_a" -eq 172 ] && [ "$_b" -ge 16 ] && [ "$_b" -le 31 ]; then
        return 0
    fi
    return 1
}

if [ "$#" -ne 1 ]; then
    usage
fi

hosts=$1
if [ ! -f "$hosts" ]; then
    echo "Arquivo nao encontrado: $hosts" >&2
    exit 1
fi

line=$(awk 'NR==1 { gsub(/[ \t\r]/, ""); print; exit }' "$hosts") || {
    echo "Nao consegui ler $hosts" >&2
    exit 1
}

if [ -z "$line" ]; then
    echo "A linha unica deve ser o IPv4 da mesa. Enquanto nao houver IP, deixe IP_DA_MESA." >&2
    exit 1
fi

if [ "$line" = "IP_DA_MESA" ]; then
    echo "Ainda esta IP_DA_MESA. Troque pelo IPv4 real da mesa deste lab e rode de novo no pendrive." >&2
    exit 1
fi

extra=$(awk 'NF > 0 && NR > 1 { n++ } END { print n + 0 }' "$hosts") || extra=1
if [ "$extra" -ne 0 ]; then
    echo "O formato e uma linha e um IP, como /etc/maratona-firewall/hosts/boca. Nao acrescente a outra mesa." >&2
    exit 1
fi

if ! valid_ipv4 "$line"; then
    echo "Linha 1 nao e um IPv4: $line" >&2
    exit 1
fi

split_ipv4 "$line"
if [ "$_a" -eq 127 ] || [ "$_a" -ge 224 ] || [ "$line" = "0.0.0.0" ]; then
    echo "IP $line nao serve como mesa." >&2
    exit 1
fi

dns=${DNS_LOCAL:-}
if [ -n "$dns" ]; then
    if ! valid_ipv4 "$dns" || ! is_rfc1918 "$dns"; then
        echo "DNS_LOCAL tem de ser o resolver privado do lab (10/8, 172.16/12 ou 192.168/16)." >&2
        echo "Sem essa variavel a porta 53 fica fechada, e a prova segue por IP." >&2
        exit 1
    fi
fi

if [ ! -f /etc/debian_version ]; then
    echo "Este script e para a Debian Live da equipe, no lab. Nao rode no Windows." >&2
    exit 1
fi

if [ "$(id -u)" -ne 0 ]; then
    echo "Rode como root na Debian da equipe, no lab." >&2
    exit 1
fi

if ! command -v ufw >/dev/null 2>&1; then
    echo "ufw nao esta instalado. Com a rede ainda aberta: apt-get install --no-install-recommends ufw" >&2
    echo "So depois rode este script. Ele nao chama o apt." >&2
    exit 1
fi

ufw --force reset
ufw default deny incoming
ufw default deny outgoing
ufw default deny routed
ufw allow in on lo
ufw allow out on lo
ufw allow out proto tcp to "$line" port 8000

if [ -n "$dns" ]; then
    ufw allow out proto udp to "$dns" port 53
    ufw allow out proto tcp to "$dns" port 53
fi

ufw reject out proto udp to any
ufw reject out proto tcp to any
ufw --force enable

printf 'Saida negada. Liberado: loopback e TCP %s porta 8000.\n' "$line"
if [ -n "$dns" ]; then
    printf 'DNS local liberado: %s porta 53 (TCP e UDP). Nenhum outro destino.\n' "$dns"
else
    printf 'DNS de saida fechado. A prova usa http://%s:8000/boca .\n' "$line"
fi
printf 'NTP fechado. Entrada negada, salvo loopback.\n'
