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
//Last updated 16/oct/2017 by cassio@ime.usp.br
require 'header.php';
?>
<br>
<form name="form1" enctype="multipart/form-data" method="post" action="misc.php">
  <input type=hidden name="confirmation" value="noconfirm" />
  <script language="javascript" type="text/javascript">
    function conf() {
      if (confirm("Confirm?")) {
        document.form1.confirmation.value='confirm';
      }
    }
    function conf2() {
      if (confirm("Confirm updating BOCA?")) {
		  if (confirm("This operation will update your BOCA system. Confirm?")) {
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
<?php
if(isset($_POST['confirmation']) && $_POST['confirmation'] == 'confirm') {
$ds = DIRECTORY_SEPARATOR;
if($ds=="") $ds = "/";
$dotransfer=false;
$doscore=false;
$dotransferall=false;
if (isset($_POST["Submit1"]) && $_POST["Submit1"] == "Transfer") {
  $dotransfer=true;
  $doscore=true;
}
if (isset($_POST["Submit2"]) && $_POST["Submit2"] == "Transfer all") {
  $dotransfer=true;
  $dotransferall=true;
  $doscore=true;
}
if (isset($_POST["Submit3"]) && $_POST["Submit3"] == "Transfer scores") {
  $doscore=true;
}
if (isset($_POST["Submit4"]) && $_POST["Submit4"] == "Clear cache") {
  if(fixbocadir(dirname(__DIR__)))
    echo "<pre>Done</pre>\n";
  else echo "<pre>Error (likely permission/ownership issues)</pre>\n";
}
if (isset($_POST["Submit5"]) && $_POST["Submit5"] == "Full clear") {
  if(fixbocadir(dirname(__DIR__),true))
    echo "<pre>Done</pre>\n";
  else echo "<pre>Error (likely permission/ownership issues)</pre>\n";
}
if (isset($_POST["Submit6"]) && $_POST["Submit6"] == "Update BOCA") {
  require('..' . $ds . 'versionnum.php');
  $curv = split('.',$BOCAVERSION);
  $dir = dirname(__DIR__);
  fixbocadir($dir);
  $tmpfname = tempnam(sys_get_temp_dir());
  if(($str = @file_get_contents('http://www.bombonera.org/updateboca.zip')) !== false) {
    @file_put_contents($tmpfname, $str);
    $t = mytime();
    $zip = new ZipArchive;
    if ($zip->open($tmpfname) === true) {
      $zip->extractTo($dir . $ds . "private" . $ds . "newboca." . $t);
      $zip->close();
      require($dir . $ds . "private" . $ds . "newboca." . $t . $ds . 'versionnum.php');
      $newv = split('.',$BOCAVERSION);
      if($curv[0] != $newv[0] || $curv[1] != $newv[1])
	echo "<pre>Cannot updated because of major version difference</pre>";
      else {
	$q = updatebocafile($dir, $dir . $ds . "private" . $ds . "newboca." . $t, $t);
	echo "<pre>" . $q . " files updated to " . $BOCAVERSION . "\n</pre>\n";
	$str = @file_get_contents($dir . $ds . "private" . $ds . "updateboca.log");
	@file_put_contents($dir . $ds . "private" . $ds . "updateboca.log",  $str . $t . "\n");
      }
    } else {
      echo "<pre>Downloaded file corrupted</pre>";
    }
  } else echo "<pre>Download error</pre>";
}
if (isset($_POST["Submit7"]) && $_POST["Submit7"] == "Revert Update") {
  $str = @file($dir . $ds . "private" . $ds . "updateboca.log");
  if(count($str) >= 1) {
    $t = trim($str[count($str)-1]);
    unset($str[count($str)-1]);
    $str = implode("\n", $str);
    $dir = dirname(__DIR__);
    fixbocadir($dir);
    echo "<pre>Reverting last update\n";
    $q = revertupdatebocafile($dir, $t);
    echo $q . " files reverted properly\n";
    echo "</pre>";
    @file_put_contents($dir . $ds . "private" . $ds . "updateboca.log", $str);
  } else {
    echo "<pre>No updates to revert</pre>\n";
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
	echo "<pre>\n";
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
	echo "Transferring scores\n";
	scoretransfer($fname . ".dat", $localsite);
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
	echo "Processing other data\n";
	getMainXML($_SESSION["usertable"]["contestnumber"],10,$dotransferall);
	echo "</pre>\n";
      }
      @unlink($destination . ".lck");
    } else {
      if(file_exists($destination . ".lck") && filemtime($destination . ".lck") < time() - 120)
	@unlink($destination . ".lck");
      echo "<pre>Transfers locked by other process - try again soon</pre>\n";
    }
  }
}
}
?>
</body>
</html>
