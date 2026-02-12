<?php
/**
 * Test Virtual Fitting API Call
 * Simulates the exact API call that production is making
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

echo "=== Testing Virtual Fitting API Call ===\n\n";

// Get API key
$encrypted_key = get_option('ai_virtual_fitting_google_ai_api_key', '');
if (empty($encrypted_key)) {
    echo "❌ No API key found\n";
    exit(1);
}

$api_key = AI_Virtual_Fitting_Security_Manager::decrypt($encrypted_key);
if (empty($api_key)) {
    echo "❌ Failed to decrypt API key\n";
    exit(1);
}

echo "✓ API key retrieved\n\n";

// Get endpoint
$endpoint = get_option('ai_virtual_fitting_gemini_image_api_endpoint', '');
if (empty($endpoint)) {
    $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3-pro-image-preview:generateContent';
}

echo "Endpoint: $endpoint\n\n";

// Create test images (small test images)
$test_customer_image = '/var/www/html/wp-content/uploads/test-customer.jpg';
$test_product_image = '/var/www/html/wp-content/uploads/test-product.jpg';

// Check if test images exist, if not use any available image
if (!file_exists($test_customer_image)) {
    // Try to find any image in uploads
    $uploads_dir = '/var/www/html/wp-content/uploads';
    $images = glob($uploads_dir . '/*.{jpg,jpeg,png}', GLOB_BRACE);
    if (!empty($images)) {
        $test_customer_image = $images[0];
        $test_product_image = isset($images[1]) ? $images[1] : $images[0];
    } else {
        echo "❌ No test images found. Please upload test images first.\n";
        exit(1);
    }
}

echo "Customer image: $test_customer_image\n";
echo "Product image: $test_product_image\n\n";

// Prepare images data (exactly as the plugin does)
$images_data = array();

// Customer image
$customer_mime = 'image/jpeg';
$customer_data = base64_encode(file_get_contents($test_customer_image));
$images_data[] = array(
    'inlineData' => array(
        'mimeType' => $customer_mime,
        'data' => $customer_data
    )
);

// Product image
$product_mime = 'image/jpeg';
$product_data = base64_encode(file_get_contents($test_product_image));
$images_data[] = array(
    'inlineData' => array(
        'mimeType' => $product_mime,
        'data' => $product_data
    )
);

echo "Images prepared:\n";
echo "  - Customer: " . strlen($customer_data) . " bytes (base64)\n";
echo "  - Product: " . strlen($product_data) . " bytes (base64)\n\n";

// Prepare prompt
$prompt = "Generate a virtual try-on image showing the person wearing the dress.";

// Prepare request (exactly as plugin does)
$parts = array();
$parts[] = array('text' => $prompt);

foreach ($images_data as $image_data) {
    $parts[] = array(
        'inline_data' => array(
            'mime_type' => $image_data['inlineData']['mimeType'],
            'data' => $image_data['inlineData']['data']
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

echo "Request structure:\n";
echo "  - Parts count: " . count($parts) . "\n";
echo "  - Has text: " . (isset($parts[0]['text']) ? 'YES' : 'NO') . "\n";
echo "  - Images: " . (count($parts) - 1) . "\n";
echo "  - Response modalities: " . implode(', ', $request_data['generationConfig']['responseModalities']) . "\n";
echo "  - Image config: " . json_encode($request_data['generationConfig']['imageConfig']) . "\n\n";

// Make API call
echo "Making API call...\n\n";

$response = wp_remote_post(
    $endpoint,
    array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'x-goog-api-key' => $api_key,
        ),
        'body' => json_encode($request_data),
        'timeout' => 120,
        'sslverify' => true
    )
);

if (is_wp_error($response)) {
    echo "❌ Request failed: " . $response->get_error_message() . "\n";
    exit(1);
}

$response_code = wp_remote_retrieve_response_code($response);
$response_body = wp_remote_retrieve_body($response);

echo "Response code: $response_code\n\n";

if ($response_code !== 200) {
    echo "❌ API Error:\n";
    echo $response_body . "\n\n";
    
    // Try to parse error
    $error_data = json_decode($response_body, true);
    if (isset($error_data['error'])) {
        echo "Error details:\n";
        echo "  - Message: " . ($error_data['error']['message'] ?? 'N/A') . "\n";
        echo "  - Code: " . ($error_data['error']['code'] ?? 'N/A') . "\n";
        echo "  - Status: " . ($error_data['error']['status'] ?? 'N/A') . "\n";
        
        if (isset($error_data['error']['details'])) {
            echo "  - Details: " . json_encode($error_data['error']['details'], JSON_PRETTY_PRINT) . "\n";
        }
    }
    exit(1);
}

echo "✓ API call successful!\n\n";

// Parse response
$data = json_decode($response_body, true);
if (isset($data['candidates'])) {
    echo "Response contains " . count($data['candidates']) . " candidate(s)\n";
    
    if (isset($data['candidates'][0]['content']['parts'])) {
        echo "Parts in response: " . count($data['candidates'][0]['content']['parts']) . "\n";
        
        foreach ($data['candidates'][0]['content']['parts'] as $index => $part) {
            if (isset($part['text'])) {
                echo "  - Part $index: TEXT (" . strlen($part['text']) . " chars)\n";
            }
            if (isset($part['inline_data'])) {
                echo "  - Part $index: IMAGE (" . strlen($part['inline_data']['data']) . " bytes base64)\n";
            }
        }
    }
}

echo "\n=== Test Complete ===\n";
