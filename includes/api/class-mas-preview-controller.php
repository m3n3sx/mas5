<?php
/**
 * Preview REST Controller for Modern Admin Styler V2
 * 
 * Handles live preview CSS generation without saving settings.
 * Implements request debouncing and proper cache headers.
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
 * Preview REST Controller Class
 * 
 * Provides endpoints for generating temporary CSS previews.
 */
class MAS_Preview_Controller extends MAS_REST_Controller {
    
    /**
     * CSS Generator Service instance
     * 
     * @var MAS_CSS_Generator_Service
     */
    private $css_generator;
    
    /**
     * Validation Service instance
     * 
     * @var MAS_Validation_Service
     */
    private $validation_service;
    
    /**
     * Last request timestamp for debouncing
     * 
     * @var int
     */
    private static $last_request_time = 0;
    
    /**
     * Minimum time between requests in milliseconds
     * 
     * @var int
     */
    private $debounce_delay = 500;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Load CSS generator service
        require_once MAS_V2_PLUGIN_DIR . 'includes/services/class-mas-css-generator-service.php';
        $this->css_generator = MAS_CSS_Generator_Service::get_instance();
        
        // Load validation service if available
        if (class_exists('MAS_Validation_Service')) {
            $this->validation_service = MAS_Validation_Service::get_instance();
        }
    }
    
    /**
     * Register routes for preview controller
     * 
     * @return void
     */
    public function register_routes() {
        // POST /preview - Generate preview CSS
        register_rest_route($this->namespace, '/preview', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'generate_preview'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => $this->get_preview_args(),
        ]);
    }
    
    /**
     * Get preview endpoint arguments schema
     * 
     * @return array Arguments schema
     */
    private function get_preview_args() {
        return [
            'settings' => [
                'description' => __('Settings to preview', 'modern-admin-styler-v2'),
                'type' => 'object',
                'required' => true,
                'validate_callback' => [$this, 'validate_preview_settings'],
                'sanitize_callback' => [$this, 'sanitize_preview_settings'],
            ],
        ];
    }
    
    /**
     * Generate preview CSS
     * 
     * Generates temporary CSS from provided settings without saving.
     * Implements server-side debouncing to prevent overload.
     * 
     * @param WP_REST_Request $request The REST request object
     * @return WP_REST_Response|WP_Error Response with generated CSS or error
     */
    public function generate_preview($request) {
        // Server-side debouncing
        $current_time = microtime(true) * 1000; // Convert to milliseconds
        $time_since_last = $current_time - self::$last_request_time;
        
        if ($time_since_last < $this->debounce_delay && self::$last_request_time > 0) {
            return $this->error_response(
                __('Too many requests. Please wait before generating another preview.', 'modern-admin-styler-v2'),
                'rate_limited',
                429
            );
        }
        
        // Update last request time
        self::$last_request_time = $current_time;
        
        // Get settings from request
        $settings = $request->get_param('settings');
        
        if (empty($settings) || !is_array($settings)) {
            return $this->error_response(
                __('Invalid settings provided', 'modern-admin-styler-v2'),
                'invalid_settings',
                400
            );
        }
        
        try {
            // Generate CSS without caching (preview should always be fresh)
            $css = $this->css_generator->generate($settings, false);
            
            // Create response
            $response = $this->success_response([
                'css' => $css,
                'settings_count' => count($settings),
                'css_length' => strlen($css),
            ], __('Preview CSS generated successfully', 'modern-admin-styler-v2'));
            
            // Set cache headers to prevent unwanted caching
            $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->header('Pragma', 'no-cache');
            $response->header('Expires', '0');
            
            return $response;
            
        } catch (Exception $e) {
            // Log error
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MAS Preview Error: ' . $e->getMessage());
            }
            
            // Return fallback CSS
            return $this->generate_fallback_response($settings);
        }
    }
    
    /**
     * Generate fallback response on error
     * 
     * @param array $settings Settings array
     * @return WP_REST_Response Response with minimal fallback CSS
     */
    private function generate_fallback_response($settings) {
        // Generate minimal fallback CSS
        $fallback_css = "/* Fallback CSS - Error occurred during generation */\n";
        $fallback_css .= "/* Using minimal safe styles */\n\n";
        
        // Add only the most basic styles
        if (!empty($settings['menu_background'])) {
            $fallback_css .= "#adminmenu { background: {$settings['menu_background']} !important; }\n";
        }
        
        if (!empty($settings['admin_bar_background'])) {
            $fallback_css .= "#wpadminbar { background: {$settings['admin_bar_background']} !important; }\n";
        }
        
        $response = new WP_REST_Response([
            'success' => true,
            'data' => [
                'css' => $fallback_css,
                'fallback' => true,
                'message' => __('Using fallback CSS due to generation error', 'modern-admin-styler-v2'),
            ],
            'timestamp' => current_time('timestamp'),
        ], 200);
        
        // Set cache headers
        $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->header('Pragma', 'no-cache');
        
        return $response;
    }
    
    /**
     * Validate preview settings
     * 
     * @param mixed $value Settings value to validate
     * @param WP_REST_Request $request The REST request object
     * @param string $param Parameter name
     * @return bool|WP_Error True if valid, WP_Error otherwise
     */
    public function validate_preview_settings($value, $request, $param) {
        if (!is_array($value)) {
            return new WP_Error(
                'invalid_type',
                __('Settings must be an object', 'modern-admin-styler-v2'),
                ['status' => 400]
            );
        }
        
        // Use validation service if available
        if ($this->validation_service) {
            $validation_result = $this->validation_service->validate_settings($value);
            
            if (is_wp_error($validation_result)) {
                return $validation_result;
            }
        }
        
        // Basic validation for critical fields
        $errors = [];
        
        // Validate color fields
        $color_fields = [
            'menu_background', 'menu_bg', 'menu_text_color',
            'admin_bar_background', 'admin_bar_bg', 'admin_bar_text_color'
        ];
        
        foreach ($color_fields as $field) {
            if (isset($value[$field]) && !empty($value[$field])) {
                if (!$this->is_valid_color($value[$field])) {
                    $errors[$field] = sprintf(
                        __('Invalid color value for %s', 'modern-admin-styler-v2'),
                        $field
                    );
                }
            }
        }
        
        if (!empty($errors)) {
            return new WP_Error(
                'validation_failed',
                __('Settings validation failed', 'modern-admin-styler-v2'),
                ['status' => 400, 'errors' => $errors]
            );
        }
        
        return true;
    }
    
    /**
     * Sanitize preview settings
     * 
     * @param mixed $value Settings value to sanitize
     * @param WP_REST_Request $request The REST request object
     * @param string $param Parameter name
     * @return array Sanitized settings
     */
    public function sanitize_preview_settings($value, $request, $param) {
        if (!is_array($value)) {
            return [];
        }
        
        return $this->sanitize_settings($value);
    }
    
    /**
     * Validate color value
     * 
     * @param string $color Color value to validate
     * @return bool True if valid
     */
    private function is_valid_color($color) {
        // Check for hex color
        if (preg_match('/^#[a-f0-9]{6}$/i', $color)) {
            return true;
        }
        
        // Check for short hex color
        if (preg_match('/^#[a-f0-9]{3}$/i', $color)) {
            return true;
        }
        
        // Check for rgb/rgba
        if (preg_match('/^rgba?\([\d\s,\.]+\)$/i', $color)) {
            return true;
        }
        
        return false;
    }
}
