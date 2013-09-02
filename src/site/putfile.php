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
//Last updated 03/nov/2012 by cassio@ime.usp.br
//
//PC^2 integration developed by Fabio Antonio Avellaneda Pachon
//
//Now it is also the integration of scores of BOCA
//
$quiet=true;
require 'header.php';
$ds = DIRECTORY_SEPARATOR;
if($ds=="") $ds = "/";
$remotedir=$ds . "private" . $ds . "remotescores";

if(($ct = DBContestInfo($_SESSION["usertable"]["contestnumber"])) == null)
exit;
if(($st = DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
exit;

if(is_writable($_SESSION["locr"] . $remotedir)) {
	if(isset($_POST['PC2']) && is_numeric($_POST['PC2']) && $_POST['PC2'] > 0) {
		$site = $_POST['PC2'];
		$problemCount = 4;
		for($i=0;$i<$problemCount;$i++) {
			$problems[$i]["name"]=chr($i+ord("A"));
			$problems[$i]["color"]="FFFFFF";
			$problems[$i]["colorname"]="white";
			$problems[$i]["solved"]=0;
			$problems[$i]["judging"]=false;
			$problems[$i]["time"]=0;
			$problems[$i]["penalty"]=0;
			$problems[$i]["count"]=0;
		}
		
		/*** Load PC2 summary.html **/
		$board=base64_decode($_POST['data']);
		
		/*** a new dom object ***/ 
		$dom = new domDocument; 
		
		/*** load the html into the object ***/ 
		$dom->loadHTML($board); 
		
		/*** discard white space ***/ 
		$dom->preserveWhiteSpace = false; 
		
		/*** the table by its tag name ***/ 
		$tables = $dom->getElementsByTagName('table'); 
		
		/*** get all rows from the table ***/ 
		$rows = $tables->item(0)->getElementsByTagName('tr'); 
		
		/*** loop over the table rows ***/ 
		$userCount = 1;
		$firstProblem=-1;
		foreach ($rows as $row) 
		{
			//teams are always being renumbered since their number is not important... just they have to be different 
			$team["user"]=(string)$userCount;
			$team["site"]=(string)$site;
			
			/*** get each column by tag name ***/ 
			$cols = $row->getElementsByTagName('td');
			$colCount=0; 
			$probCount=1;
			//Ignore empty cols
			if($cols->item(0)->nodeValue=="")
			{
				continue;
			}
			foreach ($cols as $col)
			{
				switch($colCount)
				{
					//First column, start the problem counter and by default, the team hasnt solved a problem
					case 0;
                    $probCount=1;
                    $firstProblem=-1;
					break;
					//First column: team name
					case 1:
						$team["username"]=$col->nodeValue;
//                    echo "username: $col->nodeValue\n";
						$team["usertype"]="team";
						$team["userfullname"]=$col->nodeValue;
						break;
						//Second column: How many problems does the team solved at this time
					case 2:
						$team["totalcount"]=(int)$col->nodeValue;
						//                   echo "totalcount: $col->nodeValue\n";
						break;
						//Third column: Elapsed time, including penalisations
					case 3:
						$team["totaltime"]=$col->nodeValue;
						//                   echo "totaltime: $col->nodeValue\n";
						break;
						//No more problems to process
					case $problemCount+4:
						$total[$userCount-1]=$team;
						//If firstProblem is different to -1 is because the team has solved at least one problem
						if($firstProblem!=-1)
						{
							$total[$userCount-1]["first"]=$firstProblem;
						}
						break;
						//Problems columns
					default:
						list($count, $time) = split('/', $col->nodeValue);
						//echo "Sol: $solved / time: $count";
						if($time=="--")
						{
							$solved=false;
							$time="";
						}else
						{
							$solved=true;
						}
						$team["problem"][$probCount]=$problems[$probCount];
						$team["problem"][$probCount]["solved"]=$solved;
						$team["problem"][$probCount]["time"]=$time;
						//I can calculate the effective time and the penalisation time, if needed
						$team["problem"][$probCount]["penalty"]=$time;
						$team["problem"][$probCount]["count"]=$count;
						//look for the time which first problem was solved
						if($firstProblem==-1 && $solved)
						{
							$firstProblem=$time;
						}else
						{
							if($solved && $time<$firstProblem)
							{
								$firstProblem=$time;
							}
						}                   
						$probCount++; 
				}//end switch
				
				$colCount++;	    //echo $col->nodeValue."\t";
			}//end col for
			
			$userCount++;
//        echo '<hr />'; 
		} //end row col
//echo print_r($total);
		$total=base64_encode(serialize($total));
	} else
		$total=$_POST['data'];
	
	if($_SESSION["usertable"]["usericpcid"] != '' && $_SESSION["usertable"]["usericpcid"] > 0)
	{
		$arr = unserialize(base64_decode($total));
		$arr['site']=$_SESSION["usertable"]["usericpcid"];
		$total=base64_encode(serialize($arr));
	}

	$fn = tempnam($_SESSION["locr"] . $remotedir,"tmp_");
	$fout = fopen($fn,"wb");
	fwrite($fout,$total,10000000);
	fclose($fout);

	// test the format of the file
	$fc=file_get_contents($fn);
	if(($arr = unserialize(base64_decode($fc)))===false ||
	   !is_array($arr) || !isset($arr['site'])) {
		echo "FAILED: File " . $fn . " is not compatible\n";
	} else {

		if(@rename($fn, $_SESSION["locr"] . $remotedir . $ds . "score_" . $_SESSION["usertable"]["username"] . 
				   "_" . $_SESSION["usertable"]["usericpcid"] . "_x" //. md5(getIP()) 
				   . ".dat"))
			echo "SCORE UPLOADED OK\n";
		else
			echo "FAILED: UPDATE SCORE ERROR\n";
	}
} else echo "FAILED: PERMISSION DENIED IN THE SERVER\n";
?>
