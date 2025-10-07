<?php
/**
 * Test Script for Task 5: Import/Export Endpoints
 * 
 * This script verifies the implementation of import/export REST API endpoints.
 * 
 * Usage: php test-task5-import-export.php
 */

// Load WordPress
require_once __DIR__ . '/modern-admin-styler-v2.php';

// Define plugin directory constant if not defined
if (!defined('MAS_V2_PLUGIN_DIR')) {
    define('MAS_V2_PLUGIN_DIR', __DIR__ . '/');
}

echo "=== Task 5: Import/Export Endpoints Test ===\n\n";

// Test 1: Check if service class exists
echo "Test 1: Checking if Import/Export Service class exists...\n";
$service_file = MAS_V2_PLUGIN_DIR . 'includes/services/class-mas-import-export-service.php';
if (file_exists($service_file)) {
    require_once $service_file;
    if (class_exists('MAS_Import_Export_Service')) {
        echo "✓ MAS_Import_Export_Service class exists\n";
    } else {
        echo "✗ MAS_Import_Export_Service class not found\n";
    }
} else {
    echo "✗ Service file not found: $service_file\n";
}

// Test 2: Check if controller class exists
echo "\nTest 2: Checking if Import/Export Controller class exists...\n";
$controller_file = MAS_V2_PLUGIN_DIR . 'includes/api/class-mas-import-export-controller.php';
if (file_exists($controller_file)) {
    require_once $controller_file;
    if (class_exists('MAS_Import_Export_Controller')) {
        echo "✓ MAS_Import_Export_Controller class exists\n";
    } else {
        echo "✗ MAS_Import_Export_Controller class not found\n";
    }
} else {
    echo "✗ Controller file not found: $controller_file\n";
}

// Test 3: Check service methods
echo "\nTest 3: Checking Import/Export Service methods...\n";
if (class_exists('MAS_Import_Export_Service')) {
    $required_methods = [
        'export_settings',
        'import_settings',
        'validate_json',
        'get_export_filename',
    ];
    
    foreach ($required_methods as $method) {
        if (method_exists('MAS_Import_Export_Service', $method)) {
            echo "✓ Method exists: $method\n";
        } else {
            echo "✗ Method missing: $method\n";
        }
    }
}

// Test 4: Check controller methods
echo "\nTest 4: Checking Import/Export Controller methods...\n";
if (class_exists('MAS_Import_Export_Controller')) {
    $required_methods = [
        'register_routes',
        'export_settings',
        'import_settings',
    ];
    
    foreach ($required_methods as $method) {
        if (method_exists('MAS_Import_Export_Controller', $method)) {
            echo "✓ Method exists: $method\n";
        } else {
            echo "✗ Method missing: $method\n";
        }
    }
}

// Test 5: Check JavaScript client methods
echo "\nTest 5: Checking JavaScript client import/export methods...\n";
$js_client_file = MAS_V2_PLUGIN_DIR . 'assets/js/mas-rest-client.js';
if (file_exists($js_client_file)) {
    $js_content = file_get_contents($js_client_file);
    
    $required_methods = [
        'exportSettings',
        'importSettings',
        'importSettingsFromFile',
        'triggerDownload',
        'readFileAsJSON',
        'validateImportData',
    ];
    
    foreach ($required_methods as $method) {
        if (strpos($js_content, "async $method(") !== false || strpos($js_content, "$method(") !== false) {
            echo "✓ JavaScript method exists: $method\n";
        } else {
            echo "✗ JavaScript method missing: $method\n";
        }
    }
} else {
    echo "✗ JavaScript client file not found\n";
}

// Test 6: Check integration test file
echo "\nTest 6: Checking integration test file...\n";
$test_file = MAS_V2_PLUGIN_DIR . 'tests/php/rest-api/TestMASImportExportIntegration.php';
if (file_exists($test_file)) {
    echo "✓ Integration test file exists\n";
    
    $test_content = file_get_contents($test_file);
    $test_methods = [
        'test_export_with_admin_user',
        'test_export_has_proper_headers',
        'test_import_with_valid_data',
        'test_import_with_invalid_data_structure',
        'test_import_creates_automatic_backup',
        'test_import_with_legacy_format',
        'test_import_with_field_aliases',
    ];
    
    foreach ($test_methods as $method) {
        if (strpos($test_content, "function $method(") !== false) {
            echo "✓ Test method exists: $method\n";
        } else {
            echo "✗ Test method missing: $method\n";
        }
    }
} else {
    echo "✗ Integration test file not found\n";
}

// Test 7: Verify service functionality (if WordPress is loaded)
echo "\nTest 7: Testing service functionality...\n";
if (class_exists('MAS_Import_Export_Service')) {
    try {
        // Load dependencies
        require_once MAS_V2_PLUGIN_DIR . 'includes/services/class-mas-settings-service.php';
        require_once MAS_V2_PLUGIN_DIR . 'includes/services/class-mas-backup-service.php';
        require_once MAS_V2_PLUGIN_DIR . 'includes/services/class-mas-validation-service.php';
        
        $service = MAS_Import_Export_Service::get_instance();
        
        // Test export
        $export_data = $service->export_settings(true);
        if (isset($export_data['settings']) && isset($export_data['metadata'])) {
            echo "✓ Export functionality works\n";
            echo "  - Settings count: " . count($export_data['settings']) . "\n";
            echo "  - Export version: " . $export_data['metadata']['export_version'] . "\n";
        } else {
            echo "✗ Export data structure invalid\n";
        }
        
        // Test filename generation
        $filename = $service->get_export_filename();
        if (strpos($filename, '.json') !== false) {
            echo "✓ Filename generation works: $filename\n";
        } else {
            echo "✗ Filename generation failed\n";
        }
        
        // Test JSON validation
        $valid_json = '{"settings": {"menu_background": "#1e1e2e"}}';
        $result = $service->validate_json($valid_json);
        if (is_array($result)) {
            echo "✓ JSON validation works\n";
        } else {
            echo "✗ JSON validation failed\n";
        }
        
        // Test invalid JSON
        $invalid_json = '{invalid}';
        $result = $service->validate_json($invalid_json);
        if (is_wp_error($result)) {
            echo "✓ Invalid JSON detection works\n";
        } else {
            echo "✗ Invalid JSON not detected\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Service functionality test failed: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Test Summary ===\n";
echo "Task 5 implementation verification complete.\n";
echo "\nTo run the full integration tests, use:\n";
echo "  cd tests/php/rest-api\n";
echo "  phpunit TestMASImportExportIntegration.php\n";

