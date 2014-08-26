#!/bin/bash
# ////////////////////////////////////////////////////////////////////////////////
# //BOCA Online Contest Administrator
# //    Copyright (C) 2003- by BOCA Development Team (bocasystem@gmail.com)
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
# // Last modified 03/sep/2014 by cassio@ime.usp.br
cdir=`pwd`
bocadir=$(dirname $cdir)
basen=`basename $cdir`
if [ ! -f "$cdir/genpackage.sh" -o "$basen" != "tools" ]; then
  echo "Please run this script from its own directory in tools/ of the BOCA directory"
else
if [ "$1" == "" ]; then
  ver=$(basename $(dirname $cdir) | cut -d'-' -f2-)
else
  ver=$1
fi
echo "*** Processing version $ver"
cd /tmp
cp -a $bocadir boca-$ver
rm -rf /tmp/boca-$ver/.git
rm -f /tmp/boca-$ver/tools/boca-*.tgz
if [ "$ver" != "" -a -d "boca-$ver" ]; then
echo "boca-$ver" > boca-$ver/src/version
echo -e "<?php\n\$BOCAVERSION='boca-$ver';\n\$YEAR='2014';\n?>\n" > boca-$ver/src/versionnum.php 
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
touch boca-$ver/.temp
rm boca-$ver/.temp `find boca-$ver/ -name ".DS*"`
touch boca-$ver/.temp
rm boca-$ver/.temp `find boca-$ver/ -name "._*"`
cd boca-$ver/tools/etc
tar czf ../icpc.etc.tgz *
cd ../../..
tar czf $cdir/boca-$ver.tgz boca-$ver/
rm -rf /tmp/boca-$ver
echo "*** file generated: $cdir/boca-$ver.tgz"
else
 echo "*** boca-$ver not found"
fi
fi
cd $cdir
