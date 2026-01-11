<?php
/**
 * Performance Management functionality for AI Virtual Fitting Plugin
 *
 * @package AI_Virtual_Fitting
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI Virtual Fitting Performance Manager Class
 */
class AI_Virtual_Fitting_Performance_Manager {
    
    /**
     * Cache group for virtual fitting
     */
    const CACHE_GROUP = 'ai_virtual_fitting';
    
    /**
     * Default cache expiration (24 hours)
     */
    const DEFAULT_CACHE_EXPIRATION = 86400;
    
    /**
     * Queue option name
     */
    const QUEUE_OPTION = 'ai_virtual_fitting_queue';
    
    /**
     * Processing status option name
     */
    const PROCESSING_STATUS_OPTION = 'ai_virtual_fitting_processing_status';
    
    /**
     * Maximum concurrent processes
     */
    const MAX_CONCURRENT_PROCESSES = 3;
    
    /**
     * Queue processing interval (seconds)
     */
    const QUEUE_PROCESSING_INTERVAL = 30;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
        $this->init_caching();
        $this->init_queue_processing();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Schedule queue processing
        add_action('ai_virtual_fitting_process_queue', array($this, 'process_queue'));
        
        // AJAX handlers for asynchronous processing
        add_action('wp_ajax_ai_virtual_fitting_check_status', array($this, 'check_processing_status'));
        add_action('wp_ajax_ai_virtual_fitting_queue_request', array($this, 'handle_queue_fitting_request'));
        
        // Cleanup hooks
        add_action('ai_virtual_fitting_cleanup_cache', array($this, 'cleanup_expired_cache'));
        add_action('ai_virtual_fitting_cleanup_queue', array($this, 'cleanup_old_queue_items'));
        
        // Performance monitoring hooks
        add_action('wp_footer', array($this, 'add_performance_monitoring'));
    }
    
    /**
     * Initialize caching system
     */
    private function init_caching() {
        // Set up object cache if not available
        if (!wp_using_ext_object_cache()) {
            // Use transients as fallback
            add_filter('ai_virtual_fitting_cache_get', array($this, 'transient_cache_get'), 10, 2);
            add_filter('ai_virtual_fitting_cache_set', array($this, 'transient_cache_set'), 10, 4);
            add_filter('ai_virtual_fitting_cache_delete', array($this, 'transient_cache_delete'), 10, 2);
        }
        
        // Schedule cache cleanup
        if (!wp_next_scheduled('ai_virtual_fitting_cleanup_cache')) {
            wp_schedule_event(time(), 'hourly', 'ai_virtual_fitting_cleanup_cache');
        }
    }
    
    /**
     * Initialize queue processing
     */
    private function init_queue_processing() {
        // Schedule queue processing if not already scheduled
        if (!wp_next_scheduled('ai_virtual_fitting_process_queue')) {
            wp_schedule_event(time(), 'ai_virtual_fitting_queue_interval', 'ai_virtual_fitting_process_queue');
        }
        
        // Add custom cron interval
        add_filter('cron_schedules', array($this, 'add_cron_intervals'));
        
        // Schedule queue cleanup
        if (!wp_next_scheduled('ai_virtual_fitting_cleanup_queue')) {
            wp_schedule_event(time(), 'daily', 'ai_virtual_fitting_cleanup_queue');
        }
    }
    
    /**
     * Add custom cron intervals
     *
     * @param array $schedules Existing schedules
     * @return array Modified schedules
     */
    public function add_cron_intervals($schedules) {
        $schedules['ai_virtual_fitting_queue_interval'] = array(
            'interval' => self::QUEUE_PROCESSING_INTERVAL,
            'display' => __('AI Virtual Fitting Queue Processing', 'ai-virtual-fitting')
        );
        
        return $schedules;
    }
    
    /**
     * Cache product images for faster access
     *
     * @param int $product_id Product ID
     * @return array Cached product images
     */
    public function cache_product_images($product_id) {
        $cache_key = "product_images_{$product_id}";
        
        // Try to get from cache first
        $cached_images = $this->cache_get($cache_key);
        if ($cached_images !== false) {
            return $cached_images;
        }
        
        // Get product images
        $product = wc_get_product($product_id);
        if (!$product) {
            return array();
        }
        
        $images = array();
        
        // Get featured image
        $featured_image_id = $product->get_image_id();
        if ($featured_image_id) {
            $image_data = $this->get_optimized_image_data($featured_image_id);
            if ($image_data) {
                $images[] = $image_data;
            }
        }
        
        // Get gallery images
        $gallery_image_ids = $product->get_gallery_image_ids();
        foreach ($gallery_image_ids as $image_id) {
            $image_data = $this->get_optimized_image_data($image_id);
            if ($image_data) {
                $images[] = $image_data;
            }
            
            // Stop when we have 4 images total
            if (count($images) >= 4) {
                break;
            }
        }
        
        // Cache the images
        $this->cache_set($cache_key, $images, self::DEFAULT_CACHE_EXPIRATION);
        
        return $images;
    }
    
    /**
     * Get optimized image data for AI processing
     *
     * @param int $image_id WordPress attachment ID
     * @return array|false Image data or false on failure
     */
    private function get_optimized_image_data($image_id) {
        $cache_key = "optimized_image_{$image_id}";
        
        // Try cache first
        $cached_data = $this->cache_get($cache_key);
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $image_path = get_attached_file($image_id);
        if (!$image_path || !file_exists($image_path)) {
            return false;
        }
        
        // Optimize image for AI processing
        $optimized_path = $this->optimize_image_for_ai($image_path);
        if (!$optimized_path) {
            return false;
        }
        
        $image_data = array(
            'id' => $image_id,
            'path' => $optimized_path,
            'url' => wp_get_attachment_url($image_id),
            'mime_type' => get_post_mime_type($image_id),
            'optimized' => true,
            'cached_at' => time()
        );
        
        // Cache for 6 hours
        $this->cache_set($cache_key, $image_data, 21600);
        
        return $image_data;
    }
    
    /**
     * Optimize image for AI processing
     *
     * @param string $image_path Original image path
     * @return string|false Optimized image path or false on failure
     */
    private function optimize_image_for_ai($image_path) {
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/ai-virtual-fitting-cache';
        
        if (!file_exists($cache_dir)) {
            wp_mkdir_p($cache_dir);
        }
        
        $filename = basename($image_path);
        $optimized_path = $cache_dir . '/optimized_' . $filename;
        
        // Check if optimized version already exists
        if (file_exists($optimized_path)) {
            return $optimized_path;
        }
        
        try {
            // Get image info
            $image_info = getimagesize($image_path);
            if (!$image_info) {
                return false;
            }
            
            $width = $image_info[0];
            $height = $image_info[1];
            $mime_type = $image_info['mime'];
            
            // Create image resource
            switch ($mime_type) {
                case 'image/jpeg':
                    $source = imagecreatefromjpeg($image_path);
                    break;
                case 'image/png':
                    $source = imagecreatefrompng($image_path);
                    break;
                case 'image/webp':
                    $source = imagecreatefromwebp($image_path);
                    break;
                default:
                    return false;
            }
            
            if (!$source) {
                return false;
            }
            
            // Calculate optimal dimensions (max 1024x1024 for AI processing)
            $max_dimension = 1024;
            if ($width > $max_dimension || $height > $max_dimension) {
                if ($width > $height) {
                    $new_width = $max_dimension;
                    $new_height = intval(($height * $max_dimension) / $width);
                } else {
                    $new_height = $max_dimension;
                    $new_width = intval(($width * $max_dimension) / $height);
                }
            } else {
                $new_width = $width;
                $new_height = $height;
            }
            
            // Create optimized image
            $optimized = imagecreatetruecolor($new_width, $new_height);
            
            // Preserve transparency for PNG
            if ($mime_type === 'image/png') {
                imagealphablending($optimized, false);
                imagesavealpha($optimized, true);
                $transparent = imagecolorallocatealpha($optimized, 255, 255, 255, 127);
                imagefill($optimized, 0, 0, $transparent);
            }
            
            // Resize image
            imagecopyresampled($optimized, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            
            // Save optimized image as JPEG (best for AI processing)
            $success = imagejpeg($optimized, $optimized_path, 85);
            
            // Clean up
            imagedestroy($source);
            imagedestroy($optimized);
            
            if ($success) {
                chmod($optimized_path, 0644);
                return $optimized_path;
            }
            
        } catch (Exception $e) {
            error_log('AI Virtual Fitting - Image optimization failed: ' . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Queue a virtual fitting request for asynchronous processing
     *
     * @param int $user_id User ID
     * @param string $customer_image_path Customer image path
     * @param int $product_id Product ID
     * @return string Queue ID
     */
    public function queue_fitting_request($user_id, $customer_image_path, $product_id) {
        $queue_id = uniqid('vf_', true);
        
        $queue_item = array(
            'id' => $queue_id,
            'user_id' => $user_id,
            'customer_image_path' => $customer_image_path,
            'product_id' => $product_id,
            'status' => 'queued',
            'queued_at' => time(),
            'attempts' => 0,
            'max_attempts' => 3,
            'priority' => $this->calculate_priority($user_id)
        );
        
        // Add to queue
        $queue = get_option(self::QUEUE_OPTION, array());
        $queue[$queue_id] = $queue_item;
        update_option(self::QUEUE_OPTION, $queue);
        
        // Set initial processing status
        $this->set_processing_status($queue_id, 'queued', array(
            'position' => $this->get_queue_position($queue_id),
            'estimated_wait' => $this->estimate_wait_time()
        ));
        
        return $queue_id;
    }
    
    /**
     * Process the virtual fitting queue
     */
    public function process_queue() {
        $queue = get_option(self::QUEUE_OPTION, array());
        
        if (empty($queue)) {
            return;
        }
        
        // Get currently processing items
        $processing_count = $this->get_processing_count();
        
        if ($processing_count >= self::MAX_CONCURRENT_PROCESSES) {
            return; // Too many concurrent processes
        }
        
        // Sort queue by priority and queue time
        uasort($queue, array($this, 'sort_queue_items'));
        
        $processed = 0;
        $max_to_process = self::MAX_CONCURRENT_PROCESSES - $processing_count;
        
        foreach ($queue as $queue_id => $item) {
            if ($processed >= $max_to_process) {
                break;
            }
            
            if ($item['status'] === 'queued') {
                $this->process_queue_item($queue_id, $item);
                $processed++;
            }
        }
    }
    
    /**
     * Process a single queue item
     *
     * @param string $queue_id Queue item ID
     * @param array $item Queue item data
     */
    private function process_queue_item($queue_id, $item) {
        // Update status to processing
        $this->update_queue_item_status($queue_id, 'processing');
        $this->set_processing_status($queue_id, 'processing', array(
            'started_at' => time()
        ));
        
        try {
            // Check if user still has credits
            $credit_manager = new AI_Virtual_Fitting_Credit_Manager();
            $credits = $credit_manager->get_customer_credits($item['user_id']);
            
            if ($credits <= 0) {
                $this->update_queue_item_status($queue_id, 'failed', 'Insufficient credits');
                $this->set_processing_status($queue_id, 'failed', array(
                    'error' => 'Insufficient credits'
                ));
                return;
            }
            
            // Get cached product images
            $product_images = $this->cache_product_images($item['product_id']);
            
            if (count($product_images) < 4) {
                $this->update_queue_item_status($queue_id, 'failed', 'Insufficient product images');
                $this->set_processing_status($queue_id, 'failed', array(
                    'error' => 'Product must have 4 images'
                ));
                return;
            }
            
            // Process virtual fitting
            $image_processor = new AI_Virtual_Fitting_Image_Processor();
            $product_image_paths = array_column($product_images, 'path');
            
            $result = $image_processor->process_virtual_fitting(
                $item['customer_image_path'],
                $product_image_paths
            );
            
            if ($result['success']) {
                // Deduct credit
                $credit_manager->deduct_credit($item['user_id']);
                
                // Update status to completed
                $this->update_queue_item_status($queue_id, 'completed');
                $this->set_processing_status($queue_id, 'completed', array(
                    'result_image_url' => $result['result_image_url'],
                    'completed_at' => time()
                ));
                
                // Cache the result
                $this->cache_set("fitting_result_{$queue_id}", $result, 3600); // 1 hour
                
            } else {
                // Increment attempts
                $item['attempts']++;
                
                if ($item['attempts'] >= $item['max_attempts']) {
                    $this->update_queue_item_status($queue_id, 'failed', $result['error']);
                    $this->set_processing_status($queue_id, 'failed', array(
                        'error' => $result['error']
                    ));
                } else {
                    // Retry later
                    $this->update_queue_item_status($queue_id, 'queued');
                    $this->set_processing_status($queue_id, 'queued', array(
                        'retry_attempt' => $item['attempts'],
                        'retry_at' => time() + (60 * $item['attempts']) // Exponential backoff
                    ));
                }
            }
            
        } catch (Exception $e) {
            $this->update_queue_item_status($queue_id, 'failed', $e->getMessage());
            $this->set_processing_status($queue_id, 'failed', array(
                'error' => $e->getMessage()
            ));
            
            error_log('AI Virtual Fitting - Queue processing error: ' . $e->getMessage());
        }
    }
    
    /**
     * Calculate priority for queue item
     *
     * @param int $user_id User ID
     * @return int Priority (higher = more priority)
     */
    private function calculate_priority($user_id) {
        // Premium users get higher priority
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return 1;
        }
        
        // Check if user has purchased credits recently (premium user)
        $credit_manager = new AI_Virtual_Fitting_Credit_Manager();
        $total_purchased = $credit_manager->get_total_credits_purchased($user_id);
        
        if ($total_purchased > 20) {
            return 3; // High priority
        } elseif ($total_purchased > 0) {
            return 2; // Medium priority
        }
        
        return 1; // Normal priority
    }
    
    /**
     * Sort queue items by priority and queue time
     *
     * @param array $a First item
     * @param array $b Second item
     * @return int Comparison result
     */
    private function sort_queue_items($a, $b) {
        // First sort by priority (higher priority first)
        if ($a['priority'] !== $b['priority']) {
            return $b['priority'] - $a['priority'];
        }
        
        // Then sort by queue time (earlier first)
        return $a['queued_at'] - $b['queued_at'];
    }
    
    /**
     * Get number of currently processing items
     *
     * @return int Processing count
     */
    private function get_processing_count() {
        $queue = get_option(self::QUEUE_OPTION, array());
        $processing_count = 0;
        
        foreach ($queue as $item) {
            if ($item['status'] === 'processing') {
                $processing_count++;
            }
        }
        
        return $processing_count;
    }
    
    /**
     * Get queue position for an item
     *
     * @param string $queue_id Queue item ID
     * @return int Position in queue
     */
    private function get_queue_position($queue_id) {
        $queue = get_option(self::QUEUE_OPTION, array());
        
        // Sort queue by priority and queue time
        uasort($queue, array($this, 'sort_queue_items'));
        
        $position = 1;
        foreach ($queue as $id => $item) {
            if ($id === $queue_id) {
                return $position;
            }
            if ($item['status'] === 'queued') {
                $position++;
            }
        }
        
        return $position;
    }
    
    /**
     * Estimate wait time for queue processing
     *
     * @return int Estimated wait time in seconds
     */
    private function estimate_wait_time() {
        $queue = get_option(self::QUEUE_OPTION, array());
        $queued_count = 0;
        $processing_count = 0;
        
        foreach ($queue as $item) {
            if ($item['status'] === 'queued') {
                $queued_count++;
            } elseif ($item['status'] === 'processing') {
                $processing_count++;
            }
        }
        
        // Average processing time: 60 seconds per item
        $avg_processing_time = 60;
        $concurrent_processes = min(self::MAX_CONCURRENT_PROCESSES, max(1, $processing_count));
        
        return intval(($queued_count * $avg_processing_time) / $concurrent_processes);
    }
    
    /**
     * Update queue item status
     *
     * @param string $queue_id Queue item ID
     * @param string $status New status
     * @param string $error_message Optional error message
     */
    private function update_queue_item_status($queue_id, $status, $error_message = '') {
        $queue = get_option(self::QUEUE_OPTION, array());
        
        if (isset($queue[$queue_id])) {
            $queue[$queue_id]['status'] = $status;
            $queue[$queue_id]['updated_at'] = time();
            
            if (!empty($error_message)) {
                $queue[$queue_id]['error'] = $error_message;
            }
            
            update_option(self::QUEUE_OPTION, $queue);
        }
    }
    
    /**
     * Set processing status for frontend polling
     *
     * @param string $queue_id Queue item ID
     * @param string $status Status
     * @param array $data Additional status data
     */
    private function set_processing_status($queue_id, $status, $data = array()) {
        $status_data = array_merge(array(
            'status' => $status,
            'updated_at' => time()
        ), $data);
        
        $this->cache_set("status_{$queue_id}", $status_data, 3600); // 1 hour
    }
    
    /**
     * AJAX handler to check processing status
     */
    public function check_processing_status() {
        if (!wp_verify_nonce($_POST['nonce'], 'ai_virtual_fitting_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Authentication required'));
        }
        
        $queue_id = sanitize_text_field($_POST['queue_id']);
        
        if (empty($queue_id)) {
            wp_send_json_error(array('message' => 'Queue ID required'));
        }
        
        $status = $this->cache_get("status_{$queue_id}");
        
        if ($status === false) {
            wp_send_json_error(array('message' => 'Status not found'));
        }
        
        wp_send_json_success($status);
    }
    
    /**
     * AJAX handler to queue fitting request
     */
    public function handle_queue_fitting_request() {
        if (!wp_verify_nonce($_POST['nonce'], 'ai_virtual_fitting_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Authentication required'));
        }
        
        $user_id = get_current_user_id();
        $customer_image = sanitize_text_field($_POST['customer_image']);
        $product_id = intval($_POST['product_id']);
        
        if (empty($customer_image) || empty($product_id)) {
            wp_send_json_error(array('message' => 'Missing required parameters'));
        }
        
        // Get customer image path
        $image_processor = new AI_Virtual_Fitting_Image_Processor();
        $temp_dir = wp_upload_dir()['basedir'] . '/ai-virtual-fitting-temp';
        $customer_image_path = $temp_dir . '/' . $customer_image;
        
        if (!file_exists($customer_image_path)) {
            wp_send_json_error(array('message' => 'Customer image not found'));
        }
        
        // Queue the request
        $queue_id = $this->queue_fitting_request($user_id, $customer_image_path, $product_id);
        
        wp_send_json_success(array(
            'queue_id' => $queue_id,
            'message' => 'Request queued for processing'
        ));
    }
    
    /**
     * Cleanup expired cache entries
     */
    public function cleanup_expired_cache() {
        // Clean up optimized images older than 7 days
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/ai-virtual-fitting-cache';
        
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '/optimized_*');
            $max_age = 7 * 24 * 3600; // 7 days
            $current_time = time();
            
            foreach ($files as $file) {
                if (is_file($file)) {
                    $file_age = $current_time - filemtime($file);
                    if ($file_age > $max_age) {
                        unlink($file);
                    }
                }
            }
        }
        
        // Clean up temporary files older than 24 hours
        $temp_dir = $upload_dir['basedir'] . '/ai-virtual-fitting-temp';
        
        if (is_dir($temp_dir)) {
            $files = glob($temp_dir . '/*');
            $max_age = 24 * 3600; // 24 hours
            $current_time = time();
            
            foreach ($files as $file) {
                if (is_file($file)) {
                    $file_age = $current_time - filemtime($file);
                    if ($file_age > $max_age) {
                        unlink($file);
                    }
                }
            }
        }
    }
    
    /**
     * Cleanup old queue items
     */
    public function cleanup_old_queue_items() {
        $queue = get_option(self::QUEUE_OPTION, array());
        $max_age = 24 * 3600; // 24 hours
        $current_time = time();
        $cleaned = false;
        
        foreach ($queue as $queue_id => $item) {
            $item_age = $current_time - $item['queued_at'];
            
            // Remove completed, failed, or very old items
            if ($item['status'] === 'completed' || 
                $item['status'] === 'failed' || 
                $item_age > $max_age) {
                
                unset($queue[$queue_id]);
                $cleaned = true;
                
                // Clean up status cache
                $this->cache_delete("status_{$queue_id}");
            }
        }
        
        if ($cleaned) {
            update_option(self::QUEUE_OPTION, $queue);
        }
    }
    
    /**
     * Add performance monitoring to frontend
     */
    public function add_performance_monitoring() {
        if (!is_page() || !$this->is_virtual_fitting_page()) {
            return;
        }
        
        ?>
        <script>
        // Performance monitoring for virtual fitting
        (function() {
            var startTime = performance.now();
            var metrics = {
                pageLoad: 0,
                imageUpload: 0,
                processingTime: 0,
                queueWait: 0
            };
            
            // Track page load time
            window.addEventListener('load', function() {
                metrics.pageLoad = performance.now() - startTime;
                console.log('AI Virtual Fitting - Page load time:', metrics.pageLoad + 'ms');
            });
            
            // Track image upload time
            window.aiVirtualFittingMetrics = metrics;
            
            // Send metrics to server periodically
            setInterval(function() {
                if (Object.values(metrics).some(v => v > 0)) {
                    fetch(ajaxurl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'ai_virtual_fitting_track_metrics',
                            nonce: ai_virtual_fitting_nonce,
                            metrics: JSON.stringify(metrics)
                        })
                    });
                }
            }, 30000); // Every 30 seconds
        })();
        </script>
        <?php
    }
    
    /**
     * Check if current page is virtual fitting page
     *
     * @return bool
     */
    private function is_virtual_fitting_page() {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        // Check if page contains virtual fitting shortcode or is virtual fitting page
        return has_shortcode($post->post_content, 'ai_virtual_fitting') || 
               strpos($post->post_content, 'virtual-fitting') !== false;
    }
    
    /**
     * Get cache value
     *
     * @param string $key Cache key
     * @return mixed Cache value or false if not found
     */
    private function cache_get($key) {
        if (wp_using_ext_object_cache()) {
            return wp_cache_get($key, self::CACHE_GROUP);
        } else {
            return apply_filters('ai_virtual_fitting_cache_get', false, $key);
        }
    }
    
    /**
     * Set cache value
     *
     * @param string $key Cache key
     * @param mixed $value Cache value
     * @param int $expiration Expiration time in seconds
     * @return bool Success
     */
    private function cache_set($key, $value, $expiration = self::DEFAULT_CACHE_EXPIRATION) {
        if (wp_using_ext_object_cache()) {
            return wp_cache_set($key, $value, self::CACHE_GROUP, $expiration);
        } else {
            return apply_filters('ai_virtual_fitting_cache_set', false, $key, $value, $expiration);
        }
    }
    
    /**
     * Delete cache value
     *
     * @param string $key Cache key
     * @return bool Success
     */
    private function cache_delete($key) {
        if (wp_using_ext_object_cache()) {
            return wp_cache_delete($key, self::CACHE_GROUP);
        } else {
            return apply_filters('ai_virtual_fitting_cache_delete', false, $key);
        }
    }
    
    /**
     * Fallback cache get using transients
     *
     * @param bool $result Current result
     * @param string $key Cache key
     * @return mixed Cache value
     */
    public function transient_cache_get($result, $key) {
        return get_transient(self::CACHE_GROUP . '_' . $key);
    }
    
    /**
     * Fallback cache set using transients
     *
     * @param bool $result Current result
     * @param string $key Cache key
     * @param mixed $value Cache value
     * @param int $expiration Expiration time
     * @return bool Success
     */
    public function transient_cache_set($result, $key, $value, $expiration) {
        return set_transient(self::CACHE_GROUP . '_' . $key, $value, $expiration);
    }
    
    /**
     * Fallback cache delete using transients
     *
     * @param bool $result Current result
     * @param string $key Cache key
     * @return bool Success
     */
    public function transient_cache_delete($result, $key) {
        return delete_transient(self::CACHE_GROUP . '_' . $key);
    }
    
    /**
     * Get performance metrics
     *
     * @return array Performance metrics
     */
    public function get_performance_metrics() {
        $queue = get_option(self::QUEUE_OPTION, array());
        
        $metrics = array(
            'queue_length' => 0,
            'processing_count' => 0,
            'completed_today' => 0,
            'failed_today' => 0,
            'average_processing_time' => 0,
            'cache_hit_rate' => 0
        );
        
        $today_start = strtotime('today');
        $processing_times = array();
        
        foreach ($queue as $item) {
            if ($item['status'] === 'queued') {
                $metrics['queue_length']++;
            } elseif ($item['status'] === 'processing') {
                $metrics['processing_count']++;
            } elseif ($item['status'] === 'completed' && $item['queued_at'] >= $today_start) {
                $metrics['completed_today']++;
                
                if (isset($item['updated_at'])) {
                    $processing_times[] = $item['updated_at'] - $item['queued_at'];
                }
            } elseif ($item['status'] === 'failed' && $item['queued_at'] >= $today_start) {
                $metrics['failed_today']++;
            }
        }
        
        if (!empty($processing_times)) {
            $metrics['average_processing_time'] = array_sum($processing_times) / count($processing_times);
        }
        
        return $metrics;
    }
}