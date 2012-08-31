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
		MSGError('Error deleting answer');
		LogError('Error deleting answer');
	}
	ForceLoad("answer.php");
}

if (isset($_POST["Submit3"]) && isset($_POST["answernumber"]) && is_numeric($_POST["answernumber"]) && isset($_POST["answername"]) &&
    $_POST["answername"] != "" && isset($_POST["answeryes"])) {
	if ($_POST["confirmation"] == "confirm") {
		$param["number"] = $_POST["answernumber"];
		$param["name"] = $_POST["answername"];
		$param["yes"] = $_POST["answeryes"];
		DBNewAnswer ($_SESSION["usertable"]["contestnumber"],$param);
	}
	ForceLoad("answer.php");
}
?>
<br>
  <script language="javascript">
    function conf() {
      if (confirm("Confirm?")) {
        document.form1.confirmation.value='confirm';
      }
    }
    function conf2(url) {
      if (confirm("Confirm the DELETION of the ANSWER and ALL data associated to it (including the SUBMISSIONS)?")) {
		  if (confirm("Are you REALLY sure about what you are doing? DATA CANNOT BE RECOVERED!")) {
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
  <td><b>Answer #</b></td>
  <td><b>Description</b></td>
  <td><b>Yes/No</b></td>
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
      echo "  <td nowrap>".$ans[$i]["number"]." (fake)</td>\n";
    }
    echo " <td nowrap>" . $ans[$i]["desc"] . "</td>\n";
    if($ans[$i]["yes"]=="t") echo "  <td nowrap>Yes</td>\n";
    else echo "  <td nowrap>No</td>\n";
    echo " </tr>\n";
    $n++;
}
echo "</table>";
if ($n == 0) echo "<br><center><b><font color=\"#ff0000\">NO ANSWERS DEFINED</font></b></center>";
?>

<br><br><center><b>When allowed, clicking on the answer number will delete it.<br>
	Inputting with the same number of an existing one will update its description.<br>
	TAKE CARE: deleting an answer will remove EVERYTHING related to it (ALSO IN OTHER TABLES!!).<br>
		It is NOT recommended to change anything while the contest is running.<br>
	 To insert a new answer, enter the data below.<br>
	 Note that any changes will overwrite the already defined data.<br><br>
</b>
<form name="form1" enctype="multipart/form-data" method="post" action="answer.php">
  <input type=hidden name="confirmation" value="noconfirm" />
    <table border="0">
      <tr>
        <td width="35%" align=right>Number:</td>
        <td width="65%">
          <input type="text" name="answernumber" value="" size="20" maxlength="20" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Description:</td>
        <td width="65%">
          <input type="text" name="answername" value="" size="50" maxlength="50" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Type:</td>
        <td width="65%">
                <select name="answeryes">
                <option selected value="f">No</option>
                <option value="t">Yes</option>
                </select>
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
