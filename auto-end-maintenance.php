<?php
/**
 * Auto-End Maintenance Mode Script
 * This script should be run periodically (via cron job) to automatically end maintenance mode
 * when the estimated time has passed
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Auto-end maintenance mode when time has passed
 */
function autoEndMaintenanceMode() {
    try {
        // Get all active maintenance records
        $response = supabaseRequest(
            "/rest/v1/maintenance_mode?is_active=eq.true&select=*",
            'GET',
            null,
            null
        );
        
        if ($response['status'] !== 200 || empty($response['data'])) {
            return ['success' => true, 'message' => 'No active maintenance found'];
        }
        
        $now = new DateTime();
        $endedCount = 0;
        
        foreach ($response['data'] as $maintenance) {
            $startTime = new DateTime($maintenance['start_time']);
            $durationMinutes = $maintenance['duration_minutes'];
            $endTime = clone $startTime;
            $endTime->add(new DateInterval('PT' . $durationMinutes . 'M'));
            
            // If current time is past the end time, deactivate maintenance
            if ($now > $endTime) {
                $updateResponse = supabaseRequest(
                    "/rest/v1/maintenance_mode?id=eq.{$maintenance['id']}",
                    'PATCH',
                    [
                        'is_active' => false,
                        'updated_at' => $now->format('c')
                    ],
                    null
                );
                
                if ($updateResponse['status'] === 200) {
                    $endedCount++;
                    
                    // Log the auto-end event
                    logMaintenanceEvent($maintenance['id'], 'auto_ended', 'Maintenance mode automatically ended after estimated duration');
                }
            }
        }
        
        return [
            'success' => true, 
            'message' => "Auto-ended {$endedCount} maintenance record(s)",
            'ended_count' => $endedCount
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error auto-ending maintenance: ' . $e->getMessage()
        ];
    }
}

/**
 * Log maintenance events
 */
function logMaintenanceEvent($maintenanceId, $eventType, $description) {
    try {
        $logData = [
            'maintenance_id' => $maintenanceId,
            'event_type' => $eventType,
            'description' => $description,
            'created_at' => date('c')
        ];
        
        // Try to insert into maintenance_logs table (create if doesn't exist)
        $response = supabaseRequest(
            "/rest/v1/maintenance_logs",
            'POST',
            $logData,
            null
        );
        
        // If table doesn't exist, that's okay - we'll just skip logging
        return $response['status'] === 201;
        
    } catch (Exception $e) {
        // Logging failed, but that's not critical
        return false;
    }
}

// Run the auto-end function
$result = autoEndMaintenanceMode();

// If called from command line, output result
if (php_sapi_name() === 'cli') {
    echo json_encode($result) . "\n";
} else {
    // If called from web, return JSON
    header('Content-Type: application/json');
    echo json_encode($result);
}
?>
