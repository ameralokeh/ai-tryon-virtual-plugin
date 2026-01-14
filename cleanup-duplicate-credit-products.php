<?php
/**
 * Cleanup Duplicate Credit Products
 */

require_once('/var/www/html/wp-load.php');

echo "=== Cleanup Duplicate Credit Products ===\n\n";

// Find all credit products
$all_products = get_posts(array(
    'post_type' => 'product',
    'posts_per_page' => -1,
    'post_status' => array('publish', 'draft', 'trash')
));

$credit_products = array();
foreach ($all_products as $p) {
    $product = wc_get_product($p->ID);
    if ($product && strpos(strtolower($product->get_name()), 'virtual fitting credit') !== false) {
        $credit_products[] = array(
            'id' => $p->ID,
            'name' => $product->get_name(),
            'price' => $product->get_price(),
            'status' => get_post_status($p->ID),
            'is_virtual' => $product->is_virtual()
        );
    }
}

echo "Found " . count($credit_products) . " credit products:\n";
foreach ($credit_products as $cp) {
    echo "  - ID: " . $cp['id'] . " | " . $cp['name'] . " | $" . $cp['price'] . " | Status: " . $cp['status'] . "\n";
}

$official_product_id = get_option('ai_virtual_fitting_credit_product_id');
echo "\nOfficial product ID from options: " . $official_product_id . "\n";

// Delete duplicates
$deleted = array();
foreach ($credit_products as $cp) {
    if ($cp['id'] != $official_product_id) {
        echo "\nDeleting duplicate product ID: " . $cp['id'] . " (" . $cp['name'] . ")\n";
        
        // Force delete (bypass hooks)
        $result = wp_delete_post($cp['id'], true);
        
        if ($result) {
            echo "  ✓ Deleted successfully\n";
            $deleted[] = $cp['id'];
        } else {
            echo "  ✗ Failed to delete\n";
        }
    }
}

echo "\n=== Summary ===\n";
echo "Deleted " . count($deleted) . " duplicate product(s)\n";
echo "Remaining official product ID: " . $official_product_id . "\n";

// Verify
$remaining_product = wc_get_product($official_product_id);
if ($remaining_product) {
    echo "✓ Official product still exists: " . $remaining_product->get_name() . "\n";
} else {
    echo "✗ Official product not found!\n";
}

echo "\n=== Complete ===\n";
