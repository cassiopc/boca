#!/bin/bash

if [ "$1" == "users" ]; then
    for i in `ls runs-submitted*.txt`; do
	[ "$2" == "verb" ] && echo "users checking $i"
	a=""
	cat $i | while read lin; do
	    if [ "$a" == "" ]; then
		a=$lin
	    else
		a1=`echo $a | cut -d'-' -f1`
		l1=`echo $lin | cut -d'-' -f1`
		if [ "$a1" != "$l1" ]; then
		    echo "$i $a1 $l1"
		    a=$lin
		fi
	    fi
	done
    done
else
    if [ "$1" == "gencodes" ]; then
	while read lin; do 
	    #First Surname:email@gmail.com:Sao Paulo:SP:spsp:46:48479:146:qrw3
	    pas="`echo -n $lin | cut -d':' -f9`"
	    pas="`echo -n $pas | sha256sum - | cut -f1 -d' '`"
	    astring="xyzxyzxyz"
	    pass="`echo -n "${astring}$pass" | sha256sum - | cut -f1 -d' '`"
	    echo "`echo -n $lin | cut -d':' -f5` $pas $pass 0"
	done
    else    
	for i in `ls runs-submitted*.txt`; do
	    [ "$2" == "verb" ] && echo "checking $i"
	    cat $i|cut -d'-' -f1 |sort -u| while read lin; do
		q=`grep -c $lin runs-submitted*.txt | grep -v ":0$" | wc -l`
		if [ "$q" != "1" ]; then
		    echo "===Computer $lin used by multiple users"
		    grep -c $lin runs-submitted*.txt | grep -v ":0$" | while read line1; do
                      fname=`echo $line1 | cut -d':' -f1`
		      fname=`basename $fname .txt`
                      echo $fname
		      grep $lin ${fname}.try | cut -d'-' -f4 | while read line2; do date -d "@$line2"; done
                   done   
		fi
	    done
	done
	# for arquivo in `ls runs-submitted-*txt`; do
	#     TIME="$(cut -d'-' -f3-5 <<< "`basename $arquivo .txt`")"
	#     printf "$TIME "
	#     ##grep '\-2[0-1][0-9]\-'
	#     cat $arquivo|cut -d'-' -f1 |sort -u|wc -l
	# done
    fi
fi

if [ 0 == 1 ]; then
  ###example of codes
  sitename='imeu'
  password='password'
  ress=`echo -n "$password" | sha256sum - | cut -f1 -d' '`
  res=`echo -n "${password}${ress}${password}" | sha256sum - | cut -f1 -d' '`
  echo $sitename $ress $res 0

###example of generating score.sep
###First Surname:email@gmail.com:Sao Paulo:SP:spsp:46:48479:146:qrw3
  #!/bin/bash
  while read lin; do 
    prefix="`echo -n $lin | cut -d':' -f8`"
    prenam="`echo -n $lin | cut -d':' -f5`"
    echo "$prenam ${prefix}000/${prefix}399/1 # /^team${prenam}/ /^staff${prenam}/ /^score${prenam}/"
    echo "${prenam}ccl ${prefix}000/${prefix}500/1 # /^staff${prenam}/ /^teamccl${prenam}/ /^judge/"
  done
  #!/bin/bash
  i=1
  j=1
  k=1
  while [ $i -le 49 ]; do
    let "jj = $j + 1"
    let "ii = $i - 1"
    let "ff = $ii * 100 + 50000"
    let "gg = $i * 100 + 49999"
    echo "sede$i $ff/$gg/1 # /^team${j}\$/ /^team${jj}\$/ /^staff${i}\$/"
    let "i = $i + 1"
    let "j = $j + 2"
  done
fi
