$ErrorActionPreference = 'Stop'
Push-Location (Join-Path $PSScriptRoot '..')
try {
    . (Join-Path $PSScriptRoot 'enable-cms-php.ps1')
    $env:COMPOSER_CACHE_DIR = Join-Path (Get-Location) 'storage/framework/cache/composer'
    $cmsCommands = & php artisan list --raw
    if ($LASTEXITCODE -ne 0) { throw 'Laravel no pudo cargar su configuracion.' }
    if (-not ($cmsCommands -match '^filament:assets\s')) {
        Write-Host 'Instalando Filament. Se requiere acceso a Packagist y GitHub.'
        & (Join-Path $PSScriptRoot 'install-admin.ps1')
    }
    php artisan optimize:clear
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo preparar Laravel.' }
    php artisan migrate --no-interaction
    if ($LASTEXITCODE -ne 0) { throw 'Comprueba la conexion MySQL en backend/.env. No se eliminaron datos.' }
    php artisan clinic:initialize-cms
    if ($LASTEXITCODE -ne 0) { throw 'No se pudieron preparar los borradores del CMS.' }
    php artisan clinic:create-admin --if-missing
    if ($LASTEXITCODE -ne 0) { throw 'No se creo un administrador. Revisa los datos introducidos.' }
    $cmsRoutesText = & php artisan route:list --path=admin --json
    if ($LASTEXITCODE -ne 0) { throw 'No se pudieron comprobar las rutas del panel.' }
    $cmsRoutes = $cmsRoutesText | ConvertFrom-Json
    if (-not ($cmsRoutes | Where-Object { $_.uri -eq 'admin/login' })) {
        throw 'Filament no registro la ruta de acceso. El panel aun no esta operativo.'
    }
    Write-Host ''
    Write-Host 'Panel: http://127.0.0.1:8000/admin'
    Write-Host 'Conserva esta terminal abierta mientras utilizas el administrador.'
    php artisan serve --host=127.0.0.1 --port=8000 --tries=1
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo iniciar Laravel en el puerto 8000.' }
} finally {
    Pop-Location
}
