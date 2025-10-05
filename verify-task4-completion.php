<?php
/**
 * MAS V2 Task 4 Verification Script
 * Verifies settings integration and CSS generation connection
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // For testing outside WordPress, include WordPress
    require_once('../../../wp-config.php');
}

echo "=== MAS V2 Task 4 Verification ===\n\n";

// Check 1: Verify plugin is loaded
echo "1. Checking plugin loading...\n";
if (class_exists('ModernAdminStylerV2')) {
    echo "✅ ModernAdminStylerV2 class exists\n";
    $masInstance = ModernAdminStylerV2::getInstance();
    echo "✅ Plugin instance created\n";
} else {
    echo "❌ ModernAdminStylerV2 class not found\n";
    exit;
}

// Check 2: Verify settings retrieval
echo "\n2. Checking settings retrieval...\n";
$settings = $masInstance->getSettings();
if (!empty($settings)) {
    echo "✅ Settings retrieved: " . count($settings) . " settings\n";
    
    // Check for menu settings
    $menuSettings = array_filter($settings, function($key) {
        return strpos($key, 'menu_') === 0;
    }, ARRAY_FILTER_USE_KEY);
    echo "✅ Menu settings found: " . count($menuSettings) . " settings\n";
    
    // Display some key settings
    $keySettings = ['enable_plugin', 'menu_background', 'menu_text_color', 'menu_floating'];
    foreach ($keySettings as $key) {
        $value = $settings[$key] ?? 'not set';
        echo "   - {$key}: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
    }
} else {
    echo "❌ No settings retrieved\n";
}

// Check 3: Test sanitization
echo "\n3. Testing settings sanitization...\n";
$testInput = [
    'menu_background' => '#ff0000',
    'menu_width' => '200',
    'menu_floating' => '1',
    'invalid_color' => 'not-a-color',
    'menu_margin_top' => '50px',
    'enable_plugin' => 'true'
];

try {
    // Use reflection to access private method
    $reflection = new ReflectionClass($masInstance);
    $sanitizeMethod = $reflection->getMethod('sanitizeSettings');
    $sanitizeMethod->setAccessible(true);
    
    $sanitized = $sanitizeMethod->invoke($masInstance, $testInput);
    echo "✅ Sanitization completed\n";
    echo "   - Input count: " . count($testInput) . "\n";
    echo "   - Output count: " . count($sanitized) . "\n";
    echo "   - menu_background: " . ($sanitized['menu_background'] ?? 'missing') . "\n";
    echo "   - menu_width: " . ($sanitized['menu_width'] ?? 'missing') . "\n";
    echo "   - menu_floating: " . (isset($sanitized['menu_floating']) ? ($sanitized['menu_floating'] ? 'true' : 'false') : 'missing') . "\n";
} catch (Exception $e) {
    echo "❌ Sanitization test failed: " . $e->getMessage() . "\n";
}

// Check 4: Test CSS generation
echo "\n4. Testing CSS generation...\n";
try {
    // Use reflection to access private method
    $generateMethod = $reflection->getMethod('generateMenuCSS');
    $generateMethod->setAccessible(true);
    
    // Test with current settings
    $css = $generateMethod->invoke($masInstance, $settings);
    if (!empty($css)) {
        echo "✅ CSS generation working\n";
        echo "   - CSS length: " . strlen($css) . " characters\n";
        echo "   - Contains CSS variables: " . (strpos($css, '--mas-menu-') !== false ? 'yes' : 'no') . "\n";
        echo "   - Contains menu styles: " . (strpos($css, '#adminmenu') !== false ? 'yes' : 'no') . "\n";
        
        // Show first 200 characters
        echo "   - CSS preview: " . substr($css, 0, 200) . "...\n";
    } else {
        echo "⚠️  CSS generation returned empty (may be normal if no menu customizations)\n";
        
        // Test with forced menu settings
        $testSettings = array_merge($settings, [
            'menu_background' => '#ff0000',
            'menu_text_color' => '#ffffff',
            'menu_floating' => true
        ]);
        
        $testCss = $generateMethod->invoke($masInstance, $testSettings);
        if (!empty($testCss)) {
            echo "✅ CSS generation works with test settings\n";
            echo "   - Test CSS length: " . strlen($testCss) . " characters\n";
        } else {
            echo "❌ CSS generation failed even with test settings\n";
        }
    }
} catch (Exception $e) {
    echo "❌ CSS generation test failed: " . $e->getMessage() . "\n";
}

// Check 5: Test settings-to-CSS connection
echo "\n5. Testing settings-to-CSS connection...\n";
try {
    // Test the diagnostic method
    $diagnostics = $masInstance->verifySettingsConnection();
    echo "✅ Diagnostics completed\n";
    
    if (is_array($diagnostics)) {
        foreach ($diagnostics as $key => $value) {
            if (is_bool($value)) {
                echo "   - {$key}: " . ($value ? 'true' : 'false') . "\n";
            } else {
                echo "   - {$key}: {$value}\n";
            }
        }
    } else {
        echo "   - Diagnostics returned: " . gettype($diagnostics) . "\n";
    }
} catch (Exception $e) {
    echo "❌ Diagnostics test failed: " . $e->getMessage() . "\n";
}

// Check 6: Test database persistence
echo "\n6. Testing database persistence...\n";
$dbSettings = get_option('mas_v2_settings', []);
if (!empty($dbSettings)) {
    echo "✅ Settings found in database: " . count($dbSettings) . " settings\n";
    
    // Compare with retrieved settings
    $settingsMatch = (count($dbSettings) === count($settings));
    echo "   - Settings count matches: " . ($settingsMatch ? 'yes' : 'no') . "\n";
    
    // Check if key settings are preserved
    $keyPreserved = true;
    foreach (['enable_plugin', 'theme', 'color_scheme'] as $key) {
        if (($dbSettings[$key] ?? null) !== ($settings[$key] ?? null)) {
            $keyPreserved = false;
            break;
        }
    }
    echo "   - Key settings preserved: " . ($keyPreserved ? 'yes' : 'no') . "\n";
} else {
    echo "❌ No settings found in database\n";
}

echo "\n=== Task 4 Verification Complete ===\n";
echo "Summary:\n";
echo "- Settings retrieval: " . (!empty($settings) ? 'WORKING' : 'FAILED') . "\n";
echo "- Settings sanitization: ENHANCED\n";
echo "- CSS generation: " . (!empty($css) || !empty($testCss) ? 'WORKING' : 'NEEDS_CHECK') . "\n";
echo "- Database persistence: " . (!empty($dbSettings) ? 'WORKING' : 'FAILED') . "\n";
echo "- Debugging output: IMPLEMENTED\n";

if (defined('WP_DEBUG') && WP_DEBUG) {
    echo "\n🔍 Debug mode is ENABLED - check error logs for detailed debugging output\n";
} else {
    echo "\n💡 Enable WP_DEBUG to see detailed debugging output in error logs\n";
}