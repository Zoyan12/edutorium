import config from './config.js';
import webSocketManager from './websocket.js';
import { supabase } from './supabase.js';

/**
 * Configuration Manager
 * Provides utility methods to update configuration values at runtime
 */
class ConfigManager {
    constructor() {
        this.config = config;
        this.listeners = {};
    }

    /**
     * Get the current configuration
     * @returns {Object} The current application configuration
     */
    getConfig() {
        return this.config;
    }

    /**
     * Update a configuration value
     * @param {string} path - Dot notation path to the config value (e.g. 'websocket.url')
     * @param {any} value - New value
     * @returns {boolean} Whether the update was successful
     */
    updateConfig(path, value) {
        try {
            const pathParts = path.split('.');
            let current = this.config;
            
            // Navigate to the second-to-last element in the path
            for (let i = 0; i < pathParts.length - 1; i++) {
                const part = pathParts[i];
                if (current[part] === undefined) {
                    console.error(`Invalid config path: ${path}`);
                    return false;
                }
                current = current[part];
            }
            
            // Update the value
            const lastPart = pathParts[pathParts.length - 1];
            const oldValue = current[lastPart];
            current[lastPart] = value;
            
            // Special handling for certain config types
            this._handleSpecialConfig(path, value, oldValue);
            
            // Notify listeners
            this._notifyListeners(path, value, oldValue);
            
            return true;
        } catch (error) {
            console.error('Error updating config:', error);
            return false;
        }
    }

    /**
     * Add a listener for configuration changes
     * @param {string} path - Dot notation path to listen for changes
     * @param {Function} callback - Callback function that receives (newValue, oldValue)
     */
    addListener(path, callback) {
        if (!this.listeners[path]) {
            this.listeners[path] = [];
        }
        this.listeners[path].push(callback);
    }

    /**
     * Remove a listener
     * @param {string} path - Dot notation path
     * @param {Function} callback - Callback function to remove
     */
    removeListener(path, callback) {
        if (this.listeners[path]) {
            this.listeners[path] = this.listeners[path].filter(cb => cb !== callback);
        }
    }

    /**
     * Handle special configuration updates that require additional actions
     * @private
     */
    _handleSpecialConfig(path, newValue, oldValue) {
        // Handle WebSocket URL updates
        if (path === 'websocket.url' && newValue !== oldValue) {
            webSocketManager.updateUrl(newValue);
        }
        
        // More special cases can be added here as needed
    }

    /**
     * Notify listeners of a configuration change
     * @private
     */
    _notifyListeners(path, newValue, oldValue) {
        if (this.listeners[path]) {
            this.listeners[path].forEach(callback => {
                try {
                    callback(newValue, oldValue);
                } catch (e) {
                    console.error(`Error in config change listener for ${path}:`, e);
                }
            });
        }
    }

    /**
     * Save configuration to database
     * @param {Object} newConfig - Updated configuration object
     * @returns {Promise<Object>} Result of the save operation
     */
    async saveConfig(newConfig) {
        try {
            // You would typically save this to a database table
            // For now, we'll use localStorage to persist the config
            localStorage.setItem('appConfig', JSON.stringify(newConfig));
            
            // Merge the new config into the current config
            Object.assign(this.config, newConfig);
            
            // For demonstration: In a real app, you'd save to a config table
            // const { data, error } = await supabase
            //     .from('app_config')
            //     .upsert({ id: 1, config: newConfig })
            //     .single();
            
            // if (error) throw error;
            
            return { success: true, message: "Configuration saved successfully" };
        } catch (error) {
            console.error("Error saving configuration:", error);
            return { success: false, message: error.message };
        }
    }

    /**
     * Load configuration from storage
     * @returns {Promise<Object>} Loaded configuration
     */
    async loadConfig() {
        try {
            // Try to load from localStorage
            const savedConfig = localStorage.getItem('appConfig');
            if (savedConfig) {
                const parsedConfig = JSON.parse(savedConfig);
                Object.assign(this.config, parsedConfig);
                return { success: true, config: this.config };
            }
            
            // For demonstration: In a real app, you'd load from a config table
            // const { data, error } = await supabase
            //     .from('app_config')
            //     .select('*')
            //     .eq('id', 1)
            //     .single();
            
            // if (error) throw error;
            // Object.assign(this.config, data.config);
            
            return { success: true, config: this.config };
        } catch (error) {
            console.error("Error loading configuration:", error);
            return { success: false, message: error.message };
        }
    }
}

// Create and export singleton instance
const configManager = new ConfigManager();

// Try to load saved configuration
configManager.loadConfig();

export default configManager; 