<?php
/**
 * System Diagnostics REST Controller for Modern Admin Styler V2
 * 
 * Provides REST API endpoints for system health monitoring, diagnostics,
 * performance metrics, conflict detection, and cache management.
 *
 * @package ModernAdminStylerV2
 * @subpackage API
 * @since 2.3.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * System Diagnostics REST Controller Class
 * 
 * Handles all system-related REST API endpoints including health checks,
 * system information, performance metrics, and cache management.
 */
class MAS_System_Controller extends MAS_REST_Controller {
    
    /**
     * System health service instance
     * 
     * @var MAS_System_Health_Service
     */
    private $health_service;
    
    /**
     * Cache service instance
     * 
     * @var MAS_Cache_Service
     */
    private $cache_service;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        $this->health_service = new MAS_System_Health_Service();
        
        // Cache service is optional for Phase 2
        if (class_exists('MAS_Cache_Service')) {
            $this->cache_service = new MAS_Cache_Service();
        }
    }
    
    /**
     * Register REST API routes
     * 
     * @return void
     */
    public function register_routes() {
        // GET /system/health - Get overall health status
        register_rest_route($this->namespace, '/system/health', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_health'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => []
        ]);
        
        // GET /system/info - Get system information
        register_rest_route($this->namespace, '/system/info', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_info'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => []
        ]);
        
        // GET /system/performance - Get performance metrics
        register_rest_route($this->namespace, '/system/performance', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_performance'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => []
        ]);
        
        // GET /system/conflicts - Get conflict detection results
        register_rest_route($this->namespace, '/system/conflicts', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_conflicts'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => []
        ]);
        
        // GET /system/cache - Get cache status
        register_rest_route($this->namespace, '/system/cache', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_cache_status'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => []
        ]);
        
        // DELETE /system/cache - Clear all caches
        register_rest_route($this->namespace, '/system/cache', [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [$this, 'clear_cache'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => []
        ]);
    }
    
    /**
     * Get overall health status
     * 
     * Returns comprehensive health check results including all system checks,
     * overall status calculation, and actionable recommendations.
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_health($request) {
        try {
            $health_status = $this->health_service->get_health_status();
            
            // Log health check
            $this->security_logger->log_event(
                'system_health_check',
                get_current_user_id(),
                [
                    'status' => $health_status['status'],
                    'critical_count' => $health_status['summary']['critical'],
                    'warning_count' => $health_status['summary']['warning']
                ]
            );
            
            return $this->optimized_response(
                $health_status,
                $request,
                [
                    'message' => 'Health status retrieved successfully',
                    'cache_max_age' => 60, // Cache for 1 minute
                    'use_etag' => true
                ]
            );
            
        } catch (Exception $e) {
            return $this->error_response(
                'Failed to retrieve health status: ' . $e->getMessage(),
                'health_check_failed',
                500
            );
        }
    }
    
    /**
     * Get system information
     * 
     * Returns detailed system information including PHP version, WordPress version,
     * plugin version, server information, and configuration details.
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_info($request) {
        try {
            global $wp_version;
            
            $plugin_file = WP_PLUGIN_DIR . '/modern-admin-styler-v2/modern-admin-styler-v2.php';
            $plugin_data = [];
            
            if (file_exists($plugin_file) && function_exists('get_plugin_data')) {
                $plugin_data = get_plugin_data($plugin_file, false, false);
            }
            
            $info = [
                'php' => [
                    'version' => PHP_VERSION,
                    'memory_limit' => ini_get('memory_limit'),
                    'max_execution_time' => ini_get('max_execution_time'),
                    'post_max_size' => ini_get('post_max_size'),
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                    'extensions' => get_loaded_extensions()
                ],
                'wordpress' => [
                    'version' => $wp_version,
                    'memory_limit' => WP_MEMORY_LIMIT,
                    'max_memory_limit' => WP_MAX_MEMORY_LIMIT,
                    'multisite' => is_multisite(),
                    'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
                    'locale' => get_locale(),
                    'timezone' => wp_timezone_string()
                ],
                'plugin' => [
                    'version' => $plugin_data['Version'] ?? '2.3.0',
                    'name' => $plugin_data['Name'] ?? 'Modern Admin Styler V2',
                    'author' => $plugin_data['Author'] ?? 'Unknown',
                    'active' => is_plugin_active('modern-admin-styler-v2/modern-admin-styler-v2.php'),
                    'rest_api_namespace' => $this->namespace
                ],
                'server' => [
                    'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                    'protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown',
                    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
                    'https' => is_ssl()
                ],
                'database' => $this->get_database_info()
            ];
            
            return $this->optimized_response(
                $info,
                $request,
                [
                    'message' => 'System information retrieved successfully',
                    'cache_max_age' => 300, // Cache for 5 minutes
                    'use_etag' => true
                ]
            );
            
        } catch (Exception $e) {
            return $this->error_response(
                'Failed to retrieve system information: ' . $e->getMessage(),
                'system_info_failed',
                500
            );
        }
    }
    
    /**
     * Get performance metrics
     * 
     * Returns detailed performance metrics including memory usage, cache statistics,
     * database query performance, and execution times.
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_performance($request) {
        try {
            $metrics = $this->health_service->get_performance_metrics();
            
            // Add additional performance data
            $metrics['wordpress'] = [
                'active_plugins' => count(get_option('active_plugins', [])),
                'active_theme' => wp_get_theme()->get('Name'),
                'registered_post_types' => count(get_post_types()),
                'registered_taxonomies' => count(get_taxonomies())
            ];
            
            // Add cache service stats if available
            if ($this->cache_service && method_exists($this->cache_service, 'get_stats')) {
                $metrics['cache_service'] = $this->cache_service->get_stats();
            }
            
            return $this->optimized_response(
                $metrics,
                $request,
                [
                    'message' => 'Performance metrics retrieved successfully',
                    'cache_max_age' => 30, // Cache for 30 seconds
                    'use_etag' => false // Don't use ETag for real-time metrics
                ]
            );
            
        } catch (Exception $e) {
            return $this->error_response(
                'Failed to retrieve performance metrics: ' . $e->getMessage(),
                'performance_metrics_failed',
                500
            );
        }
    }
    
    /**
     * Get conflict detection results
     * 
     * Returns detailed conflict detection including plugin conflicts, theme conflicts,
     * JavaScript conflicts, and actionable recommendations.
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_conflicts($request) {
        try {
            $health_status = $this->health_service->get_health_status();
            $conflicts = $health_status['checks']['conflicts'];
            
            // Add recommendations specific to conflicts
            $conflict_recommendations = array_filter(
                $health_status['recommendations'],
                function($rec) {
                    return $rec['category'] === 'conflicts';
                }
            );
            
            $result = [
                'conflicts' => $conflicts,
                'recommendations' => $conflict_recommendations,
                'summary' => [
                    'total_conflicts' => $conflicts['total_conflicts'],
                    'plugin_conflicts' => count($conflicts['plugin_conflicts']),
                    'theme_conflicts' => count($conflicts['theme_conflicts']),
                    'js_conflicts' => count($conflicts['js_conflicts'])
                ]
            ];
            
            return $this->optimized_response(
                $result,
                $request,
                [
                    'message' => 'Conflict detection completed successfully',
                    'cache_max_age' => 300, // Cache for 5 minutes
                    'use_etag' => true
                ]
            );
            
        } catch (Exception $e) {
            return $this->error_response(
                'Failed to detect conflicts: ' . $e->getMessage(),
                'conflict_detection_failed',
                500
            );
        }
    }
    
    /**
     * Get cache status
     * 
     * Returns cache status including object cache availability, transient counts,
     * cache statistics, and performance metrics.
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_cache_status($request) {
        try {
            $health_status = $this->health_service->get_health_status();
            $cache_status = $health_status['checks']['cache_status'];
            
            // Add cache service stats if available
            if ($this->cache_service && method_exists($this->cache_service, 'get_stats')) {
                $cache_status['service_stats'] = $this->cache_service->get_stats();
            }
            
            // Add WordPress cache info
            $cache_status['wordpress'] = [
                'object_cache_dropin' => file_exists(WP_CONTENT_DIR . '/object-cache.php'),
                'cache_plugins' => $this->detect_cache_plugins()
            ];
            
            return $this->optimized_response(
                $cache_status,
                $request,
                [
                    'message' => 'Cache status retrieved successfully',
                    'cache_max_age' => 60, // Cache for 1 minute
                    'use_etag' => true
                ]
            );
            
        } catch (Exception $e) {
            return $this->error_response(
                'Failed to retrieve cache status: ' . $e->getMessage(),
                'cache_status_failed',
                500
            );
        }
    }
    
    /**
     * Clear all caches
     * 
     * Clears all plugin caches including WordPress transients, object cache,
     * and cache service caches.
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function clear_cache($request) {
        try {
            $cleared = [];
            
            // Clear WordPress transients
            $transients_cleared = $this->clear_plugin_transients();
            $cleared['transients'] = $transients_cleared;
            
            // Clear object cache if available
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
                $cleared['object_cache'] = true;
            }
            
            // Clear cache service if available
            if ($this->cache_service && method_exists($this->cache_service, 'flush')) {
                $this->cache_service->flush();
                $cleared['cache_service'] = true;
            }
            
            // Clear settings cache
            delete_transient('mas_v2_settings');
            delete_transient('mas_v2_generated_css');
            
            // Log cache clear
            $this->security_logger->log_event(
                'cache_cleared',
                get_current_user_id(),
                [
                    'transients_cleared' => $transients_cleared,
                    'object_cache_cleared' => $cleared['object_cache'] ?? false
                ]
            );
            
            return $this->success_response(
                $cleared,
                'All caches cleared successfully',
                200,
                $request
            );
            
        } catch (Exception $e) {
            return $this->error_response(
                'Failed to clear caches: ' . $e->getMessage(),
                'cache_clear_failed',
                500
            );
        }
    }
    
    /**
     * Get database information
     * 
     * @return array Database information
     */
    private function get_database_info() {
        global $wpdb;
        
        $info = [
            'version' => $wpdb->db_version(),
            'prefix' => $wpdb->prefix,
            'charset' => $wpdb->charset,
            'collate' => $wpdb->collate
        ];
        
        // Get database size if possible
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT SUM(data_length + index_length) as size 
                 FROM information_schema.TABLES 
                 WHERE table_schema = %s",
                DB_NAME
            )
        );
        
        if ($result && isset($result->size)) {
            $info['size'] = size_format($result->size);
            $info['size_bytes'] = $result->size;
        }
        
        return $info;
    }
    
    /**
     * Detect cache plugins
     * 
     * @return array List of detected cache plugins
     */
    private function detect_cache_plugins() {
        $cache_plugins = [];
        $active_plugins = get_option('active_plugins', []);
        
        $known_cache_plugins = [
            'wp-super-cache' => 'WP Super Cache',
            'w3-total-cache' => 'W3 Total Cache',
            'wp-rocket' => 'WP Rocket',
            'wp-fastest-cache' => 'WP Fastest Cache',
            'litespeed-cache' => 'LiteSpeed Cache',
            'redis-cache' => 'Redis Object Cache',
            'memcached' => 'Memcached Object Cache'
        ];
        
        foreach ($active_plugins as $plugin) {
            $plugin_slug = dirname($plugin);
            
            foreach ($known_cache_plugins as $cache_slug => $cache_name) {
                if (strpos($plugin_slug, $cache_slug) !== false) {
                    $cache_plugins[] = [
                        'name' => $cache_name,
                        'slug' => $plugin_slug
                    ];
                }
            }
        }
        
        return $cache_plugins;
    }
    
    /**
     * Clear plugin transients
     * 
     * @return int Number of transients cleared
     */
    private function clear_plugin_transients() {
        global $wpdb;
        
        $count = $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_mas_v2_%' 
             OR option_name LIKE '_transient_timeout_mas_v2_%'"
        );
        
        return (int) $count;
    }
}
