<?php
/**
 * Test Google AI Studio API Connection
 * 
 * This script helps test and configure Google AI Studio API for the AI Virtual Fitting plugin
 */

// WordPress environment setup
require_once('/var/www/html/wp-config.php');

echo "=== Google AI Studio API Connection Test ===\n\n";

// Check if the plugin is active
if (!class_exists('AI_Virtual_Fitting_Core')) {
    echo "❌ AI Virtual Fitting plugin is not active or not found.\n";
    echo "Please activate the plugin first.\n\n";
    exit(1);
}

// Get current API configuration
$api_provider = get_option('ai_virtual_fitting_api_provider', 'google_ai_studio');
$google_ai_api_key = get_option('ai_virtual_fitting_google_ai_api_key', '');

echo "Current Configuration:\n";
echo "- API Provider: " . $api_provider . "\n";
echo "- Google AI API Key: " . (!empty($google_ai_api_key) ? "✅ Configured (" . substr($google_ai_api_key, 0, 10) . "...)" : "❌ Not configured") . "\n\n";

if (empty($google_ai_api_key)) {
    echo "🔧 To configure Google AI Studio:\n";
    echo "1. Go to: https://aistudio.google.com/app/apikey\n";
    echo "2. Create a new API key\n";
    echo "3. Copy the API key (starts with 'AIza...')\n";
    echo "4. Go to WordPress Admin → Settings → AI Virtual Fitting\n";
    echo "5. Select 'Google AI Studio (API Key)' as provider\n";
    echo "6. Paste your API key and test the connection\n\n";
    exit(0);
}

// Test the API connection
echo "Testing Google AI Studio API connection...\n";

try {
    $image_processor = new AI_Virtual_Fitting_Image_Processor();
    $test_result = $image_processor->test_api_connection($google_ai_api_key);
    
    if ($test_result['success']) {
        echo "✅ SUCCESS: " . $test_result['message'] . "\n";
        echo "\n🎉 Your Google AI Studio API is working correctly!\n";
        echo "You can now use the virtual fitting feature.\n\n";
        
        // Update the provider setting to ensure it's set to Google AI Studio
        update_option('ai_virtual_fitting_api_provider', 'google_ai_studio');
        echo "✅ API provider set to Google AI Studio\n";
        
    } else {
        echo "❌ FAILED: " . $test_result['message'] . "\n";
        echo "\n🔧 Troubleshooting steps:\n";
        echo "1. Verify your API key is correct\n";
        echo "2. Check that your API key has the necessary permissions\n";
        echo "3. Ensure your server can access Google's API endpoints\n";
        echo "4. Try generating a new API key if the current one doesn't work\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nPlease check the plugin installation and try again.\n\n";
}

// Show current plugin status
echo "=== Plugin Status ===\n";

// Check database tables
global $wpdb;
$table_name = $wpdb->prefix . 'virtual_fitting_credits';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
echo "- Database tables: " . ($table_exists ? "✅ Exist" : "❌ Missing") . "\n";

// Check WooCommerce
$woocommerce_active = class_exists('WooCommerce');
echo "- WooCommerce: " . ($woocommerce_active ? "✅ Active" : "❌ Not active") . "\n";

// Check credit product
$credit_product_id = get_option('ai_virtual_fitting_credit_product_id');
$credit_product_exists = $credit_product_id && get_post($credit_product_id);
echo "- Credit product: " . ($credit_product_exists ? "✅ Exists (ID: $credit_product_id)" : "❌ Missing") . "\n";

// Check upload directory permissions
$upload_dir = wp_upload_dir();
$temp_dir = $upload_dir['basedir'] . '/ai-virtual-fitting/temp/';
$temp_dir_writable = is_dir($temp_dir) && is_writable($temp_dir);
echo "- Upload directory: " . ($temp_dir_writable ? "✅ Writable" : "❌ Not writable") . "\n";

echo "\n=== Next Steps ===\n";
if ($test_result['success'] ?? false) {
    echo "🎯 Your setup is ready! You can now:\n";
    echo "1. Visit the virtual fitting page on your site\n";
    echo "2. Upload a customer photo\n";
    echo "3. Select a product to try on\n";
    echo "4. Test the virtual fitting functionality\n\n";
} else {
    echo "🔧 Complete the setup by:\n";
    echo "1. Configuring your Google AI Studio API key\n";
    echo "2. Testing the API connection in WordPress admin\n";
    echo "3. Ensuring all plugin components are properly installed\n\n";
}

echo "For support, check the plugin documentation or contact the developer.\n";
?>