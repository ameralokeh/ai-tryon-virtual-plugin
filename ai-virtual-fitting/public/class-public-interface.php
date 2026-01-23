<?php
/**
 * Public Interface for AI Virtual Fitting Plugin
 *
 * @package AI_Virtual_Fitting
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI Virtual Fitting Public Interface Class
 */
class AI_Virtual_Fitting_Public_Interface {
    
    /**
     * Credit Manager instance
     *
     * @var AI_Virtual_Fitting_Credit_Manager
     */
    private $credit_manager;
    
    /**
     * Image Processor instance
     *
     * @var AI_Virtual_Fitting_Image_Processor
     */
    private $image_processor;
    
    /**
     * WooCommerce Integration instance
     *
     * @var AI_Virtual_Fitting_WooCommerce_Integration
     */
    private $woocommerce_integration;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->credit_manager = new AI_Virtual_Fitting_Credit_Manager();
        $this->image_processor = new AI_Virtual_Fitting_Image_Processor();
        $this->woocommerce_integration = new AI_Virtual_Fitting_WooCommerce_Integration();
        
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // AJAX hooks for logged-in and non-logged-in users
        add_action('wp_ajax_ai_virtual_fitting_upload', array($this, 'handle_image_upload'));
        add_action('wp_ajax_nopriv_ai_virtual_fitting_upload', array($this, 'handle_image_upload'));
        
        add_action('wp_ajax_ai_virtual_fitting_process', array($this, 'handle_fitting_request'));
        add_action('wp_ajax_nopriv_ai_virtual_fitting_process', array($this, 'handle_fitting_request'));
        
        add_action('wp_ajax_ai_virtual_fitting_download', array($this, 'handle_image_download'));
        add_action('wp_ajax_nopriv_ai_virtual_fitting_download', array($this, 'handle_image_download'));
        
        add_action('wp_ajax_ai_virtual_fitting_get_products', array($this, 'handle_get_products'));
        add_action('wp_ajax_nopriv_ai_virtual_fitting_get_products', array($this, 'handle_get_products'));
        
        add_action('wp_ajax_ai_virtual_fitting_check_credits', array($this, 'handle_check_credits'));
        add_action('wp_ajax_nopriv_ai_virtual_fitting_check_credits', array($this, 'handle_check_credits'));
        
        // Embedded checkout AJAX endpoints
        add_action('wp_ajax_ai_virtual_fitting_add_credits_to_cart', array($this, 'handle_add_credits_to_cart'));
        add_action('wp_ajax_nopriv_ai_virtual_fitting_add_credits_to_cart', array($this, 'handle_add_credits_to_cart'));
        
        add_action('wp_ajax_ai_virtual_fitting_clear_cart', array($this, 'handle_clear_cart'));
        add_action('wp_ajax_nopriv_ai_virtual_fitting_clear_cart', array($this, 'handle_clear_cart'));
        
        add_action('wp_ajax_ai_virtual_fitting_load_checkout', array($this, 'handle_load_checkout'));
        add_action('wp_ajax_nopriv_ai_virtual_fitting_load_checkout', array($this, 'handle_load_checkout'));
        
        add_action('wp_ajax_ai_virtual_fitting_process_checkout', array($this, 'handle_process_checkout'));
        add_action('wp_ajax_nopriv_ai_virtual_fitting_process_checkout', array($this, 'handle_process_checkout'));
        
        // Express checkout (Apple Pay / Google Pay) AJAX endpoint
        add_action('wp_ajax_ai_virtual_fitting_process_express_checkout', array($this, 'handle_process_express_checkout'));
        add_action('wp_ajax_nopriv_ai_virtual_fitting_process_express_checkout', array($this, 'handle_process_express_checkout'));
        
        // Real-time credit updates AJAX endpoint
        add_action('wp_ajax_ai_virtual_fitting_refresh_credits', array($this, 'handle_refresh_credits'));
        add_action('wp_ajax_nopriv_ai_virtual_fitting_refresh_credits', array($this, 'handle_refresh_credits'));
        
        // Payment method fee calculation AJAX endpoint
        add_action('wp_ajax_ai_virtual_fitting_calculate_fees', array($this, 'handle_calculate_fees'));
        add_action('wp_ajax_nopriv_ai_virtual_fitting_calculate_fees', array($this, 'handle_calculate_fees'));
        
        // WooCommerce hooks for fee calculation
        add_action('woocommerce_cart_calculate_fees', array($this, 'add_payment_method_fees'));
        
        // AJAX login handler
        add_action('wp_ajax_nopriv_ai_vf_ajax_login', array($this, 'handle_ajax_login'));
        
        // Fetch single product by ID (for product pre-selection)
        add_action('wp_ajax_ai_virtual_fitting_get_single_product', array($this, 'handle_get_single_product'));
        add_action('wp_ajax_nopriv_ai_virtual_fitting_get_single_product', array($this, 'handle_get_single_product'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Add virtual fitting page shortcode
        add_shortcode('ai_virtual_fitting', array($this, 'render_virtual_fitting_shortcode'));
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        // Check if we're on a page with the virtual fitting shortcode
        global $post;
        
        $should_enqueue = false;
        
        // Method 1: Check current post
        if (is_page() && $post && has_shortcode($post->post_content, 'ai_virtual_fitting')) {
            $should_enqueue = true;
        }
        
        // Method 2: Check if this is the virtual fitting page by slug
        if (is_page('virtual-fitting-2')) {
            $should_enqueue = true;
        }
        
        // Method 3: Check query var for virtual fitting page
        if (get_query_var('virtual_fitting_page')) {
            $should_enqueue = true;
        }
        
        // Method 4: Check for virtual fitting page by common slugs
        if (is_page(array('virtual-fitting', 'ai-virtual-fitting', 'virtual-try-on'))) {
            $should_enqueue = true;
        }
        
        // Method 5: Force enqueue on admin pages for testing
        if (is_admin() && isset($_GET['page']) && strpos($_GET['page'], 'virtual-fitting') !== false) {
            $should_enqueue = true;
        }
        
        // Method 6: Fallback - enqueue on all pages if we detect the shortcode anywhere
        if (!$should_enqueue && $post) {
            // Check post content more thoroughly
            if (strpos($post->post_content, 'ai_virtual_fitting') !== false || 
                strpos($post->post_content, 'virtual-fitting') !== false) {
                $should_enqueue = true;
            }
        }
        
        // Method 7: Emergency fallback - check URL for virtual fitting indicators
        if (!$should_enqueue) {
            $current_url = $_SERVER['REQUEST_URI'] ?? '';
            if (strpos($current_url, 'virtual-fitting') !== false || 
                strpos($current_url, 'virtual_fitting') !== false) {
                $should_enqueue = true;
            }
        }
        
        // Method 8: FORCE ENQUEUE - Always enqueue on frontend pages (temporary fix)
        // This ensures CSS loads even if detection fails
        if (!$should_enqueue && !is_admin()) {
            // Check if page ID matches known virtual fitting page
            $virtual_fitting_page_id = get_option('ai_virtual_fitting_page_id', 0);
            if ($virtual_fitting_page_id && is_page($virtual_fitting_page_id)) {
                $should_enqueue = true;
            }
        }
        
        if ($should_enqueue) {
            // Enqueue modern CSS
            wp_enqueue_style(
                'ai-virtual-fitting-modern-style',
                plugin_dir_url(__FILE__) . 'css/modern-virtual-fitting.css',
                array(),
                '1.7.17'  // Updated: Added "See More" pagination functionality
            );
            
            // Enqueue React checkout modal CSS (Simplified version)
            wp_enqueue_style(
                'ai-virtual-fitting-checkout-modal-react',
                plugin_dir_url(__FILE__) . 'css/checkout-modal-react.css',
                array(),
                '1.0.0'
            );
            
            wp_enqueue_style(
                'ai-virtual-fitting-checkout-modal-simple',
                plugin_dir_url(__FILE__) . 'css/checkout-modal-simple.css',
                array('ai-virtual-fitting-checkout-modal-react'),
                '1.0.0'
            );
            
            // Enqueue Stripe.js for Apple Pay / Google Pay support
            wp_enqueue_script(
                'stripe-js',
                'https://js.stripe.com/v3/',
                array(),
                '3.0',
                true
            );
            
            // Enqueue React and ReactDOM - Local first with CDN fallback
            // React
            wp_enqueue_script(
                'react',
                plugin_dir_url(__FILE__) . 'js/vendor/react.production.min.js',
                array(),
                '18.2.0',
                true
            );
            
            // Add CDN fallback for React
            wp_add_inline_script(
                'react',
                'window.React || document.write(\'<script src="https://unpkg.com/react@18.2.0/umd/react.production.min.js" integrity="sha384-/S8V0TNSxRqhXQrIJWy3+Iu+VJN6VJN6VJN6VJN6VJN6VJN6VJN6VJN6VJN6VJN6" crossorigin="anonymous"><\/script>\');',
                'after'
            );
            
            // ReactDOM
            wp_enqueue_script(
                'react-dom',
                plugin_dir_url(__FILE__) . 'js/vendor/react-dom.production.min.js',
                array('react'),
                '18.2.0',
                true
            );
            
            // Add CDN fallback for ReactDOM
            wp_add_inline_script(
                'react-dom',
                'window.ReactDOM || document.write(\'<script src="https://unpkg.com/react-dom@18.2.0/umd/react-dom.production.min.js" integrity="sha384-pprn3073KE6tl6bjs2QrFaJGz5/SUsLqktiwsUTF55Jfv3qYSDhgCecCxMW52nD2" crossorigin="anonymous"><\/script>\');',
                'after'
            );
            
            // Enqueue Babel standalone for JSX transformation
            wp_enqueue_script(
                'babel-standalone',
                'https://unpkg.com/@babel/standalone/babel.min.js',
                array(),
                '7.23.0',
                true
            );
            
            // Enqueue React checkout modal component (Simplified JSX version)
            wp_enqueue_script(
                'ai-virtual-fitting-checkout-modal-react',
                plugin_dir_url(__FILE__) . 'js/checkout-modal-simple.jsx',
                array('react', 'react-dom', 'babel-standalone'),
                '1.3.0',  // Updated to simplified version
                true
            );
            
            // Add script type for Babel transformation
            add_filter('script_loader_tag', function($tag, $handle) {
                if ('ai-virtual-fitting-checkout-modal-react' === $handle) {
                    return str_replace('<script', '<script type="text/babel"', $tag);
                }
                return $tag;
            }, 10, 2);
            
            // Enqueue login modal CSS
            wp_enqueue_style(
                'ai-virtual-fitting-login-modal',
                plugin_dir_url(__FILE__) . 'css/login-modal.css',
                array(),
                '1.0.0'
            );
            
            // Enqueue login modal JavaScript
            wp_enqueue_script(
                'ai-virtual-fitting-login-modal',
                plugin_dir_url(__FILE__) . 'js/login-modal.js',
                array('jquery'),
                '1.0.0',
                true
            );
            
            // Enqueue modern JavaScript
            wp_enqueue_script(
                'ai-virtual-fitting-modern-script',
                plugin_dir_url(__FILE__) . 'js/modern-virtual-fitting.js',
                array('jquery', 'ai-virtual-fitting-checkout-modal-react'),
                '1.5.6',  // Updated: Added "See More" pagination functionality
                true
            );
            
            // Localize script for AJAX
            wp_localize_script('ai-virtual-fitting-modern-script', 'ai_virtual_fitting_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_virtual_fitting_nonce'),
                'user_logged_in' => is_user_logged_in(),
                'checkout_url' => wc_get_checkout_url(),
                'register_url' => wp_registration_url(),
                'lost_password_url' => wp_lostpassword_url(),
                'messages' => array(
                    'login_required' => __('Please log in to use virtual fitting.', 'ai-virtual-fitting'),
                    'insufficient_credits' => __('You have insufficient credits. Please purchase more.', 'ai-virtual-fitting'),
                    'upload_error' => __('Error uploading image. Please try again.', 'ai-virtual-fitting'),
                    'processing_error' => __('Error processing virtual fitting. Please try again.', 'ai-virtual-fitting'),
                    'select_product' => __('Please select a product to try on.', 'ai-virtual-fitting'),
                    'upload_image' => __('Please upload your image first.', 'ai-virtual-fitting'),
                    'cart_add_error' => __('Error adding credits to cart. Please try again.', 'ai-virtual-fitting'),
                    'cart_clear_error' => __('Error clearing cart. Please try again.', 'ai-virtual-fitting')
                )
            ));
        }
    }
    
    /**
     * Render virtual fitting page shortcode
     */
    public function render_virtual_fitting_shortcode($atts) {
        // Force enqueue assets when shortcode is rendered
        $this->force_enqueue_assets();
        
        ob_start();
        $this->render_virtual_fitting_page();
        return ob_get_clean();
    }
    
    /**
     * Force enqueue assets (called from shortcode)
     */
    private function force_enqueue_assets() {
        // Enqueue modern CSS
        wp_enqueue_style(
            'ai-virtual-fitting-modern-style',
            plugin_dir_url(__FILE__) . 'css/modern-virtual-fitting.css',
            array(),
            '1.7.17'  // Updated: Added "See More" pagination functionality
        );
        
        // Enqueue login modal CSS
        wp_enqueue_style(
            'ai-virtual-fitting-login-modal',
            plugin_dir_url(__FILE__) . 'css/login-modal.css',
            array(),
            '1.0.0'
        );
        
        // Enqueue React checkout modal CSS (Simplified version)
        wp_enqueue_style(
            'ai-virtual-fitting-checkout-modal-react',
            plugin_dir_url(__FILE__) . 'css/checkout-modal-react.css',
            array(),
            '1.0.0'
        );
        
        wp_enqueue_style(
            'ai-virtual-fitting-checkout-modal-simple',
            plugin_dir_url(__FILE__) . 'css/checkout-modal-simple.css',
            array('ai-virtual-fitting-checkout-modal-react'),
            '1.0.0'
        );
        
        // Enqueue Stripe.js for Apple Pay / Google Pay support
        wp_enqueue_script(
            'stripe-js',
            'https://js.stripe.com/v3/',
            array(),
            '3.0',
            true
        );
        
        // Enqueue React and ReactDOM
        wp_enqueue_script(
            'react',
            plugin_dir_url(__FILE__) . 'js/vendor/react.production.min.js',
            array(),
            '18.2.0',
            true
        );
        
        wp_enqueue_script(
            'react-dom',
            plugin_dir_url(__FILE__) . 'js/vendor/react-dom.production.min.js',
            array('react'),
            '18.2.0',
            true
        );
        
        // Enqueue Babel standalone for JSX transformation
        wp_enqueue_script(
            'babel-standalone',
            'https://unpkg.com/@babel/standalone/babel.min.js',
            array(),
            '7.23.0',
            true
        );
        
        // Enqueue React checkout modal component
        wp_enqueue_script(
            'ai-virtual-fitting-checkout-modal-react',
            plugin_dir_url(__FILE__) . 'js/checkout-modal-simple.jsx',
            array('react', 'react-dom', 'babel-standalone'),
            '1.3.0',
            true
        );
        
        // Add script type for Babel transformation
        add_filter('script_loader_tag', function($tag, $handle) {
            if ('ai-virtual-fitting-checkout-modal-react' === $handle) {
                return str_replace('<script', '<script type="text/babel"', $tag);
            }
            return $tag;
        }, 10, 2);
        
        // Enqueue modern JavaScript
        wp_enqueue_script(
            'ai-virtual-fitting-modern-script',
            plugin_dir_url(__FILE__) . 'js/modern-virtual-fitting.js',
            array('jquery', 'ai-virtual-fitting-checkout-modal-react'),
            '1.5.6',
            true
        );
        
        // Enqueue login modal JavaScript
        wp_enqueue_script(
            'ai-virtual-fitting-login-modal',
            plugin_dir_url(__FILE__) . 'js/login-modal.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('ai-virtual-fitting-modern-script', 'ai_virtual_fitting_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_virtual_fitting_nonce'),
            'user_logged_in' => is_user_logged_in(),
            'checkout_url' => wc_get_checkout_url(),
            'register_url' => wp_registration_url(),
            'lost_password_url' => wp_lostpassword_url(),
            'messages' => array(
                'login_required' => __('Please log in to use virtual fitting.', 'ai-virtual-fitting'),
                'insufficient_credits' => __('You have insufficient credits. Please purchase more.', 'ai-virtual-fitting'),
                'upload_error' => __('Error uploading image. Please try again.', 'ai-virtual-fitting'),
                'processing_error' => __('Error processing virtual fitting. Please try again.', 'ai-virtual-fitting'),
                'select_product' => __('Please select a product to try on.', 'ai-virtual-fitting'),
                'upload_image' => __('Please upload your image first.', 'ai-virtual-fitting'),
                'cart_add_error' => __('Error adding credits to cart. Please try again.', 'ai-virtual-fitting'),
                'cart_clear_error' => __('Error clearing cart. Please try again.', 'ai-virtual-fitting')
            )
        ));
    }
    
    /**
     * Render virtual fitting page
     */
    public function render_virtual_fitting_page() {
        $current_user_id = get_current_user_id();
        $is_logged_in = is_user_logged_in();
        $credits = $is_logged_in ? $this->credit_manager->get_customer_credits($current_user_id) : 0;
        $free_credits = $is_logged_in ? $this->credit_manager->get_free_credits_remaining($current_user_id) : 0;
        
        // Get WooCommerce products for the slider (first page only)
        $result = $this->get_woocommerce_products(1, 20);
        $products = $result['products'];
        $has_more = $result['has_more'];
        $total_products = $result['total'];
        
        // Get WooCommerce categories for the dropdown
        $categories = $this->get_woocommerce_categories();
        
        // Debug: Log product count
        error_log('AI Virtual Fitting - Products loaded: ' . count($products) . ' of ' . $total_products);
        if (!empty($products)) {
            error_log('AI Virtual Fitting - First product: ' . json_encode($products[0]));
        }
        
        include plugin_dir_path(__FILE__) . 'modern-virtual-fitting-page.php';
    }
    
    /**
     * Get WooCommerce product categories
     */
    private function get_woocommerce_categories() {
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        $category_data = array();
        
        if (!is_wp_error($categories)) {
            foreach ($categories as $category) {
                $category_data[] = array(
                    'id' => $category->term_id,
                    'slug' => $category->slug,
                    'name' => $category->name,
                    'count' => $category->count
                );
            }
        }
        
        // Debug: Log categories
        error_log('AI Virtual Fitting - Categories loaded: ' . json_encode($category_data));
        
        return $category_data;
    }

    /**
     * Get WooCommerce products for virtual fitting
     * 
     * @param int $page Page number (default: 1)
     * @param int $per_page Products per page (default: 20)
     * @return array Array with 'products', 'has_more', and 'total' keys
     */
    private function get_woocommerce_products($page = 1, $per_page = 20) {
        // Get virtual credit product ID to exclude it
        $credit_product_id = get_option('ai_virtual_fitting_credit_product_id');
        
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $per_page,
            'post_status' => 'publish',
            'paged' => $page
        );
        
        // Exclude virtual credit product
        if ($credit_product_id) {
            $args['post__not_in'] = array($credit_product_id);
        }
        
        $query = new WP_Query($args);
        $products = $query->posts;
        $product_data = array();
        
        foreach ($products as $product_post) {
            $product = wc_get_product($product_post->ID);
            if ($product) {
                // Double-check: skip if this is the credit product
                if ($credit_product_id && $product->get_id() == $credit_product_id) {
                    continue;
                }
                
                $featured_image = wp_get_attachment_image_src(get_post_thumbnail_id($product->get_id()), 'large');
                $gallery_images = $this->get_product_gallery_images($product->get_id());
                
                // Combine featured image with gallery images
                $all_images = array();
                if ($featured_image) {
                    $all_images[] = $featured_image[0];
                }
                $all_images = array_merge($all_images, $gallery_images);
                
                // Get product categories
                $product_categories = wp_get_post_terms($product->get_id(), 'product_cat');
                $category_slugs = array();
                if (!is_wp_error($product_categories) && !empty($product_categories)) {
                    foreach ($product_categories as $cat) {
                        $category_slugs[] = $cat->slug;
                    }
                }
                
                $product_data[] = array(
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'price' => $product->get_price_html(),
                    'description' => $product->get_short_description() ?: wp_trim_words($product->get_description(), 20),
                    'image' => $featured_image ? array($featured_image[0]) : array(),
                    'gallery' => $all_images, // All images including featured
                    'categories' => $category_slugs // WooCommerce category slugs
                );
            }
        }
        
        // Calculate if there are more products
        $has_more = $query->max_num_pages > $page;
        $total = $query->found_posts;
        
        return array(
            'products' => $product_data,
            'has_more' => $has_more,
            'total' => $total
        );
    }
    
    /**
     * Get product gallery images
     */
    private function get_product_gallery_images($product_id) {
        $product = wc_get_product($product_id);
        $gallery_ids = $product->get_gallery_image_ids();
        $gallery_images = array();
        
        foreach ($gallery_ids as $image_id) {
            $image_url = wp_get_attachment_image_src($image_id, 'large');
            if ($image_url) {
                $gallery_images[] = $image_url[0];
            }
        }
        
        return $gallery_images;
    }
    
    /**
     * Handle image upload AJAX request
     */
    public function handle_image_upload() {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'], 'ai_virtual_fitting_nonce')) {
                $this->log_error('Security check failed for image upload', array(
                    'user_id' => get_current_user_id(),
                    'ip' => $this->get_client_ip()
                ));
                wp_send_json_error(array(
                    'message' => 'Security check failed',
                    'error_code' => 'SECURITY_FAILED'
                ));
            }
            
            // Check rate limit
            $user_id = get_current_user_id();
            if (!AI_Virtual_Fitting_Security_Manager::check_rate_limit('upload_image', $user_id)) {
                $this->log_error('Rate limit exceeded for image upload', array(
                    'user_id' => $user_id,
                    'ip' => $this->get_client_ip()
                ));
                wp_send_json_error(array(
                    'message' => 'Too many requests. Please wait a few minutes and try again.',
                    'error_code' => 'RATE_LIMIT_EXCEEDED'
                ));
            }
            
            // Check if user is logged in
            if (!is_user_logged_in()) {
                wp_send_json_error(array(
                    'message' => 'Please log in to upload images',
                    'error_code' => 'AUTH_REQUIRED'
                ));
            }
            
            // Check if file was uploaded
            if (!isset($_FILES['customer_image']) || $_FILES['customer_image']['error'] !== UPLOAD_ERR_OK) {
                $error_message = isset($_FILES['customer_image']) 
                    ? $this->get_upload_error_message($_FILES['customer_image']['error'])
                    : 'No image file uploaded';
                    
                $this->log_error('Image upload failed', array(
                    'user_id' => get_current_user_id(),
                    'upload_error' => $error_message,
                    'files_info' => isset($_FILES['customer_image']) ? $_FILES['customer_image'] : 'no_file'
                ));
                
                wp_send_json_error(array(
                    'message' => $error_message,
                    'error_code' => 'UPLOAD_FAILED'
                ));
            }
            
            // Validate uploaded image
            $validation_result = $this->image_processor->validate_uploaded_image($_FILES['customer_image']);
            if (is_wp_error($validation_result)) {
                $this->log_error('Image validation failed', array(
                    'user_id' => get_current_user_id(),
                    'validation_error' => $validation_result->get_error_message(),
                    'file_info' => array(
                        'name' => $_FILES['customer_image']['name'],
                        'size' => $_FILES['customer_image']['size'],
                        'type' => $_FILES['customer_image']['type']
                    )
                ));
                
                wp_send_json_error(array(
                    'message' => $validation_result->get_error_message(),
                    'error_code' => 'VALIDATION_FAILED'
                ));
            }
            
            // Move uploaded file to temporary location
            $upload_dir = wp_upload_dir();
            $temp_dir = $upload_dir['basedir'] . '/ai-virtual-fitting/temp/';
            
            if (!file_exists($temp_dir)) {
                if (!wp_mkdir_p($temp_dir)) {
                    $this->log_error('Failed to create temp directory', array(
                        'user_id' => get_current_user_id(),
                        'temp_dir' => $temp_dir
                    ));
                    wp_send_json_error(array(
                        'message' => 'Failed to create temporary directory',
                        'error_code' => 'TEMP_DIR_FAILED'
                    ));
                }
            }
            
            $file_extension = pathinfo($_FILES['customer_image']['name'], PATHINFO_EXTENSION);
            $user_id = get_current_user_id() ?: 'guest';
            // Add microseconds to ensure uniqueness even for rapid uploads
            $temp_filename = 'customer_' . $user_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
            $temp_filepath = $temp_dir . $temp_filename;
            
            if (!move_uploaded_file($_FILES['customer_image']['tmp_name'], $temp_filepath)) {
                $this->log_error('Failed to move uploaded file', array(
                    'user_id' => get_current_user_id(),
                    'temp_file' => $temp_filename,
                    'destination' => $temp_filepath
                ));
                wp_send_json_error(array(
                    'message' => 'Failed to save uploaded image',
                    'error_code' => 'SAVE_FAILED'
                ));
            }
            
            // Set proper file permissions
            chmod($temp_filepath, 0644);
            
            $this->log_info('Image uploaded successfully', array(
                'user_id' => get_current_user_id(),
                'temp_file' => $temp_filename,
                'file_size' => $_FILES['customer_image']['size']
            ));
            
            wp_send_json_success(array(
                'message' => 'Image uploaded successfully',
                'temp_file' => $temp_filename
            ));
            
        } catch (Exception $e) {
            $this->log_error('Exception in image upload', array(
                'user_id' => get_current_user_id(),
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            
            wp_send_json_error(array(
                'message' => 'An unexpected error occurred while uploading your image. Please try again.',
                'error_code' => 'UPLOAD_EXCEPTION'
            ));
        }
    }
    
    /**
     * Handle virtual fitting request
     */
    public function handle_fitting_request() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'ai_virtual_fitting_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        // Check rate limit
        $user_id = get_current_user_id();
        if (!AI_Virtual_Fitting_Security_Manager::check_rate_limit('process_fitting', $user_id)) {
            wp_send_json_error(array(
                'message' => 'Too many requests. Please wait a few minutes and try again.',
                'error_code' => 'RATE_LIMIT_EXCEEDED'
            ));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please log in to use virtual fitting'));
        }
        
        $user_id = get_current_user_id();
        
        // Check if user has credits
        $credits = $this->credit_manager->get_customer_credits($user_id);
        if ($credits <= 0) {
            wp_send_json_error(array(
                'message' => 'Insufficient credits',
                'credits' => 0
            ));
        }
        
        // Get request parameters
        $temp_filename = sanitize_text_field($_POST['temp_file']);
        $product_id = intval($_POST['product_id']);
        
        if (empty($temp_filename) || empty($product_id)) {
            wp_send_json_error(array('message' => 'Missing required parameters'));
        }
        
        // Get customer image path
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/ai-virtual-fitting/temp/';
        $customer_image_path = $temp_dir . $temp_filename;
        
        if (!file_exists($customer_image_path)) {
            wp_send_json_error(array('message' => 'Customer image not found'));
        }
        
        // Log customer image details for debugging
        if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
            error_log('AI Virtual Fitting - Processing Request: ' . json_encode(array(
                'user_id' => $user_id,
                'temp_filename' => $temp_filename,
                'customer_image_path' => $customer_image_path,
                'customer_image_exists' => file_exists($customer_image_path),
                'customer_image_size' => file_exists($customer_image_path) ? filesize($customer_image_path) : 0,
                'product_id' => $product_id
            )));
        }
        
        // Get product images
        $product_images = $this->get_product_images_for_ai($product_id);
        if (empty($product_images)) {
            wp_send_json_error(array('message' => 'Product images not found'));
        }
        
        // Process virtual fitting
        $result = $this->image_processor->process_virtual_fitting($customer_image_path, $product_images);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        // Check if processing was successful
        if (!$result['success']) {
            wp_send_json_error(array('message' => $result['error']));
        }
        
        // Deduct credit after successful processing
        $this->credit_manager->deduct_credit($user_id);
        $remaining_credits = $this->credit_manager->get_customer_credits($user_id);
        
        wp_send_json_success(array(
            'message' => 'Virtual fitting completed successfully',
            'result_image' => $result['result_image_url'], // Extract the URL from the result
            'credits' => $remaining_credits
        ));
    }
    
    /**
     * Get product images for AI processing
     */
    private function get_product_images_for_ai($product_id) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return array();
        }
        
        $images = array();
        
        // Get only the featured image for better AI focus
        $featured_image_id = get_post_thumbnail_id($product_id);
        if ($featured_image_id) {
            $featured_image_url = wp_get_attachment_image_src($featured_image_id, 'large');
            if ($featured_image_url) {
                $images[] = $featured_image_url[0];
            }
        }
        
        // Note: Removed gallery images to use only 1 product image for better AI performance
        // This reduces visual confusion and improves processing speed
        
        return $images;
    }
    
    /**
     * Handle image download request
     */
    public function handle_image_download() {
        // Verify nonce
        if (!wp_verify_nonce($_GET['nonce'], 'ai_virtual_fitting_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_die('Please log in to download images');
        }
        
        $result_filename = sanitize_text_field($_GET['result_file']);
        if (empty($result_filename)) {
            wp_die('Invalid download request');
        }
        
        $upload_dir = wp_upload_dir();
        $results_dir = $upload_dir['basedir'] . '/ai-virtual-fitting/results/';
        $result_filepath = $results_dir . $result_filename;
        
        if (!file_exists($result_filepath)) {
            wp_die('File not found');
        }
        
        // Set headers for download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="virtual-fitting-result.jpg"');
        header('Content-Length: ' . filesize($result_filepath));
        
        // Output file
        readfile($result_filepath);
        exit;
    }
    
    /**
     * Handle get products AJAX request
     */
    public function handle_get_products() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'ai_virtual_fitting_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        // Get pagination parameters
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
        
        // Get products with pagination
        $result = $this->get_woocommerce_products($page, $per_page);
        
        wp_send_json_success(array(
            'products' => $result['products'],
            'has_more' => $result['has_more'],
            'total' => $result['total'],
            'page' => $page
        ));
    }
    
    /**
     * Handle check credits AJAX request
     */
    public function handle_check_credits() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'ai_virtual_fitting_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_success(array('credits' => 0, 'logged_in' => false));
        }
        
        $user_id = get_current_user_id();
        $credits = $this->credit_manager->get_customer_credits($user_id);
        
        wp_send_json_success(array('credits' => $credits, 'logged_in' => true));
    }
    
    /**
     * Handle add credits to cart AJAX request for embedded checkout
     */
    public function handle_add_credits_to_cart() {
        error_log('AI Virtual Fitting: handle_add_credits_to_cart() called - User ID: ' . get_current_user_id());
        
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'], 'ai_virtual_fitting_nonce')) {
                $this->log_error('Security check failed for add credits to cart', array(
                    'user_id' => get_current_user_id(),
                    'ip' => $this->get_client_ip()
                ));
                wp_send_json_error(array(
                    'message' => 'Security check failed',
                    'error_code' => 'SECURITY_FAILED',
                    'retry_allowed' => false
                ));
            }
            
            // Check if WooCommerce is active
            if (!class_exists('WooCommerce')) {
                wp_send_json_error(array(
                    'message' => 'WooCommerce is not active',
                    'error_code' => 'WOOCOMMERCE_INACTIVE',
                    'retry_allowed' => false
                ));
            }
            
            // Initialize WooCommerce cart if not already done
            if (!WC()->cart) {
                wc_load_cart();
            }
            
            // Handle cart conflicts and validation
            $cart_validation_result = $this->validate_and_prepare_cart_for_credits();
            if (is_wp_error($cart_validation_result)) {
                wp_send_json_error(array(
                    'message' => $cart_validation_result->get_error_message(),
                    'error_code' => $cart_validation_result->get_error_code(),
                    'retry_allowed' => true,
                    'cart_action_required' => $cart_validation_result->get_error_data('cart_action')
                ));
            }
            
            // Get or create credits product
            $product_id = $this->woocommerce_integration->get_or_create_credits_product();
            
            if (!$product_id) {
                $this->log_error('Failed to get or create credits product', array(
                    'user_id' => get_current_user_id()
                ));
                wp_send_json_error(array(
                    'message' => 'Failed to create credits product. Please try again.',
                    'error_code' => 'PRODUCT_CREATION_FAILED',
                    'retry_allowed' => true
                ));
            }
            
            // Validate Stripe configuration before proceeding
            $payment_methods = $this->get_available_payment_methods();
            if (!$payment_methods['stripe_available']) {
                $this->log_error('Checkout blocked - Stripe not configured', array(
                    'user_id' => get_current_user_id(),
                    'error' => $payment_methods['error']
                ));
                
                wp_send_json_success(array(
                    'message' => 'Stripe configuration required',
                    'cart_total_text' => '$10.00',
                    'credits_per_package' => get_option('ai_virtual_fitting_credits_per_package', 20), // Add credits amount
                    'payment_methods' => $payment_methods,
                    'stripe_configuration_required' => true
                ));
            }
            
            // Validate product before adding to cart
            $product_validation = $this->validate_credits_product($product_id);
            if (is_wp_error($product_validation)) {
                wp_send_json_error(array(
                    'message' => $product_validation->get_error_message(),
                    'error_code' => $product_validation->get_error_code(),
                    'retry_allowed' => false
                ));
            }
            
            // Check if credits product is already in cart
            $existing_cart_item = $this->find_credits_product_in_cart($product_id);
            if ($existing_cart_item) {
                // Credits already in cart - return success with existing data
                error_log('AI Virtual Fitting: Credits already in cart - returning existing cart data');
                error_log('AI Virtual Fitting: Cart item key: ' . $existing_cart_item['key']);
                error_log('AI Virtual Fitting: Cart quantity: ' . $existing_cart_item['item']['quantity']);
                
                WC()->cart->calculate_totals();
                
                wp_send_json_success(array(
                    'message' => 'Credits already in cart',
                    'cart_item_key' => $existing_cart_item['key'],
                    'cart_total' => WC()->cart->get_total(),
                    'cart_total_text' => html_entity_decode(wp_strip_all_tags(WC()->cart->get_total()), ENT_QUOTES, 'UTF-8'), // Plain text version for React
                    'cart_count' => WC()->cart->get_cart_contents_count(),
                    'product_id' => $product_id,
                    'credits_per_package' => get_option('ai_virtual_fitting_credits_per_package', 20), // Add credits amount
                    'payment_methods' => $this->get_available_payment_methods(), // Add available payment methods
                    'already_in_cart' => true
                ));
            }
            
            // Add credits product to cart
            error_log('AI Virtual Fitting: Adding credits to cart - Product ID: ' . $product_id);
            $cart_item_key = WC()->cart->add_to_cart($product_id, 1);
            
            if (!$cart_item_key) {
                // Get WooCommerce notices for specific error details
                $notices = wc_get_notices('error');
                $error_message = 'Failed to add credits to cart';
                
                if (!empty($notices)) {
                    $error_messages = array();
                    foreach ($notices as $notice) {
                        $error_messages[] = $notice['notice'];
                    }
                    $error_message = implode(' ', $error_messages);
                    wc_clear_notices();
                }
                
                error_log('AI Virtual Fitting: Failed to add to cart - ' . $error_message);
                
                $this->log_error('Failed to add credits to cart', array(
                    'user_id' => get_current_user_id(),
                    'product_id' => $product_id,
                    'error_message' => $error_message,
                    'cart_contents' => WC()->cart->get_cart_contents_count()
                ));
                
                wp_send_json_error(array(
                    'message' => $error_message,
                    'error_code' => 'CART_ADD_FAILED',
                    'retry_allowed' => true
                ));
            }
            
            error_log('AI Virtual Fitting: Successfully added to cart - Cart item key: ' . $cart_item_key);
            
            // Calculate cart totals and validate
            WC()->cart->calculate_totals();
            $cart_total = WC()->cart->get_total();
            $cart_count = WC()->cart->get_cart_contents_count();
            
            // Validate cart state after addition
            if ($cart_count === 0 || empty($cart_total)) {
                $this->log_error('Cart validation failed after adding credits', array(
                    'user_id' => get_current_user_id(),
                    'product_id' => $product_id,
                    'cart_item_key' => $cart_item_key,
                    'cart_count' => $cart_count,
                    'cart_total' => $cart_total
                ));
                
                wp_send_json_error(array(
                    'message' => 'Cart validation failed. Please refresh and try again.',
                    'error_code' => 'CART_VALIDATION_FAILED',
                    'retry_allowed' => true
                ));
            }
            
            $this->log_info('Credits added to cart successfully', array(
                'user_id' => get_current_user_id(),
                'product_id' => $product_id,
                'cart_item_key' => $cart_item_key,
                'cart_total' => $cart_total,
                'cart_count' => $cart_count
            ));
            
            wp_send_json_success(array(
                'message' => 'Credits added to cart successfully',
                'cart_item_key' => $cart_item_key,
                'cart_total' => $cart_total,
                'cart_total_text' => html_entity_decode(wp_strip_all_tags($cart_total), ENT_QUOTES, 'UTF-8'), // Plain text version for React
                'cart_count' => $cart_count,
                'product_id' => $product_id,
                'credits_per_package' => get_option('ai_virtual_fitting_credits_per_package', 20), // Add credits amount
                'payment_methods' => $this->get_available_payment_methods(), // Add available payment methods
                'already_in_cart' => false
            ));
            
        } catch (Exception $e) {
            $this->log_error('Exception in add credits to cart', array(
                'user_id' => get_current_user_id(),
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            
            wp_send_json_error(array(
                'message' => 'An unexpected error occurred while adding credits to cart. Please try again.',
                'error_code' => 'ADD_CART_EXCEPTION',
                'retry_allowed' => true
            ));
        }
    }
    
    /**
     * Validate and prepare cart for credits addition
     */
    private function validate_and_prepare_cart_for_credits() {
        // Check if cart is accessible
        if (!WC()->cart) {
            return new WP_Error('CART_NOT_AVAILABLE', 'Shopping cart is not available. Please refresh the page.');
        }
        
        // Get current cart contents
        $cart_contents = WC()->cart->get_cart();
        $credits_product_id = $this->woocommerce_integration->get_credits_product_id();
        
        // Check for cart conflicts
        $has_non_credits_items = false;
        $has_credits_items = false;
        
        foreach ($cart_contents as $cart_item_key => $cart_item) {
            if ($this->woocommerce_integration->is_credits_product($cart_item['product_id'])) {
                $has_credits_items = true;
            } else {
                $has_non_credits_items = true;
            }
        }
        
        // Handle cart conflicts
        if ($has_non_credits_items && !$has_credits_items) {
            // Cart has other products - need user confirmation to clear
            return new WP_Error(
                'CART_CONFLICT_OTHER_PRODUCTS',
                'Your cart contains other items. Adding credits will clear your current cart. Do you want to continue?',
                array('cart_action' => 'clear_and_add')
            );
        }
        
        if ($has_credits_items) {
            // Credits already in cart - this is handled in the main function
            return true;
        }
        
        // Clear cart if it has non-credits items (user confirmed via retry)
        if ($has_non_credits_items) {
            WC()->cart->empty_cart();
        }
        
        return true;
    }
    
    /**
     * Validate credits product
     */
    private function validate_credits_product($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return new WP_Error('PRODUCT_NOT_FOUND', 'Credits product not found. Please contact support.');
        }
        
        if (!$product->is_purchasable()) {
            return new WP_Error('PRODUCT_NOT_PURCHASABLE', 'Credits product is not available for purchase.');
        }
        
        if ($product->get_status() !== 'publish') {
            return new WP_Error('PRODUCT_NOT_PUBLISHED', 'Credits product is not available.');
        }
        
        return true;
    }
    
    /**
     * Find credits product in cart
     */
    private function find_credits_product_in_cart($product_id) {
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if ($cart_item['product_id'] == $product_id) {
                return array(
                    'key' => $cart_item_key,
                    'item' => $cart_item
                );
            }
        }
        return false;
    }
    
    /**
     * Handle clear cart AJAX request for embedded checkout
     */
    public function handle_clear_cart() {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'], 'ai_virtual_fitting_nonce')) {
                $this->log_error('Security check failed for clear cart', array(
                    'user_id' => get_current_user_id(),
                    'ip' => $this->get_client_ip()
                ));
                wp_send_json_error(array(
                    'message' => 'Security check failed',
                    'error_code' => 'SECURITY_FAILED',
                    'retry_allowed' => false
                ));
            }
            
            // Check if WooCommerce is active
            if (!class_exists('WooCommerce')) {
                wp_send_json_error(array(
                    'message' => 'WooCommerce is not active',
                    'error_code' => 'WOOCOMMERCE_INACTIVE',
                    'retry_allowed' => false
                ));
            }
            
            // Initialize WooCommerce cart if not already done
            if (!WC()->cart) {
                wc_load_cart();
            }
            
            // Get current cart state for logging and validation
            $cart_contents_before = WC()->cart->get_cart_contents_count();
            $cart_items_before = WC()->cart->get_cart();
            
            // Handle empty cart scenario
            if ($cart_contents_before === 0) {
                wp_send_json_success(array(
                    'message' => 'Cart is already empty',
                    'cart_count' => 0,
                    'cleared_items' => 0,
                    'was_empty' => true
                ));
            }
            
            // Perform cart clearing with validation
            $clear_result = $this->clear_cart_with_validation($cart_items_before);
            
            if (is_wp_error($clear_result)) {
                wp_send_json_error(array(
                    'message' => $clear_result->get_error_message(),
                    'error_code' => $clear_result->get_error_code(),
                    'retry_allowed' => true
                ));
            }
            
            // Recalculate cart totals
            WC()->cart->calculate_totals();
            $cart_contents_after = WC()->cart->get_cart_contents_count();
            
            // Validate that cart was actually cleared
            if ($cart_contents_after > 0) {
                $this->log_error('Cart clearing validation failed', array(
                    'user_id' => get_current_user_id(),
                    'cart_contents_before' => $cart_contents_before,
                    'cart_contents_after' => $cart_contents_after,
                    'remaining_items' => WC()->cart->get_cart()
                ));
                
                wp_send_json_error(array(
                    'message' => 'Failed to clear cart completely. Please refresh and try again.',
                    'error_code' => 'CART_CLEAR_VALIDATION_FAILED',
                    'retry_allowed' => true
                ));
            }
            
            $this->log_info('Cart cleared successfully', array(
                'user_id' => get_current_user_id(),
                'cart_contents_before' => $cart_contents_before,
                'cart_contents_after' => $cart_contents_after,
                'cleared_items' => $cart_contents_before - $cart_contents_after
            ));
            
            wp_send_json_success(array(
                'message' => 'Cart cleared successfully',
                'cart_count' => $cart_contents_after,
                'cleared_items' => $cart_contents_before - $cart_contents_after,
                'was_empty' => false
            ));
            
        } catch (Exception $e) {
            $this->log_error('Exception in clear cart', array(
                'user_id' => get_current_user_id(),
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            
            wp_send_json_error(array(
                'message' => 'An unexpected error occurred while clearing cart. Please try again.',
                'error_code' => 'CLEAR_CART_EXCEPTION',
                'retry_allowed' => true
            ));
        }
    }
    
    /**
     * Clear cart with validation and selective clearing
     */
    private function clear_cart_with_validation($cart_items_before) {
        try {
            $credits_product_id = $this->woocommerce_integration->get_credits_product_id();
            $has_non_credits_items = false;
            $credits_items_to_remove = array();
            $other_items_to_preserve = array();
            
            // Analyze cart contents
            foreach ($cart_items_before as $cart_item_key => $cart_item) {
                if ($this->woocommerce_integration->is_credits_product($cart_item['product_id'])) {
                    $credits_items_to_remove[] = $cart_item_key;
                } else {
                    $has_non_credits_items = true;
                    $other_items_to_preserve[] = array(
                        'key' => $cart_item_key,
                        'product_id' => $cart_item['product_id'],
                        'quantity' => $cart_item['quantity']
                    );
                }
            }
            
            // Determine clearing strategy
            if ($has_non_credits_items && !empty($other_items_to_preserve)) {
                // Only remove credits products, preserve other items
                foreach ($credits_items_to_remove as $cart_item_key) {
                    $remove_result = WC()->cart->remove_cart_item($cart_item_key);
                    if (!$remove_result) {
                        return new WP_Error(
                            'SELECTIVE_CLEAR_FAILED',
                            'Failed to remove credits from cart. Please try again.'
                        );
                    }
                }
            } else {
                // Safe to clear entire cart (only contains credits or is empty)
                WC()->cart->empty_cart();
            }
            
            return true;
            
        } catch (Exception $e) {
            return new WP_Error(
                'CART_CLEAR_EXCEPTION',
                'Error occurred while clearing cart: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Handle load checkout AJAX request for embedded checkout
     */
    public function handle_load_checkout() {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'], 'ai_virtual_fitting_nonce')) {
                $this->log_error('Security check failed for load checkout', array(
                    'user_id' => get_current_user_id(),
                    'ip' => $this->get_client_ip()
                ));
                wp_send_json_error(array(
                    'message' => 'Security check failed',
                    'error_code' => 'SECURITY_FAILED',
                    'retry_allowed' => false
                ));
            }
            
            // Check if WooCommerce is active
            if (!class_exists('WooCommerce')) {
                wp_send_json_error(array(
                    'message' => 'WooCommerce is not active',
                    'error_code' => 'WOOCOMMERCE_INACTIVE',
                    'retry_allowed' => false
                ));
            }
            
            // Initialize WooCommerce cart if not already done
            if (!WC()->cart) {
                wc_load_cart();
            }
            
            // Handle empty cart scenario with recovery
            if (WC()->cart->is_empty()) {
                $recovery_result = $this->recover_empty_cart_for_checkout();
                if (is_wp_error($recovery_result)) {
                    wp_send_json_error(array(
                        'message' => $recovery_result->get_error_message(),
                        'error_code' => $recovery_result->get_error_code(),
                        'retry_allowed' => true,
                        'recovery_action' => 'add_credits_to_cart'
                    ));
                }
            }
            
            // Validate cart contents for checkout
            $cart_validation = $this->validate_cart_for_checkout();
            if (is_wp_error($cart_validation)) {
                wp_send_json_error(array(
                    'message' => $cart_validation->get_error_message(),
                    'error_code' => $cart_validation->get_error_code(),
                    'retry_allowed' => true
                ));
            }
            
            // Start output buffering to capture checkout form HTML
            ob_start();
            
            // Set up WooCommerce checkout context
            if (!defined('WOOCOMMERCE_CHECKOUT')) {
                define('WOOCOMMERCE_CHECKOUT', true);
            }
            
            // Get checkout object
            $checkout = WC()->checkout();
            
            // Ensure checkout fields are loaded
            $checkout->checkout_fields = $checkout->get_checkout_fields();
            
            // Load checkout template with minimal styling for modal
            echo '<div class="woocommerce-checkout-wrapper">';
            
            // Display any notices
            if (wc_notice_count() > 0) {
                wc_print_notices();
            }
            
            // Display checkout form
            echo '<form name="checkout" method="post" class="checkout woocommerce-checkout" action="' . esc_url(wc_get_checkout_url()) . '" enctype="multipart/form-data">';
            
            // Checkout fields
            do_action('woocommerce_checkout_before_customer_details');
            
            echo '<div class="col2-set" id="customer_details">';
            echo '<div class="col-1">';
            
            // Billing fields
            $checkout->get_checkout_fields('billing');
            
            do_action('woocommerce_checkout_billing');
            
            echo '</div>';
            echo '<div class="col-2">';
            
            // Shipping fields (if needed)
            if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) {
                do_action('woocommerce_checkout_shipping');
            }
            
            echo '</div>';
            echo '</div>';
            
            do_action('woocommerce_checkout_after_customer_details');
            
            // Order review
            echo '<h3 id="order_review_heading">' . esc_html__('Your order', 'woocommerce') . '</h3>';
            
            do_action('woocommerce_checkout_before_order_review');
            
            echo '<div id="order_review" class="woocommerce-checkout-review-order">';
            woocommerce_order_review();
            echo '</div>';
            
            do_action('woocommerce_checkout_after_order_review');
            
            echo '</form>';
            echo '</div>';
            
            // Get the checkout HTML
            $checkout_html = ob_get_clean();
            
            // Add modal-specific CSS
            $modal_css = '
            <style>
            .woocommerce-checkout-wrapper {
                max-height: 70vh;
                overflow-y: auto;
                padding: 20px;
            }
            .woocommerce-checkout-wrapper .col2-set {
                display: flex;
                gap: 20px;
                margin-bottom: 20px;
            }
            .woocommerce-checkout-wrapper .col-1,
            .woocommerce-checkout-wrapper .col-2 {
                flex: 1;
            }
            .woocommerce-checkout-wrapper .form-row {
                margin-bottom: 15px;
            }
            .woocommerce-checkout-wrapper input[type="text"],
            .woocommerce-checkout-wrapper input[type="email"],
            .woocommerce-checkout-wrapper input[type="tel"],
            .woocommerce-checkout-wrapper select,
            .woocommerce-checkout-wrapper textarea {
                width: 100%;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
            }
            .woocommerce-checkout-wrapper label {
                display: block;
                margin-bottom: 5px;
                font-weight: 500;
            }
            .woocommerce-checkout-wrapper .required {
                color: #e74c3c;
            }
            .woocommerce-checkout-wrapper #order_review {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 8px;
                margin-top: 20px;
            }
            .woocommerce-checkout-wrapper .place-order {
                text-align: center;
                margin-top: 20px;
            }
            .woocommerce-checkout-wrapper #place_order {
                background: #4a90e2;
                color: white;
                border: none;
                padding: 15px 30px;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                width: 100%;
            }
            .woocommerce-checkout-wrapper #place_order:hover {
                background: #357abd;
            }
            @media (max-width: 768px) {
                .woocommerce-checkout-wrapper .col2-set {
                    flex-direction: column;
                }
            }
            </style>';
            
            $this->log_info('Checkout form loaded successfully', array(
                'user_id' => get_current_user_id(),
                'cart_count' => WC()->cart->get_cart_contents_count(),
                'cart_total' => WC()->cart->get_total()
            ));
            
            wp_send_json_success(array(
                'message' => 'Checkout form loaded successfully',
                'checkout_html' => $modal_css . $checkout_html,
                'cart_total' => WC()->cart->get_total(),
                'cart_count' => WC()->cart->get_cart_contents_count()
            ));
            
        } catch (Exception $e) {
            $this->log_error('Exception in load checkout', array(
                'user_id' => get_current_user_id(),
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            
            wp_send_json_error(array(
                'message' => 'An unexpected error occurred while loading checkout. Please try again.',
                'error_code' => 'LOAD_CHECKOUT_EXCEPTION',
                'retry_allowed' => true
            ));
        }
    }
    
    /**
     * Recover empty cart for checkout
     */
    private function recover_empty_cart_for_checkout() {
        try {
            // Get or create credits product
            $product_id = $this->woocommerce_integration->get_or_create_credits_product();
            
            if (!$product_id) {
                return new WP_Error(
                    'PRODUCT_RECOVERY_FAILED',
                    'Unable to recover cart. Credits product not available.'
                );
            }
            
            // Add credits product to cart
            $cart_item_key = WC()->cart->add_to_cart($product_id, 1);
            
            if (!$cart_item_key) {
                return new WP_Error(
                    'CART_RECOVERY_FAILED',
                    'Unable to recover cart. Please refresh and try again.'
                );
            }
            
            // Calculate totals
            WC()->cart->calculate_totals();
            
            $this->log_info('Empty cart recovered for checkout', array(
                'user_id' => get_current_user_id(),
                'product_id' => $product_id,
                'cart_item_key' => $cart_item_key
            ));
            
            return true;
            
        } catch (Exception $e) {
            return new WP_Error(
                'CART_RECOVERY_EXCEPTION',
                'Error recovering cart: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Validate cart for checkout
     */
    private function validate_cart_for_checkout() {
        // Check if cart is still empty after recovery attempts
        if (WC()->cart->is_empty()) {
            return new WP_Error(
                'EMPTY_CART_VALIDATION',
                'Cart is empty. Please add credits and try again.'
            );
        }
        
        // Check if cart has valid items
        $cart_contents = WC()->cart->get_cart();
        $valid_items = 0;
        
        foreach ($cart_contents as $cart_item) {
            $product = wc_get_product($cart_item['product_id']);
            if ($product && $product->is_purchasable()) {
                $valid_items++;
            }
        }
        
        if ($valid_items === 0) {
            return new WP_Error(
                'NO_VALID_ITEMS',
                'Cart contains no valid items. Please refresh and try again.'
            );
        }
        
        // Check cart totals
        WC()->cart->calculate_totals();
        $cart_total = WC()->cart->get_total();
        
        if (empty($cart_total) || $cart_total <= 0) {
            return new WP_Error(
                'INVALID_CART_TOTAL',
                'Cart total is invalid. Please refresh and try again.'
            );
        }
        
        return true;
    }
    
    /**
     * Handle process checkout AJAX request for embedded checkout
     */
    public function handle_process_checkout() {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'], 'ai_virtual_fitting_nonce')) {
                $this->log_error('Security check failed for process checkout', array(
                    'user_id' => get_current_user_id(),
                    'ip' => $this->get_client_ip()
                ));
                wp_send_json_error(array(
                    'message' => 'Security check failed',
                    'error_code' => 'SECURITY_FAILED',
                    'retry_allowed' => false
                ));
            }
            
            // Check if WooCommerce is active
            if (!class_exists('WooCommerce')) {
                wp_send_json_error(array(
                    'message' => 'WooCommerce is not active',
                    'error_code' => 'WOOCOMMERCE_INACTIVE',
                    'retry_allowed' => false
                ));
            }
            
            // Ensure cart has items
            if (WC()->cart->is_empty()) {
                wp_send_json_error(array(
                    'message' => 'Cart is empty. Please refresh and try again.',
                    'error_code' => 'EMPTY_CART',
                    'retry_allowed' => true
                ));
            }
            
            // Set up checkout context
            if (!defined('WOOCOMMERCE_CHECKOUT')) {
                define('WOOCOMMERCE_CHECKOUT', true);
            }
            
            // Get checkout object
            $checkout = WC()->checkout();
            
            // Validate checkout fields using WooCommerce's public validation
            $validation_errors = new WP_Error();
            
            // Use WooCommerce's built-in validation instead of calling protected method
            // Validate required fields manually
            $required_fields = array(
                'billing_first_name' => 'First name',
                'billing_last_name' => 'Last name', 
                'billing_email' => 'Email address',
                'billing_phone' => 'Phone number',
                'billing_address_1' => 'Address',
                'billing_city' => 'City',
                'billing_postcode' => 'Postal code',
                'payment_method' => 'Payment method'
            );
            
            foreach ($required_fields as $field => $label) {
                if (empty($_POST[$field])) {
                    $validation_errors->add('required_field', sprintf('%s is a required field.', $label));
                }
            }
            
            // Validate email format
            if (!empty($_POST['billing_email']) && !is_email($_POST['billing_email'])) {
                $validation_errors->add('invalid_email', 'Please enter a valid email address.');
            }
            
            // Validate payment method
            $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
            $payment_method = sanitize_text_field($_POST['payment_method']);
            
            if (!isset($available_gateways[$payment_method])) {
                $validation_errors->add('invalid_payment_method', 'Invalid payment method selected.');
            } else {
                // Validate payment method specific fields
                $gateway = $available_gateways[$payment_method];
                if (method_exists($gateway, 'validate_fields')) {
                    $gateway_validation = $gateway->validate_fields();
                    if (!$gateway_validation) {
                        // Gateway validation failed, errors are already added to WC notices
                        $validation_errors->add('gateway_validation', 'Payment method validation failed.');
                    }
                }
            }
            
            // Check for validation errors
            if (wc_notice_count('error') > 0 || $validation_errors->has_errors()) {
                $notices = wc_get_notices('error');
                $error_messages = array();
                
                // Collect WooCommerce notices
                foreach ($notices as $notice) {
                    $error_messages[] = $notice['notice'];
                }
                
                // Collect validation errors
                foreach ($validation_errors->get_error_messages() as $error) {
                    $error_messages[] = $error;
                }
                
                wc_clear_notices();
                
                wp_send_json_error(array(
                    'message' => implode(' ', $error_messages),
                    'error_code' => 'VALIDATION_FAILED',
                    'retry_allowed' => true,
                    'field_errors' => $this->extract_field_errors($error_messages)
                ));
            }
            
            // Process the order
            $order_id = $checkout->create_order($_POST);
            
            if (is_wp_error($order_id)) {
                $this->log_error('Order creation failed', array(
                    'user_id' => get_current_user_id(),
                    'error' => $order_id->get_error_message(),
                    'post_data' => $this->sanitize_post_data_for_logging($_POST)
                ));
                
                wp_send_json_error(array(
                    'message' => 'Order creation failed: ' . $order_id->get_error_message(),
                    'error_code' => 'ORDER_CREATION_FAILED',
                    'retry_allowed' => true
                ));
            }
            
            // Get the order object
            $order = wc_get_order($order_id);
            
            if (!$order) {
                wp_send_json_error(array(
                    'message' => 'Failed to retrieve order',
                    'error_code' => 'ORDER_RETRIEVAL_FAILED',
                    'retry_allowed' => true
                ));
            }
            
            // Process payment with enhanced error handling
            $payment_result = $this->process_payment_with_error_handling($order, $_POST);
            
            if ($payment_result['success']) {
                // Payment successful - complete the order
                $order->payment_complete();
                
                // Process credits addition through WooCommerce integration
                $this->woocommerce_integration->handle_payment_complete($order_id);
                
                // Get updated credits for user
                $user_id = get_current_user_id();
                $updated_credits = $user_id ? $this->credit_manager->get_customer_credits($user_id) : 0;
                
                // Clear cart
                WC()->cart->empty_cart();
                
                $this->log_info('Checkout processed successfully', array(
                    'user_id' => $user_id,
                    'order_id' => $order_id,
                    'payment_method' => $order->get_payment_method(),
                    'updated_credits' => $updated_credits
                ));
                
                wp_send_json_success(array(
                    'message' => 'Payment processed successfully',
                    'order_id' => $order_id,
                    'credits' => $updated_credits,
                    'redirect_url' => $payment_result['redirect_url']
                ));
                
            } else {
                // Payment failed - handle specific error types
                $this->handle_payment_failure($order, $payment_result);
            }
            
        } catch (Exception $e) {
            $this->log_error('Exception in process checkout', array(
                'user_id' => get_current_user_id(),
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            
            wp_send_json_error(array(
                'message' => 'An unexpected error occurred during checkout processing. Please try again.',
                'error_code' => 'CHECKOUT_EXCEPTION',
                'retry_allowed' => true
            ));
        }
    }
    
    /**
     * Process payment with enhanced error handling
     */
    private function process_payment_with_error_handling($order, $post_data) {
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
        $payment_method = isset($post_data['payment_method']) ? sanitize_text_field($post_data['payment_method']) : '';
        
        if (empty($payment_method) || !isset($available_gateways[$payment_method])) {
            return array(
                'success' => false,
                'error_code' => 'INVALID_PAYMENT_METHOD',
                'message' => 'Invalid payment method selected. Please choose a valid payment option.',
                'retry_allowed' => true
            );
        }
        
        // Set payment method
        $order->set_payment_method($available_gateways[$payment_method]);
        $order->save();
        
        try {
            // Process payment with timeout handling
            $payment_result = $this->process_payment_with_timeout($available_gateways[$payment_method], $order->get_id());
            
            if (isset($payment_result['result']) && $payment_result['result'] === 'success') {
                return array(
                    'success' => true,
                    'redirect_url' => isset($payment_result['redirect']) ? $payment_result['redirect'] : null
                );
            } else {
                // Extract specific error message from payment gateway
                $error_message = $this->extract_payment_error_message($payment_result, $payment_method);
                
                return array(
                    'success' => false,
                    'error_code' => 'PAYMENT_GATEWAY_ERROR',
                    'message' => $error_message,
                    'retry_allowed' => $this->is_payment_error_retryable($payment_result, $payment_method),
                    'gateway_code' => isset($payment_result['error_code']) ? $payment_result['error_code'] : null
                );
            }
            
        } catch (Exception $e) {
            $this->log_error('Payment gateway exception', array(
                'order_id' => $order->get_id(),
                'payment_method' => $payment_method,
                'exception' => $e->getMessage()
            ));
            
            return array(
                'success' => false,
                'error_code' => 'PAYMENT_GATEWAY_EXCEPTION',
                'message' => 'Payment processing encountered an error. Please try again or use a different payment method.',
                'retry_allowed' => true
            );
        }
    }
    
    /**
     * Process payment with timeout handling
     */
    private function process_payment_with_timeout($gateway, $order_id) {
        // Set a reasonable timeout for payment processing
        $original_timeout = ini_get('max_execution_time');
        set_time_limit(60); // 60 seconds for payment processing
        
        try {
            $result = $gateway->process_payment($order_id);
            set_time_limit($original_timeout); // Restore original timeout
            return $result;
        } catch (Exception $e) {
            set_time_limit($original_timeout); // Restore original timeout
            throw $e;
        }
    }
    
    /**
     * Extract payment error message from gateway response
     */
    private function extract_payment_error_message($payment_result, $payment_method) {
        // Default error message
        $default_message = 'Payment failed. Please check your payment details and try again.';
        
        // Check for specific error messages
        if (isset($payment_result['messages'])) {
            if (is_array($payment_result['messages'])) {
                return implode(' ', $payment_result['messages']);
            } else {
                return $payment_result['messages'];
            }
        }
        
        if (isset($payment_result['error'])) {
            return $payment_result['error'];
        }
        
        if (isset($payment_result['message'])) {
            return $payment_result['message'];
        }
        
        // Gateway-specific error handling
        switch ($payment_method) {
            case 'stripe':
                return 'Credit card payment failed. Please check your card details and try again.';
            case 'paypal':
                return 'PayPal payment failed. Please check your PayPal account and try again.';
            case 'bacs':
                return 'Bank transfer setup failed. Please try again or contact support.';
            default:
                return $default_message;
        }
    }
    
    /**
     * Determine if payment error is retryable
     */
    private function is_payment_error_retryable($payment_result, $payment_method) {
        // Check for specific non-retryable errors
        if (isset($payment_result['error_code'])) {
            $non_retryable_codes = array(
                'card_declined_permanently',
                'insufficient_funds',
                'invalid_account',
                'account_closed',
                'fraud_detected'
            );
            
            if (in_array($payment_result['error_code'], $non_retryable_codes)) {
                return false;
            }
        }
        
        // Check for specific error messages that indicate non-retryable errors
        $error_message = strtolower($this->extract_payment_error_message($payment_result, $payment_method));
        $non_retryable_keywords = array(
            'insufficient funds',
            'card declined',
            'invalid card',
            'expired card',
            'fraud',
            'blocked'
        );
        
        foreach ($non_retryable_keywords as $keyword) {
            if (strpos($error_message, $keyword) !== false) {
                return false;
            }
        }
        
        // Default to retryable for temporary issues
        return true;
    }
    
    /**
     * Handle payment failure with specific error responses
     */
    private function handle_payment_failure($order, $payment_result) {
        $order_id = $order->get_id();
        $payment_method = $order->get_payment_method();
        
        $this->log_error('Payment processing failed', array(
            'user_id' => get_current_user_id(),
            'order_id' => $order_id,
            'payment_method' => $payment_method,
            'error_code' => $payment_result['error_code'],
            'error_message' => $payment_result['message'],
            'retry_allowed' => $payment_result['retry_allowed']
        ));
        
        // Update order status based on error type
        if ($payment_result['retry_allowed']) {
            $order->update_status('pending', 'Payment failed - retry allowed: ' . $payment_result['message']);
        } else {
            $order->update_status('cancelled', 'Payment failed - not retryable: ' . $payment_result['message']);
        }
        
        wp_send_json_error(array(
            'message' => $payment_result['message'],
            'error_code' => $payment_result['error_code'],
            'retry_allowed' => $payment_result['retry_allowed'],
            'gateway_code' => isset($payment_result['gateway_code']) ? $payment_result['gateway_code'] : null,
            'order_id' => $order_id
        ));
    }
    
    /**
     * Extract field-specific errors for frontend validation
     */
    private function extract_field_errors($error_messages) {
        $field_errors = array();
        
        foreach ($error_messages as $message) {
            // Common field error patterns
            if (strpos($message, 'email') !== false) {
                $field_errors['billing_email'] = $message;
            } elseif (strpos($message, 'phone') !== false) {
                $field_errors['billing_phone'] = $message;
            } elseif (strpos($message, 'first name') !== false) {
                $field_errors['billing_first_name'] = $message;
            } elseif (strpos($message, 'last name') !== false) {
                $field_errors['billing_last_name'] = $message;
            } elseif (strpos($message, 'address') !== false) {
                $field_errors['billing_address_1'] = $message;
            } elseif (strpos($message, 'city') !== false) {
                $field_errors['billing_city'] = $message;
            } elseif (strpos($message, 'postcode') !== false || strpos($message, 'zip') !== false) {
                $field_errors['billing_postcode'] = $message;
            }
        }
        
        return $field_errors;
    }
    
    /**
     * Sanitize POST data for logging (remove sensitive information)
     */
    private function sanitize_post_data_for_logging($post_data) {
        $sanitized = $post_data;
        
        // Remove sensitive payment information
        $sensitive_fields = array(
            'billing_first_name',
            'billing_last_name',
            'billing_email',
            'billing_phone',
            'billing_address_1',
            'billing_address_2',
            'billing_city',
            'billing_postcode',
            'billing_state',
            'billing_country'
        );
        
        foreach ($sensitive_fields as $field) {
            if (isset($sanitized[$field])) {
                $sanitized[$field] = '[REDACTED]';
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Handle express checkout (Apple Pay / Google Pay) AJAX request
     */
    public function handle_process_express_checkout() {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'], 'ai_virtual_fitting_nonce')) {
                $this->log_error('Security check failed for express checkout', array(
                    'user_id' => get_current_user_id(),
                    'ip' => $this->get_client_ip()
                ));
                wp_send_json_error(array(
                    'message' => 'Security check failed',
                    'error_code' => 'SECURITY_FAILED',
                    'retry_allowed' => false
                ));
            }
            
            // Check if WooCommerce is active
            if (!class_exists('WooCommerce')) {
                wp_send_json_error(array(
                    'message' => 'WooCommerce is not active',
                    'error_code' => 'WOOCOMMERCE_INACTIVE',
                    'retry_allowed' => false
                ));
            }
            
            // Ensure cart has items
            if (WC()->cart->is_empty()) {
                wp_send_json_error(array(
                    'message' => 'Cart is empty. Please refresh and try again.',
                    'error_code' => 'EMPTY_CART',
                    'retry_allowed' => true
                ));
            }
            
            // Get payment method ID from Stripe
            $payment_method_id = sanitize_text_field($_POST['payment_method_id']);
            $payer_email = sanitize_email($_POST['payer_email']);
            $payer_name = sanitize_text_field($_POST['payer_name']);
            $payer_phone = sanitize_text_field($_POST['payer_phone']);
            $shipping_address_json = isset($_POST['shipping_address']) ? $_POST['shipping_address'] : '';
            
            if (empty($payment_method_id)) {
                wp_send_json_error(array(
                    'message' => 'Payment method ID is required',
                    'error_code' => 'MISSING_PAYMENT_METHOD',
                    'retry_allowed' => false
                ));
            }
            
            // Parse shipping address
            $shipping_address = null;
            if (!empty($shipping_address_json)) {
                $shipping_address = json_decode(stripslashes($shipping_address_json), true);
            }
            
            // Split name into first and last
            $name_parts = explode(' ', $payer_name, 2);
            $first_name = $name_parts[0];
            $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
            
            // Create WooCommerce order
            $order = wc_create_order();
            
            if (is_wp_error($order)) {
                $this->log_error('Express checkout order creation failed', array(
                    'user_id' => get_current_user_id(),
                    'error' => $order->get_error_message()
                ));
                
                wp_send_json_error(array(
                    'message' => 'Failed to create order: ' . $order->get_error_message(),
                    'error_code' => 'ORDER_CREATION_FAILED',
                    'retry_allowed' => true
                ));
            }
            
            // CRITICAL: Set customer ID on order so credits can be added
            $user_id = get_current_user_id();
            if ($user_id) {
                $order->set_customer_id($user_id);
            }
            
            // Add credit product to order
            $credit_product_id = get_option('ai_virtual_fitting_credit_product_id');
            if (!$credit_product_id) {
                wp_send_json_error(array(
                    'message' => 'Credits product not found',
                    'error_code' => 'PRODUCT_NOT_FOUND',
                    'retry_allowed' => false
                ));
            }
            
            $product = wc_get_product($credit_product_id);
            if (!$product) {
                wp_send_json_error(array(
                    'message' => 'Credits product not found',
                    'error_code' => 'PRODUCT_NOT_FOUND',
                    'retry_allowed' => false
                ));
            }
            
            $order->add_product($product, 1);
            
            // Set billing details
            $order->set_billing_first_name($first_name);
            $order->set_billing_last_name($last_name);
            $order->set_billing_email($payer_email);
            
            // Set billing phone if provided
            if (!empty($payer_phone)) {
                $order->set_billing_phone($payer_phone);
            }
            
            // Set billing/shipping address if provided
            if ($shipping_address && is_array($shipping_address)) {
                // Billing address
                if (isset($shipping_address['addressLine']) && is_array($shipping_address['addressLine'])) {
                    $order->set_billing_address_1($shipping_address['addressLine'][0] ?? '');
                    $order->set_billing_address_2($shipping_address['addressLine'][1] ?? '');
                }
                if (isset($shipping_address['city'])) {
                    $order->set_billing_city($shipping_address['city']);
                }
                if (isset($shipping_address['region'])) {
                    $order->set_billing_state($shipping_address['region']);
                }
                if (isset($shipping_address['postalCode'])) {
                    $order->set_billing_postcode($shipping_address['postalCode']);
                }
                if (isset($shipping_address['country'])) {
                    $order->set_billing_country($shipping_address['country']);
                }
                
                // Shipping address (same as billing for digital products)
                $order->set_shipping_first_name($first_name);
                $order->set_shipping_last_name($last_name);
                if (isset($shipping_address['addressLine']) && is_array($shipping_address['addressLine'])) {
                    $order->set_shipping_address_1($shipping_address['addressLine'][0] ?? '');
                    $order->set_shipping_address_2($shipping_address['addressLine'][1] ?? '');
                }
                if (isset($shipping_address['city'])) {
                    $order->set_shipping_city($shipping_address['city']);
                }
                if (isset($shipping_address['region'])) {
                    $order->set_shipping_state($shipping_address['region']);
                }
                if (isset($shipping_address['postalCode'])) {
                    $order->set_shipping_postcode($shipping_address['postalCode']);
                }
                if (isset($shipping_address['country'])) {
                    $order->set_shipping_country($shipping_address['country']);
                }
            }
            
            // Set payment method to Stripe
            $order->set_payment_method('stripe');
            
            // Calculate totals
            $order->calculate_totals();
            $order->save();
            
            // Process payment with Stripe using the payment method ID
            try {
                // Get Stripe API key from gateway settings
                $stripe_settings = get_option('woocommerce_stripe_settings', array());
                $test_mode = isset($stripe_settings['testmode']) && $stripe_settings['testmode'] === 'yes';
                $secret_key = $test_mode 
                    ? (isset($stripe_settings['test_secret_key']) ? $stripe_settings['test_secret_key'] : '')
                    : (isset($stripe_settings['secret_key']) ? $stripe_settings['secret_key'] : '');
                
                if (empty($secret_key)) {
                    wp_send_json_error(array(
                        'message' => 'Stripe is not properly configured',
                        'error_code' => 'STRIPE_NOT_CONFIGURED',
                        'retry_allowed' => false
                    ));
                }
                
                // Create payment intent using Stripe API
                $amount = $order->get_total() * 100; // Convert to cents
                $currency = strtolower($order->get_currency());
                
                $response = wp_remote_post('https://api.stripe.com/v1/payment_intents', array(
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $secret_key,
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ),
                    'body' => array(
                        'amount' => $amount,
                        'currency' => $currency,
                        'payment_method' => $payment_method_id,
                        'confirm' => 'true',
                        'description' => 'Virtual Fitting Credits - Order #' . $order->get_id(),
                        'metadata' => array(
                            'order_id' => $order->get_id(),
                            'customer_email' => $payer_email,
                            'customer_name' => $payer_name,
                        ),
                    ),
                    'timeout' => 30,
                ));
                
                if (is_wp_error($response)) {
                    $this->log_error('Stripe API request failed', array(
                        'user_id' => get_current_user_id(),
                        'order_id' => $order->get_id(),
                        'error' => $response->get_error_message()
                    ));
                    
                    $order->update_status('failed', 'Stripe API error: ' . $response->get_error_message());
                    
                    wp_send_json_error(array(
                        'message' => 'Payment processing failed. Please try again.',
                        'error_code' => 'STRIPE_API_ERROR',
                        'retry_allowed' => true
                    ));
                }
                
                $body = json_decode(wp_remote_retrieve_body($response), true);
                $status_code = wp_remote_retrieve_response_code($response);
                
                if ($status_code !== 200 || !isset($body['id'])) {
                    $error_message = isset($body['error']['message']) ? $body['error']['message'] : 'Unknown error';
                    
                    $this->log_error('Stripe payment intent failed', array(
                        'user_id' => get_current_user_id(),
                        'order_id' => $order->get_id(),
                        'status_code' => $status_code,
                        'error' => $error_message,
                        'body' => $body
                    ));
                    
                    $order->update_status('failed', 'Stripe payment failed: ' . $error_message);
                    
                    wp_send_json_error(array(
                        'message' => $error_message,
                        'error_code' => 'PAYMENT_FAILED',
                        'retry_allowed' => true
                    ));
                }
                
                // Check payment intent status
                if ($body['status'] === 'succeeded') {
                    // Payment successful
                    $order->update_meta_data('_stripe_payment_intent_id', $body['id']);
                    $order->update_meta_data('_stripe_payment_method_id', $payment_method_id);
                    $order->set_transaction_id($body['id']);
                    $order->payment_complete($body['id']);
                    $order->add_order_note('Payment completed via Google Pay/Apple Pay. Payment Intent: ' . $body['id']);
                    
                    // CRITICAL: Add credits directly (don't rely on hooks for express checkout)
                    $user_id = get_current_user_id();
                    error_log('EXPRESS CHECKOUT - User ID: ' . $user_id);
                    
                    if ($user_id) {
                        // Get credits amount from product
                        $credit_product_id = get_option('ai_virtual_fitting_credit_product_id');
                        error_log('EXPRESS CHECKOUT - Credit Product ID: ' . $credit_product_id);
                        
                        $credits_to_add = get_post_meta($credit_product_id, '_virtual_fitting_credits', true);
                        $credits_to_add = intval($credits_to_add);
                        error_log('EXPRESS CHECKOUT - Credits to add: ' . $credits_to_add);
                        
                        if ($credits_to_add > 0) {
                            // Add credits to user account
                            $success = $this->credit_manager->add_credits($user_id, $credits_to_add);
                            error_log('EXPRESS CHECKOUT - Add credits result: ' . ($success ? 'SUCCESS' : 'FAILED'));
                            
                            if ($success) {
                                $order->add_order_note(
                                    sprintf('Virtual Fitting Credits: Added %d credits to customer account.', $credits_to_add)
                                );
                                $order->update_meta_data('_virtual_fitting_credits_processed', 'yes');
                                $order->save();
                                
                                $this->log_info('Credits added via express checkout', array(
                                    'user_id' => $user_id,
                                    'order_id' => $order->get_id(),
                                    'credits_added' => $credits_to_add
                                ));
                            } else {
                                $this->log_error('Failed to add credits via express checkout', array(
                                    'user_id' => $user_id,
                                    'order_id' => $order->get_id(),
                                    'credits_to_add' => $credits_to_add
                                ));
                            }
                        } else {
                            error_log('EXPRESS CHECKOUT - No credits to add (credits_to_add = 0)');
                        }
                    } else {
                        error_log('EXPRESS CHECKOUT - No user ID (user not logged in?)');
                    }
                    
                    // Get updated credits for user
                    $updated_credits = $user_id ? $this->credit_manager->get_customer_credits($user_id) : 0;
                    
                    // Get credits amount from settings
                    $credits_added = get_option('ai_virtual_fitting_credits_per_package', 20);
                    
                    // Clear cart
                    WC()->cart->empty_cart();
                    
                    $this->log_info('Express checkout processed successfully', array(
                        'user_id' => $user_id,
                        'order_id' => $order->get_id(),
                        'payment_intent_id' => $body['id'],
                        'payment_method' => 'stripe_express',
                        'updated_credits' => $updated_credits
                    ));
                    
                    wp_send_json_success(array(
                        'message' => 'Payment processed successfully',
                        'order_id' => $order->get_id(),
                        'credits' => $updated_credits,
                        'credits_added' => $credits_added
                    ));
                } else {
                    // Payment requires additional action or failed
                    $this->log_error('Payment intent not succeeded', array(
                        'user_id' => get_current_user_id(),
                        'order_id' => $order->get_id(),
                        'status' => $body['status'],
                        'payment_intent_id' => $body['id']
                    ));
                    
                    $order->update_status('failed', 'Payment status: ' . $body['status']);
                    
                    wp_send_json_error(array(
                        'message' => 'Payment could not be completed. Status: ' . $body['status'],
                        'error_code' => 'PAYMENT_NOT_SUCCEEDED',
                        'retry_allowed' => true
                    ));
                }
                
            } catch (Exception $e) {
                $this->log_error('Express checkout exception', array(
                    'user_id' => get_current_user_id(),
                    'order_id' => $order->get_id(),
                    'exception' => $e->getMessage()
                ));
                
                $order->update_status('failed', 'Express checkout exception: ' . $e->getMessage());
                
                wp_send_json_error(array(
                    'message' => 'Payment processing failed. Please try again.',
                    'error_code' => 'PAYMENT_EXCEPTION',
                    'retry_allowed' => true
                ));
            }
            
        } catch (Exception $e) {
            $this->log_error('Exception in express checkout', array(
                'user_id' => get_current_user_id(),
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            
            wp_send_json_error(array(
                'message' => 'An unexpected error occurred. Please try again.',
                'error_code' => 'EXPRESS_CHECKOUT_EXCEPTION',
                'retry_allowed' => true
            ));
        }
    }
    
    /**
     * Handle refresh credits AJAX request for real-time credit updates
     */
    public function handle_refresh_credits() {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'], 'ai_virtual_fitting_nonce')) {
                $this->log_error('Security check failed for refresh credits', array(
                    'user_id' => get_current_user_id(),
                    'ip' => $this->get_client_ip()
                ));
                wp_send_json_error(array(
                    'message' => 'Security check failed',
                    'error_code' => 'SECURITY_FAILED'
                ));
            }
            
            // Check if user is logged in
            if (!is_user_logged_in()) {
                wp_send_json_success(array(
                    'credits' => 0,
                    'free_credits' => 0,
                    'logged_in' => false
                ));
            }
            
            $user_id = get_current_user_id();
            
            // Get updated credit information
            $total_credits = $this->credit_manager->get_customer_credits($user_id);
            $free_credits = $this->credit_manager->get_free_credits_remaining($user_id);
            
            $this->log_info('Credits refreshed successfully', array(
                'user_id' => $user_id,
                'total_credits' => $total_credits,
                'free_credits' => $free_credits
            ));
            
            wp_send_json_success(array(
                'credits' => $total_credits,
                'free_credits' => $free_credits,
                'logged_in' => true,
                'message' => 'Credits refreshed successfully'
            ));
            
        } catch (Exception $e) {
            $this->log_error('Exception in refresh credits', array(
                'user_id' => get_current_user_id(),
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            
            wp_send_json_error(array(
                'message' => 'An unexpected error occurred while refreshing credits',
                'error_code' => 'REFRESH_CREDITS_EXCEPTION'
            ));
        }
    }
    
    /**
     * Get upload error message
     *
     * @param int $error_code PHP upload error code
     * @return string Error message
     */
    private function get_upload_error_message($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return __('The uploaded file is too large.', 'ai-virtual-fitting');
            case UPLOAD_ERR_PARTIAL:
                return __('The file was only partially uploaded.', 'ai-virtual-fitting');
            case UPLOAD_ERR_NO_FILE:
                return __('No file was uploaded.', 'ai-virtual-fitting');
            case UPLOAD_ERR_NO_TMP_DIR:
                return __('Missing temporary folder.', 'ai-virtual-fitting');
            case UPLOAD_ERR_CANT_WRITE:
                return __('Failed to write file to disk.', 'ai-virtual-fitting');
            case UPLOAD_ERR_EXTENSION:
                return __('File upload stopped by extension.', 'ai-virtual-fitting');
            default:
                return __('Unknown upload error.', 'ai-virtual-fitting');
        }
    }
    
    /**
     * Log error message with context
     *
     * @param string $message Error message
     * @param array $context Additional context data
     */
    private function log_error($message, $context = array()) {
        if (!AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
            return;
        }
        
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'level' => 'ERROR',
            'component' => 'PublicInterface',
            'message' => $message,
            'context' => $context
        );
        
        error_log('AI Virtual Fitting - ' . json_encode($log_entry));
    }
    
    /**
     * Log info message with context
     *
     * @param string $message Info message
     * @param array $context Additional context data
     */
    private function log_info($message, $context = array()) {
        if (!AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
            return;
        }
        
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'level' => 'INFO',
            'component' => 'PublicInterface',
            'message' => $message,
            'context' => $context
        );
        
        error_log('AI Virtual Fitting - ' . json_encode($log_entry));
    }
    
    /**
     * Get client IP address
     *
     * @return string Client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    }

    /**
     * Get available WooCommerce payment methods
     * 
     * @return array Array of available payment methods with their details
     */
    private function get_available_payment_methods() {
        if (!class_exists('WC_Payment_Gateways')) {
            $this->log_error('WooCommerce Payment Gateways class not available', array(
                'user_id' => get_current_user_id(),
                'context' => 'get_available_payment_methods'
            ));
            
            return array(
                'stripe_available' => false,
                'error' => 'WooCommerce is not active'
            );
        }

        $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
        
        // Check for Stripe gateway specifically (Stripe-only approach)
        $stripe_gateway = null;
        $stripe_gateway_id = null;
        
        // Look for Stripe gateway (could be 'stripe', 'stripe_cc', etc.)
        foreach ($payment_gateways as $gateway_id => $gateway) {
            if (strpos(strtolower($gateway_id), 'stripe') !== false && $gateway->is_available()) {
                $stripe_gateway = $gateway;
                $stripe_gateway_id = $gateway_id;
                break;
            }
        }
        
        // If Stripe is not available, log and return configuration instructions
        if (!$stripe_gateway) {
            $this->log_error('Stripe payment gateway not configured', array(
                'user_id' => get_current_user_id(),
                'available_gateways' => array_keys($payment_gateways),
                'context' => 'checkout_initialization'
            ));
            
            return array(
                'stripe_available' => false,
                'error' => 'Stripe payment gateway is not configured',
                'setup_instructions' => array(
                    'Install the WooCommerce Stripe Payment Gateway plugin',
                    'Go to WooCommerce → Settings → Payments',
                    'Enable and configure Stripe with your API keys',
                    'Save changes and refresh this page'
                )
            );
        }
        
        // Validate Stripe configuration
        $stripe_config_valid = $this->validate_stripe_configuration($stripe_gateway);
        
        if (!$stripe_config_valid) {
            $this->log_error('Stripe configuration validation failed', array(
                'user_id' => get_current_user_id(),
                'gateway_id' => $stripe_gateway_id,
                'context' => 'checkout_initialization'
            ));
            
            return array(
                'stripe_available' => false,
                'error' => 'Stripe is installed but not properly configured',
                'setup_instructions' => array(
                    'Go to WooCommerce → Settings → Payments → Stripe',
                    'Enter your Stripe API keys (Publishable and Secret keys)',
                    'Enable Test Mode for testing or Live Mode for production',
                    'Save changes and refresh this page'
                )
            );
        }
        
        // Stripe is available and configured - log success and return Stripe details
        $this->log_info('Stripe payment gateway available', array(
            'user_id' => get_current_user_id(),
            'gateway_id' => $stripe_gateway_id,
            'gateway_title' => $stripe_gateway->get_title()
        ));
        
        // Get Stripe publishable key for frontend
        $publishable_key = '';
        $test_mode = false;
        if (method_exists($stripe_gateway, 'get_option')) {
            // Check if test mode is enabled
            $test_mode = $stripe_gateway->get_option('testmode') === 'yes';
            
            // Try different possible option names for publishable key
            $publishable_key = $stripe_gateway->get_option('publishable_key') 
                            ?: $stripe_gateway->get_option('stripe_publishable_key')
                            ?: $stripe_gateway->get_option('test_publishable_key')
                            ?: $stripe_gateway->get_option('live_publishable_key')
                            ?: '';
        }
        
        return array(
            'stripe_available' => true,
            'stripe_publishable_key' => $publishable_key, // Add publishable key for frontend
            'test_mode' => $test_mode, // Add test mode flag
            'payment_method' => array(
                'id' => $stripe_gateway_id,
                'title' => $stripe_gateway->get_title(),
                'description' => $stripe_gateway->get_description(),
                'icon' => $stripe_gateway->get_icon(),
                'method_title' => $stripe_gateway->get_method_title(),
                'method_description' => $stripe_gateway->get_method_description(),
                'has_fields' => true, // Stripe always has card input fields
                'supports' => array(
                    'products' => $stripe_gateway->supports('products'),
                    'refunds' => $stripe_gateway->supports('refunds'),
                    'tokenization' => $stripe_gateway->supports('tokenization')
                )
            )
        );
    }
    
    /**
     * Validate Stripe gateway configuration
     * 
     * @param WC_Payment_Gateway $gateway Stripe gateway instance
     * @return bool True if configuration is valid
     */
    private function validate_stripe_configuration($gateway) {
        // Check if gateway has required settings
        if (!method_exists($gateway, 'get_option')) {
            return false;
        }
        
        // Check for API keys (different Stripe plugins may use different option names)
        $has_publishable_key = false;
        $has_secret_key = false;
        
        // Common Stripe option names
        $publishable_key_options = array('publishable_key', 'stripe_publishable_key', 'test_publishable_key', 'live_publishable_key');
        $secret_key_options = array('secret_key', 'stripe_secret_key', 'test_secret_key', 'live_secret_key');
        
        foreach ($publishable_key_options as $option) {
            if (!empty($gateway->get_option($option))) {
                $has_publishable_key = true;
                break;
            }
        }
        
        foreach ($secret_key_options as $option) {
            if (!empty($gateway->get_option($option))) {
                $has_secret_key = true;
                break;
            }
        }
        
        // Log configuration status
        if (!$has_publishable_key || !$has_secret_key) {
            $this->log_error('Stripe API keys not configured', array(
                'has_publishable_key' => $has_publishable_key,
                'has_secret_key' => $has_secret_key,
                'gateway_id' => $gateway->id
            ));
        }
        
        return $has_publishable_key && $has_secret_key;
    }
    
    /**
     * Calculate payment method fee
     * 
     * @param string $payment_method Payment method ID
     * @return float Fee amount
     */
    private function calculate_payment_method_fee($payment_method) {
        // No fees for any payment methods - customers pay only the product price
        return 0.00;
    }
    
    /**
     * Handle calculate fees AJAX request
     */
    public function handle_calculate_fees() {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'], 'ai_virtual_fitting_nonce')) {
                wp_send_json_error(array(
                    'message' => 'Security check failed',
                    'error_code' => 'SECURITY_FAILED'
                ));
            }
            
            $payment_method = sanitize_text_field($_POST['payment_method'] ?? '');
            
            if (empty($payment_method)) {
                wp_send_json_error(array(
                    'message' => 'Payment method is required',
                    'error_code' => 'MISSING_PAYMENT_METHOD'
                ));
            }
            
            // Calculate fee for the selected payment method
            $fee_amount = $this->calculate_payment_method_fee($payment_method);
            
            // Get current cart total
            if (!WC()->cart) {
                wc_load_cart();
            }
            
            // Temporarily add the fee to calculate new total
            if ($fee_amount > 0) {
                WC()->cart->add_fee('Processing Fee', $fee_amount);
            }
            
            WC()->cart->calculate_totals();
            $new_total = WC()->cart->get_total();
            $new_total_text = html_entity_decode(wp_strip_all_tags($new_total), ENT_QUOTES, 'UTF-8');
            
            // Remove the temporary fee
            if ($fee_amount > 0) {
                WC()->cart->fees_api()->remove_all_fees();
                WC()->cart->calculate_totals();
            }
            
            wp_send_json_success(array(
                'fee_amount' => $fee_amount,
                'fee_display' => $fee_amount > 0 ? '+$' . number_format($fee_amount, 2) . ' processing fee' : '',
                'new_total' => $new_total,
                'new_total_text' => $new_total_text,
                'payment_method' => $payment_method
            ));
            
        } catch (Exception $e) {
            $this->log_error('Exception in calculate fees', array(
                'user_id' => get_current_user_id(),
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            
            wp_send_json_error(array(
                'message' => 'An unexpected error occurred while calculating fees',
                'error_code' => 'CALCULATE_FEES_EXCEPTION'
            ));
        }
    }
    
    /**
     * Add payment method fees to cart
     */
    public function add_payment_method_fees() {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        // Only add fees during checkout process
        if (!is_checkout() && !defined('WOOCOMMERCE_CHECKOUT')) {
            return;
        }
        
        // Get the selected payment method from session or POST data
        $chosen_payment_method = WC()->session->get('chosen_payment_method');
        
        // If not in session, check POST data (for AJAX requests)
        if (empty($chosen_payment_method) && isset($_POST['payment_method'])) {
            $chosen_payment_method = sanitize_text_field($_POST['payment_method']);
        }
        
        if (empty($chosen_payment_method)) {
            return;
        }
        
        // Calculate and add fee
        $fee_amount = $this->calculate_payment_method_fee($chosen_payment_method);
        
        if ($fee_amount > 0) {
            WC()->cart->add_fee('Processing Fee', $fee_amount);
        }
    }
    
    /**
     * Handle AJAX login
     */
    public function handle_ajax_login() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ai_virtual_fitting_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) && $_POST['remember'] === 'true';
        
        // Attempt login
        $creds = array(
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => $remember
        );
        
        $user = wp_signon($creds, false);
        
        if (is_wp_error($user)) {
            wp_send_json_error(array('message' => $user->get_error_message()));
        } else {
            wp_send_json_success(array('message' => 'Login successful'));
        }
    }
    
    /**
     * Handle AJAX request to get single product by ID
     */
    public function handle_get_single_product() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ai_virtual_fitting_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        if (!$product_id) {
            wp_send_json_error(array('message' => 'Invalid product ID'));
            return;
        }
        
        // Get the product
        $product = wc_get_product($product_id);
        
        if (!$product || !$product->is_visible()) {
            wp_send_json_error(array('message' => 'Product not found or not visible'));
            return;
        }
        
        // Check if this is the credit product (exclude it)
        $credit_product_id = get_option('ai_virtual_fitting_credit_product_id');
        if ($credit_product_id && $product_id == $credit_product_id) {
            wp_send_json_error(array('message' => 'This product is not available for virtual try-on'));
            return;
        }
        
        // Get product data
        $featured_image = wp_get_attachment_image_src(get_post_thumbnail_id($product_id), 'large');
        $gallery_images = $this->get_product_gallery_images($product_id);
        
        // Combine featured image with gallery images
        $all_images = array();
        if ($featured_image) {
            $all_images[] = $featured_image[0];
        }
        $all_images = array_merge($all_images, $gallery_images);
        
        // Get product categories
        $product_categories = wp_get_post_terms($product_id, 'product_cat');
        $category_slugs = array();
        if (!is_wp_error($product_categories) && !empty($product_categories)) {
            foreach ($product_categories as $cat) {
                $category_slugs[] = $cat->slug;
            }
        }
        
        $product_data = array(
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'price' => $product->get_price_html(),
            'description' => $product->get_short_description() ?: wp_trim_words($product->get_description(), 20),
            'image' => $featured_image ? array($featured_image[0]) : array(),
            'gallery' => $all_images,
            'categories' => $category_slugs
        );
        
        wp_send_json_success(array('product' => $product_data));
    }
}
