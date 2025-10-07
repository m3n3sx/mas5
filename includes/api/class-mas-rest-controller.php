<?php
/**
 * Base REST Controller for Modern Admin Styler V2
 * 
 * Provides common functionality for all REST API endpoints including
 * authentication, permission checks, and standardized response methods.
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
 * Base REST Controller Class
 * 
 * All REST API controllers should extend this class to inherit
 * common authentication, authorization, and response formatting.
 */
abstract class MAS_REST_Controller extends WP_REST_Controller {
    
    /**
     * REST API namespace
     * 
     * @var string
     */
    protected $namespace = 'mas-v2/v1';
    
    /**
     * Rate limiter service instance
     * 
     * @var MAS_Rate_Limiter_Service
     */
    protected $rate_limiter;
    
    /**
     * Security logger service instance
     * 
     * @var MAS_Security_Logger_Service
     */
    protected $security_logger;
    
    /**
     * Deprecation service instance
     * 
     * @var MAS_Deprecation_Service
     */
    protected $deprecation_service;
    
    /**
     * Version manager instance
     * 
     * @var MAS_Version_Manager
     */
    protected $version_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize rate limiter
        $this->rate_limiter = new MAS_Rate_Limiter_Service();
        
        // Initialize security logger
        $this->security_logger = new MAS_Security_Logger_Service();
        
        // Initialize deprecation service
        if (class_exists('MAS_Deprecation_Service')) {
            $this->deprecation_service = new MAS_Deprecation_Service();
        }
        
        // Initialize version manager
        if (class_exists('MAS_Version_Manager')) {
            $this->version_manager = new MAS_Version_Manager();
        }
        
        // Hook into REST API initialization
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    /**
     * Register routes for this controller
     * 
     * Must be implemented by child classes
     * 
     * @return void
     */
    abstract public function register_routes();
    
    /**
     * Check if user has permission to access REST API endpoints
     * 
     * Verifies that the current user has the 'manage_options' capability,
     * validates nonce for write operations (POST, PUT, DELETE), and
     * enforces rate limiting. Logs all security events.
     * 
     * @param WP_REST_Request $request The REST request object
     * @return bool|WP_Error True if user has permission, WP_Error otherwise
     */
    public function check_permission($request) {
        $endpoint = $request->get_route();
        $user_id = get_current_user_id();
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            // Log permission denial
            $this->security_logger->log_permission_denied(
                $user_id,
                $endpoint,
                'manage_options'
            );
            
            return new WP_Error(
                'rest_forbidden',
                __('You do not have permission to access this resource.', 'modern-admin-styler-v2'),
                ['status' => 403]
            );
        }
        
        // Verify nonce for write operations
        $method = $request->get_method();
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $nonce = $request->get_header('X-WP-Nonce');
            
            if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
                // Log nonce failure
                $this->security_logger->log_nonce_failure($endpoint, $nonce ?: 'missing');
                
                return new WP_Error(
                    'rest_cookie_invalid_nonce',
                    __('Cookie nonce is invalid.', 'modern-admin-styler-v2'),
                    ['status' => 403]
                );
            }
        }
        
        // Check rate limit
        $rate_limit_check = $this->rate_limiter->check_rate_limit($endpoint);
        
        if (is_wp_error($rate_limit_check)) {
            // Log rate limit exceeded
            $transient_key = 'mas_rate_limit_' . $user_id . '_' . str_replace('/', '_', $endpoint);
            $request_data = get_transient($transient_key);
            $request_count = $request_data ? $request_data['count'] : 0;
            
            $this->security_logger->log_rate_limit_exceeded(
                $user_id,
                $endpoint,
                $request_count
            );
            
            return $rate_limit_check;
        }
        
        return true;
    }
    
    /**
     * Create standardized error response
     * 
     * @param string $message Error message
     * @param string $code Error code (default: 'error')
     * @param int $status HTTP status code (default: 400)
     * @param array $additional_data Additional data to include in response
     * @return WP_Error
     */
    protected function error_response($message, $code = 'error', $status = 400, $additional_data = []) {
        $data = array_merge(['status' => $status], $additional_data);
        
        // Log error if debug mode is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'MAS REST API Error [%s]: %s (Status: %d)',
                $code,
                $message,
                $status
            ));
        }
        
        return new WP_Error($code, $message, $data);
    }
    
    /**
     * Create standardized success response
     * 
     * @param mixed $data Response data
     * @param string $message Success message (optional)
     * @param int $status HTTP status code (default: 200)
     * @param WP_REST_Request|null $request Request object for rate limit headers
     * @return WP_REST_Response
     */
    protected function success_response($data, $message = '', $status = 200, $request = null) {
        $response_data = [
            'success' => true,
            'data' => $data
        ];
        
        // Add message if provided
        if (!empty($message)) {
            $response_data['message'] = $message;
        }
        
        // Add timestamp
        $response_data['timestamp'] = current_time('timestamp');
        
        $response = new WP_REST_Response($response_data, $status);
        
        // Add rate limit headers if request provided
        if ($request !== null) {
            $endpoint = $request->get_route();
            $headers = $this->rate_limiter->get_rate_limit_headers($endpoint);
            
            foreach ($headers as $key => $value) {
                $response->header($key, $value);
            }
        }
        
        return $response;
    }
    
    /**
     * Validate required parameters
     * 
     * @param WP_REST_Request $request The REST request object
     * @param array $required_params Array of required parameter names
     * @return bool|WP_Error True if all required params present, WP_Error otherwise
     */
    protected function validate_required_params($request, $required_params) {
        $missing_params = [];
        
        foreach ($required_params as $param) {
            if (!$request->has_param($param)) {
                $missing_params[] = $param;
            }
        }
        
        if (!empty($missing_params)) {
            return $this->error_response(
                sprintf(
                    __('Missing required parameters: %s', 'modern-admin-styler-v2'),
                    implode(', ', $missing_params)
                ),
                'missing_parameters',
                400,
                ['missing_parameters' => $missing_params]
            );
        }
        
        return true;
    }
    
    /**
     * Sanitize settings data
     * 
     * @param array $data Raw data to sanitize
     * @return array Sanitized data
     */
    protected function sanitize_settings($data) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            // Handle different data types
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize_settings($value);
            } elseif (is_bool($value)) {
                $sanitized[$key] = (bool) $value;
            } elseif (is_numeric($value)) {
                $sanitized[$key] = is_float($value) ? (float) $value : (int) $value;
            } else {
                // Sanitize as text field by default
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize color value (hex color)
     * 
     * @param string $color Color value to sanitize
     * @return string Sanitized color or empty string if invalid
     */
    protected function sanitize_color($color) {
        // Use WordPress built-in sanitization
        $sanitized = sanitize_hex_color($color);
        
        // If sanitization fails, return empty string
        return $sanitized ? $sanitized : '';
    }
    
    /**
     * Sanitize CSS unit value (px, em, rem, %, etc.)
     * 
     * @param string $value CSS unit value to sanitize
     * @return string Sanitized value
     */
    protected function sanitize_css_unit($value) {
        // Remove any potentially harmful characters
        $value = sanitize_text_field($value);
        
        // Validate format: number + unit
        if (preg_match('/^-?\d+(\.\d+)?(px|em|rem|%|vh|vw|vmin|vmax)$/', $value)) {
            return $value;
        }
        
        return '';
    }
    
    /**
     * Sanitize boolean value
     * 
     * @param mixed $value Value to sanitize
     * @return bool Sanitized boolean
     */
    protected function sanitize_boolean($value) {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
    
    /**
     * Sanitize integer value
     * 
     * @param mixed $value Value to sanitize
     * @param int $min Minimum allowed value (optional)
     * @param int $max Maximum allowed value (optional)
     * @return int Sanitized integer
     */
    protected function sanitize_integer($value, $min = null, $max = null) {
        $sanitized = (int) $value;
        
        if ($min !== null && $sanitized < $min) {
            $sanitized = $min;
        }
        
        if ($max !== null && $sanitized > $max) {
            $sanitized = $max;
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize array of values
     * 
     * @param array $array Array to sanitize
     * @param callable $callback Sanitization callback for each item
     * @return array Sanitized array
     */
    protected function sanitize_array($array, $callback = 'sanitize_text_field') {
        if (!is_array($array)) {
            return [];
        }
        
        return array_map($callback, $array);
    }
    
    /**
     * Sanitize JSON string
     * 
     * @param string $json JSON string to sanitize
     * @return string|false Sanitized JSON or false if invalid
     */
    protected function sanitize_json($json) {
        // Decode to validate
        $decoded = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        
        // Re-encode to ensure clean JSON
        return wp_json_encode($decoded);
    }
    
    /**
     * Escape output for safe display
     * 
     * @param mixed $data Data to escape
     * @return mixed Escaped data
     */
    protected function escape_output($data) {
        if (is_array($data)) {
            return array_map([$this, 'escape_output'], $data);
        }
        
        if (is_string($data)) {
            return esc_html($data);
        }
        
        return $data;
    }
    
    /**
     * Sanitize file name for safe storage
     * 
     * @param string $filename File name to sanitize
     * @return string Sanitized file name
     */
    protected function sanitize_filename($filename) {
        return sanitize_file_name($filename);
    }
    
    /**
     * Sanitize URL
     * 
     * @param string $url URL to sanitize
     * @return string Sanitized URL
     */
    protected function sanitize_url($url) {
        return esc_url_raw($url);
    }
    
    /**
     * Get namespace for this controller
     * 
     * @return string
     */
    public function get_namespace() {
        return $this->namespace;
    }
    
    /**
     * Add ETag header to response for conditional requests
     * 
     * Generates an ETag based on the response data and adds it to the response.
     * Checks if the client's If-None-Match header matches the ETag.
     * 
     * @param WP_REST_Response $response Response object
     * @param mixed $data Data used to generate ETag
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Modified response with ETag header
     */
    protected function add_etag_header($response, $data, $request) {
        // Generate ETag from data
        $etag = '"' . md5(json_encode($data)) . '"';
        
        // Add ETag header
        $response->header('ETag', $etag);
        
        // Check if client sent If-None-Match header
        $if_none_match = $request->get_header('If-None-Match');
        
        if ($if_none_match && $if_none_match === $etag) {
            // Data hasn't changed, return 304 Not Modified
            $response->set_status(304);
            $response->set_data(null);
        }
        
        return $response;
    }
    
    /**
     * Add Last-Modified header to response for conditional requests
     * 
     * Adds Last-Modified header and checks If-Modified-Since for 304 responses.
     * 
     * @param WP_REST_Response $response Response object
     * @param int $last_modified Unix timestamp of last modification
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Modified response with Last-Modified header
     */
    protected function add_last_modified_header($response, $last_modified, $request) {
        // Format timestamp as HTTP date
        $last_modified_date = gmdate('D, d M Y H:i:s', $last_modified) . ' GMT';
        
        // Add Last-Modified header
        $response->header('Last-Modified', $last_modified_date);
        
        // Check if client sent If-Modified-Since header
        $if_modified_since = $request->get_header('If-Modified-Since');
        
        if ($if_modified_since) {
            // Parse the If-Modified-Since header
            $if_modified_since_timestamp = strtotime($if_modified_since);
            
            // Compare timestamps (ignore seconds for better compatibility)
            if ($if_modified_since_timestamp >= $last_modified) {
                // Data hasn't been modified, return 304 Not Modified
                $response->set_status(304);
                $response->set_data(null);
            }
        }
        
        return $response;
    }
    
    /**
     * Add Cache-Control headers to response
     * 
     * Sets appropriate cache headers based on the endpoint and data type.
     * 
     * @param WP_REST_Response $response Response object
     * @param int $max_age Maximum age in seconds (default: 300 = 5 minutes)
     * @param bool $public Whether cache is public or private (default: false)
     * @return WP_REST_Response Modified response with cache headers
     */
    protected function add_cache_headers($response, $max_age = 300, $public = false) {
        $cache_control = $public ? 'public' : 'private';
        $cache_control .= ', max-age=' . $max_age;
        
        // Add must-revalidate for data integrity
        $cache_control .= ', must-revalidate';
        
        $response->header('Cache-Control', $cache_control);
        $response->header('Expires', gmdate('D, d M Y H:i:s', time() + $max_age) . ' GMT');
        
        return $response;
    }
    
    /**
     * Add no-cache headers to response
     * 
     * Prevents caching for sensitive or frequently changing data.
     * 
     * @param WP_REST_Response $response Response object
     * @return WP_REST_Response Modified response with no-cache headers
     */
    protected function add_no_cache_headers($response) {
        $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->header('Pragma', 'no-cache');
        $response->header('Expires', '0');
        
        return $response;
    }
    
    /**
     * Create optimized response with caching and ETag support
     * 
     * Combines success response with cache headers and ETag for optimal performance.
     * 
     * @param mixed $data Response data
     * @param WP_REST_Request $request Request object
     * @param array $options Options for response optimization
     *   - 'message' (string): Success message
     *   - 'status' (int): HTTP status code (default: 200)
     *   - 'cache_max_age' (int): Cache max age in seconds (default: 300)
     *   - 'cache_public' (bool): Whether cache is public (default: false)
     *   - 'use_etag' (bool): Whether to use ETag (default: true)
     *   - 'use_last_modified' (bool): Whether to use Last-Modified (default: false)
     *   - 'last_modified' (int): Unix timestamp of last modification
     *   - 'no_cache' (bool): Whether to prevent caching (default: false)
     * @return WP_REST_Response Optimized response
     */
    protected function optimized_response($data, $request, $options = []) {
        // Default options
        $defaults = [
            'message' => '',
            'status' => 200,
            'cache_max_age' => 300,
            'cache_public' => false,
            'use_etag' => true,
            'use_last_modified' => false,
            'last_modified' => null,
            'no_cache' => false
        ];
        
        $options = wp_parse_args($options, $defaults);
        
        // Create success response
        $response = $this->success_response(
            $data,
            $options['message'],
            $options['status'],
            $request
        );
        
        // Add cache headers
        if ($options['no_cache']) {
            $response = $this->add_no_cache_headers($response);
        } else {
            $response = $this->add_cache_headers(
                $response,
                $options['cache_max_age'],
                $options['cache_public']
            );
        }
        
        // Add ETag if enabled
        if ($options['use_etag']) {
            $response = $this->add_etag_header($response, $data, $request);
        }
        
        // Add Last-Modified if enabled and timestamp provided
        if ($options['use_last_modified'] && $options['last_modified'] !== null) {
            $response = $this->add_last_modified_header($response, $options['last_modified'], $request);
        }
        
        // Add deprecation warnings if applicable
        $response = $this->add_deprecation_warnings($response, $request);
        
        // Add version headers
        $response = $this->add_version_headers($response, $request);
        
        return $response;
    }
    
    /**
     * Add deprecation warnings to response
     * 
     * Checks if the endpoint is deprecated and adds appropriate warning headers.
     * 
     * @param WP_REST_Response $response Response object
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Modified response with deprecation warnings
     */
    protected function add_deprecation_warnings($response, $request) {
        if (!$this->deprecation_service) {
            return $response;
        }
        
        // Get endpoint and version
        $endpoint = $request->get_route();
        $version = $this->get_api_version($request);
        
        // Remove namespace prefix from endpoint
        $endpoint = str_replace('/' . $this->namespace, '', $endpoint);
        
        // Check if endpoint is deprecated
        if ($this->deprecation_service->is_deprecated($endpoint, $version)) {
            // Add deprecation headers
            $response = $this->deprecation_service->add_deprecation_headers($response, $endpoint, $version);
            
            // Log deprecation usage
            $this->deprecation_service->log_usage($endpoint, $version, get_current_user_id());
        }
        
        return $response;
    }
    
    /**
     * Add version headers to response
     * 
     * Adds API version information to response headers.
     * 
     * @param WP_REST_Response $response Response object
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Modified response with version headers
     */
    protected function add_version_headers($response, $request) {
        if (!$this->version_manager) {
            return $response;
        }
        
        // Get API version from request
        $version = $this->get_api_version($request);
        
        // Add version headers
        $response = $this->version_manager->add_version_headers($response, $version);
        
        return $response;
    }
    
    /**
     * Get API version from request
     * 
     * @param WP_REST_Request $request Request object
     * @return string API version (e.g., 'v1', 'v2')
     */
    protected function get_api_version($request) {
        if ($this->version_manager) {
            return $this->version_manager->get_version_from_request($request);
        }
        
        // Fallback: extract from namespace
        if (preg_match('/v(\d+)$/', $this->namespace, $matches)) {
            return 'v' . $matches[1];
        }
        
        return 'v1';
    }
    
    /**
     * Trigger webhook for an event
     * 
     * @param string $event Event name
     * @param array $payload Event payload data
     * @return int Number of webhooks triggered
     */
    protected function trigger_webhook($event, $payload) {
        // Check if webhook service is available
        if (!class_exists('MAS_Webhook_Service')) {
            return 0;
        }
        
        try {
            $webhook_service = new MAS_Webhook_Service();
            return $webhook_service->trigger_webhook($event, $payload);
        } catch (Exception $e) {
            // Log error but don't fail the main operation
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    'MAS Webhook Error: Failed to trigger webhook for event "%s": %s',
                    $event,
                    $e->getMessage()
                ));
            }
            return 0;
        }
    }
}
