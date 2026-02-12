<?php
/**
 * Test What Was Actually Encrypted
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

echo "=== Testing What Was Encrypted ===\n\n";

// The masked value that gets submitted
$masked_value = str_repeat('•', 40);

echo "1. Masked value: " . $masked_value . "\n";
echo "2. Masked value length: " . strlen($masked_value) . " bytes\n";
echo "3. Masked value matches regex: " . (preg_match('/^[•*]+$/', $masked_value) ? "YES" : "NO") . "\n";
echo "4. Masked value matches 40 non-word chars: " . (strlen($masked_value) === 40 && preg_match('/^[^\w\s]+$/', $masked_value) ? "YES" : "NO") . "\n\n";

// What if the masked value was encrypted?
$encrypted_masked = AI_Virtual_Fitting_Security_Manager::encrypt($masked_value);
echo "5. If masked value was encrypted:\n";
echo "   Length: " . strlen($encrypted_masked) . " bytes\n";
echo "   First 50 chars: " . substr($encrypted_masked, 0, 50) . "\n\n";

// Get what's actually in the database
$db_value = get_option('ai_virtual_fitting_google_ai_api_key', '');
echo "6. Database value:\n";
echo "   Length: " . strlen($db_value) . " bytes\n";
echo "   First 50 chars: " . substr($db_value, 0, 50) . "\n\n";

// Compare
if ($encrypted_masked === $db_value) {
    echo "❌ PROBLEM FOUND: The masked value was encrypted and saved!\n";
    echo "   This means the sanitize_api_key function failed to detect the mask.\n";
} else {
    echo "✓ Database value is different from encrypted mask.\n";
    echo "   The issue is elsewhere.\n";
}

echo "\n=== Test Complete ===\n";
