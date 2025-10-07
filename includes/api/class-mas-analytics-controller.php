<?php
/**
 * Analytics REST Controller
 *
 * Handles analytics and monitoring endpoints for API usage statistics,
 * performance metrics, and error analysis.
 *
 * @package    Modern_Admin_Styler_V2
 * @subpackage API
 * @since      2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MAS_Analytics_Controller
 *
 * Provides REST API endpoints for analytics and monitoring.
 */
class MAS_Analytics_Controller extends MAS_REST_Controller {

    /**
     * Analytics service instance
     *
     * @var MAS_Analytics_Service
     */
    private $analytics_service;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->analytics_service = new MAS_Analytics_Service();
    }

    /**
     * Register routes for analytics endpoints
     *
     * @return void
     */
    public function register_routes() {
        // GET /analytics/usage - Get usage statistics
        register_rest_route($this->namespace, '/analytics/usage', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_usage_stats'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'start_date' => [
                    'description' => 'Start date for statistics (Y-m-d H:i:s format)',
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'end_date' => [
                    'description' => 'End date for statistics (Y-m-d H:i:s format)',
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // GET /analytics/performance - Get performance metrics
        register_rest_route($this->namespace, '/analytics/performance', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_performance_metrics'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'start_date' => [
                    'description' => 'Start date for metrics (Y-m-d H:i:s format)',
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'end_date' => [
                    'description' => 'End date for metrics (Y-m-d H:i:s format)',
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // GET /analytics/errors - Get error statistics
        register_rest_route($this->namespace, '/analytics/errors', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_error_stats'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'start_date' => [
                    'description' => 'Start date for error stats (Y-m-d H:i:s format)',
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'end_date' => [
                    'description' => 'End date for error stats (Y-m-d H:i:s format)',
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // GET /analytics/export - Export analytics data as CSV
        register_rest_route($this->namespace, '/analytics/export', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'export_analytics'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'start_date' => [
                    'description' => 'Start date for export (Y-m-d H:i:s format)',
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'end_date' => [
                    'description' => 'End date for export (Y-m-d H:i:s format)',
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
    }

    /**
     * Get usage statistics
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_usage_stats($request) {
        try {
            $start_date = $request->get_param('start_date');
            $end_date = $request->get_param('end_date');

            // Validate date formats if provided
            if ($start_date && !$this->validate_date_format($start_date)) {
                return $this->error_response(
                    'Invalid start_date format. Use Y-m-d H:i:s format.',
                    'invalid_date_format',
                    400
                );
            }

            if ($end_date && !$this->validate_date_format($end_date)) {
                return $this->error_response(
                    'Invalid end_date format. Use Y-m-d H:i:s format.',
                    'invalid_date_format',
                    400
                );
            }

            $stats = $this->analytics_service->get_usage_stats($start_date, $end_date);

            return $this->optimized_response($stats, $request, [
                'message' => 'Usage statistics retrieved successfully',
                'cache_max_age' => 300, // Cache for 5 minutes
                'use_etag' => true,
            ]);

        } catch (Exception $e) {
            return $this->error_response(
                $e->getMessage(),
                'usage_stats_error',
                500
            );
        }
    }

    /**
     * Get performance metrics
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_performance_metrics($request) {
        try {
            $start_date = $request->get_param('start_date');
            $end_date = $request->get_param('end_date');

            // Validate date formats if provided
            if ($start_date && !$this->validate_date_format($start_date)) {
                return $this->error_response(
                    'Invalid start_date format. Use Y-m-d H:i:s format.',
                    'invalid_date_format',
                    400
                );
            }

            if ($end_date && !$this->validate_date_format($end_date)) {
                return $this->error_response(
                    'Invalid end_date format. Use Y-m-d H:i:s format.',
                    'invalid_date_format',
                    400
                );
            }

            $metrics = $this->analytics_service->get_performance_percentiles($start_date, $end_date);

            return $this->optimized_response($metrics, $request, [
                'message' => 'Performance metrics retrieved successfully',
                'cache_max_age' => 300, // Cache for 5 minutes
                'use_etag' => true,
            ]);

        } catch (Exception $e) {
            return $this->error_response(
                $e->getMessage(),
                'performance_metrics_error',
                500
            );
        }
    }

    /**
     * Get error statistics
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_error_stats($request) {
        try {
            $start_date = $request->get_param('start_date');
            $end_date = $request->get_param('end_date');

            // Validate date formats if provided
            if ($start_date && !$this->validate_date_format($start_date)) {
                return $this->error_response(
                    'Invalid start_date format. Use Y-m-d H:i:s format.',
                    'invalid_date_format',
                    400
                );
            }

            if ($end_date && !$this->validate_date_format($end_date)) {
                return $this->error_response(
                    'Invalid end_date format. Use Y-m-d H:i:s format.',
                    'invalid_date_format',
                    400
                );
            }

            $stats = $this->analytics_service->get_error_stats($start_date, $end_date);

            return $this->optimized_response($stats, $request, [
                'message' => 'Error statistics retrieved successfully',
                'cache_max_age' => 300, // Cache for 5 minutes
                'use_etag' => true,
            ]);

        } catch (Exception $e) {
            return $this->error_response(
                $e->getMessage(),
                'error_stats_error',
                500
            );
        }
    }

    /**
     * Export analytics data as CSV
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function export_analytics($request) {
        try {
            $start_date = $request->get_param('start_date');
            $end_date = $request->get_param('end_date');

            // Validate date formats if provided
            if ($start_date && !$this->validate_date_format($start_date)) {
                return $this->error_response(
                    'Invalid start_date format. Use Y-m-d H:i:s format.',
                    'invalid_date_format',
                    400
                );
            }

            if ($end_date && !$this->validate_date_format($end_date)) {
                return $this->error_response(
                    'Invalid end_date format. Use Y-m-d H:i:s format.',
                    'invalid_date_format',
                    400
                );
            }

            $csv_content = $this->analytics_service->export_to_csv($start_date, $end_date);

            // Generate filename
            $filename = sprintf(
                'mas-analytics-%s-to-%s.csv',
                $start_date ? date('Y-m-d', strtotime($start_date)) : date('Y-m-d', strtotime('-7 days')),
                $end_date ? date('Y-m-d', strtotime($end_date)) : date('Y-m-d')
            );

            // Create response with CSV content
            $response = new WP_REST_Response($csv_content, 200);
            
            // Set headers for file download
            $response->header('Content-Type', 'text/csv; charset=utf-8');
            $response->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $response->header('Content-Length', strlen($csv_content));
            $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->header('Pragma', 'no-cache');
            $response->header('Expires', '0');

            return $response;

        } catch (Exception $e) {
            return $this->error_response(
                $e->getMessage(),
                'export_error',
                500
            );
        }
    }

    /**
     * Validate date format
     *
     * @param string $date Date string to validate
     * @return bool True if valid, false otherwise
     */
    private function validate_date_format($date) {
        $d = DateTime::createFromFormat('Y-m-d H:i:s', $date);
        return $d && $d->format('Y-m-d H:i:s') === $date;
    }
}
