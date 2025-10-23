# WebSocket Proxy Implementation Guide

## ✅ Changes Implemented

### 1. Updated Dockerfile
- ✅ Added Apache proxy modules: `proxy`, `proxy_wstunnel`, `proxy_http`
- ✅ Added WebSocket proxy configuration to Apache virtual host
- ✅ WebSocket requests to `/ws/` are now proxied to `ws://localhost:8080/`

### 2. WebSocket Proxy Configuration
```apache
# WebSocket proxy
ProxyPreserveHost On
ProxyPass /ws/ ws://localhost:8080/
ProxyPassReverse /ws/ ws://localhost:8080/
```

## 🚀 Deployment Steps

### 1. Update Database Setting
In your admin panel, update the `websocket_url` setting to:
```
wss://edutorium.pegioncloud.com/ws/
```

### 2. Redeploy Application
1. Commit the updated Dockerfile
2. Redeploy in Coolify
3. Wait for deployment to complete

### 3. Test WebSocket Connection
1. Visit: `https://edutorium.pegioncloud.com/websocket-test.html`
2. Click "Test WebSocket Connection"
3. Check if connection is successful

## 🔧 How It Works

### Before (Direct Connection)
```
Browser → wss://edutorium.pegioncloud.com:8080/ → WebSocket Server
❌ Failed: No SSL support on WebSocket server
```

### After (Proxy Connection)
```
Browser → wss://edutorium.pegioncloud.com/ws/ → Apache Proxy → ws://localhost:8080/ → WebSocket Server
✅ Success: Apache handles SSL termination and proxies to WebSocket server
```

## 🎯 Benefits

1. **SSL Termination**: Apache handles HTTPS/WSS, WebSocket server stays on HTTP/WS
2. **No Code Changes**: WebSocket server doesn't need SSL modifications
3. **Coolify Compatible**: Works with Coolify's SSL certificate management
4. **Reliable**: Apache proxy is more stable than direct WebSocket SSL

## 🔍 Troubleshooting

### If WebSocket Test Fails

1. **Check Apache Logs**:
   ```bash
   docker logs <container-name> | grep -i proxy
   ```

2. **Verify Proxy Modules**:
   ```bash
   docker exec <container-name> apache2ctl -M | grep proxy
   ```

3. **Test Direct WebSocket Server**:
   ```bash
   docker exec <container-name> netstat -tlnp | grep 8080
   ```

### Common Issues

1. **"Connection Refused"**: WebSocket server not running
2. **"404 Not Found"**: Proxy configuration not loaded
3. **"SSL Error"**: Apache SSL configuration issue

## 📋 Verification Checklist

- [ ] Dockerfile updated with proxy modules
- [ ] Apache virtual host includes WebSocket proxy
- [ ] Database setting updated to `/ws/` endpoint
- [ ] Application redeployed
- [ ] WebSocket test page accessible
- [ ] WebSocket connection successful
- [ ] Battle functionality working

## 🚀 Next Steps

1. **Deploy the updated Dockerfile**
2. **Update database WebSocket URL**
3. **Test the connection**
4. **Verify battle functionality works**

The WebSocket proxy implementation is now complete and ready for deployment!
