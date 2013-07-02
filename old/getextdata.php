<?php
//////////////////////////////////////////////////////////////////////////////////////////
//BOCA Online Contest Administrator. Copyright (c) 2003-2004 Cassio Polpo de Campos.
//It may be distributed under the terms of the Q Public License version 1.0. A copy of the
//license can be found with this software or at http://www.opensource.org/licenses/qtpl.php
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
//INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
//PURPOSE AND NONINFRINGEMENT OF THIRD PARTY RIGHTS. IN NO EVENT SHALL THE COPYRIGHT HOLDER
//OR HOLDERS INCLUDED IN THIS NOTICE BE LIABLE FOR ANY CLAIM, OR ANY SPECIAL INDIRECT OR
//CONSEQUENTIAL DAMAGES, OR ANY DAMAGES WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR
//PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING
//OUT OF OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
///////////////////////////////////////////////////////////////////////////////////////////

//retorna ip do cliente
function getIP() {
        if (getenv("HTTP_CLIENT_IP"))
                $ip = getenv("HTTP_CLIENT_IP");
        else
        if(getenv("HTTP_X_FORWARDED_FOR"))
                $ip = getenv("HTTP_X_FORWARDED_FOR");
        else
        if(getenv("REMOTE_ADDR"))
                $ip = getenv("REMOTE_ADDR");
        else
                $ip = "UNKNOWN";
        $ip = strtok ($ip, ",");
        return $ip;
}
//para compatibilidade com versoes velhas e novas do php
function DB_lo_open($conn, $file, $mode) {
	if (strcmp(phpversion(),'4.2.0')<0)
		return pg_loopen ($conn, $file, $mode);
	else
		return pg_lo_open ($conn, $file, $mode);
}
function DB_lo_read_all($id) {
	if (strcmp(phpversion(),'4.2.0')<0)
		return pg_loreadall ($id);
	else
		return pg_lo_read_all ($id);
}
function DB_lo_import($conn, $file) {
	if (strcmp(phpversion(),'4.2.0')<0)
		return pg_loimport ($file, $conn);
	else
		return pg_lo_import ($conn, $file);
}
function DB_lo_close($id) {
	if (strcmp(phpversion(),'4.2.0')<0)
		return pg_loclose ($id);
	else
		return pg_lo_close ($id);
}
function DB_lo_unlink($c,$id) {
	if (strcmp(phpversion(),'4.2.0')<0)
		return pg_lounlink ($id,$c);
	else
		return pg_lo_unlink ($c,$id);
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
function DB_lo_read($fp, $len) {
	if (strcmp(phpversion(),'4.2.0')<0)
		return pg_loread ($fp, $len);
	else
		return pg_lo_read ($fp, $len);
}

//abrir conexao com o banco de dados
function DBConnect() {
	$dbconn="host=localhost dbname=bocadb user=bocauser password=boca";
	$conn = pg_connect ($dbconn);
	if (!$conn) {
		echo "Unable to connect to local database\n";
		return null;
	}
	return $conn;
}
function DBExtConnect($c, $contest, $site) {
        $r = DBExec($c, "select * from sitetable where sitenumber=$site and contestnumber=$contest");
	if (DBnLines($r)==0) {
                echo "Unable to find the site in the database (site=$site, contest=$contest).\n";
		exit;
	}
	$st = DBRow($r,0);

	if(($f = file("sqlpass.php"))===false) $pass="boca";
	else $pass=trim($f[1]);

	// gambiarra para testar se a conexao esta de pe
	$fp = fsockopen ($st["siteip"], 5432, $errno, $errstr, 10);
	if (!$fp) {
    		echo "$errstr ($errno). Aborting this connection (ip=".$st["siteip"].").\n";
		return null;
	}
    	fclose ($fp);
	// se a conexao cair entre o teste acima e o connect abaixo, esse script pode demorar ate
	// dar o timeout (no meu teste foram cerca de 3min para cada timeout)

	$conn = pg_connect ("host=" . $st["siteip"] . " dbname=bocadb user=bocauser password=$pass");
	if (!$conn) {
		echo "Unable to connect to site $site (ip=".$st["siteip"].")\n";
		return null;
	}
	echo "Connected to " .$st["siteip"].". Let's exchange data...\n";
	return $conn;
}

function GetExternalData ($contest) {
	$c = DBConnect();
        if ($c==null) return;
	$r = DBExec($c, "select * from contesttable where contestnumber=$contest");
	if (DBnLines($r)==0) {
		echo "Unable to find the contest $contest in the database.\n";
		exit;
	}
	$ct = DBRow($r,0);
	$localsite = $ct["contestlocalsite"];
	$mainsite = $ct["contestmainsite"];

	if ($mainsite!=$localsite) {
          $r = DBExec($c, "select * from sitetable where sitenumber=".$ct["contestmainsite"]." and contestnumber=$contest");
  	  if (DBnLines($r)==0) {
                echo "Unable to find the main site in the database (site=$site, contest=$contest).\n";
		exit;
	  }
	  $st = DBRow($r,0);
	  if (($t = DBExtConnect($c, $contest, $st["sitenumber"]))!=null) {
		DBExec($c, "begin work");

		// sincronizando contest
		$r = DBExec ($t, "select * from contesttable where contestnumber=$contest");
		$n = DBnLines ($r);
		$atual = DBRow($r,0);
		if ($atual["updatetime"]=="") $atual["updatetime"]=time();
		DBExec ($c, "update contesttable set ".
					"contestduration=".$atual["contestduration"].",".
					"conteststartdate=".$atual["conteststartdate"].",".
					"contestmaxfilesize=".$atual["contestmaxfilesize"].",".
					"contestactive='".$atual["contestactive"]."',".
					//"contestmainsite=".$atual["contestmainsite"].",".
					"contestname='".escape_string($atual["contestname"])."',".
					"contestlastmileanswer=".$atual["contestlastmileanswer"].",".
					"contestlastmilescore=".$atual["contestlastmilescore"].",".
					"contestpenalty=".$atual["contestpenalty"].",".
					"updatetime=".$atual["updatetime"].
				" where contestnumber=$contest and updatetime<".$atual["updatetime"]);
		
		// sincronizando answers
		$r = DBExec ($t, "select * from answertable where contestnumber=$contest");
		$rr = DBExec ($c, "select * from answertable where contestnumber=$contest for update");
		$n = DBnLines ($r);
		$nn = DBnLines ($rr);
		echo "answers: external(site=$mainsite, reading=$mainsite)=$n, local=$nn\n";
		for ($k=0; $k<$nn; $k++) $needed[$k]=false;
		for ($j=0;$j<$n;$j++) {
			$la = DBRow($r,$j);
			for ($k=0; $k<$nn; $k++) {
				$aqui = DBRow($rr,$k);
				if ($la["answernumber"]==$aqui["answernumber"] && $la["contestnumber"]==$aqui["contestnumber"]) {
					$needed[$k]=true;
					if ($la["updatetime"]>$aqui["updatetime"])
						$situacao[$j]="atualizar";
					else
						$situacao[$j]="ok";
					break;
				}
			}
			if ($k>=$nn) $situacao[$j]="inserir";
		}
		$del=0; $upd=0; $ins=0;
		for ($k=0; $k<$nn; $k++) {
			$atual = DBRow($rr, $k);
			if (!$needed[$k]) {
				DBExec($c, "delete from answertable where contestnumber=$contest and answernumber=".$atual["answernumber"]);
				$del++;
			}
		}
		for ($j=0;$j<$n;$j++) {
			$atual = DBRow($r,$j);
			if ($atual["updatetime"]=="") $atual["updatetime"]=time();
			if ($situacao[$j]=="atualizar") {
				$upd++;
				DBExec ($c, "update answertable set ".
						"runanswer='".$atual["runanswer"]."',".
						"yes='".$atual["yes"]."',".
						"fake='".$atual["fake"]."',".
						"updatetime=".$atual["updatetime"].
						" where contestnumber=$contest and answernumber=".$atual["answernumber"].
						" and updatetime<".$atual["updatetime"]);
			} else if ($situacao[$j]=="inserir") {
				$ins++;
				DBExec($c,"insert into answertable (contestnumber, answernumber, runanswer, yes, fake, updatetime) values (" .
					$contest.",".$atual["answernumber"].",'".$atual["runanswer"]."','".$atual["yes"]."',".
					"'".$atual["fake"]."',".$atual["updatetime"].")");
			}
		}
		echo "   deletions=$del, updates=$upd, insertions=$ins\n\n";

		// sincronizando languages
		$r = DBExec ($t, "select * from langtable where contestnumber=$contest");
		$rr = DBExec ($c, "select * from langtable where contestnumber=$contest for update");
		$n = DBnLines ($r);
		$nn = DBnLines ($rr);
		echo "languages: external(site=$mainsite, reading=$mainsite)=$n, local=$nn\n";
		for ($k=0; $k<$nn; $k++) $needed[$k]=false;
		for ($j=0;$j<$n;$j++) {
			$la = DBRow($r,$j);
			for ($k=0; $k<$nn; $k++) {
				$aqui = DBRow($rr,$k);
				if ($la["langnumber"]==$aqui["langnumber"] && $la["contestnumber"]==$aqui["contestnumber"]) {
					$needed[$k]=true;
					if ($la["updatetime"]>$aqui["updatetime"])
						$situacao[$j]="atualizar";
					else
						$situacao[$j]="ok";
					break;
				}
			}
			if ($k>=$nn) $situacao[$j]="inserir";
		}
		$del=0; $upd=0; $ins=0;
		for ($k=0; $k<$nn; $k++) {
			$atual = DBRow($rr, $k);
			if (!$needed[$k]) {
				DBExec($c, "delete from langtable where contestnumber=$contest and langnumber=".$atual["langnumber"]);
				$del++;
			}
		}
		for ($j=0;$j<$n;$j++) {
			$atual = DBRow($r,$j);
			if ($atual["updatetime"]=="") $atual["updatetime"]=time();
			if ($situacao[$j]=="atualizar") {
				$upd++;
				DBExec ($c, "update langtable set ".
						"langname='".escape_string($atual["langname"])."',".
						"updatetime=".$atual["updatetime"].
						" where contestnumber=$contest and langnumber=".$atual["langnumber"].
						" and updatetime<".$atual["updatetime"]);
			} else if ($situacao[$j]=="inserir") {
				$ins++;
				DBExec($c,"insert into langtable (contestnumber, langnumber, langname, updatetime) values ($contest,".$atual["langnumber"].
					",'".escape_string($atual["langname"])."',".$atual["updatetime"].")");
			}
		}
		echo "   deletions=$del, updates=$upd, insertions=$ins\n\n";

		// sincronizando problems
		$r = DBExec ($t, "select * from problemtable where contestnumber=$contest");
		$rr = DBExec ($c, "select * from problemtable where contestnumber=$contest for update");
		$n = DBnLines ($r);
		$nn = DBnLines ($rr);
		echo "problems: external(site=$mainsite, reading=$mainsite)=$n, local=$nn\n";
		for ($k=0; $k<$nn; $k++) $needed[$k]=false;
		for ($j=0;$j<$n;$j++) {
			$la = DBRow($r,$j);
			for ($k=0; $k<$nn; $k++) {
				$aqui = DBRow($rr,$k);
				if ($la["problemnumber"]==$aqui["problemnumber"] && $la["contestnumber"]==$aqui["contestnumber"]) {
					$needed[$k]=true;
					if ($la["updatetime"]>$aqui["updatetime"])
						$situacao[$j]="atualizar";
					else
						$situacao[$j]="ok";
					break;
				}
			}
			if ($k>=$nn) $situacao[$j]="inserir";
		}
		$del=0; $upd=0; $ins=0;
		for ($k=0; $k<$nn; $k++) {
			$atual = DBRow($rr, $k);
			if (!$needed[$k]) {
				DBExec($c, "delete from problemtable where contestnumber=$contest and problemnumber=".$atual["problemnumber"]);
				$del++;
			}
		}
		for ($j=0;$j<$n;$j++) {
			$atual = DBRow($r,$j);
			if ($atual["updatetime"]=="") $atual["updatetime"]=time();
			if ($situacao[$j]=="inserir") {
				$ins++; $upd--;
				DBExec($c,"insert into problemtable (contestnumber, problemnumber, problemname, updatetime) values ($contest,".
					$atual["problemnumber"].",'".escape_string($atual["problemname"])."', 0)");
			}
			if ($situacao[$j]=="atualizar" || $situacao[$j]=="inserir") {
				$upd++;
				$p = DBRow($r2, 0);
				if ($p["probleminputfile"]>0) DB_lo_unlink($c, $p["probleminputfile"]);
				if ($p["problemsolfile"]>0) DB_lo_unlink($c, $p["problemsolfile"]);
				DBExec($t, "begin work");
				if ($atual["probleminputfile"]=="") $inputfile="null";
				else {
					$in = DB_lo_open($t, $atual["probleminputfile"], "r");
					if (!$in) $inputfile="null";
					else {
						$inputfile = DB_lo_create ($c);
						$out = DB_lo_open ($c, $inputfile, "w");
						while (($buf = DB_lo_read ($in, 100000)) != false)
							DB_lo_write ($out, $buf);
						DB_lo_close ($out);
						DB_lo_close ($in);
					}
				}
				if ($atual["problemsolfile"]=="") $solfile="null";
				else {
					$in = DB_lo_open($t, $atual["problemsolfile"], "r");
					if (!$in) $solfile="null";
					else {
						$solfile = DB_lo_create ($c);
						$out = DB_lo_open ($c, $solfile, "w");
						while (($buf = DB_lo_read ($in, 100000)) != false)
							DB_lo_write ($out, $buf);
						DB_lo_close ($out);
						DB_lo_close ($in);
					}
				}
				DBExec ($t, "commit work");

				DBExec ($c, "update problemtable set ".
						"problemname='".escape_string($atual["problemname"])."',".
						"problemfullname='".escape_string($atual["problemfullname"])."',".
						"problembasefilename='".escape_string($atual["problembasefilename"])."',".
						"probleminputfilename='".escape_string($atual["probleminputfilename"])."',".
						"problemsolfilename='".escape_string($atual["problemsolfilename"])."',".
						"fake='".$atual["fake"]."',".
						"probleminputfile=".$inputfile.",".
						"problemsolfile=".$solfile.",".
						"updatetime=".$atual["updatetime"].
					" where contestnumber=$contest and problemnumber=".$atual["problemnumber"].
					" and updatetime<".$atual["updatetime"]);
			}
		}
		echo "   deletions=$del, updates=$upd, insertions=$ins\n\n";
		DBExec($c, "commit work");
	  }

	}

	$r = DBExec ($c, "select * from sitetable where contestnumber=$contest");
	$n = DBnlines($r);
	if ($n == 0) {
		echo "Unable to find sites in the database.\n";
		exit;
	}
	$st = array();
	for ($i=0;$i<$n;$i++) {
		$st[$i] = DBRow($r,$i);
	}
	
	for ($i=0; $i<count($st); $i++) {
		$site=$st[$i]["sitenumber"];
		if ($site==$ct["contestlocalsite"]) continue;
		if (($t = DBExtConnect($c, $contest, $site))==null) continue;
		DBExec($c, "begin work");

		// sincronizando site
		$r = DBExec ($t, "select * from sitetable where contestnumber=$contest and sitenumber=$site");
		$rr = DBExec ($c, "select * from sitetable where contestnumber=$contest and sitenumber=$site for update");
		$n = DBnLines ($r);
		$nn = DBnLines ($rr);
		$atual = DBRow($r,0);
		$p = DBRow($rr,0);
//		if ($p["updatetime"]<$atual["updatetime"]) {
			if ($atual["sitestartdate"]=="") $atual["sitestartdate"]="null";
			if ($atual["sitelastmilescore"]=="") $atual["sitelastmilescore"]="null";
			if ($atual["sitelastmileanswer"]=="") $atual["sitelastmileanswer"]="null";
			if ($atual["siteenddate"]=="") $atual["siteenddate"]="null";
			if ($atual["siteendeddate"]=="") $atual["siteendeddate"]="null";
			if ($atual["siteautoend"]!="t") $atual["siteautoend"]="f";
			if ($atual["updatetime"]=="") $atual["updatetime"]=time();
			DBExec ($c, "update sitetable set ".
				//"siteip='".escape_string($atual["siteip"])."',".
				"sitename='".escape_string($atual["sitename"])."',".
				"siteactive='".escape_string($atual["siteactive"])."',".
				"sitepermitlogins='".escape_string($atual["sitepermitlogins"])."',".
				"sitestartdate=".$atual["sitestartdate"].",".
				"sitelastmilescore=".$atual["sitelastmilescore"].",".
				"sitelastmileanswer=".$atual["sitelastmileanswer"].",".
				"siteenddate=".$atual["siteenddate"].",".
				"siteendeddate=".$atual["siteendeddate"].",".
				"siteautoend='".escape_string($atual["siteautoend"])."',".
				"sitejudging='".escape_string($atual["sitejudging"])."',".
				"siteglobalscore='".escape_string($atual["siteglobalscore"])."',".
				"sitescorelevel=".$atual["sitescorelevel"].",".
				"sitenextuser=".$atual["sitenextuser"].",".
				"sitenextclar=".$atual["sitenextclar"].",".
				"sitenextrun=".$atual["sitenextrun"].",".
				"updatetime=".$atual["updatetime"].
				" where contestnumber=$contest and sitenumber=".$atual["sitenumber"]);
//				." and updatetime<".$atual["updatetime"]);
//		}

		// sincronizando users
		$r = DBExec ($t, "select * from usertable where contestnumber=$contest and usersitenumber=$site");
		$rr = DBExec ($c, "select * from usertable where contestnumber=$contest and usersitenumber=$site");
		$n = DBnLines ($r);
		$nn = DBnLines ($rr);
		echo "users: external(site=$site, reading=$site)=$n, local=$nn\n";
		for ($k=0; $k<$nn; $k++) $needed[$k]=false;
		for ($j=0;$j<$n;$j++) {
			$la = DBRow($r,$j);
			for ($k=0; $k<$nn; $k++) {
				$aqui = DBRow($rr,$k);
				if ($la["usernumber"]==$aqui["usernumber"] && $la["contestnumber"]==$aqui["contestnumber"] &&
					$la["usersitenumber"]==$aqui["usersitenumber"]) {
					$needed[$k]=true;
					if ($la["updatetime"]>$aqui["updatetime"])
						$situacao[$j]="atualizar";
					else
						$situacao[$j]="ok";
					break;
				}
			}
			if ($k>=$nn) $situacao[$j]="inserir";
		}
		$del=0;
		for ($k=0; $k<$nn; $k++) {
			$atual = DBRow($rr, $k);
			if (!$needed[$k]) {
				DBExec($c, "delete from usertable where contestnumber=$contest and usersitenumber=$site and usernumber=".
					$atual["usernumber"]);
				$del++;
			}
		}
		$upd=0; $ins=0;
		for ($j=0;$j<$n;$j++) {
			$atual = DBRow($r,$j);
			if ($atual["userlastlogin"]=="") $atual["userlastlogin"]="null";
			if ($atual["userlastlogout"]=="") $atual["userlastlogout"]="null";
			if ($atual["updatetime"]=="") $atual["updatetime"]=time();
			if ($situacao[$j]=="atualizar") {
				DBExec ($c, "update usertable set ".
						"username='".escape_string($atual["username"])."',".
						"userfullname='".escape_string($atual["userfullname"])."',".
						"userdesc='".escape_string($atual["userdesc"])."',".
						"usertype='".escape_string($atual["usertype"])."',".
						"userenabled='".$atual["userenabled"]."',".
						"usermultilogin='".$atual["usermultilogin"]."',".
						"userpassword='".$atual["userpassword"]."',".
						"userip='".$atual["userip"]."',".
						"userlastlogin=".$atual["userlastlogin"].",".
						"userlastlogout=".$atual["userlastlogout"].",".
						"usersession='".$atual["usersession"]."',".
						"userpermitip='".$atual["userpermitip"]."',".
						"updatetime=".$atual["updatetime"].
					" where contestnumber=$contest and usersitenumber=".$atual["usersitenumber"].
					" and usernumber=".$atual["usernumber"]." and updatetime<".$atual["updatetime"]);
				$upd++;
			} else if ($situacao[$j]=="inserir") {
				DBExec ($c, "insert into usertable (username, userfullname, userdesc, usertype, userenabled, usermultilogin, ".
						"userpassword, userip, userlastlogin, userlastlogout, usersession, userpermitip, updatetime, ".
						"contestnumber, usersitenumber, usernumber) values (".
						"'".escape_string($atual["username"])."',".
						"'".escape_string($atual["userfullname"])."',".
						"'".escape_string($atual["userdesc"])."',".
						"'".$atual["usertype"]."',".
						"'".$atual["userenabled"]."',".
						"'".$atual["usermultilogin"]."',".
						"'".$atual["userpassword"]."',".
						"'".$atual["userip"]."',".
						$atual["userlastlogin"].",".
						$atual["userlastlogout"].",".
						"'".$atual["usersession"]."',".
						"'".$atual["userpermitip"]."',".
						$atual["updatetime"].",$contest,".$atual["usersitenumber"].",".$atual["usernumber"].")");
				$ins++;
			}
		}
		echo "   deletions=$del, updates=$upd, insertions=$ins\n\n";

		// sincronizando clars
		$r = DBExec ($t, "select * from clartable where contestnumber=$contest and clarsitenumber=$site");
		$rr = DBExec ($c, "select * from clartable where contestnumber=$contest and clarsitenumber=$site");
		$n = DBnLines ($r);
		$nn = DBnLines ($rr);
		echo "clars: external(site=$site, reading=$site)=$n, local=$nn\n";
		for ($k=0; $k<$nn; $k++) $needed[$k]=false;
		for ($j=0;$j<$n;$j++) {
			$la = DBRow($r,$j);
			for ($k=0; $k<$nn; $k++) {
				$aqui = DBRow($rr,$k);
				if ($la["clarnumber"]==$aqui["clarnumber"] && $la["contestnumber"]==$aqui["contestnumber"] &&
					$la["clarsitenumber"]==$aqui["clarsitenumber"]) {
					$needed[$k]=true;
					if ($la["updatetime"]>$aqui["updatetime"])
						$situacao[$j]="atualizar";
					else
						$situacao[$j]="ok";
					break;
				}
			}
			if ($k>=$nn) $situacao[$j]="inserir";
		}
		$del=0;
		for ($k=0; $k<$nn; $k++) {
			$atual = DBRow($rr, $k);
			if (!$needed[$k]) {
				DBExec($c, "delete from clartable where contestnumber=$contest and clarsitenumber=$site and clarnumber=".
					$atual["clarnumber"]);
				$del++;
			}
		}
		$upd=0; $ins=0;
		for ($j=0;$j<$n;$j++) {
			$atual = DBRow($r,$j);
			if ($atual["claranswer"]=="") $atual["claranswer"]="null";
			else $atual["claranswer"]="'".escape_string($atual["claranswer"])."'";
			if ($atual["clarjudge"]=="") $atual["clarjudge"]="null";
			if ($atual["clarjudgesite"]=="") $atual["clarjudgesite"]="null";
			if ($atual["updatetime"]=="") $atual["updatetime"]=time();
			if ($situacao[$j]=="atualizar") {
				DBExec ($c, "update clartable set ".
						"usernumber=".$atual["usernumber"].",".
						"clardate=".$atual["clardate"].",".
						"clardatediff=".$atual["clardatediff"].",".
						"clardatediffans=".$atual["clardatediffans"].",".
						"clarproblem=".$atual["clarproblem"].",".
						"clardata='".escape_string($atual["clardata"])."',".
						"claranswer=".$atual["claranswer"].",".
						"clarstatus='".escape_string($atual["clarstatus"])."',".
						"clarjudge=".$atual["clarjudge"].",".
						"clarjudgesite=".$atual["clarjudgesite"].",".
						"updatetime=".$atual["updatetime"].
					" where contestnumber=$contest and clarsitenumber=".$atual["clarsitenumber"].
					" and clarnumber=".$atual["clarnumber"]." and updatetime<".$atual["updatetime"]);
				$upd++;
			} else if($situacao[$j]=="inserir") {
				DBExec ($c, "insert into clartable (contestnumber, clarsitenumber, clarnumber, usernumber, clardate,".
					"clardatediff, clardatediffans, clarproblem, clardata, claranswer, clarstatus, clarjudge, clarjudgesite, updatetime) ".
					"values (".$contest.",".$atual["clarsitenumber"].",".$atual["clarnumber"].",".$atual["usernumber"].",".
                                                $atual["clardate"].",".
                                                $atual["clardatediff"].",".
                                                $atual["clardatediffans"].",".
                                                $atual["clarproblem"].",".
                                                "'".escape_string($atual["clardata"])."',".
                                                $atual["claranswer"].",".
                                                "'".escape_string($atual["clarstatus"])."',".
                                                $atual["clarjudge"].",".
                                                $atual["clarjudgesite"].",".
                                                $atual["updatetime"].")");
				$ins++;
			}
		}
		echo "   deletions=$del, updates=$upd, insertions=$ins\n\n";

		// sincronizando clars
		$r = DBExec ($t, "select * from clartable where contestnumber=$contest and clarsitenumber=$localsite");
		$rr = DBExec ($c, "select * from clartable where contestnumber=$contest and clarsitenumber=$localsite");
		$n = DBnLines ($r);
		$nn = DBnLines ($rr);
		echo "clars: external(site=$site, reading=$localsite)=$n, local=$nn\n";
		for ($k=0; $k<$nn; $k++) $needed[$k]=false;
		for ($j=0;$j<$n;$j++) {
			$la = DBRow($r,$j);
			for ($k=0; $k<$nn; $k++) {
				$aqui = DBRow($rr,$k);
				if ($la["clarnumber"]==$aqui["clarnumber"] && $la["contestnumber"]==$aqui["contestnumber"] &&
					$la["clarsitenumber"]==$aqui["clarsitenumber"]) {
					$needed[$k]=true;
					if ($la["updatetime"]>$aqui["updatetime"])
						$situacao[$j]="atualizar";
					else
						$situacao[$j]="ok";
					break;
				}
			}
			if ($k>=$nn) $situacao[$j]="inserir";
		}
		$upd=0; $ins=0;
		for ($j=0;$j<$n;$j++) {
			$atual = DBRow($r,$j);
			if ($atual["claranswer"]=="") $atual["claranswer"]="null";
			else $atual["claranswer"]="'".escape_string($atual["claranswer"])."'";
			if ($atual["clarjudge"]=="") $atual["clarjudge"]="null";
			if ($atual["clarjudgesite"]=="") $atual["clarjudgesite"]="null";
			if ($atual["updatetime"]=="") $atual["updatetime"]=time();
			if ($situacao[$j]=="atualizar") {
				DBExec ($c, "update clartable set ".
						"usernumber=".$atual["usernumber"].",".
						"clardate=".$atual["clardate"].",".
						"clardatediff=".$atual["clardatediff"].",".
						"clardatediffans=".$atual["clardatediffans"].",".
						"clarproblem=".$atual["clarproblem"].",".
						"clardata='".escape_string($atual["clardata"])."',".
						"claranswer=".$atual["claranswer"].",".
						"clarstatus='".escape_string($atual["clarstatus"])."',".
						"clarjudge=".$atual["clarjudge"].",".
						"clarjudgesite=".$atual["clarjudgesite"].",".
						"updatetime=".$atual["updatetime"].
					" where contestnumber=$contest and clarsitenumber=".$atual["clarsitenumber"].
					" and clarnumber=".$atual["clarnumber"]." and updatetime<".$atual["updatetime"]);
				$upd++;
			} else if($situacao[$j]=="inserir") {
				$ins++;
				echo "Clar inserts shouldn't exist (clarnumber=".$atual["clarnumber"].")...\n";
			}
		}
		echo "   updates=$upd, insertions=$ins\n\n";

		// sincronizando runs
		$r = DBExec ($t, "select * from runtable where contestnumber=$contest and runsitenumber=$site");
		$rr = DBExec ($c, "select * from runtable where contestnumber=$contest and runsitenumber=$site");
		$n = DBnLines ($r);
		$nn = DBnLines ($rr);
		echo "runs: external(site=$site, reading=$site)=$n, local=$nn\n";
		for ($k=0; $k<$nn; $k++) $needed[$k]=false;
		for ($j=0;$j<$n;$j++) {
			$la = DBRow($r,$j);
			for ($k=0; $k<$nn; $k++) {
				$aqui = DBRow($rr,$k);
				if ($la["runnumber"]==$aqui["runnumber"] && $la["contestnumber"]==$aqui["contestnumber"] &&
					$la["runsitenumber"]==$aqui["runsitenumber"]) {
					$needed[$k]=true;
					if ($la["updatetime"]>$aqui["updatetime"]) {
						$situacao[$j]="atualizar";
					}
					else
						$situacao[$j]="ok";
					break;
				}
			}
			if ($k>=$nn) $situacao[$j]="inserir";
		}
		$del=0;
		for ($k=0; $k<$nn; $k++) {
			$atual = DBRow($rr, $k);
			if (!$needed[$k]) {
				$del++;
				DBExec($c, "delete from runtable where contestnumber=$contest and runsitenumber=$site and runnumber=".
					$atual["runnumber"]);
			}
		}
		$upd=0; $ins=0;
		for ($j=0;$j<$n;$j++) {
			$atual = DBRow($r,$j);
/*
			$r2 = DBExec ($c,"select * from runtable where contestnumber=$contest and runsitenumber=".$atual["runsitenumber"].
                                        " and runnumber=".$atual["runnumber"]." for update");
			if (DBnLines($r2)>0) {
				$p = DBRow($r2, 0);
				DB_lo_unlink($c, $p["rundata"]);
			}
*/
			if ($atual["runjudge"]=="") $atual["runjudge"]="null";
			if ($atual["runjudgesite"]=="") $atual["runjudgesite"]="null";
			if ($atual["updatetime"]=="") $atual["updatetime"]=time();
			if ($situacao[$j]=="atualizar") {
				$upd++;
				DBExec ($c, "update runtable set ".
						"usernumber=".$atual["usernumber"].",".
						"rundate=".$atual["rundate"].",".
						"rundatediff=".$atual["rundatediff"].",".
						"rundatediffans=".$atual["rundatediffans"].",".
						"runproblem=".$atual["runproblem"].",".
						"runlangnumber=".$atual["runlangnumber"].",".
						"runanswer=".$atual["runanswer"].",".
						"runstatus='".escape_string($atual["runstatus"])."',".
						"runjudge=".$atual["runjudge"].",".
						"runjudgesite=".$atual["runjudgesite"].",".
						"runfilename='".escape_string($atual["runfilename"])."',".
						"updatetime=".$atual["updatetime"].
					" where contestnumber=$contest and runsitenumber=".$atual["runsitenumber"].
					" and runnumber=".$atual["runnumber"]." and updatetime<".$atual["updatetime"]);
			} else if ($situacao[$j]=="inserir") {
				$ins++;
				DBExec($t, "begin work");
				$in = DB_lo_open($t, $atual["rundata"], "r");
				if (!$in) $o="0";
				else {
					$o = DB_lo_create ($c);
					$out = DB_lo_open ($c, $o, "w");
					while (($buf = DB_lo_read ($in, 1000)) != false)
						DB_lo_write ($out, $buf);
					DB_lo_close ($out);
					DB_lo_close ($in);

					$in = DB_lo_open($t, $atual["rundata"], "r");
					if (!$in) {
						$o="0";
				                LOGLevel("Run not saved as file (run=".$atual["runnumber"].",site=".$atual["runsitenumber"].
							",contest=$contest", 1);
					} else {
						$sitess=$atual["runsitenumber"];
						$nss=$atual["runnumber"];
						$user=$atual["usernumber"];
						$problem=$atual["runproblem"];
						$filename=escape_string($atual["runfilename"]);
						$ttimet=$atual["rundate"];
				                $fp = fopen("/tmp/boca/contest${contest}.site${sitess}.run${nss}.user${user}.".
								"problem${problem}.time${ttimet}.${filename}", "w");
						if ($fp) {
							while (($buf = DB_lo_read ($in, 1000)) != false)
								fwrite ($fp, $buf);
							fclose ($fp);
					                $fp = fopen("/tmp/check/contest${contest}.site${sitess}.run${nss}.user${user}.".
									"problem${problem}.time${ttimet}.${filename}.check", "w");
							if ($fp) {
								fwrite($fp, "1");
								fclose ($fp);
							}
							else 
							LOGLevel("Run not saved as check file (run=".$atual["runnumber"].",site=".$atual["runsitenumber"].
									",contest=$contest", 1);
						} else
					                LOGLevel("Run not saved as file (run=".$atual["runnumber"].",site=".$atual["runsitenumber"].
								",contest=$contest", 1);
						DB_lo_close ($out);
					}

				}
				DBExec ($c, "insert into runtable (contestnumber, runsitenumber, runnumber, usernumber, rundate,".
					"rundatediff, rundatediffans, runproblem, runfilename, rundata, runanswer, runstatus, runjudge,".
					"runjudgesite, runlangnumber, updatetime) ".
					"values (".$contest.",".$atual["runsitenumber"].",".$atual["runnumber"].",".$atual["usernumber"].",".
                                                $atual["rundate"].",".
                                                $atual["rundatediff"].",".
                                                $atual["rundatediffans"].",".
                                                $atual["runproblem"].",".
                                                "'".escape_string($atual["runfilename"])."',$o,".
                                                $atual["runanswer"].",".
                                                "'".escape_string($atual["runstatus"])."',".
                                                $atual["runjudge"].",".
                                                $atual["runjudgesite"].",".
                                                $atual["runlangnumber"].",".
                                                $atual["updatetime"].")");
				DBExec ($t, "commit work");
			}
		}
		echo "   deletions=$del, updates=$upd, insertions=$ins\n\n";

		// sincronizando runs
		$r = DBExec ($t, "select * from runtable where contestnumber=$contest and runsitenumber=$localsite");
		$rr = DBExec ($c, "select * from runtable where contestnumber=$contest and runsitenumber=$localsite");
		$n = DBnLines ($r);
		$nn = DBnLines ($rr);
		echo "runs: external(site=$site, reading=$localsite)=$n, local=$nn\n";
		for ($k=0; $k<$nn; $k++) $needed[$k]=false;
		for ($j=0;$j<$n;$j++) {
			$la = DBRow($r,$j);
			for ($k=0; $k<$nn; $k++) {
				$aqui = DBRow($rr,$k);
				if ($la["runnumber"]==$aqui["runnumber"] && $la["contestnumber"]==$aqui["contestnumber"] &&
					$la["runsitenumber"]==$aqui["runsitenumber"]) {
					$needed[$k]=true;
					if ($la["updatetime"]>$aqui["updatetime"]) {
						$situacao[$j]="atualizar";


						if ($la["runanswer"] != "") {
							$rrr = DBExec($c, "select * from answertable where answernumber=".$la["runanswer"].
								" and contestnumber=".$la["contestnumber"]);
							$ans = (DBnLines($rrr)>0)? DBRow($rrr, 0) : null;
							if ($ans == null) {
		                        			echo "Problem with the answer table. Unable to send balloon because the answer was " .
									"not found (run=".$la["runnumber"].", site=".$la["runsite"].", contest=" .
									$la["contestnumber"].", answer=".$la["runanswer"].").";
								$yesla = 'x';
			        		        } else $yesla = $ans["yes"];
						} else $yesla='f';
						if ($aqui["runanswer"] != "") {
							$rrr = DBExec($c, "select * from answertable where answernumber=".$aqui["runanswer"].
								" and contestnumber=".$aqui["contestnumber"]);
							$ans = (DBnLines($rrr)>0)? DBRow($rrr, 0) : null;
							if ($ans == null) {
		                        			echo "Problem with the answer table. Unable to send balloon because the answer was " .
									"not found (run=".$aqui["runnumber"].", site=".$aqui["runsite"].", contest=" .
									$aqui["contestnumber"].", answer=".$aqui["runanswer"].").";
								$yesaqui = 'x';
			        		        } else $yesaqui = $ans["yes"];
						} else $yesaqui='f';

						if ($yesla == 't' && $yesaqui == 'f') {
							$rrr = DBExec ($c, "select * from sitetable where contestnumber=".$aqui["contestnumber"].
								" and sitenumber=$localsite");
							if (DBnLines($rrr)<=0)
								echo "Site info not found (contest=${aqui["contestnumber"]}, site=$localsite).";
							else {
								$b = DBRow($rrr,0);
						                $ti = $b["sitestartdate"];
						                $tempo = time();
						                $ta = $tempo - $ti;
						                $tf = $b["sitelastmileanswer"];
//						                if ($ta < $tf) {
									$rrr = DBExec ($c, "select * from usertable where contestnumber=".
										$aqui["contestnumber"]." and usersitenumber=$localsite and usernumber=".
										$aqui["usernumber"]);
									if (DBnLines($rrr)<=0)
										echo "User info not found (contest=${aqui["contestnumber"]}, ".
											"site=$localsite, user=${aqui["runusernumber"]}).";
									else {
							                        $u = DBRow ($rrr,0);
										$rrr = DBExec ($c, "select * from problemtable where contestnumber=".
											$aqui["contestnumber"]." and problemnumber=".
											$aqui["runproblem"]);
										if (DBnLines($rrr)<=0)
											echo "Problem info not found (contest=${aqui["contestnumber"]}, ".
												"problem=${aqui["runproblem"]}).";
										else {
							                        	$p = DBRow ($rrr,0);
							                	        mail("balloon@mainserver", "YES: team=" . $u["username"] . 
												", problem=" . $p["problemname"],
				                                				"User ".$u["username"] ." should receive a balloon for ".
											$p["problemfullname"]."\n");
										}
									}
//								}
							}
					        } else if ($yesla == 'f' && $yesaqui == 't') {
							$rrr = DBExec ($c, "select * from usertable where contestnumber=".
								$aqui["contestnumber"]." and usersitenumber=$localsite and usernumber=".
								$aqui["usernumber"]);
							if (DBnLines($rrr)<=0)
								echo "User info not found (contest=${aqui["contestnumber"]}, ".
									"site=$localsite, user=${aqui["runusernumber"]}).";
							else {
					                        $u = DBRow ($rrr,0);
								$rrr = DBExec ($c, "select * from problemtable where contestnumber=".
									$aqui["contestnumber"]." and problemnumber=".
									$aqui["runproblem"]);
								if (DBnLines($rrr)<=0)
									echo "Problem info not found (contest=${aqui["contestnumber"]}, ".
										"problem=${aqui["runproblem"]}).";
								else {
					                        	$p = DBRow ($rrr,0);
									mail("balloon@mainserver", "NO: team=" . $u["username"] . 
										", problem=" . $p["problemname"],
										"Remove the balloon from user ". $u["username"] .
										" for ".$p["problemfullname"]."\n");
								}
							}
						}


					}
					else
						$situacao[$j]="ok";
					break;
				}
			}
			if ($k>=$nn) $situacao[$j]="inserir";
		}
		$upd=0; $ins=0;
		for ($j=0;$j<$n;$j++) {
			$atual = DBRow($r,$j);

			if ($atual["runjudge"]=="") $atual["runjudge"]="null";
			if ($atual["runjudgesite"]=="") $atual["runjudgesite"]="null";
			if ($atual["updatetime"]=="") $atual["updatetime"]=time();
			if ($situacao[$j]=="atualizar") {
				$upd++;
				DBExec ($c, "update runtable set ".
						"usernumber=".$atual["usernumber"].",".
						"rundate=".$atual["rundate"].",".
						"rundatediff=".$atual["rundatediff"].",".
						"rundatediffans=".$atual["rundatediffans"].",".
						"runproblem=".$atual["runproblem"].",".
						"runlangnumber=".$atual["runlangnumber"].",".
						"runanswer=".$atual["runanswer"].",".
						"runstatus='".escape_string($atual["runstatus"])."',".
						"runjudge=".$atual["runjudge"].",".
						"runjudgesite=".$atual["runjudgesite"].",".
						"runfilename='".escape_string($atual["runfilename"])."',".
						"updatetime=".$atual["updatetime"].
					" where contestnumber=$contest and runsitenumber=".$atual["runsitenumber"].
					" and runnumber=".$atual["runnumber"]." and updatetime<".$atual["updatetime"]);
			} else if ($situacao[$j]=="inserir") {
				$ins++;
				echo "Run Inserts shouldn't exist (run=".$atual["runnumber"].")...";
			}
		}
		echo "   updates=$upd, insertions=$ins\n\n";

		DBExec($c, "commit work");
		DBClose($t);
	}
	DBClose($c);
}

//fecha a conexao com o banco (isso nao eh realmente necessario, ja que o php/apache cuidam do servico)
function DBClose($c) {
	if ($c) pg_close($c);
}
//executar instrucao no banco de dados, parando em caso de erro quando $stop=1
function DBExec($conn,$sql) {
//	echo $sql . "\n";
	$result = pg_exec ($conn, $sql);
	if (!$result) {
		echo "Unable to exec SQL in the database. SQL=(" . $sql . ")," .
                         " Error=(" . pg_errormessage($conn) . ")\n";
		exit;
	}
	return $result;
}
//devolve o numero de linhas da consulta
function DBnlines ($result) {
	return pg_numrows ($result);
}
//pega uma linha da consulta no formato de array
function DBRow ($r, $i) {
	return pg_fetch_array ($r, $i);
}
function escape_string($s) {
	return str_replace("'", "''", $s);
}
if (getIP()!="UNKNOWN") exit;

$x = DBConnect();
if ($x==null) exit;
$y = DBExec($x, "select * from contesttable where contestactive='t'");
if (DBnLines($y)==0) {
  echo "Unable to find the active contest in the database.\n";
  exit;
}
$ct = DBRow($y,0);
DBClose($x);
GetExternalData($ct["contestnumber"]);

?>
