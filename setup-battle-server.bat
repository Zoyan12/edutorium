@echo off
echo Checking if PHP is installed...
php -v >nul 2>&1
if %errorlevel% neq 0 (
    echo PHP is not installed or not in the PATH.
    echo Please install PHP from https://windows.php.net/download/
    pause
    exit /b 1
)

echo Checking if Composer is installed...
composer -v >nul 2>&1
if %errorlevel% neq 0 (
    echo Composer is not installed or not in the PATH.
    echo Please install Composer from https://getcomposer.org/download/
    pause
    exit /b 1
)

echo Installing Composer dependencies...
composer install

echo Setup complete! You can now run start-battle-server.bat to start the battle server.
pause 