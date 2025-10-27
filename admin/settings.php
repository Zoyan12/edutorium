<?php
/**
 * Settings Management Page - JavaScript Authentication Version
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
    <title>System Settings - Admin Panel</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
    <script src="js/utils.js"></script>
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
                <!-- Filters -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">System Settings Management</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Search Settings</label>
                                    <input type="text" class="form-control" id="searchInput" placeholder="Search by key or description...">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="form-label">&nbsp;</label>
                                    <button class="btn btn-primary w-100" id="searchBtn">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">&nbsp;</label>
                                    <button class="btn btn-success w-100" id="addSettingBtn">
                                        <i class="fas fa-plus"></i> Add Setting
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">&nbsp;</label>
                                    <button class="btn btn-info w-100" id="refreshBtn">
                                        <i class="fas fa-sync"></i> Refresh
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Settings -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Quick Settings</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">WebSocket URL</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="websocketUrl" placeholder="ws://localhost:8080">
                                        <button class="btn btn-outline-secondary" onclick="settingsManagement.updateQuickSetting('websocket_url', document.getElementById('websocketUrl').value)">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Maintenance Mode</label>
                                    <div class="input-group">
                                        <select class="form-control" id="maintenanceMode">
                                            <option value="false">Disabled</option>
                                            <option value="true">Enabled</option>
                                        </select>
                                        <button class="btn btn-outline-secondary" onclick="settingsManagement.updateQuickSetting('maintenance_mode', document.getElementById('maintenanceMode').value)">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Max Battle Duration (minutes)</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="maxBattleDuration" placeholder="30" min="1" max="120">
                                        <button class="btn btn-outline-secondary" onclick="settingsManagement.updateQuickSetting('max_battle_duration', document.getElementById('maxBattleDuration').value)">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Settings Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">All Settings</h3>
                        <div>
                            <span class="text-muted" id="settingsCount">Loading...</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Key</th>
                                        <th>Value</th>
                                        <th>Description</th>
                                        <th>Updated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="settingsTableBody">
                                    <tr>
                                        <td colspan="5" class="text-center">
                                            <div class="loading">
                                                <div class="spinner"></div>
                                                <p>Loading settings...</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-3" id="paginationContainer">
                        </div>
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

    <!-- Add/Edit Setting Modal -->
    <div class="modal" id="settingModal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add New Setting</h5>
                <button type="button" class="modal-close" onclick="closeSettingModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="settingForm">
                    <input type="hidden" id="settingId">
                    
                    <div class="form-group">
                        <label class="form-label">Setting Key</label>
                        <input type="text" class="form-control" id="settingKey" required placeholder="e.g., websocket_url">
                        <small class="form-text text-muted">Unique identifier for the setting</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Setting Value</label>
                        <textarea class="form-control" id="settingValue" rows="3" required placeholder="Enter the setting value"></textarea>
                        <small class="form-text text-muted">The actual value for this setting</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="settingDescription" rows="2" placeholder="Optional description of what this setting does"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeSettingModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveSetting()">Save Setting</button>
            </div>
        </div>
    </div>

    <script>

        class SettingsManagement {
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
                this.currentPage = 1;
                this.currentFilters = {};
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
                        await this.loadQuickSettings();
                        this.loadSettings();
                    } else {
                        document.getElementById('loadingOverlay').style.display = 'none';
                        document.getElementById('accessDenied').style.display = 'block';
                    }
                } catch (error) {
                    console.error('Error initializing settings management:', error);
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
                        .single();

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
                // Search button
                document.getElementById('searchBtn').addEventListener('click', () => {
                    this.currentPage = 1;
                    this.loadSettings();
                });

                // Add setting button
                document.getElementById('addSettingBtn').addEventListener('click', () => {
                    this.openAddModal();
                });

                // Refresh button
                document.getElementById('refreshBtn').addEventListener('click', () => {
                    this.loadQuickSettings();
                    this.loadSettings();
                });

                // Enter key on search input
                document.getElementById('searchInput').addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        this.currentPage = 1;
                        this.loadSettings();
                    }
                });
            }

            async loadQuickSettings() {
                try {
                    // Load common settings for quick access
                    const quickSettings = ['websocket_url', 'maintenance_mode', 'max_battle_duration'];
                    
                    for (const key of quickSettings) {
                        try {
                            const response = await this.utils?.apiRequest(`settings.php?action=key&key=${key}`) ||
                                           await this.supabase.from('settings').select('*').eq('key', key).single();
                            
                            const setting = response.setting || response.data;
                            if (setting) {
                                const element = document.getElementById(key.replace('_', ''));
                                if (element) {
                                    element.value = setting.value;
                                }
                            }
                        } catch (error) {
                            console.log(`Setting ${key} not found, using default`);
                        }
                    }
                } catch (error) {
                    console.error('Error loading quick settings:', error);
                }
            }

            getCurrentFilters() {
                return {
                    search: document.getElementById('searchInput').value.trim(),
                    page: this.currentPage,
                    limit: 20
                };
            }

            async loadSettings() {
                try {
                    this.currentFilters = this.getCurrentFilters();
                    
                    // Build Supabase query
                    let query = this.supabase.from('settings').select('*');
                    
                    // Apply filters
                    if (this.currentFilters.search) {
                        query = query.or(`key.ilike.%${this.currentFilters.search}%,description.ilike.%${this.currentFilters.search}%`);
                    }
                    
                    // Add ordering and pagination
                    query = query.order('key', { ascending: true });
                    query = query.range(
                        (this.currentPage - 1) * this.currentFilters.limit,
                        this.currentPage * this.currentFilters.limit - 1
                    );

                    const { data: settings, error } = await query;

                    if (error) {
                        throw error;
                    }

                    this.renderSettingsTable(settings || []);
                    this.updateSettingsCount(settings ? settings.length : 0);

                } catch (error) {
                    console.error('Error loading settings:', error);
                    this.showAlert('Error loading settings', 'danger');
                }
            }

            renderSettingsTable(settings) {
                const tbody = document.getElementById('settingsTableBody');
                
                if (!settings || settings.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center text-muted">No settings found</td>
                        </tr>
                    `;
                    return;
                }

                const html = settings.map(setting => `
                    <tr>
                        <td>
                            <strong>${setting.key}</strong>
                        </td>
                        <td>
                            <div class="setting-value" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${setting.value}">
                                ${setting.value}
                            </div>
                        </td>
                        <td>
                            <small class="text-muted">${setting.description || 'No description'}</small>
                        </td>
                        <td>
                            <small>${this.formatDate(setting.updated_at)}</small>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-primary" onclick="settingsManagement.editSetting('${setting.id}')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="settingsManagement.deleteSetting('${setting.id}', '${setting.key}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');

                tbody.innerHTML = html;
            }

            formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
            }

            updateSettingsCount(total) {
                document.getElementById('settingsCount').textContent = `${total} settings found`;
            }

            openAddModal() {
                document.getElementById('modalTitle').textContent = 'Add New Setting';
                document.getElementById('settingForm').reset();
                document.getElementById('settingModal').classList.add('show');
            }

            async editSetting(settingId) {
                try {
                    const response = await this.utils?.apiRequest(`settings.php?action=single&id=${settingId}`) ||
                                   await this.supabase.from('settings').select('*').eq('id', settingId).single();
                    
                    const setting = response.setting || response.data;

                    // Populate form
                    document.getElementById('modalTitle').textContent = 'Edit Setting';
                    document.getElementById('settingId').value = settingId;
                    document.getElementById('settingKey').value = setting.key || '';
                    document.getElementById('settingValue').value = setting.value || '';
                    document.getElementById('settingDescription').value = setting.description || '';

                    // Show modal
                    document.getElementById('settingModal').classList.add('show');

                } catch (error) {
                    console.error('Error loading setting:', error);
                    this.showAlert('Error loading setting details', 'danger');
                }
            }

            async saveSetting() {
                try {
                    const settingId = document.getElementById('settingId').value;
                    const data = {
                        key: document.getElementById('settingKey').value,
                        value: document.getElementById('settingValue').value,
                        description: document.getElementById('settingDescription').value
                    };

                    if (settingId) {
                        // Update existing setting
                        await this.utils?.apiRequest(`settings.php?id=${settingId}`, 'PATCH', data);
                        this.showAlert('Setting updated successfully', 'success');
                    } else {
                        // Create new setting
                        await this.utils?.apiRequest('settings.php', 'POST', data);
                        this.showAlert('Setting created successfully', 'success');
                    }
                    
                    this.closeSettingModal();
                    this.loadSettings();

                } catch (error) {
                    console.error('Error saving setting:', error);
                    this.showAlert('Error saving setting', 'danger');
                }
            }

            async deleteSetting(settingId, settingKey) {
                this.utils?.showConfirm(
                    'Delete Setting',
                    `Are you sure you want to delete the setting "${settingKey}"? This action cannot be undone.`,
                    async () => {
                        try {
                            await this.utils?.apiRequest(`settings.php?id=${settingId}`, 'DELETE');
                            this.showAlert('Setting deleted successfully', 'success');
                            this.loadSettings();
                        } catch (error) {
                            console.error('Error deleting setting:', error);
                            this.showAlert('Error deleting setting', 'danger');
                        }
                    }
                );
            }

            async updateQuickSetting(key, value) {
                try {
                    // Check if setting exists
                    let response;
                    try {
                        response = await this.utils?.apiRequest(`settings.php?action=key&key=${key}`) ||
                                  await this.supabase.from('settings').select('*').eq('key', key).single();
                    } catch (error) {
                        // Setting doesn't exist, create it
                        await this.utils?.apiRequest('settings.php', 'POST', {
                            key: key,
                            value: value,
                            description: `Quick setting for ${key.replace('_', ' ')}`
                        });
                        this.showAlert('Setting created successfully', 'success');
                        return;
                    }

                    // Update existing setting
                    const setting = response.setting || response.data;
                    await this.utils?.apiRequest(`settings.php?id=${setting.id}`, 'PATCH', { value: value });
                    this.showAlert('Setting updated successfully', 'success');
                    
                } catch (error) {
                    console.error('Error updating quick setting:', error);
                    this.showAlert('Error updating setting', 'danger');
                }
            }

            closeSettingModal() {
                document.getElementById('settingModal').classList.remove('show');
            }

            showAlert(message, type = 'info') {
                // Use AdminUtils if available, otherwise create simple alert
                if (this.utils?.showAlert) {
                    this.utils.showAlert(message, type);
                } else {
                    alert(message);
                }
            }
        }

        // Global functions for modal
        function closeSettingModal() {
            window.settingsManagement.closeSettingModal();
        }

        function saveSetting() {
            window.settingsManagement.saveSetting();
        }

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            window.settingsManagement = new SettingsManagement();
        });
    </script>
</body>
</html>