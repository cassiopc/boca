<?php
////////////////////////////////////////////////////////////////////////////////
//BOCA Online Contest Administrator
//    Copyright (C) 2003-2013 by BOCA Development Team (bocasystem@gmail.com)
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
// Last modified 28/oct/2013 by cassio@ime.usp.br
require('header.php');
if(isset($_GET['mainuser']) && isset($_GET['mainpass']) && $_GET['mainuser']!="" && $_GET['mainpass']!="") {
	$_SESSION['mainuser'] = $_GET['mainuser'];
	$_SESSION['mainpass'] = $_GET['mainpass'];
	$_SESSION['check'] = $_GET['check'];
	unset($_GET['mainuser']);
	unset($_GET['mainpass']);
	unset($_GET['check']);
	ForceLoad('index.php');
}
$smi = isset($_SESSION['mainid'])? $_SESSION['mainid']: "";
$smu = isset($_SESSION['mainuser'])? $_SESSION['mainuser']: "";
$smp = isset($_SESSION['mainpass'])? $_SESSION['mainpass']: "";
$smc = isset($_SESSION['check'])? $_SESSION['check']: "";
//MSGError("id=".$smi."   user=".$smu."  pass=".$smp. "  check=".$smc);

$contest=$_SESSION["usertable"]["contestnumber"];
if($contest != '' && is_numeric($contest)) {
  $ct = DBContestInfo($contest);
  $mainsiteurl = explode(' ',$ct['contestmainsiteurl']);
//  if(count($mainsiteurl)==3) {
//    if($smu == '') $smu = $mainsiteurl[1];
//    if($smp == '') $smp = myhash($mainsiteurl[2]);
//  }
}

echo "<html><head><title>Site Page</title>\n";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">\n";
echo "<link rel=stylesheet href=\"../Css.php\" type=\"text/css\">\n";
if ($smp != $smc && $smp != '' && $smc != '')
	echo "<meta http-equiv=\"refresh\" content=\"60\" />"; 

if ($smu != '')
	echo "</head><body onload=\"document.form1.password.focus()\"><table border=1 width=\"100%\">\n";
else
	echo "</head><body onload=\"document.form1.name.focus()\"><table border=1 width=\"100%\">\n";
echo "<tr><td nowrap bgcolor=\"#ff00ff\" align=center>";
echo "<img src=\"../images/smallballoontransp.png\" alt=\"\">";
echo "<font color=\"#000000\">BOCA</font>";
echo "</td><td bgcolor=\"#ff00ff\" width=\"99%\">\n";
echo "Username: " . $_SESSION["usertable"]["userfullname"] . " (site=".$_SESSION["usertable"]["usersitenumber"].")<br>\n";
list($clockstr,$clocktype)=siteclock();
echo "</td><td bgcolor=\"#ff00ff\" align=center nowrap>&nbsp;".$clockstr."&nbsp;</td></tr>\n";
echo "</table>\n";
echo "<table border=0 width=\"100%\" align=center>\n";
echo " <tr>\n";
echo "  <td align=center><a class=menu style=\"font-weight:bold\" href=../index.php>Logout</a></td>\n";
echo " </tr>\n";
echo "</table>\n";

list($t,$id,$idextra) = getMainXML($smu,$smi,$smp,$smc);
//MSGError("t=". ($t==false?"false":"true") ." id=$id idextra=$idextra");
if($t==false)
	$_SESSION['mainid'] = $id;
$_SESSION['mainok'] = $t;

if(!$t) {
	unset($_GET['mainuser']);
	unset($_GET['mainpass']);
	unset($_GET['check']);
	unset($_SESSION["mainuser"]);
	unset($_SESSION["mainpass"]);
	unset($_SESSION["check"]);
?>
<script language="JavaScript" src="../sha256.js"></script>
<script language="JavaScript">
function computeHASH()
{
	var userHASH, passHASH, passHASH2;
	userHASH = document.form1.name.value;
	<?php if(strlen($id) > 20 && strlen($idextra) > 20) { ?>
    tmpv = js_myhash(document.form1.password.value);
	passHASH = js_myhash(tmpv+'<?php echo $id; ?>');
	passHASH2 = js_myhash(tmpv+'<?php echo $idextra; ?>');
    <?php } ?>
	tmpv = '                                                                               ';
	document.form1.name.value = '';
	document.form1.password.value = '                                                               ';
	document.location = 'index.php?mainuser='+userHASH+'&mainpass='+passHASH+'&check='+passHASH2;
}
</script>
<table width="100%" height="100%" border="0">
  <tr align="center" valign="middle"> 
    <td> 
      <form name="form1" action="javascript:computeHASH()">
        <div align="center"> 
          <table border="0" align="center">
<?php
	  if($id == '' || $idextra=='') {
?>
            <input type="hidden" name="name" value="">
            <input type="hidden" name="password" value="">
            <tr> 
              <td nowrap>
                <div align="center"><font face="Verdana, Arial, Helvetica, sans-serif" size="+1">
Cannot access main server. Check IP address,<br> password and internet connection, then press OK.</font></div>
              </td>
            </tr>
            <tr>
              <td valign="top"> 
              <center><input type="submit" name="Submit" value="OK"></center>
              </td>
            </tr>
<?php		  
	  } else {
?>
            <tr> 
              <td nowrap>
                <div align="center"><font face="Verdana, Arial, Helvetica, sans-serif" size="+1">
			  <?php 
			  if($id==$idextra) {
				  echo "Credentials to connect to main server<br>";
				  echo "at URL: " . $mainsiteurl[0];
			  } else
				  echo "<u>Status</u>: initial connection established<br><br>To guarantee an encrypted connection, please type the same password again:";
?>
               </font></div>
              </td>
            </tr>
            <tr>
              <td valign="top"> 
                <table border="0" align="left">
                  <tr> 
                    <td><font face="Verdana, Arial, Helvetica, sans-serif" > 
                      Name
                      </font></td>
                    <td> 
                      <input type="text" name="name" value="<?php echo $smu; ?>">
                    </td>
                  </tr>
                  <tr> 
                    <td><font face="Verdana, Arial, Helvetica, sans-serif" >Password</font></td>
                    <td> 
                      <input type="password" name="password">
                    </td>
                  </tr>
                </table>
                <input type="submit" name="Submit" value="Login">
              </td>
            </tr>
<?php } ?>
          </table>
        </div>
      </form>
    </td>
  </tr>
</table>
<?php
} else {
	if($idextra == "<OK>")
		echo "<u>Data sent correctly to main server</u><br><br>";
	else
		echo "<u>Error sending data to main server</u><br><br>";
	$ac['CONTESTREC']=array('number'=>-1, 
						 'name'=>-1, 
						 'startdate'=>-1, 
						 'duration'=>-1, 
						 'lastmileanswer'=>-1,
						 'lastmilescore'=>-1,
						 'penalty'=>-1,
						 'maxfilesize'=>-1,
						 'updatetime'=>-1);
	$ac['ANSWERREC']=array('number'=>-1,
						'name'=>-1,
						'yes'=>-1,
						'updatetime'=>-1);
	$ac['LANGUAGEREC']=array('number'=>-1,
						  'name'=>-1,
						  'filepath'=>-1,
						  'filename'=>-1,
						  'comppath'=>-1,
						  'compname'=>-1,
						  'problemnumber'=>-1,
						  'updatetime'=>-1);
	$ac['PROBLEMREC']=array('number'=>-1,
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
//						 'colorname'=>-1,
//						 'color'=>-1,
						 'fake'=>-1,
						 'updatetime'=>-1);
	$ac['SITETIME']=array('site'=>-1,
						  'start'=>-1,
						  'enddate'=>-1,
						  'updatetime'=>-1);
	$ac['SITEREC']=array('sitenumber'=>-1,
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
	$ac['USERREC']=array('site'=>-1,
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
	$ac['CLARREC']=array('site'=>-1,
					  'user'=>-1,
					  'number'=>-1,
					  'problem'=>-1,
					  'question'=>-1,
					  'clarnumber'=>-1,
					  'clardate'=>-1,
					  'clardatediff'=>-1,
					  'clardatediffans'=>-1,
					  'claranswer'=>-1,
					  'clarstatus'=>-1,
					  'clarjudge'=>-1,
					  'clarjudgesite'=>-1,
					  'updatetime'=>-1);
	$ac['RUNREC']=array('site'=>-1,
					 'user'=>-1,
					 'number'=>-1,
					 'runnumber'=>-1,
					 'problem'=>-1,
					 'lang'=>-1,
					 'filename'=>-1,
					 'filepath'=>-1,
					 'rundate'=>-1,
					 'rundatediff'=>-1,
					 'rundatediffans'=>-1,
					 'runanswer'=>-1,
					 'runstatus'=>-1,
					 'runjudge'=>-1,
					 'runjudgesite'=>-1,
					 'runjudge1'=>-1,
					 'runjudgesite1'=>-1,
					 'runanswer1'=>-1,
					 'runjudge2'=>-1,
					 'runjudgesite2'=>-1,
					 'runanswer2'=>-1,
					 'autoip'=>-1,
					 'autobegindate'=>-1,
					 'autoenddate'=>-1,
					 'autoanswer'=>-1,
					 'autostdout'=>-1,
					 'autostderr'=>-1,
					 'updatetime'=>-1);
	$ac['TASKREC']=array(
		'site'=>-1,
		'user'=>-1,
		'desc'=>-1,
		'number'=>-1,
		'tasknumber'=>-1,
		'color'=>-1,
		'colorname'=>-1,
		'updatetime'=>-1,
		'filename'=>-1,
		'filepath'=>-1,
		'sys'=>-1,
		'status'=>-1,
		'taskdate'=>-1,
		'taskdatediff'=>-1,
		'taskdatediffans'=>-1,
		'taskstaffnumber'=>-1,
		'taskstaffsite'=>-1);

	if(importFromXML($id,$ac,$_SESSION["usertable"]["contestnumber"]))
		echo "<u>Data received correctly from main server at " . dateconv(time()) . "</u>";
	else
		echo "<u>Error receiving data from main server at ".  dateconv(time()) . "</u>";
//	echo "<pre>" . $id . "</pre>";
	echo "<br><br><b>waiting for next round...</b>";
}
?>
</body>
</html>
