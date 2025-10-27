<?php
/**
 * Users Management Page - JavaScript Authentication Version
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
    <title>User Management - Admin Panel</title>
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
                        <h3 class="card-title">User Management</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Search Users</label>
                                    <input type="text" class="form-control" id="searchInput" placeholder="Search by username or name...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">Field</label>
                                    <select class="form-control" id="fieldFilter">
                                        <option value="">All Fields</option>
                                        <option value="JEE">JEE</option>
                                        <option value="NEET">NEET</option>
                                        <option value="General">General</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">Admin Status</label>
                                    <select class="form-control" id="adminFilter">
                                        <option value="">All Users</option>
                                        <option value="true">Admins Only</option>
                                        <option value="false">Non-Admins Only</option>
                                    </select>
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
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Users List</h3>
                        <div>
                            <span class="text-muted" id="usersCount">Loading...</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Field</th>
                                        <th>Points</th>
                                        <th>Admin</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody">
                                    <tr>
                                        <td colspan="8" class="text-center">
                                            <div class="loading">
                                                <div class="spinner"></div>
                                                <p>Loading users...</p>
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

    <!-- Edit User Modal -->
    <div class="modal" id="editUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" id="editUserId">
                    
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" id="editUsername" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="editFullName">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Field</label>
                        <select class="form-control" id="editField">
                            <option value="">Select Field</option>
                            <option value="JEE">JEE</option>
                            <option value="NEET">NEET</option>
                            <option value="General">General</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Points</label>
                        <input type="number" class="form-control" id="editPoints" min="0">
                    </div>
                    
                    <div class="form-group">
                        <div class="d-flex align-items-center">
                            <input type="checkbox" id="editIsAdmin" class="me-2">
                            <label class="form-label mb-0">Admin User</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="d-flex align-items-center">
                            <input type="checkbox" id="editIsComplete" class="me-2">
                            <label class="form-label mb-0">Profile Complete</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveUser()">Save Changes</button>
            </div>
        </div>
    </div>

    <script>
        class UserManagement {
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
                        this.loadUsers();
                    } else {
                        document.getElementById('loadingOverlay').style.display = 'none';
                        document.getElementById('accessDenied').style.display = 'block';
                    }
                } catch (error) {
                    console.error('Error initializing user management:', error);
                    document.getElementById('loadingOverlay').style.display = 'none';
                    document.getElementById('accessDenied').style.display = 'block';
                }
            }

            async checkAdminAccess() {
                try {
                    // Get current session
                    const { data: { session }, error } = await this.supabase.auth.getSession();
                    
                    if (error || !session) {
                        console.log('No active session');
                        return false;
                    }

                    // Get user profile to check admin status
                    const { data: profile, error: profileError } = await this.supabase
                        .from('profiles')
                        .select('is_admin, username, full_name')
                        .eq('user_id', session.user.id)
                        .single();

                    if (profileError || !profile) {
                        console.log('Profile not found');
                        return false;
                    }

                    // Store admin info globally
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
                    this.loadUsers();
                });

                // Enter key on search input
                document.getElementById('searchInput').addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        this.currentPage = 1;
                        this.loadUsers();
                    }
                });

                // Filter changes
                ['fieldFilter', 'adminFilter'].forEach(id => {
                    document.getElementById(id).addEventListener('change', () => {
                        this.currentPage = 1;
                        this.loadUsers();
                    });
                });
            }

            getCurrentFilters() {
                return {
                    search: document.getElementById('searchInput').value.trim(),
                    field: document.getElementById('fieldFilter').value,
                    admin_only: document.getElementById('adminFilter').value,
                    page: this.currentPage,
                    limit: 20
                };
            }

            async loadUsers() {
                try {
                    this.currentFilters = this.getCurrentFilters();
                    
                    // Build Supabase query
                    let query = this.supabase.from('profiles').select('*');
                    
                    // Apply filters
                    if (this.currentFilters.search) {
                        query = query.or(`username.ilike.%${this.currentFilters.search}%,full_name.ilike.%${this.currentFilters.search}%`);
                    }
                    
                    if (this.currentFilters.field) {
                        query = query.eq('field', this.currentFilters.field);
                    }
                    
                    if (this.currentFilters.admin_only) {
                        query = query.eq('is_admin', this.currentFilters.admin_only === 'true');
                    }
                    
                    // Add ordering and pagination (using id since created_at doesn't exist in profiles table)
                    query = query.order('id', { ascending: false });
                    query = query.range(
                        (this.currentPage - 1) * this.currentFilters.limit,
                        this.currentPage * this.currentFilters.limit - 1
                    );

                    const { data: users, error } = await query;

                    if (error) {
                        throw error;
                    }

                    this.renderUsersTable(users || []);
                    this.updateUsersCount(users ? users.length : 0);

                } catch (error) {
                    console.error('Error loading users:', error);
                    this.showAlert('Error loading users', 'danger');
                }
            }

            renderUsersTable(users) {
                const tbody = document.getElementById('usersTableBody');
                
                if (!users || users.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="8" class="text-center text-muted">No users found</td>
                        </tr>
                    `;
                    return;
                }

                const html = users.map(user => `
                    <tr>
                        <td>
                            <strong>${user.username}</strong>
                        </td>
                        <td>${user.full_name || '-'}</td>
                        <td>
                            <span class="badge badge-info">${user.field || 'Not set'}</span>
                        </td>
                        <td>
                            <span class="badge badge-primary">${user.points || 0}</span>
                        </td>
                        <td>
                            ${user.is_admin ? 
                                '<span class="badge badge-warning"><i class="fas fa-crown"></i> Admin</span>' : 
                                '<span class="badge badge-secondary">User</span>'
                            }
                        </td>
                        <td>
                            ${user.is_complete ? 
                                '<span class="badge badge-success">Complete</span>' : 
                                '<span class="badge badge-warning">Incomplete</span>'
                            }
                        </td>
                        <td>
                            <small>User ID: ${user.id}</small>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-primary" onclick="userManagement.editUser('${user.user_id}')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="userManagement.deleteUser('${user.user_id}', '${user.username}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');

                tbody.innerHTML = html;
            }

            updateUsersCount(total) {
                document.getElementById('usersCount').textContent = `${total} users found`;
            }

            formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
            }

            showAlert(message, type = 'info') {
                // Remove existing alerts
                const existingAlerts = document.querySelectorAll('.alert');
                existingAlerts.forEach(alert => alert.remove());

                // Create new alert
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type}`;
                alertDiv.innerHTML = `
                    ${message}
                    <button type="button" class="btn-close" onclick="this.parentElement.remove()" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;">&times;</button>
                `;

                // Insert at top of content area
                const contentArea = document.querySelector('.content-area');
                if (contentArea) {
                    contentArea.insertBefore(alertDiv, contentArea.firstChild);
                }

                // Auto remove after 5 seconds
                setTimeout(() => {
                    if (alertDiv.parentElement) {
                        alertDiv.remove();
                    }
                }, 5000);
            }

            async editUser(userId) {
                try {
                    // Use AdminUtils if available, otherwise use direct Supabase query
                    let response;
                    if (window.adminUtils?.apiRequest) {
                        response = await window.adminUtils.apiRequest(`users.php?action=single&id=${userId}`);
                    } else {
                        response = await this.supabase.from('profiles').select('*').eq('user_id', userId).single();
                    }
                    
                    const user = response.user || response.data;

                    // Populate form
                    document.getElementById('editUserId').value = userId;
                    document.getElementById('editUsername').value = user.username || '';
                    document.getElementById('editFullName').value = user.full_name || '';
                    document.getElementById('editField').value = user.field || '';
                    document.getElementById('editPoints').value = user.points || 0;
                    document.getElementById('editIsAdmin').checked = user.is_admin || false;
                    document.getElementById('editIsComplete').checked = user.is_complete || false;

                    // Show modal
                    document.getElementById('editUserModal').classList.add('show');

                } catch (error) {
                    console.error('Error loading user:', error);
                    this.showAlert('Error loading user details', 'danger');
                }
            }

            async deleteUser(userId, username) {
                // Use AdminUtils if available, otherwise use simple confirm
                if (window.adminUtils?.showConfirm) {
                    window.adminUtils.showConfirm(
                        'Delete User',
                        `Are you sure you want to delete user "${username}"? This action cannot be undone.`,
                        async () => {
                            try {
                                if (window.adminUtils?.apiRequest) {
                                    await window.adminUtils.apiRequest(`users.php?id=${userId}`, 'DELETE');
                                } else {
                                    await this.supabase.from('profiles').delete().eq('user_id', userId);
                                }
                                this.showAlert('User deleted successfully', 'success');
                                this.loadUsers();
                            } catch (error) {
                                console.error('Error deleting user:', error);
                                this.showAlert('Error deleting user', 'danger');
                            }
                        }
                    );
                } else {
                    if (confirm(`Are you sure you want to delete user "${username}"? This action cannot be undone.`)) {
                        try {
                            await this.supabase.from('profiles').delete().eq('user_id', userId);
                            this.showAlert('User deleted successfully', 'success');
                            this.loadUsers();
                        } catch (error) {
                            console.error('Error deleting user:', error);
                            this.showAlert('Error deleting user', 'danger');
                        }
                    }
                }
            }
            async saveUser() {
                try {
                    const userId = document.getElementById('editUserId').value;
                    const data = {
                        username: document.getElementById('editUsername').value,
                        full_name: document.getElementById('editFullName').value,
                        field: document.getElementById('editField').value,
                        points: parseInt(document.getElementById('editPoints').value) || 0,
                        is_admin: document.getElementById('editIsAdmin').checked,
                        is_complete: document.getElementById('editIsComplete').checked
                    };

                    // Use AdminUtils if available, otherwise use direct Supabase update
                    if (window.adminUtils?.apiRequest) {
                        await window.adminUtils.apiRequest(`users.php?id=${userId}`, 'PATCH', data);
                    } else {
                        await this.supabase.from('profiles').update(data).eq('user_id', userId);
                    }
                    
                    this.showAlert('User updated successfully', 'success');
                    this.closeEditModal();
                    this.loadUsers();

                } catch (error) {
                    console.error('Error saving user:', error);
                    this.showAlert('Error updating user', 'danger');
                }
            }

            closeEditModal() {
                document.getElementById('editUserModal').classList.remove('show');
            }
        }

        // Global functions for modal
        function closeEditModal() {
            window.userManagement.closeEditModal();
        }

        function saveUser() {
            window.userManagement.saveUser();
        }

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            window.userManagement = new UserManagement();
        });
    </script>
</body>
</html>
