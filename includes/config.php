<?php
/**
 * Configuration file for Edutorium Battle System
 */

// Application settings
define('APP_NAME', 'Edutorium Battle System');

// Detect if running in CLI mode (for WebSocket server) or in web mode
$is_cli = (php_sapi_name() == 'cli');

// Set APP_URL differently based on environment
if ($is_cli) {
    // In CLI mode (WebSocket server)
    define('APP_URL', 'http://localhost/new');
} else {
    // In web mode
    define('APP_URL', 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/new');
}

define('APP_ENV', 'development'); // development, production

// Supabase settings - can be overridden by environment variables
$supabase_url = getenv('SUPABASE_URL');
$supabase_key = getenv('SUPABASE_KEY');

// Default fallback values
if (empty($supabase_url)) {
    $supabase_url = 'https://ratxqmbqzwbvfgsonlrd.supabase.co';
}

if (empty($supabase_key)) {
    $supabase_key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InJhdHhxbWJxendidmZnc29ubHJkIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDQyMDI0NDAsImV4cCI6MjA1OTc3ODQ0MH0.HJ9nQbvVvVisvQb6HMVMlmQBVmW7Ie42Z6Afdwn8W2M';
}

// Define constants with values determined above
define('SUPABASE_URL', $supabase_url);
define('SUPABASE_ANON_KEY', $supabase_key);

// Error reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Session settings - only apply if in web context and session hasn't started yet
if (!$is_cli && session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    if (APP_ENV === 'production') {
        ini_set('session.cookie_secure', 1);
    }
    
    // Start the session
    session_start();
    
    // CSRF token for forms
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

// Include common functions
require_once 'functions.php'; 