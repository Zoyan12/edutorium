<?php
/**
 * Settings API Endpoint
 * Handles CRUD operations for system settings management
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
$action = $_GET['action'] ?? 'list';
$settingId = $_GET['id'] ?? null;

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action, $settingId);
            break;
        case 'POST':
            handlePostRequest();
            break;
        case 'PATCH':
            handlePatchRequest($settingId);
            break;
        case 'DELETE':
            handleDeleteRequest($settingId);
            break;
        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGetRequest($action, $settingId) {
    switch ($action) {
        case 'list':
            getSettingsList();
            break;
        case 'count':
            getSettingsCount();
            break;
        case 'single':
            if (!$settingId) {
                throw new Exception('Setting ID required');
            }
            getSingleSetting($settingId);
            break;
        case 'key':
            $key = $_GET['key'] ?? '';
            if (!$key) {
                throw new Exception('Setting key required');
            }
            getSettingByKey($key);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function getSettingsList() {
    $page = intval($_GET['page'] ?? 1);
    $limit = intval($_GET['limit'] ?? 20);
    $search = $_GET['search'] ?? '';
    
    $offset = ($page - 1) * $limit;
    
    // Build query
    $query = "/rest/v1/settings?select=*";
    
    // Add filters
    $filters = [];
    
    if ($search) {
        $filters[] = "or=(key.ilike.%{$search}%,description.ilike.%{$search}%)";
    }
    
    if (!empty($filters)) {
        $query .= "&" . implode('&', $filters);
    }
    
    // Add ordering and pagination
    $query .= "&order=key.asc&limit={$limit}&offset={$offset}";
    
    $response = supabaseRequest($query, 'GET', null, $_SESSION['user']['token']);
    
    if ($response['status'] === 200) {
        echo json_encode([
            'settings' => $response['data'],
            'page' => $page,
            'limit' => $limit,
            'total' => count($response['data'])
        ]);
    } else {
        throw new Exception('Failed to fetch settings');
    }
}

function getSettingsCount() {
    $response = supabaseRequest(
        "/rest/v1/settings?select=count",
        'GET',
        null,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 200) {
        echo json_encode(['count' => $response['data'][0]['count'] ?? 0]);
    } else {
        throw new Exception('Failed to fetch settings count');
    }
}

function getSingleSetting($settingId) {
    $response = supabaseRequest(
        "/rest/v1/settings?id=eq.{$settingId}&select=*",
        'GET',
        null,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 200 && !empty($response['data'])) {
        echo json_encode(['setting' => $response['data'][0]]);
    } else {
        throw new Exception('Setting not found');
    }
}

function getSettingByKey($key) {
    $response = supabaseRequest(
        "/rest/v1/settings?key=eq.{$key}&select=*",
        'GET',
        null,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 200 && !empty($response['data'])) {
        echo json_encode(['setting' => $response['data'][0]]);
    } else {
        throw new Exception('Setting not found');
    }
}

function handlePostRequest() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }
    
    // Required fields
    $requiredFields = ['key', 'value'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            throw new Exception("Field '{$field}' is required");
        }
    }
    
    $settingData = [
        'key' => $input['key'],
        'value' => $input['value'],
        'description' => $input['description'] ?? null
    ];
    
    $response = supabaseRequest(
        "/rest/v1/settings",
        'POST',
        $settingData,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 201) {
        echo json_encode(['success' => true, 'message' => 'Setting created successfully', 'setting' => $response['data'][0]]);
    } else {
        throw new Exception('Failed to create setting');
    }
}

function handlePatchRequest($settingId) {
    if (!$settingId) {
        throw new Exception('Setting ID required');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }
    
    // Allowed fields for update
    $allowedFields = ['key', 'value', 'description'];
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
        "/rest/v1/settings?id=eq.{$settingId}",
        'PATCH',
        $updateData,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 200) {
        echo json_encode(['success' => true, 'message' => 'Setting updated successfully']);
    } else {
        throw new Exception('Failed to update setting');
    }
}

function handleDeleteRequest($settingId) {
    if (!$settingId) {
        throw new Exception('Setting ID required');
    }
    
    $response = supabaseRequest(
        "/rest/v1/settings?id=eq.{$settingId}",
        'DELETE',
        null,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 200 || $response['status'] === 204) {
        echo json_encode(['success' => true, 'message' => 'Setting deleted successfully']);
    } else {
        throw new Exception('Failed to delete setting');
    }
}
?>