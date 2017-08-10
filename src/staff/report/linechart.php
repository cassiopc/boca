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
// Last modified 05/aug/2012 by cassio@ime.usp.br

ob_start();
header ("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header ("Cache-Control: no-cache, must-revalidate");
header ("Pragma: no-cache");
session_start();
$locr = $_SESSION['locr'];
$loc = $_SESSION['loc'];

require_once($locr . "/libchart/libchart.php");
header ("Content-type: image/png");
ob_end_flush();

$v = explode(chr(1),rawurldecode($_GET['dados']),100);

$chart = new VerticalChart(1000, 300);

$chart->setUpperBound($v[1]);

for($i=2;$i<count($v); $i+=2)
  $chart->addPoint(new Point($v[$i], $v[$i+1]));

$chart->setTitle($v[0]);
$chart->setLogo($locr. "/images/poweredbyboca.png");
$chart->render();
?>
