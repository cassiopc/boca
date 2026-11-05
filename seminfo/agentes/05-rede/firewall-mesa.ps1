<#
.SYNOPSIS
Cria, na mesa do professor, a entrada TCP 8000 limitada a -Subnet.

.DESCRIPTION
Script da mesa Windows do bocaCaramel. Alguem roda no lab, em PowerShell
elevado, com a sub-rede real daquele lab. -WhatIf valida o CIDR e imprime
as tres regras TCP 8000, sem chamar New-NetFirewallRule e sem exigir
administrador. Abrir ou copiar este arquivo nao cria regra. Nao rode na
maquina de preparo.

Lab 3 (Sistemas de Informacao, stack boca-si) e Lab 4 (Informatica para a
Internet, stack boca-tii) sao mesas diferentes. Cada mesa recebe o proprio
prefixo. A porta 3001 nao e aberta: o Grafana do compose escuta so em
127.0.0.1:3001 e este script nao cria regra para ela.

Uma regra de bloqueio cobre o IPv4 fora da sub-rede e outra cobre o IPv6 na
porta 8000. Bloqueio vence allow, inclusive um allow largo que o Docker ja
tenha deixado. Rode antes de a equipe sentar: a troca remove a regra anterior
deste script e grava a nova.

.PARAMETER Subnet
CIDR IPv4 obrigatorio da sub-rede deste lab. Exemplo: 192.168.13.0/24.
Nao ha valor padrao. /0 e recusado.

.EXAMPLE
powershell -NoProfile -ExecutionPolicy Bypass -File .\firewall-mesa.ps1 -Subnet 192.168.13.0/24

.EXAMPLE
powershell -NoProfile -ExecutionPolicy Bypass -File .\firewall-mesa.ps1 -Subnet 192.168.13.0/24 -WhatIf
#>
[CmdletBinding(SupportsShouldProcess = $true)]
param(
    [Parameter(Mandatory = $true)]
    [ValidateNotNullOrEmpty()]
    [string]$Subnet
)

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'

$AllowName = 'BOCA SEMINFO 8000 allow'
$Block4Name = 'BOCA SEMINFO 8000 block fora'
$Block6Name = 'BOCA SEMINFO 8000 block ipv6'
$RuleGroup = 'SEMINFO-BOCA'
$BocaPort = 8000

function ConvertTo-UInt32Address {
    param([string]$Text)
    $bytes = [System.Net.IPAddress]::Parse($Text).GetAddressBytes()
    $value = [uint64]0
    foreach ($byte in $bytes) {
        $value = ($value * 256) + [uint64]$byte
    }
    return $value
}

function Format-IPv4 {
    param([uint64]$Value)
    $o1 = [int]([math]::Floor($Value / 16777216) % 256)
    $o2 = [int]([math]::Floor($Value / 65536) % 256)
    $o3 = [int]([math]::Floor($Value / 256) % 256)
    $o4 = [int]($Value % 256)
    return '{0}.{1}.{2}.{3}' -f $o1, $o2, $o3, $o4
}

function Get-LabCidr {
    param([string]$Cidr)

    $text = $Cidr.Trim()
    if ($text -notmatch '^(?<ip>(?:\d{1,3}\.){3}\d{1,3})/(?<prefix>\d{1,2})$') {
        throw "Subnet invalida '$Cidr'. Use CIDR IPv4, por exemplo 192.168.13.0/24."
    }

    $ipText = $Matches.ip
    $prefix = [int]$Matches.prefix
    if ($prefix -lt 1 -or $prefix -gt 32) {
        throw "Prefixo /$prefix recusado. /0 abriria o TCP 8000 para qualquer origem. Informe a sub-rede do lab."
    }

    foreach ($part in $ipText.Split('.')) {
        if ($part -notmatch '^\d+$' -or [int]$part -gt 255) {
            throw "Octeto invalido em '$Cidr'."
        }
        if ($part.Length -gt 1 -and $part.StartsWith('0')) {
            throw "Nao use zero a esquerda em '$Cidr'."
        }
    }

    $parsed = $null
    $parsedOk = [System.Net.IPAddress]::TryParse($ipText, [ref]$parsed)
    if (-not $parsedOk -or $parsed.AddressFamily -ne [System.Net.Sockets.AddressFamily]::InterNetwork) {
        throw "Subnet IPv4 invalida '$Cidr'."
    }

    $ip = ConvertTo-UInt32Address $ipText
    $mask = [uint64]0
    for ($i = 0; $i -lt $prefix; $i++) {
        $mask = ($mask * 2) + 1
    }
    for ($i = 0; $i -lt (32 - $prefix); $i++) {
        $mask = $mask * 2
    }

    $all = [uint64]4294967295
    $network = [uint64]($ip -band $mask)
    $wildcard = [uint64]($mask -bxor $all)
    $broadcast = [uint64]($network -bor $wildcard)

    if ($network -eq 0) {
        throw "Sub-rede que inclui 0.0.0.0 recusada. Use o prefixo da sala."
    }

    $loopback = ConvertTo-UInt32Address '127.0.0.0'
    $loopbackEnd = ConvertTo-UInt32Address '127.255.255.255'
    if ($network -ge $loopback -and $network -le $loopbackEnd) {
        throw "Loopback nao e sub-rede de lab."
    }

    $multicast = ConvertTo-UInt32Address '224.0.0.0'
    if ($network -ge $multicast) {
        throw "Sub-rede multicast ou reservada. Use o prefixo unicast do lab."
    }

    $outside = @()
    if ($network -gt 0) {
        $outside += '0.0.0.0-' + (Format-IPv4 ([uint64]($network - 1)))
    }
    if ($broadcast -lt $all) {
        $outside += (Format-IPv4 ([uint64]($broadcast + 1))) + '-255.255.255.255'
    }
    if ($outside.Count -eq 0) {
        throw "Complemento vazio. A porta 8000 ficaria sem limite."
    }

    foreach ($address in @($network, $broadcast)) {
        foreach ($range in $outside) {
            $ends = $range.Split('-')
            $start = ConvertTo-UInt32Address $ends[0]
            $end = ConvertTo-UInt32Address $ends[1]
            if ($address -ge $start -and $address -le $end) {
                throw "Bug: o bloqueio inclui a propria sub-rede ($range)."
            }
        }
    }

    [pscustomobject]@{
        Normalized = '{0}/{1}' -f (Format-IPv4 $network), $prefix
        Prefix     = $prefix
        Outside    = [string[]]$outside
    }
}

function Write-WidePrefixWarning {
    param($LabCidr)
    if ($LabCidr.Prefix -lt 24) {
        Write-Warning "Prefixo /$($LabCidr.Prefix) e largo para uma sala. Se o Lab 3 e o Lab 4 couberem neste CIDR, a mesa nao separa as equipes: quem separa e o nftables de seminfo/agentes/09-debian/templates/nftables.conf."
    }
}

function Remove-BocaSeminfoRule {
    param([string]$DisplayName)
    $existing = @(Get-NetFirewallRule -DisplayName $DisplayName -ErrorAction SilentlyContinue)
    if ($existing.Count -gt 0) {
        $existing | Remove-NetFirewallRule -Confirm:$false
    }
}

function Assert-BocaPortOnly {
    param([string]$DisplayName)

    $rule = @(Get-NetFirewallRule -DisplayName $DisplayName)
    if ($rule.Count -ne 1) {
        throw "Esperava uma regra '$DisplayName', achei $($rule.Count)."
    }

    $portFilter = @($rule | Get-NetFirewallPortFilter)
    $ports = @($portFilter | ForEach-Object { "$($_.LocalPort)" } | Select-Object -Unique)
    $protocols = @($portFilter | ForEach-Object { "$($_.Protocol)" } | Select-Object -Unique)
    foreach ($protocol in $protocols) {
        if ($protocol -ne 'TCP' -and $protocol -ne '6') {
            throw "A regra '$DisplayName' nao ficou so em TCP (veio $protocol)."
        }
    }
    if ($protocols.Count -lt 1) {
        throw "A regra '$DisplayName' nao ficou em TCP."
    }
    if ($ports -contains '3001') {
        throw "A regra '$DisplayName' saiu com a porta 3001. O Grafana nao pode ser publicado."
    }
    if ($ports.Count -ne 1 -or $ports[0] -ne "$BocaPort") {
        throw "A regra '$DisplayName' nao ficou restrita a TCP $BocaPort (veio $($ports -join ','))."
    }
}

if ($WhatIfPreference) {
    $lab = Get-LabCidr -Cidr $Subnet
    Write-WidePrefixWarning -LabCidr $lab
    Write-Host "WhatIf: nenhuma regra sera criada. New-NetFirewallRule nao sera chamado."
    Write-Host "Allow '$AllowName': TCP $BocaPort de $($lab.Normalized)"
    Write-Host "Block '$Block4Name': TCP $BocaPort de $($lab.Outside -join ',')"
    Write-Host "Block '$Block6Name': TCP $BocaPort de ::/0"
    return
}

$caller = [Security.Principal.WindowsIdentity]::GetCurrent()
$principal = New-Object Security.Principal.WindowsPrincipal($caller)
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw "Rode na mesa do lab, em PowerShell elevado. Abrir este arquivo nao cria regra, e ele nao deve ser executado na maquina de preparo."
}

$lab = Get-LabCidr -Cidr $Subnet
Write-WidePrefixWarning -LabCidr $lab

$description = "SEMINFO 2026. TCP 8000 somente de $($lab.Normalized). Nenhuma outra porta, em especial 3001, entra nesta regra."

try {
    foreach ($name in @($AllowName, $Block4Name, $Block6Name)) {
        Remove-BocaSeminfoRule -DisplayName $name
    }

    New-NetFirewallRule `
        -DisplayName $AllowName `
        -Group $RuleGroup `
        -Description $description `
        -Direction Inbound `
        -Action Allow `
        -Protocol TCP `
        -LocalPort $BocaPort `
        -RemoteAddress $lab.Normalized `
        -Profile Any `
        -EdgeTraversalPolicy Block `
        -Enabled True | Out-Null

    New-NetFirewallRule `
        -DisplayName $Block4Name `
        -Group $RuleGroup `
        -Description $description `
        -Direction Inbound `
        -Action Block `
        -Protocol TCP `
        -LocalPort $BocaPort `
        -RemoteAddress $lab.Outside `
        -Profile Any `
        -Enabled True | Out-Null

    New-NetFirewallRule `
        -DisplayName $Block6Name `
        -Group $RuleGroup `
        -Description "SEMINFO 2026. TCP 8000 sem IPv6. A porta 3001 continua sem regra." `
        -Direction Inbound `
        -Action Block `
        -Protocol TCP `
        -LocalPort $BocaPort `
        -RemoteAddress '::/0' `
        -Profile Any `
        -Enabled True | Out-Null

    Assert-BocaPortOnly -DisplayName $AllowName
    Assert-BocaPortOnly -DisplayName $Block4Name
    Assert-BocaPortOnly -DisplayName $Block6Name

    $allowRemote = @((Get-NetFirewallRule -DisplayName $AllowName | Get-NetFirewallAddressFilter).RemoteAddress | ForEach-Object { "$_" })
    if ($allowRemote -contains 'Any' -or $allowRemote -contains '0.0.0.0/0' -or $allowRemote -contains '*') {
        throw "A permissao de TCP 8000 ficou sem limite de sub-rede."
    }
}
catch {
    foreach ($name in @($AllowName, $Block4Name, $Block6Name)) {
        Remove-BocaSeminfoRule -DisplayName $name
    }
    throw
}

Write-Host "Mesa pronta para o lab. TCP 8000 aceita so $($lab.Normalized)."
Write-Host "Fora dessa sub-rede, e em IPv6, a porta 8000 fica bloqueada."
Write-Host "Nenhuma regra foi criada para a porta 3001."
