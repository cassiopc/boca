<?php

require '../../db.php';
require '../config.php';

header('Content-type: text/plain; encoding=utf-8');

$ct = DBContestInfo($contest);

echo
	$ct['contestname'] . "\n";

echo 
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

echo 
	$numTeams . '' .
	$numProblems . "\n";

for ($i = 0; $i < $numTeams; $i++) {
	$a = DBRow($r, $i);
	$teamID = $a['username'];
	$pieces = explode('</b>', $a['userfullname']);
	$teamName = trim($pieces[1]);
	$pieces = explode('<b>', $pieces[0]);
	$teamUni = trim($pieces[1]);

	echo
		$teamID . '' .
		$teamUni . '' .
		$teamName . "\n";
}

?>
