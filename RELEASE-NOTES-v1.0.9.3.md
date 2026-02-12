# Release Notes - Version 1.0.9.3

**Release Date:** January 31, 2026  
**Type:** Bug Fix & Security Update

## Overview

Version 1.0.9.3 addresses critical API key handling issues and updates Gemini AI model endpoints to use the latest stable versions. This release ensures proper API key encryption, prevents corruption during settings updates, and improves compatibility with Google AI Studio's current model lineup.

---

## 🔒 Security & Bug Fixes

### API Key Management
- **Fixed:** API key corruption when updating user credits or other settings
  - Root cause: UTF-8 bullet character (•) in masked field was 3 bytes, not detected by byte-length check
  - Solution: Implemented `mb_strlen()` for proper UTF-8 character counting
  - Added validation to ensure new keys match Google AI format (`AIza...` 39 chars)
  
- **Improved:** API key sanitization with enhanced detection
  - Detects masked placeholders using character count instead of byte count
  - Validates API key format before encryption
  - Better error messages for invalid keys
  - Comprehensive logging for debugging

- **Added:** Automatic corruption detection and recovery
  - Script to detect and clear corrupted encrypted keys
  - Clear instructions for re-entering API keys after corruption

### Gemini AI Model Updates
- **Updated:** Text API endpoint from `gemini-2.0-flash-exp` to `gemini-2.5-flash` (stable)
- **Updated:** Image API endpoint from `gemini-3-pro-image-preview` to `gemini-2.5-flash-image` (stable)
- **Reason:** Previous experimental models were deprecated or unavailable
- **Benefit:** Improved reliability and access to latest stable AI models

---

## 📋 Technical Changes

### Modified Files

**Core Plugin:**
- `ai-virtual-fitting.php` - Version bump to 1.0.9.3

**Admin:**
- `admin/class-admin-settings.php`
  - Enhanced `sanitize_api_key()` with UTF-8 character counting
  - Added API key format validation
  - Updated default model endpoints
  - Improved error handling and logging

**Includes:**
- `includes/class-image-processor.php`
  - Updated `DEFAULT_GEMINI_TEXT_API_ENDPOINT` to `gemini-2.5-flash`
  - Updated `DEFAULT_GEMINI_IMAGE_API_ENDPOINT` to `gemini-2.5-flash-image`

- `includes/class-plugin-config.php`
  - Updated default API endpoint constants

---

## 🔧 Upgrade Instructions

### For Users with Corrupted API Keys

If you experience "API key not valid" errors after updating user credits:

1. The corrupted key has been automatically cleared
2. Go to **WordPress Admin → Virtual Fitting → Settings**
3. Enter your Google AI Studio API key (starts with `AIza...`)
4. Click **Save Changes**
5. Click **Test Connection** to verify

### For All Users

1. **Backup your site** before updating
2. Update the plugin through WordPress admin or FTP
3. Go to **Virtual Fitting → Settings**
4. Click **Test Connection** to verify API connectivity
5. If test fails, re-enter your API key and save

---

## 🐛 Known Issues

None reported in this release.

---

## 📊 Testing Performed

- ✅ API key encryption/decryption with various key formats
- ✅ Settings save with masked API key (preserves existing key)
- ✅ User credit updates (API key remains intact)
- ✅ API connection test with new model endpoints
- ✅ Model availability verification via Google AI API
- ✅ UTF-8 character handling in masked fields

---

## 🔄 Migration Notes

### From v1.0.9.2 to v1.0.9.3

**No database changes required.**

**Action Required:**
- If you encounter API key issues, re-enter your API key in settings
- Test API connection after update

**Automatic Changes:**
- Plugin will use new stable Gemini models automatically
- No configuration changes needed for model updates

---

## 📝 Changelog Summary

**Added:**
- API key format validation before encryption
- UTF-8 character counting for masked field detection
- Corruption detection and recovery tools

**Fixed:**
- API key corruption when saving settings with masked value
- Model endpoint compatibility with current Google AI Studio API
- UTF-8 bullet character handling in form fields

**Changed:**
- Default text model: `gemini-2.0-flash-exp` → `gemini-2.5-flash`
- Default image model: `gemini-3-pro-image-preview` → `gemini-2.5-flash-image`

**Improved:**
- API key sanitization logic
- Error messages and logging
- Settings save reliability

---

## 🔗 Related Documentation

- [API Key Setup Guide](ai-virtual-fitting/docs/CONFIGURATION.md)
- [Troubleshooting Guide](ai-virtual-fitting/docs/TROUBLESHOOTING.md)
- [Admin Guide](ai-virtual-fitting/docs/ADMIN-GUIDE.md)

---

## 👥 Support

For issues or questions:
- Check the [Troubleshooting Guide](ai-virtual-fitting/docs/TROUBLESHOOTING.md)
- Review [Known Issues](#known-issues)
- Contact support with error logs from WordPress debug.log

---

## 📅 Next Release

Version 1.0.9.4 (planned):
- Additional mobile UX improvements
- Enhanced error handling for AI processing
- Performance optimizations

---

**Full Changelog:** [View all releases](../CHANGELOG.md)
