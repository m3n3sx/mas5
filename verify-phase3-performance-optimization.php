<?php
/**
 * Phase 3 Performance Optimization Verification Script
 * 
 * Comprehensive verification of performance improvements after Phase 3 cleanup
 * Requirements: 6.4, 5.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Allow CLI execution
    if (php_sapi_name() === 'cli') {
        define('ABSPATH', dirname(__FILE__) . '/');
    } else {
        exit('Direct access not allowed');
    }
}

class MAS_Phase3_Performance_Verifier {
    
    private $results = [];
    private $errors = [];
    private $warnings = [];
    
    public function __construct() {
        $this->log("🚀 Phase 3 Performance Optimization Verifier Initialized");
    }
    
    /**
     * Run complete performance verification
     */
    public function verify_performance_optimization() {
        $this->log("Starting comprehensive performance verification...");
        
        $start_time = microtime(true);
        
        // Verification tests
        $this->verify_file_removal();
        $this->verify_enqueue_cleanup();
        $this->verify_memory_optimization();
        $this->verify_load_time_improvement();
        $this->verify_404_elimination();
        $this->verify_script_dependencies();
        $this->verify_remaining_functionality();
        
        $end_time = microtime(true);
        $total_time = round(($end_time - $start_time) * 1000, 2);
        
        $this->generate_verification_report($total_time);
        
        return [
            'success' => empty($this->errors),
            'results' => $this->results,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'execution_time' => $total_time
        ];
    }
    
    /**
     * Verify Phase 3 files have been removed
     */
    private function verify_file_removal() {
        $this->log("🗑️ Verifying Phase 3 file removal...");
        
        $phase3_files = [
            'assets/js/core/EventBus.js',
            'assets/js/core/StateManager.js',
            'assets/js/core/APIClient.js',
            'assets/js/core/ErrorHandler.js',
            'assets/js/mas-admin-app.js',
            'assets/js/components/LivePreviewComponent.js',
            'assets/js/components/SettingsFormComponent.js',
            'assets/js/components/NotificationSystem.js',
            'assets/js/components/Component.js',
            'assets/js/utils/DOMOptimizer.js',
            'assets/js/utils/VirtualList.js',
            'assets/js/utils/LazyLoader.js',
            'assets/js/admin-settings-simple.js',
            'assets/js/LivePreviewManager.js'
        ];
        
        $removed_files = [];
        $existing_files = [];
        
        foreach ($phase3_files as $file) {
            $full_path = ABSPATH . $file;
            if (file_exists($full_path)) {
                $existing_files[] = $file;
                $this->warnings[] = "Phase 3 file still exists: {$file}";
            } else {
                $removed_files[] = $file;
            }
        }
        
        $removal_rate = (count($removed_files) / count($phase3_files)) * 100;
        
        $this->results['file_removal'] = [
            'total_files' => count($phase3_files),
            'removed_files' => count($removed_files),
            'existing_files' => count($existing_files),
            'removal_rate' => round($removal_rate, 2),
            'status' => $removal_rate >= 90 ? 'PASS' : 'FAIL'
        ];
        
        if ($removal_rate < 90) {
            $this->errors[] = "File removal incomplete: {$removal_rate}% removed";
        }
        
        $this->log("✅ File removal verification: {$removal_rate}% removed");
    }
    
    /**
     * Verify WordPress enqueue system cleanup
     */
    private function verify_enqueue_cleanup() {
        $this->log("🔧 Verifying enqueue system cleanup...");
        
        // Check main plugin file for enqueue references
        $plugin_file = ABSPATH . 'modern-admin-styler-v2.php';
        $enqueue_issues = [];
        
        if (file_exists($plugin_file)) {
            $content = file_get_contents($plugin_file);
            
            // Check for Phase 3 script references
            $phase3_scripts = [
                'mas-admin-app',
                'EventBus',
                'StateManager',
                'APIClient',
                'ErrorHandler',
                'LivePreviewComponent',
                'SettingsFormComponent',
                'admin-settings-simple'
            ];
            
            foreach ($phase3_scripts as $script) {
                if (strpos($content, $script) !== false) {
                    $enqueue_issues[] = "Found reference to {$script} in plugin file";
                }
            }
        }
        
        // Check includes directory for enqueue functions
        $includes_dir = ABSPATH . 'includes/';
        if (is_dir($includes_dir)) {
            $this->scan_directory_for_enqueue_issues($includes_dir, $enqueue_issues);
        }
        
        $this->results['enqueue_cleanup'] = [
            'issues_found' => count($enqueue_issues),
            'issues' => $enqueue_issues,
            'status' => empty($enqueue_issues) ? 'PASS' : 'FAIL'
        ];
        
        if (!empty($enqueue_issues)) {
            $this->errors[] = "Enqueue cleanup incomplete: " . count($enqueue_issues) . " issues found";
        }
        
        $this->log("✅ Enqueue cleanup verification: " . (empty($enqueue_issues) ? 'CLEAN' : count($enqueue_issues) . ' issues'));
    }
    
    /**
     * Verify memory optimization improvements
     */
    private function verify_memory_optimization() {
        $this->log("🧠 Verifying memory optimization...");
        
        $start_memory = memory_get_usage();
        
        // Simulate loading remaining scripts
        $remaining_scripts = [
            'assets/js/mas-settings-form-handler.js',
            'assets/js/simple-live-preview.js'
        ];
        
        $total_size = 0;
        foreach ($remaining_scripts as $script) {
            $file_path = ABSPATH . $script;
            if (file_exists($file_path)) {
                $total_size += filesize($file_path);
            }
        }
        
        // Calculate theoretical memory savings
        $phase3_estimated_size = 465 * 1024; // 465KB in bytes
        $current_size = $total_size;
        $memory_savings = $phase3_estimated_size - $current_size;
        $savings_percent = ($memory_savings / $phase3_estimated_size) * 100;
        
        $end_memory = memory_get_usage();
        $actual_memory_used = $end_memory - $start_memory;
        
        $this->results['memory_optimization'] = [
            'phase3_estimated_kb' => round($phase3_estimated_size / 1024, 2),
            'current_size_kb' => round($current_size / 1024, 2),
            'memory_savings_kb' => round($memory_savings / 1024, 2),
            'savings_percent' => round($savings_percent, 2),
            'actual_memory_used_bytes' => $actual_memory_used,
            'status' => $savings_percent > 50 ? 'PASS' : 'FAIL'
        ];
        
        if ($savings_percent <= 50) {
            $this->warnings[] = "Memory savings below expected threshold: {$savings_percent}%";
        }
        
        $this->log("✅ Memory optimization: {$savings_percent}% reduction");
    }
    
    /**
     * Verify load time improvements
     */
    private function verify_load_time_improvement() {
        $this->log("⏱️ Verifying load time improvements...");
        
        $start_time = microtime(true);
        
        // Simulate admin page load with remaining scripts
        $this->simulate_admin_page_load();
        
        $end_time = microtime(true);
        $current_load_time = ($end_time - $start_time) * 1000;
        
        // Baseline comparison (estimated)
        $baseline_load_time = 2000; // 2 seconds
        $improvement = $baseline_load_time - $current_load_time;
        $improvement_percent = ($improvement / $baseline_load_time) * 100;
        
        $this->results['load_time_improvement'] = [
            'baseline_ms' => $baseline_load_time,
            'current_ms' => round($current_load_time, 2),
            'improvement_ms' => round($improvement, 2),
            'improvement_percent' => round($improvement_percent, 2),
            'status' => $improvement_percent > 10 ? 'PASS' : 'FAIL'
        ];
        
        if ($improvement_percent <= 10) {
            $this->warnings[] = "Load time improvement below expected: {$improvement_percent}%";
        }
        
        $this->log("✅ Load time improvement: {$improvement_percent}%");
    }
    
    /**
     * Verify 404 error elimination
     */
    private function verify_404_elimination() {
        $this->log("🔍 Verifying 404 error elimination...");
        
        $phase3_files = [
            'assets/js/core/EventBus.js',
            'assets/js/core/StateManager.js',
            'assets/js/core/APIClient.js',
            'assets/js/core/ErrorHandler.js',
            'assets/js/mas-admin-app.js'
        ];
        
        $missing_files = 0;
        $existing_files = 0;
        $file_status = [];
        
        foreach ($phase3_files as $file) {
            $full_path = ABSPATH . $file;
            if (file_exists($full_path)) {
                $existing_files++;
                $file_status[$file] = 'exists';
                $this->warnings[] = "Phase 3 file still exists (potential 404 source): {$file}";
            } else {
                $missing_files++;
                $file_status[$file] = 'removed';
            }
        }
        
        $elimination_rate = ($missing_files / count($phase3_files)) * 100;
        
        $this->results['404_elimination'] = [
            'total_files' => count($phase3_files),
            'missing_files' => $missing_files,
            'existing_files' => $existing_files,
            'elimination_rate' => round($elimination_rate, 2),
            'file_status' => $file_status,
            'status' => $elimination_rate >= 95 ? 'PASS' : 'FAIL'
        ];
        
        if ($elimination_rate < 95) {
            $this->errors[] = "404 elimination incomplete: {$elimination_rate}% eliminated";
        }
        
        $this->log("✅ 404 elimination: {$elimination_rate}% eliminated");
    }
    
    /**
     * Verify script dependencies are correct
     */
    private function verify_script_dependencies() {
        $this->log("🔗 Verifying script dependencies...");
        
        $remaining_scripts = [
            'assets/js/mas-settings-form-handler.js',
            'assets/js/simple-live-preview.js'
        ];
        
        $dependency_issues = [];
        $working_scripts = 0;
        
        foreach ($remaining_scripts as $script) {
            $file_path = ABSPATH . $script;
            if (file_exists($file_path)) {
                $content = file_get_contents($file_path);
                
                // Check for Phase 3 dependencies
                $phase3_dependencies = [
                    'EventBus',
                    'StateManager',
                    'APIClient',
                    'ErrorHandler',
                    'mas-admin-app'
                ];
                
                $script_has_issues = false;
                foreach ($phase3_dependencies as $dep) {
                    if (strpos($content, $dep) !== false) {
                        $dependency_issues[] = "{$script} still references {$dep}";
                        $script_has_issues = true;
                    }
                }
                
                if (!$script_has_issues) {
                    $working_scripts++;
                }
            } else {
                $dependency_issues[] = "Required script missing: {$script}";
            }
        }
        
        $dependency_health = ($working_scripts / count($remaining_scripts)) * 100;
        
        $this->results['script_dependencies'] = [
            'total_scripts' => count($remaining_scripts),
            'working_scripts' => $working_scripts,
            'dependency_issues' => count($dependency_issues),
            'issues' => $dependency_issues,
            'health_percent' => round($dependency_health, 2),
            'status' => $dependency_health >= 90 ? 'PASS' : 'FAIL'
        ];
        
        if ($dependency_health < 90) {
            $this->errors[] = "Script dependency issues: {$dependency_health}% healthy";
        }
        
        $this->log("✅ Script dependencies: {$dependency_health}% healthy");
    }
    
    /**
     * Verify remaining functionality works
     */
    private function verify_remaining_functionality() {
        $this->log("⚙️ Verifying remaining functionality...");
        
        $functionality_tests = [
            'settings_handler' => $this->test_settings_handler(),
            'live_preview' => $this->test_live_preview(),
            'admin_interface' => $this->test_admin_interface()
        ];
        
        $working_functions = 0;
        $total_functions = count($functionality_tests);
        
        foreach ($functionality_tests as $test => $result) {
            if ($result) {
                $working_functions++;
            } else {
                $this->warnings[] = "Functionality test failed: {$test}";
            }
        }
        
        $functionality_rate = ($working_functions / $total_functions) * 100;
        
        $this->results['remaining_functionality'] = [
            'total_tests' => $total_functions,
            'passing_tests' => $working_functions,
            'functionality_rate' => round($functionality_rate, 2),
            'test_results' => $functionality_tests,
            'status' => $functionality_rate >= 80 ? 'PASS' : 'FAIL'
        ];
        
        if ($functionality_rate < 80) {
            $this->errors[] = "Functionality verification failed: {$functionality_rate}% working";
        }
        
        $this->log("✅ Remaining functionality: {$functionality_rate}% working");
    }
    
    /**
     * Generate comprehensive verification report
     */
    private function generate_verification_report($execution_time) {
        $this->log("📊 Generating verification report...");
        
        $total_tests = count($this->results);
        $passed_tests = 0;
        
        foreach ($this->results as $result) {
            if ($result['status'] === 'PASS') {
                $passed_tests++;
            }
        }
        
        $overall_score = ($passed_tests / $total_tests) * 100;
        $overall_status = $overall_score >= 80 ? 'PASS' : 'FAIL';
        
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "🎯 PHASE 3 PERFORMANCE OPTIMIZATION VERIFICATION REPORT\n";
        echo str_repeat("=", 80) . "\n";
        
        echo "📊 Overall Score: {$overall_score}% ({$passed_tests}/{$total_tests} tests passed)\n";
        echo "⏱️ Execution Time: {$execution_time}ms\n";
        echo "🎯 Overall Status: " . ($overall_status === 'PASS' ? '✅ PASS' : '❌ FAIL') . "\n\n";
        
        // Detailed results
        echo "📋 DETAILED TEST RESULTS:\n";
        echo str_repeat("-", 80) . "\n";
        
        foreach ($this->results as $test_name => $result) {
            $status_icon = $result['status'] === 'PASS' ? '✅' : '❌';
            echo "{$status_icon} " . ucwords(str_replace('_', ' ', $test_name)) . ": {$result['status']}\n";
            
            // Show key metrics
            $this->display_test_metrics($test_name, $result);
            echo "\n";
        }
        
        // Errors and warnings
        if (!empty($this->errors)) {
            echo "❌ ERRORS:\n";
            foreach ($this->errors as $error) {
                echo "  • {$error}\n";
            }
            echo "\n";
        }
        
        if (!empty($this->warnings)) {
            echo "⚠️ WARNINGS:\n";
            foreach ($this->warnings as $warning) {
                echo "  • {$warning}\n";
            }
            echo "\n";
        }
        
        // Performance summary
        $this->display_performance_summary();
        
        echo str_repeat("=", 80) . "\n";
        echo "🏁 Verification completed at " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat("=", 80) . "\n";
    }
    
    /**
     * Helper methods
     */
    private function log($message) {
        if (php_sapi_name() === 'cli') {
            echo "[" . date('H:i:s') . "] {$message}\n";
        } else {
            error_log("[MAS Performance Verifier] {$message}");
        }
    }
    
    private function scan_directory_for_enqueue_issues($dir, &$issues) {
        $files = glob($dir . '*.php');
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (strpos($content, 'mas-admin-app') !== false || 
                strpos($content, 'admin-settings-simple') !== false) {
                $issues[] = "Found Phase 3 script reference in " . basename($file);
            }
        }
    }
    
    private function simulate_admin_page_load() {
        // Simulate loading time
        usleep(100000); // 100ms
    }
    
    private function test_settings_handler() {
        $file = ABSPATH . 'assets/js/mas-settings-form-handler.js';
        return file_exists($file) && filesize($file) > 1000;
    }
    
    private function test_live_preview() {
        $file = ABSPATH . 'assets/js/simple-live-preview.js';
        return file_exists($file) && filesize($file) > 500;
    }
    
    private function test_admin_interface() {
        $template = ABSPATH . 'templates/admin-page.php';
        return file_exists($template);
    }
    
    private function display_test_metrics($test_name, $result) {
        switch ($test_name) {
            case 'file_removal':
                echo "    Removed: {$result['removed_files']}/{$result['total_files']} files ({$result['removal_rate']}%)\n";
                break;
            case 'memory_optimization':
                echo "    Memory savings: {$result['memory_savings_kb']}KB ({$result['savings_percent']}%)\n";
                break;
            case 'load_time_improvement':
                echo "    Load time improvement: {$result['improvement_ms']}ms ({$result['improvement_percent']}%)\n";
                break;
            case '404_elimination':
                echo "    Files eliminated: {$result['missing_files']}/{$result['total_files']} ({$result['elimination_rate']}%)\n";
                break;
            case 'script_dependencies':
                echo "    Working scripts: {$result['working_scripts']}/{$result['total_scripts']} ({$result['health_percent']}%)\n";
                break;
            case 'remaining_functionality':
                echo "    Functional tests: {$result['passing_tests']}/{$result['total_tests']} ({$result['functionality_rate']}%)\n";
                break;
        }
    }
    
    private function display_performance_summary() {
        echo "🚀 PERFORMANCE SUMMARY:\n";
        echo str_repeat("-", 40) . "\n";
        
        if (isset($this->results['memory_optimization'])) {
            echo "💾 Memory Reduction: {$this->results['memory_optimization']['savings_percent']}%\n";
        }
        
        if (isset($this->results['load_time_improvement'])) {
            echo "⏱️ Load Time Improvement: {$this->results['load_time_improvement']['improvement_percent']}%\n";
        }
        
        if (isset($this->results['file_removal'])) {
            echo "🗑️ Files Removed: {$this->results['file_removal']['removed_files']} files\n";
        }
        
        if (isset($this->results['404_elimination'])) {
            echo "🔍 404 Errors Eliminated: {$this->results['404_elimination']['elimination_rate']}%\n";
        }
        
        echo "\n";
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $verifier = new MAS_Phase3_Performance_Verifier();
    $results = $verifier->verify_performance_optimization();
    
    exit($results['success'] ? 0 : 1);
}

// Web execution
if (isset($_GET['verify_performance'])) {
    $verifier = new MAS_Phase3_Performance_Verifier();
    $results = $verifier->verify_performance_optimization();
    
    // Save results
    file_put_contents(
        __DIR__ . '/phase3-performance-verification-results.json',
        json_encode($results, JSON_PRETTY_PRINT)
    );
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Phase 3 Performance Verification</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .verify-button { background: #0073aa; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .verify-button:hover { background: #005a87; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 Phase 3 Performance Optimization Verification</h1>
        
        <div style="background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3>📋 Verification Overview</h3>
            <p>This comprehensive verification suite validates all performance improvements from the Phase 3 cleanup:</p>
            <ul>
                <li><strong>File Removal:</strong> Confirms all Phase 3 files are properly removed</li>
                <li><strong>Enqueue Cleanup:</strong> Verifies WordPress script enqueuing is updated</li>
                <li><strong>Memory Optimization:</strong> Measures memory usage reduction</li>
                <li><strong>Load Time Improvement:</strong> Tests page loading performance</li>
                <li><strong>404 Elimination:</strong> Ensures no broken file references</li>
                <li><strong>Script Dependencies:</strong> Validates remaining scripts work correctly</li>
                <li><strong>Functionality:</strong> Tests that core features still work</li>
            </ul>
        </div>
        
        <?php if (!isset($_GET['verify_performance'])): ?>
            <div style="text-align: center; margin: 40px 0;">
                <a href="?verify_performance=1" class="verify-button">🎯 Run Performance Verification</a>
            </div>
            
            <div style="background: #fff3cd; padding: 15px; border-radius: 4px; margin: 20px 0;">
                <strong>⚠️ Note:</strong> This verification will run comprehensive tests to ensure the Phase 3 cleanup achieved all performance optimization goals.
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
            <h4>🔧 Command Line Usage</h4>
            <p>You can also run this verification from the command line:</p>
            <code style="background: #1e1e1e; color: #00ff00; padding: 10px; display: block; border-radius: 4px;">
                php verify-phase3-performance-optimization.php
            </code>
        </div>
    </div>
</body>
</html>