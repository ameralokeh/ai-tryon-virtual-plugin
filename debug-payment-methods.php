<?php
/**
 * Debug Payment Methods
 * Check what payment methods are available and their configuration
 */

// WordPress Bootstrap
require_once('/var/www/html/wp-config.php');

if (!class_exists('WooCommerce')) {
    die('WooCommerce is not active');
}

echo "=== PAYMENT METHODS DEBUG ===\n\n";

// Get available payment gateways
$payment_gateways = WC()->payment_gateways->get_available_payment_gateways();

echo "Available Payment Gateways:\n";
foreach ($payment_gateways as $gateway_id => $gateway) {
    if ($gateway->is_available()) {
        echo "✅ {$gateway_id}: {$gateway->get_title()}\n";
        echo "   Description: {$gateway->get_description()}\n";
        echo "   Has Fields: " . ($gateway->has_fields ? 'Yes' : 'No') . "\n";
        echo "   Enabled: " . ($gateway->enabled === 'yes' ? 'Yes' : 'No') . "\n";
        echo "   Supports: " . implode(', ', array_keys($gateway->supports)) . "\n";
        echo "\n";
    } else {
        echo "❌ {$gateway_id}: Not available\n";
    }
}

// Test the get_available_payment_methods function
echo "=== TESTING get_available_payment_methods() ===\n";

// Simulate the function from our class
function get_available_payment_methods() {
    if (!class_exists('WC_Payment_Gateways')) {
        return array();
    }

    $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
    $available_methods = array();

    foreach ($payment_gateways as $gateway_id => $gateway) {
        if ($gateway->is_available()) {
            $available_methods[] = array(
                'id' => $gateway_id,
                'title' => $gateway->get_title(),
                'description' => $gateway->get_description(),
                'icon' => $gateway->get_icon(),
                'method_title' => $gateway->get_method_title(),
                'method_description' => $gateway->get_method_description(),
                'has_fields' => $gateway->has_fields,
                'supports' => array(
                    'products' => $gateway->supports('products'),
                    'refunds' => $gateway->supports('refunds'),
                    'subscriptions' => $gateway->supports('subscriptions'),
                    'tokenization' => $gateway->supports('tokenization')
                )
            );
        }
    }

    return $available_methods;
}

$methods = get_available_payment_methods();
echo "Methods returned by function:\n";
echo json_encode($methods, JSON_PRETTY_PRINT);

echo "\n=== CART STATUS ===\n";
echo "Cart empty: " . (WC()->cart->is_empty() ? 'Yes' : 'No') . "\n";
echo "Cart contents count: " . WC()->cart->get_cart_contents_count() . "\n";
echo "Cart total: " . WC()->cart->get_total() . "\n";

echo "\n=== TEST CREDIT CARD GATEWAY STATUS ===\n";
$test_gateway_settings = get_option('woocommerce_test_credit_card_settings', array());
echo "Test gateway settings:\n";
print_r($test_gateway_settings);

// Check if the plugin is active
$active_plugins = get_option('active_plugins', array());
echo "\nActive plugins:\n";
foreach ($active_plugins as $plugin) {
    if (strpos($plugin, 'test-credit-card') !== false) {
        echo "✅ {$plugin}\n";
    }
}

echo "\n=== DONE ===\n";
?>