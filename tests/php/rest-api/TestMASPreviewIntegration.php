<?php
/**
 * Test MAS_Preview_Controller Integration
 *
 * Tests for the preview endpoint including CSS generation,
 * debouncing, and error handling.
 *
 * @package Modern_Admin_Styler_V2
 * @subpackage Tests
 */

/**
 * Test case for MAS_Preview_Controller
 */
class TestMASPreviewIntegration extends WP_UnitTestCase {

	/**
	 * Admin user ID
	 *
	 * @var int
	 */
	protected $admin_user;

	/**
	 * Preview controller instance
	 *
	 * @var MAS_Preview_Controller
	 */
	protected $controller;

	/**
	 * CSS Generator service instance
	 *
	 * @var MAS_CSS_Generator_Service
	 */
	protected $css_generator;

	/**
	 * REST API server instance
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * Set up test environment
	 */
	public function setUp() {
		parent::setUp();

		// Create admin user
		$this->admin_user = $this->factory->user->create( array( 'role' => 'administrator' ) );

		// Load required classes
		require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/includes/api/class-mas-rest-controller.php';
		require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/includes/api/class-mas-preview-controller.php';
		require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/includes/services/class-mas-css-generator-service.php';

		// Initialize controller
		$this->controller = new MAS_Preview_Controller();
		$this->css_generator = MAS_CSS_Generator_Service::get_instance();

		// Set up REST API server
		global $wp_rest_server;
		$this->server = $wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );

		// Register routes
		$this->controller->register_routes();
	}

	/**
	 * Tear down test environment
	 */
	public function tearDown() {
		parent::tearDown();
		wp_set_current_user( 0 );

		global $wp_rest_server;
		$wp_rest_server = null;

		// Clear CSS cache
		$this->css_generator->clear_cache();
	}

	/**
	 * Test preview endpoint requires authentication
	 */
	public function test_preview_requires_authentication() {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/preview' );
		$request->set_param( 'settings', array( 'menu_background' => '#1e1e2e' ) );

		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status(), 'Unauthenticated request should return 403' );
	}

	/**
	 * Test preview endpoint requires manage_options capability
	 */
	public function test_preview_requires_manage_options() {
		$editor_user = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor_user );

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/preview' );
		$request->set_param( 'settings', array( 'menu_background' => '#1e1e2e' ) );

		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status(), 'Editor should not have permission' );
	}

	/**
	 * Test preview generates CSS without saving settings
	 */
	public function test_preview_generates_css_without_saving() {
		wp_set_current_user( $this->admin_user );

		// Get initial settings
		$initial_settings = get_option( 'mas_v2_settings', array() );

		// Request preview with different settings
		$preview_settings = array(
			'menu_background' => '#ff0000',
			'menu_text_color' => '#ffffff',
			'admin_bar_background' => '#00ff00',
		);

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/preview' );
		$request->set_param( 'settings', $preview_settings );

		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status(), 'Preview should succeed' );

		$data = $response->get_data();
		$this->assertTrue( $data['success'], 'Response should indicate success' );
		$this->assertArrayHasKey( 'data', $data, 'Response should have data' );
		$this->assertArrayHasKey( 'css', $data['data'], 'Response should contain CSS' );
		$this->assertNotEmpty( $data['data']['css'], 'CSS should not be empty' );

		// Verify settings were NOT saved
		$current_settings = get_option( 'mas_v2_settings', array() );
		$this->assertEquals( $initial_settings, $current_settings, 'Settings should not be modified' );

		// Verify CSS contains preview settings
		$css = $data['data']['css'];
		$this->assertStringContainsString( '#ff0000', $css, 'CSS should contain menu background color' );
		$this->assertStringContainsString( '#00ff00', $css, 'CSS should contain admin bar background color' );
	}

	/**
	 * Test preview CSS includes all styling sections
	 */
	public function test_preview_css_includes_all_sections() {
		wp_set_current_user( $this->admin_user );

		$settings = array(
			'menu_background' => '#1e1e2e',
			'menu_text_color' => '#ffffff',
			'admin_bar_background' => '#23282d',
			'submenu_background' => '#2c3338',
			'content_background' => '#f0f0f1',
			'button_primary_background' => '#0073aa',
			'enable_animations' => true,
			'enable_shadows' => true,
		);

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/preview' );
		$request->set_param( 'settings', $settings );

		$response = rest_do_request( $request );
		$data = $response->get_data();
		$css = $data['data']['css'];

		// Verify CSS contains expected sections
		$this->assertStringContainsString( 'Admin Bar Styles', $css, 'Should include admin bar section' );
		$this->assertStringContainsString( 'Menu Styles', $css, 'Should include menu section' );
		$this->assertStringContainsString( 'Submenu Styles', $css, 'Should include submenu section' );
		$this->assertStringContainsString( 'Content Area Styles', $css, 'Should include content section' );
		$this->assertStringContainsString( 'Button Styles', $css, 'Should include button section' );
		$this->assertStringContainsString( 'Animations', $css, 'Should include animations section' );
	}

	/**
	 * Test preview validates color values
	 */
	public function test_preview_validates_color_values() {
		wp_set_current_user( $this->admin_user );

		$invalid_settings = array(
			'menu_background' => 'not-a-color',
			'menu_text_color' => '#ffffff',
		);

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/preview' );
		$request->set_param( 'settings', $invalid_settings );

		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status(), 'Invalid color should return 400' );

		$data = $response->get_data();
		$this->assertEquals( 'validation_failed', $data['code'], 'Should return validation_failed error' );
	}

	/**
	 * Test preview accepts valid hex colors
	 */
	public function test_preview_accepts_valid_hex_colors() {
		wp_set_current_user( $this->admin_user );

		$valid_colors = array(
			'menu_background' => '#1e1e2e',
			'menu_text_color' => '#fff',
			'admin_bar_background' => '#23282d',
		);

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/preview' );
		$request->set_param( 'settings', $valid_colors );

		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status(), 'Valid colors should be accepted' );
	}

	/**
	 * Test preview accepts rgba colors
	 */
	public function test_preview_accepts_rgba_colors() {
		wp_set_current_user( $this->admin_user );

		$rgba_settings = array(
			'menu_background' => 'rgba(30, 30, 46, 0.8)',
			'admin_bar_background' => 'rgb(35, 40, 45)',
		);

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/preview' );
		$request->set_param( 'settings', $rgba_settings );

		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status(), 'RGBA colors should be accepted' );
	}

	/**
	 * Test preview requires settings parameter
	 */
	public function test_preview_requires_settings_parameter() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/preview' );
		// Don't set settings parameter

		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status(), 'Missing settings should return 400' );
	}

	/**
	 * Test preview rejects non-object settings
	 */
	public function test_preview_rejects_non_object_settings() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/preview' );
		$request->set_param( 'settings', 'not-an-object' );

		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status(), 'Non-object settings should return 400' );
	}

	/**
	 * Test preview sets proper cache headers
	 */
	public function test_preview_sets_no_cache_headers() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/preview' );
		$request->set_param( 'settings', array( 'menu_background' => '#1e1e2e' ) );

		$response = rest_do_request( $request );

		$headers = $response->get_headers();

		$this->assertArrayHasKey( 'Cache-Control', $headers, 'Should have Cache-Control header' );
		$this->assertStringContainsString( 'no-cache', $headers['Cache-Control'], 'Should prevent caching' );
		$this->assertStringContainsString( 'no-store', $headers['Cache-Control'], 'Should prevent storage' );
	}

	/**
	 * Test preview debouncing prevents rapid requests
	 */
	public function test_preview_debouncing_prevents_rapid_requests() {
		wp_set_current_user( $this->admin_user );

		$settings = array( 'menu_background' => '#1e1e2e' );

		// First request should succeed
		$request1 = new WP_REST_Request( 'POST', '/mas-v2/v1/preview' );
		$request1->set_param( 'settings', $settings );
		$response1 = rest_do_request( $request1 );

		$this->assertEquals( 200, $response1->get_status(), 'First request should succeed' );

		// Immediate second request should be rate limited
		$request2 = new WP_REST_Request( 'POST', '/mas-v2/v1/preview' );
		$request2->set_param( 'settings', $settings );
		$response2 = rest_do_request( $request2 );

		$this->assertEquals( 429, $response2->get_status(), 'Rapid second request should be rate limited' );

		$data = $response2->get_data();
		$this->assertEquals( 'rate_limited', $data['code'], 'Should return rate_limited error' );
	}

	/**
	 * Test preview allows requests after debounce delay
	 */
	public function test_preview_allows_requests_after_debounce_delay() {
		wp_set_current_user( $this->admin_user );

		$settings = array( 'menu_background' => '#1e1e2e' );

		// First request
		$request1 = new WP_REST_Request( 'POST', '/mas-v2/v1/preview' );
		$request1->set_param( 'settings', $settings );
		$response1 = rest_do_request( $request1 );

		$this->assertEquals( 200, $response1->get_status(), 'First request should succeed' );

		// Wait for debounce delay (500ms + buffer)
		usleep( 600000 ); // 600ms

		// Second request after delay should succeed
		$request2 = new WP_REST_Request( 'POST', '/mas-v2/v1/preview' );
		$request2->set_param( 'settings', $settings );
		$response2 = rest_do_request( $request2 );

		$this->assertEquals( 200, $response2->get_status(), 'Request after delay should succeed' );
	}

	/**
	 * Test preview returns fallback CSS on generation error
	 */
	public function test_preview_returns_fallback_on_generation_error() {
		wp_set_current_user( $this->admin_user );

		// Create a mock CSS generator that throws an exception
		$mock_generator = $this->getMockBuilder( 'MAS_CSS_Generator_Service' )
			->disableOriginalConstructor()
			->getMock();

		$mock_generator->method( 'generate' )
			->will( $this->throwException( new Exception( 'CSS generation failed' ) ) );

		// Use reflection to replace the CSS generator
		$reflection = new ReflectionClass( $this->controller );
		$property = $reflection->getProperty( 'css_generator' );
		$property->setAccessible( true );
		$property->setValue( $this->controller, $mock_generator );

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/preview' );
		$request->set_param( 'settings', array(
			'menu_background' => '#1e1e2e',
			'admin_bar_background' => '#23282d',
		) );

		$response = rest_do_request( $request );

		// Should still return 200 with fallback CSS
		$this->assertEquals( 200, $response->get_status(), 'Should return 200 with fallback' );

		$data = $response->get_data();
		$this->assertTrue( $data['success'], 'Should indicate success' );
		$this->assertArrayHasKey( 'data', $data );
		$this->assertArrayHasKey( 'css', $data['data'], 'Should contain CSS' );
		$this->assertArrayHasKey( 'fallback', $data['data'], 'Should indicate fallback mode' );
		$this->assertTrue( $data['data']['fallback'], 'Fallback flag should be true' );

		// Verify fallback CSS contains basic styles
		$css = $data['data']['css'];
		$this->assertStringContainsString( 'Fallback CSS', $css, 'Should indicate fallback CSS' );
		$this->assertStringContainsString( '#adminmenu', $css, 'Should include basic menu styles' );
		$this->assertStringContainsString( '#1e1e2e', $css, 'Should include menu background from settings' );
	}

	/**
	 * Test preview fallback includes provided colors
	 */
	public function test_preview_fallback_includes_provided_colors() {
		wp_set_current_user( $this->admin_user );

		// Force an error by using reflection to break the generator
		$reflection = new ReflectionClass( $this->controller );
		$property = $reflection->getProperty( 'css_generator' );
		$property->setAccessible( true );
		$property->setValue( $this->controller, null );

		$settings = array(
			'menu_background' => '#ff0000',
			'admin_bar_background' => '#00ff00',
		);

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/preview' );
		$request->set_param( 'settings', $settings );

		$response = rest_do_request( $request );
		$data = $response->get_data();
		$css = $data['data']['css'];

		// Verify fallback CSS includes the provided colors
		$this->assertStringContainsString( '#ff0000', $css, 'Fallback should include menu background' );
		$this->assertStringContainsString( '#00ff00', $css, 'Fallback should include admin bar background' );
	}

	/**
	 * Test preview response includes metadata
	 */
	public function test_preview_response_includes_metadata() {
		wp_set_current_user( $this->admin_user );

		$settings = array(
			'menu_background' => '#1e1e2e',
			'menu_text_color' => '#ffffff',
			'admin_bar_background' => '#23282d',
		);

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/preview' );
		$request->set_param( 'settings', $settings );

		$response = rest_do_request( $request );
		$data = $response->get_data();

		$this->assertArrayHasKey( 'data', $data );
		$this->assertArrayHasKey( 'settings_count', $data['data'], 'Should include settings count' );
		$this->assertArrayHasKey( 'css_length', $data['data'], 'Should include CSS length' );

		$this->assertEquals( 3, $data['data']['settings_count'], 'Settings count should match' );
		$this->assertGreaterThan( 0, $data['data']['css_length'], 'CSS length should be positive' );
	}

	/**
	 * Test preview handles empty settings gracefully
	 */
	public function test_preview_handles_empty_settings() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/preview' );
		$request->set_param( 'settings', array() );

		$response = rest_do_request( $request );

		// Should return error for empty settings
		$this->assertEquals( 400, $response->get_status(), 'Empty settings should return 400' );
	}

	/**
	 * Test preview sanitizes settings
	 */
	public function test_preview_sanitizes_settings() {
		wp_set_current_user( $this->admin_user );

		$settings = array(
			'menu_background' => '#1e1e2e',
			'menu_text_color' => '<script>alert("xss")</script>',
			'custom_css' => 'body { color: red; }',
		);

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/preview' );
		$request->set_param( 'settings', $settings );

		$response = rest_do_request( $request );

		// Should either sanitize or reject the malicious input
		$this->assertNotEquals( 500, $response->get_status(), 'Should not cause server error' );
	}

	/**
	 * Test preview does not use cache
	 */
	public function test_preview_does_not_use_cache() {
		wp_set_current_user( $this->admin_user );

		$settings1 = array( 'menu_background' => '#1e1e2e' );
		$settings2 = array( 'menu_background' => '#ff0000' );

		// First request
		$request1 = new WP_REST_Request( 'POST', '/mas-v2/v1/preview' );
		$request1->set_param( 'settings', $settings1 );
		$response1 = rest_do_request( $request1 );
		$css1 = $response1->get_data()['data']['css'];

		// Wait for debounce
		usleep( 600000 );

		// Second request with different settings
		$request2 = new WP_REST_Request( 'POST', '/mas-v2/v1/preview' );
		$request2->set_param( 'settings', $settings2 );
		$response2 = rest_do_request( $request2 );
		$css2 = $response2->get_data()['data']['css'];

		// CSS should be different (not cached)
		$this->assertNotEquals( $css1, $css2, 'Preview should not use cached CSS' );
		$this->assertStringContainsString( '#1e1e2e', $css1, 'First CSS should have first color' );
		$this->assertStringContainsString( '#ff0000', $css2, 'Second CSS should have second color' );
	}

	/**
	 * Test preview with field name aliases
	 */
	public function test_preview_with_field_name_aliases() {
		wp_set_current_user( $this->admin_user );

		// Use alias field names
		$settings = array(
			'menu_bg' => '#1e1e2e', // Alias for menu_background
			'admin_bar_bg' => '#23282d', // Alias for admin_bar_background
		);

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/preview' );
		$request->set_param( 'settings', $settings );

		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status(), 'Should accept alias field names' );

		$data = $response->get_data();
		$css = $data['data']['css'];

		// Verify CSS contains the colors
		$this->assertStringContainsString( '#1e1e2e', $css, 'Should process menu_bg alias' );
		$this->assertStringContainsString( '#23282d', $css, 'Should process admin_bar_bg alias' );
	}

	/**
	 * Test preview with complex settings
	 */
	public function test_preview_with_complex_settings() {
		wp_set_current_user( $this->admin_user );

		$settings = array(
			'menu_background' => '#1e1e2e',
			'menu_text_color' => '#ffffff',
			'menu_hover_background' => '#2d2d44',
			'menu_detached' => true,
			'glassmorphism_effects' => true,
			'glassmorphism_blur' => 15,
			'enable_animations' => true,
			'animation_speed' => 400,
			'enable_shadows' => true,
			'shadow_blur' => 12,
		);

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/preview' );
		$request->set_param( 'settings', $settings );

		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status(), 'Should handle complex settings' );

		$data = $response->get_data();
		$css = $data['data']['css'];

		// Verify CSS includes various features
		$this->assertStringContainsString( 'backdrop-filter', $css, 'Should include glassmorphism' );
		$this->assertStringContainsString( 'transition', $css, 'Should include animations' );
		$this->assertStringContainsString( 'box-shadow', $css, 'Should include shadows' );
	}
}
