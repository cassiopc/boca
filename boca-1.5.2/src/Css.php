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
$corfundo = "#e0e0d0";
$corfrente = "#000000";
$corfundo2 = "#dfdfdf";
$cormenu = "#dfdfdf";
?>
DIV.menu {background-color:<?php echo $corfundo?>; layer-background-color:<?php echo $corfundo?>}
DIV.menudown {background-color:<?php echo $cormenu?>; border-bottom:1px solid white; border-right:1px solid white;border-top:2px solid #555555;border-left:1px solid #555555}
DIV.fname {background-color:<?php echo $corfundo2?>; layer-background-color:<?php echo $corfundo2?>; position:absolute; visibility:hidden; border:0; left:0px; top:0px; height:19px; z-index:100;}
DIV.dir {background-color:<?php echo $corfundo?>; layer-background-color:<?php echo $corfundo?>; position:absolute; visibility:hidden; border:0; left:0px; top:0px; height:19px;z-index:100; }
A {font-family:"Courier New", Courier, mono; font-size:12pt; color:<?php echo $corfrente?>} 
A.header {font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12pt} 
A.menu {font-family:Verdana, Arial, Helvetica, sans-serif; text-decoration:none; font-size:12pt; border: 1px solid <?php echo $corfundo?>} 
A.menu:hover {background-color:<?php echo $cormenu?>; border-bottom:1px solid #555555; border-right:1px solid #555555;border-top:1px solid white;border-left:1px solid white} 
A.user {font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12pt} 
A.user:hover {font-weight: bolder} 
A.disabled {font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12pt; text-decoration:none; color:#BFBFBF} 
A.form {font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12pt; background-color:<?php echo $cormenu?>} 
BODY {background-color:<?php echo $corfundo?>; font-family:"Courier new", monospace; font-size:12pt; color:<?php echo $corfrente?>} 
BODY.cline {background-color:#000000; color:#FFFFFF} 
TABLE { font-family:"Courier New", Courier, mono; font-size:12pt } 
TABLE.form { font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12pt } 
FORM { font-size:12pt;} 
FORM.alt { font-size:12pt; margin-top: 5px } 
FORM.fname { font-size:12pt; margin: 0px} 
INPUT.fname { font-family:"Courier New", Courier, mono;font-size:12pt; border:0; background-color:<?php echo $corfundo2?> }
FORM.dir { font-size:12pt; margin: 0px} 
INPUT.dir { font-family:"Courier New", Courier, mono;font-size:12pt; border:0; background-color:<?php echo $corfundo?> }
<?php if( strstr(getenv("HTTP_USER_AGENT"), "MSIE")) { ?>
input.checkbox { border:none }
<?php } else { ?>
input.checkbox { }
<?php } ?>
INPUT { font-size:12pt; border:1px solid #555555}
INPUT.cline { background-color:#000000; font-family:"Courier new", monospace; font-size:12pt; color:#FFFFFF; border:0} 
TEXTAREA { border:1px solid #555555 }
TEXTAREA.edit { font-family:"Courier New", Courier, mono;font-size:10pt; background-color:#EFEFEF } 
SELECT { font-size:12pt;}
p.link a:hover {background-color: #2B2E21;;color:#fff;}
p.link a:link span{display: none;}
p.link a:visited span{display: none;}
p.link a:hover span {
  position: absolute;
  margin:15px 0px 0px 20px;
  background-color: beige;
  max-width:220;
  padding: 2px 10px 2px 10px;
  border: 1px solid #C0C0C0;
  font: normal 10px/12px verdana;
  color: #000;
  text-align:left;
  display: block;
}
<?php for($i=1;$i<999;$i++) echo "table.sitehide$i .sitegroup$i { display: none; }\n"; ?>
