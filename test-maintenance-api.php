<?php
/**
 * Test Maintenance Status API
 * Simple test page to verify the maintenance status API is working
 */

echo "<h1>Maintenance Status API Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .success { background: #d4edda; border-color: #c3e6cb; }
    .error { background: #f8d7da; border-color: #f5c6cb; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>";

// Test the API endpoint
echo "<div class='test-section'>";
echo "<h2>Testing Maintenance Status API</h2>";

$apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/maintenance-status.php';
echo "<p><strong>API URL:</strong> <a href='{$apiUrl}' target='_blank'>{$apiUrl}</a></p>";

// Test with cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<div class='error'>";
    echo "<h3>cURL Error:</h3>";
    echo "<p>{$error}</p>";
    echo "</div>";
} else {
    echo "<div class='success'>";
    echo "<h3>API Response (HTTP {$httpCode}):</h3>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    // Try to decode JSON
    $data = json_decode($response, true);
    if ($data) {
        echo "<h3>Parsed Data:</h3>";
        $prettyJson = json_encode($data, JSON_PRETTY_PRINT);
        echo "<pre>" . htmlspecialchars($prettyJson) . "</pre>";
        
        if (isset($data['is_active']) && $data['is_active']) {
            echo "<p style='color: #e74c3c; font-weight: bold;'>⚠️ Maintenance Mode is ACTIVE</p>";
        } else {
            echo "<p style='color: #27ae60; font-weight: bold;'>✅ Maintenance Mode is INACTIVE</p>";
        }
    } else {
        echo "<p style='color: #f39c12;'>⚠️ Response is not valid JSON</p>";
    }
    echo "</div>";
}

echo "</div>";

// Test JavaScript fetch
echo "<div class='test-section'>";
echo "<h2>JavaScript Test</h2>";
echo "<button onclick='testMaintenanceAPI()'>Test API with JavaScript</button>";
echo "<div id='js-result'></div>";
echo "</div>";

echo "<script>
async function testMaintenanceAPI() {
    const resultDiv = document.getElementById('js-result');
    resultDiv.innerHTML = '<p>Testing...</p>';
    
    try {
        const response = await fetch('api/maintenance-status.php');
        const data = await response.json();
        
        resultDiv.innerHTML = `
            <div class='success'>
                <h3>JavaScript API Test Result:</h3>
                <pre>\${JSON.stringify(data, null, 2)}</pre>
                <p>Status: \${data.is_active ? '⚠️ ACTIVE' : '✅ INACTIVE'}</p>
            </div>
        `;
    } catch (error) {
        resultDiv.innerHTML = `
            <div class='error'>
                <h3>JavaScript Error:</h3>
                <p>\${error.message}</p>
            </div>
        `;
    }
}
</script>";

echo "<div class='test-section'>";
echo "<h2>Quick Links</h2>";
echo "<ul>";
echo "<li><a href='../admin/maintenance.php' target='_blank'>Admin Maintenance Panel</a></li>";
echo "<li><a href='../maintenance.php' target='_blank'>Maintenance Page</a></li>";
echo "<li><a href='../pages/dashboard.php' target='_blank'>Dashboard</a></li>";
echo "<li><a href='../debug-maintenance.php' target='_blank'>Debug Maintenance</a></li>";
echo "</ul>";
echo "</div>";
?>