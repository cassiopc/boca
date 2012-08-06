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
// updated 20/oct/08 by cassio@ime.usp.br
//  -  bugfix of Marcelo Cezar Pinto (mcpinto@unipampa.edu.br) - div by zero at counts
require('header.php');
?>
<br>
<table width="100%" border=1>
 <tr>
  <td><b>Clar # (site)</b></td>
  <td><b>Time</b></td>
  <td><b>Problem</b></td>
  <td><b>Status</b></td>
  <td><b>Question</b></td>
  <td><b>Answer</b></td>
 </tr>
<?php
$clar = DBAllClarsInSites($_SESSION["usertable"]["contestnumber"],  $s["sitejudging"], "clar");
//$clar = DBJudgedClars($_SESSION["usertable"]["contestnumber"],
//		      $_SESSION["usertable"]["usersitenumber"],
//		      $_SESSION["usertable"]["usernumber"]);
$myclars = 0;
for ($i=0; $i<count($clar); $i++) {
  echo " <tr>\n";
  if($clar[$i]["judge"] == $_SESSION["usertable"]["usernumber"] &&
     $clar[$i]["judgesite"] == $_SESSION["usertable"]["usersitenumber"]) {
    echo "  <td nowrap bgcolor=\"#b0b0a0\">" . $clar[$i]["number"] . "(" . $clar[$i]["site"] . ")</td>\n";
    $myclars++;
  }
  else
    echo "  <td nowrap>" . $clar[$i]["number"] . "(" . $clar[$i]["site"] . ")</td>\n";
  echo "  <td nowrap>" . dateconvminutes($clar[$i]["timestamp"]) . "</td>\n";
  echo "  <td nowrap>" . $clar[$i]["problem"] . "</td>\n";
  echo "  <td nowrap>" . $clar[$i]["status"] . "</td>\n";
  if ($clar[$i]["question"] == "") $clar[$i]["question"] = "&nbsp;";
  if ($clar[$i]["answer"] == "") $clar[$i]["answer"] = "&nbsp;";

  echo "  <td>";
//  echo "<pre>" . $clar[$i]["question"] . "</pre>";
//  echo $clar[$i]["question"];
  echo "  <textarea name=\"m$i\" cols=\"60\" rows=\"8\" readonly>".$clar[$i]["question"]."</textarea>\n";
  echo "</td>\n";
  if (trim($clar[$i]["answer"]) == "") $clar[$i]["answer"] = "Not answered yet";
  echo "  <td>";
//  echo "  <pre>" . $clar[$i]["answer"] . "</pre>";
//  echo $clar[$i]["answer"];
  echo "  <textarea name=\"a$i\" cols=\"60\" rows=\"8\" readonly>".$clar[$i]["answer"]."</textarea>\n";
  echo "</td>\n";

  echo " </tr>\n";
}
echo "</table>";
if (count($clar) == 0) echo "<br><center><b><font color=\"#ff0000\">NO CLARIFICATIONS AVAILABLE</font></b></center>";
else echo "<br><b><font color=\"#b0b0a0\">* Shadowed clars and runs were judged by this judge</font></b>";

?>
<br><br>
<table width="100%" border=1>
 <tr>
  <td><b>Run #</b></td>
  <td><b>Time</b></td>
  <td><b>Problem</b></td>
  <td><b>Language</b></td>
  <td><b>Status</b></td>
  <td><b>Answer</b></td>
 </tr>
<?php
$run = DBAllRunsInSites($_SESSION["usertable"]["contestnumber"],
  	  	     $s["sitejudging"],
		    "run");
//$run = DBJudgedRuns($_SESSION["usertable"]["contestnumber"],
//  	  	    $_SESSION["usertable"]["usersitenumber"],
//		    $_SESSION["usertable"]["usernumber"]);
$yes = 0;
$myyes = 0;
$myruns = 0;
for ($i=0; $i<count($run); $i++) {
  echo " <tr>\n";
  if($run[$i]["yes"]=="t") $yes++;
  if(($_SESSION["usertable"]["usersitenumber"] == $run[$i]["judgesite1"] &&
     $_SESSION["usertable"]["usernumber"] == $run[$i]["judge1"]) ||
	 ($_SESSION["usertable"]["usersitenumber"] == $run[$i]["judgesite2"] &&
	  $_SESSION["usertable"]["usernumber"] == $run[$i]["judge2"])) {
    echo "  <td nowrap bgcolor=\"#b0b0a0\">" . $run[$i]["number"] . "</td>\n";
    if($run[$i]["yes"]=="t") $myyes++;
    $myruns++;
  }
  else
    echo "  <td nowrap>" . $run[$i]["number"] . "</td>\n";
  echo "  <td nowrap>" . dateconvminutes($run[$i]["timestamp"]) . "</td>\n";
  echo "  <td nowrap>" . $run[$i]["problem"] . "</td>\n";
  echo "  <td nowrap>" . $run[$i]["language"] . "</td>\n";
  echo "  <td nowrap>" . $run[$i]["status"] . "</td>\n";
  if ($run[$i]["answer"] == "") $run[$i]["answer"] = "&nbsp;";
  echo "  <td>" . $run[$i]["answer"] . "</td>\n";
  echo " </tr>\n";
}
echo "</table>";
if (count($run) == 0) echo "<br><center><b><font color=\"#ff0000\">NO RUNS AVAILABLE</font></b></center>";

echo "<br><br>\n";
echo "My answered clars: " . $myclars . "/" . count($clar) . " (";
if(count($clar)>0) echo ((int) ($myclars*1000/count($clar)))/10 . "%)<br>\n";
else echo "0%)<br>\n";
echo "My judged runs: " . $myruns . "/" . count($run) ." (";
if(count($run)>0) echo ((int) ($myruns*1000/count($run)))/10 . "%)<br>\n";
else echo "0%)<br>\n";
echo "Accepted runs that I've judged: " . $myyes . "/" . $yes . " (";
if($yes>0) echo ((int) ($myyes*1000/$yes))/10 ."%)<br>\n";
else echo "0%)<br>\n";
?>
</body>
</html>
