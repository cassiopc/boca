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
function DBDropTaskTable() {
	 $c = DBConnect();
	 $r = DBExec($c, "drop table \"tasktable\"", "DBDropTaskTable(drop table)");
}
function DBCreateTaskTable() {
	 $c = DBConnect();
	 $conf = globalconf();
	 if($conf["dbuser"]=="") $conf["dbuser"]="bocauser";
	 $r = DBExec($c, "
CREATE TABLE \"tasktable\" (
\"contestnumber\" int4 NOT NULL,			-- (id do concurso)
\"sitenumber\" int4 NOT NULL,			-- (id do site)
\"usernumber\" int4 NOT NULL,			-- (id do usuario requisitando a tarefa)
\"tasknumber\" int4 NOT NULL,			-- (id do problema)
\"taskstaffnumber\" int4,				-- (id do usuario executando a tarefa)
\"taskstaffsite\" int4,				-- (id do usuario executando a tarefa)
\"taskdate\" int4 NOT NULL,		-- (dia/hora da submissao no local de origem)
\"taskdatediff\" int4 NOT NULL,	-- (diferenca entre inicio da competicao e dia/hora da submissao em seg)
\"taskdatediffans\" int4 NOT NULL, -- (diferenca entre inicio da competicao e dia/hora da correcao em seg)
\"taskdesc\" varchar(200),			-- (descricao da tarefa)
\"taskfilename\" varchar(100),			-- (nome do arquivo)
\"taskdata\" oid,					-- (apontador para o arquivo)
\"tasksystem\" bool NOT NULL,		-- (tarefa de sistema?)
\"taskstatus\" varchar(20) NOT NULL,		-- (status da tarefa: opentask, processing, done)
\"colorname\" varchar(100) DEFAULT '',     -- nome da cor do problema
\"color\" varchar(6) DEFAULT '',           -- cor do problema, formato html (RGB hexadecimal)
\"updatetime\" int4 DEFAULT EXTRACT(EPOCH FROM now()) NOT NULL, -- (indica a ultima atualizacao no registro)
-- (tabela com tarefas para os staffs. Dentre elas podemos citar baloes e impressoes)
CONSTRAINT \"task_pkey\" PRIMARY KEY (\"contestnumber\", \"sitenumber\", \"tasknumber\"),
CONSTRAINT \"user_fk\" FOREIGN KEY (\"contestnumber\", \"sitenumber\", \"usernumber\")
	REFERENCES \"usertable\" (\"contestnumber\", \"usersitenumber\", \"usernumber\")
	ON DELETE CASCADE ON UPDATE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
)", "DBCreateTaskTable(create table)");
         $r = DBExec($c, "REVOKE ALL ON \"tasktable\" FROM PUBLIC", "DBCreateTaskTable(revoke public)");
	 $r = DBExec($c, "GRANT ALL ON \"tasktable\" TO \"".$conf["dbuser"]."\"", "DBCreateTaskTable(grant bocauser)");
	 $r = DBExec($c, "CREATE UNIQUE INDEX \"task_index\" ON \"tasktable\" USING btree ".
	      "(\"contestnumber\" int4_ops, \"sitenumber\" int4_ops, \"tasknumber\" int4_ops)",
	      "DBCreateTaskTable(create index)");
}

function DBChiefUpdateTask($contest, $usersite, $usernumber, $tasksite, $tasknumber, $st) {
	return DBUpdateTaskC($contest, $usersite, $usernumber, $tasksite, $tasknumber, $st, 1);
}
function DBUpdateTask($contest, $usersite, $usernumber, $tasksite, $tasknumber, $st) {
	return DBUpdateTaskC($contest, $usersite, $usernumber, $tasksite, $tasknumber, $st, 0);
}
function DBUpdateTaskC($contest, $usersite, $usernumber, $tasksite, $tasknumber, $status, $chief) {
	$b = DBSiteInfo($contest, $tasksite);
	if ($b == null) {
		exit;
	}

	$c = DBConnect();
	DBExec($c, "begin work", "DBUpdateTaskC(transaction)");
	$sql = "select * from tasktable as t where t.contestnumber=$contest and " .
		"t.sitenumber=$tasksite and t.tasknumber=$tasknumber";
	if ($chief != 1) {
		$sql .= " and t.taskstatus='processing' and t.taskstaffnumber=$usernumber and t.taskstaffsite=$usersite";
		$tx = "Judge";
	} else $tx = "Chief";
	$r = DBExec ($c, $sql . " for update", "DBUpdateTaskC(get task for update)");
	$n = DBnlines($r);
	if ($n != 1) {
		DBExec($c, "rollback work", "DBUpdateTaskC(rollback)");
		LogLevel("Unable to alter the task record (maybe it was already done by a chief) " .
			"(task=$tasknumber, site=$tasksite, contest=$contest).",1);
		MSGError("Unable to alter the task record (maybe it was already done by a chief)");
		return false;
	}
	$temp = DBRow($r,0);
	$b = DBSiteInfo($contest, $tasksite, $c);
	$t = $b["currenttime"];

	DBExec($c, "update tasktable set taskstatus='$status', taskstaffnumber=$usernumber, taskstaffsite=$usersite, " . 
		"taskdatediffans=$t, updatetime=".time()." " .
		"where contestnumber=$contest and tasknumber=$tasknumber and sitenumber=$tasksite",
               "DBUpdateTaskC(update task)");

	DBExec($c, "commit work", "DBUpdateTaskC(commit)");
	LOGLevel("Task updated (task=$tasknumber, site=$tasksite, contest=$contest, user=$usernumber(site=$usersite), ".
		"status=$status).", 3);
	return true;
}
//devolve uma task que estava sendo processada. Recebe o numero da task, 
//o numero do site da task e o numero do contest.
//tenta alterar o status para 'opentask'. Se nao conseguir retorna false
function DBChiefTaskGiveUp($number,$site,$contest) {
	return DBTaskGiveUp($number,$site,$contest,-1,-1);
}
function DBTaskGiveUp($number,$site,$contest,$usernumber,$usersite) {
	$c = DBConnect();
	DBExec($c, "begin work", "DBTaskGiveUp(transaction)");
	$sql = "select * from tasktable as t where t.contestnumber=$contest and " .
		 "t.sitenumber=$site and t.tasknumber=$number";
	if ($usernumber != -1 || $usersite != -1) {
		$sql .= " and t.taskstatus='processing' and t.taskstaffnumber=$usernumber and taskstaffsite=$usersite";
		$tx = "Staff";
	} else $tx = "Chief";
	$r = DBExec ($c, $sql . " for update", "DBTaskGiveUp(get task for update)");
	$n = DBnlines($r);
	if ($n != 1) {
		DBExec($c, "rollback work", "DBTaskGiveUp(rollback)");
		LogLevel("Unable to return a task (maybe the timeout or a chief returned it first). ".
			"(task=$number, site=$site, contest=$contest)",1);
		return false;
	}

	DBExec($c, "update tasktable set taskstatus='opentask', taskstaffnumber=NULL, taskstaffsite=NULL, ".
		   "updatetime=" .time(). " ".
		   "where contestnumber=$contest and tasknumber=$number and sitenumber=$site",
	       "DBTaskGiveUp(update task)");

	DBExec($c, "commit work", "DBTaskGiveUp(commit)");
	LOGLevel("Task returned (task=$number, site=$site, contest=$contest, user=$usernumber).", 3);
	return true;
}
function DBTaskDelete($number,$site,$contest,$user,$usersite) {
	$c = DBConnect();
	DBExec($c, "begin work", "DBTaskDelete(transaction)");
	$sql = "select * from tasktable as t where t.contestnumber=$contest and " .
		 "t.sitenumber=$site and t.tasknumber=$number";
	$r = DBExec ($c, $sql . " for update", "DBTaskDelete(get task for update)");
	$n = DBnlines($r);
	if ($n != 1) {
		DBExec($c, "rollback work", "DBTaskDelete(rollback)");
		LogLevel("Unable to delete a task. ".
			"(task=$number, site=$site, contest=$contest)",1);
		return false;
	}

	DBExec($c, "update tasktable set taskstatus='deleted', taskstaffnumber=$user, taskstaffsite=$usersite, ".
		   "updatetime=" . time()." ".
		   "where contestnumber=$contest and tasknumber=$number and sitenumber=$site",
		"DBTaskDelete(update task)");

	DBExec($c, "commit work", "DBTaskDelete(commit)");
	LOGLevel("Task deleted (task=$number, site=$site, contest=$contest, user=$user($usersite)).", 3);
	return true;
}

//pega uma task para processar. Recebe o numero da task, o numero do site e o numero do contest.
//tenta alterar o status para 'processing' e se conseguir, devolve um array com dados da task. 
//Se nao conseguir, retorna false
function DBChiefGetTaskToAnswer($number,$site,$contest) {
	return DBGetTaskToAnswerC($number,$site,$contest,1);
}
function DBGetTaskToAnswer($number,$site,$contest) {
	return DBGetTaskToAnswerC($number,$site,$contest,0);
}
function DBGetTaskToAnswerC($number,$site,$contest,$chief) {
	$c = DBConnect();
	DBExec($c, "begin work", "DBGetTaskToAnswerC(transaction)");
	$sql = "select t.contestnumber as contestnumber, t.sitenumber as sitenumber, ".
               "t.tasknumber as number ".
                   "from tasktable as t ".
                   "where t.contestnumber=$contest and t.sitenumber=$site and " .
	   	        "t.tasknumber=$number";
	if ($chief != 1) {
		$sql .= " and t.taskstatus='opentask'";
		$tx = "Staff";
	} else $tx = "Chief";
	$r = DBExec ($c, $sql . " for update", "DBGetTaskToAnswerC(get task for update)");
	$n = DBnlines($r);
	if ($n != 1) {
		DBExec($c, "rollback work", "DBGetTaskToAnswerC(rollback)");
		LogLevel("Unable to get a task (maybe other staff got it first). ".
			"(task=$number, site=$site, contest=$contest)",2);
		return false;
	}
	$a = DBRow($r,0);
	if ($chief != 1) {
		DBExec($c, "update tasktable set taskstaffnumber=" . 
			   $_SESSION["usertable"]["usernumber"] . ", ".
			   "taskstaffsite=".$_SESSION["usertable"]["usersitenumber"]. 
			", taskstatus='processing', updatetime=".time()." " .
			"where contestnumber=$contest and tasknumber=$number and ".
			       "sitenumber=$site",
		      "DBGetTaskToAnswerC(update task)");
	}

	DBExec($c, "commit work", "DBGetTaskToAnswerC(commit)");
	LOGLevel("User got a task (task=$number, site=$site, contest=$contest, user=". 
		 $_SESSION["usertable"]["usernumber"]."(".$_SESSION["usertable"]["usersitenumber"].")).", 3);
	return $a;
}
function DBAllTasks($contest) {
	return DBOpenTasksSNS($contest,"x",-1);
}
function DBAllTasksInSites($contest,$site,$order) {
	return DBOpenTasksSNS($contest,$site,-1,$order);
}
function DBOpenTasks($contest) {
	return DBOpenTasksSNS($contest,"x",1);
}
function DBOpenTasksInSites($contest,$site) {
	return DBOpenTasksSNS($contest,$site,1);
}
function DBOpenTasksSNS($contest,$site,$st,$order='task') {
	$c = DBConnect();
	$sql = "select distinct t.tasknumber as number, t.taskdatediff as timestamp, t.usernumber as user, ".
		"u.username as username, t.color as color, t.colorname as colorname, " .
		"t.taskstatus as status, t.sitenumber as site, t.taskstaffnumber as staff, " .
		"t.taskstaffsite as staffsite, t.taskdesc as description, tasksystem as system, " .
		"t.taskfilename as filename, t.taskdata as oid, uu.username as staffname " .
		"from tasktable as t left join usertable as uu on ".
		"uu.usernumber=t.taskstaffnumber and uu.usersitenumber=t.taskstaffsite and ".
		"uu.contestnumber=t.contestnumber, ".
		"usertable as u " .
		"where t.contestnumber=$contest and u.contestnumber=t.contestnumber and ".
		"u.usernumber=t.usernumber and u.usersitenumber=t.sitenumber";
	if ($site != "x") {
	        $str = explode(",", $site);
        	$sql .= " and (t.sitenumber=-1";
	        for ($i=0;$i<count($str);$i++) {
				if (is_numeric($str[$i])) {
					$sql .= " or (t.sitenumber=".$str[$i];
					$b = DBSiteInfo($contest, $str[$i]);
					if ($b == null) {
						exit;
					}
					$t = $b["currenttime"]; 
					$sql .= " and (t.taskdatediffans<=$t or (t.taskstatus != 'done' and t.taskdatediff<=$t))) ";
				}
	        }
        	$sql .= ")";
	}

	if ($st == 1) 
		$sql .= " and (t.taskstatus ~ 'opentask' or t.taskstatus ~ 'processing') order by ";
	else $sql .= " order by ";

        if($order == "description")
                $sql .= "t.taskdesc,";
        else if ($order == "status")
                $sql .= "t.taskstatus,";
        else if ($order == "user")
                $sql .= "u.username,t.sitenumber,";
        else if ($order == "staff")
                $sql .= "t.taskstaffnumber,t.taskstaffsite,";

        if ($st == 1)
                $sql .= "t.tasknumber";
        else
                $sql .= "t.taskdatediff desc";

	$r = DBExec($c, $sql, "DBOpenTasksSNS(get task)");

	$n = DBnlines($r);
	$a = array();
	for ($i=0;$i<$n;$i++)
		$a[$i] = DBRow($r,$i);
	return $a;
}
function DBNewTask_old ($contest, $site, $user, $desc, $filename, $filepath, $sys, $color='', $colorname='', $c=null) {
	$param = array('contest'=>$contest,
				   'site'=>$site,
				   'user'=>$user,
				   'desc'=>$desc,
				   'filename'=>$filename,
				   'filepath'=>$filepath,
				   'color'=>$color,
				   'colorname'=>$colorname,
				   'sys'=>$sys);
	return DBNewTask($param,$c);
}

function DBNewTask($param, $c=null) {
	if(isset($param['contestnumber']) && !isset($param['contest'])) $param['contest']=$param['contestnumber'];
	if(isset($param['sitenumber']) && !isset($param['site'])) $param['site']=$param['sitenumber'];
	if(isset($param['usernumber']) && !isset($param['user'])) $param['user']=$param['usernumber'];
	if(isset($param['number']) && !isset($param['tasknumber'])) $param['tasknumber']=$param['number'];

	$ac=array('contest','site','user','desc');
	$ac1=array('color','colorname','updatetime','filename','filepath','sys','tasknumber','status',
			   'taskdate','taskdatediff','taskdatediffans','taskstaffnumber','taskstaffsite');
	$type['contest']=1;
	$type['updatetime']=1;
	$type['site']=1;
	$type['user']=1;
	$type['tasknumber']=1;
	$type['taskdate']=1;
	$type['taskdatediff']=1;
	$type['taskdatediffans']=1;
	$type['taskstaffnumber']=1;
	$type['taskstaffsite']=1;
	foreach($ac as $key) {
		if(!isset($param[$key]) || $param[$key]=="") {
			MSGError("DBNewTask param error: $key not found");
			return false;
		}
		if(isset($type[$key]) && !is_numeric($param[$key])) {
			MSGError("DBNewTask param error: $key is not numeric");
			return false;
		}
		$$key = sanitizeText($param[$key]);
	}
	$taskstaffnumber=-1;
	$taskstaffsite=-1;
	$t = time();
	$taskdate=$t;
	$sys='f';
	$filename='';
	$filepath='';
	$color=''; 
	$colorname='';
	$tasknumber=-1;
	$taskdatediffans=999999999;
	$updatetime=-1;
	$status='opentask';
	$taskdatediff=-1;
	foreach($ac1 as $key) {
		if(isset($param[$key])) {
			$$key = sanitizeText($param[$key]);
			if(isset($type[$key]) && !is_numeric($param[$key])) {
				MSGError("DBNewTask param error: $key is not numeric");
				return false;
			}
		}
	}
	if($updatetime <= 0)
		$updatetime=$t;
	if($sys != 't') $sys='f';

	$cw = false;
	if($c == null) {
		$cw = true;
		$c = DBConnect();
		DBExec($c, "begin work", "DBNewTask(transaction)");
	}
	$insert=true;
	if($tasknumber < 0) {
		$sql = "select sitenexttask as nexttask, sitemaxtask as maxtask from " .
			"sitetable where sitenumber=$site and contestnumber=$contest for update";
		$r = DBExec($c, $sql, "DBNewTask(get site for update)");
		if (DBnlines($r) != 1) {
			DBExec($c, "rollback work", "DBNewTask(rollback-site)");
			LOGError("Unable to find a unique site/contest in the database. SQL=(" . $sql . ")");
			MSGError("Unable to find a unique site/contest in the database.");
			exit;
		}
		$a = DBRow($r,0);
		$b = DBSiteInfo($contest, $site, $c);
		$dif = $b["currenttime"];
		if($taskdatediff < 0)
			$taskdatediff = $dif;
		if($sys!='t' && DBCountOpenTasks($contest, $site, $user) > $a["maxtask"]) {
			DBExec($c, "rollback work", "DBNewTask(rollback-maxtask)");
			LOGError("Too many open tasks for user=$user, site=$site, contest=$contest");
			MSGError("Too many open tasks! Task not included.");
			exit;
		}
		if ($sys != 't' && $dif < 0) {
			DBExec($c, "rollback work", "DBNewTask(rollback-started)");
			LOGError("Tried to submit a task but the contest is not started. SQL=(" . $sql . ")");
			MSGError("The contest is not started yet!");
			exit;
		}
		if ($sys != 't' && !$b["siterunning"]) {
			DBExec($c, "rollback work", "DBNewTask(rollback-over)");
			LOGError("Tried to submit a task but the contest is over. SQL=(" . $sql . ")");
			MSGError("The contest is over!");
			exit;
		}
		$tasknumber = $a["nexttask"] + 1;
	} else {
		$sql = "select * from tasktable as t where t.contestnumber=$contest and " .
			"t.sitenumber=$site and t.tasknumber=$tasknumber";
		$r = DBExec ($c, $sql . " for update", "DBNewTask(get task for update)");
		$n = DBnlines($r);
		if ($n > 0) {
			$insert=false;
			$lr = DBRow($r,0);
			$t = $lr['updatetime'];
		}
	}
	DBExec($c, "update sitetable set sitenexttask=$tasknumber, updatetime=".$t.
		   " where sitenumber=$site and contestnumber=$contest and sitenexttask<$tasknumber", "DBNewTask(update site)");
	$ret=1;
	if($insert) {
		if($filename!="" && $filepath!="") {
			if(substr($filepath,0,7)!="base64:") {
				if (($oid = DB_lo_import($c, $filepath)) === false) {
					DBExec($c, "rollback work", "DBNewTask(rollback-import)");
					LOGError("DBNewTask: Unable to create a large object for file $filepath.");
					MSGError("problem importing file to database. Contact an admin now!");
					exit;
				}
			} else {
				$filepath = base64_decode(substr($filepath,7));
				if (($oid = DB_lo_import_text($c, $filepath)) == null) {
					DBExec($c, "rollback work", "DBNewTask(rollback-import)");
					LOGError("DBNewTask: Unable to create a large object for file.");
					MSGError("problem importing file to database. Contact an admin now!");
					exit;
				}
			}
		} else $oid="NULL";
		DBExec($c, "INSERT INTO tasktable (contestnumber, sitenumber, tasknumber, usernumber, taskdate, " .
			   "taskdatediff, taskdatediffans, taskfilename, taskdata, taskstatus, taskdesc, tasksystem, ".
			   "color, colorname, updatetime) " . 
			   "VALUES ($contest, $site, $tasknumber, $user, $taskdate, $taskdatediff, $taskdatediffans, '$filename', $oid, '$status', " .
			   "'$desc', '$sys', '$color', '$colorname', $updatetime)",
			   "DBNewTask(insert task)");
              if($sys=="t") $u="System";
                else $u = "User $user";
  
		if($cw) {
			DBExec($c, "commit work", "DBNewTask(commit-insert)");
			LOGLevel("$u submitted a task (#$tasknumber) on site #$site " .
					 "(filename=$filename, contest=$contest).",2);
		}
		$ret=2;
	} else {
		if($updatetime > $t) {
			$ret=2;
			$sql = "update tasktable set usernumber=$user, taskdesc='$desc', " .
				"color='$color',colorname='$colorname',taskstatus='$status',";
			if($taskstaffnumber>0)
				$sql .= "taskstaffnumber=$taskstaffnumber, ";
			if($taskstaffsite>0)
				$sql .= "taskstaffsite=$taskstaffsite, ";
			$sql .= "taskdatediffans=$taskdatediffans, updatetime=$updatetime where " .
				"contestnumber=$contest and sitenumber=$site and tasknumber=$tasknumber";
			 DBExec($c,$sql,"DBNewTask(update task)");
		}
		if($cw) DBExec($c, "commit work", "DBNewTask(commit-update)");
	}
	return $ret;
}
//recebe o numero do contest, o numero do site e o numero do usuario
//devolve um array, onde cada linha tem os atributos
//  number (numero da task)
//  timestamp (hora da criacao da task)
//  filename (nome do arquivo relacionado)
//  status (situacao da task)
//  desc (descricao da task)
function DBUserTasks($contest,$site,$user) {
	$b = DBSiteInfo($contest, $site);
	if ($b == null) {
		exit;
	}
	$t = $b["currenttime"]; 

	$c = DBConnect();
	$r = DBExec($c, "select distinct t.sitenumber as site, t.tasknumber as number, ".
				"t.taskdatediff as timestamp, t.taskstatus as status, t.taskfilename as filename, " .
				"t.taskdesc as description, t.updatetime " .
				"from tasktable as t " .
				"where t.contestnumber=$contest and t.sitenumber=$site and ".
				"(t.taskdatediffans<=$t or (t.taskstatus != 'done' and t.taskdatediff<=$t)) and " .
				"t.usernumber=$user and t.taskstatus != 'deleted' and t.tasksystem='f' " .
				"order by t.updatetime",
				"DBUserTasks(get tasks)");
	$n = DBnlines($r);

	$a = array();
	for ($i=0;$i<$n;$i++) {
		$a[$i] = DBRow($r,$i);
	}
	return $a;
}
function DBCountOpenTasks($contest,$site,$user) {
	$c = DBConnect();
	$r = DBExec($c, "select t.contestnumber, t.sitenumber, t.tasknumber ".
                             "from tasktable as t " .
                             "where t.contestnumber=$contest and t.sitenumber=$site and ".
			     "t.usernumber=$user and t.taskstatus='opentask' and t.tasksystem='f'",
		   "DBCountOpenTasks(get tasks)");
	$n = DBnlines($r);
	return $n;
}
//recebe o numero do contest, o numero do site do juiz e o numero do juiz
//devolve um array, onde cada linha tem os atributos
//  number (numero da task)
//  timestamp (hora da criacao da task)
//  problem (nome do problema)
//  status (situacao da task)
//  answer (texto com a resposta)
function DBJudgedTasks($contest,$site,$user) {
	$c = DBConnect();
	$r = DBExec($c, "select distinct t.sitenumber as site, t.tasknumber as number, ".
			"t.taskdatediff as timestamp, t.taskstatus as status, t.taskfilename as filename, " .
                        "t.taskdesc as description, t.updatetime " .
                             "from tasktable as t " .
                             "where t.contestnumber=$contest and t.taskstaffsite=$site and ".
			     "t.taskstaffnumber=$user order by t.updatetime",
		   "DBJudgedTasks(get tasks)");
	$n = DBnlines($r);

	$a = array();
	for ($i=0;$i<$n;$i++) {
		$a[$i] = DBRow($r,$i);
	}
	return $a;
}
// eof
?>
