> HISTÓRICO — preparação anterior. Substituído por `seminfo/runbook.md`, `seminfo/imagem-maratona.md` e `seminfo/VALIDACAO.md`. Não executar esta receita na entrega Maratona Linux atual.

# Agente 01 — inventário de mídia

Data da checagem: 2026-10-13. Nada foi extraído por completo, montado, apagado ou enviado. `Windows.iso` não foi aberta. Não há `7z`, `isoinfo` nem `xorriso` no PATH; o rótulo e o modo híbrido saíram de leituras pontuais do sistema ISO 9660 (descritores de volume, diretório raiz, `.disk/` e `isolinux/`).

## Conclusão

A base da distro das equipes é a Debian Live XFCE que já está na pasta de ISOs:

`C:\Users\vitor\OneDrive\Desktop\SENTRA\Isos\debian-live-13.7.0-amd64-xfce.iso`

Não há distro BOCA pronta nessa pasta, nem outra imagem pré-configurada de BOCA ou Maratona Linux nos locais vasculhados. O arquivo é a Live oficial do Debian 13.7.0 (trixie), ambiente XFCE, híbrida (BIOS pelo ISOLINUX e UEFI pelo GRUB). A lista de pacotes do squashfs não contém nada com `boca` ou `maratona`.

O servidor da prova continua o bocaCaramel em Docker na mesa do professor (Windows). A Debian é a máquina da equipe, não o juiz.

Há uma segunda cópia, do mesmo tamanho, da mesma data e com o mesmo volume ID, em `C:\Users\vitor\Downloads\debian-live-13.7.0-amd64-xfce.iso`. É a mesma Live oficial, não uma imagem de BOCA.

## Pasta de ISOs

`C:\Users\vitor\OneDrive\Desktop\SENTRA\Isos` não tem subpastas. Só estes dois arquivos:

| Arquivo | Tamanho | Última gravação | Criação no disco | Uso |
| --- | --- | --- | --- | --- |
| `debian-live-13.7.0-amd64-xfce.iso` | 3.826.974.720 bytes (3,56 GiB) | 2026-10-11 18:21:18 | 2026-10-11 18:21:26 | Base das equipes |
| `Windows.iso` | 4.727.242.752 bytes (4,40 GiB) | 2026-10-11 17:54:46 | 2026-10-11 17:54:40 | Fora da maratona; não aberta |

O tamanho da Debian coincide com o volume ISO (1.868.640 blocos de 2048 bytes). O arquivo não está truncado.

## O que a Debian é

Leitura do descritor primário (setor 16) e dos metadados `.disk/`, sem descompactar o `filesystem.squashfs` (3.060.621.312 bytes):

- Volume ID ISO 9660: `d-live 13.7.0 xf amd64`
- Aplicativo: `Debian Live`
- `.disk/info`: `Official Debian GNU/Linux Live 13.7.0 xfce 2026-09-12T07:55:41Z`
- `.disk/cd_type`: `live`
- Gerador: `rebuild.sh --configuration xfce --debian-version trixie --debian-version-number 13.7.0 --timestamp archive`
- Preparador: live-build `3067ec33ca3af695213fb6424062359a6c916f2e`, 2026-08-21T15:48:18+02:00
- Publicador: Debian Live project
- Linha do `mkisofs` grava a saída como `live-image-amd64.hybrid.iso` e pede `-isohybrid-mbr`, `-isohybrid-gpt-basdat` e `-isohybrid-apm-hfsplus`
- Boot BIOS: El Torito, `isolinux/isolinux.bin`, menu `Live system (amd64)`
- Boot UEFI: `boot/grub/efi.img` e `EFI/BOOT/BOOTX64.EFI`
- Kernel no nome do arquivo: `vmlinuz-6.12.107-deb13-amd64`
- Desktop no pacote: `task-xfce-desktop` 3.81 e `xfce4` 4.20.1, mais `live-boot` e `live-config`
- Pacotes com `boca` ou `maratona`: nenhum (2410 linhas em `live/filesystem.packages`)

Híbrida também no cabeçalho do arquivo, não só na receita:

- Assinatura de MBR `55 AA` no offset 510; o MBR cita `isolinux.bin`
- Partição 1 ativa (status `80`), tipo `00`, LBA 64, 7.474.496 setores de 512 bytes
- Partição 2 tipo `EF` (sistema EFI), LBA 8052, 6.656 setores = 3.407.872 bytes, o mesmo tamanho de `boot/grub/efi.img`
- GPT com assinatura `EFI PART` no LBA 1
- Descritor de boot El Torito no setor 17

A cópia em Downloads tem o mesmo volume ID (`d-live 13.7.0 xf amd64`), os mesmos 3.826.974.720 bytes e a mesma última gravação (2026-10-11 18:21:18).

## Busca por imagem de BOCA ou Maratona

Não varri o disco inteiro. Extensões: `*.iso`, `*.vdi`, `*.ova`.

| Raiz | Resultado |
| --- | --- |
| `C:\Users\vitor\Desktop` | 143 diretórios, 0 imagens. Este caminho não é a Área de Trabalho conhecida do Windows. |
| `C:\Users\vitor\OneDrive\Desktop` | Área de Trabalho conhecida (`shell:Desktop`). 111.871 diretórios. |
| `C:\Users\vitor\Downloads` | `shell:Downloads`. 60 diretórios. |
| `C:\Users\vitor\OneDrive\Downloads` | Não existe. |

Um diretório muito longo em `SENTRA\third_party\langfuse\...` falhou no limite clássico de caminho. A mesma árvore, refeita com prefixo `\\?\`, deu 25 diretórios, 0 erros e 0 imagens.

O que apareceu, além da Debian e da `Windows.iso`:

| Arquivo | Tamanho | Data | Classificação |
| --- | --- | --- | --- |
| `SENTRA\.tmp\seed-validation.iso` | 45.056 bytes | 2026-10-12 09:11:18 | Seed do SENTRA, não é distro. Não aberto. |
| `SENTRA\.sentra\durable\dot-vms\SENTRA-Dot-fcd974cfccdc5636\bootstrap-attempts\1791808577-6fbb84c5\seed.iso` | 47.104 bytes | 2026-10-12 09:33:50 | Seed de bootstrap. Não aberto. |
| `SENTRA\.sentra\durable\dot-vms\SENTRA-Dot-fcd974cfccdc5636\bootstrap\seed.iso` | 47.104 bytes | 2026-10-12 13:06:22 | Seed de bootstrap. Não aberto. |
| `SENTRA\.sentra\durable\dot-vms\SENTRA-Dot-b1a7b4555800414f\system.vdi` | 5.134.876.672 bytes (4,78 GiB) | 2026-10-13 15:53:46 | Disco VirtualBox de uma VM SENTRA-Dot. Não montado. |

O `system.vdi` começa com `<<< Oracle VirtualBox Disk Image >>>`. Ao lado há `SENTRA-Dot-b1a7b4555800414f.vbox` e arquivos `Unattended-...-autounattend.xml` / `VBOXPOST.CMD`, típicos de instalação Windows não assistida. Esses arquivos não foram lidos. Não é imagem de Maratona Linux nem de BOCA. Não há `*.ova` em nenhum dos três roots.

## Papel na SEMINFO 2026

Duas maratonas em 29/10/2026 às 13h. Até 10 equipes de 3.

| Lab | Curso | Perfil | Papel de parede |
| --- | --- | --- | --- |
| Lab 3 | Sistemas de Informação | si | `seminfo\banners\lab3-si.png` |
| Lab 4 | Informática para a Internet | tii | `seminfo\banners\lab4-tii.png` |

Os dois PNG estão no disco, 1.344.849 e 1.344.512 bytes, gravados em 2026-10-13 22:33:59. O IHDR e o System.Drawing confirmam 1920×1080, 24 bpp. São o papel de parede da área de trabalho da distro, não uma página web. O HTML ao lado (`lab3-si.html`, `lab4-tii.html`) não entra no pendrive como interface. Os banners ainda aguardam ok visual; até lá não vão para a mídia.

A máquina da equipe parte desta Debian Live XFCE, ainda sem BOCA e sem o wallpaper aplicado. O juiz não vai dentro dessa ISO: fica no bocaCaramel em Docker, na mesa do professor.
