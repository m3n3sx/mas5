<?php
/**
 * Integration tests for Phase 2 Theme Management features.
 *
 * Tests Requirements: 1.1, 1.2, 1.3, 1.4
 */

class TestPhase2ThemeManagement extends MAS_REST_Test_Case {
	
	/**
	 * Theme preset service instance.
	 *
	 * @var MAS_Theme_Preset_Service
	 */
	private $theme_preset_service;
	
	/**
	 * Themes controller instance.
	 *
	 * @var MAS_Themes_Controller
	 */
	private $themes_controller;
	
	/**
	 * Set up test case.
	 */
	public function setUp(): void {
		parent::setUp();
		
		$this->theme_preset_service = new MAS_Theme_Preset_Service();
		$this->themes_controller = new MAS_Themes_Controller();
	}
	
	/**
	 * Test complete theme preview workflow.
	 * Requirement: 1.2
	 */
	public function test_theme_preview_workflow() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Get available theme presets
		$response = $this->perform_rest_request( 'GET', '/themes/presets' );
		$this->assertRestResponseSuccess( $response );
		
		$data = $response->get_data();
		$this->assertArrayHasKey( 'data', $data );
		$this->assertArrayHasKey( 'presets', $data['data'] );
		$this->assertNotEmpty( $data['data']['presets'] );
		
		// Select a theme to preview
		$theme_id = array_key_first( $data['data']['presets'] );
		$theme_preset = $data['data']['presets'][ $theme_id ];
		
		// Preview the theme (should not save)
		$response = $this->perform_rest_request(
			'POST',
			'/themes/preview',
			array( 'theme_id' => $theme_id ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$preview_data = $response->get_data();
		$this->assertArrayHasKey( 'data', $preview_data );
		$this->assertArrayHasKey( 'css', $preview_data['data'] );
		$this->assertArrayHasKey( 'settings', $preview_data['data'] );
		$this->assertNotEmpty( $preview_data['data']['css'] );
		
		// Verify CSS contains theme colors
		$css = $preview_data['data']['css'];
		$this->assertStringContainsString( $theme_preset['settings']['menu_background'], $css );
		
		// Verify settings were NOT saved (preview only)
		$current_settings = get_option( 'mas_v2_settings' );
		$this->assertNotEquals( $theme_preset['settings']['menu_background'], $current_settings['menu_background'] );
	}
	
	/**
	 * Test theme import/export with version checking.
	 * Requirements: 1.3, 1.4, 1.5
	 */
	public function test_theme_import_export_with_version_checking() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Create a custom theme
		$custom_theme = array(
			'id' => 'export-test-theme',
			'name' => 'Export Test Theme',
			'settings' => array(
				'menu_background' => '#e91e63',
				'menu_text_color' => '#ffffff',
				'menu_hover_background' => '#c2185b',
				'menu_hover_text_color' => '#ffffff',
				'glassmorphism_enabled' => true,
				'glassmorphism_blur' => '12px'
			)
		);
		
		$response = $this->perform_rest_request(
			'POST',
			'/themes',
			$custom_theme,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response, 201 );
		
		// Export the theme
		$response = $this->perform_rest_request(
			'POST',
			'/themes/export',
			array( 'theme_id' => 'export-test-theme' ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$export_data = $response->get_data();
		$this->assertArrayHasKey( 'data', $export_data );
		$this->assertArrayHasKey( 'theme', $export_data['data'] );
		$this->assertArrayHasKey( 'version', $export_data['data'] );
		$this->assertArrayHasKey( 'checksum', $export_data['data'] );
		
		$exported_theme = $export_data['data']['theme'];
		$version = $export_data['data']['version'];
		$checksum = $export_data['data']['checksum'];
		
		// Verify version metadata
		$this->assertNotEmpty( $version );
		$this->assertArrayHasKey( 'plugin_version', $version );
		$this->assertArrayHasKey( 'export_date', $version );
		
		// Verify checksum
		$this->assertNotEmpty( $checksum );
		
		// Delete the theme
		$response = $this->perform_rest_request(
			'DELETE',
			'/themes/export-test-theme',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Import the theme back
		$import_data = array(
			'theme' => $exported_theme,
			'version' => $version,
			'checksum' => $checksum
		);
		
		$response = $this->perform_rest_request(
			'POST',
			'/themes/import',
			$import_data,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$import_result = $response->get_data();
		$this->assertArrayHasKey( 'data', $import_result );
		$this->assertArrayHasKey( 'theme_id', $import_result['data'] );
		$this->assertEquals( 'export-test-theme', $import_result['data']['theme_id'] );
		
		// Verify theme was imported correctly
		$response = $this->perform_rest_request( 'GET', '/themes' );
		$themes_data = $response->get_data();
		
		$imported_theme = null;
		foreach ( $themes_data['data']['themes'] as $theme ) {
			if ( $theme['id'] === 'export-test-theme' ) {
				$imported_theme = $theme;
				break;
			}
		}
		
		$this->assertNotNull( $imported_theme );
		$this->assertEquals( '#e91e63', $imported_theme['settings']['menu_background'] );
		$this->assertTrue( $imported_theme['settings']['glassmorphism_enabled'] );
	}
	
	/**
	 * Test theme import with incompatible version.
	 * Requirement: 1.5
	 */
	public function test_theme_import_version_compatibility_check() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Create import data with incompatible version
		$incompatible_import = array(
			'theme' => array(
				'id' => 'incompatible-theme',
				'name' => 'Incompatible Theme',
				'settings' => array(
					'menu_background' => '#ff0000'
				)
			),
			'version' => array(
				'plugin_version' => '1.0.0', // Old version
				'export_date' => '2020-01-01'
			),
			'checksum' => 'invalid-checksum'
		);
		
		$response = $this->perform_rest_request(
			'POST',
			'/themes/import',
			$incompatible_import,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// Should return error for incompatible version
		$this->assertRestResponseError( $response, 400 );
		
		$error_data = $response->get_data();
		$this->assertStringContainsString( 'version', strtolower( $error_data['message'] ) );
	}
	
	/**
	 * Test theme import with invalid checksum.
	 * Requirement: 1.5
	 */
	public function test_theme_import_checksum_validation() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Create import data with invalid checksum
		$invalid_checksum_import = array(
			'theme' => array(
				'id' => 'checksum-test-theme',
				'name' => 'Checksum Test Theme',
				'settings' => array(
					'menu_background' => '#00ff00'
				)
			),
			'version' => array(
				'plugin_version' => MAS_VERSION,
				'export_date' => current_time( 'mysql' )
			),
			'checksum' => 'definitely-invalid-checksum'
		);
		
		$response = $this->perform_rest_request(
			'POST',
			'/themes/import',
			$invalid_checksum_import,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// Should return error for invalid checksum
		$this->assertRestResponseError( $response, 400 );
		
		$error_data = $response->get_data();
		$this->assertStringContainsString( 'checksum', strtolower( $error_data['message'] ) );
	}
	
	/**
	 * Test theme preset application.
	 * Requirement: 1.1
	 */
	public function test_theme_preset_application() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Get initial settings
		$initial_settings = get_option( 'mas_v2_settings' );
		
		// Get available presets
		$response = $this->perform_rest_request( 'GET', '/themes/presets' );
		$presets_data = $response->get_data();
		$presets = $presets_data['data']['presets'];
		
		// Apply a preset theme
		$preset_id = 'dark'; // Assuming 'dark' preset exists
		if ( ! isset( $presets[ $preset_id ] ) ) {
			$preset_id = array_key_first( $presets );
		}
		
		$response = $this->perform_rest_request(
			'POST',
			"/themes/{$preset_id}/apply",
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Verify settings were updated
		$updated_settings = get_option( 'mas_v2_settings' );
		$this->assertNotEquals( $initial_settings['menu_background'], $updated_settings['menu_background'] );
		
		// Verify CSS was regenerated
		$css_cache = wp_cache_get( 'generated_css', 'mas_v2' );
		$this->assertNotEmpty( $css_cache );
		
		// Verify the applied theme settings match the preset
		$preset_settings = $presets[ $preset_id ]['settings'];
		foreach ( $preset_settings as $key => $value ) {
			$this->assertEquals( $value, $updated_settings[ $key ], "Setting {$key} should match preset value" );
		}
	}
	
	/**
	 * Test theme preview with custom settings.
	 * Requirement: 1.2
	 */
	public function test_theme_preview_with_custom_settings() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Preview with custom settings (not a preset)
		$custom_preview_settings = array(
			'menu_background' => '#9c27b0',
			'menu_text_color' => '#ffffff',
			'menu_hover_background' => '#7b1fa2',
			'glassmorphism_enabled' => true,
			'glassmorphism_blur' => '20px',
			'shadow_effects_enabled' => true
		);
		
		$response = $this->perform_rest_request(
			'POST',
			'/themes/preview',
			array( 'settings' => $custom_preview_settings ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$preview_data = $response->get_data();
		$this->assertArrayHasKey( 'css', $preview_data['data'] );
		
		$css = $preview_data['data']['css'];
		
		// Verify CSS contains custom settings
		$this->assertStringContainsString( '#9c27b0', $css );
		$this->assertStringContainsString( 'backdrop-filter: blur(20px)', $css );
		
		// Verify settings were NOT saved
		$current_settings = get_option( 'mas_v2_settings' );
		$this->assertNotEquals( '#9c27b0', $current_settings['menu_background'] );
	}
	
	/**
	 * Test theme export includes all required metadata.
	 * Requirement: 1.3
	 */
	public function test_theme_export_metadata() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Create a theme with full settings
		$full_theme = array(
			'id' => 'metadata-test-theme',
			'name' => 'Metadata Test Theme',
			'description' => 'Theme for testing export metadata',
			'settings' => array(
				'menu_background' => '#3f51b5',
				'menu_text_color' => '#ffffff',
				'menu_hover_background' => '#303f9f',
				'menu_hover_text_color' => '#ffffff',
				'menu_active_background' => '#1a237e',
				'menu_active_text_color' => '#ffffff',
				'menu_width' => '300px',
				'menu_item_height' => '50px',
				'menu_border_radius' => '15px',
				'glassmorphism_enabled' => true,
				'glassmorphism_blur' => '15px',
				'shadow_effects_enabled' => true,
				'shadow_intensity' => 'high',
				'animations_enabled' => true,
				'animation_speed' => 'fast'
			)
		);
		
		$response = $this->perform_rest_request(
			'POST',
			'/themes',
			$full_theme,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response, 201 );
		
		// Export the theme
		$response = $this->perform_rest_request(
			'POST',
			'/themes/export',
			array( 'theme_id' => 'metadata-test-theme' ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$export_data = $response->get_data();
		$metadata = $export_data['data'];
		
		// Verify all required metadata fields
		$this->assertArrayHasKey( 'theme', $metadata );
		$this->assertArrayHasKey( 'version', $metadata );
		$this->assertArrayHasKey( 'checksum', $metadata );
		
		// Verify version metadata
		$version = $metadata['version'];
		$this->assertArrayHasKey( 'plugin_version', $version );
		$this->assertArrayHasKey( 'wordpress_version', $version );
		$this->assertArrayHasKey( 'export_date', $version );
		$this->assertArrayHasKey( 'php_version', $version );
		
		// Verify theme data is complete
		$theme = $metadata['theme'];
		$this->assertEquals( 'metadata-test-theme', $theme['id'] );
		$this->assertEquals( 'Metadata Test Theme', $theme['name'] );
		$this->assertArrayHasKey( 'settings', $theme );
		$this->assertCount( 15, $theme['settings'] );
	}
	
	/**
	 * Test concurrent theme operations.
	 * Requirement: 1.1, 1.2
	 */
	public function test_concurrent_theme_operations() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Create multiple themes
		$theme_ids = array();
		for ( $i = 1; $i <= 3; $i++ ) {
			$theme = array(
				'id' => "concurrent-theme-{$i}",
				'name' => "Concurrent Theme {$i}",
				'settings' => array(
					'menu_background' => sprintf( '#%06x', mt_rand( 0, 0xFFFFFF ) ),
					'menu_text_color' => '#ffffff'
				)
			);
			
			$response = $this->perform_rest_request(
				'POST',
				'/themes',
				$theme,
				array( 'X-WP-Nonce' => $nonce )
			);
			
			$this->assertRestResponseSuccess( $response, 201 );
			$theme_ids[] = "concurrent-theme-{$i}";
		}
		
		// Preview multiple themes rapidly
		foreach ( $theme_ids as $theme_id ) {
			$response = $this->perform_rest_request(
				'POST',
				'/themes/preview',
				array( 'theme_id' => $theme_id ),
				array( 'X-WP-Nonce' => $nonce )
			);
			
			$this->assertRestResponseSuccess( $response );
		}
		
		// Apply one theme
		$response = $this->perform_rest_request(
			'POST',
			"/themes/{$theme_ids[1]}/apply",
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Verify correct theme was applied
		$settings = get_option( 'mas_v2_settings' );
		$this->assertEquals( 'concurrent-theme-2', $settings['current_theme'] );
		
		// Clean up
		foreach ( $theme_ids as $theme_id ) {
			$this->perform_rest_request(
				'DELETE',
				"/themes/{$theme_id}",
				array(),
				array( 'X-WP-Nonce' => $nonce )
			);
		}
	}
}
