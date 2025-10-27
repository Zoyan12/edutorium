<?php
/**
 * Maintenance Mode Page
 * Displayed to users when maintenance mode is active
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if maintenance mode is active
 * @return bool True if maintenance mode is active
 */
function isMaintenanceModeActive() {
    try {
        $response = supabaseRequest(
            "/rest/v1/maintenance_mode?is_active=eq.true&select=id",
            'GET',
            null,
            null
        );
        
        return $response['status'] === 200 && !empty($response['data']);
    } catch (Exception $e) {
        // If we can't check, assume maintenance mode is not active
        return false;
    }
}

// Check if this is a preview request
$isPreview = isset($_GET['preview']) && $_GET['preview'] === 'true';

// If not preview, check if maintenance mode is actually active
if (!$isPreview) {
    $maintenanceActive = isMaintenanceModeActive();
    if (!$maintenanceActive) {
        // Determine correct redirect path based on the page user was on
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $redirectTo = 'index.php'; // Default to index.php
        
        // If there was a referer, try to go back to that page
        if (!empty($referer) && !str_contains($referer, 'maintenance.php')) {
            $redirectTo = $referer;
        } else {
            // Check if user is logged in and redirect to dashboard
            if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
                $redirectTo = '/pages/dashboard.php';
            }
        }
        
        // Redirect to appropriate page
        header('Location: ' . $redirectTo);
        exit();
    }
}

// Get maintenance data
$maintenanceData = getMaintenanceData($isPreview);

// Check if user is admin (for bypass functionality)
$isAdmin = false;
if (!$isPreview) {
    // Check session for admin profile
    if (isset($_SESSION['admin_profile']) && isset($_SESSION['admin_profile']['is_admin'])) {
        $isAdmin = $_SESSION['admin_profile']['is_admin'] === true;
    } else if (isset($_SESSION['user']) && isset($_SESSION['user']['is_admin'])) {
        // Check if user session has is_admin flag directly
        $isAdmin = $_SESSION['user']['is_admin'] === true;
    } else if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
        // Check session for user and verify admin status
        try {
            $response = supabaseRequest(
                "/rest/v1/profiles?user_id=eq." . urlencode($_SESSION['user']['id']) . "&select=is_admin",
                'GET',
                null,
                $_SESSION['user']['token'] ?? null
            );
            
            if ($response['status'] === 200 && !empty($response['data'])) {
                $isAdmin = $response['data'][0]['is_admin'] === true;
            }
        } catch (Exception $e) {
            // If we can't verify, assume not admin
        }
    }
}

// Handle admin bypass
if ($isAdmin && isset($_GET['bypass']) && $_GET['bypass'] === 'true') {
    // Set bypass cookie for 1 hour
    setcookie('maintenance_bypass', 'true', time() + 3600, '/');
    // Remove bypass parameter from URL
    $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $redirectUrl);
    exit();
}

// Check bypass cookie
$hasBypass = isset($_COOKIE['maintenance_bypass']) && $_COOKIE['maintenance_bypass'] === 'true';

// If admin has bypass, redirect to dashboard
if ($isAdmin && $hasBypass) {
    header('Location: pages/dashboard.php');
    exit();
}

/**
 * Check if maintenance mode is active
 */
function checkMaintenanceMode() {
    try {
        $response = supabaseRequest(
            "/rest/v1/maintenance_mode?is_active=eq.true&select=*",
            'GET',
            null,
            null
        );
        
        return $response['status'] === 200 && !empty($response['data']);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get maintenance mode data
 */
function getMaintenanceData($isPreview = false) {
    if ($isPreview) {
        // Preview mode - use GET parameters
        return [
            'user_message' => $_GET['message'] ?? 'We are currently performing scheduled maintenance. Please check back later.',
            'expected_resolution' => $_GET['resolution'] ?? 'Soon',
            'reason' => $_GET['reason'] ?? 'Scheduled maintenance',
            'start_time' => date('c'),
            'duration_minutes' => 60
        ];
    }
    
    try {
        $response = supabaseRequest(
            "/rest/v1/maintenance_mode?is_active=eq.true&select=*&order=start_time.desc&limit=1",
            'GET',
            null,
            null
        );
        
        if ($response['status'] === 200 && !empty($response['data'])) {
            return $response['data'][0];
        }
    } catch (Exception $e) {
        // Fallback data
    }
    
    // Default fallback data
    return [
        'user_message' => 'We are currently performing scheduled maintenance. Please check back later.',
        'expected_resolution' => 'Soon',
        'reason' => 'Scheduled maintenance',
        'start_time' => date('c'),
        'duration_minutes' => 60
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }

        .maintenance-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 90%;
            margin: 20px;
            position: relative;
            overflow: hidden;
        }

        .maintenance-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4);
        }

        .maintenance-icon {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .maintenance-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .maintenance-message {
            font-size: 1.2rem;
            color: #555;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .maintenance-details {
            background: rgba(102, 126, 234, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
        }

        .detail-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 10px 0;
        }

        .detail-item:last-child {
            margin-bottom: 0;
        }

        .detail-label {
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
        }

        .detail-label i {
            margin-right: 8px;
            color: #667eea;
        }

        .detail-value {
            color: #555;
            font-weight: 500;
        }

        .countdown-container {
            background: rgba(255, 107, 107, 0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            border: 2px solid rgba(255, 107, 107, 0.2);
        }

        .countdown-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #e74c3c;
            margin-bottom: 15px;
        }

        .countdown-timer {
            font-size: 2rem;
            font-weight: 700;
            color: #e74c3c;
            font-family: 'Courier New', monospace;
        }

        .refresh-button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin-right: 15px;
        }

        .refresh-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .admin-bypass {
            background: rgba(52, 152, 219, 0.1);
            border: 2px solid rgba(52, 152, 219, 0.3);
            border-radius: 15px;
            padding: 20px;
            margin-top: 30px;
        }

        .admin-bypass-title {
            font-size: 1rem;
            font-weight: 600;
            color: #3498db;
            margin-bottom: 10px;
        }

        .admin-bypass-button {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .admin-bypass-button:hover {
            background: #2980b9;
            transform: translateY(-1px);
        }

        .footer-info {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            color: #777;
            font-size: 0.9rem;
        }

        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-right: 10px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .maintenance-container {
                padding: 40px 20px;
                margin: 10px;
            }

            .maintenance-title {
                font-size: 2rem;
            }

            .maintenance-message {
                font-size: 1.1rem;
            }

            .detail-item {
                flex-direction: column;
                align-items: flex-start;
                text-align: left;
            }

            .detail-value {
                margin-top: 5px;
            }

            .refresh-button {
                display: block;
                margin: 10px 0;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-icon">
            <i class="fas fa-tools"></i>
        </div>
        
        <h1 class="maintenance-title">Under Maintenance</h1>
        
        <p class="maintenance-message">
            <?php echo htmlspecialchars($maintenanceData['user_message']); ?>
        </p>
        
        <div class="maintenance-details">
            <div class="detail-item">
                <span class="detail-label">
                    <i class="fas fa-info-circle"></i>
                    Reason
                </span>
                <span class="detail-value"><?php echo htmlspecialchars($maintenanceData['reason']); ?></span>
            </div>
            
            <div class="detail-item">
                <span class="detail-label">
                    <i class="fas fa-clock"></i>
                    Expected Resolution
                </span>
                <span class="detail-value"><?php echo htmlspecialchars($maintenanceData['expected_resolution']); ?></span>
            </div>
            
            <div class="detail-item">
                <span class="detail-label">
                    <i class="fas fa-calendar"></i>
                Started
                </span>
                <span class="detail-value"><?php echo date('M j, Y \a\t g:i A', strtotime($maintenanceData['start_time'])); ?></span>
            </div>
        </div>
        
        <?php if (!$isPreview): ?>
        <div class="countdown-container">
            <div class="countdown-title">
                <i class="fas fa-hourglass-half"></i>
                Estimated Time Remaining
            </div>
            <div class="countdown-timer" id="countdownTimer">
                <span class="loading-spinner"></span>
                Calculating...
            </div>
        </div>
        <?php endif; ?>
        
        <a href="javascript:void(0)" class="refresh-button" onclick="checkMaintenanceStatus()">
            <i class="fas fa-sync-alt"></i>
            Check Status
        </a>
        
        <a href="mailto:support@edutorium.com" class="refresh-button">
            <i class="fas fa-envelope"></i>
            Contact Support
        </a>
        
        <?php if ($isAdmin && !$hasBypass): ?>
        <div class="admin-bypass">
            <div class="admin-bypass-title">
                <i class="fas fa-user-shield"></i>
                Admin Access
            </div>
            <p style="margin-bottom: 15px; color: #555;">
                You have admin privileges. You can bypass maintenance mode to access the site.
            </p>
            <a href="?bypass=true" class="admin-bypass-button">
                <i class="fas fa-unlock"></i>
                Bypass Maintenance Mode
            </a>
        </div>
        <?php endif; ?>
        
        <div class="footer-info">
            <p>
                <i class="fas fa-heart" style="color: #e74c3c;"></i>
                Thank you for your patience. We're working hard to improve your experience.
            </p>
            <p style="margin-top: 10px;">
                <strong><?php echo APP_NAME; ?></strong> - Educational Battle Platform
            </p>
        </div>
    </div>

    <?php if (!$isPreview): ?>
    <script>
        // Countdown timer functionality
        function updateCountdown() {
            const startTime = new Date('<?php echo $maintenanceData['start_time']; ?>').getTime();
            const durationMinutes = <?php echo $maintenanceData['duration_minutes']; ?>;
            const endTime = startTime + (durationMinutes * 60 * 1000);
            const now = new Date().getTime();
            
            const timerElement = document.getElementById('countdownTimer');
            
            if (now >= endTime) {
                timerElement.innerHTML = '<i class="fas fa-check-circle" style="color: #27ae60;"></i> Maintenance should be complete';
                return;
            }
            
            const timeLeft = endTime - now;
            const hours = Math.floor(timeLeft / (1000 * 60 * 60));
            const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
            
            let timeString = '';
            if (hours > 0) {
                timeString += hours + 'h ';
            }
            if (minutes > 0 || hours > 0) {
                timeString += minutes + 'm ';
            }
            timeString += seconds + 's';
            
            timerElement.innerHTML = '<i class="fas fa-clock"></i> ' + timeString;
        }

        // Check maintenance status
        function checkMaintenanceStatus() {
            const button = event.target.closest('.refresh-button');
            const originalText = button.innerHTML;
            
            button.innerHTML = '<span class="loading-spinner"></span>Checking...';
            button.style.pointerEvents = 'none';
            
            // Simulate API call (in real implementation, this would check the database)
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }

        // Update countdown every second
        updateCountdown();
        setInterval(updateCountdown, 1000);

        // Initialize Supabase client for status checking
        let supabaseClient = null;
        if (window.supabase && typeof window.supabase.createClient === 'function') {
            try {
                supabaseClient = window.supabase.createClient(
                    'https://ratxqmbqzwbvfgsonlrd.supabase.co',
                    'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InJhdHhxbWJxendidmZnc29ubHJkIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDQyMDI0NDAsImV4cCI6MjA1OTc3ODQ0MH0.HJ9nQbvVvVisvQb6HMVMlmQBVmW7Ie42Z6Afdwn8W2M'
                );
                console.log('Supabase client initialized for status checking');
            } catch (e) {
                console.error('Could not initialize Supabase client:', e);
            }
        }

        // Periodic check if maintenance mode is still active AND auto-end if duration passed
        const checkInterval = setInterval(async () => {
            try {
                if (!supabaseClient) {
                    console.debug('Supabase client not available, skipping status check');
                    return;
                }
                
                // Query Supabase directly
                const { data, error } = await supabaseClient
                    .from('maintenance_mode')
                    .select('id, is_active, start_time, duration_minutes')
                    .eq('is_active', true)
                    .limit(1);
                
                if (error) {
                    console.debug('Error checking maintenance status:', error.message);
                    return;
                }
                
                // If we have active maintenance, check if duration has passed
                if (data && data.length > 0) {
                    const maintenance = data[0];
                    const startTime = new Date(maintenance.start_time);
                    const durationMinutes = maintenance.duration_minutes || 60;
                    const endTime = new Date(startTime.getTime() + (durationMinutes * 60 * 1000));
                    const now = new Date();
                    
                    // If maintenance time has passed, auto-end it
                    if (now >= endTime) {
                        console.log('Maintenance duration has passed. Auto-ending maintenance mode...');
                        
                        // Disable maintenance mode
                        const { error: updateError } = await supabaseClient
                            .from('maintenance_mode')
                            .update({ 
                                is_active: false,
                                updated_at: new Date().toISOString()
                            })
                            .eq('id', maintenance.id);
                        
                        if (!updateError) {
                            console.log('Maintenance mode auto-ended successfully');
                        } else {
                            console.error('Failed to auto-end maintenance mode:', updateError);
                        }
                    }
                } else {
                    // If no active maintenance mode records, maintenance is off
                    console.log('Maintenance mode is no longer active. Redirecting...');
                    clearInterval(checkInterval);
                    
                    // Determine the correct redirect URL
                    let redirectUrl;
                    
                    // First, check if we have a stored return URL
                    const storedUrl = sessionStorage.getItem('maintenance_return_url');
                    if (storedUrl && !storedUrl.includes('maintenance.php')) {
                        redirectUrl = storedUrl;
                    } else {
                        // Default to index.php (or index.html if PHP doesn't work)
                        redirectUrl = './index.php';
                        
                        // Check if user might be logged in - redirect to dashboard
                        try {
                            const checkAuth = await supabaseClient.auth.getSession();
                            if (checkAuth.data && checkAuth.data.session) {
                                redirectUrl = './pages/dashboard.php';
                            }
                        } catch (e) {
                            // If we can't check auth, just use default
                            console.debug('Could not check auth status:', e);
                        }
                    }
                    
                    console.log('Redirecting to:', redirectUrl);
                    window.location.href = redirectUrl;
                }
            } catch (error) {
                console.debug('Could not check maintenance status:', error);
            }
        }, 5000); // Check every 5 seconds
        
        // Auto-refresh page every 2 minutes as a backup (only if maintenance still active)
        setTimeout(() => {
            // Reload page as backup check
            window.location.reload();
        }, 2 * 60 * 1000);
    </script>
    <?php endif; ?>
</body>
</html>
