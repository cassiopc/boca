<?php

header('Content-type: image/png');

$smile=imagecreatefrompng("../images/balloon4.png");
imageSaveAlpha($smile, true);
$kek=imagecolorallocate($smile,0,0,255);
imagefill($smile,12,25,$kek);
imagepng($smile);

?>
