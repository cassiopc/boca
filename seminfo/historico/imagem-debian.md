> HISTÓRICO — preparação anterior. Substituído por `seminfo/runbook.md`, `seminfo/imagem-maratona.md` e `seminfo/VALIDACAO.md`. Não executar esta receita na entrega Maratona Linux atual.

# Duas imagens de equipe para a SEMINFO

A ISO que está no disco é a Debian Live 13.7 XFCE:

`C:\Users\vitor\OneDrive\Desktop\SENTRA\Isos\debian-live-13.7.0-amd64-xfce.iso` (3,6 GB).

Nessa pasta também há uma `Windows.iso`. Ela não entra na maratona. Não há imagem de BOCA pré-configurada ali. O servidor continua o bocaCaramel em Docker na mesa do professor. A Debian Live 13.7 XFCE é o pendrive da equipe.

O banner da área de trabalho é o PNG, não o HTML. O HTML em `banners/` só serviu para compor o texto sem erro de letra. As artes, em 1920×1080, aguardam o seu ok antes de irem para o pendrive:

- `banners/lab3-si.png` — Lab 3, Sistemas de Informação
- `banners/lab4-tii.png` — Lab 4, Informática para a Internet

A receita de persistência, wallpaper, Firefox e firewall está em `seminfo/agentes/09-debian/RECEITA.md`. Grave o pendrive por essa receita. Esta ISO não traz `maratona-background` nem `maratona-firewall`.

Saem dois pendrives, iguais em compilador, editor e juiz, diferentes no wallpaper, no IP permitido e na página inicial. Os nomes dos arquivos continuam os do runbook:

| Arquivo do pendrive | Wallpaper | Único destino de saída |
| --- | --- | --- |
| `ml-seminfo-lab3-si` | `banners/lab3-si.png` | IP da mesa do Lab 3, TCP 8000 |
| `ml-seminfo-lab4-tii` | `banners/lab4-tii.png` | IP da mesa do Lab 4, TCP 8000 |

O IP de cada mesa continua em branco. Os PNGs só entram depois da validação visual. Até lá, monte o restante e deixe o wallpaper da Debian.

## Arquivos que a receita usa

A outra frente ainda pode estar gravando estes caminhos. O pendrive aponta para eles:

- `seminfo/agentes/09-debian/scripts/seminfo-wallpaper` — papel de parede do XFCE. O PNG do lab vai para `/usr/local/share/seminfo/wallpaper.png`.
- `seminfo/agentes/09-debian/scripts/seminfo-wallpaper.desktop` — autostart da sessão XFCE.
- `seminfo/agentes/09-debian/templates/policies.json` — Firefox ESR abre em `http://IP_DA_MESA:8000/boca`.
- `seminfo/agentes/09-debian/templates/nftables.conf` — firewall de saída do pendrive. O placeholder no arquivo é o texto `IP_DA_MESA`. Troque pelo IPv4 da mesa daquele lab antes de carregar a regra. Não use o IP do outro lab.
- `seminfo/agentes/09-debian/scripts/enable-nftables.hook.chroot` — habilita o nftables no remaster com `live-build`.

`seminfo/agentes/05-rede/ufw-debian.sh` fica fora deste pendrive. Não apague o arquivo. A regra de saída da equipe é `seminfo/agentes/09-debian/templates/nftables.conf`.

## Firewall da mesa

A mesa do professor, no Windows, aceita TCP 8000 só da sub-rede do lab. O script é `seminfo/agentes/05-rede/firewall-mesa.ps1`. Rode no lab, em PowerShell elevado, antes de a equipe sentar. Não rode na máquina de preparo. O prefixo real vai em `-Subnet` e não está neste arquivo. A porta 3001 não recebe regra. O Grafana continua em `127.0.0.1:3001`.

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\seminfo\agentes\05-rede\firewall-mesa.ps1 -Subnet PREFIXO_DESTE_LAB
```

O pendrive e a mesa filtram lados diferentes. O `nftables.conf` só deixa a equipe sair para o IP da própria mesa, na porta 8000. O script da mesa só deixa entrar quem está na sub-rede daquele lab.

## Homepage

A homepage do Firefox muda de imagem para imagem. A política é `seminfo/agentes/09-debian/templates/policies.json`. No ensaio, abra o navegador e confirme que a primeira página é `http://IP_DA_MESA:8000/boca`. Confira também que `example.com` não abre.

## O que gravar

Siga `seminfo/agentes/09-debian/RECEITA.md`. Há duas variantes: a ISO oficial mais uma partição `persistence`, ou um remaster com `live-build`. As duas aplicam o wallpaper, a política do Firefox e o nftables.

1. Um pendrive de teste de cada variante, no lab certo.
2. Secure Boot desligado, UEFI. O ensaio de boot está no runbook.
3. Submissão de um programa de teste no BOCA daquele lab, placar do projetor mostrando essa submissão, e falha ao abrir um site externo.
4. Os banners só contam como aprovados se o XFCE mostrar o curso certo.

Não grave os dez pendrives antes desse teste passar uma vez em cada lab.
