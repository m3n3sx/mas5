<?php
/**
 * AJAX Deprecation Wrapper for Modern Admin Styler V2
 * 
 * Wraps existing AJAX handlers with deprecation warnings.
 *
 * @package ModernAdminStylerV2
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class MAS_AJAX_Deprecation_Wrapper {
    
    /**
     * Deprecation service
     */
    private $deprecation_service;
    
    /**
     * Original plugin instance
     */
    private $plugin_instance;
    
    /**
     * AJAX handler mappings to REST endpoints
     */
    private $handler_mappings = [
        'mas_v2_save_settings' => '/wp-json/mas-v2/v1/settings',
        'mas_v2_reset_settings' => '/wp-json/mas-v2/v1/settings',
        'mas_v2_export_settings' => '/wp-json/mas-v2/v1/export',
        'mas_v2_import_settings' => '/wp-json/mas-v2/v1/import',
        'mas_v2_get_preview_css' => '/wp-json/mas-v2/v1/preview',
        'mas_v2_save_theme' => '/wp-json/mas-v2/v1/themes',
        'mas_v2_diagnostics' => '/wp-json/mas-v2/v1/diagnostics',
        'mas_v2_list_backups' => '/wp-json/mas-v2/v1/backups',
        'mas_v2_restore_backup' => '/wp-json/mas-v2/v1/backups/{id}/restore',
        'mas_v2_create_backup' => '/wp-json/mas-v2/v1/backups',
        'mas_v2_delete_backup' => '/wp-json/mas-v2/v1/backups/{id}'
    ];
    
    /**
     * Constructor
     *
     * @param object $plugin_instance
     */
    public function __construct($plugin_instance) {
        $this->plugin_instance = $plugin_instance;
        $this->deprecation_service = new MAS_Deprecation_Service();
        
        $this->wrap_ajax_handlers();
    }
    
    /**
     * Wrap all AJAX handlers with deprecation warnings
     */
    private function wrap_ajax_handlers() {
        foreach ($this->handler_mappings as $handler => $rest_endpoint) {
            $this->wrap_handler($handler, $rest_endpoint);
        }
    }
    
    /**
     * Wrap individual AJAX handler
     *
     * @param string $handler_name
     * @param string $rest_endpoint
     */
    private function wrap_handler($handler_name, $rest_endpoint) {
        // Remove original handler with high priority to ensure it's removed
        remove_action("wp_ajax_{$handler_name}", [$this->plugin_instance, $this->get_method_name($handler_name)], 10);
        
        // Add wrapped handler with same priority
        add_action("wp_ajax_{$handler_name}", function() use ($handler_name, $rest_endpoint) {
            $this->handle_deprecated_ajax($handler_name, $rest_endpoint);
        }, 10);
    }
    
    /**
     * Handle deprecated AJAX request
     *
     * @param string $handler_name
     * @param string $rest_endpoint
     */
    private function handle_deprecated_ajax($handler_name, $rest_endpoint) {
        // Add deprecation headers before any output
        $this->add_deprecation_headers($handler_name, $rest_endpoint);
        
        // Record usage statistics
        $this->deprecation_service->record_handler_usage($handler_name);
        
        // Log deprecation warning
        if ($this->deprecation_service->should_warn_for_handler($handler_name)) {
            $context = [
                'post_data' => $_POST,
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'POST',
                'content_type' => $_SERVER['CONTENT_TYPE'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ];
            
            $this->deprecation_service->log_ajax_deprecation($handler_name, $rest_endpoint, $context);
        }
        
        // Call original handler method
        $method_name = $this->get_method_name($handler_name);
        if (method_exists($this->plugin_instance, $method_name)) {
            call_user_func([$this->plugin_instance, $method_name]);
        } else {
            wp_die(__('Handler method not found', 'modern-admin-styler-v2'), 500);
        }
    }
    
    /**
     * Get method name from handler name
     *
     * @param string $handler_name
     * @return string
     */
    private function get_method_name($handler_name) {
        // Convert mas_v2_save_settings to ajaxSaveSettings
        $parts = explode('_', str_replace('mas_v2_', '', $handler_name));
        $method = 'ajax';
        
        foreach ($parts as $part) {
            $method .= ucfirst($part);
        }
        
        return $method;
    }
    
    /**
     * Add deprecation info to AJAX responses
     *
     * @param array $response
     * @param string $handler_name
     * @param string $rest_endpoint
     * @return array
     */
    public function add_deprecation_info_to_response($response, $handler_name, $rest_endpoint) {
        if (!is_array($response)) {
            $response = ['data' => $response];
        }
        
        $response['_deprecated'] = [
            'handler' => $handler_name,
            'message' => sprintf(
                __('This AJAX handler is deprecated. Use REST API endpoint: %s', 'modern-admin-styler-v2'),
                $rest_endpoint
            ),
            'rest_endpoint' => $rest_endpoint,
            'migration_guide' => 'https://github.com/your-repo/modern-admin-styler-v2/wiki/REST-API-Migration'
        ];
        
        return $response;
    }
    
    /**
     * Get handler statistics
     *
     * @return array
     */
    public function get_handler_stats() {
        return $this->deprecation_service->get_deprecation_stats();
    }
    
    /**
     * Check if handler exists and is wrapped
     *
     * @param string $handler_name
     * @return bool
     */
    public function is_handler_wrapped($handler_name) {
        return isset($this->handler_mappings[$handler_name]);
    }
    
    /**
     * Get all wrapped handlers
     *
     * @return array
     */
    public function get_wrapped_handlers() {
        return array_keys($this->handler_mappings);
    }
    
    /**
     * Get REST endpoint for handler
     *
     * @param string $handler_name
     * @return string|null
     */
    public function get_rest_endpoint($handler_name) {
        return $this->handler_mappings[$handler_name] ?? null;
    }
    
    /**
     * Unwrap all handlers (for testing or rollback)
     */
    public function unwrap_handlers() {
        foreach ($this->handler_mappings as $handler => $rest_endpoint) {
            // Remove wrapped handler
            remove_action("wp_ajax_{$handler}", [$this, 'handle_deprecated_ajax']);
            
            // Restore original handler
            $method_name = $this->get_method_name($handler);
            if (method_exists($this->plugin_instance, $method_name)) {
                add_action("wp_ajax_{$handler}", [$this->plugin_instance, $method_name]);
            }
        }
    }
    
    /**
     * Add deprecation headers to AJAX responses
     */
    public function add_deprecation_headers($handler_name, $rest_endpoint) {
        if (!headers_sent()) {
            header('X-MAS-Deprecated: true');
            header('X-MAS-Deprecated-Handler: ' . $handler_name);
            header('X-MAS-REST-Endpoint: ' . $rest_endpoint);
            header('X-MAS-Migration-Guide: https://github.com/your-repo/modern-admin-styler-v2/wiki/REST-API-Migration');
        }
    }
}