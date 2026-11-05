> HISTÓRICO — preparação anterior. Substituído por `seminfo/runbook.md`, `seminfo/imagem-maratona.md` e `seminfo/VALIDACAO.md`. Não executar esta receita na entrega Maratona Linux atual.

# Rede — SEMINFO 2026

Quinta 29/10/2026, 13h. Lab 3 e Lab 4 ao mesmo tempo, sem caminho de um para o outro. A equipe usa Debian Live XFCE, não a ISO pronta do Maratona Linux. O navegador só alcança o BOCA da própria mesa.

## O que fica isolado

- Lab 3, Sistemas de Informação: stack `boca-si`, pendrive `ml-seminfo-lab3-si`, arquivo `hosts-boca.lab3`.
- Lab 4, Informática para a Internet: stack `boca-tii`, pendrive `ml-seminfo-lab4-tii`, arquivo `hosts-boca.lab4`.
- Cada lab é uma mesa Windows. O compose publica `8000:80`. O Grafana fica em `127.0.0.1:3001`. `firewall-mesa.ps1` não cria regra para 3001.
- Na mesa, TCP 8000 só entra da `-Subnet` daquele lab. O resto do IPv4 e todo IPv6 nessa porta são bloqueados. O bloqueio vence um allow mais largo que o Docker já tenha.
- No pendrive, o firewall é o nftables de `seminfo/agentes/09-debian/templates/nftables.conf`. O placeholder é o texto `IP_DA_MESA`. A saída de aplicação é só IPv4, só esse host, só TCP 8000. O restante cai, inclusive IPv6, DNS, ICMP echo e qualquer outra porta da mesa. Loopback fica liberado. DHCP v4 (UDP 68 para 67 e a resposta 67 para 68) fica liberado. NTP não sai. `ufw-debian.sh` não entra nesse pendrive.
- DNS de saída fica fechado. A URL é numérica (`http://IP:8000/boca`). O nftables não abre a porta 53. A conexão não sai para outro site, nem para a mesa vizinha.
- `hosts-boca.lab3` e `hosts-boca.lab4` estão no formato de `/etc/maratona-firewall/hosts/boca`: uma linha, um IP. O pacote maratona, se alguém o usar, libera o IP inteiro. Neste pendrive a regra que vale é `seminfo/agentes/09-debian/templates/nftables.conf`. `ufw-debian.sh` fica fora do caminho da SEMINFO.

## O que a mesa ainda precisa

- Trocar `IP_DA_MESA` pelo IPv4 real da mesa daquele lab, no `nftables.conf` daquele pendrive. `hosts-boca.lab3` e `hosts-boca.lab4` seguem o endereço da própria mesa. Um lab não fica com o endereço do outro.
- No Windows da mesa, no lab, PowerShell elevado, antes de a equipe sentar: `powershell -NoProfile -ExecutionPolicy Bypass -File .\firewall-mesa.ps1 -Subnet PREFIXO_REAL`. Não rodar na máquina de preparo. `-WhatIf` valida o CIDR e imprime as três regras TCP 8000 sem criar regra e sem exigir administrador. Se os dois labs couberem no mesmo prefixo, a mesa não separa as equipes; quem separa é o nftables de `seminfo/agentes/09-debian/templates/nftables.conf`. Inclua no prefixo o PC do projetor, se ele não for a própria mesa.
- Na Debian, com a rede ainda aberta, instalar o pacote `nftables`. Troque `IP_DA_MESA` em `seminfo/agentes/09-debian/templates/nftables.conf` e grave `/etc/nftables.conf` antes de habilitar o serviço. A cópia e o hook estão em `seminfo/agentes/09-debian/`. Não rode `ufw-debian.sh` no pendrive.
- No ensaio: `http://IP:8000/boca` abre, `example.com` não abre, o IP da outra mesa não abre, e a porta 3001 não responde na rede do lab.

## Preparo 14/10/2026

Medido nesta máquina de preparo com `Get-NetTCPConnection`, estado Listen. Não é IP de lab.

- TCP 8000 em escuta: `127.0.0.1` pelo `pythonw3.12` (PID 15772); `::1` pelo `wslrelay` (PID 18436); `::` pelo `com.docker.backend` (PID 27800).
- TCP 3001 em escuta: só `127.0.0.1` pelo `com.docker.backend` (PID 27800).
- A porta 3001 não aparece fora de `127.0.0.1`.
- `127.0.0.1:8000` responde 404 e é o `pythonw3.12`, não o BOCA. `http://localhost:8000/boca/` responde 200, título `BOCA Online Contest Administrator boca-1.5.24 - Login`, porque cai no Docker em `::`.
