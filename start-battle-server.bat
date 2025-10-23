@echo off
echo Starting Battle WebSocket Server...

REM Clear any existing environment variables
set SUPABASE_URL=
set SUPABASE_KEY=

REM Load from .env file if it exists (you need to create this file with your URLs)
if exist .env (
    echo Loading environment variables from .env file
    for /F "tokens=*" %%A in (.env) do set %%A
)

REM Set default values if not provided
if "%SUPABASE_URL%"=="" (
    echo SUPABASE_URL not found, using default settings
    set SUPABASE_URL=http://localhost:8000
)

if "%SUPABASE_KEY%"=="" (
    echo SUPABASE_KEY not found, using default settings
    set SUPABASE_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.example-key
)

:restart
echo Running Battle Server with:
echo SUPABASE_URL=%SUPABASE_URL%
echo SUPABASE_KEY=%SUPABASE_KEY:~0,10%...

REM Run the Battle Server in the PHP server
php battle-server.php

REM If we get here, the server crashed or exited
echo Server has stopped. Restarting in 5 seconds...
timeout /t 5
goto restart