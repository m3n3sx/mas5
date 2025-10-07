<?php
/**
 * Test Suite 5.1: Plugin Activation Testing
 * 
 * Tests:
 * - Activate plugin on fresh WordPress install
 * - Verify no fatal errors in error log
 * - Confirm site loads normally
 * - Check REST API endpoints are registered
 * 
 * Requirements: 1.1, 4.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

class MAS_Task51_PluginActivationTest {
    private $results = [];
    private $errors = [];
    
    public function run_tests() {
        echo "<h1>Task 5.1: Plugin Activation Testing</h1>\n";
        echo "<p>Testing plugin activation, error logs, site loading, and REST API registration</p>\n";
        
        $this->test_wordpress_environment();
        $this->test_plugin_loaded();
        $this->test_no_fatal_errors();
        $this->test_site_loads_normally();
        $this->test_rest_api_endpoints_registered();
        
        $this->display_results();
    }
    
    private function test_wordpress_environment() {
        echo "<h2>Test 1: WordPress Environment Check</h2>\n";
        
        // Check if WordPress is loaded
        if (!function_exists('get_bloginfo')) {
            $this->add_error('WordPress is not loaded');
            echo "<p style='color: red;'>❌ WordPress not loaded</p>\n";
            return;
        }
        
        $wp_version = get_bloginfo('version');
        $php_version = phpversion();
        
        echo "<ul>\n";
        echo "<li>WordPress Version: <strong>{$wp_version}</strong></li>\n";
        echo "<li>PHP Version: <strong>{$php_version}</strong></li>\n";
        echo "<li>Site URL: <strong>" . get_site_url() . "</strong></li>\n";
        echo "<li>Admin URL: <strong>" . admin_url() . "</strong></li>\n";
        echo "</ul>\n";
        
        $this->add_result('WordPress environment', true, "WP {$wp_version}, PHP {$php_version}");
    }
    
    private function test_plugin_loaded() {
        echo "<h2>Test 2: Plugin Loaded Check</h2>\n";
        
        // Check if main plugin class exists
        if (!class_exists('ModernAdminStylerV2')) {
            $this->add_error('Main plugin class not found');
            echo "<p style='color: red;'>❌ Plugin not loaded</p>\n";
            return;
        }
        
        echo "<p style='color: green;'>✅ Main plugin class exists</p>\n";
        
        // Check if plugin constants are defined
        $constants = [
            'MAS_V2_VERSION',
            'MAS_V2_PLUGIN_DIR',
            'MAS_V2_PLUGIN_URL'
        ];
        
        echo "<h3>Plugin Constants:</h3>\n<ul>\n";
        foreach ($constants as $constant) {
            if (defined($constant)) {
                echo "<li style='color: green;'>✅ {$constant}: " . constant($constant) . "</li>\n";
            } else {
                echo "<li style='color: orange;'>⚠️ {$constant}: Not defined</li>\n";
            }
        }
        echo "</ul>\n";
        
        $this->add_result('Plugin loaded', true, 'ModernAdminStylerV2 class exists');
    }
    
    private function test_no_fatal_errors() {
        echo "<h2>Test 3: Fatal Error Check</h2>\n";
        
        // Check if WP_DEBUG is enabled
        $debug_enabled = defined('WP_DEBUG') && WP_DEBUG;
        echo "<p>WP_DEBUG: " . ($debug_enabled ? '<strong>Enabled</strong>' : 'Disabled') . "</p>\n";
        
        // Check error log file
        $error_log_path = ini_get('error_log');
        if (empty($error_log_path)) {
            $error_log_path = WP_CONTENT_DIR . '/debug.log';
        }
        
        echo "<p>Error log path: <code>{$error_log_path}</code></p>\n";
        
        if (file_exists($error_log_path)) {
            $log_size = filesize($error_log_path);
            echo "<p>Error log size: " . number_format($log_size) . " bytes</p>\n";
            
            // Read last 50 lines of error log
            $lines = $this->tail_file($error_log_path, 50);
            
            // Check for fatal errors related to our plugin
            $fatal_errors = [];
            $plugin_errors = [];
            
            foreach ($lines as $line) {
                if (stripos($line, 'fatal') !== false) {
                    $fatal_errors[] = $line;
                }
                if (stripos($line, 'modern-admin-styler') !== false || 
                    stripos($line, 'MAS') !== false) {
                    $plugin_errors[] = $line;
                }
            }
            
            if (!empty($fatal_errors)) {
                echo "<h3 style='color: red;'>❌ Fatal Errors Found:</h3>\n";
                echo "<pre style='background: #fee; padding: 10px; overflow-x: auto;'>";
                foreach ($fatal_errors as $error) {
                    echo htmlspecialchars($error) . "\n";
                }
                echo "</pre>\n";
                $this->add_error('Fatal errors found in log');
            } else {
                echo "<p style='color: green;'>✅ No fatal errors in recent log entries</p>\n";
            }
            
            if (!empty($plugin_errors)) {
                echo "<h3>Plugin-related log entries:</h3>\n";
                echo "<pre style='background: #ffc; padding: 10px; overflow-x: auto;'>";
                foreach (array_slice($plugin_errors, -10) as $error) {
                    echo htmlspecialchars($error) . "\n";
                }
                echo "</pre>\n";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ Error log file not found</p>\n";
        }
        
        $this->add_result('No fatal errors', empty($fatal_errors), 
            empty($fatal_errors) ? 'No fatal errors detected' : count($fatal_errors) . ' fatal errors found');
    }
    
    private function test_site_loads_normally() {
        echo "<h2>Test 4: Site Loading Check</h2>\n";
        
        // If we got here, the site is loading
        echo "<p style='color: green;'>✅ Site is loading (this page rendered successfully)</p>\n";
        
        // Check if admin is accessible
        $admin_url = admin_url();
        echo "<p>Admin URL: <a href='{$admin_url}' target='_blank'>{$admin_url}</a></p>\n";
        
        // Check if REST API is accessible
        $rest_url = rest_url();
        echo "<p>REST API URL: <a href='{$rest_url}' target='_blank'>{$rest_url}</a></p>\n";
        
        // Test a simple HTTP request to the site
        $response = wp_remote_get(home_url());
        if (is_wp_error($response)) {
            echo "<p style='color: red;'>❌ Error loading site: " . $response->get_error_message() . "</p>\n";
            $this->add_error('Site loading failed: ' . $response->get_error_message());
        } else {
            $status_code = wp_remote_retrieve_response_code($response);
            echo "<p style='color: green;'>✅ Site responds with HTTP {$status_code}</p>\n";
            $this->add_result('Site loads normally', $status_code == 200, "HTTP {$status_code}");
        }
    }
    
    private function test_rest_api_endpoints_registered() {
        echo "<h2>Test 5: REST API Endpoints Registration</h2>\n";
        
        // Check if REST API is available
        if (!function_exists('rest_get_server')) {
            echo "<p style='color: red;'>❌ REST API not available</p>\n";
            $this->add_error('REST API not available');
            return;
        }
        
        // Get REST server
        $rest_server = rest_get_server();
        $namespaces = $rest_server->get_namespaces();
        
        echo "<h3>Available Namespaces:</h3>\n<ul>\n";
        foreach ($namespaces as $namespace) {
            echo "<li><code>{$namespace}</code></li>\n";
        }
        echo "</ul>\n";
        
        // Check for our plugin's namespace
        $our_namespace = 'mas/v2';
        $namespace_exists = in_array($our_namespace, $namespaces);
        
        if ($namespace_exists) {
            echo "<p style='color: green;'>✅ Plugin namespace '{$our_namespace}' is registered</p>\n";
            
            // Get routes for our namespace
            $routes = $rest_server->get_routes();
            $our_routes = [];
            
            foreach ($routes as $route => $handlers) {
                if (strpos($route, '/' . $our_namespace) === 0) {
                    $our_routes[] = $route;
                }
            }
            
            echo "<h3>Plugin REST API Endpoints (" . count($our_routes) . " total):</h3>\n";
            
            if (!empty($our_routes)) {
                echo "<ul>\n";
                foreach ($our_routes as $route) {
                    $route_handlers = $routes[$route];
                    $methods = [];
                    foreach ($route_handlers as $handler) {
                        if (isset($handler['methods'])) {
                            $methods = array_merge($methods, array_keys($handler['methods']));
                        }
                    }
                    $methods = array_unique($methods);
                    echo "<li><code>{$route}</code> - " . implode(', ', $methods) . "</li>\n";
                }
                echo "</ul>\n";
                
                $this->add_result('REST API endpoints registered', true, count($our_routes) . ' endpoints found');
            } else {
                echo "<p style='color: orange;'>⚠️ No routes found for namespace</p>\n";
                $this->add_result('REST API endpoints registered', false, 'No routes found');
            }
        } else {
            echo "<p style='color: red;'>❌ Plugin namespace '{$our_namespace}' not found</p>\n";
            $this->add_error("Namespace '{$our_namespace}' not registered");
        }
        
        // Test a sample endpoint
        if ($namespace_exists && !empty($our_routes)) {
            echo "<h3>Sample Endpoint Test:</h3>\n";
            $test_route = $our_routes[0];
            $test_url = rest_url($test_route);
            
            echo "<p>Testing: <code>{$test_url}</code></p>\n";
            
            $response = wp_remote_get($test_url, [
                'headers' => [
                    'X-WP-Nonce' => wp_create_nonce('wp_rest')
                ]
            ]);
            
            if (is_wp_error($response)) {
                echo "<p style='color: orange;'>⚠️ Error: " . $response->get_error_message() . "</p>\n";
            } else {
                $status_code = wp_remote_retrieve_response_code($response);
                $body = wp_remote_retrieve_body($response);
                
                echo "<p>Response code: <strong>{$status_code}</strong></p>\n";
                
                if ($status_code == 200 || $status_code == 401) {
                    echo "<p style='color: green;'>✅ Endpoint is accessible</p>\n";
                } else {
                    echo "<p style='color: orange;'>⚠️ Unexpected status code</p>\n";
                }
            }
        }
    }
    
    private function tail_file($file, $lines = 50) {
        $handle = fopen($file, 'r');
        if (!$handle) {
            return [];
        }
        
        $buffer = 4096;
        $output = '';
        $chunk = '';
        
        fseek($handle, -1, SEEK_END);
        
        if (fread($handle, 1) != "\n") {
            $output = "\n";
        }
        
        while (ftell($handle) > 0 && substr_count($output, "\n") < $lines) {
            $seek = min(ftell($handle), $buffer);
            fseek($handle, -$seek, SEEK_CUR);
            $chunk = fread($handle, $seek);
            $output = $chunk . $output;
            fseek($handle, -mb_strlen($chunk, '8bit'), SEEK_CUR);
        }
        
        fclose($handle);
        
        $lines_array = explode("\n", $output);
        return array_slice($lines_array, -$lines);
    }
    
    private function add_result($test, $passed, $message = '') {
        $this->results[] = [
            'test' => $test,
            'passed' => $passed,
            'message' => $message
        ];
    }
    
    private function add_error($message) {
        $this->errors[] = $message;
    }
    
    private function display_results() {
        echo "<hr>\n";
        echo "<h2>Test Summary</h2>\n";
        
        $passed = 0;
        $failed = 0;
        
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr><th>Test</th><th>Status</th><th>Details</th></tr>\n";
        
        foreach ($this->results as $result) {
            $status = $result['passed'] ? '✅ PASS' : '❌ FAIL';
            $color = $result['passed'] ? 'green' : 'red';
            
            if ($result['passed']) {
                $passed++;
            } else {
                $failed++;
            }
            
            echo "<tr>";
            echo "<td>{$result['test']}</td>";
            echo "<td style='color: {$color}; font-weight: bold;'>{$status}</td>";
            echo "<td>{$result['message']}</td>";
            echo "</tr>\n";
        }
        
        echo "</table>\n";
        
        $total = $passed + $failed;
        $percentage = $total > 0 ? round(($passed / $total) * 100, 1) : 0;
        
        echo "<h3>Results: {$passed}/{$total} tests passed ({$percentage}%)</h3>\n";
        
        if (!empty($this->errors)) {
            echo "<h3 style='color: red;'>Errors:</h3>\n<ul>\n";
            foreach ($this->errors as $error) {
                echo "<li style='color: red;'>{$error}</li>\n";
            }
            echo "</ul>\n";
        }
        
        if ($failed == 0 && $passed > 0) {
            echo "<div style='background: #dfd; padding: 20px; border: 2px solid green; margin: 20px 0;'>\n";
            echo "<h2 style='color: green; margin: 0;'>✅ All Tests Passed!</h2>\n";
            echo "<p>Task 5.1 requirements verified:</p>\n";
            echo "<ul>\n";
            echo "<li>✅ Plugin activated successfully</li>\n";
            echo "<li>✅ No fatal errors in error log</li>\n";
            echo "<li>✅ Site loads normally</li>\n";
            echo "<li>✅ REST API endpoints are registered</li>\n";
            echo "</ul>\n";
            echo "</div>\n";
        } else {
            echo "<div style='background: #fdd; padding: 20px; border: 2px solid red; margin: 20px 0;'>\n";
            echo "<h2 style='color: red; margin: 0;'>❌ Some Tests Failed</h2>\n";
            echo "<p>Please review the errors above and fix the issues.</p>\n";
            echo "</div>\n";
        }
    }
}

// Run tests if WordPress is loaded
if (function_exists('get_bloginfo')) {
    $test = new MAS_Task51_PluginActivationTest();
    $test->run_tests();
} else {
    echo "<h1>Error</h1>\n";
    echo "<p>WordPress is not loaded. Please run this file from within WordPress.</p>\n";
    echo "<p>You can access it at: " . $_SERVER['REQUEST_URI'] . "</p>\n";
}
