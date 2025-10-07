<?php
/**
 * Test Script for Phase 2 Task 8: Analytics and Monitoring
 * 
 * Tests the analytics service, controller, and monitoring features.
 * 
 * @package ModernAdminStylerV2
 * @since 2.3.0
 */

// Load WordPress
require_once dirname(__FILE__) . '/../../../wp-load.php';

// Ensure user is logged in and has admin capabilities
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('You must be logged in as an administrator to run this test.');
}

// Load required files
require_once dirname(__FILE__) . '/includes/services/class-mas-analytics-service.php';
require_once dirname(__FILE__) . '/includes/api/class-mas-rest-controller.php';
require_once dirname(__FILE__) . '/includes/api/class-mas-analytics-controller.php';
require_once dirname(__FILE__) . '/includes/services/class-mas-rate-limiter-service.php';
require_once dirname(__FILE__) . '/includes/services/class-mas-security-logger-service.php';

/**
 * Test Results Tracker
 */
class TestResults {
    private $tests = [];
    private $passed = 0;
    private $failed = 0;
    
    public function add($name, $passed, $message = '') {
        $this->tests[] = [
            'name' => $name,
            'passed' => $passed,
            'message' => $message
        ];
        
        if ($passed) {
            $this->passed++;
        } else {
            $this->failed++;
        }
    }
    
    public function get_summary() {
        return [
            'total' => count($this->tests),
            'passed' => $this->passed,
            'failed' => $this->failed,
            'tests' => $this->tests
        ];
    }
}

$results = new TestResults();

echo "<h1>Phase 2 Task 8: Analytics and Monitoring Tests</h1>\n";
echo "<style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; margin: 20px; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .test-pass { color: #0a0; font-weight: bold; }
    .test-fail { color: #a00; font-weight: bold; }
    .test-item { margin: 10px 0; padding: 10px; background: #f9f9f9; border-left: 3px solid #ddd; }
    .test-item.pass { border-left-color: #0a0; }
    .test-item.fail { border-left-color: #a00; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
    .summary { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0; }
</style>\n";

// Test 1: Analytics Service Initialization
echo "<div class='test-section'>\n";
echo "<h2>Test 1: Analytics Service Initialization</h2>\n";

try {
    $analytics_service = new MAS_Analytics_Service();
    $results->add('Analytics Service Creation', true, 'Service created successfully');
    echo "<div class='test-item pass'><span class='test-pass'>✓ PASS:</span> Analytics service created successfully</div>\n";
} catch (Exception $e) {
    $results->add('Analytics Service Creation', false, $e->getMessage());
    echo "<div class='test-item fail'><span class='test-fail'>✗ FAIL:</span> " . esc_html($e->getMessage()) . "</div>\n";
}

echo "</div>\n";

// Test 2: Track API Calls
echo "<div class='test-section'>\n";
echo "<h2>Test 2: Track API Calls</h2>\n";

try {
    // Track some sample API calls
    $test_calls = [
        ['/settings', 'GET', 150, 200],
        ['/settings', 'POST', 350, 200],
        ['/themes', 'GET', 120, 200],
        ['/backups', 'POST', 450, 200],
        ['/settings', 'GET', 180, 200],
        ['/themes', 'GET', 400, 404], // Error
        ['/settings', 'POST', 500, 500], // Server error
    ];
    
    foreach ($test_calls as $call) {
        $analytics_service->track_api_call($call[0], $call[1], $call[2], $call[3]);
    }
    
    $results->add('Track API Calls', true, 'Tracked ' . count($test_calls) . ' API calls');
    echo "<div class='test-item pass'><span class='test-pass'>✓ PASS:</span> Tracked " . count($test_calls) . " API calls successfully</div>\n";
} catch (Exception $e) {
    $results->add('Track API Calls', false, $e->getMessage());
    echo "<div class='test-item fail'><span class='test-fail'>✗ FAIL:</span> " . esc_html($e->getMessage()) . "</div>\n";
}

echo "</div>\n";

// Test 3: Get Usage Statistics
echo "<div class='test-section'>\n";
echo "<h2>Test 3: Get Usage Statistics</h2>\n";

try {
    $usage_stats = $analytics_service->get_usage_stats();
    
    $has_required_fields = isset($usage_stats['total_requests']) &&
                          isset($usage_stats['by_endpoint']) &&
                          isset($usage_stats['by_method']) &&
                          isset($usage_stats['by_status']);
    
    if ($has_required_fields) {
        $results->add('Usage Statistics', true, 'Retrieved usage statistics with all required fields');
        echo "<div class='test-item pass'><span class='test-pass'>✓ PASS:</span> Usage statistics retrieved successfully</div>\n";
        echo "<pre>" . print_r($usage_stats, true) . "</pre>\n";
    } else {
        $results->add('Usage Statistics', false, 'Missing required fields in usage statistics');
        echo "<div class='test-item fail'><span class='test-fail'>✗ FAIL:</span> Missing required fields in usage statistics</div>\n";
    }
} catch (Exception $e) {
    $results->add('Usage Statistics', false, $e->getMessage());
    echo "<div class='test-item fail'><span class='test-fail'>✗ FAIL:</span> " . esc_html($e->getMessage()) . "</div>\n";
}

echo "</div>\n";

// Test 4: Get Performance Percentiles
echo "<div class='test-section'>\n";
echo "<h2>Test 4: Get Performance Percentiles</h2>\n";

try {
    $percentiles = $analytics_service->get_performance_percentiles();
    
    $has_percentiles = isset($percentiles['p50']) &&
                      isset($percentiles['p75']) &&
                      isset($percentiles['p90']) &&
                      isset($percentiles['p95']) &&
                      isset($percentiles['p99']) &&
                      isset($percentiles['avg']);
    
    if ($has_percentiles) {
        $results->add('Performance Percentiles', true, 'Retrieved all performance percentiles');
        echo "<div class='test-item pass'><span class='test-pass'>✓ PASS:</span> Performance percentiles retrieved successfully</div>\n";
        echo "<pre>" . print_r($percentiles, true) . "</pre>\n";
    } else {
        $results->add('Performance Percentiles', false, 'Missing percentile values');
        echo "<div class='test-item fail'><span class='test-fail'>✗ FAIL:</span> Missing percentile values</div>\n";
    }
} catch (Exception $e) {
    $results->add('Performance Percentiles', false, $e->getMessage());
    echo "<div class='test-item fail'><span class='test-fail'>✗ FAIL:</span> " . esc_html($e->getMessage()) . "</div>\n";
}

echo "</div>\n";

// Test 5: Get Error Statistics
echo "<div class='test-section'>\n";
echo "<h2>Test 5: Get Error Statistics</h2>\n";

try {
    $error_stats = $analytics_service->get_error_stats();
    
    $has_error_fields = isset($error_stats['total_requests']) &&
                       isset($error_stats['error_requests']) &&
                       isset($error_stats['error_rate']) &&
                       isset($error_stats['client_errors']) &&
                       isset($error_stats['server_errors']);
    
    if ($has_error_fields) {
        $results->add('Error Statistics', true, 'Retrieved error statistics with all required fields');
        echo "<div class='test-item pass'><span class='test-pass'>✓ PASS:</span> Error statistics retrieved successfully</div>\n";
        echo "<pre>" . print_r($error_stats, true) . "</pre>\n";
    } else {
        $results->add('Error Statistics', false, 'Missing required fields in error statistics');
        echo "<div class='test-item fail'><span class='test-fail'>✗ FAIL:</span> Missing required fields in error statistics</div>\n";
    }
} catch (Exception $e) {
    $results->add('Error Statistics', false, $e->getMessage());
    echo "<div class='test-item fail'><span class='test-fail'>✗ FAIL:</span> " . esc_html($e->getMessage()) . "</div>\n";
}

echo "</div>\n";

// Test 6: Export to CSV
echo "<div class='test-section'>\n";
echo "<h2>Test 6: Export to CSV</h2>\n";

try {
    $csv_content = $analytics_service->export_to_csv();
    
    $has_csv_header = strpos($csv_content, 'Endpoint,Method,Response Time') !== false;
    $has_csv_data = substr_count($csv_content, "\n") > 1;
    
    if ($has_csv_header && $has_csv_data) {
        $results->add('CSV Export', true, 'CSV export generated successfully');
        echo "<div class='test-item pass'><span class='test-pass'>✓ PASS:</span> CSV export generated successfully</div>\n";
        echo "<pre>" . esc_html(substr($csv_content, 0, 500)) . "...</pre>\n";
    } else {
        $results->add('CSV Export', false, 'CSV export missing header or data');
        echo "<div class='test-item fail'><span class='test-fail'>✗ FAIL:</span> CSV export missing header or data</div>\n";
    }
} catch (Exception $e) {
    $results->add('CSV Export', false, $e->getMessage());
    echo "<div class='test-item fail'><span class='test-fail'>✗ FAIL:</span> " . esc_html($e->getMessage()) . "</div>\n";
}

echo "</div>\n";

// Test 7: Performance Threshold Checking
echo "<div class='test-section'>\n";
echo "<h2>Test 7: Performance Threshold Checking</h2>\n";

try {
    $alerts = $analytics_service->check_performance_thresholds([
        'avg_response_time' => 100, // Low threshold to trigger alert
        'p95_response_time' => 200,
        'error_rate' => 1.0
    ]);
    
    $results->add('Performance Thresholds', true, 'Threshold checking completed with ' . count($alerts) . ' alerts');
    echo "<div class='test-item pass'><span class='test-pass'>✓ PASS:</span> Performance threshold checking completed</div>\n";
    echo "<p>Alerts generated: " . count($alerts) . "</p>\n";
    if (!empty($alerts)) {
        echo "<pre>" . print_r($alerts, true) . "</pre>\n";
    }
} catch (Exception $e) {
    $results->add('Performance Thresholds', false, $e->getMessage());
    echo "<div class='test-item fail'><span class='test-fail'>✗ FAIL:</span> " . esc_html($e->getMessage()) . "</div>\n";
}

echo "</div>\n";

// Test 8: Optimization Recommendations
echo "<div class='test-section'>\n";
echo "<h2>Test 8: Optimization Recommendations</h2>\n";

try {
    $recommendations = $analytics_service->generate_optimization_recommendations();
    
    $results->add('Optimization Recommendations', true, 'Generated ' . count($recommendations) . ' recommendations');
    echo "<div class='test-item pass'><span class='test-pass'>✓ PASS:</span> Optimization recommendations generated</div>\n";
    echo "<p>Recommendations: " . count($recommendations) . "</p>\n";
    if (!empty($recommendations)) {
        echo "<pre>" . print_r($recommendations, true) . "</pre>\n";
    }
} catch (Exception $e) {
    $results->add('Optimization Recommendations', false, $e->getMessage());
    echo "<div class='test-item fail'><span class='test-fail'>✗ FAIL:</span> " . esc_html($e->getMessage()) . "</div>\n";
}

echo "</div>\n";

// Test 9: Analytics Controller Initialization
echo "<div class='test-section'>\n";
echo "<h2>Test 9: Analytics Controller Initialization</h2>\n";

try {
    $controller = new MAS_Analytics_Controller();
    $results->add('Analytics Controller', true, 'Controller created successfully');
    echo "<div class='test-item pass'><span class='test-pass'>✓ PASS:</span> Analytics controller created successfully</div>\n";
} catch (Exception $e) {
    $results->add('Analytics Controller', false, $e->getMessage());
    echo "<div class='test-item fail'><span class='test-fail'>✗ FAIL:</span> " . esc_html($e->getMessage()) . "</div>\n";
}

echo "</div>\n";

// Test 10: REST API Endpoints
echo "<div class='test-section'>\n";
echo "<h2>Test 10: REST API Endpoints</h2>\n";

$endpoints_to_test = [
    '/wp-json/mas-v2/v1/analytics/usage',
    '/wp-json/mas-v2/v1/analytics/performance',
    '/wp-json/mas-v2/v1/analytics/errors',
    '/wp-json/mas-v2/v1/analytics/export'
];

foreach ($endpoints_to_test as $endpoint) {
    $url = home_url($endpoint);
    $response = wp_remote_get($url, [
        'headers' => [
            'X-WP-Nonce' => wp_create_nonce('wp_rest')
        ],
        'cookies' => $_COOKIE
    ]);
    
    if (!is_wp_error($response)) {
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code === 200 || $status_code === 304) {
            $results->add('Endpoint: ' . $endpoint, true, 'Status: ' . $status_code);
            echo "<div class='test-item pass'><span class='test-pass'>✓ PASS:</span> {$endpoint} (Status: {$status_code})</div>\n";
        } else {
            $results->add('Endpoint: ' . $endpoint, false, 'Status: ' . $status_code);
            echo "<div class='test-item fail'><span class='test-fail'>✗ FAIL:</span> {$endpoint} (Status: {$status_code})</div>\n";
        }
    } else {
        $results->add('Endpoint: ' . $endpoint, false, $response->get_error_message());
        echo "<div class='test-item fail'><span class='test-fail'>✗ FAIL:</span> {$endpoint} - " . esc_html($response->get_error_message()) . "</div>\n";
    }
}

echo "</div>\n";

// Display Summary
$summary = $results->get_summary();

echo "<div class='summary'>\n";
echo "<h2>Test Summary</h2>\n";
echo "<p><strong>Total Tests:</strong> {$summary['total']}</p>\n";
echo "<p><strong class='test-pass'>Passed:</strong> {$summary['passed']}</p>\n";
echo "<p><strong class='test-fail'>Failed:</strong> {$summary['failed']}</p>\n";
echo "<p><strong>Success Rate:</strong> " . round(($summary['passed'] / $summary['total']) * 100, 2) . "%</p>\n";
echo "</div>\n";

// Cleanup
echo "<div class='test-section'>\n";
echo "<h2>Cleanup</h2>\n";
echo "<p>Note: Test data has been added to the analytics database. You may want to clean it up manually if needed.</p>\n";
echo "</div>\n";

echo "<p><a href='" . admin_url() . "'>← Back to Dashboard</a></p>\n";
