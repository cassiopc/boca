#!/bin/bash
#//////////////////////////////////////////////////////////////////////////////////////////
#//BOCA Online Contest Administrator. Copyright (c) 2003- Cassio Polpo de Campos.
#//It may be distributed under the terms of the Q Public License version 1.0. A copy of the
#//license can be found with this software or at http://www.opensource.org/licenses/qtpl.php
#//
#//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
#//INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
#//PURPOSE AND NONINFRINGEMENT OF THIRD PARTY RIGHTS. IN NO EVENT SHALL THE COPYRIGHT HOLDER
#//OR HOLDERS INCLUDED IN THIS NOTICE BE LIABLE FOR ANY CLAIM, OR ANY SPECIAL INDIRECT OR
#//CONSEQUENTIAL DAMAGES, OR ANY DAMAGES WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR
#//PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING
#//OUT OF OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
#///////////////////////////////////////////////////////////////////////////////////////////
#Last modified: 10/aug/2009 by cassio@ime.usp.br
#
# parameters are:
# $1 base_filename
# $2 source_file
# $3 input_file
# $4 languagename
# $5 problemname
# $6 timelimit
#
# the output of the submission should be directed to the standard output
#
# the return code show what happened:
# 0 ok
# 1 compile error
# 2 runtime error
# 3 timelimit exceeded
# other_codes are unknown to boca: in this case BOCA will present the
#                                  last line of standard output to the judge

umask 0022
chown nobody.nogroup .

export CLASSPATH=.:$CLASSPATH

# this script makes use of safeexec to execute the code with less privilegies
# make sure that directories below are correct.
sf=`which safeexec`
[ -x "$sf" ] || sf=/usr/bin/safeexec
gcc=`which gcc`
[ -x "$gcc" ] || gcc=/usr/bin/gcc
gpp=`which g++`
[ -x "$gpp" ] || gpp=/usr/bin/g++
java=`which java`
[ -x "$java" ] || java=/usr/java/bin/java
javac=`which javac`
[ -x "$javac" ] || javac=/usr/java/bin/javac
pascal=`which fpc`
[ -x "$pascal" ] || pascal=/usr/bin/fpc
grep=`which grep`
[ -x "$grep" ] || grep=/bin/grep

if [ "$1" == "" -o "$2" == "" -o "$3" == "" ]; then
    echo "parameter problem"
    exit 43
fi
if [ ! -r "$2" ]; then
    echo "$2 not found or it's not readable"
    exit 44
fi
if [ ! -r "$3" ]; then
    echo "$3 not found or it's not readable"
    exit 45
fi
if [ ! -x "$sf" ]; then
    echo "$sf not found or it's not executable"
    exit 46
fi

prefix=$1
name=$2
input=$3

# setting up the timelimit according to the problem
# note that problems should spelling the same as inside BOCA
if [ "$6" == "" ]; then
time=5
else
time=$6
fi
let ttime=$time+30

# choose the compiler according to the language
# note that languages should spelling the same as inside BOCA
case "$4" in
C)
	$gcc -lm -o "$prefix" "$name"
	ret=$?
	if [ "$ret" != "0" ]; then
		echo "Compiling Error: $ret"
		exit 1
	else
		$sf -F10 -t$time -T$ttime -i$input -n0 -R. "./$prefix"
		ret=$?
		if [ $ret -gt 3 ]; then
                    ret=0
		fi
	fi
	;;
C++)
	$gpp -lm -o "$prefix" "$name"
	ret=$?
	if [ "$ret" != "0" ]; then
		echo "Compiling Error: $ret"
		exit 1
	else
		$sf -F10 -t$time -T$ttime -i$input -n0 -R. "./$prefix"
		ret=$?
		if [ $ret -gt 3 ]; then
                    ret=0
		fi
	fi
	;;
Pascal)
	$pascal -o"$prefix" "$name" >compiler.out 2>compiler.out
        $grep -irq linking compiler.out
	ret=$?
        $grep -irq "lines compiled" compiler.out
	ret2=$?
	if [ "$ret" != "0" -o "$ret2" != "0" ]; then
		cat compiler.out
		echo "Compiling Error: $ret"
		exit 1
	else
		$sf -F10 -t$time -T$ttime -i$input -opascal.out -n0 -R. "./$prefix"
		ret=$?
                if [ -f pascal.out ]; then
 	          cat pascal.out
                  $grep -irq "runtime error" pascal.out
                  ret2=$?
		  if [ "$ret2" = "0" ]; then
			echo "Strange output - possible runtime error"
			if [ $ret -lt 4 ]; then
				ret=48
			fi
                  fi
		fi
	fi
	;;
Java)
	$javac "$name"
	ret=$?
	if [ "$ret" != "0" ]; then
		echo "Compiling Error: $ret"
		exit 1
	else
		$sf -u10 -F30 -t$time -T$ttime -i$input -n0 -R. $java "$prefix"
		ret=$?
		if [ $ret -gt 3 ]; then
		    echo "Nonzero return code - possible runtime error"
		    ret=47
		fi
	fi
	;;
*)
	echo "Language not recognized"
	exit 42
	;;
esac
exit $ret
