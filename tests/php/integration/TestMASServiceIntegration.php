<?php
/**
 * Integration tests for MAS services working together.
 */

class TestMASServiceIntegration extends MAS_Test_Case {
	
	/**
	 * Settings service instance.
	 *
	 * @var MAS_Settings_Service
	 */
	private $settings_service;
	
	/**
	 * Theme service instance.
	 *
	 * @var MAS_Theme_Service
	 */
	private $theme_service;
	
	/**
	 * Backup service instance.
	 *
	 * @var MAS_Backup_Service
	 */
	private $backup_service;
	
	/**
	 * CSS generator service instance.
	 *
	 * @var MAS_CSS_Generator_Service
	 */
	private $css_generator;
	
	/**
	 * Set up test case.
	 */
	public function setUp(): void {
		parent::setUp();
		
		$this->settings_service = new MAS_Settings_Service();
		$this->theme_service = new MAS_Theme_Service();
		$this->backup_service = new MAS_Backup_Service();
		$this->css_generator = new MAS_CSS_Generator_Service();
	}
	
	/**
	 * Test settings service integration with CSS generation.
	 */
	public function test_settings_css_generation_integration() {
		wp_set_current_user( $this->admin_user_id );
		
		// Test settings save triggers CSS generation
		$new_settings = array_merge( $this->default_settings, array(
			'menu_background' => '#e91e63',
			'menu_text_color' => '#ffffff',
			'glassmorphism_enabled' => true,
			'glassmorphism_blur' => '15px'
		) );
		
		$result = $this->settings_service->save_settings( $new_settings );
		$this->assertTrue( $result );
		
		// Verify CSS was generated with new settings
		$generated_css = $this->css_generator->generate_css( $new_settings );
		$this->assertNotEmpty( $generated_css );
		$this->assertStringContainsString( '#e91e63', $generated_css );
		$this->assertStringContainsString( 'backdrop-filter: blur(15px)', $generated_css );
		
		// Verify CSS is cached
		$cached_css = wp_cache_get( 'generated_css', 'mas_v2' );
		$this->assertNotEmpty( $cached_css );
		$this->assertEquals( $generated_css, $cached_css );
	}
	
	/**
	 * Test theme service integration with settings.
	 */
	public function test_theme_settings_integration() {
		wp_set_current_user( $this->admin_user_id );
		
		// Create a custom theme
		$theme_data = array(
			'id' => 'integration-test-theme',
			'name' => 'Integration Test Theme',
			'settings' => array(
				'menu_background' => '#9c27b0',
				'menu_text_color' => '#ffffff',
				'menu_hover_background' => '#7b1fa2',
				'glassmorphism_enabled' => false
			)
		);
		
		$result = $this->theme_service->create_theme( $theme_data );
		$this->assertTrue( $result );
		
		// Apply the theme
		$result = $this->theme_service->apply_theme( 'integration-test-theme' );
		$this->assertTrue( $result );
		
		// Verify settings were updated
		$current_settings = $this->settings_service->get_settings();
		$this->assertEquals( '#9c27b0', $current_settings['menu_background'] );
		$this->assertEquals( '#ffffff', $current_settings['menu_text_color'] );
		$this->assertEquals( '#7b1fa2', $current_settings['menu_hover_background'] );
		$this->assertFalse( $current_settings['glassmorphism_enabled'] );
		
		// Verify CSS was regenerated
		$generated_css = $this->css_generator->generate_css( $current_settings );
		$this->assertStringContainsString( '#9c27b0', $generated_css );
		$this->assertStringNotContainsString( 'backdrop-filter', $generated_css );
	}
	
	/**
	 * Test backup service integration with settings.
	 */
	public function test_backup_settings_integration() {
		wp_set_current_user( $this->admin_user_id );
		
		// Set initial settings
		$initial_settings = array_merge( $this->default_settings, array(
			'menu_background' => '#4caf50',
			'menu_text_color' => '#ffffff'
		) );
		
		$this->settings_service->save_settings( $initial_settings );
		
		// Create backup
		$backup_id = $this->backup_service->create_backup( 'Integration test backup' );
		$this->assertNotEmpty( $backup_id );
		
		// Change settings
		$changed_settings = array_merge( $initial_settings, array(
			'menu_background' => '#f44336',
			'menu_hover_background' => '#d32f2f'
		) );
		
		$this->settings_service->save_settings( $changed_settings );
		
		// Verify settings changed
		$current_settings = $this->settings_service->get_settings();
		$this->assertEquals( '#f44336', $current_settings['menu_background'] );
		
		// Restore from backup
		$result = $this->backup_service->restore_backup( $backup_id );
		$this->assertTrue( $result );
		
		// Verify settings were restored
		$restored_settings = $this->settings_service->get_settings();
		$this->assertEquals( '#4caf50', $restored_settings['menu_background'] );
		$this->assertEquals( '#ffffff', $restored_settings['menu_text_color'] );
		
		// Verify CSS was regenerated after restore
		$generated_css = $this->css_generator->generate_css( $restored_settings );
		$this->assertStringContainsString( '#4caf50', $generated_css );
		$this->assertStringNotContainsString( '#f44336', $generated_css );
	}
	
	/**
	 * Test automatic backup creation during settings save.
	 */
	public function test_automatic_backup_on_settings_save() {
		wp_set_current_user( $this->admin_user_id );
		
		// Get initial backup count
		$initial_backups = $this->backup_service->list_backups();
		$initial_count = count( $initial_backups );
		
		// Save settings (should trigger automatic backup)
		$new_settings = array_merge( $this->default_settings, array(
			'menu_background' => '#ff5722',
			'current_theme' => 'custom'
		) );
		
		$this->settings_service->save_settings( $new_settings );
		
		// Verify automatic backup was created
		$updated_backups = $this->backup_service->list_backups();
		$updated_count = count( $updated_backups );
		
		$this->assertGreaterThan( $initial_count, $updated_count );
		
		// Find the automatic backup
		$automatic_backup = null;
		foreach ( $updated_backups as $backup ) {
			if ( $backup['type'] === 'automatic' ) {
				$automatic_backup = $backup;
				break;
			}
		}
		
		$this->assertNotNull( $automatic_backup );
		$this->assertArrayHasKey( 'settings', $automatic_backup );
	}
	
	/**
	 * Test validation service integration across components.
	 */
	public function test_validation_service_integration() {
		wp_set_current_user( $this->admin_user_id );
		
		$validation_service = new MAS_Validation_Service();
		
		// Test settings validation
		$invalid_settings = array(
			'menu_background' => 'invalid-color',
			'menu_width' => 'invalid-width',
			'menu_detached' => 'not-boolean'
		);
		
		$validation_result = $validation_service->validate_settings( $invalid_settings );
		$this->assertFalse( $validation_result['valid'] );
		$this->assertNotEmpty( $validation_result['errors'] );
		$this->assertArrayHasKey( 'menu_background', $validation_result['errors'] );
		$this->assertArrayHasKey( 'menu_width', $validation_result['errors'] );
		$this->assertArrayHasKey( 'menu_detached', $validation_result['errors'] );
		
		// Test theme validation
		$invalid_theme = array(
			'id' => '', // Empty ID
			'name' => 'Test Theme',
			'settings' => array(
				'menu_background' => 'invalid-color'
			)
		);
		
		$theme_validation = $validation_service->validate_theme( $invalid_theme );
		$this->assertFalse( $theme_validation['valid'] );
		$this->assertNotEmpty( $theme_validation['errors'] );
		
		// Test valid data passes validation
		$valid_settings = array(
			'menu_background' => '#2196f3',
			'menu_text_color' => '#ffffff',
			'menu_width' => '280px',
			'menu_detached' => true
		);
		
		$valid_result = $validation_service->validate_settings( $valid_settings );
		$this->assertTrue( $valid_result['valid'] );
		$this->assertEmpty( $valid_result['errors'] );
	}
	
	/**
	 * Test cache service integration.
	 */
	public function test_cache_service_integration() {
		wp_set_current_user( $this->admin_user_id );
		
		$cache_service = new MAS_Cache_Service();
		
		// Test settings caching
		$settings = $this->settings_service->get_settings();
		$cached_settings = $cache_service->get( 'current_settings' );
		$this->assertNotEmpty( $cached_settings );
		$this->assertEquals( $settings, $cached_settings );
		
		// Test cache invalidation on settings save
		$new_settings = array_merge( $this->default_settings, array(
			'menu_background' => '#795548'
		) );
		
		$this->settings_service->save_settings( $new_settings );
		
		// Cache should be invalidated
		$cached_after_save = $cache_service->get( 'current_settings' );
		$this->assertNotEquals( $settings, $cached_after_save );
		
		// Test CSS caching
		$css = $this->css_generator->generate_css( $new_settings );
		$cached_css = $cache_service->get( 'generated_css' );
		$this->assertEquals( $css, $cached_css );
	}
	
	/**
	 * Test error handling integration across services.
	 */
	public function test_error_handling_integration() {
		wp_set_current_user( $this->admin_user_id );
		
		// Test settings service error handling
		$invalid_settings = array(
			'menu_background' => 'invalid-color'
		);
		
		$result = $this->settings_service->save_settings( $invalid_settings );
		$this->assertFalse( $result );
		
		// Verify original settings unchanged
		$current_settings = $this->settings_service->get_settings();
		$this->assertEquals( $this->default_settings['menu_background'], $current_settings['menu_background'] );
		
		// Test theme service error handling
		$invalid_theme = array(
			'id' => 'default', // Try to modify predefined theme
			'name' => 'Modified Default',
			'settings' => array( 'menu_background' => '#ff0000' )
		);
		
		$result = $this->theme_service->update_theme( 'default', $invalid_theme );
		$this->assertFalse( $result );
		
		// Test backup service error handling
		$result = $this->backup_service->restore_backup( 999999 ); // Non-existent backup
		$this->assertFalse( $result );
		
		// Verify settings unchanged after failed restore
		$settings_after_failed_restore = $this->settings_service->get_settings();
		$this->assertEquals( $current_settings, $settings_after_failed_restore );
	}
	
	/**
	 * Test performance with multiple service interactions.
	 */
	public function test_performance_integration() {
		wp_set_current_user( $this->admin_user_id );
		
		$start_time = microtime( true );
		$start_memory = memory_get_usage();
		
		// Perform multiple operations
		for ( $i = 0; $i < 5; $i++ ) {
			// Save settings
			$settings = array_merge( $this->default_settings, array(
				'menu_background' => sprintf( '#%06x', mt_rand( 0, 0xFFFFFF ) )
			) );
			$this->settings_service->save_settings( $settings );
			
			// Generate CSS
			$this->css_generator->generate_css( $settings );
			
			// Create backup
			$this->backup_service->create_backup( "Performance test backup {$i}" );
		}
		
		$end_time = microtime( true );
		$end_memory = memory_get_usage();
		
		$execution_time = $end_time - $start_time;
		$memory_used = $end_memory - $start_memory;
		
		// Performance assertions (adjust thresholds as needed)
		$this->assertLessThan( 2.0, $execution_time, 'Operations should complete within 2 seconds' );
		$this->assertLessThan( 10 * 1024 * 1024, $memory_used, 'Memory usage should be under 10MB' );
	}
}