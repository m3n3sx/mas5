<?php
/**
 * Phase 2 Performance Optimization Script
 * 
 * Profiles and optimizes slow operations:
 * - Database query optimization
 * - Cache optimization
 * - Webhook delivery optimization
 * 
 * Run: php test-phase2-performance-optimization.php
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

echo "=== Phase 2 Performance Optimization ===\n\n";

// Load required services
require_once __DIR__ . '/includes/services/class-mas-cache-service.php';
require_once __DIR__ . '/includes/services/class-mas-database-optimizer.php';
require_once __DIR__ . '/includes/services/class-mas-performance-profiler.php';

$profiler = new MAS_Performance_Profiler();
$db_optimizer = MAS_Database_Optimizer::get_instance();
$cache_service = new MAS_Cache_Service();

echo "Step 1: Analyzing Current Performance\n";
echo "=====================================\n\n";

// Get database stats
$db_stats = $db_optimizer->get_stats();
echo "Database Statistics:\n";
echo "  - Total plugin options: {$db_stats['total_options']}\n";
echo "  - Backup count: {$db_stats['backup_count']}\n";
echo "  - Transient count: {$db_stats['transient_count']}\n";
echo "  - Options table size: {$db_stats['options_table_size_mb']} MB\n";
echo "  - External cache: " . ($db_stats['cache_enabled'] ? 'Yes ✅' : 'No ❌') . "\n\n";

// Get cache stats
$cache_stats = $cache_service->get_stats();
echo "Cache Statistics:\n";
echo "  - Hit rate: {$cache_stats['hit_rate']}%\n";
echo "  - Hits: {$cache_stats['hits']}\n";
echo "  - Misses: {$cache_stats['misses']}\n\n";

// Get slow operations
$slow_ops = $profiler->get_slow_operations();
echo "Slow Operations (last 24h): " . count($slow_ops) . "\n";
if (count($slow_ops) > 0) {
    echo "Recent slow operations:\n";
    foreach (array_slice($slow_ops, -5) as $op) {
        echo sprintf(
            "  - %s: %.2fms (%d queries, %.2fMB)\n",
            $op['name'],
            $op['duration_ms'],
            $op['query_count'],
            $op['memory_used_mb']
        );
    }
}
echo "\n";

echo "Step 2: Running Optimizations\n";
echo "=============================\n\n";

// 1. Database optimization
echo "Optimizing database...\n";
$db_results = $profiler->optimize_database_queries();

echo "  ✅ Indexes checked/added: " . count($db_results['indexes']) . "\n";
if (count($db_results['indexes']) > 0) {
    foreach ($db_results['indexes'] as $index) {
        echo "     - {$index['table']}.{$index['column']} ({$index['status']})\n";
    }
}

echo "  ✅ Expired transients cleaned: {$db_results['transients_cleaned']}\n";
echo "  ✅ Tables optimized: " . count($db_results['tables_optimized']) . "\n";

if (!empty($db_results['query_analysis']['recommendations'])) {
    echo "  ⚠️  Query analysis recommendations:\n";
    foreach ($db_results['query_analysis']['recommendations'] as $rec) {
        echo "     - {$rec['message']}\n";
    }
}
echo "\n";

// 2. Cache optimization
echo "Optimizing cache...\n";
$cache_service->warm_cache();
echo "  ✅ Cache warmed with frequently accessed data\n";

// Get updated cache stats
$new_cache_stats = $cache_service->get_stats();
echo "  ✅ New cache hit rate: {$new_cache_stats['hit_rate']}%\n\n";

// 3. Profile key operations
echo "Step 3: Profiling Key Operations\n";
echo "=================================\n\n";

// Profile settings retrieval
echo "Profiling settings retrieval...\n";
$settings_profile = $profiler->profile_callback('settings_retrieval', function() {
    $settings_service = new MAS_Settings_Service();
    return $settings_service->get_settings();
});
echo sprintf(
    "  Duration: %.2fms | Queries: %d | Memory: %.2fMB\n",
    $settings_profile['profile']['duration_ms'],
    $settings_profile['profile']['query_count'],
    $settings_profile['profile']['memory_used_mb']
);

// Profile system health check
echo "\nProfiling system health check...\n";
$health_profile = $profiler->profile_callback('system_health_check', function() {
    $health_service = new MAS_System_Health_Service();
    return $health_service->get_health_status();
});
echo sprintf(
    "  Duration: %.2fms | Queries: %d | Memory: %.2fMB\n",
    $health_profile['profile']['duration_ms'],
    $health_profile['profile']['query_count'],
    $health_profile['profile']['memory_used_mb']
);

// Profile backup creation
echo "\nProfiling backup creation...\n";
$backup_profile = $profiler->profile_callback('backup_creation', function() {
    $backup_service = new MAS_Backup_Retention_Service();
    return $backup_service->create_backup('automatic', 'Performance test');
});
echo sprintf(
    "  Duration: %.2fms | Queries: %d | Memory: %.2fMB\n",
    $backup_profile['profile']['duration_ms'],
    $backup_profile['profile']['query_count'],
    $backup_profile['profile']['memory_used_mb']
);

echo "\n";

// 4. Get optimization recommendations
echo "Step 4: Optimization Recommendations\n";
echo "====================================\n\n";

$recommendations = $profiler->get_optimization_recommendations();

if (empty($recommendations)) {
    echo "✅ No optimization recommendations - system is performing well!\n";
} else {
    foreach ($recommendations as $rec) {
        $icon = $rec['severity'] === 'warning' ? '⚠️' : 'ℹ️';
        echo "$icon {$rec['type']}: {$rec['message']}\n";
        echo "   Action: {$rec['action']}\n\n";
    }
}

// 5. Summary
echo "Step 5: Optimization Summary\n";
echo "============================\n\n";

$summary = [
    'database_optimizations' => count($db_results['indexes']) + count($db_results['tables_optimized']),
    'transients_cleaned' => $db_results['transients_cleaned'],
    'cache_warmed' => true,
    'slow_operations_found' => count($slow_ops),
    'recommendations' => count($recommendations)
];

echo "Optimizations Applied:\n";
echo "  - Database optimizations: {$summary['database_optimizations']}\n";
echo "  - Transients cleaned: {$summary['transients_cleaned']}\n";
echo "  - Cache warmed: " . ($summary['cache_warmed'] ? 'Yes ✅' : 'No') . "\n";
echo "  - Slow operations found: {$summary['slow_operations_found']}\n";
echo "  - Active recommendations: {$summary['recommendations']}\n\n";

// Performance comparison
echo "Performance Metrics:\n";
echo "  - Settings retrieval: " . ($settings_profile['profile']['duration_ms'] < 50 ? '✅' : '❌') . " ";
echo sprintf("%.2fms (target: <50ms)\n", $settings_profile['profile']['duration_ms']);

echo "  - Health check: " . ($health_profile['profile']['duration_ms'] < 300 ? '✅' : '❌') . " ";
echo sprintf("%.2fms (target: <300ms)\n", $health_profile['profile']['duration_ms']);

echo "  - Backup creation: " . ($backup_profile['profile']['duration_ms'] < 500 ? '✅' : '❌') . " ";
echo sprintf("%.2fms (target: <500ms)\n", $backup_profile['profile']['duration_ms']);

echo "\n✅ Optimization complete!\n";

// Save optimization report
$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'database_stats' => $db_stats,
    'cache_stats_before' => $cache_stats,
    'cache_stats_after' => $new_cache_stats,
    'optimizations' => $db_results,
    'profiles' => [
        'settings_retrieval' => $settings_profile['profile'],
        'system_health' => $health_profile['profile'],
        'backup_creation' => $backup_profile['profile']
    ],
    'recommendations' => $recommendations,
    'summary' => $summary
];

$filename = __DIR__ . '/optimization-report-' . date('Y-m-d-His') . '.json';
file_put_contents($filename, json_encode($report, JSON_PRETTY_PRINT));

echo "\nOptimization report saved to: $filename\n";
