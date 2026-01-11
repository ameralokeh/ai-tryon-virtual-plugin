<?php
/**
 * Create Virtual Fitting Page in WordPress
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

echo "Creating Virtual Fitting Page...\n";

// Check if page already exists
$existing_page = get_page_by_title('Virtual Fitting');

if ($existing_page) {
    echo "Virtual Fitting page already exists with ID: {$existing_page->ID}\n";
    echo "URL: " . get_permalink($existing_page->ID) . "\n";
} else {
    // Create the page
    $page_data = array(
        'post_title'    => 'Virtual Fitting',
        'post_content'  => '[ai_virtual_fitting]',
        'post_status'   => 'publish',
        'post_type'     => 'page',
        'post_author'   => 1,
        'post_slug'     => 'virtual-fitting'
    );
    
    $page_id = wp_insert_post($page_data);
    
    if ($page_id && !is_wp_error($page_id)) {
        echo "✓ Virtual Fitting page created successfully!\n";
        echo "Page ID: {$page_id}\n";
        echo "URL: " . get_permalink($page_id) . "\n";
        
        // Set as front page (optional)
        // update_option('page_on_front', $page_id);
        // update_option('show_on_front', 'page');
        
    } else {
        echo "✗ Failed to create Virtual Fitting page\n";
        if (is_wp_error($page_id)) {
            echo "Error: " . $page_id->get_error_message() . "\n";
        }
    }
}

echo "\n=== Access Information ===\n";
echo "WordPress Site: http://localhost:8080\n";
echo "Virtual Fitting Page: http://localhost:8080/virtual-fitting/\n";
echo "WordPress Admin: http://localhost:8080/wp-admin\n";
echo "Admin Username: admin\n";
echo "Admin Password: (set during WordPress installation)\n";

echo "\n=== Testing Instructions ===\n";
echo "1. Visit: http://localhost:8080/virtual-fitting/\n";
echo "2. If not logged in, you'll see the authentication gate\n";
echo "3. Log in to access the full virtual fitting interface\n";
echo "4. You should see:\n";
echo "   - Credits display\n";
echo "   - Product slider with wedding dresses\n";
echo "   - Image upload area\n";
echo "   - Try on functionality (requires credits)\n";

echo "\nPage creation completed!\n";