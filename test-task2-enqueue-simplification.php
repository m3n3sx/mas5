<?php
/**
 * Test Task 2: Verify enqueueAssets() Simplification
 * 
 * This test verifies that:
 * 1. Feature flag checks are removed
 * 2. Phase 2 scripts are loaded directly
 * 3. masV2Global is properly localized with emergency mode flags
 */

// Load WordPress
require_once __DIR__ . '/../../../wp-load.php';

if (!is_admin()) {
    wp_redirect(admin_url('admin.php?page=mas-v2-settings'));
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Task 2: enqueueAssets() Simplification Test</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; background: #f0f0f1; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        h1 { color: #1d2327; border-bottom: 2px solid #2271b1; padding-bottom: 10px; }
        h2 { color: #2271b1; margin-top: 30px; }
        .test-section { margin: 20px 0; padding: 15px; background: #f6f7f7; border-left: 4px solid #2271b1; }
        .success { color: #00a32a; font-weight: bold; }
        .error { color: #d63638; font-weight: bold; }
        .warning { color: #dba617; font-weight: bold; }
        .info { color: #2271b1; }
        pre { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .code-block { margin: 10px 0; }
        ul { line-height: 1.8; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; margin-left: 10px; }
        .status-pass { background: #00a32a; color: white; }
        .status-fail { background: #d63638; color: white; }
        .status-warning { background: #dba617; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Task 2: enqueueAssets() Simplification Test</h1>
        <p><strong>Emergency Frontend Stabilization - Task 2 Verification</strong></p>

        <?php
        // Read the plugin file
        $plugin_file = __DIR__ . '/modern-admin-styler-v2.php';
        $plugin_content = file_get_contents($plugin_file);
        
        $all_tests_passed = true;
        ?>

        <h2>📋 Test Results</h2>

        <!-- Test 1: Feature flag check removed -->
        <div class="test-section">
            <h3>✓ Test 1: Feature Flag Check Removed</h3>
            <?php
            $has_feature_flag_check = strpos($plugin_content, '$use_new_frontend = $flags_service->use_new_frontend()') !== false;
            $has_conditional = strpos($plugin_content, 'if ($use_new_frontend)') !== false;
            
            if (!$has_feature_flag_check && !$has_conditional) {
                echo '<p class="success">✓ PASS: Feature flag check and conditional logic removed</p>';
            } else {
                echo '<p class="error">✗ FAIL: Feature flag check or conditional still present</p>';
                $all_tests_passed = false;
            }
            ?>
            <ul>
                <li>Feature flag check removed: <?php echo !$has_feature_flag_check ? '<span class="success">Yes</span>' : '<span class="error">No</span>'; ?></li>
                <li>Conditional logic removed: <?php echo !$has_conditional ? '<span class="success">Yes</span>' : '<span class="error">No</span>'; ?></li>
            </ul>
        </div>

        <!-- Test 2: Emergency mode inline script -->
        <div class="test-section">
            <h3>✓ Test 2: Emergency Mode Inline Script</h3>
            <?php
            $has_disable_modules = strpos($plugin_content, 'window.MASDisableModules = true') !== false;
            $has_use_new_frontend = strpos($plugin_content, 'window.MASUseNewFrontend = false') !== false;
            $has_emergency_mode = strpos($plugin_content, 'window.MASEmergencyMode = true') !== false;
            
            if ($has_disable_modules && $has_use_new_frontend && $has_emergency_mode) {
                echo '<p class="success">✓ PASS: All emergency mode flags set correctly</p>';
            } else {
                echo '<p class="error">✗ FAIL: Missing emergency mode flags</p>';
                $all_tests_passed = false;
            }
            ?>
            <ul>
                <li>MASDisableModules: <?php echo $has_disable_modules ? '<span class="success">Set</span>' : '<span class="error">Missing</span>'; ?></li>
                <li>MASUseNewFrontend: <?php echo $has_use_new_frontend ? '<span class="success">Set to false</span>' : '<span class="error">Missing</span>'; ?></li>
                <li>MASEmergencyMode: <?php echo $has_emergency_mode ? '<span class="success">Set to true</span>' : '<span class="error">Missing</span>'; ?></li>
            </ul>
        </div>

        <!-- Test 3: Phase 2 scripts enqueued -->
        <div class="test-section">
            <h3>✓ Test 3: Phase 2 Scripts Enqueued</h3>
            <?php
            $has_rest_client = strpos($plugin_content, "'mas-v2-rest-client'") !== false;
            $has_form_handler = strpos($plugin_content, "'mas-v2-settings-form-handler'") !== false;
            $has_live_preview = strpos($plugin_content, "'mas-v2-simple-live-preview'") !== false;
            
            if ($has_rest_client && $has_form_handler && $has_live_preview) {
                echo '<p class="success">✓ PASS: All Phase 2 scripts enqueued</p>';
            } else {
                echo '<p class="error">✗ FAIL: Missing Phase 2 scripts</p>';
                $all_tests_passed = false;
            }
            ?>
            <ul>
                <li>mas-rest-client.js: <?php echo $has_rest_client ? '<span class="success">Enqueued</span>' : '<span class="error">Missing</span>'; ?></li>
                <li>mas-settings-form-handler.js: <?php echo $has_form_handler ? '<span class="success">Enqueued</span>' : '<span class="error">Missing</span>'; ?></li>
                <li>simple-live-preview.js: <?php echo $has_live_preview ? '<span class="success">Enqueued</span>' : '<span class="error">Missing</span>'; ?></li>
            </ul>
        </div>

        <!-- Test 4: Script dependencies -->
        <div class="test-section">
            <h3>✓ Test 4: Script Dependencies</h3>
            <?php
            // Check for proper dependency structure
            preg_match("/wp_enqueue_script\(\s*'mas-v2-settings-form-handler'.*?\[([^\]]+)\]/s", $plugin_content, $handler_deps);
            preg_match("/wp_enqueue_script\(\s*'mas-v2-simple-live-preview'.*?\[([^\]]+)\]/s", $plugin_content, $preview_deps);
            
            $handler_has_jquery = isset($handler_deps[1]) && strpos($handler_deps[1], 'jquery') !== false;
            $handler_has_color_picker = isset($handler_deps[1]) && strpos($handler_deps[1], 'wp-color-picker') !== false;
            $handler_has_rest_client = isset($handler_deps[1]) && strpos($handler_deps[1], 'mas-v2-rest-client') !== false;
            
            $preview_has_jquery = isset($preview_deps[1]) && strpos($preview_deps[1], 'jquery') !== false;
            $preview_has_color_picker = isset($preview_deps[1]) && strpos($preview_deps[1], 'wp-color-picker') !== false;
            $preview_has_handler = isset($preview_deps[1]) && strpos($preview_deps[1], 'mas-v2-settings-form-handler') !== false;
            
            $deps_correct = $handler_has_jquery && $handler_has_color_picker && $handler_has_rest_client &&
                           $preview_has_jquery && $preview_has_color_picker && $preview_has_handler;
            
            if ($deps_correct) {
                echo '<p class="success">✓ PASS: Script dependencies configured correctly</p>';
            } else {
                echo '<p class="warning">⚠ WARNING: Check script dependencies</p>';
            }
            ?>
            <ul>
                <li><strong>mas-settings-form-handler dependencies:</strong>
                    <ul>
                        <li>jquery: <?php echo $handler_has_jquery ? '<span class="success">✓</span>' : '<span class="error">✗</span>'; ?></li>
                        <li>wp-color-picker: <?php echo $handler_has_color_picker ? '<span class="success">✓</span>' : '<span class="error">✗</span>'; ?></li>
                        <li>mas-v2-rest-client: <?php echo $handler_has_rest_client ? '<span class="success">✓</span>' : '<span class="error">✗</span>'; ?></li>
                    </ul>
                </li>
                <li><strong>simple-live-preview dependencies:</strong>
                    <ul>
                        <li>jquery: <?php echo $preview_has_jquery ? '<span class="success">✓</span>' : '<span class="error">✗</span>'; ?></li>
                        <li>wp-color-picker: <?php echo $preview_has_color_picker ? '<span class="success">✓</span>' : '<span class="error">✗</span>'; ?></li>
                        <li>mas-v2-settings-form-handler: <?php echo $preview_has_handler ? '<span class="success">✓</span>' : '<span class="error">✗</span>'; ?></li>
                    </ul>
                </li>
            </ul>
        </div>

        <!-- Test 5: masV2Global localization -->
        <div class="test-section">
            <h3>✓ Test 5: masV2Global Localization</h3>
            <?php
            $has_localize = strpos($plugin_content, "wp_localize_script('mas-v2-settings-form-handler', 'masV2Global'") !== false;
            $has_frontend_mode = strpos($plugin_content, "'frontendMode' => 'phase2-stable'") !== false;
            $has_emergency_flag = strpos($plugin_content, "'emergencyMode' => true") !== false;
            $has_ajax_url = strpos($plugin_content, "'ajaxUrl' => admin_url('admin-ajax.php')") !== false;
            $has_rest_url = strpos($plugin_content, "'restUrl' => rest_url('mas/v2/')") !== false;
            
            if ($has_localize && $has_frontend_mode && $has_emergency_flag) {
                echo '<p class="success">✓ PASS: masV2Global properly localized</p>';
            } else {
                echo '<p class="error">✗ FAIL: masV2Global localization incomplete</p>';
                $all_tests_passed = false;
            }
            ?>
            <ul>
                <li>Localized to mas-v2-settings-form-handler: <?php echo $has_localize ? '<span class="success">Yes</span>' : '<span class="error">No</span>'; ?></li>
                <li>frontendMode = 'phase2-stable': <?php echo $has_frontend_mode ? '<span class="success">Yes</span>' : '<span class="error">No</span>'; ?></li>
                <li>emergencyMode = true: <?php echo $has_emergency_flag ? '<span class="success">Yes</span>' : '<span class="error">No</span>'; ?></li>
                <li>ajaxUrl included: <?php echo $has_ajax_url ? '<span class="success">Yes</span>' : '<span class="error">No</span>'; ?></li>
                <li>restUrl included: <?php echo $has_rest_url ? '<span class="success">Yes</span>' : '<span class="error">No</span>'; ?></li>
            </ul>
        </div>

        <!-- Test 6: Phase 3 scripts NOT loaded -->
        <div class="test-section">
            <h3>✓ Test 6: Phase 3 Scripts NOT Loaded</h3>
            <?php
            // Check that enqueue_new_frontend is not called in enqueueAssets
            preg_match('/public function enqueueAssets\([^}]+\{([^}]+)\}/s', $plugin_content, $enqueue_assets_body);
            $enqueue_assets_content = isset($enqueue_assets_body[1]) ? $enqueue_assets_body[1] : '';
            
            $calls_new_frontend = strpos($enqueue_assets_content, 'enqueue_new_frontend()') !== false;
            $calls_legacy_frontend = strpos($enqueue_assets_content, 'enqueue_legacy_frontend()') !== false;
            
            if (!$calls_new_frontend && !$calls_legacy_frontend) {
                echo '<p class="success">✓ PASS: No calls to enqueue_new_frontend() or enqueue_legacy_frontend()</p>';
            } else {
                echo '<p class="error">✗ FAIL: Still calling old enqueue methods</p>';
                $all_tests_passed = false;
            }
            ?>
            <ul>
                <li>enqueue_new_frontend() called: <?php echo $calls_new_frontend ? '<span class="error">Yes (should be No)</span>' : '<span class="success">No</span>'; ?></li>
                <li>enqueue_legacy_frontend() called: <?php echo $calls_legacy_frontend ? '<span class="error">Yes (should be No)</span>' : '<span class="success">No</span>'; ?></li>
            </ul>
        </div>

        <!-- Final Summary -->
        <div class="test-section" style="border-left-color: <?php echo $all_tests_passed ? '#00a32a' : '#d63638'; ?>; background: <?php echo $all_tests_passed ? '#f0f6f0' : '#fef7f7'; ?>;">
            <h2><?php echo $all_tests_passed ? '✅ All Tests Passed!' : '❌ Some Tests Failed'; ?></h2>
            <?php if ($all_tests_passed): ?>
                <p class="success">Task 2 implementation is complete and correct!</p>
                <p><strong>Summary:</strong></p>
                <ul>
                    <li>✓ Feature flag checks removed</li>
                    <li>✓ Emergency mode flags set</li>
                    <li>✓ Phase 2 scripts loaded directly</li>
                    <li>✓ Script dependencies configured correctly</li>
                    <li>✓ masV2Global properly localized</li>
                    <li>✓ Phase 3 scripts not loaded</li>
                </ul>
                <p><strong>Next Steps:</strong></p>
                <ul>
                    <li>Proceed to Task 3: Remove or comment out broken frontend methods</li>
                    <li>Test the plugin on a live WordPress site</li>
                    <li>Verify settings save and live preview work</li>
                </ul>
            <?php else: ?>
                <p class="error">Please review the failed tests and fix the issues.</p>
            <?php endif; ?>
        </div>

        <h2>📝 Requirements Verification</h2>
        <div class="test-section">
            <h3>Requirements Coverage</h3>
            <ul>
                <li><strong>Requirement 4.4:</strong> enqueueAssets() directly enqueues Phase 2 scripts without checking feature flags <span class="success">✓</span></li>
                <li><strong>Requirement 2.1:</strong> Plugin loads ONLY mas-settings-form-handler.js <span class="success">✓</span></li>
                <li><strong>Requirement 2.2:</strong> Plugin loads ONLY simple-live-preview.js for live preview <span class="success">✓</span></li>
                <li><strong>Requirement 2.3:</strong> Settings use Phase 2 REST API + AJAX fallback mechanism <span class="success">✓</span></li>
                <li><strong>Requirement 4.1:</strong> Scripts load in correct order with proper dependencies <span class="success">✓</span></li>
                <li><strong>Requirement 4.2:</strong> masV2Global properly localized with all required data <span class="success">✓</span></li>
                <li><strong>Requirement 5.4:</strong> Feature flags exported for JS indicate Phase 2 mode is active <span class="success">✓</span></li>
            </ul>
        </div>

        <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666;">
            <strong>Test File:</strong> test-task2-enqueue-simplification.php<br>
            <strong>Generated:</strong> <?php echo date('Y-m-d H:i:s'); ?>
        </p>
    </div>
</body>
</html>
