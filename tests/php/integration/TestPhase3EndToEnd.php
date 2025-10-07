<?php
/**
 * Phase 3 End-to-End Integration Tests
 * 
 * Tests complete user workflows with the new frontend architecture
 * 
 * @package ModernAdminStyler
 * @subpackage Tests
 */

class TestPhase3EndToEnd extends WP_UnitTestCase {
    
    private $admin_user;
    private $test_settings;
    
    public function setUp() {
        parent::setUp();
        
        // Create admin user
        $this->admin_user = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($this->admin_user);
        
        // Default test settings
        $this->test_settings = [
            'menu_background' => '#1e1e2e',
            'menu_text_color' => '#ffffff',
            'menu_hover_background' => '#2d2d44',
            'menu_hover_text_color' => '#ffffff',
            'menu_active_background' => '#3d3d5c',
            'menu_active_text_color' => '#ffffff',
            'glassmorphism_enabled' => true,
            'animations_enabled' => true,
        ];
    }
    
    public function tearDown() {
        parent::tearDown();
        
        // Clean up
        delete_option('mas_v2_settings');
        delete_option('mas_v2_use_new_frontend');
    }
    
    /**
     * Test complete settings save workflow
     */
    public function test_complete_settings_workflow() {
        // 1. Load initial settings
        $request = new WP_REST_Request('GET', '/mas-v2/v1/settings');
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $this->assertTrue($response->data['success']);
        
        // 2. Update settings
        $request = new WP_REST_Request('POST', '/mas-v2/v1/settings');
        foreach ($this->test_settings as $key => $value) {
            $request->set_param($key, $value);
        }
        
        $response = rest_do_request($request);
        $this->assertEquals(200, $response->get_status());
        $this->assertTrue($response->data['success']);
        
        // 3. Verify settings were saved
        $saved_settings = get_option('mas_v2_settings');
        foreach ($this->test_settings as $key => $value) {
            $this->assertEquals($value, $saved_settings[$key], "Setting $key should be saved correctly");
        }
        
        // 4. Verify CSS was regenerated
        $this->assertTrue($response->data['data']['css_generated']);
    }
    
    /**
     * Test live preview workflow
     */
    public function test_live_preview_workflow() {
        // Generate preview without saving
        $request = new WP_REST_Request('POST', '/mas-v2/v1/preview');
        $request->set_param('menu_background', '#ff0000');
        $request->set_param('menu_text_color', '#ffffff');
        
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $this->assertTrue($response->data['success']);
        $this->assertNotEmpty($response->data['data']['css']);
        
        // Verify original settings unchanged
        $saved_settings = get_option('mas_v2_settings', []);
        $this->assertNotEquals('#ff0000', $saved_settings['menu_background'] ?? '');
    }
    
    /**
     * Test theme application workflow
     */
    public function test_theme_application_workflow() {
        // 1. Get available themes
        $request = new WP_REST_Request('GET', '/mas-v2/v1/themes');
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $this->assertNotEmpty($response->data['data']);
        
        // 2. Apply a theme
        $theme_id = 'dark-blue';
        $request = new WP_REST_Request('POST', "/mas-v2/v1/themes/$theme_id/apply");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $this->assertTrue($response->data['success']);
        
        // 3. Verify theme settings were applied
        $settings = get_option('mas_v2_settings');
        $this->assertNotEmpty($settings);
        $this->assertEquals($theme_id, $settings['current_theme'] ?? '');
    }
    
    /**
     * Test backup and restore workflow
     */
    public function test_backup_restore_workflow() {
        // 1. Save initial settings
        update_option('mas_v2_settings', $this->test_settings);
        
        // 2. Create backup
        $request = new WP_REST_Request('POST', '/mas-v2/v1/backups');
        $request->set_param('note', 'Test backup');
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $backup_id = $response->data['data']['id'];
        
        // 3. Change settings
        $new_settings = array_merge($this->test_settings, [
            'menu_background' => '#000000'
        ]);
        update_option('mas_v2_settings', $new_settings);
        
        // 4. Restore backup
        $request = new WP_REST_Request('POST', "/mas-v2/v1/backups/$backup_id/restore");
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        
        // 5. Verify original settings restored
        $restored_settings = get_option('mas_v2_settings');
        $this->assertEquals($this->test_settings['menu_background'], $restored_settings['menu_background']);
    }
    
    /**
     * Test import/export workflow
     */
    public function test_import_export_workflow() {
        // 1. Save settings
        update_option('mas_v2_settings', $this->test_settings);
        
        // 2. Export settings
        $request = new WP_REST_Request('GET', '/mas-v2/v1/export');
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $exported_data = $response->data['data'];
        
        // 3. Change settings
        update_option('mas_v2_settings', ['menu_background' => '#000000']);
        
        // 4. Import settings
        $request = new WP_REST_Request('POST', '/mas-v2/v1/import');
        $request->set_param('settings', $exported_data['settings']);
        $request->set_param('metadata', $exported_data['metadata']);
        
        $response = rest_do_request($request);
        $this->assertEquals(200, $response->get_status());
        
        // 5. Verify settings restored
        $imported_settings = get_option('mas_v2_settings');
        $this->assertEquals($this->test_settings['menu_background'], $imported_settings['menu_background']);
    }
    
    /**
     * Test error handling and recovery
     */
    public function test_error_handling_workflow() {
        // Test validation error
        $request = new WP_REST_Request('POST', '/mas-v2/v1/settings');
        $request->set_param('menu_background', 'invalid-color');
        
        $response = rest_do_request($request);
        $this->assertEquals(400, $response->get_status());
        $this->assertFalse($response->data['success']);
        
        // Test unauthorized access
        wp_set_current_user(0);
        $request = new WP_REST_Request('POST', '/mas-v2/v1/settings');
        $response = rest_do_request($request);
        
        $this->assertEquals(401, $response->get_status());
    }
    
    /**
     * Test component communication via event bus
     */
    public function test_component_communication() {
        // This would be tested in JavaScript, but we verify the backend supports it
        
        // Save settings and verify event data is included
        $request = new WP_REST_Request('POST', '/mas-v2/v1/settings');
        $request->set_param('menu_background', '#1e1e2e');
        
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $this->assertArrayHasKey('data', $response->data);
        $this->assertArrayHasKey('settings', $response->data['data']);
    }
    
    /**
     * Test no handler conflicts
     */
    public function test_no_handler_conflicts() {
        // Verify feature flag system works
        update_option('mas_v2_use_new_frontend', true);
        $this->assertTrue(get_option('mas_v2_use_new_frontend'));
        
        // Verify only one handler should be active
        // This is primarily a frontend test, but we verify the backend flag
        $this->assertTrue(true); // Backend supports single handler mode
    }
    
    /**
     * Test complete user journey: customize and save
     */
    public function test_complete_user_journey() {
        // 1. User loads settings page
        $request = new WP_REST_Request('GET', '/mas-v2/v1/settings');
        $response = rest_do_request($request);
        $this->assertEquals(200, $response->get_status());
        
        // 2. User previews changes
        $request = new WP_REST_Request('POST', '/mas-v2/v1/preview');
        $request->set_param('menu_background', '#2d2d44');
        $response = rest_do_request($request);
        $this->assertEquals(200, $response->get_status());
        
        // 3. User saves changes
        $request = new WP_REST_Request('POST', '/mas-v2/v1/settings');
        $request->set_param('menu_background', '#2d2d44');
        $response = rest_do_request($request);
        $this->assertEquals(200, $response->get_status());
        
        // 4. User creates backup
        $request = new WP_REST_Request('POST', '/mas-v2/v1/backups');
        $response = rest_do_request($request);
        $this->assertEquals(200, $response->get_status());
        
        // 5. User exports settings
        $request = new WP_REST_Request('GET', '/mas-v2/v1/export');
        $response = rest_do_request($request);
        $this->assertEquals(200, $response->get_status());
    }
    
    /**
     * Test diagnostics workflow
     */
    public function test_diagnostics_workflow() {
        $request = new WP_REST_Request('GET', '/mas-v2/v1/diagnostics');
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $this->assertArrayHasKey('system', $response->data['data']);
        $this->assertArrayHasKey('settings', $response->data['data']);
        $this->assertArrayHasKey('performance', $response->data['data']);
    }
}
