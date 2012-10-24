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
// Last modified 21/jul/2012 by cassio@ime.usp.br

function DBDropProblemTable() {
	$c = DBConnect();
	$r = DBExec($c, "drop table \"problemtable\"", "DBDropProblemTable(drop table)");
}
function DBCreateProblemTable() {
	$c = DBConnect();
	$conf = globalconf();
	if($conf["dbuser"]=="") $conf["dbuser"]="bocauser";
	$r = DBExec($c, "
CREATE TABLE \"problemtable\" (
\"contestnumber\" int4 NOT NULL,                  -- (id do concurso)
\"problemnumber\" int4 NOT NULL,                  -- (id do problema)
\"problemname\" varchar(20) NOT NULL,             -- (nome do problema)
\"problemfullname\" varchar(100) DEFAULT '',      -- (nome completo do problema)
\"problembasefilename\" varchar(100),             -- (nome base dos arquivos do problema)
\"probleminputfilename\" varchar(100) DEFAULT '',            -- (nome do arquivo de entrada)
\"probleminputfile\" oid,                         -- (apontador para o arquivo de entrada)
\"probleminputfilehash\" varchar(50),                         -- (apontador para o arquivo de entrada)
\"fake\" bool DEFAULT 'f' NOT NULL,               -- (indica se o problema eh valido para submissoes. Util para
                                                --  clarification em General, por exemplo)
\"problemcolorname\" varchar(100) DEFAULT '',	  -- nome da cor do problema
\"problemcolor\" varchar(6) DEFAULT '',		  -- cor do problema, formato html (RGB hexadecimal)
\"updatetime\" int4 DEFAULT EXTRACT(EPOCH FROM now()) NOT NULL, -- (indica a ultima atualizacao no registro)
-- (tabela com os problemas. Se um problema tiver mais que par de arquivos
-- entrada/solucao, entao colocamos mais que uma linha para ele aqui.)
CONSTRAINT \"problem_pkey\" PRIMARY KEY (\"contestnumber\", \"problemnumber\"),
CONSTRAINT \"contest_fk\" FOREIGN KEY (\"contestnumber\") REFERENCES \"contesttable\" (\"contestnumber\")
           ON DELETE CASCADE ON UPDATE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
)", "DBCreateProblemTable(create table)");
         $r = DBExec($c, "REVOKE ALL ON \"problemtable\" FROM PUBLIC", "DBCreateProblemTable(revoke public)");
	 $r = DBExec($c, "GRANT ALL ON \"problemtable\" TO \"".$conf["dbuser"]."\"", "DBCreateProblemTable(grant bocauser)");
	 $r = DBExec($c, "CREATE UNIQUE INDEX \"problem_index\" ON \"problemtable\" USING btree ".
	      "(\"contestnumber\" int4_ops, \"problemnumber\" int4_ops)", "DBCreateProblemTable(create problem_index)");
	 $r = DBExec($c, "CREATE INDEX \"problem_index2\" ON \"problemtable\" USING btree ".
              "(\"contestnumber\" int4_ops, \"problemname\" varchar_ops)", "DBCreateProblemTable(create problem_index2)");
}

function DBinsertfakeproblem($n,$c) {
	DBExec($c, "insert into problemtable (contestnumber, problemnumber, problemname, problemfullname, ".
		"problembasefilename, probleminputfilename, probleminputfile, fake) values ($n, 0, 'General', 'General', NULL, NULL, ".
	   	"NULL, 't')", "DBNewContest(insert problem)");
}

//////////////////////funcoes de problemas//////////////////////////////////////////////////////////////
//recebe um numero de contest e numero de problema
//devolve todos os dados relativos ao problema em cada linha do array, sendo que cada linha representa o fato
//que existe mais que um arquivo de entrada/sol. Nao retorna dados sobre problemas fake, ja que eles nao devem ter.
function DBGetProblemData($contestnumber, $problemnumber, $c=null) {
	if($c==null)
		$c = DBConnect();
	$r = DBExec($c, "select p.problemname as problemname, p.problemfullname as fullname, p.problembasefilename " . 
			"as basefilename, p.problemnumber as number, " .
			"p.problemcolor as color, p.problemcolorname as colorname, " .
			"p.probleminputfilename as inputfilename, p.probleminputfile as inputoid, p.probleminputfilehash as inputhash " .
			" from problemtable as p where p.contestnumber=$contestnumber and p.problemnumber=$problemnumber and p.fake!='t'",
		      "DBGetProblemData(get problem)");
	$n = DBnlines($r);
	if ($n == 0) {
		LOGError("Unable to find problem data in the database ($contestnumber, $problemnumber)");
		MSGError("Unable to find problem data in the database. Contact an admin now!");
		exit;
	}
	$a = array();
	for ($i=0;$i<$n;$i++) {
		$a[$i] = DBRow($r,$i);
		if(isset($_SESSION['locr'])) {
		$ds = DIRECTORY_SEPARATOR;
		if($ds=="") $ds = "/";
		$nn = $a[$i]['number'];
		$ptmp = $_SESSION["locr"] . $ds . "private" . $ds . "problemtmp" . $ds . "contest" . $contestnumber ."-problem" . $nn;
		if(is_readable($ptmp . ".name")) {
			$a[$i]['descfilename']=trim(file_get_contents($ptmp . ".name"));
			if($a[$i]['descfilename'] != '')
				$a[$i]['descoid']=-1;
		}
		}
	}
	return $a;
}
function DBClearProblemTmp($contestnumber) {
	$ds = DIRECTORY_SEPARATOR;
	if($ds=="") $ds = "/";
	$ptmp = $_SESSION["locr"] . $ds . "private" . $ds . "problemtmp" . $ds . "contest" . $contestnumber . "-*.name";
	foreach(glob($ptmp) as $file) @unlink($file);
	$ptmp = $_SESSION["locr"] . $ds . "private" . $ds . "problemtmp" . $ds . "contest" . $contestnumber . "-*.hash";
	foreach(glob($ptmp) as $file) @unlink($file);
}
function DBGetFullProblemData($contestnumber,$freeproblems=false) {
	$c = DBConnect();
	DBExec($c, "begin work", "GetFullProblemData");
	$r = DBExec($c, "select p.problemnumber as number, p.problemname as name, p.problemfullname as fullname, " .
			"p.problembasefilename as basefilename, p.fake as fake, " .
			"p.problemcolor as color, p.problemcolorname as colorname, " .
			"p.probleminputfilename as inputfilename, p.probleminputfile as inputoid, p.probleminputfilehash as inputhash " .
			" from problemtable as p " .
		      "where p.contestnumber=$contestnumber order by p.problemnumber",
		    "DBGetFullProblemData(get problem)");
             // and p.problemfullname !~ '(DEL)'
	$n = DBnlines($r);
	if ($n == 0) {
		LOGLevel("No problems defined in the database ($contestnumber)",1);
	}
	$cf = globalconf();
	$a = array();
	$ds = DIRECTORY_SEPARATOR;
	if($ds=="") $ds = "/";
	for ($i=0;$i<$n;$i++) {
           $a[$i] = array_merge(array(),DBRow($r,$i));

		if(strpos($a[$i]['fullname'],'(DEL)') !== false) continue;

		$nn=$a[$i]['number'];
		$ptmp = $_SESSION["locr"] . $ds . "private" . $ds . "problemtmp" . $ds . "contest" . $contestnumber ."-problem" . $nn;
		$ck = myshorthash('');
		if(is_readable($ptmp . ".hash")) {
			$ck = trim(file_get_contents($ptmp . ".hash"));
		}
		if($ck != $a[$i]['inputhash']) {
			@unlink($ptmp . ".name");
			@unlink($ptmp . ".hash");
			$a[$i]['basefilename']='';
			$a[$i]['descfilename']='';
			$a[$i]['fullname']='';
		}
		if($freeproblems && $a[$i]['fake'] != 't') {
			if(is_readable($ptmp . ".name")) {
				$a[$i]['descfilename']=trim(file_get_contents($ptmp . ".name"));
				if($a[$i]['descfilename'] != '')
					$a[$i]['descoid']=-1;
			} else {
				@unlink($ptmp . ".name");
				@unlink($ptmp . ".hash");
				$randnum = session_id() . "_" . rand();
				$dir = $ptmp . '-' . $randnum;
				@mkdir($dir,0770,true);
				$failed=0;
				if(($ret=DB_lo_export($contestnumber, $c, $a[$i]["inputoid"], $dir . $ds . "tmp.zip")) === false) {
					LogError("FreeProblems: Unable to read problem package from database (problem=$nn, contest=$contestnumber)");
					$failed=1;
				}
				if(!$failed) {
					$zip = new ZipArchive;
					if ($zip->open($dir . $ds . "tmp.zip") === true) {
						$zip->extractTo($dir);
						$zip->close();
						if(($info=@parse_ini_file($dir . $ds . "description" . $ds . 'problem.info'))===false) {
							$failed=2;
						}
						if(!$failed) {
							$descfile='';
							if(isset($info['descfile']))
								$descfile=trim(sanitizeText($info['descfile']));
							$basename=trim(sanitizeText($info['basename']));
							$fullname=trim(sanitizeText($info['fullname']));
							if($basename=='' || $fullname=='')
								$failed=3;
						}
					} else $failed=4;
					if(!$failed) {
						@mkdir($ptmp);
						if($descfile != '') {
							if(file_put_contents($ptmp . $ds . $descfile, encryptData(file_get_contents($dir . $ds . "description" . $ds . $descfile),$cf['key']),LOCK_EX)===FALSE)
								$failed=5;
							if(!$failed) {
								file_put_contents($ptmp . ".name",$ptmp . $ds . $descfile);
								file_put_contents($ptmp . ".hash",$a[$i]['inputhash']);
								if(is_readable($ptmp . ".name")) {
									$a[$i]['descfilename']=trim(file_get_contents($ptmp . ".name"));
									if($a[$i]['descfilename'] != '')
										$a[$i]['descoid']=-1;
								}
							}
						} else {
							@unlink($ptmp . ".name");
							@unlink($ptmp . ".hash");
						}
						if(!$failed) {
							DBExec($c,"update problemtable set problemfullname='$fullname', problembasefilename='$basename' where problemnumber=$nn and contestnumber=$contestnumber",
								   "DBGetFullProblemData(free problem)");
							$a[$i]['basefilename']=$basename;
							$a[$i]['fullname']=$fullname;
						}
					}
				}
				if($failed) {
					$a[$i]['basefilename']='';
					$a[$i]['descfilename']='';
					@unlink($ptmp . ".name");
					@unlink($ptmp . ".hash");
					DBExec($c,"update problemtable set problemfullname='', problembasefilename='' where problemnumber=$nn and contestnumber=$contestnumber",
						   "DBGetFullProblemData(unfree problem)");

					if($failed!=4) {
						LogError("Failed to unzip problem package (failcode=$failed, problem=$nn, contest=$contestnumber)");
						if($failed==1) $a[$i]['fullname']='(ERROR READING FROM DATABASE, OR DIRECTORY PERMISSION PROBLEM)';
						else $a[$i]['fullname']='(PROBLEM PACKAGE SEEMS INVALID)';
					} else {
						if($ret==1) $a[$i]['fullname']='(PROBABLY ENCRYPTED FILE)';
						if($ret==2) $a[$i]['fullname']='(FILE IS NOT A ZIP)';
					}
				}
				cleardir($dir,false,true);
			}	
		}
	}
	DBExec($c, "commit", "GetFullProblemData");
	return $a;
}
function DBDeleteProblem($contestnumber, $param, $c=null) {
	$ac=array('number','inputfilename');
	foreach($ac as $key) {
		if(!isset($param[$key])) return false;
		$$key = sanitizeText($param[$key]);
	}

	$sql = "select * from problemtable where problemnumber=$number and contestnumber=$contestnumber and fake='f'";
	if ($inputfilename != "")
		$sql .= " and probleminputfilename='$inputfilename'";

	$cw = false;
	if($c == null) {
		$cw = true;
		$c = DBConnect();
        DBExec($c, "begin work", "DBDeleteProblem(transaction)");
	}

	$r = DBExec($c, $sql . " for update", "DBDeleteProblem(get for update)");
	if(DBnlines($r)>0) {
		$a = DBRow($r,0);
		if(($pos=strpos($a["problemfullname"],"(DEL)")) !== false) {
			$sql="update problemtable set problemfullname='".substr($a["problemfullname"],0,$pos) ."', updatetime=".time().
				" where contestnumber=$contestnumber and problemnumber=$number ";
		} else {
			$sql="update problemtable set problemfullname='".$a["problemfullname"] ."(DEL)', updatetime=".time().
				" where contestnumber=$contestnumber and problemnumber=$number ";
		}
		if ($inputfilename != "")
			$sql .= " and probleminputfilename='$inputfilename'";
		$r = DBExec($c, $sql, "DBDeleteLanguage(update)");
		$r = DBExec($c,"select runnumber as number, runsitenumber as site from runtable where contestnumber=$contestnumber and runproblem=$number for update");
		$n = DBnlines($r);
		for ($i=0;$i<$n;$i++) {
			$a = DBRow($r,$i);
			DBRunDelete($a["number"],$a["site"],$contestnumber,$_SESSION["usertable"]["usernumber"],$_SESSION["usertable"]["usersitenumber"]);
		}
	}
	if($cw)
		DBExec($c, "commit", "DBDeleteProblem(commit)");
			$ds = DIRECTORY_SEPARATOR;
			if($ds=="") $ds = "/";
	
	$ptmp = $_SESSION["locr"] . $ds . "private" . $ds . "problemtmp" . $ds . "contest" . $contestnumber ."-problem" . $number;
	@unlink($ptmp . ".name");
	@unlink($ptmp . ".hash");

	LOGLevel("Problem $number (inputfile=$inputfilename) deleted (user=".
			 $_SESSION["usertable"]["username"]."/".$_SESSION["usertable"]["usersitenumber"] . ")",2);
	return true;
}
function DBNewProblem($contestnumber, $param, $c=null) {
	if(isset($param["action"]) && $param["action"]=="delete") {
		return DBDeleteProblem($contestnumber, $param);
	}

	$ac=array('number','name');
	$type['number']=1;
	$type['updatetime']=1;
	$ac1=array('colorname','fake','color','updatetime','fullname',
			   'basename','inputfilename','inputfilepath');
	$colorname='';
	$color='';
	$fake='f';
	foreach($ac as $key) {
		if(!isset($param[$key])) {
			MSGError("DBNewProblem param error: $key is not set");
			return false;
		}
		if(isset($type[$key]) && !is_numeric($param[$key])) {
			MSGError("DBNewProblem param error: $key is not numeric");
			return false;
		}
		$$key = sanitizeText($param[$key]);
	}
	$basename='';
	$inputfilename='';
	$inputfilepath='';
	$fullname='';
	$updatetime=-1;
	foreach($ac1 as $key) {
		if(isset($param[$key])) {
			if(isset($type[$key]) && !is_numeric($param[$key])) {
				MSGError("DBNewProblem param error: $key is not numeric");
				return false;
			}
			$$key = sanitizeText($param[$key]);
		}
	}
	$t = time();
	if($updatetime <= 0)
		$updatetime=$t;
	$inputhash = '';

	$sql2 = "select * from problemtable where contestnumber=$contestnumber and problemnumber=$number for update";
// "select * from problemtable where contestnumber=$contestnumber and problemnumber=$number " .
// "and probleminputfilename='$inputfilename'";

	$cw = false;
	if($c == null) {
		$cw = true;
		$c = DBConnect();
		DBExec($c, "begin work", "DBNewProblem(transaction)");
	}
	$r = DBExec ($c, $sql2, "DBNewProblem(get problem for update)");
	$n = DBnlines($r);
	$ret=1;
	$oldfullname='';
	$deservesupdatetime=false;
	if ($n == 0) {
		DBExec ($c, "insert into problemtable (contestnumber, problemnumber, problemname, problemcolor) values " .
				"($contestnumber, $number, '$name','-1')", "DBNewProblem(insert problem)");
		$deservesupdatetime=true;
		$s = "created";
	}
	else {
		$lr = DBRow($r,0);
		$t = $lr['updatetime'];
		$oldfullname=$lr['problemfullname'];
		$s = "updated";
		$inputhash = $lr['probleminputfilehash'];
	}
	if($s=="created" || $updatetime > $t) {
		if(substr($inputfilepath,0,7)!="base64:") {
			if ($inputfilepath != "") {
				$hash = myshorthash(file_get_contents($inputfilepath));
				if($hash != $inputhash) {
					$oldoid='';
					if(isset($lr))
						$oldoid = $lr['probleminputfile'];
					if (($oid1 = DB_lo_import($c, $inputfilepath)) === false) {
						DBExec($c, "rollback work", "DBNewProblem(rollback-input)");
						LOGError("Unable to create a large object for file $inputfilename.");
						MSGError("problem importing file to database. See log for details!");
						exit;
					}
					if($oldoid != '') DB_lo_unlink($c,$oldoid);
					$inputhash = DBcrc($contestnumber, $oid1, $c);
				} else
					$oid1 = $lr['probleminputfile'];
			}
		} else {
			$inputfilepath = base64_decode(substr($inputfilepath,7));
			$hash = myshorthash($inputfilepath);
			if($hash != $inputhash) {				
				$oldoid='';
				if(isset($lr))
					$oldoid = $lr['probleminputfile'];
				if (($oid1 = DB_lo_import_text($c, $inputfilepath)) == null) {
					DBExec($c, "rollback work", "DBNewProblem(rollback-i-import)");
					LOGError("Unable to import the large object for file $inputfilename.");
					MSGError("problem importing file to database. See log for details!");
					exit;
				}
				if($oldoid != '') DB_lo_unlink($c,$oldoid);
				$inputhash = DBcrc($contestnumber, $oid1, $c);
			} else
				$oid1 = $lr['probleminputfile'];
		}
		if ($name != "")
			DBExec ($c, "update problemtable set problemname='$name' where contestnumber=$contestnumber ". 
					"and problemnumber=$number", "DBNewProblem(update name)");
		if ($fullname != "" || strpos($oldfullname,'(DEL)')!==false) {
			$deservesupdatetime=true;
			DBExec ($c, "update problemtable set problemfullname='$fullname' where contestnumber=$contestnumber ".
					"and problemnumber=$number", "DBNewProblem(update fullname)");
		}
		if ($basename != "") {
			$deservesupdatetime=true;
			DBExec ($c, "update problemtable set problembasefilename='$basename' where contestnumber=$contestnumber ".
					"and problemnumber=$number", "DBNewProblem(update basename)");
		}
		if ($colorname != "")
			DBExec ($c, "update problemtable set problemcolorname='$colorname' where contestnumber=$contestnumber ".
					"and problemnumber=$number", "DBNewProblem(update colorname)");
		if ($color != "")
			DBExec ($c, "update problemtable set problemcolor='$color' where contestnumber=$contestnumber ".
					"and problemnumber=$number", "DBNewProblem(update color)");
		if ($inputfilename != "") {
			$deservesupdatetime=true;
			DBExec ($c, "update problemtable set probleminputfilename='$inputfilename' where ".
					"contestnumber=$contestnumber and problemnumber=$number ", "DBNewProblem(update inputfilename)");
		}
		if ($inputfilepath != "") {
			$deservesupdatetime=true;
			DBExec ($c, "update problemtable set probleminputfile=$oid1,probleminputfilehash='$inputhash' where contestnumber=$contestnumber and ".
					"problemnumber=$number ", "DBNewProblem(update inputfile)");
		}
		if ($fake == "t") {
			$deservesupdatetime=true;
			DBExec ($c, "update problemtable set fake='$fake' where contestnumber=$contestnumber and ".
					"problemnumber=$number", "DBNewProblem(update fake)");
		}
		
		if($deservesupdatetime) {
			$ds = DIRECTORY_SEPARATOR;
			if($ds=="") $ds = "/";
			@unlink($_SESSION["locr"] . $ds . "private" . $ds . "problemtmp" . $ds . "contest" . $contestnumber ."-problem" . $number . '.name');
			DBExec ($c, "update problemtable set updatetime=" . $updatetime .
					" where contestnumber=$contestnumber and problemnumber=$number", "DBNewProblem(time)");
		}
		if($cw)
			DBExec($c, "commit work", "DBNewProblem(commit)");
		LOGLevel ("Problem $number (inputfile=$inputfilename) $s (user=".$_SESSION["usertable"]["usernumber"].
				  ",site=".$_SESSION["usertable"]["usersitenumber"].",contest=$contestnumber)", 2);
		$ret=2;
	} else {
		if($cw)
			DBExec($c, "commit work", "DBNewProblem(commit)");
	}
	return $ret;
}
//recebe o numero do contest
//devolve um array, onde cada linha tem os atributos number (numero do problema), problem (nome do problema),
//descfilename (nome do arquivo com a descricao do problema) e descoid (large object com a descricao)
function DBGetProblems($contest,$showanyway=false) {
	if (($b = DBSiteInfo($contest,$_SESSION["usertable"]["usersitenumber"])) == null)
		return array();

	if ($b["currenttime"] < 0 && !$showanyway)
		return array();

	$c = DBConnect();
	$sql = "select distinct p.problemnumber as number, p.problemname as problem, " .
		"p.problemfullname as fullname, p.problembasefilename as basefilename, " .
			"p.problemcolor as color, p.problemcolorname as colorname " .
	       "from problemtable as p where p.fake!='t' and p.contestnumber=$contest and p.problemfullname !~ '(DEL)' order by p.problemnumber";
	$r = DBExec($c, $sql, "DBGetProblems(get problems)");
	$n = DBnlines($r);
	$a = array();
	for ($i=0;$i<$n;$i++) {
		$a[$i] = DBRow($r,$i);

		$ds = DIRECTORY_SEPARATOR;
		if($ds=="") $ds = "/";
		$nn = $a[$i]['number'];
		$ptmp = $_SESSION["locr"] . $ds . "private" . $ds . "problemtmp" . $ds . "contest" . $contest ."-problem" . $nn;
		if(is_readable($ptmp . ".name")) {
			$a[$i]['descfilename']=trim(file_get_contents($ptmp . ".name"));
			if($a[$i]['descfilename'] != '')
				$a[$i]['descoid']=-1;
		}
	}
	return $a;
}
//recebe o numero do contest
//devolve um array, onde cada linha tem os atributos number (numero do problema) e problem (nome do problema)
//para todos os problemas, inclusive os fakes
function DBGetAllProblems($contest) {
	if (($b = DBSiteInfo($contest,$_SESSION["usertable"]["usersitenumber"])) == null)
		return array();

	$c = DBConnect();
	$sql = "select distinct p.problemnumber as number, p.problemname as problem, " .
			"p.problemcolor as color, p.problemcolorname as colorname " .
			"from problemtable as p " .
			"where p.contestnumber=$contest and p.problemfullname !~ '(DEL)' ";
	if ($b["currenttime"] < 0) $sql .= "and p.fake='t' ";
	$sql .= " order by p.problemnumber";
	$r = DBExec($c, $sql, "DBGetAllProblems(get problems)");

	$n = DBnlines($r);
	$a = array();
	for ($i=0;$i<$n;$i++)
		$a[$i] = DBRow($r,$i);
	return $a;
}
// eof
?>
