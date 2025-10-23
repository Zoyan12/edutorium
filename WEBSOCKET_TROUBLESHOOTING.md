# WebSocket Connection Troubleshooting Guide

## 🚨 Current Issue
WebSocket connection to `wss://edutorium.pegioncloud.com/ws/` is failing with a generic error.

## 🔧 Enhanced Dockerfile Changes

I've made several improvements to fix the WebSocket connection:

### 1. **Improved Apache Configuration**
- ✅ Added proper WebSocket upgrade handling
- ✅ Added both `/ws/` and `/ws` endpoints
- ✅ Added detailed logging for debugging
- ✅ Added `ProxyRequests Off` for security

### 2. **Enhanced Startup Script**
- ✅ Better error handling and process monitoring
- ✅ Increased wait times for service startup
- ✅ Process ID tracking and validation
- ✅ Graceful shutdown handling

### 3. **Debug Tools**
- ✅ Created `debug-websocket.sh` script for troubleshooting
- ✅ Enhanced logging in Apache configuration

## 🚀 Deployment Steps

### 1. **Redeploy with Updated Dockerfile**
The updated Dockerfile now includes:
- Better WebSocket proxy configuration
- Enhanced error handling
- Debug logging

### 2. **Update Database Setting**
Ensure your database `websocket_url` is set to:
```
wss://edutorium.pegioncloud.com/ws/
```

### 3. **Test the Connection**
After deployment, test with:
```javascript
const ws = new WebSocket('wss://edutorium.pegioncloud.com/ws/');
ws.onopen = () => console.log('✅ Connected!');
ws.onerror = (e) => console.log('❌ Error:', e);
```

## 🔍 Debugging Steps

### 1. **Check Container Logs**
```bash
docker logs <container-name>
```
Look for:
- "Apache started successfully"
- "WebSocket server started successfully"
- Any error messages

### 2. **Run Debug Script**
```bash
docker exec <container-name> /var/www/html/debug-websocket.sh
```

### 3. **Check Apache Proxy**
```bash
curl -I http://localhost/ws/
```

### 4. **Test WebSocket Upgrade**
```bash
curl -H "Connection: Upgrade" -H "Upgrade: websocket" -H "Sec-WebSocket-Key: test" -H "Sec-WebSocket-Version: 13" http://localhost/ws/
```

## 🎯 Alternative Solutions

### Option 1: Try Different WebSocket URLs
Test these URLs in your database setting:
1. `wss://edutorium.pegioncloud.com/ws/`
2. `wss://edutorium.pegioncloud.com/ws`
3. `wss://edutorium.pegioncloud.com:8080/`

### Option 2: Check Coolify Configuration
1. **Verify SSL Certificate**: Ensure Coolify has a valid SSL certificate
2. **Check Port Mapping**: Ensure port 80 is properly mapped
3. **Review Coolify Logs**: Check for any proxy-related errors

### Option 3: Direct WebSocket Connection
If proxy doesn't work, try direct connection:
1. **Expose port 8080** in Coolify
2. **Update database setting** to: `wss://edutorium.pegioncloud.com:8080/`
3. **Add SSL support** to WebSocket server

## 🔧 Common Issues & Solutions

### Issue 1: "Connection Refused"
**Cause**: WebSocket server not running
**Solution**: Check container logs for WebSocket server startup errors

### Issue 2: "404 Not Found"
**Cause**: Apache proxy not configured
**Solution**: Verify Apache modules are loaded and configuration is correct

### Issue 3: "SSL Error"
**Cause**: Certificate mismatch or proxy SSL issues
**Solution**: Check Coolify SSL configuration

### Issue 4: "Upgrade Required"
**Cause**: WebSocket upgrade headers not handled
**Solution**: Verify `upgrade=websocket` in Apache configuration

## 📋 Verification Checklist

- [ ] Dockerfile updated with enhanced configuration
- [ ] Application redeployed successfully
- [ ] Container logs show both services running
- [ ] Database websocket_url updated
- [ ] Apache proxy accessible at `/ws/`
- [ ] WebSocket server running on port 8080
- [ ] SSL certificate valid in Coolify
- [ ] Browser console shows connection success

## 🚀 Next Steps

1. **Deploy the updated Dockerfile**
2. **Update database WebSocket URL**
3. **Test the connection**
4. **Run debug script if issues persist**
5. **Check Coolify configuration**

The enhanced Dockerfile should resolve the WebSocket connection issues. The key improvements are:
- Proper WebSocket upgrade handling
- Better error detection and logging
- Enhanced startup sequence
- Multiple WebSocket endpoint support

Try the deployment and let me know if you still encounter issues!
