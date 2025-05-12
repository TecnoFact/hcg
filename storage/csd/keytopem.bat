@echo off
setlocal

echo ============================================
echo = CONVERTIR .key A .pem PARA FIRMAR CFDI   =
echo ============================================

REM Ruta base del proyecto Laravel
set BASEDIR=%~dp0
set CSDDIR=%BASEDIR%

REM Ruta a openssl.exe (ajusta si lo instalaste en otro lugar)
set OPENSSL=C:\Laragon\bin\openssl\bin\openssl.exe

REM Confirmar que openssl existe
if not exist "%OPENSSL%" (
    echo ❌ ERROR: OpenSSL no encontrado en %OPENSSL%
    pause
    exit /b
)

REM Confirmar que llave.key existe
if not exist "%CSDDIR%\llave.key" (
    echo ❌ ERROR: No se encontró %CSDDIR%\llave.key
    pause
    exit /b
)

REM Solicitar contraseña
set /p PASS=Introduce la contraseña del .key (CSD): 

echo 🔄 Ejecutando conversión...
"%OPENSSL%" pkcs8 -inform DER -in "%CSDDIR%\llave.key" -passin pass:%PASS% -out "%CSDDIR%\llave.pem"

if exist "%CSDDIR%\llave.pem" (
    echo ✅ Conversion completada: llave.pem generada exitosamente.
) else (
    echo ❌ Error: No se pudo generar llave.pem
)

pause

