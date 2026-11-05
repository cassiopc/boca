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
// Last modified 08/aug/2015 by cassio@ime.usp.br
require('header.php');

if(($ct = DBContestInfo($_SESSION["usertable"]["contestnumber"])) == null)
	ForceLoad("../index.php");

if (isset($_GET["delete"]) && is_numeric($_GET["delete"])) {
	$param["number"] = $_GET["delete"];
	DBDeleteLanguage ($_SESSION["usertable"]["contestnumber"],$param);
	ForceLoad("language.php");
}

if (isset($_POST["Submit3"]) && isset($_POST["langnumber"]) && is_numeric($_POST["langnumber"]) && 
    isset($_POST["langname"]) && $_POST["langname"] != "") {
	if(strpos(trim($_POST["langname"]),' ')!==false) {
		$_POST["confirmation"]='';
		MSGError('O nome da linguagem não pode conter espaços');
	} else {
	if ($_POST["confirmation"] == "confirm") {
		$param = array();
		$param['number'] = $_POST['langnumber'];
		$param['name'] = trim($_POST['langname']);
		$param['extension'] = $_POST['langextension'];
		DBNewLanguage ($_SESSION["usertable"]["contestnumber"], $param);
	}
	}
	ForceLoad("language.php");
}
?>
<br>
  <script language="javascript">
    function conf2(url) {
      if (confirm("Confirmar a exclusão da linguagem e de todos os dados associados, inclusive as submissões?")) {
		  if (confirm("Tem certeza? OS DADOS NÃO PODERÃO SER RECUPERADOS!")) {
			  document.location=url;
		  } else {
			  document.location='language.php';
		  }
      } else {
        document.location='language.php';
      }
    }
  </script>
<table width="100%" border=1>
 <tr>
  <td><b>Linguagem nº</b></td>
  <td><b>Nome</b></td>
  <td><b>Extensão</b></td>
 </tr>
<?php
$lang = DBGetLanguages($_SESSION["usertable"]["contestnumber"]);
$cf = globalconf();
for ($i=0; $i<count($lang); $i++) {
  echo " <tr>\n";
  echo "  <td nowrap><a href=\"javascript: conf2('language.php?delete=" . $lang[$i]["number"] . "')\">" . 
	  $lang[$i]["number"] . "</a></td>\n";
  echo "  <td nowrap>" . $lang[$i]["name"] . "</td>\n";
  echo "  <td nowrap>" . $lang[$i]["extension"] . "</td>\n";
  echo " </tr>\n";
}
echo "</table>";
if (count($lang) == 0) echo "<br><center><b><font color=\"#ff0000\">NENHUMA LINGUAGEM CADASTRADA</font></b></center>";

?>

<br><br><center><b>Clique no número de uma linguagem para excluí-la.<br>
ATENÇÃO: excluir uma linguagem removerá todos os dados relacionados.<br>
Não é recomendado alterar esses dados enquanto a prova estiver em andamento.<br>
</b></center>

<form name="form1" enctype="multipart/form-data" method="post" action="language.php">
  <input type=hidden name="confirmation" value="noconfirm" />
  <script language="javascript">
    function conf() {
      if (confirm("Confirmar?")) {
        document.form1.confirmation.value='confirm';
      }
    }
  </script>
  <center>
<b>Para criar ou editar uma linguagem, informe os dados abaixo.<br>
As alterações substituirão os dados já cadastrados.<br><br>
</b>
    <table border="0">
      <tr>
        <td width="35%" align=right>Número:</td>
        <td width="65%">
          <input type="text" name="langnumber" value="" size="20" maxlength="20" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Nome:</td>
        <td width="65%">
          <input type="text" name="langname" value="" size="20" maxlength="20" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Extensão:</td>
        <td width="65%">
          <input type="text" name="langextension" value="" size="20" maxlength="20" />
        </td>
      </tr>
    </table>
  </center>
  <center>
      <button type="submit" name="Submit3" value="Send" onClick="conf()">Enviar</button>
      <input type="reset" name="Submit4" value="Limpar">
  </center>
</form>

</body>
</html>
