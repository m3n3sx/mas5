<?php
/**
 * Test REST API Settings Endpoints
 * 
 * This file tests the Phase 2 implementation of the REST API settings endpoints.
 * 
 * @package ModernAdminStylerV2
 * @since 2.2.0
 */

// Load WordPress
require_once dirname(__FILE__) . '/../../../wp-load.php';

// Check if user is logged in and has admin capabilities
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('You must be logged in as an administrator to run this test.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>MAS REST API Settings Test</title>
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
            border-radius: 8px;
            padding: 30px;
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
        .info {
            color: #2271b1;
        }
        button {
            background: #2271b1;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
            font-size: 14px;
        }
        button:hover {
            background: #135e96;
        }
        button:disabled {
            background: #dcdcde;
            cursor: not-allowed;
        }
        pre {
            background: #1d2327;
            color: #f0f0f1;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 12px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: #f6f7f7;
            padding: 15px;
            border-radius: 4px;
            text-align: center;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #2271b1;
        }
        .stat-label {
            color: #646970;
            font-size: 14px;
            margin-top: 5px;
        }
        #log {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🧪 MAS REST API Settings Test - Phase 2</h1>
        
        <div class="test-section">
            <h2>Test Configuration</h2>
            <p><strong>REST API URL:</strong> <span class="info" id="api-url"></span></p>
            <p><strong>Nonce:</strong> <span class="info" id="nonce-status"></span></p>
            <p><strong>User:</strong> <?php echo wp_get_current_user()->user_login; ?> (ID: <?php echo get_current_user_id(); ?>)</p>
        </div>
        
        <div class="test-section">
            <h2>Quick Tests</h2>
            <button onclick="testGetSettings()">1. GET Settings</button>
            <button onclick="testSaveSettings()">2. POST Settings (Save)</button>
            <button onclick="testUpdateSettings()">3. PUT Settings (Update)</button>
            <button onclick="testResetSettings()">4. DELETE Settings (Reset)</button>
            <button onclick="runAllTests()">▶️ Run All Tests</button>
            <button onclick="clearLog()">🗑️ Clear Log</button>
        </div>
        
        <div class="test-section">
            <h2>Test Statistics</h2>
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-value" id="stat-total">0</div>
                    <div class="stat-label">Total Tests</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value success" id="stat-passed">0</div>
                    <div class="stat-label">Passed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value error" id="stat-failed">0</div>
                    <div class="stat-label">Failed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="stat-time">0ms</div>
                    <div class="stat-label">Total Time</div>
                </div>
            </div>
        </div>
        
        <div class="test-section">
            <h2>Test Log</h2>
            <div id="log"></div>
        </div>
    </div>
    
    <script>
        // Initialize
        const apiUrl = '<?php echo rest_url('mas-v2/v1'); ?>';
        const nonce = '<?php echo wp_create_nonce('wp_rest'); ?>';
        
        document.getElementById('api-url').textContent = apiUrl;
        document.getElementById('nonce-status').textContent = nonce ? '✓ Valid' : '✗ Missing';
        
        // Statistics
        let stats = {
            total: 0,
            passed: 0,
            failed: 0,
            startTime: 0,
            totalTime: 0
        };
        
        // Logging
        function log(message, type = 'info') {
            const logDiv = document.getElementById('log');
            const timestamp = new Date().toLocaleTimeString();
            const colors = {
                success: '#00a32a',
                error: '#d63638',
                info: '#2271b1',
                warning: '#dba617'
            };
            
            const entry = document.createElement('div');
            entry.style.marginBottom = '10px';
            entry.style.padding = '10px';
            entry.style.background = 'white';
            entry.style.borderLeft = `4px solid ${colors[type]}`;
            entry.innerHTML = `<strong>[${timestamp}]</strong> ${message}`;
            
            logDiv.appendChild(entry);
            logDiv.scrollTop = logDiv.scrollHeight;
        }
        
        function clearLog() {
            document.getElementById('log').innerHTML = '';
            stats = { total: 0, passed: 0, failed: 0, startTime: 0, totalTime: 0 };
            updateStats();
        }
        
        function updateStats() {
            document.getElementById('stat-total').textContent = stats.total;
            document.getElementById('stat-passed').textContent = stats.passed;
            document.getElementById('stat-failed').textContent = stats.failed;
            document.getElementById('stat-time').textContent = stats.totalTime + 'ms';
        }
        
        // REST API Helper
        async function restRequest(endpoint, options = {}) {
            const url = apiUrl + endpoint;
            const headers = {
                'Content-Type': 'application/json',
                'X-WP-Nonce': nonce,
                ...options.headers
            };
            
            const response = await fetch(url, {
                ...options,
                headers,
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Request failed');
            }
            
            return data;
        }
        
        // Test Functions
        async function testGetSettings() {
            stats.total++;
            const testStart = Date.now();
            
            try {
                log('Testing GET /settings...', 'info');
                
                const response = await restRequest('/settings', {
                    method: 'GET'
                });
                
                if (response.success && response.data) {
                    const settingsCount = Object.keys(response.data).length;
                    log(`✓ GET Settings successful - Retrieved ${settingsCount} settings`, 'success');
                    log(`<pre>${JSON.stringify(response.data, null, 2).substring(0, 500)}...</pre>`, 'info');
                    stats.passed++;
                } else {
                    throw new Error('Invalid response format');
                }
            } catch (error) {
                log(`✗ GET Settings failed: ${error.message}`, 'error');
                stats.failed++;
            }
            
            stats.totalTime += Date.now() - testStart;
            updateStats();
        }
        
        async function testSaveSettings() {
            stats.total++;
            const testStart = Date.now();
            
            try {
                log('Testing POST /settings (Save)...', 'info');
                
                const testSettings = {
                    menu_background: '#ff0000',
                    menu_text_color: '#ffffff',
                    test_timestamp: Date.now()
                };
                
                const response = await restRequest('/settings', {
                    method: 'POST',
                    body: JSON.stringify(testSettings)
                });
                
                if (response.success && response.data && response.data.settings) {
                    log('✓ POST Settings successful - Settings saved', 'success');
                    log(`<pre>${JSON.stringify(response.data, null, 2)}</pre>`, 'info');
                    stats.passed++;
                } else {
                    throw new Error('Invalid response format');
                }
            } catch (error) {
                log(`✗ POST Settings failed: ${error.message}`, 'error');
                stats.failed++;
            }
            
            stats.totalTime += Date.now() - testStart;
            updateStats();
        }
        
        async function testUpdateSettings() {
            stats.total++;
            const testStart = Date.now();
            
            try {
                log('Testing PUT /settings (Update)...', 'info');
                
                const partialSettings = {
                    menu_background: '#00ff00',
                    test_update_timestamp: Date.now()
                };
                
                const response = await restRequest('/settings', {
                    method: 'PUT',
                    body: JSON.stringify(partialSettings)
                });
                
                if (response.success && response.data && response.data.settings) {
                    log('✓ PUT Settings successful - Settings updated', 'success');
                    log(`<pre>${JSON.stringify(response.data, null, 2)}</pre>`, 'info');
                    stats.passed++;
                } else {
                    throw new Error('Invalid response format');
                }
            } catch (error) {
                log(`✗ PUT Settings failed: ${error.message}`, 'error');
                stats.failed++;
            }
            
            stats.totalTime += Date.now() - testStart;
            updateStats();
        }
        
        async function testResetSettings() {
            stats.total++;
            const testStart = Date.now();
            
            try {
                log('Testing DELETE /settings (Reset)...', 'info');
                
                const response = await restRequest('/settings', {
                    method: 'DELETE'
                });
                
                if (response.success && response.data && response.data.settings) {
                    log('✓ DELETE Settings successful - Settings reset to defaults', 'success');
                    log(`<pre>${JSON.stringify(response.data, null, 2).substring(0, 500)}...</pre>`, 'info');
                    stats.passed++;
                } else {
                    throw new Error('Invalid response format');
                }
            } catch (error) {
                log(`✗ DELETE Settings failed: ${error.message}`, 'error');
                stats.failed++;
            }
            
            stats.totalTime += Date.now() - testStart;
            updateStats();
        }
        
        async function runAllTests() {
            clearLog();
            log('🚀 Starting all tests...', 'info');
            
            await testGetSettings();
            await new Promise(resolve => setTimeout(resolve, 500));
            
            await testSaveSettings();
            await new Promise(resolve => setTimeout(resolve, 500));
            
            await testUpdateSettings();
            await new Promise(resolve => setTimeout(resolve, 500));
            
            await testResetSettings();
            
            log('✅ All tests completed!', 'success');
        }
        
        // Initial log
        log('Test environment ready. Click a button to start testing.', 'info');
    </script>
</body>
</html>
