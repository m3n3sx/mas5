<?php
/**
 * Integration tests for Phase 2 Backward Compatibility with Phase 1.
 *
 * Tests Requirements: All Phase 1 requirements
 */

class TestPhase2BackwardCompatibility extends MAS_REST_Test_Case {
	
	/**
	 * Test all Phase 1 endpoints still work.
	 * Requirement: All Phase 1 requirements
	 */
	public function test_phase1_endpoints_still_work() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Test Phase 1 Settings endpoints
		
		// GET /settings
		$response = $this->perform_rest_request(
			'GET',
			'/settings',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		$this->assertArrayHasKey( 'data', $response->get_data() );
		
		// POST /settings
		$settings = array_merge( $this->default_settings, array(
			'menu_background' => '#e91e63'
		) );
		
		$response = $this->perform_rest_request(
			'POST',
			'/settings',
			$settings,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// PUT /settings (partial update)
		$response = $this->perform_rest_request(
			'PUT',
			'/settings',
			array( 'menu_text_color' => '#ffffff' ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Test Phase 1 Theme endpoints
		
		// GET /themes
		$response = $this->perform_rest_request(
			'GET',
			'/themes',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// POST /themes/{id}/apply
		$response = $this->perform_rest_request(
			'POST',
			'/themes/dark/apply',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Test Phase 1 Backup endpoints
		
		// GET /backups
		$response = $this->perform_rest_request(
			'GET',
			'/backups',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// POST /backups
		$response = $this->perform_rest_request(
			'POST',
			'/backups',
			array( 'note' => 'Compatibility test backup' ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response, 201 );
		
		// Test Phase 1 Import/Export endpoints
		
		// GET /export
		$response = $this->perform_rest_request(
			'GET',
			'/export',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Test Phase 1 Preview endpoint
		
		// POST /preview
		$response = $this->perform_rest_request(
			'POST',
			'/preview',
			array( 'menu_background' => '#2196f3' ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Test Phase 1 Diagnostics endpoint
		
		// GET /diagnostics
		$response = $this->perform_rest_request(
			'GET',
			'/diagnostics',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
	}
	
	/**
	 * Test Phase 1 JavaScript client compatibility.
	 * Requirement: All Phase 1 requirements
	 */
	public function test_phase1_javascript_client_compatibility() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Simulate Phase 1 JavaScript client requests
		
		// Test settings retrieval (Phase 1 format)
		$response = $this->perform_rest_request(
			'GET',
			'/settings',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$data = $response->get_data();
		
		// Verify Phase 1 response format is maintained
		$this->assertArrayHasKey( 'success', $data );
		$this->assertArrayHasKey( 'data', $data );
		$this->assertTrue( $data['success'] );
		
		// Test settings save (Phase 1 format)
		$settings = array(
			'menu_background' => '#9c27b0',
			'menu_text_color' => '#ffffff'
		);
		
		$response = $this->perform_rest_request(
			'POST',
			'/settings',
			$settings,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$data = $response->get_data();
		
		// Verify Phase 1 response format
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'message', $data );
		
		// Test error response format (Phase 1 compatibility)
		$response = $this->perform_rest_request(
			'POST',
			'/settings',
			array( 'menu_background' => 'invalid-color' ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertEquals( 400, $response->get_status() );
		
		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertArrayHasKey( 'message', $data );
	}
	
	/**
	 * Test graceful degradation when Phase 2 features disabled.
	 * Requirement: All Phase 1 requirements
	 */
	public function test_graceful_degradation_phase2_disabled() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Disable Phase 2 features via feature flags
		update_option( 'mas_v2_feature_flags', array(
			'rest_api_enabled' => true,
			'phase2_features_enabled' => false,
			'theme_presets_enabled' => false,
			'backup_retention_enabled' => false,
			'webhooks_enabled' => false,
			'analytics_enabled' => false
		) );
		
		// Test that Phase 1 endpoints still work
		$response = $this->perform_rest_request(
			'GET',
			'/settings',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Test that Phase 2 endpoints return appropriate responses
		
		// Theme presets should return empty or disabled message
		$response = $this->perform_rest_request(
			'GET',
			'/themes/presets',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// Should either return empty presets or feature disabled message
		if ( $response->get_status() === 200 ) {
			$data = $response->get_data();
			$this->assertArrayHasKey( 'data', $data );
		} else {
			$this->assertEquals( 503, $response->get_status() ); // Service Unavailable
		}
		
		// Webhooks should be disabled
		$response = $this->perform_rest_request(
			'POST',
			'/webhooks',
			array(
				'url' => 'https://example.com/webhook',
				'events' => array( 'settings.updated' )
			),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// Should return feature disabled
		$this->assertContains( $response->get_status(), array( 403, 503 ) );
		
		// Re-enable Phase 2 features
		update_option( 'mas_v2_feature_flags', array(
			'rest_api_enabled' => true,
			'phase2_features_enabled' => true
		) );
	}
	
	/**
	 * Test Phase 1 settings structure compatibility.
	 * Requirement: All Phase 1 requirements
	 */
	public function test_phase1_settings_structure_compatibility() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Set settings using Phase 1 structure
		$phase1_settings = array(
			'menu_background' => '#1e1e2e',
			'menu_text_color' => '#ffffff',
			'menu_hover_background' => '#2d2d44',
			'menu_hover_text_color' => '#ffffff',
			'menu_active_background' => '#3d3d5c',
			'menu_active_text_color' => '#ffffff',
			'menu_width' => '280px',
			'menu_item_height' => '48px',
			'menu_border_radius' => '12px',
			'menu_detached' => false,
			'glassmorphism_enabled' => true,
			'glassmorphism_blur' => '10px',
			'shadow_effects_enabled' => true,
			'shadow_intensity' => 'medium',
			'animations_enabled' => true,
			'animation_speed' => 'normal'
		);
		
		$response = $this->perform_rest_request(
			'POST',
			'/settings',
			$phase1_settings,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Retrieve settings and verify structure
		$response = $this->perform_rest_request(
			'GET',
			'/settings',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$data = $response->get_data();
		$settings = $data['data'];
		
		// Verify all Phase 1 fields are present
		foreach ( $phase1_settings as $key => $value ) {
			$this->assertArrayHasKey( $key, $settings, "Phase 1 setting {$key} should be present" );
			$this->assertEquals( $value, $settings[ $key ], "Phase 1 setting {$key} should match" );
		}
	}
	
	/**
	 * Test Phase 1 validation rules still apply.
	 * Requirement: All Phase 1 requirements
	 */
	public function test_phase1_validation_rules() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Test invalid color validation (Phase 1 rule)
		$response = $this->perform_rest_request(
			'POST',
			'/settings',
			array( 'menu_background' => 'not-a-color' ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseError( $response, 400 );
		
		// Test invalid CSS unit validation (Phase 1 rule)
		$response = $this->perform_rest_request(
			'POST',
			'/settings',
			array( 'menu_width' => 'invalid-width' ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseError( $response, 400 );
		
		// Test boolean validation (Phase 1 rule)
		$response = $this->perform_rest_request(
			'POST',
			'/settings',
			array( 'menu_detached' => 'not-a-boolean' ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseError( $response, 400 );
	}
	
	/**
	 * Test Phase 1 authentication and authorization.
	 * Requirement: All Phase 1 requirements
	 */
	public function test_phase1_authentication_authorization() {
		// Test unauthenticated request
		$response = $this->perform_rest_request( 'GET', '/settings' );
		$this->assertEquals( 401, $response->get_status() );
		
		// Test with editor user (should fail - needs manage_options)
		$nonce = $this->authenticate_user( $this->editor_user_id );
		
		$response = $this->perform_rest_request(
			'POST',
			'/settings',
			array( 'menu_background' => '#ff0000' ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertEquals( 403, $response->get_status() );
		
		// Test with admin user (should succeed)
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		$response = $this->perform_rest_request(
			'POST',
			'/settings',
			array_merge( $this->default_settings, array( 'menu_background' => '#00ff00' ) ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
	}
	
	/**
	 * Test Phase 1 backup and restore workflow.
	 * Requirement: All Phase 1 requirements
	 */
	public function test_phase1_backup_restore_workflow() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Set initial settings
		$initial_settings = array_merge( $this->default_settings, array(
			'menu_background' => '#4caf50'
		) );
		
		$this->perform_rest_request(
			'POST',
			'/settings',
			$initial_settings,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// Create backup (Phase 1 way)
		$response = $this->perform_rest_request(
			'POST',
			'/backups',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response, 201 );
		$backup_id = $response->get_data()['data']['backup_id'];
		
		// Change settings
		$this->perform_rest_request(
			'POST',
			'/settings',
			array_merge( $this->default_settings, array( 'menu_background' => '#f44336' ) ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// Restore backup (Phase 1 way)
		$response = $this->perform_rest_request(
			'POST',
			"/backups/{$backup_id}/restore",
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Verify restoration
		$response = $this->perform_rest_request(
			'GET',
			'/settings',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$settings = $response->get_data()['data'];
		$this->assertEquals( '#4caf50', $settings['menu_background'] );
	}
	
	/**
	 * Test Phase 1 import/export workflow.
	 * Requirement: All Phase 1 requirements
	 */
	public function test_phase1_import_export_workflow() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Set specific settings
		$settings = array_merge( $this->default_settings, array(
			'menu_background' => '#673ab7',
			'glassmorphism_enabled' => true
		) );
		
		$this->perform_rest_request(
			'POST',
			'/settings',
			$settings,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// Export (Phase 1 way)
		$response = $this->perform_rest_request(
			'GET',
			'/export',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$export_data = $response->get_data();
		$this->assertArrayHasKey( 'data', $export_data );
		
		// Change settings
		$this->perform_rest_request(
			'POST',
			'/settings',
			array_merge( $this->default_settings, array( 'menu_background' => '#000000' ) ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// Import (Phase 1 way)
		$response = $this->perform_rest_request(
			'POST',
			'/import',
			$export_data['data'],
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Verify import
		$response = $this->perform_rest_request(
			'GET',
			'/settings',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$imported_settings = $response->get_data()['data'];
		$this->assertEquals( '#673ab7', $imported_settings['menu_background'] );
		$this->assertTrue( $imported_settings['glassmorphism_enabled'] );
	}
	
	/**
	 * Test Phase 1 live preview functionality.
	 * Requirement: All Phase 1 requirements
	 */
	public function test_phase1_live_preview_functionality() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Generate preview (Phase 1 way)
		$preview_settings = array(
			'menu_background' => '#ff5722',
			'menu_text_color' => '#ffffff',
			'glassmorphism_enabled' => true,
			'glassmorphism_blur' => '15px'
		);
		
		$response = $this->perform_rest_request(
			'POST',
			'/preview',
			$preview_settings,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$preview_data = $response->get_data();
		$this->assertArrayHasKey( 'data', $preview_data );
		$this->assertArrayHasKey( 'css', $preview_data['data'] );
		
		$css = $preview_data['data']['css'];
		$this->assertStringContainsString( '#ff5722', $css );
		$this->assertStringContainsString( 'backdrop-filter: blur(15px)', $css );
		
		// Verify settings were NOT saved
		$response = $this->perform_rest_request(
			'GET',
			'/settings',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$current_settings = $response->get_data()['data'];
		$this->assertNotEquals( '#ff5722', $current_settings['menu_background'] );
	}
	
	/**
	 * Test Phase 1 diagnostics functionality.
	 * Requirement: All Phase 1 requirements
	 */
	public function test_phase1_diagnostics_functionality() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Get diagnostics (Phase 1 way)
		$response = $this->perform_rest_request(
			'GET',
			'/diagnostics',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$diagnostics_data = $response->get_data();
		$this->assertArrayHasKey( 'data', $diagnostics_data );
		
		$diagnostics = $diagnostics_data['data'];
		
		// Verify Phase 1 diagnostics fields
		$this->assertArrayHasKey( 'php_version', $diagnostics );
		$this->assertArrayHasKey( 'wordpress_version', $diagnostics );
		$this->assertArrayHasKey( 'plugin_version', $diagnostics );
		$this->assertArrayHasKey( 'settings_valid', $diagnostics );
	}
	
	/**
	 * Test Phase 1 and Phase 2 features work together.
	 * Requirement: All Phase 1 and Phase 2 requirements
	 */
	public function test_phase1_and_phase2_integration() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Use Phase 1 endpoint to save settings
		$settings = array_merge( $this->default_settings, array(
			'menu_background' => '#00bcd4'
		) );
		
		$response = $this->perform_rest_request(
			'POST',
			'/settings',
			$settings,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Use Phase 2 endpoint to check system health
		$response = $this->perform_rest_request(
			'GET',
			'/system/health',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Use Phase 2 endpoint to get theme presets
		$response = $this->perform_rest_request(
			'GET',
			'/themes/presets',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Use Phase 1 endpoint to create backup
		$response = $this->perform_rest_request(
			'POST',
			'/backups',
			array( 'note' => 'Integration test' ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response, 201 );
		
		// Use Phase 2 endpoint to check audit log
		$response = $this->perform_rest_request(
			'GET',
			'/security/audit-log',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Verify audit log contains Phase 1 operations
		$log_data = $response->get_data();
		$entries = $log_data['data']['entries'];
		
		$actions = array_column( $entries, 'action' );
		$this->assertContains( 'settings_updated', $actions );
		$this->assertContains( 'backup_created', $actions );
	}
	
	/**
	 * Test Phase 1 error handling remains consistent.
	 * Requirement: All Phase 1 requirements
	 */
	public function test_phase1_error_handling_consistency() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Test validation error format (Phase 1)
		$response = $this->perform_rest_request(
			'POST',
			'/settings',
			array( 'menu_background' => 'invalid' ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertEquals( 400, $response->get_status() );
		
		$error_data = $response->get_data();
		$this->assertArrayHasKey( 'code', $error_data );
		$this->assertArrayHasKey( 'message', $error_data );
		$this->assertArrayHasKey( 'data', $error_data );
		
		// Test not found error format (Phase 1)
		$response = $this->perform_rest_request(
			'POST',
			'/backups/999999/restore',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertEquals( 404, $response->get_status() );
		
		// Test permission error format (Phase 1)
		$editor_nonce = $this->authenticate_user( $this->editor_user_id );
		
		$response = $this->perform_rest_request(
			'POST',
			'/settings',
			$this->default_settings,
			array( 'X-WP-Nonce' => $editor_nonce )
		);
		
		$this->assertEquals( 403, $response->get_status() );
	}
	
	/**
	 * Test complete backward compatibility workflow.
	 * Requirement: All Phase 1 requirements
	 */
	public function test_complete_backward_compatibility_workflow() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Step 1: Use Phase 1 to get current settings
		$response = $this->perform_rest_request(
			'GET',
			'/settings',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Step 2: Use Phase 1 to create backup
		$response = $this->perform_rest_request(
			'POST',
			'/backups',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response, 201 );
		$backup_id = $response->get_data()['data']['backup_id'];
		
		// Step 3: Use Phase 1 to update settings
		$response = $this->perform_rest_request(
			'POST',
			'/settings',
			array_merge( $this->default_settings, array( 'menu_background' => '#3f51b5' ) ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Step 4: Use Phase 1 to apply theme
		$response = $this->perform_rest_request(
			'POST',
			'/themes/dark/apply',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Step 5: Use Phase 1 to generate preview
		$response = $this->perform_rest_request(
			'POST',
			'/preview',
			array( 'menu_background' => '#ff9800' ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Step 6: Use Phase 1 to restore backup
		$response = $this->perform_rest_request(
			'POST',
			"/backups/{$backup_id}/restore",
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Step 7: Use Phase 1 to export settings
		$response = $this->perform_rest_request(
			'GET',
			'/export',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Step 8: Use Phase 1 to get diagnostics
		$response = $this->perform_rest_request(
			'GET',
			'/diagnostics',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// All Phase 1 operations should work seamlessly
		$this->assertTrue( true, 'All Phase 1 operations completed successfully' );
	}
}
