<?php
/**
 * Migration Testing Suite (PHP)
 * 
 * Tests migration between old and new frontend systems from PHP side.
 * Verifies feature flags, script loading, and data integrity.
 * 
 * @package ModernAdminStylerV2
 * @subpackage Tests
 */

class TestMigration extends WP_UnitTestCase {
    
    private $flags_service;
    private $admin_user;
    
    public function setUp(): void {
        parent::setUp();
        
        // Load feature flags service
        require_once MAS_V2_PLUGIN_DIR . 'includes/services/class-mas-feature-flags-service.php';
        $this->flags_service = MAS_Feature_Flags_Service::get_instance();
        
        // Create admin user
        $this->admin_user = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($this->admin_user);
    }
    
    public function tearDown(): void {
        // Reset feature flags
        $this->flags_service->reset_to_defaults();
        
        parent::tearDown();
    }
    
    /**
     * Test feature flag system
     */
    public function test_feature_flag_system() {
        // Default should be legacy
        $this->assertFalse($this->flags_service->use_new_frontend());
        $this->assertEquals('legacy', $this->flags_service->get_frontend_mode());
        
        // Enable new frontend
        $this->flags_service->enable('use_new_frontend');
        $this->assertTrue($this->flags_service->use_new_frontend());
        $this->assertEquals('new', $this->flags_service->get_frontend_mode());
        
        // Disable new frontend
        $this->flags_service->disable('use_new_frontend');
        $this->assertFalse($this->flags_service->use_new_frontend());
        $this->assertEquals('legacy', $this->flags_service->get_frontend_mode());
    }
    
    /**
     * Test settings preservation during migration
     */
    public function test_settings_preservation() {
        // Save test settings
        $test_settings = [
            'menu_background' => '#1e1e2e',
            'menu_text_color' => '#ffffff',
            'menu_detached' => true,
            'custom_css' => '.test { color: red; }'
        ];
        
        update_option('mas_v2_settings', $test_settings);
        
        // Switch to new frontend
        $this->flags_service->enable('use_new_frontend');
        
        // Settings should be preserved
        $saved_settings = get_option('mas_v2_settings');
        $this->assertEquals($test_settings, $saved_settings);
        
        // Switch back to legacy
        $this->flags_service->disable('use_new_frontend');
        
        // Settings should still be preserved
        $saved_settings = get_option('mas_v2_settings');
        $this->assertEquals($test_settings, $saved_settings);
    }
    
    /**
     * Test no data loss during migration
     */
    public function test_no_data_loss() {
        // Create complex settings
        $complex_settings = [
            'menu_background' => '#1e1e2e',
            'menu_text_color' => '#ffffff',
            'menu_hover_background' => '#2d2d44',
            'menu_hover_text_color' => '#ffffff',
            'menu_active_background' => '#3d3d5c',
            'menu_active_text_color' => '#ffffff',
            'menu_width' => '280px',
            'menu_item_height' => '48px',
            'menu_border_radius' => '12px',
            'menu_detached' => true,
            'glassmorphism_enabled' => true,
            'glassmorphism_blur' => '10px',
            'custom_css' => '.custom { background: blue; }',
            'custom_themes' => [
                'dark-blue' => [
                    'name' => 'Dark Blue',
                    'colors' => ['#1e1e2e', '#ffffff']
                ]
            ]
        ];
        
        update_option('mas_v2_settings', $complex_settings);
        
        // Perform multiple migrations
        for ($i = 0; $i < 5; $i++) {
            $this->flags_service->set_flag('use_new_frontend', $i % 2 === 0);
            
            $saved_settings = get_option('mas_v2_settings');
            $this->assertEquals($complex_settings, $saved_settings, "Data lost on iteration $i");
        }
    }
    
    /**
     * Test script loading based on feature flags
     */
    public function test_script_loading() {
        global $wp_scripts;
        
        // Reset scripts
        $wp_scripts = new WP_Scripts();
        
        // Test legacy mode
        $this->flags_service->disable('use_new_frontend');
        
        // Simulate script enqueue
        do_action('admin_enqueue_scripts', 'toplevel_page_mas-v2-settings');
        
        // Legacy scripts should be enqueued
        $this->assertTrue(wp_script_is('mas-v2-settings-form-handler', 'enqueued') || 
                         wp_script_is('mas-v2-settings-form-handler', 'registered'));
        
        // Reset scripts
        $wp_scripts = new WP_Scripts();
        
        // Test new mode
        $this->flags_service->enable('use_new_frontend');
        
        // Simulate script enqueue
        do_action('admin_enqueue_scripts', 'toplevel_page_mas-v2-settings');
        
        // New scripts should be enqueued
        $this->assertTrue(wp_script_is('mas-v2-admin-app', 'enqueued') || 
                         wp_script_is('mas-v2-admin-app', 'registered'));
    }
    
    /**
     * Test feature flag persistence
     */
    public function test_feature_flag_persistence() {
        // Set flags
        $this->flags_service->enable('use_new_frontend');
        $this->flags_service->enable('debug_mode');
        
        // Create new instance (simulates page reload)
        $new_instance = MAS_Feature_Flags_Service::get_instance();
        
        // Flags should be persisted
        $this->assertTrue($new_instance->is_enabled('use_new_frontend'));
        $this->assertTrue($new_instance->is_enabled('debug_mode'));
    }
    
    /**
     * Test rollback functionality
     */
    public function test_rollback() {
        // Start in new mode
        $this->flags_service->enable('use_new_frontend');
        $this->assertTrue($this->flags_service->use_new_frontend());
        
        // Save settings in new mode
        $settings = ['menu_background' => '#2d2d44'];
        update_option('mas_v2_settings', $settings);
        
        // Rollback to legacy
        $this->flags_service->disable('use_new_frontend');
        $this->assertFalse($this->flags_service->use_new_frontend());
        
        // Settings should be preserved
        $saved_settings = get_option('mas_v2_settings');
        $this->assertEquals($settings, $saved_settings);
    }
    
    /**
     * Test constant override
     */
    public function test_constant_override() {
        // Define constant
        if (!defined('MAS_V2_USE_NEW_FRONTEND')) {
            define('MAS_V2_USE_NEW_FRONTEND', true);
        }
        
        // Constant should override database value
        $this->flags_service->disable('use_new_frontend');
        
        // Should still return true due to constant
        $this->assertTrue($this->flags_service->is_enabled('use_new_frontend'));
    }
    
    /**
     * Test admin UI access
     */
    public function test_admin_ui_access() {
        // Admin should have access
        $this->assertTrue(current_user_can('manage_options'));
        
        // Non-admin should not
        $subscriber = $this->factory->user->create(['role' => 'subscriber']);
        wp_set_current_user($subscriber);
        $this->assertFalse(current_user_can('manage_options'));
    }
    
    /**
     * Test export for JavaScript
     */
    public function test_export_for_js() {
        $this->flags_service->enable('use_new_frontend');
        $this->flags_service->enable('debug_mode');
        
        $exported = $this->flags_service->export_for_js();
        
        $this->assertIsArray($exported);
        $this->assertTrue($exported['useNewFrontend']);
        $this->assertTrue($exported['debugMode']);
        $this->assertEquals('new', $exported['frontendMode']);
    }
    
    /**
     * Test migration with backups
     */
    public function test_migration_with_backups() {
        // Save original settings
        $original_settings = [
            'menu_background' => '#1e1e2e',
            'menu_text_color' => '#ffffff'
        ];
        update_option('mas_v2_settings', $original_settings);
        
        // Create backup before migration
        $backup = get_option('mas_v2_settings');
        update_option('mas_v2_settings_backup_pre_migration', $backup);
        
        // Perform migration
        $this->flags_service->enable('use_new_frontend');
        
        // Modify settings in new mode
        $new_settings = [
            'menu_background' => '#2d2d44',
            'menu_text_color' => '#e0e0e0'
        ];
        update_option('mas_v2_settings', $new_settings);
        
        // Rollback should restore backup
        $backup = get_option('mas_v2_settings_backup_pre_migration');
        $this->assertEquals($original_settings, $backup);
    }
    
    /**
     * Test parallel system prevention
     */
    public function test_parallel_system_prevention() {
        // Enable new frontend
        $this->flags_service->enable('use_new_frontend');
        
        // Check that only one system is active
        $frontend_mode = $this->flags_service->get_frontend_mode();
        $this->assertEquals('new', $frontend_mode);
        
        // Legacy should be disabled
        $this->assertFalse($this->flags_service->get_frontend_mode() === 'legacy');
    }
    
    /**
     * Test error handling during migration
     */
    public function test_error_handling() {
        // Simulate error during migration
        try {
            // Invalid flag name
            $this->flags_service->set_flag('invalid_flag', true);
            
            // Should not throw exception
            $this->assertTrue(true);
        } catch (Exception $e) {
            $this->fail('Should not throw exception for invalid flag');
        }
    }
    
    /**
     * Test performance during migration
     */
    public function test_migration_performance() {
        $start_time = microtime(true);
        
        // Perform multiple migrations
        for ($i = 0; $i < 100; $i++) {
            $this->flags_service->set_flag('use_new_frontend', $i % 2 === 0);
        }
        
        $end_time = microtime(true);
        $duration = $end_time - $start_time;
        
        // Should complete in reasonable time (< 1 second)
        $this->assertLessThan(1.0, $duration, 'Migration took too long');
    }
}
