<?php
/**
 * Diagnostics REST Controller for Modern Admin Styler V2
 * 
 * Handles REST API endpoints for system diagnostics, health checks,
 * and performance monitoring.
 *
 * @package ModernAdminStylerV2
 * @subpackage API
 * @since 2.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Diagnostics REST Controller Class
 * 
 * Provides REST API endpoints for retrieving system diagnostics,
 * health information, and optimization recommendations.
 */
class MAS_Diagnostics_Controller extends MAS_REST_Controller {
    
    /**
     * Diagnostics service instance
     * 
     * @var MAS_Diagnostics_Service
     */
    private $diagnostics_service;
    
    /**
     * Constructor
     * 
     * @param MAS_Diagnostics_Service $diagnostics_service Diagnostics service instance
     */
    public function __construct($diagnostics_service = null) {
        $this->diagnostics_service = $diagnostics_service ?: new MAS_Diagnostics_Service();
        parent::__construct();
    }
    
    /**
     * Register REST API routes
     * 
     * @return void
     */
    public function register_routes() {
        // GET /diagnostics - Get system diagnostics
        register_rest_route($this->namespace, '/diagnostics', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_diagnostics'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'include' => [
                    'description' => __('Comma-separated list of diagnostic sections to include', 'modern-admin-styler-v2'),
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => [$this, 'validate_include_param']
                ]
            ]
        ]);
        
        // GET /diagnostics/health - Quick health check
        register_rest_route($this->namespace, '/diagnostics/health', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_health_check'],
            'permission_callback' => [$this, 'check_permission']
        ]);
        
        // GET /diagnostics/performance - Performance metrics only
        register_rest_route($this->namespace, '/diagnostics/performance', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_performance_metrics'],
            'permission_callback' => [$this, 'check_permission']
        ]);
    }
    
    /**
     * Get comprehensive system diagnostics
     * 
     * @param WP_REST_Request $request REST request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_diagnostics($request) {
        try {
            $start_time = microtime(true);
            
            // Get full diagnostics
            $diagnostics = $this->diagnostics_service->get_diagnostics();
            
            // Filter by include parameter if provided
            $include = $request->get_param('include');
            if ($include) {
                $sections = array_map('trim', explode(',', $include));
                $diagnostics = array_intersect_key(
                    $diagnostics,
                    array_flip($sections)
                );
            }
            
            // Add metadata
            $diagnostics['_metadata'] = [
                'generated_at' => current_time('mysql'),
                'generated_timestamp' => current_time('timestamp'),
                'execution_time' => round((microtime(true) - $start_time) * 1000, 2) . 'ms'
            ];
            
            return $this->success_response(
                $diagnostics,
                __('Diagnostics retrieved successfully', 'modern-admin-styler-v2')
            );
            
        } catch (Exception $e) {
            return $this->error_response(
                sprintf(
                    __('Failed to retrieve diagnostics: %s', 'modern-admin-styler-v2'),
                    $e->getMessage()
                ),
                'diagnostics_error',
                500
            );
        }
    }
    
    /**
     * Get quick health check
     * 
     * @param WP_REST_Request $request REST request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_health_check($request) {
        try {
            $health = [
                'status' => 'healthy',
                'checks' => []
            ];
            
            // Check REST API
            $health['checks']['rest_api'] = [
                'status' => rest_url() ? 'pass' : 'fail',
                'message' => rest_url() ? 'REST API is available' : 'REST API is not available'
            ];
            
            // Check settings integrity
            $settings_check = $this->diagnostics_service->check_settings_integrity();
            $health['checks']['settings'] = [
                'status' => $settings_check['valid'] ? 'pass' : 'fail',
                'message' => $settings_check['valid'] ? 'Settings are valid' : 'Settings have integrity issues'
            ];
            
            // Check filesystem
            $filesystem_check = $this->diagnostics_service->check_filesystem();
            $health['checks']['filesystem'] = [
                'status' => $filesystem_check['upload_dir_writable'] ? 'pass' : 'fail',
                'message' => $filesystem_check['upload_dir_writable'] ? 'Filesystem is writable' : 'Filesystem has permission issues'
            ];
            
            // Check PHP version
            $php_ok = version_compare(PHP_VERSION, '7.4', '>=');
            $health['checks']['php_version'] = [
                'status' => $php_ok ? 'pass' : 'warning',
                'message' => $php_ok ? 'PHP version is adequate' : 'PHP version should be upgraded'
            ];
            
            // Determine overall status
            $failed_checks = array_filter($health['checks'], function($check) {
                return $check['status'] === 'fail';
            });
            
            $warning_checks = array_filter($health['checks'], function($check) {
                return $check['status'] === 'warning';
            });
            
            if (!empty($failed_checks)) {
                $health['status'] = 'unhealthy';
            } elseif (!empty($warning_checks)) {
                $health['status'] = 'warning';
            }
            
            return $this->success_response(
                $health,
                sprintf(
                    __('Health check completed: %s', 'modern-admin-styler-v2'),
                    $health['status']
                )
            );
            
        } catch (Exception $e) {
            return $this->error_response(
                sprintf(
                    __('Health check failed: %s', 'modern-admin-styler-v2'),
                    $e->getMessage()
                ),
                'health_check_error',
                500
            );
        }
    }
    
    /**
     * Get performance metrics only
     * 
     * @param WP_REST_Request $request REST request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_performance_metrics($request) {
        try {
            $start_time = microtime(true);
            
            $metrics = $this->diagnostics_service->get_performance_metrics($start_time);
            
            return $this->success_response(
                $metrics,
                __('Performance metrics retrieved successfully', 'modern-admin-styler-v2')
            );
            
        } catch (Exception $e) {
            return $this->error_response(
                sprintf(
                    __('Failed to retrieve performance metrics: %s', 'modern-admin-styler-v2'),
                    $e->getMessage()
                ),
                'performance_metrics_error',
                500
            );
        }
    }
    
    /**
     * Validate include parameter
     * 
     * @param string $value Parameter value
     * @param WP_REST_Request $request REST request object
     * @param string $param Parameter name
     * @return bool True if valid
     */
    public function validate_include_param($value, $request, $param) {
        if (empty($value)) {
            return true;
        }
        
        $valid_sections = [
            'system',
            'plugin',
            'settings',
            'filesystem',
            'conflicts',
            'performance',
            'recommendations'
        ];
        
        $sections = array_map('trim', explode(',', $value));
        
        foreach ($sections as $section) {
            if (!in_array($section, $valid_sections)) {
                return false;
            }
        }
        
        return true;
    }
}
