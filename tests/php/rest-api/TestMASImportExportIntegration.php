<?php
/**
 * Integration Tests for MAS Import/Export REST API Endpoints
 *
 * Tests export with proper headers and format, import with valid and invalid data,
 * automatic backup creation on import, and legacy format migration.
 *
 * @package Modern_Admin_Styler_V2
 * @subpackage Tests
 */

/**
 * Test case for MAS Import/Export REST API Integration
 */
class TestMASImportExportIntegration extends WP_UnitTestCase {

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
	 * Import/Export controller instance
	 *
	 * @var MAS_Import_Export_Controller
	 */
	protected $controller;

	/**
	 * Import/Export service instance
	 *
	 * @var MAS_Import_Export_Service
	 */
	protected $service;

	/**
	 * Settings service instance
	 *
	 * @var MAS_Settings_Service
	 */
	protected $settings_service;

	/**
	 * Backup service instance
	 *
	 * @var MAS_Backup_Service
	 */
	protected $backup_service;

	/**
	 * REST API namespace
	 *
	 * @var string
	 */
	protected $namespace = 'mas-v2/v1';

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
		require_once MAS_V2_PLUGIN_DIR . 'includes/services/class-mas-backup-service.php';
		require_once MAS_V2_PLUGIN_DIR . 'includes/services/class-mas-validation-service.php';
		require_once MAS_V2_PLUGIN_DIR . 'includes/services/class-mas-import-export-service.php';
		require_once MAS_V2_PLUGIN_DIR . 'includes/api/class-mas-import-export-controller.php';

		// Initialize controller and services
		$this->controller = new MAS_Import_Export_Controller();
		$this->service = MAS_Import_Export_Service::get_instance();
		$this->settings_service = MAS_Settings_Service::get_instance();
		$this->backup_service = MAS_Backup_Service::get_instance();

		// Register routes
		$this->controller->register_routes();

		// Reset settings and backups
		delete_option( 'mas_v2_settings' );
		$this->cleanup_all_backups();
		wp_cache_flush();
	}

	/**
	 * Tear down test environment
	 */
	public function tearDown() {
		// Clean up
		delete_option( 'mas_v2_settings' );
		$this->cleanup_all_backups();
		wp_cache_flush();

		parent::tearDown();
	}

	/**
	 * Clean up all backups
	 */
	protected function cleanup_all_backups() {
		$backups = $this->backup_service->list_backups();
		foreach ( $backups as $backup ) {
			$this->backup_service->delete_backup( $backup['id'] );
		}
	}

	/**
	 * Test export endpoint requires authentication
	 */
	public function test_export_requires_authentication() {
		$request = new WP_REST_Request( 'GET', '/mas-v2/v1/export' );
		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status() );
		$this->assertInstanceOf( 'WP_Error', $response->as_error() );
	}

	/**
	 * Test export endpoint with admin user
	 */
	public function test_export_with_admin_user() {
		wp_set_current_user( $this->admin_user );

		// Set some test settings
		$test_settings = array(
			'menu_background' => '#1e1e2e',
			'menu_text_color' => '#ffffff',
			'admin_bar_background' => '#23282d',
		);
		$this->settings_service->save_settings( $test_settings );

		$request = new WP_REST_Request( 'GET', '/mas-v2/v1/export' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'data', $data );
		$this->assertArrayHasKey( 'settings', $data['data'] );
		$this->assertArrayHasKey( 'metadata', $data['data'] );
		$this->assertArrayHasKey( 'filename', $data );

		// Verify settings are included
		$exported_settings = $data['data']['settings'];
		$this->assertEquals( '#1e1e2e', $exported_settings['menu_background'] );
		$this->assertEquals( '#ffffff', $exported_settings['menu_text_color'] );
	}

	/**
	 * Test export with proper headers
	 */
	public function test_export_has_proper_headers() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'GET', '/mas-v2/v1/export' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Check headers
		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'Content-Disposition', $headers );
		$this->assertStringContainsString( 'attachment', $headers['Content-Disposition'] );
		$this->assertStringContainsString( '.json', $headers['Content-Disposition'] );
	}

	/**
	 * Test export metadata includes version information
	 */
	public function test_export_includes_version_metadata() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'GET', '/mas-v2/v1/export' );
		$response = rest_do_request( $request );

		$data = $response->get_data();
		$metadata = $data['data']['metadata'];

		$this->assertArrayHasKey( 'export_version', $metadata );
		$this->assertArrayHasKey( 'plugin_version', $metadata );
		$this->assertArrayHasKey( 'wordpress_version', $metadata );
		$this->assertArrayHasKey( 'export_date', $metadata );
		$this->assertArrayHasKey( 'export_timestamp', $metadata );
	}

	/**
	 * Test import endpoint requires authentication
	 */
	public function test_import_requires_authentication() {
		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/import' );
		$request->set_param( 'data', array( 'settings' => array() ) );
		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test import with valid data
	 */
	public function test_import_with_valid_data() {
		wp_set_current_user( $this->admin_user );

		// Create import data
		$import_data = array(
			'settings' => array(
				'menu_background' => '#2d2d44',
				'menu_text_color' => '#00a0d2',
				'admin_bar_background' => '#32373c',
			),
			'metadata' => array(
				'export_version' => '2.2.0',
				'plugin_version' => '2.2.0',
			),
		);

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/import' );
		$request->set_param( 'data', $import_data );
		$request->set_param( 'create_backup', true );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertTrue( $data['data']['imported'] );
		$this->assertTrue( $data['data']['backup_created'] );

		// Verify settings were imported
		$settings = $this->settings_service->get_settings();
		$this->assertEquals( '#2d2d44', $settings['menu_background'] );
		$this->assertEquals( '#00a0d2', $settings['menu_text_color'] );
	}

	/**
	 * Test import with invalid data structure
	 */
	public function test_import_with_invalid_data_structure() {
		wp_set_current_user( $this->admin_user );

		// Missing settings key
		$import_data = array(
			'metadata' => array(
				'export_version' => '2.2.0',
			),
		);

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/import' );
		$request->set_param( 'data', $import_data );
		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertInstanceOf( 'WP_Error', $response->as_error() );
	}

	/**
	 * Test import with invalid JSON string
	 */
	public function test_import_with_invalid_json_string() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/import' );
		$request->set_param( 'data', 'invalid json {' );
		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status() );
		$error = $response->as_error();
		$this->assertEquals( 'invalid_json', $error->get_error_code() );
	}

	/**
	 * Test import with valid JSON string
	 */
	public function test_import_with_valid_json_string() {
		wp_set_current_user( $this->admin_user );

		$import_data = array(
			'settings' => array(
				'menu_background' => '#3d3d5c',
				'menu_text_color' => '#ffffff',
			),
			'metadata' => array(
				'export_version' => '2.2.0',
			),
		);

		$json_string = json_encode( $import_data );

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/import' );
		$request->set_param( 'data', $json_string );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Verify settings were imported
		$settings = $this->settings_service->get_settings();
		$this->assertEquals( '#3d3d5c', $settings['menu_background'] );
	}

	/**
	 * Test import creates automatic backup
	 */
	public function test_import_creates_automatic_backup() {
		wp_set_current_user( $this->admin_user );

		// Set initial settings
		$initial_settings = array(
			'menu_background' => '#111111',
			'menu_text_color' => '#222222',
		);
		$this->settings_service->save_settings( $initial_settings );

		// Count backups before import
		$backups_before = $this->backup_service->list_backups();
		$count_before = count( $backups_before );

		// Import new settings
		$import_data = array(
			'settings' => array(
				'menu_background' => '#333333',
				'menu_text_color' => '#444444',
			),
			'metadata' => array(
				'export_version' => '2.2.0',
			),
		);

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/import' );
		$request->set_param( 'data', $import_data );
		$request->set_param( 'create_backup', true );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Count backups after import
		$backups_after = $this->backup_service->list_backups();
		$count_after = count( $backups_after );

		// Verify backup was created
		$this->assertEquals( $count_before + 1, $count_after );

		// Verify backup contains old settings
		$latest_backup = $backups_after[0];
		$backup_data = $this->backup_service->get_backup( $latest_backup['id'] );
		$this->assertEquals( '#111111', $backup_data['settings']['menu_background'] );
	}

	/**
	 * Test import without creating backup
	 */
	public function test_import_without_backup() {
		wp_set_current_user( $this->admin_user );

		// Count backups before import
		$backups_before = $this->backup_service->list_backups();
		$count_before = count( $backups_before );

		// Import settings without backup
		$import_data = array(
			'settings' => array(
				'menu_background' => '#555555',
			),
			'metadata' => array(
				'export_version' => '2.2.0',
			),
		);

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/import' );
		$request->set_param( 'data', $import_data );
		$request->set_param( 'create_backup', false );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Count backups after import
		$backups_after = $this->backup_service->list_backups();
		$count_after = count( $backups_after );

		// Verify no backup was created
		$this->assertEquals( $count_before, $count_after );
	}

	/**
	 * Test import with incompatible version
	 */
	public function test_import_with_incompatible_version() {
		wp_set_current_user( $this->admin_user );

		$import_data = array(
			'settings' => array(
				'menu_background' => '#666666',
			),
			'metadata' => array(
				'export_version' => '1.0.0', // Too old
				'plugin_version' => '1.0.0',
			),
		);

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/import' );
		$request->set_param( 'data', $import_data );
		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status() );
		$error = $response->as_error();
		$this->assertEquals( 'incompatible_version', $error->get_error_code() );
	}

	/**
	 * Test import with legacy format (no metadata)
	 */
	public function test_import_with_legacy_format() {
		wp_set_current_user( $this->admin_user );

		// Legacy format without metadata
		$import_data = array(
			'settings' => array(
				'menu_background' => '#777777',
				'menu_text_color' => '#888888',
			),
		);

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/import' );
		$request->set_param( 'data', $import_data );
		$response = rest_do_request( $request );

		// Should succeed with migration
		$this->assertEquals( 200, $response->get_status() );

		// Verify settings were imported
		$settings = $this->settings_service->get_settings();
		$this->assertEquals( '#777777', $settings['menu_background'] );
	}

	/**
	 * Test import with field aliases
	 */
	public function test_import_with_field_aliases() {
		wp_set_current_user( $this->admin_user );

		// Use old field names
		$import_data = array(
			'settings' => array(
				'menu_bg' => '#999999',  // Old alias
				'menu_txt' => '#aaaaaa', // Old alias
			),
			'metadata' => array(
				'export_version' => '2.0.0',
			),
		);

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/import' );
		$request->set_param( 'data', $import_data );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Verify aliases were converted to new field names
		$settings = $this->settings_service->get_settings();
		$this->assertEquals( '#999999', $settings['menu_background'] );
		$this->assertEquals( '#aaaaaa', $settings['menu_text_color'] );
	}

	/**
	 * Test import with invalid color values
	 */
	public function test_import_with_invalid_color_values() {
		wp_set_current_user( $this->admin_user );

		$import_data = array(
			'settings' => array(
				'menu_background' => 'not-a-color',
				'menu_text_color' => '#ffffff',
			),
			'metadata' => array(
				'export_version' => '2.2.0',
			),
		);

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/import' );
		$request->set_param( 'data', $import_data );
		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status() );
		$error = $response->as_error();
		$this->assertEquals( 'settings_validation_failed', $error->get_error_code() );
	}

	/**
	 * Test full export-import workflow
	 */
	public function test_full_export_import_workflow() {
		wp_set_current_user( $this->admin_user );

		// Set initial settings
		$initial_settings = array(
			'menu_background' => '#bbbbbb',
			'menu_text_color' => '#cccccc',
			'admin_bar_background' => '#dddddd',
		);
		$this->settings_service->save_settings( $initial_settings );

		// Export settings
		$export_request = new WP_REST_Request( 'GET', '/mas-v2/v1/export' );
		$export_response = rest_do_request( $export_request );
		$this->assertEquals( 200, $export_response->get_status() );

		$export_data = $export_response->get_data();
		$exported_settings = $export_data['data'];

		// Change settings
		$changed_settings = array(
			'menu_background' => '#000000',
			'menu_text_color' => '#111111',
		);
		$this->settings_service->save_settings( $changed_settings );

		// Import exported settings
		$import_request = new WP_REST_Request( 'POST', '/mas-v2/v1/import' );
		$import_request->set_param( 'data', $exported_settings );
		$import_response = rest_do_request( $import_request );
		$this->assertEquals( 200, $import_response->get_status() );

		// Verify settings were restored
		$restored_settings = $this->settings_service->get_settings();
		$this->assertEquals( '#bbbbbb', $restored_settings['menu_background'] );
		$this->assertEquals( '#cccccc', $restored_settings['menu_text_color'] );
		$this->assertEquals( '#dddddd', $restored_settings['admin_bar_background'] );
	}

	/**
	 * Test import with empty settings
	 */
	public function test_import_with_empty_settings() {
		wp_set_current_user( $this->admin_user );

		$import_data = array(
			'settings' => array(),
			'metadata' => array(
				'export_version' => '2.2.0',
			),
		);

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/import' );
		$request->set_param( 'data', $import_data );
		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test editor user cannot export
	 */
	public function test_editor_cannot_export() {
		wp_set_current_user( $this->editor_user );

		$request = new WP_REST_Request( 'GET', '/mas-v2/v1/export' );
		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test editor user cannot import
	 */
	public function test_editor_cannot_import() {
		wp_set_current_user( $this->editor_user );

		$import_data = array(
			'settings' => array(
				'menu_background' => '#eeeeee',
			),
		);

		$request = new WP_REST_Request( 'POST', '/mas-v2/v1/import' );
		$request->set_param( 'data', $import_data );
		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status() );
	}
}

