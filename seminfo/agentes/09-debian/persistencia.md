> HISTÓRICO — preparação anterior. Substituído por `seminfo/runbook.md`, `seminfo/imagem-maratona.md` e `seminfo/VALIDACAO.md`. Não executar esta receita na entrega Maratona Linux atual.

# Um boot antes de clonar

Um pendrive de cada variante, no lab dele. Lab 3 só com `ml-seminfo-lab3-si` e o IP da mesa do Lab 3. Lab 4 só com `ml-seminfo-lab4-tii` e o IP da mesa do Lab 4. A mesa está ligada, o BOCA daquele lab responde em `http://IP_DA_MESA:8000/boca`, e a regra de entrada do Windows no runbook já existe. Não é hora de gravar os dez. O runbook e o `seminfo/imagem-maratona.md` seguram a cópia até este teste passar uma vez em cada lab, e os PNGs são rascunho até alguém ver o banner na tela.

Secure Boot desligado, UEFI, como no runbook. Variante 1: o menu precisa da palavra `persistence` na linha do kernel, senão o teste olha a imagem crua e reprova à toa. Conferir no boot a tecla (`e` no GRUB, Tab no isolinux). Variante 2: não acrescente `persistence`.

Compilador e submissão não entram neste teste. Não faça login de equipe, não envie solução, não abra o placar de outra máquina. `gcc`, `g++`, `python3` e o JDK, se coube, já foram vistos na montagem.

Três resultados. Os três têm de passar no mesmo boot.

## 1. Wallpaper visível

O fundo é o banner daquele lab, 1920×1080, não o tema Debian e não o banner do outro curso.

- Lab 3: dá para ler "Sistemas de Informação" e "Laboratório 3".
- Lab 4: dá para ler "Informática para a Internet" e "Laboratório 4".

Se ainda for o papel de parede Debian, saia da sessão e entre de novo. O script `/usr/local/sbin/seminfo-wallpaper` regrava `last-image` depois que o xfdesktop cria a chave do monitor. Se o segundo login continuar errado, conferir no boot:

```
xfconf-query -c xfce4-desktop -l -v | grep last-image
ls -l /usr/local/share/seminfo/wallpaper.png
```

A propriedade tem de ser um caminho sob `/backdrop/screen0/monitor…/workspace…/last-image` e o arquivo tem de ser o PNG do lab. Não aprove um wallpaper "quase": texto do curso errado é o pendrive do outro lab.

## 2. Firefox preso

Abra o Firefox ESR pelo menu, uma vez. A primeira página tem de ser `http://IP_DA_MESA:8000/boca` e a página do BOCA tem de carregar. Ver a tela de entrada já basta. Não entre.

Na barra, peça `http://example.com/` e um endereço que não seja a mesa (por exemplo `http://1.1.1.1/`). Os dois ficam bloqueados. A barra continuar aparecendo não é falha: a trava é a política, não um quiosque.

`about:policies` mostra o serviço ativo, com `Homepage` e `WebsiteFilter`, sem erro de chave desconhecida. Se estiver inativo, a política não foi lida. Conferir no boot o caminho que o processo abre (`strace -f -e trace=file firefox-esr about:policies`) e não considere este item passado.

Controle mínimo, para não confundir "rede morta" com "navegador preso": a página do BOCA abre e o exemplo externo não abre. Se nem o BOCA abrir, o firewall ou o IP está errado e o item 2 não passa, mesmo com `example.com` bloqueado.

Não use outro navegador como prova. O pendrive trava o Firefox ESR que a imagem traz.

## 3. Ping externo falha

Na mesma sessão, com a mesa ainda acessível pelo navegador:

```
ping -c 2 -W 2 1.1.1.1
ping -c 2 -W 2 8.8.8.8
```

Nenhuma das duas recebe resposta. Timeout ou "packet filtered" passa. Resposta com tempo em milissegundos reprova.

O ping na própria mesa também pode falhar: a regra não libera ICMP echo, só TCP 8000. Isso não reprova. O que reprova é ping externo com resposta.

Confira que a exceção não virou "rede aberta":

```
curl -m 5 -I "http://IP_DA_MESA:8000/boca"
curl -m 5 -I "http://IP_DA_MESA/"
curl -m 5 -I "http://example.com/"
```

A primeira volta cabeçalho HTTP do BOCA. A segunda, outra porta da mesma mesa, falha. A terceira falha. Se `curl` não estiver no pendrive, o mesmo julgamento vale pelo Firefox: a URL do BOCA abre, `http://IP_DA_MESA/` e `http://example.com/` não.

## Variante 1, ainda neste ciclo

Este boot de lab precisa ser um boot em que a persistência já está valendo. Se `/etc/seminfo-persist-ok` não existir, você não está no overlay da `RECEITA.md`. Pare, não clone, e volte para o segundo boot de montagem descrito lá. Não transforme isso numa submissão.

## Se falhar

Não grave os outros pendrives. Não "aproveite" o que passou pela metade.

- Wallpaper do outro curso: PNG trocado. Refaça só esse lab.
- BOCA não abre e o ping externo também falha: IP errado no `nftables.conf`, DHCP não pegou endereço, ou a mesa está fora. Conferir no boot `ip -4 addr` e o IP anotado no runbook. Não abra a porta 80 nem DNS para "testar".
- BOCA abre e `example.com` também abre: o `nftables` não está ativo, ou a política do Firefox não está. `systemctl is-active nftables` na variante em que ainda há sudo. Sem sudo, o ping do item 3 já é a prova da regra.
- Wallpaper some depois de um reboot na variante 1: o menu foi iniciado sem `persistence`.
- Wallpaper some depois de um reboot na variante 2: não era para depender de persistência. A imagem gerada não levou o script ou o PNG. Refaça o `lb build` daquele lab.

## Só então

Os dois labs passaram os três itens, e alguém viu o banner certo no XFCE, não só o PNG no Windows. Aí os dez pendrives, cada um clonado da variante do seu lab. Variante 1: clonar o disco inteiro, senão a partição `persistence` não vai junto. Variante 2: `cp` do ISO híbrido, sem partição `persistence`.
