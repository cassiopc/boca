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

if(system('test "`id -u`" -eq "0"',$retval)===false || $retval!=0) {
	echo "Must be run as root\n";
	exit;
}
echo "\nThis will erase all the data in your bocadb database.";
echo "\n***** YOU WILL LOSE WHATEVER YOU HAVE THERE!!! *****";
echo "\nType YES and press return to continue or anything else will abort it: ";
$resp = strtoupper(trim(fgets(STDIN)));
if($resp != 'YES') exit;

echo "\ndropping database\n";
DBDropDatabase();
echo "creating database\n";
DBCreateDatabase();
echo "creating tables\n";
DBCreateContestTable();
DBCreateSiteTable();
DBCreateSiteTimeTable();
DBCreateUserTable();
DBCreateLogTable();
DBCreateProblemTable();
DBCreateAnswerTable();
DBCreateTaskTable();
DBCreateLangTable();
DBCreateRunTable();
DBCreateClarTable();
DBCreateBkpTable();
echo "creating initial fake contest\n";
DBFakeContest();
?>
