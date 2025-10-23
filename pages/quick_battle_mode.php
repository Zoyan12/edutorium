<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

use Supabase\Client;

// Debug session variables
error_log("Session ID: " . session_id());
error_log("Session Variables: " . print_r($_SESSION, true));

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['access_token'])) {
    error_log("User not logged in - Redirecting to login");
    header('Location: ../login.html');
    exit();
}

// Extract user ID from the access token
$access_token = $_SESSION['user']['access_token'];
$token_parts = explode('.', $access_token);
$payload = json_decode(base64_decode($token_parts[1]), true);
$user_id = $payload['sub'] ?? null;

if (!$user_id) {
    error_log("Could not extract user ID from token - Redirecting to login");
    header('Location: ../login.html');
    exit();
}

error_log("User ID from token: " . $user_id);

// Initialize Supabase client
$supabase = new Client(
    SUPABASE_URL,
    SUPABASE_ANON_KEY,
    [
        'auth' => [
            'autoRefreshToken' => true,
            'persistSession' => true,
            'detectSessionInUrl' => true
        ]
    ]
);

// Check if user has an active quick battle
$battle_id = isset($_GET['battle_id']) ? $_GET['battle_id'] : null;

error_log("Battle ID from GET: " . $battle_id);

if (!$battle_id) {
    // Try to find an active quick battle for the user
    $battle_query = $supabase->from('battle_records')
        ->select('id')
        ->eq('player1_id', $user_id)
        ->eq('battle_mode', 'quick')
        ->eq('status', 'in_progress')
        ->order('created_at', 'desc')
        ->limit(1)
        ->execute();

    error_log("Battle query result: " . print_r($battle_query, true));

    if ($battle_query->data) {
        $battle_id = $battle_query->data[0]['id'];
        error_log("Found battle ID: " . $battle_id);
    } else {
        error_log("No active battle found - Redirecting to lobby");
        header('Location: ../quick_battle_lobby.php');
        exit();
    }
}

// Verify the battle exists and user is a participant
$battle_query = $supabase->from('battle_records')
    ->select('*')
    ->eq('id', $battle_id)
    ->eq('battle_mode', 'quick')
    ->in('status', ['in_progress', 'waiting'])
    ->execute();

if (!$battle_query->data) {
    // Battle not found or not in correct status
    header('Location: ../quick_battle_lobby.php');
    exit();
}

$battle = $battle_query->data[0];

// Verify user is a participant in this battle
if ($battle['player1_id'] !== $user_id && $battle['player2_id'] !== $user_id) {
    // User is not a participant in this battle
    header('Location: ../quick_battle_lobby.php');
    exit();
}

// Get opponent's information
$opponent_id = $battle['player1_id'] === $user_id ? $battle['player2_id'] : $battle['player1_id'];
$opponent_query = $supabase->from('profiles')
    ->select('username, avatar_url')
    ->eq('id', $opponent_id)
    ->execute();

$opponent = $opponent_query->data[0] ?? null;

// Get current user's information
$user_query = $supabase->from('profiles')
    ->select('username, avatar_url')
    ->eq('id', $user_id)
    ->execute();

$user = $user_query->data[0] ?? null;

// Set up page variables
$page_title = "Quick Battle Mode";
$is_player1 = $battle['player1_id'] === $user_id;
$player1_score = $battle['player1_score'] ?? 0;
$player2_score = $battle['player2_score'] ?? 0;
$current_question = $battle['current_question'] ?? 1;
$total_questions = $battle['total_questions'] ?? 10;
$time_per_question = $battle['time_per_question'] ?? 15;
$battle_status = $battle['status'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Edutorium</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://unpkg.com/@supabase/supabase-js@2"></script>
    <script>
        // Initialize Supabase client
        const supabaseUrl = '<?php echo SUPABASE_URL; ?>';
        const supabaseKey = '<?php echo SUPABASE_ANON_KEY; ?>';
        const supabase = supabase.createClient(supabaseUrl, supabaseKey);

        // Set up auth state change listener
        supabase.auth.onAuthStateChange((event, session) => {
            if (event === 'SIGNED_OUT') {
                window.location.href = '../login.html';
            }
        });

        // Initialize battle UI
        document.addEventListener('DOMContentLoaded', () => {
            // Set initial values from PHP variables
            document.getElementById('player1-score').textContent = <?php echo $player1_score; ?>;
            document.getElementById('player2-score').textContent = <?php echo $player2_score; ?>;
            document.getElementById('current-question').textContent = <?php echo $current_question; ?>;
            document.getElementById('total-questions').textContent = <?php echo $total_questions; ?>;
            
            // Set player names and avatars
            document.querySelector('.player1-name').textContent = '<?php echo htmlspecialchars($user['username'] ?? 'Player 1'); ?>';
            document.querySelector('.player2-name').textContent = '<?php echo htmlspecialchars($opponent['username'] ?? 'Player 2'); ?>';
            document.querySelector('.player1-avatar img').src = '<?php echo htmlspecialchars($user['avatar_url'] ?? 'https://ui-avatars.com/api/?name=Player+1&background=random'); ?>';
            document.querySelector('.player2-avatar img').src = '<?php echo htmlspecialchars($opponent['avatar_url'] ?? 'https://ui-avatars.com/api/?name=Player+2&background=random'); ?>';
        });
    </script>
    <style>
        :root {
            --primary-color: #4CAF50;
            --secondary-color: #45a049;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --text-color: #333;
            --bg-color: #f5f5f5;
            --dark-color: #343a40;
            --light-color: #f8f9fa;
            --border-color: #e9ecef;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 8px rgba(0,0,0,0.1);
            --shadow-lg: 0 8px 16px rgba(0,0,0,0.1);
            --transition-speed: 0.3s;
            --border-radius: 10px;
            --card-border-radius: 15px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
            transition: background-color 0.3s ease, color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }
        
        /* Animation Keyframes */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        @keyframes gradientAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Header */
        .battle-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            background-size: 200% 200%;
            animation: gradientAnimation 10s ease infinite;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
            z-index: 10;
            text-align: center;
            box-shadow: var(--shadow-md);
        }
        
        .battle-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://www.transparenttextures.com/patterns/cubes.png');
            opacity: 0.1;
            z-index: -1;
        }
        
        .battle-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.7), transparent);
        }
        
        .header-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            position: relative;
        }
        
        .battle-type-badge {
            position: absolute;
            top: -35px;
            right: -60px;
            background: var(--warning-color);
            color: #333;
            font-size: 0.7rem;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: bold;
            transform: rotate(25deg);
            box-shadow: var(--shadow-sm);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .battle-header h1 {
            font-size: 2rem;
            margin: 0;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 15px;
            animation: fadeIn 0.6s ease-out;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        
        .battle-header h1 i {
            color: var(--warning-color);
            font-size: 1.8rem;
            filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.3));
            animation: pulseIcon 2s infinite;
        }
        
        @keyframes pulseIcon {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.4)); }
            100% { transform: scale(1); }
        }
        
        @media (max-width: 768px) {
            .battle-header h1 {
                font-size: 1.6rem;
            }
            
            .battle-type-badge {
                top: -25px;
                right: -40px;
                font-size: 0.6rem;
            }
        }
        
        @media (max-width: 480px) {
            .header-content {
                flex-direction: column;
                gap: 8px;
            }
            
            .battle-header h1 {
                font-size: 1.4rem;
            }
        }
        
        /* Players Battle Area */
        .players-battle-area {
            background-color: white;
            margin: 20px auto;
            width: 95%;
            max-width: 800px;
            border-radius: var(--card-border-radius);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            animation: slideIn 0.5s ease-out;
        }
        
        /* Players Header */
        .players-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            background: linear-gradient(to right, #f8f9fa, #ffffff, #f8f9fa);
            border-bottom: 1px solid var(--border-color);
            position: relative;
        }
        
        .players-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 10%;
            right: 10%;
            height: 3px;
            background: linear-gradient(to right, transparent, var(--primary-color) 50%, transparent);
            border-radius: 50%;
            filter: blur(1px);
        }
        
        .player-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            padding: 15px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            border: 1px solid #eaeaea;
            min-width: 120px;
        }
        
        .player-container:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            background: white;
        }
        
        .player-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid var(--primary-color);
            box-shadow: var(--shadow-sm), 0 0 0 2px rgba(255, 255, 255, 0.6);
            position: relative;
            z-index: 2;
        }
        
        .player-avatar::after {
            content: '';
            position: absolute;
            top: -3px;
            left: -3px;
            right: -3px;
            bottom: -3px;
            border-radius: 50%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.8), transparent);
            z-index: 1;
            animation: rotateGradient 3s linear infinite;
        }
        
        @keyframes rotateGradient {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .player-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: relative;
            z-index: 2;
        }
        
        .player-name {
            font-weight: 700;
            font-size: 1.1rem;
            text-align: center;
            color: var(--dark-color);
            background: linear-gradient(to right, var(--dark-color), #666);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 120px;
        }
        
        .player-score {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 5px 14px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 1rem;
            min-width: 40px;
            text-align: center;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .player-score::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 50%;
            background: linear-gradient(to bottom, rgba(255, 255, 255, 0.4), transparent);
            border-radius: 20px 20px 0 0;
        }
        
        .vs-indicator {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            position: relative;
            z-index: 1;
        }
        
        .vs-badge {
            background: linear-gradient(135deg, #ff9800, #f44336);
            color: white;
            font-weight: bold;
            padding: 10px 20px;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(244, 67, 54, 0.3);
            position: relative;
            animation: pulseVs 2s infinite;
        }
        
        @keyframes pulseVs {
            0% { transform: scale(1); box-shadow: 0 4px 15px rgba(244, 67, 54, 0.3); }
            50% { transform: scale(1.05); box-shadow: 0 6px 20px rgba(244, 67, 54, 0.5); }
            100% { transform: scale(1); box-shadow: 0 4px 15px rgba(244, 67, 54, 0.3); }
        }
        
        .vs-badge::before {
            content: '';
            position: absolute;
            top: -8px;
            left: -8px;
            right: -8px;
            bottom: -8px;
            background: linear-gradient(135deg, #ff9800, #f44336);
            border-radius: 50%;
            opacity: 0.3;
            z-index: -1;
            animation: pulseVsRing 2s infinite;
        }
        
        @keyframes pulseVsRing {
            0% { transform: scale(0.9); opacity: 0.3; }
            50% { transform: scale(1.1); opacity: 0.2; }
            100% { transform: scale(0.9); opacity: 0.3; }
        }
        
        .timer-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            background: rgba(255, 255, 255, 0.8);
            padding: 10px 20px;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            border: 1px solid #eaeaea;
        }
        
        .timer-display {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--dark-color);
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
        }
        
        .timer-label {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .players-header {
                padding: 15px;
            }
            
            .player-avatar {
                width: 60px;
                height: 60px;
            }
            
            .player-name {
                font-size: 1rem;
            }
            
            .vs-badge {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
            
            .timer-display {
                font-size: 1.2rem;
            }
            
            .question-section, .answers-section {
                padding: 20px 15px;
            }
            
            .question-text {
                font-size: 1rem;
            }
            
            .answer-number {
                width: 40px;
                min-width: 40px;
            }
            
            .answer-text-container {
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 480px) {
            .players-header {
                flex-direction: column;
                gap: 20px;
            }
            
            .player-container {
                flex-direction: row;
                width: 100%;
                justify-content: space-between;
                padding: 10px 15px;
            }
            
            .player-avatar {
                width: 50px;
                height: 50px;
                margin-right: 10px;
            }
            
            .player-name {
                text-align: left;
            }
            
            .vs-indicator {
                order: -1;
                flex-direction: row;
                width: 100%;
                justify-content: center;
                gap: 20px;
            }
            
            .timer-container {
                flex-direction: row;
                gap: 10px;
            }
            
            .question-image-container {
                height: 150px;
            }
        }
        
        /* Question Section */
        .question-section {
            padding: 25px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .question-number {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .question-label {
            font-size: 0.9rem;
            color: #666;
        }
        
        .question-count {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 35px;
            height: 35px;
            background-color: #222;
            color: white;
            font-weight: bold;
            border-radius: 5px;
            font-size: 1.2rem;
        }
        
        .question-difficulty {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            background: #f5f5f5;
            border: 1px solid #e0e0e0;
            font-size: 0.9rem;
            color: #555;
        }
        
        .question-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }
        
        .question-image-container {
            width: 100%;
            height: 200px;
            background-color: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .question-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: none; /* Initially hidden, shown when loaded */
        }
        
        .image-loader {
            position: absolute;
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        .expand-image-btn {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 5;
            opacity: 0;
            transition: opacity 0.3s ease, transform 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        .question-image-container:hover .expand-image-btn {
            opacity: 1;
            transform: translateY(0);
        }
        
        .expand-image-btn:hover {
            background: rgba(0, 0, 0, 0.8);
            transform: scale(1.1);
        }
        
        .question-text {
            font-size: 1.1rem;
            line-height: 1.6;
            color: #333;
            font-weight: 400;
            text-align: center;
        }
        
        /* Answers Section */
        .answers-section {
            padding: 25px;
        }
        
        .answers-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .answer-option {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 0;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: stretch;
            background-color: white;
            box-shadow: none;
        }
        
        .answer-option:hover {
            border-color: #ccc;
            background-color: #f9f9f9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }
        
        .answer-option:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .answer-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            min-width: 45px;
            font-weight: bold;
            color: #333;
            background-color: #f5f5f5;
            border-right: 1px solid #e0e0e0;
            font-size: 1.1rem;
        }
        
        .answer-text-container {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            flex: 1;
        }
        
        .answer-text {
            font-size: 1rem;
            font-weight: normal;
            color: #333;
        }
        
        .answer-radio {
            margin-left: auto;
            width: 20px;
            height: 20px;
            border: 2px solid #ddd;
            border-radius: 50%;
            padding: 2px;
            transition: all 0.2s ease;
        }
        
        .answer-option.selected {
            border-color: var(--primary-color);
            background-color: rgba(76, 175, 80, 0.05);
        }
        
        .answer-option.selected .answer-radio {
            background-color: white;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
        }
        
        .answer-option.selected .answer-radio::after {
            content: '';
            display: block;
            width: 10px;
            height: 10px;
            background-color: var(--primary-color);
            border-radius: 50%;
            margin: 3px;
        }
        
        /* Skip Button */
        .skip-button-container {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
            flex-wrap: wrap;
        }
        
        .skip-button {
            padding: 12px 30px;
            background-color: var(--dark-color);
            color: white;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
        }
        
        .skip-button:hover {
            background-color: #23272b;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .skip-button:active {
            transform: translateY(0);
            box-shadow: var(--shadow-sm);
        }
        
        .submit-button {
            padding: 12px 30px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
        }
        
        .submit-button:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .submit-button:active {
            transform: translateY(0);
            box-shadow: var(--shadow-sm);
        }
        
        .submit-button:disabled {
            background-color: #cccccc;
            color: #888888;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .leave-button {
            padding: 12px 30px;
            background-color: var(--danger-color);
            color: white;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
        }
        
        .leave-button:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .leave-button:active {
            transform: translateY(0);
            box-shadow: var(--shadow-sm);
        }
        
        /* Confirmation Dialog */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0);
            backdrop-filter: blur(0px);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s cubic-bezier(0.19, 1, 0.22, 1);
            pointer-events: none;
        }
        
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            pointer-events: all;
        }
        
        .confirmation-dialog {
            background: white;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            transform: scale(0.7) translateY(30px);
            opacity: 0;
            transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            overflow: hidden;
        }
        
        .modal-overlay.active .confirmation-dialog {
            transform: scale(1) translateY(0);
            opacity: 1;
        }
        
        .confirmation-dialog::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(to right, var(--danger-color), #d32f2f);
        }
        
        .modal-warning-icon {
            background: #ffebee;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        
        .modal-warning-icon i {
            color: #f44336;
            font-size: 32px;
        }
        
        .confirmation-dialog h3 {
            margin: 0 0 20px 0;
            color: var(--text-color);
            font-size: 1.8rem;
            position: relative;
            display: inline-block;
        }
        
        .confirmation-dialog h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -8px;
            height: 3px;
            width: 50px;
            background: var(--danger-color);
            left: 50%;
            transform: translateX(-50%);
            border-radius: 2px;
        }
        
        .confirmation-content p {
            margin: 0 0 15px 0;
            color: #666;
            font-size: 1.1rem;
        }
        
        .confirmation-content .warning-text {
            margin-bottom: 30px;
        }
        
        .confirmation-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .cancel-btn, .confirm-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.19, 1, 0.22, 1);
            position: relative;
            overflow: hidden;
            min-width: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .cancel-btn::after, .confirm-btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }
        
        .cancel-btn:hover::after, .confirm-btn:hover::after {
            width: 300%;
            height: 300%;
        }
        
        .cancel-btn:active, .confirm-btn:active {
            transform: scale(0.95);
        }
        
        .confirm-btn {
            background: var(--danger-color);
            color: white;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }
        
        .confirm-btn:hover {
            background: #c82333;
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
        }
        
        .cancel-btn {
            background: white;
            color: #333;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
        }
        
        .cancel-btn:hover {
            background: #f5f5f5;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }
        
        @media (max-width: 480px) {
            .confirmation-actions {
                flex-direction: column;
            }
        }
        
        /* Image Viewer Modal */
        .image-viewer-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0);
            z-index: 3000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s ease;
            pointer-events: none;
        }
        
        .image-viewer-overlay.active {
            background: rgba(0, 0, 0, 0.9);
            opacity: 1;
            visibility: visible;
            pointer-events: all;
        }
        
        .image-viewer-container {
            width: 90%;
            max-width: 1000px;
            height: 80vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            transform: scale(0.9);
            opacity: 0;
            transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        .image-viewer-overlay.active .image-viewer-container {
            transform: scale(1);
            opacity: 1;
        }
        
        .image-viewer-img {
            max-width: 100%;
            max-height: 90%;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.5);
        }
        
        .close-viewer-btn {
            position: absolute;
            top: -40px;
            right: 0;
            background: white;
            color: #333;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        
        .close-viewer-btn:hover {
            background: #f5f5f5;
            transform: rotate(90deg);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="battle-header">
        <div class="header-content">
            <div class="battle-type-badge">Quick Mode</div>
            <h1>
                <i class="fas fa-bolt"></i>
                Quick Battle
            </h1>
        </div>
    </header>
    
    <!-- Main Battle Area -->
    <main class="players-battle-area">
        <!-- Players Header -->
        <div class="players-header">
            <!-- Player 1 -->
            <div class="player-container">
                <div class="player-avatar">
                    <img src="https://ui-avatars.com/api/?name=Player+1&background=random" alt="Player 1">
                </div>
                <div class="player-name">Player 1</div>
                <div class="player-score">0</div>
            </div>
            
            <!-- VS Indicator -->
            <div class="vs-indicator">
                <div class="vs-badge">VS</div>
                <div class="timer-container">
                    <div class="timer-display" id="questionTimer">00:15</div>
                    <div class="timer-label">Time Left</div>
                </div>
            </div>
            
            <!-- Player 2 -->
            <div class="player-container">
                <div class="player-avatar">
                    <img src="https://ui-avatars.com/api/?name=Player+2&background=random" alt="Player 2">
                </div>
                <div class="player-name">Player 2</div>
                <div class="player-score">0</div>
            </div>
        </div>
        
        <!-- Question Section -->
        <div class="question-section">
            <div class="question-header">
                <div class="question-number">
                    <div class="question-label">Question</div>
                    <div class="question-count">1</div>
                </div>
                <div class="question-difficulty">Medium</div>
            </div>
            
            <div class="question-content">
                <div class="question-image-container">
                    <div class="image-loader"></div>
                    <img src="" alt="Question Image" class="question-image">
                    <button class="expand-image-btn" id="expandImageBtn">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
                
                <div class="question-text">
                    What is the capital city of France?
                </div>
            </div>
        </div>
        
        <!-- Answers Section -->
        <div class="answers-section">
            <div class="answers-container">
                <!-- Answer Option A -->
                <div class="answer-option" data-answer="a">
                    <div class="answer-number">A</div>
                    <div class="answer-text-container">
                        <div class="answer-text">London</div>
                        <div class="answer-radio"></div>
                    </div>
                </div>
                
                <!-- Answer Option B -->
                <div class="answer-option" data-answer="b">
                    <div class="answer-number">B</div>
                    <div class="answer-text-container">
                        <div class="answer-text">Paris</div>
                        <div class="answer-radio"></div>
                    </div>
                </div>
                
                <!-- Answer Option C -->
                <div class="answer-option" data-answer="c">
                    <div class="answer-number">C</div>
                    <div class="answer-text-container">
                        <div class="answer-text">Berlin</div>
                        <div class="answer-radio"></div>
                    </div>
                </div>
                
                <!-- Answer Option D -->
                <div class="answer-option" data-answer="d">
                    <div class="answer-number">D</div>
                    <div class="answer-text-container">
                        <div class="answer-text">Madrid</div>
                        <div class="answer-radio"></div>
                    </div>
                </div>
            </div>
            
            <!-- Skip Button -->
            <div class="skip-button-container">
                <button class="submit-button" id="submitAnswer" disabled>
                    <i class="fas fa-paper-plane"></i> Submit Answer
                </button>
                <button class="skip-button">
                    <i class="fas fa-forward"></i> Skip Question
                </button>
                <button class="leave-button">
                    <i class="fas fa-sign-out-alt"></i> Leave Battle
                </button>
            </div>
        </div>
    </main>
    
    <!-- Confirmation Dialog -->
    <div class="modal-overlay" id="leaveConfirmationModal">
        <div class="confirmation-dialog">
            <div class="modal-warning-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <h3>Leave Battle?</h3>
            <div class="confirmation-content">
                <p>Are you sure you want to leave this battle?</p>
                <p class="warning-text">You will lose points and the battle will be recorded as a forfeit.</p>
            </div>
            <div class="confirmation-actions">
                <button id="cancelLeave" class="cancel-btn">
                    <i class="fas fa-times"></i>Cancel
                </button>
                <button id="confirmLeave" class="confirm-btn">
                    <i class="fas fa-check"></i>Leave Battle
                </button>
            </div>
        </div>
    </div>
    
    <!-- Image Viewer Modal -->
    <div class="image-viewer-overlay" id="imageViewerModal">
        <div class="image-viewer-container">
            <img src="" alt="Enlarged Question Image" class="image-viewer-img" id="enlargedImage">
            <button class="close-viewer-btn" id="closeViewerBtn">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</body>
</html> 