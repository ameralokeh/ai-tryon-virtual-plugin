<?php
/**
 * Fix Invalid Timestamps in Activity Log
 * Run this once to fix existing entries with 0000-00-00 00:00:00 timestamps
 */

// Load WordPress
require_once('/home/customer/www/bridesandtailor.com/public_html/wp-load.php');

global $wpdb;
$table_name = $wpdb->prefix . 'virtual_fitting_activity_log';

echo "=== Fix Invalid Timestamps ===\n\n";

// Find entries with invalid timestamps
$invalid_entries = $wpdb->get_results("
    SELECT id, user_email, action, status, created_at 
    FROM {$table_name} 
    WHERE created_at = '0000-00-00 00:00:00' 
    OR created_at IS NULL
    ORDER BY id DESC
");

if (empty($invalid_entries)) {
    echo "✓ No invalid timestamps found\n";
    exit(0);
}

echo "Found " . count($invalid_entries) . " entries with invalid timestamps:\n\n";

foreach ($invalid_entries as $entry) {
    echo "- ID: {$entry->id} | {$entry->user_email} | {$entry->action} | {$entry->status}\n";
}

echo "\nFixing timestamps...\n\n";

// Update each entry with current timestamp
// We'll set them to "now" since we don't know the original time
$fixed_count = 0;
$current_time = gmdate('Y-m-d H:i:s');

foreach ($invalid_entries as $entry) {
    $result = $wpdb->update(
        $table_name,
        array('created_at' => $current_time),
        array('id' => $entry->id),
        array('%s'),
        array('%d')
    );
    
    if ($result !== false) {
        $fixed_count++;
        echo "✓ Fixed ID: {$entry->id}\n";
    } else {
        echo "✗ Failed to fix ID: {$entry->id}\n";
    }
}

echo "\n=== Summary ===\n";
echo "Total entries fixed: {$fixed_count} / " . count($invalid_entries) . "\n";
echo "\nNow check the Activity Log in WordPress admin!\n";
