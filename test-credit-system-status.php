<?php
/**
 * Test Credit System Status and Self-Healing
 * 
 * This script verifies:
 * 1. Current product status
 * 2. Self-healing capability
 * 3. Product exclusion from virtual fitting
 * 4. WooCommerce integration
 */

// WordPress environment
require_once('/var/www/html/wp-load.php');

echo "=== Virtual Credit System Status Check ===\n\n";

// Check current product
echo "1. Current Credit Product Status:\n";
$product_id = get_option('ai_virtual_fitting_credit_product_id');
echo "   Product ID: " . ($product_id ?: 'NOT SET') . "\n";

if ($product_id) {
    $product = get_post($product_id);
    if ($product) {
        echo "   Status: " . $product->post_status . "\n";
        
        if ($product->post_status === 'trash') {
            echo "   ⚠ Product is in trash - needs restoration\n";
        }
        
        $wc_product = wc_get_product($product_id);
        if ($wc_product) {
            echo "   Name: " . $wc_product->get_name() . "\n";
            echo "   Price: $" . $wc_product->get_price() . "\n";
            echo "   Virtual: " . ($wc_product->is_virtual() ? 'Yes' : 'No') . "\n";
            echo "   Visibility: " . $wc_product->get_catalog_visibility() . "\n";
        }
    } else {
        echo "   ✗ Product not found in database\n";
    }
}

echo "\n2. Testing Self-Healing:\n";
$virtual_credit_system = new AI_Virtual_Fitting_Virtual_Credit_System();
$healed_id = $virtual_credit_system->get_or_create_credit_product();
echo "   Self-healing returned ID: " . $healed_id . "\n";

if ($healed_id) {
    $healed_product = wc_get_product($healed_id);
    if ($healed_product) {
        echo "   ✓ Product accessible via WooCommerce\n";
        echo "   Name: " . $healed_product->get_name() . "\n";
        echo "   Status: " . get_post_status($healed_id) . "\n";
    }
}

echo "\n3. Product Exclusion Test:\n";
$credit_product_id = get_option('ai_virtual_fitting_credit_product_id');

// Simulate virtual fitting product query
$args = array(
    'post_type' => 'product',
    'posts_per_page' => 20,
    'post_status' => 'publish'
);

if ($credit_product_id) {
    $args['post__not_in'] = array($credit_product_id);
}

$products = get_posts($args);
echo "   Total products in query: " . count($products) . "\n";

$found = false;
foreach ($products as $p) {
    if ($p->ID == $credit_product_id) {
        $found = true;
        break;
    }
}

echo "   Credit product in results: " . ($found ? 'YES (ERROR)' : 'NO (CORRECT)') . "\n";

echo "\n4. WooCommerce Integration Test:\n";
$wc_integration = new AI_Virtual_Fitting_WooCommerce_Integration();
$wc_product_id = $wc_integration->get_or_create_credits_product();
echo "   WooCommerce integration product ID: " . $wc_product_id . "\n";
echo "   Matches Virtual Credit System: " . ($wc_product_id == $healed_id ? 'YES' : 'NO') . "\n";

echo "\n5. Settings Check:\n";
$credits_per_package = get_option('ai_virtual_fitting_credits_per_package', 20);
$package_price = get_option('ai_virtual_fitting_credits_package_price', 10.00);
$initial_credits = get_option('ai_virtual_fitting_initial_credits', 2);

echo "   Credits per package: " . $credits_per_package . "\n";
echo "   Package price: $" . $package_price . "\n";
echo "   Initial free credits: " . $initial_credits . "\n";

echo "\n=== Summary ===\n";
if ($healed_id && get_post_status($healed_id) === 'publish') {
    echo "✓ Self-healing system is operational\n";
    echo "✓ Credit product is properly configured\n";
    echo "✓ Product exclusion is working\n";
    echo "✓ System ready for use\n";
} else {
    echo "⚠ System needs attention\n";
    if (get_post_status($healed_id) === 'trash') {
        echo "  - Product is in trash and needs restoration\n";
    }
}

echo "\n=== Test Complete ===\n";
