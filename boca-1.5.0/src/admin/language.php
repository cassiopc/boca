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

if (isset($_GET["delete"]) && is_numeric($_GET["delete"])) {
	$param["number"] = $_GET["delete"];
	DBDeleteLanguage ($_SESSION["usertable"]["contestnumber"],$param);
	ForceLoad("language.php");
}

if (isset($_POST["Submit3"]) && isset($_POST["langnumber"]) && is_numeric($_POST["langnumber"]) && 
    isset($_POST["langname"]) && $_POST["langname"] != "") {
	if ($_POST["confirmation"] == "confirm") {
		$param = array();
		$param['number'] = $_POST['langnumber'];
		$param['name'] = $_POST['langname'];
		$param['extension'] = $_POST['langextension'];
		DBNewLanguage ($_SESSION["usertable"]["contestnumber"], $param);
	}
	ForceLoad("language.php");
}
?>
<br>
  <script language="javascript">
    function conf2(url) {
      if (confirm("Confirm the DELETION of the LANGUAGE and ALL data associated to it (including the SUBMISSIONS)?")) {
		  if (confirm("Are you REALLY sure about what you are doing? DATA CANNOT BE RECOVERED!")) {
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
  <td><b>Language #</b></td>
  <td><b>Name</b></td>
  <td><b>Extension</b></td>
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
if (count($lang) == 0) echo "<br><center><b><font color=\"#ff0000\">NO LANGUAGES DEFINED</font></b></center>";

?>

<br><br><center><b>Clicking on a language number will DELETE it.<br>
WARNING: deleting a language will remove EVERYTHING related to it.<br>
It is NOT recommended to change anything while the contest is running.<br>
</b></center>

<form name="form1" enctype="multipart/form-data" method="post" action="language.php">
  <input type=hidden name="confirmation" value="noconfirm" />
  <script language="javascript">
    function conf() {
      if (confirm("Confirm?")) {
        document.form1.confirmation.value='confirm';
      }
    }
  </script>
  <center>
<b>To insert/edit a language, enter the data below.<br>
Note that any changes will overwrite the already defined data.<br><br>
</b>
    <table border="0">
      <tr>
        <td width="35%" align=right>Number:</td>
        <td width="65%">
          <input type="text" name="langnumber" value="" size="20" maxlength="20" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Name:</td>
        <td width="65%">
          <input type="text" name="langname" value="" size="20" maxlength="20" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Extension:</td>
        <td width="65%">
          <input type="text" name="langextension" value="" size="20" maxlength="20" />
        </td>
      </tr>
    </table>
  </center>
  <center>
      <input type="submit" name="Submit3" value="Send" onClick="conf()">
      <input type="reset" name="Submit4" value="Clear">
  </center>
</form>

</body>
</html>
