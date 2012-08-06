<?php
//////////////////////////////////////////////////////////////////////////////////////////
//BOCA Online Contest Administrator. Copyright (c) 2003- Cassio Polpo de Campos.
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
// created 14/June/2011 by cassio@ime.usp.br
require('header.php');

if(($ct = DBContestInfo($_SESSION["usertable"]["contestnumber"])) == null)
	ForceLoad("../index.php");
if(($st = DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
	ForceLoad("../index.php");

$fn = tempnam("/tmp","bkp-");
$fout = fopen($fn,"wb");
echo $_POST;
echo $_POST['data'];
fwrite($fout,base64_decode($_POST['data']));
fclose($fout);
$size=filesize($fn);
$name=$_POST['name'];
if ($size > $ct["contestmaxfilesize"] || strlen($name)>100 || strlen($name)<1) {
	LOGLevel("User {$_SESSION["usertable"]["username"]} tried to submit file " .
			 ":${name}: with $size bytes.", 1);
	MSGError("File size exceeds the limit allowed or invalid name.");
} else

	DBNewBkp ($_SESSION["usertable"]["contestnumber"],
			  $_SESSION["usertable"]["usersitenumber"],
			  $_SESSION["usertable"]["usernumber"],
			  $name,
			  $fn, $size);
@unlink($fn);
ForceLoad("../index.php");
?>
