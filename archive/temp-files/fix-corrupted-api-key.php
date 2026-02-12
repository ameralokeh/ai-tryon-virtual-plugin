<?php
/**
 * Fix Corrupted API Key
 * This script clears the corrupted encrypted API key so you can re-enter it
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

echo "=== Fix Corrupted API Key ===\n\n";

// Check current state
$current_key = get_option('ai_virtual_fitting_google_ai_api_key', '');
echo "Current encrypted key length: " . strlen($current_key) . " bytes\n";

if (!empty($current_key)) {
    $decrypted = AI_Virtual_Fitting_Security_Manager::decrypt($current_key);
    if ($decrypted === false) {
        echo "Status: Cannot decrypt (corrupted)\n\n";
    } else {
        $decrypted_length = strlen($decrypted);
        echo "Decrypted length: " . $decrypted_length . " bytes\n";
        
        // Check if it's the corrupted masked value (120 bytes of bullets)
        if ($decrypted_length === 120 && preg_match('/^[•\x00-\x1F\x7F-\xFF]+$/u', $decrypted)) {
            echo "Status: CORRUPTED (contains encrypted masked placeholder)\n\n";
        } else if (preg_match('/^AIza[0-9A-Za-z_-]{35}$/', $decrypted)) {
            echo "Status: Valid API key found!\n";
            echo "First 10 chars: " . substr($decrypted, 0, 10) . "...\n\n";
            echo "✓ Your API key is fine. No fix needed.\n";
            exit(0);
        } else {
            echo "Status: Unknown format\n\n";
        }
    }
    
    // Clear the corrupted key
    echo "Clearing corrupted API key...\n";
    delete_option('ai_virtual_fitting_google_ai_api_key');
    echo "✓ Corrupted key cleared.\n\n";
    
    echo "NEXT STEPS:\n";
    echo "1. Go to WordPress Admin → Virtual Fitting → Settings\n";
    echo "2. Enter your real Google AI Studio API key (starts with AIza...)\n";
    echo "3. Click 'Save Changes'\n";
    echo "4. Click 'Test Connection' to verify\n";
    
} else {
    echo "Status: No API key found in database\n\n";
    echo "NEXT STEPS:\n";
    echo "1. Go to WordPress Admin → Virtual Fitting → Settings\n";
    echo "2. Enter your Google AI Studio API key\n";
    echo "3. Click 'Save Changes'\n";
}

echo "\n=== Complete ===\n";
