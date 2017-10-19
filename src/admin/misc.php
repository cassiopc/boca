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
   </script>
   <center>
   <input type="submit" name="Submit1" value="Transfer" onClick="conf()"> &nbsp;
   <input type="submit" name="Submit2" value="Transfer one-way" onClick="conf()"> &nbsp;
   <input type="submit" name="Submit3" value="Transfer scores"> &nbsp;
   <input type="submit" name="Submit4" value="Update BOCA" onClick="conf()"> 
  </center>
</form>
<?php
$ds = DIRECTORY_SEPARATOR;
if($ds=="") $ds = "/";

if (isset($_POST["Submit1"]) && $_POST["Submit1"] == "Transfer") {
  $privatedir = $_SESSION['locr'] . $ds . "private";
  $remotedir = $_SESSION['locr'] . $ds . "private" . $ds . "remotescores";
  $destination = $remotedir . $ds ."scores.zip";
  if(is_writable($remotedir)) {
    if(($fp = @fopen($destination . ".lck",'x')) !== false) {

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
      echo "Processing other data\n";
      getMainXML($_SESSION["usertable"]["contestnumber"]);
      echo "</pre>\n";
      @unlink($destination . ".lck");
    } else {
      if(file_exists($destination . ".lck") && filemtime($destination . ".lck") < time() - 180)
	@unlink($destination . ".lck");
    }
  }
}
?>
</body>
</html>
