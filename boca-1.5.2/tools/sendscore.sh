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
# last modified 22/oct/2012 by cassio@ime.usp.br

if [ "$1" == "" -o "$2" == "" -o "$3" == "" -o "$4" == "" ]; then
  echo "Usage $0 <scorefile> <BOCAaddress> <user> <password> [<PC2site>]"
  echo "e.g. $0 score.dat http://bombonera.ime.usp.br/boca site1 hardpass"
  echo "     $0 summary.html http://bombonera.ime.usp.br/boca site1 hardpass 1"
  echo "the last number in the previous line indicates the number of the site, which should be unique among sites"
  exit 1
fi
BOCASERVER=$2
user=$3
pass=$4
pc2=0
if [ "$5" != "" ]; then
pc2=$5
fi

for i in wget tr perl shasum cut; do
  p=`which $i`
  if [ -x "$p" ]; then
    echo -n ""
  else
    echo command "$i" not found
    exit 1
  fi
done

if [ -r "$1" ]; then
md=`wget -S -T3 -t3 $BOCASERVER/index.php -O /dev/null --save-cookies /tmp/.cookie.txt --keep-session-cookies 2>&1 | grep PHPSESS | tail -n1 | cut -f2 -d'=' | cut -f1 -d';'`
res=`echo -n $pass | shasum -a 256 - | cut -f1 -d' '`
res=`echo -n "${res}${md}" | shasum -a 256 - | cut -f1 -d' '`
wget -T3 -t3 "$BOCASERVER/index.php?name=${user}&password=${res}&action=scoretransfer" --load-cookies /tmp/.cookie.txt --keep-session-cookies --save-cookies /tmp/.cookie.txt -O /tmp/.temp.txt 2>/dev/null >/dev/null
grep -qi incorrect /tmp/.temp.txt
if [ $? == 0 ]; then 
  echo User or password incorrect
else
nom=`echo -n $1 | perl -MURI::Escape -lne 'print uri_escape($_)'`
echo -n "PC2=${pc2}&name=${nom}&data=" > /tmp/.temp.txt
if [ "$pc2" != "0" ]; then
  uuencode -m zzzzzzzzzz < "$1" | grep -v "begin-base64.*zzzzzzzzzz" | perl -MURI::Escape -lne 'print uri_escape($_)' >> /tmp/.temp.txt
else
  cat "$1" | perl -MURI::Escape -lne 'print uri_escape($_)' >> /tmp/.temp.txt
fi
wget -t3 -T3 "$BOCASERVER/site/putfile.php" --load-cookies /tmp/.cookie.txt --keep-session-cookies -O /tmp/.temp2.txt --post-file=/tmp/.temp.txt >/dev/null 2>/dev/null
[ -r /tmp/.temp2.txt ] && cat /tmp/.temp2.txt
rm -f /tmp/.temp2.txt
fi
rm -f /tmp/.temp.txt
rm -f /tmp/.cookie.txt
else
  echo file $1 not found
fi
