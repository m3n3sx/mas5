<?php
/**
 * Test Task 4: Feature Flags Admin UI Update
 * 
 * This test verifies that the feature flags admin UI correctly displays
 * the emergency mode notice and disables the Phase 3 toggle control.
 * 
 * Test Coverage:
 * - Emergency mode notice is displayed
 * - Phase 3 toggle is disabled
 * - Proper styling is applied
 * - Quick actions are hidden in emergency mode
 * 
 * @package ModernAdminStylerV2
 * @subpackage Tests
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // For testing outside WordPress, define required constants
    define('ABSPATH', dirname(__FILE__) . '/');
    define('WP_DEBUG', true);
}

echo "=== Task 4: Feature Flags Admin UI Update Test ===\n\n";

// Test 1: Verify file exists and is readable
echo "Test 1: File Existence Check\n";
$file_path = __DIR__ . '/includes/admin/class-mas-feature-flags-admin.php';
if (file_exists($file_path)) {
    echo "✓ Feature flags admin file exists\n";
    $content = file_get_contents($file_path);
    echo "✓ File is readable (" . strlen($content) . " bytes)\n";
} else {
    echo "✗ Feature flags admin file not found\n";
    exit(1);
}

// Test 2: Verify emergency mode notice implementation
echo "\nTest 2: Emergency Mode Notice\n";
$checks = [
    'Emergency Stabilization Mode Active' => false,
    'Broken EventBus' => false,
    'Broken StateManager' => false,
    'Broken APIClient' => false,
    'Handler Conflicts' => false,
    'is_emergency_mode()' => false,
    'notice notice-error' => false,
];

foreach ($checks as $check => $found) {
    if (strpos($content, $check) !== false) {
        echo "✓ Found: $check\n";
        $checks[$check] = true;
    } else {
        echo "✗ Missing: $check\n";
    }
}

// Test 3: Verify Phase 3 toggle disable logic
echo "\nTest 3: Phase 3 Toggle Disable Logic\n";
$toggle_checks = [
    'is_disabled' => false,
    'disabled="disabled"' => false,
    'mas-toggle-disabled' => false,
    'Disabled - Emergency Mode' => false,
];

foreach ($toggle_checks as $check => $found) {
    if (strpos($content, $check) !== false) {
        echo "✓ Found: $check\n";
        $toggle_checks[$check] = true;
    } else {
        echo "✗ Missing: $check\n";
    }
}

// Test 4: Verify disabled styling
echo "\nTest 4: Disabled Toggle Styling\n";
$style_checks = [
    '.mas-toggle-disabled' => false,
    'opacity: 0.5' => false,
    'cursor: not-allowed' => false,
    'background-color: #999' => false,
];

foreach ($style_checks as $check => $found) {
    if (strpos($content, $check) !== false) {
        echo "✓ Found: $check\n";
        $style_checks[$check] = true;
    } else {
        echo "✗ Missing: $check\n";
    }
}

// Test 5: Verify quick actions are hidden in emergency mode
echo "\nTest 5: Quick Actions Hidden in Emergency Mode\n";
if (preg_match('/if\s*\(\s*!\$is_emergency_mode\s*\).*Quick Actions/s', $content)) {
    echo "✓ Quick actions are conditionally hidden in emergency mode\n";
} else {
    echo "✗ Quick actions conditional check not found\n";
}

// Test 6: Verify emergency mode description for use_new_frontend flag
echo "\nTest 6: Emergency Mode Description\n";
$description_checks = [
    'Phase 3 frontend is temporarily disabled' => false,
    'broken dependencies' => false,
    'stable Phase 2 system' => false,
    'toggle will be re-enabled' => false,
];

foreach ($description_checks as $check => $found) {
    if (stripos($content, $check) !== false) {
        echo "✓ Found: $check\n";
        $description_checks[$check] = true;
    } else {
        echo "✗ Missing: $check\n";
    }
}

// Test 7: PHP Syntax Check
echo "\nTest 7: PHP Syntax Validation\n";
$output = [];
$return_var = 0;
exec("php -l " . escapeshellarg($file_path) . " 2>&1", $output, $return_var);
if ($return_var === 0) {
    echo "✓ PHP syntax is valid\n";
} else {
    echo "✗ PHP syntax errors found:\n";
    echo implode("\n", $output) . "\n";
}

// Test 8: Verify conditional rendering logic
echo "\nTest 8: Conditional Rendering Logic\n";
$logic_checks = [
    'Check emergency mode variable' => preg_match('/\$is_emergency_mode\s*=/', $content),
    'Emergency notice conditional' => preg_match('/if\s*\(\s*\$is_emergency_mode\s*\).*Emergency Stabilization/s', $content),
    'Toggle disabled conditional' => preg_match('/\$is_disabled\s*=\s*\$is_emergency_mode/', $content),
    'Flag-specific disable check' => preg_match('/use_new_frontend/', $content),
];

foreach ($logic_checks as $check => $result) {
    if ($result) {
        echo "✓ $check\n";
    } else {
        echo "✗ $check\n";
    }
}

// Summary
echo "\n=== Test Summary ===\n";
$all_checks = array_merge($checks, $toggle_checks, $style_checks, $description_checks, $logic_checks);
$passed = count(array_filter($all_checks));
$total = count($all_checks);
$percentage = round(($passed / $total) * 100, 2);

echo "Passed: $passed / $total ($percentage%)\n";

if ($passed === $total) {
    echo "\n✓ All tests passed! Task 4 implementation is complete.\n";
    exit(0);
} else {
    echo "\n⚠ Some tests failed. Please review the implementation.\n";
    exit(1);
}
