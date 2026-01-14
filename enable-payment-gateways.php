<?php
/**
 * Enable WooCommerce Payment Gateways for Testing
 * Run this script to enable basic payment methods in the local WordPress environment
 */

// WordPress Bootstrap
require_once('/var/www/html/wp-config.php');

if (!class_exists('WooCommerce')) {
    die('WooCommerce is not active');
}

echo "Enabling WooCommerce Payment Gateways...\n";

// Get available payment gateways
$payment_gateways = WC()->payment_gateways->payment_gateways();

// Enable basic payment methods
$gateways_to_enable = array(
    'bacs' => 'Direct Bank Transfer',
    'cheque' => 'Check Payments', 
    'cod' => 'Cash on Delivery',
    'paypal' => 'PayPal'
);

foreach ($gateways_to_enable as $gateway_id => $gateway_name) {
    if (isset($payment_gateways[$gateway_id])) {
        $gateway = $payment_gateways[$gateway_id];
        
        // Enable the gateway
        $gateway->enabled = 'yes';
        
        // Update gateway settings
        $settings = get_option($gateway->get_option_key(), array());
        $settings['enabled'] = 'yes';
        
        // Set basic configuration for each gateway
        switch ($gateway_id) {
            case 'bacs':
                $settings['title'] = 'Direct Bank Transfer';
                $settings['description'] = 'Make your payment directly into our bank account.';
                break;
                
            case 'cheque':
                $settings['title'] = 'Check Payments';
                $settings['description'] = 'Please send a check to our store address.';
                break;
                
            case 'cod':
                $settings['title'] = 'Cash on Delivery';
                $settings['description'] = 'Pay with cash upon delivery.';
                break;
                
            case 'paypal':
                $settings['title'] = 'PayPal';
                $settings['description'] = 'Pay via PayPal; you can pay with your credit card if you don\'t have a PayPal account.';
                $settings['email'] = 'test@example.com'; // Test PayPal email
                $settings['testmode'] = 'yes'; // Enable sandbox mode
                break;
        }
        
        update_option($gateway->get_option_key(), $settings);
        
        echo "✅ Enabled: {$gateway_name} ({$gateway_id})\n";
    } else {
        echo "❌ Gateway not found: {$gateway_name} ({$gateway_id})\n";
    }
}

// Clear any caches
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
}

echo "\n🎉 Payment gateways configuration complete!\n";
echo "Available payment methods:\n";

// List all available payment methods
$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
foreach ($available_gateways as $gateway_id => $gateway) {
    if ($gateway->is_available()) {
        echo "  - {$gateway->get_title()} ({$gateway_id})\n";
    }
}

echo "\nYou can now test the checkout modal with real WooCommerce payment methods!\n";
?>