<?php
/**
 * Verification script for Task 2: Remove Phase 3 core architecture files
 * 
 * This script verifies that all Phase 3 core files have been successfully removed:
 * - assets/js/core/ directory files (EventBus, StateManager, APIClient, ErrorHandler)
 * - assets/js/mas-admin-app.js main application file
 */

echo "=== TASK 2 VERIFICATION: Phase 3 Core Architecture Removal ===\n\n";

$phase3_files_to_check = [
    'assets/js/core/EventBus.js',
    'assets/js/core/StateManager.js', 
    'assets/js/core/APIClient.js',
    'assets/js/core/ErrorHandler.js',
    'assets/js/mas-admin-app.js'
];

$all_removed = true;
$results = [];

foreach ($phase3_files_to_check as $file) {
    $exists = file_exists($file);
    $results[] = [
        'file' => $file,
        'exists' => $exists,
        'status' => $exists ? 'FAIL - Still exists' : 'PASS - Removed'
    ];
    
    if ($exists) {
        $all_removed = false;
    }
}

// Display results
foreach ($results as $result) {
    printf("%-40s: %s\n", $result['file'], $result['status']);
}

echo "\n=== CORE DIRECTORY CHECK ===\n";
$core_dir = 'assets/js/core/';
if (is_dir($core_dir)) {
    $core_files = array_diff(scandir($core_dir), ['.', '..']);
    if (empty($core_files)) {
        echo "✓ Core directory exists but is empty (ready for removal)\n";
    } else {
        echo "✗ Core directory still contains files:\n";
        foreach ($core_files as $file) {
            echo "  - $file\n";
        }
        $all_removed = false;
    }
} else {
    echo "✓ Core directory has been completely removed\n";
}

echo "\n=== REMAINING WORKING FILES CHECK ===\n";
$working_files = [
    'assets/js/mas-settings-form-handler.js',
    'assets/js/simple-live-preview.js'
];

foreach ($working_files as $file) {
    $exists = file_exists($file);
    printf("%-40s: %s\n", $file, $exists ? '✓ Present' : '✗ Missing');
}

echo "\n=== FINAL RESULT ===\n";
if ($all_removed) {
    echo "✅ SUCCESS: All Phase 3 core architecture files have been removed\n";
    echo "✅ Task 2 requirements (1.1, 1.2) satisfied\n";
    echo "\nNext steps:\n";
    echo "- Proceed to Task 3: Remove Phase 3 component system files\n";
    echo "- Update WordPress enqueue scripts to remove references\n";
} else {
    echo "❌ FAILURE: Some Phase 3 files still exist\n";
    echo "Please review the results above and remove remaining files\n";
}

echo "\n" . str_repeat("=", 60) . "\n";