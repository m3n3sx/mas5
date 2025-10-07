<?php
/**
 * Phase 2 Performance Benchmarking Script
 * 
 * Benchmarks all Phase 2 endpoints against performance targets:
 * - Settings retrieval with ETag: <50ms for 304
 * - Settings save with backup: <500ms
 * - Batch operations: <1000ms for 10 items
 * - System health check: <300ms
 * 
 * @package ModernAdminStylerV2
 */

// Load WordPress
require_once dirname(__FILE__) . '/../../../modern-admin-styler-v2.php';

class MAS_Phase2_Benchmark {
    
    private $results = [];
    private $rest_server;
    private $admin_user_id;
    
    public function __construct() {
        $this->setup_environment();
    }
    
    private function setup_environment() {
        // Create admin user for testing
        $this->admin_user_id = wp_insert_user([
            'user_login' => 'benchmark_admin_' . time(),
            'user_pass' => wp_generate_password(),
            'role' => 'administrator'
        ]);
        
        wp_set_current_user($this->admin_user_id);
        
        // Initialize REST server
        global $wp_rest_server;
        $this->rest_server = $wp_rest_server = new WP_REST_Server();
        do_action('rest_api_init');
    }
    
    public function run_all_benchmarks() {
        echo "=== Phase 2 Performance Benchmarking ===\n\n";
        
        $this->benchmark_settings_retrieval_with_etag();
        $this->benchmark_settings_save_with_backup();
        $this->benchmark_batch_operations();
        $this->benchmark_system_health_check();
        $this->benchmark_theme_preview();
        $this->benchmark_backup_download();
        $this->benchmark_webhook_delivery();
        $this->benchmark_analytics_query();
        
        $this->print_summary();
        $this->cleanup();
    }
    
    /**
     * Benchmark 1: Settings retrieval with ETag (target: <50ms for 304)
     */
    private function benchmark_settings_retrieval_with_etag() {
        echo "Benchmark 1: Settings Retrieval with ETag\n";
        echo "Target: <50ms for 304 Not Modified response\n";
        
        // First request to get ETag
        $request = new WP_REST_Request('GET', '/mas-v2/v1/settings');
        $response = $this->rest_server->dispatch($request);
        $etag = $response->get_headers()['ETag'] ?? null;
        
        if (!$etag) {
            echo "❌ FAIL: No ETag header in response\n\n";
            return;
        }
        
        // Benchmark conditional request with ETag
        $times = [];
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            $request = new WP_REST_Request('GET', '/mas-v2/v1/settings');
            $request->set_header('If-None-Match', $etag);
            
            $start = microtime(true);
            $response = $this->rest_server->dispatch($request);
            $end = microtime(true);
            
            $times[] = ($end - $start) * 1000; // Convert to milliseconds
            
            if ($response->get_status() !== 304) {
                echo "❌ FAIL: Expected 304 status, got {$response->get_status()}\n";
            }
        }
        
        $this->record_result('settings_etag', $times, 50);
    }
    
    /**
     * Benchmark 2: Settings save with backup (target: <500ms)
     */
    private function benchmark_settings_save_with_backup() {
        echo "\nBenchmark 2: Settings Save with Automatic Backup\n";
        echo "Target: <500ms\n";
        
        $times = [];
        $iterations = 50;
        
        $test_settings = [
            'menu_background' => '#1e1e2e',
            'menu_text_color' => '#ffffff',
            'menu_hover_background' => '#2d2d44',
            'admin_bar_background' => '#1e1e2e'
        ];
        
        for ($i = 0; $i < $iterations; $i++) {
            $request = new WP_REST_Request('POST', '/mas-v2/v1/settings');
            $request->set_header('Content-Type', 'application/json');
            $request->set_body(json_encode($test_settings));
            
            $start = microtime(true);
            $response = $this->rest_server->dispatch($request);
            $end = microtime(true);
            
            $times[] = ($end - $start) * 1000;
            
            if ($response->get_status() !== 200) {
                echo "❌ FAIL: Expected 200 status, got {$response->get_status()}\n";
            }
        }
        
        $this->record_result('settings_save_with_backup', $times, 500);
    }
    
    /**
     * Benchmark 3: Batch operations (target: <1000ms for 10 items)
     */
    private function benchmark_batch_operations() {
        echo "\nBenchmark 3: Batch Operations (10 items)\n";
        echo "Target: <1000ms\n";
        
        $times = [];
        $iterations = 20;
        
        // Create batch of 10 operations
        $operations = [];
        for ($i = 0; $i < 10; $i++) {
            $operations[] = [
                'type' => 'update_setting',
                'data' => [
                    'key' => 'menu_background',
                    'value' => sprintf('#%06x', mt_rand(0, 0xFFFFFF))
                ]
            ];
        }
        
        for ($i = 0; $i < $iterations; $i++) {
            $request = new WP_REST_Request('POST', '/mas-v2/v1/settings/batch');
            $request->set_header('Content-Type', 'application/json');
            $request->set_body(json_encode(['operations' => $operations]));
            
            $start = microtime(true);
            $response = $this->rest_server->dispatch($request);
            $end = microtime(true);
            
            $times[] = ($end - $start) * 1000;
            
            if ($response->get_status() !== 200) {
                echo "❌ FAIL: Expected 200 status, got {$response->get_status()}\n";
            }
        }
        
        $this->record_result('batch_operations_10_items', $times, 1000);
    }
    
    /**
     * Benchmark 4: System health check (target: <300ms)
     */
    private function benchmark_system_health_check() {
        echo "\nBenchmark 4: System Health Check\n";
        echo "Target: <300ms\n";
        
        $times = [];
        $iterations = 50;
        
        for ($i = 0; $i < $iterations; $i++) {
            $request = new WP_REST_Request('GET', '/mas-v2/v1/system/health');
            
            $start = microtime(true);
            $response = $this->rest_server->dispatch($request);
            $end = microtime(true);
            
            $times[] = ($end - $start) * 1000;
            
            if ($response->get_status() !== 200) {
                echo "❌ FAIL: Expected 200 status, got {$response->get_status()}\n";
            }
        }
        
        $this->record_result('system_health_check', $times, 300);
    }
    
    /**
     * Benchmark 5: Theme preview generation
     */
    private function benchmark_theme_preview() {
        echo "\nBenchmark 5: Theme Preview Generation\n";
        echo "Target: <200ms\n";
        
        $times = [];
        $iterations = 50;
        
        $theme_data = [
            'settings' => [
                'menu_background' => '#1e1e2e',
                'menu_text_color' => '#ffffff',
                'menu_hover_background' => '#2d2d44'
            ]
        ];
        
        for ($i = 0; $i < $iterations; $i++) {
            $request = new WP_REST_Request('POST', '/mas-v2/v1/themes/preview');
            $request->set_header('Content-Type', 'application/json');
            $request->set_body(json_encode($theme_data));
            
            $start = microtime(true);
            $response = $this->rest_server->dispatch($request);
            $end = microtime(true);
            
            $times[] = ($end - $start) * 1000;
        }
        
        $this->record_result('theme_preview', $times, 200);
    }
    
    /**
     * Benchmark 6: Backup download
     */
    private function benchmark_backup_download() {
        echo "\nBenchmark 6: Backup Download\n";
        echo "Target: <150ms\n";
        
        // Create a test backup first
        $backup_service = new MAS_Backup_Retention_Service();
        $backup = $backup_service->create_backup('manual', 'Benchmark test');
        
        $times = [];
        $iterations = 50;
        
        for ($i = 0; $i < $iterations; $i++) {
            $request = new WP_REST_Request('GET', '/mas-v2/v1/backups/' . $backup['id'] . '/download');
            
            $start = microtime(true);
            $response = $this->rest_server->dispatch($request);
            $end = microtime(true);
            
            $times[] = ($end - $start) * 1000;
        }
        
        $this->record_result('backup_download', $times, 150);
    }
    
    /**
     * Benchmark 7: Webhook delivery simulation
     */
    private function benchmark_webhook_delivery() {
        echo "\nBenchmark 7: Webhook Delivery (simulated)\n";
        echo "Target: <100ms\n";
        
        $times = [];
        $iterations = 30;
        
        $webhook_service = new MAS_Webhook_Service();
        
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            
            // Simulate webhook preparation (without actual HTTP call)
            $payload = [
                'event' => 'settings.updated',
                'data' => ['menu_background' => '#1e1e2e'],
                'timestamp' => time()
            ];
            $body = json_encode($payload);
            $signature = hash_hmac('sha256', $body, 'test_secret');
            
            $end = microtime(true);
            
            $times[] = ($end - $start) * 1000;
        }
        
        $this->record_result('webhook_delivery_prep', $times, 100);
    }
    
    /**
     * Benchmark 8: Analytics query
     */
    private function benchmark_analytics_query() {
        echo "\nBenchmark 8: Analytics Query\n";
        echo "Target: <250ms\n";
        
        $times = [];
        $iterations = 30;
        
        for ($i = 0; $i < $iterations; $i++) {
            $request = new WP_REST_Request('GET', '/mas-v2/v1/analytics/usage');
            $request->set_param('start_date', date('Y-m-d', strtotime('-7 days')));
            $request->set_param('end_date', date('Y-m-d'));
            
            $start = microtime(true);
            $response = $this->rest_server->dispatch($request);
            $end = microtime(true);
            
            $times[] = ($end - $start) * 1000;
        }
        
        $this->record_result('analytics_query', $times, 250);
    }
    
    /**
     * Record benchmark result
     */
    private function record_result($name, $times, $target) {
        $min = min($times);
        $max = max($times);
        $avg = array_sum($times) / count($times);
        $median = $this->calculate_median($times);
        $p95 = $this->calculate_percentile($times, 95);
        $p99 = $this->calculate_percentile($times, 99);
        
        $passed = $p95 <= $target;
        
        $this->results[$name] = [
            'target' => $target,
            'min' => $min,
            'max' => $max,
            'avg' => $avg,
            'median' => $median,
            'p95' => $p95,
            'p99' => $p99,
            'passed' => $passed,
            'iterations' => count($times)
        ];
        
        $status = $passed ? '✅ PASS' : '❌ FAIL';
        
        echo sprintf(
            "%s - Min: %.2fms | Avg: %.2fms | Median: %.2fms | P95: %.2fms | P99: %.2fms | Target: %.2fms\n",
            $status,
            $min,
            $avg,
            $median,
            $p95,
            $p99,
            $target
        );
    }
    
    /**
     * Calculate median
     */
    private function calculate_median($values) {
        sort($values);
        $count = count($values);
        $middle = floor($count / 2);
        
        if ($count % 2 == 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        }
        
        return $values[$middle];
    }
    
    /**
     * Calculate percentile
     */
    private function calculate_percentile($values, $percentile) {
        sort($values);
        $index = ceil(($percentile / 100) * count($values)) - 1;
        return $values[$index];
    }
    
    /**
     * Print summary
     */
    private function print_summary() {
        echo "\n=== Benchmark Summary ===\n\n";
        
        $passed = 0;
        $failed = 0;
        
        foreach ($this->results as $name => $result) {
            if ($result['passed']) {
                $passed++;
            } else {
                $failed++;
            }
        }
        
        echo "Total Benchmarks: " . count($this->results) . "\n";
        echo "Passed: $passed\n";
        echo "Failed: $failed\n\n";
        
        if ($failed > 0) {
            echo "Failed Benchmarks:\n";
            foreach ($this->results as $name => $result) {
                if (!$result['passed']) {
                    echo sprintf(
                        "  - %s: P95 %.2fms (target: %.2fms, exceeded by %.2fms)\n",
                        $name,
                        $result['p95'],
                        $result['target'],
                        $result['p95'] - $result['target']
                    );
                }
            }
        }
        
        // Save results to file
        $this->save_results();
    }
    
    /**
     * Save results to JSON file
     */
    private function save_results() {
        $output = [
            'timestamp' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'plugin_version' => MAS_VERSION ?? '2.3.0',
            'results' => $this->results
        ];
        
        $filename = dirname(__FILE__) . '/benchmark-results-' . date('Y-m-d-His') . '.json';
        file_put_contents($filename, json_encode($output, JSON_PRETTY_PRINT));
        
        echo "\nResults saved to: $filename\n";
    }
    
    /**
     * Cleanup
     */
    private function cleanup() {
        // Delete test user
        if ($this->admin_user_id) {
            wp_delete_user($this->admin_user_id);
        }
    }
}

// Run benchmarks
$benchmark = new MAS_Phase2_Benchmark();
$benchmark->run_all_benchmarks();
