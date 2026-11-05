[CmdletBinding(SupportsShouldProcess=$true)]
param(
    [Parameter(Mandatory=$true)][ValidateSet('si','tii')][string]$Profile,
    [switch]$Maintenance
)
$ErrorActionPreference='Stop'
$configPath=Join-Path $PSScriptRoot "local/$Profile/lab.json"
$config=Get-Content -LiteralPath $configPath -Raw | ConvertFrom-Json
if ($config.profile -ne $Profile) { throw 'Perfil diverge da configuracao' }
$backupPath=Join-Path $PSScriptRoot "local/$Profile/firewall-before.json"
$group='SEMINFO-BOCA'
$guard='SEMINFO-BOCA troca protegida'
# Windows Firewall rejects the zero-length IPv6 prefix ::/0.
$allIPv6=@('::/1','8000::/1')
function To-Number([string]$Text) {
    $ip=[System.Net.IPAddress]::Parse($Text)
    if ($ip.AddressFamily -ne [System.Net.Sockets.AddressFamily]::InterNetwork) { throw 'Somente IPv4' }
    $n=[uint64]0
    foreach($b in $ip.GetAddressBytes()) { $n=$n*256+$b }
    return $n
}
function To-Address([uint64]$N) {
    return '{0}.{1}.{2}.{3}' -f ([int]([math]::Floor($N/16777216)%256)),([int]([math]::Floor($N/65536)%256)),([int]([math]::Floor($N/256)%256)),([int]($N%256))
}
if (-not $Maintenance) {
    $validator=Join-Path $PSScriptRoot 'manage.py'
    & python $validator check $Profile --network
    if ($LASTEXITCODE -ne 0) { throw 'Preencha/revise IPs e sub-rede antes de aplicar o firewall' }
    # BOCA is allowed only for live approvals; the native gate independently
    # checks the same leases on every request. Enrollment remains available.
    $allowed=@('127.0.0.1',$config.server_ip)
    $approvedPath=Join-Path $PSScriptRoot "local/$Profile/admission-approved.json"
    if (Test-Path -LiteralPath $approvedPath) {
        $approved=Get-Content -LiteralPath $approvedPath -Raw | ConvertFrom-Json
        foreach($value in $approved) {
            $null=To-Number ([string]$value)
            $allowed += [string]$value
        }
    }
    $outside=@()
    $numbers=@($allowed | ForEach-Object { To-Number $_ } | Sort-Object -Unique)
    $next=[uint64]0
    foreach($n in $numbers) {
        if($n -gt $next) { $outside+=(To-Address $next)+'-'+(To-Address ($n-1)) }
        $next=$n+1
    }
    if($next -le 4294967295) { $outside+=(To-Address $next)+'-255.255.255.255' }
    $cidr=$config.subnet.Split('/')
    $network=To-Number $cidr[0]
    $last=$network+[math]::Pow(2,32-[int]$cidr[1])-1
    foreach($value in $allowed) {
        $n=To-Number $value
        if($value -ne '127.0.0.1' -and ($n -le $network -or $n -ge $last)) { throw 'IP aprovado fora da sub-rede' }
    }
    $enrollmentOutside=@()
    $ranges=@(@([uint64]2130706433,[uint64]2130706433),@([uint64]$network,[uint64]$last)) | Sort-Object { $_[0] }
    foreach($value in @($config.control_ips)) {
        if($null -ne $value) { $n=To-Number $value; $ranges+=,@($n,$n) }
    }
    $ranges=@($ranges | Sort-Object { $_[0] })
    $next=[uint64]0
    foreach($range in $ranges) {
        if($range[0] -gt $next) { $enrollmentOutside+=(To-Address $next)+'-'+(To-Address ($range[0]-1)) }
        $next=[uint64][math]::Max($next,$range[1]+1)
    }
    if($next -le 4294967295) { $enrollmentOutside+=(To-Address $next)+'-255.255.255.255' }
    Write-Host "TCP 8000 permitido: $($allowed -join ', ')"
    Write-Host 'Solicitacoes TLS 8443 somente da sub-rede; BOCA 8000 somente aprovado; backend 18000 fechado na rede.'
}
if (-not $PSCmdlet.ShouldProcess("Mesa $Profile",$(if($Maintenance){'Restaurar regras SEMINFO anteriores'}else{'Aplicar firewall SEMINFO'}))) { return }
$principal=New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if(-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) { throw 'Execute na mesa do laboratorio, em PowerShell elevado' }
if (-not (Test-Path -LiteralPath $backupPath)) {
    $before=@(Get-NetFirewallRule -Group $group -ErrorAction SilentlyContinue | ForEach-Object {
        $rule=$_; $port=$rule | Get-NetFirewallPortFilter; $address=$rule | Get-NetFirewallAddressFilter
        @{DisplayName=$rule.DisplayName;Action=[string]$rule.Action;Direction=[string]$rule.Direction;Enabled=[string]$rule.Enabled;Profile=[string]$rule.Profile;Protocol=$port.Protocol;LocalPort=$port.LocalPort;RemoteAddress=$address.RemoteAddress}
    })
    ConvertTo-Json -InputObject $before -Depth 5 | Set-Content -LiteralPath $backupPath -Encoding UTF8
}
if(-not (Get-NetFirewallRule -DisplayName $guard -ErrorAction SilentlyContinue)) {
    New-NetFirewallRule -DisplayName $guard -Direction Inbound -Action Block -Protocol TCP -LocalPort 8000,8443 -Profile Any | Out-Null
}
try {
    Get-NetFirewallRule -Group $group -ErrorAction SilentlyContinue | Remove-NetFirewallRule
    if ($Maintenance) {
        $before=Get-Content -LiteralPath $backupPath -Raw | ConvertFrom-Json
        foreach($old in $before) {
            New-NetFirewallRule -DisplayName $old.DisplayName -Group $group -Direction $old.Direction -Action $old.Action -Protocol $old.Protocol -LocalPort $old.LocalPort -RemoteAddress $old.RemoteAddress -Profile $old.Profile -Enabled $old.Enabled | Out-Null
        }
    } else {
        New-NetFirewallRule -DisplayName "SEMINFO $Profile allow" -Group $group -Direction Inbound -Action Allow -Protocol TCP -LocalPort 8000 -RemoteAddress $allowed -Profile Any | Out-Null
        New-NetFirewallRule -DisplayName "SEMINFO $Profile block ipv4" -Group $group -Direction Inbound -Action Block -Protocol TCP -LocalPort 8000 -RemoteAddress $outside -Profile Any | Out-Null
        New-NetFirewallRule -DisplayName "SEMINFO $Profile block ipv6" -Group $group -Direction Inbound -Action Block -Protocol TCP -LocalPort 8000 -RemoteAddress $allIPv6 -Profile Any | Out-Null
        $enrollmentAllowed=@('127.0.0.1',$config.subnet)+@($config.control_ips | Where-Object { $null -ne $_ })
        New-NetFirewallRule -DisplayName "SEMINFO $Profile enrollment allow" -Group $group -Direction Inbound -Action Allow -Protocol TCP -LocalPort 8443 -RemoteAddress $enrollmentAllowed -Profile Any | Out-Null
        New-NetFirewallRule -DisplayName "SEMINFO $Profile enrollment block ipv4" -Group $group -Direction Inbound -Action Block -Protocol TCP -LocalPort 8443 -RemoteAddress $enrollmentOutside -Profile Any | Out-Null
        New-NetFirewallRule -DisplayName "SEMINFO $Profile enrollment block ipv6" -Group $group -Direction Inbound -Action Block -Protocol TCP -LocalPort 8443 -RemoteAddress $allIPv6 -Profile Any | Out-Null
        New-NetFirewallRule -DisplayName "SEMINFO $Profile backend block ipv4" -Group $group -Direction Inbound -Action Block -Protocol TCP -LocalPort 18000,17860,17861,17862 -RemoteAddress @('0.0.0.0-127.0.0.0','127.0.0.2-255.255.255.255') -Profile Any | Out-Null
        New-NetFirewallRule -DisplayName "SEMINFO $Profile backend block ipv6" -Group $group -Direction Inbound -Action Block -Protocol TCP -LocalPort 18000,17860,17861,17862 -RemoteAddress $allIPv6 -Profile Any | Out-Null
    }
    Get-NetFirewallRule -DisplayName $guard | Remove-NetFirewallRule
} catch {
    throw "Falha ao configurar: TCP 8000 continua fechado pela regra de protecao. Corrija e execute novamente. $($_.Exception.Message)"
}
