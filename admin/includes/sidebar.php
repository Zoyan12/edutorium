<?php
/**
 * Admin Sidebar Navigation Component
 */
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2 class="logo">
            <i class="fas fa-shield-alt"></i>
            Admin Panel
        </h2>
    </div>
    
    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item <?php echo $currentPage === 'index' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            Dashboard
        </a>
        
        <a href="users.php" class="nav-item <?php echo $currentPage === 'users' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            Users
        </a>
        
        <a href="questions.php" class="nav-item <?php echo $currentPage === 'questions' ? 'active' : ''; ?>">
            <i class="fas fa-question-circle"></i>
            Questions
        </a>
        
        <a href="battles.php" class="nav-item <?php echo $currentPage === 'battles' ? 'active' : ''; ?>">
            <i class="fas fa-gamepad"></i>
            Battles
        </a>
        
        <a href="friendships.php" class="nav-item <?php echo $currentPage === 'friendships' ? 'active' : ''; ?>">
            <i class="fas fa-user-friends"></i>
            Friendships
        </a>
        
        <a href="settings.php" class="nav-item <?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            Settings
        </a>
        
        <a href="maintenance.php" class="nav-item <?php echo $currentPage === 'maintenance' ? 'active' : ''; ?>">
            <i class="fas fa-tools"></i>
            Maintenance Mode
        </a>
        
        <div class="nav-divider"></div>
        
        <a href="../pages/dashboard.php" class="nav-item">
            <i class="fas fa-arrow-left"></i>
            Back to Main Site
        </a>
        
        <a href="#" class="nav-item" id="logoutBtn">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>
    </nav>
    
    <div class="user-profile">
        <div class="user-avatar">
            <i class="fas fa-user-shield"></i>
        </div>
        <div class="user-info">
            <p class="user-name" id="adminUsername">Admin</p>
            <p class="user-field">Administrator</p>
        </div>
    </div>
</div>

<script>
// Update admin username when available
document.addEventListener('DOMContentLoaded', () => {
    if (window.adminProfile && window.adminProfile.username) {
        document.getElementById('adminUsername').textContent = window.adminProfile.username;
    }
});
</script>
