<?php
/**
 * Debug Payment Processing
 * This script simulates the checkout process to identify where it's failing
 */

// WordPress environment setup
$wp_path = '/var/www/html';
require_once($wp_path . '/wp-config.php');
require_once($wp_path . '/wp-load.php');

echo "=== PAYMENT PROCESSING DEBUG ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Check if WooCommerce is active
if (!class_exists('WooCommerce')) {
    die("❌ WooCommerce is not active\n");
}

echo "✅ WooCommerce is active\n";

// Initialize WooCommerce
WC()->init();
WC()->frontend_includes();

if (is_admin()) {
    WC()->admin_includes();
}

echo "✅ WooCommerce initialized\n";

// Check if test credit card gateway is available
$payment_gateways = WC()->payment_gateways->get_available_payment_gateways();

echo "\n=== AVAILABLE PAYMENT GATEWAYS ===\n";
foreach ($payment_gateways as $id => $gateway) {
    echo "Gateway: {$gateway->get_title()} (ID: $id)\n";
    echo "  - Enabled: " . ($gateway->is_available() ? 'YES' : 'NO') . "\n";
    echo "  - Has Fields: " . (method_exists($gateway, 'has_fields') && $gateway->has_fields() ? 'YES' : 'NO') . "\n";
    
    if ($id === 'test_credit_card') {
        echo "  - 🎯 TEST CREDIT CARD GATEWAY FOUND!\n";
        
        // Test the gateway directly
        echo "\n=== TESTING CREDIT CARD GATEWAY ===\n";
        
        // Simulate form data
        $_POST['test_credit_card-card-number'] = '4242424242424242';
        $_POST['test_credit_card-card-expiry'] = '12/28';
        $_POST['test_credit_card-card-cvc'] = '123';
        
        echo "Simulated form data:\n";
        echo "  - Card Number: 4242424242424242\n";
        echo "  - Expiry: 12/28\n";
        echo "  - CVC: 123\n";
        
        // Test field validation
        echo "\nTesting field validation...\n";
        $validation_result = $gateway->validate_fields();
        echo "Validation result: " . ($validation_result ? 'PASS ✅' : 'FAIL ❌') . "\n";
        
        if (!$validation_result) {
            $notices = wc_get_notices('error');
            echo "Validation errors:\n";
            foreach ($notices as $notice) {
                echo "  - " . $notice['notice'] . "\n";
            }
            wc_clear_notices();
        }
    }
    echo "\n";
}

// Test creating a simple order
echo "=== TESTING ORDER CREATION ===\n";

// Create a test user if not exists
$test_user_id = 1; // Admin user
wp_set_current_user($test_user_id);

echo "Current user ID: " . get_current_user_id() . "\n";

// Add a simple product to cart for testing
$products = wc_get_products(array('limit' => 1));
if (!empty($products)) {
    $product = $products[0];
    echo "Test product: {$product->get_name()} (ID: {$product->get_id()})\n";
    
    // Clear cart first
    WC()->cart->empty_cart();
    
    // Add product to cart
    $cart_item_key = WC()->cart->add_to_cart($product->get_id(), 1);
    
    if ($cart_item_key) {
        echo "✅ Product added to cart\n";
        echo "Cart total: " . WC()->cart->get_total() . "\n";
        
        // Test order creation
        echo "\nTesting order creation...\n";
        
        // Simulate checkout data
        $checkout_data = array(
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
        
        // Set POST data for checkout
        $_POST = array_merge($_POST, $checkout_data);
        
        try {
            $checkout = WC()->checkout();
            $order_id = $checkout->create_order($checkout_data);
            
            if (is_wp_error($order_id)) {
                echo "❌ Order creation failed: " . $order_id->get_error_message() . "\n";
            } else {
                echo "✅ Order created successfully (ID: $order_id)\n";
                
                $order = wc_get_order($order_id);
                echo "Order status: " . $order->get_status() . "\n";
                echo "Order total: " . $order->get_total() . "\n";
                
                // Test payment processing
                echo "\nTesting payment processing...\n";
                
                if (isset($payment_gateways['test_credit_card'])) {
                    $gateway = $payment_gateways['test_credit_card'];
                    
                    // Set payment method on order
                    $order->set_payment_method($gateway);
                    $order->save();
                    
                    try {
                        $payment_result = $gateway->process_payment($order_id);
                        
                        echo "Payment result:\n";
                        echo json_encode($payment_result, JSON_PRETTY_PRINT) . "\n";
                        
                        if (isset($payment_result['result']) && $payment_result['result'] === 'success') {
                            echo "✅ Payment processed successfully!\n";
                            
                            // Check order status after payment
                            $order = wc_get_order($order_id); // Refresh order
                            echo "Final order status: " . $order->get_status() . "\n";
                            
                        } else {
                            echo "❌ Payment failed\n";
                            
                            // Check for WooCommerce notices
                            $notices = wc_get_notices('error');
                            if (!empty($notices)) {
                                echo "Error notices:\n";
                                foreach ($notices as $notice) {
                                    echo "  - " . $notice['notice'] . "\n";
                                }
                            }
                        }
                        
                    } catch (Exception $e) {
                        echo "❌ Payment processing exception: " . $e->getMessage() . "\n";
                        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
                    }
                } else {
                    echo "❌ Test credit card gateway not found\n";
                }
            }
            
        } catch (Exception $e) {
            echo "❌ Order creation exception: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        }
        
    } else {
        echo "❌ Failed to add product to cart\n";
    }
} else {
    echo "❌ No products found for testing\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
?>