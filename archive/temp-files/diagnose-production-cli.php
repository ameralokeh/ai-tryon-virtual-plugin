#!/usr/bin/env php
<?php
/**
 * Production API Error Diagnostic Script - CLI Version
 * 
 * Run this directly on production server via SSH:
 * php diagnose-production-cli.php
 * 
 * Or upload to plugin directory and run:
 * cd wp-content/plugins/ai-virtual-fitting
 * php ../../../diagnose-production-cli.php
 */

// Find WordPress root
$wp_load_paths = [
    __DIR__ . '/wp-load.php',
    __DIR__ . '/../wp-load.php',
    __DIR__ . '/../../wp-load.php',
    __DIR__ . '/../../../wp-load.php',
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die("ERROR: Could not find wp-load.php. Please run this script from WordPress root or plugin directory.\n");
}

echo "=== AI Virtual Fitting Production Diagnostic (CLI) ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Server: " . gethostname() . "\n\n";

// 1. Plugin Status
echo "1. Plugin Status:\n";
if (is_plugin_active('ai-virtual-fitting/ai-virtual-fitting.php')) {
    echo "   ✓ Plugin is active\n";
    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/ai-virtual-fitting/ai-virtual-fitting.php');
    echo "   Version: " . $plugin_data['Version'] . "\n";
} else {
    echo "   ✗ Plugin is NOT active\n";
}
echo "\n";

// 2. API Configuration
echo "2. API Configuration:\n";
$api_provider = get_option('ai_virtual_fitting_api_provider', 'google_ai_studio');
echo "   Provider: " . $api_provider . "\n";

$encrypted_key = get_option('ai_virtual_fitting_google_ai_api_key');
if (!empty($encrypted_key)) {
    echo "   ✓ API key is configured (encrypted)\n";
    echo "   Encrypted key length: " . strlen($encrypted_key) . " bytes\n";
    
    if (class_exists('AI_Virtual_Fitting_Security_Manager')) {
        $decrypted = AI_Virtual_Fitting_Security_Manager::decrypt($encrypted_key);
        if ($decrypted !== false) {
            echo "   ✓ API key decrypts successfully\n";
            echo "   Decrypted key length: " . strlen($decrypted) . " characters\n";
            $is_valid = preg_match('/^AIza[0-9A-Za-z_-]{35}$/', $decrypted);
            echo "   Key format: " . ($is_valid ? '✓ Valid Google AI format' : '✗ Invalid format') . "\n";
            if (!$is_valid) {
                echo "   Key preview: " . substr($decrypted, 0, 10) . "... (first 10 chars)\n";
            }
        } else {
            echo "   ✗ API key decryption FAILED\n";
            echo "   This is likely the problem! Key needs to be re-saved.\n";
        }
    }
} else {
    echo "   ✗ No API key configured\n";
}
echo "\n";

// 3. Endpoints
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

if (preg_match('/models\/([^:]+)/', $image_endpoint, $matches)) {
    $model = $matches[1];
    echo "   Image model: " . $model . "\n";
    if ($model !== 'gemini-3-pro-image-preview') {
        echo "   ⚠ WARNING: Should be 'gemini-3-pro-image-preview'\n";
    }
}
echo "\n";

// 4. API Test
echo "4. API Connection Test:\n";
if (class_exists('AI_Virtual_Fitting_Image_Processor')) {
    $processor = new AI_Virtual_Fitting_Image_Processor();
    $test_result = $processor->test_api_connection();
    
    if ($test_result['success']) {
        echo "   ✓ " . $test_result['message'] . "\n";
    } else {
        echo "   ✗ " . $test_result['message'] . "\n";
        echo "   This is the error production is experiencing!\n";
    }
} else {
    echo "   ✗ Image processor class not found\n";
}
echo "\n";

// 5. Recent Logs
echo "5. Recent Error Logs (last 10 AI-related):\n";
$log_file = WP_CONTENT_DIR . '/debug.log';
if (file_exists($log_file)) {
    $logs = file($log_file);
    $ai_logs = array_filter($logs, function($line) {
        return stripos($line, 'AI Virtual Fitting') !== false || 
               stripos($line, 'virtual fitting') !== false ||
               stripos($line, 'gemini') !== false;
    });
    
    if (!empty($ai_logs)) {
        foreach (array_slice($ai_logs, -10) as $log) {
            echo "   " . trim($log) . "\n";
        }
    } else {
        echo "   No AI Virtual Fitting logs found\n";
    }
} else {
    echo "   Debug log not found\n";
}
echo "\n";

// 6. Database
echo "6. Database Status:\n";
global $wpdb;
$credits_table = $wpdb->prefix . 'virtual_fitting_credits';
$exists = $wpdb->get_var("SHOW TABLES LIKE '$credits_table'") === $credits_table;
echo "   Credits table: " . ($exists ? '✓ Exists' : '✗ Missing') . "\n";
if ($exists) {
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $credits_table");
    echo "   Total records: " . $count . "\n";
}
echo "\n";

// 7. Recommendations
echo "7. DIAGNOSIS & RECOMMENDATIONS:\n";
if (empty($encrypted_key)) {
    echo "   ❌ CRITICAL: No API key configured\n";
    echo "      → Go to plugin settings and enter your Google AI API key\n";
} elseif (!isset($decrypted) || $decrypted === false) {
    echo "   ❌ CRITICAL: API key is corrupted (decryption failed)\n";
    echo "      → This is the problem! Go to plugin settings and re-save your API key\n";
    echo "      → The key was likely corrupted by the masking bug\n";
} elseif (!$is_valid) {
    echo "   ❌ CRITICAL: API key format is invalid\n";
    echo "      → Key should start with 'AIza' and be 39 characters\n";
    echo "      → Re-enter your API key from Google AI Studio\n";
} elseif (!$test_result['success']) {
    echo "   ❌ CRITICAL: API connection test failed\n";
    echo "      → Error: " . $test_result['message'] . "\n";
    echo "      → Check if API key has correct permissions\n";
    echo "      → Verify endpoint is correct\n";
} else {
    echo "   ✓ All checks passed! API should be working.\n";
}
echo "\n";

echo "=== Diagnostic Complete ===\n";
