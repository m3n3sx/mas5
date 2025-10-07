<?php
/**
 * Integration tests for Phase 2 Backup System features.
 *
 * Tests Requirements: 2.1, 2.2, 2.3, 2.4, 2.5
 */

class TestPhase2BackupSystem extends MAS_REST_Test_Case {
	
	/**
	 * Backup retention service instance.
	 *
	 * @var MAS_Backup_Retention_Service
	 */
	private $backup_retention_service;
	
	/**
	 * Settings service instance.
	 *
	 * @var MAS_Settings_Service
	 */
	private $settings_service;
	
	/**
	 * Set up test case.
	 */
	public function setUp(): void {
		parent::setUp();
		
		$this->backup_retention_service = new MAS_Backup_Retention_Service();
		$this->settings_service = new MAS_Settings_Service();
		
		// Clean up any existing backups
		delete_option( 'mas_v2_backups' );
	}
	
	/**
	 * Test automatic backup creation before changes.
	 * Requirement: 2.1
	 */
	public function test_automatic_backup_before_settings_change() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Set initial settings
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
		
		// Get backup count
		$backups_before = $this->backup_retention_service->list_backups();
		$count_before = count( $backups_before );
		
		// Change settings (should trigger automatic backup)
		$changed_settings = array_merge( $initial_settings, array(
			'menu_background' => '#f44336',
			'menu_hover_background' => '#d32f2f'
		) );
		
		$response = $this->perform_rest_request(
			'POST',
			'/settings',
			$changed_settings,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Verify automatic backup was created
		$backups_after = $this->backup_retention_service->list_backups();
		$count_after = count( $backups_after );
		
		$this->assertGreaterThan( $count_before, $count_after, 'Automatic backup should be created' );
		
		// Find the automatic backup
		$automatic_backup = null;
		foreach ( $backups_after as $backup ) {
			if ( $backup['type'] === 'automatic' ) {
				$automatic_backup = $backup;
				break;
			}
		}
		
		$this->assertNotNull( $automatic_backup, 'Automatic backup should exist' );
		$this->assertArrayHasKey( 'settings', $automatic_backup );
		$this->assertEquals( '#4caf50', $automatic_backup['settings']['menu_background'] );
	}
	
	/**
	 * Test automatic backup before theme application.
	 * Requirement: 2.1
	 */
	public function test_automatic_backup_before_theme_application() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Set initial settings
		$initial_settings = array_merge( $this->default_settings, array(
			'menu_background' => '#2196f3'
		) );
		
		$this->settings_service->save_settings( $initial_settings );
		
		// Get backup count
		$backups_before = $this->backup_retention_service->list_backups();
		$count_before = count( $backups_before );
		
		// Apply a theme (should trigger automatic backup)
		$response = $this->perform_rest_request(
			'POST',
			'/themes/dark/apply',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Verify automatic backup was created
		$backups_after = $this->backup_retention_service->list_backups();
		$count_after = count( $backups_after );
		
		$this->assertGreaterThan( $count_before, $count_after, 'Automatic backup should be created before theme application' );
		
		// Verify backup contains pre-theme settings
		$latest_backup = $backups_after[0];
		$this->assertEquals( 'automatic', $latest_backup['type'] );
		$this->assertEquals( '#2196f3', $latest_backup['settings']['menu_background'] );
	}
	
	/**
	 * Test automatic backup before import.
	 * Requirement: 2.1
	 */
	public function test_automatic_backup_before_import() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Set initial settings
		$initial_settings = array_merge( $this->default_settings, array(
			'menu_background' => '#ff9800'
		) );
		
		$this->settings_service->save_settings( $initial_settings );
		
		// Get backup count
		$backups_before = $this->backup_retention_service->list_backups();
		$count_before = count( $backups_before );
		
		// Prepare import data
		$import_data = array(
			'settings' => array_merge( $this->default_settings, array(
				'menu_background' => '#9c27b0'
			) ),
			'version' => MAS_VERSION
		);
		
		// Import settings (should trigger automatic backup)
		$response = $this->perform_rest_request(
			'POST',
			'/import',
			$import_data,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Verify automatic backup was created
		$backups_after = $this->backup_retention_service->list_backups();
		$count_after = count( $backups_after );
		
		$this->assertGreaterThan( $count_before, $count_after, 'Automatic backup should be created before import' );
		
		// Verify backup contains pre-import settings
		$latest_backup = $backups_after[0];
		$this->assertEquals( 'automatic', $latest_backup['type'] );
		$this->assertEquals( '#ff9800', $latest_backup['settings']['menu_background'] );
	}
	
	/**
	 * Test backup retention policy enforcement.
	 * Requirements: 2.4, 2.7
	 */
	public function test_backup_retention_policy_enforcement() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Create more than 30 automatic backups
		for ( $i = 0; $i < 35; $i++ ) {
			$backup_data = array(
				'settings' => array_merge( $this->default_settings, array(
					'menu_background' => sprintf( '#%06x', mt_rand( 0, 0xFFFFFF ) )
				) ),
				'type' => 'automatic',
				'timestamp' => time() - ( $i * 3600 ), // 1 hour apart
				'metadata' => array(
					'user_id' => $this->admin_user_id,
					'note' => "Automatic backup {$i}"
				)
			);
			
			$this->backup_retention_service->create_backup( $backup_data['settings'], 'automatic', $backup_data['metadata']['note'] );
		}
		
		// Trigger cleanup
		$response = $this->perform_rest_request(
			'POST',
			'/backups/cleanup',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Verify retention policy was enforced
		$backups_after_cleanup = $this->backup_retention_service->list_backups();
		$automatic_backups = array_filter( $backups_after_cleanup, function( $backup ) {
			return $backup['type'] === 'automatic';
		} );
		
		$this->assertLessThanOrEqual( 30, count( $automatic_backups ), 'Should keep maximum 30 automatic backups' );
	}
	
	/**
	 * Test manual backups are preserved during cleanup.
	 * Requirement: 2.7
	 */
	public function test_manual_backups_preserved_during_cleanup() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Create manual backups
		$manual_backup_ids = array();
		for ( $i = 0; $i < 5; $i++ ) {
			$response = $this->perform_rest_request(
				'POST',
				'/backups',
				array(
					'note' => "Manual backup {$i}",
					'type' => 'manual'
				),
				array( 'X-WP-Nonce' => $nonce )
			);
			
			$this->assertRestResponseSuccess( $response, 201 );
			$data = $response->get_data();
			$manual_backup_ids[] = $data['data']['backup_id'];
		}
		
		// Create many automatic backups
		for ( $i = 0; $i < 40; $i++ ) {
			$this->backup_retention_service->create_backup(
				$this->default_settings,
				'automatic',
				"Auto backup {$i}"
			);
		}
		
		// Trigger cleanup
		$response = $this->perform_rest_request(
			'POST',
			'/backups/cleanup',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Verify all manual backups still exist
		$backups_after = $this->backup_retention_service->list_backups();
		$manual_backups = array_filter( $backups_after, function( $backup ) {
			return $backup['type'] === 'manual';
		} );
		
		$this->assertCount( 5, $manual_backups, 'All manual backups should be preserved' );
		
		// Verify manual backup IDs are still present
		$remaining_ids = array_column( $manual_backups, 'id' );
		foreach ( $manual_backup_ids as $manual_id ) {
			$this->assertContains( $manual_id, $remaining_ids, "Manual backup {$manual_id} should be preserved" );
		}
	}
	
	/**
	 * Test backup download workflow.
	 * Requirements: 2.2, 2.5
	 */
	public function test_backup_download_workflow() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Create a backup with specific settings
		$backup_settings = array_merge( $this->default_settings, array(
			'menu_background' => '#673ab7',
			'menu_text_color' => '#ffffff',
			'glassmorphism_enabled' => true,
			'glassmorphism_blur' => '18px'
		) );
		
		$response = $this->perform_rest_request(
			'POST',
			'/backups',
			array(
				'settings' => $backup_settings,
				'note' => 'Download test backup',
				'type' => 'manual'
			),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response, 201 );
		
		$backup_data = $response->get_data();
		$backup_id = $backup_data['data']['backup_id'];
		
		// Download the backup
		$response = $this->perform_rest_request(
			'GET',
			"/backups/{$backup_id}/download",
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Verify response headers
		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'Content-Type', $headers );
		$this->assertEquals( 'application/json', $headers['Content-Type'] );
		$this->assertArrayHasKey( 'Content-Disposition', $headers );
		$this->assertStringContainsString( 'attachment', $headers['Content-Disposition'] );
		$this->assertStringContainsString( 'backup', $headers['Content-Disposition'] );
		
		// Verify download data
		$download_data = $response->get_data();
		$this->assertArrayHasKey( 'data', $download_data );
		$this->assertArrayHasKey( 'backup', $download_data['data'] );
		
		$backup = $download_data['data']['backup'];
		$this->assertEquals( $backup_id, $backup['id'] );
		$this->assertEquals( '#673ab7', $backup['settings']['menu_background'] );
		$this->assertTrue( $backup['settings']['glassmorphism_enabled'] );
	}
	
	/**
	 * Test backup metadata tracking.
	 * Requirements: 2.3, 2.5
	 */
	public function test_backup_metadata_tracking() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Create backup with custom note
		$custom_note = 'Before major redesign - includes all custom colors';
		$response = $this->perform_rest_request(
			'POST',
			'/backups',
			array(
				'note' => $custom_note,
				'type' => 'manual'
			),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response, 201 );
		
		$backup_data = $response->get_data();
		$backup_id = $backup_data['data']['backup_id'];
		
		// Retrieve backup list
		$response = $this->perform_rest_request( 'GET', '/backups' );
		$this->assertRestResponseSuccess( $response );
		
		$list_data = $response->get_data();
		$backups = $list_data['data']['backups'];
		
		// Find our backup
		$our_backup = null;
		foreach ( $backups as $backup ) {
			if ( $backup['id'] === $backup_id ) {
				$our_backup = $backup;
				break;
			}
		}
		
		$this->assertNotNull( $our_backup, 'Backup should be in list' );
		
		// Verify metadata
		$this->assertArrayHasKey( 'metadata', $our_backup );
		$metadata = $our_backup['metadata'];
		
		$this->assertArrayHasKey( 'user_id', $metadata );
		$this->assertEquals( $this->admin_user_id, $metadata['user_id'] );
		
		$this->assertArrayHasKey( 'note', $metadata );
		$this->assertEquals( $custom_note, $metadata['note'] );
		
		$this->assertArrayHasKey( 'size', $metadata );
		$this->assertGreaterThan( 0, $metadata['size'] );
		
		$this->assertArrayHasKey( 'settings_count', $metadata );
		$this->assertGreaterThan( 0, $metadata['settings_count'] );
		
		$this->assertArrayHasKey( 'checksum', $metadata );
		$this->assertNotEmpty( $metadata['checksum'] );
		
		$this->assertArrayHasKey( 'plugin_version', $metadata );
		$this->assertArrayHasKey( 'wordpress_version', $metadata );
	}
	
	/**
	 * Test batch backup operations.
	 * Requirement: 2.3
	 */
	public function test_batch_backup_operations() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Create multiple backups
		$backup_ids = array();
		for ( $i = 0; $i < 5; $i++ ) {
			$response = $this->perform_rest_request(
				'POST',
				'/backups',
				array(
					'note' => "Batch test backup {$i}",
					'type' => 'manual'
				),
				array( 'X-WP-Nonce' => $nonce )
			);
			
			$data = $response->get_data();
			$backup_ids[] = $data['data']['backup_id'];
		}
		
		// Perform batch delete
		$response = $this->perform_rest_request(
			'POST',
			'/backups/batch',
			array(
				'operation' => 'delete',
				'backup_ids' => array_slice( $backup_ids, 0, 3 )
			),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$batch_result = $response->get_data();
		$this->assertArrayHasKey( 'data', $batch_result );
		$this->assertArrayHasKey( 'deleted', $batch_result['data'] );
		$this->assertEquals( 3, $batch_result['data']['deleted'] );
		
		// Verify backups were deleted
		$response = $this->perform_rest_request( 'GET', '/backups' );
		$list_data = $response->get_data();
		$remaining_backups = $list_data['data']['backups'];
		
		$remaining_ids = array_column( $remaining_backups, 'id' );
		
		// First 3 should be deleted
		for ( $i = 0; $i < 3; $i++ ) {
			$this->assertNotContains( $backup_ids[ $i ], $remaining_ids );
		}
		
		// Last 2 should remain
		for ( $i = 3; $i < 5; $i++ ) {
			$this->assertContains( $backup_ids[ $i ], $remaining_ids );
		}
	}
	
	/**
	 * Test backup restoration with validation.
	 * Requirement: 2.2
	 */
	public function test_backup_restoration_with_validation() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Set initial settings
		$initial_settings = array_merge( $this->default_settings, array(
			'menu_background' => '#00bcd4',
			'menu_text_color' => '#ffffff',
			'glassmorphism_enabled' => true
		) );
		
		$this->settings_service->save_settings( $initial_settings );
		
		// Create backup
		$response = $this->perform_rest_request(
			'POST',
			'/backups',
			array(
				'note' => 'Restoration test backup',
				'type' => 'manual'
			),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$backup_data = $response->get_data();
		$backup_id = $backup_data['data']['backup_id'];
		
		// Change settings
		$changed_settings = array_merge( $initial_settings, array(
			'menu_background' => '#e91e63',
			'glassmorphism_enabled' => false
		) );
		
		$this->settings_service->save_settings( $changed_settings );
		
		// Verify settings changed
		$current = $this->settings_service->get_settings();
		$this->assertEquals( '#e91e63', $current['menu_background'] );
		$this->assertFalse( $current['glassmorphism_enabled'] );
		
		// Restore backup
		$response = $this->perform_rest_request(
			'POST',
			"/backups/{$backup_id}/restore",
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Verify settings were restored
		$restored = $this->settings_service->get_settings();
		$this->assertEquals( '#00bcd4', $restored['menu_background'] );
		$this->assertEquals( '#ffffff', $restored['menu_text_color'] );
		$this->assertTrue( $restored['glassmorphism_enabled'] );
	}
	
	/**
	 * Test backup age-based retention.
	 * Requirement: 2.4
	 */
	public function test_backup_age_based_retention() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Create old automatic backups (older than 30 days)
		$old_timestamp = time() - ( 35 * DAY_IN_SECONDS );
		for ( $i = 0; $i < 5; $i++ ) {
			$backup_data = array(
				'settings' => $this->default_settings,
				'type' => 'automatic',
				'timestamp' => $old_timestamp - ( $i * 3600 ),
				'metadata' => array(
					'user_id' => $this->admin_user_id,
					'note' => "Old automatic backup {$i}"
				)
			);
			
			// Manually insert old backup
			$backups = get_option( 'mas_v2_backups', array() );
			$backups[] = $backup_data;
			update_option( 'mas_v2_backups', $backups );
		}
		
		// Create recent automatic backups
		for ( $i = 0; $i < 3; $i++ ) {
			$this->backup_retention_service->create_backup(
				$this->default_settings,
				'automatic',
				"Recent automatic backup {$i}"
			);
		}
		
		// Trigger cleanup
		$response = $this->perform_rest_request(
			'POST',
			'/backups/cleanup',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Verify old backups were removed
		$backups_after = $this->backup_retention_service->list_backups();
		$automatic_backups = array_filter( $backups_after, function( $backup ) {
			return $backup['type'] === 'automatic';
		} );
		
		// Should only have recent backups
		$this->assertCount( 3, $automatic_backups, 'Only recent automatic backups should remain' );
		
		// Verify all remaining backups are recent
		foreach ( $automatic_backups as $backup ) {
			$age_days = ( time() - $backup['timestamp'] ) / DAY_IN_SECONDS;
			$this->assertLessThan( 30, $age_days, 'Remaining backups should be less than 30 days old' );
		}
	}
	
	/**
	 * Test backup system performance with many backups.
	 * Requirement: 2.2, 2.3
	 */
	public function test_backup_system_performance() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Create many backups
		$start_time = microtime( true );
		
		for ( $i = 0; $i < 20; $i++ ) {
			$this->backup_retention_service->create_backup(
				$this->default_settings,
				'automatic',
				"Performance test backup {$i}"
			);
		}
		
		$creation_time = microtime( true ) - $start_time;
		
		// List backups
		$start_time = microtime( true );
		$response = $this->perform_rest_request( 'GET', '/backups' );
		$list_time = microtime( true ) - $start_time;
		
		$this->assertRestResponseSuccess( $response );
		
		// Performance assertions
		$this->assertLessThan( 2.0, $creation_time, 'Creating 20 backups should take less than 2 seconds' );
		$this->assertLessThan( 0.5, $list_time, 'Listing backups should take less than 0.5 seconds' );
		
		// Test cleanup performance
		$start_time = microtime( true );
		$response = $this->perform_rest_request(
			'POST',
			'/backups/cleanup',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		$cleanup_time = microtime( true ) - $start_time;
		
		$this->assertRestResponseSuccess( $response );
		$this->assertLessThan( 1.0, $cleanup_time, 'Cleanup should take less than 1 second' );
	}
}
