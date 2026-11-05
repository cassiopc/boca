[CmdletBinding(SupportsShouldProcess = $true)]
param()

$ErrorActionPreference = 'Stop'
$taskRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$taskPython = (Get-Command python -ErrorAction Stop).Source
$taskDockerBin = Join-Path $env:ProgramFiles 'Docker\Docker\resources\bin'
if (Test-Path -LiteralPath (Join-Path $taskDockerBin 'docker.exe')) {
    $env:PATH = $taskDockerBin + ';' + $env:PATH
}
$taskCmdlet = $PSCmdlet
function Invoke-RecoveryPython {
    param([string[]]$Arguments)
    if ($taskCmdlet.ShouldProcess(($Arguments -join ' '), 'Executar etapa da reconstrucao')) {
        & $taskPython @Arguments
        if ($LASTEXITCODE -ne 0) { throw ('Etapa falhou; dados preservados: ' + ($Arguments -join ' ')) }
    }
}

Push-Location -LiteralPath $taskRoot
try {
    if (-not $WhatIfPreference) {
        $taskDocker = Get-Command docker -ErrorAction Stop
        $taskEngine = & $taskDocker.Source info --format '{{.OSType}}' 2>$null
        if ($LASTEXITCODE -ne 0 -or ($taskEngine -join '').Trim() -ne 'linux') {
            throw 'Inicie o Docker Desktop com containers Linux. Se o Windows solicitou reinicio, reinicie antes de continuar.'
        }
    }

    foreach ($taskProfile in @('si', 'tii')) {
        Invoke-RecoveryPython -Arguments @('seminfo/manage.py', 'init', $taskProfile)
    }
    Invoke-RecoveryPython -Arguments @('seminfo/manage.py', 'build-server')
    if ($taskCmdlet.ShouldProcess('seminfo/client-builder:22.04', 'Preparar builder para integracoes de rede e ISOs')) {
        & docker build -t seminfo/client-builder:22.04 -f seminfo/client/builder.Dockerfile seminfo/client
        if ($LASTEXITCODE -ne 0) { throw 'Construcao do builder Linux falhou.' }
    }
    foreach ($taskProfile in @('si', 'tii')) {
        # stage prepara bancos separados sem publicar dois servidores na mesma porta.
        Invoke-RecoveryPython -Arguments @('seminfo/manage.py', 'stage', $taskProfile)
    }
    Invoke-RecoveryPython -Arguments @('-m', 'unittest', 'discover', '-s', 'seminfo/tests', '-v')
    foreach ($taskTest in @('integration.py', 'contest_setup.py', 'network.py', 'admission_network.py')) {
        Invoke-RecoveryPython -Arguments @(('seminfo/tests/' + $taskTest))
    }

    $taskCheckImage = @'
import json,sys,ssl,hashlib
from pathlib import Path
sys.path.insert(0,'seminfo')
import manage
p=sys.argv[1];cfg=manage.config(p);folder=manage.DIST/'clients'/p
image=folder/('ml-seminfo-lab'+str(cfg['lab'])+'-'+p+'.iso')
if not image.exists():
 print('missing');sys.exit(0)
metadata=folder/'image.json'
if not metadata.exists():
 raise SystemExit('ISO sem manifesto: preserve-a em outra pasta antes de reconstruir.')
m=json.loads(metadata.read_text())
pin=hashlib.sha256(ssl.PEM_cert_to_DER_cert((manage.LOCAL/p/'admission-cert.pem').read_text())).hexdigest()
if m.get('sha256')!=manage.digest(image) or m.get('config_sha256')!=manage.config_hash(cfg) or m.get('client_source_sha256')!=manage.client_source_hash() or m.get('admission_fingerprint')!=pin:
 raise SystemExit('ISO existente diverge da receita/configuracao/identidade; preserve-a em outra pasta antes de reconstruir.')
print('verified')
'@
    foreach ($taskProfile in @('si', 'tii')) {
        $taskReady = $false
        if (-not $WhatIfPreference) {
            $taskImageState = & $taskPython -c $taskCheckImage $taskProfile
            if ($LASTEXITCODE -ne 0) { throw 'Imagem existente nao validada; reconstrucao interrompida sem sobrescrever dados.' }
            $taskReady = ($taskImageState -join '').Trim() -eq 'verified'
        }
        if ($taskReady) {
            Write-Output ('ISO ' + $taskProfile + ' ja conferida; preservada.')
        } else {
            Invoke-RecoveryPython -Arguments @('seminfo/manage.py', 'build-client', $taskProfile, '--allow-incomplete')
        }
    }

    if ($taskCmdlet.ShouldProcess('Ambas as ISOs', 'Inspecionar sistemas comprimidos no builder Linux')) {
        $taskReports = Join-Path $PSScriptRoot 'reports'
        New-Item -ItemType Directory -Path $taskReports -Force | Out-Null
        & docker run --rm --mount ('type=bind,source=' + $taskRoot + ',target=/kit,readonly') --mount ('type=bind,source=' + $taskReports + ',target=/reports') seminfo/client-builder:22.04 python3 /kit/seminfo/tests/inspect_admission_images.py
        if ($LASTEXITCODE -ne 0) { throw 'Inspecao das ISOs falhou.' }
    }
    Invoke-RecoveryPython -Arguments @('seminfo/manage.py', 'animeitor')
    Invoke-RecoveryPython -Arguments @('seminfo/manage.py', 'bundle')
    $taskBackup = "import sys;sys.path.insert(0,'seminfo');import manage;folder=manage.backup(sys.argv[1]);manage.check_restore(folder)"
    foreach ($taskProfile in @('si', 'tii')) {
        Invoke-RecoveryPython -Arguments @('-c', $taskBackup, $taskProfile)
    }
    if (-not $WhatIfPreference) {
        Write-Output 'Construcao, integracoes, inspecao e backups concluidos. Contests continuam inativos. Boot virtual e homologacao fisica ainda precisam ser observados e registrados.'
    }
} finally {
    Pop-Location
}
