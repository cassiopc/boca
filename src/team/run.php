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
// Last modified 28/oct/2013 by cassio@ime.usp.br
require('header.php');

if (isset($_FILES["sourcefile"]) && isset($_POST["problem"]) && isset($_POST["Submit"]) && isset($_POST["language"]) &&
    is_numeric($_POST["problem"]) && is_numeric($_POST["language"]) && $_FILES["sourcefile"]["name"]!="") {
	if ($_POST["confirmation"] == "confirm") {
		if(($ct = DBContestInfo($_SESSION["usertable"]["contestnumber"])) == null)
			ForceLoad("../index.php");

		$prob = myhtmlspecialchars($_POST["problem"]);
		$lang = myhtmlspecialchars($_POST["language"]);

		$type=myhtmlspecialchars($_FILES["sourcefile"]["type"]);
		$size=myhtmlspecialchars($_FILES["sourcefile"]["size"]);
		$name=myhtmlspecialchars($_FILES["sourcefile"]["name"]);
		$temp=myhtmlspecialchars($_FILES["sourcefile"]["tmp_name"]);

		if ($size > $ct["contestmaxfilesize"]) {
	                LOGLevel("User {$_SESSION["usertable"]["username"]} tried to submit file " .
			"$name with $size bytes ({$ct["contestmaxfilesize"]} max allowed).", 1);
			MSGError("File size exceeds the limit allowed.");
			ForceLoad($runteam);
		}
		if(strpos($name,' ') === true || strpos($temp,' ') === true) {
			MSGError("File name cannot contain spaces.");
			ForceLoad($runteam);		
		}
		if (!is_uploaded_file($temp) || strlen($name)>100) {
			IntrusionNotify("file upload problem.");
			ForceLoad("../index.php");
		}


		$ac=array('contest','site','user','problem','lang','filename','filepath');
		$ac1=array('runnumber','rundate','rundatediff','rundatediffans','runanswer','runstatus','runjudge','runjudgesite',
			   'runjudge1','runjudgesite1','runanswer1','runjudge2','runjudgesite2','runanswer2',
			   'autoip','autobegindate','autoenddate','autoanswer','autostdout','autostderr','updatetime');
		$param = array('contest'=>$_SESSION["usertable"]["contestnumber"],
					   'site'=>$_SESSION["usertable"]["usersitenumber"],
					   'user'=>  $_SESSION["usertable"]["usernumber"],
					   'problem'=>$prob,
					   'lang'=>$lang,
					   'filename'=>$name,
					   'filepath'=>$temp);
		DBNewRun ($param);
		$_SESSION['forceredo']=true;
	}
	ForceLoad($runteam);
}

$ds = DIRECTORY_SEPARATOR;
if($ds=="") $ds = "/";

$runtmp = $_SESSION["locr"] . $ds . "private" . $ds . "runtmp" . $ds . "run-contest" . $_SESSION["usertable"]["contestnumber"] . 
	"-site". $_SESSION["usertable"]["usersitenumber"] . "-user" . $_SESSION["usertable"]["usernumber"] . ".php";
$redo = TRUE;
if(!isset($_SESSION['forceredo']) || $_SESSION['forceredo']==false) {
	$actualdelay = 30;
	if(file_exists($runtmp)) {
		if(isset($strtmp) || (($strtmp = file_get_contents($runtmp,FALSE,NULL,-1,1000000)) !== FALSE)) {
			list($d) = sscanf($strtmp,"%*s %d");
			if($d > time() - $actualdelay) {
				$conf=globalconf();
				$strtmp = decryptData(substr($strtmp,strpos($strtmp,"\n")+1),$conf["key"],'runtmp');
				if($strtmp !== false)
					$redo = FALSE;
			}
		}
	}
}
if($redo) {
	$_SESSION["popuptime"] = time();
	$_SESSION['forceredo']=false;
	if(($st = DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
        ForceLoad("../index.php");
	$strtmp="<br>\n<table width=\"100%\" border=1>\n <tr>\n  <td><b>Run #</b></td>\n<td><b>Time</b></td>\n".
		"  <td><b>Problem</b></td>\n  <td><b>Language</b></td>\n  <td><b>Answer</b></td>\n  <td><b>File</b></td>\n </tr>\n";
	$strcolors = "0";
	$run = DBUserRuns($_SESSION["usertable"]["contestnumber"],
					  $_SESSION["usertable"]["usersitenumber"],
					  $_SESSION["usertable"]["usernumber"]);
	for ($i=0; $i<count($run); $i++) {
		$strtmp .= " <tr>\n";
		$strtmp .= "  <td nowrap>" . $run[$i]["number"] . "</td>\n";
		$strtmp .= "  <td nowrap>" . dateconvminutes($run[$i]["timestamp"]) . "</td>\n";
		$strtmp .= "  <td nowrap>" . $run[$i]["problem"] . "</td>\n";
		$strtmp .= "  <td nowrap>" . $run[$i]["language"] . "</td>\n";
//  $strtmp .= "  <td nowrap>" . $run[$i]["status"] . "</td>\n";
		if (trim($run[$i]["answer"]) == "") { 
			$run[$i]["answer"] = "Not answered yet";
			$strtmp .= "  <td>Not answered yet"; 
		}
		else {
			$strtmp .= "  <td>" . $run[$i]["answer"]; 
			if($run[$i]['yes']=='t') {
				$strtmp .= " <img alt=\"".$run[$i]["colorname"]."\" width=\"15\" ".
					"src=\"" . balloonurl($run[$i]["color"]) ."\" />";
				$strcolors .= "\t" . $run[$i]["colorname"] . "\t" . $run[$i]["color"];
			}
		}
		$strtmp .= "</td>\n";
		$strtmp .= "<td nowrap><a href=\"../filedownload.php?" . filedownload($run[$i]["oid"],$run[$i]["filename"]) . "\">";
		$strtmp .= $run[$i]["filename"] . "</a>";
		
		$strtmp .= "</td>\n";
		
		$strtmp .= " </tr>\n";
	}
$strtmp .= "</table>";
if (count($run) == 0) $strtmp .= "<br><center><b><font color=\"#ff0000\">NO RUNS AVAILABLE</font></b></center>";

$strtmp .= "<br><br><center><b>To submit a program, just fill in the following fields:</b></center>\n".
"<form name=\"form1\" enctype=\"multipart/form-data\" method=\"post\" action=\"". $runteam ."\">\n".
"  <input type=hidden name=\"confirmation\" value=\"noconfirm\" />\n".
"  <center>\n".
"    <table border=\"0\">\n".
"      <tr> \n".
"        <td width=\"25%\" align=right>Problem:</td>\n".
"        <td width=\"75%\">\n".
"          <select name=\"problem\" onclick=\"Arquivo()\">\n";
$prob = DBGetProblems($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usertype"]=='judge');
$strtmp .= "<option selected value=\"-1\"> -- </option>\n";
for ($i=0;$i<count($prob);$i++)
	$strtmp .= "<option value=\"" . $prob[$i]["number"] . "\">" . $prob[$i]["problem"] . "</option>\n";
$strtmp .= "	  </select>\n".
"        </td>\n".
"      </tr>\n".
"      <tr> \n".
"        <td width=\"25%\" align=right>Language:</td>\n".
"        <td width=\"75%\"> \n".
"          <select name=\"language\" onclick=\"Arquivo()\">\n";
$lang = DBGetLanguages($_SESSION["usertable"]["contestnumber"]);
$strtmp .= "<option selected value=\"-1\"> -- </option>\n";
for ($i=0;$i<count($lang);$i++)
	$strtmp .= "<option value=\"" . $lang[$i]["number"] . "\">" . $lang[$i]["name"] . "</option>\n";
$strtmp .= "	  </select>\n".
"        </td>\n".
"      </tr>\n".
"      <tr> \n".
"        <td width=\"25%\" align=right>Source code:</td>\n".
"        <td width=\"75%\">\n".
"	  <input type=\"file\" name=\"sourcefile\" size=\"40\" onclick=\"Arquivo()\">\n".
"        </td>\n".
"      </tr>\n".
"    </table>\n".
"  </center>\n".
"  <script language=\"javascript\">\n".
"    function conf() {\n".
"      if (document.form1.problem.value != '-1' && document.form1.language.value != '-1') {\n".
"       if (confirm(\"Confirm submission?\")) {\n".
"        document.form1.confirmation.value='confirm';\n".
"       }\n".
"      } else {\n".
"        alert('Invalid problem and/or language');\n".
"      }\n".
"    }\n".
"  </script>\n".
"  <center>\n".
"      <input type=\"submit\" name=\"Submit\" value=\"Send\" onClick=\"conf()\">\n".
"      <input type=\"reset\" name=\"Submit2\" value=\"Clear\">\n".
"  </center>\n".
"</form>\n";
    $conf=globalconf();
    $strtmp1 = "<!-- " . time() . " --> <?php exit; ?>\t" . encryptData($strcolors,$conf["key"],false) . "\n" . encryptData($strtmp,$conf["key"],false);
	$randnum = session_id() . "_" . rand();
	if(file_put_contents($runtmp . "_" . $randnum, $strtmp1,LOCK_EX)===FALSE) {
		if(!isset($_SESSION['writewarn'])) {
			LOGError("Cannot write to the user-run cache file $runtmp -- performance might be compromised");
			$_SESSION['writewarn']=true;
		}
	}
	@rename($runtmp . "_" . $randnum, $runtmp);
}
echo $strtmp;
?>


</body>
</html>
