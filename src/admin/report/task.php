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
require('header.php');

?>
<br>
<center><h2>Task List</h2></center>
<table width="100%" border=1>
 <tr>
  <td><b>#</b></td>
  <td><b>Time</b></td>
  <td><b>User / Site</b></td>
  <td><b>Description</b></td>
  <td><b>File</b></td>
  <td><b>Staff / Site</b></td>
  <td><b>Status</b></td>
 </tr>
<?php
$s = $st;
$task = DBAllTasksInSites($_SESSION["usertable"]["contestnumber"], $s["sitetasking"], 'report');
$cf = globalconf();
for ($i=0; $i<count($task); $i++) {
  $st = $task[$i]["status"];

  if($st == "processing" && $task[$i]["staff"]==$_SESSION["usertable"]["usernumber"] &&
	 $task[$i]["staffsite"]==$_SESSION["usertable"]["usersitenumber"]) $mine=1;
  else $mine=0;

  echo " <tr>\n";
  echo "  <td nowrap>" . $task[$i]["number"] . "</td>\n";
  echo "  <td nowrap>" . dateconvminutes($task[$i]["timestamp"]) . "</td>\n";
  echo "  <td nowrap>".$task[$i]["username"]." / ".$task[$i]["site"]."</td>\n";
  echo "  <td>" . $task[$i]["description"] . "</td>\n";
  if ($task[$i]["oid"] != null) {
    echo "  <td nowrap>" . $task[$i]["filename"];
             echo "</td>\n";

  }
  else
    echo "  <td nowrap>&nbsp;</td>\n";
  if($st != "opentask")
    echo "  <td nowrap>". $task[$i]["staffname"] . "(" . $task[$i]["staff"] .") / ".$task[$i]["staffsite"]."</td>\n";
  else
    echo "  <td nowrap>&nbsp;</td>\n";

  echo "  <td nowrap>$st</td>\n";
}
echo "</table>";
if (count($task) == 0) echo "<br><center><b><font color=\"#ff0000\">NO TASKS FOUND</font></b></center>";

include("$locr/footnote.php");
?>
