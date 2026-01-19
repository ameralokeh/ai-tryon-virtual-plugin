# Technology Stack

## Core Technologies
- **PHP 7.4+**: WordPress plugin development and server-side logic
- **WordPress 5.0+**: CMS platform and plugin framework
- **WooCommerce 5.0+**: E-commerce integration for credit purchases
- **MySQL 8.0**: Database backend for WordPress and plugin data
- **Docker & Docker Compose**: Local development environment containerization
- **JavaScript/jQuery**: Frontend interactivity and AJAX requests
- **React**: Modern checkout modal component
- **CSS3**: Responsive styling and animations

## External Services
- **Google AI Studio**: Gemini 2.5 Flash Image model for AI processing
- **WordPress REST API**: Internal API for AJAX operations
- **WooCommerce REST API**: Product and order management

## Key WordPress APIs & Features
- **WordPress Plugin API**: Hooks, filters, and actions
- **WordPress Database API**: `$wpdb` for database operations
- **WordPress HTTP API**: `wp_remote_post()` for external API calls
- **WordPress Transients API**: Caching and temporary data storage
- **WordPress User API**: Authentication and user management
- **WooCommerce Hooks**: Order processing and product management

## Development Tools
- **PHPUnit**: Unit testing framework
- **Eris**: Property-based testing library
- **PHP_CodeSniffer**: WordPress coding standards enforcement
- **WP-CLI**: WordPress command-line interface
- **Composer**: PHP dependency management (for testing)

## Environment Configuration
- **Local Development**: http://localhost:8080
- **WordPress Admin**: http://localhost:8080/wp-admin
- **phpMyAdmin**: http://localhost:8081
- **Virtual Fitting Page**: http://localhost:8080/virtual-fitting

## Common Commands

### Environment Management
```bash
# Start local WordPress environment
docker-compose up -d

# Stop environment
docker-compose down

# View WordPress logs
docker logs wordpress_site --tail=50

# View database logs
docker logs wordpress_db --tail=50

# Restart WordPress container
docker-compose restart wordpress
```

### WordPress CLI (WP-CLI)
```bash
# Access WordPress container
docker exec -it wordpress_site bash

# List plugins
docker exec -it wordpress_site wp plugin list

# Activate plugin
docker exec -it wordpress_site wp plugin activate ai-virtual-fitting

# Deactivate plugin
docker exec -it wordpress_site wp plugin deactivate ai-virtual-fitting

# List users
docker exec -it wordpress_site wp user list

# Create test user
docker exec -it wordpress_site wp user create testuser test@example.com --role=customer

# Flush rewrite rules
docker exec -it wordpress_site wp rewrite flush
```

### Plugin Development
```bash
# Copy plugin to WordPress
docker cp ai-virtual-fitting/ wordpress_site:/var/www/html/wp-content/plugins/

# Set proper permissions
docker exec -it wordpress_site chown -R www-data:www-data /var/www/html/wp-content/plugins/ai-virtual-fitting

# Run plugin tests
php ai-virtual-fitting/tests/wp-test-runner.php

# Run specific test
php ai-virtual-fitting/tests/wp-test-runner.php test-credit-manager

# Run simple tests
php ai-virtual-fitting/tests/simple-test-runner.php
```

### Database Operations
```bash
# Access MySQL CLI
docker exec -it wordpress_db mysql -u root -prootpassword wordpress

# Check plugin tables
docker exec -it wordpress_db mysql -u root -prootpassword wordpress \
  -e "SHOW TABLES LIKE 'wp_virtual_fitting%';"

# View credit records
docker exec -it wordpress_db mysql -u root -prootpassword wordpress \
  -e "SELECT * FROM wp_virtual_fitting_credits;"

# Backup database
docker exec wordpress_db mysqldump -u root -prootpassword wordpress > backup.sql

# Restore database
docker exec -i wordpress_db mysql -u root -prootpassword wordpress < backup.sql
```

### Service Access
- **WordPress Frontend**: http://localhost:8080
- **WordPress Admin**: http://localhost:8080/wp-admin
- **Virtual Fitting Page**: http://localhost:8080/virtual-fitting
- **phpMyAdmin**: http://localhost:8081
- **WooCommerce**: http://localhost:8080/wp-admin/admin.php?page=wc-admin

### API Testing
```bash
# Test WooCommerce API connection
curl -u "ck_766bbfe203974f257f6e0f4fb6fc7dec:cs_548afe062b47d36477507316615de236" \
  "http://localhost:8080/wp-json/wc/v3/system_status"

# Test plugin AJAX endpoint
curl -X POST http://localhost:8080/wp-admin/admin-ajax.php \
  -d "action=ai_virtual_fitting_process" \
  -d "nonce=YOUR_NONCE"
```

## MCP Server Configuration
- **Local WooCommerce Server**: `mcp_woocommerce_local_*` functions
- **Production WooCommerce Server**: `mcp_woocommerce_production_*` functions
- **Dynamic Server**: `dynamic-woocommerce-mcp.js` for environment switching

## File Formats
- **PHP**: Plugin classes, templates, and WordPress integration
- **JavaScript**: Frontend interactivity and AJAX handlers
- **CSS**: Styling and responsive design
- **JSON**: Configuration files and API responses
- **Markdown**: Documentation and guides
- **SQL**: Database schema and queries
- **POT**: Translation template files

## Performance Optimization
- **Object Caching**: WordPress transients for API responses
- **Image Optimization**: Automatic resizing and compression
- **Asynchronous Processing**: Non-blocking AI requests
- **Database Indexing**: Optimized queries with proper indexes
- **Queue Management**: Request queuing for high traffic