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
// Last modified 31/aug/2012 by cassio@ime.usp.br
if ($_POST["confirmation"] != "confirm")
	unset($_POST['noflush']);

require('header.php');
if(($ct = DBContestInfo($_SESSION["usertable"]["contestnumber"])) == null)
	ForceLoad("../index.php");

if (isset($_GET["delete"]) && is_numeric($_GET["delete"]) && isset($_GET["input"])) {
	$param = array();
	$param['number']=$_GET["delete"];
	$param['inputfilename']=$_GET["input"];
	if(!DBDeleteProblem ($_SESSION["usertable"]["contestnumber"], $param)) {
		MSGError('Error deleting problem');
		LogError('Error deleting problem');
	}
	ForceLoad("problem.php");
}

if(isset($_POST['Submit5']) && $_POST['Submit5']=='Build')
	ForceLoad("buildproblem.php");

if(isset($_POST['Submit5']) && $_POST['Submit5']=='Send') {
	if(isset($_POST['basename']) &&
	   isset($_POST['fullname']) &&
	   isset($_POST['timelimit']) &&
	   $_POST["confirmation"] == "confirm") {
		if ($_FILES["probleminput"]["name"] != "") {
			$type=myhtmlspecialchars($_FILES["probleminput"]["type"]);
			$size=myhtmlspecialchars($_FILES["probleminput"]["size"]);
			$name=myhtmlspecialchars($_FILES["probleminput"]["name"]);
			$temp=myhtmlspecialchars($_FILES["probleminput"]["tmp_name"]);
			if (!is_uploaded_file($temp)) {
				ob_end_flush();
				IntrusionNotify("file upload problem.");
				ForceLoad("../index.php");
			}
		} else $name = "";
		if ($_FILES["problemsol"]["name"] != "") {
			$type1=myhtmlspecialchars($_FILES["problemsol"]["type"]);
			$size1=myhtmlspecialchars($_FILES["problemsol"]["size"]);
			$name1=myhtmlspecialchars($_FILES["problemsol"]["name"]);
			$temp1=myhtmlspecialchars($_FILES["problemsol"]["tmp_name"]);
			if (!is_uploaded_file($temp1)) {
				ob_end_flush();
				IntrusionNotify("file upload problem.");
				ForceLoad("../index.php");
			}
		} else $name1 = "";
		if (isset($_FILES["problemdesc"]) && $_FILES["problemdesc"]["name"] != "") {
			$type2=myhtmlspecialchars($_FILES["problemdesc"]["type"]);
			$size2=myhtmlspecialchars($_FILES["problemdesc"]["size"]);
			$name2=myhtmlspecialchars($_FILES["problemdesc"]["name"]);
			$temp2=myhtmlspecialchars($_FILES["problemdesc"]["tmp_name"]);
			if (!is_uploaded_file($temp2)) {
				ob_end_flush();
				IntrusionNotify("file upload problem.");
				ForceLoad("../index.php");
			}
		} else $name2 = "";

		$ds = DIRECTORY_SEPARATOR;
		if($ds=="") $ds = "/";
		$tmpdir = getenv("TMP");
		if($tmpdir=="") $tmpdir = getenv("TMPDIR");
		if($tmpdir[0] != $ds) $tmdir = $ds . "tmp";
		if($tmpdir=="") $tmpdir = $ds . "tmp";
		$locr = $_SESSION["locr"];
		$tfile = tempnam($tmpdir, "problem");
		if(@mkdir($tfile . "_d", 0700)) {
			$dir = $tfile . "_d";
			@mkdir($dir . $ds . 'limits');
			@mkdir($dir . $ds . 'compare');
			@mkdir($dir . $ds . 'compile');
			@mkdir($dir . $ds . 'run');
			@mkdir($dir . $ds . 'input');
			@mkdir($dir . $ds . 'output');
			@mkdir($dir . $ds . 'tests');
			@mkdir($dir . $ds . 'description');
			$filea = array('compare' . $ds . 'c','compare' . $ds . 'cpp','compare' . $ds . 'java',
						   'compile' . $ds . 'c','compile' . $ds . 'cpp','compile' . $ds . 'java',
						   'run' . $ds . 'c','run' . $ds . 'cpp','run' . $ds . 'java');
			foreach($filea as $file) {
				$rfile=$locr . $ds . '..' . $ds . 'doc' . $ds . 'problemexamples' . $ds . 'problemtemplate' . $ds . $file;
				if(is_readable($rfile)) {
					@copy($rfile, $dir . $ds . $file);
				} else {
					@unlink($tfile);
					cleardir($dir);
					ob_end_flush();
					MSGError('Could not read problem template file ' . $rfile);
					ForceLoad('problem.php');
				}
			}
			$tl = explode(',',$_POST['timelimit']);
			if(!isset($tl[1]) || !is_numeric(trim($tl[1]))) $tl[1]='1';
			$str = "echo " . trim($tl[0]) . "\necho " . trim($tl[1]) . "\necho 512\necho " . floor(10 + $size1 / 512) . "\nexit 0\n";
			file_put_contents($dir . $ds . 'limits' . $ds . 'c',$str);
			file_put_contents($dir . $ds . 'limits' . $ds . 'cpp',$str);
			file_put_contents($dir . $ds . 'limits' . $ds . 'java',$str);
			$str = "basename=" . trim($_POST['basename']) . "\nfullname=" . trim($_POST['fullname']);
			if($name2) {
				@copy($temp2, $dir . $ds . 'description' . $ds . $name2);
				@unlink($temp2);
				$str .= "\ndescfile=" . $name2;
			}
			$str .= "\n";
			file_put_contents($dir . $ds . 'description' . $ds . 'problem.info',$str);
			if($name && $name1) {
				@copy($temp, $dir . $ds . 'input' . $ds . 'file1');
				@unlink($temp);
				@copy($temp1, $dir . $ds . 'output' . $ds . 'file1');
				@unlink($temp1);
			} else {
				@unlink($tfile);
				cleardir($dir);
				ob_end_flush();
				MSGError('Could not read problem input/output files');
				ForceLoad('problem.php');
			}
			$ret=create_zip($dir, glob($dir . $ds . '*'),$dir . '.zip');
			cleardir($dir);
			if($ret <= 0) {
				@unlink($tfile);
				@unlink($dir . '.zip');
				ob_end_flush();
				MSGError('Could not write to zip file');
				ForceLoad('problem.php');
			}
			$str = file_get_contents($dir . '.zip');
			@unlink($dir . '.zip');
			@unlink($tfile);
			header ("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
			header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header ("Cache-Control: no-cache, must-revalidate");
			header ("Pragma: no-cache");
			header ("Content-transfer-encoding: binary\n");
			header ("Content-type: application/force-download");
			header ("Content-Disposition: attachment; filename=" . basename($dir . '.zip'));
			ob_end_flush();
			echo $str;
			exit;
		} else {
			@unlink($tfile);
			ob_end_flush();
			MSGError('Could not write to temporary directory');
		}
	}
	ForceLoad('problem.php');
}

if (isset($_POST["Submit3"]) && isset($_POST["problemnumber"]) && is_numeric($_POST["problemnumber"]) && 
    isset($_POST["problemname"]) && $_POST["problemname"] != "") {
	if ($_POST["confirmation"] == "confirm") {
		if ($_FILES["probleminput"]["name"] != "") {
			$type=myhtmlspecialchars($_FILES["probleminput"]["type"]);
			$size=myhtmlspecialchars($_FILES["probleminput"]["size"]);
			$name=myhtmlspecialchars($_FILES["probleminput"]["name"]);
			$temp=myhtmlspecialchars($_FILES["probleminput"]["tmp_name"]);
			if (!is_uploaded_file($temp)) {
				IntrusionNotify("file upload problem.");
				ForceLoad("../index.php");
			}
		} else $name = "";

		$param = array();
		$param['number'] = $_POST["problemnumber"];
		$param['name'] = $_POST["problemname"];
		$param['inputfilename'] = $name;
		$param['inputfilepath'] = $temp;
		$param['fake'] = 'f';
		$param['colorname'] = $_POST["colorname"];
		$param['color'] = $_POST["color"];
		DBNewProblem ($_SESSION["usertable"]["contestnumber"], $param);
	}
	ForceLoad("problem.php");
}
?>
<br>
  <script language="javascript">
    function conf2(url) {
      if (confirm("Confirm the DELETION of the PROBLEM and ALL data associated to it?")) {
		  if (confirm("Are you REALLY sure about what you are doing? DATA CANNOT BE RECOVERED!")) {
			  document.location=url;
		  } else {
			  document.location='problem.php';
		  }
      } else {
        document.location='problem.php';
      }
    }
    function conf3(url) {
      if (confirm("Confirm the UNDELETION of the PROBLEM?")) {
		  document.location=url;
	  } else {
		  document.location='problem.php';
	  }
    }
  </script>
<table width="100%" border=1>
 <tr>
  <td><b>Problem #</b></td>
  <td><b>Short Name</b></td>
  <td><b>Fullname</b></td>
  <td><b>Basename</b></td>
  <td><b>Descfile</b></td>
  <td><b>Package file</b></td>
<!--  <td><b>Compare file</b></td>
  <td><b>Timelimit</b></td>-->
  <td><b>Color</b></td>
 </tr>
<?php
	$prob = DBGetFullProblemData($_SESSION["usertable"]["contestnumber"],true);
for ($i=0; $i<count($prob); $i++) {
  echo " <tr>\n";
  if($prob[$i]["fake"]!='t') {
	  if(strpos($prob[$i]["fullname"],"(DEL)") !== false) {
		  echo "  <td nowrap><a href=\"javascript: conf3('problem.php?delete=" . $prob[$i]["number"] . "&input=" . rawurlencode($prob[$i]["inputfilename"]) . 
			  "')\">" . $prob[$i]["number"];
		  echo "(deleted)";
	  } else {
		  echo "  <td nowrap><a href=\"javascript: conf2('problem.php?delete=" . $prob[$i]["number"] . "&input=" . rawurlencode($prob[$i]["inputfilename"]) . 
			  "')\">" . $prob[$i]["number"];
	  }
	  echo "</a></td>\n";
  } else {
    echo "  <td nowrap>" . $prob[$i]["number"] . " (fake)</td>\n";
  }
  echo "  <td nowrap>" . $prob[$i]["name"] . "</td>\n";
  echo "  <td nowrap>" . $prob[$i]["fullname"] . "&nbsp;</td>\n";
  echo "  <td nowrap>" . $prob[$i]["basefilename"] . "&nbsp;</td>\n";
  if (isset($prob[$i]["descoid"]) && $prob[$i]["descoid"] != null && isset($prob[$i]["descfilename"])) {
	  echo "  <td nowrap><a href=\"../filedownload.php?" . filedownload($prob[$i]["descoid"], $prob[$i]["descfilename"]) . "\">" . 
		  basename($prob[$i]["descfilename"]) . "</td>\n";
  }
  else
    echo "  <td>&nbsp;</td>\n";
  if ($prob[$i]["inputoid"] != null) {
    $tx = $prob[$i]["inputhash"];
    echo "  <td nowrap><a href=\"../filedownload.php?" . filedownload($prob[$i]["inputoid"] ,$prob[$i]["inputfilename"]) ."\">" .
		$prob[$i]["inputfilename"] . "</a> " . 
		"<img title=\"hash: $tx\" alt=\"$tx\" width=\"25\" src=\"../images/bigballoontransp-hash.png\" />" . 
        "</td>\n";
  }
  else
    echo "  <td nowrap>&nbsp;</td>\n";
/*
  if ($prob[$i]["soloid"] != null) {
    $tx = $prob[$i]["solhash"];
    echo "  <td nowrap><a href=\"../filedownload.php?" . filedownload($prob[$i]["soloid"],$prob[$i]["solfilename"]) ."\">" . 
	$prob[$i]["solfilename"] . "</a> ".
	"<img title=\"hash: $tx\" alt=\"$tx\" width=\"25\" src=\"../images/bigballoontransp-hash.png\" />" . 
	"</td>\n";
  }
  else
    echo "  <td nowrap>&nbsp;</td>\n";
  if ($prob[$i]["timelimit"]!="")
    echo "  <td nowrap>" . $prob[$i]["timelimit"] . "</td>\n";
  else
    echo "  <td nowrap>&nbsp;</td>\n";
*/
  if ($prob[$i]["color"]!="") {
	  echo "  <td nowrap>" . $prob[$i]["colorname"] . 
		  "<img title=\"".$prob[$i]["color"]."\" alt=\"".$prob[$i]["colorname"]."\" width=\"25\" src=\"" . 
		  balloonurl($prob[$i]["color"]) . "\" /></td>\n";
  } else
    echo "  <td nowrap>&nbsp;</td>\n";
  echo " </tr>\n";
}
echo "</table>";
if (count($prob) == 0) echo "<br><center><b><font color=\"#ff0000\">NO PROBLEMS DEFINED</font></b></center>";

?>

<br><br><center><b>Clicking on a problem number will delete it.<br>
WARNING: deleting a problem will remove EVERYTHING related to it.<br>
It is NOT recommended to change anything while the contest is running.<br>
To import a problem, fill in the following fields.<br>
To replace the data of a problem, proceed as if it did not exist (data will be replaced without removing it).</b></center>

<form name="form1" enctype="multipart/form-data" method="post" action="problem.php">
  <input type=hidden name="confirmation" value="noconfirm" />
  <script language="javascript">
    function conf() {
			if(document.form1.problemname.value=="") {
				alert('Sorry, mandatory fields are empty');
			} else {
/*
				var s1 = String(document.form1.problemdesc.value);
				var l = s1.length;
				if(l >= 3 && (s1.substr(l-3,3).toUpperCase()==".IN" ||
							 s1.substr(l-4,4).toUpperCase()==".OUT" ||
							 s1.substr(l-4,4).toUpperCase()==".SOL" ||
							 s1.substr(l-2,2).toUpperCase()==".C" ||
							 s1.substr(l-2,2).toUpperCase()==".H" ||
							 s1.substr(l-3,3).toUpperCase()==".CC" ||
							 s1.substr(l-3,3).toUpperCase()==".GZ" ||
							 s1.substr(l-4,4).toUpperCase()==".CPP" ||
							 s1.substr(l-4,4).toUpperCase()==".HPP" ||
							 s1.substr(l-4,4).toUpperCase()==".ZIP" ||
							 s1.substr(l-4,4).toUpperCase()==".TGZ" ||
							 s1.substr(l-5,5).toUpperCase()==".JAVA")) {
					alert('Description file has invalid extension: ...'+s1.substr(l-3,3));
				} else {
*/
				var s2 = String(document.form1.probleminput.value);
				if(s2.length > 4) {
					if (confirm("Confirm?")) {
						document.form1.confirmation.value='confirm';
					}
				} else {
					alert('File package must be given');
				}
			}
    }
  </script>
  <center>
    <table border="0">
      <tr>
        <td width="35%" align=right>Number:</td>
        <td width="65%">
          <input type="text" name="problemnumber" value="" size="20" maxlength="20" />
        </td>
      </tr>
      <tr>
	 <td width="35%" align=right>Short Name (usually a letter):</td>
        <td width="65%">
          <input type="text" name="problemname" value="" size="20" maxlength="20" />
        </td>
      </tr>
<!--
      <tr>
        <td width="35%" align=right>Problem Fullname:</td>
        <td width="65%">
          <input type="text" name="fullname" value="" size="50" maxlength="100" />
        </td>
      </tr>
      <tr>
	 <td width="35%" align=right>Problem Basename (a.k.a. name of class expected to have the main):</td>
        <td width="65%">
          <input type="text" name="basename" value="" size="50" maxlength="100" />
        </td>
      </tr>
      <tr>
	 <td width="35%" align=right>Description file (PDF, txt, ...):</td>
        <td width="65%">
          <input type="file" name="problemdesc" value="" size="40" />
        </td>
      </tr>
-->
      <tr>
	 <td width="35%" align=right>Problem package (ZIP):</td>
        <td width="65%">
          <input type="file" name="probleminput" value="" size="40" />
        </td>
      </tr>
<!--
      <tr>
	 <td width="35%" align=right>Compare file archive (ZIP):</td>
        <td width="65%">
          <input type="file" name="problemsol" value="" size="40" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Timelimit (in sec):</td>
        <td width="65%">
          <input type="text" name="timelimit" value="" size="10" />
(optional: use a , followed by the number of repetitions to run)
        </td>
      </tr>
-->
      <tr>
        <td width="35%" align=right>Color name:</td>
        <td width="65%">
          <input type="text" name="colorname" value="" size="40" maxlength="100" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Color (RGB HTML format):</td>
        <td width="65%">
          <input type="text" name="color" value="" size="6" maxlength="6" />
        </td>
      </tr>
    </table>
  </center>
  <center>
      <input type="submit" name="Submit3" value="Send" onClick="conf()">
      <input type="reset" name="Submit4" value="Clear">
  </center>

	 <br><br><br><center>To build a problem package from files, use this link:
      <input type="submit" name="Submit5" value="Build">
</center>
</form>

</body>
</html>
