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
//$locr = $_SESSION['locr'];
//$loc = $_SESSION['loc'];
$loc = $locr = "../..";

require $locr.'/version.php';
require_once($locr . "/globals.php");
if(!ValidSession()) {
        InvalidSession($_SERVER['PHP_SELF']);
        ForceLoad($loc."/index.php");
}
if($_SESSION["usertable"]["usertype"] != "admin") {
        IntrusionNotify($_SERVER['PHP_SELF']);
        ForceLoad($loc."/index.php");
}

require_once($locr."/db.php");
require_once($locr."/freport.php");

echo "<html><head><title>Report Page</title>\n";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">\n";

echo "<link rel=stylesheet href=\"$loc/Css.php\" type=\"text/css\">\n";

$contest=$_SESSION["usertable"]["contestnumber"];
if(($ct = DBContestInfo($contest)) == null)
        ForceLoad($loc."/index.php");
$site=$_SESSION["usertable"]["usersitenumber"];
if(($st = DBSiteInfo($contest,$site)) == null)
        ForceLoad($loc."/index.php");

echo "</head><body><table border=1 width=\"100%\">\n";
echo "<tr><td bgcolor=\"eeee00\" nowrap align=center>";
echo "<img src=\"$loc/images/smallballoontransp.png\" alt=\"\">";
echo "<font color=\"#ffffff\"><a href=\"http://www.ime.usp.br/~cassio/boca/\">BOCA</a></font>";
echo "</td><td bgcolor=\"#eeee00\" width=\"99%\">\n";
echo $ct["contestname"] . " - " . $st["sitename"] . "</td>\n";
echo "</tr></table>\n";
?>
