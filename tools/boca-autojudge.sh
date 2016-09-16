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


if ! whoami |grep -q root; then
  echo "$0 must be run as root"
  exit 1
fi

if [[ ! -e /bocajail ]]; then
  echo "Bocajail not found. Please run boca-createjail"
  exit 1
fi

. /etc/boca.conf

if [[ "x$bdserver" == "x" && "x$bdcreated" == "x" ]];then
  if grep dbhost $bocadir/src/private/conf.php|grep -q localhost;then
    echo "It was found no evidence that this machine is running a BOCA BD"
    echo "Please consider running 'boca-config-dbhost' before"
    exit 2
  fi
fi

cd $bocadir/src/private
php autojudging.php
