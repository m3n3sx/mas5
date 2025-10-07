<?php
/**
 * Base REST API test case class for MAS tests.
 */

class MAS_REST_Test_Case extends WP_Test_REST_TestCase {
	
	/**
	 * Admin user ID for testing.
	 *
	 * @var int
	 */
	protected $admin_user_id;
	
	/**
	 * Editor user ID for testing.
	 *
	 * @var int
	 */
	protected $editor_user_id;
	
	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'mas-v2/v1';
	
	/**
	 * Default settings for testing.
	 *
	 * @var array
	 */
	protected $default_settings;
	
	/**
	 * Set up test case.
	 */
	public function setUp(): void {
		parent::setUp();
		
		// Create test users
		$this->admin_user_id = $this->factory->user->create( array(
			'role' => 'administrator',
			'user_login' => 'testadmin_' . wp_rand(),
			'user_email' => 'admin' . wp_rand() . '@test.com'
		) );
		
		$this->editor_user_id = $this->factory->user->create( array(
			'role' => 'editor',
			'user_login' => 'testeditor_' . wp_rand(),
			'user_email' => 'editor' . wp_rand() . '@test.com'
		) );
		
		// Set default settings
		$this->default_settings = array(
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
			'animation_speed' => 'normal',
			'current_theme' => 'default',
			'performance_mode' => false,
			'debug_mode' => false
		);
		
		// Reset plugin options
		update_option( 'mas_v2_settings', $this->default_settings );
		update_option( 'mas_v2_feature_flags', array(
			'rest_api_enabled' => true,
			'dual_mode_enabled' => true,
			'deprecation_warnings' => true
		) );
		
		// Initialize REST API
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server;
		do_action( 'rest_api_init' );
		
		// Clear any cached data
		wp_cache_flush();
	}
	
	/**
	 * Tear down test case.
	 */
	public function tearDown(): void {
		// Clean up options
		delete_option( 'mas_v2_settings' );
		delete_option( 'mas_v2_feature_flags' );
		delete_option( 'mas_v2_backups' );
		delete_option( 'mas_v2_themes' );
		
		// Clear cache
		wp_cache_flush();
		
		global $wp_rest_server;
		$wp_rest_server = null;
		
		parent::tearDown();
	}
	
	/**
	 * Perform a REST API request.
	 *
	 * @param string $method HTTP method.
	 * @param string $endpoint Endpoint path.
	 * @param array $params Request parameters.
	 * @param array $headers Request headers.
	 * @return WP_REST_Response Response object.
	 */
	protected function perform_rest_request( $method, $endpoint, $params = array(), $headers = array() ) {
		$request = new WP_REST_Request( $method, '/' . $this->namespace . $endpoint );
		
		if ( ! empty( $params ) ) {
			if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ) ) ) {
				$request->set_body_params( $params );
			} else {
				foreach ( $params as $key => $value ) {
					$request->set_param( $key, $value );
				}
			}
		}
		
		foreach ( $headers as $key => $value ) {
			$request->set_header( $key, $value );
		}
		
		return rest_do_request( $request );
	}
	
	/**
	 * Assert that a REST response is successful.
	 *
	 * @param WP_REST_Response $response Response object.
	 * @param int $expected_status Expected HTTP status code.
	 * @param string $message Optional message.
	 */
	protected function assertRestResponseSuccess( $response, $expected_status = 200, $message = '' ) {
		$this->assertNotInstanceOf( 'WP_Error', $response, 'Response should not be a WP_Error' );
		$this->assertEquals( $expected_status, $response->get_status(), $message ?: "Expected status {$expected_status}" );
		
		$data = $response->get_data();
		if ( isset( $data['success'] ) ) {
			$this->assertTrue( $data['success'], 'Response should indicate success' );
		}
	}
	
	/**
	 * Assert that a REST response is an error.
	 *
	 * @param WP_REST_Response $response Response object.
	 * @param int $expected_status Expected HTTP status code.
	 * @param string $expected_code Expected error code.
	 * @param string $message Optional message.
	 */
	protected function assertRestResponseError( $response, $expected_status = 400, $expected_code = '', $message = '' ) {
		$this->assertEquals( $expected_status, $response->get_status(), $message ?: "Expected status {$expected_status}" );
		
		$data = $response->get_data();
		if ( $expected_code ) {
			$this->assertEquals( $expected_code, $data['code'], "Expected error code {$expected_code}" );
		}
	}
	
	/**
	 * Assert that response data contains expected keys.
	 *
	 * @param WP_REST_Response $response Response object.
	 * @param array $expected_keys Expected keys in response data.
	 * @param string $message Optional message.
	 */
	protected function assertResponseHasKeys( $response, $expected_keys, $message = '' ) {
		$data = $response->get_data();
		
		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $data, $message ?: "Response should contain key: {$key}" );
		}
	}
	
	/**
	 * Create a nonce for REST API requests.
	 *
	 * @return string Nonce value.
	 */
	protected function create_rest_nonce() {
		return wp_create_nonce( 'wp_rest' );
	}
	
	/**
	 * Set current user and create nonce.
	 *
	 * @param int $user_id User ID to set as current user.
	 * @return string Nonce value.
	 */
	protected function authenticate_user( $user_id ) {
		wp_set_current_user( $user_id );
		return $this->create_rest_nonce();
	}
}