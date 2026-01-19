# Installation Guide: AI Virtual Fitting Plugin

**Version**: 1.0.0  
**Last Updated**: January 2026  
**Audience**: Administrators, Technical Staff

---

## Table of Contents

- [Pre-Installation](#pre-installation)
- [Installation Methods](#installation-methods)
- [Post-Installation](#post-installation)
- [Troubleshooting Installation](#troubleshooting-installation)
- [Uninstallation](#uninstallation)

---

## Pre-Installation

Before installing the AI Virtual Fitting Plugin, it's essential to verify that your system meets all requirements and to prepare your WordPress environment. Following these pre-installation steps will help ensure a smooth installation process and prevent common issues.

### System Requirements Check

#### WordPress and PHP Requirements

**WordPress Version**
- Minimum: WordPress 5.0
- Recommended: WordPress 6.0 or higher
- Tested up to: WordPress 6.4

To check your WordPress version:
1. Log in to WordPress admin dashboard
2. Navigate to **Dashboard → Updates**
3. Your WordPress version is displayed at the top

**PHP Version**
- Minimum: PHP 7.4
- Recommended: PHP 8.0 or higher

To check your PHP version:
1. Log in to WordPress admin dashboard
2. Navigate to **Tools → Site Health → Info → Server**
3. Look for "PHP version" in the server information

**Required PHP Extensions**

The following PHP extensions must be installed and enabled:

- **curl**: For API communication with Google AI Studio
- **gd** or **imagick**: For image processing and manipulation
- **json**: For data handling and API responses
- **mbstring**: For multi-byte string handling
- **openssl**: For encryption and secure communication
- **zip**: For file compression and decompression

To verify PHP extensions:
1. Navigate to **Tools → Site Health → Info → Server**
2. Expand the "PHP Extensions" section
3. Verify all required extensions are listed

If any extensions are missing, contact your hosting provider to have them installed.

#### Database Requirements

- **MySQL**: 5.6 or higher (8.0+ recommended)
- **MariaDB**: 10.0 or higher (alternative to MySQL)

To check your database version:
1. Navigate to **Tools → Site Health → Info → Database**
2. Look for "Database version"

#### Server Configuration

**Memory Limit**
- Minimum: 128MB
- Recommended: 256MB or higher

**Upload File Size**
- Minimum: 10MB
- Recommended: 20MB or higher

**Execution Time**
- Minimum: 60 seconds
- Recommended: 120 seconds or higher

**Disk Space**
- Minimum: 100MB free space for plugin files and temporary storage
- Recommended: 500MB or more for optimal performance

To check server configuration:
1. Navigate to **Tools → Site Health → Info → Server**
2. Review the server settings
3. If values are below minimum requirements, contact your hosting provider


### Dependency Verification

#### WooCommerce Installation

The AI Virtual Fitting Plugin requires WooCommerce to be installed and activated for credit purchase functionality.

**WooCommerce Requirements**
- Minimum version: WooCommerce 5.0
- Recommended version: WooCommerce 7.0 or higher

**To verify WooCommerce installation:**

1. Log in to WordPress admin dashboard
2. Navigate to **Plugins → Installed Plugins**
3. Look for "WooCommerce" in the plugin list
4. Verify it shows "Active" status

**If WooCommerce is not installed:**

1. Navigate to **Plugins → Add New**
2. Search for "WooCommerce"
3. Click **Install Now** on the official WooCommerce plugin
4. Click **Activate** after installation completes
5. Follow the WooCommerce setup wizard to configure your store

#### Google AI Studio API Key

You must have a valid Google AI Studio API key before installing the plugin.

**To obtain an API key:**

1. Visit [Google AI Studio](https://aistudio.google.com/)
2. Sign in with your Google account
3. Navigate to **API Keys** section
4. Click **Create API Key**
5. Copy and save your API key securely
6. Ensure you have access to the Gemini 2.5 Flash Image model

**Important**: Keep your API key secure and never share it publicly. You'll need this key during the post-installation configuration.

#### WordPress Configuration

**Permalinks**

Pretty permalinks must be enabled for the plugin to function correctly.

To verify and enable permalinks:
1. Navigate to **Settings → Permalinks**
2. Select any option except "Plain"
3. Recommended: "Post name" structure
4. Click **Save Changes**


**File Uploads**

File uploads must be enabled in WordPress.

To verify:
1. Navigate to **Media → Add New**
2. If you can see the upload interface, file uploads are enabled
3. If not, contact your hosting provider

**REST API Access**

The WordPress REST API must be accessible and not blocked by security plugins.

To verify:
1. Visit: `https://yoursite.com/wp-json/` (replace with your domain)
2. You should see JSON data, not an error message
3. If blocked, check your security plugin settings

### Backup Procedures

**Critical**: Always create a complete backup before installing new plugins. This allows you to restore your site if any issues occur during installation.

#### What to Backup

1. **WordPress Files**
   - All WordPress core files
   - wp-content directory (themes, plugins, uploads)
   - wp-config.php file

2. **Database**
   - Complete MySQL/MariaDB database
   - All tables including wp_* tables

3. **Configuration Files**
   - .htaccess file
   - wp-config.php
   - Any custom configuration files

#### Backup Methods

**Method 1: Using a Backup Plugin (Recommended)**

Popular backup plugins:
- UpdraftPlus
- BackupBuddy
- Duplicator
- All-in-One WP Migration

Steps:
1. Install and activate a backup plugin
2. Configure backup settings
3. Run a complete backup (files + database)
4. Download backup files to your local computer
5. Verify backup files are complete and accessible


**Method 2: Manual Backup via cPanel**

If your host provides cPanel:
1. Log in to cPanel
2. Navigate to **Files → Backup**
3. Download "Home Directory" backup (includes all files)
4. Navigate to **Databases → phpMyAdmin**
5. Select your WordPress database
6. Click **Export** tab
7. Choose "Quick" export method
8. Click **Go** to download database backup

**Method 3: Manual Backup via FTP/SFTP**

For files:
1. Connect to your server via FTP/SFTP client (FileZilla, Cyberduck)
2. Download the entire WordPress directory to your local computer
3. Ensure all files are downloaded successfully

For database:
1. Access phpMyAdmin through your hosting control panel
2. Select your WordPress database
3. Click **Export** tab
4. Select "Quick" export method and SQL format
5. Click **Go** to download

**Method 4: Using WP-CLI (Advanced)**

If you have command-line access:

```bash
# Backup database
wp db export backup-$(date +%Y%m%d).sql

# Create files backup
tar -czf wordpress-backup-$(date +%Y%m%d).tar.gz /path/to/wordpress/

# Or use WP-CLI backup package
wp backup create
```

#### Backup Verification

After creating backups:
1. Verify backup files exist and are not corrupted
2. Check file sizes are reasonable (not 0 bytes)
3. For database backups, verify the .sql file opens in a text editor
4. Store backups in a safe location separate from your server
5. Consider keeping multiple backup versions

#### Backup Best Practices

- Create backups during low-traffic periods
- Test your backup restoration process periodically
- Keep at least 3 recent backups
- Store backups in multiple locations (local, cloud storage)
- Document your backup procedure for future reference


### Pre-Installation Checklist

Before proceeding with installation, verify you have completed all pre-installation steps:

- [ ] WordPress version 5.0 or higher installed
- [ ] PHP version 7.4 or higher with all required extensions
- [ ] MySQL 5.6+ or MariaDB 10.0+ database
- [ ] WooCommerce 5.0 or higher installed and activated
- [ ] Google AI Studio API key obtained
- [ ] Pretty permalinks enabled
- [ ] File uploads enabled
- [ ] REST API accessible
- [ ] Complete backup created and verified
- [ ] Server meets minimum memory, upload size, and execution time requirements
- [ ] At least 100MB free disk space available

If all items are checked, you're ready to proceed with installation!

---

## Installation Methods

The AI Virtual Fitting Plugin can be installed using three different methods. Choose the method that best suits your technical expertise and server access level.

### Method 1: WordPress Admin Upload (Recommended)

This is the easiest and most common installation method, suitable for most users.

#### Step 1: Download the Plugin

1. Obtain the plugin ZIP file (`ai-virtual-fitting.zip`)
2. Save it to a location on your computer where you can easily find it
3. Do not extract/unzip the file—WordPress needs the ZIP format

#### Step 2: Access WordPress Admin

1. Log in to your WordPress admin dashboard
2. Navigate to **Plugins → Add New**
3. Click the **Upload Plugin** button at the top of the page


#### Step 3: Upload the Plugin File

1. Click **Choose File** button
2. Browse to the location where you saved `ai-virtual-fitting.zip`
3. Select the ZIP file
4. Click **Open** to confirm selection
5. Click **Install Now** button

WordPress will now upload and extract the plugin files. This may take 30-60 seconds depending on your connection speed.

#### Step 4: Activate the Plugin

1. After successful upload, you'll see a success message
2. Click the **Activate Plugin** link
3. You'll be redirected to the Plugins page
4. Verify "AI Virtual Fitting" shows "Active" status

#### Step 5: Verify Installation

1. Look for a new menu item "Virtual Fitting" in the WordPress admin sidebar
2. Navigate to **Virtual Fitting → Settings**
3. If you can access the settings page, installation was successful

**Screenshot Reference**: See `images/installation/wordpress-admin-upload.png` for visual guide.

### Method 2: FTP/SFTP Upload

This method is useful if you have FTP access to your server or if the WordPress admin upload fails due to file size restrictions.

#### Prerequisites

- FTP/SFTP client installed (FileZilla, Cyberduck, WinSCP, etc.)
- FTP/SFTP credentials from your hosting provider
- Plugin ZIP file downloaded and extracted on your local computer

#### Step 1: Extract the Plugin

1. Locate the `ai-virtual-fitting.zip` file on your computer
2. Right-click and select "Extract" or "Unzip"
3. You should now have a folder named `ai-virtual-fitting`
4. Verify the folder contains plugin files (not another folder with the same name)


#### Step 2: Connect to Your Server

1. Open your FTP/SFTP client
2. Enter your server connection details:
   - **Host**: Your server address (e.g., ftp.yoursite.com)
   - **Username**: Your FTP username
   - **Password**: Your FTP password
   - **Port**: Usually 21 for FTP, 22 for SFTP
3. Click **Connect** or **Quick Connect**

#### Step 3: Navigate to Plugins Directory

1. In the remote server panel, navigate to your WordPress installation
2. Open the `wp-content` folder
3. Open the `plugins` folder
4. You should see other plugin folders here

Full path is typically: `/public_html/wp-content/plugins/` or `/www/wp-content/plugins/`

#### Step 4: Upload the Plugin Folder

1. In your local computer panel, navigate to the extracted `ai-virtual-fitting` folder
2. Drag the entire `ai-virtual-fitting` folder to the remote `plugins` directory
3. Wait for all files to upload (may take 2-5 minutes)
4. Verify all files uploaded successfully (check file count and sizes)

#### Step 5: Set Correct Permissions

After upload, set appropriate file permissions:

**For folders**: 755 (rwxr-xr-x)
**For files**: 644 (rw-r--r--)

In most FTP clients:
1. Right-click the `ai-virtual-fitting` folder
2. Select "File Permissions" or "CHMOD"
3. Set folder permissions to 755
4. Check "Recurse into subdirectories"
5. Apply to directories only first, then repeat for files with 644

#### Step 6: Activate the Plugin

1. Log in to WordPress admin dashboard
2. Navigate to **Plugins → Installed Plugins**
3. Find "AI Virtual Fitting" in the list
4. Click **Activate** link
5. Verify the plugin shows "Active" status

**Screenshot Reference**: See `images/installation/ftp-upload.png` for visual guide.


### Method 3: WP-CLI Installation (Advanced)

This method is for advanced users with command-line access to their server. WP-CLI provides the fastest and most efficient installation method.

#### Prerequisites

- SSH access to your server
- WP-CLI installed on your server
- Plugin ZIP file accessible on the server or via URL

#### Step 1: Connect to Your Server

```bash
# Connect via SSH
ssh username@yourserver.com

# Navigate to WordPress installation directory
cd /path/to/wordpress/
```

#### Step 2: Verify WP-CLI Installation

```bash
# Check WP-CLI version
wp --version

# Verify WordPress installation
wp core version
```

If WP-CLI is not installed, visit [wp-cli.org](https://wp-cli.org/) for installation instructions.

#### Step 3: Install the Plugin

**Option A: Install from Local ZIP File**

```bash
# If ZIP file is on the server
wp plugin install /path/to/ai-virtual-fitting.zip

# Activate immediately
wp plugin install /path/to/ai-virtual-fitting.zip --activate
```

**Option B: Install from URL**

```bash
# If ZIP file is hosted online
wp plugin install https://example.com/ai-virtual-fitting.zip

# Activate immediately
wp plugin install https://example.com/ai-virtual-fitting.zip --activate
```

**Option C: Install from WordPress.org (if available)**

```bash
# Install from WordPress plugin repository
wp plugin install ai-virtual-fitting

# Activate immediately
wp plugin install ai-virtual-fitting --activate
```


#### Step 4: Verify Installation

```bash
# List all plugins and check status
wp plugin list

# Check specific plugin status
wp plugin status ai-virtual-fitting

# Verify plugin is active
wp plugin list --status=active | grep ai-virtual-fitting
```

Expected output should show:
- Name: ai-virtual-fitting
- Status: active
- Version: 1.0.0 (or current version)

#### Step 5: Check for Errors

```bash
# Check for PHP errors
wp plugin status ai-virtual-fitting

# Verify database tables were created
wp db query "SHOW TABLES LIKE 'wp_virtual_fitting%';"

# Check plugin files
ls -la wp-content/plugins/ai-virtual-fitting/
```

#### Additional WP-CLI Commands

**Deactivate plugin:**
```bash
wp plugin deactivate ai-virtual-fitting
```

**Reactivate plugin:**
```bash
wp plugin activate ai-virtual-fitting
```

**Update plugin (when updates available):**
```bash
wp plugin update ai-virtual-fitting
```

**Uninstall plugin:**
```bash
wp plugin uninstall ai-virtual-fitting --deactivate
```

**Get plugin information:**
```bash
wp plugin get ai-virtual-fitting
```

**Screenshot Reference**: See `images/installation/wp-cli-install.png` for visual guide.

### Installation Method Comparison

| Feature | WordPress Admin | FTP/SFTP | WP-CLI |
|---------|----------------|----------|---------|
| **Ease of Use** | ⭐⭐⭐⭐⭐ Easy | ⭐⭐⭐ Moderate | ⭐⭐ Advanced |
| **Speed** | ⭐⭐⭐ Moderate | ⭐⭐⭐ Moderate | ⭐⭐⭐⭐⭐ Fast |
| **Requirements** | WordPress admin access | FTP credentials | SSH access |
| **File Size Limits** | May have limits | No limits | No limits |
| **Best For** | Most users | Large files, restricted admin | Developers, automation |
| **Troubleshooting** | ⭐⭐⭐ Easy | ⭐⭐⭐⭐ Moderate | ⭐⭐⭐⭐⭐ Advanced |


---

## Post-Installation

After successfully installing and activating the plugin, you must complete the initial configuration to make the plugin functional. This section guides you through the essential setup steps.

### Initial Configuration

#### Step 1: Access Plugin Settings

1. Log in to WordPress admin dashboard
2. Navigate to **Virtual Fitting → Settings** in the admin sidebar
3. You'll see the plugin settings page with multiple tabs

The settings page includes:
- **General Settings**: Basic plugin configuration
- **API Settings**: Google AI Studio configuration
- **Credit Settings**: Credit system configuration
- **Image Settings**: Upload and processing settings
- **Advanced Settings**: Performance and security options

#### Step 2: Configure General Settings

1. Click the **General Settings** tab
2. Configure the following options:

**Enable Virtual Fitting**
- Check this box to enable the virtual fitting feature
- Leave unchecked if you want to configure everything before going live

**Enable Logging**
- Recommended: Enable for initial setup to track any issues
- Logs are stored in `wp-content/uploads/ai-virtual-fitting/logs/`

**Enable Analytics**
- Enable to track usage statistics and performance metrics
- Useful for monitoring plugin performance

3. Click **Save Changes** at the bottom of the page


### API Key Setup

This is the most critical configuration step. The plugin cannot function without a valid Google AI Studio API key.

#### Step 1: Navigate to API Settings

1. Click the **API Settings** tab in the plugin settings
2. You'll see the API configuration form

#### Step 2: Enter Your API Key

1. Locate the **Google AI Studio API Key** field
2. Paste your API key (obtained during pre-installation)
3. The key should look like: `AIzaSy...` (39 characters)

**Important Security Notes:**
- The API key is encrypted using AES-256-CBC before storage
- Never share your API key publicly
- Regularly rotate your API key for security
- Monitor your API usage in Google AI Studio dashboard

#### Step 3: Configure API Settings

**API Endpoint** (Advanced)
- Default: `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent`
- Only change if instructed by Google or for testing purposes

**Request Timeout**
- Default: 60 seconds
- Increase if you experience timeout errors
- Recommended range: 60-120 seconds

**Max Retries**
- Default: 3 attempts
- Number of retry attempts for failed API requests
- Recommended: 2-3 retries

**Retry Delay**
- Default: 2 seconds
- Delay between retry attempts
- Recommended: 1-3 seconds

#### Step 4: Test API Connection

1. Click the **Test API Connection** button
2. Wait for the test to complete (5-10 seconds)
3. You should see a success message: "API connection successful!"

**If the test fails:**
- Verify your API key is correct (no extra spaces)
- Check your API key has access to Gemini 2.5 Flash Image model
- Verify your server can make outbound HTTPS requests
- Check your API quota in Google AI Studio
- Review error logs for detailed error messages

4. Click **Save Changes** to save your API configuration


### Credit System Configuration

Configure how credits are allocated and managed for your users.

#### Step 1: Navigate to Credit Settings

1. Click the **Credit Settings** tab
2. Review the credit system configuration options

#### Step 2: Configure Initial Credits

**Free Credits for New Users**
- Default: 2 credits
- Recommended: 2-5 credits for trial purposes
- Set to 0 to disable free credits

**Credit Package Configuration**
- Default package: 20 credits for $10
- You can modify this in WooCommerce product settings later

#### Step 3: Configure Credit Products

The plugin automatically creates a WooCommerce product for credit purchases during activation. To verify or modify:

1. Navigate to **Products → All Products** in WordPress admin
2. Look for "Virtual Fitting Credits" product
3. Click to edit if you want to customize:
   - Product name
   - Product description
   - Price (default: $10 for 20 credits)
   - Product images
   - Product categories

**Important**: Do not delete the credit product. The plugin uses it for credit purchases.

#### Step 4: Save Credit Settings

1. Review all credit settings
2. Click **Save Changes**
3. Verify settings were saved successfully

### Image Processing Configuration

Configure image upload and processing settings.

#### Step 1: Navigate to Image Settings

1. Click the **Image Settings** tab
2. Review image processing options

#### Step 2: Configure Upload Settings

**Maximum File Size**
- Default: 10MB
- Recommended: 5-10MB
- Must not exceed server's upload_max_filesize

**Allowed File Formats**
- Default: JPEG, PNG, WebP
- All formats are recommended for best user experience
- Uncheck formats you want to disable


**Image Dimension Requirements**
- Minimum width: 400px
- Minimum height: 400px
- Maximum width: 4000px
- Maximum height: 4000px
- Recommended: 800x1200px or higher

**Image Quality**
- Default: 85% (JPEG compression)
- Range: 60-100%
- Higher quality = larger file sizes

#### Step 3: Configure Processing Settings

**Enable Image Optimization**
- Recommended: Enabled
- Automatically optimizes uploaded images
- Reduces file size without significant quality loss

**Enable Caching**
- Recommended: Enabled
- Caches processed results for faster repeat access
- Reduces API calls and costs

**Cache Duration**
- Default: 24 hours
- How long to cache results
- Recommended: 12-48 hours

#### Step 4: Save Image Settings

1. Review all image settings
2. Click **Save Changes**
3. Verify settings were saved successfully

### Testing Procedures

After completing the initial configuration, test the plugin to ensure everything works correctly.

#### Test 1: Create Test User

1. Navigate to **Users → Add New**
2. Create a test user account:
   - Username: `testuser`
   - Email: `test@yoursite.com`
   - Role: Customer
3. Click **Add New User**
4. Verify the user received 2 free credits (check in Virtual Fitting → Users)


#### Test 2: Create Virtual Fitting Page

1. Navigate to **Pages → Add New**
2. Create a new page:
   - Title: "Virtual Fitting"
   - Add the shortcode: `[ai_virtual_fitting]`
   - Or use the block editor to add the Virtual Fitting block
3. Click **Publish**
4. Note the page URL for testing

#### Test 3: Test Virtual Fitting Process

1. Log out of WordPress admin
2. Log in as the test user you created
3. Navigate to the Virtual Fitting page
4. Test the virtual fitting process:
   - Upload a test photo (use a clear, well-lit photo)
   - Select a wedding dress from the product slider
   - Click "Generate Virtual Fitting"
   - Wait for processing (30-60 seconds)
   - Verify the result displays correctly
   - Test the download functionality

**Expected Results:**
- Photo uploads successfully
- Processing completes without errors
- Result image displays correctly
- Download works properly
- Credit balance decreases by 1

#### Test 4: Test Credit Purchase

1. While logged in as test user
2. Click "Buy More Credits" button
3. Verify redirect to WooCommerce cart
4. Complete checkout process (use test payment gateway)
5. After order completion, verify:
   - Credits added to user account
   - Order shows "Completed" status
   - User receives confirmation email

#### Test 5: Admin Dashboard Test

1. Log back in as administrator
2. Navigate to **Virtual Fitting → Dashboard**
3. Verify you can see:
   - Usage statistics
   - Recent virtual fittings
   - Credit purchase history
   - System status


### Verification Checklist

Complete this checklist to ensure your installation is successful and the plugin is ready for production use.

#### Configuration Verification

- [ ] Plugin activated successfully
- [ ] Settings page accessible
- [ ] Google AI Studio API key configured
- [ ] API connection test passed
- [ ] Credit settings configured
- [ ] Image settings configured
- [ ] WooCommerce credit product exists
- [ ] Virtual Fitting page created with shortcode

#### Functionality Verification

- [ ] Test user created and received free credits
- [ ] Photo upload works correctly
- [ ] Virtual fitting processing completes successfully
- [ ] Result image displays properly
- [ ] Download functionality works
- [ ] Credit deduction works correctly
- [ ] Credit purchase process works
- [ ] Credits added after purchase
- [ ] Admin dashboard displays data

#### System Verification

- [ ] No PHP errors in error logs
- [ ] No JavaScript errors in browser console
- [ ] Database tables created successfully
- [ ] File permissions set correctly
- [ ] Temporary files directory writable
- [ ] Logs directory writable (if logging enabled)
- [ ] WooCommerce integration working
- [ ] Email notifications working

#### Security Verification

- [ ] API key encrypted in database
- [ ] File upload validation working
- [ ] Rate limiting configured
- [ ] HTTPS enabled (recommended for production)
- [ ] Security headers configured
- [ ] User authentication required for virtual fitting

#### Performance Verification

- [ ] Page load times acceptable
- [ ] Image upload speed acceptable
- [ ] Processing time within expected range (30-60 seconds)
- [ ] No memory errors
- [ ] No timeout errors
- [ ] Caching working (if enabled)


### Next Steps After Installation

Once installation and testing are complete, consider these next steps:

#### 1. Customize Settings

- Review and adjust credit pricing
- Customize email notifications
- Configure advanced performance settings
- Set up analytics tracking

#### 2. Add Wedding Dress Products

1. Navigate to **Products → Add New**
2. Create products for your wedding dresses
3. Add high-quality product images
4. Assign to appropriate categories
5. Publish products

The virtual fitting feature will automatically use these product images.

#### 3. Customize the Virtual Fitting Page

- Add custom content above/below the virtual fitting interface
- Customize page design to match your theme
- Add instructions or guidelines for users
- Include FAQ or help section

#### 4. Configure WooCommerce

- Set up payment gateways
- Configure shipping (if applicable)
- Set up tax settings
- Configure email templates
- Test checkout process

#### 5. Set Up Monitoring

- Enable error logging
- Set up uptime monitoring
- Configure Google Analytics
- Monitor API usage in Google AI Studio
- Set up backup schedule

#### 6. Train Your Team

- Train staff on admin dashboard
- Document internal procedures
- Create user support guidelines
- Prepare FAQ for customers

#### 7. Launch Preparation

- Test with real users (beta testing)
- Gather feedback and make adjustments
- Prepare marketing materials
- Set up customer support channels
- Create user documentation


---

## Troubleshooting Installation

This section covers common installation issues and their solutions. If you encounter problems during installation, check this section before contacting support.

### Common Installation Issues

#### Issue 1: Plugin Upload Fails

**Symptoms:**
- "The uploaded file exceeds the upload_max_filesize directive in php.ini"
- Upload progress bar stops or fails
- Timeout during upload

**Causes:**
- Server upload file size limit too low
- PHP execution time too short
- Server memory limit too low

**Solutions:**

**Solution A: Increase PHP Limits (Recommended)**

Contact your hosting provider to increase:
- `upload_max_filesize` to at least 10MB
- `post_max_size` to at least 10MB
- `max_execution_time` to at least 60 seconds
- `memory_limit` to at least 256MB

**Solution B: Use FTP Upload Method**

If you cannot increase PHP limits, use Method 2 (FTP/SFTP Upload) instead of WordPress admin upload.

**Solution C: Edit .htaccess (if you have access)**

Add these lines to your `.htaccess` file:
```apache
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value max_execution_time 60
php_value memory_limit 256M
```

**Solution D: Edit php.ini (if you have access)**

Add or modify these lines in `php.ini`:
```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 60
memory_limit = 256M
```


#### Issue 2: Plugin Activation Fails

**Symptoms:**
- "Plugin could not be activated because it triggered a fatal error"
- White screen after clicking Activate
- Error message about missing dependencies

**Causes:**
- PHP version too old
- Missing required PHP extensions
- WooCommerce not installed or activated
- Conflicting plugins
- Insufficient memory

**Solutions:**

**Solution A: Check PHP Version**
```bash
# Via WP-CLI
wp cli info

# Via PHP command line
php -v
```
Ensure PHP 7.4 or higher. Upgrade if necessary.

**Solution B: Verify PHP Extensions**

Check that all required extensions are installed:
- curl
- gd or imagick
- json
- mbstring
- openssl
- zip

Contact your hosting provider to install missing extensions.

**Solution C: Verify WooCommerce**

1. Navigate to **Plugins → Installed Plugins**
2. Ensure WooCommerce is installed and active
3. If not, install and activate WooCommerce first
4. Then try activating AI Virtual Fitting again

**Solution D: Check Error Logs**

1. Enable WordPress debugging by adding to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

2. Check `wp-content/debug.log` for specific error messages
3. Address the specific error mentioned

**Solution E: Increase Memory Limit**

Add to `wp-config.php` before "That's all, stop editing!":
```php
define('WP_MEMORY_LIMIT', '256M');
```


#### Issue 3: Database Tables Not Created

**Symptoms:**
- Plugin activates but doesn't work
- Error messages about missing tables
- "Table 'wp_virtual_fitting_credits' doesn't exist"

**Causes:**
- Database user lacks CREATE TABLE permission
- Database connection issues
- Activation hook didn't run properly

**Solutions:**

**Solution A: Check Database Permissions**

Verify your database user has these permissions:
- CREATE
- ALTER
- DROP
- INSERT
- UPDATE
- DELETE
- SELECT

Contact your hosting provider if permissions are missing.

**Solution B: Manually Create Tables**

If automatic creation failed, run this SQL manually in phpMyAdmin:

```sql
CREATE TABLE IF NOT EXISTS `wp_virtual_fitting_credits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `credits` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Solution C: Deactivate and Reactivate**

1. Deactivate the plugin
2. Wait 5 seconds
3. Activate the plugin again
4. Check if tables are created

**Solution D: Use WP-CLI**

```bash
# Deactivate
wp plugin deactivate ai-virtual-fitting

# Reactivate
wp plugin activate ai-virtual-fitting

# Verify tables
wp db query "SHOW TABLES LIKE 'wp_virtual_fitting%';"
```


#### Issue 4: Permission Errors

**Symptoms:**
- "Unable to create directory"
- "Permission denied" errors
- Cannot upload images
- Cannot write to log files

**Causes:**
- Incorrect file/folder permissions
- Wrong file ownership
- Server security restrictions

**Solutions:**

**Solution A: Set Correct Permissions via FTP**

1. Connect via FTP/SFTP
2. Navigate to `wp-content/plugins/ai-virtual-fitting/`
3. Set folder permissions to 755
4. Set file permissions to 644

**Solution B: Set Permissions via SSH**

```bash
# Navigate to WordPress directory
cd /path/to/wordpress/

# Set folder permissions
find wp-content/plugins/ai-virtual-fitting/ -type d -exec chmod 755 {} \;

# Set file permissions
find wp-content/plugins/ai-virtual-fitting/ -type f -exec chmod 644 {} \;

# Fix ownership (replace 'www-data' with your web server user)
chown -R www-data:www-data wp-content/plugins/ai-virtual-fitting/
```

**Solution C: Create Required Directories**

Ensure these directories exist and are writable:
- `wp-content/uploads/ai-virtual-fitting/`
- `wp-content/uploads/ai-virtual-fitting/temp/`
- `wp-content/uploads/ai-virtual-fitting/logs/`

Create them manually if needed:
```bash
mkdir -p wp-content/uploads/ai-virtual-fitting/temp
mkdir -p wp-content/uploads/ai-virtual-fitting/logs
chmod 755 wp-content/uploads/ai-virtual-fitting/
chmod 755 wp-content/uploads/ai-virtual-fitting/temp/
chmod 755 wp-content/uploads/ai-virtual-fitting/logs/
```


#### Issue 5: API Connection Test Fails

**Symptoms:**
- "API connection failed" message
- "Invalid API key" error
- Timeout during API test

**Causes:**
- Invalid or incorrect API key
- Server cannot make outbound HTTPS requests
- Firewall blocking Google AI Studio
- API quota exceeded
- Network connectivity issues

**Solutions:**

**Solution A: Verify API Key**

1. Check your API key in Google AI Studio
2. Ensure you copied the entire key (no spaces)
3. Verify the key has access to Gemini 2.5 Flash Image model
4. Try generating a new API key

**Solution B: Test Outbound Connections**

```bash
# Test if server can reach Google AI Studio
curl -I https://generativelanguage.googleapis.com

# Should return HTTP 200 or similar
```

If this fails, contact your hosting provider about outbound HTTPS restrictions.

**Solution C: Check Firewall Rules**

Ensure your server firewall allows outbound connections to:
- `generativelanguage.googleapis.com`
- Port 443 (HTTPS)

**Solution D: Verify API Quota**

1. Log in to Google AI Studio
2. Check your API usage dashboard
3. Verify you haven't exceeded quota limits
4. Upgrade plan if necessary

**Solution E: Check WordPress HTTP API**

Some security plugins block WordPress HTTP API. Temporarily disable security plugins and test again.


#### Issue 6: WooCommerce Integration Issues

**Symptoms:**
- Credit product not created
- Cannot add credits to cart
- Credits not added after purchase

**Causes:**
- WooCommerce not properly configured
- Conflicting WooCommerce extensions
- Order status not updating
- Hook conflicts

**Solutions:**

**Solution A: Verify WooCommerce Setup**

1. Navigate to **WooCommerce → Status**
2. Check for any errors or warnings
3. Resolve any WooCommerce issues first

**Solution B: Manually Create Credit Product**

1. Navigate to **Products → Add New**
2. Create a new product:
   - Name: "Virtual Fitting Credits"
   - Type: Simple product
   - Price: $10 (or your preferred price)
   - Add custom field: `_virtual_fitting_credits` = `20`
3. Publish the product

**Solution C: Check Order Processing**

1. Navigate to **WooCommerce → Orders**
2. Find a test order
3. Manually change status to "Completed"
4. Check if credits are added to user account

**Solution D: Clear WooCommerce Cache**

```bash
# Via WP-CLI
wp cache flush
wp transient delete --all

# Or use a caching plugin's clear cache function
```

### Error Messages Reference

#### "Plugin could not be activated"

**Meaning**: Fatal PHP error during activation

**Resolution**:
1. Check PHP version (must be 7.4+)
2. Verify all required PHP extensions installed
3. Check error logs for specific error
4. Increase memory limit


#### "The uploaded file exceeds the upload_max_filesize"

**Meaning**: Server upload limit too low

**Resolution**:
1. Increase `upload_max_filesize` in php.ini
2. Increase `post_max_size` in php.ini
3. Or use FTP upload method instead

#### "Table 'wp_virtual_fitting_credits' doesn't exist"

**Meaning**: Database tables not created during activation

**Resolution**:
1. Check database user permissions
2. Deactivate and reactivate plugin
3. Manually create tables using SQL provided above

#### "API connection failed"

**Meaning**: Cannot connect to Google AI Studio

**Resolution**:
1. Verify API key is correct
2. Check server can make outbound HTTPS requests
3. Verify API quota not exceeded
4. Check firewall rules

#### "Permission denied"

**Meaning**: File/folder permissions incorrect

**Resolution**:
1. Set folder permissions to 755
2. Set file permissions to 644
3. Ensure web server user owns files
4. Create required directories

#### "WooCommerce is required"

**Meaning**: WooCommerce not installed or activated

**Resolution**:
1. Install WooCommerce plugin
2. Activate WooCommerce
3. Complete WooCommerce setup wizard
4. Then activate AI Virtual Fitting plugin

### Getting Additional Help

If you've tried the solutions above and still experience issues:

#### Check Documentation

- [User Guide](USER-GUIDE.md) - For end-user issues
- [Admin Guide](ADMIN-GUIDE.md) - For configuration issues
- [Troubleshooting Guide](TROUBLESHOOTING.md) - For operational issues
- [Developer Documentation](../DEVELOPER.md) - For technical issues


#### Enable Debug Mode

For detailed error information:

1. Edit `wp-config.php`
2. Add before "That's all, stop editing!":
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```
3. Check `wp-content/debug.log` for errors

#### Collect System Information

When contacting support, provide:

1. WordPress version
2. PHP version
3. WooCommerce version
4. Plugin version
5. Server environment (Apache/Nginx)
6. Error messages from logs
7. Steps to reproduce the issue

#### Contact Support

- **Email**: support@yourcompany.com
- **Documentation**: https://yoursite.com/docs
- **Support Forum**: https://yoursite.com/support
- **GitHub Issues**: https://github.com/yourcompany/ai-virtual-fitting/issues


---

## Uninstallation

If you need to remove the AI Virtual Fitting Plugin from your WordPress site, follow these procedures. The uninstallation process can preserve your data for future reinstallation or completely remove all plugin data.

### Before Uninstalling

#### Important Considerations

**Data Loss Warning**: Uninstalling the plugin will remove all plugin data unless you choose to preserve it. Consider the following before proceeding:

- **User Credits**: All user credit balances will be lost
- **Usage History**: Virtual fitting history and analytics will be deleted
- **Settings**: All plugin configuration will be removed
- **Database Tables**: Plugin database tables will be dropped
- **Uploaded Files**: Temporary files and logs will be deleted

**Recommendation**: Create a backup before uninstalling if you might want to reinstall later.

#### Backup Before Uninstallation

**Option 1: Export User Credits**

Before uninstalling, export user credit data:

1. Navigate to **Virtual Fitting → Users**
2. Click **Export Credits** button
3. Save the CSV file to your computer
4. This allows you to restore credits if you reinstall

**Option 2: Database Backup**

Create a backup of plugin database tables:

```bash
# Via WP-CLI
wp db export backup-virtual-fitting.sql --tables=wp_virtual_fitting_credits

# Via phpMyAdmin
# 1. Select your database
# 2. Click "Export" tab
# 3. Select "Custom" export method
# 4. Choose only wp_virtual_fitting_* tables
# 5. Click "Go" to download
```

**Option 3: Complete Backup**

Create a full site backup using your preferred backup method (see Pre-Installation section for backup procedures).


### Deactivation Procedures

Deactivating the plugin disables its functionality but preserves all data. This is useful for temporary disabling or troubleshooting.

#### Method 1: WordPress Admin Deactivation

1. Log in to WordPress admin dashboard
2. Navigate to **Plugins → Installed Plugins**
3. Find "AI Virtual Fitting" in the plugin list
4. Click **Deactivate** link
5. The plugin is now deactivated but data is preserved

**What Happens During Deactivation:**
- Plugin functionality stops immediately
- Virtual fitting page becomes non-functional
- Admin menu items are hidden
- Database tables remain intact
- User credits are preserved
- Settings are preserved
- Files remain on server

**To Reactivate:**
1. Navigate to **Plugins → Installed Plugins**
2. Find "AI Virtual Fitting"
3. Click **Activate** link
4. All data and settings are restored

#### Method 2: WP-CLI Deactivation

```bash
# Deactivate plugin
wp plugin deactivate ai-virtual-fitting

# Verify deactivation
wp plugin list --status=inactive | grep ai-virtual-fitting

# Reactivate if needed
wp plugin activate ai-virtual-fitting
```

#### Method 3: Emergency Deactivation

If you cannot access WordPress admin (e.g., due to fatal error):

**Via FTP/SFTP:**
1. Connect to your server via FTP/SFTP
2. Navigate to `wp-content/plugins/`
3. Rename `ai-virtual-fitting` folder to `ai-virtual-fitting-disabled`
4. Plugin is now deactivated
5. To reactivate, rename back to `ai-virtual-fitting`

**Via Database:**
1. Access phpMyAdmin
2. Select your WordPress database
3. Open `wp_options` table
4. Find row where `option_name` = `active_plugins`
5. Edit `option_value` and remove the plugin entry
6. Save changes


### Data Preservation Options

If you want to uninstall the plugin but preserve data for future use:

#### Option 1: Deactivate Only (Recommended)

Simply deactivate the plugin instead of deleting it. This preserves:
- All database tables and data
- All plugin files
- All settings and configuration
- User credits and history

The plugin files remain on your server but are inactive.

#### Option 2: Delete Plugin, Keep Database

1. Before deleting, modify the uninstall behavior
2. Edit `ai-virtual-fitting/uninstall.php`
3. Comment out the database cleanup code:
```php
// Comment out these lines to preserve database
// global $wpdb;
// $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}virtual_fitting_credits");
```
4. Now you can delete the plugin while preserving database data

**Warning**: This is an advanced option and requires file editing.

#### Option 3: Export and Reimport

1. Export user credits (as described above)
2. Document your settings (take screenshots)
3. Uninstall the plugin completely
4. When reinstalling, manually restore:
   - Import user credits from CSV
   - Reconfigure settings from screenshots
   - Recreate WooCommerce products

### Complete Removal

To completely remove the plugin and all its data:

#### Method 1: WordPress Admin Deletion (Recommended)

1. **First, deactivate the plugin** (see Deactivation Procedures above)
2. Navigate to **Plugins → Installed Plugins**
3. Find "AI Virtual Fitting" (should show "Inactive")
4. Click **Delete** link
5. Confirm deletion when prompted
6. WordPress will:
   - Delete all plugin files
   - Run uninstall.php script
   - Remove database tables
   - Clean up uploaded files
   - Remove plugin options

**Verification:**
- Plugin no longer appears in plugin list
- Database tables removed (check in phpMyAdmin)
- Plugin folder removed from `wp-content/plugins/`


#### Method 2: WP-CLI Deletion

```bash
# First deactivate
wp plugin deactivate ai-virtual-fitting

# Then uninstall (runs uninstall.php and deletes files)
wp plugin uninstall ai-virtual-fitting

# Verify removal
wp plugin list | grep ai-virtual-fitting
# Should return no results

# Verify database tables removed
wp db query "SHOW TABLES LIKE 'wp_virtual_fitting%';"
# Should return no results
```

#### Method 3: Manual Deletion

If automatic deletion fails, manually remove plugin files and data:

**Step 1: Deactivate Plugin**
Follow deactivation procedures above.

**Step 2: Delete Plugin Files**

Via FTP/SFTP:
1. Connect to your server
2. Navigate to `wp-content/plugins/`
3. Delete the `ai-virtual-fitting` folder
4. Verify folder is completely removed

Via SSH:
```bash
cd /path/to/wordpress/wp-content/plugins/
rm -rf ai-virtual-fitting/
```

**Step 3: Remove Database Tables**

Via phpMyAdmin:
1. Access phpMyAdmin
2. Select your WordPress database
3. Find tables starting with `wp_virtual_fitting_`
4. Select all plugin tables
5. Click "Drop" to delete
6. Confirm deletion

Via WP-CLI:
```bash
wp db query "DROP TABLE IF EXISTS wp_virtual_fitting_credits;"
```

**Step 4: Remove Plugin Options**

Via phpMyAdmin:
1. Open `wp_options` table
2. Search for options starting with `ai_virtual_fitting_`
3. Delete all matching rows

Via WP-CLI:
```bash
wp option delete ai_virtual_fitting_settings
wp option delete ai_virtual_fitting_version
wp option delete ai_virtual_fitting_api_key
# Repeat for all plugin options
```


**Step 5: Remove Uploaded Files**

Via FTP/SFTP:
1. Navigate to `wp-content/uploads/`
2. Delete the `ai-virtual-fitting` folder

Via SSH:
```bash
cd /path/to/wordpress/wp-content/uploads/
rm -rf ai-virtual-fitting/
```

**Step 6: Remove WooCommerce Products**

1. Navigate to **Products → All Products**
2. Find "Virtual Fitting Credits" product
3. Move to trash or delete permanently
4. Empty trash if needed

**Step 7: Clean Up Pages**

1. Navigate to **Pages → All Pages**
2. Find pages using `[ai_virtual_fitting]` shortcode
3. Remove the shortcode or delete the pages
4. Update any menu items pointing to these pages

### Post-Uninstallation Verification

After uninstalling, verify complete removal:

#### Verification Checklist

- [ ] Plugin not listed in **Plugins → Installed Plugins**
- [ ] Plugin folder removed from `wp-content/plugins/`
- [ ] Database tables removed (check phpMyAdmin)
- [ ] Plugin options removed from `wp_options` table
- [ ] Uploaded files removed from `wp-content/uploads/`
- [ ] No PHP errors on site
- [ ] No JavaScript errors in browser console
- [ ] WooCommerce credit products removed or updated
- [ ] Pages with shortcode updated or removed
- [ ] Admin menu items no longer visible

#### Database Verification

```bash
# Check for remaining tables
wp db query "SHOW TABLES LIKE 'wp_virtual_fitting%';"

# Check for remaining options
wp db query "SELECT * FROM wp_options WHERE option_name LIKE 'ai_virtual_fitting%';"

# Both should return no results
```


### Reinstallation After Uninstallation

If you uninstalled the plugin and want to reinstall it:

#### Fresh Installation

1. Follow the installation procedures in this guide
2. The plugin will be installed as new
3. All settings will be at default values
4. No user credits will exist
5. You'll need to reconfigure everything

#### Restoring Previous Data

If you backed up data before uninstalling:

**Restore User Credits:**
1. Install and activate the plugin
2. Navigate to **Virtual Fitting → Users**
3. Click **Import Credits**
4. Upload the CSV file you exported
5. Credits are restored to user accounts

**Restore Settings:**
1. Manually reconfigure settings using your screenshots/notes
2. Re-enter API key
3. Reconfigure credit packages
4. Recreate WooCommerce products if needed

**Restore Database Backup:**
```bash
# If you backed up database tables
wp db import backup-virtual-fitting.sql

# Verify tables restored
wp db query "SHOW TABLES LIKE 'wp_virtual_fitting%';"
```

### Troubleshooting Uninstallation

#### Issue: Cannot Delete Plugin

**Cause**: File permissions or active plugin

**Solution**:
1. Ensure plugin is deactivated first
2. Check file permissions (need write access)
3. Try manual deletion via FTP
4. Contact hosting provider if permissions issue

#### Issue: Database Tables Not Removed

**Cause**: Uninstall script didn't run or failed

**Solution**:
1. Manually drop tables via phpMyAdmin
2. Or use WP-CLI commands provided above
3. Verify tables are removed

#### Issue: Files Remain After Deletion

**Cause**: Permission issues or incomplete deletion

**Solution**:
1. Manually delete via FTP/SFTP
2. Check file ownership and permissions
3. Use SSH to force delete if needed


### Uninstallation Best Practices

1. **Always Backup First**: Create complete backup before uninstalling
2. **Export Data**: Export user credits and important data
3. **Document Settings**: Take screenshots of all settings
4. **Deactivate First**: Always deactivate before deleting
5. **Verify Removal**: Check that all data is removed after uninstallation
6. **Clean Up**: Remove related pages, products, and menu items
7. **Test Site**: Verify site works correctly after uninstallation
8. **Keep Backups**: Store backups for at least 30 days

### Alternative to Uninstallation

Instead of uninstalling, consider these alternatives:

**Temporary Disable**: Just deactivate the plugin
- Preserves all data
- Can reactivate anytime
- No data loss risk

**Disable Features**: Use plugin settings to disable features
- Keep plugin installed
- Turn off virtual fitting feature
- Maintain data and history

**Maintenance Mode**: Put plugin in maintenance mode
- Display maintenance message to users
- Keep admin access
- Preserve all functionality for later

---

## Related Documentation

- [User Guide](USER-GUIDE.md) - End user documentation
- [Admin Guide](ADMIN-GUIDE.md) - Administrator documentation
- [Configuration Reference](CONFIGURATION.md) - Detailed settings guide
- [Troubleshooting Guide](TROUBLESHOOTING.md) - Problem resolution
- [Developer Documentation](../DEVELOPER.md) - Technical documentation

---

## Support

If you need assistance with installation, configuration, or uninstallation:

- **Documentation**: [https://yoursite.com/docs](https://yoursite.com/docs)
- **Support Email**: support@yourcompany.com
- **Support Forum**: [https://yoursite.com/support](https://yoursite.com/support)
- **GitHub Issues**: [https://github.com/yourcompany/ai-virtual-fitting/issues](https://github.com/yourcompany/ai-virtual-fitting/issues)

---

**Last Updated**: January 2026  
**Version**: 1.0.0  
**Document Version**: 1.0
