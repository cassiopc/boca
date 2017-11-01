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
if(isset($_SESSION["locr"]) && isset($_SESSION["loc"]) && !is_readable($_SESSION["locr"] . '/private/conf.php')) {
	MSGError('Permission problems in ' . $_SESSION["locr"] . '/private/conf.php - the file must be readable to the user running the web server');
	exit;
}
require_once('hex.php');
require_once('globals.php');
require_once('private/conf.php');

//para compatibilidade com versoes velhas e novas do php, varias das funcoes foram
//colocadas aqui para guarantir a portabilidade. Infelizmente algumas trocas de nomes
//e parametros aconteceram com a versao 4.2.0 do php.
function DB_lo_open($conn, $file, $mode) {
	if (strcmp(phpversion(),'4.2.0')<0)
		return pg_loopen ($conn, $file, $mode);
	else
		return pg_lo_open ($conn, $file, $mode);
}
function DB_lo_read_tobrowser($contest,$id,$c=null) {
  $str = DB_lo_read($contest,$id,-1,$c);
  echo $str;
  return true;
}

function DB_lo_read($contest,$id,$s=-1,$c=null) {
	if (strcmp(phpversion(),'4.2.0')<0) {
		if($s<0) {
			$str='';
			while (($buf = pg_loread ($id, 1000000)) != false) $str .= $buf;
		} else
			$str = pg_loread ($id, $s);
	}
	else {
		if($s<0) {
			$str='';
			while (($buf = pg_lo_read ($id, 1000000)) != false) $str .= $buf;
		} else
			$str = pg_lo_read ($id, $s);
	}
	if(($str2 = DB_unlock($contest,$str,$c))===false) return $str;
	return $str2;
}
function DB_unlock($contest,$str,$c=null) {
	if($contest <= 0) return false;
	if(($ct = DBContestInfo($contest,$c)) == null) return false;
	if(strlen($ct['contestunlockkey']) > 1) {
		$ar=explode(',',$ct['contestkeys']);
		foreach($ar as $key) {
			if(substr($key,0,10) == substr($str,0,10)) {
				$pass=decryptData(substr($key,15),$ct['contestunlockkey'],'db_unlock');
				if(substr($pass,0,5) != '#####') continue;
				$str2=decryptData($str,$pass,'db_unlock2');
				if($str2=='') continue;
				return $str2;
			}
		}
	}
	return false;
}

function DB_lo_import($conn, $file) {
	if (strcmp(phpversion(),'4.2.0')<0)
		return pg_loimport ($file, $conn);
	else
		return pg_lo_import ($conn, $file);
}
function DB_lo_import_text($conn, $text) {
  if(($oid = DB_lo_create($conn))===false) return false;
  if(($handle = DB_lo_open($conn, $oid, "w"))===false) return false;
  if(DB_lo_write($handle, $text)===false) $oid=false;
  DB_lo_close($handle);
  return $oid;
}

function DB_lo_export($contest, $conn, $oid, $file) {
	if (strcmp(phpversion(),'4.2.0')<0)
		$stat= pg_loexport ($oid, $file, $conn);
	else
		$stat= pg_lo_export ($oid, $file, $conn);
	if($stat===false) return false;
	if(!is_readable($file)) return false;
	if(($str=DB_unlock($contest,file_get_contents($file),$conn))!==false) {
		file_put_contents($file,$str);
		return 2;
	}
	return 1;
}
function DB_lo_close($id) {
	if (strcmp(phpversion(),'4.2.0')<0)
		return pg_loclose ($id);
	else
		return pg_lo_close ($id);
}
function DB_lo_create($conn) {
	if (strcmp(phpversion(),'4.2.0')<0)
		return pg_locreate ($conn);
	else
		return pg_lo_create ($conn);
}
function DB_lo_write($fp, $data) {
	if (strcmp(phpversion(),'4.2.0')<0)
		return pg_lowrite ($fp, $data);
	else
		return pg_lo_write ($fp, $data);
}
function DB_lo_unlink($conn, $data) {
	if(($fp = DB_lo_open ($conn, $data, "r"))===false) return false;
	DB_lo_close($fp);
	if (strcmp(phpversion(),'4.2.0')<0)
		return pg_lounlink ($conn, $data);
	else
		return pg_lo_unlink ($conn, $data);
}
function DB_pg_exec($conn, $data) {
	if (strcmp(phpversion(),'4.2.0')<0)
		return pg_exec ($conn, $data);
	else
		return pg_query ($conn, $data);
}
function escape_string($str) {
	if(strcmp(phpversion(),'4.2.0')<0)
		return addslashes($str);
	else
		return pg_escape_string($str);
}

//abrir conexao com o banco de dados
//temos aqui um problema de seguranca. A senha/usuario para acesso ao postgresql
//fica guardada em texto plano em um arquivo com permissao de acesso para todos com shell 
//na maquina. O melhor a fazer eh configurar o postgres para permitir acesso no banco de
//dados do boca ao usuario dono desses arquivos php. Esses problemas nao existem quando
//shell no servidor do boca+postgres eh restrito.
function DBConnect($forcenew=false) {
        // procura por arquivo com as configuracao. se nao achar, usa padroes
	$conf = globalconf();
	if($conf["dblocal"]=="true") {
		if($forcenew)
			$conn = @pg_connect ("connect_timeout=10 dbname=".$conf["dbname"]." user=".$conf["dbuser"].
								 " password=".$conf["dbpass"],PGSQL_CONNECT_FORCE_NEW);
		else
			$conn = @pg_connect ("connect_timeout=10 dbname=".$conf["dbname"]." user=".$conf["dbuser"].
								 " password=".$conf["dbpass"]);
	} else {
		if($forcenew)
			$conn = @pg_connect ("connect_timeout=10 host=".$conf["dbhost"]." port=".$conf["dbport"]." dbname=".$conf["dbname"].
								 " user=".$conf["dbuser"]." password=".$conf["dbpass"],PGSQL_CONNECT_FORCE_NEW);
		else
			$conn = @pg_connect ("connect_timeout=10 host=".$conf["dbhost"]." port=".$conf["dbport"]." dbname=".$conf["dbname"].
								 " user=".$conf["dbuser"]." password=".$conf["dbpass"]);
	}
	if (!$conn) {
		LOGError("Unable to connect to database (${conf["dbhost"]},${conf["dbname"]},${conf["dbuser"]}).");
		MSGError("Unable to connect to database (${conf["dbhost"]}:${conf["dbport"]},${conf["dbname"]},${conf["dbuser"]}). ".
			"Is it running? Is the DB password in conf.php correct?");
		exit;
	}
	if(isset($conf["dbclientenc"]))
		DBExecNonStop($conn,"SET NAMES '${conf["dbclientenc"]}'","set client encoding");
	return $conn;
}
//fecha a conexao com o banco (isso nao eh realmente necessario, ja que o php/apache cuidam do servico)
function DBClose($c) {
	pg_close($c);
}
//executar instrucao no banco de dados, sem finalizar a execucao do php
//em caso de erro (mas com uma chamada para a funcao LOGLevel) 
//$conn eh a conexao com o banco
//$sql eh a instrucao sql
//$txt eh um pequeno texto descrevendo o que esta sendo feito no sql
function DBExecNonStop($conn,$sql,$txt='') {
	if($txt=='') $txt='unknown at '. getFunctionName();
	$result = @DB_pg_exec ($conn, $sql);
	if (!$result) {
		LOGError("Unable to exec SQL in the database ($txt). " .
                         " Error=(" . pg_errormessage($conn) . ")");
	}
	return $result;
}
//executar instrucao no banco de dados, finalizando a execucao do php
//em caso de erro (alem da chamada para a funcao LOGLevel e alerta na tela) 
//$conn eh a conexao com o banco
//$sql eh a instrucao sql
//$txt eh um pequeno texto descrevendo o que esta sendo feito no sql
function DBExec($conn,$sql,$txt='') {
	if($txt=='') $txt='unknown at '. getFunctionName();
//	LOGLevel("DBExec: " . $sql, 3, false);
	$result = DB_pg_exec ($conn, $sql);
	if (!$result) {
		LOGError("Unable to exec SQL in the database ($txt). " .
				 " SQL=(" . sanitizeText(str_replace("\n", " ",$sql)) . "Error=(" . sanitizeText(str_replace("\n", " ",pg_errormessage($conn))) . ")");
		MSGError("Unable to exec SQL in the database ($txt). Aborting.");
		exit;
	}
	return $result;
}
//executar instrucao no banco de dados, sem finalizar a execucao do php
//mesmo em caso de erro (apenas uma janela de aviso tentara ser mostrada ao usuario
//$conn eh a conexao com o banco
//$sql eh a instrucao sql
//$txt eh um pequeno texto descrevendo o que esta sendo feito no sql
function DBExecNoSQLLog($sql,$txt='') {
	if($txt=='') $txt='unknown at '.getFunctionName();
	$conn = DBConnect(true);
	$result = DB_pg_exec ($conn, $sql);
	pg_close($conn);
	if (!$result) {
		MSGError("Unable to exec SQL in the database ($txt).");
	}
	return $result;
}
//devolve o numero de linhas da consulta
function DBnlines ($result) {
	return pg_numrows ($result);
}
//pega uma linha da consulta no formato de array
function DBRow ($r, $i) {
	return pg_fetch_array ($r, $i, PGSQL_ASSOC);
}
//faz a consulta e pega uma linha da consulta no formato de array
//$sql eh a consulta em sql
//$i eh a linha desejada, comecando de zero
//$txt eh uma descricao da consulta sendo feita
function DBGetRow ($sql,$i,$c=null,$txt='') {
	if($txt=='') $txt='unknown at '.getFunctionName();
	if($c==null)
		$c = DBConnect();
	$r = DBExec($c,$sql,$txt);
	if (DBnlines($r) < $i+1) return null;
	$a = DBRow ($r, $i);
	if (!$a) {
	  DBClose($c);
	  LOGError("Unable to get row $i from a query ($txt). SQL=(" . $sql . ")");
	  MSGError("Unable to get row from query ($txt).");
	  exit;
	}
	return $a;
}
function DBDropDatabase() {
	$conf = globalconf();
	if($conf["dblocal"]=="true")
		$conn = pg_connect ("connect_timeout=10 dbname=template1 user=".$conf["dbsuperuser"]." password=".$conf["dbsuperpass"]);
	else
		$conn = pg_connect ("connect_timeout=10 host=".$conf["dbhost"]." port=".$conf["dbport"]." dbname=template1 user=".$conf["dbsuperuser"].
				   " password=".$conf["dbsuperpass"]);
	 if(!$conn) {
		 MSGError("Unable to connect to template1 as ".$conf["dbsuperuser"]);
		 exit;
	 }
	 $r = DBExecNonStop($conn, "drop database ${conf["dbname"]}", "DBDropDatabase(drop)");
}
// pg_connect ("options='--client_encoding=UTF8' dbname=template1 ... ????
function DBCreateDatabase() {
	$conf = globalconf();
	if($conf["dblocal"]=="true")
		$conn = pg_connect ("connect_timeout=10 dbname=template1 user=".$conf["dbsuperuser"]." password=".$conf["dbsuperpass"]);
	else
		$conn = pg_connect ("connect_timeout=10 host=".$conf["dbhost"]." port=".$conf["dbport"]." dbname=template1 user=".$conf["dbsuperuser"].
				   " password=".$conf["dbsuperpass"]);

	 if(!$conn) {
		 MSGError("Unable to connect to template1 as ".$conf["dbsuperuser"]);
		 exit;
	 }
	 if(isset($conf["dbencoding"]))
		 $r = DBExec($conn, "create database ${conf["dbname"]} with encoding = '${conf["dbencoding"]}'", "DBCreateDatabase(create)");
	 else
		 $r = DBExec($conn, "create database ${conf["dbname"]} with encoding = 'UTF8'", "DBCreateDatabase(create)");
}

function DBcrc($contest,$id, $c=null) {
	$docommit=false;
	if($c == null) {
		$docommit=true;
		$c = DBConnect();
		DBExec($c, "begin work", "DBcrc(begin)");
	}
	if(($f = DB_lo_open($c, $id, "r")) === false) {
		if($docommit)
			DBExec($c, "commit work", "DBcrc(commit)");
        // just to return a unique string that will not match any other...
		return "no-HASH-" . rand() . "-" . rand() . "-" . time();
	}
	$str = DB_lo_read($contest,$f,-1,$c);
	DB_lo_close($f);
	if($docommit)
		DBExec($c, "commit work", "DBcrc(commit)");
	return myshorthash($str);
}

require_once('flog.php');
require_once('fclar.php');
require_once('frun.php');
require_once('ftask.php');
require_once('fproblem.php');
require_once('flanguage.php');
require_once('fscore.php');
require_once('fanswer.php');
require_once('fcontest.php');
require_once('fbkp.php');
require_once('fextdata.php');
require_once('fzip.php');
require_once('fballoon.php');
// eof
?>
