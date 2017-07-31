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

  $siteurl = $sitedata[0];
  LOGInfo("getMainXML: site $siteurl");
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
  $ti = mytime();
  //		LOGError("ok=" . $ok);
  if(substr($ok,strlen($ok)-strlen('TRANSFER OK'),strlen('TRANSFER OK')) == 'TRANSFER OK') {

    $data = encryptData(generateSiteXML($contest, $localsite, $updatetime-30),myhash(trim($sitedata[2])));
    
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
      LOGInfo("xmltransfer: OK");
    else
      LOGError("xmltransfer: failed (" . $s . ")");

    $s = substr($s, strpos($s, "\n") + 1);
    //    LOGError("string: " . substr($s,0,50));
    $s = decryptData($s,myhash (trim($sitedata[2])),'xml from main not ok');
    if(strtoupper(substr($s,0,5)) != "<XML>") {
      return false;
    }
    if(importFromXML($s, $contest, $localsite, false, 1+$ct['updatetime'])) {
      $str = $sitedata[0] . ' ' . $sitedata[1] . ' ' . $sitedata[2] . ' ' . $ti;
      $ti = 2+$ct['updatetime'];
      $param = array('contestnumber' => $contest, 'mainsiteurl' => $str, 'updatetime' => $ti);
      DBUpdateContest ($param, null);
      return true;
    } else {
      LOGError("error importing xml");
    }
  } else {
    LOGError("xmltransfer: failed (" . $ok . ")");
  }
  return false;
}

function importFromXML($ar,$contest,$site,$tomain=false,$uptime=0) {
  LOGInfo("importFromXML: contest $contest site $site tomain $tomain");
  $data = implode("",explode("\n",$ar));
  $parser = xml_parser_create('');
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
    LOGError("importFromXML: Unable to find the contest $contest in the database.");
    //    DBExec($conn,"rollback work");
    return false;
  }
  $ct = DBRow($r,0);

  DBClose($conn); 
  $conn=null;
  $firsttimetime=true;
  $tables = array('contesttable','answertable','langtable','problemtable','sitetable','sitetimetable','usertable','clartable','runtable','tasktable');

  foreach($tables as $table) {
    foreach($tags as $key=>$val) {
      if($values[$val[0]]['type'] != 'open') continue;
      if($key == "XML") continue;
      if($key != strtoupper($table)) continue;

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

	  $param['contestnumber'] = $contest;
	  $param['contest'] = $contest;
	  if(count($param) < 2) continue;
	  unset($param['number']);

	  if(!$tomain && $table == "contesttable") {
	    if($uptime > 0) $param['updatetime']=$uptime;
	    if(($ret=DBUpdateContest ($param, $conn))) {
	      if($ret==2) {
		LOGInfo("importFromXML: Contest " . $param["contestnumber"] . " updated");
	      }
	    }
	    else {
	      LOGError("importFromXML: error to update $table ". $param["contestnumber"]);
	      if($conn != null)
		DBExec($conn,"rollback work");
	      return false;
	    }
	  }

	  
	  //	  LOGInfo("importFromXML: $key params " .print_r( $param,true));
	  if(!$tomain && $table == "answertable") {
	    if(($ret=DBNewAnswer ($contest, $param, $conn))) {
	      if($ret==2) {
		LOGInfo("importFromXML: Answer " . $param["answernumber"] . " updated");
	      }
	    }
	    else {
	      LOGError("importFromXML: error to update $table ". $param["answernumber"]);
	      if($conn != null)
		DBExec($conn,"rollback work");
	      return false;
	    }
	  }
	  if(!$tomain && $table == "langtable") {
	    if(($ret=DBNewLanguage ($contest,$param, $conn))) {
	      if($ret==2) {
		LOGInfo("importFromXML: Language " . $param['langnumber'] ." updated");
	      }
	    }
	    else {
	      LOGError("importFromXML: error to update $table ". $param['langnumber']);
	      if($conn != null)
		DBExec($conn,"rollback work");
	      return false;
	    }
	  }
	  if(!$tomain && $table == "problemtable") {
	    if(($ret=DBNewProblem ($contest,$param, $conn))) {
	      if($ret==2)
		LOGInfo("importFromXML: Problem " . $param['problemnumber'] ." updated");
	    }
	    else {
	      LOGError("importFromXML: error to update $table " . $param['problemnumber']);
	      if($conn != null)
		DBExec($conn,"rollback work");
	      return false;
	    }
	  }
	  if(isset($param['usersitenumber']) && !isset($param['sitenumber'])) $param['sitenumber']=$param['usersitenumber'];              
	  if(!isset($param['sitenumber']) || $param['sitenumber'] != $site) continue;
	  
	  if($tomain && $table == "sitetable") {
	    if(!DBNewSite($contest, $conn, $param)) {
	      LOGError("importFromXML: error to update $table");
	      if($conn != null)
		DBExec($conn,"rollback work");
	      return false;
	    }
	    if(($ret=DBUpdateSite($param, $conn))) {
	      if($ret==2) {
		LOGInfo("importFromXML: Site " . $param["sitenumber"] . " updated");
	      }
	    } else {
	      LOGError("importFromXML: error to update $table ". $param["sitenumber"]);
	      if($conn != null)
		DBExec($conn,"rollback work");
	      return false;
	    }
	  }
	  if($tomain && $table == "sitetimetable") {
	    if(!DBUpdateSiteTime($contest, $param, $firsttimetime, $conn)) {
	      LOGError("importFromXML: error to update $table");
	      if($conn != null)
		DBExec($conn,"rollback work");
	      return false;
	    } else {
	      LOGInfo("importFromXML: SiteTime updated");
	    }
	    $firsttimetime=false;
	  }
	  if($table == "usertable") {
	    if(($ret=DBNewUser($param, $conn))) {
	      if($ret==2) {
		LOGInfo("importFromXML: User " . $param["usernumber"]."/".$param['sitenumber']. " updated");
	      }
	    } else {
	      LOGError("importFromXML: error to update $table ". $param["usernumber"]."/".$param['sitenumber']);
	      if($conn != null)
		DBExec($conn,"rollback work");
	      return false;
	    }
	  }
	  if($table == "tasktable") {
	    if(($ret=DBNewTask ($param, $conn))) {
	      if($ret==2)
		LOGInfo("importFromXML: Task " . $param['tasknumber']."/".$param['sitenumber']." updated");
	    }
	    else {
	      LOGError("importFromXML: error to update $table " . $param['tasknumber']."/".$param['sitenumber']);
	      if($conn != null)
		DBExec($conn,"rollback work");
	      return false;
	    }
	  }
	  if($table == "clartable") {
	    if(($ret=DBNewClar ($param, $conn))) {
	      if($ret==2)
		LOGInfo("importFromXML: Clarification " . $param['clarnumber']."/".$param['sitenumber'] ." updated");
	    }
	    else {
	      LOGError("importFromXML: error to update $table ". $param['clarnumber']."/".$param['sitenumber']);
	      if($conn != null)
		DBExec($conn,"rollback work");
	      return false;
	    }
	  }
	  if($table == "runtable") {
	    if(($ret=DBNewRun ($param, $conn))) {
	      if($ret==2)
		LOGInfo("importFromXML: Run " . $param['runnumber'] ."/".$param['sitenumber']." updated");
	    }
	    else {
	      LOGError("importFromXML: error to update $table ". $param['runnumber'] ."/".$param['sitenumber']);
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

function genSQLs($contest, $site, $updatetime) {
  $sql = array();
  $sql['contesttable']="select contestnumber, contestname, conteststartdate, contestduration, contestlastmileanswer," .
    "contestlastmilescore, contestpenalty, contestmaxfilesize, contestmainsite, contestkeys " .
    "from contesttable where contestnumber=$contest"; // and updatetime >= $updatetime";
  $sql['sitetable']="select * from sitetable where contestnumber=$contest and sitenumber=$site and updatetime >= $updatetime";
  $sql['answertable']="select * from answertable where contestnumber=$contest and fake='f' and updatetime >= $updatetime";
  $sql['langtable']="select * from langtable where contestnumber=$contest and updatetime >= $updatetime";
  $sql['problemtable']="select * from problemtable where contestnumber=$contest and fake='f' and updatetime >= $updatetime";
  $sql['sitetimetable']="select * from sitetimetable where contestnumber=$contest and sitenumber=$site and updatetime >= $updatetime";
  $sql['usertable']="select * from usertable where contestnumber=$contest and usersitenumber=$site and updatetime >= $updatetime";
  $sql['clartable']="select * from clartable where contestnumber=$contest and clarsitenumber=$site and updatetime >= $updatetime";
  $sql['runtable']="select * from runtable where contestnumber=$contest and runsitenumber=$site and updatetime >= $updatetime";
  $sql['tasktable']="select * from tasktable where contestnumber=$contest and sitenumber=$site and updatetime >= $updatetime";
  return $sql;
}

function generateSiteXML($contest,$site,$updatetime) {
  $sql = genSQLs($contest, $site, $updatetime);
  $c = DBConnect();
  $str = "<XML>\n";
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
	    }
	    $str .= "</" . $kk . ">\n";
	  }
	}
	$str .= "</XML>\n";
	DBExec($c,"commit work","generateXML(commit)");
	LOGInfo("xml data generated for contest $contest site $site at time $updatetime");
	return $str;
}

