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
ob_start();
header ("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header ("Cache-Control: no-cache, must-revalidate");
header ("Pragma: no-cache");
header ("Content-Type: text/html; charset=utf-8");
session_start();
ob_end_flush();
require_once('../version.php');

require_once("../globals.php");
require_once("../db.php");

echo "<html><head><title>Score Board Page</title>\n";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">\n";
echo "<link rel=stylesheet href=\"../Css.php\" type=\"text/css\">\n";
echo "<meta http-equiv=\"refresh\" content=\"60\" />"; 

$ret=1;
if (!isset($_SESSION["usertable"])) $ret=0;
else {
        $_SESSION["usertable"] = DBUserInfo($_SESSION["usertable"]["contestnumber"],
                                $_SESSION["usertable"]["usersitenumber"], $_SESSION["usertable"]["usernumber"]);
        if ($_SESSION["usertable"]["usersession"] != session_id() && $_SESSION["usertable"]["usermultilogin"]!="t")
                $ret=0;
}

if($ret==0) {
	InvalidSession("score/index.php");
        ForceLoad("../index.php");
}
if($_SESSION["usertable"]["usertype"] != "score") {
	IntrusionNotify("score/index.php");
        ForceLoad("../index.php");
}

echo "</head><body><table border=1 width=\"100%\">\n";
echo "<tr><td nowrap bgcolor=\"#cc9966\" align=center>";
echo "<img src=\"../images/smallballoontransp.png\" alt=\"\">";
echo "<font color=\"#000000\">BOCA</font>";
echo "</td><td bgcolor=\"#cc9966\" width=\"99%\">\n";
echo "Username: " . $_SESSION["usertable"]["userfullname"] . " (site=".$_SESSION["usertable"]["usersitenumber"].")<br>\n";
//list($clockstr,$clocktype)=siteclock(); // THIS IS SLOW, WE NEED A BETTER OPTION
$clockstr='';
echo "</td><td bgcolor=\"#cc9966\" align=center nowrap>&nbsp;".$clockstr."&nbsp;</td></tr>\n";
echo "</table>\n";
if(!isset($_SESSION["scorenomenu"]) || !$_SESSION["scorenomenu"]) {
	echo "<table border=0 width=\"100%\" align=center>\n";
	echo " <tr>\n";
	echo "  <td align=center><a class=menu style=\"font-weight:bold\" href=index.php>Score</a></td>\n";
	echo "  <td align=center><a class=menu style=\"font-weight:bold\" href=../index.php>Logout</a></td>\n";
	echo " </tr>\n";
	echo "</table>\n";
}
?>
