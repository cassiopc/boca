#!/bin/bash

for i in pg_dump grep cut gzip date; do
  if [ "`which $i`" == "" ]; then
    echo "$i executable is not in the PATH. Aborting"
    exit 1
  fi
done
da=`date +%d%b%Y-%Hh%Mmin`
echo "I will create the file `pwd`/bocadb.$da.tar.gz"
f=/var/www/boca/private/conf.php
[ -r $f ] || f=/var/www/private/conf.php
if [ -r $f ]; then
  echo I believe the password is `grep "\$conf\[\"dbpass\"\]=" /var/www/boca/private/conf.php | cut -d'"' -f4`
else
  echo "The password can be found in private/conf.php of the boca directory"
fi
pg_dump -f bocadb.$da.tar -Ft -b -h 127.0.0.1 -U bocauser bocadb
gzip -9 bocadb.$da.tar

exit 0
