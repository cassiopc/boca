> HISTÓRICO — preparação anterior. Substituído por `seminfo/runbook.md`, `seminfo/imagem-maratona.md` e `seminfo/VALIDACAO.md`. Não executar esta receita na entrega Maratona Linux atual.

# Wallpaper do XFCE — SEMINFO 2026

O script `install-wallpaper.sh` roda **dentro** da Debian Live 13.7 XFCE já bootada. Não monta a ISO. O wallpaper é o backdrop do `xfdesktop`, não uma página.

```bash
sudo bash install-wallpaper.sh lab3
sudo bash install-wallpaper.sh lab4
```

`lab3` usa `lab3-si.png` (Lab 3, Sistemas de Informação). `lab4` usa `lab4-tii.png` (Lab 4, Informática para a Internet). O PNG é procurado ao lado do script e, se não estiver lá, em `/opt/seminfo/banners/`. Se os dois faltarem, vale o arquivo já copiado em `/usr/share/backgrounds/seminfo/`. A cópia fica nesse diretório (modo 0644) e o caminho em `/etc/seminfo/wallpaper.conf`. Repetir o comando não duplica arquivo nem chave.

## Caminho xfconf do xfdesktop

Canal `xfce4-desktop`. No xfdesktop 4.20.1 da Debian 13 o monitor não é `monitor0`: o nome é `monitor` colado no conector do xrandr (`HDMI-1` vira `monitorHDMI-1`). Para cada monitor ligado e cada workspace:

- `/backdrop/screen0/monitorHDMI-1/workspace0/last-image` — caminho absoluto do PNG. É esta a propriedade que o xfdesktop 4.20 lê.
- `/backdrop/screen0/monitorHDMI-1/workspace0/image-path` — o mesmo caminho. Chave antiga: o xfdesktop anterior migrava `image-path` para `last-image`. No 4.20 ela não substitui `last-image`, mas o script também a grava.
- `/backdrop/screen0/monitorHDMI-1/workspace0/backdrop-cycle-enable` — `false`. O ciclo fica desligado para o banner não ser trocado pelo próximo arquivo da pasta.
- `/backdrop/screen0/monitorHDMI-1/workspace0/image-style` — `5` (zoom), para o PNG 1920×1080 preencher a tela.

O mesmo prefixo vale para os outros workspaces (`workspace1`, …) e para o conector que o `xrandr` mostrar na máquina (`eDP-1`, `Virtual-1`, `DP-1`). Como o conector só existe com a sessão gráfica aberta, o script aplica na hora em quem já está logado e deixa `/etc/xdg/autostart/seminfo-wallpaper.desktop`, que chama `/usr/local/sbin/seminfo-wallpaper.sh --apply` em cada login XFCE.

Tela de login, só se o arquivo já existir: em `/etc/lightdm/lightdm-gtk-greeter.conf`, seção `[greeter]`, `background` aponta para o PNG e `user-background=false`. Em `/etc/lightdm/slick-greeter.conf`, seção `[Greeter]`, só `background`. Caminho ausente não é criado. O mesmo vale para `BackgroundFile` em `/var/lib/AccountsService/users/<usuario>` quando esse arquivo já existe.

Sem a persistência da Debian (receita em `agentes/09-debian/`), a cópia e o xfconf valem só até desligar.

## Validação

As artes `banners/lab3-si.png` e `banners/lab4-tii.png` ainda esperam a validação visual do Vitor. Não grave o pendrive com esse wallpaper antes desse ok.

No Maratona Linux o arquivo oficial é `/usr/share/maratona-background/maratona-common-wallpaper.png`, apontado pelo GNOME via dconf (`/etc/dconf/db/local.d/90-ml-wallpaper`). Nesta SEMINFO a base escolhida no disco é a Debian Live 13.7 XFCE, não a ISO do Maratona Linux: o wallpaper é o backdrop do xfdesktop, no canal acima, e não o dconf do GNOME.
