/**
 * Enhanced Maintenance Mode Client-Side Checker
 * Provides real-time maintenance mode checking with auto-redirect
 */

class EnhancedMaintenanceChecker {
    constructor() {
        this.checkInterval = null;
        this.isChecking = false;
        this.lastCheckTime = 0;
        this.checkCooldown = 10000; // 10 seconds between checks
        this.redirectAttempts = 0;
        this.maxRedirectAttempts = 3;
        this.isAdmin = false;
        this.hasBypass = false;
        this.realtimeChannel = null;
        this.lastMaintenanceStatus = null;
        this.init();
    }

    /**
     * Initialize the maintenance checker
     */
    async init() {
        // Check if current page should be excluded
        if (this.isCurrentPageExcluded()) {
            console.log('Maintenance checker: Page is excluded');
            return;
        }

        // Check if user is admin
        this.isAdmin = await this.checkIfAdmin();
        
        // Check if user has bypass
        this.hasBypass = this.hasBypassCookie();

        // Start periodic checking
        this.startPeriodicCheck();

        // Start real-time subscription
        this.startRealtimeSubscription();

        // Initial check
        await this.checkMaintenanceStatus();
    }

    /**
     * Check if current page should be excluded from maintenance checking
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
            currentPath.includes(excludedPath)
        );
    }

    /**
     * Check if user is admin
     */
    async checkIfAdmin() {
        try {
            // Check if we can access admin pages (simpler approach)
            const currentPath = window.location.pathname;
            let adminPath = '/admin/';
            
            // If we're in a subdirectory, adjust the path
            if (currentPath.includes('/edutorium/client/')) {
                adminPath = '/edutorium/client/admin/';
            } else if (currentPath.includes('/client/')) {
                adminPath = '/client/admin/';
            }
            
            // Try to access admin index to check if user is admin
            const response = await fetch(adminPath, {
                method: 'HEAD', // Just check if accessible
                credentials: 'same-origin'
            });

            if (response.ok) {
                return true; // If we can access admin area, we're likely admin
            }
        } catch (error) {
            // Not admin or admin area not accessible
        }

        return false;
    }

    /**
     * Check if user has bypass cookie
     */
    hasBypassCookie() {
        return document.cookie.split(';').some(cookie => 
            cookie.trim().startsWith('maintenance_bypass=true')
        );
    }

    /**
     * Start periodic maintenance mode checking
     */
    startPeriodicCheck() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
        }

        this.checkInterval = setInterval(() => {
            this.checkMaintenanceStatus();
        }, 30000); // Check every 30 seconds
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

            // Wait for subscription to be established
            channel.on('close', () => {
                console.log('Real-time subscription closed');
            });
            
            channel.on('presence', (state, presences) => {
                console.log('Presence state changed:', state);
            });
            
            channel.on('error', (err) => {
                console.error('Real-time subscription error:', err);
            });

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
     */
    async checkMaintenanceStatus() {
        const now = Date.now();
        
        // Skip if recently checked and not forced
        if ((now - this.lastCheckTime) < this.checkCooldown) {
            return;
        }

        if (this.isChecking) {
            return;
        }

        this.isChecking = true;
        this.lastCheckTime = now;

        try {
            // Get the correct API path based on current location
            const currentPath = window.location.pathname;
            let apiPath = '/api/maintenance-status.php';
            
            // If we're in a subdirectory, adjust the path
            if (currentPath.includes('/edutorium/client/')) {
                apiPath = '/edutorium/client/api/maintenance-status.php';
            } else if (currentPath.includes('/client/')) {
                apiPath = '/client/api/maintenance-status.php';
            }
            
            console.log('Checking maintenance status at:', apiPath);
            
            const response = await fetch(apiPath, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                console.error('Maintenance API error:', response.status, response.statusText);
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            
            // Handle maintenance mode status
            if (data.maintenance && data.maintenance.is_active) {
                this.lastMaintenanceStatus = 'active';
                // Check if maintenance should auto-end
                this.checkAutoEndMaintenance(data.maintenance);
                this.handleMaintenanceModeActive(data.maintenance);
            } else {
                this.lastMaintenanceStatus = 'inactive';
                this.handleMaintenanceModeInactive();
            }

        } catch (error) {
            console.error('Error checking maintenance status:', error);
            
            // If we can't check status, assume maintenance is not active
            // This prevents false redirects when there are network issues
            this.handleMaintenanceModeInactive();
        } finally {
            this.isChecking = false;
        }
    }

    /**
     * Handle when maintenance mode is active
     */
    handleMaintenanceModeActive(maintenanceData) {
        // Skip if admin with bypass
        if (this.isAdmin && this.hasBypass) {
            console.log('Maintenance mode active but admin has bypass');
            return;
        }

        // Skip if admin accessing admin pages
        if (this.isAdmin && window.location.pathname.includes('/admin/')) {
            console.log('Maintenance mode active but admin accessing admin page');
            return;
        }

        // Show notification and redirect
        this.showMaintenanceNotification(maintenanceData);
        this.redirectToMaintenance();
    }

    /**
     * Handle when maintenance mode is inactive
     */
    handleMaintenanceModeInactive() {
        // Remove any maintenance notifications
        this.removeMaintenanceNotification();
        
        // If user is on maintenance page and maintenance is now inactive, redirect them
        const currentPath = window.location.pathname;
        if (currentPath.includes('maintenance.php')) {
            console.log('Maintenance mode ended. Redirecting user from maintenance page...');
            
            // Wait a moment for database consistency, then redirect
            setTimeout(() => {
                let returnUrl = sessionStorage.getItem('maintenance_return_url');
                
                // If no stored URL, determine based on auth status
                if (!returnUrl || returnUrl.includes('maintenance.php')) {
                    returnUrl = '../index.php'; // Default to index.php
                    
                    // Try to check if user is logged in
                    // Note: This will only work if supabase client is available
                    if (window.supabase && typeof window.supabase.createClient === 'function') {
                        try {
                            const supabaseClient = window.supabase.createClient(
                                'https://ratxqmbqzwbvfgsonlrd.supabase.co',
                                'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InJhdHhxbWJxendidmZnc29ubHJkIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDQyMDI0NDAsImV4cCI6MjA1OTc3ODQ0MH0.HJ9nQbvVvVisvQb6HMVMlmQBVmW7Ie42Z6Afdwn8W2M'
                            );
                            
                            supabaseClient.auth.getSession().then(({ data }) => {
                                if (data && data.session) {
                                    returnUrl = '../pages/dashboard.php';
                                }
                                window.location.href = returnUrl;
                            });
                            return; // Don't continue to redirect below
                        } catch (e) {
                            console.debug('Could not check auth, using default URL');
                        }
                    }
                }
                
                window.location.href = returnUrl;
            }, 500);
        }
    }

    /**
     * Show maintenance notification
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
            animation: slideDown 0.3s ease-out;
        `;

        notification.innerHTML = `
            <div style="max-width: 1200px; margin: 0 auto;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 10px; flex-wrap: wrap;">
                    <i class="fas fa-tools" style="font-size: 18px;"></i>
                    <strong>Maintenance Mode Active:</strong>
                    <span>${maintenanceData.user_message || 'We are currently performing scheduled maintenance.'}</span>
                    <span style="background: rgba(255,255,255,0.2); padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                        Expected: ${maintenanceData.expected_resolution || 'Soon'}
                    </span>
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <span class="loading-spinner" style="display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-radius: 50%; border-top-color: white; animation: spin 1s linear infinite;"></span>
                        <span>Redirecting...</span>
                    </div>
                </div>
            </div>
        `;

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideDown {
                from { transform: translateY(-100%); }
                to { transform: translateY(0); }
            }
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);

        // Add to page
        document.body.insertBefore(notification, document.body.firstChild);

        // Adjust body padding
        document.body.style.paddingTop = '80px';
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
        if (this.redirectAttempts >= this.maxRedirectAttempts) {
            console.error('Max redirect attempts reached');
            return;
        }

        this.redirectAttempts++;

        // Store the current URL so we can return to it after maintenance
        const currentUrl = window.location.href;
        const currentPath = window.location.pathname;
        sessionStorage.setItem('maintenance_return_url', currentUrl);
        
        // Extract the base path from current location
        // For example: /edutorium/client/pages/dashboard.php -> /edutorium/client
        const pathParts = currentPath.split('/').filter(part => part !== '');
        
        // Find the project root path (everything before the specific page and its directory)
        let basePath = '';
        if (pathParts.length >= 2) {
            // Remove the last part (the actual file)
            const baseParts = pathParts.slice(0, -1);
            
            // For pages in subdirectories like /pages/, we need to go up one more level
            // Check if the last remaining part is a common subdirectory
            const commonSubdirs = ['pages', 'admin', 'api', 'css', 'js', 'img', 'assets', 'vendor', 'includes', 'sql', 'docs', 'monitoring', 'nginx', 'utils', 'src'];
            
            if (baseParts.length > 0 && commonSubdirs.includes(baseParts[baseParts.length - 1])) {
                // Remove the subdirectory as well
                baseParts.pop();
            }
            
            basePath = '/' + baseParts.join('/');
        }
        
        // Construct maintenance URL
        let maintenanceUrl = '/maintenance.php';
        if (basePath && basePath !== '/') {
            maintenanceUrl = basePath + maintenanceUrl;
        }

        // Add bypass parameter if admin
        if (this.isAdmin) {
            maintenanceUrl += '?bypass=true';
        }

        console.log('Storing return URL:', currentUrl);
        console.log('Redirecting to maintenance page:', maintenanceUrl);
        console.log('Current path:', currentPath, 'Base path:', basePath);

        // Redirect after a short delay to show notification
        setTimeout(() => {
            window.location.href = maintenanceUrl;
        }, 2000);
    }

    /**
     * Check if maintenance should auto-end
     */
    checkAutoEndMaintenance(maintenanceData) {
        const startTime = new Date(maintenanceData.start_time).getTime();
        const durationMinutes = maintenanceData.duration_minutes;
        const endTime = startTime + (durationMinutes * 60 * 1000);
        const now = new Date().getTime();
        
        // If maintenance time has passed, try to auto-end it
        if (now >= endTime) {
            console.log('Maintenance time has passed, attempting to auto-end...');
            
            // Get the correct API path
            const currentPath = window.location.pathname;
            let apiPath = '/auto-end-maintenance.php';
            
            if (currentPath.includes('/edutorium/client/')) {
                apiPath = '/edutorium/client/auto-end-maintenance.php';
            } else if (currentPath.includes('/client/')) {
                apiPath = '/client/auto-end-maintenance.php';
            }
            
            // Call the auto-end script
            fetch(apiPath, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Maintenance auto-ended:', data.message);
                    // Force a new check after a short delay
                    setTimeout(() => {
                        this.forceCheck();
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error auto-ending maintenance:', error);
            });
        }
    }

    /**
     * Force check maintenance status (for manual triggers)
     */
    async forceCheck() {
        this.lastCheckTime = 0;
        await this.checkMaintenanceStatus();
    }
}

// Create global instance
window.enhancedMaintenanceChecker = new EnhancedMaintenanceChecker();

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = EnhancedMaintenanceChecker;
}
