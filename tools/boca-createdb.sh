#!/bin/bash
# /////////////////////////////////////////////////////////////////////////////
# //BOCA Online Contest Administrator
# //    Copyright (C) 2016- by BOCA Development Team (bocasystem@gmail.com)
# //
# //    This program is free software: you can redistribute it and/or modify
# //    it under the terms of the GNU General Public License as published by
# //    the Free Software Foundation, either version 3 of the License, or
# //    (at your option) any later version.
# //
# //    This program is distributed in the hope that it will be useful,
# //    but WITHOUT ANY WARRANTY; without even the implied warranty of
# //    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# //    GNU General Public License for more details.
# //    You should have received a copy of the GNU General Public License
# //    along with this program.  If not, see <http://www.gnu.org/licenses/>.
# /////////////////////////////////////////////////////////////////////////////
# // Last modified 15/Sep/2016 by brunoribas@utfpr.edu.br

if ! whoami | grep -q '^root$' ; then
  echo "$0 must be run as root"
  exit 1
fi

if [[ "$1" == "-h" ]]; then
  echo "Usage:"
  echo "  $0 [-f]"
  echo "     -f : Optional Parameter that enforces the (re)creation of the postgres holes"
  echo "          this also regenerate passwords"
  echo "     first time execution everything is created."
  exit 0
fi

. /etc/boca.conf

privatedir=$bocadir/src/private
postgresuser=postgres

if [[ "x$bdserver" == "x" ]]; then
  echo "Please run boca-config-dbhost"
  exit 2
fi

if [[ "x$bdcreated" == "x" || "$1" == "-f" ]] ; then
  OK=n
  if [[ "$bdserver" == "localhost" ]]; then
    echo "You need to define a password to be used in the database."
    echo "IF THIS IS A BKP SERVER, PLEASE USE THE SAME AS IN THE MAIN SERVER."
    echo -n "It is possible generate a random one. Want a random password "
    read -p "[Y/n]? " OK
  fi
  if [ "$OK" = "n" ]; then
  read -p "Enter DB password: " -s PASS
  else
    PASS=`makepasswd --char 10`
    echo "The DB password is: $PASS"
  fi
  echo "Keep the DB password safe!"

  PASSK=`makepasswd --chars 20`
  awk -v boca="$bdserver" -v pass="$PASS" -v passk="$PASSK" '{ if(index($0,"[\"dbpass\"]")>0) \
    print "$conf[\"dbpass\"]=\"" pass "\";"; \
    else if(index($0,"[\"dbhost\"]")>0) print "$conf[\"dbhost\"]=\"" boca "\";"; \
    else if(index($0,"[\"dbsuperpass\"]")>0) print "$conf[\"dbsuperpass\"]=\"" pass "\";"; \
    else if(index($0,"[\"key\"]")>0) print "$conf[\"key\"]=\"" passk "\";"; else print $0; }' \
    < $privatedir/conf.php > $privatedir/conf.php1
  mv -f $privatedir/conf.php1 $privatedir/conf.php

  if [[ "$bdserver" == "localhost" ]]; then
    su - $postgresuser -c "echo drop user bocauser | psql -d template1 >/dev/null 2>/dev/null"
    su - $postgresuser -c "echo create user bocauser createdb password \'$PASS\'| psql -d template1"
    su - $postgresuser -c "echo alter user bocauser createdb password \'$PASS\'| psql -d template1"
    #allowing outside connections
    if ! echo "$*" | grep -q notouchpgconf; then
      echo "##########################"
      echo "     ATENTION"
      echo "##########################"
      echo
      echo "I AM GIVING ACCESS TO THE DATABASE FROM ANY IP (AS LONG AS THE PASSWORD IS OK)"
      CONTINUE="y"
      printf "May I give access? [Y/n]"
      read CONTINUE

      if [[ "$CONTINUE" == "Y" || "$CONTINUE" == "y" ]]; then
        for i in /etc/postgresql/*/main/pg_hba.conf; do
          if grep -q "host.*bocadb.*bocauser" $i; then
            continue;
          fi
          echo "host bocadb bocauser 0/0 md5" >> $i
          echo "host postgres replication 0/0 md5" >> $i
        done
        for i in /etc/postgresql/*/main/postgresql.conf; do
        if ! grep -q "^[^\#]*listen_addresses" $i; then
          echo "listen_addresses = '*'" >> $i
        fi
        done
        service postgresql restart

      else
        echo "#### READ THIS ####"
        echo "If you change your mind later, you may call me again as:"
        echo "$0 -f"
        sleep 3
        echo
        echo
      fi
    fi
  fi
  if [[ "x$bdcreated" == "x" ]]; then
    echo 'bdcreated=y' >> /etc/boca.conf
  fi
fi

if ! echo "$*" | grep -q 'nocreate'; then
    php ${bocadir}/src/private/createdb.php
    chown www-data.www-data ${bocadir}/src/private/conf.php
    chmod 600 ${bocadir}/src/private/conf.php
fi

exit 0
