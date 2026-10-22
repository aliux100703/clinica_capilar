$ErrorActionPreference = 'Stop'
Push-Location (Join-Path $PSScriptRoot '..')
try {
    . (Join-Path $PSScriptRoot 'enable-cms-php.ps1')
    # Use ~ to preserve the constraint when passed through Windows PowerShell.
    composer require 'filament/filament:~5.0' --no-interaction
    if ($LASTEXITCODE -ne 0) { throw 'Composer failed. Resolve network/platform requirements before continuing.' }
    php artisan filament:assets
    if ($LASTEXITCODE -ne 0) { throw 'Filament assets failed.' }
    php artisan optimize:clear
    if ($LASTEXITCODE -ne 0) { throw 'Cache clearing failed.' }
    php artisan route:list --path=admin
    if ($LASTEXITCODE -ne 0) { throw 'Panel verification failed.' }
} finally {
    Pop-Location
}
