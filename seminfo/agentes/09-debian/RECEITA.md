> HISTÓRICO — preparação anterior. Substituído por `seminfo/runbook.md`, `seminfo/imagem-maratona.md` e `seminfo/VALIDACAO.md`. Não executar esta receita na entrega Maratona Linux atual.

seminfo/banners/lab3-si.png 1920x1080. seminfo/banners/lab4-tii.png 1920x1080.

# Duas lives Debian 13 XFCE para a SEMINFO

Arquivos extraídos, com o mesmo conteúdo dos blocos desta receita:

- `scripts/seminfo-wallpaper`
- `scripts/seminfo-wallpaper.desktop`
- `templates/policies.json`
- `templates/nftables.conf`
- `scripts/enable-nftables.hook.chroot`

Isto não é uma imagem de Maratona Linux. `seminfo/imagem-maratona.md` aponta para esta receita. Não há ISO de BOCA pré-configurada na pasta, e esta Debian Live não traz `maratona-background`, `dconf` nem `maratona-firewall`. A base é o arquivo já no disco:

`C:\Users\vitor\OneDrive\Desktop\SENTRA\Isos\debian-live-13.7.0-amd64-xfce.iso`

Debian Live 13.7.0, trixie, amd64, XFCE. O anúncio do Debian 13 lista XFCE 4.20; o pacote `xfdesktop4` no trixie é o 4.20.1. O log publicado dessa ISO instala `firefox-esr` e firmware de `non-free-firmware`. Não abra, não extraia e não descompacte esse arquivo. Não use nenhuma `Windows.iso`. Não grave mídia a partir deste Windows.

A montagem é numa máquina Debian 13, com o ISO e os dois PNGs copiados para lá. Os nomes dos pendrives continuam os do `seminfo/runbook.md`, para a tabela bater:

| Pendrive | PNG | Único destino de saída |
| --- | --- | --- |
| `ml-seminfo-lab3-si` | `seminfo/banners/lab3-si.png` | IP da mesa do Lab 3, TCP 8000 |
| `ml-seminfo-lab4-tii` | `seminfo/banners/lab4-tii.png` | IP da mesa do Lab 4, TCP 8000 |

Os dois PNGs estão em 1920×1080. São rascunho: o runbook e o `imagem-maratona.md` mandam não gravar os dez pendrives antes do ok visual e de um teste de cada variante. A receita copia o PNG para o meio de teste. Os outros nove esperam o teste de `persistencia.md` passar, com o banner certo na tela do XFCE.

O que as duas variantes fixam é o mesmo. O que muda é só o PNG e o IP.

- Wallpaper do XFCE 4.20, apontando para `/usr/local/share/seminfo/wallpaper.png`.
- Firefox ESR abrindo só `http://IP_DA_MESA:8000/boca`.
- Firewall de saída: TCP 8000 só para esse IP. Loopback e DHCP v4 são exceção de enlace, não de navegação.

A variante 1 não altera o ISO: copia o arquivo inteiro para o pendrive e guarda a mudança numa partição `persistence`. A variante 2 não lê esse ISO: o `live-build` do trixie gera outra ISO híbrida a partir do espelho Debian. Nenhum dos dois caminhos extrai a ISO de 3,8 GB.

O IP não está neste arquivo. É o da mesa daquele lab, o mesmo anotado no runbook (`http://IP:8000/boca`). Onde estiver escrito `IP_DA_MESA`, troque por um IPv4 literal antes de carregar a regra ou a política. Não use nome. Não use o IP do outro lab. `192.0.2.10` não aparece de propósito: é rede de documentação, e um arquivo carregado com isso não alcança o BOCA.

A regra de entrada do Windows no runbook continua. Ela limita quem fala com a porta 8000 da mesa. O pendrive limita para onde a equipe sai. São filtros diferentes. O `imagem-maratona.md` libera o IP inteiro da mesa e deixa a porta para o Windows; aqui a porta 8000 também é cortada no pendrive.

## O que entra nos dois

Compiladores desta maratona interna, e só estes: `gcc`, `g++`, `python3`, e `openjdk-21-jdk` se couber. No trixie esse pacote existe e puxa o JDK 21 (`default-jdk` no amd64 aponta para ele). Não é o conjunto do Maratona Linux: sem Pascal, Kotlin, Rust, Eclipse nem os outros juízes e editores daquela imagem. O XFCE já traz o Mousepad. Não instale mais nada para "completar".

`openjdk-21-jdk` mais o JRE ficam na casa das centenas de MB, não de vários GB. A ISO oficial tem cerca de 3,6 GB. Num pendrive de 8 GB cabe, com folga menor do que parece porque "8 GB" de rótulo às vezes é 7,2 GiB. Se depois do JDK a partição de persistência (variante 1) ou a folga que você reservou ficar com menos de 1 GB livres, não instale o JDK e anote no pendrive "sem Java". C, C++ e Python permanecem.

O usuário da sessão live é `user`. A senha está no menu de boot da imagem oficial; não copiar senha para este repositório. Na variante 1 o setup usa `sudo -i`. Ver o limite no fim: esse usuário consegue desfazer o firewall.

### Wallpaper

O banner do lab substitui o arquivo único abaixo. Lab 3 e Lab 4 não compartilham pendrive.

```
sudo mkdir -p /usr/local/share/seminfo
sudo cp lab3-si.png /usr/local/share/seminfo/wallpaper.png
sudo chmod 644 /usr/local/share/seminfo/wallpaper.png
```

No Lab 4 a origem é `lab4-tii.png` e o destino é o mesmo caminho. Não use `fundo.jpg`: o texto do curso já está no PNG.

Trocar só `/usr/share/images/desktop-base/default` não basta no XFCE 4.20 do trixie. O xfdesktop lê, no canal `xfce4-desktop`, a propriedade:

`/backdrop/screen0/monitorNOME/workspace0/last-image`

`NOME` é a saída do `xrandr` (`HDMI-1` vira `monitorHDMI-1`). O nome muda com o monitor do lab. Conferir no boot. O inteiro `image-style` 5 é o valor zoomed da enum do xfdesktop (0 none, 1 centered, 2 tiled, 3 stretched, 4 scaled, 5 zoomed). O PNG é 1920×1080; zoomed preenche essa resolução sem deformar. Num painel de outra proporção ele corta a borda. Conferir no boot.

Script, nos dois variantes, em `/usr/local/sbin/seminfo-wallpaper`:

```sh
#!/bin/sh
IMG=/usr/local/share/seminfo/wallpaper.png
sleep 2
if [ ! -f "$IMG" ]; then
  echo "sem wallpaper: $IMG" >&2
  exit 0
fi
xrandr --query | awk '/ connected/{print $1}' | while read -r out; do
  base="/backdrop/screen0/monitor${out}/workspace0"
  xfconf-query -c xfce4-desktop -p "$base/last-image" -n -t string -s "$IMG" \
    || xfconf-query -c xfce4-desktop -p "$base/last-image" -t string -s "$IMG"
  xfconf-query -c xfce4-desktop -p "$base/image-style" -n -t int -s 5 \
    || xfconf-query -c xfce4-desktop -p "$base/image-style" -t int -s 5
done
xfconf-query -c xfce4-desktop -l | while read -r prop; do
  case "$prop" in
    */last-image|*/image-path|*/last-single-image)
      xfconf-query -c xfce4-desktop -p "$prop" -s "$IMG" || true
      ;;
  esac
done
```

`chmod 755` nesse script. Autostart da sessão, em `/etc/xdg/autostart/seminfo-wallpaper.desktop`:

```
[Desktop Entry]
Type=Application
Name=Wallpaper SEMINFO
Exec=/usr/local/sbin/seminfo-wallpaper
OnlyShowIn=XFCE;
```

O autostart roda como o usuário da sessão, não como root. O xfconf é por sessão. Se o primeiro login ainda mostrar o papel de parede Debian, saia e entre de novo: o script regrava as propriedades `last-image` que o xfdesktop já tiver criado. Se continuar errado, conferir no boot: mude o fundo uma vez em Definições, Desktop, com `xfconf-query -c xfce4-desktop -m` aberto, e anote a propriedade que aparecer. Há relato de saída `eDP-1` gravada sem o hífen. Não chute o nome.

### Firefox ESR

O pacote da ISO 13.7 é o `firefox-esr` (o log dessa série descompacta o ESR 140 e os pacotes `firefox-esr-l10n-*`). O diretório de instalação publicado no pacote do trixie inclui `/usr/lib/firefox-esr/distribution`. O Firefox no Linux também lê `/etc/firefox/policies/policies.json`; no `firefox-esr` do Debian um `strace` antigo já mostrou esse caminho, e não `/etc/firefox-esr/policies`. Grave o mesmo JSON nos dois lugares. Qual dos dois vale é o que `about:policies` marcar como ativo. Conferir no boot. Se os dois estiverem inativos:

```
dpkg -l firefox-esr firefox
strace -f -e trace=file firefox-esr about:policies 2>&1 | grep policies.json
```

Não navegue para outro site nesse teste.

`/etc/firefox/policies/policies.json` e `/usr/lib/firefox-esr/distribution/policies.json`:

```json
{
  "policies": {
    "Homepage": {
      "URL": "http://IP_DA_MESA:8000/boca",
      "Locked": true,
      "StartPage": "homepage-locked"
    },
    "OverrideFirstRunPage": "",
    "OverridePostUpdatePage": "",
    "DontCheckDefaultBrowser": true,
    "DisableAppUpdate": true,
    "DisablePrivateBrowsing": true,
    "BlockAboutConfig": true,
    "CaptivePortal": false,
    "HttpsOnlyMode": "disallowed",
    "DNSOverHTTPS": {
      "Enabled": false,
      "Locked": true
    },
    "ExtensionSettings": {
      "*": {
        "installation_mode": "blocked"
      }
    },
    "WebsiteFilter": {
      "Block": ["<all_urls>"],
      "Exceptions": ["http://IP_DA_MESA:8000/*"]
    }
  }
}
```

`StartPage` `homepage-locked` e `WebsiteFilter` com `<all_urls>` estão no manual de políticas do Firefox. `HttpsOnlyMode` `disallowed` é obrigatório porque o BOCA deste runbook é `http`, não `https`. DoH fica desligado para não furar o firewall por outro resolvedor. A barra de endereços continua visível: "preso" aqui é política e firewall, não modo quiosque. `about:policies` tem de listar `Homepage` e `WebsiteFilter` sem erro. Se uma chave for rejeitada, apague só essa chave e confira de novo. Não invente outra.

### Firewall

Pacote `nftables` do trixie. A lista de arquivos do pacote publica `/etc/nftables.conf` e `/usr/lib/systemd/system/nftables.service`. Conferir no boot com `dpkg -L nftables` se um ponto de release mover a unit.

Substitua `IP_DA_MESA` e só então grave `/etc/nftables.conf`:

```
#!/usr/sbin/nft -f
flush ruleset

table inet seminfo {
  chain input {
    type filter hook input priority filter; policy drop;
    iifname "lo" accept
    ct state established,related accept
    udp sport 67 udp dport 68 accept
    icmp type destination-unreachable accept
  }

  chain forward {
    type filter hook forward priority filter; policy drop;
  }

  chain output {
    type filter hook output priority filter; policy drop;
    oifname "lo" accept
    ct state established,related accept
    udp sport 68 udp dport 67 accept
    ip daddr IP_DA_MESA tcp dport 8000 accept
  }
}
```

Saída de aplicação: só IPv4, só esse host, só TCP 8000. O restante cai, inclusive IPv6, DNS, ICMP echo e qualquer outra porta da mesa. Sem DNS de propósito: a URL é o IP literal. DHCP v4 (UDP 68 para 67, e a resposta 67 para 68) fica liberado porque sem isso o pendrive não ganha endereço. Loopback fica liberado porque não é saída de rede. `destination-unreachable` na entrada evita buraco de PMTU no meio do caminho; não responde ping. A tabela `inet` cobre IPv4 e IPv6; não há regra `ip6 daddr` aceitando tráfego.

Se a mesa não tiver IPv4, pare. Este conjunto não serve para um lab só IPv6.

Carregar, ainda com a rede de instalação no ar, só depois dos `apt` abaixo:

```
sudo nft -c -f /etc/nftables.conf
sudo systemctl enable --now nftables.service
sudo systemctl restart nftables.service
sudo nft list ruleset
```

`nft -c -f` só confere a sintaxe. Se falhar, o token `IP_DA_MESA` ainda está no arquivo. Não ligue o serviço antes dos pacotes: o espelho Debian deixa de ser alcançável.

### Ordem numa sessão já iniciada (variante 1)

Internet só nesta etapa. A prova não.

```
sudo apt-get update
sudo apt-get install -y --no-install-recommends gcc g++ python3 openjdk-21-jdk nftables
```

Se o JDK não couber, tire `openjdk-21-jdk` da linha e anote. Confira `gcc --version`, `g++ --version`, `python3 --version` e, se instalou, `javac -version`. Isso é da montagem, não do teste de `persistencia.md`.

Copie o PNG, o script, o `.desktop`, os dois `policies.json` e o `nftables.conf`. Habilite o `nftables` por último. Reabra o Firefox depois da política; a primeira janela antiga não a relê.

## Variante 1 — ISO oficial mais partição persistence

Manual do Debian Live, seções 4.4.2, 4.4.3 e 10.3. A ferramenta é a do próprio Debian: `cp` do ISO híbrido, `parted`, `mkfs.ext4`. Não use um gravador que estica a imagem até o fim do pendrive: ele não deixa espaço livre, e o manual não faz isso.

O volume de persistência é uma partição reconhecida pelo rótulo de filesystem `persistence`, com `persistence.conf` na raiz. O manual também aceita o nome GPT `persistence`. O parâmetro de boot `persistence` é obrigatório em todo boot. Sem ele, o live ignora a partição e volta à imagem crua. `nopersistence` faz o mesmo. A partição ISO9660 gravada pelo `cp` é só leitura, então o menu não lembra o parâmetro: cada boot pede de novo. Conferir no boot a tecla do menu. No GRUB (o caso UEFI desta imagem) é `e` na entrada Live, `persistence` no fim da linha `linux`, Ctrl+X ou F10 para iniciar. No isolinux/syslinux a tecla costuma ser Tab e depois Enter. O runbook pede Secure Boot desligado e UEFI.

`/ union` é o atalho do manual para persistência inteira. O mesmo manual diz que `/`, `/lib` e `/lib/live` não podem ser montagens customizadas. Não monte uma lista parcial.

Identifique o disco. `lsblk -o NAME,SIZE,TYPE,FSTYPE,LABEL,TRAN,MODEL` depois de plugar o pendrive. `USB` é o disco (`/dev/sdb`), nunca a partição (`/dev/sdb1`) e nunca o disco interno.

```
sudo cp -- /caminho/debian-live-13.7.0-amd64-xfce.iso "$USB"
sudo sync
sudo parted "$USB" unit MiB print free
```

No intervalo livre grande (não nos vãos de alinhamento de poucos MiB), `START` é o início que o `print free` mostrou:

```
sudo parted "$USB" --script mkpart persistence ext4 START 100%
sudo parted "$USB" unit MiB print
```

A partição nova é a que não existia. Na híbrida do Debian costuma aparecer a ISO9660, uma ESP e então esta. Confira com `lsblk` antes do `mkfs`. Formatar a partição errada acaba com o boot. Se o `parted` recusar a linha, conferir no boot o `man parted` dessa máquina; não apague as partições que o ISO criou.

```
sudo mkfs.ext4 -L persistence /dev/sdXN
sudo mount /dev/sdXN /mnt
echo '/ union' | sudo tee /mnt/persistence.conf
sudo umount /mnt
```

Primeiro boot, com internet e com `persistence` na linha do kernel. Confirme que a partição entrou:

```
findmnt -t ext4
```

O rótulo tem de ser `persistence`. Conferir no boot o ponto de montagem exato. Instale os pacotes, copie os arquivos da seção anterior, ligue o nftables. Grave um marcador e reinicie de novo com `persistence`:

```
echo ok | sudo tee /etc/seminfo-persist-ok
```

No segundo boot, `cat /etc/seminfo-persist-ok` tem de imprimir `ok`. Se não imprimir, esta variante falhou neste hardware. Não clone. Use a variante 2.

São dois pendrives, ou dois passes, um por lab. Não reuse a partição de um lab no outro: o overlay teria o PNG e o IP errados.

Clonar, só depois do teste: `dd` ou `cp` do disco inteiro, não só do ISO, senão a partição de persistência fica para trás. O overlay clonado leva também lixo de teste. Antes de duplicar, não deixe arquivo de aluno em `/home`.

## Variante 2 — remaster mínimo com live-build

Pacote Debian `live-build` (no trixie, `1:20250505+deb13u1`). Comandos conferidos no manual do Debian Live e em `lb_config(1)`: `lb config`, `lb build`, `--distribution`, `--archive-areas`, `--architectures`, `--binary-images`, `--debian-installer`, `--bootappend-live`, `--image-name`, `--firmware-chroot`, `--apt-recommends`. Diretórios conferidos no manual: `config/package-lists/*.list.chroot`, `config/includes.chroot/` (ainda aceito; não misture com `includes.chroot_after_packages`), `config/hooks/normal/*.hook.chroot`.

Não aponta para o ISO de 3,8 GB. Baixa o trixie do espelho e produz outra ISO híbrida. Precisa de rede e de dezenas de minutos. O resultado fica do tamanho de uma live XFCE, não de um pendrive mínimo, porque `live-task-xfce` puxa `task-xfce-desktop` e, com recommends, o mesmo tipo de desktop da imagem oficial (incluindo LibreOffice). Isso não adiciona o conjunto do Maratona Linux.

Dois diretórios, um por lab. Não reaproveite `config/` entre eles.

```
sudo apt-get install -y live-build
mkdir ml-seminfo-lab3-si && cd ml-seminfo-lab3-si
lb config \
  --distribution trixie \
  --archive-areas "main non-free-firmware" \
  --architectures amd64 \
  --binary-images iso-hybrid \
  --debian-installer none \
  --firmware-chroot true \
  --apt-recommends true \
  --image-name ml-seminfo-lab3-si \
  --bootappend-live "boot=live components"
```

`--firmware-chroot true` é o padrão do `lb_config`. A área `non-free-firmware` é a que a ISO 13.7 realmente usa; sem ela o firmware não entra e uma placa do lab pode subir sem rede. `--debian-installer none` também é o padrão (nenhum instalador). O `--bootappend-live` do manual substitui a linha, por isso `boot=live components` vai junto. Não coloque `persistence` nessa linha: o que esta variante fixa já está no squashfs, e um reboot sem pendrive de overlay volta ao que foi gravado. Locale e teclado `br` não são desta receita; o menu da live deixa escolher. Conferir no boot.

`config/package-lists/seminfo.list.chroot`, um pacote por linha ou na mesma linha:

```
live-task-xfce
firefox-esr
gcc
g++
python3
openjdk-21-jdk
nftables
```

`firefox-esr` já vem na imagem oficial; listar aqui evita uma live sem navegador se o recommend mudar. Se for ficar sem Java, apague a linha `openjdk-21-jdk` antes do `lb build`.

Os arquivos da seção "O que entra nos dois" vão para `config/includes.chroot/` com o mesmo caminho absoluto. Exemplos:

```
config/includes.chroot/usr/local/share/seminfo/wallpaper.png
config/includes.chroot/usr/local/sbin/seminfo-wallpaper
config/includes.chroot/etc/xdg/autostart/seminfo-wallpaper.desktop
config/includes.chroot/etc/firefox/policies/policies.json
config/includes.chroot/usr/lib/firefox-esr/distribution/policies.json
config/includes.chroot/etc/nftables.conf
```

O PNG desse diretório é o do Lab 3. O JSON e o `nftables.conf` já têm o IP da mesa do Lab 3, não o token.

Hook `config/hooks/normal/9000-nftables.hook.chroot`, executável (`chmod +x`), como o manual pede para hook de chroot:

```sh
#!/bin/sh
set -e
systemctl enable nftables.service
```

O hook roda dentro do chroot, depois dos includes. `systemctl enable` nesse contexto só cria o vínculo da unit; não precisa de systemd rodando. Se o `lb build` quebrar nesta linha, conferir no boot `dpkg -L nftables | grep nftables.service` num chroot trixie e ajuste o nome que o `enable` procura. Não habilite na mão um caminho inventado.

```
sudo lb build
```

O manual, com o nome padrão `live-image`, gera `live-image-amd64.hybrid.iso`. Com `--image-name ml-seminfo-lab3-si` o esperado é `ml-seminfo-lab3-si-amd64.hybrid.iso`. Conferir no boot o arquivo `*.hybrid.iso` que o `lb build` deixar no diretório. Repita o diretório para `ml-seminfo-lab4-tii` com o outro PNG e o outro IP.

Gravar um pendrive de teste, de novo pelo manual, sem partição persistence:

```
sudo cp -- ml-seminfo-lab3-si-amd64.hybrid.iso "$USB"
sudo sync
```

Boot normal, sem o parâmetro `persistence`. Se alguém reutilizar um pendrive da variante 1, a partição antiga ainda pode ser apanhada se o menu levar `persistence`. Pendrive da variante 2 é só o ISO.

## Limite

As duas variantes aplicam wallpaper, política do Firefox e nftables. Nenhuma das duas é a imagem lacrada do Maratona Linux. O `live-config` atual documenta `live-config.noroot` (atalho `noroot`): não configura sudo nem policykit, e o usuário não ganha root por esse caminho. Na variante 2 dá para pôr esse token no `--bootappend-live`, junto de `boot=live components`, se quiser o teste já sem sudo. Na variante 1 ele não apaga um sudo que a persistência já gravou: o componente só deixa de conceder de novo. Conferir no boot, num segundo arranque com `persistence live-config.noroot`, se `sudo` ainda funciona. Se funcionar, o grant está no overlay (`/etc/sudoers` e `/etc/sudoers.d/`); apague o que o live-config escreveu, sem chutar o nome do arquivo, e teste outra vez.

Quem tem root derruba o firewall com um flush. O teste de `persistencia.md` não prova o contrário. Não documente senha aqui para "facilitar" isso.

## Antes dos dez pendrives

Um pendrive de cada variante, no lab certo, passa por `persistencia.md`. Os banners só estão aprovados se o XFCE mostrar o curso certo, não se o PNG abrir no Windows. Aí sim os dez. Até lá, não grave o resto.
