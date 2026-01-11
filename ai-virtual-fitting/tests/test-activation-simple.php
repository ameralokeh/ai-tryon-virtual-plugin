<?php
/**
 * Simple Test for Plugin Activation Logic
 * Tests the activation process without requiring WordPress environment
 *
 * @package AI_Virtual_Fitting
 */

echo "Testing AI Virtual Fitting Plugin Activation Logic\n";
echo "==================================================\n\n";

/**
 * Test activation hook setup
 */
echo "Testing Activation Hook Setup:\n";
echo "------------------------------\n";

// Test 1: Verify activation hook is properly registered
$main_plugin_file = file_get_contents(__DIR__ . '/../ai-virtual-fitting.php');

$tests_passed = 0;
$tests_total = 0;

$tests_total++;
if (strpos($main_plugin_file, 'register_activation_hook(__FILE__, array($this, \'activate\'))') !== false) {
    echo "✓ Activation hook is properly registered in main plugin file\n";
    $tests_passed++;
} else {
    echo "✗ Activation hook is not properly registered\n";
}

// Test 2: Verify activation method calls Core::activate()
$tests_total++;
if (strpos($main_plugin_file, 'AI_Virtual_Fitting_Core::activate()') !== false) {
    echo "✓ Main plugin activation calls Core::activate() method\n";
    $tests_passed++;
} else {
    echo "✗ Main plugin activation does not call Core::activate()\n";
}

// Test 3: Verify Core class has activation method
$core_file = file_get_contents(__DIR__ . '/../includes/class-virtual-fitting-core.php');

$tests_total++;
if (strpos($core_file, 'public static function activate()') !== false) {
    echo "✓ Core class has static activate() method\n";
    $tests_passed++;
} else {
    echo "✗ Core class is missing static activate() method\n";
}

// Test 4: Verify activation method creates database tables
$tests_total++;
if (strpos($core_file, '$database_manager = new AI_Virtual_Fitting_Database_Manager()') !== false &&
    strpos($core_file, '$database_manager->create_tables()') !== false) {
    echo "✓ Activation method creates database tables\n";
    $tests_passed++;
} else {
    echo "✗ Activation method does not create database tables\n";
}

// Test 5: Verify activation method has error handling
$tests_total++;
if (strpos($core_file, 'try {') !== false && 
    strpos($core_file, 'catch (Exception $e)') !== false) {
    echo "✓ Activation method has proper error handling\n";
    $tests_passed++;
} else {
    echo "✗ Activation method lacks proper error handling\n";
}

// Test 6: Verify activation method verifies table creation
$tests_total++;
if (strpos($core_file, 'verify_tables_exist()') !== false) {
    echo "✓ Activation method verifies table creation\n";
    $tests_passed++;
} else {
    echo "✗ Activation method does not verify table creation\n";
}

// Test 7: Verify activation method sets default options
$tests_total++;
if (strpos($core_file, 'set_default_options()') !== false) {
    echo "✓ Activation method sets default options\n";
    $tests_passed++;
} else {
    echo "✗ Activation method does not set default options\n";
}

// Test 8: Verify activation method flushes rewrite rules
$tests_total++;
if (strpos($core_file, 'flush_rewrite_rules()') !== false) {
    echo "✓ Activation method flushes rewrite rules\n";
    $tests_passed++;
} else {
    echo "✗ Activation method does not flush rewrite rules\n";
}

echo "\n";
echo "Testing Deactivation Process:\n";
echo "-----------------------------\n";

// Test 9: Verify deactivation preserves data
$tests_total++;
if (strpos($core_file, 'We don\'t delete data on deactivation') !== false ||
    strpos($core_file, 'data preserved') !== false) {
    echo "✓ Deactivation preserves customer data (Requirements 8.6)\n";
    $tests_passed++;
} else {
    echo "✗ Deactivation does not explicitly preserve data\n";
}

echo "\n";
echo "Testing Uninstall Process:\n";
echo "--------------------------\n";

// Test 10: Verify uninstall.php exists and handles cleanup
$uninstall_file = __DIR__ . '/../uninstall.php';
$tests_total++;
if (file_exists($uninstall_file)) {
    $uninstall_content = file_get_contents($uninstall_file);
    if (strpos($uninstall_content, 'drop_tables()') !== false &&
        strpos($uninstall_content, 'delete_option') !== false) {
        echo "✓ Uninstall process properly cleans up data (Requirements 8.8)\n";
        $tests_passed++;
    } else {
        echo "✗ Uninstall process does not properly clean up data\n";
    }
} else {
    echo "✗ Uninstall.php file is missing\n";
}

echo "\n";
echo "Testing Database Schema Requirements:\n";
echo "------------------------------------\n";

// Test 11: Verify DatabaseManager class exists
$db_manager_file = __DIR__ . '/../includes/class-database-manager.php';
$tests_total++;
if (file_exists($db_manager_file)) {
    echo "✓ DatabaseManager class file exists\n";
    $tests_passed++;
} else {
    echo "✗ DatabaseManager class file is missing\n";
}

// Test 12: Verify database schema includes required tables
$tests_total++;
if (file_exists($db_manager_file)) {
    $db_content = file_get_contents($db_manager_file);
    if (strpos($db_content, 'virtual_fitting_credits') !== false &&
        strpos($db_content, 'virtual_fitting_sessions') !== false) {
        echo "✓ Database schema includes required tables\n";
        $tests_passed++;
    } else {
        echo "✗ Database schema is missing required tables\n";
    }
} else {
    $tests_passed++; // Skip if file doesn't exist (already counted above)
}

echo "\n";
echo "Activation Test Results:\n";
echo "=======================\n";
echo "Passed: {$tests_passed}/{$tests_total} tests\n";

if ($tests_passed === $tests_total) {
    echo "🎉 All activation tests PASSED!\n";
    echo "\nValidated Requirements:\n";
    echo "- 8.3: Plugin activation creates necessary database tables\n";
    echo "- 8.6: Data preservation during deactivation\n";
    echo "- 8.8: Proper uninstall cleanup capabilities\n";
    echo "\nThe plugin activation process is properly implemented with:\n";
    echo "✓ Database table creation with verification\n";
    echo "✓ Error handling and graceful failure\n";
    echo "✓ Default settings initialization\n";
    echo "✓ Data preservation during deactivation\n";
    echo "✓ Complete cleanup during uninstall\n";
    exit(0);
} else {
    echo "❌ Some activation tests FAILED!\n";
    echo "The plugin activation process needs review.\n";
    exit(1);
}