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
  $text = str_replace("*", "", $text);
  $text = str_replace("$", "", $text);
  $text = str_replace(")", "", $text);
  $text = str_replace("(", "", $text);
  $text = str_replace(";", "", $text);
  $text = str_replace("&", "", $text);
  $text = str_replace("<", "", $text);
  $text = str_replace(">", "", $text); 
  $text = str_replace("\"", "", $text); 
  $text = str_replace("'", "", $text);
  $text = str_replace("`", "", $text);
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
      @mkdir('/var/www/boca/src/private/logexternal/',0770,true);
      @file_put_contents("/var/www/boca/src/private/logexternal/" . $secret[0] . '.' . $name, '\nbegin ' .  time() . ' ' . base64_decode($_POST['data']), LOCK_EX | FILE_APPEND);
      @file_put_contents("/var/www/boca/src/private/logexternal/logexternal.log", $name . "|" . $secret[0] . '|' . date(DATE_RFC2822) . "\n", LOCK_EX | FILE_APPEND);
      echo "ok\n";
      exit;
    }
  }
}
echo "incorrect\n";
exit;
?>
