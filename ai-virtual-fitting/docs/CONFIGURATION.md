# Configuration Reference

## Table of Contents

- [Configuration Overview](#configuration-overview)
- [General Settings](#general-settings)
- [API Configuration](#api-configuration)
- [Credit System Configuration](#credit-system-configuration)
- [Image Processing Configuration](#image-processing-configuration)
- [Security Configuration](#security-configuration)
- [Performance Configuration](#performance-configuration)
- [Advanced Configuration](#advanced-configuration)
- [Environment-Specific Configuration](#environment-specific-configuration)
- [Configuration Examples](#configuration-examples)

---

## Configuration Overview

The AI Virtual Fitting plugin provides comprehensive configuration options accessible through the WordPress admin panel at **Settings → AI Virtual Fitting**. All settings are stored in the WordPress options table and can be managed through the admin interface or programmatically via WordPress functions.

### Configuration Locations

**Admin Interface:**
- Navigate to **WordPress Admin → Settings → AI Virtual Fitting**
- All settings are organized into logical sections with inline help tooltips
- Changes take effect immediately after saving

**Database Storage:**
- Settings are stored as WordPress options in the `wp_options` table
- Option names follow the pattern: `ai_virtual_fitting_{setting_name}`
- Sensitive data (API keys) are encrypted using AES-256-CBC encryption

**Configuration Files:**
- Plugin configuration constants: `includes/class-plugin-config.php`
- Admin settings class: `admin/class-admin-settings.php`
- Security manager: `includes/class-security-manager.php`

### Settings Hierarchy

Settings are organized into the following sections:

1. **API Configuration** - Google AI Studio integration settings
2. **Credit System** - Credit allocation and pricing
3. **System Settings** - Image processing and retry logic
4. **Advanced Settings** - Performance, security, and developer options
5. **Monitoring** - Analytics and logging configuration

### Default Values

All settings have sensible defaults that work for most installations:


| Setting | Default Value | Description |
|---------|--------------|-------------|
| API Provider | `google_ai_studio` | AI service provider |
| Initial Credits | `2` | Free credits for new users |
| Credits per Package | `20` | Credits in each purchase |
| Package Price | `$10.00` | Price per credit package |
| Max Image Size | `10 MB` | Maximum upload file size |
| API Retry Attempts | `3` | Number of retry attempts |
| API Timeout | `60 seconds` | Maximum API wait time |
| Enable Logging | `true` | Debug logging enabled |
| Enable Analytics | `true` | Usage tracking enabled |
| Require Login | `true` | Login required for access |
| Temp File Cleanup | `24 hours` | Cleanup interval |

---

## General Settings

General settings control the overall behavior of the plugin, including logging, analytics, and user access requirements.

### Plugin Enable/Disable

The plugin can be activated or deactivated through the WordPress Plugins page. When deactivated, all virtual fitting functionality is disabled, but data is preserved.

**Location:** WordPress Admin → Plugins → AI Virtual Fitting

**Options:**
- **Activate** - Enable all plugin functionality
- **Deactivate** - Disable plugin (preserves data)
- **Delete** - Remove plugin and optionally delete data


### Logging Options

**Setting:** `ai_virtual_fitting_enable_logging`  
**Type:** Boolean (checkbox)  
**Default:** `true`  
**Location:** Settings → AI Virtual Fitting → System Settings

**Description:**  
Controls whether the plugin writes detailed logs for debugging and monitoring purposes. When enabled, the plugin logs API calls, errors, user activities, and system events.

**Options:**
- **Enabled** - Write detailed logs to WordPress debug log
- **Disabled** - Only log critical errors

**Log Location:**  
Logs are written to the WordPress debug log when `WP_DEBUG_LOG` is enabled in `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

**Log File:** `wp-content/debug.log`

**What Gets Logged:**
- API requests and responses
- Image processing operations
- Credit transactions
- Error messages and stack traces
- Security events (rate limiting, validation failures)
- Performance metrics

**Recommendations:**
- **Development:** Enable logging for troubleshooting
- **Production:** Enable logging initially, disable after stable operation
- **High Traffic:** Disable to reduce disk I/O


### Analytics Options

**Setting:** `ai_virtual_fitting_enable_analytics`  
**Type:** Boolean (checkbox)  
**Default:** `true`  
**Location:** Settings → AI Virtual Fitting → Advanced Settings

**Description:**  
Enables collection of anonymous usage statistics for plugin improvement and performance monitoring.

**Data Collected:**
- Number of virtual fittings processed
- Success/failure rates
- Average processing times
- Credit usage patterns
- Popular products
- User engagement metrics

**Privacy:**
- No personally identifiable information (PII) is collected
- Data is stored locally in your WordPress database
- No data is sent to external servers
- Compliant with GDPR and privacy regulations

**Database Tables:**
- `wp_virtual_fitting_analytics` - Usage statistics
- `wp_virtual_fitting_sessions` - Session tracking

**Recommendations:**
- **Enable** for insights into plugin usage and performance
- **Disable** if you have strict privacy requirements or want to reduce database size

---

## API Configuration

API configuration settings control the connection to Google AI Studio for AI-powered virtual fitting processing.


### Google AI Studio Settings

**Setting:** `ai_virtual_fitting_google_ai_api_key`  
**Type:** String (encrypted)  
**Default:** Empty  
**Location:** Settings → AI Virtual Fitting → Google AI Studio Configuration

**Description:**  
Your Google AI Studio API key is required for AI-powered virtual fitting functionality. The key is encrypted using AES-256-CBC encryption before storage.

**How to Obtain:**
1. Visit [Google AI Studio](https://aistudio.google.com/app/apikey)
2. Sign in with your Google account
3. Click "Create API Key"
4. Copy the generated key (starts with `AIza...`)
5. Paste into the plugin settings

**Security:**
- API keys are encrypted before storage in the database
- Encryption uses WordPress `AUTH_KEY` and `SECURE_AUTH_KEY` constants
- Keys are decrypted only when making API calls
- Never logged or displayed in plain text

**Validation:**
- Click "Test Connection" button to verify the API key
- Plugin will make a test API call to confirm connectivity
- Invalid keys will display an error message

**API Provider Selection:**

The plugin supports two Google AI providers:

1. **Google AI Studio (API Key)** - Recommended for most users
   - Simple setup with just an API key
   - Free tier available
   - No Google Cloud account required

2. **Google Cloud Vertex AI (Service Account)** - For enterprise users
   - Requires Google Cloud project
   - Uses service account JSON credentials
   - More control and billing options


### AI Prompt Template

**Setting:** `ai_virtual_fitting_ai_prompt_template`  
**Type:** Text (2000 character limit)  
**Default:** Optimized prompt for wedding dress virtual try-on  
**Location:** Settings → AI Virtual Fitting → Google AI Studio Configuration

**Description:**  
The AI prompt template instructs the AI model how to generate virtual fitting images. You can customize this prompt to improve results for your specific products or use cases.

**Default Prompt:**
```
You are a virtual try-on image generation system.

INPUTS:
- Image A: a real person (customer photo).
- Image(s) B: wedding dress product images.

OBJECTIVE:
Generate a realistic virtual try-on image showing the person from Image A 
wearing the wedding dress from Image B.

STRICT RULES (DO NOT VIOLATE):
1. The person's body shape, weight, proportions, height, posture, and pose 
   from Image A MUST be preserved exactly.
2. The person's face, identity, skin tone, and expression MUST remain unchanged.
3. The wedding dress style MUST match the product images.
4. Lighting and perspective MUST match Image A.
5. The result MUST look like a real-life fitting.
```

**Customization Tips:**
- Keep the prompt clear and specific
- Include constraints to maintain realism
- Specify what should be preserved (body, face, lighting)
- Define quality expectations
- Test changes with sample images

**Character Limit:** 10-2000 characters

**Reset to Default:** Click "Reset to Default" button to restore the original prompt


### API Endpoints

**Gemini Text API Endpoint:**  
**Setting:** `ai_virtual_fitting_gemini_text_api_endpoint`  
**Type:** URL (HTTPS only)  
**Default:** `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent`  
**Location:** Settings → AI Virtual Fitting → Google AI Studio Configuration

**Gemini Image API Endpoint:**  
**Setting:** `ai_virtual_fitting_gemini_image_api_endpoint`  
**Type:** URL (HTTPS only)  
**Default:** `https://generativelanguage.googleapis.com/v1beta/models/gemini-3-pro-image-preview:generateContent`  
**Location:** Settings → AI Virtual Fitting → Google AI Studio Configuration

**Description:**  
Custom API endpoints for Gemini models. Leave empty to use default endpoints. Only change if you need to use a different Gemini model version or have a custom API gateway.

**When to Customize:**
- Using a different Gemini model version
- Routing through a custom API gateway
- Testing beta or experimental models
- Enterprise proxy requirements

**Validation:**
- Must be valid HTTPS URLs
- Must point to Google AI API endpoints
- Test connection after changing

### Timeout Settings

**Setting:** `ai_virtual_fitting_api_timeout`  
**Type:** Integer (seconds)  
**Default:** `60`  
**Range:** 10-300 seconds  
**Location:** Settings → AI Virtual Fitting → Advanced Settings

**Description:**  
Maximum time to wait for API responses before timing out. AI image generation typically takes 30-60 seconds.

**Recommendations:**
- **Fast Network:** 30-45 seconds
- **Standard:** 60 seconds (default)
- **Slow Network:** 90-120 seconds
- **High Quality:** 120-180 seconds

**Impact:**
- Too low: Premature timeouts, failed requests
- Too high: Long wait times for users, resource usage


### Retry Configuration

**Setting:** `ai_virtual_fitting_api_retry_attempts`  
**Type:** Integer  
**Default:** `3`  
**Range:** 1-10 attempts  
**Location:** Settings → AI Virtual Fitting → System Settings

**Description:**  
Number of times to retry failed API calls before giving up. Helps handle temporary network issues or API rate limits.

**Retry Behavior:**
- Exponential backoff between retries
- First retry: immediate
- Second retry: 2 seconds delay
- Third retry: 4 seconds delay
- Subsequent retries: 8+ seconds delay

**Recommendations:**
- **Reliable Network:** 2-3 retries
- **Unreliable Network:** 4-5 retries
- **Production:** 3 retries (default)
- **Development:** 1-2 retries (faster failure)

**Impact:**
- More retries: Better reliability, longer wait times
- Fewer retries: Faster failures, less reliability

---

## Credit System Configuration

Credit system settings control how credits are allocated, priced, and managed for virtual fitting usage.


### Initial Credits

**Setting:** `ai_virtual_fitting_initial_credits`  
**Type:** Integer  
**Default:** `2`  
**Range:** 0-10 credits  
**Location:** Settings → AI Virtual Fitting → Credit System Settings

**Description:**  
Number of free credits automatically given to new users when they register or first access the virtual fitting feature.

**Purpose:**
- Allow users to try the feature before purchasing
- Reduce friction in the onboarding process
- Demonstrate value before asking for payment

**Recommendations:**
- **Conservative:** 1 credit (minimal trial)
- **Standard:** 2 credits (default, allows comparison)
- **Generous:** 3-5 credits (extensive trial)
- **No Trial:** 0 credits (purchase required)

**Business Considerations:**
- Higher initial credits increase trial conversion
- Lower initial credits reduce API costs
- Consider your API costs and profit margins
- Monitor conversion rates to optimize

**When Credits Are Granted:**
- New user registration
- First login (for existing users)
- Only granted once per user
- Tracked in `wp_virtual_fitting_credits` table


### Package Configuration

**Credits per Package:**  
**Setting:** `ai_virtual_fitting_credits_per_package`  
**Type:** Integer  
**Default:** `20`  
**Range:** 1-100 credits  
**Location:** Settings → AI Virtual Fitting → Credit System Settings

**Description:**  
Number of virtual fitting credits included in each purchased credit package.

**Pricing Strategy:**
- More credits per package = better value for customers
- Fewer credits per package = higher revenue per credit
- Consider bulk discount tiers for larger packages

**Common Configurations:**
- **Small Package:** 10 credits
- **Standard Package:** 20 credits (default)
- **Large Package:** 50 credits
- **Bulk Package:** 100 credits

**Package Price:**  
**Setting:** `ai_virtual_fitting_credits_package_price`  
**Type:** Decimal (currency)  
**Default:** `$10.00`  
**Range:** $0.01 and up  
**Location:** Settings → AI Virtual Fitting → Credit System Settings

**Description:**  
Price customers pay for each credit package in your store's currency.

**Pricing Considerations:**
- **API Costs:** Google AI Studio API costs per request
- **Server Costs:** Image processing and storage
- **Profit Margin:** Desired profit per credit
- **Market Rate:** Competitive pricing in your market
- **Value Perception:** Price should reflect quality

**Example Pricing Models:**

| Credits | Price | Cost per Credit | Use Case |
|---------|-------|----------------|----------|
| 10 | $5.00 | $0.50 | Trial package |
| 20 | $10.00 | $0.50 | Standard (default) |
| 50 | $20.00 | $0.40 | Value package |
| 100 | $35.00 | $0.35 | Bulk discount |


### Product Settings

**Credit Product ID:**  
**Setting:** `ai_virtual_fitting_credit_product_id`  
**Type:** Integer (WooCommerce Product ID)  
**Default:** Auto-created on activation  
**Location:** Automatically managed

**Description:**  
The WooCommerce product ID for the virtual fitting credit package. This product is automatically created when the plugin is activated.

**Product Details:**
- **Product Type:** Simple product
- **Product Name:** "Virtual Fitting Credits"
- **SKU:** `virtual-fitting-credits`
- **Price:** Set by `credits_package_price` setting
- **Stock:** Always in stock (virtual product)
- **Downloadable:** No
- **Virtual:** Yes

**Customization:**
- Edit product in WooCommerce → Products
- Change product name, description, images
- Add product categories or tags
- Modify product visibility
- **Do not change:** SKU, product type, or meta fields

**Meta Fields:**
- `_is_virtual_fitting_credit_product` - Identifies credit product
- `_virtual_fitting_credit_amount` - Credits per purchase
- `_virtual_fitting_credits` - Product meta

---

## Image Processing Configuration

Image processing settings control file upload limits, allowed formats, and quality requirements.


### File Size Limits

**Setting:** `ai_virtual_fitting_max_image_size`  
**Type:** Integer (bytes)  
**Default:** `10485760` (10 MB)  
**Range:** 1 MB - 50 MB  
**Location:** Settings → AI Virtual Fitting → System Settings

**Description:**  
Maximum allowed file size for customer photo uploads and product images.

**Size Recommendations:**

| Size | Use Case | Quality | Upload Time |
|------|----------|---------|-------------|
| 2 MB | Mobile-optimized | Good | Fast |
| 5 MB | Standard quality | Very Good | Medium |
| 10 MB | High quality (default) | Excellent | Slower |
| 20 MB | Professional | Maximum | Slow |

**Considerations:**
- **Server Limits:** Check PHP `upload_max_filesize` and `post_max_size`
- **Network Speed:** Consider your users' connection speeds
- **Storage:** Larger files require more disk space
- **Processing:** Larger files take longer to process

**Server Configuration:**

Ensure your server allows the configured file size:

```php
// wp-config.php or php.ini
upload_max_filesize = 10M
post_max_size = 10M
memory_limit = 256M
max_execution_time = 300
```


### Allowed Formats

**Supported Image Formats:**
- **JPEG** (.jpg, .jpeg) - Recommended for photos
- **PNG** (.png) - Supports transparency
- **WebP** (.webp) - Modern format, smaller file sizes

**Format Validation:**
- MIME type verification
- Magic byte validation (file header check)
- Extension validation
- Image integrity check

**Format Recommendations:**
- **Customer Photos:** JPEG (best compression for photos)
- **Product Images:** JPEG or PNG (PNG for transparency)
- **Modern Browsers:** WebP (best compression and quality)

**Not Supported:**
- GIF (animated or static)
- BMP (uncompressed, too large)
- TIFF (professional format, too large)
- SVG (vector format, not suitable)

### Dimension Requirements

**Minimum Dimensions:**
- Width: 512 pixels
- Height: 512 pixels

**Maximum Dimensions:**
- Width: 2048 pixels
- Height: 2048 pixels

**Optimal Dimensions:**
- Width: 1024 pixels
- Height: 1024 pixels

**Aspect Ratio:**
- No strict requirements
- Portrait orientation recommended for customer photos
- Any orientation accepted for product images

**Automatic Processing:**
- Images are automatically resized if too large
- Aspect ratio is preserved
- Quality is optimized for AI processing
- Optimized images are cached for performance


### Quality Settings

**Image Quality:**
- JPEG compression: 85% quality
- PNG: Lossless compression
- WebP: 85% quality

**Optimization:**
- Automatic resizing to optimal dimensions
- Color space conversion (RGB)
- Metadata stripping (EXIF data removed)
- Progressive encoding for faster loading

**Processing Pipeline:**
1. Upload validation (format, size, dimensions)
2. Security validation (magic bytes, MIME type)
3. Image optimization (resize, compress)
4. Temporary storage
5. AI processing
6. Result storage
7. Cleanup (after 24 hours)

---

## Security Configuration

Security settings protect your site from attacks and ensure safe operation of the virtual fitting feature.

### API Key Encryption

**Encryption Method:** AES-256-CBC  
**Key Derivation:** WordPress `AUTH_KEY` + `SECURE_AUTH_KEY`  
**Storage:** Encrypted in `wp_options` table

**Description:**  
All API keys are automatically encrypted before storage using industry-standard AES-256-CBC encryption.

**Security Features:**
- 256-bit encryption key
- Random initialization vector (IV) per encryption
- Base64 encoding for storage
- Automatic decryption only when needed
- Never logged or displayed in plain text

**Key Management:**
- Encryption keys derived from WordPress security constants
- Change WordPress keys to invalidate stored API keys
- Backup encrypted keys separately from WordPress keys


### Rate Limiting

**Rate Limit Window:** 5 minutes (300 seconds)  
**Maximum Requests:** 20 per window  
**Scope:** Per user (logged in) or per IP (guests)

**Description:**  
Automatic rate limiting prevents abuse and protects your API quota from excessive usage.

**Protected Actions:**
- Image uploads
- Virtual fitting processing
- Credit purchases
- API calls

**Rate Limit Behavior:**
- Tracks requests per user/IP
- Resets after time window expires
- Returns error when limit exceeded
- Logs rate limit violations

**Customization:**

To modify rate limits, edit `includes/class-security-manager.php`:

```php
const RATE_LIMIT_WINDOW = 300; // 5 minutes
const MAX_REQUESTS_PER_WINDOW = 20; // 20 requests
```

**Bypass Rate Limits:**
- Administrators are not rate limited
- Can be disabled for testing (not recommended)

### File Validation

**Validation Layers:**

1. **Extension Check** - Verify file extension (.jpg, .png, .webp)
2. **MIME Type Check** - Verify MIME type header
3. **Magic Byte Check** - Verify file signature (first bytes)
4. **Image Integrity** - Verify image can be loaded
5. **Size Check** - Verify file size within limits
6. **Dimension Check** - Verify image dimensions

**Security Measures:**
- Prevents malicious file uploads
- Blocks executable files disguised as images
- Validates actual file content, not just extension
- Sanitizes file names
- Stores files in protected directories


### SSRF Protection

**SSRF (Server-Side Request Forgery) Protection:**

**Allowed Domains:**
- localhost (development only)
- Current WordPress site domain
- WooCommerce product image domains (configurable)

**Setting:** `ai_virtual_fitting_allow_woocommerce_domains`  
**Type:** Boolean  
**Default:** `true`

**Protection Measures:**
- URL validation (format, protocol)
- Domain whitelist enforcement
- Private IP range blocking
- Protocol restriction (HTTP/HTTPS only)
- DNS resolution validation

**Blocked Targets:**
- Private IP ranges (10.x.x.x, 192.168.x.x, 172.16-31.x.x)
- Localhost (in production)
- Reserved IP ranges
- Non-HTTP protocols (file://, ftp://, etc.)

**Configuration:**

To add allowed domains, edit `includes/class-security-manager.php`:

```php
const ALLOWED_IMAGE_DOMAINS = array(
    'localhost',
    '127.0.0.1',
    'your-cdn-domain.com',
    'your-image-host.com'
);
```

---

## Performance Configuration

Performance settings optimize the plugin for speed, efficiency, and scalability.


### Caching Settings

**Cache System:**
- WordPress object cache (if available)
- Transient API (fallback)
- File-based cache for optimized images

**Cache Types:**

1. **Product Images Cache**
   - Duration: 1 hour (3600 seconds)
   - Key: `ai_vf_products_{product_id}`
   - Stores: Product image URLs and metadata

2. **Optimized Images Cache**
   - Duration: 6 hours (21600 seconds)
   - Key: `optimized_image_{image_id}`
   - Stores: Resized and optimized image paths

3. **User Credits Cache**
   - Duration: 1 minute (60 seconds)
   - Key: `ai_vf_user_credits_{user_id}`
   - Stores: Current credit balance

4. **Analytics Cache**
   - Duration: 5 minutes (300 seconds)
   - Key: `ai_vf_analytics`
   - Stores: Dashboard analytics data

**Cache Configuration:**

Cache durations are defined in `includes/class-plugin-config.php`:

```php
const CACHE_PRODUCTS_EXPIRATION = 3600;      // 1 hour
const CACHE_ANALYTICS_EXPIRATION = 300;      // 5 minutes
const CACHE_USER_CREDITS_EXPIRATION = 60;    // 1 minute
const CACHE_API_RESPONSE_EXPIRATION = 86400; // 24 hours
```

**Cache Cleanup:**
- Automatic cleanup runs hourly
- Removes expired cache entries
- Deletes old optimized images (7+ days)
- Cleans temporary files (24+ hours)


### Queue Management

**Queue System:**
- Asynchronous processing queue
- Priority-based scheduling
- Automatic retry on failure
- Concurrent processing support

**Queue Settings:**

**Maximum Concurrent Processes:** 3  
**Queue Processing Interval:** 30 seconds  
**Maximum Queue Age:** 24 hours

**Queue Priority Levels:**

| Priority | User Type | Description |
|----------|-----------|-------------|
| 3 (High) | Premium | Purchased 20+ credits |
| 2 (Medium) | Paid | Purchased any credits |
| 1 (Normal) | Free | Using free credits only |

**Queue Processing:**
1. Items sorted by priority, then queue time
2. Up to 3 items processed concurrently
3. Failed items retry with exponential backoff
4. Completed items removed after 24 hours

**Queue Configuration:**

Edit `includes/class-performance-manager.php`:

```php
const MAX_CONCURRENT_PROCESSES = 3;
const QUEUE_PROCESSING_INTERVAL = 30;
```

### Optimization Options

**Image Optimization:**
- Automatic resizing to optimal dimensions (1024x1024)
- JPEG compression (85% quality)
- Progressive encoding
- Metadata stripping

**Database Optimization:**
- Indexed queries for fast lookups
- Efficient credit tracking
- Optimized analytics queries
- Regular cleanup of old data

**Resource Management:**
- Memory limit: 256 MB recommended
- Execution time: 300 seconds recommended
- Temporary file cleanup
- Cache expiration management


---

## Advanced Configuration

Advanced settings provide fine-grained control for experienced administrators and developers.

### Database Settings

**Database Tables:**

1. **Credits Table:** `wp_virtual_fitting_credits`
   - Stores user credit balances
   - Tracks credit transactions
   - Indexed by user_id

2. **Analytics Table:** `wp_virtual_fitting_analytics`
   - Stores usage statistics
   - Tracks performance metrics
   - Indexed by date and user_id

3. **Sessions Table:** `wp_virtual_fitting_sessions`
   - Tracks virtual fitting sessions
   - Stores processing status
   - Indexed by session_id and user_id

**Database Maintenance:**
- Automatic cleanup of old sessions (30+ days)
- Index optimization on activation
- Transaction logging for credit changes
- Foreign key relationships maintained

**Manual Database Operations:**

```sql
-- View user credits
SELECT * FROM wp_virtual_fitting_credits WHERE user_id = 1;

-- View recent sessions
SELECT * FROM wp_virtual_fitting_sessions 
WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY created_at DESC;

-- View analytics summary
SELECT DATE(created_at) as date, COUNT(*) as fittings
FROM wp_virtual_fitting_sessions
WHERE status = 'completed'
GROUP BY DATE(created_at)
ORDER BY date DESC;
```


### Custom Endpoints

**WordPress REST API Endpoints:**

The plugin registers custom REST API endpoints for AJAX operations:

- `ai_virtual_fitting_upload` - Image upload
- `ai_virtual_fitting_process` - Process virtual fitting
- `ai_virtual_fitting_download` - Download result
- `ai_virtual_fitting_get_products` - Get product list
- `ai_virtual_fitting_check_credits` - Check credit balance
- `ai_virtual_fitting_add_credits_to_cart` - Add credits to cart
- `ai_virtual_fitting_refresh_credits` - Refresh credit display

**Custom Endpoint Configuration:**

Endpoints are defined in `includes/class-plugin-config.php`:

```php
const AJAX_UPLOAD_IMAGE = 'ai_virtual_fitting_upload';
const AJAX_PROCESS_FITTING = 'ai_virtual_fitting_process';
const AJAX_DOWNLOAD_RESULT = 'ai_virtual_fitting_download';
```

**Endpoint Security:**
- Nonce verification required
- User authentication checked
- Rate limiting applied
- Input sanitization enforced

### Developer Options

**Debug Mode:**

Enable WordPress debug mode for detailed logging:

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
```

**Development Constants:**

```php
// Enable verbose logging
define('AI_VIRTUAL_FITTING_DEBUG', true);

// Disable rate limiting (testing only)
define('AI_VIRTUAL_FITTING_NO_RATE_LIMIT', true);

// Use test API key
define('AI_VIRTUAL_FITTING_TEST_MODE', true);
```


**WordPress Hooks:**

The plugin provides hooks for customization:

```php
// Modify AI prompt before processing
add_filter('ai_virtual_fitting_prompt', function($prompt, $customer_image, $product_images) {
    // Customize prompt
    return $prompt;
}, 10, 3);

// Modify credit cost per fitting
add_filter('ai_virtual_fitting_credit_cost', function($cost) {
    return 2; // Charge 2 credits instead of 1
});

// After successful fitting
add_action('ai_virtual_fitting_completed', function($user_id, $result) {
    // Custom logic after fitting
}, 10, 2);

// Before credit deduction
add_action('ai_virtual_fitting_before_deduct_credit', function($user_id) {
    // Custom logic before deduction
});
```

**User Role Configuration:**

**Setting:** `ai_virtual_fitting_allowed_user_roles`  
**Type:** Array  
**Default:** `['customer', 'subscriber', 'administrator']`  
**Location:** Settings → AI Virtual Fitting → Advanced Settings

**Description:**  
Control which WordPress user roles can access virtual fitting features.

**Available Roles:**
- Administrator
- Editor
- Author
- Contributor
- Subscriber
- Customer (WooCommerce)
- Shop Manager (WooCommerce)

**Recommendations:**
- Always include Administrator
- Include Customer for e-commerce sites
- Include Subscriber for membership sites
- Exclude Editor/Author unless needed


**Login Requirements:**

**Setting:** `ai_virtual_fitting_require_login`  
**Type:** Boolean  
**Default:** `true`  
**Location:** Settings → AI Virtual Fitting → Advanced Settings

**Description:**  
Require users to be logged in to use virtual fitting features.

**Options:**
- **Enabled (Recommended):** Only logged-in users can access
  - Better credit tracking
  - User accountability
  - Prevents abuse
  - Enables personalization

- **Disabled:** Allow guest access
  - Lower barrier to entry
  - More conversions
  - Requires IP-based rate limiting
  - Limited credit tracking

**Email Notifications:**

**Customer Notifications:**  
**Setting:** `ai_virtual_fitting_enable_email_notifications`  
**Type:** Boolean  
**Default:** `true`

**Admin Notifications:**  
**Setting:** `ai_virtual_fitting_admin_email_notifications`  
**Type:** Boolean  
**Default:** `false`

**Notification Types:**
- Credit purchase confirmation (customer)
- Credit added to account (customer)
- API errors (admin)
- High usage alerts (admin)
- System issues (admin)

---

## Environment-Specific Configuration

Different environments (development, staging, production) may require different configurations.


### Development Settings

**Recommended Configuration:**

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
```

**Plugin Settings:**
- Enable Logging: ✓ Yes
- Enable Analytics: ✓ Yes
- Initial Credits: 10 (generous for testing)
- API Retry Attempts: 1-2 (fail fast)
- API Timeout: 30 seconds (shorter for faster feedback)
- Require Login: ✗ No (easier testing)
- Rate Limiting: Disabled or high limits

**Development Tools:**
- Use test API key
- Enable verbose logging
- Monitor debug.log file
- Use browser developer tools
- Test with various image sizes and formats

**Local Environment:**
- Docker containers (recommended)
- Local WordPress installation
- phpMyAdmin for database access
- WP-CLI for command-line operations

### Staging Settings

**Recommended Configuration:**

**Plugin Settings:**
- Enable Logging: ✓ Yes
- Enable Analytics: ✓ Yes
- Initial Credits: 2 (production-like)
- API Retry Attempts: 3 (production-like)
- API Timeout: 60 seconds (production-like)
- Require Login: ✓ Yes
- Rate Limiting: Enabled (production-like)

**Purpose:**
- Test production configuration
- Validate API integration
- Test payment processing
- Verify email notifications
- Load testing
- Security testing

**Staging Best Practices:**
- Use separate API key from production
- Mirror production configuration
- Test with production-like data
- Validate all workflows
- Test error scenarios


### Production Settings

**Recommended Configuration:**

```php
// wp-config.php
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', false);
```

**Plugin Settings:**
- Enable Logging: ✓ Yes (initially), ✗ No (after stable)
- Enable Analytics: ✓ Yes
- Initial Credits: 2 (balanced)
- API Retry Attempts: 3 (reliable)
- API Timeout: 60 seconds (balanced)
- Require Login: ✓ Yes (recommended)
- Rate Limiting: Enabled (required)
- Max Image Size: 10 MB (balanced)

**Production Checklist:**

- [ ] Valid production API key configured
- [ ] API key encrypted and secure
- [ ] SSL/HTTPS enabled
- [ ] Rate limiting enabled
- [ ] Login required (recommended)
- [ ] Email notifications configured
- [ ] Backup system in place
- [ ] Monitoring enabled
- [ ] Error logging configured
- [ ] Cache system optimized
- [ ] Database indexed
- [ ] Security hardened

**Performance Optimization:**
- Enable object caching (Redis/Memcached)
- Use CDN for static assets
- Optimize database queries
- Enable GZIP compression
- Minimize plugin conflicts
- Regular cache cleanup

**Security Hardening:**
- Strong WordPress security keys
- Regular security updates
- Firewall rules configured
- Rate limiting enforced
- File permissions correct (644/755)
- Database access restricted


---

## Configuration Examples

### Example 1: Small Business Setup

**Scenario:** Small bridal shop with limited budget, testing the feature.

**Configuration:**
```
API Provider: Google AI Studio
Initial Credits: 3
Credits per Package: 10
Package Price: $5.00
Max Image Size: 5 MB
API Retry Attempts: 3
API Timeout: 60 seconds
Enable Logging: Yes
Require Login: Yes
```

**Rationale:**
- Generous initial credits for trial
- Small, affordable packages
- Conservative image size for bandwidth
- Standard reliability settings
- Logging enabled for troubleshooting

### Example 2: High-Volume E-commerce

**Scenario:** Large online retailer with high traffic and many customers.

**Configuration:**
```
API Provider: Google Cloud Vertex AI
Initial Credits: 2
Credits per Package: 50
Package Price: $20.00
Max Image Size: 10 MB
API Retry Attempts: 3
API Timeout: 90 seconds
Enable Logging: No (after stable)
Require Login: Yes
Rate Limiting: Enabled (strict)
Queue Processing: Enabled
Caching: Redis/Memcached
```

**Rationale:**
- Enterprise API for better control
- Standard initial credits
- Bulk packages for value
- Higher timeout for reliability
- Logging disabled for performance
- Strict rate limiting for protection
- Advanced caching for performance


### Example 3: Premium Service

**Scenario:** Luxury bridal boutique offering premium virtual fitting service.

**Configuration:**
```
API Provider: Google Cloud Vertex AI
Initial Credits: 5
Credits per Package: 20
Package Price: $25.00
Max Image Size: 20 MB
API Retry Attempts: 5
API Timeout: 120 seconds
Enable Logging: Yes
Require Login: Yes
Email Notifications: Yes (customer and admin)
```

**Rationale:**
- Generous initial credits for premium experience
- Higher pricing for premium service
- Large image size for maximum quality
- Extra retries for reliability
- Longer timeout for quality processing
- Full notifications for customer service

### Example 4: Development/Testing

**Scenario:** Developer testing and customizing the plugin.

**Configuration:**
```
API Provider: Google AI Studio
Initial Credits: 10
Credits per Package: 100
Package Price: $1.00
Max Image Size: 10 MB
API Retry Attempts: 1
API Timeout: 30 seconds
Enable Logging: Yes
Require Login: No
Rate Limiting: Disabled
Debug Mode: Enabled
```

**Rationale:**
- Many free credits for testing
- Cheap packages for testing purchases
- Fast failure for debugging
- Short timeout for quick feedback
- No login required for easy testing
- No rate limiting for rapid testing
- Full logging for debugging


### Best Practices

**General Recommendations:**

1. **Start Conservative**
   - Begin with default settings
   - Monitor performance and usage
   - Adjust based on actual data
   - Test changes in staging first

2. **Security First**
   - Always require login in production
   - Enable rate limiting
   - Use HTTPS/SSL
   - Keep WordPress and plugins updated
   - Regular security audits

3. **Monitor and Optimize**
   - Enable analytics initially
   - Monitor API usage and costs
   - Track conversion rates
   - Optimize based on metrics
   - Regular performance reviews

4. **User Experience**
   - Balance quality vs. speed
   - Provide clear error messages
   - Set realistic expectations
   - Offer adequate free credits
   - Competitive pricing

5. **Cost Management**
   - Monitor API costs
   - Set appropriate pricing
   - Consider bulk discounts
   - Track ROI
   - Optimize image processing

**Configuration Checklist:**

- [ ] API key configured and tested
- [ ] Credit pricing set appropriately
- [ ] Image size limits configured
- [ ] Security settings enabled
- [ ] Rate limiting configured
- [ ] Logging enabled (initially)
- [ ] Analytics enabled
- [ ] Email notifications configured
- [ ] User roles configured
- [ ] Backup system in place
- [ ] Monitoring enabled
- [ ] Documentation reviewed

---

## Support and Resources

**Documentation:**
- [Installation Guide](INSTALLATION.md)
- [User Guide](USER-GUIDE.md)
- [Admin Guide](ADMIN-GUIDE.md)
- [Developer Documentation](../DEVELOPER.md)
- [Troubleshooting Guide](TROUBLESHOOTING.md)

**Configuration Help:**
- Review default values in `includes/class-plugin-config.php`
- Check admin settings in `admin/class-admin-settings.php`
- Consult security settings in `includes/class-security-manager.php`

**Support Channels:**
- Plugin documentation
- WordPress support forums
- GitHub issues (if applicable)
- Email support

---

**Last Updated:** 2024-01-15  
**Version:** 1.0.0  
**Compatibility:** WordPress 5.0+, WooCommerce 5.0+, PHP 7.4+

