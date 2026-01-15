<?php
/**
 * Admin Settings Page Template
 *
 * @package AI_Virtual_Fitting
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$google_ai_api_key = get_option('ai_virtual_fitting_google_ai_api_key', '');
$initial_credits = get_option('ai_virtual_fitting_initial_credits', 2);
$credits_per_package = get_option('ai_virtual_fitting_credits_per_package', 20);
$credits_package_price = get_option('ai_virtual_fitting_credits_package_price', 10.00);
$max_image_size = get_option('ai_virtual_fitting_max_image_size', 10485760);
$api_retry_attempts = get_option('ai_virtual_fitting_api_retry_attempts', 3);
$enable_logging = get_option('ai_virtual_fitting_enable_logging', true);

// Get system status
$admin_settings = new AI_Virtual_Fitting_Admin_Settings();
$system_status = $admin_settings->get_system_status();
?>

<div class="wrap">
    <h1><?php _e('AI Virtual Fitting Settings', 'ai-virtual-fitting'); ?></h1>
    
    <?php settings_errors(); ?>
    
    <!-- Tab Navigation -->
    <h2 class="nav-tab-wrapper">
        <a href="#settings" class="nav-tab nav-tab-active" data-tab="settings">
            <?php _e('Settings', 'ai-virtual-fitting'); ?>
        </a>
        <a href="#users" class="nav-tab" data-tab="users">
            <?php _e('User Management', 'ai-virtual-fitting'); ?>
        </a>
    </h2>
    
    <div class="ai-virtual-fitting-admin-content">
        
        <!-- Settings Tab Content -->
        <div id="tab-settings" class="tab-content active">
        
        <!-- System Status Dashboard -->
        <div class="ai-virtual-fitting-section">
            <h3><?php _e('System Status', 'ai-virtual-fitting'); ?></h3>
            <div class="system-status">
                <?php foreach ($system_status as $key => $status): ?>
                <div class="status-item">
                    <div class="status-label">
                        <?php 
                        switch ($key) {
                            case 'wordpress_version':
                                _e('WordPress Version', 'ai-virtual-fitting');
                                break;
                            case 'woocommerce':
                                _e('WooCommerce', 'ai-virtual-fitting');
                                break;
                            case 'api_key':
                                _e('Google AI API Key', 'ai-virtual-fitting');
                                break;
                            case 'database':
                                _e('Database Tables', 'ai-virtual-fitting');
                                break;
                            case 'credit_product':
                                _e('Credit Product', 'ai-virtual-fitting');
                                break;
                        }
                        ?>
                    </div>
                    <div class="status-value"><?php echo esc_html($status['value']); ?></div>
                    <div class="status-indicator <?php echo esc_attr($status['status']); ?>"></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Settings Form -->
        <form method="post" action="options.php">
            <?php
            settings_fields(AI_Virtual_Fitting_Admin_Settings::SETTINGS_GROUP);
            do_settings_sections(AI_Virtual_Fitting_Admin_Settings::PAGE_SLUG);
            submit_button();
            ?>
        </form>
        
        <!-- Help Section -->
        <div class="ai-virtual-fitting-help">
            <h4><?php _e('Setup Guide', 'ai-virtual-fitting'); ?></h4>
            <ol>
                <li><?php _e('Get your Google AI Studio API key from the link above and enter it in the API Key field.', 'ai-virtual-fitting'); ?></li>
                <li><?php _e('Test the API connection to ensure it\'s working correctly.', 'ai-virtual-fitting'); ?></li>
                <li><?php _e('Configure your credit system settings based on your business model.', 'ai-virtual-fitting'); ?></li>
                <li><?php _e('Adjust system settings like maximum image size based on your server capabilities.', 'ai-virtual-fitting'); ?></li>
                <li><?php _e('Monitor the analytics dashboard to track usage and performance.', 'ai-virtual-fitting'); ?></li>
            </ol>
            
            <h4><?php _e('Troubleshooting', 'ai-virtual-fitting'); ?></h4>
            <ul>
                <li><?php _e('If API tests fail, verify your API key and check that Google AI Studio is accessible from your server.', 'ai-virtual-fitting'); ?></li>
                <li><?php _e('Enable logging to debug issues with virtual fitting processing.', 'ai-virtual-fitting'); ?></li>
                <li><?php _e('Check the system status indicators above for any configuration issues.', 'ai-virtual-fitting'); ?></li>
                <li><?php _e('Ensure WooCommerce is properly configured for credit purchases to work.', 'ai-virtual-fitting'); ?></li>
            </ul>
        </div>
        
        </div><!-- End Settings Tab -->
        
        <!-- Users Tab Content -->
        <div id="tab-users" class="tab-content" style="display: none;">
        
        <!-- Analytics Dashboard -->
        <div class="ai-virtual-fitting-section">
            <h3>
                <?php _e('Analytics Dashboard', 'ai-virtual-fitting'); ?>
                <button type="button" id="refresh-analytics" class="button button-secondary" style="margin-left: 10px;">
                    <?php _e('Refresh', 'ai-virtual-fitting'); ?>
                </button>
            </h3>
            <div class="ai-virtual-fitting-analytics">
                <div class="analytics-card" id="metric-total-users">
                    <h4><?php _e('Total Users', 'ai-virtual-fitting'); ?></h4>
                    <div class="metric">0</div>
                    <div class="description"><?php _e('Users with virtual fitting credits', 'ai-virtual-fitting'); ?></div>
                </div>
                <div class="analytics-card" id="metric-credits-purchased">
                    <h4><?php _e('Credits Purchased', 'ai-virtual-fitting'); ?></h4>
                    <div class="metric">0</div>
                    <div class="description"><?php _e('Total credits purchased by all users', 'ai-virtual-fitting'); ?></div>
                </div>
                <div class="analytics-card" id="metric-credits-used">
                    <h4><?php _e('Credits Used', 'ai-virtual-fitting'); ?></h4>
                    <div class="metric">0</div>
                    <div class="description"><?php _e('Total credits consumed for virtual fittings', 'ai-virtual-fitting'); ?></div>
                </div>
                <div class="analytics-card" id="metric-credits-remaining">
                    <h4><?php _e('Credits Remaining', 'ai-virtual-fitting'); ?></h4>
                    <div class="metric">0</div>
                    <div class="description"><?php _e('Total unused credits across all users', 'ai-virtual-fitting'); ?></div>
                </div>
                <div class="analytics-card" id="metric-recent-activity">
                    <h4><?php _e('Recent Activity', 'ai-virtual-fitting'); ?></h4>
                    <div class="metric">0</div>
                    <div class="description"><?php _e('Credit transactions in last 30 days', 'ai-virtual-fitting'); ?></div>
                </div>
                <div class="analytics-card" id="metric-credit-sales">
                    <h4><?php _e('Credit Sales', 'ai-virtual-fitting'); ?></h4>
                    <div class="metric">0</div>
                    <div class="description"><?php _e('Completed credit package orders', 'ai-virtual-fitting'); ?></div>
                </div>
            </div>
            <p class="analytics-last-updated description" style="text-align: center; margin-top: 15px;">
                <?php _e('Click refresh to load current analytics data', 'ai-virtual-fitting'); ?>
            </p>
        </div>
        
        <!-- User Management Section -->
        <div class="ai-virtual-fitting-section">
            <h3>
                <?php _e('User Credit Management', 'ai-virtual-fitting'); ?>
                <button type="button" id="refresh-user-credits-tab" class="button button-secondary" style="margin-left: 10px;">
                    <?php _e('Refresh', 'ai-virtual-fitting'); ?>
                </button>
            </h3>
            
            <!-- Search and Filters -->
            <div class="user-credits-controls" style="margin-bottom: 20px;">
                <input type="text" id="user-search-tab" placeholder="<?php esc_attr_e('Search users...', 'ai-virtual-fitting'); ?>" style="width: 300px;" />
                <button type="button" id="search-users-tab" class="button"><?php _e('Search', 'ai-virtual-fitting'); ?></button>
                <button type="button" id="clear-search-tab" class="button"><?php _e('Clear', 'ai-virtual-fitting'); ?></button>
            </div>
            
            <!-- User Credits Table -->
            <div id="user-credits-table-container-tab">
                <table class="wp-list-table widefat fixed striped" id="user-credits-table-tab">
                    <thead>
                        <tr>
                            <th><?php _e('User', 'ai-virtual-fitting'); ?></th>
                            <th><?php _e('Email', 'ai-virtual-fitting'); ?></th>
                            <th><?php _e('Credits Remaining', 'ai-virtual-fitting'); ?></th>
                            <th><?php _e('Credits Purchased', 'ai-virtual-fitting'); ?></th>
                            <th><?php _e('Credits Used', 'ai-virtual-fitting'); ?></th>
                            <th><?php _e('Last Activity', 'ai-virtual-fitting'); ?></th>
                            <th><?php _e('Actions', 'ai-virtual-fitting'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="user-credits-tbody-tab">
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px;">
                                <?php _e('Loading user data...', 'ai-virtual-fitting'); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div id="user-credits-pagination-tab" style="margin-top: 20px; text-align: center;">
                <!-- Pagination will be inserted here by JavaScript -->
            </div>
        </div>
        
        </div><!-- End Users Tab -->
        
        <!-- Credit Management Modal -->
        <div id="credit-management-modal" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
            <div style="background-color: #fff; margin: 5% auto; padding: 20px; border-radius: 5px; width: 500px; max-width: 90%;">
                <h3><?php _e('Manage User Credits', 'ai-virtual-fitting'); ?></h3>
                <form id="credit-management-form">
                    <input type="hidden" id="manage-user-id" />
                    <p>
                        <strong><?php _e('User:', 'ai-virtual-fitting'); ?></strong> 
                        <span id="manage-user-name"></span>
                    </p>
                    <p>
                        <strong><?php _e('Current Credits:', 'ai-virtual-fitting'); ?></strong> 
                        <span id="manage-current-credits"></span>
                    </p>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="credit-action"><?php _e('Action', 'ai-virtual-fitting'); ?></label></th>
                            <td>
                                <select id="credit-action" name="credit_action">
                                    <option value="set"><?php _e('Set Credits To', 'ai-virtual-fitting'); ?></option>
                                    <option value="add"><?php _e('Add Credits', 'ai-virtual-fitting'); ?></option>
                                    <option value="subtract"><?php _e('Subtract Credits', 'ai-virtual-fitting'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="credit-amount"><?php _e('Amount', 'ai-virtual-fitting'); ?></label></th>
                            <td>
                                <input type="number" id="credit-amount" name="credit_amount" min="0" max="1000" value="0" />
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Update Credits', 'ai-virtual-fitting'); ?></button>
                        <button type="button" class="button" id="cancel-credit-management"><?php _e('Cancel', 'ai-virtual-fitting'); ?></button>
                    </p>
                </form>
            </div>
        </div>
        
    </div>
</div>