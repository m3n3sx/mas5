<?php
/**
 * Task 8: Simple Live Preview Verification Test
 * Tests live preview functionality without Phase 3 dependencies
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Mock WordPress functions for testing
if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false) {
        echo "<!-- Enqueued: $handle -->\n";
    }
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $object_name, $l10n) {
        echo "<script>var $object_name = " . json_encode($l10n) . ";</script>\n";
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null) {
        header('Content-Type: application/json');
        echo json_encode(array('success' => true, 'data' => $data));
        exit;
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null) {
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'data' => $data));
        exit;
    }
}

// Mock AJAX handler for preview CSS
function mas_v2_get_preview_css_handler() {
    // Verify nonce (simplified for testing)
    if (!isset($_POST['nonce']) || $_POST['nonce'] !== 'test_nonce_123') {
        wp_send_json_error('Invalid nonce');
        return;
    }
    
    $setting = sanitize_text_field($_POST['setting'] ?? '');
    $value = sanitize_text_field($_POST['value'] ?? '');
    
    if (empty($setting)) {
        wp_send_json_error('Setting name is required');
        return;
    }
    
    // Generate mock CSS based on setting
    $css = '';
    switch ($setting) {
        case 'primary_color':
            $css = ".wp-admin { --primary-color: $value; }";
            break;
        case 'font_size':
            $css = ".wp-admin { font-size: {$value}px; }";
            break;
        case 'enable_animations':
            $css = $value ? ".wp-admin { transition: all 0.3s ease; }" : ".wp-admin { transition: none; }";
            break;
        default:
            $css = "/* Setting: $setting = $value */";
    }
    
    wp_send_json_success(array(
        'css' => $css,
        'setting' => $setting,
        'value' => $value,
        'performance' => array(
            'execution_time_ms' => rand(10, 50)
        )
    ));
}

// Handle AJAX request
if (isset($_POST['action']) && $_POST['action'] === 'mas_v2_get_preview_css') {
    mas_v2_get_preview_css_handler();
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task 8: Simple Live Preview Verification</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f1f1f1;
        }
        
        .test-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .test-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .preview-area {
            background: #f9f9f9;
            border: 2px dashed #ddd;
            padding: 20px;
            border-radius: 8px;
            min-height: 100px;
        }
        
        .console-output {
            background: #1e1e1e;
            color: #00ff00;
            padding: 15px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
            margin-top: 20px;
        }
        
        .test-status {
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        
        .test-pass { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .test-fail { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .test-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        
        .error-recovery-test {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
        }
        
        button {
            background: #0073aa;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        
        button:hover {
            background: #005a87;
        }
        
        button.danger {
            background: #dc3545;
        }
        
        button.danger:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>Task 8: Simple Live Preview System Verification</h1>
        <p>Testing live preview functionality without Phase 3 dependencies</p>
        
        <div id="test-results"></div>
        
        <div class="test-form">
            <div>
                <h3>Form Controls</h3>
                
                <div class="form-group">
                    <label for="primary_color">Primary Color</label>
                    <input type="color" id="primary_color" name="mas_v2_settings[primary_color]" 
                           class="mas-v2-color" value="#0073aa">
                </div>
                
                <div class="form-group">
                    <label for="font_size">Font Size</label>
                    <input type="number" id="font_size" name="mas_v2_settings[font_size]" 
                           class="mas-v2-input" value="14" min="10" max="24">
                </div>
                
                <div class="form-group">
                    <label for="enable_animations">Enable Animations</label>
                    <input type="checkbox" id="enable_animations" name="mas_v2_settings[enable_animations]" 
                           class="mas-v2-checkbox" checked>
                </div>
                
                <div class="form-group">
                    <label for="theme_style">Theme Style</label>
                    <select id="theme_style" name="mas_v2_settings[theme_style]" class="mas-v2-select">
                        <option value="modern">Modern</option>
                        <option value="classic">Classic</option>
                        <option value="minimal">Minimal</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="opacity">Opacity</label>
                    <input type="range" id="opacity" name="mas_v2_settings[opacity]" 
                           class="mas-v2-slider" min="0" max="100" value="80">
                    <span id="opacity-value">80</span>
                </div>
            </div>
            
            <div>
                <h3>Preview Area</h3>
                <div class="preview-area" id="preview-area">
                    <p>Live preview changes will be applied here</p>
                    <p>Current styles will be injected into the page head</p>
                </div>
                
                <h3>Error Recovery Tests</h3>
                <div class="error-recovery-test">
                    <button onclick="testNetworkError()">Test Network Error</button>
                    <button onclick="testInvalidResponse()">Test Invalid Response</button>
                    <button onclick="testMissingNonce()">Test Missing Nonce</button>
                    <button class="danger" onclick="testCriticalFailure()">Test Critical Failure</button>
                </div>
            </div>
        </div>
        
        <div class="console-output" id="console-output">
            <div>Console output will appear here...</div>
        </div>
    </div>

    <!-- Load jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- WordPress Color Picker (simplified mock) -->
    <script>
        // Mock WordPress Color Picker
        if (typeof jQuery !== 'undefined') {
            jQuery.fn.wpColorPicker = function(options) {
                return this.each(function() {
                    var $input = jQuery(this);
                    $input.on('change', function() {
                        if (options && options.change) {
                            options.change.call(this, null, {
                                color: {
                                    toString: function() {
                                        return $input.val();
                                    }
                                }
                            });
                        }
                    });
                });
            };
        }
    </script>
    
    <!-- Localize script data -->
    <?php
    wp_localize_script('mas-simple-live-preview', 'masV2Global', array(
        'ajaxUrl' => $_SERVER['PHP_SELF'],
        'nonce' => 'test_nonce_123',
        'settings' => array(
            'primary_color' => '#0073aa',
            'font_size' => '14',
            'enable_animations' => true,
            'theme_style' => 'modern',
            'opacity' => '80'
        )
    ));
    ?>
    
    <!-- Load Simple Live Preview -->
    <script src="assets/js/simple-live-preview.js"></script>
    
    <script>
        // Console output capture
        var originalLog = console.log;
        var originalError = console.error;
        var consoleOutput = document.getElementById('console-output');
        
        function addToConsole(message, type = 'log') {
            var div = document.createElement('div');
            div.style.color = type === 'error' ? '#ff6b6b' : '#00ff00';
            div.textContent = new Date().toLocaleTimeString() + ' - ' + message;
            consoleOutput.appendChild(div);
            consoleOutput.scrollTop = consoleOutput.scrollHeight;
        }
        
        console.log = function(...args) {
            originalLog.apply(console, args);
            addToConsole(args.join(' '), 'log');
        };
        
        console.error = function(...args) {
            originalError.apply(console, args);
            addToConsole(args.join(' '), 'error');
        };
        
        // Test functions
        function runDiagnosticTests() {
            var results = document.getElementById('test-results');
            results.innerHTML = '<h3>Running Diagnostic Tests...</h3>';
            
            var tests = [
                {
                    name: 'jQuery Loaded',
                    test: () => typeof jQuery !== 'undefined',
                    expected: true
                },
                {
                    name: 'masV2Global Defined',
                    test: () => typeof masV2Global !== 'undefined',
                    expected: true
                },
                {
                    name: 'AJAX URL Present',
                    test: () => masV2Global && masV2Global.ajaxUrl,
                    expected: true
                },
                {
                    name: 'Nonce Present',
                    test: () => masV2Global && masV2Global.nonce,
                    expected: true
                },
                {
                    name: 'Form Elements Found',
                    test: () => jQuery('.mas-v2-color, .mas-v2-input, .mas-v2-checkbox, .mas-v2-select, .mas-v2-slider').length > 0,
                    expected: true
                },
                {
                    name: 'MASPreviewDiagnostics Available',
                    test: () => typeof window.MASPreviewDiagnostics !== 'undefined' || 
                               jQuery(document).data('masPreviewLoaded') === true,
                    expected: true
                }
            ];
            
            var passed = 0;
            var total = tests.length;
            
            tests.forEach(test => {
                var result = test.test();
                var status = result === test.expected ? 'PASS' : 'FAIL';
                var className = result === test.expected ? 'test-pass' : 'test-fail';
                
                if (result === test.expected) passed++;
                
                results.innerHTML += `<div class="test-status ${className}">
                    ${status}: ${test.name} (Expected: ${test.expected}, Got: ${result})
                </div>`;
            });
            
            results.innerHTML += `<div class="test-status ${passed === total ? 'test-pass' : 'test-fail'}">
                Overall: ${passed}/${total} tests passed
            </div>`;
        }
        
        // Error recovery test functions
        function testNetworkError() {
            console.log('Testing network error recovery...');
            
            // Temporarily break the AJAX URL
            var originalUrl = masV2Global.ajaxUrl;
            masV2Global.ajaxUrl = 'http://invalid-url-that-will-fail.test';
            
            // Trigger a preview update
            jQuery('#primary_color').val('#ff0000').trigger('change');
            
            // Restore URL after 2 seconds
            setTimeout(() => {
                masV2Global.ajaxUrl = originalUrl;
                console.log('AJAX URL restored');
            }, 2000);
        }
        
        function testInvalidResponse() {
            console.log('Testing invalid response handling...');
            
            // This would require server-side modification to return invalid response
            // For now, we'll simulate by triggering an update with invalid data
            jQuery.post(masV2Global.ajaxUrl, {
                action: 'mas_v2_get_preview_css',
                nonce: 'invalid_nonce',
                setting: 'test_setting',
                value: 'test_value'
            }).fail(function(jqXHR, textStatus, errorThrown) {
                console.log('Expected failure for invalid nonce test');
            });
        }
        
        function testMissingNonce() {
            console.log('Testing missing nonce handling...');
            
            var originalNonce = masV2Global.nonce;
            masV2Global.nonce = '';
            
            // Trigger update
            jQuery('#font_size').val('16').trigger('input');
            
            // Restore nonce
            setTimeout(() => {
                masV2Global.nonce = originalNonce;
                console.log('Nonce restored');
            }, 1000);
        }
        
        function testCriticalFailure() {
            console.log('Testing critical failure recovery...');
            
            // Temporarily remove masV2Global
            var backup = window.masV2Global;
            window.masV2Global = undefined;
            
            // Try to trigger update
            jQuery('#theme_style').val('classic').trigger('change');
            
            // Restore after 2 seconds
            setTimeout(() => {
                window.masV2Global = backup;
                console.log('masV2Global restored');
            }, 2000);
        }
        
        // Update opacity display
        jQuery('#opacity').on('input', function() {
            jQuery('#opacity-value').text(jQuery(this).val());
        });
        
        // Run tests when page loads
        jQuery(document).ready(function() {
            setTimeout(runDiagnosticTests, 1000);
        });
    </script>
</body>
</html>