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
// Last modified 21/jul/2012 by cassio@ime.usp.br

ob_start();
header ("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header ("Cache-Control: no-cache, must-revalidate");
header ("Pragma: no-cache");
header ("Content-Type: text/html; charset=utf-8");
session_start();
if(!isset($_POST['noflush']))
	ob_end_flush();
//$loc = $_SESSION['loc'];
//$locr = $_SESSION['locr'];
$loc = $locr = "..";
$runphp = "run.php";
$runeditphp = "runedit.php";

require_once("$locr/globals.php");
require_once("$locr/db.php");

if(!isset($_POST['noflush'])) {
	require_once("$locr/version.php");
	echo "<html><head><title>Admin's Page</title>\n";
	echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">\n";
	echo "<link rel=stylesheet href=\"$loc/Css.php\" type=\"text/css\">\n";
}

//echo "<meta http-equiv=\"refresh\" content=\"60\" />";
if(!ValidSession()) {
	InvalidSession("admin/index.php");
        ForceLoad("$loc/index.php");
}
if($_SESSION["usertable"]["usertype"] != "admin") {
	IntrusionNotify("admin/index.php");
	ForceLoad("$loc/index.php");
}

if(!isset($_POST['noflush'])) {
	echo "</head><body><table border=1 width=\"100%\">\n";
	echo "<tr><td nowrap bgcolor=\"eeee00\" align=center>";
	echo "<img src=\"../images/smallballoontransp.png\" alt=\"\">";
	echo "<font color=\"#000000\">BOCA</font>";
	echo "</td><td bgcolor=\"#eeee00\" width=\"99%\">\n";
	echo "Username: " . $_SESSION["usertable"]["userfullname"] . " (site=".$_SESSION["usertable"]["usersitenumber"].")<br>\n";
	list($clockstr,$clocktype)=siteclock();
	echo "</td><td bgcolor=\"#eeee00\" align=center nowrap>&nbsp;".$clockstr."&nbsp;</td></tr>\n";
	echo "</table>\n";
	echo "<table border=0 width=\"100%\" align=center>\n";
	echo " <tr>\n";
	echo "  <td align=center><a class=menu style=\"font-weight:bold\" href=run.php>Runs</a></td>\n";
	echo "  <td align=center><a class=menu style=\"font-weight:bold\" href=score.php>Score</a></td>\n";
	echo "  <td align=center><a class=menu style=\"font-weight:bold\" href=clar.php>Clarifications</a></td>\n";
	echo "  <td align=center><a class=menu style=\"font-weight:bold\" href=user.php>Users</a></td>\n";
	echo "  <td align=center><a class=menu style=\"font-weight:bold\" href=problem.php>Problems</a></td>\n";
	echo "  <td align=center><a class=menu style=\"font-weight:bold\" href=language.php>Languages</a></td>\n";
	echo "  <td align=center><a class=menu style=\"font-weight:bold\" href=answer.php>Answers</a></td>\n";
	echo "  <td align=center><a class=menu style=\"font-weight:bold\" href=export.php>Export</a></td>\n";
//echo " </tr></table><hr><table border=0 width=\"100%\" align=center><tr>\n";
	echo " </tr><tr>\n";
	echo "  <td align=center><a class=menu style=\"font-weight:bold\" href=task.php>Tasks</a></td>\n";
	echo "  <td align=center><a class=menu style=\"font-weight:bold\" href=site.php>Site</a></td>\n";
	echo "  <td align=center><a class=menu style=\"font-weight:bold\" href=contest.php>Contest</a></td>\n";
	echo "  <td align=center><a class=menu style=\"font-weight:bold\" href=log.php>Logs</a></td>\n";
	echo "  <td align=center><a class=menu style=\"font-weight:bold\" href=report.php>Reports</a></td>\n";
	echo "  <td align=center><a class=menu style=\"font-weight:bold\" href=files.php>Backups</a></td>\n";
	echo "  <td align=center><a class=menu style=\"font-weight:bold\" href=option.php>Options</a></td>\n";
	echo "  <td align=center><a class=menu style=\"font-weight:bold\" href=$loc/index.php>Logout</a></td>\n";
	echo " </tr>\n"; 
	echo "</table>\n";
}
?>
