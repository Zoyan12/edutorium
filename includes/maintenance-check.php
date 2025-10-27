<?php
/**
 * Maintenance Mode Check
 * Include this file at the top of all pages to check for maintenance mode
 * and redirect users to the maintenance page if active
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

/**
 * Check if maintenance mode is active
 * @return bool True if maintenance mode is active
 */
function isMaintenanceModeActive() {
    try {
        $response = supabaseRequest(
            "/rest/v1/maintenance_mode?is_active=eq.true&select=id",
            'GET',
            null,
            null
        );
        
        return $response['status'] === 200 && !empty($response['data']);
    } catch (Exception $e) {
        // If we can't check, assume maintenance mode is not active
        return false;
    }
}

/**
 * Check if current user is admin
 * @return bool True if user is admin
 */
function isCurrentUserAdmin() {
    // Check session for admin profile
    if (isset($_SESSION['admin_profile']) && isset($_SESSION['admin_profile']['is_admin'])) {
        return $_SESSION['admin_profile']['is_admin'] === true;
    }
    
    // Check if user session has is_admin flag directly
    if (isset($_SESSION['user']) && isset($_SESSION['user']['is_admin'])) {
        return $_SESSION['user']['is_admin'] === true;
    }
    
    // Check session for user and verify admin status
    if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
        try {
            $response = supabaseRequest(
                "/rest/v1/profiles?user_id=eq." . urlencode($_SESSION['user']['id']) . "&select=is_admin",
                'GET',
                null,
                $_SESSION['user']['token'] ?? null
            );
            
            if ($response['status'] === 200 && !empty($response['data'])) {
                return $response['data'][0]['is_admin'] === true;
            }
        } catch (Exception $e) {
            // If we can't verify, assume not admin
        }
    }
    
    return false;
}

/**
 * Check if user has maintenance bypass
 * @return bool True if user has bypass
 */
function hasMaintenanceBypass() {
    // Check bypass cookie
    if (isset($_COOKIE['maintenance_bypass']) && $_COOKIE['maintenance_bypass'] === 'true') {
        return true;
    }
    
    return false;
}

/**
 * Get current page path for exclusion checking
 * @return string Current page path
 */
function getCurrentPagePath() {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    
    // Extract path from request URI (remove query string)
    $path = parse_url($requestUri, PHP_URL_PATH);
    
    return $path ?: $scriptName;
}

/**
 * Check if current page should be excluded from maintenance mode
 * @param string $pagePath Current page path
 * @return bool True if page should be excluded
 */
function isPageExcludedFromMaintenance($pagePath) {
    $excludedPages = [
        '/maintenance.php',
        '/admin/',
        '/api/',
        '/css/',
        '/js/',
        '/img/',
        '/assets/',
        '/vendor/',
        '/includes/',
        '/sql/',
        '/docs/',
        '/monitoring/',
        '/nginx/',
        '/utils/',
        '/src/',
        '/pages/login.html',
        '/pages/signup.html'
    ];
    
    foreach ($excludedPages as $excludedPath) {
        if (strpos($pagePath, $excludedPath) === 0) {
            return true;
        }
    }
    
    return false;
}

// Main maintenance mode check
function checkMaintenanceMode() {
    // Skip check for excluded pages
    $currentPath = getCurrentPagePath();
    if (isPageExcludedFromMaintenance($currentPath)) {
        return;
    }
    
    // Skip check if maintenance mode is not active
    if (!isMaintenanceModeActive()) {
        return;
    }
    
    // Skip check for admin users with bypass
    if (isCurrentUserAdmin() && hasMaintenanceBypass()) {
        return;
    }
    
    // Skip check for admin users accessing admin pages
    if (isCurrentUserAdmin() && strpos($currentPath, '/admin/') === 0) {
        return;
    }
    
    // Redirect to maintenance page
    $maintenanceUrl = '/maintenance.php';
    
    // Preserve query parameters for admin bypass
    if (isCurrentUserAdmin() && isset($_GET['bypass'])) {
        $maintenanceUrl .= '?bypass=' . urlencode($_GET['bypass']);
    }
    
    // Get the correct base path based on current location
    $currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
    $currentUri = $_SERVER['REQUEST_URI'] ?? '';
    
    // Extract the base path from the current script
    // For example: /edutorium/client/pages/dashboard.php -> /edutorium/client
    $pathParts = explode('/', trim($currentScript, '/'));
    
    // Find the project root path (everything before the specific page and its directory)
    $basePath = '';
    if (count($pathParts) >= 2) {
        // Remove the last part (the actual file)
        array_pop($pathParts);
        
        // For pages in subdirectories like /pages/, we need to go up one more level
        // Check if the last remaining part is a common subdirectory
        $commonSubdirs = ['pages', 'admin', 'api', 'css', 'js', 'img', 'assets', 'vendor', 'includes', 'sql', 'docs', 'monitoring', 'nginx', 'utils', 'src'];
        
        if (count($pathParts) > 0 && in_array(end($pathParts), $commonSubdirs)) {
            // Remove the subdirectory as well
            array_pop($pathParts);
        }
        
        $basePath = '/' . implode('/', $pathParts);
    }
    
    // Construct the full maintenance URL
    if ($basePath && $basePath !== '/') {
        $maintenanceUrl = $basePath . $maintenanceUrl;
    }
    
    header('Location: ' . $maintenanceUrl);
    exit();
}

// Perform the check
checkMaintenanceMode();
?>
