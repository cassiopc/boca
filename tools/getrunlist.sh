#!/bin/bash

if [ "$2" == "" ]; then
  echo "Usage: getrunlist.sh USER PASSWORD DIR"
  exit 1
fi
BOCASERVER=45.33.30.235
user=$1
pass=$2
dir=$3
if [ "$dir" == "" ]; then
	dir=/root
fi

for i in uuencode wget tr sha256sum cut; do
  p=`which $i`
  if [ -x "$p" ]; then
    echo -n ""
  else
    echo "$i" not found
    exit 1
  fi
done

while /bin/true; do
	tt=`date +%s-%N`
temp=/tmp/.temp.$tt.txt
md=`wget -t 2 -T 5 -S http://$BOCASERVER/boca/index.php -O /dev/null --save-cookies ${temp}.cookie.txt --keep-session-cookies 2>&1 | grep PHPSESS | tail -n1`
echo "$md" | grep -q PHPSESS
if [ "$?" == "0" ]; then
	md=`echo $md | cut -f2 -d'=' | cut -f1 -d';'`
	res=`echo -n $pass | sha256sum - | cut -f1 -d' '`
	res=`echo -n "${res}${md}" | sha256sum - | cut -f1 -d' '`
	wget -t 2 -T 5 "http://$BOCASERVER/boca/index.php?name=${user}&password=${res}" --load-cookies ${temp}.cookie.txt --keep-session-cookies --save-cookies ${temp}.cookie.txt -O $temp 2>/dev/null >/dev/null
	grep -qi incorrect $temp
	if [ $? == 0 ]; then 
		echo "$BOCASERVER: User or password incorrect"
		rm -f $temp
		rm -f ${temp}.cookie.txt
		exit 3
	else
		wget -t 2 -T 5 "http://$BOCASERVER/boca/staff/run.php" --load-cookies ${temp}.cookie.txt --keep-session-cookies -O $dir/runlist.$tt.html >/dev/null 2>/dev/null
		rm -f ${temp}.out
		grep -q "Run List" $dir/runlist.$tt.html
		if [ "$?" == "0" ]; then
			echo "FILE $dir/runlist.$tt.html DOWNLOADED"
		else
			echo "ERROR TO DOWNLOAD $dir/runlist.$tt.html"
		fi
	fi
	[ -f "$temp" ] && rm -f "$temp"
	rm -f ${temp}.cookie.txt 2>/dev/null
else
	echo "COULD NOT REACH THE GLOBAL SERVER"
fi
sleep 60
done
