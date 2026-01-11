<?php
/**
 * Simple Gemini API Debug Test
 * Test the exact API format from Google's documentation
 */

// WordPress environment setup
require_once('/var/www/html/wp-config.php');

echo "=== Gemini API Debug Test ===\n\n";

// Get API key
$api_key = get_option('ai_virtual_fitting_google_ai_api_key', '');
if (empty($api_key)) {
    echo "❌ No API key found\n";
    exit(1);
}

echo "✅ API key found\n";

// Create a simple test image (base64 encoded 1x1 pixel PNG)
$test_image_data = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChAI9jU77zgAAAABJRU5ErkJggg==';

// Test 1: Simple text-to-image (no input images)
echo "\n1. Testing simple text-to-image...\n";

$request_data = array(
    'contents' => array(
        array(
            'parts' => array(
                array('text' => 'A simple red circle on white background')
            )
        )
    )
);

$response = wp_remote_post(
    'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent?key=' . $api_key,
    array(
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode($request_data),
        'timeout' => 60
    )
);

if (is_wp_error($response)) {
    echo "❌ Request failed: " . $response->get_error_message() . "\n";
} else {
    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    echo "Response code: $code\n";
    
    if ($code === 200) {
        echo "✅ Simple text-to-image works!\n";
        $data = json_decode($body, true);
        if (isset($data['candidates'][0]['content']['parts'])) {
            echo "✅ Response has expected structure\n";
        }
    } else {
        echo "❌ Error response: " . substr($body, 0, 200) . "...\n";
    }
}

// Test 2: Text + image input (virtual fitting scenario)
echo "\n2. Testing text + image input...\n";

$request_data = array(
    'contents' => array(
        array(
            'parts' => array(
                array('text' => 'Create a virtual try-on showing this person wearing a wedding dress'),
                array(
                    'inline_data' => array(
                        'mime_type' => 'image/png',
                        'data' => $test_image_data
                    )
                )
            )
        )
    )
);

$response = wp_remote_post(
    'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent?key=' . $api_key,
    array(
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode($request_data),
        'timeout' => 60
    )
);

if (is_wp_error($response)) {
    echo "❌ Request failed: " . $response->get_error_message() . "\n";
} else {
    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    echo "Response code: $code\n";
    
    if ($code === 200) {
        echo "✅ Text + image input works!\n";
    } else {
        echo "❌ Error response: " . substr($body, 0, 500) . "...\n";
        
        // Try to parse error details
        $error_data = json_decode($body, true);
        if (isset($error_data['error']['message'])) {
            echo "Error message: " . $error_data['error']['message'] . "\n";
        }
    }
}

echo "\n=== Debug Complete ===\n";
?>