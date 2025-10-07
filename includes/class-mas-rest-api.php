<?php
/**
 * REST API Bootstrap Class for Modern Admin Styler V2
 * 
 * Initializes and registers all REST API controllers and manages
 * the REST API namespace for the plugin.
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
 * REST API Bootstrap Class
 * 
 * Handles initialization of the REST API infrastructure including
 * controller registration and dependency injection.
 */
class MAS_REST_API {
    
    /**
     * Singleton instance
     * 
     * @var MAS_REST_API
     */
    private static $instance = null;
    
    /**
     * REST API namespace (default/primary)
     * 
     * @var string
     */
    private $namespace = 'mas-v2/v1';
    
    /**
     * Version manager instance
     * 
     * @var MAS_Version_Manager
     */
    private $version_manager = null;
    
    /**
     * Registered controllers (organized by version)
     * 
     * @var array
     */
    private $controllers = [];
    
    /**
     * Services container for dependency injection
     * 
     * @var array
     */
    private $services = [];
    
    /**
     * Initialization errors
     * 
     * @var array
     */
    private $initialization_errors = [];
    
    /**
     * Initialization status
     * 
     * @var bool
     */
    private $initialized = false;
    
    /**
     * Get singleton instance
     * 
     * @return MAS_REST_API
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor to enforce singleton pattern
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize REST API
     * 
     * @return void
     */
    private function init() {
        // Register controllers (dependencies will be loaded when rest_api_init fires)
        add_action('rest_api_init', [$this, 'register_controllers']);
        
        // Add CORS headers for admin-ajax compatibility
        add_action('rest_api_init', [$this, 'add_cors_headers']);
        
        // Add analytics tracking middleware
        add_filter('rest_pre_dispatch', [$this, 'track_request_start'], 10, 3);
        add_filter('rest_post_dispatch', [$this, 'track_request_end'], 10, 3);
        
        // Register admin notice handler for initialization failures
        add_action('admin_notices', [$this, 'display_initialization_errors']);
        
        // Log initialization in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MAS REST API: Initialized successfully');
        }
    }
    
    /**
     * Load required dependencies
     * 
     * @return void
     */
    private function load_dependencies() {
        // Load base controller with error handling
        $base_controller = MAS_V2_PLUGIN_DIR . 'includes/api/class-mas-rest-controller.php';
        $this->safe_require($base_controller, 'Base REST Controller');
        
        // Load services
        $services_dir = MAS_V2_PLUGIN_DIR . 'includes/services/';
        
        // Version manager (Phase 2 - Task 9)
        $this->safe_require($services_dir . 'class-mas-version-manager.php', 'Version Manager Service');
        
        // Deprecation service (Phase 2 - Task 9)
        $this->safe_require($services_dir . 'class-mas-deprecation-service.php', 'Deprecation Service');
        
        // Validation service
        $this->safe_require($services_dir . 'class-mas-validation-service.php', 'Validation Service');
        
        // Rate limiter service
        $this->safe_require($services_dir . 'class-mas-rate-limiter-service.php', 'Rate Limiter Service');
        
        // Security logger service
        $this->safe_require($services_dir . 'class-mas-security-logger-service.php', 'Security Logger Service');
        
        // Diagnostics service
        $this->safe_require($services_dir . 'class-mas-diagnostics-service.php', 'Diagnostics Service');
        
        // Database optimizer
        $this->safe_require($services_dir . 'class-mas-database-optimizer.php', 'Database Optimizer');
        
        // System health service (Phase 2 - Task 3)
        $this->safe_require($services_dir . 'class-mas-system-health-service.php', 'System Health Service');
        
        // Transaction service (Phase 2 - Task 6)
        $this->safe_require($services_dir . 'class-mas-transaction-service.php', 'Transaction Service');
        
        // Webhook service (Phase 2 - Task 7)
        $this->safe_require($services_dir . 'class-mas-webhook-service.php', 'Webhook Service');
        
        // Analytics service (Phase 2 - Task 8)
        $this->safe_require($services_dir . 'class-mas-analytics-service.php', 'Analytics Service');
        
        // Load controllers
        $controllers_dir = MAS_V2_PLUGIN_DIR . 'includes/api/';
        
        // Settings controller (Phase 2)
        $this->safe_require($controllers_dir . 'class-mas-settings-controller.php', 'Settings Controller');
        
        // Theme controller (Phase 2)
        $this->safe_require($controllers_dir . 'class-mas-themes-controller.php', 'Themes Controller');
        
        // Backup controller (Phase 3)
        $this->safe_require($controllers_dir . 'class-mas-backups-controller.php', 'Backups Controller');
        
        // Import/Export controller (Phase 3)
        $this->safe_require($controllers_dir . 'class-mas-import-export-controller.php', 'Import/Export Controller');
        
        // Preview controller (Phase 3)
        $this->safe_require($controllers_dir . 'class-mas-preview-controller.php', 'Preview Controller');
        
        // Diagnostics controller (Phase 3)
        $this->safe_require($controllers_dir . 'class-mas-diagnostics-controller.php', 'Diagnostics Controller');
        
        // System controller (Phase 2 - Task 3)
        $this->safe_require($controllers_dir . 'class-mas-system-controller.php', 'System Controller');
        
        // Security controller (Phase 2 - Task 5)
        $this->safe_require($controllers_dir . 'class-mas-security-controller.php', 'Security Controller');
        
        // Batch controller (Phase 2 - Task 6)
        $this->safe_require($controllers_dir . 'class-mas-batch-controller.php', 'Batch Controller');
        
        // Webhooks controller (Phase 2 - Task 7)
        $this->safe_require($controllers_dir . 'class-mas-webhooks-controller.php', 'Webhooks Controller');
        
        // Analytics controller (Phase 2 - Task 8)
        $this->safe_require($controllers_dir . 'class-mas-analytics-controller.php', 'Analytics Controller');
    }
    
    /**
     * Safely require a file with error handling
     * 
     * @param string $file_path Full path to the file
     * @param string $description Human-readable description for logging
     * @return bool True if file was loaded successfully, false otherwise
     */
    private function safe_require($file_path, $description = '') {
        // Check if file exists
        if (!file_exists($file_path)) {
            // File doesn't exist - this is expected for optional components
            return false;
        }
        
        try {
            // Attempt to require the file
            require_once $file_path;
            return true;
        } catch (Exception $e) {
            // Log the error with full context
            $this->log_error(sprintf(
                'Failed to load %s: %s',
                $description ?: basename($file_path),
                $e->getMessage()
            ), [
                'file' => $file_path,
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        } catch (Error $e) {
            // Catch PHP 7+ errors (like parse errors, class not found, etc.)
            $this->log_error(sprintf(
                'Fatal error loading %s: %s',
                $description ?: basename($file_path),
                $e->getMessage()
            ), [
                'file' => $file_path,
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    
    /**
     * Log error with context information
     * 
     * @param string $message Error message
     * @param array $context Additional context information
     * @return void
     */
    private function log_error($message, $context = []) {
        // Store error for admin notice display
        $this->initialization_errors[] = [
            'message' => $message,
            'context' => $context,
            'timestamp' => current_time('mysql')
        ];
        
        // Only log when WP_DEBUG is enabled
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        // Build context string
        $context_parts = [
            'WordPress Version: ' . get_bloginfo('version'),
            'PHP Version: ' . PHP_VERSION
        ];
        
        if (!empty($context['file'])) {
            $context_parts[] = 'File: ' . $context['file'];
        }
        
        if (!empty($context['line'])) {
            $context_parts[] = 'Line: ' . $context['line'];
        }
        
        // Log the error
        error_log(sprintf(
            'MAS REST API Error: %s | Context: %s',
            $message,
            implode(', ', $context_parts)
        ));
        
        // Log trace if available (only in debug mode)
        if (!empty($context['trace']) && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('Stack trace: ' . $context['trace']);
        }
    }
    
    /**
     * Initialize services for dependency injection
     * 
     * @return void
     */
    private function init_services() {
        // Version manager (Phase 2 - Task 9)
        if (class_exists('MAS_Version_Manager')) {
            $this->version_manager = new MAS_Version_Manager();
            $this->services['version_manager'] = $this->version_manager;
        }
        
        // Deprecation service (Phase 2 - Task 9)
        if (class_exists('MAS_Deprecation_Service')) {
            $this->services['deprecation'] = new MAS_Deprecation_Service();
        }
        
        // Validation service
        if (class_exists('MAS_Validation_Service')) {
            $this->services['validation'] = new MAS_Validation_Service();
        }
        
        // Additional services will be added in future phases
        // Settings service (Phase 2)
        // Theme service (Phase 2)
        // Backup service (Phase 3)
        // CSS Generator service (Phase 3)
        // Diagnostics service (Phase 3)
    }
    
    /**
     * Display initialization errors as admin notices
     * 
     * @return void
     */
    public function display_initialization_errors() {
        // Only show to administrators
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // No errors to display
        if (empty($this->initialization_errors)) {
            return;
        }
        
        // Display error notice
        echo '<div class="notice notice-error is-dismissible">';
        echo '<p><strong>' . esc_html__('Modern Admin Styler V2 - REST API Initialization Error', 'modern-admin-styler-v2') . '</strong></p>';
        echo '<p>' . esc_html__('The REST API failed to initialize properly. Some features may not work correctly.', 'modern-admin-styler-v2') . '</p>';
        
        // Show error details in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            echo '<details style="margin-top: 10px;">';
            echo '<summary style="cursor: pointer; font-weight: bold;">' . esc_html__('Technical Details (Debug Mode)', 'modern-admin-styler-v2') . '</summary>';
            echo '<ul style="margin: 10px 0; padding-left: 20px;">';
            
            foreach ($this->initialization_errors as $error) {
                echo '<li>';
                echo '<strong>' . esc_html($error['message']) . '</strong>';
                
                if (!empty($error['context'])) {
                    echo '<ul style="margin: 5px 0; padding-left: 20px; font-size: 0.9em;">';
                    
                    if (!empty($error['context']['file'])) {
                        echo '<li>File: <code>' . esc_html($error['context']['file']) . '</code></li>';
                    }
                    
                    if (!empty($error['context']['line'])) {
                        echo '<li>Line: ' . esc_html($error['context']['line']) . '</li>';
                    }
                    
                    echo '</ul>';
                }
                
                echo '</li>';
            }
            
            echo '</ul>';
            echo '</details>';
        }
        
        // Troubleshooting information
        echo '<div style="margin-top: 10px; padding: 10px; background: #f0f0f1; border-left: 4px solid #d63638;">';
        echo '<p style="margin: 0 0 10px 0;"><strong>' . esc_html__('Troubleshooting Steps:', 'modern-admin-styler-v2') . '</strong></p>';
        echo '<ol style="margin: 0; padding-left: 20px;">';
        echo '<li>' . esc_html__('Ensure you are running WordPress 5.8 or higher', 'modern-admin-styler-v2') . '</li>';
        echo '<li>' . esc_html__('Check that the WordPress REST API is not disabled by another plugin', 'modern-admin-styler-v2') . '</li>';
        echo '<li>' . esc_html__('Verify that all plugin files are properly uploaded', 'modern-admin-styler-v2') . '</li>';
        echo '<li>' . esc_html__('Try deactivating and reactivating the plugin', 'modern-admin-styler-v2') . '</li>';
        echo '<li>' . esc_html__('Check your server error logs for more details', 'modern-admin-styler-v2') . '</li>';
        echo '</ol>';
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Register all REST API controllers
     * 
     * @return void
     */
    public function register_controllers() {
        // Safety check: Verify WP_REST_Controller is available
        if (!class_exists('WP_REST_Controller')) {
            $this->log_error('WP_REST_Controller class not available. REST API initialization skipped.', [
                'wp_version' => get_bloginfo('version'),
                'php_version' => PHP_VERSION
            ]);
            return;
        }
        
        // Load required files now that WordPress REST API is ready
        $this->load_dependencies();
        
        // Initialize services
        $this->init_services();
        
        // Mark as successfully initialized
        $this->initialized = true;
        
        // Log successful initialization
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MAS REST API: Dependencies loaded successfully, WP_REST_Controller is available');
        }
        
        // Register controllers as they become available
        
        // Settings controller (Phase 2)
        if (class_exists('MAS_Settings_Controller')) {
            $this->register_controller('settings', 'MAS_Settings_Controller');
        }
        
        // Theme controller (Phase 2)
        if (class_exists('MAS_Themes_Controller')) {
            $this->register_controller('themes', 'MAS_Themes_Controller');
        }
        
        // Backup controller (Phase 3)
        if (class_exists('MAS_Backups_Controller')) {
            $this->register_controller('backups', 'MAS_Backups_Controller');
        }
        
        // Import/Export controller (Phase 3)
        if (class_exists('MAS_Import_Export_Controller')) {
            $this->register_controller('import_export', 'MAS_Import_Export_Controller');
        }
        
        // Preview controller (Phase 3)
        if (class_exists('MAS_Preview_Controller')) {
            $this->register_controller('preview', 'MAS_Preview_Controller');
        }
        
        // Diagnostics controller (Phase 3)
        if (class_exists('MAS_Diagnostics_Controller')) {
            $this->register_controller('diagnostics', 'MAS_Diagnostics_Controller');
        }
        
        // System controller (Phase 2 - Task 3)
        if (class_exists('MAS_System_Controller')) {
            $this->register_controller('system', 'MAS_System_Controller');
        }
        
        // Security controller (Phase 2 - Task 5)
        if (class_exists('MAS_Security_Controller')) {
            $this->register_controller('security', 'MAS_Security_Controller');
        }
        
        // Batch controller (Phase 2 - Task 6)
        if (class_exists('MAS_Batch_Controller')) {
            $this->register_controller('batch', 'MAS_Batch_Controller');
        }
        
        // Webhooks controller (Phase 2 - Task 7)
        if (class_exists('MAS_Webhooks_Controller')) {
            $this->register_controller('webhooks', 'MAS_Webhooks_Controller');
        }
        
        // Analytics controller (Phase 2 - Task 8)
        if (class_exists('MAS_Analytics_Controller')) {
            $this->register_controller('analytics', 'MAS_Analytics_Controller');
        }
        
        // Log registered controllers in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'MAS REST API: Registered %d controllers',
                count($this->controllers)
            ));
        }
    }
    
    /**
     * Register a single controller with dependency injection
     * 
     * @param string $name Controller name
     * @param string $class_name Controller class name
     * @return void
     */
    private function register_controller($name, $class_name) {
        if (!class_exists($class_name)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    'MAS REST API: Controller class %s not found',
                    $class_name
                ));
            }
            return;
        }
        
        // Instantiate controller (controllers handle their own dependencies)
        $controller = new $class_name();
        
        // Store controller reference
        $this->controllers[$name] = $controller;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'MAS REST API: Registered controller %s (%s)',
                $name,
                $class_name
            ));
        }
    }
    
    /**
     * Add CORS headers for admin-ajax compatibility
     * 
     * @return void
     */
    public function add_cors_headers() {
        // Allow requests from the same origin (admin-ajax compatibility)
        header('Access-Control-Allow-Origin: ' . get_site_url());
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Content-Type, X-WP-Nonce');
    }
    
    /**
     * Get a registered service
     * 
     * @param string $name Service name
     * @return mixed|null Service instance or null if not found
     */
    public function get_service($name) {
        return isset($this->services[$name]) ? $this->services[$name] : null;
    }
    
    /**
     * Get a registered controller
     * 
     * @param string $name Controller name
     * @return mixed|null Controller instance or null if not found
     */
    public function get_controller($name) {
        return isset($this->controllers[$name]) ? $this->controllers[$name] : null;
    }
    
    /**
     * Get the REST API namespace
     * 
     * @param string $version Optional version identifier
     * @return string
     */
    public function get_namespace($version = null) {
        if ($version && $this->version_manager) {
            $namespace = $this->version_manager->get_namespace($version);
            if ($namespace) {
                return $namespace;
            }
        }
        return $this->namespace;
    }
    
    /**
     * Get version manager instance
     * 
     * @return MAS_Version_Manager|null
     */
    public function get_version_manager() {
        return $this->version_manager;
    }
    
    /**
     * Check if REST API is available
     * 
     * @return bool
     */
    public static function is_available() {
        return function_exists('rest_url') && get_option('permalink_structure');
    }
    
    /**
     * Get REST API base URL
     * 
     * @return string
     */
    public function get_base_url() {
        return rest_url($this->namespace);
    }
    
    /**
     * Check if REST API was initialized successfully
     * 
     * @return bool
     */
    public function is_initialized() {
        return $this->initialized;
    }
    
    /**
     * Get initialization errors
     * 
     * @return array
     */
    public function get_initialization_errors() {
        return $this->initialization_errors;
    }
    
    /**
     * Check if there are any initialization errors
     * 
     * @return bool
     */
    public function has_errors() {
        return !empty($this->initialization_errors);
    }
    
    /**
     * Track request start time
     * 
     * @param mixed $result Response to replace the requested version with
     * @param WP_REST_Server $server Server instance
     * @param WP_REST_Request $request Request used to generate the response
     * @return mixed Unmodified result
     */
    public function track_request_start($result, $server, $request) {
        // Only track our namespace
        $route = $request->get_route();
        if (strpos($route, '/' . $this->namespace . '/') !== 0) {
            return $result;
        }
        
        // Store start time in request attribute
        $request->set_param('_mas_start_time', microtime(true));
        
        return $result;
    }
    
    /**
     * Track request end and log analytics
     * 
     * @param WP_HTTP_Response $result Result to send to the client
     * @param WP_REST_Server $server Server instance
     * @param WP_REST_Request $request Request used to generate the response
     * @return WP_HTTP_Response Unmodified result
     */
    public function track_request_end($result, $server, $request) {
        // Only track our namespace
        $route = $request->get_route();
        if (strpos($route, '/' . $this->namespace . '/') !== 0) {
            return $result;
        }
        
        // Get start time
        $start_time = $request->get_param('_mas_start_time');
        if (!$start_time) {
            return $result;
        }
        
        // Calculate response time in milliseconds
        $response_time = round((microtime(true) - $start_time) * 1000);
        
        // Get endpoint (remove namespace prefix)
        $endpoint = str_replace('/' . $this->namespace, '', $route);
        
        // Get method
        $method = $request->get_method();
        
        // Get status code
        $status_code = $result->get_status();
        
        // Track the API call
        if (class_exists('MAS_Analytics_Service')) {
            try {
                $analytics_service = new MAS_Analytics_Service();
                $analytics_service->track_api_call($endpoint, $method, $response_time, $status_code);
            } catch (Exception $e) {
                // Silently fail - don't break the request
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MAS Analytics: Failed to track API call - ' . $e->getMessage());
                }
            }
        }
        
        return $result;
    }
}
