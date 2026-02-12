<?php
/**
 * Fix existing invalid timestamps in activity log
 * This will set all 0000-00-00 timestamps to current time
 * Upload to production and run once
 */

// WordPress bootstrap
require_once('../../../wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    die('Access denied. Admin privileges required.');
}

header('Content-Type: text/plain');

echo "Fixing Invalid Activity Log Timestamps\n";
echo str_repeat("=", 70) . "\n\n";

global $wpdb;
$table_name = $wpdb->prefix . 'virtual_fitting_activity_log';

// Count invalid timestamps
$invalid_count = $wpdb->get_var("
    SELECT COUNT(*) 
    FROM $table_name 
    WHERE created_at = '0000-00-00 00:00:00' OR created_at IS NULL OR created_at = ''
");

echo "Found $invalid_count records with invalid timestamps\n\n";

if ($invalid_count == 0) {
    echo "No invalid timestamps to fix!\n";
    exit;
}

// Fix timestamps - set to current time
// Note: We can't recover the original timestamps, so we use current time
$current_time = gmdate('Y-m-d H:i:s');

$result = $wpdb->query($wpdb->prepare("
    UPDATE $table_name 
    SET created_at = %s 
    WHERE created_at = '0000-00-00 00:00:00' OR created_at IS NULL OR created_at = ''
", $current_time));

if ($result === false) {
    echo "ERROR: Failed to update timestamps\n";
    echo "MySQL Error: " . $wpdb->last_error . "\n";
} else {
    echo "SUCCESS: Updated $result records\n";
    echo "All invalid timestamps set to: $current_time (GMT)\n\n";
    
    // Verify fix
    $remaining_invalid = $wpdb->get_var("
        SELECT COUNT(*) 
        FROM $table_name 
        WHERE created_at = '0000-00-00 00:00:00' OR created_at IS NULL OR created_at = ''
    ");
    
    echo "Remaining invalid timestamps: $remaining_invalid\n";
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "Fix complete!\n\n";
echo "IMPORTANT: Delete this file after running for security:\n";
echo "  rm fix-existing-timestamps.php\n";
?>
