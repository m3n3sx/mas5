<?php
/**
 * Phase 2 Performance Benchmarking Test Script
 * 
 * Run this script to benchmark Phase 2 endpoints:
 * php test-phase2-performance-benchmarks.php
 * 
 * @package ModernAdminStylerV2
 */

// Load WordPress
require_once __DIR__ . '/../../../../wp-load.php';

if (!defined('ABSPATH')) {
    die('WordPress not loaded');
}

// Ensure user is admin
if (!current_user_can('manage_options')) {
    wp_set_current_user(1); // Set to admin user
}

echo "=== Phase 2 Performance Benchmarking ===\n\n";
echo "Testing against performance targets:\n";
echo "- Settings retrieval with ETag: <50ms for 304\n";
echo "- Settings save with backup: <500ms\n";
echo "- Batch operations: <1000ms for 10 items\n";
echo "- System health check: <300ms\n\n";

/**
 * Helper function to benchmark an operation
 */
function benchmark_operation($name, $callback, $iterations, $target_ms) {
    echo "Benchmarking: $name\n";
    echo "Target: <{$target_ms}ms\n";
    
    $times = [];
    
    for ($i = 0; $i < $iterations; $i++) {
        $start = microtime(true);
        $callback();
        $end = microtime(true);
        
        $times[] = ($end - $start) * 1000; // Convert to milliseconds
    }
    
    // Calculate statistics
    sort($times);
    $min = min($times);
    $max = max($times);
    $avg = array_sum($times) / count($times);
    $median = $times[floor(count($times) / 2)];
    $p95_index = ceil(0.95 * count($times)) - 1;
    $p95 = $times[$p95_index];
    $p99_index = ceil(0.99 * count($times)) - 1;
    $p99 = $times[$p99_index];
    
    $passed = $p95 <= $target_ms;
    $status = $passed ? '✅ PASS' : '❌ FAIL';
    
    echo sprintf(
        "%s - Min: %.2fms | Avg: %.2fms | Median: %.2fms | P95: %.2fms | P99: %.2fms\n",
        $status,
        $min,
        $avg,
        $median,
        $p95,
        $p99
    );
    echo "\n";
    
    return [
        'name' => $name,
        'target' => $target_ms,
        'min' => $min,
        'max' => $max,
        'avg' => $avg,
        'median' => $median,
        'p95' => $p95,
        'p99' => $p99,
        'passed' => $passed,
        'iterations' => $iterations
    ];
}

$results = [];

// Benchmark 1: Settings retrieval with caching
$results[] = benchmark_operation(
    'Settings Retrieval (cached)',
    function() {
        $settings_service = new MAS_Settings_Service();
        $settings_service->get_settings();
    },
    100,
    50
);

// Benchmark 2: Settings save with backup
$results[] = benchmark_operation(
    'Settings Save with Backup',
    function() {
        $settings_service = new MAS_Settings_Service();
        $backup_service = new MAS_Backup_Retention_Service();
        
        // Create backup
        $backup_service->create_backup('automatic');
        
        // Save settings
        $settings_service->save_settings([
            'menu_background' => '#1e1e2e',
            'menu_text_color' => '#ffffff'
        ]);
    },
    20,
    500
);

// Benchmark 3: System health check
$results[] = benchmark_operation(
    'System Health Check',
    function() {
        $health_service = new MAS_System_Health_Service();
        $health_service->get_health_status();
    },
    50,
    300
);

// Benchmark 4: Theme preview generation
$results[] = benchmark_operation(
    'Theme Preview Generation',
    function() {
        $theme_service = new MAS_Theme_Preset_Service();
        $theme_service->preview_theme([
            'settings' => [
                'menu_background' => '#1e1e2e',
                'menu_text_color' => '#ffffff',
                'menu_hover_background' => '#2d2d44'
            ]
        ]);
    },
    50,
    200
);

// Benchmark 5: Cache operations
$results[] = benchmark_operation(
    'Cache Get/Set Operations',
    function() {
        $cache_service = new MAS_Cache_Service();
        $cache_service->set('test_key', ['data' => 'test'], 300);
        $cache_service->get('test_key');
    },
    100,
    10
);

// Benchmark 6: Analytics query
$results[] = benchmark_operation(
    'Analytics Usage Query',
    function() {
        $analytics_service = new MAS_Analytics_Service();
        $analytics_service->get_usage_stats(
            date('Y-m-d', strtotime('-7 days')),
            date('Y-m-d')
        );
    },
    30,
    250
);

// Benchmark 7: Rate limit check
$results[] = benchmark_operation(
    'Rate Limit Check',
    function() {
        $rate_limiter = new MAS_Rate_Limiter_Service();
        try {
            $rate_limiter->check_rate_limit('default');
        } catch (Exception $e) {
            // Expected if limit exceeded
        }
    },
    100,
    5
);

// Benchmark 8: Audit log query
$results[] = benchmark_operation(
    'Audit Log Query',
    function() {
        $security_logger = new MAS_Security_Logger_Service();
        $security_logger->get_audit_log(50, 0);
    },
    30,
    100
);

// Print summary
echo "=== Benchmark Summary ===\n\n";

$passed = 0;
$failed = 0;

foreach ($results as $result) {
    if ($result['passed']) {
        $passed++;
    } else {
        $failed++;
    }
}

echo "Total Benchmarks: " . count($results) . "\n";
echo "Passed: $passed ✅\n";
echo "Failed: $failed " . ($failed > 0 ? '❌' : '') . "\n\n";

if ($failed > 0) {
    echo "Failed Benchmarks:\n";
    foreach ($results as $result) {
        if (!$result['passed']) {
            echo sprintf(
                "  - %s: P95 %.2fms (target: %.2fms, exceeded by %.2fms)\n",
                $result['name'],
                $result['p95'],
                $result['target'],
                $result['p95'] - $result['target']
            );
        }
    }
    echo "\n";
}

// Save results
$output = [
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'wordpress_version' => get_bloginfo('version'),
    'plugin_version' => defined('MAS_VERSION') ? MAS_VERSION : '2.3.0',
    'server_info' => [
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status()
    ],
    'results' => $results
];

$filename = __DIR__ . '/benchmark-results-' . date('Y-m-d-His') . '.json';
file_put_contents($filename, json_encode($output, JSON_PRETTY_PRINT));

echo "Results saved to: $filename\n";
echo "\n✅ Benchmark complete!\n";
