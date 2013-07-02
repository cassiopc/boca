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

if (isset($_POST["answer"]) && isset($_POST["giveup"]) && $_POST["giveup"]=="Cancel" &&
    isset($_POST["sitenumber"]) && isset($_POST["number"]) && is_numeric($_POST["number"]) &&
    is_numeric($_POST["sitenumber"])) {

        $sitenumber = myhtmlspecialchars($_POST["sitenumber"]);
        $number = myhtmlspecialchars($_POST["number"]);

	DBClarGiveUp($number, $sitenumber, $_SESSION["usertable"]["contestnumber"], 
			$_SESSION["usertable"]["usernumber"], $_SESSION["usertable"]["usersitenumber"]);
	MSGError("Clarification returned.");
	ForceLoad("clar.php");
}

if (isset($_POST["answer"]) && isset($_POST["Submit"]) && ($_POST["Submit"]=="Answer" || $_POST["Submit"]=="No response") && 
    is_numeric($_POST["number"]) && isset($_POST["sitenumber"]) && isset($_POST["number"]) && is_numeric($_POST["sitenumber"])) {
	if ($_POST["confirmation"]=="confirm") {

	        $ans = myhtmlspecialchars($_POST["answer"]);
	        $sitenumber = myhtmlspecialchars($_POST["sitenumber"]);
	        $number = myhtmlspecialchars($_POST["number"]);

		if ($_POST["Submit"]=="No response")
			$ans='No response. '.$ans;

		if (trim($ans)=="") {
			DBClarGiveUp($number, $sitenumber, $_SESSION["usertable"]["contestnumber"], 
				$_SESSION["usertable"]["usernumber"], $_SESSION["usertable"]["usersitenumber"]);
			MSGError("Clarification returned.");
		} else {
			if (isset($_POST["answerall"])) $type='all';
			else if (isset($_POST["answersite"])) $type='site';
			else $type = 'none';

	        	DBUpdateClar($_SESSION["usertable"]["contestnumber"],
	                     $_SESSION["usertable"]["usersitenumber"],
	                     $_SESSION["usertable"]["usernumber"],
	                     $sitenumber, $number, $ans, $type);
		}
	}
        ForceLoad("clar.php");
}

if (!isset($_GET["clarnumber"]) || !isset($_GET["clarsitenumber"]) || 
    !is_numeric($_GET["clarnumber"]) || !is_numeric($_GET["clarsitenumber"])) {
	IntrusionNotify("tried to open the judge/claredit.php with wrong parameters.");
	ForceLoad("clar.php");
}

$clarsitenumber = myhtmlspecialchars($_GET["clarsitenumber"]);
$clarnumber = myhtmlspecialchars($_GET["clarnumber"]);

if (($a = DBGetClarToAnswer($clarnumber, $clarsitenumber, 
		$_SESSION["usertable"]["contestnumber"])) === false) {
	MSGError("Another judge got it first.");
	ForceLoad("clar.php");
}

?>
<br><br><center><b>Use the following fields to answer the clarification:
</b></center>
<form name="form1" method="post" action="claredit.php">
  <input type=hidden name="confirmation" value="noconfirm" />
  <center>
    <table border="0">
      <tr> 
        <td width="20%" align=right><b>Clarification Site:</b></td>
        <td width="80%"> 
		<input type=hidden name="sitenumber" value="<?php echo $a["sitenumber"]; ?>" />
		<?php echo $a["sitenumber"]; ?>
        </td>
      </tr>
      <tr> 
        <td width="20%" align=right><b>Clarification Number:</b></td>
        <td width="80%"> 
		<input type=hidden name="number" value="<?php echo $a["number"]; ?>" />
		<?php echo $a["number"]; ?>
        </td>
      </tr>
      <tr> 
        <td width="20%" align=right><b>Clarification Time:</b></td>
        <td width="80%"> 
		<?php echo dateconvminutes($a["timestamp"]); ?>
        </td>
      </tr>
      <tr> 
        <td width="20%" align=right><b>Problem:</b></td>
        <td width="80%"> 
		<?php echo $a["problemname"]; ?>
        </td>
      </tr>
      <tr> 
        <td width="20%" align=right><b>Clarification:</b></td>
        <td width="80%">
          <textarea name="message" readonly cols="60" rows="8"><?php echo $a["question"]; ?></textarea>
        </td>
      </tr>
      <tr> 
        <td width="20%" align=right><b>Answer:</b></td>
        <td width="80%">
          <textarea name="answer" cols="60" rows="8"><?php echo $a["answer"]; ?></textarea>
        </td>
      </tr>
      <tr> 
        <td width="20%" align=right><b>Answer to all users in the site</b></td>
        <td width="80%">
          <input class=checkbox type=checkbox name="answersite" value="yes">
        </td>
      </tr>
<!--
      <tr> 
        <td width="20%" align=right><b>Answer to all users in all sites</b></td>
        <td width="80%">
          <input class=checkbox type=checkbox name="answerall" value="yes">
        </td>
      </tr>
-->
    </table>
  </center>
  <script language="javascript">
    function conf() {
//      if (confirm("Confirm clarification?")) {
        document.form1.confirmation.value='confirm';
//      }
    }
  </script>
  <center>
      <input type="submit" name="Submit" value="Answer" onclick="conf()">
      <input type="submit" name="Submit" value="No response" onclick="conf()">
      <input type="submit" name="giveup" value="Cancel">
      <input type="reset" name="Submit2" value="Clear">
  </center>
</form>

</body>
</html>
