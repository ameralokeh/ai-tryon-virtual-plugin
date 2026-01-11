<?php
/**
 * Property-Based Tests for Credit Manager
 *
 * @package AI_Virtual_Fitting
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test class for AI Virtual Fitting Credit Manager
 */
class Test_AI_Virtual_Fitting_Credit_Manager extends WP_UnitTestCase {
    
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
        
        // Set default options for testing
        update_option('ai_virtual_fitting_initial_credits', 2);
        update_option('ai_virtual_fitting_enable_logging', false); // Disable logging for tests
    }
    
    /**
     * Clean up after tests
     */
    public function tearDown(): void {
        // Clean up tables
        $this->database_manager->drop_tables();
        
        // Clean up options
        delete_option('ai_virtual_fitting_initial_credits');
        delete_option('ai_virtual_fitting_enable_logging');
        
        parent::tearDown();
    }
    
    /**
     * Feature: ai-virtual-fitting, Property 5: Credit Lifecycle Management
     * For any customer account, new users should receive exactly 2 free credits, 
     * successful fittings should deduct exactly 1 credit after showing results, 
     * and failed fittings should never deduct credits
     * 
     * **Validates: Requirements 4.1, 4.2, 4.5, 4.6**
     */
    public function test_credit_lifecycle_management_property() {
        // Property: Credit lifecycle should follow exact rules for all users
        
        // Test with multiple users to verify universal behavior
        $test_users = array();
        for ($i = 0; $i < 10; $i++) {
            $test_users[] = $this->factory->user->create();
        }
        
        foreach ($test_users as $user_id) {
            // Test initial credit allocation (Requirement 4.1)
            $initial_credits = $this->credit_manager->get_customer_credits($user_id);
            $this->assertEquals(2, $initial_credits, 
                              "New user {$user_id} should receive exactly 2 initial credits");
            
            // Verify initial credits are granted only once
            $credits_after_second_call = $this->credit_manager->get_customer_credits($user_id);
            $this->assertEquals(2, $credits_after_second_call, 
                              "User {$user_id} should still have 2 credits after second call");
            
            // Test successful credit deduction (Requirements 4.2, 4.5)
            $deduction_result = $this->credit_manager->deduct_credit($user_id);
            $this->assertTrue($deduction_result, 
                            "Credit deduction should succeed for user {$user_id} with available credits");
            
            $credits_after_deduction = $this->credit_manager->get_customer_credits($user_id);
            $this->assertEquals(1, $credits_after_deduction, 
                              "User {$user_id} should have exactly 1 credit after deduction");
            
            // Test second deduction
            $second_deduction = $this->credit_manager->deduct_credit($user_id);
            $this->assertTrue($second_deduction, 
                            "Second credit deduction should succeed for user {$user_id}");
            
            $credits_after_second_deduction = $this->credit_manager->get_customer_credits($user_id);
            $this->assertEquals(0, $credits_after_second_deduction, 
                              "User {$user_id} should have 0 credits after second deduction");
            
            // Test deduction failure when no credits remain (Requirement 4.6)
            $failed_deduction = $this->credit_manager->deduct_credit($user_id);
            $this->assertFalse($failed_deduction, 
                             "Credit deduction should fail for user {$user_id} with no credits");
            
            $credits_after_failed_deduction = $this->credit_manager->get_customer_credits($user_id);
            $this->assertEquals(0, $credits_after_failed_deduction, 
                              "User {$user_id} should still have 0 credits after failed deduction");
            
            // Test credit addition
            $addition_result = $this->credit_manager->add_credits($user_id, 5);
            $this->assertTrue($addition_result, 
                            "Credit addition should succeed for user {$user_id}");
            
            $credits_after_addition = $this->credit_manager->get_customer_credits($user_id);
            $this->assertEquals(5, $credits_after_addition, 
                              "User {$user_id} should have 5 credits after addition");
            
            // Test credit history tracking
            $history = $this->credit_manager->get_customer_credit_history($user_id);
            $this->assertEquals(5, $history['credits_remaining'], 
                              "History should show correct remaining credits for user {$user_id}");
            $this->assertEquals(5, $history['total_credits_purchased'], 
                              "History should show correct purchased credits for user {$user_id}");
            $this->assertEquals(2, $history['total_credits_used'], 
                              "History should show correct used credits for user {$user_id}");
        }
        
        // Test edge cases with invalid user IDs
        $invalid_user_credits = $this->credit_manager->get_customer_credits(0);
        $this->assertEquals(0, $invalid_user_credits, 
                           "Invalid user ID should return 0 credits");
        
        $invalid_deduction = $this->credit_manager->deduct_credit(null);
        $this->assertFalse($invalid_deduction, 
                          "Credit deduction should fail for null user ID");
        
        $invalid_addition = $this->credit_manager->add_credits('invalid', 5);
        $this->assertFalse($invalid_addition, 
                          "Credit addition should fail for invalid user ID");
        
        // Test negative credit addition
        $negative_addition = $this->credit_manager->add_credits($test_users[0], -5);
        $this->assertFalse($negative_addition, 
                          "Negative credit addition should fail");
        
        // Test zero credit addition
        $zero_addition = $this->credit_manager->add_credits($test_users[0], 0);
        $this->assertFalse($zero_addition, 
                          "Zero credit addition should fail");
    }
    
    /**
     * Test sufficient credits checking
     */
    public function test_sufficient_credits_property() {
        // Property: Credit sufficiency check should be accurate for all scenarios
        
        $user_id = $this->factory->user->create();
        
        // User starts with 2 credits
        $this->assertTrue($this->credit_manager->has_sufficient_credits($user_id, 1), 
                         "User should have sufficient credits for 1 credit requirement");
        $this->assertTrue($this->credit_manager->has_sufficient_credits($user_id, 2), 
                         "User should have sufficient credits for 2 credit requirement");
        $this->assertFalse($this->credit_manager->has_sufficient_credits($user_id, 3), 
                          "User should not have sufficient credits for 3 credit requirement");
        
        // After deducting 1 credit
        $this->credit_manager->deduct_credit($user_id);
        $this->assertTrue($this->credit_manager->has_sufficient_credits($user_id, 1), 
                         "User should have sufficient credits for 1 credit requirement after deduction");
        $this->assertFalse($this->credit_manager->has_sufficient_credits($user_id, 2), 
                          "User should not have sufficient credits for 2 credit requirement after deduction");
        
        // After deducting all credits
        $this->credit_manager->deduct_credit($user_id);
        $this->assertFalse($this->credit_manager->has_sufficient_credits($user_id, 1), 
                          "User should not have sufficient credits for any requirement with 0 credits");
    }
    
    /**
     * Test system-wide credit statistics
     */
    public function test_system_credit_statistics_property() {
        // Property: System statistics should accurately reflect all user credit data
        
        $users = array();
        $expected_total_remaining = 0;
        $expected_total_purchased = 0;
        
        // Create multiple users with different credit scenarios
        for ($i = 0; $i < 5; $i++) {
            $user_id = $this->factory->user->create();
            $users[] = $user_id;
            
            // Each user starts with 2 initial credits
            $initial_credits = $this->credit_manager->get_customer_credits($user_id);
            $expected_total_remaining += $initial_credits;
            
            // Add purchased credits to some users
            if ($i % 2 === 0) {
                $purchased_amount = ($i + 1) * 10;
                $this->credit_manager->add_credits($user_id, $purchased_amount);
                $expected_total_remaining += $purchased_amount;
                $expected_total_purchased += $purchased_amount;
            }
            
            // Deduct some credits from some users
            if ($i % 3 === 0) {
                $this->credit_manager->deduct_credit($user_id);
                $expected_total_remaining -= 1;
            }
        }
        
        $stats = $this->credit_manager->get_system_credit_stats();
        
        $this->assertEquals(5, $stats['total_users'], 
                           "System should count correct number of users");
        $this->assertEquals($expected_total_remaining, $stats['total_remaining_credits'], 
                           "System should sum remaining credits correctly");
        $this->assertEquals($expected_total_purchased, $stats['total_purchased_credits'], 
                           "System should sum purchased credits correctly");
        $this->assertGreaterThan(0, $stats['total_used_credits'], 
                                "System should calculate used credits correctly");
        $this->assertTrue(is_numeric($stats['avg_remaining_credits']), 
                         "Average remaining credits should be numeric");
    }
    
    /**
     * Test existing user migration
     */
    public function test_existing_user_migration_property() {
        // Property: Migration should grant initial credits to all existing users without credits
        
        // Create users before credit system is initialized
        $existing_users = array();
        for ($i = 0; $i < 3; $i++) {
            $existing_users[] = $this->factory->user->create();
        }
        
        // Simulate migration
        $migrated_count = $this->credit_manager->migrate_existing_users();
        $this->assertEquals(3, $migrated_count, 
                           "Should migrate exactly 3 existing users");
        
        // Verify all users now have initial credits
        foreach ($existing_users as $user_id) {
            $credits = $this->credit_manager->get_customer_credits($user_id);
            $this->assertEquals(2, $credits, 
                              "Migrated user {$user_id} should have initial credits");
        }
        
        // Test migration idempotency - running again should not add more credits
        $second_migration = $this->credit_manager->migrate_existing_users();
        $this->assertEquals(0, $second_migration, 
                           "Second migration should not migrate any users");
        
        // Verify credits remain the same
        foreach ($existing_users as $user_id) {
            $credits = $this->credit_manager->get_customer_credits($user_id);
            $this->assertEquals(2, $credits, 
                              "User {$user_id} should still have only initial credits after second migration");
        }
    }
}
    /**
     * Feature: ai-virtual-fitting, Property 6: Credit-Based Access Control
     * For any customer with zero credits, the system should prevent virtual fitting access 
     * and display purchase options, while customers with credits should have unrestricted access
     * 
     * **Validates: Requirements 4.3, 5.1**
     */
    public function test_credit_based_access_control_property() {
        // Property: Access control should be consistent based on credit availability
        
        // Test with multiple users in different credit states
        $test_scenarios = array(
            array('credits' => 0, 'should_have_access' => false, 'description' => 'zero credits'),
            array('credits' => 1, 'should_have_access' => true, 'description' => 'one credit'),
            array('credits' => 2, 'should_have_access' => true, 'description' => 'two credits'),
            array('credits' => 5, 'should_have_access' => true, 'description' => 'five credits'),
            array('credits' => 10, 'should_have_access' => true, 'description' => 'ten credits'),
        );
        
        foreach ($test_scenarios as $scenario) {
            $user_id = $this->factory->user->create();
            
            // Set up user with specific credit amount
            if ($scenario['credits'] === 0) {
                // Create user and then deduct all credits
                $this->credit_manager->get_customer_credits($user_id); // Creates entry with 2 credits
                $this->credit_manager->deduct_credit($user_id);
                $this->credit_manager->deduct_credit($user_id);
            } else {
                // Create user and adjust credits to desired amount
                $this->credit_manager->get_customer_credits($user_id); // Creates entry with 2 credits
                if ($scenario['credits'] > 2) {
                    $this->credit_manager->add_credits($user_id, $scenario['credits'] - 2);
                } elseif ($scenario['credits'] < 2) {
                    $credits_to_deduct = 2 - $scenario['credits'];
                    for ($i = 0; $i < $credits_to_deduct; $i++) {
                        $this->credit_manager->deduct_credit($user_id);
                    }
                }
            }
            
            // Verify credit amount is correct
            $actual_credits = $this->credit_manager->get_customer_credits($user_id);
            $this->assertEquals($scenario['credits'], $actual_credits, 
                              "User should have exactly {$scenario['credits']} credits for {$scenario['description']} scenario");
            
            // Test access control based on credit availability (Requirement 4.3)
            $has_access = $this->credit_manager->has_sufficient_credits($user_id, 1);
            $this->assertEquals($scenario['should_have_access'], $has_access, 
                              "User with {$scenario['description']} should " . 
                              ($scenario['should_have_access'] ? 'have' : 'not have') . ' access to virtual fitting');
            
            // Test that users with credits can perform virtual fitting
            if ($scenario['should_have_access']) {
                // User should be able to deduct a credit
                $deduction_result = $this->credit_manager->deduct_credit($user_id);
                $this->assertTrue($deduction_result, 
                                "User with {$scenario['description']} should be able to use virtual fitting");
                
                $credits_after_use = $this->credit_manager->get_customer_credits($user_id);
                $this->assertEquals($scenario['credits'] - 1, $credits_after_use, 
                                  "Credits should be deducted after virtual fitting use");
            } else {
                // User should not be able to deduct a credit (Requirement 5.1)
                $deduction_result = $this->credit_manager->deduct_credit($user_id);
                $this->assertFalse($deduction_result, 
                                 "User with {$scenario['description']} should not be able to use virtual fitting");
                
                $credits_after_failed_use = $this->credit_manager->get_customer_credits($user_id);
                $this->assertEquals($scenario['credits'], $credits_after_failed_use, 
                                  "Credits should remain unchanged after failed virtual fitting attempt");
            }
        }
        
        // Test edge cases for access control
        
        // Test with invalid user ID
        $invalid_access = $this->credit_manager->has_sufficient_credits(0, 1);
        $this->assertFalse($invalid_access, 
                          "Invalid user ID should not have access");
        
        $null_access = $this->credit_manager->has_sufficient_credits(null, 1);
        $this->assertFalse($null_access, 
                          "Null user ID should not have access");
        
        // Test with different credit requirements
        $user_with_credits = $this->factory->user->create();
        $this->credit_manager->add_credits($user_with_credits, 5); // User will have 7 total (2 initial + 5)
        
        $this->assertTrue($this->credit_manager->has_sufficient_credits($user_with_credits, 1), 
                         "User with 7 credits should have access for 1 credit requirement");
        $this->assertTrue($this->credit_manager->has_sufficient_credits($user_with_credits, 5), 
                         "User with 7 credits should have access for 5 credit requirement");
        $this->assertTrue($this->credit_manager->has_sufficient_credits($user_with_credits, 7), 
                         "User with 7 credits should have access for 7 credit requirement");
        $this->assertFalse($this->credit_manager->has_sufficient_credits($user_with_credits, 8), 
                          "User with 7 credits should not have access for 8 credit requirement");
        
        // Test boundary conditions
        $boundary_user = $this->factory->user->create();
        $this->credit_manager->get_customer_credits($boundary_user); // 2 initial credits
        $this->credit_manager->deduct_credit($boundary_user); // Now has 1 credit
        
        $this->assertTrue($this->credit_manager->has_sufficient_credits($boundary_user, 1), 
                         "User with exactly 1 credit should have access for 1 credit requirement");
        $this->assertFalse($this->credit_manager->has_sufficient_credits($boundary_user, 2), 
                          "User with exactly 1 credit should not have access for 2 credit requirement");
        
        // After using the last credit, access should be denied
        $this->credit_manager->deduct_credit($boundary_user); // Now has 0 credits
        $this->assertFalse($this->credit_manager->has_sufficient_credits($boundary_user, 1), 
                          "User with 0 credits should not have access for any requirement");
    }
}