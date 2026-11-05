> HISTÓRICO — preparação anterior. Substituído por `seminfo/runbook.md`, `seminfo/imagem-maratona.md` e `seminfo/VALIDACAO.md`. Não executar esta receita na entrega Maratona Linux atual.

# Revisão da stack — agente 03

Não abri `boca-docker/.env`, não subi a stack e não rodei `reset`. O `docker compose config` desta revisão foi noutro diretório, com valores fictícios (`pa$word`, `abc#def`), no Docker Compose v5.5.1. O here-string do PowerShell e o heredoc do bash foram exercitados com os mesmos valores fictícios.

Há três bugs. O nome do projeto, o container, o perfil vazio e o reset não estão entre eles. Um `#` colado na senha também não quebra o Compose desta máquina.

## 1. Os três leitores do `.env` não aplicam a mesma senha

Arquivos:

- `boca_deploy.sh`, por volta da linha 48 (`source` do `.env`)
- `setup_boca_profiles.ps1`, linhas 11–26 (parser manual)
- `boca-docker/.env.example`, linhas 9–16 (não avisa a regra do Compose)

O que quebra: Compose, bash e PowerShell leem a mesma linha e entregam senhas diferentes. O container sobe com uma; `profiles` tenta conectar com outra.

Medido no Compose v5.5.1, com a mesma forma de lista usada no `docker-compose.yml` (`- PASSWORD=${PASSWORD:?...}`):

| Linha no `.env` | Compose entrega | PowerShell entrega | `source` no bash, com `set -u` |
| --- | --- | --- | --- |
| `BOCA_DB_PASSWORD=pa$word` | `pa` (aviso: `word` vazio) | `pa$word` | aborta: `word: unbound variable` |
| `BOCA_DB_PASSWORD="pa$word"` | `pa` | `pa$word` | aborta, ou interpola se `word` existir |
| `BOCA_DB_PASSWORD='pa$word'` | `pa$word` | `'pa$word'` com as aspas | `pa$word` |
| `BOCA_DB_PASSWORD=abc #def` | `abc` | `abc #def` | `abc` |
| `BOCA_DB_PASSWORD=abc#def` | `abc#def` | `abc#def` | `abc#def` |

`$$` sem aspas vira um `$` só. `export BOCA_DB_PASSWORD=...` faz o PowerShell gravar a chave `export BOCA_DB_PASSWORD`, e a variável exigida fica vazia.

O heredoc `<<PHP` (linhas 96–109) e o here-string `@"..."@` (linhas 44–57) **não** reexpandem o valor já guardado na variável. Teste do here-string com `pa$word'q` + crase + barra + `#`: o PHP gerado ficou `putenv('BOCA_DB_PASSWORD=pa$word\'q`tick\\slash#hash');`, que é o escape certo para string PHP de aspas simples. O `php_escape` do bash também: `a'b` → `a\'b`, `a\b` → `a\\b`, e o heredoc manteve `pa$word`. A corrupção acontece antes, no `source` e no parser.

Correção: não fazer `source`. Os dois scripts precisam da regra do Compose — aspas simples literais, aspas duplas e valor solto interpolam `$`, espaço antes de `#` é comentário só no valor sem aspas — e recusar `$` fora de aspas simples, que é a única forma literal. No `.env.example`, mostrar aspas simples.

```bash
# boca_deploy.sh — substituir load_env
load_env() {
  assert_boca_dir
  local line key value
  while IFS= read -r line || [ -n "$line" ]; do
    line="${line%$'\r'}"
    line="${line#"${line%%[![:space:]]*}"}"
    case "$line" in
      ''|\#*) continue ;;
      export[[:space:]]*) line="${line#export }" ;;
    esac
    case "$line" in
      *=*) ;;
      *) continue ;;
    esac
    key="${line%%=*}"
    value="${line#*=}"
    key="${key%"${key##*[![:space:]]}"}"
    value="${value#"${value%%[![:space:]]*}"}"
    if [[ "$value" == \'*\' ]]; then
      value="${value:1:${#value}-2}"
      value="${value//\\\'/\'}"
    elif [[ "$value" == \"*\" ]]; then
      value="${value:1:${#value}-2}"
      case "$value" in
        *'$'*)
          echo "$key esta entre aspas duplas e contem \$. Use aspas simples." >&2
          exit 1
          ;;
      esac
    else
      case "$value" in
        *' #'*) value="${value%% #*}" ;;
      esac
      value="${value%"${value##*[![:space:]]}"}"
      case "$value" in
        *'$'*)
          echo "$key contem \$ sem aspas simples. O Compose interpola. Use KEY='valor'." >&2
          exit 1
          ;;
      esac
    fi
    printf -v "$key" '%s' "$value"
    export "$key"
  done < "$BOCA_DOCKER_DIR/.env"
}
```

```powershell
# setup_boca_profiles.ps1 — substituir o Get-Content das linhas 11-26
Get-Content -LiteralPath $EnvFile | ForEach-Object {
    $line = $_.Trim()
    if ($line -eq '' -or $line.StartsWith('#')) { return }
    if ($line.StartsWith('export ')) { $line = $line.Substring(7).Trim() }
    $eq = $line.IndexOf('=')
    if ($eq -lt 1) { return }
    $key = $line.Substring(0, $eq).Trim()
    $value = $line.Substring($eq + 1).Trim()
    if ($value.Length -ge 2 -and $value.StartsWith("'") -and $value.EndsWith("'")) {
        $value = $value.Substring(1, $value.Length - 2).Replace("\'", "'")
    }
    elseif ($value.Length -ge 2 -and $value.StartsWith('"') -and $value.EndsWith('"')) {
        $value = $value.Substring(1, $value.Length - 2)
        if ($value.Contains('$')) {
            throw "$key usa aspas duplas com `$. No Compose isso interpola. Use aspas simples."
        }
    }
    else {
        $comment = $value.IndexOf(' #')
        if ($comment -ge 0) { $value = $value.Substring(0, $comment).TrimEnd() }
        if ($value.Contains('$')) {
            throw "$key contem `$ sem aspas simples. O Compose interpola. Use aspas simples."
        }
    }
    Set-Item -Path "Env:$key" -Value $value
}
```

```text
# boca-docker/.env.example — no lugar das linhas 9-16
# Segredo sempre entre aspas simples. Sem aspas, $nome e " #" mudam o valor
# no Compose. Aspas duplas tambem interpolam $.
BOCA_DB_PASSWORD=''
BOCA_DB_SUPER_PASSWORD=''
POSTGRES_PASSWORD=''
BOCA_PASSWORD=''
BOCA_KEY=''
```

O `docker-compose.yml` não precisa de aspas extras por causa de `#`. No v5.5.1, `abc#def` e `#secreto` sobrevivem à interpolação `${VAR:?...}` e ao YAML. O `#` só some no `.env`, quando há espaço antes dele e o valor não está entre aspas.

## 2. Grafana expande `$__env` e depois corta a senha em `$`

Arquivo: `boca-docker/grafana/provisioning/datasources/boca-postgres.yml`, linha 10.

```yaml
password: $__env{BOCA_DB_PASSWORD}
```

O que quebra: `$__env{VAR}` é a sintaxe do `grafana.ini`. No provisioning, `secureJsonData` é `values.StringMapValue`. O Grafana atual chama `setting.ExpandVar` e, em seguida, `os.ExpandEnv` no texto já expandido (`pkg/services/provisioning/values/values.go`, `interpolateValue`). A variável `BOCA_DB_PASSWORD` do container entra na primeira passada. Se a senha for `Pa$sword` e não existir `sword` no container, a segunda passada grava `Pa`. A documentação do provisioning mostra exatamente isso: `${PASSWORD}` com valor `Pa$sw0rd` vira `Pa`; a forma de uma passada só é `$PASSWORD`.

A env está no serviço `grafana` do `docker-compose.yml` (linha 31). O arquivo montado não passa pelo interpolador do Compose, então o `$` chega inteiro ao Grafana. O defeito é a segunda expansão, não a ausência da variável.

Correção:

```yaml
secureJsonData:
  password: $BOCA_DB_PASSWORD
```

Não usar `${BOCA_DB_PASSWORD}`: essa forma é a que a própria documentação marca como expansão dupla.

## 3. Se o `php` falha, `inject.php` fica em `/boca/`

Arquivos:

- `boca_deploy.sh`, linhas 110–114
- `setup_boca_profiles.ps1`, linhas 63–68

O que quebra: os dois gravam a senha em `/var/www/boca/src/inject.php` e só apagam depois do `php` terminar. Com `set -e` no bash, e com `$ErrorActionPreference = 'Stop'` no PowerShell, uma falha do `php` sai antes do `rm`. O `finally` do PowerShell apaga só o arquivo local.

`tools/000-boca.conf` faz `Alias /boca /var/www/boca/src` e libera esse diretório (`Allow from all`, linhas 4–19). `src/private` é que está com `Deny from all`. Enquanto `inject.php` existir, `http://<mesa>:8000/boca/inject.php` executa o script que troca as senhas do contest e do admin. O `inject_profiles.php` local está no `.gitignore` (linha 20), mas também fica no disco se o bash morre no meio.

Correção: copiar para `/tmp`, fora do alias, e apagar nos dois lados mesmo quando o `php` falha.

```bash
# boca_deploy.sh — no lugar das linhas 110-114
container="${PROJECT_NAME}-boca-web-1"
remote=/tmp/inject-profiles.php
cleanup_inject() {
  docker exec "$container" rm -f "$remote" >/dev/null 2>&1 || true
  rm -f "$temp_file"
}
trap cleanup_inject EXIT
docker cp "$temp_file" "$container:$remote" >/dev/null
docker exec "$container" php "$remote"
```

```powershell
# setup_boca_profiles.ps1 — no lugar das linhas 63-72
$container = "$ProjectName-boca-web-1"
$remote = '/tmp/inject-profiles.php'
try {
    docker cp $tempFile "${container}:${remote}" | Out-Null
    if ($LASTEXITCODE -ne 0) { throw "docker cp falhou ($LASTEXITCODE)" }
    $response = docker exec $container php $remote
    if ($LASTEXITCODE -ne 0) { throw "php do perfil falhou ($LASTEXITCODE)" }
    Write-Host $response
}
finally {
    docker exec $container rm -f $remote | Out-Null
    Remove-Item $tempFile -Force -ErrorAction SilentlyContinue
}
```

## O que foi conferido e não é bug

- Perfil vazio. No bash, `PROFILE=""` cai em `boca-docker` (linhas 19–21). No PowerShell, omitir `-Profile` deixa `$Profile` nulo e o `default` do `switch` (linhas 24–27) faz o mesmo. Perfil diferente de `si` ou `tii` é rejeitado nos dois.
- Nome do container. Não há `container_name` nem `name:` nos compose. Os scripts usam `$ProjectName-boca-web-1` / `${PROJECT_NAME}-boca-web-1`, que é o padrão do Compose v2: `boca-si-boca-web-1`, `boca-tii-boca-web-1`, `boca-docker-boca-web-1`. `--project-name` está em `boca_deploy.ps1` linha 30 e em `boca_deploy.sh` linha 62, e tem precedência sobre o nome da pasta. Não liguei container para ver o nome ao vivo.
- Reset. `down -v` recebe o mesmo `--project-name` do perfil. O prompt cita esse nome (`boca_deploy.ps1` linhas 137–142, `boca_deploy.sh` linhas 160–165). Sem perfil, apaga o projeto `boca-docker`, não `boca-si` nem `boca-tii`. Não apaga o projeto errado em silêncio.
- `deploy.mode: global` em `docker-compose.prod.yml` vale para Swarm. Os scripts chamam `docker compose`, não `docker stack deploy`. Isso não troca o sufixo `-1` do container.
- Here-string e heredoc, como acima: o escape de `'`, `\`, `$`, crase e `#` no PHP gerado está certo.
- `#` sem espaço antes, no Compose v5.5.1: o valor chega inteiro ao container. Não há o que aspejar no `docker-compose.yml` por esse motivo.
- Portas `8000` e `127.0.0.1:3001` iguais nos dois perfis. O runbook põe um BOCA por mesa, então não é defeito de reset. Dois perfis na mesma máquina não sobem juntos.

## Observação fora da lista

Não entra no diff pedido, mas está no caminho da senha que o compose injeta:

- `boca-docker/boca/src/private/conf.php`, linha 32: `getenv('BOCA_DB_PASSWORD') ? ... : "dAm0HAiC"`. Em PHP a string `"0"` é falsa, e a senha cai no padrão histórico, que já foi público.
- `boca-docker/docker/dev/web/init.sh`, linhas 76 e 84: `PGPASSWORD=$BOCA_DB_PASSWORD` sem aspas, e a senha entra crua no SQL `PASSWORD '$BOCA_DB_PASSWORD'`. Um `$` ou uma aspa simples que sobreviva ao `.env` ainda quebra a criação do usuário na imagem. O deploy usa a imagem `ghcr.io/joaofazolo/boca-docker/boca-web:latest`; não conferi se o binário publicado é este `init.sh`.
