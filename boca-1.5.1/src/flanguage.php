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
function DBDropLangTable() {
	$c = DBConnect();
	$r = DBExec($c, "drop table \"langtable\"", "DBDropLangTable(drop table)");
}
function DBCreateLangTable() {
	$c = DBConnect();
	$conf = globalconf();
	if($conf["dbuser"]=="") $conf["dbuser"]="bocauser";
	$r = DBExec($c, "
CREATE TABLE \"langtable\" (
        \"contestnumber\" int4 NOT NULL,
        \"langnumber\" int4 NOT NULL,
        \"langname\" varchar(50) NOT NULL,
        \"langextension\" varchar(20) NOT NULL,
        \"updatetime\" int4 DEFAULT EXTRACT(EPOCH FROM now()) NOT NULL, -- (indica a ultima atualizacao no registro)
        CONSTRAINT \"lang_pkey\" PRIMARY KEY (\"contestnumber\", \"langnumber\"),
        CONSTRAINT \"contest_fk\" FOREIGN KEY (\"contestnumber\") REFERENCES \"contesttable\" (\"contestnumber\")
                ON DELETE CASCADE ON UPDATE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
)", "DBCreateLangTable(create table)");
	$r = DBExec($c, "REVOKE ALL ON \"langtable\" FROM PUBLIC", "DBCreateLangTable(revoke public)");
	$r = DBExec($c, "GRANT ALL ON \"langtable\" TO \"".$conf["dbuser"]."\"", "DBCreateLangTable(grant bocauser)");
	$r = DBExec($c, "CREATE INDEX \"lang_index\" ON \"langtable\" USING btree ".
				"(\"contestnumber\" int4_ops, \"langnumber\" int4_ops)", "DBCreateLangTable(create lang_index)");
	$r = DBExec($c, "CREATE INDEX \"lang_index2\" ON \"langtable\" USING btree ".
				"(\"contestnumber\" int4_ops, \"langname\" varchar_ops)", "DBCreateLangTable(create lang_index2)");
}

//recebe o numero do contest
//devolve um array, onde cada linha tem os atributos number (numero da linguagem) e name (nome da linguagem)
function DBGetLanguages($contest) {
	$c = DBConnect();
	$r = DBExec($c, "select distinct l.langnumber as number, l.langname as name, l.langextension as extension from langtable as l " .
				"where l.contestnumber=$contest and l.langname !~ '(DEL)' order by l.langnumber", "DBGetLanguages(get lang)");
	$n = DBnlines($r);
	$a = array();
	for ($i=0;$i<$n;$i++)
		$a[$i] = DBRow($r,$i);
	return $a;
}
//recebe o numero do contest e o numero da resposta e remove-a caso seu tipo nao seja fake

function DBDeleteLanguage($contestnumber, $param, $c=null) {
	$ac=array('number');
	foreach($ac as $key) {
		if(!isset($param[$key])) {
			MSGError("DBDeleteLanguage param error: $key not found");
			return false;
		}
		$$key = sanitizeText($param[$key]);
	}
	$cw = false;
	if($c == null) {
		$cw = true;
		$c = DBConnect();
		DBExec($c, "begin work", "DBDeleteLanguage(transaction)");
	}
	$sql = "select * from langtable where langnumber=$number and contestnumber=$contestnumber";

	$r = DBExec($c, $sql . " for update", "DBDeleteLanguage(get lang for update)");

	if(DBnlines($r)>0) {
		$a = DBRow($r,0);
		$r = DBExec($c, "update langtable set langname='".$a["langname"] ."(DEL)', updatetime=".time().
					" where contestnumber=$contestnumber and langnumber=$number ".
					"", "DBDeleteLanguage(update)");
		$r = DBExec($c,"select runnumber as number, runsitenumber as site from runtable where contestnumber=$contestnumber and runlangnumber=$number for update");
		$n = DBnlines($r);
		for ($i=0;$i<$n;$i++) {
			$a = DBRow($r,$i);
			DBRunDelete($a["number"],$a["site"],$contestnumber,$_SESSION["usertable"]["usernumber"],$_SESSION["usertable"]["usersitenumber"]);
		}
	}
	if($cw) DBExec($c, "commit", "DBDeleteLanguage(commit)");
	LOGLevel("Language $number deleted (user=".$_SESSION["usertable"]["username"].
			 "/".$_SESSION["usertable"]["usersitenumber"].")", 2);
	return true;
}
function DBNewLanguage($contestnumber, $param, $c=null) {
	if(isset($param["action"]) && $param["action"]=="delete") {
		return DBDeleteLanguage($contestnumber, $param, $c);
	}
	$ac=array('number','name');
	$ac1=array('updatetime','extension');
	$type['number']=1;
	$type['updatetime']=1;
	$extension='';
	foreach($ac as $key) {
		if(!isset($param[$key]) || $param[$key]=="") {
			MSGError("DBNewLanguage param error: $key not found");
			return false;
		}
		if(isset($type[$key]) && !is_numeric($param[$key])) {
			MSGError("DBNewLanguage param error: $key is not numeric");
			return false;
		}
		$$key = sanitizeText($param[$key]);
	}
	$updatetime=-1;
	foreach($ac1 as $key) {
		if(isset($param[$key])) {
			$$key = sanitizeText($param[$key]);
			if(isset($type[$key]) && !is_numeric($param[$key])) {
				MSGError("DBNewLanguage param error: $key is not numeric");
				return false;
			}
		}
	}
	$t = time();
	if($updatetime <= 0)
		$updatetime=$t;

	$cw = false;
	if($c == null) {
		$cw = true;
		$c = DBConnect();
		DBExec($c, "begin work", "DBNewLanguage(transaction)");
	}

	$sql2 = "select * from langtable where contestnumber=$contestnumber and langnumber=$number";

	$r = DBExec ($c, $sql2 . " for update", "DBNewLanguage(get lang)");
	$n = DBnlines($r);
	$ret=1;
	if ($n == 0) {
		DBExec ($c, "insert into langtable (contestnumber,langnumber, langname,langextension) values ".
				"($contestnumber, $number, '$name','$extension')", "DBNewLanguage(insert lang)");
		$s = "created";
	}
	else {
		$lr = DBRow($r,0);
		$t = $lr['updatetime'];
		if($updatetime > $t) {
			if ($name != "")
				DBExec ($c, "update langtable set langname='$name', updatetime=$updatetime where contestnumber=$contestnumber ".
						"and langnumber=$number", "DBNewLanguage(update lang)");
			if ($extension != "")
				DBExec ($c, "update langtable set langextension='$extension', updatetime=$updatetime where contestnumber=$contestnumber ".
						"and langnumber=$number", "DBNewLanguage(update lang)");
		}
		$s = "updated";
	}
	if($cw)
		DBExec($c, "commit work", "DBNewLanguage(commit)");
	if($s=="created" || $updatetime > $t) {
		LOGLevel ("Language $number updated (user=".$_SESSION["usertable"]["usernumber"].
				  ",site=".$_SESSION["usertable"]["usersitenumber"].",contest=$contestnumber)", 2);
		$ret=2;
	}
	return $ret;
}
// eof
?>
