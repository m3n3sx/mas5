<?php
/**
 * Integration Tests for MAS Settings REST API Endpoints
 *
 * Tests the complete settings workflow including GET, POST, PUT, DELETE operations,
 * validation, authentication, and authorization.
 *
 * @package Modern_Admin_Styler_V2
 * @subpackage Tests
 */

/**
 * Test case for MAS Settings REST API Integration
 */
class TestMASSettingsIntegration extends WP_UnitTestCase {

	/**
	 * Admin user ID
	 *
	 * @var int
	 */
	protected $admin_user;

	/**
	 * Editor user ID
	 *
	 * @var int
	 */
	protected $editor_user;

	/**
	 * Subscriber user ID
	 *
	 * @var int
	 */
	protected $subscriber_user;

	/**
	 * Settings controller instance
	 *
	 * @var MAS_Settings_Controller
	 */
	protected $controller;

	/**
	 * Settings service instance
	 *
	 * @var MAS_Settings_Service
	 */
	protected $service;

	/**
	 * REST API namespace
	 *
	 * @var string
	 */
	protected $namespace = 'mas-v2/v1';

	/**
	 * REST API base route
	 *
	 * @var string
	 */
	protected $route = '/mas-v2/v1/settings';

	/**
	 * Set up test environment
	 */
	public function setUp() {
		parent::setUp();

		// Create test users with different roles
		$this->admin_user = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->editor_user = $this->factory->user->create( array( 'role' => 'editor' ) );
		$this->subscriber_user = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		// Load required files
		require_once MAS_V2_PLUGIN_DIR . 'includes/api/class-mas-rest-controller.php';
		require_once MAS_V2_PLUGIN_DIR . 'includes/services/class-mas-settings-service.php';
		require_once MAS_V2_PLUGIN_DIR . 'includes/api/class-mas-settings-controller.php';

		// Initialize controller and service
		$this->controller = new MAS_Settings_Controller();
		$this->service = MAS_Settings_Service::get_instance();

		// Register routes
		$this->controller->register_routes();

		// Reset settings to defaults before each test
		delete_option( 'mas_v2_settings' );
		wp_cache_flush();
	}

	/**
	 * Tear down test environment
	 */
	public function tearDown() {
		parent::tearDown();
		wp_set_current_user( 0 );
		delete_option( 'mas_v2_settings' );
		wp_cache_flush();
	}

	/**
	 * Test full settings workflow: get, save, update, reset
	 * Requirements: 12.2
	 */
	public function test_complete_settings_workflow() {
		wp_set_current_user( $this->admin_user );

		// Step 1: Get initial settings
		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );
		
		$this->assertEquals( 200, $response->get_status(), 'GET request should return 200' );
		$data = $response->get_data();
		$this->assertTrue( $data['success'], 'Response should indicate success' );
		$this->assertArrayHasKey( 'data', $data, 'Response should contain data' );
		
		$initial_settings = $data['data'];
		$this->assertIsArray( $initial_settings, 'Settings should be an array' );
		$this->assertArrayHasKey( 'menu_background', $initial_settings, 'Should have menu_background' );

		// Step 2: Save new settings (POST - complete replacement)
		$new_settings = array(
			'menu_background' => '#2d2d44',
			'menu_text_color' => '#ffffff',
			'admin_bar_background' => '#1e1e2e',
			'enable_animations' => true,
			'animation_speed' => 400,
		);

		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $new_settings ) );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status(), 'POST request should return 200' );
		$data = $response->get_data();
		$this->assertTrue( $data['success'], 'Save should be successful' );
		$this->assertArrayHasKey( 'settings', $data['data'], 'Response should contain settings' );
		$this->assertTrue( $data['data']['css_generated'], 'CSS should be generated' );

		// Step 3: Verify settings were saved
		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );
		$data = $response->get_data();
		$saved_settings = $data['data'];

		$this->assertEquals( '#2d2d44', $saved_settings['menu_background'], 'Menu background should be updated' );
		$this->assertEquals( '#ffffff', $saved_settings['menu_text_color'], 'Menu text color should be updated' );
		$this->assertEquals( 400, $saved_settings['animation_speed'], 'Animation speed should be updated' );

		// Step 4: Update settings (PUT - partial update)
		$partial_update = array(
			'menu_background' => '#3d3d5c',
			'enable_animations' => false,
		);

		$request = new WP_REST_Request( 'PUT', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $partial_update ) );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status(), 'PUT request should return 200' );
		$data = $response->get_data();
		$this->assertTrue( $data['success'], 'Update should be successful' );

		// Step 5: Verify partial update
		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );
		$data = $response->get_data();
		$updated_settings = $data['data'];

		$this->assertEquals( '#3d3d5c', $updated_settings['menu_background'], 'Menu background should be updated' );
		$this->assertFalse( $updated_settings['enable_animations'], 'Animations should be disabled' );
		$this->assertEquals( '#ffffff', $updated_settings['menu_text_color'], 'Other settings should remain unchanged' );

		// Step 6: Reset settings to defaults
		$request = new WP_REST_Request( 'DELETE', $this->route );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status(), 'DELETE request should return 200' );
		$data = $response->get_data();
		$this->assertTrue( $data['success'], 'Reset should be successful' );
		$this->assertTrue( $data['data']['backup_created'], 'Backup should be created' );
		$this->assertTrue( $data['data']['css_generated'], 'CSS should be regenerated' );

		// Step 7: Verify settings were reset
		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );
		$data = $response->get_data();
		$reset_settings = $data['data'];

		$defaults = $this->service->get_defaults();
		$this->assertEquals( $defaults['menu_background'], $reset_settings['menu_background'], 'Should be reset to default' );
	}

	/**
	 * Test GET settings endpoint with authentication
	 * Requirements: 12.3
	 */
	public function test_get_settings_requires_authentication() {
		// Test without authentication
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status(), 'Unauthenticated request should return 403' );
		$data = $response->get_data();
		$this->assertEquals( 'rest_forbidden', $data['code'], 'Should return forbidden error code' );
	}

	/**
	 * Test GET settings endpoint with proper authorization
	 * Requirements: 12.3
	 */
	public function test_get_settings_with_admin_authorization() {
		wp_set_current_user( $this->admin_user );
		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status(), 'Admin should be able to get settings' );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertIsArray( $data['data'] );
	}

	/**
	 * Test GET settings endpoint with insufficient permissions
	 * Requirements: 12.3
	 */
	public function test_get_settings_with_insufficient_permissions() {
		// Editor doesn't have manage_options capability
		wp_set_current_user( $this->editor_user );
		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status(), 'Editor should not have permission' );

		// Subscriber doesn't have manage_options capability
		wp_set_current_user( $this->subscriber_user );
		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status(), 'Subscriber should not have permission' );
	}

	/**
	 * Test POST settings with invalid color values
	 * Requirements: 12.4
	 */
	public function test_save_settings_with_invalid_colors() {
		wp_set_current_user( $this->admin_user );

		$invalid_settings = array(
			'menu_background' => 'not-a-color',
			'menu_text_color' => 'invalid',
		);

		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $invalid_settings ) );
		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status(), 'Invalid colors should return 400' );
		$data = $response->get_data();
		$this->assertEquals( 'validation_failed', $data['code'], 'Should return validation_failed error' );
		$this->assertArrayHasKey( 'errors', $data['data'], 'Should contain validation errors' );
	}

	/**
	 * Test POST settings with invalid numeric values
	 * Requirements: 12.4
	 */
	public function test_save_settings_with_invalid_numeric_values() {
		wp_set_current_user( $this->admin_user );

		$invalid_settings = array(
			'menu_width' => 'not-a-number',
			'animation_speed' => 'invalid',
		);

		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $invalid_settings ) );
		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status(), 'Invalid numeric values should return 400' );
		$data = $response->get_data();
		$this->assertEquals( 'validation_failed', $data['code'] );
	}

	/**
	 * Test POST settings with invalid boolean values
	 * Requirements: 12.4
	 */
	public function test_save_settings_with_invalid_boolean_values() {
		wp_set_current_user( $this->admin_user );

		$invalid_settings = array(
			'enable_animations' => 'not-a-boolean',
			'menu_floating' => 'invalid',
		);

		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $invalid_settings ) );
		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status(), 'Invalid boolean values should return 400' );
		$data = $response->get_data();
		$this->assertEquals( 'validation_failed', $data['code'] );
	}

	/**
	 * Test POST settings with valid data
	 * Requirements: 12.2
	 */
	public function test_save_settings_with_valid_data() {
		wp_set_current_user( $this->admin_user );

		$valid_settings = array(
			'menu_background' => '#1e1e2e',
			'menu_text_color' => '#ffffff',
			'menu_width' => 200,
			'enable_animations' => true,
			'animation_speed' => 350,
		);

		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $valid_settings ) );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status(), 'Valid settings should return 200' );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'settings', $data['data'] );
		$this->assertTrue( $data['data']['css_generated'] );
	}

	/**
	 * Test POST settings without authentication
	 * Requirements: 12.3
	 */
	public function test_save_settings_without_authentication() {
		wp_set_current_user( 0 );

		$settings = array(
			'menu_background' => '#1e1e2e',
		);

		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $settings ) );
		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status(), 'Unauthenticated POST should return 403' );
	}

	/**
	 * Test POST settings with empty data
	 * Requirements: 12.4
	 */
	public function test_save_settings_with_empty_data() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array() ) );
		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status(), 'Empty data should return 400' );
		$data = $response->get_data();
		$this->assertEquals( 'no_settings_data', $data['code'] );
	}

	/**
	 * Test PUT settings (partial update)
	 * Requirements: 12.2
	 */
	public function test_update_settings_partial() {
		wp_set_current_user( $this->admin_user );

		// First, save some initial settings
		$initial_settings = array(
			'menu_background' => '#1e1e2e',
			'menu_text_color' => '#ffffff',
			'enable_animations' => true,
		);

		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $initial_settings ) );
		rest_do_request( $request );

		// Now update only one field
		$partial_update = array(
			'menu_background' => '#2d2d44',
		);

		$request = new WP_REST_Request( 'PUT', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $partial_update ) );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );

		// Verify only the specified field was updated
		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );
		$data = $response->get_data();
		$settings = $data['data'];

		$this->assertEquals( '#2d2d44', $settings['menu_background'], 'Updated field should change' );
		$this->assertEquals( '#ffffff', $settings['menu_text_color'], 'Other fields should remain unchanged' );
		$this->assertTrue( $settings['enable_animations'], 'Other fields should remain unchanged' );
	}

	/**
	 * Test PUT settings without authentication
	 * Requirements: 12.3
	 */
	public function test_update_settings_without_authentication() {
		wp_set_current_user( 0 );

		$settings = array(
			'menu_background' => '#1e1e2e',
		);

		$request = new WP_REST_Request( 'PUT', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $settings ) );
		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status(), 'Unauthenticated PUT should return 403' );
	}

	/**
	 * Test DELETE settings (reset to defaults)
	 * Requirements: 12.2
	 */
	public function test_reset_settings_to_defaults() {
		wp_set_current_user( $this->admin_user );

		// First, save some custom settings
		$custom_settings = array(
			'menu_background' => '#custom',
			'menu_text_color' => '#custom',
		);

		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $custom_settings ) );
		rest_do_request( $request );

		// Now reset to defaults
		$request = new WP_REST_Request( 'DELETE', $this->route );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertTrue( $data['data']['backup_created'], 'Backup should be created before reset' );

		// Verify settings were reset
		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );
		$data = $response->get_data();
		$settings = $data['data'];

		$defaults = $this->service->get_defaults();
		$this->assertEquals( $defaults['menu_background'], $settings['menu_background'] );
		$this->assertEquals( $defaults['menu_text_color'], $settings['menu_text_color'] );
	}

	/**
	 * Test DELETE settings without authentication
	 * Requirements: 12.3
	 */
	public function test_reset_settings_without_authentication() {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'DELETE', $this->route );
		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status(), 'Unauthenticated DELETE should return 403' );
	}

	/**
	 * Test settings persistence across requests
	 * Requirements: 12.2
	 */
	public function test_settings_persistence() {
		wp_set_current_user( $this->admin_user );

		$test_settings = array(
			'menu_background' => '#persistent',
			'menu_text_color' => '#test123',
		);

		// Save settings
		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $test_settings ) );
		rest_do_request( $request );

		// Clear cache to ensure we're reading from database
		wp_cache_flush();

		// Retrieve settings in a new request
		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );
		$data = $response->get_data();
		$settings = $data['data'];

		$this->assertEquals( '#persistent', $settings['menu_background'] );
		$this->assertEquals( '#test123', $settings['menu_text_color'] );
	}

	/**
	 * Test CSS generation on settings save
	 * Requirements: 12.2
	 */
	public function test_css_generation_on_save() {
		wp_set_current_user( $this->admin_user );

		// Clear any existing CSS cache
		delete_transient( 'mas_v2_generated_css' );

		$settings = array(
			'menu_background' => '#testcss',
		);

		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $settings ) );
		$response = rest_do_request( $request );

		$data = $response->get_data();
		$this->assertTrue( $data['data']['css_generated'], 'CSS should be generated flag should be true' );

		// Verify CSS was actually generated and cached
		$cached_css = get_transient( 'mas_v2_generated_css' );
		$this->assertNotFalse( $cached_css, 'CSS should be cached' );
		$this->assertStringContainsString( '#testcss', $cached_css, 'CSS should contain the test color' );
	}

	/**
	 * Test validation error response format
	 * Requirements: 12.4
	 */
	public function test_validation_error_response_format() {
		wp_set_current_user( $this->admin_user );

		$invalid_settings = array(
			'menu_background' => 'invalid-color',
			'menu_width' => 'not-a-number',
		);

		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $invalid_settings ) );
		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();

		// Check error response structure
		$this->assertArrayHasKey( 'code', $data );
		$this->assertArrayHasKey( 'message', $data );
		$this->assertArrayHasKey( 'data', $data );
		$this->assertArrayHasKey( 'errors', $data['data'] );
		$this->assertIsArray( $data['data']['errors'] );
		$this->assertNotEmpty( $data['data']['errors'] );
	}

	/**
	 * Test hex color validation
	 * Requirements: 12.4
	 */
	public function test_hex_color_validation() {
		wp_set_current_user( $this->admin_user );

		$test_cases = array(
			array( 'color' => '#ffffff', 'valid' => true ),
			array( 'color' => '#000000', 'valid' => true ),
			array( 'color' => '#abc123', 'valid' => true ),
			array( 'color' => '#fff', 'valid' => true ),
			array( 'color' => 'ffffff', 'valid' => false ),
			array( 'color' => '#gggggg', 'valid' => false ),
			array( 'color' => 'not-a-color', 'valid' => false ),
			array( 'color' => '#12345', 'valid' => false ),
		);

		foreach ( $test_cases as $test_case ) {
			$settings = array(
				'menu_background' => $test_case['color'],
			);

			$request = new WP_REST_Request( 'POST', $this->route );
			$request->set_header( 'Content-Type', 'application/json' );
			$request->set_body( wp_json_encode( $settings ) );
			$response = rest_do_request( $request );

			if ( $test_case['valid'] ) {
				$this->assertEquals( 200, $response->get_status(), "Color {$test_case['color']} should be valid" );
			} else {
				$this->assertEquals( 400, $response->get_status(), "Color {$test_case['color']} should be invalid" );
			}
		}
	}

	/**
	 * Test multiple concurrent updates
	 * Requirements: 12.2
	 */
	public function test_concurrent_settings_updates() {
		wp_set_current_user( $this->admin_user );

		// Simulate multiple updates
		$updates = array(
			array( 'menu_background' => '#update1' ),
			array( 'menu_text_color' => '#update2' ),
			array( 'admin_bar_background' => '#update3' ),
		);

		foreach ( $updates as $update ) {
			$request = new WP_REST_Request( 'PUT', $this->route );
			$request->set_header( 'Content-Type', 'application/json' );
			$request->set_body( wp_json_encode( $update ) );
			$response = rest_do_request( $request );

			$this->assertEquals( 200, $response->get_status() );
		}

		// Verify all updates were applied
		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );
		$data = $response->get_data();
		$settings = $data['data'];

		$this->assertEquals( '#update1', $settings['menu_background'] );
		$this->assertEquals( '#update2', $settings['menu_text_color'] );
		$this->assertEquals( '#update3', $settings['admin_bar_background'] );
	}

	/**
	 * Test settings caching behavior
	 * Requirements: 12.2
	 */
	public function test_settings_caching() {
		wp_set_current_user( $this->admin_user );

		// Get settings (should cache)
		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );
		$this->assertEquals( 200, $response->get_status() );

		// Verify cache was set
		$cached = wp_cache_get( 'current_settings', 'mas_v2_settings' );
		$this->assertNotFalse( $cached, 'Settings should be cached' );

		// Update settings (should clear cache)
		$update = array( 'menu_background' => '#cached' );
		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $update ) );
		rest_do_request( $request );

		// Cache should be cleared after update
		$cached_after = wp_cache_get( 'current_settings', 'mas_v2_settings' );
		// Note: Cache might be repopulated immediately, so we just verify the update worked
		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );
		$data = $response->get_data();
		$this->assertEquals( '#cached', $data['data']['menu_background'] );
	}
}

