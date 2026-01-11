# Project Structure

## Root Directory Organization

### Core Migration Scripts
- `clear-local-store.js` - Phase 1: Cleanup local products
- `migrate-single-product.js` - Phase 1: Single product test
- `migrate-batch-5.js` - Phase 2: Small batch migration
- `migrate-batch-10.js` - Phase 2: Medium batch migration  
- `migrate-batch-50.js` - Phase 2: Full migration

### Configuration Files
- `docker-compose.yml` - Local WordPress environment setup
- `woocommerce-environments.json` - Multi-environment API credentials
- `local-woocommerce-credentials.json` - Local API keys (generated)
- `setup-local-woocommerce.php` - WordPress setup automation

### MCP Integration
- `dynamic-woocommerce-mcp.js` - Dynamic MCP server with environment switching

### Documentation
- `README.md` - Project overview and quick start
- `PRODUCT-MIGRATION-GUIDE.md` - Detailed migration process
- `local-setup-instructions.md` - Local environment setup
- `PROJECT-TRACKER.md` - Project status tracking

### Migration Reports (Generated)
- `migration-log.json` - Real-time migration progress
- `migrated_products.json` - Product migration results
- `MIGRATION-SUMMARY-5-PRODUCTS.md` - 5-product batch report
- `COMPLETE-MIGRATION-SUMMARY-20-PRODUCTS.md` - 20-product batch report
- `FINAL-MIGRATION-REPORT.md` - Complete migration summary

### Hidden Directories
- `.kiro/` - Kiro IDE configuration and steering rules
- `.vscode/` - VS Code workspace settings

## File Naming Conventions

### Migration Scripts
- Pattern: `migrate-{scope}.js` or `{action}-{scope}.js`
- Examples: `migrate-single-product.js`, `clear-local-store.js`

### Documentation
- Guides: `{TOPIC}-GUIDE.md` (uppercase)
- Reports: `{TYPE}-REPORT.md` or `{TYPE}-SUMMARY.md`
- Instructions: `{purpose}-instructions.md` (lowercase)

### Configuration
- Environment configs: `{service}-environments.json`
- Credentials: `{service}-credentials.json`
- Docker: `docker-compose.yml`

## Data Flow Architecture

1. **Source**: Production WooCommerce (bridesandtailor.com)
2. **Transport**: MCP functions via `mcp_woocommerce_production_*`
3. **Processing**: Node.js migration scripts
4. **Target**: Local WordPress via `mcp_woocommerce_local_*`
5. **Logging**: JSON files for tracking and reporting

## Environment Separation

### Production Environment
- URL: https://www.bridesandtailor.com
- MCP Functions: `mcp_woocommerce_production_*`
- Purpose: Source data for migration

### Local Environment  
- URL: http://localhost:8080
- MCP Functions: `mcp_woocommerce_local_*`
- Purpose: Migration target and development

### Dynamic Environment
- Server: `dynamic-woocommerce-mcp.js`
- Purpose: Environment switching and testing