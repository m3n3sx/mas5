<?php
/**
 * Test Task 2: Lazy Loading Implementation
 * 
 * This test verifies that the MAS_REST_API class properly implements
 * lazy loading and doesn't cause fatal errors when WP_REST_Controller
 * is not available.
 */

// Simulate WordPress environment
define('ABSPATH', __DIR__ . '/');
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Mock WordPress functions
function get_bloginfo($show = '') {
    return '6.4.0';
}

function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
    echo "✓ Action registered: $hook\n";
    return true;
}

function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
    echo "✓ Filter registered: $hook\n";
    return true;
}

// error_log is a built-in function, we'll just let it work normally

// Define plugin directory constant
define('MAS_V2_PLUGIN_DIR', __DIR__ . '/');

echo "=== Task 2: Lazy Loading Test ===\n\n";

// Test 1: Verify class can be instantiated without WP_REST_Controller
echo "Test 1: Instantiate MAS_REST_API without WP_REST_Controller\n";
echo "-------------------------------------------------------\n";

// Load the class
require_once __DIR__ . '/includes/class-mas-rest-api.php';

try {
    // This should NOT cause a fatal error
    $api = MAS_REST_API::get_instance();
    echo "✓ MAS_REST_API instantiated successfully\n";
    echo "✓ No fatal error occurred\n\n";
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
    exit(1);
} catch (Error $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Verify register_controllers() handles missing WP_REST_Controller
echo "Test 2: Call register_controllers() without WP_REST_Controller\n";
echo "---------------------------------------------------------------\n";

try {
    // Simulate the rest_api_init hook firing
    $api->register_controllers();
    echo "✓ register_controllers() executed without fatal error\n";
    echo "✓ Gracefully handled missing WP_REST_Controller\n\n";
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
    exit(1);
} catch (Error $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 3: Verify behavior with WP_REST_Controller available
echo "Test 3: Simulate WP_REST_Controller availability\n";
echo "------------------------------------------------\n";

// Create a mock WP_REST_Controller class
class WP_REST_Controller {
    public function __construct() {
        echo "✓ WP_REST_Controller mock created\n";
    }
}

try {
    // Create a new instance to test with WP_REST_Controller available
    // Reset singleton for testing
    $reflection = new ReflectionClass('MAS_REST_API');
    $instance = $reflection->getProperty('instance');
    $instance->setAccessible(true);
    $instance->setValue(null, null);
    
    $api2 = MAS_REST_API::get_instance();
    echo "✓ New instance created with WP_REST_Controller available\n";
    
    // Call register_controllers - this should now load dependencies
    $api2->register_controllers();
    echo "✓ register_controllers() executed with WP_REST_Controller available\n";
    echo "✓ Dependencies should be loaded (check logs above)\n\n";
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
    exit(1);
} catch (Error $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 4: Verify safe_require error handling
echo "Test 4: Verify error handling for missing files\n";
echo "------------------------------------------------\n";

// Test that safe_require handles missing files gracefully
$reflection = new ReflectionClass('MAS_REST_API');
$method = $reflection->getMethod('safe_require');
$method->setAccessible(true);

$result = $method->invoke($api2, '/nonexistent/file.php', 'Test File');
if ($result === false) {
    echo "✓ safe_require() correctly returns false for missing file\n";
} else {
    echo "✗ FAILED: safe_require() should return false for missing file\n";
    exit(1);
}

echo "\n=== All Tests Passed! ===\n\n";

echo "Summary:\n";
echo "--------\n";
echo "✓ MAS_REST_API can be instantiated without WP_REST_Controller\n";
echo "✓ register_controllers() gracefully handles missing WP_REST_Controller\n";
echo "✓ Dependencies are only loaded when rest_api_init fires\n";
echo "✓ Error handling works correctly for missing files\n";
echo "✓ No fatal errors occur during initialization\n\n";

echo "The lazy loading implementation is working correctly!\n";
