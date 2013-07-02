#!/bin/bash
cd /home
if [ -f /home/icpc/.cleandisk.sh ]; then
rm -rf /home/icpc
find /home -user icpc -delete
if [ -x /usr/bin/makepasswd ]; then
 pass=`echo -n icpc | /usr/bin/makepasswd --clearfrom - --crypt-md5 | cut -d'$' -f2-`
 pass=\$`echo $pass`
 id -u icpc >/dev/null 2>/dev/null
 if [ $? != 0 ]; then
  useradd -d /home/icpc -k /etc/skel -m -p "$pass" -s /bin/bash -g users icpc
 else
  usermod -d /home/icpc -p "$pass" -s /bin/bash -g users icpc
 fi
fi
for i in media mnt var opt tmp usr; do
  find /$i -user icpc -delete
done
if [ ! -d /home/icpc ]; then
  rm -rf /home/icpc
  rm -rf /home/skel
  cp -ar /etc/skel/ /home
  mv /home/skel /home/icpc
fi
chown -R icpc.users /home/icpc
chmod -R u+rwx /home/icpc
fi
cd - >/dev/null
exit 0
