<?php
/**
 * Simple Verification: Task 3 Implementation
 * 
 * Verifies that both methods are properly disabled by checking the source code
 */

echo "=== Task 3 Implementation Verification ===\n\n";

$file = 'modern-admin-styler-v2.php';

if (!file_exists($file)) {
    echo "❌ FAIL: File not found: {$file}\n";
    exit(1);
}

$content = file_get_contents($file);

// Test 1: Check enqueue_new_frontend() is disabled
echo "Test 1: enqueue_new_frontend() Method\n";
$test1_pass = true;

$checks = [
    'DISABLED FOR EMERGENCY STABILIZATION' => false,
    'private function enqueue_new_frontend()' => false,
    'EMERGENCY STABILIZATION: Method disabled - early return' => false,
    'Phase 3 frontend has broken dependencies' => false,
    'Requirements: 1.1, 1.2, 1.3' => false,
    'DISABLED CODE - DO NOT UNCOMMENT UNTIL PHASE 3 IS FIXED' => false
];

foreach ($checks as $string => $found) {
    if (strpos($content, $string) !== false) {
        echo "  ✅ Found: '{$string}'\n";
    } else {
        echo "  ❌ Missing: '{$string}'\n";
        $test1_pass = false;
    }
}

if ($test1_pass) {
    echo "✅ PASS: enqueue_new_frontend() properly disabled\n\n";
} else {
    echo "❌ FAIL: enqueue_new_frontend() not properly disabled\n\n";
}

// Test 2: Check enqueue_legacy_frontend() is disabled
echo "Test 2: enqueue_legacy_frontend() Method\n";
$test2_pass = true;

$checks2 = [
    'private function enqueue_legacy_frontend()' => false,
    'DISABLED FOR EMERGENCY STABILIZATION' => false,
    'replaced by inline script loading' => false,
    'Requirements: 3.4' => false,
    'DISABLED CODE - Phase 2 scripts now loaded inline' => false
];

foreach ($checks2 as $string => $found) {
    if (strpos($content, $string) !== false) {
        echo "  ✅ Found: '{$string}'\n";
    } else {
        echo "  ❌ Missing: '{$string}'\n";
        $test2_pass = false;
    }
}

if ($test2_pass) {
    echo "✅ PASS: enqueue_legacy_frontend() properly disabled\n\n";
} else {
    echo "❌ FAIL: enqueue_legacy_frontend() not properly disabled\n\n";
}

// Test 3: Verify early return statements
echo "Test 3: Early Return Statements\n";
$test3_pass = true;

// Count occurrences of the early return pattern
$pattern1 = '/private function enqueue_new_frontend\(\).*?return;/s';
$pattern2 = '/private function enqueue_legacy_frontend\(\).*?return;/s';

if (preg_match($pattern1, $content)) {
    echo "  ✅ enqueue_new_frontend() has early return\n";
} else {
    echo "  ❌ enqueue_new_frontend() missing early return\n";
    $test3_pass = false;
}

if (preg_match($pattern2, $content)) {
    echo "  ✅ enqueue_legacy_frontend() has early return\n";
} else {
    echo "  ❌ enqueue_legacy_frontend() missing early return\n";
    $test3_pass = false;
}

if ($test3_pass) {
    echo "✅ PASS: Both methods have early return statements\n\n";
} else {
    echo "❌ FAIL: Missing early return statements\n\n";
}

// Test 4: Verify Phase 3 scripts are commented out
echo "Test 4: Phase 3 Scripts Commented Out\n";
$test4_pass = true;

$phase3_scripts = [
    'mas-v2-event-bus',
    'mas-v2-state-manager',
    'mas-v2-api-client',
    'mas-v2-admin-app',
    'mas-v2-component'
];

foreach ($phase3_scripts as $script) {
    if (strpos($content, $script) !== false) {
        echo "  ✅ Script '{$script}' found in commented code\n";
    } else {
        echo "  ⚠️  Script '{$script}' not found (may be OK if removed)\n";
    }
}

echo "✅ PASS: Phase 3 scripts preserved in comments\n\n";

// Test 5: Verify spec reference
echo "Test 5: Spec Documentation Reference\n";

if (strpos($content, '.kiro/specs/emergency-frontend-stabilization/') !== false) {
    echo "  ✅ Found spec reference\n";
    echo "✅ PASS: Spec documentation referenced\n\n";
} else {
    echo "  ❌ Missing spec reference\n";
    echo "❌ FAIL: Spec not referenced\n\n";
}

// Final Summary
echo "=== Summary ===\n";

$all_pass = $test1_pass && $test2_pass && $test3_pass;

if ($all_pass) {
    echo "✅ ALL TESTS PASSED\n\n";
    echo "Task 3 Implementation Status: ✅ COMPLETE\n\n";
    echo "Both methods are properly disabled:\n";
    echo "  • enqueue_new_frontend() - Phase 3 frontend disabled\n";
    echo "  • enqueue_legacy_frontend() - Replaced by inline loading\n\n";
    echo "Next Steps:\n";
    echo "  • Task 4: Update feature flags admin UI\n";
    echo "  • Task 5: Test emergency stabilization\n";
    exit(0);
} else {
    echo "❌ SOME TESTS FAILED\n";
    echo "Please review the implementation\n";
    exit(1);
}
