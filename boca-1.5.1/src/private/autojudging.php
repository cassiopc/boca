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
// Last modified 06/aug/2012 by cassio@ime.usp.br
$ds = DIRECTORY_SEPARATOR;
if($ds=="") $ds = "/";

if(is_readable('/etc/boca.conf')) {
	$pif=parse_ini_file('/etc/boca.conf');
	$bocadir = trim($pif['bocadir']) . $ds . 'src';
} else {
	$bocadir = getcwd();
}

if(is_readable($bocadir . $ds . '..' .$ds . 'db.php')) {
	require_once($bocadir . $ds . '..' .$ds . 'db.php');
	require_once($bocadir . $ds . '..' .$ds . 'version.php');
} else {
  if(is_readable($bocadir . $ds . 'db.php')) {
	require_once($bocadir . $ds . 'db.php');
	require_once($bocadir . $ds . 'version.php');
  } else {
	  echo "unable to find db.php";
	  exit;
  }
}
if (getIP()!="UNKNOWN" || php_sapi_name()!=="cli") exit;
if(system('test "`id -u`" -eq "0"',$retval)===false || $retval!=0) {
	echo "Must be run as root\n";
	exit;
}

ini_set('memory_limit','600M');
ini_set('output_buffering','off');
ini_set('implicit_flush','on');
@ob_end_flush();
echo "max memory set to " . ini_get('memory_limit'). "\n";

$tmpdir = getenv("TMP");
if($tmpdir=="") $tmpdir = getenv("TMPDIR");
if($tmpdir[0] != '/') $tmdir = "/tmp";
if($tmpdir=="") $tmpdir = "/tmp";

$basdir=$ds;
if(file_exists($ds . 'bocajail' . $tmpdir)) {
  $tmpdir=$ds . 'bocajail' . $tmpdir;
  $basdir=$ds . 'bocajail' . $ds;
  echo "bocajail environment seems to exist - trying to use it\n";
} else {
  echo "bocajail not found - trying to proceed without using it\n";
}

if($ds=='/') {
	system("find $basdir -user bocajail -delete >/dev/null 2>/dev/null");
	system("find $basdir -user nobody -delete >/dev/null 2>/dev/null");
	system("find $basdir -group users -exec chgrp root '{}' \\; 2>/dev/null");
	system("find $basdir -perm /1002 -type d > /tmp/boca.writabledirs.tmp 2>/dev/null");
	system('chmod 400 /tmp/boca.writabledirs.tmp 2>/dev/null');
}
umask(0022);

$cache = $tmpdir . $ds . "bocacache.d";
cleardir($cache);
@mkdir($cache);
$key=md5(mt_rand() . rand() . mt_rand());

$cf = globalconf();
$ip = $cf["ip"];
$activecontest=DBGetActiveContest();
$prevsleep=0;
//$dodebug=1;
while(42) {

if(($run = DBGetRunToAutojudging($activecontest["contestnumber"], $ip)) === false) {
  if($prevsleep==0)
    echo "Nothing to do. Sleeping...";
  else
    echo ".";
  flush();
  sleep(10);
  $prevsleep=1;
  continue;
}
if(!isset($dodebug)) {
	if(isset($dir)) cleardir($dir);
	if(isset($name)) unlink($name);
}
echo "\n";
flush();
$prevsleep=0;

$number=$run["number"];
$site=$run["site"];
$contest=$run["contest"];

echo "Removing possible files from previous runs\n";
$dirs=file('/tmp/boca.writabledirs.tmp');
for($dir=0;$dir<count($dirs);$dir++) {
	$dirn=trim($dirs[$dir]) . $ds;
	if($dirn[0] != '/') continue;
	system("find \"$dirn\" -user bocajail -delete >/dev/null 2>/dev/null");
	system("find \"$dirn\" -user nobody -delete >/dev/null 2>/dev/null");
}

echo "Entering directory $tmpdir (contest=$contest, site=$site, run=$number)\n";
chdir($tmpdir);
for($i=0; $i<5; $i++) {
	  $name = tempnam($tmpdir, "boca");
	  $dir = $name . ".d";
	  if(@mkdir($dir, 0755)) break;
	  @unlink($name);
	  @rmdir($dir);
}
if($i>=5) {
	  echo "It was not possible to create a unique temporary directory\n";
  	  LogLevel("Autojuging: Unable to create temp directory (run=$number, site=$site, contest=$contest)",1);
	  DBGiveUpRunAutojudging($contest, $site, $number, $ip, "Autojuging error: problem creating temp directory");
	  continue;
}
chdir($dir);

echo "Using directory $dir (contest=$contest, site=$site, run=$number)\n";

if($run["sourceoid"]=="" || $run["sourcename"]=="") {
	LogLevel("Autojuging: Source file not defined (run=$number, site=$site, contest=$contest)",1);
	echo "Source file not defined (contest=$contest, site=$site, run=$number)\n";
	DBGiveUpRunAutojudging($contest, $site, $number, $ip, "Autojuging error: source file not defined");
	continue;
}
if($run["inputoid"]=="" || $run["inputname"]=="") {
	LogLevel("Autojuging: problem package not defined (run=$number, site=$site, contest=$contest)",1);
	echo "Package file not defined (contest=$contest, site=$site, run=$number)\n";
	DBGiveUpRunAutojudging($contest, $site, $number, $ip, "Autojuging error: problem package file not defined");
	continue;
}
$c = DBConnect();
DBExec($c, "begin work", "Autojudging(exporttransaction)");
if(DB_lo_export($contest,$c, $run["sourceoid"], $dir . $ds . $run["sourcename"]) === false) {
        DBExec($c, "rollback work", "Autojudging(rollback-source)");
	LogLevel("Autojudging: Unable to export source file (run=$number, site=$site, contest=$contest)",1);
        echo "Error exporting source file ${run["sourcename"]} (contest=$contest, site=$site, run=$number)\n";
	DBGiveUpRunAutojudging($contest, $site, $number, $ip, "Autojuging error: unable to export source file");
	DBExec($c, "commit", "Autojudging(exportcommit)");
	continue;
}
if(is_readable($cache . $ds . $run["inputoid"] . "." . $run["inputname"])) {
	DBExec($c, "commit", "Autojudging(exportcommit)");
	echo "Getting problem package file from local cache: " . $cache . $ds . $run["inputoid"] . "." . $run["inputname"] . "\n";
	$s = file_get_contents($cache .	$ds . $run["inputoid"]	. "." . $run["inputname"]);
	file_put_contents($dir . $ds . $run["inputname"], decryptData($s,$key));
	$basename=$basenames[$run['inputoid']. "." . $run["inputname"]];
} else {
	echo "Downloading problem package file from db into: " . $dir . $ds . $run["inputname"] . "\n";
	if(DB_lo_export($contest,$c, $run["inputoid"], $dir . $ds . $run["inputname"]) === false) {
        DBExec($c, "rollback work", "Autojudging(rollback-input)");
		LogLevel("Autojudging: Unable to export problem package file (run=$number, site=$site, contest=$contest)",1);
        echo "Error exporting problem package file ${run["inputname"]} (contest=$contest, site=$site, run=$number)\n";
		DBGiveUpRunAutojudging($contest, $site, $number, $ip, "Autojuging error: unable to export problem package file");
		DBExec($c, "commit", "Autojudging(exportcommit)");
		continue;
	}
	DBExec($c, "commit", "Autojudging(exportcommit)");

	echo "Problem package downloaded -- running init scripts to obtain limits and other information\n";
	$zip = new ZipArchive;
	if ($zip->open($dir . $ds . $run["inputname"]) === true) {
		$zip->extractTo($dir . $ds . "problemdata");
		$zip->close();
	} else {
		echo "Failed to unzip the package file -- please check the problem package (maybe it is encrypted?)\n";
		DBGiveUpRunAutojudging($contest, $site, $number, $ip, "Autojuging error: problem package file is invalid");
		cleardir($dir . $ds . "problemdata");
		continue;
	}
	if(($info=@parse_ini_file($dir . $ds . "problemdata" . $ds . "description" . $ds . 'problem.info'))===false) {
		echo "Problem content missing (description/problem.info) -- please check the problem package\n";
		DBGiveUpRunAutojudging($contest, $site, $number, $ip, "Autojuging error: problem package file is incomplete");
		cleardir($dir . $ds . "problemdata");
		continue;
	}
	$descfile=trim(sanitizeText($info['descfile']));
	$basename=trim(sanitizeText($info['basename']));
	$fullname=trim(sanitizeText($info['fullname']));
	if($basename=='') {
		echo "Problem content missing (description/problem.info) -- please check the problem package\n";
		DBGiveUpRunAutojudging($contest, $site, $number, $ip, "Autojuging error: problem package file is invalid");
		cleardir($dir . $ds . "problemdata");
		continue;
	}
	$basenames[$run['inputoid']. "." . $run["inputname"]]=$basename;
	if(!is_dir($dir . $ds . "problemdata" . $ds . "limits")) {
		echo "Problem content missing (limits) -- please check the problem package\n";
		DBGiveUpRunAutojudging($contest, $site, $number, $ip, "Autojuging error: problem package file is invalid");
		cleardir($dir . $ds . "problemdata");
		continue;
	}
	chdir($dir . $ds . "problemdata" . $ds . "limits");
	$limits[$basename]=array();
	foreach(glob($dir . $ds . "problemdata" . $ds . "limits" .$ds . '*') as $file) {
		chmod($file,0700);
		$ex = escapeshellcmd($file);
		$ex .= " >stdout 2>stderr";
		echo "Executing INIT SCRIPT " . $ex . " at " . getcwd() . "\n";
		if(system($ex, $retval)===false) $retval=-1;
		if($retval != 0) {
			echo "Error running script -- please check the problem package\n";
			DBGiveUpRunAutojudging($contest, $site, $number, $ip, "Autojuging error: problem package file is invalid");
			cleardir($dir . $ds . "problemdata");
			continue;
		}
		$limits[$basename][basename($file)] = file('stdout',FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	}
	$cont=false;
	foreach(glob($dir . $ds . "problemdata" . $ds . "tests" .$ds . '*') as $file) {
		chdir($dir . $ds . "problemdata" . $ds . "tests");
		chmod($file,0700);
		$ex = escapeshellcmd($file);
		$ex .= " >stdout 2>stderr";
		echo "Executing TEST SCRIPT " . $ex . " at " . getcwd() . "\n";
		if(system($ex, $retval)===false) $retval=-1;
		if($retval != 0) {
			echo "Error running test script -- please check the problem package\n";
			DBGiveUpRunAutojudging($contest, $site, $number, $ip, "Autojuging error: internal test script failed");
			$cont=true;
			break;
		}
	}
	cleardir($dir . $ds . "problemdata");
	if($cont)
		continue;

	$s = file_get_contents($dir . $ds . $run["inputname"]);
	file_put_contents($cache . $ds . $run["inputoid"] . "." . $run["inputname"], encryptData($s,$key));
}

if(!isset($limits[$basename][$run["extension"]][0]) || !is_numeric($limits[$basename][$run["extension"]][0]) ||
   !isset($limits[$basename][$run["extension"]][1]) || !is_numeric($limits[$basename][$run["extension"]][1]) ||
   !isset($limits[$basename][$run["extension"]][2]) || !is_numeric($limits[$basename][$run["extension"]][2]) ||
   !isset($limits[$basename][$run["extension"]][3]) || !is_numeric($limits[$basename][$run["extension"]][3]) ) {
	echo "Failed to find proper limits information for the problem -- please check the problem package\n";
	DBGiveUpRunAutojudging($contest, $site, $number, $ip, "Autojuging error: problem package file is invalid");
	continue;
}

// COMPILATION
//# parameters are:
//# $1 source_file
//# $2 exe_file (default ../run.exe)
//# $3 timelimit (optional, limit to run all the repetitions, by default only one repetition)
//# $4 maximum allowed memory (in MBytes)

$zip = new ZipArchive;
if ($zip->open($dir . $ds . $run["inputname"]) === true) {
	$zip->extractTo($dir, array("compile" . $ds . $run["extension"]));
	$zip->close();
} else {
	echo "Failed to unzip the package file -- please check the problem package\n";
	DBGiveUpRunAutojudging($contest, $site, $number, $ip, "Autojuging error: problem package file is invalid");
	continue;
}

$script = $dir . $ds . 'compile' . $ds . $run["extension"];
if(!is_file($script)) {
	echo "Error (not found) compile script for ".$run["extension"]." -- please check the problem package\n";
	DBGiveUpRunAutojudging($contest, $site, $number, $ip, "Autojuging error: compile script failed (".$run["extension"].")");
	continue;
}

chdir($dir);
@unlink('allout');
system('touch allout');
@unlink('allerr');
system('touch allerr');

chmod($script, 0700);
$ex = escapeshellcmd($script) ." ".
	escapeshellarg($run["sourcename"])." ".
	escapeshellarg($basename) . " ".
	escapeshellarg(trim($limits[$basename][$run["extension"]][0]))." ".
	escapeshellarg(trim($limits[$basename][$run["extension"]][2]));
$ex .= " >stdout 2>stderr";
echo "Executing " . $ex . " at " . getcwd() . "\n";
if(system($ex, $retval)===false) $retval=-1;

if(is_readable('stdout')) {
    system('echo ##### COMPILATION STDOUT: >> allerr');
	system('cat stdout >> allerr');
}
if(is_readable('stderr')) {
    system('echo ##### COMPILATION STDERR: >> allerr');
	system('cat stderr >> allerr');
}

if($retval != 0) {
	list($retval,$answer) = exitmsg($retval);
	$answer = "(WHILE COMPILING) " . $answer;
} else {
//# parameters are:
//# $1 exe_file
//# $2 input_file
//# $3 timelimit (limit to run all the repetitions, by default only one repetition)
//# $4 number_of_repetitions_to_run (optional, can be used for better tuning the timelimit)
//# $5 maximum allowed memory (in MBytes)
//# $6 maximum allowed output size (in KBytes)

	$zip = new ZipArchive;
	$inputlist = array();
	$ninputlist = 0;
	$outputlist = array();
	$noutputlist = 0;
	if ($zip->open($dir . $ds . $run["inputname"]) === true) {
		for($i = 0; $i < $zip->numFiles; $i++) {
			$filename = $zip->getNameIndex($i);
			$pos = strrpos(dirname($filename),"input");
			if($pos !== false && $pos==strlen(dirname($filename))-5) {
				$inputlist[$ninputlist++] = 'input' . $ds . basename($filename);
				$outputlist[$noutputlist++] = 'output' . $ds . basename($filename,'.link');
			}
		}
		$zip->extractTo($dir, array_merge(array("run" . $ds . $run["extension"]),$inputlist));
		$zip->close();    
	} else {
		echo "Failed to unzip the file (inputs) -- please check the problem package\n";
		DBGiveUpRunAutojudging($contest, $site, $number, $ip, "Autojuging error: problem package file is invalid");
		continue;
	}
	$retval = 0;
	$script = $dir . $ds . 'run' . $ds . $run["extension"];
	if(!is_file($script)) {
		echo "Failed to unzip the run script -- please check the problem package\n";
		DBGiveUpRunAutojudging($contest, $site, $number, $ip, "Autojuging error: problem package file is invalid");
		continue;
	}
	chmod($script, 0700);
	mkdir('team', 0755);
	if($ninputlist == 0) {
		echo "Failed to read input files from ZIP -- please check the problem package\n";
		DBGiveUpRunAutojudging($contest, $site, $number, $ip, "Autojuging error: problem package file is invalid");
		continue;
	} else {
		$errp=0;
		foreach($inputlist as $file) {
			$file = basename($file);
			if(is_file($dir . $ds . "input" . $ds . $file)) {
				$file1=basename($file,'.link');
				if($file != $file1) {
					$fnam = trim(file_get_contents($dir . $ds . "input" . $ds . $file));
					echo "Input file $file is a link. Trying to read the linked file: ($fnam)\n";
					if(is_readable($fnam)) {
						@unlink($dir . $ds . "input" . $ds . $file);
						$file = basename($file,".link");
						@copy($fnam,$dir . $ds . "input" . $ds . $file);
 					} else {
						echo "Failed to read input files from link indicated in the ZIP -- please check the problem package\n";
						DBGiveUpRunAutojudging($contest, $site, $number, $ip, "Autojuging error: problem package file is invalid or missing files on the autojudge");
						$errp=1; break;
					}
				}

				$ex = escapeshellcmd($script) ." ".
					escapeshellarg($basename) . " ".
					escapeshellarg($dir . $ds . "input" . $ds . $file)." ".
					escapeshellarg(trim($limits[$basename][$run["extension"]][0]))." ".
					escapeshellarg(trim($limits[$basename][$run["extension"]][1]))." ".
					escapeshellarg(trim($limits[$basename][$run["extension"]][2]))." ".
					escapeshellarg(trim($limits[$basename][$run["extension"]][3]));
				$ex .= " >stdout 2>stderr";
				echo "Executing " . $ex . " at " . getcwd() . " for input " . $file . "\n";
				if(system($ex, $retval)===false) $retval=-1;
				foreach (glob($dir . $ds . '*') as $fne) {
					@chown($fne,"nobody");
					@chmod($fne,0755);
				}
				if(is_readable('stderr0'))
					system('cat stderr0 >> stderr');
				system('echo ##### STDERR FOR FILE ' . escapeshellarg($file) . ' >> allerr');
				system('cat stderr >> allerr');
				system('cat stdout > team' . $ds . escapeshellarg($file));
				system('echo ##### STDOUT FOR FILE ' . escapeshellarg($file) . ' >> allout');
				system('cat stdout >> allout');
				if($retval != 0) {
					list($retval,$answer) = exitmsg($retval);
					$answer = "(WHILE RUNNING) " . $answer;
					break;
				}
			} else {
				echo "==> ERROR reading input file " . $dir . $ds . "input" . $ds . $file . " - skipping it!\n";
			}
		}
		if($errp==1) continue;
	}
	if($retval==0) {
		echo "Processing results\n";
		$zip = new ZipArchive;
		if ($zip->open($dir . $ds . $run["inputname"]) === true) {
			$zip->extractTo($dir, array_merge(array("compare" . $ds . $run["extension"]),$outputlist));
			$zip->close();
		} else {
			echo "Failed to unzip the file (outputs) -- please check the problem package\n";
			DBGiveUpRunAutojudging($contest, $site, $number, $ip, "Autojuging error: problem package file is invalid");
			continue;
		}
		$script = $dir . $ds . 'compare' . $ds . $run["extension"];
		$retval = 0;
		chmod($script, 0700);
		foreach($outputlist as $file) {
			$file = basename($file);
			if(is_file($dir . $ds . 'output' . $ds . $file)) {
				$ex = escapeshellcmd($script) ." ".
					escapeshellarg($dir . $ds . "team" . $ds . $file)." ".
					escapeshellarg($dir . $ds . "output" . $ds . $file)." ".
					escapeshellarg($dir . $ds . "input" . $ds . $file) . " >compout";
				echo "Executing " . $ex . " at " . getcwd() . " for output file $file\n";
				$answer = system($ex, $localretval);

				$fp = fopen($dir . $ds . "allerr", "a+");
				fwrite($fp, "\n\n===OUTPUT OF COMPARING SCRIPT FOLLOWS FOR FILE " .$file ." (EMPTY MEANS NO DIFF)===\n");
				$dif = file($dir . $ds . "compout");
				$difi = 0;
				for(; $difi < count($dif)-1 && $difi < 5000; $difi++)
					fwrite($fp, $dif[$difi]);
				if($difi >= 5000) fwrite($fp, "===OUTPUT OF COMPARING SCRIPT TOO LONG - TRUNCATED===\n");
				else fwrite($fp, "===OUTPUT OF COMPARING SCRIPT ENDS HERE===\n");
				$answer = trim($dif[count($dif)-1]);
				fclose($fp);
				foreach (glob($dir . $ds . '*') as $fne) {
					@chown($fne,"nobody");
					@chmod($fne,0755);
				}
				// retval 5 (presentation) and retval 6 (wronganswer) are already compatible with the compare script
				if($localretval < 4 || $localretval > 6) {
					// contact staff
					$retval = 7;
					$answer='Contact staff';
				}
				else if($retval==0) {
					if($localretval==4) {
						// YES!
						$answer='YES';
						$retval = 1;
					} else $retval=$localretval;
				}
				else if($retval==1) {
					if($localretval!=4) {
						$retval=$localretval;
						$answer='Presentation error';
					}
				}
			} else {
				echo "==> ERROR reading output file " . $dir . $ds . 'output' . $ds . $file . " - skipping it!\n";
			}
		}
	}
}
if($retval > 9) {
	$ans = file("allout");
	$anstmp = trim(escape_string($ans[count($ans)-1]));
	unset($ans);
	LogLevel("Autojudging: Script returned unusual code: $retval ($anstmp)".
			 "(run=$number, site=$site, contest=$contest)",1);
	echo "Autojudging script returned unusual code $retval ($anstmp)".
		"(contest=$contest, site=$site, run=$number)\n";
	$answer = "(check output files - unusual code: $retval) " . $anstmp;
	// contact staff
	$retval = 7;
}

echo "Sending results to server...\n";
//echo "out==> "; system("tail -n1 ". $dir.$ds.'allout');
//echo "err==> "; system("tail -n1 ". $dir.$ds.'allerr');
DBUpdateRunAutojudging($contest, $site, $number, $ip, $answer, $dir.$ds.'allout', $dir.$ds.'allerr', $retval);
LogLevel("Autojudging: answered '$answer' (run=$number, site=$site, contest=$contest)",3);
echo "Autojudging answered '$answer' (contest=$contest, site=$site, run=$number)\n";
}
?>
