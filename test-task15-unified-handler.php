<?php
/**
 * Test Task 15: Unified REST API Form Handler
 * 
 * Tests the complete settings save workflow with the new unified handler
 * that eliminates dual handler conflicts.
 * 
 * @package ModernAdminStylerV2
 * @since 2.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Load WordPress
require_once ABSPATH . 'wp-load.php';

// Ensure user is logged in and has admin capabilities
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('You must be logged in as an administrator to run this test.');
}

/**
 * Test Results Class
 */
class MAS_Task15_Test_Results {
    private $tests = [];
    private $passed = 0;
    private $failed = 0;
    
    public function add_test($name, $passed, $message = '', $details = []) {
        $this->tests[] = [
            'name' => $name,
            'passed' => $passed,
            'message' => $message,
            'details' => $details
        ];
        
        if ($passed) {
            $this->passed++;
        } else {
            $this->failed++;
        }
    }
    
    public function get_summary() {
        return [
            'total' => count($this->tests),
            'passed' => $this->passed,
            'failed' => $this->failed,
            'success_rate' => count($this->tests) > 0 ? ($this->passed / count($this->tests)) * 100 : 0
        ];
    }
    
    public function get_tests() {
        return $this->tests;
    }
}

// Initialize test results
$results = new MAS_Task15_Test_Results();

// Test 1: Check if old handlers are disabled
$plugin_file = dirname(__FILE__) . '/modern-admin-styler-v2.php';
$plugin_content = file_get_contents($plugin_file);

$old_handler_disabled = strpos($plugin_content, "// wp_enqueue_script(\n        //     'mas-v2-admin-settings-simple'") !== false;
$results->add_test(
    'Old AJAX handler disabled',
    $old_handler_disabled,
    $old_handler_disabled ? 'admin-settings-simple.js is commented out' : 'Old handler still active',
    ['file' => 'modern-admin-styler-v2.php']
);

// Test 2: Check if new handler is loaded
$new_handler_loaded = strpos($plugin_content, "mas-v2-settings-form-handler") !== false;
$results->add_test(
    'New unified handler loaded',
    $new_handler_loaded,
    $new_handler_loaded ? 'mas-settings-form-handler.js is enqueued' : 'New handler not found',
    ['file' => 'modern-admin-styler-v2.php']
);

// Test 3: Check if REST client is loaded
$rest_client_loaded = strpos($plugin_content, "mas-v2-rest-client") !== false;
$results->add_test(
    'REST client loaded',
    $rest_client_loaded,
    $rest_client_loaded ? 'mas-rest-client.js is enqueued' : 'REST client not found',
    ['file' => 'modern-admin-styler-v2.php']
);

// Test 4: Check if new handler file exists
$handler_file = dirname(__FILE__) . '/assets/js/mas-settings-form-handler.js';
$handler_exists = file_exists($handler_file);
$results->add_test(
    'Handler file exists',
    $handler_exists,
    $handler_exists ? 'mas-settings-form-handler.js found' : 'Handler file missing',
    ['path' => $handler_file]
);

// Test 5: Check handler file content
if ($handler_exists) {
    $handler_content = file_get_contents($handler_file);
    
    // Check for key features
    $has_rest_support = strpos($handler_content, 'submitViaRest') !== false;
    $has_ajax_fallback = strpos($handler_content, 'submitViaAjax') !== false;
    $has_form_data_collection = strpos($handler_content, 'collectFormData') !== false;
    $has_checkbox_handling = strpos($handler_content, 'input[type="checkbox"]') !== false;
    
    $results->add_test(
        'Handler has REST support',
        $has_rest_support,
        $has_rest_support ? 'REST API submission implemented' : 'REST support missing'
    );
    
    $results->add_test(
        'Handler has AJAX fallback',
        $has_ajax_fallback,
        $has_ajax_fallback ? 'AJAX fallback implemented' : 'Fallback missing'
    );
    
    $results->add_test(
        'Handler collects form data',
        $has_form_data_collection,
        $has_form_data_collection ? 'Form data collection implemented' : 'Data collection missing'
    );
    
    $results->add_test(
        'Handler handles checkboxes',
        $has_checkbox_handling,
        $has_checkbox_handling ? 'Checkbox handling implemented' : 'Checkbox handling missing'
    );
}

// Test 6: Check deprecation notices
$simple_handler_file = dirname(__FILE__) . '/assets/js/admin-settings-simple.js';
if (file_exists($simple_handler_file)) {
    $simple_content = file_get_contents($simple_handler_file);
    $has_deprecation = strpos($simple_content, '@deprecated') !== false;
    
    $results->add_test(
        'Old handler has deprecation notice',
        $has_deprecation,
        $has_deprecation ? 'Deprecation notice added' : 'No deprecation notice',
        ['file' => 'admin-settings-simple.js']
    );
}

// Test 7: Check SettingsManager form submission disabled
$settings_manager_file = dirname(__FILE__) . '/assets/js/modules/SettingsManager.js';
if (file_exists($settings_manager_file)) {
    $settings_content = file_get_contents($settings_manager_file);
    $form_submit_disabled = strpos($settings_content, '// this.form.addEventListener(\'submit\'') !== false;
    
    $results->add_test(
        'SettingsManager form submission disabled',
        $form_submit_disabled,
        $form_submit_disabled ? 'Form submission commented out' : 'Form submission still active',
        ['file' => 'SettingsManager.js']
    );
}

// Test 8: Check REST API endpoints availability
$rest_url = rest_url('mas-v2/v1/settings');
$rest_available = !empty($rest_url);

$results->add_test(
    'REST API URL available',
    $rest_available,
    $rest_available ? "REST URL: $rest_url" : 'REST URL not available',
    ['url' => $rest_url]
);

// Test 9: Test REST API settings endpoint
if ($rest_available) {
    $request = new WP_REST_Request('GET', '/mas-v2/v1/settings');
    $response = rest_do_request($request);
    $rest_works = $response->get_status() === 200;
    
    $results->add_test(
        'REST API settings endpoint works',
        $rest_works,
        $rest_works ? 'GET /settings returns 200' : 'Endpoint failed: ' . $response->get_status(),
        [
            'status' => $response->get_status(),
            'data' => $rest_works ? 'Settings retrieved' : $response->get_data()
        ]
    );
}

// Test 10: Check masV2Global localization
$has_localization = strpos($plugin_content, "wp_localize_script('mas-v2-settings-form-handler', 'masV2Global'") !== false;
$results->add_test(
    'Script localization configured',
    $has_localization,
    $has_localization ? 'masV2Global localized for form handler' : 'Localization missing'
);

// Test 11: Verify all required settings fields
$default_settings = [
    'menu_background',
    'menu_text_color',
    'menu_hover_background',
    'menu_hover_text_color',
    'menu_active_background',
    'menu_active_text_color',
    'admin_bar_background',
    'admin_bar_text_color',
    'glassmorphism_enabled',
    'shadow_effects_enabled',
    'animations_enabled'
];

$settings_service_file = dirname(__FILE__) . '/includes/services/class-mas-settings-service.php';
if (file_exists($settings_service_file)) {
    $settings_content = file_get_contents($settings_service_file);
    $all_fields_present = true;
    $missing_fields = [];
    
    foreach ($default_settings as $field) {
        if (strpos($settings_content, "'$field'") === false) {
            $all_fields_present = false;
            $missing_fields[] = $field;
        }
    }
    
    $results->add_test(
        'All settings fields defined',
        $all_fields_present,
        $all_fields_present ? 'All required fields present' : 'Missing fields: ' . implode(', ', $missing_fields),
        ['missing' => $missing_fields]
    );
}

// Test 12: Check for duplicate handler prevention
if ($handler_exists) {
    $handler_content = file_get_contents($handler_file);
    $has_duplicate_prevention = strpos($handler_content, 'removeExistingHandlers') !== false;
    
    $results->add_test(
        'Duplicate handler prevention',
        $has_duplicate_prevention,
        $has_duplicate_prevention ? 'Handler removes existing handlers' : 'No duplicate prevention',
        ['method' => 'removeExistingHandlers']
    );
}

// Test 13: Check for loading state management
if ($handler_exists) {
    $has_loading_state = strpos($handler_content, 'setLoadingState') !== false;
    
    $results->add_test(
        'Loading state management',
        $has_loading_state,
        $has_loading_state ? 'Loading states implemented' : 'No loading state management'
    );
}

// Test 14: Check for error handling
if ($handler_exists) {
    $has_error_handling = strpos($handler_content, 'handleError') !== false;
    
    $results->add_test(
        'Error handling implemented',
        $has_error_handling,
        $has_error_handling ? 'Error handling present' : 'No error handling'
    );
}

// Test 15: Check for success feedback
if ($handler_exists) {
    $has_success_feedback = strpos($handler_content, 'handleSuccess') !== false;
    
    $results->add_test(
        'Success feedback implemented',
        $has_success_feedback,
        $has_success_feedback ? 'Success feedback present' : 'No success feedback'
    );
}

// Get summary
$summary = $results->get_summary();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task 15 Test Results - Unified Handler</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }
        
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 40px;
            background: #f8f9fa;
        }
        
        .summary-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .summary-card:hover {
            transform: translateY(-5px);
        }
        
        .summary-card .number {
            font-size: 3em;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .summary-card .label {
            font-size: 1em;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .summary-card.total .number { color: #667eea; }
        .summary-card.passed .number { color: #10b981; }
        .summary-card.failed .number { color: #ef4444; }
        .summary-card.rate .number { color: #f59e0b; }
        
        .tests {
            padding: 40px;
        }
        
        .test-item {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .test-item:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
        }
        
        .test-item.passed {
            border-left: 5px solid #10b981;
        }
        
        .test-item.failed {
            border-left: 5px solid #ef4444;
        }
        
        .test-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .test-name {
            font-size: 1.3em;
            font-weight: 600;
            color: #1f2937;
        }
        
        .test-status {
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .test-status.passed {
            background: #d1fae5;
            color: #065f46;
        }
        
        .test-status.failed {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .test-message {
            color: #6b7280;
            font-size: 1.1em;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .test-details {
            background: #f9fafb;
            border-radius: 8px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            color: #374151;
        }
        
        .test-details pre {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .footer {
            background: #1f2937;
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .footer p {
            margin: 5px 0;
            opacity: 0.8;
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.8em;
            }
            
            .summary {
                grid-template-columns: 1fr 1fr;
            }
            
            .test-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Task 15 Test Results</h1>
            <p>Unified REST API Form Handler</p>
        </div>
        
        <div class="summary">
            <div class="summary-card total">
                <div class="number"><?php echo $summary['total']; ?></div>
                <div class="label">Total Tests</div>
            </div>
            <div class="summary-card passed">
                <div class="number"><?php echo $summary['passed']; ?></div>
                <div class="label">Passed</div>
            </div>
            <div class="summary-card failed">
                <div class="number"><?php echo $summary['failed']; ?></div>
                <div class="label">Failed</div>
            </div>
            <div class="summary-card rate">
                <div class="number"><?php echo number_format($summary['success_rate'], 1); ?>%</div>
                <div class="label">Success Rate</div>
            </div>
        </div>
        
        <div class="tests">
            <?php foreach ($results->get_tests() as $test): ?>
                <div class="test-item <?php echo $test['passed'] ? 'passed' : 'failed'; ?>">
                    <div class="test-header">
                        <div class="test-name"><?php echo esc_html($test['name']); ?></div>
                        <div class="test-status <?php echo $test['passed'] ? 'passed' : 'failed'; ?>">
                            <?php echo $test['passed'] ? '✓ Passed' : '✗ Failed'; ?>
                        </div>
                    </div>
                    <div class="test-message"><?php echo esc_html($test['message']); ?></div>
                    <?php if (!empty($test['details'])): ?>
                        <div class="test-details">
                            <pre><?php echo esc_html(json_encode($test['details'], JSON_PRETTY_PRINT)); ?></pre>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="footer">
            <p><strong>Modern Admin Styler V2</strong></p>
            <p>Task 15: Unified REST API Form Handler</p>
            <p>Test Date: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>
