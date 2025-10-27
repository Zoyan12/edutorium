/**
 * Admin Panel JavaScript Utilities
 */

class AdminUtils {
    constructor() {
        // Initialize Supabase client
        this.supabase = window.supabase.createClient(
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

    init() {
        this.setupSidebarToggle();
        this.setupLogout();
        this.setupPageTitle();
    }

    /**
     * Setup sidebar toggle for mobile
     */
    setupSidebarToggle() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('open');
            });
        }
    }

    /**
     * Setup logout functionality
     */
    setupLogout() {
        const logoutBtn = document.getElementById('logoutBtn');
        
        if (logoutBtn) {
            logoutBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                
                if (confirm('Are you sure you want to logout?')) {
                    try {
                        await this.supabase.auth.signOut();
                        window.location.href = '../pages/login.html';
                    } catch (error) {
                        console.error('Logout error:', error);
                        this.showAlert('Error logging out', 'danger');
                    }
                }
            });
        }
    }

    /**
     * Set page title based on current page
     */
    setupPageTitle() {
        const pageTitle = document.getElementById('pageTitle');
        if (pageTitle) {
            const currentPage = window.location.pathname.split('/').pop().replace('.php', '');
            const titles = {
                'index': 'Dashboard',
                'users': 'User Management',
                'questions': 'Question Management',
                'battles': 'Battle Records',
                'friendships': 'Friendship Management',
                'settings': 'System Settings'
            };
            
            pageTitle.textContent = titles[currentPage] || 'Admin Panel';
        }
    }

    /**
     * Make API request with error handling
     */
    async apiRequest(endpoint, method = 'GET', data = null) {
        try {
            const options = {
                method,
                headers: {
                    'Content-Type': 'application/json',
                }
            };

            if (data) {
                options.body = JSON.stringify(data);
            }

            const response = await fetch(`api/${endpoint}`, options);
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || 'API request failed');
            }

            return result;
        } catch (error) {
            console.error('API request error:', error);
            this.showAlert(error.message, 'danger');
            throw error;
        }
    }

    /**
     * Show alert message
     */
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

    /**
     * Show confirmation modal
     */
    showConfirm(title, message, onConfirm) {
        const modal = document.createElement('div');
        modal.className = 'modal show';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">${title}</h5>
                    <button type="button" class="modal-close" onclick="this.closest('.modal').remove()">&times;</button>
                </div>
                <div class="modal-body">
                    <p>${message}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').remove()">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmBtn">Confirm</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        document.getElementById('confirmBtn').addEventListener('click', () => {
            onConfirm();
            modal.remove();
        });
    }

    /**
     * Show loading state
     */
    showLoading(element) {
        if (element) {
            element.innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Loading...</p>
                </div>
            `;
        }
    }

    /**
     * Format date for display
     */
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }

    /**
     * Format number with commas
     */
    formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    /**
     * Create pagination controls
     */
    createPagination(currentPage, totalPages, onPageChange) {
        const pagination = document.createElement('div');
        pagination.className = 'pagination';
        pagination.innerHTML = '';

        // Previous button
        if (currentPage > 1) {
            const prevBtn = document.createElement('button');
            prevBtn.className = 'btn btn-secondary btn-sm';
            prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i> Previous';
            prevBtn.addEventListener('click', () => onPageChange(currentPage - 1));
            pagination.appendChild(prevBtn);
        }

        // Page numbers
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);

        for (let i = startPage; i <= endPage; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = `btn btn-sm ${i === currentPage ? 'btn-primary' : 'btn-secondary'}`;
            pageBtn.textContent = i;
            pageBtn.addEventListener('click', () => onPageChange(i));
            pagination.appendChild(pageBtn);
        }

        // Next button
        if (currentPage < totalPages) {
            const nextBtn = document.createElement('button');
            nextBtn.className = 'btn btn-secondary btn-sm';
            nextBtn.innerHTML = 'Next <i class="fas fa-chevron-right"></i>';
            nextBtn.addEventListener('click', () => onPageChange(currentPage + 1));
            pagination.appendChild(nextBtn);
        }

        return pagination;
    }

    /**
     * Load dashboard statistics
     */
    async loadDashboardStats() {
        try {
            const [usersCount, questionsCount, battlesCount] = await Promise.all([
                this.apiRequest('users.php?action=count'),
                this.apiRequest('questions.php?action=count'),
                this.apiRequest('battles.php?action=count')
            ]);

            // Update header stats
            const totalUsersEl = document.getElementById('totalUsers');
            const totalQuestionsEl = document.getElementById('totalQuestions');
            const totalBattlesEl = document.getElementById('totalBattles');

            if (totalUsersEl) totalUsersEl.textContent = this.formatNumber(usersCount.count);
            if (totalQuestionsEl) totalQuestionsEl.textContent = this.formatNumber(questionsCount.count);
            if (totalBattlesEl) totalBattlesEl.textContent = this.formatNumber(battlesCount.count);

            return { usersCount, questionsCount, battlesCount };
        } catch (error) {
            console.error('Error loading dashboard stats:', error);
            return null;
        }
    }
}

// AdminUtils is available globally

// Initialize admin utils when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.adminUtils = new AdminUtils();
});

// Make AdminUtils available globally
window.AdminUtils = AdminUtils;
