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

//optionlower.php: parte de baixo da tela de option.php, que eh igual para
//			todos os usuarios
require_once("globals.php");

if(!ValidSession()) {
        InvalidSession("scoretable.php");
        ForceLoad("index.php");
}
$loc = $_SESSION['loc'];

if (isset($_GET["username"]) && isset($_GET["userfullname"]) && isset($_GET["userdesc"]) && 
    isset($_GET["passwordo"]) && isset($_GET["passwordn"])) {
	$username = myhtmlspecialchars($_GET["username"]);
	$userfullname = myhtmlspecialchars($_GET["userfullname"]);
	$userdesc = myhtmlspecialchars($_GET["userdesc"]);
	$passwordo = myhtmlspecialchars($_GET["passwordo"]);
	$passwordn = myhtmlspecialchars($_GET["passwordn"]);
	DBUserUpdate($_SESSION["usertable"]["contestnumber"],
				 $_SESSION["usertable"]["usersitenumber"],
				 $_SESSION["usertable"]["usernumber"],
				 $_SESSION["usertable"]["username"], // $username, but users should not change their names
				 $userfullname,
				 $userdesc,
				 $passwordo,
				 $passwordn);
	ForceLoad("option.php");
}

$a = DBUserInfo($_SESSION["usertable"]["contestnumber"],
                $_SESSION["usertable"]["usersitenumber"],
                $_SESSION["usertable"]["usernumber"]);

?>

<script language="JavaScript" src="<?php echo $loc; ?>/sha256.js"></script>
<script language="JavaScript" src="<?php echo $loc; ?>/hex.js"></script>
<script language="JavaScript">
function computeHASH()
{
	var username, userdesc, userfull, passHASHo, passHASHn1, passHASHn2;
	if (document.form1.passwordn1.value != document.form1.passwordn2.value) return;
	username = document.form1.username.value;
	userdesc = document.form1.userdesc.value;
	userfull = document.form1.userfull.value;

	passMDo = js_myhash(js_myhash(document.form1.passwordo.value)+'<?php echo session_id(); ?>');
	passMDn = bighexsoma(js_myhash(document.form1.passwordn2.value),js_myhash(document.form1.passwordo.value));
	document.form1.passwordo.value = '                                                         ';
	document.form1.passwordn1.value = '                                                         ';
	document.form1.passwordn2.value = '                                                         ';
	document.location='option.php?username='+username+'&userdesc='+userdesc+'&userfullname='+userfull+'&passwordo='+passMDo+'&passwordn='+passMDn;
}
</script>

<br><br>
<form name="form1" action="javascript:computeHASH()">
  <center>
    <table border="0">
      <tr> 
        <td width="35%" align=right>Username:</td>
        <td width="65%">
	  <input type="text" readonly name="username" value="<?php echo $a["username"]; ?>" size="20" maxlength="20" />
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>User Full Name:</td>
        <td width="65%">
	  <input type="text" readonly name="userfull" value="<?php echo $a["userfullname"]; ?>" size="50" maxlength="50" />
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>User Description:</td>
        <td width="65%">
	  <input type="text" name="userdesc" value="<?php echo $a["userdesc"]; ?>" size="50" maxlength="250" />
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>Old Password:</td>
        <td width="65%">
	  <input type="password" name="passwordo" size="20" maxlength="20" />
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>New Password:</td>
        <td width="65%">
	  <input type="password" name="passwordn1" size="20" maxlength="20" />
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>Retype New Password:</td>
        <td width="65%">
	  <input type="password" name="passwordn2" size="20" maxlength="20" />
        </td>
      </tr>
    </table>
  </center>
  <center>
      <input type="submit" name="Submit" value="Send">
  </center>
</form>

</body>
</html>
