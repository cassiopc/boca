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

$contest = $_SESSION["usertable"]["contestnumber"];
$site = $_SESSION["usertable"]["usersitenumber"];

$ct = DBContestInfo($contest);
if(($st =  DBSiteInfo($contest, $site)) == null)
   ForceLoad("../index.php");

//if(isset($_GET['full']) && $_GET['full'] > 0)
   $freezeTime = $st['siteduration'];
//else
// $freezeTime = $st['sitelastmilescore'];


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


$r = DBExec($c,
   'SELECT * FROM usertable' .
   ' WHERE contestnumber = ' . $contest .
   ' AND userenabled = \'t\' AND usersitenumber = ' . $site .
   ' AND usertype = \'team\'');

$contestans = array();
$numTeams = DBnlines($r);
for ($i = 0; $i < $numTeams; $i++) {
   $a = cleanuserdesc(DBRow($r, $i));
   //$teamID = $a['username'];

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
   $runTime = dateconvminutes($run[$i]['timestamp']);
   if ($runTime > $freezeTime) {
      continue;
   }

   $u = DBUserInfo($contest, $site, $run[$i]['user']);

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

$obj['runs'] = $runs;



$ds = DIRECTORY_SEPARATOR;
if($ds=="") $ds = "/";

if(isset($_SESSION['locr'])) {
   $s4risparentdir = $_SESSION['locr'] . $ds. 'private';
   $s4risdir = $s4risparentdir .$ds. 's4ris';
} else {
   $s4risparentdir = $locr . $ds . 'private';
   $s4risdir = $s4risparentdir . $ds . 's4ris';
}
cleardir($s4risdir);
@mkdir($s4risdir);
if(is_writable($s4risdir)) {
   file_put_contents($s4risdir . $ds . 'results.json', json_encode($obj, JSON_PRETTY_PRINT));
   if(@create_zip($s4risparentdir,array('s4ris'), $s4risdir . ".tmp") != 1) {
      LOGError("Cannot create score s4risdir.tmp file");
      MSGError("Cannot create score s4risdir.tmp file");
   } else {
      $cf = globalconf();
      file_put_contents($s4risdir . ".tmp", encryptData(file_get_contents($s4risdir . ".tmp"), $cf["key"],false));
      @rename($s4risdir . ".tmp",$s4risdir . '.zip');
   }
   echo "<br><br><br><center>";
   echo "<a href=\"$locr/filedownload.php?". 
      filedownload(-1,$s4risdir . '.zip') . "\">CLICK TO DOWNLOAD</a>";
   echo "</center>";
} else {
   LOGError('Error creating the folder for the ZIP file: '. $s4risdir);
   MSGError('Error creating the folder for the ZIP file: '.$s4risdir);
   ForceLoad("../index.php");
}
echo "<br><br><br>\n";
echo "<br><br><br>\n";
echo "<br><br><br>\n";
echo "<br><br><br>\n";
echo "<br><br><br>\n";
echo "<br><br><br>\n";
?>
<?php include("$locr/footnote.php"); ?>
