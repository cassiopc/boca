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
require_once("globals.php");

if(!ValidSession()) {
        InvalidSession("scorelower.php");
        ForceLoad("index.php");
}

if (($s = DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
  ForceLoad("../index.php");

if ($_SESSION["usertable"]["usertype"]!="judge" && 
    $_SESSION["usertable"]["usertype"]!="admin") $ver=true;
else $ver=false;
if($_SESSION["usertable"]["usertype"]=="score") $des=false;
else $des=true;

// temp do carlinhos (placar de judge == placar de time)
//if ($_SESSION["usertable"]["usertype"]=="judge") $ver = true;

if ($s["currenttime"] >= $s["sitelastmilescore"] && $ver)
	echo "<br><center>Scoreboard frozen</center>";

require('scoretable.php');
?>

</body>
</html>
