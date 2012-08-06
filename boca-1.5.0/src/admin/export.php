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
//Change list 
// 15/June/2011 by cassio@ime.usp.br: created based on import.php
require('header.php');

if(isset($_POST["Submit"]) || isset($_POST['Submit1'])) {
	if ($_POST["confirmation"] == "confirm" && isset($_POST['localsite']) && is_numeric($_POST['localsite']) && 
		isset($_POST['challenge']) && isset($_POST['password'])) {
		$localsite=$_POST['localsite'];
		
		header ("Content-transfer-encoding: binary\n");
		header ("Content-type: application/force-download");
//header ("Content-type: application/octet-stream");
//if (strstr($_SERVER["HTTP_USER_AGENT"], "MSIE"))
//	header("Content-Disposition: filename=" .$_GET["filename"]); // For IE
//else
		header ("Content-Disposition: attachment; filename=export.dat");
		ob_end_flush();
		$reduced = false;
		if(isset($_POST["Submit"]) && $_POST['Submit']=="Reduced Export") {
			$reduced = true;
		}
		
		$fromsite = $localsite;
		$siteinfo = DBSiteInfo($_SESSION["usertable"]["contestnumber"],$fromsite);
		$scores = explode(",", $siteinfo['siteglobalscore']);
		if(count($scores)==0 || (count($scores)==1 && !is_numeric($scores[0]))) $scores=array($fromsite);
		$judges = explode(",", $siteinfo['sitejudging']);
		if(count($judges)==0 || (count($judges)==1 && !is_numeric($judges[0]))) $judges=array($fromsite);
		$scores = array_unique(array_merge($scores,$judges));
		if(in_array(0,$scores)) $scores=null;

		$xml = generateXML($_SESSION["usertable"]["contestnumber"],$localsite,$scores,$reduced);
		if(isset($_POST['nopassword']) && $_POST['nopassword']=='true')
			echo $xml;
		else
			echo rawurlencode($_POST['challenge']) . " " . encryptData($xml,($_POST['password']));
		exit;
	}
}
ob_end_flush();
?>
<br>
<body onload="document.form1.name.focus()">
<script language="JavaScript" src="../sha256.js"></script>
<script language="JavaScript">
function computeHASH()
{
	var passHASH;
    if(document.form1.password.value == '') {
		document.form1.nopassword.value = 'true';
	} else {
		passHASH = js_myhash(js_myhash(document.form1.password.value)+document.form1.challenge.value);
		document.form1.password.value = passHASH;
	}
}
</script>

<form name="form1" enctype="multipart/form-data" method="post" action="export.php">
  <input type="hidden" name="confirmation" value="noconfirm" />
  <input type="hidden" name="noflush" value="noflush" />
  <input type="hidden" name="nopassword" value="false" />
  <center>
    <table border="0">
      <tr>
        <td width="50%" align=right>Local site number:</td>
        <td width="50%">
	<input type="text" name="localsite" size="10">
        </td>
      </tr>
      <tr><td width="50%" align=right>Challenge string:</td><td width="50%"><input type="text" name="challenge" size="20"></td></tr>
<!--
      <tr>
        <td width="50%" align=right>Import file:</td>
        <td width="50%">
				<input type="file" name="importfile" size="40">
        </td>
      </tr>
-->
	<tr> 
	 <td width="50%" align=right>Encryption key:</td>
	<td width="50%">
	<input type="password" name="password">
	</td>
	</tr>
    </table>
  </center>
  <script language="javascript">
    function conf() {
      if (confirm("Confirm?")) {
  	    computeHASH();
        document.form1.confirmation.value='confirm';
      }
    }
  </script>
  <center>
      <input type="submit" name="Submit" value="Reduced Export" onClick="conf()">
      <input type="submit" name="Submit1" value="Full Export" onClick="conf()">
      <input type="reset" name="Submit2" value="Clear">
  </center>
</form>

</body>
</html>
