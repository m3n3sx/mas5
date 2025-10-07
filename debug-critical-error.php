<?php
/**
 * Critical Error Diagnostic Script
 * 
 * This script helps identify what's causing the WordPress critical error.
 * Run this from the command line: php debug-critical-error.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Modern Admin Styler V2 - Critical Error Diagnostic ===\n\n";

// Check PHP version
echo "1. PHP Version Check:\n";
echo "   Current PHP version: " . PHP_VERSION . "\n";
echo "   Required: 7.4+\n";
echo "   Status: " . (version_compare(PHP_VERSION, '7.4.0', '>=') ? "✓ OK" : "✗ FAIL") . "\n\n";

// Check if WordPress constants are defined (simulated)
echo "2. Simulating WordPress Environment:\n";
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
    echo "   ABSPATH defined: " . ABSPATH . "\n";
}

if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
    echo "   WP_DEBUG enabled\n";
}

// Define plugin constants
define('MAS_V2_VERSION', '3.0.0');
define('MAS_V2_PLUGIN_DIR', __DIR__ . '/');
define('MAS_V2_PLUGIN_URL', 'http://localhost/');
define('MAS_V2_PLUGIN_FILE', __FILE__);

echo "   Plugin constants defined\n\n";

// Try to load the main plugin file
echo "3. Loading Main Plugin File:\n";
try {
    // Mock WordPress functions that the plugin uses
    if (!function_exists('plugin_dir_path')) {
        function plugin_dir_path($file) { return dirname($file) . '/'; }
    }
    if (!function_exists('plugin_dir_url')) {
        function plugin_dir_url($file) { return 'http://localhost/'; }
    }
    if (!function_exists('add_action')) {
        function add_action($hook, $callback, $priority = 10, $args = 1) { 
            echo "   - Registered action: $hook\n";
        }
    }
    if (!function_exists('add_filter')) {
        function add_filter($hook, $callback, $priority = 10, $args = 1) {
            echo "   - Registered filter: $hook\n";
        }
    }
    if (!function_exists('register_activation_hook')) {
        function register_activation_hook($file, $callback) {
            echo "   - Registered activation hook\n";
        }
    }
    if (!function_exists('register_deactivation_hook')) {
        function register_deactivation_hook($file, $callback) {
            echo "   - Registered deactivation hook\n";
        }
    }
    
    echo "   Loading modern-admin-styler-v2.php...\n";
    // Don't actually include it, just check if it's readable
    if (!file_exists('modern-admin-styler-v2.php')) {
        echo "   ✗ FAIL: File not found\n";
    } elseif (!is_readable('modern-admin-styler-v2.php')) {
        echo "   ✗ FAIL: File not readable\n";
    } else {
        echo "   ✓ OK: File exists and is readable\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ FAIL: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
} catch (Error $e) {
    echo "   ✗ FAIL: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n4. Checking Critical Files:\n";
$critical_files = [
    'modern-admin-styler-v2.php' => 'Main plugin file',
    'includes/class-mas-rest-api.php' => 'REST API bootstrap',
    'includes/api/class-mas-rest-controller.php' => 'Base REST controller',
    'includes/services/class-mas-settings-service.php' => 'Settings service',
];

foreach ($critical_files as $file => $description) {
    $status = file_exists($file) ? '✓' : '✗';
    $size = file_exists($file) ? filesize($file) : 0;
    echo "   $status $description ($file) - " . number_format($size) . " bytes\n";
    
    if (file_exists($file)) {
        // Check for syntax errors
        $output = [];
        $return_var = 0;
        exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $return_var);
        if ($return_var !== 0) {
            echo "      ✗ SYNTAX ERROR: " . implode("\n      ", $output) . "\n";
        }
    }
}

echo "\n5. Checking Required PHP Extensions:\n";
$required_extensions = ['json', 'mbstring', 'curl'];
foreach ($required_extensions as $ext) {
    $status = extension_loaded($ext) ? '✓' : '✗';
    echo "   $status $ext\n";
}

echo "\n6. Checking File Permissions:\n";
$check_dirs = ['includes', 'includes/api', 'includes/services', 'assets'];
foreach ($check_dirs as $dir) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        $writable = is_writable($dir) ? '✓' : '✗';
        echo "   $writable $dir (permissions: $perms)\n";
    } else {
        echo "   ✗ $dir (not found)\n";
    }
}

echo "\n=== Diagnostic Complete ===\n";
echo "\nNext Steps:\n";
echo "1. Check your WordPress error log (usually in wp-content/debug.log)\n";
echo "2. Enable WP_DEBUG in wp-config.php if not already enabled\n";
echo "3. Check your web server error log (Apache/Nginx)\n";
echo "4. Try deactivating other plugins to check for conflicts\n";
