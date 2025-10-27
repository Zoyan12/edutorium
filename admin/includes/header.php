<?php
/**
 * Admin Header Component
 */
?>

<div class="main-header">
    <div class="header-left">
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="page-title" id="pageTitle">Dashboard</h1>
    </div>
    
    <div class="header-right">
        <div class="header-stats">
            <div class="stat-item">
                <span class="stat-label">Total Users</span>
                <span class="stat-value" id="totalUsers">-</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total Questions</span>
                <span class="stat-value" id="totalQuestions">-</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total Battles</span>
                <span class="stat-value" id="totalBattles">-</span>
            </div>
        </div>
        
        <div class="admin-info">
            <span class="admin-name"><?php echo htmlspecialchars($_SESSION['admin_profile']['username'] ?? 'Admin'); ?></span>
            <i class="fas fa-crown"></i>
        </div>
    </div>
</div>
