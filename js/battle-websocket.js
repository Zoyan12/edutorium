/**
 * Edutorium Battle WebSocket Module
 * 
 * This module handles WebSocket connections for the battle system.
 * It automatically uses the WebSocket URL provided by the server.
 */

// WebSocket connection object
let battleWs = null;

// Configuration
const BattleWebSocket = {
    // WebSocket URL (will be set dynamically)
    url: null,
    
    // Connection status
    isConnected: false,
    
    // Event callbacks
    callbacks: {
        onOpen: [],
        onMessage: [],
        onClose: [],
        onError: []
    },
    
    // Last received data
    lastData: null,
    
    /**
     * Initialize the WebSocket connection
     * @param {string} url - The WebSocket URL (if null, uses the URL from the websocket-url element)
     * @returns {Promise} - Resolves when connected, rejects on failure
     */
    init: function(url = null) {
        return new Promise((resolve, reject) => {
            // If already connected, disconnect first
            if (this.isConnected) {
                this.disconnect();
            }
            
            // Get the URL from the element if not provided
            if (!url) {
                const urlElement = document.getElementById('websocket-url');
                if (urlElement) {
                    url = urlElement.textContent.trim();
                }
            }
            
            // If still no URL, use default with current hostname
            if (!url) {
                url = `ws://${window.location.hostname}:8080`;
            }
            
            this.url = url;
            
            try {
                battleWs = new WebSocket(url);
                
                // Setup event handlers
                battleWs.onopen = (event) => {
                    this.isConnected = true;
                    this._triggerCallbacks('onOpen', event);
                    resolve();
                };
                
                battleWs.onmessage = (event) => {
                    try {
                        const data = JSON.parse(event.data);
                        this.lastData = data;
                        this._triggerCallbacks('onMessage', data);
                    } catch (error) {
                        console.error('Error parsing WebSocket message:', error);
                    }
                };
                
                battleWs.onclose = (event) => {
                    this.isConnected = false;
                    this._triggerCallbacks('onClose', event);
                };
                
                battleWs.onerror = (event) => {
                    this._triggerCallbacks('onError', event);
                    reject(event);
                };
            } catch (error) {
                reject(error);
            }
        });
    },
    
    /**
     * Disconnect from the WebSocket server
     */
    disconnect: function() {
        if (battleWs && this.isConnected) {
            battleWs.close();
            this.isConnected = false;
        }
    },
    
    /**
     * Send a message to the WebSocket server
     * @param {Object} message - The message to send
     * @returns {boolean} - True if sent successfully, false otherwise
     */
    send: function(message) {
        if (!this.isConnected || !battleWs) {
            console.error('Cannot send message: Not connected to WebSocket server');
            return false;
        }
        
        try {
            battleWs.send(JSON.stringify(message));
            return true;
        } catch (error) {
            console.error('Error sending WebSocket message:', error);
            return false;
        }
    },
    
    /**
     * Register an event callback
     * @param {string} event - The event name (onOpen, onMessage, onClose, onError)
     * @param {Function} callback - The callback function
     */
    on: function(event, callback) {
        if (this.callbacks[event]) {
            this.callbacks[event].push(callback);
        }
    },
    
    /**
     * Remove an event callback
     * @param {string} event - The event name
     * @param {Function} callback - The callback function to remove
     */
    off: function(event, callback) {
        if (this.callbacks[event]) {
            this.callbacks[event] = this.callbacks[event].filter(cb => cb !== callback);
        }
    },
    
    /**
     * Trigger all registered callbacks for an event
     * @param {string} event - The event name
     * @param {*} data - The data to pass to the callbacks
     * @private
     */
    _triggerCallbacks: function(event, data) {
        if (this.callbacks[event]) {
            this.callbacks[event].forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(`Error in ${event} callback:`, error);
                }
            });
        }
    },
    
    /**
     * Login to the battle server
     * @param {string} userId - The user ID
     * @param {string} username - The username
     * @param {string} avatar - The avatar URL
     * @returns {boolean} - True if sent successfully, false otherwise
     */
    login: function(userId, username, avatar = 'default-avatar.png') {
        return this.send({
            action: 'login',
            userId: userId,
            username: username,
            avatar: avatar
        });
    },
    
    /**
     * Find a match
     * @param {Object} config - The match configuration
     * @param {string} battleType - The battle type ('quick' or 'arena')
     * @returns {boolean} - True if sent successfully, false otherwise
     */
    findMatch: function(config, battleType = 'arena') {
        return this.send({
            action: 'find_match',
            config: config,
            battleType: battleType
        });
    },
    
    /**
     * Cancel matchmaking
     * @returns {boolean} - True if sent successfully, false otherwise
     */
    cancelMatchmaking: function() {
        return this.send({
            action: 'cancel_matchmaking'
        });
    },
    
    /**
     * Send a heartbeat
     * @returns {boolean} - True if sent successfully, false otherwise
     */
    sendHeartbeat: function() {
        return this.send({
            action: 'heartbeat',
            timestamp: Date.now()
        });
    }
}; 