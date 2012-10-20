#!/bin/bash
if [ "$1" == "" ]; then
  echo "CREATING FILES WITHOUT PASSWORDS"
  for i in `ls`; do 
    if [ -d $i ]; then 
      cd $i; zip -r ../$i.zip .; cd -; 
    fi
  done
else
  echo "USING ARGUMENT AS PASSWORD"
  rm -f keys.txt
  for i in `ls`; do 
    if [ -d $i ]; then 
      ../../src/private/createproblemzip.php "$i" "$i.zip" "$1" | grep -A2 "The following line" | tail -n1 >> keys.txt
    fi
  done
fi
