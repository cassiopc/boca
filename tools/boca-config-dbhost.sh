#!/bin/bash
# /////////////////////////////////////////////////////////////////////////////
# //BOCA Online Contest Administrator
# //    Copyright (C) 2016- by BOCA Development Team (bocasystem@gmail.com)
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
# /////////////////////////////////////////////////////////////////////////////
# // Last modified 15/Sep/2016 by brunoribas@utfpr.edu.br

if ! whoami | grep -q '^root$' ; then
  echo "$0 must be run as root"
  exit 1
fi

if (( $# != 1 )); then
  echo "Usage:"
  echo "  $0 bdserver-ip|localhost"
  echo
  echo "Parameter should be localhost if the postgres is running localhost, or"
  echo "the IP address of the postgres"
  exit 0
fi

bdservernew=$1

. /etc/boca.conf

CHANGE=n
if [[ "x$bdserver" == "x" ]]; then
  echo "bdserver=$bdservernew" >> /etc/boca.conf
else
  CHANGE=y
  VV="$(grep -v '^bdserver=' /etc/boca.conf)"
  printf "bdserver=$bdservernew\n$VV\n" > /etc/boca.conf
fi

bdserver=$bdservernew

if [[ "$bdserver" == "localhost" && "x$bdcreated" != "xy" ]]; then
  if [[ "$CHANGE" == "n" ]]; then
    boca-createdb
  else
    boca-createdb -f
  fi
elif [[ "$bdserver" != "localhost" ]]; then
  printf "You will be asked to prompt the BD password [enter do continue]"
  read
  #just to config password
  if [[ "$CHANGE" == "n" ]]; then
    boca-createdb nocreate
  else
    boca-createdb -f nocreate
  fi
fi

exit 0
