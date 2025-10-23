# Separate WebSocket Server Deployment Guide

## 🎯 Why Separate Deployment is Better

### **Advantages:**
- ✅ **Independent Scaling**: Scale WebSocket server based on concurrent users
- ✅ **Better Performance**: Dedicated resources for real-time connections
- ✅ **Easier Debugging**: Isolated logs and monitoring
- ✅ **Flexible Deployment**: Deploy to different servers/regions
- ✅ **Simplified Architecture**: Clean separation of concerns
- ✅ **Better Reliability**: Independent failure recovery

## 🚀 Deployment Options

### Option 1: Coolify (Recommended)
1. **Create new project** in Coolify
2. **Connect repository** with WebSocket server code
3. **Set environment variables**:
   ```
   SUPABASE_URL=https://ratxqmbqzwbvfgsonlrd.supabase.co
   SUPABASE_KEY=your-supabase-key
   WEBSOCKET_PORT=8080
   APP_ENV=production
   ```
4. **Deploy** the WebSocket server

### Option 2: Docker Compose
```bash
cd websocket-server
docker-compose up -d
```

### Option 3: Manual Docker
```bash
cd websocket-server
docker build -t edutorium-websocket .
docker run -d -p 8080:8080 \
  -e SUPABASE_URL=https://ratxqmbqzwbvfgsonlrd.supabase.co \
  -e SUPABASE_KEY=your-supabase-key \
  edutorium-websocket
```

## 🔧 Main App Configuration

### 1. Update Database Setting
In your main Edutorium app's admin panel, update the `websocket_url` setting:

**For Coolify deployment:**
```
wss://your-websocket-domain:8080/
```

**For direct IP deployment:**
```
ws://your-server-ip:8080/
```

### 2. Update Main App Dockerfile
Remove WebSocket server from main app:

```dockerfile
# Remove WebSocket-related code from main Dockerfile
# Keep only Apache web server
FROM php:8.2-apache

# ... rest of web server configuration only
```

## 📊 Monitoring & Testing

### Health Check
```bash
curl http://your-websocket-server:8080/health
```

### Test WebSocket Connection
```javascript
const ws = new WebSocket('ws://your-websocket-server:8080/');
ws.onopen = () => console.log('✅ Connected!');
ws.onerror = (e) => console.log('❌ Error:', e);
```

### Monitor Logs
```bash
# Coolify
docker logs -f <websocket-container>

# Docker Compose
docker-compose logs -f websocket-server
```

## 🔗 Integration Steps

### 1. Deploy WebSocket Server
- Deploy the separate WebSocket server
- Note the server URL/IP

### 2. Update Main App
- Update database `websocket_url` setting
- Remove WebSocket code from main app
- Redeploy main app

### 3. Test Integration
- Test WebSocket connection from main app
- Verify battle functionality works

## 📋 Architecture Comparison

### Before (Combined):
```
┌─────────────────────────┐
│   Main App Container    │
│   ├── Apache (Port 80)  │
│   ├── WebSocket (8080)   │
│   └── Complex startup   │
└─────────────────────────┘
```

### After (Separated):
```
┌─────────────────┐    ┌─────────────────┐
│   Main App      │    │   WebSocket     │
│   (Apache only) │    │   Server        │
│   Port 80/443   │    │   Port 8080     │
└─────────────────┘    └─────────────────┘
```

## 🎯 Benefits Realized

1. **Simplified Main App**: Only handles web requests
2. **Dedicated WebSocket Server**: Optimized for real-time connections
3. **Independent Scaling**: Scale each service based on demand
4. **Better Monitoring**: Separate logs and health checks
5. **Easier Debugging**: Isolated issues and troubleshooting
6. **Flexible Deployment**: Deploy to different servers/regions

## 🚀 Next Steps

1. **Deploy WebSocket Server** using one of the methods above
2. **Update Main App** database setting with WebSocket server URL
3. **Remove WebSocket code** from main app Dockerfile
4. **Test the integration** between both services
5. **Monitor both services** independently

The separate deployment approach will give you much better control, performance, and reliability for your Edutorium Battle System! 🎯
