<?php
/**
 * Settings REST Controller
 * 
 * Handles all settings-related REST API endpoints including
 * GET, POST, PUT, and DELETE operations for plugin settings.
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

// Load settings service if not already loaded
require_once MAS_V2_PLUGIN_DIR . 'includes/services/class-mas-settings-service.php';

/**
 * Settings REST Controller
 * 
 * Provides CRUD operations for plugin settings via REST API.
 */
class MAS_Settings_Controller extends MAS_REST_Controller {
    
    /**
     * REST base for settings endpoints
     * 
     * @var string
     */
    protected $rest_base = 'settings';
    
    /**
     * Settings service instance
     * 
     * @var MAS_Settings_Service
     */
    private $settings_service;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->settings_service = MAS_Settings_Service::get_instance();
    }
    
    /**
     * Register routes for settings endpoints
     * 
     * @return void
     */
    public function register_routes() {
        // GET /settings - Retrieve current settings
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_settings'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => []
            ],
            'schema' => [$this, 'get_public_item_schema']
        ]);
        
        // POST /settings - Save settings (full update)
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'save_settings'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::CREATABLE)
            ]
        ]);
        
        // PUT /settings - Update settings (partial update)
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_settings'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::EDITABLE)
            ]
        ]);
        
        // DELETE /settings - Reset to defaults
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'reset_settings'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => []
            ]
        ]);
    }
    
    /**
     * Get current settings
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_settings($request) {
        try {
            // Check if settings are cached
            $cache_hit = false;
            $settings = wp_cache_get('mas_v2_settings', 'mas_v2');
            
            if ($settings === false) {
                // Cache miss - get from service
                $settings = $this->settings_service->get_settings();
                $cache_hit = false;
            } else {
                // Cache hit
                $cache_hit = true;
            }
            
            // Get last modified time for Last-Modified header
            $last_modified = $this->settings_service->get_last_modified_time();
            
            // Use optimized response with caching, ETag, and Last-Modified
            $response = $this->optimized_response($settings, $request, [
                'message' => __('Settings retrieved successfully', 'modern-admin-styler-v2'),
                'cache_max_age' => 300, // Cache for 5 minutes
                'use_etag' => true,
                'use_last_modified' => true,
                'last_modified' => $last_modified
            ]);
            
            // Add X-Cache header to indicate cache hit/miss
            $response->header('X-Cache', $cache_hit ? 'HIT' : 'MISS');
            
            return $response;
        } catch (Exception $e) {
            return $this->error_response(
                $e->getMessage(),
                'get_settings_failed',
                500
            );
        }
    }
    
    /**
     * Save settings (complete replacement)
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function save_settings($request) {
        // Check rate limit for settings save
        try {
            $this->rate_limiter->check_rate_limit('settings_save');
        } catch (MAS_Rate_Limit_Exception $e) {
            // Log rate limit exceeded
            $this->security_logger->log_event(
                'rate_limit_exceeded',
                sprintf(__('Rate limit exceeded for settings save: %s', 'modern-admin-styler-v2'), $e->getMessage()),
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
        
        $settings = $request->get_json_params();
        
        if (empty($settings)) {
            return $this->error_response(
                __('No settings data provided', 'modern-admin-styler-v2'),
                'no_settings_data',
                400
            );
        }
        
        // Get old settings for audit log
        $old_settings = $this->settings_service->get_settings();
        
        // Create automatic backup before saving (Phase 2)
        $backup_created = $this->create_automatic_backup('Before settings save');
        
        // Save settings via service
        $result = $this->settings_service->save_settings($settings);
        
        if (is_wp_error($result)) {
            // Log failed save
            $this->security_logger->log_event(
                'settings_updated',
                __('Settings save failed', 'modern-admin-styler-v2'),
                [
                    'status' => 'failed',
                    'old_value' => $old_settings,
                    'new_value' => $settings
                ]
            );
            
            return $result;
        }
        
        // Get updated settings
        $updated_settings = $this->settings_service->get_settings();
        
        // Log successful save
        $this->security_logger->log_event(
            'settings_updated',
            __('Settings saved successfully', 'modern-admin-styler-v2'),
            [
                'status' => 'success',
                'old_value' => $old_settings,
                'new_value' => $updated_settings
            ]
        );
        
        // Trigger webhook for settings.updated event
        $this->trigger_webhook('settings.updated', [
            'event' => 'settings.updated',
            'timestamp' => current_time('timestamp'),
            'user_id' => get_current_user_id(),
            'old_settings' => $old_settings,
            'new_settings' => $updated_settings,
            'backup_created' => $backup_created !== false
        ]);
        
        return $this->success_response(
            [
                'settings' => $updated_settings,
                'css_generated' => true,
                'backup_created' => $backup_created !== false
            ],
            __('Settings saved successfully', 'modern-admin-styler-v2')
        );
    }
    
    /**
     * Update settings (partial update)
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function update_settings($request) {
        $settings = $request->get_json_params();
        
        if (empty($settings)) {
            return $this->error_response(
                __('No settings data provided', 'modern-admin-styler-v2'),
                'no_settings_data',
                400
            );
        }
        
        // Create automatic backup before updating (Phase 2)
        $backup_created = $this->create_automatic_backup('Before settings update');
        
        // Get old settings for webhook
        $old_settings = $this->settings_service->get_settings();
        
        // Update settings via service
        $result = $this->settings_service->update_settings($settings);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Get updated settings
        $updated_settings = $this->settings_service->get_settings();
        
        // Trigger webhook for settings.updated event
        $this->trigger_webhook('settings.updated', [
            'event' => 'settings.updated',
            'timestamp' => current_time('timestamp'),
            'user_id' => get_current_user_id(),
            'old_settings' => $old_settings,
            'new_settings' => $updated_settings,
            'backup_created' => $backup_created !== false
        ]);
        
        return $this->success_response(
            [
                'settings' => $updated_settings,
                'css_generated' => true,
                'backup_created' => $backup_created !== false
            ],
            __('Settings updated successfully', 'modern-admin-styler-v2')
        );
    }
    
    /**
     * Reset settings to defaults
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function reset_settings($request) {
        // Get old settings for audit log
        $old_settings = $this->settings_service->get_settings();
        
        // Reset settings via service
        $result = $this->settings_service->reset_settings();
        
        if (is_wp_error($result)) {
            // Log failed reset
            $this->security_logger->log_event(
                'settings_reset',
                __('Settings reset failed', 'modern-admin-styler-v2'),
                ['status' => 'failed']
            );
            
            return $result;
        }
        
        // Get default settings
        $default_settings = $this->settings_service->get_settings();
        
        // Log successful reset
        $this->security_logger->log_event(
            'settings_reset',
            __('Settings reset to defaults', 'modern-admin-styler-v2'),
            [
                'status' => 'success',
                'old_value' => $old_settings,
                'new_value' => $default_settings
            ]
        );
        
        return $this->success_response(
            [
                'settings' => $default_settings,
                'backup_created' => true,
                'css_generated' => true
            ],
            __('Settings reset to defaults successfully', 'modern-admin-styler-v2')
        );
    }
    
    /**
     * Get the schema for settings, conforming to JSON Schema
     * 
     * @return array Schema array
     */
    public function get_item_schema() {
        if ($this->schema) {
            return $this->add_additional_fields_schema($this->schema);
        }
        
        $schema = [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title' => 'settings',
            'type' => 'object',
            'properties' => [
                // General settings
                'enable_plugin' => [
                    'description' => __('Enable plugin functionality', 'modern-admin-styler-v2'),
                    'type' => 'boolean',
                    'default' => true
                ],
                'theme' => [
                    'description' => __('Theme name', 'modern-admin-styler-v2'),
                    'type' => 'string',
                    'default' => 'modern'
                ],
                'color_scheme' => [
                    'description' => __('Color scheme (light/dark)', 'modern-admin-styler-v2'),
                    'type' => 'string',
                    'enum' => ['light', 'dark'],
                    'default' => 'light'
                ],
                
                // Admin Bar settings
                'admin_bar_background' => [
                    'description' => __('Admin bar background color', 'modern-admin-styler-v2'),
                    'type' => 'string',
                    'format' => 'hex-color',
                    'default' => '#23282d'
                ],
                'admin_bar_text_color' => [
                    'description' => __('Admin bar text color', 'modern-admin-styler-v2'),
                    'type' => 'string',
                    'format' => 'hex-color',
                    'default' => '#ffffff'
                ],
                
                // Menu settings
                'menu_background' => [
                    'description' => __('Menu background color', 'modern-admin-styler-v2'),
                    'type' => 'string',
                    'format' => 'hex-color',
                    'default' => '#23282d'
                ],
                'menu_text_color' => [
                    'description' => __('Menu text color', 'modern-admin-styler-v2'),
                    'type' => 'string',
                    'format' => 'hex-color',
                    'default' => '#ffffff'
                ],
                'menu_hover_background' => [
                    'description' => __('Menu hover background color', 'modern-admin-styler-v2'),
                    'type' => 'string',
                    'format' => 'hex-color',
                    'default' => '#32373c'
                ],
                'menu_hover_text_color' => [
                    'description' => __('Menu hover text color', 'modern-admin-styler-v2'),
                    'type' => 'string',
                    'format' => 'hex-color',
                    'default' => '#00a0d2'
                ],
                'menu_width' => [
                    'description' => __('Menu width in pixels', 'modern-admin-styler-v2'),
                    'type' => 'integer',
                    'minimum' => 100,
                    'maximum' => 400,
                    'default' => 160
                ],
                
                // Effects settings
                'enable_animations' => [
                    'description' => __('Enable animations', 'modern-admin-styler-v2'),
                    'type' => 'boolean',
                    'default' => true
                ],
                'animation_speed' => [
                    'description' => __('Animation speed in milliseconds', 'modern-admin-styler-v2'),
                    'type' => 'integer',
                    'minimum' => 100,
                    'maximum' => 1000,
                    'default' => 300
                ],
                'glassmorphism_effects' => [
                    'description' => __('Enable glassmorphism effects', 'modern-admin-styler-v2'),
                    'type' => 'boolean',
                    'default' => false
                ],
                
                // Advanced settings
                'custom_css' => [
                    'description' => __('Custom CSS code', 'modern-admin-styler-v2'),
                    'type' => 'string',
                    'default' => ''
                ],
                'debug_mode' => [
                    'description' => __('Enable debug mode', 'modern-admin-styler-v2'),
                    'type' => 'boolean',
                    'default' => false
                ]
            ]
        ];
        
        $this->schema = $schema;
        
        return $this->add_additional_fields_schema($this->schema);
    }
    
    /**
     * Get public schema for settings
     * 
     * @return array Public schema
     */
    public function get_public_item_schema() {
        $schema = $this->get_item_schema();
        
        // Remove internal fields from public schema
        unset($schema['properties']['debug_mode']);
        
        return $schema;
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
