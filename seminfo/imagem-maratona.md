# Imagens Maratona Linux locais

Base escolhida: Ubuntu 22.04.5 Desktop amd64 com `maratona-desktop` do PPA estável oficial. Todo o sistema, linguagens e editores ficam no USB; boot e prova não dependem dos serviços centrais nem dos discos internos. A antiga Debian não é usada.

Os fundos atuais são os PNGs fornecidos pelo usuário: `banners/caramel-claro.png` e `banners/caramel-espresso.png`. A categoria TII usa sempre o claro, com a etiqueta JUNIOR; SI usa sempre o espresso, com a etiqueta PLENO. O perfil em `/etc/seminfo/lab.json` determina o fundo em cada boot, sem sorteio ou alternância durante a sessão. Os banners anteriores foram preservados como histórico. Ver [admissao-e-placares.md](admissao-e-placares.md).

## Construção

Em Windows/Python 3.12 com Docker Linux:

```powershell
python seminfo/manage.py init si
python seminfo/manage.py fetch-ubuntu
python seminfo/manage.py build-client si
python seminfo/manage.py build-client tii
```

Definir antes os endereços reais em `seminfo/local/PERFIL/lab.json` quando disponíveis. `--allow-incomplete` constrói bloqueada, com janela de solicitação: o IP da banca pode ser informado nela. O certificado da banca já está fixado na imagem; levar a identidade privada correspondente para a mesa final. Não considerar a imagem homologada sem ensaio físico.

O Ubuntu é baixado de `releases.ubuntu.com`, com retomada por ranges e SHA256 publicado pelo Ubuntu. O builder Linux extrai a ISO, instala os pacotes em chroot, aplica o overlay, recompõe squashfs/initramfs e reutiliza o boot híbrido original. A senha de manutenção é convertida para hash após normalizar finais de linha LF/CRLF; senhas BOCA não são copiadas.

A construção habilita o componente oficial `universe` e remove `snapd`/`systemd-timesyncd` conforme os conflitos declarados pelos pacotes Maratona Linux. Antes de comprimir o sistema, verifica que a conta `icpc` compila e executa as quatro linguagens sem sudo. O Casper é adaptado para aceitar `home-rw` somente no mesmo dispositivo da mídia de boot; não cria persistência de raiz nem carrega snapshots de outros discos.

O hook de boot remove as concessões de administrador do live CD em sudo/PolicyKit, corrige a propriedade da pasta da equipe e desabilita os serviços que limpam essa pasta. Os trabalhos existentes são preservados.

Saídas: `seminfo/dist/clients/PERFIL/`, com ISO, SHA256, lista de pacotes e manifesto do overlay. A área de trabalho do builder fica no volume `seminfo-client-build` para diagnóstico. Construir uma variante por vez e reservar espaço para a ISO extraída e os pacotes. Após verificar a mídia, remover apenas esse volume se não estiver em uso; não limpar o Docker inteiro.

Para retomar uma construção interrompida, conferir o diretório informado no log e definir temporariamente `$env:SEMINFO_RESUME_WORK='/work/seminfo-build.XXXXXX'` antes de repetir o comando. Após sucesso, a segunda variante reaproveita os pacotes locais da mesma base, aplicando seu próprio banner/configuração e senha de manutenção. Remover `seminfo/dist/client-work.json` se o volume de trabalho for removido.

### Atualizar uma ISO SEMINFO existente

Quando a identidade da banca ou o endereço mudar, gerar novamente o overlay
com `python seminfo/manage.py client-config tii`. O script
`client/reconfigure-image.sh` aplica esse overlay a uma ISO SEMINFO da mesma
categoria, sem baixar pacotes. Executar como root no builder Linux, com volume
de trabalho em `/work`, os scripts em `/scripts` e os arquivos de entrada
montados somente para leitura:

```bash
bash /scripts/reconfigure-image.sh /input/original.iso SHA256_ORIGINAL /out/overlay /out/atualizada.iso /maintenance-password.txt
```

O destino deve ser novo. O script confere o SHA-256 da origem, atualiza o
cliente e a senha de manutenção, testa os compiladores, recompõe o initramfs
e preserva o boot híbrido BIOS/UEFI. Não copiar credenciais BOCA ou a chave
privada da banca. Registrar o SHA-256 final e o pin público no `image.json`.
Conferir a ISO publicada com `tests/inspect_admission_images.py --profile tii
--iso /caminho/atualizada.iso --report /reports/tii-iso-inspection.json`, em
Linux com xorriso, squashfs-tools e unmkinitramfs. A inspeção estática exige
complemento por boot, aprovação e reinicialização em uma máquina do laboratório.

## Gravação em Linux

```bash
sudo bash seminfo/client/write-usb.sh --device /dev/sdX /caminho/ml-seminfo-lab3-si.iso
```

Somente USB inteiro, 32 GB ou mais, sem partições montadas. O script mostra modelo/série e exige digitar o caminho exato do dispositivo. Confere SHA256, grava, compara os bytes e cria `home-rw` ext4 no espaço restante. Só `/home` persiste. A gravação apaga o USB escolhido; identificar a mídia física antes de executar. Nenhum disco é gravado automaticamente pelo builder.

## Configuração e ensaio

PCs amd64, pelo menos 8 GB de RAM, Secure Boot desabilitado e boot USB. Entrar com `seminfo-admin` e a senha privada de `maintenance-password.txt` se faltar configuração:

```bash
sudo seminfo-configure /caminho/lab.json
```

Normalmente basta informar o IP da banca na janela e solicitar autorização, sem entrar na manutenção. O JSON administrativo continua disponível e precisa ser da própria sala; não dispensa a aprovação. Configuração inválida mantém a navegação bloqueada. Manutenção temporária: `sudo seminfo-network maintenance`; antes de liberar a equipe, `sudo seminfo-network resume`, que restaura bloqueio e agente de autorização.

Na sessão `icpc`:

```bash
seminfo-verify
mkdir -p ~/trabalhos
printf 'persistencia-ok\n' > ~/trabalhos/ensaio.txt
```

O enquadramento padrão `scaled` mantém a arte completa também em telas 4:3. Conferir claro/JUNIOR na TII e espresso/PLENO na SI, inclusive após reiniciar, e ausência de alternância durante a sessão.

O verificador compila/executa C, C++20, Java 17 e Python 3, exige autorização válida, acessa o BOCA e testa bloqueio externo. Reiniciar e conferir arquivo, fundo e regras; solicitar nova aprovação e testar outro BOCA e PC vizinho. Falha na persistência impede a homologação e replicação. `image.json` distingue ISO gerada de boot e USB efetivamente verificados.

Antes de receber os IPs, `seminfo-verify --offline` permite conferir compiladores, escrita na home e proteção dos arquivos do sistema. A variante incompleta pode mostrar falha de `seminfo-network.service` por falta do endereço; nftables continua bloqueando a saída. Depois de configurar, repetir o verificador completo. Na sessão participante, `sudo -n true` e `pkexec --disable-internal-agent /usr/bin/id` devem ser recusados.
