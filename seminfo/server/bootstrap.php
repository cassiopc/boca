<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit(1); }
chdir('/var/www/boca/src');
require_once 'db.php';
$cfg = globalconf();
function connstr($value) { return "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], $value) . "'"; }
$admin = pg_connect('host=' . connstr($cfg['dbhost']) . ' dbname=postgres user=' . connstr($cfg['dbsuperuser']) . ' password=' . connstr($cfg['dbsuperpass']));
if (!$admin) { fwrite(STDERR, "PostgreSQL indisponivel\n"); exit(1); }
$user = pg_escape_identifier($admin, $cfg['dbuser']);
$database = pg_escape_identifier($admin, $cfg['dbname']);
$exists = pg_query_params($admin, 'SELECT 1 FROM pg_roles WHERE rolname=$1', [$cfg['dbuser']]);
if (pg_num_rows($exists) === 0) {
    if (!pg_query($admin, 'CREATE ROLE ' . $user . ' LOGIN PASSWORD ' . pg_escape_literal($admin, $cfg['dbpass']))) exit(1);
}
$exists = pg_query_params($admin, 'SELECT 1 FROM pg_database WHERE datname=$1', [$cfg['dbname']]);
if (pg_num_rows($exists) === 0 && !pg_query($admin, "CREATE DATABASE $database OWNER $user ENCODING 'UTF8'")) exit(1);
if (!pg_query($admin, "ALTER DATABASE $database SET lo_compat_privileges=on")) exit(1);
$c = DBConnect();
$tables = pg_query($c, "SELECT to_regclass('public.contesttable') AS name");
if (!pg_fetch_result($tables, 0, 'name')) {
    foreach (['Contest','Site','SiteTime','User','Log','Problem','Answer','Task','Lang','Run','Clar','Bkp'] as $table) {
        $fn = 'DBCreate' . $table . 'Table';
        $fn();
    }
    DBFakeContest();
}
echo "SEMINFO: banco inicializado; dados existentes preservados.\n";
