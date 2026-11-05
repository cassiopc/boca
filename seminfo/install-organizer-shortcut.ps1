[CmdletBinding(SupportsShouldProcess=$true)]
param()
$ErrorActionPreference='Stop'
$launcher=Join-Path $PSScriptRoot 'Abrir Central SEMINFO.vbs'
$shell=New-Object -ComObject WScript.Shell
foreach($folder in @([Environment]::GetFolderPath('Desktop'),[Environment]::GetFolderPath('Programs'))) {
    $target=Join-Path $folder 'Central SEMINFO.lnk'
    if($PSCmdlet.ShouldProcess($target,'Criar atalho da central dos organizadores')) {
        $shortcut=$shell.CreateShortcut($target)
        $shortcut.TargetPath=Join-Path $env:SystemRoot 'System32/wscript.exe'
        $shortcut.Arguments='"'+$launcher+'"'
        $shortcut.WorkingDirectory=$PSScriptRoot
        $shortcut.Description='Organizar a maratona, aprovar equipes, projetar placares e fazer backup'
        $shortcut.Save()
    }
    $pythonExe=(Get-Command python -ErrorAction Stop).Source
    $pythonExe=(& $pythonExe -c 'import sys; print(sys.executable)').Trim()
    $pythonHidden=Join-Path (Split-Path $pythonExe -Parent) 'pythonw.exe'
    $prepTarget=Join-Path $folder 'Configurar Provas SEMINFO.lnk'
    if($PSCmdlet.ShouldProcess($prepTarget,'Criar atalho da configuracao local de provas')) {
        if(-not (Test-Path -LiteralPath $pythonHidden)) { throw 'pythonw.exe ausente; confira a instalacao do Python.' }
        $shortcut=$shell.CreateShortcut($prepTarget)
        $shortcut.TargetPath=$pythonHidden
        $shortcut.Arguments='"'+(Join-Path $PSScriptRoot 'organizer.py')+'" --prepare'
        $shortcut.WorkingDirectory=$PSScriptRoot
        $shortcut.Description='Definir horarios, criar e importar questoes e testar referencias da categoria'
        $shortcut.Save()
    }
}
