<?php
/**
 * Live Test Script for Embedded Checkout
 * 
 * This script tests the embedded checkout functionality directly
 * in the WordPress environment by simulating user interactions.
 */

// WordPress environment setup
define('WP_USE_THEMES', false);
require_once('/var/www/html/wp-load.php');

/**
 * Live Embedded Checkout Test Class
 */
class Live_Embedded_Checkout_Test {
    
    private $test_results = [];
    private $test_user_id = null;
    
    public function __construct() {
        // Set up test environment
        $this->setup_test_environment();
    }
    
    /**
     * Run comprehensive live tests
     */
    public function run_live_tests() {
        echo "=== Live Embedded Checkout Testing ===\n\n";
        
        // Test 1: Plugin Activation and Setup
        $this->test_plugin_activation();
        
        // Test 2: AJAX Endpoints Functionality
        $this->test_ajax_endpoints();
        
        // Test 3: Cart Management Operations
        $this->test_cart_operations();
        
        // Test 4: Checkout Form Loading
        $this->test_checkout_form_loading();
        
        // Test 5: Credit Management Integration
        $this->test_credit_management();
        
        // Test 6: WooCommerce Integration
        $this->test_woocommerce_integration();
        
        // Test 7: Error Handling
        $this->test_error_scenarios();
        
        // Display comprehensive results
        $this->display_live_results();
        
        return $this->get_overall_success();
    }
    
    /**
     * Setup test environment
     */
    private function setup_test_environment() {
        // Create or get test user
        $test_user = get_user_by('login', 'hooktest');
        if (!$test_user) {
            $this->test_user_id = wp_create_user('live_test_user', 'testpass123', 'livetest@example.com');
        } else {
            $this->test_user_id = $test_user->ID;
        }
        
        // Set current user for testing
        wp_set_current_user($this->test_user_id);
        
        echo "Test environment setup complete. User ID: {$this->test_user_id}\n\n";
    }
    
    /**
     * Test plugin activation and basic setup
     */
    public function test_plugin_activation() {
        echo "Testing Plugin Activation and Setup\n";
        
        $test_cases = [
            'plugin_active' => $this->check_plugin_active(),
            'woocommerce_active' => $this->check_woocommerce_active(),
            'database_tables' => $this->check_database_tables(),
            'ajax_hooks_registered' => $this->check_ajax_hooks(),
            'shortcode_registered' => $this->check_shortcode_registered()
        ];
        
        $this->test_results['plugin_setup'] = $test_cases;
        
        $passed = array_filter($test_cases);
        echo sprintf("  Result: %d/%d setup tests passed\n\n", count($passed), count($test_cases));
    }
    
    /**
     * Test AJAX endpoints functionality
     */
    public function test_ajax_endpoints() {
        echo "Testing AJAX Endpoints Functionality\n";
        
        $test_cases = [
            'add_credits_to_cart' => $this->test_add_credits_endpoint(),
            'clear_cart' => $this->test_clear_cart_endpoint(),
            'load_checkout' => $this->test_load_checkout_endpoint(),
            'refresh_credits' => $this->test_refresh_credits_endpoint()
        ];
        
        $this->test_results['ajax_endpoints'] = $test_cases;
        
        $passed = array_filter($test_cases);
        echo sprintf("  Result: %d/%d AJAX tests passed\n\n", count($passed), count($test_cases));
    }
    
    /**
     * Test cart operations
     */
    public function test_cart_operations() {
        echo "Testing Cart Management Operations\n";
        
        $test_cases = [
            'cart_initialization' => $this->test_cart_initialization(),
            'credit_product_creation' => $this->test_credit_product_creation(),
            'add_to_cart_functionality' => $this->test_add_to_cart_functionality(),
            'cart_clearing' => $this->test_cart_clearing_functionality(),
            'cart_validation' => $this->test_cart_validation()
        ];
        
        $this->test_results['cart_operations'] = $test_cases;
        
        $passed = array_filter($test_cases);
        echo sprintf("  Result: %d/%d cart tests passed\n\n", count($passed), count($test_cases));
    }
    
    /**
     * Test checkout form loading
     */
    public function test_checkout_form_loading() {
        echo "Testing Checkout Form Loading\n";
        
        $test_cases = [
            'checkout_object_creation' => $this->test_checkout_object_creation(),
            'checkout_fields_generation' => $this->test_checkout_fields_generation(),
            'payment_methods_available' => $this->test_payment_methods_available(),
            'form_html_generation' => $this->test_form_html_generation()
        ];
        
        $this->test_results['checkout_form'] = $test_cases;
        
        $passed = array_filter($test_cases);
        echo sprintf("  Result: %d/%d checkout form tests passed\n\n", count($passed), count($test_cases));
    }
    
    /**
     * Test credit management integration
     */
    public function test_credit_management() {
        echo "Testing Credit Management Integration\n";
        
        $test_cases = [
            'credit_manager_exists' => $this->test_credit_manager_exists(),
            'get_customer_credits' => $this->test_get_customer_credits(),
            'credit_addition' => $this->test_credit_addition(),
            'credit_deduction' => $this->test_credit_deduction()
        ];
        
        $this->test_results['credit_management'] = $test_cases;
        
        $passed = array_filter($test_cases);
        echo sprintf("  Result: %d/%d credit management tests passed\n\n", count($passed), count($test_cases));
    }
    
    /**
     * Test WooCommerce integration
     */
    public function test_woocommerce_integration() {
        echo "Testing WooCommerce Integration\n";
        
        $test_cases = [
            'woocommerce_integration_class' => $this->test_woocommerce_integration_class(),
            'credit_product_management' => $this->test_credit_product_management(),
            'order_processing' => $this->test_order_processing_setup()
        ];
        
        $this->test_results['woocommerce_integration'] = $test_cases;
        
        $passed = array_filter($test_cases);
        echo sprintf("  Result: %d/%d WooCommerce tests passed\n\n", count($passed), count($test_cases));
    }
    
    /**
     * Test error handling scenarios
     */
    public function test_error_scenarios() {
        echo "Testing Error Handling Scenarios\n";
        
        $test_cases = [
            'invalid_nonce_handling' => $this->test_invalid_nonce_handling(),
            'empty_cart_handling' => $this->test_empty_cart_handling(),
            'invalid_user_handling' => $this->test_invalid_user_handling()
        ];
        
        $this->test_results['error_handling'] = $test_cases;
        
        $passed = array_filter($test_cases);
        echo sprintf("  Result: %d/%d error handling tests passed\n\n", count($passed), count($test_cases));
    }
    
    // Individual test methods
    
    private function check_plugin_active() {
        return is_plugin_active('ai-virtual-fitting/ai-virtual-fitting.php');
    }
    
    private function check_woocommerce_active() {
        return class_exists('WooCommerce');
    }
    
    private function check_database_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_virtual_fitting_credits';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        
        return $table_exists;
    }
    
    private function check_ajax_hooks() {
        global $wp_filter;
        
        $required_hooks = [
            'wp_ajax_ai_virtual_fitting_add_credits_to_cart',
            'wp_ajax_ai_virtual_fitting_clear_cart',
            'wp_ajax_ai_virtual_fitting_load_checkout',
            'wp_ajax_ai_virtual_fitting_process_checkout'
        ];
        
        foreach ($required_hooks as $hook) {
            if (!isset($wp_filter[$hook])) {
                return false;
            }
        }
        
        return true;
    }
    
    private function check_shortcode_registered() {
        global $shortcode_tags;
        return isset($shortcode_tags['ai_virtual_fitting']);
    }
    
    private function test_add_credits_endpoint() {
        // Simulate AJAX request
        $_POST['action'] = 'ai_virtual_fitting_add_credits_to_cart';
        $_POST['nonce'] = wp_create_nonce('ai_virtual_fitting_nonce');
        
        ob_start();
        
        try {
            // Get the public interface instance
            $public_interface = new AI_Virtual_Fitting_Public_Interface();
            
            // Initialize WooCommerce cart
            if (!WC()->cart) {
                wc_load_cart();
            }
            
            // Clear any existing cart
            WC()->cart->empty_cart();
            
            // Call the handler
            $public_interface->handle_add_credits_to_cart();
            
        } catch (Exception $e) {
            ob_end_clean();
            return false;
        }
        
        $output = ob_get_clean();
        
        // Check if response is JSON and successful
        $response = json_decode($output, true);
        return $response && isset($response['success']) && $response['success'];
    }
    
    private function test_clear_cart_endpoint() {
        $_POST['action'] = 'ai_virtual_fitting_clear_cart';
        $_POST['nonce'] = wp_create_nonce('ai_virtual_fitting_nonce');
        
        ob_start();
        
        try {
            $public_interface = new AI_Virtual_Fitting_Public_Interface();
            $public_interface->handle_clear_cart();
        } catch (Exception $e) {
            ob_end_clean();
            return false;
        }
        
        $output = ob_get_clean();
        $response = json_decode($output, true);
        return $response && isset($response['success']) && $response['success'];
    }
    
    private function test_load_checkout_endpoint() {
        $_POST['action'] = 'ai_virtual_fitting_load_checkout';
        $_POST['nonce'] = wp_create_nonce('ai_virtual_fitting_nonce');
        
        // Ensure cart has items
        if (!WC()->cart) {
            wc_load_cart();
        }
        
        // Add a test product to cart
        $woocommerce_integration = new AI_Virtual_Fitting_WooCommerce_Integration();
        $product_id = $woocommerce_integration->get_or_create_credits_product();
        
        if ($product_id) {
            WC()->cart->add_to_cart($product_id, 1);
        }
        
        ob_start();
        
        try {
            $public_interface = new AI_Virtual_Fitting_Public_Interface();
            $public_interface->handle_load_checkout();
        } catch (Exception $e) {
            ob_end_clean();
            return false;
        }
        
        $output = ob_get_clean();
        $response = json_decode($output, true);
        return $response && isset($response['success']) && $response['success'] && 
               isset($response['data']['checkout_html']);
    }
    
    private function test_refresh_credits_endpoint() {
        $_POST['action'] = 'ai_virtual_fitting_refresh_credits';
        $_POST['nonce'] = wp_create_nonce('ai_virtual_fitting_nonce');
        
        ob_start();
        
        try {
            $public_interface = new AI_Virtual_Fitting_Public_Interface();
            $public_interface->handle_refresh_credits();
        } catch (Exception $e) {
            ob_end_clean();
            return false;
        }
        
        $output = ob_get_clean();
        $response = json_decode($output, true);
        return $response && isset($response['success']) && $response['success'];
    }
    
    private function test_cart_initialization() {
        if (!WC()->cart) {
            wc_load_cart();
        }
        
        return WC()->cart !== null;
    }
    
    private function test_credit_product_creation() {
        $woocommerce_integration = new AI_Virtual_Fitting_WooCommerce_Integration();
        $product_id = $woocommerce_integration->get_or_create_credits_product();
        
        return !empty($product_id) && is_numeric($product_id);
    }
    
    private function test_add_to_cart_functionality() {
        if (!WC()->cart) {
            wc_load_cart();
        }
        
        WC()->cart->empty_cart();
        
        $woocommerce_integration = new AI_Virtual_Fitting_WooCommerce_Integration();
        $product_id = $woocommerce_integration->get_or_create_credits_product();
        
        if (!$product_id) {
            return false;
        }
        
        $cart_item_key = WC()->cart->add_to_cart($product_id, 1);
        
        return !empty($cart_item_key) && WC()->cart->get_cart_contents_count() > 0;
    }
    
    private function test_cart_clearing_functionality() {
        if (!WC()->cart) {
            wc_load_cart();
        }
        
        // Add something to cart first
        $woocommerce_integration = new AI_Virtual_Fitting_WooCommerce_Integration();
        $product_id = $woocommerce_integration->get_or_create_credits_product();
        
        if ($product_id) {
            WC()->cart->add_to_cart($product_id, 1);
        }
        
        // Now clear it
        WC()->cart->empty_cart();
        
        return WC()->cart->get_cart_contents_count() === 0;
    }
    
    private function test_cart_validation() {
        if (!WC()->cart) {
            wc_load_cart();
        }
        
        // Test with empty cart
        WC()->cart->empty_cart();
        $empty_cart_valid = WC()->cart->is_empty();
        
        // Test with items in cart
        $woocommerce_integration = new AI_Virtual_Fitting_WooCommerce_Integration();
        $product_id = $woocommerce_integration->get_or_create_credits_product();
        
        if ($product_id) {
            WC()->cart->add_to_cart($product_id, 1);
        }
        
        $cart_with_items_valid = !WC()->cart->is_empty();
        
        return $empty_cart_valid && $cart_with_items_valid;
    }
    
    private function test_checkout_object_creation() {
        try {
            $checkout = WC()->checkout();
            return $checkout !== null;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function test_checkout_fields_generation() {
        try {
            $checkout = WC()->checkout();
            $fields = $checkout->get_checkout_fields();
            return !empty($fields) && isset($fields['billing']);
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function test_payment_methods_available() {
        try {
            $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
            return !empty($available_gateways);
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function test_form_html_generation() {
        try {
            // Ensure cart has items
            if (!WC()->cart) {
                wc_load_cart();
            }
            
            $woocommerce_integration = new AI_Virtual_Fitting_WooCommerce_Integration();
            $product_id = $woocommerce_integration->get_or_create_credits_product();
            
            if ($product_id) {
                WC()->cart->empty_cart();
                WC()->cart->add_to_cart($product_id, 1);
            }
            
            $checkout = WC()->checkout();
            
            ob_start();
            woocommerce_checkout_billing();
            $billing_html = ob_get_clean();
            
            return !empty($billing_html);
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function test_credit_manager_exists() {
        try {
            $credit_manager = new AI_Virtual_Fitting_Credit_Manager();
            return $credit_manager !== null;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function test_get_customer_credits() {
        try {
            $credit_manager = new AI_Virtual_Fitting_Credit_Manager();
            $credits = $credit_manager->get_customer_credits($this->test_user_id);
            return is_numeric($credits);
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function test_credit_addition() {
        try {
            $credit_manager = new AI_Virtual_Fitting_Credit_Manager();
            $initial_credits = $credit_manager->get_customer_credits($this->test_user_id);
            
            $credit_manager->add_credits($this->test_user_id, 5);
            
            $new_credits = $credit_manager->get_customer_credits($this->test_user_id);
            
            return $new_credits === ($initial_credits + 5);
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function test_credit_deduction() {
        try {
            $credit_manager = new AI_Virtual_Fitting_Credit_Manager();
            
            // Ensure user has credits
            $credit_manager->add_credits($this->test_user_id, 10);
            $initial_credits = $credit_manager->get_customer_credits($this->test_user_id);
            
            $credit_manager->deduct_credit($this->test_user_id);
            
            $new_credits = $credit_manager->get_customer_credits($this->test_user_id);
            
            return $new_credits === ($initial_credits - 1);
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function test_woocommerce_integration_class() {
        try {
            $woocommerce_integration = new AI_Virtual_Fitting_WooCommerce_Integration();
            return $woocommerce_integration !== null;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function test_credit_product_management() {
        try {
            $woocommerce_integration = new AI_Virtual_Fitting_WooCommerce_Integration();
            $product_id = $woocommerce_integration->get_or_create_credits_product();
            
            if (!$product_id) {
                return false;
            }
            
            $product = wc_get_product($product_id);
            return $product && $product->get_name() === 'Virtual Fitting Credits';
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function test_order_processing_setup() {
        try {
            $woocommerce_integration = new AI_Virtual_Fitting_WooCommerce_Integration();
            
            // Check if the method exists
            return method_exists($woocommerce_integration, 'process_order_completion');
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function test_invalid_nonce_handling() {
        $_POST['action'] = 'ai_virtual_fitting_add_credits_to_cart';
        $_POST['nonce'] = 'invalid_nonce';
        
        ob_start();
        
        try {
            $public_interface = new AI_Virtual_Fitting_Public_Interface();
            $public_interface->handle_add_credits_to_cart();
        } catch (Exception $e) {
            ob_end_clean();
            return true; // Exception expected for invalid nonce
        }
        
        $output = ob_get_clean();
        $response = json_decode($output, true);
        
        // Should return error for invalid nonce
        return $response && isset($response['success']) && !$response['success'];
    }
    
    private function test_empty_cart_handling() {
        $_POST['action'] = 'ai_virtual_fitting_load_checkout';
        $_POST['nonce'] = wp_create_nonce('ai_virtual_fitting_nonce');
        
        // Ensure cart is empty
        if (!WC()->cart) {
            wc_load_cart();
        }
        WC()->cart->empty_cart();
        
        ob_start();
        
        try {
            $public_interface = new AI_Virtual_Fitting_Public_Interface();
            $public_interface->handle_load_checkout();
        } catch (Exception $e) {
            ob_end_clean();
            return true; // Exception or error expected for empty cart
        }
        
        $output = ob_get_clean();
        $response = json_decode($output, true);
        
        // Should handle empty cart gracefully (either success with recovery or appropriate error)
        return $response && isset($response['success']);
    }
    
    private function test_invalid_user_handling() {
        // Test with no user logged in
        wp_set_current_user(0);
        
        $_POST['action'] = 'ai_virtual_fitting_refresh_credits';
        $_POST['nonce'] = wp_create_nonce('ai_virtual_fitting_nonce');
        
        ob_start();
        
        try {
            $public_interface = new AI_Virtual_Fitting_Public_Interface();
            $public_interface->handle_refresh_credits();
        } catch (Exception $e) {
            ob_end_clean();
            // Restore test user
            wp_set_current_user($this->test_user_id);
            return true;
        }
        
        $output = ob_get_clean();
        $response = json_decode($output, true);
        
        // Restore test user
        wp_set_current_user($this->test_user_id);
        
        // Should handle non-logged-in user appropriately
        return $response && isset($response['success']);
    }
    
    /**
     * Display comprehensive live test results
     */
    private function display_live_results() {
        echo "=== Live Test Results Summary ===\n";
        
        $total_tests = 0;
        $total_passed = 0;
        
        foreach ($this->test_results as $category => $tests) {
            $passed = array_filter($tests);
            $total_tests += count($tests);
            $total_passed += count($passed);
            
            echo sprintf("%s: %d/%d passed\n", 
                ucwords(str_replace('_', ' ', $category)), 
                count($passed), 
                count($tests)
            );
            
            // Show failed tests
            foreach ($tests as $test_name => $result) {
                if (!$result) {
                    echo sprintf("  ❌ %s\n", ucwords(str_replace('_', ' ', $test_name)));
                }
            }
        }
        
        echo sprintf("\nOverall Live Testing: %d/%d tests passed (%.1f%%)\n", 
            $total_passed, 
            $total_tests, 
            ($total_passed / $total_tests) * 100
        );
        
        if ($total_passed === $total_tests) {
            echo "\n🎉 ALL LIVE TESTS PASSED!\n";
            echo "The embedded checkout system is fully functional in the WordPress environment.\n";
        } else {
            $failed = $total_tests - $total_passed;
            echo sprintf("\n⚠️  %d tests failed!\n", $failed);
            echo "Review the failed tests and check the WordPress environment setup.\n";
        }
        
        echo "\n=== Next Steps ===\n";
        echo "1. Open http://localhost:8080/virtual-fitting-2/ in your browser\n";
        echo "2. Log in as a test user (hooktest / password)\n";
        echo "3. Click 'Get More Credits' to test the embedded checkout modal\n";
        echo "4. Complete a test purchase to verify the full flow\n";
    }
    
    /**
     * Get overall success status
     */
    private function get_overall_success() {
        $total_tests = 0;
        $total_passed = 0;
        
        foreach ($this->test_results as $tests) {
            $passed = array_filter($tests);
            $total_tests += count($tests);
            $total_passed += count($passed);
        }
        
        return $total_passed === $total_tests;
    }
}

// Run the live test
$live_test = new Live_Embedded_Checkout_Test();
$success = $live_test->run_live_tests();

exit($success ? 0 : 1);