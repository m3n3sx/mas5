<?php
/**
 * Final End-to-End Testing Script
 * 
 * Comprehensive test of all REST API functionality for Modern Admin Styler V2
 * Tests complete plugin functionality with REST API integration
 * 
 * @package Modern_Admin_Styler_V2
 * @subpackage Tests
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/../../../');
}

// Load WordPress
require_once ABSPATH . 'wp-load.php';

class MAS_Final_E2E_Test {
    
    private $results = [];
    private $passed = 0;
    private $failed = 0;
    private $rest_base = '/wp-json/mas-v2/v1';
    
    public function __construct() {
        // Ensure user is admin
        if (!current_user_can('manage_options')) {
            wp_die('This test requires administrator privileges.');
        }
    }
    
    /**
     * Run all end-to-end tests
     */
    public function run_all_tests() {
        echo "<h1>Modern Admin Styler V2 - Final End-to-End Testing</h1>\n";
        echo "<p>Testing complete plugin functionality with REST API</p>\n";
        echo "<hr>\n";
        
        // Test REST API Infrastructure
        $this->test_rest_api_infrastructure();
        
        // Test Settings Workflow
        $this->test_settings_complete_workflow();
        
        // Test Theme Management
        $this->test_theme_management_workflow();
        
        // Test Backup and Restore
        $this->test_backup_restore_workflow();
        
        // Test Import/Export
        $this->test_import_export_workflow();
        
        // Test Live Preview
        $this->test_live_preview_workflow();
        
        // Test Diagnostics
        $this->test_diagnostics_workflow();
        
        // Test Security Features
        $this->test_security_features();
        
        // Test Performance Features
        $this->test_performance_features();
        
        // Test Backward Compatibility
        $this->test_backward_compatibility();
        
        // Test Upgrade Path
        $this->test_upgrade_path();
        
        // Display results
        $this->display_results();
    }
    
    /**
     * Test REST API Infrastructure
     */
    private function test_rest_api_infrastructure() {
        echo "<h2>1. REST API Infrastructure Tests</h2>\n";
        
        // Test namespace registration
        $this->assert_true(
            rest_get_server()->get_namespaces(),
            'REST API server is available',
            'rest_api_available'
        );
        
        // Test namespace exists
        $namespaces = rest_get_server()->get_namespaces();
        $this->assert_true(
            in_array('mas-v2/v1', $namespaces),
            'MAS v2 namespace is registered',
            'namespace_registered'
        );
        
        // Test routes are registered
        $routes = rest_get_server()->get_routes();
        $expected_routes = [
            '/mas-v2/v1/settings',
            '/mas-v2/v1/themes',
            '/mas-v2/v1/backups',
            '/mas-v2/v1/export',
            '/mas-v2/v1/import',
            '/mas-v2/v1/preview',
            '/mas-v2/v1/diagnostics'
        ];
        
        foreach ($expected_routes as $route) {
            $this->assert_true(
                isset($routes[$route]),
                "Route {$route} is registered",
                'route_' . sanitize_key($route)
            );
        }
    }
    
    /**
     * Test complete settings workflow
     */
    private function test_settings_complete_workflow() {
        echo "<h2>2. Settings Complete Workflow Tests</h2>\n";
        
        // 1. Get current settings
        $request = new WP_REST_Request('GET', '/mas-v2/v1/settings');
        $response = rest_do_request($request);
        
        $this->assert_equals(
            200,
            $response->get_status(),
            'GET settings returns 200',
            'get_settings_status'
        );
        
        $data = $response->get_data();
        $this->assert_true(
            isset($data['success']) && $data['success'],
            'GET settings returns success',
            'get_settings_success'
        );
        
        // Store original settings for restoration
        $original_settings = $data['data'];
        
        // 2. Update settings (partial)
        $request = new WP_REST_Request('PUT', '/mas-v2/v1/settings');
        $request->set_param('menu_background', '#ff0000');
        $response = rest_do_request($request);
        
        $this->assert_equals(
            200,
            $response->get_status(),
            'PUT settings returns 200',
            'put_settings_status'
        );
        
        // 3. Verify update
        $request = new WP_REST_Request('GET', '/mas-v2/v1/settings');
        $response = rest_do_request($request);
        $data = $response->get_data();
        
        $this->assert_equals(
            '#ff0000',
            $data['data']['menu_background'],
            'Settings update persisted correctly',
            'settings_persistence'
        );
        
        // 4. Save complete settings
        $request = new WP_REST_Request('POST', '/mas-v2/v1/settings');
        $request->set_param('menu_background', '#00ff00');
        $request->set_param('menu_text_color', '#ffffff');
        $response = rest_do_request($request);
        
        $this->assert_equals(
            200,
            $response->get_status(),
            'POST settings returns 200',
            'post_settings_status'
        );
        
        // 5. Restore original settings
        $request = new WP_REST_Request('POST', '/mas-v2/v1/settings');
        foreach ($original_settings as $key => $value) {
            $request->set_param($key, $value);
        }
        rest_do_request($request);
    }
    
    /**
     * Test theme management workflow
     */
    private function test_theme_management_workflow() {
        echo "<h2>3. Theme Management Workflow Tests</h2>\n";
        
        // 1. Get all themes
        $request = new WP_REST_Request('GET', '/mas-v2/v1/themes');
        $response = rest_do_request($request);
        
        $this->assert_equals(
            200,
            $response->get_status(),
            'GET themes returns 200',
            'get_themes_status'
        );
        
        $data = $response->get_data();
        $this->assert_true(
            isset($data['data']) && is_array($data['data']),
            'Themes list is returned',
            'themes_list'
        );
        
        // 2. Apply a theme
        if (!empty($data['data'])) {
            $first_theme = reset($data['data']);
            $theme_id = $first_theme['id'];
            
            $request = new WP_REST_Request('POST', "/mas-v2/v1/themes/{$theme_id}/apply");
            $response = rest_do_request($request);
            
            $this->assert_equals(
                200,
                $response->get_status(),
                'Apply theme returns 200',
                'apply_theme_status'
            );
        }
        
        // 3. Create custom theme
        $request = new WP_REST_Request('POST', '/mas-v2/v1/themes');
        $request->set_param('name', 'Test Theme E2E');
        $request->set_param('settings', [
            'menu_background' => '#123456',
            'menu_text_color' => '#ffffff'
        ]);
        $response = rest_do_request($request);
        
        $this->assert_true(
            $response->get_status() === 200 || $response->get_status() === 201,
            'Create custom theme succeeds',
            'create_theme_status'
        );
    }
    
    /**
     * Test backup and restore workflow
     */
    private function test_backup_restore_workflow() {
        echo "<h2>4. Backup and Restore Workflow Tests</h2>\n";
        
        // 1. Create backup
        $request = new WP_REST_Request('POST', '/mas-v2/v1/backups');
        $request->set_param('note', 'E2E Test Backup');
        $response = rest_do_request($request);
        
        $this->assert_true(
            $response->get_status() === 200 || $response->get_status() === 201,
            'Create backup succeeds',
            'create_backup_status'
        );
        
        $data = $response->get_data();
        $backup_id = isset($data['data']['id']) ? $data['data']['id'] : null;
        
        // 2. List backups
        $request = new WP_REST_Request('GET', '/mas-v2/v1/backups');
        $response = rest_do_request($request);
        
        $this->assert_equals(
            200,
            $response->get_status(),
            'GET backups returns 200',
            'get_backups_status'
        );
        
        // 3. Restore backup (if we have one)
        if ($backup_id) {
            $request = new WP_REST_Request('POST', "/mas-v2/v1/backups/{$backup_id}/restore");
            $response = rest_do_request($request);
            
            $this->assert_equals(
                200,
                $response->get_status(),
                'Restore backup returns 200',
                'restore_backup_status'
            );
        }
    }
    
    /**
     * Test import/export workflow
     */
    private function test_import_export_workflow() {
        echo "<h2>5. Import/Export Workflow Tests</h2>\n";
        
        // 1. Export settings
        $request = new WP_REST_Request('GET', '/mas-v2/v1/export');
        $response = rest_do_request($request);
        
        $this->assert_equals(
            200,
            $response->get_status(),
            'Export settings returns 200',
            'export_status'
        );
        
        $exported_data = $response->get_data();
        
        // 2. Import settings
        $request = new WP_REST_Request('POST', '/mas-v2/v1/import');
        $request->set_param('settings', $exported_data);
        $response = rest_do_request($request);
        
        $this->assert_equals(
            200,
            $response->get_status(),
            'Import settings returns 200',
            'import_status'
        );
    }
    
    /**
     * Test live preview workflow
     */
    private function test_live_preview_workflow() {
        echo "<h2>6. Live Preview Workflow Tests</h2>\n";
        
        // Generate preview
        $request = new WP_REST_Request('POST', '/mas-v2/v1/preview');
        $request->set_param('menu_background', '#abcdef');
        $request->set_param('menu_text_color', '#000000');
        $response = rest_do_request($request);
        
        $this->assert_equals(
            200,
            $response->get_status(),
            'Generate preview returns 200',
            'preview_status'
        );
        
        $data = $response->get_data();
        $this->assert_true(
            isset($data['data']['css']),
            'Preview CSS is generated',
            'preview_css_generated'
        );
    }
    
    /**
     * Test diagnostics workflow
     */
    private function test_diagnostics_workflow() {
        echo "<h2>7. Diagnostics Workflow Tests</h2>\n";
        
        $request = new WP_REST_Request('GET', '/mas-v2/v1/diagnostics');
        $response = rest_do_request($request);
        
        $this->assert_equals(
            200,
            $response->get_status(),
            'Diagnostics returns 200',
            'diagnostics_status'
        );
        
        $data = $response->get_data();
        $this->assert_true(
            isset($data['data']['system_info']),
            'System info is included',
            'diagnostics_system_info'
        );
        
        $this->assert_true(
            isset($data['data']['health_checks']),
            'Health checks are included',
            'diagnostics_health_checks'
        );
    }
    
    /**
     * Test security features
     */
    private function test_security_features() {
        echo "<h2>8. Security Features Tests</h2>\n";
        
        // Test authentication requirement
        $original_user = wp_get_current_user();
        wp_set_current_user(0); // Logout
        
        $request = new WP_REST_Request('GET', '/mas-v2/v1/settings');
        $response = rest_do_request($request);
        
        $this->assert_equals(
            401,
            $response->get_status(),
            'Unauthenticated request returns 401',
            'auth_required'
        );
        
        // Restore user
        wp_set_current_user($original_user->ID);
        
        // Test input sanitization
        $request = new WP_REST_Request('POST', '/mas-v2/v1/settings');
        $request->set_param('menu_background', '<script>alert("xss")</script>');
        $response = rest_do_request($request);
        
        $this->assert_true(
            $response->get_status() === 400 || $response->is_error(),
            'Invalid input is rejected',
            'input_validation'
        );
    }
    
    /**
     * Test performance features
     */
    private function test_performance_features() {
        echo "<h2>9. Performance Features Tests</h2>\n";
        
        // Test caching
        $start = microtime(true);
        $request = new WP_REST_Request('GET', '/mas-v2/v1/settings');
        rest_do_request($request);
        $first_time = microtime(true) - $start;
        
        $start = microtime(true);
        $request = new WP_REST_Request('GET', '/mas-v2/v1/settings');
        rest_do_request($request);
        $second_time = microtime(true) - $start;
        
        $this->assert_true(
            $second_time <= $first_time,
            'Caching improves performance',
            'caching_performance'
        );
        
        // Test response time
        $start = microtime(true);
        $request = new WP_REST_Request('GET', '/mas-v2/v1/settings');
        rest_do_request($request);
        $response_time = (microtime(true) - $start) * 1000; // Convert to ms
        
        $this->assert_true(
            $response_time < 200,
            "Settings retrieval under 200ms (actual: " . round($response_time, 2) . "ms)",
            'response_time'
        );
    }
    
    /**
     * Test backward compatibility
     */
    private function test_backward_compatibility() {
        echo "<h2>10. Backward Compatibility Tests</h2>\n";
        
        // Test that AJAX handlers still exist
        $this->assert_true(
            has_action('wp_ajax_mas_v2_save_settings'),
            'AJAX save settings handler exists',
            'ajax_save_exists'
        );
        
        $this->assert_true(
            has_action('wp_ajax_mas_v2_get_settings'),
            'AJAX get settings handler exists',
            'ajax_get_exists'
        );
        
        // Test deprecation service
        $this->assert_true(
            class_exists('MAS_Deprecation_Service'),
            'Deprecation service exists',
            'deprecation_service_exists'
        );
        
        // Test feature flags
        $this->assert_true(
            class_exists('MAS_Feature_Flags_Service'),
            'Feature flags service exists',
            'feature_flags_exists'
        );
    }
    
    /**
     * Test upgrade path
     */
    private function test_upgrade_path() {
        echo "<h2>11. Upgrade Path Tests</h2>\n";
        
        // Test migration utility exists
        $this->assert_true(
            class_exists('MAS_Migration_Utility'),
            'Migration utility exists',
            'migration_utility_exists'
        );
        
        // Test that all services are properly initialized
        $services = [
            'MAS_Settings_Service',
            'MAS_Theme_Service',
            'MAS_Backup_Service',
            'MAS_Import_Export_Service',
            'MAS_CSS_Generator_Service',
            'MAS_Validation_Service',
            'MAS_Diagnostics_Service',
            'MAS_Cache_Service',
            'MAS_Rate_Limiter_Service'
        ];
        
        foreach ($services as $service) {
            $this->assert_true(
                class_exists($service),
                "{$service} is available",
                'service_' . sanitize_key($service)
            );
        }
        
        // Test that all controllers are registered
        $controllers = [
            'MAS_Settings_Controller',
            'MAS_Themes_Controller',
            'MAS_Backups_Controller',
            'MAS_Import_Export_Controller',
            'MAS_Preview_Controller',
            'MAS_Diagnostics_Controller'
        ];
        
        foreach ($controllers as $controller) {
            $this->assert_true(
                class_exists($controller),
                "{$controller} is available",
                'controller_' . sanitize_key($controller)
            );
        }
    }
    
    /**
     * Assert true helper
     */
    private function assert_true($condition, $message, $test_id) {
        if ($condition) {
            $this->passed++;
            $this->results[] = [
                'status' => 'PASS',
                'message' => $message,
                'test_id' => $test_id
            ];
            echo "<p style='color: green;'>✓ PASS: {$message}</p>\n";
        } else {
            $this->failed++;
            $this->results[] = [
                'status' => 'FAIL',
                'message' => $message,
                'test_id' => $test_id
            ];
            echo "<p style='color: red;'>✗ FAIL: {$message}</p>\n";
        }
    }
    
    /**
     * Assert equals helper
     */
    private function assert_equals($expected, $actual, $message, $test_id) {
        $this->assert_true($expected === $actual, $message, $test_id);
    }
    
    /**
     * Display final results
     */
    private function display_results() {
        echo "<hr>\n";
        echo "<h2>Final Results</h2>\n";
        echo "<p><strong>Total Tests:</strong> " . ($this->passed + $this->failed) . "</p>\n";
        echo "<p style='color: green;'><strong>Passed:</strong> {$this->passed}</p>\n";
        echo "<p style='color: red;'><strong>Failed:</strong> {$this->failed}</p>\n";
        
        $percentage = $this->passed + $this->failed > 0 
            ? round(($this->passed / ($this->passed + $this->failed)) * 100, 2) 
            : 0;
        
        echo "<p><strong>Success Rate:</strong> {$percentage}%</p>\n";
        
        if ($this->failed === 0) {
            echo "<h3 style='color: green;'>✓ All tests passed! Plugin is ready for release.</h3>\n";
        } else {
            echo "<h3 style='color: red;'>✗ Some tests failed. Please review and fix issues before release.</h3>\n";
        }
    }
}

// Run tests
$test = new MAS_Final_E2E_Test();
$test->run_all_tests();
