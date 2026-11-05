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
  # If variables are not set or null, use default values.
  export BOCA_DB_HOST="${BOCA_DB_HOST:-boca-db}"
  export BOCA_WEB_HOST="${BOCA_WEB_HOST:-boca-web}"

  file_env 'BOCA_DB_USER' 'bocauser'
  file_env 'BOCA_DB_PASSWORD' 'dAm0HAiC'
  file_env 'BOCA_DB_NAME' 'bocadb'
}

docker_setup_env

echo "bocadir=/var/www/boca" > /etc/boca.conf && \
echo "bdserver=$BOCA_DB_HOST" >> /etc/boca.conf && \
echo "bdcreated=y" >> /etc/boca.conf

printf "#!/bin/sh\n\
BOCAIP=%s" "$BOCA_WEB_HOST" > /etc/bocaip

until PGPASSWORD=$BOCA_DB_PASSWORD \
  psql -h "$BOCA_DB_HOST" -U "$BOCA_DB_USER" -d "$BOCA_DB_NAME" -c '\q';
do
  >&2 echo "PostgreSQL server is unavailable - sleeping"
  sleep 1
done

>&2 echo "PostgreSQL server is up - executing command"

# Use exec format to run program directly as pid 1
# https://www.padok.fr/en/blog/docker-processes-container
exec boca-autojudge
