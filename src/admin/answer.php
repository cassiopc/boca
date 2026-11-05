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

if(($ct = DBContestInfo($_SESSION["usertable"]["contestnumber"])) == null)
	ForceLoad("$loc/index.php");

if (isset($_GET["delete"]) && is_numeric($_GET["delete"])) {
	$param["number"] = $_GET["delete"];
	if(!DBDeleteAnswer($_SESSION["usertable"]["contestnumber"], $param)) {
		MSGError('Erro ao excluir a resposta');
		LogError('Erro ao excluir a resposta');
	}
	ForceLoad("answer.php");
}

if (isset($_POST["Submit3"]) && isset($_POST["answernumber"]) && is_numeric($_POST["answernumber"]) && isset($_POST["answername"]) &&
    $_POST["answername"] != "" && isset($_POST["answeryes"])) {
	if ($_POST["confirmation"] == "confirm") {
		$param["number"] = $_POST["answernumber"];
		$param["name"] = $_POST["answername"];
		$param["yes"] = $_POST["answeryes"];
		$param["short"] = $_POST["answershort"];
		DBNewAnswer ($_SESSION["usertable"]["contestnumber"],$param);
	}
	ForceLoad("answer.php");
}
?>
<br>
  <script language="javascript">
    function conf() {
      if (confirm("Confirmar?")) {
        document.form1.confirmation.value='confirm';
      }
    }
    function conf2(url) {
      if (confirm("Confirmar a exclusão da resposta e de todos os dados associados, inclusive as submissões?")) {
		  if (confirm("Tem certeza? OS DADOS NÃO PODERÃO SER RECUPERADOS!")) {
			  document.location=url;
		  } else {
			  document.location='answer.php';
		  }
      } else {
        document.location='answer.php';
      }
    }
  </script>
<table width="100%" border=1>
 <tr>
  <td><b>Resposta nº</b></td>
  <td><b>Descrição</b></td>
  <td><b>Nome curto</b></td>
  <td><b>Sim/Não</b></td>
 </tr>
<?php
$ans = DBGetAnswers($_SESSION["usertable"]["contestnumber"]);
$n=0;
for ($i=0; $i<count($ans); $i++) {
    echo " <tr>\n";
    if($ans[$i]["fake"]!="t") {
		if($ans[$i]["number"]>7) {
      echo "  <td nowrap><a href=\"javascript:conf2('answer.php?delete=" . $ans[$i]["number"] . 
	   "')\">" . $ans[$i]["number"] . "</a></td>\n";
		} else 
			echo "  <td nowrap>".$ans[$i]["number"]."</td>\n";
    } else {
      echo "  <td nowrap>".$ans[$i]["number"]." (simulada)</td>\n";
    }
    echo " <td nowrap>" . displayRunAnswer($ans[$i]["desc"]) . "</td>\n";
    
    if ($ans[$i]["short"]=="") echo "  <td nowrap>&lt;VAZIO&gt;</td>\n";
    else echo "  <td nowrap>".$ans[$i]["short"]."</td>\n";
    
    if($ans[$i]["yes"]=="t") echo "  <td nowrap>Sim</td>\n";
    else echo "  <td nowrap>Não</td>\n";
    
    echo " </tr>\n";
    $n++;
}
echo "</table>";
if ($n == 0) echo "<br><center><b><font color=\"#ff0000\">NENHUMA RESPOSTA CADASTRADA</font></b></center>";
?>

<br><br><center><b>Quando permitido, clique no número da resposta para excluí-la.<br>
	Informar o número de uma resposta existente atualizará sua descrição.<br>
	ATENÇÃO: excluir uma resposta removerá todos os dados relacionados, inclusive em outras tabelas.<br>
		Não é recomendado alterar esses dados enquanto a prova estiver em andamento.<br>
	 Para cadastrar uma resposta, informe os dados abaixo.<br>
	 As alterações substituirão os dados já cadastrados.<br><br>
</b>
<form name="form1" enctype="multipart/form-data" method="post" action="answer.php">
  <input type=hidden name="confirmation" value="noconfirm" />
    <table border="0">
      <tr>
        <td width="35%" align=right>Número:</td>
        <td width="65%">
          <input type="text" name="answernumber" value="" size="20" maxlength="20" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Descrição:</td>
        <td width="65%">
          <input type="text" name="answername" value="" size="50" maxlength="50" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Nome curto (geralmente 2 ou 3 letras):</td>
        <td width="65%">
          <input type="text" name="answershort" value="" size="20" maxlength="20" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Tipo:</td>
        <td width="65%">
                <select name="answeryes">
                <option selected value="f">Não</option>
                <option value="t">Sim</option>
                </select>
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
