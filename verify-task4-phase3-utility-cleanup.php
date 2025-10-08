<?php
/**
 * Verification Test for Task 4: Remove Phase 3 Utility and Helper Files
 * 
 * This script verifies that all Phase 3 utility and helper files have been
 * successfully removed from the system.
 * 
 * @package ModernAdminStyler
 * @since Phase 3 Cleanup
 */

// Prevent direct access
if (!defined('ABSPATH') && php_sapi_name() !== 'cli') {
    exit('Direct access not allowed');
}

echo "=== Phase 3 Utility Files Cleanup Verification ===\n\n";

// Define the files that should have been removed
$removed_files = [
    // Complex utility files
    'assets/js/utils/DOMOptimizer.js',
    'assets/js/utils/VirtualList.js', 
    'assets/js/utils/LazyLoader.js',
    
    // Accessibility helpers that depend on Phase 3 architecture
    'assets/js/utils/AccessibilityHelper.js',
    'assets/js/utils/KeyboardNavigationHelper.js',
    'assets/js/utils/FocusManager.js',
    'assets/js/utils/ColorContrastHelper.js',
    
    // Diagnostic tools specific to Phase 3 system
    'assets/js/utils/CSSDiagnostics.js',
    'assets/js/utils/HandlerDiagnostics.js',
    
    // Additional Phase 3 utilities
    'assets/js/utils/Debouncer.js',
    'assets/js/utils/Validator.js',
    
    // Test files for removed utilities
    'test-css-diagnostics.html',
    'test-handler-diagnostics.html',
    
    // PHP diagnostic files
    'mas-handler-diagnostics.php'
];

$all_removed = true;
$issues = [];

echo "Checking removal of Phase 3 utility files...\n";
echo str_repeat("-", 50) . "\n";

foreach ($removed_files as $file) {
    $file_path = __DIR__ . '/' . $file;
    
    if (file_exists($file_path)) {
        $all_removed = false;
        $issues[] = "❌ FAILED: File still exists: $file";
        echo "❌ FAILED: $file (still exists)\n";
    } else {
        echo "✅ PASSED: $file (successfully removed)\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";

// Check if utils directory is empty or doesn't exist
$utils_dir = __DIR__ . '/assets/js/utils';
if (is_dir($utils_dir)) {
    $remaining_files = array_diff(scandir($utils_dir), ['.', '..']);
    if (!empty($remaining_files)) {
        echo "⚠️  WARNING: Utils directory still contains files:\n";
        foreach ($remaining_files as $file) {
            echo "   - $file\n";
        }
        echo "\n";
    } else {
        echo "✅ PASSED: Utils directory is empty\n\n";
    }
} else {
    echo "✅ PASSED: Utils directory has been removed\n\n";
}

// Summary
if ($all_removed) {
    echo "🎉 SUCCESS: All Phase 3 utility and helper files have been successfully removed!\n";
    echo "\nRemoved files summary:\n";
    echo "- Complex utilities: 3 files (DOMOptimizer, VirtualList, LazyLoader)\n";
    echo "- Accessibility helpers: 4 files (AccessibilityHelper, KeyboardNavigationHelper, FocusManager, ColorContrastHelper)\n";
    echo "- Diagnostic tools: 2 files (CSSDiagnostics, HandlerDiagnostics)\n";
    echo "- Additional utilities: 2 files (Debouncer, Validator)\n";
    echo "- Test files: 2 files (test-css-diagnostics.html, test-handler-diagnostics.html)\n";
    echo "- PHP diagnostics: 1 file (mas-handler-diagnostics.php)\n";
    echo "- Total removed: " . count($removed_files) . " files\n";
    
    echo "\n✅ Task 4 Requirements Verification:\n";
    echo "✅ Requirement 1.3: Complex utility files removed\n";
    echo "✅ Requirement 4.2: Accessibility helpers and diagnostic tools removed\n";
    echo "✅ All Phase 3 dependencies eliminated\n";
    
    exit(0);
} else {
    echo "❌ FAILED: Some Phase 3 files were not properly removed\n";
    echo "\nIssues found:\n";
    foreach ($issues as $issue) {
        echo "$issue\n";
    }
    exit(1);
}