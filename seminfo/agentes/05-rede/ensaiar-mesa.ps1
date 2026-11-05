<#
.SYNOPSIS
Ensaio da mesa Windows, sem criar regra de firewall.

.DESCRIPTION
Nao pede administrador. Confere se seminfo/agentes/09-debian/templates/nftables.conf
existe e ainda contem o texto IP_DA_MESA. Confere hosts-boca.lab3 e
hosts-boca.lab4: uma linha cada, um IPv4 ou o texto IP_DA_MESA. Nao altera
esses arquivos. Em seguida roda firewall-mesa.ps1 -WhatIf com -Subnet e
repassa o codigo de saida. Se o nftables.conf nao existir, sai com erro.

.PARAMETER Subnet
CIDR IPv4 obrigatorio, sem valor padrao. Repassado a firewall-mesa.ps1.

.EXAMPLE
powershell -NoProfile -ExecutionPolicy Bypass -File .\ensaiar-mesa.ps1 -Subnet 192.168.13.0/24
#>
[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [ValidateNotNullOrEmpty()]
    [string]$Subnet
)

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'

function Test-OneHostFile {
    param([string]$Path)

    if (-not (Test-Path -LiteralPath $Path)) {
        throw "Arquivo nao encontrado: $Path"
    }

    $lines = @(Get-Content -LiteralPath $Path)
    if ($lines.Count -ne 1) {
        throw "O arquivo deve ter uma linha: $Path"
    }

    $line = $lines[0].Trim()
    if ($line -eq 'IP_DA_MESA') {
        return
    }

    $parts = $line.Split('.')
    if ($parts.Count -ne 4) {
        throw "A linha deve ser um IPv4 ou o texto IP_DA_MESA: $Path"
    }
    foreach ($part in $parts) {
        if ($part -notmatch '^\d+$' -or $part.Length -gt 3) {
            throw "A linha deve ser um IPv4 ou o texto IP_DA_MESA: $Path"
        }
        if ($part.Length -gt 1 -and $part.StartsWith('0')) {
            throw "IPv4 invalido em $Path"
        }
        if ([int]$part -gt 255) {
            throw "IPv4 invalido em $Path"
        }
    }
}

$nft = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\09-debian\templates\nftables.conf'))
if (-not (Test-Path -LiteralPath $nft)) {
    [Console]::Error.WriteLine("nftables.conf nao encontrado: $nft")
    exit 1
}

$nftText = Get-Content -LiteralPath $nft -Raw
if ($nftText -notlike '*IP_DA_MESA*') {
    [Console]::Error.WriteLine("nftables.conf nao contem o texto IP_DA_MESA.")
    exit 1
}

Test-OneHostFile -Path (Join-Path $PSScriptRoot 'hosts-boca.lab3')
Test-OneHostFile -Path (Join-Path $PSScriptRoot 'hosts-boca.lab4')

$firewall = Join-Path $PSScriptRoot 'firewall-mesa.ps1'
if (-not (Test-Path -LiteralPath $firewall)) {
    [Console]::Error.WriteLine("firewall-mesa.ps1 nao encontrado: $firewall")
    exit 1
}

Write-Host "nftables.conf contem IP_DA_MESA. hosts-boca.lab3 e hosts-boca.lab4 ok."

$ErrorActionPreference = 'Continue'
& powershell.exe -NoProfile -ExecutionPolicy Bypass -File $firewall -Subnet $Subnet -WhatIf
$code = $LASTEXITCODE
if ($null -eq $code) {
    $code = 1
}
exit $code
