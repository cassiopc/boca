> HISTÓRICO — preparação anterior. Substituído por `seminfo/runbook.md`, `seminfo/imagem-maratona.md` e `seminfo/VALIDACAO.md`. Não executar esta receita na entrega Maratona Linux atual.

# SEMINFO 2026 — duas maratonas, dois BOCAs

Quinta-feira 29/10/2026, início às 13h, em paralelo. O programa está em `Atividades SEMINFO 2026.pdf`.

| Lab | Prova | Perfil Compose | Pendrive |
| --- | --- | --- | --- |
| 3 | Superior, Bacharelado em Sistemas de Informação | `si` (`boca-si`) | `ml-seminfo-lab3-si` |
| 4 | Médio/Técnico em Informática para a Internet | `tii` (`boca-tii`) | `ml-seminfo-lab4-tii` |

Cada lab tem a própria máquina do professor, o próprio banco e o próprio placar. Um lab não consulta o outro. Até 10 equipes de 3 pessoas. No BOCA isso é um login por equipe. O pendrive da equipe sai da Debian Live XFCE em `C:\Users\vitor\OneDrive\Desktop\SENTRA\Isos\debian-live-13.7.0-amd64-xfce.iso`. O papel de parede dessa área de trabalho é `banners/lab3-si.png` ou `banners/lab4-tii.png`, ainda esperando o ok visual.

A duração não está fechada no PDF. O bloco escrito é 13:00–14:40 e a tarde de quinta não lista outra atividade nesses labs. Preencha a duração abaixo antes de ativar os dois contests. Freeze e “parar de responder” só entram nessa hora. O padrão ICPC (freeze 60 minutos antes do fim, silêncio 15 minutos) serve para uma prova longa. Num slot de 1h40 ele come a prova inteira.

- Duração confirmada: ______ minutos
- Stop answering: ______ minutos
- Stop scoreboard: ______ minutos
- Penalidade: 20 minutos, salvo decisão contrária
- Linguagens: ______

As questões são da Cristiane. Este repositório não guarda o enunciado da prova. O formato de pacote já usado aqui está em `doc/problemsCaramel/`.

## Antes do dia

1. Em cada mesa, Docker Desktop aberto e este repositório atualizado.
2. Confira `boca-docker/.env`. Troque as senhas que já circularam no git público. O histórico do GitHub continua com os valores antigos mesmo depois que saírem dos arquivos.
3. Lab 3: `.\boca_deploy.ps1 install si`
4. Lab 4: `.\boca_deploy.ps1 install tii`
5. Anote o IP da mesa em cada lab. As equipes usam `http://IP:8000/boca`. O número não está neste arquivo.
6. Rode o firewall na mesa do professor, no lab, em PowerShell elevado, antes de a equipe sentar. Não rode na máquina de preparo. O prefixo real vai em `-Subnet`. Ele não está neste arquivo. A porta 3001 não ganha regra.

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\seminfo\agentes\05-rede\firewall-mesa.ps1 -Subnet PREFIXO_DESTE_LAB
```

O Grafana escuta só em `127.0.0.1:3001`. Não publique essa porta.

7. Entre com `BOCA_CONTEST_USER` e crie o contest. Duração, stop answering e stop scoreboard continuam em branco acima: não ative, e não grave o 300, 285 e 240 do formulário, enquanto esses três tempos estiverem vazios. Depois do Activate, o admin da prova é o usuário `admin`, com `BOCA_PASSWORD`. `BOCA_ADMIN_USER` fica no contest 0. Entrar com esse nome volta ao contest modelo. O clique está em `seminfo/agentes/06-contest/ROTEIRO.md`.
8. Cadastre um site local, as linguagens combinadas e até 10 usuários do tipo team. Três alunos compartilham o login da equipe.
9. Importe o zip de cada problema. Rode o juiz automático num solução correta e numa errada antes de abrir a sala.
10. Crie um usuário do tipo score, por exemplo `placar`, só para o projetor.

## Projetor, durante a prova

O placar ao vivo é o do próprio BOCA, no navegador da mesa ou do PC do projetor, em tela cheia:

`http://IP-DA-MESA:8000/boca/`

com o usuário score daquele lab. A página fica em `/boca/score/`. Atualiza sozinha. Não use o PC de uma equipe para isso, e não aponte o Lab 3 para o IP do Lab 4.

## Animeitor, na revelação

O [maratona-animeitor](https://github.com/maratona-linux/maratona-animeitor) é o placar de encerramento, em Pygame. Ele não consulta o BOCA por API. Ele lê a pasta de webcast que o BOCA já sabe gerar (`src/admin/report/webcast.php`): arquivos `contest`, `runs` e `time`, campos separados por ASCII 0x1c.

Na mesa do professor, com o contest ativo:

1. Copie `seminfo/webcast.sep.example` para dentro do container, em `/var/www/boca/src/private/webcast.sep`. O arquivo é uma linha: `allUsers 1/1000/9000` (código, site, primeiro usuário, último usuário).
2. Baixe o zip. O código na URL dispensa login de admin; quem tiver o código e alcançar a porta 8000 baixa o placar. Use a rede do lab, não publique isso na internet.

```powershell
curl.exe -fL "http://localhost:8000/boca/admin/report/webcast.php?webcastcode=allUsers" -o webcast.zip
Expand-Archive webcast.zip -DestinationPath webcast -Force
```

3. Num Ubuntu com Python 3 e Pygame, no clone do animeitor: `./main.py caminho/do/webcast`. Barra de espaço avança a revelação. Fotos de equipe, se existirem, ficam em `images/team_photos/` com o nome do usuário.
4. O `webcast.php` deste código fixa `$contest = 1`. Com um único contest ativo por BOCA, é o caso destas maratonas. Se o número do contest for outro, o zip sai vazio ou de outra prova: confira o arquivo `contest` antes de projetar.

Este Windows não rodou o Pygame. O ensaio do animeitor é na máquina Ubuntu, com um contest de mentira. Se o ensaio falhar, a revelação continua no placar web, com o freeze desligado no fim. Isso não impede a prova.

## Se o pendrive não sobe

Secure Boot desligado, UEFI, 8 GB de RAM. A partição de persistência segue `seminfo/agentes/09-debian/RECEITA.md`. Teste um pendrive de cada variante antes de gravar os dez. Quarta 28/10 o Lab 3 tem minicurso às 13h. O Lab 4 está livre nesse horário no PDF.

A imagem da equipe e os caminhos do wallpaper, do Firefox e do nftables estão em `imagem-maratona.md`. A receita para gravar o pendrive é `seminfo/agentes/09-debian/RECEITA.md`.

## Banners

`banners/lab3-si.png` e `banners/lab4-tii.png` são rascunhos para validação. Não entram na ISO antes do ok visual. O fundo sem texto é `banners/fundo.jpg`.
