<?php
/**
 * Analytics and Monitoring functionality for AI Virtual Fitting Plugin
 *
 * @package AI_Virtual_Fitting
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI Virtual Fitting Analytics Manager Class
 */
class AI_Virtual_Fitting_Analytics_Manager {
    
    /**
     * Analytics table name
     */
    const ANALYTICS_TABLE = 'ai_virtual_fitting_analytics';
    
    /**
     * Events table name
     */
    const EVENTS_TABLE = 'ai_virtual_fitting_events';
    
    /**
     * Performance metrics table name
     */
    const METRICS_TABLE = 'ai_virtual_fitting_metrics';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
        $this->create_analytics_tables();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // AJAX handlers for analytics
        add_action('wp_ajax_ai_virtual_fitting_track_metrics', array($this, 'track_frontend_metrics'));
        add_action('wp_ajax_ai_virtual_fitting_get_analytics', array($this, 'get_analytics_data'));
        add_action('wp_ajax_ai_virtual_fitting_log_tryon_button_click', array($this, 'ajax_log_button_click'));
        add_action('wp_ajax_nopriv_ai_virtual_fitting_log_tryon_button_click', array($this, 'ajax_log_button_click'));
        
        // Hook into plugin events for automatic tracking
        add_action('ai_virtual_fitting_image_uploaded', array($this, 'track_image_upload'));
        add_action('ai_virtual_fitting_fitting_completed', array($this, 'track_fitting_completion'));
        add_action('ai_virtual_fitting_fitting_failed', array($this, 'track_fitting_failure'));
        add_action('ai_virtual_fitting_credit_purchased', array($this, 'track_credit_purchase'));
        add_action('ai_virtual_fitting_image_downloaded', array($this, 'track_image_download'));
        
        // Schedule analytics cleanup
        add_action('ai_virtual_fitting_cleanup_analytics', array($this, 'cleanup_old_analytics'));
        
        if (!wp_next_scheduled('ai_virtual_fitting_cleanup_analytics')) {
            wp_schedule_event(time(), 'weekly', 'ai_virtual_fitting_cleanup_analytics');
        }
    }
    
    /**
     * Create analytics database tables
     */
    private function create_analytics_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Analytics summary table
        $analytics_table = $wpdb->prefix . self::ANALYTICS_TABLE;
        $analytics_sql = "CREATE TABLE IF NOT EXISTS $analytics_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            date_recorded date NOT NULL,
            total_users int(11) DEFAULT 0,
            new_users int(11) DEFAULT 0,
            total_fittings int(11) DEFAULT 0,
            successful_fittings int(11) DEFAULT 0,
            failed_fittings int(11) DEFAULT 0,
            total_uploads int(11) DEFAULT 0,
            total_downloads int(11) DEFAULT 0,
            credits_purchased int(11) DEFAULT 0,
            credits_used int(11) DEFAULT 0,
            avg_processing_time decimal(10,2) DEFAULT 0,
            peak_concurrent_users int(11) DEFAULT 0,
            button_clicks int(11) DEFAULT 0,
            button_conversions int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY date_recorded (date_recorded),
            KEY idx_date (date_recorded)
        ) $charset_collate;";
        
        // Events table for detailed tracking
        $events_table = $wpdb->prefix . self::EVENTS_TABLE;
        $events_sql = "CREATE TABLE IF NOT EXISTS $events_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            event_type varchar(50) NOT NULL,
            event_data longtext,
            processing_time decimal(10,3) DEFAULT NULL,
            success tinyint(1) DEFAULT 1,
            error_message text,
            user_agent text,
            ip_address varchar(45),
            button_click tinyint(1) DEFAULT 0,
            source_product_id bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_event_type (event_type),
            KEY idx_created_at (created_at),
            KEY idx_success (success),
            KEY idx_button_click (button_click),
            KEY idx_source_product (source_product_id)
        ) $charset_collate;";
        
        // Performance metrics table
        $metrics_table = $wpdb->prefix . self::METRICS_TABLE;
        $metrics_sql = "CREATE TABLE IF NOT EXISTS $metrics_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            metric_name varchar(100) NOT NULL,
            metric_value decimal(15,4) NOT NULL,
            metric_unit varchar(20) DEFAULT '',
            recorded_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_metric_name (metric_name),
            KEY idx_recorded_at (recorded_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($analytics_sql);
        dbDelta($events_sql);
        dbDelta($metrics_sql);
        
        // Run migration for existing tables
        $this->migrate_analytics_tables();
    }
    
    /**
     * Migrate analytics tables to add new columns
     */
    private function migrate_analytics_tables() {
        global $wpdb;
        
        $events_table = $wpdb->prefix . self::EVENTS_TABLE;
        $analytics_table = $wpdb->prefix . self::ANALYTICS_TABLE;
        
        // Check if button_click column exists in events table
        $button_click_exists = $wpdb->get_results($wpdb->prepare("
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = %s 
            AND TABLE_NAME = %s 
            AND COLUMN_NAME = 'button_click'
        ", DB_NAME, $events_table));
        
        if (empty($button_click_exists)) {
            $wpdb->query("ALTER TABLE $events_table ADD COLUMN button_click tinyint(1) DEFAULT 0");
            $wpdb->query("ALTER TABLE $events_table ADD INDEX idx_button_click (button_click)");
        }
        
        // Check if source_product_id column exists in events table
        $source_product_exists = $wpdb->get_results($wpdb->prepare("
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = %s 
            AND TABLE_NAME = %s 
            AND COLUMN_NAME = 'source_product_id'
        ", DB_NAME, $events_table));
        
        if (empty($source_product_exists)) {
            $wpdb->query("ALTER TABLE $events_table ADD COLUMN source_product_id bigint(20) DEFAULT NULL");
            $wpdb->query("ALTER TABLE $events_table ADD INDEX idx_source_product (source_product_id)");
        }
        
        // Check if button_clicks column exists in analytics table
        $button_clicks_col_exists = $wpdb->get_results($wpdb->prepare("
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = %s 
            AND TABLE_NAME = %s 
            AND COLUMN_NAME = 'button_clicks'
        ", DB_NAME, $analytics_table));
        
        if (empty($button_clicks_col_exists)) {
            $wpdb->query("ALTER TABLE $analytics_table ADD COLUMN button_clicks int(11) DEFAULT 0");
        }
        
        // Check if button_conversions column exists in analytics table
        $button_conversions_col_exists = $wpdb->get_results($wpdb->prepare("
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = %s 
            AND TABLE_NAME = %s 
            AND COLUMN_NAME = 'button_conversions'
        ", DB_NAME, $analytics_table));
        
        if (empty($button_conversions_col_exists)) {
            $wpdb->query("ALTER TABLE $analytics_table ADD COLUMN button_conversions int(11) DEFAULT 0");
        }
    }
    
    /**
     * Track an event
     *
     * @param string $event_type Type of event
     * @param array $event_data Event data
     * @param int $user_id User ID (optional)
     * @param float $processing_time Processing time in seconds (optional)
     * @param bool $success Whether the event was successful
     * @param string $error_message Error message if failed
     */
    public function track_event($event_type, $event_data = array(), $user_id = null, $processing_time = null, $success = true, $error_message = '') {
        global $wpdb;
        
        if (!AI_Virtual_Fitting_Core::get_option('enable_analytics', true)) {
            return;
        }
        
        $events_table = $wpdb->prefix . self::EVENTS_TABLE;
        
        // Determine if this is a button click event
        $is_button_click = ($event_type === 'button_click' || $event_type === 'button_conversion') ? 1 : 0;
        
        // Extract source product ID if available
        $source_product_id = isset($event_data['product_id']) ? (int) $event_data['product_id'] : null;
        
        $data = array(
            'user_id' => $user_id,
            'event_type' => $event_type,
            'event_data' => json_encode($event_data),
            'processing_time' => $processing_time,
            'success' => $success ? 1 : 0,
            'error_message' => $error_message,
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'ip_address' => $this->get_client_ip(),
            'button_click' => $is_button_click,
            'source_product_id' => $source_product_id,
            'created_at' => current_time('mysql')
        );
        
        $wpdb->insert($events_table, $data);
        
        // Update daily analytics summary
        $this->update_daily_analytics($event_type, $success, $processing_time);
    }
    
    /**
     * Track performance metric
     *
     * @param string $metric_name Name of the metric
     * @param float $value Metric value
     * @param string $unit Unit of measurement
     */
    public function track_metric($metric_name, $value, $unit = '') {
        global $wpdb;
        
        if (!AI_Virtual_Fitting_Core::get_option('enable_analytics', true)) {
            return;
        }
        
        $metrics_table = $wpdb->prefix . self::METRICS_TABLE;
        
        $wpdb->insert(
            $metrics_table,
            array(
                'metric_name' => $metric_name,
                'metric_value' => $value,
                'metric_unit' => $unit,
                'recorded_at' => current_time('mysql')
            )
        );
    }
    
    /**
     * Update daily analytics summary
     *
     * @param string $event_type Event type
     * @param bool $success Whether event was successful
     * @param float $processing_time Processing time
     */
    private function update_daily_analytics($event_type, $success, $processing_time = null) {
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . self::ANALYTICS_TABLE;
        $today = current_time('Y-m-d');
        
        // Get or create today's record
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $analytics_table WHERE date_recorded = %s",
            $today
        ));
        
        if (!$existing) {
            // Create new record for today
            $wpdb->insert(
                $analytics_table,
                array(
                    'date_recorded' => $today,
                    'total_users' => $this->get_total_users_count(),
                    'new_users' => $this->get_new_users_count_today()
                )
            );
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $analytics_table WHERE date_recorded = %s",
                $today
            ));
        }
        
        // Update counters based on event type
        $updates = array();
        
        switch ($event_type) {
            case 'image_upload':
                $updates['total_uploads'] = $existing->total_uploads + 1;
                break;
                
            case 'fitting_request':
                $updates['total_fittings'] = $existing->total_fittings + 1;
                if ($success) {
                    $updates['successful_fittings'] = $existing->successful_fittings + 1;
                } else {
                    $updates['failed_fittings'] = $existing->failed_fittings + 1;
                }
                
                // Update average processing time
                if ($processing_time !== null) {
                    $current_avg = $existing->avg_processing_time;
                    $total_fittings = $existing->total_fittings + 1;
                    $new_avg = (($current_avg * ($total_fittings - 1)) + $processing_time) / $total_fittings;
                    $updates['avg_processing_time'] = $new_avg;
                }
                break;
                
            case 'image_download':
                $updates['total_downloads'] = $existing->total_downloads + 1;
                break;
                
            case 'credit_purchase':
                $updates['credits_purchased'] = $existing->credits_purchased + 1;
                break;
                
            case 'credit_used':
                $updates['credits_used'] = $existing->credits_used + 1;
                break;
                
            case 'button_click':
                $updates['button_clicks'] = $existing->button_clicks + 1;
                break;
                
            case 'button_conversion':
                $updates['button_conversions'] = $existing->button_conversions + 1;
                break;
        }
        
        if (!empty($updates)) {
            $wpdb->update(
                $analytics_table,
                $updates,
                array('date_recorded' => $today)
            );
        }
    }
    
    /**
     * Get analytics dashboard data
     *
     * @param string $period Period to get data for (today, week, month, year)
     * @return array Analytics data
     */
    public function get_dashboard_analytics($period = 'week') {
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . self::ANALYTICS_TABLE;
        $events_table = $wpdb->prefix . self::EVENTS_TABLE;
        
        // Determine date range
        switch ($period) {
            case 'today':
                $start_date = current_time('Y-m-d');
                $end_date = $start_date;
                break;
            case 'week':
                $start_date = date('Y-m-d', strtotime('-7 days'));
                $end_date = current_time('Y-m-d');
                break;
            case 'month':
                $start_date = date('Y-m-d', strtotime('-30 days'));
                $end_date = current_time('Y-m-d');
                break;
            case 'year':
                $start_date = date('Y-m-d', strtotime('-365 days'));
                $end_date = current_time('Y-m-d');
                break;
            default:
                $start_date = date('Y-m-d', strtotime('-7 days'));
                $end_date = current_time('Y-m-d');
        }
        
        // Get summary data
        $summary = $wpdb->get_row($wpdb->prepare("
            SELECT 
                SUM(total_fittings) as total_fittings,
                SUM(successful_fittings) as successful_fittings,
                SUM(failed_fittings) as failed_fittings,
                SUM(total_uploads) as total_uploads,
                SUM(total_downloads) as total_downloads,
                SUM(credits_purchased) as credits_purchased,
                SUM(credits_used) as credits_used,
                AVG(avg_processing_time) as avg_processing_time,
                MAX(peak_concurrent_users) as peak_concurrent_users
            FROM $analytics_table 
            WHERE date_recorded BETWEEN %s AND %s
        ", $start_date, $end_date));
        
        // Get daily breakdown
        $daily_data = $wpdb->get_results($wpdb->prepare("
            SELECT 
                date_recorded,
                total_fittings,
                successful_fittings,
                failed_fittings,
                total_uploads,
                total_downloads,
                avg_processing_time
            FROM $analytics_table 
            WHERE date_recorded BETWEEN %s AND %s
            ORDER BY date_recorded ASC
        ", $start_date, $end_date));
        
        // Get error breakdown
        $error_breakdown = $wpdb->get_results($wpdb->prepare("
            SELECT 
                error_message,
                COUNT(*) as error_count
            FROM $events_table 
            WHERE success = 0 
            AND created_at >= %s 
            AND created_at <= %s
            AND error_message != ''
            GROUP BY error_message
            ORDER BY error_count DESC
            LIMIT 10
        ", $start_date . ' 00:00:00', $end_date . ' 23:59:59'));
        
        // Get popular products (most tried on)
        $popular_products = $wpdb->get_results($wpdb->prepare("
            SELECT 
                JSON_EXTRACT(event_data, '$.product_id') as product_id,
                COUNT(*) as try_count
            FROM $events_table 
            WHERE event_type = 'fitting_request'
            AND created_at >= %s 
            AND created_at <= %s
            AND JSON_EXTRACT(event_data, '$.product_id') IS NOT NULL
            GROUP BY JSON_EXTRACT(event_data, '$.product_id')
            ORDER BY try_count DESC
            LIMIT 10
        ", $start_date . ' 00:00:00', $end_date . ' 23:59:59'));
        
        // Calculate success rate
        $success_rate = 0;
        if ($summary && $summary->total_fittings > 0) {
            $success_rate = ($summary->successful_fittings / $summary->total_fittings) * 100;
        }
        
        return array(
            'period' => $period,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'summary' => array(
                'total_fittings' => (int) ($summary->total_fittings ?? 0),
                'successful_fittings' => (int) ($summary->successful_fittings ?? 0),
                'failed_fittings' => (int) ($summary->failed_fittings ?? 0),
                'success_rate' => round($success_rate, 2),
                'total_uploads' => (int) ($summary->total_uploads ?? 0),
                'total_downloads' => (int) ($summary->total_downloads ?? 0),
                'credits_purchased' => (int) ($summary->credits_purchased ?? 0),
                'credits_used' => (int) ($summary->credits_used ?? 0),
                'avg_processing_time' => round($summary->avg_processing_time ?? 0, 2),
                'peak_concurrent_users' => (int) ($summary->peak_concurrent_users ?? 0)
            ),
            'daily_data' => $daily_data,
            'error_breakdown' => $error_breakdown,
            'popular_products' => $popular_products
        );
    }
    
    /**
     * Get real-time system status
     *
     * @return array System status data
     */
    public function get_system_status() {
        global $wpdb;
        
        $events_table = $wpdb->prefix . self::EVENTS_TABLE;
        
        // Get current queue status
        $queue = get_option('ai_virtual_fitting_queue', array());
        $queue_stats = array(
            'total' => count($queue),
            'queued' => 0,
            'processing' => 0,
            'completed' => 0,
            'failed' => 0
        );
        
        foreach ($queue as $item) {
            if (isset($queue_stats[$item['status']])) {
                $queue_stats[$item['status']]++;
            }
        }
        
        // Get recent activity (last hour)
        $recent_activity = $wpdb->get_results($wpdb->prepare("
            SELECT 
                event_type,
                COUNT(*) as count,
                AVG(processing_time) as avg_time
            FROM $events_table 
            WHERE created_at >= %s
            GROUP BY event_type
            ORDER BY count DESC
        ", date('Y-m-d H:i:s', strtotime('-1 hour'))));
        
        // Get error rate (last 24 hours)
        $error_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_events,
                SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed_events
            FROM $events_table 
            WHERE created_at >= %s
        ", date('Y-m-d H:i:s', strtotime('-24 hours'))));
        
        $error_rate = 0;
        if ($error_stats && $error_stats->total_events > 0) {
            $error_rate = ($error_stats->failed_events / $error_stats->total_events) * 100;
        }
        
        // Get performance metrics
        $performance_manager = AI_Virtual_Fitting_Core::instance()->get_performance_manager();
        $performance_metrics = $performance_manager->get_performance_metrics();
        
        return array(
            'timestamp' => current_time('mysql'),
            'queue_status' => $queue_stats,
            'recent_activity' => $recent_activity,
            'error_rate_24h' => round($error_rate, 2),
            'performance_metrics' => $performance_metrics,
            'system_health' => $this->get_system_health_status()
        );
    }
    
    /**
     * Get system health status
     *
     * @return array Health status
     */
    private function get_system_health_status() {
        $image_processor = new AI_Virtual_Fitting_Image_Processor();
        $health_check = $image_processor->perform_health_check();
        
        return array(
            'overall_status' => $health_check['overall_status'],
            'checks_passed' => count($health_check['checks']),
            'warnings' => count($health_check['warnings']),
            'errors' => count($health_check['errors']),
            'details' => $health_check
        );
    }
    
    /**
     * Track image upload event
     *
     * @param array $data Event data
     */
    public function track_image_upload($data) {
        $this->track_event('image_upload', $data, $data['user_id'] ?? null);
    }
    
    /**
     * Track fitting completion event
     *
     * @param array $data Event data
     */
    public function track_fitting_completion($data) {
        $processing_time = $data['processing_time'] ?? null;
        $this->track_event('fitting_request', $data, $data['user_id'] ?? null, $processing_time, true);
        
        // Also track credit usage
        $this->track_event('credit_used', array('credits_used' => 1), $data['user_id'] ?? null);
    }
    
    /**
     * Track fitting failure event
     *
     * @param array $data Event data
     */
    public function track_fitting_failure($data) {
        $processing_time = $data['processing_time'] ?? null;
        $error_message = $data['error_message'] ?? '';
        $this->track_event('fitting_request', $data, $data['user_id'] ?? null, $processing_time, false, $error_message);
    }
    
    /**
     * Track credit purchase event
     *
     * @param array $data Event data
     */
    public function track_credit_purchase($data) {
        $this->track_event('credit_purchase', $data, $data['user_id'] ?? null);
    }
    
    /**
     * Track image download event
     *
     * @param array $data Event data
     */
    public function track_image_download($data) {
        $this->track_event('image_download', $data, $data['user_id'] ?? null);
    }
    
    /**
     * Log button click event
     *
     * @param int $product_id Product ID
     * @param int $user_id User ID (optional)
     * @return bool Success status
     */
    public function log_button_click($product_id, $user_id = null) {
        if (!AI_Virtual_Fitting_Core::get_option('enable_analytics', true)) {
            return false;
        }
        
        $event_data = array(
            'product_id' => $product_id,
            'source' => 'tryon_button'
        );
        
        $this->track_event('button_click', $event_data, $user_id);
        
        return true;
    }
    
    /**
     * Track button-to-fitting conversion
     *
     * @param int $product_id Product ID
     * @param int $user_id User ID
     * @return bool Success status
     */
    public function track_conversion($product_id, $user_id) {
        global $wpdb;
        
        if (!AI_Virtual_Fitting_Core::get_option('enable_analytics', true)) {
            return false;
        }
        
        $events_table = $wpdb->prefix . self::EVENTS_TABLE;
        
        // Find the most recent button click for this product and user
        $button_click = $wpdb->get_row($wpdb->prepare("
            SELECT id, created_at 
            FROM $events_table 
            WHERE event_type = 'button_click' 
            AND user_id = %d 
            AND JSON_EXTRACT(event_data, '$.product_id') = %d
            ORDER BY created_at DESC 
            LIMIT 1
        ", $user_id, $product_id));
        
        if ($button_click) {
            // Track conversion event
            $event_data = array(
                'product_id' => $product_id,
                'button_click_id' => $button_click->id,
                'time_to_conversion' => strtotime(current_time('mysql')) - strtotime($button_click->created_at)
            );
            
            $this->track_event('button_conversion', $event_data, $user_id);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get button click statistics
     *
     * @param array $date_range Optional date range array with 'start' and 'end' keys
     * @return array Button statistics
     */
    public function get_button_stats($date_range = null) {
        global $wpdb;
        
        $events_table = $wpdb->prefix . self::EVENTS_TABLE;
        
        // Set default date range (last 30 days)
        if (!$date_range) {
            $date_range = array(
                'start' => date('Y-m-d H:i:s', strtotime('-30 days')),
                'end' => current_time('mysql')
            );
        }
        
        // Get total button clicks
        $total_clicks = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM $events_table 
            WHERE event_type = 'button_click' 
            AND created_at BETWEEN %s AND %s
        ", $date_range['start'], $date_range['end']));
        
        // Get total conversions
        $total_conversions = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM $events_table 
            WHERE event_type = 'button_conversion' 
            AND created_at BETWEEN %s AND %s
        ", $date_range['start'], $date_range['end']));
        
        // Calculate conversion rate
        $conversion_rate = $total_clicks > 0 ? ($total_conversions / $total_clicks) * 100 : 0;
        
        // Get clicks by product
        $clicks_by_product = $wpdb->get_results($wpdb->prepare("
            SELECT 
                JSON_EXTRACT(event_data, '$.product_id') as product_id,
                COUNT(*) as click_count
            FROM $events_table 
            WHERE event_type = 'button_click' 
            AND created_at BETWEEN %s AND %s
            GROUP BY JSON_EXTRACT(event_data, '$.product_id')
            ORDER BY click_count DESC
        ", $date_range['start'], $date_range['end']));
        
        // Get daily breakdown
        $daily_clicks = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as clicks
            FROM $events_table 
            WHERE event_type = 'button_click' 
            AND created_at BETWEEN %s AND %s
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", $date_range['start'], $date_range['end']));
        
        return array(
            'total_clicks' => (int) $total_clicks,
            'total_conversions' => (int) $total_conversions,
            'conversion_rate' => round($conversion_rate, 2),
            'clicks_by_product' => $clicks_by_product,
            'daily_clicks' => $daily_clicks,
            'date_range' => $date_range
        );
    }
    
    /**
     * Get most popular products (by button clicks)
     *
     * @param int $limit Number of products to return
     * @param array $date_range Optional date range
     * @return array Popular products
     */
    public function get_popular_products($limit = 10, $date_range = null) {
        global $wpdb;
        
        $events_table = $wpdb->prefix . self::EVENTS_TABLE;
        
        // Set default date range (last 30 days)
        if (!$date_range) {
            $date_range = array(
                'start' => date('Y-m-d H:i:s', strtotime('-30 days')),
                'end' => current_time('mysql')
            );
        }
        
        // Get products with click counts
        $products = $wpdb->get_results($wpdb->prepare("
            SELECT 
                JSON_EXTRACT(event_data, '$.product_id') as product_id,
                COUNT(*) as click_count,
                COUNT(DISTINCT user_id) as unique_users
            FROM $events_table 
            WHERE event_type = 'button_click' 
            AND created_at BETWEEN %s AND %s
            AND JSON_EXTRACT(event_data, '$.product_id') IS NOT NULL
            GROUP BY JSON_EXTRACT(event_data, '$.product_id')
            ORDER BY click_count DESC
            LIMIT %d
        ", $date_range['start'], $date_range['end'], $limit));
        
        // Enrich with product data
        $popular_products = array();
        foreach ($products as $product) {
            $product_id = (int) str_replace('"', '', $product->product_id);
            $wc_product = wc_get_product($product_id);
            
            if ($wc_product) {
                $popular_products[] = array(
                    'product_id' => $product_id,
                    'product_name' => $wc_product->get_name(),
                    'product_url' => get_permalink($product_id),
                    'click_count' => (int) $product->click_count,
                    'unique_users' => (int) $product->unique_users
                );
            }
        }
        
        return $popular_products;
    }
    
    /**
     * Calculate conversion rate for button clicks
     *
     * @param array $date_range Optional date range
     * @return float Conversion rate percentage
     */
    public function calculate_conversion_rate($date_range = null) {
        global $wpdb;
        
        $events_table = $wpdb->prefix . self::EVENTS_TABLE;
        
        // Set default date range (last 30 days)
        if (!$date_range) {
            $date_range = array(
                'start' => date('Y-m-d H:i:s', strtotime('-30 days')),
                'end' => current_time('mysql')
            );
        }
        
        // Get total button clicks
        $total_clicks = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM $events_table 
            WHERE event_type = 'button_click' 
            AND created_at BETWEEN %s AND %s
        ", $date_range['start'], $date_range['end']));
        
        // Get total conversions
        $total_conversions = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM $events_table 
            WHERE event_type = 'button_conversion' 
            AND created_at BETWEEN %s AND %s
        ", $date_range['start'], $date_range['end']));
        
        // Calculate conversion rate
        if ($total_clicks > 0) {
            return round(($total_conversions / $total_clicks) * 100, 2);
        }
        
        return 0.0;
    }
    
    /**
     * Track frontend metrics via AJAX
     */
    public function track_frontend_metrics() {
        if (!wp_verify_nonce($_POST['nonce'], 'ai_virtual_fitting_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Authentication required'));
        }
        
        $metrics = json_decode(stripslashes($_POST['metrics']), true);
        
        if (!is_array($metrics)) {
            wp_send_json_error(array('message' => 'Invalid metrics data'));
        }
        
        $user_id = get_current_user_id();
        
        // Track each metric
        foreach ($metrics as $metric_name => $value) {
            if (is_numeric($value) && $value > 0) {
                $this->track_metric("frontend_{$metric_name}", $value, 'ms');
            }
        }
        
        // Track user activity
        $this->track_event('frontend_metrics', $metrics, $user_id);
        
        wp_send_json_success(array('message' => 'Metrics tracked successfully'));
    }
    
    /**
     * AJAX handler for logging button clicks
     */
    public function ajax_log_button_click() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ai_virtual_fitting_tryon_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Get product ID
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        if ($product_id <= 0) {
            wp_send_json_error(array('message' => 'Invalid product ID'));
            return;
        }
        
        // Get user ID (may be null for non-logged-in users)
        $user_id = is_user_logged_in() ? get_current_user_id() : null;
        
        // Log the button click
        $result = $this->log_button_click($product_id, $user_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => 'Button click logged successfully',
                'product_id' => $product_id,
                'user_id' => $user_id
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to log button click'));
        }
    }
    
    /**
     * Get analytics data via AJAX
     */
    public function get_analytics_data() {
        if (!wp_verify_nonce($_POST['nonce'], 'ai_virtual_fitting_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $period = sanitize_text_field($_POST['period'] ?? 'week');
        $data_type = sanitize_text_field($_POST['data_type'] ?? 'dashboard');
        
        switch ($data_type) {
            case 'dashboard':
                $data = $this->get_dashboard_analytics($period);
                break;
            case 'status':
                $data = $this->get_system_status();
                break;
            default:
                wp_send_json_error(array('message' => 'Invalid data type'));
                return;
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * Get total users count
     *
     * @return int Total users count
     */
    private function get_total_users_count() {
        global $wpdb;
        
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
    }
    
    /**
     * Get new users count for today
     *
     * @return int New users count
     */
    private function get_new_users_count_today() {
        global $wpdb;
        
        $today = current_time('Y-m-d');
        
        return (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->users} 
            WHERE DATE(user_registered) = %s
        ", $today));
    }
    
    /**
     * Clean up old analytics data
     */
    public function cleanup_old_analytics() {
        global $wpdb;
        
        $retention_days = AI_Virtual_Fitting_Core::get_option('analytics_retention_days', 365);
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        $events_table = $wpdb->prefix . self::EVENTS_TABLE;
        $metrics_table = $wpdb->prefix . self::METRICS_TABLE;
        
        // Clean up old events (keep analytics summary)
        $wpdb->query($wpdb->prepare("
            DELETE FROM $events_table 
            WHERE created_at < %s
        ", $cutoff_date));
        
        // Clean up old metrics
        $wpdb->query($wpdb->prepare("
            DELETE FROM $metrics_table 
            WHERE recorded_at < %s
        ", $cutoff_date));
        
        // Log cleanup
        if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
            error_log("AI Virtual Fitting: Analytics cleanup completed - removed data older than {$retention_days} days");
        }
    }
    
    /**
     * Get client IP address
     *
     * @return string Client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    }
    
    /**
     * Export analytics data
     *
     * @param string $format Export format (csv, json)
     * @param string $period Period to export
     * @return string|array Exported data
     */
    public function export_analytics($format = 'csv', $period = 'month') {
        $data = $this->get_dashboard_analytics($period);
        
        switch ($format) {
            case 'csv':
                return $this->export_to_csv($data);
            case 'json':
                return json_encode($data, JSON_PRETTY_PRINT);
            default:
                return $data;
        }
    }
    
    /**
     * Export data to CSV format
     *
     * @param array $data Analytics data
     * @return string CSV data
     */
    private function export_to_csv($data) {
        $csv = "Date,Total Fittings,Successful Fittings,Failed Fittings,Success Rate,Uploads,Downloads,Processing Time\n";
        
        foreach ($data['daily_data'] as $day) {
            $success_rate = $day->total_fittings > 0 ? 
                round(($day->successful_fittings / $day->total_fittings) * 100, 2) : 0;
            
            $csv .= sprintf(
                "%s,%d,%d,%d,%.2f%%,%d,%d,%.2fs\n",
                $day->date_recorded,
                $day->total_fittings,
                $day->successful_fittings,
                $day->failed_fittings,
                $success_rate,
                $day->total_uploads,
                $day->total_downloads,
                $day->avg_processing_time
            );
        }
        
        return $csv;
    }
}