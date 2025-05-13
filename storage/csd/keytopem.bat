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
if not exist "%CSDDIR%\CSD_PULSARIX_SA_DE_CV_PUL230626UV4_20240626_212117.key" (
    echo ❌ ERROR: No se encontró %CSDDIR%\llave.key
    pause
    exit /b
)

REM Solicitar contraseña
set /p PASS=Introduce la contraseña del .key (CSD): 

echo 🔄 Ejecutando conversión...
"%OPENSSL%" pkcs8 -inform DER -in "%CSDDIR%\CSD_PULSARIX_SA_DE_CV_PUL230626UV4_20240626_212117.key" -passin pass:%PASS% -out "%CSDDIR%\CSD_PULSARIX_SA_DE_CV_PUL230626UV4_20240626_212117.pem"

if exist "%CSDDIR%\CSD_PULSARIX_SA_DE_CV_PUL230626UV4_20240626_212117.pem" (
    echo ✅ Conversion completada: CSD_PULSARIX_SA_DE_CV_PUL230626UV4_20240626_212117.pem generada exitosamente.
) else (
    echo ❌ Error: No se pudo generar CSD_PULSARIX_SA_DE_CV_PUL230626UV4_20240626_212117.pem
)

pause

