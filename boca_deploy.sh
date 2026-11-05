#!/bin/bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")" && pwd)"
BOCA_DOCKER_DIR="$ROOT_DIR/boca-docker"
BOCA_URL="http://localhost:8000/boca"
ACTION="${1:-}"
PROFILE="${2:-}"

if [[ "$PROFILE" == si || "$PROFILE" == tii ]]; then
  case "$ACTION" in
    profiles) ACTION=prepare ;;
    reset) echo 'SEMINFO preserva os dados: use backup e restauracao isolada.' >&2; exit 1 ;;
    restart)
      python3 "$ROOT_DIR/seminfo/manage.py" stop "$PROFILE"
      exec python3 "$ROOT_DIR/seminfo/manage.py" start "$PROFILE"
      ;;
    open) exec xdg-open "$BOCA_URL" ;;
  esac
  exec python3 "$ROOT_DIR/seminfo/manage.py" "$ACTION" "$PROFILE"
fi

case "$PROFILE" in
  si)
    PROJECT_NAME="boca-si"
    PROFILE_LABEL="Lab 3 — Sistemas de Informação"
    ;;
  tii)
    PROJECT_NAME="boca-tii"
    PROFILE_LABEL="Lab 4 — Informática para a Internet"
    ;;
  "")
    PROJECT_NAME="boca-docker"
    PROFILE_LABEL="local"
    ;;
  *)
    echo "Perfil invalido: $PROFILE (use si ou tii)" >&2
    exit 1
    ;;
esac

write_section() {
  printf '\n%s\n' "Caramel Coders BOCA - $PROFILE_LABEL - $1"
}

assert_boca_dir() {
  if [ ! -d "$BOCA_DOCKER_DIR" ]; then
    echo "Diretorio nao encontrado: $BOCA_DOCKER_DIR" >&2
    exit 1
  fi
  if [ ! -f "$BOCA_DOCKER_DIR/.env" ]; then
    echo "Crie $BOCA_DOCKER_DIR/.env a partir de .env.example antes de operar a stack." >&2
    exit 1
  fi
}

load_env() {
  assert_boca_dir
  local line key value
  while IFS= read -r line || [ -n "$line" ]; do
    line="${line%$'\r'}"
    line="${line#"${line%%[![:space:]]*}"}"
    case "$line" in
      ''|\#*) continue ;;
      export[[:space:]]*)
        line="${line#export }"
        line="${line#"${line%%[![:space:]]*}"}"
        ;;
    esac
    case "$line" in
      *=*) ;;
      *) continue ;;
    esac
    key="${line%%=*}"
    value="${line#*=}"
    key="${key%"${key##*[![:space:]]}"}"
    value="${value#"${value%%[![:space:]]*}"}"
    case "$key" in
      [A-Za-z_][A-Za-z0-9_]*) ;;
      *)
        echo "Chave invalida no .env: $key" >&2
        exit 1
        ;;
    esac
    if [[ "$value" == \'*\' ]]; then
      value="${value:1:${#value}-2}"
      case "$value" in
        *\'*)
          echo "$key tem aspas simples no meio. O Compose fecha a citacao ali." >&2
          exit 1
          ;;
      esac
    elif [[ "$value" == \"*\" ]]; then
      value="${value:1:${#value}-2}"
      case "$value" in
        *'$'*|*\\*)
          echo "$key esta entre aspas duplas com \$ ou barra. Use aspas simples: ${key}='valor'." >&2
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
          echo "$key contem \$ sem aspas simples. O Compose interpola. Use ${key}='valor'." >&2
          exit 1
          ;;
      esac
    fi
    printf -v "$key" '%s' "$value"
    export "$key"
  done < "$BOCA_DOCKER_DIR/.env"
}

test_prerequisites() {
  docker --version >/dev/null
  docker compose version --short >/dev/null
  docker info >/dev/null
}

invoke_compose() {
  assert_boca_dir
  (
    cd "$BOCA_DOCKER_DIR"
    docker compose --project-name "$PROJECT_NAME" -f docker-compose.yml -f docker-compose.prod.yml "$@"
  )
}

wait_boca() {
  local attempt=0
  while [ "$attempt" -lt 36 ]; do
    if curl -fsS "$BOCA_URL" >/dev/null 2>&1; then
      return 0
    fi
    attempt=$((attempt + 1))
    sleep 5
  done
  return 1
}

php_escape() {
  printf '%s' "$1" | sed "s/\\\\/\\\\\\\\/g; s/'/\\\\'/g"
}

apply_profiles() {
  load_env
  local temp_file db_pass contest_user contest_pass admin_user admin_pass container
  : "${BOCA_DB_PASSWORD:?BOCA_DB_PASSWORD vazio}"
  : "${BOCA_CONTEST_USER:?BOCA_CONTEST_USER vazio}"
  : "${BOCA_CONTEST_PASSWORD:?BOCA_CONTEST_PASSWORD vazio}"
  : "${BOCA_ADMIN_USER:?BOCA_ADMIN_USER vazio}"
  : "${BOCA_ADMIN_PASSWORD:?BOCA_ADMIN_PASSWORD vazio}"
  db_pass="$(php_escape "$BOCA_DB_PASSWORD")"
  contest_user="$(php_escape "$BOCA_CONTEST_USER")"
  contest_pass="$(php_escape "$BOCA_CONTEST_PASSWORD")"
  admin_user="$(php_escape "$BOCA_ADMIN_USER")"
  admin_pass="$(php_escape "$BOCA_ADMIN_PASSWORD")"
  temp_file="$ROOT_DIR/inject_profiles.php"
  cat > "$temp_file" <<PHP
<?php
putenv('BOCA_DB_HOST=boca-db');
putenv('BOCA_DB_PASSWORD=$db_pass');
require_once '/var/www/boca/src/db.php';
\$c = DBConnect();
\$contestPass = hash('sha256', '$contest_pass');
\$adminPass = hash('sha256', '$admin_pass');
DBExec(\$c, "ALTER TABLE usertable ALTER COLUMN username TYPE varchar(50)");
DBExec(\$c, "UPDATE usertable SET username='$contest_user', userfullname='Systems', userpassword='\$contestPass', usersession='', usersessionextra='' WHERE contestnumber=0 AND usersitenumber=1 AND usernumber=1");
DBExec(\$c, "INSERT INTO usertable (contestnumber, usersitenumber, usernumber, username, userfullname, userdesc, usertype, userenabled, usermultilogin, userpassword, userip, userlastlogin, usersession, usersessionextra, userlastlogout, userpermitip, updatetime, usericpcid, userinfo) VALUES (0, 1, 1000, '$admin_user', 'Administrator', NULL, 'admin', 't', 't', '\$adminPass', NULL, NULL, '', '', NULL, NULL, CAST(EXTRACT(EPOCH FROM now()) AS int), '', '') ON CONFLICT (contestnumber, usersitenumber, usernumber) DO UPDATE SET username=EXCLUDED.username, userfullname=EXCLUDED.userfullname, usertype=EXCLUDED.usertype, userenabled=EXCLUDED.userenabled, usermultilogin=EXCLUDED.usermultilogin, userpassword=EXCLUDED.userpassword, usersession='', usersessionextra='', updatetime=CAST(EXTRACT(EPOCH FROM now()) AS int)");
echo "Perfis aplicados com sucesso!\n";
?>
PHP
  container="${PROJECT_NAME}-boca-web-1"
  remote=/tmp/inject-profiles.php
  cleanup_inject() {
    docker exec -u root "$container" rm -f "$remote" >/dev/null 2>&1 || true
    rm -f "$temp_file"
  }
  trap cleanup_inject EXIT
  docker cp "$temp_file" "$container:$remote" >/dev/null
  docker exec "$container" php "$remote"
  cleanup_inject
  trap - EXIT
}

install_boca() {
  write_section "Install"
  test_prerequisites
  invoke_compose up -d --build
  wait_boca
  apply_profiles
  printf '%s\n' "BOCA: $BOCA_URL"
  printf '%s\n' "Grafana: http://localhost:3001"
  printf '%s\n' "Projeto Compose: $PROJECT_NAME"
}

start_boca() {
  write_section "Start"
  invoke_compose up -d
  wait_boca
  printf '%s\n' "BOCA: $BOCA_URL"
  printf '%s\n' "Projeto Compose: $PROJECT_NAME"
}

stop_boca() {
  write_section "Stop"
  invoke_compose down
}

status_boca() {
  write_section "Status"
  invoke_compose ps
}

logs_boca() {
  write_section "Logs"
  invoke_compose logs -f --tail=50
}

restart_boca() {
  write_section "Restart"
  invoke_compose restart
  wait_boca
  printf '%s\n' "BOCA: $BOCA_URL"
}

reset_boca() {
  write_section "Reset"
  read -r -p "Digite SIM para remover containers e volumes de $PROJECT_NAME: " confirmation
  if [ "$confirmation" != "SIM" ]; then
    echo "Operacao cancelada."
    return
  fi
  invoke_compose down -v
}

open_boca() {
  if command -v xdg-open >/dev/null 2>&1; then
    xdg-open "$BOCA_URL" >/dev/null 2>&1 &
    return
  fi
  if command -v open >/dev/null 2>&1; then
    open "$BOCA_URL"
    return
  fi
  printf '%s\n' "$BOCA_URL"
}

show_menu() {
  while true; do
    write_section "Menu"
    printf '%s\n' "1. install"
    printf '%s\n' "2. start"
    printf '%s\n' "3. stop"
    printf '%s\n' "4. status"
    printf '%s\n' "5. logs"
    printf '%s\n' "6. restart"
    printf '%s\n' "7. reset"
    printf '%s\n' "8. profiles"
    printf '%s\n' "9. open"
    printf '%s\n' "0. sair"
    read -r -p "Escolha uma opcao: " choice
    case "$choice" in
      1) install_boca ;;
      2) start_boca ;;
      3) stop_boca ;;
      4) status_boca ;;
      5) logs_boca ;;
      6) restart_boca ;;
      7) reset_boca ;;
      8) apply_profiles ;;
      9) open_boca ;;
      0) exit 0 ;;
      *) echo "Opcao invalida." ;;
    esac
  done
}

case "$ACTION" in
  install) install_boca ;;
  start) start_boca ;;
  stop) stop_boca ;;
  status) status_boca ;;
  logs) logs_boca ;;
  restart) restart_boca ;;
  reset) reset_boca ;;
  profiles) apply_profiles ;;
  open) open_boca ;;
  *) show_menu ;;
esac
