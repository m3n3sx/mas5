<?php
/**
 * Integration tests for Phase 2 Webhook Support.
 *
 * Tests Requirements: 10.1, 10.2, 10.3, 10.4
 */

class TestPhase2Webhooks extends MAS_REST_Test_Case {
	
	/**
	 * Webhook service instance.
	 *
	 * @var MAS_Webhook_Service
	 */
	private $webhook_service;
	
	/**
	 * Test webhook URL.
	 *
	 * @var string
	 */
	private $test_webhook_url = 'https://webhook.site/test-endpoint';
	
	/**
	 * Set up test case.
	 */
	public function setUp(): void {
		parent::setUp();
		
		$this->webhook_service = new MAS_Webhook_Service();
		
		// Clear webhook data
		global $wpdb;
		$webhooks_table = $wpdb->prefix . 'mas_v2_webhooks';
		$deliveries_table = $wpdb->prefix . 'mas_v2_webhook_deliveries';
		
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$webhooks_table}'" ) === $webhooks_table ) {
			$wpdb->query( "TRUNCATE TABLE {$webhooks_table}" );
		}
		
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$deliveries_table}'" ) === $deliveries_table ) {
			$wpdb->query( "TRUNCATE TABLE {$deliveries_table}" );
		}
	}
	
	/**
	 * Test webhook registration and delivery.
	 * Requirements: 10.1, 10.2
	 */
	public function test_webhook_registration_and_delivery() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Register webhook
		$webhook_data = array(
			'url' => $this->test_webhook_url,
			'events' => array( 'settings.updated', 'theme.applied' ),
			'secret' => 'test-secret-key',
			'active' => true
		);
		
		$response = $this->perform_rest_request(
			'POST',
			'/webhooks',
			$webhook_data,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response, 201 );
		
		$webhook_response = $response->get_data();
		$this->assertArrayHasKey( 'data', $webhook_response );
		$this->assertArrayHasKey( 'webhook_id', $webhook_response['data'] );
		
		$webhook_id = $webhook_response['data']['webhook_id'];
		
		// Verify webhook was registered
		$response = $this->perform_rest_request(
			'GET',
			'/webhooks',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$webhooks_data = $response->get_data();
		$webhooks = $webhooks_data['data']['webhooks'];
		
		$this->assertCount( 1, $webhooks );
		$this->assertEquals( $webhook_id, $webhooks[0]['id'] );
		$this->assertEquals( $this->test_webhook_url, $webhooks[0]['url'] );
		$this->assertContains( 'settings.updated', $webhooks[0]['events'] );
		$this->assertContains( 'theme.applied', $webhooks[0]['events'] );
	}
	
	/**
	 * Test webhook delivery with HMAC signature.
	 * Requirement: 10.3
	 */
	public function test_webhook_delivery_with_signature() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Register webhook
		$secret = 'test-secret-' . wp_rand();
		$webhook_data = array(
			'url' => $this->test_webhook_url,
			'events' => array( 'settings.updated' ),
			'secret' => $secret,
			'active' => true
		);
		
		$response = $this->perform_rest_request(
			'POST',
			'/webhooks',
			$webhook_data,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$webhook_id = $response->get_data()['data']['webhook_id'];
		
		// Trigger event that should fire webhook
		$settings = array_merge( $this->default_settings, array(
			'menu_background' => '#e91e63'
		) );
		
		$this->perform_rest_request(
			'POST',
			'/settings',
			$settings,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// Check webhook deliveries
		$response = $this->perform_rest_request(
			'GET',
			"/webhooks/{$webhook_id}/deliveries",
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$deliveries_data = $response->get_data();
		$deliveries = $deliveries_data['data']['deliveries'];
		
		$this->assertGreaterThan( 0, count( $deliveries ), 'Should have webhook delivery' );
		
		// Verify delivery has signature
		$delivery = $deliveries[0];
		$this->assertArrayHasKey( 'signature', $delivery );
		$this->assertNotEmpty( $delivery['signature'] );
		
		// Verify signature format (should be HMAC-SHA256)
		$this->assertStringStartsWith( 'sha256=', $delivery['signature'] );
		
		// Verify signature is valid
		$payload = json_encode( $delivery['payload'] );
		$expected_signature = 'sha256=' . hash_hmac( 'sha256', $payload, $secret );
		$this->assertEquals( $expected_signature, $delivery['signature'] );
	}
	
	/**
	 * Test webhook retry mechanism on failure.
	 * Requirement: 10.3
	 */
	public function test_webhook_retry_mechanism() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Register webhook with invalid URL (will fail)
		$webhook_data = array(
			'url' => 'https://invalid-webhook-url-that-does-not-exist.test',
			'events' => array( 'settings.updated' ),
			'secret' => 'test-secret',
			'active' => true
		);
		
		$response = $this->perform_rest_request(
			'POST',
			'/webhooks',
			$webhook_data,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$webhook_id = $response->get_data()['data']['webhook_id'];
		
		// Trigger event
		$settings = array_merge( $this->default_settings, array(
			'menu_background' => '#9c27b0'
		) );
		
		$this->perform_rest_request(
			'POST',
			'/settings',
			$settings,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// Wait a moment for delivery attempt
		sleep( 1 );
		
		// Check deliveries
		$response = $this->perform_rest_request(
			'GET',
			"/webhooks/{$webhook_id}/deliveries",
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$deliveries_data = $response->get_data();
		$deliveries = $deliveries_data['data']['deliveries'];
		
		$this->assertGreaterThan( 0, count( $deliveries ), 'Should have delivery attempt' );
		
		$delivery = $deliveries[0];
		
		// Verify delivery failed
		$this->assertEquals( 'failed', $delivery['status'] );
		
		// Verify retry information
		$this->assertArrayHasKey( 'attempts', $delivery );
		$this->assertGreaterThan( 0, $delivery['attempts'] );
		
		// Verify next retry time is set
		$this->assertArrayHasKey( 'next_retry', $delivery );
		$this->assertNotEmpty( $delivery['next_retry'] );
	}
	
	/**
	 * Test webhook delivery history tracking.
	 * Requirement: 10.4
	 */
	public function test_webhook_delivery_history_tracking() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Register webhook
		$webhook_data = array(
			'url' => $this->test_webhook_url,
			'events' => array( 'settings.updated', 'theme.applied', 'backup.created' ),
			'secret' => 'test-secret',
			'active' => true
		);
		
		$response = $this->perform_rest_request(
			'POST',
			'/webhooks',
			$webhook_data,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$webhook_id = $response->get_data()['data']['webhook_id'];
		
		// Trigger multiple events
		
		// Event 1: Settings update
		$this->perform_rest_request(
			'POST',
			'/settings',
			array_merge( $this->default_settings, array( 'menu_background' => '#ff0000' ) ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// Event 2: Theme application
		$this->perform_rest_request(
			'POST',
			'/themes/dark/apply',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// Event 3: Backup creation
		$this->perform_rest_request(
			'POST',
			'/backups',
			array( 'note' => 'Webhook test backup', 'type' => 'manual' ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// Get delivery history
		$response = $this->perform_rest_request(
			'GET',
			"/webhooks/{$webhook_id}/deliveries",
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$deliveries_data = $response->get_data();
		$deliveries = $deliveries_data['data']['deliveries'];
		
		$this->assertGreaterThanOrEqual( 3, count( $deliveries ), 'Should have at least 3 deliveries' );
		
		// Verify each delivery has required fields
		foreach ( $deliveries as $delivery ) {
			$this->assertArrayHasKey( 'id', $delivery );
			$this->assertArrayHasKey( 'webhook_id', $delivery );
			$this->assertArrayHasKey( 'event', $delivery );
			$this->assertArrayHasKey( 'payload', $delivery );
			$this->assertArrayHasKey( 'status', $delivery );
			$this->assertArrayHasKey( 'response_code', $delivery );
			$this->assertArrayHasKey( 'attempts', $delivery );
			$this->assertArrayHasKey( 'created_at', $delivery );
			$this->assertArrayHasKey( 'delivered_at', $delivery );
		}
		
		// Verify different events were tracked
		$events = array_column( $deliveries, 'event' );
		$this->assertContains( 'settings.updated', $events );
		$this->assertContains( 'theme.applied', $events );
		$this->assertContains( 'backup.created', $events );
	}
	
	/**
	 * Test webhook management endpoints.
	 * Requirements: 10.1, 10.2
	 */
	public function test_webhook_management_endpoints() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Create webhook
		$webhook_data = array(
			'url' => $this->test_webhook_url,
			'events' => array( 'settings.updated' ),
			'secret' => 'test-secret',
			'active' => true
		);
		
		$response = $this->perform_rest_request(
			'POST',
			'/webhooks',
			$webhook_data,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$webhook_id = $response->get_data()['data']['webhook_id'];
		
		// Get specific webhook
		$response = $this->perform_rest_request(
			'GET',
			"/webhooks/{$webhook_id}",
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$webhook_data = $response->get_data();
		$webhook = $webhook_data['data']['webhook'];
		
		$this->assertEquals( $webhook_id, $webhook['id'] );
		$this->assertEquals( $this->test_webhook_url, $webhook['url'] );
		
		// Update webhook
		$updated_data = array(
			'events' => array( 'settings.updated', 'theme.applied' ),
			'active' => false
		);
		
		$response = $this->perform_rest_request(
			'PUT',
			"/webhooks/{$webhook_id}",
			$updated_data,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Verify update
		$response = $this->perform_rest_request(
			'GET',
			"/webhooks/{$webhook_id}",
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$webhook = $response->get_data()['data']['webhook'];
		$this->assertContains( 'theme.applied', $webhook['events'] );
		$this->assertFalse( $webhook['active'] );
		
		// Delete webhook
		$response = $this->perform_rest_request(
			'DELETE',
			"/webhooks/{$webhook_id}",
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		// Verify deletion
		$response = $this->perform_rest_request(
			'GET',
			"/webhooks/{$webhook_id}",
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseError( $response, 404 );
	}
	
	/**
	 * Test webhook triggers for all events.
	 * Requirement: 10.1
	 */
	public function test_webhook_triggers_for_all_events() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Register webhook for all events
		$webhook_data = array(
			'url' => $this->test_webhook_url,
			'events' => array(
				'settings.updated',
				'theme.applied',
				'backup.created',
				'backup.restored'
			),
			'secret' => 'test-secret',
			'active' => true
		);
		
		$response = $this->perform_rest_request(
			'POST',
			'/webhooks',
			$webhook_data,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$webhook_id = $response->get_data()['data']['webhook_id'];
		
		// Trigger settings.updated
		$this->perform_rest_request(
			'POST',
			'/settings',
			array_merge( $this->default_settings, array( 'menu_background' => '#e91e63' ) ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// Trigger theme.applied
		$this->perform_rest_request(
			'POST',
			'/themes/dark/apply',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// Trigger backup.created
		$backup_response = $this->perform_rest_request(
			'POST',
			'/backups',
			array( 'note' => 'Test backup', 'type' => 'manual' ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$backup_id = $backup_response->get_data()['data']['backup_id'];
		
		// Trigger backup.restored
		$this->perform_rest_request(
			'POST',
			"/backups/{$backup_id}/restore",
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// Check deliveries
		$response = $this->perform_rest_request(
			'GET',
			"/webhooks/{$webhook_id}/deliveries",
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$deliveries = $response->get_data()['data']['deliveries'];
		
		// Should have deliveries for all events
		$this->assertGreaterThanOrEqual( 4, count( $deliveries ) );
		
		$events = array_column( $deliveries, 'event' );
		$this->assertContains( 'settings.updated', $events );
		$this->assertContains( 'theme.applied', $events );
		$this->assertContains( 'backup.created', $events );
		$this->assertContains( 'backup.restored', $events );
	}
	
	/**
	 * Test webhook payload structure.
	 * Requirement: 10.2
	 */
	public function test_webhook_payload_structure() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Register webhook
		$webhook_data = array(
			'url' => $this->test_webhook_url,
			'events' => array( 'settings.updated' ),
			'secret' => 'test-secret',
			'active' => true
		);
		
		$response = $this->perform_rest_request(
			'POST',
			'/webhooks',
			$webhook_data,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$webhook_id = $response->get_data()['data']['webhook_id'];
		
		// Trigger event
		$this->perform_rest_request(
			'POST',
			'/settings',
			array_merge( $this->default_settings, array( 'menu_background' => '#2196f3' ) ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// Get delivery
		$response = $this->perform_rest_request(
			'GET',
			"/webhooks/{$webhook_id}/deliveries",
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$delivery = $response->get_data()['data']['deliveries'][0];
		$payload = $delivery['payload'];
		
		// Verify payload structure
		$this->assertArrayHasKey( 'event', $payload );
		$this->assertArrayHasKey( 'timestamp', $payload );
		$this->assertArrayHasKey( 'data', $payload );
		$this->assertArrayHasKey( 'webhook_id', $payload );
		
		$this->assertEquals( 'settings.updated', $payload['event'] );
		$this->assertEquals( $webhook_id, $payload['webhook_id'] );
		
		// Verify event-specific data
		$this->assertArrayHasKey( 'settings', $payload['data'] );
		$this->assertEquals( '#2196f3', $payload['data']['settings']['menu_background'] );
	}
	
	/**
	 * Test webhook exponential backoff retry.
	 * Requirement: 10.3
	 */
	public function test_webhook_exponential_backoff_retry() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Register webhook with invalid URL
		$webhook_data = array(
			'url' => 'https://invalid-url-for-retry-test.test',
			'events' => array( 'settings.updated' ),
			'secret' => 'test-secret',
			'active' => true
		);
		
		$response = $this->perform_rest_request(
			'POST',
			'/webhooks',
			$webhook_data,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$webhook_id = $response->get_data()['data']['webhook_id'];
		
		// Trigger event
		$this->perform_rest_request(
			'POST',
			'/settings',
			array_merge( $this->default_settings, array( 'menu_background' => '#ff5722' ) ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// Wait for initial delivery attempt
		sleep( 1 );
		
		// Get delivery
		$response = $this->perform_rest_request(
			'GET',
			"/webhooks/{$webhook_id}/deliveries",
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$delivery = $response->get_data()['data']['deliveries'][0];
		
		// Verify retry schedule uses exponential backoff
		$this->assertArrayHasKey( 'next_retry', $delivery );
		$this->assertArrayHasKey( 'attempts', $delivery );
		
		// First retry should be soon (within a few seconds)
		if ( $delivery['attempts'] === 1 ) {
			$next_retry_time = strtotime( $delivery['next_retry'] );
			$current_time = time();
			$retry_delay = $next_retry_time - $current_time;
			
			// First retry should be within 60 seconds
			$this->assertLessThan( 60, $retry_delay );
		}
	}
	
	/**
	 * Test webhook filtering by event type.
	 * Requirement: 10.1
	 */
	public function test_webhook_filtering_by_event_type() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Register webhook for specific events only
		$webhook_data = array(
			'url' => $this->test_webhook_url,
			'events' => array( 'theme.applied' ), // Only theme events
			'secret' => 'test-secret',
			'active' => true
		);
		
		$response = $this->perform_rest_request(
			'POST',
			'/webhooks',
			$webhook_data,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$webhook_id = $response->get_data()['data']['webhook_id'];
		
		// Trigger settings.updated (should NOT fire webhook)
		$this->perform_rest_request(
			'POST',
			'/settings',
			array_merge( $this->default_settings, array( 'menu_background' => '#00bcd4' ) ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// Trigger theme.applied (SHOULD fire webhook)
		$this->perform_rest_request(
			'POST',
			'/themes/dark/apply',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// Check deliveries
		$response = $this->perform_rest_request(
			'GET',
			"/webhooks/{$webhook_id}/deliveries",
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$deliveries = $response->get_data()['data']['deliveries'];
		
		// Should only have theme.applied delivery
		$this->assertCount( 1, $deliveries );
		$this->assertEquals( 'theme.applied', $deliveries[0]['event'] );
	}
	
	/**
	 * Test webhook performance with multiple webhooks.
	 * Requirements: 10.1, 10.2
	 */
	public function test_webhook_performance() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Register multiple webhooks
		$webhook_ids = array();
		for ( $i = 0; $i < 5; $i++ ) {
			$webhook_data = array(
				'url' => $this->test_webhook_url . "/{$i}",
				'events' => array( 'settings.updated' ),
				'secret' => "test-secret-{$i}",
				'active' => true
			);
			
			$response = $this->perform_rest_request(
				'POST',
				'/webhooks',
				$webhook_data,
				array( 'X-WP-Nonce' => $nonce )
			);
			
			$webhook_ids[] = $response->get_data()['data']['webhook_id'];
		}
		
		// Measure time to trigger all webhooks
		$start_time = microtime( true );
		
		$this->perform_rest_request(
			'POST',
			'/settings',
			array_merge( $this->default_settings, array( 'menu_background' => '#673ab7' ) ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$trigger_time = microtime( true ) - $start_time;
		
		// Webhook delivery should not significantly slow down the request
		$this->assertLessThan( 2.0, $trigger_time, 'Webhook delivery should be fast' );
	}
	
	/**
	 * Test complete webhook workflow.
	 * Requirements: 10.1, 10.2, 10.3, 10.4
	 */
	public function test_complete_webhook_workflow() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Step 1: Register webhook
		$webhook_data = array(
			'url' => $this->test_webhook_url,
			'events' => array( 'settings.updated', 'theme.applied' ),
			'secret' => 'workflow-test-secret',
			'active' => true
		);
		
		$response = $this->perform_rest_request(
			'POST',
			'/webhooks',
			$webhook_data,
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response, 201 );
		$webhook_id = $response->get_data()['data']['webhook_id'];
		
		// Step 2: Trigger events
		$this->perform_rest_request(
			'POST',
			'/settings',
			array_merge( $this->default_settings, array( 'menu_background' => '#4caf50' ) ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->perform_rest_request(
			'POST',
			'/themes/dark/apply',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// Step 3: Check delivery history
		$response = $this->perform_rest_request(
			'GET',
			"/webhooks/{$webhook_id}/deliveries",
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		$deliveries = $response->get_data()['data']['deliveries'];
		
		$this->assertGreaterThanOrEqual( 2, count( $deliveries ) );
		
		// Step 4: Verify delivery details
		foreach ( $deliveries as $delivery ) {
			$this->assertArrayHasKey( 'signature', $delivery );
			$this->assertArrayHasKey( 'payload', $delivery );
			$this->assertArrayHasKey( 'status', $delivery );
		}
		
		// Step 5: Update webhook
		$this->perform_rest_request(
			'PUT',
			"/webhooks/{$webhook_id}",
			array( 'active' => false ),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		// Step 6: Verify webhook is inactive
		$response = $this->perform_rest_request(
			'GET',
			"/webhooks/{$webhook_id}",
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$webhook = $response->get_data()['data']['webhook'];
		$this->assertFalse( $webhook['active'] );
		
		// Step 7: Delete webhook
		$response = $this->perform_rest_request(
			'DELETE',
			"/webhooks/{$webhook_id}",
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
	}
}
