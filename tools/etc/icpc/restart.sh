#!/bin/bash
uid=`id -u`
if [ "$uid" != "0" ]; then
  echo "Must be root to run this script. Use sudo /bin/bash first"
  exit 1
fi

apt-get clean
if [ -f /etc/icpc/postgresql.version ]; then
 . /etc/icpc/postgresql.version
else
POSTGRESV=""
if [ ! -f /etc/init.d/postgresql ]; then
  POSTGRESV="-8.4"
fi
fi

pass=\$`echo -n icpc | makepasswd --clearfrom - --crypt-md5 | cut -d'$' -f2-`
usermod -p "$pass" icpc

rm -f /etc/icpc/.isserver
rm -f /etc/icpc/.firsttimedone
rm -f /etc/icpc/bocaserver.sh
/etc/icpc/cleandisk.sh
/etc/init.d/apache2 stop
/etc/init.d/postgresql$POSTGRESV stop
rm -f /var/log/apache2/*
rm -f /var/log/postgresql/*
update-rc.d -f apache2 remove
update-rc.d -f postgresql$POSTGRESV remove

