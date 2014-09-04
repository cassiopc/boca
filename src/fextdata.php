<?php
////////////////////////////////////////////////////////////////////////////////
//BOCA Online Contest Administrator
//    Copyright (C) 2003-2014 by BOCA Development Team (bocasystem@gmail.com)
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
// Last modified 04/sept/2014 by cassio@ime.usp.br

function scoretransfer($putname, $localsite) {
	$ds = DIRECTORY_SEPARATOR;
	if($ds=="") $ds = "/";

	if(is_readable('/etc/boca.conf')) {
		$pif=parse_ini_file('/etc/boca.conf');
		$bocaproxy = @trim($pif['proxy']);
		if(substr($bocaproxy,0,6) != 'tcp://')
			$bocaproxy = 'tcp://' . $bocaproxy;
		$bocaproxylogin = @trim($pif['proxylogin']);
		$bocaproxypass = @trim($pif['proxypassword']);
		if($bocaproxylogin != "")
			$bocaproxypass = base64_encode($bocaproxylogin . ":" . $bocaproxypass)
	} else {
		$bocaproxy = "";
		$bocaproxypass = "";
	}

	$privatedir = $_SESSION['locr'] . $ds . "private";
	if(!is_readable($privatedir . $ds . 'remotescores' . $ds . "otherservers")) return;
	$localfile = "score_site" . $localsite . "_" . $localsite . "_x.dat";
	$remotesite = @file($privatedir . $ds . 'remotescores' . $ds . "otherservers");

   $contest=$_SESSION["usertable"]["contestnumber"];
   if($contest != '' && ($ct = DBContestInfo($contest)) != null) {
     if(trim($ct['contestmainsiteurl']) != '') {
       $tmp = explode(' ',$ct['contestmainsiteurl']);
       if(count($tmp) == 3) {
          $remotesite[count($remotesite)] = $ct['contestmainsiteurl'];
       }
     }
   }

	for($i = 0; $i < count($remotesite); $i++) {
		$sitedata = explode(' ', $remotesite[$i]);
		if(count($sitedata) < 3) continue;
		$siteurl = $sitedata[0];
		if(strpos($siteurl,'#') !== false) continue;
		LOGError("scoretransfer: found site $siteurl");
		if(substr($siteurl,0,7) != 'http://')
			$siteurl = 'http://' . $siteurl;
		$urldiv='/';
		if(substr($siteurl,strlen($siteurl)-1,1) == '/')
			$urldiv = '';
//		LOGError("url=" .$siteurl . $urldiv . "index.php?getsessionid=1");
		$sess = @file_get_contents($siteurl . $urldiv . "index.php?getsessionid=1");
//		LOGError("sess=$sess pass=" . trim($sitedata[2]) . " hash=" .  myhash(trim($sitedata[2])));
		$user = trim($sitedata[1]);
		$res = myhash( myhash (trim($sitedata[2])) . $sess);
//		LOGError("url=" . $siteurl . $urldiv . "index.php?name=${user}&password=${res}&action=scoretransfer");
		$opts = array(
			'http' => array(
				'method' => 'GET',
				'request_fulluri' => true, 
				'header' => 'Cookie: PHPSESSID=' . $sess
				)
			);
		if($bocaproxy != "")
			$opts['http']['proxy'] = $bocaproxy;
		if($bocapass != "")
			$opts['http']['header'] .= "\r\nProxy-Authorization: Basic " . $bocapass;

		$context = stream_context_create($opts);


		$ok = @file_get_contents($siteurl . $urldiv . "index.php?name=${user}&password=${res}&action=scoretransfer", 0, $context);
//		LOGError("ok=" . $ok);
		if(substr($ok,strlen($ok)-strlen('SCORETRANSFER OK'),strlen('SCORETRANSFER OK')) == 'SCORETRANSFER OK') {
			$res = @file_get_contents($siteurl . $urldiv . "scoretable.php?remote=-42", 0, $context);
			@file_put_contents($privatedir . $ds . 'remotescores' . $ds . 'tmp.zip', $res);
			if(is_readable($privatedir . $ds . 'remotescores' . $ds . 'tmp.zip')) {
				$zip = new ZipArchive;
				if ($zip->open($privatedir . $ds . 'remotescores' . $ds . 'tmp.zip') === true) {
					cleardir($privatedir . $ds . 'remotescores' . $ds . 'tmp');
					@mkdir($privatedir . $ds . 'remotescores' . $ds . 'tmp');	
					$zip->extractTo($privatedir . $ds . 'remotescores' . $ds . 'tmp');
					foreach(glob($privatedir . $ds . 'remotescores' . $ds . 'tmp' . $ds . '*.dat') as $file) {
						@chown($file,"www-data");
						@chmod($file,0660);
						$bn = basename($file);
						if($bn == $localfile)
							@rename($file, $privatedir . $ds . 'remotescores' . $ds . "score_site" . $localsite . "__y.dat");
						else
							@rename($file, $privatedir . $ds . 'remotescores' . $ds . basename($file));
					}
					$zip->close();
					LOGError("scoretransfer: download OK");
				} else {
					LOGError("scoretransfer: download failed (2)");
				}
				cleardir($privatedir . $ds . 'remotescores' . $ds . 'tmp');
				@unlink($privatedir . $ds . 'remotescores' . $ds . 'tmp.zip');
			} else {
				LOGError("scoretransfer: download failed (3)");
			}
		} else {
			LOGError("scoretransfer: download failed (1)");
		}

		if(is_readable($putname)) {
			$data = @file_get_contents($putname);
			$data_url = http_build_query(array('data' => $data,
										 ));

			$opts = array(
				'http' => array(
					'method' => 'POST',
					'request_fulluri' => true, 
					'header' => 'Cookie: PHPSESSID=' . $sess . "\r\nContent-Type: application/x-www-form-urlencoded",
					'content' => $data_url
					)
				);
			if($bocaproxy != "")
				$opts['http']['proxy'] = $bocaproxy;
			if($bocapass != "")
				$opts['http']['header'] .= "\r\nProxy-Authorization: Basic " . $bocapass;

			$context = stream_context_create($opts);
			$s = @file_get_contents($siteurl . $urldiv . "site/putfile.php", 0, $context);
			if(strpos($s,'SCORE UPLOADED OK') !== false)
				LOGError("scoretransfer: upload OK");
			else
				LOGError("scoretransfer: upload failed (" . $s . ")");
		}
		break;
	}
}


function getMainXML($username,$sess,$pass,$pass2) {
	$c = DBConnect();
    if ($c==null) return array(false,"");
	$contest = $_SESSION["usertable"]["contestnumber"];
	$r = DBExec($c, "select * from contesttable where contestnumber=$contest");
	if (DBnLines($r)==0) {
		echo "Unable to find the contest $contest in the database.\n";
		exit;
	}
	$ct = DBRow($r,0);
	$localsite = $ct["contestlocalsite"];
	$mainsite = $ct["contestmainsite"];
	$siteurl = $ct['contestmainsiteurl'] . '/site/get.php';
//	if ($mainsite==$localsite) return array(true,"");
/*
	$r = DBExec($c, "select * from sitetable where sitenumber=".$mainsite." and contestnumber=$contest");
	if (DBnLines($r)==0) {
		echo "Unable to find the main site in the database (site=$mainsite, contest=$contest).\n";
		exit;
	}
	$st = DBRow($r,0);
	$siteurl =  $st["siteip"] . '/site/get.php';
*/
	if(substr($siteurl,0,7) != 'http://')
		$siteurl = 'http://' . $siteurl;
	if($sess == '') {
//		MSGError('session empty');
		$s = file_get_contents($siteurl);
		if($s === false) return array(false,'','');
//		MSGError($s);
		$t = strtok($s," \t");
		while($t !== false && substr($t,0,8) != '<SESSION' && substr($t,0,6) != '<ERROR' && $t != '<OK>' && $t != '<NOTOK>') {
			echo $t . " ";
			$t = strtok(" \t");
		}
		if($t === false) return array(false,'','');
		echo $t . " -->\n";
		if(substr($t,0,8) == '<SESSION') {
			$id = strtok(" \t");
			return array(false,$id,$id);
		}
		else
			return array(false,'','');
	}
	if($pass == $pass2) {
//		MSGError('equal');
		$opts = array(
			'http' => array(
				'method' => 'GET',
				'header' => 'Cookie: PHPSESSID=' . $sess
				)
			);
		$context = stream_context_create($opts);
		$s = file_get_contents($siteurl . '?name='. $username . '&password=' . 
							   $pass . '&check=nocheck', 0, $context);
	} else {
		$data = encryptData(generateXML($contest),myhash($pass2));
		$data_url = http_build_query(array('xml' => $data,
										   'name' => $username,
										   'password' => $pass,
										   'check' => myhash($pass . $pass2)
										 ));
		$opts = array(
			'http' => array(
				'method' => 'POST',
				'header' => 'Cookie: PHPSESSID=' . $sess . "\r\nContent-Type: application/x-www-form-urlencoded",
				'content' => $data_url
				));
		$context = stream_context_create($opts);
		$s = file_get_contents($siteurl, 0, $context);
	}
	if($s === false) return array(false,"",'');
//		MSGError('OPA1: ' . $s);
	$t = strtok($s," \t");
	while($t !== false && substr($t,0,8) != '<SESSION' && substr($t,0,6) != '<ERROR' && $t != '<OK>' && $t != '<NOTOK>') {
		echo $t . " ";
		$t = strtok(" \t");
	}
	if($t === false) return array(false,'','');
	echo $t . " -->\n";
	if(substr($t,0,6) == "<ERROR") {
		$id = strtok(" \t");
		if($id === false) return array(false,'','');
		return array(false,$id,"");
	}
	if(substr($t,0,8) == "<SESSION") {
		$id = strtok(" \t");
		if($id === false) return array(false,'','');
		$idextra = strtok(" \t");
		if($idextra === false) return array(false,'','');
//		MSGError("id=$id  idextra=$idextra");
		return array(false,$id,$idextra);
	}
	$id = strtok(" \t");
	if($id === false) return array(false,'','');
//MSGError('OPA2: ' . $s);
	if($pass2 != '')
		$s = decryptData($id,myhash($pass2));
	if(strtoupper(substr($s,0,5)) != "<XML>") {
		return array(false,'',$t);
	}
	return array(true,$s,$t);
}

function importFromXML($ar,$acr,$contest=0,$localsite=0) {
	$data = implode("",explode("\n",$ar));
	$parser = xml_parser_create();
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 1);
	xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
	xml_parse_into_struct($parser, $data, $values, $tags);
	xml_parser_free($parser);
//	print_r($tags);
//	print_r($values);

	$orderkeys=array('CONTESTREC','SITETIME','SITEREC','USERREC','ANSWERREC','LANGUAGEREC','PROBLEMREC','CLARREC','RUNREC','TASKREC');
	$norderkeys=10;
	$nc=0;
	unset($sitetime);
	unset($nsitetime);
	$conn = DBConnect();
    if ($conn==null) return false;
//	DBExec($conn,"begin work","importFromXML(begin)");
//	DBExec($conn,"lock","importFromXML(lock)");
	if($contest != 0) {
		$r = DBExec($conn, "select * from contesttable where contestnumber=$contest");
		if (DBnLines($r)==0) {
			echo "Unable to find the contest $contest in the database.\n";
			DBExec($conn,"rollback work");
			return false;
		}
		$ct = DBRow($r,0);
		if($localsite==0)
			$localsite = $ct["contestlocalsite"];
	} else if($localsite==0) $localsite=1;
//	$mainsite = $ct["contestmainsite"];
	DBClose($conn); 
	$conn=null;

	for($keyindex=0; $keyindex < $norderkeys; $keyindex++) {
 	  foreach($tags as $key=>$val) {
		if($values[$val[0]]['type'] != 'open') continue;
		if($key == "XML") continue;
		if($key != $orderkeys[$keyindex]) continue;
		if(isset($acr[$key]))
			$ac = $acr[$key];
		else
			continue;
		foreach($val as $k=>$v) {
			if($values[$v]['type'] != 'open') continue;
			if(count($val) > $k+1) {
				$param = array();
				if(isset($ac['site'])) {			
					for($i=$v; $i < $val[$k+1]; $i++) {
						$p  = strtolower($values[$i]["tag"]);
						if($p=='site') {
							if($values[$i]["type"]=="complete" && isset($values[$i]["value"])) {
								$tmp = sanitizeText(trim(implode('',explode('\n',$values[$i]["value"]))));
								if(is_array($ac['site']) && in_array($tmp,$ac['site'])) {									
									$param['site'] = $tmp;
								} else {
									if($ac['site']==-1 || ($ac['site']==-2 && $tmp==$localsite) || ($ac['site']==-3 && $tmp!=$localsite) ||
									   ($ac['site']>0 && $ac['site']==$tmp))
										$param['site'] = $tmp;
								}							
							}
						}
					}
				}
				for($i=$v; $i < $val[$k+1]; $i++) {
					$p  = strtolower($values[$i]["tag"]);
					if(isset($ac[$p]) && $p != 'site') {
						if($values[$i]["type"]=="complete" && isset($values[$i]["value"])) {
							if(is_string($ac[$p])) $param[$p] = $ac[$p];
							else {
								$tmp = sanitizeText(trim(implode('',explode('\n',$values[$i]["value"]))));
								if(is_array($ac[$p]) && in_array($tmp,$ac[$p])) {									
									$param[$p] = $tmp;
								} else {
									if($ac[$p]==-1 || ($ac[$p]==-2 && $tmp==$localsite) || ($ac[$p]==-3 && $tmp!=$localsite) ||
									   ($ac[$p]==0 && isset($param['site'])) || ($ac[$p]>0 && $ac[$p]==$tmp))
										$param[$p] = $tmp;
								}
							}
						}
					}
				}
//		  echo "\nKEY KEY KEY\n";
//		  print_r($key);
//		  echo "\nVAL VAL VAL\n";
//		  print_r($val);
//		  echo "\n";
//				print_r($param);
				$param['contestnumber'] = $contest;
				if($key == "CONTESTREC") {
					$param['number'] = $contest;
					if($contest == 0) {
						$nc=1;
						$contest = DBNewContest($param, $conn);
						if($contest > 0)
							echo "<br><u>Contest $contest created</u> (not active by default)<br>";
						else {
							echo "<br>Error creating contest<br>";
							if($conn != null)
								DBExec($conn,"rollback work");
							return false;
						}
					}
					$param['number'] = $contest;
					$param['contestnumber'] = $contest;
					if(($ret=DBUpdateContest($param, $conn))) {
						if($ret==2)
							echo "<br>Contest $contest updated<br>";
					}
					else {
						if($conn != null)
							DBExec($conn,"rollback work");
						return false;
					}
				}
				if(!isset($param['number']) || count($param) < 2) continue;
				if($key == "SITEREC") {
					if(!DBNewSite($contest, $conn, $param)) {
						if($conn != null)
							DBExec($conn,"rollback work");
						return false;
					}			
					if(($ret=DBUpdateSite($param, $conn))) {
						if($ret==2) {
							echo "Site " . $param["number"] . " updated<br>";
						}
					} else {
						if($conn != null)
							DBExec($conn,"rollback work");
						return false;
					}
					if(isset($nsitetime[$param["number"]])) {
						if(($qtd=DBRenewSiteTime($sitetime[$param["number"]], $conn))) {
							if($qtd==2) {
								echo "Time for site " . $param["number"] . " updated<br>";
							}
						} else {
							if($conn != null)
								DBExec($conn,"rollback work");
							return false;
						}
					}
				}
				if($key == "SITETIME") {
					if(isset($param['site']) && is_numeric($param['site'])) {
						$s = $param['site'];
						if(!isset($nsitetime[$s])) $nsitetime[$s]=0;
						$sitetime[$s][$nsitetime[$s]] = $param;
						$nsitetime[$s]++;
					}
				}
				if($key == "USERREC") {
					if(($ret=DBNewUser($param, $conn))) {
						if($ret==2) {
							echo "User " . $param["number"]."/".$param['site']. " updated<br>";
						}
					} else {
						if($conn != null)
							DBExec($conn,"rollback work");
						return false;
					}
				}
				if($key == "ANSWERREC") {
					if(($ret=DBNewAnswer ($contest, $param, $conn))) {
						if($ret==2) {
							echo "Answer " . $param["number"] . " updated<br>";
						}
					}
					else {
						if($conn != null)
							DBExec($conn,"rollback work");
						return false;
					}
				}
				if($key == "LANGUAGEREC") {
					if(($ret=DBNewLanguage ($contest,$param, $conn))) {
						if($ret==2) {
							echo "Language " . $param['number'] ." updated<br>";
						}
					}
					else {
						if($conn != null)
							DBExec($conn,"rollback work");
						return false;
					}
				}
				if($key == "PROBLEMREC") {
					if(($ret=DBNewProblem ($contest,$param, $conn))) {
						if($ret==2)
							echo "Problem " . $param['number'] ." updated<br>";
					}
					else {
						if($conn != null)
							DBExec($conn,"rollback work");
						return false;
					}
				}
				if($key == "TASKREC") {
					if(($ret=DBNewTask ($param, $conn))) {
						if($ret==2)
							echo "Task " . $param['number']."/".$param['site']." updated<br>";
					}
					else {
						if($conn != null)
							DBExec($conn,"rollback work");
						return false;
					}
				}
				if($key == "CLARREC") {
					if(($ret=DBNewClar ($param, $conn))) {
						if($ret==2)
							echo "Clarification " . $param['number']."/".$param['site'] ." updated<br>";
					}
					else {
						if($conn != null)
							DBExec($conn,"rollback work");
						return false;
					}
				}
				if($key == "RUNREC") {
					if(($ret=DBNewRun ($param, $conn))) {
						if($ret==2)
							echo "Run " . $param['number'] ."/".$param['site']." updated<br>";
					}
					else {
						if($conn != null)
							DBExec($conn,"rollback work");
						return false;
					}
				}
			}
		}
	  }
	}
//	DBExec($conn,"commit work","importFromXML(commit)");
	return true;
}


function generateXML($contest,$localsite=0,$sites=null,$reduced=false) {
	$str = "<XML>\n";
	$ac['CONTESTREC']=array('number'=>'contestnumber', 
							'name'=>'contestname', 
							'startdate'=>'conteststartdate', 
							'duration'=>'contestduration', 
							'lastmileanswer'=>'contestlastmileanswer',
							'lastmilescore'=>'contestlastmilescore',
							'penalty'=>'contestpenalty',
							'maxfilesize'=>'contestmaxfilesize',
							'updatetime'=>'updatetime',
							'mainsite'=>'contestmainsite',
							'mainsiteurl'=>'contestmainsiteurl',
							'keys'=>'contestkeys',
							'unlockkey'=>'contestunlockkey',
							'updatetime'=>'updatetime');
	if($localsite > 0)
		$ac['CONTESTREC']['localsite'] = array($localsite,2);
	$sql['CONTESTREC']="select * from contesttable where contestnumber=$contest";
	$ac['ANSWERREC']=array('number'=>'answernumber',
						'name'=>'runanswer',
						'yes'=>'yes',
						'updatetime'=>'updatetime');
	$sql['ANSWERREC']="select * from answertable where contestnumber=$contest and fake='f'";

	$ac['LANGUAGEREC']=array('number'=>'langnumber',
						  'name'=>'langname',
						  'updatetime'=>'updatetime');
	$sql['LANGUAGEREC']="select * from langtable where contestnumber=$contest";
	
	$ac['PROBLEMREC']=array('number'=>'problemnumber',
						 'name'=>'problemname',
						 'fullname'=>'problemfullname',
						 'basename'=>'problembasefilename',
						 'inputfilename'=>'probleminputfilename',
						 'inputfilepath'=>array('probleminputfile',1),
						 'solfilename'=>'problemsolfilename',
						 'solfilepath'=>array('problemsolfile',1),
						 'descfilename'=>'problemdescfilename',
						 'descfilepath'=>array('problemdescfile',1),
						 'tl'=>'problemtimelimit',
						 'colorname'=>'problemcolorname',
						 'color'=>'problemcolor',
						 'fake'=>'fake',
						 'updatetime'=>'updatetime');
	$sql['PROBLEMREC']="select * from problemtable where contestnumber=$contest and fake='f'";

	$sql['SITEREC']="select * from sitetable where contestnumber=$contest";
	$ac['SITEREC']=array('sitenumber'=>'sitenumber',
					  'site'=>'sitenumber',
					  'number'=>'sitenumber',
					  'sitename'=>'sitename',
					  'siteip'=>'siteip',
					  'siteduration'=>'siteduration',
					  'sitelastmileanswer'=>'sitelastmileanswer',
					  'sitelastmilescore'=>'sitelastmilescore',
					  'sitejudging'=>'sitejudging',
					  'sitetasking'=>'sitetasking',
					  'siteautoend'=>'siteautoend',
					  'siteglobalscore'=>'siteglobalscore',
					  'siteactive'=>'siteactive',
					  'sitescorelevel'=>'sitescorelevel',
					  'sitepermitlogins'=>'sitepermitlogins',
					  'siteautojudge'=>'siteautojudge',
					  'sitenextuser'=>'sitenextuser',
					  'sitenextclar'=>'sitenextclar',
					  'sitenextrun'=>'sitenextrun',
					  'sitenexttask'=>'sitenexttask',
					  'sitemaxtask'=>'sitemaxtask',
					  'sitechiefname'=>'sitechiefname',
					  'updatetime'=>'updatetime');
	$sql['SITETIME']="select * from sitetimetable where contestnumber=$contest";
	$ac['SITETIME']=array('site'=>'sitenumber',
						  'number'=>'sitenumber',
						  'start'=>'sitestartdate',
						  'enddate'=>'siteenddate',
						  'updatetime'=>'updatetime');

	$sql['USERREC']="select * from usertable where contestnumber=$contest";
	$ac['USERREC']=array('site'=>'usersitenumber',
					  'user'=>'usernumber',
					  'number'=>'usernumber',
					  'username'=>'username',
					  'updatetime'=>'updatetime',
					  'usericpcid'=>'usericpcid',
					  'userfull'=>'userfullname',
					  'userdesc'=>'userdesc',
					  'type'=>'usertype',
					  'enabled'=>'userenabled',
					  'multilogin'=>'usermultilogin',
//					  'pass'=>'userpassword',
//					  'usersession'=>'usersession',
					  'userip'=>'userip',
					  'userlastlogin'=>'userlastlogin',
					  'userlastlogout'=>'userlastlogout',
					  'permitip'=>'userpermitip',
					  'updatetime'=>'updatetime');

	if(!$reduced) {
		$sql['CLARREC']="select * from clartable where contestnumber=$contest";
		$ac['CLARREC']=array('site'=>'clarsitenumber',
							 'user'=>'usernumber',
							 'number'=>'clarnumber',
							 'problem'=>'clarproblem',
							 'question'=>'clardata',
							 'clarnumber'=>'clarnumber',
							 'clardate'=>'clardate',
							 'clardatediff'=>'clardatediff',
							 'clardatediffans'=>'clardatediffans',
							 'claranswer'=>'claranswer',
							 'clarstatus'=>'clarstatus',
							 'clarjudge'=>'clarjudge',
							 'clarjudgesite'=>'clarjudgesite',
							 'updatetime'=>'updatetime');

		$sql['RUNREC']="select * from runtable where contestnumber=$contest";
		if(is_array($sites)) {
			$sql['RUNREC'] .= " and (1=0";
			foreach($sites as $k => $v) {
				$sql['RUNREC'] .= " or runsitenumber=$v";
			}
			$sql['RUNREC'] .= ")";
		}
		$ac['RUNREC']=array('site'=>'runsitenumber',
					 'user'=>'usernumber',
					 'number'=>'runnumber',
					 'runnumber'=>'runnumber',
					 'problem'=>'runproblem',
					 'lang'=>'runlangnumber',
					 'filename'=>'runfilename',
					 'filepath'=>array('rundata',1),
					 'rundate'=>'rundate',
					 'rundatediff'=>'rundatediff',
					 'rundatediffans'=>'rundatediffans',
					 'runanswer'=>'runanswer',
					 'runstatus'=>'runstatus',
					 'runjudge'=>'runjudge',
					 'runjudgesite'=>'runjudgesite',
					 'runjudge1'=>'runjudge1',
					 'runjudgesite1'=>'runjudgesite1',
					 'runanswer1'=>'runanswer1',
					 'runjudge2'=>'runjudge2',
					 'runjudgesite2'=>'runjudgesite2',
					 'runanswer2'=>'runanswer2',
					 'autoip'=>'autoip',
					 'autobegindate'=>'autobegindate',
					 'autoenddate'=>'autoenddate',
					 'autoanswer'=>'autoanswer',
					 'autostdout'=>array('autostdout',1),
					 'autostderr'=>array('autostderr',1),
					 'updatetime'=>'updatetime');
		$sql['TASKREC']="select * from tasktable where contestnumber=$contest";
		if(is_array($sites)) {
			$sql['TASKREC'] .= " and (1=0";
			foreach($sites as $k => $v) {
				$sql['TASKREC'] .= " or sitenumber=$v";
			}
			$sql['TASKREC'] .= ")";
		}
		$ac['TASKREC']=array(
			'site'=>'sitenumber',
			'user'=>'usernumber',
			'desc'=>'taskdesc',
			'number'=>'tasknumber',
			'tasknumber'=>'tasknumber',
			'color'=>'color',
			'colorname'=>'colorname',
			'updatetime'=>'updatetime',
			'filename'=>'taskfilename',
			'filepath'=>array('taskdata',1),
			'sys'=>'tasksystem',
			'status'=>'taskstatus',
			'taskdate'=>'taskdate',
			'taskdatediff'=>'taskdatediff',
			'taskdatediffans'=>'taskdatediffans',
			'taskstaffnumber'=>'taskstaffnumber',
			'taskstaffsite'=>'taskstaffsite');
	}
	$c = DBConnect();
    if ($c==null) return null;
	DBExec($c, "begin work");
	foreach($ac as $kk => $vv) {
		$r = DBExec ($c, $sql[$kk], "generateXML($kk)");
		$n = DBnLines ($r);
		for($i=0; $i<$n; $i++) {
			$atual = DBRow($r,$i);
			$str .= "<" . $kk . ">\n";
			foreach($vv as $key => $val) {
				if(is_array($val)) {
					if(is_array($val[0])) {
						if(!isset($atual['site']) || in_array($atual['site'],$val[0]))
							if(isset($atual[$val[1]])) 
								$str .= "  <" . $key . ">" . $atual[$val[1]] . "</" . $key . ">\n";
					}
					if($val[1]==2) {
						$str .= "  <" . $key . ">" . $val[0] . "</" . $key . ">\n";
					}
					if($val[1]==1) {
						if(isset($atual[$val[0]]) && $atual[$val[0]]!='') {
							if (($lo = DB_lo_open ($c, $atual[$val[0]], "r")) !== false) {
								$str .= "  <" . $key . ">base64:" . base64_encode(DB_lo_read($contest,$lo)) . "</" . $key . ">\n";
								DB_lo_close($lo);
							}
						}
					}
				} else {
					if(isset($atual[$val])) 
						$str .= "  <" . $key . ">" . $atual[$val] . "</" . $key . ">\n";
				}
			}
			$str .= "</" . $kk . ">\n";
		}
	}
	$str .= "</XML>\n";
	DBExec($c,"commit work","generateXML(commit)");
	return $str;
}
?>
