# WooCommerce Product Migration Guide
## Brides and Tailor → Local WordPress

### 🎯 Objective
Migrate products from bridesandtailor.com to local WordPress (localhost:8080) with complete data integrity including images, categories, and metadata.

### 📋 Requirements
- **Source**: bridesandtailor.com (Production WooCommerce)
- **Target**: localhost:8080 (Local WooCommerce)
- **Product Count**: 50 products
- **Images**: 4 images per product (featured + gallery)
- **Data**: Complete product information, categories, tags, variations

### 🔧 Prerequisites
1. **Local WordPress Setup**
   - Docker environment running (docker-compose up -d)
   - WordPress accessible at http://localhost:8080
   - WooCommerce plugin activated
   - API keys configured

2. **MCP Configuration**
   - Production MCP server: `mcp_woocommerce_production_*`
   - Local MCP server: `mcp_woocommerce_local_*`
   - Both servers enabled and authenticated

### 🚀 Migration Process

#### Phase 1: Preparation (REQUIRED)
1. **Clear Local Products**
   ```
   Run: node clear-local-store.js
   ```
   - Removes all existing products from local store
   - Cleans up orphaned images and data
   - Resets product counters

2. **Test Single Product**
   ```
   Run: node migrate-single-product.js
   ```
   - Migrates one product as proof of concept
   - Validates image download and upload
   - Confirms data mapping accuracy

#### Phase 2: Scaled Migration
3. **Migrate 5 Products**
   ```
   Run: node migrate-batch-5.js
   ```
   - Tests batch processing
   - Validates error handling
   - Confirms performance

4. **Migrate 10 Products**
   ```
   Run: node migrate-batch-10.js
   ```
   - Larger batch test
   - Memory usage validation
   - Progress tracking

5. **Full Migration (50 Products)**
   ```
   Run: node migrate-batch-50.js
   ```
   - Complete migration
   - Full progress reporting
   - Error recovery

### 📊 Success Criteria
- ✅ All products migrated with correct data
- ✅ All images downloaded and properly assigned
- ✅ Categories and tags correctly mapped
- ✅ No data corruption or loss
- ✅ Complete audit trail in migration-log.json

### 🔍 Validation Steps
1. **Product Count Verification**
   - Production count matches local count
   - All SKUs properly transferred

2. **Image Verification**
   - Featured images assigned correctly
   - Gallery images in proper order
   - No broken image links

3. **Data Integrity Check**
   - Prices, descriptions, and metadata accurate
   - Categories and tags properly assigned
   - Product variations (if any) correctly mapped

### 📁 File Structure
```
├── clear-local-store.js          # Phase 1: Cleanup
├── migrate-single-product.js     # Phase 1: Single test
├── migrate-batch-5.js           # Phase 2: Small batch
├── migrate-batch-10.js          # Phase 2: Medium batch  
├── migrate-batch-50.js          # Phase 2: Full migration
├── migration-log.json           # Progress tracking
└── PRODUCT-MIGRATION-GUIDE.md   # This guide
```

### ⚠️ Important Notes
- **Always run phases in order** - each builds on the previous
- **Monitor disk space** - images can consume significant storage
- **Check migration-log.json** for detailed progress and errors
- **Backup local database** before starting migration
- **Test thoroughly** after each phase

### 🆘 Troubleshooting
- **API Rate Limits**: Scripts include automatic retry logic
- **Image Download Failures**: Individual image errors logged, migration continues
- **Memory Issues**: Batch sizes optimized for stability
- **Network Timeouts**: Automatic retry with exponential backoff

---
**Created**: January 8, 2026  
**Status**: Ready for execution  
**Next Step**: Run clear-local-store.js