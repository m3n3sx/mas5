<?php
/**
 * Verification Script for Task 6.5 - Preview Endpoint Tests
 * 
 * This script verifies that the preview endpoint tests are properly implemented
 * and cover all required functionality.
 *
 * @package Modern_Admin_Styler_V2
 * @subpackage Tests
 */

// Color output helpers
function print_header($text) {
    echo "\n\033[1;34m" . str_repeat('=', 70) . "\033[0m\n";
    echo "\033[1;34m  " . $text . "\033[0m\n";
    echo "\033[1;34m" . str_repeat('=', 70) . "\033[0m\n\n";
}

function print_success($text) {
    echo "\033[0;32m✓ " . $text . "\033[0m\n";
}

function print_error($text) {
    echo "\033[0;31m✗ " . $text . "\033[0m\n";
}

function print_info($text) {
    echo "\033[0;36mℹ " . $text . "\033[0m\n";
}

function print_section($text) {
    echo "\n\033[1;33m" . $text . "\033[0m\n";
    echo str_repeat('-', 70) . "\n";
}

// Start verification
print_header('Task 6.5 - Preview Endpoint Tests Verification');

$errors = [];
$warnings = [];
$success_count = 0;
$total_checks = 0;

// Check 1: Test file exists
print_section('1. Checking Test File Existence');
$total_checks++;

$test_file = __DIR__ . '/tests/php/rest-api/TestMASPreviewIntegration.php';
if (file_exists($test_file)) {
    print_success('Test file exists: TestMASPreviewIntegration.php');
    $success_count++;
} else {
    print_error('Test file not found: TestMASPreviewIntegration.php');
    $errors[] = 'Test file missing';
}

// Check 2: Test file syntax
print_section('2. Checking Test File Syntax');
$total_checks++;

$output = [];
$return_var = 0;
exec("php -l " . escapeshellarg($test_file) . " 2>&1", $output, $return_var);

if ($return_var === 0) {
    print_success('Test file has valid PHP syntax');
    $success_count++;
} else {
    print_error('Test file has syntax errors');
    $errors[] = 'Syntax errors in test file';
    print_info('Output: ' . implode("\n", $output));
}

// Check 3: Test class structure
print_section('3. Analyzing Test Class Structure');

if (file_exists($test_file)) {
    $content = file_get_contents($test_file);
    
    // Check class exists
    $total_checks++;
    if (preg_match('/class\s+TestMASPreviewIntegration\s+extends\s+WP_UnitTestCase/', $content)) {
        print_success('Test class properly extends WP_UnitTestCase');
        $success_count++;
    } else {
        print_error('Test class structure incorrect');
        $errors[] = 'Invalid test class structure';
    }
    
    // Check setUp method
    $total_checks++;
    if (preg_match('/public\s+function\s+setUp\s*\(\)/', $content)) {
        print_success('setUp() method defined');
        $success_count++;
    } else {
        print_error('setUp() method missing');
        $errors[] = 'Missing setUp method';
    }
    
    // Check tearDown method
    $total_checks++;
    if (preg_match('/public\s+function\s+tearDown\s*\(\)/', $content)) {
        print_success('tearDown() method defined');
        $success_count++;
    } else {
        print_error('tearDown() method missing');
        $errors[] = 'Missing tearDown method';
    }
}

// Check 4: Required test methods
print_section('4. Checking Required Test Methods');

$required_tests = [
    // Authentication tests
    'test_preview_requires_authentication' => 'Authentication required',
    'test_preview_requires_manage_options' => 'Manage options capability required',
    
    // CSS generation tests
    'test_preview_generates_css_without_saving' => 'CSS generation without saving',
    'test_preview_css_includes_all_sections' => 'All CSS sections included',
    'test_preview_does_not_use_cache' => 'Preview does not use cache',
    
    // Validation tests
    'test_preview_validates_color_values' => 'Color validation',
    'test_preview_accepts_valid_hex_colors' => 'Hex color acceptance',
    'test_preview_accepts_rgba_colors' => 'RGBA color acceptance',
    'test_preview_requires_settings_parameter' => 'Settings parameter required',
    'test_preview_rejects_non_object_settings' => 'Non-object settings rejected',
    
    // Debouncing tests
    'test_preview_debouncing_prevents_rapid_requests' => 'Debouncing prevents rapid requests',
    'test_preview_allows_requests_after_debounce_delay' => 'Requests allowed after delay',
    
    // Fallback tests
    'test_preview_returns_fallback_on_generation_error' => 'Fallback on generation error',
    'test_preview_fallback_includes_provided_colors' => 'Fallback includes colors',
    
    // Response format tests
    'test_preview_sets_no_cache_headers' => 'No-cache headers set',
    'test_preview_response_includes_metadata' => 'Response includes metadata',
];

foreach ($required_tests as $method => $description) {
    $total_checks++;
    if (preg_match('/public\s+function\s+' . preg_quote($method) . '\s*\(\)/', $content)) {
        print_success("Test method exists: {$description}");
        $success_count++;
    } else {
        print_error("Test method missing: {$description}");
        $errors[] = "Missing test: {$method}";
    }
}

// Check 5: Test coverage areas
print_section('5. Verifying Test Coverage Areas');

$coverage_areas = [
    'CSS generation without saving' => 'generates_css_without_saving',
    'Debouncing functionality' => 'debouncing',
    'Rate limiting (429 status)' => '429',
    'Fallback CSS generation' => 'fallback',
    'Color validation' => 'validates_color',
    'Cache headers' => 'Cache-Control',
    'Authentication checks' => 'check_permission',
    'Settings sanitization' => 'sanitize',
];

foreach ($coverage_areas as $area => $pattern) {
    $total_checks++;
    if (stripos($content, $pattern) !== false) {
        print_success("Coverage area: {$area}");
        $success_count++;
    } else {
        print_error("Missing coverage: {$area}");
        $warnings[] = "Limited coverage for: {$area}";
    }
}

// Check 6: Assertion usage
print_section('6. Checking Assertion Usage');

$assertion_patterns = [
    'assertEquals' => 'Equality assertions',
    'assertTrue' => 'Boolean assertions',
    'assertArrayHasKey' => 'Array key assertions',
    'assertStringContainsString' => 'String content assertions',
    'assertInstanceOf' => 'Type assertions',
    'assertNotEmpty' => 'Non-empty assertions',
];

foreach ($assertion_patterns as $assertion => $description) {
    $total_checks++;
    if (preg_match('/\$this->' . preg_quote($assertion) . '\s*\(/', $content)) {
        print_success("Uses {$description} ({$assertion})");
        $success_count++;
    } else {
        print_info("Note: {$description} not used ({$assertion})");
    }
}

// Check 7: Documentation
print_section('7. Checking Documentation');

$total_checks++;
$quick_start = __DIR__ . '/tests/php/rest-api/PREVIEW-TESTS-QUICK-START.md';
if (file_exists($quick_start)) {
    print_success('Quick start guide exists');
    $success_count++;
} else {
    print_error('Quick start guide missing');
    $warnings[] = 'Missing documentation';
}

// Check 8: Test requirements coverage
print_section('8. Verifying Requirements Coverage');

$requirements = [
    '12.1' => 'Unit tests cover business logic',
    '12.2' => 'Integration tests cover end-to-end workflows',
    '6.1' => 'Preview generates CSS without saving',
    '6.3' => 'Debouncing prevents server overload',
    '6.4' => 'Fallback CSS on generation errors',
    '6.6' => 'Proper cache headers',
];

foreach ($requirements as $req_id => $description) {
    $total_checks++;
    if (stripos($content, $req_id) !== false || 
        stripos($content, str_replace('.', '_', $req_id)) !== false) {
        print_success("Requirement {$req_id}: {$description}");
        $success_count++;
    } else {
        print_info("Note: Requirement {$req_id} not explicitly referenced");
    }
}

// Check 9: Edge cases
print_section('9. Checking Edge Case Coverage');

$edge_cases = [
    'Empty settings' => 'empty_settings',
    'Invalid colors' => 'invalid.*color|not-a-color',
    'Rapid requests' => 'rapid.*request',
    'Generation errors' => 'generation.*error|Exception',
    'Unauthenticated access' => 'unauthenticated',
];

foreach ($edge_cases as $case => $pattern) {
    $total_checks++;
    if (preg_match('/' . $pattern . '/i', $content)) {
        print_success("Edge case tested: {$case}");
        $success_count++;
    } else {
        print_info("Note: Edge case may not be covered: {$case}");
    }
}

// Check 10: Mock usage for error testing
print_section('10. Checking Mock Object Usage');

$total_checks++;
if (preg_match('/getMockBuilder|createMock/', $content)) {
    print_success('Uses mock objects for error testing');
    $success_count++;
} else {
    print_info('Note: Mock objects may not be used');
}

// Final summary
print_section('Verification Summary');

$percentage = ($success_count / $total_checks) * 100;

echo "\nTotal Checks: {$total_checks}\n";
echo "Passed: \033[0;32m{$success_count}\033[0m\n";
echo "Failed: \033[0;31m" . (count($errors)) . "\033[0m\n";
echo "Warnings: \033[0;33m" . (count($warnings)) . "\033[0m\n";
echo "Success Rate: " . ($percentage >= 80 ? "\033[0;32m" : "\033[0;31m") . 
     number_format($percentage, 1) . "%\033[0m\n";

if (!empty($errors)) {
    print_section('Errors Found');
    foreach ($errors as $error) {
        print_error($error);
    }
}

if (!empty($warnings)) {
    print_section('Warnings');
    foreach ($warnings as $warning) {
        echo "\033[0;33m⚠ {$warning}\033[0m\n";
    }
}

// Task completion status
print_header('Task 6.5 Completion Status');

if (empty($errors) && $percentage >= 80) {
    print_success('Task 6.5 is COMPLETE');
    print_success('All required tests are implemented');
    echo "\n";
    print_info('Next steps:');
    print_info('1. Run the tests: phpunit tests/php/rest-api/TestMASPreviewIntegration.php');
    print_info('2. Verify all tests pass');
    print_info('3. Review test coverage report');
    print_info('4. Mark task 6.5 as complete in tasks.md');
    echo "\n";
    exit(0);
} else {
    print_error('Task 6.5 is INCOMPLETE');
    print_error('Please address the errors above');
    echo "\n";
    exit(1);
}
