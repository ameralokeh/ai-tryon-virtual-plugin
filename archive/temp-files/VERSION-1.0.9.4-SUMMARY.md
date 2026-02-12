# Version 1.0.9.4 - Critical Endpoint Fix

**Release Date:** January 31, 2026  
**Package:** `ai-virtual-fitting-v1.0.9.4.zip` (273 KB)  
**Type:** Critical Bug Fix

## What Was Fixed

### The Problem
Production was failing with error: **"Request contains an invalid argument"**

**Root Cause:** Wrong Gemini API endpoint hardcoded in TWO files:
- ❌ `gemini-2.5-flash-image` (doesn't exist/work)
- ✅ Should be: `gemini-3-pro-image-preview` (correct model)

### Files Fixed
1. `admin/class-admin-settings.php` - Line 968 (UI display)
2. `includes/class-image-processor.php` - Line 80 (actual API calls)

## Changes Made

```php
// BEFORE (v1.0.9.3 - BROKEN)
const DEFAULT_GEMINI_IMAGE_API_ENDPOINT = 
  'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent';

// AFTER (v1.0.9.4 - FIXED)
const DEFAULT_GEMINI_IMAGE_API_ENDPOINT = 
  'https://generativelanguage.googleapis.com/v1beta/models/gemini-3-pro-image-preview:generateContent';
```

## Why It Failed

1. v1.0.9.3 introduced wrong endpoint name
2. Model `gemini-2.5-flash-image` doesn't exist in Google AI
3. Endpoint was in 2 separate files
4. Even after re-uploading plugin, wrong endpoint persisted
5. Production logs confirmed wrong endpoint in use

## Verification

✅ Compared with v1.0.9.1 (working version) - endpoint matches  
✅ Downloaded production files - confirmed wrong endpoint  
✅ Checked production logs - saw wrong endpoint in API requests  
✅ Uploaded corrected files - verified deployment  
✅ API request format identical to working version

## Installation

### Quick Fix (Production)
Already deployed to production via FTP:
- ✅ `admin/class-admin-settings.php` uploaded
- ✅ `includes/class-image-processor.php` uploaded

### Full Package Deployment
```bash
# Upload the ZIP file
ai-virtual-fitting-v1.0.9.4.zip

# Or upload specific files:
- ai-virtual-fitting/ai-virtual-fitting.php (version bump)
- ai-virtual-fitting/admin/class-admin-settings.php (endpoint fix)
- ai-virtual-fitting/includes/class-image-processor.php (endpoint fix)
```

## Testing

After deployment:
1. Go to: https://bridesandtailor.com/virtual-fitting
2. Upload a photo
3. Select a dress
4. Click "Generate Virtual Fitting"
5. Should succeed (no "invalid argument" error)

## Rollback (If Needed)

If issues occur, revert to v1.0.9.1:
```bash
# v1.0.9.1 was the last known working version
# It has the correct endpoint: gemini-3-pro-image-preview
```

## Documentation

- **Release Notes:** `RELEASE-NOTES-v1.0.9.4.md`
- **This Summary:** `VERSION-1.0.9.4-SUMMARY.md`
- **Package:** `ai-virtual-fitting-v1.0.9.4.zip`

## Next Steps

1. ✅ Files already uploaded to production
2. ⏳ Test virtual fitting on production
3. ⏳ Monitor error logs for any issues
4. ⏳ Confirm image generation succeeds

## Support

If still experiencing issues:
1. Check debug logs: `wp-content/debug.log`
2. Verify endpoint in logs shows: `gemini-3-pro-image-preview`
3. Re-enter API key in plugin settings if needed
4. Click "Test Connection" to verify API access

---

**Critical:** This is a required update for anyone experiencing the "invalid argument" error.
