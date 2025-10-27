/**
 * Battle Manager - Handles battle state, UI updates, and WebSocket communication
 */

class BattleManager {
    constructor(userData) {
        this.userData = userData;
        this.accessToken = userData.accessToken || null;
        
        // Get battleId from URL if present
        const urlParams = new URLSearchParams(window.location.search);
        const battleId = urlParams.get('matchId');
        
        this.state = {
            battleId: battleId,
            currentQuestion: 0,
            totalQuestions: 5,
            timer: 30,
            timerInterval: null,
            player1Score: 0,
            player2Score: 0,
            player1Name: userData.username,
            player2Name: 'Waiting...',
            player1Avatar: userData.avatar,
            player2Avatar: 'default-avatar.png',
            answeredQuestion: false,
            currentQuestionData: null,
            battleType: 'arena',
            wsConnected: false
        };

        this.ws = null;
        this.initWebSocket();
    }

    /**
     * Initialize WebSocket connection
     */
    initWebSocket() {
        const wsUrl = document.getElementById('websocket-url')?.textContent.trim();
        if (!wsUrl) {
            console.error('WebSocket URL not found');
            this.showError('Unable to connect to battle server');
            return;
        }

        BattleWebSocket.init(wsUrl)
            .then(() => {
                this.ws = BattleWebSocket;
                this.state.wsConnected = true;
                this.updateConnectionStatus('connected');
                
                // Register message handlers
                this.ws.on('onMessage', (data) => this.handleMessage(data));
                this.ws.on('onError', (error) => this.handleError(error));
                this.ws.on('onClose', () => this.handleClose());
                
                // Login to battle server with auth token if available
                const loginData = {
                    action: 'login',
                    userId: this.userData.userId,
                    username: this.userData.username,
                    avatar: this.userData.avatar
                };
                
                // Add access token if available
                if (this.accessToken) {
                    loginData.token = this.accessToken;
                }
                
                this.ws.send(loginData);
                
                console.log('WebSocket connected and logged in');
            })
            .catch((error) => {
                console.error('Failed to connect to WebSocket:', error);
                this.showError('Failed to connect to battle server');
                this.updateConnectionStatus('disconnected');
            });
    }

    /**
     * Start a battle with given match ID
     */
    startBattle(matchId) {
        this.state.battleId = matchId;
        console.log('Starting battle:', matchId);
        
        // Show loading
        this.showLoading('Connecting to opponent...');
        
        // If already logged in, join the battle now
        if (this.ws && this.ws.isConnected) {
            const joinMessage = {
                action: 'join_match',
                matchId: matchId,
                userId: this.userData.userId
            };
            console.log('Sending join_match request:', JSON.stringify(joinMessage));
            this.ws.send(joinMessage);
            console.log('join_match request sent for:', matchId);
        } else {
            console.error('Cannot join battle - WebSocket not connected');
        }
        // Otherwise, wait for loginSuccess to trigger join_match
    }

    /**
     * Handle WebSocket messages
     */
    handleMessage(data) {
        console.log('Received message:', data);

        // Check for message type or action field
        const messageType = data.type || data.action;
        
        console.log('Message type detected:', messageType);

        switch (messageType) {
            case 'matchFound':
                this.handleMatchFound(data);
                break;

            case 'battleStart':
                this.handleBattleStart(data);
                break;

            case 'nextQuestion':
                this.handleNextQuestion(data);
                break;

            case 'opponentAnswered':
                this.handleOpponentAnswered(data);
                break;

            case 'questionComplete':
                this.handleQuestionComplete(data);
                break;

            case 'battleEnd':
                this.handleBattleEnd(data);
                break;
                
            case 'joinMatchSuccess':
            case 'join_success':
                this.handleJoinMatchSuccess(data);
                break;
                
            case 'battleStart':
            case 'battle_started':
                this.handleBattleStart(data);
                break;

            case 'error':
                this.showError(data.error || data.message);
                break;

            case 'ping':
                // Handle ping - send pong response
                this.handlePing(data);
                break;

            case 'waitingCount':
                // Handle waiting count updates
                this.handleWaitingCount(data);
                break;
                
            case 'loginSuccess':
            case 'login_success':
                this.handleLoginSuccess(data);
                break;

            default:
                if (messageType !== 'ping' && messageType !== 'pong') {
                    console.warn('Unknown message type:', messageType, 'Full data:', JSON.stringify(data));
                    // Try to handle as error if it has an error message
                    if (data.error || data.message) {
                        this.showError(data.error || data.message);
                    }
                }
        }
    }
    
    /**
     * Handle login success from server
     */
    handleLoginSuccess(data) {
        console.log('Login successful:', data);
        // If we have a battleId, join the battle now
        if (this.state.battleId && this.ws && this.ws.isConnected) {
            console.log('Attempting to join battle:', this.state.battleId);
            
            // Use the battle-websocket.js send method properly
            const joinMessage = {
                action: 'join_match',
                matchId: this.state.battleId,
                userId: this.userData.userId  // Include userId for server validation
            };
            
            try {
                // Check if send method exists and how to call it
                if (typeof this.ws.send === 'function') {
                    console.log('Calling ws.send with:', JSON.stringify(joinMessage));
                    const result = this.ws.send(joinMessage);
                    console.log('join_match sent, result:', result);
                    
                    // Set a timeout to show error if no response in 5 seconds
                    this.joinTimeout = setTimeout(() => {
                        console.error('No response from server after 5 seconds for join_match');
                        this.showError('Battle not found or server error. Please try again.');
                    }, 5000);
                } else {
                    console.error('ws.send is not a function:', typeof this.ws.send);
                }
            } catch (error) {
                console.error('Error sending join_match:', error);
                this.showError('Failed to join battle. Please refresh the page.');
            }
        } else {
            console.log('No battleId or not connected. battleId:', this.state.battleId, 'connected:', this.ws?.isConnected);
        }
    }
    
    /**
     * Handle ping message
     */
    handlePing(data) {
        // Send pong response
        if (this.ws && this.ws.isConnected) {
            this.ws.send({
                action: 'pong',
                timestamp: Date.now()
            });
        }
    }
    
    /**
     * Handle waiting count updates
     */
    handleWaitingCount(data) {
        // Could show waiting count to user if needed
        console.log('Players waiting:', data.count);
    }

    /**
     * Handle match found
     */
    handleMatchFound(data) {
        console.log('Match found:', data);
        this.updateOpponentInfo(data.opponent);
    }

    /**
     * Handle battle start
     */
    handleBattleStart(data) {
        console.log('Battle started:', data);
        this.hideLoading();
        
        this.state.battleType = data.battleType || 'arena';
        this.updateBattleType();
        
        // Update opponent info
        if (data.players && data.players.length >= 2) {
            const myUserId = this.userData.userId;
            const opponent = data.players.find(p => p.userId !== myUserId);
            if (opponent) {
                this.updateOpponentInfo(opponent);
            }
        }
        
        // Store initial question data if provided
        if (data.question) {
            // Handle both 'current' and 'current_round' field names
            const current = data.current || data.current_round || 1;
            const total = data.total || data.total_rounds || 5;
            this.displayQuestion(data.question, current, total);
            this.startTimer(data.time_limit || 30); // Start timer for first question
        } else {
            console.warn('No question in battleStart message');
        }
    }
    
    /**
     * Handle join match success
     */
    handleJoinMatchSuccess(data) {
        console.log('Successfully joined match:', data);
        
        // Clear the timeout if it exists
        if (this.joinTimeout) {
            clearTimeout(this.joinTimeout);
            delete this.joinTimeout;
        }
        
        this.updateConnectionStatus('connected');
        // Show loading while waiting for battle state
        this.showLoading('Loading battle...');
    }

    /**
     * Handle next question
     */
    handleNextQuestion(data) {
        console.log('Next question:', data);
        
        // Handle both 'current' and 'current_round' field names
        this.state.currentQuestion = data.current || data.current_round || this.state.currentQuestion + 1;
        this.state.totalQuestions = data.total || data.total_rounds || 5;
        this.state.answeredQuestion = false;
        this.state.currentQuestionData = data.question;

        // Reset UI
        this.resetAnswerButtons();
        this.hideAnswerIndicators();
        this.hideOpponentNotification();

        // Display question
        this.displayQuestion(data.question, this.state.currentQuestion, this.state.totalQuestions);
        
        // Start timer
        this.startTimer(data.timer || data.time_limit || 30);
    }

    /**
     * Handle opponent answered
     */
    handleOpponentAnswered(data) {
        console.log('Opponent answered');
        this.showOpponentNotification();
        this.showAnswerIndicator('player2Answered');
    }

    /**
     * Handle question complete
     */
    handleQuestionComplete(data) {
        console.log('Question complete:', data);
        
        this.stopTimer();
        
        // Show correct/incorrect feedback
        this.showQuestionResults(data);

        // Update scores
        if (data.player1Score !== undefined) {
            this.state.player1Score = data.player1Score;
        }
        if (data.player2Score !== undefined) {
            this.state.player2Score = data.player2Score;
        }
        this.updateScores();

        // Auto-advance to next question after 3 seconds
        setTimeout(() => {
            // Server will send next question or battle end
        }, 3000);
    }

    /**
     * Handle battle end
     */
    handleBattleEnd(data) {
        console.log('Battle ended:', data);
        
        this.stopTimer();
        this.hideLoading();

        // Determine result
        const result = this.determineBattleResult(data);
        
        // Show result modal
        this.showResultModal(result, data);
    }

    /**
     * Display a question
     */
    displayQuestion(question, current, total) {
        // Update question counter
        document.getElementById('currentQuestion').textContent = current;
        document.getElementById('totalQuestions').textContent = total;

        // Display question text
        const questionText = document.getElementById('questionText');
        if (questionText && question.question) {
            questionText.textContent = question.question;
            questionText.style.display = 'block';
        }

        // Hide placeholder, show question image if available
        const placeholder = document.querySelector('.question-placeholder');
        const questionImage = document.getElementById('questionImage');
        if (placeholder) placeholder.style.display = 'none';
        if (questionImage) {
            if (question.image_url) {
                questionImage.src = question.image_url;
                questionImage.style.display = 'block';
            } else {
                questionImage.style.display = 'none';
            }
        }

        // Update choice buttons - support both old format (choicea, choiceb, etc.) and new format (options array)
        const choices = ['A', 'B', 'C', 'D'];
        const options = question.options || [];
        
        choices.forEach((choice, index) => {
            const btn = document.getElementById(`choice${choice}`);
            const text = document.getElementById(`choice${choice}Text`);
            
            if (btn && text) {
                // Get option text from either options array or individual fields
                const optionText = options[index] || question[`choice${choice.toLowerCase()}`] || '';
                
                if (optionText) {
                    text.textContent = optionText;
                    btn.disabled = false;
                    // Remove old listener and add new one
                    btn.replaceWith(btn.cloneNode(true));
                    document.getElementById(`choice${choice}`).addEventListener('click', () => this.submitAnswer(choice));
                }
            }
        });
    }

    /**
     * Submit an answer
     */
    submitAnswer(choice) {
        if (this.state.answeredQuestion) {
            console.log('Already answered this question');
            return;
        }

        if (!this.state.currentQuestionData) {
            console.error('No current question data');
            return;
        }

        const timeTaken = 30 - this.state.timer;
        this.state.answeredQuestion = true;

        // Disable all buttons
        this.disableAnswerButtons();
        this.highlightSelectedAnswer(choice);
        this.showAnswerIndicator('player1Answered');

        // Send answer to server
        if (this.ws && this.ws.isConnected) {
            this.ws.send({
                action: 'submit_answer',
                battle_id: this.state.battleId,
                question_id: this.state.currentQuestionData.question_id || this.state.currentQuestion,
                answer: choice,
                time_taken_ms: timeTaken * 1000
            });

            console.log('Answer submitted:', choice, 'Time:', timeTaken);
        } else {
            console.error('WebSocket not connected');
        }
    }

    /**
     * Start countdown timer
     */
    startTimer(seconds) {
        this.state.timer = seconds;
        this.updateTimer();

        this.state.timerInterval = setInterval(() => {
            this.state.timer--;
            this.updateTimer();

            if (this.state.timer <= 0) {
                this.stopTimer();
                // Auto-submit (timeout)
                if (!this.state.answeredQuestion && this.state.currentQuestionData) {
                    this.submitAnswer(null); // Submit null for timeout
                }
            }
        }, 1000);
    }

    /**
     * Stop timer
     */
    stopTimer() {
        if (this.state.timerInterval) {
            clearInterval(this.state.timerInterval);
            this.state.timerInterval = null;
        }
    }

    /**
     * Update timer display
     */
    updateTimer() {
        const timerValue = document.getElementById('timerValue');
        if (timerValue) {
            timerValue.textContent = this.state.timer;
        }

        // Update SVG progress
        const progressCircle = document.getElementById('timerProgress');
        if (progressCircle) {
            const circumference = 2 * Math.PI * 45; // radius = 45
            const remaining = this.state.timer / 30;
            const offset = circumference * (1 - remaining);
            progressCircle.style.strokeDashoffset = offset;

            // Add warning/danger classes
            if (this.state.timer <= 10) {
                progressCircle.classList.add('danger');
                progressCircle.classList.remove('warning');
            } else if (this.state.timer <= 15) {
                progressCircle.classList.add('warning');
            } else {
                progressCircle.classList.remove('warning', 'danger');
            }
        }
    }

    /**
     * Update scores on screen
     */
    updateScores() {
        const p1Score = document.getElementById('player1Score');
        const p2Score = document.getElementById('player2Score');
        
        if (p1Score) p1Score.textContent = this.state.player1Score;
        if (p2Score) p2Score.textContent = this.state.player2Score;
    }

    /**
     * Update opponent information
     */
    updateOpponentInfo(opponent) {
        if (!opponent) return;

        this.state.player2Name = opponent.username || opponent.name || 'Opponent';
        this.state.player2Avatar = opponent.avatar || 'default-avatar.png';

        const p2Name = document.getElementById('player2Name');
        const p2Avatar = document.getElementById('player2Avatar');

        if (p2Name) p2Name.textContent = this.state.player2Name;
        if (p2Avatar) p2Avatar.src = `../img/${this.state.player2Avatar}`;
    }

    /**
     * Show answer indicators
     */
    showAnswerIndicator(player) {
        const indicator = document.getElementById(player + 'Answered');
        if (indicator) {
            indicator.classList.add('show');
        }
    }

    /**
     * Hide answer indicators
     */
    hideAnswerIndicators() {
        ['player1Answered', 'player2Answered'].forEach(player => {
            const indicator = document.getElementById(player + 'Answered');
            if (indicator) {
                indicator.classList.remove('show');
            }
        });
    }

    /**
     * Show opponent notification
     */
    showOpponentNotification() {
        const notification = document.getElementById('opponentNotification');
        if (notification) {
            notification.style.display = 'flex';
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }
    }

    /**
     * Hide opponent notification
     */
    hideOpponentNotification() {
        const notification = document.getElementById('opponentNotification');
        if (notification) {
            notification.style.display = 'none';
        }
    }

    /**
     * Highlight selected answer
     */
    highlightSelectedAnswer(choice) {
        const btn = document.getElementById(`choice${choice}`);
        if (btn) {
            btn.classList.add('selected');
        }
    }

    /**
     * Show question results (correct/incorrect feedback)
     */
    showQuestionResults(data) {
        const correctAnswer = data.correctAnswer;
        const myAnswer = data.myAnswer || data.yourAnswer;
        
        // Highlight correct answer
        const correctBtn = document.getElementById(`choice${correctAnswer}`);
        if (correctBtn) {
            correctBtn.classList.add('correct');
        }

        // Highlight incorrect answer if applicable
        if (myAnswer && myAnswer !== correctAnswer) {
            const myBtn = document.getElementById(`choice${myAnswer}`);
            if (myBtn) {
                myBtn.classList.add('incorrect');
            }
        }
    }

    /**
     * Reset answer buttons
     */
    resetAnswerButtons() {
        ['A', 'B', 'C', 'D'].forEach(choice => {
            const btn = document.getElementById(`choice${choice}`);
            if (btn) {
                btn.classList.remove('selected', 'correct', 'incorrect');
                btn.disabled = false;
                // Remove old event listeners by cloning
                const newBtn = btn.cloneNode(true);
                btn.parentNode.replaceChild(newBtn, btn);
            }
        });
    }

    /**
     * Disable answer buttons
     */
    disableAnswerButtons() {
        ['A', 'B', 'C', 'D'].forEach(choice => {
            const btn = document.getElementById(`choice${choice}`);
            if (btn) {
                btn.disabled = true;
            }
        });
    }

    /**
     * Update battle type label
     */
    updateBattleType() {
        const label = document.getElementById('battleTypeLabel');
        if (label) {
            label.textContent = this.state.battleType.charAt(0).toUpperCase() + 
                               this.state.battleType.slice(1) + ' Battle';
        }
    }

    /**
     * Update connection status
     */
    updateConnectionStatus(status) {
        const statusEl = document.getElementById('connectionStatus');
        if (statusEl) {
            statusEl.className = `connection-status ${status}`;
            const span = statusEl.querySelector('span');
            if (span) {
                span.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            }
        }
    }

    /**
     * Determine battle result
     */
    determineBattleResult(data) {
        if (data.player1Score > data.player2Score) {
            return this.userData.userId === data.player1Id ? 'win' : 'loss';
        } else if (data.player2Score > data.player1Score) {
            return this.userData.userId === data.player2Id ? 'win' : 'loss';
        } else {
            return 'draw';
        }
    }

    /**
     * Show result modal
     */
    showResultModal(result, data) {
        const modal = document.getElementById('resultModal');
        const title = document.getElementById('resultTitle');
        const icon = document.getElementById('resultIcon');
        const finalScore1 = document.getElementById('finalScore1');
        const correctAnswers1 = document.getElementById('correctAnswers1');
        const finalScore2 = document.getElementById('finalScore2');

        if (!modal) return;

        // Update title and icon
        if (title) {
            if (result === 'win') {
                title.textContent = 'Victory!';
                if (icon) {
                    icon.innerHTML = '<i class="fas fa-trophy"></i>';
                    icon.classList.add('winner');
                }
            } else if (result === 'loss') {
                title.textContent = 'Defeat';
                if (icon) {
                    icon.innerHTML = '<i class="fas fa-times-circle"></i>';
                }
            } else {
                title.textContent = 'Draw!';
                if (icon) {
                    icon.innerHTML = '<i class="fas fa-handshake"></i>';
                }
            }
        }

        // Update stats
        if (finalScore1) {
            finalScore1.textContent = data.player1Score || this.state.player1Score;
        }
        if (correctAnswers1) {
            correctAnswers1.textContent = data.player1Correct || 0;
        }
        if (finalScore2) {
            finalScore2.textContent = data.player2Score || this.state.player2Score;
        }

        // Show modal
        modal.style.display = 'flex';

        // Setup button handlers
        document.getElementById('rematchBtn')?.addEventListener('click', () => {
            window.location.href = 'dashboard.php';
        });

        document.getElementById('backToDashboardBtn')?.addEventListener('click', () => {
            window.location.href = 'dashboard.php';
        });
    }

    /**
     * Show loading overlay
     */
    showLoading(text = 'Loading...') {
        const overlay = document.getElementById('loadingOverlay');
        const loadingText = document.getElementById('loadingText');
        
        if (overlay) overlay.style.display = 'flex';
        if (loadingText) loadingText.textContent = text;
    }

    /**
     * Hide loading overlay
     */
    hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) overlay.style.display = 'none';
    }

    /**
     * Show error message
     */
    showError(message) {
        console.error(message);
        alert(message);
    }

    /**
     * Handle WebSocket errors
     */
    handleError(error) {
        console.error('WebSocket error:', error);
        this.updateConnectionStatus('disconnected');
        this.showError('Connection error. Please refresh the page.');
    }

    /**
     * Handle WebSocket close
     */
    handleClose() {
        console.log('WebSocket closed');
        this.state.wsConnected = false;
        this.updateConnectionStatus('disconnected');
    }
}

