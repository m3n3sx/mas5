<?php
/**
 * Performance Testing Script
 * 
 * Tests REST API performance under various load conditions
 */

class MAS_Performance_Test {
    
    private $base_url;
    private $nonce;
    private $results = [];
    
    public function __construct() {
        $this->base_url = rest_url('mas-v2/v1');
        $this->nonce = wp_create_nonce('wp_rest');
    }
    
    /**
     * Run performance tests
     */
    public function run_tests() {
        echo "Starting Performance Testing...\n";
        
        $this->test_single_requests();
        $this->test_concurrent_requests();
        $this->test_memory_usage();
        $this->test_cache_effectiveness();
        
        $this->generate_report();
    }
    
    /**
     * Test single request performance
     */
    private function test_single_requests() {
        echo "Testing single request performance...\n";
        
        $endpoints = [
            'GET /settings' => ['GET', '/settings'],
            'POST /settings' => ['POST', '/settings', ['menu_background' => '#1e1e2e']],
            'GET /themes' => ['GET', '/themes'],
            'POST /preview' => ['POST', '/preview', ['menu_background' => '#2d2d44']],
            'GET /diagnostics' => ['GET', '/diagnostics']
        ];
        
        foreach ($endpoints as $name => $config) {
            $times = [];
            
            // Run each test 10 times
            for ($i = 0; $i < 10; $i++) {
                $start = microtime(true);
                $this->make_request($config[0], $config[1], $config[2] ?? []);
                $end = microtime(true);
                $times[] = ($end - $start) * 1000; // Convert to milliseconds
            }
            
            $avg_time = array_sum($times) / count($times);
            $min_time = min($times);
            $max_time = max($times);
            
            $this->results['single_requests'][$name] = [
                'average' => $avg_time,
                'min' => $min_time,
                'max' => $max_time,
                'times' => $times
            ];
            
            echo "  {$name}: Avg " . number_format($avg_time, 2) . "ms, Min " . number_format($min_time, 2) . "ms, Max " . number_format($max_time, 2) . "ms\n";
        }
    }    

    /**
     * Test concurrent request handling
     */
    private function test_concurrent_requests() {
        echo "Testing concurrent request handling...\n";
        
        $concurrent_levels = [5, 10, 20];
        
        foreach ($concurrent_levels as $level) {
            echo "  Testing {$level} concurrent requests...\n";
            
            $start_time = microtime(true);
            $processes = [];
            
            // Simulate concurrent requests using curl_multi
            $multi_handle = curl_multi_init();
            $curl_handles = [];
            
            for ($i = 0; $i < $level; $i++) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $this->base_url . '/settings');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'X-WP-Nonce: ' . $this->nonce
                ]);
                
                curl_multi_add_handle($multi_handle, $ch);
                $curl_handles[] = $ch;
            }
            
            // Execute all requests
            $running = null;
            do {
                curl_multi_exec($multi_handle, $running);
                curl_multi_select($multi_handle);
            } while ($running > 0);
            
            $end_time = microtime(true);
            $total_time = ($end_time - $start_time) * 1000;
            
            // Clean up
            foreach ($curl_handles as $ch) {
                curl_multi_remove_handle($multi_handle, $ch);
                curl_close($ch);
            }
            curl_multi_close($multi_handle);
            
            $this->results['concurrent_requests'][$level] = [
                'total_time' => $total_time,
                'avg_per_request' => $total_time / $level
            ];
            
            echo "    Total time: " . number_format($total_time, 2) . "ms, Avg per request: " . 
                 number_format($total_time / $level, 2) . "ms\n";
        }
    }
    
    /**
     * Test memory usage
     */
    private function test_memory_usage() {
        echo "Testing memory usage...\n";
        
        $initial_memory = memory_get_usage();
        
        // Make multiple requests to test for memory leaks
        for ($i = 0; $i < 50; $i++) {
            $this->make_request('GET', '/settings');
            $this->make_request('POST', '/settings', ['menu_background' => '#' . dechex(rand(0, 16777215))]);
        }
        
        $final_memory = memory_get_usage();
        $memory_increase = $final_memory - $initial_memory;
        
        $this->results['memory_usage'] = [
            'initial' => $initial_memory,
            'final' => $final_memory,
            'increase' => $memory_increase,
            'increase_mb' => $memory_increase / 1024 / 1024
        ];
        
        echo "  Memory increase: " . ($memory_increase / 1024 / 1024) . " MB\n";
    }
    
    /**
     * Test cache effectiveness
     */
    private function test_cache_effectiveness() {
        echo "Testing cache effectiveness...\n";
        
        // First request (cache miss)
        $start = microtime(true);
        $this->make_request('GET', '/settings');
        $end = microtime(true);
        $first_request_time = ($end - $start) * 1000;
        
        // Second request (cache hit)
        $start = microtime(true);
        $this->make_request('GET', '/settings');
        $end = microtime(true);
        $second_request_time = ($end - $start) * 1000;
        
        $cache_improvement = (($first_request_time - $second_request_time) / $first_request_time) * 100;
        
        $this->results['cache_effectiveness'] = [
            'first_request' => $first_request_time,
            'second_request' => $second_request_time,
            'improvement_percent' => $cache_improvement
        ];
        
        echo "  First request: " . number_format($first_request_time, 2) . "ms\n";
        echo "  Second request: " . number_format($second_request_time, 2) . "ms\n";
        echo "  Cache improvement: " . number_format($cache_improvement, 1) . "%\n";
    }
    
    /**
     * Make HTTP request
     */
    private function make_request($method, $endpoint, $data = []) {
        $url = $this->base_url . $endpoint;
        
        $args = [
            'method' => $method,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-WP-Nonce' => $this->nonce
            ],
            'timeout' => 30
        ];
        
        if (!empty($data)) {
            $args['body'] = json_encode($data);
        }
        
        return wp_remote_request($url, $args);
    }
    
    /**
     * Generate performance report
     */
    private function generate_report() {
        echo "\n=== Performance Test Report ===\n";
        
        // Check if performance requirements are met
        $requirements_met = true;
        
        if (isset($this->results['single_requests']['GET /settings'])) {
            $settings_time = $this->results['single_requests']['GET /settings']['average'];
            echo "Settings retrieval: " . number_format($settings_time, 2) . "ms (requirement: <200ms) ";
            if ($settings_time < 200) {
                echo "✓\n";
            } else {
                echo "✗\n";
                $requirements_met = false;
            }
        }
        
        if (isset($this->results['single_requests']['POST /settings'])) {
            $save_time = $this->results['single_requests']['POST /settings']['average'];
            echo "Settings save: " . number_format($save_time, 2) . "ms (requirement: <500ms) ";
            if ($save_time < 500) {
                echo "✓\n";
            } else {
                echo "✗\n";
                $requirements_met = false;
            }
        }
        
        echo "\nConcurrent Request Handling:\n";
        foreach ($this->results['concurrent_requests'] ?? [] as $level => $data) {
            echo "  {$level} concurrent: " . number_format($data['total_time'], 2) . "ms total, " .
                 number_format($data['avg_per_request'], 2) . "ms avg\n";
        }
        
        echo "\nMemory Usage:\n";
        if (isset($this->results['memory_usage'])) {
            $memory = $this->results['memory_usage'];
            echo "  Memory increase: " . number_format($memory['increase_mb'], 2) . " MB\n";
            
            if ($memory['increase_mb'] > 10) {
                echo "  ⚠️  High memory usage detected\n";
                $requirements_met = false;
            }
        }
        
        echo "\nCache Effectiveness:\n";
        if (isset($this->results['cache_effectiveness'])) {
            $cache = $this->results['cache_effectiveness'];
            echo "  Cache improvement: " . number_format($cache['improvement_percent'], 1) . "%\n";
            
            if ($cache['improvement_percent'] < 10) {
                echo "  ⚠️  Low cache effectiveness\n";
            }
        }
        
        echo "\nOverall Performance: " . ($requirements_met ? "✓ PASS" : "✗ FAIL") . "\n";
        
        // Save detailed report
        $report_data = [
            'timestamp' => current_time('mysql'),
            'requirements_met' => $requirements_met,
            'results' => $this->results
        ];
        
        file_put_contents(
            WP_CONTENT_DIR . '/mas-performance-report.json',
            json_encode($report_data, JSON_PRETTY_PRINT)
        );
        
        echo "\nDetailed report saved to: " . WP_CONTENT_DIR . "/mas-performance-report.json\n";
    }
}

// Run performance tests if called directly
if (defined('WP_CLI') && WP_CLI) {
    $perf_test = new MAS_Performance_Test();
    $perf_test->run_tests();
}