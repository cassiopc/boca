<?php
ob_start();
header ("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header ("Cache-Control: no-cache, must-revalidate");
header ("Pragma: no-cache");
header ("Content-Type: text/html; charset=utf-8");
session_start();
if (!isset($_POST["comp"])) {
  session_unset();
  session_destroy();
  session_start();
  echo session_id();
  exit;
}
ob_end_flush();

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

function myhash($k) {
	return hash('sha256',$k);
}

if(isset($_POST["comp"]) && $_POST["comp"] != "" ) {
  $name = sanitizeFilename($_POST["comp"]);
  $password = $_POST["code"];
  $secrets = file("/var/www/boca/src/private/run-past.config");
  for($i = 0; $i < count($secrets); $i++) {
    $secret = explode(' ', $secrets[$i]);
    $p = myhash($secret[2] . session_id());
    if($p == $password) {
	    $filn = $secret[0] . '.' . $name . '.' . time();
	    $dirn1= '/var/www/boca/src/private/logexternal/' . $secret[0];
	    $dirn = $dirn1 . '/' . $name;
      @mkdir($dirn,0770,true);
      if(isset($_POST['logsession']))
	@file_put_contents($dirn . '/' . $filn . '.logsession', "\nbegin(" . date(DATE_RFC2822) . ")\n" . base64_decode($_POST['logsession']), LOCK_EX | FILE_APPEND);
      if(isset($_POST['logfs']))
	@file_put_contents($dirn . '/' . $filn . '.logfs', "\nbegin(" . date(DATE_RFC2822) . ")\n" . base64_decode($_POST['logfs']), LOCK_EX | FILE_APPEND);
      if(isset($_POST['loglshw']))
	@file_put_contents($dirn . '/' . $filn . '.loglshw', "\nbegin(" . date(DATE_RFC2822) . ")\n" . base64_decode($_POST['loglshw']), LOCK_EX | FILE_APPEND);
      if(isset($_POST['logupd']))
	@file_put_contents($dirn . '/' . $filn . '.logupd', "\nbegin(" . date(DATE_RFC2822) . ")\n" . base64_decode($_POST['logupd']), LOCK_EX | FILE_APPEND);
      if(isset($_POST['logkfs']))
	@file_put_contents($dirn . '/' . $filn . '.logkfs', "\nbegin(" . date(DATE_RFC2822) . ")\n" . base64_decode($_POST['logkfs']), LOCK_EX | FILE_APPEND);
      if(isset($_POST['logkeys']))
	@file_put_contents($dirn . '/' . $filn . '.logkeys', "\nbegin(" . date(DATE_RFC2822) . ")\n" . base64_decode($_POST['logkeys']), LOCK_EX | FILE_APPEND);
      @file_put_contents($dirn1 . "/logexternal.log", $name . "|" . $secret[0] . '|' . date(DATE_RFC2822) . "\n", LOCK_EX | FILE_APPEND);
      echo "ok\n";
      exit;
    }
  }
}
echo "incorrect\n";
exit;
?>
