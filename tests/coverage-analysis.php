<?php
/**
 * Test Coverage Analysis Script
 * 
 * Analyzes test coverage and identifies missing test cases
 */

class MAS_Coverage_Analysis {
    
    private $coverage_data = [];
    private $source_files = [];
    private $test_files = [];
    
    public function __construct() {
        $this->scan_source_files();
        $this->scan_test_files();
    }
    
    /**
     * Run coverage analysis
     */
    public function analyze() {
        echo "Analyzing test coverage...\n";
        
        $this->analyze_rest_controllers();
        $this->analyze_services();
        $this->analyze_javascript_files();
        $this->identify_missing_tests();
        $this->generate_report();
    }
    
    /**
     * Scan source files
     */
    private function scan_source_files() {
        $directories = [
            'includes/api/',
            'includes/services/',
            'assets/js/modules/',
            'assets/js/'
        ];
        
        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '*.php') ?: [];
                $js_files = glob($dir . '*.js') ?: [];
                $this->source_files = array_merge($this->source_files, $files, $js_files);
            }
        }
    }
    
    /**
     * Scan test files
     */
    private function scan_test_files() {
        $directories = [
            'tests/php/unit/',
            'tests/php/integration/',
            'tests/php/rest-api/',
            'tests/php/e2e/',
            'tests/js/'
        ];
        
        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '*.php') ?: [];
                $js_files = glob($dir . '*.js') ?: [];
                $this->test_files = array_merge($this->test_files, $files, $js_files);
            }
        }
    }
    
    /**
     * Analyze REST controller coverage
     */
    private function analyze_rest_controllers() {
        echo "Analyzing REST controller coverage...\n";
        
        $controllers = [
            'includes/api/class-mas-rest-controller.php',
            'includes/api/class-mas-settings-controller.php',
            'includes/api/class-mas-themes-controller.php',
            'includes/api/class-mas-backups-controller.php',
            'includes/api/class-mas-import-export-controller.php',
            'includes/api/class-mas-preview-controller.php',
            'includes/api/class-mas-diagnostics-controller.php'
        ];
        
        foreach ($controllers as $controller) {
            if (file_exists($controller)) {
                $this->analyze_php_file($controller);
            }
        }
    }    

    /**
     * Analyze service coverage
     */
    private function analyze_services() {
        echo "Analyzing service coverage...\n";
        
        $services = [
            'includes/services/class-mas-settings-service.php',
            'includes/services/class-mas-theme-service.php',
            'includes/services/class-mas-backup-service.php',
            'includes/services/class-mas-import-export-service.php',
            'includes/services/class-mas-css-generator-service.php',
            'includes/services/class-mas-diagnostics-service.php',
            'includes/services/class-mas-validation-service.php',
            'includes/services/class-mas-cache-service.php',
            'includes/services/class-mas-rate-limiter-service.php'
        ];
        
        foreach ($services as $service) {
            if (file_exists($service)) {
                $this->analyze_php_file($service);
            }
        }
    }
    
    /**
     * Analyze JavaScript file coverage
     */
    private function analyze_javascript_files() {
        echo "Analyzing JavaScript coverage...\n";
        
        $js_files = [
            'assets/js/mas-rest-client.js',
            'assets/js/mas-dual-mode-client.js',
            'assets/js/modules/SettingsManager.js',
            'assets/js/modules/ThemeManager.js',
            'assets/js/modules/BackupManager.js',
            'assets/js/modules/PreviewManager.js',
            'assets/js/modules/DiagnosticsManager.js'
        ];
        
        foreach ($js_files as $js_file) {
            if (file_exists($js_file)) {
                $this->analyze_js_file($js_file);
            }
        }
    }
    
    /**
     * Analyze PHP file for test coverage
     */
    private function analyze_php_file($file_path) {
        $content = file_get_contents($file_path);
        $class_name = $this->extract_class_name($content);
        
        if (!$class_name) {
            return;
        }
        
        // Extract methods
        preg_match_all('/(?:public|protected|private)\s+function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $content, $matches);
        $methods = $matches[1] ?? [];
        
        // Check for corresponding test file
        $test_file = $this->find_test_file($class_name);
        $tested_methods = [];
        
        if ($test_file) {
            $test_content = file_get_contents($test_file);
            foreach ($methods as $method) {
                if (strpos($test_content, "test_{$method}") !== false || 
                    strpos($test_content, "test_" . strtolower($method)) !== false) {
                    $tested_methods[] = $method;
                }
            }
        }
        
        $coverage_percent = count($methods) > 0 ? (count($tested_methods) / count($methods)) * 100 : 0;
        
        $this->coverage_data[$file_path] = [
            'class' => $class_name,
            'methods' => $methods,
            'tested_methods' => $tested_methods,
            'untested_methods' => array_diff($methods, $tested_methods),
            'coverage_percent' => $coverage_percent,
            'test_file' => $test_file
        ];
        
        echo "  {$class_name}: " . number_format($coverage_percent, 1) . "% coverage\n";
    }
    
    /**
     * Analyze JavaScript file for test coverage
     */
    private function analyze_js_file($file_path) {
        $content = file_get_contents($file_path);
        
        // Extract class/function names
        preg_match_all('/(?:class\s+([a-zA-Z_][a-zA-Z0-9_]*)|function\s+([a-zA-Z_][a-zA-Z0-9_]*)|([a-zA-Z_][a-zA-Z0-9_]*)\s*:\s*function)/', $content, $matches);
        
        $functions = array_filter(array_merge($matches[1], $matches[2], $matches[3]));
        
        // Check for corresponding test file
        $basename = basename($file_path, '.js');
        $test_file = "tests/js/{$basename}.test.js";
        $tested_functions = [];
        
        if (file_exists($test_file)) {
            $test_content = file_get_contents($test_file);
            foreach ($functions as $func) {
                if (strpos($test_content, "test(") !== false && strpos($test_content, $func) !== false) {
                    $tested_functions[] = $func;
                }
            }
        }
        
        $coverage_percent = count($functions) > 0 ? (count($tested_functions) / count($functions)) * 100 : 0;
        
        $this->coverage_data[$file_path] = [
            'functions' => $functions,
            'tested_functions' => $tested_functions,
            'untested_functions' => array_diff($functions, $tested_functions),
            'coverage_percent' => $coverage_percent,
            'test_file' => file_exists($test_file) ? $test_file : null
        ];
        
        echo "  {$basename}.js: " . number_format($coverage_percent, 1) . "% coverage\n";
    }
    
    /**
     * Extract class name from PHP content
     */
    private function extract_class_name($content) {
        preg_match('/class\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*(?:extends|implements|\{)/', $content, $matches);
        return $matches[1] ?? null;
    }
    
    /**
     * Find corresponding test file for a class
     */
    private function find_test_file($class_name) {
        $possible_names = [
            "tests/php/unit/Test{$class_name}.php",
            "tests/php/integration/Test{$class_name}.php",
            "tests/php/rest-api/Test{$class_name}.php",
            "tests/php/e2e/Test{$class_name}.php"
        ];
        
        foreach ($possible_names as $name) {
            if (file_exists($name)) {
                return $name;
            }
        }
        
        return null;
    }
    
    /**
     * Identify missing test cases
     */
    private function identify_missing_tests() {
        echo "\nIdentifying missing test cases...\n";
        
        $missing_tests = [];
        
        foreach ($this->coverage_data as $file => $data) {
            if ($data['coverage_percent'] < 80) {
                $missing_tests[$file] = $data;
            }
        }
        
        if (!empty($missing_tests)) {
            echo "Files with insufficient coverage (<80%):\n";
            foreach ($missing_tests as $file => $data) {
                echo "  {$file}: " . number_format($data['coverage_percent'], 1) . "%\n";
                if (!empty($data['untested_methods'])) {
                    echo "    Missing tests for methods: " . implode(', ', $data['untested_methods']) . "\n";
                }
                if (!empty($data['untested_functions'])) {
                    echo "    Missing tests for functions: " . implode(', ', $data['untested_functions']) . "\n";
                }
            }
        }
    }
    
    /**
     * Generate coverage report
     */
    private function generate_report() {
        $total_files = count($this->coverage_data);
        $total_coverage = 0;
        $files_above_80 = 0;
        
        foreach ($this->coverage_data as $data) {
            $total_coverage += $data['coverage_percent'];
            if ($data['coverage_percent'] >= 80) {
                $files_above_80++;
            }
        }
        
        $average_coverage = $total_files > 0 ? $total_coverage / $total_files : 0;
        $coverage_goal_met = $average_coverage >= 80;
        
        echo "\n=== Test Coverage Report ===\n";
        echo "Total files analyzed: {$total_files}\n";
        echo "Average coverage: " . number_format($average_coverage, 1) . "%\n";
        echo "Files meeting 80% goal: {$files_above_80}/{$total_files}\n";
        echo "Coverage goal met: " . ($coverage_goal_met ? "✓ YES" : "✗ NO") . "\n";
        
        // Recommendations
        echo "\nRecommendations:\n";
        
        $low_coverage_files = array_filter($this->coverage_data, function($data) {
            return $data['coverage_percent'] < 80;
        });
        
        if (!empty($low_coverage_files)) {
            echo "1. Add tests for the following files:\n";
            foreach ($low_coverage_files as $file => $data) {
                echo "   - {$file} (" . number_format($data['coverage_percent'], 1) . "% coverage)\n";
            }
        }
        
        $files_without_tests = array_filter($this->coverage_data, function($data) {
            return empty($data['test_file']);
        });
        
        if (!empty($files_without_tests)) {
            echo "2. Create test files for:\n";
            foreach ($files_without_tests as $file => $data) {
                echo "   - {$file}\n";
            }
        }
        
        // Save detailed report
        $report_data = [
            'timestamp' => current_time('mysql'),
            'total_files' => $total_files,
            'average_coverage' => $average_coverage,
            'files_above_80' => $files_above_80,
            'coverage_goal_met' => $coverage_goal_met,
            'detailed_coverage' => $this->coverage_data
        ];
        
        file_put_contents(
            WP_CONTENT_DIR . '/mas-coverage-report.json',
            json_encode($report_data, JSON_PRETTY_PRINT)
        );
        
        echo "\nDetailed report saved to: " . WP_CONTENT_DIR . "/mas-coverage-report.json\n";
    }
}

// Run coverage analysis if called directly
if (defined('WP_CLI') && WP_CLI) {
    $coverage = new MAS_Coverage_Analysis();
    $coverage->analyze();
}