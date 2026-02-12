# Release Notes - Version 1.0.9.7

**Release Date:** February 11, 2026

## Overview

This release updates the consent checkbox with comprehensive legal terms to protect both the business and users. The new consent includes age verification, content guidelines, AI processing consent, and links to Terms & Privacy Policy.

## Changes

### Legal & Compliance

#### Enhanced Consent Checkbox
- **Updated consent terms** with comprehensive legal protections
- Added age verification (18+ years old)
- Added photo ownership/permission confirmation
- Added content appropriateness confirmation (no nudity/inappropriate content)
- Added explicit AI processing consent for Brides & Tailor
- Added simulation disclaimer (results not guarantee of final fit)
- Added clickable link to Terms & Privacy Policy (https://bridesandtailor.com/terms-and-conditions/)
- Updated checkbox label to "I confirm and agree to all of the above"
- Added `required` attribute for HTML5 validation

#### UI Improvements
- Added "I confirm that:" header for clarity
- Added footer text: "By uploading your photo, you agree to these guidelines."
- Styled Terms link with proper hover/focus states for accessibility
- Maintained existing consent box functionality (checkbox reveals upload area)

### Technical Details

**Files Modified:**
- `ai-virtual-fitting/public/modern-virtual-fitting-page.php` - Updated consent HTML
- `ai-virtual-fitting/public/css/modern-virtual-fitting.css` - Added link and footer styling
- `ai-virtual-fitting/ai-virtual-fitting.php` - Version bump to 1.0.9.7

## Legal Protection

The new consent provides protection against:
- Underage users
- Unauthorized photo usage
- Inappropriate content uploads
- Misunderstanding of AI simulation results
- Disputes about data processing

## Deployment

### Files to Upload to Production:
```
ai-virtual-fitting/ai-virtual-fitting.php
ai-virtual-fitting/public/modern-virtual-fitting-page.php
ai-virtual-fitting/public/css/modern-virtual-fitting.css
```

### Deployment Steps:
1. Backup current production files
2. Upload modified files via FTP
3. Verify consent box displays correctly
4. Test checkbox functionality
5. Verify Terms link opens correctly

## Testing Checklist

- [ ] Consent box displays with all new terms
- [ ] Terms & Privacy Policy link opens in new tab
- [ ] Checkbox must be checked to proceed
- [ ] Upload area appears after checking consent
- [ ] Mobile responsive display works correctly
- [ ] Link hover/focus states work properly

## Compatibility

- WordPress: 5.0+
- WooCommerce: 5.0+
- PHP: 7.4+
- Browsers: Modern browsers with CSS3 support

## Notes

- Consent is only shown in modern virtual fitting interface
- Regular virtual fitting page does not have consent (consider adding if needed)
- Consent state is stored in JavaScript session (not persistent across page reloads)
- Users must re-consent each time they visit the page

## Previous Version

Upgrading from: 1.0.9.6 (Automatic cleanup manager for customer uploads)

---

**Version:** 1.0.9.7  
**Build Date:** February 11, 2026  
**Package:** ai-virtual-fitting-v1.0.9.7.zip
