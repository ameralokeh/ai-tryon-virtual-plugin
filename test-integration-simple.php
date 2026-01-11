<?php
/**
 * Simple Integration Test for AI Virtual Fitting Plugin
 * Tests basic component integration through WordPress
 */

// Load WordPress
require_once('setup-local-woocommerce.php');

echo "=== AI Virtual Fitting Integration Test ===\n";

try {
    // Test 1: Check if plugin is loaded
    if (!class_exists('AI_Virtual_Fitting_Core')) {
        throw new Exception("AI Virtual Fitting Core class not loaded");
    }
    echo "✓ Plugin core class loaded\n";
    
    // Test 2: Check database tables
    global $wpdb;
    $table_name = $wpdb->prefix . 'virtual_fitting_credits';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    
    if (!$table_exists) {
        throw new Exception("Database table not found: $table_name");
    }
    echo "✓ Database table exists\n";
    
    // Test 3: Check component initialization
    $core = AI_Virtual_Fitting_Core::instance();
    if (!$core) {
        throw new Exception("Core instance not created");
    }
    echo "✓ Core instance created\n";
    
    // Test 4: Check component managers
    $credit_manager = $core->get_credit_manager();
    if (!$credit_manager) {
        throw new Exception("Credit manager not initialized");
    }
    echo "✓ Credit manager initialized\n";
    
    $image_processor = $core->get_image_processor();
    if (!$image_processor) {
        throw new Exception("Image processor not initialized");
    }
    echo "✓ Image processor initialized\n";
    
    $wc_integration = $core->get_woocommerce_integration();
    if (!$wc_integration) {
        throw new Exception("WooCommerce integration not initialized");
    }
    echo "✓ WooCommerce integration initialized\n";
    
    $public_interface = $core->get_public_interface();
    if (!$public_interface) {
        throw new Exception("Public interface not initialized");
    }
    echo "✓ Public interface initialized\n";
    
    // Test 5: Check WooCommerce credit product
    $credit_product_id = $wc_integration->get_credits_product_id();
    if (!$credit_product_id) {
        echo "⚠ WooCommerce credit product not found (may need manual creation)\n";
    } else {
        echo "✓ WooCommerce credit product exists (ID: $credit_product_id)\n";
    }
    
    // Test 6: Test credit operations
    $test_user_id = 1; // Admin user
    $initial_credits = $credit_manager->get_customer_credits($test_user_id);
    echo "✓ Credit retrieval working (User $test_user_id has $initial_credits credits)\n";
    
    // Test 7: Check plugin options
    $api_key = AI_Virtual_Fitting_Core::get_option('google_ai_api_key', '');
    $initial_credits_setting = AI_Virtual_Fitting_Core::get_option('initial_credits', 0);
    
    if ($initial_credits_setting !== 2) {
        throw new Exception("Default initial credits setting incorrect");
    }
    echo "✓ Plugin options configured correctly\n";
    
    // Test 8: Check AJAX hooks
    $ajax_actions = array(
        'ai_virtual_fitting_upload',
        'ai_virtual_fitting_process',
        'ai_virtual_fitting_download',
        'ai_virtual_fitting_get_products',
        'ai_virtual_fitting_check_credits'
    );
    
    foreach ($ajax_actions as $action) {
        if (!has_action("wp_ajax_$action") || !has_action("wp_ajax_nopriv_$action")) {
            throw new Exception("AJAX action not registered: $action");
        }
    }
    echo "✓ AJAX handlers registered\n";
    
    // Test 9: Check rewrite rules
    global $wp_rewrite;
    $wp_rewrite->flush_rules();
    echo "✓ Rewrite rules flushed\n";
    
    // Test 10: Check file structure
    $required_files = array(
        'ai-virtual-fitting/ai-virtual-fitting.php',
        'ai-virtual-fitting/includes/class-virtual-fitting-core.php',
        'ai-virtual-fitting/includes/class-credit-manager.php',
        'ai-virtual-fitting/includes/class-image-processor.php',
        'ai-virtual-fitting/includes/class-woocommerce-integration.php',
        'ai-virtual-fitting/public/class-public-interface.php',
        'ai-virtual-fitting/public/virtual-fitting-page.php',
        'ai-virtual-fitting/public/js/virtual-fitting.js',
        'ai-virtual-fitting/public/css/virtual-fitting.css'
    );
    
    foreach ($required_files as $file) {
        if (!file_exists($file)) {
            throw new Exception("Required file missing: $file");
        }
    }
    echo "✓ All required files present\n";
    
    echo "\n🎉 ALL INTEGRATION TESTS PASSED!\n";
    echo "The AI Virtual Fitting Plugin is properly integrated and ready for use.\n\n";
    
    echo "Next steps:\n";
    echo "1. Configure Google AI Studio API key in WordPress admin\n";
    echo "2. Test the virtual fitting page at: http://localhost:8080/virtual-fitting\n";
    echo "3. Verify WooCommerce credit purchases work correctly\n";
    
} catch (Exception $e) {
    echo "\n❌ INTEGRATION TEST FAILED: " . $e->getMessage() . "\n";
    exit(1);
}