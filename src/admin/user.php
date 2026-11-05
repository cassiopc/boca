<?php
////////////////////////////////////////////////////////////////////////////////
//BOCA Online Contest Administrator
//    Copyright (C) 2003-2014 by BOCA Development Team (bocasystem@gmail.com)
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
require('header.php');

if (isset($_GET["site"]) && isset($_GET["user"]) && is_numeric($_GET["site"]) && is_numeric($_GET["user"]) &&
    isset($_GET["logout"]) && $_GET["logout"] == 1) {
	DBLogOut($_SESSION["usertable"]["contestnumber"], $_GET["site"], $_GET["user"]);
        ForceLoad("user.php");
}
if (isset($_POST["usersitenumber"]) && isset($_POST["usernumber"]) && is_numeric($_POST["usersitenumber"]) && 
    is_numeric($_POST["usernumber"]) && isset($_POST["confirmation"]) && $_POST["confirmation"] == "delete") {
	if (!DBDeleteUser($_SESSION["usertable"]["contestnumber"], $_POST["usersitenumber"], $_POST["usernumber"]))
		MSGError("Não foi possível remover o usuário.");
        ForceLoad("user.php");
}

if(($ct = DBContestInfo($_SESSION["usertable"]["contestnumber"])) == null)
        ForceLoad("../index.php");
if($ct["contestlocalsite"]==$ct["contestmainsite"]) $main=true; else $main=false;

if (isset($_POST["username"]) && isset($_POST["userfullname"]) && isset($_POST["userdesc"]) && isset($_POST["userip"]) &&
    isset($_POST["usernumber"]) && isset($_POST["usersitenumber"]) && isset($_POST["userenabled"]) && isset($_POST["usericpcid"]) &&
    isset($_POST["usermultilogin"]) && isset($_POST["usertype"]) && isset($_POST["confirmation"]) &&
    isset($_POST["passwordn1"]) && isset($_POST["passwordn2"]) && isset($_POST["passwordo"]) && $_POST["confirmation"] == "confirm") {
	$param['user'] = htmlspecialchars($_POST["usernumber"]);
	$param['site'] = htmlspecialchars($_POST["usersitenumber"]);
	$param['username'] = htmlspecialchars($_POST["username"]);
	$param['usericpcid'] = htmlspecialchars($_POST["usericpcid"]);
	$param['enabled'] = htmlspecialchars($_POST["userenabled"]);
	$param['multilogin'] = htmlspecialchars($_POST["usermultilogin"]);
	$param['userfull'] = htmlspecialchars($_POST["userfullname"]);
	$param['userdesc'] = htmlspecialchars($_POST["userdesc"]);
	$param['type'] = htmlspecialchars($_POST["usertype"]);
	$param['permitip'] = htmlspecialchars($_POST["userip"]);
	$param['contest'] = $_SESSION["usertable"]["contestnumber"];
	$param['changepass']='t';
	if(isset($_POST['changepass']) && $_POST['changepass'] != 't') $param['changepass']='f';
/*
	$param['user'] = myhtmlspecialchars($_POST["usernumber"]);
	$param['site'] = myhtmlspecialchars($_POST["usersitenumber"]);
	$param['username'] = myhtmlspecialchars($_POST["username"]);
	$param['usericpcid'] = myhtmlspecialchars($_POST["usericpcid"]);
	$param['enabled'] = myhtmlspecialchars($_POST["userenabled"]);
	$param['multilogin'] = myhtmlspecialchars($_POST["usermultilogin"]);
	$param['userfull'] = unsanitizeText($_POST["userfullname"]); //myhtmlspecialchars($_POST["userfullname"]);
	$param['userdesc'] = unsanitizeText($_POST["userdesc"]); //myhtmlspecialchars($_POST["userdesc"]);
	$param['type'] = myhtmlspecialchars($_POST["usertype"]);
	$param['permitip'] = myhtmlspecialchars($_POST["userip"]);
*/


	$passcheck = $_POST["passwordo"];
	$a = DBUserInfo($_SESSION["usertable"]["contestnumber"], $_SESSION["usertable"]["usersitenumber"], $_SESSION["usertable"]["usernumber"], null, false);
	if(myhash($a['userpassword'] . session_id()) != $passcheck) {
		MSGError('A senha de administrador está incorreta');
	} else {
		if ($_POST["passwordn1"] == $_POST["passwordn2"]) {
			$param['pass'] = bighexsub($_POST["passwordn1"],$a['userpassword']);
			while(strlen($param['pass']) < strlen($a['userpassword'])) $param['pass'] = '0' . $param['pass'];
			if($param['user'] != 1000)
				DBNewUser($param);
		}
		else MSGError ("As senhas não coincidem.");
	}
	ForceLoad("user.php");
}
else if (isset($_FILES["importfile"]) && isset($_POST["Submit"]) && $_FILES["importfile"]["name"]!="") {
        if ($_POST["confirmation"] == "confirm") {
                $type=myhtmlspecialchars($_FILES["importfile"]["type"]);
                $size=myhtmlspecialchars($_FILES["importfile"]["size"]);
                $name=myhtmlspecialchars($_FILES["importfile"]["name"]);
                $temp=myhtmlspecialchars($_FILES["importfile"]["tmp_name"]);
                if (!is_uploaded_file($temp)) {
                        IntrusionNotify("file upload problem.");
                        ForceLoad("../index.php");
                }

                if (($ar = file($temp)) === false) {
                        IntrusionNotify("Unable to open the uploaded file.");
                        ForceLoad("user.php");
                }
				$userlist=array();
				if(strtolower(substr($name,-4))==".tsv") {
					for ($i=0; $i < count($ar) && strpos($ar[$i], "File_Version\t1") === false; $i++) ;
					if($i >= count($ar)) MSGError('Formato de arquivo não reconhecido');
					$oklines=0;
					for ($i++; $i < count($ar); $i++) {
                        $x = explode("\t",trim($ar[$i]));
						if(count($x)==7) {
							$param['site']=trim($x[2]);
							$param['username']=trim($x[1]);
							$param['usericpcid']=trim($x[1]);
							$param['usernumber']=trim($x[1]);
							if(trim($x[5])!='')
								$param['userfull']=trim($x[3]) . ' - ' . trim($x[5]);
							else
								$param['userfull']=trim($x[3]);
							$param['userdesc']=trim($x[4]);
							$param['type']='team';
							$param['enabled']='t';
							$param['multilogin']='f';
							$userlist[$param['site'] . '-' . $param['usernumber']] = randstr(6,'0123456789');
							$param['pass']=myhash($userlist[$param['site'] . '-' . $param['usernumber']]);
							$param['changepass']='t';
							$param['contest']=$_SESSION["usertable"]["contestnumber"];
							if($_SESSION["usertable"]["usersitenumber"] == $param['site'] || $main)
								if($param['usernumber'] != 1000 && DBNewUser($param)) {
									$oklines++;
								} else {
									unset($userlist[$param['site'] . '-' . $param['usernumber']]);
									break;
								}
						}
					}
					MSGError($oklines . ' usuários incluídos ou atualizados com sucesso');
				} else if(strtolower(substr($name,-4))==".tab") {
					$oklines=0;
					for ($i=0; $i<count($ar); $i++) {
                        $x = explode("\t",trim($ar[$i]));
						if(count($x)==9) {
							$param=array();
							$param['site']=trim($x[1]);
							$param['username']=trim($x[0]);
							$param['usericpcid']=trim($x[0]);
							$param['usernumber']=trim($x[0]);
							if(trim($x[5])!='')
								$param['userfull']=trim($x[3]) . ' - ' . trim($x[5]);
							else
								$param['userfull']=trim($x[3]);
							$param['userdesc']=trim($x[4]);
							$param['type']='team';
							$param['enabled']='t';
							$param['multilogin']='f';
							$userlist[$param['site'] . '-' . $param['usernumber']] = randstr(6,'0123456789');
							$param['pass']=myhash($userlist[$param['site'] . '-' . $param['usernumber']]);
							$param['changepass']='t';
							$param['contest']=$_SESSION["usertable"]["contestnumber"];
							if($_SESSION["usertable"]["usersitenumber"] == $param['site'] || $main)
								if($param['usernumber'] != 1000 && DBNewUser($param)) {
									$oklines++;
								} else {
									unset($userlist[$param['site'] . '-' . $param['usernumber']]);
									break;
								}
						}
					}
					MSGError($oklines . ' usuários incluídos ou atualizados com sucesso');
				} else {
					for ($i=0; $i < count($ar) && strpos($ar[$i], "[user]") === false; $i++) ;
					if($i >= count($ar)) MSGError('Formato de arquivo não reconhecido');
					for ($i++; $i < count($ar) && $ar[$i][0] != "["; $i++) {
                        $x = trim($ar[$i]);
                        if (strpos($x, "user") !== false && strpos($x, "user") == 0) {
                        	$param = array();
							$param['changepass']='t';
							while (strpos($x, "user") !== false && strpos($x, "user") == 0) {
								$tmp = explode ("=", $x, 2);
								switch (trim($tmp[0])) {
									case "usersitenumber":    $param['site']=trim($tmp[1]); break;
									case "username":          $param['username']=trim($tmp[1]); break;
									case "usericpcid":        $param['usericpcid']=trim($tmp[1]); break;
									case "usernumber":        $param['usernumber']=trim($tmp[1]); break;
									case "userfullname":      $param['userfull']=trim($tmp[1]); break;
									case "userdesc":          $param['userdesc']=trim($tmp[1]); break;
									case "usertype":          $param['type']=trim($tmp[1]); break;
									case "userenabled":       $param['enabled']=trim($tmp[1]); break;
									case "usermultilogin":    $param['multilogin']=trim($tmp[1]); break;
									case "userpassword":      $param['pass']=myhash(trim($tmp[1])); break;
									case "userchangepassword": $param['changepass']=trim($tmp[1]); break;
									case "userip":            $param['permitip']=trim($tmp[1]); break;
								}
								$i++;
								if ($i>=count($ar)) break;
								$x = trim($ar[$i]);
							}
							$param['contest']=$_SESSION["usertable"]["contestnumber"];
							if($_SESSION["usertable"]["usersitenumber"] == $param['site'] || $main)
								if($param['usernumber'] != 1000) DBNewUser($param);
                        }
					}
				}
				if(count($userlist) > 0) {
?>
<center>
<br><u><b>ANOTE OS USUÁRIOS E AS SENHAS E MANTENHA-OS EM SIGILO</b></u><br><br>
<table border=1>
 <tr>
  <td nowrap><b>Local</b></td><td><b>Usuário nº</b></td>
  <td><b>Senha</b></td>
 </tr>
<?php
				  	foreach($userlist as $user => $pass) {
						$x = explode('-',$user);
						echo "<tr><td>" . $x[0] . "</td><td>" . $x[1] . "</td><td>$pass</td></tr>\n";
					}
?>
</table><br><br><u><b>ANOTE OS USUÁRIOS E AS SENHAS E MANTENHA-OS EM SIGILO</b></u></center></body></html>
<?php
	  exit;
				}
        }
        ForceLoad("user.php");
}

if($main)
	$usr = DBAllUserInfo($_SESSION["usertable"]["contestnumber"]);
else
	$usr = DBAllUserInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"]);
?>

  <script language="javascript">
    function conf2(url) {
      if (confirm("Tem certeza?")) {
        document.location=url;
      } else {
        document.location='user.php';
      }
    }
  </script>
<br>
<table width="100%" border=1>
 <tr>
  <td nowrap><b>Usuário nº</b></td>
  <td><b>Local</b></td>
  <td><b>Usuário</b></td>
  <td><b>ICPC ID</b></td>
  <td><b>Tipo</b></td>
  <td><b>IP</b></td>
  <td><b>Último acesso</b></td>
  <td><b>Última saída</b></td>
  <td><b>Ativo</b></td>
  <td><b>Multi</b></td>
  <td><b>Nome completo</b></td>
  <td><b>Descrição</b></td>
 </tr>
<?php
$userTypeLabels = array('team' => 'Equipe', 'judge' => 'Juiz', 'admin' => 'Administrador',
                        'staff' => 'Apoio', 'score' => 'Placar', 'site' => 'Sede');
for ($i=0; $i < count($usr); $i++) {
  echo " <tr>\n";
  if(($usr[$i]["usersitenumber"] == $_SESSION["usertable"]["usersitenumber"] || $main==true) && 
	 //$usr[$i]["usertype"] != 'site' && 
	 ($usr[$i]["usernumber"] != $_SESSION["usertable"]["usernumber"] || 
	  $usr[$i]["usersitenumber"] != $_SESSION["usertable"]["usersitenumber"]))
	  echo "  <td nowrap><a href=\"user.php?site=" . $usr[$i]["usersitenumber"] . "&user=" .
		  $usr[$i]["usernumber"] . "\">" . $usr[$i]["usernumber"] . "</a>";
  else
	  echo "  <td nowrap>" . $usr[$i]["usernumber"];
  if($usr[$i]['userenabled'] != 't' && $usr[$i]['userlastlogin'] < 1) echo "(inativo)";
  echo "</td>\n";

  echo "  <td nowrap>" . $usr[$i]["usersitenumber"] . "</td>\n";
  echo "  <td nowrap>" . $usr[$i]["username"] . "&nbsp;</td>\n";
  echo "  <td nowrap>" . $usr[$i]["usericpcid"] . "&nbsp;</td>\n";
  $type = $usr[$i]["usertype"];
  echo "  <td nowrap>" . htmlspecialchars($userTypeLabels[$type] ?? $type, ENT_QUOTES, 'UTF-8') . "&nbsp;</td>\n";
  if ($usr[$i]["userpermitip"]!="")
    echo "  <td nowrap>" . $usr[$i]["userpermitip"] . "*&nbsp;</td>\n";
  else
    echo "  <td nowrap>" . $usr[$i]["userip"] . "&nbsp;</td>\n";
  if ($usr[$i]["userlastlogin"] < 1)
    echo "  <td nowrap>nunca</td>\n";
  else
    echo "  <td nowrap>" . dateconv($usr[$i]["userlastlogin"]) . "</td>\n";
  if ($usr[$i]["usersession"] != "")
    echo "  <td nowrap><a href=\"javascript: conf2('user.php?logout=1&site=" . $usr[$i]["usersitenumber"] . "&user=" .
         $usr[$i]["usernumber"] . "')\">Encerrar sessão</a></td>\n";
  else {
    if ($usr[$i]["userlastlogout"] < 1)
      echo "  <td nowrap>nunca</td>\n";
    else
      echo "  <td nowrap>" . dateconv($usr[$i]["userlastlogout"]) . "</td>\n";
  }
  if ($usr[$i]["userenabled"] == "t")
    echo "  <td nowrap>Sim</td>\n";
  else
    echo "  <td nowrap>Não</td>\n";
  if ($usr[$i]["usermultilogin"] == "t")
    echo "  <td nowrap>Sim</td>\n";
  else
    echo "  <td nowrap>Não</td>\n";
  echo "  <td nowrap>" . $usr[$i]["userfullname"] . "&nbsp;</td>\n";
  echo "  <td nowrap>" . $usr[$i]["userdesc"] . "&nbsp;</td>\n";
  echo "</tr>";
}
echo "</table>\n";

unset($u);
if (isset($_GET["site"]) && isset($_GET["user"]) && is_numeric($_GET["site"]) && is_numeric($_GET["user"]))
  $u = DBUserInfo($_SESSION["usertable"]["contestnumber"], $_GET["site"], $_GET["user"]);

?>
<script language="JavaScript" src="../sha256.js"></script>
<script language="JavaScript" src="../hex.js"></script>
<script language="JavaScript">
function computeHASH()
{
	document.form3.passwordn1.value = bighexsoma(js_myhash(document.form3.passwordn1.value),js_myhash(document.form3.passwordo.value));
	document.form3.passwordn2.value = bighexsoma(js_myhash(document.form3.passwordn2.value),js_myhash(document.form3.passwordo.value));
	document.form3.passwordo.value = js_myhash(js_myhash(document.form3.passwordo.value)+'<?php echo session_id(); ?>');
//	document.form3.passwordn1.value = js_myhash(document.form3.passwordn1.value);
//	document.form3.passwordn2.value = js_myhash(document.form3.passwordn2.value);
}
</script>

<br><br><center><b>Clique no número de um usuário para editar seus dados.<br>
Para importar usuários, selecione o arquivo de importação.<br>
O arquivo deve seguir o formato definido no manual da administração.</b></center>

<form name="form1" enctype="multipart/form-data" method="post" action="user.php">
  <input type=hidden name="confirmation" value="noconfirm" />
  <center>
    <table border="0">
      <tr>
        <td width="25%" align=right>Arquivo para importação:</td>
        <td width="75%">
          <input type="file" name="importfile" size="40">
        </td>
      </tr>
    </table>
  </center>
  <script language="javascript">
    function conf() {
      if (confirm("Confirmar?")) {
        document.form1.confirmation.value='confirm';
      }
    }
  </script>
  <center>
      <button type="submit" name="Submit" value="Import" onClick="conf()">Importar</button>
      <input type="reset" name="Submit2" value="Limpar">
  </center>
</form>

  <br><br>
  <center>
<b>Para criar ou editar um usuário, informe os dados abaixo.<br>
As alterações substituirão os dados já cadastrados.<br>
Tenha cuidado ao usar um número de usuário já existente.<br>
<br>
</b>
    <table border="0">
<form name="form3" action="user.php" method="post">
  <input type=hidden name="confirmation" value="noconfirm" />
  <script language="javascript">
    function conf3() {
      computeHASH();
      if (confirm("Confirmar?")) {
        document.form3.confirmation.value='confirm';
      }
    }
<?php 
if (isset($u)) {
  echo "    function conf4() {\n";
  echo "      if (confirm('Confirmar a exclusão?')) {\n";
  echo "       document.form3.confirmation.value='delete';\n";
  echo "      }\n";
  echo "    }\n";
  $usite = $u['usersitenumber'];
} else
  $usite = $ct['contestlocalsite'];
?>
    function conf5() {
      document.form3.confirmation.value='noconfirm';
    }
  </script>
   <center>
    <table border="0">
      <tr> 
        <td width="35%" align=right>Número do local do usuário:</td>
        <td width="65%">
	  <input type="text" name="usersitenumber" <?php if(!$main) echo "readonly "; echo "value=\"" . $usite . "\""; ?> size="20" maxlength="20" />
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>Número do usuário:</td>
        <td width="65%">
	  <input type="text" name="usernumber" value="<?php if(isset($u)) echo $u["usernumber"]; ?>" size="20" maxlength="20" />
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>Usuário:</td>
        <td width="65%">
	  <input type="text" name="username" value="<?php if(isset($u)) echo $u["username"]; ?>" size="20" maxlength="20" />
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>ICPC ID:</td>
        <td width="65%">
	  <input type="text" name="usericpcid" value="<?php if(isset($u)) echo $u["usericpcid"]; ?>" size="20" maxlength="50" />
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>Tipo:</td>
        <td width="65%">
		<select name="usertype">
		<option <?php if(!isset($u) || $u["usertype"] == "team") echo "selected"; ?> value="team">Equipe</option>
		<option <?php if(isset($u)) if($u["usertype"] == "judge") echo "selected"; ?> value="judge">Juiz</option>
		<option <?php if(isset($u)) if($u["usertype"] == "admin") echo "selected"; ?> value="admin">Administrador</option>
		<option <?php if(isset($u)) if($u["usertype"] == "staff") echo "selected"; ?> value="staff">Apoio</option>
		<option <?php if(isset($u)) if($u["usertype"] == "score") echo "selected"; ?> value="score">Placar</option>
		<?php if(1 || $main) { ?>
        <option <?php if(isset($u)) if($u["usertype"] == "site") echo "selected"; ?> value="site">Sede</option>
        <?php } ?>
		</select>
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>Ativo:</td>
        <td width="65%">
		<select name="userenabled">
		<option <?php if(!isset($u) || $u["userenabled"] != "f") echo "selected"; ?> value="t">Sim</option>
		<option <?php if(isset($u) && $u["userenabled"] == "f") echo "selected"; ?> value="f">Não</option>
		</select>
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>Acessos simultâneos (para equipes locais, selecione <b>Não</b>):</td>
        <td width="65%">
		<select name="usermultilogin">
		<option <?php if(isset($u) && $u["usermultilogin"] == "t") echo "selected"; ?> value="t">Sim</option>
		<option <?php if(!isset($u) || $u["usermultilogin"] != "t") echo "selected"; ?> value="f">Não</option>
		</select>
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>Nome completo do usuário:</td>
        <td width="65%">
	  <input type="text" name="userfullname" value="<?php if(isset($u)) echo $u["userfullname"]; ?>" size="50" maxlength="200" />
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>Descrição do usuário:</td>
        <td width="65%">
	  <input type="text" name="userdesc" value="<?php if(isset($u)) { 
if($u['usershortinstitution']!='')
  echo '[' . $u['usershortinstitution'] .']';
if($u['userflag']!='') {
  echo '[' . $u['userflag'];
  if($u['usersitename']!='') echo ',' . $u['usersitename'];
  echo ']';
}
echo $u["userdesc"]; } ?>" size="50" maxlength="300" />
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>IP do usuário:</td>
        <td width="65%">
	  <input type="text" name="userip" value="<?php if(isset($u)) echo $u["userpermitip"]; ?>" size="20" maxlength="20" />
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>Senha:</td>
        <td width="65%">
	  <input type="password" name="passwordn1" value="" size="20" maxlength="200" />
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>Repita a senha:</td>
        <td width="65%">
	  <input type="password" name="passwordn2" value="" size="20" maxlength="200" />
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>Permitir alteração de senha:</td>
        <td width="65%">
		<select name="changepass">
		<option <?php if(isset($u) && $u["changepassword"]) echo "selected"; ?> value="t">Sim</option>
		<option <?php if(!isset($u) || !$u["changepassword"]) echo "selected"; ?> value="f">Não</option>
		</select>
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>Senha do administrador (seu usuário):</td>
        <td width="65%">
	  <input type="password" name="passwordo" value="" size="20" maxlength="200" />
        </td>
      </tr>
    </table>
  </center>
  <center>
      <button type="submit" name="Submit" value="Send" onClick="conf3()">Enviar</button>
<?php if(isset($u)) { ?>
      <button type="submit" name="Delete" value="Delete" onClick="conf4()">Excluir</button>
<?php } ?>
      <button type="submit" name="Cancel" value="Cancel" onClick="conf5()">Cancelar</button>
<?php if(isset($u)) { ?>
<br><br><b>ATENÇÃO: excluir um usuário removerá todos os dados relacionados, inclusive submissões e dúvidas.<b><br>
<?php } ?>
  </center>
</form>

</body>
</html>
