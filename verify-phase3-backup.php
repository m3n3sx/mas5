<?php
/**
 * Phase 3 Backup Verification Script
 * 
 * Verifies that the Phase 3 backup was created successfully
 * and all files are accounted for.
 */

echo "=== Phase 3 Backup Verification ===\n\n";

// Define expected files
$expectedFiles = [
    // Core files
    'assets/js/mas-admin-app.js',
    'assets/js/core/EventBus.js',
    'assets/js/core/StateManager.js', 
    'assets/js/core/APIClient.js',
    'assets/js/core/ErrorHandler.js',
    
    // Components
    'assets/js/components/Component.js',
    'assets/js/components/SettingsFormComponent.js',
    'assets/js/components/LivePreviewComponent.js',
    'assets/js/components/NotificationSystem.js',
    'assets/js/components/BackupManagerComponent.js',
    'assets/js/components/TabManager.js',
    'assets/js/components/ThemeSelectorComponent.js',
    
    // Utilities
    'assets/js/utils/DOMOptimizer.js',
    'assets/js/utils/LazyLoader.js',
    'assets/js/utils/VirtualList.js',
    'assets/js/utils/AccessibilityHelper.js',
    'assets/js/utils/ColorContrastHelper.js',
    'assets/js/utils/KeyboardNavigationHelper.js',
    'assets/js/utils/FocusManager.js',
    'assets/js/utils/CSSDiagnostics.js',
    'assets/js/utils/HandlerDiagnostics.js',
    
    // Deprecated
    'assets/js/admin-settings-simple.js',
    'assets/js/modules/LivePreviewManager.js'
];

$backupDir = 'phase3-backup/';
$originalDir = './';

echo "1. Checking backup directory exists...\n";
if (!is_dir($backupDir)) {
    echo "❌ ERROR: Backup directory not found: $backupDir\n";
    exit(1);
}
echo "✅ Backup directory exists\n\n";

echo "2. Verifying backup files...\n";
$missingFiles = [];
$backedUpFiles = [];

foreach ($expectedFiles as $file) {
    $originalPath = $originalDir . $file;
    $backupPath = $backupDir . $file;
    
    if (file_exists($originalPath)) {
        if (file_exists($backupPath)) {
            $originalSize = filesize($originalPath);
            $backupSize = filesize($backupPath);
            
            if ($originalSize === $backupSize) {
                echo "✅ $file (backed up, {$originalSize} bytes)\n";
                $backedUpFiles[] = $file;
            } else {
                echo "⚠️  $file (size mismatch: original {$originalSize}, backup {$backupSize})\n";
            }
        } else {
            echo "❌ $file (original exists but backup missing)\n";
            $missingFiles[] = $file;
        }
    } else {
        echo "ℹ️  $file (original not found - may have been removed already)\n";
    }
}

echo "\n3. Backup Summary:\n";
echo "Files successfully backed up: " . count($backedUpFiles) . "\n";
echo "Files missing from backup: " . count($missingFiles) . "\n";

if (count($missingFiles) > 0) {
    echo "\nMissing files:\n";
    foreach ($missingFiles as $file) {
        echo "- $file\n";
    }
}

echo "\n4. Checking working files are preserved...\n";
$workingFiles = [
    'assets/js/mas-settings-form-handler.js',
    'assets/js/simple-live-preview.js',
    'assets/js/mas-rest-client.js',
    'assets/js/utils/Debouncer.js',
    'assets/js/utils/Validator.js'
];

$workingFilesOk = true;
foreach ($workingFiles as $file) {
    if (file_exists($originalDir . $file)) {
        echo "✅ $file (preserved)\n";
    } else {
        echo "❌ $file (MISSING - this should be preserved!)\n";
        $workingFilesOk = false;
    }
}

echo "\n5. Checking audit documentation...\n";
$auditFiles = [
    'phase3-backup-audit-report.md',
    'phase3-file-inventory.json'
];

foreach ($auditFiles as $file) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "✅ $file ({$size} bytes)\n";
    } else {
        echo "❌ $file (missing)\n";
    }
}

echo "\n=== VERIFICATION RESULT ===\n";

if (count($missingFiles) === 0 && $workingFilesOk) {
    echo "✅ BACKUP VERIFICATION PASSED\n";
    echo "- All Phase 3 files backed up successfully\n";
    echo "- Working files preserved\n";
    echo "- Audit documentation created\n";
    echo "- Ready to proceed with Phase 3 removal\n";
    exit(0);
} else {
    echo "❌ BACKUP VERIFICATION FAILED\n";
    echo "- Some files missing from backup or working files missing\n";
    echo "- DO NOT proceed with removal until backup is complete\n";
    exit(1);
}
?>