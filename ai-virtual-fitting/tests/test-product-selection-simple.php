<?php
/**
 * Simple Product Selection Test
 * Tests product selection functionality
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

// Load plugin classes
require_once('/var/www/html/wp-content/plugins/ai-virtual-fitting/includes/class-database-manager.php');
require_once('/var/www/html/wp-content/plugins/ai-virtual-fitting/includes/class-credit-manager.php');
require_once('/var/www/html/wp-content/plugins/ai-virtual-fitting/includes/class-image-processor.php');
require_once('/var/www/html/wp-content/plugins/ai-virtual-fitting/includes/class-woocommerce-integration.php');
require_once('/var/www/html/wp-content/plugins/ai-virtual-fitting/public/class-public-interface.php');

echo "=== Product Selection Property Test ===\n";
echo "Feature: ai-virtual-fitting, Property 2: Product Selection Consistency\n";
echo "Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5\n\n";

// Initialize components
$database_manager = new AI_Virtual_Fitting_Database_Manager();
$database_manager->create_tables();

$public_interface = new AI_Virtual_Fitting_Public_Interface();

// Create test user
$test_user_id = wp_create_user('test_products_' . time(), 'testpass', 'test_products_' . time() . '@example.com');
wp_set_current_user($test_user_id);

// Test Case 1: Get products for authenticated user
echo "Test 1: Get products for authenticated user... ";

$_POST['nonce'] = wp_create_nonce('ai_virtual_fitting_nonce');
$_POST['action'] = 'ai_virtual_fitting_get_products';

ob_start();
try {
    $public_interface->handle_get_products();
} catch (Exception $e) {
    // Expected - wp_send_json calls exit
}
$output1 = ob_get_clean();

// Parse JSON response
$response1 = json_decode($output1, true);

if ($response1 && $response1['success'] && isset($response1['data']['products'])) {
    echo "PASS\n";
    $test1_pass = true;
    
    // Validate product data structure
    $products = $response1['data']['products'];
    echo "  Found " . count($products) . " products\n";
    
    if (count($products) > 0) {
        $sample_product = $products[0];
        $required_fields = array('id', 'name', 'price', 'image', 'gallery');
        $has_all_fields = true;
        
        foreach ($required_fields as $field) {
            if (!isset($sample_product[$field])) {
                $has_all_fields = false;
                echo "  Missing field: {$field}\n";
            }
        }
        
        if ($has_all_fields) {
            echo "  Product data structure: PASS\n";
        } else {
            echo "  Product data structure: FAIL\n";
        }
    }
} else {
    echo "FAIL (Expected success response with products, got: " . substr($output1, 0, 100) . ")\n";
    $test1_pass = false;
}

unset($_POST['nonce'], $_POST['action']);

// Test Case 2: Unauthenticated user get products
echo "Test 2: Unauthenticated user get products... ";
wp_set_current_user(0); // Unauthenticated

$_POST['nonce'] = wp_create_nonce('ai_virtual_fitting_nonce');
$_POST['action'] = 'ai_virtual_fitting_get_products';

ob_start();
try {
    $public_interface->handle_get_products();
} catch (Exception $e) {
    // Expected
}
$output2 = ob_get_clean();

// Parse JSON response
$response2 = json_decode($output2, true);

if ($response2 && $response2['success'] && isset($response2['data']['products'])) {
    echo "PASS (Products available to unauthenticated users)\n";
    $test2_pass = true;
} else {
    echo "FAIL (Expected products to be available, got: " . substr($output2, 0, 100) . ")\n";
    $test2_pass = false;
}

unset($_POST['nonce'], $_POST['action']);

// Clean up
wp_delete_user($test_user_id);

// Summary
echo "\n=== RESULTS ===\n";
$total_tests = 2;
$passed_tests = ($test1_pass ? 1 : 0) + ($test2_pass ? 1 : 0);

echo "Passed: {$passed_tests}/{$total_tests}\n";

if ($passed_tests === $total_tests) {
    echo "✓ Product Selection Property Test PASSED\n";
    exit(0);
} else {
    echo "✗ Product Selection Property Test FAILED\n";
    exit(1);
}