# Technology Stack

## Core Technologies
- **Node.js**: Migration scripts and MCP server implementation
- **Docker & Docker Compose**: Local WordPress environment containerization
- **WordPress**: Target CMS platform with WooCommerce plugin
- **MySQL 8.0**: Database backend for WordPress
- **PHP**: WordPress setup scripts and WooCommerce integration

## Key Libraries & Frameworks
- **@modelcontextprotocol/sdk**: MCP server implementation
- **@woocommerce/woocommerce-rest-api**: WooCommerce API client
- **WordPress REST API**: WooCommerce v3 endpoints

## Environment Configuration
- **Production**: bridesandtailor.com (source)
- **Local**: localhost:8080 (target)
- **Staging**: staging.bridesandtailor.com (optional)

## Common Commands

### Environment Management
```bash
# Start local WordPress environment
docker-compose up -d

# Stop environment
docker-compose down

# View logs
docker-compose logs -f wordpress
```

### Migration Process
```bash
# Phase 1: Preparation
node clear-local-store.js
node migrate-single-product.js

# Phase 2: Batch Migration
node migrate-batch-5.js
node migrate-batch-10.js
node migrate-batch-50.js
```

### Service Access
- **WordPress**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081
- **WordPress Admin**: http://localhost:8080/wp-admin

### API Testing
```bash
# Test WooCommerce API connection
curl -u "consumer_key:consumer_secret" \
  "http://localhost:8080/wp-json/wc/v3/system_status"
```

## MCP Server Configuration
- **Production Server**: `mcp_woocommerce_production_*` functions
- **Local Server**: `mcp_woocommerce_local_*` functions  
- **Dynamic Server**: Environment switching capabilities

## File Formats
- **JSON**: Configuration files, credentials, migration logs
- **Markdown**: Documentation and guides
- **JavaScript**: Migration scripts and MCP server
- **PHP**: WordPress setup and configuration scripts