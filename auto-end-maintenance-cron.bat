@echo off
REM Maintenance Mode Auto-End Cron Job for Windows
REM This script should be run every 5 minutes using Windows Task Scheduler
REM to automatically end maintenance mode when the estimated time has passed

REM Get the directory where this script is located
set SCRIPT_DIR=%~dp0

REM Run the auto-end maintenance script
php "%SCRIPT_DIR%auto-end-maintenance.php"

REM Log the execution (optional)
echo %date% %time%: Auto-end maintenance check completed >> "%SCRIPT_DIR%maintenance-cron.log"

REM Pause to see output (remove this line for production)
pause
