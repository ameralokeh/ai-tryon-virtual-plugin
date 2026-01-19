# API Reference

## Overview

The AI Virtual Fitting Plugin provides a comprehensive REST API for integrating virtual fitting functionality into WordPress and WooCommerce environments. The API consists of AJAX endpoints for frontend interactions, WordPress hooks for extensibility, and integration points with Google AI Studio and WooCommerce.

### API Architecture

The plugin follows a modular architecture with clear separation of concerns:

- **Public Interface Layer**: Handles all AJAX requests from the frontend
- **Core Processing Layer**: Manages business logic for credits, images, and AI processing
- **Integration Layer**: Connects with WooCommerce and Google AI Studio
- **Security Layer**: Implements authentication, rate limiting, and validation

### Authentication

All API endpoints use WordPress's built-in authentication system combined with nonce verification for security.

#### Nonce Verification

Every AJAX request must include a valid nonce token:

```javascript
// Nonce is automatically generated and localized in JavaScript
const nonce = ai_virtual_fitting_ajax.nonce;

// Include in AJAX requests
jQuery.ajax({
    url: ai_virtual_fitting_ajax.ajax_url,
    type: 'POST',
    data: {
        action: 'ai_virtual_fitting_upload',
        nonce: nonce,
        // ... other parameters
    }
});
```

#### User Authentication

Most endpoints require user authentication. The plugin checks:

1. **User Login Status**: `is_user_logged_in()`
2. **User Capabilities**: Verified through WordPress roles
3. **Nonce Validation**: `wp_verify_nonce()`

### Rate Limiting

The plugin implements rate limiting to prevent abuse and ensure fair resource usage.


#### Rate Limit Configuration

- **Upload Image**: 10 requests per minute per user
- **Process Fitting**: 5 requests per minute per user
- **Add to Cart**: 20 requests per minute per user
- **Checkout Operations**: 10 requests per minute per user

Rate limits are enforced using the `AI_Virtual_Fitting_Security_Manager::check_rate_limit()` method.

#### Rate Limit Response

When rate limit is exceeded, the API returns:

```json
{
    "success": false,
    "data": {
        "message": "Too many requests. Please wait a few minutes and try again.",
        "error_code": "RATE_LIMIT_EXCEEDED"
    }
}
```

### Error Handling

All API endpoints follow a consistent error response format:

#### Success Response

```json
{
    "success": true,
    "data": {
        "message": "Operation completed successfully",
        // ... additional data
    }
}
```

#### Error Response

```json
{
    "success": false,
    "data": {
        "message": "Error description",
        "error_code": "ERROR_CODE",
        "retry_allowed": true
    }
}
```

#### Common Error Codes

| Error Code | Description | Retry Allowed |
|------------|-------------|---------------|
| `SECURITY_FAILED` | Nonce verification failed | No |
| `AUTH_REQUIRED` | User must be logged in | No |
| `RATE_LIMIT_EXCEEDED` | Too many requests | Yes (after delay) |
| `INSUFFICIENT_CREDITS` | User has no credits | No |
| `UPLOAD_FAILED` | Image upload error | Yes |
| `VALIDATION_FAILED` | Input validation error | Yes |
| `PROCESSING_ERROR` | AI processing error | Yes |
| `CART_ADD_FAILED` | Failed to add to cart | Yes |
| `WOOCOMMERCE_INACTIVE` | WooCommerce not active | No |


---

## AJAX Endpoints

All AJAX endpoints are accessed through WordPress's `admin-ajax.php` endpoint. The base URL is available in JavaScript as `ai_virtual_fitting_ajax.ajax_url`.

### Image Upload

Upload a customer photo for virtual fitting.

**Endpoint**: `ai_virtual_fitting_upload`

**Method**: POST (multipart/form-data)

**Authentication**: Required

**Rate Limit**: 10 requests/minute

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `action` | string | Yes | Must be `ai_virtual_fitting_upload` |
| `nonce` | string | Yes | WordPress nonce for security |
| `customer_image` | file | Yes | Image file (JPEG, PNG, or WebP) |

#### Request Example

```javascript
const formData = new FormData();
formData.append('action', 'ai_virtual_fitting_upload');
formData.append('nonce', ai_virtual_fitting_ajax.nonce);
formData.append('customer_image', fileInput.files[0]);

jQuery.ajax({
    url: ai_virtual_fitting_ajax.ajax_url,
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function(response) {
        if (response.success) {
            console.log('Temp file:', response.data.temp_file);
        }
    }
});
```

#### Success Response

```json
{
    "success": true,
    "data": {
        "message": "Image uploaded successfully",
        "temp_file": "customer_123_1234567890_abc123.jpg"
    }
}
```

#### Error Responses

```json
{
    "success": false,
    "data": {
        "message": "File size exceeds maximum allowed (10MB)",
        "error_code": "VALIDATION_FAILED"
    }
}
```

### Process Virtual Fitting

Process a virtual fitting request using AI.

**Endpoint**: `ai_virtual_fitting_process`

**Method**: POST

**Authentication**: Required

**Rate Limit**: 5 requests/minute

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `action` | string | Yes | Must be `ai_virtual_fitting_process` |
| `nonce` | string | Yes | WordPress nonce for security |
| `temp_file` | string | Yes | Temporary filename from upload |
| `product_id` | integer | Yes | WooCommerce product ID |

#### Request Example

```javascript
jQuery.ajax({
    url: ai_virtual_fitting_ajax.ajax_url,
    type: 'POST',
    data: {
        action: 'ai_virtual_fitting_process',
        nonce: ai_virtual_fitting_ajax.nonce,
        temp_file: 'customer_123_1234567890_abc123.jpg',
        product_id: 456
    },
    success: function(response) {
        if (response.success) {
            console.log('Result image:', response.data.result_image);
            console.log('Remaining credits:', response.data.credits);
        }
    }
});
```

#### Success Response

```json
{
    "success": true,
    "data": {
        "message": "Virtual fitting completed successfully",
        "result_image": "https://example.com/wp-content/uploads/ai-virtual-fitting/results/result_123_1234567890.jpg",
        "credits": 18
    }
}
```

#### Error Responses

```json
{
    "success": false,
    "data": {
        "message": "Insufficient credits",
        "credits": 0
    }
}
```

### Check Credits

Check the current user's credit balance.

**Endpoint**: `ai_virtual_fitting_check_credits`

**Method**: POST

**Authentication**: Optional (returns 0 if not logged in)

**Rate Limit**: None

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `action` | string | Yes | Must be `ai_virtual_fitting_check_credits` |
| `nonce` | string | Yes | WordPress nonce for security |

#### Request Example

```javascript
jQuery.ajax({
    url: ai_virtual_fitting_ajax.ajax_url,
    type: 'POST',
    data: {
        action: 'ai_virtual_fitting_check_credits',
        nonce: ai_virtual_fitting_ajax.nonce
    },
    success: function(response) {
        if (response.success) {
            console.log('Credits:', response.data.credits);
            console.log('Logged in:', response.data.logged_in);
        }
    }
});
```

#### Success Response

```json
{
    "success": true,
    "data": {
        "credits": 20,
        "logged_in": true
    }
}
```

### Get Products

Retrieve WooCommerce products for virtual fitting.

**Endpoint**: `ai_virtual_fitting_get_products`

**Method**: POST

**Authentication**: Optional

**Rate Limit**: None

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `action` | string | Yes | Must be `ai_virtual_fitting_get_products` |
| `nonce` | string | Yes | WordPress nonce for security |

#### Request Example

```javascript
jQuery.ajax({
    url: ai_virtual_fitting_ajax.ajax_url,
    type: 'POST',
    data: {
        action: 'ai_virtual_fitting_get_products',
        nonce: ai_virtual_fitting_ajax.nonce
    },
    success: function(response) {
        if (response.success) {
            console.log('Products:', response.data.products);
        }
    }
});
```

#### Success Response

```json
{
    "success": true,
    "data": {
        "products": [
            {
                "id": 123,
                "name": "Elegant Wedding Dress",
                "price": "$299.00",
                "description": "Beautiful lace wedding dress...",
                "image": ["https://example.com/image.jpg"],
                "gallery": ["https://example.com/image1.jpg", "https://example.com/image2.jpg"],
                "categories": ["wedding-dresses", "lace"]
            }
        ]
    }
}
```

### Add Credits to Cart

Add virtual fitting credits to the WooCommerce cart.

**Endpoint**: `ai_virtual_fitting_add_credits_to_cart`

**Method**: POST

**Authentication**: Optional

**Rate Limit**: 20 requests/minute

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `action` | string | Yes | Must be `ai_virtual_fitting_add_credits_to_cart` |
| `nonce` | string | Yes | WordPress nonce for security |

#### Request Example

```javascript
jQuery.ajax({
    url: ai_virtual_fitting_ajax.ajax_url,
    type: 'POST',
    data: {
        action: 'ai_virtual_fitting_add_credits_to_cart',
        nonce: ai_virtual_fitting_ajax.nonce
    },
    success: function(response) {
        if (response.success) {
            console.log('Cart total:', response.data.cart_total);
            console.log('Payment methods:', response.data.payment_methods);
        }
    }
});
```

#### Success Response

```json
{
    "success": true,
    "data": {
        "message": "Credits added to cart successfully",
        "cart_item_key": "abc123def456",
        "cart_total": "$10.00",
        "cart_total_text": "$10.00",
        "cart_count": 1,
        "product_id": 789,
        "payment_methods": [
            {
                "id": "bacs",
                "title": "Direct Bank Transfer",
                "description": "Make payment directly..."
            }
        ],
        "already_in_cart": false
    }
}
```

### Clear Cart

Clear the WooCommerce cart.

**Endpoint**: `ai_virtual_fitting_clear_cart`

**Method**: POST

**Authentication**: Optional

**Rate Limit**: 10 requests/minute

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `action` | string | Yes | Must be `ai_virtual_fitting_clear_cart` |
| `nonce` | string | Yes | WordPress nonce for security |

#### Request Example

```javascript
jQuery.ajax({
    url: ai_virtual_fitting_ajax.ajax_url,
    type: 'POST',
    data: {
        action: 'ai_virtual_fitting_clear_cart',
        nonce: ai_virtual_fitting_ajax.nonce
    },
    success: function(response) {
        if (response.success) {
            console.log('Cart cleared:', response.data.cleared_items);
        }
    }
});
```

#### Success Response

```json
{
    "success": true,
    "data": {
        "message": "Cart cleared successfully",
        "cart_count": 0,
        "cleared_items": 1,
        "was_empty": false
    }
}
```

### Load Checkout

Load the WooCommerce checkout form for embedded display.

**Endpoint**: `ai_virtual_fitting_load_checkout`

**Method**: POST

**Authentication**: Optional

**Rate Limit**: 10 requests/minute

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `action` | string | Yes | Must be `ai_virtual_fitting_load_checkout` |
| `nonce` | string | Yes | WordPress nonce for security |

#### Request Example

```javascript
jQuery.ajax({
    url: ai_virtual_fitting_ajax.ajax_url,
    type: 'POST',
    data: {
        action: 'ai_virtual_fitting_load_checkout',
        nonce: ai_virtual_fitting_ajax.nonce
    },
    success: function(response) {
        if (response.success) {
            // Inject checkout HTML into modal
            jQuery('#checkout-container').html(response.data.checkout_html);
        }
    }
});
```

#### Success Response

```json
{
    "success": true,
    "data": {
        "message": "Checkout form loaded successfully",
        "checkout_html": "<div class=\"woocommerce-checkout-wrapper\">...</div>",
        "cart_total": "$10.00",
        "cart_count": 1
    }
}
```

### Refresh Credits

Get real-time credit balance updates.

**Endpoint**: `ai_virtual_fitting_refresh_credits`

**Method**: POST

**Authentication**: Required

**Rate Limit**: None

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `action` | string | Yes | Must be `ai_virtual_fitting_refresh_credits` |
| `nonce` | string | Yes | WordPress nonce for security |

#### Request Example

```javascript
jQuery.ajax({
    url: ai_virtual_fitting_ajax.ajax_url,
    type: 'POST',
    data: {
        action: 'ai_virtual_fitting_refresh_credits',
        nonce: ai_virtual_fitting_ajax.nonce
    },
    success: function(response) {
        if (response.success) {
            console.log('Current credits:', response.data.credits);
            console.log('Free credits:', response.data.free_credits);
        }
    }
});
```

#### Success Response

```json
{
    "success": true,
    "data": {
        "credits": 22,
        "free_credits": 2
    }
}
```


---

## Google AI Studio Integration

The plugin integrates with Google AI Studio's Gemini 2.5 Flash Image model for AI-powered virtual fitting processing.

### API Endpoint

**Base URL**: `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent`

**Method**: POST

**Content-Type**: application/json

### Authentication

Google AI Studio uses API key authentication passed as a query parameter.

#### API Key Configuration

The API key is stored securely in WordPress options:

```php
// Get API key
$api_key = AI_Virtual_Fitting_Core::get_option('google_ai_api_key');

// Update API key
AI_Virtual_Fitting_Core::update_option('google_ai_api_key', $new_api_key);
```

#### Security Features

- **Encryption**: API keys are encrypted using AES-256-CBC before storage
- **Access Control**: Only administrators can view/modify API keys
- **Validation**: API keys are validated before use

### Request Format

The plugin sends multimodal requests combining text prompts with images.

#### Request Structure

```json
{
    "contents": [
        {
            "parts": [
                {
                    "text": "Create a realistic virtual fitting by seamlessly placing the wedding dress from the product image onto the person in the customer photo..."
                },
                {
                    "inline_data": {
                        "mime_type": "image/jpeg",
                        "data": "base64_encoded_customer_image"
                    }
                },
                {
                    "inline_data": {
                        "mime_type": "image/jpeg",
                        "data": "base64_encoded_product_image"
                    }
                }
            ]
        }
    ],
    "generationConfig": {
        "temperature": 0.4,
        "topK": 32,
        "topP": 1,
        "maxOutputTokens": 4096
    }
}
```

#### Image Encoding

Images are base64-encoded before sending:

```php
// Read image file
$image_data = file_get_contents($image_path);

// Base64 encode
$base64_image = base64_encode($image_data);

// Determine MIME type
$mime_type = mime_content_type($image_path);
```

### Response Format

#### Success Response

```json
{
    "candidates": [
        {
            "content": {
                "parts": [
                    {
                        "inline_data": {
                            "mime_type": "image/jpeg",
                            "data": "base64_encoded_result_image"
                        }
                    }
                ],
                "role": "model"
            },
            "finishReason": "STOP",
            "index": 0,
            "safetyRatings": [
                {
                    "category": "HARM_CATEGORY_SEXUALLY_EXPLICIT",
                    "probability": "NEGLIGIBLE"
                }
            ]
        }
    ],
    "usageMetadata": {
        "promptTokenCount": 1234,
        "candidatesTokenCount": 5678,
        "totalTokenCount": 6912
    }
}
```

#### Error Response

```json
{
    "error": {
        "code": 400,
        "message": "Invalid API key",
        "status": "INVALID_ARGUMENT"
    }
}
```

### Error Handling

The plugin implements comprehensive error handling for AI API interactions.

#### Common Error Scenarios

| Error Code | Description | Handling |
|------------|-------------|----------|
| 400 | Invalid request format | Log error, return user-friendly message |
| 401 | Invalid API key | Alert admin, prompt for key update |
| 403 | Quota exceeded | Implement retry with backoff |
| 429 | Rate limit exceeded | Queue request for later processing |
| 500 | Server error | Retry with exponential backoff |
| 503 | Service unavailable | Queue request, notify admin |

#### Retry Logic

```php
$max_retries = 3;
$retry_delay = 1; // seconds

for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
    $response = wp_remote_post($api_url, $args);
    
    if (!is_wp_error($response)) {
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            // Success
            break;
        } elseif ($status_code >= 500) {
            // Server error - retry with backoff
            sleep($retry_delay * $attempt);
            continue;
        } else {
            // Client error - don't retry
            break;
        }
    }
}
```

### Rate Limits

Google AI Studio enforces rate limits on API usage.

#### Default Limits

- **Requests per minute**: 60
- **Requests per day**: 1,500
- **Tokens per minute**: 32,000

#### Plugin Rate Management

The plugin implements client-side rate limiting to stay within quotas:

```php
// Check rate limit before API call
if (!AI_Virtual_Fitting_Security_Manager::check_rate_limit('google_ai_api', $user_id)) {
    // Queue request for later processing
    AI_Virtual_Fitting_Performance_Manager::queue_fitting_request($request_data);
    return new WP_Error('rate_limited', 'Request queued due to rate limits');
}
```

### Performance Optimization

#### Image Optimization

Images are optimized before sending to reduce API costs and improve response times:

- **Resize**: Maximum 1024x1024 pixels
- **Compression**: JPEG quality 85%
- **Format**: Convert to JPEG for consistency

#### Caching

API responses are cached to reduce redundant requests:

```php
// Check cache first
$cache_key = 'ai_result_' . md5($customer_image . $product_image);
$cached_result = AI_Virtual_Fitting_Performance_Manager::get_cache($cache_key);

if ($cached_result) {
    return $cached_result;
}

// Make API call and cache result
$result = $this->call_google_ai_api($request_data);
AI_Virtual_Fitting_Performance_Manager::set_cache($cache_key, $result, 3600);
```

### Monitoring and Logging

#### API Call Logging

All API interactions are logged for monitoring and debugging:

```php
error_log('AI Virtual Fitting - API Request: ' . json_encode(array(
    'user_id' => $user_id,
    'product_id' => $product_id,
    'timestamp' => time(),
    'request_size' => strlen($request_body)
)));
```

#### Usage Tracking

The plugin tracks API usage for analytics:

- Total API calls
- Success/failure rates
- Average response times
- Token usage
- Cost estimation


---

## WordPress Hooks

The plugin provides action hooks and filter hooks for extensibility and customization.

### Action Hooks

Action hooks allow you to execute custom code at specific points in the plugin's execution.

#### Plugin Initialization

**Hook**: `ai_virtual_fitting_init`

**Description**: Fires after the plugin is fully initialized

**Parameters**: None

**Usage Example**:

```php
add_action('ai_virtual_fitting_init', function() {
    // Custom initialization code
    error_log('AI Virtual Fitting plugin initialized');
});
```

#### Queue Processing

**Hook**: `ai_virtual_fitting_process_queue`

**Description**: Fires when the processing queue is executed

**Parameters**: None

**Usage Example**:

```php
add_action('ai_virtual_fitting_process_queue', function() {
    // Custom queue processing logic
    do_custom_queue_processing();
});
```

#### Cache Cleanup

**Hook**: `ai_virtual_fitting_cleanup_cache`

**Description**: Fires when expired cache entries are cleaned up

**Parameters**: None

**Usage Example**:

```php
add_action('ai_virtual_fitting_cleanup_cache', function() {
    // Custom cache cleanup logic
    clean_custom_cache();
});
```

#### Queue Cleanup

**Hook**: `ai_virtual_fitting_cleanup_queue`

**Description**: Fires when old queue items are cleaned up

**Parameters**: None

**Usage Example**:

```php
add_action('ai_virtual_fitting_cleanup_queue', function() {
    // Custom queue cleanup logic
    remove_old_queue_items();
});
```

#### Before Image Upload

**Hook**: `ai_virtual_fitting_before_upload`

**Description**: Fires before an image is uploaded

**Parameters**:
- `$user_id` (int): User ID performing the upload
- `$file_data` (array): File upload data

**Usage Example**:

```php
add_action('ai_virtual_fitting_before_upload', function($user_id, $file_data) {
    // Log upload attempt
    error_log("User {$user_id} uploading: {$file_data['name']}");
}, 10, 2);
```

#### After Image Upload

**Hook**: `ai_virtual_fitting_after_upload`

**Description**: Fires after an image is successfully uploaded

**Parameters**:
- `$user_id` (int): User ID who uploaded
- `$temp_filename` (string): Temporary filename
- `$file_path` (string): Full file path

**Usage Example**:

```php
add_action('ai_virtual_fitting_after_upload', function($user_id, $temp_filename, $file_path) {
    // Custom post-upload processing
    optimize_uploaded_image($file_path);
}, 10, 3);
```

#### Before Processing

**Hook**: `ai_virtual_fitting_before_process`

**Description**: Fires before AI processing begins

**Parameters**:
- `$user_id` (int): User ID
- `$product_id` (int): Product ID
- `$customer_image` (string): Customer image path

**Usage Example**:

```php
add_action('ai_virtual_fitting_before_process', function($user_id, $product_id, $customer_image) {
    // Track processing start
    update_user_meta($user_id, 'last_processing_start', time());
}, 10, 3);
```

#### After Processing

**Hook**: `ai_virtual_fitting_after_process`

**Description**: Fires after AI processing completes

**Parameters**:
- `$user_id` (int): User ID
- `$result_image` (string): Result image URL
- `$credits_remaining` (int): Remaining credits

**Usage Example**:

```php
add_action('ai_virtual_fitting_after_process', function($user_id, $result_image, $credits_remaining) {
    // Send notification
    send_processing_complete_email($user_id, $result_image);
}, 10, 3);
```

#### Credit Added

**Hook**: `ai_virtual_fitting_credits_added`

**Description**: Fires when credits are added to a user account

**Parameters**:
- `$user_id` (int): User ID
- `$credits_added` (int): Number of credits added
- `$new_balance` (int): New credit balance

**Usage Example**:

```php
add_action('ai_virtual_fitting_credits_added', function($user_id, $credits_added, $new_balance) {
    // Send notification
    notify_user_credits_added($user_id, $credits_added);
}, 10, 3);
```

#### Credit Deducted

**Hook**: `ai_virtual_fitting_credit_deducted`

**Description**: Fires when a credit is deducted from a user account

**Parameters**:
- `$user_id` (int): User ID
- `$new_balance` (int): New credit balance

**Usage Example**:

```php
add_action('ai_virtual_fitting_credit_deducted', function($user_id, $new_balance) {
    // Track usage
    log_credit_usage($user_id, $new_balance);
}, 10, 2);
```

### Filter Hooks

Filter hooks allow you to modify data before it's used by the plugin.

#### Image Upload Validation

**Hook**: `ai_virtual_fitting_validate_upload`

**Description**: Filter image upload validation rules

**Parameters**:
- `$is_valid` (bool): Whether the upload is valid
- `$file_data` (array): File upload data

**Return**: bool

**Usage Example**:

```php
add_filter('ai_virtual_fitting_validate_upload', function($is_valid, $file_data) {
    // Add custom validation
    if ($file_data['size'] > 5000000) { // 5MB
        return false;
    }
    return $is_valid;
}, 10, 2);
```

#### Maximum File Size

**Hook**: `ai_virtual_fitting_max_file_size`

**Description**: Filter maximum allowed file size

**Parameters**:
- `$max_size` (int): Maximum file size in bytes

**Return**: int

**Usage Example**:

```php
add_filter('ai_virtual_fitting_max_file_size', function($max_size) {
    // Increase max size to 15MB
    return 15 * 1024 * 1024;
});
```

#### Allowed Image Types

**Hook**: `ai_virtual_fitting_allowed_types`

**Description**: Filter allowed image MIME types

**Parameters**:
- `$types` (array): Array of allowed MIME types

**Return**: array

**Usage Example**:

```php
add_filter('ai_virtual_fitting_allowed_types', function($types) {
    // Add GIF support
    $types[] = 'image/gif';
    return $types;
});
```

#### Initial Credits

**Hook**: `ai_virtual_fitting_initial_credits`

**Description**: Filter the number of initial free credits

**Parameters**:
- `$credits` (int): Number of initial credits
- `$user_id` (int): User ID

**Return**: int

**Usage Example**:

```php
add_filter('ai_virtual_fitting_initial_credits', function($credits, $user_id) {
    // Give premium users more credits
    if (user_has_premium_membership($user_id)) {
        return 10;
    }
    return $credits;
}, 10, 2);
```

#### Credits Per Package

**Hook**: `ai_virtual_fitting_credits_per_package`

**Description**: Filter the number of credits in a package

**Parameters**:
- `$credits` (int): Credits per package

**Return**: int

**Usage Example**:

```php
add_filter('ai_virtual_fitting_credits_per_package', function($credits) {
    // Offer bonus credits during promotion
    return $credits + 5;
});
```

#### Package Price

**Hook**: `ai_virtual_fitting_package_price`

**Description**: Filter the price of a credit package

**Parameters**:
- `$price` (float): Package price
- `$user_id` (int): User ID

**Return**: float

**Usage Example**:

```php
add_filter('ai_virtual_fitting_package_price', function($price, $user_id) {
    // Offer discount to returning customers
    if (has_previous_purchase($user_id)) {
        return $price * 0.9; // 10% discount
    }
    return $price;
}, 10, 2);
```

#### AI Prompt

**Hook**: `ai_virtual_fitting_ai_prompt`

**Description**: Filter the AI prompt sent to Google AI Studio

**Parameters**:
- `$prompt` (string): AI prompt text
- `$product_id` (int): Product ID
- `$user_id` (int): User ID

**Return**: string

**Usage Example**:

```php
add_filter('ai_virtual_fitting_ai_prompt', function($prompt, $product_id, $user_id) {
    // Customize prompt for specific products
    $product = wc_get_product($product_id);
    if ($product->has_tag('vintage')) {
        $prompt .= ' Emphasize the vintage style and details.';
    }
    return $prompt;
}, 10, 3);
```

#### Cache Expiration

**Hook**: `ai_virtual_fitting_cache_expiration`

**Description**: Filter cache expiration time

**Parameters**:
- `$expiration` (int): Expiration time in seconds
- `$cache_key` (string): Cache key

**Return**: int

**Usage Example**:

```php
add_filter('ai_virtual_fitting_cache_expiration', function($expiration, $cache_key) {
    // Longer cache for product images
    if (strpos($cache_key, 'product_') === 0) {
        return 7200; // 2 hours
    }
    return $expiration;
}, 10, 2);
```

#### Result Image Quality

**Hook**: `ai_virtual_fitting_result_quality`

**Description**: Filter result image quality

**Parameters**:
- `$quality` (int): JPEG quality (0-100)

**Return**: int

**Usage Example**:

```php
add_filter('ai_virtual_fitting_result_quality', function($quality) {
    // Higher quality for premium users
    return 95;
});
```

#### Cache Get

**Hook**: `ai_virtual_fitting_cache_get`

**Description**: Filter cache retrieval (for custom cache implementations)

**Parameters**:
- `$value` (mixed): Cached value (false if not found)
- `$key` (string): Cache key

**Return**: mixed

**Usage Example**:

```php
add_filter('ai_virtual_fitting_cache_get', function($value, $key) {
    // Use custom cache backend
    return my_custom_cache_get($key);
}, 10, 2);
```

#### Cache Set

**Hook**: `ai_virtual_fitting_cache_set`

**Description**: Filter cache storage (for custom cache implementations)

**Parameters**:
- `$result` (bool): Whether cache was set
- `$key` (string): Cache key
- `$value` (mixed): Value to cache
- `$expiration` (int): Expiration time

**Return**: bool

**Usage Example**:

```php
add_filter('ai_virtual_fitting_cache_set', function($result, $key, $value, $expiration) {
    // Use custom cache backend
    return my_custom_cache_set($key, $value, $expiration);
}, 10, 4);
```


---

## WooCommerce Integration

The plugin integrates deeply with WooCommerce for product management, order processing, and credit purchases.

### Product API

#### Get Credit Product

Retrieve or create the virtual fitting credits product.

```php
$woocommerce_integration = new AI_Virtual_Fitting_WooCommerce_Integration();
$product_id = $woocommerce_integration->get_or_create_credits_product();
```

#### Check if Product is Credits

Determine if a product is the virtual fitting credits product.

```php
$is_credits = $woocommerce_integration->is_credits_product($product_id);
```

**Parameters**:
- `$product_id` (int): WooCommerce product ID

**Returns**: bool

#### Get Product Images

Retrieve product images for AI processing.

```php
$product_images = $this->get_product_images_for_ai($product_id);
```

**Parameters**:
- `$product_id` (int): WooCommerce product ID

**Returns**: array of image URLs

#### Product Metadata

Credits products have special metadata:

```php
// Get credits amount
$credits = get_post_meta($product_id, '_virtual_fitting_credits', true);

// Check if product is virtual fitting product
$is_vf_product = get_post_meta($product_id, '_virtual_fitting_product', true);
```

### Order API

#### Order Completion Hook

The plugin hooks into WooCommerce order completion to add credits.

**Hook**: `woocommerce_order_status_completed`

**Handler**: `AI_Virtual_Fitting_WooCommerce_Integration::handle_order_completed()`

**Usage Example**:

```php
// Automatically handled by plugin
// Credits are added when order status changes to 'completed'
```

#### Payment Complete Hook

Alternative hook for credit addition on payment completion.

**Hook**: `woocommerce_payment_complete`

**Handler**: `AI_Virtual_Fitting_WooCommerce_Integration::handle_payment_complete()`

#### Order Processing Logic

```php
/**
 * Process credits order
 * 
 * @param int $order_id WooCommerce order ID
 */
private function process_credits_order($order_id) {
    $order = wc_get_order($order_id);
    
    // Check if already processed
    if ($order->get_meta('_virtual_fitting_credits_processed') === 'yes') {
        return;
    }
    
    $customer_id = $order->get_customer_id();
    $credits_added = 0;
    
    // Process each order item
    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        
        if ($this->is_credits_product($product_id)) {
            $credits = get_post_meta($product_id, '_virtual_fitting_credits', true);
            $quantity = $item->get_quantity();
            $total_credits = $credits * $quantity;
            
            // Add credits
            $this->credit_manager->add_credits($customer_id, $total_credits);
            $credits_added += $total_credits;
        }
    }
    
    // Mark as processed
    $order->update_meta_data('_virtual_fitting_credits_processed', 'yes');
    $order->save();
}
```

#### Order Notes

The plugin adds order notes when credits are processed:

```php
$order->add_order_note(
    sprintf(
        __('Virtual Fitting Credits: Added %d credits to customer account.', 'ai-virtual-fitting'),
        $credits_added
    )
);
```

### Customer API

#### Get Customer Credits

Retrieve a customer's credit balance.

```php
$credit_manager = new AI_Virtual_Fitting_Credit_Manager();
$credits = $credit_manager->get_customer_credits($user_id);
```

**Parameters**:
- `$user_id` (int): WordPress user ID

**Returns**: int (credit balance)

#### Add Credits

Add credits to a customer account.

```php
$success = $credit_manager->add_credits($user_id, $amount);
```

**Parameters**:
- `$user_id` (int): WordPress user ID
- `$amount` (int): Number of credits to add

**Returns**: bool (success/failure)

#### Deduct Credit

Deduct one credit from a customer account.

```php
$success = $credit_manager->deduct_credit($user_id);
```

**Parameters**:
- `$user_id` (int): WordPress user ID

**Returns**: bool (success/failure)

#### Get Free Credits Remaining

Get the number of free credits remaining for a user.

```php
$free_credits = $credit_manager->get_free_credits_remaining($user_id);
```

**Parameters**:
- `$user_id` (int): WordPress user ID

**Returns**: int (free credits remaining)

### Cart Integration

#### Add Credits to Cart

Programmatically add credits to the cart.

```php
// Get credits product ID
$product_id = $woocommerce_integration->get_or_create_credits_product();

// Add to cart
$cart_item_key = WC()->cart->add_to_cart($product_id, 1);

if ($cart_item_key) {
    // Success
    WC()->cart->calculate_totals();
}
```

#### Check Cart for Credits

Check if credits are already in the cart.

```php
foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
    if ($woocommerce_integration->is_credits_product($cart_item['product_id'])) {
        // Credits found in cart
        $has_credits = true;
        break;
    }
}
```

#### Clear Cart

Clear the WooCommerce cart.

```php
WC()->cart->empty_cart();
WC()->cart->calculate_totals();
```

### Checkout Integration

#### Embedded Checkout

The plugin provides an embedded checkout experience within the virtual fitting interface.

**Features**:
- Modal-based checkout
- Streamlined form
- Real-time validation
- Payment method selection
- Order processing

#### Checkout Hooks

The plugin uses WooCommerce checkout hooks:

```php
// Before customer details
do_action('woocommerce_checkout_before_customer_details');

// Billing fields
do_action('woocommerce_checkout_billing');

// Shipping fields
do_action('woocommerce_checkout_shipping');

// After customer details
do_action('woocommerce_checkout_after_customer_details');

// Before order review
do_action('woocommerce_checkout_before_order_review');

// After order review
do_action('woocommerce_checkout_after_order_review');
```

### Payment Gateway Integration

#### Available Payment Methods

Get available payment methods for checkout.

```php
private function get_available_payment_methods() {
    $gateways = WC()->payment_gateways->get_available_payment_gateways();
    $methods = array();
    
    foreach ($gateways as $gateway) {
        $methods[] = array(
            'id' => $gateway->id,
            'title' => $gateway->get_title(),
            'description' => $gateway->get_description(),
            'icon' => $gateway->get_icon()
        );
    }
    
    return $methods;
}
```

#### Payment Method Fees

Add fees based on payment method selection.

**Hook**: `woocommerce_cart_calculate_fees`

**Usage Example**:

```php
add_action('woocommerce_cart_calculate_fees', function() {
    $chosen_gateway = WC()->session->get('chosen_payment_method');
    
    if ($chosen_gateway === 'cod') {
        // Add cash on delivery fee
        WC()->cart->add_fee(__('COD Fee', 'ai-virtual-fitting'), 2.00);
    }
});
```

### Integration Points

#### Product Catalog

The plugin integrates with the WooCommerce product catalog to:

- Retrieve wedding dress products
- Display product images
- Show product details
- Filter by categories

#### Order Management

Integration with WooCommerce order management:

- Process credit purchases
- Track order status
- Add order notes
- Send notifications

#### Customer Management

Integration with WooCommerce customer data:

- Link credits to customer accounts
- Track purchase history
- Manage customer metadata

### Custom Queries

#### Get Products for Virtual Fitting

```php
$args = array(
    'post_type' => 'product',
    'posts_per_page' => 20,
    'post_status' => 'publish',
    'post__not_in' => array($credit_product_id) // Exclude credits product
);

$products = get_posts($args);
```

#### Get Customer Orders with Credits

```php
$orders = wc_get_orders(array(
    'customer_id' => $user_id,
    'status' => 'completed',
    'meta_key' => '_virtual_fitting_credits_processed',
    'meta_value' => 'yes'
));
```

#### Get Credit Purchase History

```php
global $wpdb;
$table_name = $wpdb->prefix . 'virtual_fitting_credits';

$history = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_name 
     WHERE user_id = %d 
     AND transaction_type = 'purchase'
     ORDER BY created_at DESC",
    $user_id
));
```


---

## Authentication

The plugin implements multiple layers of authentication and security to protect user data and prevent unauthorized access.

### Nonce Verification

WordPress nonces are used to verify that requests originate from legitimate sources.

#### Generating Nonces

Nonces are automatically generated and localized in JavaScript:

```php
wp_localize_script('ai-virtual-fitting-modern-script', 'ai_virtual_fitting_ajax', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('ai_virtual_fitting_nonce'),
    'user_logged_in' => is_user_logged_in()
));
```

#### Verifying Nonces

All AJAX endpoints verify nonces before processing:

```php
public function handle_image_upload() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'ai_virtual_fitting_nonce')) {
        wp_send_json_error(array(
            'message' => 'Security check failed',
            'error_code' => 'SECURITY_FAILED'
        ));
    }
    
    // Continue processing...
}
```

#### Nonce Lifetime

WordPress nonces have a default lifetime of 24 hours. The plugin respects this setting.

### User Authentication

#### Checking Login Status

Most endpoints require users to be logged in:

```php
if (!is_user_logged_in()) {
    wp_send_json_error(array(
        'message' => 'Please log in to use virtual fitting',
        'error_code' => 'AUTH_REQUIRED'
    ));
}
```

#### Getting Current User

Retrieve the current user ID:

```php
$user_id = get_current_user_id();

if (!$user_id) {
    // User not logged in
    return false;
}
```

#### User Roles and Capabilities

The plugin respects WordPress user roles and capabilities.

**Allowed Roles** (configurable):
- Customer
- Subscriber
- Administrator

**Checking Capabilities**:

```php
// Check if user can use virtual fitting
if (!current_user_can('read')) {
    wp_send_json_error(array(
        'message' => 'Insufficient permissions',
        'error_code' => 'INSUFFICIENT_PERMISSIONS'
    ));
}

// Check if user can manage settings (admin only)
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized access');
}
```

### Capability Checks

#### Admin Capabilities

Administrative functions require elevated permissions:

```php
// Check admin capability
if (!current_user_can('manage_options')) {
    wp_send_json_error(array(
        'message' => 'Admin access required',
        'error_code' => 'ADMIN_REQUIRED'
    ));
}
```

#### Custom Capabilities

The plugin can define custom capabilities:

```php
// Add custom capability
$role = get_role('customer');
$role->add_cap('use_virtual_fitting');

// Check custom capability
if (!current_user_can('use_virtual_fitting')) {
    wp_send_json_error(array(
        'message' => 'Virtual fitting access denied',
        'error_code' => 'ACCESS_DENIED'
    ));
}
```

### API Key Management

#### Storing API Keys

Google AI Studio API keys are stored securely:

```php
// Encrypt and store API key
$encrypted_key = AI_Virtual_Fitting_Security_Manager::encrypt_api_key($api_key);
update_option('ai_virtual_fitting_google_ai_api_key', $encrypted_key);
```

#### Retrieving API Keys

API keys are decrypted when needed:

```php
// Get and decrypt API key
$encrypted_key = get_option('ai_virtual_fitting_google_ai_api_key');
$api_key = AI_Virtual_Fitting_Security_Manager::decrypt_api_key($encrypted_key);
```

#### API Key Encryption

The plugin uses AES-256-CBC encryption for API keys:

```php
class AI_Virtual_Fitting_Security_Manager {
    
    /**
     * Encrypt API key
     */
    public static function encrypt_api_key($api_key) {
        $encryption_key = self::get_encryption_key();
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        
        $encrypted = openssl_encrypt(
            $api_key,
            'aes-256-cbc',
            $encryption_key,
            0,
            $iv
        );
        
        return base64_encode($encrypted . '::' . $iv);
    }
    
    /**
     * Decrypt API key
     */
    public static function decrypt_api_key($encrypted_data) {
        $encryption_key = self::get_encryption_key();
        list($encrypted, $iv) = explode('::', base64_decode($encrypted_data), 2);
        
        return openssl_decrypt(
            $encrypted,
            'aes-256-cbc',
            $encryption_key,
            0,
            $iv
        );
    }
    
    /**
     * Get encryption key
     */
    private static function get_encryption_key() {
        // Use WordPress security keys
        return hash('sha256', AUTH_KEY . SECURE_AUTH_KEY);
    }
}
```

#### API Key Validation

Validate API keys before use:

```php
public function validate_api_key($api_key) {
    // Check format
    if (empty($api_key) || strlen($api_key) < 20) {
        return false;
    }
    
    // Test API key with simple request
    $test_response = $this->test_api_connection($api_key);
    
    return !is_wp_error($test_response);
}
```

### Session Management

#### WordPress Sessions

The plugin uses WordPress's built-in session management:

```php
// Start session if needed
if (!session_id()) {
    session_start();
}

// Store data in session
$_SESSION['virtual_fitting_temp_data'] = $data;

// Retrieve from session
$data = isset($_SESSION['virtual_fitting_temp_data']) 
    ? $_SESSION['virtual_fitting_temp_data'] 
    : null;
```

#### WooCommerce Sessions

For cart-related operations, WooCommerce sessions are used:

```php
// Store in WooCommerce session
WC()->session->set('virtual_fitting_data', $data);

// Retrieve from WooCommerce session
$data = WC()->session->get('virtual_fitting_data');
```

### Security Best Practices

#### Input Sanitization

All user input is sanitized:

```php
// Sanitize text input
$temp_filename = sanitize_text_field($_POST['temp_file']);

// Sanitize integer
$product_id = intval($_POST['product_id']);

// Sanitize email
$email = sanitize_email($_POST['email']);

// Sanitize URL
$url = esc_url_raw($_POST['url']);
```

#### Output Escaping

All output is escaped:

```php
// Escape HTML
echo esc_html($user_input);

// Escape attributes
echo '<div data-id="' . esc_attr($product_id) . '">';

// Escape JavaScript
echo '<script>var data = ' . wp_json_encode($data) . ';</script>';

// Escape URL
echo '<a href="' . esc_url($url) . '">Link</a>';
```

#### SQL Injection Prevention

Use prepared statements for database queries:

```php
global $wpdb;

// Prepared statement
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}virtual_fitting_credits 
     WHERE user_id = %d 
     AND created_at > %s",
    $user_id,
    $date
));
```

#### CSRF Protection

Nonces provide CSRF protection:

```php
// Generate nonce for form
wp_nonce_field('ai_virtual_fitting_action', 'ai_virtual_fitting_nonce');

// Verify nonce on submission
if (!wp_verify_nonce($_POST['ai_virtual_fitting_nonce'], 'ai_virtual_fitting_action')) {
    wp_die('Security check failed');
}
```

#### XSS Prevention

Escape all dynamic content:

```php
// Safe output
echo '<div class="message">' . esc_html($message) . '</div>';

// Safe JSON output
wp_localize_script('my-script', 'myData', array(
    'message' => esc_js($message)
));
```

### Rate Limiting

Rate limiting prevents abuse and ensures fair resource usage.

#### Implementation

```php
class AI_Virtual_Fitting_Security_Manager {
    
    /**
     * Check rate limit
     */
    public static function check_rate_limit($action, $user_id) {
        $limits = array(
            'upload_image' => array('limit' => 10, 'period' => 60),
            'process_fitting' => array('limit' => 5, 'period' => 60),
            'add_to_cart' => array('limit' => 20, 'period' => 60)
        );
        
        if (!isset($limits[$action])) {
            return true;
        }
        
        $limit = $limits[$action]['limit'];
        $period = $limits[$action]['period'];
        
        $key = "rate_limit_{$action}_{$user_id}";
        $count = get_transient($key);
        
        if ($count === false) {
            set_transient($key, 1, $period);
            return true;
        }
        
        if ($count >= $limit) {
            return false;
        }
        
        set_transient($key, $count + 1, $period);
        return true;
    }
}
```

#### Rate Limit Response

When rate limit is exceeded:

```php
if (!AI_Virtual_Fitting_Security_Manager::check_rate_limit('upload_image', $user_id)) {
    wp_send_json_error(array(
        'message' => 'Too many requests. Please wait a few minutes and try again.',
        'error_code' => 'RATE_LIMIT_EXCEEDED'
    ));
}
```

### IP Address Tracking

Track IP addresses for security logging:

```php
/**
 * Get client IP address
 */
private function get_client_ip() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    return sanitize_text_field($ip);
}
```

### Security Logging

Log security events for monitoring:

```php
/**
 * Log security event
 */
private function log_security_event($event, $data) {
    if (AI_Virtual_Fitting_Core::get_option('enable_logging', true)) {
        error_log('AI Virtual Fitting Security: ' . $event . ' - ' . json_encode($data));
    }
}

// Usage
$this->log_security_event('Failed login attempt', array(
    'user_id' => $user_id,
    'ip' => $this->get_client_ip(),
    'timestamp' => time()
));
```


---

## Error Codes Reference

This section provides a comprehensive reference of all error codes used by the plugin, their meanings, and recommended resolution procedures.

### Error Code Format

Error codes follow a consistent format:

```json
{
    "success": false,
    "data": {
        "message": "Human-readable error message",
        "error_code": "ERROR_CODE_IDENTIFIER",
        "retry_allowed": true|false
    }
}
```

### Security Errors

#### SECURITY_FAILED

**Description**: Nonce verification failed

**Cause**: Invalid or expired nonce token

**Resolution**:
1. Refresh the page to get a new nonce
2. Ensure JavaScript is enabled
3. Check for browser extensions blocking requests

**Retry Allowed**: No

**Example**:
```json
{
    "success": false,
    "data": {
        "message": "Security check failed",
        "error_code": "SECURITY_FAILED"
    }
}
```

#### AUTH_REQUIRED

**Description**: User authentication required

**Cause**: User is not logged in

**Resolution**:
1. Log in to WordPress
2. Create an account if needed
3. Check session cookies are enabled

**Retry Allowed**: No

**Example**:
```json
{
    "success": false,
    "data": {
        "message": "Please log in to use virtual fitting",
        "error_code": "AUTH_REQUIRED"
    }
}
```

#### INSUFFICIENT_PERMISSIONS

**Description**: User lacks required permissions

**Cause**: User role doesn't have necessary capabilities

**Resolution**:
1. Contact site administrator
2. Verify account status
3. Check user role assignments

**Retry Allowed**: No

#### RATE_LIMIT_EXCEEDED

**Description**: Too many requests in short time

**Cause**: Rate limit threshold exceeded

**Resolution**:
1. Wait 1-5 minutes before retrying
2. Reduce request frequency
3. Contact support if issue persists

**Retry Allowed**: Yes (after delay)

**Example**:
```json
{
    "success": false,
    "data": {
        "message": "Too many requests. Please wait a few minutes and try again.",
        "error_code": "RATE_LIMIT_EXCEEDED"
    }
}
```

### Upload Errors

#### UPLOAD_FAILED

**Description**: Image upload failed

**Cause**: File upload error occurred

**Resolution**:
1. Check file size (max 10MB)
2. Verify file format (JPEG, PNG, WebP)
3. Ensure stable internet connection
4. Try a different image

**Retry Allowed**: Yes

**Example**:
```json
{
    "success": false,
    "data": {
        "message": "Failed to save uploaded image",
        "error_code": "UPLOAD_FAILED"
    }
}
```

#### VALIDATION_FAILED

**Description**: Image validation failed

**Cause**: Image doesn't meet requirements

**Common Reasons**:
- File size exceeds 10MB
- Invalid file format
- Corrupted image file
- Dimensions too small/large

**Resolution**:
1. Reduce file size if too large
2. Convert to JPEG, PNG, or WebP
3. Use a different image
4. Check image isn't corrupted

**Retry Allowed**: Yes

**Example**:
```json
{
    "success": false,
    "data": {
        "message": "File size exceeds maximum allowed (10MB)",
        "error_code": "VALIDATION_FAILED"
    }
}
```

#### TEMP_DIR_FAILED

**Description**: Failed to create temporary directory

**Cause**: Server permission or disk space issue

**Resolution**:
1. Contact site administrator
2. Check server disk space
3. Verify upload directory permissions

**Retry Allowed**: Yes

#### SAVE_FAILED

**Description**: Failed to save uploaded file

**Cause**: Server write permission issue

**Resolution**:
1. Contact site administrator
2. Check directory permissions
3. Verify disk space available

**Retry Allowed**: Yes

### Processing Errors

#### INSUFFICIENT_CREDITS

**Description**: User has no credits remaining

**Cause**: Credit balance is zero

**Resolution**:
1. Purchase more credits
2. Check credit balance
3. Verify previous purchase completed

**Retry Allowed**: No

**Example**:
```json
{
    "success": false,
    "data": {
        "message": "Insufficient credits",
        "credits": 0
    }
}
```

#### PROCESSING_ERROR

**Description**: AI processing failed

**Cause**: Error during AI image processing

**Common Reasons**:
- API timeout
- Invalid image format
- AI service unavailable
- Network error

**Resolution**:
1. Retry the request
2. Try a different image
3. Check internet connection
4. Contact support if persists

**Retry Allowed**: Yes

**Example**:
```json
{
    "success": false,
    "data": {
        "message": "Error processing virtual fitting. Please try again.",
        "error_code": "PROCESSING_ERROR"
    }
}
```

#### MISSING_PARAMETERS

**Description**: Required parameters missing

**Cause**: API request missing required data

**Resolution**:
1. Ensure all required fields are filled
2. Check JavaScript console for errors
3. Refresh page and try again

**Retry Allowed**: Yes

#### PRODUCT_NOT_FOUND

**Description**: Product doesn't exist

**Cause**: Invalid product ID

**Resolution**:
1. Select a different product
2. Refresh product list
3. Contact support

**Retry Allowed**: No

#### IMAGE_NOT_FOUND

**Description**: Uploaded image not found

**Cause**: Temporary file expired or deleted

**Resolution**:
1. Upload image again
2. Process immediately after upload
3. Check server temp file cleanup settings

**Retry Allowed**: Yes

### Cart and Checkout Errors

#### WOOCOMMERCE_INACTIVE

**Description**: WooCommerce plugin not active

**Cause**: WooCommerce is disabled or not installed

**Resolution**:
1. Contact site administrator
2. Activate WooCommerce plugin
3. Install WooCommerce if needed

**Retry Allowed**: No

**Example**:
```json
{
    "success": false,
    "data": {
        "message": "WooCommerce is not active",
        "error_code": "WOOCOMMERCE_INACTIVE"
    }
}
```

#### CART_ADD_FAILED

**Description**: Failed to add item to cart

**Cause**: WooCommerce cart error

**Resolution**:
1. Clear browser cache
2. Refresh page
3. Try again
4. Check WooCommerce settings

**Retry Allowed**: Yes

**Example**:
```json
{
    "success": false,
    "data": {
        "message": "Failed to add credits to cart",
        "error_code": "CART_ADD_FAILED"
    }
}
```

#### CART_NOT_AVAILABLE

**Description**: Shopping cart not accessible

**Cause**: WooCommerce cart not initialized

**Resolution**:
1. Refresh the page
2. Clear browser cache
3. Check WooCommerce is active

**Retry Allowed**: Yes

#### CART_CONFLICT_OTHER_PRODUCTS

**Description**: Cart contains non-credit items

**Cause**: Attempting to add credits with other products in cart

**Resolution**:
1. Clear cart and try again
2. Complete current purchase first
3. Purchase credits separately

**Retry Allowed**: Yes (after clearing cart)

**Example**:
```json
{
    "success": false,
    "data": {
        "message": "Your cart contains other items. Adding credits will clear your current cart.",
        "error_code": "CART_CONFLICT_OTHER_PRODUCTS",
        "cart_action": "clear_and_add"
    }
}
```

#### PRODUCT_CREATION_FAILED

**Description**: Failed to create credits product

**Cause**: WooCommerce product creation error

**Resolution**:
1. Contact site administrator
2. Check WooCommerce permissions
3. Verify database connectivity

**Retry Allowed**: Yes

#### PRODUCT_NOT_PURCHASABLE

**Description**: Product cannot be purchased

**Cause**: Product is not available for sale

**Resolution**:
1. Contact site administrator
2. Check product status
3. Verify product settings

**Retry Allowed**: No

#### EMPTY_CART

**Description**: Cart is empty at checkout

**Cause**: Cart was cleared before checkout

**Resolution**:
1. Add credits to cart again
2. Refresh page
3. Try checkout again

**Retry Allowed**: Yes

#### CHECKOUT_VALIDATION_FAILED

**Description**: Checkout form validation failed

**Cause**: Required fields missing or invalid

**Resolution**:
1. Fill all required fields
2. Check email format
3. Verify phone number format
4. Complete billing address

**Retry Allowed**: Yes

### System Errors

#### DATABASE_ERROR

**Description**: Database operation failed

**Cause**: Database connection or query error

**Resolution**:
1. Contact site administrator
2. Check database connectivity
3. Verify database permissions

**Retry Allowed**: Yes

#### API_ERROR

**Description**: External API error

**Cause**: Google AI Studio API error

**Resolution**:
1. Retry the request
2. Check API key configuration
3. Verify API quota
4. Contact support

**Retry Allowed**: Yes

#### TIMEOUT_ERROR

**Description**: Request timeout

**Cause**: Operation took too long

**Resolution**:
1. Retry with smaller image
2. Check internet connection
3. Try during off-peak hours

**Retry Allowed**: Yes

#### SERVER_ERROR

**Description**: Internal server error

**Cause**: Unexpected server-side error

**Resolution**:
1. Retry the request
2. Contact support if persists
3. Check error logs

**Retry Allowed**: Yes

### Error Handling Best Practices

#### For Developers

1. **Always check error codes**: Don't rely solely on messages
2. **Implement retry logic**: For errors with `retry_allowed: true`
3. **Log errors**: Track error patterns for debugging
4. **Provide user feedback**: Display clear error messages
5. **Handle edge cases**: Account for network failures, timeouts

#### Example Error Handling

```javascript
jQuery.ajax({
    url: ai_virtual_fitting_ajax.ajax_url,
    type: 'POST',
    data: requestData,
    success: function(response) {
        if (response.success) {
            // Handle success
            handleSuccess(response.data);
        } else {
            // Handle error
            handleError(response.data);
        }
    },
    error: function(xhr, status, error) {
        // Handle AJAX error
        handleAjaxError(error);
    }
});

function handleError(errorData) {
    const errorCode = errorData.error_code;
    const message = errorData.message;
    const retryAllowed = errorData.retry_allowed;
    
    // Display error to user
    showErrorMessage(message);
    
    // Log error
    console.error('Error:', errorCode, message);
    
    // Implement retry logic if allowed
    if (retryAllowed) {
        setTimeout(function() {
            retryRequest();
        }, 2000);
    }
}
```

### Error Logging

Errors are logged for debugging and monitoring:

```php
error_log('AI Virtual Fitting Error: ' . json_encode(array(
    'error_code' => $error_code,
    'message' => $message,
    'user_id' => $user_id,
    'timestamp' => time(),
    'context' => $context
)));
```

### Support Contact

For unresolved errors:

1. Check documentation
2. Review error logs
3. Contact support with:
   - Error code
   - Error message
   - Steps to reproduce
   - Browser/device information


---

## Code Examples

This section provides practical code examples for common integration scenarios.

### PHP Examples

#### Complete Virtual Fitting Workflow

```php
<?php
/**
 * Complete virtual fitting workflow example
 */

// Initialize components
$credit_manager = new AI_Virtual_Fitting_Credit_Manager();
$image_processor = new AI_Virtual_Fitting_Image_Processor();
$woocommerce_integration = new AI_Virtual_Fitting_WooCommerce_Integration();

// Get current user
$user_id = get_current_user_id();

// Check if user has credits
$credits = $credit_manager->get_customer_credits($user_id);

if ($credits <= 0) {
    // Redirect to purchase credits
    $product_id = $woocommerce_integration->get_or_create_credits_product();
    WC()->cart->add_to_cart($product_id, 1);
    wp_redirect(wc_get_checkout_url());
    exit;
}

// Process virtual fitting
$customer_image_path = '/path/to/customer/image.jpg';
$product_images = array('/path/to/product/image.jpg');

$result = $image_processor->process_virtual_fitting(
    $customer_image_path,
    $product_images
);

if (is_wp_error($result)) {
    // Handle error
    wp_die($result->get_error_message());
}

// Deduct credit
$credit_manager->deduct_credit($user_id);

// Display result
echo '<img src="' . esc_url($result['result_image_url']) . '" alt="Virtual Fitting Result">';
echo '<p>Remaining credits: ' . $credit_manager->get_customer_credits($user_id) . '</p>';
?>
```

#### Custom Credit Package

```php
<?php
/**
 * Create a custom credit package
 */

function create_custom_credit_package($credits, $price, $name) {
    $product = new WC_Product_Simple();
    $product->set_name($name);
    $product->set_description("Purchase {$credits} virtual fitting credits.");
    $product->set_short_description("{$credits} virtual fitting credits.");
    $product->set_regular_price($price);
    $product->set_price($price);
    $product->set_virtual(true);
    $product->set_catalog_visibility('visible'); // Make visible in catalog
    $product->set_status('publish');
    
    // Set credits metadata
    $product->add_meta_data('_virtual_fitting_credits', $credits, true);
    $product->add_meta_data('_virtual_fitting_product', 'yes', true);
    
    $product_id = $product->save();
    
    return $product_id;
}

// Usage
$product_id = create_custom_credit_package(50, 20.00, 'Virtual Fitting Credits - 50 Pack');
?>
```

#### Hook into Credit Addition

```php
<?php
/**
 * Send email notification when credits are added
 */

add_action('ai_virtual_fitting_credits_added', function($user_id, $credits_added, $new_balance) {
    $user = get_userdata($user_id);
    $to = $user->user_email;
    $subject = 'Virtual Fitting Credits Added';
    $message = "Hello {$user->display_name},\n\n";
    $message .= "{$credits_added} credits have been added to your account.\n";
    $message .= "Your new balance is: {$new_balance} credits.\n\n";
    $message .= "Thank you for your purchase!";
    
    wp_mail($to, $subject, $message);
}, 10, 3);
?>
```

#### Custom Image Validation

```php
<?php
/**
 * Add custom image validation rules
 */

add_filter('ai_virtual_fitting_validate_upload', function($is_valid, $file_data) {
    // Check image dimensions
    $image_info = getimagesize($file_data['tmp_name']);
    
    if ($image_info) {
        $width = $image_info[0];
        $height = $image_info[1];
        
        // Require minimum dimensions
        if ($width < 800 || $height < 600) {
            return new WP_Error(
                'invalid_dimensions',
                'Image must be at least 800x600 pixels'
            );
        }
        
        // Require portrait orientation
        if ($width > $height) {
            return new WP_Error(
                'invalid_orientation',
                'Image must be in portrait orientation'
            );
        }
    }
    
    return $is_valid;
}, 10, 2);
?>
```

#### Programmatic Credit Management

```php
<?php
/**
 * Manage user credits programmatically
 */

// Add credits to user
function add_user_credits($user_id, $amount, $reason = 'manual') {
    $credit_manager = new AI_Virtual_Fitting_Credit_Manager();
    
    $success = $credit_manager->add_credits($user_id, $amount);
    
    if ($success) {
        // Log the transaction
        error_log("Added {$amount} credits to user {$user_id}. Reason: {$reason}");
        
        // Add user note
        add_user_meta($user_id, 'credit_transaction', array(
            'amount' => $amount,
            'reason' => $reason,
            'timestamp' => time()
        ));
    }
    
    return $success;
}

// Deduct credits from user
function deduct_user_credits($user_id, $amount) {
    $credit_manager = new AI_Virtual_Fitting_Credit_Manager();
    
    // Check if user has enough credits
    $current_balance = $credit_manager->get_customer_credits($user_id);
    
    if ($current_balance < $amount) {
        return new WP_Error('insufficient_credits', 'User does not have enough credits');
    }
    
    // Deduct credits one at a time
    for ($i = 0; $i < $amount; $i++) {
        $credit_manager->deduct_credit($user_id);
    }
    
    return true;
}

// Get credit history
function get_user_credit_history($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'virtual_fitting_credits';
    
    $history = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name 
         WHERE user_id = %d 
         ORDER BY created_at DESC 
         LIMIT 50",
        $user_id
    ));
    
    return $history;
}
?>
```

### JavaScript Examples

#### Complete AJAX Workflow

```javascript
/**
 * Complete virtual fitting AJAX workflow
 */

(function($) {
    'use strict';
    
    const VirtualFitting = {
        
        init: function() {
            this.bindEvents();
            this.checkCredits();
        },
        
        bindEvents: function() {
            $('#upload-image').on('change', this.handleImageUpload.bind(this));
            $('#process-fitting').on('click', this.processFitting.bind(this));
            $('#buy-credits').on('click', this.buyCredits.bind(this));
        },
        
        checkCredits: function() {
            $.ajax({
                url: ai_virtual_fitting_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ai_virtual_fitting_check_credits',
                    nonce: ai_virtual_fitting_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#credit-balance').text(response.data.credits);
                        
                        if (response.data.credits === 0) {
                            $('#process-fitting').prop('disabled', true);
                            $('#buy-credits').show();
                        }
                    }
                }
            });
        },
        
        handleImageUpload: function(e) {
            const file = e.target.files[0];
            
            if (!file) return;
            
            // Validate file size
            if (file.size > 10 * 1024 * 1024) {
                alert('File size must be less than 10MB');
                return;
            }
            
            // Validate file type
            const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                alert('Please upload a JPEG, PNG, or WebP image');
                return;
            }
            
            // Upload image
            const formData = new FormData();
            formData.append('action', 'ai_virtual_fitting_upload');
            formData.append('nonce', ai_virtual_fitting_ajax.nonce);
            formData.append('customer_image', file);
            
            $('#upload-status').text('Uploading...');
            
            $.ajax({
                url: ai_virtual_fitting_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $('#upload-status').text('Upload complete!');
                        $('#temp-file').val(response.data.temp_file);
                        $('#process-fitting').prop('disabled', false);
                        
                        // Show preview
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            $('#image-preview').attr('src', e.target.result).show();
                        };
                        reader.readAsDataURL(file);
                    } else {
                        $('#upload-status').text('Upload failed: ' + response.data.message);
                    }
                },
                error: function() {
                    $('#upload-status').text('Upload failed. Please try again.');
                }
            });
        },
        
        processFitting: function() {
            const tempFile = $('#temp-file').val();
            const productId = $('#product-select').val();
            
            if (!tempFile || !productId) {
                alert('Please upload an image and select a product');
                return;
            }
            
            $('#process-status').text('Processing...').show();
            $('#process-fitting').prop('disabled', true);
            
            $.ajax({
                url: ai_virtual_fitting_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ai_virtual_fitting_process',
                    nonce: ai_virtual_fitting_ajax.nonce,
                    temp_file: tempFile,
                    product_id: productId
                },
                success: function(response) {
                    if (response.success) {
                        $('#process-status').text('Processing complete!');
                        $('#result-image').attr('src', response.data.result_image).show();
                        $('#credit-balance').text(response.data.credits);
                        
                        // Enable download
                        $('#download-result').attr('href', response.data.result_image).show();
                    } else {
                        $('#process-status').text('Processing failed: ' + response.data.message);
                        
                        if (response.data.credits === 0) {
                            $('#buy-credits').show();
                        }
                    }
                    
                    $('#process-fitting').prop('disabled', false);
                },
                error: function() {
                    $('#process-status').text('Processing failed. Please try again.');
                    $('#process-fitting').prop('disabled', false);
                }
            });
        },
        
        buyCredits: function() {
            $.ajax({
                url: ai_virtual_fitting_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ai_virtual_fitting_add_credits_to_cart',
                    nonce: ai_virtual_fitting_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Redirect to checkout
                        window.location.href = ai_virtual_fitting_ajax.checkout_url;
                    } else {
                        alert('Failed to add credits to cart: ' + response.data.message);
                    }
                }
            });
        }
    };
    
    $(document).ready(function() {
        VirtualFitting.init();
    });
    
})(jQuery);
```

#### Error Handling with Retry Logic

```javascript
/**
 * AJAX request with automatic retry logic
 */

function ajaxWithRetry(options, maxRetries = 3) {
    let attempts = 0;
    
    function makeRequest() {
        attempts++;
        
        return $.ajax(options)
            .fail(function(xhr, status, error) {
                if (attempts < maxRetries) {
                    // Exponential backoff
                    const delay = Math.pow(2, attempts) * 1000;
                    
                    console.log(`Request failed. Retrying in ${delay}ms... (Attempt ${attempts}/${maxRetries})`);
                    
                    setTimeout(function() {
                        makeRequest();
                    }, delay);
                } else {
                    console.error('Max retries reached. Request failed.');
                }
            });
    }
    
    return makeRequest();
}

// Usage
ajaxWithRetry({
    url: ai_virtual_fitting_ajax.ajax_url,
    type: 'POST',
    data: {
        action: 'ai_virtual_fitting_process',
        nonce: ai_virtual_fitting_ajax.nonce,
        temp_file: tempFile,
        product_id: productId
    },
    success: function(response) {
        console.log('Success:', response);
    }
}, 3);
```

#### Real-time Credit Updates

```javascript
/**
 * Poll for credit updates
 */

function startCreditPolling(interval = 5000) {
    setInterval(function() {
        $.ajax({
            url: ai_virtual_fitting_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ai_virtual_fitting_refresh_credits',
                nonce: ai_virtual_fitting_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const credits = response.data.credits;
                    const freeCredits = response.data.free_credits;
                    
                    // Update UI
                    $('#credit-balance').text(credits);
                    $('#free-credits').text(freeCredits);
                    
                    // Show notification if credits changed
                    const previousCredits = parseInt($('#credit-balance').data('previous') || 0);
                    if (credits !== previousCredits) {
                        showNotification('Your credit balance has been updated!');
                        $('#credit-balance').data('previous', credits);
                    }
                }
            }
        });
    }, interval);
}

// Start polling when page loads
$(document).ready(function() {
    startCreditPolling(5000); // Poll every 5 seconds
});
```

### Integration Examples

#### WordPress Plugin Integration

```php
<?php
/**
 * Plugin Name: Virtual Fitting Extension
 * Description: Extends AI Virtual Fitting with custom features
 */

// Hook into virtual fitting initialization
add_action('ai_virtual_fitting_init', 'my_virtual_fitting_init');

function my_virtual_fitting_init() {
    // Add custom functionality
    add_filter('ai_virtual_fitting_initial_credits', 'my_custom_initial_credits', 10, 2);
    add_action('ai_virtual_fitting_after_process', 'my_after_process_handler', 10, 3);
}

// Give VIP users more initial credits
function my_custom_initial_credits($credits, $user_id) {
    if (user_has_vip_membership($user_id)) {
        return 20; // VIP users get 20 free credits
    }
    return $credits;
}

// Custom post-processing
function my_after_process_handler($user_id, $result_image, $credits_remaining) {
    // Save to user's gallery
    save_to_user_gallery($user_id, $result_image);
    
    // Send notification
    if ($credits_remaining < 5) {
        send_low_credit_notification($user_id);
    }
}
?>
```

#### WooCommerce Extension

```php
<?php
/**
 * Add virtual fitting to product pages
 */

// Add virtual fitting button to product page
add_action('woocommerce_after_add_to_cart_button', 'add_virtual_fitting_button');

function add_virtual_fitting_button() {
    global $product;
    
    // Only show for wedding dress category
    if (has_term('wedding-dresses', 'product_cat', $product->get_id())) {
        echo '<a href="/virtual-fitting?product=' . $product->get_id() . '" class="button virtual-fitting-button">';
        echo 'Try Virtual Fitting';
        echo '</a>';
    }
}

// Add virtual fitting tab to product page
add_filter('woocommerce_product_tabs', 'add_virtual_fitting_tab');

function add_virtual_fitting_tab($tabs) {
    $tabs['virtual_fitting'] = array(
        'title' => __('Virtual Fitting', 'ai-virtual-fitting'),
        'priority' => 50,
        'callback' => 'virtual_fitting_tab_content'
    );
    return $tabs;
}

function virtual_fitting_tab_content() {
    echo '<h2>Try Virtual Fitting</h2>';
    echo '<p>See how this dress looks on you with our AI-powered virtual fitting technology.</p>';
    echo do_shortcode('[ai_virtual_fitting]');
}
?>
```

---

## Related Documentation

- [User Guide](USER-GUIDE.md) - End user documentation
- [Admin Guide](ADMIN-GUIDE.md) - Administrator documentation
- [Developer Documentation](../DEVELOPER.md) - Technical architecture
- [Troubleshooting Guide](TROUBLESHOOTING.md) - Common issues and solutions
- [Security Documentation](SECURITY.md) - Security features and best practices

## Support

For API-related questions or issues:

1. Check this API reference
2. Review code examples
3. Check error codes reference
4. Contact support with:
   - API endpoint being used
   - Request/response data
   - Error codes received
   - Steps to reproduce

---

**Last Updated**: 2025-01-15  
**Version**: 1.0.0  
**Plugin Version**: 1.0.2
