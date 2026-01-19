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

The AI Virtual Fitting Plugin follows a modular, layered architecture with clear separation of concerns and well-defined component relationships. The architecture is designed for scalability, maintainability, and extensibility.

### System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           PRESENTATION LAYER                                 │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐         │
│  │  Admin Interface │  │  Public Interface│  │  AJAX Endpoints  │         │
│  │  - Settings Page │  │  - Fitting Page  │  │  - Upload Image  │         │
│  │  - Dashboard     │  │  - Product Slider│  │  - Process Fit   │         │
│  │  - Analytics     │  │  - Result Display│  │  - Check Credits │         │
│  └────────┬─────────┘  └────────┬─────────┘  └────────┬─────────┘         │
│           │                     │                      │                    │
└───────────┼─────────────────────┼──────────────────────┼────────────────────┘
            │                     │                      │
┌───────────┼─────────────────────┼──────────────────────┼────────────────────┐
│           │         APPLICATION/BUSINESS LOGIC LAYER   │                    │
├───────────┴─────────────────────┴──────────────────────┴────────────────────┤
│                                                                              │
│  ┌──────────────────────────────────────────────────────────────────────┐  │
│  │                    Virtual Fitting Core (Orchestrator)                │  │
│  │  - Component Initialization  - Hook Management  - Lifecycle Control  │  │
│  └────┬──────────────────┬──────────────────┬──────────────────┬────────┘  │
│       │                  │                  │                  │            │
│  ┌────▼────────┐  ┌──────▼──────┐  ┌───────▼────────┐  ┌─────▼──────┐    │
│  │   Credit    │  │    Image    │  │  WooCommerce   │  │  Security  │    │
│  │   Manager   │  │  Processor  │  │  Integration   │  │  Manager   │    │
│  │             │  │             │  │                │  │            │    │
│  │ - Allocate  │  │ - Validate  │  │ - Products     │  │ - Encrypt  │    │
│  │ - Deduct    │  │ - Optimize  │  │ - Orders       │  │ - Validate │    │
│  │ - Track     │  │ - Process   │  │ - Cart         │  │ - Rate Lim │    │
│  └─────────────┘  └──────┬──────┘  └────────────────┘  └────────────┘    │
│                          │                                                  │
│  ┌─────────────┐  ┌──────▼──────┐  ┌────────────────┐  ┌─────────────┐   │
│  │  Database   │  │  Vertex AI  │  │  Performance   │  │  Analytics  │   │
│  │  Manager    │  │  Manager    │  │  Manager       │  │  Manager    │   │
│  │             │  │             │  │                │  │             │   │
│  │ - Tables    │  │ - API Calls │  │ - Caching      │  │ - Tracking  │   │
│  │ - Queries   │  │ - Retry     │  │ - Queue        │  │ - Metrics   │   │
│  │ - Migration │  │ - Error Hdl │  │ - Optimization │  │ - Reports   │   │
│  └─────────────┘  └─────────────┘  └────────────────┘  └─────────────┘   │
│                                                                              │
└──────────────────────────────────┬───────────────────────────────────────────┘
                                   │
┌──────────────────────────────────┴───────────────────────────────────────────┐
│                           DATA/INTEGRATION LAYER                             │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐         │
│  │  WordPress DB    │  │  Google AI Studio│  │  WooCommerce API │         │
│  │  - wp_users      │  │  - Gemini 2.5    │  │  - Products      │         │
│  │  - wp_postmeta   │  │  - Flash Image   │  │  - Orders        │         │
│  │  - wp_vf_credits │  │  - REST API      │  │  - Customers     │         │
│  └──────────────────┘  └──────────────────┘  └──────────────────┘         │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Component Relationships

The plugin uses a **hierarchical component architecture** with the following relationships:

#### 1. Core Orchestrator Pattern
- **Virtual Fitting Core** acts as the central orchestrator
- Initializes all components in proper dependency order
- Manages component lifecycle and inter-component communication
- Implements singleton pattern for global access

#### 2. Manager Pattern
- Each functional area has a dedicated manager class
- Managers are independent and loosely coupled
- Communication through well-defined interfaces
- Dependency injection for testability

#### 3. Component Dependencies

```
Virtual Fitting Core
    ├── Database Manager (no dependencies)
    ├── Security Manager (no dependencies)
    ├── Credit Manager (depends on: Database Manager)
    ├── Performance Manager (depends on: Database Manager)
    ├── Analytics Manager (depends on: Database Manager)
    ├── Vertex AI Manager (depends on: Security Manager, Performance Manager)
    ├── Image Processor (depends on: Vertex AI Manager, Security Manager)
    ├── WooCommerce Integration (depends on: Credit Manager, Database Manager)
    ├── Admin Settings (depends on: all managers)
    └── Public Interface (depends on: Credit Manager, Image Processor, WooCommerce Integration)
```

#### 4. Data Flow Patterns

**Virtual Fitting Request Flow:**
```
User Upload → Public Interface → Image Processor → Vertex AI Manager → Google AI Studio
                    ↓                    ↓                ↓
              Credit Manager ← Security Manager ← Performance Manager
                    ↓
              Database Manager → WordPress DB
```

**Credit Purchase Flow:**
```
User Action → WooCommerce Cart → Checkout → Order Complete Hook
                                                    ↓
                                          WooCommerce Integration
                                                    ↓
                                              Credit Manager
                                                    ↓
                                            Database Manager
```

### Design Patterns

The plugin implements several well-established design patterns:

#### 1. Singleton Pattern
- Used for: Core, all Manager classes
- Purpose: Single instance, global access point
- Implementation: Private constructor, static instance method

```php
class AI_Virtual_Fitting_Core {
    private static $instance = null;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Initialize
    }
}
```

#### 2. Factory Pattern
- Used for: Component initialization
- Purpose: Centralized object creation
- Implementation: Core class creates and configures all managers

#### 3. Strategy Pattern
- Used for: Image processing, API retry logic
- Purpose: Interchangeable algorithms
- Implementation: Different strategies for different scenarios

#### 4. Observer Pattern
- Used for: WordPress hooks and filters
- Purpose: Event-driven architecture
- Implementation: WordPress action/filter system

#### 5. Repository Pattern
- Used for: Database operations
- Purpose: Abstract data access
- Implementation: Database Manager encapsulates all queries

#### 6. Facade Pattern
- Used for: Public Interface, Admin Settings
- Purpose: Simplified interface to complex subsystems
- Implementation: High-level interfaces hide complexity

### Design Principles

The architecture follows SOLID principles and WordPress best practices:

- **Single Responsibility**: Each component has one clear purpose
- **Open/Closed**: Extensible via hooks without modifying core code
- **Liskov Substitution**: Components can be replaced with compatible implementations
- **Interface Segregation**: Focused, minimal interfaces
- **Dependency Inversion**: Depend on abstractions (hooks) not concrete implementations
- **Modularity**: Clear boundaries between components
- **Extensibility**: 50+ hooks and filters for customization
- **Performance**: Asynchronous processing, caching, queue management
- **Security**: Multi-layer validation, encryption, rate limiting
- **Compatibility**: WordPress and WooCommerce coding standards
- **Testability**: Dependency injection, mockable components
- **Maintainability**: Clear structure, comprehensive documentation

## Plugin Structure

The plugin follows WordPress plugin development best practices with a clear, organized directory structure.

### Directory Structure

```
ai-virtual-fitting/
├── ai-virtual-fitting.php          # Main plugin file (bootstrap)
├── README.md                       # User-facing documentation
├── DEVELOPER.md                    # Technical documentation (this file)
├── uninstall.php                   # Cleanup script for plugin removal
├── phpunit.xml                     # PHPUnit test configuration
│
├── includes/                       # Core business logic classes
│   ├── class-virtual-fitting-core.php          # Main orchestrator (singleton)
│   ├── class-credit-manager.php                # Credit allocation and tracking
│   ├── class-image-processor.php               # Image validation and processing
│   ├── class-woocommerce-integration.php       # WooCommerce product/order management
│   ├── class-database-manager.php              # Database operations and migrations
│   ├── class-performance-manager.php           # Caching, queue, optimization
│   ├── class-analytics-manager.php             # Usage tracking and metrics
│   ├── class-security-manager.php              # Security features and validation
│   ├── class-vertex-ai-manager.php             # Google AI Studio API integration
│   ├── class-plugin-config.php                 # Configuration management
│   └── class-virtual-credit-system.php         # Credit system logic
│
├── admin/                          # WordPress admin interface
│   ├── class-admin-settings.php                # Settings page controller
│   ├── admin-settings-page.php                 # Settings page template
│   ├── help-documentation.php                  # Help/documentation page template
│   ├── css/                                    # Admin stylesheets
│   │   ├── admin-settings.css                  # Settings page styles
│   │   └── help-tooltips.css                   # Tooltip styles
│   └── js/                                     # Admin JavaScript
│       ├── admin-settings.js                   # Settings page functionality
│       ├── help-tooltips.js                    # Tooltip functionality
│       └── simple-tooltips.js                  # Lightweight tooltip system
│
├── public/                         # Frontend interface
│   ├── class-public-interface.php              # Frontend controller
│   ├── virtual-fitting-page.php                # Classic virtual fitting template
│   ├── modern-virtual-fitting-page.php         # Modern UI template
│   ├── css/                                    # Frontend stylesheets
│   │   ├── virtual-fitting.css                 # Classic UI styles
│   │   ├── modern-virtual-fitting.css          # Modern UI styles
│   │   └── checkout-modal-react.css            # React modal styles
│   └── js/                                     # Frontend JavaScript
│       ├── virtual-fitting.js                  # Classic UI functionality
│       ├── modern-virtual-fitting.js           # Modern UI functionality
│       ├── checkout-modal-react.js             # Compiled React modal
│       ├── checkout-modal-react.jsx            # React modal source
│       └── vendor/                             # Third-party libraries
│           ├── react.production.min.js
│           ├── react-dom.production.min.js
│           └── babel-standalone.min.js
│
├── tests/                          # Test suite
│   ├── bootstrap.php                           # Test environment setup
│   ├── wp-test-runner.php                      # WordPress-integrated test runner
│   ├── simple-test-runner.php                  # Standalone test runner
│   ├── test-helper.php                         # Test utilities and helpers
│   │
│   ├── test-activation.php                     # Plugin activation tests
│   ├── test-activation-simple.php              # Simplified activation tests
│   │
│   ├── test-database-manager.php               # Database manager unit tests
│   ├── test-database-manager-simple.php        # Simplified database tests
│   │
│   ├── test-credit-manager.php                 # Credit manager unit tests
│   ├── test-credit-manager-simple.php          # Simplified credit tests
│   ├── test-credit-access-control-simple.php   # Credit access control tests
│   │
│   ├── test-woocommerce-integration.php        # WooCommerce integration tests
│   ├── test-woocommerce-integration-simple.php # Simplified WooCommerce tests
│   │
│   ├── test-image-processor.php                # Image processor unit tests
│   ├── test-image-processor-simple.php         # Simplified image tests
│   │
│   ├── test-authentication-flow.php            # Authentication workflow tests
│   ├── test-authentication-flow-standalone.php # Standalone auth tests
│   ├── test-auth-simple.php                    # Simplified auth tests
│   │
│   ├── test-product-selection.php              # Product selection tests
│   ├── test-product-selection-simple.php       # Simplified product tests
│   │
│   ├── test-download-functionality.php         # Download feature tests
│   ├── test-download-simple.php                # Simplified download tests
│   ├── test-download-complete.php              # Complete download workflow tests
│   │
│   ├── test-error-handling-simple.php          # Error handling tests
│   ├── test-error-handling-property.php        # Property-based error tests
│   │
│   ├── test-performance-concurrency-property.php # Performance property tests
│   │
│   ├── test-integration-workflow.php           # Integration workflow tests
│   ├── test-complete-integration.php           # Complete integration tests
│   ├── test-plugin-lifecycle.php               # Plugin lifecycle tests
│   │
│   ├── test-checkout-integration.php           # Checkout integration tests
│   ├── test-checkout-requirements-validation.php # Checkout validation tests
│   └── test-embedded-checkout-flow.php         # Embedded checkout tests
│
├── docs/                           # Additional documentation
│   ├── README.md                               # Documentation index
│   ├── CONTRIBUTING.md                         # Contribution guidelines
│   ├── .style-guide.md                         # Documentation style guide
│   ├── .doc-template.md                        # Documentation template
│   ├── .code-example-template.md               # Code example template
│   ├── .screenshot-template.md                 # Screenshot template
│   ├── .markdownlint.json                      # Markdown linting config
│   └── images/                                 # Documentation images
│       ├── configuration/                      # Configuration screenshots
│       ├── installation/                       # Installation screenshots
│       ├── user-interface/                     # UI screenshots
│       └── workflows/                          # Workflow diagrams
│
├── languages/                      # Internationalization
│   └── ai-virtual-fitting.pot                  # Translation template
│
└── assets/                         # Static assets
    ├── images/                                 # Plugin images
    │   └── .gitkeep
    └── icons/                                  # Plugin icons
        └── .gitkeep
```

### File Organization Principles

#### 1. Separation by Concern
- **includes/**: Business logic, no UI code
- **admin/**: WordPress admin interface only
- **public/**: Frontend interface only
- **tests/**: All test files isolated

#### 2. Class-per-File Convention
- One class per file
- File name matches class name (lowercase, hyphenated)
- Example: `AI_Virtual_Fitting_Credit_Manager` → `class-credit-manager.php`

#### 3. Asset Organization
- CSS files grouped by context (admin/public)
- JavaScript files grouped by context
- Vendor libraries in dedicated vendor/ directory
- Images organized by purpose in docs/images/

#### 4. Test Organization
- Tests mirror source structure
- Multiple test variants: full, simple, standalone
- Property-based tests clearly marked
- Integration tests separated from unit tests

### Naming Conventions

#### PHP Files
```
Pattern: class-{component-name}.php
Examples:
  - class-credit-manager.php
  - class-image-processor.php
  - class-woocommerce-integration.php
```

#### PHP Classes
```
Pattern: AI_Virtual_Fitting_{Component_Name}
Examples:
  - AI_Virtual_Fitting_Core
  - AI_Virtual_Fitting_Credit_Manager
  - AI_Virtual_Fitting_Image_Processor
```

#### Test Files
```
Pattern: test-{component}-{variant}.php
Examples:
  - test-credit-manager.php (full test suite)
  - test-credit-manager-simple.php (simplified tests)
  - test-error-handling-property.php (property-based tests)
```

#### CSS/JS Files
```
Pattern: {component-name}.{ext}
Examples:
  - admin-settings.css
  - virtual-fitting.js
  - checkout-modal-react.jsx
```

#### Template Files
```
Pattern: {page-name}-page.php
Examples:
  - admin-settings-page.php
  - virtual-fitting-page.php
  - modern-virtual-fitting-page.php
```

### File Loading Strategy

#### 1. Autoloading
The plugin uses SPL autoloader for automatic class loading:

```php
spl_autoload_register(function($class_name) {
    // Only autoload plugin classes
    if (strpos($class_name, 'AI_Virtual_Fitting_') !== 0) {
        return;
    }
    
    // Convert class name to file name
    $file_name = 'class-' . strtolower(
        str_replace('_', '-', 
            str_replace('AI_Virtual_Fitting_', '', $class_name)
        )
    ) . '.php';
    
    // Search in multiple directories
    $directories = array(
        AI_VIRTUAL_FITTING_PLUGIN_DIR . 'includes/',
        AI_VIRTUAL_FITTING_PLUGIN_DIR . 'admin/',
        AI_VIRTUAL_FITTING_PLUGIN_DIR . 'public/',
    );
    
    foreach ($directories as $directory) {
        $file_path = $directory . $file_name;
        if (file_exists($file_path)) {
            require_once $file_path;
            break;
        }
    }
});
```

#### 2. Lazy Loading
Components are loaded only when needed:
- Admin classes loaded only in admin context
- Public classes loaded only on frontend
- Test classes loaded only during testing

#### 3. Dependency Order
Core class initializes components in dependency order:
1. Database Manager (no dependencies)
2. Security Manager (no dependencies)
3. Performance Manager (depends on Database)
4. Credit Manager (depends on Database)
5. Analytics Manager (depends on Database)
6. Vertex AI Manager (depends on Security, Performance)
7. Image Processor (depends on Vertex AI, Security)
8. WooCommerce Integration (depends on Credit, Database)
9. Admin/Public interfaces (depend on all managers)

### Plugin Constants

The plugin defines several constants for paths and configuration:

```php
// Version
define('AI_VIRTUAL_FITTING_VERSION', '1.0.0');

// Paths
define('AI_VIRTUAL_FITTING_PLUGIN_FILE', __FILE__);
define('AI_VIRTUAL_FITTING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_VIRTUAL_FITTING_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AI_VIRTUAL_FITTING_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Usage in code
$css_url = AI_VIRTUAL_FITTING_PLUGIN_URL . 'public/css/virtual-fitting.css';
$class_file = AI_VIRTUAL_FITTING_PLUGIN_DIR . 'includes/class-credit-manager.php';
```

### WordPress Integration Points

#### Hooks Registration
```php
// Plugin lifecycle
register_activation_hook(__FILE__, array($this, 'activate'));
register_deactivation_hook(__FILE__, array($this, 'deactivate'));

// WordPress initialization
add_action('plugins_loaded', array($this, 'init'));
add_action('init', array($this, 'load_textdomain'));
```

#### Admin Menu Registration
```php
add_action('admin_menu', array($this, 'add_admin_menu'));
add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
```

#### Frontend Registration
```php
add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
add_shortcode('ai_virtual_fitting', array($this, 'render_virtual_fitting_page'));
```

#### AJAX Endpoints
```php
add_action('wp_ajax_ai_virtual_fitting_upload', array($this, 'handle_upload'));
add_action('wp_ajax_ai_virtual_fitting_process', array($this, 'handle_process'));
add_action('wp_ajax_ai_virtual_fitting_check_credits', array($this, 'handle_check_credits'));
```

## Core Components

The plugin consists of 11 core components, each with specific responsibilities and well-defined interfaces. All components follow the singleton pattern and are initialized by the Virtual Fitting Core orchestrator.

### Component Overview

| Component | Purpose | Dependencies | Key Responsibilities |
|-----------|---------|--------------|---------------------|
| Virtual Fitting Core | Orchestrator | All components | Initialization, lifecycle, coordination |
| Database Manager | Data persistence | None | Table management, schema, migrations |
| Security Manager | Security features | None | Encryption, validation, rate limiting |
| Credit Manager | Credit operations | Database Manager | Allocation, deduction, tracking |
| Performance Manager | Optimization | Database Manager | Caching, queue, monitoring |
| Analytics Manager | Usage tracking | Database Manager | Events, metrics, reports |
| Vertex AI Manager | AI integration | Security, Performance | API calls, retry logic, error handling |
| Image Processor | Image handling | Vertex AI, Security | Validation, optimization, processing |
| WooCommerce Integration | E-commerce | Credit, Database | Products, orders, cart |
| Admin Settings | Admin UI | All managers | Settings pages, configuration |
| Public Interface | Frontend UI | Credit, Image, WooCommerce | User interface, AJAX handlers |

### 1. Virtual Fitting Core (`AI_Virtual_Fitting_Core`)

**Purpose:** Central orchestrator that initializes and coordinates all plugin components.

**Responsibilities:**
- Plugin lifecycle management (activation, deactivation, initialization)
- Component initialization in proper dependency order
- WordPress hook registration and management
- Settings management and configuration
- Global access point for all components

**Key Methods:**

```php
class AI_Virtual_Fitting_Core {
    // Singleton instance
    public static function instance()
    
    // Plugin lifecycle
    public static function activate()
    public static function deactivate()
    public function init()
    
    // Component access
    public function get_credit_manager()
    public function get_image_processor()
    public function get_woocommerce_integration()
    public function get_database_manager()
    public function get_performance_manager()
    public function get_analytics_manager()
    public function get_security_manager()
    
    // Settings management
    public static function get_option($key, $default = null)
    public static function update_option($key, $value)
    public static function delete_option($key)
    
    // Hook management
    private function init_hooks()
    private function init_components()
}
```

**Initialization Flow:**
```
1. WordPress loads plugin file
2. AI_Virtual_Fitting::instance() called
3. Hooks registered (plugins_loaded, init)
4. Dependencies checked (WooCommerce, PHP version)
5. Core::instance() initializes components
6. Components initialized in dependency order
7. Admin/Public interfaces loaded based on context
```

**Component Access Pattern:**
```php
// Get core instance
$core = AI_Virtual_Fitting_Core::instance();

// Access components
$credit_manager = $core->get_credit_manager();
$image_processor = $core->get_image_processor();

// Or direct access
$credits = AI_Virtual_Fitting_Core::instance()
    ->get_credit_manager()
    ->get_customer_credits($user_id);
```

### 2. Credit Manager (`AI_Virtual_Fitting_Credit_Manager`)

**Purpose:** Manages all credit-related operations including allocation, deduction, and tracking.

**Responsibilities:**
- Credit allocation and deduction
- Initial credit grants for new users
- Credit purchase processing
- Credit balance tracking
- Credit history management
- System-wide credit statistics

**Key Methods:**

```php
class AI_Virtual_Fitting_Credit_Manager {
    // Credit operations
    public function get_customer_credits($user_id)
    public function deduct_credit($user_id)
    public function add_credits($user_id, $amount)
    public function grant_initial_credits($user_id)
    
    // Credit validation
    public function has_sufficient_credits($user_id, $required_credits = 1)
    
    // Credit history
    public function get_customer_credit_history($user_id)
    public function get_total_credits_purchased($user_id)
    public function get_free_credits_remaining($user_id)
    
    // Purchase processing
    public function handle_credit_purchase($order_id)
    
    // System statistics
    public function get_system_credit_stats()
    
    // Migration
    public function migrate_existing_users()
}
```

**Database Schema:**
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

**Interaction Pattern:**
```
User Action → Credit Check → Deduct Credit → Process Request → Update Balance
                ↓                                                    ↓
          Insufficient? → Redirect to Purchase              Analytics Tracking
```

### 3. Image Processor (`AI_Virtual_Fitting_Image_Processor`)

**Purpose:** Handles image upload, validation, optimization, and AI processing.

**Responsibilities:**
- Image upload handling and validation
- File type and size verification
- Image optimization and resizing
- Google AI Studio API integration
- Result image processing and storage
- Temporary file cleanup

**Key Methods:**

```php
class AI_Virtual_Fitting_Image_Processor {
    // Image validation
    public function validate_uploaded_image($file)
    
    // AI processing
    public function process_virtual_fitting($customer_image_path, $product_images)
    public function call_gemini_api($images_data, $prompt)
    
    // API testing
    public function test_api_connection($credentials = null)
    
    // Result handling
    public function save_result_image($image_data)
    
    // File management
    private function get_temp_directory()
    private function cleanup_temp_files()
    private function optimize_image($image_path)
    
    // AJAX handlers
    public function handle_image_upload()
    public function handle_process_fitting()
    
    // Security
    public function require_login()
}
```

**Image Processing Flow:**
```
Upload → Validate → Optimize → Encode Base64 → API Call → Decode Result → Save → Cleanup
   ↓         ↓          ↓            ↓             ↓           ↓          ↓        ↓
 Size?   Format?   Resize?    Product Imgs   Retry Logic   Parse    Temp Dir  Delete Temp
```

**Validation Rules:**
- **Allowed Types:** JPEG, PNG, WebP
- **Max Size:** 10MB (configurable)
- **Min Dimensions:** 200x200px
- **Max Dimensions:** 4096x4096px
- **Magic Byte Verification:** Yes
- **MIME Type Check:** Yes

### 4. WooCommerce Integration (`AI_Virtual_Fitting_WooCommerce_Integration`)

**Purpose:** Manages WooCommerce product creation and order processing for credit purchases.

**Responsibilities:**
- Credit product creation and management
- Order processing and credit allocation
- Cart integration
- Product validation
- Purchase URL generation

**Key Methods:**

```php
class AI_Virtual_Fitting_WooCommerce_Integration {
    // Product management
    public function create_credits_product()
    public function get_or_create_credits_product()
    public function get_credits_product_id()
    public function is_credits_product($product_id)
    public function validate_credits_product($order)
    
    // Order processing
    public function handle_order_completed($order_id)
    public function handle_payment_complete($order_id)
    private function process_credits_order($order_id)
    
    // Cart operations
    public function add_credits_to_cart($quantity = 1)
    public function ajax_add_credits_to_cart()
    
    // URL generation
    public function get_credits_purchase_url()
    
    // Initialization
    public function initialize()
}
```

**Order Processing Flow:**
```
Add to Cart → Checkout → Payment → Order Complete Hook → Validate Order → Add Credits → Notify User
                                           ↓
                                  WooCommerce Integration
                                           ↓
                                    Credit Manager
                                           ↓
                                   Database Manager
```

**WooCommerce Hooks Used:**
- `woocommerce_payment_complete` - Triggered when payment is confirmed
- `woocommerce_order_status_completed` - Triggered when order status changes to completed
- `woocommerce_thankyou` - Triggered on thank you page
- `woocommerce_add_to_cart` - Triggered when item added to cart

### 5. Database Manager (`AI_Virtual_Fitting_Database_Manager`)

**Purpose:** Handles all database operations, table creation, and data management.

**Responsibilities:**
- Database table creation and management
- Schema versioning and migrations
- Table verification
- Data cleanup
- Table name management

**Key Methods:**

```php
class AI_Virtual_Fitting_Database_Manager {
    // Table management
    public function create_tables()
    public function drop_tables()
    public function verify_tables_exist()
    
    // Table accessors
    public function get_credits_table()
    public function get_sessions_table()
    
    // Migration
    public function migrate_schema($from_version, $to_version)
    
    // Cleanup
    public function cleanup_old_data($days = 30)
}
```

**Database Tables:**

1. **Credits Table** (`wp_virtual_fitting_credits`)
   - Stores user credit balances
   - Tracks total purchases
   - Timestamps for audit trail

2. **Sessions Table** (`wp_virtual_fitting_sessions`) - Future use
   - Stores processing sessions
   - Tracks request status
   - Temporary data storage

### 6. Performance Manager (`AI_Virtual_Fitting_Performance_Manager`)

**Purpose:** Manages system performance, caching, and queue processing.

**Responsibilities:**
- Performance monitoring and metrics
- Caching strategy implementation
- Request queue management
- System load detection
- Resource optimization

**Key Methods:**

```php
class AI_Virtual_Fitting_Performance_Manager {
    // Performance monitoring
    public function get_system_metrics()
    public function detect_system_load()
    public function track_processing_time($operation, $time)
    
    // Caching
    public function cache_product_images($product_id)
    public function get_cached_data($key)
    public function set_cached_data($key, $data, $expiration = 3600)
    public function clear_cache($key = null)
    
    // Queue management
    public function add_to_queue($request)
    public function process_queue()
    public function get_queue_status()
    
    // Optimization
    public function optimize_database()
    public function cleanup_temp_files()
}
```

**Caching Strategy:**
- **Product Images:** 1 hour TTL
- **API Responses:** 5 minutes TTL (debug only)
- **User Credits:** 10 minutes TTL
- **System Metrics:** 5 minutes TTL

### 7. Analytics Manager (`AI_Virtual_Fitting_Analytics_Manager`)

**Purpose:** Tracks usage, performance metrics, and generates reports.

**Responsibilities:**
- Event tracking (uploads, fittings, purchases, downloads)
- Metric collection (processing time, success rate, revenue)
- Dashboard analytics
- System status monitoring
- Report generation

**Key Methods:**

```php
class AI_Virtual_Fitting_Analytics_Manager {
    // Event tracking
    public function track_event($event_type, $event_data, $user_id, $processing_time, $success, $error_message)
    public function track_image_upload($data)
    public function track_fitting_completion($data)
    public function track_fitting_failure($data)
    public function track_credit_purchase($data)
    public function track_image_download($data)
    
    // Metrics
    public function track_metric($metric_name, $value, $unit)
    
    // Analytics
    public function get_dashboard_analytics($period = 'week')
    public function get_system_status()
    
    // AJAX handlers
    public function track_frontend_metrics()
    public function get_analytics_data()
    
    // Cleanup
    public function cleanup_old_analytics($days = 90)
}
```

**Tracked Events:**
- `image_upload` - User uploads photo
- `fitting_request` - Virtual fitting processed
- `fitting_failure` - Processing failed
- `credit_purchase` - Credits purchased
- `image_download` - Result downloaded
- `api_call` - External API called
- `error` - Error occurred

### 8. Security Manager (`AI_Virtual_Fitting_Security_Manager`)

**Purpose:** Implements security features including encryption, validation, and rate limiting.

**Responsibilities:**
- API key encryption/decryption
- Input validation and sanitization
- Rate limiting
- File upload security
- SSRF protection
- Nonce verification

**Key Methods:**

```php
class AI_Virtual_Fitting_Security_Manager {
    // Encryption
    public function encrypt_api_key($api_key)
    public function decrypt_api_key($encrypted_key)
    
    // Validation
    public function validate_image_file($file)
    public function validate_url($url)
    public function sanitize_input($input, $type)
    
    // Rate limiting
    public function check_rate_limit($user_id, $action)
    public function record_request($user_id, $action)
    
    // SSRF protection
    public function is_safe_url($url)
    public function validate_external_request($url)
    
    // Nonce management
    public function verify_nonce($nonce, $action)
    public function create_nonce($action)
}
```

**Security Features:**
- **API Key Encryption:** AES-256-CBC with WordPress salts
- **Rate Limiting:** 10 requests per minute per user
- **File Validation:** Magic byte + MIME type verification
- **SSRF Protection:** Private IP blocking, domain whitelist
- **Input Sanitization:** Context-aware sanitization

### 9. Vertex AI Manager (`AI_Virtual_Fitting_Vertex_AI_Manager`)

**Purpose:** Manages Google AI Studio API integration with retry logic and error handling.

**Responsibilities:**
- API request construction
- Authentication management
- Retry logic with exponential backoff
- Error handling and logging
- Response parsing

**Key Methods:**

```php
class AI_Virtual_Fitting_Vertex_AI_Manager {
    // API calls
    public function generate_content($images, $prompt)
    private function make_api_request($payload, $attempt = 1)
    
    // Authentication
    private function get_api_key()
    private function validate_credentials()
    
    // Error handling
    private function handle_api_error($response, $attempt)
    private function should_retry($attempt, $error_code)
    
    // Retry logic
    private function calculate_backoff_delay($attempt)
    
    // Response processing
    private function parse_api_response($response)
    private function extract_image_from_response($response)
}
```

**API Configuration:**
- **Endpoint:** `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent`
- **Model:** Gemini 2.5 Flash Image
- **Timeout:** 60 seconds
- **Max Retries:** 3
- **Backoff:** Exponential (1s, 2s, 4s)

### 10. Admin Settings (`AI_Virtual_Fitting_Admin_Settings`)

**Purpose:** Provides WordPress admin interface for plugin configuration.

**Responsibilities:**
- Settings page rendering
- Configuration management
- API key management
- System status display
- Help documentation

**Key Methods:**

```php
class AI_Virtual_Fitting_Admin_Settings {
    // Page management
    public function add_admin_menu()
    public function render_settings_page()
    public function render_help_page()
    
    // Settings
    public function register_settings()
    public function save_settings()
    public function get_setting($key, $default)
    
    // Assets
    public function enqueue_admin_assets()
    
    // AJAX handlers
    public function test_api_connection()
    public function get_system_status()
}
```

**Settings Tabs:**
- **General:** Plugin enable/disable, logging
- **API Configuration:** Google AI Studio credentials
- **Credit System:** Initial credits, package pricing
- **Image Settings:** File size limits, allowed formats
- **Advanced:** Debug mode, cache settings

### 11. Public Interface (`AI_Virtual_Fitting_Public_Interface`)

**Purpose:** Provides frontend user interface for virtual fitting functionality.

**Responsibilities:**
- Frontend page rendering
- AJAX endpoint handling
- Asset enqueuing
- Shortcode registration
- User interaction handling

**Key Methods:**

```php
class AI_Virtual_Fitting_Public_Interface {
    // Page rendering
    public function render_virtual_fitting_page()
    public function render_modern_virtual_fitting_page()
    
    // Shortcodes
    public function register_shortcodes()
    
    // Assets
    public function enqueue_public_assets()
    
    // AJAX handlers
    public function handle_upload_image()
    public function handle_process_fitting()
    public function handle_check_credits()
    public function handle_add_to_cart()
    
    // User interface
    private function render_product_slider()
    private function render_credit_display()
    private function render_upload_form()
}
```

**Frontend Features:**
- **Photo Upload:** Drag-and-drop or file picker
- **Product Slider:** Horizontal scrolling product gallery
- **Credit Display:** Real-time balance updates
- **Result Display:** High-quality image preview
- **Download Button:** Save result to device
- **Checkout Modal:** React-based credit purchase

### Component Interaction Patterns

#### Pattern 1: Request Processing
```
Public Interface → Image Processor → Vertex AI Manager → Google AI Studio
        ↓                                    ↓
   Credit Manager ← Security Manager ← Performance Manager
        ↓
  Database Manager
```

#### Pattern 2: Credit Purchase
```
Public Interface → WooCommerce Integration → WooCommerce
                            ↓
                     Credit Manager
                            ↓
                   Database Manager
                            ↓
                   Analytics Manager
```

#### Pattern 3: Settings Management
```
Admin Settings → Security Manager (encrypt) → Database (wp_options)
                        ↓
                All Components (read settings)
```

### Error Handling Strategy

All components implement consistent error handling:

```php
// Return WP_Error on failure
if ($error_condition) {
    return new WP_Error('error_code', 'Error message', array('context' => 'data'));
}

// Check for errors
if (is_wp_error($result)) {
    $error_code = $result->get_error_code();
    $error_message = $result->get_error_message();
    $error_data = $result->get_error_data();
    
    // Handle error
    $this->log_error($error_code, $error_message, $error_data);
    return false;
}
```

### Logging Strategy

Components use WordPress debug logging:

```php
if (WP_DEBUG && WP_DEBUG_LOG) {
    error_log(sprintf(
        '[AI Virtual Fitting] %s: %s',
        $component_name,
        $message
    ));
}
```

## Database Schema

The plugin uses a custom database schema alongside WordPress core tables and WooCommerce tables. All custom tables use the WordPress table prefix (`wp_` by default).

### Custom Tables

#### 1. Virtual Fitting Credits Table

**Table Name:** `wp_virtual_fitting_credits`

**Purpose:** Stores user credit balances, purchase history, and usage tracking.

**Schema:**
```sql
CREATE TABLE wp_virtual_fitting_credits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    credits_remaining INT DEFAULT 0,
    total_credits_purchased INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_credits_remaining (credits_remaining),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES wp_users(ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Column Descriptions:**

| Column | Type | Description | Constraints |
|--------|------|-------------|-------------|
| `id` | INT | Primary key, auto-increment | PRIMARY KEY |
| `user_id` | BIGINT | WordPress user ID | NOT NULL, FOREIGN KEY |
| `credits_remaining` | INT | Current credit balance | DEFAULT 0 |
| `total_credits_purchased` | INT | Lifetime credits purchased | DEFAULT 0 |
| `created_at` | DATETIME | Record creation timestamp | DEFAULT CURRENT_TIMESTAMP |
| `updated_at` | DATETIME | Last update timestamp | ON UPDATE CURRENT_TIMESTAMP |

**Indexes:**
- `PRIMARY KEY (id)` - Fast lookups by record ID
- `INDEX idx_user_id (user_id)` - Fast lookups by user
- `INDEX idx_credits_remaining (credits_remaining)` - Query users by credit balance
- `INDEX idx_created_at (created_at)` - Time-based queries and reporting

**Relationships:**
- `user_id` → `wp_users.ID` (CASCADE DELETE)

**Usage Patterns:**
```php
// Get user credits
$credits = $wpdb->get_var($wpdb->prepare(
    "SELECT credits_remaining FROM {$table} WHERE user_id = %d",
    $user_id
));

// Deduct credit
$wpdb->query($wpdb->prepare(
    "UPDATE {$table} SET credits_remaining = credits_remaining - 1 
     WHERE user_id = %d AND credits_remaining > 0",
    $user_id
));

// Add credits
$wpdb->query($wpdb->prepare(
    "UPDATE {$table} 
     SET credits_remaining = credits_remaining + %d,
         total_credits_purchased = total_credits_purchased + %d
     WHERE user_id = %d",
    $amount, $amount, $user_id
));
```

#### 2. Virtual Fitting Analytics Table (Optional)

**Table Name:** `wp_virtual_fitting_analytics`

**Purpose:** Stores event tracking, metrics, and usage analytics.

**Schema:**
```sql
CREATE TABLE wp_virtual_fitting_analytics (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NULL,
    event_type VARCHAR(50) NOT NULL,
    event_data JSON,
    processing_time FLOAT NULL,
    success BOOLEAN DEFAULT TRUE,
    error_message TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at),
    INDEX idx_success (success),
    INDEX idx_composite (event_type, created_at, success)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Column Descriptions:**

| Column | Type | Description | Constraints |
|--------|------|-------------|-------------|
| `id` | BIGINT | Primary key, auto-increment | PRIMARY KEY |
| `user_id` | BIGINT | WordPress user ID (nullable for anonymous events) | NULL |
| `event_type` | VARCHAR(50) | Event type identifier | NOT NULL |
| `event_data` | JSON | Event-specific data | NULL |
| `processing_time` | FLOAT | Processing duration in seconds | NULL |
| `success` | BOOLEAN | Event success status | DEFAULT TRUE |
| `error_message` | TEXT | Error message if failed | NULL |
| `created_at` | DATETIME | Event timestamp | DEFAULT CURRENT_TIMESTAMP |

**Event Types:**
- `image_upload` - User uploads photo
- `fitting_request` - Virtual fitting processed
- `fitting_failure` - Processing failed
- `credit_purchase` - Credits purchased
- `image_download` - Result downloaded
- `api_call` - External API called
- `error` - Error occurred

**Indexes:**
- `PRIMARY KEY (id)` - Fast lookups by record ID
- `INDEX idx_user_id (user_id)` - User-specific analytics
- `INDEX idx_event_type (event_type)` - Event type filtering
- `INDEX idx_created_at (created_at)` - Time-based queries
- `INDEX idx_success (success)` - Success/failure filtering
- `INDEX idx_composite (event_type, created_at, success)` - Complex queries

#### 3. Virtual Fitting Sessions Table (Future)

**Table Name:** `wp_virtual_fitting_sessions`

**Purpose:** Stores processing session data for tracking and recovery.

**Schema:**
```sql
CREATE TABLE wp_virtual_fitting_sessions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) UNIQUE NOT NULL,
    user_id BIGINT NOT NULL,
    product_id BIGINT NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    customer_image_path TEXT,
    result_image_path TEXT,
    error_message TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES wp_users(ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Status Values:**
- `pending` - Session created, awaiting processing
- `processing` - Currently being processed
- `completed` - Successfully completed
- `failed` - Processing failed
- `cancelled` - User cancelled

### WordPress Core Tables Used

The plugin integrates with standard WordPress tables:

#### wp_users
**Usage:** User authentication and identification
**Relationships:** 
- `wp_virtual_fitting_credits.user_id` → `wp_users.ID`
- `wp_virtual_fitting_analytics.user_id` → `wp_users.ID`

**Queries:**
```php
// Get user by ID
$user = get_user_by('id', $user_id);

// Check if user is logged in
$current_user_id = get_current_user_id();
```

#### wp_usermeta
**Usage:** Store user-specific plugin settings
**Meta Keys:**
- `ai_virtual_fitting_initial_credits_granted` - Boolean flag
- `ai_virtual_fitting_last_fitting_date` - Timestamp
- `ai_virtual_fitting_total_fittings` - Counter

**Queries:**
```php
// Check if initial credits granted
$granted = get_user_meta($user_id, 'ai_virtual_fitting_initial_credits_granted', true);

// Update user meta
update_user_meta($user_id, 'ai_virtual_fitting_initial_credits_granted', true);
```

#### wp_options
**Usage:** Store plugin settings and configuration
**Option Keys:**
- `ai_virtual_fitting_api_key` - Encrypted API key
- `ai_virtual_fitting_api_provider` - API provider selection
- `ai_virtual_fitting_initial_credits` - Default initial credits
- `ai_virtual_fitting_credits_per_package` - Credits per package
- `ai_virtual_fitting_package_price` - Package price
- `ai_virtual_fitting_credits_product_id` - WooCommerce product ID
- `ai_virtual_fitting_max_image_size` - Max upload size
- `ai_virtual_fitting_allowed_types` - Allowed file types
- `ai_virtual_fitting_version` - Plugin version

**Queries:**
```php
// Get option
$api_key = get_option('ai_virtual_fitting_api_key');

// Update option
update_option('ai_virtual_fitting_api_key', $encrypted_key);

// Delete option
delete_option('ai_virtual_fitting_api_key');
```

### WooCommerce Tables Used

The plugin integrates with WooCommerce database tables:

#### wp_posts (Products)
**Usage:** Store credit package products
**Post Type:** `product`
**Meta Keys:**
- `_virtual_fitting_product` - Identifies credit products
- `_virtual_fitting_credits` - Number of credits in package
- `_price` - Product price
- `_regular_price` - Regular price
- `_virtual` - Virtual product flag

**Queries:**
```php
// Get credit product
$product = wc_get_product($product_id);

// Check if credit product
$is_credit_product = get_post_meta($product_id, '_virtual_fitting_product', true);
```

#### wp_woocommerce_order_items
**Usage:** Track credit purchases in orders
**Item Type:** `line_item`

**Queries:**
```php
// Get order items
$order = wc_get_order($order_id);
$items = $order->get_items();

// Check for credit products
foreach ($items as $item) {
    $product_id = $item->get_product_id();
    if ($this->is_credits_product($product_id)) {
        // Process credit purchase
    }
}
```

### Database Relationship Diagram

```
┌─────────────────┐
│   wp_users      │
│  (WordPress)    │
└────────┬────────┘
         │
         │ user_id (FK)
         │
    ┌────┴────────────────────────────────┐
    │                                     │
    ▼                                     ▼
┌─────────────────────┐      ┌──────────────────────┐
│ wp_virtual_fitting_ │      │ wp_virtual_fitting_  │
│      credits        │      │     analytics        │
│                     │      │                      │
│ - id (PK)          │      │ - id (PK)            │
│ - user_id (FK)     │      │ - user_id (FK)       │
│ - credits_remaining│      │ - event_type         │
│ - total_purchased  │      │ - event_data (JSON)  │
│ - created_at       │      │ - processing_time    │
│ - updated_at       │      │ - success            │
└─────────────────────┘      │ - created_at         │
                             └──────────────────────┘

┌─────────────────┐
│   wp_posts      │
│  (WooCommerce)  │
│                 │
│ - ID (PK)       │
│ - post_type     │◄─── product_id
│   = 'product'   │
└─────────────────┘
         │
         │ post_id (FK)
         │
         ▼
┌─────────────────┐
│  wp_postmeta    │
│  (WooCommerce)  │
│                 │
│ - meta_key      │
│   _virtual_     │
│   fitting_      │
│   product       │
└─────────────────┘

┌──────────────────────┐
│ wp_woocommerce_      │
│    order_items       │
│  (WooCommerce)       │
│                      │
│ - order_item_id (PK) │
│ - order_id (FK)      │
│ - order_item_type    │
└──────────────────────┘
```

### Database Operations

#### Table Creation
```php
public function create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    $sql = "CREATE TABLE {$this->credits_table} (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT NOT NULL,
        credits_remaining INT DEFAULT 0,
        total_credits_purchased INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id)
    ) $charset_collate;";
    
    dbDelta($sql);
}
```

#### Table Verification
```php
public function verify_tables_exist() {
    global $wpdb;
    
    $credits_exists = $wpdb->get_var(
        "SHOW TABLES LIKE '{$this->credits_table}'"
    ) === $this->credits_table;
    
    return $credits_exists;
}
```

#### Data Migration
```php
public function migrate_existing_users() {
    global $wpdb;
    
    // Get all users
    $users = get_users(array('fields' => 'ID'));
    
    foreach ($users as $user_id) {
        // Check if record exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->credits_table} WHERE user_id = %d",
            $user_id
        ));
        
        if (!$exists) {
            // Create record with initial credits
            $wpdb->insert(
                $this->credits_table,
                array(
                    'user_id' => $user_id,
                    'credits_remaining' => 2,
                    'total_credits_purchased' => 0
                ),
                array('%d', '%d', '%d')
            );
        }
    }
}
```

### Database Optimization

#### Index Strategy
- **Primary Keys:** Auto-increment integers for fast lookups
- **Foreign Keys:** Indexed for join performance
- **Composite Indexes:** For common query patterns
- **Covering Indexes:** Include frequently queried columns

#### Query Optimization
```php
// Use prepared statements
$wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d", $user_id);

// Use appropriate indexes
$wpdb->get_results("
    SELECT * FROM {$table} 
    WHERE user_id = {$user_id} 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ORDER BY created_at DESC
");

// Limit result sets
$wpdb->get_results("SELECT * FROM {$table} LIMIT 100");
```

#### Maintenance Tasks
```php
// Optimize tables
$wpdb->query("OPTIMIZE TABLE {$this->credits_table}");

// Clean up old analytics data
$wpdb->query($wpdb->prepare(
    "DELETE FROM {$this->analytics_table} 
     WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
    90
));

// Rebuild indexes
$wpdb->query("ANALYZE TABLE {$this->credits_table}");
```

### Backup and Recovery

#### Backup Strategy
```bash
# Backup custom tables
mysqldump -u username -p database_name \
    wp_virtual_fitting_credits \
    wp_virtual_fitting_analytics \
    > virtual_fitting_backup.sql

# Backup with WordPress data
wp db export virtual_fitting_full_backup.sql
```

#### Recovery
```bash
# Restore custom tables
mysql -u username -p database_name < virtual_fitting_backup.sql

# Restore with WordPress
wp db import virtual_fitting_full_backup.sql
```

### Data Integrity

#### Constraints
- Foreign key constraints ensure referential integrity
- CASCADE DELETE removes orphaned records
- NOT NULL constraints prevent invalid data
- DEFAULT values ensure consistent state

#### Validation
```php
// Validate before insert
if (!$user_id || !is_numeric($user_id)) {
    return new WP_Error('invalid_user_id', 'Invalid user ID');
}

// Check for existing record
$exists = $wpdb->get_var($wpdb->prepare(
    "SELECT id FROM {$table} WHERE user_id = %d",
    $user_id
));

if ($exists) {
    return new WP_Error('duplicate_record', 'Record already exists');
}
```

### Performance Considerations

- **Connection Pooling:** WordPress handles connection pooling
- **Query Caching:** Use WordPress transients for frequently accessed data
- **Batch Operations:** Use bulk inserts/updates when possible
- **Index Maintenance:** Regular ANALYZE TABLE operations
- **Data Archiving:** Move old analytics data to archive tables

## API Integration

The plugin integrates with multiple external APIs and WordPress/WooCommerce internal APIs to provide comprehensive functionality.

### Google AI Studio Integration

#### Overview

The plugin uses Google AI Studio's Gemini 2.5 Flash Image model for AI-powered virtual fitting generation.

**API Endpoint:**
```
https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent
```

**Authentication:** API Key (passed as query parameter)

**Model:** `gemini-2.5-flash`

**Capabilities:**
- Multi-modal input (text + images)
- Image generation
- Context understanding
- High-speed processing

#### API Request Structure

```php
// Request payload
$payload = array(
    'contents' => array(
        array(
            'parts' => array(
                // Text prompt
                array(
                    'text' => $prompt
                ),
                // Customer image
                array(
                    'inline_data' => array(
                        'mime_type' => 'image/jpeg',
                        'data' => base64_encode($customer_image_data)
                    )
                ),
                // Product images (multiple)
                array(
                    'inline_data' => array(
                        'mime_type' => 'image/jpeg',
                        'data' => base64_encode($product_image_data)
                    )
                )
            )
        )
    ),
    'generationConfig' => array(
        'temperature' => 0.7,
        'maxOutputTokens' => 1024,
        'topP' => 0.8,
        'topK' => 40
    )
);

// Make request
$response = wp_remote_post(
    $api_endpoint . '?key=' . $api_key,
    array(
        'headers' => array(
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($payload),
        'timeout' => 60,
        'sslverify' => true
    )
);
```

#### API Response Structure

**Success Response:**
```json
{
  "candidates": [
    {
      "content": {
        "parts": [
          {
            "text": "Generated image description",
            "inlineData": {
              "mimeType": "image/jpeg",
              "data": "base64_encoded_image_data"
            }
          }
        ]
      },
      "finishReason": "STOP",
      "safetyRatings": [...]
    }
  ]
}
```

**Error Response:**
```json
{
  "error": {
    "code": 400,
    "message": "Invalid request",
    "status": "INVALID_ARGUMENT"
  }
}
```

#### Error Handling

```php
private function handle_api_error($response, $attempt) {
    $error_code = wp_remote_retrieve_response_code($response);
    
    switch ($error_code) {
        case 400: // Bad Request
            $this->log_error('Invalid API request', array(
                'attempt' => $attempt,
                'response' => wp_remote_retrieve_body($response)
            ));
            return false; // Don't retry
            
        case 401: // Unauthorized
            $this->log_error('API authentication failed - check API key');
            return false; // Don't retry
            
        case 403: // Forbidden
            $this->log_error('API access forbidden - check permissions');
            return false; // Don't retry
            
        case 429: // Rate Limit
            $this->log_error('API rate limit exceeded', array('attempt' => $attempt));
            return $this->should_retry($attempt); // Retry with backoff
            
        case 500: // Server Error
        case 502: // Bad Gateway
        case 503: // Service Unavailable
            $this->log_error('API server error', array(
                'code' => $error_code,
                'attempt' => $attempt
            ));
            return $this->should_retry($attempt); // Retry with backoff
            
        default:
            $this->log_error('Unknown API error', array(
                'code' => $error_code,
                'attempt' => $attempt
            ));
            return false;
    }
}
```

#### Retry Logic

The plugin implements exponential backoff for transient errors:

```php
private function make_api_request($payload, $attempt = 1) {
    $max_attempts = 3;
    
    // Make request
    $response = wp_remote_post($this->api_endpoint, $args);
    
    // Check for errors
    if (is_wp_error($response)) {
        if ($attempt < $max_attempts) {
            // Calculate backoff delay
            $delay = pow(2, $attempt - 1); // 1s, 2s, 4s
            sleep($delay);
            
            // Retry
            return $this->make_api_request($payload, $attempt + 1);
        }
        
        return $response; // Max attempts reached
    }
    
    // Check HTTP status
    $status_code = wp_remote_retrieve_response_code($response);
    
    if ($status_code >= 500 && $attempt < $max_attempts) {
        // Server error - retry with backoff
        $delay = pow(2, $attempt - 1);
        sleep($delay);
        return $this->make_api_request($payload, $attempt + 1);
    }
    
    return $response;
}
```

#### Rate Limiting

**Google AI Studio Limits:**
- **Requests per minute:** 60
- **Requests per day:** 1,500 (free tier)
- **Max request size:** 20MB
- **Max response size:** 10MB

**Plugin Rate Limiting:**
```php
public function check_rate_limit($user_id) {
    $cache_key = "api_rate_limit_{$user_id}";
    $requests = wp_cache_get($cache_key, 'ai_virtual_fitting');
    
    if ($requests === false) {
        $requests = 0;
    }
    
    // Allow 10 requests per minute per user
    if ($requests >= 10) {
        return new WP_Error('rate_limit', 'Rate limit exceeded. Please try again later.');
    }
    
    // Increment counter
    wp_cache_set($cache_key, $requests + 1, 'ai_virtual_fitting', 60);
    
    return true;
}
```

#### API Configuration

```php
// Configuration options
$config = array(
    'api_endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent',
    'api_key' => get_option('ai_virtual_fitting_api_key'),
    'timeout' => 60, // seconds
    'max_retries' => 3,
    'retry_delay' => 1, // seconds (exponential backoff)
    'max_image_size' => 10485760, // 10MB
    'temperature' => 0.7,
    'max_output_tokens' => 1024
);
```

### WordPress API Usage

#### REST API Endpoints

The plugin registers custom REST API endpoints for AJAX operations:

```php
// Register endpoints
add_action('rest_api_init', function() {
    register_rest_route('ai-virtual-fitting/v1', '/upload', array(
        'methods' => 'POST',
        'callback' => array($this, 'handle_upload'),
        'permission_callback' => array($this, 'check_permissions')
    ));
    
    register_rest_route('ai-virtual-fitting/v1', '/process', array(
        'methods' => 'POST',
        'callback' => array($this, 'handle_process'),
        'permission_callback' => array($this, 'check_permissions')
    ));
    
    register_rest_route('ai-virtual-fitting/v1', '/credits', array(
        'methods' => 'GET',
        'callback' => array($this, 'get_credits'),
        'permission_callback' => array($this, 'check_permissions')
    ));
});
```

**Endpoint URLs:**
- `POST /wp-json/ai-virtual-fitting/v1/upload` - Upload customer image
- `POST /wp-json/ai-virtual-fitting/v1/process` - Process virtual fitting
- `GET /wp-json/ai-virtual-fitting/v1/credits` - Get user credit balance

#### AJAX Handlers

The plugin uses WordPress AJAX for frontend operations:

```php
// Register AJAX handlers
add_action('wp_ajax_ai_virtual_fitting_upload', array($this, 'handle_upload'));
add_action('wp_ajax_ai_virtual_fitting_process', array($this, 'handle_process'));
add_action('wp_ajax_ai_virtual_fitting_check_credits', array($this, 'check_credits'));
add_action('wp_ajax_ai_virtual_fitting_add_to_cart', array($this, 'add_to_cart'));

// AJAX handler example
public function handle_upload() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'ai_virtual_fitting_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'));
    }
    
    // Check user authentication
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Please log in'));
    }
    
    // Process upload
    $result = $this->process_upload($_FILES['image']);
    
    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    }
    
    wp_send_json_success($result);
}
```

**AJAX Request Example:**
```javascript
jQuery.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'ai_virtual_fitting_upload',
        nonce: ai_virtual_fitting.nonce,
        image: imageData
    },
    success: function(response) {
        if (response.success) {
            console.log('Upload successful', response.data);
        } else {
            console.error('Upload failed', response.data.message);
        }
    }
});
```

#### WordPress HTTP API

The plugin uses WordPress HTTP API for external requests:

```php
// GET request
$response = wp_remote_get('https://api.example.com/data', array(
    'timeout' => 30,
    'headers' => array(
        'Authorization' => 'Bearer ' . $token
    )
));

// POST request
$response = wp_remote_post('https://api.example.com/data', array(
    'timeout' => 60,
    'headers' => array(
        'Content-Type' => 'application/json'
    ),
    'body' => json_encode($data)
));

// Check for errors
if (is_wp_error($response)) {
    $error_message = $response->get_error_message();
    error_log('HTTP request failed: ' . $error_message);
    return false;
}

// Get response data
$status_code = wp_remote_retrieve_response_code($response);
$body = wp_remote_retrieve_body($response);
$data = json_decode($body, true);
```

### WooCommerce Integration

#### Product API

The plugin uses WooCommerce product API for credit package management:

```php
// Create product
$product = new WC_Product_Simple();
$product->set_name('Virtual Fitting Credits - 20 Pack');
$product->set_regular_price(10.00);
$product->set_virtual(true);
$product->set_downloadable(false);
$product->set_sold_individually(false);
$product->set_catalog_visibility('visible');
$product->set_status('publish');

// Add custom meta
$product->update_meta_data('_virtual_fitting_product', 'yes');
$product->update_meta_data('_virtual_fitting_credits', 20);

// Save product
$product_id = $product->save();

// Get product
$product = wc_get_product($product_id);
$price = $product->get_price();
$credits = $product->get_meta('_virtual_fitting_credits');
```

#### Order API

The plugin hooks into WooCommerce order processing:

```php
// Hook into order completion
add_action('woocommerce_payment_complete', array($this, 'handle_payment_complete'));
add_action('woocommerce_order_status_completed', array($this, 'handle_order_completed'));

// Process order
public function handle_order_completed($order_id) {
    $order = wc_get_order($order_id);
    
    if (!$order) {
        return;
    }
    
    // Get order items
    $items = $order->get_items();
    
    foreach ($items as $item) {
        $product_id = $item->get_product_id();
        
        // Check if credit product
        if ($this->is_credits_product($product_id)) {
            $product = wc_get_product($product_id);
            $credits = $product->get_meta('_virtual_fitting_credits');
            $quantity = $item->get_quantity();
            $total_credits = $credits * $quantity;
            
            // Add credits to user account
            $user_id = $order->get_user_id();
            $this->credit_manager->add_credits($user_id, $total_credits);
            
            // Add order note
            $order->add_order_note(
                sprintf(
                    __('%d virtual fitting credits added to user account.', 'ai-virtual-fitting'),
                    $total_credits
                )
            );
        }
    }
}
```

#### Cart API

The plugin integrates with WooCommerce cart:

```php
// Add to cart
public function add_credits_to_cart($quantity = 1) {
    $product_id = $this->get_or_create_credits_product();
    
    if (!$product_id) {
        return false;
    }
    
    // Add to cart
    $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity);
    
    if ($cart_item_key) {
        return array(
            'cart_item_key' => $cart_item_key,
            'cart_url' => wc_get_cart_url(),
            'checkout_url' => wc_get_checkout_url()
        );
    }
    
    return false;
}

// AJAX add to cart
public function ajax_add_credits_to_cart() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'virtual_fitting_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'));
    }
    
    // Add to cart
    $result = $this->add_credits_to_cart();
    
    if ($result) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error(array('message' => 'Failed to add to cart'));
    }
}
```

#### Customer API

The plugin uses WooCommerce customer data:

```php
// Get customer
$customer = new WC_Customer($user_id);

// Get customer data
$email = $customer->get_email();
$first_name = $customer->get_first_name();
$last_name = $customer->get_last_name();

// Get order history
$orders = wc_get_orders(array(
    'customer_id' => $user_id,
    'limit' => -1
));

// Get total spent
$total_spent = $customer->get_total_spent();
```

### API Security

#### Authentication

```php
// Nonce verification
if (!wp_verify_nonce($_POST['nonce'], 'ai_virtual_fitting_nonce')) {
    wp_send_json_error(array('message' => 'Security check failed'));
}

// User authentication
if (!is_user_logged_in()) {
    wp_send_json_error(array('message' => 'Please log in'));
}

// Capability check
if (!current_user_can('manage_options')) {
    wp_send_json_error(array('message' => 'Insufficient permissions'));
}
```

#### API Key Encryption

```php
// Encrypt API key
public function encrypt_api_key($api_key) {
    $method = 'AES-256-CBC';
    $key = wp_salt('auth');
    $iv = substr(wp_salt('secure_auth'), 0, 16);
    
    $encrypted = openssl_encrypt($api_key, $method, $key, 0, $iv);
    return base64_encode($encrypted);
}

// Decrypt API key
public function decrypt_api_key($encrypted_key) {
    $method = 'AES-256-CBC';
    $key = wp_salt('auth');
    $iv = substr(wp_salt('secure_auth'), 0, 16);
    
    $encrypted = base64_decode($encrypted_key);
    return openssl_decrypt($encrypted, $method, $key, 0, $iv);
}
```

#### Input Validation

```php
// Validate and sanitize inputs
$user_id = absint($_POST['user_id']);
$product_id = absint($_POST['product_id']);
$email = sanitize_email($_POST['email']);
$text = sanitize_text_field($_POST['text']);
$url = esc_url_raw($_POST['url']);

// Validate file upload
$allowed_types = array('image/jpeg', 'image/png', 'image/webp');
if (!in_array($_FILES['image']['type'], $allowed_types)) {
    return new WP_Error('invalid_type', 'Invalid file type');
}
```

### API Monitoring

#### Request Logging

```php
public function log_api_request($endpoint, $request, $response, $duration) {
    if (WP_DEBUG && WP_DEBUG_LOG) {
        error_log(sprintf(
            '[AI Virtual Fitting API] %s - Duration: %.2fs - Status: %d',
            $endpoint,
            $duration,
            wp_remote_retrieve_response_code($response)
        ));
    }
    
    // Track in analytics
    $this->analytics_manager->track_event('api_call', array(
        'endpoint' => $endpoint,
        'duration' => $duration,
        'status_code' => wp_remote_retrieve_response_code($response)
    ));
}
```

#### Performance Tracking

```php
public function track_api_performance($operation, $callback) {
    $start_time = microtime(true);
    
    $result = call_user_func($callback);
    
    $duration = microtime(true) - $start_time;
    
    $this->analytics_manager->track_metric('api_response_time', $duration, 'seconds');
    
    return $result;
}
```

## Hooks and Filters

The plugin provides 50+ action hooks and filter hooks for extensibility and customization. All hooks follow WordPress naming conventions and are prefixed with `ai_virtual_fitting_`.

### Action Hooks

Action hooks allow you to execute custom code at specific points in the plugin's execution flow.

#### Plugin Lifecycle Hooks

```php
/**
 * Fired when plugin is activated
 * 
 * @since 1.0.0
 */
do_action('ai_virtual_fitting_activated');

/**
 * Fired when plugin is deactivated
 * 
 * @since 1.0.0
 */
do_action('ai_virtual_fitting_deactivated');

/**
 * Fired after plugin initialization
 * 
 * @since 1.0.0
 */
do_action('ai_virtual_fitting_initialized');
```

**Usage Example:**
```php
add_action('ai_virtual_fitting_activated', function() {
    // Custom activation logic
    error_log('AI Virtual Fitting plugin activated');
    
    // Send notification email
    wp_mail(
        get_option('admin_email'),
        'Plugin Activated',
        'AI Virtual Fitting plugin has been activated.'
    );
});
```

#### Credit System Hooks

```php
/**
 * Fired when credits are added to user account
 * 
 * @param int $user_id User ID
 * @param int $amount Number of credits added
 * @param string $source Source of credits (purchase, grant, admin)
 * 
 * @since 1.0.0
 */
do_action('ai_virtual_fitting_credit_added', $user_id, $amount, $source);

/**
 * Fired when credit is deducted from user account
 * 
 * @param int $user_id User ID
 * @param int $credits_remaining Remaining credits after deduction
 * 
 * @since 1.0.0
 */
do_action('ai_virtual_fitting_credit_deducted', $user_id, $credits_remaining);

/**
 * Fired when initial credits are granted to new user
 * 
 * @param int $user_id User ID
 * @param int $credits Number of initial credits granted
 * 
 * @since 1.0.0
 */
do_action('ai_virtual_fitting_initial_credits_granted', $user_id, $credits);

/**
 * Fired when user has insufficient credits
 * 
 * @param int $user_id User ID
 * @param int $required_credits Required credits
 * @param int $available_credits Available credits
 * 
 * @since 1.0.0
 */
do_action('ai_virtual_fitting_insufficient_credits', $user_id, $required_credits, $available_credits);
```

**Usage Example:**
```php
// Send email when credits are low
add_action('ai_virtual_fitting_credit_deducted', function($user_id, $credits_remaining) {
    if ($credits_remaining <= 2) {
        $user = get_user_by('id', $user_id);
        wp_mail(
            $user->user_email,
            'Low Credit Balance',
            "You have {$credits_remaining} credits remaining. Purchase more to continue using virtual fitting."
        );
    }
}, 10, 2);
```

#### Processing Hooks

```php
/**
 * Fired when virtual fitting processing starts
 * 
 * @param int $user_id User ID
 * @param int $product_id Product ID
 * @param string $image_path Customer image path
 * 
 * @since 1.0.0
 */
do_action('ai_virtual_fitting_processing_started', $user_id, $product_id, $image_path);

/**
 * Fired when virtual fitting processing completes successfully
 * 
 * @param int $user_id User ID
 * @param int $product_id Product ID
 * @param array $result Processing result data
 * 
 * @since 1.0.0
 */
do_action('ai_virtual_fitting_processing_complete', $user_id, $product_id, $result);

/**
 * Fired when virtual fitting processing fails
 * 
 * @param int $user_id User ID
 * @param int $product_id Product ID
 * @param WP_Error $error Error object
 * 
 * @since 1.0.0
 */
do_action('ai_virtual_fitting_processing_failed', $user_id, $product_id, $error);

/**
 * Fired before API call is made
 * 
 * @param array $payload API request payload
 * @param int $attempt Attempt number
 * 
 * @since 1.0.0
 */
do_action('ai_virtual_fitting_before_api_call', $payload, $attempt);

/**
 * Fired after API call completes
 * 
 * @param array $response API response
 * @param float $duration Request duration in seconds
 * 
 * @since 1.0.0
 */
do_action('ai_virtual_fitting_after_api_call', $response, $duration);
```

**Usage Example:**
```php
// Track processing time
add_action('ai_virtual_fitting_processing_complete', function($user_id, $product_id, $result) {
    $processing_time = $result['processing_time'] ?? 0;
    
    // Log slow processing
    if ($processing_time > 30) {
        error_log("Slow processing detected: {$processing_time}s for user {$user_id}");
    }
}, 10, 3);
```

#### Image Hooks

```php
/**
 * Fired when image is uploaded
 * 
 * @param int $user_id User ID
 * @param array $file_info File information
 * 
 * @since 1.0.0
 */
do_action('ai_virtual_fitting_image_uploaded', $user_id, $file_info);

/**
 * Fired when image validation fails
 * 
 * @param int $user_id User ID
 * @param WP_Error $error Validation error
 * 
 * @since 1.0.0
 */
do_action('ai_virtual_fitting_image_validation_failed', $user_id, $error);

/**
 * Fired when result image is downloaded
 * 
 * @param int $user_id User ID
 * @param string $result_file Result file path
 * 
 * @since 1.0.0
 */
do_action('ai_virtual_fitting_image_downloaded', $user_id, $result_file);

/**
 * Fired before temporary files are cleaned up
 * 
 * @param array $files Array of file paths to be deleted
 * 
 * @since 1.0.0
 */
do_action('ai_virtual_fitting_before_cleanup', $files);
```

#### WooCommerce Integration Hooks

```php
/**
 * Fired when credit product is created
 * 
 * @param int $product_id Product ID
 * @param int $credits Number of credits in package
 * @param float $price Product price
 * 
 * @since 1.0.0
 */
do_action('ai_virtual_fitting_product_created', $product_id, $credits, $price);

/**
 * Fired when credit purchase order is processed
 * 
 * @param int $order_id Order ID
 * @param int $user_id User ID
 * @param int $credits_added Credits added
 * 
 * @since 1.0.0
 */
do_action('ai_virtual_fitting_purchase_processed', $order_id, $user_id, $credits_added);

/**
 * Fired when credits are added to cart
 * 
 * @param int $product_id Product ID
 * @param int $quantity Quantity
 * @param string $cart_item_key Cart item key
 * 
 * @since 1.0.0
 */
do_action('ai_virtual_fitting_added_to_cart', $product_id, $quantity, $cart_item_key);
```

#### Analytics Hooks

```php
/**
 * Fired when analytics event is tracked
 * 
 * @param string $event_type Event type
 * @param array $event_data Event data
 * @param int $user_id User ID
 * 
 * @since 1.0.0
 */
do_action('ai_virtual_fitting_event_tracked', $event_type, $event_data, $user_id);

/**
 * Fired when metric is recorded
 * 
 * @param string $metric_name Metric name
 * @param mixed $value Metric value
 * @param string $unit Unit of measurement
 * 
 * @since 1.0.0
 */
do_action('ai_virtual_fitting_metric_recorded', $metric_name, $value, $unit);
```

### Filter Hooks

Filter hooks allow you to modify data before it's used by the plugin.

#### Credit System Filters

```php
/**
 * Filter initial credits for new users
 * 
 * @param int $credits Default initial credits (2)
 * @param int $user_id User ID
 * 
 * @return int Modified initial credits
 * 
 * @since 1.0.0
 */
$initial_credits = apply_filters('ai_virtual_fitting_initial_credits', 2, $user_id);

/**
 * Filter credits per package
 * 
 * @param int $credits Default credits per package (20)
 * 
 * @return int Modified credits per package
 * 
 * @since 1.0.0
 */
$credits_per_package = apply_filters('ai_virtual_fitting_credits_per_package', 20);

/**
 * Filter package price
 * 
 * @param float $price Default package price (10.00)
 * 
 * @return float Modified package price
 * 
 * @since 1.0.0
 */
$package_price = apply_filters('ai_virtual_fitting_package_price', 10.00);

/**
 * Filter credit cost per fitting
 * 
 * @param int $cost Default cost (1 credit)
 * @param int $user_id User ID
 * @param int $product_id Product ID
 * 
 * @return int Modified credit cost
 * 
 * @since 1.0.0
 */
$credit_cost = apply_filters('ai_virtual_fitting_credit_cost', 1, $user_id, $product_id);
```

**Usage Example:**
```php
// Give premium members more initial credits
add_filter('ai_virtual_fitting_initial_credits', function($credits, $user_id) {
    $user = get_user_by('id', $user_id);
    
    if (in_array('premium_member', $user->roles)) {
        return 10; // Premium members get 10 free credits
    }
    
    return $credits;
}, 10, 2);

// Discount for wholesale customers
add_filter('ai_virtual_fitting_package_price', function($price) {
    if (current_user_can('wholesale_customer')) {
        return $price * 0.8; // 20% discount
    }
    
    return $price;
});
```

#### Image Processing Filters

```php
/**
 * Filter maximum image upload size
 * 
 * @param int $size Default max size in bytes (10485760 = 10MB)
 * 
 * @return int Modified max size
 * 
 * @since 1.0.0
 */
$max_image_size = apply_filters('ai_virtual_fitting_max_image_size', 10485760);

/**
 * Filter allowed image types
 * 
 * @param array $types Default allowed types
 * 
 * @return array Modified allowed types
 * 
 * @since 1.0.0
 */
$allowed_types = apply_filters('ai_virtual_fitting_allowed_types', array(
    'image/jpeg',
    'image/png',
    'image/webp'
));

/**
 * Filter image quality for optimization
 * 
 * @param int $quality Default quality (90)
 * 
 * @return int Modified quality (1-100)
 * 
 * @since 1.0.0
 */
$image_quality = apply_filters('ai_virtual_fitting_image_quality', 90);

/**
 * Filter maximum image dimensions
 * 
 * @param array $dimensions Default dimensions
 * 
 * @return array Modified dimensions
 * 
 * @since 1.0.0
 */
$max_dimensions = apply_filters('ai_virtual_fitting_max_dimensions', array(
    'width' => 4096,
    'height' => 4096
));
```

**Usage Example:**
```php
// Add AVIF support
add_filter('ai_virtual_fitting_allowed_types', function($types) {
    $types[] = 'image/avif';
    return $types;
});

// Increase max size for premium users
add_filter('ai_virtual_fitting_max_image_size', function($size) {
    if (current_user_can('premium_member')) {
        return 20971520; // 20MB for premium
    }
    
    return $size;
});
```

#### API Configuration Filters

```php
/**
 * Filter API timeout
 * 
 * @param int $timeout Default timeout in seconds (60)
 * 
 * @return int Modified timeout
 * 
 * @since 1.0.0
 */
$api_timeout = apply_filters('ai_virtual_fitting_api_timeout', 60);

/**
 * Filter API retry attempts
 * 
 * @param int $attempts Default retry attempts (3)
 * 
 * @return int Modified retry attempts
 * 
 * @since 1.0.0
 */
$retry_attempts = apply_filters('ai_virtual_fitting_retry_attempts', 3);

/**
 * Filter API prompt
 * 
 * @param string $prompt Default prompt
 * @param array $product_data Product data
 * 
 * @return string Modified prompt
 * 
 * @since 1.0.0
 */
$api_prompt = apply_filters('ai_virtual_fitting_api_prompt', $default_prompt, $product_data);

/**
 * Filter API request payload
 * 
 * @param array $payload Default payload
 * @param array $images Image data
 * @param string $prompt Prompt text
 * 
 * @return array Modified payload
 * 
 * @since 1.0.0
 */
$payload = apply_filters('ai_virtual_fitting_api_payload', $payload, $images, $prompt);
```

**Usage Example:**
```php
// Customize prompt for specific product categories
add_filter('ai_virtual_fitting_api_prompt', function($prompt, $product_data) {
    if ($product_data['category'] === 'evening-gowns') {
        return "Create an elegant virtual fitting for this evening gown with dramatic lighting...";
    }
    
    return $prompt;
}, 10, 2);

// Increase timeout for complex requests
add_filter('ai_virtual_fitting_api_timeout', function($timeout) {
    return 90; // 90 seconds
});
```

#### Performance Filters

```php
/**
 * Filter cache duration
 * 
 * @param int $duration Default duration in seconds (3600)
 * @param string $cache_key Cache key
 * 
 * @return int Modified duration
 * 
 * @since 1.0.0
 */
$cache_duration = apply_filters('ai_virtual_fitting_cache_duration', 3600, $cache_key);

/**
 * Filter queue size
 * 
 * @param int $size Default queue size (10)
 * 
 * @return int Modified queue size
 * 
 * @since 1.0.0
 */
$queue_size = apply_filters('ai_virtual_fitting_queue_size', 10);

/**
 * Filter whether to use queue
 * 
 * @param bool $use_queue Default (false)
 * @param int $system_load Current system load
 * 
 * @return bool Modified setting
 * 
 * @since 1.0.0
 */
$use_queue = apply_filters('ai_virtual_fitting_use_queue', false, $system_load);
```

#### UI Customization Filters

```php
/**
 * Filter virtual fitting page template
 * 
 * @param string $template Default template path
 * 
 * @return string Modified template path
 * 
 * @since 1.0.0
 */
$template = apply_filters('ai_virtual_fitting_page_template', $default_template);

/**
 * Filter product slider settings
 * 
 * @param array $settings Default slider settings
 * 
 * @return array Modified settings
 * 
 * @since 1.0.0
 */
$slider_settings = apply_filters('ai_virtual_fitting_slider_settings', array(
    'items_per_page' => 5,
    'autoplay' => false,
    'loop' => true
));

/**
 * Filter button text
 * 
 * @param string $text Default button text
 * @param string $button_type Button type (upload, process, download, purchase)
 * 
 * @return string Modified button text
 * 
 * @since 1.0.0
 */
$button_text = apply_filters('ai_virtual_fitting_button_text', $default_text, $button_type);
```

### Hook Priority and Execution Order

WordPress executes hooks in priority order (default priority is 10):

```php
// Early execution (priority 5)
add_action('ai_virtual_fitting_processing_started', 'my_early_function', 5);

// Default execution (priority 10)
add_action('ai_virtual_fitting_processing_started', 'my_function');

// Late execution (priority 20)
add_action('ai_virtual_fitting_processing_started', 'my_late_function', 20);
```

### Removing Hooks

```php
// Remove action
remove_action('ai_virtual_fitting_credit_added', 'my_function', 10);

// Remove filter
remove_filter('ai_virtual_fitting_initial_credits', 'my_filter', 10);

// Remove all actions for a hook
remove_all_actions('ai_virtual_fitting_processing_complete');

// Remove all filters for a hook
remove_all_filters('ai_virtual_fitting_api_prompt');
```

### Hook Documentation Template

When creating custom hooks, follow this documentation template:

```php
/**
 * Hook description
 * 
 * Detailed explanation of when this hook fires and what it does.
 * 
 * @param type $param1 Parameter description
 * @param type $param2 Parameter description
 * 
 * @return type Return value description (for filters only)
 * 
 * @since 1.0.0
 * 
 * @example
 * add_action('hook_name', function($param1, $param2) {
 *     // Custom code
 * }, 10, 2);
 */
do_action('hook_name', $param1, $param2);
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