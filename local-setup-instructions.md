# Local WooCommerce MCP Setup Instructions

## ✅ What's Already Done

1. **WooCommerce Plugin**: Downloaded and installed in `/wp-content/plugins/woocommerce/`
2. **API Keys**: Generated and stored in database
   - **Consumer Key**: `ck_766bbfe203974f257f6e0f4fb6fc7dec`
   - **Consumer Secret**: `cs_548afe062b47d36477507316615de236`
3. **MCP Configuration**: Updated with local credentials
4. **Environment Config**: Updated `woocommerce-environments.json`

## 🔧 Manual Steps Required

### Step 1: Complete WordPress Setup (if needed)
1. Go to: http://localhost:8080
2. If you see WordPress installation screen, complete the setup:
   - Site Title: "Local Development"
   - Username: admin
   - Password: (choose a secure password)
   - Email: your-email@example.com

### Step 2: Activate WooCommerce
1. Go to: http://localhost:8080/wp-admin
2. Login with your WordPress admin credentials
3. Navigate to **Plugins → Installed Plugins**
4. Find "WooCommerce" and click **Activate**
5. Follow the WooCommerce setup wizard (you can skip most steps for development)

### Step 3: Verify API Keys
1. In WordPress admin, go to **WooCommerce → Settings → Advanced → REST API**
2. You should see "Local Development MCP" key listed
3. If not, click **Add Key** and create:
   - Description: "Local Development MCP"
   - User: admin
   - Permissions: Read/Write

### Step 4: Test API Connection
Run this command to test:
```bash
curl -u "ck_766bbfe203974f257f6e0f4fb6fc7dec:cs_548afe062b47d36477507316615de236" \
  "http://localhost:8080/wp-json/wc/v3/system_status"
```

### Step 5: Enable Local MCP Server
Your MCP configuration has been updated. The `woocommerce-local` server is now:
- ✅ Enabled (`disabled: false`)
- ✅ Configured with correct URL and API keys
- ✅ Ready to use

## 🚀 Using the Local MCP

Once activated, you can use these MCP functions for your local WordPress:

- `mcp_woocommerce_local_get_products`
- `mcp_woocommerce_local_get_orders`
- `mcp_woocommerce_local_get_customers`
- And all other WooCommerce functions with `_local` suffix

## 🔄 Switching Between Environments

You now have multiple WooCommerce environments configured:

1. **Production**: `woocommerce-production` (bridesandtailor.com)
2. **Local**: `woocommerce-local` (localhost:8080)
3. **Dynamic**: `woocommerce-dynamic` (can switch environments)

Enable/disable them in your MCP config as needed!

## 📁 Files Created

- `local-woocommerce-credentials.json` - API credentials
- `woocommerce-environments.json` - Environment configuration
- `dynamic-woocommerce-mcp.js` - Dynamic MCP server
- `setup-local-woocommerce.php` - Setup script (in WordPress container)

## 🆘 Troubleshooting

If API calls fail:
1. Ensure WooCommerce is activated
2. Check that WordPress permalinks are set (Settings → Permalinks → Save)
3. Verify API keys in WooCommerce settings
4. Test with curl command above