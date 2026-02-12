<?php
/**
 * Test API Key Decryption
 * Run this to test if the encrypted API key can be decrypted
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

echo "=== API Key Decryption Test ===\n\n";

// Get the encrypted key from database
$encrypted_key = get_option('ai_virtual_fitting_google_ai_api_key', '');

echo "1. Encrypted key exists: " . (!empty($encrypted_key) ? "YES" : "NO") . "\n";
echo "2. Encrypted key length: " . strlen($encrypted_key) . " bytes\n";
echo "3. First 50 chars: " . substr($encrypted_key, 0, 50) . "\n\n";

// Try to decrypt it
if (!empty($encrypted_key)) {
    $decrypted = AI_Virtual_Fitting_Security_Manager::decrypt($encrypted_key);
    
    if ($decrypted === false) {
        echo "❌ DECRYPTION FAILED\n";
        echo "   The key could not be decrypted.\n\n";
        
        // Check if it's already a plain key
        if (preg_match('/^AIza[0-9A-Za-z_-]{35}$/', $encrypted_key)) {
            echo "   Note: The stored value looks like an unencrypted API key.\n";
        } else {
            echo "   The stored value appears to be encrypted but decryption failed.\n";
            echo "   This could mean:\n";
            echo "   - WordPress AUTH_KEY or SECURE_AUTH_KEY changed\n";
            echo "   - Database corruption\n";
            echo "   - Encryption method mismatch\n";
        }
    } else {
        echo "✓ DECRYPTION SUCCESSFUL\n";
        echo "   Decrypted key length: " . strlen($decrypted) . " chars\n";
        echo "   Starts with 'AIza': " . (strpos($decrypted, 'AIza') === 0 ? "YES" : "NO") . "\n";
        echo "   First 10 chars: " . substr($decrypted, 0, 10) . "...\n";
        echo "   Last 5 chars: ..." . substr($decrypted, -5) . "\n";
    }
} else {
    echo "❌ NO API KEY FOUND IN DATABASE\n";
}

echo "\n=== Test Complete ===\n";
