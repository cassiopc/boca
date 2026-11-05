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

if (isset($_POST["cancel"]) && $_POST["cancel"]=="Cancel")
	ForceLoad("clar.php");

if (isset($_POST["delete"]) && $_POST["delete"]=="Delete" &&
    isset($_POST["sitenumber"]) && isset($_POST["number"]) && is_numeric($_POST["number"]) &&
    is_numeric($_POST["sitenumber"])) {
	if ($_POST["confirmation"]=="confirm") {
	        $sitenumber = myhtmlspecialchars($_POST["sitenumber"]);
	        $number = myhtmlspecialchars($_POST["number"]);

		if (DBClarDelete($number, $sitenumber, $_SESSION["usertable"]["contestnumber"],
	                     $_SESSION["usertable"]["usernumber"], $_SESSION["usertable"]["usersitenumber"]))
			MSGError("Dúvida excluída.");
	}
	ForceLoad("clar.php");
}

if (isset($_POST["answer"]) && isset($_POST["open"]) && $_POST["open"]=="Open the Clar" &&
    isset($_POST["sitenumber"]) && isset($_POST["number"]) && is_numeric($_POST["number"]) &&
    is_numeric($_POST["sitenumber"])) {
	if ($_POST["confirmation"]=="confirm") {
	        $sitenumber = myhtmlspecialchars($_POST["sitenumber"]);
	        $number = myhtmlspecialchars($_POST["number"]);

		if (DBChiefClarGiveUp($number, $sitenumber, $_SESSION["usertable"]["contestnumber"]))
			MSGError("Dúvida devolvida.");
		ForceLoad("clar.php");
	}
}

if (isset($_POST["answer"]) && isset($_POST["Submit"]) && $_POST["Submit"]=="Answer" && is_numeric($_POST["number"]) &&
    isset($_POST["sitenumber"]) && isset($_POST["number"]) && is_numeric($_POST["sitenumber"])) {
	if ($_POST["confirmation"]=="confirm") {

	        $ans = myhtmlspecialchars($_POST["answer"]);
	        $sitenumber = myhtmlspecialchars($_POST["sitenumber"]);
	        $number = myhtmlspecialchars($_POST["number"]);

		if (isset($_POST["answerall"])) $type='all';
		else if (isset($_POST["answersite"])) $type='site';
		else $type = 'none';

                if (trim($ans)=="") {
                        DBClarGiveUp($number, $sitenumber, $_SESSION["usertable"]["contestnumber"],
                                $_SESSION["usertable"]["usernumber"], $_SESSION["usertable"]["usersitenumber"]);
                        MSGError("Dúvida devolvida.");
                } else {
		        DBChiefUpdateClar($_SESSION["usertable"]["contestnumber"],
	                     $_SESSION["usertable"]["usersitenumber"],
	                     $_SESSION["usertable"]["usernumber"],
	                     $sitenumber, $number, $ans, $type);
		}
	}
        ForceLoad("clar.php");
}

if (!isset($_GET["clarnumber"]) || !isset($_GET["clarsitenumber"]) || 
    !is_numeric($_GET["clarnumber"]) || !is_numeric($_GET["clarsitenumber"])) {
	IntrusionNotify("tried to open the admin/claredit.php with wrong parameters.");
	ForceLoad("clar.php");
}

$clarsitenumber = myhtmlspecialchars($_GET["clarsitenumber"]);
$clarnumber = myhtmlspecialchars($_GET["clarnumber"]);

if (($a = DBChiefGetClarToAnswer($clarnumber, $clarsitenumber, 
		$_SESSION["usertable"]["contestnumber"])) === false) {
	MSGError("Outro juiz já selecionou este item.");
	ForceLoad("clar.php");
}

?>
<br><br><center><b>Use os campos abaixo para responder à dúvida:
</b></center>
<form name="form1" method="post" action="claredit.php">
  <input type=hidden name="confirmation" value="noconfirm" />
  <center>
    <table border="0">
      <tr> 
        <td width="20%" align=right><b>Local da dúvida:</b></td>
        <td width="80%"> 
		<input type=hidden name="sitenumber" value="<?php echo $a["sitenumber"]; ?>" />
		<?php echo $a["sitenumber"]; ?>
        </td>
      </tr>
      <tr> 
        <td width="20%" align=right><b>Número da dúvida:</b></td>
        <td width="80%"> 
		<input type=hidden name="number" value="<?php echo $a["number"]; ?>" />
		<?php echo $a["number"]; ?>
        </td>
      </tr>
      <tr> 
        <td width="20%" align=right><b>Tempo da dúvida:</b></td>
        <td width="80%"> 
		<?php echo dateconvminutes($a["timestamp"]); ?>
        </td>
      </tr>
      <tr> 
        <td width="20%" align=right><b>Questão:</b></td>
        <td width="80%"> 
		<?php echo displayClarProblem($a["problemname"], $a["problemnumber"]); ?>
        </td>
      </tr>
      <tr> 
        <td width="20%" align=right><b>Dúvida:</b></td>
        <td width="80%">
          <textarea name="message" readonly cols="60" rows="8"><?php echo unsanitizeText($a["question"]); ?>
	  </textarea>
        </td>
      </tr>
      <tr> 
        <td width="20%" align=right><b>Resposta:</b></td>
        <td width="80%">
          <textarea name="answer" cols="60" rows="8"><?php echo unsanitizeText($a["answer"]); ?></textarea>
        </td>
      </tr>
      <tr> 
        <td width="20%" align=right><b>Responder a todos os usuários deste local</b></td>
        <td width="80%">
          <input class=checkbox type=checkbox <?php if ($a["status"] == "answeredsite") echo "checked"; ?> name="answersite" value="yes">
        </td>
      </tr>
      <tr> 
        <td width="20%" align=right><b>Responder a todos os usuários de todos os locais</b></td>
        <td width="80%">
          <input class=checkbox type=checkbox <?php if ($a["status"] == "answeredall") echo "checked"; ?> name="answerall" value="yes">
        </td>
      </tr>
    </table>
  </center>
  <script language="javascript">
    function conf() {
      if (confirm("Confirmar?")) {
        document.form1.confirmation.value='confirm';
      }
    }
  </script>
  <center>
      <button type="submit" name="Submit" value="Answer" onClick="conf()">Responder</button>
      <button type="submit" name="open" value="Open the Clar" onClick="conf()">Abrir a dúvida</button>
      <button type="submit" name="cancel" value="Cancel">Cancelar</button>
      <button type="submit" name="delete" value="Delete" onClick="conf()">Excluir</button>
      <input type="reset" name="Submit2" value="Limpar">
  </center>
</form>

</body>
</html>
