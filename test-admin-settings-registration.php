<?php
/**
 * Test Admin Settings Registration
 * Forces re-registration of settings to ensure AI prompt field appears
 */

// WordPress environment setup
$wp_path = '/var/www/html';
if (file_exists($wp_path . '/wp-config.php')) {
    require_once $wp_path . '/wp-config.php';
    require_once $wp_path . '/wp-load.php';
} else {
    echo "WordPress not found. Run this inside the WordPress container.\n";
    exit(1);
}

echo "=== Admin Settings Registration Test ===\n\n";

// Force admin context
if (!is_admin()) {
    define('WP_ADMIN', true);
}

// Test 1: Check if admin settings class exists and can be instantiated
echo "1. Testing admin settings class...\n";
if (class_exists('AI_Virtual_Fitting_Admin_Settings')) {
    echo "✅ Admin Settings class exists\n";
    
    try {
        $admin_settings = new AI_Virtual_Fitting_Admin_Settings();
        echo "✅ Admin Settings class instantiated successfully\n";
        
        // Force initialization of settings
        $admin_settings->init_settings();
        echo "✅ Settings initialization called\n";
        
    } catch (Exception $e) {
        echo "❌ Error instantiating Admin Settings: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Admin Settings class not found\n";
    exit(1);
}

// Test 2: Check registered settings after forced initialization
echo "\n2. Checking registered settings...\n";
$registered_settings = get_registered_settings();

$expected_settings = array(
    'ai_virtual_fitting_api_provider',
    'ai_virtual_fitting_google_ai_api_key',
    'ai_virtual_fitting_ai_prompt_template',
    'ai_virtual_fitting_vertex_credentials',
    'ai_virtual_fitting_initial_credits'
);

foreach ($expected_settings as $setting) {
    if (isset($registered_settings[$setting])) {
        echo "✅ $setting is registered\n";
    } else {
        echo "❌ $setting is NOT registered\n";
    }
}

// Test 3: Check if the setting can be saved and retrieved
echo "\n3. Testing AI prompt template option...\n";
$test_prompt = "Test prompt for configuration verification.";

// Try to save the option
$save_result = update_option('ai_virtual_fitting_ai_prompt_template', $test_prompt);
if ($save_result) {
    echo "✅ AI prompt template option can be saved\n";
} else {
    echo "❌ Failed to save AI prompt template option\n";
}

// Try to retrieve the option
$retrieved_prompt = get_option('ai_virtual_fitting_ai_prompt_template');
if ($retrieved_prompt === $test_prompt) {
    echo "✅ AI prompt template option can be retrieved correctly\n";
} else {
    echo "❌ AI prompt template option retrieval failed\n";
    echo "   Expected: $test_prompt\n";
    echo "   Got: $retrieved_prompt\n";
}

// Test 4: Check if admin page hooks are registered
echo "\n4. Checking admin hooks...\n";
global $wp_filter;

$admin_hooks = array(
    'admin_menu',
    'admin_init'
);

foreach ($admin_hooks as $hook) {
    if (isset($wp_filter[$hook])) {
        $found_ai_fitting = false;
        foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                if (is_array($callback['function']) && 
                    is_object($callback['function'][0]) && 
                    get_class($callback['function'][0]) === 'AI_Virtual_Fitting_Admin_Settings') {
                    $found_ai_fitting = true;
                    break 2;
                }
            }
        }
        
        if ($found_ai_fitting) {
            echo "✅ AI Virtual Fitting hooks found on $hook\n";
        } else {
            echo "❌ AI Virtual Fitting hooks NOT found on $hook\n";
        }
    } else {
        echo "❌ Hook $hook not registered\n";
    }
}

// Test 5: Force settings page rendering test
echo "\n5. Testing settings page rendering...\n";
if (method_exists($admin_settings, 'render_ai_prompt_field')) {
    echo "✅ render_ai_prompt_field method exists\n";
    
    // Capture output
    ob_start();
    try {
        $admin_settings->render_ai_prompt_field();
        $output = ob_get_contents();
        ob_end_clean();
        
        if (strpos($output, 'ai_prompt_template') !== false) {
            echo "✅ AI prompt field renders correctly\n";
            echo "   Field HTML contains expected elements\n";
        } else {
            echo "❌ AI prompt field rendering failed\n";
            echo "   Output: " . substr($output, 0, 200) . "...\n";
        }
    } catch (Exception $e) {
        ob_end_clean();
        echo "❌ Error rendering AI prompt field: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ render_ai_prompt_field method not found\n";
}

// Cleanup
delete_option('ai_virtual_fitting_ai_prompt_template');

echo "\n=== Registration Test Summary ===\n";
echo "If the AI prompt template setting is not showing in the admin:\n";
echo "1. The WordPress admin cache might need clearing\n";
echo "2. Try deactivating and reactivating the plugin\n";
echo "3. Check WordPress admin at: http://localhost:8080/wp-admin/admin.php?page=ai-virtual-fitting-settings\n";

?>