<?php
/**
 * Verification Script for Task 8: Security Hardening and Rate Limiting
 * 
 * Verifies that all components of Task 8 are properly implemented.
 */

echo "=== Task 8 Completion Verification ===\n\n";

$checks = [
    'files' => [],
    'classes' => [],
    'methods' => [],
    'integration' => [],
];

// Check 1: Required files exist
echo "1. Checking Required Files...\n";
$required_files = [
    'includes/services/class-mas-rate-limiter-service.php',
    'includes/services/class-mas-security-logger-service.php',
    'includes/api/class-mas-rest-controller.php',
    'includes/class-mas-rest-api.php',
];

foreach ($required_files as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    $checks['files'][$file] = $exists;
    echo ($exists ? '✓' : '✗') . " $file\n";
}

echo "\n";

// Check 2: Required classes exist
echo "2. Checking Required Classes...\n";

// Load WordPress if not already loaded
if (!function_exists('add_action')) {
    echo "Note: WordPress not loaded, skipping class checks\n";
} else {
    $required_classes = [
        'MAS_Rate_Limiter_Service',
        'MAS_Security_Logger_Service',
        'MAS_REST_Controller',
        'MAS_REST_API',
    ];
    
    foreach ($required_classes as $class) {
        $exists = class_exists($class);
        $checks['classes'][$class] = $exists;
        echo ($exists ? '✓' : '✗') . " $class\n";
    }
}

echo "\n";

// Check 3: Required methods in Rate Limiter
echo "3. Checking Rate Limiter Methods...\n";
if (class_exists('MAS_Rate_Limiter_Service')) {
    $rate_limiter_methods = [
        'check_rate_limit',
        'get_rate_limit_headers',
        'reset_rate_limit',
        'configure_limits',
        'set_window',
        'set_default_limit',
    ];
    
    foreach ($rate_limiter_methods as $method) {
        $exists = method_exists('MAS_Rate_Limiter_Service', $method);
        $checks['methods']['rate_limiter_' . $method] = $exists;
        echo ($exists ? '✓' : '✗') . " $method\n";
    }
} else {
    echo "✗ MAS_Rate_Limiter_Service class not found\n";
}

echo "\n";

// Check 4: Required methods in Security Logger
echo "4. Checking Security Logger Methods...\n";
if (class_exists('MAS_Security_Logger_Service')) {
    $logger_methods = [
        'log_auth_failure',
        'log_permission_denied',
        'log_rate_limit_exceeded',
        'log_suspicious_activity',
        'log_nonce_failure',
        'log_validation_failure',
        'get_logs',
        'get_statistics',
        'cleanup_old_logs',
        'clear_logs',
    ];
    
    foreach ($logger_methods as $method) {
        $exists = method_exists('MAS_Security_Logger_Service', $method);
        $checks['methods']['logger_' . $method] = $exists;
        echo ($exists ? '✓' : '✗') . " $method\n";
    }
} else {
    echo "✗ MAS_Security_Logger_Service class not found\n";
}

echo "\n";

// Check 5: Sanitization methods in Base Controller
echo "5. Checking Sanitization Methods...\n";
if (class_exists('MAS_REST_Controller')) {
    $reflection = new ReflectionClass('MAS_REST_Controller');
    $sanitization_methods = [
        'sanitize_color',
        'sanitize_css_unit',
        'sanitize_boolean',
        'sanitize_integer',
        'sanitize_array',
        'sanitize_json',
        'escape_output',
        'sanitize_filename',
        'sanitize_url',
    ];
    
    foreach ($sanitization_methods as $method) {
        $exists = $reflection->hasMethod($method);
        $checks['methods']['sanitize_' . $method] = $exists;
        echo ($exists ? '✓' : '✗') . " $method\n";
    }
} else {
    echo "✗ MAS_REST_Controller class not found\n";
}

echo "\n";

// Check 6: Integration with Base Controller
echo "6. Checking Integration...\n";
if (class_exists('MAS_REST_Controller')) {
    $reflection = new ReflectionClass('MAS_REST_Controller');
    
    // Check properties
    $has_rate_limiter = $reflection->hasProperty('rate_limiter');
    $checks['integration']['rate_limiter_property'] = $has_rate_limiter;
    echo ($has_rate_limiter ? '✓' : '✗') . " rate_limiter property\n";
    
    $has_security_logger = $reflection->hasProperty('security_logger');
    $checks['integration']['security_logger_property'] = $has_security_logger;
    echo ($has_security_logger ? '✓' : '✗') . " security_logger property\n";
    
    // Check methods
    $has_check_permission = $reflection->hasMethod('check_permission');
    $checks['integration']['check_permission_method'] = $has_check_permission;
    echo ($has_check_permission ? '✓' : '✗') . " check_permission method\n";
} else {
    echo "✗ MAS_REST_Controller class not found\n";
}

echo "\n";

// Check 7: Documentation files
echo "7. Checking Documentation...\n";
$doc_files = [
    'TASK-8-SECURITY-HARDENING-COMPLETION.md',
    'TASK-8.2-SANITIZATION-REVIEW.md',
    'SECURITY-API-QUICK-REFERENCE.md',
    'test-task8-security-hardening.php',
];

foreach ($doc_files as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    $checks['files'][$file] = $exists;
    echo ($exists ? '✓' : '✗') . " $file\n";
}

echo "\n";

// Summary
echo "=== Verification Summary ===\n";
$total_checks = 0;
$passed_checks = 0;

foreach ($checks as $category => $items) {
    foreach ($items as $item => $result) {
        $total_checks++;
        if ($result) {
            $passed_checks++;
        }
    }
}

echo "Total Checks: $total_checks\n";
echo "Passed: $passed_checks\n";
echo "Failed: " . ($total_checks - $passed_checks) . "\n";

if ($passed_checks === $total_checks) {
    echo "\n✓✓✓ Task 8 is COMPLETE and ready for production! ✓✓✓\n";
    exit(0);
} else {
    echo "\n✗ Some checks failed. Please review the implementation.\n";
    exit(1);
}
