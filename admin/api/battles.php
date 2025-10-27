<?php
/**
 * Battles API Endpoint
 * Handles CRUD operations for battle records management
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
$battleId = $_GET['id'] ?? null;

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action, $battleId);
            break;
        case 'DELETE':
            handleDeleteRequest($battleId);
            break;
        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGetRequest($action, $battleId) {
    switch ($action) {
        case 'list':
            getBattlesList();
            break;
        case 'count':
            getBattlesCount();
            break;
        case 'single':
            if (!$battleId) {
                throw new Exception('Battle ID required');
            }
            getSingleBattle($battleId);
            break;
        case 'stats':
            getBattleStats();
            break;
        case 'export':
            exportBattles();
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function getBattlesList() {
    $page = intval($_GET['page'] ?? 1);
    $limit = intval($_GET['limit'] ?? 20);
    $mode = $_GET['mode'] ?? '';
    $result = $_GET['result'] ?? '';
    $player = $_GET['player'] ?? '';
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    
    $offset = ($page - 1) * $limit;
    
    // Build query
    $query = "/rest/v1/battle_records?select=*";
    
    // Add filters
    $filters = [];
    
    if ($mode) {
        $filters[] = "battle_mode=eq.{$mode}";
    }
    
    if ($result) {
        $filters[] = "battle_result=eq.{$result}";
    }
    
    if ($player) {
        $filters[] = "or=(player1_name.ilike.%{$player}%,player2_name.ilike.%{$player}%)";
    }
    
    if ($dateFrom) {
        $filters[] = "start_time=gte.{$dateFrom}T00:00:00";
    }
    
    if ($dateTo) {
        $filters[] = "start_time=lte.{$dateTo}T23:59:59";
    }
    
    if (!empty($filters)) {
        $query .= "&" . implode('&', $filters);
    }
    
    // Add ordering and pagination
    $query .= "&order=start_time.desc&limit={$limit}&offset={$offset}";
    
    $response = supabaseRequest($query, 'GET', null, $_SESSION['user']['token']);
    
    if ($response['status'] === 200) {
        echo json_encode([
            'battles' => $response['data'],
            'page' => $page,
            'limit' => $limit,
            'total' => count($response['data'])
        ]);
    } else {
        throw new Exception('Failed to fetch battles');
    }
}

function getBattlesCount() {
    $response = supabaseRequest(
        "/rest/v1/battle_records?select=count",
        'GET',
        null,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 200) {
        echo json_encode(['count' => $response['data'][0]['count'] ?? 0]);
    } else {
        throw new Exception('Failed to fetch battles count');
    }
}

function getSingleBattle($battleId) {
    $response = supabaseRequest(
        "/rest/v1/battle_records?battle_id=eq.{$battleId}&select=*",
        'GET',
        null,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 200 && !empty($response['data'])) {
        echo json_encode(['battle' => $response['data'][0]]);
    } else {
        throw new Exception('Battle not found');
    }
}

function getBattleStats() {
    try {
        // Get total battles
        $totalResponse = supabaseRequest(
            "/rest/v1/battle_records?select=count",
            'GET',
            null,
            $_SESSION['user']['token']
        );
        
        // Get battles by mode
        $arenaResponse = supabaseRequest(
            "/rest/v1/battle_records?battle_mode=eq.arena&select=count",
            'GET',
            null,
            $_SESSION['user']['token']
        );
        
        $quickResponse = supabaseRequest(
            "/rest/v1/battle_records?battle_mode=eq.quick&select=count",
            'GET',
            null,
            $_SESSION['user']['token']
        );
        
        // Get battles by result
        $completedResponse = supabaseRequest(
            "/rest/v1/battle_records?battle_result=neq.Incomplete&select=count",
            'GET',
            null,
            $_SESSION['user']['token']
        );
        
        echo json_encode([
            'total_battles' => $totalResponse['data'][0]['count'] ?? 0,
            'arena_battles' => $arenaResponse['data'][0]['count'] ?? 0,
            'quick_battles' => $quickResponse['data'][0]['count'] ?? 0,
            'completed_battles' => $completedResponse['data'][0]['count'] ?? 0
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Failed to fetch battle statistics');
    }
}

function exportBattles() {
    try {
        $response = supabaseRequest(
            "/rest/v1/battle_records?select=*&order=start_time.desc",
            'GET',
            null,
            $_SESSION['user']['token']
        );
        
        if ($response['status'] === 200) {
            // Set headers for CSV download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="battle_records_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($output, [
                'Battle ID', 'Player 1', 'Player 2', 'Mode', 'Subject', 'Difficulty',
                'Result', 'Start Time', 'End Time', 'Duration (seconds)', 'Questions Count',
                'Player 1 Points', 'Player 2 Points', 'Player 1 Correct', 'Player 2 Correct'
            ]);
            
            // CSV data
            foreach ($response['data'] as $battle) {
                fputcsv($output, [
                    $battle['battle_id'],
                    $battle['player1_name'],
                    $battle['player2_name'],
                    $battle['battle_mode'],
                    $battle['subject'],
                    $battle['difficulty'],
                    $battle['battle_result'],
                    $battle['start_time'],
                    $battle['end_time'],
                    $battle['duration_seconds'],
                    $battle['questions_count'],
                    $battle['player1_final_points'],
                    $battle['player2_final_points'],
                    $battle['player1_correct_answers'],
                    $battle['player2_correct_answers']
                ]);
            }
            
            fclose($output);
            exit();
        } else {
            throw new Exception('Failed to export battles');
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleDeleteRequest($battleId) {
    if (!$battleId) {
        throw new Exception('Battle ID required');
    }
    
    $response = supabaseRequest(
        "/rest/v1/battle_records?battle_id=eq.{$battleId}",
        'DELETE',
        null,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 200 || $response['status'] === 204) {
        echo json_encode(['success' => true, 'message' => 'Battle deleted successfully']);
    } else {
        throw new Exception('Failed to delete battle');
    }
}
?>