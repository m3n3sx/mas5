<?php
/**
 * Diagnostics Service for Modern Admin Styler V2
 * 
 * Provides system health checks, diagnostics information, and optimization
 * recommendations for troubleshooting and performance monitoring.
 *
 * @package ModernAdminStylerV2
 * @subpackage Services
 * @since 2.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Diagnostics Service Class
 * 
 * Handles system diagnostics, health checks, and performance monitoring.
 */
class MAS_Diagnostics_Service {
    
    /**
     * Settings service instance
     * 
     * @var MAS_Settings_Service
     */
    private $settings_service;
    
    /**
     * Constructor
     * 
     * @param MAS_Settings_Service $settings_service Settings service instance
     */
    public function __construct($settings_service = null) {
        $this->settings_service = $settings_service ?: new MAS_Settings_Service();
    }
    
    /**
     * Get comprehensive system diagnostics
     * 
     * @return array Diagnostics data
     */
    public function get_diagnostics() {
        $start_time = microtime(true);
        
        $diagnostics = [
            'system' => $this->get_system_info(),
            'plugin' => $this->get_plugin_info(),
            'settings' => $this->check_settings_integrity(),
            'filesystem' => $this->check_filesystem(),
            'conflicts' => $this->detect_conflicts(),
            'performance' => $this->get_performance_metrics($start_time),
            'recommendations' => []
        ];
        
        // Generate recommendations based on diagnostics
        $diagnostics['recommendations'] = $this->generate_recommendations($diagnostics);
        
        return $diagnostics;
    }
    
    /**
     * Get system information
     * 
     * @return array System information
     */
    private function get_system_info() {
        global $wp_version;
        
        return [
            'php_version' => PHP_VERSION,
            'php_version_check' => version_compare(PHP_VERSION, '7.4', '>='),
            'wordpress_version' => $wp_version,
            'wordpress_version_check' => version_compare($wp_version, '5.8', '>='),
            'mysql_version' => $this->get_mysql_version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'php_memory_limit' => ini_get('memory_limit'),
            'php_max_execution_time' => ini_get('max_execution_time'),
            'php_post_max_size' => ini_get('post_max_size'),
            'php_upload_max_filesize' => ini_get('upload_max_filesize'),
            'wordpress_memory_limit' => WP_MEMORY_LIMIT,
            'wordpress_max_memory_limit' => WP_MAX_MEMORY_LIMIT,
            'multisite' => is_multisite(),
            'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
            'rest_api_enabled' => $this->check_rest_api_enabled()
        ];
    }
    
    /**
     * Get MySQL version
     * 
     * @return string MySQL version
     */
    private function get_mysql_version() {
        global $wpdb;
        
        if (method_exists($wpdb, 'db_version')) {
            return $wpdb->db_version();
        }
        
        return 'Unknown';
    }
    
    /**
     * Check if REST API is enabled
     * 
     * @return bool True if REST API is enabled
     */
    private function check_rest_api_enabled() {
        return function_exists('rest_url') && rest_url();
    }
    
    /**
     * Get plugin information
     * 
     * @return array Plugin information
     */
    private function get_plugin_info() {
        $plugin_file = WP_PLUGIN_DIR . '/modern-admin-styler-v2/modern-admin-styler-v2.php';
        $plugin_data = [];
        
        if (file_exists($plugin_file) && function_exists('get_plugin_data')) {
            $plugin_data = get_plugin_data($plugin_file, false, false);
        }
        
        return [
            'version' => $plugin_data['Version'] ?? '2.2.0',
            'name' => $plugin_data['Name'] ?? 'Modern Admin Styler V2',
            'author' => $plugin_data['Author'] ?? 'Unknown',
            'active' => is_plugin_active('modern-admin-styler-v2/modern-admin-styler-v2.php'),
            'rest_api_namespace' => 'mas-v2/v1',
            'rest_api_available' => $this->check_plugin_rest_api()
        ];
    }
    
    /**
     * Check if plugin REST API is available
     * 
     * @return bool True if plugin REST API is available
     */
    private function check_plugin_rest_api() {
        $routes = rest_get_server()->get_routes();
        return isset($routes['/mas-v2/v1/settings']);
    }
    
    /**
     * Check settings integrity
     * 
     * @return array Settings integrity check results
     */
    public function check_settings_integrity() {
        $settings = $this->settings_service->get_settings();
        $defaults = $this->settings_service->get_default_settings();
        
        $issues = [];
        $missing_keys = [];
        $invalid_values = [];
        
        // Check for missing keys
        foreach ($defaults as $key => $default_value) {
            if (!isset($settings[$key])) {
                $missing_keys[] = $key;
            }
        }
        
        // Validate color values
        $color_fields = [
            'menu_background', 'menu_text_color', 'menu_hover_background',
            'menu_hover_text_color', 'menu_active_background', 'menu_active_text_color',
            'admin_bar_background', 'admin_bar_text_color'
        ];
        
        foreach ($color_fields as $field) {
            if (isset($settings[$field]) && !$this->validate_color($settings[$field])) {
                $invalid_values[$field] = 'Invalid color format';
            }
        }
        
        // Check CSS unit values
        $unit_fields = ['menu_width', 'menu_item_height', 'menu_border_radius'];
        
        foreach ($unit_fields as $field) {
            if (isset($settings[$field]) && !$this->validate_css_unit($settings[$field])) {
                $invalid_values[$field] = 'Invalid CSS unit';
            }
        }
        
        return [
            'valid' => empty($missing_keys) && empty($invalid_values),
            'missing_keys' => $missing_keys,
            'invalid_values' => $invalid_values,
            'total_settings' => count($settings),
            'expected_settings' => count($defaults)
        ];
    }
    
    /**
     * Validate color value
     * 
     * @param string $color Color value to validate
     * @return bool True if valid
     */
    private function validate_color($color) {
        // Check hex color format
        if (preg_match('/^#[a-f0-9]{6}$/i', $color)) {
            return true;
        }
        
        // Check rgb/rgba format
        if (preg_match('/^rgba?\([0-9,\s.]+\)$/i', $color)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Validate CSS unit value
     * 
     * @param string $value CSS unit value to validate
     * @return bool True if valid
     */
    private function validate_css_unit($value) {
        return preg_match('/^[0-9]+(\.[0-9]+)?(px|em|rem|%|vh|vw)$/', $value);
    }
    
    /**
     * Check filesystem permissions and structure
     * 
     * @return array Filesystem check results
     */
    public function check_filesystem() {
        $upload_dir = wp_upload_dir();
        $plugin_dir = WP_PLUGIN_DIR . '/modern-admin-styler-v2';
        
        $checks = [
            'upload_dir_writable' => is_writable($upload_dir['basedir']),
            'upload_dir_path' => $upload_dir['basedir'],
            'plugin_dir_readable' => is_readable($plugin_dir),
            'plugin_dir_path' => $plugin_dir,
            'required_directories' => []
        ];
        
        // Check required directories
        $required_dirs = [
            'includes' => $plugin_dir . '/includes',
            'includes/api' => $plugin_dir . '/includes/api',
            'includes/services' => $plugin_dir . '/includes/services',
            'assets' => $plugin_dir . '/assets',
            'assets/css' => $plugin_dir . '/assets/css',
            'assets/js' => $plugin_dir . '/assets/js'
        ];
        
        foreach ($required_dirs as $name => $path) {
            $checks['required_directories'][$name] = [
                'exists' => file_exists($path) && is_dir($path),
                'readable' => is_readable($path),
                'path' => $path
            ];
        }
        
        return $checks;
    }
    
    /**
     * Detect potential plugin conflicts
     * 
     * @return array Conflict detection results
     */
    public function detect_conflicts() {
        $conflicts = [
            'potential_conflicts' => [],
            'admin_menu_plugins' => [],
            'rest_api_conflicts' => []
        ];
        
        // Get all active plugins
        $active_plugins = get_option('active_plugins', []);
        
        // Known conflicting plugins (admin menu/styling related)
        $known_conflicts = [
            'admin-menu-editor' => 'Admin Menu Editor',
            'adminimize' => 'Adminimize',
            'white-label-cms' => 'White Label CMS',
            'admin-color-schemes' => 'Admin Color Schemes',
            'custom-admin-interface' => 'Custom Admin Interface'
        ];
        
        foreach ($active_plugins as $plugin) {
            $plugin_slug = dirname($plugin);
            
            // Check for known conflicts
            foreach ($known_conflicts as $conflict_slug => $conflict_name) {
                if (strpos($plugin_slug, $conflict_slug) !== false) {
                    $conflicts['potential_conflicts'][] = [
                        'plugin' => $conflict_name,
                        'slug' => $plugin_slug,
                        'reason' => 'May modify admin menu or styling'
                    ];
                }
            }
            
            // Check for admin menu related plugins
            if (strpos($plugin_slug, 'admin') !== false || strpos($plugin_slug, 'menu') !== false) {
                $conflicts['admin_menu_plugins'][] = $plugin_slug;
            }
        }
        
        // Check for REST API namespace conflicts
        $routes = rest_get_server()->get_routes();
        foreach ($routes as $route => $handlers) {
            if (strpos($route, '/mas-v2/') !== false && strpos($route, '/mas-v2/v1/') === false) {
                $conflicts['rest_api_conflicts'][] = $route;
            }
        }
        
        return $conflicts;
    }
    
    /**
     * Get performance metrics
     * 
     * @param float $start_time Start time for execution measurement
     * @return array Performance metrics
     */
    public function get_performance_metrics($start_time) {
        $execution_time = microtime(true) - $start_time;
        
        return [
            'memory_usage' => [
                'current' => size_format(memory_get_usage(true)),
                'current_bytes' => memory_get_usage(true),
                'peak' => size_format(memory_get_peak_usage(true)),
                'peak_bytes' => memory_get_peak_usage(true),
                'limit' => ini_get('memory_limit')
            ],
            'execution_time' => [
                'diagnostics' => round($execution_time * 1000, 2) . 'ms',
                'diagnostics_seconds' => round($execution_time, 4)
            ],
            'database' => $this->get_database_metrics(),
            'cache' => $this->get_cache_metrics()
        ];
    }
    
    /**
     * Get database performance metrics
     * 
     * @return array Database metrics
     */
    private function get_database_metrics() {
        global $wpdb;
        
        return [
            'queries' => $wpdb->num_queries,
            'query_time' => isset($wpdb->query_time) ? round($wpdb->query_time, 4) . 's' : 'N/A',
            'prefix' => $wpdb->prefix
        ];
    }
    
    /**
     * Get cache metrics
     * 
     * @return array Cache metrics
     */
    private function get_cache_metrics() {
        $cache_enabled = wp_using_ext_object_cache();
        
        return [
            'object_cache_enabled' => $cache_enabled,
            'cache_type' => $cache_enabled ? 'External' : 'Database',
            'transients_count' => $this->count_transients()
        ];
    }
    
    /**
     * Count plugin transients
     * 
     * @return int Number of transients
     */
    private function count_transients() {
        global $wpdb;
        
        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_mas_v2_%' 
             OR option_name LIKE '_transient_timeout_mas_v2_%'"
        );
        
        return (int) $count;
    }
    
    /**
     * Generate optimization recommendations
     * 
     * @param array $diagnostics Diagnostics data
     * @return array Recommendations
     */
    public function generate_recommendations($diagnostics) {
        $recommendations = [];
        
        // PHP version check
        if (!$diagnostics['system']['php_version_check']) {
            $recommendations[] = [
                'severity' => 'high',
                'category' => 'system',
                'title' => 'PHP Version Outdated',
                'description' => 'Your PHP version is below 7.4. Please upgrade to PHP 7.4 or higher for better performance and security.',
                'action' => 'Contact your hosting provider to upgrade PHP'
            ];
        }
        
        // WordPress version check
        if (!$diagnostics['system']['wordpress_version_check']) {
            $recommendations[] = [
                'severity' => 'medium',
                'category' => 'system',
                'title' => 'WordPress Version Outdated',
                'description' => 'Your WordPress version is below 5.8. Consider upgrading for better REST API support.',
                'action' => 'Update WordPress to the latest version'
            ];
        }
        
        // Memory limit check
        $memory_limit = ini_get('memory_limit');
        $memory_limit_bytes = $this->parse_memory_limit($memory_limit);
        if ($memory_limit_bytes < 128 * 1024 * 1024) {
            $recommendations[] = [
                'severity' => 'medium',
                'category' => 'performance',
                'title' => 'Low PHP Memory Limit',
                'description' => 'PHP memory limit is below 128MB. This may cause issues with large settings or backups.',
                'action' => 'Increase PHP memory_limit to at least 128MB'
            ];
        }
        
        // Settings integrity check
        if (!$diagnostics['settings']['valid']) {
            $recommendations[] = [
                'severity' => 'high',
                'category' => 'settings',
                'title' => 'Settings Integrity Issues',
                'description' => 'Some settings are missing or invalid. This may cause unexpected behavior.',
                'action' => 'Reset settings to defaults or fix invalid values'
            ];
        }
        
        // Filesystem check
        if (!$diagnostics['filesystem']['upload_dir_writable']) {
            $recommendations[] = [
                'severity' => 'high',
                'category' => 'filesystem',
                'title' => 'Upload Directory Not Writable',
                'description' => 'The WordPress uploads directory is not writable. Backups and exports may fail.',
                'action' => 'Set proper permissions on the uploads directory'
            ];
        }
        
        // Conflict detection
        if (!empty($diagnostics['conflicts']['potential_conflicts'])) {
            $recommendations[] = [
                'severity' => 'medium',
                'category' => 'conflicts',
                'title' => 'Potential Plugin Conflicts Detected',
                'description' => 'Other plugins that modify the admin menu or styling were detected.',
                'action' => 'Review and test for conflicts with: ' . implode(', ', array_column($diagnostics['conflicts']['potential_conflicts'], 'plugin'))
            ];
        }
        
        // Performance recommendations
        $peak_memory = $diagnostics['performance']['memory_usage']['peak_bytes'];
        if ($peak_memory > 64 * 1024 * 1024) {
            $recommendations[] = [
                'severity' => 'low',
                'category' => 'performance',
                'title' => 'High Memory Usage',
                'description' => 'Peak memory usage is above 64MB. Consider enabling performance mode.',
                'action' => 'Enable performance mode in plugin settings'
            ];
        }
        
        // Cache recommendations
        if (!$diagnostics['performance']['cache']['object_cache_enabled']) {
            $recommendations[] = [
                'severity' => 'low',
                'category' => 'performance',
                'title' => 'Object Cache Not Enabled',
                'description' => 'External object cache is not enabled. Performance could be improved.',
                'action' => 'Consider installing Redis or Memcached for better performance'
            ];
        }
        
        // REST API check
        if (!$diagnostics['system']['rest_api_enabled']) {
            $recommendations[] = [
                'severity' => 'high',
                'category' => 'system',
                'title' => 'REST API Disabled',
                'description' => 'WordPress REST API is disabled. Plugin functionality will be limited.',
                'action' => 'Enable WordPress REST API'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Parse memory limit string to bytes
     * 
     * @param string $limit Memory limit string (e.g., "128M")
     * @return int Memory limit in bytes
     */
    private function parse_memory_limit($limit) {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
}
