<?php
/**
 * Test Gemini API with real image from the system
 */

// WordPress environment setup
require_once('/var/www/html/wp-config.php');

echo "=== Real Image API Test ===\n\n";

// Get API key
$api_key = get_option('ai_virtual_fitting_google_ai_api_key', '');
if (empty($api_key)) {
    echo "❌ No API key found\n";
    exit(1);
}

// Find a real product image to test with
$upload_dir = wp_upload_dir();
$image_path = $upload_dir['basedir'] . '/2026/01/danielle_1-747x1024.png';

if (!file_exists($image_path)) {
    echo "❌ Test image not found at: $image_path\n";
    exit(1);
}

echo "✅ Found test image: " . basename($image_path) . "\n";
echo "✅ Image size: " . number_format(filesize($image_path)) . " bytes\n";

// Get image info
$image_info = getimagesize($image_path);
if ($image_info) {
    echo "✅ Image dimensions: {$image_info[0]}x{$image_info[1]}\n";
    echo "✅ Image type: {$image_info['mime']}\n";
}

// Encode image
$image_data = base64_encode(file_get_contents($image_path));
echo "✅ Base64 encoded length: " . strlen($image_data) . " characters\n";

// Test with real image
echo "\nTesting with real product image...\n";

$request_data = array(
    'contents' => array(
        array(
            'parts' => array(
                array('text' => 'Describe this wedding dress in detail'),
                array(
                    'inline_data' => array(
                        'mime_type' => 'image/png',
                        'data' => $image_data
                    )
                )
            )
        )
    )
);

echo "Request size: " . strlen(json_encode($request_data)) . " bytes\n";

$response = wp_remote_post(
    'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent?key=' . $api_key,
    array(
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode($request_data),
        'timeout' => 120
    )
);

if (is_wp_error($response)) {
    echo "❌ Request failed: " . $response->get_error_message() . "\n";
} else {
    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    echo "Response code: $code\n";
    
    if ($code === 200) {
        echo "✅ Real image processing works!\n";
        $data = json_decode($body, true);
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            echo "Response: " . substr($data['candidates'][0]['content']['parts'][0]['text'], 0, 200) . "...\n";
        }
    } else {
        echo "❌ Error response: " . substr($body, 0, 500) . "...\n";
        
        // Try to parse error details
        $error_data = json_decode($body, true);
        if (isset($error_data['error']['message'])) {
            echo "Error message: " . $error_data['error']['message'] . "\n";
        }
    }
}

echo "\n=== Test Complete ===\n";
?>