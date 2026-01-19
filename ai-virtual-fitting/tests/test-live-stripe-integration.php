<?php
/**
 * Live Stripe Integration Test
 * 
 * Tests the actual Stripe integration in the WordPress environment
 * to verify the checkout modal and payment gateway detection work correctly.
 *
 * @package AI_Virtual_Fitting
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

// Ensure we're logged in as admin
wp_set_current_user(1);

echo "=== Live Stripe Integration Test ===\n\n";

// Test 1: Check if WooCommerce is active
echo "Test 1: WooCommerce Active\n";
$wc_active = class_exists('WooCommerce');
echo $wc_active ? "✅ PASS: WooCommerce is active\n" : "❌ FAIL: WooCommerce is not active\n";
echo "\n";

// Test 2: Check if payment gateways are available
echo "Test 2: Payment Gateways Available\n";
$payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
echo "Available payment gateways: " . count($payment_gateways) . "\n";
foreach ($payment_gateways as $gateway_id => $gateway) {
    echo "  - {$gateway_id}: {$gateway->title}\n";
}
echo "\n";

// Test 3: Check for Stripe-related gateways
echo "Test 3: Stripe Gateway Detection\n";
$stripe_found = false;
$stripe_gateway_id = null;
foreach ($payment_gateways as $gateway_id => $gateway) {
    if (stripos($gateway_id, 'stripe') !== false || 
        stripos($gateway->id, 'stripe') !== false ||
        $gateway_id === 'woocommerce_payments') {
        $stripe_found = true;
        $stripe_gateway_id = $gateway_id;
        echo "✅ PASS: Stripe-compatible gateway found: {$gateway_id} ({$gateway->title})\n";
        echo "  - Enabled: " . ($gateway->enabled === 'yes' ? 'Yes' : 'No') . "\n";
        echo "  - Available: " . ($gateway->is_available() ? 'Yes' : 'No') . "\n";
        break;
    }
}
if (!$stripe_found) {
    echo "❌ FAIL: No Stripe-compatible gateway found\n";
}
echo "\n";

// Test 4: Check credit product exists
echo "Test 4: Credit Product Exists\n";
$args = array(
    'post_type' => 'product',
    'posts_per_page' => 1,
    's' => 'credit',
    'post_status' => 'publish'
);
$products = get_posts($args);
if (!empty($products)) {
    $product = wc_get_product($products[0]->ID);
    echo "✅ PASS: Credit product found\n";
    echo "  - ID: {$product->get_id()}\n";
    echo "  - Name: {$product->get_name()}\n";
    echo "  - Price: \${$product->get_price()}\n";
} else {
    echo "❌ FAIL: Credit product not found\n";
}
echo "\n";

// Test 5: Test the public interface method for getting payment methods
echo "Test 5: Public Interface Payment Method Detection\n";
if (class_exists('AI_Virtual_Fitting_Public_Interface')) {
    $public_interface = new AI_Virtual_Fitting_Public_Interface('ai-virtual-fitting', '1.0.0');
    
    // Use reflection to access the private method
    $reflection = new ReflectionClass($public_interface);
    $method = $reflection->getMethod('get_available_payment_methods');
    $method->setAccessible(true);
    
    $payment_methods = $method->invoke($public_interface);
    
    echo "Payment method detection result:\n";
    echo "  - Stripe Available: " . ($payment_methods['stripe_available'] ? 'Yes' : 'No') . "\n";
    
    if ($payment_methods['stripe_available']) {
        echo "✅ PASS: Stripe detected by public interface\n";
        echo "  - Gateway ID: {$payment_methods['payment_method']['id']}\n";
        echo "  - Gateway Title: {$payment_methods['payment_method']['title']}\n";
    } else {
        echo "❌ FAIL: Stripe not detected by public interface\n";
        if (isset($payment_methods['error'])) {
            echo "  - Error: {$payment_methods['error']}\n";
        }
        if (isset($payment_methods['setup_instructions'])) {
            echo "  - Setup Instructions:\n";
            foreach ($payment_methods['setup_instructions'] as $instruction) {
                echo "    * {$instruction}\n";
            }
        }
    }
} else {
    echo "❌ FAIL: Public interface class not found\n";
}
echo "\n";

// Test 6: Simulate AJAX cart addition
echo "Test 6: Simulate Add to Cart AJAX\n";
if (!empty($products)) {
    $product_id = $products[0]->ID;
    
    // Clear cart first
    WC()->cart->empty_cart();
    
    // Add product to cart
    $cart_item_key = WC()->cart->add_to_cart($product_id, 1);
    
    if ($cart_item_key) {
        echo "✅ PASS: Product added to cart\n";
        echo "  - Cart items: " . WC()->cart->get_cart_contents_count() . "\n";
        echo "  - Cart total: " . WC()->cart->get_cart_total() . "\n";
        
        // Clear cart after test
        WC()->cart->empty_cart();
    } else {
        echo "❌ FAIL: Failed to add product to cart\n";
    }
} else {
    echo "⚠️  SKIP: No credit product to test\n";
}
echo "\n";

// Test 7: Check if checkout page exists
echo "Test 7: Checkout Page Exists\n";
$checkout_page_id = wc_get_page_id('checkout');
if ($checkout_page_id > 0) {
    $checkout_page = get_post($checkout_page_id);
    echo "✅ PASS: Checkout page exists\n";
    echo "  - Page ID: {$checkout_page_id}\n";
    echo "  - Page Title: {$checkout_page->post_title}\n";
    echo "  - Page URL: " . get_permalink($checkout_page_id) . "\n";
} else {
    echo "❌ FAIL: Checkout page not found\n";
}
echo "\n";

// Summary
echo "=== Test Summary ===\n";
$total_tests = 7;
$passed_tests = 0;

if ($wc_active) $passed_tests++;
if (count($payment_gateways) > 0) $passed_tests++;
if ($stripe_found) $passed_tests++;
if (!empty($products)) $passed_tests++;
if (isset($payment_methods) && $payment_methods['stripe_available']) $passed_tests++;
if (isset($cart_item_key) && $cart_item_key) $passed_tests++;
if ($checkout_page_id > 0) $passed_tests++;

echo "Tests passed: {$passed_tests}/{$total_tests}\n";

if ($passed_tests === $total_tests) {
    echo "\n✅ All tests PASSED! Stripe integration is working correctly.\n";
    exit(0);
} else {
    echo "\n⚠️  Some tests failed. Review the results above.\n";
    exit(1);
}
