<?php
/**
 * Test Gemini 3 Pro Image Preview Virtual Fitting with 5 Images
 * This should work with the new model that supports up to 14 images
 */

// WordPress environment setup
require_once('/var/www/html/wp-config.php');

echo "=== Gemini 3 Pro Image Preview Virtual Fitting Test ===\n\n";

// Get API key
$api_key = get_option('ai_virtual_fitting_google_ai_api_key', '');
if (empty($api_key)) {
    echo "❌ No API key found\n";
    exit(1);
}

// Create test user image
$upload_dir = wp_upload_dir();
$temp_dir = $upload_dir['basedir'] . '/ai-virtual-fitting-temp/';

if (!file_exists($temp_dir)) {
    wp_mkdir_p($temp_dir);
}

$user_photo_path = $temp_dir . 'test_user_gemini3_' . time() . '.jpg';

// Create a simple test image for user
$test_image = imagecreatetruecolor(512, 512);
$bg_color = imagecolorallocate($test_image, 220, 220, 220);
$text_color = imagecolorallocate($test_image, 50, 50, 50);
imagefill($test_image, 0, 0, $bg_color);
imagestring($test_image, 5, 160, 250, 'Test User G3', $text_color);

ob_start();
imagejpeg($test_image, null, 80);
$image_data = ob_get_contents();
ob_end_clean();
imagedestroy($test_image);

file_put_contents($user_photo_path, $image_data);
chmod($user_photo_path, 0644);

echo "✅ Created test user photo: " . basename($user_photo_path) . "\n";

// Get all 4 product images
$product_images = array(
    $upload_dir['basedir'] . '/2026/01/danielle_1-747x1024.png',
    $upload_dir['basedir'] . '/2026/01/danielle_2-768x1024.jpg',
    $upload_dir['basedir'] . '/2026/01/danielle_3-768x1024.jpg',
    $upload_dir['basedir'] . '/2026/01/danielle_4-768x1024.jpg'
);

$valid_images = array();
foreach ($product_images as $img) {
    if (file_exists($img)) {
        $valid_images[] = $img;
        echo "✅ Found product image: " . basename($img) . "\n";
    }
}

if (count($valid_images) < 4) {
    echo "⚠️  Only found " . count($valid_images) . " product images, proceeding with available images\n";
}

// Prepare exactly 5 images for API (1 user + 4 product)
$user_image_data = base64_encode(file_get_contents($user_photo_path));
$product_data = array();

foreach ($valid_images as $img) {
    $product_data[] = base64_encode(file_get_contents($img));
}

echo "✅ User image encoded: " . strlen($user_image_data) . " chars\n";
foreach ($product_data as $i => $data) {
    echo "✅ Product " . ($i + 1) . " encoded: " . strlen($data) . " chars\n";
}

// Test virtual fitting request with Gemini 3 Pro Image Preview
echo "\nTesting Gemini 3 Pro Image Preview virtual fitting API call...\n";

// Build parts array
$parts = array();
$parts[] = array('text' => 'Create a realistic virtual try-on showing the person from the first image wearing the wedding dress from the product images. Show how the dress would look on this person, maintaining their natural pose, body proportions, and lighting. The result should be a seamless, professional-looking virtual fitting that accurately represents how the dress would fit and appear on them.');

// Add user image
$parts[] = array(
    'inline_data' => array(
        'mime_type' => 'image/jpeg',
        'data' => $user_image_data
    )
);

// Add product images
foreach ($product_data as $i => $data) {
    $mime_type = ($i === 0) ? 'image/png' : 'image/jpeg'; // First is PNG, others are JPG
    $parts[] = array(
        'inline_data' => array(
            'mime_type' => $mime_type,
            'data' => $data
        )
    );
}

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

echo "Request parts count: " . count($parts) . " (1 text + " . (count($parts) - 1) . " images)\n";
echo "Request size: " . number_format(strlen(json_encode($request_data))) . " bytes\n";

$start_time = microtime(true);

$response = wp_remote_post(
    'https://generativelanguage.googleapis.com/v1beta/models/gemini-3-pro-image-preview:generateContent?key=' . $api_key,
    array(
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode($request_data),
        'timeout' => 180 // Longer timeout for Gemini 3
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
        echo "✅ Gemini 3 Pro Image Preview virtual fitting API call successful!\n";
        
        $data = json_decode($body, true);
        if (isset($data['candidates'][0]['content']['parts'])) {
            foreach ($data['candidates'][0]['content']['parts'] as $part) {
                if (isset($part['inline_data']['data'])) {
                    echo "✅ Generated image found (inline_data format)!\n";
                    echo "✅ Image data length: " . strlen($part['inline_data']['data']) . " chars\n";
                    
                    // Save the result
                    $result_path = $temp_dir . 'gemini3_virtual_fitting_result_' . time() . '.jpg';
                    $image_binary = base64_decode($part['inline_data']['data']);
                    file_put_contents($result_path, $image_binary);
                    echo "✅ Result saved: " . basename($result_path) . "\n";
                    break;
                }
                if (isset($part['inlineData']['data'])) {
                    echo "✅ Generated image found (inlineData format)!\n";
                    echo "✅ Image data length: " . strlen($part['inlineData']['data']) . " chars\n";
                    
                    // Save the result
                    $result_path = $temp_dir . 'gemini3_virtual_fitting_result_' . time() . '.jpg';
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

echo "\n=== Gemini 3 Pro Image Preview Test Complete ===\n";
?>