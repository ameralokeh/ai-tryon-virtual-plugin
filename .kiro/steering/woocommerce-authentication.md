# WooCommerce Authentication & API Access

## API Authentication Methods

### 1. REST API Keys (Primary Method)
- **Consumer Key**: `ck_766bbfe203974f257f6e0f4fb6fc7dec`
- **Consumer Secret**: `cs_548afe062b47d36477507316615de236`
- **Permissions**: Read/Write
- **Description**: Local Development MCP

### 2. Authentication Headers
```bash
# Basic Auth (recommended for local development)
curl -u "ck_766bbfe203974f257f6e0f4fb6fc7dec:cs_548afe062b47d36477507316615de236" \
  "http://localhost:8080/wp-json/wc/v3/products"

# Query Parameters (alternative)
curl "http://localhost:8080/wp-json/wc/v3/products?consumer_key=ck_766bbfe203974f257f6e0f4fb6fc7dec&consumer_secret=cs_548afe062b47d36477507316615de236"
```

## MCP Server Configuration

### Local WooCommerce MCP Server
```json
{
  "woocommerce-local": {
    "command": "node",
    "args": ["/path/to/dynamic-woocommerce-mcp.js"],
    "env": {
      "WC_URL": "http://localhost:8080",
      "WC_CONSUMER_KEY": "ck_766bbfe203974f257f6e0f4fb6fc7dec",
      "WC_CONSUMER_SECRET": "cs_548afe062b47d36477507316615de236"
    },
    "disabled": false
  }
}
```

### Available MCP Functions
- `mcp_woocommerce_local_get_products` - List products
- `mcp_woocommerce_local_get_product` - Get single product
- `mcp_woocommerce_local_search_products` - Search products
- `mcp_woocommerce_local_get_orders` - List orders
- `mcp_woocommerce_local_get_customers` - List customers
- `mcp_woocommerce_local_get_product_categories` - List categories
- `mcp_woocommerce_local_get_product_tags` - List tags

## WordPress User Authentication

### Admin User Access
- **Username**: admin
- **Role**: Administrator
- **Capabilities**: Full site management
- **API Key Owner**: Yes (can create/manage API keys)

### User Management via WP-CLI
```bash
# List all users
docker exec -it wordpress_site wp user list

# Create new user
docker exec -it wordpress_site wp user create newuser user@example.com --role=administrator

# Update user password
docker exec -it wordpress_site wp user update admin --user_pass=newpassword

# Grant capabilities
docker exec -it wordpress_site wp user add-cap admin manage_woocommerce
```

## API Key Management

### Creating New API Keys
```bash
# Via WordPress Admin
# 1. Go to WooCommerce → Settings → Advanced → REST API
# 2. Click "Add Key"
# 3. Set description, user, and permissions
# 4. Copy generated keys

# Via WP-CLI (if available)
docker exec -it wordpress_site wp wc api_key create \
  --description="New API Key" \
  --user=admin \
  --permissions=read_write
```

### Revoking API Keys
```bash
# Via WordPress Admin
# 1. Go to WooCommerce → Settings → Advanced → REST API
# 2. Find the key and click "Revoke"

# Via Database (emergency)
docker exec -it wordpress_db mysql -u root -prootpassword wordpress \
  -e "DELETE FROM wp_woocommerce_api_keys WHERE description='Local Development MCP';"
```

## Security Considerations

### Local Development Security
- **HTTP Only**: No SSL required for localhost
- **Network Isolation**: Containers accessible only on localhost
- **Default Passwords**: Change for production use
- **API Key Scope**: Limited to read/write operations

### Production Security Notes
- **HTTPS Required**: Always use SSL in production
- **Key Rotation**: Regularly rotate API keys
- **Permission Scoping**: Use minimal required permissions
- **IP Restrictions**: Consider IP-based access controls

## Testing Authentication

### Quick API Test
```bash
# Test system status endpoint
curl -u "ck_766bbfe203974f257f6e0f4fb6fc7dec:cs_548afe062b47d36477507316615de236" \
  "http://localhost:8080/wp-json/wc/v3/system_status"

# Test products endpoint
curl -u "ck_766bbfe203974f257f6e0f4fb6fc7dec:cs_548afe062b47d36477507316615de236" \
  "http://localhost:8080/wp-json/wc/v3/products?per_page=1"
```

### MCP Function Test
```javascript
// Test via Kiro MCP functions
mcp_woocommerce_local_get_products({per_page: 5})
```

## Troubleshooting Authentication

### Common Issues
1. **401 Unauthorized**: Check API keys and permissions
2. **403 Forbidden**: Verify user has WooCommerce capabilities
3. **404 Not Found**: Ensure WooCommerce is activated and permalinks are set
4. **SSL Errors**: Use HTTP for localhost, HTTPS for production

### Debug Steps
```bash
# Check WooCommerce status
docker exec -it wordpress_site wp plugin status woocommerce

# Verify API keys in database
docker exec -it wordpress_db mysql -u root -prootpassword wordpress \
  -e "SELECT * FROM wp_woocommerce_api_keys;"

# Check WordPress permalinks
docker exec -it wordpress_site wp rewrite flush
```