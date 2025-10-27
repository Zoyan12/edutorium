<?php
/**
 * Main Landing Page with Maintenance Mode Check
 */

// Include maintenance mode check
require_once 'includes/maintenance-check.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edutorium - Interactive Learning Platform</title>
    <link rel="stylesheet" href="css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="js/utils/enhancedMaintenanceChecker.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="css/components/loading.css">
</head>

<body>
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner"></div>
        <progress class="progress w-56"></progress>
        <div class="loading-text">Loading...</div>
    </div>
    <div style="visibility: hidden; opacity: 0;" class="landing-container">
        <div class="landing-content">
            <h1 class="logo">Edutorium</h1>
            <p class="tagline">Learn. Battle. Grow.</p>
            <div class="cta-buttons">
                <button id="getStartedBtn" class="btn-primary">Get Started</button>
                <button id="loginBtn" class="btn-secondary">Login</button>
            </div>
        </div>
        <div class="landing-footer">
            <p>Prepare for JEE/NEET through interactive battles</p>
        </div>
    </div>
    <script type="module">
        import { AuthManager } from './js/auth/authManager.js';
        import { supabase } from './js/config/supabase.js';

        document.addEventListener('DOMContentLoaded', () => {
            // Initialize auth manager
            new AuthManager();

            // Set up button event listeners
            document.getElementById('getStartedBtn').addEventListener('click', () => {
                window.location.href = './pages/signup.html';
            });

            document.getElementById('loginBtn').addEventListener('click', () => {
                window.location.href = './pages/login.html';
            });

            // Check authentication status
            supabase.auth.getSession().then(({ data: { session } }) => {
                if (session) {
                    // User is logged in, redirect to dashboard
                    window.location.href = './pages/dashboard.php';
                } else {
                    // User is not logged in, show landing page
                    document.getElementById('loadingOverlay').style.display = 'none';
                    document.querySelector('.landing-container').style.visibility = 'visible';
                    document.querySelector('.landing-container').style.opacity = '1';
                }
            }).catch((error) => {
                console.error('Error checking session:', error);
                // Show landing page on error
                document.getElementById('loadingOverlay').style.display = 'none';
                document.querySelector('.landing-container').style.visibility = 'visible';
                document.querySelector('.landing-container').style.opacity = '1';
            });
        });
    </script>
</body>

</html>
