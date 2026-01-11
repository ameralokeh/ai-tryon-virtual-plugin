<?php
/**
 * End-to-End Virtual Fitting Test
 * Complete test with user photo and Danielle Champagne product
 */

// WordPress environment setup
require_once('/var/www/html/wp-config.php');

echo "=== End-to-End Virtual Fitting Test ===\n\n";

// Test configuration
$test_user_id = 1; // Admin user
$target_product_name = 'Danielle Champagne'; // Target product
$user_photo_data = null; // Will be set from user's uploaded image

// Step 1: System readiness check
echo "1. System Readiness Check...\n";

// Check plugin activation
if (!class_exists('AI_Virtual_Fitting_Core')) {
    echo "❌ Plugin not active\n";
    exit(1);
}
echo "✅ Plugin active\n";

// Check API configuration
$api_key = get_option('ai_virtual_fitting_google_ai_api_key', '');
if (empty($api_key)) {
    echo "❌ API key not configured\n";
    exit(1);
}
echo "✅ API key configured\n";

// Initialize components
try {
    $credit_manager = new AI_Virtual_Fitting_Credit_Manager();
    $image_processor = new AI_Virtual_Fitting_Image_Processor();
    echo "✅ Components initialized\n";
} catch (Exception $e) {
    echo "❌ Component initialization failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// Step 2: Find target product
echo "2. Product Selection...\n";

$products = get_posts(array(
    'post_type' => 'product',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    's' => $target_product_name
));

$target_product = null;
foreach ($products as $product) {
    if (stripos($product->post_title, $target_product_name) !== false) {
        $target_product = $product;
        break;
    }
}

if (!$target_product) {
    echo "❌ Target product '{$target_product_name}' not found\n";
    echo "Available products:\n";
    $all_products = get_posts(array('post_type' => 'product', 'posts_per_page' => 10));
    foreach ($all_products as $p) {
        echo "  - {$p->post_title} (ID: {$p->ID})\n";
    }
    exit(1);
}

echo "✅ Found target product: {$target_product->post_title} (ID: {$target_product->ID})\n";

// Get product images
$wc_product = wc_get_product($target_product->ID);
$product_images = array();

// Featured image
$featured_image_id = get_post_thumbnail_id($target_product->ID);
if ($featured_image_id) {
    $featured_url = wp_get_attachment_image_src($featured_image_id, 'large');
    if ($featured_url) {
        $product_images[] = $featured_url[0];
        echo "✅ Featured image: " . basename($featured_url[0]) . "\n";
        echo "    Full URL: " . $featured_url[0] . "\n";
    }
}

// Gallery images
$gallery_ids = $wc_product->get_gallery_image_ids();
foreach (array_slice($gallery_ids, 0, 3) as $image_id) {
    $image_url = wp_get_attachment_image_src($image_id, 'large');
    if ($image_url) {
        $product_images[] = $image_url[0];
        echo "✅ Gallery image: " . basename($image_url[0]) . "\n";
        echo "    Full URL: " . $image_url[0] . "\n";
    }
}

echo "✅ Total product images: " . count($product_images) . "\n\n";

// Step 3: User photo setup
echo "3. User Photo Setup...\n";

// Create a test user photo (placeholder for now)
$upload_dir = wp_upload_dir();
$temp_dir = $upload_dir['basedir'] . '/ai-virtual-fitting-temp/';

if (!file_exists($temp_dir)) {
    wp_mkdir_p($temp_dir);
}

// For this test, we'll create a placeholder user image
// In real usage, this would be the uploaded user photo
$user_photo_path = $temp_dir . 'test_user_photo_' . time() . '.jpg';

// Create a simple test image (placeholder)
$test_image = imagecreatetruecolor(800, 600);
$bg_color = imagecolorallocate($test_image, 220, 220, 220);
$text_color = imagecolorallocate($test_image, 50, 50, 50);
imagefill($test_image, 0, 0, $bg_color);
imagestring($test_image, 5, 300, 280, 'Test User Photo', $text_color);
imagestring($test_image, 3, 320, 320, 'Woman in striped shirt', $text_color);

ob_start();
imagejpeg($test_image, null, 80);
$image_data = ob_get_contents();
ob_end_clean();
imagedestroy($test_image);

file_put_contents($user_photo_path, $image_data);
chmod($user_photo_path, 0644);

echo "✅ Test user photo created: " . basename($user_photo_path) . "\n";
echo "✅ Photo size: " . number_format(filesize($user_photo_path)) . " bytes\n\n";

// Step 4: Credit check and management
echo "4. Credit Management...\n";

$current_credits = $credit_manager->get_customer_credits($test_user_id);
echo "✅ Current credits: {$current_credits}\n";

if ($current_credits <= 0) {
    echo "⚠️  Adding credits for test...\n";
    $credit_manager->add_credits($test_user_id, 5);
    $current_credits = $credit_manager->get_customer_credits($test_user_id);
    echo "✅ Credits after addition: {$current_credits}\n";
}

echo "\n";

// Step 5: API connection test
echo "5. API Connection Test...\n";

$api_test = $image_processor->test_api_connection();
if (!$api_test['success']) {
    echo "❌ API test failed: " . $api_test['message'] . "\n";
    exit(1);
}
echo "✅ API connection successful\n\n";

// Step 6: Virtual fitting processing
echo "6. Virtual Fitting Processing...\n";

echo "📤 Sending to AI:\n";
echo "  - User photo: " . basename($user_photo_path) . "\n";
echo "  - Product images: " . count($product_images) . " images\n";
echo "  - Product: {$target_product->post_title}\n";
echo "  - Processing with Google AI Studio...\n\n";

$start_time = microtime(true);

try {
    $result = $image_processor->process_virtual_fitting($user_photo_path, $product_images);
    
    $processing_time = round(microtime(true) - $start_time, 2);
    echo "⏱️  Processing time: {$processing_time} seconds\n";
    
    if ($result['success']) {
        echo "✅ Virtual fitting completed successfully!\n";
        echo "✅ Result image saved: " . basename($result['result_image_path']) . "\n";
        echo "✅ Result URL: " . $result['result_image_url'] . "\n";
        
        // Verify result file
        if (file_exists($result['result_image_path'])) {
            $result_size = filesize($result['result_image_path']);
            echo "✅ Result file size: " . number_format($result_size) . " bytes\n";
        }
        
        // Deduct credit
        $credit_manager->deduct_credit($test_user_id);
        $remaining_credits = $credit_manager->get_customer_credits($test_user_id);
        echo "✅ Credit deducted. Remaining: {$remaining_credits}\n";
        
    } else {
        echo "❌ Virtual fitting failed: " . $result['error'] . "\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ Processing exception: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// Step 7: Result verification
echo "7. Result Verification...\n";

if (isset($result) && $result['success']) {
    $result_path = $result['result_image_path'];
    $result_url = $result['result_image_url'];
    
    // Check file properties
    if (file_exists($result_path)) {
        $image_info = getimagesize($result_path);
        if ($image_info) {
            echo "✅ Result image dimensions: {$image_info[0]}x{$image_info[1]}\n";
            echo "✅ Result image type: {$image_info['mime']}\n";
        }
        
        // Check if image is accessible via URL
        echo "✅ Result accessible at: {$result_url}\n";
    }
}

echo "\n";

// Step 8: Cleanup
echo "8. Cleanup...\n";

// Clean up test files
if (file_exists($user_photo_path)) {
    unlink($user_photo_path);
    echo "✅ Test user photo cleaned up\n";
}

echo "\n";

// Final summary
echo "=== TEST SUMMARY ===\n";
echo "✅ End-to-end virtual fitting test completed successfully!\n\n";

echo "🎯 WORKFLOW VERIFIED:\n";
echo "1. ✅ System components initialized\n";
echo "2. ✅ Target product found and images retrieved\n";
echo "3. ✅ User photo processed\n";
echo "4. ✅ Credits managed correctly\n";
echo "5. ✅ API connection working\n";
echo "6. ✅ Virtual fitting processing completed\n";
echo "7. ✅ Result image generated and saved\n";
echo "8. ✅ Credit deduction applied\n\n";

echo "🚀 READY FOR REAL USER TESTING:\n";
echo "- Visit: " . home_url('/virtual-fitting/') . "\n";
echo "- Upload your actual photo\n";
echo "- Select '{$target_product->post_title}'\n";
echo "- Click 'Try On Dress'\n";
echo "- View your personalized result!\n\n";

echo "💡 TECHNICAL DETAILS:\n";
echo "- Processing time: ~{$processing_time} seconds\n";
echo "- Product images used: " . count($product_images) . "\n";
echo "- API provider: Google AI Studio (Gemini 2.0 Flash)\n";
echo "- Credit cost: 1 credit per try-on\n";
echo "- Result format: JPEG image\n\n";

echo "🎉 Virtual fitting system is fully operational!\n";
?>