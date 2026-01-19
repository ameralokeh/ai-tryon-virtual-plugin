<?php
/**
 * Core functionality for AI Virtual Fitting Plugin
 *
 * @package AI_Virtual_Fitting
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI Virtual Fitting Core Class
 */
class AI_Virtual_Fitting_Core {
    
    /**
     * Core instance
     *
     * @var AI_Virtual_Fitting_Core
     */
    private static $instance = null;
    
    /**
     * Database Manager instance
     *
     * @var AI_Virtual_Fitting_Database_Manager
     */
    private $database_manager;
    
    /**
     * Credit Manager instance
     *
     * @var AI_Virtual_Fitting_Credit_Manager
     */
    private $credit_manager;
    
    /**
     * WooCommerce Integration instance
     *
     * @var AI_Virtual_Fitting_WooCommerce_Integration
     */
    private $woocommerce_integration;
    
    /**
     * Image Processor instance
     *
     * @var AI_Virtual_Fitting_Image_Processor
     */
    private $image_processor;
    
    /**
     * Public Interface instance
     *
     * @var AI_Virtual_Fitting_Public_Interface
     */
    private $public_interface;
    
    /**
     * Admin Settings instance
     *
     * @var AI_Virtual_Fitting_Admin_Settings
     */
    private $admin_settings;
    
    /**
     * Performance Manager instance
     *
     * @var AI_Virtual_Fitting_Performance_Manager
     */
    private $performance_manager;
    
    /**
     * Analytics Manager instance
     *
     * @var AI_Virtual_Fitting_Analytics_Manager
     */
    private $analytics_manager;
    
    /**
     * Virtual Credit System instance
     *
     * @var AI_Virtual_Fitting_Virtual_Credit_System
     */
    private $virtual_credit_system;
    
    /**
     * Get core instance
     *
     * @return AI_Virtual_Fitting_Core
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
        $this->init_components();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Initialize after WordPress is fully loaded
        add_action('init', array($this, 'init'));
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
        
        // Frontend hooks
        if (!is_admin()) {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        }
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Core component files will be loaded by autoloader
        // Dependencies are loaded when components are instantiated
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize Database Manager first
        $this->database_manager = new AI_Virtual_Fitting_Database_Manager();
        
        // Initialize Credit Manager
        $this->credit_manager = new AI_Virtual_Fitting_Credit_Manager();
        
        // Initialize WooCommerce Integration
        $this->woocommerce_integration = new AI_Virtual_Fitting_WooCommerce_Integration();
        
        // Initialize Image Processor
        $this->image_processor = new AI_Virtual_Fitting_Image_Processor();
        
        // Initialize Public Interface
        $this->public_interface = new AI_Virtual_Fitting_Public_Interface();
        
        // Initialize Admin Settings (admin only)
        if (is_admin()) {
            $this->admin_settings = new AI_Virtual_Fitting_Admin_Settings();
        }
        
        // Initialize Virtual Credit System
        $this->virtual_credit_system = new AI_Virtual_Fitting_Virtual_Credit_System();
        
        // Initialize Performance Manager
        $this->performance_manager = new AI_Virtual_Fitting_Performance_Manager();
        
        // Initialize Analytics Manager
        $this->analytics_manager = new AI_Virtual_Fitting_Analytics_Manager();
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Add virtual fitting page rewrite rule
        add_rewrite_rule(
            '^virtual-fitting/?$',
            'index.php?virtual_fitting_page=1',
            'top'
        );
        
        // Add query var
        add_filter('query_vars', array($this, 'add_query_vars'));
        
        // Handle virtual fitting page template
        add_action('template_redirect', array($this, 'handle_virtual_fitting_page'));
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Admin-specific initialization
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        // Disabled - using modern interface instead
        // Only enqueue on virtual fitting page
        /*
        if (get_query_var('virtual_fitting_page')) {
            wp_enqueue_script(
                'ai-virtual-fitting-frontend',
                AI_VIRTUAL_FITTING_PLUGIN_URL . 'public/js/virtual-fitting.js',
                array('jquery'),
                AI_VIRTUAL_FITTING_VERSION,
                true
            );
            
            wp_enqueue_style(
                'ai-virtual-fitting-frontend',
                AI_VIRTUAL_FITTING_PLUGIN_URL . 'public/css/virtual-fitting.css',
                array(),
                AI_VIRTUAL_FITTING_VERSION
            );
            
            // Localize script with AJAX URL and nonce
            wp_localize_script('ai-virtual-fitting-frontend', 'ai_virtual_fitting_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_virtual_fitting_nonce'),
                'messages' => array(
                    'processing' => __('Processing your virtual fitting...', 'ai-virtual-fitting'),
                    'error' => __('An error occurred. Please try again.', 'ai-virtual-fitting'),
                    'no_credits' => __('You have no remaining credits. Please purchase more to continue.', 'ai-virtual-fitting'),
                )
            ));
        }
        */
    }
    
    /**
     * Add query vars
     *
     * @param array $vars
     * @return array
     */
    public function add_query_vars($vars) {
        $vars[] = 'virtual_fitting_page';
        return $vars;
    }
    
    /**
     * Handle virtual fitting page template
     */
    public function handle_virtual_fitting_page() {
        if (get_query_var('virtual_fitting_page')) {
            // Load virtual fitting page template
            include AI_VIRTUAL_FITTING_PLUGIN_DIR . 'public/virtual-fitting-page.php';
            exit;
        }
    }
    
    /**
     * Plugin activation procedures
     */
    public static function activate() {
        try {
            // Create database tables
            $database_manager = new AI_Virtual_Fitting_Database_Manager();
            $tables_created = $database_manager->create_tables();
            
            if (!$tables_created) {
                throw new Exception('Failed to create database tables during plugin activation.');
            }
            
            // Verify tables were created successfully
            if (!$database_manager->verify_tables_exist()) {
                throw new Exception('Database tables were not created successfully during plugin activation.');
            }
            
            // Create WooCommerce credit product
            $woocommerce_integration = new AI_Virtual_Fitting_WooCommerce_Integration();
            $product_created = $woocommerce_integration->create_credits_product();
            
            // Fix existing credit products missing required meta (migration)
            self::fix_existing_credit_products();
            
            // Set default options
            self::set_default_options();
            
            // Log successful activation
            if (self::get_option('enable_logging', true)) {
                error_log('AI Virtual Fitting: Plugin activated successfully');
            }
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
        } catch (Exception $e) {
            // Log the error
            error_log('AI Virtual Fitting Activation Error: ' . $e->getMessage());
            
            // Clean up any partially created resources
            if (isset($database_manager)) {
                // Don't drop tables on activation failure - they might be from previous install
                // Just log the issue for admin review
                error_log('AI Virtual Fitting: Activation failed, please check database tables manually');
            }
            
            // Display error to admin
            if (defined('WP_DEBUG') && WP_DEBUG) {
                wp_die('AI Virtual Fitting Plugin Activation Failed: ' . $e->getMessage());
            } else {
                wp_die('AI Virtual Fitting Plugin activation failed. Please check error logs and try again.');
            }
        }
    }
    
    /**
     * Fix existing credit products missing required meta
     * Migration function to add _virtual_fitting_product meta to existing products
     */
    private static function fix_existing_credit_products() {
        // Get all products with _ai_virtual_fitting_credits meta
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_ai_virtual_fitting_credits',
                    'compare' => 'EXISTS'
                )
            )
        );
        
        $products = get_posts($args);
        
        foreach ($products as $product_post) {
            $product_id = $product_post->ID;
            
            // Check if _virtual_fitting_product meta exists
            $has_meta = get_post_meta($product_id, '_virtual_fitting_product', true);
            
            if ($has_meta !== 'yes') {
                // Add the missing meta
                update_post_meta($product_id, '_virtual_fitting_product', 'yes');
                error_log('AI Virtual Fitting: Added missing _virtual_fitting_product meta to product ID: ' . $product_id);
            }
        }
    }
    
    /**
     * Plugin deactivation procedures
     */
    public static function deactivate() {
        try {
            // Log deactivation
            if (self::get_option('enable_logging', true)) {
                error_log('AI Virtual Fitting: Plugin deactivated - data preserved');
            }
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
            // Note: We don't delete data on deactivation to preserve customer credits
            // Data cleanup only happens on uninstall (Requirements 8.6)
            
        } catch (Exception $e) {
            // Log the error but don't prevent deactivation
            error_log('AI Virtual Fitting Deactivation Warning: ' . $e->getMessage());
            
            // Still flush rewrite rules even if other operations fail
            flush_rewrite_rules();
        }
    }
    
    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        // Default settings
        $default_options = array(
            'google_ai_api_key' => '',
            'initial_credits' => 2,
            'credits_per_package' => 20,
            'credits_package_price' => 10.00,
            'max_image_size' => 10485760, // 10MB
            'allowed_image_types' => array('image/jpeg', 'image/png', 'image/webp'),
            'api_retry_attempts' => 3,
            'enable_logging' => true,
            'temp_file_cleanup_hours' => 24,
            'enable_analytics' => true,
            'require_login' => true,
            'allowed_user_roles' => array('customer', 'subscriber', 'administrator'),
            'api_timeout' => 60,
            'enable_email_notifications' => true,
            'admin_email_notifications' => false,
        );
        
        foreach ($default_options as $option_name => $default_value) {
            $option_key = 'ai_virtual_fitting_' . $option_name;
            if (false === get_option($option_key)) {
                add_option($option_key, $default_value);
            }
        }
    }
    
    /**
     * Get plugin option
     *
     * @param string $option_name
     * @param mixed $default
     * @return mixed
     */
    public static function get_option($option_name, $default = false) {
        return get_option('ai_virtual_fitting_' . $option_name, $default);
    }
    
    /**
     * Update plugin option
     *
     * @param string $option_name
     * @param mixed $value
     * @return bool
     */
    public static function update_option($option_name, $value) {
        return update_option('ai_virtual_fitting_' . $option_name, $value);
    }
    
    /**
     * Get component instances
     */
    public function get_database_manager() {
        return $this->database_manager;
    }
    
    public function get_credit_manager() {
        return $this->credit_manager;
    }
    
    public function get_woocommerce_integration() {
        return $this->woocommerce_integration;
    }
    
    public function get_image_processor() {
        return $this->image_processor;
    }
    
    public function get_public_interface() {
        return $this->public_interface;
    }
    
    public function get_admin_settings() {
        return $this->admin_settings;
    }
    
    public function get_performance_manager() {
        return $this->performance_manager;
    }
    
    public function get_analytics_manager() {
        return $this->analytics_manager;
    }
}