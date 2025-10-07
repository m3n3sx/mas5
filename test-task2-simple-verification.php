<?php
/**
 * Simple Verification Test for Task 2: Lazy Loading
 * 
 * This test verifies the code structure without executing it.
 */

echo "=== Task 2: Lazy Loading Verification ===\n\n";

// Read the refactored file
$file_content = file_get_contents(__DIR__ . '/includes/class-mas-rest-api.php');

$tests_passed = 0;
$tests_failed = 0;

// Test 1: Verify load_dependencies() is NOT called in init()
echo "Test 1: Verify load_dependencies() is NOT called in init()\n";
echo "-----------------------------------------------------------\n";
if (preg_match('/private function init\(\).*?\{(.*?)\n    \}/s', $file_content, $matches)) {
    $init_method = $matches[1];
    if (strpos($init_method, 'load_dependencies()') === false) {
        echo "✓ PASS: load_dependencies() is not called in init()\n\n";
        $tests_passed++;
    } else {
        echo "✗ FAIL: load_dependencies() is still called in init()\n\n";
        $tests_failed++;
    }
} else {
    echo "✗ FAIL: Could not find init() method\n\n";
    $tests_failed++;
}

// Test 2: Verify WP_REST_Controller check exists in register_controllers()
echo "Test 2: Verify WP_REST_Controller check in register_controllers()\n";
echo "-------------------------------------------------------------------\n";
if (preg_match('/public function register_controllers\(\).*?\{(.*?)\n    \}/s', $file_content, $matches)) {
    $register_method = $matches[1];
    if (strpos($register_method, "class_exists('WP_REST_Controller')") !== false) {
        echo "✓ PASS: WP_REST_Controller existence check found\n\n";
        $tests_passed++;
    } else {
        echo "✗ FAIL: WP_REST_Controller check not found\n\n";
        $tests_failed++;
    }
} else {
    echo "✗ FAIL: Could not find register_controllers() method\n\n";
    $tests_failed++;
}

// Test 3: Verify load_dependencies() is called in register_controllers()
echo "Test 3: Verify load_dependencies() is called in register_controllers()\n";
echo "-----------------------------------------------------------------------\n";
if (preg_match('/public function register_controllers\(\).*?\{(.*?)\n    \}/s', $file_content, $matches)) {
    $register_method = $matches[1];
    if (strpos($register_method, 'load_dependencies()') !== false) {
        echo "✓ PASS: load_dependencies() is called in register_controllers()\n\n";
        $tests_passed++;
    } else {
        echo "✗ FAIL: load_dependencies() is not called in register_controllers()\n\n";
        $tests_failed++;
    }
}

// Test 4: Verify safe_require() method exists
echo "Test 4: Verify safe_require() method exists\n";
echo "--------------------------------------------\n";
if (strpos($file_content, 'private function safe_require(') !== false) {
    echo "✓ PASS: safe_require() method found\n\n";
    $tests_passed++;
} else {
    echo "✗ FAIL: safe_require() method not found\n\n";
    $tests_failed++;
}

// Test 5: Verify file_exists() checks in safe_require()
echo "Test 5: Verify file_exists() check in safe_require()\n";
echo "-----------------------------------------------------\n";
if (preg_match('/private function safe_require\(.*?\{(.*?)\n    \}/s', $file_content, $matches)) {
    $safe_require_method = $matches[1];
    if (strpos($safe_require_method, 'file_exists(') !== false) {
        echo "✓ PASS: file_exists() check found in safe_require()\n\n";
        $tests_passed++;
    } else {
        echo "✗ FAIL: file_exists() check not found in safe_require()\n\n";
        $tests_failed++;
    }
}

// Test 6: Verify try-catch blocks in safe_require()
echo "Test 6: Verify try-catch error handling in safe_require()\n";
echo "----------------------------------------------------------\n";
if (preg_match('/private function safe_require\(.*?\{(.*?)\n    \}/s', $file_content, $matches)) {
    $safe_require_method = $matches[1];
    if (strpos($safe_require_method, 'try {') !== false && 
        strpos($safe_require_method, 'catch (Exception') !== false &&
        strpos($safe_require_method, 'catch (Error') !== false) {
        echo "✓ PASS: try-catch blocks found for Exception and Error\n\n";
        $tests_passed++;
    } else {
        echo "✗ FAIL: try-catch blocks not properly implemented\n\n";
        $tests_failed++;
    }
}

// Test 7: Verify log_error() method exists
echo "Test 7: Verify log_error() method exists\n";
echo "-----------------------------------------\n";
if (strpos($file_content, 'private function log_error(') !== false) {
    echo "✓ PASS: log_error() method found\n\n";
    $tests_passed++;
} else {
    echo "✗ FAIL: log_error() method not found\n\n";
    $tests_failed++;
}

// Test 8: Verify error logging includes context
echo "Test 8: Verify error logging includes context information\n";
echo "----------------------------------------------------------\n";
if (preg_match('/private function log_error\(.*?\{(.*?)\n    \}/s', $file_content, $matches)) {
    $log_error_method = $matches[1];
    if (strpos($log_error_method, 'WordPress Version') !== false && 
        strpos($log_error_method, 'PHP Version') !== false) {
        echo "✓ PASS: Context information (WP version, PHP version) included\n\n";
        $tests_passed++;
    } else {
        echo "✗ FAIL: Context information not properly included\n\n";
        $tests_failed++;
    }
}

// Test 9: Verify debug logging for successful initialization
echo "Test 9: Verify debug logging for successful initialization\n";
echo "-----------------------------------------------------------\n";
if (preg_match('/public function register_controllers\(\).*?\{(.*?)\n    \}/s', $file_content, $matches)) {
    $register_method = $matches[1];
    if (strpos($register_method, 'Dependencies loaded successfully') !== false) {
        echo "✓ PASS: Success logging found in register_controllers()\n\n";
        $tests_passed++;
    } else {
        echo "✗ FAIL: Success logging not found\n\n";
        $tests_failed++;
    }
}

// Summary
echo "\n=== Test Summary ===\n";
echo "Tests Passed: $tests_passed\n";
echo "Tests Failed: $tests_failed\n\n";

if ($tests_failed === 0) {
    echo "✓ All verification tests passed!\n\n";
    echo "Task 2 Implementation Summary:\n";
    echo "------------------------------\n";
    echo "✓ Subtask 2.1: load_dependencies() moved from __construct() to register_controllers()\n";
    echo "✓ Subtask 2.2: WP_REST_Controller existence check added\n";
    echo "✓ Subtask 2.3: Safe file loading with error handling implemented\n\n";
    echo "The lazy loading pattern is correctly implemented!\n";
    exit(0);
} else {
    echo "✗ Some tests failed. Please review the implementation.\n";
    exit(1);
}
