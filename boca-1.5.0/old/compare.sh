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
#Last modified: 31/oct/2011 by cassio@ime.usp.br
#
# This script receives:
# $1 team_output
# $2 sol_output
# $3 languagename
# $4 problemname
# $5 problem_input
#
# BOCA reads the last line of the standard output
# and pass it to judges
#
if [ ! -r "$1" -o ! -r "$2" ]; then
  echo "Parameter problem"
  exit 43
fi

# if there is an special checker, use it. It can be defined by an sol_output
# which has .sh extension (which makes it be executed instead of compared to)
# or by the existence of the file bocachecker.$4 in the execution path (here
# $4 is in fact the short problename, which has to match with the spec in BOCA)
schecker=
if [ ${2: -3} == ".sh" ]; then
  schecker=$2
  chmod 755 "$schecker"
else
  if [ "$4" != "" ]; then
    schecker=`which "bocachecker.$4"`
  fi
fi
if [ -x "$schecker" ]; then
  echo "Calling special checker $schecker"
  "$schecker" "$@"
  ret=$?
  if [ "$ret" == "0" ]; then
	  echo "Checker answered YES"
      exit 4
  fi
  if [ "$ret" == "1" ]; then
	  echo "Checker answered WRONG ANSWER"
    exit 6
  fi
  if [ "$ret" == "2" ]; then
	  echo "Checker answered OUTPUT FORMAT ERROR"
    exit 5
  fi
  echo "special checker returned unknown code"
  exit 43
fi

# Next lines of this script just compares team_output and sol_output,
# although it is possible to change them to more complex evaluations.

diff -q "$1" "$2" >/dev/null 2>/dev/null
if [ "$?" == "0" ]; then
  echo -e "diff \"$1\" \"$2\" # files match"
  echo "Files match exactly"
  exit 4
fi
diff -q -b "$1" "$2" >/dev/null 2>/dev/null
if [ "$?" == "0" ]; then
  echo -e "diff -c -b \"$1\" \"$2\" # files match"
  echo -e "diff -c \"$1\" \"$2\" # files dont match - see output"
  diff -c "$1" "$2"
  echo "Files match with differences in the amount of white spaces"
  exit 5
fi
diff -q -b -B "$1" "$2" >/dev/null 2>/dev/null
if [ "$?" == "0" ]; then
  echo -e "diff -c -b -B \"$1\" \"$2\" # files match"
  echo -e "diff -c -b \"$1\" \"$2\" # files dont match - see output"
  diff -c -b "$1" "$2"
  echo "Files match with differences in the amount of white spaces and blank lines"
  exit 5
fi
diff -q -i -b -B "$1" "$2" >/dev/null 2>/dev/null
if [ "$?" == "0" ]; then
  echo -e "diff -c -i -b -B \"$1\" \"$2\" # files match"
  echo -e "diff -c -b -B \"$1\" \"$2\" # files dont match - see output"
  diff -c -b -B "$1" "$2"
  echo "Files match if we ignore case and differences in the amount of white spaces and blank lines"
  exit 5
fi
diff -q -b -B -w "$1" "$2" >/dev/null 2>/dev/null
if [ "$?" == "0" ]; then
  echo -e "diff -c -b -B -w \"$1\" \"$2\" # files match"
  echo -e "diff -c -i -b -B \"$1\" \"$2\" # files dont match - see output"
  diff -c -i -b -B "$1" "$2"
  echo "Files match if we discard all white spaces"
  exit 5
fi
diff -q -i -b -B -w "$1" "$2" >/dev/null 2>/dev/null
if [ "$?" == "0" ]; then
  echo -e "diff -c -i -b -B -w \"$1\" \"$2\" # files match"
  echo -e "diff -c -b -B -w \"$1\" \"$2\" # files dont match - see output"
  diff -c -b -B -w "$1" "$2"
  echo "Files match if we ignore case and discard all white spaces"
  exit 5
fi
wd=`which wdiff`
if [ "$wd" != "" ]; then
  wdiff \"$1\" \"$2\" >/dev/null 2>/dev/null
  if [ "$?" == "0" ]; then
    echo -e "wdiff \"$1\" \"$2\" # files match"
    echo -e "diff -c -i -b -B -w \"$1\" \"$2\" # files dont match - see output" 
    diff -c -i -b -B -w "$1" "$2"
    echo "BUT Files match if we compare word by word, ignoring everything else, using wdiff"
    echo "diff has a bug that, if a line contains a single space, this is not discarded by -w"
    exit 5
  fi
fi
echo -e "### files dont match - see output"
diff -c -i -b -B -w "$1" "$2"
echo "Differences found"
exit 6
