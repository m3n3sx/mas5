<?php
/**
 * Test Phase 2 Task 3: System Diagnostics and Health Monitoring
 * 
 * Tests the system health service, system diagnostics REST controller,
 * conflict detection, and JavaScript client integration.
 * 
 * @package ModernAdminStylerV2
 * @since 2.3.0
 */

// Load WordPress
require_once dirname(__FILE__) . '/../../../wp-load.php';

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('You must be an administrator to run this test.');
}

// Load required files
require_once plugin_dir_path(__FILE__) . 'includes/services/class-mas-settings-service.php';
require_once plugin_dir_path(__FILE__) . 'includes/services/class-mas-system-health-service.php';
require_once plugin_dir_path(__FILE__) . 'includes/api/class-mas-rest-controller.php';
require_once plugin_dir_path(__FILE__) . 'includes/api/class-mas-system-controller.php';
require_once plugin_dir_path(__FILE__) . 'includes/services/class-mas-rate-limiter-service.php';
require_once plugin_dir_path(__FILE__) . 'includes/services/class-mas-security-logger-service.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phase 2 Task 3: System Diagnostics Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        h2 {
            color: #34495e;
            margin-top: 30px;
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            border-radius: 4px;
        }
        .success {
            color: #27ae60;
            font-weight: bold;
        }
        .error {
            color: #e74c3c;
            font-weight: bold;
        }
        .warning {
            color: #f39c12;
            font-weight: bold;
        }
        .info {
            color: #3498db;
        }
        pre {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-right: 5px;
        }
        .badge-success { background: #27ae60; color: white; }
        .badge-error { background: #e74c3c; color: white; }
        .badge-warning { background: #f39c12; color: white; }
        .badge-info { background: #3498db; color: white; }
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
            background: #34495e;
            color: white;
        }
        .test-button {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin: 5px;
        }
        .test-button:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🏥 Phase 2 Task 3: System Diagnostics and Health Monitoring</h1>
        <p>Testing system health service, diagnostics REST controller, conflict detection, and JavaScript client integration.</p>
    </div>

    <?php
    // Test 1: System Health Service
    echo '<div class="test-container">';
    echo '<h2>Test 1: System Health Service</h2>';
    
    try {
        $health_service = new MAS_System_Health_Service();
        echo '<div class="test-section">';
        echo '<p class="success">✓ System Health Service instantiated successfully</p>';
        
        // Test get_health_status
        $health_status = $health_service->get_health_status();
        echo '<p class="success">✓ get_health_status() executed successfully</p>';
        
        echo '<h3>Health Status:</h3>';
        echo '<p><strong>Overall Status:</strong> <span class="badge badge-' . 
             ($health_status['status'] === 'healthy' ? 'success' : 
              ($health_status['status'] === 'warning' ? 'warning' : 'error')) . '">' . 
             strtoupper($health_status['status']) . '</span></p>';
        
        echo '<h4>Summary:</h4>';
        echo '<ul>';
        echo '<li>Total Checks: ' . $health_status['summary']['total_checks'] . '</li>';
        echo '<li>Healthy: ' . $health_status['summary']['healthy'] . '</li>';
        echo '<li>Warning: ' . $health_status['summary']['warning'] . '</li>';
        echo '<li>Critical: ' . $health_status['summary']['critical'] . '</li>';
        echo '<li>Health Percentage: ' . $health_status['summary']['health_percentage'] . '%</li>';
        echo '</ul>';
        
        // Test individual checks
        echo '<h4>Individual Checks:</h4>';
        echo '<table>';
        echo '<tr><th>Check</th><th>Status</th><th>Message</th></tr>';
        
        foreach ($health_status['checks'] as $check_name => $check) {
            $badge_class = $check['status'] === 'healthy' ? 'success' : 
                          ($check['status'] === 'warning' ? 'warning' : 'error');
            echo '<tr>';
            echo '<td>' . ucwords(str_replace('_', ' ', $check_name)) . '</td>';
            echo '<td><span class="badge badge-' . $badge_class . '">' . strtoupper($check['status']) . '</span></td>';
            echo '<td>' . htmlspecialchars($check['message']) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        
        // Test recommendations
        if (!empty($health_status['recommendations'])) {
            echo '<h4>Recommendations (' . count($health_status['recommendations']) . '):</h4>';
            echo '<ul>';
            foreach ($health_status['recommendations'] as $rec) {
                $badge_class = $rec['severity'] === 'critical' ? 'error' : 
                              ($rec['severity'] === 'warning' ? 'warning' : 'info');
                echo '<li>';
                echo '<span class="badge badge-' . $badge_class . '">' . strtoupper($rec['severity']) . '</span> ';
                echo '<strong>' . htmlspecialchars($rec['title']) . '</strong><br>';
                echo htmlspecialchars($rec['description']) . '<br>';
                echo '<em>Action: ' . htmlspecialchars($rec['action']) . '</em>';
                echo '</li>';
            }
            echo '</ul>';
        }
        
        // Test performance metrics
        echo '<h4>Performance Metrics:</h4>';
        $metrics = $health_service->get_performance_metrics();
        echo '<ul>';
        echo '<li>Memory Usage: ' . $metrics['memory']['current'] . ' (Peak: ' . $metrics['memory']['peak'] . ')</li>';
        echo '<li>Cache Type: ' . $metrics['cache']['cache_type'] . '</li>';
        echo '<li>Database Queries: ' . $metrics['database']['queries'] . '</li>';
        echo '</ul>';
        
        echo '</div>';
    } catch (Exception $e) {
        echo '<div class="test-section">';
        echo '<p class="error">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</div>';
    }
    
    echo '</div>';
    
    // Test 2: System Controller
    echo '<div class="test-container">';
    echo '<h2>Test 2: System Diagnostics REST Controller</h2>';
    
    try {
        $controller = new MAS_System_Controller();
        echo '<div class="test-section">';
        echo '<p class="success">✓ System Controller instantiated successfully</p>';
        echo '<p class="info">Controller registered at namespace: ' . $controller->get_namespace() . '</p>';
        echo '</div>';
    } catch (Exception $e) {
        echo '<div class="test-section">';
        echo '<p class="error">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</div>';
    }
    
    echo '</div>';
    
    // Test 3: REST API Endpoints
    echo '<div class="test-container">';
    echo '<h2>Test 3: REST API Endpoints</h2>';
    
    $rest_url = rest_url('mas-v2/v1');
    $nonce = wp_create_nonce('wp_rest');
    
    echo '<div class="test-section">';
    echo '<p><strong>REST API Base URL:</strong> ' . $rest_url . '</p>';
    echo '<p><strong>Nonce:</strong> ' . substr($nonce, 0, 20) . '...</p>';
    
    echo '<h3>Available Endpoints:</h3>';
    echo '<ul>';
    echo '<li><code>GET ' . $rest_url . '/system/health</code> - Get overall health status</li>';
    echo '<li><code>GET ' . $rest_url . '/system/info</code> - Get system information</li>';
    echo '<li><code>GET ' . $rest_url . '/system/performance</code> - Get performance metrics</li>';
    echo '<li><code>GET ' . $rest_url . '/system/conflicts</code> - Get conflict detection</li>';
    echo '<li><code>GET ' . $rest_url . '/system/cache</code> - Get cache status</li>';
    echo '<li><code>DELETE ' . $rest_url . '/system/cache</code> - Clear all caches</li>';
    echo '</ul>';
    
    echo '<h3>Test Endpoints:</h3>';
    echo '<button class="test-button" onclick="testEndpoint(\'health\')">Test Health</button>';
    echo '<button class="test-button" onclick="testEndpoint(\'info\')">Test Info</button>';
    echo '<button class="test-button" onclick="testEndpoint(\'performance\')">Test Performance</button>';
    echo '<button class="test-button" onclick="testEndpoint(\'conflicts\')">Test Conflicts</button>';
    echo '<button class="test-button" onclick="testEndpoint(\'cache\')">Test Cache Status</button>';
    
    echo '<div id="endpoint-results" style="margin-top: 20px;"></div>';
    
    echo '</div>';
    echo '</div>';
    
    // Test 4: JavaScript Client
    echo '<div class="test-container">';
    echo '<h2>Test 4: JavaScript Client Integration</h2>';
    
    echo '<div class="test-section">';
    echo '<p>Testing MASRestClient diagnostics methods...</p>';
    
    echo '<button class="test-button" onclick="testJSClient()">Test JavaScript Client</button>';
    echo '<div id="js-client-results" style="margin-top: 20px;"></div>';
    
    echo '</div>';
    echo '</div>';
    ?>

    <script>
        // Setup REST API settings
        window.wpApiSettings = {
            root: '<?php echo rest_url(); ?>',
            nonce: '<?php echo $nonce; ?>'
        };
    </script>
    
    <!-- Load REST Client -->
    <script src="<?php echo plugins_url('assets/js/mas-rest-client.js', __FILE__); ?>"></script>
    
    <script>
        // Initialize REST client
        const restClient = new MASRestClient({
            debug: true
        });
        
        // Test endpoint function
        async function testEndpoint(endpoint) {
            const resultsDiv = document.getElementById('endpoint-results');
            resultsDiv.innerHTML = '<p>Testing endpoint: <code>/system/' + endpoint + '</code>...</p>';
            
            try {
                let result;
                
                switch(endpoint) {
                    case 'health':
                        result = await restClient.getSystemHealth();
                        break;
                    case 'info':
                        result = await restClient.getSystemInfo();
                        break;
                    case 'performance':
                        result = await restClient.getPerformanceMetrics();
                        break;
                    case 'conflicts':
                        result = await restClient.getConflicts();
                        break;
                    case 'cache':
                        result = await restClient.getCacheStatus();
                        break;
                }
                
                resultsDiv.innerHTML = '<p class="success">✓ Endpoint test successful!</p>' +
                    '<pre>' + JSON.stringify(result, null, 2) + '</pre>';
                    
            } catch (error) {
                resultsDiv.innerHTML = '<p class="error">✗ Endpoint test failed: ' + error.message + '</p>' +
                    '<pre>' + JSON.stringify(error, null, 2) + '</pre>';
            }
        }
        
        // Test JavaScript client
        async function testJSClient() {
            const resultsDiv = document.getElementById('js-client-results');
            resultsDiv.innerHTML = '<p>Testing JavaScript client methods...</p>';
            
            const tests = [];
            
            try {
                // Test getSystemHealth
                console.log('Testing getSystemHealth...');
                const health = await restClient.getSystemHealth();
                tests.push({
                    method: 'getSystemHealth()',
                    status: 'success',
                    result: 'Status: ' + health.status
                });
                
                // Test getSystemInfo
                console.log('Testing getSystemInfo...');
                const info = await restClient.getSystemInfo();
                tests.push({
                    method: 'getSystemInfo()',
                    status: 'success',
                    result: 'PHP: ' + info.php.version + ', WP: ' + info.wordpress.version
                });
                
                // Test getPerformanceMetrics
                console.log('Testing getPerformanceMetrics...');
                const metrics = await restClient.getPerformanceMetrics();
                tests.push({
                    method: 'getPerformanceMetrics()',
                    status: 'success',
                    result: 'Memory: ' + metrics.memory.current
                });
                
                // Test getCacheStatus
                console.log('Testing getCacheStatus...');
                const cache = await restClient.getCacheStatus();
                tests.push({
                    method: 'getCacheStatus()',
                    status: 'success',
                    result: 'Type: ' + cache.cache_type
                });
                
                // Display results
                let html = '<table><tr><th>Method</th><th>Status</th><th>Result</th></tr>';
                tests.forEach(test => {
                    html += '<tr>';
                    html += '<td><code>' + test.method + '</code></td>';
                    html += '<td><span class="badge badge-success">SUCCESS</span></td>';
                    html += '<td>' + test.result + '</td>';
                    html += '</tr>';
                });
                html += '</table>';
                
                resultsDiv.innerHTML = '<p class="success">✓ All JavaScript client tests passed!</p>' + html;
                
            } catch (error) {
                resultsDiv.innerHTML = '<p class="error">✗ JavaScript client test failed: ' + error.message + '</p>' +
                    '<pre>' + JSON.stringify(error, null, 2) + '</pre>';
            }
        }
    </script>
</body>
</html>
