<?php
/**
 * Image Processing functionality for AI Virtual Fitting Plugin
 *
 * @package AI_Virtual_Fitting
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI Virtual Fitting Image Processor Class
 */
class AI_Virtual_Fitting_Image_Processor {
    
    /**
     * Maximum file size in bytes (10MB)
     */
    const MAX_FILE_SIZE = 10485760;
    
    /**
     * Minimum image dimensions
     */
    const MIN_WIDTH = 512;
    const MIN_HEIGHT = 512;
    
    /**
     * Maximum image dimensions
     */
    const MAX_WIDTH = 2048;
    const MAX_HEIGHT = 2048;
    
    /**
     * Allowed MIME types
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
     * Magic byte signatures for file validation
     * First few bytes that identify real image files
     */
    const MAGIC_BYTES = array(
        'jpeg' => array(
            array(0xFF, 0xD8, 0xFF, 0xE0), // JPEG JFIF
            array(0xFF, 0xD8, 0xFF, 0xE1), // JPEG Exif
            array(0xFF, 0xD8, 0xFF, 0xE2), // JPEG
            array(0xFF, 0xD8, 0xFF, 0xE3), // JPEG
            array(0xFF, 0xD8, 0xFF, 0xE8), // JPEG SPIFF
        ),
        'png' => array(
            array(0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A), // PNG
        ),
        'webp' => array(
            array(0x52, 0x49, 0x46, 0x46), // RIFF (WebP starts with RIFF)
        ),
    );
    
    /**
     * Default Google AI Studio API endpoints
     * These can be overridden in admin settings
     */
    const DEFAULT_GEMINI_TEXT_API_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent';
    const DEFAULT_GEMINI_IMAGE_API_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3-pro-image-preview:generateContent';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize hooks for AJAX handlers
        add_action('wp_ajax_ai_virtual_fitting_upload_image', array($this, 'handle_image_upload'));
        add_action('wp_ajax_ai_virtual_fitting_process_fitting', array($this, 'handle_fitting_request'));
        add_action('wp_ajax_ai_virtual_fitting_download_result', array($this, 'handle_image_download'));
        
        // Add hooks for non-logged-in users (they should be redirected to login)
        add_action('wp_ajax_nopriv_ai_virtual_fitting_upload_image', array($this, 'require_login'));
        add_action('wp_ajax_nopriv_ai_virtual_fitting_process_fitting', array($this, 'require_login'));
        add_action('wp_ajax_nopriv_ai_virtual_fitting_download_result', array($this, 'require_login'));
    }
    
    /**
     * Get configured Gemini text API endpoint
     *
     * @return string API endpoint URL
     */
    private function get_gemini_text_endpoint() {
        $custom_endpoint = AI_Virtual_Fitting_Core::get_option('gemini_text_api_endpoint', '');
        return !empty($custom_endpoint) ? $custom_endpoint : self::DEFAULT_GEMINI_TEXT_API_ENDPOINT;
    }
    
    /**
     * Get configured Gemini image API endpoint
     *
     * @return string API endpoint URL
     */
    private function get_gemini_image_endpoint() {
        $custom_endpoint = AI_Virtual_Fitting_Core::get_option('gemini_image_api_endpoint', '');
        return !empty($custom_endpoint) ? $custom_endpoint : self::DEFAULT_GEMINI_IMAGE_API_ENDPOINT;
    }
    
    /**
     * Get decrypted API key
     * Retrieves and decrypts the stored API key
     *
     * @return string|false Decrypted API key or false on failure
     */
    private function get_api_key() {
        $encrypted_key = AI_Virtual_Fitting_Core::get_option('google_ai_api_key');
        
        if (empty($encrypted_key)) {
            return false;
        }
        
        // Try to decrypt - if it fails, it might be an old unencrypted key
        $decrypted = AI_Virtual_Fitting_Security_Manager::decrypt($encrypted_key);
        
        if ($decrypted === false) {
            // Might be an old unencrypted key, return as-is for backward compatibility
            // TODO: Remove this fallback after migration period
            return $encrypted_key;
        }
        
        return $decrypted;
    }
    
    /**
     * Validate uploaded image file
     *
     * @param array $file WordPress $_FILES array element
     * @return array Validation result with 'valid' boolean and 'error' message
     */
    public function validate_uploaded_image($file) {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return array(
                'valid' => false,
                'error' => __('No file was uploaded.', 'ai-virtual-fitting')
            );
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return array(
                'valid' => false,
                'error' => $this->get_upload_error_message($file['error'])
            );
        }
        
        // Check file size
        if ($file['size'] > self::MAX_FILE_SIZE) {
            return array(
                'valid' => false,
                'error' => sprintf(
                    __('Image file must be smaller than %s.', 'ai-virtual-fitting'),
                    size_format(self::MAX_FILE_SIZE)
                )
            );
        }
        
        // SECURITY: Validate magic bytes first (prevents file type spoofing)
        $magic_byte_validation = $this->validate_magic_bytes($file['tmp_name']);
        if (!$magic_byte_validation['valid']) {
            return $magic_byte_validation;
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, self::ALLOWED_MIME_TYPES)) {
            return array(
                'valid' => false,
                'error' => __('Please upload a JPEG, PNG, or WebP image file.', 'ai-virtual-fitting')
            );
        }
        
        // Check file extension
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, self::ALLOWED_EXTENSIONS)) {
            return array(
                'valid' => false,
                'error' => __('Invalid file extension. Please use JPG, PNG, or WebP.', 'ai-virtual-fitting')
            );
        }
        
        // Verify it's actually an image and get dimensions
        $image_info = getimagesize($file['tmp_name']);
        if ($image_info === false) {
            return array(
                'valid' => false,
                'error' => __('Unable to process image file. Please try a different image.', 'ai-virtual-fitting')
            );
        }
        
        $width = $image_info[0];
        $height = $image_info[1];
        
        // Check minimum dimensions
        if ($width < self::MIN_WIDTH || $height < self::MIN_HEIGHT) {
            return array(
                'valid' => false,
                'error' => sprintf(
                    __('Image must be at least %dx%d pixels.', 'ai-virtual-fitting'),
                    self::MIN_WIDTH,
                    self::MIN_HEIGHT
                )
            );
        }
        
        // Check maximum dimensions
        if ($width > self::MAX_WIDTH || $height > self::MAX_HEIGHT) {
            return array(
                'valid' => false,
                'error' => sprintf(
                    __('Image must be no larger than %dx%d pixels.', 'ai-virtual-fitting'),
                    self::MAX_WIDTH,
                    self::MAX_HEIGHT
                )
            );
        }
        
        return array(
            'valid' => true,
            'width' => $width,
            'height' => $height,
            'mime_type' => $mime_type,
            'file_size' => $file['size']
        );
    }
    
    /**
     * Validate file magic bytes to ensure it's a real image file
     * This prevents file type spoofing attacks
     *
     * @param string $file_path Path to the uploaded file
     * @return array Validation result
     */
    private function validate_magic_bytes($file_path) {
        // Read first 12 bytes (enough for all our image types)
        $handle = fopen($file_path, 'rb');
        if (!$handle) {
            return array(
                'valid' => false,
                'error' => __('Unable to read uploaded file.', 'ai-virtual-fitting')
            );
        }
        
        $bytes = fread($handle, 12);
        fclose($handle);
        
        if ($bytes === false || strlen($bytes) < 4) {
            return array(
                'valid' => false,
                'error' => __('Uploaded file is too small or corrupted.', 'ai-virtual-fitting')
            );
        }
        
        // Convert bytes to array of integers
        $byte_array = array_values(unpack('C*', $bytes));
        
        // Check against known magic bytes
        $is_valid = false;
        foreach (self::MAGIC_BYTES as $type => $signatures) {
            foreach ($signatures as $signature) {
                $matches = true;
                for ($i = 0; $i < count($signature); $i++) {
                    if (!isset($byte_array[$i]) || $byte_array[$i] !== $signature[$i]) {
                        $matches = false;
                        break;
                    }
                }
                
                if ($matches) {
                    // Special check for WebP - must have "WEBP" after RIFF
                    if ($type === 'webp') {
                        if (strlen($bytes) >= 12) {
                            $webp_check = substr($bytes, 8, 4);
                            if ($webp_check === 'WEBP') {
                                $is_valid = true;
                                break 2;
                            }
                        }
                    } else {
                        $is_valid = true;
                        break 2;
                    }
                }
            }
        }
        
        if (!$is_valid) {
            // Log suspicious upload attempt
            if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
                error_log('AI Virtual Fitting: Suspicious file upload blocked - invalid magic bytes');
            }
            
            return array(
                'valid' => false,
                'error' => __('File type validation failed. The file may be corrupted or not a valid image.', 'ai-virtual-fitting')
            );
        }
        
        return array('valid' => true);
    }
    
    /**
     * Test API connection with provided credentials
     *
     * @param string $credentials API key or service account JSON
     * @return array Test result with success boolean and message
     */
    public function test_api_connection($credentials = null) {
        $api_provider = get_option('ai_virtual_fitting_api_provider', 'google_ai_studio');
        
        if ($api_provider === 'vertex_ai') {
            return $this->test_vertex_ai_connection($credentials);
        } else {
            return $this->test_google_ai_studio_connection($credentials);
        }
    }
    
    /**
     * Test Google AI Studio connection
     */
    private function test_google_ai_studio_connection($api_key = null) {
        if ($api_key === null) {
            $api_key = $this->get_api_key();
        }
        
        if (empty($api_key)) {
            return array(
                'success' => false,
                'message' => __('API key is required for testing.', 'ai-virtual-fitting')
            );
        }
        
        try {
            // Create a simple test request
            $request_data = array(
                'contents' => array(
                    array(
                        'parts' => array(
                            array('text' => 'Hello, this is a test connection.')
                        )
                    )
                ),
                'generationConfig' => array(
                    'temperature' => 0.1,
                    'maxOutputTokens' => 10,
                )
            );
            
            // Make test API request
            $response = wp_remote_post(
                $this->get_gemini_text_endpoint() . '?key=' . $api_key,
                array(
                    'headers' => array(
                        'Content-Type' => 'application/json',
                    ),
                    'body' => json_encode($request_data),
                    'timeout' => 30,
                    'sslverify' => true
                )
            );
            
            if (is_wp_error($response)) {
                return array(
                    'success' => false,
                    'message' => sprintf(__('Connection failed: %s', 'ai-virtual-fitting'), $response->get_error_message())
                );
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            
            if ($response_code === 200) {
                $data = json_decode($response_body, true);
                if (isset($data['candidates'])) {
                    return array(
                        'success' => true,
                        'message' => __('Google AI Studio connection successful!', 'ai-virtual-fitting')
                    );
                }
            }
            
            // Handle error response
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['error']['message']) 
                ? $error_data['error']['message'] 
                : sprintf(__('HTTP %d: %s', 'ai-virtual-fitting'), $response_code, $response_body);
                
            return array(
                'success' => false,
                'message' => sprintf(__('API test failed: %s', 'ai-virtual-fitting'), $error_message)
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => sprintf(__('Test failed with exception: %s', 'ai-virtual-fitting'), $e->getMessage())
            );
        }
    }
    
    /**
     * Test Vertex AI connection
     */
    private function test_vertex_ai_connection($credentials = null) {
        return array(
            'success' => false,
            'message' => __('Vertex AI testing not fully implemented. Please use Google AI Studio for now.', 'ai-virtual-fitting')
        );
    }
    
    /**
     * Process virtual fitting request
     *
     * @param string $customer_image_path Path to customer image
     * @param array $product_images Array of product image URLs or paths
     * @return array Processing result
     */
    public function process_virtual_fitting($customer_image_path, $product_images) {
        try {
            // Validate inputs
            if (!file_exists($customer_image_path)) {
                throw new Exception('Customer image file not found.');
            }
            
            // Allow flexible number of product images (1-4)
            if (empty($product_images)) {
                throw new Exception('At least 1 product image is required.');
            }
            
            // Limit to maximum 1 product image for better AI focus and performance
            // Using only the featured/main product image reduces visual confusion for the AI
            $product_images = array_slice($product_images, 0, 1);
            
            // Log the actual number of images being processed
            if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
                error_log('AI Virtual Fitting - Image count after limiting: User=1, Product=' . count($product_images) . ', Total=' . (1 + count($product_images)) . ' (Gemini 3 Pro Image Preview limit: 14 max)');
            }
            
            // Download product images if they are URLs
            $local_product_images = array();
            foreach ($product_images as $index => $product_image) {
                if (filter_var($product_image, FILTER_VALIDATE_URL)) {
                    // Check if it's a local WordPress URL - convert to local path
                    $site_url = get_site_url();
                    if (strpos($product_image, $site_url) === 0) {
                        $local_path = $this->convert_site_url_to_path($product_image);
                        if ($local_path && file_exists($local_path)) {
                            $local_product_images[] = $local_path;
                            $this->log_info('Converted site URL to local path', array(
                                'url' => $product_image,
                                'local_path' => $local_path
                            ));
                        }
                    } else {
                        // It's an external URL, download it
                        $local_path = $this->download_product_image($product_image, $index);
                        if ($local_path) {
                            $local_product_images[] = $local_path;
                        }
                    }
                } else {
                    // It's a local path, verify it exists
                    if (file_exists($product_image)) {
                        $local_product_images[] = $product_image;
                    }
                }
            }
            
            if (empty($local_product_images)) {
                throw new Exception('No valid product images found.');
            }
            
            // Prepare images for API call
            $images_data = array();
            
            // Add customer image first
            $customer_image_data = array(
                'inlineData' => array(
                    'mimeType' => $this->get_image_mime_type($customer_image_path),
                    'data' => base64_encode(file_get_contents($customer_image_path))
                )
            );
            $images_data[] = $customer_image_data;
            
            // Log customer image details for debugging
            if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
                error_log('AI Virtual Fitting - Customer Image Details: ' . json_encode(array(
                    'path' => $customer_image_path,
                    'mime_type' => $customer_image_data['inlineData']['mimeType'],
                    'file_size' => filesize($customer_image_path),
                    'data_length' => strlen($customer_image_data['inlineData']['data']),
                    'file_modified' => filemtime($customer_image_path)
                )));
            }
            
            // Add product images
            foreach ($local_product_images as $index => $product_image) {
                $product_image_data = array(
                    'inlineData' => array(
                        'mimeType' => $this->get_image_mime_type($product_image),
                        'data' => base64_encode(file_get_contents($product_image))
                    )
                );
                $images_data[] = $product_image_data;
                
                // Log product image details for debugging
                if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
                    error_log('AI Virtual Fitting - Product Image ' . ($index + 1) . ' Details: ' . json_encode(array(
                        'path' => $product_image,
                        'mime_type' => $product_image_data['inlineData']['mimeType'],
                        'file_size' => filesize($product_image),
                        'data_length' => strlen($product_image_data['inlineData']['data'])
                    )));
                }
            }
            
            // Create prompt for virtual fitting
            $prompt = $this->get_ai_prompt_template();
            
            // Call Gemini API
            $api_response = $this->call_gemini_api($images_data, $prompt);
            
            if (!$api_response['success']) {
                throw new Exception($api_response['error']);
            }
            
            // Save result image
            $result_image_path = $this->save_result_image($api_response['image_data']);
            
            return array(
                'success' => true,
                'result_image_path' => $result_image_path,
                'result_image_url' => $this->get_temp_image_url($result_image_path)
            );
            
        } catch (Exception $e) {
            // Log error
            if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
                error_log('AI Virtual Fitting - Processing Error: ' . $e->getMessage());
            }
            
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Call Google AI Studio Gemini API for image generation
     *
     * @param array $images_data Array of image data
     * @param string $prompt Text prompt for AI
     * @return array API response
     */
    public function call_gemini_api($images_data, $prompt) {
        $api_key = $this->get_api_key();
        
        if (empty($api_key)) {
            return array(
                'success' => false,
                'error' => __('Google AI API key not configured.', 'ai-virtual-fitting')
            );
        }
        
        $retry_attempts = AI_Virtual_Fitting_Core::get_option('api_retry_attempts', 3);
        $last_error = '';
        
        for ($attempt = 1; $attempt <= $retry_attempts; $attempt++) {
            try {
                // Prepare request data for image generation using correct format
                $parts = array();
                
                // Add text prompt first
                $parts[] = array('text' => $prompt);
                
                // Add all images with correct format
                foreach ($images_data as $image_data) {
                    $parts[] = array(
                        'inline_data' => array(
                            'mime_type' => $image_data['inlineData']['mimeType'],
                            'data' => $image_data['inlineData']['data']
                        )
                    );
                }
                
                $request_data = array(
                    'contents' => array(
                        array(
                            'parts' => $parts
                        )
                    ),
                    'generationConfig' => array(
                        'responseModalities' => array('TEXT', 'IMAGE'),
                        'imageConfig' => array(
                            'aspectRatio' => '1:1',
                            'imageSize' => '1K'
                        )
                    )
                );
                
                // Log request for debugging
                if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
                    error_log("AI Virtual Fitting - API Request Attempt $attempt: " . json_encode(array(
                        'model' => 'gemini-3-pro-image-preview',
                        'endpoint' => $this->get_gemini_image_endpoint(),
                        'parts_count' => count($parts),
                        'has_text' => !empty($prompt),
                        'images_count' => count($images_data)
                    )));
                }
                
                // Make API request to image generation endpoint
                $response = wp_remote_post(
                    $this->get_gemini_image_endpoint() . '?key=' . $api_key,
                    array(
                        'headers' => array(
                            'Content-Type' => 'application/json',
                        ),
                        'body' => json_encode($request_data),
                        'timeout' => 120, // Longer timeout for image generation
                        'sslverify' => true
                    )
                );
                
                if (is_wp_error($response)) {
                    $last_error = $response->get_error_message();
                    
                    // Log attempt
                    if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
                        error_log("AI Virtual Fitting - API Attempt $attempt failed: " . $last_error);
                    }
                    
                    // Wait before retry (except on last attempt)
                    if ($attempt < $retry_attempts) {
                        sleep(2 * $attempt); // Exponential backoff
                    }
                    continue;
                }
                
                $response_code = wp_remote_retrieve_response_code($response);
                $response_body = wp_remote_retrieve_body($response);
                
                // Log response for debugging
                if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
                    error_log("AI Virtual Fitting - API Response $attempt: HTTP $response_code, Body length: " . strlen($response_body));
                }
                
                if ($response_code !== 200) {
                    $error_data = json_decode($response_body, true);
                    $last_error = isset($error_data['error']['message']) 
                        ? $error_data['error']['message'] 
                        : "API returned status code: $response_code";
                    
                    // Log full error response for debugging
                    if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
                        error_log("AI Virtual Fitting - API Error Response: " . $response_body);
                    }
                    
                    // Handle rate limiting
                    if ($response_code === 429) {
                        if ($attempt < $retry_attempts) {
                            sleep(5 * $attempt); // Longer wait for rate limiting
                        }
                        continue;
                    }
                    
                    // For other errors, wait before retry
                    if ($attempt < $retry_attempts) {
                        sleep(2 * $attempt);
                    }
                    continue;
                }
                
                // Parse successful response
                $response_data = json_decode($response_body, true);
                
                if (!$response_data) {
                    $last_error = 'Invalid JSON response from API';
                    continue;
                }
                
                // Look for generated image in response - check multiple possible locations
                if (isset($response_data['candidates'][0]['content']['parts'])) {
                    foreach ($response_data['candidates'][0]['content']['parts'] as $part) {
                        // Check for inline_data (correct format)
                        if (isset($part['inline_data']['data'])) {
                            if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
                                error_log("AI Virtual Fitting - Image generation successful on attempt $attempt (inline_data format)");
                            }
                            
                            return array(
                                'success' => true,
                                'image_data' => $part['inline_data']['data'],
                                'api_response' => 'Virtual fitting image generated successfully'
                            );
                        }
                        // Check for inlineData (alternative format)
                        if (isset($part['inlineData']['data'])) {
                            if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
                                error_log("AI Virtual Fitting - Image generation successful on attempt $attempt (inlineData format)");
                            }
                            
                            return array(
                                'success' => true,
                                'image_data' => $part['inlineData']['data'],
                                'api_response' => 'Virtual fitting image generated successfully'
                            );
                        }
                    }
                }
                
                $last_error = 'No image data found in API response';
                
                // Log the actual response structure for debugging
                if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
                    error_log("AI Virtual Fitting - API Response Structure: " . json_encode(array(
                        'has_candidates' => isset($response_data['candidates']),
                        'candidates_count' => isset($response_data['candidates']) ? count($response_data['candidates']) : 0,
                        'first_candidate_keys' => isset($response_data['candidates'][0]) ? array_keys($response_data['candidates'][0]) : array(),
                        'response_sample' => substr($response_body, 0, 500) . '...'
                    )));
                }
                continue;
                
            } catch (Exception $e) {
                $last_error = $e->getMessage();
                
                // Log attempt
                if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
                    error_log("AI Virtual Fitting - API Attempt $attempt exception: " . $last_error);
                }
                
                if ($attempt < $retry_attempts) {
                    sleep(2 * $attempt);
                }
            }
        }
        
        // All attempts failed
        return array(
            'success' => false,
            'error' => sprintf(
                __('AI image generation failed after %d attempts. Last error: %s', 'ai-virtual-fitting'),
                $retry_attempts,
                $last_error
            )
        );
    }
    
    /**
     * Create virtual fitting result image with AI description
     * Since Gemini doesn't generate images, we create a composite result
     *
     * @param string $customer_image_path Path to customer image
     * @param array $product_images Array of product image paths
     * @param string $ai_description AI's description of the virtual fitting
     * @return string Base64 encoded result image
     */
    private function create_virtual_fitting_result($customer_image_path, $product_images, $ai_description) {
        try {
            // Create a result canvas
            $canvas_width = 1200;
            $canvas_height = 800;
            $canvas = imagecreatetruecolor($canvas_width, $canvas_height);
            
            // Set background color (light gray)
            $bg_color = imagecolorallocate($canvas, 245, 245, 245);
            imagefill($canvas, 0, 0, $bg_color);
            
            // Load and resize customer image
            $customer_img = $this->load_and_resize_image($customer_image_path, 400, 600);
            if ($customer_img) {
                imagecopy($canvas, $customer_img, 50, 100, 0, 0, imagesx($customer_img), imagesy($customer_img));
                imagedestroy($customer_img);
            }
            
            // Load and display first product image
            if (!empty($product_images)) {
                $product_img = $this->load_and_resize_image($product_images[0], 300, 400);
                if ($product_img) {
                    imagecopy($canvas, $product_img, 500, 150, 0, 0, imagesx($product_img), imagesy($product_img));
                    imagedestroy($product_img);
                }
            }
            
            // Add title
            $title_color = imagecolorallocate($canvas, 50, 50, 50);
            $title = 'AI Virtual Fitting Result';
            imagestring($canvas, 5, 50, 50, $title, $title_color);
            
            // Add AI description (wrapped text)
            $text_color = imagecolorallocate($canvas, 80, 80, 80);
            $description_lines = $this->wrap_text($ai_description, 60);
            $y_pos = 720;
            foreach (array_slice($description_lines, 0, 3) as $line) { // Show first 3 lines
                imagestring($canvas, 3, 50, $y_pos, $line, $text_color);
                $y_pos += 20;
            }
            
            // Add watermark
            $watermark_color = imagecolorallocate($canvas, 150, 150, 150);
            imagestring($canvas, 2, $canvas_width - 200, $canvas_height - 30, 'AI Virtual Fitting Demo', $watermark_color);
            
            // Convert to JPEG
            ob_start();
            imagejpeg($canvas, null, 85);
            $image_data = ob_get_contents();
            ob_end_clean();
            
            // Clean up
            imagedestroy($canvas);
            
            return base64_encode($image_data);
            
        } catch (Exception $e) {
            // Fallback to simple placeholder
            return $this->generate_placeholder_image();
        }
    }
    
    /**
     * Load and resize image
     *
     * @param string $image_path Path to image
     * @param int $max_width Maximum width
     * @param int $max_height Maximum height
     * @return resource|false Image resource or false on failure
     */
    private function load_and_resize_image($image_path, $max_width, $max_height) {
        if (!file_exists($image_path)) {
            return false;
        }
        
        $image_info = getimagesize($image_path);
        if (!$image_info) {
            return false;
        }
        
        // Load image based on type
        switch ($image_info[2]) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($image_path);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($image_path);
                break;
            case IMAGETYPE_WEBP:
                $source = imagecreatefromwebp($image_path);
                break;
            default:
                return false;
        }
        
        if (!$source) {
            return false;
        }
        
        // Calculate new dimensions
        $orig_width = imagesx($source);
        $orig_height = imagesy($source);
        
        $ratio = min($max_width / $orig_width, $max_height / $orig_height);
        $new_width = intval($orig_width * $ratio);
        $new_height = intval($orig_height * $ratio);
        
        // Create resized image
        $resized = imagecreatetruecolor($new_width, $new_height);
        
        // Preserve transparency for PNG
        if ($image_info[2] == IMAGETYPE_PNG) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefilledrectangle($resized, 0, 0, $new_width, $new_height, $transparent);
        }
        
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
        imagedestroy($source);
        
        return $resized;
    }
    
    /**
     * Wrap text to specified width
     *
     * @param string $text Text to wrap
     * @param int $width Character width
     * @return array Array of text lines
     */
    private function wrap_text($text, $width) {
        return explode("\n", wordwrap($text, $width, "\n", true));
    }

    /**
     * Generate placeholder image data for testing
     * In production, this would be replaced with actual API response handling
     *
     * @return string Base64 encoded placeholder image
     */
    private function generate_placeholder_image() {
        // Create a simple 800x600 placeholder image
        $width = 800;
        $height = 600;
        
        $image = imagecreatetruecolor($width, $height);
        
        // Set background color (light gray)
        $bg_color = imagecolorallocate($image, 240, 240, 240);
        imagefill($image, 0, 0, $bg_color);
        
        // Add text
        $text_color = imagecolorallocate($image, 100, 100, 100);
        $text = 'Virtual Fitting Result';
        
        // Calculate text position (center)
        $font_size = 5;
        $text_width = imagefontwidth($font_size) * strlen($text);
        $text_height = imagefontheight($font_size);
        $x = ($width - $text_width) / 2;
        $y = ($height - $text_height) / 2;
        
        imagestring($image, $font_size, $x, $y, $text, $text_color);
        
        // Capture image as JPEG
        ob_start();
        imagejpeg($image, null, 80);
        $image_data = ob_get_contents();
        ob_end_clean();
        
        // Clean up
        imagedestroy($image);
        
        return base64_encode($image_data);
    }
    
    /**
     * Save AI result image to temporary storage
     *
     * @param string $image_data Base64 encoded image data
     * @return string Path to saved image
     */
    public function save_result_image($image_data) {
        // Create temp directory if it doesn't exist
        $temp_dir = $this->get_temp_directory();
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }
        
        // Generate unique filename
        $filename = 'virtual_fitting_result_' . uniqid() . '_' . time() . '.jpg';
        $file_path = $temp_dir . '/' . $filename;
        
        // Decode and save image
        $image_binary = base64_decode($image_data);
        
        if (file_put_contents($file_path, $image_binary) === false) {
            throw new Exception('Failed to save result image.');
        }
        
        // Set file permissions
        chmod($file_path, 0644);
        
        return $file_path;
    }
    
    /**
     * Convert site URL to local file path
     * Works with any WordPress installation (localhost or production)
     *
     * @param string $url Site URL
     * @return string|false Local file path or false on failure
     */
    private function convert_site_url_to_path($url) {
        // Get WordPress site URL and remove it from the image URL
        $site_url = get_site_url();
        $path = str_replace($site_url, '', $url);
        
        // Convert to absolute file system path using WordPress constants
        // ABSPATH points to the WordPress root directory
        $local_path = ABSPATH . ltrim($path, '/');
        
        return $local_path;
    }

    /**
     * Download product image from URL to local temporary storage
     *
     * @param string $image_url URL of the product image
     * @param int $index Image index for unique naming
     * @return string|false Local path to downloaded image or false on failure
     */
    private function download_product_image($image_url, $index = 0) {
        try {
            // SECURITY: Validate URL to prevent SSRF attacks
            $url_validation = AI_Virtual_Fitting_Security_Manager::validate_external_url($image_url);
            if (!$url_validation['valid']) {
                $this->log_error('URL validation failed for product image', array(
                    'url' => $image_url,
                    'error' => $url_validation['error']
                ));
                return false;
            }
            
            // Create temp directory if it doesn't exist
            $temp_dir = $this->get_temp_directory();
            if (!file_exists($temp_dir)) {
                wp_mkdir_p($temp_dir);
            }
            
            // Generate unique filename
            $filename = 'product_image_' . $index . '_' . uniqid() . '_' . time() . '.jpg';
            $file_path = $temp_dir . '/' . $filename;
            
            // Download image using WordPress HTTP API
            $response = wp_remote_get($image_url, array(
                'timeout' => 30,
                'sslverify' => true, // Enable SSL verification for security
                'redirection' => 3, // Limit redirects
                'user-agent' => 'AI-Virtual-Fitting-Plugin/' . AI_VIRTUAL_FITTING_VERSION
            ));
            
            if (is_wp_error($response)) {
                $this->log_error('Failed to download product image', array(
                    'url' => $image_url,
                    'error' => $response->get_error_message()
                ));
                return false;
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                $this->log_error('Product image download failed', array(
                    'url' => $image_url,
                    'response_code' => $response_code
                ));
                return false;
            }
            
            $image_data = wp_remote_retrieve_body($response);
            if (empty($image_data)) {
                $this->log_error('Empty product image data', array('url' => $image_url));
                return false;
            }
            
            // Save image data to file
            if (file_put_contents($file_path, $image_data) === false) {
                $this->log_error('Failed to save product image', array(
                    'url' => $image_url,
                    'local_path' => $file_path
                ));
                return false;
            }
            
            // SECURITY: Validate downloaded file
            $file_validation = AI_Virtual_Fitting_Security_Manager::validate_downloaded_file($file_path);
            if (!$file_validation['valid']) {
                // Delete invalid file
                @unlink($file_path);
                $this->log_error('Downloaded file validation failed', array(
                    'url' => $image_url,
                    'error' => $file_validation['error']
                ));
                return false;
            }
            
            // Set file permissions
            chmod($file_path, 0644);
            
            $this->log_info('Product image downloaded successfully', array(
                'url' => $image_url,
                'local_path' => $file_path,
                'size' => filesize($file_path)
            ));
            
            return $file_path;
            
        } catch (Exception $e) {
            $this->log_error('Exception downloading product image', array(
                'url' => $image_url,
                'exception' => $e->getMessage()
            ));
            return false;
        }
    }

    /**
     * Get image MIME type
     *
     * @param string $file_path
     * @return string
     */
    private function get_image_mime_type($file_path) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        return $mime_type;
    }
    
    /**
     * Get temporary directory path
     *
     * @return string
     */
    private function get_temp_directory() {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'] . '/ai-virtual-fitting-temp';
    }
    
    /**
     * Get temporary image URL
     *
     * @param string $file_path
     * @return string
     */
    private function get_temp_image_url($file_path) {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/ai-virtual-fitting-temp';
        $temp_url = $upload_dir['baseurl'] . '/ai-virtual-fitting-temp';
        
        return str_replace($temp_dir, $temp_url, $file_path);
    }
    
    /**
     * Save uploaded image to temporary storage
     *
     * @param array $file WordPress $_FILES array element
     * @return string Path to saved file
     */
    private function save_uploaded_image($file) {
        $temp_dir = $this->get_temp_directory();
        
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }
        
        $filename = 'customer_image_' . uniqid() . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $file_path = $temp_dir . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            throw new Exception(__('Failed to save uploaded image.', 'ai-virtual-fitting'));
        }
        
        chmod($file_path, 0644);
        
        return $file_path;
    }
    
    /**
     * Require login for AJAX requests
     */
    public function require_login() {
        wp_send_json_error(array(
            'message' => __('You must be logged in to use this feature.', 'ai-virtual-fitting'),
            'login_required' => true
        ));
    }
    
    /**
     * Get upload error message
     *
     * @param int $error_code PHP upload error code
     * @return string Error message
     */
    private function get_upload_error_message($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return __('The uploaded file is too large.', 'ai-virtual-fitting');
            case UPLOAD_ERR_PARTIAL:
                return __('The file was only partially uploaded.', 'ai-virtual-fitting');
            case UPLOAD_ERR_NO_FILE:
                return __('No file was uploaded.', 'ai-virtual-fitting');
            case UPLOAD_ERR_NO_TMP_DIR:
                return __('Missing temporary folder.', 'ai-virtual-fitting');
            case UPLOAD_ERR_CANT_WRITE:
                return __('Failed to write file to disk.', 'ai-virtual-fitting');
            case UPLOAD_ERR_EXTENSION:
                return __('File upload stopped by extension.', 'ai-virtual-fitting');
            default:
                return __('Unknown upload error.', 'ai-virtual-fitting');
        }
    }
    
    /**
     * Log error message with context
     *
     * @param string $message Error message
     * @param array $context Additional context data
     */
    private function log_error($message, $context = array()) {
        if (!AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
            return;
        }
        
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'level' => 'ERROR',
            'component' => 'ImageProcessor',
            'message' => $message,
            'context' => $context
        );
        
        error_log('AI Virtual Fitting - ' . json_encode($log_entry));
    }
    
    /**
     * Log info message with context
     *
     * @param string $message Info message
     * @param array $context Additional context data
     */
    private function log_info($message, $context = array()) {
        if (!AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
            return;
        }
        
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'level' => 'INFO',
            'component' => 'ImageProcessor',
            'message' => $message,
            'context' => $context
        );
        
        error_log('AI Virtual Fitting - ' . json_encode($log_entry));
    }
    
    /**
     * Get AI prompt template from settings or default
     *
     * @return string AI prompt template
     */
    private function get_ai_prompt_template() {
        $custom_prompt = AI_Virtual_Fitting_Core::get_option('ai_prompt_template');
        
        if (!empty($custom_prompt)) {
            return $custom_prompt;
        }
        
        // Fallback to default prompt
        return $this->get_default_ai_prompt();
    }
    
    /**
     * Get default AI prompt
     *
     * @return string Default AI prompt
     */
    private function get_default_ai_prompt() {
        return "You are a virtual try-on image generation system.\n\nINPUTS:\n- Image A: a real person (customer photo).\n- Image(s) B: wedding dress product images.\n\nOBJECTIVE:\nGenerate a realistic virtual try-on image showing the person from Image A wearing the wedding dress from Image B.\n\nSTRICT RULES (DO NOT VIOLATE):\n1. The person's body shape, weight, proportions, height, posture, and pose from Image A MUST be preserved exactly.\n   - Do NOT slim, stretch, reshape, beautify, or alter the body.\n   - The dress must adapt to the person's body, not the other way around.\n\n2. The person's face, identity, skin tone, and expression MUST remain unchanged.\n   - No face replacement, no facial enhancement, no smoothing.\n\n3. The wedding dress style MUST match the product images.\n   - Give highest priority to the FIRST product image.\n   - Preserve fabric type, lace patterns, neckline, sleeves, waistline, skirt volume, and silhouette.\n   - Do not invent new design elements.\n\n4. Lighting and perspective MUST match Image A.\n   - Do not change scene lighting or camera angle.\n   - The dress should appear naturally lit in the same environment as the person.\n\n5. The result MUST look like a real-life fitting:\n   - Natural folds, gravity, and fabric behavior.\n   - Proper alignment with shoulders, waist, hips, and legs.\n   - No floating, clipping, or unnatural stretching.\n\nQUALITY REQUIREMENTS:\n- Seamless integration between body and dress.\n- Photorealistic, professional, retail-quality output.\n- No stylization, no artistic filters, no exaggeration.\n\nFAILURE CONDITIONS (AVOID):\n- Altered body size or proportions\n- Changed pose or posture\n- Generic or blended dress styles\n- Over-smoothing or beauty retouching\n- Unrealistic fabric behavior\n\nOUTPUT:\n- One realistic virtual try-on image of the person wearing the selected wedding dress.";
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
}