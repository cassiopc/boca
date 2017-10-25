<?php
////////////////////////////////////////////////////////////////////////////////
//BOCA Online Contest Administrator
//    Copyright (C) by BOCA Development Team (bocasystem@gmail.com)
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
// Last modified 24/oct/2017 by cassio@ime.usp.br
require_once('db.php');
define("dbcompat_1_4_1",true);

// sanitization 
function sanitizeVariables(&$item, $key) 
{ 
    if (!is_array($item)) 
    { 
        // undoing 'magic_quotes_gpc = On' directive 
        if (get_magic_quotes_gpc()) 
            $item = stripcslashes($item); 
        
        $item = sanitizeText($item); 
    } 
} 

function filedownload($oid,$fname,$msg='') {
	$cf = globalconf();
	$if = rawurlencode(encryptData($fname, session_id() . $cf['key'],false));
	$p = myhash($oid . $fname . $msg . session_id() . $cf["key"]);
	$str = "oid=". $oid . "&filename=". $if . "&check=" . $p;
	if($msg != '') $str .= "&msg=" . rawurlencode($msg);
	return $str;
}
function dirrec($dir, $user, $group, $dirPermissions, $filePermissions, $avoid=array()) {
  $ds = DIRECTORY_SEPARATOR;
  if($ds=="") $ds = "/";
  if(is_dir($dir)) {
    $u = posix_getpwuid(fileowner($dir));
    $un = $u['name'];
    $ug = $u['gid'];
    if($un != $user) echo "user of $dir must be fixed!!\n";
    if($ug != $group) echo "group of $dir must be fixed!!\n";
    if(@chmod($dir, $dirPermissions) === false) echo "cannot chmod $dir\n";
    if(($dp = @opendir($dir)) === false) return;
    while($file = readdir($dp)) {
      if (($file == ".") || ($file == ".."))
	continue;
      $cont = false;
      for($i = 0; $i < count($avoid); $i++)
	if(substr($file, strlen($file)-strlen($avoid[$i])) == $avoid[$i]) {
	  $cont = true;
	  break;
	}
      if($cont) continue;
      $fullPath = $dir . $ds . $file;
      dirrec($fullPath, $user, $group, $dirPermissions, $filePermissions, $avoid);
    }
    closedir($dp);
  } else {
    if(!is_link($dir)) {
      //      $t = myunique();
      //@copy($dir, $dir . '.tmp' . $t);
      //@rename($dir . '.tmp' . $t, $dir);
      $u = posix_getpwuid(fileowner($dir));
      $un = $u['name'];
      $ug = $u['gid'];
      if($un != $user) echo "user of $dir must be fixed!!\n";
      if($ug != $group) echo "group of $dir must be fixed!!\n";
      if(@chmod($dir, $filePermissions)=== false) echo "cannot chmod $dir\n";
    }
  }
}

function fixbocadir($dir,$full=false) {
	if(is_dir($dir)) {
	  $ds = DIRECTORY_SEPARATOR;
	  if($ds=="") $ds = "/";
	  $u = posix_getpwuid(fileowner($dir));
	  $un = $u['name'];
	  $ug = $u['gid'];
	  @file_put_contents($dir . $ds . 'private' . $ds . '.htaccess', "Deny from all\n");
	  @touch($dir . $ds . 'private' . $ds . 'remotescores' . $ds . 'otherservers');
	  if($full)
	    $d = array('problemtmp','runtmp','scoretmp','remotescores','remotescoresfull','comp','logexternal','runslog');
	  else
	    $d = array('problemtmp','runtmp','scoretmp');
	  foreach($d as $a) cleardir($dir . $ds . 'private' . $ds . $a,true,true,false);
	  dirrec($dir, $un, $ug, 0755, 0644, array('private', '.oldboca'));
	  dirrec($dir . $ds . 'private', $un, $ug, 0750, 0640, array('.oldboca'));
	  if(@file_put_contents($dir . $ds . 'private' . $ds . '.htaccess', "Deny from all\n") === false) return false;
	  return true;
	} else {
	  return false;
	}	
}
function updatebocafile($dirboca, $dirz, $t) {
  $ok = 0;
  if(is_dir($dirz)) {
    $ds = DIRECTORY_SEPARATOR;
    if($ds=="") $ds = "/";
    $d = @opendir($dirz);
    while (($file = @readdir($d)) !== false) {
      if($file != '.' && $file != '..')
	$ok = $ok + updatebocafile($dirboca . $ds . $file, $dirz . $ds . $file, $t);
    }
    @closedir($d);
    @cleardir($dirz);
  } else {
    if(is_file($dirboca)) {
      copy($dirboca, $dirboca . '.' . $t . '.oldboca');
    } else {
      file_put_contents($dirboca . '.' . $t . '.oldboca', "");
    }
    @chmod($dirboca . '.' . $t . '.oldboca', 0000);
    if(rename($dirz, $dirboca) === true) $ok=1;
  }
  return $ok;
}
function revertupdatebocafile($dirboca, $t) {
  $ok = 0;
  if(is_dir($dirboca)) {
    $ds = DIRECTORY_SEPARATOR;
    if($ds=="") $ds = "/";
    $d = @opendir($dirboca);
    while (($file = @readdir($d)) !== false) {
      if($file != '.' && $file != '..')
	$ok = $ok + revertupdatebocafile($dirboca . $ds . $file, $t);
    }
    @closedir($d);
  } else {
    if(is_file($dirboca) && substr($dirboca, strlen($dirboca) - strlen('.' . $t . '.oldboca')) == '.' . $t . '.oldboca') {
      @chmod($dirboca, 0600);
      if(@copy($dirboca, substr($dirboca, 0, strlen($dirboca) - strlen('.' . $t . '.oldboca'))) === true) $ok=1;
      @chmod($dirboca, 0000);
    }
  }
  return $ok;
}
function cleardir($dir,$cddir=true,$secure=true,$removedir=true) {
  if(file_exists($dir)) {
	if(is_dir($dir)) {
		$ds = DIRECTORY_SEPARATOR;
		if($ds=="") $ds = "/";
		if($cddir) {
			@chdir($dir);
			@chdir('..');
		}
		$d = @opendir($dir);
		while (($file = @readdir($d)) !== false) {
		  if($file != '.' && $file != '..')
		    cleardir($dir . $ds . $file, false, $secure, true);
		}
		@closedir($d);
		if($removedir)
		  @rmdir($dir);
	} else {
	  if($secure && !is_link($dir))
	    file_put_contents($dir,str_repeat('XXXXXXXXXX',10000));
	  @unlink($dir);
	}
  }
}

// gen random alphanum string
function randstr($len=8,$from='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789') {
	$str='';
	$fromlen=strlen($from);
	while($len > 0) {
		$str .= substr($from,rand(0,$fromlen-1),1);
		$len--;
	}
	return $str;
}

function myhtmlspecialchars($text) {
	return sanitizeText($text,false);
}

// does the actual 'html' and 'sql' sanitization.
function sanitizeText($text, $doamp=true) 
{
	if($doamp)
		$text = str_replace("&", "&amp;", $text);
    $text = str_replace("<", "&lt;", $text);
    $text = str_replace(">", "&gt;", $text); 
    $text = str_replace("\"", "&quot;", $text); 
    $text = str_replace("'", "&#39;", $text);
    $text = str_replace("`", "&#96;", $text);
    //$text = escape_string($text); 
    $text = addslashes($text); 
    return $text; 
}
function sanitizeFilename($text) 
{
  $text = str_replace("*", "_", $text);
  $text = str_replace("$", "_", $text);
  $text = str_replace(")", "_", $text);
  $text = str_replace("(", "_", $text);
  $text = str_replace(";", "_", $text);
  $text = str_replace("&", "_", $text);
  $text = str_replace("<", "_", $text);
  $text = str_replace(">", "_", $text); 
  $text = str_replace("\"", "_", $text); 
  $text = str_replace("'", "_", $text);
  $text = str_replace("`", "_", $text);
  $text = addslashes($text); 
  return $text; 
}

function unsanitizeText($text) {
    $text = str_replace("&amp;", "&", $text);
	return $text;
}

array_walk_recursive($_FILES, 'sanitizeVariables'); 
array_walk_recursive($_POST, 'sanitizeVariables'); 
array_walk_recursive($_GET, 'sanitizeVariables'); 
array_walk_recursive($_COOKIE, 'sanitizeVariables'); 

//name of calling function
function getFunctionName($num=2) {
        if(strcmp(phpversion(),'5.3.6')<0) {
                $backtrace = debug_backtrace();
        } else {
                if(strcmp(phpversion(),'5.4.0')<0)
                        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
                else
                        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT,$num+5);
        }
        $ret = '';
        for($i=0; $i<$num; $i++)
                if(isset($backtrace[$i]) && isset($backtrace[$i]['function']))
                        $ret .= " " . $backtrace[$i]['function'];
        if($ret =='') $ret='undef';
        return $ret;
}

function getIP() {
	if (getenv("REMOTE_ADDR"))
		$ip = getenv("REMOTE_ADDR");
	else
		return "UNKNOWN";
	if(defined("dbcompat_1_4_1") && dbcompat_1_4_1==true) return $ip;

	$ip1='';
	if (getenv("HTTP_X_FORWARDED_FOR")) {
		$ip1 = getenv("HTTP_X_FORWARDED_FOR");
		$ip1 = strtok ($ip1, ",");
		if($ip1 != $ip) $ip .= ';' . $ip1;
	}
	if (getenv("HTTP_X_CLIENTIP")) {
		$ip1a = getenv("HTTP_X_CLIENTIP");
		$ip1a = strtok ($ip1a, ",");
		if($ip1a != $ip1 && $ip1a != getenv("REMOTE_ADDR")) $ip .= ';' . $ip1a;
	}
	if (getenv("HTTP_CLIENT_IP")) {
		$ip2 = getenv("HTTP_CLIENT_IP");
		$ip2 = strtok ($ip2, ",");
		if($ip2 != $ip1a && $ip1 != $ip2 && $ip2 != getenv("REMOTE_ADDR")) $ip .= ';' . $ip2;
	} else {
		if (getenv('HTTP_X_FORWARDED')) {
			$ip .= ';' . getenv('HTTP_X_FORWARDED');
		} else {
			if (getenv('HTTP_FORWARDED')) {
				$ip .= ';' . getenv('HTTP_FORWARDED');
			}
		}
	}
	return sanitizeText($ip);
}
//retorna ip e hostname do cliente
function getIPHost() {
	$ips = explode(';',getIP());
	$s='';
	for($ipn=0;$ipn<count($ips);$ipn++) {
		$ip = $ips[$ipn];
//next lines where suggested to be removed by 
//Mario Sanchez (Ing. de Sistemas y Computacion, Universidad de los Andes, Bogota, Colombia)
//because they are very slow to run depending on the network
//		$host = @gethostbyaddr($ip);
//		if ($host != $ip && $host != "")
//			$s .= $ip . "(" . $host . ") ";
//		else
			$s .= $ip . ' ';
	}
	return $s;
}
//trata o caso de sessao invalida
function InvalidSession($where) {
	$msg = "Session expired on $where";
	LOGLevel($msg,3);
	unset($_SESSION["usertable"]);
	MSGError("Session expired. You must log in again.");
}
//trata o caso de tentativa de burlar as regras
function IntrusionNotify($where) {
	$msg = "Security Violation: $where";
	if (isset($_SESSION["usertable"]["username"]))
		$msg .= " (" . $_SESSION["usertable"]["username"] . "/" . $_SESSION["usertable"]["usersitenumber"] .")";
	unset($_SESSION["usertable"]);
	LOGLevel($msg,1);
	MSGError("Violation ($where). Admin warned.");
}
// verifica se a sessao esta aberta e ok
function ValidSession() {
	if (!isset($_SESSION["usertable"])) return(FALSE);
	$gip = getIP();
	// cassiopc: sites that use multiple IP addresses to go out create a serious problem to check IPs...
//	if(substr($_SESSION["usertable"]["userip"],0,6) != '157.92') {
//	if ($_SESSION["usertable"]["userip"] != $gip ||
//		$_SESSION["usertable"]["usersession"] != session_id()) return(FALSE);
  //      } else {
	if($_SESSION["usertable"]["usersession"] != session_id()) return(FALSE);
    //    }
	if($_SESSION["usertable"]["usermultilogin"] == 't') return(TRUE);
	
	$tmp = DBUserInfo($_SESSION["usertable"]["contestnumber"], 
					  $_SESSION["usertable"]["usersitenumber"], 
					  $_SESSION["usertable"]["usernumber"]);
	if ($tmp["userip"] != $gip) return(FALSE); //cassiopc: they may create a problem here too...
	return(TRUE);
}
// grava erro no arquivo de log
function LOGError($msg) {
	LOGLevel($msg,0,false);
}
function LOGInfo($msg) {
	LOGLevel($msg,2,false);
}
// grava linha no arquivo de log com o nivel especificado
function LOGLevel($msg,$level,$dodb=true) {
	$msga = sanitizeText(str_replace("\n", " ", $msg));
	$msg = now() . ": ";
    // if php version arrives to 5.10 then this will not work!!
	if(strcmp(phpversion(),'5.4.0')<0) define_syslog_variables ();
	$prior = LOG_CRIT;
	switch ($level) {
		case 0: $msg .= "ERROR: ";
			$type = "error";
			$prior = LOG_ERR;
			break;
		case 1: $msg .= "WARN: ";
			$type = "warn";
			$prior = LOG_WARNING;
			break;
		case 2: $msg .= "INFO: ";
			$type = "info";
			$prior = LOG_INFO;
			break;
		case 3: $msg .= "DEBUG: ";
			$type = "debug";
			$prior = LOG_DEBUG;
			break;
	}
	$msg .= getIPHost() . ": " . $msga;

	openlog ("BOCA", LOG_ODELAY, LOG_USER);	
	syslog ($prior, $msg);
	closelog();
	if ($dodb && isset($_SESSION["usertable"]))
		DBNewLog($_SESSION["usertable"]["contestnumber"], $_SESSION["usertable"]["usersitenumber"], 
				 $_SESSION["usertable"]["usernumber"], $type, getIP(), $msga, "");
}
function mytime() {
  return time();
}
function mymtime() {
  return microtime(true);
}
function myunique($val=0) {
  return (((int)(100*microtime(true))) % 10000000)*100 + ($val % 100);
}
//retorna data e hora atuais
function now () {
	return date('H\:i:s T \- d/M/Y');
}
//retorna data e hora em seg convertida para padrao
function dateconv ($d) {
	return date('H\:i:s T \- d/M/Y', $d);
}
//retorna data e hora em seg convertida para padrao simples
function dateconvsimple ($d) {
	return date('H\:i', $d);
}
//transforma segundos para minutos
function dateconvminutes ($d) {
	return (int)($d/60);
}
//alerta mensagem via javascript
function MSGError($msg) {
	$msg = str_replace("\n", " ", $msg);
        echo "<script language=\"JavaScript\">\n";
        echo "alert('". $msg . "');\n";
        echo "</script>\n";
}
//gera script para voltar aa tela dada
function ForceLoad($where) {
        echo "<script language=\"JavaScript\">\n";
	echo "document.location='" . $where . "';\n";
	echo "</script></html>\n";
	exit;
}
function ForceClose() {
        echo "<script language=\"JavaScript\">\n";
	echo "window.close;\n";
	echo "</script></html>\n";
	exit;
}

/**
 * Compare an IP address to network(s)
 *
 * The network(s) argument may be a string or an array. A negative network
 * match must start with a "!". Depending on the 3rd parameter, it will
 * return true or false on the first match, or any negative rule will have
 * absolute priority (default).
 *
 * Samples:
 * match_network ("192.168.1.0/24", "192.168.1.1") -> true
 *
 * match_network (array ("192.168.1.0/24",  "!192.168.1.1"), "192.168.1.1")       -> false
 * match_network (array ("192.168.1.0/24",  "!192.168.1.1"), "192.168.1.1", true) -> true
 * match_network (array ("!192.168.1.0/24", "192.168.1.1"),  "192.168.1.1")       -> false
 * match_network (array ("!192.168.1.0/24", "192.168.1.1"),  "192.168.1.1", true) -> false
 *
 * @param mixed  Network to match
 * @param string IP address
 * @param bool   true: first match will return / false: priority to negative rules (default)
 * @see http://php.benscom.com/manual/en/function.ip2long.php#56373
 */
function match_network ($nets, $ip) {
    if (!is_array ($nets)) $nets = explode(",",$nets);
   
    foreach ($nets as $net) {
	$net = trim($net);
        $rev = (preg_match ("/^\!/", $net)) ? true : false;
        $net = preg_replace ("/^\!/", "", $net);

        $ip_arr   = explode('/', $net);
        $net_long = ip2long(trim($ip_arr[0]));
		if(count($ip_arr) > 1 && trim($ip_arr[1]) != '') {
			$x        = ip2long(trim($ip_arr[1]));
			$mask     = long2ip($x) == ((int) trim($ip_arr[1])) ? $x : 0xffffffff << (32 - ((int) trim($ip_arr[1])));
        } else { 
			$mask=0xffffffff;
		}
		$ip_long  = ip2long($ip);
		
        if ($rev) {
            if (($ip_long & $mask) != ($net_long & $mask)) return true;
        } else {
            if (($ip_long & $mask) == ($net_long & $mask)) return true;
        }
    }
    return false;
}
// eof
?>
