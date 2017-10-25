<?php
////////////////////////////////////////////////////////////////////////////////
//BOCA Online Contest Administrator
//    Copyright (C) 2003-2013 by BOCA System (bocasystem@gmail.com)
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
//Last updated 24/oct/2017 by cassio@ime.usp.br
require 'header.php';
?>
<br>
<form name="form1" enctype="multipart/form-data" method="get" action="misc.php">
  <input type=hidden name="confirmation" value="noconfirm" />
  <script language="javascript" type="text/javascript">
    function conf() {
      if (confirm("Confirm?")) {
        document.form1.confirmation.value='confirm';
      }
    }
    function conf2() {
      if (confirm("Confirm updating BOCA?")) {
		  if (confirm("This operation may perform considerable changes to your BOCA system. Confirm?")) {
			  document.form1.confirmation.value='confirm';
		  }
      }
    }
   </script>
   <center>
   <input type="submit" name="Submit1" value="Transfer" onClick="conf()"> &nbsp;
   <input type="submit" name="Submit2" value="Transfer all" onClick="conf()"> &nbsp;
   <input type="submit" name="Submit3" value="Transfer scores"> &nbsp;
   <input type="submit" name="Submit4" value="Clear cache" onClick="conf()"> &nbsp; 
   <input type="submit" name="Submit5" value="Full clear" onClick="conf2()">  &nbsp;
   <input type="submit" name="Submit6" value="Update BOCA" onClick="conf2()">  &nbsp;
   <input type="submit" name="Submit7" value="Revert Update" onClick="conf2()"> 
  </center>
</form>
<pre>
OPERATION LOG DISPLAYS BELOW:

<?php
echo "Start: " . now() . "\n";
if(isset($_GET['confirmation']) && $_GET['confirmation'] == 'confirm') {
$ds = DIRECTORY_SEPARATOR;
if($ds=="") $ds = "/";
$dotransfer=false;
$doscore=false;
$dotransferall=false;
if (isset($_GET["Submit1"]) && $_GET["Submit1"] == "Transfer") {
  $dotransfer=true;
  $doscore=true;
}
if (isset($_GET["Submit2"]) && $_GET["Submit2"] == "Transfer all") {
  $dotransfer=true;
  $dotransferall=true;
  $doscore=true;
}
if (isset($_GET["Submit3"]) && $_GET["Submit3"] == "Transfer scores") {
  $doscore=true;
}
if (isset($_GET["Submit4"]) && $_GET["Submit4"] == "Clear cache") {
  if(fixbocadir(dirname(__DIR__)))
    echo "Done\n";
  else echo "Error (likely permission/ownership issues)\n";
}
if (isset($_GET["Submit5"]) && $_GET["Submit5"] == "Full clear") {
  if(fixbocadir(dirname(__DIR__),true))
    echo "Done\n";
  else echo "Error (likely permission/ownership issues)\n";
}
if (isset($_GET["Submit6"]) && $_GET["Submit6"] == "Update BOCA") {
  $dir = dirname(__DIR__);
  if(!is_readable($dir . $ds . "private" . $ds . "updateboca.log")) @file_put_contents($dir . $ds . "private" . $ds . "updateboca.log", "");
  if(is_writable($dir . $ds . "private" . $ds . "updateboca.log")) {
    require('..' . $ds . 'versionnum.php');
    $curv = explode('.',$BOCAVERSION);
    fixbocadir($dir);
    $tmpfname = $dir . $ds . "private" . $ds . 'newboca.zip';
    if(($str = @file_get_contents('http://www.bombonera.org/updateboca.zip')) !== false) {
      @file_put_contents($tmpfname, $str);
      $t = mytime();
      $zip = new ZipArchive;
      if ($zip->open($tmpfname) === true) {
	$zip->extractTo($dir . $ds . "private" . $ds . "newboca." . $t);
	$zip->close();
	require($dir . $ds . "private" . $ds . "newboca." . $t . $ds . 'versionnum.php');
	$newv = explode('.',$BOCAVERSION);
	if($curv[0] != $newv[0] || $curv[1] != $newv[1])
	  echo "Cannot updated because of major version difference\n";
	else {
	  echo "Updating\n";
	  $q = updatebocafile($dir, $dir . $ds . "private" . $ds . "newboca." . $t, $t);
	  echo $q . " files updated to " . $BOCAVERSION . "\n\n";
	  $str = @file_get_contents($dir . $ds . "private" . $ds . "updateboca.log");
	  @file_put_contents($dir . $ds . "private" . $ds . "updateboca.log",  $str . $t . "\n");
	}
      } else {
	echo "Downloaded file corrupted\n";
      }
      @unlink($tmpfname);
    } else echo "Download error\n";
  } else {
    echo "Cannot update log file\n";
  }
}
if (isset($_GET["Submit7"]) && $_GET["Submit7"] == "Revert Update") {
  $dir = dirname(__DIR__);
  if(!is_readable($dir . $ds . "private" . $ds . "updateboca.log")) @file_put_contents($dir . $ds . "private" . $ds . "updateboca.log", "");
  if(is_writable($dir . $ds . "private" . $ds . "updateboca.log")) {
    $str = @file($dir . $ds . "private" . $ds . "updateboca.log");
    if(count($str) >= 1) {
      $t = trim($str[count($str)-1]);
      unset($str[count($str)-1]);
      $str = implode("\n", $str);
      fixbocadir($dir);
      if($t != '') {
	echo "Reverting last update\n";
	$q = revertupdatebocafile($dir, $t);
	echo $q . " files reverted properly\n";
	fixbocadir($dir);
      }
      @file_put_contents($dir . $ds . "private" . $ds . "updateboca.log", $str);
    } else {
      echo "No updates to revert\n";
    }
  } else {
    echo "Cannot update log file\n";
  }
}
if($dotransfer || $doscore || $dotransferall) {
  $privatedir = $_SESSION['locr'] . $ds . "private";
  $remotedir = $_SESSION['locr'] . $ds . "private" . $ds . "remotescores";
  $destination = $remotedir . $ds ."scores.zip";
  if(is_writable($remotedir)) {
    if(($fp = @fopen($destination . ".lck",'x')) !== false) {
      if($doscore) {
	if (($s = DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
	  ForceLoad("index.php");
	echo "Building scores\n";
	$level=$s["sitescorelevel"];
	$data0 = array();
	if($level>0) {
	  list($score,$data0) = DBScoreSite($_SESSION["usertable"]["contestnumber"], 
					    $_SESSION["usertable"]["usersitenumber"], 0, -1);
	}
	$ct=DBGetActiveContest();
	$localsite=$ct['contestlocalsite'];
	$fname = $privatedir . $ds . "score_localsite_" . $localsite . "_x"; // . md5($_SERVER['HTTP_HOST']);
	@file_put_contents($fname . ".tmp",base64_encode(serialize($data0)));
	@rename($fname . ".tmp",$fname . ".dat");
	
	$data0 = array();
	if($level>0) {
	  list($score,$data0) = DBScoreSite($_SESSION["usertable"]["contestnumber"], 
					    $_SESSION["usertable"]["usersitenumber"], 1, -1);
	}
	$ct=DBGetActiveContest();
	$localsite=$ct['contestlocalsite'];
	$fname = $remotedir . $ds . "score_site" . $localsite . "_" . $localsite . "_x"; // . md5($_SERVER['HTTP_HOST']);
	@file_put_contents($fname . ".tmp",base64_encode(serialize($data0)));
	@rename($fname . ".tmp",$fname . ".dat");
	echo "Checking for transfers\n";
	echo scoretransfer($fname . ".dat", $localsite);
	echo "Saving scores\n";
	if(@create_zip($remotedir,glob($remotedir . '/*.dat'),$fname . ".tmp") != 1) {
	  LOGError("Cannot create score zip file");
	  if(@create_zip($remotedir,array(),$fname . ".tmp") == 1)
	    @rename($fname . ".tmp",$destination);
	} else {
	  @rename($fname . ".tmp",$destination);
	}
	@fclose($fp);
      }
      if($dotransfer) {
	echo "Processing contest data\n";
	echo getMainXML($_SESSION["usertable"]["contestnumber"],10,$dotransferall);
      }
      @unlink($destination . ".lck");
    } else {
      if(file_exists($destination . ".lck") && filemtime($destination . ".lck") < time() - 120)
	@unlink($destination . ".lck");
      echo "Transfers locked by other process - try again soon\n";
    }
  }
}
}
echo "End: " . now() . "\n";
?>
</pre>
</body>
</html>
