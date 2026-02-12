# Release Notes - Version 1.0.9.4

**Release Date:** January 31, 2026  
**Type:** Critical Bug Fix  
**Status:** Production Ready

## Overview

Version 1.0.9.4 fixes a critical bug where the wrong Gemini API endpoint was hardcoded in multiple files, causing "Request contains an invalid argument" errors in production. This release ensures the correct `gemini-3-pro-image-preview` model is used for image generation.

## Critical Fixes

### 🔴 Wrong API Endpoint (CRITICAL)

**Problem:**
- Two files had incorrect hardcoded endpoint: `gemini-2.5-flash-image`
- This model doesn't exist or doesn't support the image generation format
- Caused all virtual fitting requests to fail with "invalid argument" error
- Issue persisted even after plugin re-upload due to multiple file locations

**Files Fixed:**
1. `admin/class-admin-settings.php` - Admin UI display of default endpoint
2. `includes/class-image-processor.php` - Actual API endpoint constant used in requests

**Solution:**
- Changed `DEFAULT_GEMINI_IMAGE_API_ENDPOINT` from `gemini-2.5-flash-image` to `gemini-3-pro-image-preview`
- Verified endpoint matches working v1.0.9.1 configuration
- Tested API request format is identical to working version

## Changes

### Modified Files
- `ai-virtual-fitting/ai-virtual-fitting.php` - Version bump to 1.0.9.4
- `ai-virtual-fitting/admin/class-admin-settings.php` - Fixed default endpoint display
- `ai-virtual-fitting/includes/class-image-processor.php` - Fixed API endpoint constant

### Constants Updated
```php
// OLD (WRONG)
const DEFAULT_GEMINI_IMAGE_API_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent';

// NEW (CORRECT)
const DEFAULT_GEMINI_IMAGE_API_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3-pro-image-preview:generateContent';
```

## Technical Details

### Root Cause Analysis

1. **v1.0.9.3 introduced wrong endpoint** in attempt to update to newer Gemini model
2. **Model doesn't exist** - `gemini-2.5-flash-image` is not a valid Gemini model name
3. **Multiple file locations** - Endpoint was hardcoded in 2 separate files
4. **Database persistence** - Settings persist even after plugin re-upload
5. **Production impact** - All virtual fitting requests failed with cryptic error

### API Request Format (Verified Correct)

The API request format is identical to working v1.0.9.1:
```json
{
  "contents": [{
    "parts": [
      {"text": "prompt"},
      {"inline_data": {"mime_type": "image/jpeg", "data": "base64..."}}
    ]
  }],
  "generationConfig": {
    "responseModalities": ["TEXT", "IMAGE"],
    "imageConfig": {"aspectRatio": "1:1", "imageSize": "1K"}
  }
}
```

### Verification Steps

1. ✅ Downloaded production files and confirmed wrong endpoint
2. ✅ Checked production logs showing endpoint in use
3. ✅ Compared with v1.0.9.1 (working version)
4. ✅ Uploaded corrected files to production
5. ✅ Verified endpoint now matches working configuration

## Upgrade Instructions

### From v1.0.9.3 to v1.0.9.4

**Critical:** This is a required update if experiencing "Request contains an invalid argument" errors.

1. **Upload plugin files:**
   ```bash
   # Upload entire plugin directory or specific files:
   - ai-virtual-fitting/ai-virtual-fitting.php
   - ai-virtual-fitting/admin/class-admin-settings.php
   - ai-virtual-fitting/includes/class-image-processor.php
   ```

2. **No database changes needed** - Endpoint is now correctly hardcoded

3. **Test virtual fitting:**
   - Upload a photo
   - Select a dress
   - Verify image generation succeeds

### From v1.0.9.1 or earlier

Follow standard upgrade procedure - this version maintains compatibility with all v1.0.9.x releases.

## Compatibility

- **WordPress:** 5.0+
- **PHP:** 7.4+
- **WooCommerce:** 5.0+
- **Google AI Studio:** Gemini 3 Pro Image Preview model

## Known Issues

None. This release resolves the critical API endpoint issue.

## Testing Performed

- ✅ Local testing with correct endpoint - SUCCESS
- ✅ Production log analysis - Confirmed wrong endpoint in use
- ✅ File comparison with v1.0.9.1 - Format identical
- ✅ Production file upload - Verified correct endpoint deployed

## Migration Notes

### Database Settings

If you have a custom endpoint saved in the database:
1. Go to: Virtual Fitting → Settings
2. Find: "Gemini Image API Endpoint" field
3. Clear the field (make it empty)
4. Save changes
5. The correct default will be used automatically

### API Key

If still experiencing issues after upgrade:
1. Re-enter your Google AI API key in plugin settings
2. Click "Test Connection" to verify
3. Ensure key has access to Gemini 3 Pro Image Preview model

## Support

For issues or questions:
- Check production error logs: `wp-content/debug.log`
- Verify endpoint in use via logs
- Ensure API key is valid and has model access

## Credits

- **Issue Identified:** Production error logs analysis
- **Root Cause:** Wrong endpoint in v1.0.9.3
- **Solution:** Reverted to v1.0.9.1 endpoint configuration
- **Verification:** File comparison and log analysis

---

**Previous Version:** v1.0.9.3  
**Next Version:** TBD
