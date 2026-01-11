<?php
/**
 * PHPUnit bootstrap file for AI Virtual Fitting Plugin tests
 *
 * @package AI_Virtual_Fitting
 */

// Define test environment
define('AI_VIRTUAL_FITTING_TESTING', true);

// WordPress test environment
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

// Give access to tests_add_filter() function
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested
 */
function _manually_load_plugin() {
    // Load WordPress
    require dirname(dirname(__FILE__)) . '/ai-virtual-fitting.php';
    
    // Activate the plugin
    activate_plugin('ai-virtual-fitting/ai-virtual-fitting.php');
}
tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment
require $_tests_dir . '/includes/bootstrap.php';

// Load plugin classes
require_once dirname(dirname(__FILE__)) . '/includes/class-virtual-fitting-core.php';
require_once dirname(dirname(__FILE__)) . '/includes/class-database-manager.php';
require_once dirname(dirname(__FILE__)) . '/includes/class-credit-manager.php';
require_once dirname(dirname(__FILE__)) . '/includes/class-woocommerce-integration.php';
require_once dirname(dirname(__FILE__)) . '/includes/class-image-processor.php';
require_once dirname(dirname(__FILE__)) . '/public/class-public-interface.php';
require_once dirname(dirname(__FILE__)) . '/admin/class-admin-settings.php';