> HISTÓRICO: em 16/10/2026 foi confirmado que Médio/Técnico e Graduação usam questões diferentes e bancas independentes. Não seguir as instruções abaixo sobre repetir os mesmos ZIPs nas duas salas. Usar ../../documentacao/manual-if.md.

> HISTÓRICO — preparação anterior. Substituído por `seminfo/runbook.md`, `seminfo/imagem-maratona.md` e `seminfo/VALIDACAO.md`. Não executar esta receita na entrega Maratona Linux atual.

# Contest BOCA — SEMINFO 2026

Quinta 29/10/2026, 13h. Dois BOCAs independentes, um por lab. Até 10 equipes de 3. No BOCA isso é um login por equipe, não um login por aluno.

| Lab | Prova | Quem sobe a stack | Projeto |
| --- | --- | --- | --- |
| 3 | Superior, Sistemas de Informação | `.\boca_deploy.ps1 install si` | `boca-si` |
| 4 | Médio, Informática para a Internet | `.\boca_deploy.ps1 install tii` | `boca-tii` |

Este roteiro começa com a stack já no ar. Não sobe Docker, não mexe no banco e não leva senha. Na mesa do professor o BOCA é `http://localhost:8000/boca`. As equipes e o projetor usam `http://IP-DESTE-LAB:8000/boca`.

Os rótulos abaixo são os da tela do BOCA, em inglês. Cada `Send`, `Activate` e `Import` abre `Confirm?`. Confirme. Sem isso o clique não grava.

- [Contest (system)](#1-contest-system)
- [Ativar](#2-ativar)
- [Admin](#3-admin)
- [Site](#4-site)
- [Linguagens](#5-linguagens)
- [EQUIPE01 a EQUIPE10](#6-equipes)
- [Placar](#7-placar)
- [Importar ZIP](#8-importar-zip)

## O que repetir e o que não compartilhar

Repita o roteiro inteiro em cada mesa. O clique numa não aparece na outra.

Não compartilhe:

- O banco. `boca-si` e `boca-tii` têm Postgres separados. Não aponte um contest para o outro e não use contest multi-site.
- A senha de equipe. Cada lab gera as suas, no papel, só daquele lab. Não reutilize a senha de staff, não copie o `.env` e não escreva senha neste arquivo.
- A sessão. O projetor do Lab 3 entra no IP do Lab 3. O do Lab 4, no IP do Lab 4.

Os ZIPs podem ser os mesmos arquivos nos dois labs. A importação é em cada BOCA. Subir o pacote num admin não enche o outro banco.

O que muda de um lab para o outro, e só isso:

| Campo | Lab 3 si | Lab 4 tii |
| --- | --- | --- |
| Name do contest | `SEMINFO 2026 SI` | `SEMINFO 2026 TII` |
| Name do site | `Lab 3 SI` | `Lab 4 TII` |
| User Description das equipes | `[SI][Sistemas de Informação]` | `[TII][Informática para a Internet]` |
| Description do placar | `Projetor Lab 3` | `Projetor Lab 4` |

## Staff, sem abrir o .env

O `install` grava dois usuários no contest 0, a partir do `boca-docker/.env` daquela máquina. Não leia o arquivo para copiar senha.

- `BOCA_CONTEST_USER` é o usuário system do contest 0. É o login da primeira tela.
- `BOCA_ADMIN_USER` também está no contest 0. O login olha o contest 0 antes do contest ativo. Entrar com esse nome depois do Activate volta ao contest modelo, não à prova.

O contest novo nasce com outro admin: username `admin`, número 1000, senha base daquela máquina (`BOCA_PASSWORD`). É esse o login da área admin. A mensagem na tela, ao ativar, repete a senha. Não a copie para cá, para o git nem para o outro lab.

## Duração em branco

A hora de início está fechada: 29/10/2026, 13:00. A duração não está. Não invente minuto.

- Duração (minutos): ______
- Stop answering (minutos a partir do início): ______
- Stop scoreboard (minutos a partir do início): ______
- Penalidade: 20 minutos, salvo decisão contrária. Se mudar: ______

`Stop answering` é o minuto em que a equipe deixa de receber o veredito. `Stop scoreboard` é o minuto em que o placar congela. Os dois contam desde o início, não são horário de relógio. Enquanto as três linhas estiverem vazias, não calcule freeze nem silêncio.

O formulário vem preenchido com 300, 285 e 240. Isso é o padrão do programa no instante em que se clica `new`, não é a prova. Não ative com esses números.

Se a duração ainda não foi fechada, pode criar o contest no passo 1 e parar. Volte quando os três números existirem: entre de novo com `BOCA_CONTEST_USER`, abra `Contest`, escolha o número sem asterisco, troque os três campos, `Send`, e só então `Activate`.

## 1. Contest (system)

- [ ] Lab 3
- [ ] Lab 4

1. Abra `http://localhost:8000/boca`.
2. Em `Name`, o valor de `BOCA_CONTEST_USER`. Em `Password`, a senha desse usuário no `.env` desta mesa. Não use `BOCA_ADMIN_USER` aqui.
3. Clique em `Contest`. O combo `Contest number` mostra o contest 0, o modelo. Não é a prova. Não o ative.
4. No mesmo combo, escolha `new`. O BOCA cria um contest inativo e abre o formulário. O número que aparecer (em geral 1) é o desta prova. O asterisco no combo marca o contest ativo. O novo ainda não tem asterisco.
5. Preencha, ou confira:

| Campo na tela | O que colocar |
| --- | --- |
| Name | o nome deste lab, na tabela acima |
| Start date | `13` : `00` e `29` / `10` / `2026`. Apague o "agora + 10 minutos" que o formulário sugere |
| Duration (in minutes) | o número anotado acima, ou pare se ainda estiver em branco |
| Stop answering (in minutes) | o número anotado acima |
| Stop scoreboard (in minutes) | o número anotado acima |
| Penalty (in minutes) | `20`, salvo o valor anotado |
| Max file size allowed for teams (in KB) | deixe o que já veio, salvo fonte maior do que isso |
| Contest main site URL | vazio |
| Contest main site number | `1` |
| Contest local site number | `1` |

Os dois labs usam site 1. Não são sede e filial. Não coloque o IP do outro lab na URL.

Não clique em `Activate` neste passo se a duração ainda estiver em branco. `Send` também grava Duration, Stop answering e Stop scoreboard: não dê `Send` enquanto esses três campos da tela ainda forem 300, 285 e 240.

## 2. Ativar

- [ ] Lab 3
- [ ] Lab 4

Só com os três tempos já escritos no papel e no formulário.

1. `Send`, confirme, e confira se o nome e a data continuam na tela.
2. `Activate`, confirme. O contest passa a ser o ativo (asterisco nesse número, não no 0).
3. O BOCA desloga sozinho e mostra a senha padrão do admin. É a senha base desta máquina. Não anote nesta página.
4. Se ativou cedo demais com 300/285/240, não comece a prova assim. Siga o passo 3 e corrija com `Update Contest and All Sites` antes do dia 29.

## 3. Admin

- [ ] Lab 3
- [ ] Lab 4

1. De novo em `http://localhost:8000/boca`.
2. `Name`: `admin`. `Password`: a senha base desta máquina, a mesma da mensagem. Não entre com `BOCA_ADMIN_USER`.
3. Abra `Contest` e confira o nome `SEMINFO 2026 SI` ou `SEMINFO 2026 TII`. Se aparecer o contest modelo, você entrou no contest 0. Saia em `Logout` e entre com `admin`.
4. Não clique em `Become Main Site`. Não clique em `Delete ALL clars`, `Delete ALL runs`, `Delete ALL tasks` nem `Delete ALL bkps`.
5. Se mais tarde a duração for corrigida, altere os campos nesta tela e use `Update Contest and All Sites`, não o `Update` simples. O `Update` simples não copia o relógio para o site. `Start Now` e `Stop Now` ficam na aba Site e não são o jeito de abrir esta prova.

A aba `Answers` já vem com YES, erro de compilação, runtime, tempo, apresentação, resposta errada e contact staff. Não edite e não apague.

## 4. Site

- [ ] Lab 3
- [ ] Lab 4

Clique em `Site`. O site 1 já existe, criado junto com o contest, com o relógio padrão daquele instante. O nome que veio (`Site`) não é o do lab. O `Activate` não regrava esta tela.

| Campo na tela | O que colocar |
| --- | --- |
| Site number | `1`. Deve constar `(local site)`. Não escolha `new` |
| Name | `Lab 3 SI` ou `Lab 4 TII` |
| Start date | `13` : `00` e `29` / `10` / `2026`, igual ao contest |
| Duration | os mesmos minutos do contest |
| Stop answering | os mesmos minutos do contest |
| Stop scoreboard | os mesmos minutos do contest |
| Runs/clars that will be judged here | `1` |
| Tasks that will be treated here | `1` |
| Username of the chief judge | vazio |
| Active | marcado |
| Autoend | marcado. A prova encerra sozinha ao fim da duração |
| Global score | `1` |
| Autojudge (without human interaction) | desmarcado. O manual pede para não ligar isso sem ensaio |
| Score level | deixe o `3` que já veio. Não é decisão deste roteiro |

`Send` e confirme. A linha `Logins are:` deve continuar `enabled`.

Não aperte `Start Now`, `Stop Now`, `Logoff all users`, `Disable logins` nem nenhum `Delete ALL`. Esses botões existem para falha de energia e para limpar um aquecimento. Não são o início da prova.

## 5. Linguagens

- [ ] Lab 3
- [ ] Lab 4

Clique em `Languages`. Ao criar o contest o BOCA já insere linguagens. A tabela que aparece não é a lista do SEMINFO. Não invente uma lista fechada e não apague nada enquanto os quatro itens abaixo estiverem em branco.

Marque aqui só o que for combinado. O que ficar sem marca não está decidido.

- [ ] C
- [ ] C++
- [ ] Java
- [ ] Python

A tela não tem estes checkboxes. Ela tem número, nome e extensão. Quando a lista for fechada, o que ficar de fora se apaga clicando no número. São dois confirms. Apagar uma linguagem apaga também tudo o que estiver ligado a ela. Durante a prova, não clique no número.

O nome não pode ter espaço. Repetir o mesmo número e dar `Send` atualiza a linha. Não cria outra.

Se a linha já existir, a extensão é o que precisa bater com o ZIP, não o apelido:

| Se ficar | Nome que o BOCA já grava | Extensão |
| --- | --- | --- |
| C | `C` | `c` |
| C++ | `C++20` | `cpp` |
| Java | `Java` | `java` |
| Python | `Python3` | `py3` |

A tabela pode ter outras linhas, inseridas pelo próprio BOCA. Não as trate como combinadas. Só apague uma delas se a lista fechada mandar.

Para incluir uma que não está na tabela: próximo número livre, nome sem espaço, extensão igual ao arquivo de script do ZIP, `Send`, confirme.

## 6. Equipes

- [ ] Lab 3
- [ ] Lab 4

Clique em `Users`. Crie dez usuários, um por vez, de `EQUIPE01` a `EQUIPE10`. Três alunos compartilham esse login. Não crie trinta usuários.

O número 1000 é o admin. O formulário ignora gravação com esse número. Não use 1000 para equipe nem para o placar.

Em cada uma, preencha e dê `Send`:

| Campo na tela | O que colocar |
| --- | --- |
| User Site Number | `1` |
| User Number | `1` para EQUIPE01, `2` para EQUIPE02, até `10` para EQUIPE10 |
| Username | `EQUIPE01` … `EQUIPE10`, com o zero. Só letras e dígitos. Cabe em 20 caracteres |
| ICPC ID | vazio |
| Type | `Team` |
| Enabled | `Yes` |
| MultiLogins | `No`. A própria tela pede isso para equipe local. Os três alunos usam o mesmo computador |
| User Full Name | `[EQUIPE01] Equipe 01`, trocando o número. O colchete é o nome curto no placar |
| User Description | o prefixo deste lab, na tabela do início, e os três nomes quando a lista existir. Enquanto não existir, deixe só o prefixo |
| User IP | vazio. O campo aceita 20 caracteres; sem IP não há trava de máquina |
| Password e Retype Password | uma senha desta equipe, neste lab. Os dois campos iguais e preenchidos. Não deixe em branco e não escreva a senha aqui |
| Allow password change | `No` |
| Admin (this user) Password | a senha do `admin` com o qual você está logado. Sem ela a tela responde que a senha de admin está errada |

Confirme. A equipe tem de aparecer na tabela com type `team` e Enabled `Yes`.

Se uma equipe for usar duas máquinas ao mesmo tempo, volte no número dessa equipe (o clique no número de usuário edita, não apaga) e mude `MultiLogins` para `Yes`. Nos problemas e nas linguagens o clique no número apaga. Não misture as duas telas.

Senha errada: clique o número, preencha `Password` e `Retype Password` de novo e a senha de admin. Se esses dois ficarem vazios, a senha antiga permanece.

Não use `Import` nesta prova. O arquivo de importação geraria outra senha e pediria para anotar na hora. Aqui a senha sai no papel do lab.

## 7. Placar

- [ ] Lab 3
- [ ] Lab 4

Ainda em `Users`, mais um usuário. É a conta do projetor. Não julga, não vê código, não administra.

| Campo na tela | O que colocar |
| --- | --- |
| User Site Number | `1` |
| User Number | `11` |
| Username | `placar` |
| ICPC ID | vazio |
| Type | `Score` |
| Enabled | `Yes` |
| MultiLogins | `Yes` |
| User Full Name | `Placar` |
| User Description | `Projetor Lab 3` ou `Projetor Lab 4` |
| User IP | vazio |
| Password e Retype Password | senha só deste placar, diferente da do outro lab e diferente das equipes. Não a escreva aqui |
| Allow password change | `No` |
| Admin (this user) Password | a senha do `admin` desta sessão |

`Send` e confirme.

No computador do projetor, não no PC de uma equipe: `http://IP-DESTE-LAB:8000/boca/`. Entre com `placar`. A página cai em `/boca/score/`. O menu é `Score` e `Logout`. Deixe em tela cheia. Não aponte o projetor para o IP do outro lab.

## 8. Importar ZIP

- [ ] Lab 3
- [ ] Lab 4

As questões são da Cristiane e entram depois. Um ZIP por questão, no formato de pacote do BOCA. Não cole enunciado em lugar nenhum, nem nesta página.

O formato já usado neste repositório está em `doc/problemsCaramel/`: `A.zip` até `M.zip`. Cada ZIP traz, na raiz, as pastas `compare/`, `compile/`, `description/`, `input/`, `limits/`, `output/`, `run/` e `tests/`. Em `description/` tem de existir `problem.info`, com as tags `basename`, `fullname` e `descfile`. Os scripts de `compare/`, `compile/`, `limits/` e `run/` são arquivos cujo nome é a extensão da linguagem: `c`, `cpp`, `java`, `kt`, `py3`. A extensão da aba Languages tem de ser esse nome. Python, neste pacote, é `py3`, não `py`.

`times_boca.txt` e `times_teams.txt` são anotações de limite. Não são ZIP e não entram em `Problems`.

Se a Cristiane entregar outros ZIPs, importe os dela. Os arquivos da pasta podem ser os mesmos nos dois labs. A importação, não: faça o upload em cada admin.

Clique em `Problems`. Há uma linha fake. Não é questão. Não importe por cima dela e não tente apagá-la.

Para cada ZIP, na ordem das letras:

| Campo na tela | O que colocar |
| --- | --- |
| Number | `1` para A, `2` para B, e assim por diante. Outro conjunto começa em 1 na ordem que ela entregar |
| Short Name | a letra, sem espaço: `A`, `B`, `C`… |
| Problem package (ZIP) | o arquivo. A tela exige o pacote |
| Color name | vazio, salvo balão combinado |
| Color (RGB HTML format) | vazio, ou seis dígitos sem `#` |
| Autojudge Setting | `Everything`, salvo outra ordem |

`Send` e confirme. O nome curto não pode ter espaço. O mesmo Number de novo substitui o pacote sem apagar antes.

Clicar no número do problema apaga o problema e tudo o que estiver ligado a ele, inclusive submissões. Não clique no número durante a prova.

Não use o botão `Build`. Ele monta um ZIP a partir de outro formulário. O pacote da Cristiane já vem pronto.

Quando os dois labs tiverem os mesmos arquivos, confira em cada um a tabela: mesmo Number, mesma letra, pacote com hash visível. Um lab preenchido não vale pelo outro.

## Antes de sair da mesa

- [ ] Lab 3: contest ativo com o nome SI, site `Lab 3 SI`, data 29/10/2026 13:00, duração igual à que foi escrita acima (não 300, se esse não for o número).
- [ ] Lab 4: o mesmo, com o nome TII e o site `Lab 4 TII`.
- [ ] Linguagens: só as marcadas na seção 5, quando a lista deixar de estar em branco. Até lá, a tabela do BOCA continua como o programa criou.
- [ ] Dez equipes, `EQUIPE01` … `EQUIPE10`, type team, e o `placar`, type score. Senhas só no papel daquele lab.
- [ ] Cada ZIP importado nos dois BOCAs. Nenhum enunciado copiado para fora do ZIP.
- [ ] Projetor de cada lab testado com `placar` no IP da própria mesa.
