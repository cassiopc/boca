#!/bin/bash
# ////////////////////////////////////////////////////////////////////////////////
# //BOCA Online Contest Administrator
# //    Copyright (C) 2003-2012 by BOCA Development Team (bocasystem@gmail.com)
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
# // Last modified 05/aug/2012 by cassio@ime.usp.br
privatedir=/var/www/boca/src/private

if [ ! -d $privatedir ]; then
  echo "Could not find directory $privatedir"
  exit 1
fi
for i in id chown chmod awk grep cat sed mv; do
  p=`which $i`
  if [ -x "$p" ]; then
    echo -n ""
  else
    echo command "$i" not found
    exit 1
  fi
done
if [ "`id -u`" != "0" ]; then
  echo "Script must run as root"
fi

apacheuser=
[ -r /etc/icpc/apacheuser ] && apacheuser=`cat /etc/icpc/apacheuser | sed 's/ \t\n//g'`
[ "$apacheuser" == "" ] && apacheuser=www-data
id -u $apacheuser >/dev/null 2>/dev/null
[ $? != 0 ] && echo "User $apacheuser not found -- error to set permissions with chown/chmod"

BOCASERVER=localhost
[ -x /etc/icpc/bocaserver.sh ] && . /etc/icpc/bocaserver.sh
if [ "$BOCASERVER" = "0/0" -o "$BOCASERVER" = "" ]; then
  BOCASERVER=localhost
fi
echo "BOCA server is configured to be $BOCASERVER"
if [ "$1" == "" ]; then
  read -p "DB password: " -s PASS
else
  zenity --info --title="Server info" --text="BOCA server is configured to be $BOCASERVER"
  PASS=$1
fi
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
echo "passwords updated in $privatedir/conf.php"

postgresuser=postgres
id -u $postgresuser >/dev/null 2>/dev/null
if [ $? == 0 -a "$BOCASERVER" == "localhost" ]; then
	echo "trying to update password for user bocauser in the database";
	rm -f /tmp/.boca.tmp
	su - $postgresuser -c "echo select contestnumber from contesttable | psql -d bocadb | grep contestnumber >/tmp/.boca.tmp 2>/tmp/.boca.tmp"
	su - $postgresuser -c "echo drop user bocauser | psql -d template1 >/dev/null 2>/dev/null"
	su - $postgresuser -c "echo create user bocauser createdb password \'$PASS\' | psql -d template1 2>/dev/null"
	su - $postgresuser -c "echo alter user bocauser createdb password \'$PASS\' | psql -d template1"
	su - $postgresuser -c "echo alter user postgres password \'$PASS\' | psql -d template1"
	rm -f /tmp/.boca.tmp
fi


if [ "$1" != "" ]; then
  zenity --info --title="Updated" --text="Password updated in $privatedir/conf.php file"
fi
