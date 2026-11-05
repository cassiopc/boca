<?php
// Teacher-side CLI only: there is deliberately no network API for full results.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit(1); }
ini_set('display_errors', 'stderr');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);
chdir('/var/www/boca/src');
require_once 'db.php';
// Logging uses a separate DB connection: bootstrap identity must already exist
// before DBNewContest commits its new administrator/site.
$_SESSION = ['locr' => getcwd(), 'usertable' => ['username'=>'system', 'contestnumber'=>0, 'usersitenumber'=>1, 'usernumber'=>1]];
$input = json_decode(stream_get_contents(STDIN), true);
$action = $argv[1] ?? '';
$cfg = $input['config'] ?? [];
$credentials = $input['credentials'] ?? [];
function fail($message) { echo json_encode(['ok'=>false, 'errors'=>[$message]], JSON_UNESCAPED_UNICODE); exit(1); }
function result($data) { echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); exit; }
function query($sql, $values=[]) {
    global $db;
    $r = pg_query_params($db, $sql, $values);
    if (!$r) fail('Falha SQL; transacao revertida ao desconectar');
    return $r;
}
function rows($sql, $values=[]) { return pg_fetch_all(query($sql, $values)) ?: []; }
function scalar($sql, $values=[]) { return pg_fetch_result(query($sql, $values), 0, 0); }
$contest = $cfg['contest_id'] ?? 0;
$site = $cfg['site_id'] ?? 0;
if (!is_int($contest) || $contest < 1 || !is_int($site) || $site < 1) fail('Contest/site obrigatorios');
if (($cfg['profile'] ?? '') !== getenv('SEMINFO_PROFILE')) fail('Perfil da configuracao diverge da mesa');
$db = DBConnect();
if ($action === 'prepare') {
    if (scalar('SELECT count(*) FROM runtable WHERE contestnumber=$1', [$contest]) > 0) fail('Prepare recusado: contest ja recebeu submissoes');
    if (scalar("SELECT count(*) FROM contesttable WHERE contestnumber=$1 AND contestactive='t'", [$contest]) > 0) fail('Prepare recusado: contest ativo');
    query('BEGIN');
    $start = strtotime($cfg['start']);
    $duration = ($cfg['timing_confirmed'] && is_int($cfg['duration_minutes'])) ? $cfg['duration_minutes'] * 60 : 0;
    $answer = $duration ? $duration - $cfg['stop_answering_minutes_before_end'] * 60 : 0;
    $freeze = $duration ? $duration - $cfg['freeze_minutes_before_end'] * 60 : 0;
    if (!scalar('SELECT count(*) FROM contesttable WHERE contestnumber=$1', [$contest])) {
        $next = (int)scalar('SELECT coalesce(max(contestnumber),0)+1 FROM contesttable');
        if ($next !== $contest) fail('Escolha contest_id igual ao proximo numero disponivel: ' . $next);
        $number = DBNewContest(['name'=>$cfg['name'], 'startdate'=>$start, 'duration'=>1,
            'lastmileanswer'=>1, 'lastmilescore'=>1, 'penalty'=>$cfg['penalty_minutes']*60,
            'number'=>$site, 'localsite'=>$site, 'mainsite'=>$site], $db);
        if ($number !== $contest) fail('Falha ao criar contest');
    }
    if (!scalar('SELECT count(*) FROM sitetable WHERE contestnumber=$1 AND sitenumber=$2', [$contest,$site])) fail('Site nao existe');
    query('UPDATE contesttable SET contestname=$2,conteststartdate=$3,contestduration=$4,contestlastmileanswer=$5,contestlastmilescore=$6,contestpenalty=$7,contestactive=false,contestlocalsite=$8,contestmainsite=$8,updatetime=extract(epoch from now()) WHERE contestnumber=$1',
        [$contest,$cfg['name'],$start,$duration,$answer,$freeze,$cfg['penalty_minutes']*60,$site]);
    query('UPDATE sitetable SET sitename=$3,siteip=$4,siteduration=$5,sitelastmileanswer=$6,sitelastmilescore=$7,siteautoend=true,siteactive=true,sitejudging=$2,sitetasking=$2,siteautojudge=true,sitescorelevel=3,updatetime=extract(epoch from now()) WHERE contestnumber=$1 AND sitenumber=$2',
        [$contest,$site,'Lab '.$cfg['lab'].' '.strtoupper($cfg['profile']),($cfg['server_ip'] ?: '127.0.0.1').':8000/boca',$duration,$answer,$freeze]);
    query('DELETE FROM sitetimetable WHERE contestnumber=$1 AND sitenumber=$2', [$contest,$site]);
    query('INSERT INTO sitetimetable(contestnumber,sitenumber,sitestartdate,siteenddate) VALUES($1,$2,$3,0)', [$contest,$site,$start]);
    query('DELETE FROM langtable WHERE contestnumber=$1 AND langnumber NOT IN (1,2,3,5)', [$contest]);
    foreach ([[1,'C','c'],[2,'C++20','cpp'],[3,'Java','java'],[5,'Python3','py3']] as $lang) {
        query('INSERT INTO langtable(contestnumber,langnumber,langname,langextension) VALUES($1,$2,$3,$4) ON CONFLICT(contestnumber,langnumber) DO UPDATE SET langname=excluded.langname,langextension=excluded.langextension', [$contest,...$lang]);
    }
    // Parameterized SQL also avoids printing plaintext credentials in BOCA logs.
    $users = [[1000,'admin','Administrador','admin',$credentials['BOCA_PASSWORD']], [1500,'placar','Placar do projetor','score',$credentials['SCORE_PASSWORD']]];
    for ($i=1; $i<=$cfg['teams']; $i++) $users[] = [2000+$i, $cfg['profile'].sprintf('%02d',$i), 'Equipe '.sprintf('%02d',$i), 'team', $credentials[sprintf('TEAM_%02d',$i)]];
    foreach ($users as [$number,$username,$fullname,$type,$password]) {
        $hash = ($type === 'admin' ? '' : '!') . hash('sha256', $password);
        query("INSERT INTO usertable(contestnumber,usersitenumber,usernumber,username,userfullname,userdesc,usertype,userenabled,usermultilogin,userpassword,usersession,usersessionextra,usericpcid,userinfo) VALUES($1,$2,$3,$4,$5,'[Nucleo de Informatica]',$6,true,false,$7,'','','','') ON CONFLICT(contestnumber,usersitenumber,usernumber) DO UPDATE SET username=excluded.username,userfullname=excluded.userfullname,userdesc=excluded.userdesc,usertype=excluded.usertype,userenabled=true,userpassword=excluded.userpassword,usersession='',usersessionextra=''", [$contest,$site,$number,$username,$fullname,$type,$hash]);
    }
    query("UPDATE usertable SET username='system',userpassword=$1,usersession='',usersessionextra='' WHERE contestnumber=0 AND usertype='system'", [hash('sha256',$credentials['SYSTEM_PASSWORD'])]);
    query('COMMIT');
    result(['ok'=>true,'contest'=>$contest,'active'=>false,'teams'=>$cfg['teams']]);
}
$ct = rows('SELECT * FROM contesttable WHERE contestnumber=$1', [$contest])[0] ?? null;
if (!$ct) fail('Contest ainda nao preparado');
if ($action === 'problem-package') {
    $number=$input['number'] ?? null;
    if (!is_int($number) || $number<1 || $number>9999) fail('Questao invalida');
    $p=rows("SELECT problemnumber AS number,problemname AS name,problemfullname AS title,
        problembasefilename AS basename,probleminputfilehash AS package_hash,
        problemautojudge AS autojudge,updatetime AS updated_at,probleminputfile AS oid
        FROM problemtable WHERE contestnumber=$1 AND problemnumber=$2 AND fake=false AND problemfullname !~ '(DEL)'",[$contest,$number])[0] ?? null;
    if (!$p || !$p['oid']) fail('Questao sem pacote');
    $raw=scalar('SELECT encode(lo_get($1),\'base64\')',[(int)$p['oid']]);
    if (strlen($raw)>90*1024*1024) fail('O pacote excede 64 MiB para o ensaio local');
    unset($p['oid']);
    foreach (['number','autojudge','updated_at'] as $key) $p[$key]=(int)$p[$key];
    result(['ok'=>true,'revision_data'=>$p,'package'=>str_replace(["\r","\n"],'',$raw)]);
}
if (in_array($action,['reference-seed','reference-results'])) {
    if (getenv('SEMINFO_REFERENCE') !== '1') fail('Acao exclusiva do BOCA descartavel de ensaio');
    $_SESSION['usertable']=['username'=>'admin','contestnumber'=>$contest,'usersitenumber'=>$site,'usernumber'=>1000,'usertype'=>'admin'];
    if ($action==='reference-results') {
        $runs=rows("SELECT r.runstatus AS status,a.shortname AS verdict,a.yes AS accepted,
            CASE WHEN r.autostderr IS NOT NULL THEN encode(substring(lo_get(r.autostderr) from 1 for 4000),'base64') ELSE '' END AS detail
            FROM runtable r JOIN answertable a ON a.contestnumber=r.contestnumber AND a.answernumber=r.runanswer WHERE r.contestnumber=$1 ORDER BY r.runnumber",[$contest]);
        foreach ($runs as &$run) $run['accepted']=$run['accepted']==='t';
        result($runs);
    }
    $p=$input['problem'] ?? [];
    $number=$p['number'] ?? 0;
    if (!is_int($number) || $number<1 || $number>9999) fail('Questao invalida');
    $package=base64_decode($input['package'] ?? '',true);
    if ($package===false || strlen($package)>64*1024*1024) fail('Pacote invalido');
    $path=tempnam('/tmp','reference-'); file_put_contents($path,$package);
    $saved=DBNewProblem($contest,['number'=>$number,'name'=>$p['name'],'fullname'=>$p['title'],
        'basename'=>$p['basename'],'inputfilename'=>'reference.zip','inputfilepath'=>$path,'autojudge'=>1023]);
    unlink($path);
    if (!$saved) fail('Falha ao importar pacote no ensaio');
    $sources=$input['sources'] ?? [];
    if (!is_array($sources) || count($sources)<1 || count($sources)>4) fail('Referencias invalidas');
    foreach ($sources as $i=>$source) {
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,79}\.(c|cpp|java|py3)$/D',$source['name'] ?? '') ||
            !in_array($source['lang'] ?? null,[1,2,3,5],true)) fail('Arquivo de referencia invalido');
        $raw=base64_decode($source['content'] ?? '',true);
        if ($raw===false || strlen($raw)>131072) fail('Referencia grande demais');
        $path='/tmp/'.$source['name']; file_put_contents($path,$raw);
        $saved=DBNewRun(['contest'=>$contest,'site'=>$site,'user'=>2001,'problem'=>$number,
            'lang'=>$source['lang'],'filename'=>$source['name'],'filepath'=>$path,'runnumber'=>$i+1]);
        unlink($path);
        if (!$saved) fail('Falha ao enviar referencia ao juiz de ensaio');
    }
    result(['ok'=>true]);
}
if ($action === 'verify' || $action === 'activate') {
    $errors = [];
    $teams = (int)scalar("SELECT count(*) FROM usertable WHERE contestnumber=$1 AND usersitenumber=$2 AND usertype='team' AND userenabled", [$contest,$site]);
    if ($teams !== $cfg['teams']) $errors[] = 'Quantidade de equipes diverge';
    foreach (['admin','score'] as $type) if (!scalar('SELECT count(*) FROM usertable WHERE contestnumber=$1 AND usersitenumber=$2 AND usertype=$3 AND userenabled', [$contest,$site,$type])) $errors[]='Usuario '.$type.' ausente';
    $languages = array_column(rows('SELECT langname FROM langtable WHERE contestnumber=$1 ORDER BY langnumber', [$contest]), 'langname');
    if ($languages !== ['C','C++20','Java','Python3']) $errors[]='Linguagens divergem';
    $problems = (int)scalar("SELECT count(*) FROM problemtable WHERE contestnumber=$1 AND problemnumber>0 AND problemfullname !~ '(DEL)'", [$contest]);
    if ($action === 'activate') {
        if (!$cfg['timing_confirmed'] || !is_int($cfg['duration_minutes']) || $cfg['duration_minutes'] < 1 || !is_int($cfg['freeze_minutes_before_end']) || $cfg['freeze_minutes_before_end'] < 0 || $cfg['freeze_minutes_before_end'] >= $cfg['duration_minutes']) $errors[]='Tempos nao confirmados';
        if (!$problems) $errors[]='Nenhum problema importado';
        if ($errors) result(['ok'=>false,'errors'=>$errors]);
        $duration=$cfg['duration_minutes']*60;
        $freeze=$duration-$cfg['freeze_minutes_before_end']*60;
        $answer=$duration-$cfg['stop_answering_minutes_before_end']*60;
        if ($ct['contestactive'] === 't') result(['ok'=>true,'already_active'=>true]);
        query('BEGIN');
        query('UPDATE contesttable SET contestactive=false');
        query('UPDATE contesttable SET contestactive=true,conteststartdate=$2,contestduration=$3,contestlastmilescore=$4,contestlastmileanswer=$5,contestpenalty=$6 WHERE contestnumber=$1', [$contest,strtotime($cfg['start']),$duration,$freeze,$answer,$cfg['penalty_minutes']*60]);
        query('UPDATE sitetable SET siteduration=$3,sitelastmilescore=$4,sitelastmileanswer=$5,siteautoend=true,siteautojudge=true WHERE contestnumber=$1 AND sitenumber=$2', [$contest,$site,$duration,$freeze,$answer]);
        query('DELETE FROM sitetimetable WHERE contestnumber=$1 AND sitenumber=$2', [$contest,$site]);
        query('INSERT INTO sitetimetable(contestnumber,sitenumber,sitestartdate,siteenddate) VALUES($1,$2,$3,0)', [$contest,$site,strtotime($cfg['start'])]);
        query('COMMIT');
    }
    result(['ok'=>!$errors,'errors'=>$errors,'teams'=>$teams,'problems'=>$problems,'languages'=>$languages]);
}
if ($action === 'scoreboard') {
    // Public/frozen view only. This endpoint must never call the full export.
    $_SESSION['usertable']=['username'=>'placar','contestnumber'=>$contest,'usersitenumber'=>$site,'usernumber'=>1500,'usertype'=>'score'];
    query('BEGIN ISOLATION LEVEL REPEATABLE READ READ ONLY');
    $st=DBSiteInfo($contest,$site,$db);
    $final=($input['public_final'] ?? false) === true && (int)$st['currenttime'] >= (int)$st['siteduration'] &&
        (int)$st['siteduration']>0 && (int)scalar("SELECT count(*) FROM runtable WHERE contestnumber=$1 AND runsitenumber=$2 AND runstatus NOT IN ('judged','deleted')",[$contest,$site])===0;
    [$score,$unused]=DBScoreSite($contest,$site,!$final);
    $public=[];
    foreach ($score as $entry) $public[]=['user'=>(int)$entry['user'],'site'=>(int)$entry['site'],
        'team'=>$entry['username'],'name'=>$entry['userfullname'],'solved'=>(int)$entry['totalcount'],
        'penalty'=>(int)$entry['totaltime'],'last_accept'=>(int)($entry['first'] ?? 0),'problems'=>$entry['problem']];
    $problems=rows("SELECT problemname AS name, probleminputfilehash AS hash FROM problemtable WHERE contestnumber=$1 AND problemnumber>0 AND problemfullname !~ '(DEL)' ORDER BY problemname",[$contest]);
    $st=DBSiteInfo($contest,$site,$db);
    query('COMMIT');
    $ready=count($problems)>0 && (int)$ct['contestduration']>0;
    foreach ($problems as $p) if (!$p['hash']) $ready=false;
    result(['ok'=>true,'profile'=>$cfg['profile'],'lab'=>$cfg['lab'],'name'=>$ct['contestname'],
        'contest_id'=>$contest,'site_id'=>$site,'generated_at'=>time(),
        'common'=>['start'=>(int)$ct['conteststartdate'],'duration'=>(int)$ct['contestduration'],
            'freeze'=>(int)$st['sitelastmilescore'],'penalty'=>(int)$ct['contestpenalty'],
            'problems_sha256'=>hash('sha256',json_encode($problems))],
        'common_ready'=>$ready,'public_final'=>$final,'frozen'=>!$final && (int)$st['currenttime']>=(int)$st['sitelastmilescore'],
        'elapsed_seconds'=>(int)$st['currenttime'],'teams'=>$public]);
}
if ($action === 'export') {
    $_SESSION['usertable']=['username'=>'admin','contestnumber'=>$contest,'usersitenumber'=>$site,'usernumber'=>1000];
    query('BEGIN ISOLATION LEVEL REPEATABLE READ READ ONLY');
    $teams=rows("SELECT username AS id,userfullname AS name,usernumber AS number,usersitenumber AS site FROM usertable WHERE contestnumber=$1 AND usersitenumber=$2 AND usertype='team' AND userenabled AND userlastlogin IS NOT NULL ORDER BY usernumber", [$contest,$site]);
    $runs=rows("SELECT r.runnumber AS id,r.rundatediff AS seconds,r.rundatediffans AS judged_seconds,u.username AS team,p.problemname AS problem,r.runanswer AS answer,a.yes,r.runstatus AS status FROM runtable r JOIN usertable u ON u.contestnumber=r.contestnumber AND u.usersitenumber=r.runsitenumber AND u.usernumber=r.usernumber JOIN problemtable p ON p.contestnumber=r.contestnumber AND p.problemnumber=r.runproblem JOIN answertable a ON a.contestnumber=r.contestnumber AND a.answernumber=r.runanswer WHERE r.contestnumber=$1 AND r.runsitenumber=$2 AND u.usertype='team' AND u.userenabled AND u.userlastlogin IS NOT NULL AND r.runstatus !~ 'deleted' ORDER BY r.rundatediff,r.runnumber", [$contest,$site]);
    $problems=rows("SELECT problemname AS name FROM problemtable WHERE contestnumber=$1 AND problemnumber>0 AND problemfullname !~ '(DEL)' ORDER BY problemnumber",[$contest]);
    [$score,$unused]=DBScoreSite($contest,$site,false);
    $names=[]; foreach ($teams as $team) $names[(int)$team['number']]=$team['id'];
    $official=[];
    foreach ($score as $entry) if (isset($names[(int)$entry['user']])) $official[]=['team'=>$names[(int)$entry['user']], 'solved'=>(int)$entry['totalcount'], 'penalty'=>(int)$entry['totaltime']];
    $st=DBSiteInfo($contest,$site,$db);
    $pending=(int)scalar("SELECT count(*) FROM runtable WHERE contestnumber=$1 AND runsitenumber=$2 AND runstatus NOT IN ('judged','deleted')",[$contest,$site]);
    query('COMMIT');
    result(['ok'=>true,'contest_id'=>$contest,'site_id'=>$site,'name'=>$ct['contestname'],'teams'=>$teams,'problems'=>$problems,'runs'=>$runs,'official'=>$official,'pending_runs'=>$pending,'elapsed_seconds'=>(int)$st['currenttime'],'duration_seconds'=>(int)$ct['contestduration']]);
}
fail('Acao desconhecida');
