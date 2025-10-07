<?php
/**
 * Integration tests for Phase 2 Batch Operations and Transaction Support.
 *
 * Tests Requirements: 6.1, 6.2, 6.3, 6.4
 */

class TestPhase2BatchOperations extends MAS_REST_Test_Case {
	
	/**
	 * Transaction service instance.
	 *
	 * @var MAS_Transaction_Service
	 */
	private $transaction_service;
	
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
		
		$this->transaction_service = new MAS_Transaction_Service();
		$this->settings_service = new MAS_Settings_Service();
	}
	
	/**
	 * Test batch settings update with rollback.
	 * Requirements: 6.1, 6.2, 6.5
	 */
	public function test_batch_settings_update_with_rollback() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Set initial settings
		$initial_settings = array_merge( $this->default_settings, array(
			'menu_background' => '#000000',
			'menu_text_color' => '#ffffff',
			'menu_hover_background' => '#111111'
		) );
		
		$this->settings_service->save_settings( $initial_settings );
		
		// Prepare batch update with one invalid setting
		$batch_updates = array(
			array(
				'key' => 'menu_background',
				'value' => '#ff0000'
			),
			array(
				'key' => 'menu_text_color',
				'value' => '#00ff00'
			),
			array(
				'key' => 'menu_hover_background',
				'value' => 'invalid-color' // This will cause failure
			)
		);
		
		// Perform batch update
		$response = $this->perform_rest_request(
			'POST',
			'/settings/batch',
			array( 'updates' => $batch_updates ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// Should fail due to invalid color
		$this->assertRestResponseError( $response, 400 );
		
		// Verify rollback - settings should remain unchanged
		$current_settings = $this->settings_service->get_settings();
		$this->assertEquals( '#000000', $current_settings['menu_background'], 'Settings should be rolled back' );
		$this->assertEquals( '#ffffff', $current_settings['menu_text_color'], 'Settings should be rolled back' );
		$this->assertEquals( '#111111', $current_settings['menu_hover_background'], 'Settings should be rolled back' );
	}
	
	/**
	 * Test successful batch settings update.
	 * Requirements: 6.1, 6.2
	 */
	public function test_successful_batch_settings_update() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Set initial settings
		$initial_settings = array_merge( $this->default_settings, array(
			'menu_background' => '#000000',
			'menu_text_color' => '#ffffff',
			'menu_hover_background' => '#111111'
		) );
		
		$this->settings_service->save_settings( $initial_settings );
		
		// Prepare valid batch update
		$batch_updates = array(
			array(
				'key' => 'menu_background',
				'value' => '#e91e63'
			),
			array(
				'key' => 'menu_text_color',
				'value' => '#ffffff'
			),
			array(
				'key' => 'menu_hover_background',
				'value' => '#c2185b'
			),
			array(
				'key' => 'glassmorphism_enabled',
				'value' => true
			)
		);
		
		// Perform batch update
		$response = $this->perform_rest_request(
			'POST',
			'/settings/batch',
			array( 'updates' => $batch_updates ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$batch_data = $response->get_data();
		$this->assertArrayHasKey( 'data', $batch_data );
		$this->assertArrayHasKey( 'updated', $batch_data['data'] );
		$this->assertEquals( 4, $batch_data['data']['updated'] );
		
		// Verify all settings were updated
		$current_settings = $this->settings_service->get_settings();
		$this->assertEquals( '#e91e63', $current_settings['menu_background'] );
		$this->assertEquals( '#ffffff', $current_settings['menu_text_color'] );
		$this->assertEquals( '#c2185b', $current_settings['menu_hover_background'] );
		$this->assertTrue( $current_settings['glassmorphism_enabled'] );
	}
	
	/**
	 * Test transaction commit and rollback.
	 * Requirements: 6.1, 12.1, 12.2, 12.3, 12.4
	 */
	public function test_transaction_commit_and_rollback() {
		// Test commit
		$transaction_id = $this->transaction_service->begin_transaction();
		$this->assertNotEmpty( $transaction_id );
		
		// Add operations to transaction
		$this->transaction_service->add_operation( $transaction_id, 'update_setting', array(
			'key' => 'menu_background',
			'value' => '#2196f3'
		) );
		
		$this->transaction_service->add_operation( $transaction_id, 'update_setting', array(
			'key' => 'menu_text_color',
			'value' => '#ffffff'
		) );
		
		// Commit transaction
		$result = $this->transaction_service->commit( $transaction_id );
		$this->assertTrue( $result );
		
		// Verify changes were applied
		$settings = $this->settings_service->get_settings();
		$this->assertEquals( '#2196f3', $settings['menu_background'] );
		$this->assertEquals( '#ffffff', $settings['menu_text_color'] );
		
		// Test rollback
		$transaction_id = $this->transaction_service->begin_transaction();
		
		// Add operations
		$this->transaction_service->add_operation( $transaction_id, 'update_setting', array(
			'key' => 'menu_background',
			'value' => '#ff0000'
		) );
		
		// Rollback transaction
		$result = $this->transaction_service->rollback( $transaction_id );
		$this->assertTrue( $result );
		
		// Verify changes were NOT applied
		$settings = $this->settings_service->get_settings();
		$this->assertEquals( '#2196f3', $settings['menu_background'], 'Settings should not change after rollback' );
	}
	
	/**
	 * Test batch backup operations.
	 * Requirement: 6.3
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
		
		$batch_data = $response->get_data();
		$this->assertArrayHasKey( 'data', $batch_data );
		$this->assertArrayHasKey( 'success_count', $batch_data['data'] );
		$this->assertArrayHasKey( 'error_count', $batch_data['data'] );
		$this->assertEquals( 3, $batch_data['data']['success_count'] );
		$this->assertEquals( 0, $batch_data['data']['error_count'] );
		
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
	 * Test batch theme application with validation.
	 * Requirement: 6.4
	 */
	public function test_batch_theme_application() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Create custom themes
		$theme_ids = array();
		for ( $i = 1; $i <= 3; $i++ ) {
			$theme = array(
				'id' => "batch-theme-{$i}",
				'name' => "Batch Theme {$i}",
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
			$theme_ids[] = "batch-theme-{$i}";
		}
		
		// Apply theme using batch endpoint (with validation)
		$response = $this->perform_rest_request(
			'POST',
			'/themes/batch-apply',
			array(
				'theme_id' => $theme_ids[1],
				'validate' => true
			),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Verify theme was applied
		$settings = $this->settings_service->get_settings();
		$this->assertEquals( 'batch-theme-2', $settings['current_theme'] );
		
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
	
	/**
	 * Test async batch processing for large batches.
	 * Requirement: 6.7
	 */
	public function test_async_batch_processing() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Create a large batch (> 50 items)
		$large_batch = array();
		for ( $i = 0; $i < 60; $i++ ) {
			$large_batch[] = array(
				'key' => "test_setting_{$i}",
				'value' => "test_value_{$i}"
			);
		}
		
		// Submit large batch
		$response = $this->perform_rest_request(
			'POST',
			'/settings/batch',
			array(
				'updates' => $large_batch,
				'async' => true
			),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response, 202 ); // 202 Accepted for async
		
		$batch_data = $response->get_data();
		$this->assertArrayHasKey( 'data', $batch_data );
		$this->assertArrayHasKey( 'batch_id', $batch_data['data'] );
		$this->assertArrayHasKey( 'status', $batch_data['data'] );
		$this->assertEquals( 'processing', $batch_data['data']['status'] );
		
		$batch_id = $batch_data['data']['batch_id'];
		
		// Check batch status
		$response = $this->perform_rest_request(
			'GET',
			"/batch/{$batch_id}/status",
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$status_data = $response->get_data();
		$this->assertArrayHasKey( 'data', $status_data );
		$this->assertArrayHasKey( 'status', $status_data['data'] );
		$this->assertArrayHasKey( 'progress', $status_data['data'] );
		
		// Status should be one of: pending, processing, completed, failed
		$this->assertContains( $status_data['data']['status'], array( 'pending', 'processing', 'completed', 'failed' ) );
	}
	
	/**
	 * Test partial failure handling in batch operations.
	 * Requirement: 6.6
	 */
	public function test_partial_failure_handling() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Create batch with some valid and some invalid operations
		$mixed_batch = array(
			array(
				'key' => 'menu_background',
				'value' => '#ff0000' // Valid
			),
			array(
				'key' => 'menu_text_color',
				'value' => 'invalid-color' // Invalid
			),
			array(
				'key' => 'menu_hover_background',
				'value' => '#00ff00' // Valid
			),
			array(
				'key' => 'menu_width',
				'value' => 'invalid-width' // Invalid
			)
		);
		
		// Perform batch update with partial failure handling
		$response = $this->perform_rest_request(
			'POST',
			'/settings/batch',
			array(
				'updates' => $mixed_batch,
				'continue_on_error' => true
			),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// Should return partial success
		$this->assertEquals( 207, $response->get_status() ); // 207 Multi-Status
		
		$batch_data = $response->get_data();
		$this->assertArrayHasKey( 'data', $batch_data );
		$this->assertArrayHasKey( 'success_count', $batch_data['data'] );
		$this->assertArrayHasKey( 'error_count', $batch_data['data'] );
		$this->assertArrayHasKey( 'errors', $batch_data['data'] );
		
		$this->assertEquals( 2, $batch_data['data']['success_count'] );
		$this->assertEquals( 2, $batch_data['data']['error_count'] );
		$this->assertCount( 2, $batch_data['data']['errors'] );
		
		// Verify valid operations were applied
		$settings = $this->settings_service->get_settings();
		$this->assertEquals( '#ff0000', $settings['menu_background'] );
		$this->assertEquals( '#00ff00', $settings['menu_hover_background'] );
	}
	
	/**
	 * Test transaction state backup and restore.
	 * Requirements: 12.5, 12.6
	 */
	public function test_transaction_state_backup_and_restore() {
		// Set initial state
		$initial_settings = array_merge( $this->default_settings, array(
			'menu_background' => '#000000',
			'menu_text_color' => '#ffffff'
		) );
		
		$this->settings_service->save_settings( $initial_settings );
		
		// Begin transaction and create state backup
		$transaction_id = $this->transaction_service->begin_transaction();
		$backup_id = $this->transaction_service->create_state_backup( $transaction_id );
		
		$this->assertNotEmpty( $backup_id );
		
		// Make changes
		$changed_settings = array_merge( $initial_settings, array(
			'menu_background' => '#ff0000',
			'menu_text_color' => '#00ff00'
		) );
		
		$this->settings_service->save_settings( $changed_settings );
		
		// Verify changes were applied
		$current = $this->settings_service->get_settings();
		$this->assertEquals( '#ff0000', $current['menu_background'] );
		$this->assertEquals( '#00ff00', $current['menu_text_color'] );
		
		// Restore state from backup
		$result = $this->transaction_service->restore_state_backup( $transaction_id, $backup_id );
		$this->assertTrue( $result );
		
		// Verify state was restored
		$restored = $this->settings_service->get_settings();
		$this->assertEquals( '#000000', $restored['menu_background'] );
		$this->assertEquals( '#ffffff', $restored['menu_text_color'] );
	}
	
	/**
	 * Test batch operation with transaction support.
	 * Requirements: 6.1, 6.2
	 */
	public function test_batch_operation_with_transaction() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Set initial settings
		$initial_settings = array_merge( $this->default_settings, array(
			'menu_background' => '#000000'
		) );
		
		$this->settings_service->save_settings( $initial_settings );
		
		// Perform batch operation within transaction
		$batch_updates = array(
			array(
				'key' => 'menu_background',
				'value' => '#e91e63'
			),
			array(
				'key' => 'menu_text_color',
				'value' => '#ffffff'
			),
			array(
				'key' => 'glassmorphism_enabled',
				'value' => true
			)
		);
		
		$response = $this->perform_rest_request(
			'POST',
			'/settings/batch',
			array(
				'updates' => $batch_updates,
				'use_transaction' => true
			),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$batch_data = $response->get_data();
		$this->assertArrayHasKey( 'data', $batch_data );
		$this->assertArrayHasKey( 'transaction_id', $batch_data['data'] );
		$this->assertArrayHasKey( 'committed', $batch_data['data'] );
		$this->assertTrue( $batch_data['data']['committed'] );
		
		// Verify all changes were applied atomically
		$settings = $this->settings_service->get_settings();
		$this->assertEquals( '#e91e63', $settings['menu_background'] );
		$this->assertEquals( '#ffffff', $settings['menu_text_color'] );
		$this->assertTrue( $settings['glassmorphism_enabled'] );
	}
	
	/**
	 * Test batch operations performance.
	 * Requirements: 6.1, 6.2
	 */
	public function test_batch_operations_performance() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Create batch with 10 items
		$batch_updates = array();
		for ( $i = 0; $i < 10; $i++ ) {
			$batch_updates[] = array(
				'key' => "test_setting_{$i}",
				'value' => "test_value_{$i}"
			);
		}
		
		// Measure batch operation time
		$start_time = microtime( true );
		
		$response = $this->perform_rest_request(
			'POST',
			'/settings/batch',
			array( 'updates' => $batch_updates ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$batch_time = microtime( true ) - $start_time;
		
		$this->assertRestResponseSuccess( $response );
		$this->assertLessThan( 1.0, $batch_time, 'Batch of 10 items should complete in under 1 second' );
		
		// Verify all updates were applied
		$batch_data = $response->get_data();
		$this->assertEquals( 10, $batch_data['data']['updated'] );
	}
	
	/**
	 * Test complete batch operations workflow.
	 * Requirements: 6.1, 6.2, 6.3, 6.4
	 */
	public function test_complete_batch_operations_workflow() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Step 1: Begin transaction
		$transaction_id = $this->transaction_service->begin_transaction();
		$this->assertNotEmpty( $transaction_id );
		
		// Step 2: Create state backup
		$backup_id = $this->transaction_service->create_state_backup( $transaction_id );
		$this->assertNotEmpty( $backup_id );
		
		// Step 3: Perform batch settings update
		$batch_updates = array(
			array(
				'key' => 'menu_background',
				'value' => '#9c27b0'
			),
			array(
				'key' => 'menu_text_color',
				'value' => '#ffffff'
			),
			array(
				'key' => 'glassmorphism_enabled',
				'value' => true
			)
		);
		
		$response = $this->perform_rest_request(
			'POST',
			'/settings/batch',
			array( 'updates' => $batch_updates ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Step 4: Verify changes
		$settings = $this->settings_service->get_settings();
		$this->assertEquals( '#9c27b0', $settings['menu_background'] );
		$this->assertTrue( $settings['glassmorphism_enabled'] );
		
		// Step 5: Commit transaction
		$result = $this->transaction_service->commit( $transaction_id );
		$this->assertTrue( $result );
		
		// Step 6: Verify final state
		$final_settings = $this->settings_service->get_settings();
		$this->assertEquals( '#9c27b0', $final_settings['menu_background'] );
		$this->assertEquals( '#ffffff', $final_settings['menu_text_color'] );
		$this->assertTrue( $final_settings['glassmorphism_enabled'] );
	}
}
