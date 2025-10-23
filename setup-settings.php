<?php
/**
 * Settings Table Setup Script
 * 
 * This script initializes the Settings table in Supabase and provides SQL
 * instructions to insert the websocket_url setting, which needs to be run
 * directly in the Supabase SQL editor due to RLS policy restrictions.
 */

// Start session for potential messages
session_start();

// Load configuration
require_once 'includes/config.php';

echo "<h1>Settings Table Setup</h1>";
echo "<pre>";
echo "Starting Settings table setup...\n";

// Check if table exists in Supabase
$response = supabaseRequest(
    "/rest/v1/settings?limit=1",
    'GET',
    null,
    null
);

$tableExists = ($response['status'] === 200);
$needsCreation = ($response['status'] === 404 || !$tableExists);

echo "Table check response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";

if ($needsCreation) {
    echo "Settings table does not exist or needs creation.\n\n";
    echo "⚠️ You'll need to run the SQL directly in the Supabase SQL Editor.\n";
    echo "Copy and paste the following SQL into your Supabase SQL Editor:\n\n";
    
    $createTableSQL = "
-- Create the settings table for dynamic application configuration
CREATE TABLE IF NOT EXISTS settings (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  key TEXT NOT NULL UNIQUE,
  value TEXT NOT NULL,
  description TEXT,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Insert default setting for WebSocket URL
INSERT INTO settings (key, value, description)
VALUES ('websocket_url', 'ws://" . $_SERVER['HTTP_HOST'] . ":8080', 'WebSocket URL for real-time features')
ON CONFLICT (key) 
DO UPDATE SET 
  value = EXCLUDED.value,
  description = EXCLUDED.description,
  updated_at = CURRENT_TIMESTAMP;

-- Add Row Level Security policies
ALTER TABLE settings ENABLE ROW LEVEL SECURITY;

-- Create a policy that allows admins to manage settings
CREATE POLICY \"Allow admins to manage settings\" 
ON settings 
FOR ALL
USING (auth.role() = 'authenticated' AND auth.jwt() ->> 'is_admin' = 'true')
WITH CHECK (auth.role() = 'authenticated' AND auth.jwt() ->> 'is_admin' = 'true');

-- Create a policy that allows all authenticated users to read settings
CREATE POLICY \"Allow authenticated users to read settings\" 
ON settings 
FOR SELECT
USING (auth.role() = 'authenticated');

-- Create a policy that allows anonymous access to read settings
CREATE POLICY \"Allow anonymous to read settings\" 
ON settings 
FOR SELECT
USING (true);
";
    
    echo "<code>$createTableSQL</code>\n\n";
} else {
    echo "Settings table already exists.\n";
    
    // Check for the websocket_url setting
    $settingsResponse = supabaseRequest(
        "/rest/v1/settings?key=eq.websocket_url&select=key,value",
        'GET',
        null,
        null
    );
    
    $hasWebsocketUrl = ($settingsResponse['status'] === 200 && !empty($settingsResponse['data']));
    
    echo "WebSocket URL check response: " . json_encode($settingsResponse, JSON_PRETTY_PRINT) . "\n";
    
    // Generate a default WebSocket URL
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $defaultWebsocketUrl = 'ws://' . $host . ':8080';
    
    if (!$hasWebsocketUrl) {
        echo "\n⚠️ The websocket_url setting is missing!\n\n";
        echo "Due to Row-Level Security (RLS) policies, you need to create the setting directly in the Supabase SQL Editor.\n";
        echo "Copy and paste the following SQL into your Supabase SQL Editor:\n\n";
        
        $insertSQL = "
-- Insert websocket_url setting
INSERT INTO settings (key, value, description)
VALUES ('websocket_url', '" . $defaultWebsocketUrl . "', 'WebSocket URL for real-time features')
ON CONFLICT (key) 
DO UPDATE SET 
  value = EXCLUDED.value,
  description = EXCLUDED.description,
  updated_at = CURRENT_TIMESTAMP;
";
        
        echo "<code>$insertSQL</code>\n\n";
        
        // Try to create setting directly (will likely fail due to RLS)
        $createResponse = supabaseRequest(
            "/rest/v1/settings",
            'POST',
            [
                'key' => 'websocket_url',
                'value' => $defaultWebsocketUrl,
                'description' => 'WebSocket URL for real-time features'
            ],
            null
        );
        
        echo "Automatic insertion attempt result: " . json_encode($createResponse, JSON_PRETTY_PRINT) . "\n";
        
        if ($createResponse['status'] === 401) {
            echo "\n⚠️ As expected, the automatic insertion failed due to RLS policy restrictions.\n";
            echo "Please use the SQL above to create the setting manually in the Supabase SQL Editor.\n";
        } elseif ($createResponse['status'] === 201) {
            echo "\n✅ Surprisingly, the setting was created successfully! You don't need to run the SQL manually.\n";
        }
    } else {
        echo "\n✅ The websocket_url setting exists with value: " . $settingsResponse['data'][0]['value'] . "\n";
        
        echo "\nIf you want to update the WebSocket URL, run this SQL in your Supabase SQL Editor:\n\n";
        
        $updateSQL = "
-- Update websocket_url setting
UPDATE settings
SET value = '" . $defaultWebsocketUrl . "',
    updated_at = CURRENT_TIMESTAMP
WHERE key = 'websocket_url';
";
        
        echo "<code>$updateSQL</code>\n";
    }
}

// Get the current websocket URL
$currentUrl = getWebSocketUrl();
echo "\nCurrent WebSocket URL from getWebSocketUrl(): " . $currentUrl . "\n";

echo "</pre>";

echo "<div style='margin: 20px 0; padding: 15px; background-color: #e9f7ef; border: 1px solid #27ae60; border-radius: 5px;'>";
echo "<h3>Instructions to Create Settings in Supabase</h3>";
echo "<ol>";
echo "<li>Log in to your Supabase dashboard at <a href='https://app.supabase.io' target='_blank'>https://app.supabase.io</a></li>";
echo "<li>Select your project</li>";
echo "<li>Go to the 'SQL Editor' section</li>";
echo "<li>Create a new query</li>";
echo "<li>Copy and paste the SQL code above</li>";
echo "<li>Click 'Run' to execute the SQL</li>";
echo "<li>Refresh this page to confirm the setting was created</li>";
echo "</ol>";
echo "</div>";

echo "<p><a href='test-websocket.php' style='display: inline-block; padding: 10px 15px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;'>Go to WebSocket Test Page</a></p>";
?> 