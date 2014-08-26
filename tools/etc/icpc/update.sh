#!/bin/bash
if [ "`id -u`" != "0" ]; then
  echo "Script must run as root"
fi
wget -O /tmp/update.sh "http://www.ime.usp.br/~cassio/boca/update.sh"
if [ $? != 0 ]; then
  echo "ERROR DOWNLOADING UPDATE"
  exit 1
fi
echo ">>>>>>>>>>"
echo ">>>>>>>>>> Running update script"
echo ">>>>>>>>>>"
/tmp/update.sh
exit $?
