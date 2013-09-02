<?php
////////////////////////////////////////////////////////////////////////////////
//BOCA Online Contest Administrator
//    Copyright (C) 2003-2013 by BOCA Development Team (bocasystem@gmail.com)
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
// Last modified 03/sep/2013 by cassio@ime.usp.br

////////////////////////////funcoes para placar///////////////////////////////////////////////
function ScoreCMP($a,$b) {
	if ($a["totalcount"]=="") $a["totalcount"]=0;
	if ($b["totalcount"]=="") $b["totalcount"]=0;
	if ($a["totaltime"]=="") $a["totaltime"]=0;
	if ($b["totaltime"]=="") $b["totaltime"]=0;

	if ($a["totalcount"]>$b["totalcount"]) return 1;
	else if ($a["totalcount"]<$b["totalcount"]) return -1;
	else {
		if ($a["totaltime"]<$b["totaltime"]) return 1;
		else if ($a["totaltime"]>$b["totaltime"]) return -1;
		else {
			if(isset($a["first"]) && $a["first"] != 0) {
				if($a["first"]<$b["first"]) return 1;
				else if($a["first"]>$b["first"]) return -1;
			}
			if ($a["user"]<$b["user"]) return 1;
			else if ($a["user"]>$b["user"]) return -1;
			else return 0;
		}
	}
}
function ordena($a) {
	$r = array();
	for ($i=0; $i<count($a); $i++) {
		$max=null;
		reset($a);
		while ( list ($e, $c) = each($a) ) {
			if ($a[$e]!=null && ($max==null || ScoreCMP($a[$e],$max) > 0)) {
			     $j=0;
			     for(;$j<$i;$j++)
			       if($r[$j]['user']==$a[$e]['user'] && $r[$j]['site']==$a[$e]['site']) break;
			     if($j>=$i) {
				$max = $a[$e];
				$maxe = $e;
			     }
			}
		}
		if ($max==null) break;
		$r[$i] = $max;
		$a[$maxe] = null;
	}
	return $r;
}
function DBScore($contest, $verifylastmile, $hor=-1, $globalsite='0') {
	$c = DBConnect();
	$r = DBExec($c, "select sitenumber as number from sitetable where contestnumber=$contest and siteactive='t'",
	     "DBScore(get site)");
	$n = DBnlines($r);
	if ($n == 0) {
		LOGError("Unable to get site information. No active sites available (contest=$contest)");
		MSGError("Unable to get site information. No active sites available. Contact an admin now!");
		exit;
	}
	$a = array();
	$resp = array();
	$whichsites=explode(',',$globalsite);
	for ($i=0;$i<$n;$i++) {
		$a = DBRow($r,$i);
		if(in_array($a["number"], $whichsites) || in_array(0,$whichsites)) {
			list($resp1,$data0) = DBScoreSite($contest, $a["number"], $verifylastmile, $hor);
			$resp =  array_merge($resp, $resp1);
		}
	}
	$ds = DIRECTORY_SEPARATOR;
	if($ds=="") $ds = "/";
	$probs=DBGetProblems($contest); $nprobs=count($probs);

	$scoreitems = glob($_SESSION['locr'] . $ds . "private" .$ds . "remotescores" . $ds . "score*.dat", GLOB_NOSORT);
	array_multisort(array_map('filemtime', $scoreitems), SORT_NUMERIC, SORT_DESC, $scoreitems);

	foreach ($scoreitems as $fname) {
		$namear=explode('_',$fname);
		$overloadsite=-1;
		if(isset($namear[3]) && trim($namear[2]) != '' && is_numeric($namear[2])) $overloadsite=$namear[2];
		$fc=file_get_contents($fname);
		if(($arr = unserialize(base64_decode($fc)))===false) {
			LOGError("File " . sanitizeText($fname) . " is not compatible");
		} else { 
			if(is_array($arr)) {
				if(isset($arr['site'])) {
					$site=$arr['site']; 
					if($overloadsite>0) $site=$overloadsite;
					if(!in_array($site, $whichsites) && !in_array(0,$whichsites)) continue;
					$fine=1;
					reset($resp);
					while(list($e, $c) = each($resp)) {
						if($resp[$e]['site']==$site) { $fine=0; break; }					
					}
					if($fine) {
						list($arr,$data0) = DBScoreSite($contest, $site, $verifylastmile, $hor, $arr);
						reset($arr);
						while(list($ee,$cc) = each($arr)) {
							if($site != $arr[$ee]['site']) {
								$arr[$ee]=null;
								unset($arr[$ee]);
							} else {
								// just to make the color of the other site changed to the color of the problem in this site
								while(list($e2,$c2) = each($arr[$ee]["problem"])) {
									for($prob=0; $prob<$nprobs; $prob++) {
										if($probs[$prob]['number']==$e2) {
											$arr[$ee]['problem'][$e2]['color'] = $probs[$prob]['color'];
											$arr[$ee]['problem'][$e2]['colorname'] = $probs[$prob]['colorname'];
											break;
										}
									}
								}
							}
						}
						$resp = array_merge($resp, $arr);
					}
				} else {
// old version -- just for compatibility ---
					while(list($ee,$cc) = each($arr)) {
						$fine=1;
						reset($resp);
						while(list($e, $c) = each($resp)) {
							if($resp[$e]['site']==$arr[$ee]['site']) { $fine=0; break; }
						}
						if($fine==0) $arr[$ee]=null;
						else {
							// just to make the color of the other site changed to the color of the problem in this site
							while(list($e2,$c2) = each($arr[$ee]["problem"])) {
								for($prob=0; $prob<$nprobs; $prob++) {
									if($probs[$prob]['number']==$e2) {
										$arr[$ee]['problem'][$e2]['color'] = $probs[$prob]['color'];
										$arr[$ee]['problem'][$e2]['colorname'] = $probs[$prob]['colorname'];
										break;
									}
								}
							}
						}
					}
					$resp = array_merge($resp, $arr);
// ---- end of old version ---
				}
			}
//		MSGError("got scores from $fname");
		}
	}
	if (($result = ordena ($resp)) === false) {
		LOGError("Error while sorting scores (contest=$contest).");
		MSGError("Error while sorting scores. Contact an admin now!");
	}
	return $result;
}

function DBBalloon($contest, $site, $user, $problem, $localsite=true, $c=null) {
	if($c==null)
		$c = DBConnect();
	if (($b = DBSiteInfo($contest, $site, $c)) == null)
		exit;
	if ($localsite) {
		if (($blocal = DBSiteInfo($contest, $_SESSION["usertable"]["usersitenumber"], $c)) == null)
			exit;
	} else $blocal = $b;
	if (($ct = DBContestInfo($contest,$c)) == null)
		exit;

	$t = time();
	$ta = $blocal["currenttime"];
	$tf = $b["siteduration"];
	$r = DBExec($c, "select r.rundatediff as time, a.yes as yes from " .
		"runtable as r, answertable as a where r.runanswer=a.answernumber and " .
		"a.contestnumber=$contest and r.usernumber=$user and r.runproblem=$problem and " .
		"r.contestnumber=$contest and r.runsitenumber=$site and (r.runstatus ~ 'judged' or r.runstatus ~ 'judged+') and " .
		"r.rundatediff>=0 " . 
				"and r.rundatediff<=$tf " .
				"and r.rundatediffans<=$ta " . 
		"order by r.rundatediff", "DBBalloon(get runs)");
	$n = DBnlines($r);
	for ($i=0;$i<$n;$i++) {
		$a = DBRow($r,$i);
		if($a["yes"]=='t') return true;
	}
	return false;
}
function DBRecentNews($contest, $site, $verifylastmile, $minutes=3) {
	if (($b = DBSiteInfo($contest, $site)) == null)
		exit;
	if (($blocal = DBSiteInfo($contest, $_SESSION["usertable"]["usersitenumber"])) == null)
		exit;
	if (($ct = DBContestInfo($contest)) == null)
		exit;

	$t = time();
	$ta = $blocal["currenttime"];
	$taa = $ta - $minutes*60;
	if ($verifylastmile)
		$tf = $b["sitelastmilescore"];
	else {
		$tf = $b["siteduration"];
	}

	$c = DBConnect();
	$r = DBExec($c, "select a.yes as yes, p.problemcolor as color, p.problemcolorname as colorname, u.userfullname as userfullname, " .
		"u.usernumber as usernumber, p.problemnumber as problemnumber, p.problemname, (r.rundatediffans>$ta) as fut, min(r.rundatediff) as time from " .
		"runtable as r, answertable as a, problemtable as p, usertable as u where r.runanswer=a.answernumber and " .
		"p.contestnumber=$contest and a.contestnumber=$contest and r.usernumber = u.usernumber and u.usertype='team' and " .
		"p.problemnumber=r.runproblem and r.contestnumber=$contest and r.runsitenumber=$site and u.userenabled='t' and " .
		"r.rundatediff>=$taa and r.rundatediff<=$tf and r.rundatediff<=$ta and u.contestnumber=$contest and u.usersitenumber=$site and " . 
                "((a.yes='t' and r.rundatediffans<=$ta) or (r.rundatediffans>$ta)) " .
		"group by a.yes,p.problemcolor,p.problemcolorname,p.problemname,u.userfullname,u.usernumber,p.problemnumber,fut order by time", "DBRecentNews(get runs)");
	$n = DBnlines($r);
        $a = array();
	for ($i=0;$i<$n;$i++) {
		$a[$i] = DBRow($r,$i);
		if($a[$i]["fut"]=='t' && $a[$i]["yes"]=='t') $a[$i]["yes"]='f';
	}
	return $a;
}
function DBScoreSite($contest, $site, $verifylastmile, $hor=-1, $data=null) {
	if (($blocal = DBSiteInfo($contest, $_SESSION["usertable"]["usersitenumber"])) == null)
		exit;
	if (($b = DBSiteInfo($contest, $site, null, false)) == null)
		$b=$blocal;
	if (($ct = DBContestInfo($contest)) == null)
		exit;

	$t = time();
	$ta = $blocal["currenttime"];
	if($hor >= 0) $ta = $hor;
	if ($verifylastmile)
		$tf = $b["sitelastmilescore"];
	else {
		$tf = $b["siteduration"];
	}
	if($data != null && is_numeric($data)) {
		if($data < $ta) $ta = $data;
		$data=null;
	}

	$data0=array();
	if($data==null) {
		$c = DBConnect();
		$resp = array();
        $r = DBExec($c, "select * from usertable where contestnumber=$contest and usersitenumber=$site and ".
					"usertype='team' and userlastlogin is not null and userenabled='t'", "DBScoreSite(get users)");
		$n = DBnlines($r);
		for ($i=0;$i<$n;$i++) {
			$a = DBRow($r,$i);
			$resp[$a["usernumber"]]["user"]=$a["usernumber"];
			$resp[$a["usernumber"]]["site"]=$a["usersitenumber"];
			$resp[$a["usernumber"]]["username"]=$a["username"];
			$resp[$a["usernumber"]]["usertype"]=$a["usertype"];
			$resp[$a["usernumber"]]["userfullname"]=$a["userfullname"];
			$resp[$a["usernumber"]]["totaltime"]=0;
			$resp[$a["usernumber"]]["totalcount"]=0;
			$resp[$a["usernumber"]]["problem"]=array();
		}
		$r = DBExec($c, "select r.usernumber as user, p.problemname as problemname, r.runproblem as problem, ".
					"p.problemcolor as color, p.problemcolorname as colorname, " .
					"r.rundatediff as time, r.rundatediffans as anstime, a.yes as yes, r.runanswer as answer from " .
					"runtable as r, answertable as a, problemtable as p where r.runanswer=a.answernumber and " .
					"a.contestnumber=$contest and p.problemnumber=r.runproblem and p.contestnumber=$contest and " .
					"r.contestnumber=$contest and r.runsitenumber=$site and (r.runstatus ~ 'judged' or r.runstatus ~ 'judged+') and " .
					"r.rundatediff>=0 and r.rundatediff<=$tf and r.rundatediffans<=$ta " . 
					"order by r.usernumber, r.runproblem, r.rundatediff", "DBScoreSite(get runs)");
		$n = DBnlines($r);
		$a = array();
		for ($i=0;$i<$n;$i++) {
			$a[$i] = DBRow($r,$i);
		}
		$data0['n']=$n;
		$data0['resp']=$resp;
		$data0['a']=$a;
		$data0['site']=$site;
	} else {
		$resp=$data['resp'];
		$n=$data['n'];
		$a=$data['a'];
	}

	$i=0;
	while ($i<$n) {
		if($a[$i]["anstime"] > $ta) { $i++; continue; }
		$user = $a[$i]["user"];
		$problem = $a[$i]["problem"];
		$time = 0;
		$k = 0;
		if(!isset($resp[$user])) { $i++; continue; }
		$resp[$user]["user"] = $user;
		$resp[$user]["site"] = $site;
		$resp[$user]["problem"][$problem]["name"] = $a[$i]["problemname"];
		$resp[$user]["problem"][$problem]["color"] = $a[$i]["color"];
		$resp[$user]["problem"][$problem]["colorname"] = $a[$i]["colorname"];
		$resp[$user]["problem"][$problem]["solved"] = false;
		$resp[$user]["problem"][$problem]["judging"] = false;
		$resp[$user]["problem"][$problem]["time"] = 0;
		$resp[$user]["problem"][$problem]["penalty"] = 0;
		$resp[$user]["problem"][$problem]["count"] = 0;

		while ($i<$n && $a[$i]["anstime"] <= $ta && $a[$i]["user"]==$user && $a[$i]["problem"]==$problem && $a[$i]["yes"]!='t') {
			$time += (int) (($ct["contestpenalty"])/60);
			$k++;
			$i++;
		}
		
		$resp[$user]["problem"][$problem]["count"] = $k;
		if ($i>=$n) break; 
		if($a[$i]["anstime"] <= $ta && $a[$i]["user"]==$user && $a[$i]["problem"]==$problem && $a[$i]["yes"]=='t') {
			$timet = (int) (($a[$i]["time"])/60);
			if(!isset($resp[$user]["first"]) || $timet < $resp[$user]["first"])
				$resp[$user]["first"] = $timet;
			$time += $timet;
			$resp[$user]["problem"][$problem]["time"] = $timet;
			$resp[$user]["problem"][$problem]["penalty"] = $time;
			$resp[$user]["problem"][$problem]["solved"] = true;
			$resp[$user]["problem"][$problem]["count"]++;
			$resp[$user]["totaltime"] += $time;
			$resp[$user]["totalcount"]++;
		}
		while ($i<$n && $a[$i]["user"]==$user && $a[$i]["problem"]==$problem) {
			$i++;
		}
	}

	if($data==null) {
		$aa = DBRecentNews($contest, $site, $verifylastmile, $ta);
		$data0['aa']=$aa;
	} else $aa=$data['aa'];

	for($i=0; $i<count($aa); $i++) {
		if($aa[$i]["fut"]=='t') {
			$resp[$aa[$i]["usernumber"]]["problem"][$aa[$i]["problemnumber"]]["judging"] = true;
		}
	}

	if (($result = ordena ($resp)) === false) {
		LOGError("Error while sorting scores (contest=$contest, site=$site).");
		MSGError("Error while sorting scores. Contact an admin now!");
	}
	return array($result,$data0);
}
// eof
?>
