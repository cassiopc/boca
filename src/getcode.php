<?php
ob_start();
header ("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header ("Cache-Control: no-cache, must-revalidate");
header ("Pragma: no-cache");
header ("Content-Type: text/html; charset=utf-8");
session_start();
if (!isset($_GET["name"])) {
  session_unset();
  session_destroy();
  session_start();
  echo session_id();
  exit;
}
ob_end_flush();

function make_seed()
{
  list($usec, $sec) = explode(' ', microtime());
  return $sec + $usec * 1000000;
}
srand(make_seed());

function myhash($k) {
	return hash('sha256',$k);
}
$iv = "1234567812345678";

if(isset($_GET["name"]) && $_GET["name"] != "" ) {
  $name = $_GET["name"];
  $password = $_GET["password"];
  $secrets = file("/var/www/boca/src/private/run-past.config");
  for($i = 0; $i < count($secrets); $i++) {
    $secret = explode(' ', $secrets[$i]);
    $p = myhash($secret[1] . session_id());
    if($name == $secret[0] && $p == $password) {
      $cc = md5(rand() . rand() . @file_get_contents('/proc/uptime') . rand() . rand());
      $txt = "#!/bin/bash\n" .        
        "mkdir -p /root/submissions\n" .
        "chown root.root /root/submissions\n" .
        "chmod 700 /root/submissions\n" .
        "echo -n \"" . $cc . "\" >/root/submissions/comp\n" .
        "chmod 600 /root/submissions/comp\n" .
        "echo -n \"" . trim($secret[2]) . "\" > /root/submissions/code\n" .
        "chmod 600 /root/submissions/code\n";

      if(($str = @file_get_contents("/var/www/boca/src/private/run-past.code")) !== false) $txt .= $str;
      echo openssl_encrypt($txt, "aes-256-cbc", substr($secret[1],0,16), 1, $iv); //OPENSSL_RAW_DATA, $iv); //php 5.4.0
      @file_put_contents("/var/www/boca/src/private/run-past.log", $name . "|" . $cc . "|" . date(DATE_RFC2822) . "\n", LOCK_EX | FILE_APPEND);
      exit;
    }
  }
}
echo "incorrect\n";
exit;
?>
