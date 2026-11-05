param(
    [string]$ProjectName = 'boca-docker'
)

$ErrorActionPreference = 'Stop'
$EnvFile = Join-Path $PSScriptRoot 'boca-docker\.env'
if (-not (Test-Path $EnvFile)) {
    throw "Arquivo nao encontrado: $EnvFile"
}

Get-Content -LiteralPath $EnvFile | ForEach-Object {
    $line = $_.Trim().TrimStart([char]0xFEFF)
    if ($line -eq '' -or $line.StartsWith('#')) {
        return
    }
    if ($line.StartsWith('export ')) {
        $line = $line.Substring(7).Trim()
    }
    $eq = $line.IndexOf('=')
    if ($eq -lt 1) {
        return
    }
    $key = $line.Substring(0, $eq).Trim()
    $value = $line.Substring($eq + 1).Trim()
    if ($key -notmatch '^[A-Za-z_][A-Za-z0-9_]*$') {
        throw "Chave invalida no .env: $key"
    }
    if ($value.Length -ge 2 -and $value.StartsWith("'") -and $value.EndsWith("'")) {
        $value = $value.Substring(1, $value.Length - 2)
        if ($value.Contains("'")) {
            throw "$key tem aspas simples no meio. O Compose fecha a citacao ali."
        }
    }
    elseif ($value.Length -ge 2 -and $value.StartsWith('"') -and $value.EndsWith('"')) {
        $value = $value.Substring(1, $value.Length - 2)
        if ($value.Contains('$') -or $value.Contains('\')) {
            throw "$key usa aspas duplas com `$ ou barra. Use aspas simples."
        }
    }
    else {
        $comment = $value.IndexOf(' #')
        if ($comment -ge 0) {
            $value = $value.Substring(0, $comment).TrimEnd()
        }
        if ($value.Contains('$')) {
            throw "$key contem `$ sem aspas simples. O Compose interpola. Use ${key}='valor'."
        }
    }
    Set-Item -Path "Env:$key" -Value $value
}

foreach ($required in @('BOCA_DB_PASSWORD', 'BOCA_CONTEST_USER', 'BOCA_CONTEST_PASSWORD', 'BOCA_ADMIN_USER', 'BOCA_ADMIN_PASSWORD')) {
    if (-not (Get-Item -Path "Env:$required" -ErrorAction SilentlyContinue).Value) {
        throw "Variavel vazia em .env: $required"
    }
}

function ConvertTo-PhpString([string]$Value) {
    return $Value.Replace('\', '\\').Replace("'", "\'")
}

$dbPass = ConvertTo-PhpString $env:BOCA_DB_PASSWORD
$contestUser = ConvertTo-PhpString $env:BOCA_CONTEST_USER
$contestPass = ConvertTo-PhpString $env:BOCA_CONTEST_PASSWORD
$adminUser = ConvertTo-PhpString $env:BOCA_ADMIN_USER
$adminPass = ConvertTo-PhpString $env:BOCA_ADMIN_PASSWORD

$phpScript = @"
<?php
putenv('BOCA_DB_HOST=boca-db');
putenv('BOCA_DB_PASSWORD=$dbPass');
require_once '/var/www/boca/src/db.php';
`$c = DBConnect();
`$contestPass = hash('sha256', '$contestPass');
`$adminPass = hash('sha256', '$adminPass');
DBExec(`$c, "ALTER TABLE usertable ALTER COLUMN username TYPE varchar(50)");
DBExec(`$c, "UPDATE usertable SET username='$contestUser', userfullname='Systems', userpassword='`$contestPass', usersession='', usersessionextra='' WHERE contestnumber=0 AND usersitenumber=1 AND usernumber=1");
DBExec(`$c, "INSERT INTO usertable (contestnumber, usersitenumber, usernumber, username, userfullname, userdesc, usertype, userenabled, usermultilogin, userpassword, userip, userlastlogin, usersession, usersessionextra, userlastlogout, userpermitip, updatetime, usericpcid, userinfo) VALUES (0, 1, 1000, '$adminUser', 'Administrator', NULL, 'admin', 't', 't', '`$adminPass', NULL, NULL, '', '', NULL, NULL, CAST(EXTRACT(EPOCH FROM now()) AS int), '', '') ON CONFLICT (contestnumber, usersitenumber, usernumber) DO UPDATE SET username=EXCLUDED.username, userfullname=EXCLUDED.userfullname, usertype=EXCLUDED.usertype, userenabled=EXCLUDED.userenabled, usermultilogin=EXCLUDED.usermultilogin, userpassword=EXCLUDED.userpassword, usersession='', usersessionextra='', updatetime=CAST(EXTRACT(EPOCH FROM now()) AS int)");
echo "Perfis aplicados com sucesso!\n";
?>
"@

$tempFile = Join-Path $PSScriptRoot 'inject_profiles.php'
$utf8 = New-Object System.Text.UTF8Encoding $false
[System.IO.File]::WriteAllText($tempFile, $phpScript, $utf8)

$container = "$ProjectName-boca-web-1"
$remote = '/tmp/inject-profiles.php'
try {
    & docker cp $tempFile "${container}:${remote}"
    if ($LASTEXITCODE -ne 0) {
        throw "docker cp falhou ($LASTEXITCODE)"
    }
    $response = & docker exec $container php $remote
    if ($LASTEXITCODE -ne 0) {
        throw "php do perfil falhou ($LASTEXITCODE)"
    }
    Write-Host $response
}
finally {
    & docker exec -u root $container rm -f $remote | Out-Null
    Remove-Item $tempFile -Force -ErrorAction SilentlyContinue
}
