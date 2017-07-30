<?php
////////////////////////////////////////////////////////////////////////////////
//BOCA Online Contest Administrator
//    Copyright (C) 2003-2016 by BOCA Development Team (bocasystem@gmail.com)
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
// Last modified 25/jul/2017 by cassio@ime.usp.br

function scoretransfer($putname, $localsite) {
	$ds = DIRECTORY_SEPARATOR;
	if($ds=="") $ds = "/";

	if(is_readable('/etc/boca.conf')) {
		$pif=parse_ini_file('/etc/boca.conf');
		$bocaproxy = @trim($pif['proxy']);
		if($bocaproxy != "" && substr($bocaproxy,0,6) != 'tcp://')
			$bocaproxy = 'tcp://' . $bocaproxy;
		$bocaproxylogin = @trim($pif['proxylogin']);
		$bocaproxypass = @trim($pif['proxypassword']);
		if($bocaproxylogin != "")
			$bocaproxypass = base64_encode($bocaproxylogin . ":" . $bocaproxypass);
	} else {
		$bocaproxy = "";
		$bocaproxypass = "";
	}

	$privatedir = $_SESSION['locr'] . $ds . "private";
	if(!is_readable($privatedir . $ds . 'remotescores' . $ds . "otherservers")) return;
$superlfile = $privatedir . $ds . "score_localsite_" . $localsite . "_x.dat";
	$localfile = "score_site" . $localsite . "_" . $localsite . "_x.dat";
	$remotesite = @file($privatedir . $ds . 'remotescores' . $ds . "otherservers");

   $contest=$_SESSION["usertable"]["contestnumber"];
   if($contest != '' && ($ct = DBContestInfo($contest)) != null) {
     if(trim($ct['contestmainsiteurl']) != '') {
       $tmp = explode(' ',$ct['contestmainsiteurl']);
       if(count($tmp) >= 3) {
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
//		LOGError("url=" . $siteurl . $urldiv . "index.php?name=${user}&password=${res}&action=transfer");
		$opts = array(
			'http' => array(
				'method' => 'GET',
				'request_fulluri' => true, 
				'header' => 'Cookie: PHPSESSID=' . $sess
				)
			);
		if($bocaproxy != "")
			$opts['http']['proxy'] = $bocaproxy;
		if($bocaproxypass != "")
			$opts['http']['header'] .= "\r\nProxy-Authorization: Basic " . $bocaproxypass;

		$context = stream_context_create($opts);


		$ok = @file_get_contents($siteurl . $urldiv . "index.php?name=${user}&password=${res}&action=transfer", 0, $context);
//		LOGError("ok=" . $ok);
		if(substr($ok,strlen($ok)-strlen('TRANSFER OK'),strlen('TRANSFER OK')) == 'TRANSFER OK') {
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
			if($bocaproxypass != "")
				$opts['http']['header'] .= "\r\nProxy-Authorization: Basic " . $bocaproxypass;

			$context = stream_context_create($opts);
			$s = @file_get_contents($siteurl . $urldiv . "site/putfile.php", 0, $context);
			if(strpos($s,'SCORE UPLOADED OK') !== false)
				LOGError("scoretransfer: upload OK");
			else
				LOGError("scoretransfer: upload failed (" . $s . ")");
		}
                if(is_readable($superlfile)) {
                        $data = @file_get_contents($superlfile);
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
                        if($bocaproxypass != "")
                                $opts['http']['header'] .= "\r\nProxy-Authorization: Basic " . $bocaproxypass;

                        $context = stream_context_create($opts);
                        $s = @file_get_contents($siteurl . $urldiv . "site/putfilesuper.php", 0, $context);
                        if(strpos($s,'SCORE UPLOADED OK') !== false)
                                LOGError("scoretransfer: upload full OK");
                        else
                                LOGError("scoretransfer: upload full failed (" . $s . ")");
                }

		break;
	}
}


function getMainXML() {
  $ds = DIRECTORY_SEPARATOR;
  if($ds=="") $ds = "/";
  
  if(is_readable('/etc/boca.conf')) {
    $pif=parse_ini_file('/etc/boca.conf');
    $bocaproxy = @trim($pif['proxy']);
    if($bocaproxy != "" && substr($bocaproxy,0,6) != 'tcp://')
      $bocaproxy = 'tcp://' . $bocaproxy;
    $bocaproxylogin = @trim($pif['proxylogin']);
    $bocaproxypass = @trim($pif['proxypassword']);
    if($bocaproxylogin != "")
      $bocaproxypass = base64_encode($bocaproxylogin . ":" . $bocaproxypass);
  } else {
    $bocaproxy = "";
    $bocaproxypass = "";
  }
  
  $privatedir = $_SESSION['locr'] . $ds . "private";
  
  $c = DBConnect();
  if ($c==null) return false;
  $contest = $_SESSION["usertable"]["contestnumber"];
  $r = DBExec($c, "select * from contesttable where contestnumber=$contest");
  if (DBnLines($r)==0) {
    echo "Unable to find the contest $contest in the database.\n";
    exit;
  }
  $ct = DBRow($r,0);
  $localsite = $ct["contestlocalsite"];
  $mainsite = $ct["contestmainsite"];
  
  if(trim($ct['contestmainsiteurl']) == '') {
    return false;
  }
  $sitedata = explode(' ',$ct['contestmainsiteurl']);
  if(count($sitedata) < 3) {
    return false;
  }
  if(count($sitedata) == 3) {
    $updatetime=0;
  } else
    $updatetime=trim($sitedata[3]);
  $ti = time();

  $siteurl = $sitedata[0];
  LOGError("getMainXML: site $siteurl");
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
  $opts = array(
		'http' => array(
				'method' => 'GET',
				'request_fulluri' => true, 
				'header' => 'Cookie: PHPSESSID=' . $sess
				)
		);
  if($bocaproxy != "")
    $opts['http']['proxy'] = $bocaproxy;
  if($bocaproxypass != "")
    $opts['http']['header'] .= "\r\nProxy-Authorization: Basic " . $bocaproxypass;
  
  $context = stream_context_create($opts);
  $ok = @file_get_contents($siteurl . $urldiv . "index.php?name=${user}&password=${res}&action=transfer", 0, $context);
  //		LOGError("ok=" . $ok);
  if(substr($ok,strlen($ok)-strlen('TRANSFER OK'),strlen('TRANSFER OK')) == 'TRANSFER OK') {

    $data = encryptData(generateSiteXML($contest, $localsite, $updatetime-30),myhash (trim($sitedata[2])));
    
    $data_url = http_build_query(array('xml' => $data, 'updatetime' => ($updatetime-30)
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
    if($bocaproxypass != "")
      $opts['http']['header'] .= "\r\nProxy-Authorization: Basic " . $bocaproxypass;

    $context = stream_context_create($opts);
    $s = @file_get_contents($siteurl . $urldiv . "site/getsite.php", 0, $context);
    if(strpos($s,'<OK>') !== false)
      LOGError("xmltransfer: OK");
    else
      LOGError("xmltransfer: failed (" . $s . ")");
  } else {
    LOGError("xmltransfer: failed (" . $ok . ")");
  }
    
  $s = decryptData($s,myhash (trim($sitedata[2])));
  if(strtoupper(substr($s,0,5)) != "<XML>") {
    return false;
  }
  importFromXML($s, $contest, $localsite);
  $str = $sitedata[0] . ' ' . $sitedata[1] . ' ' . $sitedata[2] . ' ' . $ti;
  $param = array('contestnumber' => $contest, 'mainsiteurl' => $str, 'updatetime' => $ct['updatetime']);
  DBUpdateContest ($param, $c);
  return true;
}

function importFromXML($ar,$contest,$site,$tomain=false) {
  $data = implode("",explode("\n",$ar));
  $parser = xml_parser_create();
  xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 1);
  xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
  xml_parse_into_struct($parser, $data, $values, $tags);
  xml_parser_free($parser);
//	print_r($tags);
//	print_r($values);
  $conn = DBConnect();
  if ($conn==null) return false;
  DBExec($conn,"begin work","importFromXML(begin)");
//	DBExec($conn,"lock","importFromXML(lock)");
  $r = DBExec($conn, "select * from contesttable where contestnumber=$contest");
  if (DBnLines($r)==0) {
    echo "Unable to find the contest $contest in the database.\n";
    //    DBExec($conn,"rollback work");
    return false;
  }
  $ct = DBRow($r,0);

  DBClose($conn); 
  $conn=null;

  $tables = array('answertable','langtable','problemtable','sitetable','usertable','clartable','runtable','tasktable');

  foreach($tables as $table) {
    foreach($tags as $key=>$val) {
      if($values[$val[0]]['type'] != 'open') continue;
      if($key == "XML") continue;
      if($key != $table) continue;

      foreach($val as $k=>$v) {
	if($values[$v]['type'] != 'open') continue;
	if(count($val) > $k+1) {
	  $param = array();
	  for($i=$v; $i < $val[$k+1]; $i++) {
	    $p  = strtolower($values[$i]["tag"]);
	    if($values[$i]["type"]=="complete" && isset($values[$i]["value"])) {
	      $tmp = sanitizeText(trim(implode('',explode('\n',$values[$i]["value"]))));
	      $param[$p] = $tmp;
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
      if(count($param) < 2) continue;
      unset($param['number']);

      if(!$tomain && $key == "answertable") {
	if(($ret=DBNewAnswer ($contest, $param, $conn))) {
	  if($ret==2) {
	    echo "Answer " . $param["answernumber"] . " updated<br>";
	  }
	}
	else {
	  if($conn != null)
	    DBExec($conn,"rollback work");
	  return false;
	}
      }
      if(!$tomain && $key == "langtable") {
	if(($ret=DBNewLanguage ($contest,$param, $conn))) {
	  if($ret==2) {
	    echo "Language " . $param['langnumber'] ." updated<br>";
	  }
	}
	else {
	  if($conn != null)
	    DBExec($conn,"rollback work");
	  return false;
	}
      }
      if(!$tomain && $key == "problemtable") {
	if(($ret=DBNewProblem ($contest,$param, $conn))) {
	  if($ret==2)
	    echo "Problem " . $param['problemnumber'] ." updated<br>";
	}
	else {
	  if($conn != null)
	    DBExec($conn,"rollback work");
	  return false;
	}
      }
      
      if(!isset($param['sitenumber']) || $param['sitenumber'] != $site) continue;
      
      if($tomain && $key == "sitetable") {
	if(!DBNewSite($contest, $conn, $param)) {
	  if($conn != null)
	    DBExec($conn,"rollback work");
	  return false;
	}
	if(($ret=DBUpdateSite($param, $conn))) {
	  if($ret==2) {
	    echo "Site " . $param["sitenumber"] . " updated<br>";
	  }
	} else {
	  if($conn != null)
	    DBExec($conn,"rollback work");
	  return false;
	  }
      }
      if($key == "usertable") {
	if(($ret=DBNewUser($param, $conn))) {
	  if($ret==2) {
	    echo "User " . $param["usernumber"]."/".$param['sitenumber']. " updated<br>";
	  }
	} else {
	  if($conn != null)
	    DBExec($conn,"rollback work");
	  return false;
	}
      }
      if($key == "tasktable") {
	if(($ret=DBNewTask ($param, $conn))) {
	  if($ret==2)
	    echo "Task " . $param['tasknumber']."/".$param['sitenumber']." updated<br>";
	}
	else {
	  if($conn != null)
	    DBExec($conn,"rollback work");
	  return false;
	}
      }
      if($key == "clartable") {
	if(($ret=DBNewClar ($param, $conn))) {
	  if($ret==2)
	    echo "Clarification " . $param['clarnumber']."/".$param['sitenumber'] ." updated<br>";
	}
	else {
	  if($conn != null)
	    DBExec($conn,"rollback work");
	  return false;
	}
      }
      if($key == "runtable") {
	if(($ret=DBNewRun ($param, $conn))) {
	  if($ret==2)
	    echo "Run " . $param['runnumber'] ."/".$param['sitenumber']." updated<br>";
	}
	else {
	  if($conn != null)
	    DBExec($conn,"rollback work");
	  return false;
	}
      }
    }
  }
  //	DBExec($conn,"commit work","importFromXML(commit)");
  return true;
}

function genSQLs($contest, $site, $updatetime) {
  $sql = array();
  //  $sql['contesttable']="select * from contesttable where contestnumber=$contest and updatetime >= $updatetime";
  $sql['sitetable']="select * from sitetable where contestnumber=$contest and sitenumber=$site and updatetime >= $updatetime";
  $sql['answertable']="select * from answertable where contestnumber=$contest and fake='f' and updatetime >= $updatetime";
  $sql['langtable']="select * from langtable where contestnumber=$contest and updatetime >= $updatetime";
  $sql['problemtable']="select * from problemtable where contestnumber=$contest and fake='f' and updatetime >= $updatetime";
  //  $sql['sitetimetable']="select * from sitetimetable where contestnumber=$contest and sitenumber=$site and updatetime >= $updatetime";
  $sql['usertable']="select * from usertable where contestnumber=$contest and usersitenumber=$site and updatetime >= $updatetime";
  $sql['clartable']="select * from clartable where contestnumber=$contest and clarsitenumber=$site and updatetime >= $updatetime";
  $sql['runtable']="select * from runtable where contestnumber=$contest and runsitenumber=$site and updatetime >= $updatetime";
  $sql['tasktable']="select * from tasktable where contestnumber=$contest and sitenumber=$site and updatetime >= $updatetime";
  return $sql;
}

function generateSiteXML($contest,$site,$updatetime) {
  $sql = genSQLs($contest, $site, $updatetime);
  $c = DBConnect();
  $str = "";
  if ($c==null) return null;
  DBExec($c, "begin work");
  foreach($sql as $kk => $vv) {
	  $meta = pg_meta_data($c, $kk);
	  if (!is_array($meta)) return null;
	  $r = DBExec ($c, $vv, "generateSiteXML($kk)");
	  $n = DBnLines ($r);
	  for($i=0; $i<$n; $i++) {
	    $atual = DBRow($r,$i);
	    $str .= "<" . $kk . ">\n";
	    foreach($atual as $key => $val) {
	      if($meta[$key]['type'] == 'oid' && $val != '') {
		if (($lo = DB_lo_open ($c, $val, "r")) !== false) {
		  $str .= "  <" . $key . ">base64:" . base64_encode(DB_lo_read($contest,$lo)) . "</" . $key . ">\n";
		  DB_lo_close($lo);
		} else {
		  LOGError("large object ($key,$val) not readable");
		}
	      } else {
		$str .= "  <" . $key . ">" . $val . "</" . $key . ">\n";
	      }
	      $str .= "</" . $kk . ">\n";
	    }
	  }
	}
	$str .= "</XML>\n";
	DBExec($c,"commit work","generateXML(commit)");
	LOGError("xml data generated for contest $contest site $site at time $updatetime");
	return $str;
}

/*
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
*/
?>
