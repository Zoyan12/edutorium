#!/bin/bash

# Edutorium Battle System - Docker Deployment Script
# This script automates the deployment process

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
COMPOSE_FILE="docker-compose.yml"
ENV_FILE=".env"
ENV_EXAMPLE="env.example"

# Functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if Docker is installed
check_docker() {
    log_info "Checking Docker installation..."
    if ! command -v docker &> /dev/null; then
        log_error "Docker is not installed. Please install Docker first."
        exit 1
    fi
    
    if ! command -v docker-compose &> /dev/null; then
        log_error "Docker Compose is not installed. Please install Docker Compose first."
        exit 1
    fi
    
    log_success "Docker and Docker Compose are installed"
}

# Check if .env file exists
check_env() {
    log_info "Checking environment configuration..."
    if [ ! -f "$ENV_FILE" ]; then
        if [ -f "$ENV_EXAMPLE" ]; then
            log_warning ".env file not found. Creating from example..."
            cp "$ENV_EXAMPLE" "$ENV_FILE"
            log_warning "Please update the .env file with your actual configuration values"
            log_warning "Especially update SUPABASE_URL and SUPABASE_KEY"
        else
            log_error "No environment file found. Please create .env file manually."
            exit 1
        fi
    else
        log_success "Environment file found"
    fi
}

# Create necessary directories
create_directories() {
    log_info "Creating necessary directories..."
    mkdir -p logs
    mkdir -p uploads
    mkdir -p nginx/ssl
    mkdir -p monitoring
    log_success "Directories created"
}

# Build and start services
deploy_services() {
    local profile=$1
    log_info "Building and starting services with profile: $profile"
    
    if [ "$profile" = "production" ]; then
        docker-compose --profile production build
        docker-compose --profile production up -d
    elif [ "$profile" = "monitoring" ]; then
        docker-compose --profile production --profile monitoring build
        docker-compose --profile production --profile monitoring up -d
    else
        docker-compose build
        docker-compose up -d
    fi
    
    log_success "Services started successfully"
}

# Check service health
check_health() {
    log_info "Checking service health..."
    sleep 10
    
    # Check if containers are running
    if docker-compose ps | grep -q "Up"; then
        log_success "All services are running"
    else
        log_error "Some services failed to start"
        docker-compose ps
        exit 1
    fi
    
    # Check web server
    if curl -f http://localhost/ > /dev/null 2>&1; then
        log_success "Web server is responding"
    else
        log_warning "Web server is not responding on port 80"
    fi
    
    # Check WebSocket server
    if curl -f http://localhost:8080/ > /dev/null 2>&1; then
        log_success "WebSocket server is responding"
    else
        log_warning "WebSocket server is not responding on port 8080"
    fi
}

# Show service URLs
show_urls() {
    log_info "Service URLs:"
    echo "  Web Application: http://localhost"
    echo "  WebSocket Server: ws://localhost:8080"
    
    if docker-compose ps | grep -q "nginx-proxy"; then
        echo "  HTTPS Application: https://localhost"
    fi
    
    if docker-compose ps | grep -q "grafana"; then
        echo "  Grafana Dashboard: http://localhost:3000"
    fi
    
    if docker-compose ps | grep -q "prometheus"; then
        echo "  Prometheus Metrics: http://localhost:9090"
    fi
}

# Show logs
show_logs() {
    log_info "Showing recent logs..."
    docker-compose logs --tail=50
}

# Cleanup function
cleanup() {
    log_info "Cleaning up..."
    docker-compose down
    log_success "Cleanup completed"
}

# Main deployment function
deploy() {
    local profile=${1:-development}
    
    log_info "Starting Edutorium Battle System deployment..."
    log_info "Deployment profile: $profile"
    
    check_docker
    check_env
    create_directories
    deploy_services "$profile"
    check_health
    show_urls
    
    log_success "Deployment completed successfully!"
    log_info "You can view logs with: docker-compose logs -f"
    log_info "To stop services: docker-compose down"
}

# Script options
case "${1:-deploy}" in
    "deploy")
        deploy "${2:-development}"
        ;;
    "production")
        deploy "production"
        ;;
    "monitoring")
        deploy "monitoring"
        ;;
    "logs")
        show_logs
        ;;
    "status")
        docker-compose ps
        ;;
    "stop")
        cleanup
        ;;
    "restart")
        cleanup
        deploy "${2:-development}"
        ;;
    "help"|"-h"|"--help")
        echo "Usage: $0 [command] [profile]"
        echo ""
        echo "Commands:"
        echo "  deploy      Deploy the application (default: development)"
        echo "  production  Deploy with production profile"
        echo "  monitoring  Deploy with production and monitoring profiles"
        echo "  logs        Show application logs"
        echo "  status      Show service status"
        echo "  stop        Stop all services"
        echo "  restart     Restart all services"
        echo "  help        Show this help message"
        echo ""
        echo "Examples:"
        echo "  $0                    # Deploy in development mode"
        echo "  $0 production         # Deploy in production mode"
        echo "  $0 monitoring         # Deploy with monitoring"
        echo "  $0 logs              # Show logs"
        echo "  $0 stop              # Stop services"
        ;;
    *)
        log_error "Unknown command: $1"
        echo "Use '$0 help' for usage information"
        exit 1
        ;;
esac
