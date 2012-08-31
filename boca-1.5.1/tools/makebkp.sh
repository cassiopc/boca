#!/bin/bash

for i in zip du find cut; do
	p=`which $i`
	if [ -x "$p" ]; then
		echo -n ""
	else
		echo "$i" not found
		exit 1
	fi
done

ex=`which singlefilebkp.sh`
if [ "$ex" = "" ]; then
	ex=/var/www/boca/tools/singlefilebkp.sh
fi

if [ -x "$ex" ]; then
	zip /tmp/bkp.zip `find $cdir -name "*.c"` `find $cdir -name "*.java"` `find $cdir -name "*.cpp"` `find $cdir -name "*.in"`
	if [ ! -f /tmp/bkp.zip ]; then
		echo "Nothing to backup"
	else
		size=`du -s /tmp/bkp.zip | cut -f1`
		if [ "$size" -gt 100000 ]; then
			echo Bkp is already too large. BACKUP ABORTED
		else
			$ex /tmp/bkp.zip
		fi
	fi
else
	echo Bkp script not found or is not executable
fi
