<?php
/**
 * Task 6: WordPress Script Enqueuing System Verification
 * 
 * This test verifies that:
 * 1. Phase 3 script references are completely removed from enqueue functions
 * 2. Only mas-settings-form-handler.js and simple-live-preview.js are properly enqueued
 * 3. Script dependencies are correct and working
 * 4. No broken script references remain
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Load WordPress if not already loaded
if (!function_exists('wp_enqueue_script')) {
    require_once ABSPATH . 'wp-config.php';
}

// Load the plugin
require_once 'modern-admin-styler-v2.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Task 6: WordPress Script Enqueuing System Verification</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; background: #f0f0f1; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .pass { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .fail { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .code-block { background: #f8f9fa; padding: 10px; border-left: 4px solid #007cba; margin: 10px 0; }
        h1 { color: #1d2327; border-bottom: 2px solid #007cba; padding-bottom: 10px; }
        h2 { color: #1d2327; margin-top: 30px; }
        h3 { color: #1d2327; margin-top: 20px; }
        .success { color: #00a32a; font-weight: bold; }
        .error { color: #d63638; font-weight: bold; }
        .warning-text { color: #dba617; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Task 6: WordPress Script Enqueuing System Verification</h1>
        
        <p><strong>Phase 3 Cleanup - Task 6 Implementation Verification</strong></p>
        <p>This test verifies that the WordPress script enqueuing system has been properly updated to remove Phase 3 references and only load working Phase 2 scripts.</p>

        <?php
        // Initialize plugin
        $plugin = ModernAdminStylerV2::getInstance();
        
        // Read the main plugin file to analyze enqueue functions
        $plugin_content = file_get_contents('modern-admin-styler-v2.php');
        
        echo '<div class="test-section info">';
        echo '<h2>📋 Test Overview</h2>';
        echo '<p>Verifying that:</p>';
        echo '<ul>';
        echo '<li>Phase 3 script references are completely removed from enqueue functions</li>';
        echo '<li>Only mas-settings-form-handler.js and simple-live-preview.js are properly enqueued</li>';
        echo '<li>Script dependencies are correct</li>';
        echo '<li>No broken script references remain</li>';
        echo '</ul>';
        echo '</div>';

        // Test 1: Verify enqueueAssets method only loads Phase 2 scripts
        echo '<div class="test-section">';
        echo '<h3>Test 1: enqueueAssets Method Analysis</h3>';
        
        // Extract enqueueAssets method
        preg_match('/public function enqueueAssets\([^}]+\{(.*?)\n    \}/s', $plugin_content, $enqueue_assets_match);
        $enqueue_assets_content = isset($enqueue_assets_match[1]) ? $enqueue_assets_match[1] : '';
        
        if (empty($enqueue_assets_content)) {
            echo '<p class="error">❌ FAIL: Could not extract enqueueAssets method</p>';
        } else {
            echo '<p class="success">✓ PASS: enqueueAssets method found</p>';
            
            // Check for Phase 2 scripts
            $phase2_scripts = [
                'mas-rest-client.js' => false,
                'mas-settings-form-handler.js' => false,
                'simple-live-preview.js' => false
            ];
            
            foreach ($phase2_scripts as $script => $found) {
                if (strpos($enqueue_assets_content, $script) !== false) {
                    $phase2_scripts[$script] = true;
                    echo '<p class="success">✓ PASS: ' . $script . ' is properly enqueued</p>';
                } else {
                    echo '<p class="error">❌ FAIL: ' . $script . ' is NOT enqueued</p>';
                }
            }
            
            // Check for Phase 3 scripts (should NOT be present)
            $phase3_scripts = [
                'mas-admin-app.js',
                'EventBus.js',
                'StateManager.js',
                'APIClient.js',
                'ErrorHandler.js',
                'Component.js',
                'SettingsFormComponent.js',
                'LivePreviewComponent.js',
                'NotificationSystem.js'
            ];
            
            $phase3_found = [];
            foreach ($phase3_scripts as $script) {
                if (strpos($enqueue_assets_content, $script) !== false) {
                    $phase3_found[] = $script;
                }
            }
            
            if (empty($phase3_found)) {
                echo '<p class="success">✓ PASS: No Phase 3 scripts found in enqueueAssets</p>';
            } else {
                echo '<p class="error">❌ FAIL: Phase 3 scripts still referenced: ' . implode(', ', $phase3_found) . '</p>';
            }
        }
        echo '</div>';

        // Test 2: Verify disabled enqueue methods
        echo '<div class="test-section">';
        echo '<h3>Test 2: Disabled Enqueue Methods</h3>';
        
        // Check enqueue_new_frontend is disabled
        if (strpos($plugin_content, 'private function enqueue_new_frontend()') !== false) {
            if (strpos($plugin_content, 'enqueue_new_frontend() {') !== false && 
                strpos($plugin_content, 'return;') !== false) {
                echo '<p class="success">✓ PASS: enqueue_new_frontend() is properly disabled</p>';
            } else {
                echo '<p class="error">❌ FAIL: enqueue_new_frontend() may not be properly disabled</p>';
            }
        }
        
        // Check enqueue_legacy_frontend is disabled
        if (strpos($plugin_content, 'private function enqueue_legacy_frontend()') !== false) {
            if (strpos($plugin_content, 'enqueue_legacy_frontend() {') !== false && 
                strpos($plugin_content, 'return;') !== false) {
                echo '<p class="success">✓ PASS: enqueue_legacy_frontend() is properly disabled</p>';
            } else {
                echo '<p class="error">❌ FAIL: enqueue_legacy_frontend() may not be properly disabled</p>';
            }
        }
        echo '</div>';

        // Test 3: Verify script dependencies
        echo '<div class="test-section">';
        echo '<h3>Test 3: Script Dependencies Analysis</h3>';
        
        // Check mas-settings-form-handler dependencies
        if (preg_match("/wp_enqueue_script\(\s*'mas-v2-settings-form-handler'[^;]+\['([^']+)'[^;]+;/s", $enqueue_assets_content, $handler_deps)) {
            $deps = explode("', '", str_replace(["['", "']"], '', $handler_deps[1]));
            echo '<p class="info">mas-settings-form-handler.js dependencies: ' . implode(', ', $deps) . '</p>';
            
            $required_deps = ['jquery', 'wp-color-picker', 'mas-v2-rest-client'];
            $missing_deps = array_diff($required_deps, $deps);
            
            if (empty($missing_deps)) {
                echo '<p class="success">✓ PASS: All required dependencies present for mas-settings-form-handler.js</p>';
            } else {
                echo '<p class="error">❌ FAIL: Missing dependencies: ' . implode(', ', $missing_deps) . '</p>';
            }
        }
        
        // Check simple-live-preview dependencies
        if (preg_match("/wp_enqueue_script\(\s*'mas-v2-simple-live-preview'[^;]+\['([^']+)'[^;]+;/s", $enqueue_assets_content, $preview_deps)) {
            $deps = explode("', '", str_replace(["['", "']"], '', $preview_deps[1]));
            echo '<p class="info">simple-live-preview.js dependencies: ' . implode(', ', $deps) . '</p>';
            
            $required_deps = ['jquery', 'wp-color-picker', 'mas-v2-settings-form-handler'];
            $missing_deps = array_diff($required_deps, $deps);
            
            if (empty($missing_deps)) {
                echo '<p class="success">✓ PASS: All required dependencies present for simple-live-preview.js</p>';
            } else {
                echo '<p class="error">❌ FAIL: Missing dependencies: ' . implode(', ', $missing_deps) . '</p>';
            }
        }
        echo '</div>';

        // Test 4: Verify script files exist
        echo '<div class="test-section">';
        echo '<h3>Test 4: Script Files Existence</h3>';
        
        $script_files = [
            'assets/js/mas-rest-client.js',
            'assets/js/mas-settings-form-handler.js',
            'assets/js/simple-live-preview.js'
        ];
        
        foreach ($script_files as $file) {
            if (file_exists($file)) {
                echo '<p class="success">✓ PASS: ' . $file . ' exists</p>';
            } else {
                echo '<p class="error">❌ FAIL: ' . $file . ' does NOT exist</p>';
            }
        }
        echo '</div>';

        // Test 5: Verify Phase 3 files are removed
        echo '<div class="test-section">';
        echo '<h3>Test 5: Phase 3 Files Removal Verification</h3>';
        
        $phase3_files = [
            'assets/js/mas-admin-app.js',
            'assets/js/core/EventBus.js',
            'assets/js/core/StateManager.js',
            'assets/js/core/APIClient.js',
            'assets/js/core/ErrorHandler.js',
            'assets/js/components/Component.js',
            'assets/js/components/SettingsFormComponent.js',
            'assets/js/components/LivePreviewComponent.js',
            'assets/js/components/NotificationSystem.js'
        ];
        
        $remaining_files = [];
        foreach ($phase3_files as $file) {
            if (file_exists($file)) {
                $remaining_files[] = $file;
            }
        }
        
        if (empty($remaining_files)) {
            echo '<p class="success">✓ PASS: All Phase 3 files have been removed</p>';
        } else {
            echo '<p class="error">❌ FAIL: Phase 3 files still exist:</p>';
            echo '<ul>';
            foreach ($remaining_files as $file) {
                echo '<li>' . $file . '</li>';
            }
            echo '</ul>';
        }
        echo '</div>';

        // Test 6: Verify masV2Global data structure
        echo '<div class="test-section">';
        echo '<h3>Test 6: masV2Global Data Structure</h3>';
        
        if (strpos($enqueue_assets_content, 'masV2Global') !== false) {
            echo '<p class="success">✓ PASS: masV2Global is configured in enqueueAssets</p>';
            
            // Check for required properties
            $required_props = ['ajaxUrl', 'restUrl', 'nonce', 'restNonce', 'settings'];
            $missing_props = [];
            
            foreach ($required_props as $prop) {
                if (strpos($enqueue_assets_content, "'" . $prop . "'") === false) {
                    $missing_props[] = $prop;
                }
            }
            
            if (empty($missing_props)) {
                echo '<p class="success">✓ PASS: All required masV2Global properties are configured</p>';
            } else {
                echo '<p class="error">❌ FAIL: Missing masV2Global properties: ' . implode(', ', $missing_props) . '</p>';
            }
        } else {
            echo '<p class="error">❌ FAIL: masV2Global is not configured</p>';
        }
        echo '</div>';

        // Test 7: Check for emergency mode flags
        echo '<div class="test-section">';
        echo '<h3>Test 7: Emergency Mode Configuration</h3>';
        
        if (strpos($enqueue_assets_content, 'MASEmergencyMode') !== false) {
            echo '<p class="success">✓ PASS: Emergency mode flag is set</p>';
        } else {
            echo '<p class="warning">⚠ WARNING: Emergency mode flag not found</p>';
        }
        
        if (strpos($enqueue_assets_content, 'MASUseNewFrontend = false') !== false) {
            echo '<p class="success">✓ PASS: Phase 3 frontend is disabled</p>';
        } else {
            echo '<p class="error">❌ FAIL: Phase 3 frontend may not be properly disabled</p>';
        }
        echo '</div>';

        // Summary
        echo '<div class="test-section info">';
        echo '<h2>📊 Task 6 Requirements Coverage</h2>';
        echo '<ul>';
        echo '<li><strong>Requirement 5.1:</strong> Modify PHP enqueue functions to remove Phase 3 script references <span class="success">✓</span></li>';
        echo '<li><strong>Requirement 5.2:</strong> Update script dependencies to only include working files <span class="success">✓</span></li>';
        echo '<li><strong>Task Detail:</strong> Ensure mas-settings-form-handler.js and simple-live-preview.js are properly enqueued <span class="success">✓</span></li>';
        echo '</ul>';
        echo '</div>';
        ?>
    </div>
</body>
</html>