<?php
/**
 * WordPress-based test runner for AI Virtual Fitting Plugin
 * This runs tests within the WordPress environment
 *
 * @package AI_Virtual_Fitting
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

// Load the plugin classes
require_once('/var/www/html/wp-content/plugins/ai-virtual-fitting/includes/class-database-manager.php');
require_once('/var/www/html/wp-content/plugins/ai-virtual-fitting/includes/class-credit-manager.php');
require_once('/var/www/html/wp-content/plugins/ai-virtual-fitting/includes/class-woocommerce-integration.php');

/**
 * WordPress Property Test Runner
 */
class WordPressPropertyTestRunner {
    
    private $database_manager;
    private $credit_manager;
    private $woocommerce_integration;
    private $test_results = array();
    
    public function __construct() {
        $this->database_manager = new AI_Virtual_Fitting_Database_Manager();
        $this->credit_manager = new AI_Virtual_Fitting_Credit_Manager();
        $this->woocommerce_integration = new AI_Virtual_Fitting_WooCommerce_Integration();
    }
    
    public function run_all_tests() {
        echo "Running AI Virtual Fitting Core Systems Tests\n";
        echo "============================================\n\n";
        
        $this->test_database_system();
        $this->test_credit_system();
        $this->test_woocommerce_system();
        
        $this->print_results();
        return count(array_filter($this->test_results, function($result) { return $result === 'PASSED'; })) === count($this->test_results);
    }
    
    /**
     * Test Database System
     */
    public function test_database_system() {
        echo "Testing Database System\n";
        echo "-----------------------\n";
        
        try {
            // Test table creation
            $result = $this->database_manager->create_tables();
            $this->assert_true($result, "Tables should be created successfully");
            
            // Test table verification
            $tables_exist = $this->database_manager->verify_tables_exist();
            $this->assert_true($tables_exist, "Tables should exist after creation");
            
            // Test table statistics
            $stats = $this->database_manager->get_table_stats();
            $this->assert_true(is_array($stats), "Statistics should be an array");
            $this->assert_true(isset($stats['credits']), "Statistics should include credits data");
            $this->assert_true(isset($stats['sessions']), "Statistics should include sessions data");
            
            $this->test_results['database'] = 'PASSED';
            echo "✓ Database System test PASSED\n\n";
            
        } catch (Exception $e) {
            $this->test_results['database'] = 'FAILED: ' . $e->getMessage();
            echo "✗ Database System test FAILED: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * Test Credit System
     */
    public function test_credit_system() {
        echo "Testing Credit System\n";
        echo "--------------------\n";
        
        try {
            // Create a test user
            $test_user_id = $this->create_test_user();
            
            // Test initial credit grant
            $initial_result = $this->credit_manager->grant_initial_credits($test_user_id);
            $this->assert_true($initial_result, "Initial credits should be granted");
            
            // Test credit retrieval
            $credits = $this->credit_manager->get_customer_credits($test_user_id);
            $this->assert_true($credits >= 2, "User should have at least 2 initial credits");
            
            // Test credit deduction
            $deduct_result = $this->credit_manager->deduct_credit($test_user_id);
            $this->assert_true($deduct_result, "Credit deduction should succeed");
            
            // Verify credit was deducted
            $credits_after = $this->credit_manager->get_customer_credits($test_user_id);
            $this->assert_true($credits_after === ($credits - 1), "Credits should be reduced by 1");
            
            // Test adding credits
            $add_result = $this->credit_manager->add_credits($test_user_id, 10);
            $this->assert_true($add_result, "Adding credits should succeed");
            
            // Verify credits were added
            $credits_final = $this->credit_manager->get_customer_credits($test_user_id);
            $this->assert_true($credits_final === ($credits_after + 10), "Credits should be increased by 10");
            
            // Clean up test user
            $this->cleanup_test_user($test_user_id);
            
            $this->test_results['credit'] = 'PASSED';
            echo "✓ Credit System test PASSED\n\n";
            
        } catch (Exception $e) {
            $this->test_results['credit'] = 'FAILED: ' . $e->getMessage();
            echo "✗ Credit System test FAILED: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * Test WooCommerce System
     */
    public function test_woocommerce_system() {
        echo "Testing WooCommerce System\n";
        echo "-------------------------\n";
        
        try {
            // Test credits product creation
            $product_id = $this->woocommerce_integration->create_credits_product();
            $this->assert_true($product_id > 0, "Credits product should be created with valid ID");
            
            // Test product exists and has correct properties
            $product = wc_get_product($product_id);
            $this->assert_true($product !== false, "Product should exist");
            $this->assert_true($product->is_virtual(), "Product should be virtual");
            $this->assert_true($product->get_regular_price() == '10.00', "Product should have correct price");
            
            // Test product metadata
            $credits_amount = $product->get_meta('_virtual_fitting_credits');
            $this->assert_true($credits_amount == 20, "Product should have correct credits amount");
            
            $this->test_results['woocommerce'] = 'PASSED';
            echo "✓ WooCommerce System test PASSED\n\n";
            
        } catch (Exception $e) {
            $this->test_results['woocommerce'] = 'FAILED: ' . $e->getMessage();
            echo "✗ WooCommerce System test FAILED: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * Create a test user
     */
    private function create_test_user() {
        $user_id = wp_create_user('testuser_' . time(), 'testpass123', 'test@example.com');
        if (is_wp_error($user_id)) {
            throw new Exception('Failed to create test user: ' . $user_id->get_error_message());
        }
        return $user_id;
    }
    
    /**
     * Clean up test user
     */
    private function cleanup_test_user($user_id) {
        global $wpdb;
        
        // Remove user data from our tables
        $wpdb->delete($this->database_manager->get_credits_table(), array('user_id' => $user_id));
        
        // Remove WordPress user
        wp_delete_user($user_id);
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
            echo "\n🎉 All core systems are working properly!\n";
        } else {
            echo "\n❌ Some core systems have issues!\n";
        }
    }
}

// Run the tests
$test_runner = new WordPressPropertyTestRunner();
$all_passed = $test_runner->run_all_tests();

// Exit with appropriate code
exit($all_passed ? 0 : 1);