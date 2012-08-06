#!/bin/bash
cdir=`pwd`
basen=`basename $cdir`
if [ ! -f "$cdir/genpackage.sh" -o "$basen" != "tools" ]; then
  echo "Please run this script from its own directory in tools/ of the BOCA directory"
else
ver=$(basename $(dirname $cdir) | cut -d'-' -f2-)
echo "*** Processing version $ver"
cd ../..
if [ "$ver" != "" -a -d "boca-$ver" ]; then
echo "boca-$ver" > boca-$ver/src/version
echo -e "<?php\n\$BOCAVERSION='boca-$ver';\n\$YEAR='2012';\n?>\n" > boca-$ver/src/versionnum.php 
touch boca-$ver/src/private/runtmp/run0.php boca-$ver/src/private/scoretmp/0.php boca-$ver/src/private/remotescores/0.dat \
  boca-$ver/src/private/remotescores/0.tmp boca-$ver/src/private/problemtmp/problem0.tmp
rm -f boca-$ver/src/balloons/*.png
rm -f boca-$ver/src/private/runtmp/run*.php boca-$ver/src/private/scoretmp/*.php boca-$ver/src/private/remotescores/*.dat \
  boca-$ver/src/private/remotescores/*.tmp 
rm -rf boca-$ver/src/private/problemtmp/problem*
touch boca-$ver/.temp
rm boca-$ver/.temp `find boca-$ver/ -name "*.orig"`
touch boca-$ver/.temp
rm boca-$ver/.temp `find boca-$ver/ -name "*~"`
touch boca-$ver/.temp
rm boca-$ver/.temp `find boca-$ver/ -name ".\#*"`
cd boca-$ver/tools/etc
tar cvzf ../icpc.etc.tgz *
cd ../../..
tar czf boca-$ver.tgz boca-$ver/
echo "*** file generated: `pwd`/boca-$ver.tgz"
else
 echo "*** boca-$ver not found"
fi
fi
cd $cdir
