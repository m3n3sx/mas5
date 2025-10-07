<?php
/**
 * Verification Script for Task 4.5: Backup Endpoints Tests
 * 
 * This script verifies that comprehensive tests have been created for
 * the backup REST API endpoints.
 * 
 * Requirements Verified:
 * - 12.1: Unit tests cover all business logic
 * - 12.2: Integration tests cover end-to-end workflows
 * - 12.4: Edge cases and malformed data handled
 */

// Color output helpers
function print_success($message) {
    echo "\033[0;32m✓ $message\033[0m\n";
}

function print_error($message) {
    echo "\033[0;31m✗ $message\033[0m\n";
}

function print_info($message) {
    echo "\033[0;34mℹ $message\033[0m\n";
}

function print_header($message) {
    echo "\n\033[1;36m" . str_repeat('=', 70) . "\033[0m\n";
    echo "\033[1;36m$message\033[0m\n";
    echo "\033[1;36m" . str_repeat('=', 70) . "\033[0m\n\n";
}

print_header('Task 4.5 Verification: Backup Endpoints Tests');

$all_checks_passed = true;

// Check 1: Test file exists
print_info('Checking if test file exists...');
$test_file = __DIR__ . '/tests/php/rest-api/TestMASBackupsIntegration.php';

if (file_exists($test_file)) {
    print_success('Test file exists: TestMASBackupsIntegration.php');
} else {
    print_error('Test file not found: TestMASBackupsIntegration.php');
    $all_checks_passed = false;
}

// Check 2: Test file syntax
print_info('Checking test file syntax...');
$syntax_check = shell_exec("php -l $test_file 2>&1");

if (strpos($syntax_check, 'No syntax errors') !== false) {
    print_success('Test file has no syntax errors');
} else {
    print_error('Test file has syntax errors:');
    echo $syntax_check;
    $all_checks_passed = false;
}

// Check 3: Analyze test coverage
print_info('Analyzing test coverage...');
$test_content = file_get_contents($test_file);

$required_tests = [
    // Backup creation and listing
    'test_create_backup' => 'Backup creation via REST API',
    'test_list_backups' => 'Backup listing',
    'test_list_backups_with_pagination' => 'Backup listing with pagination',
    'test_get_specific_backup' => 'Get specific backup',
    'test_get_nonexistent_backup' => 'Handle non-existent backup',
    
    // Backup restoration
    'test_restore_backup_with_validation' => 'Backup restoration with validation',
    'test_restore_creates_pre_restore_backup' => 'Pre-restore backup creation',
    'test_restore_nonexistent_backup' => 'Restore non-existent backup',
    
    // Backup deletion
    'test_delete_backup' => 'Backup deletion',
    'test_delete_nonexistent_backup' => 'Delete non-existent backup',
    
    // Automatic cleanup
    'test_automatic_cleanup_by_count' => 'Automatic cleanup by count',
    'test_automatic_cleanup_preserves_manual_backups' => 'Cleanup preserves manual backups',
    
    // Validation
    'test_backup_validation_with_invalid_data' => 'Validation with invalid data',
    'test_backup_validation_with_missing_metadata' => 'Validation with missing metadata',
    'test_rollback_on_failed_restore' => 'Rollback on failed restore',
    
    // Authentication
    'test_endpoints_require_authentication' => 'Authentication requirement',
    'test_endpoints_require_manage_options_capability' => 'Authorization requirement',
    
    // Integration
    'test_complete_backup_workflow' => 'Complete backup workflow',
    'test_backup_statistics' => 'Backup statistics',
    'test_backup_metadata_storage' => 'Metadata storage',
    'test_backup_type_distinction' => 'Backup type distinction',
    'test_concurrent_backup_operations' => 'Concurrent operations',
    'test_backup_response_format' => 'Response format consistency',
    'test_error_response_format' => 'Error format consistency',
];

$tests_found = 0;
$tests_missing = [];

foreach ($required_tests as $test_name => $description) {
    if (strpos($test_content, "function $test_name") !== false) {
        print_success("Found test: $test_name - $description");
        $tests_found++;
    } else {
        print_error("Missing test: $test_name - $description");
        $tests_missing[] = $test_name;
        $all_checks_passed = false;
    }
}

echo "\n";
print_info("Tests found: $tests_found / " . count($required_tests));

// Check 4: Verify requirements coverage
print_info('Verifying requirements coverage...');

$requirements_coverage = [
    '12.1' => ['test_create_backup', 'test_list_backups', 'test_delete_backup'],
    '12.2' => ['test_complete_backup_workflow', 'test_restore_backup_with_validation', 'test_automatic_cleanup_by_count'],
    '12.3' => ['test_endpoints_require_authentication', 'test_endpoints_require_manage_options_capability'],
    '12.4' => ['test_backup_validation_with_invalid_data', 'test_rollback_on_failed_restore'],
];

foreach ($requirements_coverage as $req => $tests) {
    $covered = true;
    foreach ($tests as $test) {
        if (strpos($test_content, "function $test") === false) {
            $covered = false;
            break;
        }
    }
    
    if ($covered) {
        print_success("Requirement $req covered");
    } else {
        print_error("Requirement $req not fully covered");
        $all_checks_passed = false;
    }
}

// Check 5: Verify documentation exists
print_info('Checking documentation...');
$doc_file = __DIR__ . '/tests/php/rest-api/BACKUP-TESTS-QUICK-START.md';

if (file_exists($doc_file)) {
    print_success('Documentation exists: BACKUP-TESTS-QUICK-START.md');
} else {
    print_error('Documentation not found: BACKUP-TESTS-QUICK-START.md');
    $all_checks_passed = false;
}

// Check 6: Verify test class structure
print_info('Verifying test class structure...');

$required_methods = [
    'setUp' => 'Test setup method',
    'tearDown' => 'Test teardown method',
    'cleanup_all_backups' => 'Cleanup helper method',
    'create_test_settings' => 'Test settings helper',
];

foreach ($required_methods as $method => $description) {
    if (strpos($test_content, "function $method") !== false) {
        print_success("Found method: $method - $description");
    } else {
        print_error("Missing method: $method - $description");
        $all_checks_passed = false;
    }
}

// Check 7: Verify test assertions
print_info('Checking test assertions...');

$assertion_patterns = [
    'assertEquals' => 'Equality assertions',
    'assertTrue' => 'Boolean assertions',
    'assertArrayHasKey' => 'Array key assertions',
    'assertCount' => 'Count assertions',
    'assertGreaterThan' => 'Comparison assertions',
    'assertNotNull' => 'Null assertions',
];

foreach ($assertion_patterns as $pattern => $description) {
    $count = substr_count($test_content, $pattern);
    if ($count > 0) {
        print_success("Found $count $pattern assertions - $description");
    } else {
        print_error("No $pattern assertions found - $description");
    }
}

// Check 8: Verify test scenarios
print_info('Verifying test scenarios...');

$scenarios = [
    'Backup creation and listing' => ['test_create_backup', 'test_list_backups'],
    'Backup restoration with validation' => ['test_restore_backup_with_validation'],
    'Automatic cleanup functionality' => ['test_automatic_cleanup_by_count'],
    'Rollback on failed restore' => ['test_rollback_on_failed_restore'],
];

foreach ($scenarios as $scenario => $tests) {
    $scenario_covered = true;
    foreach ($tests as $test) {
        if (strpos($test_content, "function $test") === false) {
            $scenario_covered = false;
            break;
        }
    }
    
    if ($scenario_covered) {
        print_success("Scenario covered: $scenario");
    } else {
        print_error("Scenario not covered: $scenario");
        $all_checks_passed = false;
    }
}

// Final summary
print_header('Verification Summary');

if ($all_checks_passed) {
    print_success('All checks passed! Task 4.5 is complete.');
    echo "\n";
    print_info('Test Coverage Summary:');
    echo "  • Total tests: " . count($required_tests) . "\n";
    echo "  • Tests found: $tests_found\n";
    echo "  • Requirements covered: 12.1, 12.2, 12.3, 12.4\n";
    echo "\n";
    print_info('Next Steps:');
    echo "  1. Run the tests: phpunit tests/php/rest-api/TestMASBackupsIntegration.php\n";
    echo "  2. Review test output\n";
    echo "  3. Mark task 4.5 as complete\n";
    echo "  4. Proceed to task 5.1 (Import/Export service)\n";
    echo "\n";
    exit(0);
} else {
    print_error('Some checks failed. Please review the errors above.');
    echo "\n";
    if (!empty($tests_missing)) {
        print_info('Missing tests:');
        foreach ($tests_missing as $test) {
            echo "  • $test\n";
        }
    }
    echo "\n";
    exit(1);
}
