/**
 * Maintenance Mode Client-Side Checker
 * Provides client-side maintenance mode status checking and handling
 */

class MaintenanceModeChecker {
    constructor() {
        this.checkInterval = null;
        this.isChecking = false;
        this.lastCheckTime = 0;
        this.checkCooldown = 30000; // 30 seconds between checks
        this.realtimeChannel = null;
        this.lastMaintenanceStatus = null;
    }

    /**
     * Start periodic maintenance mode checking
     * @param {number} intervalMs - Check interval in milliseconds (default: 60000 = 1 minute)
     */
    startPeriodicCheck(intervalMs = 60000) {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
        }

        this.checkInterval = setInterval(() => {
            this.checkMaintenanceStatus();
        }, intervalMs);

        // Initial check
        this.checkMaintenanceStatus();

        // Start real-time subscription
        this.startRealtimeSubscription();
    }

    /**
     * Stop periodic maintenance mode checking
     */
    stopPeriodicCheck() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
            this.checkInterval = null;
        }
        // Stop real-time subscription
        this.stopRealtimeSubscription();
    }

    /**
     * Start real-time subscription to maintenance mode changes
     */
    startRealtimeSubscription() {
        // Check if Supabase library is loaded
        if (!window.supabase || typeof window.supabase.createClient !== 'function') {
            console.warn('Supabase library not available for real-time subscription');
            return;
        }

        // Create Supabase client for real-time
        let supabaseClient;
        try {
            supabaseClient = window.supabase.createClient(
                'https://ratxqmbqzwbvfgsonlrd.supabase.co',
                'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InJhdHhxbWJxendidmZnc29ubHJkIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDQyMDI0NDAsImV4cCI6MjA1OTc3ODQ0MH0.HJ9nQbvVvVisvQb6HMVMlmQBVmW7Ie42Z6Afdwn8W2M'
            );
        } catch (e) {
            console.error('Could not create Supabase client for real-time:', e);
            return;
        }

        try {
            // Subscribe to maintenance_mode table changes
            const channelName = 'maintenance-mode-' + Date.now();
            const channel = supabaseClient
                .channel(channelName)
                .on(
                    'postgres_changes',
                    {
                        event: '*', // Listen to all events (INSERT, UPDATE, DELETE)
                        schema: 'public',
                        table: 'maintenance_mode'
                    },
                    (payload) => {
                        console.log('Maintenance mode change detected:', payload);
                        this.handleRealtimeChange(payload);
                    }
                )
                .subscribe();

            this.realtimeChannel = channel;
            
            // Check subscription status after a short delay
            setTimeout(() => {
                const status = channel.state;
                console.log('Real-time subscription status:', status);
                if (status === 'joined') {
                    console.log('Successfully subscribed to maintenance mode changes');
                }
            }, 1000);

        } catch (error) {
            console.error('Error setting up real-time subscription:', error);
        }
    }

    /**
     * Stop real-time subscription
     */
    stopRealtimeSubscription() {
        if (this.realtimeChannel) {
            try {
                // Unsubscribe from the channel
                const status = this.realtimeChannel.unsubscribe();
                console.log('Unsubscribed from maintenance mode changes. Status:', status);
            } catch (error) {
                console.error('Error stopping real-time subscription:', error);
            }
            this.realtimeChannel = null;
        }
    }

    /**
     * Handle real-time maintenance mode changes
     */
    handleRealtimeChange(payload) {
        console.log('Real-time change payload:', payload);
        
        // Get the event type
        const eventType = payload.eventType;
        const newRecord = payload.new;
        const oldRecord = payload.old;
        
        // Determine current status based on event type and data
        let currentStatus = 'inactive';
        
        if (eventType === 'INSERT') {
            currentStatus = newRecord && newRecord.is_active ? 'active' : 'inactive';
        } else if (eventType === 'UPDATE') {
            // Check the new record
            currentStatus = newRecord && newRecord.is_active ? 'active' : 'inactive';
        } else if (eventType === 'DELETE') {
            // If deleted, check if old record was active
            currentStatus = 'inactive';
        }
        
        const lastStatus = this.lastMaintenanceStatus;
        
        console.log('Maintenance mode change detected:', {
            eventType,
            newStatus: currentStatus,
            lastStatus: lastStatus,
            newRecord,
            oldRecord
        });

        // Always refresh when there's a change to maintenance mode table
        // This ensures all users see the update instantly
        if (eventType === 'INSERT' || eventType === 'UPDATE' || eventType === 'DELETE') {
            console.log('Maintenance mode changed. Refreshing page in 1 second...');
            
            this.lastMaintenanceStatus = currentStatus;
            
            // Refresh the page immediately to show the change
            setTimeout(() => {
                console.log('Refreshing page now...');
                window.location.reload();
            }, 1000); // 1 second delay to ensure database consistency
        }
    }

    /**
     * Check maintenance mode status
     * @param {boolean} force - Force check even if recently checked
     * @returns {Promise<Object>} Maintenance status object
     */
    async checkMaintenanceStatus(force = false) {
        const now = Date.now();
        
        // Skip if recently checked and not forced
        if (!force && (now - this.lastCheckTime) < this.checkCooldown) {
            return this.getCachedStatus();
        }

        if (this.isChecking) {
            return this.getCachedStatus();
        }

        this.isChecking = true;
        this.lastCheckTime = now;

        try {
            const response = await fetch('/admin/api/maintenance.php?action=current', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            
            // Cache the result
            this.cacheStatus(data);

            // Handle maintenance mode status
            if (data.maintenance && data.maintenance.is_active) {
                this.lastMaintenanceStatus = 'active';
                this.handleMaintenanceModeActive(data.maintenance);
            } else {
                this.lastMaintenanceStatus = 'inactive';
                this.handleMaintenanceModeInactive();
            }

            return data;

        } catch (error) {
            console.error('Error checking maintenance status:', error);
            
            // Return cached status on error
            return this.getCachedStatus();
        } finally {
            this.isChecking = false;
        }
    }

    /**
     * Cache maintenance status
     * @param {Object} status - Status object to cache
     */
    cacheStatus(status) {
        try {
            localStorage.setItem('maintenance_status', JSON.stringify({
                data: status,
                timestamp: Date.now()
            }));
        } catch (error) {
            console.warn('Failed to cache maintenance status:', error);
        }
    }

    /**
     * Get cached maintenance status
     * @returns {Object} Cached status or default
     */
    getCachedStatus() {
        try {
            const cached = localStorage.getItem('maintenance_status');
            if (cached) {
                const parsed = JSON.parse(cached);
                // Return cached data if less than 5 minutes old
                if (Date.now() - parsed.timestamp < 300000) {
                    return parsed.data;
                }
            }
        } catch (error) {
            console.warn('Failed to get cached maintenance status:', error);
        }

        return {
            maintenance: null,
            timestamp: new Date().toISOString()
        };
    }

    /**
     * Handle when maintenance mode is active
     * @param {Object} maintenanceData - Maintenance mode data
     */
    handleMaintenanceModeActive(maintenanceData) {
        // Check if user has bypass
        if (this.hasBypassCookie()) {
            console.log('Maintenance mode active but user has bypass');
            return;
        }

        // Check if current page is excluded
        if (this.isCurrentPageExcluded()) {
            console.log('Maintenance mode active but current page is excluded');
            return;
        }

        // Show maintenance notification or redirect
        this.showMaintenanceNotification(maintenanceData);
    }

    /**
     * Handle when maintenance mode is inactive
     */
    handleMaintenanceModeInactive() {
        // Remove any maintenance notifications
        this.removeMaintenanceNotification();
    }

    /**
     * Check if user has bypass cookie
     * @returns {boolean} True if user has bypass
     */
    hasBypassCookie() {
        return document.cookie.split(';').some(cookie => 
            cookie.trim().startsWith('maintenance_bypass=true')
        );
    }

    /**
     * Check if current page should be excluded from maintenance mode
     * @returns {boolean} True if page should be excluded
     */
    isCurrentPageExcluded() {
        const currentPath = window.location.pathname;
        const excludedPages = [
            '/maintenance.php',
            '/admin/',
            '/api/',
            '/css/',
            '/js/',
            '/img/',
            '/assets/',
            '/vendor/',
            '/includes/',
            '/sql/',
            '/docs/',
            '/monitoring/',
            '/nginx/',
            '/utils/',
            '/src/',
            '/pages/login.html',
            '/pages/signup.html'
        ];

        return excludedPages.some(excludedPath => 
            currentPath.startsWith(excludedPath)
        );
    }

    /**
     * Show maintenance notification
     * @param {Object} maintenanceData - Maintenance mode data
     */
    showMaintenanceNotification(maintenanceData) {
        // Remove existing notification
        this.removeMaintenanceNotification();

        // Create notification element
        const notification = document.createElement('div');
        notification.id = 'maintenance-notification';
        notification.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            padding: 15px;
            text-align: center;
            z-index: 10000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        `;

        notification.innerHTML = `
            <div style="max-width: 1200px; margin: 0 auto;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                    <i class="fas fa-tools" style="font-size: 18px;"></i>
                    <strong>Maintenance Mode Active:</strong>
                    <span>${maintenanceData.user_message || 'We are currently performing scheduled maintenance.'}</span>
                    <button onclick="maintenanceChecker.redirectToMaintenance()" 
                            style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); 
                                   color: white; padding: 5px 10px; border-radius: 4px; cursor: pointer; 
                                   font-size: 12px; margin-left: 10px;">
                        View Details
                    </button>
                </div>
            </div>
        `;

        // Add to page
        document.body.insertBefore(notification, document.body.firstChild);

        // Adjust body padding to account for notification
        document.body.style.paddingTop = '60px';

        // Auto-redirect after 10 seconds
        setTimeout(() => {
            this.redirectToMaintenance();
        }, 10000);
    }

    /**
     * Remove maintenance notification
     */
    removeMaintenanceNotification() {
        const notification = document.getElementById('maintenance-notification');
        if (notification) {
            notification.remove();
            document.body.style.paddingTop = '';
        }
    }

    /**
     * Redirect to maintenance page
     */
    redirectToMaintenance() {
        window.location.href = '/maintenance.php';
    }

    /**
     * Enable maintenance bypass for admin users
     */
    enableBypass() {
        document.cookie = 'maintenance_bypass=true; path=/; max-age=3600'; // 1 hour
        window.location.reload();
    }

    /**
     * Disable maintenance bypass
     */
    disableBypass() {
        document.cookie = 'maintenance_bypass=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
        window.location.reload();
    }

    /**
     * Get maintenance mode status for display
     * @returns {Promise<Object>} Status object with display information
     */
    async getStatusForDisplay() {
        const status = await this.checkMaintenanceStatus();
        
        if (status.maintenance && status.maintenance.is_active) {
            const startTime = new Date(status.maintenance.start_time);
            const durationMs = status.maintenance.duration_minutes * 60 * 1000;
            const endTime = new Date(startTime.getTime() + durationMs);
            const now = new Date();
            
            return {
                isActive: true,
                message: status.maintenance.user_message,
                reason: status.maintenance.reason,
                expectedResolution: status.maintenance.expected_resolution,
                startTime: startTime,
                endTime: endTime,
                durationMinutes: status.maintenance.duration_minutes,
                timeRemaining: Math.max(0, endTime.getTime() - now.getTime()),
                isOverdue: now > endTime
            };
        }

        return {
            isActive: false,
            message: null,
            reason: null,
            expectedResolution: null,
            startTime: null,
            endTime: null,
            durationMinutes: 0,
            timeRemaining: 0,
            isOverdue: false
        };
    }
}

// Create global instance
window.maintenanceChecker = new MaintenanceModeChecker();

// Auto-start checking on page load
document.addEventListener('DOMContentLoaded', () => {
    // Only start checking if not on excluded pages
    if (!window.maintenanceChecker.isCurrentPageExcluded()) {
        window.maintenanceChecker.startPeriodicCheck();
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MaintenanceModeChecker;
}
