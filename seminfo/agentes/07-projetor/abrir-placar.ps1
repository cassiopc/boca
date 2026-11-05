param(
    [Parameter(Mandatory = $true)]
    [string]$BocaUrl
)

$ErrorActionPreference = 'Stop'

$BocaUrl = if ($null -eq $BocaUrl) { '' } else { $BocaUrl.Trim() }
if ($BocaUrl -eq '') {
    throw 'Informe -BocaUrl com a URL do BOCA deste lab, por exemplo http://IP-DA-MESA:8000/boca/'
}

if ($BocaUrl -match '(?i)[?&](password|passwd|senha)=') {
    throw 'BocaUrl nao pode levar senha. O login e manual.'
}

$uri = $BocaUrl -as [Uri]
if (-not $uri -or -not $uri.IsAbsoluteUri) {
    throw 'BocaUrl tem de ser uma URL absoluta, por exemplo http://IP-DA-MESA:8000/boca/'
}
if ($uri.Scheme -notin @('http', 'https')) {
    throw 'BocaUrl tem de usar http ou https.'
}
if (-not [string]::IsNullOrEmpty($uri.UserInfo)) {
    throw 'Nao coloque usuario nem senha na URL. O login e manual.'
}
if ([string]::IsNullOrWhiteSpace($uri.Host)) {
    throw 'BocaUrl sem host. Use o IP da mesa deste lab.'
}

$port = ''
if (-not $uri.IsDefaultPort) {
    $port = ":$($uri.Port)"
}

Write-Host "Abrindo $($uri.Scheme)://$($uri.Host)$port$($uri.AbsolutePath)"
Write-Host 'Navegador padrao. Sem login automatico. Este script nao guarda senha.'

# So abre a URL. Nao autentica e nao grava credencial.
Start-Process $uri.AbsoluteUri
