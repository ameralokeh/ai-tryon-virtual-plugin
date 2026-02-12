<?php
/**
 * Cleanup Manager Class
 *
 * Handles automatic deletion of customer uploaded images older than 24 hours
 * to maintain privacy and reduce server storage usage.
 *
 * @package AI_Virtual_Fitting
 * @since 1.0.9.6
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Virtual_Fitting_Cleanup_Manager {
    
    /**
     * Retention period in seconds (24 hours)
     */
    const RETENTION_PERIOD = 86400; // 24 * 60 * 60
    
    /**
     * Cron hook name
     */
    const CRON_HOOK = 'ai_vf_cleanup_old_uploads';
    
    /**
     * Initialize the cleanup manager
     */
    public function __construct() {
        // Schedule cleanup on plugin activation
        register_activation_hook(AI_VIRTUAL_FITTING_PLUGIN_FILE, array($this, 'schedule_cleanup'));
        
        // Unschedule on plugin deactivation
        register_deactivation_hook(AI_VIRTUAL_FITTING_PLUGIN_FILE, array($this, 'unschedule_cleanup'));
        
        // Hook the cleanup function
        add_action(self::CRON_HOOK, array($this, 'cleanup_old_customer_uploads'));
        
        // Add admin settings for cleanup configuration
        add_action('admin_init', array($this, 'register_cleanup_settings'));
    }
    
    /**
     * Schedule the cleanup cron job
     */
    public function schedule_cleanup() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), 'hourly', self::CRON_HOOK);
            error_log('AI Virtual Fitting: Scheduled automatic cleanup of customer uploads');
        }
    }
    
    /**
     * Unschedule the cleanup cron job
     */
    public function unschedule_cleanup() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
            error_log('AI Virtual Fitting: Unscheduled automatic cleanup of customer uploads');
        }
    }
    
    /**
     * Register cleanup settings in admin
     */
    public function register_cleanup_settings() {
        register_setting('ai_virtual_fitting_settings', 'ai_virtual_fitting_cleanup_enabled', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));
        
        register_setting('ai_virtual_fitting_settings', 'ai_virtual_fitting_cleanup_retention_hours', array(
            'type' => 'integer',
            'default' => 24,
            'sanitize_callback' => 'absint'
        ));
    }
    
    /**
     * Main cleanup function - deletes customer uploads older than retention period
     */
    public function cleanup_old_customer_uploads() {
        // Check if cleanup is enabled
        $cleanup_enabled = get_option('ai_virtual_fitting_cleanup_enabled', true);
        if (!$cleanup_enabled) {
            error_log('AI Virtual Fitting: Cleanup is disabled in settings');
            return;
        }
        
        // Get retention period from settings (default 24 hours)
        $retention_hours = get_option('ai_virtual_fitting_cleanup_retention_hours', 24);
        $cutoff_time = time() - ($retention_hours * 3600);
        
        // Get upload directory
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/ai-virtual-fitting/temp/';
        
        if (!is_dir($temp_dir)) {
            error_log('AI Virtual Fitting: Temp directory does not exist: ' . $temp_dir);
            return;
        }
        
        // Find all customer upload files
        $patterns = array(
            $temp_dir . 'customer_*.jpg',
            $temp_dir . 'customer_*.jpeg',
            $temp_dir . 'customer_*.JPEG',
            $temp_dir . 'customer_*.png',
            $temp_dir . 'customer_*.PNG',
            $temp_dir . 'customer_*.webp',
            $temp_dir . 'customer_*.WEBP'
        );
        
        $files = array();
        foreach ($patterns as $pattern) {
            $matched_files = glob($pattern);
            if ($matched_files) {
                $files = array_merge($files, $matched_files);
            }
        }
        
        if (empty($files)) {
            error_log('AI Virtual Fitting: No customer upload files found for cleanup');
            return;
        }
        
        $deleted_count = 0;
        $kept_count = 0;
        $error_count = 0;
        
        foreach ($files as $file) {
            $filename = basename($file);
            
            // Extract timestamp from filename: customer_{user_id}_{timestamp}_{uniqid}.{ext}
            $parts = explode('_', $filename);
            
            if (count($parts) >= 3) {
                $timestamp = intval($parts[2]);
                
                // Check if file is older than cutoff time
                if ($timestamp > 0 && $timestamp < $cutoff_time) {
                    // Delete the file
                    if (@unlink($file)) {
                        $deleted_count++;
                        error_log("AI Virtual Fitting: Deleted old customer upload (>{$retention_hours}h): $filename");
                    } else {
                        $error_count++;
                        error_log("AI Virtual Fitting: Failed to delete customer upload: $filename");
                    }
                } else {
                    $kept_count++;
                }
            } else {
                // Invalid filename format, log warning
                error_log("AI Virtual Fitting: Invalid customer upload filename format: $filename");
            }
        }
        
        // Log summary
        if ($deleted_count > 0 || $error_count > 0) {
            error_log(sprintf(
                'AI Virtual Fitting: Cleanup completed. Deleted: %d, Kept: %d, Errors: %d',
                $deleted_count,
                $kept_count,
                $error_count
            ));
        }
        
        // Store cleanup stats for admin dashboard
        $this->update_cleanup_stats($deleted_count, $kept_count, $error_count);
    }
    
    /**
     * Update cleanup statistics
     */
    private function update_cleanup_stats($deleted, $kept, $errors) {
        $stats = array(
            'last_run' => current_time('mysql'),
            'deleted_count' => $deleted,
            'kept_count' => $kept,
            'error_count' => $errors,
            'total_runs' => get_option('ai_virtual_fitting_cleanup_total_runs', 0) + 1
        );
        
        update_option('ai_virtual_fitting_cleanup_last_stats', $stats);
        update_option('ai_virtual_fitting_cleanup_total_runs', $stats['total_runs']);
        
        // Update cumulative totals
        $total_deleted = get_option('ai_virtual_fitting_cleanup_total_deleted', 0) + $deleted;
        update_option('ai_virtual_fitting_cleanup_total_deleted', $total_deleted);
    }
    
    /**
     * Get cleanup statistics for admin display
     */
    public static function get_cleanup_stats() {
        return get_option('ai_virtual_fitting_cleanup_last_stats', array(
            'last_run' => 'Never',
            'deleted_count' => 0,
            'kept_count' => 0,
            'error_count' => 0,
            'total_runs' => 0
        ));
    }
    
    /**
     * Manual cleanup trigger (for admin use)
     */
    public function manual_cleanup() {
        error_log('AI Virtual Fitting: Manual cleanup triggered');
        $this->cleanup_old_customer_uploads();
    }
    
    /**
     * Get next scheduled cleanup time
     */
    public static function get_next_cleanup_time() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            return date('Y-m-d H:i:s', $timestamp);
        }
        return 'Not scheduled';
    }
}
