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
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Add virtual fitting page shortcode
        add_shortcode('ai_virtual_fitting', array($this, 'render_virtual_fitting_shortcode'));
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        if (is_page() && has_shortcode(get_post()->post_content, 'ai_virtual_fitting')) {
            // Enqueue modern CSS
            wp_enqueue_style(
                'ai-virtual-fitting-modern-style',
                plugin_dir_url(__FILE__) . 'css/modern-virtual-fitting.css',
                array(),
                '1.2.0'  // Updated version to bust cache
            );
            
            // Enqueue modern JavaScript
            wp_enqueue_script(
                'ai-virtual-fitting-modern-script',
                plugin_dir_url(__FILE__) . 'js/modern-virtual-fitting.js',
                array('jquery'),
                '1.1.0',
                true
            );
            
            // Localize script for AJAX
            wp_localize_script('ai-virtual-fitting-modern-script', 'ai_virtual_fitting_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_virtual_fitting_nonce'),
                'user_logged_in' => is_user_logged_in(),
                'messages' => array(
                    'login_required' => __('Please log in to use virtual fitting.', 'ai-virtual-fitting'),
                    'insufficient_credits' => __('You have insufficient credits. Please purchase more.', 'ai-virtual-fitting'),
                    'upload_error' => __('Error uploading image. Please try again.', 'ai-virtual-fitting'),
                    'processing_error' => __('Error processing virtual fitting. Please try again.', 'ai-virtual-fitting'),
                    'select_product' => __('Please select a product to try on.', 'ai-virtual-fitting'),
                    'upload_image' => __('Please upload your image first.', 'ai-virtual-fitting')
                )
            ));
        }
    }
    
    /**
     * Render virtual fitting page shortcode
     */
    public function render_virtual_fitting_shortcode($atts) {
        ob_start();
        $this->render_virtual_fitting_page();
        return ob_get_clean();
    }
    
    /**
     * Render virtual fitting page
     */
    public function render_virtual_fitting_page() {
        $current_user_id = get_current_user_id();
        $is_logged_in = is_user_logged_in();
        $credits = $is_logged_in ? $this->credit_manager->get_customer_credits($current_user_id) : 0;
        $free_credits = $is_logged_in ? $this->credit_manager->get_free_credits_remaining($current_user_id) : 0;
        
        // Get WooCommerce products for the slider
        $products = $this->get_woocommerce_products();
        
        // Get WooCommerce categories for the dropdown
        $categories = $this->get_woocommerce_categories();
        
        // Debug: Log product count
        error_log('AI Virtual Fitting - Products loaded: ' . count($products));
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
     */
    private function get_woocommerce_products() {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => 20,
            'post_status' => 'publish'
        );
        
        $products = get_posts($args);
        $product_data = array();
        
        foreach ($products as $product_post) {
            $product = wc_get_product($product_post->ID);
            if ($product) {
                $featured_image = wp_get_attachment_image_src(get_post_thumbnail_id($product->get_id()), 'medium');
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
        
        return $product_data;
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
        
        $products = $this->get_woocommerce_products();
        wp_send_json_success(array('products' => $products));
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
}