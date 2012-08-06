<?php
//////////////////////////////////////////////////////////////////////////////////////////
//BOCA Online Contest Administrator. Copyright (c) 2003- Cassio Polpo de Campos.
//It may be distributed under the terms of the Q Public License version 1.0. A copy of the
//license can be found with this software or at http://www.opensource.org/licenses/qtpl.php
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
//INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
//PURPOSE AND NONINFRINGEMENT OF THIRD PARTY RIGHTS. IN NO EVENT SHALL THE COPYRIGHT HOLDER
//OR HOLDERS INCLUDED IN THIS NOTICE BE LIABLE FOR ANY CLAIM, OR ANY SPECIAL INDIRECT OR
//CONSEQUENTIAL DAMAGES, OR ANY DAMAGES WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR
//PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING
//OUT OF OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
///////////////////////////////////////////////////////////////////////////////////////////
//Change list 
// 17/aug/2007 by cassio@ime.usp.br: created
// 25/aug/2007 by cassio@ime.usp.br: php initial tag changed to complete form

require 'header.php';

if (isset($_FILES["importfile"]) && isset($_POST["Submit"]) && $_FILES["importfile"]["name"]!="") {
        if ($_POST["confirmation"] == "confirm") {
                $type=myhtmlspecialchars($_FILES["importfile"]["type"]);
                $size=myhtmlspecialchars($_FILES["importfile"]["size"]);
                $name=myhtmlspecialchars($_FILES["importfile"]["name"]);
                $temp=myhtmlspecialchars($_FILES["importfile"]["tmp_name"]);
                if (!is_uploaded_file($temp)) {
                        IntrusionNotify("file upload problem.");
                        ForceLoad("../index.php");
                }
                if (($ar = file($temp)) === false) {
                        IntrusionNotify("Unable to open the uploaded file.");
                        ForceLoad("../index.php");
                }
		echo "<br>Starting to create the contest<br>";
		$asep = trim($ar[0]);
		$i=1;

		for (; $i<count($ar) && strpos($ar[$i], "[contest]") === false; $i++) ;
		for ($i++; $i<count($ar) && $ar[$i][0] != "["; $i++) {
			$x = trim($ar[$i]);
        		//contestname, startdate, duration, lastmileanswer, lastmilescore, penalty, contestactive
			$tmp = explode("=", $x, 2);
			$param[trim($tmp[0])]=trim($tmp[1]);
		}
		$nc = DBNewContest($param);
		echo "<br>Contest $nc created<br>";

		for (; $i<count($ar) && strpos($ar[$i], "[site]") === false; $i++) ;
		while(strpos($ar[$i],"[site]") === true) {
			for ($i++; $i<count($ar) && $ar[$i][0] != "["; $i++) {
				$x = trim($ar[$i]);
				// sitenumber, siteip, sitename, scorelevel 
				$tmp = explode("=", $x, 2);
				$param[trim($tmp[0])]=trim($tmp[1]);
			}
			DBNewSite($nc, null, $param);
			echo "New site created<br>";
		}

		for (; $i<count($ar) && strpos($ar[$i], "[answer]") === false; $i++) ;
		for ($i++; $i<count($ar) && $ar[$i][0] != "["; $i++) {
			echo "<br>Searching for answers<br>\n";
			$x = trim($ar[$i]);
			if (strpos($x, "answ") !== false && strpos($x, "answ") == 0) {
				unset($answnumber);
				unset($answname);
				unset($answyes);
				while (strpos($x, "answ") !== false && strpos($x, "answ") == 0) {
					$tmp = explode ("=", $x, 2);
					switch (trim($tmp[0])) {
						case "answernumber":		$answnumber	=trim($tmp[1]); break;
						case "answername":		$answname	=trim($tmp[1]); break;
						case "answeryes":		$answyes	=trim($tmp[1]); break;
					}
					$i++;
					if ($i>=count($ar)) break;
					$x = trim($ar[$i]);
				}
				if (isset($answnumber) && is_numeric($answnumber) && isset($answname)) {
					DBNewAnswer ($nc, $answnumber, $answname, $answyes);
					echo "Answer $answnumber created<br>";
				}
			}
		}

		for (; $i<count($ar) && strpos($ar[$i], "[language]") === false; $i++) ;
		for ($i++; $i<count($ar) && $ar[$i][0] != "["; $i++) {
			echo "<br>Searching for languages<br>\n";
			$x = trim($ar[$i]);
			if (strpos($x, "lang") !== false && strpos($x, "lang") == 0) {
				unset($langnumber);
				unset($langname);
				unset($langproblem);	
				unset($script);
				unset($compscript);
				unset($langscript);
				unset($langcompscript);
				unset($langscripthash);
				unset($langcompscripthash);
				while (strpos($x, "lang") !== false && strpos($x, "lang") == 0) {
					$tmp = explode ("=", $x, 2);
					switch (trim($tmp[0])) {
						case "langnumber":		$langnumber	=trim($tmp[1]); break;
						case "langname":		$langname	=trim($tmp[1]); break;
						case "langproblem":		$langproblem	=trim($tmp[1]); break;
						case "langscripthash":		$langscripthash	=trim($tmp[1]); break;
						case "langscript":		$langscript	=trim($tmp[1]);
							$i++;
							for ($j=1; trim($ar[$i]) != "***$asep***"; $j++) {
								if(substr($langscript,0,7)!="base64:") $script .= $ar[$i];
								else $script .= trim($ar[$i]);
								$i++;
							}
							if(substr($langscript,0,7)=="base64:") {
								$langscript = substr($langscript,7);
								$script = base64_decode($script);
							}
							if(trim($langscripthash) != "" && myshorthash($script) != trim($langscripthash))
								echo "ERROR: Hash of $langscript does not match $langscripthash, ".myshorthash($script)."<br>\n";
							break;
						case "langcompscripthash":	$langcompscripthash=trim($tmp[1]); break;
						case "langcompscript":		$langcompscript	=trim($tmp[1]); 
							$i++;
							for ($j=1; trim($ar[$i]) != "***$asep***"; $j++) {
								if(substr($langcompscript,0,7)!="base64:") $compscript .= $ar[$i];
								else $compscript .= trim($ar[$i]);
								$i++;
							}
							if(substr($langcompscript,0,7)=="base64:") {
								$langcompscript = substr($langcompscript,7);
								$compscript = base64_decode($compscript);
							}
							if(trim($langcompscripthash) != "" && myshorthash($compscript) != trim($langcompscripthash))
								echo "ERROR: Hash of $langcompscript does not match<br>\n";
							break;
					}
					$i++;
					if ($i>=count($ar)) break;
					$x = trim($ar[$i]);
				}
				if (isset($langnumber) && is_numeric($langnumber) && isset($langname)) {
					DBNewLanguage ($nc,
					  $langnumber, $langname, $langproblem, '', '', 
					  $langshowingoutput, $script, $langscript, $compscript, $langcompscript, 1, 1);
					echo "Language $langnumber created<br>";
				}
			}
		}

		for (; $i<count($ar) && strpos($ar[$i], "[problem]") === false; $i++) ;
		for ($i++; $i<count($ar) && $ar[$i][0] != "["; $i++) {
			echo "<br>Searching for problems<br>\n";
			$x = trim($ar[$i]);
			if (strpos($x, "prob") !== false && strpos($x, "prob") == 0) {
				unset($probnumber);
				unset($probname);
				unset($probfullname);	
				unset($probbasename);
				unset($probinputfile);
				unset($probinputfilehash);
				unset($probinputfilepath);
				unset($probsolfile);
				unset($probsolfilehash);
				unset($probsolfilepath);
				unset($probdescfile);
				unset($probdescfilehash);
				unset($probdescfilepath);
				unset($probtimelimit);
				unset($probcolorname);
				unset($probcolor);
				while (strpos($x, "prob") !== false && strpos($x, "prob") == 0) {
					$tmp = explode ("=", $x, 2);
					switch (trim($tmp[0])) {
						case "probnumber":		$probnumber	=trim($tmp[1]); break;
						case "probname":		$probname	=trim($tmp[1]); break;
						case "probfullname":		$probfullname	=trim($tmp[1]); break;
						case "probbasename":	 	$probbasename	=trim($tmp[1]); break;
						case "probtimelimit":		$probtimelimit	=trim($tmp[1]); break;
						case "probcolorname":		$probcolorname	=trim($tmp[1]); break;
						case "probcolor":		$probcolor	=trim($tmp[1]); break;
						case "probinputfilehash":	$probinputfilehash  =trim($tmp[1]); break;
						case "probinputfile":		$probinputfile  =trim($tmp[1]); 
							$i++;
							for ($j=1; trim($ar[$i]) != "***$asep***"; $j++) {
								if(substr($probinputfile,0,7)!="base64:") $probinputfilepath .= $ar[$i];
								else $probinputfilepath .= trim($ar[$i]);
								$i++;
							}
							if(substr($probinputfile,0,7)=="base64:") {
								$probinputfile = substr($probinputfile,7);
								$probinputfilepath = base64_decode($probinputfilepath);
							}
							if(trim($probinputfilehash) != "" && myshorthash($probinputfilepath) != trim($probinputfilehash))
								echo "ERROR: Hash of $probinputfile does not match<br>\n";
							break;
						case "probsolfilehash":		$probsolfilehash	=trim($tmp[1]); break;
						case "probsolfile":		$probsolfile	=trim($tmp[1]); 
							$i++;
							for ($j=1; trim($ar[$i]) != "***$asep***"; $j++) {
								if(substr($probsolfile,0,7)!="base64:") $probsolfilepath .= $ar[$i];
								else $probsolfilepath .= trim($ar[$i]);
								$i++;
							}
							if(substr($probsolfile,0,7)=="base64:") {
								$probsolfile = substr($probsolfile,7);
								$probsolfilepath = base64_decode($probsolfilepath);
							}
							if(trim($probsolfilehash) != "" && myshorthash($probsolfilepath) != trim($probsolfilehash))
								echo "ERROR: Hash of $probsolfile does not match<br>\n";
							break;
						case "probdescfilehash":		$probdescfilehash=trim($tmp[1]); break;
						case "probdescfile":		$probdescfile	=trim($tmp[1]); 
							$i++;
							for ($j=1; trim($ar[$i]) != "***$asep***"; $j++) {
								if(substr($probdescfile,0,7)!="base64:") $probdescfilepath .= $ar[$i];
								else $probdescfilepath .= trim($ar[$i]);
								$i++;
							}
							if(substr($probdescfile,0,7)=="base64:") {
								$probdescfile = substr($probdescfile,7);
								$probdescfilepath = base64_decode($probdescfilepath);
							}
							if(trim($probdescfilehash) != "" && myshorthash($probdescfilepath) != trim($probdescfilehash))
								echo "ERROR: Hash of $probdescfile does not match<br>\n";
							break;
					}
					$i++;
					if ($i>=count($ar)) break;
					$x = trim($ar[$i]);
				}
				if (isset($probnumber) && is_numeric($probnumber) && isset($probname) &&
				    isset($probfullname) && isset($probbasename) && 
				    isset($probtimelimit) && isset($probcolorname) && isset($probcolor)) {
					DBNewProblem ($nc,
					  $probnumber, $probname, $probfullname, $probbasename, $probinputfile, 
                       		          $probinputfilepath, $probsolfile, $probsolfilepath, 'f', $probdescfile, $probdescfilepath, 
					  $probtimelimit, $probcolorname, $probcolor, 1, 1, 1);
					echo "Problem $probnumber created<br>";
				}
			}
		}
	}
	echo "</body></html>";
	exit;
}
?>
<br>
<br>
<center><b>
To import a pre-defined contest, just fill in the import file field.</b></center>
<br>
<form name="form1" enctype="multipart/form-data" method="post" action="import.php">
  <input type=hidden name="confirmation" value="noconfirm" />
  <center>
    <table border="0">
      <tr>
        <td width="25%" align=right>Import file:</td>
        <td width="75%">
          <input type="file" name="importfile" size="40">
        </td>
      </tr>
    </table>
  </center>
  <script language="javascript">
    function conf() {
      if (confirm("Confirm?")) {
        document.form1.confirmation.value='confirm';
      }
    }
  </script>
  <center>
      <input type="submit" name="Submit" value="Import" onClick="conf()">
      <input type="reset" name="Submit2" value="Clear">
  </center>
</form>

</body>
</html>

