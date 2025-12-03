<!-- 1762899904 --> <?php exit; ?>	0
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
  <td nowrap>998117101</td>
  <td nowrap>11</td>
  <td nowrap>A</td>
  <td nowrap>C++17</td>
  <td>Not answered yet</td>
<td nowrap><a href="../filedownload.php?oid=16620&filename=VktsN2xGL1E3eWVua0ZNS09DY2hLTkNTZnVDWVQ2TGovMmJlVXRndWJCczd3bUs0VlZRVXpqZFVFUUIxV25mZmlFcmIrWmN6WTFEcDNmZWQ2OEZXeHc9PQ%3D%3D&check=22ffc696bfffc6981b9af640285edbd60c703af9923e9adcb61671a9f38f9c90">a.cpp</a></td>
 </tr>
 <tr>
  <td nowrap>998214602</td>
  <td nowrap>11</td>
  <td nowrap>F</td>
  <td nowrap>C++17</td>
  <td>Not answered yet</td>
<td nowrap><a href="../filedownload.php?oid=16621&filename=WjJndk1ITGxobUNVOTcxTkxETng5VlM1QWVBY25rdGVabFZrV0dyR2g5ZDZYSUZNMTBSTENOcWxaWDVmVUUwYVJlSm5kOUd2QW02T21Zckx2Mzk1d0E9PQ%3D%3D&check=1620687b07311b7cd0a334f88a03c693db14b1db7b64fbb8f850f5aca19a45c8">a.cpp</a></td>
 </tr>
 <tr>
  <td nowrap>998376803</td>
  <td nowrap>11</td>
  <td nowrap>E</td>
  <td nowrap>C++17</td>
  <td>Not answered yet</td>
<td nowrap><a href="../filedownload.php?oid=16622&filename=UmxzU3ZCMW1Xb2NMSUk1OFZMcWpIMW9kTEZGMHZMUFJEVEh4TmxrWGJIdG0xdFdwdnQ1MENqQWhWNjhOaVdNN1FWaHdranJyaXFEV2FobGxqZndjTlE9PQ%3D%3D&check=7133e2fbf6b04bbc7b58168032c41c8f36c879d27093087ba01746cfc2e4ccb6">a.cpp</a></td>
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
