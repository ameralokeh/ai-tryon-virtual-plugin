<?php
/**
 * Property-Based Tests for WooCommerce Integration
 *
 * @package AI_Virtual_Fitting
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test class for AI Virtual Fitting WooCommerce Integration
 */
class Test_AI_Virtual_Fitting_WooCommerce_Integration extends WP_UnitTestCase {
    
    /**
     * WooCommerce Integration instance
     *
     * @var AI_Virtual_Fitting_WooCommerce_Integration
     */
    private $wc_integration;
    
    /**
     * Credit Manager instance
     *
     * @var AI_Virtual_Fitting_Credit_Manager
     */
    private $credit_manager;
    
    /**
     * Database Manager instance
     *
     * @var AI_Virtual_Fitting_Database_Manager
     */
    private $database_manager;
    
    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Initialize database manager and create tables
        $this->database_manager = new AI_Virtual_Fitting_Database_Manager();
        $this->database_manager->create_tables();
        
        // Initialize credit manager
        $this->credit_manager = new AI_Virtual_Fitting_Credit_Manager();
        
        // Initialize WooCommerce integration
        $this->wc_integration = new AI_Virtual_Fitting_WooCommerce_Integration();
        
        // Set default options for testing
        update_option('ai_virtual_fitting_initial_credits', 2);
        update_option('ai_virtual_fitting_enable_logging', false); // Disable logging for tests
        
        // Ensure WooCommerce is available for testing
        if (!class_exists('WC_Product_Simple')) {
            $this->markTestSkipped('WooCommerce is not available for testing');
        }
    }
    
    /**
     * Clean up after tests
     */
    public function tearDown(): void {
        // Clean up created products
        $credits_product_id = get_option('ai_virtual_fitting_credits_product_id', 0);
        if ($credits_product_id) {
            wp_delete_post($credits_product_id, true);
            delete_option('ai_virtual_fitting_credits_product_id');
        }
        
        // Clean up tables
        $this->database_manager->drop_tables();
        
        // Clean up options
        delete_option('ai_virtual_fitting_initial_credits');
        delete_option('ai_virtual_fitting_enable_logging');
        
        parent::tearDown();
    }
    
    /**
     * Feature: ai-virtual-fitting, Property 7: WooCommerce Integration Consistency
     * For any completed WooCommerce order containing virtual fitting credits, 
     * the system should automatically add exactly 20 credits to the customer's account 
     * and send confirmation
     * 
     * **Validates: Requirements 5.2, 5.3, 5.4, 5.6, 5.7**
     */
    public function test_woocommerce_integration_consistency_property() {
        // Property: WooCommerce integration should handle all order scenarios consistently
        
        // Test credits product creation (Requirement 5.2)
        $product_id = $this->wc_integration->create_credits_product();
        $this->assertIsInt($product_id, "Credits product should be created successfully");
        $this->assertGreaterThan(0, $product_id, "Product ID should be positive integer");
        
        // Verify product properties
        $product = wc_get_product($product_id);
        $this->assertInstanceOf('WC_Product', $product, "Created product should be valid WooCommerce product");
        $this->assertEquals('Virtual Fitting Credits - 20 Pack', $product->get_name(), 
                           "Product should have correct name");
        $this->assertEquals('10.00', $product->get_price(), "Product should have correct price");
        $this->assertTrue($product->is_virtual(), "Product should be virtual");
        $this->assertEquals('hidden', $product->get_catalog_visibility(), 
                           "Product should be hidden from catalog");
        $this->assertEquals('20', $product->get_meta('_virtual_fitting_credits'), 
                           "Product should have correct credits metadata");
        $this->assertEquals('yes', $product->get_meta('_virtual_fitting_product'), 
                           "Product should be marked as credits product");
        
        // Test product creation idempotency
        $second_product_id = $this->wc_integration->create_credits_product();
        $this->assertEquals($product_id, $second_product_id, 
                           "Second creation should return same product ID");
        
        // Test multiple order scenarios
        $test_scenarios = array(
            array('quantity' => 1, 'expected_credits' => 20, 'description' => 'single credit pack'),
            array('quantity' => 2, 'expected_credits' => 40, 'description' => 'two credit packs'),
            array('quantity' => 3, 'expected_credits' => 60, 'description' => 'three credit packs'),
            array('quantity' => 5, 'expected_credits' => 100, 'description' => 'five credit packs'),
        );
        
        foreach ($test_scenarios as $scenario) {
            $customer_id = $this->factory->user->create();
            
            // Get initial credits (should be 2 for new users)
            $initial_credits = $this->credit_manager->get_customer_credits($customer_id);
            $this->assertEquals(2, $initial_credits, 
                              "New customer should have 2 initial credits");
            
            // Create WooCommerce order with credits product (Requirement 5.3)
            $order = wc_create_order();
            $order->set_customer_id($customer_id);
            $order->add_product($product, $scenario['quantity']);
            $order->set_status('completed');
            $order->save();
            
            // Process the order (Requirements 5.4, 5.6, 5.7)
            $this->wc_integration->handle_order_completed($order->get_id());
            
            // Verify credits were added correctly
            $final_credits = $this->credit_manager->get_customer_credits($customer_id);
            $expected_total = $initial_credits + $scenario['expected_credits'];
            $this->assertEquals($expected_total, $final_credits, 
                              "Customer should have {$expected_total} credits after purchasing {$scenario['description']}");
            
            // Verify order was marked as processed
            $processed = $order->get_meta('_virtual_fitting_credits_processed');
            $this->assertEquals('yes', $processed, 
                              "Order should be marked as processed for {$scenario['description']}");
            
            // Verify order note was added
            $notes = wc_get_order_notes(array('order_id' => $order->get_id()));
            $credit_note_found = false;
            foreach ($notes as $note) {
                if (strpos($note->content, 'Virtual Fitting Credits') !== false) {
                    $credit_note_found = true;
                    $this->assertStringContainsString((string)$scenario['expected_credits'], $note->content, 
                                                    "Order note should mention correct credit amount");
                    break;
                }
            }
            $this->assertTrue($credit_note_found, 
                            "Order should have credit addition note for {$scenario['description']}");
            
            // Test order processing idempotency - processing again should not add more credits
            $this->wc_integration->handle_order_completed($order->get_id());
            $credits_after_reprocess = $this->credit_manager->get_customer_credits($customer_id);
            $this->assertEquals($expected_total, $credits_after_reprocess, 
                              "Credits should not be added again when reprocessing order for {$scenario['description']}");
            
            // Clean up order
            $order->delete(true);
        }
        
        // Test mixed orders (credits + other products)
        $mixed_customer = $this->factory->user->create();
        $initial_credits = $this->credit_manager->get_customer_credits($mixed_customer);
        
        // Create a regular product for testing
        $regular_product = new WC_Product_Simple();
        $regular_product->set_name('Regular Product');
        $regular_product->set_price('25.00');
        $regular_product_id = $regular_product->save();
        
        // Create mixed order
        $mixed_order = wc_create_order();
        $mixed_order->set_customer_id($mixed_customer);
        $mixed_order->add_product($product, 2); // 2 credit packs = 40 credits
        $mixed_order->add_product(wc_get_product($regular_product_id), 1); // Regular product
        $mixed_order->set_status('completed');
        $mixed_order->save();
        
        // Process mixed order
        $this->wc_integration->handle_order_completed($mixed_order->get_id());
        
        // Verify only credits were processed
        $final_mixed_credits = $this->credit_manager->get_customer_credits($mixed_customer);
        $this->assertEquals($initial_credits + 40, $final_mixed_credits, 
                           "Mixed order should add correct credits amount");
        
        // Clean up
        $mixed_order->delete(true);
        wp_delete_post($regular_product_id, true);
        
        // Test edge cases
        
        // Test order without customer ID
        $no_customer_order = wc_create_order();
        $no_customer_order->add_product($product, 1);
        $no_customer_order->set_status('completed');
        $no_customer_order->save();
        
        // Should not crash or add credits
        $this->wc_integration->handle_order_completed($no_customer_order->get_id());
        $processed = $no_customer_order->get_meta('_virtual_fitting_credits_processed');
        $this->assertEmpty($processed, "Order without customer should not be processed");
        
        $no_customer_order->delete(true);
        
        // Test invalid order ID
        $this->wc_integration->handle_order_completed(0);
        $this->wc_integration->handle_order_completed(null);
        $this->wc_integration->handle_order_completed('invalid');
        // Should not crash (no assertions needed, just ensuring no fatal errors)
        
        // Test order with zero quantity
        $zero_customer = $this->factory->user->create();
        $zero_initial = $this->credit_manager->get_customer_credits($zero_customer);
        
        $zero_order = wc_create_order();
        $zero_order->set_customer_id($zero_customer);
        $zero_order->add_product($product, 0); // Zero quantity
        $zero_order->set_status('completed');
        $zero_order->save();
        
        $this->wc_integration->handle_order_completed($zero_order->get_id());
        
        $zero_final = $this->credit_manager->get_customer_credits($zero_customer);
        $this->assertEquals($zero_initial, $zero_final, 
                           "Zero quantity order should not add credits");
        
        $zero_order->delete(true);
    }
    
    /**
     * Test cart integration functionality
     */
    public function test_cart_integration_property() {
        // Property: Cart integration should work consistently for all scenarios
        
        // Test adding credits to cart
        $cart_item_key = $this->wc_integration->add_credits_to_cart(1);
        $this->assertIsString($cart_item_key, "Adding credits to cart should return cart item key");
        $this->assertNotEmpty($cart_item_key, "Cart item key should not be empty");
        
        // Verify cart contents
        $cart_contents = WC()->cart->get_cart();
        $this->assertCount(1, $cart_contents, "Cart should contain one item");
        
        $cart_item = reset($cart_contents);
        $this->assertEquals(1, $cart_item['quantity'], "Cart item should have correct quantity");
        
        $product_in_cart = $cart_item['data'];
        $this->assertEquals('Virtual Fitting Credits - 20 Pack', $product_in_cart->get_name(), 
                           "Cart should contain credits product");
        
        // Test adding multiple quantities
        WC()->cart->empty_cart();
        $multi_cart_key = $this->wc_integration->add_credits_to_cart(3);
        $this->assertIsString($multi_cart_key, "Adding multiple credits to cart should work");
        
        $multi_cart_contents = WC()->cart->get_cart();
        $multi_cart_item = reset($multi_cart_contents);
        $this->assertEquals(3, $multi_cart_item['quantity'], "Cart should contain correct quantity");
        
        // Clean up cart
        WC()->cart->empty_cart();
    }
    
    /**
     * Test product validation functionality
     */
    public function test_product_validation_property() {
        // Property: Product validation should correctly identify credits products
        
        // Create credits product
        $credits_product_id = $this->wc_integration->create_credits_product();
        
        // Test credits product identification
        $this->assertTrue($this->wc_integration->is_credits_product($credits_product_id), 
                         "Credits product should be identified correctly");
        
        // Create regular product for comparison
        $regular_product = new WC_Product_Simple();
        $regular_product->set_name('Regular Product');
        $regular_product->set_price('15.00');
        $regular_product_id = $regular_product->save();
        
        $this->assertFalse($this->wc_integration->is_credits_product($regular_product_id), 
                          "Regular product should not be identified as credits product");
        
        // Test with invalid product ID
        $this->assertFalse($this->wc_integration->is_credits_product(0), 
                          "Invalid product ID should return false");
        $this->assertFalse($this->wc_integration->is_credits_product(null), 
                          "Null product ID should return false");
        $this->assertFalse($this->wc_integration->is_credits_product('invalid'), 
                          "Invalid product ID type should return false");
        
        // Test order validation
        $customer_id = $this->factory->user->create();
        
        // Order with credits product
        $credits_order = wc_create_order();
        $credits_order->set_customer_id($customer_id);
        $credits_order->add_product(wc_get_product($credits_product_id), 1);
        $credits_order->save();
        
        $this->assertTrue($this->wc_integration->validate_credits_product($credits_order), 
                         "Order with credits product should validate as true");
        
        // Order without credits product
        $regular_order = wc_create_order();
        $regular_order->set_customer_id($customer_id);
        $regular_order->add_product(wc_get_product($regular_product_id), 1);
        $regular_order->save();
        
        $this->assertFalse($this->wc_integration->validate_credits_product($regular_order), 
                          "Order without credits product should validate as false");
        
        // Mixed order
        $mixed_order = wc_create_order();
        $mixed_order->set_customer_id($customer_id);
        $mixed_order->add_product(wc_get_product($credits_product_id), 1);
        $mixed_order->add_product(wc_get_product($regular_product_id), 1);
        $mixed_order->save();
        
        $this->assertTrue($this->wc_integration->validate_credits_product($mixed_order), 
                         "Mixed order with credits product should validate as true");
        
        // Test with null order
        $this->assertFalse($this->wc_integration->validate_credits_product(null), 
                          "Null order should validate as false");
        
        // Clean up
        $credits_order->delete(true);
        $regular_order->delete(true);
        $mixed_order->delete(true);
        wp_delete_post($regular_product_id, true);
    }
    
    /**
     * Test purchase URL generation
     */
    public function test_purchase_url_generation_property() {
        // Property: Purchase URL should always be valid and functional
        
        $purchase_url = $this->wc_integration->get_credits_purchase_url();
        $this->assertIsString($purchase_url, "Purchase URL should be string");
        $this->assertNotEmpty($purchase_url, "Purchase URL should not be empty");
        $this->assertStringContainsString('add-to-cart', $purchase_url, 
                                        "Purchase URL should contain add-to-cart parameter");
        
        // Verify URL contains correct product ID
        $credits_product_id = $this->wc_integration->get_credits_product_id();
        $this->assertStringContainsString((string)$credits_product_id, $purchase_url, 
                                        "Purchase URL should contain credits product ID");
        
        // Test URL is valid format
        $parsed_url = parse_url($purchase_url);
        $this->assertIsArray($parsed_url, "Purchase URL should be valid URL format");
        $this->assertArrayHasKey('query', $parsed_url, "Purchase URL should have query parameters");
        
        parse_str($parsed_url['query'], $query_params);
        $this->assertArrayHasKey('add-to-cart', $query_params, 
                                "Purchase URL should have add-to-cart parameter");
        $this->assertEquals($credits_product_id, $query_params['add-to-cart'], 
                           "Purchase URL should have correct product ID in query");
    }
    
    /**
     * Test initialization functionality
     */
    public function test_initialization_property() {
        // Property: Initialization should set up all required components
        
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
        $this->assertGreaterThan(0, $product_id, "Initialization should create credits product");
        
        $product = wc_get_product($product_id);
        $this->assertInstanceOf('WC_Product', $product, "Initialized product should be valid");
        $this->assertEquals('Virtual Fitting Credits - 20 Pack', $product->get_name(), 
                           "Initialized product should have correct name");
        
        // Test initialization idempotency
        $this->wc_integration->initialize();
        $second_product_id = get_option('ai_virtual_fitting_credits_product_id', 0);
        $this->assertEquals($product_id, $second_product_id, 
                           "Second initialization should not create new product");
    }
}