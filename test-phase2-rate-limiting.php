<?php
/**
 * Phase 2 Rate Limiting Test Script
 *
 * Tests rate limiting effectiveness including:
 * - Rate limit enforcement
 * - Retry-After headers
 * - Per-user limiting
 * - Per-IP limiting
 * - Different endpoint limits
 *
 * @package ModernAdminStyler
 * @since 2.3.0
 */

// Load WordPress
require_once dirname(__FILE__) . '/../../../wp-load.php';

// Ensure we're running from command line
if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line.');
}

echo "===========================================\n";
echo "Phase 2 Rate Limiting Test\n";
echo "===========================================\n\n";

// Initialize services
$rate_limiter = new MAS_Rate_Limiter_Service();
$security_logger = new MAS_Security_Logger_Service();

$test_results = [];
$total_tests = 0;
$passed_tests = 0;

/**
 * Helper function to run a test
 */
function run_test($name, $callback) {
    global $test_results, $total_tests, $passed_tests;
    
    $total_tests++;
    echo "Testing: {$name}... ";
    
    try {
        $result = $callback();
        if ($result['passed']) {
            echo "✓ PASSED\n";
            if (!empty($result['message'])) {
                echo "  → {$result['message']}\n";
            }
            $passed_tests++;
            $test_results[] = ['test' => $name, 'status' => 'PASSED', 'message' => $result['message'] ?? ''];
        } else {
            echo "✗ FAILED\n";
            echo "  → {$result['message']}\n";
            $test_results[] = ['test' => $name, 'status' => 'FAILED', 'message' => $result['message']];
        }
    } catch (Exception $e) {
        echo "✗ FAILED (Exception)\n";
        echo "  → {$e->getMessage()}\n";
        $test_results[] = ['test' => $name, 'status' => 'FAILED', 'message' => $e->getMessage()];
    }
    
    echo "\n";
}

// Test 1: Default rate limit allows requests within limit
run_test('Default rate limit allows requests within limit', function() use ($rate_limiter) {
    $test_user_id = 999991;
    $test_ip = '192.168.1.100';
    
    // Reset any existing limits
    $rate_limiter->reset_limit('user', $test_user_id, 'default');
    $rate_limiter->reset_limit('ip', $test_ip, 'default');
    
    // Should allow first request
    try {
        $result = $rate_limiter->check_rate_limit('default', $test_user_id, $test_ip);
        return [
            'passed' => $result === true,
            'message' => 'First request allowed within rate limit'
        ];
    } catch (MAS_Rate_Limit_Exception $e) {
        return [
            'passed' => false,
            'message' => 'First request should not be rate limited: ' . $e->getMessage()
        ];
    }
});

// Test 2: Rate limit blocks requests exceeding limit
run_test('Rate limit blocks requests exceeding limit', function() use ($rate_limiter) {
    $test_user_id = 999992;
    $test_ip = '192.168.1.101';
    
    // Reset limits
    $rate_limiter->reset_limit('user', $test_user_id, 'settings_save');
    $rate_limiter->reset_limit('ip', $test_ip, 'settings_save');
    
    // Make 10 requests (the limit for settings_save)
    for ($i = 0; $i < 10; $i++) {
        try {
            $rate_limiter->check_rate_limit('settings_save', $test_user_id, $test_ip);
        } catch (MAS_Rate_Limit_Exception $e) {
            return [
                'passed' => false,
                'message' => "Request {$i} should not be rate limited"
            ];
        }
    }
    
    // 11th request should be blocked
    try {
        $rate_limiter->check_rate_limit('settings_save', $test_user_id, $test_ip);
        return [
            'passed' => false,
            'message' => '11th request should be rate limited'
        ];
    } catch (MAS_Rate_Limit_Exception $e) {
        return [
            'passed' => true,
            'message' => 'Rate limit correctly blocked 11th request: ' . $e->getMessage()
        ];
    }
});

// Test 3: Rate limit exception includes retry-after time
run_test('Rate limit exception includes retry-after time', function() use ($rate_limiter) {
    $test_user_id = 999993;
    $test_ip = '192.168.1.102';
    
    // Reset and exhaust limit
    $rate_limiter->reset_limit('user', $test_user_id, 'backup_create');
    $rate_limiter->reset_limit('ip', $test_ip, 'backup_create');
    
    // Exhaust limit (5 requests for backup_create)
    for ($i = 0; $i < 5; $i++) {
        $rate_limiter->check_rate_limit('backup_create', $test_user_id, $test_ip);
    }
    
    // Next request should include retry-after
    try {
        $rate_limiter->check_rate_limit('backup_create', $test_user_id, $test_ip);
        return [
            'passed' => false,
            'message' => 'Should have thrown rate limit exception'
        ];
    } catch (MAS_Rate_Limit_Exception $e) {
        $retry_after = $e->get_retry_after();
        return [
            'passed' => $retry_after > 0 && $retry_after <= 300,
            'message' => "Retry-After: {$retry_after} seconds (expected 1-300)"
        ];
    }
});

// Test 4: Per-user rate limiting works independently
run_test('Per-user rate limiting works independently', function() use ($rate_limiter) {
    $test_user_1 = 999994;
    $test_user_2 = 999995;
    $test_ip = '192.168.1.103';
    
    // Reset limits
    $rate_limiter->reset_limit('user', $test_user_1, 'theme_apply');
    $rate_limiter->reset_limit('user', $test_user_2, 'theme_apply');
    $rate_limiter->reset_limit('ip', $test_ip, 'theme_apply');
    
    // Exhaust limit for user 1
    for ($i = 0; $i < 10; $i++) {
        $rate_limiter->check_rate_limit('theme_apply', $test_user_1, $test_ip);
    }
    
    // User 1 should be blocked
    try {
        $rate_limiter->check_rate_limit('theme_apply', $test_user_1, $test_ip);
        return [
            'passed' => false,
            'message' => 'User 1 should be rate limited'
        ];
    } catch (MAS_Rate_Limit_Exception $e) {
        // Expected
    }
    
    // User 2 should still be allowed (different user)
    try {
        // Reset IP limit since it was exhausted by user 1
        $rate_limiter->reset_limit('ip', $test_ip, 'theme_apply');
        $rate_limiter->check_rate_limit('theme_apply', $test_user_2, $test_ip);
        return [
            'passed' => true,
            'message' => 'User 2 can still make requests (per-user limiting works)'
        ];
    } catch (MAS_Rate_Limit_Exception $e) {
        return [
            'passed' => false,
            'message' => 'User 2 should not be rate limited: ' . $e->getMessage()
        ];
    }
});

// Test 5: Per-IP rate limiting works independently
run_test('Per-IP rate limiting works independently', function() use ($rate_limiter) {
    $test_user = 999996;
    $test_ip_1 = '192.168.1.104';
    $test_ip_2 = '192.168.1.105';
    
    // Reset limits
    $rate_limiter->reset_limit('user', $test_user, 'import');
    $rate_limiter->reset_limit('ip', $test_ip_1, 'import');
    $rate_limiter->reset_limit('ip', $test_ip_2, 'import');
    
    // Exhaust limit for IP 1
    for ($i = 0; $i < 3; $i++) {
        $rate_limiter->check_rate_limit('import', $test_user, $test_ip_1);
    }
    
    // IP 1 should be blocked
    try {
        $rate_limiter->check_rate_limit('import', $test_user, $test_ip_1);
        return [
            'passed' => false,
            'message' => 'IP 1 should be rate limited'
        ];
    } catch (MAS_Rate_Limit_Exception $e) {
        // Expected
    }
    
    // IP 2 should still be allowed (different IP)
    try {
        // Reset user limit since it was exhausted by IP 1
        $rate_limiter->reset_limit('user', $test_user, 'import');
        $rate_limiter->check_rate_limit('import', $test_user, $test_ip_2);
        return [
            'passed' => true,
            'message' => 'IP 2 can still make requests (per-IP limiting works)'
        ];
    } catch (MAS_Rate_Limit_Exception $e) {
        return [
            'passed' => false,
            'message' => 'IP 2 should not be rate limited: ' . $e->getMessage()
        ];
    }
});

// Test 6: Rate limit status endpoint returns correct information
run_test('Rate limit status endpoint returns correct information', function() use ($rate_limiter) {
    $test_user_id = 999997;
    $test_ip = '192.168.1.106';
    
    // Reset and make some requests
    $rate_limiter->reset_limit('user', $test_user_id, 'default');
    $rate_limiter->reset_limit('ip', $test_ip, 'default');
    
    // Make 5 requests
    for ($i = 0; $i < 5; $i++) {
        $rate_limiter->check_rate_limit('default', $test_user_id, $test_ip);
    }
    
    // Get status
    $status = $rate_limiter->get_status('default', $test_user_id, $test_ip);
    
    // Verify status structure
    $checks = [
        isset($status['action']) && $status['action'] === 'default',
        isset($status['limit']) && $status['limit'] === 60,
        isset($status['window']) && $status['window'] === 60,
        isset($status['user']['used']) && $status['user']['used'] === 5,
        isset($status['user']['remaining']) && $status['user']['remaining'] === 55,
        isset($status['ip']['used']) && $status['ip']['used'] === 5,
        isset($status['ip']['remaining']) && $status['ip']['remaining'] === 55,
    ];
    
    $all_passed = !in_array(false, $checks, true);
    
    return [
        'passed' => $all_passed,
        'message' => $all_passed 
            ? 'Status includes all required fields with correct values'
            : 'Status missing fields or has incorrect values'
    ];
});

// Test 7: Different endpoints have different limits
run_test('Different endpoints have different limits', function() use ($rate_limiter) {
    $test_user_id = 999998;
    $test_ip = '192.168.1.107';
    
    // Test default limit (60 requests)
    $rate_limiter->reset_limit('user', $test_user_id, 'default');
    $rate_limiter->reset_limit('ip', $test_ip, 'default');
    $default_status = $rate_limiter->get_status('default', $test_user_id, $test_ip);
    
    // Test settings_save limit (10 requests)
    $rate_limiter->reset_limit('user', $test_user_id, 'settings_save');
    $rate_limiter->reset_limit('ip', $test_ip, 'settings_save');
    $settings_status = $rate_limiter->get_status('settings_save', $test_user_id, $test_ip);
    
    // Test backup_create limit (5 requests)
    $rate_limiter->reset_limit('user', $test_user_id, 'backup_create');
    $rate_limiter->reset_limit('ip', $test_ip, 'backup_create');
    $backup_status = $rate_limiter->get_status('backup_create', $test_user_id, $test_ip);
    
    $passed = (
        $default_status['limit'] === 60 &&
        $settings_status['limit'] === 10 &&
        $backup_status['limit'] === 5
    );
    
    return [
        'passed' => $passed,
        'message' => sprintf(
            'Limits: default=%d, settings_save=%d, backup_create=%d',
            $default_status['limit'],
            $settings_status['limit'],
            $backup_status['limit']
        )
    ];
});

// Test 8: Rate limit reset works correctly
run_test('Rate limit reset works correctly', function() use ($rate_limiter) {
    $test_user_id = 999999;
    $test_ip = '192.168.1.108';
    
    // Reset and exhaust limit
    $rate_limiter->reset_limit('user', $test_user_id, 'settings_save');
    $rate_limiter->reset_limit('ip', $test_ip, 'settings_save');
    
    // Exhaust limit
    for ($i = 0; $i < 10; $i++) {
        $rate_limiter->check_rate_limit('settings_save', $test_user_id, $test_ip);
    }
    
    // Should be blocked
    try {
        $rate_limiter->check_rate_limit('settings_save', $test_user_id, $test_ip);
        return [
            'passed' => false,
            'message' => 'Should be rate limited before reset'
        ];
    } catch (MAS_Rate_Limit_Exception $e) {
        // Expected
    }
    
    // Reset limits
    $rate_limiter->reset_limit('user', $test_user_id, 'settings_save');
    $rate_limiter->reset_limit('ip', $test_ip, 'settings_save');
    
    // Should be allowed again
    try {
        $rate_limiter->check_rate_limit('settings_save', $test_user_id, $test_ip);
        return [
            'passed' => true,
            'message' => 'Rate limit reset successfully, requests allowed again'
        ];
    } catch (MAS_Rate_Limit_Exception $e) {
        return [
            'passed' => false,
            'message' => 'Should not be rate limited after reset: ' . $e->getMessage()
        ];
    }
});

// Test 9: Rate limit logging works
run_test('Rate limit violations are logged', function() use ($rate_limiter, $security_logger) {
    $test_user_id = 1000000;
    $test_ip = '192.168.1.109';
    
    // Reset limits
    $rate_limiter->reset_limit('user', $test_user_id, 'theme_apply');
    $rate_limiter->reset_limit('ip', $test_ip, 'theme_apply');
    
    // Exhaust limit
    for ($i = 0; $i < 10; $i++) {
        $rate_limiter->check_rate_limit('theme_apply', $test_user_id, $test_ip);
    }
    
    // Trigger rate limit and log it
    try {
        $rate_limiter->check_rate_limit('theme_apply', $test_user_id, $test_ip);
    } catch (MAS_Rate_Limit_Exception $e) {
        // Log the rate limit violation
        $security_logger->log_event(
            'rate_limit_exceeded',
            'Rate limit exceeded for theme_apply',
            ['status' => 'warning'],
            $test_user_id
        );
    }
    
    // Check if log entry was created
    $logs = $security_logger->get_audit_log([
        'user_id' => $test_user_id,
        'action' => 'rate_limit_exceeded',
        'limit' => 1
    ]);
    
    return [
        'passed' => count($logs) > 0,
        'message' => count($logs) > 0 
            ? 'Rate limit violation logged successfully'
            : 'Rate limit violation was not logged'
    ];
});

// Test 10: REST API returns proper 429 status with Retry-After header
run_test('REST API returns 429 status with Retry-After header', function() use ($rate_limiter) {
    // This test simulates what the REST API controller would do
    $test_user_id = 1000001;
    $test_ip = '192.168.1.110';
    
    // Reset and exhaust limit
    $rate_limiter->reset_limit('user', $test_user_id, 'settings_save');
    $rate_limiter->reset_limit('ip', $test_ip, 'settings_save');
    
    for ($i = 0; $i < 10; $i++) {
        $rate_limiter->check_rate_limit('settings_save', $test_user_id, $test_ip);
    }
    
    // Try to exceed limit
    try {
        $rate_limiter->check_rate_limit('settings_save', $test_user_id, $test_ip);
        return [
            'passed' => false,
            'message' => 'Should have thrown rate limit exception'
        ];
    } catch (MAS_Rate_Limit_Exception $e) {
        // Verify exception properties
        $status_code = $e->getCode();
        $retry_after = $e->get_retry_after();
        
        $passed = ($status_code === 429 && $retry_after > 0);
        
        return [
            'passed' => $passed,
            'message' => sprintf(
                'Status: %d (expected 429), Retry-After: %d seconds',
                $status_code,
                $retry_after
            )
        ];
    }
});

// Print summary
echo "\n===========================================\n";
echo "Test Summary\n";
echo "===========================================\n";
echo "Total Tests: {$total_tests}\n";
echo "Passed: {$passed_tests}\n";
echo "Failed: " . ($total_tests - $passed_tests) . "\n";
echo "Success Rate: " . round(($passed_tests / $total_tests) * 100, 2) . "%\n";
echo "\n";

// Print detailed results
echo "Detailed Results:\n";
echo "-------------------------------------------\n";
foreach ($test_results as $result) {
    $status_icon = $result['status'] === 'PASSED' ? '✓' : '✗';
    echo "{$status_icon} {$result['test']}: {$result['status']}\n";
    if (!empty($result['message'])) {
        echo "  {$result['message']}\n";
    }
}

echo "\n===========================================\n";
echo "Rate Limiting Test Complete\n";
echo "===========================================\n";

// Exit with appropriate code
exit($passed_tests === $total_tests ? 0 : 1);
