# Project Structure

## Root Directory Organization

### Plugin Directory (`ai-virtual-fitting/`)
Main WordPress plugin with all core functionality

#### Core Plugin Files
- `ai-virtual-fitting.php` - Main plugin file with activation/deactivation hooks
- `README.md` - User documentation and installation guide
- `DEVELOPER.md` - Technical documentation for developers
- `uninstall.php` - Cleanup script for plugin uninstallation
- `phpunit.xml` - PHPUnit test configuration

#### Core Classes (`includes/`)
- `class-virtual-fitting-core.php` - Main orchestrator and plugin initialization
- `class-credit-manager.php` - Credit allocation, deduction, and tracking
- `class-image-processor.php` - Image upload, validation, and AI processing
- `class-woocommerce-integration.php` - WooCommerce product and order management
- `class-database-manager.php` - Database operations and table management
- `class-performance-manager.php` - Performance optimization and caching
- `class-analytics-manager.php` - Usage tracking and metrics
- `class-vertex-ai-manager.php` - Google AI Studio API integration

#### Admin Interface (`admin/`)
- `class-admin-settings.php` - Admin settings management
- `admin-settings-page.php` - Settings page template
- `help-documentation.php` - Help and documentation page
- `css/admin-settings.css` - Admin styles
- `js/admin-settings.js` - Admin JavaScript

#### Public Interface (`public/`)
- `class-public-interface.php` - Frontend functionality
- `virtual-fitting-page.php` - Virtual fitting page template
- `modern-virtual-fitting-page.php` - Modern UI version
- `css/virtual-fitting.css` - Frontend styles
- `css/modern-virtual-fitting.css` - Modern UI styles
- `js/virtual-fitting.js` - Frontend JavaScript
- `js/modern-virtual-fitting.js` - Modern UI JavaScript
- `js/checkout-modal-react.js` - React-based checkout modal

#### Test Suite (`tests/`)
- `bootstrap.php` - Test environment setup
- `wp-test-runner.php` - WordPress test runner
- `simple-test-runner.php` - Standalone test runner
- `test-*.php` - Unit, integration, and property-based tests

#### Assets (`assets/`)
- `images/` - Plugin images and graphics
- `icons/` - Icon files

#### Translations (`languages/`)
- `ai-virtual-fitting.pot` - Translation template

### Root Configuration Files
- `docker-compose.yml` - Local WordPress environment setup
- `dynamic-woocommerce-mcp.js` - WooCommerce MCP server
- `woocommerce-environments.json` - Multi-environment API credentials
- `local-woocommerce-credentials.json` - Local API keys

### Documentation Files
- `README.md` - Project overview
- `local-setup-instructions.md` - Local environment setup
- `vertex-ai-setup-guide.md` - Google AI Studio configuration
- `AUDIT-SUMMARY.md` - Code audit results
- `HARDCODED-ELEMENTS-AUDIT.md` - Hardcoded elements analysis
- `PROJECT-TRACKER.md` - Project status tracking

### Kiro Configuration (`.kiro/`)
- `specs/` - Feature specifications and task tracking
- `steering/` - Agent steering rules and guidelines
- `settings/` - Kiro IDE settings

## File Naming Conventions

### PHP Classes
- Pattern: `class-{component-name}.php`
- Examples: `class-credit-manager.php`, `class-image-processor.php`

### Test Files
- Pattern: `test-{component}-{type}.php`
- Examples: `test-credit-manager-simple.php`, `test-integration-workflow.php`

### Documentation
- Guides: `{TOPIC}-GUIDE.md` or `{topic}-instructions.md`
- Reports: `{TYPE}-SUMMARY.md` or `{TYPE}-AUDIT.md`

### Assets
- CSS: `{component-name}.css`
- JavaScript: `{component-name}.js`
- React: `{component-name}-react.jsx`

## Data Flow Architecture

### Virtual Fitting Workflow
1. **User Input**: Customer uploads photo via frontend interface
2. **Validation**: Image processor validates format, size, and dimensions
3. **Credit Check**: Credit manager verifies available credits
4. **Product Images**: WooCommerce integration retrieves product images
5. **AI Processing**: Vertex AI manager sends request to Google AI Studio
6. **Result Processing**: Image processor handles API response
7. **Credit Deduction**: Credit manager deducts credit from user account
8. **Result Delivery**: Frontend displays result with download option

### Credit Purchase Workflow
1. **User Action**: Customer clicks "Buy More Credits"
2. **WooCommerce Cart**: Credit product added to cart
3. **Checkout**: Standard WooCommerce checkout process
4. **Order Completion**: Order status changes to completed
5. **Credit Addition**: WooCommerce integration adds credits to user account
6. **Notification**: User receives confirmation

## Environment Separation

### Local Development Environment
- **URL**: http://localhost:8080
- **WordPress Admin**: http://localhost:8080/wp-admin
- **phpMyAdmin**: http://localhost:8081
- **MCP Functions**: `mcp_woocommerce_local_*`
- **Purpose**: Plugin development and testing

### Docker Containers
- **wordpress_site**: WordPress application container
- **wordpress_db**: MySQL 8.0 database container
- **phpmyadmin**: Database management interface

### Database Tables
- `wp_virtual_fitting_credits` - User credit tracking
- `wp_posts` - WooCommerce products (credit packages)
- `wp_postmeta` - Product metadata
- `wp_woocommerce_order_items` - Order tracking