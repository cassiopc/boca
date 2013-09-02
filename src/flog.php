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

function DBDropLogTable() {
         $c = DBConnect();
         $r = DBExec($c, "drop table \"logtable\"", "DBDropLogTable(drop table)");
}
function DBCreateLogTable() {
         $c = DBConnect();
	 $conf = globalconf();
	 if($conf["dbuser"]=="") $conf["dbuser"]="bocauser";
         $r = DBExec($c, "
CREATE TABLE \"logtable\" (
        \"lognumber\" serial,                     -- (serial para o log)
        \"contestnumber\" int4 NOT NULL,          -- (id do concurso)
        \"sitenumber\" int4 NOT NULL,             -- (id do site local)
        \"loguser\" int4,                         -- (usuario envolvido com o log)
        \"logip\" varchar(20) NOT NULL,           -- (numero do site do usuario envolvido)
        \"logdate\" int4 NOT NULL,                -- (dia/hora da criacao deste registro)
        \"logtype\" varchar(20) NOT NULL,         -- (tipo de registro: error, warn, info, debug)
        \"logdata\" text NOT NULL,                -- (descricao do registro)
        \"logstatus\" varchar(20) DEFAULT '',     -- (status do registro)
        CONSTRAINT \"log_pkey\" PRIMARY KEY (\"lognumber\"),
        CONSTRAINT \"site_fk\" FOREIGN KEY (\"contestnumber\", \"sitenumber\")
                REFERENCES \"sitetable\" (\"contestnumber\", \"sitenumber\")
                ON DELETE CASCADE ON UPDATE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE,
        CONSTRAINT \"loguser\" FOREIGN KEY (\"contestnumber\", \"loguser\", \"sitenumber\")
                REFERENCES \"usertable\" (\"contestnumber\", \"usernumber\", \"usersitenumber\")
                ON DELETE CASCADE ON UPDATE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
)", "DBCreateLogTable(create table)");
        $r = DBExec($c, "REVOKE ALL ON \"logtable\" FROM PUBLIC", "DBCreateLogTable(revoke public)");
	$r = DBExec($c, "GRANT INSERT, SELECT ON \"logtable\" TO \"".$conf["dbuser"]."\"", "DBCreateLogTable(grant bocauser)");
	$r = DBExec($c, "CREATE INDEX \"log_index\" ON \"logtable\" USING btree ".
	     "(\"contestnumber\" int4_ops, \"sitenumber\" int4_ops, \"logdate\" int4_ops)", 
	     "DBCreateLogTable(create log_index)");
	$r = DBExec($c, "CREATE INDEX \"log_index2\" ON \"logtable\" USING btree ".
	     "(\"contestnumber\" int4_ops, \"loguser\" int4_ops, \"sitenumber\" int4_ops)",
	     "DBCreateLogTable(create log_index2)");
	$r = DBExec($c, "REVOKE ALL ON \"logtable_lognumber_seq\" FROM PUBLIC", "DBCreateLogTable(revoke public seq)");
	$r = DBExec($c, "GRANT ALL ON \"logtable_lognumber_seq\" TO \"".$conf["dbuser"]."\"", "DBCreateLogTable(grant bocauser seq)");
}

////////////////////funcoes para logar////////////////////////////////////////////////////////////////
function DBNewLog($contest, $site, $user, $type, $ip, $data, $status) {
	$t = time();
	$data = str_replace("'", "\"", $data);
	DBExecNoSQLLog ("insert into logtable (contestnumber, sitenumber, loguser, logdate, logtype, " .
        "logip, logdata, logstatus) values ($contest, $site, $user, $t, '$type', '$ip', '$data', '$status')",
	"DBNewLog(insert log)");
}
function DBGetLogs($o, $contest, $site, $user, $type, $ip, $limit) {
	$c = DBConnect();
	$where = "";
	if ($site != "") $where .= "sitenumber=$site and ";
	if ($user != "") $where .= "loguser=$user and ";
	if ($type != "") $where .= "logtype='$type' and ";
	if ($ip != "") $where .= "logip='$ip' and ";
	$where .= "contestnumber=$contest";
	switch ($o) {
		case "user": $order="contestnumber, sitenumber, loguser, logdate desc"; break;
		case "type": $order="contestnumber, sitenumber, logtype, logdate desc"; break;
		case "ip": $order="contestnumber, sitenumber, logip, logdate desc"; break;
		default: $order="contestnumber, sitenumber, logdate desc"; break;
	}
	$r = DBExec ($c, "select contestnumber as contest, sitenumber as site, loguser as user, logdate as date, " .
			"logtype as type, logip as ip, logdata as data, logstatus as status from logtable " .
			" where $where order by $order limit $limit", "DBGetLogs(get logs)");
	$n = DBnlines($r);
	$a = array();
	for ($i=0;$i<$n;$i++)
		$a[$i] = DBRow($r,$i);
	return $a;
}

//funcao para fazer o login de um usuario. Vai procurar por um contest ativo, verificar qual o site
//local, e entao procurar pelo usuario no site local do contest ativo. Alem disso, verifica outras
//flags, como logins habilitados, ip correto, se usuario ja esta logado, etc
//$name eh o nome do usuario
//$pass eh o password
function DBLogIn($name,$pass, $msg=true) {
	$b = DBGetRow("select * from contesttable where contestnumber=0", 0, null, "DBLogIn(get template contest)");
	if ($b != null) {
		$r = DBLogInContest($name, $pass, $b["contestnumber"], false);
		if($r !== false) return $r;
	}
	$b = DBGetRow("select * from contesttable where contestactive=true", 0, null, "DBLogIn(get active contest)");
	if ($b != null) {
		$r = DBLogInContest($name, $pass, $b["contestnumber"], $msg);
		if ($r !== false) return $r;
	} else {
		LOGLevel("There is no active or template contest.",0);
		MSGError("There is no active or template contest.");
	}
	return false;
}
function DBLogInContest($name,$pass,$contest,$msg=true) {
	$b = DBGetRow("select * from contesttable where contestnumber=$contest", 0, null, "DBLogIn(get active contest)");
	if ($b == null) {
		LOGLevel("There is no contest $contest.",0);
		if($msg) MSGError("There is no contest $contest, contact an admin.");
		return false;
	}
	$d = DBSiteInfo($b["contestnumber"], $b["contestlocalsite"],null,false);
	if ($d == null) {
		if($msg) MSGError("There is no active site, contact an admin.");
		return false;
	}
	$a = DBGetRow("select * from usertable where username='$name' and contestnumber=".
		$b["contestnumber"]." and " .
 	    "usersitenumber=".$b["contestlocalsite"], 0, null, "DBLogIn(get user)");
	if ($a == null) {
		if($msg) {
			LOGLevel("User $name tried to log in contest $contest but it does not exist.",2);
			MSGError("User does not exist or incorrect password.");
		}
		return false;
	}
	$a = DBUserInfo($b["contestnumber"], $b["contestlocalsite"],$a['usernumber'],null,false);
	$_SESSION['usertable'] = $a;
	$p = myhash($a["userpassword"] . session_id());
	$_SESSION['usertable']['userpassword'] = $p;
	if ($a["userpassword"] != "" && $p != $pass) {
		LOGLevel("User $name tried to log in contest $contest but password was incorrect.",2);
		if($msg) MSGError("Incorrect password.");
		unset($_SESSION["usertable"]);
		return false;
	}
	if ($d["sitepermitlogins"]=="f" && $a["usertype"] != "admin" && $a["usertype"] != "judge" && $a["usertype"] != "site") {
		LOGLevel("User $name tried to login contest $contest but logins are denied.",2);
		if($msg) MSGError("Logins are not allowed.");
		unset($_SESSION["usertable"]);
		return false;
	}
	if ($a["userenabled"] != "t") {
		LOGLevel("User $name tried to log in contest $contest but it is disabled.",2);
		if($msg) MSGError("User disabled.");
		unset($_SESSION["usertable"]);
		return false;
	}
	$gip=getIP();
	if ($a["userip"] != $gip && $a["userip"] != "" && $a["usertype"] != "score") {
		LOGLevel("User $name is using two different IPs: " . $a["userip"] . 
			 "(" . dateconv($a["userlastlogin"]) .") and " . $gip,1);
		if($msg && $a["usertype"] != "admin") MSGError("You are using two distinct IPs. Admin notified.");
	}
	if ($a["userpermitip"] != "") {
		$ips=explode(';',$a["userpermitip"]);
		$gips=explode(';',$gip);
		if(count($gips) < count($ips)) {
			IntrusionNotify("Invalid IP: " . $gip);
			ForceLoad("index.php");
		}
		for($ipss=0;$ipss<count($ips);$ipss++) {
			$gipi=$gips[$ipss];
			$ipi=$ips[$ipss];
			if(!match_network($ipi, $gipi)) {
				IntrusionNotify("Invalid IP: " . $gip);
				ForceLoad("index.php");
			}
		}
	}
	$c = DBConnect();
	$t = time();
	if($a["usertype"] == "team" && $a["usermultilogin"] != "t" && $a["userpermitip"] == "") {
	  $r = DBExec($c,"update usertable set userip='" . $gip . "', updatetime=" . time() . ", userpermitip='" . $gip . "'," .
		"userlastlogin=$t, usersession='".session_id()."' where username='$name' and contestnumber=".
					$b["contestnumber"]." and usersitenumber=".$b["contestlocalsite"], "DBLogIn(update session)");
	} else {
		DBExec($c,"begin work");
		$sql = "update usertable set usersessionextra='".session_id()."' where username='$name' and contestnumber=".
			$b["contestnumber"]." and usersitenumber=".$b["contestlocalsite"] .
			" and (usersessionextra='' or userip != '" . $gip ."' or userlastlogin<=" . ($t-86400) . ")";
		DBExec($c,$sql);

		DBExec($c,"update usertable set userip='" . $gip . "', updatetime=" . time() . ", userlastlogin=$t, ". 
			   "usersession='".session_id()."' where username='$name' and contestnumber=".
			   $b["contestnumber"]." and usersitenumber=".$b["contestlocalsite"], "DBLogIn(update user)");
		if($name=='admin') {
			list($clockstr,$clocktime)=siteclock();
			if($clocktime < -600)
				DBExec($c,"update contesttable set contestunlockkey='' where contestnumber=" . $b["contestnumber"], "DBLogInContest(update contest)");
		}
		DBExec($c,"commit work");
	}
	LOGLevel("User $name authenticated (" . $gip . ")",2);

	return $a;
}
//faz o logout. Note que o timestamp de logout fica sem sentido quando o usuario
//eh do tipo multilogin
function DBLogOut($contest, $site, $user, $isadmin=false) {
	$c = DBConnect();
	$r = DBExec($c,"update usertable set usersession='',usersessionextra='', updatetime=".time().", " .
		"userlastlogout=".time()." where usernumber=$user and " .
			"contestnumber=$contest and usersitenumber=$site", "DBLogOut(update user)");
	if($isadmin) {
		list($clockstr,$clocktime)=siteclock();
		if($clocktime < -600) {
			DBExec($c,"update contesttable set contestunlockkey='' where contestnumber=$contest", "DBLogOut(update contest)");		
			DBExec($c,"update problemtable set problemfullname='', problembasefilename='' where problemfullname !~ '(DEL)' and contestnumber=$contest", "DBLogOut(update problems)");

			$ds = DIRECTORY_SEPARATOR;
			if($ds=="") $ds = "/";
			$dir=$_SESSION["locr"] . $ds . "private" . $ds . "problemtmp" . $ds;
			foreach(glob($dir . '*') as $file) {
				cleardir($file,false,true);
			}
		}
	}
	LOGLevel("User $user (contest=$contest,site=$site) logged out.",2);
}
// eof
?>
