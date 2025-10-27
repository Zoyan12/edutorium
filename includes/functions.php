<?php
/**
 * Common functions for the Edutorium Battle System
 */

// Global cache for settings
$GLOBALS['_settings_cache'] = [];

/**
 * Make a request to the Supabase API
 * 
 * @param string $endpoint The API endpoint to call
 * @param string $method The HTTP method (GET, POST, PATCH, DELETE)
 * @param array|null $data The data to send with the request
 * @param string $token The authentication token
 * @return array Response with status and data
 */
function supabaseRequest($endpoint, $method = 'GET', $data = null, $token = null) {
    require_once 'config.php';
    
    $url = SUPABASE_URL . $endpoint;
    $ch = curl_init($url);
    
    $headers = [
        'Content-Type: application/json',
        'apikey: ' . SUPABASE_ANON_KEY,
        'Prefer: return=representation'
    ];
    
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST' || $method === 'PATCH' || $method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        return ['status' => 0, 'error' => $error];
    }
    
    $responseData = json_decode($response, true);
    
    return [
        'status' => $statusCode,
        'data' => $responseData
    ];
}

/**
 * Get a setting value from the database
 * 
 * @param string $key Setting key
 * @param mixed $default Default value if setting not found
 * @param bool $useCache Whether to use cache or force a fresh database lookup
 * @return mixed The setting value or default if not found
 */
function getSetting($key, $default = null, $useCache = true) {
    // Check if Supabase URL is still the default value
    if (defined('SUPABASE_URL') && SUPABASE_URL === 'YOUR_ACTUAL_SUPABASE_URL') {
        // If we're looking for the WebSocket URL, return a working local value
        if ($key === 'websocket_url') {
            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
            return 'ws://' . $host . ':8080';
        }
        return $default;
    }
    
    // Check cache first if enabled
    if ($useCache && isset($GLOBALS['_settings_cache'][$key])) {
        return $GLOBALS['_settings_cache'][$key];
    }
    
    // Get from database - no token needed for settings (uses anonymous access)
    $response = supabaseRequest(
        "/rest/v1/settings?key=eq." . urlencode($key) . "&select=value",
        'GET',
        null,
        null
    );
    
    if ($response['status'] === 200 && !empty($response['data'])) {
        $value = $response['data'][0]['value'];
        
        // Cache the value
        $GLOBALS['_settings_cache'][$key] = $value;
        
        return $value;
    }
    
    // Try direct SQL query as a last resort for certain keys
    if ($key === 'websocket_url') {
        $sqlResponse = supabaseRequest(
            "/rest/v1/rpc/execute_sql",
            'POST',
            ['sql' => "SELECT value FROM settings WHERE key = '{$key}';"],
            null
        );
        
        if ($sqlResponse['status'] === 200 && isset($sqlResponse['data'][0]['value'])) {
            $value = $sqlResponse['data'][0]['value'];
            $GLOBALS['_settings_cache'][$key] = $value;
            return $value;
        }
    }
    
    // Log error in development mode
    if (defined('APP_ENV') && APP_ENV === 'development') {
        error_log("Failed to fetch setting '{$key}': " . json_encode($response));
    }
    
    return $default;
}

/**
 * Update a setting in the database
 * 
 * @param string $key Setting key
 * @param string $value New value
 * @param string $description Optional description
 * @return bool Success status
 */
function updateSetting($key, $value, $description = null) {
    // Require admin access
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['is_admin']) || $_SESSION['user']['is_admin'] !== true) {
        return false;
    }
    
    $data = [
        'value' => $value,
        'updated_at' => date('c')
    ];
    
    if ($description !== null) {
        $data['description'] = $description;
    }
    
    $response = supabaseRequest(
        "/rest/v1/settings?key=eq." . urlencode($key),
        'PATCH',
        $data,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 200 || $response['status'] === 204) {
        // Update cache
        $GLOBALS['_settings_cache'][$key] = $value;
        return true;
    }
    
    return false;
}

/**
 * Create a new setting in the database
 * 
 * @param string $key Setting key
 * @param string $value Setting value
 * @param string $description Optional description
 * @return bool Success status
 */
function createSetting($key, $value, $description = null) {
    // Require admin access
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['is_admin']) || $_SESSION['user']['is_admin'] !== true) {
        return false;
    }
    
    $data = [
        'key' => $key,
        'value' => $value
    ];
    
    if ($description !== null) {
        $data['description'] = $description;
    }
    
    $response = supabaseRequest(
        "/rest/v1/settings",
        'POST',
        $data,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 201) {
        // Update cache
        $GLOBALS['_settings_cache'][$key] = $value;
        return true;
    }
    
    return false;
}

/**
 * Get the WebSocket URL from settings
 * 
 * @return string The WebSocket URL
 */
function getWebSocketUrl() {
    // Get the WebSocket URL from the database
    $url = getSetting('websocket_url', 'ws://localhost:8080');
    
    // If the URL is using wss:// and doesn't have a port, append port 3000
    if (strpos($url, 'wss://') === 0) {
        // Extract the host part (everything after wss:// and before the first /)
        $hostPart = substr($url, 6); // Remove 'wss://'
        $slashPos = strpos($hostPart, '/');
        if ($slashPos !== false) {
            $hostPart = substr($hostPart, 0, $slashPos);
        }
        
        // If there's no colon in the host part, it means no port is specified
        if (strpos($hostPart, ':') === false) {
            $url = $url . ':3000';
        }
    }
    
    return $url;
}

/**
 * Sanitize input to prevent XSS attacks
 * 
 * @param string $input The input to sanitize
 * @return string Sanitized input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Set a flash message to be displayed on the next page load
 * 
 * @param string $message The message text
 * @param string $type The message type (success, danger, warning, info)
 */
function setFlashMessage($message, $type = 'info') {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    
    $_SESSION['flash_messages'][] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Display flash messages and clear them from session
 * 
 * @return string HTML for flash messages
 */
function displayFlashMessage() {
    if (!isset($_SESSION['flash_messages']) || empty($_SESSION['flash_messages'])) {
        return '';
    }
    
    $output = '';
    foreach ($_SESSION['flash_messages'] as $flash) {
        $output .= '<div class="alert alert-' . $flash['type'] . ' alert-dismissible fade show" role="alert">';
        $output .= sanitizeInput($flash['message']);
        $output .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        $output .= '</div>';
    }
    
    // Clear flash messages
    $_SESSION['flash_messages'] = [];
    
    return $output;
} 