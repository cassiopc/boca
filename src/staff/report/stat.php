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
// Last modified 29/aug/2017 by cassio@ime.usp.br

require('header.php');

$d = DBRunReport($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"]);

echo "<center><h2>Estatísticas</h2></center>\n";
//----------------------------------------------------------
echo "<center><h3>Submissões por questão</h3></center>\n";
echo "<center><table border=1>\n";
echo "<tr><td><b><u>Questões</u></b></td>";

echo "<td>Total</td><td>Aceitas</td>";
echo "</tr>\n";

$str="Todas as submissões por questão";
$str2="Submissões aceitas por questão";
$cor = "";
$color=array();
$values_ac=array();
// while (list($keya, $val) = each($d['problem'])) {
foreach($d['problem'] as $keya => $val){
  $val = $d['problemyes'][$keya]; if($val=="") $val=0; 
  $str2 .= chr(1) . $keya . "(" . $val . ")" . chr(1) . $val;
  $cor .= "-" . $d['color'][$keya];
  $color[] = "#".$d['color'][$keya];
  $values_ac[]="$keya:$val";
}
$cor = substr($cor,1);
$values = array();

foreach($d['problem'] as $keya => $val){
  // while (list($keya, $val) = each($d['problem'])) {
  $values[] =  $keya . ":" . $val;
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

$myfile = fopen("runs_by_problems.txt", "w") or die("Não foi possível abrir o arquivo runs_by_problems.txt.");
for($i=0;$i<count($values);$i++){
  fwrite($myfile, $values[$i]);
  fwrite($myfile, " ");
  fwrite($myfile, $color[$i]);
  fwrite($myfile, "\n");
}
fclose($myfile);

$myfile = fopen("accepted_runs_by_problems.txt", "w") or die("Não foi possível abrir o arquivo accepted_runs_by_problems.txt.");
for($i=0;$i<count($values_ac);$i++){
  fwrite($myfile, $values_ac[$i]);
  fwrite($myfile, " ");
  fwrite($myfile, $color[$i]);
  fwrite($myfile, "\n");
}
fclose($myfile);

shell_exec("python3 ../../admin/report/piechart.py runs_by_problems.txt 'Submissões por questão'");
shell_exec("python3 ../../admin/report/piechart.py accepted_runs_by_problems.txt 'Submissões aceitas por questão'");


echo "<center><table><tr>";
echo "<td><img alt='submissões por questão' src=runs_by_problems.png width=500></td></tr>\n";
echo "<td><img alt='submissões aceitas por questão' src=accepted_runs_by_problems.png width=500></td>\n";
echo "</table> </center>";


//----------------------------------------------------------
echo "<center><h3>Submissões por questão e resposta</h3></center>\n";
echo "<center><table border=1>\n";
echo "<tr><td><b><u>Questões x respostas</u></b></td>";
// while (list($key, $val) = each($d['answer']))
foreach($d['answer'] as $key => $val){
  echo "<td>" . displayRunAnswer($key) . "</td>";
}
echo "<td>Total</td></tr>\n";

foreach($d['problem'] as $keya => $vala){
// while (list($keya, $vala) = each($d['problem'])) {
  echo "<tr><td>$keya ";
  echo "<img alt=\"balloon\" width=\"15\" ".
	  "src=\"" . balloonurl($d['color'][$keya]) ."\" />\n";
  echo "</td>";
  // while (list($key, $val) = each($d['answer'])) {
  foreach($d['answer'] as $key => $val){ 
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
echo "<center><h3>Submissões por questão e linguagem</h3></center>\n";
echo "<center><table border=1>\n";
echo "<tr><td><b><u>Questões x linguagens</u></b></td>";
reset($d['language']);
// while (list($key, $val) = each($d['language']))
foreach($d['language'] as $key => $val){
  echo "<td>$key</td>";
}
echo "<td>Total</td></tr>\n";

reset($d['problem']);
// while (list($keya, $vala) = each($d['problem'])) {
foreach($d['problem'] as $keya => $vala){
  echo "<tr><td>$keya ";
  echo "<img alt=\"balloon\" width=\"15\" ".
	  "src=\"" . balloonurl($d['color'][$keya]) ."\" />\n";
  echo "</td>";
  reset($d['language']);
  // while (list($key, $val) = each($d['language'])) {
  foreach($d['language'] as $key => $val){
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
echo "<center><h3>Submissões por linguagem</h3></center>\n";
echo "<center><table border=1>\n";
echo "<tr><td><b><u>Linguagens</u></b></td>";

echo "<td>Total</td><td>Aceitas</td>";
echo "</tr>\n";

$str="Todas as submissões por linguagem";
$str2="Submissões aceitas por linguagem";
$values = array();
$values_ac = array();

// while (list($keya, $val) = each($d['language'])) {
foreach($d['language'] as $keya => $val){
  $val=0;
  if(isset($d['languageyes'][$keya]))
    $val = $d['languageyes'][$keya];
  $str2 .= chr(1) . $keya . "(" . $val . ")" . chr(1) . $val;
  $values_ac[] = $keya.":".$val;
}

reset($d['language']);
// while (list($keya, $val) = each($d['language'])) {
foreach($d['language'] as $keya => $val){
  $str .= chr(1) . $keya . "(" . $val . ")" . chr(1) . $val;
  $values[] =$keya . ":" . $val;
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

$color = array();
$color[] = "#2cba00";
$color[] = "#a3ff00";
$color[] = "#fff400";
$color[] = "#ffa700";	
$color[] = "#ff0000";


$myfile = fopen("all_runs_by_language.txt", "w") or die("Não foi possível abrir o arquivo all_runs_by_language.txt.");
for($i=0;$i<count($values);$i++){
  fwrite($myfile, $values[$i]);
  fwrite($myfile, " ");
  fwrite($myfile, $color[$i]);
  fwrite($myfile, "\n");
}
fclose($myfile);

$myfile = fopen("accepted_runs_by_language.txt", "w") or die("Não foi possível abrir o arquivo accepted_runs_by_language.txt.");
for($i=0;$i<count($values);$i++){
  fwrite($myfile, $values_ac[$i]);
  fwrite($myfile, " ");
  fwrite($myfile, $color[$i]);
  fwrite($myfile, "\n");
}

fclose($myfile);


shell_exec("python3 ../../admin/report/piechart.py all_runs_by_language.txt 'Todas as submissões por linguagem' 'lower'");
shell_exec("python3 ../../admin/report/piechart.py accepted_runs_by_language.txt 'Submissões aceitas por linguagem' 'lower'");


echo "<center><table><tr>";
echo "<td><img alt='submissões por linguagem' src=all_runs_by_language.png width=500></td></tr>\n";
echo "<td><img alt='submissões aceitas por linguagem' src=accepted_runs_by_language.png width=500></td>\n";
echo "</table> </center>";

//----------------------------------------------------------
echo "<center><h3>Submissões por linguagem e resposta</h3></center>\n";
echo "<center><table border=1>\n";
echo "<tr><td><b><u>Linguagens x respostas</u></b></td>";
reset($d['answer']);
// while (list($key, $val) = each($d['answer']))
foreach($d['answer'] as $key => $val){
  echo "<td>" . displayRunAnswer($key) . "</td>";
}
echo "<td>Total</td></tr>\n";

reset($d['language']);
// while (list($keya, $vala) = each($d['language'])) {
foreach($d['language'] as $keya => $vala){

  echo "<tr><td>$keya</td>";
  reset($d['answer']);
  // while (list($key, $val) = each($d['answer'])) {
  foreach($d['answer'] as $key => $val){
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
echo "<center><h3>Submissões por resposta</h3></center>\n";

echo "<center><table><tr>";
echo "<td>";

echo "<center><table border=1>\n";
echo "<tr><td><b><u>Respostas</u></b></td>";

echo "<td>Respostas</td>";
echo "</tr>\n";

$str="Todas as submissões por resposta";
$values = array();
// while (list($keya, $val) = each($d['answer'])) {
foreach($d['answer'] as $keya => $val){
  $values[] = displayRunAnswer($keya) . ":" . $val;
  $str .= chr(1) . $keya . "(" . $val . ")" . chr(1) . $val;
  echo "<tr><td>" . displayRunAnswer($keya) . "</td>";
  echo "<td>$val</td>";
  echo "</tr>";
}

$color[] = "#af7f57";
$myfile = fopen("all_runs_by_answer.txt", "w") or die("Não foi possível abrir o arquivo all_runs_by_answer.txt.");
for($i=0;$i<count($values);$i++){
  fwrite($myfile, $values[$i]);
  fwrite($myfile, " ");
  fwrite($myfile, $color[$i]);
  fwrite($myfile, "\n");
}

fclose($myfile);

shell_exec("python3 ../../admin/report/piechart.py all_runs_by_answer.txt 'Todas as submissões por resposta' 'lower'");


echo "</table></center>";

echo "</td>";
echo "<td><img alt='submissões por resposta' src=all_runs_by_answer.png width=500></td></tr>\n";

echo "</table></center>\n";

//----------------------------------------------------------
echo "<br />";
echo "<hr />";
echo "<center><h3>Submissões por usuário e questão</h3></center>\n";
echo "<center><table border=1>\n";
echo "<tr><td><b><u>Usuários x questões</u></b></td>";
reset($d['problem']);
// while (list($key, $val) = each($d['problem'])) {
foreach($d['problem'] as $key => $val){
  echo "<td>$key ";
  echo "<img alt=\"balloon\" width=\"15\" ".
	  "src=\"" . balloonurl($d['color'][$key]) ."\" />\n";
  echo "</td>";
}
echo "<td>Total</td><td>Aceitas</td></tr>\n";

reset($d['username']);
// while (list($keya, $vala) = each($d['username'])) {
foreach($d['username'] as $keya => $vala){
  $keya = $d['username'][$keya];
  if(isset($d['user'][$keya]))
	  $vala = $d['user'][$keya];
  else $vala=0;
  echo "<tr><td>".$d['userfull'][$keya]."</td>";
  reset($d['problem']);
  // while (list($key, $val) = each($d['problem'])) {
  foreach($d['problem'] as $key => $val){
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
echo "<center><h3>Submissões por período</h3></center>\n";

$vezes = 30;
$passo = $st['siteduration']/$vezes;
$atual = 0;
$pos = 0;
$res = array();
$m = 0;
sort($d['timestamp']);
reset($d['timestamp']);
// while (list($keya, $val) = each($d['timestamp'])) {
foreach($d['timestamp'] as $keya => $val){
  while($atual+$passo < $val) {
    $atual += $passo;
    $pos++;
  }
  if(isset($res[$pos]))
	  $res[$pos]++;
  else $res[$pos]=1;
  if($res[$pos] > $m) $m=$res[$pos];
}

$str="Submissões por período" . chr(1) . $m;
$atual=0;
$values = array();
for($pos=0; $pos<$vezes; $pos++) {
  if(!isset($res[$pos]) || $res[$pos]=="") $res[$pos] = 0;
  $q = (int) ($atual/60);
  $atual += $passo;
  $qq = (int) ($atual/60);
  $str .= chr(1) . $q . "-" .$qq . chr(1) . $res[$pos];
  $values[] = $res[$pos];
}

$myfile = fopen("runs_by_time_period.txt", "w") or die("Não foi possível abrir o arquivo runs_by_time_period.txt.");
for($i=0;$i<count($values);$i++){
  fwrite($myfile, $values[$i]);
  fwrite($myfile, "\n");
}

shell_exec("python3 ../../admin/report/barplot.py runs_by_time_period.txt 'Submissões por período'");


echo "<center><img alt=runs_by_time_period src=runs_by_time_period.png width=500></center>\n";


//------------------------------------------------
$vezes = 30;
$passo = $st['siteduration']/$vezes;
$atual = 0;
$pos = 0;
$res = array();
sort($d['timestampyes']);
reset($d['timestampyes']);
// while (list($keya, $val) = each($d['timestampyes'])) {
foreach($d['timestampyes'] as $keya => $val){
  while($atual+$passo < $val) {
    $atual += $passo;
    $pos++;
  }
  if(isset($res[$pos]))
	  $res[$pos]++;
  else $res[$pos]=1;
}

$values_ac = array();
$str="Submissões aceitas por período" . chr(1) . $m;
$atual=0;
for($pos=0; $pos<$vezes; $pos++) {
  if(!isset($res[$pos]) || $res[$pos]=="") $res[$pos] = 0;
  $q = (int) ($atual/60);
  $atual += $passo;
  $qq = (int) ($atual/60);
  $str .= chr(1) . $q . "-" .$qq . chr(1) . $res[$pos];
  $values_ac[] = $res[$pos];
}

$myfile = fopen("accepted_runs_by_time_period.txt", "w") or die("Não foi possível abrir o arquivo accepted_runs_by_time_period.txt.");
for($i=0;$i<count($values_ac);$i++){
  fwrite($myfile, $values_ac[$i]);
  fwrite($myfile, "\n");
}

shell_exec("python3 ../../admin/report/barplot.py accepted_runs_by_time_period.txt 'Submissões aceitas por período'");


echo "<center><img alt=runs_by_time_period src=accepted_runs_by_time_period.png width=500></center>\n";


include("$locr/footnote.php");
?>
