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
//Last updated 18/oct/2017

require('header.php');
if(!isset($_GET['webcastcode']) || !ctype_alnum($_GET['webcastcode'])) exit;
$webcastcode=$_GET['webcastcode'];

$ds = DIRECTORY_SEPARATOR;
if($ds=="") $ds = "/";

if(isset($_SESSION['locr'])) {
	$webcastdir = $_SESSION['locr'] . $ds . 'private' .$ds. 's4ris.' . $webcastcode;
	$webcastparentdir = $_SESSION['locr'] . $ds. 'private';
} else {
	$webcastdir = $locr . $ds . 'private' . $ds . 's4ris.' . $webcastcode;
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
    @file_put_contents($webcastparentdir . $ds . 's4ris.log', $webcastcode . "|Y|" . getIP() . "|" . date(DATE_RFC2822) . "\n", LOCK_EX | FILE_APPEND);
    break;
  }
}
if($i>=count($wcdata)) {
  @file_put_contents($webcastparentdir . $ds . 's4ris.log', $webcastcode . "|N|" . getIP() . "|" . date(DATE_RFC2822) . "\n", LOCK_EX | FILE_APPEND);
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

$obj = array(
   'contestName' => $ct['contestname'],
   'freezeTimeMinutesFromStart' => $ct['contestlastmilescore']/60
);

$c = DBConnect();

$r = DBExec($c,
   'SELECT problemname FROM problemtable' .
   ' WHERE contestnumber = ' . $contest .
   ' AND problemnumber > 0');

$problems = array();
$numProblems = DBnlines($r);
for ($i = 0; $i < $numProblems; $i++) {
   $a = DBRow($r, $i);
   $problems[$i] = $a['problemname'];
}

$obj['problemLetters'] = $problems;

$sql = 'SELECT * FROM usertable' .
  ' WHERE contestnumber = ' . $contest .
  ' AND userenabled = \'t\' AND usertype = \'team\' AND ((0 = 1)';  
//  ' AND userenabled = \'t\' AND not (usericpcid = \'\') AND not (usericpcid = \'000000\') AND not (usericpcid = \'0\') AND usertype = \'team\' AND ((0 = 1)';
for($i=0; $i < count($wcloweruser); $i++)
  $sql .= ' OR (usersitenumber = ' . $wcsite[$i] . ' AND usernumber >= ' . $wcloweruser[$i] . ' AND usernumber <= ' . $wcupperuser[$i] . ')';
$sql .= ')';
$r = DBExec($c,$sql);

$teamIDs = array();
$contestans = array();
$numTeams = DBnlines($r);
for ($i = 0; $i < $numTeams; $i++) {
   $a = cleanuserdesc(DBRow($r, $i));
   $teamID = $a['username'];
   $teamIDs[count($teamIDs)] = $teamID;

   if (isset($a['usershortname']))
      $teamName = $a['usershortname'];
   else
      $teamName = $a['userfullname'];

   if (isset($a['usershortinstitution'])) {
      $teamName .= ' @ ' . $a['usershortinstitution'];
      if (isset($a['userflag'])) {
         $teamName .= '.' . $a['userflag'];
      }
   }

   $contestants[$i] = $teamName;
}

$obj['contestants'] = $contestants;

$run = DBAllRunsInSites($contest, $site, 'report');
$numRuns = count($run);

$runs = array();
for ($i = 0; $i < $numRuns; $i++) {
   if($run[$i]['status'] == 'deleted') continue;
   $runTime = dateconvminutes($run[$i]['timestamp']);
   if ($runTime > $freezeTime) {
      continue;
   }

   $u = DBUserInfo($contest, $site, $run[$i]['user']);
   $runTeam = $u['username'];
   if(in_array($runTeam, $teamIDs)) {
     if(isset($u['usershortname']))
       $runTeam = $u['usershortname'];
     else
       $runTeam = $u['userfullname'];

     if(isset($u['usershortinstitution'])) {
       $runTeam .= ' @ ' . $u['usershortinstitution'];
       if (isset($u['userflag'])) {
         $runTeam .= '.' . $u['userflag'];
       }
     }

     $runProblem = $run[$i]['problem'];

     $runs[$i] = array(
		       'contestant' => $runTeam,
		       'problemLetter' => $runProblem,
		       'timeMinutesFromStart' => $runTime,
		       'success' => $run[$i]['yes'] == 't'
		       );
   }
}

$obj['runs'] = $runs;



if(is_writable($webcastdir)) {
   file_put_contents($webcastdir . $ds . 'results.json', json_encode($obj, JSON_PRETTY_PRINT));

   if(@create_zip($webcastdir,array('.'),$webcastdir . ".zip") != 1) {
     LOGError("Cannot create file s4ris.zip file");
     MSGError("Cannot create file s4ris.zip file");
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
