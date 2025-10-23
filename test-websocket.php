<?php
// First load config (which includes functions)
require_once 'includes/config.php';

// Then start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Debug: Test database connection and settings
$debugInfo = [];
$debugInfo[] = "Testing database connection and settings...";

// Check if Supabase configuration is missing
if (defined('SUPABASE_URL') && SUPABASE_URL === 'YOUR_ACTUAL_SUPABASE_URL') {
    $debugInfo[] = "⚠️ Supabase configuration is missing. Using local WebSocket URL.";
    $debugInfo[] = "To use a database-stored URL, update includes/config.php with your Supabase credentials.";
    
    // Use a hardcoded URL for local development without Supabase
    $currentUrl = 'ws://' . $_SERVER['HTTP_HOST'] . ':8080';
    $debugInfo[] = "Using development WebSocket URL: " . $currentUrl;
} else {
    // Try to fetch the settings
    $settingsResponse = supabaseRequest(
        "/rest/v1/settings?select=key,value&limit=10",
        'GET',
        null,
        null
    );

    $debugInfo[] = "Settings API Response: " . json_encode($settingsResponse);
    
    // Add more detailed debugging for the Supabase request
    $debugInfo[] = "SUPABASE_URL: " . SUPABASE_URL;
    $debugInfo[] = "Supabase Endpoint: " . SUPABASE_URL . "/rest/v1/settings?select=key,value&limit=10";
    
    // Try to get the specific websocket_url setting
    $websocketSettingResponse = supabaseRequest(
        "/rest/v1/settings?key=eq.websocket_url&select=key,value",
        'GET',
        null,
        null
    );
    
    $debugInfo[] = "WebSocket URL setting response: " . json_encode($websocketSettingResponse);
    
    // Force clear the cache and try again
    $GLOBALS['_settings_cache'] = [];
    
    // Check if settings table exists
    if ($settingsResponse['status'] === 404) {
        $debugInfo[] = "⚠️ The settings table does not exist yet. Run setup-settings.php to create it.";
    } elseif ($settingsResponse['status'] !== 200) {
        $debugInfo[] = "⚠️ Error connecting to database: " . ($settingsResponse['error'] ?? "Unknown error");
    } else {
        if (empty($settingsResponse['data'])) {
            $debugInfo[] = "⚠️ Settings table exists but is empty. No 'websocket_url' setting found.";
            // Create default setting
            $createResponse = supabaseRequest(
                "/rest/v1/settings",
                'POST',
                [
                    'key' => 'websocket_url',
                    'value' => 'ws://' . $_SERVER['HTTP_HOST'] . ':8080',
                    'description' => 'WebSocket URL for real-time features'
                ],
                null
            );
            $debugInfo[] = "Attempted to create default websocket_url setting: " . json_encode($createResponse);
        } else {
            $debugInfo[] = "Found settings: " . json_encode($settingsResponse['data']);
            $hasWebsocketUrl = false;
            foreach ($settingsResponse['data'] as $setting) {
                if ($setting['key'] === 'websocket_url') {
                    $hasWebsocketUrl = true;
                    $debugInfo[] = "✓ Found websocket_url in main settings list: " . $setting['value'];
                    break;
                }
            }
            
            if (!$hasWebsocketUrl) {
                $debugInfo[] = "⚠️ No 'websocket_url' setting found in the main settings list.";
                
                // Check the specific websocket_url response
                if ($websocketSettingResponse['status'] === 200 && !empty($websocketSettingResponse['data'])) {
                    $debugInfo[] = "✓ Found websocket_url in direct request: " . $websocketSettingResponse['data'][0]['value'];
                } else {
                    $debugInfo[] = "⚠️ No 'websocket_url' setting found in direct request either.";
                    
                    // Create default setting
                    $createResponse = supabaseRequest(
                        "/rest/v1/settings",
                        'POST',
                        [
                            'key' => 'websocket_url',
                            'value' => 'ws://' . $_SERVER['HTTP_HOST'] . ':8080',
                            'description' => 'WebSocket URL for real-time features'
                        ],
                        null
                    );
                    $debugInfo[] = "Attempted to create default websocket_url setting: " . json_encode($createResponse);
                }
            }
        }
    }

    // Get the current websocket URL (after possible creation)
    $currentUrl = getWebSocketUrl();
    
    // Check if we can get the setting directly with SQL
    $sqlResponse = supabaseRequest(
        "/rest/v1/rpc/execute_sql",
        'POST',
        ['sql' => "SELECT key, value FROM settings WHERE key = 'websocket_url';"],
        null
    );
    $debugInfo[] = "SQL query response: " . json_encode($sqlResponse);

    // If we got here and have a value, settings are working!
    if ($currentUrl && $currentUrl !== 'ws://localhost:8080') {
        $debugInfo[] = "✅ Successfully retrieved WebSocket URL from database: " . $currentUrl;
    } elseif ($settingsResponse['status'] === 200 && !empty($settingsResponse['data'])) {
        $debugInfo[] = "✅ Settings table exists and has data. Using WebSocket URL: " . $currentUrl;
    } else {
        $debugInfo[] = "⚠️ Something might still be wrong. Check the response data above.";
    }
}

$debugInfo[] = "Current WebSocket URL from getWebSocketUrl(): " . $currentUrl;

// Add a notification about browser cache
$debugInfo[] = "\nNOTE: If you've added the setting but still see this message, try clearing your browser cache or opening in a private/incognito window.";

// If there are issues, display a notice
$showDebug = true; // Set to false to hide debug info in production
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Battle Server WebSocket Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        #console {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            padding: 10px;
            height: 400px;
            overflow-y: auto;
            font-family: monospace;
            margin-bottom: 10px;
        }
        .log {
            margin: 5px 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .send { color: blue; }
        .receive { color: green; }
        .error { color: red; }
        .info { color: gray; }
        button {
            padding: 5px 10px;
            margin-right: 5px;
        }
        .debug-info {
            background-color: #fff3cd;
            border: 1px solid #ffecb5;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
            font-family: monospace;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <h1>Battle Server WebSocket Test</h1>
    
    <?php if ($showDebug): ?>
    <div class="debug-info">
        <h4>Debug Information</h4>
        <?php foreach ($debugInfo as $info): ?>
            <div><?php echo htmlspecialchars($info); ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <div id="connection-status">Status: Disconnected</div>
    <div class="mb-3">WebSocket URL: <span id="websocket-url"><?php echo $currentUrl; ?></span></div>
    <div id="console"></div>
    <div id="controls">
        <button id="connectBtn">Connect</button>
        <button id="loginBtn" disabled>Login</button>
        <button id="findMatchBtn" disabled>Find Match</button>
        <button id="heartbeatBtn" disabled>Send Heartbeat</button>
        <button id="disconnectBtn" disabled>Disconnect</button>
    </div>

    <!-- Load our WebSocket module -->
    <script src="js/battle-websocket.js"></script>
    
    <script>
        // Test user data
        let userId = 'test-user-' + Math.floor(Math.random() * 1000);
        let username = 'Test User';
        
        // DOM elements
        const consoleElem = document.getElementById('console');
        const statusElem = document.getElementById('connection-status');
        const connectBtn = document.getElementById('connectBtn');
        const loginBtn = document.getElementById('loginBtn');
        const findMatchBtn = document.getElementById('findMatchBtn');
        const heartbeatBtn = document.getElementById('heartbeatBtn');
        const disconnectBtn = document.getElementById('disconnectBtn');
        
        // Log to console
        function log(message, type = 'info') {
            const logItem = document.createElement('div');
            logItem.className = `log ${type}`;
            logItem.textContent = message;
            consoleElem.appendChild(logItem);
            consoleElem.scrollTop = consoleElem.scrollHeight;
        }
        
        // Set up WebSocket event handlers
        BattleWebSocket.on('onOpen', (event) => {
            log('Connected to server', 'info');
            statusElem.textContent = 'Status: Connected';
            connectBtn.disabled = true;
            loginBtn.disabled = false;
            disconnectBtn.disabled = false;
        });
        
        BattleWebSocket.on('onMessage', (data) => {
            log(`Received: ${JSON.stringify(data)}`, 'receive');
            
            // Handle login success
            if (data.type === 'loginSuccess') {
                findMatchBtn.disabled = false;
                heartbeatBtn.disabled = false;
            }
        });
        
        BattleWebSocket.on('onClose', (event) => {
            log('Disconnected from server', 'info');
            statusElem.textContent = 'Status: Disconnected';
            connectBtn.disabled = false;
            loginBtn.disabled = true;
            findMatchBtn.disabled = true;
            heartbeatBtn.disabled = true;
            disconnectBtn.disabled = true;
        });
        
        BattleWebSocket.on('onError', (event) => {
            log('WebSocket error', 'error');
            statusElem.textContent = 'Status: Error';
        });
        
        // Connect to WebSocket server
        connectBtn.addEventListener('click', () => {
            if (BattleWebSocket.isConnected) {
                log('Already connected, please disconnect first', 'error');
                return;
            }
            
            // Get the URL from the element
            const websocketUrl = document.getElementById('websocket-url').textContent;
            log(`Connecting to ${websocketUrl}...`);
            
            BattleWebSocket.init(websocketUrl).catch(error => {
                log(`Error: ${error.message || 'Connection failed'}`, 'error');
            });
        });
        
        // Login to server
        loginBtn.addEventListener('click', () => {
            if (!BattleWebSocket.isConnected) {
                log('Not connected to server', 'error');
                return;
            }
            
            const message = {
                action: 'login',
                userId: userId,
                username: username,
                avatar: 'default-avatar.png'
            };
            
            BattleWebSocket.send(message);
            log(`Sent: ${JSON.stringify(message)}`, 'send');
        });
        
        // Find match
        findMatchBtn.addEventListener('click', () => {
            if (!BattleWebSocket.isConnected) {
                log('Not connected to server', 'error');
                return;
            }
            
            const message = {
                action: 'find_match',
                config: {
                    difficulty: 'medium',
                    subject: 'general',
                    questionCount: 5
                }
            };
            
            BattleWebSocket.send(message);
            log(`Sent: ${JSON.stringify(message)}`, 'send');
        });
        
        // Send heartbeat
        heartbeatBtn.addEventListener('click', () => {
            if (!BattleWebSocket.isConnected) {
                log('Not connected to server', 'error');
                return;
            }
            
            const message = {
                action: 'heartbeat',
                timestamp: Date.now()
            };
            
            BattleWebSocket.send(message);
            log(`Sent: ${JSON.stringify(message)}`, 'send');
        });
        
        // Disconnect from server
        disconnectBtn.addEventListener('click', () => {
            if (!BattleWebSocket.isConnected) {
                log('Not connected to server', 'error');
                return;
            }
            
            BattleWebSocket.disconnect();
        });
    </script>
</body>
</html> 