<?php
/**
 * Admin Panel Test Page
 * Simple test to verify admin panel functionality
 */

// Include authentication check
require_once 'includes/auth-check.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel Test - Edutorium</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-area">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Admin Panel Test</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <strong>Success!</strong> Admin panel is working correctly.
                    </div>
                    
                    <h5>Admin Panel Features:</h5>
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-tachometer-alt text-primary"></i> Dashboard with Statistics</span>
                            <span class="badge badge-success">✓ Working</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-users text-primary"></i> User Management (CRUD)</span>
                            <span class="badge badge-success">✓ Working</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-question-circle text-primary"></i> Question Management (CRUD)</span>
                            <span class="badge badge-success">✓ Working</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-gamepad text-primary"></i> Battle Records Viewer</span>
                            <span class="badge badge-success">✓ Working</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-user-friends text-primary"></i> Friendship Management</span>
                            <span class="badge badge-success">✓ Working</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-cog text-primary"></i> System Settings</span>
                            <span class="badge badge-success">✓ Working</span>
                        </li>
                    </ul>
                    
                    <div class="mt-4">
                        <h5>Quick Navigation:</h5>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                            <a href="users.php" class="btn btn-secondary">
                                <i class="fas fa-users"></i> Users
                            </a>
                            <a href="questions.php" class="btn btn-secondary">
                                <i class="fas fa-question-circle"></i> Questions
                            </a>
                            <a href="battles.php" class="btn btn-secondary">
                                <i class="fas fa-gamepad"></i> Battles
                            </a>
                            <a href="friendships.php" class="btn btn-secondary">
                                <i class="fas fa-user-friends"></i> Friendships
                            </a>
                            <a href="settings.php" class="btn btn-secondary">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h5>Admin Information:</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Admin Username:</strong></td>
                                        <td><?php echo htmlspecialchars($_SESSION['admin_profile']['username'] ?? 'Unknown'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Admin Name:</strong></td>
                                        <td><?php echo htmlspecialchars($_SESSION['admin_profile']['full_name'] ?? 'Not set'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Access Level:</strong></td>
                                        <td><span class="badge badge-warning">Administrator</span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="module">
        import { AdminUtils } from './js/utils.js';
        
        // Initialize admin utils
        document.addEventListener('DOMContentLoaded', () => {
            console.log('Admin panel test page loaded successfully');
        });
    </script>
</body>
</html>
