<?php
/**
 * Live Modal Functionality Test
 * Tests the embedded checkout modal in the WordPress environment
 */

// WordPress environment setup
$wp_path = '/var/www/html';
require_once($wp_path . '/wp-config.php');
require_once($wp_path . '/wp-load.php');

// Ensure we're in WordPress context
if (!function_exists('wp_head')) {
    die('WordPress not loaded properly');
}

// Get the virtual fitting page URL
$virtual_fitting_page = get_page_by_path('virtual-fitting-2');
if (!$virtual_fitting_page) {
    // Try alternative page names
    $virtual_fitting_page = get_page_by_path('virtual-fitting');
    if (!$virtual_fitting_page) {
        // Create a test page with the shortcode
        $page_data = array(
            'post_title' => 'Virtual Fitting Test',
            'post_content' => '[ai_virtual_fitting]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_name' => 'virtual-fitting-test'
        );
        $page_id = wp_insert_post($page_data);
        $virtual_fitting_page = get_post($page_id);
    }
}

$page_url = get_permalink($virtual_fitting_page->ID);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Modal Functionality Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .test-container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .test-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            background: #f8f9fa;
        }
        
        .test-section h3 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .test-step {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 6px;
            border-left: 4px solid #3498db;
        }
        
        .test-step.success {
            border-left-color: #27ae60;
            background: #d5f4e6;
        }
        
        .test-step.error {
            border-left-color: #e74c3c;
            background: #fdf2f2;
        }
        
        .test-step.warning {
            border-left-color: #f39c12;
            background: #fef9e7;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            margin: 5px;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn.success {
            background: #27ae60;
        }
        
        .btn.success:hover {
            background: #219a52;
        }
        
        .code-block {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
            margin: 10px 0;
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-indicator.success {
            background: #27ae60;
        }
        
        .status-indicator.error {
            background: #e74c3c;
        }
        
        .status-indicator.warning {
            background: #f39c12;
        }
        
        .iframe-container {
            border: 2px solid #bdc3c7;
            border-radius: 8px;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .iframe-container iframe {
            width: 100%;
            height: 800px;
            border: none;
        }
        
        .test-results {
            background: #ecf0f1;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .checklist {
            list-style: none;
            padding: 0;
        }
        
        .checklist li {
            padding: 8px 0;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .checklist li:last-child {
            border-bottom: none;
        }
        
        .checklist li::before {
            content: "☐ ";
            color: #7f8c8d;
            font-weight: bold;
            margin-right: 8px;
        }
        
        .checklist li.completed::before {
            content: "☑ ";
            color: #27ae60;
        }
        
        .debug-info {
            background: #34495e;
            color: white;
            padding: 15px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 12px;
            margin: 10px 0;
            max-height: 200px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="test-header">
        <h1>🧪 Live Modal Functionality Test</h1>
        <p>Testing the embedded checkout modal in WordPress environment</p>
        <p><strong>Test Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>

    <div class="test-container">
        <div class="test-section">
            <h3>📋 Test Overview</h3>
            <p>This test verifies that the "Get More Credits" button opens the embedded checkout modal instead of redirecting to the shop page.</p>
            
            <div class="test-step">
                <strong>Expected Behavior:</strong>
                <ul>
                    <li>Click "Get More Credits" button</li>
                    <li>Modal should open with checkout form</li>
                    <li>No page redirect should occur</li>
                    <li>Modal should be functional and responsive</li>
                </ul>
            </div>
        </div>

        <div class="test-section">
            <h3>🔧 Environment Check</h3>
            
            <?php
            // Check WordPress environment
            $wp_version = get_bloginfo('version');
            $wc_active = class_exists('WooCommerce');
            $plugin_active = is_plugin_active('ai-virtual-fitting/ai-virtual-fitting.php');
            
            echo "<div class='test-step " . ($wp_version ? 'success' : 'error') . "'>";
            echo "<span class='status-indicator " . ($wp_version ? 'success' : 'error') . "'></span>";
            echo "<strong>WordPress:</strong> " . ($wp_version ? "Version $wp_version" : "Not detected");
            echo "</div>";
            
            echo "<div class='test-step " . ($wc_active ? 'success' : 'error') . "'>";
            echo "<span class='status-indicator " . ($wc_active ? 'success' : 'error') . "'></span>";
            echo "<strong>WooCommerce:</strong> " . ($wc_active ? "Active" : "Not active");
            echo "</div>";
            
            echo "<div class='test-step " . ($plugin_active ? 'success' : 'warning') . "'>";
            echo "<span class='status-indicator " . ($plugin_active ? 'success' : 'warning') . "'></span>";
            echo "<strong>AI Virtual Fitting Plugin:</strong> " . ($plugin_active ? "Active" : "Not active (may still work)");
            echo "</div>";
            ?>
            
            <div class="test-step">
                <strong>Virtual Fitting Page:</strong> 
                <a href="<?php echo esc_url($page_url); ?>" target="_blank" class="btn">
                    Open Virtual Fitting Page
                </a>
            </div>
        </div>

        <div class="test-section">
            <h3>🎯 Manual Test Instructions</h3>
            
            <div class="test-step">
                <strong>Step 1:</strong> Open the virtual fitting page in a new tab
                <br><a href="<?php echo esc_url($page_url); ?>" target="_blank" class="btn">Open Page</a>
            </div>
            
            <div class="test-step">
                <strong>Step 2:</strong> Look for the "Get More Credits" button in the credits banner
            </div>
            
            <div class="test-step">
                <strong>Step 3:</strong> Click the "Get More Credits" button
            </div>
            
            <div class="test-step">
                <strong>Step 4:</strong> Verify that:
                <ul class="checklist">
                    <li>A modal opens (doesn't redirect to shop page)</li>
                    <li>Modal contains "Purchase Credits" header</li>
                    <li>Modal shows loading spinner initially</li>
                    <li>Checkout form loads inside the modal</li>
                    <li>Modal can be closed with X button</li>
                </ul>
            </div>
        </div>

        <div class="test-section">
            <h3>🔍 Debug Information</h3>
            
            <?php
            // Check if scripts are enqueued
            global $wp_scripts;
            $scripts_enqueued = false;
            $css_enqueued = false;
            
            if (isset($wp_scripts->registered['ai-virtual-fitting-modern-script'])) {
                $scripts_enqueued = true;
            }
            
            global $wp_styles;
            if (isset($wp_styles->registered['ai-virtual-fitting-modern-style'])) {
                $css_enqueued = true;
            }
            ?>
            
            <div class="debug-info">
                <strong>Plugin File Check:</strong><br>
                <?php
                $plugin_file = WP_PLUGIN_DIR . '/ai-virtual-fitting/ai-virtual-fitting.php';
                echo "Plugin file exists: " . (file_exists($plugin_file) ? 'YES' : 'NO') . "\n";
                
                $public_interface_file = WP_PLUGIN_DIR . '/ai-virtual-fitting/public/class-public-interface.php';
                echo "Public interface file exists: " . (file_exists($public_interface_file) ? 'YES' : 'NO') . "\n";
                
                $js_file = WP_PLUGIN_DIR . '/ai-virtual-fitting/public/js/modern-virtual-fitting.js';
                echo "JavaScript file exists: " . (file_exists($js_file) ? 'YES' : 'NO') . "\n";
                
                $css_file = WP_PLUGIN_DIR . '/ai-virtual-fitting/public/css/modern-virtual-fitting.css';
                echo "CSS file exists: " . (file_exists($css_file) ? 'YES' : 'NO') . "\n";
                ?>
            </div>
            
            <div class="debug-info">
                <strong>Page Information:</strong><br>
                <?php
                echo "Current page ID: " . get_the_ID() . "\n";
                echo "Page URL: " . $page_url . "\n";
                echo "Page slug: " . $virtual_fitting_page->post_name . "\n";
                echo "Has shortcode: " . (has_shortcode($virtual_fitting_page->post_content, 'ai_virtual_fitting') ? 'YES' : 'NO') . "\n";
                ?>
            </div>
        </div>

        <div class="test-section">
            <h3>🚀 Quick Test Actions</h3>
            
            <div class="test-step">
                <strong>Browser Console Test:</strong>
                <p>Open browser console on the virtual fitting page and run:</p>
                <div class="code-block">
// Check if modal exists
console.log('Modal exists:', $('#checkout-modal').length > 0);

// Check if function exists
console.log('openCheckoutModal function exists:', typeof openCheckoutModal === 'function');

// Try to open modal manually
if (typeof openCheckoutModal === 'function') {
    openCheckoutModal();
} else {
    console.error('openCheckoutModal function not found');
}
                </div>
            </div>
            
            <div class="test-step">
                <strong>Network Tab Check:</strong>
                <p>In browser dev tools, check Network tab for:</p>
                <ul>
                    <li>modern-virtual-fitting.js (should load successfully)</li>
                    <li>modern-virtual-fitting.css (should load successfully)</li>
                    <li>AJAX calls to admin-ajax.php when clicking button</li>
                </ul>
            </div>
        </div>

        <div class="test-section">
            <h3>📊 Test Results</h3>
            <div class="test-results">
                <p><strong>Complete the manual tests above and record your results:</strong></p>
                
                <ul class="checklist">
                    <li>Virtual fitting page loads successfully</li>
                    <li>"Get More Credits" button is visible</li>
                    <li>Button click opens modal (no redirect)</li>
                    <li>Modal contains proper content</li>
                    <li>Modal is responsive and functional</li>
                    <li>JavaScript console shows no errors</li>
                    <li>Network requests are successful</li>
                </ul>
                
                <div style="margin-top: 20px;">
                    <strong>Overall Test Status:</strong>
                    <div style="margin-top: 10px;">
                        <button class="btn success" onclick="alert('Mark as PASSED if all checklist items are completed successfully')">
                            ✅ PASSED
                        </button>
                        <button class="btn" style="background: #e74c3c;" onclick="alert('Mark as FAILED if any critical issues found')">
                            ❌ FAILED
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh debug info every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
        
        // Add click handlers for checklist items
        document.querySelectorAll('.checklist li').forEach(function(item) {
            item.addEventListener('click', function() {
                this.classList.toggle('completed');
            });
        });
    </script>
</body>
</html>