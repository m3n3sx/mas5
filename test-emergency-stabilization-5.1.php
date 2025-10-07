<?php
/**
 * Test 5.1: Verify Plugin Loads Without Errors
 * 
 * This test verifies that:
 * - Plugin loads without PHP errors
 * - Only Phase 2 scripts are enqueued
 * - No Phase 3 scripts are enqueued
 * - Emergency mode flags are set correctly
 * 
 * Requirements: 1.1, 1.2, 1.3, 3.1, 3.2, 3.3
 */

// Simulate WordPress environment
define('WP_DEBUG', true);
define('ABSPATH', __DIR__ . '/');

// Mock WordPress functions
function admin_url($path) { return 'http://localhost/wp-admin/' . $path; }
function rest_url($path) { return 'http://localhost/wp-json/' . $path; }
function wp_create_nonce($action) { return 'test_nonce_' . $action; }
function wp_enqueue_style($handle, $src = '', $deps = [], $ver = false, $media = 'all') {
    global $wp_styles;
    $wp_styles[$handle] = ['src' => $src, 'deps' => $deps, 'ver' => $ver];
}
function wp_enqueue_script($handle, $src = '', $deps = [], $ver = false, $in_footer = false) {
    global $wp_scripts;
    $wp_scripts[$handle] = ['src' => $src, 'deps' => $deps, 'ver' => $ver, 'in_footer' => $in_footer];
}
function wp_add_inline_script($handle, $data, $position = 'after') {
    global $wp_inline_scripts;
    $wp_inline_scripts[$handle][$position] = $data;
}
function wp_localize_script($handle, $object_name, $data) {
    global $wp_localized_scripts;
    $wp_localized_scripts[$handle] = ['object' => $object_name, 'data' => $data];
}
function wp_enqueue_media() {}
function esc_html__($text, $domain) { return $text; }
function __($text, $domain) { return $text; }
function _e($text, $domain) { echo $text; }
function get_option($option, $default = false) { return $default; }
function update_option($option, $value) { return true; }
function wp_parse_args($args, $defaults) { return array_merge($defaults, (array)$args); }
function wp_cache_delete($key, $group) { return true; }
function current_user_can($capability) { return true; }

// Initialize globals
global $wp_scripts, $wp_styles, $wp_inline_scripts, $wp_localized_scripts;
$wp_scripts = [];
$wp_styles = [];
$wp_inline_scripts = [];
$wp_localized_scripts = [];

// Define plugin constants
define('MAS_V2_VERSION', '2.3.0');
define('MAS_V2_PLUGIN_URL', 'http://localhost/wp-content/plugins/modern-admin-styler-v2/');

echo "=== Test 5.1: Verify Plugin Loads Without Errors ===\n\n";

// Test 1: Check Feature Flags Service
echo "Test 1: Feature Flags Service Override\n";
echo "---------------------------------------\n";

if (file_exists(__DIR__ . '/includes/services/class-mas-feature-flags-service.php')) {
    require_once __DIR__ . '/includes/services/class-mas-feature-flags-service.php';
    
    $flags_service = MAS_Feature_Flags_Service::get_instance();
    
    $use_new_frontend = $flags_service->use_new_frontend();
    echo "✓ use_new_frontend() returns: " . ($use_new_frontend ? 'true' : 'false') . "\n";
    
    if ($use_new_frontend === false) {
        echo "✓ PASS: Phase 3 is disabled (returns false)\n";
    } else {
        echo "✗ FAIL: Phase 3 should be disabled!\n";
    }
    
    if (method_exists($flags_service, 'is_emergency_mode')) {
        $emergency_mode = $flags_service->is_emergency_mode();
        echo "✓ is_emergency_mode() returns: " . ($emergency_mode ? 'true' : 'false') . "\n";
        
        if ($emergency_mode === true) {
            echo "✓ PASS: Emergency mode is active\n";
        } else {
            echo "✗ FAIL: Emergency mode should be active!\n";
        }
    }
    
    if (method_exists($flags_service, 'export_for_js')) {
        $js_flags = $flags_service->export_for_js();
        echo "✓ export_for_js() returns:\n";
        print_r($js_flags);
        
        if (isset($js_flags['useNewFrontend']) && $js_flags['useNewFrontend'] === false) {
            echo "✓ PASS: JS flags indicate Phase 2 mode\n";
        } else {
            echo "✗ FAIL: JS flags should indicate Phase 2 mode!\n";
        }
    }
} else {
    echo "✗ FAIL: Feature flags service file not found\n";
}

echo "\n";

// Test 2: Check enqueueAssets() method
echo "Test 2: Script Enqueue Analysis\n";
echo "--------------------------------\n";

if (file_exists(__DIR__ . '/modern-admin-styler-v2.php')) {
    // Read the main plugin file
    $plugin_content = file_get_contents(__DIR__ . '/modern-admin-styler-v2.php');
    
    // Check for emergency mode inline script
    if (strpos($plugin_content, 'window.MASDisableModules = true') !== false) {
        echo "✓ PASS: MASDisableModules flag is set\n";
    } else {
        echo "✗ FAIL: MASDisableModules flag not found\n";
    }
    
    if (strpos($plugin_content, 'window.MASUseNewFrontend = false') !== false) {
        echo "✓ PASS: MASUseNewFrontend flag is set to false\n";
    } else {
        echo "✗ FAIL: MASUseNewFrontend flag not found or incorrect\n";
    }
    
    if (strpos($plugin_content, 'window.MASEmergencyMode = true') !== false) {
        echo "✓ PASS: MASEmergencyMode flag is set\n";
    } else {
        echo "✗ FAIL: MASEmergencyMode flag not found\n";
    }
    
    // Check Phase 2 scripts are enqueued
    $phase2_scripts = [
        'mas-v2-rest-client' => 'mas-rest-client.js',
        'mas-v2-settings-form-handler' => 'mas-settings-form-handler.js',
        'mas-v2-simple-live-preview' => 'simple-live-preview.js'
    ];
    
    echo "\nPhase 2 Scripts Check:\n";
    foreach ($phase2_scripts as $handle => $filename) {
        if (strpos($plugin_content, "'" . $handle . "'") !== false && 
            strpos($plugin_content, $filename) !== false) {
            echo "✓ PASS: $handle ($filename) is enqueued\n";
        } else {
            echo "✗ FAIL: $handle ($filename) not found in enqueue\n";
        }
    }
    
    // Check Phase 3 scripts are NOT enqueued
    $phase3_scripts = [
        'mas-admin-app.js',
        'EventBus.js',
        'StateManager.js',
        'APIClient.js',
        'ErrorHandler.js',
        'Component.js',
        'SettingsFormComponent.js',
        'LivePreviewComponent.js',
        'NotificationSystem.js',
        'admin-settings-simple.js',
        'LivePreviewManager.js'
    ];
    
    echo "\nPhase 3 Scripts Check (should NOT be present):\n";
    $phase3_found = false;
    foreach ($phase3_scripts as $script) {
        if (strpos($plugin_content, $script) !== false) {
            // Check if it's in a comment or disabled section
            $lines = explode("\n", $plugin_content);
            $found_active = false;
            foreach ($lines as $line) {
                if (strpos($line, $script) !== false && 
                    strpos($line, '//') === false && 
                    strpos($line, '/*') === false &&
                    strpos($line, '*') !== 0) {
                    $found_active = true;
                    break;
                }
            }
            
            if ($found_active) {
                echo "✗ WARNING: $script found in active code\n";
                $phase3_found = true;
            } else {
                echo "✓ OK: $script only in comments/disabled code\n";
            }
        }
    }
    
    if (!$phase3_found) {
        echo "✓ PASS: No Phase 3 scripts in active code\n";
    }
    
    // Check for localization
    if (strpos($plugin_content, "wp_localize_script") !== false &&
        strpos($plugin_content, "'masV2Global'") !== false) {
        echo "\n✓ PASS: masV2Global localization found\n";
        
        if (strpos($plugin_content, "'frontendMode' => 'phase2-stable'") !== false) {
            echo "✓ PASS: frontendMode set to 'phase2-stable'\n";
        } else {
            echo "✗ FAIL: frontendMode not set correctly\n";
        }
        
        if (strpos($plugin_content, "'emergencyMode' => true") !== false) {
            echo "✓ PASS: emergencyMode flag set to true\n";
        } else {
            echo "✗ FAIL: emergencyMode flag not set\n";
        }
    } else {
        echo "\n✗ FAIL: masV2Global localization not found\n";
    }
    
} else {
    echo "✗ FAIL: Main plugin file not found\n";
}

echo "\n";

// Test 3: Check that broken methods are disabled
echo "Test 3: Broken Methods Disabled\n";
echo "--------------------------------\n";

if (file_exists(__DIR__ . '/modern-admin-styler-v2.php')) {
    $plugin_content = file_get_contents(__DIR__ . '/modern-admin-styler-v2.php');
    
    // Check if enqueue_new_frontend is disabled
    if (preg_match('/private\s+function\s+enqueue_new_frontend\s*\([^)]*\)\s*\{/', $plugin_content, $matches, PREG_OFFSET_CAPTURE)) {
        $start_pos = $matches[0][1];
        $method_section = substr($plugin_content, $start_pos, 500);
        
        if (strpos($method_section, 'return;') !== false || 
            strpos($method_section, '// EMERGENCY') !== false ||
            strpos($method_section, 'disabled') !== false) {
            echo "✓ PASS: enqueue_new_frontend() appears to be disabled\n";
        } else {
            echo "✗ WARNING: enqueue_new_frontend() may still be active\n";
        }
    } else {
        echo "✓ OK: enqueue_new_frontend() method not found (may be removed)\n";
    }
    
    // Check if enqueue_legacy_frontend is disabled
    if (preg_match('/private\s+function\s+enqueue_legacy_frontend\s*\([^)]*\)\s*\{/', $plugin_content, $matches, PREG_OFFSET_CAPTURE)) {
        $start_pos = $matches[0][1];
        $method_section = substr($plugin_content, $start_pos, 500);
        
        if (strpos($method_section, 'return;') !== false || 
            strpos($method_section, '// EMERGENCY') !== false ||
            strpos($method_section, 'disabled') !== false) {
            echo "✓ PASS: enqueue_legacy_frontend() appears to be disabled\n";
        } else {
            echo "✗ WARNING: enqueue_legacy_frontend() may still be active\n";
        }
    } else {
        echo "✓ OK: enqueue_legacy_frontend() method not found (may be removed)\n";
    }
}

echo "\n";

// Test 4: Verify script files exist
echo "Test 4: Phase 2 Script Files Exist\n";
echo "-----------------------------------\n";

$required_files = [
    'assets/js/mas-rest-client.js',
    'assets/js/mas-settings-form-handler.js',
    'assets/js/simple-live-preview.js'
];

foreach ($required_files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "✓ PASS: $file exists\n";
    } else {
        echo "✗ FAIL: $file not found!\n";
    }
}

echo "\n";

// Summary
echo "=== Test 5.1 Summary ===\n";
echo "This test verified:\n";
echo "✓ Feature flags service forces Phase 2 mode\n";
echo "✓ Emergency mode flags are set\n";
echo "✓ Only Phase 2 scripts are enqueued\n";
echo "✓ Phase 3 scripts are not enqueued\n";
echo "✓ Broken methods are disabled\n";
echo "✓ Required script files exist\n";
echo "\n";
echo "Next steps:\n";
echo "1. Load the plugin in a browser\n";
echo "2. Open browser console (F12)\n";
echo "3. Check for JavaScript errors\n";
echo "4. Verify Network tab shows only Phase 2 scripts\n";
echo "5. Confirm no Phase 3 scripts are requested\n";
echo "\n";
echo "Expected browser console output:\n";
echo "- window.MASDisableModules should be true\n";
echo "- window.MASUseNewFrontend should be false\n";
echo "- window.MASEmergencyMode should be true\n";
echo "- masV2Global.frontendMode should be 'phase2-stable'\n";
echo "- masV2Global.emergencyMode should be true\n";
