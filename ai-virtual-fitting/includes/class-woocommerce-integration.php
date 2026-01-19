<?php
/**
 * WooCommerce Integration for AI Virtual Fitting Plugin
 *
 * @package AI_Virtual_Fitting
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI Virtual Fitting WooCommerce Integration Class
 */
class AI_Virtual_Fitting_WooCommerce_Integration {
    
    /**
     * Credit Manager instance
     *
     * @var AI_Virtual_Fitting_Credit_Manager
     */
    private $credit_manager;
    
    /**
     * Credits product ID
     *
     * @var int
     */
    private $credits_product_id;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->credit_manager = new AI_Virtual_Fitting_Credit_Manager();
        $this->credits_product_id = get_option('ai_virtual_fitting_credits_product_id', 0);
        
        // Hook into WooCommerce order completion
        add_action('woocommerce_order_status_completed', array($this, 'handle_order_completed'));
        add_action('woocommerce_payment_complete', array($this, 'handle_payment_complete'));
        
        // Hook into WooCommerce cart actions
        add_action('wp_ajax_add_virtual_fitting_credits', array($this, 'ajax_add_credits_to_cart'));
        add_action('wp_ajax_nopriv_add_virtual_fitting_credits', array($this, 'ajax_add_credits_to_cart'));
    }
    
    /**
     * Create virtual fitting credits product
     *
     * @return int|false Product ID on success, false on failure
     */
    public function create_credits_product() {
        // Enhanced logging
        error_log('AI Virtual Fitting: create_credits_product() called');
        
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            error_log('AI Virtual Fitting: WooCommerce not active during product creation');
            return false;
        }
        
        // Check if product already exists
        $product_id = get_option('ai_virtual_fitting_credits_product_id');
        if ($product_id) {
            $post = get_post($product_id);
            if ($post) {
                // Check if product is trashed
                if ($post->post_status === 'trash') {
                    error_log('AI Virtual Fitting: Credits product (ID: ' . $product_id . ') is in trash, restoring it');
                    
                    // Restore from trash using WooCommerce
                    $product = wc_get_product($product_id);
                    if ($product) {
                        $product->set_status('private'); // Set to private instead of publish
                        $product->set_catalog_visibility('hidden');
                        $product->save();
                        error_log('AI Virtual Fitting: Restored credits product from trash with ID: ' . $product_id);
                    } else {
                        // Fallback to WordPress method
                        wp_untrash_post($product_id);
                        wp_update_post(array(
                            'ID' => $product_id,
                            'post_status' => 'private' // Set to private instead of publish
                        ));
                        error_log('AI Virtual Fitting: Restored credits product using wp_untrash_post with ID: ' . $product_id);
                    }
                    
                    return $product_id;
                } else {
                    // Product exists - verify it's private and hidden
                    if ($post->post_status !== 'private') {
                        error_log('AI Virtual Fitting: Credits product (ID: ' . $product_id . ') is not private, fixing status');
                        $product = wc_get_product($product_id);
                        if ($product) {
                            $product->set_status('private');
                            $product->set_catalog_visibility('hidden');
                            $product->save();
                            error_log('AI Virtual Fitting: Updated credits product to private status');
                        }
                    }
                    error_log('AI Virtual Fitting: Credits product already exists with ID: ' . $product_id);
                    return $product_id;
                }
            }
        }

        try {
            // Create the product
            $product = new WC_Product_Simple();
            $product->set_name('Virtual Fitting Credits - 20 Pack');
            $product->set_description('Purchase 20 virtual fitting credits to try on wedding dresses with AI technology.');
            $product->set_short_description('20 virtual fitting credits for AI-powered dress try-on experience.');
            $product->set_regular_price('10.00');
            $product->set_price('10.00');
            $product->set_virtual(true);
            $product->set_downloadable(false);
            $product->set_catalog_visibility('hidden'); // Hidden from catalog
            $product->set_status('private'); // Private - not visible in catalog or search
            $product->set_manage_stock(false);
            $product->set_stock_status('instock');
            
            // Set custom meta to identify this as credits product
            $product->add_meta_data('_virtual_fitting_credits', '20', true);
            $product->add_meta_data('_virtual_fitting_product', 'yes', true);
            
            // Save the product
            $product_id = $product->save();
            
            if ($product_id) {
                // Store product ID in options
                update_option('ai_virtual_fitting_credits_product_id', $product_id);
                update_post_meta($product_id, '_ai_virtual_fitting_credits', 20);
                $this->credits_product_id = $product_id;
                
                // Log the creation
                error_log('AI Virtual Fitting: Created credits product with ID: ' . $product_id);
                
                return $product_id;
            }
            
            error_log('AI Virtual Fitting: Failed to create credits product - save returned false');
            return false;
        } catch (Exception $e) {
            error_log('AI Virtual Fitting: Exception creating credits product: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Handle WooCommerce order completion
     *
     * @param int $order_id
     */
    public function handle_order_completed($order_id) {
        $this->process_credits_order($order_id);
    }
    
    /**
     * Handle WooCommerce payment completion
     *
     * @param int $order_id
     */
    public function handle_payment_complete($order_id) {
        $this->process_credits_order($order_id);
    }
    
    /**
     * Process credits order and add credits to customer account
     *
     * @param int $order_id
     */
    private function process_credits_order($order_id) {
        if (!$order_id) {
            return;
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            error_log("AI Virtual Fitting: Invalid order ID: {$order_id}");
            return;
        }
        
        // Check if we've already processed this order
        $processed = $order->get_meta('_virtual_fitting_credits_processed');
        if ($processed === 'yes') {
            return; // Already processed
        }
        
        $customer_id = $order->get_customer_id();
        if (!$customer_id) {
            error_log("AI Virtual Fitting: No customer ID for order: {$order_id}");
            return;
        }
        
        $credits_added = 0;
        
        // Check each item in the order
        foreach ($order->get_items() as $item_id => $item) {
            $product_id = $item->get_product_id();
            $quantity = $item->get_quantity();
            
            // Check if this is our credits product
            if ($this->is_credits_product($product_id)) {
                // Try both meta keys for backwards compatibility
                $credits_per_product = get_post_meta($product_id, '_ai_virtual_fitting_credits', true);
                if (empty($credits_per_product)) {
                    $credits_per_product = get_post_meta($product_id, '_virtual_fitting_credits', true);
                }
                $credits_per_product = intval($credits_per_product);
                
                if ($credits_per_product > 0) {
                    $total_credits = $credits_per_product * $quantity;
                    
                    // Add credits to customer account
                    $success = $this->credit_manager->add_credits($customer_id, $total_credits);
                    
                    if ($success) {
                        $credits_added += $total_credits;
                        error_log("AI Virtual Fitting: Added {$total_credits} credits to user {$customer_id} from order {$order_id}");
                    } else {
                        error_log("AI Virtual Fitting: Failed to add credits to user {$customer_id} from order {$order_id}");
                    }
                }
            }
        }
        
        if ($credits_added > 0) {
            // Mark order as processed
            $order->update_meta_data('_virtual_fitting_credits_processed', 'yes');
            $order->save();
            
            // Add order note
            $order->add_order_note(
                sprintf(
                    __('Virtual Fitting Credits: Added %d credits to customer account.', 'ai-virtual-fitting'),
                    $credits_added
                )
            );
            
            // Send confirmation email (optional - can be implemented later)
            $this->send_credits_confirmation($customer_id, $credits_added, $order_id);
        }
    }
    
    /**
     * Add credits to cart via AJAX
     */
    public function ajax_add_credits_to_cart() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'virtual_fitting_nonce')) {
            wp_die('Security check failed');
        }
        
        // Ensure credits product exists
        $product_id = $this->get_or_create_credits_product();
        
        if (!$product_id) {
            wp_send_json_error('Failed to create credits product');
            return;
        }
        
        // Add to cart
        $cart_item_key = WC()->cart->add_to_cart($product_id, 1);
        
        if ($cart_item_key) {
            wp_send_json_success(array(
                'message' => 'Credits added to cart',
                'cart_url' => wc_get_cart_url(),
                'checkout_url' => wc_get_checkout_url()
            ));
        } else {
            wp_send_json_error('Failed to add credits to cart');
        }
    }
    
    /**
     * Add credits to cart programmatically
     *
     * @param int $quantity Number of credit packs to add
     * @return bool|string Cart item key on success, false on failure
     */
    public function add_credits_to_cart($quantity = 1) {
        $product_id = $this->get_or_create_credits_product();
        
        if (!$product_id) {
            return false;
        }
        
        return WC()->cart->add_to_cart($product_id, $quantity);
    }
    
    /**
     * Get or create credits product (uses Virtual Credit System's self-healing method)
     *
     * @return int|false Product ID on success, false on failure
     */
    public function get_or_create_credits_product() {
        // Use the Virtual Credit System's self-healing method
        // This ensures the product is automatically recreated if deleted
        $virtual_credit_system = new AI_Virtual_Fitting_Virtual_Credit_System();
        $product_id = $virtual_credit_system->get_or_create_credit_product();
        
        if ($product_id) {
            $this->credits_product_id = $product_id;
            return $product_id;
        }
        
        // Fallback to old method if Virtual Credit System fails
        if ($this->credits_product_id && get_post($this->credits_product_id)) {
            return $this->credits_product_id;
        }
        
        return $this->create_credits_product();
    }
    
    /**
     * Check if a product is a credits product
     *
     * @param int $product_id
     * @return bool
     */
    public function is_credits_product($product_id) {
        $is_credits_product = get_post_meta($product_id, '_virtual_fitting_product', true);
        return $is_credits_product === 'yes';
    }
    
    /**
     * Validate credits product in order
     *
     * @param WC_Order $order
     * @return bool
     */
    public function validate_credits_product($order) {
        if (!$order) {
            return false;
        }
        
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            if ($this->is_credits_product($product_id)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get credits product ID
     *
     * @return int
     */
    public function get_credits_product_id() {
        return $this->credits_product_id;
    }
    
    /**
     * Send credits confirmation notification
     *
     * @param int $customer_id
     * @param int $credits_added
     * @param int $order_id
     */
    private function send_credits_confirmation($customer_id, $credits_added, $order_id) {
        $user = get_user_by('ID', $customer_id);
        if (!$user) {
            return;
        }
        
        $subject = __('Virtual Fitting Credits Added to Your Account', 'ai-virtual-fitting');
        $message = sprintf(
            __('Hello %s,

Thank you for your purchase! We have added %d virtual fitting credits to your account.

You can now use these credits to try on wedding dresses with our AI-powered virtual fitting technology.

Order #: %d

Best regards,
The Virtual Fitting Team', 'ai-virtual-fitting'),
            $user->display_name,
            $credits_added,
            $order_id
        );
        
        wp_mail($user->user_email, $subject, $message);
        
        error_log("AI Virtual Fitting: Sent confirmation email to {$user->user_email} for {$credits_added} credits");
    }
    
    /**
     * Get purchase URL for credits
     *
     * @return string
     */
    public function get_credits_purchase_url() {
        $product_id = $this->get_or_create_credits_product();
        
        if (!$product_id) {
            return wc_get_cart_url();
        }
        
        return add_query_arg(array(
            'add-to-cart' => $product_id
        ), wc_get_cart_url());
    }
    
    /**
     * Initialize WooCommerce integration
     * Called during plugin activation
     */
    public function initialize() {
        // Create credits product during plugin activation
        $this->create_credits_product();
    }
}