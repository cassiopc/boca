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
<br><b>Information:</b>
<?php
/*
<br>General information: <a href="https://global.naquadah.com.br/boca/info_sheet.pdf">info_sheet.pdf</a>

<br>Timelimits:
<a href="https://global.naquadah.com.br/boca/contest_times.pdf">contest_times.pdf</a> 
 */

if(is_readable('/var/www/boca/src/sample/secretcontest/maratona.pdf')) {
?>
<b>PLAIN FILES:</b>  <b>CONTEST</b> (
<a href='https://global.naquadah.com.br/boca/secretcontest/maratona.pdf'>PT</a> |
<a href='https://global.naquadah.com.br/boca/secretcontest/maratona_es.pdf'>ES</a> |
<a href='https://global.naquadah.com.br/boca/secretcontest/maratona_en.pdf'>EN</a>
)
&nbsp;&nbsp;&nbsp; 
<b>Info Sheet</b> (
<a href='https://global.naquadah.com.br/boca/secretcontest/info_maratona.pdf'>PT</a> |
<a href='https://global.naquadah.com.br/boca/secretcontest/info_maratona_es.pdf'>ES</a> |
<a href='https://global.naquadah.com.br/boca/secretcontest/info_maratona_en.pdf'>EN</a>
)

<?php
}
?>


<br><br><br>
<table width="100%" border=1>
 <tr>
  <td><b>Name</b></td>
  <td><b>Basename</b></td>
  <td><b>Submissions</b></td>
  <td><b>Fullname</b></td>
  <td><b>Descfile</b></td>
 </tr>
<?php
$prob = DBGetProblems($_SESSION["usertable"]["contestnumber"]);
// gather submission counts per problem (only team users, exclude deleted runs)
$subcounts = array();
$accepteds = array();

$contest = $_SESSION["usertable"]["contestnumber"];
$site = $_SESSION["usertable"]["usersitenumber"];

if (($blocal = DBSiteInfo($contest, $_SESSION["usertable"]["usersitenumber"])) == null)
  exit;
if (($b = DBSiteInfo($contest, $site, null, false)) == null)
  $b = $blocal;
if (($ct = DBContestInfo($contest)) == null)
  exit;

$ta = $blocal["currenttime"];
$t_freeze = $b["sitelastmilescore"]; 

$counts = DBGetProblemSubmissionCounts($contest, $site, $t_freeze, $ta);
$subcounts = $counts['subcounts'];
$accepteds = $counts['accepteds'];


for ($i=0; $i<count($prob); $i++) {
  echo " <tr>\n";
//  echo "  <td nowrap>" . $prob[$i]["number"] . "</td>\n";
  echo "  <td nowrap>" . $prob[$i]["problem"];
  if($prob[$i]["color"] != "")
          echo " <img alt=\"".$prob[$i]["colorname"]."\" width=\"20\" ".
			  "src=\"" . balloonurl($prob[$i]["color"]) ."\" />\n";
  echo "</td>\n";
  echo "  <td nowrap>" . $prob[$i]["basefilename"] . "&nbsp;</td>\n";
  $count = (isset($subcounts[$prob[$i]['number']]) ? $subcounts[$prob[$i]['number']] : 0);
  $count_yes = (isset($accepteds[$prob[$i]['number']]) ? $accepteds[$prob[$i]['number']] : 0);
  echo "  <td nowrap>" . $count_yes . "/" . $count . "&nbsp;</td>\n";



  // $ct=DBGetActiveContest();
	// $contest=$ct['contestnumber'];
	// $duration=$ct['contestduration'];

	// if(!isset($hor)) $hor = -1;
	// if($hor>$duration) $hor=$duration;

	// $level=$s["sitescorelevel"];
	// if($level<=0) $level=-$level;
	// else {
	// 	$des=true;
	// }



  // if (($s = DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
	// 	ForceLoad("index.php");
	// $score = DBScore($_SESSION["usertable"]["contestnumber"], $ver, $hor*60, $s["siteglobalscore"]);
	
	// if ($_SESSION["usertable"]["usertype"]!="score" && $_SESSION["usertable"]["usertype"]!="admin" && $level>3) $level=3;

	// $minu = 3;
	// $rn = DBRecentNews($_SESSION["usertable"]["contestnumber"],
	// 				   $_SESSION["usertable"]["usersitenumber"], $ver, $minu);




  echo "  <td nowrap>" . $prob[$i]["fullname"] . "&nbsp;</td>\n";
  if (isset($prob[$i]["descoid"]) && $prob[$i]["descoid"] != null && isset($prob[$i]["descfilename"])) {
    echo "  <td nowrap><a href=\"../filedownload.php?" . filedownload($prob[$i]["descoid"], $prob[$i]["descfilename"]) .
		"\">" . basename($prob[$i]["descfilename"]) . "</a></td>\n";
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