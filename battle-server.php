<?php
/**
 * Battle WebSocket Server for Edutorium
 * 
 * This server manages battle matchmaking and gameplay
 */

// Enable error reporting for troubleshooting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Display startup diagnostics
echo "Starting Battle WebSocket Server...\n";
echo "Current directory: " . getcwd() . "\n";

// Load dependencies
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/includes/config.php';

echo "Environment configuration:\n";
echo "- SUPABASE_URL: " . SUPABASE_URL . "\n";
echo "- API Key length: " . (strlen(SUPABASE_ANON_KEY) > 10 ? "OK (" . strlen(SUPABASE_ANON_KEY) . " chars)" : "MISSING") . "\n\n";

// Note: The WebSocket URL is configurable through the 'settings' table in the database
// Use the 'websocket_url' key to change the URL without modifying the code

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

/**
 * Battle Server - WebSocket server for handling real-time educational battles
 */
class BattleServer implements MessageComponentInterface {
    protected $clients;
    protected $users = []; // userId => connection
    protected $userDetails = []; // userId => user details (name, avatar, etc)
    protected $waitingPlayers = []; // players waiting for a match
    protected $activeBattles = []; // ongoing battles
    protected $matchConfirmations = []; // players who have confirmed a match
    protected $battleState = []; // state of each battle
    protected $questionSets = []; // question sets for each battle

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        echo "Battle Server started!\n";
        
        // Set up a heartbeat to check for disconnected players
        $this->setupHeartbeat();
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $conn->battleData = new \stdClass();
        $conn->battleData->userId = null;
        $conn->battleData->username = null;
        $conn->battleData->avatar = null;
        $conn->battleData->inBattle = false;
        $conn->battleData->battleId = null;
        $conn->battleData->state = 'connected';
        $conn->battleData->config = null;
        $conn->battleData->lastPing = time();
        
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (!$data || !isset($data['action'])) {
            return;
        }
        
        switch ($data['action']) {
            case 'login':
                $this->handleLogin($from, $data);
                break;
                
            case 'find_match':
                $this->handleFindMatch($from, $data);
                break;
                
            case 'cancel_matchmaking':
                $this->handleCancelMatchmaking($from);
                break;
                
            case 'confirm_match':
                $this->handleConfirmMatch($from, $data);
                break;
                
            case 'submit_answer':
                $this->handleSubmitAnswer($from, $data);
                break;
                
            case 'ready_for_next_round':
                $this->handleReadyForNextRound($from);
                break;
                
            case 'quit_battle':
                $this->handleQuitBattle($from);
                break;
                
            case 'join_match':
                $this->handleJoinMatch($from, $data);
                break;
                
            case 'heartbeat':
                $this->handleHeartbeat($from, $data);
                break;
                
            case 'leaving_page':
                $this->handleLeavingPage($from);
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // Only handle disconnection if the connection is still valid
        if ($conn->battleData) {
            // Handle battle disconnection
            if (isset($conn->battleData->battleId) && isset($this->activeBattles[$conn->battleData->battleId])) {
                $battleId = $conn->battleData->battleId;
                $battle = &$this->activeBattles[$battleId];
                
                // Only handle if it hasn't already ended
                if (!isset($battle['end_time'])) {
                    // Determine if this is player 1 or 2
                    $isPlayer1 = $battle['player1']['connection'] === $conn;
                    $opponent = $isPlayer1 ? $battle['player2']['connection'] : $battle['player1']['connection'];
                    
                    // Mark the player as disconnected but keep the battle active
                    if ($isPlayer1) {
                        $battle['player1']['connected'] = false;
                        $battle['player1']['disconnect_time'] = time();
                    } else {
                        $battle['player2']['connected'] = false;
                        $battle['player2']['disconnect_time'] = time();
                    }
                    
                    // Notify opponent
                    if ($opponent) {
                        $opponent->send(json_encode([
                            'type' => 'opponentTemporarilyDisconnected',
                            'message' => 'Your opponent has temporarily disconnected. The battle will continue when they reconnect.'
                        ]));
                    }
                    
                    echo "Player " . ($isPlayer1 ? "1" : "2") . " temporarily disconnected from battle {$battleId}\n";
                    
                    // Start a timer to end the battle if player doesn't reconnect within 2 minutes
                    // We'll implement this in a separate heartbeat method
                }
            }
            
            // Remove from waiting queue if waiting
            if (isset($this->waitingPlayers[$conn->resourceId])) {
                unset($this->waitingPlayers[$conn->resourceId]);
                $this->broadcastWaitingCount();
            }
            
            // Remove from users list if logged in
            if (isset($conn->battleData->userId)) {
                unset($this->users[$conn->battleData->userId]);
            }
        }
        
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
    
    /**
     * Handle user login to the battle system
     */
    protected function handleLogin(ConnectionInterface $conn, $data) {
        if (!isset($data['userId'], $data['username'])) {
            $conn->send(json_encode([
                'type' => 'error',
                'error' => 'Missing user information'
            ]));
            return;
        }
        
        // Check if user is already logged in
        if (isset($this->users[$data['userId']])) {
            $oldConn = $this->users[$data['userId']];
            
            // If the old connection is still valid and in a battle, don't close it
            if ($oldConn->battleData && $oldConn->battleData->inBattle) {
                $conn->send(json_encode([
                    'type' => 'error',
                    'error' => 'You are already in a battle'
                ]));
                return;
            }
            
            // Close old connection if it exists and isn't in a battle
            if ($oldConn !== $conn) {
                $oldConn->close();
            }
        }
        
        // Store user data
        $conn->battleData->userId = $data['userId'];
        $conn->battleData->username = $data['username'];
        $conn->battleData->avatar = $data['avatar'] ?? 'default-avatar.png';
        $conn->battleData->state = 'connected';
        $conn->battleData->lastPing = time();
        
        // Store battle type if provided, making sure it's only 'quick' or 'arena'
        if (isset($data['battleType']) && in_array($data['battleType'], ['quick', 'arena'])) {
            $conn->battleData->battleType = $data['battleType'];
        } else {
            $conn->battleData->battleType = 'arena'; // Default to arena if not specified or invalid
        }
        
        // Update users list
        $this->users[$data['userId']] = $conn;
        
        echo "User {$conn->battleData->username} (ID: {$conn->battleData->userId}) logged in for {$conn->battleData->battleType} battles\n";
        
        // Send success response
        $conn->send(json_encode([
            'type' => 'loginSuccess',
            'userId' => $data['userId'],
            'username' => $data['username'],
            'avatar' => $conn->battleData->avatar,
            'battleType' => $conn->battleData->battleType // Send battleType back to client
        ]));
        
        // Check if player is trying to reconnect to a specific battle
        if (isset($data['battleId'])) {
            $battleId = $data['battleId'];
            
            // Check if the battle exists
            if (isset($this->activeBattles[$battleId])) {
                $battle = $this->activeBattles[$battleId];
                
                // Verify the user is a participant in this battle
                if ($battle['player1']['id'] === $data['userId'] || $battle['player2']['id'] === $data['userId']) {
                    echo "User {$conn->battleData->username} is attempting to reconnect to battle {$battleId}\n";
                    
                    // Handle the battle rejoin
                    $this->handleJoinMatch($conn, [
                        'action' => 'join_match',
                        'matchId' => $battleId
                    ]);
                } else {
                    echo "User {$conn->battleData->username} tried to join battle {$battleId} but is not a participant\n";
                    
                    $conn->send(json_encode([
                        'type' => 'error',
                        'error' => 'You are not a participant in this battle'
                    ]));
                }
            } else {
                echo "User {$conn->battleData->username} tried to reconnect to non-existent battle {$battleId}\n";
                
                $conn->send(json_encode([
                    'type' => 'error',
                    'error' => 'Battle not found'
                ]));
            }
        }
    }
    
    /**
     * Send current battle state to a reconnected player
     */
    protected function sendBattleState(ConnectionInterface $conn, $battleId) {
        if (!isset($this->activeBattles[$battleId])) {
            return;
        }
        
        $battle = $this->activeBattles[$battleId];
        $currentQuestion = $battle['current_question'];
        
        // Determine if this is player 1 or 2
        $isPlayer1 = $battle['player1']['connection'] === $conn;
        $opponent = $isPlayer1 ? $battle['player2'] : $battle['player1'];
        
        // Get the current question data
        $currentQuestionData = isset($battle['questions'][$currentQuestion - 1]) ? 
            $battle['questions'][$currentQuestion - 1] : null;
        
        // Send battle start message with current state
        $conn->send(json_encode([
            'type' => 'battleStart',
            'battleId' => $battleId,
            'players' => [
                [
                    'userId' => $battle['player1']['id'],
                    'username' => $battle['player1']['username'],
                    'avatar' => $battle['player1']['avatar']
                ],
                [
                    'userId' => $battle['player2']['id'],
                    'username' => $battle['player2']['username'],
                    'avatar' => $battle['player2']['avatar']
                ]
            ],
            'current' => $currentQuestion,
            'total' => count($battle['questions']),
            'question' => $currentQuestionData,
            'playerScore' => $isPlayer1 ? ($battle['player1']['score'] ?? 0) : ($battle['player2']['score'] ?? 0),
            'opponentScore' => $isPlayer1 ? ($battle['player2']['score'] ?? 0) : ($battle['player1']['score'] ?? 0),
            'battleType' => $battle['battleType'] ?? 'arena'
        ]));
        
        // If player has already answered this question, send their answer back
        $playerAnswers = $isPlayer1 ? $battle['player1']['answers'] : $battle['player2']['answers'];
        if (isset($playerAnswers[$currentQuestion])) {
            $conn->send(json_encode([
                'type' => 'answer_received',
                'question' => $currentQuestion,
                'answer' => $playerAnswers[$currentQuestion]['answer']
            ]));
        }
        
        // If opponent has already answered, send that info too
            $opponentAnswers = $isPlayer1 ? $battle['player2']['answers'] : $battle['player1']['answers'];
        if (isset($opponentAnswers[$currentQuestion])) {
                $conn->send(json_encode([
                'type' => 'opponent_answered',
                'question' => $currentQuestion
                ]));
            }
            
        // If both players have answered, send the results
        if (isset($playerAnswers[$currentQuestion]) && isset($opponentAnswers[$currentQuestion])) {
                $conn->send(json_encode([
                'type' => 'answer_results',
                'question' => $currentQuestion,
                'your_answer' => $playerAnswers[$currentQuestion]['answer'],
                'opponent_answer' => $opponentAnswers[$currentQuestion]['answer'],
                'correct_answer' => $currentQuestionData['correctAnswer'],
                'your_correct' => $playerAnswers[$currentQuestion]['correct'],
                'opponent_correct' => $opponentAnswers[$currentQuestion]['correct'],
                'your_score' => $isPlayer1 ? $battle['player1']['score'] : $battle['player2']['score'],
                'opponent_score' => $isPlayer1 ? $battle['player2']['score'] : $battle['player1']['score']
                ]));
            }
    }
    
    /**
     * Handle matchmaking request
     */
    protected function handleFindMatch(ConnectionInterface $from, $data) {
        if (!isset($from->battleData->userId)) {
            $from->send(json_encode([
                'type' => 'error',
                'error' => 'Not logged in'
            ]));
            return;
        }
        
        // Check if user is already in a battle or waiting
        if ($from->battleData->inBattle || $from->battleData->state === 'waiting') {
            $from->send(json_encode([
                'type' => 'error',
                'error' => 'Already in matchmaking or battle'
            ]));
            return;
        }
        
        // Mark as waiting and store battle config
        $from->battleData->state = 'waiting';
        $from->battleData->config = isset($data['config']) ? $data['config'] : ['difficulty' => 'medium', 'subject' => 'general'];
        
        // Store battle type (quick or arena)
        if (isset($data['battleType'])) {
            $from->battleData->battleType = $data['battleType']; // Store directly on battleData
            $from->battleData->config['battleType'] = $data['battleType']; // Also store in config for backward compatibility
        } else {
            $from->battleData->battleType = 'arena'; // Default to arena if not specified
            $from->battleData->config['battleType'] = 'arena';
        }
        
        // Use resourceId as a unique key in the waiting players array
        $resourceId = $from->resourceId;
        $this->waitingPlayers[$resourceId] = $from;
        
        // Send active player count
        $waitingCount = count($this->waitingPlayers);
        $from->send(json_encode([
            'type' => 'waitingCount',
            'count' => $waitingCount,
            'battleType' => $from->battleData->battleType
        ]));
        
        echo "User {$from->battleData->username} joined matchmaking queue for {$from->battleData->battleType} battle. {$waitingCount} players waiting.\n";
        
        // Try to find a match immediately
        $this->matchPlayers();
    }
    
    /**
     * Remove player from waiting queue
     */
    protected function handleCancelMatchmaking(ConnectionInterface $from) {
        $resourceId = $from->resourceId;
        
        // Remove from waiting queue
        if (isset($this->waitingPlayers[$resourceId])) {
            unset($this->waitingPlayers[$resourceId]);
            echo "User {$from->battleData->username} cancelled matchmaking\n";
        }
        
        $from->battleData->state = 'connected';
        
        $from->send(json_encode([
            'type' => 'matchmakingCancelled'
        ]));
        
        // Update waiting count for other players
        $this->broadcastWaitingCount();
    }
    
    /**
     * Handle match confirmation
     */
    protected function handleConfirmMatch(ConnectionInterface $conn, $data) {
        if (!isset($conn->battleData->userId) || !isset($data['matchId'])) {
            return;
        }
        
        $userId = $conn->battleData->userId;
        $matchId = $data['matchId'];
        
        // Store confirmation
        if (!isset($this->matchConfirmations[$matchId])) {
            $this->matchConfirmations[$matchId] = [];
        }
        
        $this->matchConfirmations[$matchId][$userId] = true;
        
        echo "User {$conn->battleData->username} confirmed match {$matchId}\n";
        
        // Check if all players confirmed
        if (isset($this->activeBattles[$matchId])) {
            $battle = $this->activeBattles[$matchId];
            
            // Generate UUID for the battle first thing to ensure it's always available
            if (!isset($battle['database_battle_id'])) {
                $this->activeBattles[$matchId]['database_battle_id'] = $this->generateUUID();
                echo "Generated UUID for battle {$matchId}: {$this->activeBattles[$matchId]['database_battle_id']}\n";
            }
            
            $player1Id = $battle['player1']['id'];
            $player2Id = $battle['player2']['id'];
            
            // Determine who is ready
            $player1Ready = isset($this->matchConfirmations[$matchId][$player1Id]);
            $player2Ready = isset($this->matchConfirmations[$matchId][$player2Id]);
            
            // Inform other player that this one is ready
            if ($userId === $player1Id && !$player2Ready) {
                // Player 1 is ready, notify player 2
                if (isset($battle['player2']['connection'])) {
                    $battle['player2']['connection']->send(json_encode([
                        'type' => 'opponentReady'
                    ]));
                }
            } else if ($userId === $player2Id && !$player1Ready) {
                // Player 2 is ready, notify player 1
                if (isset($battle['player1']['connection'])) {
                    $battle['player1']['connection']->send(json_encode([
                        'type' => 'opponentReady'
                    ]));
                }
            }
            
            // If both players confirmed, start the countdown and then the battle
            if ($player1Ready && $player2Ready) {
                // Send countdown notification to both players
                $battle['player1']['connection']->send(json_encode([
                    'type' => 'bothReady',
                    'database_battle_id' => $this->activeBattles[$matchId]['database_battle_id'] // Send UUID
                ]));
                
                $battle['player2']['connection']->send(json_encode([
                    'type' => 'bothReady',
                    'database_battle_id' => $this->activeBattles[$matchId]['database_battle_id'] // Send UUID
                ]));
                
                // Start battle after a delay (simulating the countdown)
                $this->scheduleStartBattle($matchId, 3); // 3-second countdown
            }
        }
    }
    
    /**
     * Schedule battle start after countdown
     */
    protected function scheduleStartBattle($battleId, $delay) {
        echo "Scheduling battle {$battleId} to start in {$delay} seconds\n";
        
        // Start battle after the countdown
        $startTime = time() + $delay;
        
        // Check every second if it's time to start
        $checkInterval = function() use ($battleId, $startTime) {
            if (time() >= $startTime) {
                $this->startBattle($battleId);
                return false; // Stop the timer
            }
            return true; // Continue checking
        };
        
        // Create a timer that checks every second
        $loop = \React\EventLoop\Loop::get();
        $timer = $loop->addPeriodicTimer(1, function() use ($loop, $checkInterval, &$timer) {
            if (!$checkInterval()) {
                $loop->cancelTimer($timer);
            }
        });
    }
    
    /**
     * Handle answer submission
     */
    protected function handleSubmitAnswer(ConnectionInterface $from, $data) {
        if (!isset($from->battleData->battleId) || !isset($data['question_id'], $data['answer'], $data['time'])) {
            $from->send(json_encode([
                'type' => 'error',
                'error' => 'Invalid answer submission. Missing required fields.'
            ]));
            return;
        }
        
        $battleId = $from->battleData->battleId;
        
        if (!isset($this->activeBattles[$battleId])) {
            $from->send(json_encode([
                'type' => 'error',
                'error' => 'Battle not found.'
            ]));
            return;
        }
        
        $battle = &$this->activeBattles[$battleId];
        $currentQuestion = $battle['current_question'];
        $questionId = $data['question_id'];
        
        // Make sure we're answering the current question
        if ($questionId != $currentQuestion) {
            $from->send(json_encode([
                'type' => 'error',
                'error' => 'Wrong question number.'
            ]));
            return;
        }
        
        // Get the correct answer for the current question
        $correctAnswer = $battle['questions'][$currentQuestion - 1]['correctAnswer'];
        $isCorrect = ($data['answer'] === $correctAnswer);
        
        // Record the answer
        if ($battle['player1']['connection'] === $from) {
            $battle['player1']['answers'][$currentQuestion] = [
                'answer' => $data['answer'],
                'time' => $data['time'],
                'correct' => $isCorrect
            ];
            
            if ($isCorrect) {
                $battle['player1']['score'] += 10 + min(10, $data['time']); // Bonus for speed
            }
            
            // Update health
            $player1Health = 100 - (count($battle['questions']) - array_sum(array_column($battle['player1']['answers'], 'correct'))) * (100 / count($battle['questions']));
            
            // Send result to the player
            $from->send(json_encode([
                'type' => 'answerResult',
                'correctAnswer' => $correctAnswer,
                'isCorrect' => $isCorrect,
                'player1Health' => $player1Health,
                'player2Health' => 100 - (count($battle['questions']) - array_sum(array_column($battle['player2']['answers'], 'correct'))) * (100 / count($battle['questions']))
            ]));
            
            echo "Player 1 in battle {$battleId} answered question {$currentQuestion}. Correct: " . ($isCorrect ? "Yes" : "No") . "\n";
        } else if ($battle['player2']['connection'] === $from) {
            $battle['player2']['answers'][$currentQuestion] = [
                'answer' => $data['answer'],
                'time' => $data['time'],
                'correct' => $isCorrect
            ];
            
            if ($isCorrect) {
                $battle['player2']['score'] += 10 + min(10, $data['time']); // Bonus for speed
            }
            
            // Update health
            $player2Health = 100 - (count($battle['questions']) - array_sum(array_column($battle['player2']['answers'], 'correct'))) * (100 / count($battle['questions']));
            
            // Send result to the player
            $from->send(json_encode([
                'type' => 'answerResult',
                'correctAnswer' => $correctAnswer,
                'isCorrect' => $isCorrect,
                'player1Health' => 100 - (count($battle['questions']) - array_sum(array_column($battle['player1']['answers'], 'correct'))) * (100 / count($battle['questions'])),
                'player2Health' => $player2Health
            ]));
            
            echo "Player 2 in battle {$battleId} answered question {$currentQuestion}. Correct: " . ($isCorrect ? "Yes" : "No") . "\n";
        }
        
        // Check if both players answered
        $player1Answered = isset($battle['player1']['answers'][$currentQuestion]);
        $player2Answered = isset($battle['player2']['answers'][$currentQuestion]);
        
        if ($player1Answered && $player2Answered) {
            // Both players answered, move to next question
            $this->moveToNextQuestion($battleId);
        } else {
            // Just notify opponent that this player has answered
            $opponent = $battle['player1']['connection'] === $from ? 
                $battle['player2']['connection'] : $battle['player1']['connection'];
                
            $opponent->send(json_encode([
                'type' => 'opponentAnswer',
                'health' => $battle['player1']['connection'] === $from ? 
                    (100 - (count($battle['questions']) - array_sum(array_column($battle['player1']['answers'], 'correct'))) * (100 / count($battle['questions']))) : 
                    (100 - (count($battle['questions']) - array_sum(array_column($battle['player2']['answers'], 'correct'))) * (100 / count($battle['questions'])))
            ]));
        }
    }
    
    /**
     * Move to the next question or end the battle
     */
    protected function moveToNextQuestion($battleId) {
        if (!isset($this->activeBattles[$battleId])) {
            return;
        }
        
        $battle = &$this->activeBattles[$battleId];
        
        // Advance to next question
        $battle['current_question']++;
        
        // Check if battle is complete
        if ($battle['current_question'] > count($battle['questions'])) {
            $this->endBattle($battleId);
        } else {
            // Send next question to both players
            $nextQuestion = $battle['questions'][$battle['current_question'] - 1];
            
            $battle['player1']['connection']->send(json_encode([
                'type' => 'question',
                'question' => $nextQuestion,
                'current' => $battle['current_question'],
                'total' => count($battle['questions'])
            ]));
            
            $battle['player2']['connection']->send(json_encode([
                'type' => 'question',
                'question' => $nextQuestion,
                'current' => $battle['current_question'],
                'total' => count($battle['questions'])
            ]));
            
            echo "Battle {$battleId} moved to question {$battle['current_question']}\n";
        }
    }
    
    /**
     * Handle player leaving a battle
     */
    protected function handleLeaveBattle(ConnectionInterface $from) {
        $this->handleQuitBattle($from);
    }
    
    /**
     * Try to match waiting players together
     */
    protected function matchPlayers() {
        // Need at least 2 players to make a match
        if (count($this->waitingPlayers) < 2) {
            return;
        }
        
        echo "Trying to match players... " . count($this->waitingPlayers) . " players waiting\n";
        
        // Group players by battle type
        $playersByBattleType = [];
        foreach ($this->waitingPlayers as $player) {
            $battleType = $player->battleData->battleType ?? 'arena';
            if (!isset($playersByBattleType[$battleType])) {
                $playersByBattleType[$battleType] = [];
            }
            $playersByBattleType[$battleType][] = $player;
        }
        
        // For each battle type, try to create matches
        foreach ($playersByBattleType as $battleType => $players) {
            // Need at least 2 players of the same battle type to match
            if (count($players) < 2) {
                continue;
            }
            
            // Sort players by join time (oldest first)
            usort($players, function($a, $b) {
                $timeA = isset($a->battleData->joinTime) ? $a->battleData->joinTime : 0;
                $timeB = isset($b->battleData->joinTime) ? $b->battleData->joinTime : 0;
                return $timeA - $timeB;
            });
            
            // Match the first two players
            $player1 = array_shift($players);
            $player2 = array_shift($players);
            
            echo "Creating match between {$player1->battleData->username} and {$player2->battleData->username} for $battleType battle\n";
            
            // Create the match
            $battleId = $this->createMatch($player1, $player2, $battleType);
            
            // Remove players from waiting queue
            unset($this->waitingPlayers[$player1->resourceId]);
            unset($this->waitingPlayers[$player2->resourceId]);
            
            // Update waiting count for remaining players
            $this->broadcastWaitingCount();
            
            // Check if we can create more matches
            if (count($players) >= 2) {
                $this->matchPlayers();
            }
        }
    }
    
    /**
     * Create a match between two players
     */
    protected function createMatch(ConnectionInterface $player1, ConnectionInterface $player2, $battleType = 'arena') {
        // Generate a unique battle ID
        $battleId = uniqid('battle_');
        
        // Update player connection states
        $player1->battleData->state = 'battle';
        $player1->battleData->battleId = $battleId;
        
        $player2->battleData->state = 'battle';
        $player2->battleData->battleId = $battleId;
        
        // Get battle configuration
        $numQuestions = $player1->battleData->numQuestions ?? 5; // Default to 5 questions
        $difficulty = $player1->battleData->difficulty ?? 'medium';  // Default to medium difficulty
        $subjects = $player1->battleData->subjects ?? ['general'];  // Default to general subject
        
        // For quick battles, override with default settings
        if ($battleType === 'quick') {
            $numQuestions = 3; // Quick battles have 3 questions
            $difficulty = 'medium'; // Medium difficulty for quick battles
            $subjects = ['general']; // General knowledge for quick battles
        }
        
        $battleConfig = [
            'numQuestions' => $numQuestions,
            'difficulty' => $difficulty,
            'subjects' => $subjects
        ];
        
        echo "Battle config: " . json_encode($battleConfig) . "\n";
        
        // Generate questions
        $questions = $this->generateQuestions($battleConfig);
        
        if (!$questions || count($questions) === 0) {
            // Failed to generate questions, inform players and cancel match
            $player1->send(json_encode([
                'type' => 'error',
                'message' => 'Failed to create battle. No suitable questions found.'
            ]));
            
            $player2->send(json_encode([
                'type' => 'error',
                'message' => 'Failed to create battle. No suitable questions found.'
            ]));
            
            // Reset player states
            $player1->battleData->state = 'connected';
            $player2->battleData->state = 'connected';
            
            return null;
        }
        
        // Create battle in memory
            $this->activeBattles[$battleId] = [
                'id' => $battleId,
            'database_battle_id' => $this->generateUUID(), // Store UUID for database operations
                'player1' => [
                'connection' => $player1,
                'id' => $player1->battleData->userId,
                'username' => $player1->battleData->username,
                'avatar' => $player1->battleData->avatar,
                    'ready' => false,
                    'next_ready' => false,
                    'score' => 0,
                    'answers' => [],
                    'connected' => true
                ],
                'player2' => [
                'connection' => $player2,
                'id' => $player2->battleData->userId,
                'username' => $player2->battleData->username,
                'avatar' => $player2->battleData->avatar,
                    'ready' => false,
                    'next_ready' => false,
                    'score' => 0,
                    'answers' => [],
                    'connected' => true
                ],
                'questions' => $questions,
                'current_question' => 0,
                'start_time' => null,
                'end_time' => null,
            'battleType' => $battleType,
            'config' => $battleConfig,
            'battle_result' => 'Incomplete',
            'battle_type' => $battleType // Make sure battle_type exists for database compatibility
            ];
            
        // Send match found notifications to both players
        $matchFoundData = [
                'type' => 'matchFound',
                'matchId' => $battleId,
            'battleType' => $battleType,
            'database_battle_id' => $this->activeBattles[$battleId]['database_battle_id'],
            'questionCount' => $numQuestions
        ];
        
        // Add player specific data to notifications
        $player1Data = $matchFoundData;
        $player1Data['opponent'] = [
            'userId' => $player2->battleData->userId,
            'username' => $player2->battleData->username,
            'avatar' => $player2->battleData->avatar
        ];
        
        $player2Data = $matchFoundData;
        $player2Data['opponent'] = [
            'userId' => $player1->battleData->userId,
            'username' => $player1->battleData->username,
            'avatar' => $player1->battleData->avatar
        ];
        
        // Send notifications
        $player1->send(json_encode($player1Data));
        $player2->send(json_encode($player2Data));
        
        echo "Created {$battleType} battle {$battleId} (UUID: {$this->activeBattles[$battleId]['database_battle_id']}) between {$player1->battleData->username} and {$player2->battleData->username}\n";
        
        return $battleId;
    }
    
    /**
     * Start a battle between matched players
     */
    protected function startBattle($battleId) {
        if (!isset($this->activeBattles[$battleId])) {
            echo "Battle {$battleId} not found, cannot start\n";
            return;
        }
        
        echo "Starting battle {$battleId}\n";
        
        $battle = &$this->activeBattles[$battleId];
        
        // Generate a proper UUID for the battle to match database expectations
        $battle['database_battle_id'] = $this->generateUUID();
        
        // Set start time
        $battle['start_time'] = time();
        
        // Generate questions for the battle
        $config = $battle['config'] ?? [];
        $battle['questions'] = $this->generateQuestions($config);
        
        // Set initial state
        $battle['current_question'] = 0;
        $battle['player1']['score'] = 0;
        $battle['player1']['answers'] = [];
        $battle['player2']['score'] = 0;
        $battle['player2']['answers'] = [];
        
        // Send start battle message to both players
        $this->sendCurrentBattleState($battleId);
    }
    
    /**
     * Generate a proper UUID v4
     */
    protected function generateUUID() {
        // Format: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx where x is any hex digit and y is 8, 9, a, or b
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Send current battle state to both players
     */
    protected function sendCurrentBattleState($battleId) {
        if (!isset($this->activeBattles[$battleId])) {
            return;
        }
        
        $battle = &$this->activeBattles[$battleId];
        $battle['current_question']++;
        
        // If we've gone through all questions, end the battle
        if ($battle['current_question'] > count($battle['questions'])) {
            $this->endBattle($battleId);
            return;
        }
        
        echo "Sending question {$battle['current_question']} to players in battle {$battleId}\n";
        
        // Get current question
        $questionIndex = $battle['current_question'] - 1;
        $question = $battle['questions'][$questionIndex];
        
        // Send to player 1
        if (isset($battle['player1']['connection']) && $battle['player1']['connected']) {
            $battleInfo = [
                'type' => 'battleState',
                'state' => 'question',
                'question' => $this->prepareQuestionForClient($question),
                'questionNumber' => $battle['current_question'],
                'totalQuestions' => count($battle['questions']),
                'database_battle_id' => $battle['database_battle_id'], // Include formatted UUID
                'battle_uuid' => $battle['database_battle_id'],        // New parameter with proper UUID
                'battleType' => $battle['battleType'] ?? 'arena'  // Use the battle's battleType property directly
            ];
            
            $battle['player1']['connection']->send(json_encode($battleInfo));
        }
        
        // Send to player 2
        if (isset($battle['player2']['connection']) && $battle['player2']['connected']) {
            $battleInfo = [
                'type' => 'battleState',
                'state' => 'question',
                'question' => $this->prepareQuestionForClient($question),
                'questionNumber' => $battle['current_question'],
                'totalQuestions' => count($battle['questions']),
                'database_battle_id' => $battle['database_battle_id'], // Include formatted UUID
                'battle_uuid' => $battle['database_battle_id'],        // New parameter with proper UUID
                'battleType' => $battle['battleType'] ?? 'arena'  // Use the battle's battleType property directly
            ];
            
            $battle['player2']['connection']->send(json_encode($battleInfo));
        }
    }
    
    /**
     * Prepare question data for sending to clients
     */
    protected function prepareQuestionForClient($question) {
        // Return a copy of the question without the correct answer
        $clientQuestion = $question;
        unset($clientQuestion['correctAnswer']); // Don't send correct answer to clients
        return $clientQuestion;
    }
    
    /**
     * Save battle to database
     */
    protected function saveBattleToDatabase($battleId) {
        if (!isset($this->activeBattles[$battleId])) {
            echo "ERROR: Cannot save battle to database - battle {$battleId} not found\n";
            return;
        }
        
        $battle = $this->activeBattles[$battleId];
        
        // Get Supabase credentials
        $supabaseUrl = getenv('SUPABASE_URL') ?: SUPABASE_URL;
        $supabaseKey = getenv('SUPABASE_KEY') ?: SUPABASE_ANON_KEY;
        
        if (!$supabaseUrl || !$supabaseKey) {
            echo "ERROR: Supabase credentials not found, cannot save battle record\n";
            return;
        }
        
        // Check if database battle id exists, if not, generate one
        if (!isset($battle['database_battle_id'])) {
            $battle['database_battle_id'] = $this->generateUUID();
            echo "Generated missing UUID for battle {$battleId}: {$battle['database_battle_id']}\n";
        }
        
        echo "DEBUG: Saving battle {$battleId} to database, battle type: {$battle['battleType']}\n";
        
        // Format the battle data for database insertion
        $battleData = [
            'battle_id' => $battle['database_battle_id'], // Use the UUID format instead of internal ID
            'player1_id' => $battle['player1']['id'],
            'player2_id' => $battle['player2']['id'],
            'start_time' => date('c', $battle['start_time']),
            'questions_count' => count($battle['questions']),
            'battle_type' => $battle['battleType'] ?? 'arena', // Use the battle's battleType property
            'battle_result' => $battle['battle_result'] ?? 'Incomplete', // Default result until battle ends
            'difficulty' => isset($battle['config']['difficulty']) ? $battle['config']['difficulty'] : 'medium',
            'subject' => isset($battle['config']['subjects']) && is_array($battle['config']['subjects']) && !empty($battle['config']['subjects']) ? 
                $battle['config']['subjects'][0] : 'general', // Use first subject as string, not JSON
        ];
        
        echo "DEBUG: Battle data to save: " . json_encode($battleData) . "\n";
        
        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $supabaseUrl . '/rest/v1/battle_records');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($battleData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'apikey: ' . $supabaseKey,
            'Authorization: Bearer ' . $supabaseKey,
            'Prefer: return=representation'
        ]);
        
        // Execute cURL request
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Check response
        if ($statusCode === 201 || $statusCode === 200) {
            $responseData = json_decode($response, true);
            echo "Battle record saved to database with UUID: {$battle['database_battle_id']}\n";
            
            // If we got a record ID back, store it in the battle record
            if (isset($responseData[0]['id'])) {
                $this->activeBattles[$battleId]['database_id'] = $responseData[0]['id'];
                echo "DEBUG: Database record ID: {$responseData[0]['id']}\n";
            }
        } else {
            echo "ERROR: Failed to save battle record (Status: {$statusCode})\n";
            echo "Response: {$response}\n";
            if ($curlError) {
                echo "cURL Error: {$curlError}\n";
            }
        }
    }
    
    /**
     * End a battle and calculate results
     */
    protected function endBattle($battleId) {
        if (!isset($this->activeBattles[$battleId])) {
            return;
        }
        
        $battle = &$this->activeBattles[$battleId];
        
        // Set end time
        $battle['end_time'] = time();
        
        // Calculate results
        $player1Score = $battle['player1']['score'];
        $player2Score = $battle['player2']['score'];
        
        $player1CorrectAnswers = 0;
        foreach ($battle['player1']['answers'] as $answer) {
            if ($answer['correct']) {
                $player1CorrectAnswers++;
            }
        }
        
        $player2CorrectAnswers = 0;
        foreach ($battle['player2']['answers'] as $answer) {
            if ($answer['correct']) {
                $player2CorrectAnswers++;
            }
        }
        
        $player1Won = $player1Score > $player2Score;
        $player2Won = $player2Score > $player1Score;
        $tie = $player1Score === $player2Score;
        
        // Record the battle result - who won
        if ($player1Won) {
            $battle['battle_result'] = 'Player1Wins';
        } else if ($player2Won) {
            $battle['battle_result'] = 'Player2Wins';
        } else {
            $battle['battle_result'] = 'Draw';
        }
        
        // Record the battle type (quick or arena)
        if (!isset($battle['battle_type'])) {
            // Use the actual battle type from the battle record
            $battle['battle_type'] = $battle['battleType'] ?? 'arena';
            // Log the battle type for debugging
            echo "Recording battle {$battleId} completion with type: {$battle['battle_type']}\n";
        }
        
        $battleTime = $battle['end_time'] - $battle['start_time'];
        $totalQuestions = count($battle['questions']);
        
        // Calculate bonus points based on time and correct answers
        $player1TimeBonus = 0;
        $player2TimeBonus = 0;
        
        foreach ($battle['player1']['answers'] as $answer) {
            if ($answer['correct']) {
                $player1TimeBonus += $answer['time'];
            }
        }
        
        foreach ($battle['player2']['answers'] as $answer) {
            if ($answer['correct']) {
                $player2TimeBonus += $answer['time'];
            }
        }
        
        // Update battle record in database
        $this->updateBattleInDatabase($battleId, [
            'status' => 'completed',
            'end_time' => date('c', $battle['end_time']),
            'player1_score' => $player1Score,
            'player2_score' => $player2Score,
            'player1_correct' => $player1CorrectAnswers,
            'player2_correct' => $player2CorrectAnswers,
            'result' => $battle['battle_result'],
            'duration' => $battleTime
        ]);
        
        // Send results to player 1
        $battle['player1']['connection']->send(json_encode([
            'type' => 'battleEnd',
            'winner' => $player1Won ? $battle['player1']['id'] : ($tie ? null : $battle['player2']['id']),
            'result' => $player1Won ? 'win' : ($tie ? 'tie' : 'lose'),
            'stats' => [
                'your_score' => $player1Score,
                'opponent_score' => $player2Score,
                'correctAnswers' => $player1CorrectAnswers,
                'opponentCorrectAnswers' => $player2CorrectAnswers,
                'totalQuestions' => $totalQuestions,
                'timeBonus' => $player1TimeBonus,
                'pointsEarned' => $player1Score
            ]
        ]));
        
        // Send results to player 2
        $battle['player2']['connection']->send(json_encode([
            'type' => 'battleEnd',
            'winner' => $player2Won ? $battle['player2']['id'] : ($tie ? null : $battle['player1']['id']),
            'result' => $player2Won ? 'win' : ($tie ? 'tie' : 'lose'),
            'stats' => [
                'your_score' => $player2Score,
                'opponent_score' => $player1Score,
                'correctAnswers' => $player2CorrectAnswers,
                'opponentCorrectAnswers' => $player1CorrectAnswers,
                'totalQuestions' => $totalQuestions,
                'timeBonus' => $player2TimeBonus,
                'pointsEarned' => $player2Score
            ]
        ]));
        
        // Update player battle states
        $battle['player1']['connection']->battleData->state = 'connected';
        $battle['player2']['connection']->battleData->state = 'connected';
        
        unset($battle['player1']['connection']->battleData->battleId);
        unset($battle['player2']['connection']->battleData->battleId);
        unset($battle['player1']['connection']->battleData->inBattle);
        unset($battle['player2']['connection']->battleData->inBattle);
        
        echo "Battle {$battleId} ended. Player 1: {$player1Score}, Player 2: {$player2Score}\n";
        
        // Clean up battle
        unset($this->activeBattles[$battleId]);
    }
    
    /**
     * Update battle record in database
     */
    protected function updateBattleInDatabase($battleId, $data) {
        // Get Supabase credentials
        $supabaseUrl = getenv('SUPABASE_URL') ?: SUPABASE_URL;
        $supabaseKey = getenv('SUPABASE_KEY') ?: SUPABASE_ANON_KEY;
        
        if (!$supabaseUrl || !$supabaseKey) {
            echo "ERROR: Supabase credentials not found, cannot update battle record\n";
            return;
        }
        
        // Check if database_battle_id exists in the battle data
        $databaseBattleId = $this->activeBattles[$battleId]['database_battle_id'] ?? null;
        if (!$databaseBattleId) {
            echo "ERROR: No database battle ID found for battle {$battleId}\n";
            return;
        }
        
        // Map incoming data keys to database field names
        $dbData = [];
        
        // Map field names correctly
        if (isset($data['end_time'])) {
            $dbData['end_time'] = $data['end_time'];
        }
        
        if (isset($data['player1_score'])) {
            $dbData['player1_final_points'] = $data['player1_score'];
        }
        
        if (isset($data['player2_score'])) {
            $dbData['player2_final_points'] = $data['player2_score'];
        }
        
        if (isset($data['player1_correct'])) {
            $dbData['player1_correct_answers'] = $data['player1_correct'];
        }
        
        if (isset($data['player2_correct'])) {
            $dbData['player2_correct_answers'] = $data['player2_correct'];
        }
        
        if (isset($data['result'])) {
            $dbData['battle_result'] = $data['result'];
        }
        
        if (isset($data['duration'])) {
            $dbData['duration_seconds'] = $data['duration'];
        }
        
        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $supabaseUrl . '/rest/v1/battle_records?battle_id=eq.' . urlencode($databaseBattleId));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dbData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'apikey: ' . $supabaseKey,
            'Authorization: Bearer ' . $supabaseKey,
            'Prefer: return=minimal'
        ]);
        
        // Execute cURL request
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Check response
        if ($statusCode === 204) {
            echo "Battle record updated in database. Battle ID: {$battleId}, UUID: {$databaseBattleId}\n";
        } else {
            echo "ERROR: Failed to update battle record (Status: {$statusCode})\n";
            if ($response) {
                echo "Response: {$response}\n";
            }
        }
    }
    
    /**
     * Handle a player quitting a battle
     */
    protected function handleQuitBattle(ConnectionInterface $from) {
        if (!isset($from->battleData->userId) || !isset($from->battleData->battleId)) {
            return;
        }
        
        $userId = $from->battleData->userId;
        $battleId = $from->battleData->battleId;
        
        echo "Player {$from->battleData->username} quit battle {$battleId}\n";
        
        // End the battle and notify the opponent
        if (isset($this->activeBattles[$battleId])) {
            $battle = &$this->activeBattles[$battleId];
            
            // Mark the battle as intentionally ended
            $battle['forced_end'] = true;
            $battle['end_time'] = time();
            $battle['quit_by'] = $userId;
            $battle['battle_type'] = $battle['player1']['id'] === $userId ? 'Player1Left' : 'Player2Left';
            
            // Record the battle result - opponent wins
            $battle['battle_result'] = $battle['player1']['id'] === $userId ? 'Player2Wins' : 'Player1Wins';
            
            // Determine if this is player 1 or 2
            $isPlayer1 = $battle['player1']['id'] === $userId;
            $opponent = $isPlayer1 ? $battle['player2']['connection'] : $battle['player1']['connection'];
            
            // Calculate player scores and correct answers
            $player1Score = $battle['player1']['score'] ?? 0;
            $player2Score = $battle['player2']['score'] ?? 0;
            $player1CorrectAnswers = isset($battle['player1']['answers']) ? count(array_filter($battle['player1']['answers'], function($a) { return $a['correct']; })) : 0;
            $player2CorrectAnswers = isset($battle['player2']['answers']) ? count(array_filter($battle['player2']['answers'], function($a) { return $a['correct']; })) : 0;
            
            // Update battle record in database
            $this->updateBattleInDatabase($battleId, [
                'status' => 'abandoned',
                'end_time' => date('c', $battle['end_time']),
                'player1_score' => $player1Score,
                'player2_score' => $player2Score,
                'player1_correct' => $player1CorrectAnswers,
                'player2_correct' => $player2CorrectAnswers,
                'result' => $battle['battle_result'],
                'duration' => $battle['end_time'] - ($battle['start_time'] ?? $battle['end_time']),
                'quit_by' => $userId
            ]);
            
            // Notify opponent
            if ($opponent && isset($opponent->battleData) && $opponent->battleData->inBattle) {
                $opponent->send(json_encode([
                    'type' => 'opponentQuit',
                    'message' => 'Your opponent has quit the battle'
                ]));
                
                // Award win to opponent
                $winnerScore = $isPlayer1 ? $player2Score : $player1Score;
                $loserScore = $isPlayer1 ? $player1Score : $player2Score;
                
                $opponent->send(json_encode([
                    'type' => 'battleEnd',
                    'result' => 'win',
                    'reason' => 'opponent_quit',
                    'stats' => [
                        'your_score' => $winnerScore,
                        'opponent_score' => $loserScore,
                        'correctAnswers' => $isPlayer1 ? $player2CorrectAnswers : $player1CorrectAnswers,
                        'opponentCorrectAnswers' => $isPlayer1 ? $player1CorrectAnswers : $player2CorrectAnswers,
                        'time' => time() - $battle['start_time']
                    ]
                ]));
                
                // Clean up opponent connection
                $opponent->battleData->state = 'connected';
                unset($opponent->battleData->battleId);
                unset($opponent->battleData->inBattle);
            }
            
            // Clean up this connection
            $from->battleData->state = 'connected';
            unset($from->battleData->battleId);
            unset($from->battleData->inBattle);
            
            // Send battle end notification to the player who quit
            $from->send(json_encode([
                'type' => 'battleEnd',
                'result' => 'lose',
                'reason' => 'quit',
                'stats' => [
                    'your_score' => $isPlayer1 ? $player1Score : $player2Score,
                    'opponent_score' => $isPlayer1 ? $player2Score : $player1Score,
                    'correctAnswers' => $isPlayer1 ? $player1CorrectAnswers : $player2CorrectAnswers,
                    'opponentCorrectAnswers' => $isPlayer1 ? $player2CorrectAnswers : $player1CorrectAnswers,
                    'time' => time() - $battle['start_time']
                ]
            ]));
            
            // Remove the battle
            unset($this->activeBattles[$battleId]);
            
            // Confirm to sender
            $from->send(json_encode([
                'type' => 'quitSuccess',
                'message' => 'You have successfully quit the battle'
            ]));
        }
    }
    
    /**
     * Handle player disconnection
     */
    protected function handlePlayerDisconnect($userId, $battleId) {
        // Check if battle exists
        if (!isset($this->activeBattles[$battleId])) {
            return false;
        }
        
        $battle = &$this->activeBattles[$battleId];
        $now = time();
        $playerIndex = null;
        $opponentIndex = null;
        
        // Identify which player disconnected
        if (isset($battle['player1']['id']) && $battle['player1']['id'] === $userId) {
            $playerIndex = 'player1';
            $opponentIndex = 'player2';
        } else if (isset($battle['player2']['id']) && $battle['player2']['id'] === $userId) {
            $playerIndex = 'player2';
            $opponentIndex = 'player1';
        } else {
            // Player not found in battle
            return false;
        }
        
        echo "Player $userId disconnected from battle $battleId\n";
        
        // Mark player as disconnected
        $battle[$playerIndex]['connected'] = false;
        $battle[$playerIndex]['disconnect_time'] = $now;
        
        // Don't immediately end the battle - instead mark player as disconnected
        // and let the heartbeat system handle the timeout
        
        // Track the disconnect for analytics
        if (!isset($battle['disconnections'])) {
            $battle['disconnections'] = [];
        }
        
        // Add disconnect event to history
        $battle['disconnections'][] = [
            'player' => $playerIndex,
            'time' => $now,
            'battle_time' => isset($battle['start_time']) ? $now - $battle['start_time'] : 0
        ];
        
        // Notify the opponent player if they're still connected
        if (isset($battle[$opponentIndex]['connection']) && 
            $battle[$opponentIndex]['connected']) {
            $battle[$opponentIndex]['connection']->send(json_encode([
                'type' => 'opponentDisconnected',
                'message' => 'Your opponent has disconnected. The battle will continue if they reconnect within 2 minutes.'
            ]));
        }
        
        return true;
    }
    
    /**
     * Remove player from waiting queue
     */
    protected function removeFromWaitingQueue(ConnectionInterface $conn) {
        $resourceId = $conn->resourceId;
        
        if (isset($this->waitingPlayers[$resourceId])) {
            unset($this->waitingPlayers[$resourceId]);
            echo "Removed player with resource ID {$resourceId} from waiting queue\n";
            $this->broadcastWaitingCount();
        }
    }
    
    /**
     * Broadcast waiting count to all waiting players
     */
    protected function broadcastWaitingCount() {
        // Count waiting players by battle type
        $waitingByType = ['quick' => 0, 'arena' => 0];
        
        foreach ($this->waitingPlayers as $conn) {
            $battleType = isset($conn->battleData->battleType) ? $conn->battleData->battleType : 'arena';
            if ($battleType === 'quick' || $battleType === 'arena') {
                $waitingByType[$battleType]++;
            }
        }
        
        // Total count for logging
        $totalCount = count($this->waitingPlayers);
        
        // Broadcast to all waiting players, but only send count of players with the same battle type
        foreach ($this->waitingPlayers as $conn) {
            $battleType = isset($conn->battleData->battleType) ? $conn->battleData->battleType : 'arena';
            $relevantCount = ($battleType === 'quick' || $battleType === 'arena') ? $waitingByType[$battleType] : 0;
            
            $conn->send(json_encode([
                'type' => 'waitingCount',
                'count' => $relevantCount,
                'battleType' => $battleType
            ]));
        }
        
        echo "Broadcasting waiting counts - Total: {$totalCount}, Quick: {$waitingByType['quick']}, Arena: {$waitingByType['arena']}\n";
    }
    
    /**
     * Generate questions for a battle based on config
     */
    protected function generateQuestions($config) {
        // Default values
        $subjects = ['general'];
        $numQuestions = 5;
        $difficulty = 'medium';
        $battleType = 'arena';
        
        echo "DEBUG: Generating questions for config: " . json_encode($config) . "\n";
        
        // Extract config values based on format
        if (is_array($config) || is_object($config)) {
            // Extract battle type
            if (isset($config['battleType'])) {
                $battleType = $config['battleType'];
            }
            
            // Set default questions count based on battle type
            $numQuestions = ($battleType === 'quick') ? 3 : 5;
            
            // Extract subjects (may be in 'subjects' or 'subject')
            if (isset($config['subjects']) && is_array($config['subjects'])) {
                $subjects = !empty($config['subjects']) ? $config['subjects'] : ['general'];
            } elseif (isset($config['subject'])) {
                $subjects = is_array($config['subject']) ? $config['subject'] : [$config['subject']];
            }
            
            // Extract question count if specified (override default)
            if (isset($config['questionCount']) && is_numeric($config['questionCount'])) {
                $numQuestions = (int)$config['questionCount'];
            }
            
            // For quick battles, always limit to 3 questions
            if ($battleType === 'quick' && $numQuestions > 3) {
                $numQuestions = 3;
                echo "Quick battle detected: limiting to 3 questions\n";
            }
            
            // Extract difficulty
            if (isset($config['difficulty'])) {
                $difficulty = $config['difficulty'];
            }
        }
        
        echo "DEBUG: Will generate {$numQuestions} questions with difficulty '{$difficulty}' for {$battleType} battle\n";
        echo "DEBUG: Subjects: " . json_encode($subjects) . "\n";
        
        // Ensure subjects is an array and not empty
        if (!is_array($subjects) || empty($subjects)) {
            $subjects = ['general'];
        }
        
        // Set up API request to Supabase
        $supabaseUrl = getenv('SUPABASE_URL') ?: SUPABASE_URL;
        $supabaseKey = getenv('SUPABASE_KEY') ?: SUPABASE_ANON_KEY;
        
        if (!$supabaseUrl || !$supabaseKey) {
            echo "ERROR: Supabase credentials not found in environment variables\n";
            echo "Using fallback sample questions\n";
            // Fall back to sample questions if Supabase credentials are missing
            return $this->generateSampleQuestions($numQuestions, $subjects, $difficulty);
        }
        
        // Build the API endpoint
        $endpoint = $supabaseUrl . '/rest/v1/questions';
        
        // Initialize parameters with only columns we know exist based on the schema
        $params = [
            'select' => 'id,image_url,correct_answer,subject,difficulty,created_by',
            'limit' => 100 // Fetch a pool of questions to randomly select from
        ];
        
        // For quick battles, don't filter by subject - get random questions
        if ($battleType === 'quick') {
            echo "DEBUG: Quick battle mode - selecting random questions regardless of subject\n";
            
            // Only filter by difficulty if specified
            if ($difficulty !== 'any') {
                $params['difficulty'] = 'eq.' . $difficulty;
            }
        } else {
            // Regular arena battle - add filters for difficulty and subjects
            if ($difficulty !== 'any') {
                $params['difficulty'] = 'eq.' . $difficulty;
            }
            
            // For subjects, we need to handle it differently because we need OR conditions
            $subjectFilter = '';
            foreach ($subjects as $index => $subject) {
                if ($subject !== 'any') {
                    if ($index > 0) {
                        $subjectFilter .= ',';
                    }
                    $subjectFilter .= 'subject.eq.' . urlencode($subject);
                }
            }
            
            if (!empty($subjectFilter)) {
                // Correctly format the OR condition with parentheses for the Supabase API
                $params['or'] = '(' . $subjectFilter . ')';
            }
        }
        
        // Build query string
        $queryString = http_build_query($params);
        $url = $endpoint . '?' . $queryString;
        
        echo "DEBUG: Fetching questions from: {$url}\n";
        
        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . $supabaseKey,
            'Authorization: Bearer ' . $supabaseKey
        ]);
        
        // Execute cURL request
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Check if request was successful
        if ($statusCode !== 200) {
            echo "ERROR: Failed to fetch questions from Supabase (Status: {$statusCode})\n";
            if ($curlError) {
                echo "cURL Error: {$curlError}\n";
            }
            echo "Response: {$response}\n";
            echo "Using fallback sample questions\n";
            // Fall back to sample questions
            return $this->generateSampleQuestions($numQuestions, $subjects, $difficulty);
        }
        
        // Parse response
        $allQuestions = json_decode($response, true);
        
        if (empty($allQuestions)) {
            echo "WARNING: No questions found matching criteria\n";
            echo "Using fallback sample questions\n";
            // Fall back to sample questions
            return $this->generateSampleQuestions($numQuestions, $subjects, $difficulty);
        }
        
        echo "DEBUG: Found " . count($allQuestions) . " questions in database, selecting {$numQuestions}\n";
        
        // Shuffle questions and select the required number
        shuffle($allQuestions);
        $dbQuestions = array_slice($allQuestions, 0, $numQuestions);
        
        // Format the questions as needed for the battle, focusing on the image_url
        $formattedQuestions = [];
        
        foreach ($dbQuestions as $question) {
            // Verify image_url exists, otherwise use a placeholder
            $imageUrl = !empty($question['image_url']) ? $question['image_url'] : 'https://placehold.co/600x400?text=Question+Image+Missing';
            
            // Create options based on the correct answer (A, B, C, D)
            $options = ['Option A', 'Option B', 'Option C', 'Option D'];
            
            $formattedQuestions[] = [
                'id' => $question['id'],
                'subject' => $question['subject'] ?? 'general',
                'difficulty' => $question['difficulty'] ?? 'medium',
                'text' => 'Please view the image for this question', // Simple placeholder text
                'options' => $options,
                'correctAnswer' => $this->getCorrectAnswerIndex($question['correct_answer']),
                'image_url' => $imageUrl,
                'is_image_question' => true // Flag to indicate this is an image-based question
            ];
        }
        
        echo "DEBUG: Successfully formatted {$numQuestions} image-based questions for battle\n";
        
        return $formattedQuestions;
    }
    
    /**
     * Convert letter answer (A, B, C, D) to index (0, 1, 2, 3)
     */
    protected function getCorrectAnswerIndex($letter) {
        switch (strtoupper($letter)) {
            case 'A': return 0;
            case 'B': return 1;
            case 'C': return 2;
            case 'D': return 3;
            default: return 0; // Default to A if invalid
        }
    }
    
    /**
     * Generate sample questions as a fallback
     */
    protected function generateSampleQuestions($numQuestions, $subjects, $difficulty) {
        echo "DEBUG: Generating {$numQuestions} sample fallback questions\n";
        
        $questions = [];
        
        // Use a subject from the array or default to general
        $subject = !empty($subjects) && is_array($subjects) ? $subjects[array_rand($subjects)] : 'general';
        echo "DEBUG: Generated {$numQuestions} sample questions for subject: {$subject}\n";
        
        // Sample image URLs (placeholders with subject and question text)
        $placeholderUrl = "https://placehold.co/600x400?text=Sample+";
        
        // Sample questions for different subjects
        $sampleQuestions = [
            'science' => [
                'Which planet is known as the Red Planet?',
                'What is the chemical symbol for water?',
                'What is the largest organ in the human body?',
                'What force keeps us on the ground?',
                'Which element has the symbol Fe on the periodic table?'
            ],
            'history' => [
                'Who was the first president of the United States?',
                'In which year did World War II end?',
                'Who painted the Mona Lisa?',
                'What ancient civilization built the pyramids of Giza?',
                'Which empire was ruled by Julius Caesar?'
            ],
            'math' => [
                'What is the square root of 64?',
                'What is 7 × 8?',
                'What is 3 squared plus 4 squared?',
                'If x + 5 = 12, what is x?',
                'What is the value of π (pi) to two decimal places?'
            ],
            'english' => [
                'What is the past tense of "run"?',
                'Which word is a synonym for "happy"?',
                'What type of word is "quickly"?',
                'What is the plural form of "child"?',
                'What punctuation mark ends an interrogative sentence?'
            ],
            'general' => [
                'Which color is created by mixing blue and yellow?',
                'How many continents are there on Earth?',
                'Which animal is known as the "king of the jungle"?',
                'What is the capital of France?',
                'How many sides does a hexagon have?'
            ]
        ];
        
        // Sample options
        $correctAnswers = ['A', 'B', 'C', 'D'];
        
        // If subject is not in our samples, use general
        if (!isset($sampleQuestions[$subject])) {
            $subject = 'general';
        }
        
        // Generate the requested number of questions
        for ($i = 0; $i < $numQuestions; $i++) {
            // Select a random question from the subject
            $questionIndex = $i % count($sampleQuestions[$subject]);
            $questionText = $sampleQuestions[$subject][$questionIndex];
            
            // Encode the question text for URL
            $encodedText = urlencode(substr($questionText, 0, 30) . '...');
            
            // Generate random correct answer index (0-3)
            $correctAnswerIndex = rand(0, 3);
            
            // Create the question
            $questions[] = [
                'id' => $i + 1000, // Fake ID starting from 1000
                'subject' => $subject,
                'difficulty' => $difficulty,
                'text' => 'Please view the image for this question', // Placeholder text
                'options' => ['Option A', 'Option B', 'Option C', 'Option D'],
                'correctAnswer' => $correctAnswerIndex,
                'image_url' => $placeholderUrl . $subject . '+Question+' . ($i + 1),
                'is_image_question' => true // Flag to indicate this is an image-based question
            ];
        }
        
        return $questions;
    }
    
    /**
     * Handle join match request
     */
    protected function handleJoinMatch(ConnectionInterface $from, $data) {
        if (!isset($from->battleData->userId)) {
            $from->send(json_encode([
                'type' => 'error',
                'error' => 'Not logged in'
            ]));
            return;
        }
        
        if (!isset($data['matchId'])) {
            $from->send(json_encode([
                'type' => 'error',
                'error' => 'Match ID is required'
            ]));
            return;
        }
        
        $matchId = $data['matchId'];
        
        // Check if battle exists
        if (!isset($this->activeBattles[$matchId])) {
            $from->send(json_encode([
                'type' => 'error',
                'error' => 'Battle not found'
            ]));
            return;
        }
        
        $battle = &$this->activeBattles[$matchId];
        
        // Validate the user is authorized to join this battle
        if ($battle['player1']['id'] !== $from->battleData->userId &&
            $battle['player2']['id'] !== $from->battleData->userId) {
            $from->send(json_encode([
                'type' => 'error',
                'error' => 'You are not authorized to join this battle.'
            ]));
            return;
        }
        
        // Update connection reference for the appropriate player
        $isPlayer1 = $battle['player1']['id'] === $from->battleData->userId;
        
        if ($isPlayer1) {
            $battle['player1']['connection'] = $from;
            $battle['player1']['connected'] = true;
            $battle['player1']['last_heartbeat'] = time();
            
            // If player was marked as disconnected, clear that status
            if (isset($battle['player1']['disconnect_time'])) {
                unset($battle['player1']['disconnect_time']);
            }
        } else {
            $battle['player2']['connection'] = $from;
            $battle['player2']['connected'] = true;
            $battle['player2']['last_heartbeat'] = time();
            
            // If player was marked as disconnected, clear that status
            if (isset($battle['player2']['disconnect_time'])) {
                unset($battle['player2']['disconnect_time']);
            }
        }
        
        // Update connection battle data
        $from->battleData->battleId = $matchId;
        $from->battleData->inBattle = true;
        $from->battleData->state = 'battling';
        
        // Let the client know they've successfully joined
        $from->send(json_encode([
            'type' => 'joinMatchSuccess',
            'battleId' => $matchId
        ]));
        
        echo "User {$from->battleData->username} rejoined battle {$matchId}\n";
        
        // Notify opponent that player has reconnected
        $opponent = $isPlayer1 ? $battle['player2']['connection'] : $battle['player1']['connection'];
        if ($opponent && isset($opponent->battleData) && $opponent->battleData->inBattle) {
            $opponent->send(json_encode([
                'type' => 'opponentReconnected',
                'message' => 'Your opponent has reconnected. The battle will continue.'
            ]));
        }
        
        // Send current battle state to the player
        $this->sendBattleState($from, $matchId);
    }
    
    /**
     * Set up a periodic heartbeat to check for disconnected players
     */
    protected function setupHeartbeat() {
        // Run the heartbeat every 10 seconds
        $interval = 10;
        $timeoutPeriod = 120; // 2 minutes timeout for disconnected players
        
        echo "Setting up heartbeat to check for disconnected players every {$interval} seconds\n";
        
        try {
            // Get the event loop from the global loop
            $loop = \React\EventLoop\Loop::get();
            
            // Add a periodic timer
            $loop->addPeriodicTimer($interval, function() use ($timeoutPeriod) {
                $this->checkDisconnectedPlayers($timeoutPeriod);
            });
            
            echo "Heartbeat timer set up successfully\n";
        } catch (\Exception $e) {
            echo "Warning: Could not set up heartbeat: " . $e->getMessage() . "\n";
            echo "Battles will not automatically end when players disconnect\n";
        }
    }
    
    /**
     * Check for disconnected players and handle timeouts
     */
    protected function checkDisconnectedPlayers($timeoutPeriod) {
        $now = time();
        
        foreach ($this->activeBattles as $battleId => &$battle) {
            // Skip battles that have already ended
            if (isset($battle['end_time'])) {
                continue;
            }
            
            // Check player 1 disconnect status
            $player1Disconnected = isset($battle['player1']['connected']) && 
                                  $battle['player1']['connected'] === false && 
                                  isset($battle['player1']['disconnect_time']);
                                  
            // Check player 2 disconnect status
            $player2Disconnected = isset($battle['player2']['connected']) && 
                                  $battle['player2']['connected'] === false && 
                                  isset($battle['player2']['disconnect_time']);
            
            // If both players are disconnected, end the battle
            if ($player1Disconnected && $player2Disconnected) {
                echo "Both players disconnected from battle {$battleId}, ending battle\n";
                $battle['forced_end'] = true;
                $battle['end_time'] = $now;
                $battle['battle_type'] = 'Cancelled';
                $battle['battle_result'] = 'Incomplete';
                unset($this->activeBattles[$battleId]);
                continue;
            }
            
            // Check if player 1 has been disconnected for too long
            if ($player1Disconnected && ($now - $battle['player1']['disconnect_time'] > $timeoutPeriod)) {
                echo "Player 1 has been disconnected for over {$timeoutPeriod} seconds from battle {$battleId}\n";
                
                // Set battle outcome
                $battle['forced_end'] = true;
                $battle['end_time'] = $now;
                $battle['battle_type'] = 'Timeout';
                $battle['battle_result'] = 'Player2Wins';
                
                // Calculate scores and correct answers
                $player1Score = $battle['player1']['score'] ?? 0;
                $player2Score = $battle['player2']['score'] ?? 0;
                $player1CorrectAnswers = isset($battle['player1']['answers']) ? count(array_filter($battle['player1']['answers'], function($a) { return $a['correct']; })) : 0;
                $player2CorrectAnswers = isset($battle['player2']['answers']) ? count(array_filter($battle['player2']['answers'], function($a) { return $a['correct']; })) : 0;
                
                // Notify player 2 if connected
                if (!$player2Disconnected && $battle['player2']['connection']) {
                    $battle['player2']['connection']->send(json_encode([
                        'type' => 'opponentDisconnected',
                        'message' => 'Your opponent has been disconnected for too long. You win the battle!'
                    ]));
                    
                    // Award win to player 2
                    $battle['player2']['connection']->send(json_encode([
                        'type' => 'battleEnd',
                        'result' => 'win',
                        'reason' => 'opponent_timeout',
                        'stats' => [
                            'your_score' => $player2Score,
                            'opponent_score' => $player1Score,
                            'correctAnswers' => $player2CorrectAnswers,
                            'opponentCorrectAnswers' => $player1CorrectAnswers,
                            'time' => $now - $battle['start_time']
                        ]
                    ]));
                    
                    // Clean up player 2 connection
                    $battle['player2']['connection']->battleData->state = 'connected';
                    unset($battle['player2']['connection']->battleData->battleId);
                    unset($battle['player2']['connection']->battleData->inBattle);
                }
                
                // End the battle
                unset($this->activeBattles[$battleId]);
                continue;
            }
            
            // Check if player 2 has been disconnected for too long
            if ($player2Disconnected && ($now - $battle['player2']['disconnect_time'] > $timeoutPeriod)) {
                echo "Player 2 has been disconnected for over {$timeoutPeriod} seconds from battle {$battleId}\n";
                
                // Set battle outcome
                $battle['forced_end'] = true;
                $battle['end_time'] = $now;
                $battle['battle_type'] = 'Timeout';
                $battle['battle_result'] = 'Player1Wins';
                
                // Calculate scores and correct answers
                $player1Score = $battle['player1']['score'] ?? 0;
                $player2Score = $battle['player2']['score'] ?? 0;
                $player1CorrectAnswers = isset($battle['player1']['answers']) ? count(array_filter($battle['player1']['answers'], function($a) { return $a['correct']; })) : 0;
                $player2CorrectAnswers = isset($battle['player2']['answers']) ? count(array_filter($battle['player2']['answers'], function($a) { return $a['correct']; })) : 0;
                
                // Notify player 1 if connected
                if (!$player1Disconnected && $battle['player1']['connection']) {
                    $battle['player1']['connection']->send(json_encode([
                        'type' => 'opponentDisconnected',
                        'message' => 'Your opponent has been disconnected for too long. You win the battle!'
                    ]));
                    
                    // Award win to player 1
                    $battle['player1']['connection']->send(json_encode([
                        'type' => 'battleEnd',
                        'result' => 'win',
                        'reason' => 'opponent_timeout',
                        'stats' => [
                            'your_score' => $player1Score,
                            'opponent_score' => $player2Score,
                            'correctAnswers' => $player1CorrectAnswers,
                            'opponentCorrectAnswers' => $player2CorrectAnswers,
                            'time' => $now - $battle['start_time']
                        ]
                    ]));
                    
                    // Clean up player 1 connection
                    $battle['player1']['connection']->battleData->state = 'connected';
                    unset($battle['player1']['connection']->battleData->battleId);
                    unset($battle['player1']['connection']->battleData->inBattle);
                }
                
                // End the battle
                unset($this->activeBattles[$battleId]);
            }
        }
    }
    
    /**
     * Handle heartbeat messages from clients
     */
    protected function handleHeartbeat(ConnectionInterface $from, $data) {
        // Log heartbeat with timestamp
        $timestamp = isset($data['timestamp']) ? $data['timestamp'] : time() * 1000;
        
        // Update the connection's last activity time
        $userId = isset($from->battleData->userId) ? $from->battleData->userId : null;
        if ($userId) {
            echo "Received heartbeat from user $userId\n";
            
            // If user is in a battle, update their connection status
            if (isset($from->battleData->battleId)) {
                $battleId = $from->battleData->battleId;
                
                if (isset($this->activeBattles[$battleId])) {
                    // Determine if player is player1 or player2
                    if (isset($this->activeBattles[$battleId]['player1']) && 
                        $this->activeBattles[$battleId]['player1']['id'] === $userId) {
                        // Update player1 connection status
                        $this->activeBattles[$battleId]['player1']['connected'] = true;
                        $this->activeBattles[$battleId]['player1']['last_heartbeat'] = time();
                        // If they were previously marked as disconnected, note that they're back
                        if (isset($this->activeBattles[$battleId]['player1']['disconnect_time'])) {
                            echo "Player 1 reconnected to battle $battleId\n";
                            unset($this->activeBattles[$battleId]['player1']['disconnect_time']);
                            
                            // Notify opponent if they're connected
                            if (isset($this->activeBattles[$battleId]['player2']['connection']) && 
                                $this->activeBattles[$battleId]['player2']['connected']) {
                                $this->activeBattles[$battleId]['player2']['connection']->send(json_encode([
                                    'type' => 'opponentReconnected',
                                    'message' => 'Your opponent has reconnected to the battle'
                                ]));
                            }
                            
                            // Resend battle state to this player
                            $this->sendBattleState($from, $battleId);
                        }
                    } 
                    else if (isset($this->activeBattles[$battleId]['player2']) && 
                             $this->activeBattles[$battleId]['player2']['id'] === $userId) {
                        // Update player2 connection status
                        $this->activeBattles[$battleId]['player2']['connected'] = true;
                        $this->activeBattles[$battleId]['player2']['last_heartbeat'] = time();
                        // If they were previously marked as disconnected, note that they're back
                        if (isset($this->activeBattles[$battleId]['player2']['disconnect_time'])) {
                            echo "Player 2 reconnected to battle $battleId\n";
                            unset($this->activeBattles[$battleId]['player2']['disconnect_time']);
                            
                            // Notify opponent if they're connected
                            if (isset($this->activeBattles[$battleId]['player1']['connection']) && 
                                $this->activeBattles[$battleId]['player1']['connected']) {
                                $this->activeBattles[$battleId]['player1']['connection']->send(json_encode([
                                    'type' => 'opponentReconnected',
                                    'message' => 'Your opponent has reconnected to the battle'
                                ]));
                            }
                            
                            // Resend battle state to this player
                            $this->sendBattleState($from, $battleId);
                        }
                    }
                }
            }
        }
        
        // Send acknowledgment back to client
        $from->send(json_encode([
            'type' => 'heartbeat_ack',
            'timestamp' => time() * 1000,
            'received_timestamp' => $timestamp
        ]));
    }
    
    /**
     * Handle notifications when a user is about to leave the page
     */
    protected function handleLeavingPage(ConnectionInterface $from) {
        $userId = isset($from->battleData->userId) ? $from->battleData->userId : null;
        
        if ($userId) {
            echo "User $userId is leaving the page\n";
            
            // If they're in a battle, mark them as temporarily disconnected
            if (isset($from->battleData->battleId)) {
                $battleId = $from->battleData->battleId;
                
                // Handle the disconnection - using existing method
                $this->handlePlayerDisconnect($userId, $battleId);
            }
        }
    }
    
    /**
     * Handle player ready for next round
     */
    protected function handleReadyForNextRound(ConnectionInterface $from) {
        if (!isset($from->battleData->battleId)) {
            return;
        }
        
        $userId = $from->battleData->userId;
        $battleId = $from->battleData->battleId;
        
        // Check if battle exists
        if (!isset($this->activeBattles[$battleId])) {
            return;
        }
        
        $battle = &$this->activeBattles[$battleId];
        
        // Mark this player as ready for the next question
        if ($battle['player1']['connection'] === $from) {
            $battle['player1']['ready_for_next'] = true;
            
            // Inform the opponent
            if ($battle['player2']['connection']) {
                $battle['player2']['connection']->send(json_encode([
                    'type' => 'opponent_ready_next',
                    'message' => 'Your opponent is ready for the next question'
                ]));
            }
        } else if ($battle['player2']['connection'] === $from) {
            $battle['player2']['ready_for_next'] = true;
            
            // Inform the opponent
            if ($battle['player1']['connection']) {
                $battle['player1']['connection']->send(json_encode([
                    'type' => 'opponent_ready_next',
                    'message' => 'Your opponent is ready for the next question'
                ]));
            }
        }
        
        // Check if both players are ready
        if (isset($battle['player1']['ready_for_next']) && 
            isset($battle['player2']['ready_for_next']) && 
            $battle['player1']['ready_for_next'] && 
            $battle['player2']['ready_for_next']) {
            
            // Move to next question or end battle if all questions have been answered
            $this->moveToNextQuestion($battleId);
        }
    }

    protected function findMatch(ConnectionInterface $conn, $data) {
        if (!isset($conn->battleData->userId)) {
            $conn->send(json_encode([
                'type' => 'error', 
                'error' => 'You must be logged in to find a match'
            ]));
            return;
        }
        
        // Check if user is already in a battle
        if ($conn->battleData->inBattle) {
            $conn->send(json_encode([
                'type' => 'error',
                'error' => 'You are already in a battle'
            ]));
            return;
        }

        // Get battle preferences
        $numQuestions = $data['numQuestions'] ?? 5;
        $difficulty = $data['difficulty'] ?? 'medium';
        $subjects = $data['subjects'] ?? [];
        $battleType = $data['battleType'] ?? ($conn->battleData->battleType ?? 'arena');
        
        // Ensure battleType is stored in user's data for matching
        $conn->battleData->battleType = $battleType;
        
        // Add user to waiting queue
        $conn->battleData->state = 'waiting';
        $conn->battleData->preferences = [
            'numQuestions' => $numQuestions,
            'difficulty' => $difficulty,
            'subjects' => $subjects,
            'battleType' => $battleType
        ];
        
        // Find a matching opponent
        foreach ($this->users as $userId => $potentialOpponent) {
            // Skip if it's the same user
            if ($userId === $conn->battleData->userId) {
                continue;
            }
            
            // Skip if the user is not waiting for a match
            if ($potentialOpponent->battleData->state !== 'waiting') {
                continue;
            }
            
            // Skip if the battle types don't match
            if ($potentialOpponent->battleData->battleType !== $battleType) {
                continue;
            }
            
            // Check if preferences match
            $opponentPrefs = $potentialOpponent->battleData->preferences;
            
            // For now, we'll match if the number of questions and difficulty match
            if ($opponentPrefs['numQuestions'] == $numQuestions && 
                $opponentPrefs['difficulty'] == $difficulty) {
                
                // If either player has subjects specified, check for overlap
                $subjectMatch = true;
                if (!empty($subjects) && !empty($opponentPrefs['subjects'])) {
                    $commonSubjects = array_intersect($subjects, $opponentPrefs['subjects']);
                    $subjectMatch = !empty($commonSubjects);
                }
                
                if ($subjectMatch) {
                    // We found a match!
                    $this->createBattle($conn, $potentialOpponent, array_merge(
                        $conn->battleData->preferences,
                        ['subjects' => $subjects && $opponentPrefs['subjects'] ? array_values(array_intersect($subjects, $opponentPrefs['subjects'])) : []]
                    ));
                    return;
                }
            }
        }
        
        // If we get here, we didn't find a match
        // Tell the client how many players are waiting
        $waitingCount = 0;
        foreach ($this->users as $user) {
            if ($user->battleData->state === 'waiting' && 
                $user->battleData->battleType === $battleType) {
                $waitingCount++;
            }
        }
        
        $conn->send(json_encode([
            'type' => 'waitingCount',
            'count' => $waitingCount,
            'battleType' => $battleType
        ]));
    }

    /**
     * Create a new battle when two players are matched
     */
    protected function createBattle(ConnectionInterface $player1Conn, ConnectionInterface $player2Conn, $preferences) {
        // Generate a unique battle ID
        $battleId = uniqid('battle_');
        
        // Update player states
        $player1Conn->battleData->battleId = $battleId;
        $player2Conn->battleData->battleId = $battleId;
        $player1Conn->battleData->state = 'matched';
        $player2Conn->battleData->state = 'matched';
        $player1Conn->battleData->inBattle = true;
        $player2Conn->battleData->inBattle = true;
        
        // Get battle configuration
        $numQuestions = $preferences['numQuestions'] ?? 5;
        $difficulty = $preferences['difficulty'] ?? 'medium';
        $subjects = $preferences['subjects'] ?? ['general'];
        
        // Ensure battle type consistency - both players should have the same type
        $battleType = $player1Conn->battleData->battleType ?? 'arena';
        if (isset($player2Conn->battleData->battleType) && $battleType !== $player2Conn->battleData->battleType) {
            echo "WARNING: Battle types don't match: {$battleType} vs {$player2Conn->battleData->battleType}. Using {$battleType}.\n";
        }
        
        // Generate a proper UUID for database storage
        $databaseBattleId = $this->generateUUID();
        echo "Generated UUID for new battle {$battleId}: {$databaseBattleId}\n";
        
        // Override number of questions for quick battles
        if ($battleType === 'quick' && $numQuestions > 3) {
            $numQuestions = 3; // Quick battles have fewer questions
            echo "Quick battle detected: limiting to 3 questions\n";
        }
        
        // Generate questions based on preferences
        $questions = $this->generateQuestions([
            'battleType' => $battleType,
            'questionCount' => $numQuestions,
            'difficulty' => $difficulty,
            'subjects' => $subjects
        ]);
        
        // Create battle record
        $this->activeBattles[$battleId] = [
            'id' => $battleId,
            'database_battle_id' => $databaseBattleId, // Store UUID for database operations
            'player1' => [
                'connection' => $player1Conn,
                'id' => $player1Conn->battleData->userId,
                'username' => $player1Conn->battleData->username,
                'avatar' => $player1Conn->battleData->avatar,
                'ready' => false,
                'next_ready' => false,
                'score' => 0,
                'answers' => [],
                'connected' => true
            ],
            'player2' => [
                'connection' => $player2Conn,
                'id' => $player2Conn->battleData->userId,
                'username' => $player2Conn->battleData->username,
                'avatar' => $player2Conn->battleData->avatar,
                'ready' => false,
                'next_ready' => false,
                'score' => 0,
                'answers' => [],
                'connected' => true
            ],
            'questions' => $questions,
            'current_question' => 0,
            'start_time' => null,
            'end_time' => null,
            'battleType' => $battleType,
            'config' => [
                'subjects' => $subjects,
                'difficulty' => $difficulty,
                'questionCount' => $numQuestions,
                'battleType' => $battleType
            ],
            'battle_result' => 'Incomplete',
            'battle_type' => $battleType // Make sure battle_type exists for database compatibility
        ];
        
        // Send match found notifications to both players
        $matchFoundData = [
            'type' => 'matchFound',
            'matchId' => $battleId,
            'battleType' => $battleType,
            'database_battle_id' => $databaseBattleId,
            'questionCount' => $numQuestions
        ];
        
        // Add player specific data to notifications
        $player1Data = $matchFoundData;
        $player1Data['opponent'] = [
            'userId' => $player2Conn->battleData->userId,
            'username' => $player2Conn->battleData->username,
            'avatar' => $player2Conn->battleData->avatar
        ];
        
        $player2Data = $matchFoundData;
        $player2Data['opponent'] = [
            'userId' => $player1Conn->battleData->userId,
            'username' => $player1Conn->battleData->username,
            'avatar' => $player1Conn->battleData->avatar
        ];
        
        // Send notifications
        $player1Conn->send(json_encode($player1Data));
        $player2Conn->send(json_encode($player2Data));
        
        echo "Created {$battleType} battle {$battleId} (UUID: {$databaseBattleId}) between {$player1Conn->battleData->username} and {$player2Conn->battleData->username}\n";
        
        return $battleId;
    }
}

// Set up the server
try {
    echo "Starting Battle WebSocket Server on port 8080...\n";
    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new BattleServer()
            )
        ),
        8080
    );

    echo "Battle WebSocket Server is running on port 8080. Press Ctrl+C to stop.\n";
    $server->run();
} catch (\Exception $e) {
    echo "Error starting server: " . $e->getMessage() . "\n";
    exit(1);
} 