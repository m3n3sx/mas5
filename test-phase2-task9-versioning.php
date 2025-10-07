<?php
/**
 * Test Phase 2 Task 9: API Versioning and Deprecation Management
 * 
 * Tests the API versioning infrastructure, deprecation service,
 * and version handling capabilities.
 * 
 * @package ModernAdminStylerV2
 * @subpackage Tests
 */

// Load WordPress
require_once dirname(__FILE__) . '/../../../wp-load.php';

// Ensure user is logged in as admin
if (!current_user_can('manage_options')) {
    wp_die('You must be logged in as an administrator to run this test.');
}

// Load required files
require_once dirname(__FILE__) . '/includes/services/class-mas-version-manager.php';
require_once dirname(__FILE__) . '/includes/services/class-mas-deprecation-service.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phase 2 Task 9: API Versioning Test</title>
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
            color: #1e1e2e;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        h2 {
            color: #667eea;
            margin-top: 30px;
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #667eea;
        }
        .success {
            color: #10b981;
            font-weight: bold;
        }
        .error {
            color: #ef4444;
            font-weight: bold;
        }
        .warning {
            color: #f59e0b;
            font-weight: bold;
        }
        .info {
            color: #3b82f6;
        }
        pre {
            background: #1e1e2e;
            color: #f8f8f2;
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
            background: #d1fae5;
            border-left: 4px solid #10b981;
        }
        .test-result.fail {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin: 5px;
        }
        button:hover {
            background: #5568d3;
        }
        .code-block {
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 10px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🔄 Phase 2 Task 9: API Versioning and Deprecation Management</h1>
        <p>Testing API versioning infrastructure, deprecation service, and version handling.</p>
        
        <?php
        $tests_passed = 0;
        $tests_failed = 0;
        
        /**
         * Test helper function
         */
        function run_test($name, $callback) {
            global $tests_passed, $tests_failed;
            
            echo "<div class='test-section'>";
            echo "<h3>🧪 $name</h3>";
            
            try {
                $result = $callback();
                if ($result === true) {
                    echo "<div class='test-result pass'><span class='success'>✓ PASS</span></div>";
                    $tests_passed++;
                } else {
                    echo "<div class='test-result fail'><span class='error'>✗ FAIL:</span> $result</div>";
                    $tests_failed++;
                }
            } catch (Exception $e) {
                echo "<div class='test-result fail'><span class='error'>✗ ERROR:</span> " . esc_html($e->getMessage()) . "</div>";
                $tests_failed++;
            }
            
            echo "</div>";
        }
        
        // Test 1: Version Manager Initialization
        run_test('Version Manager Initialization', function() {
            $version_manager = new MAS_Version_Manager();
            
            if (!$version_manager) {
                return 'Failed to create version manager instance';
            }
            
            $default_version = $version_manager->get_default_version();
            if ($default_version !== 'v1') {
                return "Expected default version 'v1', got '$default_version'";
            }
            
            echo "<div class='info'>✓ Version manager initialized with default version: $default_version</div>";
            return true;
        });
        
        // Test 2: Version Validation
        run_test('Version Validation', function() {
            $version_manager = new MAS_Version_Manager();
            
            // Test valid versions
            if (!$version_manager->is_valid_version('v1')) {
                return 'v1 should be valid';
            }
            
            if (!$version_manager->is_valid_version('v2')) {
                return 'v2 should be valid';
            }
            
            // Test invalid version
            if ($version_manager->is_valid_version('v99')) {
                return 'v99 should be invalid';
            }
            
            echo "<div class='info'>✓ Version validation working correctly</div>";
            return true;
        });
        
        // Test 3: Namespace Resolution
        run_test('Namespace Resolution', function() {
            $version_manager = new MAS_Version_Manager();
            
            $namespace_v1 = $version_manager->get_namespace('v1');
            if ($namespace_v1 !== 'mas-v2/v1') {
                return "Expected 'mas-v2/v1', got '$namespace_v1'";
            }
            
            $namespace_v2 = $version_manager->get_namespace('v2');
            if ($namespace_v2 !== 'mas-v2/v2') {
                return "Expected 'mas-v2/v2', got '$namespace_v2'";
            }
            
            echo "<div class='info'>✓ v1 namespace: $namespace_v1</div>";
            echo "<div class='info'>✓ v2 namespace: $namespace_v2</div>";
            return true;
        });
        
        // Test 4: Version Information
        run_test('Version Information Retrieval', function() {
            $version_manager = new MAS_Version_Manager();
            
            $info = $version_manager->get_version_info('v1');
            if (!$info) {
                return 'Failed to get version info for v1';
            }
            
            if (!isset($info['status']) || $info['status'] !== 'stable') {
                return 'v1 should have stable status';
            }
            
            echo "<div class='info'>✓ v1 status: {$info['status']}</div>";
            echo "<div class='info'>✓ v1 released: {$info['released']}</div>";
            return true;
        });
        
        // Test 5: Deprecation Service Initialization
        run_test('Deprecation Service Initialization', function() {
            $deprecation_service = new MAS_Deprecation_Service();
            
            if (!$deprecation_service) {
                return 'Failed to create deprecation service instance';
            }
            
            echo "<div class='info'>✓ Deprecation service initialized successfully</div>";
            return true;
        });
        
        // Test 6: Mark Endpoint as Deprecated
        run_test('Mark Endpoint as Deprecated', function() {
            $deprecation_service = new MAS_Deprecation_Service();
            
            $result = $deprecation_service->mark_deprecated(
                '/settings/legacy',
                'v1',
                '2026-12-31',
                '/settings',
                'Use the new /settings endpoint with updated schema'
            );
            
            if (!$result) {
                return 'Failed to mark endpoint as deprecated';
            }
            
            if (!$deprecation_service->is_deprecated('/settings/legacy', 'v1')) {
                return 'Endpoint should be marked as deprecated';
            }
            
            echo "<div class='info'>✓ Endpoint marked as deprecated successfully</div>";
            return true;
        });
        
        // Test 7: Get Deprecation Info
        run_test('Get Deprecation Information', function() {
            $deprecation_service = new MAS_Deprecation_Service();
            
            // Mark an endpoint as deprecated first
            $deprecation_service->mark_deprecated(
                '/themes/old',
                'v1',
                '2026-06-30',
                '/themes',
                'Use the enhanced themes endpoint'
            );
            
            $info = $deprecation_service->get_deprecation_info('/themes/old', 'v1');
            
            if (!$info) {
                return 'Failed to get deprecation info';
            }
            
            if ($info['removal_date'] !== '2026-06-30') {
                return 'Incorrect removal date';
            }
            
            if ($info['replacement'] !== '/themes') {
                return 'Incorrect replacement endpoint';
            }
            
            echo "<div class='info'>✓ Endpoint: {$info['endpoint']}</div>";
            echo "<div class='info'>✓ Removal date: {$info['removal_date']}</div>";
            echo "<div class='info'>✓ Replacement: {$info['replacement']}</div>";
            return true;
        });
        
        // Test 8: Generate Warning Message
        run_test('Generate Deprecation Warning Message', function() {
            $deprecation_service = new MAS_Deprecation_Service();
            
            // Mark an endpoint as deprecated
            $deprecation_service->mark_deprecated(
                '/backups/old',
                'v1',
                '2026-12-31',
                '/backups',
                'Use the enhanced backup endpoint'
            );
            
            $message = $deprecation_service->get_warning_message('/backups/old', 'v1');
            
            if (empty($message)) {
                return 'Warning message should not be empty';
            }
            
            if (strpos($message, '2026-12-31') === false) {
                return 'Warning message should contain removal date';
            }
            
            if (strpos($message, '/backups') === false) {
                return 'Warning message should contain replacement endpoint';
            }
            
            echo "<div class='code-block'>" . esc_html($message) . "</div>";
            return true;
        });
        
        // Test 9: Generate Warning Header
        run_test('Generate RFC 7234 Warning Header', function() {
            $deprecation_service = new MAS_Deprecation_Service();
            
            // Mark an endpoint as deprecated
            $deprecation_service->mark_deprecated(
                '/export/old',
                'v1',
                '2026-12-31',
                '/export',
                'Use the new export endpoint'
            );
            
            $header = $deprecation_service->get_warning_header('/export/old', 'v1');
            
            if (empty($header)) {
                return 'Warning header should not be empty';
            }
            
            if (strpos($header, '299') === false) {
                return 'Warning header should use warn-code 299';
            }
            
            echo "<div class='code-block'>Warning: " . esc_html($header) . "</div>";
            return true;
        });
        
        // Test 10: Version Detection from Mock Request
        run_test('Version Detection from Request', function() {
            $version_manager = new MAS_Version_Manager();
            
            // Create a mock request object
            $request = new WP_REST_Request('GET', '/mas-v2/v2/settings');
            
            $detected_version = $version_manager->get_version_from_request($request);
            
            if ($detected_version !== 'v2') {
                return "Expected 'v2', got '$detected_version'";
            }
            
            echo "<div class='info'>✓ Detected version from route: $detected_version</div>";
            return true;
        });
        
        // Display summary
        echo "<div class='test-section'>";
        echo "<h2>📊 Test Summary</h2>";
        $total_tests = $tests_passed + $tests_failed;
        $pass_rate = $total_tests > 0 ? round(($tests_passed / $total_tests) * 100, 1) : 0;
        
        echo "<p><strong>Total Tests:</strong> $total_tests</p>";
        echo "<p><span class='success'>Passed:</span> $tests_passed</p>";
        echo "<p><span class='error'>Failed:</span> $tests_failed</p>";
        echo "<p><strong>Pass Rate:</strong> $pass_rate%</p>";
        
        if ($tests_failed === 0) {
            echo "<div class='test-result pass'>";
            echo "<h3 class='success'>✓ All Tests Passed!</h3>";
            echo "<p>API versioning and deprecation management is working correctly.</p>";
            echo "</div>";
        } else {
            echo "<div class='test-result fail'>";
            echo "<h3 class='error'>✗ Some Tests Failed</h3>";
            echo "<p>Please review the failed tests above and fix any issues.</p>";
            echo "</div>";
        }
        echo "</div>";
        ?>
        
        <div class="test-section">
            <h2>📚 JavaScript Client Testing</h2>
            <p>Open the browser console to see JavaScript version handling tests.</p>
            <button onclick="runJavaScriptTests()">Run JavaScript Tests</button>
            <div id="js-test-results"></div>
        </div>
    </div>
    
    <script>
        // Load the REST client
        const script = document.createElement('script');
        script.src = '<?php echo plugins_url('assets/js/mas-rest-client.js', __FILE__); ?>';
        document.head.appendChild(script);
        
        script.onload = function() {
            console.log('%c[MAS Versioning Test] REST Client loaded', 'color: #10b981; font-weight: bold;');
        };
        
        async function runJavaScriptTests() {
            const resultsDiv = document.getElementById('js-test-results');
            resultsDiv.innerHTML = '<p>Running tests... Check console for details.</p>';
            
            console.log('%c=== JavaScript Version Handling Tests ===', 'color: #667eea; font-weight: bold; font-size: 16px;');
            
            try {
                // Test 1: Create client with version
                console.log('%c\nTest 1: Create client with specific version', 'color: #3b82f6; font-weight: bold;');
                const client = new MASRestClient({
                    version: 'v2',
                    debug: true
                });
                console.log('✓ Client created with version:', client.getVersion());
                
                // Test 2: Change version
                console.log('%c\nTest 2: Change API version', 'color: #3b82f6; font-weight: bold;');
                client.setVersion('v1');
                console.log('✓ Version changed to:', client.getVersion());
                
                // Test 3: Get migration info (will show deprecation if endpoint is deprecated)
                console.log('%c\nTest 3: Get migration info', 'color: #3b82f6; font-weight: bold;');
                const migrationInfo = await client.getMigrationInfo('/settings');
                if (migrationInfo) {
                    console.log('⚠ Endpoint is deprecated:', migrationInfo);
                } else {
                    console.log('✓ Endpoint is not deprecated');
                }
                
                // Test 4: Check deprecation warnings
                console.log('%c\nTest 4: Check deprecation warnings', 'color: #3b82f6; font-weight: bold;');
                const warnings = client.getDeprecationWarnings();
                console.log('Deprecation warnings encountered:', warnings);
                
                resultsDiv.innerHTML = '<div class="test-result pass">✓ JavaScript tests completed. Check console for details.</div>';
                
            } catch (error) {
                console.error('✗ Test failed:', error);
                resultsDiv.innerHTML = '<div class="test-result fail">✗ JavaScript tests failed. Check console for details.</div>';
            }
        }
        
        // Listen for deprecation events
        window.addEventListener('mas-api-deprecated', function(event) {
            console.warn('%c[Deprecation Event]', 'color: #f59e0b; font-weight: bold;', event.detail);
        });
    </script>
</body>
</html>
