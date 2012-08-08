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

if (isset($_POST["cancel"]) && $_POST["cancel"]=="Cancel editing")
        ForceLoad($runphp);

if (isset($_POST["giveup"]) && $_POST["giveup"]=="Renew" &&
    isset($_POST["sitenumber"]) && isset($_POST["number"]) && is_numeric($_POST["number"]) &&
    is_numeric($_POST["sitenumber"])) {
        $sitenumber = myhtmlspecialchars($_POST["sitenumber"]);
        $number = myhtmlspecialchars($_POST["number"]);
        if (DBGiveUpRunAutojudging($_SESSION["usertable"]["contestnumber"], $sitenumber, $number))
            MSGError("Run renewed.");
        ForceLoad($runphp);
}

if (isset($_POST["delete"]) && $_POST["delete"]=="Delete" &&
    isset($_POST["sitenumber"]) && isset($_POST["number"]) && is_numeric($_POST["number"]) &&
    is_numeric($_POST["sitenumber"])) {
        if ($_POST["confirmation"]=="confirm") {
                $sitenumber = myhtmlspecialchars($_POST["sitenumber"]);
                $number = myhtmlspecialchars($_POST["number"]);

                if (DBRunDelete($number, $sitenumber, $_SESSION["usertable"]["contestnumber"],
                             $_SESSION["usertable"]["usernumber"], $_SESSION["usertable"]["usersitenumber"]))
                        MSGError("Run deleted.");
        }
        ForceLoad($runphp);
}

if (isset($_POST["answer"]) && isset($_POST["open"]) && $_POST["open"]=="Open run for rejudging" &&
    isset($_POST["sitenumber"]) && isset($_POST["number"]) && is_numeric($_POST["number"]) &&
    is_numeric($_POST["sitenumber"])) {

	if ($_POST["confirmation"] == "confirm") {
	        $sitenumber = myhtmlspecialchars($_POST["sitenumber"]);
        	$number = myhtmlspecialchars($_POST["number"]);

			DBGiveUpRunAutojudging($_SESSION["usertable"]["contestnumber"], $sitenumber, $number);
			if (DBChiefRunGiveUp($_POST["number"], $_POST["sitenumber"], $_SESSION["usertable"]["contestnumber"]))
				MSGError("Run returned.");
			ForceLoad($runphp);
	}
}

if (isset($_POST["answer"]) && isset($_POST["Submit"]) && $_POST["Submit"]=="Judge" && is_numeric($_POST["answer"]) &&
    isset($_POST["sitenumber"]) && isset($_POST["number"]) && is_numeric($_POST["sitenumber"]) &&
    is_numeric($_POST["number"])) { // && isset($_POST["notifyuser"]) && isset($_POST["updatescore"])) {

	if ($_POST["confirmation"] == "confirm") {
	        $answer = myhtmlspecialchars($_POST["answer"]);
	        $sitenumber = myhtmlspecialchars($_POST["sitenumber"]);
	        $number = myhtmlspecialchars($_POST["number"]);
//      	  $notuser = myhtmlspecialchars($_POST["notifyuser"]);
//	        $updscore = myhtmlspecialchars($_POST["updatescore"]);

	        DBChiefUpdateRun($_SESSION["usertable"]["contestnumber"],
	                     $_SESSION["usertable"]["usersitenumber"],
	                     $_SESSION["usertable"]["usernumber"],
	                     $sitenumber, $number, $answer); //, $notuser, updscore);
	}
        ForceLoad($runphp);
}

if (!isset($_GET["runnumber"]) || !isset($_GET["runsitenumber"]) || 
    !is_numeric($_GET["runnumber"]) || !is_numeric($_GET["runsitenumber"])) {
	IntrusionNotify("tried to open the admin/runedit.php with wrong parameters.");
	ForceLoad($runphp);
}

// ???
$runsitenumber = myhtmlspecialchars($_GET["runsitenumber"]);
$runnumber = myhtmlspecialchars($_GET["runnumber"]);

if (($a = DBChiefGetRunToAnswer($runnumber, $runsitenumber, 
		$_SESSION["usertable"]["contestnumber"])) === false) {
	MSGError("Another judge got it first.");
	ForceLoad($runphp);
}

$b = DBGetProblemData($_SESSION["usertable"]["contestnumber"], $a["problemnumber"]);
?>
<br><br><center><b>Use the following fields to judge the run:
</b></center>
<form name="form1" method="post" action="<?php echo $runeditphp; ?>">
  <input type=hidden name="confirmation" value="noconfirm" />
  <center>
    <table border="1">
      <tr> 
        <td width="27%" align=right><b>Site:</b></td>
        <td width="83%"> 
		<input type=hidden name="sitenumber" value="<?php echo $a["sitenumber"]; ?>" />
		<?php echo $a["sitenumber"]; ?>
        </td>
      </tr>
      <tr> 
        <td width="27%" align=right><b>Number:</b></td>
        <td width="83%"> 
		<input type=hidden name="number" value="<?php echo $a["number"]; ?>" />
		<?php echo $a["number"]; ?>
        </td>
      </tr>
      <tr> 
        <td width="27%" align=right><b>Time:</b></td>
        <td width="83%"> 
		<?php echo dateconvminutes($a["timestamp"]); ?>
        </td>
      </tr>
      <tr> 
        <td width="27%" align=right><b>Problem</b><i> <?php echo $a["problemname"]; ?></i>: </td>
        <td width="83%"> 
<?php
for ($i=0;$i<count($b);$i++) {
	echo "<a href=\"../filedownload.php?". filedownload($b[$i]["inputoid"],$b[$i]["inputfilename"]) . "\">";
	echo $b[$i]["inputfilename"] . "</a>";
//	echo " <a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('../filewindow.php?".
//	filedownload($b[$i]["inputoid"],$b[$i]["inputfilename"]) ."', 'View$i - INPUT','width=680,height=600,scrollbars=yes,resizable=yes')\">view</a> &nbsp;";
/*
	echo "<b>Sol:</b><a href=\"../filedownload.php?". filedownload($b[$i]["soloid"], $b[$i]["solfilename"]) . "\">";
	echo $b[$i]["solfilename"] . "</a>";
	echo " <a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('../filewindow.php?".
             filedownload($b[$i]["soloid"], $b[$i]["solfilename"]) ."', 'View$i - CORRECT OUTPUT','width=680,height=600,scrollbars=yes,resizable=yes')\">view</a>";
*/
}
?>
	&nbsp;</td>
      </tr>
      <tr> 
        <td width="27%" align=right><b>Language</b>:</td>
        <td width="83%">
        <i> <?php echo $a["language"]; ?></i></td>
      </tr>
      <tr> 
        <td width="27%" align=right><b>Team's code:</b></td>
        <td width="83%"> 
<?php //'
echo "<a href=\"../filedownload.php?". filedownload($a["sourceoid"],$a["sourcename"]) . "\">" . $a["sourcename"] . "</a>\n";
echo "<a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('../filewindow.php?" .
filedownload($a["sourceoid"],$a["sourcename"])  ."', 'View - SOURCE', 'width=680,height=600,scrollbars=yes,resizable=yes')\">view</a>\n";
?>
        </td>
      </tr>
      <tr>
        <td width="27%" align=right><b>Answer:</b></td>
        <td width="83%">
          <select name="answer">
<?php
$ans = DBGetAnswers($_SESSION["usertable"]["contestnumber"]);
//$isfak = true;
for ($i=0;$i<count($ans);$i++)
	if ($a["answer"] == $ans[$i]["number"]) {
//	  if($ans[$i]["fake"] != "t") $isfak = false;
       	  echo "<option selected value=\"" . $ans[$i]["number"] . "\">" . $ans[$i]["desc"] . "</option>\n";
	} else
	        echo "<option value=\"" . $ans[$i]["number"] . "\">" . $ans[$i]["desc"] . "</option>\n";
	echo "</select>";
//	if(!$isfak) {
        if($a["judgesite"] != "" && $a["judge"] != "") {
 	 $uu = DBUserInfo ($_SESSION["usertable"]["contestnumber"], $a["judgesite"], $a["judge"]);
   	 echo " [judge=" . $uu["username"] . " (" . $a["judgesite"] . ")]";
        }
?>
        </td>
      </tr>
      <tr>
        <td width="27%" align=right><b>Answer 1:</b></td>
        <td width="83%">
<?php
$ans = DBGetAnswers($_SESSION["usertable"]["contestnumber"]);
for ($i=0;$i<count($ans);$i++)
	if ($a["answer1"] == $ans[$i]["number"]) {
//	  if($ans[$i]["fake"] != "t") {
          if($a["judgesite1"] != "" && $a["judge1"] != "") {
   	   $uu = DBUserInfo ($_SESSION["usertable"]["contestnumber"], $a["judgesite1"], $a["judge1"]);
   	   echo $ans[$i]["desc"] . " [judge=" . $uu["username"] . " (" . $a["judgesite1"] . ")]";
	  } else
	   echo $ans[$i]["desc"];
        }
?>
        </td>
      </tr>
      <tr>
        <td width="27%" align=right><b>Answer 2:</b></td>
        <td width="83%">
<?php
$ans = DBGetAnswers($_SESSION["usertable"]["contestnumber"]);
for ($i=0;$i<count($ans);$i++)
	if ($a["answer2"] == $ans[$i]["number"]) {
//	  if($ans[$i]["fake"] != "t") {
          if($a["judgesite2"] != "" && $a["judge2"] != "") {
   	   $uu = DBUserInfo ($_SESSION["usertable"]["contestnumber"], $a["judgesite2"], $a["judge2"]);
   	   echo $ans[$i]["desc"] . " [judge=" . $uu["username"] . " (" . $a["judgesite2"] . ")]";
	  } else
	   echo $ans[$i]["desc"];
	}
?>
        </td>
      </tr>

<!--
      <tr> 
        <td width="27%" align=right><b>Notify user:</b></td>
        <td width="83%">
          <input class=checkbox type=checkbox name="notifyuser" value="yes"
<?php 
if (($s=DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
        ForceLoad("../index.php");

if ($a["timestamp"] < $s["sitelastmileanswer"]) echo "checked"; ?>>
(do not change this unless you know exactly what you are doing)
        </td>
      </tr>
      <tr> 
        <td width="27%" align=right><b>Update score board:</b></td>
        <td width="83%">
          <input class=checkbox type=checkbox name="updatescore" value="yes"
<?php if ($a["timestamp"] < $s["sitelastmilescore"]) echo "checked"; ?>>
(do not change this unless you know exactly what you are doing)
        </td>
      </tr>
-->
    </table>
  </center>
  <br>
  <script language="javascript">
    function conf() {
      if (confirm("Confirm?")) {
        document.form1.confirmation.value='confirm';
      }
    }
  </script>
  <center>
      <input type="submit" name="Submit" value="Judge" onClick="conf()">
     <input type="submit" name="open" value="Open run for rejudging" onClick="conf()">
      <input type="submit" name="cancel" value="Cancel editing">
      <input type="submit" name="delete" value="Delete" onClick="conf()">
      <input type="reset" name="Submit2" value="Clear">
<br><br>
  </center>

  <center>
<br>
<b>Autojudging:</b>
<input type="submit" name="giveup" value="Renew">
<br><br>
  <table border="1">
  <tr>
        <td width="27%" align=right><b>Autojudging answer:</b></td>
        <td width="83%"> 
<?php
if($a["autobegin"]!="" && $a["autoend"]=="")
      echo "in progress";
else if($a["autoend"]!="") {
      if($a["autoanswer"]!="") echo $a["autoanswer"];
      else echo "Autojudging error";
} else
      echo "unavailable";
?>
        </td>
  </tr>
  <tr>
        <td width="27%" align=right><b>Autojudged by:</b></td>
<?php if($a["autobegin"]!="" && $a["autoend"]=="")
      echo "<td width=\"83%\">". $a["autoip"] ." since ". dateconvsimple($a["autobegin"]) ."</td>";
else if($a["autoend"]!="")
      echo "<td width=\"83%\">". $a["autoip"] ." from ". dateconvsimple($a["autobegin"]) ." to ". dateconvsimple($a["autoend"]) ."</td>";
else
      echo "<td width=\"83%\">unavailable</td>";
?>
  </tr>
  <tr> 
        <td width="27%" align=right><b>Standard output:</b></td>
        <td width="83%"> 
<?php 
if($a["autostdout"]!="") {
	echo "<a href=\"../filedownload.php?".filedownload($a["autostdout"],"stdout") ."\">stdout</a>\n";
      echo "<a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('../filewindow.php?".
	filedownload($a["autostdout"],"stdout") ."', 'View - STDOUT','width=680,height=600,scrollbars=yes,".
	   "resizable=yes')\">view</a>\n";
} else
      echo "unavailable";
?>
        </td>
  </tr>
  <tr> 
        <td width="27%" align=right><b>Standard error:</b></td>
        <td width="83%"> 
<?php 
if($a["autostderr"]!="") {
	echo "<a href=\"../filedownload.php?". filedownload($a["autostderr"],"stderr") . "\">stderr</a>\n";
	echo "<a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('../filewindow.php?".
	filedownload($a["autostderr"],"stderr") ."', 'View - STDERR','width=680,height=600,scrollbars=yes,".
	"resizable=yes')\">view</a>\n";
} else
      echo "unavailable";
?>
        </td>
  </tr>
  </table></center>

</form>
</body>
</html>
