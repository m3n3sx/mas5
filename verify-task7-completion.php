<?php
/**
 * Verification Script for Task 7 Completion
 * 
 * Quick verification that all Task 7 components are in place and functional.
 * 
 * @package ModernAdminStylerV2
 * @since 2.2.0
 */

// Load WordPress
require_once dirname(__FILE__) . '/../../../wp-load.php';

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('You must be an administrator to run this verification.');
}

echo "Task 7: Diagnostics and Health Check Endpoint - Verification\n";
echo str_repeat('=', 70) . "\n\n";

$all_passed = true;

// Check 1: Diagnostics Service File
echo "1. Checking diagnostics service file... ";
$service_file = __DIR__ . '/includes/services/class-mas-diagnostics-service.php';
if (file_exists($service_file)) {
    echo "✓ PASS\n";
} else {
    echo "✗ FAIL\n";
    $all_passed = false;
}

// Check 2: Diagnostics Controller File
echo "2. Checking diagnostics controller file... ";
$controller_file = __DIR__ . '/includes/api/class-mas-diagnostics-controller.php';
if (file_exists($controller_file)) {
    echo "✓ PASS\n";
} else {
    echo "✗ FAIL\n";
    $all_passed = false;
}

// Check 3: DiagnosticsManager Module
echo "3. Checking DiagnosticsManager module... ";
$manager_file = __DIR__ . '/assets/js/modules/DiagnosticsManager.js';
if (file_exists($manager_file)) {
    echo "✓ PASS\n";
} else {
    echo "✗ FAIL\n";
    $all_passed = false;
}

// Check 4: REST Client Methods
echo "4. Checking REST client diagnostics methods... ";
$rest_client_file = __DIR__ . '/assets/js/mas-rest-client.js';
if (file_exists($rest_client_file)) {
    $content = file_get_contents($rest_client_file);
    $has_methods = strpos($content, 'getDiagnostics') !== false &&
                   strpos($content, 'getHealthCheck') !== false &&
                   strpos($content, 'getPerformanceMetrics') !== false;
    
    if ($has_methods) {
        echo "✓ PASS\n";
    } else {
        echo "✗ FAIL\n";
        $all_passed = false;
    }
} else {
    echo "✗ FAIL\n";
    $all_passed = false;
}

// Check 5: Service Class Exists
echo "5. Checking MAS_Diagnostics_Service class... ";
require_once $service_file;
if (class_exists('MAS_Diagnostics_Service')) {
    echo "✓ PASS\n";
} else {
    echo "✗ FAIL\n";
    $all_passed = false;
}

// Check 6: Controller Class Exists
echo "6. Checking MAS_Diagnostics_Controller class... ";
require_once __DIR__ . '/includes/api/class-mas-rest-controller.php';
require_once $controller_file;
if (class_exists('MAS_Diagnostics_Controller')) {
    echo "✓ PASS\n";
} else {
    echo "✗ FAIL\n";
    $all_passed = false;
}

// Check 7: REST API Routes
echo "7. Checking REST API routes... ";
$routes = rest_get_server()->get_routes();
$has_routes = isset($routes['/mas-v2/v1/diagnostics']) &&
              isset($routes['/mas-v2/v1/diagnostics/health']) &&
              isset($routes['/mas-v2/v1/diagnostics/performance']);

if ($has_routes) {
    echo "✓ PASS\n";
} else {
    echo "✗ FAIL\n";
    $all_passed = false;
}

// Check 8: Service Functionality
echo "8. Testing diagnostics service functionality... ";
try {
    $service = new MAS_Diagnostics_Service();
    $diagnostics = $service->get_diagnostics();
    
    $required_sections = ['system', 'plugin', 'settings', 'filesystem', 'conflicts', 'performance', 'recommendations'];
    $has_all_sections = true;
    
    foreach ($required_sections as $section) {
        if (!isset($diagnostics[$section])) {
            $has_all_sections = false;
            break;
        }
    }
    
    if ($has_all_sections) {
        echo "✓ PASS\n";
    } else {
        echo "✗ FAIL\n";
        $all_passed = false;
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    $all_passed = false;
}

// Check 9: Endpoint Responses
echo "9. Testing endpoint responses... ";
try {
    $request = new WP_REST_Request('GET', '/mas-v2/v1/diagnostics');
    $response = rest_do_request($request);
    
    if ($response->get_status() === 200) {
        $data = $response->get_data();
        if (isset($data['success']) && $data['success'] && isset($data['data'])) {
            echo "✓ PASS\n";
        } else {
            echo "✗ FAIL: Invalid response structure\n";
            $all_passed = false;
        }
    } else {
        echo "✗ FAIL: Status " . $response->get_status() . "\n";
        $all_passed = false;
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    $all_passed = false;
}

// Check 10: Documentation
echo "10. Checking documentation... ";
$doc_file = __DIR__ . '/DIAGNOSTICS-API-QUICK-REFERENCE.md';
$completion_file = __DIR__ . '/TASK-7-DIAGNOSTICS-COMPLETION.md';
if (file_exists($doc_file) && file_exists($completion_file)) {
    echo "✓ PASS\n";
} else {
    echo "✗ FAIL\n";
    $all_passed = false;
}

// Final Result
echo "\n" . str_repeat('=', 70) . "\n";
if ($all_passed) {
    echo "✓ ALL CHECKS PASSED - Task 7 is complete!\n";
    exit(0);
} else {
    echo "✗ SOME CHECKS FAILED - Please review the errors above.\n";
    exit(1);
}
