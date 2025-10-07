<?php
/**
 * Batch Operations Controller
 *
 * Handles batch operations for settings, backups, and themes with transaction support.
 * Provides atomic batch processing with automatic rollback on failure.
 *
 * @package    Modern_Admin_Styler_V2
 * @subpackage API
 * @since      2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MAS_Batch_Controller
 *
 * REST API controller for batch operations with transaction support.
 */
class MAS_Batch_Controller extends MAS_REST_Controller {

    /**
     * Transaction service instance
     *
     * @var MAS_Transaction_Service
     */
    private $transaction_service;

    /**
     * Settings service instance
     *
     * @var MAS_Settings_Service
     */
    private $settings_service;

    /**
     * Backup service instance
     *
     * @var MAS_Backup_Retention_Service
     */
    private $backup_service;

    /**
     * Theme service instance
     *
     * @var MAS_Theme_Preset_Service
     */
    private $theme_service;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        $this->settings_service = new MAS_Settings_Service();
        $this->backup_service = new MAS_Backup_Retention_Service();
        $this->theme_service = new MAS_Theme_Preset_Service();
        $this->transaction_service = new MAS_Transaction_Service(
            $this->settings_service,
            $this->security_logger
        );
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Batch settings updates
        register_rest_route($this->namespace, '/settings/batch', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'batch_update_settings'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'operations' => [
                    'required' => true,
                    'type' => 'array',
                    'description' => 'Array of operations to perform',
                    'validate_callback' => [$this, 'validate_operations_array']
                ],
                'async' => [
                    'required' => false,
                    'type' => 'boolean',
                    'default' => false,
                    'description' => 'Process asynchronously for large batches'
                ]
            ]
        ]);

        // Batch backup operations
        register_rest_route($this->namespace, '/backups/batch', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'batch_backup_operations'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'operations' => [
                    'required' => true,
                    'type' => 'array',
                    'description' => 'Array of backup operations to perform',
                    'validate_callback' => [$this, 'validate_operations_array']
                ]
            ]
        ]);

        // Batch theme application with validation
        register_rest_route($this->namespace, '/themes/batch-apply', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'batch_apply_theme'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'theme_id' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Theme ID to apply'
                ],
                'validate_only' => [
                    'required' => false,
                    'type' => 'boolean',
                    'default' => false,
                    'description' => 'Only validate without applying'
                ]
            ]
        ]);

        // Batch operation status endpoint
        register_rest_route($this->namespace, '/batch/status/(?P<job_id>[a-zA-Z0-9_-]+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_batch_status'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'job_id' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Batch job ID'
                ]
            ]
        ]);
    }

    /**
     * Batch update settings
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function batch_update_settings($request) {
        $operations = $request->get_param('operations');
        $async = $request->get_param('async');

        if (!is_array($operations) || empty($operations)) {
            return $this->error_response('No operations provided', 'invalid_batch', 400);
        }

        // Check if batch is large (> 50 operations) and should be processed asynchronously
        $operation_count = count($operations);
        $should_async = $async || $operation_count > 50;

        if ($should_async) {
            return $this->schedule_async_batch($operations, 'settings');
        }

        $start_time = microtime(true);

        // Start transaction
        try {
            $txn_id = $this->transaction_service->begin_transaction();
        } catch (Exception $e) {
            return $this->error_response(
                'Failed to start transaction: ' . $e->getMessage(),
                'transaction_failed',
                500
            );
        }

        $results = [];
        $success_count = 0;
        $error_count = 0;

        try {
            foreach ($operations as $index => $operation) {
                try {
                    // Validate operation structure
                    if (!isset($operation['type']) || !isset($operation['data'])) {
                        throw new Exception('Invalid operation structure');
                    }

                    // Execute operation
                    $result = $this->execute_operation($operation);
                    
                    $results[$index] = [
                        'success' => true,
                        'data' => $result,
                        'operation' => $operation['type']
                    ];
                    
                    $success_count++;

                    // Add to transaction log
                    $this->transaction_service->add_operation(
                        $operation['type'],
                        $operation['data']
                    );

                } catch (Exception $e) {
                    $results[$index] = [
                        'success' => false,
                        'error' => $e->getMessage(),
                        'operation' => $operation['type'] ?? 'unknown'
                    ];
                    
                    $error_count++;

                    // Rollback on any error
                    throw $e;
                }
            }

            // Commit if all successful
            $this->transaction_service->commit();

            $processing_time = round((microtime(true) - $start_time) * 1000, 2);

            return $this->success_response([
                'transaction_id' => $txn_id,
                'success_count' => $success_count,
                'error_count' => $error_count,
                'processing_time_ms' => $processing_time,
                'results' => $results
            ], 'Batch operation completed successfully');

        } catch (Exception $e) {
            // Rollback on error
            try {
                $this->transaction_service->rollback($e->getMessage());
            } catch (Exception $rollback_error) {
                // Log rollback failure
                error_log('Rollback failed: ' . $rollback_error->getMessage());
            }

            $processing_time = round((microtime(true) - $start_time) * 1000, 2);

            return $this->error_response(
                'Batch operation failed: ' . $e->getMessage(),
                'batch_failed',
                400,
                [
                    'transaction_id' => $txn_id,
                    'success_count' => $success_count,
                    'error_count' => $error_count,
                    'processing_time_ms' => $processing_time,
                    'results' => $results
                ]
            );
        }
    }

    /**
     * Batch backup operations
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function batch_backup_operations($request) {
        $operations = $request->get_param('operations');

        if (!is_array($operations) || empty($operations)) {
            return $this->error_response('No operations provided', 'invalid_batch', 400);
        }

        $start_time = microtime(true);
        $results = [];
        $success_count = 0;
        $error_count = 0;

        foreach ($operations as $index => $operation) {
            try {
                if (!isset($operation['type'])) {
                    throw new Exception('Operation type not specified');
                }

                $result = null;

                switch ($operation['type']) {
                    case 'create':
                        $note = $operation['data']['note'] ?? '';
                        $result = $this->backup_service->create_backup('manual', $note);
                        break;

                    case 'delete':
                        if (!isset($operation['data']['backup_id'])) {
                            throw new Exception('Backup ID not specified');
                        }
                        $result = $this->backup_service->delete_backup($operation['data']['backup_id']);
                        break;

                    case 'cleanup':
                        $result = $this->backup_service->cleanup_old_backups();
                        break;

                    default:
                        throw new Exception('Unknown operation type: ' . $operation['type']);
                }

                $results[$index] = [
                    'success' => true,
                    'data' => $result,
                    'operation' => $operation['type']
                ];
                
                $success_count++;

            } catch (Exception $e) {
                $results[$index] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'operation' => $operation['type'] ?? 'unknown'
                ];
                
                $error_count++;
            }
        }

        $processing_time = round((microtime(true) - $start_time) * 1000, 2);

        return $this->success_response([
            'success_count' => $success_count,
            'error_count' => $error_count,
            'processing_time_ms' => $processing_time,
            'results' => $results
        ], 'Batch backup operations completed');
    }

    /**
     * Batch apply theme with validation
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function batch_apply_theme($request) {
        $theme_id = $request->get_param('theme_id');
        $validate_only = $request->get_param('validate_only');

        try {
            // Get theme
            $theme = $this->theme_service->get_theme($theme_id);
            
            if (!$theme) {
                return $this->error_response(
                    'Theme not found: ' . $theme_id,
                    'theme_not_found',
                    404
                );
            }

            // Validate theme settings
            $validation_result = $this->theme_service->validate_theme($theme);
            
            if (!$validation_result['valid']) {
                return $this->error_response(
                    'Theme validation failed',
                    'validation_failed',
                    400,
                    ['errors' => $validation_result['errors']]
                );
            }

            // If validate only, return validation result
            if ($validate_only) {
                return $this->success_response([
                    'valid' => true,
                    'theme_id' => $theme_id,
                    'theme_name' => $theme['name'] ?? $theme_id
                ], 'Theme validation successful');
            }

            // Apply theme with transaction support
            $txn_id = $this->transaction_service->begin_transaction();

            try {
                // Create backup before applying
                $backup = $this->backup_service->create_backup('automatic', 'Before applying theme: ' . $theme_id);

                // Apply theme
                $result = $this->theme_service->apply_theme($theme_id);

                // Add to transaction log
                $this->transaction_service->add_operation('apply_theme', [
                    'theme_id' => $theme_id,
                    'backup_id' => $backup['id']
                ]);

                // Commit transaction
                $this->transaction_service->commit();

                return $this->success_response([
                    'transaction_id' => $txn_id,
                    'theme_id' => $theme_id,
                    'backup_id' => $backup['id'],
                    'applied' => true
                ], 'Theme applied successfully');

            } catch (Exception $e) {
                // Rollback on error
                $this->transaction_service->rollback($e->getMessage());
                
                throw $e;
            }

        } catch (Exception $e) {
            return $this->error_response(
                'Failed to apply theme: ' . $e->getMessage(),
                'theme_apply_failed',
                500
            );
        }
    }

    /**
     * Execute a single operation with validation
     *
     * @param array $operation Operation to execute
     * @return mixed Operation result
     * @throws Exception If operation fails
     */
    private function execute_operation($operation) {
        $type = $operation['type'];
        $data = $operation['data'];

        // Validate operation before execution
        $validation_result = $this->validate_operation($operation);
        if (!$validation_result['valid']) {
            throw new Exception($validation_result['error']);
        }

        // Execute based on operation type
        switch ($type) {
            case 'update_setting':
                return $this->execute_update_setting($data);

            case 'update_settings':
                return $this->execute_update_settings($data);

            case 'reset_setting':
                return $this->execute_reset_setting($data);

            case 'reset_all_settings':
                return $this->execute_reset_all_settings();

            default:
                throw new Exception('Unknown operation type: ' . $type);
        }
    }

    /**
     * Validate an operation before execution
     *
     * @param array $operation Operation to validate
     * @return array Validation result with 'valid' and 'error' keys
     */
    private function validate_operation($operation) {
        $type = $operation['type'];
        $data = $operation['data'] ?? [];

        switch ($type) {
            case 'update_setting':
                if (!isset($data['key'])) {
                    return ['valid' => false, 'error' => 'Setting key required'];
                }
                if (!isset($data['value'])) {
                    return ['valid' => false, 'error' => 'Setting value required'];
                }
                break;

            case 'update_settings':
                if (!is_array($data)) {
                    return ['valid' => false, 'error' => 'Settings data must be an array'];
                }
                if (empty($data)) {
                    return ['valid' => false, 'error' => 'Settings data cannot be empty'];
                }
                break;

            case 'reset_setting':
                if (!isset($data['key'])) {
                    return ['valid' => false, 'error' => 'Setting key required'];
                }
                break;

            case 'reset_all_settings':
                // No validation needed
                break;

            default:
                return ['valid' => false, 'error' => 'Unknown operation type: ' . $type];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Execute update single setting operation
     *
     * @param array $data Operation data
     * @return bool Success status
     * @throws Exception If operation fails
     */
    private function execute_update_setting($data) {
        $current_settings = $this->settings_service->get_settings();
        $current_settings[$data['key']] = $data['value'];
        
        return $this->settings_service->save_settings($current_settings, false);
    }

    /**
     * Execute update multiple settings operation
     *
     * @param array $data Settings data
     * @return bool Success status
     * @throws Exception If operation fails
     */
    private function execute_update_settings($data) {
        return $this->settings_service->save_settings($data, false);
    }

    /**
     * Execute reset single setting operation
     *
     * @param array $data Operation data
     * @return bool Success status
     * @throws Exception If operation fails
     */
    private function execute_reset_setting($data) {
        $defaults = $this->settings_service->get_defaults();
        $current_settings = $this->settings_service->get_settings();
        
        if (!isset($defaults[$data['key']])) {
            throw new Exception('Setting not found in defaults: ' . $data['key']);
        }
        
        $current_settings[$data['key']] = $defaults[$data['key']];
        return $this->settings_service->save_settings($current_settings, false);
    }

    /**
     * Execute reset all settings operation
     *
     * @return bool Success status
     * @throws Exception If operation fails
     */
    private function execute_reset_all_settings() {
        $defaults = $this->settings_service->get_defaults();
        return $this->settings_service->save_settings($defaults, false);
    }

    /**
     * Get detailed results summary
     *
     * @param array $results Results array
     * @return array Summary with counts and details
     */
    private function get_results_summary($results) {
        $success_count = 0;
        $error_count = 0;
        $errors = [];

        foreach ($results as $index => $result) {
            if ($result['success']) {
                $success_count++;
            } else {
                $error_count++;
                $errors[] = [
                    'index' => $index,
                    'operation' => $result['operation'],
                    'error' => $result['error']
                ];
            }
        }

        return [
            'success_count' => $success_count,
            'error_count' => $error_count,
            'total_count' => count($results),
            'errors' => $errors
        ];
    }

    /**
     * Validate operations array
     *
     * @param array $operations Operations array to validate
     * @return bool True if valid
     */
    public function validate_operations_array($operations) {
        if (!is_array($operations)) {
            return false;
        }

        if (empty($operations)) {
            return false;
        }

        // Validate each operation has required structure
        foreach ($operations as $operation) {
            if (!is_array($operation)) {
                return false;
            }

            if (!isset($operation['type'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Schedule asynchronous batch processing
     *
     * @param array  $operations Operations to process
     * @param string $type       Batch type (settings, backups, etc.)
     * @return WP_REST_Response Response with job ID
     */
    private function schedule_async_batch($operations, $type) {
        $job_id = uniqid('batch_' . $type . '_', true);

        // Store batch job data in transient (expires in 1 hour)
        $job_data = [
            'id' => $job_id,
            'type' => $type,
            'operations' => $operations,
            'status' => 'pending',
            'created_at' => time(),
            'user_id' => get_current_user_id(),
            'total_operations' => count($operations),
            'processed_operations' => 0,
            'success_count' => 0,
            'error_count' => 0,
            'results' => []
        ];

        set_transient('mas_batch_job_' . $job_id, $job_data, HOUR_IN_SECONDS);

        // Schedule background processing using WordPress cron
        wp_schedule_single_event(time() + 10, 'mas_process_batch_job', [$job_id]);

        return $this->success_response([
            'job_id' => $job_id,
            'status' => 'pending',
            'total_operations' => count($operations),
            'message' => 'Batch job scheduled for asynchronous processing',
            'status_url' => rest_url($this->namespace . '/batch/status/' . $job_id)
        ], 'Batch job scheduled successfully');
    }

    /**
     * Get batch operation status
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_batch_status($request) {
        $job_id = $request->get_param('job_id');

        $job_data = get_transient('mas_batch_job_' . $job_id);

        if (!$job_data) {
            return $this->error_response(
                'Batch job not found or expired',
                'job_not_found',
                404
            );
        }

        // Calculate progress percentage
        $progress = 0;
        if ($job_data['total_operations'] > 0) {
            $progress = round(($job_data['processed_operations'] / $job_data['total_operations']) * 100, 2);
        }

        return $this->success_response([
            'job_id' => $job_data['id'],
            'type' => $job_data['type'],
            'status' => $job_data['status'],
            'progress' => $progress,
            'total_operations' => $job_data['total_operations'],
            'processed_operations' => $job_data['processed_operations'],
            'success_count' => $job_data['success_count'],
            'error_count' => $job_data['error_count'],
            'created_at' => $job_data['created_at'],
            'results' => $job_data['results']
        ]);
    }

    /**
     * Process batch job in background
     *
     * This method is called by WordPress cron to process batch jobs asynchronously.
     *
     * @param string $job_id Job ID to process
     */
    public static function process_batch_job($job_id) {
        $job_data = get_transient('mas_batch_job_' . $job_id);

        if (!$job_data) {
            return;
        }

        // Update status to processing
        $job_data['status'] = 'processing';
        $job_data['started_at'] = time();
        set_transient('mas_batch_job_' . $job_id, $job_data, HOUR_IN_SECONDS);

        // Initialize services
        $settings_service = new MAS_Settings_Service();
        $security_logger = new MAS_Security_Logger_Service();
        $transaction_service = new MAS_Transaction_Service($settings_service, $security_logger);

        // Start transaction
        try {
            $txn_id = $transaction_service->begin_transaction();

            foreach ($job_data['operations'] as $index => $operation) {
                try {
                    // Create temporary controller instance to execute operation
                    $controller = new self();
                    $result = $controller->execute_operation($operation);

                    $job_data['results'][$index] = [
                        'success' => true,
                        'data' => $result,
                        'operation' => $operation['type']
                    ];

                    $job_data['success_count']++;
                    $transaction_service->add_operation($operation['type'], $operation['data']);

                } catch (Exception $e) {
                    $job_data['results'][$index] = [
                        'success' => false,
                        'error' => $e->getMessage(),
                        'operation' => $operation['type'] ?? 'unknown'
                    ];

                    $job_data['error_count']++;
                    
                    // Rollback on error
                    throw $e;
                }

                $job_data['processed_operations']++;

                // Update progress every 10 operations
                if ($job_data['processed_operations'] % 10 === 0) {
                    set_transient('mas_batch_job_' . $job_id, $job_data, HOUR_IN_SECONDS);
                }
            }

            // Commit transaction
            $transaction_service->commit();

            // Update final status
            $job_data['status'] = 'completed';
            $job_data['completed_at'] = time();
            $job_data['transaction_id'] = $txn_id;

        } catch (Exception $e) {
            // Rollback on error
            $transaction_service->rollback($e->getMessage());

            $job_data['status'] = 'failed';
            $job_data['error'] = $e->getMessage();
            $job_data['completed_at'] = time();
        }

        // Save final job data (keep for 24 hours)
        set_transient('mas_batch_job_' . $job_id, $job_data, DAY_IN_SECONDS);
    }
}
