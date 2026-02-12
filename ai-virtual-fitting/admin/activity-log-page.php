<?php
/**
 * Activity Log Admin Page
 * Displays virtual fitting activity logs with filtering and deletion options
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current filter values
$days_filter = isset($_GET['days']) ? intval($_GET['days']) : 3;
$status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';
$action_filter = isset($_GET['action_filter']) ? sanitize_text_field($_GET['action_filter']) : '';

// Handle bulk delete
if (isset($_POST['bulk_delete']) && isset($_POST['log_ids']) && check_admin_referer('avf_activity_log_bulk_action')) {
    $logger = new AI_Virtual_Fitting_Activity_Logger();
    $deleted = $logger->delete_logs($_POST['log_ids']);
    if ($deleted) {
        echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(__('%d log entries deleted successfully.', 'ai-virtual-fitting'), $deleted) . '</p></div>';
    }
}

// Handle delete all
if (isset($_POST['delete_all']) && check_admin_referer('avf_activity_log_delete_all')) {
    $logger = new AI_Virtual_Fitting_Activity_Logger();
    $deleted = $logger->delete_all_logs();
    if ($deleted !== false) {
        echo '<div class="notice notice-success is-dismissible"><p>' . __('All log entries deleted successfully.', 'ai-virtual-fitting') . '</p></div>';
    }
}

// Get logs
$logger = new AI_Virtual_Fitting_Activity_Logger();
$logs = $logger->get_logs(array(
    'days' => $days_filter,
    'status' => $status_filter,
    'action' => $action_filter,
    'limit' => 100
));

$total_count = $logger->get_logs_count(array(
    'days' => $days_filter,
    'status' => $status_filter,
    'action' => $action_filter
));

// Get statistics
$stats = $logger->get_statistics($days_filter);
?>

<div class="wrap avf-activity-log">
    <h1><?php _e('Activity Log', 'ai-virtual-fitting'); ?></h1>
    <p class="description">
        <?php _e('View and manage virtual fitting activity logs. Logs are automatically deleted after 30 days.', 'ai-virtual-fitting'); ?>
    </p>
    
    <!-- Statistics Cards -->
    <div class="avf-stats-grid">
        <div class="avf-stat-card">
            <div class="avf-stat-icon avf-stat-icon-total">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="avf-stat-content">
                <div class="avf-stat-value"><?php echo number_format($stats['total_requests']); ?></div>
                <div class="avf-stat-label"><?php _e('Total Requests', 'ai-virtual-fitting'); ?></div>
            </div>
        </div>
        
        <div class="avf-stat-card">
            <div class="avf-stat-icon avf-stat-icon-success">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="avf-stat-content">
                <div class="avf-stat-value"><?php echo number_format($stats['successful_requests']); ?></div>
                <div class="avf-stat-label"><?php _e('Successful', 'ai-virtual-fitting'); ?></div>
            </div>
        </div>
        
        <div class="avf-stat-card">
            <div class="avf-stat-icon avf-stat-icon-error">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="avf-stat-content">
                <div class="avf-stat-value"><?php echo number_format($stats['failed_requests']); ?></div>
                <div class="avf-stat-label"><?php _e('Failed', 'ai-virtual-fitting'); ?></div>
            </div>
        </div>
        
        <div class="avf-stat-card">
            <div class="avf-stat-icon avf-stat-icon-users">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="avf-stat-content">
                <div class="avf-stat-value"><?php echo number_format($stats['unique_users']); ?></div>
                <div class="avf-stat-label"><?php _e('Unique Users', 'ai-virtual-fitting'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="avf-filters-bar">
        <form method="get" action="" id="avf-filter-form">
            <input type="hidden" name="page" value="ai-virtual-fitting-settings">
            <input type="hidden" name="tab" value="activity">
            
            <div class="avf-filter-group">
                <label for="days"><?php _e('Time Period:', 'ai-virtual-fitting'); ?></label>
                <select name="days" id="days">
                    <option value="1" <?php selected($days_filter, 1); ?>><?php _e('Last 24 hours', 'ai-virtual-fitting'); ?></option>
                    <option value="3" <?php selected($days_filter, 3); ?>><?php _e('Last 3 days', 'ai-virtual-fitting'); ?></option>
                    <option value="7" <?php selected($days_filter, 7); ?>><?php _e('Last 7 days', 'ai-virtual-fitting'); ?></option>
                    <option value="14" <?php selected($days_filter, 14); ?>><?php _e('Last 14 days', 'ai-virtual-fitting'); ?></option>
                    <option value="30" <?php selected($days_filter, 30); ?>><?php _e('Last 30 days', 'ai-virtual-fitting'); ?></option>
                    <option value="0" <?php selected($days_filter, 0); ?>><?php _e('All time', 'ai-virtual-fitting'); ?></option>
                </select>
            </div>
            
            <div class="avf-filter-group">
                <label for="status_filter"><?php _e('Status:', 'ai-virtual-fitting'); ?></label>
                <select name="status_filter" id="status_filter">
                    <option value="" <?php selected($status_filter, ''); ?>><?php _e('All', 'ai-virtual-fitting'); ?></option>
                    <option value="success" <?php selected($status_filter, 'success'); ?>><?php _e('Success', 'ai-virtual-fitting'); ?></option>
                    <option value="error" <?php selected($status_filter, 'error'); ?>><?php _e('Error', 'ai-virtual-fitting'); ?></option>
                </select>
            </div>
            
            <div class="avf-filter-group">
                <label for="action_filter"><?php _e('Action:', 'ai-virtual-fitting'); ?></label>
                <select name="action_filter" id="action_filter">
                    <option value="" <?php selected($action_filter, ''); ?>><?php _e('All', 'ai-virtual-fitting'); ?></option>
                    <option value="virtual_fitting" <?php selected($action_filter, 'virtual_fitting'); ?>><?php _e('Virtual Fitting', 'ai-virtual-fitting'); ?></option>
                    <option value="credit_purchase" <?php selected($action_filter, 'credit_purchase'); ?>><?php _e('Credit Purchase', 'ai-virtual-fitting'); ?></option>
                </select>
            </div>
            
            <button type="submit" class="button button-primary">
                <span class="dashicons dashicons-filter"></span>
                <?php _e('Apply Filters', 'ai-virtual-fitting'); ?>
            </button>
            
            <a href="?page=ai-virtual-fitting-settings&tab=activity" class="button">
                <span class="dashicons dashicons-image-rotate"></span>
                <?php _e('Reset', 'ai-virtual-fitting'); ?>
            </a>
        </form>
        
        <div class="avf-bulk-actions">
            <form method="post" action="" onsubmit="return confirm('<?php esc_attr_e('Are you sure you want to delete ALL log entries? This cannot be undone.', 'ai-virtual-fitting'); ?>');">
                <?php wp_nonce_field('avf_activity_log_delete_all'); ?>
                <button type="submit" name="delete_all" class="button button-secondary">
                    <span class="dashicons dashicons-trash"></span>
                    <?php _e('Delete All Logs', 'ai-virtual-fitting'); ?>
                </button>
            </form>
        </div>
    </div>
    
    <!-- Results Count -->
    <div class="avf-results-info">
        <p>
            <?php printf(__('Showing %d of %d log entries', 'ai-virtual-fitting'), count($logs), $total_count); ?>
        </p>
    </div>
    
    <!-- Activity Log Table -->
    <form method="post" action="" id="avf-activity-log-form">
        <?php wp_nonce_field('avf_activity_log_bulk_action'); ?>
        
        <table class="wp-list-table widefat fixed striped avf-activity-table">
            <thead>
                <tr>
                    <th class="check-column">
                        <input type="checkbox" id="select-all-logs">
                    </th>
                    <th><?php _e('Timestamp', 'ai-virtual-fitting'); ?></th>
                    <th><?php _e('User', 'ai-virtual-fitting'); ?></th>
                    <th><?php _e('Action', 'ai-virtual-fitting'); ?></th>
                    <th><?php _e('Product', 'ai-virtual-fitting'); ?></th>
                    <th><?php _e('Status', 'ai-virtual-fitting'); ?></th>
                    <th><?php _e('Details', 'ai-virtual-fitting'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" class="avf-no-logs">
                            <div class="avf-empty-state">
                                <span class="dashicons dashicons-info"></span>
                                <p><?php _e('No activity logs found for the selected filters.', 'ai-virtual-fitting'); ?></p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr class="avf-log-row" data-log-id="<?php echo esc_attr($log->id); ?>">
                            <th class="check-column">
                                <input type="checkbox" name="log_ids[]" value="<?php echo esc_attr($log->id); ?>">
                            </th>
                            <td class="avf-timestamp">
                                <?php 
                                // Display timestamp - stored in GMT, convert to local
                                if (!empty($log->created_at) && $log->created_at !== '0000-00-00 00:00:00') {
                                    // Use mysql2date which handles WordPress timezone conversion
                                    echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $log->created_at));
                                } else {
                                    echo '<span class="avf-na">—</span>';
                                }
                                ?>
                            </td>
                            <td class="avf-user">
                                <strong><?php echo esc_html($log->user_name); ?></strong><br>
                                <small><?php echo esc_html($log->user_email); ?></small>
                            </td>
                            <td class="avf-action">
                                <span class="avf-action-badge">
                                    <?php echo esc_html(ucwords(str_replace('_', ' ', $log->action))); ?>
                                </span>
                            </td>
                            <td class="avf-product">
                                <?php if ($log->product_name): ?>
                                    <a href="<?php echo esc_url(get_permalink($log->product_id)); ?>" target="_blank">
                                        <?php echo esc_html($log->product_name); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="avf-na">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="avf-status">
                                <?php if ($log->status === 'success'): ?>
                                    <span class="avf-status-badge avf-status-success">
                                        <span class="dashicons dashicons-yes-alt"></span>
                                        <?php _e('Success', 'ai-virtual-fitting'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="avf-status-badge avf-status-error">
                                        <span class="dashicons dashicons-warning"></span>
                                        <?php _e('Error', 'ai-virtual-fitting'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="avf-details">
                                <button type="button" class="button button-small avf-view-details" data-log-id="<?php echo esc_attr($log->id); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <?php _e('View', 'ai-virtual-fitting'); ?>
                                </button>
                                
                                <!-- Hidden details -->
                                <div class="avf-log-details" id="avf-log-details-<?php echo esc_attr($log->id); ?>" style="display:none;">
                                    <?php if ($log->error_message): ?>
                                        <div class="avf-detail-row">
                                            <strong><?php _e('Error Message:', 'ai-virtual-fitting'); ?></strong>
                                            <p class="avf-error-message"><?php echo esc_html($log->error_message); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($log->processing_time): ?>
                                        <div class="avf-detail-row">
                                            <strong><?php _e('Processing Time:', 'ai-virtual-fitting'); ?></strong>
                                            <p><?php echo esc_html(number_format($log->processing_time, 2)); ?> <?php _e('seconds', 'ai-virtual-fitting'); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="avf-detail-row">
                                        <strong><?php _e('IP Address:', 'ai-virtual-fitting'); ?></strong>
                                        <p><?php echo esc_html($log->ip_address); ?></p>
                                    </div>
                                    
                                    <?php if ($log->user_agent): ?>
                                        <div class="avf-detail-row">
                                            <strong><?php _e('User Agent:', 'ai-virtual-fitting'); ?></strong>
                                            <p class="avf-user-agent"><?php echo esc_html(substr($log->user_agent, 0, 100)); ?><?php echo strlen($log->user_agent) > 100 ? '...' : ''; ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th class="check-column">
                        <input type="checkbox">
                    </th>
                    <th><?php _e('Timestamp', 'ai-virtual-fitting'); ?></th>
                    <th><?php _e('User', 'ai-virtual-fitting'); ?></th>
                    <th><?php _e('Action', 'ai-virtual-fitting'); ?></th>
                    <th><?php _e('Product', 'ai-virtual-fitting'); ?></th>
                    <th><?php _e('Status', 'ai-virtual-fitting'); ?></th>
                    <th><?php _e('Details', 'ai-virtual-fitting'); ?></th>
                </tr>
            </tfoot>
        </table>
        
        <?php if (!empty($logs)): ?>
            <div class="avf-bulk-actions-bottom">
                <button type="submit" name="bulk_delete" class="button button-secondary" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete the selected log entries?', 'ai-virtual-fitting'); ?>');">
                    <span class="dashicons dashicons-trash"></span>
                    <?php _e('Delete Selected', 'ai-virtual-fitting'); ?>
                </button>
            </div>
        <?php endif; ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Select all checkbox
    $('#select-all-logs').on('change', function() {
        $('input[name="log_ids[]"]').prop('checked', $(this).prop('checked'));
    });
    
    // View details toggle
    $('.avf-view-details').on('click', function() {
        var logId = $(this).data('log-id');
        var detailsDiv = $('#avf-log-details-' + logId);
        
        if (detailsDiv.is(':visible')) {
            detailsDiv.slideUp(200);
            $(this).find('.dashicons').removeClass('dashicons-hidden').addClass('dashicons-visibility');
        } else {
            detailsDiv.slideDown(200);
            $(this).find('.dashicons').removeClass('dashicons-visibility').addClass('dashicons-hidden');
        }
    });
    
    // Handle tab switching on page load if tab parameter is present
    var urlParams = new URLSearchParams(window.location.search);
    var tab = urlParams.get('tab');
    if (tab === 'activity') {
        // Switch to activity tab
        $('.nav-tab').removeClass('nav-tab-active');
        $('.nav-tab[data-tab="activity"]').addClass('nav-tab-active');
        $('.tab-content').hide();
        $('#tab-activity').show();
    }
});
</script>

<style>
.avf-activity-log {
    max-width: 1400px;
}

.avf-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.avf-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.avf-stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.avf-stat-icon-total {
    background: #e3f2fd;
    color: #1976d2;
}

.avf-stat-icon-success {
    background: #e8f5e9;
    color: #388e3c;
}

.avf-stat-icon-error {
    background: #ffebee;
    color: #d32f2f;
}

.avf-stat-icon-users {
    background: #f3e5f5;
    color: #7b1fa2;
}

.avf-stat-value {
    font-size: 28px;
    font-weight: 600;
    line-height: 1;
    margin-bottom: 5px;
}

.avf-stat-label {
    font-size: 13px;
    color: #666;
}

.avf-filters-bar {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    margin: 20px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.avf-filters-bar form {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.avf-filter-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.avf-filter-group label {
    font-weight: 600;
    margin: 0;
}

.avf-filter-group select {
    min-width: 150px;
}

.avf-results-info {
    margin: 15px 0;
    color: #666;
}

.avf-activity-table {
    margin-top: 20px;
}

.avf-activity-table th,
.avf-activity-table td {
    vertical-align: middle;
}

.avf-timestamp {
    white-space: nowrap;
}

.avf-user strong {
    display: block;
    margin-bottom: 3px;
}

.avf-user small {
    color: #666;
}

.avf-action-badge {
    display: inline-block;
    padding: 4px 10px;
    background: #f0f0f0;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}

.avf-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 600;
}

.avf-status-success {
    background: #e8f5e9;
    color: #2e7d32;
}

.avf-status-error {
    background: #ffebee;
    color: #c62828;
}

.avf-na {
    color: #999;
}

.avf-view-details {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.avf-log-details {
    margin-top: 10px;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.avf-detail-row {
    margin-bottom: 10px;
}

.avf-detail-row:last-child {
    margin-bottom: 0;
}

.avf-detail-row strong {
    display: block;
    margin-bottom: 5px;
    color: #333;
}

.avf-detail-row p {
    margin: 0;
    color: #666;
}

.avf-error-message {
    color: #c62828;
    font-family: monospace;
    font-size: 12px;
}

.avf-user-agent {
    font-family: monospace;
    font-size: 11px;
    word-break: break-all;
}

.avf-empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.avf-empty-state .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #ccc;
}

.avf-bulk-actions-bottom {
    margin-top: 15px;
}
</style>
