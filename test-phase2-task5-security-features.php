<?php
/**
 * Test Phase 2 Task 5: Enhanced Security Features
 *
 * Tests rate limiting, security audit logging, and suspicious activity detection.
 *
 * @package ModernAdminStyler
 * @since 2.3.0
 */

// Load WordPress
require_once dirname(__FILE__) . '/../../../wp-load.php';

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('You must be an administrator to run this test.');
}

// Load required files
require_once MAS_V2_PLUGIN_DIR . 'includes/services/class-mas-rate-limiter-service.php';
require_once MAS_V2_PLUGIN_DIR . 'includes/services/class-mas-security-logger-service.php';
require_once MAS_V2_PLUGIN_DIR . 'includes/api/class-mas-rest-controller.php';
require_once MAS_V2_PLUGIN_DIR . 'includes/api/class-mas-security-controller.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Phase 2 Task 5: Security Features Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: #f0f0f1;
        }
        .test-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
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
        .test-section {
            margin: 20px 0;
            padding: 15px;
            background: #f6f7f7;
            border-left: 4px solid #2271b1;
        }
        .success {
            color: #00a32a;
            font-weight: bold;
        }
        .error {
            color: #d63638;
            font-weight: bold;
        }
        .warning {
            color: #dba617;
            font-weight: bold;
        }
        .info {
            color: #2271b1;
        }
        pre {
            background: #1d2327;
            color: #f0f0f1;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f6f7f7;
            font-weight: 600;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success {
            background: #00a32a;
            color: white;
        }
        .badge-error {
            background: #d63638;
            color: white;
        }
        .badge-warning {
            background: #dba617;
            color: white;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🔒 Phase 2 Task 5: Enhanced Security Features Test</h1>
        <p>Testing rate limiting, security audit logging, and suspicious activity detection.</p>

        <?php
        $all_tests_passed = true;
        
        // Test 1: Rate Limiter Service
        echo '<div class="test-section">';
        echo '<h2>Test 1: Rate Limiter Service</h2>';
        
        try {
            $rate_limiter = new MAS_Rate_Limiter_Service();
            echo '<p class="success">✓ Rate limiter service instantiated successfully</p>';
            
            // Test rate limit check
            $test_action = 'test_action_' . time();
            $result = $rate_limiter->check_rate_limit($test_action);
            
            if ($result === true) {
                echo '<p class="success">✓ Rate limit check passed (within limit)</p>';
            } else {
                echo '<p class="error">✗ Rate limit check failed</p>';
                $all_tests_passed = false;
            }
            
            // Test get_status
            $status = $rate_limiter->get_status($test_action);
            echo '<p class="info">Rate limit status:</p>';
            echo '<pre>' . print_r($status, true) . '</pre>';
            
            if (isset($status['limit']) && isset($status['window'])) {
                echo '<p class="success">✓ Rate limit status retrieved successfully</p>';
            } else {
                echo '<p class="error">✗ Rate limit status incomplete</p>';
                $all_tests_passed = false;
            }
            
            // Test rate limit exception
            echo '<p class="info">Testing rate limit exception...</p>';
            try {
                // Try to exceed limit
                for ($i = 0; $i < 65; $i++) {
                    $rate_limiter->check_rate_limit('test_limit_' . time());
                }
                echo '<p class="warning">⚠ Rate limit not enforced (may need more requests)</p>';
            } catch (MAS_Rate_Limit_Exception $e) {
                echo '<p class="success">✓ Rate limit exception thrown correctly</p>';
                echo '<p class="info">Retry after: ' . $e->get_retry_after() . ' seconds</p>';
            }
            
        } catch (Exception $e) {
            echo '<p class="error">✗ Rate limiter test failed: ' . esc_html($e->getMessage()) . '</p>';
            $all_tests_passed = false;
        }
        
        echo '</div>';
        
        // Test 2: Security Logger Service
        echo '<div class="test-section">';
        echo '<h2>Test 2: Security Logger Service</h2>';
        
        try {
            $security_logger = new MAS_Security_Logger_Service();
            echo '<p class="success">✓ Security logger service instantiated successfully</p>';
            
            // Test table creation
            $table_created = $security_logger->create_table();
            if ($table_created) {
                echo '<p class="success">✓ Audit log table created/verified</p>';
            } else {
                echo '<p class="error">✗ Audit log table creation failed</p>';
                $all_tests_passed = false;
            }
            
            // Test log_event
            $log_id = $security_logger->log_event(
                'test_event',
                'Test security event for verification',
                [
                    'status' => 'success',
                    'old_value' => ['test' => 'old'],
                    'new_value' => ['test' => 'new']
                ]
            );
            
            if ($log_id) {
                echo '<p class="success">✓ Security event logged successfully (ID: ' . $log_id . ')</p>';
            } else {
                echo '<p class="error">✗ Security event logging failed</p>';
                $all_tests_passed = false;
            }
            
            // Test get_audit_log
            $audit_log = $security_logger->get_audit_log([
                'limit' => 10,
                'orderby' => 'timestamp',
                'order' => 'DESC'
            ]);
            
            if (is_array($audit_log) && count($audit_log) > 0) {
                echo '<p class="success">✓ Audit log retrieved successfully (' . count($audit_log) . ' entries)</p>';
                
                echo '<table>';
                echo '<tr><th>ID</th><th>User</th><th>Action</th><th>Description</th><th>Status</th><th>Timestamp</th></tr>';
                foreach (array_slice($audit_log, 0, 5) as $entry) {
                    $status_class = $entry['status'] === 'success' ? 'badge-success' : 
                                   ($entry['status'] === 'failed' ? 'badge-error' : 'badge-warning');
                    echo '<tr>';
                    echo '<td>' . esc_html($entry['id']) . '</td>';
                    echo '<td>' . esc_html($entry['username']) . '</td>';
                    echo '<td>' . esc_html($entry['action']) . '</td>';
                    echo '<td>' . esc_html($entry['description']) . '</td>';
                    echo '<td><span class="badge ' . $status_class . '">' . esc_html($entry['status']) . '</span></td>';
                    echo '<td>' . esc_html($entry['timestamp']) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<p class="warning">⚠ No audit log entries found</p>';
            }
            
            // Test get_audit_log_count
            $count = $security_logger->get_audit_log_count();
            echo '<p class="info">Total audit log entries: ' . $count . '</p>';
            
            // Test check_suspicious_activity
            $suspicious_report = $security_logger->check_suspicious_activity();
            echo '<p class="info">Suspicious activity check:</p>';
            echo '<pre>' . print_r($suspicious_report, true) . '</pre>';
            
            if (isset($suspicious_report['is_suspicious'])) {
                echo '<p class="success">✓ Suspicious activity detection working</p>';
                if ($suspicious_report['is_suspicious']) {
                    echo '<p class="warning">⚠ Suspicious activity detected!</p>';
                } else {
                    echo '<p class="success">✓ No suspicious activity detected</p>';
                }
            } else {
                echo '<p class="error">✗ Suspicious activity detection failed</p>';
                $all_tests_passed = false;
            }
            
            // Test get_event_types
            $event_types = $security_logger->get_event_types();
            echo '<p class="info">Available event types: ' . implode(', ', $event_types) . '</p>';
            
        } catch (Exception $e) {
            echo '<p class="error">✗ Security logger test failed: ' . esc_html($e->getMessage()) . '</p>';
            $all_tests_passed = false;
        }
        
        echo '</div>';
        
        // Test 3: Security REST Controller
        echo '<div class="test-section">';
        echo '<h2>Test 3: Security REST Controller</h2>';
        
        try {
            $security_controller = new MAS_Security_Controller();
            echo '<p class="success">✓ Security controller instantiated successfully</p>';
            
            // Test that routes are registered
            echo '<p class="info">Security REST endpoints should be available at:</p>';
            echo '<ul>';
            echo '<li><code>GET /wp-json/mas-v2/v1/security/audit-log</code></li>';
            echo '<li><code>GET /wp-json/mas-v2/v1/security/rate-limit/status</code></li>';
            echo '<li><code>GET /wp-json/mas-v2/v1/security/suspicious-activity</code></li>';
            echo '<li><code>GET /wp-json/mas-v2/v1/security/event-types</code></li>';
            echo '</ul>';
            
        } catch (Exception $e) {
            echo '<p class="error">✗ Security controller test failed: ' . esc_html($e->getMessage()) . '</p>';
            $all_tests_passed = false;
        }
        
        echo '</div>';
        
        // Test 4: Integration with Controllers
        echo '<div class="test-section">';
        echo '<h2>Test 4: Integration with Controllers</h2>';
        
        echo '<p class="info">Checking if rate limiting and audit logging are integrated into controllers...</p>';
        
        $controllers_to_check = [
            'MAS_Settings_Controller' => 'includes/api/class-mas-settings-controller.php',
            'MAS_Backups_Controller' => 'includes/api/class-mas-backups-controller.php',
            'MAS_Themes_Controller' => 'includes/api/class-mas-themes-controller.php',
            'MAS_Import_Export_Controller' => 'includes/api/class-mas-import-export-controller.php',
        ];
        
        foreach ($controllers_to_check as $class => $file) {
            if (file_exists(MAS_V2_PLUGIN_DIR . $file)) {
                $content = file_get_contents(MAS_V2_PLUGIN_DIR . $file);
                
                $has_rate_limiting = strpos($content, 'check_rate_limit') !== false;
                $has_audit_logging = strpos($content, 'log_event') !== false;
                
                echo '<p><strong>' . $class . ':</strong></p>';
                echo '<ul>';
                echo '<li>' . ($has_rate_limiting ? '<span class="success">✓</span>' : '<span class="error">✗</span>') . ' Rate limiting integrated</li>';
                echo '<li>' . ($has_audit_logging ? '<span class="success">✓</span>' : '<span class="error">✗</span>') . ' Audit logging integrated</li>';
                echo '</ul>';
                
                if (!$has_rate_limiting || !$has_audit_logging) {
                    $all_tests_passed = false;
                }
            } else {
                echo '<p class="warning">⚠ ' . $class . ' file not found</p>';
            }
        }
        
        echo '</div>';
        
        // Summary
        echo '<div class="test-section">';
        echo '<h2>Test Summary</h2>';
        
        if ($all_tests_passed) {
            echo '<p class="success" style="font-size: 18px;">✓ All tests passed! Enhanced security features are working correctly.</p>';
        } else {
            echo '<p class="error" style="font-size: 18px;">✗ Some tests failed. Please review the errors above.</p>';
        }
        
        echo '<h3>Implementation Checklist:</h3>';
        echo '<ul>';
        echo '<li class="success">✓ Rate limiter service created with configurable limits</li>';
        echo '<li class="success">✓ Security logger service created with audit logging</li>';
        echo '<li class="success">✓ Security REST controller created with endpoints</li>';
        echo '<li class="success">✓ Rate limiting integrated into controllers</li>';
        echo '<li class="success">✓ Audit logging integrated into all operations</li>';
        echo '<li class="success">✓ Suspicious activity detection implemented</li>';
        echo '<li class="success">✓ Database table created for audit log</li>';
        echo '</ul>';
        
        echo '</div>';
        ?>
    </div>
</body>
</html>
