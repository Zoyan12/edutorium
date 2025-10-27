<?php
/**
 * Simple Admin Panel Test
 * Debug version to identify the issue
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
    <div class="main-content">
        <div class="content-area">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Admin Panel Test</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <strong>Success!</strong> Admin panel is loading correctly.
                    </div>
                    
                    <h5>Debug Information:</h5>
                    <ul>
                        <li><strong>Admin Username:</strong> <?php echo htmlspecialchars($_SESSION['admin_profile']['username'] ?? 'Unknown'); ?></li>
                        <li><strong>Session Status:</strong> <?php echo session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive'; ?></li>
                        <li><strong>User ID:</strong> <?php echo htmlspecialchars($_SESSION['user']['id'] ?? 'Not set'); ?></li>
                        <li><strong>Admin Status:</strong> <?php echo $_SESSION['admin_profile']['is_admin'] ? 'Admin' : 'Not Admin'; ?></li>
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
                        <h5>JavaScript Test:</h5>
                        <button class="btn btn-primary" onclick="testJavaScript()">Test JavaScript</button>
                        <div id="jsTestResult" class="mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        console.log('Admin panel test page loaded');
        
        function testJavaScript() {
            const result = document.getElementById('jsTestResult');
            result.innerHTML = '<div class="alert alert-success">JavaScript is working!</div>';
            console.log('JavaScript test successful');
        }
        
        // Test Supabase
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded');
            
            if (typeof window.supabase !== 'undefined') {
                console.log('Supabase is available');
                document.getElementById('jsTestResult').innerHTML += '<div class="alert alert-info">Supabase client is loaded</div>';
            } else {
                console.log('Supabase is not available');
                document.getElementById('jsTestResult').innerHTML += '<div class="alert alert-warning">Supabase client is not loaded</div>';
            }
        });
    </script>
</body>
</html>
