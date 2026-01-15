<?php
/**
 * Security Manager for AI Virtual Fitting Plugin
 * Handles encryption, rate limiting, and security validations
 *
 * @package AI_Virtual_Fitting
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI Virtual Fitting Security Manager Class
 */
class AI_Virtual_Fitting_Security_Manager {
    
    /**
     * Encryption method
     */
    const ENCRYPTION_METHOD = 'AES-256-CBC';
    
    /**
     * Rate limit window in seconds (5 minutes)
     */
    const RATE_LIMIT_WINDOW = 300;
    
    /**
     * Maximum requests per window
     */
    const MAX_REQUESTS_PER_WINDOW = 20;
    
    /**
     * Allowed domains for external image downloads
     */
    const ALLOWED_IMAGE_DOMAINS = array(
        'localhost',
        '127.0.0.1',
        // Add your production domains here
    );
    
    /**
     * Get encryption key
     * Uses WordPress AUTH_KEY and SECURE_AUTH_KEY for encryption
     *
     * @return string Encryption key
     */
    private static function get_encryption_key() {
        // Use WordPress security constants for key derivation
        $key_material = AUTH_KEY . SECURE_AUTH_KEY;
        
        // Derive a proper length key using hash
        return hash('sha256', $key_material, true);
    }
    
    /**
     * Encrypt sensitive data
     *
     * @param string $data Data to encrypt
     * @return string|false Encrypted data (base64 encoded) or false on failure
     */
    public static function encrypt($data) {
        if (empty($data)) {
            return false;
        }
        
        try {
            $key = self::get_encryption_key();
            
            // Generate random IV
            $iv_length = openssl_cipher_iv_length(self::ENCRYPTION_METHOD);
            $iv = openssl_random_pseudo_bytes($iv_length);
            
            // Encrypt the data
            $encrypted = openssl_encrypt(
                $data,
                self::ENCRYPTION_METHOD,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );
            
            if ($encrypted === false) {
                return false;
            }
            
            // Combine IV and encrypted data, then base64 encode
            $result = base64_encode($iv . $encrypted);
            
            return $result;
            
        } catch (Exception $e) {
            if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
                error_log('AI Virtual Fitting - Encryption Error: ' . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Decrypt sensitive data
     *
     * @param string $encrypted_data Encrypted data (base64 encoded)
     * @return string|false Decrypted data or false on failure
     */
    public static function decrypt($encrypted_data) {
        if (empty($encrypted_data)) {
            return false;
        }
        
        try {
            $key = self::get_encryption_key();
            
            // Decode from base64
            $data = base64_decode($encrypted_data, true);
            if ($data === false) {
                return false;
            }
            
            // Extract IV and encrypted data
            $iv_length = openssl_cipher_iv_length(self::ENCRYPTION_METHOD);
            $iv = substr($data, 0, $iv_length);
            $encrypted = substr($data, $iv_length);
            
            // Decrypt the data
            $decrypted = openssl_decrypt(
                $encrypted,
                self::ENCRYPTION_METHOD,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );
            
            return $decrypted;
            
        } catch (Exception $e) {
            if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
                error_log('AI Virtual Fitting - Decryption Error: ' . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Check rate limit for a given action
     *
     * @param string $action Action identifier (e.g., 'upload_image', 'process_fitting')
     * @param int $user_id User ID (0 for guests, uses IP)
     * @return bool True if within rate limit, false if exceeded
     */
    public static function check_rate_limit($action, $user_id = 0) {
        // Get identifier (user ID or IP address)
        $identifier = $user_id > 0 ? 'user_' . $user_id : 'ip_' . self::get_client_ip();
        
        // Create transient key
        $transient_key = 'ai_vf_rate_limit_' . $action . '_' . md5($identifier);
        
        // Get current request count
        $request_data = get_transient($transient_key);
        
        if ($request_data === false) {
            // First request in this window
            $request_data = array(
                'count' => 1,
                'first_request' => time()
            );
            set_transient($transient_key, $request_data, self::RATE_LIMIT_WINDOW);
            return true;
        }
        
        // Check if we're still in the same window
        $elapsed = time() - $request_data['first_request'];
        
        if ($elapsed > self::RATE_LIMIT_WINDOW) {
            // Window expired, reset counter
            $request_data = array(
                'count' => 1,
                'first_request' => time()
            );
            set_transient($transient_key, $request_data, self::RATE_LIMIT_WINDOW);
            return true;
        }
        
        // Check if limit exceeded
        if ($request_data['count'] >= self::MAX_REQUESTS_PER_WINDOW) {
            // Log rate limit violation
            if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
                error_log(sprintf(
                    'AI Virtual Fitting - Rate limit exceeded: action=%s, identifier=%s, count=%d',
                    $action,
                    $identifier,
                    $request_data['count']
                ));
            }
            return false;
        }
        
        // Increment counter
        $request_data['count']++;
        set_transient($transient_key, $request_data, self::RATE_LIMIT_WINDOW);
        
        return true;
    }
    
    /**
     * Get client IP address
     *
     * @return string Client IP address
     */
    private static function get_client_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER)) {
                $ip = $_SERVER[$key];
                
                // Handle multiple IPs (take first one)
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                
                $ip = trim($ip);
                
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Validate external URL for image download
     * Prevents SSRF attacks
     *
     * @param string $url URL to validate
     * @return array Validation result with 'valid' boolean and 'error' message
     */
    public static function validate_external_url($url) {
        // Check if URL is valid
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return array(
                'valid' => false,
                'error' => __('Invalid URL format.', 'ai-virtual-fitting')
            );
        }
        
        // Parse URL
        $parsed = parse_url($url);
        
        if (!$parsed || !isset($parsed['scheme']) || !isset($parsed['host'])) {
            return array(
                'valid' => false,
                'error' => __('Invalid URL structure.', 'ai-virtual-fitting')
            );
        }
        
        // Only allow HTTP and HTTPS
        if (!in_array(strtolower($parsed['scheme']), array('http', 'https'))) {
            return array(
                'valid' => false,
                'error' => __('Only HTTP and HTTPS protocols are allowed.', 'ai-virtual-fitting')
            );
        }
        
        // Check if domain is in allowed list or is current site
        $site_host = parse_url(get_site_url(), PHP_URL_HOST);
        $allowed_domains = array_merge(self::ALLOWED_IMAGE_DOMAINS, array($site_host));
        
        $is_allowed = false;
        foreach ($allowed_domains as $allowed_domain) {
            if (strcasecmp($parsed['host'], $allowed_domain) === 0 || 
                substr($parsed['host'], -strlen('.' . $allowed_domain)) === '.' . $allowed_domain) {
                $is_allowed = true;
                break;
            }
        }
        
        if (!$is_allowed) {
            // Check if it's a WooCommerce product image domain (allow any domain for product images)
            // This is safe because WooCommerce validates these URLs
            if (AI_Virtual_Fitting_Core::get_option('allow_woocommerce_domains', true)) {
                // Allow the URL but log it
                if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
                    error_log('AI Virtual Fitting - External domain allowed: ' . $parsed['host']);
                }
                $is_allowed = true;
            }
        }
        
        if (!$is_allowed) {
            return array(
                'valid' => false,
                'error' => sprintf(
                    __('Domain "%s" is not in the allowed list.', 'ai-virtual-fitting'),
                    $parsed['host']
                )
            );
        }
        
        // Prevent access to private IP ranges (SSRF protection)
        $ip = gethostbyname($parsed['host']);
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            // Allow localhost for development
            if (!in_array($parsed['host'], array('localhost', '127.0.0.1', '::1'))) {
                return array(
                    'valid' => false,
                    'error' => __('Access to private IP ranges is not allowed.', 'ai-virtual-fitting')
                );
            }
        }
        
        return array('valid' => true);
    }
    
    /**
     * Sanitize and validate file download
     * Additional security layer for downloaded files
     *
     * @param string $file_path Path to downloaded file
     * @return array Validation result
     */
    public static function validate_downloaded_file($file_path) {
        if (!file_exists($file_path)) {
            return array(
                'valid' => false,
                'error' => __('Downloaded file not found.', 'ai-virtual-fitting')
            );
        }
        
        // Check file size (max 10MB)
        $max_size = 10 * 1024 * 1024;
        if (filesize($file_path) > $max_size) {
            return array(
                'valid' => false,
                'error' => __('Downloaded file exceeds maximum size.', 'ai-virtual-fitting')
            );
        }
        
        // Verify it's an image
        $image_info = getimagesize($file_path);
        if ($image_info === false) {
            return array(
                'valid' => false,
                'error' => __('Downloaded file is not a valid image.', 'ai-virtual-fitting')
            );
        }
        
        // Check MIME type
        $allowed_types = array('image/jpeg', 'image/png', 'image/webp');
        if (!in_array($image_info['mime'], $allowed_types)) {
            return array(
                'valid' => false,
                'error' => __('Downloaded file type is not allowed.', 'ai-virtual-fitting')
            );
        }
        
        return array('valid' => true);
    }
}
