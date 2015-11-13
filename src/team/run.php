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
// Last modified 09/sep/2015 by cassio@ime.usp.br
require('header.php');
$ds = DIRECTORY_SEPARATOR;
if($ds=="") $ds = "/";

if (isset($_POST["problem"]) && isset($_POST["language"]) &&
	((isset($_FILES["sourcefile"]) && isset($_POST["Submit"]) && $_FILES["sourcefile"]["name"]!="") || (isset($_POST["data"]) && isset($_POST["name"])))) {
	if ($_POST["confirmation"] == "confirm" || (isset($_POST["data"]) && isset($_POST["name"]))) {
		if(($ct = DBContestInfo($_SESSION["usertable"]["contestnumber"])) == null) {
			if(isset($_POST['name']) && $_POST['name'] != '') {
				echo "\nRESULT: CONTEST NOT FOUND";
				exit;
			}
			ForceLoad("../index.php");
		}
		$prob = myhtmlspecialchars($_POST["problem"]);
		$lang = myhtmlspecialchars($_POST["language"]);

		if(!is_numeric($prob)) {
			$probs = DBGetProblems($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usertype"]=='judge');
			$i = 0;
			$ss = "";
			for (;$i<count($probs);$i++) {
				if($probs[$i]["problem"]==$prob) {
					$prob = $probs[$i]["number"];
					break;
				}
				$ss .= $probs[$i]["problem"] . " ";
			}
			if($i >= count($probs)) {
				echo "\nRESULT: INVALID PROBLEM (options are: " . $ss . ")";
				exit;
			}
		}
		if(!is_numeric($lang)) {
			$langs = DBGetLanguages($_SESSION["usertable"]["contestnumber"]);
			$i = 0;
			$ss = "";
			for (;$i<count($langs);$i++) {
				if($langs[$i]["name"]==$lang) {
					$lang = $langs[$i]["number"];
					break;
				}
				$ss .= $langs[$i]["name"] . " ";
			}
			if($i >= count($langs)) {
				echo "\nRESULT: INVALID LANGUAGE (options are: " . $ss . ")";
				exit;
			}
		}
		if(isset($_POST['name']) && $_POST['name'] != '') {
			$temp = tempnam("/tmp","bkp-");
			$fout = fopen($temp,"wb");
			fwrite($fout,base64_decode($_POST['data']));
			fclose($fout);
			$size=filesize($temp);
			$name=$_POST['name'];
			if ($size > $ct["contestmaxfilesize"] || strlen($name)>100 || strlen($name)<1) {
				echo "\nRESULT: SUBMITTED FILE (OR NAME) TOO LARGE";
				exit;
			}
		} else {			
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
			if (!is_uploaded_file($temp) || strlen($name)>100) {
				IntrusionNotify("file upload problem.");
				ForceLoad("../index.php");
			}
		}
		if(strpos($name,' ') === true || strpos($temp,' ') === true || strpos($name,'/') === true || strpos($temp,'/') === true ||
		   strpos($name,'`') === true || strpos($temp,'`') === true || strpos($name,'\'') === true || strpos($temp,'\'') === true ||
		   strpos($name, "\"") === true || strpos($temp, "\"") === true || strpos($name,'$') === true || strpos($temp,'$') === true) {
			if(isset($_POST['name']) && $_POST['name'] != '') {
				echo "\nRESULT: FILE NAME PROBLEM (EG CANNOT HAVE SPACES)";
				exit;
			}
			MSGError("File name cannot contain spaces.");
			ForceLoad($runteam);		
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

		if(isset($_POST['pastcode']) && $_POST['pastcode'] != '') {
			$pastcode = myhtmlspecialchars($_POST["pastcode"]);
			if(isset($_POST["pasthash"]) && isset($_POST["pastval"])) {
				$pasthash = myhtmlspecialchars($_POST["pasthash"]);
				$pastvalhash = myhtmlspecialchars($_POST["pastvalhash"]);
				$pastval = myhtmlspecialchars($_POST["pastval"]);
				$pastabs = myhtmlspecialchars($_POST["pastabs"]);
				if(is_readable($_SESSION["locr"] . $ds . "private" . $ds . 'run-past.config')) {
					$pastsubmission = myhash(trim(@file_get_contents($_SESSION["locr"] . $ds . "private" . $ds . 'run-past.config')) . $pastcode . $pastval);
					if($pastsubmission != $pastvalhash) {
						$pastsubmission = myhash(trim(@file_get_contents($_SESSION["locr"] . $ds . "private" . $ds . 'run-past.config')) . $pastcode . $pastabs);
						if($pastsubmission != $pasthash) {
							echo "\nRESULT: INVALID SUBMISSION CODE";
							exit;
						}
					}
				} else $pastval = 0;
			} else {
				$pastval = 0;
			}
			$verify = $pastcode . '-' .$name . '-'. $_SESSION["usertable"]["contestnumber"].'-'.$_SESSION["usertable"]["usersitenumber"].'-'.$_SESSION["usertable"]["usernumber"];
			$fcname = $_SESSION["locr"] . $ds . "private" . $ds . 'laterun-submitted-' . $_SESSION["usertable"]["contestnumber"].'-'.
				$_SESSION["usertable"]["usersitenumber"].'-'.$_SESSION["usertable"]["usernumber"].'.txt';
			$codes = @file($fcname,FILE_IGNORE_NEW_LINES);
			if(in_array($verify,$codes)) {
				echo "\nRESULT: RUN ALREADY SUBMITTED";
			} else {
				if($pastval > 0) {
					$param['rundate']=time() - $pastval;
					$b = DBSiteInfo($_SESSION["usertable"]["contestnumber"], $_SESSION["usertable"]["usersitenumber"]);
					$dif = $b["currenttime"]; 
					$param['rundatediff']=$dif - $pastval;
				}

				///////CASO DE COMECAR MAIS TARDE NO CENTRALIZADO
				if(false && substr($_SESSION["usertable"]["username"],0,3) == 'XXX') {
					$param['rundate']=$param['rundate'] - 60*10; // 10 minutos
					$param['rundatediff']=$param['rundatediff'] - 60*10;
				}
				$retv = DBNewRun ($param);
				if($retv == 2) {
					@file_put_contents($fcname, $verify . "\n", FILE_APPEND | LOCK_EX);
					echo "\nRESULT: RUN SUBMITTED SUCCESSFULLY ($pastval)";
				} else {
					if($retv == 0) echo "\nRESULT: CONTEST NOT RUNNING";
					else
						echo "\nRESULT: UNKNOWN PROBLEM";
				}
			}
			exit;
		}
		///////CASO DE COMECAR MAIS TARDE NO CENTRALIZADO
		if(false && substr($_SESSION["usertable"]["username"],0,3) == 'XXX') {
			$param['rundate']=$param['rundate'] - 60*10; // 10 minutos
			$param['rundatediff']=$param['rundatediff'] - 60*10;
		}
		$retv = DBNewRun ($param);
		if(isset($_POST['name']) && $_POST['name'] != '') {
			if($retv == 2)
				echo "\nRESULT: RUN SUBMITTED SUCCESSFULLY";
			else {
				if($retv == 0) echo "\nRESULT: CONTEST NOT RUNNING";
				else
					echo "\nRESULT: UNKNOWN PROBLEM";
			}
			exit;
		}
		$_SESSION['forceredo']=true;
	}
	ForceLoad($runteam);
}
if(isset($_POST['name']) && $_POST['name'] != '') {
	echo "RESULT: PARAMETERS MISSING";
	exit;
}

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
			if(false) {
				if(strpos($run[$i]["autoanswer"],"OKs") > 0)
					$strtmp .= ' ' . substr($run[$i]["autoanswer"],strrpos($run[$i]["autoanswer"],'('));
			}
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

$linesubmission = @file_get_contents($_SESSION["locr"] . $ds . "private" . $ds . 'run-using-command.config');
if(trim($linesubmission) == '1') {
$strtmp .= "<br><br><center><b>To submit a program, use the command-line tool:</b>\n<br>".
	"<pre>boca-submit-run USER PASSWORD PROBLEM LANGUAGE FILE</pre><br>".
    "where USER is your username, PASSWORD is your password, <br>".
	"PROBLEM is one of { ";

$prob = DBGetProblems($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usertype"]=='judge');
for ($i=0;$i<count($prob);$i++)
	$strtmp .= $prob[$i]["problem"] . " ";
$strtmp .= "} and<br>LANGUAGE is one of { ";
$lang = DBGetLanguages($_SESSION["usertable"]["contestnumber"]);
for ($i=0;$i<count($lang);$i++)
	$strtmp .= $lang[$i]["name"] . " ";
$strtmp .= "}<br>FILE is your submission file<br><br>\n";
} else {

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
}
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
