import config from './config.js';

/**
 * WebSocket connection manager
 * Centralizes WebSocket functionality with automatic reconnection
 */
class WebSocketManager {
    constructor() {
        this.socket = null;
        this.url = config.websocket.url;
        this.reconnectInterval = config.websocket.reconnectInterval;
        this.maxReconnectAttempts = config.websocket.maxReconnectAttempts;
        this.reconnectAttempts = 0;
        this.listeners = {
            message: [],
            open: [],
            close: [],
            error: []
        };
        this.isConnected = false;
    }

    /**
     * Connect to the WebSocket server
     */
    connect() {
        if (this.socket) {
            this.socket.close();
        }

        try {
            this.socket = new WebSocket(this.url);
            
            this.socket.onopen = (event) => {
                console.log('WebSocket connected');
                this.isConnected = true;
                this.reconnectAttempts = 0;
                this._notifyListeners('open', event);
            };
            
            this.socket.onclose = (event) => {
                console.log('WebSocket disconnected');
                this.isConnected = false;
                this._notifyListeners('close', event);
                this._attemptReconnect();
            };
            
            this.socket.onerror = (error) => {
                console.error('WebSocket error:', error);
                this._notifyListeners('error', error);
            };
            
            this.socket.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    this._notifyListeners('message', data);
                } catch (e) {
                    console.error('Error parsing WebSocket message:', e);
                    this._notifyListeners('message', event.data);
                }
            };
        } catch (error) {
            console.error('Error connecting to WebSocket:', error);
            this._attemptReconnect();
        }
    }

    /**
     * Send data through the WebSocket
     * @param {Object|string} data - Data to send
     * @returns {boolean} - Whether the data was sent
     */
    send(data) {
        if (!this.isConnected) {
            console.warn('WebSocket not connected, cannot send data');
            return false;
        }

        try {
            const dataToSend = typeof data === 'object' ? JSON.stringify(data) : data;
            this.socket.send(dataToSend);
            return true;
        } catch (error) {
            console.error('Error sending data through WebSocket:', error);
            return false;
        }
    }

    /**
     * Add an event listener
     * @param {string} event - Event type (message, open, close, error)
     * @param {Function} callback - Callback function
     */
    addEventListener(event, callback) {
        if (!this.listeners[event]) {
            this.listeners[event] = [];
        }
        this.listeners[event].push(callback);
    }

    /**
     * Remove an event listener
     * @param {string} event - Event type
     * @param {Function} callback - Callback function to remove
     */
    removeEventListener(event, callback) {
        if (this.listeners[event]) {
            this.listeners[event] = this.listeners[event].filter(cb => cb !== callback);
        }
    }

    /**
     * Close the WebSocket connection
     */
    disconnect() {
        if (this.socket) {
            this.socket.close();
            this.socket = null;
        }
    }

    /**
     * Notify all listeners of an event
     * @private
     */
    _notifyListeners(event, data) {
        if (this.listeners[event]) {
            this.listeners[event].forEach(callback => {
                try {
                    callback(data);
                } catch (e) {
                    console.error(`Error in ${event} listener:`, e);
                }
            });
        }
    }

    /**
     * Attempt to reconnect to the WebSocket server
     * @private
     */
    _attemptReconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            console.log(`Attempting to reconnect (${this.reconnectAttempts}/${this.maxReconnectAttempts})...`);
            
            setTimeout(() => {
                this.connect();
            }, this.reconnectInterval);
        } else {
            console.error(`Failed to reconnect after ${this.maxReconnectAttempts} attempts`);
        }
    }

    /**
     * Update the WebSocket URL
     * @param {string} newUrl - New WebSocket URL
     */
    updateUrl(newUrl) {
        this.url = newUrl;
        
        // Reconnect with the new URL if currently connected
        if (this.isConnected) {
            this.connect();
        }
    }
}

// Create and export a singleton instance
const webSocketManager = new WebSocketManager();
export default webSocketManager; 