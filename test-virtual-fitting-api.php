<?php
/**
 * Test Virtual Fitting API Call
 * Test the exact scenario we're trying to implement
 */

// WordPress environment setup
require_once('/var/www/html/wp-config.php');

echo "=== Virtual Fitting API Test ===\n\n";

// Get API key
$api_key = get_option('ai_virtual_fitting_google_ai_api_key', '');
if (empty($api_key)) {
    echo "❌ No API key found\n";
    exit(1);
}

// Create a simple user image (placeholder)
$upload_dir = wp_upload_dir();
$temp_dir = $upload_dir['basedir'] . '/ai-virtual-fitting-temp/';

if (!file_exists($temp_dir)) {
    wp_mkdir_p($temp_dir);
}

$user_photo_path = $temp_dir . 'test_user_photo_' . time() . '.jpg';

// Create a simple test image for user
$test_image = imagecreatetruecolor(512, 512);
$bg_color = imagecolorallocate($test_image, 220, 220, 220);
$text_color = imagecolorallocate($test_image, 50, 50, 50);
imagefill($test_image, 0, 0, $bg_color);
imagestring($test_image, 5, 200, 250, 'Test User', $text_color);

ob_start();
imagejpeg($test_image, null, 80);
$image_data = ob_get_contents();
ob_end_clean();
imagedestroy($test_image);

file_put_contents($user_photo_path, $image_data);
chmod($user_photo_path, 0644);

echo "✅ Created test user photo: " . basename($user_photo_path) . "\n";

// Get product image
$product_image_path = $upload_dir['basedir'] . '/2026/01/danielle_1-747x1024.png';

if (!file_exists($product_image_path)) {
    echo "❌ Product image not found\n";
    exit(1);
}

echo "✅ Found product image: " . basename($product_image_path) . "\n";

// Prepare images for API
$user_image_data = base64_encode(file_get_contents($user_photo_path));
$product_image_data = base64_encode(file_get_contents($product_image_path));

echo "✅ User image encoded: " . strlen($user_image_data) . " chars\n";
echo "✅ Product image encoded: " . strlen($product_image_data) . " chars\n";

// Test virtual fitting request
echo "\nTesting virtual fitting API call...\n";

$request_data = array(
    'contents' => array(
        array(
            'parts' => array(
                array('text' => 'Create a realistic virtual try-on showing the person from the first image wearing the wedding dress from the second image. The result should look natural and maintain the person\'s pose and lighting.'),
                array(
                    'inline_data' => array(
                        'mime_type' => 'image/jpeg',
                        'data' => $user_image_data
                    )
                ),
                array(
                    'inline_data' => array(
                        'mime_type' => 'image/png',
                        'data' => $product_image_data
                    )
                )
            )
        )
    )
);

echo "Request size: " . number_format(strlen(json_encode($request_data))) . " bytes\n";

$start_time = microtime(true);

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

$processing_time = round(microtime(true) - $start_time, 2);
echo "Processing time: {$processing_time} seconds\n";

if (is_wp_error($response)) {
    echo "❌ Request failed: " . $response->get_error_message() . "\n";
} else {
    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    echo "Response code: $code\n";
    
    if ($code === 200) {
        echo "✅ Virtual fitting API call successful!\n";
        
        $data = json_decode($body, true);
        if (isset($data['candidates'][0]['content']['parts'])) {
            foreach ($data['candidates'][0]['content']['parts'] as $part) {
                if (isset($part['inline_data']['data'])) {
                    echo "✅ Generated image found (inline_data format)!\n";
                    echo "✅ Image data length: " . strlen($part['inline_data']['data']) . " chars\n";
                    
                    // Save the result
                    $result_path = $temp_dir . 'virtual_fitting_result_' . time() . '.jpg';
                    $image_binary = base64_decode($part['inline_data']['data']);
                    file_put_contents($result_path, $image_binary);
                    echo "✅ Result saved: " . basename($result_path) . "\n";
                    break;
                }
                if (isset($part['inlineData']['data'])) {
                    echo "✅ Generated image found (inlineData format)!\n";
                    echo "✅ Image data length: " . strlen($part['inlineData']['data']) . " chars\n";
                    
                    // Save the result
                    $result_path = $temp_dir . 'virtual_fitting_result_' . time() . '.jpg';
                    $image_binary = base64_decode($part['inlineData']['data']);
                    file_put_contents($result_path, $image_binary);
                    echo "✅ Result saved: " . basename($result_path) . "\n";
                    break;
                }
                if (isset($part['text'])) {
                    echo "Text response: " . substr($part['text'], 0, 100) . "...\n";
                }
            }
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

// Cleanup
if (file_exists($user_photo_path)) {
    unlink($user_photo_path);
}

echo "\n=== Virtual Fitting Test Complete ===\n";
?>