<?php
/**
 * End-to-end integration tests for complete MAS workflows.
 */

class TestMASCompleteWorkflows extends MAS_REST_Test_Case {
	
	/**
	 * Test complete settings lifecycle workflow.
	 */
	public function test_complete_settings_lifecycle() {
		// Authenticate as admin
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// 1. Get initial settings
		$response = $this->perform_rest_request( 'GET', '/settings' );
		$this->assertRestResponseSuccess( $response );
		$initial_data = $response->get_data();
		$this->assertArrayHasKey( 'data', $initial_data );
		$initial_settings = $initial_data['data'];
		
		// 2. Update settings (partial update)
		$update_data = array(
			'menu_background' => '#ff5722',
			'menu_text_color' => '#ffffff'
		);
		
		$response = $this->perform_rest_request( 
			'PUT', 
			'/settings', 
			$update_data,
			array( 'X-WP-Nonce' => $nonce )
		);
		$this->assertRestResponseSuccess( $response );
		
		// 3. Verify settings were updated
		$response = $this->perform_rest_request( 'GET', '/settings' );
		$this->assertRestResponseSuccess( $response );
		$updated_data = $response->get_data();
		$updated_settings = $updated_data['data'];
		
		$this->assertEquals( '#ff5722', $updated_settings['menu_background'] );
		$this->assertEquals( '#ffffff', $updated_settings['menu_text_color'] );
		
		// 4. Save complete settings (full update)
		$complete_settings = array_merge( $initial_settings, array(
			'menu_background' => '#2196f3',
			'menu_text_color' => '#ffffff',
			'menu_hover_background' => '#1976d2'
		) );
		
		$response = $this->perform_rest_request( 
			'POST', 
			'/settings', 
			$complete_settings,
			array( 'X-WP-Nonce' => $nonce )
		);
		$this->assertRestResponseSuccess( $response );
		
		// 5. Verify complete settings were saved
		$response = $this->perform_rest_request( 'GET', '/settings' );
		$this->assertRestResponseSuccess( $response );
		$final_data = $response->get_data();
		$final_settings = $final_data['data'];
		
		$this->assertEquals( '#2196f3', $final_settings['menu_background'] );
		$this->assertEquals( '#1976d2', $final_settings['menu_hover_background'] );
		
		// 6. Reset settings to defaults
		$response = $this->perform_rest_request( 
			'DELETE', 
			'/settings',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		$this->assertRestResponseSuccess( $response );
		
		// 7. Verify settings were reset
		$response = $this->perform_rest_request( 'GET', '/settings' );
		$this->assertRestResponseSuccess( $response );
		$reset_data = $response->get_data();
		$reset_settings = $reset_data['data'];
		
		// Should match initial default settings
		$this->assertEquals( $this->default_settings['menu_background'], $reset_settings['menu_background'] );
	}
	
	/**
	 * Test complete theme application workflow.
	 */
	public function test_complete_theme_workflow() {
		// Authenticate as admin
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// 1. Get available themes
		$response = $this->perform_rest_request( 'GET', '/themes' );
		$this->assertRestResponseSuccess( $response );
		$themes_data = $response->get_data();
		$this->assertArrayHasKey( 'data', $themes_data );
		$themes = $themes_data['data'];
		$this->assertNotEmpty( $themes );
		
		// 2. Create a custom theme
		$custom_theme = array(
			'id' => 'test-custom-theme',
			'name' => 'Test Custom Theme',
			'settings' => array(
				'menu_background' => '#e91e63',
				'menu_text_color' => '#ffffff',
				'menu_hover_background' => '#c2185b'
			)
		);
		
		$response = $this->perform_rest_request( 
			'POST', 
			'/themes', 
			$custom_theme,
			array( 'X-WP-Nonce' => $nonce )
		);
		$this->assertRestResponseSuccess( $response, 201 );
		
		// 3. Verify theme was created
		$response = $this->perform_rest_request( 'GET', '/themes' );
		$this->assertRestResponseSuccess( $response );
		$updated_themes_data = $response->get_data();
		$updated_themes = $updated_themes_data['data'];
		
		$found_custom_theme = false;
		foreach ( $updated_themes as $theme ) {
			if ( $theme['id'] === 'test-custom-theme' ) {
				$found_custom_theme = true;
				$this->assertEquals( 'Test Custom Theme', $theme['name'] );
				$this->assertEquals( 'custom', $theme['type'] );
				break;
			}
		}
		$this->assertTrue( $found_custom_theme, 'Custom theme should be found in themes list' );
		
		// 4. Apply the custom theme
		$response = $this->perform_rest_request( 
			'POST', 
			'/themes/test-custom-theme/apply',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		$this->assertRestResponseSuccess( $response );
		
		// 5. Verify theme was applied to settings
		$response = $this->perform_rest_request( 'GET', '/settings' );
		$this->assertRestResponseSuccess( $response );
		$settings_data = $response->get_data();
		$settings = $settings_data['data'];
		
		$this->assertEquals( '#e91e63', $settings['menu_background'] );
		$this->assertEquals( '#ffffff', $settings['menu_text_color'] );
		$this->assertEquals( '#c2185b', $settings['menu_hover_background'] );
		
		// 6. Update the custom theme
		$updated_theme = array(
			'name' => 'Updated Custom Theme',
			'settings' => array(
				'menu_background' => '#9c27b0',
				'menu_text_color' => '#ffffff'
			)
		);
		
		$response = $this->perform_rest_request( 
			'PUT', 
			'/themes/test-custom-theme', 
			$updated_theme,
			array( 'X-WP-Nonce' => $nonce )
		);
		$this->assertRestResponseSuccess( $response );
		
		// 7. Delete the custom theme
		$response = $this->perform_rest_request( 
			'DELETE', 
			'/themes/test-custom-theme',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		$this->assertRestResponseSuccess( $response );
		
		// 8. Verify theme was deleted
		$response = $this->perform_rest_request( 'GET', '/themes' );
		$this->assertRestResponseSuccess( $response );
		$final_themes_data = $response->get_data();
		$final_themes = $final_themes_data['data'];
		
		$theme_still_exists = false;
		foreach ( $final_themes as $theme ) {
			if ( $theme['id'] === 'test-custom-theme' ) {
				$theme_still_exists = true;
				break;
			}
		}
		$this->assertFalse( $theme_still_exists, 'Custom theme should be deleted' );
	}
	
	/**
	 * Test complete backup and restore workflow.
	 */
	public function test_complete_backup_restore_workflow() {
		// Authenticate as admin
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// 1. Set up initial settings
		$initial_settings = array_merge( $this->default_settings, array(
			'menu_background' => '#4caf50',
			'menu_text_color' => '#ffffff'
		) );
		
		$response = $this->perform_rest_request( 
			'POST', 
			'/settings', 
			$initial_settings,
			array( 'X-WP-Nonce' => $nonce )
		);
		$this->assertRestResponseSuccess( $response );
		
		// 2. Create a manual backup
		$response = $this->perform_rest_request( 
			'POST', 
			'/backups',
			array( 'note' => 'Test backup before changes' ),
			array( 'X-WP-Nonce' => $nonce )
		);
		$this->assertRestResponseSuccess( $response, 201 );
		$backup_data = $response->get_data();
		$backup_id = $backup_data['data']['id'];
		
		// 3. Verify backup was created
		$response = $this->perform_rest_request( 'GET', '/backups' );
		$this->assertRestResponseSuccess( $response );
		$backups_data = $response->get_data();
		$backups = $backups_data['data'];
		$this->assertNotEmpty( $backups );
		
		$found_backup = false;
		foreach ( $backups as $backup ) {
			if ( $backup['id'] == $backup_id ) {
				$found_backup = true;
				$this->assertEquals( 'manual', $backup['type'] );
				$this->assertEquals( '#4caf50', $backup['settings']['menu_background'] );
				break;
			}
		}
		$this->assertTrue( $found_backup, 'Backup should be found in backups list' );
		
		// 4. Change settings
		$changed_settings = array_merge( $initial_settings, array(
			'menu_background' => '#f44336',
			'menu_text_color' => '#ffffff'
		) );
		
		$response = $this->perform_rest_request( 
			'POST', 
			'/settings', 
			$changed_settings,
			array( 'X-WP-Nonce' => $nonce )
		);
		$this->assertRestResponseSuccess( $response );
		
		// 5. Verify settings changed
		$response = $this->perform_rest_request( 'GET', '/settings' );
		$this->assertRestResponseSuccess( $response );
		$current_data = $response->get_data();
		$current_settings = $current_data['data'];
		$this->assertEquals( '#f44336', $current_settings['menu_background'] );
		
		// 6. Restore from backup
		$response = $this->perform_rest_request( 
			'POST', 
			"/backups/{$backup_id}/restore",
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		$this->assertRestResponseSuccess( $response );
		
		// 7. Verify settings were restored
		$response = $this->perform_rest_request( 'GET', '/settings' );
		$this->assertRestResponseSuccess( $response );
		$restored_data = $response->get_data();
		$restored_settings = $restored_data['data'];
		$this->assertEquals( '#4caf50', $restored_settings['menu_background'] );
		
		// 8. Delete the backup
		$response = $this->perform_rest_request( 
			'DELETE', 
			"/backups/{$backup_id}",
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		$this->assertRestResponseSuccess( $response );
		
		// 9. Verify backup was deleted
		$response = $this->perform_rest_request( 'GET', '/backups' );
		$this->assertRestResponseSuccess( $response );
		$final_backups_data = $response->get_data();
		$final_backups = $final_backups_data['data'];
		
		$backup_still_exists = false;
		foreach ( $final_backups as $backup ) {
			if ( $backup['id'] == $backup_id ) {
				$backup_still_exists = true;
				break;
			}
		}
		$this->assertFalse( $backup_still_exists, 'Backup should be deleted' );
	}
	
	/**
	 * Test complete import/export workflow.
	 */
	public function test_complete_import_export_workflow() {
		// Authenticate as admin
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// 1. Set up initial settings and custom theme
		$initial_settings = array_merge( $this->default_settings, array(
			'menu_background' => '#607d8b',
			'menu_text_color' => '#ffffff',
			'current_theme' => 'custom-export-theme'
		) );
		
		$response = $this->perform_rest_request( 
			'POST', 
			'/settings', 
			$initial_settings,
			array( 'X-WP-Nonce' => $nonce )
		);
		$this->assertRestResponseSuccess( $response );
		
		// Create a custom theme
		$custom_theme = array(
			'id' => 'custom-export-theme',
			'name' => 'Custom Export Theme',
			'settings' => array(
				'menu_background' => '#607d8b',
				'menu_text_color' => '#ffffff'
			)
		);
		
		$response = $this->perform_rest_request( 
			'POST', 
			'/themes', 
			$custom_theme,
			array( 'X-WP-Nonce' => $nonce )
		);
		$this->assertRestResponseSuccess( $response, 201 );
		
		// 2. Export settings
		$response = $this->perform_rest_request( 'GET', '/export' );
		$this->assertRestResponseSuccess( $response );
		$export_data = $response->get_data();
		
		$this->assertArrayHasKey( 'data', $export_data );
		$exported_config = $export_data['data'];
		
		// Verify export structure
		$this->assertArrayHasKey( 'version', $exported_config );
		$this->assertArrayHasKey( 'export_date', $exported_config );
		$this->assertArrayHasKey( 'settings', $exported_config );
		$this->assertArrayHasKey( 'themes', $exported_config );
		$this->assertArrayHasKey( 'metadata', $exported_config );
		
		// Verify exported settings
		$this->assertEquals( '#607d8b', $exported_config['settings']['menu_background'] );
		$this->assertArrayHasKey( 'custom-export-theme', $exported_config['themes'] );
		
		// 3. Change current settings
		$changed_settings = array_merge( $this->default_settings, array(
			'menu_background' => '#795548',
			'menu_text_color' => '#ffffff'
		) );
		
		$response = $this->perform_rest_request( 
			'POST', 
			'/settings', 
			$changed_settings,
			array( 'X-WP-Nonce' => $nonce )
		);
		$this->assertRestResponseSuccess( $response );
		
		// 4. Import the exported configuration
		$response = $this->perform_rest_request( 
			'POST', 
			'/import', 
			$exported_config,
			array( 'X-WP-Nonce' => $nonce )
		);
		$this->assertRestResponseSuccess( $response );
		
		// 5. Verify settings were imported
		$response = $this->perform_rest_request( 'GET', '/settings' );
		$this->assertRestResponseSuccess( $response );
		$imported_data = $response->get_data();
		$imported_settings = $imported_data['data'];
		
		$this->assertEquals( '#607d8b', $imported_settings['menu_background'] );
		$this->assertEquals( 'custom-export-theme', $imported_settings['current_theme'] );
		
		// 6. Verify themes were imported
		$response = $this->perform_rest_request( 'GET', '/themes' );
		$this->assertRestResponseSuccess( $response );
		$themes_data = $response->get_data();
		$themes = $themes_data['data'];
		
		$found_imported_theme = false;
		foreach ( $themes as $theme ) {
			if ( $theme['id'] === 'custom-export-theme' ) {
				$found_imported_theme = true;
				$this->assertEquals( 'Custom Export Theme', $theme['name'] );
				break;
			}
		}
		$this->assertTrue( $found_imported_theme, 'Imported theme should be available' );
	}
	
	/**
	 * Test live preview workflow.
	 */
	public function test_live_preview_workflow() {
		// Authenticate as admin
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// 1. Get current settings
		$response = $this->perform_rest_request( 'GET', '/settings' );
		$this->assertRestResponseSuccess( $response );
		$current_data = $response->get_data();
		$current_settings = $current_data['data'];
		
		// 2. Generate preview with modified settings
		$preview_settings = array_merge( $current_settings, array(
			'menu_background' => '#ff9800',
			'menu_text_color' => '#000000',
			'glassmorphism_enabled' => false
		) );
		
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
		$this->assertArrayHasKey( 'timestamp', $preview_data['data'] );
		
		$generated_css = $preview_data['data']['css'];
		$this->assertNotEmpty( $generated_css );
		$this->assertStringContainsString( '#ff9800', $generated_css );
		$this->assertStringContainsString( '#000000', $generated_css );
		
		// 3. Verify original settings unchanged
		$response = $this->perform_rest_request( 'GET', '/settings' );
		$this->assertRestResponseSuccess( $response );
		$unchanged_data = $response->get_data();
		$unchanged_settings = $unchanged_data['data'];
		
		$this->assertEquals( $current_settings['menu_background'], $unchanged_settings['menu_background'] );
		$this->assertNotEquals( '#ff9800', $unchanged_settings['menu_background'] );
	}
	
	/**
	 * Test error handling across workflows.
	 */
	public function test_error_handling_workflows() {
		// Test unauthorized access
		$response = $this->perform_rest_request( 'POST', '/settings', array( 'menu_background' => '#ff0000' ) );
		$this->assertRestResponseError( $response, 401 );
		
		// Test insufficient permissions
		wp_set_current_user( $this->editor_user_id );
		$response = $this->perform_rest_request( 'POST', '/settings', array( 'menu_background' => '#ff0000' ) );
		$this->assertRestResponseError( $response, 403 );
		
		// Authenticate as admin for remaining tests
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Test invalid data validation
		$invalid_settings = array(
			'menu_background' => 'invalid-color',
			'menu_width' => 'invalid-width'
		);
		
		$response = $this->perform_rest_request( 
			'POST', 
			'/settings', 
			$invalid_settings,
			array( 'X-WP-Nonce' => $nonce )
		);
		$this->assertRestResponseError( $response, 400, 'validation_failed' );
		
		// Test not found errors
		$response = $this->perform_rest_request( 
			'POST', 
			'/themes/nonexistent-theme/apply',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		$this->assertRestResponseError( $response, 404 );
		
		// Test backup restore with invalid ID
		$response = $this->perform_rest_request( 
			'POST', 
			'/backups/999999/restore',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		$this->assertRestResponseError( $response, 404 );
	}
}