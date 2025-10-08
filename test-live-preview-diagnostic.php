<?php
/**
 * Live Preview System Diagnostic Test
 * 
 * Tests the complete live preview flow:
 * 1. JavaScript file loads
 * 2. AJAX handler responds
 * 3. CSS generation works
 * 4. Preview updates in browser
 */

// Load WordPress
require_once __DIR__ . '/wp-load.php';

// Must be admin
if (!current_user_can('manage_options')) {
    die('Must be admin to run this test');
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Live Preview Diagnostic Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f0f0f1;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .test-section h2 {
            margin-top: 0;
            color: #1d2327;
            border-bottom: 2px solid #2271b1;
            padding-bottom: 10px;
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 12px;
        }
        .status.pass { background: #00a32a; color: white; }
        .status.fail { background: #d63638; color: white; }
        .status.pending { background: #dba617; color: white; }
        .log {
            background: #f6f7f7;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            padding: 15px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 300px;
            overflow-y: auto;
        }
        .log-entry {
            margin: 5px 0;
            padding: 5px;
            border-left: 3px solid #2271b1;
            padding-left: 10px;
        }
        .log-entry.error { border-left-color: #d63638; color: #d63638; }
        .log-entry.success { border-left-color: #00a32a; color: #00a32a; }
        .log-entry.info { border-left-color: #2271b1; color: #2271b1; }
        .test-controls {
            margin: 20px 0;
        }
        button {
            background: #2271b1;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
        }
        button:hover {
            background: #135e96;
        }
        button:disabled {
            background: #c3c4c7;
            cursor: not-allowed;
        }
        .preview-demo {
            background: #2c3338;
            padding: 20px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .admin-bar-preview {
            background: #23282d;
            height: 32px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        input[type="color"] {
            width: 100px;
            height: 40px;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1>🔍 Live Preview System Diagnostic</h1>
    
    <!-- Test 1: Check if files exist -->
    <div class="test-section">
        <h2>Test 1: File Existence</h2>
        <div id="test1-results">
            <?php
            $files_to_check = [
                'simple-live-preview.js' => MAS_V2_PLUGIN_DIR . 'assets/js/simple-live-preview.js',
                'mas-settings-form-handler.js' => MAS_V2_PLUGIN_DIR . 'assets/js/mas-settings-form-handler.js',
                'modern-admin-styler-v2.php' => MAS_V2_PLUGIN_DIR . 'modern-admin-styler-v2.php'
            ];
            
            $all_exist = true;
            foreach ($files_to_check as $name => $path) {
                $exists = file_exists($path);
                $all_exist = $all_exist && $exists;
                $status = $exists ? 'pass' : 'fail';
                $icon = $exists ? '✅' : '❌';
                echo "<p>{$icon} <strong>{$name}:</strong> <span class='status {$status}'>" . ($exists ? 'EXISTS' : 'MISSING') . "</span></p>";
                if ($exists) {
                    $size = filesize($path);
                    echo "<p style='margin-left: 30px; color: #666;'>Size: " . number_format($size) . " bytes</p>";
                }
            }
            ?>
        </div>
    </div>
    
    <!-- Test 2: Check AJAX handler registration -->
    <div class="test-section">
        <h2>Test 2: AJAX Handler Registration</h2>
        <div id="test2-results">
            <?php
            global $wp_filter;
            $ajax_action = 'wp_ajax_mas_v2_get_preview_css';
            $is_registered = isset($wp_filter[$ajax_action]) && !empty($wp_filter[$ajax_action]);
            $status = $is_registered ? 'pass' : 'fail';
            $icon = $is_registered ? '✅' : '❌';
            
            echo "<p>{$icon} <strong>AJAX Handler:</strong> <span class='status {$status}'>" . ($is_registered ? 'REGISTERED' : 'NOT REGISTERED') . "</span></p>";
            
            if ($is_registered) {
                echo "<p style='margin-left: 30px; color: #666;'>Action: {$ajax_action}</p>";
                echo "<p style='margin-left: 30px; color: #666;'>Priority: " . $wp_filter[$ajax_action]->current_priority() . "</p>";
            }
            ?>
        </div>
    </div>
    
    <!-- Test 3: Check masV2Global localization -->
    <div class="test-section">
        <h2>Test 3: JavaScript Localization</h2>
        <div id="test3-results">
            <p><span class="status pending">PENDING</span> Waiting for page load...</p>
        </div>
    </div>
    
    <!-- Test 4: Test AJAX endpoint -->
    <div class="test-section">
        <h2>Test 4: AJAX Endpoint Test</h2>
        <div class="test-controls">
            <button id="test-ajax-btn">Run AJAX Test</button>
        </div>
        <div id="test4-results">
            <p><span class="status pending">PENDING</span> Click button to test</p>
        </div>
        <div id="ajax-log" class="log" style="display: none;"></div>
    </div>
    
    <!-- Test 5: Live preview demo -->
    <div class="test-section">
        <h2>Test 5: Live Preview Demo</h2>
        <p>Change the color below and watch the preview update in real-time:</p>
        <div class="test-controls">
            <label for="test-color">Admin Bar Color:</label>
            <input type="color" id="test-color" value="#23282d" class="mas-v2-color" name="mas_v2_settings[admin_bar_bg]">
        </div>
        <div class="preview-demo">
            <div class="admin-bar-preview" id="admin-bar-preview"></div>
        </div>
        <div id="test5-results">
            <p><span class="status pending">PENDING</span> Change color to test</p>
        </div>
        <div id="preview-log" class="log" style="display: none;"></div>
    </div>
    
    <!-- Load WordPress scripts -->
    <?php
    wp_enqueue_script('jquery');
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    
    // Enqueue the live preview script
    wp_enqueue_script(
        'mas-v2-simple-live-preview',
        MAS_V2_PLUGIN_URL . 'assets/js/simple-live-preview.js',
        ['jquery', 'wp-color-picker'],
        MAS_V2_VERSION,
        true
    );
    
    // Localize script
    wp_localize_script('mas-v2-simple-live-preview', 'masV2Global', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mas_v2_nonce'),
        'settings' => get_option('mas_v2_settings', []),
        'debug_mode' => true
    ]);
    
    wp_print_scripts();
    wp_print_styles();
    ?>
    
    <script>
    jQuery(document).ready(function($) {
        console.log('🔍 Live Preview Diagnostic Started');
        
        // Test 3: Check masV2Global
        function test3() {
            const results = $('#test3-results');
            results.empty();
            
            if (typeof masV2Global === 'undefined') {
                results.html('<p>❌ <strong>masV2Global:</strong> <span class="status fail">NOT DEFINED</span></p>');
                return false;
            }
            
            results.html('<p>✅ <strong>masV2Global:</strong> <span class="status pass">DEFINED</span></p>');
            
            const checks = [
                { key: 'ajaxUrl', value: masV2Global.ajaxUrl },
                { key: 'nonce', value: masV2Global.nonce },
                { key: 'settings', value: masV2Global.settings }
            ];
            
            checks.forEach(check => {
                const exists = typeof check.value !== 'undefined';
                const icon = exists ? '✅' : '❌';
                const status = exists ? 'pass' : 'fail';
                results.append(`<p style="margin-left: 30px;">${icon} ${check.key}: <span class="status ${status}">${exists ? 'OK' : 'MISSING'}</span></p>`);
            });
            
            return true;
        }
        
        // Test 4: AJAX endpoint
        $('#test-ajax-btn').on('click', function() {
            const btn = $(this);
            const results = $('#test4-results');
            const log = $('#ajax-log');
            
            btn.prop('disabled', true);
            results.html('<p><span class="status pending">TESTING...</span></p>');
            log.show().html('');
            
            function addLog(message, type = 'info') {
                const entry = $('<div class="log-entry ' + type + '"></div>').text(message);
                log.append(entry);
                log.scrollTop(log[0].scrollHeight);
            }
            
            addLog('📤 Sending AJAX request...', 'info');
            addLog('URL: ' + masV2Global.ajaxUrl, 'info');
            addLog('Action: mas_v2_get_preview_css', 'info');
            
            $.post(masV2Global.ajaxUrl, {
                action: 'mas_v2_get_preview_css',
                nonce: masV2Global.nonce,
                setting: 'admin_bar_bg',
                value: '#2271b1'
            })
            .done(function(response) {
                addLog('✅ AJAX request successful', 'success');
                addLog('Response: ' + JSON.stringify(response, null, 2), 'info');
                
                if (response.success) {
                    results.html('<p>✅ <strong>AJAX Endpoint:</strong> <span class="status pass">WORKING</span></p>');
                    
                    if (response.data && response.data.css) {
                        results.append('<p style="margin-left: 30px;">CSS Length: ' + response.data.css.length + ' characters</p>');
                    }
                    
                    if (response.data && response.data.performance) {
                        results.append('<p style="margin-left: 30px;">Execution Time: ' + response.data.performance.execution_time_ms + 'ms</p>');
                    }
                } else {
                    results.html('<p>❌ <strong>AJAX Endpoint:</strong> <span class="status fail">ERROR</span></p>');
                    results.append('<p style="margin-left: 30px; color: #d63638;">' + (response.data ? response.data.message : 'Unknown error') + '</p>');
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                addLog('❌ AJAX request failed', 'error');
                addLog('Status: ' + textStatus, 'error');
                addLog('Error: ' + errorThrown, 'error');
                
                results.html('<p>❌ <strong>AJAX Endpoint:</strong> <span class="status fail">FAILED</span></p>');
                results.append('<p style="margin-left: 30px; color: #d63638;">' + textStatus + ': ' + errorThrown + '</p>');
            })
            .always(function() {
                btn.prop('disabled', false);
            });
        });
        
        // Test 5: Live preview
        let previewTestCount = 0;
        $('#test-color').on('change', function() {
            previewTestCount++;
            const color = $(this).val();
            const results = $('#test5-results');
            const log = $('#preview-log');
            const preview = $('#admin-bar-preview');
            
            log.show();
            
            function addLog(message, type = 'info') {
                const entry = $('<div class="log-entry ' + type + '"></div>').text(message);
                log.append(entry);
                log.scrollTop(log[0].scrollHeight);
            }
            
            addLog('🎨 Color changed to: ' + color, 'info');
            addLog('Test #' + previewTestCount, 'info');
            
            // Update preview immediately
            preview.css('background-color', color);
            
            results.html('<p><span class="status pending">TESTING...</span> Waiting for live preview update...</p>');
            
            // Check if CSS was injected after a delay
            setTimeout(function() {
                const styleEl = $('#mas-preview-styles');
                if (styleEl.length > 0) {
                    addLog('✅ Preview styles injected', 'success');
                    addLog('CSS length: ' + styleEl.text().length + ' characters', 'info');
                    results.html('<p>✅ <strong>Live Preview:</strong> <span class="status pass">WORKING</span></p>');
                    results.append('<p style="margin-left: 30px;">Tests passed: ' + previewTestCount + '</p>');
                } else {
                    addLog('❌ Preview styles not found', 'error');
                    results.html('<p>❌ <strong>Live Preview:</strong> <span class="status fail">NOT WORKING</span></p>');
                    results.append('<p style="margin-left: 30px; color: #d63638;">CSS not injected into page</p>');
                }
            }, 1000);
        });
        
        // Initialize color picker
        if ($.fn.wpColorPicker) {
            $('#test-color').wpColorPicker({
                change: function(event, ui) {
                    $(this).trigger('change');
                }
            });
        }
        
        // Run Test 3 on load
        setTimeout(test3, 500);
        
        console.log('✅ Diagnostic tests initialized');
    });
    </script>
</body>
</html>
