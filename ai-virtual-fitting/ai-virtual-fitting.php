<?php
/**
 * Plugin Name: AI Virtual Fitting
 * Plugin URI: https://example.com/ai-virtual-fitting
 * Description: AI-powered virtual try-on experience for wedding dresses using Google AI Studio's Gemini 2.5 Flash Image model. Integrates with WooCommerce for product management and credit-based usage tracking. Now with Apple Pay and Google Pay support!
 * Version: 1.0.7.7
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-virtual-fitting
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 *
 * @package AI_Virtual_Fitting
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AI_VIRTUAL_FITTING_VERSION', '1.0.7.7');
define('AI_VIRTUAL_FITTING_PLUGIN_FILE', __FILE__);
define('AI_VIRTUAL_FITTING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_VIRTUAL_FITTING_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AI_VIRTUAL_FITTING_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main AI Virtual Fitting Plugin Class
 */
final class AI_Virtual_Fitting {
    
    /**
     * Plugin instance
     *
     * @var AI_Virtual_Fitting
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     *
     * @return AI_Virtual_Fitting
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
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Plugin activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Initialize plugin after WordPress loads
        add_action('plugins_loaded', array($this, 'init'));
        
        // Load text domain for translations
        add_action('init', array($this, 'load_textdomain'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Autoloader for plugin classes
        spl_autoload_register(array($this, 'autoload'));
        
        // Include core files
        require_once AI_VIRTUAL_FITTING_PLUGIN_DIR . 'includes/class-virtual-fitting-core.php';
    }
    
    /**
     * Autoloader for plugin classes
     *
     * @param string $class_name
     */
    public function autoload($class_name) {
        // Only autoload our plugin classes
        if (strpos($class_name, 'AI_Virtual_Fitting_') !== 0) {
            return;
        }
        
        // Convert class name to file name
        $file_name = 'class-' . strtolower(str_replace('_', '-', str_replace('AI_Virtual_Fitting_', '', $class_name))) . '.php';
        
        // Define possible directories
        $directories = array(
            AI_VIRTUAL_FITTING_PLUGIN_DIR . 'includes/',
            AI_VIRTUAL_FITTING_PLUGIN_DIR . 'admin/',
            AI_VIRTUAL_FITTING_PLUGIN_DIR . 'public/',
        );
        
        // Try to load the file from each directory
        foreach ($directories as $directory) {
            $file_path = $directory . $file_name;
            if (file_exists($file_path)) {
                require_once $file_path;
                break;
            }
        }
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Check if WooCommerce is active
        if (!$this->is_woocommerce_active()) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Initialize core functionality
        if (class_exists('AI_Virtual_Fitting_Core')) {
            AI_Virtual_Fitting_Core::instance();
        }
    }
    
    /**
     * Plugin activation hook
     */
    public function activate() {
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            wp_die(__('AI Virtual Fitting requires WordPress 5.0 or higher.', 'ai-virtual-fitting'));
        }
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            wp_die(__('AI Virtual Fitting requires PHP 7.4 or higher.', 'ai-virtual-fitting'));
        }
        
        // Check if WooCommerce is active
        if (!$this->is_woocommerce_active()) {
            wp_die(__('AI Virtual Fitting requires WooCommerce to be installed and activated.', 'ai-virtual-fitting'));
        }
        
        // Load dependencies for activation
        $this->load_dependencies();
        
        // Run activation procedures
        if (class_exists('AI_Virtual_Fitting_Core')) {
            AI_Virtual_Fitting_Core::activate();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation hook
     */
    public function deactivate() {
        // Load dependencies for deactivation
        $this->load_dependencies();
        
        // Run deactivation procedures
        if (class_exists('AI_Virtual_Fitting_Core')) {
            AI_Virtual_Fitting_Core::deactivate();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Load plugin text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'ai-virtual-fitting',
            false,
            dirname(AI_VIRTUAL_FITTING_PLUGIN_BASENAME) . '/languages/'
        );
    }
    
    /**
     * Check if WooCommerce is active
     *
     * @return bool
     */
    private function is_woocommerce_active() {
        return class_exists('WooCommerce');
    }
    
    /**
     * Display WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <?php 
                echo wp_kses_post(
                    sprintf(
                        __('AI Virtual Fitting requires %s to be installed and activated.', 'ai-virtual-fitting'),
                        '<strong>WooCommerce</strong>'
                    )
                );
                ?>
            </p>
        </div>
        <?php
    }
}

/**
 * Initialize the plugin
 */
function ai_virtual_fitting() {
    return AI_Virtual_Fitting::instance();
}

// Start the plugin
ai_virtual_fitting();