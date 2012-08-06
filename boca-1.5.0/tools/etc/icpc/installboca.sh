#!/bin/bash
di=`date +%s`
echo "==================================================="
echo "=================== obtaining BOCA  ==============="
echo "==================================================="

wget -O /tmp/.boca.tmp "http://www.ime.usp.br/~cassio/boca/boca.date.txt"
echo ">>>>>>>>>>"
echo ">>>>>>>>>> Downloading boca release `cat /tmp/.boca.tmp`"
echo ">>>>>>>>>>"

if [ "$1" == "" ]; then
wget -O /tmp/.boca.tmp "http://www.ime.usp.br/~cassio/boca/bocaver.txt"
bocaver=`cat /tmp/.boca.tmp`
else
bocaver=$1
fi
echo "Looking for BOCA version $bocaver from http://www.ime.usp.br/~cassio/boca/"
cd /var/www
rm -f boca-$bocaver.tgz
wget -O boca-$bocaver.tgz "http://www.ime.usp.br/~cassio/boca/download.php?filename=boca-$bocaver.tgz"
if [ "$?" != "0" -o ! -f boca-$bocaver.tgz ]; then
  echo "ERROR downloading BOCA package version $bocaver. Aborting *****************"
  exit 1
fi
grep -qi "bad parameters" boca-$bocaver.tgz
if [ "$?" == "0" ]; then
  echo "ERROR downloading BOCA package version $bocaver. Aborting *****************"
  exit 1
fi
echo "==========================================================="
echo "====================== BACKUPING OLD BOCA   ==============="
echo "==========================================================="
if [ -d boca-$bocaver ]; then
  mv boca-$bocaver boca-$bocaver.$di
  echo "OLD BOCA FOLDER for version $bocaver saved as boca-$bocaver.$di"
fi

echo "====================================================="
echo "=================== EXTRACTING BOCA   ==============="
echo "====================================================="

OK=x
if [ -f boca-$bocaver.$di/src/private/conf.php ]; then
 echo "OLD CONFIG FILE EXISTS"
 OK=x
 while [ "$OK" != "y" -a "$OK" != "n" ]; do
   OK=x
   read -p "Do you want to keep the old private/conf.php file [y/n] (note that the old file might be incompatible with this version)? " OK
 done
 if [ "$OK" == "n" ]; then
   echo "You probably need to update the new file boca-$bocaver/src/private/conf.php with the correct passwords - PLEASE CHECK IT - NOT DONE AUTOMATICALLY"
 fi
fi
tar xzf boca-$bocaver.tgz
chown -R www-data.www-data boca-$bocaver/
[ -f boca-$bocaver.$di/src/private/otherservers ] && cp -f boca-$bocaver.$di/src/private/otherservers boca-$bocaver/src/private/otherservers
if [ "$OK" == "y" ]; then
  cp -f boca-$bocaver.$di/src/private/conf.php boca-$bocaver/src/private/conf.php
  chown www-data.www-data boca-$bocaver/src/private/conf.php
  chmod 660 boca-$bocaver/src/private/conf.php
fi
chown root.root boca-$bocaver/src/private/autojudging.php
chmod 600 boca-$bocaver/src/private/autojudging.php
chown root.root boca-$bocaver/src/private/createdb.php
chmod 600 boca-$bocaver/src/private/createdb.php
chown root.root boca-$bocaver/tools/*.sh
chmod 700 boca-$bocaver/tools/*.sh

echo "=========================================================================================="
echo "=========== SETTING UP SOME LINKS (main apache server index.html updated)  ==============="
echo "=========================================================================================="

rm -f /var/www/boca /usr/bin/makebkp.sh
ln -s /var/www/boca-$bocaver /var/www/boca
ln -s /var/www/boca/tools/makebkp.sh /usr/bin/makebkp.sh
chmod 755 /var/www/boca/tools/makebkp.sh
chmod 755 /var/www/boca/tools/singlefilebkp.sh

echo "=============================================================="
echo "================== COMPILING safeexec utility  ==============="
echo "=============================================================="

cd /var/www/boca/tools
gcc -static -O2 -Wall safeexec.c -o safeexec
if [ $? == 0 ]; then
  echo "COMPILATION OK"
fi
strip safeexec
cp -f safeexec /usr/bin
chmod 4555 /usr/bin/safeexec

echo "=================================================="
echo "=================== SERVER SETUP   ==============="
echo "=================================================="

OK=n
echo "You can run at anytime later the script /etc/icpc/becomeserver.sh to prepare the computer to be the BOCA server"
read -p "Do you want me to call the script to make this computer the server (don't do it if this install is for a team or autojudge) [y/N]? " OK
if [ "$OK" == "y" -o "$OK" == "Y" ]; then
  OK=n
  read -p "Do you really want to make this computer the server (you don't need to do it if you are only upgrading BOCA)? [y/N]? " OK
  if [ "$OK" == "y" -o "$OK" == "Y" ]; then
  /etc/icpc/becomeserver.sh
  fi
fi
