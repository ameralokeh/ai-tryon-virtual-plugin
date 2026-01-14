<?php
/**
 * Restore Credit Product from Trash
 */

require_once('/var/www/html/wp-load.php');

echo "=== Restoring Credit Product ===\n\n";

$product_id = get_option('ai_virtual_fitting_credit_product_id');
echo "Product ID: " . $product_id . "\n";

if ($product_id) {
    $current_status = get_post_status($product_id);
    echo "Current status: " . $current_status . "\n";
    
    if ($current_status === 'trash') {
        echo "Restoring from trash...\n";
        
        // Untrash the product
        $result = wp_untrash_post($product_id);
        
        if ($result) {
            echo "✓ Product restored successfully\n";
            
            // Verify restoration
            $new_status = get_post_status($product_id);
            echo "New status: " . $new_status . "\n";
            
            // Ensure it's published
            if ($new_status !== 'publish') {
                wp_update_post(array(
                    'ID' => $product_id,
                    'post_status' => 'publish'
                ));
                echo "✓ Product status set to publish\n";
            }
            
            // Verify product details
            $product = wc_get_product($product_id);
            if ($product) {
                echo "\nProduct Details:\n";
                echo "  Name: " . $product->get_name() . "\n";
                echo "  Price: $" . $product->get_price() . "\n";
                echo "  Virtual: " . ($product->is_virtual() ? 'Yes' : 'No') . "\n";
                echo "  Visibility: " . $product->get_catalog_visibility() . "\n";
                echo "  Status: " . get_post_status($product_id) . "\n";
            }
        } else {
            echo "✗ Failed to restore product\n";
        }
    } else {
        echo "Product is not in trash (status: " . $current_status . ")\n";
    }
}

echo "\n=== Complete ===\n";
