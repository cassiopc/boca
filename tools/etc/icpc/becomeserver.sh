#!/bin/bash
# ////////////////////////////////////////////////////////////////////////////////
# //BOCA Online Contest Administrator
# //    Copyright (C) 2003-2014 by BOCA Development Team (bocasystem@gmail.com)
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
# ////////////////////////////////////////////////////////////////////////////////
# // Last modified 15/aug/2014 by cassio@ime.usp.br
for i in id chown chmod cut awk grep cat sed makepasswd ifconfig iptables php touch mkdir update-rc.d su rm mv; do
  p=`which $i`
  if [ -x "$p" ]; then
    echo -n ""
  else
    echo command "$i" not found
    exit 1
  fi
done
bkpserver=0
if [ "$1" == "bkp" ]; then
  if [ "$2" == "" ]; then
    echo "Usage $0 bkp <IP-number-of-main-server>"
    exit 1
  else
    bkpserver=$2
  fi
fi

if [ "`id -u`" != "0" ]; then
  echo "Must be run as root"
  exit 1
fi
bocadir=/var/www/boca
[ -r /etc/boca.conf ] && . /etc/boca.conf

privatedir=$bocadir/src/private
if [ ! -d $privatedir ]; then
  echo "Could not find directory $privatedir"
  exit 1
fi

apacheuser=
[ -r /etc/icpc/apacheuser ] && apacheuser=`cat /etc/icpc/apacheuser | sed 's/ \t\n//g'`
[ "$apacheuser" == "" ] && apacheuser=www-data
id -u $apacheuser >/dev/null 2>/dev/null
if [ $? != 0 ]; then
  echo "User $apacheuser not found -- error to set permissions with chown/chmod"
  apacheuser=root
fi

postgresuser=postgres
id -u $postgresuser >/dev/null 2>/dev/null
if [ $? != 0 ]; then
  echo "User $postgresuser not found -- maybe you use another name (then update this script) or postgres is not installed"
  exit 1
fi

grep -iq "iface.*eth0.*inet.*static" /etc/network/interfaces
if [ $? != 0 ]; then
	echo "*****************************************"
    echo "IMPORTANT NOTICE ************************"
	echo "Network interface eth0 has to be set with"
	echo "a static IP address for this computer to "
	echo "be a proper server -- DO IT ASAP ********"
	echo "*****************************************"
	sleep 2
fi

BOCASERVER=localhost
if [ -f /etc/icpc/postgresql.version ]; then
 . /etc/icpc/postgresql.version
else
POSTGRESV=""
if [ ! -f /etc/init.d/postgresql ]; then
  POSTGRESV="-8.4"
fi
fi
if [ ! -f /etc/init.d/postgresql$POSTGRESV ]; then
  echo "I did not find the correct version of postgres -- please check it and update this script"
  exit 1
fi

for i in `ls /etc/postgresql/*/main/pg_hba.conf`; do
  grep -q "host.*bocadb.*bocauser" $i
  if [ $? != 0 ]; then
   echo "############"
   echo "I AM GIVING ACCESS TO THE DATABASE FROM ANY IP (AS LONG AS THE PASSWORD IS OK)"
   echo "In order to improve security, it is possible to alter the file $i"
   echo "and perform a finer tune. Nevertheless, if the password of the DB is safe, there is no big threat"
   echo "For doing that, I am using the line:"
   echo ""
   echo -e "echo \"host bocadb bocauser 0/0 md5\" >> $i"
   echo -e "echo \"host postgres replication 0/0 md5\" >> $i"
   echo ""
   echo "==> IDEALLY FOR IMPROVED SECURITY, REPLACE THE FIRST 0/0 ABOVE (IN THAT FILE) WITH THE IP ADDRESS OF THE AUTOJUDGE MACHINE <=="
   echo "==> IF YOU HAVE MULTIPLE AUTOJUDGE MACHINES, WRITE ONE LINE FOR EACH IP ADDRESS THERE IN THE FILE <=="
   echo "==> IDEALLY FOR IMPROVED SECURITY, REPLACE THE SECOND 0/0 ABOVE (FOR REPLICATION) WITH THE IP ADDRESS OF THE REPLICATION MACHINE <=="
   echo "############"
   echo "host bocadb bocauser 0/0 md5" >> $i
   echo "host postgres replication 0/0 md5" >> $i
  else
   echo "############"
   echo "IT SEEMS YOU ALREADY HAVE MODIFIED THE FILE $i WITH BOCA'S INFORMATION"
   echo "I WOULD USE THE LINE:"
   echo ""
   echo -e "echo \"host bocadb bocauser 0/0 md5\" >> $i"
   echo -e "echo \"host postgres replication 0/0 md5\" >> $i"
   echo ""
   echo "to give access to the database to other computers, but"
   echo ">>> I'M NOT DOING IT -- PLEASE CHECK IT <<<"
   echo "############"
  fi
done

for i in `ls /etc/postgresql/*/main/postgresql.conf`; do
grep -q "^[^\#]*listen_addresses" $i
if [ $? != 0 ]; then
  echo "listen_addresses = '*'" >> $i
fi
done
for i in `ls /etc/postgresql/*/main/postgresql.conf`; do
grep -q "^[^\#]*max_connections" $i
if [ $? != 0 ]; then
  echo "max_connections = 100" >> $i
fi
done
for i in `ls /etc/postgresql/*/main/postgresql.conf`; do
grep -q "^[^\#]*maintenance_work_mem" $i
if [ $? != 0 ]; then
  echo "maintenance_work_mem = 64MB" >> $i
fi
done
for i in `ls /etc/postgresql/*/main/postgresql.conf`; do
grep -q "^[^\#]*shared_buffers" $i
if [ $? != 0 ]; then
  echo "shared_buffers = 128MB" >> $i
fi
done
for i in `ls /etc/postgresql/*/main/postgresql.conf`; do
grep -q "^[^\#]*work_mem" $i
if [ $? != 0 ]; then
  echo "work_mem = 4MB" >> $i
fi
done
for i in `ls /etc/postgresql/*/main/postgresql.conf`; do
grep -q "^[^\#]*max_wal_senders" $i
if [ $? != 0 ]; then
  echo "max_wal_senders = 3" >> $i
fi
done
for i in `ls /etc/postgresql/*/main/postgresql.conf`; do
grep -q "^[^\#]*wal_level" $i
if [ $? != 0 ]; then
  echo "wal_level = hot_standby" >> $i
fi
done
for i in `ls /etc/postgresql/*/main/postgresql.conf`; do
grep -q "^[^\#]*wal_keep_segments" $i
if [ $? != 0 ]; then
  echo "wal_keep_segments = 100" >> $i
fi
done

# for i in `ls /etc/postgresql/*/main/postgresql.conf`; do
# grep -q "^[^\#]*archive_mode" $i
# if [ $? != 0 ]; then
#   echo "archive_mode = on" >> $i
# fi
# done
# for i in `ls /etc/postgresql/*/main/postgresql.conf`; do
# grep -q "^[^\#]*archive_command" $i
# if [ $? != 0 ]; then
#   echo "archive_command = 'test ! -f /var/www/pg_archive/%f.gz && gzip < %p > /var/www/pg_archive/%f.gz && chmod 640 /var/www/pg_archive/%f.gz''" >> $i
# fi
# done
# mkdir -p /var/www/pg_archive
# chown postgres:icpcadmin /var/www/pg_archive
# chmod 6770 /var/www/pg_archive

echo "You need to define a password to be used in the database."
echo "IF THIS IS A BKP SERVER, PLEASE USE THE SAME AS IN THE MAIN SERVER."
echo -n "It is possible generate a random one. Want a random password "
read -p "[Y/n]? " OK
if [ "$OK" = "n" ]; then
 read -p "Enter DB password: " -s PASS
else
 PASS=`makepasswd --char 10`
 echo "The DB password is $PASS"
fi
echo "Keep the DB password safe!"
echo "The IP address that is computer is using is"
echo "(check using the command ifconfig, if desired. Use this address to configure other computers)"
ifconfig eth0 | grep -i "inet addr"

PASSK=`makepasswd --chars 20`
awk -v boca="$BOCASERVER" -v pass="$PASS" -v passk="$PASSK" '{ if(index($0,"[\"dbpass\"]")>0) \
  print "$conf[\"dbpass\"]=\"" pass "\";"; \
  else if(index($0,"[\"dbhost\"]")>0) print "$conf[\"dbhost\"]=\"" boca "\";"; \
  else if(index($0,"[\"dbsuperpass\"]")>0) print "$conf[\"dbsuperpass\"]=\"" pass "\";"; \
  else if(index($0,"[\"key\"]")>0) print "$conf[\"key\"]=\"" passk "\";"; else print $0; }' \
  < $privatedir/conf.php > $privatedir/conf.php1
mv -f $privatedir/conf.php1 $privatedir/conf.php
echo "Deny from all" > $privatedir/.htaccess
chown -R $apacheuser.root $privatedir
chmod -R u+rw,g+rw,o-rw $privatedir
iptables -F
echo "BOCASERVER=0/0" > /etc/icpc/bocaserver.sh


grep -iq "^[^\#]*ServerName" /etc/apache2/apache2.conf
if [ $? != 0 ]; then
 echo "ServerName boca" >> /etc/apache2/apache2.conf
fi

/etc/init.d/apache2 restart
mkdir -p /var/run/postgresql
chown $postgresuser.$postgresuser /var/run/postgresql
/etc/init.d/postgresql$POSTGRESV restart
update-rc.d apache2 defaults
update-rc.d postgresql$POSTGRESV defaults

rm -f /tmp/.boca.tmp
su - $postgresuser -c "echo select contestnumber from contesttable | psql -d bocadb | grep contestnumber >/tmp/.boca.tmp 2>/tmp/.boca.tmp"
su - $postgresuser -c "echo drop user bocauser | psql -d template1 >/dev/null 2>/dev/null"
su - $postgresuser -c "echo create user bocauser createdb password \'$PASS\' | psql -d template1"
su - $postgresuser -c "echo alter user bocauser createdb password \'$PASS\' | psql -d template1"

OK=y
grep -qi contestnumber /tmp/.boca.tmp
if [ $? == 0 ]; then
  OK=x
  while [ "$OK" != "y" -a "$OK" != "n" ]; do
  echo "====== An old database seems to exist. I can keep it, but it might not work with the version"
  echo -n "of BOCA being installed. May I erase all the content of the bocadb database [y/n]"
  OK=x
  read -p "?" OK
  done
fi
if [ "$OK" == "y" ]; then
cd $bocadir/src
php private/createdb.php
cd - >/dev/null 2>/dev/null
 echo "database renewed. Data on bocadb has been lost"
else
 echo "*** database not erased. Check if BOCA is compatible. You can always erase the database and"
 echo "*** fix the problem by running (as root) cd $bocadir/src; php private/createdb.php"
 echo "*** still, all data regarding BOCA in the database will be lost" 
fi
touch /etc/icpc/.isserver

if [ "$bkpserver" != "0" ]; then
 echo "Connecting to main server at $bkpserver to initialize the database -- pay attention in the following messages"
 for i in `ls -d /var/lib/postgresql/*/main`; do
  echo "standby_mode = \'on\'" > $i/recovery.conf
  chmod 600 $i/recovery.conf
  echo "primary_conninfo = \'host=$bkpserver port=5432 user=postgres password=$PASS\'" >> $i/recovery.conf
  chown $postgresuser $i/recovery.conf
  su - $postgresuser -c "pg_basebackup -D $i -w -R --xlog-method=stream --dbname=\'host=$bkpserver user=postgres port=5432 password=$PASS\'"
 done
 echo "=-=-=-= CHECK IF THE PREVIOUS MESSAGES HAVE NO ERRORS =-=-=-="
fi

echo "configuration finished. Boca should be available at http://localhost/boca/"
echo "reboot might not be required, but is advised."
