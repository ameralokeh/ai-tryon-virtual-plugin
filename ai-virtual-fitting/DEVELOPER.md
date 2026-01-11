# AI Virtual Fitting Plugin - Developer Documentation

## Overview

This document provides comprehensive technical documentation for developers working with the AI Virtual Fitting Plugin. It covers architecture, APIs, customization options, and development workflows.

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Plugin Structure](#plugin-structure)
3. [Core Components](#core-components)
4. [Database Schema](#database-schema)
5. [API Integration](#api-integration)
6. [Hooks and Filters](#hooks-and-filters)
7. [Customization Guide](#customization-guide)
8. [Testing Framework](#testing-framework)
9. [Development Workflow](#development-workflow)
10. [Performance Optimization](#performance-optimization)
11. [Security Considerations](#security-considerations)
12. [Troubleshooting](#troubleshooting)

## Architecture Overview

The AI Virtual Fitting Plugin follows a modular architecture with clear separation of concerns:

```
┌─────────────────────────────────────────────────────────────┐
│                    WordPress Frontend                        │
├─────────────────────────────────────────────────────────────┤
│  Public Interface  │  Virtual Fitting Page  │  AJAX Handlers │
├─────────────────────────────────────────────────────────────┤
│                    Plugin Core Layer                        │
├─────────────────────────────────────────────────────────────┤
│ Credit Manager │ Image Processor │ WooCommerce Integration  │
├─────────────────────────────────────────────────────────────┤
│ Database Manager │ Performance Manager │ Analytics Manager │
├─────────────────────────────────────────────────────────────┤
│                    External Services                        │
├─────────────────────────────────────────────────────────────┤
│  Google AI Studio API  │  WordPress Database  │  WooCommerce │
└─────────────────────────────────────────────────────────────┘
```

### Design Principles

- **Modularity**: Each component has a single responsibility
- **Extensibility**: Hooks and filters allow customization
- **Performance**: Asynchronous processing and caching
- **Security**: Input validation and sanitization
- **Compatibility**: WordPress and WooCommerce standards

## Plugin Structure

```
ai-virtual-fitting/
├── ai-virtual-fitting.php          # Main plugin file
├── README.md                       # User documentation
├── DEVELOPER.md                    # Developer documentation
├── uninstall.php                   # Uninstall cleanup
├── phpunit.xml                     # PHPUnit configuration
├── includes/                       # Core classes
│   ├── class-virtual-fitting-core.php
│   ├── class-credit-manager.php
│   ├── class-image-processor.php
│   ├── class-woocommerce-integration.php
│   ├── class-database-manager.php
│   ├── class-performance-manager.php
│   └── class-analytics-manager.php
├── admin/                          # Admin interface
│   ├── class-admin-settings.php
│   ├── admin-settings-page.php
│   ├── help-documentation.php
│   ├── css/
│   │   └── admin-settings.css
│   └── js/
│       └── admin-settings.js
├── public/                         # Frontend interface
│   ├── class-public-interface.php
│   ├── virtual-fitting-page.php
│   ├── css/
│   │   └── virtual-fitting.css
│   └── js/
│       └── virtual-fitting.js
├── tests/                          # Test files
│   ├── bootstrap.php
│   ├── wp-test-runner.php
│   ├── simple-test-runner.php
│   └── [test files]
├── languages/                      # Translations
│   └── ai-virtual-fitting.pot
└── assets/                         # Static assets
    ├── images/
    └── icons/
```

## Core Components

### 1. Virtual Fitting Core (`AI_Virtual_Fitting_Core`)

The main orchestrator that initializes and coordinates all plugin components.

```php
class AI_Virtual_Fitting_Core {
    // Singleton pattern
    public static function instance()
    
    // Component initialization
    private function init_components()
    
    // WordPress hooks
    private function init_hooks()
    
    // Plugin activation/deactivation
    public static function activate()
    public static function deactivate()
}
```

**Key Responsibilities:**
- Plugin lifecycle management
- Component initialization
- WordPress integration
- Settings management

### 2. Credit Manager (`AI_Virtual_Fitting_Credit_Manager`)

Handles all credit-related operations including allocation, deduction, and tracking.

```php
class AI_Virtual_Fitting_Credit_Manager {
    // Credit operations
    public function get_customer_credits($user_id)
    public function deduct_credit($user_id)
    public function add_credits($user_id, $amount)
    public function grant_initial_credits($user_id)
    
    // Database operations
    private function create_user_record($user_id)
    private function update_user_credits($user_id, $credits)
}
```

**Database Table:**
```sql
CREATE TABLE wp_virtual_fitting_credits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    credits_remaining INT DEFAULT 0,
    total_credits_purchased INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id)
);
```

### 3. Image Processor (`AI_Virtual_Fitting_Image_Processor`)

Handles image upload, validation, and AI processing through Google AI Studio.

```php
class AI_Virtual_Fitting_Image_Processor {
    // Image validation
    public function validate_uploaded_image($file)
    
    // AI processing
    public function process_virtual_fitting($customer_image, $product_images)
    
    // API integration
    private function call_gemini_api($images, $prompt)
    
    // File management
    public function cleanup_temp_files()
}
```

**Image Processing Flow:**
1. Upload validation (format, size, dimensions)
2. Temporary storage
3. Product image retrieval
4. Google AI Studio API call
5. Result processing and storage
6. Cleanup of temporary files

### 4. WooCommerce Integration (`AI_Virtual_Fitting_WooCommerce_Integration`)

Manages WooCommerce product creation and order processing for credit purchases.

```php
class AI_Virtual_Fitting_WooCommerce_Integration {
    // Product management
    public function create_credits_product()
    public function get_credits_product_id()
    
    // Order processing
    public function handle_order_completed($order_id)
    
    // Cart integration
    public function add_credits_to_cart()
}
```

**WooCommerce Hooks Used:**
- `woocommerce_payment_complete`
- `woocommerce_order_status_completed`
- `woocommerce_thankyou`

### 5. Database Manager (`AI_Virtual_Fitting_Database_Manager`)

Handles database operations, table creation, and data management.

```php
class AI_Virtual_Fitting_Database_Manager {
    // Table management
    public function create_tables()
    public function drop_tables()
    public function verify_tables_exist()
    
    // Data operations
    public function migrate_data()
    public function cleanup_old_data()
}
```

### 6. Performance Manager (`AI_Virtual_Fitting_Performance_Manager`)

Manages system performance, caching, and queue processing.

```php
class AI_Virtual_Fitting_Performance_Manager {
    // Performance monitoring
    public function get_system_metrics()
    public function detect_system_load()
    
    // Queue management
    public function add_to_queue($request)
    public function process_queue()
    
    // Caching
    public function cache_product_images($product_id)
    public function clear_cache()
}
```

### 7. Analytics Manager (`AI_Virtual_Fitting_Analytics_Manager`)

Tracks usage, performance metrics, and generates reports.

```php
class AI_Virtual_Fitting_Analytics_Manager {
    // Usage tracking
    public function track_usage($event, $data)
    public function track_virtual_fitting($user_id, $product_id)
    
    // Metrics
    public function get_usage_metrics($period)
    public function get_performance_metrics()
    
    // Reports
    public function generate_report($type, $period)
}
```

## Database Schema

### Virtual Fitting Credits Table

```sql
CREATE TABLE wp_virtual_fitting_credits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    credits_remaining INT DEFAULT 0,
    total_credits_purchased INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES wp_users(ID) ON DELETE CASCADE
);
```

### Analytics Table (Optional)

```sql
CREATE TABLE wp_virtual_fitting_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT,
    event_type VARCHAR(50) NOT NULL,
    event_data JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at)
);
```

## API Integration

### Google AI Studio Integration

The plugin integrates with Google AI Studio's Gemini 2.5 Flash Image model:

```php
// API Configuration
private $api_endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
private $api_key;
private $timeout = 60;
private $retry_attempts = 3;

// API Call Structure
public function call_gemini_api($images, $prompt) {
    $payload = array(
        'contents' => array(
            array(
                'parts' => array(
                    array('text' => $prompt),
                    // Image parts
                )
            )
        ),
        'generationConfig' => array(
            'temperature' => 0.7,
            'maxOutputTokens' => 1024
        )
    );
    
    return $this->make_api_request($payload);
}
```

### API Error Handling

```php
private function handle_api_error($response, $attempt) {
    $error_code = wp_remote_retrieve_response_code($response);
    
    switch ($error_code) {
        case 429: // Rate limit
            $this->log_error('API rate limit exceeded', array('attempt' => $attempt));
            return $this->should_retry($attempt);
            
        case 401: // Authentication
            $this->log_error('API authentication failed');
            return false;
            
        case 500: // Server error
            $this->log_error('API server error', array('attempt' => $attempt));
            return $this->should_retry($attempt);
            
        default:
            $this->log_error('Unknown API error', array('code' => $error_code));
            return false;
    }
}
```

## Hooks and Filters

### Action Hooks

```php
// Plugin lifecycle
do_action('ai_virtual_fitting_activated');
do_action('ai_virtual_fitting_deactivated');

// Credit operations
do_action('ai_virtual_fitting_credit_added', $user_id, $amount);
do_action('ai_virtual_fitting_credit_deducted', $user_id);
do_action('ai_virtual_fitting_initial_credits_granted', $user_id);

// Processing events
do_action('ai_virtual_fitting_processing_started', $user_id, $product_id);
do_action('ai_virtual_fitting_processing_complete', $user_id, $product_id, $result);
do_action('ai_virtual_fitting_processing_failed', $user_id, $product_id, $error);

// Image operations
do_action('ai_virtual_fitting_image_uploaded', $user_id, $file_info);
do_action('ai_virtual_fitting_image_downloaded', $user_id, $result_file);
```

### Filter Hooks

```php
// Credit system
$initial_credits = apply_filters('ai_virtual_fitting_initial_credits', 2, $user_id);
$credits_per_package = apply_filters('ai_virtual_fitting_credits_per_package', 20);
$package_price = apply_filters('ai_virtual_fitting_package_price', 10.00);

// Image processing
$max_image_size = apply_filters('ai_virtual_fitting_max_image_size', 10485760);
$allowed_types = apply_filters('ai_virtual_fitting_allowed_types', array('image/jpeg', 'image/png', 'image/webp'));
$image_quality = apply_filters('ai_virtual_fitting_image_quality', 90);

// API configuration
$api_timeout = apply_filters('ai_virtual_fitting_api_timeout', 60);
$retry_attempts = apply_filters('ai_virtual_fitting_retry_attempts', 3);
$api_prompt = apply_filters('ai_virtual_fitting_api_prompt', $default_prompt, $product_data);

// Performance
$queue_size = apply_filters('ai_virtual_fitting_queue_size', 10);
$cache_duration = apply_filters('ai_virtual_fitting_cache_duration', 3600);
```

## Customization Guide

### 1. Customizing Credit System

```php
// Change initial credits for specific user roles
add_filter('ai_virtual_fitting_initial_credits', function($credits, $user_id) {
    $user = get_user_by('id', $user_id);
    
    if (in_array('premium_member', $user->roles)) {
        return 10; // Premium members get 10 free credits
    }
    
    return $credits;
}, 10, 2);

// Custom credit pricing based on user role
add_filter('ai_virtual_fitting_package_price', function($price) {
    if (current_user_can('wholesale_customer')) {
        return $price * 0.8; // 20% discount for wholesale customers
    }
    
    return $price;
});
```

### 2. Customizing Image Processing

```php
// Custom image validation
add_filter('ai_virtual_fitting_allowed_types', function($types) {
    // Add AVIF support
    $types[] = 'image/avif';
    return $types;
});

// Custom API prompt
add_filter('ai_virtual_fitting_api_prompt', function($prompt, $product_data) {
    // Customize prompt based on product category
    if ($product_data['category'] === 'evening-gowns') {
        return "Create an elegant virtual fitting for this evening gown...";
    }
    
    return $prompt;
}, 10, 2);
```

### 3. Adding Custom Analytics

```php
// Track custom events
add_action('ai_virtual_fitting_processing_complete', function($user_id, $product_id, $result) {
    // Custom analytics tracking
    $analytics = AI_Virtual_Fitting_Core::instance()->get_analytics_manager();
    $analytics->track_usage('virtual_fitting_success', array(
        'user_id' => $user_id,
        'product_id' => $product_id,
        'processing_time' => $result['processing_time'],
        'image_size' => $result['image_size']
    ));
});
```

### 4. Custom Admin Dashboard Widgets

```php
// Add custom dashboard widget
add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget(
        'ai_virtual_fitting_stats',
        'Virtual Fitting Statistics',
        'ai_virtual_fitting_dashboard_widget'
    );
});

function ai_virtual_fitting_dashboard_widget() {
    $analytics = AI_Virtual_Fitting_Core::instance()->get_analytics_manager();
    $stats = $analytics->get_usage_metrics('today');
    
    echo '<div class="virtual-fitting-stats">';
    echo '<p>Today\'s Virtual Fittings: ' . $stats['total_fittings'] . '</p>';
    echo '<p>Credits Consumed: ' . $stats['credits_used'] . '</p>';
    echo '<p>Revenue Generated: $' . $stats['revenue'] . '</p>';
    echo '</div>';
}
```

## Testing Framework

### Unit Testing

The plugin uses PHPUnit for unit testing with WordPress test suite integration:

```php
// Example test class
class AI_Virtual_Fitting_Credit_Manager_Test extends WP_UnitTestCase {
    
    private $credit_manager;
    private $test_user_id;
    
    public function setUp(): void {
        parent::setUp();
        $this->credit_manager = new AI_Virtual_Fitting_Credit_Manager();
        $this->test_user_id = $this->factory->user->create();
    }
    
    public function test_initial_credits_allocation() {
        $this->credit_manager->grant_initial_credits($this->test_user_id);
        $credits = $this->credit_manager->get_customer_credits($this->test_user_id);
        
        $this->assertEquals(2, $credits);
    }
    
    public function test_credit_deduction() {
        $this->credit_manager->grant_initial_credits($this->test_user_id);
        $this->credit_manager->deduct_credit($this->test_user_id);
        
        $credits = $this->credit_manager->get_customer_credits($this->test_user_id);
        $this->assertEquals(1, $credits);
    }
}
```

### Property-Based Testing

The plugin includes property-based tests using Eris:

```php
use Eris\Generator;

class AI_Virtual_Fitting_Property_Test extends WP_UnitTestCase {
    
    use Eris\TestTrait;
    
    public function testCreditOperationsProperty() {
        $this->forAll(
            Generator\pos(),
            Generator\choose(1, 100)
        )->then(function($user_id, $credits_to_add) {
            $credit_manager = new AI_Virtual_Fitting_Credit_Manager();
            
            $initial_credits = $credit_manager->get_customer_credits($user_id);
            $credit_manager->add_credits($user_id, $credits_to_add);
            $final_credits = $credit_manager->get_customer_credits($user_id);
            
            $this->assertEquals($initial_credits + $credits_to_add, $final_credits);
        });
    }
}
```

### Integration Testing

```php
class AI_Virtual_Fitting_Integration_Test extends WP_UnitTestCase {
    
    public function test_complete_virtual_fitting_workflow() {
        // Create test user
        $user_id = $this->factory->user->create();
        wp_set_current_user($user_id);
        
        // Create test product
        $product_id = $this->create_test_product();
        
        // Grant initial credits
        $credit_manager = new AI_Virtual_Fitting_Credit_Manager();
        $credit_manager->grant_initial_credits($user_id);
        
        // Test image upload
        $image_processor = new AI_Virtual_Fitting_Image_Processor();
        $test_image = $this->create_test_image();
        
        $validation_result = $image_processor->validate_uploaded_image($test_image);
        $this->assertFalse(is_wp_error($validation_result));
        
        // Test credit deduction
        $credits_before = $credit_manager->get_customer_credits($user_id);
        $credit_manager->deduct_credit($user_id);
        $credits_after = $credit_manager->get_customer_credits($user_id);
        
        $this->assertEquals($credits_before - 1, $credits_after);
    }
}
```

### Running Tests

```bash
# Run all tests
php tests/wp-test-runner.php

# Run specific test class
php tests/wp-test-runner.php AI_Virtual_Fitting_Credit_Manager_Test

# Run property-based tests
php tests/wp-test-runner.php --property-tests

# Run with coverage
php tests/wp-test-runner.php --coverage
```

## Development Workflow

### 1. Setting Up Development Environment

```bash
# Clone the repository
git clone [repository-url]
cd ai-virtual-fitting

# Set up local WordPress environment
docker-compose up -d

# Install dependencies
composer install
npm install

# Run initial tests
php tests/wp-test-runner.php
```

### 2. Code Standards

The plugin follows WordPress Coding Standards:

```bash
# Install PHP_CodeSniffer with WordPress rules
composer global require "squizlabs/php_codesniffer=*"
composer global require wp-coding-standards/wpcs

# Check code standards
phpcs --standard=WordPress ai-virtual-fitting/

# Auto-fix issues
phpcbf --standard=WordPress ai-virtual-fitting/
```

### 3. Git Workflow

```bash
# Create feature branch
git checkout -b feature/new-feature

# Make changes and commit
git add .
git commit -m "Add new feature: description"

# Run tests before pushing
php tests/wp-test-runner.php

# Push and create pull request
git push origin feature/new-feature
```

### 4. Release Process

```bash
# Update version numbers
# - ai-virtual-fitting.php (plugin header)
# - README.md
# - package.json

# Run full test suite
php tests/wp-test-runner.php --all

# Build production assets
npm run build

# Create release tag
git tag -a v1.0.1 -m "Release version 1.0.1"
git push origin v1.0.1

# Create release package
zip -r ai-virtual-fitting-v1.0.1.zip ai-virtual-fitting/ -x "*.git*" "node_modules/*" "tests/*"
```

## Performance Optimization

### 1. Database Optimization

```php
// Use prepared statements
$wpdb->prepare("SELECT * FROM {$table_name} WHERE user_id = %d", $user_id);

// Add proper indexes
CREATE INDEX idx_user_credits ON wp_virtual_fitting_credits (user_id, credits_remaining);

// Optimize queries
$credits = wp_cache_get("user_credits_{$user_id}", 'ai_virtual_fitting');
if (false === $credits) {
    $credits = $this->get_credits_from_database($user_id);
    wp_cache_set("user_credits_{$user_id}", $credits, 'ai_virtual_fitting', 3600);
}
```

### 2. Image Optimization

```php
// Optimize images before API calls
public function optimize_image_for_api($image_path) {
    $image = wp_get_image_editor($image_path);
    
    if (!is_wp_error($image)) {
        // Resize if too large
        $image->resize(1024, 1024, false);
        
        // Compress
        $image->set_quality(85);
        
        // Save optimized version
        $image->save($image_path);
    }
    
    return $image_path;
}
```

### 3. Caching Strategy

```php
// Cache product images
public function cache_product_images($product_id) {
    $cache_key = "product_images_{$product_id}";
    $images = wp_cache_get($cache_key, 'ai_virtual_fitting');
    
    if (false === $images) {
        $images = $this->get_product_images($product_id);
        wp_cache_set($cache_key, $images, 'ai_virtual_fitting', 3600);
    }
    
    return $images;
}

// Cache API responses (for debugging/development)
public function cache_api_response($request_hash, $response) {
    if (WP_DEBUG) {
        wp_cache_set("api_response_{$request_hash}", $response, 'ai_virtual_fitting', 300);
    }
}
```

### 4. Asynchronous Processing

```php
// Queue processing for high load
public function queue_virtual_fitting_request($user_id, $product_id, $image_path) {
    $queue_item = array(
        'user_id' => $user_id,
        'product_id' => $product_id,
        'image_path' => $image_path,
        'timestamp' => time(),
        'status' => 'pending'
    );
    
    // Add to queue
    $this->add_to_processing_queue($queue_item);
    
    // Schedule processing
    wp_schedule_single_event(time() + 10, 'ai_virtual_fitting_process_queue');
}
```

## Security Considerations

### 1. Input Validation

```php
// Validate and sanitize all inputs
public function validate_uploaded_image($file) {
    // Check file type
    $allowed_types = array('image/jpeg', 'image/png', 'image/webp');
    if (!in_array($file['type'], $allowed_types)) {
        return new WP_Error('invalid_type', 'Invalid file type');
    }
    
    // Check file size
    $max_size = AI_Virtual_Fitting_Core::get_option('max_image_size', 10485760);
    if ($file['size'] > $max_size) {
        return new WP_Error('file_too_large', 'File too large');
    }
    
    // Verify actual file type (not just extension)
    $file_info = getimagesize($file['tmp_name']);
    if (!$file_info) {
        return new WP_Error('invalid_image', 'Invalid image file');
    }
    
    return true;
}
```

### 2. Nonce Verification

```php
// Verify nonces for all AJAX requests
public function handle_ajax_request() {
    if (!wp_verify_nonce($_POST['nonce'], 'ai_virtual_fitting_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'));
    }
    
    // Process request...
}
```

### 3. Capability Checks

```php
// Check user capabilities
public function admin_action() {
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }
    
    // Admin action...
}
```

### 4. SQL Injection Prevention

```php
// Always use prepared statements
global $wpdb;
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table_name} WHERE user_id = %d AND status = %s",
    $user_id,
    $status
));
```

### 5. File Security

```php
// Secure file uploads
public function secure_file_upload($file) {
    // Generate unique filename
    $filename = wp_unique_filename($upload_dir, $file['name']);
    
    // Move to secure location
    $upload_path = $upload_dir . '/' . $filename;
    move_uploaded_file($file['tmp_name'], $upload_path);
    
    // Set proper permissions
    chmod($upload_path, 0644);
    
    return $upload_path;
}
```

## Troubleshooting

### 1. Common Issues

#### Plugin Activation Fails
```php
// Debug activation issues
add_action('activated_plugin', function($plugin) {
    if ($plugin === 'ai-virtual-fitting/ai-virtual-fitting.php') {
        error_log('AI Virtual Fitting Plugin activated');
        
        // Check requirements
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            error_log('PHP version too old: ' . PHP_VERSION);
        }
        
        if (!class_exists('WooCommerce')) {
            error_log('WooCommerce not found');
        }
    }
});
```

#### API Connection Issues
```php
// Debug API calls
private function debug_api_call($request, $response) {
    if (WP_DEBUG) {
        error_log('API Request: ' . json_encode($request));
        error_log('API Response: ' . wp_remote_retrieve_body($response));
        error_log('Response Code: ' . wp_remote_retrieve_response_code($response));
    }
}
```

#### Database Issues
```php
// Check database connectivity
public function check_database_health() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'virtual_fitting_credits';
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    
    if (!$table_exists) {
        error_log('Virtual fitting credits table does not exist');
        return false;
    }
    
    // Check table structure
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    $expected_columns = array('id', 'user_id', 'credits_remaining', 'total_credits_purchased');
    
    foreach ($expected_columns as $column) {
        $found = false;
        foreach ($columns as $col) {
            if ($col->Field === $column) {
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            error_log("Missing database column: $column");
            return false;
        }
    }
    
    return true;
}
```

### 2. Debugging Tools

```php
// Enable debug logging
define('AI_VIRTUAL_FITTING_DEBUG', true);

// Custom logging function
public function debug_log($message, $data = null) {
    if (defined('AI_VIRTUAL_FITTING_DEBUG') && AI_VIRTUAL_FITTING_DEBUG) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'message' => $message,
            'data' => $data,
            'backtrace' => wp_debug_backtrace_summary()
        );
        
        error_log('AI Virtual Fitting Debug: ' . json_encode($log_entry));
    }
}
```

### 3. Performance Monitoring

```php
// Monitor performance
public function monitor_performance($operation, $callback) {
    $start_time = microtime(true);
    $start_memory = memory_get_usage();
    
    $result = call_user_func($callback);
    
    $end_time = microtime(true);
    $end_memory = memory_get_usage();
    
    $this->log_performance_metrics(array(
        'operation' => $operation,
        'execution_time' => $end_time - $start_time,
        'memory_usage' => $end_memory - $start_memory,
        'peak_memory' => memory_get_peak_usage()
    ));
    
    return $result;
}
```

## Contributing

### Code Contribution Guidelines

1. **Follow WordPress Coding Standards**
2. **Write comprehensive tests for new features**
3. **Update documentation for any API changes**
4. **Use semantic versioning for releases**
5. **Include proper error handling and logging**

### Pull Request Process

1. Fork the repository
2. Create a feature branch
3. Write tests for your changes
4. Ensure all tests pass
5. Update documentation
6. Submit pull request with detailed description

### Bug Reports

When reporting bugs, include:
- WordPress version
- PHP version
- WooCommerce version
- Plugin version
- Steps to reproduce
- Expected vs actual behavior
- Error logs (if any)

---

**Last Updated**: January 2026  
**Version**: 1.0.0  
**Compatibility**: WordPress 5.0+, WooCommerce 5.0+, PHP 7.4+