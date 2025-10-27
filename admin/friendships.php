<?php
/**
 * Friendships Management Page - JavaScript Authentication Version
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
    <title>Friendship Management - Admin Panel</title>
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
                        <h3 class="card-title">Friendship Management</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">Status</label>
                                    <select class="form-control" id="statusFilter">
                                        <option value="">All Status</option>
                                        <option value="pending">Pending</option>
                                        <option value="accepted">Accepted</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
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
                                    <button class="btn btn-success w-100" id="approveAllBtn">
                                        <i class="fas fa-check"></i> Approve All Pending
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

                <!-- Friendship Statistics -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-card-icon">
                                <i class="fas fa-user-friends"></i>
                            </div>
                            <div class="stat-card-value" id="totalFriendshipsStat">-</div>
                            <div class="stat-card-label">Total Friendships</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-card-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-card-value" id="pendingRequestsStat">-</div>
                            <div class="stat-card-label">Pending Requests</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-card-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-card-value" id="acceptedFriendshipsStat">-</div>
                            <div class="stat-card-label">Accepted</div>
                        </div>
                    </div>
                </div>

                <!-- Friendships Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Friendship Relationships</h3>
                        <div>
                            <span class="text-muted" id="friendshipsCount">Loading...</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Friend</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="friendshipsTableBody">
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <div class="loading">
                                                <div class="spinner"></div>
                                                <p>Loading friendships...</p>
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

    <script>

        class FriendshipManagement {
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
                        await this.loadFriendshipStats();
                        this.loadFriendships();
                    } else {
                        document.getElementById('loadingOverlay').style.display = 'none';
                        document.getElementById('accessDenied').style.display = 'block';
                    }
                } catch (error) {
                    console.error('Error initializing friendship management:', error);
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
                    this.loadFriendships();
                });

                // Approve all button
                document.getElementById('approveAllBtn').addEventListener('click', () => {
                    this.approveAllPending();
                });

                // Refresh button
                document.getElementById('refreshBtn').addEventListener('click', () => {
                    this.loadFriendshipStats();
                    this.loadFriendships();
                });

                // Filter changes
                ['statusFilter'].forEach(id => {
                    document.getElementById(id).addEventListener('change', () => {
                        this.currentPage = 1;
                        this.loadFriendships();
                    });
                });
            }

            async loadFriendshipStats() {
                try {
                    const response = await this.utils?.apiRequest('friendships.php?action=stats') ||
                                   await this.supabase.from('friend_relationships').select('status');

                    if (response.total_friendships !== undefined) {
                        // API response
                        document.getElementById('totalFriendshipsStat').textContent = this.formatNumber(response.total_friendships);
                        document.getElementById('pendingRequestsStat').textContent = this.formatNumber(response.pending_requests);
                        document.getElementById('acceptedFriendshipsStat').textContent = this.formatNumber(response.accepted_friendships);
                    } else {
                        // Direct Supabase response - calculate stats
                        const friendships = response.data || [];
                        const total = friendships.length;
                        const pending = friendships.filter(f => f.status === 'pending').length;
                        const accepted = friendships.filter(f => f.status === 'accepted').length;

                        document.getElementById('totalFriendshipsStat').textContent = this.formatNumber(total);
                        document.getElementById('pendingRequestsStat').textContent = this.formatNumber(pending);
                        document.getElementById('acceptedFriendshipsStat').textContent = this.formatNumber(accepted);
                    }
                } catch (error) {
                    console.error('Error loading friendship stats:', error);
                }
            }

            getCurrentFilters() {
                return {
                    status: document.getElementById('statusFilter').value,
                    page: this.currentPage,
                    limit: 20
                };
            }

            async loadFriendships() {
                try {
                    this.currentFilters = this.getCurrentFilters();
                    
                    // Build Supabase query
                    let query = this.supabase.from('friend_relationships').select('*');
                    
                    // Apply filters
                    if (this.currentFilters.status) {
                        query = query.eq('status', this.currentFilters.status);
                    }
                    
                    // Add ordering and pagination
                    query = query.order('created_at', { ascending: false });
                    query = query.range(
                        (this.currentPage - 1) * this.currentFilters.limit,
                        this.currentPage * this.currentFilters.limit - 1
                    );

                    const { data: friendships, error } = await query;

                    if (error) {
                        throw error;
                    }

                    this.renderFriendshipsTable(friendships || []);
                    this.updateFriendshipsCount(friendships ? friendships.length : 0);

                } catch (error) {
                    console.error('Error loading friendships:', error);
                    this.showAlert('Error loading friendships', 'danger');
                }
            }

            renderFriendshipsTable(friendships) {
                const tbody = document.getElementById('friendshipsTableBody');
                
                if (!friendships || friendships.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center text-muted">No friendships found</td>
                        </tr>
                    `;
                    return;
                }

                const html = friendships.map(friendship => `
                    <tr>
                        <td>
                            <small class="text-muted">${friendship.id}</small>
                        </td>
                        <td>
                            <div>
                                <strong>User ID:</strong> ${friendship.user_id.substring(0, 8)}...
                            </div>
                        </td>
                        <td>
                            <div>
                                <strong>Friend ID:</strong> ${friendship.friend_id.substring(0, 8)}...
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-${friendship.status === 'accepted' ? 'success' : 'warning'}">${friendship.status}</span>
                        </td>
                        <td>
                            <small>${this.formatDate(friendship.created_at)}</small>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                ${friendship.status === 'pending' ? 
                                    `<button class="btn btn-sm btn-success" onclick="friendshipManagement.approveFriendship('${friendship.id}')">
                                        <i class="fas fa-check"></i>
                                    </button>` : ''
                                }
                                <button class="btn btn-sm btn-danger" onclick="friendshipManagement.deleteFriendship('${friendship.id}')">
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

            formatNumber(num) {
                return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }

            updateFriendshipsCount(total) {
                document.getElementById('friendshipsCount').textContent = `${total} friendships found`;
            }

            async approveFriendship(friendshipId) {
                this.utils?.showConfirm(
                    'Approve Friendship',
                    'Are you sure you want to approve this friendship request?',
                    async () => {
                        try {
                            await this.utils?.apiRequest(`friendships.php?id=${friendshipId}`, 'PATCH', { status: 'accepted' });
                            this.showAlert('Friendship approved successfully', 'success');
                            this.loadFriendships();
                            this.loadFriendshipStats();
                        } catch (error) {
                            console.error('Error approving friendship:', error);
                            this.showAlert('Error approving friendship', 'danger');
                        }
                    }
                );
            }

            async deleteFriendship(friendshipId) {
                this.utils?.showConfirm(
                    'Delete Friendship',
                    'Are you sure you want to delete this friendship? This action cannot be undone.',
                    async () => {
                        try {
                            await this.utils?.apiRequest(`friendships.php?id=${friendshipId}`, 'DELETE');
                            this.showAlert('Friendship deleted successfully', 'success');
                            this.loadFriendships();
                            this.loadFriendshipStats();
                        } catch (error) {
                            console.error('Error deleting friendship:', error);
                            this.showAlert('Error deleting friendship', 'danger');
                        }
                    }
                );
            }

            async approveAllPending() {
                this.utils?.showConfirm(
                    'Approve All Pending',
                    'Are you sure you want to approve all pending friendship requests? This action cannot be undone.',
                    async () => {
                        try {
                            // Get all pending friendships
                            const { data: pendingFriendships, error } = await this.supabase
                                .from('friend_relationships')
                                .select('id')
                                .eq('status', 'pending');

                            if (error) {
                                throw error;
                            }

                            // Approve each friendship
                            const approvePromises = pendingFriendships.map(friendship => 
                                this.utils?.apiRequest(`friendships.php?id=${friendship.id}`, 'PATCH', { status: 'accepted' })
                            );

                            await Promise.all(approvePromises);
                            
                            this.showAlert(`${pendingFriendships.length} friendships approved successfully`, 'success');
                            this.loadFriendships();
                            this.loadFriendshipStats();

                        } catch (error) {
                            console.error('Error approving all friendships:', error);
                            this.showAlert('Error approving friendships', 'danger');
                        }
                    }
                );
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

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            window.friendshipManagement = new FriendshipManagement();
        });
    </script>
</body>
</html>