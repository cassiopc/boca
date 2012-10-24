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
require('header.php');

header ("Content-transfer-encoding: binary\n");
ob_end_flush();

if(($ct = DBContestInfo($_SESSION["usertable"]["contestnumber"])) == null) {
	echo "<!-- <ERROR4> ".session_id() . " " . session_id() . " -->\n";
	exit;
}
if($ct["contestlocalsite"]==$ct["contestmainsite"]) {
	$fromsite = $_SESSION["usertable"]["usericpcid"];
	LOGLevel("Connection received from site=$fromsite PHPID=".$_COOKIE['PHPSESSID'].",extra=".$_SESSION['usertable']['usersessionextra'].
			 ",session=".session_id().",name=".$getx['name'].", check=".$getx['check'],2);
	if($fromsite != '' && is_numeric($fromsite) && $fromsite > 0) {
		$siteinfo = DBSiteInfo($_SESSION["usertable"]["contestnumber"],$fromsite);
		$scores = explode(",", $siteinfo['siteglobalscore']);
		if(count($scores)==0 || (count($scores)==1 && !is_numeric($scores[0]))) $scores=array($fromsite);
		$judges = explode(",", $siteinfo['sitejudging']);
		if(count($judges)==0 || (count($judges)==1 && !is_numeric($judges[0]))) $judges=array($fromsite);
		$scores = array_unique(array_merge($scores,$judges));
		if(in_array(0,$scores)) $scores=null;

		$tasks = explode(",", $siteinfo['sitetasking']);
		if(count($tasks)==0 || (count($tasks)==1 && !is_numeric($tasks[0]))) $tasks=array($fromsite);
	} else {
		echo "<!-- <ERROR9> ".session_id() . " " . session_id() . " -->\n";
		exit;
	}
	if(isset($_POST) && isset($_POST['xml'])) {
//		$fp=fopen('/tmp/aaa',"w"); fwrite($fp,$_POST['xml']); fclose($fp);
		$s = decryptData(rawurldecode($_POST['xml']),myhash($_SESSION["usertable"]["userpassword"]));
//		$fp=fopen('/tmp/aaa1',"w"); fwrite($fp,$s); fclose($fp);

		$ac=array();
		$ac['SITEREC']=array(
					  'site'=>$fromsite,
					  'sitenumber'=>0,
					  'number'=>0,
					  'sitename'=>0,
					  'siteip'=>0,
					  'siteduration'=>0,
					  'sitelastmileanswer'=>0,
					  'sitelastmilescore'=>0,
//					  'sitejudging'=>0,
//					  'sitetasking'=>0,
					  'siteautoend'=>0,
//					  'siteglobalscore'=>0,
					  'siteactive'=>0,
					  'sitescorelevel'=>0,
					  'sitepermitlogins'=>0,
					  'siteautojudge'=>0,
					  'sitenextuser'=>0,
					  'sitenextclar'=>0,
					  'sitenextrun'=>0,
					  'sitenexttask'=>0,
					  'sitemaxtask'=>0,
					  'sitechiefname'=>0,
					  'updatetime'=>0);
		$ac['SITETIME']=array('site'=>$fromsite,
							  'number'=>0,
							  'start'=>0,
							  'enddate'=>0,
							  'updatetime'=>0);
		$ac['USERREC']=array('site'=>$fromsite,
							 'user'=>0,
							 'number'=>0,
							 'username'=>0,
							 'usericpcid'=>0,
							 'userfull'=>0,
							 'userdesc'=>0,
							 'type'=>0,
							 'enabled'=>0,
							 'multilogin'=>0,
							 'userip'=>0,
							 'userlastlogin'=>0,
							 'userlastlogout'=>0,
							 'permitip'=>0,
							 'updatetime'=>0);
		$ac['CLARREC']=array('site'=>$judges,
					  'user'=>0,
					  'number'=>0,
					  'problem'=>0,
					  'question'=>0,
					  'clarnumber'=>0,
					  'clardate'=>0,
					  'clardatediff'=>0,
					  'clardatediffans'=>0,
					  'claranswer'=>0,
					  'clarstatus'=>0,
					  'clarjudge'=>0,
					  'clarjudgesite'=>0,
					  'updatetime'=>0);
		$ac['RUNREC']=array('site'=>$judges,
					 'user'=>0,
					 'number'=>0,
					 'runnumber'=>0,
					 'problem'=>0,
					 'lang'=>0,
					 'filename'=>0,
					 'filepath'=>0,
					 'rundate'=>0,
					 'rundatediff'=>0,
					 'rundatediffans'=>0,
					 'runanswer'=>0,
					 'runstatus'=>0,
					 'runjudge'=>0,
					 'runjudgesite'=>0,
					 'runjudge1'=>0,
					 'runjudgesite1'=>0,
					 'runanswer1'=>0,
					 'runjudge2'=>0,
					 'runjudgesite2'=>0,
					 'runanswer2'=>0,
					 'autoip'=>0,
					 'autobegindate'=>0,
					 'autoenddate'=>0,
					 'autoanswer'=>0,
					 'autostdout'=>0,
					 'autostderr'=>0,
					 'updatetime'=>0);
		$ac['TASKREC']=array(
			'site'=>$tasks,
			'user'=>0,
			'desc'=>0,
			'number'=>0,
			'tasknumber'=>0,
			'color'=>0,
			'colorname'=>0,
			'updatetime'=>0,
			'filename'=>0,
			'filepath'=>0,
			'sys'=>0,
			'status'=>0,
			'taskdate'=>0,
			'taskdatediff'=>0,
			'taskdatediffans'=>0,
			'taskstaffnumber'=>0,
			'taskstaffsite'=>0);

		if(importFromXML($s,$ac,$_SESSION["usertable"]["contestnumber"]))
			echo "<!-- <OK> -->";
		else
			echo "<!-- <NOTOK> -->";
	}
	$xml = generateXML($_SESSION["usertable"]["contestnumber"],0,$scores);
	echo encryptData($xml,myhash($_SESSION["usertable"]["userpassword"]));
} else 
	echo "<!-- <ERROR3> ".session_id() . " " . session_id() . " -->\n";
?>
