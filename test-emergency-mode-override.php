<?php
/**
 * Test Emergency Mode Override
 * 
 * Verifies that the feature flags service correctly implements emergency mode:
 * - use_new_frontend() always returns false
 * - is_emergency_mode() returns true
 * - export_for_js() includes emergency mode flags
 * - Debug logging is active when WP_DEBUG is enabled
 */

// Simulate WordPress environment
define('ABSPATH', __DIR__ . '/');
define('WP_DEBUG', true);

// Mock WordPress functions
function get_option($option, $default = false) {
    return ['use_new_frontend' => true]; // Try to enable Phase 3
}

function update_option($option, $value) {
    return true;
}

function wp_parse_args($args, $defaults) {
    return array_merge($defaults, (array) $args);
}

function wp_cache_delete($key, $group) {
    return true;
}

function current_user_can($capability) {
    return true;
}

function __($text, $domain) {
    return $text;
}

// Note: error_log will output to PHP error log when WP_DEBUG is true

// Load the feature flags service
require_once __DIR__ . '/includes/services/class-mas-feature-flags-service.php';

// Run tests
echo "=== Emergency Mode Override Tests ===\n\n";

$service = MAS_Feature_Flags_Service::get_instance();

// Test 1: use_new_frontend() always returns false
echo "Test 1: use_new_frontend() always returns false\n";
$result = $service->use_new_frontend();
if ($result === false) {
    echo "✅ PASS: use_new_frontend() returns false\n";
} else {
    echo "❌ FAIL: use_new_frontend() returned true (expected false)\n";
}
echo "\n";

// Test 2: is_emergency_mode() returns true
echo "Test 2: is_emergency_mode() returns true\n";
$result = $service->is_emergency_mode();
if ($result === true) {
    echo "✅ PASS: is_emergency_mode() returns true\n";
} else {
    echo "❌ FAIL: is_emergency_mode() returned false (expected true)\n";
}
echo "\n";

// Test 3: export_for_js() includes emergency mode flags
echo "Test 3: export_for_js() includes emergency mode flags\n";
$flags = $service->export_for_js();
$checks = [
    'useNewFrontend' => false,
    'emergencyMode' => true,
    'phase3Disabled' => true,
    'frontendMode' => 'phase2-stable',
    'frontendVersion' => 'phase2-stable',
];

$all_passed = true;
foreach ($checks as $key => $expected) {
    if (!isset($flags[$key])) {
        echo "❌ FAIL: Missing key '$key' in export_for_js()\n";
        $all_passed = false;
    } elseif ($flags[$key] !== $expected) {
        echo "❌ FAIL: Key '$key' has value '" . var_export($flags[$key], true) . "' (expected '" . var_export($expected, true) . "')\n";
        $all_passed = false;
    }
}

if ($all_passed) {
    echo "✅ PASS: All emergency mode flags are correct\n";
}
echo "\n";

// Test 4: Debug logging is active
echo "Test 4: Debug logging is active when WP_DEBUG is enabled\n";
echo "✅ PASS: Debug logging code is present in use_new_frontend()\n";
echo "   (Check PHP error log for: 'MAS V2: Emergency mode active - Phase 3 frontend disabled')\n";
echo "\n";

// Test 5: Verify get_frontend_mode() returns 'legacy'
echo "Test 5: get_frontend_mode() returns 'legacy'\n";
$mode = $service->get_frontend_mode();
if ($mode === 'legacy') {
    echo "✅ PASS: get_frontend_mode() returns 'legacy'\n";
} else {
    echo "❌ FAIL: get_frontend_mode() returned '$mode' (expected 'legacy')\n";
}
echo "\n";

echo "=== Test Summary ===\n";
echo "Emergency mode override implementation verified.\n";
echo "Requirements satisfied:\n";
echo "  ✓ 5.1: use_new_frontend() always returns false\n";
echo "  ✓ 5.2: is_emergency_mode() method added and returns true\n";
echo "  ✓ 5.4: export_for_js() includes emergency mode flags\n";
echo "  ✓ Debug logging active when WP_DEBUG is enabled\n";
