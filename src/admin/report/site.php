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

$sitetime = DBAllSiteTime($_SESSION["usertable"]["contestnumber"], $site);
?>
<br />
<center><h2>Registros de início e parada do local</h2></center>

    <table border="0">
<?php
echo "        <td nowrap width=\"50%\" align=right>Data de início (competição=" . dateconv($ct["conteststartdate"]) . "):</td>";
echo "<td width=\"50%\"><b>" . dateconv($st["sitestartdate"]) . "</b></td>\n";
echo "<tr>";
if (!$st["siterunning"]) {
	echo "      <tr>\n";
	echo "        <td nowrap width=\"50%\" align=right><b>Encerrado em:</b></td>\n";
	echo "        <td width=\"50%\"><b>" . dateconv($st["siteendeddate"]); 
	if($st["siteautoended"]) echo " (automático)";
	echo "</b></td>\n";
	echo "      </tr>\n";
	if($st["siteautoended"]) {
		$w = (int) ($st["siteduration"]/60);
		$ww = $st["siteduration"] % 60;
	}
	else {
		$w = (int) ($st["currenttime"]/60);
		$ww = $st["currenttime"] % 60;
	}
	echo "      <tr>\n";
	echo "        <td nowrap width=\"50%\" align=right><b>Duração real:</b></td>\n";
	echo "        <td width=\"50%\"><b>$w minutos";
	if($ww != 0)	echo " e $ww segundos";
	echo "</b></td>\n";
	echo "      </tr>\n";
}
echo "<tr>\n";
echo "        <td width=\"50%\" align=right>Duração prevista (competição=";
echo $ct["contestduration"]/60;
echo "):</td>";
?>
        <td width="50%">
          <?php echo $st["siteduration"]/60; ?>
        </td>
      </tr>
      <tr>
<?php
echo "        <td width=\"50%\" align=right>Fim das respostas (competição=";
echo $ct["contestlastmileanswer"]/60;
echo "):</td>";
?>
        <td width="50%">
          <?php echo $st["sitelastmileanswer"]/60; ?>
        </td>
      </tr>
      <tr>
<?php
echo "        <td width=\"50%\" align=right>Congelamento do placar (competição=";
echo $ct["contestlastmilescore"]/60;
echo "):</td>";
?>
        <td width="50%">
          <?php echo $st["sitelastmilescore"]/60; ?>
        </td>
      </tr>
      <tr>
        <td width=\"50%\" align=right>Número de dúvidas:</td>
        <td width="50%"><?php echo $st["sitenextclar"]; ?>
        </td>
      </tr>
      <tr>
        <td width=\"50%\" align=right>Número de submissões:</td>
        <td width="50%"><?php echo $st["sitenextrun"]; ?>
        </td>
      </tr>
      <tr>
        <td width=\"50%\" align=right>Número de pedidos:</td>
        <td width="50%"><?php echo $st["sitenexttask"]; ?>
        </td>
      </tr>
    </table>
  </center>
<center>
<br />
<table border=1>
<tr>
<td nowrap width=\"50%\" align=right>Início</td><td nowrap width=\"50%\" align=left>Término</td>
</tr>
<?php
$n = count($sitetime);
for ($i=0; $i< $n; $i++) {
  echo "<tr>";
  echo "<td nowrap align=right>";
  echo dateconv($sitetime[$i]["sitestartdate"]);
  echo "</td>";
  echo "<td nowrap align=left>";
  if($sitetime[$i]["siteenddate"] == 0) {
    if($st["siterunning"])
      echo "em andamento";
    else echo "encerrado automaticamente";
  }
  else
    echo dateconv($sitetime[$i]["siteenddate"]);
  echo "</td>";
  echo "</tr>";
}
?>
</table>
</center>

<?php include("$locr/footnote.php"); ?>
