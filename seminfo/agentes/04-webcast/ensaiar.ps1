$ErrorActionPreference = 'Stop'

$sepPath = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\..\webcast.sep.example'))
if (-not (Test-Path -LiteralPath $sepPath -PathType Leaf)) {
    throw "Exemplo nao encontrado: $sepPath"
}

$expected = 'allUsers 1/1000/9000'
$first = $null
foreach ($rawLine in [System.IO.File]::ReadAllLines($sepPath)) {
    if (-not [string]::IsNullOrWhiteSpace($rawLine)) {
        $first = $rawLine.Trim()
        break
    }
}
if ($first -cne $expected) {
    throw "Primeira linha nao vazia deve ser '$expected'."
}

$copiar = Join-Path $PSScriptRoot 'copiar-sep.ps1'
foreach ($project in @('boca-si', 'boca-tii')) {
    & powershell.exe -NoProfile -NonInteractive -ExecutionPolicy Bypass -File $copiar -ProjectName $project
    $copiarExit = $LASTEXITCODE
    if ($null -eq $copiarExit -or $copiarExit -ne 0) {
        if ($null -eq $copiarExit) { $copiarExit = 1 }
        exit $copiarExit
    }
}

& docker info --format "{{.ServerVersion}}"
$dockerExit = $LASTEXITCODE
if ($dockerExit -ne 0) {
    Write-Host 'Docker parado'
}

exit 0
