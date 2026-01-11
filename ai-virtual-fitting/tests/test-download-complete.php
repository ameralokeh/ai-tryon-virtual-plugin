<?php
/**
 * Complete Download Functionality Test
 * Tests all download scenarios individually to avoid wp_die() exit issues
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

// Load plugin classes
require_once('/var/www/html/wp-content/plugins/ai-virtual-fitting/includes/class-database-manager.php');
require_once('/var/www/html/wp-content/plugins/ai-virtual-fitting/includes/class-credit-manager.php');
require_once('/var/www/html/wp-content/plugins/ai-virtual-fitting/includes/class-image-processor.php');
require_once('/var/www/html/wp-content/plugins/ai-virtual-fitting/includes/class-woocommerce-integration.php');
require_once('/var/www/html/wp-content/plugins/ai-virtual-fitting/public/class-public-interface.php');

echo "=== Complete Download Functionality Property Test ===\n";
echo "Feature: ai-virtual-fitting, Property 8: Download Functionality\n";
echo "Validates: Requirements 6.1, 6.2, 6.3, 6.5\n\n";

// Initialize components
$database_manager = new AI_Virtual_Fitting_Database_Manager();
$database_manager->create_tables();

// Create test user
$test_user_id = wp_create_user('test_download_complete_' . time(), 'testpass', 'test_download_complete_' . time() . '@example.com');

// Create test result file
$upload_dir = wp_upload_dir();
$results_dir = $upload_dir['basedir'] . '/ai-virtual-fitting/results/';
if (!file_exists($results_dir)) {
    wp_mkdir_p($results_dir);
}

$test_filename = 'test_result_complete_' . time() . '.jpg';
$test_filepath = $results_dir . $test_filename;
file_put_contents($test_filepath, 'test image content for download testing');

echo "Test setup completed. Test file created: {$test_filename}\n\n";

// Test results
$test_results = array();

// Test 1: Check that unauthenticated users get login requirement
echo "Test 1: Unauthenticated download authentication check...\n";
wp_set_current_user(0); // Unauthenticated

// Create a reflection to test the method directly
$public_interface = new AI_Virtual_Fitting_Public_Interface();
$reflection = new ReflectionClass($public_interface);

// Test authentication check by examining the method behavior
$_GET['nonce'] = wp_create_nonce('ai_virtual_fitting_nonce');
$_GET['result_file'] = $test_filename;

ob_start();
try {
    $public_interface->handle_image_download();
} catch (Exception $e) {
    // Expected
}
$output1 = ob_get_clean();

if (strpos(strtolower($output1), 'log in') !== false) {
    echo "✓ PASS - Unauthenticated users properly required to log in\n";
    $test_results['auth_required'] = true;
} else {
    echo "✗ FAIL - Expected login requirement for unauthenticated users\n";
    $test_results['auth_required'] = false;
}

unset($_GET['nonce'], $_GET['result_file']);

// Test 2: Check file existence validation
echo "Test 2: File existence validation...\n";
wp_set_current_user($test_user_id);

$_GET['nonce'] = wp_create_nonce('ai_virtual_fitting_nonce');
$_GET['result_file'] = 'nonexistent_file_' . time() . '.jpg';

ob_start();
try {
    $public_interface->handle_image_download();
} catch (Exception $e) {
    // Expected
}
$output2 = ob_get_clean();

if (strpos($output2, 'File not found') !== false) {
    echo "✓ PASS - Nonexistent files properly rejected\n";
    $test_results['file_validation'] = true;
} else {
    echo "✗ FAIL - Expected 'File not found' for nonexistent file\n";
    echo "Got: " . substr($output2, 0, 200) . "\n";
    $test_results['file_validation'] = false;
}

unset($_GET['nonce'], $_GET['result_file']);

// Test 3: Check nonce security
echo "Test 3: Nonce security validation...\n";

$_GET['nonce'] = 'invalid_nonce_' . time();
$_GET['result_file'] = $test_filename;

ob_start();
try {
    $public_interface->handle_image_download();
} catch (Exception $e) {
    // Expected
}
$output3 = ob_get_clean();

if (strpos($output3, 'Security check failed') !== false) {
    echo "✓ PASS - Invalid nonce properly rejected\n";
    $test_results['nonce_security'] = true;
} else {
    echo "✗ FAIL - Expected security check failure for invalid nonce\n";
    echo "Got: " . substr($output3, 0, 200) . "\n";
    $test_results['nonce_security'] = false;
}

unset($_GET['nonce'], $_GET['result_file']);

// Test 4: Check valid download attempt (should not error)
echo "Test 4: Valid download attempt...\n";

$_GET['nonce'] = wp_create_nonce('ai_virtual_fitting_nonce');
$_GET['result_file'] = $test_filename;

ob_start();
try {
    $public_interface->handle_image_download();
} catch (Exception $e) {
    // Expected for wp_die()
}
$output4 = ob_get_clean();

// For valid downloads, we shouldn't see error messages
$has_errors = (strpos($output4, 'File not found') !== false || 
               strpos($output4, 'Security check failed') !== false ||
               strpos($output4, 'log in') !== false);

if (!$has_errors) {
    echo "✓ PASS - Valid download attempt processed without errors\n";
    $test_results['valid_download'] = true;
} else {
    echo "✗ FAIL - Valid download attempt had unexpected errors\n";
    echo "Got: " . substr($output4, 0, 200) . "\n";
    $test_results['valid_download'] = false;
}

unset($_GET['nonce'], $_GET['result_file']);

// Test 5: Check file security (path traversal prevention)
echo "Test 5: Path traversal security...\n";

$_GET['nonce'] = wp_create_nonce('ai_virtual_fitting_nonce');
$_GET['result_file'] = '../../../etc/passwd';

ob_start();
try {
    $public_interface->handle_image_download();
} catch (Exception $e) {
    // Expected
}
$output5 = ob_get_clean();

if (strpos($output5, 'File not found') !== false) {
    echo "✓ PASS - Path traversal attempt properly blocked\n";
    $test_results['path_security'] = true;
} else {
    echo "✗ FAIL - Path traversal attempt not properly blocked\n";
    echo "Got: " . substr($output5, 0, 200) . "\n";
    $test_results['path_security'] = false;
}

unset($_GET['nonce'], $_GET['result_file']);

// Clean up
wp_delete_user($test_user_id);
if (file_exists($test_filepath)) {
    unlink($test_filepath);
}

// Summary
echo "\n=== DETAILED RESULTS ===\n";
$total_tests = count($test_results);
$passed_tests = array_sum($test_results);

foreach ($test_results as $test_name => $result) {
    $status = $result ? "PASS" : "FAIL";
    echo "{$test_name}: {$status}\n";
}

echo "\n=== FINAL SUMMARY ===\n";
echo "Passed: {$passed_tests}/{$total_tests}\n";

if ($passed_tests === $total_tests) {
    echo "✓ Download Functionality Property Test PASSED\n";
    echo "\nAll download security and functionality requirements validated:\n";
    echo "- Authentication required for downloads ✓\n";
    echo "- File existence validation ✓\n";
    echo "- Nonce security validation ✓\n";
    echo "- Valid downloads process correctly ✓\n";
    echo "- Path traversal security ✓\n";
    exit(0);
} else {
    echo "✗ Download Functionality Property Test FAILED\n";
    echo "Some download functionality requirements not met.\n";
    exit(1);
}