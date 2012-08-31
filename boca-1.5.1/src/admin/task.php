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
require_once('header.php');
if(isset($_GET["order"]) && $_GET["order"] != "") {
$order = myhtmlspecialchars($_GET["order"]);
	$_SESSION["taskline"] = $order;
} else {
	if(isset($_SESSION["taskline"]))
		$order = $_SESSION["taskline"];
	else
		$order='';
}

if(($ct = DBContestInfo($_SESSION["usertable"]["contestnumber"])) == null)
	ForceLoad("../index.php");

if (isset($_GET["delete"]) && is_numeric($_GET["delete"]) && isset($_GET["site"]) && is_numeric($_GET["site"])) {
	DBTaskDelete ($_GET["delete"], $_GET["site"], $_SESSION["usertable"]["contestnumber"], 
		     $_SESSION["usertable"]["usernumber"], $_SESSION["usertable"]["usersitenumber"]);
	ForceLoad("task.php");
}

if (isset($_GET["return"]) && is_numeric($_GET["return"]) && isset($_GET["site"]) && is_numeric($_GET["site"])) {
	DBTaskGiveUp ($_GET["return"], $_GET["site"], $_SESSION["usertable"]["contestnumber"], -1, -1);
//		     $_SESSION["usertable"]["usernumber"], $_SESSION["usertable"]["usersitenumber"]);
	ForceLoad("task.php");
}

if (isset($_GET["get"]) && is_numeric($_GET["get"]) && isset($_GET["site"]) && is_numeric($_GET["site"])) {
	DBGetTaskToAnswer($_GET["get"], $_GET["site"], $_SESSION["usertable"]["contestnumber"]);
	ForceLoad("task.php");
}

if (isset($_GET["done"]) && is_numeric($_GET["done"]) && isset($_GET["site"]) && is_numeric($_GET["site"])) {
        DBChiefUpdateTask( $_SESSION["usertable"]["contestnumber"], $_SESSION["usertable"]["usersitenumber"],
	      $_SESSION["usertable"]["usernumber"], $_GET["site"], $_GET["done"], 'done');
	ForceLoad("task.php");
}



?>
<br>
  <script language="javascript">
    function conf2(url) {
//      if (confirm("Confirm?")) {
        document.location=url;
//      } else {
//        document.location='task.php';
//      }
    }
  </script>
<table width="100%" border=1>
 <tr>
  <td><b><a href="task.php?order=task">Task #</a></b></td>
  <td><b>Time</b></td>
  <td><b><a href="task.php?order=user">User / Site</a></b></td>
  <td><b><a href="task.php?order=description">Description</a></b></td>
  <td><b>File</b></td>
  <td><b><a href="task.php?order=staff">Staff / Site</a></b></td>
  <td><b><a href="task.php?order=status">Status</a></b></td>
  <td><b>Actions</b></td>
 </tr>
<?php
if (($s=DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
        ForceLoad("../index.php");

$task = DBAllTasksInSites($_SESSION["usertable"]["contestnumber"], $s["sitetasking"], $order);
for ($i=0; $i<count($task); $i++) {
  $st = $task[$i]["status"];

  if($st == "processing" && $task[$i]["staff"]==$_SESSION["usertable"]["usernumber"] &&
	 $task[$i]["staffsite"]==$_SESSION["usertable"]["usersitenumber"]) $mine=1;
  else $mine=0;

  echo " <tr>\n";
  echo "  <td nowrap>" . $task[$i]["number"] . "</td>\n";
  echo "  <td nowrap>" . dateconvminutes($task[$i]["timestamp"]) . "</td>\n";
  echo "  <td nowrap>".$task[$i]["username"]."(" . $task[$i]["user"] . ") / ".$task[$i]["site"]."</td>\n";
  echo "  <td>" . $task[$i]["description"];
  if($task[$i]["color"] != "") {
          echo " <img alt=\"".$task[$i]["colorname"]."\" width=\"10\" ".
			  "src=\"" . balloonurl($task[$i]["color"]) ."\" />";

  }
  echo "</td>\n";
  if ($task[$i]["oid"] != null) {
	  $msg = "///// " . $task[$i]["username"]." ".$task[$i]["username"]." ".$task[$i]["username"]." ".
		  $task[$i]["username"]." ".$task[$i]["username"]." ".$task[$i]["username"];
	  echo "  <td nowrap><a href=\"../filedownload.php?" . filedownload($task[$i]["oid"], $task[$i]["filename"]) . "\">" . $task[$i]["filename"] . "</a>";
	  echo " <a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('../filewindow.php?".
		  filedownload($task[$i]["oid"], $task[$i]["filename"], $msg) . "', 'Viewx$i','width=680,height=600,scrollbars=yes,".
		  "resizable=yes')\">view</a>";
	  echo "</td>\n";
  }
  else
    echo "  <td nowrap>&nbsp;</td>\n";
  if($st != "opentask")
    echo "  <td nowrap>". $task[$i]["staffname"] . "(" . $task[$i]["staff"] .") / ".$task[$i]["staffsite"]."</td>\n";
  else
    echo "  <td nowrap>&nbsp;</td>\n";

  if ($mine) $color="ff7777";
  else if ($st == "done") $color="bbbbff";
  else if ($st == "processing") $color="77ff77";
  else if ($st == "opentask") $color="ffff88";
  else $color="ffffff";

  echo "  <td nowrap bgcolor=\"#$color\">$st</td>\n  <td nowrap>";

  if($st != "deleted")
    echo "  <a href=\"javascript: conf2('task.php?delete=" . $task[$i]["number"] . "&site=" . 
       $task[$i]["site"] . "')\">delete</a>\n";
  if($st == "opentask")
    echo "  <a href=\"javascript: conf2('task.php?get=" . $task[$i]["number"] . "&site=" . 
       $task[$i]["site"] . "')\">get</a>\n";
  if($st != "opentask")
    echo "  <a href=\"javascript: conf2('task.php?return=" . $task[$i]["number"] . "&site=" . 
       $task[$i]["site"] . "')\">return</a>\n";
  if($st == "processing")
    echo "  <a href=\"javascript: conf2('task.php?done=" . $task[$i]["number"] . "&site=" . 
       $task[$i]["site"] . "')\">done</a>\n";
  echo "</td>\n";
}
echo "</table>";
if (count($task) == 0) echo "<br><center><b><font color=\"#ff0000\">NO TASKS FOUND</font></b></center>";

?>

</body>
</html>
