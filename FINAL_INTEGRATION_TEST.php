<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAS V2 - Final Integration Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .header h1 {
            font-size: 36px;
            margin-bottom: 10px;
            font-weight: 700;
        }
        .header p {
            font-size: 18px;
            opacity: 0.9;
        }
        .content {
            padding: 40px;
        }
        .test-section {
            margin-bottom: 30px;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 5px solid #667eea;
        }
        .test-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .test-item {
            padding: 15px;
            margin: 10px 0;
            background: white;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }
        .test-item:hover {
            border-color: #667eea;
            transform: translateX(5px);
        }
        .test-name {
            font-weight: 600;
            color: #495057;
            flex: 1;
        }
        .test-result {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 2px solid #ffeaa7;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 2px solid #bee5eb;
        }
        .icon {
            font-size: 20px;
        }
        .summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-top: 30px;
            text-align: center;
        }
        .summary h2 {
            font-size: 28px;
            margin-bottom: 20px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .stat-box {
            background: rgba(255,255,255,0.2);
            padding: 20px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        .stat-number {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 15px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 300px;
            overflow-y: auto;
        }
        .details pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e9ecef;
            border-radius: 15px;
            overflow: hidden;
            margin: 20px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 MAS V2 - Final Integration Test</h1>
            <p>Comprehensive Plugin Validation & Quality Assurance</p>
        </div>
        
        <div class="content">
            <?php
            // Load WordPress
            require_once('../../../wp-load.php');
            
            if (!current_user_can('manage_options')) {
                die('<div class="test-section"><p class="error">❌ Brak uprawnień administratora!</p></div>');
            }
            
            // Initialize test results
            $tests = [];
            $total_tests = 0;
            $passed_tests = 0;
            $failed_tests = 0;
            $warning_tests = 0;
            
            // Helper function to add test result
            function addTest($name, $status, $message = '', $details = '') {
                global $tests, $total_tests, $passed_tests, $failed_tests, $warning_tests;
                
                $tests[] = [
                    'name' => $name,
                    'status' => $status,
                    'message' => $message,
                    'details' => $details
                ];
                
                $total_tests++;
                if ($status === 'success') $passed_tests++;
                elseif ($status === 'error') $failed_tests++;
                elseif ($status === 'warning') $warning_tests++;
            }
            
            // ============================================
            // TEST 1: Plugin Installation & Activation
            // ============================================
            echo '<div class="test-section">';
            echo '<h2><span class="icon">📦</span> Test 1: Plugin Installation & Activation</h2>';
            
            // Check if plugin class exists
            if (class_exists('ModernAdminStylerV2')) {
                addTest('Plugin Class Exists', 'success', 'ModernAdminStylerV2 class loaded');
            } else {
                addTest('Plugin Class Exists', 'error', 'ModernAdminStylerV2 class not found');
            }
            
            // Check if plugin is active
            if (is_plugin_active('mas3/modern-admin-styler-v2.php') || class_exists('ModernAdminStylerV2')) {
                addTest('Plugin Active', 'success', 'Plugin is active and running');
            } else {
                addTest('Plugin Active', 'error', 'Plugin is not active');
            }
            
            // Check plugin version
            if (defined('MAS_V2_VERSION')) {
                addTest('Plugin Version', 'success', 'Version: ' . MAS_V2_VERSION);
            } else {
                addTest('Plugin Version', 'warning', 'Version constant not defined');
            }
            
            echo '</div>';
            
            // ============================================
            // TEST 2: Database & Settings
            // ============================================
            echo '<div class="test-section">';
            echo '<h2><span class="icon">💾</span> Test 2: Database & Settings</h2>';
            
            $settings = get_option('mas_v2_settings', []);
            $settings_count = count($settings);
            
            if ($settings_count > 0) {
                addTest('Settings Exist', 'success', "{$settings_count} settings found in database");
            } else {
                addTest('Settings Exist', 'error', 'No settings found in database');
            }
            
            // Check menu settings
            $menu_settings = array_filter($settings, function($key) {
                return strpos($key, 'menu_') === 0;
            }, ARRAY_FILTER_USE_KEY);
            
            if (count($menu_settings) > 0) {
                addTest('Menu Settings', 'success', count($menu_settings) . ' menu settings found');
            } else {
                addTest('Menu Settings', 'warning', 'No menu settings found');
            }
            
            // Check if plugin is enabled
            $plugin_enabled = isset($settings['enable_plugin']) ? $settings['enable_plugin'] : false;
            if ($plugin_enabled) {
                addTest('Plugin Enabled', 'success', 'Plugin is enabled in settings');
            } else {
                addTest('Plugin Enabled', 'warning', 'Plugin is disabled in settings');
            }
            
            // Check for backups
            global $wpdb;
            $backup_count = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE 'mas_v2_settings_backup_%'"
            );
            
            if ($backup_count > 0) {
                addTest('Settings Backups', 'success', "{$backup_count} backups found");
            } else {
                addTest('Settings Backups', 'info', 'No backups found (normal for fresh install)');
            }
            
            echo '</div>';
            
            // ============================================
            // TEST 3: CSS Generation
            // ============================================
            echo '<div class="test-section">';
            echo '<h2><span class="icon">🎨</span> Test 3: CSS Generation</h2>';
            
            if (class_exists('ModernAdminStylerV2')) {
                $plugin = ModernAdminStylerV2::getInstance();
                
                // Test CSS Variables generation
                try {
                    $reflection = new ReflectionClass($plugin);
                    $method = $reflection->getMethod('generateCSSVariables');
                    $method->setAccessible(true);
                    $css_vars = $method->invoke($plugin, $settings);
                    
                    if (!empty($css_vars) && strlen($css_vars) > 20) {
                        addTest('CSS Variables Generation', 'success', strlen($css_vars) . ' characters generated');
                    } else {
                        addTest('CSS Variables Generation', 'error', 'CSS Variables empty or too short');
                    }
                } catch (Exception $e) {
                    addTest('CSS Variables Generation', 'error', $e->getMessage());
                }
                
                // Test Menu CSS generation
                try {
                    $method = $reflection->getMethod('generateMenuCSS');
                    $method->setAccessible(true);
                    $menu_css = $method->invoke($plugin, $settings);
                    
                    if (!empty($menu_css) && strlen($menu_css) > 50) {
                        addTest('Menu CSS Generation', 'success', strlen($menu_css) . ' characters generated');
                    } else {
                        addTest('Menu CSS Generation', 'error', 'Menu CSS empty or too short');
                    }
                } catch (Exception $e) {
                    addTest('Menu CSS Generation', 'error', $e->getMessage());
                }
                
                // Test Admin Bar CSS generation
                try {
                    $method = $reflection->getMethod('generateAdminBarCSS');
                    $method->setAccessible(true);
                    $adminbar_css = $method->invoke($plugin, $settings);
                    
                    if (!empty($adminbar_css)) {
                        addTest('Admin Bar CSS Generation', 'success', strlen($adminbar_css) . ' characters generated');
                    } else {
                        addTest('Admin Bar CSS Generation', 'warning', 'Admin Bar CSS empty (may be disabled)');
                    }
                } catch (Exception $e) {
                    addTest('Admin Bar CSS Generation', 'error', $e->getMessage());
                }
            }
            
            echo '</div>';
            
            // ============================================
            // TEST 4: AJAX Endpoints
            // ============================================
            echo '<div class="test-section">';
            echo '<h2><span class="icon">🔌</span> Test 4: AJAX Endpoints</h2>';
            
            $ajax_actions = [
                'mas_v2_save_settings',
                'mas_v2_reset_settings',
                'mas_v2_export_settings',
                'mas_v2_import_settings',
                'mas_v2_get_preview_css',
            ];
            
            foreach ($ajax_actions as $action) {
                if (has_action("wp_ajax_{$action}")) {
                    addTest("AJAX: {$action}", 'success', 'Handler registered');
                } else {
                    addTest("AJAX: {$action}", 'error', 'Handler not registered');
                }
            }
            
            echo '</div>';
            
            // ============================================
            // TEST 5: Assets Loading
            // ============================================
            echo '<div class="test-section">';
            echo '<h2><span class="icon">📦</span> Test 5: Assets Loading</h2>';
            
            $css_files = [
                'admin-modern.css',
                'admin-menu-modern.css',
                'quick-fix.css',
                'cross-browser-compatibility.css',
            ];
            
            foreach ($css_files as $file) {
                $path = MAS_V2_PLUGIN_DIR . 'assets/css/' . $file;
                if (file_exists($path)) {
                    $size = filesize($path);
                    addTest("CSS File: {$file}", 'success', number_format($size) . ' bytes');
                } else {
                    addTest("CSS File: {$file}", 'error', 'File not found');
                }
            }
            
            $js_files = [
                'admin-settings-simple.js',
                'simple-live-preview.js',
                'cross-browser-compatibility.js',
            ];
            
            foreach ($js_files as $file) {
                $path = MAS_V2_PLUGIN_DIR . 'assets/js/' . $file;
                if (file_exists($path)) {
                    $size = filesize($path);
                    addTest("JS File: {$file}", 'success', number_format($size) . ' bytes');
                } else {
                    addTest("JS File: {$file}", 'error', 'File not found');
                }
            }
            
            echo '</div>';
            
            // ============================================
            // TEST 6: WordPress Compatibility
            // ============================================
            echo '<div class="test-section">';
            echo '<h2><span class="icon">🔧</span> Test 6: WordPress Compatibility</h2>';
            
            global $wp_version;
            $required_wp = '5.0';
            
            if (version_compare($wp_version, $required_wp, '>=')) {
                addTest('WordPress Version', 'success', "WP {$wp_version} (required: {$required_wp}+)");
            } else {
                addTest('WordPress Version', 'error', "WP {$wp_version} is below required {$required_wp}");
            }
            
            $required_php = '7.4';
            if (version_compare(PHP_VERSION, $required_php, '>=')) {
                addTest('PHP Version', 'success', "PHP " . PHP_VERSION . " (required: {$required_php}+)");
            } else {
                addTest('PHP Version', 'error', "PHP " . PHP_VERSION . " is below required {$required_php}");
            }
            
            // Check memory limit
            $memory_limit = ini_get('memory_limit');
            addTest('PHP Memory Limit', 'info', $memory_limit);
            
            // Check max execution time
            $max_execution = ini_get('max_execution_time');
            addTest('Max Execution Time', 'info', $max_execution . ' seconds');
            
            echo '</div>';
            
            // ============================================
            // TEST 7: Security
            // ============================================
            echo '<div class="test-section">';
            echo '<h2><span class="icon">🔒</span> Test 7: Security</h2>';
            
            // Check if nonce functions exist
            if (function_exists('wp_create_nonce') && function_exists('wp_verify_nonce')) {
                addTest('Nonce Functions', 'success', 'WordPress nonce functions available');
            } else {
                addTest('Nonce Functions', 'error', 'Nonce functions not available');
            }
            
            // Check if sanitization functions exist
            $sanitize_functions = [
                'sanitize_hex_color',
                'sanitize_text_field',
                'esc_html',
                'esc_attr',
                'esc_url',
            ];
            
            $missing_functions = [];
            foreach ($sanitize_functions as $func) {
                if (!function_exists($func)) {
                    $missing_functions[] = $func;
                }
            }
            
            if (empty($missing_functions)) {
                addTest('Sanitization Functions', 'success', 'All sanitization functions available');
            } else {
                addTest('Sanitization Functions', 'error', 'Missing: ' . implode(', ', $missing_functions));
            }
            
            // Check current user capabilities
            if (current_user_can('manage_options')) {
                addTest('User Capabilities', 'success', 'Current user has manage_options capability');
            } else {
                addTest('User Capabilities', 'warning', 'Current user lacks manage_options capability');
            }
            
            echo '</div>';
            
            // ============================================
            // TEST 8: Performance
            // ============================================
            echo '<div class="test-section">';
            echo '<h2><span class="icon">⚡</span> Test 8: Performance</h2>';
            
            // Test CSS generation time
            if (class_exists('ModernAdminStylerV2')) {
                $start_time = microtime(true);
                
                try {
                    $plugin = ModernAdminStylerV2::getInstance();
                    $reflection = new ReflectionClass($plugin);
                    $method = $reflection->getMethod('generateMenuCSS');
                    $method->setAccessible(true);
                    $css = $method->invoke($plugin, $settings);
                    
                    $execution_time = (microtime(true) - $start_time) * 1000;
                    
                    if ($execution_time < 100) {
                        addTest('CSS Generation Speed', 'success', number_format($execution_time, 2) . 'ms (excellent)');
                    } elseif ($execution_time < 500) {
                        addTest('CSS Generation Speed', 'warning', number_format($execution_time, 2) . 'ms (acceptable)');
                    } else {
                        addTest('CSS Generation Speed', 'error', number_format($execution_time, 2) . 'ms (too slow)');
                    }
                } catch (Exception $e) {
                    addTest('CSS Generation Speed', 'error', $e->getMessage());
                }
            }
            
            // Check memory usage
            $memory_usage = memory_get_usage(true) / 1024 / 1024;
            if ($memory_usage < 50) {
                addTest('Memory Usage', 'success', number_format($memory_usage, 2) . ' MB');
            } elseif ($memory_usage < 100) {
                addTest('Memory Usage', 'warning', number_format($memory_usage, 2) . ' MB');
            } else {
                addTest('Memory Usage', 'error', number_format($memory_usage, 2) . ' MB (high)');
            }
            
            echo '</div>';
            
            // ============================================
            // Display All Test Results
            // ============================================
            echo '<div class="test-section">';
            echo '<h2><span class="icon">📋</span> All Test Results</h2>';
            
            foreach ($tests as $test) {
                $status_class = $test['status'];
                $icon = '';
                
                switch ($test['status']) {
                    case 'success':
                        $icon = '✅';
                        break;
                    case 'error':
                        $icon = '❌';
                        break;
                    case 'warning':
                        $icon = '⚠️';
                        break;
                    case 'info':
                        $icon = 'ℹ️';
                        break;
                }
                
                echo '<div class="test-item">';
                echo '<div class="test-name">' . esc_html($test['name']) . '</div>';
                echo '<div class="test-result ' . $status_class . '">';
                echo '<span class="icon">' . $icon . '</span>';
                echo esc_html($test['message']);
                echo '</div>';
                echo '</div>';
                
                if (!empty($test['details'])) {
                    echo '<div class="details"><pre>' . esc_html($test['details']) . '</pre></div>';
                }
            }
            
            echo '</div>';
            
            // ============================================
            // Summary
            // ============================================
            $success_rate = $total_tests > 0 ? ($passed_tests / $total_tests) * 100 : 0;
            
            echo '<div class="summary">';
            echo '<h2>📊 Test Summary</h2>';
            
            echo '<div class="progress-bar">';
            echo '<div class="progress-fill" style="width: ' . $success_rate . '%">';
            echo number_format($success_rate, 1) . '%';
            echo '</div>';
            echo '</div>';
            
            echo '<div class="stats">';
            echo '<div class="stat-box">';
            echo '<div class="stat-number">' . $total_tests . '</div>';
            echo '<div class="stat-label">Total Tests</div>';
            echo '</div>';
            
            echo '<div class="stat-box">';
            echo '<div class="stat-number">' . $passed_tests . '</div>';
            echo '<div class="stat-label">Passed</div>';
            echo '</div>';
            
            echo '<div class="stat-box">';
            echo '<div class="stat-number">' . $failed_tests . '</div>';
            echo '<div class="stat-label">Failed</div>';
            echo '</div>';
            
            echo '<div class="stat-box">';
            echo '<div class="stat-number">' . $warning_tests . '</div>';
            echo '<div class="stat-label">Warnings</div>';
            echo '</div>';
            echo '</div>';
            
            if ($failed_tests === 0 && $warning_tests === 0) {
                echo '<p style="margin-top: 20px; font-size: 20px;">🎉 All tests passed! Plugin is ready for production.</p>';
            } elseif ($failed_tests === 0) {
                echo '<p style="margin-top: 20px; font-size: 18px;">✅ All critical tests passed. Some warnings to review.</p>';
            } else {
                echo '<p style="margin-top: 20px; font-size: 18px;">⚠️ Some tests failed. Please review and fix issues.</p>';
            }
            
            echo '</div>';
            
            // ============================================
            // Action Buttons
            // ============================================
            echo '<div class="action-buttons">';
            echo '<a href="' . admin_url('admin.php?page=mas-v2-settings') . '" class="btn btn-primary">Open Plugin Settings</a>';
            echo '<a href="' . admin_url('plugins.php') . '" class="btn btn-secondary">Manage Plugins</a>';
            echo '<a href="test-current-save-status.php" class="btn btn-secondary">Detailed Diagnostics</a>';
            echo '<button onclick="window.location.reload()" class="btn btn-secondary">Rerun Tests</button>';
            echo '</div>';
            ?>
        </div>
    </div>
</body>
</html>
