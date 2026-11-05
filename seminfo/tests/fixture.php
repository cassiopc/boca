<?php
// Synthetic data only; this file is never installed in an event image.
if (PHP_SAPI !== 'cli') exit(1);
chdir('/var/www/boca/src');require_once 'db.php';
$_SESSION=['locr'=>getcwd(),'usertable'=>['username'=>'test','usernumber'=>1000,'usersitenumber'=>1,'contestnumber'=>1]];
$action=$argv[1];$c=DBConnect();
if ($action==='seed') {
    DBNewProblem(1,['number'=>1,'name'=>'A','fullname'=>'Ensaio sintetico: soma','basename'=>'A','inputfilename'=>'A.zip','inputfilepath'=>'/tmp/A.zip','autojudge'=>1023]);
    echo json_encode(['ok'=>true]);
} elseif ($action==='submit') {
    $expected=[];$number=1;
    foreach (range(1,10) as $i) foreach ([[1,'Main.c'],[2,'Main.cpp'],[3,'A.java'],[5,'Main.py3']] as [$lang,$file]) {
        if (!DBNewRun(['contest'=>1,'site'=>1,'user'=>2000+$i,'problem'=>1,'lang'=>$lang,'filename'=>$file,'filepath'=>'/tmp/'.$file,'runnumber'=>$number])) exit(1);
        $expected[$number++]=1;
    }
    foreach ([[1,'Wrong.c',6],[1,'CompileError.c',2],[1,'Timeout.c',4],
              [3,'WrongJava.java',6],[3,'CompileErrorJava.java',2],[3,'TimeoutJava.java',4],
              [3,'Multi.zip',1]] as [$lang,$file,$answer]) {
        // The Java run harness uses the problem basename as its main class.
        // Keep the source ZIP name different from the problem package A.zip:
        // both are staged in the same judging directory by BOCA.
        $submittedName=$lang===3 && $file!=='Multi.zip' ? 'A.java' : $file;
        if (!DBNewRun(['contest'=>1,'site'=>1,'user'=>2001,'problem'=>1,'lang'=>$lang,'filename'=>$submittedName,'filepath'=>'/tmp/'.$file,'runnumber'=>$number])) exit(1);
        $expected[$number++]=$answer;
    }
    echo json_encode(['ok'=>true,'expected'=>$expected]);
} elseif ($action==='freeze') {
    pg_query($c,'UPDATE sitetable SET sitelastmilescore=300 WHERE contestnumber=1 AND sitenumber=1');
    pg_query($c,'UPDATE runtable SET rundatediff=600 WHERE contestnumber=1');
    echo json_encode(['ok'=>true]);
} elseif ($action==='end') {
    pg_query($c,'UPDATE sitetimetable SET sitestartdate=extract(epoch from now())::integer-6060 WHERE contestnumber=1 AND sitenumber=1');
    echo json_encode(['ok'=>true]);
} elseif (in_array($action,['pending','judged','rejudge','deleted'])) {
    $status=['pending'=>'openrun','rejudge'=>'judged+','judged'=>'judged','deleted'=>'deleted'][$action];
    pg_query_params($c,'UPDATE runtable SET runstatus=$1 WHERE contestnumber=1 AND runnumber=1',[$status]);
    echo json_encode(['ok'=>true]);
} elseif ($action==='results') {
    $r=pg_query($c,'SELECT runnumber,runanswer,runstatus,autoanswer FROM runtable WHERE contestnumber=1 ORDER BY runnumber');
    echo json_encode(pg_fetch_all($r) ?: []);
} elseif ($action==='diagnostic') {
    $r=pg_query($c,"SELECT runnumber,runfilename,runanswer,autoanswer,convert_from(lo_get(autostderr),'UTF8') AS detail FROM runtable WHERE contestnumber=1 AND runlangnumber=3 ORDER BY runnumber");
    echo json_encode(pg_fetch_all($r) ?: []);
}
