#!/bin/bash
#========================================================================
# Copyright Universidade Federal do Espirito Santo (Ufes)
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <https://www.gnu.org/licenses/>.
# 
# This program is released under license GNU GPL v3+ license.
#
#========================================================================

# usage: file_env VAR [DEFAULT]
#    ie: file_env 'XYZ_DB_PASSWORD' 'example'
# (will allow for "$XYZ_DB_PASSWORD_FILE" to fill in the value of
#  "$XYZ_DB_PASSWORD" from a file, especially for Docker's secrets feature)
# Ref: https://github.com/docker-library/postgres/blob/master/docker-entrypoint.sh
file_env() {
  local var="$1"
  local fileVar="${var}_FILE"
  local def="${2:-}"
  if [ "${!var:-}" ] && [ "${!fileVar:-}" ];
  then
    echo >&2 "error: both $var and $fileVar are set (but are exclusive)"
    exit 1
  fi
  local val="$def"
  if [ "${!var:-}" ];
  then
    val="${!var}"
  elif [ "${!fileVar:-}" ];
  then
    val="$(< "${!fileVar}")"
  fi
  export "$var"="$val"
  unset "$fileVar"
}

# Loads various settings that are used elsewhere in the script
# This should be called before any other functions
# Ref: https://github.com/docker-library/postgres/blob/master/docker-entrypoint.sh
docker_setup_env() {
  # If variable is not set or null, use default value.
  export BOCA_DB_HOST="${BOCA_DB_HOST:-boca-db}"

  file_env 'BOCA_DB_SUPER_USER' 'postgres'
  file_env 'BOCA_DB_SUPER_PASSWORD'
  file_env 'BOCA_DB_USER' 'bocauser'
  file_env 'BOCA_DB_PASSWORD' 'dAm0HAiC'
  file_env 'BOCA_DB_NAME' 'bocadb'
  file_env 'BOCA_PASSWORD'
  file_env 'BOCA_KEY'
}

docker_setup_env

until PGPASSWORD=$BOCA_DB_SUPER_PASSWORD \
  psql -h "$BOCA_DB_HOST" -U "$BOCA_DB_SUPER_USER" -c '\q';
do
  >&2 echo "PostgreSQL server is unavailable - sleeping"
  sleep 1
done
  
>&2 echo "PostgreSQL server is up - executing command"

# https://stackoverflow.com/questions/14549270/check-if-database-exists-in-postgresql-using-shell
if ! PGPASSWORD=$BOCA_DB_PASSWORD \
  psql -h "$BOCA_DB_HOST" -U "$BOCA_DB_USER" -lqt | \
  cut -d \| -f 1 | grep -qw "$BOCA_DB_NAME";
then
  echo "Create unprivileged user"
  PGPASSWORD=$BOCA_DB_SUPER_PASSWORD \
    psql -h "$BOCA_DB_HOST" -U "$BOCA_DB_SUPER_USER" -t -c \
      "DROP USER IF EXISTS $BOCA_DB_USER;\
       CREATE USER $BOCA_DB_USER WITH PASSWORD '$BOCA_DB_PASSWORD';"

  echo "Grant privileges to unprivileged user"
  PGPASSWORD=$BOCA_DB_SUPER_PASSWORD \
    psql -h "$BOCA_DB_HOST" -U "$BOCA_DB_SUPER_USER" -d "$BOCA_DB_NAME" \
      -t -c \
      "GRANT ALL PRIVILEGES ON DATABASE $BOCA_DB_NAME TO $BOCA_DB_USER; \
        GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public \
        TO $BOCA_DB_USER; \
        GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public \
        TO $BOCA_DB_USER; \
        ALTER DATABASE $BOCA_DB_NAME SET lo_compat_privileges=on; \
        GRANT USAGE ON SCHEMA public TO $BOCA_DB_USER;"

  # Create database only if it does not exist
  if ! PGPASSWORD=$BOCA_DB_PASSWORD \
    psql -h "$BOCA_DB_HOST" -U "$BOCA_DB_USER" -lqt | \
    cut -d \| -f 1 | grep -qw "$BOCA_DB_NAME";
  then
    echo "Create database"
    # https://stackoverflow.com/questions/5891888/piping-data-into-command-line-php
    cd /var/www/boca/src && echo "YES" | php private/createdb.php

    echo "Grant privileges to unprivileged user"
    PGPASSWORD=$BOCA_DB_SUPER_PASSWORD \
      psql -h "$BOCA_DB_HOST" -U "$BOCA_DB_SUPER_USER" -t -c \
        "GRANT ALL PRIVILEGES ON DATABASE $BOCA_DB_NAME TO $BOCA_DB_USER;"
  else
    echo "Database already exists"
  fi
else
  echo "Database and unprivileged user already exist"
fi

# Use exec format to run program directly as pid 1
# https://www.padok.fr/en/blog/docker-processes-container
exec apache2 -DFOREGROUND
