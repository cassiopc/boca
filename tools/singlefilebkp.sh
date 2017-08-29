#!/bin/bash

[ -x /etc/icpc/bocaserver.sh ] && . /etc/icpc/bocaserver.sh
if [ "$BOCASERVER" == "" ]; then
  echo "This computer has no configured BOCA server. Ask an admin to update /etc/icpc/bocaserver.sh (usually resetting everything is an easy way)"
  exit 1
fi

for i in uuencode wget tr perl md5sum cut; do
  p=`which $i`
  if [ -x "$p" ]; then
    echo -n ""
  else
    echo "$i" not found
    exit 1
  fi
done

if [ "$1" == "" ]; then
  echo "Usage: $0 <filename>"
  exit 1
fi

if [ -r "$1" ]; then
md=`wget -4 --no-check-certificate -S https://$BOCASERVER/boca/index.php -O /dev/null --save-cookies /tmp/.cookie.txt --keep-session-cookies 2>&1 | grep PHPSESS | tail -n1 | cut -f2 -d'=' | cut -f1 -d';'`
echo -n "User: "
read user
echo -n "Password: "
read pass
res=`echo -n $pass | md5sum - | cut -f1 -d' '`
res=`echo -n "${res}${md}" | md5sum - | cut -f1 -d' '`
wget -4 --no-check-certificate "https://$BOCASERVER/boca/index.php?name=${user}&password=${res}" --load-cookies /tmp/.cookie.txt --keep-session-cookies --save-cookies /tmp/.cookie.txt -O /tmp/.temp.txt 2>/dev/null >/dev/null
grep -qi incorrect /tmp/.temp.txt
if [ $? == 0 ]; then 
  echo User or password incorrect
else
nom=`echo -n $1 | perl -MURI::Escape -lne 'print uri_escape($_)'`
echo -n "name=${nom}&data=" > /tmp/.temp.txt
uuencode -m zzzzzzzzzz < $1 | grep -v "begin-base64.*zzzzzzzzzz" | perl -MURI::Escape -lne 'print uri_escape($_)' >> /tmp/.temp.txt
wget -4 --no-check-certificate "https://$BOCASERVER/boca/team/getfile.php" --load-cookies /tmp/.cookie.txt --keep-session-cookies -O /dev/null --post-file=/tmp/.temp.txt >/dev/null 2>/dev/null
fi
rm -f /tmp/.temp.txt
rm -f /tmp/.cookie.txt
else
  echo file $1 not found
fi
