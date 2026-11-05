[CmdletBinding()]
param([Parameter(Mandatory=$true)][ValidateSet('si','tii')][string]$Profile)
$ErrorActionPreference='Stop'
$task=Get-ScheduledTask -TaskName "SEMINFO Banca $Profile" -ErrorAction SilentlyContinue
if($task) {
    # A deliberate stop must not be undone by RestartOnFailure supervision.
    Stop-ScheduledTask -TaskName $task.TaskName -ErrorAction Stop
}
