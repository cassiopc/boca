<?php
////////////////////////////////////////////////////////////////////////////////
//BOCA Online Contest Administrator
//    Copyright (C) 2003-2012 by BOCA System (bocasystem@gmail.com)
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

require('header.php');
if(!isset($_GET['webcastcode']) || !ctype_alnum($_GET['webcastcode'])) exit;
$webcastcode=$_GET['webcastcode'];

$ds = DIRECTORY_SEPARATOR;
if($ds=="") $ds = "/";

if(isset($_SESSION['locr'])) {
	$webcastdir = $_SESSION['locr'] . $ds . 'private' .$ds. 'webcast.' . $webcastcode;
	$webcastparentdir = $_SESSION['locr'] . $ds. 'private';
} else {
	$webcastdir = $locr . $ds . 'private' . $ds . 'webcast.' . $webcastcode;
	$webcastparentdir = $locr . $ds . 'private';
}

$wcdata=@file($webcastparentdir . $ds . 'webcast.sep');
$wcsite = array();
$wcloweruser = array();
$wcupperuser = array();
for($i=0; $i<count($wcdata);$i++) {
  $wccode = explode(' ', $wcdata[$i]);
  if($wccode[0] == $webcastcode && strpos('#',$wccode[0])===false) {
    for($j=1; $j < count($wccode); $j++) {
      $temp = explode('/', trim($wccode[$j]));
      if(is_numeric($temp[0])) {
	$wcsite[count($wcsite)] = $temp[0];
	$wcloweruser[count($wcloweruser)] = 0;
	$wcupperuser[count($wcupperuser)] = -1;      
	if(count($temp) > 1 && is_numeric($temp[1]))
	  $wcloweruser[count($wcloweruser)-1] = $temp[1];
	if(count($temp) > 2 && is_numeric($temp[2]))
	  $wcupperuser[count($wcupperuser)-1] = $temp[2];
      }
    }
    @file_put_contents($webcastparentdir . $ds . 'webcast.log', $webcastcode . "|Y|" . getIP() . "|" . date(DATE_RFC2822) . "\n", LOCK_EX | FILE_APPEND);
    break;
  }
}
if($i>=count($wcdata)) {
  @file_put_contents($webcastparentdir . $ds . 'webcast.log', $webcastcode . "|N|" . getIP() . "|" . date(DATE_RFC2822) . "\n", LOCK_EX | FILE_APPEND);
  exit;
}

//cleardir($webcastdir);
@mkdir($webcastdir);

$contest = 1; //$_SESSION["usertable"]["contestnumber"];
$site = 1; //$_SESSION["usertable"]["usersitenumber"];

$ct = DBContestInfo($contest);
if(($st =  DBSiteInfo($contest, $site)) == null)
	ForceLoad("../index.php");

if(isset($_GET['full']) && $_GET['full'] > 0)
  $freezeTime = $st['siteduration'];
else
  $freezeTime = $st['sitelastmilescore'];


$contestfile = $ct['contestname'] . "\n";

$contestfile = $contestfile .
	$ct['contestduration']/60 . '' .
	$ct['contestlastmileanswer']/60 . '' .
	$ct['contestlastmilescore']/60 . '' .
	$ct['contestpenalty']/60 . "\n";

$c = DBConnect();
$r = DBExec($c,
	'SELECT problemnumber FROM problemtable' .
	' WHERE contestnumber = ' . $contest .
	    ' AND problemnumber > 0 AND not (problemfullname ~ \'(DEL)\')');
$numProblems = DBnlines($r);

$sql = 'SELECT username, userfullname, userdesc FROM usertable' .
  ' WHERE contestnumber = ' . $contest .
  ' AND userenabled = \'t\' AND usertype = \'team\' AND ((0 = 1)';  
//  ' AND userenabled = \'t\' AND not (usericpcid = \'\') AND not (usericpcid = \'000000\') AND not (usericpcid = \'0\') AND usertype = \'team\' AND ((0 = 1)';
for($i=0; $i < count($wcloweruser); $i++)
  $sql .= ' OR (usersitenumber = ' . $wcsite[$i] . ' AND usernumber >= ' . $wcloweruser[$i] . ' AND usernumber <= ' . $wcupperuser[$i] . ')';
$sql .= ')';
$r = DBExec($c,$sql);

$numTeams = DBnlines($r);

$contestfile = $contestfile .
	$numTeams . '' .
	$numProblems . "\n";
$teamIDs = array();
for ($i = 0; $i < $numTeams; $i++) {
	$a = DBRow($r, $i);
	$teamID = $a['username'];
	$teamIDs[count($teamIDs)] = $teamID;
	$pieces = explode('</b>', $a['userfullname']);
	$teamName = $a['userfullname'];
	$pieces = explode(']', $a['userdesc']);
	$pieces = explode('[', trim($pieces[0]));
	$teamUni = trim($pieces[1]);
	//print_r( array_keys($a));

	$contestfile = $contestfile .
		$teamID . '' .
		$teamUni . '' .
		$teamName . "\n";
}

/*
for ($i = 0; $i < $numTeams; $i++) {
	$a = cleanuserdesc(DBRow($r, $i));
	$teamID = $a['username'];
	if(isset($a['usershortname']))
		$teamName = $a['usershortname'];
	else
		$teamName = $a['userfullname'];
	if(isset($a['usershortinstitution']))
		$teamUni = $a['usershortinstitution'];
	else
		$teamUni = $teamName;

	$contestfile = $contestfile .
		$teamID . '' .
		$teamUni . '' .
		$teamName . "\n";
}
*/
$contestfile = $contestfile .
	'1' . '' . '1' . "\n";
$contestfile = $contestfile .
	$numProblems . '' . 'Y' . "\n";

$run = DBAllRunsInSites($contest, $site, 'run');
$numRuns = count($run);
$runfile = '';
for ($i = 0; $i < $numRuns; $i++) {
  if($run[$i]['status'] == 'deleted') continue;
	$u = DBUserInfo($contest, $site, $run[$i]['user']);
	$runID = $run[$i]['number'];
	$runTime = dateconvminutes($run[$i]['timestamp']);
	$runTeam = $u['username'];
	if(in_array($runTeam, $teamIDs)) {
	  $runProblem = $run[$i]['problem'];

if($runTime < $freezeTime) {
	  $runfile = $runfile .
	    $runID . '' .
	    $runTime . '' .
	    $runTeam . '' .
	    $runProblem . '';

	  if ($runTime >= $freezeTime) {
	    $runfile = $runfile . '?' . "\n";
	  } else if ($run[$i]['yes'] == 't') {
	    $runfile = $runfile . 'Y' . "\n";
	  } else if ($run[$i]['answer'] == 'Not answered yet') {
	    $runfile = $runfile . '?' . "\n";
	  } else {
	    $runfile = $runfile . 'N' . "\n";
	  }
	}
}
}

if($st['currenttime'] >= $freezeTime)
$timefile = $freezeTime;
else
$timefile = $st['currenttime'];


$versionfile = '1.0' . "\n";

$_SESSION["usertable"]=array();
$_SESSION["usertable"]["contestnumber"]=1;
$_SESSION["usertable"]["usersitenumber"]=1;


$score = DBScore($contest, false, -1, $site);

$icpcfile='';
$class=1;
$nid=1;
while(list($e, $c) = each($score)) {
	if(isset($score[$e]["site"]) && isset($score[$e]["user"])) {
		$r = DBUserInfo($contest, 
				$score[$e]["site"], $score[$e]["user"]);
		if(in_array($r['username'], $teamIDs)) {
		if(is_numeric($r["usericpcid"]) && $r["usericpcid"] > 0) {
		  $icpcfile .= $r["usericpcid"] . ",";
//} else {
//$icpcfile .= $nid++ . ',';
//}
		  $icpcfile .= $class++ . ",";
		  $icpcfile .= $score[$e]["totalcount"] . ",";
		  $icpcfile .= $score[$e]["totaltime"] . ",";
		
		  if($score[$e]["first"])
		    $icpcfile .= $score[$e]["first"] . "\n";
		  else $icpcfile .= "0\n";
		}
		}
	}
}

if(is_writable($webcastdir)) {
	@file_put_contents($webcastdir . $ds . 'runs',$runfile);
	@file_put_contents($webcastdir . $ds . 'contest',$contestfile);
	@file_put_contents($webcastdir . $ds . 'version',$versionfile);
	@file_put_contents($webcastdir . $ds . 'time',$timefile);
	@file_put_contents($webcastdir . $ds . 'icpc',$icpcfile);
	if(@create_zip($webcastdir,array('.'),$webcastdir . ".zip") != 1) {
		LOGError("Cannot create score webcast.zip file");
		MSGError("Cannot create score webcast.zip file");
	} else {
	  echo file_get_contents($webcastdir . ".zip");
	  exit;
	}
} else {
	LOGError('Error creating the folder for the ZIP file: '. $webcastdir);
	MSGError('Error creating the folder for the ZIP file: '.$webcastdir);
	ForceLoad("../index.php");
}
?>
