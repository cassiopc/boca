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

function makeurlhttps($siteurl) {
  if(substr($siteurl,0,7) == 'http://')
    $siteurl = substr($siteurl,7);
  if(substr($siteurl,0,8) != 'https://')
    $siteurl = 'https://' . $siteurl;
  if(substr($siteurl,strlen($siteurl)-1,1) != '/')
    $siteurl .= '/';
  return $siteurl;
}

function scoretransfer($putname, $localsite, $timeo=60) {
  $ds = DIRECTORY_SEPARATOR;
  if($ds=="") $ds = "/";
  $logstr='';

  if(is_readable('/etc/boca.conf')) {
    $pif=parse_ini_file('/etc/boca.conf');
    $bocaproxy = @trim($pif['proxy']);
    if($bocaproxy != "" && substr($bocaproxy,0,6) != 'tcp://') {
      $bocaproxy = 'tcp://' . $bocaproxy;
      $logstr .= "proxy configuration found\n";
    }
    $bocaproxylogin = @trim($pif['proxylogin']);
    $bocaproxypass = @trim($pif['proxypassword']);
    if($bocaproxylogin != "") {
      $logstr .= "proxy authentication found\n";
      $bocaproxypass = base64_encode($bocaproxylogin . ":" . $bocaproxypass);
    }
  } else {
    $bocaproxy = "";
    $bocaproxypass = "";
  }

  $privatedir = $_SESSION['locr'] . $ds . "private";

  // as of aug/2017, let's only do transfers with the mainsite as specified at the contestmainsiteurl
  //  if(!is_readable($privatedir . $ds . 'remotescores' . $ds . "otherservers")) return;
  //  $remotesite = @file($privatedir . $ds . 'remotescores' . $ds . "otherservers");
  $remotesite = array();
  
  $superlfile = $privatedir . $ds . "score_localsite_" . $localsite . "_x.dat";
  $localfile = "score_site" . $localsite . "_" . $localsite . "_x.dat";
  
  $contest=$_SESSION["usertable"]["contestnumber"];
  if($contest != '' && ($ct = DBContestInfo($contest)) != null) {
    if(trim($ct['contestmainsiteurl']) != '') {
      $tmp = explode(' ',$ct['contestmainsiteurl']);
      if(count($tmp) >= 3) {
	$remotesite[count($remotesite)] = $ct['contestmainsiteurl'];
      } else $logstr .= "Main site URL is invalid\n";
    } else $logstr .= "Main site URL not defined\n";
  } else {
    $logstr .= "Error to load contest data\n";
  }

  for($i = 0; $i < count($remotesite); $i++) {
    $sitedata = explode(' ', $remotesite[$i]);
    if(count($sitedata) < 3) continue;
    $siteurl = $sitedata[0];
    if(strpos($siteurl,'#') !== false) continue;
    LOGError("scoretransfer: found site $siteurl");
    $siteurl = makeurlhttps($siteurl);
    $logstr .= "Found site $siteurl to share info\n";
    //		LOGError("url=" .$siteurl . "index.php?getsessionid=1");
    $opts = array();
    $opts['http']['timeout'] = $timeo;
    $opts['ssl'] = array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true);
    $opts['https'] = array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true);
    $context = stream_context_create($opts);	  
    if(($sess = @file_get_contents($siteurl . "index.php?getsessionid=1", 0, $context))===false) {
      LOGError("scoretransfer: timeout at get session id for $siteurl");
      $logstr .= "timeout at get-session-id for $siteurl\n";
      continue;
    }
    //		LOGError("sess=$sess pass=" . trim($sitedata[2]) . " hash=" .  myhash(trim($sitedata[2])));
    $user = trim($sitedata[1]);
    $res = myhash( myhash (trim($sitedata[2])) . $sess);
    //		LOGError("url=" . $siteurl . "index.php?name=${user}&password=${res}&action=transfer");
    $opts = array(
		  'http' => array(
				  'method' => 'GET',
				  'request_fulluri' => true, 
				  'header' => 'Cookie: PHPSESSID=' . $sess . "\r\n"
				  )
		  );
    if($bocaproxy != "")
      $opts['http']['proxy'] = $bocaproxy;
    if($bocaproxypass != "")
      $opts['http']['header'] .= "Proxy-Authorization: Basic " . $bocaproxypass . "\r\n";
    $opts['http']['header'] .= "Connection: close\r\n";
    $opts['http']['timeout'] = $timeo;
    $opts['ssl'] = array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true);
    $opts['https'] = array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true);
    $context = stream_context_create($opts);
    try {
      $ok = @file_get_contents($siteurl . "index.php?name=${user}&password=${res}&action=transfer", 0, $context);
    } catch(Exception $e) {
      $ok = false;
    }
    if($ok===false) {
      LOGError("scoretransfer: timeout at login for $siteurl");
      $logstr .= "timeout at login for $siteurl\n";
      continue;
    }
    //		LOGError("ok=" . $ok);
    if(substr($ok,strlen($ok)-strlen('TRANSFER OK'),strlen('TRANSFER OK')) == 'TRANSFER OK') {
      if(($res = @file_get_contents($siteurl . "scoretable.php?remote=-42", 0, $context))===false) {
	LOGError("scoretransfer: timeout at get score for $siteurl");
	$logstr .= "timeout at get-score for $siteurl\n";
	continue;
      }
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
	      @rename($file, $privatedir . $ds . 'remotescores' . $ds . $bn);
	  }
	  $zip->close();
	  LOGInfo("scoretransfer: download OK");
	  $logstr .= "download OK from $siteurl\n";
	} else {
	  $logstr .= "reading failed from $siteurl (zip open error)\n";
	  LOGError("scoretransfer: download failed (2)");
	}
	cleardir($privatedir . $ds . 'remotescores' . $ds . 'tmp');
	@unlink($privatedir . $ds . 'remotescores' . $ds . 'tmp.zip');
      } else {
	LOGError("scoretransfer: download failed (3)");
	$logstr .= "download failed from $siteurl (file error)\n";
      }
    } else {
      LOGError("scoretransfer: download failed (1)");
      $logstr .= "download failed from $siteurl (connection establishing error)\n";
    }

    if(is_readable($putname)) {
      $data = @file_get_contents($putname);
      $data_url = http_build_query(array('data' => $data,
					 ));

      $opts = array(
		    'http' => array(
				    'method' => 'POST',
				    'request_fulluri' => true, 
				    'header' => 'Cookie: PHPSESSID=' . $sess . "\r\nContent-Type: application/x-www-form-urlencoded\r\n",
				    'content' => $data_url
				    )
		    );
      if($bocaproxy != "")
	$opts['http']['proxy'] = $bocaproxy;
      if($bocaproxypass != "")
	$opts['http']['header'] .= "Proxy-Authorization: Basic " . $bocaproxypass . "\r\n";
      $opts['http']['header'] .= "Connection: close\r\n";
      $opts['http']['timeout'] = $timeo;
    $opts['ssl'] = array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true);
    $opts['https'] = array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true);
      $context = stream_context_create($opts);
      try {
	$s = @file_get_contents($siteurl . "site/putfile.php", 0, $context);
      } catch(Exception $e) {
	$s = false;
      }
      if($s===false) {
	LOGError("scoretransfer: timeout at upload for $siteurl");
	$logstr .= "timeout at full upload to $siteurl\n";
      } else {			  
	if(strpos($s,'SCORE UPLOADED OK') !== false) {
	  LOGInfo("scoretransfer: upload OK");
	  $logstr .= "upload of score to $siteurl OK\n";
	} else {
	  LOGError("scoretransfer: upload failed (" . $s . ")");
	  $logstr .= "upload of score to $siteurl failed (" . $s . ")\n";
	}
      }
    }
    if(is_readable($superlfile)) {
      $data = @file_get_contents($superlfile);
      $data_url = http_build_query(array('data' => $data,
					 ));
      $opts = array(
		    'http' => array(
				    'method' => 'POST',
				    'request_fulluri' => true,
				    'header' => 'Cookie: PHPSESSID=' . $sess . "\r\nContent-Type: application/x-www-form-urlencoded\r\n",
				    'content' => $data_url
				    )
		    );
      if($bocaproxy != "")
	$opts['http']['proxy'] = $bocaproxy;
      if($bocaproxypass != "")
	$opts['http']['header'] .= "Proxy-Authorization: Basic " . $bocaproxypass . "\r\n";
      $opts['http']['timeout'] = $timeo;
    $opts['ssl'] = array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true);
    $opts['https'] = array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true);
      $context = stream_context_create($opts);
      try {
	$s = @file_get_contents($siteurl . "site/putfilesuper.php", 0, $context);
      } catch(Exception $e) {
	$s=false;
      }
      if($s===false) {
	LOGError("scoretransfer: timeout at full upload for $siteurl");
	$logstr .= "timeout at full upload to $siteurl\n";
	continue;
      } else {
	if(strpos($s,'SCORE UPLOADED OK') !== false) {
	  LOGInfo("scoretransfer: upload full OK");
	  $logstr .= "upload of full score to $siteurl OK\n";
	}
	else {
	  LOGError("scoretransfer: upload full failed (" . $s . ")");
	  $logstr .= "upload of full score to $siteurl failed (" . $s . ")\n";
	}
      }
    }
  }
  return $logstr;
}


function getMainXML($contest,$timeo=60,$upd=false) {
  $ds = DIRECTORY_SEPARATOR;
  if($ds=="") $ds = "/";
  $logstr = '';  
  //  $logstr .= "A: " . now() . "\n"
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
  //  $logstr .= "AA: " . now() . "\n"
  $privatedir = $_SESSION['locr'] . $ds . "private";
  
  $c = DBConnect();
  if ($c==null) {
    $logstr .= "local database connection problem\n";
    return $logstr;
  }
  $r = DBExec($c, "select * from contesttable where contestnumber=$contest");
  if (DBnLines($r)==0) {
    $logstr .=  "Unable to find the contest $contest in the database.\n";
    LOGError("Unable to find the contest $contest in the database.");
    return $logstr;
  }
  $ct = DBRow($r,0);
  $localsite = $ct["contestlocalsite"];
  $mainsite = $ct["contestmainsite"];
  
  if(trim($ct['contestmainsiteurl']) == '') {
    $logstr .= "Main site URL not defined\n";
    return $logstr;
  }
  $sitedata = explode(' ',$ct['contestmainsiteurl']);
  if(count($sitedata) < 3) {
    LOGError("getMainXML: invalid mainsiteurl entry");
    $logstr .= "Main site URL is invalid\n";
    return $logstr;
  }
  if(count($sitedata) == 3 || $upd) {
    $updatetime=0;
  } else
    $updatetime=trim($sitedata[3]);

  $siteurl = $sitedata[0];
  LOGInfo("getMainXML: site $siteurl");
  $siteurl = makeurlhttps($siteurl);
  //		LOGError("url=" .$siteurl . "index.php?getsessionid=1");
  $opts = array();
  $opts['http']['timeout'] = $timeo;
    $opts['ssl'] = array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true);
    $opts['https'] = array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true);
  $context = stream_context_create($opts);
  $logstr .=  "Connecting to ". $siteurl . " (updatetime=" . $updatetime . ")\n";
  try {
    $sess = @file_get_contents($siteurl . "index.php?getsessionid=1", 0, $context);
  } catch(Exception $e) {
    $sess=false;
  }
  if($sess===false) {
    $logstr .=  "timeout at connection\n";
    LOGError("getMainXML: timeout at get session id for $siteurl");
    return $logstr;
  }
  $user = trim($sitedata[1]);
  $res = myhash( myhash (trim($sitedata[2])) . $sess);
  $opts = array(
		'http' => array(
				'method' => 'GET',
				'request_fulluri' => true, 
				'header' => 'Cookie: PHPSESSID=' . $sess . "\r\n"
				)
		);
  if($bocaproxy != "")
    $opts['http']['proxy'] = $bocaproxy;
  if($bocaproxypass != "")
    $opts['http']['header'] .= "Proxy-Authorization: Basic " . $bocaproxypass . "\r\n";
  $opts['http']['header'] .= "Connection: close\r\n";
  $opts['http']['timeout'] = $timeo;  
    $opts['ssl'] = array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true);
    $opts['https'] = array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true);
  $context = stream_context_create($opts);
  $logstr .=  "Authorizing\n";
  try {
    $ok = @file_get_contents($siteurl . "index.php?name=${user}&password=${res}&action=transfer", 0, $context);
  } catch(Exception $e) {
    $ok=false;
  }
  if($ok===false) {
    $logstr .=  "timeout at authorization\n";
    LOGError("getMainXML: timeout at login for $siteurl");
    return $logstr;
  }
  //  $logstr .= "AAA: " . now() . "\n"
  $ti = mytime();
  //		LOGError("ok=" . $ok);
  if(substr($ok,strlen($ok)-strlen('TRANSFER OK'),strlen('TRANSFER OK')) == 'TRANSFER OK') {
    $logstr .=  "Generating local data for site [$localsite] at time [$updatetime]\n";
    $data = generateSiteXML($contest, $localsite, $updatetime-30, $localsite);
    $logstr .= $data[1];
    // $logstr .= $s;
    $data = encryptData($data[0], myhash(trim($sitedata[2])),false);
    //    $logstr .= "AB: " . now() . "\n"
    
    $data_url = http_build_query(array('xml' => $data, 'updatetime' => ($updatetime-30)
				       ));
    
    $opts = array(
		  'http' => array(
				  'method' => 'POST',
				  'request_fulluri' => true,
				  'header' => 'Cookie: PHPSESSID=' . $sess . "\r\nContent-Type: application/x-www-form-urlencoded\r\n",
				  'content' => $data_url
				  )
		  );
    if($bocaproxy != "")
      $opts['http']['proxy'] = $bocaproxy;
    if($bocaproxypass != "")
      $opts['http']['header'] .= "Proxy-Authorization: Basic " . $bocaproxypass . "\r\n";
    $opts['http']['header'] .= "Connection: close\r\n";
    $opts['http']['timeout'] = $timeo;
    $opts['ssl'] = array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true);
    $opts['https'] = array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true);
    $context = stream_context_create($opts);
    $logstr .=  "Transferring data to main server\n";
    try {
      $s = @file_get_contents($siteurl . "site/getsite.php", 0, $context);
    } catch(Exception $e) {
      $s=false;
    }
    //    $logstr .= "ABB: " . now() . "\n"
    if($s===false) {
      $logstr .=  "timeout at transferring\n";
      LOGError("getMainXML: timeout at transfer for $siteurl");
      return $logstr;
    }
    $chstr = "<!-- <OK> --><!-- ";
    if(strpos($s,$chstr) !== false) {
      $logstr .=  "Transfer succeeded\n";
      LOGInfo("xmltransfer: OK");
    } else {
      $logstr .=  "Transfer error (" . $s . ")\n";
      LOGError("xmltransfer: failed (" . $s . ")");
      $chstr = "<!-- <NOTOK> --><!-- ";
    }

    $logstr .=  "Processing received data from main server\n";
    $s = substr($s, strpos($s, $chstr) + strlen($chstr));
    $s = substr($s, 0, strpos($s, " -->"));
    //    LOGError("string: " . substr($s,0,50));
    $s = decryptData($s,myhash(trim($sitedata[2])),'xml from main not ok');
    //    $logstr .= "ABBB: " . now() . "\n"
    if(strtoupper(substr($s,0,5)) != "<XML>") {
      $logstr .=  "Data corrupted\n";
      return $logstr;
    }
    $logstr .=  "Importing data to local server\n";
    $resp = importFromXML($s, $contest, $localsite, false, 1+$ct['updatetime'], $mainsite);
    //    $logstr .= "AC: " . now() . "\n"
    $logstr .= $resp[1];
    if($resp[0]) {
      $str = $sitedata[0] . ' ' . $sitedata[1] . ' ' . $sitedata[2] . ' ' . $ti;
      $ti = 2+$ct['updatetime'];
      $param = array('contestnumber' => $contest, 'mainsiteurl' => $str, 'updatetime' => $ti);
      DBUpdateContest ($param, null);
      return $logstr;
    } else {
      $logstr .=  "Importing error\n";
      LOGError("error importing xml");
    }
  } else {
    $logstr .=  "Transfer init connection error (" . $ok . ")\n";
    LOGError("xmltransfer: init connection failed (" . $ok . ")");
  }
  return $logstr;
}

function importFromXML($ar,$contest,$site,$tomain=false,$uptime=0,$mainsite=-1) {
  LOGInfo("importFromXML: contest $contest site $site tomain $tomain");
  $logstr = '';
  if($tomain) $serv='Main'; else $serv='Local';
  $data = implode("",explode("\n",$ar));
  $parser = xml_parser_create('');
  xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 1);
  xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
  xml_parse_into_struct($parser, $data, $values, $tags);
  xml_parser_free($parser);
  //	print_r($tags);
  //	print_r($values);
  $conn = DBConnect();
  if ($conn==null) return array(false, $logstr);
  //DBExec($conn,"begin work","importFromXML(begin)");
  //	DBExec($conn,"lock","importFromXML(lock)");
  $r = DBExec($conn, "select * from contesttable where contestnumber=$contest");
  if (DBnLines($r)==0) {
    $logstr .= "$serv - error finding contest $contest \n";
    LOGError("importFromXML: Unable to find the contest $contest in the database.");
    //    DBExec($conn,"rollback work");
    return array(false, $logstr);
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
	      //	      $tmp = sanitizeText(base64_decode(trim(implode('',explode('\n',$values[$i]["value"])))),false);
	      $tmp = base64_decode(trim(implode('',explode('\n',$values[$i]["value"]))));
	      $param[$p] = $tmp;
	    }
	  }

	  $param['contestnumber'] = $contest;
	  $param['contest'] = $contest;
	  if(count($param) < 2) continue;
	  unset($param['number']);

	  if($table == "contesttable") {
	    if($tomain) continue;
	    if($uptime > 0) $param['updatetime']=$uptime;
	    if(($ret=DBUpdateContest ($param, $conn))) {
	      if($ret==2) {
		$logstr .= "$serv - Contest " . $param["contestnumber"] . " updated\n";
		LOGInfo("importFromXML: Contest " . $param["contestnumber"] . " updated");
	      }
	    }
	    else {
	      $logstr .= "$serv - error to update $table ". $param["contestnumber"] . "\n";
	      LOGError("importFromXML: error to update $table ". $param["contestnumber"]);
	      if($conn != null)
		DBExec($conn,"rollback work");
	      return array(false, $logstr);
	    }
	    continue;
	  }

	  
	  //	  LOGInfo("importFromXML: $key params " .print_r( $param,true));
	  if($table == "answertable") {
	    if($tomain) continue;
	    if(($ret=DBNewAnswer ($contest, $param, $conn))) {
	      if($ret==2) {
		$logstr .= "$serv - Answer " . $param["answernumber"] . " updated\n";
		LOGInfo("importFromXML: Answer " . $param["answernumber"] . " updated");
	      }
	    }
	    else {
	      $logstr .= "$serv - error to update $table ". $param["answernumber"] . "\n";
	      LOGError("importFromXML: error to update $table ". $param["answernumber"]);
	      if($conn != null)
		DBExec($conn,"rollback work");
	      return array(false, $logstr);
	    }
	    continue;
	  }
	  if($table == "langtable") {
	    if($tomain) continue;
	    if(($ret=DBNewLanguage ($contest,$param, $conn))) {
	      if($ret==2) {
		$logstr .= "$serv - Language " . $param['langnumber'] ." updated\n";
		LOGInfo("importFromXML: Language " . $param['langnumber'] ." updated");
	      }
	    }
	    else {
	      $logstr .= "$serv - error to update $table ". $param['langnumber'] . "\n";
	      LOGError("importFromXML: error to update $table ". $param['langnumber']);
	      if($conn != null)
		DBExec($conn,"rollback work");
	      return array(false, $logstr);
	    }
	    continue;
	  }
	  if($table == "problemtable") {
	    if($tomain) continue;
	    if(($ret=DBNewProblem ($contest,$param, $conn))) {
	      if($ret==2)
		$logstr .= "$serv - Problem " . $param['problemnumber'] ." updated\n";
		LOGInfo("importFromXML: Problem " . $param['problemnumber'] ." updated");
	    }
	    else {
	      $logstr .= "$serv - error to update $table ". $param['problemnumber'] . "\n";
	      LOGError("importFromXML: error to update $table " . $param['problemnumber']);
	      if($conn != null)
		DBExec($conn,"rollback work");
	      return array(false, $logstr);
	    }
	    continue;
	  }
	  if(isset($param['usersitenumber']) && !isset($param['sitenumber'])) $param['sitenumber']=$param['usersitenumber'];              
	  if(isset($param['clarsitenumber']) && !isset($param['sitenumber'])) $param['sitenumber']=$param['clarsitenumber'];              
	  if(isset($param['runsitenumber']) && !isset($param['sitenumber'])) $param['sitenumber']=$param['runsitenumber'];              
	  if(!isset($param['sitenumber']) || ($param['sitenumber'] != $site && ($param['sitenumber'] != $mainsite || $tomain))) {
	    $logstr .= "$serv - site mismatch should be [$site] and is [" . $param['sitenumber'] . "]\n";
	    LOGError("importFromXML: site mismatch should be [$site] and is [" . $param['sitenumber'] . "]");
	    continue;
	  }
	  if($tomain && $table == "sitetable") {
	    DBNewSite($contest, $conn, $param);
	    if(($ret=DBUpdateSite($param, $conn)) !== false) {
	      if($ret==2) {
		$logstr .= "$serv - Site " . $param["sitenumber"] . " updated\n";
		LOGInfo("importFromXML: Site " . $param["sitenumber"] . " updated");
	      }
	    } else {
	      $logstr .= "$serv - error to update $table ". $param["sitenumber"] . "\n";
	      LOGError("importFromXML: error to update $table ". $param["sitenumber"]);
	      if($conn != null)
		DBExec($conn,"rollback work");
	      return array(false, $logstr);
	    }
	  }
	  if($tomain && $table == "sitetimetable") {
	    if(!DBUpdateSiteTime($contest, $param, $firsttimetime, $conn)) {
	      $logstr .= "$serv - error to update $table \n";
	      LOGError("importFromXML: error to update $table");
	      if($conn != null)
		DBExec($conn,"rollback work");
	      return array(false, $logstr);
	    } else {
	      LOGInfo("importFromXML: SiteTime updated");
	    }
	    $firsttimetime=false;
	  }
	  if($table == "usertable") {
	    if(($ret=DBNewUser($param, $conn))) {
	      if($ret==2) {
		$logstr .= "$serv - User " . $param["usernumber"]."/".$param['sitenumber']. " updated\n";
		LOGInfo("importFromXML: User " . $param["usernumber"]."/".$param['sitenumber']. " updated");
	      }
	    } else {
	      $logstr .= "$serv - error to update $table ". $param["usernumber"]."/".$param['sitenumber'] . "\n";
	      LOGError("importFromXML: error to update $table ". $param["usernumber"]."/".$param['sitenumber']);
	      if($conn != null)
		DBExec($conn,"rollback work");
	      return array(false, $logstr);
	    }
	  }
	  if($table == "tasktable") {
	    if(($ret=DBNewTask ($param, $conn))) {
	      if($ret==2) {
		$logstr .= "$serv - Task " . $param['tasknumber']."/".$param['sitenumber']." updated\n";
		LOGInfo("importFromXML: Task " . $param['tasknumber']."/".$param['sitenumber']." updated");
	      }
	    }
	    else {
	      $logstr .= "$serv - error to update $table " . $param['tasknumber']."/".$param['sitenumber'] . "\n";
	      LOGError("importFromXML: error to update $table " . $param['tasknumber']."/".$param['sitenumber']);
	      if($conn != null)
		DBExec($conn,"rollback work");
	      return array(false, $logstr);
	    }
	  }
	  if($table == "clartable") {
	    if(($ret=DBNewClar ($param, $conn))) {
	      if($ret==2) {
		$logstr .= "$serv - Clarification " . $param['clarnumber']."/".$param['sitenumber'] ." updated\n";
		LOGInfo("importFromXML: Clarification " . $param['clarnumber']."/".$param['sitenumber'] ." updated");
	      }
	    }
	    else {
	      $logstr .= "$serv - error to update $table ". $param['clarnumber']."/".$param['sitenumber'] . "\n";
	      LOGError("importFromXML: error to update $table ". $param['clarnumber']."/".$param['sitenumber']);
	      if($conn != null)
		DBExec($conn,"rollback work");
	      return array(false, $logstr);
	    }
	  }
	  if($table == "runtable") {
	    if(($ret=DBNewRun ($param, $conn, $tomain))) {
	      if($ret==2) {
		$logstr .= "$serv - Run " . $param['runnumber'] ."/".$param['sitenumber']." updated\n";
		LOGInfo("importFromXML: Run " . $param['runnumber'] ."/".$param['sitenumber']." updated");
	      }
	    }
	    else {
	      $logstr .= "$serv - error to update $table ". $param['runnumber'] ."/".$param['sitenumber'] . "\n";
	      LOGError("importFromXML: error to update $table ". $param['runnumber'] ."/".$param['sitenumber']);
	      if($conn != null)
		DBExec($conn,"rollback work");
	      return array(false, $logstr);
	    }
	  }
	}
      }
    }
  }
  if($conn != null)
    DBExec($conn,"commit work","importFromXML(commit)");
  return array(true, $logstr);
}

function genSQLs($contest, $site, $updatetime, $mainsite=1) {
  $sql = array();
  $sql['contesttable']="select contestnumber, contestname, conteststartdate, contestduration, contestlastmileanswer," .
    "contestlastmilescore, contestpenalty, contestmaxfilesize, contestmainsite, contestkeys " .
    "from contesttable where contestnumber=$contest"; // and updatetime >= $updatetime";
  $sql['sitetable']="select " .
    "contestnumber, " . 
    "sitenumber, " .
    "siteip, " . 
    "sitename, " . 
    "siteactive, " . 
    "sitepermitlogins, " . 
    "sitelastmileanswer, " . 
    "sitelastmilescore, " . 
    "siteduration, " . 
    "siteautoend, " .
    "sitejudging, " . 
    "sitetasking, " . 
    "siteglobalscore, " . 
    "sitescorelevel, " . 
    "sitemaxtask, " . 
    "sitechiefname, " . 
    "siteautojudge, " . 
    "sitemaxruntime, " .
    "sitemaxjudgewaittime, " . 
    "updatetime " .
    " from sitetable where contestnumber=$contest and sitenumber=$site and updatetime >= $updatetime";
  if($site != $mainsite) {
    $sql['answertable']="select * from answertable where contestnumber=$contest and fake='f' and updatetime >= $updatetime";
    $sql['langtable']="select * from langtable where contestnumber=$contest and updatetime >= $updatetime";
    $sql['problemtable']="select " .
      "contestnumber, " .
      "problemnumber, " .
      "problemname, " .
      "problemfullname, " .
      "problembasefilename, " .
      "probleminputfilename, " .
      "probleminputfile, " .
      "probleminputfilehash, " .
      "fake, " .
      //"problemcolorname, " .
      //"problemcolor, " .
      "updatetime" .
      " from problemtable where contestnumber=$contest and fake='f' and updatetime >= $updatetime";
    $sql['usertable']="select * from usertable where contestnumber=$contest and usersitenumber=$mainsite and updatetime >= $updatetime";
  } else
    $sql['usertable']="select * from usertable where contestnumber=$contest and usersitenumber=$site and updatetime >= $updatetime";
  $sql['sitetimetable']="select * from sitetimetable where contestnumber=$contest and sitenumber=$site and updatetime >= $updatetime";
  $sql['clartable']="select * from clartable where contestnumber=$contest and (clarsitenumber=$site or clarsitenumber=$mainsite) and updatetime >= $updatetime";
  $sql['runtable']="select * from runtable where contestnumber=$contest and runsitenumber=$site and updatetime >= $updatetime";
  $sql['tasktable']="select * from tasktable where contestnumber=$contest and sitenumber=$site and updatetime >= $updatetime";
  return $sql;
}

function generateSiteXML($contest,$site,$updatetime, $mainsite=1) {
  $sql = genSQLs($contest, $site, $updatetime, $mainsite);
  $c = DBConnect();
  $str = "<XML>\n";
  $logstr = '';
  if ($c==null) return null;
  DBExec($c, "begin work");
  foreach($sql as $kk => $vv) {
    $meta = pg_meta_data($c, $kk);
    if (!is_array($meta)) return null;
    $r = DBExec ($c, $vv, "generateSiteXML($kk)");
    $n = DBnLines ($r);
    if($n > 0)
      $logstr .= "$kk has $n records to update\n";
    for($i=0; $i<$n; $i++) {
      $atual = DBRow($r,$i);
      $str .= "<" . $kk . ">\n";
      foreach($atual as $key => $val) {
	if($meta[$key]['type'] == 'oid' && $val != '') {
	  if (($lo = DB_lo_open ($c, $val, "r")) !== false) {
	    $str .= "  <" . $key . ">" . base64_encode("base64:" . base64_encode(DB_lo_read($contest,$lo,-1,$c))) . "</" . $key . ">\n";
	    DB_lo_close($lo);
	  } else {
	    LOGError("large object ($key,$val) not readable");
	  }
	} else {
	  $str .= "  <" . $key . ">" . base64_encode($val) . "</" . $key . ">\n";
	}
      }
      $str .= "</" . $kk . ">\n";
    }
  }
  $str .= "</XML>\n";
  DBExec($c,"commit work","generateSiteXML(commit)");
  LOGInfo("xml data generated for contest $contest site $site at time $updatetime");
  return array($str,$logstr);
}

