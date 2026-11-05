> HISTÓRICO — preparação anterior. Substituído por `seminfo/runbook.md`, `seminfo/imagem-maratona.md` e `seminfo/VALIDACAO.md`. Não executar esta receita na entrega Maratona Linux atual.

# Ensaio único — SEMINFO 2026

Hoje, no calendário do orquestrador, é 13/10/2026. A prova é quinta 29/10/2026, às 13h, nos dois labs ao mesmo tempo. Do dia 13 ao dia 29 faltam 16 dias. O PDF `Atividades SEMINFO 2026.pdf` só detalha terça 27, quarta 28 e quinta 29. Os dias 14 a 26/10 não aparecem nele. Este ensaio não marca horário nenhum nesses dias.

Uma sessão só. Uma máquina no Lab 3 e uma máquina no Lab 4, na mesma janela. Não é a prova e não é a gravação dos dez pendrives.

## Horário

Quarta-feira 28/10/2026, 9:00–11:00.

O que o PDF ocupa, e por que esta janela é a única que cabe:

| Quando | Lab 3 | Lab 4 | Serve para os dois juntos? |
| --- | --- | --- | --- |
| Terça 27, 13:00–17:00 | Minicurso, Jonatan, Mobile. O quadro repete MINICURSOS em 13:00–14:40 e em 15:00–17:00, com intervalo 14:40–15:00 | Minicurso, Fabio, Fundamentos de Programação Competitiva, no mesmo bloco | Não |
| Quarta 28, manhã, até 11:00 | Nenhuma linha. Palestra Leonardo (7h) e palestra Yago (10h) estão em Matemática / OU CAO GUIA | Nenhuma linha | Sim |
| Quarta 28, 11:40–13:00 | Almoço no quadro geral | Almoço no quadro geral | Não |
| Quarta 28, 13:00–17:00 | Minicurso, Jonatan, Mobile. O quadro da tarde continua MINICURSOS até 17:00. O PDF não diz que o Lab 3 esvazia às 14:40 | Sem linha às 13h. Os outros minicursos desse horário são Lab 2 e Lab 8 | Não. O Lab 4 sem linha não libera o Lab 3 |
| Quinta 29, 13:00 em diante | Maratona Superior (SI) | Maratona Médio (TII) | Não. É a prova |

A manhã de quinta não entra como segundo ensaio. O PDF não lista os labs antes das 13h nesse dia, e esta sessão já é a única.

Às 9:00–9:30 o quadro tem coffee break. Às 9:30–11:00 o quadro diz palestra do Iago e a tabela detalhada diz palestra do Yago às 10h. As duas linhas estão em Matemática / OU CAO GUIA, não no Lab 3 nem no Lab 4. O ensaio termina às 11:00, antes do almoço (11:40) e duas horas antes do Mobile do Jonatan (13:00).

## O que cada máquina tem de ser

| | Lab 3 | Lab 4 |
| --- | --- | --- |
| Prova | Superior, Bacharelado em Sistemas de Informação | Médio/Técnico em Informática para a Internet |
| Perfil na mesa | `si` (`boca-si`) | `tii` (`boca-tii`) |
| Pendrive | `ml-seminfo-lab3-si` | `ml-seminfo-lab4-tii` |
| Wallpaper | `banners/lab3-si.png` | `banners/lab4-tii.png` |
| BOCA | `http://IP-da-mesa-do-Lab-3:8000/boca` | `http://IP-da-mesa-do-Lab-4:8000/boca` |
| Placar do projetor | `/boca/score/` dessa mesa, usuário score deste lab | `/boca/score/` dessa mesa, usuário score deste lab |

O IP é o que foi anotado na mesa. Não se usa o IP do outro lab. Senhas não entram neste papel.

A mesa de cada lab já está com o próprio BOCA e com um contest de teste. Este ensaio não instala de novo e não abre o contest da prova.

## Checklist

Conferir os dois lados na mesma sessão. Cada linha é uma máquina, não o conjunto de pendrives do lab.

1. Pendrive Debian sobe. Antes de ligar, o rótulo na mão é o da tabela. Secure Boot desligado, UEFI, como no runbook. A máquina entra no Debian daquele pendrive. Se não sobe, a linha dessa máquina para aqui. Isso não autoriza repetir o ensaio noutro dia.
2. O wallpaper é o do lab certo. Lab 3 mostra o banner de Sistemas de Informação. Lab 4 mostra o de Informática para a Internet. Os dois PNGs são os de `seminfo/banners/`.
3. O browser abre o BOCA da mesa certa e não o do outro lab. A primeira página é `http://IP-da-mesa-deste-lab:8000/boca`. Não é o IP do outro lab.
4. Uma submissão de teste aparece no placar do projetor. Entra com o usuário de equipe de teste deste lab, envia uma solução de teste e olha o projetor deste lab, em tela cheia, no `/boca/score/` desta mesa. A submissão está nesse placar. O PC de uma equipe não serve de projetor.
5. O zip de webcast baixa. Na mesa deste lab, o zip daquele BOCA desce e abre com `contest`, `runs` e `time`. O `contest` de dentro é o contest de teste deste lab.

As duas máquinas passam as cinco linhas, ou o ensaio não passou. Não se grava o restante dos pendrives enquanto uma das duas falhar.

## O que aborta

Parar na hora. Não seguir a checklist, não “conferir no outro lab para comparar” e não apontar o projetor para o IP do outro.

- Pendrive no lab errado. `ml-seminfo-lab3-si` no Lab 4, ou `ml-seminfo-lab4-tii` no Lab 3. O mesmo aborto vale se o wallpaper ou a homepage forem os do outro lab: o pendrive que subiu não é o desta sala. Desliga, tira o pendrive, marca o rótulo e encerra a sessão.
- Placar do outro contest. O projetor do Lab 3 mostra a prova do Lab 4 (TII), ou o do Lab 4 mostra a do Lab 3 (SI). O zip de webcast conta como o mesmo aborto quando o `contest` de dentro é o do outro lab, ou quando vem vazio porque o número do contest não é o deste BOCA. Não se projeta esse placar.

Com qualquer um dos dois, o ensaio único acaba. O conserto fica para antes das 13h de quinta 29/10. Quinta de manhã não vira um segundo ensaio, e o bloco de 13h de quinta é a prova.

## Comandos do ensaio

Quarta 28/10/2026, 9:00. Uma mesa por lab.

- Stack na mesa: no Lab 3, `.\boca_deploy.ps1 install si`. No Lab 4, `.\boca_deploy.ps1 install tii`. Em 14/10/2026, nesta máquina de preparo, `.\boca_deploy.ps1 install si` subiu o Lab 3, `http://localhost:8000/boca/` respondeu 200 com o título BOCA Online Contest Administrator boca-1.5.24 - Login, e o Lab 4 não subiu porque a porta 8000 ficou com o `boca-si`.
- Firewall da mesa, primeiro sem regra: `.\seminfo\agentes\05-rede\ensaiar-mesa.ps1 -Subnet PREFIXO_DESTE_LAB`. A regra de verdade continua `seminfo/agentes/05-rede/firewall-mesa.ps1`, só no lab, em PowerShell elevado.
- Pendrive: `seminfo/agentes/09-debian/scripts/aplicar-ip.sh` recebe o IPv4 da mesa na hora. Os templates seguem com o token `IP_DA_MESA`.
- Webcast, sem Docker ainda: `.\seminfo\agentes\04-webcast\ensaiar.ps1`. O `-Apply` fica para quando o container estiver no ar.
- Login: `BOCA_CONTEST_USER` cria o contest e não ativa enquanto duração, stop answering e stop scoreboard estiverem em branco. Depois do Activate, o usuário é `admin` e a senha é `BOCA_PASSWORD`. O banco confirma: contest 1 chama SEMINFO 2026 SI e segue inativo. O contest 0 continua ativo. O new já gravou duration 300, stop answering 285 e stop scoreboard 240. Site 1 chama Lab 3 SI. siteip continua 127.0.0.1/boca. No contest 1 só existe o usuário admin, número 1000. As linguagens que nasceram com ele são C, C++20, Java, Kotlin e Python3, sem que isso feche o IP do lab nem o ok das linguagens, que continuam em branco.
