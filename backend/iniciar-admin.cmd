@echo off
setlocal
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\bootstrap-admin.ps1"
if errorlevel 1 (
    echo.
    echo No se pudo iniciar el administrador. Revisa el error anterior.
    pause
    exit /b 1
)
