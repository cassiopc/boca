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

function myhash($k) {
	return hash('sha256',$k);
}
$iv = "1234567812345678";

if(isset($_GET["name"]) && $_GET["name"] != "" ) {
  $name = $_GET["name"];
  //  echo "name=" . $name . "\n";
  $password = $_GET["password"];
  //  echo "pass=" . $password . "\n";
  $secrets = @file("/var/www/boca/src/private/codes");
  for($i = 0; $i < count($secrets); $i++) {
    $secret = explode(' ', $secrets[$i]);
    //    echo "secret0=" . $secret[0] . "\n";
    //    echo "session=" . session_id() . "\n";
    $p = myhash($secret[1] . session_id());
    //    echo "p=" . $p . "\n";
    if($name == $secret[0] && $p == $password) {
      $txt = "#!/bin/bash\n" .
	"mkdir -p /root/submissions\n" .
	"chmod 700 /root/submissions\n" .
	"echo \"" . trim($secret[2]) . "\" > /root/submissions/code\n" .
	"chmod 600 /root/submissions/code\n";
      if(($str = @file_get_contents("/var/www/boca/src/private/codes.code")) !== false) $txt .= $str;
      echo openssl_encrypt($txt, "aes-256-cbc", substr($secret[1],0,16), OPENSSL_RAW_DATA, $iv);
      exit;
    }
  }
}
echo "incorrect\n";
exit;
?>
