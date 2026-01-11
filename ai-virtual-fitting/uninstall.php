<?php
/**
 * Uninstall AI Virtual Fitting Plugin
 *
 * @package AI_Virtual_Fitting
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Load plugin dependencies for uninstall
require_once plugin_dir_path(__FILE__) . 'includes/class-database-manager.php';

/**
 * Clean up plugin data on uninstall
 */
function ai_virtual_fitting_uninstall() {
    try {
        // Log uninstall start
        error_log('AI Virtual Fitting: Starting plugin uninstall process');
        
        // Remove database tables
        $database_manager = new AI_Virtual_Fitting_Database_Manager();
        $tables_dropped = $database_manager->drop_tables();
        
        if ($tables_dropped) {
            error_log('AI Virtual Fitting: Database tables removed successfully');
        } else {
            error_log('AI Virtual Fitting: Warning - Some database tables may not have been removed');
        }
        
        // Remove plugin options
        $options_to_delete = array(
            'ai_virtual_fitting_google_ai_api_key',
            'ai_virtual_fitting_initial_credits',
            'ai_virtual_fitting_credits_per_package',
            'ai_virtual_fitting_credits_package_price',
            'ai_virtual_fitting_max_image_size',
            'ai_virtual_fitting_allowed_image_types',
            'ai_virtual_fitting_api_retry_attempts',
            'ai_virtual_fitting_enable_logging',
            'ai_virtual_fitting_db_version', // Don't forget the database version
        );
        
        $options_deleted = 0;
        foreach ($options_to_delete as $option) {
            if (delete_option($option)) {
                $options_deleted++;
            }
        }
        
        error_log("AI Virtual Fitting: Removed {$options_deleted} plugin options");
        
        // Clean up temporary files
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/ai-virtual-fitting-temp/';
        
        if (is_dir($temp_dir)) {
            $files_deleted = 0;
            $files = glob($temp_dir . '*');
            foreach ($files as $file) {
                if (is_file($file) && unlink($file)) {
                    $files_deleted++;
                }
            }
            
            if (rmdir($temp_dir)) {
                error_log("AI Virtual Fitting: Removed temporary directory and {$files_deleted} files");
            } else {
                error_log("AI Virtual Fitting: Warning - Could not remove temporary directory");
            }
        }
        
        // Remove virtual fitting credits WooCommerce product
        $products = get_posts(array(
            'post_type' => 'product',
            'meta_query' => array(
                array(
                    'key' => '_virtual_fitting_credits',
                    'compare' => 'EXISTS'
                )
            ),
            'posts_per_page' => -1
        ));
        
        $products_deleted = 0;
        foreach ($products as $product) {
            if (wp_delete_post($product->ID, true)) {
                $products_deleted++;
            }
        }
        
        if ($products_deleted > 0) {
            error_log("AI Virtual Fitting: Removed {$products_deleted} WooCommerce credit products");
        }
        
        // Clear any cached data
        wp_cache_flush();
        
        error_log('AI Virtual Fitting: Plugin uninstall completed successfully');
        
    } catch (Exception $e) {
        error_log('AI Virtual Fitting Uninstall Error: ' . $e->getMessage());
        // Continue with uninstall even if there are errors
        // WordPress will still remove the plugin files
    }
}

// Run uninstall
ai_virtual_fitting_uninstall();