<?php
/**
 * Test Plugin Loading
 * 
 * This script attempts to load the plugin in isolation to identify the exact error.
 * Run from command line: php test-plugin-load.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "Testing Modern Admin Styler V2 Plugin Loading...\n\n";

// Step 1: Define WordPress constants
echo "Step 1: Defining WordPress constants...\n";
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}
define('WPINC', 'wp-includes');
define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
echo "✓ Constants defined\n\n";

// Step 2: Mock WordPress functions
echo "Step 2: Mocking WordPress functions...\n";

// Mock class for WP_REST_Controller
if (!class_exists('WP_REST_Controller')) {
    class WP_REST_Controller {
        protected $namespace = '';
        protected $rest_base = '';
        public function register_routes() {}
    }
    echo "✓ WP_REST_Controller mocked\n";
}

// Mock class for WP_Error
if (!class_exists('WP_Error')) {
    class WP_Error {
        public $errors = [];
        public function __construct($code = '', $message = '', $data = '') {
            if (!empty($code)) {
                $this->errors[$code][] = $message;
            }
        }
    }
    echo "✓ WP_Error mocked\n";
}

// Mock WordPress functions
$mocked_functions = [
    'plugin_dir_path' => function($file) { return dirname($file) . '/'; },
    'plugin_dir_url' => function($file) { return 'http://localhost/'; },
    'add_action' => function($hook, $callback, $priority = 10, $args = 1) { return true; },
    'add_filter' => function($hook, $callback, $priority = 10, $args = 1) { return true; },
    'register_activation_hook' => function($file, $callback) { return true; },
    'register_deactivation_hook' => function($file, $callback) { return true; },
    'get_option' => function($option, $default = false) { return $default; },
    'update_option' => function($option, $value) { return true; },
    'add_option' => function($option, $value) { return true; },
    'delete_option' => function($option) { return true; },
    'wp_create_nonce' => function($action) { return 'test_nonce'; },
    'wp_verify_nonce' => function($nonce, $action) { return true; },
    'current_user_can' => function($capability) { return true; },
    'is_admin' => function() { return true; },
    'admin_url' => function($path = '') { return 'http://localhost/wp-admin/' . $path; },
    'rest_url' => function($path = '') { return 'http://localhost/wp-json/' . $path; },
    'register_rest_route' => function($namespace, $route, $args) { return true; },
    'get_transient' => function($transient) { return false; },
    'set_transient' => function($transient, $value, $expiration = 0) { return true; },
    'delete_transient' => function($transient) { return true; },
    'load_plugin_textdomain' => function($domain, $deprecated, $plugin_rel_path) { return true; },
    'add_menu_page' => function() { return true; },
    'add_submenu_page' => function() { return true; },
    'wp_enqueue_script' => function() { return true; },
    'wp_enqueue_style' => function() { return true; },
    'wp_localize_script' => function() { return true; },
    'get_bloginfo' => function($show = '') { return '6.4'; },
    'is_user_logged_in' => function() { return true; },
    'get_current_user_id' => function() { return 1; },
    'get_current_screen' => function() { return null; },
    'is_plugin_active' => function($plugin) { return false; },
    'deactivate_plugins' => function($plugins) { return true; },
    'wp_die' => function($message) { die($message); },
    'esc_html' => function($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); },
    'esc_attr' => function($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); },
    'esc_url' => function($url) { return $url; },
    'esc_js' => function($text) { return addslashes($text); },
    '__' => function($text, $domain = 'default') { return $text; },
    'error_log' => function($message) { echo "[LOG] $message\n"; },
];

foreach ($mocked_functions as $func_name => $func_callback) {
    if (!function_exists($func_name)) {
        eval("function $func_name(...\$args) { return call_user_func_array(\$GLOBALS['mocked_functions']['$func_name'], \$args); };");
    }
}
$GLOBALS['mocked_functions'] = $mocked_functions;
echo "✓ " . count($mocked_functions) . " functions mocked\n\n";

// Step 3: Try to load the plugin
echo "Step 3: Loading plugin file...\n";
try {
    require_once __DIR__ . '/modern-admin-styler-v2.php';
    echo "✓ Plugin file loaded successfully\n\n";
    
    // Step 4: Try to instantiate the main class
    echo "Step 4: Instantiating main class...\n";
    if (class_exists('ModernAdminStylerV2')) {
        echo "✓ ModernAdminStylerV2 class exists\n";
        
        try {
            $instance = ModernAdminStylerV2::getInstance();
            echo "✓ Plugin instance created successfully\n";
            echo "\n=== SUCCESS ===\n";
            echo "The plugin loads without errors in isolation.\n";
            echo "The issue may be:\n";
            echo "1. A conflict with another plugin\n";
            echo "2. A WordPress configuration issue\n";
            echo "3. A server/PHP configuration issue\n";
            echo "4. A database connection issue\n";
        } catch (Exception $e) {
            echo "✗ EXCEPTION during instantiation:\n";
            echo "   Message: " . $e->getMessage() . "\n";
            echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
            echo "   Trace:\n" . $e->getTraceAsString() . "\n";
        } catch (Error $e) {
            echo "✗ FATAL ERROR during instantiation:\n";
            echo "   Message: " . $e->getMessage() . "\n";
            echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
            echo "   Trace:\n" . $e->getTraceAsString() . "\n";
        }
    } else {
        echo "✗ ModernAdminStylerV2 class not found\n";
    }
    
} catch (Exception $e) {
    echo "✗ EXCEPTION during file loading:\n";
    echo "   Message: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "✗ FATAL ERROR during file loading:\n";
    echo "   Message: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
