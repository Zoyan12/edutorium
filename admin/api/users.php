<?php
/**
 * Users API Endpoint
 * Handles CRUD operations for user management
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
$userId = $_GET['id'] ?? null;

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action, $userId);
            break;
        case 'PATCH':
            handlePatchRequest($userId);
            break;
        case 'DELETE':
            handleDeleteRequest($userId);
            break;
        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGetRequest($action, $userId) {
    switch ($action) {
        case 'list':
            getUsersList();
            break;
        case 'count':
            getUsersCount();
            break;
        case 'recent':
            getRecentUsers();
            break;
        case 'active_today':
            getActiveUsersToday();
            break;
        case 'single':
            if (!$userId) {
                throw new Exception('User ID required');
            }
            getSingleUser($userId);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function getUsersList() {
    $page = intval($_GET['page'] ?? 1);
    $limit = intval($_GET['limit'] ?? 20);
    $search = $_GET['search'] ?? '';
    $field = $_GET['field'] ?? '';
    $adminOnly = $_GET['admin_only'] ?? '';
    
    $offset = ($page - 1) * $limit;
    
    // Build query (removed created_at as it doesn't exist in profiles table)
    $query = "/rest/v1/profiles?select=id,user_id,username,full_name,field,points,is_admin,is_complete";
    
    // Add filters
    $filters = [];
    
    if ($search) {
        $filters[] = "or=(username.ilike.%{$search}%,full_name.ilike.%{$search}%)";
    }
    
    if ($field) {
        $filters[] = "field=eq.{$field}";
    }
    
    if ($adminOnly === 'true') {
        $filters[] = "is_admin=eq.true";
    } elseif ($adminOnly === 'false') {
        $filters[] = "is_admin=eq.false";
    }
    
    if (!empty($filters)) {
        $query .= "&" . implode('&', $filters);
    }
    
    // Add ordering and pagination (using id instead of created_at)
    $query .= "&order=id.desc&limit={$limit}&offset={$offset}";
    
    $response = supabaseRequest($query, 'GET', null, $_SESSION['user']['token']);
    
    if ($response['status'] === 200) {
        echo json_encode([
            'users' => $response['data'],
            'page' => $page,
            'limit' => $limit,
            'total' => count($response['data'])
        ]);
    } else {
        throw new Exception('Failed to fetch users');
    }
}

function getUsersCount() {
    $response = supabaseRequest(
        "/rest/v1/profiles?select=count",
        'GET',
        null,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 200) {
        echo json_encode(['count' => $response['data'][0]['count'] ?? 0]);
    } else {
        throw new Exception('Failed to fetch users count');
    }
}

function getRecentUsers() {
    $limit = intval($_GET['limit'] ?? 10);
    
    $response = supabaseRequest(
        "/rest/v1/profiles?select=id,user_id,username,full_name,field,points&order=id.desc&limit={$limit}",
        'GET',
        null,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 200) {
        echo json_encode(['users' => $response['data']]);
    } else {
        throw new Exception('Failed to fetch recent users');
    }
}

function getActiveUsersToday() {
    // Since profiles table doesn't have created_at, we'll return total count
    // This function can be enhanced later when we have proper timestamp tracking
    $response = supabaseRequest(
        "/rest/v1/profiles?select=count",
        'GET',
        null,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 200) {
        echo json_encode(['count' => $response['data'][0]['count'] ?? 0]);
    } else {
        throw new Exception('Failed to fetch users count');
    }
}

function getSingleUser($userId) {
    $response = supabaseRequest(
        "/rest/v1/profiles?user_id=eq.{$userId}&select=*",
        'GET',
        null,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 200 && !empty($response['data'])) {
        echo json_encode(['user' => $response['data'][0]]);
    } else {
        throw new Exception('User not found');
    }
}

function handlePatchRequest($userId) {
    if (!$userId) {
        throw new Exception('User ID required');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }
    
    // Allowed fields for update
    $allowedFields = ['username', 'full_name', 'field', 'points', 'is_admin', 'is_complete'];
    $updateData = [];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updateData[$field] = $input[$field];
        }
    }
    
    if (empty($updateData)) {
        throw new Exception('No valid fields to update');
    }
    
    $response = supabaseRequest(
        "/rest/v1/profiles?user_id=eq.{$userId}",
        'PATCH',
        $updateData,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 200) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        throw new Exception('Failed to update user');
    }
}

function handleDeleteRequest($userId) {
    if (!$userId) {
        throw new Exception('User ID required');
    }
    
    $response = supabaseRequest(
        "/rest/v1/profiles?user_id=eq.{$userId}",
        'DELETE',
        null,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 200 || $response['status'] === 204) {
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        throw new Exception('Failed to delete user');
    }
}
?>
