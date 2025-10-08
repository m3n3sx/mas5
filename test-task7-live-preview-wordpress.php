<?php
/**
 * Task 7: Live Preview All Settings Test - WordPress Environment
 * 
 * This file tests the live preview functionality with all setting types
 * in the actual WordPress environment.
 * 
 * Usage: Place in WordPress root and access via browser
 */

// Load WordPress
require_once('wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task 7: Live Preview Test - WordPress Environment</title>
    <?php wp_head(); ?>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f0f1;
            padding: 20px;
        }
        
        .test-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #1d2327;
            border-bottom: 3px solid #2271b1;
            padding-bottom: 10px;
        }
        
        h2 {
            color: #2271b1;
            margin-top: 30px;
            border-left: 4px solid #2271b1;
            padding-left: 15px;
        }
        
        .test-section {
            background: #f6f7f7;
            padding: 20px;
            border-radius: 4px;
            margin: 20px 0;
        }
        
        .test-control {
            margin: 15px 0;
            padding: 15px;
            background: white;
            border-radius: 4px;
            border-left: 3px solid #2271b1;
        }
        
        .test-control label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .test-control input[type="color"] {
            width: 100px;
            height: 40px;
            border: 1px solid #8c8f94;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .test-control input[type="range"] {
            width: 100%;
            margin: 10px 0;
        }
        
        .test-control input[type="checkbox"] {
            margin-right: 8px;
        }
        
        .value-display {
            display: inline-block;
            background: #2271b1;
            color: white;
            padding: 4px 12px;
            border-radius: 3px;
            font-size: 14px;
            margin-left: 10px;
            font-weight: bold;
        }
        
        .instructions {
            background: #e7f5fe;
            border-left: 4px solid #2271b1;
            padding: 15px;
            margin: 20px 0;
        }
        
        .instructions h3 {
            margin-top: 0;
            color: #2271b1;
        }
        
        .console-monitor {
            background: #1d2327;
            color: #00ff00;
            padding: 20px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 400px;
            overflow-y: auto;
            margin-top: 20px;
        }
        
        .console-log {
            margin: 3px 0;
            padding: 2px 0;
        }
        
        .console-log.error {
            color: #ff4444;
        }
        
        .console-log.success {
            color: #00ffff;
        }
        
        .test-results {
            background: #f6f7f7;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
        }
        
        .test-result-item {
            padding: 8px;
            margin: 5px 0;
            background: white;
            border-radius: 3px;
        }
        
        .status-pass {
            color: #00a32a;
            font-weight: bold;
        }
        
        .status-fail {
            color: #d63638;
            font-weight: bold;
        }
        
        .status-pending {
            color: #f0b849;
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
        
        .network-monitor {
            background: #f6f7f7;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
        }
        
        .network-request {
            background: white;
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
            font-size: 12px;
            border-left: 3px solid #2271b1;
        }
    </style>
</head>
<body>
    <div class="test-wrapper">
        <h1>🧪 Task 7: Live Preview - All Setting Types Test</h1>
        
        <div class="instructions">
            <h3>📋 Test Instructions</h3>
            <ol>
                <li><strong>Open Browser Console (F12)</strong> - All diagnostic logs will appear there</li>
                <li><strong>Test Each Setting Type</strong> - Change colors, sliders, and checkboxes below</li>
                <li><strong>Verify Console Logs</strong> - Look for [MAS Preview] messages</li>
                <li><strong>Check AJAX Requests</strong> - Network tab should show requests to admin-ajax.php</li>
                <li><strong>Verify CSS Injection</strong> - Inspect &lt;head&gt; for #mas-preview-styles element</li>
                <li><strong>Test Rapid Changes</strong> - Verify debouncing works (max 1 request per 300ms)</li>
            </ol>
        </div>

        <!-- TASK 7.1: COLOR SETTINGS -->
        <h2>🎨 Task 7.1: Color Settings Test</h2>
        <div class="test-section">
            <p><strong>Test:</strong> Change these colors and verify preview updates immediately</p>
            
            <div class="test-control">
                <label>
                    Admin Bar Background Color
                    <span class="value-display" id="display-admin-bar-bg">#23282d</span>
                </label>
                <input type="color" 
                       name="mas_v2_settings[admin_bar_background]" 
                       class="mas-v2-color"
                       value="#23282d"
                       id="admin-bar-bg">
            </div>
            
            <div class="test-control">
                <label>
                    Menu Background Color
                    <span class="value-display" id="display-menu-bg">#23282d</span>
                </label>
                <input type="color" 
                       name="mas_v2_settings[menu_background_color]" 
                       class="mas-v2-color"
                       value="#23282d"
                       id="menu-bg">
            </div>
            
            <div class="test-control">
                <label>
                    Admin Bar Text Color
                    <span class="value-display" id="display-admin-bar-text">#ffffff</span>
                </label>
                <input type="color" 
                       name="mas_v2_settings[admin_bar_text_color]" 
                       class="mas-v2-color"
                       value="#ffffff"
                       id="admin-bar-text">
            </div>
            
            <div class="test-results">
                <h4>✅ Expected Console Output:</h4>
                <div class="test-result-item">
                    <span class="status-pending">⏳</span> [MAS Preview] Color changed: admin_bar_background = #...
                </div>
                <div class="test-result-item">
                    <span class="status-pending">⏳</span> [MAS Preview] Sending AJAX request
                </div>
                <div class="test-result-item">
                    <span class="status-pending">⏳</span> [MAS Preview] AJAX response received
                </div>
                <div class="test-result-item">
                    <span class="status-pending">⏳</span> [MAS Preview] ✓ CSS injected successfully
                </div>
            </div>
        </div>

        <!-- TASK 7.2: SIZE SETTINGS -->
        <h2>📏 Task 7.2: Size Settings Test</h2>
        <div class="test-section">
            <p><strong>Test:</strong> Move these sliders and verify preview updates immediately</p>
            
            <div class="test-control">
                <label>
                    Menu Width
                    <span class="value-display" id="display-menu-width">200</span> px
                </label>
                <input type="range" 
                       name="mas_v2_settings[menu_width]" 
                       class="mas-v2-slider"
                       min="200" 
                       max="400" 
                       value="200"
                       id="menu-width">
            </div>
            
            <div class="test-control">
                <label>
                    Admin Bar Height
                    <span class="value-display" id="display-admin-bar-height">32</span> px
                </label>
                <input type="range" 
                       name="mas_v2_settings[admin_bar_height]" 
                       class="mas-v2-slider"
                       min="20" 
                       max="60" 
                       value="32"
                       id="admin-bar-height">
            </div>
            
            <div class="test-control">
                <label>
                    Menu Item Padding
                    <span class="value-display" id="display-menu-padding">10</span> px
                </label>
                <input type="range" 
                       name="mas_v2_settings[menu_item_padding]" 
                       class="mas-v2-slider"
                       min="5" 
                       max="20" 
                       value="10"
                       id="menu-padding">
            </div>
            
            <div class="test-results">
                <h4>✅ Expected Console Output:</h4>
                <div class="test-result-item">
                    <span class="status-pending">⏳</span> [MAS Preview] Slider changed: menu_width = 250
                </div>
                <div class="test-result-item">
                    <span class="status-pending">⏳</span> [MAS Preview] Sending AJAX request
                </div>
                <div class="test-result-item">
                    <span class="status-pending">⏳</span> [MAS Preview] AJAX response received
                </div>
                <div class="test-result-item">
                    <span class="status-pending">⏳</span> [MAS Preview] ✓ CSS injected successfully
                </div>
            </div>
        </div>

        <!-- TASK 7.3: BOOLEAN SETTINGS -->
        <h2>✅ Task 7.3: Boolean Settings Test</h2>
        <div class="test-section">
            <p><strong>Test:</strong> Toggle these checkboxes and verify preview updates immediately</p>
            
            <div class="test-control">
                <label>
                    <input type="checkbox" 
                           name="mas_v2_settings[animations]" 
                           class="mas-v2-checkbox"
                           id="animations">
                    Enable Animations
                </label>
            </div>
            
            <div class="test-control">
                <label>
                    <input type="checkbox" 
                           name="mas_v2_settings[menu_show_icons]" 
                           class="mas-v2-checkbox"
                           id="menu-show-icons"
                           checked>
                    Show Menu Icons
                </label>
            </div>
            
            <div class="test-control">
                <label>
                    <input type="checkbox" 
                           name="mas_v2_settings[admin_bar_detached]" 
                           class="mas-v2-checkbox"
                           id="admin-bar-detached">
                    Floating Admin Bar
                </label>
            </div>
            
            <div class="test-results">
                <h4>✅ Expected Console Output:</h4>
                <div class="test-result-item">
                    <span class="status-pending">⏳</span> [MAS Preview] Checkbox changed: animations = true
                </div>
                <div class="test-result-item">
                    <span class="status-pending">⏳</span> [MAS Preview] Sending AJAX request
                </div>
                <div class="test-result-item">
                    <span class="status-pending">⏳</span> [MAS Preview] AJAX response received
                </div>
                <div class="test-result-item">
                    <span class="status-pending">⏳</span> [MAS Preview] ✓ CSS injected successfully
                </div>
            </div>
        </div>

        <!-- TASK 7.4: RAPID CHANGES TEST -->
        <h2>⚡ Task 7.4: Rapid Changes Test (Debouncing)</h2>
        <div class="test-section">
            <p><strong>Test:</strong> Click button to rapidly change setting 10 times. Verify only 3-4 AJAX requests sent.</p>
            
            <div class="test-control">
                <label>
                    Test Slider (for rapid changes)
                    <span class="value-display" id="display-rapid-test">50</span>
                </label>
                <input type="range" 
                       name="mas_v2_settings[test_rapid_slider]" 
                       class="mas-v2-slider"
                       min="0" 
                       max="100" 
                       value="50"
                       id="rapid-test-slider">
            </div>
            
            <button onclick="runRapidChangeTest()">🚀 Run Rapid Change Test</button>
            <button onclick="clearNetworkMonitor()">🗑️ Clear Monitor</button>
            
            <div class="network-monitor">
                <h4>📡 Network Monitor</h4>
                <p>Watch browser Network tab - should see only 3-4 requests instead of 10</p>
                <div id="network-log"></div>
            </div>
            
            <div class="test-results">
                <h4>✅ Expected Behavior:</h4>
                <div class="test-result-item">
                    <span class="status-pending">⏳</span> 10 rapid changes triggered
                </div>
                <div class="test-result-item">
                    <span class="status-pending">⏳</span> Only 3-4 AJAX requests sent (debounced)
                </div>
                <div class="test-result-item">
                    <span class="status-pending">⏳</span> Final value is correct (100)
                </div>
            </div>
        </div>

        <!-- CONSOLE MONITOR -->
        <h2>📊 Console Output Monitor</h2>
        <div class="console-monitor" id="console-monitor">
            <div class="console-log">[Monitor] Test page loaded. Open browser console (F12) for full output.</div>
            <div class="console-log">[Monitor] Make changes to settings above to test live preview.</div>
        </div>
    </div>

    <script>
        // Update value displays
        function setupValueDisplay(inputId, displayId) {
            const input = document.getElementById(inputId);
            const display = document.getElementById(displayId);
            if (input && display) {
                input.addEventListener('input', function() {
                    display.textContent = this.value;
                });
                input.addEventListener('change', function() {
                    display.textContent = this.value;
                });
            }
        }

        // Initialize all value displays
        setupValueDisplay('admin-bar-bg', 'display-admin-bar-bg');
        setupValueDisplay('menu-bg', 'display-menu-bg');
        setupValueDisplay('admin-bar-text', 'display-admin-bar-text');
        setupValueDisplay('menu-width', 'display-menu-width');
        setupValueDisplay('admin-bar-height', 'display-admin-bar-height');
        setupValueDisplay('menu-padding', 'display-menu-padding');
        setupValueDisplay('rapid-test-slider', 'display-rapid-test');

        // Console monitor
        function addToMonitor(message, type = 'log') {
            const monitor = document.getElementById('console-monitor');
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = document.createElement('div');
            logEntry.className = 'console-log ' + type;
            logEntry.textContent = `[${timestamp}] ${message}`;
            monitor.appendChild(logEntry);
            monitor.scrollTop = monitor.scrollHeight;
        }

        // Network monitor
        let requestCount = 0;
        function addNetworkRequest(setting, value) {
            requestCount++;
            const networkLog = document.getElementById('network-log');
            const timestamp = new Date().toLocaleTimeString();
            const request = document.createElement('div');
            request.className = 'network-request';
            request.innerHTML = `
                <strong>Request #${requestCount}</strong> - ${timestamp}<br>
                Setting: ${setting}<br>
                Value: ${value}
            `;
            networkLog.appendChild(request);
        }

        function clearNetworkMonitor() {
            document.getElementById('network-log').innerHTML = '';
            requestCount = 0;
            addToMonitor('Network monitor cleared', 'log');
        }

        // Intercept console to show in monitor
        const originalLog = console.log;
        const originalError = console.error;

        console.log = function(...args) {
            originalLog.apply(console, args);
            const message = args.map(arg => 
                typeof arg === 'object' ? JSON.stringify(arg) : String(arg)
            ).join(' ');
            if (message.includes('[MAS Preview]')) {
                addToMonitor(message, 'log');
            }
        };

        console.error = function(...args) {
            originalError.apply(console, args);
            const message = args.map(arg => 
                typeof arg === 'object' ? JSON.stringify(arg) : String(arg)
            ).join(' ');
            if (message.includes('[MAS Preview')) {
                addToMonitor(message, 'error');
            }
        };

        // Monitor AJAX requests
        if (typeof jQuery !== 'undefined') {
            const originalPost = jQuery.post;
            jQuery.post = function(url, data) {
                if (data && data.action === 'mas_v2_get_preview_css') {
                    addNetworkRequest(data.setting, data.value);
                }
                return originalPost.apply(this, arguments);
            };
        }

        // Rapid change test
        function runRapidChangeTest() {
            addToMonitor('🚀 Starting rapid change test...', 'log');
            clearNetworkMonitor();
            
            const slider = document.getElementById('rapid-test-slider');
            let value = 0;
            
            const interval = setInterval(() => {
                value += 10;
                slider.value = value;
                slider.dispatchEvent(new Event('input', { bubbles: true }));
                
                if (value >= 100) {
                    clearInterval(interval);
                    setTimeout(() => {
                        addToMonitor(`✅ Test complete. Check Network tab - should see only 3-4 requests (debounced)`, 'success');
                    }, 1000);
                }
            }, 100);
        }

        // Initial message
        addToMonitor('✅ Test page ready. Make changes to test live preview.', 'log');
    </script>
</body>
</html>
<?php wp_footer(); ?>
