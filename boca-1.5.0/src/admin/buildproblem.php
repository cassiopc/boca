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
// Last modified 31/aug/2012 by cassio@ime.usp.br
require('header.php');
if(($ct = DBContestInfo($_SESSION["usertable"]["contestnumber"])) == null)
	ForceLoad("../index.php");

?>
<br><br><center><b><u>
To build a problem package using standard script files, fill in the following fields.</u></b></center>
<br><br>

<form name="form1" enctype="multipart/form-data" method="post" action="problem.php">
  <input type=hidden name="noflush" value="true" />
  <input type=hidden name="confirmation" value="noconfirm" />
  <script language="javascript">
    function conf() {
	        var s2 = String(document.form1.probleminput.value);
	        var s1 = String(document.form1.problemsol.value);
			if(document.form1.fullname.value=="" || document.form1.basename=="" || document.form1.timelimit=="" || s1.length<4 || s2.length<4) {
				alert('Sorry, mandatory fields are empty');
			} else {
				var s1 = String(document.form1.problemdesc.value);
				var l = s1.length;
				if(l >= 3 && (s1.substr(l-3,3).toUpperCase()==".IN" ||
							 s1.substr(l-4,4).toUpperCase()==".OUT" ||
							 s1.substr(l-4,4).toUpperCase()==".SOL" ||
							 s1.substr(l-2,2).toUpperCase()==".C" ||
							 s1.substr(l-2,2).toUpperCase()==".H" ||
							 s1.substr(l-3,3).toUpperCase()==".CC" ||
							 s1.substr(l-3,3).toUpperCase()==".GZ" ||
							 s1.substr(l-4,4).toUpperCase()==".CPP" ||
							 s1.substr(l-4,4).toUpperCase()==".HPP" ||
							 s1.substr(l-4,4).toUpperCase()==".ZIP" ||
							 s1.substr(l-4,4).toUpperCase()==".TGZ" ||
							 s1.substr(l-5,5).toUpperCase()==".JAVA")) {
					alert('Description file has invalid extension: ...'+s1.substr(l-3,3));
				} else {
					document.form1.confirmation.value='confirm';
				}
			}
     }
  </script>
  <center>
    <table border="0">
      <tr>
        <td width="35%" align=right>Problem Fullname:</td>
        <td width="65%">
          <input type="text" name="fullname" value="" size="50" maxlength="100" />
        </td>
      </tr>
      <tr>
	 <td width="35%" align=right>Problem Basename (a.k.a. name of class expected to have the main):</td>
        <td width="65%">
          <input type="text" name="basename" value="" size="50" maxlength="100" />
        </td>
      </tr>
      <tr>
	 <td width="35%" align=right>Description file (PDF, txt, ...):</td>
        <td width="65%">
          <input type="file" name="problemdesc" value="" size="40" />
        </td>
      </tr>
      <tr>
	 <td width="35%" align=right>Problem input file:</td>
        <td width="65%">
          <input type="file" name="probleminput" value="" size="40" />
        </td>
      </tr>
      <tr>
	 <td width="35%" align=right>Problem correct output file:</td>
        <td width="65%">
          <input type="file" name="problemsol" value="" size="40" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Timelimit (in sec):</td>
        <td width="65%">
          <input type="text" name="timelimit" value="" size="10" />
(optional: use a , followed by the number of repetitions to run)
        </td>
      </tr>
    </table>
  </center>
  <center>
      <input type="submit" name="Submit5" value="Send" onClick="conf()">
      <input type="reset" name="Submit4" value="Clear">
  </center>
</center>
</form>

</body>
</html>
