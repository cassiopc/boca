#!/bin/bash
if [ "`id -u`" != "0" ]; then
  echo "Script must run as root"
fi
if [ "$1" == "" ]; then
	wget -O /tmp/update.sh "http://www.ime.usp.br/~cassio/boca/download.php?filename=update.sh"
else
	wget -O /tmp/update.sh "http://www.ime.usp.br/~cassio/boca/download.php?filename=update.$1.sh"
fi
chmod 700 /tmp/update.sh 2>/dev/null
if [ $? != 0 ]; then
  echo "ERROR DOWNLOADING UPDATE"
  exit 1
fi
echo ">>>>>>>>>>"
echo ">>>>>>>>>> Running update script"
echo ">>>>>>>>>>"
/tmp/update.sh
rm -f /tmp/update.sh
exit $?
