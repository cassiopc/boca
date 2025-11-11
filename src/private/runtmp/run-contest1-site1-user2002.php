<!-- 1762900219 --> <?php exit; ?>	0	verde	00ff00		0000ff
<br>
<table width="100%" border=1>
 <tr>
  <td><b>Run #</b></td>
<td><b>Time</b></td>
  <td><b>Problem</b></td>
  <td><b>Language</b></td>
  <td><b>Answer</b></td>
  <td><b>File</b></td>
 </tr>
 <tr>
  <td nowrap>999532604</td>
  <td nowrap>13</td>
  <td nowrap>A</td>
  <td nowrap>C++17</td>
  <td>YES <img alt="verde" width="15" src="/boca/balloons/df8b2dbf1db68daa315ab22e04677e6f.png" /></td>
<td nowrap><a href="../filedownload.php?oid=16623&filename=STJ2SUpaTXgwSjRueXUzeXlZTGNvZEwzZE1mZW83Rzg5eVMrMm5Rbm9CenNPMlBwWmdTTU5Ka2YzWHAwYjBRbDFNZEFYdUJjN1FkNGhxbkRqZ3RGUEE9PQ%3D%3D&check=c0eb6ddb1dfd19f219d6aefca3351bfdcef3c81a2a1f56c0ae63b05fa573b17d">a.cpp</a></td>
 </tr>
 <tr>
  <td nowrap>999632905</td>
  <td nowrap>14</td>
  <td nowrap>F</td>
  <td nowrap>C++17</td>
  <td>YES <img alt="" width="15" src="/boca/balloons/d5eed37f81ea68725d42553724bef434.png" /></td>
<td nowrap><a href="../filedownload.php?oid=16624&filename=K2ROellFMHAzSFhtM1ltQ1FUdzdMM3VhaE13MXgzZGlsVVBNWWl3WnNwZzg4M3VBNXN0ZVFCeUl4bFpsTXlXTDBXUmoyNms0TVpwdTdzU1lZdVUwQnc9PQ%3D%3D&check=2c8238d72d768cc871a3838791c620fc2bcd2017e07d80bbedebb6deaa031bc4">a.cpp</a></td>
 </tr>
</table><br><br><center><b>To submit a program, just fill in the following fields:</b></center>
<form name="form1" enctype="multipart/form-data" method="post" action="run.php">
  <input type=hidden name="confirmation" value="noconfirm" />
  <center>
    <table border="0">
      <tr> 
        <td width="25%" align=right>Problem:</td>
        <td width="75%">
          <select name="problem" onclick="Arquivo()">
<option selected value="-1"> -- </option>
<option value="1">A</option>
<option value="2">F</option>
<option value="3">E</option>
	  </select>
        </td>
      </tr>
      <tr> 
        <td width="25%" align=right>Language:</td>
        <td width="75%"> 
          <select name="language" onclick="Arquivo()">
<option selected value="-1"> -- </option>
<option value="1">C</option>
<option value="2">C++17</option>
<option value="3">Java</option>
<option value="4">Python2</option>
<option value="5">Python3</option>
	  </select>
        </td>
      </tr>
      <tr> 
        <td width="25%" align=right>Source code:</td>
        <td width="75%">
	  <input type="file" name="sourcefile" size="40" onclick="Arquivo()">
        </td>
      </tr>
    </table>
  </center>
  <script language="javascript">
    function conf() {
      if (document.form1.problem.value != '-1' && document.form1.language.value != '-1') {
       if (confirm("Confirm submission?")) {
        document.form1.confirmation.value='confirm';
       }
      } else {
        alert('Invalid problem and/or language');
      }
    }
  </script>
  <center>
      <input type="submit" name="Submit" value="Send" onClick="conf()">
      <input type="reset" name="Submit2" value="Clear">
  </center>
</form>
