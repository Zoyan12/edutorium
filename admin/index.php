<?php
/**
 * Admin Panel - JavaScript Authentication Version
 * Uses JavaScript to check admin status instead of PHP sessions
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
    <title>Admin Dashboard - Edutorium</title>
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
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-card-value" id="totalUsersCard">-</div>
                        <div class="stat-card-label">Total Users</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-icon">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <div class="stat-card-value" id="totalQuestionsCard">-</div>
                        <div class="stat-card-label">Total Questions</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-icon">
                            <i class="fas fa-gamepad"></i>
                        </div>
                        <div class="stat-card-value" id="totalBattlesCard">-</div>
                        <div class="stat-card-label">Total Battles</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-icon">
                            <i class="fas fa-user-friends"></i>
                        </div>
                        <div class="stat-card-value" id="totalFriendshipsCard">-</div>
                        <div class="stat-card-label">Active Friendships</div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="d-flex gap-2">
                            <a href="questions.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                Add New Question
                            </a>
                            <a href="users.php" class="btn btn-secondary">
                                <i class="fas fa-users"></i>
                                Manage Users
                            </a>
                            <a href="settings.php" class="btn btn-secondary">
                                <i class="fas fa-cog"></i>
                                System Settings
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Recent Users</h3>
                            </div>
                            <div class="card-body">
                                <div id="recentUsers">
                                    <div class="loading">
                                        <div class="spinner"></div>
                                        <p>Loading...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Recent Battles</h3>
                            </div>
                            <div class="card-body">
                                <div id="recentBattles">
                                    <div class="loading">
                                        <div class="spinner"></div>
                                        <p>Loading...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Status -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">System Status</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="stat-value" id="pendingRequests">-</div>
                                    <div class="stat-label">Pending Friend Requests</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="stat-value" id="activeUsers">-</div>
                                    <div class="stat-label">Active Users Today</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="stat-value" id="battlesToday">-</div>
                                    <div class="stat-label">Battles Today</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="stat-value" id="questionsToday">-</div>
                                    <div class="stat-label">Questions Added Today</div>
                                </div>
                            </div>
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
        class AdminDashboard {
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
                        await this.loadDashboardData();
                    } else {
                        document.getElementById('loadingOverlay').style.display = 'none';
                        document.getElementById('accessDenied').style.display = 'block';
                    }
                } catch (error) {
                    console.error('Error initializing admin dashboard:', error);
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

            async loadDashboardData() {
                try {
                    // Load basic statistics
                    const [usersCount, questionsCount, battlesCount, friendshipsCount] = await Promise.all([
                        this.supabase.from('profiles').select('count', { count: 'exact' }),
                        this.supabase.from('questions').select('count', { count: 'exact' }),
                        this.supabase.from('battle_records').select('count', { count: 'exact' }),
                        this.supabase.from('friend_relationships').select('count', { count: 'exact' })
                    ]);

                    // Update statistics cards
                    document.getElementById('totalUsersCard').textContent = this.formatNumber(usersCount.count || 0);
                    document.getElementById('totalQuestionsCard').textContent = this.formatNumber(questionsCount.count || 0);
                    document.getElementById('totalBattlesCard').textContent = this.formatNumber(battlesCount.count || 0);
                    document.getElementById('totalFriendshipsCard').textContent = this.formatNumber(friendshipsCount.count || 0);

                    // Load recent activity
                    await this.loadRecentActivity();

                } catch (error) {
                    console.error('Error loading dashboard data:', error);
                }
            }

            async loadRecentActivity() {
                try {
                    // Load recent users (using id instead of created_at)
                    const { data: recentUsers } = await this.supabase
                        .from('profiles')
                        .select('id, username, field, points')
                        .order('id', { ascending: false })
                        .limit(5);

                    this.renderRecentUsers(recentUsers || []);

                    // Load recent battles
                    const { data: recentBattles } = await this.supabase
                        .from('battle_records')
                        .select('player1_name, player2_name, battle_mode, battle_result, subject, start_time')
                        .order('start_time', { ascending: false })
                        .limit(5);

                    this.renderRecentBattles(recentBattles || []);

                } catch (error) {
                    console.error('Error loading recent activity:', error);
                }
            }

            renderRecentUsers(users) {
                const container = document.getElementById('recentUsers');
                
                if (!users || users.length === 0) {
                    container.innerHTML = '<p class="text-center text-muted">No recent users</p>';
                    return;
                }

                const html = users.map(user => `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <strong>${user.username}</strong>
                            <br>
                            <small class="text-muted">${user.field || 'No field specified'}</small>
                        </div>
                        <div class="text-right">
                            <small class="text-muted">ID: ${user.id}</small>
                            <br>
                            <span class="badge badge-primary">${user.points || 0} pts</span>
                        </div>
                    </div>
                `).join('');

                container.innerHTML = html;
            }

            renderRecentBattles(battles) {
                const container = document.getElementById('recentBattles');
                
                if (!battles || battles.length === 0) {
                    container.innerHTML = '<p class="text-center text-muted">No recent battles</p>';
                    return;
                }

                const html = battles.map(battle => `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <strong>${battle.player1_name} vs ${battle.player2_name}</strong>
                            <br>
                            <small class="text-muted">${battle.battle_mode} • ${battle.subject}</small>
                        </div>
                        <div class="text-right">
                            <small class="text-muted">${this.formatDate(battle.start_time)}</small>
                            <br>
                            <span class="badge badge-${battle.battle_result === 'Incomplete' ? 'warning' : 'success'}">${battle.battle_result}</span>
                        </div>
                    </div>
                `).join('');

                container.innerHTML = html;
            }

            formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
            }

            formatNumber(num) {
                return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }
        }

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new AdminDashboard();
        });
    </script>
</body>
</html>