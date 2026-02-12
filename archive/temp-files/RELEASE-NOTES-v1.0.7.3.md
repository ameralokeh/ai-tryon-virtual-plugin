# Release Notes - v1.0.7.3

**Release Date:** January 19, 2026  
**Type:** Critical Hotfix  
**Status:** Ready for Production Deployment

## Critical Fixes

### 🔴 Credit Addition Bug Fix
**Issue:** Credits were not being added to customer accounts after successful WooCommerce order completion.

**Root Cause:**
- Product missing `_virtual_fitting_product` meta field required by `is_credits_product()` validation
- Meta key mismatch: code checked `_virtual_fitting_credits` but product had `_ai_virtual_fitting_credits`

**Solution:**
1. Added `_virtual_fitting_product` = 'yes' meta when creating credit products
2. Added migration function `fix_existing_credit_products()` that runs on plugin activation
3. Updated credit amount lookup to check both meta keys for backwards compatibility
4. Changed product visibility to 'private' status

**Files Modified:**
- `includes/class-woocommerce-integration.php`
- `includes/class-virtual-credit-system.php`
- `includes/class-virtual-fitting-core.php`

**Testing:**
- ✅ Migration successfully adds missing meta to existing products
- ✅ Test order completed and credits added correctly
- ✅ Verified on local WordPress environment

## Previous Fixes Included

### Product Restore from Trash (v1.0.7.0)
- Detects and restores credit products moved to trash
- Ensures product is in 'publish' status before use

### Cart Conflict Modal UI (v1.0.7.1)
- Replaced browser `window.confirm()` with professional modal screen
- Added "Clear Cart & Continue" and "Proceed to Checkout" buttons
- Improved user experience during cart conflicts

### UI Improvements (v1.0.7.0)
- Removed hardcoded $10.00 price display
- Improved error screen layout and styling

## Deployment Notes

### Production Deployment Required
This is a **critical hotfix** that must be deployed to production immediately to fix the credit addition issue.

### Post-Deployment Actions
After deploying v1.0.7.3 to production:

1. **Migration will run automatically** on plugin activation
2. **Manual credit addition required** for 5 completed orders that didn't receive credits:
   - Order 30391
   - Order 30368
   - Order 30361
   - Order 30358
   - Order 30338

### Manual Credit Addition
Use WP-CLI or WordPress admin to manually add credits for affected orders:
```bash
# Via WP-CLI (if available)
wp user meta update USER_ID _virtual_fitting_credits NEW_BALANCE

# Or via WordPress admin:
# Users → Edit User → Custom Fields → _virtual_fitting_credits
```

## Version History

- **v1.0.7.3** - Critical credit addition bug fix + migration
- **v1.0.7.2** - Internal testing version
- **v1.0.7.1** - Cart conflict modal UI
- **v1.0.7.0** - Product restore from trash + UI improvements

## Package Information

- **File:** `ai-virtual-fitting-v1.0.7.3.zip`
- **Size:** 235 KB
- **Plugin Version:** 1.0.7.3
- **WordPress Tested:** 5.0+
- **WooCommerce Tested:** 5.0+
- **PHP Required:** 7.4+

## Support

For issues or questions:
- Check `INSTALLATION-GUIDE.md` for setup instructions
- Review `vertex-ai-setup-guide.md` for API configuration
- Contact: amer_okeh@hotmail.com
