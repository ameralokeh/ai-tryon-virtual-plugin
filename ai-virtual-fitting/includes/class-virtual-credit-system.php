<?php
/**
 * Virtual Credit System - No WooCommerce Products Needed
 * 
 * This class handles credit purchases without requiring visible WooCommerce products.
 * All credit management is done through WordPress admin settings.
 *
 * @package AI_Virtual_Fitting
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Virtual Credit System Class
 */
class AI_Virtual_Fitting_Virtual_Credit_System {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Plugin activation
        register_activation_hook(AI_VIRTUAL_FITTING_PLUGIN_FILE, array($this, 'on_plugin_activation'));
        
        // AJAX handlers for direct credit purchase
        add_action('wp_ajax_purchase_virtual_credits_direct', array($this, 'handle_direct_credit_purchase'));
        add_action('wp_ajax_nopriv_purchase_virtual_credits_direct', array($this, 'handle_direct_credit_purchase'));
        
        // Admin AJAX handlers
        add_action('wp_ajax_create_virtual_credit_product', array($this, 'ajax_create_credit_product'));
        add_action('wp_ajax_sync_credit_product_settings', array($this, 'ajax_sync_product_settings'));
        
        // Hide credit product from shop
        add_action('pre_get_posts', array($this, 'hide_credit_product_from_queries'));
        add_filter('woocommerce_product_is_visible', array($this, 'hide_credit_product_visibility'), 10, 2);
        
        // Prevent accidental deletion
        add_action('before_delete_post', array($this, 'prevent_credit_product_deletion'), 10, 2);
        add_action('wp_trash_post', array($this, 'prevent_credit_product_trash'));
        
        // Admin notices
        add_action('admin_notices', array($this, 'show_product_deletion_notice'));
        
        // Self-healing: Check product exists on admin pages
        add_action('admin_init', array($this, 'check_and_recreate_product'));
        
        // Sync product when settings change
        add_action('update_option_ai_virtual_fitting_credits_per_package', array($this, 'sync_product_on_settings_change'), 10, 2);
        add_action('update_option_ai_virtual_fitting_credits_package_price', array($this, 'sync_product_on_settings_change'), 10, 2);
    }
    
    /**
     * Setup on plugin activation
     */
    public function on_plugin_activation() {
        // Create hidden credit product
        $this->create_hidden_credit_product();
        
        // Create transaction table
        $this->create_transaction_table();
        
        // Set default settings
        $this->set_default_settings();
    }
    
    /**
     * Create hidden credit product automatically
     */
    public function create_hidden_credit_product() {
        // Check if product already exists
        $existing_product_id = get_option('ai_virtual_fitting_credit_product_id');
        if ($existing_product_id && get_post($existing_product_id)) {
            // Update existing product
            $this->update_credit_product($existing_product_id);
            return $existing_product_id;
        }
        
        // If we reach here, either no product ID is stored or the product was deleted
        // Clear the old product ID if it exists but product is gone
        if ($existing_product_id && !get_post($existing_product_id)) {
            delete_option('ai_virtual_fitting_credit_product_id');
            error_log('AI Virtual Fitting: Credit product was deleted. Recreating...');
        }
        
        // Get settings
        $credits_per_package = get_option('ai_virtual_fitting_credits_per_package', 20);
        $package_price = get_option('ai_virtual_fitting_credits_package_price', 10.00);
        
        // Create product
        $product = new WC_Product_Simple();
        $product->set_name(sprintf(__('%d Virtual Fitting Credits', 'ai-virtual-fitting'), $credits_per_package));
        $product->set_description(__('Virtual fitting credits for AI-powered try-on experiences. Credits are automatically added to your account after purchase.', 'ai-virtual-fitting'));
        $product->set_short_description(__('Purchase credits to use the virtual fitting feature.', 'ai-virtual-fitting'));
        $product->set_status('publish');
        $product->set_catalog_visibility('hidden');
        $product->set_featured(false);
        $product->set_virtual(true);
        $product->set_sold_individually(false);
        $product->set_manage_stock(false);
        $product->set_stock_status('instock');
        $product->set_regular_price($package_price);
        $product->set_price($package_price);
        
        // Save product
        $product_id = $product->save();
        
        if ($product_id) {
            // Add custom meta
            update_post_meta($product_id, '_ai_virtual_fitting_credits', $credits_per_package);
            update_post_meta($product_id, '_ai_virtual_fitting_hidden_product', 'yes');
            
            // Set visibility terms
            wp_set_post_terms($product_id, array('exclude-from-catalog', 'exclude-from-search'), 'product_visibility', false);
            
            // Store product ID
            update_option('ai_virtual_fitting_credit_product_id', $product_id);
            
            error_log('AI Virtual Fitting: Credit product created successfully with ID: ' . $product_id);
            
            return $product_id;
        }
        
        error_log('AI Virtual Fitting: Failed to create credit product');
        return false;
    }
    
    /**
     * Get or create credit product (self-healing)
     * This ensures the product always exists when needed
     */
    public function get_or_create_credit_product() {
        $product_id = get_option('ai_virtual_fitting_credit_product_id');
        
        // Check if product exists
        if ($product_id && get_post($product_id)) {
            return $product_id;
        }
        
        // Product doesn't exist - recreate it
        return $this->create_hidden_credit_product();
    }
    
    /**
     * Update credit product when settings change
     */
    private function update_credit_product($product_id) {
        $credits_per_package = get_option('ai_virtual_fitting_credits_per_package', 20);
        $package_price = get_option('ai_virtual_fitting_credits_package_price', 10.00);
        
        $product = wc_get_product($product_id);
        if ($product) {
            $product->set_name(sprintf(__('%d Virtual Fitting Credits', 'ai-virtual-fitting'), $credits_per_package));
            $product->set_regular_price($package_price);
            $product->set_price($package_price);
            $product->save();
            
            update_post_meta($product_id, '_ai_virtual_fitting_credits', $credits_per_package);
        }
    }
    
    /**
     * Prevent credit product from being deleted
     */
    public function prevent_credit_product_deletion($post_id, $post) {
        $credit_product_id = get_option('ai_virtual_fitting_credit_product_id');
        
        if ($post_id == $credit_product_id) {
            wp_die(
                __('This is the AI Virtual Fitting credit product and cannot be deleted. It is required for the plugin to function properly.', 'ai-virtual-fitting'),
                __('Cannot Delete Credit Product', 'ai-virtual-fitting'),
                array('back_link' => true)
            );
        }
    }
    
    /**
     * Prevent credit product from being trashed
     */
    public function prevent_credit_product_trash($post_id) {
        $credit_product_id = get_option('ai_virtual_fitting_credit_product_id');
        
        if ($post_id == $credit_product_id) {
            wp_die(
                __('This is the AI Virtual Fitting credit product and cannot be moved to trash. It is required for the plugin to function properly.', 'ai-virtual-fitting'),
                __('Cannot Trash Credit Product', 'ai-virtual-fitting'),
                array('back_link' => true)
            );
        }
    }
    
    /**
     * Check and recreate product if missing (self-healing)
     */
    public function check_and_recreate_product() {
        // Only run on admin pages, not on every request
        if (!is_admin()) {
            return;
        }
        
        // Check once per session to avoid performance issues
        if (get_transient('ai_virtual_fitting_product_check')) {
            return;
        }
        
        $product_id = get_option('ai_virtual_fitting_credit_product_id');
        
        // If product doesn't exist, recreate it
        if (!$product_id || !get_post($product_id)) {
            $new_product_id = $this->create_hidden_credit_product();
            
            if ($new_product_id) {
                // Set a flag to show admin notice
                set_transient('ai_virtual_fitting_product_recreated', true, 60);
            }
        }
        
        // Set transient to check again in 1 hour
        set_transient('ai_virtual_fitting_product_check', true, HOUR_IN_SECONDS);
    }
    
    /**
     * Show admin notice if product was recreated
     */
    public function show_product_deletion_notice() {
        if (get_transient('ai_virtual_fitting_product_recreated')) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong><?php _e('AI Virtual Fitting:', 'ai-virtual-fitting'); ?></strong>
                    <?php _e('The credit product was missing and has been automatically recreated. The plugin will continue to function normally.', 'ai-virtual-fitting'); ?>
                </p>
            </div>
            <?php
            delete_transient('ai_virtual_fitting_product_recreated');
        }
    }
    
    /**
     * Sync product when settings change
     */
    public function sync_product_on_settings_change($old_value, $new_value) {
        $product_id = get_option('ai_virtual_fitting_credit_product_id');
        if ($product_id) {
            $this->update_credit_product($product_id);
        }
    }
    
    /**
     * Hide credit product from all shop queries
     */
    public function hide_credit_product_from_queries($query) {
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        if (is_shop() || is_product_category() || is_product_tag() || is_search()) {
            $product_id = get_option('ai_virtual_fitting_credit_product_id');
            if ($product_id) {
                $excluded_ids = $query->get('post__not_in', array());
                $excluded_ids[] = $product_id;
                $query->set('post__not_in', $excluded_ids);
            }
        }
    }
    
    /**
     * Hide credit product visibility
     */
    public function hide_credit_product_visibility($visible, $product_id) {
        $credit_product_id = get_option('ai_virtual_fitting_credit_product_id');
        if ($product_id == $credit_product_id) {
            return false;
        }
        return $visible;
    }
    
    /**
     * Handle direct credit purchase (bypasses cart)
     */
    public function handle_direct_credit_purchase() {
        // Verify nonce
        check_ajax_referer('ai_virtual_fitting_checkout', 'nonce');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to purchase credits.', 'ai-virtual-fitting')));
        }
        
        $user_id = get_current_user_id();
        $payment_method = sanitize_text_field($_POST['payment_method'] ?? '');
        $payment_data = $_POST;
        
        // Get credit settings
        $credits_per_package = get_option('ai_virtual_fitting_credits_per_package', 20);
        $package_price = get_option('ai_virtual_fitting_credits_package_price', 10.00);
        
        // Process payment
        $payment_result = $this->process_payment($user_id, $package_price, $payment_method, $payment_data);
        
        if ($payment_result['success']) {
            // Add credits to user
            $credit_manager = new AI_Virtual_Fitting_Credit_Manager();
            $credits_added = $credit_manager->add_credits($user_id, $credits_per_package);
            
            if ($credits_added) {
                // Log transaction
                $this->log_transaction($user_id, $credits_per_package, $package_price, $payment_result['transaction_id'], $payment_method);
                
                // Get new balance
                $new_balance = $credit_manager->get_customer_credits($user_id);
                
                wp_send_json_success(array(
                    'message' => sprintf(__('%d credits added successfully!', 'ai-virtual-fitting'), $credits_per_package),
                    'credits_added' => $credits_per_package,
                    'new_balance' => $new_balance,
                    'transaction_id' => $payment_result['transaction_id']
                ));
            } else {
                wp_send_json_error(array('message' => __('Payment processed but failed to add credits. Please contact support.', 'ai-virtual-fitting')));
            }
        } else {
            wp_send_json_error(array('message' => $payment_result['message']));
        }
    }
    
    /**
     * Process payment based on method
     */
    private function process_payment($user_id, $amount, $payment_method, $payment_data) {
        switch ($payment_method) {
            case 'test_credit_card':
                return $this->process_test_payment($payment_data);
                
            default:
                return array(
                    'success' => false,
                    'message' => __('Invalid payment method.', 'ai-virtual-fitting')
                );
        }
    }
    
    /**
     * Process test credit card payment
     */
    private function process_test_payment($payment_data) {
        $card_number = sanitize_text_field($payment_data['test_credit_card-card-number'] ?? '');
        
        // Test card validation
        if ($card_number === '4242424242424242') {
            return array(
                'success' => true,
                'transaction_id' => 'test_' . time() . '_' . wp_rand(1000, 9999),
                'message' => __('Test payment successful', 'ai-virtual-fitting')
            );
        } elseif ($card_number === '4000000000000002') {
            return array(
                'success' => false,
                'message' => __('Card declined - insufficient funds', 'ai-virtual-fitting')
            );
        } else {
            return array(
                'success' => false,
                'message' => __('Invalid card number', 'ai-virtual-fitting')
            );
        }
    }
    
    /**
     * Log credit transaction
     */
    private function log_transaction($user_id, $credits, $amount, $transaction_id, $payment_method) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'virtual_fitting_credit_transactions';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'credits_purchased' => $credits,
                'amount_paid' => $amount,
                'transaction_id' => $transaction_id,
                'payment_method' => $payment_method,
                'status' => 'completed',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%f', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Create transaction table
     */
    private function create_transaction_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'virtual_fitting_credit_transactions';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            credits_purchased int(11) NOT NULL,
            amount_paid decimal(10,2) NOT NULL,
            transaction_id varchar(255) NOT NULL,
            payment_method varchar(50) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY transaction_id (transaction_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Set default settings
     */
    private function set_default_settings() {
        if (false === get_option('ai_virtual_fitting_initial_credits')) {
            update_option('ai_virtual_fitting_initial_credits', 2);
        }
        
        if (false === get_option('ai_virtual_fitting_credits_per_package')) {
            update_option('ai_virtual_fitting_credits_per_package', 20);
        }
        
        if (false === get_option('ai_virtual_fitting_credits_package_price')) {
            update_option('ai_virtual_fitting_credits_package_price', 10.00);
        }
    }
    
    /**
     * AJAX: Create credit product
     */
    public function ajax_create_credit_product() {
        check_ajax_referer('ai_virtual_fitting_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'ai-virtual-fitting'));
        }
        
        $product_id = $this->create_hidden_credit_product();
        
        if ($product_id) {
            wp_send_json_success(array(
                'message' => __('Hidden credit product created successfully!', 'ai-virtual-fitting'),
                'product_id' => $product_id
            ));
        } else {
            wp_send_json_error(__('Failed to create credit product.', 'ai-virtual-fitting'));
        }
    }
    
    /**
     * AJAX: Sync product settings
     */
    public function ajax_sync_product_settings() {
        check_ajax_referer('ai_virtual_fitting_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'ai-virtual-fitting'));
        }
        
        $product_id = get_option('ai_virtual_fitting_credit_product_id');
        if ($product_id) {
            $this->update_credit_product($product_id);
            wp_send_json_success(__('Product settings synced successfully!', 'ai-virtual-fitting'));
        } else {
            wp_send_json_error(__('No credit product found.', 'ai-virtual-fitting'));
        }
    }
}

// Initialize
new AI_Virtual_Fitting_Virtual_Credit_System();
