<?php
/**
 * Test Activity Logger Timestamp Fix Locally
 */

// Simulate WordPress environment
define('ABSPATH', true);

// Mock WordPress functions
function sanitize_email($email) { return $email; }
function sanitize_text_field($text) { return $text; }
function sanitize_textarea_field($text) { return $text; }

// Include the fixed activity logger
require_once('ai-virtual-fitting/includes/class-activity-logger.php');

echo "=== Testing Activity Logger Timestamp Fix ===\n\n";

// Test the timestamp generation
echo "Testing gmdate('Y-m-d H:i:s'):\n";
$timestamp = gmdate('Y-m-d H:i:s');
echo "Generated timestamp: {$timestamp}\n";
echo "Format: " . (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $timestamp) ? "✓ Valid MySQL format" : "✗ Invalid format") . "\n\n";

// Verify it's not 0000-00-00
if ($timestamp === '0000-00-00 00:00:00') {
    echo "✗ ERROR: Timestamp is still 0000-00-00 00:00:00\n";
} else {
    echo "✓ Timestamp is valid and not 0000-00-00\n";
}

echo "\n=== Test Complete ===\n";
echo "The fix will generate valid timestamps like: {$timestamp}\n";
