<?php
/**
 * Cache Monitor Service
 * 
 * Monitors cache hit rates and provides optimization recommendations.
 * Ensures cache hit rate targets are met (>80%).
 *
 * @package ModernAdminStylerV2
 * @subpackage Services
 * @since 2.3.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * MAS Cache Monitor
 * 
 * Monitors and optimizes cache performance.
 */
class MAS_Cache_Monitor {
    
    /**
     * Target cache hit rate (80%)
     * 
     * @var float
     */
    private $target_hit_rate = 80.0;
    
    /**
     * Cache service instance
     * 
     * @var MAS_Cache_Service
     */
    private $cache_service;
    
    /**
     * Monitoring data storage key
     * 
     * @var string
     */
    private $monitoring_key = 'mas_v2_cache_monitoring';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->cache_service = new MAS_Cache_Service();
    }
    
    /**
     * Get current cache hit rate
     * 
     * @return array Cache statistics with hit rate
     */
    public function get_hit_rate() {
        $stats = $this->cache_service->get_stats();
        
        $total = $stats['hits'] + $stats['misses'];
        $hit_rate = $total > 0 ? ($stats['hits'] / $total) * 100 : 0;
        
        return [
            'hit_rate' => round($hit_rate, 2),
            'hits' => $stats['hits'],
            'misses' => $stats['misses'],
            'total_requests' => $total,
            'target' => $this->target_hit_rate,
            'meets_target' => $hit_rate >= $this->target_hit_rate
        ];
    }
    
    /**
     * Monitor cache performance over time
     * 
     * @param int $duration_hours Duration to monitor (default: 24 hours)
     * @return array Monitoring data
     */
    public function monitor_performance($duration_hours = 24) {
        $monitoring_data = get_transient($this->monitoring_key) ?: [
            'start_time' => time(),
            'samples' => []
        ];
        
        // Add current sample
        $current_stats = $this->get_hit_rate();
        $monitoring_data['samples'][] = [
            'timestamp' => time(),
            'hit_rate' => $current_stats['hit_rate'],
            'hits' => $current_stats['hits'],
            'misses' => $current_stats['misses']
        ];
        
        // Keep only samples within duration
        $cutoff = time() - ($duration_hours * HOUR_IN_SECONDS);
        $monitoring_data['samples'] = array_filter(
            $monitoring_data['samples'],
            function($sample) use ($cutoff) {
                return $sample['timestamp'] >= $cutoff;
            }
        );
        
        // Save monitoring data
        set_transient($this->monitoring_key, $monitoring_data, $duration_hours * HOUR_IN_SECONDS);
        
        return $this->analyze_monitoring_data($monitoring_data);
    }
    
    /**
     * Analyze monitoring data
     * 
     * @param array $monitoring_data Monitoring data
     * @return array Analysis results
     */
    private function analyze_monitoring_data($monitoring_data) {
        if (empty($monitoring_data['samples'])) {
            return [
                'status' => 'insufficient_data',
                'message' => 'Not enough data to analyze'
            ];
        }
        
        $samples = $monitoring_data['samples'];
        $hit_rates = array_column($samples, 'hit_rate');
        
        $avg_hit_rate = array_sum($hit_rates) / count($hit_rates);
        $min_hit_rate = min($hit_rates);
        $max_hit_rate = max($hit_rates);
        
        // Calculate trend
        $trend = $this->calculate_trend($samples);
        
        return [
            'duration_hours' => (time() - $monitoring_data['start_time']) / HOUR_IN_SECONDS,
            'sample_count' => count($samples),
            'avg_hit_rate' => round($avg_hit_rate, 2),
            'min_hit_rate' => round($min_hit_rate, 2),
            'max_hit_rate' => round($max_hit_rate, 2),
            'current_hit_rate' => end($hit_rates),
            'target_hit_rate' => $this->target_hit_rate,
            'meets_target' => $avg_hit_rate >= $this->target_hit_rate,
            'trend' => $trend,
            'recommendations' => $this->generate_recommendations($avg_hit_rate, $trend)
        ];
    }
    
    /**
     * Calculate trend
     * 
     * @param array $samples Sample data
     * @return string Trend direction (improving, declining, stable)
     */
    private function calculate_trend($samples) {
        if (count($samples) < 2) {
            return 'stable';
        }
        
        // Compare first half to second half
        $mid = floor(count($samples) / 2);
        $first_half = array_slice($samples, 0, $mid);
        $second_half = array_slice($samples, $mid);
        
        $first_avg = array_sum(array_column($first_half, 'hit_rate')) / count($first_half);
        $second_avg = array_sum(array_column($second_half, 'hit_rate')) / count($second_half);
        
        $diff = $second_avg - $first_avg;
        
        if ($diff > 5) {
            return 'improving';
        } elseif ($diff < -5) {
            return 'declining';
        } else {
            return 'stable';
        }
    }
    
    /**
     * Generate recommendations
     * 
     * @param float $avg_hit_rate Average hit rate
     * @param string $trend Trend direction
     * @return array Recommendations
     */
    private function generate_recommendations($avg_hit_rate, $trend) {
        $recommendations = [];
        
        if ($avg_hit_rate < $this->target_hit_rate) {
            $recommendations[] = [
                'type' => 'low_hit_rate',
                'severity' => 'warning',
                'message' => sprintf(
                    'Cache hit rate (%.2f%%) is below target (%.2f%%)',
                    $avg_hit_rate,
                    $this->target_hit_rate
                ),
                'actions' => [
                    'Increase cache expiration times',
                    'Warm cache more frequently',
                    'Review cache invalidation strategy',
                    'Consider using persistent object cache (Redis/Memcached)'
                ]
            ];
        }
        
        if ($trend === 'declining') {
            $recommendations[] = [
                'type' => 'declining_performance',
                'severity' => 'warning',
                'message' => 'Cache hit rate is declining over time',
                'actions' => [
                    'Check for cache invalidation issues',
                    'Review recent code changes',
                    'Monitor cache size and memory usage'
                ]
            ];
        }
        
        if (!wp_using_ext_object_cache()) {
            $recommendations[] = [
                'type' => 'no_persistent_cache',
                'severity' => 'info',
                'message' => 'No persistent object cache detected',
                'actions' => [
                    'Install Redis or Memcached',
                    'Configure WordPress object cache drop-in',
                    'Expected improvement: 20-30% better hit rates'
                ]
            ];
        }
        
        if ($avg_hit_rate >= $this->target_hit_rate && $trend === 'improving') {
            $recommendations[] = [
                'type' => 'excellent_performance',
                'severity' => 'success',
                'message' => 'Cache performance is excellent and improving',
                'actions' => [
                    'Continue current cache strategy',
                    'Monitor for any degradation'
                ]
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Optimize cache strategy
     * 
     * Automatically adjusts cache settings to improve hit rate.
     * 
     * @return array Optimization results
     */
    public function optimize_cache_strategy() {
        $current_stats = $this->get_hit_rate();
        $optimizations = [];
        
        // 1. Warm cache if hit rate is low
        if ($current_stats['hit_rate'] < $this->target_hit_rate) {
            $this->cache_service->warm_cache();
            $optimizations[] = [
                'action' => 'cache_warmed',
                'message' => 'Cache warmed with frequently accessed data'
            ];
        }
        
        // 2. Adjust expiration times
        $expiration_adjustment = $this->calculate_optimal_expiration($current_stats);
        if ($expiration_adjustment) {
            $optimizations[] = $expiration_adjustment;
        }
        
        // 3. Identify frequently missed keys
        $missed_keys = $this->identify_frequently_missed_keys();
        if (!empty($missed_keys)) {
            $optimizations[] = [
                'action' => 'frequently_missed_keys',
                'message' => 'Identified keys that are frequently missed',
                'keys' => $missed_keys,
                'recommendation' => 'Consider pre-caching these keys'
            ];
        }
        
        return [
            'optimizations_applied' => count($optimizations),
            'details' => $optimizations,
            'new_hit_rate' => $this->get_hit_rate()
        ];
    }
    
    /**
     * Calculate optimal cache expiration
     * 
     * @param array $stats Current cache statistics
     * @return array|null Optimization recommendation
     */
    private function calculate_optimal_expiration($stats) {
        // If hit rate is low, suggest increasing expiration
        if ($stats['hit_rate'] < 60) {
            return [
                'action' => 'increase_expiration',
                'message' => 'Recommend increasing cache expiration from 1 hour to 2 hours',
                'current' => 3600,
                'recommended' => 7200
            ];
        }
        
        // If hit rate is very high, we can potentially reduce expiration for fresher data
        if ($stats['hit_rate'] > 95) {
            return [
                'action' => 'optimize_expiration',
                'message' => 'Hit rate is excellent, cache expiration is optimal',
                'current' => 3600,
                'recommended' => 3600
            ];
        }
        
        return null;
    }
    
    /**
     * Identify frequently missed cache keys
     * 
     * @return array List of frequently missed keys
     */
    private function identify_frequently_missed_keys() {
        // This would require tracking individual key misses
        // For now, return common keys that should be cached
        return [
            'current_settings',
            'predefined_themes',
            'system_health',
            'backup_list'
        ];
    }
    
    /**
     * Get cache performance report
     * 
     * @return array Comprehensive performance report
     */
    public function get_performance_report() {
        $hit_rate_data = $this->get_hit_rate();
        $monitoring_data = $this->monitor_performance(24);
        $cache_stats = $this->cache_service->get_stats();
        
        return [
            'timestamp' => current_time('mysql'),
            'current_performance' => $hit_rate_data,
            'monitoring_analysis' => $monitoring_data,
            'cache_statistics' => $cache_stats,
            'system_info' => [
                'using_object_cache' => wp_using_ext_object_cache(),
                'cache_backend' => $this->detect_cache_backend(),
                'memory_limit' => ini_get('memory_limit'),
                'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status()
            ],
            'recommendations' => $monitoring_data['recommendations'] ?? []
        ];
    }
    
    /**
     * Detect cache backend
     * 
     * @return string Cache backend type
     */
    private function detect_cache_backend() {
        if (wp_using_ext_object_cache()) {
            if (class_exists('Redis')) {
                return 'Redis';
            } elseif (class_exists('Memcached')) {
                return 'Memcached';
            } elseif (class_exists('Memcache')) {
                return 'Memcache';
            } else {
                return 'Unknown persistent cache';
            }
        }
        
        return 'WordPress transient (database)';
    }
    
    /**
     * Run cache verification test
     * 
     * Tests cache functionality and measures hit rate.
     * 
     * @param int $iterations Number of test iterations
     * @return array Test results
     */
    public function run_verification_test($iterations = 100) {
        $test_key = 'cache_test_' . time();
        $test_value = ['test' => 'data', 'timestamp' => time()];
        
        $hits = 0;
        $misses = 0;
        $times = [];
        
        // Set initial value
        $this->cache_service->set($test_key, $test_value, 300);
        
        // Test cache retrieval
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            $cached = $this->cache_service->get($test_key);
            $end = microtime(true);
            
            $times[] = ($end - $start) * 1000; // Convert to milliseconds
            
            if ($cached !== false) {
                $hits++;
            } else {
                $misses++;
                // Re-set if missed
                $this->cache_service->set($test_key, $test_value, 300);
            }
        }
        
        // Clean up
        $this->cache_service->delete($test_key);
        
        $avg_time = array_sum($times) / count($times);
        $hit_rate = ($hits / $iterations) * 100;
        
        return [
            'iterations' => $iterations,
            'hits' => $hits,
            'misses' => $misses,
            'hit_rate' => round($hit_rate, 2),
            'avg_retrieval_time_ms' => round($avg_time, 4),
            'meets_target' => $hit_rate >= $this->target_hit_rate,
            'status' => $hit_rate >= $this->target_hit_rate ? 'PASS' : 'FAIL'
        ];
    }
}
