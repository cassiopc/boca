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

if(($ct = DBContestInfo($_SESSION["usertable"]["contestnumber"])) == null)
	ForceLoad("../index.php");
if(($st = DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
        ForceLoad("../index.php");

if (isset($_GET["delete"]) && is_numeric($_GET["delete"])) {
   DBBkpDelete($_GET["delete"],$_SESSION["usertable"]["usersitenumber"],$_SESSION["usertable"]["contestnumber"], $_SESSION["usertable"]["usernumber"]);
   ForceLoad("files.php");
}

if (isset($_FILES["sourcefile"]) && isset($_POST["Submit"]) && $_FILES["sourcefile"]["name"]!="") {
	if ($_POST["confirmation"] == "confirm") {
		$type=myhtmlspecialchars($_FILES["sourcefile"]["type"]);
		$size=myhtmlspecialchars($_FILES["sourcefile"]["size"]);
		$name=myhtmlspecialchars($_FILES["sourcefile"]["name"]);
		$temp=myhtmlspecialchars($_FILES["sourcefile"]["tmp_name"]);

		if ($size > $ct["contestmaxfilesize"]) {
	                LOGLevel("User {$_SESSION["usertable"]["username"]} tried to submit file " .
			"$name with $size bytes ({$ct["contestmaxfilesize"]} max allowed).", 1);
			MSGError("O arquivo excede o limite de tamanho permitido.");
			ForceLoad("run.php");
		}
		if (!is_uploaded_file($temp) || strlen($name)>100) {
			IntrusionNotify("file upload problem.");
			ForceLoad("../index.php");
		}

		DBNewBkp ($_SESSION["usertable"]["contestnumber"],
	                  $_SESSION["usertable"]["usersitenumber"],
	                  $_SESSION["usertable"]["usernumber"],
			  $name,
			  $temp, $size);
	}
	ForceLoad("files.php");
}
?>
<br>
<table width="100%" border=1>
 <tr>
  <td><b>Backup nº</b></td>
  <td><b>Tempo</b></td>
  <td><b>Arquivo</b></td>
 </tr>
<?php
$run = DBUserBkps($_SESSION["usertable"]["contestnumber"],
		  $_SESSION["usertable"]["usersitenumber"],
		  $_SESSION["usertable"]["usernumber"]);

for ($i=0; $i<count($run); $i++) {
  echo " <tr>\n";
  echo "  <td nowrap><a href=\"javascript:conf2('files.php?delete=" . $run[$i]["number"] .
           "')\">" . $run[$i]["number"] . "</a></td>\n";

  echo "  <td nowrap>" . dateconvsimple($run[$i]["timestamp"]) . "</td>\n";
  echo "<td nowrap><a href=\"../filedownload.php?". filedownload($run[$i]["oid"],$run[$i]["filename"]) . "\">";
  echo $run[$i]["filename"] . "</a>";

  echo "</td>\n";
  echo " </tr>\n";

}
echo "</table>";
if (count($run) == 0) echo "<br><center><b><font color=\"#ff0000\">NENHUM BACKUP DISPONÍVEL</font></b></center>";

?>

<br><br><center><b>Para excluir um arquivo, clique no número. Para baixá-lo, clique no nome.
Para enviar um novo backup, preencha os campos abaixo:</b></center>
<form name="form1" enctype="multipart/form-data" method="post" action="files.php">
<!--<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $ct["contestmaxfilesize"] ?>">-->
  <input type=hidden name="confirmation" value="noconfirm" />
  <center>
    <table border="0">
      <tr> 
        <td width="35%" align=right>Arquivo (sujeito a limite de tamanho):</td>
        <td width="65%">
	  <input type="file" name="sourcefile" size="40">
        </td>
      </tr>
    </table>
  </center>
  <script language="javascript">
    function conf() {
      if (confirm("Confirmar envio?")) {
        document.form1.confirmation.value='confirm';
      }
    }
    function conf2(url) {
      if (confirm("Confirmar?")) {
        document.location=url;
      } else {
        document.location='files.php';
      }
    }
  </script>
  <center>
      <input type="submit" name="Submit" value="Enviar" onClick="conf()">
  </center>
</form>

</body>
</html>
