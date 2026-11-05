[CmdletBinding(SupportsShouldProcess=$true)]
param(
    [Parameter(Mandatory=$true)][ValidateSet('si','tii')][string]$Profile,
    [switch]$Uninstall
)
$ErrorActionPreference='Stop'
$principal=New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if(-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) { throw 'Execute em PowerShell elevado na mesa da banca' }
$taskName="SEMINFO Banca $Profile"
$python=(Get-Command python -ErrorAction Stop).Source
$python=(& $python -c 'import sys; print(sys.executable)').Trim()
$pythonHidden=Join-Path (Split-Path $python -Parent) 'pythonw.exe'
if(-not (Test-Path -LiteralPath $pythonHidden)) { $pythonHidden=$python }
$manager=Join-Path $PSScriptRoot 'manage.py'
if($Uninstall) {
    if($PSCmdlet.ShouldProcess($taskName,'Remover inicializacao automatica da banca')) {
        Stop-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
        Unregister-ScheduledTask -TaskName $taskName -Confirm:$false -ErrorAction SilentlyContinue
    }
    return
}
& $python $manager check $Profile --network
if($LASTEXITCODE -ne 0) { throw 'Confirme o IP e a sub-rede da mesa antes de instalar' }
if(-not $PSCmdlet.ShouldProcess($taskName,'Instalar banca, aplicar firewall e iniciar automaticamente no logon')) { return }
& $python $manager stop $Profile
if($LASTEXITCODE -ne 0) { throw 'Nao foi possivel parar a banca anterior; a reinstalacao foi interrompida' }
& $python $manager init $Profile
if($LASTEXITCODE -ne 0) { throw 'Inicializacao falhou' }
Set-Content -LiteralPath (Join-Path $PSScriptRoot "local/$Profile/admission-approved.json") -Value '[]' -Encoding UTF8
& (Join-Path $PSScriptRoot 'firewall-mesa.ps1') -Profile $Profile
$identity=[Security.Principal.WindowsIdentity]::GetCurrent().Name
# Docker Desktop belongs to the logged-in organizer, not the SYSTEM account.
$action=New-ScheduledTaskAction -Execute $pythonHidden -Argument "`"$manager`" desk-service $Profile" -WorkingDirectory (Split-Path $PSScriptRoot -Parent)
$trigger=New-ScheduledTaskTrigger -AtLogOn -User $identity
$settings=New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -RestartCount 10 -RestartInterval (New-TimeSpan -Minutes 1) -ExecutionTimeLimit ([TimeSpan]::Zero)
$account=New-ScheduledTaskPrincipal -UserId $identity -LogonType Interactive -RunLevel Highest
Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -Principal $account -Force | Out-Null
Start-ScheduledTask -TaskName $taskName
& (Join-Path $PSScriptRoot 'install-organizer-shortcut.ps1')
Write-Host 'Banca instalada. Aguarde Docker ficar pronto e abra o atalho Central SEMINFO na area de trabalho.'
