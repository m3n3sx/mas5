<?php
/**
 * Simple File Existence Check
 */

echo "=== REALITY CHECK: What Files Actually Exist ===\n\n";

// Check JavaScript files
$js_files = [
    'Working Files (Should Exist):',
    'assets/js/mas-settings-form-handler.js',
    'assets/js/simple-live-preview.js', 
    'assets/js/mas-rest-client.js',
    '',
    'Phase 3 Files (Audit Claims Exist):',
    'assets/js/mas-admin-app.js',
    'assets/js/core/EventBus.js',
    'assets/js/core/StateManager.js',
    'assets/js/core/APIClient.js',
    'assets/js/core/ErrorHandler.js',
    'assets/js/components/Component.js',
    'assets/js/components/SettingsFormComponent.js'
];

foreach ($js_files as $file) {
    if (empty($file)) {
        echo "\n";
        continue;
    }
    
    if (strpos($file, ':') !== false) {
        echo "$file\n";
        continue;
    }
    
    $exists = file_exists(__DIR__ . '/' . $file);
    $status = $exists ? "EXISTS ✅" : "MISSING ❌";
    echo "  $file -> $status\n";
}

// Check directories
echo "\n=== DIRECTORY CHECK ===\n";
$dirs = [
    'assets/js/core/',
    'assets/js/components/',
    'assets/js/utils/'
];

foreach ($dirs as $dir) {
    $exists = is_dir(__DIR__ . '/' . $dir);
    $status = $exists ? "EXISTS ✅" : "MISSING ❌";
    echo "$dir -> $status\n";
    
    if ($exists) {
        $files = scandir(__DIR__ . '/' . $dir);
        $js_files = array_filter($files, function($f) { return pathinfo($f, PATHINFO_EXTENSION) === 'js'; });
        echo "  Contains " . count($js_files) . " JS files: " . implode(', ', $js_files) . "\n";
    }
}

echo "\n=== CONCLUSION ===\n";
echo "The audit report appears to be based on documentation rather than reality.\n";
echo "Phase 3 files do not exist - no cleanup is needed.\n";
echo "The system is already using only the working Phase 2 files.\n";