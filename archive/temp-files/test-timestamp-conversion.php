<?php
/**
 * Test timestamp conversion for Activity Log
 * Simulates WordPress mysql2date() function
 */

// Simulate WordPress functions
function get_option($option) {
    $options = [
        'date_format' => 'F j, Y',
        'time_format' => 'g:i a',
        'timezone_string' => 'America/New_York'
    ];
    return $options[$option] ?? '';
}

function mysql2date($format, $date, $translate = true) {
    if (empty($date)) {
        return false;
    }

    $datetime = date_create($date, timezone_open('UTC'));
    
    if (false === $datetime) {
        return false;
    }

    // Convert to site timezone
    $timezone_string = get_option('timezone_string');
    if ($timezone_string) {
        $timezone = timezone_open($timezone_string);
        if ($timezone) {
            date_timezone_set($datetime, $timezone);
        }
    }

    return date_format($datetime, $format);
}

// Test cases
echo "Testing timestamp conversion:\n";
echo str_repeat("=", 50) . "\n\n";

$test_timestamps = [
    '2026-02-11 22:40:00',  // Recent timestamp
    '2026-02-01 04:16:00',  // Earlier this month
    '2026-01-31 23:03:00',  // Last month
    '0000-00-00 00:00:00',  // Invalid timestamp
    '',                      // Empty timestamp
];

foreach ($test_timestamps as $timestamp) {
    echo "Input: '$timestamp'\n";
    
    if (empty($timestamp) || $timestamp === '0000-00-00 00:00:00') {
        echo "Output: — (dash)\n";
    } else {
        $format = get_option('date_format') . ' ' . get_option('time_format');
        $result = mysql2date($format, $timestamp);
        echo "Output: $result\n";
    }
    
    echo "\n";
}

echo str_repeat("=", 50) . "\n";
echo "Test complete!\n";
?>
