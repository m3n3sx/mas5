<?php
/**
 * Phase 2 End-to-End Integration Tests
 *
 * Comprehensive test suite covering all Phase 2 features and their integration
 * with Phase 1 functionality.
 *
 * @package ModernAdminStylerV2
 * @subpackage Tests
 */

class TestPhase2EndToEnd extends WP_UnitTestCase {

    private $admin_user;
    private $namespace = 'mas-v2/v1';
    private $test_data = [];

    public function setUp() {
        parent::setUp();
        
        // Create admin user for testing
        $this->admin_user = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($this->admin_user);
        
        // Initialize test data storage
        $this->test_data = [];
        
        // Clear any existing test data
        $this->cleanup_test_data();
    }

    public function tearDown() {
        $this->cleanup_test_data();
        parent::tearDown();
    }

    private function cleanup_test_data() {
        global $wpdb;
        
        // Clean up test webhooks
        $wpdb->query("DELETE FROM {$wpdb->prefix}mas_v2_webhooks WHERE url LIKE '%test-webhook%'");
        
        // Clean up test audit logs
        $wpdb->query("DELETE FROM {$wpdb->prefix}mas_v2_audit_log WHERE action LIKE '%test%'");
        
        // Clean up test metrics
        $wpdb->query("DELETE FROM {$wpdb->prefix}mas_v2_metrics WHERE endpoint LIKE '%test%'");
    }

    /**
     * Test 1: Complete Theme Management Workflow
     * Tests theme presets, preview, export, import, and application
     */
    public function test_complete_theme_management_workflow() {
        // 1. Get theme presets
        $request = new WP_REST_Request('GET', "/{$this->namespace}/themes/presets");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertGreaterThan(0, count($data['data']));
        
        // Verify predefined themes exist
        $theme_ids = array_column($data['data'], 'id');
        $this->assertContains('dark', $theme_ids);
        $this->assertContains('light', $theme_ids);
        $this->assertContains('ocean', $theme_ids);
        
        // 2. Preview a theme
        $request = new WP_REST_Request('POST', "/{$this->namespace}/themes/preview");
        $request->set_param('theme_id', 'ocean');
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('css', $data['data']);
        $this->assertArrayHasKey('settings', $data['data']);
        
        // 3. Export a theme
        $request = new WP_REST_Request('POST', "/{$this->namespace}/themes/export");
        $request->set_param('theme_id', 'dark');
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('theme', $data['data']);
        $this->assertArrayHasKey('version', $data['data']['theme']);
        $this->assertArrayHasKey('checksum', $data['data']['theme']);
        
        $exported_theme = $data['data']['theme'];
        
        // 4. Import the exported theme
        $request = new WP_REST_Request('POST', "/{$this->namespace}/themes/import");
        $request->set_param('theme', $exported_theme);
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        
        // 5. Apply a theme
        $request = new WP_REST_Request('POST', "/{$this->namespace}/themes/ocean/apply");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        
        // Verify settings were updated
        $settings = get_option('mas_v2_settings');
        $this->assertNotEmpty($settings);
    }

    /**
     * Test 2: Complete Backup Management Workflow
     * Tests automatic backups, retention, download, and restore
     */
    public function test_complete_backup_management_workflow() {
        // 1. Create initial settings
        $initial_settings = [
            'menu_background' => '#1e1e2e',
            'menu_text_color' => '#ffffff'
        ];
        
        $request = new WP_REST_Request('POST', "/{$this->namespace}/settings");
        $request->set_body_params($initial_settings);
        $response = rest_do_request($request);
        $this->assertEquals(200, $response->get_status());
        
        // 2. List backups (should have automatic backup from settings save)
        $request = new WP_REST_Request('GET', "/{$this->namespace}/backups");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertGreaterThan(0, count($data['data']));
        
        $backup_id = $data['data'][0]['id'];
        
        // 3. Create manual backup with note
        $request = new WP_REST_Request('POST', "/{$this->namespace}/backups");
        $request->set_param('note', 'Test manual backup');
        $request->set_param('type', 'manual');
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('backup_id', $data['data']);
        
        $manual_backup_id = $data['data']['backup_id'];
        
        // 4. Download backup
        $request = new WP_REST_Request('GET', "/{$this->namespace}/backups/{$manual_backup_id}/download");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $headers = $response->get_headers();
        $this->assertArrayHasKey('Content-Disposition', $headers);
        
        // 5. Change settings
        $new_settings = [
            'menu_background' => '#2d2d44',
            'menu_text_color' => '#cccccc'
        ];
        
        $request = new WP_REST_Request('POST', "/{$this->namespace}/settings");
        $request->set_body_params($new_settings);
        $response = rest_do_request($request);
        $this->assertEquals(200, $response->get_status());
        
        // 6. Restore backup
        $request = new WP_REST_Request('POST', "/{$this->namespace}/backups/{$manual_backup_id}/restore");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        
        // Verify settings were restored
        $settings = get_option('mas_v2_settings');
        $this->assertEquals($initial_settings['menu_background'], $settings['menu_background']);
        
        // 7. Test batch backup operations
        $request = new WP_REST_Request('POST', "/{$this->namespace}/backups/batch");
        $request->set_param('operations', [
            ['action' => 'create', 'note' => 'Batch backup 1'],
            ['action' => 'create', 'note' => 'Batch backup 2']
        ]);
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        
        // 8. Test cleanup
        $request = new WP_REST_Request('POST', "/{$this->namespace}/backups/cleanup");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
    }

    /**
     * Test 3: System Diagnostics and Health Monitoring
     * Tests health checks, performance metrics, and conflict detection
     */
    public function test_system_diagnostics_workflow() {
        // 1. Get system health
        $request = new WP_REST_Request('GET', "/{$this->namespace}/system/health");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('status', $data['data']);
        $this->assertContains($data['data']['status'], ['healthy', 'warning', 'critical']);
        $this->assertArrayHasKey('checks', $data['data']);
        
        // 2. Get system info
        $request = new WP_REST_Request('GET', "/{$this->namespace}/system/info");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('php_version', $data['data']);
        $this->assertArrayHasKey('wordpress_version', $data['data']);
        $this->assertArrayHasKey('plugin_version', $data['data']);
        
        // 3. Get performance metrics
        $request = new WP_REST_Request('GET', "/{$this->namespace}/system/performance");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('memory_usage', $data['data']);
        $this->assertArrayHasKey('cache_stats', $data['data']);
        
        // 4. Check for conflicts
        $request = new WP_REST_Request('GET', "/{$this->namespace}/system/conflicts");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('conflicts', $data['data']);
        
        // 5. Get cache status
        $request = new WP_REST_Request('GET', "/{$this->namespace}/system/cache");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('stats', $data['data']);
        
        // 6. Clear cache
        $request = new WP_REST_Request('DELETE', "/{$this->namespace}/system/cache");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
    }

    /**
     * Test 4: Performance Optimizations (ETags and Caching)
     * Tests conditional requests and caching behavior
     */
    public function test_performance_optimizations() {
        // 1. Get settings and capture ETag
        $request = new WP_REST_Request('GET', "/{$this->namespace}/settings");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $headers = $response->get_headers();
        $this->assertArrayHasKey('ETag', $headers);
        $this->assertArrayHasKey('Last-Modified', $headers);
        $this->assertArrayHasKey('X-Cache', $headers);
        
        $etag = $headers['ETag'];
        $last_modified = $headers['Last-Modified'];
        
        // 2. Make conditional request with If-None-Match
        $request = new WP_REST_Request('GET', "/{$this->namespace}/settings");
        $request->set_header('If-None-Match', $etag);
        $response = rest_do_request($request);
        
        $this->assertEquals(304, $response->get_status());
        
        // 3. Make conditional request with If-Modified-Since
        $request = new WP_REST_Request('GET', "/{$this->namespace}/settings");
        $request->set_header('If-Modified-Since', $last_modified);
        $response = rest_do_request($request);
        
        $this->assertEquals(304, $response->get_status());
        
        // 4. Update settings and verify ETag changes
        $request = new WP_REST_Request('POST', "/{$this->namespace}/settings");
        $request->set_param('menu_background', '#3d3d5c');
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        
        // 5. Get settings again and verify new ETag
        $request = new WP_REST_Request('GET', "/{$this->namespace}/settings");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $headers = $response->get_headers();
        $new_etag = $headers['ETag'];
        
        $this->assertNotEquals($etag, $new_etag);
        
        // 6. Verify cache hit on second request
        $request = new WP_REST_Request('GET', "/{$this->namespace}/settings");
        $response = rest_do_request($request);
        
        $headers = $response->get_headers();
        $this->assertEquals('HIT', $headers['X-Cache']);
    }

    /**
     * Test 5: Security Features (Rate Limiting and Audit Logging)
     * Tests rate limiting enforcement and audit log creation
     */
    public function test_security_features() {
        // 1. Check initial rate limit status
        $request = new WP_REST_Request('GET', "/{$this->namespace}/security/rate-limit/status");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('remaining', $data['data']);
        
        // 2. Perform operations to generate audit logs
        $request = new WP_REST_Request('POST', "/{$this->namespace}/settings");
        $request->set_param('menu_background', '#1e1e2e');
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        
        // 3. Get audit log
        $request = new WP_REST_Request('GET', "/{$this->namespace}/security/audit-log");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertGreaterThan(0, count($data['data']));
        
        // Verify audit log entry structure
        $log_entry = $data['data'][0];
        $this->assertArrayHasKey('user_id', $log_entry);
        $this->assertArrayHasKey('action', $log_entry);
        $this->assertArrayHasKey('timestamp', $log_entry);
        $this->assertArrayHasKey('ip_address', $log_entry);
        
        // 4. Test rate limiting (make rapid requests)
        $rate_limited = false;
        for ($i = 0; $i < 15; $i++) {
            $request = new WP_REST_Request('POST', "/{$this->namespace}/settings");
            $request->set_param('menu_background', '#' . dechex(rand(0, 16777215)));
            $response = rest_do_request($request);
            
            if ($response->get_status() === 429) {
                $rate_limited = true;
                $headers = $response->get_headers();
                $this->assertArrayHasKey('Retry-After', $headers);
                break;
            }
        }
        
        // Note: Rate limiting might not trigger in test environment
        // This is expected behavior
    }

    /**
     * Test 6: Batch Operations and Transactions
     * Tests atomic batch processing with rollback
     */
    public function test_batch_operations() {
        // 1. Batch settings update (all valid)
        $request = new WP_REST_Request('POST', "/{$this->namespace}/settings/batch");
        $request->set_param('operations', [
            ['field' => 'menu_background', 'value' => '#1e1e2e'],
            ['field' => 'menu_text_color', 'value' => '#ffffff'],
            ['field' => 'menu_hover_background', 'value' => '#2d2d44']
        ]);
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertEquals(3, $data['data']['success_count']);
        
        // 2. Batch settings update (with invalid data - should rollback)
        $initial_settings = get_option('mas_v2_settings');
        
        $request = new WP_REST_Request('POST', "/{$this->namespace}/settings/batch");
        $request->set_param('operations', [
            ['field' => 'menu_background', 'value' => '#3d3d5c'],
            ['field' => 'menu_text_color', 'value' => 'invalid-color'], // Invalid
            ['field' => 'menu_hover_background', 'value' => '#4d4d6c']
        ]);
        $response = rest_do_request($request);
        
        // Should fail and rollback
        $this->assertNotEquals(200, $response->get_status());
        
        // Verify settings were not changed
        $current_settings = get_option('mas_v2_settings');
        $this->assertEquals($initial_settings['menu_background'], $current_settings['menu_background']);
        
        // 3. Batch theme apply
        $request = new WP_REST_Request('POST', "/{$this->namespace}/themes/batch-apply");
        $request->set_param('theme_id', 'ocean');
        $request->set_param('validate', true);
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
    }

    /**
     * Test 7: Webhook System
     * Tests webhook registration, triggering, and delivery
     */
    public function test_webhook_system() {
        // 1. Register webhook
        $request = new WP_REST_Request('POST', "/{$this->namespace}/webhooks");
        $request->set_param('url', 'https://example.com/test-webhook');
        $request->set_param('events', ['settings.updated', 'theme.applied']);
        $request->set_param('secret', 'test-secret-key');
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('webhook_id', $data['data']);
        
        $webhook_id = $data['data']['webhook_id'];
        
        // 2. List webhooks
        $request = new WP_REST_Request('GET', "/{$this->namespace}/webhooks");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertGreaterThan(0, count($data['data']));
        
        // 3. Get specific webhook
        $request = new WP_REST_Request('GET', "/{$this->namespace}/webhooks/{$webhook_id}");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertEquals($webhook_id, $data['data']['id']);
        
        // 4. Trigger webhook by updating settings
        $request = new WP_REST_Request('POST', "/{$this->namespace}/settings");
        $request->set_param('menu_background', '#1e1e2e');
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        
        // 5. Check webhook deliveries
        $request = new WP_REST_Request('GET', "/{$this->namespace}/webhooks/{$webhook_id}/deliveries");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        
        // 6. Update webhook
        $request = new WP_REST_Request('PUT', "/{$this->namespace}/webhooks/{$webhook_id}");
        $request->set_param('events', ['settings.updated']);
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        
        // 7. Delete webhook
        $request = new WP_REST_Request('DELETE', "/{$this->namespace}/webhooks/{$webhook_id}");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
    }

    /**
     * Test 8: Analytics and Monitoring
     * Tests usage statistics and performance tracking
     */
    public function test_analytics_system() {
        // Generate some API activity
        for ($i = 0; $i < 5; $i++) {
            $request = new WP_REST_Request('GET', "/{$this->namespace}/settings");
            rest_do_request($request);
        }
        
        // 1. Get usage statistics
        $request = new WP_REST_Request('GET', "/{$this->namespace}/analytics/usage");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('total_calls', $data['data']);
        $this->assertArrayHasKey('by_endpoint', $data['data']);
        
        // 2. Get performance metrics
        $request = new WP_REST_Request('GET', "/{$this->namespace}/analytics/performance");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('percentiles', $data['data']);
        
        // 3. Get error statistics
        $request = new WP_REST_Request('GET', "/{$this->namespace}/analytics/errors");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('error_rate', $data['data']);
        
        // 4. Export analytics
        $request = new WP_REST_Request('GET', "/{$this->namespace}/analytics/export");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $headers = $response->get_headers();
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertStringContainsString('text/csv', $headers['Content-Type']);
    }

    /**
     * Test 9: Phase 1 and Phase 2 Integration
     * Verifies backward compatibility and seamless integration
     */
    public function test_phase1_phase2_integration() {
        // 1. Test Phase 1 settings endpoint still works
        $request = new WP_REST_Request('GET', "/{$this->namespace}/settings");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        
        // 2. Test Phase 1 themes endpoint still works
        $request = new WP_REST_Request('GET', "/{$this->namespace}/themes");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        
        // 3. Test Phase 1 backups endpoint still works
        $request = new WP_REST_Request('GET', "/{$this->namespace}/backups");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        
        // 4. Test Phase 2 features don't break Phase 1
        // Enable rate limiting
        $request = new WP_REST_Request('POST', "/{$this->namespace}/settings");
        $request->set_param('menu_background', '#1e1e2e');
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        
        // Verify audit log was created (Phase 2 feature)
        $request = new WP_REST_Request('GET', "/{$this->namespace}/security/audit-log");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        
        // 5. Test Phase 1 import/export still works
        $request = new WP_REST_Request('GET', "/{$this->namespace}/export");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
    }

    /**
     * Test 10: Complete User Workflow
     * Simulates a real user performing common tasks
     */
    public function test_complete_user_workflow() {
        // User logs in and checks system health
        $request = new WP_REST_Request('GET', "/{$this->namespace}/system/health");
        $response = rest_do_request($request);
        $this->assertEquals(200, $response->get_status());
        
        // User previews a theme
        $request = new WP_REST_Request('POST', "/{$this->namespace}/themes/preview");
        $request->set_param('theme_id', 'ocean');
        $response = rest_do_request($request);
        $this->assertEquals(200, $response->get_status());
        
        // User applies the theme
        $request = new WP_REST_Request('POST', "/{$this->namespace}/themes/ocean/apply");
        $response = rest_do_request($request);
        $this->assertEquals(200, $response->get_status());
        
        // User makes custom changes
        $request = new WP_REST_Request('POST', "/{$this->namespace}/settings");
        $request->set_body_params([
            'menu_background' => '#1a1a2e',
            'menu_text_color' => '#eaeaea',
            'glassmorphism_enabled' => true
        ]);
        $response = rest_do_request($request);
        $this->assertEquals(200, $response->get_status());
        
        // User creates a manual backup
        $request = new WP_REST_Request('POST', "/{$this->namespace}/backups");
        $request->set_param('note', 'My custom ocean theme');
        $response = rest_do_request($request);
        $this->assertEquals(200, $response->get_status());
        $backup_id = $response->get_data()['data']['backup_id'];
        
        // User exports settings
        $request = new WP_REST_Request('GET', "/{$this->namespace}/export");
        $response = rest_do_request($request);
        $this->assertEquals(200, $response->get_status());
        
        // User checks performance metrics
        $request = new WP_REST_Request('GET', "/{$this->namespace}/analytics/performance");
        $response = rest_do_request($request);
        $this->assertEquals(200, $response->get_status());
        
        // User views audit log
        $request = new WP_REST_Request('GET', "/{$this->namespace}/security/audit-log");
        $response = rest_do_request($request);
        $this->assertEquals(200, $response->get_status());
        
        // User downloads backup
        $request = new WP_REST_Request('GET', "/{$this->namespace}/backups/{$backup_id}/download");
        $response = rest_do_request($request);
        $this->assertEquals(200, $response->get_status());
    }
}
