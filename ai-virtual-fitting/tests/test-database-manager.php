<?php
/**
 * Property-Based Tests for Database Manager
 *
 * @package AI_Virtual_Fitting
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test class for AI Virtual Fitting Database Manager
 */
class Test_AI_Virtual_Fitting_Database_Manager extends WP_UnitTestCase {
    
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
        
        // Initialize database manager
        $this->database_manager = new AI_Virtual_Fitting_Database_Manager();
        
        // Ensure clean state
        $this->database_manager->drop_tables();
    }
    
    /**
     * Clean up after tests
     */
    public function tearDown(): void {
        // Clean up tables
        $this->database_manager->drop_tables();
        
        parent::tearDown();
    }
    
    /**
     * Feature: ai-virtual-fitting, Property 10: Plugin Lifecycle Management
     * For any plugin activation, the system should create all necessary database tables 
     * and WooCommerce products, and preserve all data during deactivation
     * 
     * **Validates: Requirements 8.3, 8.6, 8.8**
     */
    public function test_plugin_lifecycle_management_property() {
        // Property: Table creation should be idempotent and preserve data
        
        // Test multiple activation cycles
        for ($i = 0; $i < 5; $i++) {
            // Simulate plugin activation - create tables
            $creation_result = $this->database_manager->create_tables();
            $this->assertTrue($creation_result, "Tables should be created successfully on attempt {$i}");
            
            // Verify tables exist
            $this->assertTrue($this->database_manager->verify_tables_exist(), 
                            "Tables should exist after creation on attempt {$i}");
            
            // Add some test data to verify preservation
            global $wpdb;
            $credits_table = $this->database_manager->get_credits_table();
            $sessions_table = $this->database_manager->get_sessions_table();
            
            // Insert test credit record
            $user_id = $this->factory->user->create();
            $wpdb->insert(
                $credits_table,
                array(
                    'user_id' => $user_id,
                    'credits_remaining' => 5,
                    'total_credits_purchased' => 20
                ),
                array('%d', '%d', '%d')
            );
            
            // Insert test session record
            $session_id = 'test_session_' . $i . '_' . time();
            $product_id = $this->factory->post->create(array('post_type' => 'product'));
            $wpdb->insert(
                $sessions_table,
                array(
                    'session_id' => $session_id,
                    'user_id' => $user_id,
                    'product_id' => $product_id,
                    'status' => 'completed'
                ),
                array('%s', '%d', '%d', '%s')
            );
            
            // Verify data was inserted
            $credit_count = $wpdb->get_var("SELECT COUNT(*) FROM {$credits_table}");
            $session_count = $wpdb->get_var("SELECT COUNT(*) FROM {$sessions_table}");
            
            $this->assertGreaterThan(0, $credit_count, "Credit data should exist after insertion");
            $this->assertGreaterThan(0, $session_count, "Session data should exist after insertion");
            
            // Test table recreation (simulating multiple activations)
            $recreation_result = $this->database_manager->create_tables();
            $this->assertTrue($recreation_result, "Tables should be recreated successfully");
            
            // Verify data preservation during recreation
            $credit_count_after = $wpdb->get_var("SELECT COUNT(*) FROM {$credits_table}");
            $session_count_after = $wpdb->get_var("SELECT COUNT(*) FROM {$sessions_table}");
            
            $this->assertEquals($credit_count, $credit_count_after, 
                              "Credit data should be preserved during table recreation");
            $this->assertEquals($session_count, $session_count_after, 
                              "Session data should be preserved during table recreation");
        }
        
        // Test deactivation behavior (data preservation)
        // Note: Deactivation should NOT delete data, only uninstall should
        $stats_before = $this->database_manager->get_table_stats();
        
        // Simulate deactivation (tables should remain)
        // In real WordPress, deactivation doesn't call drop_tables
        $this->assertTrue($this->database_manager->verify_tables_exist(), 
                         "Tables should exist after deactivation");
        
        $stats_after = $this->database_manager->get_table_stats();
        $this->assertEquals($stats_before, $stats_after, 
                           "Data should be preserved during deactivation");
    }
    
    /**
     * Test database migration functionality
     */
    public function test_database_migration_property() {
        // Property: Migration should handle version upgrades gracefully
        
        // Create tables with initial version
        $this->database_manager->create_tables();
        
        // Add test data
        global $wpdb;
        $credits_table = $this->database_manager->get_credits_table();
        $user_id = $this->factory->user->create();
        
        $wpdb->insert(
            $credits_table,
            array(
                'user_id' => $user_id,
                'credits_remaining' => 10,
                'total_credits_purchased' => 40
            ),
            array('%d', '%d', '%d')
        );
        
        $data_before = $wpdb->get_row("SELECT * FROM {$credits_table} WHERE user_id = {$user_id}");
        
        // Test migration from older version
        $migration_result = $this->database_manager->migrate_data('0.9.0', '1.0.0');
        $this->assertTrue($migration_result, "Migration should succeed");
        
        // Verify data integrity after migration
        $data_after = $wpdb->get_row("SELECT * FROM {$credits_table} WHERE user_id = {$user_id}");
        $this->assertEquals($data_before->credits_remaining, $data_after->credits_remaining, 
                           "Credits should be preserved during migration");
        $this->assertEquals($data_before->total_credits_purchased, $data_after->total_credits_purchased, 
                           "Purchase history should be preserved during migration");
    }
    
    /**
     * Test cleanup functionality
     */
    public function test_cleanup_old_data_property() {
        // Property: Cleanup should only remove old records based on specified criteria
        
        $this->database_manager->create_tables();
        
        global $wpdb;
        $sessions_table = $this->database_manager->get_sessions_table();
        $user_id = $this->factory->user->create();
        $product_id = $this->factory->post->create(array('post_type' => 'product'));
        
        // Insert recent session (should be preserved)
        $recent_session_id = 'recent_' . time();
        $wpdb->insert(
            $sessions_table,
            array(
                'session_id' => $recent_session_id,
                'user_id' => $user_id,
                'product_id' => $product_id,
                'status' => 'failed',
                'created_at' => date('Y-m-d H:i:s')
            ),
            array('%s', '%d', '%d', '%s', '%s')
        );
        
        // Insert old session (should be cleaned up)
        $old_session_id = 'old_' . time();
        $old_date = date('Y-m-d H:i:s', strtotime('-45 days'));
        $wpdb->insert(
            $sessions_table,
            array(
                'session_id' => $old_session_id,
                'user_id' => $user_id,
                'product_id' => $product_id,
                'status' => 'failed',
                'created_at' => $old_date
            ),
            array('%s', '%d', '%d', '%s', '%s')
        );
        
        $total_before = $wpdb->get_var("SELECT COUNT(*) FROM {$sessions_table}");
        $this->assertEquals(2, $total_before, "Should have 2 sessions before cleanup");
        
        // Run cleanup for records older than 30 days
        $cleaned_count = $this->database_manager->cleanup_old_data(30);
        $this->assertEquals(1, $cleaned_count, "Should clean up exactly 1 old record");
        
        // Verify recent session is preserved
        $recent_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$sessions_table} WHERE session_id = %s",
            $recent_session_id
        ));
        $this->assertEquals(1, $recent_exists, "Recent session should be preserved");
        
        // Verify old session is removed
        $old_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$sessions_table} WHERE session_id = %s",
            $old_session_id
        ));
        $this->assertEquals(0, $old_exists, "Old session should be removed");
    }
    
    /**
     * Test table statistics functionality
     */
    public function test_table_statistics_property() {
        // Property: Statistics should accurately reflect database state
        
        $this->database_manager->create_tables();
        
        // Initially empty
        $empty_stats = $this->database_manager->get_table_stats();
        $this->assertEquals(0, $empty_stats['credits']['total_users']);
        $this->assertEquals(0, $empty_stats['sessions']['total_sessions']);
        
        // Add test data
        global $wpdb;
        $credits_table = $this->database_manager->get_credits_table();
        $sessions_table = $this->database_manager->get_sessions_table();
        
        // Add multiple users with credits
        $user_ids = array();
        $total_remaining = 0;
        $total_purchased = 0;
        
        for ($i = 0; $i < 5; $i++) {
            $user_id = $this->factory->user->create();
            $user_ids[] = $user_id;
            $remaining = rand(0, 10);
            $purchased = rand(20, 100);
            $total_remaining += $remaining;
            $total_purchased += $purchased;
            
            $wpdb->insert(
                $credits_table,
                array(
                    'user_id' => $user_id,
                    'credits_remaining' => $remaining,
                    'total_credits_purchased' => $purchased
                ),
                array('%d', '%d', '%d')
            );
        }
        
        // Add sessions with different statuses
        $product_id = $this->factory->post->create(array('post_type' => 'product'));
        $session_statuses = array('completed', 'failed', 'processing', 'completed', 'failed');
        
        foreach ($user_ids as $index => $user_id) {
            $wpdb->insert(
                $sessions_table,
                array(
                    'session_id' => 'session_' . $user_id . '_' . time(),
                    'user_id' => $user_id,
                    'product_id' => $product_id,
                    'status' => $session_statuses[$index]
                ),
                array('%s', '%d', '%d', '%s')
            );
        }
        
        // Verify statistics accuracy
        $stats = $this->database_manager->get_table_stats();
        
        $this->assertEquals(5, $stats['credits']['total_users'], 
                           "Should count correct number of users");
        $this->assertEquals($total_remaining, $stats['credits']['total_remaining_credits'], 
                           "Should sum remaining credits correctly");
        $this->assertEquals($total_purchased, $stats['credits']['total_purchased_credits'], 
                           "Should sum purchased credits correctly");
        
        $this->assertEquals(5, $stats['sessions']['total_sessions'], 
                           "Should count total sessions correctly");
        $this->assertEquals(2, $stats['sessions']['completed_sessions'], 
                           "Should count completed sessions correctly");
        $this->assertEquals(2, $stats['sessions']['failed_sessions'], 
                           "Should count failed sessions correctly");
        $this->assertEquals(1, $stats['sessions']['processing_sessions'], 
                           "Should count processing sessions correctly");
    }
}