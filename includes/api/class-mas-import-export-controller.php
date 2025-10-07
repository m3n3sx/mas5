<?php
/**
 * Import/Export REST Controller
 * 
 * Handles REST API endpoints for importing and exporting settings.
 * Provides JSON export with proper headers and import with validation.
 *
 * @package ModernAdminStylerV2
 * @subpackage API
 * @since 2.2.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Import/Export Controller Class
 * 
 * Manages import and export REST API endpoints
 */
class MAS_Import_Export_Controller extends MAS_REST_Controller {
    
    /**
     * Import/Export service instance
     * 
     * @var MAS_Import_Export_Service
     */
    private $import_export_service;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->import_export_service = MAS_Import_Export_Service::get_instance();
    }
    
    /**
     * Register routes for import/export endpoints
     * 
     * @return void
     */
    public function register_routes() {
        // GET /export - Export settings as JSON
        register_rest_route($this->namespace, '/export', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'export_settings'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'include_metadata' => [
                    'description' => __('Include metadata in export', 'modern-admin-styler-v2'),
                    'type' => 'boolean',
                    'default' => true,
                    'sanitize_callback' => 'rest_sanitize_boolean',
                ],
            ],
        ]);
        
        // POST /import - Import settings from JSON
        register_rest_route($this->namespace, '/import', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'import_settings'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'data' => [
                    'description' => __('Import data as JSON string or object', 'modern-admin-styler-v2'),
                    'type' => ['string', 'object'],
                    'required' => true,
                ],
                'create_backup' => [
                    'description' => __('Create backup before import', 'modern-admin-styler-v2'),
                    'type' => 'boolean',
                    'default' => true,
                    'sanitize_callback' => 'rest_sanitize_boolean',
                ],
            ],
        ]);
    }
    
    /**
     * Export settings as JSON
     * 
     * GET /mas-v2/v1/export
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function export_settings($request) {
        try {
            // Get include_metadata parameter
            $include_metadata = $request->get_param('include_metadata');
            
            // Export settings
            $export_data = $this->import_export_service->export_settings($include_metadata);
            
            // Get filename
            $filename = $this->import_export_service->get_export_filename();
            
            // Log successful export
            $this->security_logger->log_event(
                'export',
                sprintf(__('Settings exported: %s', 'modern-admin-styler-v2'), $filename),
                ['status' => 'success']
            );
            
            // Create response with proper headers for file download
            $response = new WP_REST_Response([
                'success' => true,
                'data' => $export_data,
                'filename' => $filename,
                'message' => __('Settings exported successfully', 'modern-admin-styler-v2'),
                'timestamp' => current_time('timestamp'),
            ], 200);
            
            // Set Content-Disposition header to trigger download
            $response->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $response->header('Content-Type', 'application/json');
            $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->header('Pragma', 'no-cache');
            $response->header('Expires', '0');
            
            return $response;
            
        } catch (Exception $e) {
            // Log failed export
            $this->security_logger->log_event(
                'export',
                sprintf(__('Export failed: %s', 'modern-admin-styler-v2'), $e->getMessage()),
                ['status' => 'failed']
            );
            
            return $this->error_response(
                sprintf(
                    __('Export failed: %s', 'modern-admin-styler-v2'),
                    $e->getMessage()
                ),
                'export_failed',
                500
            );
        }
    }
    
    /**
     * Import settings from JSON
     * 
     * POST /mas-v2/v1/import
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function import_settings($request) {
        // Check rate limit for import
        try {
            $this->rate_limiter->check_rate_limit('import');
        } catch (MAS_Rate_Limit_Exception $e) {
            // Log rate limit exceeded
            $this->security_logger->log_event(
                'rate_limit_exceeded',
                sprintf(__('Rate limit exceeded for import: %s', 'modern-admin-styler-v2'), $e->getMessage()),
                ['status' => 'failed']
            );
            
            // Return 429 with Retry-After header
            $response = $this->error_response(
                $e->getMessage(),
                'rate_limit_exceeded',
                429
            );
            $response->header('Retry-After', $e->get_retry_after());
            return $response;
        }
        
        try {
            // Get parameters
            $data = $request->get_param('data');
            $create_backup = $request->get_param('create_backup');
            
            // If data is a string, parse it as JSON
            if (is_string($data)) {
                $parsed_data = $this->import_export_service->validate_json($data);
                
                if (is_wp_error($parsed_data)) {
                    // Log validation failure
                    $this->security_logger->log_event(
                        'import_failed',
                        __('Import validation failed: Invalid JSON', 'modern-admin-styler-v2'),
                        ['status' => 'failed']
                    );
                    
                    return $this->error_response(
                        $parsed_data->get_error_message(),
                        $parsed_data->get_error_code(),
                        $parsed_data->get_error_data()['status']
                    );
                }
                
                $data = $parsed_data;
            }
            
            // Validate that data is an array
            if (!is_array($data)) {
                // Log validation failure
                $this->security_logger->log_event(
                    'import_failed',
                    __('Import validation failed: Data is not an array', 'modern-admin-styler-v2'),
                    ['status' => 'failed']
                );
                
                return $this->error_response(
                    __('Import data must be a valid JSON object', 'modern-admin-styler-v2'),
                    'invalid_import_data',
                    400
                );
            }
            
            // Create automatic backup before import (Phase 2)
            $backup_created_auto = $this->create_automatic_backup('Before settings import');
            
            // Import settings
            $result = $this->import_export_service->import_settings($data, $create_backup);
            
            if (is_wp_error($result)) {
                $error_data = $result->get_error_data();
                
                // Log failed import
                $this->security_logger->log_event(
                    'import_failed',
                    sprintf(__('Import failed: %s', 'modern-admin-styler-v2'), $result->get_error_message()),
                    ['status' => 'failed']
                );
                
                return $this->error_response(
                    $result->get_error_message(),
                    $result->get_error_code(),
                    isset($error_data['status']) ? $error_data['status'] : 400,
                    $error_data
                );
            }
            
            // Log successful import
            $this->security_logger->log_event(
                'import_success',
                __('Settings imported successfully', 'modern-admin-styler-v2'),
                [
                    'status' => 'success',
                    'new_value' => $data
                ]
            );
            
            return $this->success_response(
                [
                    'imported' => true,
                    'backup_created' => $create_backup || ($backup_created_auto !== false),
                ],
                __('Settings imported successfully', 'modern-admin-styler-v2'),
                200
            );
            
        } catch (Exception $e) {
            // Log exception
            $this->security_logger->log_event(
                'import_failed',
                sprintf(__('Import exception: %s', 'modern-admin-styler-v2'), $e->getMessage()),
                ['status' => 'failed']
            );
            
            return $this->error_response(
                sprintf(
                    __('Import failed: %s', 'modern-admin-styler-v2'),
                    $e->getMessage()
                ),
                'import_failed',
                500
            );
        }
    }
    
    /**
     * Create automatic backup before changes (Phase 2)
     * 
     * @param string $note Note describing the change
     * @return array|false Backup metadata or false on failure
     */
    private function create_automatic_backup($note = '') {
        // Check if retention service is available
        if (!class_exists('MAS_Backup_Retention_Service')) {
            // Fallback to regular backup service
            if (class_exists('MAS_Backup_Service')) {
                $backup_service = MAS_Backup_Service::get_instance();
                $backup = $backup_service->create_automatic_backup($note);
                return is_wp_error($backup) ? false : $backup;
            }
            return false;
        }
        
        $retention_service = MAS_Backup_Retention_Service::get_instance();
        $backup = $retention_service->create_backup(null, 'automatic', $note);
        
        return is_wp_error($backup) ? false : $backup;
    }
}
