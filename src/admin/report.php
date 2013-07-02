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
// Last modified 07/nov/2012 by cassio@ime.usp.br

require 'header.php';

if (isset($_GET)) {
}
?>
<br><br>
  <center>
<?php
//      echo "<b>Logs</b><br /><br />\n";
      echo " <a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('report/score.php?p=2', ".
		"'Complete Scoreboard','width=800,height=600,scrollbars=yes,toolbar=yes,menubar=yes,".
		"resizable=yes')\">Scoreboard</a><br />\n";
      echo " <a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('report/score.php?p=0', ".
		"'Complete Scoreboard','width=800,height=600,scrollbars=yes,toolbar=yes,menubar=yes,".
		"resizable=yes')\">Detailed Scoreboard</a><br />\n";
      echo " <a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('report/score.php?p=0&hor=0', ".
		"'Complete Scoreboard','width=800,height=600,scrollbars=yes,toolbar=yes,menubar=yes,".
		"resizable=yes')\">Interactive Scoreboard</a><br />\n";
      echo " <a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('report/score.php?p=1', ".
		"'Public Scoreboard','width=800,height=600,scrollbars=yes,toolbar=yes,menubar=yes,".
		"resizable=yes')\">Delayed Scoreboard</a><br />\n";
      echo " <a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('report/run.php', ".
		"'Run List','width=800,height=600,scrollbars=yes,toolbar=yes,menubar=yes,".
		"resizable=yes')\">Run List</a><br />\n";
      echo " <a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('report/clar.php', ".
		"'Clarification List','width=800,height=600,scrollbars=yes,toolbar=yes,menubar=yes,".
		"resizable=yes')\">Clarification List</a><br />\n";
      echo " <a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('report/task.php', ".
		"'Task List','width=800,height=600,scrollbars=yes,toolbar=yes,menubar=yes,".
		"resizable=yes')\">Task List</a><br />\n";
      echo " <a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('report/site.php', ".
		"'Start/Stop Logs','width=800,height=600,scrollbars=yes,toolbar=yes,menubar=yes,".
		"resizable=yes')\">Site Start/Stop Logs</a><br />\n";
      echo " <a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('report/icpc.php', ".
		"'ICPC File','width=800,height=600,scrollbars=yes,toolbar=yes,menubar=yes,".
		"resizable=yes')\">ICPC File</a><br />\n";

      echo " <a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('report/webcast.php', ".
		"'Webcast File','width=800,height=600,scrollbars=yes,toolbar=yes,menubar=yes,".
		"resizable=yes')\">Webcast File</a><br />\n";

      echo " <a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('report/stat.php', ".
		"'Problem Statistics','width=800,height=600,scrollbars=yes,toolbar=yes,menubar=yes,".
		"resizable=yes')\">Statistics</a><br />\n";

/*
      echo "<br /><br />\n";
      echo "<b>Statistics</b><br /><br />\n";

      echo " <a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('report/statproblem.php', ".
		"'Problem Statistics','width=800,height=600,scrollbars=yes,toolbar=yes,menubar=yes,".
		"resizable=yes')\">Problems</a><br />\n";
      echo " <a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('report/statanswer.php', ".
		"'Answer Statistics','width=800,height=600,scrollbars=yes,toolbar=yes,menubar=yes,".
		"resizable=yes')\">Answers</a><br />\n";
      echo " <a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('report/statuser.php', ".
		"'User Statistics','width=800,height=600,scrollbars=yes,toolbar=yes,menubar=yes,".
		"resizable=yes')\">Users</a><br />\n";
      echo " <a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('report/statlanguage.php', ".
		"'Language Statistics','width=800,height=600,scrollbars=yes,toolbar=yes,menubar=yes,".
		"resizable=yes')\">Languages</a><br />\n";
      echo " <a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('report/statrun.php', ".
		"'Run Statistics','width=800,height=600,scrollbars=yes,toolbar=yes,menubar=yes,".
		"resizable=yes')\">Runs</a><br />\n";
      echo " <a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('report/statclar.php', ".
		"'Clarification Statistics','width=800,height=600,scrollbars=yes,toolbar=yes,menubar=yes,".
		"resizable=yes')\">Clarifications</a><br />\n";
*/
?>
  </center>
</form>

</body>
</html>
