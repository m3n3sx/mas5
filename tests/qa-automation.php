<?php
/**
 * Automated QA Testing Script
 * 
 * This script performs automated quality assurance testing
 * for the REST API migration implementation.
 */

class MAS_QA_Automation {
    
    private $results = [];
    private $base_url;
    private $nonce;
    
    public function __construct() {
        $this->base_url = rest_url('mas-v2/v1');
        $this->nonce = wp_create_nonce('wp_rest');
    }
    
    /**
     * Run all QA tests
     */
    public function run_all_tests() {
        echo "Starting Automated QA Testing...\n";
        
        $this->test_endpoint_availability();
        $this->test_authentication();
        $this->test_validation();
        $this->test_performance();
        $this->test_error_handling();
        
        $this->generate_report();
    }
    
    /**
     * Test endpoint availability
     */
    private function test_endpoint_availability() {
        echo "Testing endpoint availability...\n";
        
        $endpoints = [
            '/settings',
            '/themes',
            '/backups',
            '/export',
            '/import',
            '/preview',
            '/diagnostics'
        ];
        
        foreach ($endpoints as $endpoint) {
            $response = $this->make_request('GET', $endpoint);
            $this->record_result(
                "Endpoint {$endpoint} available",
                !is_wp_error($response) && $response['response']['code'] !== 404
            );
        }
    }
    
    /**
     * Test authentication requirements
     */
    private function test_authentication() {
        echo "Testing authentication...\n";
        
        // Test without authentication
        $response = $this->make_request('GET', '/settings', [], false);
        $this->record_result(
            'Unauthenticated request rejected',
            is_wp_error($response) || $response['response']['code'] === 401
        );
        
        // Test with authentication
        $response = $this->make_request('GET', '/settings', [], true);
        $this->record_result(
            'Authenticated request accepted',
            !is_wp_error($response) && $response['response']['code'] === 200
        );
    }    

    /**
     * Test input validation
     */
    private function test_validation() {
        echo "Testing input validation...\n";
        
        // Test invalid color
        $response = $this->make_request('POST', '/settings', [
            'menu_background' => 'invalid-color'
        ]);
        $this->record_result(
            'Invalid color rejected',
            is_wp_error($response) || $response['response']['code'] === 400
        );
        
        // Test valid settings
        $response = $this->make_request('POST', '/settings', [
            'menu_background' => '#1e1e2e',
            'menu_text_color' => '#ffffff'
        ]);
        $this->record_result(
            'Valid settings accepted',
            !is_wp_error($response) && $response['response']['code'] === 200
        );
    }
    
    /**
     * Test performance requirements
     */
    private function test_performance() {
        echo "Testing performance...\n";
        
        // Test settings retrieval time
        $start_time = microtime(true);
        $response = $this->make_request('GET', '/settings');
        $end_time = microtime(true);
        $duration = ($end_time - $start_time) * 1000; // Convert to milliseconds
        
        $this->record_result(
            'Settings retrieval under 200ms',
            $duration < 200,
            "Actual: {$duration}ms"
        );
        
        // Test settings save time
        $start_time = microtime(true);
        $response = $this->make_request('POST', '/settings', [
            'menu_background' => '#2d2d44'
        ]);
        $end_time = microtime(true);
        $duration = ($end_time - $start_time) * 1000;
        
        $this->record_result(
            'Settings save under 500ms',
            $duration < 500,
            "Actual: {$duration}ms"
        );
    }
    
    /**
     * Test error handling
     */
    private function test_error_handling() {
        echo "Testing error handling...\n";
        
        // Test 404 for non-existent endpoint
        $response = $this->make_request('GET', '/non-existent');
        $this->record_result(
            '404 returned for non-existent endpoint',
            is_wp_error($response) || $response['response']['code'] === 404
        );
        
        // Test malformed JSON
        $response = wp_remote_post($this->base_url . '/settings', [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-WP-Nonce' => $this->nonce
            ],
            'body' => '{"invalid": json}'
        ]);
        
        $this->record_result(
            'Malformed JSON rejected',
            is_wp_error($response) || $response['response']['code'] === 400
        );
    }
    
    /**
     * Make HTTP request to REST API
     */
    private function make_request($method, $endpoint, $data = [], $authenticate = true) {
        $url = $this->base_url . $endpoint;
        $headers = ['Content-Type' => 'application/json'];
        
        if ($authenticate) {
            $headers['X-WP-Nonce'] = $this->nonce;
        }
        
        $args = [
            'method' => $method,
            'headers' => $headers,
            'timeout' => 30
        ];
        
        if (!empty($data)) {
            $args['body'] = json_encode($data);
        }
        
        return wp_remote_request($url, $args);
    }
    
    /**
     * Record test result
     */
    private function record_result($test_name, $passed, $details = '') {
        $this->results[] = [
            'test' => $test_name,
            'passed' => $passed,
            'details' => $details
        ];
        
        $status = $passed ? 'PASS' : 'FAIL';
        echo "  {$status}: {$test_name}";
        if ($details) {
            echo " ({$details})";
        }
        echo "\n";
    }
    
    /**
     * Generate test report
     */
    private function generate_report() {
        $total = count($this->results);
        $passed = count(array_filter($this->results, function($r) { return $r['passed']; }));
        $failed = $total - $passed;
        
        echo "\n=== QA Test Report ===\n";
        echo "Total Tests: {$total}\n";
        echo "Passed: {$passed}\n";
        echo "Failed: {$failed}\n";
        echo "Success Rate: " . round(($passed / $total) * 100, 2) . "%\n";
        
        if ($failed > 0) {
            echo "\nFailed Tests:\n";
            foreach ($this->results as $result) {
                if (!$result['passed']) {
                    echo "  - {$result['test']}";
                    if ($result['details']) {
                        echo " ({$result['details']})";
                    }
                    echo "\n";
                }
            }
        }
        
        // Save report to file
        $report_data = [
            'timestamp' => current_time('mysql'),
            'total' => $total,
            'passed' => $passed,
            'failed' => $failed,
            'success_rate' => round(($passed / $total) * 100, 2),
            'results' => $this->results
        ];
        
        file_put_contents(
            WP_CONTENT_DIR . '/mas-qa-report.json',
            json_encode($report_data, JSON_PRETTY_PRINT)
        );
        
        echo "\nReport saved to: " . WP_CONTENT_DIR . "/mas-qa-report.json\n";
    }
}

// Run QA tests if called directly
if (defined('WP_CLI') && WP_CLI) {
    $qa = new MAS_QA_Automation();
    $qa->run_all_tests();
}