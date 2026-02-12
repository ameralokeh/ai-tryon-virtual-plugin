# Release Notes - Version 1.0.9.5

**Release Date:** January 31, 2025  
**Type:** Feature Release

## 🎨 New Features

### Product Image Overlay Button
- Added premium overlay "Try On" button on product images
- Circular button with glass effect and soft shadows
- Positioned in bottom-right corner of main product image
- Admin toggle to enable/disable overlay button
- Same functionality as main Try-On button (login handling, navigation, analytics)

## ✨ Enhancements

### Premium Styling
- Glass highlight effect with enhanced visibility
- Subtle always-on purple glow around button
- Smooth hover animations with enhanced glow
- Optimized icon size (28px) and text spacing
- Dark scrim behind button for better contrast on bright images
- WooCommerce theme compatibility with high-specificity CSS overrides

### User Experience
- Button appears directly on product image for immediate action
- Responsive design for mobile, tablet, and desktop
- Keyboard accessible with proper focus states
- Respects reduced motion preferences
- Touch-friendly with minimum 44px tap target

## 🔧 Technical Changes

### Files Modified
1. `ai-virtual-fitting.php` - Version bump to 1.0.9.5
2. `public/class-tryon-button.php` - Added overlay button setting and initialization
3. `public/js/virtual-tryon-button.js` - Added overlay button JavaScript with proper event handling
4. `public/css/virtual-tryon-button.css` - Added premium overlay button styling with theme overrides
5. `admin/class-admin-settings.php` - Added admin setting for overlay button toggle

### CSS Enhancements
- CSS variables for consistent theming (--vf-accent-1, --vf-accent-2, --vf-ring)
- Pseudo-elements for glass highlight (::before) and glow ring (::after)
- High-specificity WooCommerce overrides to ensure styling works across themes
- Smooth transitions (0.18s) with GPU acceleration

## 📋 Admin Settings

New setting added to **Try-On Button Settings**:
- **Show Overlay Button on Product Image** - Toggle to display circular overlay button on main product image

## 🎯 User Impact

- More prominent call-to-action on product pages
- Reduced friction - users can try on directly from product image
- Better visual hierarchy with premium glass effect
- Consistent experience across all WooCommerce themes

## 🔄 Upgrade Notes

- No database changes required
- Overlay button is disabled by default
- Enable in: WordPress Admin → AI Virtual Fitting → Try-On Button Settings
- Compatible with all existing features and settings

## 📦 Package Information

- **Version:** 1.0.9.5
- **Size:** ~275 KB
- **PHP Version:** 7.4+
- **WordPress Version:** 5.0+
- **WooCommerce Version:** 5.0+

## 🐛 Bug Fixes

- Fixed Gemini API endpoint (gemini-3-pro-image-preview) in v1.0.9.4
- Enhanced CSS specificity to override WooCommerce theme styles
- Improved event handling to prevent parent link interference

## 📝 Notes

- Overlay button feature is production-ready
- Tested on local WordPress with WooCommerce
- Premium styling optimized for wedding dress product images
- Full backward compatibility with previous versions
