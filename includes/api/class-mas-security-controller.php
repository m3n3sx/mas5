<?php
/**
 * Security REST Controller
 *
 * Provides REST API endpoints for security audit logs and rate limit status.
 *
 * @package ModernAdminStyler
 * @subpackage API
 * @since 2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Security REST Controller Class
 *
 * Handles security-related REST API endpoints.
 */
class MAS_Security_Controller extends MAS_REST_Controller {
    
    /**
     * Security logger service
     *
     * @var MAS_Security_Logger_Service
     */
    private $logger_service;
    
    /**
     * Rate limiter service
     *
     * @var MAS_Rate_Limiter_Service
     */
    private $limiter_service;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        $this->logger_service = new MAS_Security_Logger_Service();
        $this->limiter_service = new MAS_Rate_Limiter_Service();
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // GET /security/audit-log - Get audit log entries
        register_rest_route($this->namespace, '/security/audit-log', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_audit_log'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => $this->get_audit_log_args(),
        ]);
        
        // GET /security/rate-limit/status - Get rate limit status
        register_rest_route($this->namespace, '/security/rate-limit/status', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_rate_limit_status'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'action' => [
                    'description' => __('Action to check rate limit for', 'modern-admin-styler-v2'),
                    'type' => 'string',
                    'default' => 'default',
                    'sanitize_callback' => 'sanitize_key',
                ],
            ],
        ]);
        
        // GET /security/suspicious-activity - Check for suspicious activity
        register_rest_route($this->namespace, '/security/suspicious-activity', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'check_suspicious_activity'],
            'permission_callback' => [$this, 'check_permission'],
        ]);
        
        // GET /security/event-types - Get available event types
        register_rest_route($this->namespace, '/security/event-types', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_event_types'],
            'permission_callback' => [$this, 'check_permission'],
        ]);
    }
    
    /**
     * Get audit log entries
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object
     */
    public function get_audit_log($request) {
        try {
            // Build query arguments
            $args = [
                'user_id' => $request->get_param('user_id'),
                'action' => $request->get_param('action'),
                'status' => $request->get_param('status'),
                'ip_address' => $request->get_param('ip_address'),
                'date_from' => $request->get_param('date_from'),
                'date_to' => $request->get_param('date_to'),
                'limit' => $request->get_param('per_page'),
                'offset' => ($request->get_param('page') - 1) * $request->get_param('per_page'),
                'orderby' => $request->get_param('orderby'),
                'order' => $request->get_param('order'),
            ];
            
            // Remove null values
            $args = array_filter($args, function($value) {
                return $value !== null;
            });
            
            // Get audit log entries
            $entries = $this->logger_service->get_audit_log($args);
            
            // Get total count for pagination
            $total = $this->logger_service->get_audit_log_count($args);
            
            // Create response
            $response = $this->success_response([
                'entries' => $entries,
                'pagination' => [
                    'total' => $total,
                    'page' => $request->get_param('page'),
                    'per_page' => $request->get_param('per_page'),
                    'total_pages' => ceil($total / $request->get_param('per_page')),
                ],
            ], __('Audit log retrieved successfully', 'modern-admin-styler-v2'));
            
            // Add pagination headers
            $response->header('X-WP-Total', $total);
            $response->header('X-WP-TotalPages', ceil($total / $request->get_param('per_page')));
            
            return $response;
            
        } catch (Exception $e) {
            return $this->error_response(
                $e->getMessage(),
                'audit_log_error',
                500
            );
        }
    }
    
    /**
     * Get rate limit status
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object
     */
    public function get_rate_limit_status($request) {
        try {
            $action = $request->get_param('action');
            
            // Get rate limit status
            $status = $this->limiter_service->get_status($action);
            
            return $this->success_response(
                $status,
                __('Rate limit status retrieved successfully', 'modern-admin-styler-v2')
            );
            
        } catch (Exception $e) {
            return $this->error_response(
                $e->getMessage(),
                'rate_limit_status_error',
                500
            );
        }
    }
    
    /**
     * Check for suspicious activity
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object
     */
    public function check_suspicious_activity($request) {
        try {
            // Check for suspicious activity
            $report = $this->logger_service->check_suspicious_activity();
            
            // Log if suspicious activity detected
            if ($report['is_suspicious']) {
                $this->logger_service->log_event(
                    'suspicious_activity_detected',
                    sprintf(
                        __('Suspicious activity detected: %d patterns found', 'modern-admin-styler-v2'),
                        count($report['patterns'])
                    ),
                    [
                        'new_value' => $report,
                        'status' => 'warning',
                    ]
                );
            }
            
            return $this->success_response(
                $report,
                $report['is_suspicious'] 
                    ? __('Suspicious activity detected', 'modern-admin-styler-v2')
                    : __('No suspicious activity detected', 'modern-admin-styler-v2')
            );
            
        } catch (Exception $e) {
            return $this->error_response(
                $e->getMessage(),
                'suspicious_activity_check_error',
                500
            );
        }
    }
    
    /**
     * Get available event types
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object
     */
    public function get_event_types($request) {
        try {
            $event_types = $this->logger_service->get_event_types();
            
            return $this->success_response(
                ['event_types' => $event_types],
                __('Event types retrieved successfully', 'modern-admin-styler-v2')
            );
            
        } catch (Exception $e) {
            return $this->error_response(
                $e->getMessage(),
                'event_types_error',
                500
            );
        }
    }
    
    /**
     * Get audit log query arguments
     *
     * @return array Arguments schema
     */
    private function get_audit_log_args() {
        return [
            'user_id' => [
                'description' => __('Filter by user ID', 'modern-admin-styler-v2'),
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'action' => [
                'description' => __('Filter by action type', 'modern-admin-styler-v2'),
                'type' => 'string',
                'sanitize_callback' => 'sanitize_key',
            ],
            'status' => [
                'description' => __('Filter by status', 'modern-admin-styler-v2'),
                'type' => 'string',
                'enum' => ['success', 'failed', 'warning'],
                'sanitize_callback' => 'sanitize_key',
            ],
            'ip_address' => [
                'description' => __('Filter by IP address', 'modern-admin-styler-v2'),
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'date_from' => [
                'description' => __('Filter by start date (Y-m-d H:i:s)', 'modern-admin-styler-v2'),
                'type' => 'string',
                'format' => 'date-time',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'date_to' => [
                'description' => __('Filter by end date (Y-m-d H:i:s)', 'modern-admin-styler-v2'),
                'type' => 'string',
                'format' => 'date-time',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'page' => [
                'description' => __('Current page of results', 'modern-admin-styler-v2'),
                'type' => 'integer',
                'default' => 1,
                'minimum' => 1,
                'sanitize_callback' => 'absint',
            ],
            'per_page' => [
                'description' => __('Number of results per page', 'modern-admin-styler-v2'),
                'type' => 'integer',
                'default' => 50,
                'minimum' => 1,
                'maximum' => 100,
                'sanitize_callback' => 'absint',
            ],
            'orderby' => [
                'description' => __('Order results by field', 'modern-admin-styler-v2'),
                'type' => 'string',
                'default' => 'timestamp',
                'enum' => ['id', 'timestamp', 'action', 'user_id'],
                'sanitize_callback' => 'sanitize_key',
            ],
            'order' => [
                'description' => __('Order direction', 'modern-admin-styler-v2'),
                'type' => 'string',
                'default' => 'DESC',
                'enum' => ['ASC', 'DESC'],
                'sanitize_callback' => function($value) {
                    return strtoupper($value) === 'ASC' ? 'ASC' : 'DESC';
                },
            ],
        ];
    }
}
