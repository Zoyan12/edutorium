@echo off
REM Edutorium Battle System - Windows Deployment Script
REM This script automates the deployment process on Windows

setlocal enabledelayedexpansion

REM Configuration
set COMPOSE_FILE=docker-compose.yml
set ENV_FILE=.env
set ENV_EXAMPLE=env.example

REM Functions
:log_info
echo [INFO] %~1
goto :eof

:log_success
echo [SUCCESS] %~1
goto :eof

:log_warning
echo [WARNING] %~1
goto :eof

:log_error
echo [ERROR] %~1
goto :eof

REM Check if Docker is installed
:check_docker
call :log_info "Checking Docker installation..."
docker --version >nul 2>&1
if errorlevel 1 (
    call :log_error "Docker is not installed. Please install Docker Desktop first."
    exit /b 1
)

docker-compose --version >nul 2>&1
if errorlevel 1 (
    call :log_error "Docker Compose is not installed. Please install Docker Compose first."
    exit /b 1
)

call :log_success "Docker and Docker Compose are installed"
goto :eof

REM Check if .env file exists
:check_env
call :log_info "Checking environment configuration..."
if not exist "%ENV_FILE%" (
    if exist "%ENV_EXAMPLE%" (
        call :log_warning ".env file not found. Creating from example..."
        copy "%ENV_EXAMPLE%" "%ENV_FILE%" >nul
        call :log_warning "Please update the .env file with your actual configuration values"
        call :log_warning "Especially update SUPABASE_URL and SUPABASE_KEY"
    ) else (
        call :log_error "No environment file found. Please create .env file manually."
        exit /b 1
    )
) else (
    call :log_success "Environment file found"
)
goto :eof

REM Create necessary directories
:create_directories
call :log_info "Creating necessary directories..."
if not exist "logs" mkdir logs
if not exist "uploads" mkdir uploads
if not exist "nginx\ssl" mkdir nginx\ssl
if not exist "monitoring" mkdir monitoring
call :log_success "Directories created"
goto :eof

REM Build and start services
:deploy_services
set profile=%1
call :log_info "Building and starting services with profile: %profile%"

if "%profile%"=="production" (
    docker-compose --profile production build
    docker-compose --profile production up -d
) else if "%profile%"=="monitoring" (
    docker-compose --profile production --profile monitoring build
    docker-compose --profile production --profile monitoring up -d
) else (
    docker-compose build
    docker-compose up -d
)

if errorlevel 1 (
    call :log_error "Failed to start services"
    exit /b 1
)

call :log_success "Services started successfully"
goto :eof

REM Check service health
:check_health
call :log_info "Checking service health..."
timeout /t 10 /nobreak >nul

REM Check if containers are running
docker-compose ps | findstr "Up" >nul
if errorlevel 1 (
    call :log_error "Some services failed to start"
    docker-compose ps
    exit /b 1
) else (
    call :log_success "All services are running"
)

REM Check web server
curl -f http://localhost/ >nul 2>&1
if errorlevel 1 (
    call :log_warning "Web server is not responding on port 80"
) else (
    call :log_success "Web server is responding"
)

REM Check WebSocket server
curl -f http://localhost:8080/ >nul 2>&1
if errorlevel 1 (
    call :log_warning "WebSocket server is not responding on port 8080"
) else (
    call :log_success "WebSocket server is responding"
)
goto :eof

REM Show service URLs
:show_urls
call :log_info "Service URLs:"
echo   Web Application: http://localhost
echo   WebSocket Server: ws://localhost:8080

docker-compose ps | findstr "nginx-proxy" >nul
if not errorlevel 1 (
    echo   HTTPS Application: https://localhost
)

docker-compose ps | findstr "grafana" >nul
if not errorlevel 1 (
    echo   Grafana Dashboard: http://localhost:3000
)

docker-compose ps | findstr "prometheus" >nul
if not errorlevel 1 (
    echo   Prometheus Metrics: http://localhost:9090
)
goto :eof

REM Show logs
:show_logs
call :log_info "Showing recent logs..."
docker-compose logs --tail=50
goto :eof

REM Cleanup function
:cleanup
call :log_info "Cleaning up..."
docker-compose down
call :log_success "Cleanup completed"
goto :eof

REM Main deployment function
:deploy
set profile=%1
if "%profile%"=="" set profile=development

call :log_info "Starting Edutorium Battle System deployment..."
call :log_info "Deployment profile: %profile%"

call :check_docker
if errorlevel 1 exit /b 1

call :check_env
if errorlevel 1 exit /b 1

call :create_directories
call :deploy_services %profile%
if errorlevel 1 exit /b 1

call :check_health
call :show_urls

call :log_success "Deployment completed successfully!"
call :log_info "You can view logs with: docker-compose logs -f"
call :log_info "To stop services: docker-compose down"
goto :eof

REM Script options
if "%1"=="deploy" (
    call :deploy %2
) else if "%1"=="production" (
    call :deploy production
) else if "%1"=="monitoring" (
    call :deploy monitoring
) else if "%1"=="logs" (
    call :show_logs
) else if "%1"=="status" (
    docker-compose ps
) else if "%1"=="stop" (
    call :cleanup
) else if "%1"=="restart" (
    call :cleanup
    call :deploy %2
) else if "%1"=="help" (
    echo Usage: %0 [command] [profile]
    echo.
    echo Commands:
    echo   deploy      Deploy the application (default: development)
    echo   production  Deploy with production profile
    echo   monitoring  Deploy with production and monitoring profiles
    echo   logs        Show application logs
    echo   status      Show service status
    echo   stop        Stop all services
    echo   restart     Restart all services
    echo   help        Show this help message
    echo.
    echo Examples:
    echo   %0                    # Deploy in development mode
    echo   %0 production         # Deploy in production mode
    echo   %0 monitoring         # Deploy with monitoring
    echo   %0 logs              # Show logs
    echo   %0 stop              # Stop services
) else if "%1"=="" (
    call :deploy
) else (
    call :log_error "Unknown command: %1"
    echo Use '%0 help' for usage information
    exit /b 1
)
