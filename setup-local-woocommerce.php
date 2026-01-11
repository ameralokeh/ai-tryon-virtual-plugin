<?php
/**
 * Script to activate WooCommerce and create API keys for local development
 * Place this in your WordPress root and run via browser: http://localhost:8080/setup-local-woocommerce.php
 */

// WordPress bootstrap
require_once('wp-config.php');
require_once('wp-load.php');

// Check if user is admin (basic security)
if (!current_user_can('manage_options')) {
    wp_die('You need administrator privileges to run this script.');
}

echo "<h1>Local WooCommerce Setup</h1>";

// Step 1: Activate WooCommerce plugin
echo "<h2>Step 1: Activating WooCommerce</h2>";

if (!is_plugin_active('woocommerce/woocommerce.php')) {
    $result = activate_plugin('woocommerce/woocommerce.php');
    if (is_wp_error($result)) {
        echo "<p style='color: red;'>Error activating WooCommerce: " . $result->get_error_message() . "</p>";
    } else {
        echo "<p style='color: green;'>✅ WooCommerce activated successfully!</p>";
    }
} else {
    echo "<p style='color: green;'>✅ WooCommerce is already active!</p>";
}

// Step 2: Create API Keys
echo "<h2>Step 2: Creating API Keys</h2>";

if (class_exists('WC_API_Keys')) {
    global $wpdb;
    
    // Check if API key already exists
    $existing_key = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}woocommerce_api_keys WHERE description = %s",
        'Local Development MCP'
    ));
    
    if ($existing_key) {
        echo "<p style='color: orange;'>⚠️ API key already exists!</p>";
        echo "<p><strong>Consumer Key:</strong> " . esc_html($existing_key->consumer_key) . "</p>";
        echo "<p><strong>Consumer Secret:</strong> " . esc_html($existing_key->consumer_secret) . "</p>";
    } else {
        // Generate new API key
        $consumer_key = 'ck_' . wc_rand_hash();
        $consumer_secret = 'cs_' . wc_rand_hash();
        
        // Insert into database
        $wpdb->insert(
            $wpdb->prefix . 'woocommerce_api_keys',
            array(
                'user_id'         => get_current_user_id(),
                'description'     => 'Local Development MCP',
                'permissions'     => 'read_write',
                'consumer_key'    => wc_api_hash($consumer_key),
                'consumer_secret' => $consumer_secret,
                'truncated_key'   => substr($consumer_key, -7),
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($wpdb->insert_id) {
            echo "<p style='color: green;'>✅ API keys created successfully!</p>";
            echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<p><strong>Consumer Key:</strong> <code>" . esc_html($consumer_key) . "</code></p>";
            echo "<p><strong>Consumer Secret:</strong> <code>" . esc_html($consumer_secret) . "</code></p>";
            echo "</div>";
            
            // Save to file for easy access
            $credentials = array(
                'url' => home_url(),
                'consumer_key' => $consumer_key,
                'consumer_secret' => $consumer_secret,
                'created_at' => current_time('mysql')
            );
            
            file_put_contents(
                ABSPATH . 'local-woocommerce-credentials.json',
                json_encode($credentials, JSON_PRETTY_PRINT)
            );
            
            echo "<p style='color: green;'>✅ Credentials saved to local-woocommerce-credentials.json</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create API keys</p>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ WooCommerce API Keys class not available. Make sure WooCommerce is properly activated.</p>";
}

// Step 3: Test API Connection
echo "<h2>Step 3: Testing API Connection</h2>";

if (isset($consumer_key) && isset($consumer_secret)) {
    $test_url = home_url('/wp-json/wc/v3/system_status');
    echo "<p>Test URL: <a href='{$test_url}' target='_blank'>{$test_url}</a></p>";
    echo "<p style='color: blue;'>ℹ️ You can test the API connection using these credentials with your MCP server.</p>";
}

echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>Copy the Consumer Key and Consumer Secret above</li>";
echo "<li>Update your MCP configuration with these credentials</li>";
echo "<li>Enable the woocommerce-local server in your MCP config</li>";
echo "<li>Test the connection through Kiro</li>";
echo "</ol>";

echo "<p><a href='/wp-admin'>← Back to WordPress Admin</a></p>";
?>