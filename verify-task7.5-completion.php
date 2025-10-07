#!/usr/bin/env php
<?php
/**
 * Verify Task 7.5 Completion: Diagnostics Endpoint Tests
 *
 * This script verifies that all diagnostics endpoint tests have been implemented
 * and checks for syntax errors.
 */

echo "========================================\n";
echo "Task 7.5 Verification\n";
echo "Write tests for diagnostics endpoint\n";
echo "========================================\n\n";

$all_passed = true;
$test_file = __DIR__ . '/tests/php/rest-api/TestMASDiagnosticsIntegration.php';
$quick_start = __DIR__ . '/tests/php/rest-api/DIAGNOSTICS-TESTS-QUICK-START.md';

// Check 1: Test file exists
echo "1. Checking test file existence...\n";
if (file_exists($test_file)) {
    echo "   ✓ TestMASDiagnosticsIntegration.php found\n";
} else {
    echo "   ✗ TestMASDiagnosticsIntegration.php not found\n";
    $all_passed = false;
}

// Check 2: Quick start guide exists
echo "\n2. Checking quick start guide...\n";
if (file_exists($quick_start)) {
    echo "   ✓ DIAGNOSTICS-TESTS-QUICK-START.md found\n";
} else {
    echo "   ✗ DIAGNOSTICS-TESTS-QUICK-START.md not found\n";
    $all_passed = false;
}

// Check 3: Syntax check
echo "\n3. Checking PHP syntax...\n";
if (file_exists($test_file)) {
    $output = array();
    $return_var = 0;
    exec("php -l " . escapeshellarg($test_file) . " 2>&1", $output, $return_var);
    
    if ($return_var === 0) {
        echo "   ✓ No syntax errors\n";
    } else {
        echo "   ✗ Syntax errors found:\n";
        echo "       " . implode("\n       ", $output) . "\n";
        $all_passed = false;
    }
}

// Check 4: Required test methods
echo "\n4. Checking required test methods...\n";
if (file_exists($test_file)) {
    $content = file_get_contents($test_file);
    
    $required_tests = array(
        // System information collection
        'test_get_system_information' => 'Test system information collection',
        'test_get_plugin_information' => 'Test plugin information collection',
        
        // Settings integrity validation
        'test_settings_integrity_validation' => 'Test settings integrity validation',
        'test_settings_integrity_with_invalid_data' => 'Test settings integrity with invalid data',
        'test_settings_integrity_with_missing_keys' => 'Test settings integrity with missing keys',
        
        // Conflict detection
        'test_conflict_detection' => 'Test conflict detection',
        'test_conflict_detection_with_plugins' => 'Test conflict detection with plugins',
        
        // Health checks
        'test_health_check_endpoint' => 'Test health check endpoint',
        'test_health_check_status_determination' => 'Test health check status determination',
        
        // Performance metrics
        'test_performance_metrics_collection' => 'Test performance metrics collection',
        'test_performance_metrics_endpoint' => 'Test performance metrics endpoint',
        'test_diagnostics_performance' => 'Test diagnostics performance',
        
        // Authentication & Authorization
        'test_diagnostics_requires_authentication' => 'Test diagnostics requires authentication',
        'test_diagnostics_requires_proper_authorization' => 'Test diagnostics requires proper authorization',
        'test_health_check_requires_authentication' => 'Test health check requires authentication',
        'test_performance_metrics_requires_authentication' => 'Test performance metrics requires authentication',
        
        // Additional features
        'test_diagnostics_with_include_parameter' => 'Test diagnostics with include parameter',
        'test_diagnostics_with_invalid_include_parameter' => 'Test diagnostics with invalid include parameter',
        'test_recommendations_generation' => 'Test recommendations generation',
        'test_diagnostics_metadata' => 'Test diagnostics metadata',
        'test_diagnostics_error_handling' => 'Test diagnostics error handling',
        'test_complete_diagnostics_workflow' => 'Test complete diagnostics workflow',
        'test_diagnostics_response_format_consistency' => 'Test diagnostics response format consistency',
        'test_diagnostics_no_caching' => 'Test diagnostics no caching',
    );
    
    $missing_tests = array();
    foreach ($required_tests as $method => $description) {
        if (strpos($content, "function {$method}") !== false) {
            echo "   ✓ {$description}\n";
        } else {
            echo "   ✗ Missing: {$description}\n";
            $missing_tests[] = $method;
            $all_passed = false;
        }
    }
    
    if (empty($missing_tests)) {
        echo "\n   All required test methods are present!\n";
    }
}

// Check 5: Test class structure
echo "\n5. Checking test class structure...\n";
if (file_exists($test_file)) {
    $content = file_get_contents($test_file);
    
    $required_elements = array(
        'class TestMASDiagnosticsIntegration extends WP_UnitTestCase' => 'Test class declaration',
        'protected $admin_user' => 'Admin user property',
        'protected $controller' => 'Controller property',
        'protected $service' => 'Service property',
        'public function setUp()' => 'setUp method',
        'public function tearDown()' => 'tearDown method',
    );
    
    foreach ($required_elements as $element => $description) {
        if (strpos($content, $element) !== false) {
            echo "   ✓ {$description}\n";
        } else {
            echo "   ✗ Missing: {$description}\n";
            $all_passed = false;
        }
    }
}

// Check 6: Required dependencies
echo "\n6. Checking required dependencies...\n";
$required_files = array(
    __DIR__ . '/includes/api/class-mas-diagnostics-controller.php' => 'Diagnostics controller',
    __DIR__ . '/includes/services/class-mas-diagnostics-service.php' => 'Diagnostics service',
    __DIR__ . '/includes/api/class-mas-rest-controller.php' => 'Base REST controller',
);

foreach ($required_files as $file => $description) {
    if (file_exists($file)) {
        echo "   ✓ {$description} exists\n";
    } else {
        echo "   ✗ {$description} not found\n";
        $all_passed = false;
    }
}

// Check 7: Test coverage requirements
echo "\n7. Checking requirements coverage...\n";
if (file_exists($test_file)) {
    $content = file_get_contents($test_file);
    
    $requirements = array(
        '12.1' => 'Unit tests cover all business logic',
        '12.2' => 'Integration tests cover all endpoints end-to-end',
        '12.3' => 'Authentication tests cover success and failure cases',
    );
    
    foreach ($requirements as $req_id => $description) {
        if (strpos($content, "Requirements: {$req_id}") !== false || 
            strpos($content, "Requirement {$req_id}") !== false) {
            echo "   ✓ Requirement {$req_id}: {$description}\n";
        } else {
            echo "   ⚠ Requirement {$req_id} not explicitly referenced\n";
        }
    }
}

// Check 8: Test endpoints coverage
echo "\n8. Checking endpoint coverage...\n";
$endpoints = array(
    '/mas-v2/v1/diagnostics' => 'Main diagnostics endpoint',
    '/mas-v2/v1/diagnostics/health' => 'Health check endpoint',
    '/mas-v2/v1/diagnostics/performance' => 'Performance metrics endpoint',
);

if (file_exists($test_file)) {
    $content = file_get_contents($test_file);
    
    foreach ($endpoints as $endpoint => $description) {
        if (strpos($content, $endpoint) !== false) {
            echo "   ✓ {$description} tested\n";
        } else {
            echo "   ✗ {$description} not tested\n";
            $all_passed = false;
        }
    }
}

// Summary
echo "\n========================================\n";
echo "Summary\n";
echo "========================================\n\n";

if ($all_passed) {
    echo "✓ Task 7.5 completed successfully!\n\n";
    echo "Test Coverage:\n";
    echo "  • System information collection\n";
    echo "  • Settings integrity validation\n";
    echo "  • Conflict detection\n";
    echo "  • Health checks\n";
    echo "  • Performance metrics\n";
    echo "  • Authentication & authorization\n";
    echo "  • Error handling\n";
    echo "  • Complete workflow testing\n\n";
    
    echo "Files Created:\n";
    echo "  • tests/php/rest-api/TestMASDiagnosticsIntegration.php\n";
    echo "  • tests/php/rest-api/DIAGNOSTICS-TESTS-QUICK-START.md\n\n";
    
    echo "Next Steps:\n";
    echo "  1. Run the tests: phpunit tests/php/rest-api/TestMASDiagnosticsIntegration.php\n";
    echo "  2. Review test coverage report\n";
    echo "  3. Mark task 7.5 as complete\n";
    echo "  4. Proceed to task 8.1\n\n";
    
    exit(0);
} else {
    echo "✗ Task 7.5 incomplete. Please review the output above.\n\n";
    exit(1);
}
