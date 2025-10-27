<?php
/**
 * Maintenance Mode API Endpoint
 * Handles CRUD operations for maintenance mode management
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include authentication check
require_once '../includes/auth-check.php';

// Include functions
require_once '../includes/functions.php';

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'status';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action);
            break;
        case 'POST':
            handlePostRequest();
            break;
        case 'PATCH':
            handlePatchRequest();
            break;
        case 'DELETE':
            handleDeleteRequest();
            break;
        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGetRequest($action) {
    switch ($action) {
        case 'status':
            getMaintenanceStatus();
            break;
        case 'history':
            getMaintenanceHistory();
            break;
        case 'current':
            getCurrentMaintenance();
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function getMaintenanceStatus() {
    try {
        // Get current maintenance status
        $response = supabaseRequest(
            "/rest/v1/maintenance_mode?is_active=eq.true&select=*&order=start_time.desc&limit=1",
            'GET',
            null,
            $_SESSION['user']['token'] ?? null
        );
        
        if ($response['status'] === 200) {
            $isActive = !empty($response['data']);
            $maintenanceData = $isActive ? $response['data'][0] : null;
            
            echo json_encode([
                'is_active' => $isActive,
                'maintenance' => $maintenanceData,
                'timestamp' => date('c')
            ]);
        } else {
            throw new Exception('Failed to fetch maintenance status');
        }
    } catch (Exception $e) {
        throw new Exception('Error checking maintenance status: ' . $e->getMessage());
    }
}

function getMaintenanceHistory() {
    try {
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        
        $response = supabaseRequest(
            "/rest/v1/maintenance_mode?select=*,profiles:started_by(username,full_name)&order=start_time.desc&limit={$limit}&offset={$offset}",
            'GET',
            null,
            $_SESSION['user']['token'] ?? null
        );
        
        if ($response['status'] === 200) {
            echo json_encode([
                'history' => $response['data'],
                'page' => $page,
                'limit' => $limit
            ]);
        } else {
            throw new Exception('Failed to fetch maintenance history');
        }
    } catch (Exception $e) {
        throw new Exception('Error fetching maintenance history: ' . $e->getMessage());
    }
}

function getCurrentMaintenance() {
    try {
        $response = supabaseRequest(
            "/rest/v1/maintenance_mode?is_active=eq.true&select=*&order=start_time.desc&limit=1",
            'GET',
            null,
            null // No auth needed for public access
        );
        
        if ($response['status'] === 200 && !empty($response['data'])) {
            echo json_encode([
                'maintenance' => $response['data'][0],
                'timestamp' => date('c')
            ]);
        } else {
            echo json_encode([
                'maintenance' => null,
                'timestamp' => date('c')
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'maintenance' => null,
            'error' => $e->getMessage(),
            'timestamp' => date('c')
        ]);
    }
}

function handlePostRequest() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }
    
    // Required fields
    $requiredFields = ['reason', 'user_message', 'expected_resolution'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            throw new Exception("Field '{$field}' is required");
        }
    }
    
    // Get current user
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
        throw new Exception('Authentication required');
    }
    
    $userId = $_SESSION['user']['id'];
    $duration = intval($input['duration_minutes'] ?? 60);
    
    // First, disable any existing maintenance mode
    supabaseRequest(
        "/rest/v1/maintenance_mode",
        'PATCH',
        ['is_active' => false],
        $_SESSION['user']['token']
    );
    
    // Create new maintenance mode record
    $maintenanceData = [
        'is_active' => true,
        'reason' => $input['reason'],
        'user_message' => $input['user_message'],
        'expected_resolution' => $input['expected_resolution'],
        'duration_minutes' => $duration,
        'started_by' => $userId
    ];
    
    $response = supabaseRequest(
        "/rest/v1/maintenance_mode",
        'POST',
        $maintenanceData,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 201) {
        // Update settings table
        updateMaintenanceSettings($input);
        
        echo json_encode([
            'success' => true,
            'message' => 'Maintenance mode enabled successfully',
            'maintenance' => $response['data'][0]
        ]);
    } else {
        throw new Exception('Failed to enable maintenance mode');
    }
}

function handlePatchRequest() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }
    
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'disable':
            disableMaintenanceMode();
            break;
        case 'update':
            updateMaintenanceMode($input);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function disableMaintenanceMode() {
    try {
        $response = supabaseRequest(
            "/rest/v1/maintenance_mode",
            'PATCH',
            ['is_active' => false],
            $_SESSION['user']['token']
        );
        
        if ($response['status'] === 200) {
            echo json_encode([
                'success' => true,
                'message' => 'Maintenance mode disabled successfully'
            ]);
        } else {
            throw new Exception('Failed to disable maintenance mode');
        }
    } catch (Exception $e) {
        throw new Exception('Error disabling maintenance mode: ' . $e->getMessage());
    }
}

function updateMaintenanceMode($input) {
    try {
        $allowedFields = ['reason', 'user_message', 'expected_resolution', 'duration_minutes'];
        $updateData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateData[$field] = $input[$field];
            }
        }
        
        if (empty($updateData)) {
            throw new Exception('No valid fields to update');
        }
        
        $updateData['updated_at'] = date('c');
        
        $response = supabaseRequest(
            "/rest/v1/maintenance_mode?is_active=eq.true",
            'PATCH',
            $updateData,
            $_SESSION['user']['token']
        );
        
        if ($response['status'] === 200) {
            // Update settings table
            updateMaintenanceSettings($input);
            
            echo json_encode([
                'success' => true,
                'message' => 'Maintenance mode updated successfully'
            ]);
        } else {
            throw new Exception('Failed to update maintenance mode');
        }
    } catch (Exception $e) {
        throw new Exception('Error updating maintenance mode: ' . $e->getMessage());
    }
}

function handleDeleteRequest() {
    try {
        $response = supabaseRequest(
            "/rest/v1/maintenance_mode?is_active=eq.true",
            'DELETE',
            null,
            $_SESSION['user']['token']
        );
        
        if ($response['status'] === 200 || $response['status'] === 204) {
            echo json_encode([
                'success' => true,
                'message' => 'Maintenance mode disabled successfully'
            ]);
        } else {
            throw new Exception('Failed to disable maintenance mode');
        }
    } catch (Exception $e) {
        throw new Exception('Error disabling maintenance mode: ' . $e->getMessage());
    }
}

function updateMaintenanceSettings($input) {
    try {
        $settings = [
            'maintenance_mode_message' => $input['user_message'] ?? null,
            'maintenance_mode_duration' => isset($input['duration_minutes']) ? $input['duration_minutes'] : null,
            'maintenance_mode_reason' => $input['reason'] ?? null
        ];
        
        foreach ($settings as $key => $value) {
            if ($value !== null) {
                // Check if setting exists
                $existingResponse = supabaseRequest(
                    "/rest/v1/settings?key=eq.{$key}&select=id",
                    'GET',
                    null,
                    $_SESSION['user']['token']
                );
                
                if ($existingResponse['status'] === 200 && !empty($existingResponse['data'])) {
                    // Update existing setting
                    supabaseRequest(
                        "/rest/v1/settings?key=eq.{$key}",
                        'PATCH',
                        ['value' => $value, 'updated_at' => date('c')],
                        $_SESSION['user']['token']
                    );
                } else {
                    // Create new setting
                    supabaseRequest(
                        "/rest/v1/settings",
                        'POST',
                        [
                            'key' => $key,
                            'value' => $value,
                            'description' => "Maintenance mode setting for {$key}"
                        ],
                        $_SESSION['user']['token']
                    );
                }
            }
        }
    } catch (Exception $e) {
        // Log error but don't fail the main operation
        error_log('Error updating maintenance settings: ' . $e->getMessage());
    }
}
?>
