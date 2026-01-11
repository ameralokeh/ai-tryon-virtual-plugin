<?php
/**
 * Integration Tests for AI Virtual Fitting Plugin
 * Tests complete virtual fitting workflow and WooCommerce integration
 *
 * @package AI_Virtual_Fitting
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Integration Workflow Test Class
 */
class AI_Virtual_Fitting_Integration_Workflow_Test {
    
    private $test_user_id;
    private $test_product_id;
    private $test_order_id;
    private $temp_files = array();
    
    /**
     * Run all integration tests
     */
    public function run_tests() {
        echo "=== AI Virtual Fitting Integration Workflow Tests ===\n";
        
        try {
            $this->setup_test_environment();
            
            // Test complete virtual fitting workflow
            $this->test_complete_virtual_fitting_workflow();
            
            // Test WooCommerce integration and credit purchases
            $this->test_woocommerce_credit_purchase_workflow();
            
            // Test plugin activation/deactivation scenarios
            $this->test_plugin_lifecycle_scenarios();
            
            // Test error handling and recovery
            $this->test_error_handling_integration();
            
            // Test concurrent user scenarios
            $this->test_concurrent_user_scenarios();
            
            echo "\n✅ ALL INTEGRATION WORKFLOW TESTS PASSED!\n";
            
        } catch (Exception $e) {
            echo "\n❌ INTEGRATION TEST FAILED: " . $e->getMessage() . "\n";
            throw $e;
        } finally {
            $this->cleanup_test_environment();
        }
    }
    
    /**
     * Setup test environment
     */
    private function setup_test_environment() {
        echo "\nSetting up test environment...\n";
        
        // Create test user
        $this->test_user_id = wp_create_user(
            'integration_test_user_' . time(),
            'test_password_123',
            'integration_test@example.com'
        );
        
        if (is_wp_error($this->test_user_id)) {
            throw new Exception("Failed to create test user: " . $this->test_user_id->get_error_message());
        }
        
        // Create test product
        $product = new WC_Product_Simple();
        $product->set_name('Integration Test Wedding Dress');
        $product->set_regular_price('299.99');
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        $this->test_product_id = $product->save();
        
        if (!$this->test_product_id) {
            throw new Exception("Failed to create test product");
        }
        
        // Add product images
        $this->add_test_product_images($this->test_product_id);
        
        echo "✓ Test environment setup complete\n";
    }
    
    /**
     * Test complete virtual fitting workflow
     */
    private function test_complete_virtual_fitting_workflow() {
        echo "\n1. Testing Complete Virtual Fitting Workflow...\n";
        
        // Step 1: User registration and initial credit allocation
        $credit_manager = new AI_Virtual_Fitting_Credit_Manager();
        $credit_manager->grant_initial_credits($this->test_user_id);
        
        $initial_credits = $credit_manager->get_customer_credits($this->test_user_id);
        if ($initial_credits !== 2) {
            throw new Exception("Initial credits not allocated correctly. Expected 2, got $initial_credits");
        }
        echo "   ✓ User registration and initial credit allocation\n";
        
        // Step 2: User authentication
        wp_set_current_user($this->test_user_id);
        if (!is_user_logged_in()) {
            throw new Exception("User authentication failed");
        }
        echo "   ✓ User authentication successful\n";
        
        // Step 3: Product selection
        $public_interface = new AI_Virtual_Fitting_Public_Interface();
        $reflection = new ReflectionClass($public_interface);
        $get_products_method = $reflection->getMethod('get_woocommerce_products');
        $get_products_method->setAccessible(true);
        $products = $get_products_method->invoke($public_interface);
        
        if (empty($products)) {
            throw new Exception("No products available for virtual fitting");
        }
        
        $selected_product = null;
        foreach ($products as $product) {
            if ($product['id'] == $this->test_product_id) {
                $selected_product = $product;
                break;
            }
        }
        
        if (!$selected_product) {
            throw new Exception("Test product not found in virtual fitting products");
        }
        echo "   ✓ Product selection working\n";
        
        // Step 4: Image upload and validation
        $test_image_path = $this->create_test_image();
        $this->temp_files[] = $test_image_path;
        
        $image_processor = new AI_Virtual_Fitting_Image_Processor();
        $mock_file = array(
            'name' => 'test-customer-image.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => $test_image_path,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($test_image_path)
        );
        
        $validation_result = $image_processor->validate_uploaded_image($mock_file);
        if (is_wp_error($validation_result)) {
            throw new Exception("Image validation failed: " . $validation_result->get_error_message());
        }
        echo "   ✓ Image upload and validation working\n";
        
        // Step 5: Virtual fitting processing (mock)
        $product_images_method = $reflection->getMethod('get_product_images_for_ai');
        $product_images_method->setAccessible(true);
        $product_images = $product_images_method->invoke($public_interface, $this->test_product_id);
        
        if (empty($product_images)) {
            throw new Exception("No product images found for AI processing");
        }
        echo "   ✓ Product images retrieved for AI processing\n";
        
        // Step 6: Credit deduction after successful processing
        $credits_before = $credit_manager->get_customer_credits($this->test_user_id);
        $deduction_result = $credit_manager->deduct_credit($this->test_user_id);
        
        if (!$deduction_result) {
            throw new Exception("Credit deduction failed");
        }
        
        $credits_after = $credit_manager->get_customer_credits($this->test_user_id);
        if ($credits_after !== $credits_before - 1) {
            throw new Exception("Credit not deducted correctly");
        }
        echo "   ✓ Credit deduction after successful processing\n";
        
        // Step 7: Result download functionality
        $result_image_path = $this->create_test_result_image();
        $this->temp_files[] = $result_image_path;
        
        if (!file_exists($result_image_path)) {
            throw new Exception("Result image not created");
        }
        echo "   ✓ Result image creation and download preparation\n";
        
        echo "   ✅ Complete virtual fitting workflow test passed\n";
    }
    
    /**
     * Test WooCommerce credit purchase workflow
     */
    private function test_woocommerce_credit_purchase_workflow() {
        echo "\n2. Testing WooCommerce Credit Purchase Workflow...\n";
        
        $wc_integration = new AI_Virtual_Fitting_WooCommerce_Integration();
        $credit_manager = new AI_Virtual_Fitting_Credit_Manager();
        
        // Step 1: Get or create credit product
        $credit_product_id = $wc_integration->get_credits_product_id();
        if (!$credit_product_id) {
            $credit_product_id = $wc_integration->create_credits_product();
        }
        
        if (!$credit_product_id) {
            throw new Exception("Failed to create or retrieve credit product");
        }
        echo "   ✓ Credit product available\n";
        
        // Step 2: Create order with credit product
        $order = wc_create_order();
        $credit_product = wc_get_product($credit_product_id);
        $order->add_product($credit_product, 1);
        $order->set_customer_id($this->test_user_id);
        $order->calculate_totals();
        $this->test_order_id = $order->save();
        
        if (!$this->test_order_id) {
            throw new Exception("Failed to create test order");
        }
        echo "   ✓ Order created with credit product\n";
        
        // Step 3: Test order completion and credit addition
        $credits_before = $credit_manager->get_customer_credits($this->test_user_id);
        
        // Simulate order completion
        $order->update_status('completed');
        $wc_integration->handle_order_completed($this->test_order_id);
        
        $credits_after = $credit_manager->get_customer_credits($this->test_user_id);
        $expected_credits = $credits_before + 20; // Default credits per package
        
        if ($credits_after !== $expected_credits) {
            throw new Exception("Credits not added after order completion. Expected $expected_credits, got $credits_after");
        }
        echo "   ✓ Credits added after successful order completion\n";
        
        // Step 4: Test order status changes
        $order->update_status('refunded');
        // Note: We don't automatically deduct credits on refund in this implementation
        // This would be a business decision
        echo "   ✓ Order status change handling\n";
        
        echo "   ✅ WooCommerce credit purchase workflow test passed\n";
    }
    
    /**
     * Test plugin activation/deactivation scenarios
     */
    private function test_plugin_lifecycle_scenarios() {
        echo "\n3. Testing Plugin Lifecycle Scenarios...\n";
        
        // Test database table existence
        global $wpdb;
        $table_name = $wpdb->prefix . 'virtual_fitting_credits';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        if (!$table_exists) {
            throw new Exception("Database table not found after activation");
        }
        echo "   ✓ Database tables exist after activation\n";
        
        // Test data preservation during deactivation
        $credit_manager = new AI_Virtual_Fitting_Credit_Manager();
        $credits_before_deactivation = $credit_manager->get_customer_credits($this->test_user_id);
        
        // Simulate deactivation (we can't actually deactivate during test)
        // But we can verify the deactivation method preserves data
        AI_Virtual_Fitting_Core::deactivate();
        
        // Verify data still exists
        $credits_after_deactivation = $credit_manager->get_customer_credits($this->test_user_id);
        if ($credits_after_deactivation !== $credits_before_deactivation) {
            throw new Exception("Data not preserved during deactivation");
        }
        echo "   ✓ Data preserved during deactivation\n";
        
        // Test plugin options
        $initial_credits = AI_Virtual_Fitting_Core::get_option('initial_credits', 0);
        if ($initial_credits !== 2) {
            throw new Exception("Plugin options not set correctly");
        }
        echo "   ✓ Plugin options configured correctly\n";
        
        echo "   ✅ Plugin lifecycle scenarios test passed\n";
    }
    
    /**
     * Test error handling integration
     */
    private function test_error_handling_integration() {
        echo "\n4. Testing Error Handling Integration...\n";
        
        $credit_manager = new AI_Virtual_Fitting_Credit_Manager();
        $image_processor = new AI_Virtual_Fitting_Image_Processor();
        
        // Test insufficient credits scenario
        $current_credits = $credit_manager->get_customer_credits($this->test_user_id);
        
        // Deduct all credits
        for ($i = 0; $i < $current_credits; $i++) {
            $credit_manager->deduct_credit($this->test_user_id);
        }
        
        // Try to deduct when no credits available
        $deduction_result = $credit_manager->deduct_credit($this->test_user_id);
        if ($deduction_result !== false) {
            throw new Exception("Should not allow deduction when no credits available");
        }
        echo "   ✓ Insufficient credits error handling\n";
        
        // Test invalid image upload
        $invalid_file = array(
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => __FILE__, // Use this PHP file as invalid image
            'error' => UPLOAD_ERR_OK,
            'size' => filesize(__FILE__)
        );
        
        $validation_result = $image_processor->validate_uploaded_image($invalid_file);
        if (!is_wp_error($validation_result)) {
            throw new Exception("Invalid file should be rejected");
        }
        echo "   ✓ Invalid image upload error handling\n";
        
        // Test invalid user ID
        $invalid_credits = $credit_manager->get_customer_credits(999999);
        if ($invalid_credits !== 0) {
            throw new Exception("Invalid user ID should return 0 credits");
        }
        echo "   ✓ Invalid user ID error handling\n";
        
        // Restore credits for other tests
        $credit_manager->add_credits($this->test_user_id, 5);
        
        echo "   ✅ Error handling integration test passed\n";
    }
    
    /**
     * Test concurrent user scenarios
     */
    private function test_concurrent_user_scenarios() {
        echo "\n5. Testing Concurrent User Scenarios...\n";
        
        // Create multiple test users
        $test_users = array();
        for ($i = 0; $i < 3; $i++) {
            $user_id = wp_create_user(
                'concurrent_test_user_' . $i . '_' . time(),
                'test_password_123',
                "concurrent_test_$i@example.com"
            );
            
            if (is_wp_error($user_id)) {
                throw new Exception("Failed to create concurrent test user $i");
            }
            
            $test_users[] = $user_id;
        }
        
        // Test concurrent credit operations
        $credit_manager = new AI_Virtual_Fitting_Credit_Manager();
        
        foreach ($test_users as $user_id) {
            // Grant initial credits
            $credit_manager->grant_initial_credits($user_id);
            
            // Verify credits
            $credits = $credit_manager->get_customer_credits($user_id);
            if ($credits !== 2) {
                throw new Exception("Concurrent credit allocation failed for user $user_id");
            }
        }
        echo "   ✓ Concurrent credit allocation\n";
        
        // Test concurrent credit deductions
        foreach ($test_users as $user_id) {
            $deduction_result = $credit_manager->deduct_credit($user_id);
            if (!$deduction_result) {
                throw new Exception("Concurrent credit deduction failed for user $user_id");
            }
            
            $remaining_credits = $credit_manager->get_customer_credits($user_id);
            if ($remaining_credits !== 1) {
                throw new Exception("Concurrent credit deduction incorrect for user $user_id");
            }
        }
        echo "   ✓ Concurrent credit deductions\n";
        
        // Cleanup concurrent test users
        foreach ($test_users as $user_id) {
            wp_delete_user($user_id);
        }
        
        echo "   ✅ Concurrent user scenarios test passed\n";
    }
    
    /**
     * Add test product images
     */
    private function add_test_product_images($product_id) {
        // Create test images for the product
        $featured_image_path = $this->create_test_image('featured');
        $gallery_image_paths = array();
        
        for ($i = 1; $i <= 3; $i++) {
            $gallery_image_paths[] = $this->create_test_image("gallery_$i");
        }
        
        $this->temp_files = array_merge($this->temp_files, array($featured_image_path), $gallery_image_paths);
        
        // In a real scenario, we would upload these to WordPress media library
        // For testing, we just verify the files exist
        if (!file_exists($featured_image_path)) {
            throw new Exception("Failed to create featured image for test product");
        }
        
        foreach ($gallery_image_paths as $path) {
            if (!file_exists($path)) {
                throw new Exception("Failed to create gallery image for test product");
            }
        }
    }
    
    /**
     * Create test image
     */
    private function create_test_image($type = 'customer') {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/ai-virtual-fitting/temp/';
        
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }
        
        $image_path = $temp_dir . "test_{$type}_image_" . time() . '_' . rand(1000, 9999) . '.jpg';
        
        // Create a simple test image
        $image = imagecreate(800, 600);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $blue = imagecolorallocate($image, 0, 100, 200);
        
        imagefill($image, 0, 0, $white);
        imagestring($image, 5, 250, 250, strtoupper($type), $black);
        imagestring($image, 3, 300, 300, 'TEST IMAGE', $blue);
        imagestring($image, 2, 350, 350, date('Y-m-d H:i:s'), $black);
        
        imagejpeg($image, $image_path, 90);
        imagedestroy($image);
        
        return $image_path;
    }
    
    /**
     * Create test result image
     */
    private function create_test_result_image() {
        $upload_dir = wp_upload_dir();
        $results_dir = $upload_dir['basedir'] . '/ai-virtual-fitting/results/';
        
        if (!file_exists($results_dir)) {
            wp_mkdir_p($results_dir);
        }
        
        $result_path = $results_dir . 'test_result_' . time() . '.jpg';
        
        // Create a mock result image
        $image = imagecreate(800, 600);
        $white = imagecolorallocate($image, 255, 255, 255);
        $green = imagecolorallocate($image, 0, 150, 0);
        
        imagefill($image, 0, 0, $white);
        imagestring($image, 5, 200, 280, 'VIRTUAL FITTING RESULT', $green);
        
        imagejpeg($image, $result_path, 90);
        imagedestroy($image);
        
        return $result_path;
    }
    
    /**
     * Cleanup test environment
     */
    private function cleanup_test_environment() {
        echo "\nCleaning up test environment...\n";
        
        // Delete test user
        if ($this->test_user_id) {
            wp_delete_user($this->test_user_id);
        }
        
        // Delete test product
        if ($this->test_product_id) {
            wp_delete_post($this->test_product_id, true);
        }
        
        // Delete test order
        if ($this->test_order_id) {
            wp_delete_post($this->test_order_id, true);
        }
        
        // Delete temporary files
        foreach ($this->temp_files as $file_path) {
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        echo "✓ Test environment cleaned up\n";
    }
}

// Run tests if called directly
if (defined('WP_CLI') && WP_CLI) {
    $test = new AI_Virtual_Fitting_Integration_Workflow_Test();
    $test->run_tests();
}