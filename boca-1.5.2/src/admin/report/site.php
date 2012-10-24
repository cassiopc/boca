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

$sitetime = DBAllSiteTime($_SESSION["usertable"]["contestnumber"], $site);
?>
<br />
<center><h2>Site Start/Stop Logs</h2></center>

    <table border="0">
<?php
echo "        <td nowrap width=\"50%\" align=right>Start date (contest=" . dateconv($ct["conteststartdate"]) . "):</td>";
echo "<td width=\"50%\"><b>" . dateconv($st["sitestartdate"]) . "</b></td>\n";
echo "<tr>";
if (!$st["siterunning"]) {
	echo "      <tr>\n";
	echo "        <td nowrap width=\"50%\" align=right><b>Finished at:</b></td>\n";
	echo "        <td width=\"50%\"><b>" . dateconv($st["siteendeddate"]); 
	if($st["siteautoended"]) echo " (auto)";
	echo "</b></td>\n";
	echo "      </tr>\n";
	if($st["siteautoended"]) {
		$w = (int) ($st["siteduration"]/60);
		$ww = $st["siteduration"] % 60;
	}
	else {
		$w = (int) ($st["currenttime"]/60);
		$ww = $st["currenttime"] % 60;
	}
	echo "      <tr>\n";
	echo "        <td nowrap width=\"50%\" align=right><b>Real duration:</b></td>\n";
	echo "        <td width=\"50%\"><b>$w minutes";
	if($ww != 0)	echo " plus $ww seconds";
	echo "</b></td>\n";
	echo "      </tr>\n";
}
echo "<tr>\n";
echo "        <td width=\"50%\" align=right>Planned Duration (contest=";
echo $ct["contestduration"]/60;
echo "):</td>";
?>
        <td width="50%">
          <?php echo $st["siteduration"]/60; ?>
        </td>
      </tr>
      <tr>
<?php
echo "        <td width=\"50%\" align=right>Stop answering (contest=";
echo $ct["contestlastmileanswer"]/60;
echo "):</td>";
?>
        <td width="50%">
          <?php echo $st["sitelastmileanswer"]/60; ?>
        </td>
      </tr>
      <tr>
<?php
echo "        <td width=\"50%\" align=right>Stop scoreboard (contest=";
echo $ct["contestlastmilescore"]/60;
echo "):</td>";
?>
        <td width="50%">
          <?php echo $st["sitelastmilescore"]/60; ?>
        </td>
      </tr>
      <tr>
        <td width=\"50%\" align=right>Number of Clars:</td>
        <td width="50%"><?php echo $st["sitenextclar"]; ?>
        </td>
      </tr>
      <tr>
        <td width=\"50%\" align=right>Number of Runs:</td>
        <td width="50%"><?php echo $st["sitenextrun"]; ?>
        </td>
      </tr>
      <tr>
        <td width=\"50%\" align=right>Number of Tasks:</td>
        <td width="50%"><?php echo $st["sitenexttask"]; ?>
        </td>
      </tr>
    </table>
  </center>
<center>
<br />
<table border=1>
<tr>
<td nowrap width=\"50%\" align=right>Starting at</td><td nowrap width=\"50%\" align=left>Ending at</td>
</tr>
<?php
$n = count($sitetime);
for ($i=0; $i< $n; $i++) {
  echo "<tr>";
  echo "<td nowrap align=right>";
  echo dateconv($sitetime[$i]["sitestartdate"]);
  echo "</td>";
  echo "<td nowrap align=left>";
  if($sitetime[$i]["siteenddate"] == 0) {
    if($st["siterunning"])
      echo "still open";
    else echo "auto-ended";
  }
  else
    echo dateconv($sitetime[$i]["siteenddate"]);
  echo "</td>";
  echo "</tr>";
}
?>
</table>
</center>

<?php include("$locr/footnote.php"); ?>
