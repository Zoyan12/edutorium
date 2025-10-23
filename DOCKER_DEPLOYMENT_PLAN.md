# Edutorium Battle System - Docker Deployment Plan

## Project Analysis Summary

The Edutorium Battle System is a real-time educational platform built with:
- **Frontend**: HTML5, CSS3, JavaScript (ES6 modules), TailwindCSS, DaisyUI
- **Backend**: PHP 8.2 with Apache web server
- **Real-time Communication**: WebSocket server using Ratchet PHP library
- **Database**: Supabase (PostgreSQL) for user management and data storage
- **Authentication**: Supabase Auth with JWT tokens
- **Dependencies**: Composer for PHP package management

## Architecture Overview

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │   Backend       │    │   Database      │
│   (HTML/CSS/JS) │◄──►│   (PHP/Apache)  │◄──►│   (Supabase)    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │
         │                       │
         ▼                       ▼
┌─────────────────┐    ┌─────────────────┐
│   WebSocket     │    │   File Storage  │
│   (Port 8080)   │    │   (Avatars)     │
└─────────────────┘    └─────────────────┘
```

## Docker Deployment Strategy

### 1. Multi-Stage Dockerfile

The Dockerfile uses a multi-stage build approach:
- **Stage 1**: Composer stage for dependency installation
- **Stage 2**: Production stage with PHP 8.2-Apache

**Key Features:**
- Optimized for production with `--no-dev` flag
- Security headers and SSL support
- Health checks for container monitoring
- Proper file permissions and ownership

### 2. Docker Compose Services

#### Core Services:
- **edutorium-app**: Main application container
- **nginx-proxy**: Reverse proxy with SSL termination (production profile)
- **redis**: Session storage and caching (production profile)

#### Monitoring Services (optional):
- **prometheus**: Metrics collection
- **grafana**: Visualization dashboard

### 3. Environment Configuration

The system supports environment-based configuration:
- Development: Direct access to services
- Production: SSL termination, monitoring, caching

## Deployment Instructions

### Prerequisites

1. **Docker & Docker Compose**: Version 20.10+ and 2.0+
2. **SSL Certificates**: For production deployment
3. **Supabase Project**: Database and authentication setup

### Quick Start (Development)

```bash
# Clone the repository
git clone <repository-url>
cd edutorium-battle-system

# Copy environment file
cp env.example .env

# Update environment variables
nano .env

# Start the application
docker-compose up -d

# Check logs
docker-compose logs -f edutorium-app
```

### Production Deployment

```bash
# Start with production profile
docker-compose --profile production up -d

# Start with monitoring
docker-compose --profile production --profile monitoring up -d
```

### SSL Setup

1. **Generate SSL certificates**:
```bash
mkdir -p nginx/ssl
# Add your SSL certificates to nginx/ssl/
# - cert.pem (certificate)
# - key.pem (private key)
```

2. **Update nginx configuration** if needed

### Database Setup

The application uses Supabase, so no local database setup is required. Ensure:
1. Supabase project is configured
2. Database tables are created using provided SQL scripts
3. Environment variables are set correctly

## Service Configuration

### Ports
- **80**: Apache web server (HTTP)
- **443**: Nginx SSL termination (HTTPS)
- **8080**: WebSocket server
- **3000**: Grafana dashboard
- **9090**: Prometheus metrics

### Volumes
- `./logs`: Apache logs
- `./uploads`: File uploads
- `redis-data`: Redis persistence
- `prometheus-data`: Metrics storage
- `grafana-data`: Dashboard configuration

### Networks
- `edutorium-network`: Internal communication between services

## Security Considerations

### Container Security
- Non-root user execution
- Minimal attack surface
- Security headers enabled
- SSL/TLS encryption

### Application Security
- CSRF protection
- XSS prevention
- SQL injection protection via Supabase
- Rate limiting on sensitive endpoints

### Network Security
- Internal network isolation
- SSL termination at proxy level
- Firewall rules for exposed ports

## Monitoring & Logging

### Health Checks
- Application health endpoint
- Container health monitoring
- Service dependency checks

### Metrics Collection
- Prometheus for metrics gathering
- Grafana for visualization
- Custom application metrics

### Log Management
- Centralized logging
- Log rotation
- Error tracking

## Performance Optimization

### Caching Strategy
- Redis for session storage
- Static asset caching
- Database query optimization

### Resource Management
- Memory limits per container
- CPU resource allocation
- Connection pooling

### Scaling Considerations
- Horizontal scaling with load balancer
- Database connection pooling
- WebSocket connection management

## Backup & Recovery

### Data Backup
- Database: Supabase automated backups
- File uploads: Volume snapshots
- Configuration: Git repository

### Disaster Recovery
- Container orchestration
- Service redundancy
- Data replication

## Troubleshooting

### Common Issues

1. **WebSocket Connection Failures**
   - Check port 8080 accessibility
   - Verify firewall settings
   - Check container logs

2. **Database Connection Issues**
   - Verify Supabase credentials
   - Check network connectivity
   - Validate environment variables

3. **SSL Certificate Problems**
   - Verify certificate paths
   - Check certificate validity
   - Update nginx configuration

### Debug Commands

```bash
# Check container status
docker-compose ps

# View logs
docker-compose logs -f [service-name]

# Execute commands in container
docker-compose exec edutorium-app bash

# Check network connectivity
docker-compose exec edutorium-app curl -I http://localhost/
```

## Maintenance

### Updates
1. Pull latest code changes
2. Rebuild containers: `docker-compose build`
3. Restart services: `docker-compose up -d`

### Monitoring
- Regular health check reviews
- Performance metrics analysis
- Security vulnerability scans

### Scaling
- Add more application instances
- Implement load balancing
- Database read replicas

## Development Workflow

### Local Development
```bash
# Start development environment
docker-compose up -d

# Make code changes
# Restart specific service
docker-compose restart edutorium-app
```

### Testing
```bash
# Run tests
docker-compose exec edutorium-app php -l battle-server.php

# Check WebSocket connectivity
curl -i -N -H "Connection: Upgrade" -H "Upgrade: websocket" -H "Sec-WebSocket-Key: test" -H "Sec-WebSocket-Version: 13" http://localhost:8080/
```

## Cost Optimization

### Resource Allocation
- Right-size containers based on usage
- Use spot instances for non-critical services
- Implement auto-scaling policies

### Storage Optimization
- Regular cleanup of old logs
- Compress static assets
- Use CDN for static content

This deployment plan provides a comprehensive, production-ready Docker setup for the Edutorium Battle System with proper security, monitoring, and scalability considerations.
