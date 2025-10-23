#!/bin/bash

# WebSocket Server Test Script
echo "=== WebSocket Server Test ==="
echo "Timestamp: $(date)"
echo ""

# Check if WebSocket server process is running
echo "1. Checking WebSocket server process..."
if pgrep -f "battle-server.php" > /dev/null; then
    echo "✅ WebSocket server process is running"
    echo "   PID: $(pgrep -f battle-server.php)"
else
    echo "❌ WebSocket server process is NOT running"
    echo "   Attempting to start WebSocket server..."
    cd /var/www/html
    php battle-server.php &
    sleep 3
    if pgrep -f "battle-server.php" > /dev/null; then
        echo "✅ WebSocket server started successfully"
    else
        echo "❌ Failed to start WebSocket server"
        exit 1
    fi
fi

# Check if port 8080 is listening
echo ""
echo "2. Checking port 8080..."
if netstat -tlnp 2>/dev/null | grep ":8080" > /dev/null; then
    echo "✅ Port 8080 is listening"
    netstat -tlnp 2>/dev/null | grep ":8080"
else
    echo "❌ Port 8080 is NOT listening"
fi

# Test WebSocket server response
echo ""
echo "3. Testing WebSocket server response..."
if curl -s -I http://localhost:8080/ > /dev/null 2>&1; then
    echo "✅ WebSocket server responds to HTTP requests"
else
    echo "❌ WebSocket server does NOT respond to HTTP requests"
fi

# Test WebSocket upgrade
echo ""
echo "4. Testing WebSocket upgrade..."
echo "Sending WebSocket upgrade request..."
curl -s -H "Connection: Upgrade" \
     -H "Upgrade: websocket" \
     -H "Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==" \
     -H "Sec-WebSocket-Version: 13" \
     -H "Sec-WebSocket-Protocol: chat" \
     http://localhost:8080/ > /dev/null 2>&1

if [ $? -eq 0 ]; then
    echo "✅ WebSocket upgrade request sent successfully"
else
    echo "❌ WebSocket upgrade request failed"
fi

# Check Apache proxy
echo ""
echo "5. Testing Apache proxy..."
if curl -s -I http://localhost/ws/ > /dev/null 2>&1; then
    echo "✅ Apache proxy endpoint /ws/ is accessible"
else
    echo "❌ Apache proxy endpoint /ws/ is NOT accessible"
fi

# Show recent logs
echo ""
echo "6. Recent WebSocket server activity..."
echo "Checking for WebSocket-related log entries..."

# Look for WebSocket logs in various places
if [ -f /var/log/apache2/error.log ]; then
    echo "Apache error log (last 5 lines):"
    tail -5 /var/log/apache2/error.log | grep -i websocket || echo "No WebSocket errors found"
fi

echo ""
echo "=== Test Complete ==="
echo "If all tests pass, WebSocket server should be working correctly."
echo "If tests fail, check the container logs for detailed error messages."
