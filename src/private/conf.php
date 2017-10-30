<?php
////////////////////////////////////////////////////////////////////////////////
//BOCA Online Contest Administrator
//    Copyright (C) 2003-2012 by BOCA Development Team (bocasystem@gmail.com)
//
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//    You should have received a copy of the GNU General Public License
//    along with this program.  If not, see <http://www.gnu.org/licenses/>.
////////////////////////////////////////////////////////////////////////////////
// Last modified 05/aug/2012 by cassio@ime.usp.br

function globalconf() {
 $conf["dbencoding"]="UTF8";
 $conf["dbclientenc"]="UTF8";
 $conf['doenc']=false;

 $conf["dblocal"]="false"; // use unix socket to connect?
$conf["dbhost"]="localhost";
$conf["dbport"]="5432";

  $conf["dbname"]="bocadb"; // name of the boca database

  $conf["dbuser"]="bocauser"; // unprivileged boca user
$conf["dbpass"]="dAm0HAiC";

  $conf["dbsuperuser"]="bocauser"; // privileged boca user
$conf["dbsuperpass"]="dAm0HAiC";

        // note that it is fine to use the same user

  // initial password that is used for the user admin -- set it
  // to something hard to guess if the server is available
  // online even in the moment you are creating the contest
  // In this way, the new accounts for system and admin that are
  // eventually created come already with the password set to this
  // value. It is your task later to update these passwords to
  // some other values within the BOCA web interface.
  $conf["basepass"]="boca";

  // secret key to be used in HTTP headers
  // you MUST set it with any random large enough sequence
$conf["key"]="GG56KFJtNDBGjJprR6ex";


  // the following field is used by the autojudging script
  // set it with the ip of the computer running the script
  // The real purpose of it is only to differentiate between
  // autojudges when multiple computers are used as autojudges
  $conf["ip"]='local';

  return $conf;
}
?>
