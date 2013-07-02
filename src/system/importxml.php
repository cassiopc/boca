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
$id = '';

if(isset($_POST["Submit"])) {
  if(isset($_SESSION["importfile"])) {
	  $importfile = $_SESSION['importfile'];
  }
  if (isset($_FILES["importfile"]) && $_FILES["importfile"]["name"]!="") {
	  $importfile = $_FILES["importfile"];
  }
  if(isset($importfile)) {
	  $_SESSION['importfile'] = $importfile;

	if ($_POST["confirmation"] == "confirm") {
		$type=htmlspecialchars($importfile["type"]);
		$size=htmlspecialchars($importfile["size"]);
		$name=htmlspecialchars($importfile["name"]);
		$temp=htmlspecialchars($importfile["tmp_name"]);
		if(isset($importfile['filecontent']))
			$ar = $importfile['filecontent'];
		else {
			if (!is_uploaded_file($temp)) {
				IntrusionNotify("file upload problem.");
				ForceLoad("../index.php");
			}
			if (($ar = file($temp)) === false) {
				IntrusionNotify("Unable to open the uploaded file.");
				ForceLoad("../index.php");
			}
			$ar=implode('',$ar);
			$_SESSION['importfile']['filecontent']=$ar;
		}
		$localsite=0;
		if(isset($_POST['localsite']) && is_numeric($_POST['localsite'])) $localsite=$_POST['localsite'];
		$acr['CONTESTREC']=array('number'=>-1, 
								 'name'=>-1, 
								 'startdate'=>-1, 
								 'duration'=>-1, 
								 'lastmileanswer'=>-1,
								 'lastmilescore'=>-1,
								 'localsite'=>-1,
								 'penalty'=>-1,
								 'maxfilesize'=>-1,
								 'updatetime'=>-1);
		if($localsite > 0)
			$acr['CONTESTREC']['localsite'] = "" . $localsite;

		$acr['ANSWERREC']=array('number'=>-1,
							'name'=>-1,
							'yes'=>-1,
							'updatetime'=>-1);
		$acr['LANGUAGEREC']=array('number'=>-1,
							  'name'=>-1,
							  'filepath'=>-1,
							  'filename'=>-1,
							  'comppath'=>-1,
							  'compname'=>-1,
							  'problemnumber'=>-1,
							  'updatetime'=>-1);
		$acr['PROBLEMREC']=array('number'=>-1,
							  'name'=>-1,
							  'fullname'=>-1,
							  'basename'=>-1,
							  'inputfilename'=>-1,
							  'inputfilepath'=>-1,
							  'solfilename'=>-1,
							  'solfilepath'=>-1,
							  'descfilename'=>-1,
							  'descfilepath'=>-1,
							  'tl'=>-1,
							  'colorname'=>-1,
							  'color'=>-1,
							  'fake'=>-1,
							  'updatetime'=>-1);
		$acr['SITETIME']=array('site'=>-1,
							   'start'=>-1,
							   'enddate'=>-1,
							   'updatetime'=>-1);
		$acr['SITEREC']=array('sitenumber'=>-1,
							  'site'=>-1,
							  'number'=>-1,
							  'sitename'=>-1,
							  'siteip'=>-1,
							  'siteduration'=>-1,
							  'sitelastmileanswer'=>-1,
							  'sitelastmilescore'=>-1,
							  'sitejudging'=>-1,
							  'sitetasking'=>-1,
							  'siteautoend'=>-1,
							  'siteglobalscore'=>-1,
							  'siteactive'=>-1,
							  'sitescorelevel'=>-1,
							  'sitepermitlogins'=>-1,
							  'siteautojudge'=>-1,
							  'sitenextuser'=>-1,
							  'sitenextclar'=>-1,
							  'sitenextrun'=>-1,
							  'sitenexttask'=>-1,
							  'sitemaxtask'=>-1,
							  'sitechiefname'=>-1,
							  'updatetime'=>-1);
		$acr['USERREC']=array('site'=>-1,
						   'user'=>-1,
						   'number'=>-1,
						   'username'=>-1,
						   'updatetime'=>-1,
						   'usericpcid'=>-1,
						   'userfull'=>-1,
						   'userdesc'=>-1,
						   'type'=>-1,
						   'enabled'=>-1,
						   'multilogin'=>-1,
						   'userip'=>-1,
						   'userlastlogin'=>-1,
						   'userlastlogout'=>-1,
						   'permitip'=>-1);

		if(strtoupper(substr($ar,0,5)) != '<XML>' && isset($_POST['password']) && strlen($_POST['password'])>20) {
			echo "<br>Starting to create the contest<br>";
			$str = strtok($ar," \n\t");
			$str = strtok(" \n\t");
			$ar = decryptData($str,$_POST['password'],'importxml');
			if(strtoupper(substr($ar,0,5)) != '<XML>') {
				echo "<br>Error decrypting file. Import aborted.<br>";
				echo "</body></html>";
				exit;
			}
		}
		if(strtoupper(substr($ar,0,5)) == '<XML>') {
			echo "<br>File has been loaded.<br>";
//			echo "<pre>\n$ar</pre>\n";
			if(!importFromXML($ar,$acr,0,$localsite))
				echo "<br>Error during updating of the local database.<br>";
			echo "</body></html>";
			exit;
		}
		else
			$id = rawurldecode(strtok($ar," \n\t"));
	}
  }
} else {
	unset($_POST['localsite']);
	unset($_SESSION['importfile']);
}
?>
<br>
<br>
<center><b>
To import a pre-defined contest, just fill in the import file field.</b></center>
<br>
<body onload="document.form1.name.focus()">
<script language="JavaScript" src="../sha256.js"></script>
<script language="JavaScript">
function computeHASH()
{
	var passHASH;
    passHASH = '';
	<?php if(strlen($id) > 0) { ?>
	passHASH = js_myhash(js_myhash(document.form1.password.value)+'<?php echo $id; ?>');
    <?php } ?>
	document.form1.password.value = passHASH;
}
</script>

<form name="form1" enctype="multipart/form-data" method="post" action="importxml.php">
  <input type=hidden name="confirmation" value="noconfirm" />
  <center>
    <table border="0">
      <tr>
        <td width="50%" align=right>Local site number:</td>
        <td width="50%">
<?php
	if(isset($_POST["localsite"])) {
	echo $_POST['localsite'];
    echo "<input type=\"hidden\" name=\"localsite\" size=\"10\" value=\"" .$_POST['localsite']. "\">";
	} else
	echo "<input type=\"text\" name=\"localsite\" size=\"10\">";
?>
        </td>
      </tr>
<?php
		if(isset($_SESSION['importfile'])) {
			echo "<tr><td width=\"50%\" align=right>Challenge string:</td><td width=\"50%\">" . $id . "</td></tr>\n";
		} else {
?>
      <tr>
        <td width="50%" align=right>Import file:</td>
        <td width="50%">
				<input type="file" name="importfile" size="40">
        </td>
      </tr>
<?php
			  }
	if($id == '') {
		echo "<input type=\"hidden\" name=\"password\">";
	} else {
?>
	<tr> 
	 <td width="50%" align=right>Encryption key:</td>
	<td width="50%">
	<input type="password" name="password">
	</td>
	</tr>
<?php
			}
?>
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
      <input type="submit" name="Submit" value="Import" onClick="conf()">
      <input type="reset" name="Submit2" value="Clear">
  </center>
</form>

</body>
</html>
