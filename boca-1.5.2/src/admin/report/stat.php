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

require('header.php');

$d = DBRunReport($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"]);

echo "<center><h2>Statistics</h2></center>\n";
//----------------------------------------------------------
echo "<center><h3>Runs by Problem</h3></center>\n";
echo "<center><table border=1>\n";
echo "<tr><td><b><u>Problems</u></b></td>";

echo "<td>Total</td><td>Accepted</td>";
echo "</tr>\n";

$str="All Runs by Problem";
$str2="Accepted Runs by Problem";
reset($d['problem']);
$cor = "";
while (list($keya, $val) = each($d['problem'])) {
  $val = $d['problemyes'][$keya]; if($val=="") $val=0; 
  $str2 .= chr(1) . $keya . "(" . $val . ")" . chr(1) . $val;
  $cor .= "-" . $d['color'][$keya];
}
$cor = substr($cor,1);

reset($d['problem']);
while (list($keya, $val) = each($d['problem'])) {
  $str .= chr(1) . $keya . "(" . $val . ")" . chr(1) . $val;
  echo "<tr><td>$keya ";
  echo "<img alt=\"balloon\" width=\"15\" ".
	  "src=\"" . balloonurl($d['color'][$keya]) ."\" />\n";
  echo "</td>";
  echo "<td>$val</td>";
  if(isset($d['problemyes'][$keya])) {
    echo "<td nowrap>".$d['problemyes'][$keya];
    if($val != 0) {
      $p = round(100*$d['problemyes'][$keya] / $val);
      echo " (".$p."%)";
    }
    echo "</td>";
  }
  else
    echo "<td nowrap>0 (0%)</td>";
  echo "</tr>";
}
echo "</table></center>";

echo "<center><table><tr>";
echo "<td><img alt=\"\" src=\"piechart.php?dados=".rawurlencode($str)."&color=".rawurlencode($cor)."\" /></td>\n";
echo "<td><img alt=\"\" src=\"piechart.php?dados=".rawurlencode($str2)."&color=".rawurlencode($cor)."\" /></td></tr></table></center>\n";

//----------------------------------------------------------
echo "<center><h3>Runs by Problem and Answer</h3></center>\n";
echo "<center><table border=1>\n";
echo "<tr><td><b><u>Problems x Answers</u></b></td>";
reset($d['answer']);
while (list($key, $val) = each($d['answer']))
  echo "<td>$key</td>";
echo "<td>Total</td></tr>\n";

reset($d['problem']);
while (list($keya, $vala) = each($d['problem'])) {
  echo "<tr><td>$keya ";
  echo "<img alt=\"balloon\" width=\"15\" ".
	  "src=\"" . balloonurl($d['color'][$keya]) ."\" />\n";
  echo "</td>";
  reset($d['answer']);
  while (list($key, $val) = each($d['answer'])) {
    if(!isset($d['pa'][$keya][$key]))
	echo "<td>0</td>";
    else {
        $p = round(100*$d['pa'][$keya][$key] / $vala);
        echo "<td nowrap>".$d['pa'][$keya][$key]." (".$p."%)</td>";
    }
  }
  echo "<td>$vala</td>";
  echo "</tr>";
}
echo "</table></center>";

//----------------------------------------------------------
echo "<center><h3>Runs by Problem and Language</h3></center>\n";
echo "<center><table border=1>\n";
echo "<tr><td><b><u>Problems x Languages</u></b></td>";
reset($d['language']);
while (list($key, $val) = each($d['language']))
  echo "<td>$key</td>";
echo "<td>Total</td></tr>\n";

reset($d['problem']);
while (list($keya, $vala) = each($d['problem'])) {
  echo "<tr><td>$keya ";
  echo "<img alt=\"balloon\" width=\"15\" ".
	  "src=\"" . balloonurl($d['color'][$keya]) ."\" />\n";
  echo "</td>";
  reset($d['language']);
  while (list($key, $val) = each($d['language'])) {
    if(!isset($d['pl'][$keya][$key]))
	echo "<td>0</td>";
    else {
        $p = round(100*$d['pl'][$keya][$key] / $vala);
        echo "<td nowrap>".$d['pl'][$keya][$key]." (".$p."%)</td>";
    }
  }
  echo "<td>$vala</td>";
  echo "</tr>";
}
echo "</table></center>";

//----------------------------------------------------------
echo "<br />";
echo "<hr />";
echo "<center><h3>Runs by Language</h3></center>\n";
echo "<center><table border=1>\n";
echo "<tr><td><b><u>Languages</u></b></td>";

echo "<td>Total</td><td>Accepted</td>";
echo "</tr>\n";

$str="All Runs by Language";
$str2="Accepted Runs by Language";
reset($d['language']);
while (list($keya, $val) = each($d['language'])) {
  $val = $d['languageyes'][$keya]; if($val=="") $val=0; 
  $str2 .= chr(1) . $keya . "(" . $val . ")" . chr(1) . $val;
}

reset($d['language']);
while (list($keya, $val) = each($d['language'])) {
  $str .= chr(1) . $keya . "(" . $val . ")" . chr(1) . $val;
  echo "<tr><td>$keya</td>";
  echo "<td>$val</td>";
  if(isset($d['languageyes'][$keya])) {
    $p = round(100*$d['languageyes'][$keya] / $val);
    echo "<td nowrap>".$d['languageyes'][$keya]." (".$p."%)</td>";
  }
  else
    echo "<td nowrap>0 (0%)</td>";
  echo "</tr>";
}
echo "</table></center>";

echo "<center><table><tr>";
echo "<td><img alt=\"\" src=\"piechart.php?dados=".rawurlencode($str)."\" /></td>\n";
echo "<td><img alt=\"\" src=\"piechart.php?dados=".rawurlencode($str2)."\" /></td></tr></table></center>\n";

//----------------------------------------------------------
echo "<center><h3>Runs by Language and Answer</h3></center>\n";
echo "<center><table border=1>\n";
echo "<tr><td><b><u>Languages x Answers</u></b></td>";
reset($d['answer']);
while (list($key, $val) = each($d['answer']))
  echo "<td>$key</td>";
echo "<td>Total</td></tr>\n";

reset($d['language']);
while (list($keya, $vala) = each($d['language'])) {
  echo "<tr><td>$keya</td>";
  reset($d['answer']);
  while (list($key, $val) = each($d['answer'])) {
    if(!isset($d['la'][$keya][$key]))
	echo "<td>0</td>";
    else {
        $p = round(100*$d['la'][$keya][$key] / $vala);
        echo "<td nowrap>".$d['la'][$keya][$key]." (".$p."%)</td>";
    }
  }
  echo "<td>$vala</td>";
  echo "</tr>";
}
echo "</table></center>";

//----------------------------------------------------------
echo "<br />";
echo "<hr />";
echo "<center><h3>Runs by Answer</h3></center>\n";

echo "<center><table><tr>";
echo "<td>";

echo "<center><table border=1>\n";
echo "<tr><td><b><u>Answers</u></b></td>";

echo "<td>Answers</td>";
echo "</tr>\n";

$str="All Runs by Answer";
reset($d['answer']);
while (list($keya, $val) = each($d['answer'])) {
  $str .= chr(1) . $keya . "(" . $val . ")" . chr(1) . $val;
  echo "<tr><td>$keya</td>";
  echo "<td>$val</td>";
  echo "</tr>";
}
echo "</table></center>";

echo "</td>";
echo "<td><img alt=\"\" src=\"piechart.php?order=1&dados=".rawurlencode($str)."\" /></td></tr></table></center>\n";

//----------------------------------------------------------
echo "<br />";
echo "<hr />";
echo "<center><h3>Runs by User and Problem</h3></center>\n";
echo "<center><table border=1>\n";
echo "<tr><td><b><u>Users x Problems</u></b></td>";
reset($d['problem']);
while (list($key, $val) = each($d['problem'])) {
  echo "<td>$key ";
  echo "<img alt=\"balloon\" width=\"15\" ".
	  "src=\"" . balloonurl($d['color'][$key]) ."\" />\n";
  echo "</td>";
}
echo "<td>Total</td><td>Accepted</td></tr>\n";

reset($d['username']);
while (list($keya, $vala) = each($d['username'])) {
  $keya = $d['username'][$keya];
  if(isset($d['user'][$keya]))
	  $vala = $d['user'][$keya];
  else $vala=0;
  echo "<tr><td>".$d['userfull'][$keya]."</td>";
  reset($d['problem']);
  while (list($key, $val) = each($d['problem'])) {
    if(!isset($d['up'][$keya][$key]))
	echo "<td bgcolor=\"ffff88\">0</td>";
    else {
	$q = $d['up'][$keya][$key];
	$color = "ff5555";
        if($q < 0) {
		$q = - $q;
		$color = "22ee22";
	}
        echo "<td nowrap bgcolor=\"$color\">".$q;
	if($vala != 0) {
          $p = round(100*$q / $vala);
          echo " (".$p."%)";
	}
	echo "</td>";
    }
  }
  if($vala != "")
    echo "<td>$vala</td>";
  else
    echo "<td>0</td>";
  if(isset($d['useryes'][$keya])) {
    if($vala != 0) {
      $p = round(100*$d['useryes'][$keya] / $vala);
      echo "<td nowrap>".$d['useryes'][$keya]." (".$p."%)</td>";
    } else
      echo "<td>".$d['useryes'][$keya]."</td>";
  } else
    echo "<td>0</td>";

  echo "</tr>";
}
echo "</table></center>";

//----------------------------------------------------------
echo "<br />";
echo "<hr />";
echo "<center><h3>Runs by Time Period</h3></center>\n";

$vezes = 30;
$passo = $st['siteduration']/$vezes;
$atual = 0;
$pos = 0;
$res = array();
$m = 0;
sort($d['timestamp']);
reset($d['timestamp']);
while (list($keya, $val) = each($d['timestamp'])) {
  while($atual+$passo < $val) {
    $atual += $passo;
    $pos++;
  }
  if(isset($res[$pos]))
	  $res[$pos]++;
  else $res[$pos]=1;
  if($res[$pos] > $m) $m=$res[$pos];
}

$str="Runs by Time Period" . chr(1) . $m;
$atual=0;
for($pos=0; $pos<$vezes; $pos++) {
  if($res[$pos]=="") $res[$pos] = 0;
  $q = (int) ($atual/60);
  $atual += $passo;
  $qq = (int) ($atual/60);
  $str .= chr(1) . $q . "-" .$qq . chr(1) . $res[$pos];
}

echo "<center><img alt=\"\" src=\"linechart.php?dados=".rawurlencode($str)."\" /></center>\n";

//------------------------------------------------
$vezes = 30;
$passo = $st['siteduration']/$vezes;
$atual = 0;
$pos = 0;
$res = array();
sort($d['timestampyes']);
reset($d['timestampyes']);
while (list($keya, $val) = each($d['timestampyes'])) {
  while($atual+$passo < $val) {
    $atual += $passo;
    $pos++;
  }
  if(isset($res[$pos]))
	  $res[$pos]++;
  else $res[$pos]=1;
}

$str="Accepted Runs by Time Period" . chr(1) . $m;
$atual=0;
for($pos=0; $pos<$vezes; $pos++) {
  if($res[$pos]=="") $res[$pos] = 0;
  $q = (int) ($atual/60);
  $atual += $passo;
  $qq = (int) ($atual/60);
  $str .= chr(1) . $q . "-" .$qq . chr(1) . $res[$pos];
}

echo "<center><img alt=\"\" src=\"linechart.php?dados=".rawurlencode($str)."\" /></center>\n";

include("$locr/footnote.php");
?>
