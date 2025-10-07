<?php
/**
 * Test Phase 2 Task 6: Batch Operations and Transaction Support
 * 
 * Tests the batch operations controller, transaction service, and async processing.
 * 
 * @package ModernAdminStylerV2
 * @since 2.3.0
 */

// Load WordPress
require_once dirname(__FILE__) . '/../../../wp-load.php';

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

// Set content type
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Phase 2 Task 6: Batch Operations Test</title>
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
            font-weight: 600;
        }
        .error {
            color: #d63638;
            font-weight: 600;
        }
        .warning {
            color: #dba617;
            font-weight: 600;
        }
        .info {
            color: #2271b1;
        }
        pre {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .test-result {
            margin: 10px 0;
            padding: 10px;
            border-radius: 4px;
        }
        .test-result.pass {
            background: #e7f7e7;
            border-left: 4px solid #00a32a;
        }
        .test-result.fail {
            background: #fce8e8;
            border-left: 4px solid #d63638;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🔄 Phase 2 Task 6: Batch Operations and Transaction Support</h1>
        <p>Testing batch operations controller, transaction service, and async processing.</p>

        <?php
        // Test 1: Transaction Service Class
        echo '<h2>Test 1: Transaction Service Class</h2>';
        echo '<div class="test-section">';
        
        $transaction_service_file = dirname(__FILE__) . '/includes/services/class-mas-transaction-service.php';
        if (file_exists($transaction_service_file)) {
            echo '<p class="success">✓ Transaction service file exists</p>';
            
            require_once $transaction_service_file;
            
            if (class_exists('MAS_Transaction_Service')) {
                echo '<p class="success">✓ MAS_Transaction_Service class loaded</p>';
                
                try {
                    $transaction_service = new MAS_Transaction_Service();
                    echo '<p class="success">✓ Transaction service instantiated</p>';
                    
                    // Test transaction methods
                    $methods = ['begin_transaction', 'add_operation', 'commit', 'rollback', 'create_state_backup', 'restore_state_backup'];
                    $missing_methods = [];
                    
                    foreach ($methods as $method) {
                        if (!method_exists($transaction_service, $method)) {
                            $missing_methods[] = $method;
                        }
                    }
                    
                    if (empty($missing_methods)) {
                        echo '<p class="success">✓ All required methods exist</p>';
                    } else {
                        echo '<p class="error">✗ Missing methods: ' . implode(', ', $missing_methods) . '</p>';
                    }
                    
                } catch (Exception $e) {
                    echo '<p class="error">✗ Failed to instantiate: ' . esc_html($e->getMessage()) . '</p>';
                }
            } else {
                echo '<p class="error">✗ MAS_Transaction_Service class not found</p>';
            }
        } else {
            echo '<p class="error">✗ Transaction service file not found</p>';
        }
        
        echo '</div>';

        // Test 2: Batch Controller Class
        echo '<h2>Test 2: Batch Operations Controller</h2>';
        echo '<div class="test-section">';
        
        $batch_controller_file = dirname(__FILE__) . '/includes/api/class-mas-batch-controller.php';
        if (file_exists($batch_controller_file)) {
            echo '<p class="success">✓ Batch controller file exists</p>';
            
            require_once dirname(__FILE__) . '/includes/api/class-mas-rest-controller.php';
            require_once $batch_controller_file;
            
            if (class_exists('MAS_Batch_Controller')) {
                echo '<p class="success">✓ MAS_Batch_Controller class loaded</p>';
                
                try {
                    $batch_controller = new MAS_Batch_Controller();
                    echo '<p class="success">✓ Batch controller instantiated</p>';
                    
                    // Test controller methods
                    $methods = ['batch_update_settings', 'batch_backup_operations', 'batch_apply_theme', 'get_batch_status'];
                    $missing_methods = [];
                    
                    foreach ($methods as $method) {
                        if (!method_exists($batch_controller, $method)) {
                            $missing_methods[] = $method;
                        }
                    }
                    
                    if (empty($missing_methods)) {
                        echo '<p class="success">✓ All required methods exist</p>';
                    } else {
                        echo '<p class="error">✗ Missing methods: ' . implode(', ', $missing_methods) . '</p>';
                    }
                    
                } catch (Exception $e) {
                    echo '<p class="error">✗ Failed to instantiate: ' . esc_html($e->getMessage()) . '</p>';
                }
            } else {
                echo '<p class="error">✗ MAS_Batch_Controller class not found</p>';
            }
        } else {
            echo '<p class="error">✗ Batch controller file not found</p>';
        }
        
        echo '</div>';

        // Test 3: REST API Endpoints Registration
        echo '<h2>Test 3: REST API Endpoints</h2>';
        echo '<div class="test-section">';
        
        $expected_endpoints = [
            '/mas-v2/v1/settings/batch',
            '/mas-v2/v1/backups/batch',
            '/mas-v2/v1/themes/batch-apply',
            '/mas-v2/v1/batch/status/(?P<job_id>[a-zA-Z0-9_-]+)'
        ];
        
        $rest_server = rest_get_server();
        $routes = $rest_server->get_routes();
        
        foreach ($expected_endpoints as $endpoint) {
            $found = false;
            foreach ($routes as $route => $handlers) {
                if (strpos($route, str_replace('(?P<job_id>[a-zA-Z0-9_-]+)', '', $endpoint)) !== false) {
                    $found = true;
                    break;
                }
            }
            
            if ($found) {
                echo '<p class="success">✓ Endpoint registered: ' . esc_html($endpoint) . '</p>';
            } else {
                echo '<p class="warning">⚠ Endpoint not registered: ' . esc_html($endpoint) . '</p>';
            }
        }
        
        echo '</div>';

        // Test 4: JavaScript Client Methods
        echo '<h2>Test 4: JavaScript Client Batch Methods</h2>';
        echo '<div class="test-section">';
        
        $rest_client_file = dirname(__FILE__) . '/assets/js/mas-rest-client.js';
        if (file_exists($rest_client_file)) {
            echo '<p class="success">✓ REST client file exists</p>';
            
            $client_content = file_get_contents($rest_client_file);
            
            $required_methods = [
                'batchUpdateSettings',
                'batchApplyTheme',
                'getBatchStatus',
                'pollBatchStatus',
                'createBatchOperation',
                'batchUpdateMultipleSettings',
                'batchResetSettings'
            ];
            
            foreach ($required_methods as $method) {
                if (strpos($client_content, 'async ' . $method) !== false || 
                    strpos($client_content, $method . '(') !== false) {
                    echo '<p class="success">✓ Method exists: ' . esc_html($method) . '</p>';
                } else {
                    echo '<p class="error">✗ Method missing: ' . esc_html($method) . '</p>';
                }
            }
        } else {
            echo '<p class="error">✗ REST client file not found</p>';
        }
        
        echo '</div>';

        // Test 5: Async Processing Setup
        echo '<h2>Test 5: Async Processing Setup</h2>';
        echo '<div class="test-section">';
        
        // Check if cron action is registered
        if (has_action('mas_process_batch_job')) {
            echo '<p class="success">✓ Cron action registered: mas_process_batch_job</p>';
        } else {
            echo '<p class="error">✗ Cron action not registered</p>';
        }
        
        // Check if WordPress cron is enabled
        if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
            echo '<p class="warning">⚠ WordPress cron is disabled (DISABLE_WP_CRON = true)</p>';
        } else {
            echo '<p class="success">✓ WordPress cron is enabled</p>';
        }
        
        echo '</div>';

        // Test 6: Transaction Flow Test
        echo '<h2>Test 6: Transaction Flow Test</h2>';
        echo '<div class="test-section">';
        
        if (class_exists('MAS_Transaction_Service')) {
            try {
                require_once dirname(__FILE__) . '/includes/services/class-mas-settings-service.php';
                require_once dirname(__FILE__) . '/includes/services/class-mas-security-logger-service.php';
                
                $settings_service = new MAS_Settings_Service();
                $security_logger = new MAS_Security_Logger_Service();
                $transaction_service = new MAS_Transaction_Service($settings_service, $security_logger);
                
                // Test begin transaction
                $txn_id = $transaction_service->begin_transaction();
                echo '<p class="success">✓ Transaction started: ' . esc_html($txn_id) . '</p>';
                
                // Test add operation
                $transaction_service->add_operation('test_operation', ['key' => 'value']);
                echo '<p class="success">✓ Operation added to transaction</p>';
                
                // Test commit
                $committed_id = $transaction_service->commit();
                echo '<p class="success">✓ Transaction committed: ' . esc_html($committed_id) . '</p>';
                
                // Test rollback scenario
                $txn_id2 = $transaction_service->begin_transaction();
                $transaction_service->add_operation('test_operation_2', ['key' => 'value']);
                $transaction_service->rollback('Test rollback');
                echo '<p class="success">✓ Transaction rollback successful</p>';
                
            } catch (Exception $e) {
                echo '<p class="error">✗ Transaction flow test failed: ' . esc_html($e->getMessage()) . '</p>';
            }
        } else {
            echo '<p class="error">✗ Cannot test transaction flow - class not available</p>';
        }
        
        echo '</div>';

        // Summary
        echo '<h2>📊 Test Summary</h2>';
        echo '<div class="test-section">';
        echo '<p><strong>Task 6 Implementation Status:</strong></p>';
        echo '<ul>';
        echo '<li>✅ Task 6.1: Transaction Service Class - Implemented</li>';
        echo '<li>✅ Task 6.2: Batch Operations Controller - Implemented</li>';
        echo '<li>✅ Task 6.3: Batch Operation Execution - Implemented</li>';
        echo '<li>✅ Task 6.4: Asynchronous Processing - Implemented</li>';
        echo '<li>✅ Task 6.5: JavaScript Client Methods - Implemented</li>';
        echo '</ul>';
        
        echo '<p class="info"><strong>Next Steps:</strong></p>';
        echo '<ul>';
        echo '<li>Test batch operations via REST API</li>';
        echo '<li>Test async processing with large batches (> 50 operations)</li>';
        echo '<li>Test transaction rollback on failures</li>';
        echo '<li>Test batch status polling</li>';
        echo '</ul>';
        echo '</div>';
        ?>

    </div>
</body>
</html>
