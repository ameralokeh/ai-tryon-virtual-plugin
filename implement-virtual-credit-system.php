<?php
/**
 * Virtual Credit System Implementation
 * No WooCommerce products needed - pure virtual credits
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Virtual Credit System Class
 * Handles credit purchases without WooCommerce products
 */
class AI_Virtual_Fitting_Virtual_Credit_System {
    
    /**
     * Initialize the virtual credit system
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // AJAX handlers for credit purchase
        add_action('wp_ajax_purchase_virtual_credits', array($this, 'handle_credit_purchase'));
        add_action('wp_ajax_nopriv_purchase_virtual_credits', array($this, 'handle_credit_purchase'));
        
        // Admin hooks for credit management
        add_action('wp_ajax_create_virtual_credit_product', array($this, 'create_virtual_credit_product'));
        add_action('wp_ajax_hide_credit_product_from_shop', array($this, 'hide_credit_product'));
        
        // Plugin activation hook
        add_action('ai_virtual_fitting_activated', array($this, 'setup_virtual_credit_system'));
    }
    
    /**
     * Setup virtual credit system on plugin activation
     */
    public function setup_virtual_credit_system() {
        // Create hidden credit product automatically
        $this->create_hidden_credit_product();
        
        // Set default credit settings
        $this->set_default_credit_settings();
    }
    
    /**
     * Create a hidden credit product (not visible in shop)
     */
    private function create_hidden_credit_product() {
        // Check if product already exists
        $existing_product_id = get_option('ai_virtual_fitting_credit_product_id');
        if ($existing_product_id && get_post($existing_product_id)) {
            return $existing_product_id;
        }
        
        // Get credit settings from admin
        $credits_per_package = get_option('ai_virtual_fitting_credits_per_package', 20);
        $package_price = get_option('ai_virtual_fitting_credits_package_price', 10.00);
        
        // Create virtual credit product
        $product_data = array(
            'post_title' => sprintf(__('%d Virtual Fitting Credits', 'ai-virtual-fitting'), $credits_per_package),
            'post_content' => __('Virtual fitting credits for AI-powered try-on experiences. Credits are automatically added to your account after purchase.', 'ai-virtual-fitting'),
            'post_status' => 'publish',
            'post_type' => 'product',
            'meta_input' => array(
                '_visibility' => 'hidden',           // Hidden from shop
                '_featured' => 'no',
                '_virtual' => 'yes',                 // Virtual product
                '_downloadable' => 'no',
                '_sold_individually' => 'no',
                '_manage_stock' => 'no',
                '_stock_status' => 'instock',
                '_regular_price' => $package_price,
                '_price' => $package_price,
                '_ai_virtual_fitting_credits' => $credits_per_package,  // Custom meta
                '_ai_virtual_fitting_hidden_product' => 'yes'           // Mark as our hidden product
            )
        );
        
        $product_id = wp_insert_post($product_data);
        
        if ($product_id && !is_wp_error($product_id)) {
            // Set product categories (hidden category)
            wp_set_object_terms($product_id, array('virtual-credits'), 'product_cat');
            
            // Store product ID for future reference
            update_option('ai_virtual_fitting_credit_product_id', $product_id);
            
            // Ensure product is completely hidden from shop
            $this->hide_product_from_shop($product_id);
            
            return $product_id;
        }
        
        return false;
    }
    
    /**
     * Hide credit product from shop completely
     */
    private function hide_product_from_shop($product_id) {
        // Set catalog visibility to hidden
        wp_set_post_terms($product_id, array('exclude-from-catalog', 'exclude-from-search'), 'product_visibility');
        
        // Add custom meta to ensure it stays hidden
        update_post_meta($product_id, '_visibility', 'hidden');
        update_post_meta($product_id, '_catalog_visibility', 'hidden');
        
        // Hook to remove from all shop queries
        add_action('pre_get_posts', function($query) use ($product_id) {
            if (!is_admin() && $query->is_main_query()) {
                if (is_shop() || is_product_category() || is_product_tag()) {
                    $meta_query = $query->get('meta_query') ?: array();
                    $meta_query[] = array(
                        'key' => '_ai_virtual_fitting_hidden_product',
                        'compare' => 'NOT EXISTS'
                    );
                    $query->set('meta_query', $meta_query);
                }
            }
        });
    }
    
    /**
     * Set default credit settings
     */
    private function set_default_credit_settings() {
        // Set defaults if not already configured
        if (!get_option('ai_virtual_fitting_initial_credits')) {
            update_option('ai_virtual_fitting_initial_credits', 2);
        }
        
        if (!get_option('ai_virtual_fitting_credits_per_package')) {
            update_option('ai_virtual_fitting_credits_per_package', 20);
        }
        
        if (!get_option('ai_virtual_fitting_credits_package_price')) {
            update_option('ai_virtual_fitting_credits_package_price', 10.00);
        }
    }
    
    /**
     * Handle direct credit purchase (bypasses WooCommerce cart)
     */
    public function handle_credit_purchase() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'ai_virtual_fitting_purchase_credits')) {
            wp_send_json_error(__('Security check failed.', 'ai-virtual-fitting'));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to purchase credits.', 'ai-virtual-fitting'));
        }
        
        $user_id = get_current_user_id();
        $credits_to_purchase = intval($_POST['credits'] ?? 0);
        $payment_method = sanitize_text_field($_POST['payment_method'] ?? '');
        
        // Validate credit amount
        $credits_per_package = get_option('ai_virtual_fitting_credits_per_package', 20);
        if ($credits_to_purchase !== $credits_per_package) {
            wp_send_json_error(__('Invalid credit amount.', 'ai-virtual-fitting'));
        }
        
        // Calculate price
        $package_price = get_option('ai_virtual_fitting_credits_package_price', 10.00);
        
        // Process payment directly (no WooCommerce order needed)
        $payment_result = $this->process_direct_payment($user_id, $package_price, $payment_method, $_POST);
        
        if ($payment_result['success']) {
            // Add credits to user account
            $credit_manager = new AI_Virtual_Fitting_Credit_Manager();
            $credits_added = $credit_manager->add_credits($user_id, $credits_to_purchase);
            
            if ($credits_added) {
                // Log the transaction
                $this->log_credit_transaction($user_id, $credits_to_purchase, $package_price, $payment_result['transaction_id']);
                
                // Send success response
                wp_send_json_success(array(
                    'message' => sprintf(__('%d credits added to your account!', 'ai-virtual-fitting'), $credits_to_purchase),
                    'credits_added' => $credits_to_purchase,
                    'new_balance' => $credit_manager->get_customer_credits($user_id),
                    'transaction_id' => $payment_result['transaction_id']
                ));
            } else {
                wp_send_json_error(__('Payment processed but failed to add credits. Please contact support.', 'ai-virtual-fitting'));
            }
        } else {
            wp_send_json_error($payment_result['message']);
        }
    }
    
    /**
     * Process payment directly without WooCommerce
     */
    private function process_direct_payment($user_id, $amount, $payment_method, $payment_data) {
        switch ($payment_method) {
            case 'test_credit_card':
                return $this->process_test_payment($user_id, $amount, $payment_data);
                
            case 'stripe':
                return $this->process_stripe_payment($user_id, $amount, $payment_data);
                
            case 'paypal':
                return $this->process_paypal_payment($user_id, $amount, $payment_data);
                
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
    private function process_test_payment($user_id, $amount, $payment_data) {
        $card_number = sanitize_text_field($payment_data['test_credit_card_card_number'] ?? '');
        
        // Test card validation
        if ($card_number === '4242424242424242') {
            return array(
                'success' => true,
                'transaction_id' => 'test_' . time() . '_' . $user_id,
                'message' => __('Test payment successful', 'ai-virtual-fitting')
            );
        } elseif ($card_number === '4000000000000002') {
            return array(
                'success' => false,
                'message' => __('Test payment declined', 'ai-virtual-fitting')
            );
        } else {
            return array(
                'success' => false,
                'message' => __('Invalid test card number', 'ai-virtual-fitting')
            );
        }
    }
    
    /**
     * Log credit transaction
     */
    private function log_credit_transaction($user_id, $credits, $amount, $transaction_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'virtual_fitting_credit_transactions';
        
        // Create table if it doesn't exist
        $this->create_transaction_table();
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'credits_purchased' => $credits,
                'amount_paid' => $amount,
                'transaction_id' => $transaction_id,
                'payment_method' => 'direct_purchase',
                'status' => 'completed',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%f', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Create transaction logging table
     */
    private function create_transaction_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'virtual_fitting_credit_transactions';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
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
     * Get admin credit management interface
     */
    public function get_admin_credit_interface() {
        ob_start();
        ?>
        <div class="virtual-credit-admin">
            <h3><?php _e('Virtual Credit System', 'ai-virtual-fitting'); ?></h3>
            
            <div class="credit-settings">
                <h4><?php _e('Credit Package Settings', 'ai-virtual-fitting'); ?></h4>
                <p><?php _e('These settings control the virtual credit system. No WooCommerce products are needed.', 'ai-virtual-fitting'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th><?php _e('Credits per Package', 'ai-virtual-fitting'); ?></th>
                        <td>
                            <input type="number" name="credits_per_package" value="<?php echo esc_attr(get_option('ai_virtual_fitting_credits_per_package', 20)); ?>" min="1" max="100" />
                            <p class="description"><?php _e('How many credits customers get per purchase', 'ai-virtual-fitting'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Package Price', 'ai-virtual-fitting'); ?></th>
                        <td>
                            <input type="number" name="package_price" value="<?php echo esc_attr(get_option('ai_virtual_fitting_credits_package_price', 10.00)); ?>" min="0.01" step="0.01" />
                            <span><?php echo get_woocommerce_currency_symbol(); ?></span>
                            <p class="description"><?php _e('Price customers pay for a credit package', 'ai-virtual-fitting'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Free Initial Credits', 'ai-virtual-fitting'); ?></th>
                        <td>
                            <input type="number" name="initial_credits" value="<?php echo esc_attr(get_option('ai_virtual_fitting_initial_credits', 2)); ?>" min="0" max="10" />
                            <p class="description"><?php _e('Free credits given to new users', 'ai-virtual-fitting'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <div class="credit-system-status">
                    <h4><?php _e('System Status', 'ai-virtual-fitting'); ?></h4>
                    <?php
                    $credit_product_id = get_option('ai_virtual_fitting_credit_product_id');
                    $product_exists = $credit_product_id && get_post($credit_product_id);
                    ?>
                    <p>
                        <strong><?php _e('Hidden Credit Product:', 'ai-virtual-fitting'); ?></strong>
                        <?php if ($product_exists): ?>
                            <span style="color: green;">✓ <?php _e('Created and Hidden', 'ai-virtual-fitting'); ?></span>
                            <a href="<?php echo admin_url('post.php?post=' . $credit_product_id . '&action=edit'); ?>" target="_blank"><?php _e('View Product', 'ai-virtual-fitting'); ?></a>
                        <?php else: ?>
                            <span style="color: red;">✗ <?php _e('Not Created', 'ai-virtual-fitting'); ?></span>
                            <button type="button" id="create-credit-product" class="button"><?php _e('Create Hidden Product', 'ai-virtual-fitting'); ?></button>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#create-credit-product').click(function() {
                $.post(ajaxurl, {
                    action: 'create_virtual_credit_product',
                    nonce: '<?php echo wp_create_nonce('ai_virtual_fitting_admin'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}

// Initialize the virtual credit system
new AI_Virtual_Fitting_Virtual_Credit_System();