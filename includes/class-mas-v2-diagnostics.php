<?php
/**
 * MAS V2 Diagnostics Class
 * 
 * Provides diagnostic tools for emergency fix verification
 * 
 * @package ModernAdminStylerV2
 * @since 2.2.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class MAS_V2_Diagnostics {
    
    /**
     * Run emergency fix verification
     * 
     * @return array Verification results
     */
    public function run_emergency_fix_verification() {
        $results = [
            'timestamp' => current_time('mysql'),
            'tests' => [],
            'summary' => [
                'total' => 0,
                'passed' => 0,
                'failed' => 0,
                'warnings' => 0
            ]
        ];
        
        // Test 1: Check feature flags service
        $results['tests'][] = $this->test_feature_flags_service();
        
        // Test 2: Check system loading
        $results['tests'][] = $this->check_system_loading();
        
        // Test 3: Check AJAX handlers
        $results['tests'][] = $this->test_ajax_handlers();
        
        // Test 4: Verify settings save
        $results['tests'][] = $this->verify_settings_save();
        
        // Test 5: Check file existence
        $results['tests'][] = $this->check_file_existence();
        
        // Calculate summary
        foreach ($results['tests'] as $test) {
            $results['summary']['total']++;
            if ($test['status'] === 'pass') {
                $results['summary']['passed']++;
            } elseif ($test['status'] === 'fail') {
                $results['summary']['failed']++;
            } elseif ($test['status'] === 'warning') {
                $results['summary']['warnings']++;
            }
        }
        
        return $results;
    }
    
    /**
     * Test feature flags service
     */
    private function test_feature_flags_service() {
        $test = [
            'name' => 'Feature Flags Service',
            'status' => 'pass',
            'message' => '',
            'details' => []
        ];
        
        try {
            if (!class_exists('MAS_Feature_Flags_Service')) {
                require_once MAS_V2_PLUGIN_DIR . 'includes/services/class-mas-feature-flags-service.php';
            }
            
            $flags_service = MAS_Feature_Flags_Service::get_instance();
            
            // Test 1: use_new_frontend() should return false
            $use_new_frontend = $flags_service->use_new_frontend();
            if ($use_new_frontend !== false) {
                $test['status'] = 'fail';
                $test['message'] = 'use_new_frontend() should return false';
                $test['details'][] = "Returned: " . var_export($use_new_frontend, true);
            } else {
                $test['details'][] = '✓ use_new_frontend() returns false';
            }
            
            // Test 2: is_emergency_mode() should return true
            if (method_exists($flags_service, 'is_emergency_mode')) {
                $is_emergency = $flags_service->is_emergency_mode();
                if ($is_emergency !== true) {
                    $test['status'] = 'fail';
                    $test['message'] = 'is_emergency_mode() should return true';
                    $test['details'][] = "Returned: " . var_export($is_emergency, true);
                } else {
                    $test['details'][] = '✓ is_emergency_mode() returns true';
                }
            }
            
            // Test 3: export_for_js() should have correct flags
            $js_flags = $flags_service->export_for_js();
            if ($js_flags['useNewFrontend'] !== false) {
                $test['status'] = 'fail';
                $test['message'] = 'JS flags should have useNewFrontend = false';
            } else {
                $test['details'][] = '✓ JS flags configured correctly';
            }
            
            if ($test['status'] === 'pass') {
                $test['message'] = 'Feature flags service configured correctly';
            }
            
        } catch (Exception $e) {
            $test['status'] = 'fail';
            $test['message'] = 'Error testing feature flags: ' . $e->getMessage();
        }
        
        return $test;
    }
    
    /**
     * Check which systems are enqueued
     */
    public function check_system_loading() {
        $test = [
            'name' => 'System Loading',
            'status' => 'pass',
            'message' => '',
            'details' => []
        ];
        
        global $wp_scripts;
        
        if (!$wp_scripts) {
            $test['status'] = 'warning';
            $test['message'] = 'Cannot check - scripts not enqueued yet';
            return $test;
        }
        
        // Check Phase 2 scripts are enqueued
        $phase2_scripts = [
            'mas-v2-rest-client',
            'mas-v2-settings-form-handler',
            'mas-v2-simple-live-preview'
        ];
        
        foreach ($phase2_scripts as $handle) {
            if (isset($wp_scripts->registered[$handle])) {
                $test['details'][] = "✓ Phase 2 script enqueued: $handle";
            } else {
                $test['status'] = 'warning';
                $test['details'][] = "⚠ Phase 2 script not enqueued: $handle";
            }
        }
        
        // Check Phase 3 scripts are NOT enqueued
        $phase3_scripts = [
            'mas-v2-admin-app',
            'mas-v2-event-bus',
            'mas-v2-state-manager',
            'mas-v2-api-client',
            'mas-v2-error-handler'
        ];
        
        foreach ($phase3_scripts as $handle) {
            if (isset($wp_scripts->registered[$handle])) {
                $test['status'] = 'fail';
                $test['details'][] = "✗ Phase 3 script should NOT be enqueued: $handle";
            } else {
                $test['details'][] = "✓ Phase 3 script correctly excluded: $handle";
            }
        }
        
        if ($test['status'] === 'pass') {
            $test['message'] = 'Only Phase 2 scripts are loaded';
        } elseif ($test['status'] === 'fail') {
            $test['message'] = 'Phase 3 scripts detected - emergency fix not applied';
        }
        
        return $test;
    }
    
    /**
     * Test all AJAX handlers
     */
    public function test_ajax_handlers() {
        $test = [
            'name' => 'AJAX Handlers',
            'status' => 'pass',
            'message' => '',
            'details' => []
        ];
        
        // Check if AJAX actions are registered
        $ajax_actions = [
            'mas_v2_save_settings',
            'mas_v2_get_preview_css',
            'mas_v2_export_settings',
            'mas_v2_import_settings'
        ];
        
        foreach ($ajax_actions as $action) {
            if (has_action("wp_ajax_$action")) {
                $test['details'][] = "✓ AJAX handler registered: $action";
            } else {
                $test['status'] = 'warning';
                $test['details'][] = "⚠ AJAX handler not registered: $action";
            }
        }
        
        if ($test['status'] === 'pass') {
            $test['message'] = 'All AJAX handlers registered';
        } else {
            $test['message'] = 'Some AJAX handlers missing';
        }
        
        return $test;
    }
    
    /**
     * Verify settings save functionality
     */
    public function verify_settings_save() {
        $test = [
            'name' => 'Settings Save',
            'status' => 'pass',
            'message' => '',
            'details' => []
        ];
        
        // Test saving a setting
        $test_key = 'mas_v2_test_setting_' . time();
        $test_value = 'test_value_' . wp_generate_password(8, false);
        
        update_option($test_key, $test_value);
        $retrieved = get_option($test_key);
        
        if ($retrieved === $test_value) {
            $test['details'][] = '✓ Settings can be saved and retrieved';
            delete_option($test_key);
        } else {
            $test['status'] = 'fail';
            $test['message'] = 'Settings save/retrieve failed';
            $test['details'][] = "Expected: $test_value, Got: $retrieved";
        }
        
        // Check main settings option exists
        $main_settings = get_option('mas_v2_settings');
        if ($main_settings !== false) {
            $test['details'][] = '✓ Main settings option exists';
            $test['details'][] = 'Settings count: ' . count($main_settings);
        } else {
            $test['status'] = 'warning';
            $test['details'][] = '⚠ Main settings option not found';
        }
        
        if ($test['status'] === 'pass' && empty($test['message'])) {
            $test['message'] = 'Settings save functionality working';
        }
        
        return $test;
    }
    
    /**
     * Check file existence
     */
    private function check_file_existence() {
        $test = [
            'name' => 'File Existence',
            'status' => 'pass',
            'message' => '',
            'details' => []
        ];
        
        // Check Phase 2 files exist
        $phase2_files = [
            'assets/js/mas-rest-client.js',
            'assets/js/mas-settings-form-handler.js',
            'assets/js/simple-live-preview.js'
        ];
        
        foreach ($phase2_files as $file) {
            $path = MAS_V2_PLUGIN_DIR . $file;
            if (file_exists($path)) {
                $size = filesize($path);
                $test['details'][] = "✓ Phase 2 file exists: $file (" . number_format($size) . " bytes)";
            } else {
                $test['status'] = 'fail';
                $test['details'][] = "✗ Phase 2 file missing: $file";
            }
        }
        
        // Check Phase 3 files exist (but should not be loaded)
        $phase3_files = [
            'assets/js/mas-admin-app.js',
            'assets/js/core/EventBus.js',
            'assets/js/core/StateManager.js'
        ];
        
        foreach ($phase3_files as $file) {
            $path = MAS_V2_PLUGIN_DIR . $file;
            if (file_exists($path)) {
                $test['details'][] = "✓ Phase 3 file exists (not loaded): $file";
            }
        }
        
        if ($test['status'] === 'pass') {
            $test['message'] = 'All required files exist';
        } else {
            $test['message'] = 'Some required files are missing';
        }
        
        return $test;
    }
    
    /**
     * Get diagnostic report as HTML
     */
    public function get_html_report() {
        $results = $this->run_emergency_fix_verification();
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>MAS V2 Emergency Fix Diagnostic Report</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; background: #f0f0f1; }
                h1 { color: #1d2327; border-bottom: 3px solid #2271b1; padding-bottom: 10px; }
                .summary { display: flex; gap: 20px; margin: 20px 0; }
                .summary-card { flex: 1; padding: 20px; border-radius: 4px; text-align: center; background: white; }
                .summary-card h3 { margin: 0 0 10px 0; font-size: 14px; text-transform: uppercase; }
                .summary-card .count { font-size: 36px; font-weight: bold; }
                .test-result { background: white; padding: 20px; margin: 20px 0; border-radius: 4px; border-left: 4px solid #ccc; }
                .test-result.pass { border-left-color: #00a32a; }
                .test-result.fail { border-left-color: #d63638; }
                .test-result.warning { border-left-color: #dba617; }
                .test-result h3 { margin: 0 0 10px 0; }
                .test-result ul { margin: 10px 0; padding-left: 20px; }
                .timestamp { color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <h1>🔍 MAS V2 Emergency Fix Diagnostic Report</h1>
            <p class="timestamp">Generated: <?php echo esc_html($results['timestamp']); ?></p>
            
            <div class="summary">
                <div class="summary-card">
                    <h3>Total Tests</h3>
                    <div class="count"><?php echo $results['summary']['total']; ?></div>
                </div>
                <div class="summary-card" style="color: #00a32a;">
                    <h3>Passed</h3>
                    <div class="count"><?php echo $results['summary']['passed']; ?></div>
                </div>
                <div class="summary-card" style="color: #d63638;">
                    <h3>Failed</h3>
                    <div class="count"><?php echo $results['summary']['failed']; ?></div>
                </div>
                <div class="summary-card" style="color: #dba617;">
                    <h3>Warnings</h3>
                    <div class="count"><?php echo $results['summary']['warnings']; ?></div>
                </div>
            </div>
            
            <?php foreach ($results['tests'] as $test): ?>
            <div class="test-result <?php echo esc_attr($test['status']); ?>">
                <h3><?php echo esc_html($test['name']); ?></h3>
                <p><strong><?php echo esc_html($test['message']); ?></strong></p>
                <?php if (!empty($test['details'])): ?>
                <ul>
                    <?php foreach ($test['details'] as $detail): ?>
                    <li><?php echo esc_html($detail); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
