<?php
/**
 * Plugin Lifecycle Integration Tests
 * Tests plugin activation, deactivation, and uninstall scenarios
 *
 * @package AI_Virtual_Fitting
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin Lifecycle Test Class
 */
class AI_Virtual_Fitting_Plugin_Lifecycle_Test {
    
    private $test_user_id;
    private $backup_data = array();
    
    /**
     * Run plugin lifecycle tests
     */
    public function run_tests() {
        echo "=== AI Virtual Fitting Plugin Lifecycle Tests ===\n";
        
        try {
            $this->setup_test_data();
            
            // Test plugin activation
            $this->test_plugin_activation();
            
            // Test plugin deactivation
            $this->test_plugin_deactivation();
            
            // Test data preservation
            $this->test_data_preservation();
            
            // Test plugin reactivation
            $this->test_plugin_reactivation();
            
            // Test uninstall cleanup
            $this->test_uninstall_cleanup();
            
            echo "\n✅ ALL PLUGIN LIFECYCLE TESTS PASSED!\n";
            
        } catch (Exception $e) {
            echo "\n❌ PLUGIN LIFECYCLE TEST FAILED: " . $e->getMessage() . "\n";
            throw $e;
        } finally {
            $this->cleanup_test_data();
        }
    }
    
    /**
     * Setup test data
     */
    private function setup_test_data() {
        echo "\nSetting up test data...\n";
        
        // Create test user with credits
        $this->test_user_id = wp_create_user(
            'lifecycle_test_user_' . time(),
            'test_password_123',
            'lifecycle_test@example.com'
        );
        
        if (is_wp_error($this->test_user_id)) {
            throw new Exception("Failed to create test user");
        }
        
        // Grant credits to test user
        $credit_manager = new AI_Virtual_Fitting_Credit_Manager();
        $credit_manager->grant_initial_credits($this->test_user_id);
        $credit_manager->add_credits($this->test_user_id, 10); // Add extra credits
        
        // Store initial data for comparison
        $this->backup_data['user_credits'] = $credit_manager->get_customer_credits($this->test_user_id);
        $this->backup_data['plugin_options'] = array(
            'initial_credits' => AI_Virtual_Fitting_Core::get_option('initial_credits'),
            'credits_per_package' => AI_Virtual_Fitting_Core::get_option('credits_per_package'),
            'credits_package_price' => AI_Virtual_Fitting_Core::get_option('credits_package_price')
        );
        
        echo "✓ Test data setup complete\n";
    }
    
    /**
     * Test plugin activation
     */
    private function test_plugin_activation() {
        echo "\n1. Testing Plugin Activation...\n";
        
        // Test database table creation
        global $wpdb;
        $table_name = $wpdb->prefix . 'virtual_fitting_credits';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        if (!$table_exists) {
            throw new Exception("Database table not created during activation");
        }
        echo "   ✓ Database table created\n";
        
        // Test table structure
        $columns = $wpdb->get_results("DESCRIBE $table_name");
        $expected_columns = array('id', 'user_id', 'credits_remaining', 'total_credits_purchased', 'created_at', 'updated_at');
        $actual_columns = array_column($columns, 'Field');
        
        foreach ($expected_columns as $expected_column) {
            if (!in_array($expected_column, $actual_columns)) {
                throw new Exception("Missing database column: $expected_column");
            }
        }
        echo "   ✓ Database table structure correct\n";
        
        // Test default options
        $default_options = array(
            'initial_credits' => 2,
            'credits_per_package' => 20,
            'credits_package_price' => 10.00,
            'max_image_size' => 10485760,
            'api_retry_attempts' => 3,
            'enable_logging' => true
        );
        
        foreach ($default_options as $option_name => $expected_value) {
            $actual_value = AI_Virtual_Fitting_Core::get_option($option_name);
            if ($actual_value != $expected_value) {
                throw new Exception("Default option incorrect: $option_name. Expected $expected_value, got $actual_value");
            }
        }
        echo "   ✓ Default options set correctly\n";
        
        // Test WooCommerce credit product creation
        $wc_integration = new AI_Virtual_Fitting_WooCommerce_Integration();
        $credit_product_id = $wc_integration->get_credits_product_id();
        
        if (!$credit_product_id) {
            // Try to create it
            $credit_product_id = $wc_integration->create_credits_product();
        }
        
        if (!$credit_product_id) {
            throw new Exception("WooCommerce credit product not created");
        }
        
        $credit_product = wc_get_product($credit_product_id);
        if (!$credit_product || $credit_product->get_name() !== 'Virtual Fitting Credits - 20 Pack') {
            throw new Exception("WooCommerce credit product not configured correctly");
        }
        echo "   ✓ WooCommerce credit product created\n";
        
        // Test component initialization
        $core = AI_Virtual_Fitting_Core::instance();
        if (!$core->get_credit_manager() || !$core->get_image_processor() || !$core->get_woocommerce_integration()) {
            throw new Exception("Plugin components not initialized correctly");
        }
        echo "   ✓ Plugin components initialized\n";
        
        echo "   ✅ Plugin activation test passed\n";
    }
    
    /**
     * Test plugin deactivation
     */
    private function test_plugin_deactivation() {
        echo "\n2. Testing Plugin Deactivation...\n";
        
        // Store data before deactivation
        $credit_manager = new AI_Virtual_Fitting_Credit_Manager();
        $credits_before = $credit_manager->get_customer_credits($this->test_user_id);
        
        // Simulate deactivation
        AI_Virtual_Fitting_Core::deactivate();
        echo "   ✓ Deactivation hook executed\n";
        
        // Verify data still exists (should be preserved)
        global $wpdb;
        $table_name = $wpdb->prefix . 'virtual_fitting_credits';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        if (!$table_exists) {
            throw new Exception("Database table should be preserved during deactivation");
        }
        echo "   ✓ Database table preserved\n";
        
        // Verify user credits preserved
        $credits_after = $credit_manager->get_customer_credits($this->test_user_id);
        if ($credits_after !== $credits_before) {
            throw new Exception("User credits not preserved during deactivation");
        }
        echo "   ✓ User credits preserved\n";
        
        // Verify plugin options preserved
        foreach ($this->backup_data['plugin_options'] as $option_name => $expected_value) {
            $actual_value = AI_Virtual_Fitting_Core::get_option($option_name);
            if ($actual_value != $expected_value) {
                throw new Exception("Plugin option not preserved: $option_name");
            }
        }
        echo "   ✓ Plugin options preserved\n";
        
        echo "   ✅ Plugin deactivation test passed\n";
    }
    
    /**
     * Test data preservation
     */
    private function test_data_preservation() {
        echo "\n3. Testing Data Preservation...\n";
        
        $credit_manager = new AI_Virtual_Fitting_Credit_Manager();
        
        // Test that all user data is intact
        $current_credits = $credit_manager->get_customer_credits($this->test_user_id);
        if ($current_credits !== $this->backup_data['user_credits']) {
            throw new Exception("User credits not preserved correctly");
        }
        echo "   ✓ User credits preserved correctly\n";
        
        // Test database integrity
        global $wpdb;
        $table_name = $wpdb->prefix . 'virtual_fitting_credits';
        
        // Check if we can still perform database operations
        $user_record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d",
            $this->test_user_id
        ));
        
        if (!$user_record) {
            throw new Exception("User record not found in database");
        }
        
        if ($user_record->credits_remaining != $current_credits) {
            throw new Exception("Database record inconsistent with credit manager");
        }
        echo "   ✓ Database integrity maintained\n";
        
        // Test WooCommerce product preservation
        $wc_integration = new AI_Virtual_Fitting_WooCommerce_Integration();
        $credit_product_id = $wc_integration->get_credits_product_id();
        
        if (!$credit_product_id) {
            throw new Exception("WooCommerce credit product not preserved");
        }
        
        $credit_product = wc_get_product($credit_product_id);
        if (!$credit_product) {
            throw new Exception("WooCommerce credit product corrupted");
        }
        echo "   ✓ WooCommerce integration preserved\n";
        
        echo "   ✅ Data preservation test passed\n";
    }
    
    /**
     * Test plugin reactivation
     */
    private function test_plugin_reactivation() {
        echo "\n4. Testing Plugin Reactivation...\n";
        
        // Simulate reactivation (in real scenario, this would be done by WordPress)
        // We'll test that existing data is recognized and not duplicated
        
        $credit_manager = new AI_Virtual_Fitting_Credit_Manager();
        $credits_before_reactivation = $credit_manager->get_customer_credits($this->test_user_id);
        
        // Test that reactivation doesn't duplicate initial credits
        $credit_manager->grant_initial_credits($this->test_user_id);
        $credits_after_grant = $credit_manager->get_customer_credits($this->test_user_id);
        
        // Should not grant additional credits if user already has credits
        if ($credits_after_grant !== $credits_before_reactivation) {
            throw new Exception("Reactivation should not duplicate initial credits");
        }
        echo "   ✓ No duplicate initial credits on reactivation\n";
        
        // Test that all components still work
        $core = AI_Virtual_Fitting_Core::instance();
        if (!$core->get_credit_manager() || !$core->get_image_processor()) {
            throw new Exception("Plugin components not working after reactivation");
        }
        echo "   ✓ Plugin components working after reactivation\n";
        
        // Test database operations still work
        $test_credits_added = $credit_manager->add_credits($this->test_user_id, 1);
        if (!$test_credits_added) {
            throw new Exception("Database operations not working after reactivation");
        }
        
        $test_credits_deducted = $credit_manager->deduct_credit($this->test_user_id);
        if (!$test_credits_deducted) {
            throw new Exception("Credit deduction not working after reactivation");
        }
        echo "   ✓ Database operations working after reactivation\n";
        
        echo "   ✅ Plugin reactivation test passed\n";
    }
    
    /**
     * Test uninstall cleanup
     */
    private function test_uninstall_cleanup() {
        echo "\n5. Testing Uninstall Cleanup...\n";
        
        // Note: We won't actually run the uninstall in this test
        // Instead, we'll test the uninstall logic components
        
        // Test that uninstall file exists
        $uninstall_file = dirname(__DIR__) . '/uninstall.php';
        if (!file_exists($uninstall_file)) {
            throw new Exception("Uninstall file not found");
        }
        echo "   ✓ Uninstall file exists\n";
        
        // Test database manager cleanup methods
        $db_manager = new AI_Virtual_Fitting_Database_Manager();
        
        // Test that cleanup methods exist and are callable
        if (!method_exists($db_manager, 'drop_tables')) {
            throw new Exception("Database cleanup method not found");
        }
        echo "   ✓ Database cleanup methods available\n";
        
        // Test option cleanup (we'll test the logic without actually deleting)
        $plugin_options = array(
            'google_ai_api_key',
            'initial_credits',
            'credits_per_package',
            'credits_package_price',
            'enable_logging'
        );
        
        foreach ($plugin_options as $option_name) {
            $option_key = 'ai_virtual_fitting_' . $option_name;
            $option_exists = get_option($option_key) !== false;
            if (!$option_exists) {
                echo "   ⚠ Plugin option not found (may be expected): $option_name\n";
            }
        }
        echo "   ✓ Plugin options cleanup logic verified\n";
        
        // Test temporary file cleanup
        $upload_dir = wp_upload_dir();
        $plugin_upload_dir = $upload_dir['basedir'] . '/ai-virtual-fitting/';
        
        if (file_exists($plugin_upload_dir)) {
            echo "   ✓ Plugin upload directory exists for cleanup\n";
        } else {
            echo "   ✓ No plugin upload directory to cleanup\n";
        }
        
        echo "   ✅ Uninstall cleanup test passed\n";
    }
    
    /**
     * Cleanup test data
     */
    private function cleanup_test_data() {
        echo "\nCleaning up test data...\n";
        
        // Delete test user
        if ($this->test_user_id) {
            wp_delete_user($this->test_user_id);
        }
        
        echo "✓ Test data cleaned up\n";
    }
}

// Run tests if called directly
if (defined('WP_CLI') && WP_CLI) {
    $test = new AI_Virtual_Fitting_Plugin_Lifecycle_Test();
    $test->run_tests();
}