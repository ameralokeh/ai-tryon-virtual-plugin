<?php
/**
 * Test Virtual Credit System
 * 
 * This script tests the virtual credit system setup
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('You must be an administrator to access this page.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Virtual Credit System Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: #f8f9fa;
        }
        .container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        h1 {
            color: #2c3e50;
            margin-top: 0;
        }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .status-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            border-left: 4px solid #4a90e2;
        }
        .status-card h3 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 16px;
        }
        .status-value {
            font-size: 24px;
            font-weight: 700;
            color: #4a90e2;
            margin: 10px 0;
        }
        .status-label {
            font-size: 13px;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .success {
            color: #27ae60;
        }
        .error {
            color: #e74c3c;
        }
        .warning {
            color: #f39c12;
        }
        .info-box {
            background: #e8f4fd;
            border-left: 4px solid #4a90e2;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box h4 {
            margin-top: 0;
            color: #2c3e50;
        }
        .info-box ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .info-box li {
            margin: 5px 0;
        }
        .button {
            background: #4a90e2;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .button:hover {
            background: #357abd;
        }
        .button-secondary {
            background: #95a5a6;
        }
        .button-secondary:hover {
            background: #7f8c8d;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e1e8ed;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 Virtual Credit System Status</h1>
        
        <?php
        // Get credit settings
        $credits_per_package = get_option('ai_virtual_fitting_credits_per_package', 20);
        $package_price = get_option('ai_virtual_fitting_credits_package_price', 10.00);
        $initial_credits = get_option('ai_virtual_fitting_initial_credits', 2);
        $credit_product_id = get_option('ai_virtual_fitting_credit_product_id');
        
        // Check if product exists
        $product_exists = $credit_product_id && get_post($credit_product_id);
        $product = $product_exists ? wc_get_product($credit_product_id) : null;
        
        // Check if virtual credit system class exists
        $class_exists = class_exists('AI_Virtual_Fitting_Virtual_Credit_System');
        
        // Get transaction count
        global $wpdb;
        $transaction_table = $wpdb->prefix . 'virtual_fitting_credit_transactions';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$transaction_table}'") === $transaction_table;
        $transaction_count = $table_exists ? $wpdb->get_var("SELECT COUNT(*) FROM {$transaction_table}") : 0;
        ?>
        
        <div class="status-grid">
            <div class="status-card">
                <h3>System Status</h3>
                <div class="status-value <?php echo $class_exists ? 'success' : 'error'; ?>">
                    <?php echo $class_exists ? '✓ Active' : '✗ Inactive'; ?>
                </div>
                <div class="status-label">Virtual Credit System</div>
            </div>
            
            <div class="status-card">
                <h3>Hidden Product</h3>
                <div class="status-value <?php echo $product_exists ? 'success' : 'warning'; ?>">
                    <?php echo $product_exists ? '✓ Created' : '⚠ Not Created'; ?>
                </div>
                <div class="status-label">
                    <?php if ($product_exists): ?>
                        ID: <?php echo $credit_product_id; ?>
                    <?php else: ?>
                        Needs Setup
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="status-card">
                <h3>Credits per Package</h3>
                <div class="status-value"><?php echo $credits_per_package; ?></div>
                <div class="status-label">Credits</div>
            </div>
            
            <div class="status-card">
                <h3>Package Price</h3>
                <div class="status-value"><?php echo wc_price($package_price); ?></div>
                <div class="status-label">Per Package</div>
            </div>
            
            <div class="status-card">
                <h3>Free Initial Credits</h3>
                <div class="status-value"><?php echo $initial_credits; ?></div>
                <div class="status-label">For New Users</div>
            </div>
            
            <div class="status-card">
                <h3>Transactions</h3>
                <div class="status-value"><?php echo $transaction_count; ?></div>
                <div class="status-label">Total Purchases</div>
            </div>
        </div>
        
        <div class="info-box">
            <h4>✨ How the Virtual Credit System Works</h4>
            <ul>
                <li><strong>No Visible Products:</strong> The credit product is completely hidden from your shop</li>
                <li><strong>Admin Controlled:</strong> Change pricing and credit amounts from WordPress Admin → Settings → AI Virtual Fitting</li>
                <li><strong>Direct Purchase:</strong> Customers buy credits through a modal checkout (no cart needed)</li>
                <li><strong>Automatic Setup:</strong> Product is created automatically when plugin activates</li>
                <li><strong>Instant Updates:</strong> Changes to settings automatically update the hidden product</li>
            </ul>
        </div>
        
        <?php if ($product_exists && $product): ?>
        <h2>Hidden Product Details</h2>
        <table>
            <tr>
                <th>Property</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>Product ID</td>
                <td><?php echo $credit_product_id; ?></td>
            </tr>
            <tr>
                <td>Product Name</td>
                <td><?php echo $product->get_name(); ?></td>
            </tr>
            <tr>
                <td>Price</td>
                <td><?php echo wc_price($product->get_price()); ?></td>
            </tr>
            <tr>
                <td>Credits</td>
                <td><?php echo get_post_meta($credit_product_id, '_ai_virtual_fitting_credits', true); ?></td>
            </tr>
            <tr>
                <td>Visibility</td>
                <td><?php echo $product->get_catalog_visibility(); ?></td>
            </tr>
            <tr>
                <td>Virtual Product</td>
                <td><?php echo $product->is_virtual() ? 'Yes' : 'No'; ?></td>
            </tr>
            <tr>
                <td>Hidden from Shop</td>
                <td class="success">✓ Yes (Customers cannot see this product)</td>
            </tr>
        </table>
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <a href="<?php echo admin_url('options-general.php?page=ai-virtual-fitting-settings'); ?>" class="button">
                ⚙️ Go to Settings
            </a>
            <?php if ($product_exists): ?>
            <a href="<?php echo admin_url('post.php?post=' . $credit_product_id . '&action=edit'); ?>" class="button button-secondary">
                📝 View Hidden Product
            </a>
            <?php endif; ?>
            <a href="<?php echo home_url('/virtual-fitting-2/'); ?>" class="button button-secondary">
                🎨 Test Virtual Fitting
            </a>
        </div>
    </div>
</body>
</html>
