<?php
/**
 * System Health Service for Modern Admin Styler V2
 * 
 * Provides comprehensive system health monitoring, performance metrics,
 * conflict detection, and actionable recommendations for Phase 2.
 *
 * @package ModernAdminStylerV2
 * @subpackage Services
 * @since 2.3.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * System Health Service Class
 * 
 * Handles comprehensive system health checks, performance monitoring,
 * and conflict detection with actionable recommendations.
 */
class MAS_System_Health_Service {
    
    /**
     * Settings service instance
     * 
     * @var MAS_Settings_Service
     */
    private $settings_service;
    
    /**
     * Cache service instance
     * 
     * @var MAS_Cache_Service
     */
    private $cache_service;
    
    /**
     * Health status constants
     */
    const STATUS_HEALTHY = 'healthy';
    const STATUS_WARNING = 'warning';
    const STATUS_CRITICAL = 'critical';
    
    /**
     * Constructor
     * 
     * @param MAS_Settings_Service $settings_service Settings service instance
     * @param MAS_Cache_Service $cache_service Cache service instance
     */
    public function __construct($settings_service = null, $cache_service = null) {
        $this->settings_service = $settings_service ?: new MAS_Settings_Service();
        
        // Cache service is optional for Phase 2
        if (class_exists('MAS_Cache_Service')) {
            $this->cache_service = $cache_service ?: new MAS_Cache_Service();
        }
    }
    
    /**
     * Get overall health status
     * 
     * Performs all health checks and returns comprehensive status
     * with overall health calculation (healthy/warning/critical).
     * 
     * @return array Health status data
     */
    public function get_health_status() {
        $checks = [
            'php_version' => $this->check_php_version(),
            'wordpress_version' => $this->check_wordpress_version(),
            'settings_integrity' => $this->check_settings_integrity(),
            'file_permissions' => $this->check_file_permissions(),
            'cache_status' => $this->check_cache_status(),
            'conflicts' => $this->check_conflicts()
        ];
        
        // Calculate overall status
        $overall_status = $this->calculate_overall_status($checks);
        
        // Generate recommendations
        $recommendations = $this->generate_recommendations($checks);
        
        return [
            'status' => $overall_status,
            'timestamp' => current_time('mysql'),
            'checks' => $checks,
            'recommendations' => $recommendations,
            'summary' => $this->generate_summary($checks)
        ];
    }
    
    /**
     * Check PHP version
     * 
     * @return array PHP version check result
     */
    public function check_php_version() {
        $current_version = PHP_VERSION;
        $min_version = '7.4';
        $recommended_version = '8.0';
        
        $meets_minimum = version_compare($current_version, $min_version, '>=');
        $meets_recommended = version_compare($current_version, $recommended_version, '>=');
        
        $status = self::STATUS_HEALTHY;
        $message = 'PHP version is up to date';
        
        if (!$meets_minimum) {
            $status = self::STATUS_CRITICAL;
            $message = sprintf('PHP version %s is below minimum required version %s', $current_version, $min_version);
        } elseif (!$meets_recommended) {
            $status = self::STATUS_WARNING;
            $message = sprintf('PHP version %s is below recommended version %s', $current_version, $recommended_version);
        }
        
        return [
            'status' => $status,
            'message' => $message,
            'current_version' => $current_version,
            'min_version' => $min_version,
            'recommended_version' => $recommended_version,
            'meets_minimum' => $meets_minimum,
            'meets_recommended' => $meets_recommended
        ];
    }
    
    /**
     * Check WordPress version
     * 
     * @return array WordPress version check result
     */
    public function check_wordpress_version() {
        global $wp_version;
        
        $current_version = $wp_version;
        $min_version = '5.8';
        $recommended_version = '6.0';
        
        $meets_minimum = version_compare($current_version, $min_version, '>=');
        $meets_recommended = version_compare($current_version, $recommended_version, '>=');
        
        $status = self::STATUS_HEALTHY;
        $message = 'WordPress version is up to date';
        
        if (!$meets_minimum) {
            $status = self::STATUS_CRITICAL;
            $message = sprintf('WordPress version %s is below minimum required version %s', $current_version, $min_version);
        } elseif (!$meets_recommended) {
            $status = self::STATUS_WARNING;
            $message = sprintf('WordPress version %s is below recommended version %s', $current_version, $recommended_version);
        }
        
        return [
            'status' => $status,
            'message' => $message,
            'current_version' => $current_version,
            'min_version' => $min_version,
            'recommended_version' => $recommended_version,
            'meets_minimum' => $meets_minimum,
            'meets_recommended' => $meets_recommended
        ];
    }
    
    /**
     * Check settings integrity
     * 
     * @return array Settings integrity check result
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
        
        $is_valid = empty($missing_keys) && empty($invalid_values);
        
        $status = $is_valid ? self::STATUS_HEALTHY : self::STATUS_WARNING;
        $message = $is_valid ? 'All settings are valid' : 'Some settings have issues';
        
        if (!empty($missing_keys)) {
            $status = self::STATUS_CRITICAL;
            $message = 'Critical settings are missing';
        }
        
        return [
            'status' => $status,
            'message' => $message,
            'valid' => $is_valid,
            'missing_keys' => $missing_keys,
            'invalid_values' => $invalid_values,
            'total_settings' => count($settings),
            'expected_settings' => count($defaults)
        ];
    }
    
    /**
     * Check file permissions
     * 
     * @return array File permissions check result
     */
    public function check_file_permissions() {
        $upload_dir = wp_upload_dir();
        $plugin_dir = WP_PLUGIN_DIR . '/modern-admin-styler-v2';
        
        $issues = [];
        
        // Check upload directory
        if (!is_writable($upload_dir['basedir'])) {
            $issues[] = 'Upload directory is not writable: ' . $upload_dir['basedir'];
        }
        
        // Check plugin directory
        if (!is_readable($plugin_dir)) {
            $issues[] = 'Plugin directory is not readable: ' . $plugin_dir;
        }
        
        // Check required directories
        $required_dirs = [
            'includes' => $plugin_dir . '/includes',
            'includes/api' => $plugin_dir . '/includes/api',
            'includes/services' => $plugin_dir . '/includes/services',
            'assets' => $plugin_dir . '/assets',
            'assets/css' => $plugin_dir . '/assets/css',
            'assets/js' => $plugin_dir . '/assets/js'
        ];
        
        $missing_dirs = [];
        foreach ($required_dirs as $name => $path) {
            if (!file_exists($path) || !is_dir($path)) {
                $missing_dirs[] = $name;
                $issues[] = "Required directory missing: $name";
            } elseif (!is_readable($path)) {
                $issues[] = "Directory not readable: $name";
            }
        }
        
        $status = empty($issues) ? self::STATUS_HEALTHY : self::STATUS_CRITICAL;
        $message = empty($issues) ? 'All file permissions are correct' : 'File permission issues detected';
        
        return [
            'status' => $status,
            'message' => $message,
            'upload_dir_writable' => is_writable($upload_dir['basedir']),
            'plugin_dir_readable' => is_readable($plugin_dir),
            'missing_directories' => $missing_dirs,
            'issues' => $issues
        ];
    }
    
    /**
     * Check cache status
     * 
     * @return array Cache status check result
     */
    public function check_cache_status() {
        $object_cache_enabled = wp_using_ext_object_cache();
        $transients_count = $this->count_transients();
        
        // Get cache stats if cache service is available
        $cache_stats = null;
        if ($this->cache_service && method_exists($this->cache_service, 'get_stats')) {
            $cache_stats = $this->cache_service->get_stats();
        }
        
        $status = self::STATUS_HEALTHY;
        $message = 'Cache is functioning normally';
        
        if (!$object_cache_enabled && $transients_count > 100) {
            $status = self::STATUS_WARNING;
            $message = 'High transient count without object cache may impact performance';
        }
        
        return [
            'status' => $status,
            'message' => $message,
            'object_cache_enabled' => $object_cache_enabled,
            'cache_type' => $object_cache_enabled ? 'External Object Cache' : 'Database Transients',
            'transients_count' => $transients_count,
            'cache_stats' => $cache_stats
        ];
    }
    
    /**
     * Check for conflicts
     * 
     * @return array Conflicts check result
     */
    public function check_conflicts() {
        $plugin_conflicts = $this->detect_plugin_conflicts();
        $theme_conflicts = $this->detect_theme_conflicts();
        $js_conflicts = $this->detect_js_conflicts();
        
        $total_conflicts = count($plugin_conflicts) + count($theme_conflicts) + count($js_conflicts);
        
        $status = self::STATUS_HEALTHY;
        $message = 'No conflicts detected';
        
        if ($total_conflicts > 0) {
            $status = $total_conflicts > 2 ? self::STATUS_WARNING : self::STATUS_HEALTHY;
            $message = sprintf('%d potential conflict(s) detected', $total_conflicts);
        }
        
        return [
            'status' => $status,
            'message' => $message,
            'plugin_conflicts' => $plugin_conflicts,
            'theme_conflicts' => $theme_conflicts,
            'js_conflicts' => $js_conflicts,
            'total_conflicts' => $total_conflicts
        ];
    }
    
    /**
     * Detect plugin conflicts
     * 
     * @return array List of conflicting plugins
     */
    private function detect_plugin_conflicts() {
        $conflicts = [];
        $active_plugins = get_option('active_plugins', []);
        
        // Known conflicting plugins
        $known_conflicts = [
            'admin-menu-editor' => [
                'name' => 'Admin Menu Editor',
                'reason' => 'Modifies admin menu structure',
                'severity' => 'medium'
            ],
            'adminimize' => [
                'name' => 'Adminimize',
                'reason' => 'Customizes admin interface',
                'severity' => 'medium'
            ],
            'white-label-cms' => [
                'name' => 'White Label CMS',
                'reason' => 'Modifies admin styling',
                'severity' => 'high'
            ],
            'admin-color-schemes' => [
                'name' => 'Admin Color Schemes',
                'reason' => 'Changes admin colors',
                'severity' => 'high'
            ],
            'custom-admin-interface' => [
                'name' => 'Custom Admin Interface',
                'reason' => 'Customizes admin interface',
                'severity' => 'high'
            ]
        ];
        
        foreach ($active_plugins as $plugin) {
            $plugin_slug = dirname($plugin);
            
            foreach ($known_conflicts as $conflict_slug => $conflict_info) {
                if (strpos($plugin_slug, $conflict_slug) !== false) {
                    $conflicts[] = array_merge(['slug' => $plugin_slug], $conflict_info);
                }
            }
        }
        
        return $conflicts;
    }
    
    /**
     * Detect theme conflicts
     * 
     * @return array List of theme conflicts
     */
    private function detect_theme_conflicts() {
        $conflicts = [];
        $current_theme = wp_get_theme();
        
        // Check if theme has admin styling
        $theme_dir = get_template_directory();
        $admin_css_files = [
            $theme_dir . '/admin.css',
            $theme_dir . '/css/admin.css',
            $theme_dir . '/assets/css/admin.css'
        ];
        
        foreach ($admin_css_files as $file) {
            if (file_exists($file)) {
                $conflicts[] = [
                    'theme' => $current_theme->get('Name'),
                    'file' => basename($file),
                    'reason' => 'Theme includes admin styling',
                    'severity' => 'low'
                ];
                break;
            }
        }
        
        return $conflicts;
    }
    
    /**
     * Detect JavaScript conflicts
     * 
     * @return array List of JavaScript conflicts
     */
    private function detect_js_conflicts() {
        $conflicts = [];
        
        // Check for jQuery conflicts
        global $wp_scripts;
        
        if (isset($wp_scripts->registered['jquery'])) {
            $jquery_version = $wp_scripts->registered['jquery']->ver;
            
            if (version_compare($jquery_version, '3.0', '<')) {
                $conflicts[] = [
                    'type' => 'jQuery Version',
                    'version' => $jquery_version,
                    'reason' => 'Old jQuery version may cause compatibility issues',
                    'severity' => 'low'
                ];
            }
        }
        
        return $conflicts;
    }
    
    /**
     * Get performance metrics
     * 
     * @return array Performance metrics
     */
    public function get_performance_metrics() {
        $start_time = microtime(true);
        
        $metrics = [
            'memory' => $this->get_memory_metrics(),
            'cache' => $this->get_cache_metrics(),
            'database' => $this->get_database_metrics(),
            'execution_time' => round((microtime(true) - $start_time) * 1000, 2) . 'ms'
        ];
        
        return $metrics;
    }
    
    /**
     * Get memory metrics
     * 
     * @return array Memory metrics
     */
    private function get_memory_metrics() {
        $current_usage = memory_get_usage(true);
        $peak_usage = memory_get_peak_usage(true);
        $limit = ini_get('memory_limit');
        $limit_bytes = $this->parse_memory_limit($limit);
        
        $usage_percentage = $limit_bytes > 0 ? round(($current_usage / $limit_bytes) * 100, 2) : 0;
        
        return [
            'current' => size_format($current_usage),
            'current_bytes' => $current_usage,
            'peak' => size_format($peak_usage),
            'peak_bytes' => $peak_usage,
            'limit' => $limit,
            'limit_bytes' => $limit_bytes,
            'usage_percentage' => $usage_percentage
        ];
    }
    
    /**
     * Get cache metrics
     * 
     * @return array Cache metrics
     */
    private function get_cache_metrics() {
        $metrics = [
            'object_cache_enabled' => wp_using_ext_object_cache(),
            'transients_count' => $this->count_transients()
        ];
        
        // Get cache service stats if available
        if ($this->cache_service && method_exists($this->cache_service, 'get_stats')) {
            $stats = $this->cache_service->get_stats();
            $metrics['hit_rate'] = $stats['hit_rate'] ?? 0;
            $metrics['hits'] = $stats['hits'] ?? 0;
            $metrics['misses'] = $stats['misses'] ?? 0;
        }
        
        return $metrics;
    }
    
    /**
     * Get database metrics
     * 
     * @return array Database metrics
     */
    private function get_database_metrics() {
        global $wpdb;
        
        return [
            'queries' => $wpdb->num_queries,
            'query_time' => isset($wpdb->query_time) ? round($wpdb->query_time, 4) . 's' : 'N/A',
            'prefix' => $wpdb->prefix,
            'charset' => $wpdb->charset,
            'collate' => $wpdb->collate
        ];
    }
    
    /**
     * Generate recommendations based on health checks
     * 
     * @param array $checks Health check results
     * @return array Recommendations
     */
    public function generate_recommendations($checks) {
        $recommendations = [];
        
        // PHP version recommendations
        if ($checks['php_version']['status'] === self::STATUS_CRITICAL) {
            $recommendations[] = [
                'severity' => 'critical',
                'category' => 'system',
                'title' => 'Upgrade PHP Version',
                'description' => $checks['php_version']['message'],
                'action' => 'Contact your hosting provider to upgrade PHP to version ' . $checks['php_version']['recommended_version'] . ' or higher',
                'priority' => 1
            ];
        } elseif ($checks['php_version']['status'] === self::STATUS_WARNING) {
            $recommendations[] = [
                'severity' => 'warning',
                'category' => 'system',
                'title' => 'Consider PHP Upgrade',
                'description' => $checks['php_version']['message'],
                'action' => 'Upgrade to PHP ' . $checks['php_version']['recommended_version'] . ' for better performance',
                'priority' => 3
            ];
        }
        
        // WordPress version recommendations
        if ($checks['wordpress_version']['status'] === self::STATUS_CRITICAL) {
            $recommendations[] = [
                'severity' => 'critical',
                'category' => 'system',
                'title' => 'Upgrade WordPress',
                'description' => $checks['wordpress_version']['message'],
                'action' => 'Update WordPress to version ' . $checks['wordpress_version']['recommended_version'] . ' or higher',
                'priority' => 1
            ];
        } elseif ($checks['wordpress_version']['status'] === self::STATUS_WARNING) {
            $recommendations[] = [
                'severity' => 'warning',
                'category' => 'system',
                'title' => 'WordPress Update Available',
                'description' => $checks['wordpress_version']['message'],
                'action' => 'Update WordPress for better REST API support and security',
                'priority' => 2
            ];
        }
        
        // Settings integrity recommendations
        if ($checks['settings_integrity']['status'] !== self::STATUS_HEALTHY) {
            $severity = $checks['settings_integrity']['status'] === self::STATUS_CRITICAL ? 'critical' : 'warning';
            $recommendations[] = [
                'severity' => $severity,
                'category' => 'settings',
                'title' => 'Fix Settings Issues',
                'description' => $checks['settings_integrity']['message'],
                'action' => 'Reset settings to defaults or manually fix invalid values',
                'priority' => $severity === 'critical' ? 1 : 3
            ];
        }
        
        // File permissions recommendations
        if ($checks['file_permissions']['status'] === self::STATUS_CRITICAL) {
            $recommendations[] = [
                'severity' => 'critical',
                'category' => 'filesystem',
                'title' => 'Fix File Permissions',
                'description' => $checks['file_permissions']['message'],
                'action' => 'Set proper permissions on directories: ' . implode(', ', $checks['file_permissions']['issues']),
                'priority' => 1
            ];
        }
        
        // Cache recommendations
        if ($checks['cache_status']['status'] === self::STATUS_WARNING) {
            $recommendations[] = [
                'severity' => 'info',
                'category' => 'performance',
                'title' => 'Enable Object Cache',
                'description' => $checks['cache_status']['message'],
                'action' => 'Install Redis or Memcached for better performance',
                'priority' => 4
            ];
        }
        
        // Conflict recommendations
        if ($checks['conflicts']['total_conflicts'] > 0) {
            $high_severity_conflicts = array_filter(
                $checks['conflicts']['plugin_conflicts'],
                function($conflict) {
                    return $conflict['severity'] === 'high';
                }
            );
            
            if (!empty($high_severity_conflicts)) {
                $plugin_names = array_column($high_severity_conflicts, 'name');
                $recommendations[] = [
                    'severity' => 'warning',
                    'category' => 'conflicts',
                    'title' => 'Review Plugin Conflicts',
                    'description' => 'High-severity conflicts detected with: ' . implode(', ', $plugin_names),
                    'action' => 'Test plugin functionality and consider disabling conflicting plugins',
                    'priority' => 2
                ];
            }
        }
        
        // Sort by priority
        usort($recommendations, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
        
        return $recommendations;
    }
    
    /**
     * Calculate overall health status
     * 
     * @param array $checks Health check results
     * @return string Overall status (healthy/warning/critical)
     */
    private function calculate_overall_status($checks) {
        $critical_count = 0;
        $warning_count = 0;
        
        foreach ($checks as $check) {
            if ($check['status'] === self::STATUS_CRITICAL) {
                $critical_count++;
            } elseif ($check['status'] === self::STATUS_WARNING) {
                $warning_count++;
            }
        }
        
        if ($critical_count > 0) {
            return self::STATUS_CRITICAL;
        } elseif ($warning_count > 0) {
            return self::STATUS_WARNING;
        }
        
        return self::STATUS_HEALTHY;
    }
    
    /**
     * Generate summary of health checks
     * 
     * @param array $checks Health check results
     * @return array Summary
     */
    private function generate_summary($checks) {
        $total = count($checks);
        $healthy = 0;
        $warning = 0;
        $critical = 0;
        
        foreach ($checks as $check) {
            switch ($check['status']) {
                case self::STATUS_HEALTHY:
                    $healthy++;
                    break;
                case self::STATUS_WARNING:
                    $warning++;
                    break;
                case self::STATUS_CRITICAL:
                    $critical++;
                    break;
            }
        }
        
        return [
            'total_checks' => $total,
            'healthy' => $healthy,
            'warning' => $warning,
            'critical' => $critical,
            'health_percentage' => round(($healthy / $total) * 100, 2)
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
