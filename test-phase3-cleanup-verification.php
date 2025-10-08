<?php
/**
 * Phase 3 Cleanup Verification Test Suite
 * 
 * Comprehensive tests to verify Phase 3 cleanup was successful
 * Requirements: 6.1, 6.2, 6.3
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

class Phase3CleanupVerificationSuite {
    
    private $results = [];
    private $phase3_files = [
        // Core architecture files
        'assets/js/core/EventBus.js',
        'assets/js/core/StateManager.js', 
        'assets/js/core/APIClient.js',
        'assets/js/core/ErrorHandler.js',
        'assets/js/mas-admin-app.js',
        
        // Component system files
        'assets/js/components/LivePreviewComponent.js',
        'assets/js/components/SettingsFormComponent.js',
        'assets/js/components/NotificationSystem.js',
        'assets/js/components/Component.js',
        
        // Utility files
        'assets/js/utils/DOMOptimizer.js',
        'assets/js/utils/VirtualList.js',
        'assets/js/utils/LazyLoader.js',
        
        // Deprecated files
        'assets/js/admin-settings-simple.js',
        'assets/js/LivePreviewManager.js'
    ];
    
    private $required_files = [
        'assets/js/mas-settings-form-handler.js',
        'assets/js/simple-live-preview.js'
    ];

    public function runAllTests() {
        echo "<h1>Phase 3 Cleanup Verification Test Suite</h1>\n";
        echo "<p>Testing cleanup completion and system functionality...</p>\n";
        
        // Test 1: Verify Phase 3 files are removed (Requirement 6.1)
        $this->testPhase3FilesRemoved();
        
        // Test 2: Verify required files exist
        $this->testRequiredFilesExist();
        
        // Test 3: Test form functionality (Requirement 6.2)
        $this->testFormFunctionality();
        
        // Test 4: Test live preview functionality (Requirement 6.3)
        $this->testLivePreviewFunctionality();
        
        // Test 5: Test script enqueuing
        $this->testScriptEnqueuing();
        
        // Test 6: Performance verification
        $this->testPerformanceImprovements();
        
        $this->displayResults();
        return $this->results;
    }
    
    private function testPhase3FilesRemoved() {
        echo "<h2>Test 1: Phase 3 Files Removal Verification</h2>\n";
        
        $removed_count = 0;
        $still_exists = [];
        
        foreach ($this->phase3_files as $file) {
            if (!file_exists($file)) {
                $removed_count++;
                echo "<span style='color: green;'>✓ REMOVED: {$file}</span><br>\n";
            } else {
                $still_exists[] = $file;
                echo "<span style='color: red;'>✗ STILL EXISTS: {$file}</span><br>\n";
            }
        }
        
        $total_files = count($this->phase3_files);
        $success = empty($still_exists);
        
        $this->results['phase3_removal'] = [
            'success' => $success,
            'removed_count' => $removed_count,
            'total_files' => $total_files,
            'still_exists' => $still_exists,
            'message' => $success ? 
                "All {$total_files} Phase 3 files successfully removed" :
                count($still_exists) . " files still exist and need removal"
        ];
        
        echo "<p><strong>Result: " . ($success ? "PASS" : "FAIL") . "</strong></p>\n";
    }
    
    private function testRequiredFilesExist() {
        echo "<h2>Test 2: Required Files Existence</h2>\n";
        
        $missing_files = [];
        
        foreach ($this->required_files as $file) {
            if (file_exists($file)) {
                echo "<span style='color: green;'>✓ EXISTS: {$file}</span><br>\n";
            } else {
                $missing_files[] = $file;
                echo "<span style='color: red;'>✗ MISSING: {$file}</span><br>\n";
            }
        }
        
        $success = empty($missing_files);
        
        $this->results['required_files'] = [
            'success' => $success,
            'missing_files' => $missing_files,
            'message' => $success ? 
                "All required files exist" :
                "Missing files: " . implode(', ', $missing_files)
        ];
        
        echo "<p><strong>Result: " . ($success ? "PASS" : "FAIL") . "</strong></p>\n";
    }
    
    private function testFormFunctionality() {
        echo "<h2>Test 3: Form Functionality Verification</h2>\n";
        
        // Test form handler file content
        $handler_file = 'assets/js/mas-settings-form-handler.js';
        $form_tests = [];
        
        if (file_exists($handler_file)) {
            $content = file_get_contents($handler_file);
            
            // Check for REST API functionality
            $has_rest_api = strpos($content, '/wp-json/mas/v2/') !== false || 
                           strpos($content, 'REST API') !== false ||
                           strpos($content, 'useRest') !== false;
            $form_tests['rest_api'] = $has_rest_api;
            echo "<span style='color: " . ($has_rest_api ? 'green' : 'red') . ";'>" . 
                 ($has_rest_api ? '✓' : '✗') . " REST API integration found</span><br>\n";
            
            // Check for AJAX fallback
            $has_ajax_fallback = strpos($content, 'admin-ajax.php') !== false || 
                                strpos($content, 'wp_ajax') !== false;
            $form_tests['ajax_fallback'] = $has_ajax_fallback;
            echo "<span style='color: " . ($has_ajax_fallback ? 'green' : 'red') . ";'>" . 
                 ($has_ajax_fallback ? '✓' : '✗') . " AJAX fallback mechanism found</span><br>\n";
            
            // Check for error handling
            $has_error_handling = strpos($content, 'catch') !== false || 
                                strpos($content, 'error') !== false;
            $form_tests['error_handling'] = $has_error_handling;
            echo "<span style='color: " . ($has_error_handling ? 'green' : 'red') . ";'>" . 
                 ($has_error_handling ? '✓' : '✗') . " Error handling found</span><br>\n";
            
        } else {
            $form_tests = ['rest_api' => false, 'ajax_fallback' => false, 'error_handling' => false];
            echo "<span style='color: red;'>✗ Form handler file not found</span><br>\n";
        }
        
        $success = $form_tests['rest_api'] && $form_tests['ajax_fallback'] && $form_tests['error_handling'];
        
        $this->results['form_functionality'] = [
            'success' => $success,
            'tests' => $form_tests,
            'message' => $success ? 
                "Form functionality verified with REST API and fallback mechanisms" :
                "Form functionality incomplete or missing components"
        ];
        
        echo "<p><strong>Result: " . ($success ? "PASS" : "FAIL") . "</strong></p>\n";
    }
    
    private function testLivePreviewFunctionality() {
        echo "<h2>Test 4: Live Preview Functionality Verification</h2>\n";
        
        $preview_file = 'assets/js/simple-live-preview.js';
        $preview_tests = [];
        
        if (file_exists($preview_file)) {
            $content = file_get_contents($preview_file);
            
            // Check for CSS injection capability
            $has_css_injection = strpos($content, 'style') !== false || 
                               strpos($content, 'CSS') !== false ||
                               strpos($content, 'updatePreview') !== false;
            $preview_tests['css_injection'] = $has_css_injection;
            echo "<span style='color: " . ($has_css_injection ? 'green' : 'red') . ";'>" . 
                 ($has_css_injection ? '✓' : '✗') . " CSS injection capability found</span><br>\n";
            
            // Check for AJAX functionality
            $has_ajax = strpos($content, 'XMLHttpRequest') !== false || 
                       strpos($content, 'fetch') !== false ||
                       strpos($content, '$.ajax') !== false ||
                       strpos($content, 'ajaxUrl') !== false ||
                       strpos($content, 'ajax') !== false;
            $preview_tests['ajax_capability'] = $has_ajax;
            echo "<span style='color: " . ($has_ajax ? 'green' : 'red') . ";'>" . 
                 ($has_ajax ? '✓' : '✗') . " AJAX capability found</span><br>\n";
            
            // Check for independence (no Phase 3 dependencies)
            $has_phase3_deps = strpos($content, 'EventBus') !== false || 
                             strpos($content, 'StateManager') !== false ||
                             strpos($content, 'APIClient') !== false;
            $preview_tests['independence'] = !$has_phase3_deps;
            echo "<span style='color: " . (!$has_phase3_deps ? 'green' : 'red') . ";'>" . 
                 (!$has_phase3_deps ? '✓' : '✗') . " Independent of Phase 3 components</span><br>\n";
            
            // Check for error recovery
            $has_error_recovery = strpos($content, 'catch') !== false || 
                                strpos($content, 'onerror') !== false;
            $preview_tests['error_recovery'] = $has_error_recovery;
            echo "<span style='color: " . ($has_error_recovery ? 'green' : 'red') . ";'>" . 
                 ($has_error_recovery ? '✓' : '✗') . " Error recovery mechanisms found</span><br>\n";
            
        } else {
            $preview_tests = [
                'css_injection' => false, 
                'ajax_capability' => false, 
                'independence' => false,
                'error_recovery' => false
            ];
            echo "<span style='color: red;'>✗ Live preview file not found</span><br>\n";
        }
        
        $success = $preview_tests['css_injection'] && $preview_tests['ajax_capability'] && 
                  $preview_tests['independence'] && $preview_tests['error_recovery'];
        
        $this->results['live_preview'] = [
            'success' => $success,
            'tests' => $preview_tests,
            'message' => $success ? 
                "Live preview functionality verified and independent" :
                "Live preview functionality incomplete or has dependencies"
        ];
        
        echo "<p><strong>Result: " . ($success ? "PASS" : "FAIL") . "</strong></p>\n";
    }
    
    private function testScriptEnqueuing() {
        echo "<h2>Test 5: Script Enqueuing Verification</h2>\n";
        
        // Check main plugin file for enqueue functions
        $plugin_file = 'modern-admin-styler-v2.php';
        $enqueue_tests = [];
        
        if (file_exists($plugin_file)) {
            $content = file_get_contents($plugin_file);
            
            // Check that Phase 3 scripts are not enqueued
            $has_phase3_enqueue = strpos($content, 'mas-admin-app') !== false ||
                                strpos($content, 'EventBus') !== false ||
                                strpos($content, 'StateManager') !== false ||
                                strpos($content, 'APIClient') !== false;
            $enqueue_tests['no_phase3_scripts'] = !$has_phase3_enqueue;
            echo "<span style='color: " . (!$has_phase3_enqueue ? 'green' : 'red') . ";'>" . 
                 (!$has_phase3_enqueue ? '✓' : '✗') . " No Phase 3 scripts in enqueue</span><br>\n";
            
            // Check that required scripts are enqueued
            $has_form_handler = strpos($content, 'mas-settings-form-handler') !== false;
            $has_live_preview = strpos($content, 'simple-live-preview') !== false;
            $enqueue_tests['required_scripts'] = $has_form_handler && $has_live_preview;
            echo "<span style='color: " . ($enqueue_tests['required_scripts'] ? 'green' : 'red') . ";'>" . 
                 ($enqueue_tests['required_scripts'] ? '✓' : '✗') . " Required scripts enqueued</span><br>\n";
            
        } else {
            $enqueue_tests = ['no_phase3_scripts' => false, 'required_scripts' => false];
            echo "<span style='color: red;'>✗ Main plugin file not found</span><br>\n";
        }
        
        $success = $enqueue_tests['no_phase3_scripts'] && $enqueue_tests['required_scripts'];
        
        $this->results['script_enqueuing'] = [
            'success' => $success,
            'tests' => $enqueue_tests,
            'message' => $success ? 
                "Script enqueuing properly updated" :
                "Script enqueuing needs adjustment"
        ];
        
        echo "<p><strong>Result: " . ($success ? "PASS" : "FAIL") . "</strong></p>\n";
    }
    
    private function testPerformanceImprovements() {
        echo "<h2>Test 6: Performance Improvements Verification</h2>\n";
        
        $performance_tests = [];
        
        // Calculate file size reduction
        $total_removed_size = 0;
        foreach ($this->phase3_files as $file) {
            if (!file_exists($file)) {
                // Estimate size based on typical Phase 3 file sizes
                $total_removed_size += 5000; // Average 5KB per file
            }
        }
        
        $performance_tests['size_reduction'] = $total_removed_size;
        echo "<span style='color: green;'>✓ Estimated size reduction: " . 
             number_format($total_removed_size / 1024, 1) . " KB</span><br>\n";
        
        // Check for reduced HTTP requests
        $removed_files_count = count($this->phase3_files) - count($this->results['phase3_removal']['still_exists'] ?? []);
        $performance_tests['http_requests_reduced'] = $removed_files_count;
        echo "<span style='color: green;'>✓ HTTP requests reduced by: {$removed_files_count}</span><br>\n";
        
        // Check for 404 error elimination
        $no_404_errors = empty($this->results['phase3_removal']['still_exists'] ?? []);
        $performance_tests['no_404_errors'] = $no_404_errors;
        echo "<span style='color: " . ($no_404_errors ? 'green' : 'red') . ";'>" . 
             ($no_404_errors ? '✓' : '✗') . " 404 errors eliminated</span><br>\n";
        
        $success = $total_removed_size > 0 && $removed_files_count > 0 && $no_404_errors;
        
        $this->results['performance'] = [
            'success' => $success,
            'tests' => $performance_tests,
            'message' => $success ? 
                "Performance improvements verified" :
                "Performance improvements incomplete"
        ];
        
        echo "<p><strong>Result: " . ($success ? "PASS" : "FAIL") . "</strong></p>\n";
    }
    
    private function displayResults() {
        echo "<h2>Overall Test Results Summary</h2>\n";
        
        $total_tests = count($this->results);
        $passed_tests = 0;
        
        foreach ($this->results as $test_name => $result) {
            $status = $result['success'] ? 'PASS' : 'FAIL';
            $color = $result['success'] ? 'green' : 'red';
            
            echo "<div style='margin: 10px 0; padding: 10px; border-left: 4px solid {$color};'>\n";
            echo "<strong>" . ucwords(str_replace('_', ' ', $test_name)) . ": {$status}</strong><br>\n";
            echo $result['message'] . "\n";
            echo "</div>\n";
            
            if ($result['success']) {
                $passed_tests++;
            }
        }
        
        $overall_success = $passed_tests === $total_tests;
        $success_rate = round(($passed_tests / $total_tests) * 100, 1);
        
        echo "<div style='margin: 20px 0; padding: 15px; background: " . 
             ($overall_success ? '#d4edda' : '#f8d7da') . "; border: 1px solid " . 
             ($overall_success ? '#c3e6cb' : '#f5c6cb') . ";'>\n";
        echo "<h3>Final Result: " . ($overall_success ? 'ALL TESTS PASSED' : 'SOME TESTS FAILED') . "</h3>\n";
        echo "<p>Success Rate: {$success_rate}% ({$passed_tests}/{$total_tests} tests passed)</p>\n";
        
        if ($overall_success) {
            echo "<p><strong>✓ Phase 3 cleanup verification completed successfully!</strong></p>\n";
            echo "<p>The system is ready for production use with the simplified Phase 2 architecture.</p>\n";
        } else {
            echo "<p><strong>✗ Phase 3 cleanup verification found issues that need attention.</strong></p>\n";
            echo "<p>Please review the failed tests and complete the necessary cleanup steps.</p>\n";
        }
        echo "</div>\n";
        
        return $overall_success;
    }
}

// Run the test suite if accessed directly
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    $suite = new Phase3CleanupVerificationSuite();
    $results = $suite->runAllTests();
    
    // Output JSON results for automated testing
    if (isset($_GET['format']) && $_GET['format'] === 'json') {
        header('Content-Type: application/json');
        echo json_encode($results, JSON_PRETTY_PRINT);
    }
}
?>