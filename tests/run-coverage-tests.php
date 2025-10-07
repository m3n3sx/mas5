<?php
/**
 * Test Coverage Runner
 * 
 * Runs all tests and generates comprehensive coverage reports
 */

class MAS_Coverage_Runner {
    
    private $results = [];
    
    public function run() {
        echo "Running comprehensive test coverage analysis...\n\n";
        
        $this->run_phpunit_tests();
        $this->run_jest_tests();
        $this->analyze_coverage();
        $this->generate_final_report();
    }
    
    /**
     * Run PHPUnit tests with coverage
     */
    private function run_phpunit_tests() {
        echo "Running PHPUnit tests with coverage...\n";
        
        $command = 'vendor/bin/phpunit --coverage-clover=tests/coverage/clover.xml --coverage-html=tests/coverage/html --coverage-text';
        
        $output = [];
        $return_code = 0;
        exec($command, $output, $return_code);
        
        $this->results['phpunit'] = [
            'return_code' => $return_code,
            'output' => $output,
            'success' => $return_code === 0
        ];
        
        if ($return_code === 0) {
            echo "✓ PHPUnit tests completed successfully\n";
        } else {
            echo "✗ PHPUnit tests failed\n";
            foreach ($output as $line) {
                echo "  {$line}\n";
            }
        }
        
        echo "\n";
    }
    
    /**
     * Run Jest tests with coverage
     */
    private function run_jest_tests() {
        echo "Running Jest tests with coverage...\n";
        
        $command = 'npm test -- --coverage --coverageReporters=text --coverageReporters=lcov';
        
        $output = [];
        $return_code = 0;
        exec($command, $output, $return_code);
        
        $this->results['jest'] = [
            'return_code' => $return_code,
            'output' => $output,
            'success' => $return_code === 0
        ];
        
        if ($return_code === 0) {
            echo "✓ Jest tests completed successfully\n";
        } else {
            echo "✗ Jest tests failed\n";
            foreach ($output as $line) {
                echo "  {$line}\n";
            }
        }
        
        echo "\n";
    }
    
    /**
     * Analyze coverage data
     */
    private function analyze_coverage() {
        echo "Analyzing coverage data...\n";
        
        $php_coverage = $this->parse_php_coverage();
        $js_coverage = $this->parse_js_coverage();
        
        $this->results['coverage'] = [
            'php' => $php_coverage,
            'javascript' => $js_coverage,
            'overall' => $this->calculate_overall_coverage($php_coverage, $js_coverage)
        ];
        
        echo "PHP Coverage: " . number_format($php_coverage['percentage'], 1) . "%\n";
        echo "JavaScript Coverage: " . number_format($js_coverage['percentage'], 1) . "%\n";
        echo "Overall Coverage: " . number_format($this->results['coverage']['overall']['percentage'], 1) . "%\n";
        echo "\n";
    }
    
    /**
     * Parse PHP coverage from clover.xml
     */
    private function parse_php_coverage() {
        $clover_file = 'tests/coverage/clover.xml';
        
        if (!file_exists($clover_file)) {
            return ['percentage' => 0, 'lines_covered' => 0, 'lines_total' => 0];
        }
        
        $xml = simplexml_load_file($clover_file);
        $metrics = $xml->project->metrics;
        
        $lines_covered = (int) $metrics['coveredstatements'];
        $lines_total = (int) $metrics['statements'];
        $percentage = $lines_total > 0 ? ($lines_covered / $lines_total) * 100 : 0;
        
        return [
            'percentage' => $percentage,
            'lines_covered' => $lines_covered,
            'lines_total' => $lines_total
        ];
    }
    
    /**
     * Parse JavaScript coverage from lcov.info
     */
    private function parse_js_coverage() {
        $lcov_file = 'coverage/lcov.info';
        
        if (!file_exists($lcov_file)) {
            return ['percentage' => 0, 'lines_covered' => 0, 'lines_total' => 0];
        }
        
        $content = file_get_contents($lcov_file);
        
        // Parse LCOV format
        preg_match_all('/LH:(\d+)/', $content, $covered_matches);
        preg_match_all('/LF:(\d+)/', $content, $total_matches);
        
        $lines_covered = array_sum($covered_matches[1]);
        $lines_total = array_sum($total_matches[1]);
        $percentage = $lines_total > 0 ? ($lines_covered / $lines_total) * 100 : 0;
        
        return [
            'percentage' => $percentage,
            'lines_covered' => $lines_covered,
            'lines_total' => $lines_total
        ];
    }
    
    /**
     * Calculate overall coverage
     */
    private function calculate_overall_coverage($php_coverage, $js_coverage) {
        $total_covered = $php_coverage['lines_covered'] + $js_coverage['lines_covered'];
        $total_lines = $php_coverage['lines_total'] + $js_coverage['lines_total'];
        $percentage = $total_lines > 0 ? ($total_covered / $total_lines) * 100 : 0;
        
        return [
            'percentage' => $percentage,
            'lines_covered' => $total_covered,
            'lines_total' => $total_lines
        ];
    }
    
    /**
     * Generate final coverage report
     */
    private function generate_final_report() {
        echo "=== Final Test Coverage Report ===\n";
        
        $overall = $this->results['coverage']['overall'];
        $php = $this->results['coverage']['php'];
        $js = $this->results['coverage']['javascript'];
        
        echo "Overall Coverage: " . number_format($overall['percentage'], 1) . "%\n";
        echo "  Lines Covered: {$overall['lines_covered']}\n";
        echo "  Total Lines: {$overall['lines_total']}\n";
        echo "\n";
        
        echo "PHP Coverage: " . number_format($php['percentage'], 1) . "%\n";
        echo "  Lines Covered: {$php['lines_covered']}\n";
        echo "  Total Lines: {$php['lines_total']}\n";
        echo "\n";
        
        echo "JavaScript Coverage: " . number_format($js['percentage'], 1) . "%\n";
        echo "  Lines Covered: {$js['lines_covered']}\n";
        echo "  Total Lines: {$js['lines_total']}\n";
        echo "\n";
        
        // Check if coverage goals are met
        $coverage_goal_met = $overall['percentage'] >= 80;
        $php_goal_met = $php['percentage'] >= 80;
        $js_goal_met = $js['percentage'] >= 80;
        
        echo "Coverage Goals (80% minimum):\n";
        echo "  Overall: " . ($coverage_goal_met ? "✓ PASS" : "✗ FAIL") . "\n";
        echo "  PHP: " . ($php_goal_met ? "✓ PASS" : "✗ FAIL") . "\n";
        echo "  JavaScript: " . ($js_goal_met ? "✓ PASS" : "✗ FAIL") . "\n";
        echo "\n";
        
        // Test execution results
        echo "Test Execution Results:\n";
        echo "  PHPUnit: " . ($this->results['phpunit']['success'] ? "✓ PASS" : "✗ FAIL") . "\n";
        echo "  Jest: " . ($this->results['jest']['success'] ? "✓ PASS" : "✗ FAIL") . "\n";
        echo "\n";
        
        // Overall assessment
        $all_tests_passed = $this->results['phpunit']['success'] && $this->results['jest']['success'];
        $all_coverage_met = $coverage_goal_met && $php_goal_met && $js_goal_met;
        
        echo "Overall Assessment: ";
        if ($all_tests_passed && $all_coverage_met) {
            echo "✓ ALL REQUIREMENTS MET\n";
        } else {
            echo "✗ REQUIREMENTS NOT MET\n";
            
            if (!$all_tests_passed) {
                echo "  - Some tests are failing\n";
            }
            if (!$all_coverage_met) {
                echo "  - Coverage goals not achieved\n";
            }
        }
        
        // Save detailed report
        $report_data = [
            'timestamp' => current_time('mysql'),
            'results' => $this->results,
            'goals_met' => [
                'all_tests_passed' => $all_tests_passed,
                'coverage_goal_met' => $coverage_goal_met,
                'overall_success' => $all_tests_passed && $all_coverage_met
            ]
        ];
        
        file_put_contents(
            'tests/coverage/final-report.json',
            json_encode($report_data, JSON_PRETTY_PRINT)
        );
        
        echo "\nDetailed report saved to: tests/coverage/final-report.json\n";
        echo "HTML coverage report available at: tests/coverage/html/index.html\n";
    }
}

// Run coverage tests if called directly
if (php_sapi_name() === 'cli') {
    $runner = new MAS_Coverage_Runner();
    $runner->run();
}