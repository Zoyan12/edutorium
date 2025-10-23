#!/bin/bash

# WebSocket Log Monitor Script
echo "=== Edutorium WebSocket Log Monitor ==="
echo "Press Ctrl+C to stop monitoring"
echo ""

# Function to show WebSocket server status
show_status() {
    echo "=== WebSocket Server Status ==="
    if pgrep -f "battle-server.php" > /dev/null; then
        echo "✅ WebSocket server is running (PID: $(pgrep -f battle-server.php))"
    else
        echo "❌ WebSocket server is NOT running"
    fi
    
    if netstat -tlnp 2>/dev/null | grep ":8080" > /dev/null; then
        echo "✅ Port 8080 is listening"
    else
        echo "❌ Port 8080 is NOT listening"
    fi
    echo ""
}

# Show initial status
show_status

# Monitor container logs for WebSocket messages
echo "=== Monitoring WebSocket Logs ==="
echo "Looking for [WEBSOCKET] prefixed messages..."
echo ""

# If running in Docker, monitor Docker logs
if [ -f /.dockerenv ]; then
    echo "Running inside container - monitoring process logs..."
    # Monitor the WebSocket process directly
    tail -f /proc/1/fd/1 2>/dev/null | grep --line-buffered "\[WEBSOCKET\]" || {
        echo "No WebSocket logs found. Checking if WebSocket server is running..."
        show_status
        echo "Trying to start WebSocket server manually..."
        cd /var/www/html
        php battle-server.php &
        sleep 2
        show_status
    }
else
    echo "Not in container - use 'docker logs -f <container-name>' to monitor logs"
    echo "Or run this script inside the container"
fi
