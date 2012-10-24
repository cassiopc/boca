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
require('header.php');
?>

<br>
<table width="100%" border=1>
 <tr>
  <td><b>Run #</b></td>
  <td><b>Site</b></td>
  <td><b>Time</b></td>
  <td><b>Problem</b></td>
  <td><b>Language</b></td>
<!--  <td><b>Filename</b></td> -->
  <td><b>Status</b></td>
  <td><b>AJ</b></td>
  <td><b>Answer</b></td>
 </tr>
<?php
if (($s=DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
        ForceLoad("../index.php");

$run = DBOpenRunsInSites($_SESSION["usertable"]["contestnumber"], $s["sitejudging"]);

for ($i=0; $i<count($run); $i++) {
  echo " <tr>\n";
//  if (strpos($run[$i]["status"], "judged") === false || $run[$i]["judge"]=="" || $run[$i]["judge"]==$_SESSION["usertable"]["usernumber"])
  echo "  <td nowrap><a href=\"runedit.php?runnumber=".$run[$i]["number"]."&runsitenumber=".$run[$i]["site"] .
         "\">" . $run[$i]["number"] . "</td>\n";
//  else
//    echo "  <td nowrap>" . $run[$i]["number"] . "</td>\n";
  echo "  <td nowrap>" . $run[$i]["site"] . "</td>\n";
  echo "  <td nowrap>" . dateconvminutes($run[$i]["timestamp"]) . "</td>\n";
  echo "  <td nowrap>" . $run[$i]["problem"] . "</td>\n";
  echo "  <td nowrap>" . $run[$i]["language"] . "</td>\n";
//  echo "  <td nowrap>" . $run[$i]["filename"] . "</td>\n";
  if ($run[$i]["judge1"] == $_SESSION["usertable"]["usernumber"] && 
      $run[$i]["judgesite1"] == $_SESSION["usertable"]["usersitenumber"])
    $color="ff7777";
  else if ($run[$i]["judge2"] == $_SESSION["usertable"]["usernumber"] && 
      $run[$i]["judgesite2"] == $_SESSION["usertable"]["usersitenumber"])
    $color="ff7777";
  else if ($run[$i]["status"] == "judged+") $color="ffff00";
  else if ($run[$i]["status"] == "judged") $color="0000ff";
  else if ($run[$i]["status"] == "judging") $color="77ff77";
  else if ($run[$i]["status"] == "openrun") $color="ffff88";
  else $color="ffffff";

  echo "  <td nowrap bgcolor=\"#$color\">" . $run[$i]["status"] . "</td>\n";
  if ($run[$i]["autoend"] != "") {
    $color="bbbbff";
    if ($run[$i]["autoanswer"]=="") $color="ff7777";
  }
  else if ($run[$i]["autobegin"]=="") $color="ffff88";
  else $color="77ff77";
  echo "<td bgcolor=\"#$color\">&nbsp;&nbsp;</td>\n";

  if ($run[$i]["answer"] == "") $run[$i]["answer"] = "&nbsp;";
  echo "  <td>" . $run[$i]["answer"] . "</td>\n";
  echo " </tr>\n";
}

echo "</table>";
if (count($run) == 0) echo "<br><center><b><font color=\"#ff0000\">NO RUNS AVAILABLE</font></b></center>";

?>
</body>
</html>
