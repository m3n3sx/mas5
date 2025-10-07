<?php
/**
 * Integration Tests for MAS Themes REST API Endpoints
 *
 * Tests theme listing, filtering, custom theme creation, validation,
 * theme application, CSS updates, and predefined theme protection.
 *
 * @package Modern_Admin_Styler_V2
 * @subpackage Tests
 */

/**
 * Test case for MAS Themes REST API Integration
 */
class TestMASThemesIntegration extends WP_UnitTestCase {

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
	 * Themes controller instance
	 *
	 * @var MAS_Themes_Controller
	 */
	protected $controller;

	/**
	 * Theme service instance
	 *
	 * @var MAS_Theme_Service
	 */
	protected $service;

	/**
	 * Settings service instance
	 *
	 * @var MAS_Settings_Service
	 */
	protected $settings_service;

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
	protected $route = '/mas-v2/v1/themes';

	/**
	 * Set up test environment
	 */
	public function setUp() {
		parent::setUp();

		// Create test users
		$this->admin_user = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->editor_user = $this->factory->user->create( array( 'role' => 'editor' ) );

		// Load required files
		require_once MAS_V2_PLUGIN_DIR . 'includes/api/class-mas-rest-controller.php';
		require_once MAS_V2_PLUGIN_DIR . 'includes/services/class-mas-settings-service.php';
		require_once MAS_V2_PLUGIN_DIR . 'includes/services/class-mas-validation-service.php';
		require_once MAS_V2_PLUGIN_DIR . 'includes/services/class-mas-theme-service.php';
		require_once MAS_V2_PLUGIN_DIR . 'includes/api/class-mas-themes-controller.php';

		// Initialize controller and services
		$this->controller = new MAS_Themes_Controller();
		$this->service = MAS_Theme_Service::get_instance();
		$this->settings_service = MAS_Settings_Service::get_instance();

		// Register routes
		$this->controller->register_routes();

		// Clean up custom themes before each test
		delete_option( 'mas_v2_custom_themes' );
		delete_option( 'mas_v2_settings' );
		wp_cache_flush();
	}

	/**
	 * Tear down test environment
	 */
	public function tearDown() {
		parent::tearDown();
		wp_set_current_user( 0 );
		delete_option( 'mas_v2_custom_themes' );
		delete_option( 'mas_v2_settings' );
		wp_cache_flush();
	}

	/**
	 * Test theme listing - get all themes
	 * Requirements: 12.1, 12.2
	 */
	public function test_get_all_themes() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status(), 'GET themes should return 200' );
		$data = $response->get_data();
		$this->assertTrue( $data['success'], 'Response should indicate success' );
		$this->assertArrayHasKey( 'data', $data, 'Response should contain data' );
		$this->assertIsArray( $data['data'], 'Themes should be an array' );
		$this->assertGreaterThan( 0, count( $data['data'] ), 'Should have predefined themes' );

		// Verify predefined themes are present
		$theme_ids = array_column( $data['data'], 'id' );
		$this->assertContains( 'default', $theme_ids, 'Should include default theme' );
		$this->assertContains( 'dark-blue', $theme_ids, 'Should include dark-blue theme' );
		$this->assertContains( 'light-modern', $theme_ids, 'Should include light-modern theme' );
	}

	/**
	 * Test theme filtering by type - predefined
	 * Requirements: 12.1, 12.2
	 */
	public function test_filter_themes_by_predefined_type() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'GET', $this->route );
		$request->set_param( 'type', 'predefined' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$themes = $data['data'];

		$this->assertGreaterThan( 0, count( $themes ), 'Should have predefined themes' );

		// Verify all returned themes are predefined
		foreach ( $themes as $theme ) {
			$this->assertEquals( 'predefined', $theme['type'], 'All themes should be predefined type' );
			$this->assertTrue( $theme['readonly'], 'Predefined themes should be readonly' );
		}
	}

	/**
	 * Test theme filtering by type - custom
	 * Requirements: 12.1, 12.2
	 */
	public function test_filter_themes_by_custom_type() {
		wp_set_current_user( $this->admin_user );

		// Create a custom theme first
		$custom_theme = array(
			'id' => 'test-custom',
			'name' => 'Test Custom Theme',
			'settings' => array(
				'menu_background' => '#123456',
				'menu_text_color' => '#ffffff',
			),
		);

		$create_request = new WP_REST_Request( 'POST', $this->route );
		$create_request->set_header( 'Content-Type', 'application/json' );
		$create_request->set_body( wp_json_encode( $custom_theme ) );
		rest_do_request( $create_request );

		// Now filter by custom type
		$request = new WP_REST_Request( 'GET', $this->route );
		$request->set_param( 'type', 'custom' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$themes = $data['data'];

		$this->assertGreaterThan( 0, count( $themes ), 'Should have custom themes' );

		// Verify all returned themes are custom
		foreach ( $themes as $theme ) {
			$this->assertEquals( 'custom', $theme['type'], 'All themes should be custom type' );
			$this->assertFalse( $theme['readonly'], 'Custom themes should not be readonly' );
		}
	}

	/**
	 * Test get specific theme by ID
	 * Requirements: 12.1, 12.2
	 */
	public function test_get_specific_theme() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'GET', $this->route . '/dark-blue' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$theme = $data['data'];

		$this->assertEquals( 'dark-blue', $theme['id'] );
		$this->assertEquals( 'predefined', $theme['type'] );
		$this->assertTrue( $theme['readonly'] );
		$this->assertArrayHasKey( 'settings', $theme );
		$this->assertArrayHasKey( 'metadata', $theme );
	}

	/**
	 * Test get non-existent theme
	 * Requirements: 12.1, 12.2
	 */
	public function test_get_nonexistent_theme() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'GET', $this->route . '/nonexistent-theme' );
		$response = rest_do_request( $request );

		$this->assertEquals( 404, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'theme_not_found', $data['code'] );
	}

	/**
	 * Test custom theme creation with valid data
	 * Requirements: 12.1, 12.2
	 */
	public function test_create_custom_theme_success() {
		wp_set_current_user( $this->admin_user );

		$theme_data = array(
			'id' => 'my-custom-theme',
			'name' => 'My Custom Theme',
			'description' => 'A beautiful custom theme',
			'settings' => array(
				'menu_background' => '#1a1a2e',
				'menu_text_color' => '#eaeaea',
				'menu_hover_background' => '#16213e',
				'menu_hover_text_color' => '#0f3460',
				'admin_bar_background' => '#1a1a2e',
				'admin_bar_text_color' => '#eaeaea',
			),
		);

		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $theme_data ) );
		$response = rest_do_request( $request );

		$this->assertEquals( 201, $response->get_status(), 'Create should return 201' );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'data', $data );

		$created_theme = $data['data'];
		$this->assertEquals( 'my-custom-theme', $created_theme['id'] );
		$this->assertEquals( 'My Custom Theme', $created_theme['name'] );
		$this->assertEquals( 'custom', $created_theme['type'] );
		$this->assertFalse( $created_theme['readonly'] );
		$this->assertArrayHasKey( 'metadata', $created_theme );
		$this->assertArrayHasKey( 'created', $created_theme['metadata'] );
	}

	/**
	 * Test custom theme creation without required fields
	 * Requirements: 12.1, 12.2
	 */
	public function test_create_theme_missing_required_fields() {
		wp_set_current_user( $this->admin_user );

		// Missing 'id' field
		$theme_data = array(
			'name' => 'Incomplete Theme',
			'settings' => array(
				'menu_background' => '#123456',
			),
		);

		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $theme_data ) );
		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'validation_failed', $data['code'] );
		$this->assertArrayHasKey( 'errors', $data['data'] );
	}

	/**
	 * Test custom theme creation with invalid ID format
	 * Requirements: 12.1, 12.2
	 */
	public function test_create_theme_invalid_id_format() {
		wp_set_current_user( $this->admin_user );

		$invalid_ids = array(
			'Invalid ID',      // Contains space and uppercase
			'invalid_id',      // Contains underscore
			'invalid.id',      // Contains dot
			'INVALID',         // Uppercase
			'123-invalid',     // Starts with number (acceptable but test various)
		);

		foreach ( $invalid_ids as $invalid_id ) {
			$theme_data = array(
				'id' => $invalid_id,
				'name' => 'Test Theme',
				'settings' => array(
					'menu_background' => '#123456',
				),
			);

			$request = new WP_REST_Request( 'POST', $this->route );
			$request->set_header( 'Content-Type', 'application/json' );
			$request->set_body( wp_json_encode( $theme_data ) );
			$response = rest_do_request( $request );

			// IDs with uppercase, spaces, underscores, dots should fail
			if ( preg_match( '/[^a-z0-9-]/', $invalid_id ) ) {
				$this->assertEquals( 400, $response->get_status(), "ID '{$invalid_id}' should be invalid" );
			}
		}
	}

	/**
	 * Test custom theme creation with duplicate ID
	 * Requirements: 12.1, 12.2
	 */
	public function test_create_theme_duplicate_id() {
		wp_set_current_user( $this->admin_user );

		$theme_data = array(
			'id' => 'duplicate-test',
			'name' => 'First Theme',
			'settings' => array(
				'menu_background' => '#123456',
			),
		);

		// Create first theme
		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $theme_data ) );
		$response = rest_do_request( $request );
		$this->assertEquals( 201, $response->get_status() );

		// Try to create another theme with same ID
		$theme_data['name'] = 'Second Theme';
		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $theme_data ) );
		$response = rest_do_request( $request );

		$this->assertEquals( 409, $response->get_status(), 'Duplicate ID should return 409 Conflict' );
		$data = $response->get_data();
		$this->assertEquals( 'theme_exists', $data['code'] );
	}

	/**
	 * Test custom theme creation with reserved ID (predefined theme ID)
	 * Requirements: 12.1, 12.2
	 */
	public function test_create_theme_reserved_id() {
		wp_set_current_user( $this->admin_user );

		$reserved_ids = array( 'default', 'dark-blue', 'light-modern', 'ocean', 'sunset', 'forest' );

		foreach ( $reserved_ids as $reserved_id ) {
			$theme_data = array(
				'id' => $reserved_id,
				'name' => 'Test Theme',
				'settings' => array(
					'menu_background' => '#123456',
				),
			);

			$request = new WP_REST_Request( 'POST', $this->route );
			$request->set_header( 'Content-Type', 'application/json' );
			$request->set_body( wp_json_encode( $theme_data ) );
			$response = rest_do_request( $request );

			$this->assertEquals( 400, $response->get_status(), "Reserved ID '{$reserved_id}' should be rejected" );
			$data = $response->get_data();
			$this->assertEquals( 'reserved_theme_id', $data['code'] );
		}
	}

	/**
	 * Test custom theme creation with invalid color values
	 * Requirements: 12.1, 12.2
	 */
	public function test_create_theme_invalid_colors() {
		wp_set_current_user( $this->admin_user );

		$theme_data = array(
			'id' => 'invalid-colors',
			'name' => 'Invalid Colors Theme',
			'settings' => array(
				'menu_background' => 'not-a-color',
				'menu_text_color' => 'invalid',
			),
		);

		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $theme_data ) );
		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'validation_failed', $data['code'] );
		$this->assertArrayHasKey( 'errors', $data['data'] );
	}

	/**
	 * Test custom theme creation with valid color formats
	 * Requirements: 12.1, 12.2
	 */
	public function test_create_theme_valid_color_formats() {
		wp_set_current_user( $this->admin_user );

		$valid_colors = array(
			'#ffffff',
			'#fff',
			'#123abc',
			'#ABC',
		);

		$theme_counter = 0;
		foreach ( $valid_colors as $color ) {
			$theme_data = array(
				'id' => 'color-test-' . $theme_counter++,
				'name' => 'Color Test Theme',
				'settings' => array(
					'menu_background' => $color,
				),
			);

			$request = new WP_REST_Request( 'POST', $this->route );
			$request->set_header( 'Content-Type', 'application/json' );
			$request->set_body( wp_json_encode( $theme_data ) );
			$response = rest_do_request( $request );

			$this->assertEquals( 201, $response->get_status(), "Color '{$color}' should be valid" );
		}
	}

	/**
	 * Test update custom theme
	 * Requirements: 12.1, 12.2
	 */
	public function test_update_custom_theme() {
		wp_set_current_user( $this->admin_user );

		// Create a theme first
		$theme_data = array(
			'id' => 'update-test',
			'name' => 'Original Name',
			'description' => 'Original description',
			'settings' => array(
				'menu_background' => '#111111',
			),
		);

		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $theme_data ) );
		rest_do_request( $request );

		// Update the theme
		$update_data = array(
			'name' => 'Updated Name',
			'description' => 'Updated description',
			'settings' => array(
				'menu_background' => '#222222',
				'menu_text_color' => '#ffffff',
			),
		);

		$request = new WP_REST_Request( 'PUT', $this->route . '/update-test' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $update_data ) );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );

		$updated_theme = $data['data'];
		$this->assertEquals( 'Updated Name', $updated_theme['name'] );
		$this->assertEquals( 'Updated description', $updated_theme['description'] );
		$this->assertEquals( '#222222', $updated_theme['settings']['menu_background'] );
		$this->assertArrayHasKey( 'modified', $updated_theme['metadata'] );
	}

	/**
	 * Test update non-existent theme
	 * Requirements: 12.1, 12.2
	 */
	public function test_update_nonexistent_theme() {
		wp_set_current_user( $this->admin_user );

		$update_data = array(
			'name' => 'Updated Name',
		);

		$request = new WP_REST_Request( 'PUT', $this->route . '/nonexistent' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $update_data ) );
		$response = rest_do_request( $request );

		$this->assertEquals( 404, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'theme_not_found', $data['code'] );
	}

	/**
	 * Test update predefined theme (should fail - readonly protection)
	 * Requirements: 12.1, 12.2
	 */
	public function test_update_predefined_theme_protection() {
		wp_set_current_user( $this->admin_user );

		$update_data = array(
			'name' => 'Hacked Default Theme',
			'settings' => array(
				'menu_background' => '#hacked',
			),
		);

		$request = new WP_REST_Request( 'PUT', $this->route . '/default' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $update_data ) );
		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status(), 'Predefined themes should be protected' );
		$data = $response->get_data();
		$this->assertEquals( 'theme_readonly', $data['code'] );
	}

	/**
	 * Test delete custom theme
	 * Requirements: 12.1, 12.2
	 */
	public function test_delete_custom_theme() {
		wp_set_current_user( $this->admin_user );

		// Create a theme first
		$theme_data = array(
			'id' => 'delete-test',
			'name' => 'Delete Test Theme',
			'settings' => array(
				'menu_background' => '#123456',
			),
		);

		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $theme_data ) );
		rest_do_request( $request );

		// Delete the theme
		$request = new WP_REST_Request( 'DELETE', $this->route . '/delete-test' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertTrue( $data['data']['deleted'] );
		$this->assertEquals( 'delete-test', $data['data']['id'] );

		// Verify theme is actually deleted
		$request = new WP_REST_Request( 'GET', $this->route . '/delete-test' );
		$response = rest_do_request( $request );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test delete predefined theme (should fail - readonly protection)
	 * Requirements: 12.1, 12.2
	 */
	public function test_delete_predefined_theme_protection() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'DELETE', $this->route . '/dark-blue' );
		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status(), 'Predefined themes should be protected from deletion' );
		$data = $response->get_data();
		$this->assertEquals( 'theme_readonly', $data['code'] );

		// Verify theme still exists
		$request = new WP_REST_Request( 'GET', $this->route . '/dark-blue' );
		$response = rest_do_request( $request );
		$this->assertEquals( 200, $response->get_status(), 'Theme should still exist' );
	}

	/**
	 * Test delete non-existent theme
	 * Requirements: 12.1, 12.2
	 */
	public function test_delete_nonexistent_theme() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'DELETE', $this->route . '/nonexistent' );
		$response = rest_do_request( $request );

		$this->assertEquals( 404, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'theme_not_found', $data['code'] );
	}

	/**
	 * Test apply predefined theme
	 * Requirements: 12.1, 12.2
	 */
	public function test_apply_predefined_theme() {
		wp_set_current_user( $this->admin_user );

		// Get initial settings
		$initial_settings = $this->settings_service->get_settings();

		// Apply dark-blue theme
		$request = new WP_REST_Request( 'POST', $this->route . '/dark-blue/apply' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertTrue( $data['data']['applied'] );
		$this->assertEquals( 'dark-blue', $data['data']['theme_id'] );

		// Verify settings were updated
		$updated_settings = $this->settings_service->get_settings();
		$this->assertEquals( '#1e1e2e', $updated_settings['menu_background'], 'Theme colors should be applied' );
		$this->assertEquals( 'dark-blue', $updated_settings['current_theme'], 'Current theme should be updated' );
		$this->assertNotEquals( $initial_settings['menu_background'], $updated_settings['menu_background'] );
	}

	/**
	 * Test apply custom theme
	 * Requirements: 12.1, 12.2
	 */
	public function test_apply_custom_theme() {
		wp_set_current_user( $this->admin_user );

		// Create a custom theme
		$theme_data = array(
			'id' => 'apply-test',
			'name' => 'Apply Test Theme',
			'settings' => array(
				'menu_background' => '#custom1',
				'menu_text_color' => '#custom2',
				'admin_bar_background' => '#custom3',
			),
		);

		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $theme_data ) );
		rest_do_request( $request );

		// Apply the custom theme
		$request = new WP_REST_Request( 'POST', $this->route . '/apply-test/apply' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );

		// Verify settings were updated with custom theme colors
		$updated_settings = $this->settings_service->get_settings();
		$this->assertEquals( '#custom1', $updated_settings['menu_background'] );
		$this->assertEquals( '#custom2', $updated_settings['menu_text_color'] );
		$this->assertEquals( '#custom3', $updated_settings['admin_bar_background'] );
		$this->assertEquals( 'apply-test', $updated_settings['current_theme'] );
	}

	/**
	 * Test apply non-existent theme
	 * Requirements: 12.1, 12.2
	 */
	public function test_apply_nonexistent_theme() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'POST', $this->route . '/nonexistent/apply' );
		$response = rest_do_request( $request );

		$this->assertEquals( 404, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'theme_not_found', $data['code'] );
	}

	/**
	 * Test CSS generation after theme application
	 * Requirements: 12.1, 12.2
	 */
	public function test_css_generation_on_theme_apply() {
		wp_set_current_user( $this->admin_user );

		// Clear CSS cache
		delete_transient( 'mas_v2_generated_css' );

		// Apply a theme
		$request = new WP_REST_Request( 'POST', $this->route . '/ocean/apply' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Verify CSS was generated
		$cached_css = get_transient( 'mas_v2_generated_css' );
		$this->assertNotFalse( $cached_css, 'CSS should be generated after theme application' );
		$this->assertStringContainsString( '#006994', $cached_css, 'CSS should contain ocean theme colors' );
	}

	/**
	 * Test theme application preserves non-theme settings
	 * Requirements: 12.1, 12.2
	 */
	public function test_theme_apply_preserves_other_settings() {
		wp_set_current_user( $this->admin_user );

		// Set some custom settings that are not part of theme
		$custom_settings = array(
			'menu_background' => '#initial',
			'enable_animations' => true,
			'animation_speed' => 500,
			'custom_field' => 'custom_value',
		);
		$this->settings_service->save_settings( $custom_settings );

		// Apply a theme
		$request = new WP_REST_Request( 'POST', $this->route . '/forest/apply' );
		rest_do_request( $request );

		// Verify theme colors were applied but other settings preserved
		$updated_settings = $this->settings_service->get_settings();
		$this->assertEquals( '#2e7d32', $updated_settings['menu_background'], 'Theme color should be applied' );
		$this->assertTrue( $updated_settings['enable_animations'], 'Non-theme settings should be preserved' );
		$this->assertEquals( 500, $updated_settings['animation_speed'], 'Non-theme settings should be preserved' );
		$this->assertEquals( 'custom_value', $updated_settings['custom_field'], 'Custom fields should be preserved' );
	}

	/**
	 * Test authentication requirement for theme endpoints
	 * Requirements: 12.1, 12.2
	 */
	public function test_theme_endpoints_require_authentication() {
		wp_set_current_user( 0 );

		// Test GET
		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );
		$this->assertEquals( 403, $response->get_status(), 'GET should require authentication' );

		// Test POST
		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'id' => 'test', 'name' => 'Test' ) ) );
		$response = rest_do_request( $request );
		$this->assertEquals( 403, $response->get_status(), 'POST should require authentication' );

		// Test PUT
		$request = new WP_REST_Request( 'PUT', $this->route . '/test' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'name' => 'Test' ) ) );
		$response = rest_do_request( $request );
		$this->assertEquals( 403, $response->get_status(), 'PUT should require authentication' );

		// Test DELETE
		$request = new WP_REST_Request( 'DELETE', $this->route . '/test' );
		$response = rest_do_request( $request );
		$this->assertEquals( 403, $response->get_status(), 'DELETE should require authentication' );

		// Test APPLY
		$request = new WP_REST_Request( 'POST', $this->route . '/test/apply' );
		$response = rest_do_request( $request );
		$this->assertEquals( 403, $response->get_status(), 'APPLY should require authentication' );
	}

	/**
	 * Test authorization requirement (manage_options capability)
	 * Requirements: 12.1, 12.2
	 */
	public function test_theme_endpoints_require_manage_options() {
		wp_set_current_user( $this->editor_user );

		// Editor doesn't have manage_options capability
		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );
		$this->assertEquals( 403, $response->get_status(), 'Editor should not have permission' );

		$theme_data = array(
			'id' => 'test',
			'name' => 'Test',
			'settings' => array( 'menu_background' => '#123456' ),
		);

		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $theme_data ) );
		$response = rest_do_request( $request );
		$this->assertEquals( 403, $response->get_status(), 'Editor should not be able to create themes' );
	}

	/**
	 * Test theme data sanitization
	 * Requirements: 12.1, 12.2
	 */
	public function test_theme_data_sanitization() {
		wp_set_current_user( $this->admin_user );

		$theme_data = array(
			'id' => 'sanitize-test',
			'name' => '<script>alert("xss")</script>Sanitize Test',
			'description' => '<b>Bold</b> description with <script>bad code</script>',
			'settings' => array(
				'menu_background' => '#123456',
			),
		);

		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $theme_data ) );
		$response = rest_do_request( $request );

		$this->assertEquals( 201, $response->get_status() );
		$data = $response->get_data();
		$created_theme = $data['data'];

		// Verify XSS was sanitized
		$this->assertStringNotContainsString( '<script>', $created_theme['name'], 'Scripts should be sanitized' );
		$this->assertStringNotContainsString( '<script>', $created_theme['description'], 'Scripts should be sanitized' );
	}

	/**
	 * Test theme caching behavior
	 * Requirements: 12.1, 12.2
	 */
	public function test_theme_caching() {
		wp_set_current_user( $this->admin_user );

		// Get themes (should cache)
		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );
		$this->assertEquals( 200, $response->get_status() );

		// Verify cache was set
		$cached = wp_cache_get( 'all_themes', 'mas_v2_themes' );
		$this->assertNotFalse( $cached, 'Themes should be cached' );

		// Create a new theme (should clear cache)
		$theme_data = array(
			'id' => 'cache-test',
			'name' => 'Cache Test',
			'settings' => array( 'menu_background' => '#123456' ),
		);

		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $theme_data ) );
		rest_do_request( $request );

		// Get themes again - should include new theme
		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );
		$data = $response->get_data();
		$theme_ids = array_column( $data['data'], 'id' );
		$this->assertContains( 'cache-test', $theme_ids, 'New theme should be in list' );
	}

	/**
	 * Test complete theme workflow
	 * Requirements: 12.1, 12.2
	 */
	public function test_complete_theme_workflow() {
		wp_set_current_user( $this->admin_user );

		// Step 1: List all themes
		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );
		$this->assertEquals( 200, $response->get_status() );
		$initial_count = count( $response->get_data()['data'] );

		// Step 2: Create a custom theme
		$theme_data = array(
			'id' => 'workflow-test',
			'name' => 'Workflow Test Theme',
			'description' => 'Testing complete workflow',
			'settings' => array(
				'menu_background' => '#workflow',
				'menu_text_color' => '#ffffff',
			),
		);

		$request = new WP_REST_Request( 'POST', $this->route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $theme_data ) );
		$response = rest_do_request( $request );
		$this->assertEquals( 201, $response->get_status() );

		// Step 3: Verify theme was created
		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );
		$this->assertEquals( $initial_count + 1, count( $response->get_data()['data'] ) );

		// Step 4: Update the theme
		$update_data = array(
			'name' => 'Updated Workflow Theme',
			'settings' => array(
				'menu_background' => '#updated',
			),
		);

		$request = new WP_REST_Request( 'PUT', $this->route . '/workflow-test' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $update_data ) );
		$response = rest_do_request( $request );
		$this->assertEquals( 200, $response->get_status() );

		// Step 5: Apply the theme
		$request = new WP_REST_Request( 'POST', $this->route . '/workflow-test/apply' );
		$response = rest_do_request( $request );
		$this->assertEquals( 200, $response->get_status() );

		// Step 6: Verify theme was applied
		$settings = $this->settings_service->get_settings();
		$this->assertEquals( '#updated', $settings['menu_background'] );
		$this->assertEquals( 'workflow-test', $settings['current_theme'] );

		// Step 7: Delete the theme
		$request = new WP_REST_Request( 'DELETE', $this->route . '/workflow-test' );
		$response = rest_do_request( $request );
		$this->assertEquals( 200, $response->get_status() );

		// Step 8: Verify theme was deleted
		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );
		$this->assertEquals( $initial_count, count( $response->get_data()['data'] ) );
	}
}

