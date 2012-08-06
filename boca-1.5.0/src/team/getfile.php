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
require 'header.php';

if(($ct = DBContestInfo($_SESSION["usertable"]["contestnumber"])) == null)
	ForceLoad("../index.php");
if(($st = DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
        ForceLoad("../index.php");

$fn = tempnam("/tmp","bkp-");
$fout = fopen($fn,"wb");
echo $_POST;
echo $_POST['data'];
fwrite($fout,base64_decode($_POST['data']));
fclose($fout);
$size=filesize($fn);
$name=$_POST['name'];
		if ($size > $ct["contestmaxfilesize"] || strlen($name)>100 || strlen($name)<1) {
	                LOGLevel("User {$_SESSION["usertable"]["username"]} tried to submit file " .
			":${name}: with $size bytes.", 1);
			MSGError("File size exceeds the limit allowed or invalid name.");
		} else

		DBNewBkp ($_SESSION["usertable"]["contestnumber"],
	                  $_SESSION["usertable"]["usersitenumber"],
	                  $_SESSION["usertable"]["usernumber"],
			  $name,
			  $fn, $size);
@unlink($fn);
ForceLoad("../index.php");
?>
