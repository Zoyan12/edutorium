<?php
/**
 * Questions API Endpoint
 * Handles CRUD operations for question management
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
$questionId = $_GET['id'] ?? null;

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action, $questionId);
            break;
        case 'POST':
            handlePostRequest();
            break;
        case 'PATCH':
            handlePatchRequest($questionId);
            break;
        case 'DELETE':
            handleDeleteRequest($questionId);
            break;
        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGetRequest($action, $questionId) {
    switch ($action) {
        case 'list':
            getQuestionsList();
            break;
        case 'count':
            getQuestionsCount();
            break;
        case 'single':
            if (!$questionId) {
                throw new Exception('Question ID required');
            }
            getSingleQuestion($questionId);
            break;
        case 'subjects':
            getSubjects();
            break;
        case 'difficulties':
            getDifficulties();
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function getQuestionsList() {
    $page = intval($_GET['page'] ?? 1);
    $limit = intval($_GET['limit'] ?? 20);
    $subject = $_GET['subject'] ?? '';
    $difficulty = $_GET['difficulty'] ?? '';
    
    $offset = ($page - 1) * $limit;
    
    // Build query
    $query = "/rest/v1/questions?select=id,image_url,correct_answer,subject,difficulty,created_by,created_at";
    
    // Add filters
    $filters = [];
    
    if ($subject) {
        $filters[] = "subject=eq.{$subject}";
    }
    
    if ($difficulty) {
        $filters[] = "difficulty=eq.{$difficulty}";
    }
    
    if (!empty($filters)) {
        $query .= "&" . implode('&', $filters);
    }
    
    // Add ordering and pagination
    $query .= "&order=created_at.desc&limit={$limit}&offset={$offset}";
    
    $response = supabaseRequest($query, 'GET', null, $_SESSION['user']['token']);
    
    if ($response['status'] === 200) {
        echo json_encode([
            'questions' => $response['data'],
            'page' => $page,
            'limit' => $limit,
            'total' => count($response['data'])
        ]);
    } else {
        throw new Exception('Failed to fetch questions');
    }
}

function getQuestionsCount() {
    $response = supabaseRequest(
        "/rest/v1/questions?select=count",
        'GET',
        null,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 200) {
        echo json_encode(['count' => $response['data'][0]['count'] ?? 0]);
    } else {
        throw new Exception('Failed to fetch questions count');
    }
}

function getSingleQuestion($questionId) {
    $response = supabaseRequest(
        "/rest/v1/questions?id=eq.{$questionId}&select=*",
        'GET',
        null,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 200 && !empty($response['data'])) {
        echo json_encode(['question' => $response['data'][0]]);
    } else {
        throw new Exception('Question not found');
    }
}

function getSubjects() {
    $response = supabaseRequest(
        "/rest/v1/questions?select=subject&subject=not.is.null",
        'GET',
        null,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 200) {
        $subjects = array_unique(array_column($response['data'], 'subject'));
        sort($subjects);
        echo json_encode(['subjects' => $subjects]);
    } else {
        throw new Exception('Failed to fetch subjects');
    }
}

function getDifficulties() {
    echo json_encode(['difficulties' => ['easy', 'medium', 'hard']]);
}

function handlePostRequest() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }
    
    // Required fields
    $requiredFields = ['image_url', 'correct_answer', 'subject', 'difficulty'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            throw new Exception("Field '{$field}' is required");
        }
    }
    
    // Validate correct_answer
    if (!in_array($input['correct_answer'], ['A', 'B', 'C', 'D'])) {
        throw new Exception('Correct answer must be A, B, C, or D');
    }
    
    // Validate difficulty
    if (!in_array($input['difficulty'], ['easy', 'medium', 'hard'])) {
        throw new Exception('Difficulty must be easy, medium, or hard');
    }
    
    $questionData = [
        'image_url' => $input['image_url'],
        'correct_answer' => $input['correct_answer'],
        'subject' => $input['subject'],
        'difficulty' => $input['difficulty'],
        'created_by' => $_SESSION['user']['id']
    ];
    
    $response = supabaseRequest(
        "/rest/v1/questions",
        'POST',
        $questionData,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 201) {
        echo json_encode(['success' => true, 'message' => 'Question created successfully', 'question' => $response['data'][0]]);
    } else {
        throw new Exception('Failed to create question');
    }
}

function handlePatchRequest($questionId) {
    if (!$questionId) {
        throw new Exception('Question ID required');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }
    
    // Allowed fields for update
    $allowedFields = ['image_url', 'correct_answer', 'subject', 'difficulty'];
    $updateData = [];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updateData[$field] = $input[$field];
        }
    }
    
    if (empty($updateData)) {
        throw new Exception('No valid fields to update');
    }
    
    // Validate correct_answer if provided
    if (isset($updateData['correct_answer']) && !in_array($updateData['correct_answer'], ['A', 'B', 'C', 'D'])) {
        throw new Exception('Correct answer must be A, B, C, or D');
    }
    
    // Validate difficulty if provided
    if (isset($updateData['difficulty']) && !in_array($updateData['difficulty'], ['easy', 'medium', 'hard'])) {
        throw new Exception('Difficulty must be easy, medium, or hard');
    }
    
    $updateData['updated_at'] = date('c');
    
    $response = supabaseRequest(
        "/rest/v1/questions?id=eq.{$questionId}",
        'PATCH',
        $updateData,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 200) {
        echo json_encode(['success' => true, 'message' => 'Question updated successfully']);
    } else {
        throw new Exception('Failed to update question');
    }
}

function handleDeleteRequest($questionId) {
    if (!$questionId) {
        throw new Exception('Question ID required');
    }
    
    $response = supabaseRequest(
        "/rest/v1/questions?id=eq.{$questionId}",
        'DELETE',
        null,
        $_SESSION['user']['token']
    );
    
    if ($response['status'] === 200 || $response['status'] === 204) {
        echo json_encode(['success' => true, 'message' => 'Question deleted successfully']);
    } else {
        throw new Exception('Failed to delete question');
    }
}
?>