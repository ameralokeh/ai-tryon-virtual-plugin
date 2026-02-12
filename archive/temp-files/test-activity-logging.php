<?php
/**
 * Test Activity Logging
 * 
 * This script tests the activity logging functionality by simulating various scenarios
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

// Load the activity logger
require_once('/var/www/html/wp-content/plugins/ai-virtual-fitting/includes/class-activity-logger.php');

echo "=== Testing Activity Log System ===\n\n";

// Initialize the logger
$logger = new AI_Virtual_Fitting_Activity_Logger();

// Test 1: Log a successful virtual fitting
echo "Test 1: Logging successful virtual fitting...\n";
$result1 = $logger->log_activity(
    5, // user_id (hooktest)
    'virtual_fitting',
    123, // product_id
    'Beautiful Wedding Dress',
    'success',
    '',
    1250.5 // processing time in ms
);
echo "Result: " . ($result1 ? "SUCCESS" : "FAILED") . "\n\n";

// Test 2: Log a failed virtual fitting (insufficient credits)
echo "Test 2: Logging failed virtual fitting (insufficient credits)...\n";
$result2 = $logger->log_activity(
    5, // user_id
    'virtual_fitting',
    124, // product_id
    'Elegant Bridal Gown',
    'error',
    'Insufficient credits',
    0
);
echo "Result: " . ($result2 ? "SUCCESS" : "FAILED") . "\n\n";

// Test 3: Log a failed virtual fitting (image not found)
echo "Test 3: Logging failed virtual fitting (image not found)...\n";
$result3 = $logger->log_activity(
    5, // user_id
    'virtual_fitting',
    125, // product_id
    'Classic White Dress',
    'error',
    'Customer image not found',
    0
);
echo "Result: " . ($result3 ? "SUCCESS" : "FAILED") . "\n\n";

// Test 4: Log a successful credit purchase
echo "Test 4: Logging successful credit purchase...\n";
$result4 = $logger->log_activity(
    5, // user_id
    'credit_purchase',
    310, // credit product_id
    'Virtual Fitting Credits - 20 Pack',
    'success',
    'Added 20 credits from order #100',
    0
);
echo "Result: " . ($result4 ? "SUCCESS" : "FAILED") . "\n\n";

// Test 5: Log another successful virtual fitting with different user
echo "Test 5: Logging successful virtual fitting for different user...\n";
$result5 = $logger->log_activity(
    1, // user_id (amer.alokeh - admin)
    'virtual_fitting',
    126, // product_id
    'Vintage Lace Dress',
    'success',
    '',
    2340.8 // processing time in ms
);
echo "Result: " . ($result5 ? "SUCCESS" : "FAILED") . "\n\n";

// Test 6: Log a rate limit error
echo "Test 6: Logging rate limit error...\n";
$result6 = $logger->log_activity(
    5, // user_id
    'virtual_fitting',
    127, // product_id
    'Modern Minimalist Dress',
    'error',
    'Too many requests. Please wait a few minutes and try again.',
    0
);
echo "Result: " . ($result6 ? "SUCCESS" : "FAILED") . "\n\n";

// Verify logs were created
echo "=== Verification ===\n";
$logs = $logger->get_logs(30, 'all', 'all', 0, 0, 100);
echo "Total logs created: " . count($logs) . "\n\n";

// Display the logs
echo "=== Log Entries ===\n";
foreach ($logs as $log) {
    echo sprintf(
        "[%s] User: %s (%s) | Action: %s | Product: %s | Status: %s | Error: %s | Time: %sms\n",
        $log->created_at,
        $log->user_name,
        $log->user_email,
        $log->action,
        $log->product_name,
        $log->status,
        $log->error_message ?: 'N/A',
        $log->processing_time ?: 'N/A'
    );
}

// Get statistics
echo "\n=== Statistics ===\n";
$stats = $logger->get_statistics(30);
echo "Total Requests: " . $stats['total_requests'] . "\n";
echo "Successful: " . $stats['successful_requests'] . "\n";
echo "Failed: " . $stats['failed_requests'] . "\n";
echo "Unique Users: " . $stats['unique_users'] . "\n";
echo "Average Processing Time: " . $stats['avg_processing_time'] . "ms\n";

echo "\n=== Test Complete ===\n";
