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
//Last updated 07/nov/2012 by cassio@ime.usp.br

require('header.php');

$contest = $_SESSION["usertable"]["contestnumber"];
$site = $_SESSION["usertable"]["usersitenumber"];

$ct = DBContestInfo($contest);
if(($st =  DBSiteInfo($contest, $site)) == null)
	ForceLoad("../index.php");

//if(isset($_GET['full']) && $_GET['full'] > 0)
	$freezeTime = $st['siteduration'];
//else
//	$freezeTime = $st['sitelastmilescore'];

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
	' AND problemnumber > 0');
$numProblems = DBnlines($r);
$r = DBExec($c,
	'SELECT username, userfullname FROM usertable' .
	' WHERE contestnumber = ' . $contest .
	' AND userenabled = \'t\' AND usersitenumber = ' . $site .
	' AND usertype = \'team\'');
$numTeams = DBnlines($r);

$contestfile = $contestfile .
	$numTeams . '' .
	$numProblems . "\n";

for ($i = 0; $i < $numTeams; $i++) {
	$a = DBRow($r, $i);
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

$contestfile = $contestfile .
	'1' . '' . '1' . "\n";
$contestfile = $contestfile .
	$numProblems . '' . 'Y' . "\n";

$score = DBScore($_SESSION["usertable"]["contestnumber"], false, -1, $ct["contestlocalsite"]);

//$contestfile = $contestfile .
//	"<h2>ICPC Output</h2>";
//$contestfile = $contestfile .
//	"<pre>";
$n=0;
$class=1;
while(list($e, $c) = each($score)) {
	if(isset($score[$e]["site"]) && isset($score[$e]["user"])) {
		if(DBSiteInfo($_SESSION["usertable"]["contestnumber"],$score[$e]["site"]) != null) {
			$r = DBUserInfo($_SESSION["usertable"]["contestnumber"], 
							$score[$e]["site"], $score[$e]["user"]);
			$contestfile = $contestfile .
				$r["usericpcid"] . "," .
				$class++ . "," .
				$score[$e]["totalcount"] . "," . 
				$score[$e]["totaltime"] . ",";
			
			if($score[$e]["first"])
				$contestfile = $contestfile . $score[$e]["first"] . "\n";
			else $contestfile = $contestfile . "0\n";
			$n++;
		}
	}
}
//$contestfile = $contestfile .
//	"</pre>";

$timefile = $st['currenttime'];
$versionfile = '1.0' . "\n";

$run = DBAllRunsInSites($contest, $site, 'run');

$numRuns = count($run);

$runfile = '';
for ($i = 0; $i < $numRuns; $i++) {
	$u = DBUserInfo($contest, $site, $run[$i]['user']);

	$runID = $run[$i]['number'];
	$runTime = dateconvminutes($run[$i]['timestamp']);
	$runTeam = $u['username'];
	$runProblem = $run[$i]['problem'];

	if ($runTime > $freezeTime) {
		continue;
	}

	$runfile = $runfile .
		$runID . '' .
		$runTime . '' .
		$runTeam . '' .
		$runProblem . '';

	if ($run[$i]['yes']=='t') {
		$runfile = $runfile .
			'Y' . "\n";
	} else if ($run[$i]['answer'] == 'Not answered yet') {
		$runfile = $runfile .
			'?' . "\n";
	} else {
		$runfile = $runfile .
			'N' . "\n";
	}
}

$ds = DIRECTORY_SEPARATOR;
if($ds=="") $ds = "/";

if(isset($_SESSION['locr'])) {
	$webcastdir = $_SESSION['locr'] . $ds . 'private' .$ds. 'webcast';
	$webcastparentdir = $_SESSION['locr'] . $ds. 'private';
} else {
	$webcastdir = $locr . $ds . 'private' . $ds . 'webcast';
	$webcastparentdir = $locr . $ds . 'private';
}
cleardir($webcastdir);
@mkdir($webcastdir);
if(is_writable($webcastdir)) {
	file_put_contents($webcastdir . $ds . 'runs',$runfile);
	file_put_contents($webcastdir . $ds . 'contest',$contestfile);
	file_put_contents($webcastdir . $ds . 'version',$versionfile);
	file_put_contents($webcastdir . $ds . 'time',$timefile);
	if(@create_zip($webcastparentdir,array('webcast'),$webcastdir . ".tmp") != 1) {
		LOGError("Cannot create score webcast.tmp file");
		MSGError("Cannot create score webcast.tmp file");
	} else {
		$cf = globalconf();
		file_put_contents($webcastdir . ".tmp",encryptData(file_get_contents($webcastdir . ".tmp"), $cf["key"],false));
		@rename($webcastdir . ".tmp",$webcastdir . '.zip');
	}
	echo "<br><br><br><center>";
	echo "<a href=\"$locr/filedownload.php?". 
		filedownload(-1,$webcastdir . '.zip') . "\">CLICK TO DOWNLOAD</a>";
	echo "</center>";
} else {
	LOGError('Error creating the folder for the ZIP file: '. $webcastdir);
	MSGError('Error creating the folder for the ZIP file: '.$webcastdir);
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
