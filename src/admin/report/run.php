<?php
////////////////////////////////////////////////////////////////////////////////
//BOCA Online Contest Administrator
//    Copyright (C) 2003-2013 by BOCA Development Team (bocasystem@gmail.com)
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
// Last modified 06/sep/2013 by cassio@ime.usp.br

require('header.php');
?>
<br>
<center><h2>Run List</h2></center>
<table width="100%" border=1>
 <tr>
  <td><b>#</b></td>
  <td><b>Site</b></td>
  <td><b>User</b></td>
  <td><b>Time</b></td>
  <td><b>Problem</b></td>
  <td><b>Language</b></td>
  <td><b>Filename</b></td>
  <td><b>Status</b></td>
  <td><b>Judge (Site)</b></td>
  <td><b>Answer</b></td>
 </tr>
<?php
$s = $st;
// forca aparecer as runs do proprio site
if (trim($s["sitejudging"])!="") $s["sitejudging"].=",".$_SESSION["usertable"]["usersitenumber"];
else $s["sitejudging"]=$_SESSION["usertable"]["usersitenumber"];

$run = DBAllRunsInSites($_SESSION["usertable"]["contestnumber"], $s["sitejudging"], 'report');

for ($i=0; $i<count($run); $i++) {
  echo " <tr>\n";
  echo "  <td nowrap>" . $run[$i]["number"] . "</td>\n";
  echo "  <td nowrap>" . $run[$i]["site"] . "</td>\n";
  if ($run[$i]["user"] != "") {
	$u = DBUserInfo ($_SESSION["usertable"]["contestnumber"], $run[$i]["site"], $run[$i]["user"]);
	echo "  <td nowrap>" . $u["userfullname"] . "</td>\n";
  }
  echo "  <td nowrap>" . dateconvminutes($run[$i]["timestamp"]) . "</td>\n";

  if($run[$i]["status"] == "deleted") {
    echo "<td>&nbsp;</td>\n";
    echo "<td>&nbsp;</td>\n";
    echo "<td>&nbsp;</td>\n";
    echo "  <td nowrap>" . $run[$i]["status"] . "</td>\n";
    if ($run[$i]["judge"] != "") {
	$u = DBUserInfo ($_SESSION["usertable"]["contestnumber"], $run[$i]["judgesite"], $run[$i]["judge"]);
	echo "  <td nowrap>" . $u["username"] . " (" . $run[$i]["judgesite"] . ")</td>\n";
    } else
	echo "  <td>&nbsp;</td>\n";
    echo "<td>&nbsp;</td>\n";
    echo "</tr>";
    continue;
  }

  echo "  <td nowrap>" . $run[$i]["problem"];
  if($run[$i]["colorname"] != "")
    echo "(".$run[$i]["colorname"].")";
  echo "</td>\n";
  echo "  <td nowrap>" . $run[$i]["language"] . "</td>\n";
  echo "  <td nowrap>" . $run[$i]["filename"] . "</td>\n";

  echo "  <td nowrap>" . $run[$i]["status"] . "</td>\n";
  if ($run[$i]["judge"] != "") {
	$u = DBUserInfo ($_SESSION["usertable"]["contestnumber"], $run[$i]["judgesite"], $run[$i]["judge"]);
	echo "  <td nowrap>" . $u["username"] . " (" . $run[$i]["judgesite"] . ")</td>\n";
  } else
	echo "  <td>&nbsp;</td>\n";

  if ($run[$i]["answer"] == "") $run[$i]["answer"] = "&nbsp;";
  echo "  <td>" . $run[$i]["answer"] . "</td>\n";
  echo " </tr>\n";
}

echo "</table>";
if (count($run) == 0) echo "<br><center><b><font color=\"#ff0000\">NO RUNS AVAILABLE</font></b></center>";

include("$locr/footnote.php");
?>
