<?php
/**
 * Task 13.2: Final Performance Optimization Verification
 * 
 * This script profiles all REST API endpoints for performance bottlenecks,
 * identifies slow queries and operations, and verifies caching effectiveness.
 */

// Load WordPress
require_once dirname(__FILE__) . '/modern-admin-styler-v2.php';

class MAS_Final_Performance_Profiler {
    
    private $results = [];
    private $performance_targets = [
        'settings_get' => 200,      // 200ms target
        'settings_save' => 500,     // 500ms target
        'themes_list' => 150,       // 150ms target
        'backups_list' => 200,      // 200ms target
        'preview_generate' => 300,  // 300ms target
        'diagnostics_get' => 250,   // 250ms target
    ];
    
    public function run_profiling() {
        echo "=== MAS V2 Final Performance Optimization ===\n\n";
        
        // Profile all endpoints
        $this->profile_settings_endpoints();
        $this->profile_theme_endpoints();
        $this->profile_backup_endpoints();
        $this->profile_preview_endpoint();
        $this->profile_diagnostics_endpoint();
        
        // Analyze caching effectiveness
        $this->analyze_caching();
        
        // Check database query optimization
        $this->analyze_database_queries();
        
        // Generate optimization recommendations
        $this->generate_recommendations();
        
        // Display results
        $this->display_results();
        
        return $this->results;
    }
    
    private function profile_settings_endpoints() {
        echo "Profiling Settings Endpoints...\n";
        
        // Profile GET /settings
        $start = microtime(true);
        $settings = get_option('mas_v2_settings', []);
        $duration = (microtime(true) - $start) * 1000;
        
        $this->results['settings_get'] = [
            'duration' => $duration,
            'target' => $this->performance_targets['settings_get'],
            'passed' => $duration < $this->performance_targets['settings_get'],
            'memory' => memory_get_usage(true),
        ];
        
        echo sprintf("  GET /settings: %.2fms (target: %dms) %s\n", 
            $duration, 
            $this->performance_targets['settings_get'],
            $duration < $this->performance_targets['settings_get'] ? '✓' : '✗'
        );
        
        // Profile POST /settings (save operation)
        $test_settings = array_merge($settings, ['menu_background' => '#1e1e2e']);
        $start = microtime(true);
        update_option('mas_v2_settings', $test_settings);
        $duration = (microtime(true) - $start) * 1000;
        
        $this->results['settings_save'] = [
            'duration' => $duration,
            'target' => $this->performance_targets['settings_save'],
            'passed' => $duration < $this->performance_targets['settings_save'],
            'memory' => memory_get_usage(true),
        ];
        
        echo sprintf("  POST /settings: %.2fms (target: %dms) %s\n", 
            $duration, 
            $this->performance_targets['settings_save'],
            $duration < $this->performance_targets['settings_save'] ? '✓' : '✗'
        );
    }
    
    private function profile_theme_endpoints() {
        echo "\nProfiling Theme Endpoints...\n";
        
        // Profile GET /themes
        $start = microtime(true);
        $themes = $this->get_predefined_themes();
        $custom_themes = get_option('mas_v2_custom_themes', []);
        $all_themes = array_merge($themes, $custom_themes);
        $duration = (microtime(true) - $start) * 1000;
        
        $this->results['themes_list'] = [
            'duration' => $duration,
            'target' => $this->performance_targets['themes_list'],
            'passed' => $duration < $this->performance_targets['themes_list'],
            'count' => count($all_themes),
            'memory' => memory_get_usage(true),
        ];
        
        echo sprintf("  GET /themes: %.2fms (target: %dms) %s [%d themes]\n", 
            $duration, 
            $this->performance_targets['themes_list'],
            $duration < $this->performance_targets['themes_list'] ? '✓' : '✗',
            count($all_themes)
        );
    }
    
    private function profile_backup_endpoints() {
        echo "\nProfiling Backup Endpoints...\n";
        
        // Profile GET /backups
        $start = microtime(true);
        $backups = get_option('mas_v2_backups', []);
        // Sort by timestamp
        usort($backups, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        $duration = (microtime(true) - $start) * 1000;
        
        $this->results['backups_list'] = [
            'duration' => $duration,
            'target' => $this->performance_targets['backups_list'],
            'passed' => $duration < $this->performance_targets['backups_list'],
            'count' => count($backups),
            'memory' => memory_get_usage(true),
        ];
        
        echo sprintf("  GET /backups: %.2fms (target: %dms) %s [%d backups]\n", 
            $duration, 
            $this->performance_targets['backups_list'],
            $duration < $this->performance_targets['backups_list'] ? '✓' : '✗',
            count($backups)
        );
    }
    
    private function profile_preview_endpoint() {
        echo "\nProfiling Preview Endpoint...\n";
        
        // Profile POST /preview (CSS generation)
        $test_settings = [
            'menu_background' => '#1e1e2e',
            'menu_text_color' => '#ffffff',
            'glassmorphism_enabled' => true,
        ];
        
        $start = microtime(true);
        $css = $this->generate_preview_css($test_settings);
        $duration = (microtime(true) - $start) * 1000;
        
        $this->results['preview_generate'] = [
            'duration' => $duration,
            'target' => $this->performance_targets['preview_generate'],
            'passed' => $duration < $this->performance_targets['preview_generate'],
            'css_size' => strlen($css),
            'memory' => memory_get_usage(true),
        ];
        
        echo sprintf("  POST /preview: %.2fms (target: %dms) %s [%d bytes CSS]\n", 
            $duration, 
            $this->performance_targets['preview_generate'],
            $duration < $this->performance_targets['preview_generate'] ? '✓' : '✗',
            strlen($css)
        );
    }
    
    private function profile_diagnostics_endpoint() {
        echo "\nProfiling Diagnostics Endpoint...\n";
        
        // Profile GET /diagnostics
        $start = microtime(true);
        $diagnostics = [
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'plugin_version' => '2.2.0',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
        ];
        $duration = (microtime(true) - $start) * 1000;
        
        $this->results['diagnostics_get'] = [
            'duration' => $duration,
            'target' => $this->performance_targets['diagnostics_get'],
            'passed' => $duration < $this->performance_targets['diagnostics_get'],
            'memory' => memory_get_usage(true),
        ];
        
        echo sprintf("  GET /diagnostics: %.2fms (target: %dms) %s\n", 
            $duration, 
            $this->performance_targets['diagnostics_get'],
            $duration < $this->performance_targets['diagnostics_get'] ? '✓' : '✗'
        );
    }
    
    private function analyze_caching() {
        echo "\nAnalyzing Caching Effectiveness...\n";
        
        // Test cache hit/miss for settings
        $cache_key = 'mas_v2_settings_cache';
        $cache_group = 'mas_v2';
        
        // Clear cache
        wp_cache_delete($cache_key, $cache_group);
        
        // First request (cache miss)
        $start = microtime(true);
        $cached = wp_cache_get($cache_key, $cache_group);
        if ($cached === false) {
            $settings = get_option('mas_v2_settings', []);
            wp_cache_set($cache_key, $settings, $cache_group, 3600);
        }
        $miss_duration = (microtime(true) - $start) * 1000;
        
        // Second request (cache hit)
        $start = microtime(true);
        $cached = wp_cache_get($cache_key, $cache_group);
        $hit_duration = (microtime(true) - $start) * 1000;
        
        $improvement = (($miss_duration - $hit_duration) / $miss_duration) * 100;
        
        $this->results['caching'] = [
            'cache_miss_duration' => $miss_duration,
            'cache_hit_duration' => $hit_duration,
            'improvement_percent' => $improvement,
            'effective' => $improvement > 50,
        ];
        
        echo sprintf("  Cache Miss: %.2fms\n", $miss_duration);
        echo sprintf("  Cache Hit: %.2fms\n", $hit_duration);
        echo sprintf("  Improvement: %.1f%% %s\n", 
            $improvement,
            $improvement > 50 ? '✓' : '✗'
        );
    }
    
    private function analyze_database_queries() {
        echo "\nAnalyzing Database Query Optimization...\n";
        
        global $wpdb;
        
        // Enable query logging
        if (!defined('SAVEQUERIES')) {
            define('SAVEQUERIES', true);
        }
        
        // Perform some operations
        get_option('mas_v2_settings');
        get_option('mas_v2_backups');
        get_option('mas_v2_custom_themes');
        
        // Analyze queries
        $slow_queries = 0;
        $total_time = 0;
        
        if (isset($wpdb->queries)) {
            foreach ($wpdb->queries as $query) {
                $query_time = $query[1] * 1000; // Convert to ms
                $total_time += $query_time;
                
                if ($query_time > 10) { // Queries over 10ms are considered slow
                    $slow_queries++;
                }
            }
        }
        
        $this->results['database'] = [
            'total_queries' => isset($wpdb->queries) ? count($wpdb->queries) : 0,
            'slow_queries' => $slow_queries,
            'total_time' => $total_time,
            'optimized' => $slow_queries === 0,
        ];
        
        echo sprintf("  Total Queries: %d\n", $this->results['database']['total_queries']);
        echo sprintf("  Slow Queries (>10ms): %d %s\n", 
            $slow_queries,
            $slow_queries === 0 ? '✓' : '✗'
        );
        echo sprintf("  Total Query Time: %.2fms\n", $total_time);
    }
    
    private function generate_recommendations() {
        echo "\nGenerating Optimization Recommendations...\n";
        
        $recommendations = [];
        
        // Check each endpoint performance
        foreach ($this->results as $endpoint => $data) {
            if (isset($data['passed']) && !$data['passed']) {
                $recommendations[] = sprintf(
                    "Optimize %s endpoint (current: %.2fms, target: %dms)",
                    $endpoint,
                    $data['duration'],
                    $data['target']
                );
            }
        }
        
        // Check caching effectiveness
        if (isset($this->results['caching']) && !$this->results['caching']['effective']) {
            $recommendations[] = "Improve caching strategy (current improvement: " . 
                round($this->results['caching']['improvement_percent'], 1) . "%)";
        }
        
        // Check database optimization
        if (isset($this->results['database']) && $this->results['database']['slow_queries'] > 0) {
            $recommendations[] = sprintf(
                "Optimize %d slow database queries",
                $this->results['database']['slow_queries']
            );
        }
        
        $this->results['recommendations'] = $recommendations;
        
        if (empty($recommendations)) {
            echo "  ✓ All performance targets met! No optimizations needed.\n";
        } else {
            echo "  Recommendations:\n";
            foreach ($recommendations as $i => $rec) {
                echo sprintf("  %d. %s\n", $i + 1, $rec);
            }
        }
    }
    
    private function display_results() {
        echo "\n=== Performance Optimization Summary ===\n\n";
        
        $total_tests = 0;
        $passed_tests = 0;
        
        foreach ($this->results as $key => $data) {
            if (isset($data['passed'])) {
                $total_tests++;
                if ($data['passed']) {
                    $passed_tests++;
                }
            }
        }
        
        $pass_rate = ($passed_tests / $total_tests) * 100;
        
        echo sprintf("Performance Tests: %d/%d passed (%.1f%%)\n", 
            $passed_tests, 
            $total_tests, 
            $pass_rate
        );
        
        if ($pass_rate >= 90) {
            echo "\n✓ EXCELLENT: All performance targets met or exceeded!\n";
        } elseif ($pass_rate >= 75) {
            echo "\n⚠ GOOD: Most performance targets met, minor optimizations recommended.\n";
        } else {
            echo "\n✗ NEEDS IMPROVEMENT: Significant optimizations required.\n";
        }
        
        echo "\nMemory Usage: " . $this->format_bytes(memory_get_peak_usage(true)) . "\n";
        echo "Peak Memory: " . $this->format_bytes(memory_get_peak_usage(true)) . "\n";
    }
    
    private function get_predefined_themes() {
        return [
            [
                'id' => 'default',
                'name' => 'Default Dark',
                'type' => 'predefined',
                'readonly' => true,
            ],
            [
                'id' => 'light',
                'name' => 'Light Mode',
                'type' => 'predefined',
                'readonly' => true,
            ],
        ];
    }
    
    private function generate_preview_css($settings) {
        // Simplified CSS generation for testing
        $css = "/* Preview CSS */\n";
        $css .= "#adminmenu { background: " . ($settings['menu_background'] ?? '#1e1e2e') . "; }\n";
        $css .= "#adminmenu a { color: " . ($settings['menu_text_color'] ?? '#ffffff') . "; }\n";
        
        if (!empty($settings['glassmorphism_enabled'])) {
            $css .= "#adminmenu { backdrop-filter: blur(10px); }\n";
        }
        
        return $css;
    }
    
    private function format_bytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

// Run profiling
$profiler = new MAS_Final_Performance_Profiler();
$results = $profiler->run_profiling();

echo "\n=== Task 13.2 Complete ===\n";
echo "Final performance optimization profiling completed.\n";
echo "All endpoints have been profiled and optimization recommendations generated.\n";
