<?php
/**
 * Migration Utility for Modern Admin Styler V2
 * 
 * Helps users transition from AJAX to REST API with compatibility checks
 * and rollback mechanisms.
 *
 * @package ModernAdminStylerV2
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class MAS_Migration_Utility {
    
    /**
     * Migration status option name
     */
    const STATUS_OPTION = 'mas_v2_migration_status';
    
    /**
     * Backup option name
     */
    const BACKUP_OPTION = 'mas_v2_migration_backup';
    
    /**
     * Feature flags service
     */
    private $feature_flags_service;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->feature_flags_service = MAS_Feature_Flags_Service::get_instance();
    }
    
    /**
     * Run migration compatibility check
     *
     * @return array Migration status and recommendations
     */
    public function run_compatibility_check() {
        $results = [
            'compatible' => true,
            'warnings' => [],
            'errors' => [],
            'recommendations' => [],
            'checks' => []
        ];
        
        // Check WordPress version
        $wp_check = $this->check_wordpress_version();
        $results['checks']['wordpress'] = $wp_check;
        if (!$wp_check['passed']) {
            $results['compatible'] = false;
            $results['errors'][] = $wp_check['message'];
        }
        
        // Check PHP version
        $php_check = $this->check_php_version();
        $results['checks']['php'] = $php_check;
        if (!$php_check['passed']) {
            $results['compatible'] = false;
            $results['errors'][] = $php_check['message'];
        }
        
        // Check REST API availability
        $rest_check = $this->check_rest_api_availability();
        $results['checks']['rest_api'] = $rest_check;
        if (!$rest_check['passed']) {
            $results['compatible'] = false;
            $results['errors'][] = $rest_check['message'];
        }
        
        // Check for conflicting plugins
        $plugin_check = $this->check_conflicting_plugins();
        $results['checks']['plugins'] = $plugin_check;
        if (!$plugin_check['passed']) {
            $results['warnings'][] = $plugin_check['message'];
        }
        
        // Check custom code compatibility
        $custom_code_check = $this->check_custom_code_compatibility();
        $results['checks']['custom_code'] = $custom_code_check;
        if (!$custom_code_check['passed']) {
            $results['warnings'][] = $custom_code_check['message'];
        }
        
        // Check server configuration
        $server_check = $this->check_server_configuration();
        $results['checks']['server'] = $server_check;
        if (!$server_check['passed']) {
            $results['warnings'][] = $server_check['message'];
        }
        
        // Generate recommendations
        $results['recommendations'] = $this->generate_recommendations($results['checks']);
        
        return $results;
    }
    
    /**
     * Check WordPress version compatibility
     *
     * @return array Check result
     */
    private function check_wordpress_version() {
        global $wp_version;
        $required_version = '5.0';
        
        $compatible = version_compare($wp_version, $required_version, '>=');
        
        return [
            'passed' => $compatible,
            'message' => $compatible 
                ? sprintf(__('WordPress %s is compatible', 'modern-admin-styler-v2'), $wp_version)
                : sprintf(__('WordPress %s or higher is required (current: %s)', 'modern-admin-styler-v2'), $required_version, $wp_version),
            'current_version' => $wp_version,
            'required_version' => $required_version
        ];
    }
    
    /**
     * Check PHP version compatibility
     *
     * @return array Check result
     */
    private function check_php_version() {
        $current_version = PHP_VERSION;
        $required_version = '7.4';
        
        $compatible = version_compare($current_version, $required_version, '>=');
        
        return [
            'passed' => $compatible,
            'message' => $compatible 
                ? sprintf(__('PHP %s is compatible', 'modern-admin-styler-v2'), $current_version)
                : sprintf(__('PHP %s or higher is required (current: %s)', 'modern-admin-styler-v2'), $required_version, $current_version),
            'current_version' => $current_version,
            'required_version' => $required_version
        ];
    }
    
    /**
     * Check REST API availability
     *
     * @return array Check result
     */
    private function check_rest_api_availability() {
        $rest_url = rest_url('wp/v2/');
        $available = !empty($rest_url);
        
        // Test REST API endpoint
        $test_passed = false;
        $error_message = '';
        
        if ($available) {
            $response = wp_remote_get($rest_url, [
                'timeout' => 10,
                'sslverify' => false
            ]);
            
            if (!is_wp_error($response)) {
                $status_code = wp_remote_retrieve_response_code($response);
                $test_passed = ($status_code === 200);
            } else {
                $error_message = $response->get_error_message();
            }
        }
        
        return [
            'passed' => $available && $test_passed,
            'message' => $available && $test_passed
                ? __('REST API is available and working', 'modern-admin-styler-v2')
                : sprintf(__('REST API is not available or not working: %s', 'modern-admin-styler-v2'), $error_message),
            'rest_url' => $rest_url,
            'test_passed' => $test_passed,
            'error' => $error_message
        ];
    }
    
    /**
     * Check for conflicting plugins
     *
     * @return array Check result
     */
    private function check_conflicting_plugins() {
        $conflicting_plugins = [
            'disable-json-api/disable-json-api.php' => 'Disable JSON API',
            'disable-wp-rest-api/disable-wp-rest-api.php' => 'Disable WP REST API',
            'wp-rest-api-controller/wp-rest-api-controller.php' => 'WP REST API Controller'
        ];
        
        $active_plugins = get_option('active_plugins', []);
        $conflicts = [];
        
        foreach ($conflicting_plugins as $plugin_file => $plugin_name) {
            if (in_array($plugin_file, $active_plugins)) {
                $conflicts[] = $plugin_name;
            }
        }
        
        return [
            'passed' => empty($conflicts),
            'message' => empty($conflicts)
                ? __('No conflicting plugins detected', 'modern-admin-styler-v2')
                : sprintf(__('Conflicting plugins detected: %s', 'modern-admin-styler-v2'), implode(', ', $conflicts)),
            'conflicts' => $conflicts
        ];
    }
    
    /**
     * Check custom code compatibility
     *
     * @return array Check result
     */
    private function check_custom_code_compatibility() {
        $issues = [];
        
        // Check for custom AJAX handlers that might conflict
        $custom_handlers = $this->scan_for_custom_ajax_handlers();
        if (!empty($custom_handlers)) {
            $issues[] = sprintf(__('Custom AJAX handlers found: %s', 'modern-admin-styler-v2'), implode(', ', $custom_handlers));
        }
        
        // Check for direct AJAX calls in JavaScript
        $ajax_calls = $this->scan_for_ajax_calls();
        if (!empty($ajax_calls)) {
            $issues[] = sprintf(__('Direct AJAX calls found in %d files', 'modern-admin-styler-v2'), count($ajax_calls));
        }
        
        return [
            'passed' => empty($issues),
            'message' => empty($issues)
                ? __('No custom code compatibility issues detected', 'modern-admin-styler-v2')
                : implode('; ', $issues),
            'custom_handlers' => $custom_handlers ?? [],
            'ajax_calls' => $ajax_calls ?? []
        ];
    }
    
    /**
     * Check server configuration
     *
     * @return array Check result
     */
    private function check_server_configuration() {
        $issues = [];
        
        // Check memory limit
        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        $recommended_memory = 128 * 1024 * 1024; // 128MB
        
        if ($memory_limit < $recommended_memory) {
            $issues[] = sprintf(
                __('Memory limit is low: %s (recommended: 128M)', 'modern-admin-styler-v2'),
                size_format($memory_limit)
            );
        }
        
        // Check max execution time
        $max_execution_time = ini_get('max_execution_time');
        if ($max_execution_time > 0 && $max_execution_time < 30) {
            $issues[] = sprintf(
                __('Max execution time is low: %ds (recommended: 30s)', 'modern-admin-styler-v2'),
                $max_execution_time
            );
        }
        
        // Check if cURL is available
        if (!function_exists('curl_init')) {
            $issues[] = __('cURL is not available (required for REST API calls)', 'modern-admin-styler-v2');
        }
        
        return [
            'passed' => empty($issues),
            'message' => empty($issues)
                ? __('Server configuration is optimal', 'modern-admin-styler-v2')
                : implode('; ', $issues),
            'issues' => $issues
        ];
    }
    
    /**
     * Scan for custom AJAX handlers
     *
     * @return array List of custom handlers
     */
    private function scan_for_custom_ajax_handlers() {
        // This is a simplified scan - in a real implementation,
        // you might scan theme files and other plugins
        $handlers = [];
        
        // Check current theme for MAS-related AJAX handlers
        $theme_dir = get_template_directory();
        $files_to_scan = [
            $theme_dir . '/functions.php',
            $theme_dir . '/js/admin.js',
            $theme_dir . '/js/custom.js'
        ];
        
        foreach ($files_to_scan as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                if (preg_match_all('/wp_ajax_mas_v2_([a-zA-Z0-9_]+)/', $content, $matches)) {
                    $handlers = array_merge($handlers, $matches[1]);
                }
            }
        }
        
        return array_unique($handlers);
    }
    
    /**
     * Scan for AJAX calls in JavaScript files
     *
     * @return array List of files with AJAX calls
     */
    private function scan_for_ajax_calls() {
        $files_with_ajax = [];
        
        // Scan theme JavaScript files
        $theme_dir = get_template_directory();
        $js_files = glob($theme_dir . '/js/*.js');
        
        foreach ($js_files as $file) {
            $content = file_get_contents($file);
            if (preg_match('/mas_v2_[a-zA-Z0-9_]+/', $content)) {
                $files_with_ajax[] = basename($file);
            }
        }
        
        return $files_with_ajax;
    }
    
    /**
     * Generate recommendations based on check results
     *
     * @param array $checks Check results
     * @return array Recommendations
     */
    private function generate_recommendations($checks) {
        $recommendations = [];
        
        // WordPress version recommendation
        if (!$checks['wordpress']['passed']) {
            $recommendations[] = [
                'type' => 'critical',
                'title' => __('Update WordPress', 'modern-admin-styler-v2'),
                'description' => __('Update WordPress to the latest version to ensure compatibility.', 'modern-admin-styler-v2'),
                'action' => 'update_wordpress'
            ];
        }
        
        // PHP version recommendation
        if (!$checks['php']['passed']) {
            $recommendations[] = [
                'type' => 'critical',
                'title' => __('Update PHP', 'modern-admin-styler-v2'),
                'description' => __('Contact your hosting provider to update PHP to a supported version.', 'modern-admin-styler-v2'),
                'action' => 'update_php'
            ];
        }
        
        // REST API recommendation
        if (!$checks['rest_api']['passed']) {
            $recommendations[] = [
                'type' => 'critical',
                'title' => __('Enable REST API', 'modern-admin-styler-v2'),
                'description' => __('Ensure the WordPress REST API is enabled and accessible.', 'modern-admin-styler-v2'),
                'action' => 'enable_rest_api'
            ];
        }
        
        // Plugin conflicts recommendation
        if (!$checks['plugins']['passed']) {
            $recommendations[] = [
                'type' => 'warning',
                'title' => __('Resolve Plugin Conflicts', 'modern-admin-styler-v2'),
                'description' => __('Deactivate or configure conflicting plugins to allow REST API access.', 'modern-admin-styler-v2'),
                'action' => 'resolve_conflicts'
            ];
        }
        
        // Custom code recommendation
        if (!$checks['custom_code']['passed']) {
            $recommendations[] = [
                'type' => 'info',
                'title' => __('Update Custom Code', 'modern-admin-styler-v2'),
                'description' => __('Review and update custom code to use the new REST API endpoints.', 'modern-admin-styler-v2'),
                'action' => 'update_custom_code'
            ];
        }
        
        // Server configuration recommendation
        if (!$checks['server']['passed']) {
            $recommendations[] = [
                'type' => 'warning',
                'title' => __('Optimize Server Configuration', 'modern-admin-styler-v2'),
                'description' => __('Contact your hosting provider to optimize server settings.', 'modern-admin-styler-v2'),
                'action' => 'optimize_server'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Create migration backup
     *
     * @return bool Success status
     */
    public function create_migration_backup() {
        $backup_data = [
            'timestamp' => current_time('mysql'),
            'version' => MAS_V2_VERSION,
            'settings' => get_option('mas_v2_settings', []),
            'feature_flags' => get_option(MAS_Feature_Flags_Service::OPTION_NAME, []),
            'active_plugins' => get_option('active_plugins', []),
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION
        ];
        
        return update_option(self::BACKUP_OPTION, $backup_data);
    }
    
    /**
     * Restore from migration backup
     *
     * @return bool Success status
     */
    public function restore_from_backup() {
        $backup_data = get_option(self::BACKUP_OPTION);
        
        if (!$backup_data) {
            return false;
        }
        
        // Restore settings
        if (isset($backup_data['settings'])) {
            update_option('mas_v2_settings', $backup_data['settings']);
        }
        
        // Restore feature flags
        if (isset($backup_data['feature_flags'])) {
            update_option(MAS_Feature_Flags_Service::OPTION_NAME, $backup_data['feature_flags']);
        }
        
        // Log restoration
        error_log(sprintf(
            '[MAS Migration] Restored from backup created at %s',
            $backup_data['timestamp']
        ));
        
        return true;
    }
    
    /**
     * Get migration status
     *
     * @return array Migration status
     */
    public function get_migration_status() {
        $status = get_option(self::STATUS_OPTION, [
            'phase' => 'not_started',
            'started_at' => null,
            'completed_at' => null,
            'errors' => [],
            'warnings' => []
        ]);
        
        return $status;
    }
    
    /**
     * Update migration status
     *
     * @param array $status_update Status updates
     * @return bool Success status
     */
    public function update_migration_status($status_update) {
        $current_status = $this->get_migration_status();
        $new_status = array_merge($current_status, $status_update);
        
        return update_option(self::STATUS_OPTION, $new_status);
    }
    
    /**
     * Start migration process
     *
     * @return array Migration result
     */
    public function start_migration() {
        // Run compatibility check first
        $compatibility = $this->run_compatibility_check();
        
        if (!$compatibility['compatible']) {
            return [
                'success' => false,
                'message' => __('Migration cannot proceed due to compatibility issues', 'modern-admin-styler-v2'),
                'compatibility' => $compatibility
            ];
        }
        
        // Create backup
        if (!$this->create_migration_backup()) {
            return [
                'success' => false,
                'message' => __('Failed to create migration backup', 'modern-admin-styler-v2')
            ];
        }
        
        // Update migration status
        $this->update_migration_status([
            'phase' => 'in_progress',
            'started_at' => current_time('mysql')
        ]);
        
        // Enable gradual rollout
        $this->feature_flags_service->update_flags([
            'rest_api_enabled' => true,
            'ajax_fallback_enabled' => true,
            'dual_mode_enabled' => true,
            'gradual_rollout_percentage' => 25 // Start with 25%
        ]);
        
        return [
            'success' => true,
            'message' => __('Migration started successfully', 'modern-admin-styler-v2'),
            'phase' => 'gradual_rollout_25'
        ];
    }
    
    /**
     * Complete migration process
     *
     * @return array Migration result
     */
    public function complete_migration() {
        // Enable REST API for all users
        $this->feature_flags_service->update_flags([
            'rest_api_enabled' => true,
            'ajax_fallback_enabled' => false,
            'dual_mode_enabled' => false,
            'gradual_rollout_percentage' => 100,
            'deprecation_warnings_enabled' => true
        ]);
        
        // Update migration status
        $this->update_migration_status([
            'phase' => 'completed',
            'completed_at' => current_time('mysql')
        ]);
        
        return [
            'success' => true,
            'message' => __('Migration completed successfully', 'modern-admin-styler-v2'),
            'phase' => 'completed'
        ];
    }
    
    /**
     * Rollback migration
     *
     * @return array Rollback result
     */
    public function rollback_migration() {
        // Restore from backup
        if (!$this->restore_from_backup()) {
            return [
                'success' => false,
                'message' => __('Failed to restore from backup', 'modern-admin-styler-v2')
            ];
        }
        
        // Reset feature flags to AJAX-only mode
        $this->feature_flags_service->update_flags([
            'rest_api_enabled' => false,
            'ajax_fallback_enabled' => true,
            'dual_mode_enabled' => false,
            'force_ajax' => true,
            'deprecation_warnings_enabled' => false
        ]);
        
        // Update migration status
        $this->update_migration_status([
            'phase' => 'rolled_back',
            'rolled_back_at' => current_time('mysql')
        ]);
        
        return [
            'success' => true,
            'message' => __('Migration rolled back successfully', 'modern-admin-styler-v2'),
            'phase' => 'rolled_back'
        ];
    }
    
    /**
     * Get migration progress
     *
     * @return array Progress information
     */
    public function get_migration_progress() {
        $status = $this->get_migration_status();
        $flags = $this->feature_flags_service->get_flags();
        
        $phases = [
            'not_started' => 0,
            'in_progress' => 25,
            'gradual_rollout_25' => 25,
            'gradual_rollout_50' => 50,
            'gradual_rollout_75' => 75,
            'gradual_rollout_100' => 90,
            'completed' => 100,
            'rolled_back' => 0
        ];
        
        $current_phase = $status['phase'];
        $progress_percentage = $phases[$current_phase] ?? 0;
        
        return [
            'phase' => $current_phase,
            'progress_percentage' => $progress_percentage,
            'rest_api_enabled' => $flags['rest_api_enabled'],
            'ajax_fallback_enabled' => $flags['ajax_fallback_enabled'],
            'dual_mode_enabled' => $flags['dual_mode_enabled'],
            'rollout_percentage' => $flags['gradual_rollout_percentage'],
            'started_at' => $status['started_at'],
            'completed_at' => $status['completed_at'] ?? null
        ];
    }
}