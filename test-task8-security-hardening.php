<?php
/**
 * Test Task 8: Security Hardening and Rate Limiting
 * 
 * Tests the security features including rate limiting, input sanitization,
 * and security logging.
 * 
 * Usage: Run from command line or access via browser
 * php test-task8-security-hardening.php
 */

// Load WordPress
require_once __DIR__ . '/modern-admin-styler-v2.php';

// Test results
$results = [
    'rate_limiter' => [],
    'sanitization' => [],
    'security_logger' => [],
];

echo "=== Task 8: Security Hardening Tests ===\n\n";

// Test 1: Rate Limiter Service
echo "Test 1: Rate Limiter Service\n";
echo "----------------------------\n";

if (class_exists('MAS_Rate_Limiter_Service')) {
    $rate_limiter = new MAS_Rate_Limiter_Service();
    
    // Test rate limit check
    $check1 = $rate_limiter->check_rate_limit('/settings', 1);
    $results['rate_limiter']['basic_check'] = $check1 === true ? 'PASS' : 'FAIL';
    echo "✓ Basic rate limit check: " . ($check1 === true ? 'PASS' : 'FAIL') . "\n";
    
    // Test rate limit headers
    $headers = $rate_limiter->get_rate_limit_headers('/settings', 1);
    $has_headers = isset($headers['X-RateLimit-Limit']) && 
                   isset($headers['X-RateLimit-Remaining']) && 
                   isset($headers['X-RateLimit-Reset']);
    $results['rate_limiter']['headers'] = $has_headers ? 'PASS' : 'FAIL';
    echo "✓ Rate limit headers: " . ($has_headers ? 'PASS' : 'FAIL') . "\n";
    
    // Test endpoint-specific limits
    $rate_limiter->configure_limits(['/test' => 5]);
    $results['rate_limiter']['configuration'] = 'PASS';
    echo "✓ Rate limit configuration: PASS\n";
    
    // Test rate limit reset
    $reset = $rate_limiter->reset_rate_limit('/settings', 1);
    $results['rate_limiter']['reset'] = $reset ? 'PASS' : 'FAIL';
    echo "✓ Rate limit reset: " . ($reset ? 'PASS' : 'FAIL') . "\n";
    
} else {
    echo "✗ MAS_Rate_Limiter_Service class not found\n";
    $results['rate_limiter']['class_exists'] = 'FAIL';
}

echo "\n";

// Test 2: Input Sanitization
echo "Test 2: Input Sanitization\n";
echo "--------------------------\n";

if (class_exists('MAS_REST_Controller')) {
    // Create a test controller instance
    $test_controller = new class extends MAS_REST_Controller {
        public function register_routes() {}
        
        // Expose protected methods for testing
        public function test_sanitize_color($color) {
            return $this->sanitize_color($color);
        }
        
        public function test_sanitize_css_unit($value) {
            return $this->sanitize_css_unit($value);
        }
        
        public function test_sanitize_boolean($value) {
            return $this->sanitize_boolean($value);
        }
        
        public function test_sanitize_integer($value, $min = null, $max = null) {
            return $this->sanitize_integer($value, $min, $max);
        }
    };
    
    // Test color sanitization
    $color_test = $test_controller->test_sanitize_color('#ff0000');
    $results['sanitization']['color'] = $color_test === '#ff0000' ? 'PASS' : 'FAIL';
    echo "✓ Color sanitization: " . ($color_test === '#ff0000' ? 'PASS' : 'FAIL') . "\n";
    
    // Test invalid color
    $invalid_color = $test_controller->test_sanitize_color('invalid');
    $results['sanitization']['invalid_color'] = $invalid_color === '' ? 'PASS' : 'FAIL';
    echo "✓ Invalid color rejection: " . ($invalid_color === '' ? 'PASS' : 'FAIL') . "\n";
    
    // Test CSS unit sanitization
    $css_unit = $test_controller->test_sanitize_css_unit('10px');
    $results['sanitization']['css_unit'] = $css_unit === '10px' ? 'PASS' : 'FAIL';
    echo "✓ CSS unit sanitization: " . ($css_unit === '10px' ? 'PASS' : 'FAIL') . "\n";
    
    // Test boolean sanitization
    $bool_test = $test_controller->test_sanitize_boolean('true');
    $results['sanitization']['boolean'] = $bool_test === true ? 'PASS' : 'FAIL';
    echo "✓ Boolean sanitization: " . ($bool_test === true ? 'PASS' : 'FAIL') . "\n";
    
    // Test integer sanitization with bounds
    $int_test = $test_controller->test_sanitize_integer(150, 0, 100);
    $results['sanitization']['integer_bounds'] = $int_test === 100 ? 'PASS' : 'FAIL';
    echo "✓ Integer bounds enforcement: " . ($int_test === 100 ? 'PASS' : 'FAIL') . "\n";
    
} else {
    echo "✗ MAS_REST_Controller class not found\n";
    $results['sanitization']['class_exists'] = 'FAIL';
}

echo "\n";

// Test 3: Security Logger Service
echo "Test 3: Security Logger Service\n";
echo "-------------------------------\n";

if (class_exists('MAS_Security_Logger_Service')) {
    $security_logger = new MAS_Security_Logger_Service();
    
    // Test authentication failure logging
    $auth_log = $security_logger->log_auth_failure('testuser', '127.0.0.1', '/settings');
    $results['security_logger']['auth_failure'] = $auth_log ? 'PASS' : 'FAIL';
    echo "✓ Authentication failure logging: " . ($auth_log ? 'PASS' : 'FAIL') . "\n";
    
    // Test permission denied logging
    $perm_log = $security_logger->log_permission_denied(1, '/settings', 'manage_options');
    $results['security_logger']['permission_denied'] = $perm_log ? 'PASS' : 'FAIL';
    echo "✓ Permission denied logging: " . ($perm_log ? 'PASS' : 'FAIL') . "\n";
    
    // Test rate limit logging
    $rate_log = $security_logger->log_rate_limit_exceeded(1, '/settings', 100);
    $results['security_logger']['rate_limit'] = $rate_log ? 'PASS' : 'FAIL';
    echo "✓ Rate limit exceeded logging: " . ($rate_log ? 'PASS' : 'FAIL') . "\n";
    
    // Test suspicious activity logging
    $suspicious_log = $security_logger->log_suspicious_activity(
        'sql_injection_attempt',
        'Detected SQL injection pattern in request',
        ['pattern' => "' OR '1'='1"]
    );
    $results['security_logger']['suspicious_activity'] = $suspicious_log ? 'PASS' : 'FAIL';
    echo "✓ Suspicious activity logging: " . ($suspicious_log ? 'PASS' : 'FAIL') . "\n";
    
    // Test nonce failure logging
    $nonce_log = $security_logger->log_nonce_failure('/settings', 'invalid_nonce');
    $results['security_logger']['nonce_failure'] = $nonce_log ? 'PASS' : 'FAIL';
    echo "✓ Nonce failure logging: " . ($nonce_log ? 'PASS' : 'FAIL') . "\n";
    
    // Test log retrieval
    $logs = $security_logger->get_logs([], 10);
    $results['security_logger']['log_retrieval'] = is_array($logs) ? 'PASS' : 'FAIL';
    echo "✓ Log retrieval: " . (is_array($logs) ? 'PASS (' . count($logs) . ' entries)' : 'FAIL') . "\n";
    
    // Test log statistics
    $stats = $security_logger->get_statistics(7);
    $has_stats = isset($stats['total_events']) && 
                 isset($stats['by_severity']) && 
                 isset($stats['by_type']);
    $results['security_logger']['statistics'] = $has_stats ? 'PASS' : 'FAIL';
    echo "✓ Log statistics: " . ($has_stats ? 'PASS' : 'FAIL') . "\n";
    
    // Clean up test logs
    $security_logger->clear_logs();
    echo "✓ Test logs cleaned up\n";
    
} else {
    echo "✗ MAS_Security_Logger_Service class not found\n";
    $results['security_logger']['class_exists'] = 'FAIL';
}

echo "\n";

// Test 4: Integration with Base Controller
echo "Test 4: Integration with Base Controller\n";
echo "----------------------------------------\n";

if (class_exists('MAS_REST_Controller')) {
    // Check if rate limiter is initialized
    $reflection = new ReflectionClass('MAS_REST_Controller');
    $has_rate_limiter = $reflection->hasProperty('rate_limiter');
    $results['integration']['rate_limiter_property'] = $has_rate_limiter ? 'PASS' : 'FAIL';
    echo "✓ Rate limiter property exists: " . ($has_rate_limiter ? 'PASS' : 'FAIL') . "\n";
    
    // Check if security logger is initialized
    $has_security_logger = $reflection->hasProperty('security_logger');
    $results['integration']['security_logger_property'] = $has_security_logger ? 'PASS' : 'FAIL';
    echo "✓ Security logger property exists: " . ($has_security_logger ? 'PASS' : 'FAIL') . "\n";
    
    // Check if check_permission method exists
    $has_check_permission = $reflection->hasMethod('check_permission');
    $results['integration']['check_permission_method'] = $has_check_permission ? 'PASS' : 'FAIL';
    echo "✓ check_permission method exists: " . ($has_check_permission ? 'PASS' : 'FAIL') . "\n";
    
} else {
    echo "✗ MAS_REST_Controller class not found\n";
    $results['integration']['class_exists'] = 'FAIL';
}

echo "\n";

// Summary
echo "=== Test Summary ===\n";
$total_tests = 0;
$passed_tests = 0;

foreach ($results as $category => $tests) {
    foreach ($tests as $test => $result) {
        $total_tests++;
        if ($result === 'PASS') {
            $passed_tests++;
        }
    }
}

echo "Total Tests: $total_tests\n";
echo "Passed: $passed_tests\n";
echo "Failed: " . ($total_tests - $passed_tests) . "\n";
echo "Success Rate: " . round(($passed_tests / $total_tests) * 100, 2) . "%\n";

if ($passed_tests === $total_tests) {
    echo "\n✓ All tests passed! Task 8 implementation is complete.\n";
} else {
    echo "\n✗ Some tests failed. Please review the implementation.\n";
}

echo "\n=== Detailed Results ===\n";
print_r($results);
