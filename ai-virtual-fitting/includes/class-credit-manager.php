<?php
/**
 * Credit Manager for AI Virtual Fitting Plugin
 *
 * @package AI_Virtual_Fitting
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI Virtual Fitting Credit Manager Class
 */
class AI_Virtual_Fitting_Credit_Manager {
    
    /**
     * Database Manager instance
     *
     * @var AI_Virtual_Fitting_Database_Manager
     */
    private $database_manager;
    
    /**
     * Credits table name
     *
     * @var string
     */
    private $credits_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database_manager = new AI_Virtual_Fitting_Database_Manager();
        $this->credits_table = $this->database_manager->get_credits_table();
        
        // Hook into user registration to grant initial credits
        add_action('user_register', array($this, 'grant_initial_credits'));
    }
    
    /**
     * Get customer credits
     *
     * @param int $user_id WordPress user ID
     * @return int Number of credits remaining
     */
    public function get_customer_credits($user_id) {
        global $wpdb;
        
        if (!$user_id || !is_numeric($user_id)) {
            return 0;
        }
        
        $credits = $wpdb->get_var($wpdb->prepare(
            "SELECT credits_remaining FROM {$this->credits_table} WHERE user_id = %d",
            $user_id
        ));
        
        // If user doesn't exist in credits table, create entry with initial credits
        if ($credits === null) {
            $this->grant_initial_credits($user_id);
            $credits = AI_Virtual_Fitting_Core::get_option('initial_credits', 2);
        }
        
        return (int) $credits;
    }
    
    /**
     * Deduct credit from customer account
     *
     * @param int $user_id WordPress user ID
     * @return bool True on success, false on failure
     */
    public function deduct_credit($user_id) {
        global $wpdb;
        
        if (!$user_id || !is_numeric($user_id)) {
            return false;
        }
        
        // Check if user has credits
        $current_credits = $this->get_customer_credits($user_id);
        if ($current_credits <= 0) {
            return false;
        }
        
        // Deduct one credit
        $result = $wpdb->update(
            $this->credits_table,
            array(
                'credits_remaining' => $current_credits - 1,
                'updated_at' => current_time('mysql')
            ),
            array('user_id' => $user_id),
            array('%d', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            // Log error
            if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
                error_log("AI Virtual Fitting: Failed to deduct credit for user {$user_id}");
            }
            return false;
        }
        
        // Log successful credit deduction
        if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
            $remaining = $current_credits - 1;
            error_log("AI Virtual Fitting: Credit deducted for user {$user_id}. Remaining: {$remaining}");
        }
        
        return true;
    }
    
    /**
     * Add credits to customer account
     *
     * @param int $user_id WordPress user ID
     * @param int $amount Number of credits to add
     * @return bool True on success, false on failure
     */
    public function add_credits($user_id, $amount) {
        global $wpdb;
        
        if (!$user_id || !is_numeric($user_id) || !$amount || !is_numeric($amount) || $amount <= 0) {
            return false;
        }
        
        // Get current credits (this will create entry if it doesn't exist)
        $current_credits = $this->get_customer_credits($user_id);
        
        // Get current total purchased credits
        $current_purchased = $wpdb->get_var($wpdb->prepare(
            "SELECT total_credits_purchased FROM {$this->credits_table} WHERE user_id = %d",
            $user_id
        ));
        
        if ($current_purchased === null) {
            $current_purchased = 0;
        }
        
        // Update credits
        $result = $wpdb->update(
            $this->credits_table,
            array(
                'credits_remaining' => $current_credits + $amount,
                'total_credits_purchased' => $current_purchased + $amount,
                'updated_at' => current_time('mysql')
            ),
            array('user_id' => $user_id),
            array('%d', '%d', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            // Log error
            if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
                error_log("AI Virtual Fitting: Failed to add {$amount} credits for user {$user_id}");
            }
            return false;
        }
        
        // Log successful credit addition
        if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
            $new_total = $current_credits + $amount;
            error_log("AI Virtual Fitting: Added {$amount} credits for user {$user_id}. New total: {$new_total}");
        }
        
        return true;
    }
    
    /**
     * Grant initial credits to new user
     *
     * @param int $user_id WordPress user ID
     * @return bool True on success, false on failure
     */
    public function grant_initial_credits($user_id) {
        global $wpdb;
        
        if (!$user_id || !is_numeric($user_id)) {
            return false;
        }
        
        // Check if user already has credits entry
        $existing_credits = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->credits_table} WHERE user_id = %d",
            $user_id
        ));
        
        if ($existing_credits !== null) {
            // User already has credits entry, don't grant again
            return true;
        }
        
        $initial_credits = AI_Virtual_Fitting_Core::get_option('initial_credits', 2);
        
        // Insert new credits record
        $result = $wpdb->insert(
            $this->credits_table,
            array(
                'user_id' => $user_id,
                'credits_remaining' => $initial_credits,
                'total_credits_purchased' => 0,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%s', '%s')
        );
        
        if ($result === false) {
            // Log error
            if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
                error_log("AI Virtual Fitting: Failed to grant initial credits for user {$user_id}");
            }
            return false;
        }
        
        // Log successful initial credit grant
        if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
            error_log("AI Virtual Fitting: Granted {$initial_credits} initial credits for user {$user_id}");
        }
        
        return true;
    }
    
    /**
     * Handle credit purchase from WooCommerce order
     *
     * @param int $order_id WooCommerce order ID
     * @return bool True on success, false on failure
     */
    public function handle_credit_purchase($order_id) {
        if (!$order_id || !is_numeric($order_id)) {
            return false;
        }
        
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return false;
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }
        
        // Get customer user ID
        $user_id = $order->get_customer_id();
        if (!$user_id) {
            return false;
        }
        
        $credits_added = 0;
        
        // Check each order item for virtual fitting credits
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) {
                continue;
            }
            
            // Check if this is a virtual fitting credits product
            $credits_per_item = $product->get_meta('_virtual_fitting_credits');
            if ($credits_per_item && is_numeric($credits_per_item)) {
                $quantity = $item->get_quantity();
                $total_credits = $credits_per_item * $quantity;
                
                // Add credits to customer account
                if ($this->add_credits($user_id, $total_credits)) {
                    $credits_added += $total_credits;
                }
            }
        }
        
        if ($credits_added > 0) {
            // Add order note
            $order->add_order_note(
                sprintf(
                    __('Virtual Fitting Credits: %d credits added to customer account.', 'ai-virtual-fitting'),
                    $credits_added
                )
            );
            
            // Log successful credit purchase
            if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
                error_log("AI Virtual Fitting: Order {$order_id} - Added {$credits_added} credits for user {$user_id}");
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if customer has sufficient credits
     *
     * @param int $user_id WordPress user ID
     * @param int $required_credits Number of credits required (default: 1)
     * @return bool True if user has sufficient credits, false otherwise
     */
    public function has_sufficient_credits($user_id, $required_credits = 1) {
        $current_credits = $this->get_customer_credits($user_id);
        return $current_credits >= $required_credits;
    }
    
    /**
     * Get customer credit history
     *
     * @param int $user_id WordPress user ID
     * @return array Credit history data
     */
    public function get_customer_credit_history($user_id) {
        global $wpdb;
        
        if (!$user_id || !is_numeric($user_id)) {
            return array();
        }
        
        $credits_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->credits_table} WHERE user_id = %d",
            $user_id
        ), ARRAY_A);
        
        if (!$credits_data) {
            return array(
                'credits_remaining' => 0,
                'total_credits_purchased' => 0,
                'total_credits_used' => 0,
                'created_at' => null,
                'updated_at' => null
            );
        }
        
        // Calculate total credits used
        $total_used = $credits_data['total_credits_purchased'] - $credits_data['credits_remaining'];
        
        // Add initial credits to calculation if user has used more than purchased
        $initial_credits = AI_Virtual_Fitting_Core::get_option('initial_credits', 2);
        if ($total_used < 0) {
            $total_used = $initial_credits + $credits_data['total_credits_purchased'] - $credits_data['credits_remaining'];
        }
        
        return array(
            'credits_remaining' => (int) $credits_data['credits_remaining'],
            'total_credits_purchased' => (int) $credits_data['total_credits_purchased'],
            'total_credits_used' => max(0, $total_used),
            'created_at' => $credits_data['created_at'],
            'updated_at' => $credits_data['updated_at']
        );
    }
    
    /**
     * Migrate existing users to credit system
     *
     * @return int Number of users migrated
     */
    public function migrate_existing_users() {
        global $wpdb;
        
        // Get all users who don't have credits entries
        $users_without_credits = $wpdb->get_results("
            SELECT u.ID 
            FROM {$wpdb->users} u 
            LEFT JOIN {$this->credits_table} c ON u.ID = c.user_id 
            WHERE c.user_id IS NULL
        ");
        
        $migrated_count = 0;
        
        foreach ($users_without_credits as $user) {
            if ($this->grant_initial_credits($user->ID)) {
                $migrated_count++;
            }
        }
        
        // Log migration results
        if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
            error_log("AI Virtual Fitting: Migrated {$migrated_count} existing users to credit system");
        }
        
        return $migrated_count;
    }
    
    /**
     * Get total credits purchased by customer
     *
     * @param int $user_id WordPress user ID
     * @return int Total credits purchased
     */
    public function get_total_credits_purchased($user_id) {
        global $wpdb;
        
        if (!$user_id || !is_numeric($user_id)) {
            return 0;
        }
        
        $total_purchased = $wpdb->get_var($wpdb->prepare(
            "SELECT total_credits_purchased FROM {$this->credits_table} WHERE user_id = %d",
            $user_id
        ));
        
        return $total_purchased ? (int) $total_purchased : 0;
    }
    
    /**
     * Get total system credit statistics
     *
     * @return array System-wide credit statistics
     */
    public function get_system_credit_stats() {
        global $wpdb;
        
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_users,
                SUM(credits_remaining) as total_remaining_credits,
                SUM(total_credits_purchased) as total_purchased_credits,
                AVG(credits_remaining) as avg_remaining_credits
            FROM {$this->credits_table}
        ", ARRAY_A);
        
        if (!$stats) {
            return array(
                'total_users' => 0,
                'total_remaining_credits' => 0,
                'total_purchased_credits' => 0,
                'avg_remaining_credits' => 0,
                'total_used_credits' => 0
            );
        }
        
        // Calculate total used credits (including initial credits)
        $initial_credits = AI_Virtual_Fitting_Core::get_option('initial_credits', 2);
        $total_initial_credits = $stats['total_users'] * $initial_credits;
        $total_available_credits = $total_initial_credits + $stats['total_purchased_credits'];
        $total_used_credits = $total_available_credits - $stats['total_remaining_credits'];
        
        return array(
            'total_users' => (int) $stats['total_users'],
            'total_remaining_credits' => (int) $stats['total_remaining_credits'],
            'total_purchased_credits' => (int) $stats['total_purchased_credits'],
            'avg_remaining_credits' => round($stats['avg_remaining_credits'], 2),
            'total_used_credits' => max(0, $total_used_credits)
        );
    }
}