<?php
/**
 * Simple Download Functionality Test
 * Tests download functionality
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

// Load plugin classes
require_once('/var/www/html/wp-content/plugins/ai-virtual-fitting/includes/class-database-manager.php');
require_once('/var/www/html/wp-content/plugins/ai-virtual-fitting/includes/class-credit-manager.php');
require_once('/var/www/html/wp-content/plugins/ai-virtual-fitting/includes/class-image-processor.php');
require_once('/var/www/html/wp-content/plugins/ai-virtual-fitting/includes/class-woocommerce-integration.php');
require_once('/var/www/html/wp-content/plugins/ai-virtual-fitting/public/class-public-interface.php');

echo "=== Download Functionality Property Test ===\n";
echo "Feature: ai-virtual-fitting, Property 8: Download Functionality\n";
echo "Validates: Requirements 6.1, 6.2, 6.3, 6.5\n\n";

// Initialize components
$database_manager = new AI_Virtual_Fitting_Database_Manager();
$database_manager->create_tables();

$public_interface = new AI_Virtual_Fitting_Public_Interface();

// Create test user
$test_user_id = wp_create_user('test_download_' . time(), 'testpass', 'test_download_' . time() . '@example.com');

// Create test result file
$upload_dir = wp_upload_dir();
$results_dir = $upload_dir['basedir'] . '/ai-virtual-fitting/results/';
if (!file_exists($results_dir)) {
    wp_mkdir_p($results_dir);
}

$test_filename = 'test_result_' . time() . '.jpg';
$test_filepath = $results_dir . $test_filename;
file_put_contents($test_filepath, 'test image content for download');

// Test Case 1: Unauthenticated download attempt
echo "Test 1: Unauthenticated download attempt... ";
wp_set_current_user(0); // Unauthenticated

$_GET['nonce'] = wp_create_nonce('ai_virtual_fitting_nonce');
$_GET['result_file'] = $test_filename;

ob_start();
try {
    $public_interface->handle_image_download();
} catch (Exception $e) {
    // Expected
}
$output1 = ob_get_clean();

// Should require login
if (strpos(strtolower($output1), 'log in') !== false) {
    echo "PASS\n";
    $test1_pass = true;
} else {
    echo "FAIL (Expected login requirement, got: " . substr($output1, 0, 100) . ")\n";
    $test1_pass = false;
}

unset($_GET['nonce'], $_GET['result_file']);

// Test Case 2: Authenticated download with valid file
echo "Test 2: Authenticated download with valid file... ";
wp_set_current_user($test_user_id);

$_GET['nonce'] = wp_create_nonce('ai_virtual_fitting_nonce');
$_GET['result_file'] = $test_filename;

ob_start();
try {
    $public_interface->handle_image_download();
} catch (Exception $e) {
    // Expected
}
$output2 = ob_get_clean();

// Should not contain error messages
if (strpos($output2, 'File not found') === false && strpos($output2, 'Security check failed') === false) {
    echo "PASS\n";
    $test2_pass = true;
} else {
    echo "FAIL (Unexpected error, got: " . substr($output2, 0, 100) . ")\n";
    $test2_pass = false;
}

unset($_GET['nonce'], $_GET['result_file']);

// Test Case 3: Authenticated download with invalid file
echo "Test 3: Authenticated download with invalid file... ";

$_GET['nonce'] = wp_create_nonce('ai_virtual_fitting_nonce');
$_GET['result_file'] = 'nonexistent_file.jpg';

ob_start();
try {
    $public_interface->handle_image_download();
} catch (Exception $e) {
    // Expected
}
$output3 = ob_get_clean();

// Should contain "File not found"
if (strpos($output3, 'File not found') !== false) {
    echo "PASS\n";
    $test3_pass = true;
} else {
    echo "FAIL (Expected 'File not found', got: " . substr($output3, 0, 100) . ")\n";
    $test3_pass = false;
}

unset($_GET['nonce'], $_GET['result_file']);

// Test Case 4: Invalid nonce
echo "Test 4: Invalid nonce security check... ";

$_GET['nonce'] = 'invalid_nonce';
$_GET['result_file'] = $test_filename;

ob_start();
try {
    $public_interface->handle_image_download();
} catch (Exception $e) {
    // Expected
}
$output4 = ob_get_clean();

// Should contain security error
if (strpos($output4, 'Security check failed') !== false) {
    echo "PASS\n";
    $test4_pass = true;
} else {
    echo "FAIL (Expected security error, got: " . substr($output4, 0, 100) . ")\n";
    $test4_pass = false;
}

unset($_GET['nonce'], $_GET['result_file']);

// Clean up
wp_delete_user($test_user_id);
if (file_exists($test_filepath)) {
    unlink($test_filepath);
}

// Summary
echo "\n=== RESULTS ===\n";
$total_tests = 4;
$passed_tests = ($test1_pass ? 1 : 0) + ($test2_pass ? 1 : 0) + ($test3_pass ? 1 : 0) + ($test4_pass ? 1 : 0);

echo "Passed: {$passed_tests}/{$total_tests}\n";

if ($passed_tests === $total_tests) {
    echo "✓ Download Functionality Property Test PASSED\n";
    exit(0);
} else {
    echo "✗ Download Functionality Property Test FAILED\n";
    exit(1);
}