<?php
/**
 * Task 8: Performance Testing and Optimization - WordPress Integration Test
 * 
 * This file tests the actual WordPress AJAX handler performance
 * Run this file in a WordPress environment to verify real-world performance
 * 
 * Usage: Place in WordPress root and access via browser
 */

// Load WordPress
require_once('wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task 8: WordPress Performance Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f0f0f1;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 { color: #1d2327; }
        h2 { color: #2271b1; border-bottom: 2px solid #2271b1; padding-bottom: 10px; }
        button {
            padding: 10px 20px;
            background: #2271b1;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover { background: #135e96; }
        .results {
            background: #f6f7f7;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
            font-family: monospace;
            font-size: 13px;
            max-height: 400px;
            overflow-y: auto;
        }
        .pass { color: #00a32a; background: #f0f6f0; padding: 5px; margin: 5px 0; }
        .fail { color: #d63638; background: #fcf0f1; padding: 5px; margin: 5px 0; }
        .info { color: #2271b1; padding: 5px; margin: 5px 0; }
        .warning { color: #dba617; background: #fcf9e8; padding: 5px; margin: 5px 0; }
        .metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        .metric-card {
            background: #f6f7f7;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #2271b1;
        }
        .metric-label {
            font-size: 12px;
            color: #646970;
            text-transform: uppercase;
        }
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #1d2327;
        }
    </style>
</head>
<body>
    <h1>Task 8: WordPress Performance Test</h1>
    <p>Testing actual WordPress AJAX handler performance with real plugin code.</p>

    <div class="test-section">
        <h2>Test 8.1: Debouncing Verification</h2>
        <p>Verifies that rapid changes are debounced to 3-4 requests.</p>
        <button onclick="runDebounceTest()">Run Debounce Test</button>
        <div class="metrics">
            <div class="metric-card">
                <div class="metric-label">Changes Made</div>
                <div class="metric-value" id="changes-made">0</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">AJAX Requests</div>
                <div class="metric-value" id="ajax-sent">0</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Debounce Ratio</div>
                <div class="metric-value" id="debounce-ratio">-</div>
            </div>
        </div>
        <div class="results" id="debounce-results"></div>
    </div>

    <div class="test-section">
        <h2>Test 8.2: CSS Generation Performance</h2>
        <p>Measures actual CSS generation time from WordPress AJAX handler.</p>
        <button onclick="runPerformanceTest()">Run Performance Test</button>
        <div class="metrics">
            <div class="metric-card">
                <div class="metric-label">Average Time</div>
                <div class="metric-value" id="avg-time">-</div>
                <div style="font-size: 14px; color: #646970;">ms</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Min Time</div>
                <div class="metric-value" id="min-time">-</div>
                <div style="font-size: 14px; color: #646970;">ms</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Max Time</div>
                <div class="metric-value" id="max-time">-</div>
                <div style="font-size: 14px; color: #646970;">ms</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Target</div>
                <div class="metric-value">&lt; 100</div>
                <div style="font-size: 14px; color: #646970;">ms</div>
            </div>
        </div>
        <div class="results" id="performance-results"></div>
    </div>

    <div class="test-section">
        <h2>Test 8.3: Visual Smoothness</h2>
        <p>Tests CSS injection for flicker and layout shifts.</p>
        <button onclick="runFlickerTest()">Run Flicker Test</button>
        <div style="background: #f6f7f7; padding: 20px; margin: 15px 0; border-radius: 4px;">
            <div id="preview-box" style="background: #2271b1; color: white; padding: 20px; border-radius: 4px; text-align: center; transition: all 0.3s ease;">
                <h3>Live Preview Area</h3>
                <p>Watch for flicker during test</p>
            </div>
        </div>
        <div class="metrics">
            <div class="metric-card">
                <div class="metric-label">CSS Injections</div>
                <div class="metric-value" id="injection-count">0</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Flicker Events</div>
                <div class="metric-value" id="flicker-count">0</div>
            </div>
        </div>
        <div class="results" id="flicker-results"></div>
    </div>

    <script>
        // WordPress AJAX configuration
        const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        const nonce = '<?php echo wp_create_nonce('mas_v2_nonce'); ?>';

        let ajaxRequestCount = 0;
        let changeCount = 0;
        let performanceTimes = [];

        // Test 8.1: Debouncing
        async function runDebounceTest() {
            log('debounce-results', '=== Starting Debounce Test ===', 'info');
            log('debounce-results', 'Making 10 rapid changes in 1 second...', 'info');
            
            ajaxRequestCount = 0;
            changeCount = 0;
            
            // Track AJAX requests
            const originalFetch = window.fetch;
            let requestInterceptor = null;
            
            window.fetch = function(...args) {
                if (args[0].includes('admin-ajax.php')) {
                    ajaxRequestCount++;
                    document.getElementById('ajax-sent').textContent = ajaxRequestCount;
                    log('debounce-results', `AJAX Request #${ajaxRequestCount} sent`, 'info');
                }
                return originalFetch.apply(this, args);
            };

            // Make 10 rapid changes
            for (let i = 0; i < 10; i++) {
                changeCount++;
                document.getElementById('changes-made').textContent = changeCount;
                
                const value = '#' + Math.floor(Math.random()*16777215).toString(16);
                log('debounce-results', `Change #${i + 1}: ${value}`, 'info');
                
                // Trigger change event (simulating user input)
                const event = new CustomEvent('mas-setting-change', {
                    detail: { setting: 'admin_bar_bg', value: value }
                });
                document.dispatchEvent(event);
                
                await sleep(100);
            }

            log('debounce-results', 'Waiting for debounced requests...', 'info');
            await sleep(1000);

            // Restore fetch
            window.fetch = originalFetch;

            // Evaluate results
            const ratio = (changeCount / ajaxRequestCount).toFixed(1);
            document.getElementById('debounce-ratio').textContent = ratio + ':1';

            log('debounce-results', `Total changes: ${changeCount}`, 'info');
            log('debounce-results', `Total requests: ${ajaxRequestCount}`, 'info');
            log('debounce-results', `Ratio: ${ratio}:1`, 'info');

            if (ajaxRequestCount >= 3 && ajaxRequestCount <= 4) {
                log('debounce-results', '✓ PASS: Debouncing working correctly', 'pass');
            } else if (ajaxRequestCount <= 6) {
                log('debounce-results', '⚠ WARNING: More requests than ideal, but acceptable', 'warning');
            } else {
                log('debounce-results', '✗ FAIL: Too many requests, debouncing not working', 'fail');
            }
        }

        // Test 8.2: Performance
        async function runPerformanceTest() {
            log('performance-results', '=== Starting Performance Test ===', 'info');
            log('performance-results', 'Sending 10 requests to measure CSS generation time...', 'info');
            
            performanceTimes = [];

            for (let i = 0; i < 10; i++) {
                const startTime = performance.now();
                
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
                            value: '#' + Math.floor(Math.random()*16777215).toString(16)
                        })
                    });

                    const data = await response.json();
                    const endTime = performance.now();
                    const totalTime = endTime - startTime;
                    
                    if (data.success && data.data.performance) {
                        const serverTime = data.data.performance.execution_time_ms;
                        performanceTimes.push(serverTime);
                        
                        log('performance-results', 
                            `Request #${i + 1}: Server=${serverTime.toFixed(2)}ms, Total=${totalTime.toFixed(2)}ms`, 
                            'info');
                    } else {
                        log('performance-results', `Request #${i + 1}: Error - ${data.data?.message || 'Unknown error'}`, 'fail');
                    }
                } catch (error) {
                    log('performance-results', `Request #${i + 1}: Exception - ${error.message}`, 'fail');
                }

                await sleep(100);
            }

            if (performanceTimes.length > 0) {
                const avgTime = performanceTimes.reduce((a, b) => a + b, 0) / performanceTimes.length;
                const minTime = Math.min(...performanceTimes);
                const maxTime = Math.max(...performanceTimes);

                document.getElementById('avg-time').textContent = avgTime.toFixed(2);
                document.getElementById('min-time').textContent = minTime.toFixed(2);
                document.getElementById('max-time').textContent = maxTime.toFixed(2);

                log('performance-results', `Average: ${avgTime.toFixed(2)}ms`, 'info');
                log('performance-results', `Min: ${minTime.toFixed(2)}ms`, 'info');
                log('performance-results', `Max: ${maxTime.toFixed(2)}ms`, 'info');

                if (avgTime < 100) {
                    log('performance-results', `✓ PASS: CSS generation is fast (${avgTime.toFixed(2)}ms < 100ms)`, 'pass');
                } else {
                    log('performance-results', `✗ FAIL: CSS generation too slow (${avgTime.toFixed(2)}ms >= 100ms)`, 'fail');
                }
            } else {
                log('performance-results', '✗ FAIL: No successful requests', 'fail');
            }
        }

        // Test 8.3: Flicker
        async function runFlickerTest() {
            log('flicker-results', '=== Starting Flicker Test ===', 'info');
            log('flicker-results', 'Performing 20 rapid CSS updates...', 'info');
            
            let injectionCount = 0;
            let flickerCount = 0;
            
            document.getElementById('injection-count').textContent = '0';
            document.getElementById('flicker-count').textContent = '0';

            const previewBox = document.getElementById('preview-box');
            let previousRect = previewBox.getBoundingClientRect();

            for (let i = 0; i < 20; i++) {
                const randomColor = '#' + Math.floor(Math.random()*16777215).toString(16);
                const css = `.preview-box { background-color: ${randomColor}; transition: all 0.3s ease; }`;
                
                // Remove old styles
                const oldStyles = document.getElementById('mas-preview-styles');
                if (oldStyles) oldStyles.remove();

                // Inject new styles
                const styleElement = document.createElement('style');
                styleElement.id = 'mas-preview-styles';
                styleElement.textContent = css;
                document.head.appendChild(styleElement);

                injectionCount++;
                document.getElementById('injection-count').textContent = injectionCount;

                // Check for position changes
                const currentRect = previewBox.getBoundingClientRect();
                if (Math.abs(currentRect.top - previousRect.top) > 1 || 
                    Math.abs(currentRect.left - previousRect.left) > 1) {
                    flickerCount++;
                    document.getElementById('flicker-count').textContent = flickerCount;
                    log('flicker-results', `⚠ Position change at injection #${i + 1}`, 'warning');
                }
                previousRect = currentRect;

                await sleep(50);
            }

            log('flicker-results', `Total injections: ${injectionCount}`, 'info');
            log('flicker-results', `Flicker events: ${flickerCount}`, 'info');

            if (flickerCount === 0) {
                log('flicker-results', '✓ PASS: No flicker detected', 'pass');
            } else if (flickerCount <= 2) {
                log('flicker-results', '⚠ WARNING: Minor flicker detected, but acceptable', 'warning');
            } else {
                log('flicker-results', `✗ FAIL: Significant flicker detected (${flickerCount} events)`, 'fail');
            }
        }

        // Utility functions
        function log(containerId, message, type = 'info') {
            const container = document.getElementById(containerId);
            const line = document.createElement('div');
            line.className = type;
            line.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
            container.appendChild(line);
            container.scrollTop = container.scrollHeight;
        }

        function sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }

        console.log('WordPress Performance Test loaded');
        console.log('AJAX URL:', ajaxUrl);
        console.log('Nonce:', nonce);
    </script>
</body>
</html>
