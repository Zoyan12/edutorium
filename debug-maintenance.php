<?php
/**
 * Debug Maintenance Mode
 * This script helps debug maintenance mode issues
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once 'includes/config.php';
require_once 'includes/functions.php';

echo "<h1>Maintenance Mode Debug</h1>\n";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .debug-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .debug-info { background: #d1ecf1; border-color: #bee5eb; }
    .debug-success { background: #d4edda; border-color: #c3e6cb; }
    .debug-error { background: #f8d7da; border-color: #f5c6cb; }
    .debug-result { margin: 10px 0; padding: 10px; border-radius: 3px; }
    .success { background: #d4edda; color: #155724; }
    .error { background: #f8d7da; color: #721c24; }
    .info { background: #d1ecf1; color: #0c5460; }
</style>\n";

// Debug current page path
echo "<div class='debug-section debug-info'>\n";
echo "<h2>Current Page Information</h2>\n";
echo "<div class='debug-result info'>Current URL: " . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "</div>\n";
echo "<div class='debug-result info'>Script Name: " . ($_SERVER['SCRIPT_NAME'] ?? 'Not set') . "</div>\n";
echo "<div class='debug-result info'>HTTP Host: " . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "</div>\n";
echo "<div class='debug-result info'>Protocol: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'HTTPS' : 'HTTP') . "</div>\n";
echo "</div>\n";

// Debug session information
echo "<div class='debug-section debug-info'>\n";
echo "<h2>Session Information</h2>\n";
echo "<div class='debug-result info'>Session ID: " . session_id() . "</div>\n";
echo "<div class='debug-result info'>Session Status: " . session_status() . "</div>\n";
if (isset($_SESSION['user'])) {
    echo "<div class='debug-result info'>User Session: " . json_encode($_SESSION['user']) . "</div>\n";
} else {
    echo "<div class='debug-result info'>No user session found</div>\n";
}
if (isset($_SESSION['admin_profile'])) {
    echo "<div class='debug-result info'>Admin Profile: " . json_encode($_SESSION['admin_profile']) . "</div>\n";
} else {
    echo "<div class='debug-result info'>No admin profile found</div>\n";
}
echo "</div>\n";

// Debug maintenance mode status
echo "<div class='debug-section debug-info'>\n";
echo "<h2>Maintenance Mode Status</h2>\n";
try {
    $response = supabaseRequest(
        "/rest/v1/maintenance_mode?is_active=eq.true&select=*&order=start_time.desc&limit=1",
        'GET',
        null,
        null
    );
    
    if ($response['status'] === 200) {
        if (!empty($response['data'])) {
            echo "<div class='debug-result error'>Maintenance Mode: ACTIVE</div>\n";
            echo "<div class='debug-result info'>Maintenance Data: " . json_encode($response['data'][0]) . "</div>\n";
        } else {
            echo "<div class='debug-result success'>Maintenance Mode: INACTIVE</div>\n";
        }
    } else {
        echo "<div class='debug-result error'>Failed to check maintenance status: HTTP {$response['status']}</div>\n";
    }
} catch (Exception $e) {
    echo "<div class='debug-result error'>Error checking maintenance status: " . $e->getMessage() . "</div>\n";
}
echo "</div>\n";

// Debug page exclusion logic
echo "<div class='debug-section debug-info'>\n";
echo "<h2>Page Exclusion Logic</h2>\n";

function getCurrentPagePath() {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    
    // Extract path from request URI (remove query string)
    $path = parse_url($requestUri, PHP_URL_PATH);
    
    return $path ?: $scriptName;
}

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
            return $excludedPath;
        }
    }
    
    return false;
}

$currentPath = getCurrentPagePath();
$excludedBy = isPageExcludedFromMaintenance($currentPath);

echo "<div class='debug-result info'>Current Path: {$currentPath}</div>\n";
if ($excludedBy) {
    echo "<div class='debug-result success'>Page is EXCLUDED by: {$excludedBy}</div>\n";
} else {
    echo "<div class='debug-result error'>Page is NOT EXCLUDED</div>\n";
}
echo "</div>\n";

// Debug admin check
echo "<div class='debug-section debug-info'>\n";
echo "<h2>Admin Check</h2>\n";

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

function hasMaintenanceBypass() {
    // Check bypass cookie
    if (isset($_COOKIE['maintenance_bypass']) && $_COOKIE['maintenance_bypass'] === 'true') {
        return true;
    }
    
    return false;
}

$isAdmin = isCurrentUserAdmin();
$hasBypass = hasMaintenanceBypass();

echo "<div class='debug-result info'>Is Admin: " . ($isAdmin ? 'YES' : 'NO') . "</div>\n";
echo "<div class='debug-result info'>Has Bypass: " . ($hasBypass ? 'YES' : 'NO') . "</div>\n";
echo "<div class='debug-result info'>Bypass Cookie: " . ($_COOKIE['maintenance_bypass'] ?? 'Not set') . "</div>\n";
echo "</div>\n";

// Debug redirection logic
echo "<div class='debug-section debug-info'>\n";
echo "<h2>Redirection Logic</h2>\n";

$shouldRedirect = false;
$redirectReason = '';

// Skip check for excluded pages
if ($excludedBy) {
    $redirectReason = "Page is excluded by: {$excludedBy}";
} else {
    // Skip check if maintenance mode is not active
    try {
        $response = supabaseRequest(
            "/rest/v1/maintenance_mode?is_active=eq.true&select=id",
            'GET',
            null,
            null
        );
        
        $isActive = $response['status'] === 200 && !empty($response['data']);
        
        if (!$isActive) {
            $redirectReason = "Maintenance mode is not active";
        } else {
            // Skip check for admin users with bypass
            if ($isAdmin && $hasBypass) {
                $redirectReason = "Admin user has bypass";
            } else {
                // Skip check for admin users accessing admin pages
                if ($isAdmin && strpos($currentPath, '/admin/') === 0) {
                    $redirectReason = "Admin user accessing admin page";
                } else {
                    $shouldRedirect = true;
                    $redirectReason = "Should redirect to maintenance page";
                }
            }
        }
    } catch (Exception $e) {
        $redirectReason = "Error checking maintenance status: " . $e->getMessage();
    }
}

echo "<div class='debug-result info'>Should Redirect: " . ($shouldRedirect ? 'YES' : 'NO') . "</div>\n";
echo "<div class='debug-result info'>Reason: {$redirectReason}</div>\n";

if ($shouldRedirect) {
    $maintenanceUrl = '/maintenance.php';
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $fullUrl = $protocol . '://' . $host . $maintenanceUrl;
    
    echo "<div class='debug-result error'>Would redirect to: {$fullUrl}</div>\n";
    echo "<div class='debug-result info'><a href='{$fullUrl}' target='_blank'>Test Maintenance Page</a></div>\n";
}
echo "</div>\n";

// Test maintenance page directly
echo "<div class='debug-section debug-info'>\n";
echo "<h2>Test Links</h2>\n";
echo "<div class='debug-result info'><a href='maintenance.php' target='_blank'>Test Maintenance Page (relative)</a></div>\n";
echo "<div class='debug-result info'><a href='maintenance.php?preview=true&message=Test%20Message&resolution=Soon&reason=Testing' target='_blank'>Test Maintenance Page (preview)</a></div>\n";
echo "<div class='debug-result info'><a href='admin/maintenance.php' target='_blank'>Admin Maintenance Panel</a></div>\n";
echo "</div>\n";
?>
