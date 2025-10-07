<?php
/**
 * Performance Profiler Service
 * 
 * Profiles and optimizes slow operations in Phase 2.
 * Identifies bottlenecks and provides optimization recommendations.
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
 * MAS Performance Profiler
 * 
 * Profiles operations and identifies performance bottlenecks.
 */
class MAS_Performance_Profiler {
    
    /**
     * Profiling data
     * 
     * @var array
     */
    private $profiles = [];
    
    /**
     * Slow operation threshold (milliseconds)
     * 
     * @var float
     */
    private $slow_threshold = 100.0;
    
    /**
     * Cache service instance
     * 
     * @var MAS_Cache_Service
     */
    private $cache_service;
    
    /**
     * Database optimizer instance
     * 
     * @var MAS_Database_Optimizer
     */
    private $db_optimizer;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->cache_service = new MAS_Cache_Service();
        $this->db_optimizer = MAS_Database_Optimizer::get_instance();
    }
    
    /**
     * Start profiling an operation
     * 
     * @param string $operation_name Name of the operation
     * @return string Profile ID
     */
    public function start_profile($operation_name) {
        $profile_id = uniqid('profile_');
        
        $this->profiles[$profile_id] = [
            'name' => $operation_name,
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'queries_before' => get_num_queries()
        ];
        
        return $profile_id;
    }
    
    /**
     * End profiling an operation
     * 
     * @param string $profile_id Profile ID from start_profile()
     * @return array Profile results
     */
    public function end_profile($profile_id) {
        if (!isset($this->profiles[$profile_id])) {
            return null;
        }
        
        $profile = $this->profiles[$profile_id];
        
        $end_time = microtime(true);
        $end_memory = memory_get_usage(true);
        $queries_after = get_num_queries();
        
        $duration_ms = ($end_time - $profile['start_time']) * 1000;
        $memory_used = $end_memory - $profile['start_memory'];
        $query_count = $queries_after - $profile['queries_before'];
        
        $result = [
            'name' => $profile['name'],
            'duration_ms' => $duration_ms,
            'memory_used_bytes' => $memory_used,
            'memory_used_mb' => round($memory_used / 1024 / 1024, 2),
            'query_count' => $query_count,
            'is_slow' => $duration_ms > $this->slow_threshold,
            'timestamp' => current_time('mysql')
        ];
        
        // Log slow operations
        if ($result['is_slow']) {
            $this->log_slow_operation($result);
        }
        
        unset($this->profiles[$profile_id]);
        
        return $result;
    }
    
    /**
     * Profile a callback function
     * 
     * @param string $operation_name Name of the operation
     * @param callable $callback Function to profile
     * @return array ['result' => mixed, 'profile' => array]
     */
    public function profile_callback($operation_name, $callback) {
        $profile_id = $this->start_profile($operation_name);
        
        $result = call_user_func($callback);
        
        $profile = $this->end_profile($profile_id);
        
        return [
            'result' => $result,
            'profile' => $profile
        ];
    }
    
    /**
     * Log slow operation
     * 
     * @param array $profile Profile data
     * @return void
     */
    private function log_slow_operation($profile) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'MAS Slow Operation: %s took %.2fms (threshold: %.2fms), %d queries, %.2fMB memory',
                $profile['name'],
                $profile['duration_ms'],
                $this->slow_threshold,
                $profile['query_count'],
                $profile['memory_used_mb']
            ));
        }
        
        // Store in transient for admin dashboard
        $slow_ops = get_transient('mas_v2_slow_operations') ?: [];
        $slow_ops[] = $profile;
        
        // Keep only last 50
        if (count($slow_ops) > 50) {
            $slow_ops = array_slice($slow_ops, -50);
        }
        
        set_transient('mas_v2_slow_operations', $slow_ops, DAY_IN_SECONDS);
    }
    
    /**
     * Get slow operations log
     * 
     * @return array List of slow operations
     */
    public function get_slow_operations() {
        return get_transient('mas_v2_slow_operations') ?: [];
    }
    
    /**
     * Optimize webhook delivery
     * 
     * Implements async delivery and batching for webhooks.
     * 
     * @param array $webhooks List of webhooks to deliver
     * @param string $event Event name
     * @param array $payload Event payload
     * @return array Optimization results
     */
    public function optimize_webhook_delivery($webhooks, $event, $payload) {
        $profile_id = $this->start_profile('webhook_delivery_batch');
        
        // Group webhooks by domain to batch requests
        $grouped = [];
        foreach ($webhooks as $webhook) {
            $domain = parse_url($webhook['url'], PHP_URL_HOST);
            $grouped[$domain][] = $webhook;
        }
        
        $results = [];
        
        // Deliver to each domain
        foreach ($grouped as $domain => $domain_webhooks) {
            // Use WordPress HTTP API with timeout
            foreach ($domain_webhooks as $webhook) {
                $results[] = $this->deliver_webhook_optimized($webhook, $event, $payload);
            }
        }
        
        $profile = $this->end_profile($profile_id);
        
        return [
            'delivered' => count($results),
            'profile' => $profile
        ];
    }
    
    /**
     * Deliver webhook with optimization
     * 
     * @param array $webhook Webhook configuration
     * @param string $event Event name
     * @param array $payload Event payload
     * @return array Delivery result
     */
    private function deliver_webhook_optimized($webhook, $event, $payload) {
        $body = json_encode([
            'event' => $event,
            'payload' => $payload,
            'timestamp' => time()
        ]);
        
        $signature = hash_hmac('sha256', $body, $webhook['secret']);
        
        // Use shorter timeout for webhooks
        $response = wp_remote_post($webhook['url'], [
            'body' => $body,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-MAS-Signature' => $signature,
                'X-MAS-Event' => $event
            ],
            'timeout' => 5, // Reduced from 10 to 5 seconds
            'blocking' => false // Non-blocking for better performance
        ]);
        
        return [
            'webhook_id' => $webhook['id'],
            'success' => !is_wp_error($response)
        ];
    }
    
    /**
     * Optimize database queries
     * 
     * Analyzes and optimizes slow database queries.
     * 
     * @return array Optimization results
     */
    public function optimize_database_queries() {
        global $wpdb;
        
        $optimizations = [];
        
        // 1. Add missing indexes
        $optimizations['indexes'] = $this->add_missing_indexes();
        
        // 2. Clean up expired transients
        $optimizations['transients_cleaned'] = $this->db_optimizer->cleanup_expired_transients();
        
        // 3. Optimize tables
        $optimizations['tables_optimized'] = $this->optimize_tables();
        
        // 4. Analyze query patterns
        $optimizations['query_analysis'] = $this->analyze_query_patterns();
        
        return $optimizations;
    }
    
    /**
     * Add missing indexes
     * 
     * @return array Results
     */
    private function add_missing_indexes() {
        global $wpdb;
        
        $results = [];
        
        // Check if custom tables exist and add indexes
        $tables = [
            $wpdb->prefix . 'mas_v2_audit_log' => [
                ['column' => 'timestamp', 'name' => 'idx_timestamp'],
                ['column' => 'user_id', 'name' => 'idx_user_id'],
                ['column' => 'event_type', 'name' => 'idx_event_type']
            ],
            $wpdb->prefix . 'mas_v2_metrics' => [
                ['column' => 'endpoint', 'name' => 'idx_endpoint'],
                ['column' => 'timestamp', 'name' => 'idx_timestamp'],
                ['column' => 'status_code', 'name' => 'idx_status_code']
            ],
            $wpdb->prefix . 'mas_v2_webhooks' => [
                ['column' => 'active', 'name' => 'idx_active']
            ]
        ];
        
        foreach ($tables as $table => $indexes) {
            // Check if table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
            
            if (!$table_exists) {
                continue;
            }
            
            foreach ($indexes as $index) {
                // Check if index exists
                $index_exists = $wpdb->get_var(
                    $wpdb->prepare(
                        "SHOW INDEX FROM $table WHERE Key_name = %s",
                        $index['name']
                    )
                );
                
                if (!$index_exists) {
                    // Add index
                    $wpdb->query(
                        "ALTER TABLE $table ADD INDEX {$index['name']} ({$index['column']})"
                    );
                    
                    $results[] = [
                        'table' => $table,
                        'index' => $index['name'],
                        'column' => $index['column'],
                        'status' => 'added'
                    ];
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Optimize tables
     * 
     * @return array Results
     */
    private function optimize_tables() {
        global $wpdb;
        
        $results = [];
        
        $tables = [
            $wpdb->prefix . 'mas_v2_audit_log',
            $wpdb->prefix . 'mas_v2_metrics',
            $wpdb->prefix . 'mas_v2_webhooks',
            $wpdb->prefix . 'mas_v2_webhook_deliveries'
        ];
        
        foreach ($tables as $table) {
            // Check if table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
            
            if ($table_exists) {
                $wpdb->query("OPTIMIZE TABLE $table");
                $results[] = $table;
            }
        }
        
        return $results;
    }
    
    /**
     * Analyze query patterns
     * 
     * @return array Analysis results
     */
    private function analyze_query_patterns() {
        global $wpdb;
        
        $analysis = [
            'total_queries' => get_num_queries(),
            'slow_queries' => 0,
            'recommendations' => []
        ];
        
        // Check for N+1 query patterns
        if ($analysis['total_queries'] > 50) {
            $analysis['recommendations'][] = [
                'type' => 'high_query_count',
                'message' => 'High number of queries detected. Consider using query caching.',
                'severity' => 'warning'
            ];
        }
        
        // Check cache usage
        $cache_stats = $this->cache_service->get_stats();
        if ($cache_stats['hit_rate'] < 80) {
            $analysis['recommendations'][] = [
                'type' => 'low_cache_hit_rate',
                'message' => sprintf('Cache hit rate is %.2f%%. Consider warming cache or increasing TTL.', $cache_stats['hit_rate']),
                'severity' => 'warning'
            ];
        }
        
        return $analysis;
    }
    
    /**
     * Get optimization recommendations
     * 
     * @return array List of recommendations
     */
    public function get_optimization_recommendations() {
        $recommendations = [];
        
        // Check slow operations
        $slow_ops = $this->get_slow_operations();
        if (count($slow_ops) > 10) {
            $recommendations[] = [
                'type' => 'slow_operations',
                'message' => sprintf('%d slow operations detected in the last 24 hours', count($slow_ops)),
                'severity' => 'warning',
                'action' => 'Review slow operations and optimize queries'
            ];
        }
        
        // Check cache configuration
        if (!wp_using_ext_object_cache()) {
            $recommendations[] = [
                'type' => 'no_object_cache',
                'message' => 'External object cache not detected',
                'severity' => 'info',
                'action' => 'Consider installing Redis or Memcached for better performance'
            ];
        }
        
        // Check database size
        $db_stats = $this->db_optimizer->get_stats();
        if ($db_stats['backup_count'] > 100) {
            $recommendations[] = [
                'type' => 'too_many_backups',
                'message' => sprintf('%d backups stored', $db_stats['backup_count']),
                'severity' => 'warning',
                'action' => 'Run backup cleanup to remove old automatic backups'
            ];
        }
        
        // Check transients
        if ($db_stats['transient_count'] > 50) {
            $recommendations[] = [
                'type' => 'expired_transients',
                'message' => sprintf('%d transients found', $db_stats['transient_count']),
                'severity' => 'info',
                'action' => 'Clean up expired transients'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Run full optimization
     * 
     * Runs all optimization tasks.
     * 
     * @return array Optimization results
     */
    public function run_full_optimization() {
        $profile_id = $this->start_profile('full_optimization');
        
        $results = [
            'database' => $this->optimize_database_queries(),
            'cache' => $this->optimize_cache(),
            'recommendations' => $this->get_optimization_recommendations()
        ];
        
        $results['profile'] = $this->end_profile($profile_id);
        
        return $results;
    }
    
    /**
     * Optimize cache
     * 
     * @return array Results
     */
    private function optimize_cache() {
        // Warm cache with frequently accessed data
        $this->cache_service->warm_cache();
        
        // Get cache stats
        $stats = $this->cache_service->get_stats();
        
        return [
            'cache_warmed' => true,
            'stats' => $stats
        ];
    }
}
