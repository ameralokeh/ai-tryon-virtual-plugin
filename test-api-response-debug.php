<?php
/**
 * Debug API Response Test
 * Check what Google AI Studio is actually returning
 */

// WordPress environment setup
require_once('/var/www/html/wp-config.php');

echo "=== API Response Debug Test ===\n\n";

// Get API key
$api_key = get_option('ai_virtual_fitting_google_ai_api_key', '');
if (empty($api_key)) {
    echo "❌ API key not configured\n";
    exit(1);
}

echo "✅ API key found: " . substr($api_key, 0, 10) . "...\n\n";

// Test simple text request first
echo "1. Testing simple text request...\n";

$simple_request = array(
    'contents' => array(
        array(
            'parts' => array(
                array('text' => 'Hello, please respond with "API test successful"')
            )
        )
    ),
    'generationConfig' => array(
        'temperature' => 0.1,
        'maxOutputTokens' => 50,
    )
);

$api_endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent';

$response = wp_remote_post(
    $api_endpoint . '?key=' . $api_key,
    array(
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode($simple_request),
        'timeout' => 30,
        'sslverify' => true
    )
);

if (is_wp_error($response)) {
    echo "❌ Request failed: " . $response->get_error_message() . "\n";
    exit(1);
}

$response_code = wp_remote_retrieve_response_code($response);
$response_body = wp_remote_retrieve_body($response);

echo "Response Code: $response_code\n";
echo "Response Body:\n";
echo "================\n";
echo $response_body . "\n";
echo "================\n\n";

if ($response_code === 200) {
    $data = json_decode($response_body, true);
    if ($data) {
        echo "✅ JSON parsed successfully\n";
        echo "Structure:\n";
        print_r(array_keys($data));
        
        if (isset($data['candidates'])) {
            echo "\nCandidates found: " . count($data['candidates']) . "\n";
            if (isset($data['candidates'][0])) {
                echo "First candidate structure:\n";
                print_r(array_keys($data['candidates'][0]));
                
                if (isset($data['candidates'][0]['content'])) {
                    echo "\nContent structure:\n";
                    print_r(array_keys($data['candidates'][0]['content']));
                    
                    if (isset($data['candidates'][0]['content']['parts'])) {
                        echo "\nParts found: " . count($data['candidates'][0]['content']['parts']) . "\n";
                        if (isset($data['candidates'][0]['content']['parts'][0])) {
                            echo "First part structure:\n";
                            print_r($data['candidates'][0]['content']['parts'][0]);
                        }
                    }
                }
            }
        }
    } else {
        echo "❌ Failed to parse JSON\n";
    }
} else {
    echo "❌ API returned error code: $response_code\n";
}

echo "\n";

// Test with image if simple text works
if ($response_code === 200) {
    echo "2. Testing with image...\n";
    
    // Create a simple test image
    $test_image = imagecreatetruecolor(100, 100);
    $bg_color = imagecolorallocate($test_image, 255, 255, 255);
    imagefill($test_image, 0, 0, $bg_color);
    
    ob_start();
    imagejpeg($test_image, null, 80);
    $image_data = ob_get_contents();
    ob_end_clean();
    imagedestroy($test_image);
    
    $image_request = array(
        'contents' => array(
            array(
                'parts' => array(
                    array('text' => 'Describe this image briefly.'),
                    array(
                        'inlineData' => array(
                            'mimeType' => 'image/jpeg',
                            'data' => base64_encode($image_data)
                        )
                    )
                )
            )
        ),
        'generationConfig' => array(
            'temperature' => 0.1,
            'maxOutputTokens' => 100,
        )
    );
    
    $image_response = wp_remote_post(
        $api_endpoint . '?key=' . $api_key,
        array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($image_request),
            'timeout' => 30,
            'sslverify' => true
        )
    );
    
    if (is_wp_error($image_response)) {
        echo "❌ Image request failed: " . $image_response->get_error_message() . "\n";
    } else {
        $image_response_code = wp_remote_retrieve_response_code($image_response);
        $image_response_body = wp_remote_retrieve_body($image_response);
        
        echo "Image Response Code: $image_response_code\n";
        if ($image_response_code === 200) {
            $image_data = json_decode($image_response_body, true);
            if (isset($image_data['candidates'][0]['content']['parts'][0]['text'])) {
                echo "✅ Image processing successful\n";
                echo "AI Response: " . $image_data['candidates'][0]['content']['parts'][0]['text'] . "\n";
            } else {
                echo "❌ Image response missing text\n";
                echo "Response: " . $image_response_body . "\n";
            }
        } else {
            echo "❌ Image request failed with code: $image_response_code\n";
            echo "Response: " . $image_response_body . "\n";
        }
    }
}

echo "\n=== Debug Complete ===\n";
?>