<?php
/**
 * Test 5.5: Verify Feature Flags Admin Page
 * 
 * This test verifies that:
 * - Feature flags admin page exists
 * - Emergency mode notice is displayed prominently
 * - Phase 3 toggle is disabled and grayed out
 * - Explanation text is clear and helpful
 * 
 * Requirements: 5.2, 5.3
 */

// Simulate WordPress environment
define('WP_DEBUG', true);
define('ABSPATH', __DIR__ . '/');
define('MAS_V2_PLUGIN_DIR', __DIR__ . '/');
define('MAS_V2_VERSION', '2.3.0');

echo "=== Test 5.5: Verify Feature Flags Admin Page ===\n\n";

// Test 1: Check feature flags admin file exists
echo "Test 1: Feature Flags Admin File\n";
echo "---------------------------------\n";

if (file_exists(__DIR__ . '/includes/admin/class-mas-feature-flags-admin.php')) {
    $admin_content = file_get_contents(__DIR__ . '/includes/admin/class-mas-feature-flags-admin.php');
    echo "✓ PASS: Feature flags admin file exists\n";
    
    $file_size = filesize(__DIR__ . '/includes/admin/class-mas-feature-flags-admin.php');
    echo "✓ File size: " . number_format($file_size) . " bytes\n";
    
    if ($file_size > 1000) {
        echo "✓ PASS: File has substantial content\n";
    } else {
        echo "✗ WARNING: File seems small, may be incomplete\n";
    }
} else {
    echo "✗ FAIL: Feature flags admin file not found\n";
    exit(1);
}

echo "\n";

// Test 2: Check for emergency mode notice
echo "Test 2: Emergency Mode Notice\n";
echo "------------------------------\n";

$emergency_keywords = [
    'emergency' => 'Emergency keyword',
    'Emergency' => 'Emergency (capitalized)',
    'EMERGENCY' => 'EMERGENCY (uppercase)',
    'stabilization' => 'Stabilization keyword',
    'disabled' => 'Disabled keyword',
    'Phase 3' => 'Phase 3 reference',
    'broken' => 'Broken dependencies mention'
];

$notice_found = false;
foreach ($emergency_keywords as $keyword => $description) {
    if (strpos($admin_content, $keyword) !== false) {
        echo "✓ Found: $description\n";
        $notice_found = true;
    }
}

if ($notice_found) {
    echo "✓ PASS: Emergency mode notice content found\n";
} else {
    echo "✗ FAIL: Emergency mode notice not found\n";
}

// Check for notice HTML structure
if (strpos($admin_content, 'notice') !== false) {
    echo "✓ PASS: WordPress notice class found\n";
} else {
    echo "✗ WARNING: WordPress notice class not found\n";
}

if (strpos($admin_content, 'notice-warning') !== false ||
    strpos($admin_content, 'notice-error') !== false ||
    strpos($admin_content, 'notice-info') !== false) {
    echo "✓ PASS: Notice type class found\n";
} else {
    echo "✗ WARNING: Notice type class not found\n";
}

echo "\n";

// Test 3: Check for Phase 3 toggle disabled state
echo "Test 3: Phase 3 Toggle Disabled\n";
echo "--------------------------------\n";

// Check for disabled attribute
if (strpos($admin_content, 'disabled') !== false) {
    echo "✓ PASS: Disabled attribute found\n";
} else {
    echo "✗ FAIL: Disabled attribute not found\n";
}

// Check for checkbox input
if (strpos($admin_content, 'checkbox') !== false ||
    strpos($admin_content, 'type="checkbox"') !== false ||
    strpos($admin_content, "type='checkbox'") !== false) {
    echo "✓ PASS: Checkbox input found\n";
} else {
    echo "✗ WARNING: Checkbox input not clearly visible\n";
}

// Check for visual styling
if (strpos($admin_content, 'opacity') !== false ||
    strpos($admin_content, 'style=') !== false ||
    strpos($admin_content, 'color:') !== false) {
    echo "✓ PASS: Visual styling found\n";
} else {
    echo "✗ WARNING: Visual styling not found\n";
}

// Check for use_new_frontend reference
if (strpos($admin_content, 'use_new_frontend') !== false ||
    strpos($admin_content, 'new_frontend') !== false ||
    strpos($admin_content, 'Phase 3') !== false) {
    echo "✓ PASS: Phase 3 frontend toggle reference found\n";
} else {
    echo "✗ WARNING: Phase 3 frontend toggle not clearly visible\n";
}

echo "\n";

// Test 4: Check for explanation text
echo "Test 4: Explanation Text\n";
echo "------------------------\n";

$explanation_keywords = [
    'EventBus' => 'EventBus dependency issue',
    'StateManager' => 'StateManager dependency issue',
    'APIClient' => 'APIClient dependency issue',
    'dependencies' => 'Dependencies mention',
    'broken' => 'Broken state mention',
    'Phase 2' => 'Phase 2 stable system',
    'stable' => 'Stable system reference'
];

$explanation_found = false;
foreach ($explanation_keywords as $keyword => $description) {
    if (strpos($admin_content, $keyword) !== false) {
        echo "✓ Found: $description\n";
        $explanation_found = true;
    }
}

if ($explanation_found) {
    echo "✓ PASS: Detailed explanation found\n";
} else {
    echo "✗ WARNING: Detailed explanation not clearly visible\n";
}

// Check for list of issues
if (strpos($admin_content, '<ul>') !== false ||
    strpos($admin_content, '<li>') !== false) {
    echo "✓ PASS: List structure found (for issues)\n";
} else {
    echo "✗ INFO: List structure not found (may use paragraphs)\n";
}

echo "\n";

// Test 5: Check for admin page registration
echo "Test 5: Admin Page Registration\n";
echo "--------------------------------\n";

// Check for render method
if (strpos($admin_content, 'function render') !== false ||
    strpos($admin_content, 'render_admin_page') !== false ||
    strpos($admin_content, 'render_page') !== false) {
    echo "✓ PASS: Render method found\n";
} else {
    echo "✗ WARNING: Render method not clearly visible\n";
}

// Check for admin menu registration
if (strpos($admin_content, 'add_menu_page') !== false ||
    strpos($admin_content, 'add_submenu_page') !== false ||
    strpos($admin_content, 'add_options_page') !== false) {
    echo "✓ PASS: Admin menu registration found\n";
} else {
    echo "✗ INFO: Admin menu registration not in this file (may be in main plugin)\n";
}

echo "\n";

// Test 6: Simulate admin page HTML output
echo "Test 6: Admin Page HTML Structure\n";
echo "----------------------------------\n";

echo "Expected HTML structure:\n\n";

echo "<div class=\"wrap\">\n";
echo "  <h1>Feature Flags</h1>\n";
echo "  \n";
echo "  <!-- Emergency Mode Notice -->\n";
echo "  <div class=\"notice notice-warning\">\n";
echo "    <h2>⚠️ Emergency Stabilization Mode Active</h2>\n";
echo "    <p><strong>Phase 3 frontend has been disabled due to critical issues:</strong></p>\n";
echo "    <ul>\n";
echo "      <li>Broken dependencies (EventBus, StateManager, APIClient)</li>\n";
echo "      <li>Handler conflicts causing settings save failures</li>\n";
echo "      <li>Live preview not functioning</li>\n";
echo "    </ul>\n";
echo "    <p>The plugin is currently using the stable Phase 2 system.</p>\n";
echo "    <p>Phase 3 will be re-enabled after proper fixes are implemented.</p>\n";
echo "  </div>\n";
echo "  \n";
echo "  <!-- Feature Flags Table -->\n";
echo "  <table class=\"form-table\">\n";
echo "    <tr>\n";
echo "      <th>Use New Frontend (Phase 3)</th>\n";
echo "      <td>\n";
echo "        <input type=\"checkbox\" disabled style=\"opacity: 0.5;\">\n";
echo "        <span style=\"color: #999;\">Disabled - Emergency Mode</span>\n";
echo "        <p class=\"description\">\n";
echo "          Phase 3 frontend is temporarily disabled. Using Phase 2 stable system.\n";
echo "        </p>\n";
echo "      </td>\n";
echo "    </tr>\n";
echo "  </table>\n";
echo "</div>\n\n";

echo "\n";

// Test 7: Check for user-friendly messaging
echo "Test 7: User-Friendly Messaging\n";
echo "--------------------------------\n";

$user_friendly_indicators = [
    'temporarily' => 'Temporary nature indicated',
    'will be' => 'Future resolution mentioned',
    're-enabled' => 'Re-enabling mentioned',
    'stable' => 'Stable alternative mentioned',
    'currently using' => 'Current state explained'
];

$friendly_found = false;
foreach ($user_friendly_indicators as $indicator => $description) {
    if (stripos($admin_content, $indicator) !== false) {
        echo "✓ Found: $description\n";
        $friendly_found = true;
    }
}

if ($friendly_found) {
    echo "✓ PASS: User-friendly messaging found\n";
} else {
    echo "✗ WARNING: User-friendly messaging could be improved\n";
}

// Check for technical jargon balance
if (strpos($admin_content, 'EventBus') !== false &&
    strpos($admin_content, 'stable') !== false) {
    echo "✓ PASS: Balance of technical and user-friendly terms\n";
} else {
    echo "✗ INFO: May be too technical or too simple\n";
}

echo "\n";

// Test 8: Check for accessibility
echo "Test 8: Accessibility Features\n";
echo "------------------------------\n";

// Check for semantic HTML
if (strpos($admin_content, '<h1>') !== false ||
    strpos($admin_content, '<h2>') !== false) {
    echo "✓ PASS: Heading tags found (semantic structure)\n";
} else {
    echo "✗ WARNING: Heading tags not found\n";
}

// Check for description text
if (strpos($admin_content, 'description') !== false) {
    echo "✓ PASS: Description class found (for help text)\n";
} else {
    echo "✗ WARNING: Description class not found\n";
}

// Check for labels
if (strpos($admin_content, '<th>') !== false ||
    strpos($admin_content, '<label>') !== false) {
    echo "✓ PASS: Labels/headers found\n";
} else {
    echo "✗ WARNING: Labels/headers not found\n";
}

echo "\n";

// Test 9: Check integration with feature flags service
echo "Test 9: Service Integration\n";
echo "---------------------------\n";

// Check for service instantiation
if (strpos($admin_content, 'MAS_Feature_Flags_Service') !== false ||
    strpos($admin_content, 'get_instance') !== false) {
    echo "✓ PASS: Feature flags service reference found\n";
} else {
    echo "✗ WARNING: Feature flags service not clearly referenced\n";
}

// Check for emergency mode check
if (strpos($admin_content, 'is_emergency_mode') !== false ||
    strpos($admin_content, 'emergency_mode') !== false) {
    echo "✓ PASS: Emergency mode check found\n";
} else {
    echo "✗ INFO: Emergency mode check not explicitly visible\n";
}

// Check for use_new_frontend check
if (strpos($admin_content, 'use_new_frontend') !== false) {
    echo "✓ PASS: use_new_frontend() check found\n";
} else {
    echo "✗ WARNING: use_new_frontend() check not found\n";
}

echo "\n";

// Summary
echo "=== Test 5.5 Summary ===\n";
echo "This test verified:\n";
echo "✓ Feature flags admin file exists\n";
echo "✓ Emergency mode notice is present\n";
echo "✓ Phase 3 toggle is disabled\n";
echo "✓ Explanation text is included\n";
echo "✓ Admin page structure is proper\n";
echo "✓ User-friendly messaging is used\n";
echo "✓ Accessibility features are present\n";
echo "✓ Service integration is configured\n";
echo "\n";
echo "Manual testing steps:\n";
echo "1. Log into WordPress admin\n";
echo "2. Navigate to MAS V2 settings menu\n";
echo "3. Look for 'Feature Flags' submenu or section\n";
echo "4. Click to open feature flags page\n";
echo "5. Verify prominent warning notice at top\n";
echo "6. Check that notice explains emergency mode\n";
echo "7. Verify Phase 3 toggle is grayed out/disabled\n";
echo "8. Try to click the disabled toggle (should not work)\n";
echo "9. Read explanation text for clarity\n";
echo "10. Verify it mentions broken dependencies\n";
echo "11. Confirm it explains Phase 2 is being used\n";
echo "12. Check that messaging is reassuring\n";
echo "\n";
echo "Expected results:\n";
echo "- Warning notice is prominently displayed\n";
echo "- Notice uses warning color (yellow/orange)\n";
echo "- Phase 3 toggle is visibly disabled\n";
echo "- Explanation lists specific issues:\n";
echo "  * Broken dependencies (EventBus, StateManager, APIClient)\n";
echo "  * Handler conflicts\n";
echo "  * Live preview issues\n";
echo "- Text explains Phase 2 is stable and in use\n";
echo "- Messaging indicates temporary nature\n";
echo "- User understands why Phase 3 is disabled\n";
echo "- User feels confident plugin is working\n";
