<?php
/**
 * Virtual Try-On Button Display Module
 *
 * Handles the display and functionality of the "Try on Virtually" button
 * on WooCommerce product pages. This module integrates seamlessly with
 * WooCommerce product pages to provide one-click access to the virtual
 * fitting experience.
 *
 * Features:
 * - Automatic button injection on product pages
 * - Category-based filtering for button display
 * - Authentication-aware redirects
 * - Product pre-selection on virtual fitting page
 * - Analytics tracking for button clicks
 * - Theme compatibility with major WooCommerce themes
 * - Responsive design for mobile devices
 * - Accessibility compliant (WCAG 2.1 AA)
 *
 * @package AI_Virtual_Fitting
 * @subpackage Public
 * @since 1.0.7
 * @version 1.0.0
 *
 * @see AI_Virtual_Fitting_Core For plugin initialization
 * @see AI_Virtual_Fitting_Analytics_Manager For analytics tracking
 *
 * @example
 * // Initialize the Try-On Button
 * $tryon_button = new AI_Virtual_Fitting_TryOn_Button();
 *
 * @example
 * // Check if button should display for a product
 * if ($tryon_button->should_display_button()) {
 *     $tryon_button->render_button();
 * }
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI Virtual Fitting Try-On Button Class
 * 
 * Manages the display, styling, and functionality of the "Try on Virtually"
 * button on WooCommerce product pages. Handles category filtering, authentication
 * checks, URL generation, and analytics tracking.
 *
 * The button provides a seamless way for customers to access the virtual fitting
 * feature directly from product pages, with automatic product pre-selection.
 *
 * @since 1.0.7
 * @version 1.0.0
 */
class AI_Virtual_Fitting_TryOn_Button {
    
    /**
     * Virtual Fitting page ID
     *
     * The WordPress page ID where the virtual fitting interface is located.
     * This is where users will be redirected when they click the Try-On button.
     *
     * @since 1.0.7
     * @var int
     */
    private $page_id;
    
    /**
     * Button enabled flag
     *
     * Global toggle to enable or disable the Try-On button across all products.
     * When false, the button will not be displayed on any product pages.
     *
     * @since 1.0.7
     * @var bool
     */
    private $button_enabled;
    
    /**
     * Allowed product categories
     *
     * Array of WooCommerce product category IDs where the button should appear.
     * If empty, the button will appear on all products.
     *
     * @since 1.0.7
     * @var array Array of category IDs (integers)
     */
    private $allowed_categories;
    
    /**
     * Button text
     *
     * The text label displayed on the Try-On button.
     * Default: "Try on Virtually"
     *
     * @since 1.0.7
     * @var string
     */
    private $button_text;
    
    /**
     * Show icon flag
     *
     * Whether to display the camera icon on the button.
     * When true, an SVG camera icon is displayed before the button text.
     *
     * @since 1.0.7
     * @var bool
     */
    private $show_icon;
    
    /**
     * Require login flag
     *
     * Whether users must be logged in to use the virtual fitting feature.
     * When true and user is not logged in, button redirects to login page.
     *
     * @since 1.0.7
     * @var bool
     */
    private $require_login;
    
    /**
     * Cache for product eligibility checks
     *
     * Stores the results of product eligibility checks to avoid redundant
     * database queries. Key is product ID, value is boolean eligibility.
     *
     * @since 1.0.7
     * @var array Associative array with product IDs as keys
     */
    private $eligibility_cache = array();
    
    /**
     * Cache for theme classes
     *
     * Stores the theme-specific CSS classes to avoid redundant theme detection.
     * Null until first detection, then contains space-separated class string.
     *
     * @since 1.0.7
     * @var string|null
     */
    private $theme_classes_cache = null;
    
    /**
     * Constructor
     *
     * Initializes the Try-On Button by loading settings from WordPress options
     * and registering WordPress hooks for button display and asset loading.
     *
     * @since 1.0.7
     *
     * @example
     * $tryon_button = new AI_Virtual_Fitting_TryOn_Button();
     */
    public function __construct() {
        $this->load_settings();
        $this->init_hooks();
    }
    
    /**
     * Load settings from WordPress options
     *
     * Retrieves all button configuration settings from the WordPress options table.
     * Settings include page ID, enabled status, categories, button text, icon display,
     * and authentication requirements.
     *
     * @since 1.0.7
     * @access private
     *
     * @return void
     */
    private function load_settings() {
        $this->page_id = get_option('ai_virtual_fitting_tryon_button_page_id', 0);
        $this->button_enabled = get_option('ai_virtual_fitting_tryon_button_enabled', true);
        $this->allowed_categories = get_option('ai_virtual_fitting_tryon_button_categories', array());
        $this->button_text = get_option('ai_virtual_fitting_tryon_button_text', __('Try on Virtually', 'ai-virtual-fitting'));
        $this->show_icon = get_option('ai_virtual_fitting_tryon_button_show_icon', true);
        $this->require_login = get_option('ai_virtual_fitting_require_login', true);
    }
    
    /**
     * Initialize WordPress hooks
     *
     * Registers WordPress action hooks for button display and asset loading.
     * Only initializes hooks if the button is globally enabled.
     *
     * Hooks registered:
     * - woocommerce_after_add_to_cart_button: Injects button HTML
     * - wp_enqueue_scripts: Loads button CSS and JavaScript
     *
     * @since 1.0.7
     * @access public
     *
     * @return void
     */
    public function init_hooks() {
        // Only initialize if button is enabled
        if (!$this->button_enabled) {
            return;
        }
        
        // Add button to product pages
        add_action('woocommerce_after_add_to_cart_button', array($this, 'render_button'));
        
        // Enqueue button assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_button_assets'));
    }
    
    /**
     * Check if button should be displayed for current product
     *
     * Performs multiple checks to determine if the Try-On button should be displayed:
     * 1. Verifies we're on a single product page
     * 2. Checks if virtual fitting page is configured
     * 3. Validates the product exists
     * 4. Checks product eligibility based on category filters
     *
     * @since 1.0.7
     * @access public
     *
     * @global WC_Product $product Current WooCommerce product object
     *
     * @return bool True if button should be displayed, false otherwise
     *
     * @example
     * if ($tryon_button->should_display_button()) {
     *     $tryon_button->render_button();
     * }
     */
    public function should_display_button() {
        // Check if we're on a single product page
        if (!is_product()) {
            return false;
        }
        
        // Check if page ID is configured
        if (empty($this->page_id) || !get_post($this->page_id)) {
            return false;
        }
        
        // Get current product
        global $product;
        
        // If $product is not set or is not a valid product object, try to get it
        if (!$product || !is_object($product) || !method_exists($product, 'get_id')) {
            $product = wc_get_product(get_the_ID());
        }
        
        // Final check - if still no valid product, return false
        if (!$product || !is_object($product) || !method_exists($product, 'get_id')) {
            return false;
        }
        
        $product_id = $product->get_id();
        
        // Check if product is eligible
        return $this->is_product_eligible($product_id);
    }
    
    /**
     * Check if product is eligible for virtual try-on
     *
     * Determines if a specific product should display the Try-On button based on
     * category filtering. Uses caching to avoid redundant database queries.
     *
     * Logic:
     * - If no categories are configured, all products are eligible
     * - If categories are configured, product must belong to at least one
     * - Results are cached for performance
     *
     * @since 1.0.7
     * @access private
     *
     * @param int $product_id WooCommerce product ID to check
     *
     * @return bool True if product is eligible, false otherwise
     *
     * @example
     * if ($this->is_product_eligible(123)) {
     *     // Product 123 is eligible for virtual try-on
     * }
     */
    private function is_product_eligible($product_id) {
        // Check cache first
        if (isset($this->eligibility_cache[$product_id])) {
            return $this->eligibility_cache[$product_id];
        }
        
        // If no categories are configured, all products are eligible
        if (empty($this->allowed_categories)) {
            $this->eligibility_cache[$product_id] = true;
            return true;
        }
        
        // Get product categories (with caching)
        $product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
        
        if (is_wp_error($product_categories) || empty($product_categories)) {
            $this->eligibility_cache[$product_id] = false;
            return false;
        }
        
        // Check if product belongs to any allowed category
        $is_eligible = false;
        foreach ($product_categories as $category_id) {
            if (in_array($category_id, $this->allowed_categories)) {
                $is_eligible = true;
                break;
            }
        }
        
        // Cache the result
        $this->eligibility_cache[$product_id] = $is_eligible;
        
        return $is_eligible;
    }
    
    /**
     * Render the Try-On button
     *
     * Outputs the HTML for the Try-On button on WooCommerce product pages.
     * Includes accessibility features, theme compatibility, and optional icon.
     *
     * The button includes:
     * - Semantic HTML with proper ARIA labels
     * - Theme-specific CSS classes for compatibility
     * - Optional SVG camera icon
     * - Product ID data attribute for JavaScript
     * - Keyboard accessibility (tabindex, role)
     *
     * @since 1.0.7
     * @access public
     *
     * @global WC_Product $product Current WooCommerce product object
     *
     * @return void Outputs HTML directly
     *
     * @example
     * // Called automatically by WooCommerce hook
     * do_action('woocommerce_after_add_to_cart_button');
     */
    public function render_button() {
        // Check if button should be displayed
        if (!$this->should_display_button()) {
            return;
        }
        
        global $product;
        $product_id = $product->get_id();
        
        // Generate button URL
        $button_url = $this->get_button_url($product_id);
        
        // Get product name for better accessibility
        $product_name = $product->get_name();
        $aria_label = sprintf(
            /* translators: %s: product name */
            __('Try on %s virtually', 'ai-virtual-fitting'),
            $product_name
        );
        
        // Get theme-specific classes
        $theme_classes = $this->get_theme_classes();
        
        // Render button HTML
        ?>
        <div class="ai-virtual-fitting-tryon-button-wrapper <?php echo esc_attr($theme_classes); ?>" role="complementary" aria-label="<?php echo esc_attr__('Virtual try-on option', 'ai-virtual-fitting'); ?>">
            <a href="<?php echo esc_url($button_url); ?>" 
               class="ai-virtual-fitting-tryon-button" 
               data-product-id="<?php echo esc_attr($product_id); ?>"
               role="button"
               aria-label="<?php echo esc_attr($aria_label); ?>"
               tabindex="0">
                <?php if ($this->show_icon) : ?>
                    <svg class="ai-virtual-fitting-tryon-button-icon" 
                         width="20" 
                         height="20" 
                         viewBox="0 0 24 24" 
                         fill="none" 
                         xmlns="http://www.w3.org/2000/svg"
                         aria-hidden="true"
                         focusable="false">
                        <path d="M12 15.5C13.933 15.5 15.5 13.933 15.5 12C15.5 10.067 13.933 8.5 12 8.5C10.067 8.5 8.5 10.067 8.5 12C8.5 13.933 10.067 15.5 12 15.5Z" 
                              stroke="currentColor" 
                              stroke-width="2" 
                              stroke-linecap="round" 
                              stroke-linejoin="round"/>
                        <path d="M12 5.5V3M12 21V18.5M18.5 12H21M3 12H5.5M17.5 6.5L19.5 4.5M4.5 19.5L6.5 17.5M17.5 17.5L19.5 19.5M4.5 4.5L6.5 6.5" 
                              stroke="currentColor" 
                              stroke-width="2" 
                              stroke-linecap="round" 
                              stroke-linejoin="round"/>
                    </svg>
                <?php endif; ?>
                <span class="ai-virtual-fitting-tryon-button-text">
                    <?php echo esc_html($this->button_text); ?>
                </span>
            </a>
        </div>
        <?php
    }
    
    /**
     * Get theme-specific CSS classes
     *
     * Detects the current WordPress theme and page builders to add compatibility
     * CSS classes. Results are cached to avoid redundant theme detection.
     *
     * Supported themes:
     * - Storefront, Astra, OceanWP, Flatsome, Divi, Avada, GeneratePress, Kadence
     *
     * Supported page builders:
     * - Elementor, WPBakery, Beaver Builder
     *
     * @since 1.0.7
     * @access private
     *
     * @return string Space-separated CSS class names
     *
     * @example
     * $classes = $this->get_theme_classes();
     * // Returns: "astra-theme elementor-compatible"
     */
    private function get_theme_classes() {
        // Return cached result if available
        if ($this->theme_classes_cache !== null) {
            return $this->theme_classes_cache;
        }
        
        $classes = array();
        
        // Get current theme
        $theme = wp_get_theme();
        $theme_name = strtolower($theme->get('Name'));
        $theme_template = strtolower($theme->get_template());
        
        // Add theme-specific classes
        $theme_map = array(
            'storefront' => 'storefront-theme',
            'astra' => 'astra-theme',
            'oceanwp' => 'oceanwp-theme',
            'flatsome' => 'flatsome-theme',
            'divi' => 'divi-theme',
            'avada' => 'avada-theme',
            'generatepress' => 'generatepress-theme',
            'kadence' => 'kadence-theme',
        );
        
        foreach ($theme_map as $theme_slug => $theme_class) {
            if (strpos($theme_name, $theme_slug) !== false || strpos($theme_template, $theme_slug) !== false) {
                $classes[] = $theme_class;
                break;
            }
        }
        
        // Check for page builders
        if (defined('ELEMENTOR_VERSION')) {
            $classes[] = 'elementor-compatible';
        }
        
        if (defined('WPB_VC_VERSION')) {
            $classes[] = 'wpbakery-compatible';
        }
        
        if (defined('FL_BUILDER_VERSION')) {
            $classes[] = 'beaver-builder-compatible';
        }
        
        // Cache the result
        $this->theme_classes_cache = implode(' ', $classes);
        
        return $this->theme_classes_cache;
    }
    
    /**
     * Get button URL with product_id parameter
     *
     * Generates the complete URL for the Try-On button, including the product ID
     * parameter and authentication redirect if required.
     *
     * URL generation logic:
     * 1. Get permalink for virtual fitting page
     * 2. Add product_id query parameter
     * 3. If login required and user not logged in, wrap in login URL
     * 4. Login URL includes return redirect to preserve product selection
     *
     * @since 1.0.7
     * @access public
     *
     * @param int $product_id WooCommerce product ID
     *
     * @return string Complete button URL with product_id parameter
     *
     * @example
     * $url = $this->get_button_url(123);
     * // Returns: "https://example.com/virtual-fitting?product_id=123"
     * // Or if login required: "https://example.com/wp-login.php?redirect_to=..."
     */
    public function get_button_url($product_id) {
        $page_url = get_permalink($this->page_id);
        $button_url = add_query_arg('product_id', $product_id, $page_url);
        
        // Check if login is required and user is not logged in
        if ($this->require_login && !is_user_logged_in()) {
            // Generate login URL with return redirect including product_id
            $button_url = wp_login_url($button_url);
        }
        
        return $button_url;
    }
    
    /**
     * Enqueue button CSS and JavaScript
     *
     * Loads the button's stylesheet and JavaScript file on product pages where
     * the button is displayed. Includes performance optimizations:
     * - Only loads on product pages
     * - Only loads if button should be displayed
     * - JavaScript loads in footer for better performance
     * - Includes cache busting via version number
     *
     * Also localizes JavaScript with AJAX configuration and user state.
     *
     * @since 1.0.7
     * @access public
     *
     * @return void
     *
     * @example
     * // Called automatically by WordPress
     * do_action('wp_enqueue_scripts');
     */
    public function enqueue_button_assets() {
        // Only enqueue on product pages
        if (!is_product()) {
            return;
        }
        
        // Check if button should be displayed (early return for performance)
        if (!$this->should_display_button()) {
            return;
        }
        
        // Enqueue button CSS with version for cache busting
        wp_enqueue_style(
            'ai-virtual-fitting-tryon-button',
            AI_VIRTUAL_FITTING_PLUGIN_URL . 'public/css/virtual-tryon-button.css',
            array(),
            AI_VIRTUAL_FITTING_VERSION,
            'all'
        );
        
        // Enqueue login modal CSS (for non-logged-in users)
        if (!is_user_logged_in()) {
            wp_enqueue_style(
                'ai-virtual-fitting-login-modal',
                AI_VIRTUAL_FITTING_PLUGIN_URL . 'public/css/login-modal.css',
                array(),
                AI_VIRTUAL_FITTING_VERSION,
                'all'
            );
        }
        
        // Enqueue button JavaScript with defer loading
        wp_enqueue_script(
            'ai-virtual-fitting-tryon-button',
            AI_VIRTUAL_FITTING_PLUGIN_URL . 'public/js/virtual-tryon-button.js',
            array('jquery'),
            AI_VIRTUAL_FITTING_VERSION,
            true // Load in footer for better performance
        );
        
        // Enqueue login modal JavaScript (for non-logged-in users)
        if (!is_user_logged_in()) {
            wp_enqueue_script(
                'ai-virtual-fitting-login-modal',
                AI_VIRTUAL_FITTING_PLUGIN_URL . 'public/js/login-modal.js',
                array('jquery'),
                AI_VIRTUAL_FITTING_VERSION,
                true
            );
        }
        
        // Localize script with AJAX data (only once)
        wp_localize_script('ai-virtual-fitting-tryon-button', 'ai_virtual_fitting_tryon', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_virtual_fitting_tryon_nonce'),
            'user_logged_in' => is_user_logged_in(),
            'require_login' => $this->require_login
        ));
        
        // Localize login modal script with AJAX data (for non-logged-in users)
        if (!is_user_logged_in()) {
            wp_localize_script('ai-virtual-fitting-login-modal', 'ai_virtual_fitting_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_virtual_fitting_nonce'),
                'user_logged_in' => false,
                'register_url' => wp_registration_url(),
                'lost_password_url' => wp_lostpassword_url()
            ));
        }
    }
}
