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
require('header.php');

if(($ct = DBContestInfo($_SESSION["usertable"]["contestnumber"])) == null)
	ForceLoad("../index.php");

?>
<br>
<table width="100%" border=1>
 <tr>
  <td><b>Name</b></td>
  <td><b>Basename</b></td>
  <td><b>Fullname</b></td>
  <td><b>Descfile</b></td>
 </tr>
<?php
$prob = DBGetProblems($_SESSION["usertable"]["contestnumber"]);
for ($i=0; $i<count($prob); $i++) {
  echo " <tr>\n";
//  echo "  <td nowrap>" . $prob[$i]["number"] . "</td>\n";
  echo "  <td nowrap>" . $prob[$i]["problem"];
  if($prob[$i]["color"] != "")
          echo " <img alt=\"".$prob[$i]["colorname"]."\" width=\"20\" ".
			  "src=\"" . balloonurl($prob[$i]["color"]) ."\" />\n";
  echo "</td>\n";
  echo "  <td nowrap>" . $prob[$i]["basefilename"] . "&nbsp;</td>\n";
  echo "  <td nowrap>" . $prob[$i]["fullname"] . "&nbsp;</td>\n";
  if (isset($prob[$i]["descoid"]) && $prob[$i]["descoid"] != null && isset($prob[$i]["descfilename"])) {
    echo "  <td nowrap><a href=\"../filedownload.php?" . filedownload($prob[$i]["descoid"], $prob[$i]["descfilename"]) .
		"\">" . basename($prob[$i]["descfilename"]) . "</td>\n";
  }
  else
    echo "  <td nowrap>no description file available</td>\n";
  echo " </tr>\n";
}
echo "</table>";
if (count($prob) == 0) echo "<br><center><b><font color=\"#ff0000\">NO PROBLEMS AVAILABLE YET</font></b></center>";

?>
</body>
</html>
