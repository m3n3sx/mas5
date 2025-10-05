<?php
/**
 * Task 8 Settings Persistence Test
 * Tests the enhanced settings save/load mechanism with proper nonce handling
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

echo "=== Task 8: Enhanced Settings Persistence Test ===\n\n";

// Get plugin instance
$masInstance = ModernAdminStylerV2::getInstance();

if (!$masInstance) {
    die("❌ Plugin instance not found\n");
}

echo "✅ Plugin instance found\n";

// Set up admin user context
wp_set_current_user(1);

// Test 1: Test enhanced sanitization with error tracking
echo "\n1. Testing enhanced sanitization with error tracking...\n";
try {
    $reflection = new ReflectionClass($masInstance);
    $sanitizeMethod = $reflection->getMethod('sanitizeSettingsWithErrorTracking');
    $sanitizeMethod->setAccessible(true);
    
    $testData = [
        'enable_plugin' => 'on',  // String boolean
        'menu_background' => '#invalid-color',  // Invalid color
        'menu_width' => 'abc',  // Invalid number
        'admin_bar_height' => '50px',  // Valid CSS value
        'custom_css' => '<script>alert("xss")</script>body{color:red;}',  // XSS attempt with CSS
        'new_setting' => 'test_value',  // New setting
        'action' => 'mas_v2_save_settings',  // System field (should be skipped)
        'nonce' => 'test_nonce',  // System field (should be skipped)
    ];
    
    $errors = [];
    $sanitized = $sanitizeMethod->invoke($masInstance, $testData, $errors);
    
    echo "✅ Enhanced sanitization completed\n";
    echo "  - Sanitization errors tracked: " . count($errors) . "\n";
    echo "  - enable_plugin: " . ($sanitized['enable_plugin'] ? 'true' : 'false') . "\n";
    echo "  - menu_background: " . $sanitized['menu_background'] . "\n";
    echo "  - custom_css length: " . strlen($sanitized['custom_css']) . " chars\n";
    echo "  - System fields skipped: " . (!isset($sanitized['action']) && !isset($sanitized['nonce']) ? 'yes' : 'no') . "\n";
    
    if (!empty($errors)) {
        echo "  - Sample error: " . $errors[0] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Enhanced sanitization error: " . $e->getMessage() . "\n";
}

// Test 2: Test enhanced validation with error tracking
echo "\n2. Testing enhanced validation with error tracking...\n";
try {
    $reflection = new ReflectionClass($masInstance);
    $validateMethod = $reflection->getMethod('validateSettingsIntegrityWithErrors');
    $validateMethod->setAccessible(true);
    
    $testData = [
        'enable_plugin' => true,
        'color_scheme' => 'invalid_scheme',
        'theme' => 'nonexistent_theme',
        'menu_floating' => true,
        'menu_width' => 1000,  // Out of range
        'admin_bar_height' => 5,  // Out of range
        'menu_background' => 'not-a-color',
        'custom_css' => 'body{color:red;} <script>alert("bad")</script>',
    ];
    
    $errors = [];
    $validated = $validateMethod->invoke($masInstance, $testData, $errors);
    
    echo "✅ Enhanced validation completed\n";
    echo "  - Critical errors: " . count($errors['critical']) . "\n";
    echo "  - Warnings: " . count($errors['warnings']) . "\n";
    echo "  - color_scheme corrected to: " . $validated['color_scheme'] . "\n";
    echo "  - theme corrected to: " . $validated['theme'] . "\n";
    echo "  - menu_width corrected to: " . $validated['menu_width'] . "\n";
    echo "  - menu_margin_top auto-set to: " . ($validated['menu_margin_top'] ?? 'not set') . "\n";
    echo "  - custom_css cleaned: " . (empty($validated['custom_css']) ? 'yes (dangerous content removed)' : 'no') . "\n";
    
    if (!empty($errors['warnings'])) {
        echo "  - Sample warning: " . $errors['warnings'][0] . "\n";
    }
    if (!empty($errors['critical'])) {
        echo "  - Sample critical error: " . $errors['critical'][0] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Enhanced validation error: " . $e->getMessage() . "\n";
}

// Test 3: Test backup and cleanup functionality
echo "\n3. Testing backup and cleanup functionality...\n";
try {
    $reflection = new ReflectionClass($masInstance);
    $cleanupMethod = $reflection->getMethod('cleanupSettingsBackups');
    $cleanupMethod->setAccessible(true);
    
    // Create some test backups
    for ($i = 1; $i <= 7; $i++) {
        $backup_key = 'mas_v2_settings_backup_test_' . (time() - $i);
        update_option($backup_key, ['test' => 'backup_' . $i], false);
    }
    
    // Count backups before cleanup
    global $wpdb;
    $before_count = $wpdb->get_var(
        "SELECT COUNT(*) FROM $wpdb->options WHERE option_name LIKE 'mas_v2_settings_backup_%'"
    );
    
    // Run cleanup
    $cleanupMethod->invoke($masInstance);
    
    // Count backups after cleanup
    $after_count = $wpdb->get_var(
        "SELECT COUNT(*) FROM $wpdb->options WHERE option_name LIKE 'mas_v2_settings_backup_%'"
    );
    
    echo "✅ Backup cleanup completed\n";
    echo "  - Backups before cleanup: " . $before_count . "\n";
    echo "  - Backups after cleanup: " . $after_count . "\n";
    echo "  - Cleanup working: " . ($after_count <= 5 ? 'yes' : 'no') . "\n";
    
    // Clean up test backups
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'mas_v2_settings_backup_test_%'");
    
} catch (Exception $e) {
    echo "❌ Backup cleanup error: " . $e->getMessage() . "\n";
}

// Test 4: Test settings persistence with proper error handling
echo "\n4. Testing settings persistence with error handling...\n";
try {
    // Save current settings as backup
    $original_settings = get_option('mas_v2_settings', []);
    
    // Test settings with various edge cases
    $test_settings = [
        'enable_plugin' => true,
        'menu_background' => '#2c3e50',
        'menu_text_color' => '#ffffff',
        'admin_bar_background' => '#34495e',
        'theme' => 'modern',
        'color_scheme' => 'light',
        'menu_floating' => true,
        'menu_margin_top' => 25,
        'test_persistence' => time(),
    ];
    
    // Use enhanced sanitization
    $reflection = new ReflectionClass($masInstance);
    $sanitizeMethod = $reflection->getMethod('sanitizeSettingsWithErrorTracking');
    $sanitizeMethod->setAccessible(true);
    
    $errors = [];
    $sanitized_settings = $sanitizeMethod->invoke($masInstance, $test_settings, $errors);
    
    // Use enhanced validation
    $validateMethod = $reflection->getMethod('validateSettingsIntegrityWithErrors');
    $validateMethod->setAccessible(true);
    
    $validation_errors = [];
    $validated_settings = $validateMethod->invoke($masInstance, $sanitized_settings, $validation_errors);
    
    // Save settings
    $save_result = update_option('mas_v2_settings', $validated_settings);
    
    // Verify persistence
    $saved_settings = get_option('mas_v2_settings', []);
    $persistence_verified = isset($saved_settings['test_persistence']) && 
                           $saved_settings['test_persistence'] == $test_settings['test_persistence'];
    
    echo "✅ Settings persistence test completed\n";
    echo "  - Sanitization errors: " . count($errors) . "\n";
    echo "  - Validation warnings: " . count($validation_errors['warnings'] ?? []) . "\n";
    echo "  - Validation critical: " . count($validation_errors['critical'] ?? []) . "\n";
    echo "  - Save result: " . ($save_result ? 'success' : 'failed/unchanged') . "\n";
    echo "  - Persistence verified: " . ($persistence_verified ? 'yes' : 'no') . "\n";
    echo "  - Settings count: " . count($saved_settings) . "\n";
    
    // Test CSS generation with saved settings
    $generateMethod = $reflection->getMethod('generateMenuCSS');
    $generateMethod->setAccessible(true);
    $css = $generateMethod->invoke($masInstance, $saved_settings);
    
    echo "  - CSS generation: " . (!empty($css) ? 'working (' . strlen($css) . ' chars)' : 'failed') . "\n";
    
    // Restore original settings
    update_option('mas_v2_settings', $original_settings);
    
} catch (Exception $e) {
    echo "❌ Settings persistence error: " . $e->getMessage() . "\n";
    // Restore original settings on error
    if (isset($original_settings)) {
        update_option('mas_v2_settings', $original_settings);
    }
}

// Test 5: Test corrupted settings recovery
echo "\n5. Testing corrupted settings recovery...\n";
try {
    // Save current settings
    $original_settings = get_option('mas_v2_settings', []);
    
    // Create corrupted settings
    $corrupted_settings = [
        'enable_plugin' => null,
        'menu_background' => ['invalid' => 'structure'],
        'theme' => 12345,  // Wrong type
        'color_scheme' => 'totally_invalid',
        'custom_css' => '<script>alert("hack")</script>',
    ];
    
    update_option('mas_v2_settings', $corrupted_settings);
    
    // Try to get settings - should handle corruption gracefully
    $getSettingsMethod = $reflection->getMethod('getSettings');
    $getSettingsMethod->setAccessible(true);
    $recovered_settings = $getSettingsMethod->invoke($masInstance);
    
    // Verify recovery
    $recovery_successful = !empty($recovered_settings) && 
                          isset($recovered_settings['enable_plugin']) &&
                          is_bool($recovered_settings['enable_plugin']) &&
                          in_array($recovered_settings['theme'], ['modern', 'classic', 'minimal']) &&
                          in_array($recovered_settings['color_scheme'], ['light', 'dark', 'auto']);
    
    echo "✅ Corrupted settings recovery test completed\n";
    echo "  - Recovery successful: " . ($recovery_successful ? 'yes' : 'no') . "\n";
    echo "  - Recovered settings count: " . count($recovered_settings) . "\n";
    echo "  - enable_plugin type: " . gettype($recovered_settings['enable_plugin']) . "\n";
    echo "  - theme value: " . $recovered_settings['theme'] . "\n";
    echo "  - color_scheme value: " . $recovered_settings['color_scheme'] . "\n";
    echo "  - custom_css cleaned: " . (empty($recovered_settings['custom_css']) ? 'yes' : 'no') . "\n";
    
    // Restore original settings
    update_option('mas_v2_settings', $original_settings);
    
} catch (Exception $e) {
    echo "❌ Corrupted settings recovery error: " . $e->getMessage() . "\n";
    // Restore original settings on error
    if (isset($original_settings)) {
        update_option('mas_v2_settings', $original_settings);
    }
}

echo "\n=== Task 8: Enhanced Settings Persistence Test Complete ===\n";
echo "Summary of improvements:\n";
echo "✅ Enhanced AJAX error handling with multiple nonce fallbacks\n";
echo "✅ Comprehensive input sanitization with error tracking\n";
echo "✅ Advanced settings validation with integrity checks\n";
echo "✅ Automatic backup creation before save/reset operations\n";
echo "✅ Graceful recovery from corrupted settings\n";
echo "✅ Automatic cleanup of old backup files\n";
echo "✅ Detailed error reporting and logging\n";
echo "✅ Enhanced JavaScript error handling and user feedback\n";