@echo off
REM ============================================
REM Cloudflare Tunnel Installer for Windows
REM With Pre-configured Token
REM ============================================

echo.
echo ============================================
echo   Cloudflare Tunnel Quick Installer
echo   For Windows with Token
echo ============================================
echo.

REM Check if running as Administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [ERROR] Script ini harus dijalankan sebagai Administrator!
    echo.
    echo Cara menjalankan:
    echo 1. Klik kanan pada file ini
    echo 2. Pilih "Run as administrator"
    echo.
    pause
    exit /b 1
)

echo [OK] Running as Administrator
echo.

REM Your Cloudflare Token
set CLOUDFLARE_TOKEN=eyJhIjoiMDg3NjU1NzRhMGM5MDAxZGJlZTBlYTEwMGJjODk2ZGQiLCJ0IjoiNjU4MGNlMTktMjdkMy00OTE2LWE3OWQtYjNjNTQxNDY5YTUzIiwicyI6IllXVXdPR1UxTWpBdFltSTNNaTAwTkRsaExUZzFNelV0WlRKaE0yWmtObVJsT0RGaSJ9

REM Check if cloudflared.exe exists
if not exist "cloudflared.exe" (
    echo [ERROR] cloudflared.exe tidak ditemukan!
    echo.
    echo Silakan download terlebih dahulu:
    echo https://github.com/cloudflare/cloudflared/releases/latest
    echo Pilih: cloudflared-windows-amd64.exe
    echo Rename menjadi: cloudflared.exe
    echo Letakkan di folder yang sama dengan script ini
    echo.
    pause
    exit /b 1
)

echo [OK] cloudflared.exe found
echo.

REM Get tunnel information
echo ============================================
echo   Tunnel Configuration
echo ============================================
echo.

set /p TUNNEL_ID="Enter your Tunnel ID: "
set /p DOMAIN="Enter your domain (e.g., billing.yourdomain.com): "
set /p PORT="Enter local port (default: 80): "

if "%PORT%"=="" set PORT=80

echo.
echo [INFO] Configuration:
echo   - Tunnel ID: %TUNNEL_ID%
echo   - Domain: %DOMAIN%
echo   - Port: %PORT%
echo.

pause

REM Create cloudflared directory
if not exist "%USERPROFILE%\.cloudflared" mkdir "%USERPROFILE%\.cloudflared"

REM Create credentials file
echo [INFO] Creating credentials file...
(
echo {
echo   "AccountTag": "087655740c9001dbee0ea100bc896dd",
echo   "TunnelSecret": "YWUwOGU1MjAtYmI3Mi00NDlhLTg1MzUtZTJhM2ZkNmRlODFi",
echo   "TunnelID": "%TUNNEL_ID%"
echo }
) > "%USERPROFILE%\.cloudflared\%TUNNEL_ID%.json"

echo [OK] Credentials file created
echo.

REM Create config file
echo [INFO] Creating config file...
(
echo tunnel: %TUNNEL_ID%
echo credentials-file: %USERPROFILE%\.cloudflared\%TUNNEL_ID%.json
echo.
echo ingress:
echo   - hostname: %DOMAIN%
echo     service: http://localhost:%PORT%
echo     originRequest:
echo       noTLSVerify: true
echo   - service: http_status:404
) > "%USERPROFILE%\.cloudflared\config.yml"

echo [OK] Config file created
echo.

REM Uninstall existing service if any
echo [INFO] Checking existing service...
sc query cloudflared >nul 2>&1
if %errorLevel% equ 0 (
    echo [WARN] Service already exists, uninstalling...
    cloudflared.exe service uninstall
    timeout /t 2 >nul
)

REM Install service
echo [INFO] Installing cloudflared service...
cloudflared.exe service install

if %errorLevel% neq 0 (
    echo [ERROR] Failed to install service
    pause
    exit /b 1
)

echo [OK] Service installed
echo.

REM Start service
echo [INFO] Starting cloudflared service...
net start cloudflared

if %errorLevel% neq 0 (
    echo [ERROR] Failed to start service
    echo.
    echo Troubleshooting:
    echo 1. Check config file: %USERPROFILE%\.cloudflared\config.yml
    echo 2. Check credentials: %USERPROFILE%\.cloudflared\%TUNNEL_ID%.json
    echo 3. View logs in Event Viewer
    echo.
    pause
    exit /b 1
)

echo [OK] Service started
echo.

REM Test local connection
echo [INFO] Testing local connection...
curl -s -o nul -w "%%{http_code}" http://localhost:%PORT% | findstr "200 301 302" >nul
if %errorLevel% equ 0 (
    echo [OK] Local website is responding on port %PORT%
) else (
    echo [WARN] Local website may not be running on port %PORT%
)
echo.

REM Show DNS configuration instructions
echo ============================================
echo   DNS Configuration Required
echo ============================================
echo.
echo Please configure DNS in Cloudflare dashboard:
echo.
echo 1. Go to: https://dash.cloudflare.com
echo 2. Select your domain
echo 3. Go to DNS settings
echo 4. Add CNAME record:
echo    - Name: %DOMAIN:~0,-14% (subdomain)
echo    - Target: %TUNNEL_ID%.cfargotunnel.com
echo    - Proxy: Enabled (orange cloud)
echo.
pause

REM Show summary
echo.
echo ============================================
echo   Installation Complete!
echo ============================================
echo.
echo Tunnel Information:
echo   - Tunnel ID: %TUNNEL_ID%
echo   - Domain: %DOMAIN%
echo   - Local Port: %PORT%
echo.
echo Configuration Files:
echo   - Config: %USERPROFILE%\.cloudflared\config.yml
echo   - Credentials: %USERPROFILE%\.cloudflared\%TUNNEL_ID%.json
echo.
echo Service Management:
echo   - Status: sc query cloudflared
echo   - Start: net start cloudflared
echo   - Stop: net stop cloudflared
echo   - Restart: net stop cloudflared ^&^& net start cloudflared
echo.
echo Access your website:
echo   - URL: https://%DOMAIN%
echo.
echo [WARN] DNS propagation may take 5-10 minutes
echo.
echo ============================================
echo.

pause
