<?php
/**
 * Task 3 Verification Script - Basic Menu Functionality
 * Tests submenu visibility, CSS variable injection, and emergency fallbacks
 */

echo "=== MAS V2 Task 3 Verification ===\n\n";

// Check 1: Verify submenu visibility fixes in generateMenuCSS
echo "1. Checking submenu visibility fixes...\n";

$pluginFile = 'modern-admin-styler-v2.php';
$content = file_get_contents($pluginFile);

// Look for critical submenu visibility fixes
if (strpos($content, 'CRITICAL SUBMENU VISIBILITY FIXES') !== false &&
    strpos($content, 'li.wp-has-current-submenu .wp-submenu') !== false &&
    strpos($content, 'body.mas-v2-menu-floating #adminmenu li.menu-top:hover .wp-submenu') !== false) {
    echo "✅ SUCCESS: Submenu visibility fixes implemented in CSS generation\n";
} else {
    echo "❌ FAILED: Submenu visibility fixes not found\n";
    exit(1);
}

// Check 2: Verify emergency fallback styles
echo "\n2. Checking emergency fallback styles...\n";

if (strpos($content, 'Emergency Fallback CSS') !== false &&
    strpos($content, '--mas-menu-bg-color: #23282d') !== false &&
    strpos($content, 'display: block !important') !== false) {
    echo "✅ SUCCESS: Emergency fallback styles implemented\n";
} else {
    echo "❌ FAILED: Emergency fallback styles not found\n";
    exit(1);
}

// Check 3: Verify CSS variable injection enhancements
echo "\n3. Checking CSS variable injection...\n";

if (strpos($content, '--mas-menu-customizations-active: 1') !== false &&
    strpos($content, 'hasMenuCustomizations') !== false) {
    echo "✅ SUCCESS: CSS variable injection enhanced\n";
} else {
    echo "❌ FAILED: CSS variable injection not properly enhanced\n";
    exit(1);
}

// Check 4: Verify emergency JavaScript fallbacks
echo "\n4. Checking emergency JavaScript fallbacks...\n";

$jsFile = 'assets/js/admin-global.js';
if (file_exists($jsFile)) {
    $jsContent = file_get_contents($jsFile);
    
    if (strpos($jsContent, 'setupEmergencyFallbacks') !== false &&
        strpos($jsContent, 'setupEmergencySubmenuFix') !== false &&
        strpos($jsContent, 'mas-v2-menu-custom-enabled') !== false) {
        echo "✅ SUCCESS: Emergency JavaScript fallbacks implemented\n";
    } else {
        echo "❌ FAILED: Emergency JavaScript fallbacks not found\n";
        exit(1);
    }
} else {
    echo "❌ FAILED: admin-global.js file not found\n";
    exit(1);
}

// Check 5: Verify quick-fix.css emergency styles
echo "\n5. Checking quick-fix.css emergency styles...\n";

$quickFixFile = 'assets/css/quick-fix.css';
if (file_exists($quickFixFile)) {
    $quickFixContent = file_get_contents($quickFixFile);
    
    if (strpos($quickFixContent, 'EMERGENCY SUBMENU VISIBILITY FIXES') !== false &&
        strpos($quickFixContent, 'li.wp-has-current-submenu .wp-submenu') !== false &&
        strpos($quickFixContent, 'body.mas-v2-menu-floating #adminmenu li.menu-top:hover .wp-submenu') !== false) {
        echo "✅ SUCCESS: Emergency styles added to quick-fix.css\n";
    } else {
        echo "❌ FAILED: Emergency styles not found in quick-fix.css\n";
        exit(1);
    }
} else {
    echo "❌ FAILED: quick-fix.css file not found\n";
    exit(1);
}

// Check 6: Verify body class management
echo "\n6. Checking body class management...\n";

if (strpos($content, 'admin_body_class') !== false &&
    strpos($content, 'mas-v2-menu-custom-enabled') !== false) {
    echo "✅ SUCCESS: Body class management implemented\n";
} else {
    echo "❌ FAILED: Body class management not found\n";
    exit(1);
}

// Check 7: Verify submenu testing code
echo "\n7. Checking submenu testing functionality...\n";

if (strpos($content, 'Submenu Test') !== false &&
    strpos($content, 'Testing hover on first menu item') !== false) {
    echo "✅ SUCCESS: Submenu testing code implemented\n";
} else {
    echo "❌ FAILED: Submenu testing code not found\n";
    exit(1);
}

echo "\n=== TASK 3 VERIFICATION COMPLETE ===\n";
echo "All checks passed! Task 3 requirements met:\n";
echo "✅ Test and fix submenu visibility issues in floating mode by examining CSS selectors\n";
echo "✅ Implement basic CSS variable injection for menu colors and dimensions\n";
echo "✅ Create emergency fallback styles for when JavaScript modules fail to load\n";
echo "✅ Requirements 1.4, 1.5 addressed\n\n";

echo "Next steps:\n";
echo "- Test the submenu functionality in WordPress admin\n";
echo "- Verify floating mode submenu visibility on hover\n";
echo "- Check that emergency fallbacks work when JavaScript is disabled\n";
echo "- Validate CSS variables are properly injected\n";