# WebSocket Log Monitoring Guide

## 🔧 Issue Fixed: WebSocket Logs Not Visible

The problem was that the WebSocket server was running in the background without its output being captured in the main container logs.

## ✅ Solution Implemented

### 1. **Enhanced Startup Script**
Updated the startup script to capture WebSocket server output:
```bash
# Start WebSocket server and capture its output
php battle-server.php 2>&1 | while read line; do
    echo "[WEBSOCKET] $line"
done &
```

### 2. **Log Prefixing**
All WebSocket server output is now prefixed with `[WEBSOCKET]` for easy identification.

## 🔍 How to Monitor WebSocket Logs

### Method 1: Container Logs (Recommended)
```bash
# Monitor all logs
docker logs -f <container-name>

# Filter for WebSocket logs only
docker logs -f <container-name> | grep "\[WEBSOCKET\]"
```

### Method 2: Inside Container
```bash
# Run the monitoring script
docker exec -it <container-name> /var/www/html/monitor-websocket-logs.sh

# Or run the test script
docker exec -it <container-name> /var/www/html/test-websocket-server.sh
```

### Method 3: Real-time Monitoring
```bash
# Monitor logs in real-time
docker exec -it <container-name> tail -f /proc/1/fd/1 | grep "\[WEBSOCKET\]"
```

## 📋 What You Should See Now

### Successful WebSocket Server Startup:
```
[WEBSOCKET] Starting Battle WebSocket Server...
[WEBSOCKET] Current directory: /var/www/html
[WEBSOCKET] Environment configuration:
[WEBSOCKET] - SUPABASE_URL: https://ratxqmbqzwbvfgsonlrd.supabase.co
[WEBSOCKET] - API Key length: OK (208 chars)
[WEBSOCKET] Starting Battle WebSocket Server on port 8080...
[WEBSOCKET] Battle Server started!
[WEBSOCKET] Setting up heartbeat to check for disconnected players every 10 seconds
[WEBSOCKET] Heartbeat timer set up successfully
[WEBSOCKET] Battle WebSocket Server is running on port 8080. Press Ctrl+C to stop.
```

### WebSocket Connection Activity:
```
[WEBSOCKET] New connection: 1
[WEBSOCKET] User authenticated: user123
[WEBSOCKET] User joined battle queue
[WEBSOCKET] Battle started between user123 and user456
[WEBSOCKET] User disconnected: 1
```

## 🚀 Testing WebSocket Connection

### 1. **Deploy Updated Dockerfile**
The enhanced startup script will now capture WebSocket logs.

### 2. **Monitor Logs**
```bash
docker logs -f <container-name> | grep "\[WEBSOCKET\]"
```

### 3. **Test WebSocket Connection**
Visit: `https://edutorium.pegioncloud.com/websocket-test.html`

### 4. **Check for Connection Logs**
You should see logs like:
```
[WEBSOCKET] New connection: 1
[WEBSOCKET] WebSocket upgrade successful
```

## 🔧 Troubleshooting

### If Still No WebSocket Logs:

1. **Check if WebSocket server is running**:
   ```bash
   docker exec <container-name> pgrep -f battle-server.php
   ```

2. **Check port 8080**:
   ```bash
   docker exec <container-name> netstat -tlnp | grep 8080
   ```

3. **Run the test script**:
   ```bash
   docker exec <container-name> /var/www/html/test-websocket-server.sh
   ```

4. **Check for errors**:
   ```bash
   docker logs <container-name> | grep -i error
   ```

## 📊 Log Analysis

### Normal WebSocket Logs:
- ✅ Server startup messages
- ✅ Connection/disconnection events
- ✅ Battle events
- ✅ Heartbeat messages

### Error Indicators:
- ❌ "ERROR: WebSocket server failed to start"
- ❌ "Failed to connect to localhost port 8080"
- ❌ "Permission denied" errors

## 🎯 Expected Behavior After Deployment

1. **Container starts successfully**
2. **Apache starts and shows logs**
3. **WebSocket server starts with `[WEBSOCKET]` prefixed logs**
4. **Both services run and logs are visible**
5. **WebSocket connections work through the proxy**

The enhanced logging will help you debug any remaining WebSocket connection issues. Deploy the updated Dockerfile and you should now see WebSocket server logs in your container logs! 🚀
