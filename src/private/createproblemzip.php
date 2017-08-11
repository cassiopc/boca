#!/usr/bin/php
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
//Last updated 06/aug/2012 by cassio@ime.usp.br
$ds = DIRECTORY_SEPARATOR;
if($ds=="") $ds = "/";

if(is_readable('/etc/boca.conf')) {
	$pif=parse_ini_file('/etc/boca.conf');
	$bocadir = trim($pif['bocadir']) . $ds . 'src';
} else {
  if(is_readable('boca.conf')) {
        $pif=parse_ini_file('boca.conf');
        $bocadir = trim($pif['bocadir']) . $ds . 'src';
  }
  else
	$bocadir = getcwd();
}

if(is_readable($bocadir . $ds . '..' .$ds . 'db.php')) {
	require_once($bocadir . $ds . '..' .$ds . 'db.php');
	@include_once($bocadir . $ds . '..' .$ds . 'version.php');
} else {
  if(is_readable($bocadir . $ds . 'db.php')) {
	require_once($bocadir . $ds . 'db.php');
	@include_once($bocadir . $ds . 'version.php');
  } else {
	  echo "unable to find db.php";
	  exit;
  }
}
if (getIP()!="UNKNOWN" || php_sapi_name()!=="cli") exit;
ini_set('memory_limit','600M');
ini_set('output_buffering','off');
ini_set('implicit_flush','on');
@ob_end_flush();
/*
if(system('test "`id -u`" -eq "0"',$retval)===false || $retval!=0) {
	echo "Must be run as root\n";
	exit;
}
*/
if(count($argv) < 3 || !is_readable($argv[1])) {
	echo "Usage: createproblemzip.php <problem_directory> <problem_zipfile> [<password>]\n";
	exit;
}
if(count($argv) >= 4)
	$password2 = trim($argv[3]);
else {
	echo "\nWe use a two password system: The following password is\nused to unlock the true password that encrypts the zip file.\n";
	echo "It should be kept secret during all the time.\n";
	echo "Please type the password to unlock the zip file password: ";
// ONLY WORKS IN LINUX!!!
	system('stty -echo');
	$password2 = trim(fgets(STDIN));
	system('stty echo');
	
	echo "\nPlease retype the password: ";
// ONLY WORKS IN LINUX!!!
	system('stty -echo');
	$password3 = trim(fgets(STDIN));
	system('stty echo');
	if($password3 != $password2) {
		echo "\nPasswords mismatch - aborting\n";
		exit;
	}
}
if(strlen($password2) < 12)
	echo "\n\n#\n##\n###\n####\n#####\n###### WARNING: the main password should be really secure - consider using a longer and complicated password\n";

$password1 = randstr(16);
echo "\nCreating file " . $argv[2] . " from directory/file "  . $argv[1] . "\n";
if(is_dir(trim($argv[1]))) {
	if(($ret=create_zip(trim($argv[1]),glob(trim($argv[1]) . $ds . '*'),trim($argv[2]),true)) > 0)
		echo "ZIP Success\n";
	else
		echo "ZIP Error $ret\n";
	$encdata=encryptData(file_get_contents(trim($argv[2])),'#####'.$password1,false);
	if($encdata=='')
		$encdata=file_get_contents(trim($argv[2]));
} else {
	$encdata=encryptData(file_get_contents(trim($argv[1])),'#####'.$password1,true);
	if($encdata=='')
		$encdata=file_get_contents(trim($argv[1]));
}

file_put_contents(trim($argv[2]),$encdata);
echo "Output file generated in " . $argv[2] . "\n";

echo "\n\nThe following line is a key that should be appended to a text file with one key per line.\n\n";
echo substr($encdata,0,10) . '#####' . encryptData('#####'.$password1,$password2,false) . "\n\n";

echo "Later on, in the admin web interface of BOCA tab Contest, item Contest keys, please select the file with\nall these lines click on update.\n\n";

exit;
?>
