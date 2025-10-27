<?php
/**
 * Friendships API Endpoint
 * Handles CRUD operations for friendship management
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
$friendshipId = $_GET['id'] ?? null;

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action, $friendshipId);
            break;
        case 'PATCH':
            handlePatchRequest($friendshipId);
            break;
        case 'DELETE':
            handleDeleteRequest($friendshipId);
            break;
        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGetRequest($action, $friendshipId) {
    switch ($action) {
        case 'list':
            getFriendshipsList();
            break;
        case 'count':
            getFriendshipsCount();
            break;
        case 'pending':
            getPendingRequests();
            break;
        case 'accepted':
            getAcceptedFriendships();
            break;
        case 'stats':
            getFriendshipStats();
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function getFriendshipsList() {
    $page = intval($_GET['page'] ?? 1);
    $limit = intval($_GET['limit'] ?? 20);
    $status = $_GET['status'] ?? '';
    
    $offset = ($page - 1) * $limit;
    
    // Build query with user information
    $query = "/rest/v1/friend_relationships?select=*,profiles!friend_relationships_user_id_fkey(username,full_name),profiles!friend_relationships_friend_id_fkey(username,full_name)";
    
    // Add filters
    $filters = [];
    
    if ($status) {
        $filters[] = "status=eq.{$status}";
    }
    
    if (!empty($filters)) {
        $query .= "&" . implode('&', $filters);
    }
    
    // Add ordering and pagination
    $query .= "&order=created_at.desc&limit={$limit}&offset={$offset}";
    
    $response = supabaseRequest($query, 'GET', null, $_SESSION['user']['token']);
    
    if ($response['status'] === 200) {
        echo json_encode([
            'friendships' => $response['data'],
            'page' => $page,
            'limit' => $limit,
            'total' => count($response['data'])
        ]);
    } else {
        throw new Exception('Failed to fetch friendships');
    }
}

function getFriendshipsCount() {
    $response = supabaseRequest(
        "/rest/v1/friend_relationships?select=count",
        'GET',
        null,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 200) {
        echo json_encode(['count' => $response['data'][0]['count'] ?? 0]);
    } else {
        throw new Exception('Failed to fetch friendships count');
    }
}

function getPendingRequests() {
    $response = supabaseRequest(
        "/rest/v1/friend_relationships?status=eq.pending&select=*,profiles!friend_relationships_user_id_fkey(username,full_name),profiles!friend_relationships_friend_id_fkey(username,full_name)&order=created_at.desc",
        'GET',
        null,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 200) {
        echo json_encode(['friendships' => $response['data']]);
    } else {
        throw new Exception('Failed to fetch pending requests');
    }
}

function getAcceptedFriendships() {
    $response = supabaseRequest(
        "/rest/v1/friend_relationships?status=eq.accepted&select=*,profiles!friend_relationships_user_id_fkey(username,full_name),profiles!friend_relationships_friend_id_fkey(username,full_name)&order=created_at.desc",
        'GET',
        null,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 200) {
        echo json_encode(['friendships' => $response['data']]);
    } else {
        throw new Exception('Failed to fetch accepted friendships');
    }
}

function getFriendshipStats() {
    try {
        // Get total friendships
        $totalResponse = supabaseRequest(
            "/rest/v1/friend_relationships?select=count",
            'GET',
            null,
            $_SESSION['user']['token']
        );
        
        // Get pending requests
        $pendingResponse = supabaseRequest(
            "/rest/v1/friend_relationships?status=eq.pending&select=count",
            'GET',
            null,
            $_SESSION['user']['token']
        );
        
        // Get accepted friendships
        $acceptedResponse = supabaseRequest(
            "/rest/v1/friend_relationships?status=eq.accepted&select=count",
            'GET',
            null,
            $_SESSION['user']['token']
        );
        
        echo json_encode([
            'total_friendships' => $totalResponse['data'][0]['count'] ?? 0,
            'pending_requests' => $pendingResponse['data'][0]['count'] ?? 0,
            'accepted_friendships' => $acceptedResponse['data'][0]['count'] ?? 0
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Failed to fetch friendship statistics');
    }
}

function handlePatchRequest($friendshipId) {
    if (!$friendshipId) {
        throw new Exception('Friendship ID required');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }
    
    // Allowed fields for update
    $allowedFields = ['status'];
    $updateData = [];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updateData[$field] = $input[$field];
        }
    }
    
    if (empty($updateData)) {
        throw new Exception('No valid fields to update');
    }
    
    // Validate status
    if (isset($updateData['status']) && !in_array($updateData['status'], ['pending', 'accepted'])) {
        throw new Exception('Status must be pending or accepted');
    }
    
    $response = supabaseRequest(
        "/rest/v1/friend_relationships?id=eq.{$friendshipId}",
        'PATCH',
        $updateData,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 200) {
        echo json_encode(['success' => true, 'message' => 'Friendship updated successfully']);
    } else {
        throw new Exception('Failed to update friendship');
    }
}

function handleDeleteRequest($friendshipId) {
    if (!$friendshipId) {
        throw new Exception('Friendship ID required');
    }
    
    $response = supabaseRequest(
        "/rest/v1/friend_relationships?id=eq.{$friendshipId}",
        'DELETE',
        null,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 200 || $response['status'] === 204) {
        echo json_encode(['success' => true, 'message' => 'Friendship deleted successfully']);
    } else {
        throw new Exception('Failed to delete friendship');
    }
}
?>