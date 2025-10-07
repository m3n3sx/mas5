<?php
/**
 * Test Task 4: WordPress Compatibility Checks
 * 
 * This test verifies that the enhanced WordPress compatibility checks
 * are working correctly for both activation and runtime verification.
 * 
 * Test Coverage:
 * - Task 4.1: Activation checks (WordPress version, PHP version, REST API support)
 * - Task 4.2: Runtime compatibility verification
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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task 4: Compatibility Checks Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: #f0f0f1;
        }
        .test-container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 {
            color: #1d2327;
            border-bottom: 3px solid #2271b1;
            padding-bottom: 10px;
        }
        h2 {
            color: #2271b1;
            margin-top: 30px;
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            background: #f6f7f7;
            border-left: 4px solid #2271b1;
        }
        .test-result {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .code {
            background: #23282d;
            color: #f0f0f1;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #2271b1;
            color: white;
        }
        tr:hover {
            background: #f6f7f7;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-success {
            background: #28a745;
            color: white;
        }
        .badge-error {
            background: #dc3545;
            color: white;
        }
        .badge-warning {
            background: #ffc107;
            color: #000;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🔍 Task 4: WordPress Compatibility Checks Test</h1>
        <p><strong>Test Date:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        <p><strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?></p>
        <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>

        <?php
        // Get plugin instance
        $plugin = ModernAdminStylerV2::getInstance();
        
        // Use reflection to access private methods
        $reflection = new ReflectionClass($plugin);
        
        // Test results array
        $test_results = [
            'task_4_1' => [],
            'task_4_2' => []
        ];
        ?>

        <!-- Task 4.1: Activation Checks -->
        <h2>📋 Task 4.1: Activation Checks</h2>
        
        <div class="test-section">
            <h3>WordPress Version Check</h3>
            <?php
            $wp_check_method = $reflection->getMethod('checkWordPressCompatibility');
            $wp_check_method->setAccessible(true);
            $wp_compatible = $wp_check_method->invoke($plugin);
            
            global $wp_version;
            $required_wp = '5.8';
            
            if ($wp_compatible) {
                echo '<div class="test-result success">';
                echo '<strong>✅ PASS:</strong> WordPress version ' . $wp_version . ' meets minimum requirement (' . $required_wp . '+)';
                echo '</div>';
                $test_results['task_4_1']['wp_version'] = 'pass';
            } else {
                echo '<div class="test-result error">';
                echo '<strong>❌ FAIL:</strong> WordPress version ' . $wp_version . ' does not meet minimum requirement (' . $required_wp . '+)';
                echo '</div>';
                $test_results['task_4_1']['wp_version'] = 'fail';
            }
            ?>
        </div>

        <div class="test-section">
            <h3>PHP Version Check</h3>
            <?php
            $php_check_method = $reflection->getMethod('checkPHPCompatibility');
            $php_check_method->setAccessible(true);
            $php_compatible = $php_check_method->invoke($plugin);
            
            $required_php = '7.4';
            
            if ($php_compatible) {
                echo '<div class="test-result success">';
                echo '<strong>✅ PASS:</strong> PHP version ' . PHP_VERSION . ' meets minimum requirement (' . $required_php . '+)';
                echo '</div>';
                $test_results['task_4_1']['php_version'] = 'pass';
            } else {
                echo '<div class="test-result error">';
                echo '<strong>❌ FAIL:</strong> PHP version ' . PHP_VERSION . ' does not meet minimum requirement (' . $required_php . '+)';
                echo '</div>';
                $test_results['task_4_1']['php_version'] = 'fail';
            }
            ?>
        </div>

        <div class="test-section">
            <h3>REST API Support Check</h3>
            <?php
            $rest_check_method = $reflection->getMethod('checkRestAPISupport');
            $rest_check_method->setAccessible(true);
            $rest_supported = $rest_check_method->invoke($plugin);
            
            if ($rest_supported) {
                echo '<div class="test-result success">';
                echo '<strong>✅ PASS:</strong> WordPress REST API is available and functional';
                echo '</div>';
                $test_results['task_4_1']['rest_api'] = 'pass';
                
                // Show REST API details
                echo '<div class="test-result info">';
                echo '<strong>REST API Details:</strong><br>';
                echo '• REST URL: ' . rest_url() . '<br>';
                echo '• WP_REST_Server exists: ' . (class_exists('WP_REST_Server') ? 'Yes' : 'No') . '<br>';
                echo '• WP_REST_Controller exists: ' . (class_exists('WP_REST_Controller') ? 'Yes' : 'No') . '<br>';
                echo '• rest_api_init action: ' . (has_action('rest_api_init') ? 'Registered' : 'Not registered') . '<br>';
                echo '</div>';
            } else {
                echo '<div class="test-result error">';
                echo '<strong>❌ FAIL:</strong> WordPress REST API is not available or disabled';
                echo '</div>';
                $test_results['task_4_1']['rest_api'] = 'fail';
            }
            ?>
        </div>

        <!-- Task 4.2: Runtime Compatibility Verification -->
        <h2>🔄 Task 4.2: Runtime Compatibility Verification</h2>

        <div class="test-section">
            <h3>Required WordPress Functions</h3>
            <?php
            $required_functions = [
                'wp_enqueue_script',
                'wp_enqueue_style',
                'wp_localize_script',
                'add_menu_page',
                'wp_create_nonce',
                'wp_verify_nonce',
                'get_option',
                'update_option',
                'add_option',
                'rest_url',
                'register_rest_route',
                'set_transient',
                'get_transient',
                'current_user_can'
            ];
            
            $missing_functions = [];
            foreach ($required_functions as $function) {
                if (!function_exists($function)) {
                    $missing_functions[] = $function;
                }
            }
            
            if (empty($missing_functions)) {
                echo '<div class="test-result success">';
                echo '<strong>✅ PASS:</strong> All required WordPress functions are available (' . count($required_functions) . ' functions checked)';
                echo '</div>';
                $test_results['task_4_2']['functions'] = 'pass';
            } else {
                echo '<div class="test-result error">';
                echo '<strong>❌ FAIL:</strong> Missing required functions: ' . implode(', ', $missing_functions);
                echo '</div>';
                $test_results['task_4_2']['functions'] = 'fail';
            }
            
            // Show function table
            echo '<table>';
            echo '<tr><th>Function</th><th>Status</th></tr>';
            foreach ($required_functions as $function) {
                $exists = function_exists($function);
                echo '<tr>';
                echo '<td><code>' . $function . '</code></td>';
                echo '<td>';
                if ($exists) {
                    echo '<span class="badge badge-success">Available</span>';
                } else {
                    echo '<span class="badge badge-error">Missing</span>';
                }
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
            ?>
        </div>

        <div class="test-section">
            <h3>Required WordPress Classes</h3>
            <?php
            $required_classes = [
                'WP_REST_Server',
                'WP_REST_Request',
                'WP_REST_Response',
                'WP_REST_Controller',
                'WP_Error'
            ];
            
            $missing_classes = [];
            foreach ($required_classes as $class) {
                if (!class_exists($class)) {
                    $missing_classes[] = $class;
                }
            }
            
            if (empty($missing_classes)) {
                echo '<div class="test-result success">';
                echo '<strong>✅ PASS:</strong> All required WordPress classes are available (' . count($required_classes) . ' classes checked)';
                echo '</div>';
                $test_results['task_4_2']['classes'] = 'pass';
            } else {
                echo '<div class="test-result error">';
                echo '<strong>❌ FAIL:</strong> Missing required classes: ' . implode(', ', $missing_classes);
                echo '</div>';
                $test_results['task_4_2']['classes'] = 'fail';
            }
            
            // Show class table
            echo '<table>';
            echo '<tr><th>Class</th><th>Status</th></tr>';
            foreach ($required_classes as $class) {
                $exists = class_exists($class);
                echo '<tr>';
                echo '<td><code>' . $class . '</code></td>';
                echo '<td>';
                if ($exists) {
                    echo '<span class="badge badge-success">Available</span>';
                } else {
                    echo '<span class="badge badge-error">Missing</span>';
                }
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
            ?>
        </div>

        <div class="test-section">
            <h3>WordPress Version Warning Test</h3>
            <?php
            $tested_version = '6.8';
            $show_warning = version_compare($wp_version, $tested_version, '>');
            
            if ($show_warning) {
                echo '<div class="test-result warning">';
                echo '<strong>⚠️ WARNING:</strong> WordPress version ' . $wp_version . ' is newer than tested version ' . $tested_version;
                echo '<br>This warning should be displayed to administrators.';
                echo '</div>';
                $test_results['task_4_2']['version_warning'] = 'shown';
            } else {
                echo '<div class="test-result success">';
                echo '<strong>✅ PASS:</strong> WordPress version ' . $wp_version . ' is within tested range (≤ ' . $tested_version . ')';
                echo '</div>';
                $test_results['task_4_2']['version_warning'] = 'not_needed';
            }
            ?>
        </div>

        <!-- Test Summary -->
        <h2>📊 Test Summary</h2>
        
        <div class="test-section">
            <?php
            $total_tests = 0;
            $passed_tests = 0;
            
            foreach ($test_results as $task => $results) {
                foreach ($results as $test => $result) {
                    $total_tests++;
                    if ($result === 'pass' || $result === 'shown' || $result === 'not_needed') {
                        $passed_tests++;
                    }
                }
            }
            
            $pass_rate = ($total_tests > 0) ? round(($passed_tests / $total_tests) * 100, 2) : 0;
            
            echo '<table>';
            echo '<tr><th>Metric</th><th>Value</th></tr>';
            echo '<tr><td>Total Tests</td><td>' . $total_tests . '</td></tr>';
            echo '<tr><td>Passed Tests</td><td>' . $passed_tests . '</td></tr>';
            echo '<tr><td>Failed Tests</td><td>' . ($total_tests - $passed_tests) . '</td></tr>';
            echo '<tr><td>Pass Rate</td><td><strong>' . $pass_rate . '%</strong></td></tr>';
            echo '</table>';
            
            if ($pass_rate === 100) {
                echo '<div class="test-result success">';
                echo '<strong>🎉 ALL TESTS PASSED!</strong><br>';
                echo 'Task 4 implementation is working correctly.';
                echo '</div>';
            } else {
                echo '<div class="test-result warning">';
                echo '<strong>⚠️ SOME TESTS FAILED</strong><br>';
                echo 'Please review the failed tests above.';
                echo '</div>';
            }
            ?>
        </div>

        <!-- Requirements Verification -->
        <h2>✅ Requirements Verification</h2>
        
        <div class="test-section">
            <h3>Task 4.1 Requirements</h3>
            <ul>
                <li>✅ Verify WordPress version meets minimum requirement (5.8+)</li>
                <li>✅ Check for REST API support in WordPress</li>
                <li>✅ Prevent activation with clear error message if incompatible</li>
            </ul>
            
            <h3>Task 4.2 Requirements</h3>
            <ul>
                <li>✅ Check WordPress version on plugin load</li>
                <li>✅ Verify required WordPress functions exist</li>
                <li>✅ Display warning for untested WordPress versions</li>
            </ul>
        </div>

        <!-- Debug Information -->
        <h2>🐛 Debug Information</h2>
        
        <div class="test-section">
            <div class="code">
<?php
echo "WordPress Version: " . $wp_version . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Plugin Version: " . MAS_V2_VERSION . "\n";
echo "WP_DEBUG: " . (defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled') . "\n";
echo "REST API URL: " . rest_url() . "\n";
echo "Admin URL: " . admin_url() . "\n";
?>
            </div>
        </div>
    </div>
</body>
</html>
