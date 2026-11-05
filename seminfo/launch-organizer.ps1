$ErrorActionPreference='Stop'
try {
    $python=(Get-Command python -ErrorAction Stop).Source
    $python=(& $python -c 'import sys; print(sys.executable)').Trim()
    if($LASTEXITCODE -ne 0 -or -not (Test-Path -LiteralPath $python)) { throw 'Python 3.10 ou superior precisa ser instalado pela equipe tecnica.' }
    $pythonHidden=Join-Path (Split-Path $python -Parent) 'pythonw.exe'
    if(-not (Test-Path -LiteralPath $pythonHidden)) { throw 'Python para Windows incompleto. Solicite a instalacao oficial com pythonw.exe.' }
    $organizer=Join-Path $PSScriptRoot 'organizer.py'
    $principal=New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
    $options=@{FilePath=$pythonHidden;ArgumentList=('"'+$organizer+'"');WorkingDirectory=$PSScriptRoot;WindowStyle='Hidden'}
    # Same logged-in organizer as Docker Desktop. Windows owns the consent UI.
    if(-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) { $options.Verb='RunAs' }
    Start-Process @options | Out-Null
} catch {
    $message='Nao foi possivel abrir a Central SEMINFO. Confirme a permissao do Windows e a instalacao do Python. Se persistir, chame o apoio tecnico.'
    $shell=New-Object -ComObject WScript.Shell
    $null=$shell.Popup($message,0,'Central SEMINFO',16)
    exit 1
}
