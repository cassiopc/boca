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
// Last modified 08/aug/2015 by cassio@ime.usp.br
if (!isset($_POST["confirmation"]) || $_POST["confirmation"] != "confirm")
	unset($_POST['noflush']);

require('header.php');
if(($ct = DBContestInfo($_SESSION["usertable"]["contestnumber"])) == null)
	ForceLoad("../index.php");

// SEMINFO: problem changes belong to preparation, before any team submission.
if (count($_POST) || isset($_GET['delete'])) {
    $guard=DBConnect();
    $used=pg_query_params($guard,'SELECT count(*) FROM runtable WHERE contestnumber=$1',[$_SESSION['usertable']['contestnumber']]);
    if ($ct['contestactive']==='t' || !$used || (int)pg_fetch_result($used,0,0)>0) {
        MSGError('Edição bloqueada: prova ativa, com submissões ou banco indisponível.');
        ForceLoad('problem.php');
        exit;
    }
}

if (isset($_GET["delete"]) && is_numeric($_GET["delete"]) && isset($_GET["input"])) {
	$param = array();
	$param['number']=$_GET["delete"];
	$param['inputfilename']=myrawurldecode($_GET["input"]);
	if(!DBDeleteProblem ($_SESSION["usertable"]["contestnumber"], $param)) {
		MSGError('Erro ao excluir a questão');
		LogError('Erro ao excluir a questão');
	}
	ForceLoad("problem.php");
}

if(isset($_POST['Submit5']) && $_POST['Submit5']=='Criar questão')
	ForceLoad("buildproblem.php");

if(isset($_POST['Submit5']) && $_POST['Submit5']=='Send') {
	if(isset($_POST['basename']) &&
	   isset($_POST['fullname']) &&
	   isset($_POST['timelimit']) &&
	   $_POST["confirmation"] == "confirm") {
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]{0,79}$/D',trim($_POST['basename'])) ||
            !preg_match('/^(?:[1-9][0-9]{0,2})(?:,[1-9][0-9]?)?$/D',trim($_POST['timelimit'])) ||
            strlen(trim($_POST['fullname']))<1 || strlen($_POST['fullname'])>100 ||
            preg_match('/[\r\n]/',$_POST['fullname'])) {
            MSGError('Informe o nome, o nome-base Java válido e o limite inteiro de 1 a 999 segundos (opcional: ,repetições).');
            ForceLoad('buildproblem.php'); exit;
        }
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
        if ($name2 !== '') {
            $extension=strtolower(pathinfo($name2,PATHINFO_EXTENSION));
            if (!in_array($extension,['pdf','txt'])) {
                MSGError('Use enunciado em PDF ou TXT.'); ForceLoad('buildproblem.php'); exit;
            }
            $name2='enunciado.'.$extension;
        }

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
			$filea = [];
            foreach (['compare','compile','run'] as $stage)
                foreach (['c','cpp','java','py3'] as $lang) $filea[]=$stage.$ds.$lang;
			foreach($filea as $file) {
				$rfile=$locr . $ds . '..' . $ds . 'doc' . $ds . 'problemexamples' . $ds . 'problemtemplate' . $ds . $file;
				if(is_readable($rfile)) {
					@copy($rfile, $dir . $ds . $file);
				} else {
					@unlink($tfile);
					cleardir($dir);
					ob_end_flush();
					MSGError('Não foi possível ler o arquivo do modelo de questão: ' . $rfile);
					ForceLoad('problem.php');
				}
			}
			$tl = explode(',',$_POST['timelimit']);
			if(!isset($tl[1]) || !is_numeric(trim($tl[1]))) $tl[1]='1';
			$str = "echo " . trim($tl[0]) . "\necho " . trim($tl[1]) . "\necho 512\necho " . floor(10 + $size1 / 512) . "\nexit 0\n";
			file_put_contents($dir . $ds . 'limits' . $ds . 'c',$str);
			file_put_contents($dir . $ds . 'limits' . $ds . 'cpp',$str);
			file_put_contents($dir . $ds . 'limits' . $ds . 'java',$str);
            file_put_contents($dir . $ds . 'limits' . $ds . 'py3',$str);
			$str = "basename=" . trim($_POST['basename']) . "\nfullname=\"" . addcslashes(trim($_POST['fullname']),"\\\"") . "\"";
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
				MSGError('Não foi possível ler os arquivos de entrada e saída da questão');
				ForceLoad('problem.php');
			}
			$ret=create_zip($dir, glob($dir . $ds . '*'),$dir . '.zip');
			cleardir($dir);
			if($ret <= 0) {
				@unlink($tfile);
				@unlink($dir . '.zip');
				ob_end_flush();
				MSGError('Não foi possível gravar o arquivo ZIP');
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
			MSGError('Não foi possível gravar na pasta temporária');
		}
	}
	ForceLoad('problem.php');
}

if (isset($_POST["Submit3"]) && isset($_POST["problemnumber"]) && is_numeric($_POST["problemnumber"]) && 
    isset($_POST["problemname"]) && $_POST["problemname"] != "") {
	if(strpos(trim($_POST["problemname"]),' ')!==false) {
		$_POST["confirmation"]='';
		MSGError('O nome curto da questão não pode conter espaços');
	} else {
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
		$param['name'] = trim($_POST["problemname"]);
		$param['inputfilename'] = $name;
		$param['inputfilepath'] = $temp;
		$param['fake'] = 'f';
		$param['colorname'] = trim($_POST["colorname"]);
		$param['color'] = trim($_POST["color"]);
		$autojudge_value = 0;
		if (isset ($_POST["autojudge_new_sel"]) && in_array ($_POST['autojudge_new_sel'], array ('all', 'custom', 'none'))) {
			$all_answers = DBGetAnswers($_SESSION["usertable"]["contestnumber"]);
			for ($g = 0; $g < count ($all_answers); $g++) {
				if ($all_answers[$g]['fake'] == 't') continue;
				$campo = 'autojudge_chc_new_'.$all_answers[$g]['number'];
				
				if ($_POST['autojudge_new_sel'] == 'all') {
					$autojudge_value |= pow (2, $all_answers[$g]['number']);
				} else if ($_POST['autojudge_new_sel'] == 'custom' && isset ($_POST[$campo]) && $_POST[$campo] == "1") {
					$autojudge_value |= pow (2, $all_answers[$g]['number']);
				}
			}
		}
		$param['autojudge'] = $autojudge_value;
		DBNewProblem ($_SESSION["usertable"]["contestnumber"], $param);
	}
	}
	ForceLoad("problem.php");
}

$prob = DBGetFullProblemData($_SESSION["usertable"]["contestnumber"],true);
for ($i=0; $i<count($prob); $i++) {
  if($prob[$i]["fake"]!='t') {
    if (isset($_POST["SubmitProblem" . $prob[$i]['number']]) && $_POST["SubmitProblem" . $prob[$i]['number']] == 'Update' &&
	isset($_POST["colorname" . $prob[$i]['number']]) && strlen($_POST["colorname" . $prob[$i]['number']]) <= 100 && 
	isset($_POST["color" . $prob[$i]['number']]) && strlen($_POST["color" . $prob[$i]['number']]) <= 6 && 
	isset($_POST["problemname" . $prob[$i]['number']]) && $_POST["problemname" . $prob[$i]['number']] != "" && strlen($_POST["problemname" . $prob[$i]['number']]) <= 20) {
      if(strpos(trim($_POST["problemname" . $prob[$i]['number']]),' ')!==false) {
	MSGError('O nome curto da questão não pode conter espaços');
      } else {
	$param = array();
	$param['number'] = $prob[$i]['number'];
	$param['name'] = trim($_POST["problemname" . $prob[$i]['number']]);
	$param['fake'] = 'f';
	$param['colorname'] = trim($_POST["colorname" . $prob[$i]['number']]);
	$param['color'] = trim($_POST["color" . $prob[$i]['number']]);
	if (isset ($prob[$i]['autojudge'])) {
		$param['autojudge'] = ((integer) $prob[$i]['autojudge']);
	}
	DBNewProblem ($_SESSION["usertable"]["contestnumber"], $param);
      }
      ForceLoad("problem.php");
    }
  }
}

// Update Julgamento automático
for ($i=0; $i<count($prob); $i++) {
	if($prob[$i]["fake"]=='t') continue;
	
	$sel_name = "autojudge_" . $prob[$i]['number']. "_sel";
	if (isset($_POST["SubmitProblemAJ" . $prob[$i]['number']]) && $_POST["SubmitProblemAJ" . $prob[$i]['number']] == 'Update' && isset ($_POST[$sel_name]) && in_array ($_POST[$sel_name], array ('all', 'custom', 'none'))) {
		$all_answers = DBGetAnswers($_SESSION["usertable"]["contestnumber"]);
		$value = 0;
		for ($g = 0; $g < count ($all_answers); $g++) {
			if ($all_answers[$g]['fake'] == 't') continue;
			$campo = 'autojudge_chc_'.$prob[$i]['number'].'_'.$all_answers[$g]['number'];
			
			if ($_POST[$sel_name] == 'all') {
				$value |= pow (2, $all_answers[$g]['number']);
			} else if ($_POST[$sel_name] == 'custom' && isset ($_POST[$campo]) && $_POST[$campo] == "1") {
				$value |= pow (2, $all_answers[$g]['number']);
			}
		}
		$param = array();
		$param['number'] = $prob[$i]['number'];
		$param['name'] = trim($prob[$i]['name']);
		$param['fake'] = 'f';
		$param['colorname'] = trim($prob[$i]['colorname']);
		$param['color'] = trim($prob[$i]['color']);
		$param['autojudge'] = ((integer) $value);
		DBNewProblem ($_SESSION["usertable"]["contestnumber"], $param);
		ForceLoad("problem.php");
	}
}
?>
<br>
  <script language="javascript">
    function conf2(url) {
      if (confirm("Confirmar a exclusão da questão e de todos os dados associados?")) {
		  if (confirm("Tem certeza? OS DADOS NÃO PODERÃO SER RECUPERADOS!")) {
			  document.location=url;
		  } else {
			  document.location='problem.php';
		  }
      } else {
        document.location='problem.php';
      }
    }
    function conf3(url) {
      if (confirm("Confirmar a recuperação da questão?")) {
		  document.location=url;
	  } else {
		  document.location='problem.php';
	  }
    }
  </script>
<form name="form0" enctype="multipart/form-data" method="post" action="problem.php">
<table width="100%" border=1>
 <tr>
  <td><b>Questão nº</b></td>
  <td><b>Nome curto</b></td>
  <td><b>Nome completo</b></td>
  <td><b>Nome do arquivo</b></td>
  <td><b>Enunciado</b></td>
  <td><b>Pacote</b></td>
<!--  <td><b>Arquivo de comparação</b></td>
  <td><b>Limite de tempo</b></td>-->
  <td><b>Cor</b></td>
  <td><b>Julgamento automático</b></td>
 </tr>
<?php
$all_answers = DBGetAnswers($_SESSION["usertable"]["contestnumber"]);
for ($i=0; $i<count($prob); $i++) {
  echo " <tr>\n";
  if($prob[$i]["fake"]!='t') {
	  if(strpos($prob[$i]["fullname"],"(DEL)") !== false) {
		  echo "  <td nowrap><a href=\"javascript: conf3('problem.php?delete=" . $prob[$i]["number"] . "&input=" . myrawurlencode($prob[$i]["inputfilename"]) . 
			  "')\">" . $prob[$i]["number"];
		  echo " (excluída)";
	  } else {
		  echo "  <td nowrap><a href=\"javascript: conf2('problem.php?delete=" . $prob[$i]["number"] . "&input=" . myrawurlencode($prob[$i]["inputfilename"]) . 
			  "')\">" . $prob[$i]["number"];
	  }
	  echo "</a></td>\n";
	  echo "<input type=hidden name=\"problemname" . $prob[$i]['number'] . "\" value=\"" . $prob[$i]["name"] . "\" />";
	  echo "  <td nowrap>" . $prob[$i]["name"] . "</td>\n";
	  //echo "  <td nowrap>";
	  //echo "<input type=\"text\" name=\"problemname" . $prob[$i]['number'] . "\" value=\"" . $prob[$i]["name"] . "\" size=\"4\" maxlength=\"20\" />";
	  //echo "</td>\n";
  } else {
    echo "  <td nowrap>" . $prob[$i]["number"] . " (interno)</td>\n";
    echo "  <td nowrap>" . displayClarProblem($prob[$i]["name"], $prob[$i]["number"]) . "</td>\n";
  }
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
  echo "  <td nowrap>";
  if($prob[$i]["fake"]!='t') {
    if ($prob[$i]["color"]!="") {
      echo "<img title=\"".$prob[$i]["color"]."\" alt=\"".$prob[$i]["colorname"]."\" width=\"25\" src=\"" . 
	balloonurl($prob[$i]["color"]) . "\" />\n";
    }
    echo "<input type=\"text\" name=\"colorname" . $prob[$i]['number'] . "\" value=\"" . $prob[$i]["colorname"] . "\" size=\"10\" maxlength=\"100\" />";
    echo "<input type=\"text\" name=\"color" . $prob[$i]['number'] . "\" value=\"" . $prob[$i]["color"]. "\" size=\"6\" maxlength=\"6\" />";
    echo "<button type=\"submit\" name=\"SubmitProblem" . $prob[$i]["number"] . "\" value=\"Update\">Atualizar</button>";
  } else echo "&nbsp;";
  echo "</td>\n";
  
  // Print the autojudge setting with a INPUT SELECT BOX + small boxes
  echo "  <td nowrap>";
  if($prob[$i]["fake"]!='t') {
	$autojudge_int_val = ((integer) $prob[$i]['autojudge']);
	$sel_name = "autojudge_" . $prob[$i]['number']. "_sel";
	printf ('<select id="%s" name="%s">', $sel_name, $sel_name);
	
	$all_mask = 0;
	for ($g = 0; $g < count($all_answers); $g++) {
		if ($all_answers[$g]['fake'] == 't') continue;
		$all_mask |= pow (2, $all_answers[$g]['number']);
	}
	if ($autojudge_int_val == $all_mask) {
		$dis = true;
		$dis_value = true;
		$sel_value = 'all';
	} else if ($autojudge_int_val == 0) {
		$dis = true;
		$dis_value = false;
		$sel_value = 'none';
	} else {
		$dis = false;
		$sel_value = 'custom';
	}
	
	$opts = array ('Todas as respostas' => 'all', 'Personalizado' => 'custom', 'Nenhuma' => 'none');
	foreach ($opts as $display_name => $opt) {
		if ($opt == $sel_value) {
			printf ('<option value="%s" selected="selected">%s</option>', $opt, $display_name);
		} else {
			printf ('<option value="%s">%s</option>', $opt, $display_name);
		}
	}
	
	echo "</select><br />\n";
	
	echo "<table><tr>\n";
	
	for ($g = 0; $g < count($all_answers); $g++) {
		if ($all_answers[$g]['fake'] == 't') continue;
		echo "<td>\n";
		printf ('<input type="checkbox" id=autojudge_chc_%s_%s name="autojudge_chc_%s_%s" value="1" ', $prob[$i]['number'], $g, $prob[$i]['number'], $all_answers[$g]['number']);
		if ($sel_value == 'all') {
			printf ('disabled="disabled" checked="checked" />');
		} else if ($sel_value == 'none') {
			printf ('disabled="disabled" />');
		} else {
			$mask = pow (2, $all_answers[$g]['number']);
			if (($autojudge_int_val & $mask) == $mask) {
				printf ('checked="checked" />');
			} else {
				printf (' />');
			}
		}
		echo "\n</td>\n";
	}
	echo "</tr><tr>\n";
	for ($g = 0; $g < count($all_answers); $g++) {
		if ($all_answers[$g]['fake'] == 't') continue;
		printf ('<td><label for="autojudge_chc_%s_%s">', $prob[$i]['number'], $g);
		printf ('<abbr title="%s">%s</abbr>', htmlspecialchars(displayRunAnswer($all_answers[$g]['desc']), ENT_QUOTES, 'UTF-8'), $all_answers[$g]['short']);
		echo "</label></td>\n";
	}
	echo "</tr></table>\n";
	?>
	<script type="text/javascript">
		function <?php echo "f_change_".$sel_name; ?> () {
			var sel = document.getElementById("<?php echo $sel_name; ?>");
			var v = sel.value;
			var max_ans = <?php echo count($all_answers) ?>;
			var dis = false;
			if (v == "all") {
				dis = true;
				sel = true;
			} else if (v == "none") {
				dis = true;
				sel = false;
			}
			
			for (g = 1; g < max_ans; g++) {
				var c = document.getElementById ("<?php echo 'autojudge_chc_'.$prob[$i]['number'].'_';?>" + g);
				if (dis == true) {
					c.checked = sel;
					c.disabled = true;
				} else {
					c.disabled = false;
				}
			}
		}
		var sel = document.getElementById("<?php echo $sel_name; ?>");
		sel.onchange = <?php echo "f_change_".$sel_name; ?>;
		<?php echo "f_change_".$sel_name; ?> ();
	</script>
<?php
    echo "<button type=\"submit\" name=\"SubmitProblemAJ" . $prob[$i]["number"] . "\" value=\"Update\">Atualizar</button>";
  } else echo "&nbsp;";
  echo "</td>\n";
  
  echo " </tr>\n";
}
echo "</table></form>";
if (count($prob) == 0) echo "<br><center><b><font color=\"#ff0000\">NENHUMA QUESTÃO CADASTRADA</font></b></center>";

?>

<br><br><center><b>Clique no número de uma questão para excluí-la.<br>
ATENÇÃO: a exclusão remove todos os dados associados à questão.<br>
Não altere questões durante a competição.<br>
Para importar uma questão, preencha os campos abaixo.<br>
Para substituir uma questão, informe o mesmo número e envie o novo pacote.</b></center>

<form name="form1" enctype="multipart/form-data" method="post" action="problem.php">
  <input type=hidden name="confirmation" value="noconfirm" />
  <script language="javascript">
    function conf() {
			if(document.form1.problemname.value=="") {
				alert('Preencha os campos obrigatórios');
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
					alert('Extensão inválida no arquivo do enunciado: ...'+s1.substr(l-3,3));
				} else {
*/
				var s2 = String(document.form1.probleminput.value);
				if(s2.length > 4) {
					if (confirm("Confirmar o envio da questão?")) {
						document.form1.confirmation.value='confirm';
					}
				} else {
					alert('Selecione o pacote ZIP da questão');
				}
			}
    }
  </script>
  <center>
    <table border="0">
      <tr>
        <td width="35%" align=right>Número:</td>
        <td width="65%">
          <input type="text" name="problemnumber" value="" size="20" maxlength="20" />
        </td>
      </tr>
      <tr>
	 <td width="35%" align=right>Nome curto (geralmente uma letra, sem espaços):</td>
        <td width="65%">
          <input type="text" name="problemname" value="" size="20" maxlength="20" />
        </td>
      </tr>
<!--
      <tr>
        <td width="35%" align=right>Problem Nome completo:</td>
        <td width="65%">
          <input type="text" name="fullname" value="" size="50" maxlength="100" />
        </td>
      </tr>
      <tr>
	 <td width="35%" align=right>Problem Nome do arquivo (a.k.a. name of class expected to have the main):</td>
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
	 <td width="35%" align=right>Pacote da questão (ZIP):</td>
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
        <td width="35%" align=right>Nome da cor:</td>
        <td width="65%">
          <input type="text" name="colorname" value="" size="40" maxlength="100" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Cor (formato RGB hexadecimal):</td>
        <td width="65%">
          <input type="text" name="color" value="" size="6" maxlength="6" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Julgamento automático:</td>
        <td width="65%">
          <select name="autojudge_new_sel" id="autojudge_new_sel">
            <option value="all" selected="selected">Todas as respostas</option>
            <option value="custom">Personalizado</option>
            <option value="none">Nenhuma</option>
          </select>
          <table><tr>
<?php
	for ($g = 0; $g < count($all_answers); $g++) {
		if ($all_answers[$g]['fake'] == 't') continue;
		echo "<td>\n";
		printf ('<input type="checkbox" id=autojudge_chc_new_%s name="autojudge_chc_new_%s" value="1" disabled="disabled" checked="checked" />', $g, $all_answers[$g]['number']);
		echo "\n</td>\n";
	}
	echo "</tr><tr>\n";
	for ($g = 0; $g < count($all_answers); $g++) {
		if ($all_answers[$g]['fake'] == 't') continue;
		printf ('<td><label for="autojudge_chc_new_%s">', $g);
		printf ('<abbr title="%s">%s</abbr>', htmlspecialchars(displayRunAnswer($all_answers[$g]['desc']), ENT_QUOTES, 'UTF-8'), $all_answers[$g]['short']);
		echo "</label></td>\n";
	}
	echo "</tr></table>\n";
	?>
	<script type="text/javascript">
		function f_change_new () {
			var sel = document.getElementById("autojudge_new_sel");
			var v = sel.value;
			var max_ans = <?php echo count($all_answers) ?>;
			var dis = false;
			if (v == "all") {
				dis = true;
				sel = true;
			} else if (v == "none") {
				dis = true;
				sel = false;
			}
			
			for (g = 1; g < max_ans; g++) {
				var c = document.getElementById ("autojudge_chc_new_" + g);
				if (dis == true) {
					c.checked = sel;
					c.disabled = true;
				} else {
					c.disabled = false;
				}
			}
		}
		var sel = document.getElementById("autojudge_new_sel");
		sel.onchange = f_change_new;
		</script>
        </td>
      </tr>
    </table>
  </center>
  <center>
      <input type="submit" name="Submit3" value="Enviar" onClick="conf()">
      <input type="reset" name="Submit4" value="Limpar">
  </center>

	 <br><br><br><center>Para criar um pacote de questão, use o Builder:
      <input type="submit" name="Submit5" value="Criar questão">
</center>
</form>

</body>
</html>
