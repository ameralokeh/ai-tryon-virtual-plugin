<?php
/**
 * Plugin Configuration Constants
 * Centralized configuration for AI Virtual Fitting Plugin
 *
 * @package AI_Virtual_Fitting
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI Virtual Fitting Plugin Configuration Class
 * 
 * Centralizes all configuration values, constants, and defaults
 * to avoid duplication and ensure consistency across the plugin.
 */
class AI_Virtual_Fitting_Plugin_Config {
    
    /**
     * Plugin version
     */
    const VERSION = '1.0.0';
    
    /**
     * Database table names (without prefix)
     */
    const TABLE_CREDITS = 'virtual_fitting_credits';
    const TABLE_ANALYTICS = 'virtual_fitting_analytics';
    const TABLE_SESSIONS = 'virtual_fitting_sessions';
    
    /**
     * Meta keys for WooCommerce
     */
    const META_VIRTUAL_FITTING_CREDITS = '_virtual_fitting_credits';
    const META_IS_CREDIT_PRODUCT = '_is_virtual_fitting_credit_product';
    const META_CREDIT_AMOUNT = '_virtual_fitting_credit_amount';
    
    /**
     * Option keys
     */
    const OPTION_API_PROVIDER = 'ai_virtual_fitting_api_provider';
    const OPTION_GOOGLE_AI_API_KEY = 'ai_virtual_fitting_google_ai_api_key';
    const OPTION_VERTEX_CREDENTIALS = 'ai_virtual_fitting_vertex_credentials';
    const OPTION_INITIAL_CREDITS = 'ai_virtual_fitting_initial_credits';
    const OPTION_CREDITS_PER_PACKAGE = 'ai_virtual_fitting_credits_per_package';
    const OPTION_CREDITS_PACKAGE_PRICE = 'ai_virtual_fitting_credits_package_price';
    const OPTION_CREDIT_PRODUCT_ID = 'ai_virtual_fitting_credit_product_id';
    const OPTION_MAX_IMAGE_SIZE = 'ai_virtual_fitting_max_image_size';
    const OPTION_API_RETRY_ATTEMPTS = 'ai_virtual_fitting_api_retry_attempts';
    const OPTION_ENABLE_LOGGING = 'ai_virtual_fitting_enable_logging';
    const OPTION_ENABLE_ANALYTICS = 'ai_virtual_fitting_enable_analytics';
    const OPTION_TEMP_FILE_CLEANUP_HOURS = 'ai_virtual_fitting_temp_file_cleanup_hours';
    const OPTION_API_TIMEOUT = 'ai_virtual_fitting_api_timeout';
    const OPTION_REQUIRE_LOGIN = 'ai_virtual_fitting_require_login';
    const OPTION_ALLOWED_USER_ROLES = 'ai_virtual_fitting_allowed_user_roles';
    const OPTION_ENABLE_EMAIL_NOTIFICATIONS = 'ai_virtual_fitting_enable_email_notifications';
    const OPTION_ADMIN_EMAIL_NOTIFICATIONS = 'ai_virtual_fitting_admin_email_notifications';
    const OPTION_AI_PROMPT_TEMPLATE = 'ai_virtual_fitting_ai_prompt_template';
    const OPTION_GEMINI_TEXT_API_ENDPOINT = 'ai_virtual_fitting_gemini_text_api_endpoint';
    const OPTION_GEMINI_IMAGE_API_ENDPOINT = 'ai_virtual_fitting_gemini_image_api_endpoint';
    const OPTION_ALLOW_WOOCOMMERCE_DOMAINS = 'ai_virtual_fitting_allow_woocommerce_domains';
    
    /**
     * AJAX action names
     */
    const AJAX_UPLOAD_IMAGE = 'ai_virtual_fitting_upload';
    const AJAX_PROCESS_FITTING = 'ai_virtual_fitting_process';
    const AJAX_DOWNLOAD_RESULT = 'ai_virtual_fitting_download';
    const AJAX_GET_PRODUCTS = 'ai_virtual_fitting_get_products';
    const AJAX_CHECK_CREDITS = 'ai_virtual_fitting_check_credits';
    const AJAX_TEST_API = 'ai_virtual_fitting_test_api';
    const AJAX_GET_ANALYTICS = 'ai_virtual_fitting_get_analytics';
    const AJAX_GET_USER_CREDITS = 'ai_virtual_fitting_get_user_credits';
    const AJAX_UPDATE_USER_CREDITS = 'ai_virtual_fitting_update_user_credits';
    const AJAX_ADD_CREDITS_TO_CART = 'ai_virtual_fitting_add_credits_to_cart';
    const AJAX_CLEAR_CART = 'ai_virtual_fitting_clear_cart';
    const AJAX_LOAD_CHECKOUT = 'ai_virtual_fitting_load_checkout';
    const AJAX_PROCESS_CHECKOUT = 'ai_virtual_fitting_process_checkout';
    const AJAX_REFRESH_CREDITS = 'ai_virtual_fitting_refresh_credits';
    const AJAX_CALCULATE_FEES = 'ai_virtual_fitting_calculate_fees';
    
    /**
     * Image processing constants
     */
    const MAX_FILE_SIZE = 10485760; // 10MB in bytes
    const MIN_IMAGE_WIDTH = 512;
    const MIN_IMAGE_HEIGHT = 512;
    const MAX_IMAGE_WIDTH = 2048;
    const MAX_IMAGE_HEIGHT = 2048;
    
    /**
     * Allowed MIME types for images
     */
    const ALLOWED_MIME_TYPES = array(
        'image/jpeg',
        'image/png',
        'image/webp'
    );
    
    /**
     * Allowed file extensions
     */
    const ALLOWED_EXTENSIONS = array(
        'jpg',
        'jpeg',
        'png',
        'webp'
    );
    
    /**
     * Default values
     */
    const DEFAULT_INITIAL_CREDITS = 2;
    const DEFAULT_CREDITS_PER_PACKAGE = 20;
    const DEFAULT_CREDITS_PACKAGE_PRICE = 10.00;
    const DEFAULT_MAX_IMAGE_SIZE = 10485760; // 10MB
    const DEFAULT_API_RETRY_ATTEMPTS = 3;
    const DEFAULT_TEMP_FILE_CLEANUP_HOURS = 24;
    const DEFAULT_API_TIMEOUT = 60;
    const DEFAULT_API_PROVIDER = 'google_ai_studio';
    
    /**
     * API endpoints
     */
    const DEFAULT_GEMINI_TEXT_API_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
    const DEFAULT_GEMINI_IMAGE_API_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3-pro-image-preview:generateContent';
    
    /**
     * Session status values
     */
    const SESSION_STATUS_PENDING = 'pending';
    const SESSION_STATUS_PROCESSING = 'processing';
    const SESSION_STATUS_COMPLETED = 'completed';
    const SESSION_STATUS_FAILED = 'failed';
    const SESSION_STATUS_CANCELLED = 'cancelled';
    
    /**
     * Credit transaction types
     */
    const TRANSACTION_TYPE_INITIAL = 'initial';
    const TRANSACTION_TYPE_PURCHASE = 'purchase';
    const TRANSACTION_TYPE_DEDUCTION = 'deduction';
    const TRANSACTION_TYPE_REFUND = 'refund';
    const TRANSACTION_TYPE_ADMIN_ADJUSTMENT = 'admin_adjustment';
    
    /**
     * User roles allowed by default
     */
    const DEFAULT_ALLOWED_ROLES = array(
        'customer',
        'subscriber',
        'administrator'
    );
    
    /**
     * Text domain for internationalization
     */
    const TEXT_DOMAIN = 'ai-virtual-fitting';
    
    /**
     * Nonce names
     */
    const NONCE_AJAX = 'ai_virtual_fitting_nonce';
    const NONCE_ADMIN = 'ai_virtual_fitting_admin_nonce';
    const NONCE_CHECKOUT = 'ai_virtual_fitting_checkout_nonce';
    
    /**
     * Cache keys
     */
    const CACHE_PRODUCTS = 'ai_vf_products';
    const CACHE_ANALYTICS = 'ai_vf_analytics';
    const CACHE_USER_CREDITS = 'ai_vf_user_credits_';
    const CACHE_API_RESPONSE = 'ai_vf_api_response_';
    
    /**
     * Cache expiration times (in seconds)
     */
    const CACHE_PRODUCTS_EXPIRATION = 3600; // 1 hour
    const CACHE_ANALYTICS_EXPIRATION = 300; // 5 minutes
    const CACHE_USER_CREDITS_EXPIRATION = 60; // 1 minute
    const CACHE_API_RESPONSE_EXPIRATION = 86400; // 24 hours
    
    /**
     * File size constants
     */
    const SIZE_1MB = 1048576;
    const SIZE_5MB = 5242880;
    const SIZE_10MB = 10485760;
    const SIZE_50MB = 52428800;
    
    /**
     * Get full table name with WordPress prefix
     *
     * @param string $table_constant Table constant name
     * @return string Full table name with prefix
     */
    public static function get_table_name($table_constant) {
        global $wpdb;
        
        switch ($table_constant) {
            case 'TABLE_CREDITS':
                return $wpdb->prefix . self::TABLE_CREDITS;
            case 'TABLE_ANALYTICS':
                return $wpdb->prefix . self::TABLE_ANALYTICS;
            case 'TABLE_SESSIONS':
                return $wpdb->prefix . self::TABLE_SESSIONS;
            default:
                return '';
        }
    }
    
    /**
     * Get default AI prompt template
     *
     * @return string Default prompt
     */
    public static function get_default_ai_prompt() {
        return "You are an AI fashion assistant specializing in virtual try-on for wedding dresses. " .
               "The first image is a customer's photo, and the following images show a wedding dress from different angles. " .
               "Generate a realistic image showing the customer wearing the wedding dress. " .
               "Maintain the customer's facial features, body proportions, and natural pose. " .
               "Ensure the dress fits naturally and realistically on the customer's body. " .
               "Pay attention to lighting, shadows, and fabric draping for a photorealistic result. " .
               "The output should look professional and convincing, as if the customer is actually wearing the dress.";
    }
    
    /**
     * Get all default options
     *
     * @return array Default options
     */
    public static function get_default_options() {
        return array(
            self::OPTION_API_PROVIDER => self::DEFAULT_API_PROVIDER,
            self::OPTION_INITIAL_CREDITS => self::DEFAULT_INITIAL_CREDITS,
            self::OPTION_CREDITS_PER_PACKAGE => self::DEFAULT_CREDITS_PER_PACKAGE,
            self::OPTION_CREDITS_PACKAGE_PRICE => self::DEFAULT_CREDITS_PACKAGE_PRICE,
            self::OPTION_MAX_IMAGE_SIZE => self::DEFAULT_MAX_IMAGE_SIZE,
            self::OPTION_API_RETRY_ATTEMPTS => self::DEFAULT_API_RETRY_ATTEMPTS,
            self::OPTION_ENABLE_LOGGING => true,
            self::OPTION_ENABLE_ANALYTICS => true,
            self::OPTION_TEMP_FILE_CLEANUP_HOURS => self::DEFAULT_TEMP_FILE_CLEANUP_HOURS,
            self::OPTION_API_TIMEOUT => self::DEFAULT_API_TIMEOUT,
            self::OPTION_REQUIRE_LOGIN => true,
            self::OPTION_ALLOWED_USER_ROLES => self::DEFAULT_ALLOWED_ROLES,
            self::OPTION_ENABLE_EMAIL_NOTIFICATIONS => true,
            self::OPTION_ADMIN_EMAIL_NOTIFICATIONS => false,
            self::OPTION_AI_PROMPT_TEMPLATE => self::get_default_ai_prompt(),
            self::OPTION_GEMINI_TEXT_API_ENDPOINT => self::DEFAULT_GEMINI_TEXT_API_ENDPOINT,
            self::OPTION_GEMINI_IMAGE_API_ENDPOINT => self::DEFAULT_GEMINI_IMAGE_API_ENDPOINT,
            self::OPTION_ALLOW_WOOCOMMERCE_DOMAINS => true,
        );
    }
    
    /**
     * Get option value with default fallback
     *
     * @param string $option_key Option key constant
     * @param mixed $default Default value (optional)
     * @return mixed Option value
     */
    public static function get_option($option_key, $default = null) {
        $defaults = self::get_default_options();
        
        if ($default === null && isset($defaults[$option_key])) {
            $default = $defaults[$option_key];
        }
        
        return get_option($option_key, $default);
    }
}
