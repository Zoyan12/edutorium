#!/bin/bash

# WebSocket Debug Script for Edutorium
echo "=== Edutorium WebSocket Debug Script ==="
echo "Timestamp: $(date)"
echo ""

# Check if we're in a container
if [ -f /.dockerenv ]; then
    echo "✅ Running inside Docker container"
else
    echo "⚠️  Not running inside Docker container"
fi
echo ""

# Check Apache status
echo "=== Apache Status ==="
if pgrep -f "apache2" > /dev/null; then
    echo "✅ Apache is running (PID: $(pgrep -f apache2))"
else
    echo "❌ Apache is NOT running"
fi

# Check Apache modules
echo ""
echo "=== Apache Modules ==="
apache2ctl -M 2>/dev/null | grep -E "(proxy|ws)" || echo "❌ Proxy modules not loaded"

# Check WebSocket server
echo ""
echo "=== WebSocket Server Status ==="
if pgrep -f "battle-server.php" > /dev/null; then
    echo "✅ WebSocket server is running (PID: $(pgrep -f battle-server.php))"
else
    echo "❌ WebSocket server is NOT running"
fi

# Check port 8080
echo ""
echo "=== Port 8080 Status ==="
if netstat -tlnp 2>/dev/null | grep ":8080" > /dev/null; then
    echo "✅ Port 8080 is listening:"
    netstat -tlnp 2>/dev/null | grep ":8080"
else
    echo "❌ Port 8080 is NOT listening"
fi

# Check port 80
echo ""
echo "=== Port 80 Status ==="
if netstat -tlnp 2>/dev/null | grep ":80" > /dev/null; then
    echo "✅ Port 80 is listening:"
    netstat -tlnp 2>/dev/null | grep ":80"
else
    echo "❌ Port 80 is NOT listening"
fi

# Test WebSocket server directly
echo ""
echo "=== WebSocket Server Test ==="
if curl -s -I http://localhost:8080/ > /dev/null 2>&1; then
    echo "✅ WebSocket server responds to HTTP requests"
else
    echo "❌ WebSocket server does NOT respond to HTTP requests"
fi

# Test Apache proxy
echo ""
echo "=== Apache Proxy Test ==="
if curl -s -I http://localhost/ws/ > /dev/null 2>&1; then
    echo "✅ Apache proxy endpoint /ws/ is accessible"
else
    echo "❌ Apache proxy endpoint /ws/ is NOT accessible"
fi

# Check Apache error logs
echo ""
echo "=== Recent Apache Errors ==="
if [ -f /var/log/apache2/error.log ]; then
    echo "Last 10 lines of Apache error log:"
    tail -10 /var/log/apache2/error.log
else
    echo "❌ Apache error log not found"
fi

# Check Apache access logs
echo ""
echo "=== Recent Apache Access ==="
if [ -f /var/log/apache2/access.log ]; then
    echo "Last 5 lines of Apache access log:"
    tail -5 /var/log/apache2/access.log
else
    echo "❌ Apache access log not found"
fi

# Test WebSocket upgrade
echo ""
echo "=== WebSocket Upgrade Test ==="
echo "Testing WebSocket upgrade headers..."
curl -s -H "Connection: Upgrade" -H "Upgrade: websocket" -H "Sec-WebSocket-Key: test" -H "Sec-WebSocket-Version: 13" http://localhost/ws/ || echo "❌ WebSocket upgrade failed"

echo ""
echo "=== Debug Complete ==="
echo "If WebSocket is still not working, check:"
echo "1. Database websocket_url setting"
echo "2. Coolify SSL certificate"
echo "3. Browser console for detailed errors"
echo "4. Network connectivity"
