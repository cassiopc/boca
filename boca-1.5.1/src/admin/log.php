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
require('header.php');

if(isset($_GET["order"]))
$order = myhtmlspecialchars($_GET["order"]);
else $order='';
if(isset($_GET["user"]))
$user = myhtmlspecialchars($_GET["user"]);
else $user='';
if(isset($_GET["site"]))
$site = myhtmlspecialchars($_GET["site"]);
else $site='';
if(isset($_GET["type"]))
$type = myhtmlspecialchars($_GET["type"]);
else $type='';
if(isset($_GET["ip"]))
$ip = myhtmlspecialchars($_GET["ip"]);
else $ip='';
$get="&order=${order}&user=${user}&site=${site}&type=${type}&ip=${ip}";
if (isset($_GET["limit"]) && $_GET["limit"]>0)
  $limit = myhtmlspecialchars($_GET["limit"]);
else $limit = 50;
$log = DBGetLogs($order, $_SESSION["usertable"]["contestnumber"], 
		$site, $user, $type, $ip, $limit);
?>
<br>
<table width="100%" border=1>
 <tr>
  <td><b><a href="log.php?order=site&limit=<?php echo $limit; ?>">Site</a></b></td>
  <td nowrap><b><a href="log.php?order=user&limit=<?php echo $limit; ?>">User #</a></b></td>
  <td><b><a href="log.php?order=ip&limit=<?php echo $limit; ?>">IP</a></b></td>
  <td><b><a href="log.php?order=type&limit=<?php echo $limit; ?>">Type</a></b></td>
  <td><b>Date</b></td>
  <td><b>Description</b></td>
  <td><b>Status</b></td>
 </tr>
<?php
for ($i=0; $i<count($log); $i++) {
  echo " <tr>\n";
  echo "  <td nowrap><a href=\"log.php?site=" . $log[$i]["site"] . "&limit=$limit\">" . $log[$i]["site"] . "</a></td>\n";
  echo "  <td nowrap><a href=\"log.php?user=" . $log[$i]["user"] . "&limit=$limit\">" . $log[$i]["user"] . "</a></td>\n";
  echo "  <td nowrap><a href=\"log.php?ip=" . $log[$i]["ip"] . "&limit=$limit\">" . $log[$i]["ip"] . "</a></td>\n";
  echo "  <td nowrap><a href=\"log.php?type=" . $log[$i]["type"] . "&limit=$limit\">" . $log[$i]["type"] . "</a></td>\n";
  echo "  <td nowrap>" . dateconv($log[$i]["date"]) . "</td>\n";
  echo "  <td nowrap>" . $log[$i]["data"] . "</td>\n";
  echo "  <td nowrap>" . $log[$i]["status"] . "</td>\n";
  echo "</tr>\n";
}
echo "</table>\n";

?>
<br>
<center>
<a href="log.php?limit=50<?php echo $get; ?>">50</a>
<a href="log.php?limit=200<?php echo $get; ?>">200</a>
<a href="log.php?limit=1000<?php echo $get; ?>">1000</a>
<a href="log.php?limit=1000000<?php echo $get; ?>">no limit</a>
</body>
</html>
