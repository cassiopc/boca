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
// modified 21/july/2012 by cassio@ime.usp.br
ob_start();
session_start();
require_once("globals.php");

if(!ValidSession()) {
	echo "<html><head><title>Download Page</title>";
        InvalidSession("filedownload.php");
        ForceLoad("index.php");
}

if(!isset($_GET["oid"]) || !is_numeric($_GET["oid"]) || !isset($_GET["filename"]) ||
   !isset($_GET["check"]) || $_GET["check"]=="") {
	echo "<html><head><title>Download Page</title>";
        IntrusionNotify("Bad parameters in filedownload.php");
        ForceLoad("index.php");
}

$cf = globalconf();
$fname = decryptData(rawurldecode($_GET["filename"]), session_id() . $cf["key"]);

if(isset($_GET["msg"]))
	$p = myhash($_GET["oid"] . $fname . rawurldecode($_GET["msg"]) . session_id() . $cf["key"]);
else
	$p = myhash($_GET["oid"] . $fname . session_id() . $cf["key"]);

if($p != $_GET["check"]) {
        echo "<html><head><title>View Page</title>";
        IntrusionNotify("Parameters modified in filedownload.php. ");
        ForceLoad("index.php");
}

require_once("db.php");

if ($_GET["oid"]>=0) {
	$c = DBConnect();
	DBExec($c, "begin work");

	if (($lo = DB_lo_open ($c, $_GET["oid"], "r")) === false) {
		echo "<html><head><title>Download Page</title>";
		DBExec($c, "rollback work");
		LOGError ("Unable to download file (" . basename($fname) . ")");
		MSGError ("Unable to download file (" . basename($fname) . ")");
		ForceLoad("index.php");
	}

	header ("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
	header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header ("Cache-Control: no-cache, must-revalidate");
	header ("Pragma: no-cache");
	header ("Content-transfer-encoding: binary\n");
	header ("Content-type: application/force-download");
//header ("Content-type: application/octet-stream");
//if (strstr($_SERVER["HTTP_USER_AGENT"], "MSIE"))
//	header("Content-Disposition: filename=" .$_GET["filename"]); // For IE
//else
	header ("Content-Disposition: attachment; filename=" . basename($fname));
	ob_end_flush();

	if (DB_lo_read_tobrowser ($_SESSION["usertable"]["contestnumber"],$lo) === false) {
		echo "<html><head><title>Download Page</title>";
		DBExec($c, "rollback work");
		LOGError ("Unable to download file (" . basename($fname) . ")");
		MSGError ("Unable to download file (" . basename($fname) . ")");
		ForceLoad("index.php");
	}
	DB_lo_close($lo);

	DBExec($c, "commit work");
	DBClose($c);
} else {
	header ("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
	header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header ("Cache-Control: no-cache, must-revalidate");
	header ("Pragma: no-cache");
//header ("Content-type: application/octet-stream");
//if (strstr($_SERVER["HTTP_USER_AGENT"], "MSIE"))
//	header("Content-Disposition: filename=" .$_GET["filename"]); // For IE
//else
	header ("Content-Disposition: attachment; filename=" . basename($fname));

	if (($str=file_get_contents($fname))===false) {
		header ("Content-type: text/html");
		echo "<html><head><title>Download Page</title>";
		MSGError ("Unable to open file (" . basename($fname) . ")");
		LOGError ("Unable to open file (" . basename($fname) . ")");
		ForceLoad('index.php');
		exit;
	} else {
		header ("Content-transfer-encoding: binary\n");
		header ("Content-type: application/force-download");
		echo decryptData($str, $cf["key"]);
	}
	ob_end_flush();
}

?>
