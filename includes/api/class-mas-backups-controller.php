<?php
/**
 * Backups REST Controller
 * 
 * Handles all backup-related REST API endpoints including
 * listing, creating, restoring, and deleting backups.
 *
 * @package ModernAdminStylerV2
 * @subpackage API
 * @since 2.2.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load base controller if not already loaded
require_once MAS_V2_PLUGIN_DIR . 'includes/api/class-mas-rest-controller.php';

// Load backup service if not already loaded
require_once MAS_V2_PLUGIN_DIR . 'includes/services/class-mas-backup-service.php';

// Load backup retention service for Phase 2 features
if (file_exists(MAS_V2_PLUGIN_DIR . 'includes/services/class-mas-backup-retention-service.php')) {
    require_once MAS_V2_PLUGIN_DIR . 'includes/services/class-mas-backup-retention-service.php';
}

/**
 * Backups REST Controller
 * 
 * Provides CRUD operations for backups via REST API.
 * Enhanced in Phase 2 with retention policies, download, and batch operations.
 */
class MAS_Backups_Controller extends MAS_REST_Controller {
    
    /**
     * REST base for backups endpoints
     * 
     * @var string
     */
    protected $rest_base = 'backups';
    
    /**
     * Backup service instance
     * 
     * @var MAS_Backup_Service
     */
    private $backup_service;
    
    /**
     * Backup retention service instance (Phase 2)
     * 
     * @var MAS_Backup_Retention_Service
     */
    private $retention_service;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->backup_service = MAS_Backup_Service::get_instance();
        
        // Use retention service if available (Phase 2)
        if (class_exists('MAS_Backup_Retention_Service')) {
            $this->retention_service = MAS_Backup_Retention_Service::get_instance();
        }
    }
    
    /**
     * Register routes for backup endpoints
     * 
     * @return void
     */
    public function register_routes() {
        // GET /backups - List all backups
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'list_backups'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => [
                    'limit' => [
                        'description' => __('Maximum number of backups to return', 'modern-admin-styler-v2'),
                        'type' => 'integer',
                        'default' => 0,
                        'minimum' => 0,
                        'sanitize_callback' => 'absint'
                    ],
                    'offset' => [
                        'description' => __('Offset for pagination', 'modern-admin-styler-v2'),
                        'type' => 'integer',
                        'default' => 0,
                        'minimum' => 0,
                        'sanitize_callback' => 'absint'
                    ]
                ]
            ],
            'schema' => [$this, 'get_public_item_schema']
        ]);
        
        // POST /backups - Create new backup
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_backup'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => [
                    'note' => [
                        'description' => __('Optional note about the backup', 'modern-admin-styler-v2'),
                        'type' => 'string',
                        'default' => '',
                        'sanitize_callback' => 'sanitize_text_field'
                    ],
                    'name' => [
                        'description' => __('Optional custom name for the backup', 'modern-admin-styler-v2'),
                        'type' => 'string',
                        'default' => '',
                        'sanitize_callback' => 'sanitize_text_field'
                    ]
                ]
            ]
        ]);
        
        // GET /backups/{id} - Get specific backup
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[a-zA-Z0-9_-]+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_backup'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => [
                    'id' => [
                        'description' => __('Backup ID', 'modern-admin-styler-v2'),
                        'type' => 'string',
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field'
                    ]
                ]
            ]
        ]);
        
        // POST /backups/{id}/restore - Restore backup
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[a-zA-Z0-9_-]+)/restore', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'restore_backup'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => [
                    'id' => [
                        'description' => __('Backup ID to restore', 'modern-admin-styler-v2'),
                        'type' => 'string',
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field'
                    ]
                ]
            ]
        ]);
        
        // DELETE /backups/{id} - Delete backup
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[a-zA-Z0-9_-]+)', [
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_backup'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => [
                    'id' => [
                        'description' => __('Backup ID to delete', 'modern-admin-styler-v2'),
                        'type' => 'string',
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field'
                    ]
                ]
            ]
        ]);
        
        // GET /backups/statistics - Get backup statistics
        register_rest_route($this->namespace, '/' . $this->rest_base . '/statistics', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_statistics'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => []
            ]
        ]);
        
        // GET /backups/{id}/download - Download backup as JSON file (Phase 2)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[a-zA-Z0-9_-]+)/download', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'download_backup'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => [
                    'id' => [
                        'description' => __('Backup ID to download', 'modern-admin-styler-v2'),
                        'type' => 'string',
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field'
                    ]
                ]
            ]
        ]);
        
        // POST /backups/batch - Batch backup operations (Phase 2)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/batch', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'batch_operations'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => [
                    'operations' => [
                        'description' => __('Array of batch operations to perform', 'modern-admin-styler-v2'),
                        'type' => 'array',
                        'required' => true,
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'action' => [
                                    'type' => 'string',
                                    'enum' => ['create', 'delete', 'restore']
                                ],
                                'backup_id' => [
                                    'type' => 'string'
                                ],
                                'note' => [
                                    'type' => 'string'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);
        
        // POST /backups/cleanup - Manual cleanup trigger (Phase 2)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/cleanup', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'cleanup_backups'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => []
            ]
        ]);
    }
    
    /**
     * List all backups
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function list_backups($request) {
        $limit = $request->get_param('limit') ?: 10;
        $offset = $request->get_param('offset') ?: 0;
        $page = $request->get_param('page') ?: 1;
        
        // Calculate offset from page if provided
        if ($page > 1) {
            $offset = ($page - 1) * $limit;
        }
        
        // Get all backups for total count
        $all_backups = $this->backup_service->list_backups(0, 0);
        $total_backups = count($all_backups);
        
        // Get paginated backups
        $backups = $this->backup_service->list_backups($limit, $offset);
        
        // Calculate pagination info
        $total_pages = ceil($total_backups / $limit);
        
        // Create response
        $response = $this->success_response(
            $backups,
            __('Backups retrieved successfully', 'modern-admin-styler-v2'),
            200,
            $request
        );
        
        // Add pagination headers
        $response->header('X-WP-Total', $total_backups);
        $response->header('X-WP-TotalPages', $total_pages);
        
        // Add Link header for pagination navigation
        $links = [];
        $base_url = rest_url($this->namespace . '/' . $this->rest_base);
        
        // First page
        if ($page > 1) {
            $links[] = '<' . add_query_arg(['page' => 1, 'limit' => $limit], $base_url) . '>; rel="first"';
        }
        
        // Previous page
        if ($page > 1) {
            $links[] = '<' . add_query_arg(['page' => $page - 1, 'limit' => $limit], $base_url) . '>; rel="prev"';
        }
        
        // Next page
        if ($page < $total_pages) {
            $links[] = '<' . add_query_arg(['page' => $page + 1, 'limit' => $limit], $base_url) . '>; rel="next"';
        }
        
        // Last page
        if ($page < $total_pages) {
            $links[] = '<' . add_query_arg(['page' => $total_pages, 'limit' => $limit], $base_url) . '>; rel="last"';
        }
        
        if (!empty($links)) {
            $response->header('Link', implode(', ', $links));
        }
        
        return $response;
    }
    
    /**
     * Get a specific backup
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_backup($request) {
        $backup_id = $request->get_param('id');
        
        $backup = $this->backup_service->get_backup($backup_id);
        
        if (is_wp_error($backup)) {
            return $backup;
        }
        
        return $this->success_response(
            $backup,
            __('Backup retrieved successfully', 'modern-admin-styler-v2'),
            200
        );
    }
    
    /**
     * Create a new backup
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function create_backup($request) {
        // Check rate limit for backup creation
        try {
            $this->rate_limiter->check_rate_limit('backup_create');
        } catch (MAS_Rate_Limit_Exception $e) {
            // Log rate limit exceeded
            $this->security_logger->log_event(
                'rate_limit_exceeded',
                sprintf(__('Rate limit exceeded for backup creation: %s', 'modern-admin-styler-v2'), $e->getMessage()),
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
        
        $note = $request->get_param('note');
        $name = $request->get_param('name');
        
        // Use retention service if available (Phase 2)
        if ($this->retention_service) {
            $backup = $this->retention_service->create_backup(null, 'manual', $note, $name);
        } else {
            $backup = $this->backup_service->create_backup(null, 'manual', $note);
        }
        
        if (is_wp_error($backup)) {
            // Log failed backup creation
            $this->security_logger->log_event(
                'backup_created',
                __('Backup creation failed', 'modern-admin-styler-v2'),
                ['status' => 'failed']
            );
            
            return $backup;
        }
        
        // Log successful backup creation
        $this->security_logger->log_event(
            'backup_created',
            sprintf(__('Manual backup created: %s', 'modern-admin-styler-v2'), $name ?: $backup['id']),
            [
                'status' => 'success',
                'new_value' => $backup
            ]
        );
        
        // Trigger webhook for backup.created event
        $this->trigger_webhook('backup.created', [
            'event' => 'backup.created',
            'timestamp' => current_time('timestamp'),
            'user_id' => get_current_user_id(),
            'backup_id' => $backup['id'],
            'backup_type' => 'manual',
            'backup_note' => $note,
            'backup_name' => $name
        ]);
        
        return $this->success_response(
            $backup,
            __('Backup created successfully', 'modern-admin-styler-v2'),
            201
        );
    }
    
    /**
     * Restore a backup
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function restore_backup($request) {
        $backup_id = $request->get_param('id');
        
        // Get backup details for logging
        $backup = $this->backup_service->get_backup($backup_id);
        
        $result = $this->backup_service->restore_backup($backup_id);
        
        if (is_wp_error($result)) {
            // Log failed restore
            $this->security_logger->log_event(
                'backup_restored',
                sprintf(__('Backup restore failed: %s', 'modern-admin-styler-v2'), $backup_id),
                [
                    'status' => 'failed',
                    'old_value' => $backup
                ]
            );
            
            return $result;
        }
        
        // Log successful restore
        $this->security_logger->log_event(
            'backup_restored',
            sprintf(__('Backup restored: %s', 'modern-admin-styler-v2'), $backup_id),
            [
                'status' => 'success',
                'old_value' => $backup
            ]
        );
        
        // Trigger webhook for backup.restored event
        $this->trigger_webhook('backup.restored', [
            'event' => 'backup.restored',
            'timestamp' => current_time('timestamp'),
            'user_id' => get_current_user_id(),
            'backup_id' => $backup_id,
            'backup_timestamp' => $backup['timestamp'] ?? null
        ]);
        
        return $this->success_response(
            ['backup_id' => $backup_id],
            __('Backup restored successfully', 'modern-admin-styler-v2'),
            200
        );
    }
    
    /**
     * Delete a backup
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function delete_backup($request) {
        $backup_id = $request->get_param('id');
        
        // Get backup details for logging
        $backup = $this->backup_service->get_backup($backup_id);
        
        $result = $this->backup_service->delete_backup($backup_id);
        
        if (is_wp_error($result)) {
            // Log failed deletion
            $this->security_logger->log_event(
                'backup_deleted',
                sprintf(__('Backup deletion failed: %s', 'modern-admin-styler-v2'), $backup_id),
                [
                    'status' => 'failed',
                    'old_value' => $backup
                ]
            );
            
            return $result;
        }
        
        // Log successful deletion
        $this->security_logger->log_event(
            'backup_deleted',
            sprintf(__('Backup deleted: %s', 'modern-admin-styler-v2'), $backup_id),
            [
                'status' => 'success',
                'old_value' => $backup
            ]
        );
        
        return $this->success_response(
            ['backup_id' => $backup_id],
            __('Backup deleted successfully', 'modern-admin-styler-v2'),
            200
        );
    }
    
    /**
     * Get backup statistics
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_statistics($request) {
        // Use retention service if available (Phase 2)
        if ($this->retention_service) {
            $statistics = $this->retention_service->get_statistics();
        } else {
            $statistics = $this->backup_service->get_statistics();
        }
        
        return $this->success_response(
            $statistics,
            __('Statistics retrieved successfully', 'modern-admin-styler-v2'),
            200
        );
    }
    
    /**
     * Download backup as JSON file (Phase 2)
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function download_backup($request) {
        if (!$this->retention_service) {
            return new WP_Error(
                'feature_not_available',
                __('Backup download feature is not available', 'modern-admin-styler-v2'),
                ['status' => 501]
            );
        }
        
        $backup_id = $request->get_param('id');
        
        $download_data = $this->retention_service->download_backup($backup_id);
        
        if (is_wp_error($download_data)) {
            return $download_data;
        }
        
        // Create response with download headers
        $response = new WP_REST_Response($download_data['content'], 200);
        
        // Set Content-Disposition header to trigger download
        $response->header('Content-Disposition', 'attachment; filename="' . $download_data['filename'] . '"');
        $response->header('Content-Type', $download_data['mime_type']);
        $response->header('Content-Length', $download_data['size']);
        
        return $response;
    }
    
    /**
     * Batch backup operations (Phase 2)
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function batch_operations($request) {
        if (!$this->retention_service) {
            return new WP_Error(
                'feature_not_available',
                __('Batch operations feature is not available', 'modern-admin-styler-v2'),
                ['status' => 501]
            );
        }
        
        $operations = $request->get_param('operations');
        
        if (empty($operations) || !is_array($operations)) {
            return new WP_Error(
                'invalid_operations',
                __('Operations array is required', 'modern-admin-styler-v2'),
                ['status' => 400]
            );
        }
        
        $results = [];
        $success_count = 0;
        $error_count = 0;
        
        foreach ($operations as $index => $operation) {
            $action = isset($operation['action']) ? $operation['action'] : '';
            $backup_id = isset($operation['backup_id']) ? $operation['backup_id'] : '';
            $note = isset($operation['note']) ? $operation['note'] : '';
            
            $result = [
                'index' => $index,
                'action' => $action,
                'backup_id' => $backup_id,
            ];
            
            switch ($action) {
                case 'create':
                    $backup = $this->retention_service->create_backup(null, 'manual', $note);
                    if (is_wp_error($backup)) {
                        $result['success'] = false;
                        $result['error'] = $backup->get_error_message();
                        $error_count++;
                    } else {
                        $result['success'] = true;
                        $result['backup'] = $backup;
                        $success_count++;
                    }
                    break;
                    
                case 'delete':
                    if (empty($backup_id)) {
                        $result['success'] = false;
                        $result['error'] = __('Backup ID is required for delete operation', 'modern-admin-styler-v2');
                        $error_count++;
                    } else {
                        $delete_result = $this->backup_service->delete_backup($backup_id);
                        if (is_wp_error($delete_result)) {
                            $result['success'] = false;
                            $result['error'] = $delete_result->get_error_message();
                            $error_count++;
                        } else {
                            $result['success'] = true;
                            $success_count++;
                        }
                    }
                    break;
                    
                case 'restore':
                    if (empty($backup_id)) {
                        $result['success'] = false;
                        $result['error'] = __('Backup ID is required for restore operation', 'modern-admin-styler-v2');
                        $error_count++;
                    } else {
                        $restore_result = $this->backup_service->restore_backup($backup_id);
                        if (is_wp_error($restore_result)) {
                            $result['success'] = false;
                            $result['error'] = $restore_result->get_error_message();
                            $error_count++;
                        } else {
                            $result['success'] = true;
                            $success_count++;
                        }
                    }
                    break;
                    
                default:
                    $result['success'] = false;
                    $result['error'] = sprintf(__('Unknown action: %s', 'modern-admin-styler-v2'), $action);
                    $error_count++;
            }
            
            $results[] = $result;
        }
        
        return $this->success_response(
            [
                'results' => $results,
                'summary' => [
                    'total' => count($operations),
                    'success' => $success_count,
                    'errors' => $error_count,
                ]
            ],
            sprintf(
                __('Batch operation completed: %d successful, %d errors', 'modern-admin-styler-v2'),
                $success_count,
                $error_count
            ),
            200
        );
    }
    
    /**
     * Manual cleanup trigger (Phase 2)
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function cleanup_backups($request) {
        if (!$this->retention_service) {
            return new WP_Error(
                'feature_not_available',
                __('Cleanup feature is not available', 'modern-admin-styler-v2'),
                ['status' => 501]
            );
        }
        
        $cleanup_result = $this->retention_service->cleanup_old_backups();
        
        return $this->success_response(
            $cleanup_result,
            sprintf(
                __('Cleanup completed: %d backups deleted', 'modern-admin-styler-v2'),
                $cleanup_result['deleted_count']
            ),
            200
        );
    }
    
    /**
     * Get the schema for backups
     * 
     * @return array Schema array
     */
    public function get_public_item_schema() {
        if ($this->schema) {
            return $this->schema;
        }
        
        $this->schema = [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title' => 'backup',
            'type' => 'object',
            'properties' => [
                'id' => [
                    'description' => __('Unique identifier for the backup', 'modern-admin-styler-v2'),
                    'type' => 'string',
                    'context' => ['view', 'edit'],
                    'readonly' => true
                ],
                'timestamp' => [
                    'description' => __('Unix timestamp when backup was created', 'modern-admin-styler-v2'),
                    'type' => 'integer',
                    'context' => ['view', 'edit'],
                    'readonly' => true
                ],
                'date' => [
                    'description' => __('Human-readable date when backup was created', 'modern-admin-styler-v2'),
                    'type' => 'string',
                    'format' => 'date-time',
                    'context' => ['view', 'edit'],
                    'readonly' => true
                ],
                'type' => [
                    'description' => __('Backup type (manual or automatic)', 'modern-admin-styler-v2'),
                    'type' => 'string',
                    'enum' => ['manual', 'automatic'],
                    'context' => ['view', 'edit'],
                    'readonly' => true
                ],
                'settings' => [
                    'description' => __('Settings data stored in the backup', 'modern-admin-styler-v2'),
                    'type' => 'object',
                    'context' => ['view', 'edit']
                ],
                'metadata' => [
                    'description' => __('Backup metadata', 'modern-admin-styler-v2'),
                    'type' => 'object',
                    'context' => ['view', 'edit'],
                    'properties' => [
                        'plugin_version' => [
                            'description' => __('Plugin version when backup was created', 'modern-admin-styler-v2'),
                            'type' => 'string'
                        ],
                        'wordpress_version' => [
                            'description' => __('WordPress version when backup was created', 'modern-admin-styler-v2'),
                            'type' => 'string'
                        ],
                        'user_id' => [
                            'description' => __('User ID who created the backup', 'modern-admin-styler-v2'),
                            'type' => 'integer'
                        ],
                        'note' => [
                            'description' => __('Optional note about the backup', 'modern-admin-styler-v2'),
                            'type' => 'string'
                        ]
                    ]
                ]
            ]
        ];
        
        return $this->schema;
    }
}
