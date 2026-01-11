# WooCommerce Product Migration System
## Brides and Tailor → Local WordPress

This project provides a streamlined system for migrating products from bridesandtailor.com to a local WordPress development environment.

## 🚀 Quick Start

### 1. Start Local Environment
```bash
docker-compose up -d
```

### 2. Access Services
- **WordPress**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081

### 3. Run Migration Process
```bash
# Phase 1: Cleanup and test
node clear-local-store.js
node migrate-single-product.js

# Phase 2: Scale up
node migrate-batch-5.js
node migrate-batch-10.js
node migrate-batch-50.js
```

## 📁 Project Structure

### Core Migration Files
- `PRODUCT-MIGRATION-GUIDE.md` - Complete migration guide
- `clear-local-store.js` - Cleanup local products
- `migrate-single-product.js` - Single product test
- `migrate-batch-5.js` - 5-product batch test
- `migrate-batch-10.js` - 10-product batch test
- `migrate-batch-50.js` - Full 50-product migration

### Configuration Files
- `docker-compose.yml` - Local WordPress environment
- `woocommerce-environments.json` - API credentials
- `local-woocommerce-credentials.json` - Local API keys

### Documentation
- `local-setup-instructions.md` - Setup guide
- `migration-log.json` - Generated during migration
- `migration-final-report.json` - Final results

## 🎯 Migration Process

1. **Preparation**: Clear local store and test single product
2. **Validation**: Test with 5 and 10 product batches
3. **Full Migration**: Complete 50-product migration
4. **Verification**: Review results and test functionality

## 📊 Features

- ✅ Complete product data migration
- ✅ Image download and upload (4 per product)
- ✅ Category and tag mapping
- ✅ Error handling and retry logic
- ✅ Progress tracking and logging
- ✅ Comprehensive reporting

## 🔧 Technical Details

- **Source**: bridesandtailor.com (WooCommerce REST API)
- **Target**: localhost:8080 (Local WordPress)
- **Method**: MCP (Model Context Protocol) function calls
- **Images**: Automatic download and upload
- **Logging**: Detailed JSON logs for troubleshooting

## 📋 Requirements

- Docker and Docker Compose
- Node.js (for migration scripts)
- MCP servers configured for both environments
- Stable internet connection
- Sufficient disk space for images

---

**Status**: Ready for migration  
**Last Updated**: January 8, 2026