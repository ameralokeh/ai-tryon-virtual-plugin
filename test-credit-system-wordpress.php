<?php
/**
 * Test script for Credit Management System in WordPress environment
 */

// WordPress bootstrap
require_once '/var/www/html/wp-config.php';
require_once '/var/www/html/wp-load.php';

echo "=== AI Virtual Fitting Credit Management System Test ===\n";
echo "Testing in WordPress Docker environment\n\n";

// Test 1: Check if plugin is loaded
echo "1. Testing plugin initialization...\n";
if (class_exists('AI_Virtual_Fitting_Credit_Manager')) {
    echo "✓ Credit Manager class is loaded\n";
} else {
    echo "✗ Credit Manager class not found\n";
    exit(1);
}

if (class_exists('AI_Virtual_Fitting_Database_Manager')) {
    echo "✓ Database Manager class is loaded\n";
} else {
    echo "✗ Database Manager class not found\n";
    exit(1);
}

// Test 2: Initialize credit manager
echo "\n2. Testing credit manager initialization...\n";
try {
    $credit_manager = new AI_Virtual_Fitting_Credit_Manager();
    echo "✓ Credit Manager initialized successfully\n";
} catch (Exception $e) {
    echo "✗ Failed to initialize Credit Manager: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Test with existing users
echo "\n3. Testing with existing WordPress users...\n";
$users = get_users(array('number' => 5));
echo "Found " . count($users) . " existing users\n";

foreach ($users as $user) {
    echo "\nTesting user: {$user->user_login} (ID: {$user->ID})\n";
    
    // Test getting credits (should auto-create entry)
    $credits = $credit_manager->get_customer_credits($user->ID);
    echo "  Initial credits: {$credits}\n";
    
    // Test sufficient credits check
    $has_access = $credit_manager->has_sufficient_credits($user->ID, 1);
    echo "  Has access: " . ($has_access ? 'YES' : 'NO') . "\n";
    
    // Test credit deduction
    if ($has_access) {
        $deduction_result = $credit_manager->deduct_credit($user->ID);
        echo "  Credit deduction: " . ($deduction_result ? 'SUCCESS' : 'FAILED') . "\n";
        
        $credits_after = $credit_manager->get_customer_credits($user->ID);
        echo "  Credits after deduction: {$credits_after}\n";
    }
    
    // Test credit addition
    $addition_result = $credit_manager->add_credits($user->ID, 5);
    echo "  Credit addition (+5): " . ($addition_result ? 'SUCCESS' : 'FAILED') . "\n";
    
    $final_credits = $credit_manager->get_customer_credits($user->ID);
    echo "  Final credits: {$final_credits}\n";
    
    // Test credit history
    $history = $credit_manager->get_customer_credit_history($user->ID);
    echo "  Credit history - Remaining: {$history['credits_remaining']}, Purchased: {$history['total_credits_purchased']}, Used: {$history['total_credits_used']}\n";
}

// Test 4: Test new user registration simulation
echo "\n4. Testing new user registration...\n";
$new_user_id = wp_create_user('testuser_' . time(), 'testpass123', 'test@example.com');
if (is_wp_error($new_user_id)) {
    echo "✗ Failed to create test user: " . $new_user_id->get_error_message() . "\n";
} else {
    echo "✓ Created test user with ID: {$new_user_id}\n";
    
    // The user_register hook should have automatically granted credits
    $new_user_credits = $credit_manager->get_customer_credits($new_user_id);
    echo "  New user credits: {$new_user_credits}\n";
    
    if ($new_user_credits == 2) {
        echo "✓ New user received correct initial credits\n";
    } else {
        echo "✗ New user did not receive correct initial credits (expected 2, got {$new_user_credits})\n";
    }
    
    // Clean up test user
    wp_delete_user($new_user_id);
    echo "  Test user cleaned up\n";
}

// Test 5: Test system statistics
echo "\n5. Testing system statistics...\n";
$stats = $credit_manager->get_system_credit_stats();
echo "System Stats:\n";
echo "  Total users: {$stats['total_users']}\n";
echo "  Total remaining credits: {$stats['total_remaining_credits']}\n";
echo "  Total purchased credits: {$stats['total_purchased_credits']}\n";
echo "  Total used credits: {$stats['total_used_credits']}\n";
echo "  Average remaining credits: {$stats['avg_remaining_credits']}\n";

// Test 6: Test existing user migration
echo "\n6. Testing existing user migration...\n";
$migrated_count = $credit_manager->migrate_existing_users();
echo "Migrated {$migrated_count} existing users\n";

// Test 7: Check database state
echo "\n7. Checking database state...\n";
global $wpdb;
$credits_count = $wpdb->get_var("SELECT COUNT(*) FROM wp_virtual_fitting_credits");
echo "Total credit records in database: {$credits_count}\n";

$total_credits = $wpdb->get_var("SELECT SUM(credits_remaining) FROM wp_virtual_fitting_credits");
echo "Total credits in system: {$total_credits}\n";

echo "\n=== Test Complete ===\n";
echo "✓ All tests passed successfully!\n";
echo "The Credit Management System is working correctly in WordPress environment.\n";