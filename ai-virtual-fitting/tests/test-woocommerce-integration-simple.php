<?php
/**
 * Simple Property-Based Test for WooCommerce Integration
 * This validates the WooCommerce integration property test logic in WordPress environment
 *
 * @package AI_Virtual_Fitting
 */

// WordPress environment check
if (!defined('ABSPATH')) {
    // Load WordPress if not already loaded
    $wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
    if (file_exists($wp_load_path)) {
        require_once $wp_load_path;
    } else {
        die('WordPress environment not found. Please run this from within WordPress.');
    }
}

// Check if WooCommerce is available
if (!class_exists('WooCommerce')) {
    die('WooCommerce is not available. Please ensure WooCommerce is installed and activated.');
}

// Load plugin classes
require_once dirname(dirname(__FILE__)) . '/includes/class-database-manager.php';
require_once dirname(dirname(__FILE__)) . '/includes/class-credit-manager.php';
require_once dirname(dirname(__FILE__)) . '/includes/class-woocommerce-integration.php';

/**
 * Simple WooCommerce Integration Property Test Runner
 */
class SimpleWooCommerceIntegrationTestRunner {
    
    private $wc_integration;
    private $credit_manager;
    private $database_manager;
    private $test_results = array();
    private $test_users = array();
    private $test_orders = array();
    private $test_products = array();
    
    public function __construct() {
        // Initialize components
        $this->database_manager = new AI_Virtual_Fitting_Database_Manager();
        $this->credit_manager = new AI_Virtual_Fitting_Credit_Manager();
        $this->wc_integration = new AI_Virtual_Fitting_WooCommerce_Integration();
        
        // Ensure tables exist
        $this->database_manager->create_tables();
        
        // Set test options
        update_option('ai_virtual_fitting_initial_credits', 2);
        update_option('ai_virtual_fitting_enable_logging', false);
    }
    
    public function run_all_tests() {
        echo "Running AI Virtual Fitting WooCommerce Integration Property Tests\n";
        echo "================================================================\n\n";
        
        $this->test_woocommerce_integration_consistency_property();
        $this->test_cart_integration_property();
        $this->test_product_validation_property();
        $this->test_purchase_url_generation_property();
        $this->test_initialization_property();
        
        $this->cleanup_test_data();
        $this->print_results();
    }
    
    /**
     * Property 7: WooCommerce Integration Consistency
     * **Validates: Requirements 5.2, 5.3, 5.4, 5.6, 5.7**
     */
    public function test_woocommerce_integration_consistency_property() {
        echo "Testing Property 7: WooCommerce Integration Consistency\n";
        
        try {
            // Test credits product creation (Requirement 5.2)
            $product_id = $this->wc_integration->create_credits_product();
            $this->assert_true(($product_id !== false && $product_id > 0), "Credits product should be created successfully");
            $this->test_products[] = $product_id;
            
            // Verify product properties
            $product = wc_get_product($product_id);
            $this->assert_true($product instanceof WC_Product, "Created product should be valid WooCommerce product");
            $this->assert_true($product->get_name() === 'Virtual Fitting Credits - 20 Pack', "Product should have correct name");
            $this->assert_true($product->get_price() === '10.00', "Product should have correct price");
            $this->assert_true($product->is_virtual(), "Product should be virtual");
            $this->assert_true($product->get_catalog_visibility() === 'hidden', "Product should be hidden from catalog");
            $this->assert_true($product->get_meta('_virtual_fitting_credits') === '20', "Product should have correct credits metadata");
            $this->assert_true($product->get_meta('_virtual_fitting_product') === 'yes', "Product should be marked as credits product");
            
            // Test product creation idempotency
            $second_product_id = $this->wc_integration->create_credits_product();
            $this->assert_true($product_id === $second_product_id, "Second creation should return same product ID");
            
            // Test order processing scenarios
            $test_scenarios = array(
                array('quantity' => 1, 'expected_credits' => 20, 'description' => 'single credit pack'),
                array('quantity' => 2, 'expected_credits' => 40, 'description' => 'two credit packs'),
                array('quantity' => 3, 'expected_credits' => 60, 'description' => 'three credit packs'),
            );
            
            foreach ($test_scenarios as $scenario) {
                // Create test user
                $customer_id = wp_create_user(
                    'testuser_' . uniqid(),
                    'testpass123',
                    'test_' . uniqid() . '@example.com'
                );
                $this->test_users[] = $customer_id;
                
                // Get initial credits (should be 2 for new users)
                $initial_credits = $this->credit_manager->get_customer_credits($customer_id);
                $this->assert_true($initial_credits === 2, "New customer should have 2 initial credits");
                
                // Create WooCommerce order with credits product (Requirement 5.3)
                $order = wc_create_order();
                $order->set_customer_id($customer_id);
                $order->add_product($product, $scenario['quantity']);
                $order->set_status('completed');
                $order->save();
                $this->test_orders[] = $order->get_id();
                
                // Process the order (Requirements 5.4, 5.6, 5.7)
                $this->wc_integration->handle_order_completed($order->get_id());
                
                // Refresh order object to get updated metadata
                $order = wc_get_order($order->get_id());
                
                // Verify credits were added correctly
                $final_credits = $this->credit_manager->get_customer_credits($customer_id);
                $expected_total = $initial_credits + $scenario['expected_credits'];
                $this->assert_true($final_credits === $expected_total, 
                                  "Customer should have {$expected_total} credits after purchasing {$scenario['description']}");
                
                // Verify order was marked as processed
                $processed = $order->get_meta('_virtual_fitting_credits_processed');
                $this->assert_true($processed === 'yes', "Order should be marked as processed for {$scenario['description']}");
                
                // Test order processing idempotency
                $this->wc_integration->handle_order_completed($order->get_id());
                $credits_after_reprocess = $this->credit_manager->get_customer_credits($customer_id);
                $this->assert_true($credits_after_reprocess === $expected_total, 
                                  "Credits should not be added again when reprocessing order for {$scenario['description']}");
            }
            
            // Test edge cases
            
            // Test order without customer ID
            $no_customer_order = wc_create_order();
            $no_customer_order->add_product($product, 1);
            $no_customer_order->set_status('completed');
            $no_customer_order->save();
            $this->test_orders[] = $no_customer_order->get_id();
            
            // Should not crash or add credits
            $this->wc_integration->handle_order_completed($no_customer_order->get_id());
            $processed = $no_customer_order->get_meta('_virtual_fitting_credits_processed');
            $this->assert_true(empty($processed), "Order without customer should not be processed");
            
            // Test invalid order IDs (should not crash)
            $this->wc_integration->handle_order_completed(0);
            $this->wc_integration->handle_order_completed(null);
            
            $this->test_results['woocommerce_integration'] = 'PASSED';
            echo "✓ WooCommerce Integration Consistency property test PASSED\n\n";
            
        } catch (Exception $e) {
            $this->test_results['woocommerce_integration'] = 'FAILED: ' . $e->getMessage();
            echo "✗ WooCommerce Integration Consistency property test FAILED: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * Test cart integration functionality
     */
    public function test_cart_integration_property() {
        echo "Testing Cart Integration Property\n";
        
        try {
            // Clear cart first
            WC()->cart->empty_cart();
            
            // Test adding credits to cart
            $cart_item_key = $this->wc_integration->add_credits_to_cart(1);
            $this->assert_true(is_string($cart_item_key) && !empty($cart_item_key), 
                              "Adding credits to cart should return non-empty cart item key");
            
            // Verify cart contents
            $cart_contents = WC()->cart->get_cart();
            $this->assert_true(count($cart_contents) === 1, "Cart should contain one item");
            
            $cart_item = reset($cart_contents);
            $this->assert_true($cart_item['quantity'] === 1, "Cart item should have correct quantity");
            
            $product_in_cart = $cart_item['data'];
            $this->assert_true($product_in_cart->get_name() === 'Virtual Fitting Credits - 20 Pack', 
                              "Cart should contain credits product");
            
            // Test adding multiple quantities
            WC()->cart->empty_cart();
            $multi_cart_key = $this->wc_integration->add_credits_to_cart(3);
            $this->assert_true(is_string($multi_cart_key) && !empty($multi_cart_key), 
                              "Adding multiple credits to cart should work");
            
            $multi_cart_contents = WC()->cart->get_cart();
            $multi_cart_item = reset($multi_cart_contents);
            $this->assert_true($multi_cart_item['quantity'] === 3, "Cart should contain correct quantity");
            
            // Clean up cart
            WC()->cart->empty_cart();
            
            $this->test_results['cart_integration'] = 'PASSED';
            echo "✓ Cart Integration property test PASSED\n\n";
            
        } catch (Exception $e) {
            $this->test_results['cart_integration'] = 'FAILED: ' . $e->getMessage();
            echo "✗ Cart Integration property test FAILED: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * Test product validation functionality
     */
    public function test_product_validation_property() {
        echo "Testing Product Validation Property\n";
        
        try {
            // Create credits product
            $credits_product_id = $this->wc_integration->create_credits_product();
            
            // Test credits product identification
            $this->assert_true($this->wc_integration->is_credits_product($credits_product_id), 
                              "Credits product should be identified correctly");
            
            // Create regular product for comparison
            $regular_product = new WC_Product_Simple();
            $regular_product->set_name('Regular Product');
            $regular_product->set_price('15.00');
            $regular_product_id = $regular_product->save();
            $this->test_products[] = $regular_product_id;
            
            $this->assert_true(!$this->wc_integration->is_credits_product($regular_product_id), 
                              "Regular product should not be identified as credits product");
            
            // Test with invalid product IDs
            $this->assert_true(!$this->wc_integration->is_credits_product(0), 
                              "Invalid product ID should return false");
            $this->assert_true(!$this->wc_integration->is_credits_product(null), 
                              "Null product ID should return false");
            
            // Test order validation
            $customer_id = wp_create_user('testuser_validation_' . uniqid(), 'testpass123', 'testval_' . uniqid() . '@example.com');
            $this->test_users[] = $customer_id;
            
            // Order with credits product
            $credits_order = wc_create_order();
            $credits_order->set_customer_id($customer_id);
            $credits_order->add_product(wc_get_product($credits_product_id), 1);
            $credits_order->save();
            $this->test_orders[] = $credits_order->get_id();
            
            $this->assert_true($this->wc_integration->validate_credits_product($credits_order), 
                              "Order with credits product should validate as true");
            
            // Order without credits product
            $regular_order = wc_create_order();
            $regular_order->set_customer_id($customer_id);
            $regular_order->add_product(wc_get_product($regular_product_id), 1);
            $regular_order->save();
            $this->test_orders[] = $regular_order->get_id();
            
            $this->assert_true(!$this->wc_integration->validate_credits_product($regular_order), 
                              "Order without credits product should validate as false");
            
            // Test with null order
            $this->assert_true(!$this->wc_integration->validate_credits_product(null), 
                              "Null order should validate as false");
            
            $this->test_results['product_validation'] = 'PASSED';
            echo "✓ Product Validation property test PASSED\n\n";
            
        } catch (Exception $e) {
            $this->test_results['product_validation'] = 'FAILED: ' . $e->getMessage();
            echo "✗ Product Validation property test FAILED: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * Test purchase URL generation
     */
    public function test_purchase_url_generation_property() {
        echo "Testing Purchase URL Generation Property\n";
        
        try {
            $purchase_url = $this->wc_integration->get_credits_purchase_url();
            $this->assert_true(is_string($purchase_url) && !empty($purchase_url), 
                              "Purchase URL should be non-empty string");
            $this->assert_true(strpos($purchase_url, 'add-to-cart') !== false, 
                              "Purchase URL should contain add-to-cart parameter");
            
            // Verify URL contains correct product ID
            $credits_product_id = $this->wc_integration->get_credits_product_id();
            $this->assert_true(strpos($purchase_url, (string)$credits_product_id) !== false, 
                              "Purchase URL should contain credits product ID");
            
            // Test URL is valid format
            $parsed_url = parse_url($purchase_url);
            $this->assert_true(is_array($parsed_url), "Purchase URL should be valid URL format");
            $this->assert_true(isset($parsed_url['query']), "Purchase URL should have query parameters");
            
            parse_str($parsed_url['query'], $query_params);
            $this->assert_true(isset($query_params['add-to-cart']), 
                              "Purchase URL should have add-to-cart parameter");
            $this->assert_true($query_params['add-to-cart'] == $credits_product_id, 
                              "Purchase URL should have correct product ID in query");
            
            $this->test_results['purchase_url'] = 'PASSED';
            echo "✓ Purchase URL Generation property test PASSED\n\n";
            
        } catch (Exception $e) {
            $this->test_results['purchase_url'] = 'FAILED: ' . $e->getMessage();
            echo "✗ Purchase URL Generation property test FAILED: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * Test initialization functionality
     */
    public function test_initialization_property() {
        echo "Testing Initialization Property\n";
        
        try {
            // Clean up existing product first
            $existing_id = get_option('ai_virtual_fitting_credits_product_id', 0);
            if ($existing_id) {
                wp_delete_post($existing_id, true);
                delete_option('ai_virtual_fitting_credits_product_id');
            }
            
            // Test initialization
            $this->wc_integration->initialize();
            
            // Verify product was created
            $product_id = get_option('ai_virtual_fitting_credits_product_id', 0);
            $this->assert_true($product_id > 0, "Initialization should create credits product");
            
            $product = wc_get_product($product_id);
            $this->assert_true($product instanceof WC_Product, "Initialized product should be valid");
            $this->assert_true($product->get_name() === 'Virtual Fitting Credits - 20 Pack', 
                              "Initialized product should have correct name");
            
            // Test initialization idempotency
            $this->wc_integration->initialize();
            $second_product_id = get_option('ai_virtual_fitting_credits_product_id', 0);
            $this->assert_true($product_id === $second_product_id, 
                              "Second initialization should not create new product");
            
            $this->test_results['initialization'] = 'PASSED';
            echo "✓ Initialization property test PASSED\n\n";
            
        } catch (Exception $e) {
            $this->test_results['initialization'] = 'FAILED: ' . $e->getMessage();
            echo "✗ Initialization property test FAILED: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * Simple assertion helper
     */
    private function assert_true($condition, $message) {
        if (!$condition) {
            throw new Exception($message);
        }
    }
    
    /**
     * Clean up test data
     */
    private function cleanup_test_data() {
        echo "Cleaning up test data...\n";
        
        // Delete test users
        foreach ($this->test_users as $user_id) {
            if ($user_id && get_user_by('ID', $user_id)) {
                wp_delete_user($user_id);
            }
        }
        
        // Delete test orders
        foreach ($this->test_orders as $order_id) {
            if ($order_id) {
                $order = wc_get_order($order_id);
                if ($order) {
                    $order->delete(true);
                }
            }
        }
        
        // Delete test products (except credits product which should remain)
        foreach ($this->test_products as $product_id) {
            if ($product_id && $product_id !== $this->wc_integration->get_credits_product_id()) {
                wp_delete_post($product_id, true);
            }
        }
        
        echo "Test data cleanup completed.\n\n";
    }
    
    /**
     * Print test results summary
     */
    private function print_results() {
        echo "Test Results Summary\n";
        echo "===================\n";
        
        $passed = 0;
        $failed = 0;
        
        foreach ($this->test_results as $test => $result) {
            if ($result === 'PASSED') {
                echo "✓ {$test}: PASSED\n";
                $passed++;
            } else {
                echo "✗ {$test}: {$result}\n";
                $failed++;
            }
        }
        
        echo "\nTotal: " . ($passed + $failed) . " tests\n";
        echo "Passed: {$passed}\n";
        echo "Failed: {$failed}\n";
        
        if ($failed === 0) {
            echo "\n🎉 All WooCommerce Integration property tests PASSED!\n";
            return true;
        } else {
            echo "\n❌ Some WooCommerce Integration property tests FAILED!\n";
            return false;
        }
    }
}

// Run the tests
$test_runner = new SimpleWooCommerceIntegrationTestRunner();
$all_passed = $test_runner->run_all_tests();

// Exit with appropriate code
exit($all_passed ? 0 : 1);