param([Parameter(Mandatory=$true)][string]$Script)
$ErrorActionPreference='Stop'
# WhatIf returns before any firewall API or privilege-dependent mutation.
. $Script -Profile si -WhatIf
function In-Ranges([string]$Address,$Ranges) {
    $number=To-Number $Address
    foreach($item in $Ranges) {
        $parts=$item.Split('-')
        $first=To-Number $parts[0]
        $end=$first
        if($parts.Length -eq 2) { $end=To-Number $parts[1] }
        if($number -ge $first -and $number -le $end) { return $true }
    }
    return $false
}
$expectedAllowed=@('127.0.0.1','192.168.50.2')+@($approved)
foreach($ip in $expectedAllowed) {
    if(In-Ranges $ip $outside) { throw "IP aprovado bloqueado: $ip" }
}
foreach($ip in @('192.168.50.10','192.168.50.12')) {
    if($ip -notin $expectedAllowed -and -not (In-Ranges $ip $outside)) { throw "Equipe sem aprovacao liberada: $ip" }
}
foreach($ip in @('0.0.0.0','1.1.1.1','127.0.0.2','172.16.5.10','192.168.50.11','192.168.50.255','255.255.255.255')) {
    if(-not (In-Ranges $ip $outside)) { throw "BOCA ficou aberto para $ip" }
}
foreach($ip in @('127.0.0.1','192.168.50.0','192.168.50.2','192.168.50.99','192.168.50.255','172.16.5.10')) {
    if(In-Ranges $ip $enrollmentOutside) { throw "Solicitacao/central bloqueada: $ip" }
}
foreach($ip in @('0.0.0.0','1.1.1.1','127.0.0.2','172.16.5.11','192.168.49.255','192.168.51.0','255.255.255.255')) {
    if(-not (In-Ranges $ip $enrollmentOutside)) { throw "TLS aberto fora da rede/central: $ip" }
}
Write-Output 'PASSOU: complemento IPv4 do BOCA e do canal de autorizacao/central'
