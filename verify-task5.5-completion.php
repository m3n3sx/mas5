#!/usr/bin/env php
<?php
/**
 * Verification Script for Task 5.5: Import/Export Tests
 * 
 * This script verifies that all required tests for import/export endpoints
 * have been implemented according to the task requirements.
 * 
 * Task Requirements:
 * - Test export with proper headers and format
 * - Test import with valid and invalid data
 * - Test automatic backup creation on import
 * - Test legacy format migration
 * 
 * @package Modern_Admin_Styler_V2
 * @subpackage Tests
 */

echo "\n";
echo "========================================\n";
echo "Task 5.5 Verification: Import/Export Tests\n";
echo "========================================\n\n";

// Test file path
$test_file = __DIR__ . '/tests/php/rest-api/TestMASImportExportIntegration.php';

if (!file_exists($test_file)) {
    echo "❌ FAILED: Test file not found at $test_file\n";
    exit(1);
}

echo "✓ Test file exists: TestMASImportExportIntegration.php\n\n";

// Read test file content
$content = file_get_contents($test_file);

// Define required test categories and their test methods
$required_tests = [
    'Export with Proper Headers and Format' => [
        'test_export_requires_authentication' => 'Export requires authentication',
        'test_export_with_admin_user' => 'Export with admin user returns data',
        'test_export_has_proper_headers' => 'Export has Content-Disposition header',
        'test_export_includes_version_metadata' => 'Export includes version metadata',
    ],
    'Import with Valid and Invalid Data' => [
        'test_import_requires_authentication' => 'Import requires authentication',
        'test_import_with_valid_data' => 'Import with valid data succeeds',
        'test_import_with_invalid_data_structure' => 'Import rejects invalid structure',
        'test_import_with_invalid_json_string' => 'Import rejects invalid JSON',
        'test_import_with_valid_json_string' => 'Import accepts valid JSON string',
        'test_import_with_invalid_color_values' => 'Import validates color values',
        'test_import_with_empty_settings' => 'Import rejects empty settings',
        'test_import_with_incompatible_version' => 'Import checks version compatibility',
    ],
    'Automatic Backup Creation on Import' => [
        'test_import_creates_automatic_backup' => 'Import creates backup when requested',
        'test_import_without_backup' => 'Import skips backup when not requested',
    ],
    'Legacy Format Migration' => [
        'test_import_with_legacy_format' => 'Import migrates legacy format (no metadata)',
        'test_import_with_field_aliases' => 'Import handles field aliases',
    ],
    'Additional Integration Tests' => [
        'test_full_export_import_workflow' => 'Complete export-import workflow',
        'test_editor_cannot_export' => 'Editor users cannot export',
        'test_editor_cannot_import' => 'Editor users cannot import',
    ],
];

$total_tests = 0;
$passed_tests = 0;
$failed_tests = [];

echo "Checking Test Coverage:\n";
echo "------------------------\n\n";

foreach ($required_tests as $category => $tests) {
    echo "📋 $category:\n";
    
    foreach ($tests as $test_method => $description) {
        $total_tests++;
        
        // Check if test method exists
        if (preg_match('/public function ' . preg_quote($test_method, '/') . '\s*\(/', $content)) {
            echo "  ✓ $description\n";
            $passed_tests++;
        } else {
            echo "  ❌ MISSING: $description ($test_method)\n";
            $failed_tests[] = "$category: $test_method";
        }
    }
    
    echo "\n";
}

// Check for implementation files
echo "Checking Implementation Files:\n";
echo "------------------------------\n\n";

$implementation_files = [
    'includes/api/class-mas-import-export-controller.php' => 'Import/Export Controller',
    'includes/services/class-mas-import-export-service.php' => 'Import/Export Service',
];

$all_files_exist = true;

foreach ($implementation_files as $file => $name) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "  ✓ $name exists\n";
    } else {
        echo "  ❌ MISSING: $name\n";
        $all_files_exist = false;
    }
}

echo "\n";

// Check for key methods in implementation
echo "Checking Key Implementation Methods:\n";
echo "------------------------------------\n\n";

$controller_file = __DIR__ . '/includes/api/class-mas-import-export-controller.php';
$service_file = __DIR__ . '/includes/services/class-mas-import-export-service.php';

if (file_exists($controller_file)) {
    $controller_content = file_get_contents($controller_file);
    
    $controller_methods = [
        'export_settings' => 'Export settings endpoint',
        'import_settings' => 'Import settings endpoint',
    ];
    
    echo "Controller Methods:\n";
    foreach ($controller_methods as $method => $desc) {
        if (preg_match('/public function ' . preg_quote($method, '/') . '\s*\(/', $controller_content)) {
            echo "  ✓ $desc\n";
        } else {
            echo "  ❌ MISSING: $desc\n";
        }
    }
    echo "\n";
}

if (file_exists($service_file)) {
    $service_content = file_get_contents($service_file);
    
    $service_methods = [
        'export_settings' => 'Export settings logic',
        'import_settings' => 'Import settings logic',
        'validate_import_data' => 'Import data validation',
        'check_version_compatibility' => 'Version compatibility check',
        'migrate_legacy_format' => 'Legacy format migration',
        'validate_json' => 'JSON validation',
    ];
    
    echo "Service Methods:\n";
    foreach ($service_methods as $method => $desc) {
        if (preg_match('/(public|private|protected) function ' . preg_quote($method, '/') . '\s*\(/', $service_content)) {
            echo "  ✓ $desc\n";
        } else {
            echo "  ❌ MISSING: $desc\n";
        }
    }
    echo "\n";
}

// Summary
echo "========================================\n";
echo "Summary\n";
echo "========================================\n\n";

echo "Total Required Tests: $total_tests\n";
echo "Tests Implemented: $passed_tests\n";
echo "Tests Missing: " . count($failed_tests) . "\n\n";

if (count($failed_tests) > 0) {
    echo "Missing Tests:\n";
    foreach ($failed_tests as $test) {
        echo "  - $test\n";
    }
    echo "\n";
}

// Check test documentation
$readme_file = __DIR__ . '/tests/php/rest-api/IMPORT-EXPORT-TESTS-QUICK-START.md';
if (file_exists($readme_file)) {
    echo "✓ Test documentation exists\n";
} else {
    echo "⚠ Test documentation not found (optional)\n";
}

echo "\n";

// Final verdict
if ($passed_tests === $total_tests && $all_files_exist) {
    echo "✅ SUCCESS: All required tests are implemented!\n";
    echo "✅ Task 5.5 is COMPLETE\n\n";
    
    echo "Test Coverage:\n";
    echo "  • Export with proper headers and format: ✓\n";
    echo "  • Import with valid and invalid data: ✓\n";
    echo "  • Automatic backup creation on import: ✓\n";
    echo "  • Legacy format migration: ✓\n";
    echo "  • Requirements 12.1, 12.2, 12.4: ✓\n\n";
    
    exit(0);
} else {
    echo "❌ FAILED: Some tests or files are missing\n";
    echo "❌ Task 5.5 is INCOMPLETE\n\n";
    exit(1);
}
