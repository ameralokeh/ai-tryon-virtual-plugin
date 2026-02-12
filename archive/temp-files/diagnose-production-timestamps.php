<?php
/**
 * Diagnose Activity Log Timestamp Issues
 * Upload this to production and access via browser
 */

// WordPress bootstrap
require_once('../../../wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    die('Access denied. Admin privileges required.');
}

header('Content-Type: text/plain');

echo "Activity Log Timestamp Diagnostic\n";
echo str_repeat("=", 70) . "\n\n";

global $wpdb;
$table_name = $wpdb->prefix . 'virtual_fitting_activity_log';

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

if (!$table_exists) {
    echo "ERROR: Table '$table_name' does not exist!\n";
    exit;
}

echo "Table: $table_name\n";
echo "WordPress Timezone: " . wp_timezone_string() . "\n";
echo "Date Format: " . get_option('date_format') . "\n";
echo "Time Format: " . get_option('time_format') . "\n\n";

// Get recent logs
$logs = $wpdb->get_results("
    SELECT id, user_id, user_name, action, status, created_at
    FROM $table_name
    ORDER BY id DESC
    LIMIT 10
");

if (empty($logs)) {
    echo "No activity logs found in database.\n";
    exit;
}

echo "Recent Activity Logs (Last 10):\n";
echo str_repeat("-", 70) . "\n\n";

foreach ($logs as $log) {
    echo "ID: {$log->id}\n";
    echo "User: {$log->user_name} (ID: {$log->user_id})\n";
    echo "Action: {$log->action}\n";
    echo "Status: {$log->status}\n";
    echo "Raw created_at: '{$log->created_at}'\n";
    
    // Test timestamp conversion
    if (!empty($log->created_at) && $log->created_at !== '0000-00-00 00:00:00') {
        $format = get_option('date_format') . ' ' . get_option('time_format');
        $converted = mysql2date($format, $log->created_at);
        echo "Converted: $converted\n";
        
        // Also show as Unix timestamp
        $timestamp = strtotime($log->created_at . ' UTC');
        echo "Unix timestamp: $timestamp (" . date('Y-m-d H:i:s', $timestamp) . ")\n";
    } else {
        echo "Converted: INVALID (empty or 0000-00-00)\n";
    }
    
    echo "\n";
}

echo str_repeat("=", 70) . "\n";
echo "Diagnostic complete!\n\n";

// Check for common issues
echo "Common Issues Check:\n";
echo str_repeat("-", 70) . "\n";

$invalid_count = $wpdb->get_var("
    SELECT COUNT(*) 
    FROM $table_name 
    WHERE created_at = '0000-00-00 00:00:00' OR created_at IS NULL OR created_at = ''
");

echo "Records with invalid timestamps: $invalid_count\n";

$valid_count = $wpdb->get_var("
    SELECT COUNT(*) 
    FROM $table_name 
    WHERE created_at != '0000-00-00 00:00:00' AND created_at IS NOT NULL AND created_at != ''
");

echo "Records with valid timestamps: $valid_count\n";

if ($invalid_count > 0) {
    echo "\nWARNING: Found $invalid_count records with invalid timestamps!\n";
    echo "This is why you're seeing dashes in the Activity Log.\n";
}
?>
