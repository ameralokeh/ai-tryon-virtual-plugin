<?php
/**
 * Fix timestamps - Direct approach
 * Checks actual table name and fixes timestamps
 */

// WordPress bootstrap
require_once('../../../wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    die('Access denied. Admin privileges required.');
}

header('Content-Type: text/plain');

echo "Direct Timestamp Fix\n";
echo str_repeat("=", 70) . "\n\n";

global $wpdb;

// Find the actual table name
$table_name = $wpdb->prefix . 'virtual_fitting_activity_log';
echo "Looking for table: $table_name\n";

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
if (!$table_exists) {
    echo "ERROR: Table does not exist!\n";
    echo "Checking for alternative table names...\n";
    
    // Try to find the table
    $tables = $wpdb->get_results("SHOW TABLES LIKE '%virtual_fitting_activity_log%'", ARRAY_N);
    if (!empty($tables)) {
        echo "Found tables:\n";
        foreach ($tables as $table) {
            echo "  - {$table[0]}\n";
        }
        $table_name = $tables[0][0];
        echo "\nUsing table: $table_name\n";
    } else {
        echo "No activity log table found!\n";
        exit;
    }
}

echo "\n";

// Show current state
echo "Current timestamp values:\n";
$samples = $wpdb->get_results("SELECT id, user_name, created_at FROM $table_name ORDER BY id DESC LIMIT 5");
foreach ($samples as $row) {
    echo "  ID {$row->id}: '{$row->created_at}' ({$row->user_name})\n";
}

echo "\n";

// Count invalid timestamps using direct comparison
$invalid_count = $wpdb->get_var("
    SELECT COUNT(*) 
    FROM $table_name 
    WHERE created_at = '0000-00-00 00:00:00'
");

echo "Records with '0000-00-00 00:00:00': $invalid_count\n\n";

if ($invalid_count == 0) {
    echo "No timestamps to fix!\n";
    echo "\nPossible reasons:\n";
    echo "1. Timestamps are already fixed\n";
    echo "2. Table structure is different\n";
    echo "3. Data type issue\n";
    exit;
}

// Fix the timestamps
$current_time = gmdate('Y-m-d H:i:s');
echo "Setting invalid timestamps to: $current_time (GMT)\n\n";

$result = $wpdb->query("
    UPDATE $table_name 
    SET created_at = '$current_time' 
    WHERE created_at = '0000-00-00 00:00:00'
");

if ($result === false) {
    echo "ERROR: Update failed\n";
    echo "MySQL Error: " . $wpdb->last_error . "\n";
} else {
    echo "SUCCESS: Updated $result records\n\n";
    
    // Show updated state
    echo "Updated timestamp values:\n";
    $samples = $wpdb->get_results("SELECT id, user_name, created_at FROM $table_name ORDER BY id DESC LIMIT 5");
    foreach ($samples as $row) {
        echo "  ID {$row->id}: '{$row->created_at}' ({$row->user_name})\n";
    }
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "Complete! Delete this file for security.\n";
?>
