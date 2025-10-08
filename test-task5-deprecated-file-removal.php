<?php
/**
 * Task 5 Verification: Remove deprecated and conflicting JavaScript files
 * 
 * This test verifies that all deprecated and conflicting JavaScript files
 * have been successfully removed from the system.
 */

echo "<h2>🧹 Task 5: Deprecated File Removal Verification</h2>\n";

// Files that should be removed
$removedFiles = [
    'assets/js/admin-settings-simple.js' => 'Deprecated simple settings handler',
    'assets/js/modules/LivePreviewManager.js' => 'Complex, broken live preview manager',
    'assets/js/modules/ModernAdminApp.js' => 'Broken Phase 3 main application',
    'assets/js/modules/SettingsManager.js' => 'Deprecated settings manager (conflicts with mas-settings-form-handler.js)'
];

// Files that should remain (working files)
$remainingFiles = [
    'assets/js/mas-settings-form-handler.js' => 'Primary form handler (Phase 2 fallback)',
    'assets/js/simple-live-preview.js' => 'Simple live preview system'
];

echo "<h3>✅ Verification Results</h3>\n";

$allPassed = true;

// Check that removed files are gone
echo "<h4>🗑️ Removed Files (should not exist):</h4>\n";
foreach ($removedFiles as $file => $description) {
    $exists = file_exists($file);
    $status = $exists ? '❌ STILL EXISTS' : '✅ REMOVED';
    echo "<p><strong>$file</strong> - $description<br>Status: $status</p>\n";
    
    if ($exists) {
        $allPassed = false;
        echo "<p style='color: red;'>⚠️ ERROR: This file should have been removed!</p>\n";
    }
}

// Check that working files remain
echo "<h4>📁 Working Files (should exist):</h4>\n";
foreach ($remainingFiles as $file => $description) {
    $exists = file_exists($file);
    $status = $exists ? '✅ EXISTS' : '❌ MISSING';
    echo "<p><strong>$file</strong> - $description<br>Status: $status</p>\n";
    
    if (!$exists) {
        $allPassed = false;
        echo "<p style='color: red;'>⚠️ ERROR: This working file is missing!</p>\n";
    }
}

// Check modules directory
echo "<h4>📂 Modules Directory Status:</h4>\n";
$modulesDir = 'assets/js/modules/';
if (is_dir($modulesDir)) {
    $moduleFiles = array_diff(scandir($modulesDir), ['.', '..']);
    echo "<p>Remaining modules: " . count($moduleFiles) . "</p>\n";
    
    foreach ($moduleFiles as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'js') {
            echo "<p>• $file</p>\n";
        }
    }
} else {
    echo "<p>❌ Modules directory not found</p>\n";
    $allPassed = false;
}

// Final result
echo "<h3>🎯 Final Result</h3>\n";
if ($allPassed) {
    echo "<p style='color: green; font-weight: bold;'>✅ SUCCESS: All deprecated and conflicting files have been successfully removed!</p>\n";
    echo "<p>✅ Requirements 4.1 and 4.2 satisfied:</p>\n";
    echo "<ul>\n";
    echo "<li>✅ admin-settings-simple.js (deprecated) - REMOVED</li>\n";
    echo "<li>✅ LivePreviewManager.js (complex, broken) - REMOVED</li>\n";
    echo "<li>✅ ModernAdminApp.js (broken Phase 3 system) - REMOVED</li>\n";
    echo "<li>✅ SettingsManager.js (conflicting with mas-settings-form-handler.js) - REMOVED</li>\n";
    echo "</ul>\n";
    echo "<p>🎉 Task 5 completed successfully!</p>\n";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ FAILED: Some files were not properly removed or working files are missing!</p>\n";
}

// Additional checks
echo "<h3>🔍 Additional Verification</h3>\n";

// Check for any remaining deprecated references
$phpFiles = glob('*.php');
$deprecatedReferences = 0;

foreach ($phpFiles as $phpFile) {
    $content = file_get_contents($phpFile);
    if (strpos($content, 'admin-settings-simple.js') !== false) {
        echo "<p>⚠️ Found reference to admin-settings-simple.js in: $phpFile</p>\n";
        $deprecatedReferences++;
    }
    if (strpos($content, 'LivePreviewManager.js') !== false) {
        echo "<p>⚠️ Found reference to LivePreviewManager.js in: $phpFile</p>\n";
        $deprecatedReferences++;
    }
    if (strpos($content, 'ModernAdminApp.js') !== false) {
        echo "<p>⚠️ Found reference to ModernAdminApp.js in: $phpFile</p>\n";
        $deprecatedReferences++;
    }
    if (strpos($content, 'SettingsManager.js') !== false) {
        echo "<p>⚠️ Found reference to SettingsManager.js in: $phpFile</p>\n";
        $deprecatedReferences++;
    }
}

if ($deprecatedReferences === 0) {
    echo "<p>✅ No deprecated file references found in PHP files</p>\n";
} else {
    echo "<p style='color: orange;'>⚠️ Found $deprecatedReferences references to removed files - these should be updated in Task 6</p>\n";
}

echo "<hr>\n";
echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>\n";
?>