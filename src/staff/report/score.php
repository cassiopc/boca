<?php
////////////////////////////////////////////////////////////////////////////////
//BOCA Online Contest Administrator
//    Copyright (C) 2003-2012 by BOCA System (bocasystem@gmail.com)
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
//Last updated 10/jul/2012 by cassio@ime.usp.br

require('header.php');

$final = true;
$s = $st;
$des = true;
$detail=true;
if($_GET["p"] == "0") $ver = false;
else if($_GET["p"] == "2") $detail=false;
else {
  $ver = true;
  $des = false;
}
if(isset($_GET["hor"])) $hor = $_GET["hor"];
else $hor = -1;

if ($s["currenttime"] >= $s["sitelastmilescore"] && $ver) {
	$togo = (int) (($s['siteduration'] - $s["sitelastmilescore"])/60);
	echo"<br /><center><h2>Scoreboard (as of $togo minutes to go)</h2></center>\n";
} else
	echo"<br /><center><h2>Final Scoreboard</h2></center>\n";

require("$locr/scoretable.php");
include("$locr/footnote.php");
?>
