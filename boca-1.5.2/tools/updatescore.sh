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
# last updated 01/nov/2012 by cassio@ime.usp.br
if [ "`id -u`" != "0" ]; then
  echo "Must be run as root"
  exit 1
fi
bocadir=/var/www/boca
[ -r /etc/boca.conf ] && . /etc/boca.conf

privatedir=$bocadir/src/private/remotescores
others=$privatedir/otherservers
if [ "$1" == "" -o "$2" == "" ]; then
  echo "Usage $0 <remotescorefolder> <serversfile>"
  echo "e.g. $0 $privatedir $others"
  echo "*** When arguments are not given, default values as of the previous line is used"
fi
if [ "$1" != "" ]; then
  privatedir=$1
fi
if [ "$2" != "" ]; then
  others=$2
else
  others=$privatedir/otherservers
fi

for i in id chown chmod md5sum shasum wget tr cut awk tail head grep cat sed sleep; do
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

if [ ! -d $privatedir ]; then
  echo "Could not find directory $privatedir"
  exit 1
fi
tempdir=$privatedir/tmp
mkdir -p $tempdir >/dev/null 2>/dev/null
if [ ! -d $tempdir ]; then
  echo "Could not create directory $tempdir"
  exit 1
fi
httpbocadir=boca
secs=120
apacheuser=
[ -r /etc/icpc/apacheuser ] && apacheuser=`cat /etc/icpc/apacheuser | sed 's/ \t\n//g'`
[ "$apacheuser" == "" ] && apacheuser=www-data
id -u $apacheuser > /dev/null 2>/dev/null
[ $? != 0 ] && echo "User $apacheuser not found -- error to set permissions with chown/chmod"

hash="shasum -a 256 -"
#hash="md5sum -"

#rm -f $privatedir/score_*.dat
chown $apacheuser.root $privatedir/score_*.dat

if [ ! -r $others ]; then
  echo "External server list in $others not found"
  exit 1
fi
echo "Starting loop to get scores from servers defined in $others"
while /bin/true; do
	echo "Getting scores..."
	qtd=1
	for BOCASERVER in `grep -v "^[ \t]*\#" $others | awk '{ print $1; }'`; do
		if [ "$BOCASERVER" == "" ]; then
			continue
		fi
		echo $BOCASERVER | grep -q "http"
		[ $? == 0 ] || BOCASERVER=http://$BOCASERVER/boca

		user=`grep -v "^[ \t]*\#" $others | head -n$qtd | tail -n1 | awk '{ print $2; }'`
		[ "$user" == "" ] && user=score
		pass=`grep -v "^[ \t]*\#" $others | head -n$qtd | tail -n1 | awk '{ print $2; }'`
		[ "$pass" == "" ] && pass=score
		let "qtd = $qtd + 1"

		echo -n "Asking server $BOCASERVER. Authenticating with user '$user'..."
		md=`wget -t3 -T3 -S $BOCASERVER/index.php -O /dev/null --save-cookies $tempdir/.cookie.txt --keep-session-cookies 2>&1 | grep PHPSESS | tail -n1 | cut -f2 -d'=' | cut -f1 -d';'`
		res=`echo -n $pass | $hash | cut -f1 -d' '`
		res=`echo -n "${res}${md}" | $hash | cut -f1 -d' '`
		echo -n "sending password..."
		wget -t3 -T3 "$BOCASERVER/index.php?name=${user}&password=${res}" --load-cookies $tempdir/.cookie.txt --keep-session-cookies --save-cookies $tempdir/.cookie.txt -O $tempdir/.temp.txt 2>/dev/null >/dev/null
		grep -qi incorrect $tempdir/.temp.txt
		if [ "$?" != "0" ]; then
			rm -f $tempdir/*
			echo "downloading scoretable..."
			wget -t3 -T3 "$BOCASERVER/scoretable.php?remote=-42" --load-cookies $tempdir/.cookie.txt --keep-session-cookies --save-cookies $tempdir/.cookie.txt -O $tempdir/score.zip 2>$tempdir/.bocascore.tmp >$tempdir/.bocascore.tmp
			if [ "$?" == "0" ]; then
				unzip -qq $tempdir/score.zip -d $tempdir
				if [ "$?" == "0" ]; then
					for fscore in `ls -d $tempdir/*.dat`; do
						chown $apacheuser.root "$fscore"
						chmod 660 "$fscore"
						bfscore=`basename $fscore`
						hasscore=`echo $bfscore | cut -d'_' -f1`
						if [ "$hasscore" != "score" ]; then
							bfscore=score_$bfscore
						fi
						mv "$fscore" "$privatedir/$bfscore"
						echo "Score downloaded successfully into $privatedir/$bfscore"
					done
				else
					echo "Error: score file from $BOCASERVER is not a valid package"
				fi
			else
				echo "Error getting score file from $BOCASERVER: `cat $tempdir/.bocascore.tmp`"
			fi
		else
			echo "Error authenticating to server $BOCASERVER"
		fi
		rm -f $tempdir/.temp.txt
		rm -f $tempdir/.cookie.txt
	done

	echo -n "Waiting $secs secs..."
	sleep $secs
	echo ""
done
