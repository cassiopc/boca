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

function DBDropAnswerTable() { 
	 $c = DBConnect();
	 $r = DBExec($c, "drop table \"answertable\"", "DBDropAnswerTable(drop table)");
}
function DBCreateAnswerTable() { 
	 $c = DBConnect();
	 $conf = globalconf();
	 if($conf["dbuser"]=="") $conf["dbuser"]="bocauser";
	 $r = DBExec($c, "
CREATE TABLE \"answertable\" (
\"contestnumber\" int4 NOT NULL,		-- (id do concurso)
\"answernumber\" int4 NOT NULL,			-- (id da reposta)
\"runanswer\" varchar(50) NOT NULL,		-- (reposta dada no julgamento)
\"yes\" bool DEFAULT 'f' NOT NULL,		-- (flag para indicar se conta ponto)
\"fake\" bool DEFAULT 'f' NOT NULL,		-- (flag para indicar se eh resposta valida)
\"updatetime\" int4 DEFAULT EXTRACT(EPOCH FROM now()) NOT NULL, -- (indica a ultima atualizacao no registro)
-- (tipos possiveis de respostas dos juizes. O flag eh para indicar se a resposta eh YES ou NO.)
CONSTRAINT \"answer_pkey\" PRIMARY KEY (\"contestnumber\", \"answernumber\"),
CONSTRAINT \"contest_fk\" FOREIGN KEY (\"contestnumber\") REFERENCES \"contesttable\" (\"contestnumber\") 
	ON DELETE CASCADE ON UPDATE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
)", "DBCreateAnswerTable(create table)");
         $r = DBExec($c, "REVOKE ALL ON \"answertable\" FROM PUBLIC", "DBCreateAnswerTable(revoke public)");
	 $r = DBExec($c, "GRANT ALL ON \"answertable\" TO \"".$conf["dbuser"]."\"", "DBCreateAnswerTable(grant bocauser)");
	 $r = DBExec($c, "CREATE UNIQUE INDEX \"answer_index\" ON \"answertable\" USING btree ".
	 	"(\"contestnumber\" int4_ops, \"answernumber\" int4_ops)", "DBCreateAnswerTable(create index)");
}

/////////////////////////////////funcoes de respostas a runs///////////////////////////////////////////
//recebe o numero do contest
//devolve um array, onde cada linha tem os atributos number (numero da resposta) e desc (descricao da resposta)
function DBGetAnswers($contest) {
        $c = DBConnect();
        $r = DBExec($c, "select distinct a.answernumber as number, a.runanswer as desc, a.yes as yes, a.fake as fake ".
	     "from answertable as a where a.contestnumber=$contest and a.runanswer !~ '(DEL)' order by a.answernumber", "DBGetAnswers(get answers)");
        $n = DBnlines($r);
        $a = array();
        for ($i=0;$i<$n;$i++)
                $a[$i] = DBRow($r,$i);
        return $a;
}

//recebe o numero do contest e o numero da resposta e remove-a caso seu tipo nao seja fake
function DBDeleteAnswer($contest,$param,$c=null) {
	$ac=array('number');
	foreach($ac as $key) {
		if(!isset($param[$key])) return false;
		$$key = sanitizeText($param[$key]);
	}

	$cw = false;
	if($c == null) {
		$cw = true;
		$c = DBConnect();
		DBExec($c, "begin work", "DBDeleteAnswer(transaction)");
	}
	$r = DBExec($c, "select * from answertable where contestnumber=$contest and answernumber=$number ".
				"and fake='f' for update", "DBDeleteAnswers(delete)");
	if(DBnlines($r)>0) {
		$a = DBRow($r,0);
		$r = DBExec($c, "update answertable set runanswer='".$a["runanswer"] ."(DEL)', updatetime=".time().
					" where contestnumber=$contest and answernumber=$number ".
					"and fake='f'", "DBDeleteAnswers(update)");
		$r = DBExec($c,"select runnumber as number, runsitenumber as site from runtable where contestnumber=$contest and runanswer=$number for update");
		$n = DBnlines($r);
		for ($i=0;$i<$n;$i++) {
			$a = DBRow($r,$i);
			DBRunDelete($a["number"],$a["site"],$contest,$_SESSION["usertable"]["usernumber"],$_SESSION["usertable"]["usersitenumber"]);
		}
	}
	if($cw) DBExec($c, "commit", "DBDeleteAnswer(commit)");
	LOGLevel("Answer $number deleted from contest $contest (user=".$_SESSION["usertable"]["username"]."/".$_SESSION["usertable"]["usersitenumber"].")", 2);
	return true;
}

//insere nova resposta (ou altera o conteudo se a mesma ja existir)
function DBNewAnswer($contest, $param, $c=null) {
	if(isset($param["action"]) && $param["action"]=="delete") {
		return DBDeleteAnswer($contestnumber, $param, $c);
	}

	$ac=array('number','name','yes');
	$type['number']=1;
	foreach($ac as $key) {
		if(!isset($param[$key])) {
			MSGError("DBNewAnswer param error: $key is not set");
			return false;
		}
		$$key = sanitizeText($param[$key]);
		if(isset($type[$key]) && !is_numeric($param[$key])) {
			MSGError("DBNewAnswer param error: $key is not numeric");
			return false;
		}
	}
	$t = time();
	$updatetime=$t;
	if(isset($param['updatetime']) && is_numeric($param["updatetime"])) $updatetime=$param["updatetime"];

	if($yes!="t") $y = "f";
	else $y = "t";

	$cw = false;
	if($c == null) {
		$cw = true;
		$c = DBConnect();
		DBExec($c, "begin work", "DBNewAnswer(transaction)");
	}
	$r = DBExec($c, "select * from answertable as a where a.contestnumber=$contest and a.answernumber=$number for update",
	       "DBNewAnswer(get answer)");
	$n = DBnlines($r);
	$ret=1;
	if ($n == 0) {
		$ret=2;
	      DBExec($c, "insert into answertable (contestnumber, answernumber, runanswer, yes, updatetime) values " .
			 "($contest, $number, '$name', '$y', $t)", "DBNewAnswer(insert answer)");
	      if($cw) DBExec($c, "commit work", "DBNewAnswer(commit)");
	      LOGLevel("Answer $number inserted (contest=$contest,user=".$_SESSION["usertable"]["username"]."/".$_SESSION["usertable"]["usersitenumber"].")", 2);
	} else {
		$lr = DBRow($r,0);
		if($updatetime > $lr['updatetime']) {
			$ret=2;
			DBExec($c, "update answertable set runanswer='$name', yes='$y', updatetime=". $updatetime . " where ".
				   "contestnumber=$contest and answernumber=$number and fake='f'", "DBNewAnswer(update answer)");
			if($cw) DBExec($c, "commit work", "DBNewAnswer(commit)");
			LOGLevel("Answer $number updated (contest=$contest,user=".$_SESSION["usertable"]["username"]."/".$_SESSION["usertable"]["usersitenumber"].")", 2);
		} else {
			if($cw) DBExec($c, "commit work", "DBNewAnswer(commit)");
		}
	}
	return $ret;
}
// eof
?>
