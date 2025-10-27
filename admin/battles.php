<?php
/**
 * Battles Management Page - JavaScript Authentication Version
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
    <title>Battle Records - Admin Panel</title>
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
                        <h3 class="card-title">Battle Records Management</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="form-label">Mode</label>
                                    <select class="form-control" id="modeFilter">
                                        <option value="">All Modes</option>
                                        <option value="arena">Arena</option>
                                        <option value="quick">Quick</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="form-label">Result</label>
                                    <select class="form-control" id="resultFilter">
                                        <option value="">All Results</option>
                                        <option value="Player1Wins">Player 1 Wins</option>
                                        <option value="Player2Wins">Player 2 Wins</option>
                                        <option value="Draw">Draw</option>
                                        <option value="Incomplete">Incomplete</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="form-label">Player</label>
                                    <input type="text" class="form-control" id="playerFilter" placeholder="Search player...">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="form-label">From Date</label>
                                    <input type="date" class="form-control" id="dateFromFilter">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="form-label">To Date</label>
                                    <input type="date" class="form-control" id="dateToFilter">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-primary" id="searchBtn">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        <button class="btn btn-success" id="exportBtn">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Battle Statistics -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-card-icon">
                                <i class="fas fa-gamepad"></i>
                            </div>
                            <div class="stat-card-value" id="totalBattlesStat">-</div>
                            <div class="stat-card-label">Total Battles</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-card-icon">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <div class="stat-card-value" id="arenaBattlesStat">-</div>
                            <div class="stat-card-label">Arena Battles</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-card-icon">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <div class="stat-card-value" id="quickBattlesStat">-</div>
                            <div class="stat-card-label">Quick Battles</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-card-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-card-value" id="completedBattlesStat">-</div>
                            <div class="stat-card-label">Completed</div>
                        </div>
                    </div>
                </div>

                <!-- Battles Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Battle History</h3>
                        <div>
                            <span class="text-muted" id="battlesCount">Loading...</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Battle ID</th>
                                        <th>Players</th>
                                        <th>Mode</th>
                                        <th>Subject</th>
                                        <th>Result</th>
                                        <th>Duration</th>
                                        <th>Start Time</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="battlesTableBody">
                                    <tr>
                                        <td colspan="8" class="text-center">
                                            <div class="loading">
                                                <div class="spinner"></div>
                                                <p>Loading battles...</p>
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

        class BattleManagement {
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
                        await this.loadBattleStats();
                        this.loadBattles();
                    } else {
                        document.getElementById('loadingOverlay').style.display = 'none';
                        document.getElementById('accessDenied').style.display = 'block';
                    }
                } catch (error) {
                    console.error('Error initializing battle management:', error);
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
                    this.loadBattles();
                });

                // Export button
                document.getElementById('exportBtn').addEventListener('click', () => {
                    this.exportBattles();
                });

                // Filter changes
                ['modeFilter', 'resultFilter', 'playerFilter', 'dateFromFilter', 'dateToFilter'].forEach(id => {
                    document.getElementById(id).addEventListener('change', () => {
                        this.currentPage = 1;
                        this.loadBattles();
                    });
                });

                // Enter key on player filter
                document.getElementById('playerFilter').addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        this.currentPage = 1;
                        this.loadBattles();
                    }
                });
            }

            async loadBattleStats() {
                try {
                    const response = await this.utils?.apiRequest('battles.php?action=stats') ||
                                   await this.supabase.from('battle_records').select('battle_mode, battle_result');

                    if (response.total_battles !== undefined) {
                        // API response
                        document.getElementById('totalBattlesStat').textContent = this.formatNumber(response.total_battles);
                        document.getElementById('arenaBattlesStat').textContent = this.formatNumber(response.arena_battles);
                        document.getElementById('quickBattlesStat').textContent = this.formatNumber(response.quick_battles);
                        document.getElementById('completedBattlesStat').textContent = this.formatNumber(response.completed_battles);
                    } else {
                        // Direct Supabase response - calculate stats
                        const battles = response.data || [];
                        const total = battles.length;
                        const arena = battles.filter(b => b.battle_mode === 'arena').length;
                        const quick = battles.filter(b => b.battle_mode === 'quick').length;
                        const completed = battles.filter(b => b.battle_result !== 'Incomplete').length;

                        document.getElementById('totalBattlesStat').textContent = this.formatNumber(total);
                        document.getElementById('arenaBattlesStat').textContent = this.formatNumber(arena);
                        document.getElementById('quickBattlesStat').textContent = this.formatNumber(quick);
                        document.getElementById('completedBattlesStat').textContent = this.formatNumber(completed);
                    }
                } catch (error) {
                    console.error('Error loading battle stats:', error);
                }
            }

            getCurrentFilters() {
                return {
                    mode: document.getElementById('modeFilter').value,
                    result: document.getElementById('resultFilter').value,
                    player: document.getElementById('playerFilter').value.trim(),
                    date_from: document.getElementById('dateFromFilter').value,
                    date_to: document.getElementById('dateToFilter').value,
                    page: this.currentPage,
                    limit: 20
                };
            }

            async loadBattles() {
                try {
                    this.currentFilters = this.getCurrentFilters();
                    
                    // Build Supabase query
                    let query = this.supabase.from('battle_records').select('*');
                    
                    // Apply filters
                    if (this.currentFilters.mode) {
                        query = query.eq('battle_mode', this.currentFilters.mode);
                    }
                    
                    if (this.currentFilters.result) {
                        query = query.eq('battle_result', this.currentFilters.result);
                    }
                    
                    if (this.currentFilters.player) {
                        query = query.or(`player1_name.ilike.%${this.currentFilters.player}%,player2_name.ilike.%${this.currentFilters.player}%`);
                    }
                    
                    if (this.currentFilters.date_from) {
                        query = query.gte('start_time', this.currentFilters.date_from + 'T00:00:00');
                    }
                    
                    if (this.currentFilters.date_to) {
                        query = query.lte('start_time', this.currentFilters.date_to + 'T23:59:59');
                    }
                    
                    // Add ordering and pagination
                    query = query.order('start_time', { ascending: false });
                    query = query.range(
                        (this.currentPage - 1) * this.currentFilters.limit,
                        this.currentPage * this.currentFilters.limit - 1
                    );

                    const { data: battles, error } = await query;

                    if (error) {
                        throw error;
                    }

                    this.renderBattlesTable(battles || []);
                    this.updateBattlesCount(battles ? battles.length : 0);

                } catch (error) {
                    console.error('Error loading battles:', error);
                    this.showAlert('Error loading battles', 'danger');
                }
            }

            renderBattlesTable(battles) {
                const tbody = document.getElementById('battlesTableBody');
                
                if (!battles || battles.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="8" class="text-center text-muted">No battles found</td>
                        </tr>
                    `;
                    return;
                }

                const html = battles.map(battle => `
                    <tr>
                        <td>
                            <small class="text-muted">${battle.battle_id.substring(0, 8)}...</small>
                        </td>
                        <td>
                            <div>
                                <strong>${battle.player1_name}</strong> vs <strong>${battle.player2_name}</strong>
                            </div>
                            <small class="text-muted">
                                ${battle.player1_correct_answers}/${battle.questions_count} vs ${battle.player2_correct_answers}/${battle.questions_count}
                            </small>
                        </td>
                        <td>
                            <span class="badge badge-${battle.battle_mode === 'arena' ? 'primary' : 'success'}">${battle.battle_mode}</span>
                        </td>
                        <td>
                            <span class="badge badge-info">${battle.subject || 'General'}</span>
                        </td>
                        <td>
                            <span class="badge badge-${this.getResultClass(battle.battle_result)}">${this.formatResult(battle.battle_result)}</span>
                        </td>
                        <td>
                            <small>${this.formatDuration(battle.duration_seconds)}</small>
                        </td>
                        <td>
                            <small>${this.formatDate(battle.start_time)}</small>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-info" onclick="battleManagement.viewBattleDetails('${battle.battle_id}')">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="battleManagement.deleteBattle('${battle.battle_id}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');

                tbody.innerHTML = html;
            }

            getResultClass(result) {
                const classes = {
                    'Player1Wins': 'success',
                    'Player2Wins': 'success',
                    'Draw': 'warning',
                    'Incomplete': 'secondary'
                };
                return classes[result] || 'secondary';
            }

            formatResult(result) {
                const formats = {
                    'Player1Wins': 'P1 Wins',
                    'Player2Wins': 'P2 Wins',
                    'Draw': 'Draw',
                    'Incomplete': 'Incomplete'
                };
                return formats[result] || result;
            }

            formatDuration(seconds) {
                if (!seconds) return '-';
                const minutes = Math.floor(seconds / 60);
                const remainingSeconds = seconds % 60;
                return `${minutes}m ${remainingSeconds}s`;
            }

            formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
            }

            formatNumber(num) {
                return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }

            updateBattlesCount(total) {
                document.getElementById('battlesCount').textContent = `${total} battles found`;
            }

            async viewBattleDetails(battleId) {
                try {
                    const response = await this.utils?.apiRequest(`battles.php?action=single&id=${battleId}`) ||
                                   await this.supabase.from('battle_records').select('*').eq('battle_id', battleId).single();
                    
                    const battle = response.battle || response.data;
                    
                    // Create modal for battle details
                    const modal = document.createElement('div');
                    modal.className = 'modal show';
                    modal.innerHTML = `
                        <div class="modal-content" style="max-width: 800px;">
                            <div class="modal-header">
                                <h5 class="modal-title">Battle Details</h5>
                                <button type="button" class="modal-close" onclick="this.closest('.modal').remove()">&times;</button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Battle Information</h6>
                                        <p><strong>ID:</strong> ${battle.battle_id}</p>
                                        <p><strong>Mode:</strong> ${battle.battle_mode}</p>
                                        <p><strong>Subject:</strong> ${battle.subject || 'General'}</p>
                                        <p><strong>Difficulty:</strong> ${battle.difficulty}</p>
                                        <p><strong>Questions:</strong> ${battle.questions_count}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Players</h6>
                                        <p><strong>Player 1:</strong> ${battle.player1_name}</p>
                                        <p><strong>Player 2:</strong> ${battle.player2_name}</p>
                                        <p><strong>P1 Score:</strong> ${battle.player1_correct_answers}/${battle.questions_count}</p>
                                        <p><strong>P2 Score:</strong> ${battle.player2_correct_answers}/${battle.questions_count}</p>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <h6>Timing</h6>
                                        <p><strong>Start:</strong> ${this.formatDate(battle.start_time)}</p>
                                        <p><strong>End:</strong> ${battle.end_time ? this.formatDate(battle.end_time) : 'Not finished'}</p>
                                        <p><strong>Duration:</strong> ${this.formatDuration(battle.duration_seconds)}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Results</h6>
                                        <p><strong>Result:</strong> <span class="badge badge-${this.getResultClass(battle.battle_result)}">${this.formatResult(battle.battle_result)}</span></p>
                                        <p><strong>P1 Points:</strong> ${battle.player1_final_points || 0}</p>
                                        <p><strong>P2 Points:</strong> ${battle.player2_final_points || 0}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').remove()">Close</button>
                            </div>
                        </div>
                    `;
                    
                    document.body.appendChild(modal);
                    
                } catch (error) {
                    console.error('Error loading battle details:', error);
                    this.showAlert('Error loading battle details', 'danger');
                }
            }

            async deleteBattle(battleId) {
                this.utils?.showConfirm(
                    'Delete Battle',
                    'Are you sure you want to delete this battle record? This action cannot be undone.',
                    async () => {
                        try {
                            await this.utils?.apiRequest(`battles.php?id=${battleId}`, 'DELETE');
                            this.showAlert('Battle deleted successfully', 'success');
                            this.loadBattles();
                            this.loadBattleStats();
                        } catch (error) {
                            console.error('Error deleting battle:', error);
                            this.showAlert('Error deleting battle', 'danger');
                        }
                    }
                );
            }

            async exportBattles() {
                try {
                    // Create a link to download the CSV
                    const exportUrl = `api/battles.php?action=export`;
                    const link = document.createElement('a');
                    link.href = exportUrl;
                    link.download = `battle_records_${new Date().toISOString().split('T')[0]}.csv`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    this.showAlert('Battle records exported successfully', 'success');
                } catch (error) {
                    console.error('Error exporting battles:', error);
                    this.showAlert('Error exporting battles', 'danger');
                }
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
            window.battleManagement = new BattleManagement();
        });
    </script>
</body>
</html>