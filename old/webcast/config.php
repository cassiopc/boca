<?php
$loc = $locr = "..";
require_once("$locr/globals.php");
require_once("$locr/db.php");
if(!ValidSession()) {
	InvalidSession("webcast/index.php");
        ForceLoad("$loc/index.php");
}
if($_SESSION["usertable"]["usertype"] != "admin") {
	IntrusionNotify("webcast/index.php");
	ForceLoad("$loc/index.php");
}

$contest = $_SESSION["usertable"]["contestnumber"];
$site = $_SESSION["usertable"]["usersitenumber"];

if(($ct =  DBSiteInfo($contest, $site)) == null)
	ForceLoad("../index.php");

if(isset($_GET['full']) && $_GET['full'] > 0)
	$freezeTime = $ct['siteduration'];
else
	$freezeTime = $ct['sitelastmilescore'];

?>
