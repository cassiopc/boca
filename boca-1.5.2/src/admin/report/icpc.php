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

$score = DBScore($_SESSION["usertable"]["contestnumber"], false, -1, $st["siteglobalscore"]);

echo "<h2>ICPC Output</h2>";
echo "<pre>";
$n=0;
$class=1;
while(list($e, $c) = each($score)) {
	if(isset($score[$e]["site"]) && isset($score[$e]["user"])) {
		$r = DBUserInfo($_SESSION["usertable"]["contestnumber"], 
						$score[$e]["site"], $score[$e]["user"]);
		echo $r["usericpcid"] . ",";
		echo $class++ . ",";
		echo $score[$e]["totalcount"] . ",";
		echo $score[$e]["totaltime"] . ",";
		
		if($score[$e]["first"])
			echo $score[$e]["first"] . "\n";
		else echo "0\n";
		$n++;
	}
}
echo "</pre>";
?>
<?php include("$locr/footnote.php"); ?>

