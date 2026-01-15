<?php
/**
 * Simple Authentication Flow Test
 * Tests individual cases without complex framework
 */

// Load test helper (handles WordPress and plugin loading dynamically)
require_once(dirname(__FILE__) . '/test-helper.php');

echo "=== Authentication Flow Property Test ===\n";
echo "Feature: ai-virtual-fitting, Property 1: Authentication Flow Integrity\n";
echo "Validates: Requirements 1.1, 1.2, 1.3, 1.5\n\n";

// Initialize components
$database_manager = new AI_Virtual_Fitting_Database_Manager();
$database_manager->create_tables();

$public_interface = new AI_Virtual_Fitting_Public_Interface();

// Test Case 1: Unauthenticated user check credits
echo "Test 1: Unauthenticated user checking credits... ";
wp_set_current_user(0); // Unauthenticated

$_POST['nonce'] = wp_create_nonce('ai_virtual_fitting_nonce');
$_POST['action'] = 'ai_virtual_fitting_check_credits';

ob_start();
try {
    $public_interface->handle_check_credits();
} catch (Exception $e) {
    // Expected - wp_die() or wp_send_json_* calls exit
}
$output1 = ob_get_clean();

// Should contain logged_in: false
if (strpos($output1, '"logged_in":false') !== false) {
    echo "PASS\n";
    $test1_pass = true;
} else {
    echo "FAIL (Expected logged_in:false, got: " . substr($output1, 0, 100) . ")\n";
    $test1_pass = false;
}

unset($_POST['nonce'], $_POST['action']);

// Test Case 2: Authenticated user check credits
echo "Test 2: Authenticated user checking credits... ";

// Create test user
$test_user_id = wp_create_user('test_auth_' . time(), 'testpass', 'test_auth_' . time() . '@example.com');
wp_set_current_user($test_user_id);

$_POST['nonce'] = wp_create_nonce('ai_virtual_fitting_nonce');
$_POST['action'] = 'ai_virtual_fitting_check_credits';

ob_start();
try {
    $public_interface->handle_check_credits();
} catch (Exception $e) {
    // Expected
}
$output2 = ob_get_clean();

// Should contain logged_in: true
if (strpos($output2, '"logged_in":true') !== false) {
    echo "PASS\n";
    $test2_pass = true;
} else {
    echo "FAIL (Expected logged_in:true, got: " . substr($output2, 0, 100) . ")\n";
    $test2_pass = false;
}

unset($_POST['nonce'], $_POST['action']);

// Test Case 3: Unauthenticated upload attempt
echo "Test 3: Unauthenticated user upload attempt... ";
wp_set_current_user(0); // Unauthenticated

$_POST['nonce'] = wp_create_nonce('ai_virtual_fitting_nonce');
$_POST['action'] = 'ai_virtual_fitting_upload';
$_FILES['customer_image'] = array(
    'name' => 'test.jpg',
    'type' => 'image/jpeg',
    'tmp_name' => '/tmp/test.jpg',
    'error' => UPLOAD_ERR_OK,
    'size' => 1024
);

ob_start();
try {
    $public_interface->handle_image_upload();
} catch (Exception $e) {
    // Expected
}
$output3 = ob_get_clean();

// Should require login
if (strpos(strtolower($output3), 'log in') !== false) {
    echo "PASS\n";
    $test3_pass = true;
} else {
    echo "FAIL (Expected login message, got: " . substr($output3, 0, 100) . ")\n";
    $test3_pass = false;
}

unset($_POST['nonce'], $_POST['action']);
unset($_FILES['customer_image']);

// Clean up
wp_delete_user($test_user_id);

// Summary
echo "\n=== RESULTS ===\n";
$total_tests = 3;
$passed_tests = ($test1_pass ? 1 : 0) + ($test2_pass ? 1 : 0) + ($test3_pass ? 1 : 0);

echo "Passed: {$passed_tests}/{$total_tests}\n";

if ($passed_tests === $total_tests) {
    echo "✓ Authentication Flow Property Test PASSED\n";
    exit(0);
} else {
    echo "✗ Authentication Flow Property Test FAILED\n";
    exit(1);
}