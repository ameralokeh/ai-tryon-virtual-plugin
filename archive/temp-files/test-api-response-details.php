<?php
/**
 * Test API Response Details
 * Check what the API actually returns
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

echo "=== Checking API Response Details ===\n\n";

// Get API key
$encrypted_key = get_option('ai_virtual_fitting_google_ai_api_key', '');
$api_key = AI_Virtual_Fitting_Security_Manager::decrypt($encrypted_key);

$endpoint = get_option('ai_virtual_fitting_gemini_image_api_endpoint', '');
if (empty($endpoint)) {
    $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3-pro-image-preview:generateContent';
}

// Simple test with minimal data
$prompt = "Describe this image briefly.";

// Create a tiny test image (1x1 pixel PNG)
$tiny_png = base64_encode(file_get_contents('data://image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));

$parts = array();
$parts[] = array('text' => $prompt);
$parts[] = array(
    'inline_data' => array(
        'mime_type' => 'image/png',
        'data' => $tiny_png
    )
);

$request_data = array(
    'contents' => array(
        array(
            'parts' => $parts
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

echo "Testing with minimal data...\n";
echo "Endpoint: $endpoint\n\n";

$response = wp_remote_post(
    $endpoint,
    array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'x-goog-api-key' => $api_key,
        ),
        'body' => json_encode($request_data),
        'timeout' => 60,
        'sslverify' => true
    )
);

$response_code = wp_remote_retrieve_response_code($response);
$response_body = wp_remote_retrieve_body($response);

echo "Response code: $response_code\n\n";

if ($response_code !== 200) {
    echo "❌ Error response:\n";
    $error_data = json_decode($response_body, true);
    echo json_encode($error_data, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "✓ Success!\n\n";
    $data = json_decode($response_body, true);
    
    echo "Full response structure:\n";
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

echo "\n=== Test Complete ===\n";
