<?php
/**
 * Admin Authentication Check
 * Verifies that the current user is an admin before allowing access to admin pages
 */

// Include the functions file to get supabaseRequest()
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is authenticated and is an admin
 * @return array|false Returns user data if admin, false otherwise
 */
function checkAdminAuth() {
    // Check if we have a session with user data
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
        return false;
    }
    
    $userId = $_SESSION['user']['id'];
    
    // Query the profiles table to check if user is admin
    $response = supabaseRequest(
        "/rest/v1/profiles?user_id=eq." . urlencode($userId) . "&select=is_admin,username,full_name",
        'GET',
        null,
        $_SESSION['user']['token'] ?? null
    );
    
    if ($response['status'] === 200 && !empty($response['data'])) {
        $profile = $response['data'][0];
        
        // Check if user is admin
        if (isset($profile['is_admin']) && $profile['is_admin'] === true) {
            return $profile;
        }
    }
    
    return false;
}

// Perform the check
$adminProfile = checkAdminAuth();

if (!$adminProfile) {
    // Redirect to dashboard if not admin
    header('Location: ../pages/dashboard.php');
    exit();
}

// Store admin profile in session for easy access
$_SESSION['admin_profile'] = $adminProfile;
?>
