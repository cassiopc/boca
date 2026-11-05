> HISTÓRICO — preparação anterior. Substituído por `seminfo/runbook.md`, `seminfo/imagem-maratona.md` e `seminfo/VALIDACAO.md`. Não executar esta receita na entrega Maratona Linux atual.

# Webcast e Animeitor

O Animeitor é a revelação final, em Pygame. Não é o placar ao vivo. Durante a prova o projetor usa um usuário do tipo score em `/boca/score/`, no BOCA daquele lab. O Animeitor não consulta o BOCA por API.

Repositório: https://github.com/maratona-linux/maratona-animeitor

## O que o BOCA entrega

`src/admin/report/webcast.php` só responde se existir `src/private/webcast.sep`. O exemplo versionado é `seminfo/webcast.sep.example`, uma linha:

```text
allUsers 1/1000/9000
```

Código `allUsers`, site `1`, `usernumber` de 1000 a 9000. Com `?webcastcode=allUsers` na query, `header.php` vê um código alfanumérico, manda o corpo como download e não exige sessão de admin. Sem esse parâmetro, a mesma pasta de report exige admin.

URL local:

```text
http://localhost:8000/boca/admin/report/webcast.php?webcastcode=allUsers
```

O `webcast.php` está fixo no contest 1, e esse contest está inativo e sem runs. Não projetar esse zip enquanto o contest 1 não for a prova.

Quem alcança a porta 8000 e conhece o código baixa o placar. Não publicar isso fora da rede do lab.

O PHP grava `contest`, `runs`, `time`, `version` e `icpc` e devolve o zip. O separador dos campos é o byte ASCII `0x1c`. `version` e `icpc` vêm no zip; o `load_data` do Animeitor não os lê. O `get_webcast.sh` do Animeitor espera um diretório HTTP (`contest/`, `runs/`, `time/`). Não serve para esta URL: aqui a resposta é um zip.

`pull-webcast.ps1` baixa esse zip (`-BocaUrl`, padrão `http://localhost:8000/boca`), confere a assinatura PK e abre como zip, extrai em `out/` e exige `contest`, `runs` e `time`. As entradas do BOCA se chamam `./contest` e afins; o script usa só o nome do arquivo. `out/` é produto local e não entra no git.

Depois de extrair esses três arquivos, o script lê `runs`. Os campos são separados pelo byte ASCII `0x1c` e o veredito é o último campo. Se alguma linha termina em `X`, o script avisa que o Animeitor aborta nesse `X` e segue. O `X` sozinho não faz o script falhar.

`copiar-sep.ps1 -ProjectName boca-si|boca-tii|boca-docker` mostra o `docker cp` do exemplo para `$ProjectName-boca-web-1:/var/www/boca/src/private/webcast.sep`. Sem `-Apply` o script não chama o Docker. Com `-Apply` ele executa esse `docker cp` e confere `$LASTEXITCODE` logo em seguida, sem pipe. Código diferente de zero interrompe o script.

## Como o Animeitor lê a pasta

No clone, a partir do diretório do Animeitor (Ubuntu, Python 3, Pygame):

```text
./main.py caminho/do/out
```

`Contest.load_data` abre três arquivos dentro dessa pasta.

- `contest`: nome da prova; duração, parar de responder, freeze e penalidade, em minutos; quantidade de times e de problemas; uma linha por time (`username`, instituição, nome); depois o grupo de problemas. A instituição sai do trecho entre o primeiro `[` e `]` de `userdesc`.
- `runs`: uma linha por submissão, `id`, minuto, `username`, letra do problema, veredito. A letra vira índice (`A` = 0) e tem de caber na quantidade de problemas. O veredito aceito é `Y`, `N` ou `?`. Este `webcast.php` grava `X` para erro de compilação, "contact staff" e "name mismatch"; com um `X` o Animeitor aborta no `assert`. `pull-webcast.ps1` avisa quando o último campo de uma linha de `runs` é `X` e não trata esse aviso como falha.
- `time`: um inteiro. O PHP escreve o tempo corrente da prova em segundos, ou a duração inteira quando esse marco já passou. O relógio do Animeitor trata esse número como segundos.

A primeira linha de tempos do `contest` define o freeze e o período cego da animação. Runs com minuto maior que o freeze são descartados. Do minuto cego em diante o veredito entra como `?` e a barra de espaço revela, do último colocado para o primeiro. Se o freeze no arquivo for 0, a revelação não mostra as submissões. Enquanto não entra no modo de premiação, o placar relê `runs` e `time` na mesma pasta a cada poucos segundos.

Fotos não vêm no zip. Ficam em `images/team_photos/<username>.jpg`, relativo ao diretório do Animeitor. Se faltar foto, o programa lista os logins e espera `y` ou `n` no teclado. Sem fotos, o ensaio precisa de alguém para responder.

A barra de espaço, se o freeze for antes do fim, adianta o relógio até o fim e passa à premiação. Os espaços seguintes abrem as submissões pendentes e a tela do time. `q` sai. `-w` abre janela em vez de tela cheia. O placar do Animeitor conta 20 minutos de penalidade por erro, independente do quarto campo do `contest`.

## Limite `$contest = 1`

Em `src/admin/report/webcast.php` o contest não vem da sessão:

```php
$contest = 1; //$_SESSION["usertable"]["contestnumber"];
$site = 1; //$_SESSION["usertable"]["usersitenumber"];
```

`$site` também fica em 1, igual ao exemplo do `.sep`. Há um único contest ativo por BOCA nestas maratonas, e esse contest precisa ser o número 1, com site 1. Se o número for outro, `DBContestInfo(1)` ou `DBSiteInfo(1, 1)` não acha a prova: a resposta deixa de ser zip (redireciona ou sai vazia). Conferir o arquivo `contest` extraído antes de projetar. O código não foi alterado.

O freeze da interface web não é o deste zip. No `webcast.php` o filtro de freeze das runs está comentado e `$freezeTime` é forçado para a duração da prova, então o zip leva o veredito real. Quem esconde o final na animação é o Animeitor, com os minutos do arquivo `contest`.

## Ensaio

O ensaio Pygame não rodou neste Windows. O Animeitor pede Ubuntu com Python 3 e Pygame, num contest de mentira, apontando `./main.py` para a pasta extraída. Se esse ensaio falhar, a revelação continua no placar web, com o freeze desligado no fim. Isso não impede a prova.

## Preparo 14/10/2026

Lab 3 no ar. Medição com `Invoke-WebRequest -MaximumRedirection 0`, sem senha e sem baixar o zip do placar.

O código 200 de `http://localhost:8000/boca/` e de `http://localhost:8000/boca/score/` está certo para localhost, e o HTTP não redireciona. O corpo de `/boca/score/` não é o placar: ele tem `alert` Session expired e `document.location` para `../index.php`.

`http://127.0.0.1:8000/boca/` deu 404 aqui, porque essa porta é outro processo.

| URL | Primeiro código | Destino |
| --- | --- | --- |
| `http://localhost:8000/boca/` | 200 | sem redirecionamento HTTP |
| `http://localhost:8000/boca/score/` | 200 | sem redirecionamento HTTP; o corpo manda `document.location` para `../index.php` |
| `http://127.0.0.1:8000/boca/` | 404 | outro processo nesta porta |
| `http://127.0.0.1:3001/login` | 200 | sem redirecionamento HTTP |
