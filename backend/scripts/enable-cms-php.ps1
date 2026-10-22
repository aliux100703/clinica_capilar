$ErrorActionPreference = 'Stop'
# Extra ini files inside this workspace; the XAMPP/system php.ini is never changed.
$cmsIniDirectory = Join-Path $PSScriptRoot '../storage/framework/cms-php'
New-Item -ItemType Directory -Path $cmsIniDirectory -Force | Out-Null
$cmsLoadedModules = @(& php -m)
$cmsExtraExtensions = @('gd', 'intl', 'zip') | Where-Object { $cmsLoadedModules -notcontains $_ }
$cmsIniFile = Join-Path $cmsIniDirectory 'cms.ini'
$cmsResolvedIniDirectory = [IO.Path]::GetFullPath($cmsIniDirectory)
$cmsAlreadyScanning = @($env:PHP_INI_SCAN_DIR -split ';') -contains $cmsResolvedIniDirectory
if (-not $cmsAlreadyScanning) {
    [IO.File]::WriteAllLines($cmsIniFile, @($cmsExtraExtensions | ForEach-Object { 'extension=' + $_ }), (New-Object Text.UTF8Encoding($false)))
}
if ($cmsExtraExtensions.Count -gt 0 -and -not $cmsAlreadyScanning) {
    if ($env:PHP_INI_SCAN_DIR) { $env:PHP_INI_SCAN_DIR += ';' + [IO.Path]::GetFullPath($cmsIniDirectory) }
    else { $env:PHP_INI_SCAN_DIR = [IO.Path]::GetFullPath($cmsIniDirectory) }
}
$cmsVerifiedModules = @(& php -m)
foreach ($cmsExtension in @('gd', 'intl', 'zip')) {
    if ($cmsVerifiedModules -notcontains $cmsExtension) { throw ('PHP extension unavailable: ' + $cmsExtension) }
}
