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
	ForceLoad("../index.php");
if(($st = DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
        ForceLoad("../index.php");

if (isset($_POST["Submit"]) && $_POST["Submit"]=="S.O.S.") {
	if ($_POST["confirmation"] == "confirm") {
		$param['contest']=$_SESSION["usertable"]["contestnumber"];
		$param['site']=$_SESSION["usertable"]["usersitenumber"];
		$param['user']=$_SESSION["usertable"]["usernumber"];
		$param['desc']=  "Staff assistance";
		DBNewTask ($param);
	}
	ForceLoad("task.php");
}
if (isset($_FILES["filename"]) && isset($_POST["Submit"]) && $_FILES["filename"]["name"]!="") {
	if ($_POST["confirmation"] == "confirm") {
		$type=myhtmlspecialchars($_FILES["filename"]["type"]);
		$size=myhtmlspecialchars($_FILES["filename"]["size"]);
		$name=myhtmlspecialchars($_FILES["filename"]["name"]);
		$temp=myhtmlspecialchars($_FILES["filename"]["tmp_name"]);

		if ($size > $ct["contestmaxfilesize"]) {
	                LOGLevel("User {$_SESSION["usertable"]["username"]} tried to print file " .
			"$name with $size bytes ({$ct["contestmaxfilesize"]} max allowed).", 1);
			MSGError("O arquivo excede o limite de tamanho permitido.");
			ForceLoad("task.php");
		}
		if (!is_uploaded_file($temp)) {
			IntrusionNotify("Printing file upload problem");
			ForceLoad("../index.php");
		}
		$param['contest']=$_SESSION["usertable"]["contestnumber"];
		$param['site']=$_SESSION["usertable"]["usersitenumber"];
		$param['user']=$_SESSION["usertable"]["usernumber"];
		$param['desc']= "File to print";
		$param['filename']=$name;
		$param['filepath']=$temp;
		DBNewTask ($param);
	}
	ForceLoad("task.php");
}
?>
<br>
<table width="100%" border=1>
 <tr>
  <td><b>Pedido nº</b></td>
  <td><b>Tempo</b></td>
  <td><b>Descrição</b></td>
  <td><b>Arquivo</b></td>
  <td><b>Status</b></td>
 </tr>
<?php
$task = DBUserTasks($_SESSION["usertable"]["contestnumber"],
		  $_SESSION["usertable"]["usersitenumber"],
		  $_SESSION["usertable"]["usernumber"]);
for ($i=0; $i<count($task); $i++) {
  echo " <tr>\n";
  echo "  <td nowrap>" . $task[$i]["number"] . "</td>\n";
  echo "  <td nowrap>" . dateconvminutes($task[$i]["timestamp"]) . "</td>\n";
  echo "  <td nowrap>" . displayTaskDescription($task[$i]["description"]) . "</td>\n";
  echo "  <td nowrap>" . $task[$i]["filename"] . "&nbsp;</td>\n";
  echo "  <td nowrap>" . displayTaskStatus($task[$i]["status"]) . "</td>\n";
}
echo "</table>";
if (count($task) == 0) echo "<br><center><b><font color=\"#ff0000\">NENHUM PEDIDO ENCONTRADO</font></b></center>";
?>

<br><br><center><b>Para enviar um arquivo para impressão, selecione-o abaixo:</b></center>
<form name="form1" enctype="multipart/form-data" method="post" action="task.php">
<!--<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $ct["contestmaxfilesize"] ?>">-->
  <input type=hidden name="confirmation" value="noconfirm" />
  <center>
    <table border="0">
      <tr> 
        <td width="25%" align=right>Arquivo:</td>
        <td width="75%">
	  <input type="file" name="filename" size="40" onclick="Arquivo()">
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
      <input type="submit" name="Submit" value="Enviar" onClick="conf()">
      <input type="reset" name="Submit2" value="Limpar">
  </center>
<br><br><center><b>Se precisar de ajuda da organização, clique no botão abaixo e aguarde.</b><br>
      <input type="submit" name="Submit" value="S.O.S." onClick="conf()">
  </center>
</form>

</body>
</html>
