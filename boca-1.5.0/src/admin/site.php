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

if(($ct = DBContestInfo($_SESSION["usertable"]["contestnumber"])) == null)
	ForceLoad("../index.php");
if($ct["contestlocalsite"]==$ct["contestmainsite"]) $main=true; else $main=false;

if ($main) {
  if(isset($_GET["new"]) && $_GET["new"]=="1") {
        $n = DBNewSite($_SESSION["usertable"]["contestnumber"]);
        ForceLoad("site.php?site=$n");
  }
}
if (isset($_GET["site"]) && is_numeric($_GET["site"]))
    $site=$_GET["site"];
else if (isset($_POST["site"]) && is_numeric($_POST["site"]))
    $site=$_POST["site"];
else
    $site=$ct["contestlocalsite"];

if(($st = DBSiteInfo($_SESSION["usertable"]["contestnumber"], $site)) == null)
	ForceLoad("../index.php");
$sitetime = DBAllSiteTime($_SESSION["usertable"]["contestnumber"], $site);

if (isset($_POST["Logoff"]) && $_POST["Logoff"] == "Logoff all users") {
	if ($_POST["confirmation"] == "confirm") {
		DBSiteLogoffAll ($_SESSION["usertable"]["contestnumber"], $site);
	}
	ForceLoad("site.php?site=$site");
}
if (isset($_POST["Logins"]) && $_POST["Logins"] == "Enable logins") {
	if ($_POST["confirmation"] == "confirm") {
		DBSiteLogins ($_SESSION["usertable"]["contestnumber"], $site, "t");
	}
	ForceLoad("site.php?site=$site");
}
if (isset($_POST["Logins"]) && $_POST["Logins"] == "Disable logins") {
	if ($_POST["confirmation"] == "confirm") {
		DBSiteLogins ($_SESSION["usertable"]["contestnumber"], $site, "f");
	}
	ForceLoad("site.php?site=$site");
}
if (isset($_POST["Submit2"]) && $_POST["Submit2"] == "Start Now") {
	if ($_POST["confirmation"] == "confirm") {
		if(!DBSiteStartNow ($_SESSION["usertable"]["contestnumber"], $site))
			MSGError("Site is already running");
	}
	ForceLoad("site.php?site=$site");
}
if (isset($_POST["SubmitDC"]) && $_POST["SubmitDC"] == "Delete ALL site clars") {
	if ($_POST["confirmation"] == "confirm") {
		DBSiteDeleteAllClars ($_SESSION["usertable"]["contestnumber"], $site,
			$_SESSION["usertable"]["usernumber"], $_SESSION["usertable"]["usersitenumber"]);
	}
	ForceLoad("site.php?site=$site");
}
if (isset($_POST["SubmitDR"]) && $_POST["SubmitDR"] == "Delete ALL site runs") {
	if ($_POST["confirmation"] == "confirm") {
		DBSiteDeleteAllRuns ($_SESSION["usertable"]["contestnumber"], $site,
			$_SESSION["usertable"]["usernumber"], $_SESSION["usertable"]["usersitenumber"]);
	}
	ForceLoad("site.php?site=$site");
}
if (isset($_POST["SubmitDT"]) && $_POST["SubmitDT"] == "Delete ALL site tasks") {
	if ($_POST["confirmation"] == "confirm") {
		DBSiteDeleteAllTasks ($_SESSION["usertable"]["contestnumber"], $site,
			$_SESSION["usertable"]["usernumber"], $_SESSION["usertable"]["usersitenumber"]);
	}
	ForceLoad("site.php?site=$site");
}
if (isset($_POST["SubmitDB"]) && $_POST["SubmitDB"] == "Delete ALL site bkps") {
	if ($_POST["confirmation"] == "confirm") {
		DBSiteDeleteAllBkps ($_SESSION["usertable"]["contestnumber"], $site,
			$_SESSION["usertable"]["usernumber"], $_SESSION["usertable"]["usersitenumber"]);
	}
	ForceLoad("site.php?site=$site");
}
if (isset($_POST["Submit3"]) && $_POST["Submit3"] == "Stop Now") {
	if ($_POST["confirmation"] == "confirm") {
		if(DBSiteEndNow ($_SESSION["usertable"]["contestnumber"], $site))
			MSGError("Site has been finished");
	}
	ForceLoad("site.php?site=$site");
}
if (isset($_POST["endat"]) && $_POST["endat"] == "Stop At") {
	if ($_POST["confirmation"] == "confirm" &&
	    isset($_POST["enddateh"]) && $_POST["enddateh"] >= 0 && $_POST["enddateh"] <= 23 &&
	    isset($_POST["enddatemin"]) && $_POST["enddatemin"] >= 0 && $_POST["enddatemin"] <= 59 &&
	    isset($_POST["enddated"]) && isset($_POST["enddatem"]) && isset($_POST["enddatey"]) && 
	    checkdate($_POST["enddatem"], $_POST["enddated"], $_POST["enddatey"])) {
		$te = mktime ($_POST["enddateh"], $_POST["enddatemin"], 0, $_POST["enddatem"], $_POST["enddated"], $_POST["enddatey"]);
		if($te > time())
			MSGError("Impossible to stop at a future time. Still running");
		else {
			if(DBSiteEndNow ($_SESSION["usertable"]["contestnumber"], $site, $te))
				MSGError("Site has been finished");
		}
	}
	ForceLoad("site.php?site=$site");
}
if (isset($_POST["Submit1"]) && $_POST["Submit1"] == "Send" && isset($_POST["name"]) && isset($_POST["site"]) && is_numeric($_POST["site"]) &&
    $_POST["name"] != "" && isset($_POST["lastmileanswer"]) && isset($_POST["scorelevel"]) &&
    is_numeric($_POST["lastmileanswer"]) && isset($_POST["lastmilescore"]) && 
    is_numeric($_POST["lastmilescore"]) &&  is_numeric($_POST["scorelevel"]) &&
    isset($_POST["chiefname"]) &&
    isset($_POST["startdateh"]) && $_POST["startdateh"] >= 0 && $_POST["startdateh"] <= 23 &&
    isset($_POST["startdatemin"]) && $_POST["startdatemin"] >= 0 && $_POST["startdatemin"] <= 59 &&
    isset($_POST["startdated"]) && isset($_POST["startdatem"]) && isset($_POST["startdatey"]) && 

    checkdate($_POST["startdatem"], $_POST["startdated"], $_POST["startdatey"])) {
	if ($_POST["confirmation"] == "confirm") {
		$t = mktime ($_POST["startdateh"], $_POST["startdatemin"], 0, $_POST["startdatem"], $_POST["startdated"], $_POST["startdatey"]);

		$param['contestnumber']=$_SESSION["usertable"]["contestnumber"];
		$param['sitenumber']=$_POST["site"];
		$param['sitename']=$_POST["name"];
//		$param['siteip']=$_POST["ip"];
		$param['siteduration']=$_POST["duration"]*60;
		$param['sitelastmileanswer']=	$_POST["lastmileanswer"]*60;
		$param['sitelastmilescore']= $_POST["lastmilescore"]*60;
		$param['sitejudging']= $_POST["judging"];
		$param['sitetasking']= $_POST["tasking"];
		if(isset($_POST["autoend"]))
			$param['siteautoend']= $_POST["autoend"];
		if(isset($_POST["globalscore"]))
			$param['siteglobalscore']= $_POST["globalscore"];
		if(isset($_POST["active"]))
			$param['siteactive']=$_POST["active"];
		$param['sitescorelevel']=$_POST["scorelevel"];
		$param['sitepermitlogins']='';
		if(isset($_POST["autojudge"]))
			$param['siteautojudge']=$_POST["autojudge"];
		$param['sitechiefname']=$_POST["chiefname"];
		DBUpdateSite ($param);
		$st1 = DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_POST["site"]);
		if($t != $st1["sitestartdate"]) {
			$param = array('contest'=>$_SESSION["usertable"]["contestnumber"],
						   'site'=>$_POST["site"],
						   'start'=>$t);
			DBRenewSiteTime($param);
		}
	}
	ForceLoad("site.php?site=".$_POST["site"]);
}


if ($main && isset($_FILES["importfile"]) && isset($_POST["Submit"]) && $_POST["Submit"]=='Import' && $_FILES["importfile"]["name"]!="") {
        if ($_POST["confirmation"] == "confirm") {
                $type=myhtmlspecialchars($_FILES["importfile"]["type"]);
                $size=myhtmlspecialchars($_FILES["importfile"]["size"]);
                $name=myhtmlspecialchars($_FILES["importfile"]["name"]);
                $temp=myhtmlspecialchars($_FILES["importfile"]["tmp_name"]);
                if (!is_uploaded_file($temp)) {
                        IntrusionNotify("file upload problem.");
                        ForceLoad("../index.php");
                }

                if (($ar = file($temp)) === false) {
                        IntrusionNotify("Unable to open the uploaded file.");
                        ForceLoad("site.php");
                }
				$userlist=array();
				if(strtolower(substr($name,-4))==".tsv") {
					for ($i=0; $i<count($ar) && strpos($ar[$i], "File_Version\t1") === false; $i++) ;
					if($i >= $count($ar)) MSGError('File format not recognized');
					$oklines=0;
					for ($i++; $i<count($ar); $i++) {
                        $x = explode("\t",trim($ar[$i]));
						if(count($x)==2) {
							$param=array();
							$param['sitenumber']=trim($x[0]);
							$param['sitename']=trim($x[1]);
							$param['contest']=$_SESSION["usertable"]["contestnumber"];
							if($_SESSION["usertable"]["usersitenumber"] == $param['sitenumber'] || $main)
								if(DBNewSite($param['contest'],null,$param)) {
									$oklines++;
									$param=array();
									$param['contest']=$_SESSION["usertable"]["contestnumber"];
									$param['site']=$ct["contestmainsite"];
									$param['username']='site' . trim($x[0]);
									$param['usericpcid']=trim($x[0]);
									$param['usernumber']=trim($x[0]);
									$param['userfull']='Site connection';
									$param['userdesc']='';
									$param['type']='site';
									$param['enabled']='t';
									$param['multilogin']='t';
									$userlist[$param['username']] = randstr(10);
									$param['pass']=myhash($userlist[$param['username']]);
									DBNewUser($param);
								}
						}
					}
					MSGError($oklines . ' sites included/updated successfully');
				} else if(strtolower(substr($name,-4))==".tab") {
					$oklines=0;
					for ($i=0; $i<count($ar); $i++) {
                        $x = explode("\t",trim($ar[$i]));
						if(count($x)==8) {
							$param=array();
							$param['sitenumber']=trim($x[0]);
							$param['sitename']=trim($x[2]);
							$param['contest']=$_SESSION["usertable"]["contestnumber"];
							if($_SESSION["usertable"]["usersitenumber"] == $param['sitenumber'] || $main)
								if(DBNewSite($param['contest'],null,$param)) {
									$oklines++;
									$param=array();
									$param['contest']=$_SESSION["usertable"]["contestnumber"];
									$param['site']=$ct["contestmainsite"];
									$param['username']='site' . trim($x[0]);
									$param['usericpcid']=trim($x[0]);
									$param['usernumber']=trim($x[0]);
									$param['userfull']='Site connection';
									$param['userdesc']='';
									$param['type']='site';
									$param['enabled']='t';
									$param['multilogin']='t';
									$userlist[$param['username']] = randstr(10);
									$param['pass']=myhash($userlist[$param['username']]);
									DBNewUser($param);
								}
						}
					}
					MSGError($oklines . ' sites included/updated successfully');
					if(count($userlist) > 0) {
?>
<center>
<br><u><b>TAKE NOTE OF THE USERS AND PASSWORDS AND KEEP THEM SECRET</b></u><br><br>
<table border=1>
 <tr>
  <td><b>Username</b></td>
  <td><b>Password</b></td>
 </tr>
<?php
							foreach($userlist as $user => $pass) {
							echo "<tr><td>$user</td><td>$pass</td></tr>\n";
						}
?>
</table><br><br><u><b>TAKE NOTE OF THE USERS AND PASSWORDS AND KEEP THEM SECRET</b></u></center></body></html>
<?php
	  exit;
					}
				} else {
					MSGError('File format not recognized');
				}
        }
        ForceLoad("site.php");
}

?>
<br>
<form name="form1" enctype="multipart/form-data" method="post" action="site.php">
  <input type=hidden name="confirmation" value="noconfirm" />
  <script language="javascript" type="text/javascript">
    function conf() {
      if (confirm("Confirm?")) {
        document.form1.confirmation.value='confirm';
      }
    }
    function conf2() {
      if (confirm("Confirm DELETING EVERYTHING?")) {
		  if (confirm("This operation has no come back. DATA WILL BE LOST. Confirm?")) {
			  document.form1.confirmation.value='confirm';
		  }
      }
    }
    function newsite() {
      document.location='site.php?new=1';
    }
    function sitech(n) {
      if(n==null) {
        k=document.form1.site[document.form1.site.selectedIndex].value;
	if(k=='new') newsite();
        else document.location='site.php?site='+k;
      } else {
        document.location='site.php?site='+n;
      }
    }
  </script>
  <center>
    <table border="0">
      <tr>
        <td width="35%" align=right>Site number:</td>
        <td width="65%">
<?php
echo "<select onChange=\"sitech()\" name=\"site\">\n";
$cs = DBAllSiteInfo($_SESSION["usertable"]["contestnumber"]);
for ($i=0; $i<count($cs); $i++) {
    echo "<option value=\"" . $cs[$i]["sitenumber"] . "\" ";
    if ($site == $cs[$i]["sitenumber"])
		echo "selected";
    echo ">" . $cs[$i]["sitenumber"] . "</option>\n";
}
if($main) {
	echo "<option value=\"new\">new</option>\n";
}  
echo "</select>\n";

//else
//  echo "<input type=\"text\" readonly name=\"site\" value=\"$site\" size=\"2\" maxlength=\"2\" />\n";

if($ct["contestlocalsite"]==$site) echo "(local site)"; 
?>
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Name:</td>
        <td width="65%">
          <input type="text" name="name" value="<?php echo $st["sitename"]; ?>" size="50" maxlength="50" />
        </td>
      </tr>
<?php 
	if(0) {
	if($main) {
?>
      <tr>
<td width="35%" align=right>IP (plus boca path):</td>
        <td width="65%">
          <input type="text" name="ip" value="<?php echo $st["siteip"]; ?>" size="50" maxlength="200" />
        </td>
      </tr>
<?php
	} else {
?>
      <tr>
<td width="35%" align=right>IP (plus boca path):</td>
        <td width="65%">
          <?php echo $st["siteip"]; ?>
        </td>
      </tr>
<?php
	}
	}
?>
      <tr>
<?php
echo "        <td nowrap width=\"35%\" align=right>Start date (contest=" . dateconv($ct["conteststartdate"]) . "):</td>";
?>
        <td width="65%"> hh:mm
          <input type="text" name="startdateh" value="<?php echo date("H", $st["sitestartdate"]); ?>" size="2" maxlength="2" />
          :
          <input type="text" name="startdatemin" value="<?php echo date("i", $st["sitestartdate"]); ?>" size="2" maxlength="2" />
            dd/mm/yyyy
          <input type="text" name="startdated" value="<?php echo date("d", $st["sitestartdate"]); ?>" size="2" maxlength="2" />
          /
          <input type="text" name="startdatem" value="<?php echo date("m", $st["sitestartdate"]); ?>" size="2" maxlength="2" />
          /
          <input type="text" name="startdatey" value="<?php echo date("Y", $st["sitestartdate"]); ?>" size="4" maxlength="4" />
        </td>
      </tr>
      <tr>
<?php
if (!$st["siterunning"]) {
	echo "      <tr>\n";
	echo "        <td nowrap width=\"35%\" align=right><b>Site finished at:</b></td>\n";
	echo "        <td width=\"65%\"><b>" . dateconv($st["siteendeddate"]) . "</b></td>\n";
	echo "      </tr>\n";
	if($st["siteautoended"])
		$w = (int) ($st["siteduration"]/60);
	else
		$w = (int) ($st["currenttime"]/60);
	echo "      <tr>\n";
	echo "        <td nowrap width=\"35%\" align=right><b>Real duration:</b></td>\n";
	echo "        <td width=\"65%\"><b>" . $w . " minutes</b></td>\n";
	echo "      </tr>\n";
}
?>
      <tr>
<?php
echo "        <td width=\"35%\" align=right>Duration (contest=";
echo $ct["contestduration"]/60;
echo "):</td>";
?>
        <td width="65%">
          <input type="text" name="duration" value="<?php echo $st["siteduration"]/60; ?>" size="20" maxlength="20" />
        </td>
      </tr>
      <tr>
<?php
echo "        <td width=\"35%\" align=right>Stop answering (contest=";
echo $ct["contestlastmileanswer"]/60;
echo "):</td>";
?>
        <td width="65%">
          <input type="text" name="lastmileanswer" value="<?php echo $st["sitelastmileanswer"]/60; ?>" size="20" maxlength="20" />
        </td>
      </tr>
      <tr>
<?php
echo "        <td width=\"35%\" align=right>Stop scoreboard (contest=";
echo $ct["contestlastmilescore"]/60;
echo "):</td>";
?>
        <td width="65%">
          <input type="text" name="lastmilescore" value="<?php echo $st["sitelastmilescore"]/60; ?>" size="20" maxlength="20" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Runs/clars that will be judged here (comma sep):</td>
        <td width="65%">
          <input type="text" name="judging" value="<?php echo $st["sitejudging"]; ?>" size="20" maxlength="200" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Tasks that will be treated here (comma sep):</td>
        <td width="65%">
          <input type="text" name="tasking" value="<?php echo $st["sitetasking"]; ?>" size="20" maxlength="200" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Username of the chief judge (if any):</td>
        <td width="65%">
          <input type="text" name="chiefname" value="<?php echo $st["sitechiefname"]; ?>" size="20" maxlength="20" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Active:</td>
        <td width="65%">
<?php
          if ($st["siteactive"] == "t")
            echo "<input class=checkbox type=\"checkbox\" name=\"active\" checked value=\"t\" />";
          else
            echo "<input class=checkbox type=\"checkbox\" name=\"active\" value=\"t\" />";
?>
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Autoend:</td>
        <td width="65%">
<?php
          if ($st["siteautoend"] == "t")
            echo "<input class=checkbox type=\"checkbox\" name=\"autoend\" checked value=\"t\" />";
          else
            echo "<input class=checkbox type=\"checkbox\" name=\"autoend\" value=\"t\" />";
?>
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Global score:</td>
        <td width="65%">
          <input type="text" name="globalscore" value="<?php echo $st["siteglobalscore"]; ?>" size="20" maxlength="50" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Autojudge (without human interaction):</td>
        <td width="65%">
<?php
          if ($st["siteautojudge"] == "t")
            echo "<input class=checkbox type=\"checkbox\" name=\"autojudge\" checked value=\"t\" />";
          else
            echo "<input class=checkbox type=\"checkbox\" name=\"autojudge\" value=\"t\" />";
	  echo " &lt;- experimental";
?>
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Logins are:</td>
        <td width="65%">
<?php
          if ($st["sitepermitlogins"] == "t")
            echo "enabled";
          else
            echo "disabled";
?>
        </td>
      </tr>
      <tr>
        <td width=\"35%\" align=right>Score level:</td>
        <td width="65%">
          <input type="text" name="scorelevel" value="<?php echo $st["sitescorelevel"]; ?>" size="2" maxlength="2" />
        </td>
      </tr>
      <tr>
        <td width=\"35%\" align=right>Number of Clars:</td>
        <td width="65%"><?php echo $st["sitenextclar"]; ?>
        </td>
      </tr>
      <tr>
        <td width=\"35%\" align=right>Number of Runs:</td>
        <td width="65%"><?php echo $st["sitenextrun"]; ?>
        </td>
      </tr>
      <tr>
        <td width=\"35%\" align=right>Number of Tasks:</td>
        <td width="65%"><?php echo $st["sitenexttask"]; ?>
        </td>
      </tr>
    </table>
  </center>
  <center>
<?php
		if($main || $site == $ct["contestlocalsite"]) {
?>
      <input type="submit" name="Submit1" value="Send" onClick="conf()">
      <input type="submit" name="Submit2" value="Start Now" onClick="conf()">
      <input type="submit" name="Submit3" value="Stop Now" onClick="conf()">
      <input type="reset" name="Submit4" value="Restore fields">
<br>
      <input type="submit" name="Logoff" value="Logoff all users" onClick="conf()">
      <input type="submit" name="Logins" value="Disable logins" onClick="conf()">
      <input type="submit" name="Logins" value="Enable logins" onClick="conf()">
<br><br><br>

      <input type="submit" name="SubmitDC" value="Delete ALL site clars" onClick="conf2()">
      <input type="submit" name="SubmitDR" value="Delete ALL site runs" onClick="conf2()">
      <input type="submit" name="SubmitDT" value="Delete ALL site tasks" onClick="conf2()">
      <input type="submit" name="SubmitDB" value="Delete ALL site bkps" onClick="conf2()">
<?php
				}
?>
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

<?php
		if($main || $site == $ct["contestlocalsite"]) {
?>
  <center>
    <table border="0">
      <tr>
<td nowrap width="50%" align=right>
(Do not use this button unless really necessary)
  <input type="submit" name="endat" value="Stop At" onClick="conf()">:
</td>
<?php
$w = $st["siteendeddate"];
?>
        <td width="50%"> hh:mm
          <input type="text" name="enddateh" value="<?php echo date("H", $w); ?>" size="2" maxlength="2" />
          :
          <input type="text" name="enddatemin" value="<?php echo date("i", $w); ?>" size="2" maxlength="2" />
            dd/mm/yyyy
          <input type="text" name="enddated" value="<?php echo date("d", $w); ?>" size="2" maxlength="2" />
          /
          <input type="text" name="enddatem" value="<?php echo date("m", $w); ?>" size="2" maxlength="2" />
          /
          <input type="text" name="enddatey" value="<?php echo date("Y", $w); ?>" size="4" maxlength="4" />
        </td>
      </tr>
  </table>
  </center>
<?php
			 }
if($main) {
?>
<br><br>
  <center>
    <table border="0">
      <tr>
        <td width="25%" align=right>Import file:</td>
        <td width="75%">
          <input type="file" name="importfile" size="40">
        </td>
      </tr>
    </table>
      <input type="submit" name="Submit" value="Import" onClick="conf()">
  </center>
		<?php } ?>
</form>


</body>
</html>
