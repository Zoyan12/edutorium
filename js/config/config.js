/**
 * Edutorium Configuration
 * Central place to manage all URLs and endpoints
 */

const config = {
    // Base URLs
    baseURL: window.location.hostname === 'localhost' ? 'http://localhost:3000' : 'https://yourdomain.com',
    
    // WebSocket configurations
    websocket: {
        url: 'wss://yourwebsocketserver.com',
        reconnectInterval: 5000, // 5 seconds
        maxReconnectAttempts: 10
    },
    
    // API endpoints
    api: {
        questions: '/api/questions',
        users: '/api/users',
        battles: '/api/battles',
        leaderboard: '/api/leaderboard',
        profiles: '/api/profiles'
    },
    
    // Supabase configuration (imported from supabase.js)
    supabase: {
        url: 'https://ratxqmbqzwbvfgsonlrd.supabase.co',
        anonKey: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InJhdHhxbWJxendidmZnc29ubHJkIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDQyMDI0NDAsImV4cCI6MjA1OTc3ODQ0MH0.HJ9nQbvVvVisvQb6HMVMlmQBVmW7Ie42Z6Afdwn8W2M'
    },
    
    // Asset URLs
    assets: {
        defaultAvatar: '../assets/default.png',
        defaultQuestionImage: '../assets/default.png',
        iconSet: 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css'
    },
    
    // Feature flags
    features: {
        enableBattleMode: true,
        enableVoiceChat: false,
        enableMultiplayer: true,
        enableNotifications: true
    }
};

// Export the configuration
export default config; 