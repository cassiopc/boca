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
// Last modified 05/aug/2012 by cassio@ime.usp.br
ob_start();
header ("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header ("Cache-Control: no-cache, must-revalidate");
header ("Pragma: no-cache");
header ("Content-Type: text/html; charset=utf-8");
session_start();
//require_once('../version.php');
require_once("../globals.php");
require_once("../db.php");

if(!function_exists("globalconf") || !function_exists("sanitizeVariables")) {
    ob_end_flush();
	ForceLoad("../index.php");
	exit;
}

$getx=array();
if(isset($_GET['name'])) $getx['name']=$_GET['name'];
if(isset($_GET['password'])) $getx['password']=$_GET['password'];
if(isset($_GET['check'])) $getx['check']=$_GET['check'];
//if(isset($_POST)) {
if(isset($_POST["name"])) $getx['name']=$_POST['name'];
if(isset($_POST["password"])) $getx['password']=$_POST['password'];
if(isset($_POST["check"])) $getx['check']=$_POST['check'];
//}
//LOGError("PHPID=".$_COOKIE['PHPSESSID'].",extra=".$_SESSION['usertable']['usersessionextra'].
//		 ",session=".session_id().",name=".$getx['name'].", password=".$getx['password'].",check=".$getx['check']);
if (!isset($_SESSION["usertable"])) {
	if(isset($getx['name']) && $getx['name'] != "" && isset($getx['password']) && $getx['password'] != "") {
		$name = $getx["name"];
		LogLevel("Connection try by IP " . getIP() . ", username=" . $name,2);
		$password = $getx["password"];
		$usertable = DBLogIn($name, $password, false);
		if(!$usertable) {
            ob_end_flush();
			echo "<!-- <ERROR1> ". session_id() . " " . session_id() . " -->\n";
			exit;
		}
		if(!isset($getx['check'])) {
			ob_end_flush();
			echo "<!-- <ERROR2> ". session_id() . " " . session_id() . " -->\n";
			exit;
		}
	} else {
        ob_end_flush();
		LogLevel("Init connection by IP " . getIP(),2);
		echo "<!-- <SESSION1> ". session_id() . " " . session_id() . " -->\n";
		exit;
	}
}
if(!ValidSession()) {
    ob_end_flush();
	InvalidSession("site/index.php");
	ForceLoad("../index.php");
	exit;
}
if(isset($getx['check']) && isset($getx["password"]) && $getx['check'] != myhash($getx["password"] . $_SESSION['usertable']['userpassword'])) {
	ob_end_flush();
	echo "<!-- <SESSION2> ". session_id() . " " . $_SESSION['usertable']['usersessionextra'] . " -->\n";
	exit;
}

if($_SESSION["usertable"]["usertype"] != "site") {
    ob_end_flush();
	IntrusionNotify("site/index.php");
	ForceLoad("../index.php");
	exit;
}
?>
