<?php

require '../../db.php';
require '../config.php';

header('Content-type: text/plain; encoding=utf-8');

$s = DBSiteInfo($contest, $site);

$run = DBAllRunsInSites($contest, $site, 'run');

$numRuns = count($run);

for ($i = 0; $i < $numRuns; $i++) {
	$u = DBUserInfo($contest, $site, $run[$i]['user']);

	$runID = $run[$i]['number'];
	$runTime = dateconvminutes($run[$i]['timestamp']);
	$runTeam = $u['username'];
	$runProblem = $run[$i]['problem'];

	if ($runTime > $freezeTime) {
		continue;
	}

	echo
		$runID . '' .
		$runTime . '' .
		$runTeam . '' .
		$runProblem . '';

	if ($run[$i]['yes']=='t') {
		echo 'Y' . "\n";
	} else if ($run[$i]['answer'] == 'Not answered yet') {
		echo '?' . "\n";
	} else {
		echo 'N' . "\n";
	};
}

?>
