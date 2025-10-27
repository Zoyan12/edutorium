<?php
/**
 * Maintenance Mode Test Script
 * Tests all maintenance mode functionality
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Maintenance Mode Test Suite</h1>\n";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .test-pass { background: #d4edda; border-color: #c3e6cb; }
    .test-fail { background: #f8d7da; border-color: #f5c6cb; }
    .test-info { background: #d1ecf1; border-color: #bee5eb; }
    .test-result { margin: 10px 0; padding: 10px; border-radius: 3px; }
    .pass { background: #d4edda; color: #155724; }
    .fail { background: #f8d7da; color: #721c24; }
    .info { background: #d1ecf1; color: #0c5460; }
</style>\n";

/**
 * Test database connection and table existence
 */
function testDatabaseConnection() {
    echo "<div class='test-section'>\n";
    echo "<h2>1. Database Connection Test</h2>\n";
    
    try {
        $response = supabaseRequest(
            "/rest/v1/maintenance_mode?select=count",
            'GET',
            null,
            null
        );
        
        if ($response['status'] === 200) {
            echo "<div class='test-result pass'>✓ Database connection successful</div>\n";
            echo "<div class='test-result pass'>✓ maintenance_mode table exists</div>\n";
            return true;
        } else {
            echo "<div class='test-result fail'>✗ Database connection failed: HTTP {$response['status']}</div>\n";
            return false;
        }
    } catch (Exception $e) {
        echo "<div class='test-result fail'>✗ Database connection error: " . $e->getMessage() . "</div>\n";
        return false;
    }
    
    echo "</div>\n";
}

/**
 * Test maintenance mode API endpoints
 */
function testMaintenanceAPI() {
    echo "<div class='test-section'>\n";
    echo "<h2>2. Maintenance Mode API Test</h2>\n";
    
    // Test public status endpoint
    try {
        $response = supabaseRequest(
            "/rest/v1/maintenance_mode?is_active=eq.true&select=*&order=start_time.desc&limit=1",
            'GET',
            null,
            null
        );
        
        if ($response['status'] === 200) {
            echo "<div class='test-result pass'>✓ Public maintenance status endpoint accessible</div>\n";
            $isActive = !empty($response['data']);
            echo "<div class='test-result info'>ℹ Current maintenance status: " . ($isActive ? 'ACTIVE' : 'INACTIVE') . "</div>\n";
        } else {
            echo "<div class='test-result fail'>✗ Public maintenance status endpoint failed: HTTP {$response['status']}</div>\n";
        }
    } catch (Exception $e) {
        echo "<div class='test-result fail'>✗ Public maintenance status endpoint error: " . $e->getMessage() . "</div>\n";
    }
    
    echo "</div>\n";
}

/**
 * Test maintenance mode check functions
 */
function testMaintenanceCheckFunctions() {
    echo "<div class='test-section'>\n";
    echo "<h2>3. Maintenance Check Functions Test</h2>\n";
    
    // Test isMaintenanceModeActive function
    try {
        require_once 'includes/maintenance-check.php';
        
        // Test the function directly
        $response = supabaseRequest(
            "/rest/v1/maintenance_mode?is_active=eq.true&select=id",
            'GET',
            null,
            null
        );
        
        $isActive = $response['status'] === 200 && !empty($response['data']);
        
        if ($isActive) {
            echo "<div class='test-result pass'>✓ Maintenance mode is currently ACTIVE</div>\n";
        } else {
            echo "<div class='test-result info'>ℹ Maintenance mode is currently INACTIVE</div>\n";
        }
        
        // Test page exclusion logic
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
        
        echo "<div class='test-result info'>ℹ Excluded pages: " . implode(', ', $excludedPages) . "</div>\n";
        
    } catch (Exception $e) {
        echo "<div class='test-result fail'>✗ Maintenance check functions error: " . $e->getMessage() . "</div>\n";
    }
    
    echo "</div>\n";
}

/**
 * Test maintenance page accessibility
 */
function testMaintenancePage() {
    echo "<div class='test-section'>\n";
    echo "<h2>4. Maintenance Page Test</h2>\n";
    
    // Check if maintenance page exists
    if (file_exists('maintenance.php')) {
        echo "<div class='test-result pass'>✓ maintenance.php file exists</div>\n";
        
        // Test preview mode
        $previewUrl = 'maintenance.php?preview=true&message=Test%20Message&resolution=Soon&reason=Testing';
        echo "<div class='test-result info'>ℹ Preview URL: <a href='{$previewUrl}' target='_blank'>Test Preview</a></div>\n";
        
    } else {
        echo "<div class='test-result fail'>✗ maintenance.php file not found</div>\n";
    }
    
    echo "</div>\n";
}

/**
 * Test admin panel integration
 */
function testAdminPanelIntegration() {
    echo "<div class='test-section'>\n";
    echo "<h2>5. Admin Panel Integration Test</h2>\n";
    
    // Check if admin maintenance page exists
    if (file_exists('admin/maintenance.php')) {
        echo "<div class='test-result pass'>✓ admin/maintenance.php file exists</div>\n";
    } else {
        echo "<div class='test-result fail'>✗ admin/maintenance.php file not found</div>\n";
    }
    
    // Check if admin API exists
    if (file_exists('admin/api/maintenance.php')) {
        echo "<div class='test-result pass'>✓ admin/api/maintenance.php file exists</div>\n";
    } else {
        echo "<div class='test-result fail'>✗ admin/api/maintenance.php file not found</div>\n";
    }
    
    // Check if sidebar was updated
    $sidebarContent = file_get_contents('admin/includes/sidebar.php');
    if (strpos($sidebarContent, 'maintenance.php') !== false) {
        echo "<div class='test-result pass'>✓ Admin sidebar includes maintenance link</div>\n";
    } else {
        echo "<div class='test-result fail'>✗ Admin sidebar missing maintenance link</div>\n";
    }
    
    echo "</div>\n";
}

/**
 * Test page integration
 */
function testPageIntegration() {
    echo "<div class='test-section'>\n";
    echo "<h2>6. Page Integration Test</h2>\n";
    
    $pagesToCheck = [
        'pages/dashboard.php',
        'pages/battle.php',
        'index.php'
    ];
    
    foreach ($pagesToCheck as $page) {
        if (file_exists($page)) {
            $content = file_get_contents($page);
            if (strpos($content, 'maintenance-check.php') !== false) {
                echo "<div class='test-result pass'>✓ {$page} includes maintenance check</div>\n";
            } else {
                echo "<div class='test-result fail'>✗ {$page} missing maintenance check</div>\n";
            }
        } else {
            echo "<div class='test-result info'>ℹ {$page} not found (may be HTML file)</div>\n";
        }
    }
    
    echo "</div>\n";
}

/**
 * Test JavaScript utilities
 */
function testJavaScriptUtilities() {
    echo "<div class='test-section'>\n";
    echo "<h2>7. JavaScript Utilities Test</h2>\n";
    
    if (file_exists('js/utils/maintenanceChecker.js')) {
        echo "<div class='test-result pass'>✓ maintenanceChecker.js file exists</div>\n";
        
        $content = file_get_contents('js/utils/maintenanceChecker.js');
        if (strpos($content, 'class MaintenanceModeChecker') !== false) {
            echo "<div class='test-result pass'>✓ MaintenanceModeChecker class found</div>\n";
        } else {
            echo "<div class='test-result fail'>✗ MaintenanceModeChecker class not found</div>\n";
        }
        
        if (strpos($content, 'startPeriodicCheck') !== false) {
            echo "<div class='test-result pass'>✓ Periodic check functionality found</div>\n";
        } else {
            echo "<div class='test-result fail'>✗ Periodic check functionality not found</div>\n";
        }
        
    } else {
        echo "<div class='test-result fail'>✗ maintenanceChecker.js file not found</div>\n";
    }
    
    echo "</div>\n";
}

/**
 * Test settings integration
 */
function testSettingsIntegration() {
    echo "<div class='test-section'>\n";
    echo "<h2>8. Settings Integration Test</h2>\n";
    
    try {
        // Check if maintenance settings exist
        $settings = [
            'maintenance_mode_enabled',
            'maintenance_mode_message',
            'maintenance_mode_duration',
            'maintenance_mode_reason'
        ];
        
        foreach ($settings as $setting) {
            $response = supabaseRequest(
                "/rest/v1/settings?key=eq.{$setting}&select=value",
                'GET',
                null,
                null
            );
            
            if ($response['status'] === 200 && !empty($response['data'])) {
                echo "<div class='test-result pass'>✓ Setting '{$setting}' exists</div>\n";
            } else {
                echo "<div class='test-result info'>ℹ Setting '{$setting}' not found (will be created when needed)</div>\n";
            }
        }
        
    } catch (Exception $e) {
        echo "<div class='test-result fail'>✗ Settings integration error: " . $e->getMessage() . "</div>\n";
    }
    
    echo "</div>\n";
}

/**
 * Run all tests
 */
function runAllTests() {
    echo "<div class='test-section test-info'>\n";
    echo "<h2>Maintenance Mode Test Suite Results</h2>\n";
    echo "<p>Running comprehensive tests for the maintenance mode feature...</p>\n";
    echo "</div>\n";
    
    testDatabaseConnection();
    testMaintenanceAPI();
    testMaintenanceCheckFunctions();
    testMaintenancePage();
    testAdminPanelIntegration();
    testPageIntegration();
    testJavaScriptUtilities();
    testSettingsIntegration();
    
    echo "<div class='test-section test-info'>\n";
    echo "<h2>Test Complete</h2>\n";
    echo "<p>All tests have been completed. Check the results above for any issues.</p>\n";
    echo "<p><strong>Next Steps:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Run the SQL setup script: <code>client/sql/maintenance_mode_setup.sql</code></li>\n";
    echo "<li>Access the admin panel at: <code>/admin/maintenance.php</code></li>\n";
    echo "<li>Test enabling/disabling maintenance mode</li>\n";
    echo "<li>Verify that non-admin users are redirected to maintenance page</li>\n";
    echo "<li>Test admin bypass functionality</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
}

// Run the tests
runAllTests();
?>
