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

function DBDropRunTable() {
	$c = DBConnect();
	$r = DBExec($c, "drop table \"runtable\"", "DBDropRunTable(drop table)");
}
function DBCreateRunTable() {
	$c = DBConnect();
	$conf = globalconf();
	if($conf["dbuser"]=="") $conf["dbuser"]="bocauser";
	$r = DBExec($c, "
CREATE TABLE \"runtable\" (
\"contestnumber\" int4 NOT NULL,	-- (id do concurso)
\"runsitenumber\" int4 NOT NULL,	-- (local de origem da submissao)
\"runnumber\" int4 NOT NULL,		-- (numero da submissao)
\"usernumber\" int4 NOT NULL,		-- (numero do time)
\"rundate\" int4 NOT NULL,		-- (dia/hora da submissao no local de origem)
\"rundatediff\" int4 NOT NULL,		-- (diferenca entre inicio da competicao e dia/hora da submissao em seg)
\"rundatediffans\" int4 NOT NULL,	-- (diferenca entre inicio da competicao e dia/hora da correcao em seg)
\"runproblem\" int4 NOT NULL,		-- (id do problema)
\"runfilename\" varchar(200) NOT NULL,	-- (nome do arquivo submetido)
\"rundata\" oid NOT NULL,		-- (codigo fonte do arquivo submetido)
\"runanswer\" int4 DEFAULT 0 NOT NULL,	-- (resposta dada no julgamento)
\"runstatus\" varchar(20) NOT NULL,	-- (status da submissao: openrun, judging, judged, deleted, judged+)
\"runjudge\" int4 DEFAULT NULL,		-- (juiz que esta julgando)
\"runjudgesite\" int4 DEFAULT NULL,	-- (juiz que esta julgando)
\"runanswer1\" int4 DEFAULT 0 NOT NULL, -- (resposta dada no julgamento)                                                    
\"runjudge1\" int4 DEFAULT NULL,                -- (juiz que esta julgando)                                                 
\"runjudgesite1\" int4 DEFAULT NULL,    -- (juiz que esta julgando)                                                         
\"runanswer2\" int4 DEFAULT 0 NOT NULL, -- (resposta dada no julgamento)                                                    
\"runjudge2\" int4 DEFAULT NULL,                -- (juiz que esta julgando)                                                 
\"runjudgesite2\" int4 DEFAULT NULL,    -- (juiz que esta julgando)                                                         
\"runlangnumber\" int4 NOT NULL,	-- (linguagem do codigo fonte)

\"autoip\" varchar(20) DEFAULT '',  -- os campos auto... sao para a correcao automatica
\"autobegindate\" int4 DEFAULT NULL,
\"autoenddate\" int4 DEFAULT NULL,
\"autoanswer\" text DEFAULT '',
\"autostdout\" oid DEFAULT NULL,
\"autostderr\" oid DEFAULT NULL,

\"updatetime\" int4 DEFAULT EXTRACT(EPOCH FROM now()) NOT NULL, -- (indica a ultima atualizacao no registro)
CONSTRAINT \"run_pkey\" PRIMARY KEY (\"contestnumber\", \"runsitenumber\", \"runnumber\"),
CONSTRAINT \"user_fk\" FOREIGN KEY (\"contestnumber\", \"runsitenumber\", \"usernumber\")
	REFERENCES \"usertable\" (\"contestnumber\", \"usersitenumber\", \"usernumber\")
	ON DELETE CASCADE ON UPDATE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE,
CONSTRAINT \"problem_fk\" FOREIGN KEY (\"contestnumber\", \"runproblem\")
	REFERENCES \"problemtable\" (\"contestnumber\", \"problemnumber\")
	ON DELETE CASCADE ON UPDATE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE,
CONSTRAINT \"answer_fk\" FOREIGN KEY (\"contestnumber\", \"runanswer\")
	REFERENCES \"answertable\" (\"contestnumber\", \"answernumber\")
	ON DELETE CASCADE ON UPDATE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE,
CONSTRAINT \"lang_fk\" FOREIGN KEY (\"contestnumber\", \"runlangnumber\")
	REFERENCES \"langtable\" (\"contestnumber\", \"langnumber\")
	ON DELETE CASCADE ON UPDATE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
)", "DBCreateRunTable(create table)");
	$r = DBExec($c, "REVOKE ALL ON \"runtable\" FROM PUBLIC", "DBCreateRunTable(revoke public)");
	$r = DBExec($c, "GRANT ALL ON \"runtable\" TO \"".$conf["dbuser"]."\"", "DBCreateRunTable(grant bocauser)");
	$r = DBExec($c, "CREATE UNIQUE INDEX \"run_index\" ON \"runtable\" USING btree ". 
				"(\"contestnumber\" int4_ops, \"runsitenumber\" int4_ops, \"runnumber\" int4_ops)", 
				"DBCreateRunTable(create run_index)");
	$r = DBExec($c, "CREATE INDEX \"run_index2\" ON \"runtable\" USING btree ". 
				"(\"contestnumber\" int4_ops, \"runsitenumber\" int4_ops, \"usernumber\" int4_ops)",
				"DBCreateRunTable(create run_index2)");
}

///////////////////////////////funcoes de runs///////////////////////////////////////////////////////
//responde uma run. Recebe o numero do contest, site do usuario, num do usuario, site da run,
//numero da run, numero da resposta, (notifyuser e updatescore).
//tenta alterar o status para 'judged'.
function DBChiefUpdateRun($contest, $usersite, $usernumber, $runsite, $runnumber, $answer) {
	return DBUpdateRunC($contest, $usersite, $usernumber, $runsite, $runnumber, $answer, 1);
}
function DBUpdateRunO($contest, $usersite, $usernumber, $runsite, $runnumber, $answer, $c) {
	return DBUpdateRunC($contest, $usersite, $usernumber, $runsite, $runnumber, $answer, 1, $c);
}
function DBUpdateRun($contest, $usersite, $usernumber, $runsite, $runnumber, $answer) {
	return DBUpdateRunC($contest, $usersite, $usernumber, $runsite, $runnumber, $answer, 0);
}
function DBUpdateRunC($contest, $usersite, $usernumber, $runsite, $runnumber, $answer, $chief, $c=null) {
	$bw = 0;
	if($c==null) {
		$bw = 1;
		$c = DBConnect();
		DBExec($c, "begin work", "DBUpdateRunC(transaction)");
	}

	$a = DBGetRow("select * from answertable where answernumber=$answer and contestnumber=$contest",0,$c,
				  "DBUpdateRunC(get answer)");
	if ($a == null) {
		DBExec($c, "rollback work", "DBUpdateRunC(rollback)");
		MSGError("Problem with the answer table. Contact an admin now!");
		LogLevel("Unable to judge a run because the answer was not found (run=$runnumber, site=$runsite, ".
				 "contest=$contest, answer=$answer).",0);
		return false;
	}
	if ($a["fake"] == 't') {
		DBExec($c, "rollback work", "DBUpdateRunC(rollback)");
		MSGError("You must choose a valid answer.");
		LogLevel("Unable to judge a run because of the fake answer (run=$runnumber, site=$runsite, ".
				 "contest=$contest, answer=$answer).",0);
		return false;
	}
	$yes = $a["yes"];
	$b = DBSiteInfo($contest, $runsite, $c);
	if ($b == null) {
		exit;
	}

	$sql = "select * from runtable as r where r.contestnumber=$contest and " .
		"r.runsitenumber=$runsite and r.runnumber=$runnumber";
	if ($chief != 1) {
		$sql .= " and (r.runstatus='judging' or r.runstatus='judged+') and " .      
			"((r.runjudge1=$usernumber and r.runjudgesite1=$usersite) or " . 
			" (r.runjudge2=$usernumber and r.runjudgesite2=$usersite))";
		$tx = "Judge";
	} else $tx = "Chief";
	$r = DBExec ($c, $sql . " for update", "DBUpdateRunC(get run for update)");
	$n = DBnlines($r);
	if ($n != 1) {
		if($bw == 1) {
			DBExec($c, "rollback work", "DBUpdateRunC(rollback)");
			LogLevel("Unable to judge a run (maybe it was already judged or catched by a chief) " .
					 "(run=$runnumber, site=$runsite, contest=$contest).",2);
			MSGError("Unable to judge the run (maybe it was already judged or catched by a chief)");
		}
		return false;
	}
	$temp = DBRow($r,0);
	$t = $b["currenttime"]; 

	$team=$temp["usernumber"];
	if ($temp["runanswer"] != "")
		$tinhabalao = DBBalloon($contest, $runsite, $temp["usernumber"], $temp["runproblem"], ($bw==1),$c);
	else	$tinhabalao = false;

	if($temp["runjudge1"]==$usernumber && $temp["runjudgesite1"]==$usersite) {
		DBExec($c, "update runtable set runanswer1=$answer, updatetime=".time()." " .
			   "where contestnumber=$contest and runnumber=$runnumber and runsitenumber=$runsite",
               "DBUpdateRunC(update run judge1)");
		$outra = $temp["runanswer2"];
	}
	if($temp["runjudge2"]==$usernumber && $temp["runjudgesite2"]==$usersite) {
		DBExec($c, "update runtable set runanswer2=$answer, updatetime=".time()." " .
			   "where contestnumber=$contest and runnumber=$runnumber and runsitenumber=$runsite",
               "DBUpdateRunC(update run judge2)");
		$outra = $temp["runanswer1"];
	}
	$newstatus = 'judging';
	if($chief == 1 || ($outra != 0 && $outra == $answer && $temp["runstatus"] != "judged+") || 
	   ($outra != 0 && $outra == $answer && $temp["runanswer"]==$answer)) {
		$newstatus = 'judged';
		DBExec($c, "update runtable set runstatus='judged', " .
			   "runjudge=$usernumber, runjudgesite=$usersite, " . 
			   "runanswer=$answer, rundatediffans=$t, updatetime=".time()." " .
			   "where contestnumber=$contest and runnumber=$runnumber and runsitenumber=$runsite",
			   "DBUpdateRunC(update run)");

		$tembalao = DBBalloon($contest, $runsite, $temp["usernumber"], $temp["runproblem"], ($bw==1),$c);
		  
//	if ($runsite==$usersite) {
		if (!$tinhabalao && $tembalao) {
			if (($b = DBSiteInfo($contest, $runsite, $c)) == null)
				return true;
			$ta = $b["currenttime"]; 
			$tf = $b["sitelastmileanswer"];
			if ($ta < $tf || $ta > $b['siteduration']) {
				$u = DBUserInfo ($contest, $runsite, $team, $c);
				if($u['usertype']=='team') {
					$p = DBGetProblemData ($contest, $temp["runproblem"],$c);
					DBNewTask_old ($contest, $runsite, $team, 
								   escape_string("\"" . $u["username"] ."\" must have a balloon for problem " . 
												 $p[0]["problemname"] . ": " . $p[0]["fullname"]), 
								   "", "", "t", $p[0]["color"], $p[0]["colorname"], $c);
				}
			} else {
				LOGError("DBUpdateRunC: HIDDEN: user=$team,site=$runsite,contest=$contest would have a balloon for problem=" .  $temp["runproblem"]);
			}
		} else if ($tinhabalao && !$tembalao) {
			$u = DBUserInfo ($contest, $runsite, $team, $c);
			if($u['usertype']=='team') {
				$p = DBGetProblemData ($contest, $temp["runproblem"],$c);
				DBNewTask_old ($contest, $runsite, $team, escape_string("\"" . 
																		$u["username"] ."\" must have _NO_ balloon for problem " . $p[0]["problemname"] . 
																		": " . $p[0]["fullname"]). ". Please verify and remove it, if needed.", "", "", 
							   "t", $p[0]["color"], $p[0]["colorname"], $c);
			}
		}
//	}
	}

	if($bw == 1) {
		DBExec($c, "commit work", "DBUpdateRunC(commit)");
		LOGLevel("Run updated (run=$runnumber,site=$runsite,user=$team,contest=$contest,newstatus=$newstatus,".
				 "judge=$usernumber(site=$usersite),answer=$answer(".$a["runanswer"].")).", 3);
	}
	return true;
}
//devolve uma run que estava sendo respondida. Recebe o numero da run, o numero do site da run e o numero do contest.
//tenta alterar o status para 'openrun'. Se nao conseguir retorna false
function DBChiefRunGiveUp($number,$site,$contest) {
	return DBRunGiveUp($number,$site,$contest,-1,-1);
}
function DBRunGiveUp($number,$site,$contest,$usernumber,$usersite) {
	$c = DBConnect();
	DBExec($c, "begin work", "DBRunGiveUp(transaction)");
	$sql = "select * from runtable as r where r.contestnumber=$contest and " .
		"r.runsitenumber=$site and r.runnumber=$number";
	if ($usernumber != 1 && $usersite != -1) {
		$sql .= " and (r.runstatus='judging' or r.runstatus='judged+') and " .
			"((r.runjudge1=$usernumber and r.runjudgesite1=$usersite) or " .
			" (r.runjudge2=$usernumber and r.runjudgesite2=$usersite))";
		$tx = "Judge";
	} else $tx = "Chief";
	$r = DBExec ($c, $sql . " for update", "DBRunGiveUp(get run for update)");
	$n = DBnlines($r);
	if ($n != 1) {
		DBExec($c, "rollback work", "DBRunGiveUp(rollback)");
		LogLevel("Unable to return a run (maybe the timeout or a chief returned it first). ".
				 "(run=$number, site=$site, contest=$contest)",2);
		return false;
	}
	$temp = DBRow($r, 0);

	$tinhabalao = DBBalloon($contest, $site, $temp["usernumber"], $temp["runproblem"],true,$c);

	$outra = -1;
	if($temp["runjudge1"]==$usernumber && $temp["runjudgesite1"]==$usersite) {
		DBExec($c, "update runtable set runjudge1=NULL, runjudgesite1=NULL, runanswer1=0 " .
			   " where contestnumber=$contest and runnumber=$number and runsitenumber=$site",
			   "DBRunGiveUp(update run judge1)");
		$outra = $temp['runanswer2'];
	}
	if($temp["runjudge2"]==$usernumber && $temp["runjudgesite2"]==$usersite) {
		DBExec($c, "update runtable set runjudge2=NULL, runjudgesite2=NULL, runanswer2=0 " .
			   " where contestnumber=$contest and runnumber=$number and runsitenumber=$site",
               "DBRunGiveUp(update run judge1)");
		$outra = $temp['runanswer1'];
	}

	$newstatus="judging";
	if($temp["runstatus"]=="judged" || $temp["runstatus"]=="judged+") {
   	    DBExec($c, "update runtable set runstatus='judged+', " .
			   ($tx=="Chief" ? "runanswer1=0, runanswer2=0, runjudge1=NULL, runjudge2=NULL, runjudgesite1=NULL, runjudgesite2=NULL, ": "") .
			   " updatetime=" .
			   time()." where contestnumber=$contest and runnumber=$number and runsitenumber=$site",
			   "DBRunGiveUp(update run)");
		$newstatus='judged+';
	} else {
		if($outra == 0 || $tx=="Chief") {
			DBExec($c, "update runtable set runstatus='openrun', runanswer=0, runjudge=NULL, runjudgesite=NULL, ".
				   "runanswer1=0, runanswer2=0, runjudge1=NULL, runjudge2=NULL, runjudgesite1=NULL, runjudgesite2=NULL, ".
				   "updatetime=" .
				   time()." where contestnumber=$contest and runnumber=$number and runsitenumber=$site",
				   "DBRunGiveUp(update run)");
            $newstatus='openrun';
		}
	}
	$tembalao = DBBalloon($contest, $site, $temp["usernumber"], $temp["runproblem"],true,$c);
	if ($tinhabalao && !$tembalao) {
		$u = DBUserInfo ($contest, $site, $temp["usernumber"], $c);
		if($u['usertype']=='team') {
			$p = DBGetProblemData ($contest, $temp["runproblem"],$c);
			DBNewTask_old ($contest, $site, $temp["usernumber"], escape_string("\"" . 
																			   $u["username"] ."\" must have _NO_ balloon for problem " . $p[0]["problemname"] . 
																			   ": " . $p[0]["fullname"]), "", "", "t", $p[0]["color"], $p[0]["colorname"], $c);
		}
	}

	DBExec($c, "commit work", "DBRunGiveUp(commit)");
	LOGLevel("Run returned (run=$number, site=$site, contest=$contest, user=$usernumber(site=$usersite)), ".
			 "newstatus=$newstatus", 3);
	return true;
}
function DBRunDelete($number,$site,$contest,$user,$usersite) {
	$c = DBConnect();
	DBExec($c, "begin work", "DBRunDelete(transaction)");
	$sql = "select * from runtable as r where r.contestnumber=$contest and " .
		"r.runsitenumber=$site and r.runnumber=$number";
	$r = DBExec ($c, $sql . " for update", "DBRunDelete(get run for update)");
	$n = DBnlines($r);
	if ($n != 1) {
		DBExec($c, "rollback work", "DBRunDelete(rollback)");
		LogLevel("Unable to delete a run. ".
				 "(run=$number, site=$site, contest=$contest)",1);
		return false;
	}
	$temp = DBRow($r, 0);

	$tinhabalao = DBBalloon($contest, $site, $temp["usernumber"], $temp["runproblem"],true,$c);

	DBExec($c, "update runtable set runstatus='deleted', runjudge=$user, runjudgesite=$usersite, updatetime=" . 
		   time()." where contestnumber=$contest and runnumber=$number and runsitenumber=$site",
		   "DBRunDelete(update run)");

	$tembalao = DBBalloon($contest, $site, $temp["usernumber"], $temp["runproblem"],true,$c);
	if ($tinhabalao && !$tembalao) {
		$u = DBUserInfo ($contest, $site, $temp["usernumber"], $c);
		if($u['usertype']=='team') {
			$p = DBGetProblemData ($contest, $temp["runproblem"],$c);
			DBNewTask_old ($contest, $site, $temp["usernumber"], escape_string("\"" . 
																			   $u["username"] . "\" must have _NO_ balloon for problem " . $p[0]["problemname"] . 
																			   ": " . $p[0]["fullname"]), "", "", "t", $p[0]["color"], $p[0]["colorname"], $c);
		}
	}

	DBExec($c, "commit work", "DBRunDelete(commit)");
	LOGLevel("Run deleted (run=$number, site=$site, contest=$contest, user=$user(site=$usersite)).", 3);
	return true;
}

//pega uma run para julgar. Recebe o numero da run, o numero do site e o numero do contest.
//tenta alterar o status para 'judging' e se conseguir, devolve um array com dados da run. Se nao conseguir,
//retorna false
//Retorna no array: contestnumber, sitenumber, number, timestamp (em segundos), problemname, 
//			problemnumber, language, sourcename, sourceoid, (langscript, infiles, solfiles)
function DBChiefGetRunToAnswer($number,$site,$contest) {
	return DBGetRunToAnswerC($number,$site,$contest,1);
}
function DBGetRunToAnswer($number,$site,$contest) {
	return DBGetRunToAnswerC($number,$site,$contest,0);
}
function DBGetRunToAnswerC($number,$site,$contest,$chief) {
	$c = DBConnect();
	DBExec($c, "begin work", "DBGetRunToAnswerC(transaction)");
	$sql = "select r.contestnumber as contestnumber, r.runsitenumber as sitenumber, r.runanswer as answer, " .
		"r.runanswer1 as answer1, r.runanswer2 as answer2, " .
		"r.runjudge as judge, r.runjudgesite as judgesite, " . 
		"r.runjudge1 as judge1, r.runjudgesite1 as judgesite1, r.runjudge2 as judge2, r.runjudgesite2 as judgesite2, " .
		"r.runnumber as number, r.rundatediff as timestamp, r.runstatus as status, " .
		"r.rundata as sourceoid, r.runfilename as sourcename, l.langnumber as langnumber, " .
		"p.problemname as problemname, p.problemnumber as problemnumber, l.langname as language, l.langextension as extension, " .
		"r.autoip as autoip, r.autobegindate as autobegin, r.autoenddate as autoend, r.autoanswer as autoanswer, ".
		"r.autostdout as autostdout, r.autostderr as autostderr ".

		"from runtable as r, problemtable as p, langtable as l " .
		"where r.contestnumber=$contest and p.contestnumber=r.contestnumber and " .
		"r.runproblem=p.problemnumber and r.runsitenumber=$site and " .
		"r.runlangnumber=l.langnumber and r.contestnumber=l.contestnumber and " .
		"r.runnumber=$number";
	if ($chief != 1) {
		$sql .= " and (r.runstatus='openrun' or " .
			"(r.runstatus='judged+' and r.runjudge is NULL) or " .
			"((r.runstatus='judging' or r.runstatus='judged+') and " .
			" (r.runjudge1 is null or r.runjudge2 is null or " .
			" ((r.runjudge1=" . $_SESSION["usertable"]["usernumber"] . " and " .
			"   r.runjudgesite1=" . $_SESSION["usertable"]["usersitenumber"] . ") or " .
			" (r.runjudge2=" . $_SESSION["usertable"]["usernumber"] . " and " .
			"  r.runjudgesite2=" . $_SESSION["usertable"]["usersitenumber"] . ")))))";
		$tx = "Judge";
	} else $tx = "Chief";
	$r = DBExec ($c, $sql . " for update", "DBGetRunToAnswerC(get run/prob/lang for update)");
	$n = DBnlines($r);
	if ($n != 1) {
		DBExec($c, "rollback work", "DBGetRunToAnswerC(rollback)");
		LogLevel("Unable to get a run (maybe other judge got it first). (run=$number, site=$site, ".
				 "contest=$contest)",2);
		return false;
	}
	$a = DBRow($r,0);

	if ($chief != 1) {
		$upd="";
	   	if($a["status"]=="openrun") $upd="runstatus='judging',";
		
		if(($a["judge1"]!=$_SESSION["usertable"]["usernumber"] || 
			$a["judgesite1"]!=$_SESSION["usertable"]["usersitenumber"]) &&
		   ($a["judge2"]!=$_SESSION["usertable"]["usernumber"] ||
			$a["judgesite2"]!=$_SESSION["usertable"]["usersitenumber"])) {
		    if($a["judge1"]=='' && $a['judgesite1']=='') {
		    	DBExec($c, "update runtable set runjudge1=" . $_SESSION["usertable"]["usernumber"] . 
					   ",$upd updatetime=".time().", " .
					   "runjudgesite1=" . $_SESSION["usertable"]["usersitenumber"] . " " .
					   "where contestnumber=$contest and runnumber=$number and runsitenumber=$site",
					   "DBGetRunToAnswerC(update run judge1)");
		    } else {
		    	DBExec($c, "update runtable set runjudge2=" . $_SESSION["usertable"]["usernumber"] . 
					   ",$upd updatetime=".time().", " .
					   "runjudgesite2=" . $_SESSION["usertable"]["usersitenumber"] . " " .
					   "where contestnumber=$contest and runnumber=$number and runsitenumber=$site",
					   "DBGetRunToAnswerC(update run judge2)");
		    }
		}
	}

	DBExec($c, "commit work", "DBGetRunToAnswerC(commit)");
	LOGLevel("User got a run (run=$number, site=$site, contest=$contest, user=". 
			 $_SESSION["usertable"]["usernumber"].
			 "(site=".$_SESSION["usertable"]["usersitenumber"] .")).", 3);
	return $a;
}
function DBGetRunToAutojudging($contest, $ip) {
	$c = DBConnect();
	DBExec($c, "begin work", "DBGetRunToAnswerC(transaction)");
	$sql = "select r.contestnumber as contest, r.runsitenumber as site, r.runanswer as answer, " .
		"r.runanswer1 as answer1, r.runanswer2 as answer2, " .  
		"r.runnumber as number, r.rundatediff as timestamp, r.runstatus as status, " .
		"r.rundata as sourceoid, r.runfilename as sourcename, l.langnumber as langnumber, " .
		"p.problemname as problemname, p.problemnumber as problemnumber, l.langextension as extension, l.langname as language, " .
		"p.problembasefilename as basename, ".
		"p.probleminputfilename as inputname, p.probleminputfile as inputoid, " .
		"r.autoip as autoip, r.autobegindate as autobegin, r.autoenddate as autoend, r.autoanswer as autoanswer, ".
		"r.autostdout as autostdout, r.autostderr as autostderr ".
		"from runtable as r, problemtable as p, langtable as l " .
		"where r.contestnumber=$contest and p.contestnumber=r.contestnumber and " .
		"r.runproblem=p.problemnumber and r.runlangnumber=l.langnumber and ".
		"r.contestnumber=l.contestnumber and " .
		"r.autoip='' order by r.runnumber for update limit 1";
	$r = DBExec ($c, $sql, "DBGetRunToAutoJudging(get run/prob/lang for update)");
	$n = DBnlines($r);
	if ($n < 1) {
		DBExec($c, "rollback work", "DBGetRunToAutoJudging(rollback)");
		return false;
	}
	$a = DBRow($r,0);
	$t = time();

	DBExec($c, "update runtable set autoip='" . $ip . "', " . 
		   "autobegindate=$t, autoenddate=null, autoanswer=null, autostdout=null, autostderr=null, " .
		   "updatetime=$t " .
		   "where contestnumber=${a["contest"]} and runnumber=${a["number"]} and runsitenumber=${a["site"]}",
		   "DBGetRunToAutojudging(update run)");

	DBExec($c, "commit work", "DBGetRunToAutojudging(commit)");
	LOGLevel("Autojudging got a run (run=${a["number"]}, site=${a["site"]}, contest=${a["contest"]})", 3);
	return $a;
}
function DBUpdateRunAutojudging($contest, $site, $number, $ip, $answer, $stdout, $stderr, $retval=0) {
	if($retval=="") $retval=0;
	$c = DBConnect();
	DBExec($c, "begin work", "DBUpdateRunAutojudging(transaction)");
	$sql = "select * from runtable as r " .
		"where r.contestnumber=$contest and r.runnumber=$number and r.runsitenumber=$site and " .
		"r.autoip='$ip'";
	$r = DBExec ($c, $sql . " for update", "DBUpdateRunAutoJudging(get run for update)");
	$n = DBnlines($r);
	if ($n != 1) {
		DBExec($c, "rollback work", "DBUpdateRunAutoJudging(rollback)");
		LogLevel("Unable to get a run for autojudging (run=$number, site=$site, contest=$contest)",1);
		return false;
	}
	$a = DBRow($r,0);
	$b = DBSiteInfo($contest, $site, $c);
	$t = time();

	if (($oid1 = DB_lo_import($c, $stdout)) === false) {
		DBExec($c, "rollback work", "DBUpdateRunAutojudging(rollback-stdout)");
		LOGError("Unable to create a large object for file $stdout.");
		return false;
	}

	if (($oid2 = DB_lo_import($c, $stderr)) === false) {
		DBExec($c, "rollback work", "DBUpdateRunAutojudging(rollback-stderr)");
		LOGError("Unable to create a large object for file $stderr.");
		return false;
	}

	if($answer=="") $answer="null";
	else $answer="'$answer'";
	DBExec($c, "update runtable set autoenddate=$t, autoanswer=$answer, autostdout=$oid1, autostderr=$oid2, " .
		   "updatetime=$t " .
		   "where contestnumber=$contest and runnumber=$number and runsitenumber=$site",
		   "DBUpdateRunAutojudging(update run)");

	$b = DBSiteInfo($contest, $site, $c);

	if($b["siteautojudge"]!="t") {
		DBExec($c, "commit work", "DBUpdateRunAutojudging(commit)");
		LOGLevel("Autojudging answered a run (run=$number, site=$site, contest=$contest, answer='$answer', retval=$retval)", 3);
		return true;
	}

	echo "DEBUG: $contest, $site, " .$a["usernumber"].", $site, $number, $retval\n";
	if(DBUpdateRunO($contest, $site, $a["usernumber"], $site, $number, $retval, $c)==false) {
		DBExec($c, "rollback work", "DBUpdateRunAutoJudging(rollback)");
		LOGError("Unable to automatically update a run answer (run=$number, site=$site, ".
				 "contest=$contest, answer='$answer', retval=$retval)");
		return false;
	}
	DBExec($c, "commit work", "DBUpdateRunAutojudging(commit)");
	LOGLevel("Autojudging automatically answered a run (run=$number, site=$site, contest=$contest, retval=$retval, answer='$answer')", 3);
	return true;
}
function DBGiveUpRunAutojudging($contest, $site, $number, $ip="", $ans="") {
	$c = DBConnect();
	DBExec($c, "begin work", "DBGiveUpRunAutojudging(transaction)");
	$sql = "select * from runtable as r " .
		"where r.contestnumber=$contest and r.runnumber=$number and r.runsitenumber=$site";
	$r = DBExec ($c, $sql . " for update", "DBGiveUpRunAutoJudging(get run for update)");
	$n = DBnlines($r);
	if ($n != 1) {
		DBExec($c, "rollback work", "DBGiveUpRunAutoJudging(rollback)");
		LogLevel("Unable to giveup a run from autojudging (run=$number, site=$site, contest=$contest)",1);
		return false;
	}
	$a = DBRow($r,0);
	$t = time();

	if($ip=="") {
		DBExec($c, "update runtable set autoenddate=null, autoanswer=null, autostdout=null, autostderr=null, " .
			   "updatetime=$t, autobegindate=null, autoip='' " .
			   "where contestnumber=$contest and runnumber=$number and runsitenumber=$site",
			   "DBGiveUpRunAutojudging(update run)");
	} else {
		DBExec($c, "update runtable set autoenddate=$t, autoanswer='$ans', autostdout=null, ".
			   "autostderr=null, updatetime=$t, autoip='$ip' " .
			   "where contestnumber=$contest and runnumber=$number and runsitenumber=$site",
			   "DBGiveUpRunAutojudging(update run-withip)");
	}
	DBExec($c, "commit work", "DBGiveUpRunAutojudging(commit)");
	LOGLevel("Run gaveup from Autojudging (run=$number, site=$site, contest=$contest)", 3);
	return true;
}
function DBAllRuns($contest) {
	return DBOpenRunsSNS($contest,"x",-1);
}
function DBAllRunsInSites($contest,$site,$order='run') {
	return DBOpenRunsSNS($contest,$site,-1,$order);
}
//function DBOpenRuns($contest) {
//	return DBOpenRunsSNS($contest,"x",1);
//}
function DBOpenRunsInSites($contest,$site) {
	return DBOpenRunsSNS($contest,$site,1);
}
function DBOpenRunsSNS($contest,$site,$st,$order='run') {
	$c = DBConnect();
	$sql = "select distinct r.runnumber as number, r.rundatediff as timestamp, r.usernumber as user, " .
		"p.problemname as problem, r.runstatus as status, l.langname as language, l.langextension as extension, " .
		"a.yes as yes, p.problemcolor as color, p.problemcolorname as colorname, " .
		"r.runsitenumber as site, r.runjudge as judge, r.runjudgesite as judgesite, " .
		"r.runjudge1 as judge1, r.runjudgesite1 as judgesite1, " .
		"r.runjudge2 as judge2, r.runjudgesite2 as judgesite2, " .
		"a.runanswer as answer, r.runfilename as filename, " .
		"r.runanswer1 as answer1, r.runanswer2 as answer2, " .
		"r.autobegindate as autobegin, r.autoenddate as autoend, r.autoanswer as autoanswer ".
		"from runtable as r, problemtable as p, langtable as l, answertable as a, usertable as u " .
		"where r.contestnumber=$contest and p.contestnumber=r.contestnumber and u.contestnumber=r.contestnumber and " .
		"r.runproblem=p.problemnumber and l.contestnumber=r.contestnumber and r.usernumber=u.usernumber and r.runsitenumber=u.usersitenumber and " .
		"l.langnumber=r.runlangnumber and a.answernumber=r.runanswer and " .
		"a.contestnumber=r.contestnumber";
	if ($site != "x") {
		$str = explode(",", $site);
		$sql .= " and (r.runsitenumber=-1";
		for ($i=0;$i<count($str);$i++) {
			if (is_numeric($str[$i])) $sql .= " or r.runsitenumber=".$str[$i];
		}
		$sql .= ")";
	}

	if ($st == 1) {
		$sql .= " and (not (r.runjudge1=". $_SESSION["usertable"]["usernumber"] . " and " .
			"r.runjudgesite1=". $_SESSION["usertable"]["usersitenumber"] . " and r.runanswer1!=0)) and ";
		$sql .= " (not (r.runjudge2=". $_SESSION["usertable"]["usernumber"] . " and " .
			"r.runjudgesite2=". $_SESSION["usertable"]["usersitenumber"] . " and r.runanswer2!=0)) and " .

			"(not ((r.runjudge1!=". $_SESSION["usertable"]["usernumber"] . " or " .
			"r.runjudgesite1!=". $_SESSION["usertable"]["usersitenumber"] . ") and " .
			" (r.runjudge2!=". $_SESSION["usertable"]["usernumber"] . " or " .
			"r.runjudgesite2!=". $_SESSION["usertable"]["usersitenumber"] . ") and " .
			" (not (r.runjudge1 is null)) and (not (r.runjudge2 is null))))";
		if ($order == 'report')
			$sql .= " and (u.usertype != 'judge')";
		$sql .= " and (not r.runstatus = 'judged') " .
			" and not r.runstatus ~ 'deleted' order by ";
	} else $sql .= " order by ";

	if($order == "site")
		$sql .= "r.runsitenumber,";
	else if ($order == "status")
		$sql .= "r.runstatus,";
  	else if ($order == "judge")
		$sql .= "r.runjudge,r.runjudgesite,";
	else if ($order == "problem")
		$sql .= "p.problemname,";
	else if ($order == "language")
		$sql .= "l.langname,";
	else if ($order == "answer")
		$sql .= "a.runanswer,";
	else if ($order == "user")
		$sql .= "r.usernumber,r.runsitenumber,";

	if ($st == 1 || $order == "report")
		$sql .= "r.runnumber";
	else
		$sql .= "r.rundatediff desc";

	$r = DBExec($c, $sql, "DBOpenRunsSNS(get run/prob/lang/ans)");

	$n = DBnlines($r);
	$a = array();
	for ($i=0;$i<$n;$i++)
		$a[$i] = DBRow($r,$i);
	return $a;
}
function DBNewRun($param,$c=null) {
	if(isset($param['contestnumber']) && !isset($param['contest'])) $param['contest']=$param['contestnumber'];
	if(isset($param['sitenumber']) && !isset($param['site'])) $param['site']=$param['sitenumber'];
	if(isset($param['usernumber']) && !isset($param['user'])) $param['user']=$param['usernumber'];
	if(isset($param['number']) && !isset($param['runnumber'])) $param['runnumber']=$param['number'];
	if(isset($param['runlangnumber']) && !isset($param['lang'])) $param['lang']=$param['runlangnumber'];
	if(isset($param['runproblem']) && !isset($param['problem'])) $param['problem']=$param['runproblem'];

	$ac=array('contest','site','user','problem','lang','filename','filepath');
	$ac1=array('runnumber','rundate','rundatediff','rundatediffans','runanswer','runstatus','runjudge','runjudgesite',
			   'runjudge1','runjudgesite1','runanswer1','runjudge2','runjudgesite2','runanswer2',
			   'autoip','autobegindate','autoenddate','autoanswer','autostdout','autostderr','updatetime');
	$type['contest']=1;
	$type['autobegindate']=1;
	$type['autoenddate']=1;
	$type['problem']=1;
	$type['updatetime']=1;
	$type['site']=1;
	$type['user']=1;
	$type['runnumber']=1;
	$type['rundatediffans']=1;
	$type['rundatediff']=1;
	$type['rundate']=1;
	$type['runanswer']=1;
	$type['runjudge']=1;
	$type['runjudgesite']=1;
	$type['runjudge1']=1;
	$type['runjudgesite1']=1;
	$type['runanswer1']=1;
	$type['runjudge2']=1;
	$type['runjudgesite2']=1;
	$type['runanswer2']=1;
	foreach($ac as $key) {
		if(!isset($param[$key]) || $param[$key]=="") {
			MSGError("DBNewRun param error: $key not found");
			return false;
		}
		if(isset($type[$key]) && !is_numeric($param[$key])) {
			MSGError("DBNewRun param error: $key is not numeric");
			return false;
		}
		$$key = sanitizeText($param[$key]);
	}
	$t = time();
	$autoip='';
	$autobegindate='NULL';
	$autoenddate='NULL';
	$autoanswer='';
	$autostdout='';
	$autostderr='';
	$runjudge='NULL';
	$runjudgesite='NULL';
	$runjudge1='NULL';
	$runjudgesite1='NULL';
	$runanswer1=0;
	$runjudge2='NULL';
	$runjudgesite2='NULL';
	$runanswer2=0;
	$runnumber=-1;
	$updatetime = -1;
	$rundatediff = -1;
	$rundate = $t;
	$runanswer=0;
	$rundatediffans = 999999999;
	$runstatus='openrun';
	foreach($ac1 as $key) {
		if(isset($param[$key])) {
			$$key = sanitizeText($param[$key]);
			if(isset($type[$key]) && !is_numeric($param[$key])) {
				MSGError("DBNewRun param error: $key is not numeric");
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
		DBExec($c, "begin work", "DBNewRun(transaction)");
	}
	$insert=true;
	$oid1 = '';
	$oid2 = '';
	$oldold1='';
	$oldold2='';
	$sql = "select sitenextrun as nextrun from " .
		"sitetable where sitenumber=$site and contestnumber=$contest for update";
	$r = DBExec($c, $sql, "DBNewRun(get site for update)");
	if (DBnlines($r) != 1) {
		DBExec($c, "rollback work", "DBNewRun(rollback-site)");
		LOGError("Unable to find a unique site/contest in the database. SQL=(" . $sql . ")");
		MSGError("Unable to find a unique site/contest in the database.");
		exit;
	}
	$a = DBRow($r,0);
	$n = $a["nextrun"] + 1;
	if($runnumber > 0) {
		$sql = "select * from runtable as t where t.contestnumber=$contest and " .
			"t.runsitenumber=$site and t.runnumber=$runnumber";
		$r = DBExec ($c, $sql . " for update", "DBNewRun(get run for update)");
		$n = DBnlines($r);
		if ($n > 0) {
			$insert=false;
			$lr = DBRow($r,0);
			$t = $lr['updatetime'];
			if(isset($lr['autostdout']))
				$oid1 = $lr['autostdout'];
			if(isset($lr['autostderr']))
				$oid2 = $lr['autostderr'];
		}
		$n = $runnumber;
	} else
		$runnumber = $n;

	if($rundatediff < 0) {
		$b = DBSiteInfo($contest, $site, $c);
		$dif = $b["currenttime"]; 
		$rundatediff = $dif;
		if ($dif < 0) { if(!isset($param['allowneg'])) {
			DBExec($c, "rollback work", "DBNewRun(rollback-started)");
			LOGError("Tried to submit a run but the contest is not started. SQL=(" . $sql . ")");
			MSGError("The contest is not started yet!");
			return false;
		} }
		if (!$b["siterunning"]) {
			DBExec($c, "rollback work", "DBNewRun(rollback-over)");
			LOGError("Tried to submit a run but the contest is over. SQL=(" . $sql . ")");
			MSGError("The contest is over!");
			return false;
		}
	} else {
		$dif = $rundatediff;
	}

	if($updatetime > $t || $insert) {
		DBExec($c, "update sitetable set sitenextrun=$runnumber, updatetime=".$t.
			   " where sitenumber=$site and contestnumber=$contest and sitenextrun<$runnumber", "DBNewRun(update site)");

//	LOGError($autostdout);
		if(substr($autostdout,0,7)=="base64:") {
			$autostdout = base64_decode(substr($autostdout,7));
			$oldoid1 = $oid1;
			if (($oid1 = DB_lo_import_text($c, $autostdout)) == null) {
				DBExec($c, "rollback work", "DBNewRun(rollback-import stdout)");
				LOGError("Unable to create a large object for file stdout (run=$runnumber,site=$site,contest=$contest).");
				MSGError("problem importing stdout to database. Contact an admin now!");
				exit;
			}
		} else {
			if($autostdout != '') {
				DBExec($c, "rollback work", "DBNewRun(rollback-import stderr)");
				LOGError("Unable to create a large object for file stdout that is not BASE64 (run=$runnumber,site=$site,contest=$contest).");
				MSGError("problem importing stdout (not BASE64) to database. Contact an admin now!");
				exit;
			}
			$oid1 = 'NULL';
		}
		if(substr($autostderr,0,7)=="base64:") {
//		LOGError($autostderr);
			$autostderr = base64_decode(substr($autostderr,7));
			$oldoid2 = $oid2;
			if (($oid2 = DB_lo_import_text($c, $autostderr)) == null) {
				DBExec($c, "rollback work", "DBNewRun(rollback-import stderr)");
				LOGError("Unable to create a large object for file stderr (run=$runnumber,site=$site,contest=$contest).");
				MSGError("problem importing stderr to database. Contact an admin now!");
				exit;
			}
		} else {
			if($autostderr != '') {
				DBExec($c, "rollback work", "DBNewRun(rollback-import stderr)");
				LOGError("Unable to create a large object for file stderr that is not BASE64 (run=$runnumber,site=$site,contest=$contest).");
				MSGError("problem importing stderr (not BASE64) to database. Contact an admin now!");
				exit;
			}
			$oid2 = 'NULL';
		}
	}
	$ret=1;
	if($insert) {
		if(substr($filepath,0,7)!="base64:") {
			if (($oid = DB_lo_import($c, $filepath)) === false) {
				DBExec($c, "rollback work", "DBNewRun(rollback-import)");
				LOGError("DBNewRun: Unable to create a large object for file $filepath.");
				MSGError("problem importing file $filepath to database. Contact an admin now!");
				exit;
			}
		} else {
			$filepath = base64_decode(substr($filepath,7));
			if (($oid = DB_lo_import_text($c, $filepath)) == null) {
				DBExec($c, "rollback work", "DBNewRun(rollback-import)");
				LOGError("DBNewRun: Unable to create a large object for file.");
				MSGError("problem importing file to database. Contact an admin now!");
				exit;
			}
		}
		DBExec($c, "INSERT INTO runtable (contestnumber, runsitenumber, runnumber, usernumber, rundate, " .
			   "rundatediff, rundatediffans, runproblem, runfilename, rundata, runanswer, runstatus, runlangnumber, " .
			   "runjudge, runjudgesite, runanswer1, runjudge1, runjudgesite1, runanswer2, runjudge2, runjudgesite2, ".
			   "autoip, autobegindate, autoenddate, autoanswer, autostdout, autostderr, updatetime) " . 
			   "VALUES ($contest, $site, $n, $user, $rundate, $rundatediff, $rundatediffans, $problem, '$filename', $oid, $runanswer, " .
			   "'$runstatus', $lang, $runjudge, $runjudgesite, $runanswer1, $runjudge1, $runjudgesite1, $runanswer2, $runjudge2, " .
			   "$runjudgesite2, '$autoip', $autobegindate, $autoenddate, '$autoanswer', $oid1, $oid2, $updatetime)",
			   "DBNewRun(insert run)");
		if($cw) {
			DBExec($c, "commit work", "DBNewRun(commit)");
			LOGLevel("User $user submitted a run (#$n) on site #$site " .
					 "(problem=$problem,filename=$filename,lang=$lang,contest=$contest,date=$t,datedif=$dif,oid=$oid).",2);
		}
		$ret=2;
	} else {
		if($updatetime > $t) {
			$ret=2;
			DBExec($c, "update runtable set rundate=$rundate, rundatediff=$rundatediff, " .
				   "rundatediffans=$rundatediffans, runanswer=$runanswer, runanswer1=$runanswer1, runanswer2=$runanswer2, runstatus='$runstatus', ".
				   "runjudge1=$runjudge1, runjudgesite1=$runjudgesite1, runjudge2=$runjudge2, runjudgesite2=$runjudgesite2, " .
				   "runjudge=$runjudge, runjudgesite=$runjudgesite, updatetime=$updatetime, ".
				   "autoip='$autoip', autobegindate=$autobegindate, autoenddate=$autoenddate, autoanswer='$autoanswer', " .
				   "autostdout=$oid1, autostderr=$oid2 " .
				   "where runnumber=$runnumber and contestnumber=$contest and runsitenumber=$site", "DBNewRun(update run)");

			if(is_numeric($oldoid1)) DB_lo_unlink($c,$oldoid1);
			if(is_numeric($oldoid2)) DB_lo_unlink($c,$oldoid2);
		}
		if($cw) DBExec($c, "commit work", "DBNewRun(commit-update)");
	}
	return $ret;
/* // isso gera problemas de portabilidade e de seguranca (quando outros usuarios tambem tem shell
   // no servidor e podem construir paginas web. Eles podem usar essas paginas para acessar esses arquivos,
   // pois os mesmos ficaram com dono apache/www-data/etc)
   umask(0077);
   @mkdir("/tmp/boca");
   if (!move_uploaded_file ($filepath,
   "/tmp/boca/contest${contest}.site${site}.run${n}.user${user}.problem${problem}.time${t}.${filename}"))
   LOGLevel("Run not saved as file (run=$n,site=$site,contest=$contest", 1);
*/
}
//recebe o numero do contest, o numero do site e o numero do usuario
//devolve um array, onde cada linha tem os atributos
//  number (numero da run)
//  timestamp (hora da criacao da run)
//  problem (nome do problema)
//  status (situacao da run)
//  answer (texto com a resposta)
function DBUserRuns($contest,$site,$user) {
	$b = DBSiteInfo($contest, $site);
	if ($b == null) {
		exit;
	}
	$t = $b["currenttime"]; 

	$c = DBConnect();
	$r = DBExec($c, "select distinct r.runnumber as number, r.rundatediff as timestamp, " .
	     		"r.runfilename as filename, r.rundata as oid, " .
				"p.problemcolorname as colorname, p.problemcolor as color, a.yes as yes, " .
				"p.problemname as problem, r.runstatus as status, l.langname as language, l.langextension as extension, " .
				"a.runanswer as answer, a.fake as ansfake, r.rundatediffans as anstime, " .
				"r.runanswer1 as answer1, r.runanswer2 as answer2 " .
				"from runtable as r, problemtable as p, answertable as a, langtable as l " .
				"where r.contestnumber=$contest and p.contestnumber=r.contestnumber and " .
				"l.contestnumber=r.contestnumber and l.langnumber=r.runlangnumber and " .
				"r.contestnumber=a.contestnumber and r.runproblem=p.problemnumber and " .
				"r.runsitenumber=$site and r.usernumber=$user and not r.runstatus ~ 'deleted' and " .
				"(r.rundatediffans<=$t or (r.runstatus != 'judged' and r.rundatediff<=$t)) and " .
				"a.answernumber=r.runanswer order by r.runnumber",
				"DBUserRuns(get run/prob/ans/lang)");
	$n = DBnlines($r);

	$a = array();
	for ($i=0;$i<$n;$i++) {
		$a[$i] = DBRow($r,$i);
		if ($a[$i]["timestamp"] >= $b["sitelastmileanswer"])
			$a[$i]["answer"] = "";
	}
	return $a;
}
function DBUserRunsYES($contest,$site,$user) {
	$b = DBSiteInfo($contest, $site);
	if ($b == null) {
		exit;
	}
	$t = $b["currenttime"]; 
	$c = DBConnect();
	$r = DBExec($c, "select distinct p.problemcolorname as colorname, p.problemcolor as color, " .
				"r.rundatediff as timestamp, p.problemnumber as number " .
				"from runtable as r, problemtable as p, answertable as a " .
				"where r.contestnumber=$contest and p.contestnumber=r.contestnumber and " .
				"r.contestnumber=a.contestnumber and r.runproblem=p.problemnumber and " .
				"r.runsitenumber=$site and r.usernumber=$user and not r.runstatus ~ 'deleted' and " .
				"(r.rundatediffans<=$t or (r.runstatus != 'judged' and r.rundatediff<=$t)) and " .
				"a.answernumber=r.runanswer and a.yes='t' order by r.rundatediff",
				"DBUserRunsYES(get run/prob/ans/lang)");
	$n = DBnlines($r);

	$a = array(); $j=0;
	$p = array();
	for ($i=0;$i<$n;$i++) {
		$aa = DBRow($r,$i);
		if ($aa["timestamp"] < $b["sitelastmileanswer"]) {
			if(!isset($p[$aa["number"]])) {
				$p[$aa["number"]] = 1;
				$a[$j] = $aa;
				$j++;
			}
		}
	}
	return $a;
}
//recebe o numero do contest, o numero do site do juiz e o numero do juiz
//devolve um array, onde cada linha tem os atributos
//  number (numero da run)
//  timestamp (hora da criacao da run)
//  problem (nome do problema)
//  status (situacao da run)
//  answer (texto com a resposta)
function DBJudgedRuns($contest,$site,$user) {
	$c = DBConnect();
	$r = DBExec($c, "select distinct r.runsitenumber as site, r.runnumber as number, r.rundatediff as timestamp, " .
				"p.problemname as problem, r.runstatus as status, l.langname as language, l.langextension as extension, " .
				"a.runanswer as answer, r.updatetime, r.runanswer1 as answer1, r.runanswer2 as answer2 " .
				"from runtable as r, problemtable as p, answertable as a, langtable as l " .
				"where r.contestnumber=$contest and p.contestnumber=r.contestnumber and " .
				"l.contestnumber=r.contestnumber and l.langnumber=r.runlangnumber and " .
				"r.contestnumber=a.contestnumber and r.runproblem=p.problemnumber and " .
				"a.answernumber=r.runanswer and " .  
				"((r.runjudgesite=$site and r.runjudge=$user) or ".
				" (r.runjudgesite1=$site and r.runjudge1=$user) or ".
				" (r.runjudgesite2=$site and r.runjudge2=$user)) ".
				" order by r.updatetime",
				"DBJudgedRuns(get run/prob/ans/lang)");
	$n = DBnlines($r);

	$a = array();
	for ($i=0;$i<$n;$i++) {
		$a[$i] = DBRow($r,$i);
	}
	return $a;
}

function exitmsg($retval) {
/* FROM SAFEEXEC
# 0 ok
# 1 compile error
# 2 runtime error
# 3 timelimit exceeded
# 4 internal error
# 5 parameter error
# 6 internal error
# 7 memory limit exceeded
# 8 security threat
# 9 runtime error
*/
/*
	DBExec($c, "insert into answertable (contestnumber, answernumber, runanswer, yes, fake) values ".
			"($n, 0, 'Not answered yet', 'f', 't')", "DBNewContest(insert fake answer)");
	DBExec($c, "insert into answertable (contestnumber, answernumber, runanswer, yes, fake) values ".
			"($n, 1, 'YES', 't', 'f')", "DBNewContest(insert YES answer)");
	DBExec($c, "insert into answertable (contestnumber, answernumber, runanswer, yes, fake) values ".
			"($n, 2, 'NO - Compilation error', 'f', 'f')", "DBNewContest(insert CE answer)");
	DBExec($c, "insert into answertable (contestnumber, answernumber, runanswer, yes, fake) values ".
			"($n, 3, 'NO - Runtime error', 'f', 'f')", "DBNewContest(insert RE answer)");
	DBExec($c, "insert into answertable (contestnumber, answernumber, runanswer, yes, fake) values ".
			"($n, 4, 'NO - Time limit exceeded', 'f', 'f')", "DBNewContest(insert TLE answer)");
	DBExec($c, "insert into answertable (contestnumber, answernumber, runanswer, yes, fake) values ".
			"($n, 5, 'NO - Presentation error', 'f', 'f')", "DBNewContest(insert PE answer)");
	DBExec($c, "insert into answertable (contestnumber, answernumber, runanswer, yes, fake) values ".
			"($n, 6, 'NO - Wrong answer', 'f', 'f')", "DBNewContest(insert WA answer)");
	DBExec($c, "insert into answertable (contestnumber, answernumber, runanswer, yes, fake) values ".
			"($n, 7, 'NO - Contact staff', 'f', 'f')", "DBNewContest(insert CS answer)");
*/
	if($retval==-1) {
		$answer="Internal error while executing run command";
		$retval = 7; // contact staff
	}
	else if($retval==1) {
		$answer="Compilation error";
		$retval = 2; // compilation error
	}
	else if($retval==2) {
		$answer="Runtime error";
		$retval = 3; // runtime error
	}
	else if($retval==3) {
		$answer="Time limit exceeded";
		$retval = 4; // timelimit exceeded
	}
	else if($retval==4) {
		$answer="safeexec internal error (4)";
		$retval = 7; // contact staff
	}
	else if($retval==5) {
		$answer="safeexec error: parameter problem";
		$retval = 7; // contact staff
	}
	else if($retval==6) {
		$answer="safeexec internal error (6)";
		$retval = 7; // contact staff
	}
	else if($retval==7) {
		$answer="Runtime error (memory-limit)";
		$retval = 3; // runtime error
	}
	else if($retval==8) {
		$answer="Code generates security threat";
		$retval = 3; // runtime error
	}
	else if($retval==9) {
		$answer="Runtime error (or possible java class name mismatch)";
	} else {
		$answer="Unknown autojudge status";
		$retval = 7;
	}
	return array($retval,$answer);
}

// eof
?>
