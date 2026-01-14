<?php
/**
 * Debug Credits Product Script
 */

// WordPress environment setup
define('WP_USE_THEMES', false);
require_once('/var/www/html/wp-load.php');

echo "=== Debugging Credits Product ===\n\n";

// Test WooCommerce Integration
$woocommerce_integration = new AI_Virtual_Fitting_WooCommerce_Integration();

echo "1. Testing get_or_create_credits_product():\n";
$product_id = $woocommerce_integration->get_or_create_credits_product();
echo "   Product ID: " . ($product_id ? $product_id : 'NULL') . "\n";

if ($product_id) {
    echo "\n2. Testing product details:\n";
    $product = wc_get_product($product_id);
    
    if ($product) {
        echo "   Product Name: " . $product->get_name() . "\n";
        echo "   Product Status: " . $product->get_status() . "\n";
        echo "   Product Price: $" . $product->get_price() . "\n";
        echo "   Is Purchasable: " . ($product->is_purchasable() ? 'YES' : 'NO') . "\n";
        echo "   Is Virtual: " . ($product->is_virtual() ? 'YES' : 'NO') . "\n";
        echo "   Stock Status: " . $product->get_stock_status() . "\n";
        
        // Check meta data
        $is_credits = get_post_meta($product_id, '_virtual_fitting_product', true);
        echo "   Is Credits Product Meta: " . ($is_credits === 'yes' ? 'YES' : 'NO') . "\n";
        
        $credits_amount = get_post_meta($product_id, '_virtual_fitting_credits', true);
        echo "   Credits Amount Meta: " . $credits_amount . "\n";
    } else {
        echo "   ERROR: Could not load product object\n";
    }
    
    echo "\n3. Testing validation:\n";
    
    // Test the validation method from public interface
    $public_interface = new AI_Virtual_Fitting_Public_Interface();
    
    // Use reflection to access private method
    $reflection = new ReflectionClass($public_interface);
    $validate_method = $reflection->getMethod('validate_credits_product');
    $validate_method->setAccessible(true);
    
    $validation_result = $validate_method->invoke($public_interface, $product_id);
    
    if (is_wp_error($validation_result)) {
        echo "   Validation FAILED: " . $validation_result->get_error_message() . "\n";
        echo "   Error Code: " . $validation_result->get_error_code() . "\n";
    } else {
        echo "   Validation PASSED\n";
    }
}

echo "\n4. Testing cart operations:\n";

// Initialize cart
if (!WC()->cart) {
    wc_load_cart();
}

WC()->cart->empty_cart();
echo "   Cart emptied\n";

if ($product_id) {
    $cart_item_key = WC()->cart->add_to_cart($product_id, 1);
    
    if ($cart_item_key) {
        echo "   Product added to cart successfully\n";
        echo "   Cart item key: " . $cart_item_key . "\n";
        echo "   Cart contents count: " . WC()->cart->get_cart_contents_count() . "\n";
    } else {
        echo "   ERROR: Failed to add product to cart\n";
        
        // Check for WooCommerce notices
        $notices = wc_get_notices('error');
        if (!empty($notices)) {
            echo "   WooCommerce errors:\n";
            foreach ($notices as $notice) {
                echo "     - " . $notice['notice'] . "\n";
            }
        }
    }
}

echo "\n5. Checking stored product ID option:\n";
$stored_id = get_option('ai_virtual_fitting_credits_product_id', 0);
echo "   Stored Product ID: " . $stored_id . "\n";

if ($stored_id != $product_id) {
    echo "   WARNING: Stored ID doesn't match created ID\n";
    echo "   Updating stored ID...\n";
    update_option('ai_virtual_fitting_credits_product_id', $product_id);
    echo "   Updated stored ID to: " . $product_id . "\n";
}

echo "\n=== Debug Complete ===\n";