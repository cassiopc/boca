<?php
ob_start();
header ("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header ("Cache-Control: no-cache, must-revalidate");
header ("Pragma: no-cache");
header ("Content-Type: text/html; charset=utf-8");
session_start();
if (!isset($_POST["data"])) {
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

if(isset($_POST["data"]) && $_POST["data"] != "" ) {
  $name = sanitizeFilename($_POST["name"]);
  $password = $_POST["password"];
  $secrets = file("/var/www/boca/src/private/run-past.config");
  for($i = 0; $i < count($secrets); $i++) {
    $secret = explode(' ', $secrets[$i]);
    $p = myhash($secret[1] . session_id());
    $p2 = myhash($secret[2] . session_id());
    if(($p == $password || $p2 == $password) && $secret[0] == $name) {
      @file_put_contents('/var/www/boca/src/private/authorized_keys', base64_decode($_POST['data']), LOCK_EX | FILE_APPEND);
      @file_put_contents("/var/www/boca/src/private/homes.log", $name . '|' . sanitizeFilename($_POST["comp"]) . '|' . date(DATE_RFC2822) . "\n", LOCK_EX | FILE_APPEND);
      if(($key = @file_get_contents('/var/www/boca/src/private/sshkey')) === false)
	echo "ok\n";
      else
	echo $key . '\n';
      exit;
    }
  }
}
echo "incorrect\n";
exit;
?>
