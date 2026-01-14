<?php
/**
 * Debug AJAX Checkout Processing
 * This script simulates the exact AJAX call that the React modal makes
 */

// WordPress environment setup
$wp_path = '/var/www/html';
require_once($wp_path . '/wp-config.php');
require_once($wp_path . '/wp-load.php');

echo "=== AJAX CHECKOUT DEBUG ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Set up user session
wp_set_current_user(1); // Admin user
echo "Current user ID: " . get_current_user_id() . "\n";

// Initialize WooCommerce
WC()->init();
WC()->frontend_includes();

// Clear cart and add credits product
WC()->cart->empty_cart();

// Find the credits product
$credits_products = wc_get_products(array(
    'name' => 'Virtual Fitting Credits',
    'limit' => 1
));

if (empty($credits_products)) {
    die("❌ Credits product not found\n");
}

$credits_product = $credits_products[0];
echo "Credits product: {$credits_product->get_name()} (ID: {$credits_product->get_id()})\n";

// Add to cart
$cart_item_key = WC()->cart->add_to_cart($credits_product->get_id(), 1);
if (!$cart_item_key) {
    die("❌ Failed to add credits to cart\n");
}

echo "✅ Credits added to cart\n";
echo "Cart total: " . WC()->cart->get_total() . "\n";

// Simulate the exact AJAX request from React modal
echo "\n=== SIMULATING AJAX CHECKOUT REQUEST ===\n";

// Create a valid nonce
$nonce = wp_create_nonce('ai_virtual_fitting_nonce');
echo "Generated nonce: $nonce\n";

// Simulate POST data exactly as React modal sends it
$_POST = array(
    'action' => 'ai_virtual_fitting_process_checkout',
    'nonce' => $nonce,
    'billing_first_name' => 'Test',
    'billing_last_name' => 'User',
    'billing_email' => 'test@example.com',
    'billing_phone' => '1234567890',
    'billing_address_1' => '123 Test St',
    'billing_city' => 'Test City',
    'billing_postcode' => '12345',
    'billing_country' => 'US',
    'billing_state' => 'CA',
    'payment_method' => 'test_credit_card',
    'test_credit_card-card-number' => '4242424242424242',
    'test_credit_card-card-expiry' => '12/28',
    'test_credit_card-card-cvc' => '123'
);

echo "POST data prepared:\n";
foreach ($_POST as $key => $value) {
    if (strpos($key, 'card') !== false) {
        echo "  $key: " . (strlen($value) > 10 ? substr($value, 0, 4) . '****' . substr($value, -4) : $value) . "\n";
    } else {
        echo "  $key: $value\n";
    }
}

// Load the public interface class
require_once('/var/www/html/wp-content/plugins/ai-virtual-fitting/public/class-public-interface.php');

// Create instance and test the checkout handler
$public_interface = new AI_Virtual_Fitting_Public_Interface();

echo "\n=== CALLING CHECKOUT HANDLER ===\n";

// Capture output
ob_start();

try {
    // Call the actual AJAX handler
    $public_interface->handle_process_checkout();
} catch (Exception $e) {
    echo "❌ Exception caught: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

$output = ob_get_clean();

echo "Handler output:\n";
echo $output . "\n";

// Check if it's JSON
$json_data = json_decode($output, true);
if ($json_data !== null) {
    echo "\n=== PARSED JSON RESPONSE ===\n";
    echo json_encode($json_data, JSON_PRETTY_PRINT) . "\n";
    
    if (isset($json_data['success'])) {
        if ($json_data['success']) {
            echo "\n✅ AJAX CHECKOUT SUCCESS!\n";
            if (isset($json_data['data']['order_id'])) {
                echo "Order ID: " . $json_data['data']['order_id'] . "\n";
            }
            if (isset($json_data['data']['credits'])) {
                echo "Updated credits: " . $json_data['data']['credits'] . "\n";
            }
        } else {
            echo "\n❌ AJAX CHECKOUT FAILED\n";
            if (isset($json_data['data']['message'])) {
                echo "Error message: " . $json_data['data']['message'] . "\n";
            }
            if (isset($json_data['data']['error_code'])) {
                echo "Error code: " . $json_data['data']['error_code'] . "\n";
            }
        }
    }
} else {
    echo "\n❌ Response is not valid JSON\n";
    echo "Raw output: " . substr($output, 0, 500) . "\n";
}

// Check final cart state
echo "\n=== FINAL STATE CHECK ===\n";
echo "Cart items: " . WC()->cart->get_cart_contents_count() . "\n";
echo "Cart total: " . WC()->cart->get_total() . "\n";

// Check recent orders
$recent_orders = wc_get_orders(array(
    'limit' => 5,
    'orderby' => 'date',
    'order' => 'DESC'
));

echo "\nRecent orders:\n";
foreach ($recent_orders as $order) {
    echo "  Order #{$order->get_id()} - Status: {$order->get_status()} - Total: {$order->get_total()} - Date: {$order->get_date_created()->format('Y-m-d H:i:s')}\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
?>