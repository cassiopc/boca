#!/bin/bash

if [ "`id -u`" != "0" ]; then
  echo "Must be run as root"
  exit 1
fi
if [[ ! -e /etc/bocaip ]] ; then
    BOCASERVER=50.116.19.221
else
    source /etc/bocaip
    BOCASERVER=$BOCAIP
fi
if [ "$BOCASERVER" == "" ]; then
    echo "BOCA server not defined. Aborting"
    exit 1
fi

grep "session opened for user root" /var/log/auth.log |grep -v cron:session | grep -v systemd:session | tail -n 100 > /root/.logroot.tmp
[ -f /root/.logroot ] || touch /root/.logroot
diff /root/.logroot /root/.logroot.tmp > /root/.logroot.diff 2>/dev/null
res=$?
mv /root/.logroot.tmp /root/.logroot
if [ "$res" != "0" ]; then
    for i in uuencode wget tr perl sha256sum cut; do
	p=`which $i`
	if [ -x "$p" ]; then
	    echo -n ""
	else
	    echo "$i" not found
	    exit 1
	fi
    done
    temp=/root/.temp.`date +%s%N`.txt
    md=`wget --no-check-certificate -t 2 -T 5 -S https://$BOCASERVER/boca/logexternal.php -O /dev/null --save-cookies ${temp}.cookie.txt --keep-session-cookies 2>&1 | grep PHPSESS | tail -n1`
    echo "$md" | grep -q PHPSESS
    if [ "$?" == "0" ]; then
	md=`echo $md | cut -f2 -d'=' | cut -f1 -d';'`
	res=`cat /root/submissions/code 2>/dev/null`
	res=`echo -n "${res}${md}" | sha256sum - | cut -f1 -d' '`

	echo -n "comp=`cat /root/submissions/comp`" > $temp
	echo -n "&code=$res" >> $temp
	echo -n "&data=" >> $temp
	grep "^>" /root/.logroot.diff | uuencode -m zzzzzzzzzz | grep -v "begin-base64.*zzzzzzzzzz" | perl -MURI::Escape -lne 'print uri_escape($_)' >> $temp

	wget --no-check-certificate -t 2 -T 5 "https://$BOCASERVER/boca/logexternal.php" --load-cookies ${temp}.cookie.txt --keep-session-cookies --save-cookies ${temp}.cookie.txt -O ${temp}.out --post-file=$temp >/dev/null 2>/dev/null
	rm -f $temp
	rm -f ${temp}.cookie.txt
	grep -qi incorrect ${temp}.out
	res=$?
	rm ${temp}.out
	if [ "$res" == "0" ]; then 
	    echo "$BOCASERVER: User or password incorrect"
	    exit 3
	fi
    else
	echo "$BOCASERVER: connection failed"
	exit 2
    fi
fi
exit 0
