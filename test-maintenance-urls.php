<?php
/**
 * Test Maintenance URL Construction
 * This script tests the maintenance URL construction logic
 */

echo "<h1>Maintenance URL Construction Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .success { background: #d4edda; border-color: #c3e6cb; }
    .error { background: #f8d7da; border-color: #f5c6cb; }
    .info { background: #d1ecf1; border-color: #bee5eb; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>";

// Test different scenarios
$testScenarios = [
    '/edutorium/client/pages/dashboard.php' => '/edutorium/client/maintenance.php',
    '/edutorium/client/pages/battle.php' => '/edutorium/client/maintenance.php',
    '/edutorium/client/admin/maintenance.php' => '/edutorium/client/maintenance.php',
    '/edutorium/client/admin/users.php' => '/edutorium/client/maintenance.php',
    '/edutorium/client/index.php' => '/edutorium/client/maintenance.php',
    '/edutorium/client/maintenance.php' => '/edutorium/client/maintenance.php',
    '/pages/dashboard.php' => '/maintenance.php',
    '/admin/maintenance.php' => '/maintenance.php',
    '/index.php' => '/maintenance.php',
    '/maintenance.php' => '/maintenance.php',
    '/dashboard.php' => '/maintenance.php'
];

echo "<div class='test-section info'>";
echo "<h2>Testing URL Construction Logic</h2>";
echo "<p>This test simulates the maintenance URL construction for different page locations.</p>";
echo "</div>";

foreach ($testScenarios as $currentScript => $expectedUrl) {
    echo "<div class='test-section'>";
    echo "<h3>Test Case: {$currentScript}</h3>";
    
    // Simulate the maintenance check logic
    $maintenanceUrl = '/maintenance.php';
    
    // Get the correct base path based on current location
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
    
    $isCorrect = ($maintenanceUrl === $expectedUrl);
    $statusClass = $isCorrect ? 'success' : 'error';
    $statusIcon = $isCorrect ? '✅' : '❌';
    
    echo "<div class='{$statusClass}'>";
    echo "<p><strong>{$statusIcon} Result:</strong></p>";
    echo "<p><strong>Current Script:</strong> {$currentScript}</p>";
    echo "<p><strong>Base Path:</strong> {$basePath}</p>";
    echo "<p><strong>Generated URL:</strong> {$maintenanceUrl}</p>";
    echo "<p><strong>Expected URL:</strong> {$expectedUrl}</p>";
    echo "<p><strong>Status:</strong> " . ($isCorrect ? 'CORRECT' : 'INCORRECT') . "</p>";
    echo "</div>";
    echo "</div>";
}

// Test with actual server variables
echo "<div class='test-section info'>";
echo "<h2>Current Server Environment</h2>";
echo "<p><strong>SCRIPT_NAME:</strong> " . ($_SERVER['SCRIPT_NAME'] ?? 'Not set') . "</p>";
echo "<p><strong>REQUEST_URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "</p>";
echo "<p><strong>HTTP_HOST:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "</p>";

// Test the actual logic with current environment
$currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
$pathParts = explode('/', trim($currentScript, '/'));
$basePath = '';
if (count($pathParts) >= 2) {
    array_pop($pathParts);
    $basePath = '/' . implode('/', $pathParts);
}

$maintenanceUrl = '/maintenance.php';
if ($basePath && $basePath !== '/') {
    $maintenanceUrl = $basePath . $maintenanceUrl;
}

echo "<p><strong>Calculated Base Path:</strong> {$basePath}</p>";
echo "<p><strong>Maintenance URL:</strong> {$maintenanceUrl}</p>";
echo "</div>";

// Test JavaScript logic
echo "<div class='test-section info'>";
echo "<h2>JavaScript URL Construction Test</h2>";
echo "<button onclick='testJavaScriptURLConstruction()'>Test JavaScript Logic</button>";
echo "<div id='js-results'></div>";
echo "</div>";

echo "<script>
function testJavaScriptURLConstruction() {
    const resultsDiv = document.getElementById('js-results');
    resultsDiv.innerHTML = '<p>Testing...</p>';
    
    const testCases = [
        '/edutorium/client/pages/dashboard.php',
        '/edutorium/client/pages/battle.php',
        '/edutorium/client/index.php',
        '/pages/dashboard.php',
        '/index.php'
    ];
    
    let results = '<h3>JavaScript Test Results:</h3>';
    
    testCases.forEach(currentPath => {
        const pathParts = currentPath.split('/').filter(part => part !== '');
        let basePath = '';
        if (pathParts.length >= 2) {
            // Remove the last part (the actual file)
            const baseParts = pathParts.slice(0, -1);
            
            // For pages in subdirectories like /pages/, we need to go up one more level
            // Check if the last remaining part is a common subdirectory
            const commonSubdirs = ['pages', 'admin', 'api', 'css', 'js', 'img', 'assets', 'vendor', 'includes', 'sql', 'docs', 'monitoring', 'nginx', 'utils', 'src'];
            
            if (baseParts.length > 0 && commonSubdirs.includes(baseParts[baseParts.length - 1])) {
                // Remove the subdirectory as well
                baseParts.pop();
            }
            
            basePath = '/' + baseParts.join('/');
        }
        
        let maintenanceUrl = '/maintenance.php';
        if (basePath && basePath !== '/') {
            maintenanceUrl = basePath + maintenanceUrl;
        }
        
        results += \`
            <div style='margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 4px;'>
                <strong>Path:</strong> \${currentPath}<br>
                <strong>Base Path:</strong> \${basePath}<br>
                <strong>Maintenance URL:</strong> \${maintenanceUrl}
            </div>
        \`;
    });
    
    resultsDiv.innerHTML = results;
}
</script>";

echo "<div class='test-section info'>";
echo "<h2>Quick Links</h2>";
echo "<ul>";
echo "<li><a href='../admin/maintenance.php' target='_blank'>Admin Maintenance Panel</a></li>";
echo "<li><a href='../maintenance.php' target='_blank'>Maintenance Page</a></li>";
echo "<li><a href='../pages/dashboard.php' target='_blank'>Dashboard</a></li>";
echo "<li><a href='../debug-maintenance.php' target='_blank'>Debug Maintenance</a></li>";
echo "</ul>";
echo "</div>";
?>
