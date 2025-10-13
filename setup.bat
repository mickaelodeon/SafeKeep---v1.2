@echo off
echo ================================
echo SafeKeep Setup Assistant
echo ================================
echo.

REM Check if running in correct directory
if not exist "composer.json" (
    echo ERROR: Please run this script from the SafeKeep project directory
    echo Expected files: composer.json, .env.example
    pause
    exit /b 1
)

echo Step 1: Checking Composer installation...
composer --version >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo WARNING: Composer not found. Please install Composer first.
    echo Download from: https://getcomposer.org/download/
    pause
    exit /b 1
)
echo ✅ Composer found!

echo.
echo Step 2: Installing PHP dependencies...
composer install
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Failed to install dependencies
    pause
    exit /b 1
)
echo ✅ Dependencies installed!

echo.
echo Step 3: Setting up environment file...
if not exist ".env" (
    copy ".env.example" ".env"
    echo ✅ .env file created from template
) else (
    echo ⚠️ .env file already exists, skipping...
)

echo.
echo Step 4: Generating security secret...
for /f %%i in ('php -r "echo bin2hex(random_bytes(32));"') do set CSRF_SECRET=%%i
echo Generated CSRF_SECRET: %CSRF_SECRET%

REM Update .env file with generated secret
powershell -Command "(gc .env) -replace 'your-random-32-character-secret-here', '%CSRF_SECRET%' | Out-File -encoding ASCII .env"
echo ✅ Security secret updated in .env

echo.
echo Step 5: Setting up uploads directory permissions...
if not exist "uploads" mkdir uploads
echo ✅ Uploads directory ready

echo.
echo ================================
echo Setup Complete! 
echo ================================
echo.
echo Next steps:
echo 1. Make sure XAMPP Apache and MySQL are running
echo 2. Create database 'safekeep_db' in phpMyAdmin
echo 3. Import migrations/001_create_tables.sql
echo 4. Import migrations/002_sample_data.sql (optional)
echo 5. Access: http://localhost/safekeep-v2
echo.
echo Press any key to open setup guide...
pause >nul
start SETUP-GUIDE.md

echo.
echo Would you like to open phpMyAdmin now? (y/n)
set /p choice=
if /i "%choice%"=="y" (
    start http://localhost/phpmyadmin
)

echo.
echo Setup assistant completed!
pause