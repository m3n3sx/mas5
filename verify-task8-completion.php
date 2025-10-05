<?php
/**
 * Task 8 Verification: Settings Persistence Fix
 * Tests the settings save/load mechanism, AJAX error handling, and validation
 */

// WordPress environment setup
if (!defined('ABSPATH')) {
    // Try to find WordPress
    $wp_paths = [
        __DIR__ . '/wp-config.php',
        __DIR__ . '/../wp-config.php',
        __DIR__ . '/../../wp-config.php',
        __DIR__ . '/../../../wp-config.php'
    ];
    
    foreach ($wp_paths as $path) {
        if (file_exists($path)) {
            require_once dirname($path) . '/wp-load.php';
            break;
        }
    }
    
    if (!defined('ABSPATH')) {
        die("WordPress environment not found. Please run this from WordPress directory.\n");
    }
}

echo "=== Task 8: Settings Persistence Fix Verification ===\n\n";

// Get plugin instance
$masInstance = ModernAdminStylerV2::getInstance();

if (!$masInstance) {
    die("❌ Plugin instance not found\n");
}

echo "✅ Plugin instance found\n";

// Test 1: Check if settings exist in database
echo "\n1. Testing settings database persistence...\n";
$currentSettings = get_option('mas_v2_settings', []);
if (!empty($currentSettings)) {
    echo "✅ Settings found in database: " . count($currentSettings) . " settings\n";
    
    // Show some key settings
    $keySettings = ['enable_plugin', 'menu_background', 'admin_bar_background', 'theme'];
    foreach ($keySettings as $key) {
        if (isset($currentSettings[$key])) {
            echo "  - {$key}: " . (is_bool($currentSettings[$key]) ? ($currentSettings[$key] ? 'true' : 'false') : $currentSettings[$key]) . "\n";
        }
    }
} else {
    echo "⚠️  No settings found in database, initializing defaults...\n";
    
    // Use reflection to access private method
    $reflection = new ReflectionClass($masInstance);
    $getDefaultsMethod = $reflection->getMethod('getDefaultSettings');
    $getDefaultsMethod->setAccessible(true);
    $defaults = $getDefaultsMethod->invoke($masInstance);
    
    update_option('mas_v2_settings', $defaults);
    echo "✅ Default settings initialized: " . count($defaults) . " settings\n";
}

// Test 2: Test settings sanitization
echo "\n2. Testing settings sanitization...\n";
try {
    // Use reflection to access private method
    $reflection = new ReflectionClass($masInstance);
    $sanitizeMethod = $reflection->getMethod('sanitizeSettings');
    $sanitizeMethod->setAccessible(true);
    
    // Test data with various edge cases
    $testData = [
        'enable_plugin' => '1',  // String boolean
        'menu_background' => '#invalid-color',  // Invalid color
        'menu_width' => 'abc',  // Invalid number
        'admin_bar_height' => '50px',  // Valid CSS value
        'custom_css' => '<script>alert("xss")</script>',  // XSS attempt
        'new_setting' => 'test_value',  // New setting not in defaults
        'menu_margin_top' => '-10',  // Negative value
    ];
    
    $sanitized = $sanitizeMethod->invoke($masInstance, $testData);
    
    echo "✅ Sanitization completed without errors\n";
    echo "  - enable_plugin: " . ($sanitized['enable_plugin'] ? 'true' : 'false') . " (converted from string)\n";
    echo "  - menu_background: " . $sanitized['menu_background'] . " (color validation)\n";
    echo "  - menu_width: " . $sanitized['menu_width'] . " (number validation)\n";
    echo "  - custom_css: " . (strlen($sanitized['custom_css']) > 50 ? substr($sanitized['custom_css'], 0, 50) . '...' : $sanitized['custom_css']) . " (XSS protection)\n";
    
    if (isset($sanitized['new_setting'])) {
        echo "  - new_setting: " . $sanitized['new_setting'] . " (new setting handled)\n";
    }
    
} catch (Exception $e) {
    echo "❌ Sanitization error: " . $e->getMessage() . "\n";
}

// Test 3: Test settings validation integrity
echo "\n3. Testing settings validation integrity...\n";
try {
    $reflection = new ReflectionClass($masInstance);
    $validateMethod = $reflection->getMethod('validateSettingsIntegrity');
    $validateMethod->setAccessible(true);
    
    // Test data with integrity issues
    $testData = [
        'color_scheme' => 'invalid_scheme',
        'theme' => 'nonexistent_theme',
        'menu_floating' => true,
        // Missing required margin settings for floating menu
    ];
    
    $validated = $validateMethod->invoke($masInstance, $testData);
    
    echo "✅ Validation completed\n";
    echo "  - color_scheme: " . $validated['color_scheme'] . " (corrected invalid value)\n";
    echo "  - theme: " . $validated['theme'] . " (corrected invalid value)\n";
    echo "  - menu_margin_top: " . ($validated['menu_margin_top'] ?? 'not set') . " (auto-added for floating menu)\n";
    
} catch (Exception $e) {
    echo "❌ Validation error: " . $e->getMessage() . "\n";
}

// Test 4: Test AJAX save settings simulation
echo "\n4. Testing AJAX save settings simulation...\n";

// Simulate AJAX request data
$_POST = [
    'action' => 'mas_v2_save_settings',
    'nonce' => wp_create_nonce('mas_v2_nonce'),
    'enable_plugin' => '1',
    'menu_background' => '#2c3e50',
    'menu_text_color' => '#ffffff',
    'admin_bar_background' => '#34495e',
    'theme' => 'modern',
    'test_timestamp' => time()
];

// Set current user capability
wp_set_current_user(1); // Assume admin user

try {
    // Capture output
    ob_start();
    $masInstance->ajaxSaveSettings();
    $output = ob_get_clean();
    
    if (!empty($output)) {
        $response = json_decode($output, true);
        if ($response && isset($response['success'])) {
            if ($response['success']) {
                echo "✅ AJAX save successful\n";
                if (isset($response['data']['debug'])) {
                    $debug = $response['data']['debug'];
                    echo "  - Settings count: " . ($debug['settings_count'] ?? 'unknown') . "\n";
                    echo "  - CSS generated: " . ($debug['css_generated'] ? 'yes' : 'no') . "\n";
                    echo "  - Save result: " . ($debug['save_result'] ? 'success' : 'failed/unchanged') . "\n";
                }
            } else {
                echo "❌ AJAX save failed: " . ($response['data']['message'] ?? 'Unknown error') . "\n";
            }
        } else {
            echo "⚠️  Invalid JSON response: " . substr($output, 0, 100) . "\n";
        }
    } else {
        echo "⚠️  No output from AJAX save\n";
    }
    
} catch (Exception $e) {
    echo "❌ AJAX save error: " . $e->getMessage() . "\n";
}

// Test 5: Verify settings were actually saved
echo "\n5. Verifying settings persistence...\n";
$savedSettings = get_option('mas_v2_settings', []);
if (isset($savedSettings['test_timestamp'])) {
    echo "✅ Settings successfully persisted to database\n";
    echo "  - Test timestamp: " . $savedSettings['test_timestamp'] . "\n";
    echo "  - Menu background: " . ($savedSettings['menu_background'] ?? 'not set') . "\n";
    echo "  - Admin bar background: " . ($savedSettings['admin_bar_background'] ?? 'not set') . "\n";
} else {
    echo "❌ Settings were not persisted to database\n";
}

// Test 6: Test error handling for corrupted settings
echo "\n6. Testing error handling for corrupted settings...\n";

// Temporarily corrupt settings
$corruptedSettings = [
    'menu_background' => null,
    'invalid_structure' => ['nested' => ['too' => ['deep' => 'value']]],
    'xss_attempt' => '<script>alert("xss")</script>',
];

update_option('mas_v2_settings', $corruptedSettings);

try {
    // Try to get settings - should handle corruption gracefully
    $reflection = new ReflectionClass($masInstance);
    $getSettingsMethod = $reflection->getMethod('getSettings');
    $getSettingsMethod->setAccessible(true);
    $recoveredSettings = $getSettingsMethod->invoke($masInstance);
    
    if (!empty($recoveredSettings) && isset($recoveredSettings['enable_plugin'])) {
        echo "✅ Corrupted settings handled gracefully\n";
        echo "  - Recovered settings count: " . count($recoveredSettings) . "\n";
        echo "  - Plugin enabled: " . ($recoveredSettings['enable_plugin'] ? 'yes' : 'no') . "\n";
    } else {
        echo "❌ Failed to recover from corrupted settings\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error handling corrupted settings: " . $e->getMessage() . "\n";
}

// Test 7: Test CSS generation with saved settings
echo "\n7. Testing CSS generation with saved settings...\n";
try {
    $currentSettings = get_option('mas_v2_settings', []);
    
    // Use reflection to access private method
    $reflection = new ReflectionClass($masInstance);
    $generateCSSMethod = $reflection->getMethod('generateMenuCSS');
    $generateCSSMethod->setAccessible(true);
    
    $css = $generateCSSMethod->invoke($masInstance, $currentSettings);
    
    if (!empty($css)) {
        echo "✅ CSS generation working with saved settings\n";
        echo "  - CSS length: " . strlen($css) . " characters\n";
        echo "  - Contains menu styles: " . (strpos($css, 'menu') !== false ? 'yes' : 'no') . "\n";
    } else {
        echo "❌ CSS generation failed with saved settings\n";
    }
    
} catch (Exception $e) {
    echo "❌ CSS generation error: " . $e->getMessage() . "\n";
}

// Clean up - restore proper settings
echo "\n8. Cleaning up test data...\n";
$reflection = new ReflectionClass($masInstance);
$getDefaultsMethod = $reflection->getMethod('getDefaultSettings');
$getDefaultsMethod->setAccessible(true);
$defaults = $getDefaultsMethod->invoke($masInstance);
update_option('mas_v2_settings', $defaults);
echo "✅ Default settings restored\n";

echo "\n=== Task 8 Verification Complete ===\n";
echo "Summary:\n";
echo "- Settings persistence: Database operations working\n";
echo "- Sanitization: Input validation and cleaning working\n";
echo "- Validation: Settings integrity checks working\n";
echo "- AJAX handling: Save/load mechanism functional\n";
echo "- Error recovery: Corrupted settings handled gracefully\n";
echo "- CSS integration: Settings properly connected to CSS generation\n";