<?php
/**
 * Emergency Fix Diagnostics Test
 * 
 * Run this file to verify the emergency stabilization is working correctly
 * 
 * Usage: php test-emergency-fix-diagnostics.php
 * Or access via browser: http://yoursite.com/wp-content/plugins/mas3/test-emergency-fix-diagnostics.php
 */

// Load WordPress
$wp_load_paths = [
    __DIR__ . '/../../../wp-load.php',
    __DIR__ . '/../../../../wp-load.php',
    __DIR__ . '/../../../../../wp-load.php',
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die('Error: Could not find wp-load.php. Please run this file from the WordPress plugin directory.');
}

// Load diagnostic class
require_once __DIR__ . '/includes/class-mas-v2-diagnostics.php';

// Create diagnostics instance
$diagnostics = new MAS_V2_Diagnostics();

// Check if HTML output is requested
if (php_sapi_name() !== 'cli' || isset($_GET['html'])) {
    // Output HTML report
    echo $diagnostics->get_html_report();
    exit;
}

// CLI output
echo "\n";
echo "========================================\n";
echo "MAS V2 EMERGENCY FIX DIAGNOSTIC REPORT\n";
echo "========================================\n";
echo "\n";

$results = $diagnostics->run_emergency_fix_verification();

echo "Generated: " . $results['timestamp'] . "\n\n";

echo "SUMMARY:\n";
echo "--------\n";
echo "Total Tests: " . $results['summary']['total'] . "\n";
echo "Passed:      " . $results['summary']['passed'] . "\n";
echo "Failed:      " . $results['summary']['failed'] . "\n";
echo "Warnings:    " . $results['summary']['warnings'] . "\n";
echo "\n";

echo "TEST RESULTS:\n";
echo "-------------\n\n";

foreach ($results['tests'] as $test) {
    $status_icon = [
        'pass' => '✓',
        'fail' => '✗',
        'warning' => '⚠'
    ];
    
    echo $status_icon[$test['status']] . " " . $test['name'] . "\n";
    echo "  Status: " . strtoupper($test['status']) . "\n";
    echo "  Message: " . $test['message'] . "\n";
    
    if (!empty($test['details'])) {
        echo "  Details:\n";
        foreach ($test['details'] as $detail) {
            echo "    - " . $detail . "\n";
        }
    }
    
    echo "\n";
}

echo "========================================\n";

if ($results['summary']['failed'] > 0) {
    echo "RESULT: FAILED - Emergency fix has issues\n";
    exit(1);
} elseif ($results['summary']['warnings'] > 0) {
    echo "RESULT: WARNING - Emergency fix working but with warnings\n";
    exit(0);
} else {
    echo "RESULT: SUCCESS - Emergency fix working correctly\n";
    exit(0);
}
