# Agente 08 — segredos ainda versionados

Varredura com `git grep` no que o Git rastreia. Não abri `boca-docker/.env`. Não copiei valores: só arquivo, linha, nome da variável e prefixo de no máximo 4 caracteres. Não houve `git commit` nem `git push`.

`master` está igual a `origin/master` (0 à frente, 0 atrás). Ponta: `8a37338`. O literal Caramel entrou no commit `f2dd5dd` (`Caramel Coders BOCA`) e segue nesse HEAD. `boca-docker/.env` nunca teve commit.

## Veredito de push

Não é seguro tratar um push como limpeza.

`git push` envia commits, não a working tree. Como a ponta já está no GitHub, um push agora não muda o remoto e não apaga nada. Os segredos Caramel continuam no blob do commit `8a37338`, que o GitHub já tem, e em todo o histórico desde `f2dd5dd`.

A working tree rastreada já não contém o prefixo `Ur#t` (`git grep` vazio). Essa limpeza está só modificada, sem commit. Commitar esses arquivos e só então dar push tira o literal da ponta. O histórico antigo permanece. Não reescrever história. Senha e chave Caramel já publicadas devem ser trocadas fora do Git.

O ignore de `.env` também está só na working tree (`boca-docker/.gitignore` e `.gitignore` na raiz). No HEAD commitado essa regra ainda não existe. O arquivo em si nunca foi rastreado. `.env.example` não está rastreado; as variáveis de senha e chave estão vazias.

## Segredos Caramel (ainda no HEAD, fora da working tree)

Prefixo comum das senhas: `Ur#t`. Prefixo da chave: `CCAI`. Não estão mais nesses caminhos na working tree.

| Arquivo | Linha | Nome |
|---|---|---|
| `README` | 36 | senha do contest (rótulo «Senha contest») |
| `README` | 38 | senha do admin (rótulo «Senha admin») |
| `README` | 40 | senha do Grafana (rótulo «Senha Grafana») |
| `boca-docker/docker-compose.yml` | 5 | `BOCA_DB_SUPER_PASSWORD` |
| `boca-docker/docker-compose.yml` | 6 | `BOCA_PASSWORD` |
| `boca-docker/docker-compose.yml` | 7 | `BOCA_KEY` (prefixo `CCAI`) |
| `boca-docker/docker-compose.yml` | 17 | `BOCA_DB_SUPER_PASSWORD` |
| `boca-docker/docker-compose.yml` | 18 | `BOCA_PASSWORD` |
| `boca-docker/docker-compose.yml` | 19 | `BOCA_KEY` (prefixo `CCAI`) |
| `boca-docker/docker-compose.yml` | 30 | `GF_SECURITY_ADMIN_PASSWORD` |
| `boca-docker/docker-compose.yml` | 42 | `POSTGRES_PASSWORD` |
| `boca_deploy.sh` | 55 | literal dentro de `hash('sha256', …)` do contest |
| `boca_deploy.sh` | 56 | literal dentro de `hash('sha256', …)` do admin |
| `setup_boca_profiles.ps1` | 7 | literal dentro de `hash('sha256', …)` do contest |
| `setup_boca_profiles.ps1` | 8 | literal dentro de `hash('sha256', …)` do admin |

Na mesma `docker-compose.yml` do HEAD, linha 29, `GF_SECURITY_ADMIN_USER` é nome de conta (prefixo `CCAS`), não senha. O README do HEAD também lista nomes de conta nas linhas vizinhas às senhas. `boca_deploy.ps1` no HEAD não tem o prefixo `Ur#t`.

Na working tree, `docker-compose.yml` e o datasource do Grafana só referenciam variável (`${BOCA_…}` e `$__env{BOCA_DB_PASSWORD}`). Os scripts leem `boca-docker/.env`. Nada disso está commitado.

## Default público do upstream (não é segredo Caramel)

O fallback histórico da imagem, variável `BOCA_DB_PASSWORD` (e o par de superusuário), prefixo `dAm0`. É o default público do upstream, separado das senhas Caramel. Continua no HEAD e na working tree, porque esses arquivos não foram alterados.

| Arquivo | Linha | Nome |
|---|---|---|
| `src/private/conf.php` | 32 | `$conf["dbpass"]` |
| `src/private/conf.php` | 35 | `$conf["dbsuperpass"]` |
| `boca-docker/boca/src/private/conf.php` | 32 | fallback de `BOCA_DB_PASSWORD` |
| `boca-docker/boca/src/private/conf.php` | 35 | fallback de `BOCA_DB_SUPER_PASSWORD` |
| `boca-docker/docker/dev/jail/init.sh` | 57 | `file_env` de `BOCA_DB_PASSWORD` |
| `boca-docker/docker/dev/web/init.sh` | 58 | `file_env` de `BOCA_DB_PASSWORD` |
| `boca-docker/tests/env/.env` | 5 | `BOCA_DB_PASSWORD` |
| `boca-docker/tests/env/.env` | 9 | `BOCA_DB_SUPER_PASSWORD` |
| `boca_deploy.sh` (só no HEAD) | 52 | `putenv` de `BOCA_DB_PASSWORD` |
| `setup_boca_profiles.ps1` (só no HEAD) | 4 | `putenv` de `BOCA_DB_PASSWORD` |
| `boca-docker/grafana/provisioning/datasources/boca-postgres.yml` (só no HEAD) | 10 | `secureJsonData.password` |

Esse datasource commitado usa o default upstream, não a senha Caramel do Postgres. Na working tree a linha 10 passou a `$__env{BOCA_DB_PASSWORD}`.

Outros defaults públicos do mesmo `conf.php`, linha 46 `$conf["basepass"]` / fallback de `BOCA_PASSWORD` (prefixo `boca`) e linha 50 `$conf["key"]` / fallback de `BOCA_KEY` (prefixo `GG56`). Estão em `src/private/conf.php` e em `boca-docker/boca/src/private/conf.php`. Não são a chave Caramel (prefixo `CCAI`), que só aparece no compose do HEAD.

## Fixtures de teste do upstream

Placeholders do repositório `boca-docker`, não credencial Caramel. Prefixo `supe` é a senha de superusuário dos composes de teste; `mypa` e `myke` são senha e chave de exemplo.

| Arquivo | Linha | Nome | Prefixo |
|---|---|---|---|
| `boca-docker/tests/env/.env` | 12 | `BOCA_PASSWORD` | `mypa` |
| `boca-docker/tests/env/.env` | 15 | `BOCA_KEY` | `myke` |
| `boca-docker/tests/env/.env2` | 4 | `POSTGRES_PASSWORD` | `supe` |
| `boca-docker/tests/secrets/boca_key.txt` | 1 | fixture de `BOCA_KEY` | `myke` |
| `boca-docker/tests/secrets/boca_password.txt` | 1 | fixture de `BOCA_PASSWORD` | `mypa` |
| `boca-docker/tests/secrets/db_password.txt` | 1 | fixture de senha do banco | `mypa` |
| `boca-docker/tests/secrets/db_super_password.txt` | 1 | fixture de senha do superusuário | `supe` |
| `boca-docker/tests/e2e/docker-compose.yml` | 33 e 49 | `BOCA_DB_SUPER_PASSWORD`, `POSTGRES_PASSWORD` | `supe` |
| `boca-docker/tests/healthcheck/docker-compose.yml` | 32 e 53 | idem | `supe` |
| `boca-docker/tests/migrations/docker-compose.yml` | 32, 47, 63 e 108 | `BOCA_DB_SUPER_PASSWORD` / `POSTGRES_PASSWORD` | `supe` |
| `boca-docker/tests/networks/docker-compose.yml` | 36 e 56 | idem | `supe` |
| `boca-docker/tests/platforms/docker-compose.yml` | 33 e 49 | idem | `supe` |
| `boca-docker/tests/volumes/docker-compose.yml` | 32 e 47 | idem | `supe` |

`tools/control-machines.sh`, linha 58, variável `password`, prefixo `pass`: placeholder do script upstream.

Não há chave privada (`BEGIN PRIVATE KEY`), token de nuvem nem token de API rastreado no HEAD nem na working tree. Referências `${{ secrets.GITHUB_TOKEN }}` nos workflows e o texto «cole-o-token-aqui» em `CLOUDFLARE_TUNNEL.md` não são segredo embutido. O título em `boca-docker/grafana/dashboards/boca-analytics.json`, linha 362, é rótulo do painel (prefixo `CCAI`), não a `BOCA_KEY`.
