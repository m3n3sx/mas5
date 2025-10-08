<?php
/**
 * Test Task 6: Enhanced ajaxGetPreviewCSS() Method
 * 
 * This test verifies:
 * - Debug logging is added when WP_DEBUG is enabled
 * - Error handling logs nonce and permission failures
 * - Response includes setting name, value, and performance metrics
 * 
 * USAGE:
 * 1. Enable WP_DEBUG in wp-config.php: define('WP_DEBUG', true);
 * 2. Enable debug logging: define('WP_DEBUG_LOG', true);
 * 3. Load this file in WordPress admin
 * 4. Open browser console (F12)
 * 5. Check debug.log file for server-side logs
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('You must be an administrator to run this test.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Task 6: Enhanced ajaxGetPreviewCSS() Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
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
        button {
            background: #2271b1;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin: 5px;
        }
        button:hover {
            background: #135e96;
        }
        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .log-output {
            background: #1d2327;
            color: #50fa7b;
            padding: 15px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 400px;
            overflow-y: auto;
            margin-top: 10px;
        }
        .log-entry {
            margin: 5px 0;
            padding: 5px;
            border-left: 3px solid #50fa7b;
            padding-left: 10px;
        }
        .log-entry.error {
            border-left-color: #ff5555;
            color: #ff5555;
        }
        .log-entry.warning {
            border-left-color: #f1fa8c;
            color: #f1fa8c;
        }
        .response-data {
            background: #f6f7f7;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        .metric {
            display: inline-block;
            background: #2271b1;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            margin: 5px;
            font-size: 12px;
        }
        .checklist {
            list-style: none;
            padding: 0;
        }
        .checklist li {
            padding: 8px;
            margin: 5px 0;
            background: #f6f7f7;
            border-radius: 4px;
        }
        .checklist li.pass::before {
            content: "✓ ";
            color: #00a32a;
            font-weight: bold;
        }
        .checklist li.fail::before {
            content: "✗ ";
            color: #d63638;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🧪 Task 6: Enhanced ajaxGetPreviewCSS() Method Test</h1>
        
        <div class="test-section">
            <h3>Test Configuration</h3>
            <p><strong>WP_DEBUG:</strong> <?php echo defined('WP_DEBUG') && WP_DEBUG ? '<span class="success">Enabled ✓</span>' : '<span class="error">Disabled ✗</span>'; ?></p>
            <p><strong>WP_DEBUG_LOG:</strong> <?php echo defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? '<span class="success">Enabled ✓</span>' : '<span class="warning">Disabled (logs won\'t be written to file)</span>'; ?></p>
            <p><strong>AJAX URL:</strong> <?php echo admin_url('admin-ajax.php'); ?></p>
            <p><strong>Nonce:</strong> <code id="nonce-value"><?php echo wp_create_nonce('mas_v2_nonce'); ?></code></p>
        </div>

        <h2>Test 1: Valid Request with Debug Logging</h2>
        <div class="test-section">
            <p>Tests that the method logs debug information when WP_DEBUG is enabled.</p>
            <button onclick="testValidRequest()">Run Test 1</button>
            <div id="test1-output"></div>
        </div>

        <h2>Test 2: Invalid Nonce (Error Handling)</h2>
        <div class="test-section">
            <p>Tests that nonce verification failures are logged properly.</p>
            <button onclick="testInvalidNonce()">Run Test 2</button>
            <div id="test2-output"></div>
        </div>

        <h2>Test 3: Response Data Structure</h2>
        <div class="test-section">
            <p>Tests that response includes setting name, value, and performance metrics.</p>
            <button onclick="testResponseStructure()">Run Test 3</button>
            <div id="test3-output"></div>
        </div>

        <h2>Test 4: Multiple Settings Changes</h2>
        <div class="test-section">
            <p>Tests rapid setting changes with performance metrics.</p>
            <button onclick="testMultipleChanges()">Run Test 4</button>
            <div id="test4-output"></div>
        </div>

        <h2>Test Results Summary</h2>
        <div class="test-section">
            <ul class="checklist" id="results-checklist">
                <li>Run tests to see results...</li>
            </ul>
        </div>

        <div class="test-section">
            <h3>📋 Check Server Logs</h3>
            <p>After running tests, check your WordPress debug.log file for server-side logging:</p>
            <code><?php echo WP_CONTENT_DIR . '/debug.log'; ?></code>
            <p>Look for entries starting with "MAS:" to verify debug logging is working.</p>
        </div>
    </div>

    <script>
        const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        const nonce = '<?php echo wp_create_nonce('mas_v2_nonce'); ?>';
        const results = {
            test1: false,
            test2: false,
            test3: false,
            test4: false
        };

        function log(message, type = 'info') {
            console.log(`[MAS Test] ${message}`);
        }

        function displayResponse(containerId, response, success = true) {
            const container = document.getElementById(containerId);
            const statusClass = success ? 'success' : 'error';
            const statusText = success ? 'SUCCESS' : 'FAILED';
            
            let html = `<div class="log-output">`;
            html += `<div class="log-entry ${statusClass}">[${statusText}] Response received</div>`;
            html += `<div class="response-data">${JSON.stringify(response, null, 2)}</div>`;
            html += `</div>`;
            
            container.innerHTML = html;
        }

        function updateChecklist() {
            const checklist = document.getElementById('results-checklist');
            checklist.innerHTML = `
                <li class="${results.test1 ? 'pass' : 'fail'}">Test 1: Valid Request with Debug Logging</li>
                <li class="${results.test2 ? 'pass' : 'fail'}">Test 2: Invalid Nonce Error Handling</li>
                <li class="${results.test3 ? 'pass' : 'fail'}">Test 3: Response Data Structure</li>
                <li class="${results.test4 ? 'pass' : 'fail'}">Test 4: Multiple Settings Changes</li>
            `;
        }

        async function testValidRequest() {
            log('Test 1: Sending valid request...');
            
            try {
                const response = await fetch(ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'mas_v2_get_preview_css',
                        nonce: nonce,
                        setting: 'admin_bar_bg',
                        value: '#2271b1'
                    })
                });

                const data = await response.json();
                log('Test 1: Response received', data);

                // Verify response structure
                const hasCSS = data.success && data.data && data.data.css;
                const hasPerformance = data.data && data.data.performance;
                const hasSetting = data.data && data.data.setting === 'admin_bar_bg';
                const hasValue = data.data && data.data.value === '#2271b1';

                results.test1 = hasCSS && hasPerformance && hasSetting && hasValue;
                
                displayResponse('test1-output', data, results.test1);
                
                if (results.test1) {
                    log('✓ Test 1 PASSED: All required data present');
                } else {
                    log('✗ Test 1 FAILED: Missing required data');
                }
                
                updateChecklist();
            } catch (error) {
                log('✗ Test 1 ERROR: ' + error.message);
                displayResponse('test1-output', { error: error.message }, false);
                updateChecklist();
            }
        }

        async function testInvalidNonce() {
            log('Test 2: Sending request with invalid nonce...');
            
            try {
                const response = await fetch(ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'mas_v2_get_preview_css',
                        nonce: 'invalid_nonce_12345',
                        setting: 'admin_bar_bg',
                        value: '#ff0000'
                    })
                });

                const data = await response.json();
                log('Test 2: Response received', data);

                // Should receive error response
                results.test2 = !data.success && data.data && data.data.message;
                
                displayResponse('test2-output', data, results.test2);
                
                if (results.test2) {
                    log('✓ Test 2 PASSED: Error properly returned');
                } else {
                    log('✗ Test 2 FAILED: Expected error response');
                }
                
                updateChecklist();
            } catch (error) {
                log('✗ Test 2 ERROR: ' + error.message);
                displayResponse('test2-output', { error: error.message }, false);
                updateChecklist();
            }
        }

        async function testResponseStructure() {
            log('Test 3: Testing response data structure...');
            
            try {
                const response = await fetch(ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'mas_v2_get_preview_css',
                        nonce: nonce,
                        setting: 'menu_bg',
                        value: '#1d2327'
                    })
                });

                const data = await response.json();
                log('Test 3: Response received', data);

                // Verify all required fields
                const checks = {
                    success: data.success === true,
                    css: data.data && typeof data.data.css === 'string' && data.data.css.length > 0,
                    setting: data.data && data.data.setting === 'menu_bg',
                    value: data.data && data.data.value === '#1d2327',
                    performance: data.data && data.data.performance,
                    execution_time: data.data && data.data.performance && typeof data.data.performance.execution_time_ms === 'number',
                    memory_usage: data.data && data.data.performance && typeof data.data.performance.memory_usage_mb === 'number',
                    css_length: data.data && data.data.performance && typeof data.data.performance.css_length === 'number'
                };

                results.test3 = Object.values(checks).every(v => v === true);
                
                let html = `<div class="log-output">`;
                html += `<div class="log-entry ${results.test3 ? 'success' : 'error'}">[${results.test3 ? 'SUCCESS' : 'FAILED'}] Response Structure Check</div>`;
                html += `<div class="response-data">`;
                for (const [key, value] of Object.entries(checks)) {
                    html += `${value ? '✓' : '✗'} ${key}: ${value}<br>`;
                }
                html += `</div>`;
                
                if (data.data && data.data.performance) {
                    html += `<div style="margin-top: 10px;">`;
                    html += `<span class="metric">⏱️ ${data.data.performance.execution_time_ms}ms</span>`;
                    html += `<span class="metric">💾 ${data.data.performance.memory_usage_mb}MB</span>`;
                    html += `<span class="metric">📄 ${data.data.performance.css_length} chars</span>`;
                    html += `</div>`;
                }
                
                html += `</div>`;
                document.getElementById('test3-output').innerHTML = html;
                
                if (results.test3) {
                    log('✓ Test 3 PASSED: All response fields present and correct');
                } else {
                    log('✗ Test 3 FAILED: Missing or incorrect response fields');
                }
                
                updateChecklist();
            } catch (error) {
                log('✗ Test 3 ERROR: ' + error.message);
                displayResponse('test3-output', { error: error.message }, false);
                updateChecklist();
            }
        }

        async function testMultipleChanges() {
            log('Test 4: Testing multiple rapid changes...');
            
            const settings = [
                { setting: 'admin_bar_bg', value: '#2271b1' },
                { setting: 'menu_bg', value: '#1d2327' },
                { setting: 'menu_text', value: '#ffffff' },
                { setting: 'admin_bar_text', value: '#f0f0f1' }
            ];

            const container = document.getElementById('test4-output');
            let html = `<div class="log-output">`;
            
            try {
                const startTime = performance.now();
                const responses = [];

                for (const { setting, value } of settings) {
                    const response = await fetch(ajaxUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'mas_v2_get_preview_css',
                            nonce: nonce,
                            setting: setting,
                            value: value
                        })
                    });

                    const data = await response.json();
                    responses.push(data);
                    
                    html += `<div class="log-entry ${data.success ? 'success' : 'error'}">`;
                    html += `[${data.success ? 'SUCCESS' : 'FAILED'}] ${setting} = ${value}`;
                    if (data.data && data.data.performance) {
                        html += ` (${data.data.performance.execution_time_ms}ms)`;
                    }
                    html += `</div>`;
                }

                const totalTime = performance.now() - startTime;
                const allSuccess = responses.every(r => r.success);
                const avgTime = responses.reduce((sum, r) => sum + (r.data?.performance?.execution_time_ms || 0), 0) / responses.length;

                results.test4 = allSuccess && avgTime < 100;
                
                html += `<div class="log-entry ${results.test4 ? 'success' : 'warning'}">`;
                html += `[SUMMARY] Total time: ${totalTime.toFixed(2)}ms, Avg server time: ${avgTime.toFixed(2)}ms`;
                html += `</div>`;
                html += `</div>`;
                
                container.innerHTML = html;
                
                if (results.test4) {
                    log('✓ Test 4 PASSED: All requests successful with good performance');
                } else {
                    log('✗ Test 4 FAILED: Some requests failed or performance issues');
                }
                
                updateChecklist();
            } catch (error) {
                log('✗ Test 4 ERROR: ' + error.message);
                html += `<div class="log-entry error">[ERROR] ${error.message}</div></div>`;
                container.innerHTML = html;
                updateChecklist();
            }
        }

        // Initialize
        log('Test page loaded. Ready to run tests.');
        updateChecklist();
    </script>
</body>
</html>
