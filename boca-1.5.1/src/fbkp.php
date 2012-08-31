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

function DBDropBkpTable() {
	 $c = DBConnect();
	 $r = DBExec($c, "drop table \"bkptable\"", "DBDropBkpTable(drop table)");
}
function DBCreateBkpTable() {
	 $c = DBConnect();
	 $conf = globalconf();
	 if($conf["dbuser"]=="") $conf["dbuser"]="bocauser";
	 $r = DBExec($c, "
CREATE TABLE \"bkptable\" (
\"contestnumber\" int4 NOT NULL,	-- (id do concurso)
\"sitenumber\" int4 NOT NULL,	-- (local de origem da submissao)
\"bkpnumber\" int4 NOT NULL,		-- (numero da submissao)
\"usernumber\" int4 NOT NULL,		-- (numero do time)
\"bkpdate\" int4 NOT NULL,		-- (dia/hora da submissao no local de origem)
\"bkpfilename\" varchar(200) NOT NULL,	-- (nome do arquivo submetido)
\"bkpdata\" oid NOT NULL,		-- (codigo fonte do arquivo submetido)
\"bkpstatus\" varchar(50) NOT NULL,
\"bkpsize\" int4 NOT NULL,
\"updatetime\" int4 DEFAULT EXTRACT(EPOCH FROM now()) NOT NULL, -- (indica a ultima atualizacao no registro)
CONSTRAINT \"bkp_pkey\" PRIMARY KEY (\"contestnumber\", \"sitenumber\", \"bkpnumber\"),
CONSTRAINT \"user_fk\" FOREIGN KEY (\"contestnumber\", \"sitenumber\", \"usernumber\")
	REFERENCES \"usertable\" (\"contestnumber\", \"usersitenumber\", \"usernumber\")
	ON DELETE CASCADE ON UPDATE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)", "DBCreateBkpTable(create table)");
        $r = DBExec($c, "REVOKE ALL ON \"bkptable\" FROM PUBLIC", "DBCreateBkpTable(revoke public)");
	$r = DBExec($c, "GRANT ALL ON \"bkptable\" TO \"".$conf["dbuser"]."\"", "DBCreateBkpTable(grant bocauser)");
	$r = DBExec($c, "CREATE UNIQUE INDEX \"bkp_index\" ON \"bkptable\" USING btree ". 
		"(\"contestnumber\" int4_ops, \"sitenumber\" int4_ops, \"bkpnumber\" int4_ops)", 
		"DBCreateBkpTable(create bkp_index)");
	$r = DBExec($c, "CREATE INDEX \"bkp_index2\" ON \"bkptable\" USING btree ". 
	        "(\"contestnumber\" int4_ops, \"sitenumber\" int4_ops, \"usernumber\" int4_ops)",
		"DBCreateBkpTable(create bkp_index2)");
}

///////////////////////////////funcoes de runs///////////////////////////////////////////////////////
function DBBkpDelete($number,$site,$contest,$user,$adm='') {
	$c = DBConnect();
	DBExec($c, "begin work", "DBBkpDelete(transaction)");
	$sql = "select r.bkpdata as oid from bkptable as r where r.contestnumber=$contest and " .
		 "r.sitenumber=$site and r.bkpnumber=$number and r.usernumber=$user";
	$r = DBExec ($c, $sql . " for update", "DBBkpDelete(get bkp for update)");
	$n = DBnlines($r);
	if ($n != 1) {
		DBExec($c, "rollback work", "DBBkpDelete(rollback)");
		LogLevel("Unable to delete a bkp. ".
			"(bkp=$number, site=$site, contest=$contest, user=$user)",1);
		return false;
	}
	$temp = DBRow($r, 0);

	if (DB_lo_unlink($c, $temp['oid']) === false) {
		DBExec($c, "rollback work", "DBBkpDelete(rollback-import)");
		LOGError("Unable to delete a large object (user=$user, contest=$contest, site=$site, number=$number).");
		MSGError("problem deleting bkp from database. Contact an admin.");
		exit;
	}
	if (strlen($adm)>0) $str = "deleted by " . $adm;
        else $str="deleted";
	DBExec($c, "update bkptable set bkpstatus='$str', updatetime=" . 
		   time()." where contestnumber=$contest and bkpnumber=$number and sitenumber=$site",
		"DBBkpDelete(update bkp)");

	DBExec($c, "commit work", "DBBkpDelete(commit)");
	LOGLevel("Bkp deleted (bkp=$number, site=$site, contest=$contest, user=$user).", 3);
	return true;
}

function DBNewBkp($contest, $site, $user, $filename, $filepath, $size) {
	$c = DBConnect();
	DBExec($c, "begin work", "DBNewBkp(transaction)");
        DBExec($c, "lock table bkptable");
	$sql = "select count(*) as n from " .
		"bkptable where sitenumber=$site and contestnumber=$contest and usernumber=$user and bkpstatus='active'";
	$r = DBExec($c, $sql, "DBNewBkp(get bkp of user)");
	if (DBnlines($r) != 1) {
		DBExec($c, "rollback work", "DBNewBkp(rollback-toomanyerror)");
		LOGError("Error in bkp table. SQL=(" . $sql . ")");
		MSGError("Error in bkp table.");
		exit;
	}
	$a = DBRow($r,0);
        if($a['n']>100) {
		DBExec($c, "rollback work", "DBNewBkp(rollback-toomany)");
		LOGError("Too many bkps from user=$user, site=$site, contest=$contest.",2);
		MSGError("Too many bkp files. Try remove some of them before uploading another.");
		return false;
	}

	$sql = "select max(bkpnumber) as nextbkp from " .
		"bkptable where sitenumber=$site and contestnumber=$contest";
	$r = DBExec($c, $sql, "DBNewBkp(get bkp for update)");
	if (DBnlines($r) != 1) {
		DBExec($c, "rollback work", "DBNewBkp(rollback-max)");
		LOGError("Error in bkp table. SQL=(" . $sql . ")");
		MSGError("Error in bkp table.");
		exit;
	}
	$a = DBRow($r,0);
	$t = time();
	$n = $a["nextbkp"] + 1;

	if (($oid = DB_lo_import($c, $filepath)) === false) {
		DBExec($c, "rollback work", "DBNewBkp(rollback-import)");
		LOGError("Unable to create a large object for file $filepath.");
		MSGError("problem importing bkp to database. Contact an admin now!");
		exit;
	}

	DBExec($c, "INSERT INTO bkptable (contestnumber, sitenumber, bkpnumber, usernumber, bkpdate, bkpfilename, bkpdata, bkpstatus, bkpsize) " .
		"VALUES ($contest, $site, $n, $user, $t, '$filename', $oid, 'active', $size)",
	      "DBNewBkp(insert bkp)");

	DBExec($c, "commit work", "DBNewBkp(commit)");
	LOGLevel("User $user submitted a bkp (#$n) on site #$site " .
		"(filename=$filename, contest=$contest).",2);

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
function DBUserBkps($contest,$site,$user) {
	$c = DBConnect();
        $st = "select distinct r.bkpnumber as number, r.usernumber as usernumber, r.sitenumber as usersitenumber, r.bkpdate as timestamp, " .
                                        "r.bkpstatus as status, r.bkpfilename as filename, r.bkpdata as oid, r.bkpsize as size " .
                             "from bkptable as r " .
                             "where r.contestnumber=$contest";
        if($site>0 || $user>0)
            $st = $st . " and r.sitenumber=$site and r.usernumber=$user and r.bkpstatus='active'";
        $st = $st . " order by r.bkpnumber";
	$r = DBExec($c, $st, "DBUserBkps(get bkps)");
	$n = DBnlines($r);

	$a = array();
	for ($i=0;$i<$n;$i++) {
		$a[$i] = DBRow($r,$i);
	}
	return $a;
}
// eof
?>
