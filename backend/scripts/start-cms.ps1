$ErrorActionPreference = 'Stop'
Push-Location (Join-Path $PSScriptRoot '..')
try {
    . (Join-Path $PSScriptRoot 'enable-cms-php.ps1')
    php artisan serve --host=127.0.0.1 --port=8000
} finally { Pop-Location }
