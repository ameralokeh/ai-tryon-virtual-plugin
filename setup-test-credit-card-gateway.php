<?php
/**
 * Setup Test Credit Card Gateway for WooCommerce
 * This creates a simple test gateway that simulates credit card payments
 */

// WordPress Bootstrap
require_once('/var/www/html/wp-config.php');

if (!class_exists('WooCommerce')) {
    die('WooCommerce is not active');
}

echo "Setting up Test Credit Card Gateway...\n";

// Create the test gateway class
$gateway_code = '<?php
/**
 * Test Credit Card Gateway for WooCommerce
 * Simulates credit card payments for testing purposes
 */

if (!defined("ABSPATH")) {
    exit;
}

add_action("plugins_loaded", "init_test_credit_card_gateway");

function init_test_credit_card_gateway() {
    if (!class_exists("WC_Payment_Gateway")) {
        return;
    }

    class WC_Test_Credit_Card_Gateway extends WC_Payment_Gateway {
        
        public function __construct() {
            $this->id = "test_credit_card";
            $this->icon = "";
            $this->has_fields = true;
            $this->method_title = "Test Credit Card";
            $this->method_description = "Test credit card gateway for development purposes";
            
            $this->supports = array(
                "products",
                "refunds"
            );
            
            $this->init_form_fields();
            $this->init_settings();
            
            $this->title = $this->get_option("title");
            $this->description = $this->get_option("description");
            $this->enabled = $this->get_option("enabled");
            $this->testmode = "yes" === $this->get_option("testmode");
            
            add_action("woocommerce_update_options_payment_gateways_" . $this->id, array($this, "process_admin_options"));
        }
        
        public function init_form_fields() {
            $this->form_fields = array(
                "enabled" => array(
                    "title" => "Enable/Disable",
                    "type" => "checkbox",
                    "label" => "Enable Test Credit Card Gateway",
                    "default" => "yes"
                ),
                "title" => array(
                    "title" => "Title",
                    "type" => "text",
                    "description" => "This controls the title displayed during checkout.",
                    "default" => "Credit Card (Test)",
                    "desc_tip" => true,
                ),
                "description" => array(
                    "title" => "Description",
                    "type" => "textarea",
                    "description" => "Payment method description that the customer will see on your checkout.",
                    "default" => "Pay securely with your credit card. This is a test gateway for development.",
                ),
                "testmode" => array(
                    "title" => "Test mode",
                    "label" => "Enable Test Mode",
                    "type" => "checkbox",
                    "description" => "Place the payment gateway in test mode using test API keys.",
                    "default" => "yes",
                    "desc_tip" => true,
                )
            );
        }
        
        public function payment_fields() {
            if ($this->description) {
                echo wpautop(wp_kses_post($this->description));
            }
            
            echo "<fieldset id=\"wc-{$this->id}-cc-form\" class=\"wc-credit-card-form wc-payment-form\" style=\"background:transparent;\">";
            echo "<div class=\"form-row form-row-wide\">";
            echo "<label>Card Number <span class=\"required\">*</span></label>";
            echo "<input id=\"{$this->id}-card-number\" class=\"input-text wc-credit-card-form-card-number\" type=\"text\" maxlength=\"20\" autocomplete=\"cc-number\" placeholder=\"1234 1234 1234 1234\" name=\"{$this->id}-card-number\" />";
            echo "</div>";
            
            echo "<div class=\"form-row form-row-first\">";
            echo "<label>Expiry (MM/YY) <span class=\"required\">*</span></label>";
            echo "<input id=\"{$this->id}-card-expiry\" class=\"input-text wc-credit-card-form-card-expiry\" type=\"text\" autocomplete=\"cc-exp\" placeholder=\"MM / YY\" name=\"{$this->id}-card-expiry\" />";
            echo "</div>";
            
            echo "<div class=\"form-row form-row-last\">";
            echo "<label>Card Code (CVC) <span class=\"required\">*</span></label>";
            echo "<input id=\"{$this->id}-card-cvc\" class=\"input-text wc-credit-card-form-card-cvc\" type=\"text\" autocomplete=\"cc-csc\" placeholder=\"CVC\" name=\"{$this->id}-card-cvc\" />";
            echo "</div>";
            
            echo "<div class=\"clear\"></div>";
            echo "</fieldset>";
            
            if ($this->testmode) {
                echo "<p style=\"color: #28a745; font-size: 12px; margin-top: 10px;\">";
                echo "<strong>Test Mode:</strong> Use card number 4242424242424242 with any future expiry date and any 3-digit CVC.";
                echo "</p>";
            }
        }
        
        public function validate_fields() {
            if (empty($_POST[$this->id . "-card-number"])) {
                wc_add_notice("Card number is required!", "error");
                return false;
            }
            
            if (empty($_POST[$this->id . "-card-expiry"])) {
                wc_add_notice("Card expiry date is required!", "error");
                return false;
            }
            
            if (empty($_POST[$this->id . "-card-cvc"])) {
                wc_add_notice("Card CVC is required!", "error");
                return false;
            }
            
            $card_number = sanitize_text_field($_POST[$this->id . "-card-number"]);
            $card_number = preg_replace("/\s+/", "", $card_number);
            
            // Simple validation for test mode
            if ($this->testmode) {
                // Accept test card numbers
                $test_cards = array("4242424242424242", "4000000000000002", "5555555555554444");
                if (!in_array($card_number, $test_cards)) {
                    wc_add_notice("Please use a test card number: 4242424242424242", "error");
                    return false;
                }
            }
            
            return true;
        }
        
        public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            
            // Simulate payment processing
            if ($this->testmode) {
                $card_number = sanitize_text_field($_POST[$this->id . "-card-number"]);
                $card_number = preg_replace("/\s+/", "", $card_number);
                
                // Simulate different outcomes based on card number
                if ($card_number === "4000000000000002") {
                    // Simulate declined card
                    wc_add_notice("Your card was declined. Please try a different payment method.", "error");
                    return array(
                        "result" => "fail",
                        "redirect" => ""
                    );
                }
                
                // Simulate successful payment
                $order->payment_complete();
                $order->reduce_order_stock();
                
                // Add order note
                $order->add_order_note("Test credit card payment completed successfully. Transaction ID: TEST_" . time());
                
                // Empty cart
                WC()->cart->empty_cart();
                
                return array(
                    "result" => "success",
                    "redirect" => $this->get_return_url($order)
                );
            }
            
            // In real mode, you would integrate with actual payment processor
            wc_add_notice("Real payment processing not configured.", "error");
            return array(
                "result" => "fail",
                "redirect" => ""
            );
        }
        
        public function process_refund($order_id, $amount = null, $reason = "") {
            $order = wc_get_order($order_id);
            
            if (!$order) {
                return false;
            }
            
            // Simulate refund processing
            if ($this->testmode) {
                $order->add_order_note("Test refund processed: " . wc_price($amount) . " - Reason: " . $reason);
                return true;
            }
            
            return false;
        }
    }
    
    function add_test_credit_card_gateway($gateways) {
        $gateways[] = "WC_Test_Credit_Card_Gateway";
        return $gateways;
    }
    
    add_filter("woocommerce_payment_gateways", "add_test_credit_card_gateway");
}
?>';

// Write the gateway file
$gateway_file = '/var/www/html/wp-content/plugins/woocommerce/includes/gateways/test-credit-card/class-wc-gateway-test-credit-card.php';
$gateway_dir = dirname($gateway_file);

// Create directory if it doesn't exist
if (!file_exists($gateway_dir)) {
    mkdir($gateway_dir, 0755, true);
}

file_put_contents($gateway_file, $gateway_code);

echo "✅ Test Credit Card Gateway file created\n";

// Create a plugin file to load the gateway
$plugin_code = '<?php
/**
 * Plugin Name: Test Credit Card Gateway
 * Description: Test credit card payment gateway for WooCommerce development
 * Version: 1.0.0
 * Author: Development Team
 */

if (!defined("ABSPATH")) {
    exit;
}

// Load the gateway class
require_once plugin_dir_path(__FILE__) . "class-wc-gateway-test-credit-card.php";
?>';

$plugin_file = '/var/www/html/wp-content/plugins/test-credit-card-gateway/test-credit-card-gateway.php';
$plugin_dir = dirname($plugin_file);

if (!file_exists($plugin_dir)) {
    mkdir($plugin_dir, 0755, true);
}

file_put_contents($plugin_file, $plugin_code);
copy($gateway_file, $plugin_dir . '/class-wc-gateway-test-credit-card.php');

echo "✅ Test Credit Card Gateway plugin created\n";

// Activate the plugin
$active_plugins = get_option('active_plugins', array());
$plugin_path = 'test-credit-card-gateway/test-credit-card-gateway.php';

if (!in_array($plugin_path, $active_plugins)) {
    $active_plugins[] = $plugin_path;
    update_option('active_plugins', $active_plugins);
    echo "✅ Test Credit Card Gateway plugin activated\n";
} else {
    echo "ℹ️  Test Credit Card Gateway plugin already active\n";
}

// Enable the gateway
$gateway_settings = get_option('woocommerce_test_credit_card_settings', array());
$gateway_settings['enabled'] = 'yes';
$gateway_settings['title'] = 'Credit Card (Test)';
$gateway_settings['description'] = 'Pay securely with your credit card. This is a test gateway for development.';
$gateway_settings['testmode'] = 'yes';

update_option('woocommerce_test_credit_card_settings', $gateway_settings);

echo "✅ Test Credit Card Gateway enabled and configured\n";

// Clear any caches
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
}

echo "\n🎉 Test Credit Card Gateway setup complete!\n";
echo "\n📋 Test Card Numbers:\n";
echo "  ✅ Success: 4242424242424242 (any future expiry, any CVC)\n";
echo "  ❌ Decline: 4000000000000002 (any future expiry, any CVC)\n";
echo "  ✅ Success: 5555555555554444 (any future expiry, any CVC)\n";

echo "\n🔧 You can now:\n";
echo "  1. Test credit card payments in your React modal\n";
echo "  2. View successful orders in WooCommerce admin\n";
echo "  3. Test payment failures with decline card\n";
echo "  4. Process refunds from WooCommerce admin\n";

echo "\n🌐 Access:\n";
echo "  WordPress: http://localhost:8080\n";
echo "  WC Orders: http://localhost:8080/wp-admin/edit.php?post_type=shop_order\n";
echo "  Payment Settings: http://localhost:8080/wp-admin/admin.php?page=wc-settings&tab=checkout\n";
?>