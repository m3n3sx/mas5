#!/usr/bin/env php
<?php
/**
 * Verify PHPUnit test setup for Modern Admin Styler V2
 *
 * This script checks if all required components are in place for running tests.
 */

echo "========================================\n";
echo "Test Setup Verification\n";
echo "========================================\n\n";

$checks = array();
$all_passed = true;

// Check 1: PHPUnit configuration
echo "1. Checking PHPUnit configuration...\n";
if (file_exists(__DIR__ . '/../phpunit.xml.dist')) {
    echo "   ✓ phpunit.xml.dist found\n";
    $checks['phpunit_config'] = true;
} else {
    echo "   ✗ phpunit.xml.dist not found\n";
    $checks['phpunit_config'] = false;
    $all_passed = false;
}

// Check 2: Bootstrap file
echo "\n2. Checking bootstrap file...\n";
if (file_exists(__DIR__ . '/bootstrap.php')) {
    echo "   ✓ tests/bootstrap.php found\n";
    $checks['bootstrap'] = true;
} else {
    echo "   ✗ tests/bootstrap.php not found\n";
    $checks['bootstrap'] = false;
    $all_passed = false;
}

// Check 3: Test directory structure
echo "\n3. Checking test directory structure...\n";
$required_dirs = array(
    __DIR__ . '/php',
    __DIR__ . '/php/rest-api',
);

foreach ($required_dirs as $dir) {
    $dir_name = str_replace(__DIR__ . '/', '', $dir);
    if (is_dir($dir)) {
        echo "   ✓ {$dir_name}/ exists\n";
    } else {
        echo "   ✗ {$dir_name}/ not found\n";
        $all_passed = false;
    }
}

// Check 4: Test files
echo "\n4. Checking test files...\n";
$test_files = array(
    __DIR__ . '/php/rest-api/TestMASRestController.php',
);

foreach ($test_files as $file) {
    $file_name = basename($file);
    if (file_exists($file)) {
        echo "   ✓ {$file_name} found\n";
        
        // Check for syntax errors
        $output = array();
        $return_var = 0;
        exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $return_var);
        
        if ($return_var === 0) {
            echo "     ✓ No syntax errors\n";
        } else {
            echo "     ✗ Syntax errors found:\n";
            echo "       " . implode("\n       ", $output) . "\n";
            $all_passed = false;
        }
    } else {
        echo "   ✗ {$file_name} not found\n";
        $all_passed = false;
    }
}

// Check 5: Required classes
echo "\n5. Checking required classes...\n";
$required_classes = array(
    __DIR__ . '/../includes/api/class-mas-rest-controller.php',
    __DIR__ . '/../includes/class-mas-rest-api.php',
    __DIR__ . '/../includes/services/class-mas-validation-service.php',
);

foreach ($required_classes as $file) {
    $file_name = basename($file);
    if (file_exists($file)) {
        echo "   ✓ {$file_name} found\n";
    } else {
        echo "   ✗ {$file_name} not found\n";
        $all_passed = false;
    }
}

// Check 6: WordPress test library
echo "\n6. Checking WordPress test library...\n";
$wp_tests_dir = getenv('WP_TESTS_DIR');
if (!$wp_tests_dir) {
    $wp_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

if (file_exists("{$wp_tests_dir}/includes/functions.php")) {
    echo "   ✓ WordPress test library found at: {$wp_tests_dir}\n";
    $checks['wp_tests'] = true;
} else {
    echo "   ✗ WordPress test library not found\n";
    echo "     Expected location: {$wp_tests_dir}\n";
    echo "     Run: bash bin/install-wp-tests.sh wordpress_test root '' localhost latest\n";
    $checks['wp_tests'] = false;
    // Don't fail on this as it's optional for syntax checking
}

// Check 7: PHPUnit executable
echo "\n7. Checking PHPUnit installation...\n";
$phpunit_check = shell_exec('which phpunit 2>&1');
if (!empty($phpunit_check)) {
    echo "   ✓ PHPUnit found at: " . trim($phpunit_check) . "\n";
    
    // Get version
    $version = shell_exec('phpunit --version 2>&1');
    if ($version) {
        echo "     " . trim($version) . "\n";
    }
    $checks['phpunit'] = true;
} else {
    echo "   ✗ PHPUnit not found in PATH\n";
    echo "     Install with: composer require --dev phpunit/phpunit\n";
    $checks['phpunit'] = false;
    // Don't fail on this as it might be installed via composer
}

// Check 8: Installation script
echo "\n8. Checking installation script...\n";
if (file_exists(__DIR__ . '/../bin/install-wp-tests.sh')) {
    echo "   ✓ bin/install-wp-tests.sh found\n";
    
    if (is_executable(__DIR__ . '/../bin/install-wp-tests.sh')) {
        echo "     ✓ Script is executable\n";
    } else {
        echo "     ⚠ Script is not executable (run: chmod +x bin/install-wp-tests.sh)\n";
    }
} else {
    echo "   ✗ bin/install-wp-tests.sh not found\n";
    $all_passed = false;
}

// Summary
echo "\n========================================\n";
echo "Summary\n";
echo "========================================\n\n";

if ($all_passed) {
    echo "✓ All critical checks passed!\n\n";
    echo "You can now run tests with:\n";
    echo "  phpunit\n";
    echo "  phpunit --testsuite rest-api\n";
    echo "  phpunit tests/php/rest-api/TestMASRestController.php\n\n";
    
    if (!isset($checks['wp_tests']) || !$checks['wp_tests']) {
        echo "Note: WordPress test library not found. Install it to run tests:\n";
        echo "  bash bin/install-wp-tests.sh wordpress_test root '' localhost latest\n\n";
    }
    
    exit(0);
} else {
    echo "✗ Some checks failed. Please review the output above.\n\n";
    exit(1);
}
