<?php
/**
 * Phase 3 Cleanup Final Verification
 * 
 * Comprehensive final check with specific issue identification
 * Requirements: 6.1, 6.2, 6.3
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

class Phase3CleanupFinalVerification {
    
    private $issues = [];
    private $successes = [];
    
    public function runFinalCheck() {
        echo "<h1>Phase 3 Cleanup Final Verification</h1>\n";
        echo "<p>Performing comprehensive final verification...</p>\n";
        
        $this->checkFileRemoval();
        $this->checkEnqueueCleanup();
        $this->checkFunctionalityIntegrity();
        $this->displayFinalResults();
        
        return empty($this->issues);
    }
    
    private function checkFileRemoval() {
        echo "<h2>1. File Removal Verification</h2>\n";
        
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
        
        $still_exists = [];
        foreach ($phase3_files as $file) {
            if (file_exists($file)) {
                $still_exists[] = $file;
            }
        }
        
        if (empty($still_exists)) {
            $this->successes[] = "All Phase 3 files successfully removed";
            echo "<p style='color: green;'>✓ All Phase 3 files successfully removed</p>\n";
        } else {
            $this->issues[] = "Phase 3 files still exist: " . implode(', ', $still_exists);
            echo "<p style='color: red;'>✗ Phase 3 files still exist:</p>\n";
            foreach ($still_exists as $file) {
                echo "<p style='color: red; margin-left: 20px;'>- {$file}</p>\n";
            }
        }
    }
    
    private function checkEnqueueCleanup() {
        echo "<h2>2. Script Enqueue Cleanup Verification</h2>\n";
        
        $plugin_file = 'modern-admin-styler-v2.php';
        if (!file_exists($plugin_file)) {
            $this->issues[] = "Main plugin file not found";
            echo "<p style='color: red;'>✗ Main plugin file not found</p>\n";
            return;
        }
        
        $content = file_get_contents($plugin_file);
        
        // Check for Phase 3 script references
        $phase3_patterns = [
            'mas-admin-app' => 'Main Phase 3 application',
            'EventBus' => 'Event Bus system',
            'StateManager' => 'State Manager',
            'APIClient' => 'API Client',
            'ErrorHandler' => 'Error Handler',
            'LivePreviewComponent' => 'Live Preview Component',
            'SettingsFormComponent' => 'Settings Form Component',
            'NotificationSystem' => 'Notification System'
        ];
        
        $found_references = [];
        foreach ($phase3_patterns as $pattern => $description) {
            if (strpos($content, $pattern) !== false) {
                $found_references[$pattern] = $description;
            }
        }
        
        if (empty($found_references)) {
            $this->successes[] = "No Phase 3 script references in enqueue system";
            echo "<p style='color: green;'>✓ No Phase 3 script references found in enqueue system</p>\n";
        } else {
            $this->issues[] = "Phase 3 script references still exist in enqueue system";
            echo "<p style='color: red;'>✗ Phase 3 script references found in enqueue system:</p>\n";
            foreach ($found_references as $pattern => $description) {
                echo "<p style='color: red; margin-left: 20px;'>- {$pattern} ({$description})</p>\n";
                
                // Show specific lines
                $lines = explode("\n", $content);
                foreach ($lines as $line_num => $line) {
                    if (strpos($line, $pattern) !== false) {
                        echo "<p style='color: orange; margin-left: 40px; font-family: monospace;'>Line " . ($line_num + 1) . ": " . htmlspecialchars(trim($line)) . "</p>\n";
                    }
                }
            }
        }
        
        // Check for required script references
        $required_scripts = [
            'mas-settings-form-handler' => 'Form Handler',
            'simple-live-preview' => 'Live Preview'
        ];
        
        $missing_required = [];
        foreach ($required_scripts as $script => $description) {
            if (strpos($content, $script) === false) {
                $missing_required[$script] = $description;
            }
        }
        
        if (empty($missing_required)) {
            $this->successes[] = "All required scripts are properly enqueued";
            echo "<p style='color: green;'>✓ All required scripts are properly enqueued</p>\n";
        } else {
            $this->issues[] = "Required scripts missing from enqueue system";
            echo "<p style='color: red;'>✗ Required scripts missing from enqueue system:</p>\n";
            foreach ($missing_required as $script => $description) {
                echo "<p style='color: red; margin-left: 20px;'>- {$script} ({$description})</p>\n";
            }
        }
    }
    
    private function checkFunctionalityIntegrity() {
        echo "<h2>3. Functionality Integrity Check</h2>\n";
        
        // Check form handler file
        $form_handler = 'assets/js/mas-settings-form-handler.js';
        if (file_exists($form_handler)) {
            $content = file_get_contents($form_handler);
            
            // Check for key functionality
            $has_rest_api = strpos($content, 'REST') !== false || strpos($content, 'wp-json') !== false;
            $has_ajax_fallback = strpos($content, 'ajax') !== false;
            $has_error_handling = strpos($content, 'catch') !== false || strpos($content, 'error') !== false;
            
            if ($has_rest_api && $has_ajax_fallback && $has_error_handling) {
                $this->successes[] = "Form handler has complete functionality";
                echo "<p style='color: green;'>✓ Form handler has REST API, AJAX fallback, and error handling</p>\n";
            } else {
                $missing = [];
                if (!$has_rest_api) $missing[] = "REST API integration";
                if (!$has_ajax_fallback) $missing[] = "AJAX fallback";
                if (!$has_error_handling) $missing[] = "Error handling";
                
                $this->issues[] = "Form handler missing functionality: " . implode(', ', $missing);
                echo "<p style='color: red;'>✗ Form handler missing: " . implode(', ', $missing) . "</p>\n";
            }
        } else {
            $this->issues[] = "Form handler file not found";
            echo "<p style='color: red;'>✗ Form handler file not found</p>\n";
        }
        
        // Check live preview file
        $live_preview = 'assets/js/simple-live-preview.js';
        if (file_exists($live_preview)) {
            $content = file_get_contents($live_preview);
            
            // Check for independence from Phase 3
            $has_phase3_deps = strpos($content, 'EventBus') !== false || 
                             strpos($content, 'StateManager') !== false ||
                             strpos($content, 'APIClient') !== false;
            
            $has_css_capability = strpos($content, 'style') !== false || strpos($content, 'CSS') !== false;
            $has_ajax_capability = strpos($content, 'ajax') !== false;
            
            if (!$has_phase3_deps && $has_css_capability && $has_ajax_capability) {
                $this->successes[] = "Live preview is independent and functional";
                echo "<p style='color: green;'>✓ Live preview is independent of Phase 3 and has CSS/AJAX capabilities</p>\n";
            } else {
                $issues = [];
                if ($has_phase3_deps) $issues[] = "Has Phase 3 dependencies";
                if (!$has_css_capability) $issues[] = "Missing CSS capability";
                if (!$has_ajax_capability) $issues[] = "Missing AJAX capability";
                
                $this->issues[] = "Live preview issues: " . implode(', ', $issues);
                echo "<p style='color: red;'>✗ Live preview issues: " . implode(', ', $issues) . "</p>\n";
            }
        } else {
            $this->issues[] = "Live preview file not found";
            echo "<p style='color: red;'>✗ Live preview file not found</p>\n";
        }
    }
    
    private function displayFinalResults() {
        echo "<h2>Final Verification Results</h2>\n";
        
        $total_checks = count($this->successes) + count($this->issues);
        $success_rate = $total_checks > 0 ? round((count($this->successes) / $total_checks) * 100, 1) : 0;
        
        if (empty($this->issues)) {
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 20px 0;'>\n";
            echo "<h3 style='color: #155724;'>✓ PHASE 3 CLEANUP COMPLETED SUCCESSFULLY</h3>\n";
            echo "<p style='color: #155724;'>All verification checks passed. The system is ready for production.</p>\n";
            echo "<p><strong>Success Rate: 100% (" . count($this->successes) . "/" . $total_checks . " checks passed)</strong></p>\n";
            echo "</div>\n";
            
            echo "<h4>Successful Checks:</h4>\n";
            foreach ($this->successes as $success) {
                echo "<p style='color: green;'>✓ {$success}</p>\n";
            }
        } else {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 20px 0;'>\n";
            echo "<h3 style='color: #721c24;'>✗ PHASE 3 CLEANUP INCOMPLETE</h3>\n";
            echo "<p style='color: #721c24;'>Issues found that need to be resolved before the cleanup is complete.</p>\n";
            echo "<p><strong>Success Rate: {$success_rate}% (" . count($this->successes) . "/" . $total_checks . " checks passed)</strong></p>\n";
            echo "</div>\n";
            
            echo "<h4>Issues to Resolve:</h4>\n";
            foreach ($this->issues as $issue) {
                echo "<p style='color: red;'>✗ {$issue}</p>\n";
            }
            
            if (!empty($this->successes)) {
                echo "<h4>Completed Successfully:</h4>\n";
                foreach ($this->successes as $success) {
                    echo "<p style='color: green;'>✓ {$success}</p>\n";
                }
            }
            
            echo "<h4>Next Steps:</h4>\n";
            echo "<ol>\n";
            if (strpos(implode(' ', $this->issues), 'script references') !== false) {
                echo "<li>Remove Phase 3 script enqueue calls from modern-admin-styler-v2.php</li>\n";
            }
            if (strpos(implode(' ', $this->issues), 'files still exist') !== false) {
                echo "<li>Delete remaining Phase 3 files from the filesystem</li>\n";
            }
            if (strpos(implode(' ', $this->issues), 'functionality') !== false) {
                echo "<li>Verify and fix form handler and live preview functionality</li>\n";
            }
            echo "<li>Re-run this verification test</li>\n";
            echo "</ol>\n";
        }
    }
}

// Run the verification if accessed directly
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    $verification = new Phase3CleanupFinalVerification();
    $success = $verification->runFinalCheck();
    
    // Set appropriate exit code for command line usage
    if (php_sapi_name() === 'cli') {
        exit($success ? 0 : 1);
    }
}
?>