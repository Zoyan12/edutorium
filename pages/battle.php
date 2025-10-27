<?php
// Include maintenance mode check
require_once '../includes/maintenance-check.php';

// Include the functions file to get getWebSocketUrl()
require_once '../includes/functions.php';

// Restrict direct access - must come from dashboard or have matchId
if (!isset($_GET['matchId']) && !isset($_SESSION['user'])) {
    header('Location: /pages/dashboard.php?error=direct_access_denied');
    exit();
}

// Get the WebSocket URL from the database
$websocketUrl = getWebSocketUrl();

// Get user session data (session already started by maintenance-check.php)
$userId = $_SESSION['user']['id'] ?? null;
$username = $_SESSION['user']['username'] ?? 'Player';
$avatar = $_SESSION['user']['avatar'] ?? 'default-avatar.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Battle Arena - Edutorium</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/battle.css">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
        }
    </style>
</head>
<body>
    <!-- Battle Container -->
    <div class="battle-container">
        <!-- Header with Progress -->
        <div class="battle-header">
            <div class="battle-info">
                <span class="battle-type" id="battleTypeLabel">Arena Battle</span>
                <span class="question-counter">
                    <span id="currentQuestion">1</span> / <span id="totalQuestions">5</span>
                </span>
            </div>
            <div class="connection-status" id="connectionStatus">
                <i class="fas fa-circle"></i>
                <span>Connected</span>
            </div>
        </div>

        <!-- Players Section -->
        <div class="players-section">
            <!-- Player 1 (You) -->
            <div class="player-card" id="player1Card">
            <div class="player-avatar">
                <img src="../img/<?php echo htmlspecialchars($avatar); ?>" alt="Your Avatar" id="player1Avatar">
                <div class="player-badge">YOU</div>
            </div>
                <div class="player-info">
                    <h3 id="player1Name"><?php echo htmlspecialchars($username); ?></h3>
                    <div class="score">
                        <i class="fas fa-star"></i>
                        <span id="player1Score">0</span>
                    </div>
                </div>
                <div class="answer-indicator" id="player1Answered">
                    <i class="fas fa-check"></i>
                </div>
            </div>

            <!-- Timer and Question Number -->
            <div class="timer-section">
                <div class="timer-circle" id="timerCircle">
                    <svg class="timer-svg" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="45" class="timer-bg"></circle>
                        <circle cx="50" cy="50" r="45" class="timer-progress" id="timerProgress"></circle>
                    </svg>
                    <div class="timer-value" id="timerValue">30</div>
                </div>
                <div class="timer-label">Time Left</div>
            </div>

            <!-- Player 2 (Opponent) -->
            <div class="player-card" id="player2Card">
            <div class="player-avatar">
                <img src="../img/default-avatar.png" alt="Opponent Avatar" id="player2Avatar">
                <div class="player-badge">OPPONENT</div>
            </div>
                <div class="player-info">
                    <h3 id="player2Name">Waiting...</h3>
                    <div class="score">
                        <i class="fas fa-star"></i>
                        <span id="player2Score">0</span>
                    </div>
                </div>
                <div class="answer-indicator" id="player2Answered">
                    <i class="fas fa-check"></i>
                </div>
            </div>
        </div>

        <!-- Question Display -->
        <div class="question-section">
            <div class="question-image-container" id="questionImageContainer">
                <div class="question-placeholder">
                    <i class="fas fa-question-circle"></i>
                    <p>Waiting for battle to start...</p>
                </div>
                <img id="questionImage" src="" alt="Question Image" style="display: none;">
                <div class="question-overlay">
                    <div class="question-difficulty" id="questionDifficulty"></div>
                </div>
            </div>
        </div>

        <!-- Answer Choices -->
        <div class="choices-section" id="choicesSection">
            <button class="choice-btn" id="choiceA" data-choice="A">
                <span class="choice-label">A</span>
                <span class="choice-text" id="choiceAText">-</span>
            </button>
            <button class="choice-btn" id="choiceB" data-choice="B">
                <span class="choice-label">B</span>
                <span class="choice-text" id="choiceBText">-</span>
            </button>
            <button class="choice-btn" id="choiceC" data-choice="C">
                <span class="choice-label">C</span>
                <span class="choice-text" id="choiceCText">-</span>
            </button>
            <button class="choice-btn" id="choiceD" data-choice="D">
                <span class="choice-label">D</span>
                <span class="choice-text" id="choiceDText">-</span>
            </button>
        </div>

        <!-- Opponent Answer Notification -->
        <div class="opponent-notification" id="opponentNotification" style="display: none;">
            <i class="fas fa-bolt"></i>
            <span>Opponent has answered!</span>
        </div>
    </div>

    <!-- Battle Result Modal -->
    <div class="result-modal" id="resultModal" style="display: none;">
        <div class="result-content">
            <div class="result-header">
                <h2 id="resultTitle">Battle Complete!</h2>
                <div class="result-icon" id="resultIcon">
                    <i class="fas fa-trophy"></i>
                </div>
            </div>
            
            <div class="result-stats">
                <div class="stat-box">
                    <div class="stat-label">Your Score</div>
                    <div class="stat-value" id="finalScore1">0</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Correct Answers</div>
                    <div class="stat-value" id="correctAnswers1">0</div>
                </div>
                <div class="stat-box opponent-stats" style="display: none;">
                    <div class="stat-label">Opponent Score</div>
                    <div class="stat-value" id="finalScore2">0</div>
                </div>
            </div>

            <div class="result-actions">
                <button class="btn-primary" id="rematchBtn">
                    <i class="fas fa-redo"></i> Rematch
                </button>
                <button class="btn-secondary" id="backToDashboardBtn">
                    <i class="fas fa-home"></i> Back to Dashboard
                </button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay" style="display: none;">
        <div class="loader">
            <div class="spinner"></div>
            <p id="loadingText">Loading...</p>
        </div>
    </div>

    <!-- WebSocket URL (for JS access) -->
    <div id="websocket-url" style="display: none;"><?php echo htmlspecialchars($websocketUrl); ?></div>

    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
    <script src="../js/battle-websocket.js"></script>
    <script src="../js/battleManager.js"></script>
    <script>
        // Initialize Supabase client
        const supabaseUrl = '<?php echo SUPABASE_URL; ?>';
        const supabaseKey = '<?php echo SUPABASE_ANON_KEY; ?>';
        const { createClient } = supabase;
        const supabaseClient = createClient(supabaseUrl, supabaseKey);
        
        // Initialize battle manager
        let battleManager;
        
        async function initBattle() {
            try {
                // Get Supabase session for authentication token
                const { data: { session }, error: sessionError } = await supabaseClient.auth.getSession();
                if (sessionError || !session) {
                    console.error('No active session:', sessionError);
                    // Initialize without token - server will handle it
                    battleManager = new BattleManager({
                        userId: <?php echo json_encode($userId); ?>,
                        username: <?php echo json_encode($username); ?>,
                        avatar: <?php echo json_encode($avatar); ?>
                    });
                } else {
                    // Initialize with authentication token
                    battleManager = new BattleManager({
                        userId: <?php echo json_encode($userId); ?>,
                        username: <?php echo json_encode($username); ?>,
                        avatar: <?php echo json_encode($avatar); ?>,
                        accessToken: session.access_token
                    });
                }
                
                // Check if we have a battle ID
                if (battleManager && battleManager.state.battleId) {
                    console.log('Battle ID found in URL:', battleManager.state.battleId);
                } else if (battleManager) {
                    console.error('No match ID provided in URL');
                    battleManager.showError('No match ID provided');
                }
            } catch (error) {
                console.error('Error initializing battle:', error);
            }
        }
        
        // Start the battle on page load
        window.addEventListener('DOMContentLoaded', () => {
            initBattle();
        });
    </script>
</body>
</html>
