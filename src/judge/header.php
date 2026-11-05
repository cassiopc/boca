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
ob_start();
header ("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header ("Cache-Control: no-cache, must-revalidate");
header ("Pragma: no-cache");
header ("Content-Type: text/html; charset=utf-8");
session_start();
ob_end_flush();
require('../version.php');
require_once("../globals.php");
require_once("../db.php");

$runteam='team.php';
$runphp = "runchief.php";
$runeditphp = "runeditchief.php";

echo "<html><head><title>Área do juiz</title>\n";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">\n";
echo "<link rel=stylesheet href=\"../Css.php\" type=\"text/css\">\n";

//temporario!!!!
//echo "<meta http-equiv=\"refresh\" content=\"60\" />";
if(!ValidSession()) {
	InvalidSession("judge/index.php");
        ForceLoad("../index.php");
}
if($_SESSION["usertable"]["usertype"] != "judge") {
	IntrusionNotify("judge/index.php");
        ForceLoad("../index.php");
}

if(($s = DBSiteInfo($_SESSION["usertable"]["contestnumber"], $_SESSION["usertable"]["usersitenumber"])) == null)
        ForceLoad("../index.php");

$isboss=false;
if($s["sitechiefname"]== $_SESSION["usertable"]["username"] || strpos($_SESSION["usertable"]["username"],'chief')===true)
  $isboss=true;

if($isboss)
  $cc = "338833";
else
  $cc = "77cc77";


echo "<script language=\"javascript\" src=\"../reload.js\"></script>\n";
echo "</head><body onload=\"Comecar()\" onunload=\"Parar()\"><table border=1 width=\"100%\">\n";
echo "<tr><td nowrap bgcolor=\"#$cc\" align=center>";
echo "<img src=\"../images/smallballoontransp.png\" alt=\"\">";
echo "<font color=\"#000000\">BOCA</font>";
echo "</td><td bgcolor=\"#$cc\" width=\"99%\">\n";
echo "Usuário: " . $_SESSION["usertable"]["username"] . " (sede=".$_SESSION["usertable"]["usersitenumber"].")<br>\n";
list($clockstr,$clocktype)=siteclock();
echo "</td><td bgcolor=\"#$cc\" align=center nowrap>&nbsp;".$clockstr."&nbsp;</td></tr>\n";
echo "</table>\n";

$clar = DBOpenClarsInSites($_SESSION["usertable"]["contestnumber"], $s["sitejudging"]);
$run = DBOpenRunsInSites($_SESSION["usertable"]["contestnumber"], $s["sitejudging"]);
if($isboss) {
$nrchief = 0;
$rrun = DBAllRunsInSites($_SESSION["usertable"]["contestnumber"], $s["sitejudging"]);
for ($i=0; $i<count($rrun); $i++) {
  if($rrun[$i]["answer1"] != 0 && $rrun[$i]["answer2"] != 0 && $rrun[$i]["status"] != "judged")
    $nrchief++;
}
}
$nr=count($run);
$nc=count($clar);

echo "<table border=0 width=\"100%\" align=center>\n";
echo " <tr>\n";
//echo "  <td align=center width=\"10%\"><a class=menu style=\"font-weight:bold\" href=problem.php>Questões</a></td>\n";

echo "  <td align=center width=\"10%\"><a class=menu style=\"font-weight:bold\" href=run.php>Submissões ($nr)</a></td>\n";
if($isboss) {
  echo "  <td align=center width=\"10%\"><a class=menu style=\"font-weight:bold\" href=runchief.php>Juiz chefe ($nrchief)</a></td>\n";
}
{
  echo "  <td align=center width=\"10%\"><a class=menu style=\"font-weight:bold\" href=allrunlist.php>Todas as submissões </a></td>\n";
}
echo "  <td align=center width=\"10%\"><a class=menu style=\"font-weight:bold\" href=score.php>Placar</a></td>\n";
echo "  <td align=center width=\"10%\"><a class=menu style=\"font-weight:bold\" href=clar.php>Dúvidas ($nc)</a></td>\n";

echo "  <td align=center width=\"10%\"><a class=menu style=\"font-weight:bold\" href=history.php>Histórico</a></td>\n";
echo "  <td align=center width=\"10%\"><a class=menu style=\"font-weight:bold\" href=team.php>Como equipe</a></td>\n";
echo "  <td align=center width=\"10%\"><a class=menu style=\"font-weight:bold\" href=option.php>Opções</a></td>\n";
echo "  <td align=center width=\"10%\"><a class=menu style=\"font-weight:bold\" href=../index.php>Sair</a></td>\n";
echo " </tr>\n"; 
echo "</table>\n";
?>
