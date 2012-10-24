#!/bin/bash
# Copyright (c) 2007- C. P. de Campos (cassio@ime.usp.br). All rights reserved.
# Licensed under Q Public License version 1.0. See http://www.opensource.org/licenses/qtpl.php

for i in /usr/bin/cut /bin/cat /bin/date /usr/bin/basename /bin/true /usr/bin/uuencode /usr/bin/wc /usr/bin/tail; do
  if [ ! -x $i ]; then
    echo "$i not found. Aborting"
    exit 1
  fi
done

if [ "$1" == "" -o "$2" == "" -o ! -d "$2" -o ! -r "$2" ]; then
  echo "Usage $0 <filename> <directory>"
  echo "filename will be overwritten."
  echo "directory must contain the following files:"
  echo "  *.run: where * is a language name."
  echo "  *.compare: where * is a language name."
  echo "  *.in: where * is a problem name."
  echo "  *.out: where * is a problem name."
  echo "  *.pdf: where * is a problem name."
  echo "  For better compatibility, use only letters in filenames (avoid spaces, symbols, etc)."
  exit 1
fi
file=$1
dir=$2

d=`/bin/date +%s`
endmark=endmark$d

echo -n "Enter the contest name: "
read name

echo -n "Enter your site name: "
read site

st=$d
while /bin/true; do
 echo -n "Enter starting date (format complying with /bin/date. For example, '12/25/2007 13:34'): "
 read data
 st=`/bin/date -d "$data" +%s`
 if [ $? == 0 ]; then
  break
 fi
done

echo "Creating contest, site and answer sections"
/bin/cat << EOFEOF > $file
$endmark

[contest]
contestname=$name
scorelevel=4
sitename=$site
startdate=$st

[site]

[answer]
answernumber=1
answername=NO - Compile error
answeryes=f

answernumber=2
answername=NO - Runtime error
answeryes=f

answernumber=3
answername=NO - Time limit exceeded
answeryes=f

answernumber=4
answername=YES
answeryes=t

answernumber=5
answername=NO - Presentation error
answeryes=f

answernumber=6
answername=NO - Wrong answer
answeryes=f

answernumber=7
answername=NO - Contact staff
answeryes=f

answernumber=8
answername=NO - Problem/File name mismatch
answeryes=f

EOFEOF

echo "Creating language section"
echo "[language]" >> $file

j=1
for i in $dir/*.run ; do
  lang=`/usr/bin/basename "$i" .run`
  echo "Creating $lang"
  mds=`/usr/bin/md5sum $i | /usr/bin/cut -d" " -f1`
  mdc=`/usr/bin/md5sum $dir/$lang.compare | /usr/bin/cut -d" " -f1`

  echo -n "Enter language name: "
  read name

  /usr/bin/uuencode -m x < $dir/$lang.run > $file.tmp
  lin=`/usr/bin/wc -l $file.tmp | /usr/bin/cut -d" " -f1`
  let lin="$lin - 1"

  /bin/cat << EOFEOF >> $file
langnumber=$j
langname=$name
langscriptmd5=$mds
langcompscriptmd5=$mdc
langscript=base64:$lang.run
EOFEOF
  /usr/bin/tail -n $lin $file.tmp >> $file 
  echo "***${endmark}***" >> $file

  /usr/bin/uuencode -m x < $dir/$lang.compare > $file.tmp
  lin=`/usr/bin/wc -l $file.tmp | /usr/bin/cut -d" " -f1`
  let lin="$lin - 1"
  echo "langcompscript=base64:$lang.compare" >> $file
  /usr/bin/tail -n $lin $file.tmp >> $file 
  echo "***${endmark}***" >> $file

  echo "" >> $file
  let j="$j + 1"
done

echo "Creating problem section"
echo "[problem]" >> $file
letters="A B C D E F G H I J K L M N O P Q R S T U V W X Y Z"

j=1
for i in $dir/*.out ; do
  prob=`/usr/bin/basename "$i" .out`
  letter=`echo $letters | /usr/bin/cut -d" " -f$j`
  echo "Creating problem $letter (basename=$prob)"

  echo -n "Enter full name: "
  read full
  echo -n "Enter time limit: "
  read tl
  echo -n "Enter Color name: "
  read cn
  echo -n "Enter Color (html RGB format): "
  read rgb

  /bin/cat << EOFEOF >> $file
probnumber=$j
probname=$letter
probfullname=$full
probbasename=$prob
probtimelimit=$tl
probcolorname=$cn
probcolor=$rgb
EOFEOF

  if [ -r $dir/$prob.in ]; then
   mds=`/usr/bin/md5sum $dir/$prob.in | /usr/bin/cut -d" " -f1`
   echo "probinputfilemd5=$mds" >> $file
   echo "probinputfile=base64:$prob.in" >> $file
   /usr/bin/uuencode -m x < $dir/$prob.in > $file.tmp
   lin=`/usr/bin/wc -l $file.tmp | /usr/bin/cut -d" " -f1`
   let lin="$lin - 1"
   /usr/bin/tail -n $lin $file.tmp >> $file 
   echo "***${endmark}***" >> $file
  fi
  if [ -r $dir/$prob.out ]; then
   mds=`/usr/bin/md5sum $dir/$prob.out | /usr/bin/cut -d" " -f1`
   echo "probsolfilemd5=$mds" >> $file
   echo "probsolfile=base64:$prob.out" >> $file
   /usr/bin/uuencode -m x < $dir/$prob.out > $file.tmp
   lin=`/usr/bin/wc -l $file.tmp | /usr/bin/cut -d" " -f1`
   let lin="$lin - 1"
   /usr/bin/tail -n $lin $file.tmp >> $file 
   echo "***${endmark}***" >> $file
  fi
  if [ -r $dir/$prob.pdf ]; then
   mds=`/usr/bin/md5sum $dir/$prob.pdf | /usr/bin/cut -d" " -f1`
   echo "probdescfilemd5=$mds" >> $file
   echo "probdescfile=base64:$prob.pdf" >> $file
   /usr/bin/uuencode -m x < $dir/$prob.pdf > $file.tmp
   lin=`/usr/bin/wc -l $file.tmp | /usr/bin/cut -d" " -f1`
   let lin="$lin - 1"
   /usr/bin/tail -n $lin $file.tmp >> $file 
   echo "***${endmark}***" >> $file
  fi

  echo "" >> $file
  let j="$j + 1"
done

echo "[end]" >> $file
rm -f $file.tmp
echo "Done."
