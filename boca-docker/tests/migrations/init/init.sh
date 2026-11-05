#!/bin/bash
#========================================================================
# Copyright Universidade Federal do Espirito Santo (Ufes)
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <https://www.gnu.org/licenses/>.
# 
# This program is released under license GNU GPL v3+ license.
#
#========================================================================

set -e

# With psql
# Supports: plain text SQL restore only
if [[ -f "$BOCA_DB_DUMP_FILENAME" ]];
then
  # Set ON_ERROR_STOP=OFF, otherwise restore does not work as expected
  psql \
    -f "$BOCA_DB_DUMP_FILENAME" \
    -v ON_ERROR_STOP=OFF \
    postgres
fi
