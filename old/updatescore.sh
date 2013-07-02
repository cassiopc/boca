#!/bin/bash
#//////////////////////////////////////////////////////////////////////////////////////////
#//BOCA Online Contest Administrator. Copyright (c) 2003- Cassio Polpo de Campos.
#//It may be distributed under the terms of the Q Public License version 1.0. A copy of the
#//license can be found with this software or at http://www.opensource.org/licenses/qtpl.php
#//
#//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
#//INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
#//PURPOSE AND NONINFRINGEMENT OF THIRD PARTY RIGHTS. IN NO EVENT SHALL THE COPYRIGHT HOLDER
#//OR HOLDERS INCLUDED IN THIS NOTICE BE LIABLE FOR ANY CLAIM, OR ANY SPECIAL INDIRECT OR
#//CONSEQUENTIAL DAMAGES, OR ANY DAMAGES WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR
#//PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING
#//OUT OF OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
#///////////////////////////////////////////////////////////////////////////////////////////
# last updated 26/oct/2011 by cassio@ime.usp.br
for i in id chown chmod md5sum wget tr cut awk tail head grep cat sed sleep; do
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

privatedir=/var/www/boca/private
if [ ! -d $privatedir ]; then
  echo "Could not find directory $privatedir"
  exit 1
fi
httpbocadir=boca
tempdir=/tmp
others=/var/www/boca/private/otherservers
secs=120
apacheuser=
[ -r /etc/icpc/apacheuser ] && apacheuser=`cat /etc/icpc/apacheuser | sed 's/ \t\n//g'`
[ "$apacheuser" == "" ] && apacheuser=www-data
id -u $apacheuser > /dev/null 2>/dev/null
[ $? != 0 ] && echo "User $apacheuser not found -- error to set permissions with chown/chmod"

#hash="shasum -a 256 -"
hash="md5sum -"

#rm -f $privatedir/score_*.dat
#rm -f $privatedir/thissitescore.dat
chown $apacheuser.root $privatedir/score_*.dat
chown $apacheuser.root $privatedir/thissitescore.dat

BOCASERVER=
[ -x /etc/icpc/bocaserver.sh ] && . /etc/icpc/bocaserver.sh
LOCALSERVER=$BOCASERVER
if [ "$LOCALSERVER" == "" -o "$LOCALSERVER" == "0/0" ]; then
  LOCALSERVER=http://127.0.0.1/$httpbocadir
else
  LOCALSERVER=http://$LOCALSERVER/$httpbocadir
fi

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

wget -t3 -T3 "$LOCALSERVER/scoretable.php?clock=1" -O $tempdir/.temp.txt 2>/dev/null >/dev/null
if [ $? != 0 ]; then 
  echo "Error getting contest clock from $LOCALSERVER"
else
  tempo=`cat $tempdir/.temp.txt`
  echo -n "Asking server $BOCASERVER at time $tempo. Authenticating with user '$user'..."
  md=`wget -t3 -T3 -S $BOCASERVER/index.php -O /dev/null --save-cookies $tempdir/.cookie.txt --keep-session-cookies 2>&1 | grep PHPSESS | tail -n1 | cut -f2 -d'=' | cut -f1 -d';'`
  res=`echo -n $pass | $hash | cut -f1 -d' '`
  res=`echo -n "${res}${md}" | $hash | cut -f1 -d' '`
  echo -n "sending password..."
  wget -t3 -T3 "$BOCASERVER/index.php?name=${user}&password=${res}" --load-cookies $tempdir/.cookie.txt --keep-session-cookies --save-cookies $tempdir/.cookie.txt -O $tempdir/.temp.txt 2>/dev/null >/dev/null
  grep -qi incorrect $tempdir/.temp.txt
  if [ $? != 0 ]; then
    fname=`echo $BOCASERVER | tr './:' '_'`
    echo "downloading scoretable..."
    wget -t3 -T3 "$BOCASERVER/scoretable.php?remote=$tempo" --load-cookies $tempdir/.cookie.txt --keep-session-cookies --save-cookies $tempdir/.cookie.txt -O $privatedir/score_${fname}.tmp 2>$tempdir/.bocascore.tmp >$tempdir/.bocascore.tmp
    if [ $? == 0 ]; then
      chown $apacheuser.root $privatedir/score_$fname.tmp
      chmod 660 $privatedir/score_$fname.tmp
      mv $privatedir/score_$fname.tmp $privatedir/score_$fname.dat
      echo "Score downloaded successfully into $privatedir/score_$fname.dat"
    else
      echo "Error getting scoretable from $BOCASERVER: `cat $tempdir/.bocascore.tmp`"
    fi
  else
    echo "Error authenticating to server $BOCASERVER"
  fi
fi
rm -f $tempdir/.temp.txt
rm -f $tempdir/.cookie.txt
done

echo -n "Waiting $secs secs..."
sleep $secs
echo ""
done
