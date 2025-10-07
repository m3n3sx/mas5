<?php
/**
 * Phase 2 End-to-End Verification Script
 * 
 * Comprehensive verification of all Phase 2 features and integration with Phase 1.
 * This script can be run directly without PHPUnit.
 * 
 * Usage: php verify-phase2-e2e-complete.php
 */

// Load WordPress
require_once dirname(__FILE__) . '/modern-admin-styler-v2.php';

class Phase2E2EVerification {
    
    private $results = [];
    private $passed = 0;
    private $failed = 0;
    
    public function run() {
        echo "========================================\n";
        echo "Phase 2 End-to-End Verification\n";
        echo "========================================\n\n";
        
        $this->test_theme_management_workflow();
        $this->test_backup_management_workflow();
        $this->test_system_diagnostics();
        $this->test_performance_features();
        $this->test_security_features();
        $this->test_batch_operations();
        $this->test_webhook_system();
        $this->test_analytics_system();
        $this->test_phase1_integration();
        $this->test_upgrade_path();
        
        $this->print_summary();
    }
    
    private function test_theme_management_workflow() {
        echo "Testing Theme Management Workflow...\n";
        
        // Test theme presets endpoint
        $this->verify_endpoint_exists('GET', '/mas-v2/v1/themes/presets', 'Theme presets endpoint');
        
        // Test theme preview endpoint
        $this->verify_endpoint_exists('POST', '/mas-v2/v1/themes/preview', 'Theme preview endpoint');
        
        // Test theme export endpoint
        $this->verify_endpoint_exists('POST', '/mas-v2/v1/themes/export', 'Theme export endpoint');
        
        // Test theme import endpoint
        $this->verify_endpoint_exists('POST', '/mas-v2/v1/themes/import', 'Theme import endpoint');
        
        // Verify theme preset service exists
        $this->verify_class_exists('MAS_Theme_Preset_Service', 'Theme preset service');
        
        // Verify predefined themes
        if (class_exists('MAS_Theme_Preset_Service')) {
            $service = new MAS_Theme_Preset_Service();
            $presets = $service->get_predefined_themes();
            $this->assert(count($presets) >= 6, 'At least 6 predefined themes exist', count($presets) . ' themes found');
            
            $theme_ids = array_column($presets, 'id');
            $this->assert(in_array('dark', $theme_ids), 'Dark theme exists');
            $this->assert(in_array('light', $theme_ids), 'Light theme exists');
            $this->assert(in_array('ocean', $theme_ids), 'Ocean theme exists');
        }
        
        echo "\n";
    }
    
    private function test_backup_management_workflow() {
        echo "Testing Backup Management Workflow...\n";
        
        // Verify backup retention service exists
        $this->verify_class_exists('MAS_Backup_Retention_Service', 'Backup retention service');
        
        // Test backup endpoints
        $this->verify_endpoint_exists('GET', '/mas-v2/v1/backups', 'List backups endpoint');
        $this->verify_endpoint_exists('POST', '/mas-v2/v1/backups', 'Create backup endpoint');
        $this->verify_endpoint_exists('GET', '/mas-v2/v1/backups/1/download', 'Download backup endpoint');
        $this->verify_endpoint_exists('POST', '/mas-v2/v1/backups/batch', 'Batch backup endpoint');
        $this->verify_endpoint_exists('POST', '/mas-v2/v1/backups/cleanup', 'Cleanup backup endpoint');
        
        // Verify backup metadata support
        if (class_exists('MAS_Backup_Retention_Service')) {
            $service = new MAS_Backup_Retention_Service();
            $reflection = new ReflectionClass($service);
            $this->assert($reflection->hasMethod('create_backup'), 'create_backup method exists');
            $this->assert($reflection->hasMethod('cleanup_old_backups'), 'cleanup_old_backups method exists');
            $this->assert($reflection->hasMethod('download_backup'), 'download_backup method exists');
        }
        
        echo "\n";
    }
    
    private function test_system_diagnostics() {
        echo "Testing System Diagnostics...\n";
        
        // Verify system health service exists
        $this->verify_class_exists('MAS_System_Health_Service', 'System health service');
        
        // Test diagnostics endpoints
        $this->verify_endpoint_exists('GET', '/mas-v2/v1/system/health', 'System health endpoint');
        $this->verify_endpoint_exists('GET', '/mas-v2/v1/system/info', 'System info endpoint');
        $this->verify_endpoint_exists('GET', '/mas-v2/v1/system/performance', 'Performance metrics endpoint');
        $this->verify_endpoint_exists('GET', '/mas-v2/v1/system/conflicts', 'Conflict detection endpoint');
        $this->verify_endpoint_exists('GET', '/mas-v2/v1/system/cache', 'Cache status endpoint');
        $this->verify_endpoint_exists('DELETE', '/mas-v2/v1/system/cache', 'Clear cache endpoint');
        
        // Verify health check methods
        if (class_exists('MAS_System_Health_Service')) {
            $service = new MAS_System_Health_Service();
            $reflection = new ReflectionClass($service);
            $this->assert($reflection->hasMethod('get_health_status'), 'get_health_status method exists');
            $this->assert($reflection->hasMethod('check_php_version'), 'check_php_version method exists');
            $this->assert($reflection->hasMethod('check_conflicts'), 'check_conflicts method exists');
        }
        
        echo "\n";
    }
    
    private function test_performance_features() {
        echo "Testing Performance Features...\n";
        
        // Verify cache service exists
        $this->verify_class_exists('MAS_Cache_Service', 'Cache service');
        
        // Verify settings service has ETag support
        if (class_exists('MAS_Settings_Service')) {
            $service = new MAS_Settings_Service();
            $reflection = new ReflectionClass($service);
            $this->assert($reflection->hasMethod('get_last_modified_time'), 'get_last_modified_time method exists');
        }
        
        // Verify settings controller has ETag support
        if (class_exists('MAS_Settings_Controller')) {
            $controller = new MAS_Settings_Controller();
            $reflection = new ReflectionClass($controller);
            // ETag support is typically in the get_settings method
            $this->assert($reflection->hasMethod('get_settings'), 'get_settings method exists');
        }
        
        // Verify database optimizer exists
        $this->verify_class_exists('MAS_Database_Optimizer', 'Database optimizer');
        
        echo "\n";
    }
    
    private function test_security_features() {
        echo "Testing Security Features...\n";
        
        // Verify rate limiter service exists
        $this->verify_class_exists('MAS_Rate_Limiter_Service', 'Rate limiter service');
        
        // Verify security logger service exists
        $this->verify_class_exists('MAS_Security_Logger_Service', 'Security logger service');
        
        // Test security endpoints
        $this->verify_endpoint_exists('GET', '/mas-v2/v1/security/audit-log', 'Audit log endpoint');
        $this->verify_endpoint_exists('GET', '/mas-v2/v1/security/rate-limit/status', 'Rate limit status endpoint');
        
        // Verify rate limiter methods
        if (class_exists('MAS_Rate_Limiter_Service')) {
            $service = new MAS_Rate_Limiter_Service();
            $reflection = new ReflectionClass($service);
            $this->assert($reflection->hasMethod('check_rate_limit'), 'check_rate_limit method exists');
            $this->assert($reflection->hasMethod('get_status'), 'get_status method exists');
        }
        
        // Verify audit log database table exists
        global $wpdb;
        $table_name = $wpdb->prefix . 'mas_v2_audit_log';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        $this->assert($table_exists, 'Audit log table exists');
        
        echo "\n";
    }
    
    private function test_batch_operations() {
        echo "Testing Batch Operations...\n";
        
        // Verify transaction service exists
        $this->verify_class_exists('MAS_Transaction_Service', 'Transaction service');
        
        // Verify batch controller exists
        $this->verify_class_exists('MAS_Batch_Controller', 'Batch controller');
        
        // Test batch endpoints
        $this->verify_endpoint_exists('POST', '/mas-v2/v1/settings/batch', 'Batch settings endpoint');
        $this->verify_endpoint_exists('POST', '/mas-v2/v1/backups/batch', 'Batch backups endpoint');
        $this->verify_endpoint_exists('POST', '/mas-v2/v1/themes/batch-apply', 'Batch theme apply endpoint');
        
        // Verify transaction methods
        if (class_exists('MAS_Transaction_Service')) {
            $service = new MAS_Transaction_Service();
            $reflection = new ReflectionClass($service);
            $this->assert($reflection->hasMethod('begin_transaction'), 'begin_transaction method exists');
            $this->assert($reflection->hasMethod('commit'), 'commit method exists');
            $this->assert($reflection->hasMethod('rollback'), 'rollback method exists');
        }
        
        echo "\n";
    }
    
    private function test_webhook_system() {
        echo "Testing Webhook System...\n";
        
        // Verify webhook service exists
        $this->verify_class_exists('MAS_Webhook_Service', 'Webhook service');
        
        // Test webhook endpoints
        $this->verify_endpoint_exists('GET', '/mas-v2/v1/webhooks', 'List webhooks endpoint');
        $this->verify_endpoint_exists('POST', '/mas-v2/v1/webhooks', 'Create webhook endpoint');
        $this->verify_endpoint_exists('GET', '/mas-v2/v1/webhooks/1', 'Get webhook endpoint');
        $this->verify_endpoint_exists('PUT', '/mas-v2/v1/webhooks/1', 'Update webhook endpoint');
        $this->verify_endpoint_exists('DELETE', '/mas-v2/v1/webhooks/1', 'Delete webhook endpoint');
        $this->verify_endpoint_exists('GET', '/mas-v2/v1/webhooks/1/deliveries', 'Webhook deliveries endpoint');
        
        // Verify webhook database tables exist
        global $wpdb;
        $webhooks_table = $wpdb->prefix . 'mas_v2_webhooks';
        $deliveries_table = $wpdb->prefix . 'mas_v2_webhook_deliveries';
        
        $this->assert($wpdb->get_var("SHOW TABLES LIKE '$webhooks_table'") === $webhooks_table, 'Webhooks table exists');
        $this->assert($wpdb->get_var("SHOW TABLES LIKE '$deliveries_table'") === $deliveries_table, 'Webhook deliveries table exists');
        
        echo "\n";
    }
    
    private function test_analytics_system() {
        echo "Testing Analytics System...\n";
        
        // Verify analytics service exists
        $this->verify_class_exists('MAS_Analytics_Service', 'Analytics service');
        
        // Test analytics endpoints
        $this->verify_endpoint_exists('GET', '/mas-v2/v1/analytics/usage', 'Usage analytics endpoint');
        $this->verify_endpoint_exists('GET', '/mas-v2/v1/analytics/performance', 'Performance analytics endpoint');
        $this->verify_endpoint_exists('GET', '/mas-v2/v1/analytics/errors', 'Error analytics endpoint');
        $this->verify_endpoint_exists('GET', '/mas-v2/v1/analytics/export', 'Export analytics endpoint');
        
        // Verify metrics database table exists
        global $wpdb;
        $table_name = $wpdb->prefix . 'mas_v2_metrics';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        $this->assert($table_exists, 'Metrics table exists');
        
        echo "\n";
    }
    
    private function test_phase1_integration() {
        echo "Testing Phase 1 Integration...\n";
        
        // Verify Phase 1 endpoints still exist
        $this->verify_endpoint_exists('GET', '/mas-v2/v1/settings', 'Phase 1 settings endpoint');
        $this->verify_endpoint_exists('POST', '/mas-v2/v1/settings', 'Phase 1 save settings endpoint');
        $this->verify_endpoint_exists('GET', '/mas-v2/v1/themes', 'Phase 1 themes endpoint');
        $this->verify_endpoint_exists('GET', '/mas-v2/v1/backups', 'Phase 1 backups endpoint');
        $this->verify_endpoint_exists('GET', '/mas-v2/v1/export', 'Phase 1 export endpoint');
        $this->verify_endpoint_exists('POST', '/mas-v2/v1/import', 'Phase 1 import endpoint');
        $this->verify_endpoint_exists('POST', '/mas-v2/v1/preview', 'Phase 1 preview endpoint');
        $this->verify_endpoint_exists('GET', '/mas-v2/v1/diagnostics', 'Phase 1 diagnostics endpoint');
        
        // Verify Phase 1 services still exist
        $this->verify_class_exists('MAS_Settings_Service', 'Phase 1 settings service');
        $this->verify_class_exists('MAS_Theme_Service', 'Phase 1 theme service');
        $this->verify_class_exists('MAS_Backup_Service', 'Phase 1 backup service');
        $this->verify_class_exists('MAS_CSS_Generator_Service', 'Phase 1 CSS generator service');
        
        echo "\n";
    }
    
    private function test_upgrade_path() {
        echo "Testing Upgrade Path...\n";
        
        // Verify database migration system exists
        $this->verify_class_exists('MAS_Database_Schema', 'Database schema class');
        $this->verify_class_exists('MAS_Migration_Runner', 'Migration runner class');
        
        // Verify version manager exists
        $this->verify_class_exists('MAS_Version_Manager', 'Version manager');
        
        // Verify deprecation service exists
        $this->verify_class_exists('MAS_Deprecation_Service', 'Deprecation service');
        
        // Check plugin version
        $plugin_data = get_file_data(dirname(__FILE__) . '/modern-admin-styler-v2.php', ['Version' => 'Version']);
        $version = $plugin_data['Version'];
        $this->assert(!empty($version), 'Plugin version is set', "Version: $version");
        
        echo "\n";
    }
    
    private function verify_endpoint_exists($method, $route, $description) {
        $server = rest_get_server();
        $routes = $server->get_routes();
        
        $exists = isset($routes[$route]);
        if ($exists && isset($routes[$route][0]['methods'])) {
            $methods = $routes[$route][0]['methods'];
            $method_exists = isset($methods[$method]) || isset($methods['GET,POST,PUT,DELETE,PATCH']);
            $this->assert($method_exists, $description);
        } else {
            $this->assert($exists, $description);
        }
    }
    
    private function verify_class_exists($class_name, $description) {
        $exists = class_exists($class_name);
        $this->assert($exists, $description);
    }
    
    private function assert($condition, $description, $details = '') {
        if ($condition) {
            echo "  ✓ $description";
            if ($details) {
                echo " ($details)";
            }
            echo "\n";
            $this->passed++;
        } else {
            echo "  ✗ $description";
            if ($details) {
                echo " ($details)";
            }
            echo "\n";
            $this->failed++;
        }
        
        $this->results[] = [
            'passed' => $condition,
            'description' => $description,
            'details' => $details
        ];
    }
    
    private function print_summary() {
        echo "\n========================================\n";
        echo "Verification Summary\n";
        echo "========================================\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        echo "Total:  " . ($this->passed + $this->failed) . "\n";
        echo "\n";
        
        if ($this->failed === 0) {
            echo "✓ All verifications passed!\n";
            echo "Phase 2 is ready for release.\n";
        } else {
            echo "✗ Some verifications failed.\n";
            echo "Please review the failures above.\n";
        }
        
        echo "\n";
    }
}

// Run verification
$verification = new Phase2E2EVerification();
$verification->run();
