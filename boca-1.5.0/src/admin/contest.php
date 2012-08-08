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
// Last modified 21/jul/2012 by cassio@ime.usp.br
require 'header.php';

$contest=$_SESSION["usertable"]["contestnumber"];

if(($ct = DBContestInfo($contest)) == null)
	ForceLoad("$loc/index.php");
if ($ct["contestlocalsite"]==$ct["contestmainsite"]) $main=true; else $main=false;

if (isset($_POST["Submit3"]) && isset($_POST["penalty"]) && is_numeric($_POST["penalty"]) && 
    isset($_POST["maxfilesize"]) && isset($_POST["mainsite"]) && isset($_POST["name"]) && 
    $_POST["name"] != "" && isset($_POST["lastmileanswer"]) && is_numeric($_POST["lastmileanswer"]) && 
    is_numeric($_POST["mainsite"]) && isset($_POST["lastmilescore"]) && is_numeric($_POST["lastmilescore"]) && 
    isset($_POST["duration"]) && is_numeric($_POST["duration"]) && isset($_POST['localsite']) &&
    isset($_POST["startdateh"]) && $_POST["startdateh"] >= 0 && $_POST["startdateh"] <= 23 && 
    isset($_POST["startdatemin"]) && $_POST["startdatemin"] >= 0 && $_POST["startdatemin"] <= 59 &&
    isset($_POST["startdated"]) && isset($_POST["startdatem"]) && isset($_POST["startdatey"]) && 
    checkdate($_POST["startdatem"], $_POST["startdated"], $_POST["startdatey"])) {
	if ($_POST["confirmation"] == "confirm") {
		$param['number']=$contest;
		if($_POST["Submit3"] == "Become Main Site") {
			$param['mainsite']=$ct["contestlocalsite"];
		} else {
			$at = false;
			if(!is_numeric($_POST['localsite']) || $_POST['localsite']<=0) $_POST['localsite']=-1;
			if($_POST["Submit3"] == "Update Contest and All Sites") $at = true;
			$t = mktime ($_POST["startdateh"], $_POST["startdatemin"], 0, 
						 $_POST["startdatem"], $_POST["startdated"], $_POST["startdatey"]);
			$param['localsite']=$_POST['localsite'];
			$param['name']=$_POST["name"];
			$param['startdate']=$t;
			$param['duration']=$_POST["duration"]*60;
			$param['lastmileanswer']=$_POST["lastmileanswer"]*60;
			$param['lastmilescore']= $_POST["lastmilescore"]*60;
			$param['penalty']=$_POST["penalty"]*60;
			$param['maxfilesize']=$_POST["maxfilesize"]*1000;
			$param['active']=0;
			$param['mainsite']=$_POST["mainsite"];
			$param['mainsiteurl']=$_POST["mainsiteurl"];
			$param['unlockkey']=$_POST["unlockkey"];
			
			if (isset($_FILES["keyfile"]) && $_FILES["keyfile"]["name"]!="") {
                $type=myhtmlspecialchars($_FILES["keyfile"]["type"]);
                $size=myhtmlspecialchars($_FILES["keyfile"]["size"]);
                $name=myhtmlspecialchars($_FILES["keyfile"]["name"]);
                $temp=myhtmlspecialchars($_FILES["keyfile"]["tmp_name"]);
                if (!is_uploaded_file($temp)) {
					IntrusionNotify("file upload problem.");
					ForceLoad("../index.php");
                }
                if (($ar = file($temp)) === false) {
					IntrusionNotify("Unable to open the uploaded file.");
					ForceLoad("user.php");
                }
				$dd=0;
				foreach($ar as $val => $key) {
					$key=trim($key);
					if($key=='') {
						unset($ar[$val]);
						continue;
					}
					if(substr($key,10,5) != '#####') {
						MSGError('Invalid key in the file -- not importing any keys');
						$dd=0;
						break;
					}
					if(isset($param['unlockkey']) && $param['unlockkey'] != '') {
						$pass=decryptData(substr($key,15),$param['unlockkey'],'includekeys');
						if(substr($pass,0,5) != '#####') {
							MSGError('Invalid key in the file -- not importing any keys');
							$dd=0;
							break;
						}
					}
					$ar[$val]=$key;
					$dd++;
				}
				if($dd > 0) {
					$param['keys']=implode(',',$ar);
					MSGError(count($ar) . ' keys are being imported from the file');
					DBClearProblemTmp($_SESSION["usertable"]["contestnumber"]);
				}
			}
			$param['atualizasites']=$at;
		}
		DBUpdateContest ($param);
		if(strlen($param['unlockkey'])>1) {
			DBClearProblemTmp($_SESSION["usertable"]["contestnumber"]);
			DBGetFullProblemData($_SESSION["usertable"]["contestnumber"],true);
		}
	}
	ForceLoad("contest.php");
}
?>
<br>

<form name="form1" enctype="multipart/form-data" method="post" action="contest.php">
  <input type=hidden name="confirmation" value="noconfirm" />
  <script language="javascript">
    function conf() {
      if (confirm("Confirm?")) {
        document.form1.confirmation.value='confirm';
      }
    }
    function conf2() {
      if (confirm("This will restart all start/stop related information in all the sites.\n\
If you have a contest running, the result is unpredictable. Are you really sure?")) {
        document.form1.confirmation.value='confirm';
      }
    }
    function conf3() {
      if (confirm("This will make your site become the main site, that is, this site will\n\
play an active position in the contest regarding the information\n\
flow. ARE YOU SURE?")) {
        document.form1.confirmation.value='confirm';
      }
    }
  </script>
  <br><br>
  <center>
    <table border="0">
      <tr>
        <td width="35%" align=right>Contest number:</td>
        <td width="65%">
<?php 
echo $contest;
?>
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Name:</td>
        <td width="65%">
          <input type="text" <?php if(!$main) echo "readonly"; ?> name="name" value="<?php echo $ct["contestname"]; ?>" size="50" maxlength="50" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Start date:</td>
        <td width="65%"> hh:mm
          <input type="text" <?php if(!$main) echo "readonly"; ?> name="startdateh" value="<?php echo date("H", $ct["conteststartdate"]); ?>" size="2" maxlength="2" />
          :
          <input type="text" <?php if(!$main) echo "readonly"; ?> name="startdatemin" value="<?php echo date("i", $ct["conteststartdate"]); ?>" size="2" maxlength="2" />
          &nbsp; &nbsp; dd/mm/yyyy
          <input type="text" <?php if(!$main) echo "readonly"; ?> name="startdated" value="<?php echo date("d", $ct["conteststartdate"]); ?>" size="2" maxlength="2" />
          /
          <input type="text" <?php if(!$main) echo "readonly"; ?> name="startdatem" value="<?php echo date("m", $ct["conteststartdate"]); ?>" size="2" maxlength="2" />
          /
          <input type="text" <?php if(!$main) echo "readonly"; ?> name="startdatey" value="<?php echo date("Y", $ct["conteststartdate"]); ?>" size="4" maxlength="4" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Duration (in minutes):</td>
        <td width="65%">
          <input type="text" name="duration" <?php if(!$main) echo "readonly"; ?> value="<?php echo $ct["contestduration"]/60; ?>" size="20" maxlength="20" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Stop answering (in minutes):</td>
        <td width="65%">
          <input type="text" name="lastmileanswer" <?php if(!$main) echo "readonly"; ?> value="<?php echo $ct["contestlastmileanswer"]/60; ?>" size="20" maxlength="20" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Stop scoreboard (in minutes):</td>
        <td width="65%">
          <input type="text" name="lastmilescore" <?php if(!$main) echo "readonly"; ?> value="<?php echo $ct["contestlastmilescore"]/60; ?>" size="20" maxlength="20" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Penalty (in minutes):</td>
        <td width="65%">
          <input type="text" name="penalty" <?php if(!$main) echo "readonly"; ?> value="<?php echo $ct["contestpenalty"]/60; ?>" size="20" maxlength="20" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Max file size allowed for teams (in KB):</td>
        <td width="65%">
          <input type="text" name="maxfilesize" <?php if(!$main) echo "readonly"; ?> 
		value="<?php echo $ct["contestmaxfilesize"]/1000; ?>" size="20" maxlength="20" />
        </td>
      </tr>
  <tr><td width="35%" align=right>
    Your PHP config. allows at most:</td>
  <td width="65%">
    <?php echo ini_get('post_max_size').'B(max. post) and '.ini_get('upload_max_filesize').'B(max. filesize)'; ?>
  </td></tr>
  <tr><td width="35%" align=right></td>
  <td width="65%">
<?php echo ini_get('session.gc_maxlifetime').'s of session expiration and ' . ini_get('session.cookie_lifetime') . ' as cookie lifetime (0 means unlimited)'; ?>
  </td></tr>
      <tr>
							<td width="35%" align=right>Main site URL (IP/bocafolder):</td>
        <td width="65%">
          <input type="text" name="mainsiteurl" value="<?php echo $ct["contestmainsiteurl"]; ?>" size="40" maxlength="200" />
        </td>
      </tr>
      <tr>
							<td width="35%" align=right>Unlock password (only use it within a <b>secure network</b>):</td>
        <td width="65%">
          <input type="password" name="unlockkey" value="" size="40" maxlength="200" />
		   <?php if(strlen($ct["contestunlockkey"]) > 1) echo "<b><= has been set</b>"; ?>
        </td>
      </tr>
<?php if($main) { ?>
      <tr>
							<td width="35%" align=right>Keys (only use it within a <b>secure network</b>):</td>
        <td width="65%">
          <input type="file" name="keyfile" size="40">
		   <?php if(strlen($ct["contestkeys"]) > 32) echo "<b><= has been set</b>"; ?>
        </td>
      </tr>
	  <?php } ?>
      <tr>
        <td width="35%" align=right>Contest main site number:</td>
        <td width="65%">
          <input type="text" name="mainsite" <?php if(!$main) echo "readonly"; ?> value="<?php echo $ct["contestmainsite"]; ?>" size="4" maxlength="4" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Contest local site number:</td>
        <td width="65%">
          <input type="text" name="localsite" <?php if(!$main) echo "readonly"; ?> value="<?php echo $ct["contestlocalsite"]; ?>" size="4" maxlength="4" />
        </td>
      </tr>
    </table>
  </center>
  <center>
<?php if($main) { ?>
	  <input type="submit" name="Submit3" value="Update" onClick="conf()">
	   <input type="submit" name="Submit3" value="Update Contest and All Sites" onClick="conf2()">
	   <input type="reset" name="Submit4" value="Clear">
<?php } else { ?>
      <input type="submit" name="Submit3" value="Update" onClick="conf()">
	   <input type="submit" name="Submit3" value="Become Main Site" onClick="conf3()">
<?php } ?>
	   </center>
</form>

</body>
</html>
