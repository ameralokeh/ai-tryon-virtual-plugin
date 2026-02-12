# Release Notes - Version 1.0.7.8

**Release Date:** January 23, 2026

## Bug Fixes

### Admin Dashboard - Try-On Button Statistics
- **Fixed:** Try-On button statistics not displaying in admin panel
- **Issue:** JavaScript files were not being enqueued due to incorrect hook name check
- **Solution:** Updated `enqueue_admin_scripts()` to check for both `toplevel_page_` and `settings_page_` hooks
- **Impact:** Button statistics now load automatically and display correctly:
  - Total button clicks
  - Conversions (users who completed virtual fitting)
  - Conversion rate percentage
  - Most popular products table

### UI Improvements
- **Fixed:** Preview image not filling container edge-to-edge
- **Changed:** Main preview image now scales to 100% width/height with `object-fit: contain`
- **Changed:** Removed `max-height: 60%` override in mobile media query
- **Impact:** Dress images now display with minimal white space in the preview container

## Technical Changes

### JavaScript Enhancements
- Added automatic loading of button statistics on admin page load
- Added console logging for debugging AJAX calls
- Improved error handling in statistics loading

### Version Updates
- Plugin version bumped from 1.0.7.7 to 1.0.7.8
- CSS version updated to 1.7.19 for cache busting

## Files Modified

- `ai-virtual-fitting/ai-virtual-fitting.php` - Version bump
- `ai-virtual-fitting/admin/class-admin-settings.php` - Fixed script enqueue hook
- `ai-virtual-fitting/admin/js/admin-settings.js` - Auto-load statistics, added logging
- `ai-virtual-fitting/public/css/modern-virtual-fitting.css` - Image scaling fixes
- `ai-virtual-fitting/public/class-public-interface.php` - CSS version bump

## Database

No database changes required. Existing analytics tables work correctly:
- `wp_ai_virtual_fitting_events` - Tracks button clicks and conversions
- `wp_ai_virtual_fitting_analytics` - Stores aggregated statistics

## Upgrade Notes

- Clear browser cache after updating (Cmd+Shift+R or Ctrl+Shift+R)
- No manual database updates required
- Statistics will load automatically on admin page

## Known Issues

None

## Testing Performed

- ✅ Button statistics display correctly in admin panel
- ✅ AJAX calls return proper data from database
- ✅ Preview images scale correctly without white space
- ✅ JavaScript loads on admin page
- ✅ Console logging confirms proper execution flow
