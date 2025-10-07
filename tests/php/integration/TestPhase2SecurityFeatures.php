<?php
/**
 * Integration tests for Phase 2 Security Features.
 *
 * Tests Requirements: 5.1, 5.2, 5.3, 5.4
 */

class TestPhase2SecurityFeatures extends MAS_REST_Test_Case {
	
	/**
	 * Rate limiter service instance.
	 *
	 * @var MAS_Rate_Limiter_Service
	 */
	private $rate_limiter_service;
	
	/**
	 * Security logger service instance.
	 *
	 * @var MAS_Security_Logger_Service
	 */
	private $security_logger_service;
	
	/**
	 * Set up test case.
	 */
	public function setUp(): void {
		parent::setUp();
		
		$this->rate_limiter_service = new MAS_Rate_Limiter_Service();
		$this->security_logger_service = new MAS_Security_Logger_Service();
		
		// Clear rate limit data
		delete_transient( 'mas_v2_rate_limit_' . $this->admin_user_id );
		delete_transient( 'mas_v2_rate_limit_ip_' . $this->get_client_ip() );
		
		// Clear audit log
		global $wpdb;
		$table = $wpdb->prefix . 'mas_v2_audit_log';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
			$wpdb->query( "TRUNCATE TABLE {$table}" );
		}
	}
	
	/**
	 * Get client IP for testing.
	 *
	 * @return string
	 */
	private function get_client_ip() {
		return '127.0.0.1';
	}
	
	/**
	 * Test rate limiting across multiple requests.
	 * Requirements: 5.1, 5.2
	 */
	public function test_rate_limiting_across_multiple_requests() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Make requests up to the limit (60 per minute for general requests)
		$success_count = 0;
		$rate_limited_count = 0;
		
		for ( $i = 0; $i < 65; $i++ ) {
			$response = $this->perform_rest_request(
				'GET',
				'/settings',
				array(),
				array( 'X-WP-Nonce' => $nonce )
			);
			
			if ( $response->get_status() === 200 ) {
				$success_count++;
			} elseif ( $response->get_status() === 429 ) {
				$rate_limited_count++;
				
				// Verify Retry-After header is present
				$headers = $response->get_headers();
				$this->assertArrayHasKey( 'Retry-After', $headers );
				$this->assertGreaterThan( 0, $headers['Retry-After'] );
			}
		}
		
		// Should have some successful requests and some rate limited
		$this->assertGreaterThan( 0, $success_count, 'Should have some successful requests' );
		$this->assertGreaterThan( 0, $rate_limited_count, 'Should have some rate limited requests' );
		$this->assertLessThanOrEqual( 60, $success_count, 'Should not exceed rate limit' );
	}
	
	/**
	 * Test rate limiting for settings save operations.
	 * Requirement: 5.2
	 */
	public function test_rate_limiting_for_settings_save() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Settings save has stricter limit (10 per minute)
		$success_count = 0;
		$rate_limited_count = 0;
		
		for ( $i = 0; $i < 15; $i++ ) {
			$settings = array_merge( $this->default_settings, array(
				'menu_background' => sprintf( '#%06x', mt_rand( 0, 0xFFFFFF ) )
			) );
			
			$response = $this->perform_rest_request(
				'POST',
				'/settings',
				$settings,
				array( 'X-WP-Nonce' => $nonce )
			);
			
			if ( $response->get_status() === 200 ) {
				$success_count++;
			} elseif ( $response->get_status() === 429 ) {
				$rate_limited_count++;
			}
		}
		
		$this->assertLessThanOrEqual( 10, $success_count, 'Should not exceed 10 saves per minute' );
		$this->assertGreaterThan( 0, $rate_limited_count, 'Should rate limit after 10 saves' );
	}
	
	/**
	 * Test rate limiting for backup operations.
	 * Requirement: 5.2
	 */
	public function test_rate_limiting_for_backup_operations() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Backup operations have limit (5 per 5 minutes)
		$success_count = 0;
		$rate_limited_count = 0;
		
		for ( $i = 0; $i < 8; $i++ ) {
			$response = $this->perform_rest_request(
				'POST',
				'/backups',
				array(
					'note' => "Rate limit test backup {$i}",
					'type' => 'manual'
				),
				array( 'X-WP-Nonce' => $nonce )
			);
			
			if ( $response->get_status() === 201 ) {
				$success_count++;
			} elseif ( $response->get_status() === 429 ) {
				$rate_limited_count++;
			}
		}
		
		$this->assertLessThanOrEqual( 5, $success_count, 'Should not exceed 5 backups per 5 minutes' );
		$this->assertGreaterThan( 0, $rate_limited_count, 'Should rate limit after 5 backups' );
	}
	
	/**
	 * Test rate limit status endpoint.
	 * Requirement: 5.5
	 */
	public function test_rate_limit_status_endpoint() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Make some requests
		for ( $i = 0; $i < 5; $i++ ) {
			$this->perform_rest_request(
				'GET',
				'/settings',
				array(),
				array( 'X-WP-Nonce' => $nonce )
			);
		}
		
		// Check rate limit status
		$response = $this->perform_rest_request(
			'GET',
			'/security/rate-limit/status',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$status_data = $response->get_data();
		$this->assertArrayHasKey( 'data', $status_data );
		$this->assertArrayHasKey( 'limits', $status_data['data'] );
		
		$limits = $status_data['data']['limits'];
		
		// Verify limit information
		$this->assertArrayHasKey( 'general', $limits );
		$this->assertArrayHasKey( 'settings_save', $limits );
		$this->assertArrayHasKey( 'backup_create', $limits );
		
		// Each limit should have usage info
		foreach ( $limits as $limit_type => $limit_info ) {
			$this->assertArrayHasKey( 'limit', $limit_info );
			$this->assertArrayHasKey( 'remaining', $limit_info );
			$this->assertArrayHasKey( 'reset_time', $limit_info );
		}
	}
	
	/**
	 * Test audit logging for all operations.
	 * Requirements: 5.3, 5.4
	 */
	public function test_audit_logging_for_all_operations() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Perform various operations
		
		// 1. Settings change
		$settings = array_merge( $this->default_settings, array(
			'menu_background' => '#e91e63'
		) );
		
		$this->perform_rest_request(
			'POST',
			'/settings',
			$settings,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// 2. Theme application
		$this->perform_rest_request(
			'POST',
			'/themes/dark/apply',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// 3. Backup creation
		$this->perform_rest_request(
			'POST',
			'/backups',
			array(
				'note' => 'Audit log test backup',
				'type' => 'manual'
			),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// Retrieve audit log
		$response = $this->perform_rest_request(
			'GET',
			'/security/audit-log',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$log_data = $response->get_data();
		$this->assertArrayHasKey( 'data', $log_data );
		$this->assertArrayHasKey( 'entries', $log_data['data'] );
		
		$entries = $log_data['data']['entries'];
		$this->assertGreaterThanOrEqual( 3, count( $entries ), 'Should have at least 3 audit log entries' );
		
		// Verify each entry has required fields
		foreach ( $entries as $entry ) {
			$this->assertArrayHasKey( 'id', $entry );
			$this->assertArrayHasKey( 'user_id', $entry );
			$this->assertArrayHasKey( 'action', $entry );
			$this->assertArrayHasKey( 'timestamp', $entry );
			$this->assertArrayHasKey( 'ip_address', $entry );
			$this->assertArrayHasKey( 'user_agent', $entry );
			$this->assertArrayHasKey( 'details', $entry );
		}
		
		// Verify specific actions were logged
		$actions = array_column( $entries, 'action' );
		$this->assertContains( 'settings_updated', $actions );
		$this->assertContains( 'theme_applied', $actions );
		$this->assertContains( 'backup_created', $actions );
	}
	
	/**
	 * Test audit log filtering.
	 * Requirement: 5.3
	 */
	public function test_audit_log_filtering() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Create various audit entries
		$this->security_logger_service->log_event( 'settings_updated', $this->admin_user_id, array(
			'setting' => 'menu_background',
			'old_value' => '#000000',
			'new_value' => '#ffffff'
		) );
		
		$this->security_logger_service->log_event( 'theme_applied', $this->admin_user_id, array(
			'theme_id' => 'dark'
		) );
		
		$this->security_logger_service->log_event( 'backup_created', $this->admin_user_id, array(
			'backup_id' => 12345
		) );
		
		// Filter by action
		$response = $this->perform_rest_request(
			'GET',
			'/security/audit-log',
			array( 'action' => 'settings_updated' ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$log_data = $response->get_data();
		$entries = $log_data['data']['entries'];
		
		// All entries should be settings_updated
		foreach ( $entries as $entry ) {
			$this->assertEquals( 'settings_updated', $entry['action'] );
		}
		
		// Filter by user
		$response = $this->perform_rest_request(
			'GET',
			'/security/audit-log',
			array( 'user_id' => $this->admin_user_id ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$log_data = $response->get_data();
		$entries = $log_data['data']['entries'];
		
		// All entries should be from admin user
		foreach ( $entries as $entry ) {
			$this->assertEquals( $this->admin_user_id, $entry['user_id'] );
		}
	}
	
	/**
	 * Test audit log pagination.
	 * Requirement: 5.3
	 */
	public function test_audit_log_pagination() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Create many audit entries
		for ( $i = 0; $i < 25; $i++ ) {
			$this->security_logger_service->log_event( 'test_action', $this->admin_user_id, array(
				'test_data' => "Entry {$i}"
			) );
		}
		
		// Get first page
		$response = $this->perform_rest_request(
			'GET',
			'/security/audit-log',
			array(
				'per_page' => 10,
				'page' => 1
			),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$log_data = $response->get_data();
		$this->assertArrayHasKey( 'data', $log_data );
		$this->assertArrayHasKey( 'entries', $log_data['data'] );
		$this->assertArrayHasKey( 'total', $log_data['data'] );
		$this->assertArrayHasKey( 'pages', $log_data['data'] );
		
		$entries_page1 = $log_data['data']['entries'];
		$this->assertCount( 10, $entries_page1, 'First page should have 10 entries' );
		$this->assertGreaterThanOrEqual( 25, $log_data['data']['total'] );
		
		// Get second page
		$response = $this->perform_rest_request(
			'GET',
			'/security/audit-log',
			array(
				'per_page' => 10,
				'page' => 2
			),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$log_data = $response->get_data();
		$entries_page2 = $log_data['data']['entries'];
		$this->assertCount( 10, $entries_page2, 'Second page should have 10 entries' );
		
		// Entries should be different
		$ids_page1 = array_column( $entries_page1, 'id' );
		$ids_page2 = array_column( $entries_page2, 'id' );
		$this->assertEmpty( array_intersect( $ids_page1, $ids_page2 ), 'Pages should have different entries' );
	}
	
	/**
	 * Test suspicious activity detection.
	 * Requirements: 5.4, 5.6
	 */
	public function test_suspicious_activity_detection() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Simulate rapid requests (potential attack)
		for ( $i = 0; $i < 100; $i++ ) {
			$this->security_logger_service->log_event( 'api_request', $this->admin_user_id, array(
				'endpoint' => '/settings',
				'timestamp' => time()
			) );
		}
		
		// Check for suspicious activity
		$suspicious = $this->security_logger_service->check_suspicious_activity( $this->admin_user_id );
		
		$this->assertNotEmpty( $suspicious, 'Should detect suspicious activity' );
		$this->assertIsArray( $suspicious );
		
		// Verify suspicious activity details
		foreach ( $suspicious as $activity ) {
			$this->assertArrayHasKey( 'type', $activity );
			$this->assertArrayHasKey( 'severity', $activity );
			$this->assertArrayHasKey( 'description', $activity );
			$this->assertArrayHasKey( 'timestamp', $activity );
		}
	}
	
	/**
	 * Test failed authentication logging.
	 * Requirement: 5.4
	 */
	public function test_failed_authentication_logging() {
		// Attempt request without authentication
		$response = $this->perform_rest_request( 'GET', '/settings' );
		
		$this->assertEquals( 401, $response->get_status() );
		
		// Verify failed auth was logged
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		$response = $this->perform_rest_request(
			'GET',
			'/security/audit-log',
			array( 'action' => 'auth_failed' ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$log_data = $response->get_data();
		$entries = $log_data['data']['entries'];
		
		// Should have at least one failed auth entry
		$this->assertGreaterThan( 0, count( $entries ), 'Should log failed authentication attempts' );
	}
	
	/**
	 * Test audit log with old and new values.
	 * Requirement: 5.4
	 */
	public function test_audit_log_with_value_changes() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Set initial settings
		$initial_settings = array_merge( $this->default_settings, array(
			'menu_background' => '#000000',
			'menu_text_color' => '#ffffff'
		) );
		
		$this->perform_rest_request(
			'POST',
			'/settings',
			$initial_settings,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// Change settings
		$changed_settings = array_merge( $initial_settings, array(
			'menu_background' => '#ff0000',
			'menu_text_color' => '#000000'
		) );
		
		$this->perform_rest_request(
			'POST',
			'/settings',
			$changed_settings,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// Retrieve audit log
		$response = $this->perform_rest_request(
			'GET',
			'/security/audit-log',
			array( 'action' => 'settings_updated' ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$log_data = $response->get_data();
		$entries = $log_data['data']['entries'];
		
		// Find the latest settings update
		$latest_entry = $entries[0];
		
		$this->assertArrayHasKey( 'details', $latest_entry );
		$details = $latest_entry['details'];
		
		// Should contain old and new values
		$this->assertArrayHasKey( 'changes', $details );
		$changes = $details['changes'];
		
		// Verify changes are tracked
		$this->assertNotEmpty( $changes );
		
		// Each change should have old and new value
		foreach ( $changes as $field => $change ) {
			$this->assertArrayHasKey( 'old', $change );
			$this->assertArrayHasKey( 'new', $change );
		}
	}
	
	/**
	 * Test security features performance.
	 * Requirements: 5.1, 5.3
	 */
	public function test_security_features_performance() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Measure rate limit check performance
		$start_time = microtime( true );
		
		for ( $i = 0; $i < 10; $i++ ) {
			$this->rate_limiter_service->check_rate_limit( $this->admin_user_id, 'general' );
		}
		
		$rate_limit_time = microtime( true ) - $start_time;
		
		$this->assertLessThan( 0.1, $rate_limit_time, 'Rate limit checks should be fast' );
		
		// Measure audit logging performance
		$start_time = microtime( true );
		
		for ( $i = 0; $i < 10; $i++ ) {
			$this->security_logger_service->log_event( 'test_action', $this->admin_user_id, array(
				'test_data' => "Performance test {$i}"
			) );
		}
		
		$logging_time = microtime( true ) - $start_time;
		
		$this->assertLessThan( 0.5, $logging_time, 'Audit logging should be fast' );
		
		// Measure audit log retrieval performance
		$start_time = microtime( true );
		
		$response = $this->perform_rest_request(
			'GET',
			'/security/audit-log',
			array( 'per_page' => 50 ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$retrieval_time = microtime( true ) - $start_time;
		
		$this->assertRestResponseSuccess( $response );
		$this->assertLessThan( 0.3, $retrieval_time, 'Audit log retrieval should be fast' );
	}
	
	/**
	 * Test complete security workflow.
	 * Requirements: 5.1, 5.2, 5.3, 5.4
	 */
	public function test_complete_security_workflow() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Step 1: Check rate limit status
		$response = $this->perform_rest_request(
			'GET',
			'/security/rate-limit/status',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Step 2: Perform operation (should be logged)
		$response = $this->perform_rest_request(
			'POST',
			'/settings',
			array_merge( $this->default_settings, array(
				'menu_background' => '#9c27b0'
			) ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Step 3: Verify operation was logged
		$response = $this->perform_rest_request(
			'GET',
			'/security/audit-log',
			array( 'action' => 'settings_updated' ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$log_data = $response->get_data();
		$entries = $log_data['data']['entries'];
		
		$this->assertGreaterThan( 0, count( $entries ), 'Operation should be logged' );
		
		// Step 4: Check for suspicious activity
		$suspicious = $this->security_logger_service->check_suspicious_activity( $this->admin_user_id );
		
		// Normal operation should not trigger suspicious activity
		$this->assertEmpty( $suspicious, 'Normal operations should not be flagged as suspicious' );
		
		// Step 5: Verify rate limits are being tracked
		$response = $this->perform_rest_request(
			'GET',
			'/security/rate-limit/status',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$status_data = $response->get_data();
		$limits = $status_data['data']['limits'];
		
		// Remaining count should have decreased
		$this->assertLessThan( $limits['settings_save']['limit'], $limits['settings_save']['limit'] - $limits['settings_save']['remaining'] );
	}
}
