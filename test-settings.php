<?php
/**
 * Direct Settings Table Test
 * 
 * This script directly tests the settings table in Supabase
 * without any caching or complex logic to diagnose issues.
 */

// Start session for potential messages
session_start();

// Load configuration
require_once 'includes/config.php';

echo "<h1>Settings Table Direct Test</h1>";
echo "<pre>";

// Display current configuration
echo "Using Supabase URL: " . SUPABASE_URL . "\n";
echo "ANON Key length: " . strlen(SUPABASE_ANON_KEY) . " characters\n\n";

// 1. Directly try to read all settings
echo "STEP 1: Reading all settings from the table\n";
$response = supabaseRequest(
    "/rest/v1/settings?select=*",
    'GET',
    null,
    null
);

echo "Response Status: " . $response['status'] . "\n";
echo "Data: " . json_encode($response['data'], JSON_PRETTY_PRINT) . "\n\n";

// 2. Specifically check for websocket_url
echo "STEP 2: Looking specifically for websocket_url setting\n";
$response = supabaseRequest(
    "/rest/v1/settings?key=eq.websocket_url&select=*",
    'GET',
    null,
    null
);

echo "Response Status: " . $response['status'] . "\n";
echo "Data: " . json_encode($response['data'], JSON_PRETTY_PRINT) . "\n\n";

// 3. Try using direct SQL for reading
echo "STEP 3: Using direct SQL to read the setting\n";
$sqlResponse = supabaseRequest(
    "/rest/v1/rpc/execute_sql",
    'POST',
    ['sql' => "SELECT * FROM settings WHERE key = 'websocket_url';"],
    null
);

echo "SQL Response Status: " . $sqlResponse['status'] . "\n";
echo "SQL Data: " . json_encode($sqlResponse['data'] ?? null, JSON_PRETTY_PRINT) . "\n\n";

// 4. Try to insert the setting directly with REST API
echo "STEP 4: Trying to insert setting using REST API\n";
$insertResponse = supabaseRequest(
    "/rest/v1/settings",
    'POST',
    [
        'key' => 'websocket_url',
        'value' => 'ws://' . $_SERVER['HTTP_HOST'] . ':8080',
        'description' => 'WebSocket URL for real-time features (added by test script)'
    ],
    null
);

echo "Insert Response Status: " . $insertResponse['status'] . "\n";
echo "Insert Data: " . json_encode($insertResponse['data'] ?? null, JSON_PRETTY_PRINT) . "\n\n";

// 5. Try to insert using SQL as a last resort
echo "STEP 5: Trying to insert using direct SQL\n";
$sqlInsertResponse = supabaseRequest(
    "/rest/v1/rpc/execute_sql",
    'POST',
    ['sql' => "INSERT INTO settings (key, value, description) 
               VALUES ('websocket_url', 'ws://{$_SERVER['HTTP_HOST']}:8080', 'WebSocket URL for real-time features (SQL)') 
               ON CONFLICT (key) DO UPDATE 
               SET value = EXCLUDED.value, 
                   description = EXCLUDED.description, 
                   updated_at = CURRENT_TIMESTAMP
               RETURNING *;"],
    null
);

echo "SQL Insert Response Status: " . $sqlInsertResponse['status'] . "\n";
echo "SQL Insert Data: " . json_encode($sqlInsertResponse['data'] ?? null, JSON_PRETTY_PRINT) . "\n\n";

// 6. Test by getting the value through our getSetting function
echo "STEP 6: Using getSetting() function to read the value\n";
$cachedValue = getSetting('websocket_url', 'DEFAULT-VALUE', true);
echo "Value from getSetting with cache: " . $cachedValue . "\n";

// Clear cache and try again
$GLOBALS['_settings_cache'] = [];
$uncachedValue = getSetting('websocket_url', 'DEFAULT-VALUE', false);
echo "Value from getSetting without cache: " . $uncachedValue . "\n\n";

echo "Test completed.";
echo "</pre>";

echo '<p><a href="test-websocket.php" style="padding: 10px 15px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;">Go to WebSocket Test Page</a></p>';
?> 