# AI Virtual Fitting Plugin - Installation Guide

**Version:** 1.0.0  
**Package:** ai-virtual-fitting-v1.0.0.zip  
**Date:** January 14, 2026

---

## 📦 Package Contents

The plugin package includes:
- ✅ All core PHP classes with security fixes
- ✅ Admin interface with settings management
- ✅ Public interface with modern UI
- ✅ React components (bundled locally)
- ✅ CSS and JavaScript assets
- ✅ Translation template (.pot file)
- ✅ Documentation (README.md, DEVELOPER.md)

**Excluded from package:**
- ❌ Test files (tests/ directory)
- ❌ Development files (.git, phpunit.xml)
- ❌ Node modules

---

## 🔧 System Requirements

### Minimum Requirements
- **WordPress:** 5.0 or higher
- **PHP:** 7.4 or higher
- **MySQL:** 5.6 or higher (8.0 recommended)
- **WooCommerce:** 5.0 or higher
- **Memory Limit:** 128MB minimum (256MB recommended)
- **Max Upload Size:** 10MB minimum

### Required PHP Extensions
- `openssl` - For API key encryption
- `gd` or `imagick` - For image processing
- `curl` - For API requests
- `json` - For data handling
- `mbstring` - For string operations

### WordPress Capabilities
- User must have `manage_options` capability for admin settings
- WooCommerce must be installed and activated

---

## 📥 Installation Steps

### Method 1: WordPress Admin Upload (Recommended)

1. **Download the Plugin Package**
   - File: `ai-virtual-fitting-v1.0.0.zip`
   - Size: ~219KB

2. **Login to WordPress Admin**
   - Navigate to your WordPress admin panel
   - URL: `https://your-site.com/wp-admin`

3. **Upload Plugin**
   - Go to: **Plugins → Add New**
   - Click: **Upload Plugin** button
   - Choose file: `ai-virtual-fitting-v1.0.0.zip`
   - Click: **Install Now**

4. **Activate Plugin**
   - After installation completes, click **Activate Plugin**
   - You'll see a success message

5. **Verify Installation**
   - Go to: **Plugins → Installed Plugins**
   - Look for: **AI Virtual Fitting**
   - Status should be: **Active**

### Method 2: FTP/SFTP Upload

1. **Extract ZIP File**
   ```bash
   unzip ai-virtual-fitting-v1.0.0.zip
   ```

2. **Upload via FTP**
   - Connect to your server via FTP/SFTP
   - Navigate to: `/wp-content/plugins/`
   - Upload the entire `ai-virtual-fitting` folder

3. **Set Permissions**
   ```bash
   chmod 755 /wp-content/plugins/ai-virtual-fitting
   chmod 644 /wp-content/plugins/ai-virtual-fitting/*.php
   ```

4. **Activate in WordPress**
   - Go to: **Plugins → Installed Plugins**
   - Find: **AI Virtual Fitting**
   - Click: **Activate**

### Method 3: WP-CLI (Advanced)

```bash
# Upload and extract plugin
wp plugin install /path/to/ai-virtual-fitting-v1.0.0.zip

# Activate plugin
wp plugin activate ai-virtual-fitting

# Verify activation
wp plugin list --status=active
```

---

## ⚙️ Initial Configuration

### Step 1: Configure Google AI Studio API

1. **Get API Key**
   - Visit: https://aistudio.google.com/app/apikey
   - Create or copy your API key
   - Format: `AIza...` (starts with AIza)

2. **Enter API Key**
   - Go to: **Settings → AI Virtual Fitting**
   - Find: **Google AI Studio API Key** field
   - Paste your API key
   - Click: **Test Connection** to verify

3. **Configure AI Prompt (Optional)**
   - Customize the AI prompt template if needed
   - Default prompt works well for wedding dresses
   - Maximum 2000 characters

### Step 2: Configure Credit System

1. **Set Initial Free Credits**
   - Default: 2 credits per new user
   - Recommended: 2-5 credits for trial

2. **Configure Credit Package**
   - Credits per package: 20 (default)
   - Package price: $10.00 (default)
   - Adjust based on your pricing model

3. **Save Settings**
   - Click: **Save Changes**
   - Plugin will auto-create WooCommerce product

### Step 3: System Settings

1. **Image Upload Settings**
   - Max image size: 10MB (default)
   - Supported formats: JPEG, PNG, WebP
   - Min dimensions: 512x512px
   - Max dimensions: 2048x2048px

2. **API Settings**
   - Retry attempts: 3 (default)
   - API timeout: 60 seconds (default)
   - Enable logging: Yes (recommended for testing)

3. **Advanced Settings**
   - Temp file cleanup: 24 hours (default)
   - Enable analytics: Yes (recommended)
   - Require login: Yes (recommended)

---

## 🎨 Create Virtual Fitting Page

### Option 1: Use Shortcode

1. **Create New Page**
   - Go to: **Pages → Add New**
   - Title: "Virtual Fitting" or "Try On Dresses"

2. **Add Shortcode**
   ```
   [ai_virtual_fitting]
   ```

3. **Publish Page**
   - Click: **Publish**
   - Note the page URL

### Option 2: Use Page Template

1. **Create Page with Slug**
   - Create page with slug: `virtual-fitting`
   - Plugin will automatically detect and render

2. **Customize Appearance**
   - Use WordPress Customizer
   - Adjust colors, fonts, spacing
   - Preview before publishing

---

## 🛒 WooCommerce Integration

### Automatic Setup

The plugin automatically:
- ✅ Creates "Virtual Fitting Credits" product
- ✅ Sets product as virtual (no shipping)
- ✅ Configures pricing based on settings
- ✅ Adds credits to user account on purchase
- ✅ Tracks credit usage and purchases

### Manual Verification

1. **Check Product**
   - Go to: **Products → All Products**
   - Find: "Virtual Fitting Credits"
   - Verify: Price, description, settings

2. **Test Purchase Flow**
   - Add credits to cart
   - Complete checkout
   - Verify credits added to account

---

## 🔒 Security Configuration

### API Key Encryption

- API keys are automatically encrypted using AES-256-CBC
- Encryption uses WordPress `AUTH_KEY` and `SECURE_AUTH_KEY`
- No additional configuration needed

### Rate Limiting

- Automatically enabled: 20 requests per 5 minutes
- Protects against abuse and DoS attacks
- Per user/IP address tracking

### File Upload Security

- Magic byte validation enabled
- MIME type verification
- File size limits enforced
- SSRF protection active

---

## 🧪 Testing the Installation

### Test 1: Admin Settings

1. Go to: **Settings → AI Virtual Fitting**
2. Verify all tabs load correctly
3. Test API connection
4. Check system status indicators

### Test 2: Virtual Fitting Page

1. Visit your virtual fitting page
2. Verify page loads without errors
3. Check product slider displays
4. Test image upload (without processing)

### Test 3: Credit System

1. Create test user account
2. Check initial credits granted
3. Test credit purchase flow
4. Verify credits added after purchase

### Test 4: Full Workflow

1. Login as test user
2. Upload customer photo
3. Select wedding dress product
4. Process virtual fitting
5. Verify result image displays
6. Test download functionality

---

## 🐛 Troubleshooting

### Plugin Won't Activate

**Issue:** Activation fails with error message

**Solutions:**
- Check PHP version (7.4+ required)
- Verify WooCommerce is installed
- Check file permissions (755 for folders, 644 for files)
- Enable WordPress debug mode to see detailed errors

### API Connection Fails

**Issue:** "API connection failed" error

**Solutions:**
- Verify API key is correct (starts with AIza)
- Check server can make outbound HTTPS requests
- Verify `curl` PHP extension is enabled
- Check firewall isn't blocking Google AI Studio
- Test API key at: https://aistudio.google.com

### Images Won't Upload

**Issue:** Upload fails or shows error

**Solutions:**
- Check PHP `upload_max_filesize` setting (10MB minimum)
- Verify `post_max_size` is larger than `upload_max_filesize`
- Check WordPress upload directory permissions
- Ensure `gd` or `imagick` extension is enabled
- Review error logs for specific issues

### Credits Not Added After Purchase

**Issue:** User purchases credits but balance doesn't update

**Solutions:**
- Check WooCommerce order status (must be "Completed")
- Verify credit product ID in plugin settings
- Check WordPress cron is running
- Review plugin logs for errors
- Manually trigger order processing

### Virtual Fitting Page Shows 404

**Issue:** Page not found error

**Solutions:**
- Flush WordPress permalinks: **Settings → Permalinks → Save Changes**
- Verify page exists and is published
- Check shortcode is correct: `[ai_virtual_fitting]`
- Ensure plugin is activated

---

## 📊 Monitoring & Maintenance

### Check System Status

1. Go to: **Settings → AI Virtual Fitting**
2. View: **System Status** section
3. Verify all indicators are green:
   - ✅ WordPress Version
   - ✅ WooCommerce Active
   - ✅ API Key Configured
   - ✅ Database Tables Created
   - ✅ Credit Product Exists

### Monitor Analytics

1. Go to: **Settings → AI Virtual Fitting**
2. Click: **Analytics** tab (if available)
3. Review:
   - Total users
   - Credits purchased
   - Credits used
   - Recent activity

### Review Logs

1. Enable logging in plugin settings
2. Check WordPress debug log: `/wp-content/debug.log`
3. Look for entries starting with: `AI Virtual Fitting -`
4. Monitor for errors or warnings

### Database Maintenance

The plugin creates these tables:
- `wp_virtual_fitting_credits` - User credit tracking
- `wp_virtual_fitting_analytics` - Usage analytics
- `wp_virtual_fitting_sessions` - Processing sessions

**Backup regularly** before major updates!

---

## 🔄 Updating the Plugin

### Before Updating

1. **Backup Everything**
   - Database backup
   - Plugin files backup
   - WordPress backup

2. **Test on Staging**
   - Install update on staging site first
   - Test all functionality
   - Verify no conflicts

3. **Check Compatibility**
   - WordPress version
   - WooCommerce version
   - PHP version
   - Other plugins

### Update Process

1. **Deactivate Plugin**
   - Go to: **Plugins → Installed Plugins**
   - Deactivate: **AI Virtual Fitting**

2. **Delete Old Version**
   - Delete plugin (data is preserved)

3. **Install New Version**
   - Upload new ZIP file
   - Activate plugin

4. **Verify Settings**
   - Check all settings preserved
   - Test API connection
   - Verify credit system works

---

## 🆘 Support & Resources

### Documentation

- **README.md** - User guide and features
- **DEVELOPER.md** - Technical documentation
- **SECURITY-FIXES-SUMMARY.md** - Security improvements
- **AUDIT-SUMMARY.md** - Code audit results

### Common Issues

- Check WordPress debug log
- Review plugin settings
- Test with default WordPress theme
- Disable other plugins temporarily
- Check server error logs

### Getting Help

1. Review documentation files
2. Check troubleshooting section
3. Enable debug logging
4. Collect error messages
5. Note WordPress/PHP versions

---

## ✅ Post-Installation Checklist

- [ ] Plugin activated successfully
- [ ] Google AI Studio API key configured
- [ ] API connection test passed
- [ ] Credit system settings configured
- [ ] WooCommerce credit product created
- [ ] Virtual fitting page created
- [ ] Test user account created
- [ ] Initial credits granted to test user
- [ ] Image upload tested
- [ ] Virtual fitting process tested
- [ ] Credit purchase flow tested
- [ ] Download functionality tested
- [ ] System status all green
- [ ] Logging enabled for monitoring
- [ ] Backup schedule configured

---

## 🚀 Going Live

### Pre-Launch Checklist

1. **Security**
   - [ ] SSL certificate installed (HTTPS)
   - [ ] Strong WordPress admin password
   - [ ] API key encrypted (automatic)
   - [ ] Rate limiting active (automatic)
   - [ ] File upload security enabled (automatic)

2. **Performance**
   - [ ] Caching plugin installed
   - [ ] Image optimization configured
   - [ ] CDN setup (optional)
   - [ ] Database optimized

3. **Testing**
   - [ ] Full workflow tested
   - [ ] Multiple browsers tested
   - [ ] Mobile responsive verified
   - [ ] Payment processing tested
   - [ ] Email notifications working

4. **Monitoring**
   - [ ] Analytics enabled
   - [ ] Error logging active
   - [ ] Uptime monitoring setup
   - [ ] Backup automation configured

---

## 📝 Version Information

**Current Version:** 1.0.0  
**Release Date:** January 14, 2026  
**Package Size:** 219KB  
**WordPress Tested:** 6.4+  
**WooCommerce Tested:** 8.0+  
**PHP Required:** 7.4+

### What's Included in v1.0.0

- ✅ AI-powered virtual fitting with Google AI Studio
- ✅ Credit-based usage system
- ✅ WooCommerce integration
- ✅ Modern responsive UI
- ✅ Admin dashboard with analytics
- ✅ Security enhancements (encryption, rate limiting, SSRF protection)
- ✅ File upload validation
- ✅ Configurable API endpoints
- ✅ Portable across environments
- ✅ Comprehensive error handling

---

**Installation Complete!** 🎉

Your AI Virtual Fitting plugin is now ready to provide amazing virtual try-on experiences for your customers.

For technical details, see **DEVELOPER.md**  
For security information, see **SECURITY-FIXES-SUMMARY.md**

