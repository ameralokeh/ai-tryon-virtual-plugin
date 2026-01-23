# Security Fix Implementation Plan

## Overview
This document provides step-by-step fixes for all critical API key security vulnerabilities with comprehensive testing.

---

## Fix 1: Remove API Key from Test AJAX Request

### Problem
API key is sent from JavaScript to server, visible in browser DevTools Network tab.

### Solution
Test using the stored encrypted key instead of sending it from the frontend.

### Implementation

#### Step 1.1: Update JavaScript (admin-settings.js)

**Before:**
```javascript
testApiConnection: function(e) {
    e.preventDefault();
    
    var $button = $(this);
    var $result = $('#api-test-result');
    var apiKey = $('#google_ai_api_key').val().trim();  // ❌ Gets key from input
    
    if (!apiKey) {
        AIVirtualFittingAdmin.showApiResult('error', ai_virtual_fitting_admin.messages.api_error);
        return;
    }
    
    $.ajax({
        url: ai_virtual_fitting_admin.ajax_url,
        type: 'POST',
        data: {
            action: 'ai_virtual_fitting_test_api',
            nonce: ai_virtual_fitting_admin.nonce,
            api_key: apiKey  // ❌ Sends key in request
        },
```

**After:**
```javascript
testApiConnection: function(e) {
    e.preventDefault();
    
    var $button = $(this);
    var $result = $('#api-test-result');
    var $apiKeyInput = $('#google_ai_api_key');
    var hasUnsavedKey = $apiKeyInput.val().trim() !== '' && $apiKeyInput.data('original-value') !== $apiKeyInput.val();
    
    // Check if there's an unsaved key
    if (hasUnsavedKey) {
        $result.removeClass('success error loading').addClass('error').show()
               .html('⚠️ Please save your API key before testing.');
        return;
    }
    
    // Show loading state
    $button.prop('disabled', true).text(ai_virtual_fitting_admin.messages.testing_api);
    $result.removeClass('success error').addClass('loading').show()
           .html('<span class="spinner"></span>' + ai_virtual_fitting_admin.messages.testing_api);
    
    // Make AJAX request WITHOUT sending the key
    $.ajax({
        url: ai_virtual_fitting_admin.ajax_url,
        type: 'POST',
        data: {
            action: 'ai_virtual_fitting_test_api',
            nonce: ai_virtual_fitting_admin.nonce
            // ✅ No api_key parameter
        },
```

#### Step 1.2: Update PHP Handler (class-admin-settings.php)

**Before:**
```php
public function test_api_connection() {
    check_ajax_referer('ai_virtual_fitting_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_die(__('Insufficient permissions.', 'ai-virtual-fitting'));
    }
    
    $api_key = sanitize_text_field($_POST['api_key'] ?? '');  // ❌ Gets from POST
    
    if (empty($api_key)) {
        wp_send_json_error(__('API key is required.', 'ai-virtual-fitting'));
    }
    
    $image_processor = new AI_Virtual_Fitting_Image_Processor();
    $test_result = $image_processor->test_api_connection($api_key);
```

**After:**
```php
public function test_api_connection() {
    check_ajax_referer('ai_virtual_fitting_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Insufficient permissions.', 'ai-virtual-fitting')));
        return;
    }
    
    // ✅ Get stored encrypted key
    $encrypted_key = get_option('ai_virtual_fitting_google_ai_api_key', '');
    
    if (empty($encrypted_key)) {
        wp_send_json_error(array('message' => __('API key not configured. Please save your API key first.', 'ai-virtual-fitting')));
        return;
    }
    
    // ✅ Decrypt the key server-side
    $api_key = AI_Virtual_Fitting_Security_Manager::decrypt($encrypted_key);
    
    if ($api_key === false) {
        // Try unencrypted for backward compatibility
        $api_key = $encrypted_key;
    }
    
    if (empty($api_key)) {
        wp_send_json_error(array('message' => __('Failed to retrieve API key. Please re-save your settings.', 'ai-virtual-fitting')));
        return;
    }
    
    // Test the connection
    $image_processor = new AI_Virtual_Fitting_Image_Processor();
    $test_result = $image_processor->test_api_connection($api_key);
    
    if ($test_result['success']) {
        wp_send_json_success(array('message' => __('API connection successful!', 'ai-virtual-fitting')));
    } else {
        wp_send_json_error(array('message' => $test_result['message']));
    }
}
```

#### Step 1.3: Track Original Value (admin-settings.js init)

Add to the `init` function:
```javascript
init: function() {
    console.log('AI Virtual Fitting Admin: init() called');
    this.bindEvents();
    this.initSystemStatus();
    this.initTabSwitching();
    this.loadButtonStats();
    
    // ✅ Track original API key value for change detection
    var $apiKeyInput = $('#google_ai_api_key');
    if ($apiKeyInput.length) {
        $apiKeyInput.data('original-value', $apiKeyInput.val());
        
        // Update original value after successful save
        $(document).on('settings_saved', function() {
            $apiKeyInput.data('original-value', $apiKeyInput.val());
        });
    }
},
```

### Testing Plan for Fix 1

#### Test 1.1: Test with Saved Key
```
Steps:
1. Go to admin settings
2. Ensure API key is already saved
3. Click "Test API Connection"
4. Open DevTools → Network tab
5. Check the request payload

Expected:
✅ No api_key parameter in request
✅ Test succeeds with stored key
✅ Success message displayed
```

#### Test 1.2: Test with Unsaved Key
```
Steps:
1. Go to admin settings
2. Change API key in input field
3. Don't click Save
4. Click "Test API Connection"

Expected:
✅ Error message: "Please save your API key before testing"
✅ No AJAX request sent
✅ User prompted to save first
```

#### Test 1.3: Test with No Key Configured
```
Steps:
1. Delete API key from database
2. Go to admin settings
3. Click "Test API Connection"

Expected:
✅ Error message: "API key not configured"
✅ No crash or PHP errors
✅ User prompted to configure key
```

#### Test 1.4: Test with Invalid Key
```
Steps:
1. Save invalid API key
2. Click "Test API Connection"

Expected:
✅ Error message from Google AI API
✅ No key visible in Network tab
✅ Proper error handling
```

---

## Fix 2: Move API Key from URL to Headers

### Problem
API key is in URL query parameters, logged in server access logs.

### Solution
Use HTTP headers instead of query parameters for API authentication.

### Implementation

#### Step 2.1: Check Google AI Studio API Documentation

**Research Required:**
- Google AI Studio may require key in URL
- Check if header authentication is supported
- Alternative: Use POST body instead of GET with query params

#### Step 2.2: Update API Call Method (class-image-processor.php)

**Current Implementation:**
```php
$response = wp_remote_post(
    $this->get_gemini_image_endpoint() . '?key=' . $api_key,  // ❌ Key in URL
    array(
        'timeout' => 120,
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode($request_body),
    )
);
```

**Option A: If Headers Supported**
```php
$response = wp_remote_post(
    $this->get_gemini_image_endpoint(),  // ✅ No key in URL
    array(
        'timeout' => 120,
        'headers' => array(
            'Content-Type' => 'application/json',
            'x-goog-api-key' => $api_key,  // ✅ Key in header
        ),
        'body' => json_encode($request_body),
    )
);
```

**Option B: If Headers Not Supported (Workaround)**
```php
// Use POST body with key parameter instead
$request_body['key'] = $api_key;  // ✅ Key in encrypted POST body

$response = wp_remote_post(
    $this->get_gemini_image_endpoint(),  // ✅ No key in URL
    array(
        'timeout' => 120,
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode($request_body),  // ✅ Key in body (encrypted by HTTPS)
    )
);
```

**Option C: If Neither Works (Mitigation)**
```php
// At minimum, log sanitization
add_filter('http_request_args', function($args, $url) {
    // Don't log URLs containing API keys
    if (strpos($url, 'generativelanguage.googleapis.com') !== false) {
        // Disable logging for this request
        $args['_redact_log'] = true;
    }
    return $args;
}, 10, 2);
```

#### Step 2.3: Update All API Endpoints

Find all instances:
```bash
grep -r "?key=" ai-virtual-fitting/includes/
```

Update each one:
1. `get_gemini_text_endpoint()` calls
2. `get_gemini_image_endpoint()` calls  
3. Any other Google AI API calls

### Testing Plan for Fix 2

#### Test 2.1: Verify API Calls Work
```
Steps:
1. Upload image
2. Select product
3. Process virtual fitting
4. Check server logs

Expected:
✅ API calls succeed
✅ Images generated correctly
✅ No key in access logs
✅ No functional regression
```

#### Test 2.2: Check Server Logs
```
Steps:
1. Process a fitting
2. Check /var/log/apache2/access.log or nginx logs
3. Search for "generativelanguage.googleapis.com"

Expected:
✅ No API key visible in logs
✅ URLs don't contain ?key= parameter
✅ Only endpoint paths logged
```

#### Test 2.3: Test Error Handling
```
Steps:
1. Use invalid API key
2. Process fitting
3. Check error messages

Expected:
✅ Proper error handling
✅ No key in error messages
✅ User-friendly error display
```

---

## Fix 3: Mask API Key in Admin UI

### Problem
API key visible in HTML source and DOM, extractable by browser extensions.

### Solution
Show masked value, only accept new keys for updates.

### Implementation

#### Step 3.1: Update Admin Settings Page (admin-settings-page.php)

**Before:**
```php
$google_ai_api_key = get_option('ai_virtual_fitting_google_ai_api_key', '');
?>
<input type="password" 
       id="google_ai_api_key" 
       name="ai_virtual_fitting_google_ai_api_key" 
       value="<?php echo esc_attr($google_ai_api_key); ?>"  // ❌ Shows actual key
       class="regular-text" 
/>
```

**After:**
```php
$google_ai_api_key = get_option('ai_virtual_fitting_google_ai_api_key', '');
$has_key = !empty($google_ai_api_key);
$masked_value = $has_key ? str_repeat('•', 40) : '';  // ✅ Show dots
?>
<input type="password" 
       id="google_ai_api_key" 
       name="ai_virtual_fitting_google_ai_api_key" 
       value="<?php echo esc_attr($masked_value); ?>"  // ✅ Masked value
       placeholder="<?php echo $has_key ? __('Enter new key to update', 'ai-virtual-fitting') : __('Enter your API key', 'ai-virtual-fitting'); ?>"
       class="regular-text" 
       data-has-key="<?php echo $has_key ? '1' : '0'; ?>"
/>
<p class="description">
    <?php if ($has_key): ?>
        <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
        <?php _e('API key is configured and encrypted. Enter a new key to update it.', 'ai-virtual-fitting'); ?>
    <?php else: ?>
        <span class="dashicons dashicons-warning" style="color: #f56e28;"></span>
        <?php _e('No API key configured. Please enter your Google AI Studio API key.', 'ai-virtual-fitting'); ?>
    <?php endif; ?>
</p>
```

#### Step 3.2: Update Save Handler (class-admin-settings.php)

**Add to sanitize_api_key function:**
```php
public function sanitize_api_key($value) {
    $value = sanitize_text_field($value);
    
    // ✅ Check if value is the masked placeholder
    if (preg_match('/^•+$/', $value)) {
        // User didn't change the key, keep existing value
        return get_option('ai_virtual_fitting_google_ai_api_key', '');
    }
    
    // ✅ Check if value is empty
    if (empty($value)) {
        // Allow deletion if explicitly cleared
        return '';
    }
    
    // ✅ New key provided, encrypt it
    $encrypted = AI_Virtual_Fitting_Security_Manager::encrypt($value);
    
    if ($encrypted === false) {
        add_settings_error(
            'ai_virtual_fitting_google_ai_api_key',
            'encryption_failed',
            __('Failed to encrypt API key. Please try again.', 'ai-virtual-fitting')
        );
        return get_option('ai_virtual_fitting_google_ai_api_key', '');
    }
    
    return $encrypted;
}
```

### Testing Plan for Fix 3

#### Test 3.1: View Source Check
```
Steps:
1. Configure API key
2. Go to settings page
3. View page source (Ctrl+U)
4. Search for "google_ai_api_key"

Expected:
✅ Only dots (•••) visible in source
✅ No actual key in HTML
✅ Placeholder text shown
```

#### Test 3.2: Browser Extension Test
```
Steps:
1. Install browser extension that reads inputs
2. Go to settings page
3. Try to extract key value

Expected:
✅ Extension only sees masked value
✅ No actual key extractable
✅ DOM shows dots only
```

#### Test 3.3: Update Key Test
```
Steps:
1. Go to settings with existing key
2. See masked value (•••)
3. Enter new key
4. Save settings
5. Verify new key works

Expected:
✅ New key saved successfully
✅ Old key replaced
✅ Test API works with new key
```

#### Test 3.4: Keep Existing Key Test
```
Steps:
1. Go to settings with existing key
2. See masked value (•••)
3. Don't change the field
4. Save settings
5. Test API connection

Expected:
✅ Existing key preserved
✅ No key corruption
✅ API still works
```

---

## Fix 4: Remove Plaintext Fallback

### Problem
Backward compatibility allows unencrypted keys in database.

### Solution
Force encryption, provide migration path.

### Implementation

#### Step 4.1: Update get_api_key Method (class-image-processor.php)

**Before:**
```php
private function get_api_key() {
    $encrypted_key = AI_Virtual_Fitting_Core::get_option('google_ai_api_key');
    
    if (empty($encrypted_key)) {
        return false;
    }
    
    $decrypted = AI_Virtual_Fitting_Security_Manager::decrypt($encrypted_key);
    
    if ($decrypted === false) {
        // ❌ UNSAFE: Returns unencrypted key
        return $encrypted_key;
    }
    
    return $decrypted;
}
```

**After:**
```php
private function get_api_key() {
    $encrypted_key = AI_Virtual_Fitting_Core::get_option('google_ai_api_key');
    
    if (empty($encrypted_key)) {
        return false;
    }
    
    $decrypted = AI_Virtual_Fitting_Security_Manager::decrypt($encrypted_key);
    
    if ($decrypted === false) {
        // ✅ Check if it's an old unencrypted key
        if ($this->looks_like_valid_api_key($encrypted_key)) {
            // ✅ Auto-migrate: encrypt it now
            $this->migrate_unencrypted_key($encrypted_key);
            return $encrypted_key;  // Use it this time
        }
        
        // ✅ Invalid or corrupted key
        error_log('AI Virtual Fitting: Failed to decrypt API key. Please re-save your settings.');
        return false;
    }
    
    return $decrypted;
}

/**
 * Check if string looks like a valid Google AI API key
 */
private function looks_like_valid_api_key($key) {
    // Google AI keys start with "AIza" and are 39 characters
    return preg_match('/^AIza[0-9A-Za-z_-]{35}$/', $key);
}

/**
 * Migrate unencrypted key to encrypted storage
 */
private function migrate_unencrypted_key($plain_key) {
    $encrypted = AI_Virtual_Fitting_Security_Manager::encrypt($plain_key);
    
    if ($encrypted !== false) {
        update_option('ai_virtual_fitting_google_ai_api_key', $encrypted);
        error_log('AI Virtual Fitting: Successfully migrated unencrypted API key to encrypted storage.');
    } else {
        error_log('AI Virtual Fitting: Failed to migrate unencrypted API key. Manual re-save required.');
    }
}
```

### Testing Plan for Fix 4

#### Test 4.1: New Installation
```
Steps:
1. Fresh WordPress install
2. Install plugin
3. Configure API key
4. Check database

Expected:
✅ Key stored encrypted
✅ No plaintext in database
✅ Decryption works
```

#### Test 4.2: Migration from Old Version
```
Steps:
1. Manually insert plaintext key in database:
   UPDATE wp_options SET option_value = 'AIzaTestKey123...' 
   WHERE option_name = 'ai_virtual_fitting_google_ai_api_key';
2. Process a virtual fitting
3. Check database again

Expected:
✅ Key auto-migrated to encrypted
✅ Fitting works correctly
✅ Log shows migration message
```

#### Test 4.3: Corrupted Key Handling
```
Steps:
1. Insert invalid encrypted data in database
2. Try to process fitting
3. Check error handling

Expected:
✅ Graceful error message
✅ No crash or fatal error
✅ User prompted to re-configure
```

---

## Integration Testing

### Test Suite: Complete Security Fix Validation

#### Integration Test 1: End-to-End Workflow
```
Steps:
1. Fresh install
2. Configure API key (masked in UI)
3. Save settings
4. Test API (no key in network tab)
5. Process virtual fitting (no key in logs)
6. Check database (key encrypted)

Expected:
✅ All security fixes working
✅ No functional regression
✅ User experience maintained
```

#### Integration Test 2: Browser Security Audit
```
Tools:
- Chrome DevTools
- Burp Suite or similar proxy
- Browser extension tester

Steps:
1. Monitor all network traffic
2. Inspect all DOM elements
3. Check all AJAX requests
4. Review server logs

Expected:
✅ No API key in network traffic
✅ No API key in DOM
✅ No API key in logs
✅ All encrypted in transit (HTTPS)
```

#### Integration Test 3: Backward Compatibility
```
Steps:
1. Upgrade from version 1.0.7.8
2. Existing keys should work
3. Auto-migration should occur
4. No user intervention needed

Expected:
✅ Seamless upgrade
✅ Keys auto-migrated
✅ No data loss
✅ All features work
```

---

## Rollback Plan

If fixes cause issues:

1. **Immediate Rollback:**
   ```bash
   # Restore previous version
   git checkout v1.0.7.8
   ```

2. **Partial Rollback:**
   - Revert specific fix that's problematic
   - Keep other fixes in place
   - Document issue for future fix

3. **Database Rollback:**
   ```sql
   -- If keys got corrupted
   -- Restore from backup
   ```

---

## Deployment Checklist

- [ ] All code changes implemented
- [ ] All unit tests pass
- [ ] Integration tests pass
- [ ] Security audit completed
- [ ] Backward compatibility verified
- [ ] Documentation updated
- [ ] Changelog created
- [ ] Version bumped to 1.0.8.0
- [ ] Tested on staging environment
- [ ] Admin approval obtained
- [ ] Backup created before deployment
- [ ] Deployment to production
- [ ] Post-deployment verification
- [ ] Monitor for issues 24-48 hours

---

## Success Criteria

✅ **Security:**
- No API key visible in browser DevTools
- No API key in HTML source
- No API key in server logs
- All keys encrypted at rest

✅ **Functionality:**
- API test works correctly
- Virtual fitting processes successfully
- Settings save/load properly
- No PHP errors or warnings

✅ **User Experience:**
- Clear messaging about key status
- Intuitive key update process
- No confusion about masked values
- Proper error messages

✅ **Performance:**
- No performance degradation
- Encryption/decryption fast
- No additional database queries
- No memory issues
