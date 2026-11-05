param(
    [string]$BocaUrl = 'http://localhost:8000/boca'
)

$ErrorActionPreference = 'Stop'

function Get-AnimeitorVerdictXMessage {
    param([string]$RunsText)

    if ($null -eq $RunsText) {
        $RunsText = ''
    }

    $sep = [char]0x1c
    $lines = [regex]::Split($RunsText, '\r\n|\n|\r')
    $hits = New-Object 'System.Collections.Generic.List[int]'
    for ($i = 0; $i -lt $lines.Length; $i++) {
        $line = $lines[$i]
        if ([string]::IsNullOrWhiteSpace($line)) {
            continue
        }
        $fields = $line.Split($sep)
        $verdict = $fields[$fields.Length - 1].Trim()
        if ($verdict -ceq 'X') {
            $hits.Add($i + 1)
        }
    }

    if ($hits.Count -eq 0) {
        return ''
    }
    if ($hits.Count -eq 1) {
        return "AVISO: runs tem veredito X na linha $($hits[0]). O Animeitor aborta nesse X."
    }
    return "AVISO: runs tem veredito X nas linhas $($hits -join ', '). O Animeitor aborta nesse X."
}

if ([string]::IsNullOrWhiteSpace($BocaUrl) -or $BocaUrl -notmatch '^https?://') {
    throw 'BocaUrl deve comecar com http:// ou https://.'
}

$curl = Get-Command curl.exe -ErrorAction SilentlyContinue
if (-not $curl) {
    throw 'curl.exe nao encontrado.'
}

$base = $BocaUrl.TrimEnd('/')
$url = "$base/admin/report/webcast.php?webcastcode=allUsers"

$outDir = Join-Path $PSScriptRoot 'out'
if (Test-Path -LiteralPath $outDir) {
    Remove-Item -LiteralPath $outDir -Recurse -Force
}
New-Item -ItemType Directory -Path $outDir | Out-Null

$zipPath = Join-Path $outDir 'webcast.zip'
& curl.exe -fL -sS --connect-timeout 5 --max-time 120 --output $zipPath $url
if ($LASTEXITCODE -ne 0) {
    throw "Download falhou (curl $LASTEXITCODE): $url"
}

Add-Type -AssemblyName System.IO.Compression.FileSystem

$isZip = $false
$stream = [System.IO.File]::Open($zipPath, [System.IO.FileMode]::Open, [System.IO.FileAccess]::Read, [System.IO.FileShare]::Read)
try {
    if ($stream.Length -ge 4) {
        $magic = New-Object byte[] 2
        [void]$stream.Read($magic, 0, 2)
        $isZip = ($magic[0] -eq 0x50 -and $magic[1] -eq 0x4B)
    }
}
finally {
    $stream.Dispose()
}

if ($isZip) {
    try {
        $probe = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
        $probe.Dispose()
    }
    catch {
        $isZip = $false
    }
}

if (-not $isZip) {
    throw "A resposta nao e um zip. Confira se webcast.sep esta em /var/www/boca/src/private e se o contest 1 existe. Arquivo: $zipPath"
}

# create_zip grava entradas como ./contest. O nome usado e so o arquivo.
$archive = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
try {
    foreach ($entry in $archive.Entries) {
        if ([string]::IsNullOrWhiteSpace($entry.Name)) {
            continue
        }
        if ($entry.Name -match '[\\/]' -or $entry.Name -eq '.' -or $entry.Name -eq '..') {
            throw "Entrada de zip recusada: $($entry.FullName)"
        }
        $dest = Join-Path $outDir $entry.Name
        $entryStream = $entry.Open()
        try {
            $fileStream = [System.IO.File]::Open($dest, [System.IO.FileMode]::Create, [System.IO.FileAccess]::Write)
            try {
                $entryStream.CopyTo($fileStream)
            }
            finally {
                $fileStream.Dispose()
            }
        }
        finally {
            $entryStream.Dispose()
        }
    }
}
finally {
    $archive.Dispose()
}

$missing = @()
foreach ($name in @('contest', 'runs', 'time')) {
    $path = Join-Path $outDir $name
    if (-not (Test-Path -LiteralPath $path -PathType Leaf)) {
        $missing += $name
    }
}

if ($missing.Count -gt 0) {
    throw "Faltam em ${outDir}: $($missing -join ', ')"
}

$runsBytes = [System.IO.File]::ReadAllBytes((Join-Path $outDir 'runs'))
$runsText = [System.Text.Encoding]::GetEncoding(28591).GetString($runsBytes)
$xMessage = Get-AnimeitorVerdictXMessage -RunsText $runsText

Write-Host "Zip valido. contest, runs e time estao em $outDir"
if (-not [string]::IsNullOrEmpty($xMessage)) {
    Write-Host $xMessage
}
Write-Host 'A pasta out e local. Nao versionar.'
