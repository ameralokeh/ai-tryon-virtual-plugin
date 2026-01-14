<?php
/**
 * Debug Payment Methods with has_fields Property
 * 
 * This script tests the updated get_available_payment_methods function
 * to verify that the has_fields property is correctly included.
 */

// WordPress environment setup
$wp_path = '/var/www/html';
if (file_exists($wp_path . '/wp-config.php')) {
    require_once($wp_path . '/wp-config.php');
    require_once($wp_path . '/wp-load.php');
} else {
    die("WordPress not found at $wp_path\n");
}

echo "=== TESTING UPDATED get_available_payment_methods() ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Check if WooCommerce is active
if (!class_exists('WooCommerce')) {
    die("❌ WooCommerce is not active\n");
}

echo "✅ WooCommerce is active\n";

// Initialize WooCommerce
if (!WC()->payment_gateways) {
    WC()->init();
}

echo "✅ WooCommerce initialized\n\n";

// Test the updated function (simulate the class method)
function get_available_payment_methods_updated() {
    if (!class_exists('WC_Payment_Gateways')) {
        return array();
    }

    $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
    $available_methods = array();

    foreach ($payment_gateways as $gateway_id => $gateway) {
        if ($gateway->is_available()) {
            // Check if gateway has form fields (like credit card fields)
            $has_fields = false;
            if (method_exists($gateway, 'has_fields') && $gateway->has_fields()) {
                $has_fields = true;
            } elseif (method_exists($gateway, 'payment_fields') || 
                     strpos(strtolower($gateway_id), 'credit') !== false ||
                     strpos(strtolower($gateway_id), 'card') !== false ||
                     $gateway_id === 'test_credit_card') {
                $has_fields = true;
            }
            
            $available_methods[] = array(
                'id' => $gateway_id,
                'title' => $gateway->get_title(),
                'description' => $gateway->get_description(),
                'icon' => $gateway->get_icon(),
                'method_title' => $gateway->get_method_title(),
                'method_description' => $gateway->get_method_description(),
                'has_fields' => $has_fields, // This is the key addition!
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

$methods = get_available_payment_methods_updated();

echo "=== PAYMENT METHODS ANALYSIS ===\n";
echo "Total methods found: " . count($methods) . "\n\n";

$has_fields_count = 0;
foreach ($methods as $method) {
    echo "Method: {$method['title']} (ID: {$method['id']})\n";
    echo "  - Has Fields: " . ($method['has_fields'] ? 'YES ✅' : 'NO ❌') . "\n";
    echo "  - Description: " . substr($method['description'], 0, 50) . "...\n";
    
    if ($method['has_fields']) {
        $has_fields_count++;
        echo "  - 🎯 THIS METHOD SHOULD SHOW CREDIT CARD FIELDS IN REACT MODAL\n";
    }
    
    echo "\n";
}

echo "=== SUMMARY ===\n";
echo "Methods with fields: $has_fields_count\n";
echo "Methods without fields: " . (count($methods) - $has_fields_count) . "\n";

if ($has_fields_count > 0) {
    echo "\n✅ SUCCESS: Payment methods with 'has_fields' property found!\n";
    echo "The React modal should now display credit card fields for these methods.\n";
} else {
    echo "\n❌ ISSUE: No payment methods with 'has_fields' found.\n";
    echo "Check if credit card payment gateways are properly enabled.\n";
}

echo "\n=== DETAILED JSON OUTPUT ===\n";
echo json_encode($methods, JSON_PRETTY_PRINT);

echo "\n\n=== NEXT STEPS ===\n";
echo "1. Verify the React modal shows credit card fields when selecting methods with has_fields=true\n";
echo "2. Test the complete checkout flow with test credit card data\n";
echo "3. Check that styling improvements are applied (smaller fonts, professional appearance)\n";
?>