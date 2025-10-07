<?php
/**
 * Phase 2 Cache Hit Rate Verification Script
 * 
 * Verifies cache hit rate meets target (>80%):
 * - Monitors cache performance
 * - Optimizes cache warming strategy
 * - Tunes cache expiration times
 * 
 * Run: php test-phase2-cache-verification.php
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
    wp_set_current_user(1);
}

echo "=== Phase 2 Cache Hit Rate Verification ===\n\n";
echo "Target: >80% cache hit rate\n\n";

// Load required services
require_once __DIR__ . '/includes/services/class-mas-cache-service.php';
require_once __DIR__ . '/includes/services/class-mas-cache-monitor.php';

$cache_monitor = new MAS_Cache_Monitor();
$cache_service = new MAS_Cache_Service();

// Step 1: Run verification test
echo "Step 1: Running Cache Verification Test\n";
echo "========================================\n\n";

$test_results = $cache_monitor->run_verification_test(100);

echo "Test Results (100 iterations):\n";
echo "  - Hits: {$test_results['hits']}\n";
echo "  - Misses: {$test_results['misses']}\n";
echo "  - Hit Rate: {$test_results['hit_rate']}%\n";
echo "  - Avg Retrieval Time: {$test_results['avg_retrieval_time_ms']}ms\n";
echo "  - Target: 80%\n";
echo "  - Status: " . ($test_results['meets_target'] ? '✅ PASS' : '❌ FAIL') . "\n\n";

// Step 2: Get current hit rate
echo "Step 2: Current Cache Performance\n";
echo "==================================\n\n";

$hit_rate_data = $cache_monitor->get_hit_rate();

echo "Current Statistics:\n";
echo "  - Hit Rate: {$hit_rate_data['hit_rate']}%\n";
echo "  - Hits: {$hit_rate_data['hits']}\n";
echo "  - Misses: {$hit_rate_data['misses']}\n";
echo "  - Total Requests: {$hit_rate_data['total_requests']}\n";
echo "  - Meets Target: " . ($hit_rate_data['meets_target'] ? 'Yes ✅' : 'No ❌') . "\n\n";

// Step 3: Monitor performance over time
echo "Step 3: Performance Monitoring (24h)\n";
echo "====================================\n\n";

$monitoring = $cache_monitor->monitor_performance(24);

if ($monitoring['status'] !== 'insufficient_data') {
    echo "Monitoring Analysis:\n";
    echo "  - Duration: " . round($monitoring['duration_hours'], 2) . " hours\n";
    echo "  - Samples: {$monitoring['sample_count']}\n";
    echo "  - Average Hit Rate: {$monitoring['avg_hit_rate']}%\n";
    echo "  - Min Hit Rate: {$monitoring['min_hit_rate']}%\n";
    echo "  - Max Hit Rate: {$monitoring['max_hit_rate']}%\n";
    echo "  - Trend: {$monitoring['trend']}\n";
    echo "  - Meets Target: " . ($monitoring['meets_target'] ? 'Yes ✅' : 'No ❌') . "\n\n";
    
    if (!empty($monitoring['recommendations'])) {
        echo "Recommendations:\n";
        foreach ($monitoring['recommendations'] as $rec) {
            $icon = $rec['severity'] === 'warning' ? '⚠️' : ($rec['severity'] === 'success' ? '✅' : 'ℹ️');
            echo "  $icon {$rec['type']}: {$rec['message']}\n";
            if (!empty($rec['actions'])) {
                echo "     Actions:\n";
                foreach ($rec['actions'] as $action) {
                    echo "     - $action\n";
                }
            }
            echo "\n";
        }
    }
} else {
    echo "⚠️  {$monitoring['message']}\n";
    echo "   Running initial monitoring...\n\n";
}

// Step 4: Optimize cache strategy
echo "Step 4: Cache Strategy Optimization\n";
echo "===================================\n\n";

$optimization = $cache_monitor->optimize_cache_strategy();

echo "Optimizations Applied: {$optimization['optimizations_applied']}\n\n";

if (!empty($optimization['details'])) {
    foreach ($optimization['details'] as $detail) {
        echo "  ✅ {$detail['action']}: {$detail['message']}\n";
        if (isset($detail['keys'])) {
            echo "     Keys: " . implode(', ', $detail['keys']) . "\n";
        }
        if (isset($detail['recommendation'])) {
            echo "     → {$detail['recommendation']}\n";
        }
    }
    echo "\n";
}

echo "New Hit Rate: {$optimization['new_hit_rate']['hit_rate']}%\n";
echo "Meets Target: " . ($optimization['new_hit_rate']['meets_target'] ? 'Yes ✅' : 'No ❌') . "\n\n";

// Step 5: Get comprehensive performance report
echo "Step 5: Comprehensive Performance Report\n";
echo "=========================================\n\n";

$report = $cache_monitor->get_performance_report();

echo "System Information:\n";
echo "  - Object Cache: " . ($report['system_info']['using_object_cache'] ? 'Yes ✅' : 'No ❌') . "\n";
echo "  - Cache Backend: {$report['system_info']['cache_backend']}\n";
echo "  - Memory Limit: {$report['system_info']['memory_limit']}\n";
echo "  - OPcache: " . ($report['system_info']['opcache_enabled'] ? 'Enabled ✅' : 'Disabled ❌') . "\n\n";

echo "Cache Statistics:\n";
echo "  - Total Hits: {$report['cache_statistics']['hits']}\n";
echo "  - Total Misses: {$report['cache_statistics']['misses']}\n";
echo "  - Hit Rate: {$report['cache_statistics']['hit_rate']}%\n\n";

// Step 6: Stress test cache under load
echo "Step 6: Cache Stress Test\n";
echo "=========================\n\n";

echo "Running stress test with 1000 operations...\n";

$stress_test_results = [
    'operations' => 1000,
    'start_time' => microtime(true)
];

$operations = ['get', 'set', 'delete'];
$stress_hits = 0;
$stress_misses = 0;

for ($i = 0; $i < 1000; $i++) {
    $operation = $operations[array_rand($operations)];
    $key = 'stress_test_' . ($i % 100); // Reuse 100 keys
    
    switch ($operation) {
        case 'get':
            $result = $cache_service->get($key);
            if ($result !== false) {
                $stress_hits++;
            } else {
                $stress_misses++;
            }
            break;
        case 'set':
            $cache_service->set($key, ['data' => $i], 300);
            break;
        case 'delete':
            $cache_service->delete($key);
            break;
    }
}

$stress_test_results['end_time'] = microtime(true);
$stress_test_results['duration'] = $stress_test_results['end_time'] - $stress_test_results['start_time'];
$stress_test_results['ops_per_second'] = $stress_test_results['operations'] / $stress_test_results['duration'];
$stress_test_results['hits'] = $stress_hits;
$stress_test_results['misses'] = $stress_misses;
$stress_test_results['hit_rate'] = $stress_hits > 0 ? ($stress_hits / ($stress_hits + $stress_misses)) * 100 : 0;

echo "Stress Test Results:\n";
echo "  - Operations: {$stress_test_results['operations']}\n";
echo "  - Duration: " . round($stress_test_results['duration'], 2) . "s\n";
echo "  - Ops/Second: " . round($stress_test_results['ops_per_second'], 2) . "\n";
echo "  - Hit Rate: " . round($stress_test_results['hit_rate'], 2) . "%\n";
echo "  - Status: " . ($stress_test_results['hit_rate'] >= 80 ? '✅ PASS' : '❌ FAIL') . "\n\n";

// Step 7: Summary
echo "Step 7: Verification Summary\n";
echo "============================\n\n";

$all_tests_passed = 
    $test_results['meets_target'] &&
    $hit_rate_data['meets_target'] &&
    $optimization['new_hit_rate']['meets_target'] &&
    $stress_test_results['hit_rate'] >= 80;

echo "Test Results:\n";
echo "  - Verification Test: " . ($test_results['meets_target'] ? '✅ PASS' : '❌ FAIL') . "\n";
echo "  - Current Hit Rate: " . ($hit_rate_data['meets_target'] ? '✅ PASS' : '❌ FAIL') . "\n";
echo "  - After Optimization: " . ($optimization['new_hit_rate']['meets_target'] ? '✅ PASS' : '❌ FAIL') . "\n";
echo "  - Stress Test: " . ($stress_test_results['hit_rate'] >= 80 ? '✅ PASS' : '❌ FAIL') . "\n\n";

echo "Overall Status: " . ($all_tests_passed ? '✅ ALL TESTS PASSED' : '❌ SOME TESTS FAILED') . "\n\n";

if (!$all_tests_passed) {
    echo "⚠️  Cache hit rate is below target. Recommendations:\n";
    if (!$report['system_info']['using_object_cache']) {
        echo "  1. Install persistent object cache (Redis/Memcached)\n";
    }
    echo "  2. Increase cache expiration times\n";
    echo "  3. Warm cache more frequently\n";
    echo "  4. Review cache invalidation strategy\n\n";
}

// Save report
$full_report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'verification_test' => $test_results,
    'current_performance' => $hit_rate_data,
    'monitoring' => $monitoring,
    'optimization' => $optimization,
    'stress_test' => $stress_test_results,
    'system_info' => $report['system_info'],
    'all_tests_passed' => $all_tests_passed
];

$filename = __DIR__ . '/cache-verification-report-' . date('Y-m-d-His') . '.json';
file_put_contents($filename, json_encode($full_report, JSON_PRETTY_PRINT));

echo "Verification report saved to: $filename\n";
echo "\n✅ Cache verification complete!\n";
