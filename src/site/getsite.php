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
// Last modified 25/July/2017 by cassio@ime.usp.br
require('header.php');

header ("Content-transfer-encoding: binary\n");
ob_end_flush();

if(($ct = DBContestInfo($_SESSION["usertable"]["contestnumber"])) == null) {
	echo "<!-- <ERROR4> ".session_id() . " -->\n";
	exit;
}
if($ct["contestlocalsite"]==$ct["contestmainsite"]) {
	$fromsite = $_SESSION["usertable"]["usericpcid"];
	LOGLevel("Connection received from site=$fromsite",2); // PHPID=".$_COOKIE['PHPSESSID'].",extra=".$_SESSION['usertable']['usersessionextra'].",session=".session_id(),2);
	if($fromsite != '' && is_numeric($fromsite) && $fromsite > 0) {
	  if(isset($_POST)) {
	    $u = DBUserInfo($_SESSION["usertable"]["contestnumber"], $_SESSION["usertable"]["usersitenumber"], $_SESSION["usertable"]["usernumber"],null,false);
	    if(isset($_POST['xml'])) {
	      //		$fp=fopen('/tmp/aaa',"w"); fwrite($fp,$_POST['xml']); fclose($fp);
	      $s = decryptData($_POST['xml'],$u["userpassword"],'xml from local not ok');
	      //		$fp=fopen('/tmp/aaa1',"w"); fwrite($fp,$s); fclose($fp);
	      if(strtoupper(substr($s,0,5)) != "<XML>") {
		echo "<!-- <ERROR8> ".session_id() . " -->\n";
		echo "<!-- <NOTOK> -->";
	      } else {
		$resp = importFromXML($s,$_SESSION["usertable"]["contestnumber"],$fromsite,true,0,-1);
		echo $resp[1];
		if($resp[0])
		  echo "<!-- <OK> -->";
		else
		  echo "<!-- <NOTOK> -->";
	      }
	    }
	    if(isset($_POST['updatetime']) && is_numeric($_POST['updatetime'])) {
	      $xml = generateSiteXML($_SESSION["usertable"]["contestnumber"],$fromsite,$_POST['updatetime'],$ct["contestmainsite"]);
	      echo "<!-- " . encryptData($xml[0],$u["userpassword"],false) . " -->";
	      //	      echo "MAIN\n" . $xml[1];
	    }
	  } else { 
	    echo "<!-- <ERROR3> ".session_id() . " -->\n";
	  }
	} else {
	  echo "<!-- <ERROR9> ".session_id() . " -->\n";
	  exit;
	}
}
?>
