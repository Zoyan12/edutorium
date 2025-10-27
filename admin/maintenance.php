<?php
/**
 * Maintenance Mode Management Page
 * Allows admins to control maintenance mode settings
 */

// Simple PHP check - if no session, let JavaScript handle it
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode - Admin Panel</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
    <script src="js/utils.js"></script>
    <style>
        .maintenance-status {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .maintenance-status.active {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
        }
        .maintenance-status.inactive {
            background: linear-gradient(135deg, #51cf66, #40c057);
            color: white;
        }
        .maintenance-controls {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-label {
            font-weight: 600;
            margin-bottom: 5px;
            display: block;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        .btn-warning:hover {
            background: #e0a800;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }
        .maintenance-history {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-active {
            background: #ff6b6b;
            color: white;
        }
        .status-inactive {
            background: #51cf66;
            color: white;
        }
        .loading {
            text-align: center;
            padding: 40px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">Checking admin access...</div>
    </div>

    <div id="adminContent" style="display: none;">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content-area">
                <!-- Maintenance Status -->
                <div id="maintenanceStatus" class="maintenance-status">
                    <div class="loading">
                        <div class="spinner"></div>
                        <p>Loading maintenance status...</p>
                    </div>
                </div>

                <!-- Maintenance Controls -->
                <div class="maintenance-controls">
                    <h3><i class="fas fa-tools"></i> Maintenance Mode Controls</h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Maintenance Message</label>
                                <textarea class="form-control" id="maintenanceMessage" rows="3" placeholder="Enter custom message for users..."></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Expected Resolution</label>
                                <input type="text" class="form-control" id="expectedResolution" placeholder="e.g., 6:00 PM IST, 2 hours">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Reason for Maintenance</label>
                                <input type="text" class="form-control" id="maintenanceReason" placeholder="e.g., Server update, Database migration">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Duration (minutes)</label>
                                <input type="number" class="form-control" id="maintenanceDuration" min="1" max="1440" placeholder="60">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button class="btn btn-danger" id="enableMaintenanceBtn">
                            <i class="fas fa-power-off"></i> Enable Maintenance Mode
                        </button>
                        <button class="btn btn-success" id="disableMaintenanceBtn" style="display: none;">
                            <i class="fas fa-check"></i> Disable Maintenance Mode
                        </button>
                        <button class="btn btn-warning" id="testMaintenanceBtn">
                            <i class="fas fa-eye"></i> Preview Maintenance Page
                        </button>
                    </div>
                </div>

                <!-- Maintenance History -->
                <div class="maintenance-history">
                    <h3><i class="fas fa-history"></i> Maintenance History</h3>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Started</th>
                                    <th>Duration</th>
                                    <th>Reason</th>
                                    <th>Started By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="maintenanceHistoryBody">
                                <tr>
                                    <td colspan="6" class="text-center">
                                        <div class="loading">
                                            <div class="spinner"></div>
                                            <p>Loading maintenance history...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="accessDenied" style="display: none;" class="text-center p-5">
        <div class="card">
            <div class="card-body">
                <i class="fas fa-lock fa-3x text-danger mb-3"></i>
                <h3>Access Denied</h3>
                <p>You don't have admin privileges to access this panel.</p>
                <a href="../pages/dashboard.php" class="btn btn-primary">Back to Dashboard</a>
            </div>
        </div>
    </div>

    <script>
        class MaintenanceModeManager {
            constructor() {
                // Use shared Supabase client from AdminUtils to avoid multiple instances
                this.supabase = window.adminUtils?.supabase || window.supabase.createClient(
                    'https://ratxqmbqzwbvfgsonlrd.supabase.co',
                    'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InJhdHhxbWJxendidmZnc29ubHJkIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDQyMDI0NDAsImV4cCI6MjA1OTc3ODQ0MH0.HJ9nQbvVvVisvQb6HMVMlmQBVmW7Ie42Z6Afdwn8W2M',
                    {
                        auth: {
                            autoRefreshToken: true,
                            persistSession: true,
                            detectSessionInUrl: true
                        }
                    }
                );
                this.init();
            }

            async init() {
                try {
                    // Check if user is authenticated and is admin
                    const isAdmin = await this.checkAdminAccess();
                    
                    if (isAdmin) {
                        document.getElementById('loadingOverlay').style.display = 'none';
                        document.getElementById('adminContent').style.display = 'block';
                        this.setupEventListeners();
                        await this.loadMaintenanceStatus();
                        await this.loadMaintenanceHistory();
                    } else {
                        document.getElementById('loadingOverlay').style.display = 'none';
                        document.getElementById('accessDenied').style.display = 'block';
                    }
                } catch (error) {
                    console.error('Error initializing maintenance mode manager:', error);
                    document.getElementById('loadingOverlay').style.display = 'none';
                    document.getElementById('accessDenied').style.display = 'block';
                }
            }

            async checkAdminAccess() {
                try {
                    const { data: { session }, error } = await this.supabase.auth.getSession();
                    
                    if (error || !session) {
                        return false;
                    }

                    const { data: profile, error: profileError } = await this.supabase
                        .from('profiles')
                        .select('is_admin, username, full_name')
                        .eq('user_id', session.user.id)
                        .maybeSingle(); // Use maybeSingle() to handle cases where profile doesn't exist

                    if (profileError || !profile) {
                        return false;
                    }

                    window.adminProfile = profile;
                    return profile.is_admin === true;
                } catch (error) {
                    console.error('Error checking admin access:', error);
                    return false;
                }
            }

            setupEventListeners() {
                // Enable maintenance mode button
                document.getElementById('enableMaintenanceBtn').addEventListener('click', () => {
                    this.enableMaintenanceMode();
                });

                // Disable maintenance mode button
                document.getElementById('disableMaintenanceBtn').addEventListener('click', () => {
                    this.disableMaintenanceMode();
                });

                // Test maintenance page button
                document.getElementById('testMaintenanceBtn').addEventListener('click', () => {
                    this.previewMaintenancePage();
                });
            }

            async loadMaintenanceStatus() {
                try {
                    // Get current maintenance mode status
                    const { data, error } = await this.supabase
                        .from('maintenance_mode')
                        .select('*')
                        .eq('is_active', true)
                        .order('start_time', { ascending: false })
                        .limit(1);

                    const statusDiv = document.getElementById('maintenanceStatus');
                    const maintenance = data && data.length > 0 ? data[0] : null;
                    
                    if (maintenance && !error) {
                        // Maintenance mode is active
                        statusDiv.className = 'maintenance-status active';
                        statusDiv.innerHTML = `
                            <h2><i class="fas fa-exclamation-triangle"></i> Maintenance Mode ACTIVE</h2>
                            <p><strong>Started:</strong> ${this.formatDate(maintenance.start_time)}</p>
                            <p><strong>Reason:</strong> ${maintenance.reason}</p>
                            <p><strong>Expected Resolution:</strong> ${maintenance.expected_resolution}</p>
                            <p><strong>Message:</strong> ${maintenance.user_message}</p>
                        `;
                        
                        // Show disable button, hide enable button
                        document.getElementById('enableMaintenanceBtn').style.display = 'none';
                        document.getElementById('disableMaintenanceBtn').style.display = 'inline-block';
                        
                        // Populate form fields
                        document.getElementById('maintenanceMessage').value = maintenance.user_message;
                        document.getElementById('expectedResolution').value = maintenance.expected_resolution;
                        document.getElementById('maintenanceReason').value = maintenance.reason;
                        document.getElementById('maintenanceDuration').value = maintenance.duration_minutes;
                    } else {
                        // Maintenance mode is inactive
                        statusDiv.className = 'maintenance-status inactive';
                        statusDiv.innerHTML = `
                            <h2><i class="fas fa-check-circle"></i> System Online</h2>
                            <p>All systems are operational. No maintenance scheduled.</p>
                        `;
                        
                        // Show enable button, hide disable button
                        document.getElementById('enableMaintenanceBtn').style.display = 'inline-block';
                        document.getElementById('disableMaintenanceBtn').style.display = 'none';
                        
                        // Load default values
                        await this.loadDefaultSettings();
                    }
                } catch (error) {
                    console.error('Error loading maintenance status:', error);
                    this.showAlert('Error loading maintenance status', 'danger');
                }
            }

            async loadDefaultSettings() {
                try {
                    // Load default settings from the settings table
                    const defaultSettings = ['maintenance_mode_message', 'maintenance_mode_duration', 'maintenance_mode_reason'];
                    
                    for (const key of defaultSettings) {
                        try {
                            const { data: setting, error } = await this.supabase
                                .from('settings')
                                .select('value')
                                .eq('key', key)
                                .single();
                            
                            if (setting && !error) {
                                const elementId = key.replace('maintenance_mode_', 'maintenance');
                                const element = document.getElementById(elementId);
                                if (element) {
                                    element.value = setting.value;
                                }
                            }
                        } catch (e) {
                            // Setting doesn't exist, skip
                            console.debug(`Setting ${key} not found`);
                        }
                    }
                } catch (error) {
                    console.error('Error loading default settings:', error);
                }
            }

            async loadDefaultsFromDatabase() {
                try {
                    // Define defaults to use if settings don't exist
                    const defaultValues = {
                        message: 'We are currently performing scheduled maintenance. Please check back later.',
                        resolution: 'Soon',
                        reason: 'Scheduled maintenance',
                        duration: 60
                    };

                    // Load default settings from the settings table
                    const defaultSettings = {
                        'maintenance_mode_message': 'message',
                        'maintenance_mode_reason': 'reason',
                        'maintenance_mode_duration': 'duration'
                    };
                    
                    for (const [key, property] of Object.entries(defaultSettings)) {
                        try {
                            const { data: setting, error } = await this.supabase
                                .from('settings')
                                .select('value')
                                .eq('key', key)
                                .single();
                            
                            if (setting && !error) {
                                if (property === 'duration') {
                                    defaultValues[property] = parseInt(setting.value) || 60;
                                } else {
                                    defaultValues[property] = setting.value;
                                }
                            }
                        } catch (e) {
                            // Setting doesn't exist, use default
                            console.debug(`Setting ${key} not found, using default value`);
                        }
                    }

                    return defaultValues;
                } catch (error) {
                    console.error('Error loading defaults from database:', error);
                    // Return fallback defaults
                    return {
                        message: 'We are currently performing scheduled maintenance. Please check back later.',
                        resolution: 'Soon',
                        reason: 'Scheduled maintenance',
                        duration: 60
                    };
                }
            }

            async enableMaintenanceMode() {
                try {
                    let message = document.getElementById('maintenanceMessage').value.trim();
                    let resolution = document.getElementById('expectedResolution').value.trim();
                    let reason = document.getElementById('maintenanceReason').value.trim();
                    const durationInput = document.getElementById('maintenanceDuration').value.trim();
                    let duration = durationInput ? parseInt(durationInput) : null;

                    // If fields are empty, try to load defaults from settings table
                    if (!message || !resolution || !reason || !duration) {
                        const defaults = await this.loadDefaultsFromDatabase();
                        
                        if (!message) {
                            message = defaults.message;
                        }
                        if (!resolution) {
                            resolution = defaults.resolution;
                        }
                        if (!reason) {
                            reason = defaults.reason;
                        }
                        if (!duration) {
                            duration = defaults.duration;
                        }
                    }

                    // Get current user session
                    const { data: { session }, error: sessionError } = await this.supabase.auth.getSession();
                    if (sessionError || !session) {
                        this.showAlert('Authentication error', 'danger');
                        return;
                    }

                    // First, disable any existing maintenance mode
                    await this.supabase
                        .from('maintenance_mode')
                        .update({ is_active: false })
                        .eq('is_active', true);

                    // Create new maintenance mode record
                    const { data, error } = await this.supabase
                        .from('maintenance_mode')
                        .insert({
                            is_active: true,
                            reason: reason,
                            user_message: message,
                            expected_resolution: resolution,
                            duration_minutes: duration,
                            started_by: session.user.id
                        })
                        .select()
                        .single();

                    if (error) {
                        throw error;
                    }

                    // Update settings table
                    await this.updateMaintenanceSettings(message, resolution, reason, duration);

                    // Log the maintenance start event
                    await this.logMaintenanceEvent(data.id, 'started', 'Maintenance mode enabled by admin');

                    this.showAlert('Maintenance mode enabled successfully', 'success');
                    await this.loadMaintenanceStatus();
                    await this.loadMaintenanceHistory();

                } catch (error) {
                    console.error('Error enabling maintenance mode:', error);
                    this.showAlert('Error enabling maintenance mode', 'danger');
                }
            }

            async disableMaintenanceMode() {
                try {
                    // Disable maintenance mode
                    const { error } = await this.supabase
                        .from('maintenance_mode')
                        .update({ is_active: false })
                        .eq('is_active', true);

                    if (error) {
                        throw error;
                    }

                    this.showAlert('Maintenance mode disabled successfully', 'success');
                    await this.loadMaintenanceStatus();
                    await this.loadMaintenanceHistory();

                } catch (error) {
                    console.error('Error disabling maintenance mode:', error);
                    this.showAlert('Error disabling maintenance mode', 'danger');
                }
            }

            async updateMaintenanceSettings(message, resolution, reason, duration) {
                try {
                    const settings = [
                        { key: 'maintenance_mode_message', value: message },
                        { key: 'maintenance_mode_duration', value: duration.toString() },
                        { key: 'maintenance_mode_reason', value: reason }
                    ];

                    for (const setting of settings) {
                        try {
                            // Check if setting exists
                            const { data: existing } = await this.supabase
                                .from('settings')
                                .select('id')
                                .eq('key', setting.key)
                                .maybeSingle();

                            if (existing) {
                                // Update existing setting
                                await this.supabase
                                    .from('settings')
                                    .update({ value: setting.value, updated_at: new Date().toISOString() })
                                    .eq('key', setting.key);
                            } else {
                                // Create new setting
                                await this.supabase
                                    .from('settings')
                                    .insert({
                                        key: setting.key,
                                        value: setting.value,
                                        description: `Maintenance mode setting for ${setting.key.replace('maintenance_mode_', '')}`
                                    });
                            }
                        } catch (e) {
                            console.debug(`Error updating setting ${setting.key}:`, e.message);
                        }
                    }
                } catch (error) {
                    console.error('Error updating maintenance settings:', error);
                }
            }

            async loadMaintenanceHistory() {
                try {
                    // First, get maintenance records
                    const { data: maintenanceRecords, error: maintenanceError } = await this.supabase
                        .from('maintenance_mode')
                        .select('*')
                        .order('start_time', { ascending: false })
                        .limit(20);

                    if (maintenanceError) {
                        throw maintenanceError;
                    }

                    // Then, get user profiles for the started_by users
                    const history = [];
                    if (maintenanceRecords) {
                        for (const record of maintenanceRecords) {
                            let userInfo = { username: 'Unknown', full_name: 'Unknown User' };
                            
                            if (record.started_by) {
                                try {
                                    const { data: profile, error: profileError } = await this.supabase
                                        .from('profiles')
                                        .select('username, full_name')
                                        .eq('user_id', record.started_by)
                                        .maybeSingle();
                                    
                                    if (profile && !profileError) {
                                        userInfo = profile;
                                    }
                                } catch (profileError) {
                                    console.warn('Could not fetch profile for user:', record.started_by);
                                }
                            }
                            
                            history.push({
                                ...record,
                                profiles: userInfo
                            });
                        }
                    }

                    this.renderMaintenanceHistory(history);

                } catch (error) {
                    console.error('Error loading maintenance history:', error);
                    this.showAlert('Error loading maintenance history: ' + error.message, 'danger');
                }
            }

            renderMaintenanceHistory(history) {
                const tbody = document.getElementById('maintenanceHistoryBody');
                
                if (!history || history.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center text-muted">No maintenance history found</td>
                        </tr>
                    `;
                    return;
                }

                const html = history.map(record => `
                    <tr>
                        <td>
                            <span class="status-badge ${record.is_active ? 'status-active' : 'status-inactive'}">
                                ${record.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </td>
                        <td>${this.formatDate(record.start_time)}</td>
                        <td>${record.duration_minutes} minutes</td>
                        <td>${record.reason}</td>
                        <td>${record.profiles?.username || record.profiles?.full_name || 'Unknown'}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="maintenanceManager.viewDetails('${record.id}')">
                                <i class="fas fa-eye"></i> View
                            </button>
                        </td>
                    </tr>
                `).join('');

                tbody.innerHTML = html;
            }

            previewMaintenancePage() {
                const message = document.getElementById('maintenanceMessage').value.trim() || 'We are currently performing scheduled maintenance. Please check back later.';
                const resolution = document.getElementById('expectedResolution').value.trim() || 'Soon';
                const reason = document.getElementById('maintenanceReason').value.trim() || 'Scheduled maintenance';

                // Open maintenance page in new window
                const maintenanceUrl = `../maintenance.php?preview=true&message=${encodeURIComponent(message)}&resolution=${encodeURIComponent(resolution)}&reason=${encodeURIComponent(reason)}`;
                window.open(maintenanceUrl, '_blank', 'width=800,height=600');
            }

            viewDetails(recordId) {
                // This could open a modal with detailed information
                this.showAlert('View details functionality can be implemented here', 'info');
            }

            formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
            }

            async logMaintenanceEvent(maintenanceId, eventType, description) {
                // Skip logging since maintenance_logs table doesn't exist in the database
                // This is a non-critical operation, so we'll just skip it for now
                console.debug(`Maintenance event: ${eventType} - ${description} (logging skipped - table not available)`);
                return;
                
                // Future implementation: Check if table exists first
                /*
                try {
                    const logData = {
                        maintenance_id: maintenanceId,
                        event_type: eventType,
                        description: description
                    };
                    
                    const { error } = await this.supabase
                        .from('maintenance_logs')
                        .insert(logData);
                    
                    if (error) {
                        console.debug('Maintenance logs table not available, skipping log entry');
                    }
                } catch (error) {
                    console.debug('Could not log maintenance event (non-critical)');
                }
                */
            }

            showAlert(message, type = 'info') {
                // Create alert element
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
                alertDiv.innerHTML = `
                    ${message}
                    <button type="button" class="btn-close" onclick="this.parentElement.remove()">&times;</button>
                `;

                // Insert at the top of content area
                const contentArea = document.querySelector('.content-area');
                contentArea.insertBefore(alertDiv, contentArea.firstChild);

                // Auto-remove after 5 seconds
                setTimeout(() => {
                    if (alertDiv.parentElement) {
                        alertDiv.remove();
                    }
                }, 5000);
            }
        }

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            window.maintenanceManager = new MaintenanceModeManager();
        });
    </script>
</body>
</html>
