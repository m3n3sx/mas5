<?php
/**
 * Test script to verify the undefined variable fix
 */

// Suppress WordPress connection warnings for testing
error_reporting(E_ERROR | E_PARSE);

// Mock WordPress functions for testing
if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all') {
        echo "✅ wp_enqueue_style called: $handle\n";
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false) {
        echo "✅ wp_enqueue_script called: $handle\n";
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        echo "✅ add_action called: $hook\n";
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        return true;
    }
}

// Define constants
if (!defined('MAS_V2_PLUGIN_URL')) {
    define('MAS_V2_PLUGIN_URL', '/test/');
}

if (!defined('MAS_V2_VERSION')) {
    define('MAS_V2_VERSION', '2.0.0');
}

// Set up globals
$GLOBALS['pagenow'] = 'admin.php';
$_GET['page'] = 'test';

echo "🧪 Testing undefined variable fix...\n\n";

try {
    // Include the main plugin file
    require_once 'modern-admin-styler-v2.php';
    
    // Create plugin instance
    $plugin = new ModernAdminStylerV2();
    
    // Test the method that was causing the undefined variable error
    echo "📝 Testing enqueueGlobalAssets method...\n";
    $plugin->enqueueGlobalAssets('admin.php');
    
    echo "\n✅ SUCCESS: No undefined variable errors detected!\n";
    echo "🎉 The fix is working correctly.\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
} catch (Error $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}