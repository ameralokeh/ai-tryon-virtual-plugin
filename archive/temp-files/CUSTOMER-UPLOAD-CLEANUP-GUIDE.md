# Customer Upload Cleanup Guide

## Overview

This guide explains how to automatically delete customer uploaded images older than 24 hours from the production server to maintain privacy and reduce storage usage.

## Manual Cleanup

### Run the Cleanup Script

```bash
./cleanup-old-customer-uploads.sh
```

The script will:
1. Connect to production server via FTP
2. List all customer uploaded images in `/wp-content/uploads/ai-virtual-fitting/temp/`
3. Check the timestamp in each filename
4. Delete files older than 24 hours
5. Show a summary of deleted vs kept files

### What Gets Deleted

Files matching pattern: `customer_{user_id}_{timestamp}_{uniqid}.{ext}`
- Only files with timestamps older than 24 hours
- Supports: `.jpg`, `.jpeg`, `.JPEG`, `.png`, `.PNG`

### What Gets Kept

- Files uploaded within the last 24 hours
- AI result images (different naming pattern)
- Product images (different naming pattern)

## Automated Cleanup (Cron Job)

### Option 1: Server-Side Cron (Recommended)

If you have SSH access to the production server, set up a cron job:

```bash
# Edit crontab
crontab -e

# Add this line to run cleanup every hour
0 * * * * /path/to/cleanup-old-customer-uploads.sh >> /var/log/customer-cleanup.log 2>&1

# Or run every 6 hours
0 */6 * * * /path/to/cleanup-old-customer-uploads.sh >> /var/log/customer-cleanup.log 2>&1

# Or run once daily at 3 AM
0 3 * * * /path/to/cleanup-old-customer-uploads.sh >> /var/log/customer-cleanup.log 2>&1
```

### Option 2: Local Machine Cron

If running from your local machine (requires machine to be on):

```bash
# Edit crontab
crontab -e

# Add this line to run cleanup every 6 hours
0 */6 * * * cd /path/to/wordpress/project && ./cleanup-old-customer-uploads.sh >> cleanup.log 2>&1
```

### Option 3: WordPress Cron (Plugin-Based)

Add this to your plugin to use WordPress's built-in cron:

```php
// In ai-virtual-fitting.php or a new cleanup class

// Schedule the cleanup event on plugin activation
register_activation_hook(__FILE__, 'ai_vf_schedule_cleanup');

function ai_vf_schedule_cleanup() {
    if (!wp_next_scheduled('ai_vf_cleanup_old_uploads')) {
        wp_schedule_event(time(), 'hourly', 'ai_vf_cleanup_old_uploads');
    }
}

// Hook the cleanup function
add_action('ai_vf_cleanup_old_uploads', 'ai_vf_cleanup_customer_uploads');

function ai_vf_cleanup_customer_uploads() {
    $upload_dir = wp_upload_dir();
    $temp_dir = $upload_dir['basedir'] . '/ai-virtual-fitting/temp/';
    
    if (!is_dir($temp_dir)) {
        return;
    }
    
    $cutoff_time = time() - (24 * 60 * 60); // 24 hours ago
    $files = glob($temp_dir . 'customer_*.*');
    
    $deleted_count = 0;
    
    foreach ($files as $file) {
        $filename = basename($file);
        
        // Extract timestamp from filename: customer_{user_id}_{timestamp}_{uniqid}.{ext}
        $parts = explode('_', $filename);
        if (count($parts) >= 3) {
            $timestamp = intval($parts[2]);
            
            if ($timestamp < $cutoff_time) {
                if (unlink($file)) {
                    $deleted_count++;
                    error_log("AI Virtual Fitting: Deleted old customer upload: $filename");
                }
            }
        }
    }
    
    if ($deleted_count > 0) {
        error_log("AI Virtual Fitting: Cleanup completed. Deleted $deleted_count old customer uploads.");
    }
}

// Unschedule on plugin deactivation
register_deactivation_hook(__FILE__, 'ai_vf_unschedule_cleanup');

function ai_vf_unschedule_cleanup() {
    wp_clear_scheduled_hook('ai_vf_cleanup_old_uploads');
}
```

## Monitoring

### Check Cleanup Logs

```bash
# If using cron with log file
tail -f /var/log/customer-cleanup.log

# Or local log
tail -f cleanup.log
```

### Verify Cleanup

```bash
# List remaining customer uploads on server
lftp -u aalokeh@bridesandtailor.com -p 21 ftp.bridesandtailor.com <<EOF
cd /bridesandtailor.com/public_html/wp-content/uploads/ai-virtual-fitting/temp
ls -lh customer_*
bye
EOF
```

## Privacy & Compliance

### Why 24 Hours?

- Gives customers time to download their results
- Reduces storage costs
- Maintains user privacy
- Complies with data minimization principles

### GDPR Considerations

- Customer uploads contain personal data (photos)
- Automatic deletion after 24 hours demonstrates data minimization
- Document this in your privacy policy
- Consider adding user notification about 24-hour retention

### Recommended Privacy Policy Text

```
Customer Photo Retention:
Photos you upload for virtual fitting are automatically deleted from our 
servers after 24 hours. We recommend downloading your virtual fitting 
results immediately after generation. After 24 hours, you will need to 
upload your photo again to generate new results.
```

## Troubleshooting

### Script Fails to Connect

- Verify FTP credentials
- Check network connectivity
- Ensure FTP port 21 is not blocked

### Files Not Being Deleted

- Check file permissions on server
- Verify timestamp extraction logic
- Run script manually to see detailed output

### Cron Job Not Running

```bash
# Check if cron service is running
sudo systemctl status cron

# View cron logs
grep CRON /var/log/syslog

# Test cron job manually
/path/to/cleanup-old-customer-uploads.sh
```

## Best Practices

1. **Test First**: Run the script manually before setting up automation
2. **Monitor Initially**: Check logs daily for the first week
3. **Backup Strategy**: Consider backing up files before deletion (if needed for support)
4. **User Communication**: Inform users about the 24-hour retention policy
5. **Adjust Timing**: If users need more time, adjust the cutoff (e.g., 48 hours)

## Alternative: Immediate Deletion

If you want to delete customer uploads immediately after AI processing completes, add this to `class-image-processor.php`:

```php
// After successful AI processing and result delivery
private function cleanup_customer_upload($customer_image_path) {
    if (file_exists($customer_image_path)) {
        unlink($customer_image_path);
        error_log("AI Virtual Fitting: Deleted customer upload after processing: " . basename($customer_image_path));
    }
}
```

## Summary

- **Manual**: Run `./cleanup-old-customer-uploads.sh` anytime
- **Automated**: Set up cron job or WordPress cron
- **Monitoring**: Check logs regularly
- **Privacy**: Document 24-hour retention in privacy policy
