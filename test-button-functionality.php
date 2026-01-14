<?php
/**
 * Test Button Functionality
 */

// WordPress environment setup
define('WP_USE_THEMES', false);
require_once('/var/www/html/wp-load.php');

echo "=== Testing Button Functionality ===\n\n";

// Check if the virtual fitting page exists
$page = get_page_by_path('virtual-fitting-2');
if ($page) {
    echo "✅ Virtual fitting page exists (ID: " . $page->ID . ")\n";
    echo "   URL: " . get_permalink($page->ID) . "\n";
} else {
    echo "❌ Virtual fitting page not found\n";
}

// Check if user is logged in when accessing the page
$test_user = get_user_by('login', 'hooktest');
if ($test_user) {
    echo "✅ Test user exists (ID: " . $test_user->ID . ")\n";
    
    // Set current user
    wp_set_current_user($test_user->ID);
    
    // Check credits
    $credit_manager = new AI_Virtual_Fitting_Credit_Manager();
    $credits = $credit_manager->get_customer_credits($test_user->ID);
    echo "   User credits: " . $credits . "\n";
} else {
    echo "❌ Test user not found\n";
}

// Check if scripts are enqueued properly
echo "\n=== Script Enqueueing Test ===\n";

// Simulate the page load to check script enqueueing
global $wp_scripts;

// Initialize scripts
wp_enqueue_script('jquery');

// Check if our plugin scripts would be enqueued
$public_interface = new AI_Virtual_Fitting_Public_Interface();

// Check if the shortcode exists
global $shortcode_tags;
if (isset($shortcode_tags['ai_virtual_fitting'])) {
    echo "✅ Shortcode registered\n";
} else {
    echo "❌ Shortcode not registered\n";
}

echo "\n=== JavaScript Debug Test ===\n";
echo "Create a simple HTML test to check button functionality...\n";

// Create a simple test HTML
$test_html = '<!DOCTYPE html>
<html>
<head>
    <title>Button Test</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <button id="add-credits-btn" class="btn btn-primary">Get More Credits</button>
    
    <script>
    $(document).ready(function() {
        console.log("jQuery loaded:", typeof $ !== "undefined");
        console.log("Button exists:", $("#add-credits-btn").length > 0);
        
        $("#add-credits-btn").on("click", function(e) {
            e.preventDefault();
            alert("Button clicked! Modal should open here.");
            console.log("Button clicked successfully");
        });
        
        // Test if button is clickable
        setTimeout(function() {
            console.log("Button is visible:", $("#add-credits-btn").is(":visible"));
            console.log("Button is enabled:", !$("#add-credits-btn").is(":disabled"));
        }, 1000);
    });
    </script>
</body>
</html>';

file_put_contents('/var/www/html/test-button-simple.html', $test_html);
echo "✅ Created test-button-simple.html for manual testing\n";
echo "   Access at: http://localhost:8080/test-button-simple.html\n";

echo "\n=== Recommendations ===\n";
echo "1. Open http://localhost:8080/test-button-simple.html to test basic button functionality\n";
echo "2. Check browser console for JavaScript errors on the virtual fitting page\n";
echo "3. Verify that jQuery and the plugin JavaScript are loading correctly\n";
echo "4. Test with browser developer tools to see if click events are being captured\n";