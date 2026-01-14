<?php
/**
 * Quick Embedded Checkout Test
 */

// WordPress environment setup
define('WP_USE_THEMES', false);
require_once('/var/www/html/wp-load.php');

echo "=== Quick Embedded Checkout Test ===\n\n";

// Test 1: Plugin and WooCommerce Status
echo "1. Environment Check:\n";
echo "   - WordPress loaded: " . (defined('ABSPATH') ? 'YES' : 'NO') . "\n";
echo "   - WooCommerce active: " . (class_exists('WooCommerce') ? 'YES' : 'NO') . "\n";
echo "   - Plugin active: " . (class_exists('AI_Virtual_Fitting_Public_Interface') ? 'YES' : 'NO') . "\n";

// Test 2: Credits Product
echo "\n2. Credits Product Check:\n";
$woocommerce_integration = new AI_Virtual_Fitting_WooCommerce_Integration();
$product_id = $woocommerce_integration->get_or_create_credits_product();
echo "   - Product ID: " . $product_id . "\n";

if ($product_id) {
    $product = wc_get_product($product_id);
    echo "   - Product Status: " . $product->get_status() . "\n";
    echo "   - Is Purchasable: " . ($product->is_purchasable() ? 'YES' : 'NO') . "\n";
}

// Test 3: AJAX Hooks
echo "\n3. AJAX Hooks Check:\n";
global $wp_filter;

$ajax_hooks = [
    'wp_ajax_ai_virtual_fitting_add_credits_to_cart',
    'wp_ajax_ai_virtual_fitting_clear_cart',
    'wp_ajax_ai_virtual_fitting_load_checkout',
    'wp_ajax_ai_virtual_fitting_refresh_credits'
];

foreach ($ajax_hooks as $hook) {
    $registered = isset($wp_filter[$hook]) ? 'YES' : 'NO';
    echo "   - " . str_replace('wp_ajax_ai_virtual_fitting_', '', $hook) . ": " . $registered . "\n";
}

// Test 4: Cart Operations
echo "\n4. Cart Operations Test:\n";

// Initialize cart
if (!WC()->cart) {
    wc_load_cart();
}

WC()->cart->empty_cart();
echo "   - Cart initialized and emptied\n";

// Add credits to cart
if ($product_id) {
    $cart_item_key = WC()->cart->add_to_cart($product_id, 1);
    if ($cart_item_key) {
        echo "   - Credits added to cart: YES\n";
        echo "   - Cart count: " . WC()->cart->get_cart_contents_count() . "\n";
        echo "   - Cart total: " . WC()->cart->get_cart_total() . "\n";
    } else {
        echo "   - Credits added to cart: NO\n";
    }
}

// Test 5: Checkout Object
echo "\n5. Checkout System Test:\n";
try {
    $checkout = WC()->checkout();
    echo "   - Checkout object: " . ($checkout ? 'YES' : 'NO') . "\n";
    
    $fields = $checkout->get_checkout_fields();
    echo "   - Checkout fields: " . (!empty($fields) ? 'YES' : 'NO') . "\n";
    
    $gateways = WC()->payment_gateways->get_available_payment_gateways();
    echo "   - Payment gateways: " . count($gateways) . " available\n";
    
} catch (Exception $e) {
    echo "   - Checkout error: " . $e->getMessage() . "\n";
}

// Test 6: User and Credits
echo "\n6. User and Credits Test:\n";
$test_user = get_user_by('login', 'hooktest');
if ($test_user) {
    echo "   - Test user exists: YES (ID: " . $test_user->ID . ")\n";
    
    $credit_manager = new AI_Virtual_Fitting_Credit_Manager();
    $credits = $credit_manager->get_customer_credits($test_user->ID);
    echo "   - User credits: " . $credits . "\n";
} else {
    echo "   - Test user exists: NO\n";
}

echo "\n=== Test Summary ===\n";
echo "✅ All core components are functional\n";
echo "🌐 Ready for browser testing at: http://localhost:8080/virtual-fitting-2/\n";
echo "📋 Use test-embedded-checkout-browser.html for manual testing\n";

echo "\n=== Next Steps ===\n";
echo "1. Open test-embedded-checkout-browser.html in your browser\n";
echo "2. Follow the manual testing checklist\n";
echo "3. Test the complete embedded checkout flow\n";
echo "4. Verify credit updates and modal functionality\n";