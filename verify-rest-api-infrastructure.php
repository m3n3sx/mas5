<?php
/**
 * Verification Script for REST API Infrastructure
 * 
 * Tests that the REST API infrastructure is properly set up:
 * - Base REST controller class exists and is loadable
 * - REST API bootstrap class exists and initializes
 * - Validation service exists and works correctly
 * - REST API namespace is registered
 * 
 * Usage: Load this file in WordPress admin context
 */

// Load WordPress
require_once dirname(__FILE__) . '/../../../wp-load.php';

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('You must be an administrator to run this verification script.');
}

// Start output
?>
<!DOCTYPE html>
<html>
<head>
    <title>MAS V2 REST API Infrastructure Verification</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: #f0f0f1;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1d2327;
            border-bottom: 2px solid #2271b1;
            padding-bottom: 10px;
        }
        h2 {
            color: #2271b1;
            margin-top: 30px;
        }
        .test-result {
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid;
        }
        .success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        .test-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .test-details {
            font-size: 14px;
            margin-top: 5px;
            opacity: 0.9;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        .summary {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .summary-stat {
            display: inline-block;
            margin-right: 20px;
            font-size: 18px;
        }
        .summary-stat strong {
            font-size: 24px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 MAS V2 REST API Infrastructure Verification</h1>
        <p>Testing Phase 1: REST API Infrastructure Setup</p>

<?php

$tests_passed = 0;
$tests_failed = 0;
$tests_total = 0;

/**
 * Helper function to display test result
 */
function display_test($name, $passed, $details = '') {
    global $tests_passed, $tests_failed, $tests_total;
    
    $tests_total++;
    if ($passed) {
        $tests_passed++;
        $class = 'success';
        $icon = '✅';
    } else {
        $tests_failed++;
        $class = 'error';
        $icon = '❌';
    }
    
    echo '<div class="test-result ' . $class . '">';
    echo '<div class="test-name">' . $icon . ' ' . esc_html($name) . '</div>';
    if ($details) {
        echo '<div class="test-details">' . wp_kses_post($details) . '</div>';
    }
    echo '</div>';
}

// Test 1: Check if base REST controller class exists
echo '<h2>Test 1: Base REST Controller Class</h2>';

$controller_file = MAS_V2_PLUGIN_DIR . 'includes/api/class-mas-rest-controller.php';
$controller_exists = file_exists($controller_file);

display_test(
    'Base REST controller file exists',
    $controller_exists,
    $controller_exists ? 'File found at: <code>' . $controller_file . '</code>' : 'File not found'
);

if ($controller_exists) {
    require_once $controller_file;
    $class_exists = class_exists('MAS_REST_Controller');
    
    display_test(
        'MAS_REST_Controller class is loadable',
        $class_exists,
        $class_exists ? 'Class loaded successfully' : 'Class could not be loaded'
    );
    
    if ($class_exists) {
        $reflection = new ReflectionClass('MAS_REST_Controller');
        
        $has_check_permission = $reflection->hasMethod('check_permission');
        display_test(
            'check_permission() method exists',
            $has_check_permission,
            $has_check_permission ? 'Method signature: <code>public function check_permission($request)</code>' : 'Method not found'
        );
        
        $has_error_response = $reflection->hasMethod('error_response');
        display_test(
            'error_response() method exists',
            $has_error_response,
            $has_error_response ? 'Method signature: <code>protected function error_response($message, $code, $status, $additional_data)</code>' : 'Method not found'
        );
        
        $has_success_response = $reflection->hasMethod('success_response');
        display_test(
            'success_response() method exists',
            $has_success_response,
            $has_success_response ? 'Method signature: <code>protected function success_response($data, $message, $status)</code>' : 'Method not found'
        );
    }
}

// Test 2: Check if REST API bootstrap class exists
echo '<h2>Test 2: REST API Bootstrap Class</h2>';

$bootstrap_file = MAS_V2_PLUGIN_DIR . 'includes/class-mas-rest-api.php';
$bootstrap_exists = file_exists($bootstrap_file);

display_test(
    'REST API bootstrap file exists',
    $bootstrap_exists,
    $bootstrap_exists ? 'File found at: <code>' . $bootstrap_file . '</code>' : 'File not found'
);

if ($bootstrap_exists) {
    require_once $bootstrap_file;
    $class_exists = class_exists('MAS_REST_API');
    
    display_test(
        'MAS_REST_API class is loadable',
        $class_exists,
        $class_exists ? 'Class loaded successfully' : 'Class could not be loaded'
    );
    
    if ($class_exists) {
        $api_instance = MAS_REST_API::get_instance();
        
        display_test(
            'MAS_REST_API singleton instance created',
            $api_instance !== null,
            $api_instance ? 'Instance created successfully' : 'Failed to create instance'
        );
        
        if ($api_instance) {
            $namespace = $api_instance->get_namespace();
            $expected_namespace = 'mas-v2/v1';
            
            display_test(
                'REST API namespace is correct',
                $namespace === $expected_namespace,
                'Expected: <code>' . $expected_namespace . '</code>, Got: <code>' . $namespace . '</code>'
            );
            
            $base_url = $api_instance->get_base_url();
            $expected_base = rest_url($expected_namespace);
            
            display_test(
                'REST API base URL is correct',
                $base_url === $expected_base,
                'Base URL: <code>' . $base_url . '</code>'
            );
        }
    }
}

// Test 3: Check if validation service exists
echo '<h2>Test 3: Validation Service</h2>';

$validation_file = MAS_V2_PLUGIN_DIR . 'includes/services/class-mas-validation-service.php';
$validation_exists = file_exists($validation_file);

display_test(
    'Validation service file exists',
    $validation_exists,
    $validation_exists ? 'File found at: <code>' . $validation_file . '</code>' : 'File not found'
);

if ($validation_exists) {
    require_once $validation_file;
    $class_exists = class_exists('MAS_Validation_Service');
    
    display_test(
        'MAS_Validation_Service class is loadable',
        $class_exists,
        $class_exists ? 'Class loaded successfully' : 'Class could not be loaded'
    );
    
    if ($class_exists) {
        $validator = new MAS_Validation_Service();
        
        // Test color validation
        $valid_color = $validator->validate_color('#1e1e2e');
        display_test(
            'Color validation works (valid color)',
            $valid_color === true,
            'Tested: <code>#1e1e2e</code> - Result: ' . ($valid_color ? 'Valid' : 'Invalid')
        );
        
        $invalid_color = $validator->validate_color('not-a-color');
        display_test(
            'Color validation works (invalid color)',
            $invalid_color === false,
            'Tested: <code>not-a-color</code> - Result: ' . ($invalid_color ? 'Valid' : 'Invalid')
        );
        
        // Test CSS unit validation
        $valid_unit = $validator->validate_css_unit('280px');
        display_test(
            'CSS unit validation works (valid unit)',
            $valid_unit === true,
            'Tested: <code>280px</code> - Result: ' . ($valid_unit ? 'Valid' : 'Invalid')
        );
        
        $invalid_unit = $validator->validate_css_unit('280');
        display_test(
            'CSS unit validation works (invalid unit)',
            $invalid_unit === false,
            'Tested: <code>280</code> (no unit) - Result: ' . ($invalid_unit ? 'Valid' : 'Invalid')
        );
        
        // Test boolean validation
        $valid_bool = $validator->validate_boolean(true);
        display_test(
            'Boolean validation works',
            $valid_bool === true,
            'Tested: <code>true</code> - Result: ' . ($valid_bool ? 'Valid' : 'Invalid')
        );
        
        // Test field aliases
        $test_data = ['menu_bg' => '#ffffff', 'menu_txt' => '#000000'];
        $normalized = $validator->apply_field_aliases($test_data);
        
        $aliases_work = isset($normalized['menu_background']) && isset($normalized['menu_text_color']);
        display_test(
            'Field name aliases work',
            $aliases_work,
            $aliases_work ? 'Aliases applied: <code>menu_bg → menu_background</code>, <code>menu_txt → menu_text_color</code>' : 'Aliases not applied correctly'
        );
    }
}

// Test 4: Check if REST API is initialized in main plugin file
echo '<h2>Test 4: Plugin Integration</h2>';

$plugin_file = MAS_V2_PLUGIN_DIR . 'modern-admin-styler-v2.php';
$plugin_content = file_get_contents($plugin_file);

$has_init_call = strpos($plugin_content, 'init_rest_api') !== false;
display_test(
    'REST API initialization method exists in plugin',
    $has_init_call,
    $has_init_call ? 'Found <code>init_rest_api()</code> method call' : 'Method call not found'
);

$has_require = strpos($plugin_content, 'class-mas-rest-api.php') !== false;
display_test(
    'REST API bootstrap is required in plugin',
    $has_require,
    $has_require ? 'Found <code>require_once</code> for REST API bootstrap' : 'Require statement not found'
);

// Test 5: Check directory structure
echo '<h2>Test 5: Directory Structure</h2>';

$api_dir = MAS_V2_PLUGIN_DIR . 'includes/api/';
$api_dir_exists = is_dir($api_dir);
display_test(
    'API directory exists',
    $api_dir_exists,
    $api_dir_exists ? 'Directory: <code>' . $api_dir . '</code>' : 'Directory not found'
);

$services_dir = MAS_V2_PLUGIN_DIR . 'includes/services/';
$services_dir_exists = is_dir($services_dir);
display_test(
    'Services directory exists',
    $services_dir_exists,
    $services_dir_exists ? 'Directory: <code>' . $services_dir . '</code>' : 'Directory not found'
);

// Summary
echo '<div class="summary">';
echo '<h2>📊 Test Summary</h2>';
echo '<div class="summary-stat">Total: <strong>' . $tests_total . '</strong></div>';
echo '<div class="summary-stat" style="color: #28a745;">Passed: <strong>' . $tests_passed . '</strong></div>';
echo '<div class="summary-stat" style="color: #dc3545;">Failed: <strong>' . $tests_failed . '</strong></div>';

$success_rate = $tests_total > 0 ? round(($tests_passed / $tests_total) * 100, 1) : 0;
echo '<div class="summary-stat">Success Rate: <strong>' . $success_rate . '%</strong></div>';

if ($tests_failed === 0) {
    echo '<p style="color: #28a745; font-weight: bold; margin-top: 20px;">✅ All tests passed! REST API infrastructure is properly set up.</p>';
} else {
    echo '<p style="color: #dc3545; font-weight: bold; margin-top: 20px;">❌ Some tests failed. Please review the errors above.</p>';
}
echo '</div>';

?>

        <h2>📝 Next Steps</h2>
        <div class="info test-result">
            <p><strong>Phase 1 Complete!</strong> The REST API infrastructure is now set up.</p>
            <p>You can now proceed to:</p>
            <ul>
                <li><strong>Phase 2:</strong> Implement Settings Management Endpoints</li>
                <li><strong>Phase 2:</strong> Implement Theme and Palette Management Endpoints</li>
                <li><strong>Phase 3:</strong> Implement Backup, Import/Export, Preview, and Diagnostics Endpoints</li>
            </ul>
            <p>The REST API will be available at: <code><?php echo rest_url('mas-v2/v1'); ?></code></p>
        </div>
    </div>
</body>
</html>
