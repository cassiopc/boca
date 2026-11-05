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
require 'header.php';

// ???
$runsitenumber = myhtmlspecialchars($_GET["runsitenumber"]);
$runnumber = myhtmlspecialchars($_GET["runnumber"]);

if (($a = DBChiefGetRunToAnswer($runnumber, $runsitenumber, 
		$_SESSION["usertable"]["contestnumber"])) === false) {
	MSGError("Outro juiz já selecionou este item.");
	ForceLoad($runphp);
}

$b = DBGetProblemData($_SESSION["usertable"]["contestnumber"], $a["problemnumber"]);
?>
<br><br><center><b>Use os campos abaixo para julgar a submissão:
</b></center>
<form name="form1" method="post" action="<?php echo $runeditphp; ?>">
  <input type=hidden name="confirmation" value="noconfirm" />
  <center>
    <table border="1">
      <tr> 
        <td width="27%" align=right><b>Local:</b></td>
        <td width="83%"> 
		<input type=hidden name="sitenumber" value="<?php echo $a["sitenumber"]; ?>" />
		<?php echo $a["sitenumber"]; ?>
        </td>
      </tr>
      <tr> 
        <td width="27%" align=right><b>Número:</b></td>
        <td width="83%"> 
		<input type=hidden name="number" value="<?php echo $a["number"]; ?>" />
		<?php echo $a["number"]; ?>
        </td>
      </tr>
      <tr> 
        <td width="27%" align=right><b>Tempo:</b></td>
        <td width="83%"> 
		<?php echo dateconvminutes($a["timestamp"]); ?>
        </td>
      </tr>
      <tr> 
        <td width="27%" align=right><b>Questão</b><i> <?php echo $a["problemname"]; ?></i>: </td>
        <td width="83%"> 
<?php
for ($i=0;$i<count($b);$i++) {
	echo "<b>Entrada:</b><a href=\"../filedownload.php?". filedownload($b[$i]["inputoid"], $b[$i]["inputfilename"]) ."\">";
	echo $b[$i]["inputfilename"] . "</a>";
	echo " <a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('../filewindow.php?".
		filedownload($b[$i]["inputoid"], $b[$i]["inputfilename"]) ."', 'View$i - INPUT','width=680,height=600,scrollbars=yes,resizable=yes')\">ver</a> &nbsp;";

	if(isset($b[$i]["soloid"])) {
	  echo "<b>Sol:</b><a href=\"../filedownload.php?". filedownload($b[$i]["soloid"], $b[$i]["solfilename"])  . "\">";
	  echo $b[$i]["solfilename"] . "</a>";
	}
	echo " <a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('../filewindow.php?".
		filedownload($b[$i]["soloid"], $b[$i]["solfilename"]) ."', 'View$i - CORRECT OUTPUT','width=680,height=600,scrollbars=yes,resizable=yes')\">ver</a>";
}
?>
	&nbsp;</td>
      </tr>
      <tr> 
        <td width="27%" align=right><b>Linguagem</b>:</td>
        <td width="83%"><i> <?php echo $a["language"]; ?></i>
        &nbsp;</td>
      </tr>
      <tr> 
        <td width="27%" align=right><b>Código-fonte:</b></td>
        <td width="83%"> 
<?php
			echo "<a href=\"../filedownload.php?". filedownload($a["sourceoid"],$a["sourcename"]) . "\">" . $a["sourcename"] . "</a>\n";
echo "<a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('../filewindow.php?" .
filedownload($a["sourceoid"],$a["sourcename"]) ."', 'View - SOURCE', ".
"'width=680,height=600,scrollbars=yes,resizable=yes')\">ver</a>\n";
?>
        </td>
      </tr>
      <tr>
        <td width="27%" align=right><b>Resposta 1:</b></td>
        <td width="83%">
<?php
$ans = DBGetAnswers($_SESSION["usertable"]["contestnumber"]);
for ($i=0;$i<count($ans);$i++)
	if ($a["answer1"] == $ans[$i]["number"]) {
//	  if($ans[$i]["fake"] != "t") {
          if($a["judgesite1"] != "" && $a["judge1"] != "") {
   	   $uu = DBUserInfo ($_SESSION["usertable"]["contestnumber"], $a["judgesite1"], $a["judge1"]);
	   echo displayRunAnswer($ans[$i]["desc"]) . " [juiz=" . $uu["username"] . " (" . $a["judgesite1"] . ")]";
	  } else
	   echo displayRunAnswer($ans[$i]["desc"]);
        }
?>
        </td>
      </tr>
      <tr>
        <td width="27%" align=right><b>Resposta 2:</b></td>
        <td width="83%">
<?php
$ans = DBGetAnswers($_SESSION["usertable"]["contestnumber"]);
for ($i=0;$i<count($ans);$i++)
	if ($a["answer2"] == $ans[$i]["number"]) {
//	  if($ans[$i]["fake"] != "t") {
          if($a["judgesite2"] != "" && $a["judge2"] != "") {
   	   $uu = DBUserInfo ($_SESSION["usertable"]["contestnumber"], $a["judgesite2"], $a["judge2"]);
	   echo displayRunAnswer($ans[$i]["desc"]) . " [juiz=" . $uu["username"] . " (" . $a["judgesite2"] . ")]";
	  } else
	   echo displayRunAnswer($ans[$i]["desc"]);
	}
?>
        </td>
      </tr>

<!--
      <tr> 
        <td width="27%" align=right><b>Notificar usuário:</b></td>
        <td width="83%">
          <input class=checkbox type=checkbox name="notifyuser" value="yes"
<?php 
if (($s=DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
        ForceLoad("../index.php");

if ($a["timestamp"] < $s["sitelastmileanswer"]) echo "checked"; ?>>
(Altere somente se souber exatamente o efeito desta opção)
        </td>
      </tr>
      <tr> 
        <td width="27%" align=right><b>Atualizar placar:</b></td>
        <td width="83%">
          <input class=checkbox type=checkbox name="updatescore" value="yes"
<?php if ($a["timestamp"] < $s["sitelastmilescore"]) echo "checked"; ?>>
(Altere somente se souber exatamente o efeito desta opção)
        </td>
      </tr>
-->
    </table>
  </center>
  <br>
<!--
  <script language="javascript">
    function conf() {
      if (confirm("Confirmar?")) {
        document.form1.confirmation.value='confirm';
      }
    }
  </script>
  <center>
      <button type="submit" name="Submit" value="Judge" onClick="conf()">Julgar</button>
     <button type="submit" name="open" value="Open run for rejudging" onClick="conf()">Abrir submissão para novo julgamento</button>
      <button type="submit" name="cancel" value="Cancel editing">Cancelar edição</button>
      <button type="submit" name="delete" value="Delete" onClick="conf()">Excluir</button>
      <input type="reset" name="Submit2" value="Limpar">
<br><br>
  </center>

-->
  <center>
<br>
<b>Julgamento automático:</b>
<!--
<button type="submit" name="giveup" value="Renew">Renovar</button>
-->
<br><br>
  <table border="1">
  <tr>
        <td width="27%" align=right><b>Resultado automático:</b></td>
        <td width="83%"> 
<?php
if($a["autobegin"]!="" && $a["autoend"]=="")
      echo "em andamento";
else if($a["autoend"]!="") {
      if($a["autoanswer"]!="") echo displayRunAnswer($a["autoanswer"]);
      else echo "Erro no julgamento automático";
} else
      echo "indisponível";
?>
        </td>
  </tr>
  <tr>
        <td width="27%" align=right><b>Juiz automático:</b></td>
<?php if($a["autobegin"]!="" && $a["autoend"]=="")
      echo "<td width=\"83%\">". $a["autoip"] ." desde ". dateconvsimple($a["autobegin"]) ."</td>";
else if($a["autoend"]!="")
      echo "<td width=\"83%\">". $a["autoip"] ." de ". dateconvsimple($a["autobegin"]) ." até ". dateconvsimple($a["autoend"]) ."</td>";
else
      echo "<td width=\"83%\">indisponível</td>";
?>
  </tr>
  <tr> 
        <td width="27%" align=right><b>Saída padrão:</b></td>
        <td width="83%"> 
<?php 
if($a["autostdout"]!="") {
	echo "<a href=\"../filedownload.php?".filedownload($a["autostdout"],"stdout") ."\">stdout</a>\n";
	echo "<a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('../filewindow.php?".
	filedownload($a["autostdout"],"stdout") ."', 'View - STDOUT','width=680,height=600,scrollbars=yes,".
	"resizable=yes')\">ver</a>\n";
} else
      echo "indisponível";
?>
        </td>
  </tr>
  <tr> 
        <td width="27%" align=right><b>Erro padrão:</b></td>
        <td width="83%"> 
<?php 
if($a["autostderr"]!="") {
	echo "<a href=\"../filedownload.php?". filedownload($a["autostderr"],"stderr") . "\">stderr</a>\n";
	echo "<a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('../filewindow.php?".
	filedownload($a["autostderr"],"stderr") ."', 'View - STDERR','width=680,height=600,scrollbars=yes,".
	"resizable=yes')\">ver</a>\n";
} else
      echo "indisponível";
?>
        </td>
  </tr>
  </table></center>

</form>
</body>
</html>
