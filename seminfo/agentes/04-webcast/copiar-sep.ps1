param(
    [Parameter(Mandatory = $true)]
    [ValidateSet('boca-si', 'boca-tii', 'boca-docker')]
    [string]$ProjectName,

    [switch]$Apply
)

$ErrorActionPreference = 'Stop'

$sep = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\..\webcast.sep.example'))
if (-not (Test-Path -LiteralPath $sep -PathType Leaf)) {
    throw "Exemplo nao encontrado: $sep"
}

$container = "$ProjectName-boca-web-1"
$destination = '/var/www/boca/src/private/webcast.sep'

if ($Apply) {
    if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
        throw 'docker nao encontrado no PATH.'
    }

    # LASTEXITCODE logo apos o docker, sem pipe: no PowerShell 5.1 um pipe esconde o codigo.
    & docker cp $sep "${container}:${destination}"
    $dockerExit = $LASTEXITCODE
    if ($dockerExit -ne 0) {
        throw "docker cp falhou (codigo $dockerExit)."
    }

    Write-Host "Copiado para ${container}:${destination}"
}
else {
    Write-Host 'Nao executa docker. Rode o comando abaixo com o container no ar, ou repita com -Apply.'
    Write-Host 'Origem: seminfo/webcast.sep.example (linha allUsers 1/1000/9000).'
    Write-Host ''
    Write-Host ("docker cp `"{0}`" {1}:{2}" -f $sep, $container, $destination)
}
