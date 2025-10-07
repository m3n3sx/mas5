<?php
/**
 * Test Script for Task 7: Diagnostics and Health Check Endpoint
 * 
 * Tests the diagnostics REST API endpoints and service functionality.
 * 
 * Usage: Run this file from WordPress admin or via WP-CLI
 * 
 * @package ModernAdminStylerV2
 * @since 2.2.0
 */

// Load WordPress
require_once dirname(__FILE__) . '/../../../wp-load.php';

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('You must be an administrator to run this test.');
}

// Set content type
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Task 7: Diagnostics Endpoint Test</title>
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
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.5;
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
        .test-button {
            background: #2271b1;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin: 5px;
        }
        .test-button:hover {
            background: #135e96;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🔍 Task 7: Diagnostics and Health Check Endpoint Test</h1>
        <p>Testing diagnostics REST API endpoints and service functionality.</p>
        
        <?php
        
        // Test results array
        $results = [
            'service' => [],
            'controller' => [],
            'endpoints' => [],
            'javascript' => []
        ];
        
        echo '<h2>📋 Test Summary</h2>';
        
        // Test 1: Check if diagnostics service class exists
        echo '<div class="test-section">';
        echo '<h3>Test 1: Diagnostics Service Class</h3>';
        
        $service_file = __DIR__ . '/includes/services/class-mas-diagnostics-service.php';
        if (file_exists($service_file)) {
            require_once $service_file;
            
            if (class_exists('MAS_Diagnostics_Service')) {
                echo '<p class="success">✓ MAS_Diagnostics_Service class exists</p>';
                $results['service'][] = true;
                
                // Test service instantiation
                try {
                    $diagnostics_service = new MAS_Diagnostics_Service();
                    echo '<p class="success">✓ Service instantiated successfully</p>';
                    $results['service'][] = true;
                    
                    // Test get_diagnostics method
                    $diagnostics = $diagnostics_service->get_diagnostics();
                    echo '<p class="success">✓ get_diagnostics() method works</p>';
                    $results['service'][] = true;
                    
                    // Check diagnostics structure
                    $required_sections = ['system', 'plugin', 'settings', 'filesystem', 'conflicts', 'performance', 'recommendations'];
                    $missing_sections = [];
                    
                    foreach ($required_sections as $section) {
                        if (!isset($diagnostics[$section])) {
                            $missing_sections[] = $section;
                        }
                    }
                    
                    if (empty($missing_sections)) {
                        echo '<p class="success">✓ All required sections present</p>';
                        $results['service'][] = true;
                    } else {
                        echo '<p class="error">✗ Missing sections: ' . implode(', ', $missing_sections) . '</p>';
                        $results['service'][] = false;
                    }
                    
                    // Display diagnostics data
                    echo '<h4>Diagnostics Data:</h4>';
                    echo '<pre>' . json_encode($diagnostics, JSON_PRETTY_PRINT) . '</pre>';
                    
                } catch (Exception $e) {
                    echo '<p class="error">✗ Service instantiation failed: ' . $e->getMessage() . '</p>';
                    $results['service'][] = false;
                }
            } else {
                echo '<p class="error">✗ MAS_Diagnostics_Service class not found</p>';
                $results['service'][] = false;
            }
        } else {
            echo '<p class="error">✗ Diagnostics service file not found</p>';
            $results['service'][] = false;
        }
        
        echo '</div>';
        
        // Test 2: Check if diagnostics controller exists
        echo '<div class="test-section">';
        echo '<h3>Test 2: Diagnostics Controller Class</h3>';
        
        $controller_file = __DIR__ . '/includes/api/class-mas-diagnostics-controller.php';
        if (file_exists($controller_file)) {
            require_once __DIR__ . '/includes/api/class-mas-rest-controller.php';
            require_once $controller_file;
            
            if (class_exists('MAS_Diagnostics_Controller')) {
                echo '<p class="success">✓ MAS_Diagnostics_Controller class exists</p>';
                $results['controller'][] = true;
                
                // Test controller instantiation
                try {
                    $controller = new MAS_Diagnostics_Controller();
                    echo '<p class="success">✓ Controller instantiated successfully</p>';
                    $results['controller'][] = true;
                    
                    // Check if controller extends base controller
                    if ($controller instanceof MAS_REST_Controller) {
                        echo '<p class="success">✓ Controller extends MAS_REST_Controller</p>';
                        $results['controller'][] = true;
                    } else {
                        echo '<p class="error">✗ Controller does not extend MAS_REST_Controller</p>';
                        $results['controller'][] = false;
                    }
                    
                } catch (Exception $e) {
                    echo '<p class="error">✗ Controller instantiation failed: ' . $e->getMessage() . '</p>';
                    $results['controller'][] = false;
                }
            } else {
                echo '<p class="error">✗ MAS_Diagnostics_Controller class not found</p>';
                $results['controller'][] = false;
            }
        } else {
            echo '<p class="error">✗ Diagnostics controller file not found</p>';
            $results['controller'][] = false;
        }
        
        echo '</div>';
        
        // Test 3: Test REST API endpoints
        echo '<div class="test-section">';
        echo '<h3>Test 3: REST API Endpoints</h3>';
        
        // Get REST API routes
        $routes = rest_get_server()->get_routes();
        
        $expected_routes = [
            '/mas-v2/v1/diagnostics',
            '/mas-v2/v1/diagnostics/health',
            '/mas-v2/v1/diagnostics/performance'
        ];
        
        foreach ($expected_routes as $route) {
            if (isset($routes[$route])) {
                echo '<p class="success">✓ Route registered: ' . $route . '</p>';
                $results['endpoints'][] = true;
            } else {
                echo '<p class="error">✗ Route not registered: ' . $route . '</p>';
                $results['endpoints'][] = false;
            }
        }
        
        // Test endpoint functionality
        echo '<h4>Testing Endpoint Responses:</h4>';
        
        // Test /diagnostics endpoint
        $request = new WP_REST_Request('GET', '/mas-v2/v1/diagnostics');
        $response = rest_do_request($request);
        
        if ($response->get_status() === 200) {
            echo '<p class="success">✓ GET /diagnostics returns 200</p>';
            $results['endpoints'][] = true;
            
            $data = $response->get_data();
            if (isset($data['success']) && $data['success']) {
                echo '<p class="success">✓ Response has success flag</p>';
                $results['endpoints'][] = true;
            }
            
            if (isset($data['data'])) {
                echo '<p class="success">✓ Response has data</p>';
                $results['endpoints'][] = true;
            }
        } else {
            echo '<p class="error">✗ GET /diagnostics failed with status: ' . $response->get_status() . '</p>';
            $results['endpoints'][] = false;
        }
        
        // Test /diagnostics/health endpoint
        $request = new WP_REST_Request('GET', '/mas-v2/v1/diagnostics/health');
        $response = rest_do_request($request);
        
        if ($response->get_status() === 200) {
            echo '<p class="success">✓ GET /diagnostics/health returns 200</p>';
            $results['endpoints'][] = true;
            
            $data = $response->get_data();
            if (isset($data['data']['status'])) {
                echo '<p class="success">✓ Health check has status: ' . $data['data']['status'] . '</p>';
                $results['endpoints'][] = true;
            }
        } else {
            echo '<p class="error">✗ GET /diagnostics/health failed</p>';
            $results['endpoints'][] = false;
        }
        
        // Test /diagnostics/performance endpoint
        $request = new WP_REST_Request('GET', '/mas-v2/v1/diagnostics/performance');
        $response = rest_do_request($request);
        
        if ($response->get_status() === 200) {
            echo '<p class="success">✓ GET /diagnostics/performance returns 200</p>';
            $results['endpoints'][] = true;
        } else {
            echo '<p class="error">✗ GET /diagnostics/performance failed</p>';
            $results['endpoints'][] = false;
        }
        
        echo '</div>';
        
        // Test 4: Check JavaScript client
        echo '<div class="test-section">';
        echo '<h3>Test 4: JavaScript Client</h3>';
        
        $rest_client_file = __DIR__ . '/assets/js/mas-rest-client.js';
        if (file_exists($rest_client_file)) {
            $rest_client_content = file_get_contents($rest_client_file);
            
            // Check for diagnostics methods
            $required_methods = [
                'getDiagnostics',
                'getHealthCheck',
                'getPerformanceMetrics'
            ];
            
            foreach ($required_methods as $method) {
                if (strpos($rest_client_content, $method) !== false) {
                    echo '<p class="success">✓ Method exists: ' . $method . '()</p>';
                    $results['javascript'][] = true;
                } else {
                    echo '<p class="error">✗ Method missing: ' . $method . '()</p>';
                    $results['javascript'][] = false;
                }
            }
        } else {
            echo '<p class="error">✗ REST client file not found</p>';
            $results['javascript'][] = false;
        }
        
        // Check DiagnosticsManager module
        $diagnostics_manager_file = __DIR__ . '/assets/js/modules/DiagnosticsManager.js';
        if (file_exists($diagnostics_manager_file)) {
            echo '<p class="success">✓ DiagnosticsManager module exists</p>';
            $results['javascript'][] = true;
            
            $manager_content = file_get_contents($diagnostics_manager_file);
            
            // Check for key methods
            $required_manager_methods = [
                'loadDiagnostics',
                'getHealthCheck',
                'getPerformanceMetrics',
                'render',
                'fixSettingsIntegrity'
            ];
            
            foreach ($required_manager_methods as $method) {
                if (strpos($manager_content, $method) !== false) {
                    echo '<p class="success">✓ DiagnosticsManager method: ' . $method . '()</p>';
                    $results['javascript'][] = true;
                } else {
                    echo '<p class="error">✗ DiagnosticsManager method missing: ' . $method . '()</p>';
                    $results['javascript'][] = false;
                }
            }
        } else {
            echo '<p class="error">✗ DiagnosticsManager module not found</p>';
            $results['javascript'][] = false;
        }
        
        echo '</div>';
        
        // Calculate overall results
        echo '<div class="test-section">';
        echo '<h2>📊 Test Results Summary</h2>';
        
        $categories = [
            'service' => 'Diagnostics Service',
            'controller' => 'Diagnostics Controller',
            'endpoints' => 'REST API Endpoints',
            'javascript' => 'JavaScript Client'
        ];
        
        echo '<table>';
        echo '<tr><th>Category</th><th>Passed</th><th>Failed</th><th>Total</th><th>Status</th></tr>';
        
        $total_passed = 0;
        $total_failed = 0;
        
        foreach ($categories as $key => $name) {
            $passed = count(array_filter($results[$key], function($r) { return $r === true; }));
            $failed = count(array_filter($results[$key], function($r) { return $r === false; }));
            $total = count($results[$key]);
            
            $total_passed += $passed;
            $total_failed += $failed;
            
            $status = $failed === 0 ? '<span class="badge badge-success">PASS</span>' : '<span class="badge badge-error">FAIL</span>';
            
            echo '<tr>';
            echo '<td>' . $name . '</td>';
            echo '<td>' . $passed . '</td>';
            echo '<td>' . $failed . '</td>';
            echo '<td>' . $total . '</td>';
            echo '<td>' . $status . '</td>';
            echo '</tr>';
        }
        
        echo '<tr style="font-weight: bold; background: #f6f7f7;">';
        echo '<td>TOTAL</td>';
        echo '<td>' . $total_passed . '</td>';
        echo '<td>' . $total_failed . '</td>';
        echo '<td>' . ($total_passed + $total_failed) . '</td>';
        echo '<td>' . ($total_failed === 0 ? '<span class="badge badge-success">ALL PASS</span>' : '<span class="badge badge-error">SOME FAILED</span>') . '</td>';
        echo '</tr>';
        
        echo '</table>';
        
        if ($total_failed === 0) {
            echo '<p class="success" style="font-size: 18px; margin-top: 20px;">✓ All tests passed! Task 7 implementation is complete.</p>';
        } else {
            echo '<p class="error" style="font-size: 18px; margin-top: 20px;">✗ Some tests failed. Please review the errors above.</p>';
        }
        
        echo '</div>';
        
        ?>
        
        <div class="test-section">
            <h2>🧪 Interactive Tests</h2>
            <p>Use the buttons below to test the diagnostics endpoints interactively:</p>
            
            <button class="test-button" onclick="testDiagnostics()">Test Full Diagnostics</button>
            <button class="test-button" onclick="testHealthCheck()">Test Health Check</button>
            <button class="test-button" onclick="testPerformance()">Test Performance Metrics</button>
            
            <div id="interactive-results" style="margin-top: 20px;"></div>
        </div>
    </div>
    
    <script>
        async function testDiagnostics() {
            const resultsDiv = document.getElementById('interactive-results');
            resultsDiv.innerHTML = '<p>Loading diagnostics...</p>';
            
            try {
                const response = await fetch('<?php echo rest_url('mas-v2/v1/diagnostics'); ?>', {
                    method: 'GET',
                    headers: {
                        'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                    },
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                resultsDiv.innerHTML = '<h3>Full Diagnostics Response:</h3><pre>' + 
                    JSON.stringify(data, null, 2) + '</pre>';
            } catch (error) {
                resultsDiv.innerHTML = '<p class="error">Error: ' + error.message + '</p>';
            }
        }
        
        async function testHealthCheck() {
            const resultsDiv = document.getElementById('interactive-results');
            resultsDiv.innerHTML = '<p>Running health check...</p>';
            
            try {
                const response = await fetch('<?php echo rest_url('mas-v2/v1/diagnostics/health'); ?>', {
                    method: 'GET',
                    headers: {
                        'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                    },
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                resultsDiv.innerHTML = '<h3>Health Check Response:</h3><pre>' + 
                    JSON.stringify(data, null, 2) + '</pre>';
            } catch (error) {
                resultsDiv.innerHTML = '<p class="error">Error: ' + error.message + '</p>';
            }
        }
        
        async function testPerformance() {
            const resultsDiv = document.getElementById('interactive-results');
            resultsDiv.innerHTML = '<p>Loading performance metrics...</p>';
            
            try {
                const response = await fetch('<?php echo rest_url('mas-v2/v1/diagnostics/performance'); ?>', {
                    method: 'GET',
                    headers: {
                        'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                    },
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                resultsDiv.innerHTML = '<h3>Performance Metrics Response:</h3><pre>' + 
                    JSON.stringify(data, null, 2) + '</pre>';
            } catch (error) {
                resultsDiv.innerHTML = '<p class="error">Error: ' + error.message + '</p>';
            }
        }
    </script>
</body>
</html>
