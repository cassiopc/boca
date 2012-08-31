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

if (isset($_POST["message"]) && isset($_POST["problem"]) && isset($_POST["Submit"]) && is_numeric($_POST["problem"])) {
	if ($_POST["confirmation"] == "confirm") {
		$param['contest']=$_SESSION["usertable"]["contestnumber"];
		$param['site']=$_SESSION["usertable"]["usersitenumber"];
		$param['user']= $_SESSION["usertable"]["usernumber"];
		$param['problem'] = htmlspecialchars($_POST["problem"]);
		$param['question'] = htmlspecialchars($_POST["message"]);
		DBNewClar($param);
	}
	ForceLoad("clar.php");
}
$_SESSION["popuptime"] = time();
?>
<br>
<table width="100%" border=1>
 <tr>
<!--  <td><b>Clar #</b></td>-->
  <td><b>Time</b></td>
  <td><b>Problem</b></td>
<!--  <td><b>Status</b></td>-->
  <td><b>Question</b></td>
  <td><b>Answer</b></td>
 </tr>
<?php
if(($st = DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
	ForceLoad("../index.php");
$clar = DBUserClars($_SESSION["usertable"]["contestnumber"],
					$_SESSION["usertable"]["usersitenumber"],
					$_SESSION["usertable"]["usernumber"]);
for ($i=0; $i<count($clar); $i++) {
  echo " <tr>\n";
//  echo "  <td nowrap>" . $clar[$i]["number"] . "</td>\n";
  echo "  <td nowrap>" . dateconvminutes($clar[$i]["timestamp"]) . "</td>\n";
  echo "  <td nowrap>" . $clar[$i]["problem"] . "</td>\n";
//  echo "  <td nowrap>" . $clar[$i]["status"] . "</td>\n";
  if ($clar[$i]["question"] == "") $clar[$i]["question"] = "&nbsp;";
  echo "  <td>";
//  echo "<pre>" . $clar[$i]["question"] . "</pre>";
  echo "  <textarea name=\"m$i\" cols=\"60\" rows=\"8\" readonly>".$clar[$i]["question"]."</textarea>\n";
  echo "</td>\n";

  if (trim($clar[$i]["answer"]) == "") $clar[$i]["answer"] = "Not answered yet";
  echo "  <td>";
//  echo "  <pre>" . $clar[$i]["answer"] . "</pre>";
  echo "  <textarea name=\"a$i\" cols=\"60\" rows=\"8\" readonly>".$clar[$i]["answer"]."</textarea>\n";
  echo "</td>\n";
  echo " </tr>\n";
}
echo "</table>";
if (count($clar) == 0) echo "<br><center><b><font color=\"#ff0000\">NO CLARIFICATIONS AVAILABLE</font></b></center>";

?>

<br><br><center><b>To submit a clarification, just fill in the following fields</b></center>
<form name="form1" method="post" action="clar.php">
  <input type=hidden name="confirmation" value="noconfirm" />
  <center>
    <table border="0">
      <tr> 
        <td width="13%" align=right>Problem:</td>
        <td width="87%"> 
          <select name="problem" onclick="Arquivo()">
<?php
$prob = DBGetAllProblems($_SESSION["usertable"]["contestnumber"]);
for ($i=0;$i<count($prob);$i++)
	echo "<option value=\"" . $prob[$i]["number"] . "\">" . $prob[$i]["problem"] . "</option>\n";
?>
	  </select>
        </td>
      </tr>
      <tr> 
        <td width="13%" align=right>Clarification:</td>
        <td width="87%">
          <textarea name="message" cols="60" rows="8" maxlength="2000"></textarea>
        </td>
      </tr>
    </table>
  </center>
  <script language="javascript">
    function conf() {
      if (confirm("Confirm clarification?")) {
        document.form1.confirmation.value='confirm';
      }
    }
  </script>
  <center>
      <input type="submit" name="Submit" value="Send" onClick="conf()">
      <input type="reset" name="Submit2" value="Clear">
  </center>
</form>

</body>
</html>
