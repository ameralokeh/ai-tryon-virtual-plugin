<?php
/**
 * Verify Product Visibility in Virtual Fitting Interface
 */

require_once('/var/www/html/wp-load.php');

echo "=== Product Visibility Verification ===\n\n";

// Get all products
$all_products_args = array(
    'post_type' => 'product',
    'posts_per_page' => -1,
    'post_status' => 'publish'
);
$all_products = get_posts($all_products_args);

echo "1. All Published Products:\n";
echo "   Total: " . count($all_products) . "\n";
foreach ($all_products as $p) {
    $product = wc_get_product($p->ID);
    echo "   - ID: " . $p->ID . " | " . $product->get_name() . " | $" . $product->get_price() . "\n";
}

echo "\n2. Virtual Fitting Product Query (with exclusion):\n";
$credit_product_id = get_option('ai_virtual_fitting_credit_product_id');
echo "   Credit Product ID to exclude: " . $credit_product_id . "\n";

$vf_args = array(
    'post_type' => 'product',
    'posts_per_page' => 20,
    'post_status' => 'publish'
);

if ($credit_product_id) {
    $vf_args['post__not_in'] = array($credit_product_id);
}

$vf_products = get_posts($vf_args);
echo "   Total products returned: " . count($vf_products) . "\n";

$credit_found = false;
foreach ($vf_products as $p) {
    $product = wc_get_product($p->ID);
    echo "   - ID: " . $p->ID . " | " . $product->get_name() . "\n";
    
    if ($p->ID == $credit_product_id) {
        $credit_found = true;
    }
}

echo "\n3. Verification Results:\n";
if ($credit_found) {
    echo "   ✗ FAIL: Credit product IS visible in virtual fitting\n";
} else {
    echo "   ✓ PASS: Credit product is NOT visible in virtual fitting\n";
}

echo "   Expected products: " . (count($all_products) - 1) . "\n";
echo "   Actual products: " . count($vf_products) . "\n";

if (count($vf_products) == (count($all_products) - 1)) {
    echo "   ✓ PASS: Product count matches (all products minus credit product)\n";
} else {
    echo "   ⚠ WARNING: Product count mismatch\n";
}

echo "\n4. Credit Product Details:\n";
if ($credit_product_id) {
    $credit_product = wc_get_product($credit_product_id);
    if ($credit_product) {
        echo "   Name: " . $credit_product->get_name() . "\n";
        echo "   Price: $" . $credit_product->get_price() . "\n";
        echo "   Status: " . get_post_status($credit_product_id) . "\n";
        echo "   Visibility: " . $credit_product->get_catalog_visibility() . "\n";
        echo "   Virtual: " . ($credit_product->is_virtual() ? 'Yes' : 'No') . "\n";
        
        $hidden_meta = get_post_meta($credit_product_id, '_ai_virtual_fitting_hidden_product', true);
        echo "   Hidden Meta: " . ($hidden_meta === 'yes' ? 'Yes' : 'No') . "\n";
        
        $visibility_terms = wp_get_post_terms($credit_product_id, 'product_visibility', array('fields' => 'names'));
        echo "   Visibility Terms: " . implode(', ', $visibility_terms) . "\n";
    }
}

echo "\n=== Verification Complete ===\n";
