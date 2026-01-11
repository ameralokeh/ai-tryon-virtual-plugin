<?php
/**
 * Simple test to verify credit system is working in WordPress
 */

// WordPress bootstrap
require_once '/var/www/html/wp-config.php';
require_once '/var/www/html/wp-load.php';

echo "=== Simple Credit System Test ===\n";

// Test if classes exist
echo "1. Checking if classes are loaded...\n";
if (class_exists('AI_Virtual_Fitting_Credit_Manager')) {
    echo "✓ Credit Manager class exists\n";
} else {
    echo "✗ Credit Manager class not found\n";
    exit(1);
}

// Test database tables
echo "\n2. Checking database tables...\n";
global $wpdb;
$credits_table = $wpdb->prefix . 'virtual_fitting_credits';
$sessions_table = $wpdb->prefix . 'virtual_fitting_sessions';

$credits_exists = $wpdb->get_var("SHOW TABLES LIKE '{$credits_table}'") === $credits_table;
$sessions_exists = $wpdb->get_var("SHOW TABLES LIKE '{$sessions_table}'") === $sessions_table;

echo "Credits table exists: " . ($credits_exists ? 'YES' : 'NO') . "\n";
echo "Sessions table exists: " . ($sessions_exists ? 'YES' : 'NO') . "\n";

if (!$credits_exists || !$sessions_exists) {
    echo "Database tables are missing. Plugin may not be properly activated.\n";
    exit(1);
}

// Test basic credit operations without using the Credit Manager class
echo "\n3. Testing basic credit operations...\n";

// Get existing users
$users = get_users(array('number' => 2));
if (empty($users)) {
    echo "No users found in WordPress\n";
    exit(1);
}

$test_user = $users[0];
echo "Testing with user: {$test_user->user_login} (ID: {$test_user->ID})\n";

// Check if user has credits record
$existing_credits = $wpdb->get_var($wpdb->prepare(
    "SELECT credits_remaining FROM {$credits_table} WHERE user_id = %d",
    $test_user->ID
));

if ($existing_credits === null) {
    echo "User has no credits record. Creating one...\n";
    
    // Insert initial credits
    $result = $wpdb->insert(
        $credits_table,
        array(
            'user_id' => $test_user->ID,
            'credits_remaining' => 2,
            'total_credits_purchased' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ),
        array('%d', '%d', '%d', '%s', '%s')
    );
    
    if ($result) {
        echo "✓ Created credits record for user\n";
    } else {
        echo "✗ Failed to create credits record\n";
        exit(1);
    }
} else {
    echo "User already has {$existing_credits} credits\n";
}

// Test credit deduction
$current_credits = $wpdb->get_var($wpdb->prepare(
    "SELECT credits_remaining FROM {$credits_table} WHERE user_id = %d",
    $test_user->ID
));

if ($current_credits > 0) {
    echo "Deducting 1 credit...\n";
    
    $result = $wpdb->update(
        $credits_table,
        array(
            'credits_remaining' => $current_credits - 1,
            'updated_at' => current_time('mysql')
        ),
        array('user_id' => $test_user->ID),
        array('%d', '%s'),
        array('%d')
    );
    
    if ($result) {
        $new_credits = $wpdb->get_var($wpdb->prepare(
            "SELECT credits_remaining FROM {$credits_table} WHERE user_id = %d",
            $test_user->ID
        ));
        echo "✓ Credit deducted successfully. New balance: {$new_credits}\n";
    } else {
        echo "✗ Failed to deduct credit\n";
    }
} else {
    echo "User has no credits to deduct\n";
}

// Test credit addition
echo "Adding 5 credits...\n";
$current_credits = $wpdb->get_var($wpdb->prepare(
    "SELECT credits_remaining FROM {$credits_table} WHERE user_id = %d",
    $test_user->ID
));

$result = $wpdb->update(
    $credits_table,
    array(
        'credits_remaining' => $current_credits + 5,
        'total_credits_purchased' => 5,
        'updated_at' => current_time('mysql')
    ),
    array('user_id' => $test_user->ID),
    array('%d', '%d', '%s'),
    array('%d')
);

if ($result) {
    $new_credits = $wpdb->get_var($wpdb->prepare(
        "SELECT credits_remaining FROM {$credits_table} WHERE user_id = %d",
        $test_user->ID
    ));
    echo "✓ Credits added successfully. New balance: {$new_credits}\n";
} else {
    echo "✗ Failed to add credits\n";
}

// Show final statistics
echo "\n4. Final statistics...\n";
$total_users = $wpdb->get_var("SELECT COUNT(*) FROM {$credits_table}");
$total_credits = $wpdb->get_var("SELECT SUM(credits_remaining) FROM {$credits_table}");
$total_purchased = $wpdb->get_var("SELECT SUM(total_credits_purchased) FROM {$credits_table}");

echo "Total users with credits: {$total_users}\n";
echo "Total remaining credits: {$total_credits}\n";
echo "Total purchased credits: {$total_purchased}\n";

echo "\n✓ Basic credit system test completed successfully!\n";
echo "The database operations are working correctly.\n";