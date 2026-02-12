<?php
/**
 * Diagnostic Script for Activity Log Issue
 * 
 * This script checks:
 * 1. If the activity log table exists in production
 * 2. Table structure and columns
 * 3. Recent log entries
 * 4. If the activity logger class is loaded
 * 5. Test logging functionality
 */

// Load WordPress
require_once('/home/customer/www/bridesandtailor.com/public_html/wp-load.php');

echo "=== AI Virtual Fitting Activity Log Diagnostic ===\n\n";

// Check if activity logger class exists
echo "1. Checking Activity Logger Class...\n";
if (class_exists('AI_Virtual_Fitting_Activity_Logger')) {
    echo "   ✓ AI_Virtual_Fitting_Activity_Logger class is loaded\n\n";
} else {
    echo "   ✗ AI_Virtual_Fitting_Activity_Logger class NOT found\n";
    echo "   Attempting to load manually...\n";
    $plugin_path = '/home/customer/www/bridesandtailor.com/public_html/wp-content/plugins/ai-virtual-fitting/';
    if (file_exists($plugin_path . 'includes/class-activity-logger.php')) {
        require_once($plugin_path . 'includes/class-activity-logger.php');
        echo "   ✓ Class loaded manually\n\n";
    } else {
        echo "   ✗ Class file not found\n\n";
        exit(1);
    }
}

// Check database table
global $wpdb;
$table_name = $wpdb->prefix . 'virtual_fitting_activity_log';

echo "2. Checking Database Table...\n";
echo "   Table name: {$table_name}\n";

$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;

if ($table_exists) {
    echo "   ✓ Table exists\n\n";
    
    // Check table structure
    echo "3. Checking Table Structure...\n";
    $columns = $wpdb->get_results("DESCRIBE {$table_name}");
    foreach ($columns as $column) {
        echo "   - {$column->Field} ({$column->Type})\n";
    }
    echo "\n";
    
    // Check row count
    echo "4. Checking Row Count...\n";
    $total_rows = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
    echo "   Total rows: {$total_rows}\n\n";
    
    // Check recent entries
    echo "5. Checking Recent Entries (Last 10)...\n";
    $recent_logs = $wpdb->get_results("
        SELECT id, user_id, user_email, action, status, created_at 
        FROM {$table_name} 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    
    if (empty($recent_logs)) {
        echo "   ⚠ No log entries found\n\n";
    } else {
        foreach ($recent_logs as $log) {
            echo "   - ID: {$log->id} | User: {$log->user_email} | Action: {$log->action} | Status: {$log->status} | Date: {$log->created_at}\n";
        }
        echo "\n";
    }
    
    // Check successful entries specifically
    echo "6. Checking Successful Virtual Fitting Entries...\n";
    $success_count = $wpdb->get_var("
        SELECT COUNT(*) 
        FROM {$table_name} 
        WHERE action = 'virtual_fitting' AND status = 'success'
    ");
    echo "   Successful virtual fitting logs: {$success_count}\n\n";
    
    // Check error entries
    echo "7. Checking Error Entries...\n";
    $error_count = $wpdb->get_var("
        SELECT COUNT(*) 
        FROM {$table_name} 
        WHERE status = 'error'
    ");
    echo "   Error logs: {$error_count}\n\n";
    
    // Check entries from last 24 hours
    echo "8. Checking Entries from Last 24 Hours...\n";
    $recent_count = $wpdb->get_var("
        SELECT COUNT(*) 
        FROM {$table_name} 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    echo "   Entries in last 24 hours: {$recent_count}\n\n";
    
    // Test logging functionality
    echo "9. Testing Log Functionality...\n";
    $logger = new AI_Virtual_Fitting_Activity_Logger();
    
    // Get a test user
    $test_user = get_users(array('number' => 1));
    if (!empty($test_user)) {
        $test_user_id = $test_user[0]->ID;
        
        echo "   Attempting to create test log entry...\n";
        $log_id = $logger->log_activity(
            $test_user_id,
            'virtual_fitting',
            123, // test product ID
            'Test Product',
            'success',
            '',
            1234.56
        );
        
        if ($log_id) {
            echo "   ✓ Test log created successfully (ID: {$log_id})\n";
            
            // Verify it was inserted
            $test_log = $wpdb->get_row("SELECT * FROM {$table_name} WHERE id = {$log_id}");
            if ($test_log) {
                echo "   ✓ Test log verified in database\n";
                echo "   - User: {$test_log->user_email}\n";
                echo "   - Action: {$test_log->action}\n";
                echo "   - Status: {$test_log->status}\n";
                echo "   - Processing Time: {$test_log->processing_time}ms\n";
                
                // Clean up test entry
                $wpdb->delete($table_name, array('id' => $log_id));
                echo "   ✓ Test log cleaned up\n\n";
            } else {
                echo "   ✗ Test log NOT found in database after insert\n\n";
            }
        } else {
            echo "   ✗ Failed to create test log\n";
            echo "   Last database error: " . $wpdb->last_error . "\n\n";
        }
    } else {
        echo "   ⚠ No users found for testing\n\n";
    }
    
} else {
    echo "   ✗ Table does NOT exist\n";
    echo "   Expected table: {$table_name}\n\n";
    
    echo "3. Checking Available Tables...\n";
    $all_tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}virtual_fitting%'");
    if (empty($all_tables)) {
        echo "   ⚠ No virtual fitting tables found\n\n";
    } else {
        echo "   Found tables:\n";
        foreach ($all_tables as $table) {
            $table_name_col = "Tables_in_" . DB_NAME . "_({$wpdb->prefix}virtual_fitting%)";
            echo "   - " . $table->$table_name_col . "\n";
        }
        echo "\n";
    }
    
    echo "4. Recommendation:\n";
    echo "   The activity log table needs to be created.\n";
    echo "   This should happen automatically on plugin activation.\n";
    echo "   Try deactivating and reactivating the plugin.\n\n";
}

// Check if Database Manager exists
echo "10. Checking Database Manager...\n";
if (class_exists('AI_Virtual_Fitting_Database_Manager')) {
    echo "   ✓ Database Manager class is loaded\n";
    $db_manager = new AI_Virtual_Fitting_Database_Manager();
    $tables_exist = $db_manager->verify_tables_exist();
    echo "   Tables verification: " . ($tables_exist ? "✓ All tables exist" : "✗ Some tables missing") . "\n\n";
} else {
    echo "   ⚠ Database Manager class not found\n\n";
}

echo "=== Diagnostic Complete ===\n";
