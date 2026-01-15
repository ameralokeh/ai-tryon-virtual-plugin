<?php
/**
 * Test Helper - Provides dynamic path resolution for standalone tests
 * 
 * This file helps tests work in any WordPress installation, not just localhost
 *
 * @package AI_Virtual_Fitting
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Try to find WordPress root directory dynamically
    $wp_load_paths = array(
        // Standard WordPress in web root
        dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php',
        // WordPress in subdirectory
        dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/wp-load.php',
        // Docker container standard path
        '/var/www/html/wp-load.php',
        // Common local development paths
        $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php',
    );
    
    $wp_loaded = false;
    foreach ($wp_load_paths as $wp_load_path) {
        if (file_exists($wp_load_path)) {
            require_once($wp_load_path);
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        die("Error: Could not find WordPress installation. Please ensure WordPress is installed.\n");
    }
}

// Define plugin directory dynamically
if (!defined('AI_VIRTUAL_FITTING_TEST_DIR')) {
    define('AI_VIRTUAL_FITTING_TEST_DIR', dirname(__FILE__));
}

if (!defined('AI_VIRTUAL_FITTING_PLUGIN_DIR')) {
    define('AI_VIRTUAL_FITTING_PLUGIN_DIR', dirname(AI_VIRTUAL_FITTING_TEST_DIR) . '/');
}

/**
 * Load plugin class dynamically
 *
 * @param string $class_file Class filename (e.g., 'class-database-manager.php')
 * @param string $subdirectory Subdirectory within plugin (e.g., 'includes', 'admin', 'public')
 */
function ai_vf_test_load_class($class_file, $subdirectory = 'includes') {
    $file_path = AI_VIRTUAL_FITTING_PLUGIN_DIR . $subdirectory . '/' . $class_file;
    
    if (file_exists($file_path)) {
        require_once($file_path);
    } else {
        die("Error: Could not load class file: {$file_path}\n");
    }
}

// Load core plugin classes
ai_vf_test_load_class('class-virtual-fitting-core.php', 'includes');
ai_vf_test_load_class('class-database-manager.php', 'includes');
ai_vf_test_load_class('class-credit-manager.php', 'includes');
ai_vf_test_load_class('class-image-processor.php', 'includes');
ai_vf_test_load_class('class-woocommerce-integration.php', 'includes');
ai_vf_test_load_class('class-public-interface.php', 'public');
