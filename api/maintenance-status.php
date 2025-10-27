<?php
/**
 * Public Maintenance Status API
 * Provides maintenance status without requiring authentication
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include necessary files
require_once '../includes/config.php';
require_once '../includes/functions.php';

try {
    // Get current maintenance status
    $response = supabaseRequest(
        "/rest/v1/maintenance_mode?is_active=eq.true&select=*&order=start_time.desc&limit=1",
        'GET',
        null,
        null
    );
    
    if ($response['status'] === 200) {
        $isActive = !empty($response['data']);
        $maintenanceData = $isActive ? $response['data'][0] : null;
        
        echo json_encode([
            'is_active' => $isActive,
            'maintenance' => $maintenanceData,
            'timestamp' => date('c'),
            'status' => 'success'
        ]);
    } else {
        echo json_encode([
            'is_active' => false,
            'maintenance' => null,
            'timestamp' => date('c'),
            'status' => 'error',
            'message' => 'Failed to fetch maintenance status'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'is_active' => false,
        'maintenance' => null,
        'timestamp' => date('c'),
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
