<?php
////////////////////////////////////////////////////////////////////////////////
//BOCA Online Contest Administrator
//    Copyright (C) 2003-2013 by BOCA Development Team (bocasystem@gmail.com)
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
// Last modified 06/sep/2013 by cassio@ime.usp.br

function DBDropClarTable() {
	 $c = DBConnect();
	 $r = DBExec($c, "drop table clartable", "DBDropClarTable(drop table)");
}
function DBCreateClarTable() {
	 $c = DBConnect();
	 $conf = globalconf();
	 if($conf["dbuser"]=="") $conf["dbuser"]="bocauser";
	 $r = DBExec($c, "
CREATE TABLE clartable (
contestnumber int4 NOT NULL, 		-- (id do concurso)
clarsitenumber int4 NOT NULL,		-- (local de origem da pergunta)
clarnumber int4 NOT NULL,		-- (id da pergunta)
usernumber int4 NOT NULL,		-- (id do usuario)
clardate int4 NOT NULL,		-- (dia/hora da pergunta no local de origem)
clardatediff int4 NOT NULL,		-- (diferenca entre inicio da competicao e dia/hora da pergunta em seg)
clardatediffans int4 NOT NULL,	-- (diferenca entre inicio da competicao e dia/hora da resposta em seg)
clarproblem int4 NOT NULL,		-- (id do problema)
clardata text NOT NULL,		-- (texto da pergunta)
claranswer text,			-- (resposta dada aa pergunta)
clarstatus varchar(20) NOT NULL,	-- (status da submissao: openclar, answering, answered, 
					--  answeredsite, answeredall, deleted)
clarjudge int4 DEFAULT NULL,		-- (juiz que esta julgando)
clarjudgesite int4 DEFAULT NULL,	-- (juiz que esta julgando)
updatetime int4 DEFAULT EXTRACT(EPOCH FROM now()) NOT NULL, -- (indica a ultima atualizacao no registro)
CONSTRAINT clar_pkey PRIMARY KEY (contestnumber, clarsitenumber, clarnumber),
CONSTRAINT user_fk FOREIGN KEY (contestnumber, clarsitenumber, usernumber)
	REFERENCES usertable (contestnumber, usersitenumber, usernumber)
	ON DELETE CASCADE ON UPDATE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE,
CONSTRAINT problem_fk FOREIGN KEY (contestnumber, clarproblem)
	REFERENCES problemtable (contestnumber, problemnumber)
	ON DELETE CASCADE ON UPDATE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
)", "DBCreateClarTable(create table)");
         $r = DBExec($c, "REVOKE ALL ON clartable FROM PUBLIC", "DBCreateClarTable(revoke public)");
         $r = DBExec($c, "GRANT ALL ON clartable TO \"".$conf["dbuser"]."\"", "DBCreateClarTable(grant bocauser)");
         $r = DBExec($c, "CREATE UNIQUE INDEX clar_index ON clartable USING btree ".
	       "(contestnumber int4_ops, clarsitenumber int4_ops, clarnumber int4_ops)", 
	       "DBCreateClarTable(create clar_index)");
         $r = DBExec($c, "CREATE INDEX clar_index2 ON clartable USING btree ".
	       "(contestnumber int4_ops, clarsitenumber int4_ops, usernumber int4_ops)",
	       "DBCreateClarTable(create clar_index2)");
}

//////////////////////////////funcoes de clarifications/////////////////////////////////////////
//recebe o numero do contest, o numero do site e o numero do usuario
//devolve um array, onde cada linha tem os atributos
//  number (numero da clarification)
//  timestamp (hora da criacao da clar)
//  problem (nome do problema)
//  status (situacao da clarification)
//  question (texto da clar)
//  answer (texto com a resposta)
function DBUserClars($contest,$site,$user) {
	$b = DBSiteInfo($contest, $site);
	if ($b == null) {
		exit;
	}

	$t = $b["currenttime"];

	$c = DBConnect();
	$r = DBExec($c, "select distinct c.clarsitenumber as site, c.clarnumber as number, c.clardatediff as timestamp, " .
                                        "p.problemname as problem, c.clarstatus as status, c.clardata as question, " .
                                        "c.claranswer as answer, c.updatetime as updatetime, c.clardatediffans as anstime " .
                             "from clartable as c, problemtable as p " .
                             "where c.contestnumber=$contest and p.contestnumber=c.contestnumber and " .
                                    "c.clarproblem=p.problemnumber and not clarstatus ~ 'deleted' and " .
                                    "(c.clardatediffans<=$t or (c.clarstatus !~ 'answered' and c.clardatediff<=$t)) and " .
	                            "((clarsitenumber=$site and c.usernumber=$user) or (clarstatus ~ 'answeredall') or ".
				     " (clarstatus ~ 'answeredsite' and clarsitenumber=$site)) order by c.updatetime desc",
                        "DBUserClars(get clars)");
	$n = DBnlines($r);
	$a = array();
	for ($i=0;$i<$n;$i++)
		$a[$i] = DBRow($r,$i);
	return $a;
}
//recebe o numero do contest, o numero do site do juiz e o numero do juiz
//devolve um array, onde cada linha tem os atributos
//  number (numero da clarification)
//  timestamp (hora da criacao da clar)
//  problem (nome do problema)
//  status (situacao da clarification)
//  question (texto da clar)
//  answer (texto com a resposta)
function DBJudgedClars($contest,$site,$user) {
	$c = DBConnect();
	$r = DBExec($c, "select distinct c.clarsitenumber as site, c.clarnumber as number, c.clardatediff as timestamp, " .
                                        "p.problemname as problem, c.clarstatus as status, c.clardata as question, " .
                                        "c.claranswer as answer, c.updatetime " .
                             "from clartable as c, problemtable as p " .
                             "where c.contestnumber=$contest and p.contestnumber=c.contestnumber and " .
                                    "c.clarproblem=p.problemnumber and c.clarjudge=$user and " .
                                    "c.clarjudgesite=$site order by c.updatetime", "DBJudgesClars(get clars)");
	$n = DBnlines($r);
	$a = array();
	for ($i=0;$i<$n;$i++)
		$a[$i] = DBRow($r,$i);
	return $a;
}
//responde uma clarification. Recebe o numero do contest, site do usuario, num do usuario, site da clar,
//numero da clar e a resposta.
//tenta alterar o status para 'answered...', dependendo do type.
function DBUpdateClar($contest, $usersite, $usernumber, $clarsite, $clarnumber, $answer, $type) {
	return DBUpdateClarC($contest, $usersite, $usernumber, $clarsite, $clarnumber, $answer, $type, 0);
}
function DBChiefUpdateClar($contest, $usersite, $usernumber, $clarsite, $clarnumber, $answer, $type) {
	return DBUpdateClarC($contest, $usersite, $usernumber, $clarsite, $clarnumber, $answer, $type, 1);
}
function DBUpdateClarC($contest, $usersite, $usernumber, $clarsite, $clarnumber, $answer, $type, $chief) {
	if(($b = DBSiteInfo($contest, $clarsite)) == null)
		exit;
	
	$c = DBConnect();
	DBExec($c, "begin work", "DBUpdateClarC(transaction)");
	$sql = "select * from clartable as c where c.contestnumber=$contest and " .
		"c.clarsitenumber=$clarsite and c.clarnumber=$clarnumber"; 
	$tx = "Chief";
	if ($chief != 1) {
		$sql .=	" and c.clarstatus='answering' and " .
			"c.clarjudge=$usernumber and clarjudgesite=$usersite for update";
		$tx = "Judge";
	}
	$r = DBExec($c, $sql);
	$n = DBnlines($r);
	if ($n != 1) {
		DBExec($c, "rollback work", "DBUpdateClarC(rollback)");
		LogLevel("Unable to answer a clar (maybe it was already answered or catched by a chief) " .
			"(clar=$clarnumber, site=$clarsite, contest=$contest).",0);
		MSGError("Unable to answer the clarification (maybe it was already answered or catched by a chief)");
		return false;
	}

	if ($type=="all") $status="answeredall";
	else if ($type=="site") $status="answeredsite";
	else $status="answered";

	$time = time();
	$t = $b["currenttime"];

	DBExec($c, "update clartable set clarstatus='$status', clarjudge=$usernumber, clarjudgesite=$usersite, " . 
		"claranswer='$answer', clardatediffans=$t, updatetime=".time()." " .
		"where contestnumber=$contest and clarnumber=$clarnumber and clarsitenumber=$clarsite",
               "DBUpdateClarC(update clar)");

	DBExec($c, "commit work", "DBUpdateClarC(commit)");
	LOGLevel("Clarification updated (clar=$clarnumber, site=$clarsite, contest=$contest, status=$status, " .
		"user=$usernumber(site=$usersite)).", 3);
	return true;
}
//devolve uma clarification que estava sendo respondida. Recebe o numero da clar, o numero do site e o numero do contest.
//tenta alterar o status para 'openclar'. Se nao conseguir retorna false
function DBChiefClarGiveUp($number,$site,$contest) {
	return DBClarGiveUp($number,$site,$contest, -1, -1);
}
function DBClarGiveUp($number,$site,$contest, $usernumber, $usersite) {
	$c = DBConnect();
	DBExec($c, "begin work", "DBClarGiveUp(transaction)");
	$sql = "select * from clartable as c where c.contestnumber=$contest and " .
		"c.clarsitenumber=$site and c.clarnumber=$number";
	$tx = "Chief";
	if ($usernumber != -1 && $usersite != -1) {
		$sql .= " and c.clarstatus='answering' and c.clarjudge=$usernumber and clarjudgesite=$usersite";
		$tx = "Judge";
	}
	$r = DBExec($c, $sql . " for update", "DBClarGiveUp(get clar for update)");
	$n = DBnlines($r);
	if ($n != 1) {
		DBExec($c, "rollback work", "DBClarGiveUp(rollback)");
		LogLevel("Unable to return a clar (maybe the timeout or a chief returned it first). " .
			"(clar=$number, site=$site, contest=$contest)",1);
		return false;
	}

	DBExec($c, "update clartable set clarstatus='openclar', claranswer='', clarjudge=NULL, " .
		   "clarjudgesite=NULL, updatetime=".time()." " .
		"where contestnumber=$contest and clarnumber=$number and clarsitenumber=$site",
               "DBClarGiveUp(update clar)");

	DBExec($c, "commit work", "DBClarGiveUp(commit)");
	LOGLevel("Clarification returned (clar=$number, site=$site, contest=$contest, user=$usernumber(site=$usersite)).",3);
	return true;
}
//seta o status como 'deleted' de uma clarification que estava sendo respondida. Recebe o numero da clar, 
//o numero do site e o numero do contest. Se nao conseguir retorna false.
function DBClarDelete($number,$site,$contest,$user,$usersite) {
	$c = DBConnect();
	DBExec($c, "begin work", "DBClarDelete(transaction)");
	$r = DBExec($c, "select * from clartable as c where c.contestnumber=$contest and " .
			 "c.clarsitenumber=$site and c.clarnumber=$number for update", "DBClarDelete(get clar for update)");
	$n = DBnlines($r);
	if ($n != 1) {
		DBExec($c, "rollback work", "DBClarDelete(rollback)");
		LogLevel("Unable to delete a clar. " .
			"(clar=$number, site=$site, contest=$contest)",1);
		return false;
	}

	DBExec($c, "update clartable set clarstatus='deleted', clarjudge=$user, clarjudgesite=$usersite, updatetime=" .
		   time(). " where contestnumber=$contest and clarnumber=$number and clarsitenumber=$site",
               "DBClarDelete(update clar)");

	DBExec($c, "commit work", "DBClarDelete(commit)");
	LOGLevel("Clarification deleted (clar=$number, site=$site, contest=$contest, user=$user(site=$usersite)).", 3);
	return true;
}
function DBNewClar($param,$c=null) {
	if(isset($param['contestnumber']) && !isset($param['contest'])) $param['contest']=$param['contestnumber'];
	if(isset($param['sitenumber']) && !isset($param['site'])) $param['site']=$param['sitenumber'];
	if(isset($param['usernumber']) && !isset($param['user'])) $param['user']=$param['usernumber'];
	if(isset($param['number']) && !isset($param['clarnumber'])) $param['clarnumber']=$param['number'];

	$ac=array('contest','site','user','problem','question');
	$ac1=array('clarnumber','clardate','clardatediff','clardatediffans','claranswer','clarstatus','clarjudge','clarjudgesite','updatetime');
	$type['contest']=1;
	$type['problem']=1;
	$type['updatetime']=1;
	$type['site']=1;
	$type['user']=1;
	$type['clarnumber']=1;
	$type['clardatediffans']=1;
	$type['clardatediff']=1;
	$type['clardate']=1;
	$type['clarjudge']=1;
	$type['clarjudgesite']=1;
	foreach($ac as $key) {
		if(!isset($param[$key]) || $param[$key]=="") {
			MSGError("DBNewClar param error: $key not found");
			return false;
		}
		if(isset($type[$key]) && !is_numeric($param[$key])) {
			MSGError("DBNewClar param error: $key is not numeric");
			return false;
		}
		$$key = sanitizeText($param[$key]);
	}
	$t = time();
	$clarnumber=-1;
	$updatetime = -1;
	$clardatediff = -1;
	$clardate = $t;
	$claranswer='';
	$clardatediffans = 999999999;
	$clarjudge='NULL';
	$clarjudgesite='NULL';
	$clarstatus='openclar';
	foreach($ac1 as $key) {
		if(isset($param[$key])) {
			$$key = sanitizeText($param[$key]);
			if(isset($type[$key]) && !is_numeric($param[$key])) {
				MSGError("DBNewClar param error: $key is not numeric");
				return false;
			}
		}
	}
	if($updatetime < 0)
		$updatetime=$t;

	$cw = false;
	if($c == null) {
		$cw = true;
		$c = DBConnect();
		DBExec($c, "begin work", "DBNewClar(transaction)");
	}
	$insert=true;
	if($clarnumber < 0) {
		$sql = "select sitenextclar as nextclar from sitetable where sitenumber=$site and contestnumber=$contest for update";
		$r = DBExec($c, $sql, "DBNewClar(get site for update)");
		if (DBnlines($r) != 1) {
			DBExec($c, "rollback work", "DBNewClar(rollback-site)");
			LOGError("Unable to find a unique site/contest in the database. SQL=(" . $sql . ")");
			MSGError("Unable to find a unique site/contest in the database. Contact an admin now!");
			exit;
		}
		$a = DBRow($r,0);
		$n = $a["nextclar"] + 1;
		$clarnumber = $n;
	} else {
		$sql = "select * from clartable as t where t.contestnumber=$contest and " .
			"t.clarsitenumber=$site and t.clarnumber=$clarnumber";
		$r = DBExec ($c, $sql . " for update", "DBNewClar(get clar for update)");
		$n = DBnlines($r);
		if ($n > 0) {
			$insert=false;
			$lr = DBRow($r,0);
			$t = $lr['updatetime'];
		}
		$n = $clarnumber;
	}
	DBExec($c, "update sitetable set sitenextclar=$clarnumber, updatetime=" . $t . 
		   " where sitenumber=$site and contestnumber=$contest and sitenextclar<$clarnumber",
		   "DBNewClar(update site)");

	if($clardatediff < 0) {
		$b = DBSiteInfo($contest, $site, $c);
		$dif = $b["currenttime"];
		$clardatediff = $dif;
		if ($dif < 0) {
			DBExec($c, "rollback work", "DBNewClar(rollback-started)");
			LOGError("Tried to submit a clarification but the contest is not started. SQL=(" . $sql . ")");
			MSGError("The contest is not started yet!");
			return false;
		}
		if (!$b["siterunning"]) {
			DBExec($c, "rollback work", "DBNewClar(rollback-over)");
			LOGError("Tried to submit a clarification but the contest is over. SQL=(" . $sql . ")");
			MSGError("The contest is over!");
			return false;
		}
	} else {
		$dif = $clardatediff;
	}

	$ret=1;
	if($insert) {
		DBExec($c, "INSERT INTO clartable (contestnumber, clarsitenumber, clarnumber, usernumber, clardate, " .
			   "clardatediff, clardatediffans, clarproblem, clardata, claranswer, clarjudge, clarjudgesite, clarstatus, updatetime) VALUES " .
			   "($contest, $site, $n, $user, $clardate, $clardatediff, $clardatediffans, $problem, '$question', " .
			   "'$claranswer', $clarjudge, $clarjudgesite, '$clarstatus', $updatetime)",
               "DBNewClar(insert clar)");
		if($cw) DBExec($c, "commit work", "DBNewClar(commit-insert)");
		LOGLevel("User $user submitted a clarification (#$n) on site #$site " .
				 "(problem=$problem, contest=$contest).",2);
		$ret=2;
	} else {
		if($updatetime > $t) {
			$ret=2;
			DBExec($c, "update clartable set clardate=$clardate, clardatediff=$clardatediff, " .
				   "clardatediffans=$clardatediffans, claranswer='$claranswer', clarstatus='$clarstatus', ".
				   "clarjudge=$clarjudge, clarjudgesite=$clarjudgesite, updatetime=$updatetime, clardata='$question', clarproblem=$problem ".
				   "where clarnumber=$clarnumber and contestnumber=$contest and clarsitenumber=$site", "DBNewClar(update clar)");
		}
		if($cw) DBExec($c, "commit work", "DBNewClar(commit-update)");
	}
	return $ret;
/* // isso gera problemas de portabilidade e de seguranca se os demais usuarios tiverem shell no servidor
   // por outro lado, garante que as coisas estao guardadas em arquivos fora do banco, caso haja outros problemas.
	umask(0077);
	@mkdir("/tmp/boca");
        $fp = fopen("/tmp/boca/contest${contest}.site${site}.clar${n}.user${user}.problem${problem}.time${t}", "w");
	if ($fp) {
		fwrite($fp, $question);
		fclose($fp);
	} else
		 LOGLevel("Clarification not saved as file (clar=$n,site=$site,contest=$contest)", 1);
*/
}
//pega uma clarification para responder. Recebe o numero da clar, o numero do site e o numero do contest.
//tenta alterar o status para 'answering' e se conseguir, devolve um array com dados da clar. Se nao conseguir,
//retorna false. Se for chief, nao ha restricoes com relacao a outro juiz julgando. Isso deve ser passado na
//variavel $chief
//Retorna no array: contestnumber, sitenumber, number, timestamp (em segundos), problemname, 
//			problemnumber, question, status
function DBChiefGetClarToAnswer($number,$site,$contest) {
	return DBGetClarToAnswerC($number,$site,$contest,1);
}
function DBGetClarToAnswer($number,$site,$contest) {
	return DBGetClarToAnswerC($number,$site,$contest,0);
}
function DBGetClarToAnswerC($number,$site,$contest,$chief) {
	$c = DBConnect();
	DBExec($c, "begin work", "DBGetClarToAnswerC(transaction)");
	$sql =  "select c.contestnumber as contestnumber, c.clarsitenumber as sitenumber, c.claranswer as answer, " .
                   "c.clarnumber as number, c.clardatediff as timestamp, c.clarstatus as status, " .
                   "p.problemname as problemname, p.problemnumber as problemnumber, c.clardata as question " .
                      "from clartable as c, problemtable as p " .
                      "where c.contestnumber=$contest and p.contestnumber=c.contestnumber and " .
                        "c.clarproblem=p.problemnumber and c.clarsitenumber=$site and " .
	                "c.clarnumber=$number";
	if ($chief != 1) { 
		$sql .= " and (c.clarstatus='openclar' or (c.clarstatus='answering' and " .
			    "c.clarjudge=".$_SESSION["usertable"]["usernumber"]." and " .
			    "c.clarjudgesite=".$_SESSION["usertable"]["usersitenumber"]."))";
		$tx = "Chief is answering";
	}
	else $tx = "Judge is answering";

	$r = DBExec($c, $sql . " for update", "DBGetClarToAnswerC(get clar/prob for update)");
	$n = DBnlines($r);
	if ($n != 1) {
		DBExec($c, "rollback work", "DBGetClarToAnswerC(rollback)");
		LogLevel("Unable to get a clar (maybe other judge got it first). (clar=$number, site=$site, ".
			"contest=$contest)",2);
		return false;
	}
	$a = DBRow($r,0);
	if ($a["status"] == "answering") $st = "DNA";
	else $st = "started";

	if ($chief != 1) {
		DBExec($c, "update clartable set clarjudge=" . $_SESSION["usertable"]["usernumber"] . 
			", clarstatus='answering', updatetime=".time().", " .
			"clarjudgesite=" . $_SESSION["usertable"]["usersitenumber"] . " " .
			"where contestnumber=$contest and clarnumber=$number and clarsitenumber=$site",
                       "DBGetClarToAnswerC(update clar)");
	}

	DBExec($c, "commit work", "DBGetClarToAnswerC(commit)");
	LOGLevel("User got a clarification (clar=$number, site=$site, contest=$contest, status=$st, " .
		"user=".$_SESSION["usertable"]["usernumber"]."(site=".$_SESSION["usertable"]["usersitenumber"].")).", 3);
	return $a;
}
//recebe o numero do contest, o numero do site (ou -1 para todos)
//devolve um array, onde cada linha tem os atributos
//  number (numero da clarification)
//  site (numero do site onde a clarification foi criada)
//  timestamp (hora da criacao da clar)
//  problem (nome do problema)
//  status (situacao da clarification)
//(devolve apenas aquelas que a situacao eh <> de answered.*)
//  question (texto da clar)
function DBAllClars($contest) {
	return DBOpenClarsSNS($contest,"x",-1);
}
function DBAllClarsInSites($contest,$site,$order) {
	return DBOpenClarsSNS($contest,$site,-1,$order);
}
//function DBOpenClars($contest) {
//	return DBOpenClarsSNS($contest,"x",1);
//}
function DBOpenClarsInSites($contest,$site) {
	return DBOpenClarsSNS($contest,$site,1);
}
function DBOpenClarsSNS($contest,$site,$st,$order='clar') {
	$c = DBConnect();
	$sql = "select distinct c.clarnumber as number, c.clardatediff as timestamp, c.claranswer as answer, " .
                                "p.problemname as problem, c.clarstatus as status, c.clardata as question, " .
				"c.clarsitenumber as site, c.clarjudge as judge, c.clarjudgesite as judgesite, " .
				"c.usernumber as user " .
                             "from clartable as c, problemtable as p " .
                             "where c.contestnumber=$contest and p.contestnumber=c.contestnumber and " .
                                    "c.clarproblem=p.problemnumber";
	if ($site != "x") {
		$str = explode(",", $site);
		$sql .= " and (c.clarstatus='answeredall'";
		for ($i=0;$i<count($str);$i++) {
			if (is_numeric($str[$i])) $sql .= " or c.clarsitenumber=".$str[$i];
		}
		$sql .= ")";
	}

	if ($st == 1) 
		$sql .= " and not c.clarstatus ~ 'answered' and not c.clarstatus ~ 'deleted' order by ";
	else $sql .= " order by "; 

        if($order == "site")
                $sql .= "c.clarsitenumber,";
        else if ($order == "status")
                $sql .= "c.clarstatus,";
        else if ($order == "judge")
                $sql .= "c.clarjudge,c.clarjudgesite,";
        else if ($order == "problem")
                $sql .= "p.problemname,";
        else if ($order == "user")
                $sql .= "c.usernumber,c.clarsitenumber,";

        if ($st == 1 || $order == "report")
                $sql .= "c.clarnumber";
        else
                $sql .= "c.clardatediff desc";

	$r = DBExec($c, $sql, "DBOpenClarsSNS(get clar/prob)");

	$n = DBnlines($r);
	$a = array();
	for ($i=0;$i<$n;$i++)
		$a[$i] = DBRow($r,$i);
	return $a;
}
// eof
?>
