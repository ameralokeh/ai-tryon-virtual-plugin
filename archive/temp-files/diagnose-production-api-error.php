<?php
/**
 * Production API Error Diagnostic Script
 * 
 * This script helps diagnose why the production server is getting
 * "Request contains an invalid argument" error while local works fine.
 * 
 * Upload this to production and run via: php diagnose-production-api-error.php
 * Or access via browser: https://bridesandtailor.com/diagnose-production-api-error.php
 */

// Load WordPress
require_once(__DIR__ . '/wp-load.php');

// Check if running from CLI or browser
$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    // Security check for browser access
    if (!current_user_can('manage_options')) {
        die('Access denied. Admin privileges required.');
    }
    echo '<pre>';
}

echo "=== AI Virtual Fitting Production Diagnostic ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Environment: " . (defined('WP_DEBUG') && WP_DEBUG ? 'DEBUG' : 'PRODUCTION') . "\n\n";

// 1. Check plugin activation
echo "1. Plugin Status:\n";
if (is_plugin_active('ai-virtual-fitting/ai-virtual-fitting.php')) {
    echo "   ✓ Plugin is active\n";
    
    // Get plugin version
    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/ai-virtual-fitting/ai-virtual-fitting.php');
    echo "   Version: " . $plugin_data['Version'] . "\n";
} else {
    echo "   ✗ Plugin is NOT active\n";
}
echo "\n";

// 2. Check API configuration
echo "2. API Configuration:\n";
$api_provider = get_option('ai_virtual_fitting_api_provider', 'google_ai_studio');
echo "   Provider: " . $api_provider . "\n";

$encrypted_key = get_option('ai_virtual_fitting_google_ai_api_key');
if (!empty($encrypted_key)) {
    echo "   ✓ API key is configured (encrypted)\n";
    echo "   Encrypted key length: " . strlen($encrypted_key) . " bytes\n";
    
    // Try to decrypt
    if (class_exists('AI_Virtual_Fitting_Security_Manager')) {
        $decrypted = AI_Virtual_Fitting_Security_Manager::decrypt($encrypted_key);
        if ($decrypted !== false) {
            echo "   ✓ API key decrypts successfully\n";
            echo "   Decrypted key length: " . strlen($decrypted) . " characters\n";
            echo "   Key format: " . (preg_match('/^AIza[0-9A-Za-z_-]{35}$/', $decrypted) ? '✓ Valid Google AI format' : '✗ Invalid format') . "\n";
        } else {
            echo "   ✗ API key decryption FAILED\n";
        }
    }
} else {
    echo "   ✗ No API key configured\n";
}
echo "\n";

// 3. Check endpoints
echo "3. API Endpoints:\n";
$text_endpoint = get_option('ai_virtual_fitting_gemini_text_api_endpoint', '');
$image_endpoint = get_option('ai_virtual_fitting_gemini_image_api_endpoint', '');

if (empty($text_endpoint)) {
    $text_endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
    echo "   Text endpoint: (default) " . $text_endpoint . "\n";
} else {
    echo "   Text endpoint: (custom) " . $text_endpoint . "\n";
}

if (empty($image_endpoint)) {
    $image_endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3-pro-image-preview:generateContent';
    echo "   Image endpoint: (default) " . $image_endpoint . "\n";
} else {
    echo "   Image endpoint: (custom) " . $image_endpoint . "\n";
}

// Extract model name from endpoint
if (preg_match('/models\/([^:]+)/', $image_endpoint, $matches)) {
    echo "   Image model: " . $matches[1] . "\n";
}
echo "\n";

// 4. Test API connection
echo "4. API Connection Test:\n";
if (class_exists('AI_Virtual_Fitting_Image_Processor')) {
    $processor = new AI_Virtual_Fitting_Image_Processor();
    $test_result = $processor->test_api_connection();
    
    if ($test_result['success']) {
        echo "   ✓ " . $test_result['message'] . "\n";
    } else {
        echo "   ✗ " . $test_result['message'] . "\n";
    }
} else {
    echo "   ✗ Image processor class not found\n";
}
echo "\n";

// 5. Check recent error logs
echo "5. Recent Error Logs:\n";
$log_file = WP_CONTENT_DIR . '/debug.log';
if (file_exists($log_file)) {
    $logs = file($log_file);
    $recent_logs = array_slice($logs, -50); // Last 50 lines
    $ai_logs = array_filter($recent_logs, function($line) {
        return stripos($line, 'AI Virtual Fitting') !== false || 
               stripos($line, 'virtual fitting') !== false ||
               stripos($line, 'gemini') !== false;
    });
    
    if (!empty($ai_logs)) {
        echo "   Recent AI Virtual Fitting logs:\n";
        foreach (array_slice($ai_logs, -10) as $log) {
            echo "   " . trim($log) . "\n";
        }
    } else {
        echo "   No recent AI Virtual Fitting logs found\n";
    }
} else {
    echo "   Debug log file not found at: " . $log_file . "\n";
}
echo "\n";

// 6. Check PHP configuration
echo "6. PHP Configuration:\n";
echo "   PHP Version: " . PHP_VERSION . "\n";
echo "   Memory Limit: " . ini_get('memory_limit') . "\n";
echo "   Max Upload Size: " . ini_get('upload_max_filesize') . "\n";
echo "   Post Max Size: " . ini_get('post_max_size') . "\n";
echo "   Max Execution Time: " . ini_get('max_execution_time') . "s\n";
echo "   cURL Available: " . (function_exists('curl_version') ? '✓ Yes' : '✗ No') . "\n";
echo "   OpenSSL Available: " . (extension_loaded('openssl') ? '✓ Yes' : '✗ No') . "\n";
echo "\n";

// 7. Check database tables
echo "7. Database Tables:\n";
global $wpdb;
$credits_table = $wpdb->prefix . 'virtual_fitting_credits';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$credits_table'") === $credits_table;
echo "   Credits table: " . ($table_exists ? '✓ Exists' : '✗ Missing') . "\n";

if ($table_exists) {
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $credits_table");
    echo "   Total credit records: " . $count . "\n";
}
echo "\n";

// 8. Check file permissions
echo "8. File Permissions:\n";
$upload_dir = wp_upload_dir();
$temp_dir = $upload_dir['basedir'] . '/ai-virtual-fitting-temp';
echo "   Upload directory: " . $upload_dir['basedir'] . "\n";
echo "   Upload directory writable: " . (is_writable($upload_dir['basedir']) ? '✓ Yes' : '✗ No') . "\n";
echo "   Temp directory exists: " . (is_dir($temp_dir) ? '✓ Yes' : '✗ No') . "\n";
if (is_dir($temp_dir)) {
    echo "   Temp directory writable: " . (is_writable($temp_dir) ? '✓ Yes' : '✗ No') . "\n";
}
echo "\n";

// 9. Simulate API request format
echo "9. API Request Format Check:\n";
echo "   This is the exact format being sent to Gemini API:\n";
$sample_request = array(
    'contents' => array(
        array(
            'parts' => array(
                array('text' => 'Test prompt'),
                array(
                    'inline_data' => array(
                        'mime_type' => 'image/jpeg',
                        'data' => 'base64_encoded_image_data_here'
                    )
                )
            )
        )
    ),
    'generationConfig' => array(
        'responseModalities' => array('TEXT', 'IMAGE'),
        'imageConfig' => array(
            'aspectRatio' => '1:1',
            'imageSize' => '1K'
        )
    )
);
echo "   " . json_encode($sample_request, JSON_PRETTY_PRINT) . "\n";
echo "\n";

// 10. Recommendations
echo "10. Recommendations:\n";
if (empty($encrypted_key)) {
    echo "   ⚠ Configure API key in plugin settings\n";
}
if (!$test_result['success']) {
    echo "   ⚠ Fix API connection issues before testing virtual fitting\n";
}
if ($image_endpoint !== 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3-pro-image-preview:generateContent') {
    echo "   ⚠ Image endpoint should be: gemini-3-pro-image-preview\n";
}
echo "\n";

echo "=== Diagnostic Complete ===\n";

if (!$is_cli) {
    echo '</pre>';
}
