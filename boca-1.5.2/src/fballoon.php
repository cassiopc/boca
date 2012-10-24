<?php
////////////////////////////////////////////////////////////////////////////////
//BOCA Online Contest Administrator
//    Copyright (C) 2003-2012 by BOCA Development Team (bocasystem@gmail.com)
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
// Last modified 21/jul/2012 by cassio@ime.usp.br
function balloonpng($dir,$get_s,$get_color=null,$get_file=null) {
	if($get_s)
		$smile=imagecreatefrompng($dir . "/images/smallballoontransp.png");
	else
		$smile=imagecreatefrompng($dir . "/images/bigballoontransp.png");
	
	imageSaveAlpha($smile, true);
	if($get_color != null) {
		$r = hexdec( substr($get_color, 0, 2) );
		$g = hexdec( substr($get_color, 2, 2) );
		$b = hexdec( substr($get_color, 4, 2) );
		
		$kek=imagecolorallocate($smile,$r,$g,$b);
		if($get_s)
			imagefill($smile,5,5,$kek);
		else
			imagefill($smile,12,25,$kek);
	}
	if($get_file != null)
		imagepng($smile,$get_file);
	else
		imagepng($smile);
}

function balloonurl($color) {
	$locr=$_SESSION['locr'];
	$loc=$_SESSION['loc'];
	$ds = DIRECTORY_SEPARATOR;
	if($ds=="") $ds = "/";
	if(!is_readable($locr . $ds . 'balloons' . $ds . md5($color) . '.png')) {
		if($color<0 || $color=='')
			@copy($locr. $ds . 'images' . $ds . 'bigballoonboca1.png', $locr . $ds . 'balloons' . $ds . md5($color) . '.png');
		else
			balloonpng($locr,false,$color,$locr . $ds . 'balloons' . $ds . md5($color) . '.png');
		if(!is_readable($locr . $ds . 'balloons' . $ds . md5($color) . '.png')) {
			return $loc . "/images/bigballoontransp.png";
//			return $loc . "/balloon.php?color=" . $color;
		}
	}
	return $loc . "/balloons/" . md5($color) . '.png';
}
?>
