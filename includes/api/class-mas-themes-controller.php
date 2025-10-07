<?php
/**
 * Themes REST Controller
 * 
 * Handles all theme-related REST API endpoints including
 * GET, POST, PUT, and DELETE operations for themes and palettes.
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

// Load theme service if not already loaded
require_once MAS_V2_PLUGIN_DIR . 'includes/services/class-mas-theme-service.php';

/**
 * Themes REST Controller
 * 
 * Provides CRUD operations for themes via REST API.
 */
class MAS_Themes_Controller extends MAS_REST_Controller {
    
    /**
     * REST base for themes endpoints
     * 
     * @var string
     */
    protected $rest_base = 'themes';
    
    /**
     * Theme service instance
     * 
     * @var MAS_Theme_Service
     */
    private $theme_service;
    
    /**
     * Theme preset service instance
     * 
     * @var MAS_Theme_Preset_Service
     */
    private $preset_service;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->theme_service = MAS_Theme_Service::get_instance();
        
        // Load preset service if available
        if (class_exists('MAS_Theme_Preset_Service')) {
            $this->preset_service = MAS_Theme_Preset_Service::get_instance();
        }
    }
    
    /**
     * Register routes for themes endpoints
     * 
     * @return void
     */
    public function register_routes() {
        // GET /themes - List all themes
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_themes'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => [
                    'type' => [
                        'description' => __('Filter themes by type (predefined or custom)', 'modern-admin-styler-v2'),
                        'type' => 'string',
                        'enum' => ['predefined', 'custom'],
                        'sanitize_callback' => 'sanitize_text_field'
                    ]
                ]
            ],
            'schema' => [$this, 'get_public_item_schema']
        ]);
        
        // POST /themes - Create custom theme
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_theme'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => $this->get_create_theme_args()
            ]
        ]);
        
        // GET /themes/{id} - Get specific theme
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[a-zA-Z0-9-]+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_theme'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => [
                    'id' => [
                        'description' => __('Theme ID', 'modern-admin-styler-v2'),
                        'type' => 'string',
                        'required' => true,
                        'sanitize_callback' => 'sanitize_key'
                    ]
                ]
            ]
        ]);
        
        // PUT /themes/{id} - Update custom theme
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[a-zA-Z0-9-]+)', [
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_theme'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => $this->get_update_theme_args()
            ]
        ]);
        
        // DELETE /themes/{id} - Delete custom theme
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[a-zA-Z0-9-]+)', [
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_theme'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => [
                    'id' => [
                        'description' => __('Theme ID', 'modern-admin-styler-v2'),
                        'type' => 'string',
                        'required' => true,
                        'sanitize_callback' => 'sanitize_key'
                    ]
                ]
            ]
        ]);
        
        // POST /themes/{id}/apply - Apply theme
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[a-zA-Z0-9-]+)/apply', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'apply_theme'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => [
                    'id' => [
                        'description' => __('Theme ID to apply', 'modern-admin-styler-v2'),
                        'type' => 'string',
                        'required' => true,
                        'sanitize_callback' => 'sanitize_key'
                    ]
                ]
            ]
        ]);
        
        // GET /themes/presets - List predefined theme presets
        register_rest_route($this->namespace, '/' . $this->rest_base . '/presets', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_presets'],
                'permission_callback' => [$this, 'check_permission'],
            ]
        ]);
        
        // POST /themes/preview - Preview theme without applying
        register_rest_route($this->namespace, '/' . $this->rest_base . '/preview', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'preview_theme'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => [
                    'settings' => [
                        'description' => __('Theme settings to preview', 'modern-admin-styler-v2'),
                        'type' => 'object',
                        'required' => true
                    ],
                    'name' => [
                        'description' => __('Optional theme name', 'modern-admin-styler-v2'),
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ]
                ]
            ]
        ]);
        
        // POST /themes/export - Export theme with metadata
        register_rest_route($this->namespace, '/' . $this->rest_base . '/export', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'export_theme'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => [
                    'theme_id' => [
                        'description' => __('Theme ID to export', 'modern-admin-styler-v2'),
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_key'
                    ],
                    'theme_data' => [
                        'description' => __('Theme data to export (alternative to theme_id)', 'modern-admin-styler-v2'),
                        'type' => 'object'
                    ]
                ]
            ]
        ]);
        
        // POST /themes/import - Import theme with validation
        register_rest_route($this->namespace, '/' . $this->rest_base . '/import', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'import_theme'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => [
                    'import_data' => [
                        'description' => __('Theme import data with version and checksum', 'modern-admin-styler-v2'),
                        'type' => 'object',
                        'required' => true
                    ],
                    'create_theme' => [
                        'description' => __('Whether to create the theme after import', 'modern-admin-styler-v2'),
                        'type' => 'boolean',
                        'default' => false
                    ]
                ]
            ]
        ]);
    }
    
    /**
     * Get all themes
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_themes($request) {
        try {
            $themes = $this->theme_service->get_themes();
            
            // Filter by type if specified
            $type = $request->get_param('type');
            if ($type) {
                $themes = array_filter($themes, function($theme) use ($type) {
                    return isset($theme['type']) && $theme['type'] === $type;
                });
                // Re-index array
                $themes = array_values($themes);
            }
            
            // Get pagination parameters
            $limit = $request->get_param('limit') ?: 20;
            $page = $request->get_param('page') ?: 1;
            $offset = ($page - 1) * $limit;
            
            // Calculate pagination info
            $total_themes = count($themes);
            $total_pages = ceil($total_themes / $limit);
            
            // Apply pagination
            $paginated_themes = array_slice($themes, $offset, $limit);
            
            // Create response
            $response = $this->success_response(
                $paginated_themes,
                sprintf(
                    __('Retrieved %d of %d theme(s)', 'modern-admin-styler-v2'),
                    count($paginated_themes),
                    $total_themes
                ),
                200,
                $request
            );
            
            // Add pagination headers
            $response->header('X-WP-Total', $total_themes);
            $response->header('X-WP-TotalPages', $total_pages);
            
            // Add Link header for pagination navigation
            $links = [];
            $base_url = rest_url($this->namespace . '/' . $this->rest_base);
            $query_args = ['limit' => $limit];
            
            if ($type) {
                $query_args['type'] = $type;
            }
            
            // First page
            if ($page > 1) {
                $links[] = '<' . add_query_arg(array_merge($query_args, ['page' => 1]), $base_url) . '>; rel="first"';
            }
            
            // Previous page
            if ($page > 1) {
                $links[] = '<' . add_query_arg(array_merge($query_args, ['page' => $page - 1]), $base_url) . '>; rel="prev"';
            }
            
            // Next page
            if ($page < $total_pages) {
                $links[] = '<' . add_query_arg(array_merge($query_args, ['page' => $page + 1]), $base_url) . '>; rel="next"';
            }
            
            // Last page
            if ($page < $total_pages) {
                $links[] = '<' . add_query_arg(array_merge($query_args, ['page' => $total_pages]), $base_url) . '>; rel="last"';
            }
            
            if (!empty($links)) {
                $response->header('Link', implode(', ', $links));
            }
            
            return $response;
        } catch (Exception $e) {
            return $this->error_response(
                $e->getMessage(),
                'get_themes_failed',
                500
            );
        }
    }
    
    /**
     * Get a specific theme
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_theme($request) {
        $theme_id = $request->get_param('id');
        
        $theme = $this->theme_service->get_theme($theme_id);
        
        if (is_wp_error($theme)) {
            return $theme;
        }
        
        return $this->success_response(
            $theme,
            __('Theme retrieved successfully', 'modern-admin-styler-v2')
        );
    }
    
    /**
     * Create a custom theme
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function create_theme($request) {
        $theme_data = $request->get_json_params();
        
        if (empty($theme_data)) {
            return $this->error_response(
                __('No theme data provided', 'modern-admin-styler-v2'),
                'no_theme_data',
                400
            );
        }
        
        // Create theme via service
        $result = $this->theme_service->create_theme($theme_data);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return $this->success_response(
            $result,
            __('Theme created successfully', 'modern-admin-styler-v2'),
            201
        );
    }
    
    /**
     * Update a custom theme
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function update_theme($request) {
        $theme_id = $request->get_param('id');
        $theme_data = $request->get_json_params();
        
        if (empty($theme_data)) {
            return $this->error_response(
                __('No theme data provided', 'modern-admin-styler-v2'),
                'no_theme_data',
                400
            );
        }
        
        // Update theme via service
        $result = $this->theme_service->update_theme($theme_id, $theme_data);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return $this->success_response(
            $result,
            __('Theme updated successfully', 'modern-admin-styler-v2')
        );
    }
    
    /**
     * Delete a custom theme
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function delete_theme($request) {
        $theme_id = $request->get_param('id');
        
        // Delete theme via service
        $result = $this->theme_service->delete_theme($theme_id);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return $this->success_response(
            ['deleted' => true, 'id' => $theme_id],
            __('Theme deleted successfully', 'modern-admin-styler-v2')
        );
    }
    
    /**
     * Apply a theme to current settings
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function apply_theme($request) {
        // Check rate limit for theme application
        try {
            $this->rate_limiter->check_rate_limit('theme_apply');
        } catch (MAS_Rate_Limit_Exception $e) {
            // Log rate limit exceeded
            $this->security_logger->log_event(
                'rate_limit_exceeded',
                sprintf(__('Rate limit exceeded for theme application: %s', 'modern-admin-styler-v2'), $e->getMessage()),
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
        
        $theme_id = $request->get_param('id');
        
        // Create automatic backup before applying theme (Phase 2)
        $backup_created = $this->create_automatic_backup('Before applying theme: ' . $theme_id);
        
        // Apply theme via service
        $result = $this->theme_service->apply_theme($theme_id);
        
        if (is_wp_error($result)) {
            // Log failed theme application
            $this->security_logger->log_event(
                'theme_applied',
                sprintf(__('Theme application failed: %s', 'modern-admin-styler-v2'), $theme_id),
                ['status' => 'failed']
            );
            
            return $result;
        }
        
        // Log successful theme application
        $this->security_logger->log_event(
            'theme_applied',
            sprintf(__('Theme applied: %s', 'modern-admin-styler-v2'), $theme_id),
            [
                'status' => 'success',
                'new_value' => ['theme_id' => $theme_id]
            ]
        );
        
        // Trigger webhook for theme.applied event
        $this->trigger_webhook('theme.applied', [
            'event' => 'theme.applied',
            'timestamp' => current_time('timestamp'),
            'user_id' => get_current_user_id(),
            'theme_id' => $theme_id,
            'backup_created' => $backup_created !== false
        ]);
        
        return $this->success_response(
            [
                'applied' => true,
                'theme_id' => $theme_id,
                'backup_created' => $backup_created !== false
            ],
            sprintf(
                __('Theme "%s" applied successfully', 'modern-admin-styler-v2'),
                $theme_id
            )
        );
    }
    
    /**
     * Get arguments for creating a theme
     * 
     * @return array
     */
    private function get_create_theme_args() {
        return [
            'id' => [
                'description' => __('Unique theme identifier (lowercase letters, numbers, and hyphens only)', 'modern-admin-styler-v2'),
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => function($value) {
                    if (!preg_match('/^[a-z0-9-]+$/', $value)) {
                        return new WP_Error(
                            'invalid_theme_id',
                            __('Theme ID must contain only lowercase letters, numbers, and hyphens', 'modern-admin-styler-v2')
                        );
                    }
                    return true;
                }
            ],
            'name' => [
                'description' => __('Theme display name', 'modern-admin-styler-v2'),
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field'
            ],
            'description' => [
                'description' => __('Theme description', 'modern-admin-styler-v2'),
                'type' => 'string',
                'sanitize_callback' => 'sanitize_textarea_field'
            ],
            'settings' => [
                'description' => __('Theme settings (colors, styles, etc.)', 'modern-admin-styler-v2'),
                'type' => 'object',
                'required' => true
            ]
        ];
    }
    
    /**
     * Get arguments for updating a theme
     * 
     * @return array
     */
    private function get_update_theme_args() {
        return [
            'id' => [
                'description' => __('Theme ID', 'modern-admin-styler-v2'),
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_key'
            ],
            'name' => [
                'description' => __('Theme display name', 'modern-admin-styler-v2'),
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            ],
            'description' => [
                'description' => __('Theme description', 'modern-admin-styler-v2'),
                'type' => 'string',
                'sanitize_callback' => 'sanitize_textarea_field'
            ],
            'settings' => [
                'description' => __('Theme settings (colors, styles, etc.)', 'modern-admin-styler-v2'),
                'type' => 'object'
            ]
        ];
    }
    
    /**
     * Get predefined theme presets
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_presets($request) {
        if (!$this->preset_service) {
            return $this->error_response(
                __('Theme preset service not available', 'modern-admin-styler-v2'),
                'service_unavailable',
                503
            );
        }
        
        try {
            $presets = $this->preset_service->get_presets();
            
            return $this->success_response(
                $presets,
                sprintf(
                    __('Retrieved %d theme preset(s)', 'modern-admin-styler-v2'),
                    count($presets)
                )
            );
        } catch (Exception $e) {
            return $this->error_response(
                $e->getMessage(),
                'get_presets_failed',
                500
            );
        }
    }
    
    /**
     * Preview theme without applying changes
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function preview_theme($request) {
        if (!$this->preset_service) {
            return $this->error_response(
                __('Theme preset service not available', 'modern-admin-styler-v2'),
                'service_unavailable',
                503
            );
        }
        
        $theme_data = $request->get_json_params();
        
        if (empty($theme_data)) {
            return $this->error_response(
                __('No theme data provided', 'modern-admin-styler-v2'),
                'no_theme_data',
                400
            );
        }
        
        // Generate preview
        $preview = $this->preset_service->preview_theme($theme_data);
        
        if (is_wp_error($preview)) {
            return $preview;
        }
        
        // Set cache headers to prevent caching
        $response = $this->success_response(
            $preview,
            __('Theme preview generated successfully', 'modern-admin-styler-v2')
        );
        
        $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->header('Pragma', 'no-cache');
        $response->header('Expires', '0');
        
        return $response;
    }
    
    /**
     * Export theme with version metadata and checksum
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function export_theme($request) {
        if (!$this->preset_service) {
            return $this->error_response(
                __('Theme preset service not available', 'modern-admin-styler-v2'),
                'service_unavailable',
                503
            );
        }
        
        $theme_id = $request->get_param('theme_id');
        $theme_data = $request->get_param('theme_data');
        
        if (!$theme_id && !$theme_data) {
            return $this->error_response(
                __('Either theme_id or theme_data must be provided', 'modern-admin-styler-v2'),
                'missing_parameters',
                400
            );
        }
        
        // Export theme
        $export_data = $this->preset_service->export_theme($theme_id, $theme_data);
        
        if (is_wp_error($export_data)) {
            return $export_data;
        }
        
        return $this->success_response(
            $export_data,
            __('Theme exported successfully', 'modern-admin-styler-v2')
        );
    }
    
    /**
     * Import theme with version compatibility validation
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function import_theme($request) {
        if (!$this->preset_service) {
            return $this->error_response(
                __('Theme preset service not available', 'modern-admin-styler-v2'),
                'service_unavailable',
                503
            );
        }
        
        $import_data = $request->get_param('import_data');
        $create_theme = $request->get_param('create_theme');
        
        if (empty($import_data)) {
            return $this->error_response(
                __('No import data provided', 'modern-admin-styler-v2'),
                'no_import_data',
                400
            );
        }
        
        // Import and validate theme
        $imported_theme = $this->preset_service->import_theme($import_data);
        
        if (is_wp_error($imported_theme)) {
            return $imported_theme;
        }
        
        // Optionally create the theme
        if ($create_theme) {
            $created_theme = $this->theme_service->create_theme($imported_theme);
            
            if (is_wp_error($created_theme)) {
                return $created_theme;
            }
            
            return $this->success_response(
                $created_theme,
                __('Theme imported and created successfully', 'modern-admin-styler-v2'),
                201
            );
        }
        
        return $this->success_response(
            $imported_theme,
            __('Theme imported and validated successfully', 'modern-admin-styler-v2')
        );
    }
    
    /**
     * Get the schema for themes
     * 
     * @return array
     */
    public function get_public_item_schema() {
        if ($this->schema) {
            return $this->schema;
        }
        
        $this->schema = [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title' => 'theme',
            'type' => 'object',
            'properties' => [
                'id' => [
                    'description' => __('Unique theme identifier', 'modern-admin-styler-v2'),
                    'type' => 'string',
                    'context' => ['view', 'edit'],
                    'readonly' => true
                ],
                'name' => [
                    'description' => __('Theme display name', 'modern-admin-styler-v2'),
                    'type' => 'string',
                    'context' => ['view', 'edit']
                ],
                'description' => [
                    'description' => __('Theme description', 'modern-admin-styler-v2'),
                    'type' => 'string',
                    'context' => ['view', 'edit']
                ],
                'type' => [
                    'description' => __('Theme type (predefined or custom)', 'modern-admin-styler-v2'),
                    'type' => 'string',
                    'enum' => ['predefined', 'custom'],
                    'context' => ['view'],
                    'readonly' => true
                ],
                'readonly' => [
                    'description' => __('Whether theme is read-only', 'modern-admin-styler-v2'),
                    'type' => 'boolean',
                    'context' => ['view'],
                    'readonly' => true
                ],
                'settings' => [
                    'description' => __('Theme settings', 'modern-admin-styler-v2'),
                    'type' => 'object',
                    'context' => ['view', 'edit']
                ],
                'metadata' => [
                    'description' => __('Theme metadata', 'modern-admin-styler-v2'),
                    'type' => 'object',
                    'context' => ['view'],
                    'readonly' => true
                ]
            ]
        ];
        
        return $this->schema;
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
