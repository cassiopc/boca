<?php

require '../../db.php';
require '../config.php';

header('Content-type: text/plain; encoding=utf-8');

$s = DBSiteInfo($contest, $site);

echo $s['currenttime'];

?>
