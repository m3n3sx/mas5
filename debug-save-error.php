<?php
/**
 * Debug Save Error
 * 
 * This script helps diagnose the 500 Internal Server Error when saving settings
 */

// Load WordPress
$wp_load_paths = [
    __DIR__ . '/../../../wp-load.php',
    __DIR__ . '/../../../../wp-load.php',
    __DIR__ . '/../../../../../wp-load.php',
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die('Error: Could not find wp-load.php');
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>MAS V2 Save Settings Debug</h1>\n";
echo "<pre>\n";

// Test 1: Check if REST API endpoint exists
echo "=== TEST 1: REST API Endpoint ===\n";
$rest_url = rest_url('mas/v2/settings');
echo "REST URL: $rest_url\n";

// Test 2: Check if REST API class exists
echo "\n=== TEST 2: REST API Class ===\n";
$rest_api_file = __DIR__ . '/includes/class-mas-rest-api.php';
if (file_exists($rest_api_file)) {
    echo "✓ REST API file exists\n";
    require_once $rest_api_file;
    
    if (class_exists('MAS_REST_API')) {
        echo "✓ MAS_REST_API class exists\n";
        
        // Try to get singleton instance
        try {
            $rest_api = MAS_REST_API::get_instance();
            echo "✓ MAS_REST_API instantiated successfully\n";
        } catch (Exception $e) {
            echo "✗ Error instantiating: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✗ MAS_REST_API class not found\n";
    }
} else {
    echo "✗ REST API file not found: $rest_api_file\n";
}

// Test 3: Check AJAX handlers
echo "\n=== TEST 3: AJAX Handlers ===\n";
$ajax_actions = [
    'mas_v2_save_settings',
    'mas_v2_get_preview_css',
    'mas_v2_export_settings',
    'mas_v2_import_settings'
];

foreach ($ajax_actions as $action) {
    if (has_action("wp_ajax_$action")) {
        echo "✓ Handler registered: $action\n";
    } else {
        echo "✗ Handler NOT registered: $action\n";
    }
}

// Test 4: Check main plugin file
echo "\n=== TEST 4: Main Plugin File ===\n";
$plugin_file = __DIR__ . '/modern-admin-styler-v2.php';
if (file_exists($plugin_file)) {
    echo "✓ Plugin file exists\n";
    
    // Check if plugin is active
    if (is_plugin_active('modern-admin-styler-v2/modern-admin-styler-v2.php')) {
        echo "✓ Plugin is active\n";
    } else {
        echo "⚠ Plugin is NOT active\n";
    }
} else {
    echo "✗ Plugin file not found\n";
}

// Test 5: Try to save settings manually
echo "\n=== TEST 5: Manual Save Test ===\n";
try {
    $test_settings = [
        'admin_bar_bg' => '#2271b1',
        'test_field' => 'test_value_' . time()
    ];
    
    echo "Attempting to save test settings...\n";
    $result = update_option('mas_v2_settings', $test_settings);
    
    if ($result) {
        echo "✓ Settings saved successfully\n";
        
        // Verify
        $retrieved = get_option('mas_v2_settings');
        if ($retrieved === $test_settings) {
            echo "✓ Settings retrieved correctly\n";
        } else {
            echo "⚠ Settings retrieved but don't match\n";
            echo "Expected: " . print_r($test_settings, true) . "\n";
            echo "Got: " . print_r($retrieved, true) . "\n";
        }
    } else {
        echo "⚠ update_option returned false (settings may be unchanged)\n";
    }
} catch (Exception $e) {
    echo "✗ Error saving: " . $e->getMessage() . "\n";
}

// Test 6: Check PHP error log
echo "\n=== TEST 6: Recent PHP Errors ===\n";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    echo "Error log location: $error_log\n";
    echo "Last 20 lines:\n";
    echo "---\n";
    $lines = file($error_log);
    $recent = array_slice($lines, -20);
    echo implode('', $recent);
} else {
    // Try WordPress debug.log
    $wp_debug_log = WP_CONTENT_DIR . '/debug.log';
    if (file_exists($wp_debug_log)) {
        echo "WordPress debug.log location: $wp_debug_log\n";
        echo "Last 20 lines:\n";
        echo "---\n";
        $lines = file($wp_debug_log);
        $recent = array_slice($lines, -20);
        echo implode('', $recent);
    } else {
        echo "No error log found. Enable WP_DEBUG_LOG in wp-config.php:\n";
        echo "define('WP_DEBUG', true);\n";
        echo "define('WP_DEBUG_LOG', true);\n";
        echo "define('WP_DEBUG_DISPLAY', false);\n";
    }
}

// Test 7: Check for fatal errors in REST API
echo "\n=== TEST 7: Test REST API Endpoint Directly ===\n";
if (class_exists('MAS_REST_API')) {
    try {
        // Simulate REST request
        $request = new WP_REST_Request('POST', '/mas/v2/settings');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(json_encode([
            'admin_bar_bg' => '#2271b1',
            'test_field' => 'test_value'
        ]));
        
        // Get REST server
        $server = rest_get_server();
        
        echo "Attempting REST request...\n";
        $response = $server->dispatch($request);
        
        if (is_wp_error($response)) {
            echo "✗ REST Error: " . $response->get_error_message() . "\n";
        } else {
            echo "✓ REST request successful\n";
            echo "Response: " . print_r($response->get_data(), true) . "\n";
        }
    } catch (Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    } catch (Error $e) {
        echo "✗ Fatal Error: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
} else {
    echo "⚠ Cannot test - MAS_REST_API class not available\n";
}

// Test 8: Check for syntax errors in key files
echo "\n=== TEST 8: Syntax Check ===\n";
$files_to_check = [
    'includes/class-mas-rest-api.php',
    'includes/api/class-mas-settings-controller.php',
    'modern-admin-styler-v2.php'
];

foreach ($files_to_check as $file) {
    $full_path = __DIR__ . '/' . $file;
    if (file_exists($full_path)) {
        $output = [];
        $return_var = 0;
        exec("php -l " . escapeshellarg($full_path) . " 2>&1", $output, $return_var);
        
        if ($return_var === 0) {
            echo "✓ $file - No syntax errors\n";
        } else {
            echo "✗ $file - Syntax errors:\n";
            echo implode("\n", $output) . "\n";
        }
    } else {
        echo "⚠ $file - File not found\n";
    }
}

echo "\n=== RECOMMENDATIONS ===\n";
echo "1. Check the error log output above for specific PHP errors\n";
echo "2. Enable WordPress debug mode if not already enabled\n";
echo "3. Check file permissions on includes/ directory\n";
echo "4. Verify all required classes are loaded\n";
echo "5. Check for plugin conflicts\n";

echo "\n</pre>\n";
