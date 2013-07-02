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
require 'header.php';
if(isset($_GET["order"]) && $_GET["order"] != "") {
$order = myhtmlspecialchars($_GET["order"]);
	$_SESSION["runline"] = $order;
} else {
	if(isset($_SESSION["runline"]))
  $order = $_SESSION["runline"];
else
		$order = '';
}
?>

<form name="form1" method="post" action="<?php echo $runphp; ?>">
  <input type=hidden name="confirmation" value="noconfirm" />
<br>
<table width="100%" border=1>
 <tr>
  <td><b><a href="<?php echo $runphp; ?>?order=run">Run #</a></b></td>
  <td><b><a href="<?php echo $runphp; ?>?order=site">Site</a></b></td>
<?php if($runphp == "run.php") { ?>
  <td><b><a href="<?php echo $runphp; ?>?order=user">User</a></b></td>
<?php } ?>
  <td><b>Time</b></td>
  <td><b><a href="<?php echo $runphp; ?>?order=problem">Problem</a></b></td>
  <td><b><a href="<?php echo $runphp; ?>?order=language">Language</a></b></td>
<!--  <td><b>Filename</b></td> -->
  <td><b><a href="<?php echo $runphp; ?>?order=status">Status</a></b></td>
  <td><b><a href="<?php echo $runphp; ?>?order=judge">Judge (Site)</a></b></td>
  <td><b>AJ</b></td>
  <td><b><a href="<?php echo $runphp; ?>?order=answer">Answer</a></b></td>
 </tr>
<?php
if (($s=DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
        ForceLoad("../index.php");

// forca aparecer as runs do proprio site
if (trim($s["sitejudging"])!="") $s["sitejudging"].=",".$_SESSION["usertable"]["usersitenumber"];
else $s["sitejudging"]=$_SESSION["usertable"]["usersitenumber"];

$run = DBAllRunsInSites($_SESSION["usertable"]["contestnumber"], $s["sitejudging"], $order);

if(isset($_POST)) {
  $nrenew = 0;
  $nreopen = 0;
  for ($i=0; $i<count($run); $i++) {
	  if(isset($_POST["cbox_" . $run[$i]["number"] . "_" . $run[$i]["site"]]) && 
		 $_POST["cbox_" . $run[$i]["number"] . "_" . $run[$i]["site"]] != "") {
		  if(isset($_POST["auto"]) && $_POST["auto"]=="Re-run autojudge for selected runs") {
        if (DBGiveUpRunAutojudging($_SESSION["usertable"]["contestnumber"], 
	    $run[$i]["site"], $run[$i]["number"]))
	    $nrenew++;
      }
		  if(isset($_POST["open"]) && $_POST["open"]=="Open selected runs for rejudging") {
			  DBGiveUpRunAutojudging($_SESSION["usertable"]["contestnumber"], 
									 $run[$i]["site"], $run[$i]["number"]);
			  if (DBChiefRunGiveUp($run[$i]["number"], $run[$i]["site"], 
								   $_SESSION["usertable"]["contestnumber"]))
				  $nreopen++;
		  }
    }
  }
  if($nrenew > 0) {
    MSGError($nrenew . " runs renewed for autojudging.");
    ForceLoad($runphp);
  }
  if($nreopen > 0) {
    MSGError($nreopen . " runs reopened.");
    ForceLoad($runphp);
  }
}


for ($i=0; $i<count($run); $i++) {
  if($run[$i]["answer1"] != 0 && $run[$i]["answer2"] != 0 && $run[$i]["status"] != "judged") {
    if($runphp == "runchief.php")
      echo " <tr bgcolor=\"ff0000\">\n";
    else echo "<tr>\n";
    echo "  <td nowrap bgcolor=\"ff0000\">";
  }
  else {
    echo "  <tr><td nowrap>";
  }
  echo "<input type=\"checkbox\" name=\"cbox_" . $run[$i]["number"] . "_" . $run[$i]["site"] . "\" />"; 
  echo " <a href=\"" . $runeditphp . "?runnumber=".$run[$i]["number"]."&runsitenumber=".$run[$i]["site"] .
       "\">" . $run[$i]["number"] . "</a></td>\n";

  echo "  <td nowrap>" . $run[$i]["site"] . "</td>\n";
  if($runphp == "run.php") {
    if ($run[$i]["user"] != "") {
	$u = DBUserInfo ($_SESSION["usertable"]["contestnumber"], $run[$i]["site"], $run[$i]["user"]);
	echo "  <td nowrap>" . $u["username"] . "</td>\n";
    }
  }
  echo "  <td nowrap>" . dateconvminutes($run[$i]["timestamp"]) . "</td>\n";
  echo "  <td nowrap>" . $run[$i]["problem"] . "</td>\n";
  echo "  <td nowrap>" . $run[$i]["language"] . "</td>\n";
//  echo "  <td nowrap>" . $run[$i]["filename"] . "</td>\n";
  if ($run[$i]["judge"] == $_SESSION["usertable"]["usernumber"] && 
      $run[$i]["judgesite"] == $_SESSION["usertable"]["usersitenumber"] && $run[$i]["status"] == "judging")
    $color="ff7777";
  else if ($run[$i]["status"]== "judged+" && $run[$i]["judge"]=="") $color="ffff00";
  else if ($run[$i]["status"]== "judged") $color="bbbbff";
  else if ($run[$i]["status"] == "judging" || $run[$i]["status"]== "judged+") $color="77ff77";
  else if ($run[$i]["status"] == "openrun") $color="ffff88";
  else $color="ffffff";

  echo "  <td nowrap bgcolor=\"#$color\">" . $run[$i]["status"] . "</td>\n";
  if ($run[$i]["judge"] != "") {
	$u = DBUserInfo ($_SESSION["usertable"]["contestnumber"], $run[$i]["judgesite"], $run[$i]["judge"]);
	echo "  <td nowrap>" . $u["username"] . " (" . $run[$i]["judgesite"] . ")";
  } else
	echo "  <td>&nbsp;";

  if ($run[$i]["judge1"] != "") {
	$u = DBUserInfo ($_SESSION["usertable"]["contestnumber"], $run[$i]["judgesite1"], $run[$i]["judge1"]);
	echo " [" . $u["username"] . " (" . $run[$i]["judgesite1"] . ")]";
  }
  if ($run[$i]["judge2"] != "") {
	$u = DBUserInfo ($_SESSION["usertable"]["contestnumber"], $run[$i]["judgesite2"], $run[$i]["judge2"]);
	echo " [" . $u["username"] . " (" . $run[$i]["judgesite2"] . ")]";
  }

  echo "</td>\n";

  if ($run[$i]["autoend"] != "") {
    $color="bbbbff";
    if ($run[$i]["autoanswer"]=="") $color="ff7777";
  }
  else if ($run[$i]["autobegin"]=="") $color="ffff88";
  else $color="77ff77";
  echo "<td bgcolor=\"#$color\">&nbsp;&nbsp;</td>\n";

  if ($run[$i]["answer"] == "") {
    echo "  <td>&nbsp;</td>\n";
  } else {
    echo "  <td>" . $run[$i]["answer"];
    if($run[$i]['yes']=='t') {
          echo " <img alt=\"".$run[$i]["colorname"]."\" width=\"10\" ".
			  "src=\"" . balloonurl($run[$i]["color"]) ."\" />";
    }
    echo "</td>\n";
  }
  echo " </tr>\n";
}

echo "</table>";
if (count($run) == 0) echo "<br><center><b><font color=\"#ff0000\">NO RUNS AVAILABLE</font></b></center>";
else {
?>
  <br>
  <script language="javascript">
    function conf() {
      if (confirm("Confirm?")) {
        document.form1.confirmation.value='confirm';
      }
    }
  </script>
  <center>
<b>Click on the number of a run to edit it or select them with<br />the checkboxes and use the buttons to work on multiple runs:</b><br /><br />
      <input type="submit" name="auto" value="Re-run autojudge for selected runs" onClick="conf()">
      <input type="submit" name="open" value="Open selected runs for rejudging" onClick="conf()">
<br><br>
  </center>
  </form>
<?php
}
?>
</body>
</html>
