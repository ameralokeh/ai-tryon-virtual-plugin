<?php
/**
 * Debug Script Loading
 */

// WordPress environment setup
define('WP_USE_THEMES', false);
require_once('/var/www/html/wp-load.php');

echo "=== Debug Script Loading ===\n\n";

// Check if we're on the virtual fitting page
$page = get_page_by_path('virtual-fitting-2');
if ($page) {
    echo "✅ Virtual fitting page exists (ID: " . $page->ID . ")\n";
    
    // Get the actual post object
    $page_post = get_post($page->ID);
    
    // Check if shortcode exists in content
    $has_shortcode = has_shortcode($page_post->post_content, 'ai_virtual_fitting');
    echo "   Has shortcode: " . ($has_shortcode ? 'YES' : 'NO') . "\n";
    echo "   Content: " . trim($page_post->post_content) . "\n";
} else {
    echo "❌ Virtual fitting page not found\n";
}

// Check what scripts would be enqueued
echo "\n=== Script Enqueueing Test ===\n";

// Simulate the enqueue_scripts condition
$page_post = get_post($page->ID);
if ($page && has_shortcode($page_post->post_content, 'ai_virtual_fitting')) {
    echo "✅ Conditions met for script enqueueing\n";
    
    // Check if files exist
    $plugin_dir = '/var/www/html/wp-content/plugins/ai-virtual-fitting/';
    $js_file = $plugin_dir . 'public/js/modern-virtual-fitting.js';
    $css_file = $plugin_dir . 'public/css/modern-virtual-fitting.css';
    
    echo "   Looking for JS at: " . $js_file . "\n";
    echo "   Looking for CSS at: " . $css_file . "\n";
    echo "   Modern JS file exists: " . (file_exists($js_file) ? 'YES' : 'NO') . "\n";
    echo "   Modern CSS file exists: " . (file_exists($css_file) ? 'YES' : 'NO') . "\n";
    
    if (file_exists($js_file)) {
        echo "   JS file size: " . number_format(filesize($js_file)) . " bytes\n";
        
        // Check if our functions are in the file
        $js_content = file_get_contents($js_file);
        echo "   Contains openCheckoutModal: " . (strpos($js_content, 'function openCheckoutModal') !== false ? 'YES' : 'NO') . "\n";
        echo "   Contains global export: " . (strpos($js_content, 'window.openCheckoutModal') !== false ? 'YES' : 'NO') . "\n";
        echo "   Contains add-credits-btn handler: " . (strpos($js_content, '#add-credits-btn') !== false ? 'YES' : 'NO') . "\n";
    }
} else {
    echo "❌ Conditions NOT met for script enqueueing\n";
}

// Check if old script is being loaded
echo "\n=== Old Script Check ===\n";
$old_js_file = plugin_dir_path(__FILE__) . 'ai-virtual-fitting/public/js/virtual-fitting.js';
echo "Old JS file exists: " . (file_exists($old_js_file) ? 'YES' : 'NO') . "\n";

if (file_exists($old_js_file)) {
    $old_js_content = file_get_contents($old_js_file);
    echo "   Contains handlePurchaseCreditsLegacy: " . (strpos($old_js_content, 'handlePurchaseCreditsLegacy') !== false ? 'YES' : 'NO') . "\n";
    echo "   Contains shop redirect: " . (strpos($old_js_content, '/shop/?add-to-cart=virtual-fitting-credits') !== false ? 'YES' : 'NO') . "\n";
}

// Check WordPress script queue
echo "\n=== WordPress Script Queue ===\n";
global $wp_scripts;
if ($wp_scripts && isset($wp_scripts->registered)) {
    $ai_scripts = array();
    foreach ($wp_scripts->registered as $handle => $script) {
        if (strpos($handle, 'virtual-fitting') !== false || strpos($script->src, 'virtual-fitting') !== false) {
            $ai_scripts[] = $handle . ' -> ' . $script->src;
        }
    }
    
    if (!empty($ai_scripts)) {
        echo "Registered AI Virtual Fitting scripts:\n";
        foreach ($ai_scripts as $script) {
            echo "   - " . $script . "\n";
        }
    } else {
        echo "No AI Virtual Fitting scripts found in queue\n";
    }
}

echo "\n=== Recommendations ===\n";
echo "1. Clear browser cache completely (Ctrl+Shift+Delete)\n";
echo "2. Check browser Network tab for 404 errors on JS files\n";
echo "3. Verify the shortcode is in the page content\n";
echo "4. Check if scripts are actually loading in browser\n";
echo "5. Test with browser console: typeof openCheckoutModal\n";