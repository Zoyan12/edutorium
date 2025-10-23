# Coolify Deployment Troubleshooting Guide

## Issue Analysis

The deployment failed due to Apache configuration errors:

1. **Apache Syntax Error**: Invalid configuration in the virtual host
2. **Missing Modules**: The `expires` module was not enabled
3. **Health Check Failure**: Apache couldn't start due to configuration issues

## Root Causes

### 1. Apache Configuration Issues
- The virtual host configuration had syntax errors
- Missing `LoadModule` directives were causing conflicts
- The `expires` module wasn't enabled

### 2. Health Check Timing
- Health checks started too early (5 seconds)
- Apache needed more time to start properly

## Solutions Implemented

### 1. Fixed Dockerfile
- Enabled required Apache modules: `rewrite`, `headers`, `ssl`, `expires`, `deflate`
- Simplified Apache configuration to avoid syntax errors
- Increased health check start period to 30 seconds

### 2. Created Coolify-Optimized Files
- `Dockerfile.coolify`: Simplified version for Coolify deployment
- `docker-compose.coolify.yml`: Minimal compose file for Coolify

## Deployment Instructions for Coolify

### Option 1: Use the Fixed Dockerfile
1. Use the updated `Dockerfile` (already fixed)
2. The health check now waits 30 seconds before starting
3. Apache modules are properly enabled

### Option 2: Use Coolify-Optimized Files
1. Use `Dockerfile.coolify` instead of `Dockerfile`
2. Use `docker-compose.coolify.yml` for compose-based deployment
3. This version has minimal configuration to avoid conflicts

## Environment Variables for Coolify

Set these in your Coolify project settings:

```
SUPABASE_URL=https://ratxqmbqzwbvfgsonlrd.supabase.co
SUPABASE_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InJhdHhxbWJxendidmZnc29ubHJkIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDQyMDI0NDAsImV4cCI6MjA1OTc3ODQ0MH0.HJ9nQbvVvVisvQb6HMVMlmQBVmW7Ie42Z6Afdwn8W2M
APP_ENV=production
```

## Health Check Configuration

The health check now:
- Waits 30 seconds before starting (increased from 5 seconds)
- Checks every 30 seconds
- Has a 10-second timeout
- Retries 3 times before marking as unhealthy

## Common Coolify Issues & Solutions

### 1. Health Check Failures
**Problem**: Container marked as unhealthy
**Solution**: 
- Increase start period in health check
- Ensure Apache is properly configured
- Check container logs for errors

### 2. Port Binding Issues
**Problem**: Port 80 already in use
**Solution**:
- Coolify handles port mapping automatically
- Don't specify ports in docker-compose for Coolify

### 3. Environment Variables
**Problem**: Supabase connection fails
**Solution**:
- Set environment variables in Coolify UI
- Ensure SUPABASE_URL and SUPABASE_KEY are correct

### 4. WebSocket Connection Issues
**Problem**: WebSocket server not accessible
**Solution**:
- Ensure port 8080 is exposed
- Check firewall settings
- Verify WebSocket URL in frontend code

## Debugging Steps

### 1. Check Container Logs
```bash
docker logs <container-name>
```

### 2. Check Apache Status
```bash
docker exec <container-name> apache2ctl status
```

### 3. Test Health Check Manually
```bash
docker exec <container-name> curl -f http://localhost/
```

### 4. Check WebSocket Server
```bash
docker exec <container-name> netstat -tlnp | grep 8080
```

## Recommended Coolify Settings

### Build Settings
- **Dockerfile Path**: `Dockerfile.coolify` (or use the fixed `Dockerfile`)
- **Build Context**: `.` (root directory)
- **Build Args**: None required

### Runtime Settings
- **Port**: 80 (Coolify will handle external mapping)
- **Health Check**: Enabled (uses Dockerfile health check)
- **Restart Policy**: Unless stopped

### Environment Variables
- Set all required environment variables in Coolify UI
- Don't use `.env` file for Coolify deployment

## Alternative Deployment Methods

### 1. Single Container (Recommended)
Use `Dockerfile.coolify` for simplest deployment

### 2. Multi-Container
Use `docker-compose.coolify.yml` if you need additional services

### 3. Custom Configuration
Modify the Dockerfile to match your specific Coolify setup

## Testing the Fix

After deploying with the fixed configuration:

1. **Check Health Status**: Container should be healthy
2. **Test Web Access**: Visit your Coolify domain
3. **Test WebSocket**: Check browser console for WebSocket connections
4. **Monitor Logs**: Watch for any errors in Coolify logs

## Next Steps

1. Deploy using the fixed `Dockerfile` or `Dockerfile.coolify`
2. Set environment variables in Coolify
3. Monitor the deployment logs
4. Test the application functionality
5. Set up monitoring if needed

The main issue was Apache configuration syntax errors and missing modules. The fixes ensure:
- Proper Apache module loading
- Correct virtual host configuration
- Appropriate health check timing
- Simplified configuration for Coolify compatibility
