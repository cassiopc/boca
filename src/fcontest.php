<?php
////////////////////////////////////////////////////////////////////////////////
//BOCA Online Contest Administrator
//    Copyright (C) 2003-2014 by BOCA System (bocasystem@gmail.com)
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
//Last updated 26/oct/2014 by cassio@ime.usp.br
//        inclusion of default extra language C++11
//
function DBDropContestTable() {
	 $c = DBConnect();
	 $r = DBExec($c, "drop table \"contesttable\"", "DBDropContestTable(drop table)");
}
function DBCreateContestTable() {
	 $c = DBConnect();
	 $conf = globalconf();
	 if($conf["dbuser"]=="") $conf["dbuser"]="bocauser";
	 $r = DBExec($c, "
CREATE TABLE \"contesttable\" (
        \"contestnumber\" int4 NOT NULL,                  -- (id do concurso)
        \"contestname\" varchar(100) NOT NULL,             -- (nome do concurso)
        \"conteststartdate\" int4 NOT NULL,               -- (dia/horario de inicio)
        \"contestduration\" int4 NOT NULL,                -- (duracao em segundos do contest)
        \"contestlastmileanswer\" int4,                   -- (qtd segundos a partir do inicio para nao responder aos times)
        \"contestlastmilescore\" int4,                    -- (qtd segundos a partir do inicio para nao atualizar placar)
        \"contestlocalsite\" int4 NOT NULL,               -- (id do site local com relacao a este servidor)
        \"contestpenalty\" int4 NOT NULL,                 -- (qtd de segundos perdidos para cada run errada)
        \"contestmaxfilesize\" int4 NOT NULL,             -- (tamanho max em bytes dos codigos que podem ser submetidos)
        \"contestactive\" bool NOT NULL,                  -- (indica se o contest esta ativo)
        \"contestmainsite\" int4 NOT NULL,                -- (id do site principal com relacao ao contest)
        \"contestkeys\" text NOT NULL,         -- (list of keys relevant to the contest)
        \"contestunlockkey\" varchar(100) NOT NULL,         -- (key to decrypt problem files)
        \"contestmainsiteurl\" varchar(200) NOT NULL,         -- (id do site principal com relacao ao contest)
        \"updatetime\" int4 DEFAULT EXTRACT(EPOCH FROM now()) NOT NULL, -- (indica a ultima atualizacao no registro)
-- (esta tabela eh igual em todos os sites, com excecao do campo contestlocalsite,
-- que guarda qual o site local. Na verdade a ideia eh que todas as tabelas sejam
-- iguais em todos os sites.)
        CONSTRAINT \"contest_pkey\" PRIMARY KEY (\"contestnumber\")
)", "DBCreateContestTable(create table)");
         $r = DBExec($c, "REVOKE ALL ON \"contesttable\" FROM PUBLIC", "DBCreateContestTable(revoke public)");
	 $r = DBExec($c, "GRANT ALL ON \"contesttable\" TO \"".$conf["dbuser"]."\"", "DBCreateContestTable(grant bocauser)");
         $r = DBExec($c, "CREATE UNIQUE INDEX \"contestnumber_index\" ON \"contesttable\" USING btree ".
			     "(\"contestnumber\" int4_ops)", "DBCreateContestTable(create index)");
}
function DBDropSiteTable() {
	 $c = DBConnect();
	 $r = DBExec($c, "drop table \"sitetable\"", "DBDropSiteTable(drop table)");
}
function DBCreateSiteTable() {
	 $c = DBConnect();
	 $conf = globalconf();
	 if($conf["dbuser"]=="") $conf["dbuser"]="bocauser";
	 $r = DBExec($c, "
CREATE TABLE \"sitetable\" (
        \"contestnumber\" int4 NOT NULL,                  -- (id do concurso)
        \"sitenumber\" int4 NOT NULL,                     -- (id do local)
        \"siteip\" varchar(200) NOT NULL,                  -- (ip publico do servidor do site)
        \"sitename\" varchar(50) NOT NULL,                -- (nome do local)
        \"siteactive\" bool NOT NULL,                     -- (site ativo?)
        \"sitepermitlogins\" bool NOT NULL,               -- (logins estao aceitos?)
        \"sitelastmileanswer\" int4,         -- (hora (em seg do inicio) que este site para de responder aos times)
        \"sitelastmilescore\" int4,                       -- (hora (em seg do inicio) que o placar eh congelado neste site)
        \"siteduration\" int4,                            -- (tamanho da competicao em segundos)
        \"siteautoend\" bool,                             -- (?)
        \"sitejudging\" text,                             -- (indica quais sites sao julgados neste site)
        \"sitetasking\" text,                             -- (indica quais sites sao processadas as tasks neste site)
        \"siteglobalscore\" varchar(50) DEFAULT '' NOT NULL,    -- (indica se este site deve mostrar placar global)
        \"sitescorelevel\" int4 DEFAULT 0 NOT NULL,       -- (indica o nivel de detalhes do placar exibido aos times)
        \"sitenextuser\" int4 DEFAULT 0 NOT NULL,
        \"sitenextclar\" int4 DEFAULT 0 NOT NULL,
        \"sitenextrun\" int4 DEFAULT 0 NOT NULL,
        \"sitenexttask\" int4 DEFAULT 0 NOT NULL,
        \"sitemaxtask\" int4 DEFAULT 8 NOT NULL,
        \"updatetime\" int4 DEFAULT EXTRACT(EPOCH FROM now()) NOT NULL, -- (indica a ultima atualizacao no registro)
        \"sitechiefname\" varchar(20) DEFAULT '' NOT NULL,  -- (username do juiz chefe, se existir)
-- (esta tabela contem uma linha para cada site do contest)
 	    \"siteautojudge\" bool DEFAULT 'f',
        \"sitemaxruntime\" int4 DEFAULT 600 NOT NULL,
        \"sitemaxjudgewaittime\" int4 DEFAULT 900 NOT NULL,
        CONSTRAINT \"site_pkey\" PRIMARY KEY (\"contestnumber\", \"sitenumber\"),
        CONSTRAINT \"contest_fk\" FOREIGN KEY (\"contestnumber\") REFERENCES \"contesttable\" (\"contestnumber\")
                ON DELETE CASCADE ON UPDATE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
)", "DBCreateSiteTable(create table)");
        $r = DBExec($c, "REVOKE ALL ON \"sitetable\" FROM PUBLIC", "DBCreateSiteTable(revoke public)");
	$r = DBExec($c, "GRANT ALL ON \"sitetable\" TO \"".$conf["dbuser"]."\"", "DBCreateSiteTable(grant bocauser)");
	$r = DBexec($c, "CREATE UNIQUE INDEX \"site_index\" ON \"sitetable\" USING btree ".
	            "(\"contestnumber\" int4_ops, \"sitenumber\" int4_ops)", "DBCreateSiteTable(create index)");
}
function DBDropSiteTimeTable() {
	 $c = DBConnect();
	 $r = DBExec($c, "drop table \"sitetimetable\"", "DBDropSiteTimeTable(drop table)");
}
function DBCreateSiteTimeTable() {
	 $c = DBConnect();
	 $conf = globalconf();
	 if($conf["dbuser"]=="") $conf["dbuser"]="bocauser";
	 $r = DBExec($c, "
CREATE TABLE \"sitetimetable\" (
        \"contestnumber\" int4 NOT NULL,                  -- (id do concurso)
        \"sitenumber\" int4 NOT NULL,                     -- (id do local)
        \"sitestartdate\" int4 NOT NULL,                  -- (hora que o local comecou)
        \"siteenddate\" int4 NOT NULL,                    -- (hora que o local deve terminar, zero nao terminado)
        \"updatetime\" int4 DEFAULT EXTRACT(EPOCH FROM now()) NOT NULL, -- (indica a ultima atualizacao no registro)
-- (esta tabela contem uma linha para cada reinicio de um site do contest)
        CONSTRAINT \"sitetime_pkey\" PRIMARY KEY (\"contestnumber\", \"sitenumber\", \"sitestartdate\"),
        CONSTRAINT \"site_fk\" FOREIGN KEY (\"contestnumber\", \"sitenumber\")
                REFERENCES \"sitetable\" (\"contestnumber\", \"sitenumber\")
                ON DELETE CASCADE ON UPDATE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
)", "DBCreateSiteTimeTable(create table)");
        $r = DBExec($c, "REVOKE ALL ON \"sitetimetable\" FROM PUBLIC", "DBCreateSiteTimeTable(revoke public)");
	$r = DBExec($c, "GRANT ALL ON \"sitetimetable\" TO \"".$conf["dbuser"]."\"", "DBCreateSiteTimeTable(grant bocauser)");
	$r = DBExec($c, "CREATE UNIQUE INDEX \"sitetime_index\" ON \"sitetimetable\" USING btree ".
	     "(\"contestnumber\" int4_ops, \"sitenumber\" int4_ops, \"sitestartdate\" int4_ops)", 
	     "DBCreateSiteTimeTable(create index)");
	$r = DBexec($c, "CREATE INDEX \"sitetimesite_index\" ON \"sitetimetable\" USING btree ".
	            "(\"contestnumber\" int4_ops, \"sitenumber\" int4_ops)", "DBCreateSiteTimeTable(create site_index)");
}
function DBDropUserTable() {
	 $c = DBConnect();
	 $r = DBExec($c, "drop table \"usertable\"", "DBDropUserTable(drop table)");
}
function DBCreateUserTable() {
	 $c = DBConnect();
	 $conf = globalconf();
	 if($conf["dbuser"]=="") $conf["dbuser"]="bocauser";
	 $r = DBExec($c, "
CREATE TABLE \"usertable\" (
        \"contestnumber\" int4 NOT NULL,                  -- (id do concurso)
        \"usersitenumber\" int4 NOT NULL,                 -- (id do local do time)
        \"usernumber\" int4 NOT NULL,                     -- (id do usuario)
        \"username\" varchar(20) NOT NULL,                -- (nome do usuario)
        \"userfullname\" varchar(200) NOT NULL,           -- (nome completo do usuario)
        \"userdesc\" varchar(300),                        -- (descricao: escola ou integrantes ou etc)
        \"usertype\" varchar(20) NOT NULL,                -- (judge, team, admin, system)
        \"userenabled\" bool DEFAULT 't' NOT NULL,        -- (usuario ativo)
        \"usermultilogin\" bool DEFAULT 'f' NOT NULL,     -- (usuario pode se logar multiplas vezes)
        \"userpassword\" varchar(200) DEFAULT '',          -- (senha)
        \"userip\" varchar(300),                           -- (ip do ult acesso)
        \"userlastlogin\" int4,                           -- (data em seg desde epoch do ult login)
        \"usersession\" varchar(50) DEFAULT '',           -- (sessao do usuario)
        \"usersessionextra\" varchar(50) DEFAULT '',      -- (sessao do usuario)
        \"userlastlogout\" int4,                          -- (data em seg desde epoch do ult logout)
        \"userpermitip\" varchar(300),                     -- (ip permitido para acesso)
        \"userinfo\" varchar(300) DEFAULT '',
        \"updatetime\" int4 DEFAULT EXTRACT(EPOCH FROM now()) NOT NULL, -- (indica a ultima atualizacao no registro)
-- (esta tabela contem uma linha para cada usuario, seja ele administrador, juiz ou time. )
	    \"usericpcid\" varchar(50) DEFAULT '',		-- (compatibilidade com dados do ICPC)
        CONSTRAINT \"user_pkey\" PRIMARY KEY (\"contestnumber\", \"usersitenumber\", \"usernumber\"),
        CONSTRAINT \"site_fk\" FOREIGN KEY (\"contestnumber\", \"usersitenumber\")
                REFERENCES \"sitetable\" (\"contestnumber\", \"sitenumber\")
                ON DELETE CASCADE ON UPDATE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
)", "DBCreateUserTable(create table)");
	$r = DBExec($c, "REVOKE ALL ON \"usertable\" FROM PUBLIC", "DBCreateUserTable(revoke public)");
	$r = DBExec($c, "GRANT ALL ON \"usertable\" TO \"".$conf["dbuser"]."\"", "DBCreateUserTable(grant bocauser)");
	$r = DBExec($c, "CREATE UNIQUE INDEX \"user_index\" ON \"usertable\" USING btree ".
	     "(\"contestnumber\" int4_ops, \"usersitenumber\" int4_ops, \"usernumber\" int4_ops)", 
	     "DBCreateUserTable(create user_index)");
	$r = DBExec($c, "CREATE UNIQUE INDEX \"user_index2\" ON \"usertable\" USING btree ".
	     "(\"contestnumber\" int4_ops, \"usersitenumber\" int4_ops, \"username\" varchar_ops)",
	     "DBCreateUserTable(create user_index2)");
}

//////////////////////////////funcoes de usuarios/sites/contests///////////////////////////////////////
function DBFakeContest() {
	$c = DBConnect();
	DBExec($c, "begin work");
	DBExec($c, "insert into contesttable (contestnumber, contestname, conteststartdate, contestduration, ".
		"contestlastmileanswer, contestlastmilescore, contestlocalsite, contestpenalty, contestmaxfilesize, ".
		"contestactive, contestmainsite, contestmainsiteurl,contestkeys,contestunlockkey) " .
		"values (0, 'Fake contest (just for initial purposes)', ".
           "EXTRACT(EPOCH FROM now()), ".
           "0, 0, 0, 1, 20*60, 100000, 't', 1, '', '', '')", "DBFakeContest(insert contest)");

	DBExec($c, "insert into sitetable (contestnumber, sitenumber, siteip, sitename, siteactive, sitepermitlogins, ".
		"sitelastmileanswer, sitelastmilescore, siteduration, siteautoend, sitejudging, sitetasking, ".
		"siteglobalscore, sitescorelevel) ".
		"values (0, 1, '', 'Fake Site (just for initial purposes)', ".
           "'t', 't', 0, 0, 1, 't', '1', ".
           "'1', '0', 4)", "DBFakeContest(insert site)");

	$param['contest']=0;
	$param['site']=1;
	$param['start']=1;
	DBRenewSiteTime($param, $c);
	$cf = globalconf();
	$pass = myhash($cf["basepass"]);
	DBExec($c, "insert into usertable (contestnumber, usersitenumber, usernumber, username, userfullname, ".
		"userdesc, usertype, userenabled, usermultilogin, userpassword, userip, userlastlogin, usersession, ".
		"userlastlogout, userpermitip) ".
		"values (0, 1, 1, 'system', 'Systems', NULL, 'system', 't', ".
           "'t', '$pass', NULL, NULL, '', NULL, NULL)", "DBFakeContest(insert system user)");
	DBExec($c, "commit work");
}
function DBAllUserInfo($contest,$site=-1) {
	$sql = "select * from usertable where contestnumber=$contest ";
	if($site > 0) $sql .= "and usersitenumber=$site ";
	$sql .= "order by usersitenumber, usernumber";
	$c = DBConnect();
	$r = DBExec ($c, $sql, "DBAllUserInfo(get users)");
	$n = DBnlines($r);
	if ($n == 0) {
		LOGError("Unable to find users in the database. SQL=(" . $sql . ")");
		MSGError("Unable to find users in the database!");
	}

	$a = array();
	for ($i=0;$i<$n;$i++) {
		$a[$i] = DBRow($r,$i);
		$a[$i]['changepassword']=true;
		if(substr($a[$i]['userpassword'],0,1)=='!') {
			$a[$i]['userpassword'] = substr($a[$i]['userpassword'],1);
			$a[$i]['changepassword']=false;
		}
		$a[$i]['userpassword'] = myhash($a[$i]['userpassword'] . $a[$i]['usersessionextra']);
	}
	return $a;
}
function DBAllSiteTime($contest, $site) {
	$sql = "select * from sitetimetable where contestnumber=$contest and sitenumber=$site order by sitestartdate";
	$c = DBConnect();
	$r = DBExec ($c, $sql, "DBAllSiteTime(get times)");
	$n = DBnlines($r);
	if ($n == 0) {
		LOGError("Unable to find Site times in the database. SQL=(" . $sql . ")");
		MSGError("Unable to find site times in the database!");
	}

	$a = array();
	for ($i=0;$i<$n;$i++) {
		$a[$i] = DBRow($r,$i);
	}

	return $a;
}
function DBUserInfo($contest, $site, $user, $c=null,$hashpass=true) {
	$sql = "select * from usertable where usernumber=$user and usersitenumber=$site and " .
               "contestnumber=$contest";
	$a = DBGetRow ($sql, 0, $c);
	if ($a == null) {
		LOGError("Unable to find the user in the database. SQL=(" . $sql . ")");
		MSGError("Unable to find the user in the database. Contact an admin now!");
	}
	$a['changepassword']=true;
	if(substr($a['userpassword'],0,1)=='!') {
		$a['userpassword'] = substr($a['userpassword'],1);
		$a['changepassword']=false;
	}
	if($hashpass)
		$a['userpassword'] = myhash($a['userpassword'] . $a['usersessionextra']);
	$inst = explode(']',$a['userfullname']);
	if(isset($inst[1])) {
		$a['userfullname'] = trim($inst[1]);
		$inst = explode('[',$inst[0]);
		if(isset($inst[1]))
		   $a['usershortname'] = trim($inst[1]);
	}
	$inst = explode(']',$a['userdesc']);
	if(isset($inst[1])) {
		$inst2 = explode('[',$inst[0]);
		if(isset($inst2[1]))
			$a['usershortinstitution'] = trim($inst2[0]);
		if(isset($inst[2])) {
			$a['userdesc']=trim($inst[2]);
			$inst = explode('[',$inst[1]);
			if(isset($inst[1])) {
				$a['userinstitution'] = trim($inst[1]);
			}
		} else {
			$a['userdesc']=trim($inst[1]);
		}
	}
	return $a;
}
function DBDeleteUser($contest, $site, $user) {
	if ($contest==$_SESSION["usertable"]["contestnumber"] &&
	    $site==$_SESSION["usertable"]["usersitenumber"] &&
	    $user==$_SESSION["usertable"]["usernumber"]) return false;
	$c = DBConnect();
	DBExec($c, "begin work");
	DBExec($c, "lock table usertable");
	$sql = "select * from usertable where usernumber=$user and usersitenumber=$site and " .
               "contestnumber=$contest";
	$a = DBGetRow ($sql, 0);
	if ($a != null) {
		$sql = "delete from usertable where usernumber=$user and usersitenumber=$site and " .
        	       "contestnumber=$contest";
		DBExec ($c, $sql);
		DBExec($c, "commit work");
		LOGLevel("User $user (site=$site,contest=$contest) removed.", 1);
		return true;
	} else {
		DBExec($c, "rollback work");
		LOGLevel("User $user (site=$site,contest=$contest) could not be removed.", 1);
		return false;
	}
}
function DBSiteInfo($contest, $site, $c=null, $msg=true) {
	$sql = "select * from sitetable where sitenumber=$site and contestnumber=$contest";
	if($c == null) $c = DBConnect();
	$r = DBExec($c, $sql);
	if(DBnLines($r) < 1) {
	  if($msg) {
		LOGError("Unable to find the site in the database (site=$site, contest=$contest). SQL=(" . $sql . ")");
		MSGError("Unable to find the site in the database. Contact an admin now!");
          } else return null;
	}
	$a = DBRow($r, 0);

	$sql = "select sitestartdate as s, siteenddate as e from sitetimetable ".
		"where sitenumber=$site and contestnumber=$contest order by sitestartdate";
	$r = DBExec($c, $sql);
	$n = DBnLines($r);
	$a["currenttime"] = 0;
	$a["siterunning"] = false;
	$ti = time();
	for($i = 0; $i < $n; $i++) {
		$b = DBRow($r, $i);
		if($i == 0) $a["sitestartdate"] = $b["s"];
		if($b["e"] == 0) {
			$a["siterunning"] = true;
			$a["currenttime"] += $ti - $b["s"];
		} else
			$a["currenttime"] += $b["e"] - $b["s"];
		$a["siteendeddate"] = $b["e"];
	}
	if($a["siteendeddate"] == 0) $a["siteendeddate"] = $ti + $a["siteduration"] - $a["currenttime"];
	$a["siteautoended"] = false;
	if($a["siteautoend"] == "t" && $a["currenttime"] >= $a["siteduration"]) {
		$a["siterunning"] = false;
		$a["siteautoended"] = true;
	}
	return $a;
}
function DBSiteLogoffAll($contest, $site) {
	$c = DBConnect();
	DBExec($c, "begin work");
	$r = DBExec($c,"update usertable set usersessionextra='', usersession='', updatetime=".time()." where usertype!='admin' and " .
			"contestnumber=$contest and usersitenumber=$site");
	$r = DBExec($c,"update usertable set userlastlogout=".time()." where usertype!='admin' and " .
			"contestnumber=$contest and usersitenumber=$site and (userlastlogin>userlastlogout or " .
			"(userlastlogout is null and userlastlogin is not null))");
	DBExec($c, "commit work");

	LOGLevel("Logoff all (contest=$contest,site=$site).",2);
}

function DBAllSiteInfo($contest, $c=null) {
	$sql = "select * from sitetable where contestnumber=$contest";
	if($c==null)
		$c = DBConnect();
	$r = DBExec ($c, $sql);
	$n = DBnlines($r);
	if ($n == 0) {
		LOGError("Unable to find sites in the database. SQL=(" . $sql . ")");
		MSGError("Unable to find sites in the database!");
	}
	$a = array();
	for ($i=0;$i<$n;$i++) {
		$a[$i] = DBRow($r,$i);
	}
	return $a;
}
function DBAllContestInfo() {
	$sql = "select * from contesttable";
	$c = DBConnect();
	$r = DBExec ($c, $sql);
	$n = DBnlines($r);
	if ($n == 0) {
		LOGError("Unable to find contests in the database. SQL=(" . $sql . ")");
		MSGError("Unable to find contests in the database!");
	}
	$a = array();
	for ($i=0;$i<$n;$i++) {
		$a[$i] = DBRow($r,$i);
	}
	return $a;
}
function DBContestInfo($contest, $c=null) {
	$sql = "select * from contesttable where contestnumber=$contest";
	$a = DBGetRow ($sql, 0, $c);
	if ($a == null) {
		LOGError("Unable to find the contest $contest in the database. SQL=(" . $sql . ")");
		MSGError("Unable to find the contest $contest in the database. Contact an admin now!");
		return null;
	}
	return $a;
}
function DBGetActiveContest() {
       $sql = "select * from contesttable where contestactive='t'";
       $a = DBGetRow ($sql, 0);
       if ($a == null) {
               LOGError("Unable to find active contests in the database. SQL=(" . $sql . ")");
               MSGError("Unable to find active contests in the database.");
       }
       return $a;
}
function DBSiteStartNow ($contest, $site) {
	$s = DBSiteInfo($contest, $site);
	if($s["siterunning"]) return false;
	$t = time();
	$c = DBConnect();
	DBExec($c, "begin work");
	DBExec($c, "lock table sitetable");
	DBExec($c, "lock table sitetimetable");
	DBExec($c, "update sitetimetable set siteenddate=".$s['siteendeddate']." where ".
			"siteenddate=0 and sitenumber=$site and contestnumber=$contest");
	DBExec($c, "insert into sitetimetable (contestnumber, sitenumber, sitestartdate, siteenddate) ".
			"values ($contest, $site, $t, 0)");
	DBExec($c, "commit work");
	LOGLevel("Site $site (contest=$contest) started at ".dateconv($t),2);
	return true;
}
function DBSiteEndNow ($contest, $site, $w=0) {
	$s = DBSiteInfo($contest, $site);
	if(!$s["siterunning"]) return false;
	if($w == 0) $t = time();
	else $t = $w;
	$c = DBConnect();
        DBExec($c, "begin work");
	DBExec($c, "lock table runtable");
	$a = DBGetRow("select max(rundate) as t from runtable where contestnumber=$contest and ".
			"runsitenumber=$site and not runstatus ~ 'deleted'", 0);
	if($a["t"] >= $t) {
		LOGLevel("Unable to stop a contest before an existing run",2);
		MSGError("Impossible to stop a contest before an existing run");
		DBExec($c, "commit work");
		return false;
	}
        DBExec($c, "update sitetimetable set siteenddate=$t, updatetime=".time()." " .
                "where contestnumber=$contest and sitenumber=$site and siteenddate=0");
	DBExec($c, "commit work");
	LOGLevel("Site $site (contest=$contest) stopped at ".dateconv(time()),2);
	return true;
}
function DBSiteLogins ($contest, $site, $logins) {
	if(($s = DBSiteInfo($contest, $site)) == null)
		LOGError("DBSiteLogins: cant read site (contest=$contest,site=$site)");

	$param = $s;
	$param['contestnumber']=$contest;
	$param['sitenumber']=$site;
	$param['sitepermitlogins']=$logins;
	$param['updatetime']= -1;
	DBUpdateSite ($param);
	LOGLevel("Site logins=$logins (contest=$contest,site=$site)",2);
}
function DBSiteDeleteAllClars ($contest, $site, $user, $usersite, $c=null) {
	$cw=false;
	if($c==null) {
		$cw=true;
        $c = DBConnect();
        DBExec($c, "begin work");
	}
	DBExec($c, "lock table sitetable");
	DBExec($c, "lock table clartable");
	DBExec($c, "select * from sitetable where contestnumber=$contest and sitenumber=$site for update");
	$r = DBExec($c, "select * from clartable as c where c.contestnumber=$contest and " .
				"c.clarsitenumber=$site for update");
	DBExec($c, "delete from clartable where contestnumber=$contest and clarsitenumber=$site");
	DBExec($c, "update sitetable set sitenextclar=0, updatetime=".time()." " .
		   "where contestnumber=$contest and sitenumber=$site");
	if($cw) {
        DBExec($c, "commit work");
		LOGLevel("All Clarifications deleted (site=$site, contest=$contest, user=$user(site=$usersite)).", 3);
	}
	return true;
}
function DBSiteDeleteAllTasks ($contest, $site, $user, $usersite,$c=null) {
	$cw=false;
	if($c==null) {
		$cw=true;
        $c = DBConnect();
        DBExec($c, "begin work");
	}
	DBExec($c, "lock table sitetable");
	DBExec($c, "lock table tasktable");
	DBExec($c, "select * from sitetable where contestnumber=$contest and sitenumber=$site for update");
	$r = DBExec($c, "select * from tasktable as t where t.contestnumber=$contest and " .
				"t.sitenumber=$site for update");
	DBExec($c, "delete from tasktable where contestnumber=$contest and sitenumber=$site");
	DBExec($c, "update sitetable set sitenexttask=0, updatetime=".time()." " .
		   "where contestnumber=$contest and sitenumber=$site");
	if($cw) {
		DBExec($c, "commit work");
		LOGLevel("All Tasks deleted (site=$site, contest=$contest, user=$user(site=$usersite)).", 3);
	}
	return true;
}
function DBSiteDeleteAllBkps ($contest, $site, $user, $usersite,$c=null) {
	$cw=false;
	if($c==null) {
		$cw=true;
        $c = DBConnect();
        DBExec($c, "begin work");
	}
	DBExec($c, "lock table bkptable");
	$r = DBExec($c, "select bkpdata from bkptable where contestnumber=$contest and sitenumber=$site and bkpstatus='active'");
	$n = DBnlines($r);
	for ($i=0;$i<$n;$i++) {
		$a = DBRow($r,$i);
		DB_lo_unlink($c,$a["bkpdata"]);
	}
	DBExec($c, "delete from bkptable where contestnumber=$contest and sitenumber=$site");
	if($cw) {
		DBExec($c, "commit work");
		LOGLevel("All Bkps deleted (site=$site, contest=$contest, user=$user(site=$usersite)).", 3);
	}
	return true;
}
function DBSiteDeleteAllRuns ($contest, $site, $user, $usersite,$c=null) {
	$cw=false;
	if($c==null) {
		$cw=true;
		$c = DBConnect();
		DBExec($c, "begin work");
	}
	DBExec($c, "lock table sitetable");
	DBExec($c, "lock table runtable");
	DBExec($c, "select * from sitetable where contestnumber=$contest and sitenumber=$site for update");
	$sql = "select * from runtable as r where r.contestnumber=$contest and " .
		"r.runsitenumber=$site";
	$r = DBExec ($c, $sql . " for update");
	DBExec($c, "delete from runtable where contestnumber=$contest and runsitenumber=$site");
	DBExec($c, "update sitetable set sitenextrun=0, updatetime=".time()." " .
		   "where contestnumber=$contest and sitenumber=$site");
	if($cw) {
		DBExec($c, "commit work");
		LOGLevel("All Runs deleted (site=$site, contest=$contest, user=$user(site=$usersite)).", 3);
	}
	return true;
}
function DBUpdateSite ($param,$c=null) {
	$ac=array('contestnumber','sitenumber','sitename','sitepermitlogins','sitescorelevel');
	$ac1=array('updatetime','siteautoend','siteglobalscore','siteip','siteactive','siteduration','sitelastmileanswer','sitelastmilescore',
			   'siteautojudge','sitenextuser','sitenextclar','sitenextrun','sitenexttask','sitemaxtask','sitechiefname','sitejudging','sitetasking');

	if(isset($param['number']) && !isset($param['sitenumber'])) $param['sitenumber']=$param['number'];
	$type['contestnumber']=1;
	$type['sitenumber']=1;
	$type['updatetime']=1;
	$type['siteduration']=1;
	$type['sitelastmilescore']=1;
	$type['sitelastmileanswer']=1;
	$type['sitenextuser']=1;
	$type['sitenextclar']=1;
	$type['sitenextrun']=1;
	$type['sitenexttask']=1;
	$type['sitemaxtask']=1;
	$type['sitescorelevel']=1;
	foreach($ac as $key) {
		if(!isset($param[$key])) {
			MSGError("DBUpdateSite param error: $key is not set");
			return false;
		}
		$$key = sanitizeText($param[$key]);
		if(isset($type[$key]) && !is_numeric($param[$key])) {
			MSGError("DBUpdateSite param error: $key is not numeric");
			return false;
		}
	}
	$siteduration=-1;
	$sitelastmileanswer=-1;
	$sitelastmilescore=-1;
	$sitenextuser = -1;
	$sitenextclar = -1;
	$sitenextrun = -1;
	$sitenexttask = -1;
	$sitemaxtask = -1;
	$sitejudging = '';
	$sitetasking = '';
	$sitechiefname = '';
	$siteip='';
	$updatetime = -1;
	$siteautojudge = 'f';
	$siteautoend='f';
	$siteglobalscore='';
	$siteactive='f';
	foreach($ac1 as $key) {
		if(isset($param[$key])) {
			$$key = sanitizeText($param[$key]);
			if(isset($type[$key]) && !is_numeric($param[$key])) {
				MSGError("DBUpdateSite param error: $key is not numeric");
				return false;
			}
		}
	}
	if ($siteautoend != "t" && $siteautoend != "") $siteautoend = "f";
	if ($siteactive != "t" && $siteactive != "") $siteactive = "f";
	if ($siteautojudge != "t" && $siteautojudge != "") $siteautojudge = "f";
	if ($sitescorelevel == "" || !is_numeric($sitescorelevel)) {
		$sitescorelevel = -10;
	} else {
		if ($sitescorelevel < -3) $sitescorelevel = -4;
		if ($sitescorelevel > 3) $sitescorelevel = 4;
	}
	$docommit=false;
	if($c==null) {
		$c = DBConnect();
		DBExec($c, "begin work", "DBUpdateSite(begin)");
		$docommit=true;
	}
	$a = DBGetRow ("select * from sitetable where contestnumber=$contestnumber and sitenumber=$sitenumber", 0, $c);
	if ($a == null) {
		$ret=2;
		$param['number']=$sitenumber;
		DBNewSite($contestnumber,$c,$param);
		$a = DBGetRow ("select * from sitetable where contestnumber=$contestnumber and sitenumber=$sitenumber", 0, $c);
		if ($a == null) {
			DBExec($c, "rollback work", "DBUpdateSite(rollback-errorsite)");
			MSGError("DBUpdateSite update error: impossible to create a site in the DB");
			LOGLevel("DBUpdateSite update error: impossible to create a site in the DB",0);
			return false;
		}
	}
	$t = time();
	if($updatetime <= 0)
		$updatetime=$t;
	$ret=1;
	if($updatetime > $a['updatetime']) {
		$ret=2;
		if($sitenextrun==0)
			DBSiteDeleteAllRuns($contestnumber,$sitenumber,$_SESSION["usertable"]["usernumber"],$_SESSION["usertable"]["usersitenumber"],$c);
		if($sitenextclar==0)
			DBSiteDeleteAllClars($contestnumber,$sitenumber,$_SESSION["usertable"]["usernumber"],$_SESSION["usertable"]["usersitenumber"],$c);
		if($sitenexttask==0)
			DBSiteDeleteAllTasks($contestnumber,$sitenumber,$_SESSION["usertable"]["usernumber"],$_SESSION["usertable"]["usersitenumber"],$c);

		$sql = "update sitetable set sitename='$sitename', ";
		if ($sitepermitlogins!="") $sql .= "sitepermitlogins='$sitepermitlogins', ";
		if ($siteduration > 0)
			$sql .= "siteduration=$siteduration, ";
		if($siteip != '')
			$sql .= "siteip='$siteip',";
		if($siteautoend != "")
			$sql .=	"siteautoend='$siteautoend', ";
		if($siteactive != "")
			$sql .= "siteactive='$siteactive', ";
		if($siteglobalscore != "")
			$sql .= "siteglobalscore='$siteglobalscore', ";
		if($sitenextuser >= 0)
			$sql .= "sitenextuser=$sitenextuser, ";
		if($sitenextclar >= 0)
			$sql .= "sitenextclar=$sitenextclar, ";
		if($sitenextrun >= 0)
			$sql .= "sitenextrun=$sitenextrun, ";
		if($sitenexttask >= 0)
			$sql .= "sitenexttask=$sitenexttask, ";
		if($sitemaxtask >= 0)
			$sql .= "sitemaxtask=$sitemaxtask, ";
		if($sitechiefname != '')
			$sql .= "sitechiefname='$sitechiefname', ";
		if($siteautojudge != '')
			$sql .= "siteautojudge='$siteautojudge', ";
		if($sitejudging != '')
			$sql .= "sitejudging='$sitejudging', ";
		if($sitetasking != '')
			$sql .= "sitetasking='$sitetasking', ";
		if($sitelastmileanswer > 0)
			$sql .= " sitelastmileanswer=$sitelastmileanswer, ";
		if($sitelastmilescore > 0)
			$sql .= " sitelastmilescore=$sitelastmilescore, ";
		if($sitescorelevel > -5)
			$sql .= " sitescorelevel=$sitescorelevel, ";
		$sql .= " updatetime=".$updatetime." where contestnumber=$contestnumber and sitenumber=$sitenumber ";
		//. "and updatetime < $updatetime";
		DBExec($c,$sql, "DBUpdateSite(update site)");
		if($docommit) {
			DBExec($c, "commit work", "DBUpdateSite(commit-update)");	
			LOGLevel("User " . $_SESSION["usertable"]["username"]."/". $_SESSION["usertable"]["usersitenumber"] . 
					 " changed the site $sitenumber (contest=$contestnumber) settings.",2);
		}
	} else {
		if($docommit) 	
			DBExec($c, "commit work", "DBUpdateSite(commit-noupdate)");
	}
	return $ret;
}
function DBUpdateContest ($param, $c=null) {
	if(isset($param['contestnumber']) && !isset($param['number'])) $param['number']=$param['contestnumber'];

	$ac=array('number');
	$ac1=array('updatetime','atualizasites','scorelevel','mainsite','localsite','mainsiteurl','keys','unlockkey','name',
			   'active','lastmileanswer','lastmilescore','penalty','startdate', 'duration', 'maxfilesize');
	$type['number']=1;
	$type['scorelevel']=1;
	$type['startdate']=1;
	$type['updatetime']=1;
	$type['duration']=1;
	$type['penalty']=1;
	$type['maxfilesize']=1;
	$type['active']=1;
	$type['lastmilescore']=1;
	$type['lastmileanswer']=1;
	$type['mainsite']=1;
	$type['localsite']=1;
	foreach($ac as $key) {
		if(!isset($param[$key])) {
			MSGError("DBUpdateContest param error: $key is not set");
			return false;
		}
		$$key = sanitizeText($param[$key]);
		if(isset($type[$key]) && !is_numeric($param[$key])) {
			MSGError("DBUpdateContest param error: $key is not numeric");
			return false;
		}
	}
	$name='';
	$atualizasites = false;
	$mainsiteurl='';
	$keys='';
	$unlockkey='';
	$mainsite=-1;
	$duration=-1;
	$lastmilescore=-1;
	$lastmileanswer=-1;
	$penalty=-1;
	$maxfilesize=-1;
	$active=0;
	$startdate=-1;
	$localsite=-1;
	$updatetime=-1;
	foreach($ac1 as $key) {
		if(isset($param[$key])) {
			$$key = sanitizeText($param[$key]);
			if(isset($type[$key]) && !is_numeric($param[$key])) {
				MSGError("DBUpdateContest param error: $key is not numeric");
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
		DBExec($c, "begin work", "DBUpdateContest(begin)");
	}
	$a = DBGetRow("select * from contesttable where contestnumber=$number for update", 0, $c, "DBUpdateContest(get for update)");
	if($a == null) {
		MSGError("Error updating contest $number -- not found");
		LOGError("DBUpdateContest contest $number not found");
		return false;
	}
	$ret=1;
	if ($active == 1) {
		$ret=2;
		DBExec($c, "update contesttable set contestactive='f'", "DBUpdateContest(deactivate)");
		DBExec($c, "update contesttable set contestactive='t' where contestnumber=$number",
			   "DBUpdateContest(active)");
		LOGLevel("User " . $_SESSION["usertable"]["username"]."/". $_SESSION["usertable"]["usersitenumber"] . " activated contest $number.",2);
	}
	$chd=false;
	if($updatetime > $a['updatetime']) {
		$ret=2;
		$sql = "update contesttable set updatetime=".$updatetime;
		if($name != '') $sql .= ", contestname='$name'";
		if($maxfilesize > 0) $sql .= ", contestmaxfilesize=$maxfilesize";
		if($penalty > 0) $sql .= ", contestpenalty=$penalty";
		if($lastmileanswer > 0) $sql .= ", contestlastmileanswer=$lastmileanswer";
		if($lastmilescore > 0) $sql .= ", contestlastmilescore=$lastmilescore";
		if($startdate > 0) $sql .= ", conteststartdate=$startdate";
		if($duration > 0) $sql .= ", contestduration=$duration";
		if ($mainsite > 0) $sql .= ", contestmainsite=$mainsite";
		if ($mainsiteurl != '') $sql .= ", contestmainsiteurl='$mainsiteurl'";
		if ($unlockkey != '') $sql .= ", contestunlockkey='$unlockkey'";
		if ($keys != '') $sql .= ", contestkeys='$keys'";
		if ($localsite > 0) $sql .= ", contestlocalsite=$localsite";
		$sql .= " where contestnumber=$number";
		DBExec($c, $sql, "DBUpdateContest(update contest)");

		if($localsite > 0) {
			$param['contestnumber']=$number;
			$param['sitename']='Local site';
			if($duration > 0)
				$param['siteduration']=$duration;
			if(isset($param['scorelevel']))
				$param['sitescorelevel']=$scorelevel;
			if($lastmileanswer > 0)
				$param['sitelastmileanswer']=$lastmileanswer;
			if($lastmilescore > 0)
				$param['sitelastmilescore']=$lastmilescore;
			$param['number']=$localsite;
			DBNewSite ($number,$c,$param);
		}
		if($mainsite > 0) {
			$param['contestnumber']=$number;
			$param['sitename']='Main site';
			if($duration > 0)
				$param['siteduration']=$duration;
			if(isset($param['scorelevel']))
				$param['sitescorelevel']=$scorelevel;
			if($lastmileanswer > 0)
				$param['sitelastmileanswer']=$lastmileanswer;
			if($lastmilescore > 0)
				$param['sitelastmilescore']=$lastmilescore;
			$param['number']=$mainsite;
			DBNewSite ($number,$c,$param);
		}

		if($atualizasites) {
			$s = DBAllSiteInfo($number,$c);
			for($i=0; $i<count($s); $i++) {
				$param = $s[$i];
				$param['contestnumber']=$number;
				if($duration > 0)
					$param['siteduration']=$duration;
				if(isset($param['scorelevel']))
					$param['sitescorelevel']=$scorelevel;
				if($lastmileanswer > 0)
					$param['sitelastmileanswer']=$lastmileanswer;
				if($lastmilescore > 0)
					$param['sitelastmilescore']=$lastmilescore;
				unset($param['updatetime']);
				DBUpdateSite ($param,$c);
				
				if($startdate > 0) {
					$p=array();
					$p['contest']=$number;
					$p['site']=$s[$i]["sitenumber"];
					$p['start']=$startdate;
					DBRenewSiteTime($p, $c);
				}
			}
		}
		$chd=true;
	}
	if($cw) {
		DBExec($c, "commit work", "DBUpdateContest(commit)");
	}
	if($chd)
		LOGLevel("User " . $_SESSION["usertable"]["username"]."/". $_SESSION["usertable"]["usersitenumber"] . " changed the contest $number settings.",2);
	return $ret;
}
function DBRenewSiteTime($param, $c=null) {
	if(!isset($param[0])) {
		$tmp = $param;
		$param = array();
		$param[0] = $tmp;
	}
	$ac=array('contest','site','start');
	$ac1=array('enddate','updatetime');
	$type['contest']=1;
	$type['site']=1;
	$type['start']=1;
	$type['enddate']=1;

	$t = time();
	$maxtime = 0;
	for($i=0; isset($param[$i]); $i++) {
//		LOGLevel(implode(" ",array_keys($param[$i])),2);
//		LOGLevel(implode(" ",$param[$i]),2);
		if(isset($param[$i]['contestnumber']) && !isset($param[$i]['contest'])) $param[$i]['contest']=$param[$i]['contestnumber'];
		if(isset($param[$i]['sitenumber']) && !isset($param[$i]['site'])) $param[$i]['site']=$param[$i]['sitenumber'];
		foreach($ac as $key) {
			if(!isset($param[$i][$key])) {
				MSGError("DBRenewSiteTime param error: $key is not set");
				return false;
			}
			if(isset($type[$key]) && !is_numeric($param[$i][$key])) {
				MSGError("DBRenewSiteTime param error: $key is not numeric");
				return false;
			}
		}
		foreach($ac1 as $key) {
			if(isset($param[$i][$key])) {
				if(isset($type[$key]) && !is_numeric($param[$i][$key])) {
					MSGError("DBRenewSiteTime param error: $key is not numeric");
					return false;
				}
			}
		}
		if(!isset($param[$i]['updatetime'])) $param[$i]['updatetime']=$t;
		if($param[$i]['updatetime'] > $maxtime) $maxtime = $param[$i]['updatetime'];
		if(!isset($param[$i]['enddate'])) $param[$i]['enddate']=0;

		if($param[$i]['contest'] != $param[0]['contest'] || $param[$i]['site'] != $param[0]['site']) {
			MSGError("DBRenewSiteTime param error: contest and site have to match over all instances");
			return false;
		}
	}
	$cw = false;
	if($c == null) {
		$cw = true;
		$c = DBConnect();
		DBExec($c, "begin work", "DBRenewSiteTime(begin)");
	}
	DBExec($c,"lock table sitetimetable","DBRenewSiteTime(lock)");

	$a = DBGetRow ("select max(updatetime) as maxtime from sitetimetable where contestnumber=". $param[0]['contest']. 
				   " and sitenumber=". $param[0]['site'], 0, $c);
	$ret = 1;
	if ($a == null || $a['maxtime'] < $maxtime) {
		DBExec($c, "delete from sitetimetable where contestnumber=" . $param[0]['contest'].
			   " and sitenumber=". $param[0]['site'], "DBRenewSiteTime(delete)");
		for($i=0; isset($param[$i]); $i++) {
			DBExec($c, "insert into sitetimetable (contestnumber, sitenumber, sitestartdate, siteenddate, updatetime) ".
				   "values (". $param[0]['contest'].", ". $param[0]['site'].", ".$param[$i]['start'].", ".
				   $param[$i]['enddate'].", ".$param[$i]['updatetime'].")", "DBRenewSiteTime(insert)"); 
		}
		$ret = 2;
	}
	if($cw)	DBExec($c, "commit work", "DBRenewSiteTime(commit)");
	return $ret;
}
function DBNewContest ($param=array(), $c=null) {
	$cw = false;
	if($c == null) {
		$cw = true;
		$c = DBConnect();
		DBExec($c, "begin work", "DBNewContest(begin)");
	}
	$a = DBGetRow ("select max(contestnumber) as contest from contesttable", 0, $c,
				   "DBNewContest(max(contest))");
	if ($a == null) $n=1;
	else $n = $a["contest"]+1;
	
	$ac=array('name','startdate','duration','lastmileanswer','lastmilescore','penalty','updatetime','localsite','mainsite','mainsiteurl','keys','unlockkey');	 //'active'
	$type['startdate']=1;
	$type['duration']=1;
	$type['lastmileanswer']=1;
	$type['lastmilescore']=1;
	$type['penalty']=1;
	$type['updatetime']=1;
	$type['mainsite']=1;
	$type['localsite']=1;
	$mainsiteurl='';
	$keys='';
	$unlockkey='';
	foreach($ac as $key) {
		if(isset($param[$key]) && (!isset($type[$key]) || is_numeric($param[$key])))
			$$key = sanitizeText($param[$key]);
		else
			$$key = "";
	}
	if($mainsite=="") $mainsite=1;
	if($localsite=="") $localsite=1;
	if($name=="") $name="Contest";
	if($startdate=="") $startdate="EXTRACT(EPOCH FROM now())+600";
	if($duration=="") $duration=300*60;
	if($lastmileanswer=="") $lastmileanswer=285*60;
	if($lastmilescore=="") $lastmilescore=240*60;
	if($penalty=="") $penalty=20*60;
	//if($active=="") 
	$active="f";
	if($updatetime=="") $updatetime=time();

	DBExec($c, "insert into contesttable (contestnumber, contestname, conteststartdate, contestduration, ".
			"contestlastmileanswer, contestlastmilescore, contestlocalsite, contestpenalty, ".
			"contestmaxfilesize, contestactive, contestmainsite, contestmainsiteurl,contestkeys,contestunlockkey, updatetime) values ($n, '$name', ".
		   "$startdate, $duration, $lastmileanswer, " .
		   "$lastmilescore, $localsite, $penalty, 100000, '$active', $mainsite, '$mainsiteurl', '$keys','$unlockkey',$updatetime)", "DBNewContest(insert contest)");

	DBNewSite($n, $c, $param);

	insertanswers($n,$c);
	insertlanguages($n,$c);
	DBinsertfakeproblem($n,$c);

	if($cw) {
		DBExec($c, "commit work", "DBNewContest(commit)");
	}
	LOGLevel("User " . $_SESSION["usertable"]["username"]."/". $_SESSION["usertable"]["usersitenumber"] . " created a new contest ($n).",2);
	return $n;
}
function insertlanguages($n,$c=null) {
	$ok=false;
	$param=null;
	$param['number']=1;
	$param['name']='C';
	$param['extension']='c';
	DBNewLanguage($n, $param, $c);
	$param['number']=2;
	$param['name']='C++';
	$param['extension']='cpp';
	DBNewLanguage($n, $param, $c);
	$param['number']=3;
	$param['name']='Java';
	$param['extension']='java';
	DBNewLanguage($n, $param, $c);
	$param['number']=4;
	$param['name']='C++11';
	$param['extension']='cc';
	DBNewLanguage($n, $param, $c);
}
function insertanswers($n,$c) {
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
			"($n, 7, 'NO - If possible, contact staff', 'f', 'f')", "DBNewContest(insert CS answer)");
}
function DBNewSite ($contest, $c=null, $param=array()) {
	$cw = false;
	if($c == null) {
		$cw = true;
		$c = DBConnect();
		DBExec($c, "begin work");
	}
	$ct = DBContestInfo ($contest, $c);
	if($ct==null) return false;

	if(isset($param['sitenumber']) && !isset($param['number'])) $param['number']=$param['sitenumber'];
	$ac=array('number','siteip','sitename','sitescorelevel','updatetime','startdate','duration');
	$type=array();
	$type['startdate']=1;
	$type['duration']=1;
	$type['number']=1;
	$type['sitescorelevel']=1;
	$type['updatetime']=1;
	foreach($ac as $key) {
		if(isset($param[$key]) && (!isset($type[$key]) || is_numeric($param[$key])))
			$$key = sanitizeText($param[$key]);
		else
			$$key = "";
	}

	if($number=="") {
		$a = DBGetRow ("select max(sitenumber) as site from sitetable where contestnumber=$contest", 0, $c);
		if ($a == null) $n=1;
		else $n = $a["site"]+1;
		$number=$n;
	} else {
		$a = DBGetRow ("select * from sitetable where contestnumber=$contest and sitenumber=$number", 0, $c);
		if($a != null) return 1;
	}
	if($duration=='') $duration = $ct["contestduration"];
	if($startdate=='') $startdate=$ct["conteststartdate"];
	if($siteip=="") $siteip="127.0.0.1/boca";
	if($sitename=="") $sitename="Site";
	if($sitescorelevel=="") $sitescorelevel=3;
	$t=time();
	if($updatetime=="") $updatetime=$t;
	DBExec($c, "insert into sitetable (contestnumber, sitenumber, siteip, sitename, siteactive, sitepermitlogins, ".
			"sitelastmileanswer, sitelastmilescore, siteduration, siteautoend, sitejudging, sitetasking, ".
			"siteglobalscore, sitescorelevel, ".
			"sitenextuser, sitenextclar, sitenextrun, sitenexttask, sitemaxtask, updatetime) values ".
			"($contest, $number, '$siteip', '$sitename', 't', 't', ".
                        $ct["contestlastmileanswer"].",".$ct["contestlastmilescore"].
			", $duration, 't', '$number', '$number', '$number', $sitescorelevel, 0, 0, 0, 0, 10, $updatetime)");

	$cf=globalconf();
	$admpass = myhash($cf["basepass"]);

	DBExec($c, "insert into usertable ".
		"(contestnumber, usersitenumber, usernumber, username, userfullname, " .
		"userdesc, usertype, userenabled, usermultilogin, userpassword, userip, userlastlogin, ".
		"usersession, usersessionextra, userlastlogout, userpermitip, updatetime) values " .
		"($contest, $number, 1000, 'admin', 'Administrator', NULL, 'admin', ".
		   "'t', 't', '$admpass', NULL, NULL, '', '', NULL, NULL, $updatetime)");
	$param=array();
	$param['contest']=$contest;
	$param['site']=$number;
	$param['start']=$startdate;
	DBRenewSiteTime($param, $c);
	if($cw)	DBExec($c, "commit work");
	LOGLevel("User " . $_SESSION["usertable"]["username"]."/". $_SESSION["usertable"]["usersitenumber"] . 
			 " created site $number on contest $contest.",2);
	return 2;
}

function DBUserUpdate($contest, $site, $user, $username, $userfull, $userdesc, $passo, $passn) {
	$a = DBUserInfo($contest, $site, $user, null, false);
	$p = myhash($a["userpassword"] . session_id());
	if ($a["userpassword"] != "" && $p != $passo) {
		LOGLevel("User " . $_SESSION["usertable"]["username"]."/". $_SESSION["usertable"]["usersitenumber"] . " (contest=$contest, site=$site) " .
				 "tried to change settings, but password was incorrect.",2);
		MSGError ("Incorrect password.");
	}
	else { 
		if(!$a['changepassword']) {
			MSGError('Password change is DISABLED'); return;
		}
		if ($a["userpassword"] == "") $temp = myhash("");
		else $temp = $a["userpassword"];
		$lentmp = strlen($temp);
		$temp = bighexsub($passn, $temp);
		if($lentmp > strlen($temp))
			$newpass = '0' . $temp;
		else
			$newpass = substr($temp, strlen($temp)-$lentmp);

		$c = DBConnect();
		DBExec($c, "begin work");
		DBExec($c, "lock table usertable");
		$r = DBExec($c, "select * from usertable where username='$username' and usernumber!=$user and ".
				"usersitenumber=$site and contestnumber=$contest");
		$n = DBnlines ($r);
		if ($n == 0) {
			$sql = "update usertable set username='$username', userdesc='$userdesc', userfullname='$userfull', updatetime=".time();
			if ($newpass != myhash("")) $sql .= ", userpassword='$newpass'";
			$sql .= " where usernumber=$user and usersitenumber=$site and contestnumber=$contest";
			$r = DBExec ($c, $sql);
			DBExec ($c, "commit work");
			LOGLevel("User " . $_SESSION["usertable"]["username"]."/". $_SESSION["usertable"]["usersitenumber"] . " changed his settings (newname=$username) ".
				"(user=$user,site=$site,contest=$contest)",2);
			MSGError("Data updated.");
			ForceLoad("index.php");
		} else {
			DBExec ($c, "rollback work");
			LOGLevel("User " . $_SESSION["usertable"]["username"]."/". $_SESSION["usertable"]["usersitenumber"] . " couldn't change his settings " .
				"(user=$user,site=$site,contest=$contest)",2);
			MSGError ("Update problem (maybe username already in use). No data was changed.");
		}
	}
}

function DBNewUser($param, $c=null) {
	if(isset($param['contestnumber']) && !isset($param['contest'])) $param['contest']=$param['contestnumber'];
	if(isset($param['sitenumber']) && !isset($param['site'])) $param['site']=$param['sitenumber'];
	if(isset($param['usernumber']) && !isset($param['user'])) $param['user']=$param['usernumber'];
	if(isset($param['number']) && !isset($param['user'])) $param['user']=$param['number'];

	$ac=array('contest','site','user');
	$ac1=array('updatetime','username','usericpcid','userfull','userdesc','type','enabled','multilogin','pass','permitip','changepass',
			   'userip','userlastlogin','userlastlogout','usersession','usersessionextra');

	$typei['contest']=1;
	$typei['updatetime']=1;
	$typei['site']=1;
	$typei['user']=1;
	foreach($ac as $key) {
		if(!isset($param[$key]) || $param[$key]=="") {
			MSGError("DBNewUser param error: $key not found");
			return false;
		}
		if(isset($typei[$key]) && !is_numeric($param[$key])) {
			MSGError("DBNewUser param error: $key is not numeric");
			return false;
		}
		$$key = sanitizeText($param[$key]);
	}
	$username= "team" . $user;
	$updatetime=-1;
	$pass = null;
	$usericpcid='';
	$userfull='';
	$userdesc='';
	$type='team';
	$enabled='f';
	$changepass='f';
	$multilogin='f';
	$permitip='';
	$usersession=null;
	$usersessionextra=null;
	$userip=null;
	$userlastlogin=null;
	$userlastlogout=null;
	foreach($ac1 as $key) {
		if(isset($param[$key])) {
			$$key = sanitizeText($param[$key]);
			if(isset($typei[$key]) && !is_numeric($param[$key])) {
				MSGError("DBNewUser param error: $key is not numeric");
				return false;
			}
		}
	}
	$t = time();
	if($updatetime <= 0)
		$updatetime=$t;

	if ($type != "chief" && $type != "judge" && $type != "admin" && 
	    $type != "score" && $type != "staff" && $type != "site") 
		$type = "team";
	if ($type == "admin") $changepass = "t";
	if ($enabled != "f") $enabled = "t";
	if ($multilogin != "t") $multilogin = "f";
	if ($changepass != "t") $changepass = "f";

	$cw = false;
	if($c == null) {
		$cw = true;
		$c = DBConnect();
		DBExec($c, "begin work", "DBNewUser(begin)");
	}
	DBExec($c, "lock table usertable", "DBNewUser(lock)");
	$r = DBExec($c, "select * from sitetable where sitenumber=$site and contestnumber=$contest", "DBNewUser(get site)");
	$n = DBnlines ($r);
	if($n == 0) {
		DBExec ($c, "rollback work","DBNewUser(no-site)");
		MSGError("DBNewUser param error: site $site does not exist");
		return false;
	}
	if($pass != myhash("") && $type != "admin" && $changepass != "t") $pass='!'.$pass;
	$r = DBExec($c, "select * from usertable where username='$username' and usernumber!=$user and ".
				"usersitenumber=$site and contestnumber=$contest", "DBNewUser(get user)");
	$n = DBnlines ($r);
	$ret=1;
	if ($n == 0) {
		$sql = "select * from usertable where usernumber=$user and usersitenumber=$site and " .
			"contestnumber=$contest";
		$a = DBGetRow ($sql, 0, $c);
		if ($a == null) {
			$ret=2;
		  $sql = "select * from sitetable where sitenumber=$site and contestnumber=$contest";
		  $aa = DBGetRow ($sql, 0);
		   if($aa==null) {
		   	DBExec ($c, "rollback work");
			MSGError("Site $site does not exist");
			return false;
		   }
			$sql = "insert into usertable (contestnumber, usersitenumber, usernumber, username, usericpcid, userfullname, " .
				"userdesc, usertype, userenabled, usermultilogin, userpassword, userpermitip) values " .
				"($contest, $site, $user, '$username', '$usericpcid', '$userfull', '$userdesc', '$type', '$enabled', " .
				"'$multilogin', '$pass', '$permitip')";
			DBExec ($c, $sql, "DBNewUser(insert)");
			if($cw) {
				DBExec ($c, "commit work");
			}
			LOGLevel ("User $user (site=$site,contest=$contest) included.",2);
		} else {
			if($updatetime > $a['updatetime']) {
				$ret=2;
				$sql = "update usertable set username='$username', usericpcid='$usericpcid', userdesc='$userdesc', updatetime=$updatetime, " .
					"userfullname='$userfull', usertype='$type', userpermitip='$permitip', ";
				if($pass != null && $pass != myhash("")) $sql .= "userpassword='$pass', ";
				if($usersession != null) $sql .= "usersession='$usersession', ";
				if($usersessionextra != null) $sql .= "usersessionextra='$usersessionextra', ";
				if($userip != null) $sql .= "userip='$userip', ";
				if($userlastlogin != null) $sql .= "userlastlogin='$userlastlogin', ";
				if($userlastlogout != null) $sql .= "userlastlogout='$userlastlogout', ";
				$sql .= "userenabled='$enabled', usermultilogin='$multilogin'";
				$sql .=	" where usernumber=$user and usersitenumber=$site and contestnumber=$contest";
				$r = DBExec ($c, $sql, "DBNewUser(update)");
				if($cw) {
					DBExec ($c, "commit work");
				}
				LOGLevel("User $user (username=$username,site=$site,contest=$contest) updated.",2);
			}
		}
	} else {
		DBExec ($c, "rollback work");
		LOGLevel ("Update problem for user $user (site=$site,contest=$contest) (maybe username already in use).",1);
		MSGError ("Update problem for user $user, site $site (maybe username already in use).");
		return false;
	}
	return $ret;
}

function siteclock() {
	if (($s=DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
	        ForceLoad("../index.php");

	if ($s["siteactive"]!="t") 
		return array("site is not active",-1000000000);
	if (!$s["siterunning"])
		return array("contest not running",-1000000000);
	if ($s["currenttime"]<0) {
		$t = - $s["currenttime"];
		if($t>3600) {
			$t = ((int) ($t/360))/10;
			return array("&gt; ". $t . " hour(s) to start",$s["currenttime"]);
		}
		if ($t>60) {
			$t = (int) ($t/60);
			return array("&gt; ". $t . " min(s) to start",$s["currenttime"]);
		} else {
			return array($t . " second(s) to start",$s["currenttime"]);
		}
	}
	if ($s["currenttime"]>=0) {
		$t = $s["siteduration"] - $s["currenttime"];
		$str = '';
		if($t >= 3600) {
			$str .= ((int)($t/3600)) . 'h ';
			$t = $t % 3600;
		}
		if ($t>60) {
			$t = (int) ($t/60);
			return array($str . $t . " min(s) left",$s["currenttime"]);
		} else if($str=='') {
			if ($t>0) {
				return array($t . " second(s) left",$s["currenttime"]);
			} else {
				$t = (int) (- $t/60);
				return array($t . "min. of extra time",$s["currenttime"]);
			}
		} else return array($str . " left",$s["currenttime"]);
	}
	else return array("not started",-1000000000);
}
// eof
?>
