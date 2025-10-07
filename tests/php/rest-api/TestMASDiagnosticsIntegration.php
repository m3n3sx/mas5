<?php
/**
 * Integration Tests for MAS Diagnostics REST API Endpoints
 *
 * Tests system information collection, settings integrity validation,
 * conflict detection, health checks, and performance metrics.
 *
 * @package Modern_Admin_Styler_V2
 * @subpackage Tests
 */

/**
 * Test case for MAS Diagnostics REST API Integration
 */
class TestMASDiagnosticsIntegration extends WP_UnitTestCase {

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
	 * Diagnostics controller instance
	 *
	 * @var MAS_Diagnostics_Controller
	 */
	protected $controller;

	/**
	 * Diagnostics service instance
	 *
	 * @var MAS_Diagnostics_Service
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
	protected $route = '/mas-v2/v1/diagnostics';

	/**
	 * Set up test environment
	 */
	public function setUp() {
		parent::setUp();

		// Create test users with different roles
		$this->admin_user = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->editor_user = $this->factory->user->create( array( 'role' => 'editor' ) );

		// Load required files
		require_once MAS_V2_PLUGIN_DIR . 'includes/api/class-mas-rest-controller.php';
		require_once MAS_V2_PLUGIN_DIR . 'includes/services/class-mas-settings-service.php';
		require_once MAS_V2_PLUGIN_DIR . 'includes/services/class-mas-diagnostics-service.php';
		require_once MAS_V2_PLUGIN_DIR . 'includes/api/class-mas-diagnostics-controller.php';

		// Initialize controller and service
		$this->service = new MAS_Diagnostics_Service();
		$this->controller = new MAS_Diagnostics_Controller( $this->service );

		// Register routes
		$this->controller->register_routes();

		// Clear cache
		wp_cache_flush();
	}

	/**
	 * Tear down test environment
	 */
	public function tearDown() {
		parent::tearDown();
		wp_set_current_user( 0 );
		wp_cache_flush();
	}

	/**
	 * Test system information collection
	 * Requirements: 12.1, 12.2
	 */
	public function test_get_system_information() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status(), 'GET diagnostics should return 200' );
		$data = $response->get_data();
		$this->assertTrue( $data['success'], 'Response should indicate success' );
		$this->assertArrayHasKey( 'data', $data, 'Response should contain data' );

		$diagnostics = $data['data'];

		// Verify system information is present
		$this->assertArrayHasKey( 'system', $diagnostics, 'Should contain system information' );
		$system = $diagnostics['system'];

		$this->assertArrayHasKey( 'php_version', $system, 'Should include PHP version' );
		$this->assertArrayHasKey( 'wordpress_version', $system, 'Should include WordPress version' );
		$this->assertArrayHasKey( 'mysql_version', $system, 'Should include MySQL version' );
		$this->assertArrayHasKey( 'server_software', $system, 'Should include server software' );
		$this->assertArrayHasKey( 'php_memory_limit', $system, 'Should include PHP memory limit' );
		$this->assertArrayHasKey( 'rest_api_enabled', $system, 'Should include REST API status' );

		// Verify values are not empty
		$this->assertNotEmpty( $system['php_version'], 'PHP version should not be empty' );
		$this->assertNotEmpty( $system['wordpress_version'], 'WordPress version should not be empty' );
	}

	/**
	 * Test plugin information collection
	 * Requirements: 12.1, 12.2
	 */
	public function test_get_plugin_information() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );

		$data = $response->get_data();
		$diagnostics = $data['data'];

		// Verify plugin information is present
		$this->assertArrayHasKey( 'plugin', $diagnostics, 'Should contain plugin information' );
		$plugin = $diagnostics['plugin'];

		$this->assertArrayHasKey( 'version', $plugin, 'Should include plugin version' );
		$this->assertArrayHasKey( 'name', $plugin, 'Should include plugin name' );
		$this->assertArrayHasKey( 'rest_api_namespace', $plugin, 'Should include REST API namespace' );
		$this->assertArrayHasKey( 'rest_api_available', $plugin, 'Should include REST API availability' );

		$this->assertEquals( 'mas-v2/v1', $plugin['rest_api_namespace'], 'Should have correct namespace' );
	}

	/**
	 * Test settings integrity validation
	 * Requirements: 12.1, 12.2
	 */
	public function test_settings_integrity_validation() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );

		$data = $response->get_data();
		$diagnostics = $data['data'];

		// Verify settings integrity check is present
		$this->assertArrayHasKey( 'settings', $diagnostics, 'Should contain settings integrity check' );
		$settings = $diagnostics['settings'];

		$this->assertArrayHasKey( 'valid', $settings, 'Should include validity status' );
		$this->assertArrayHasKey( 'missing_keys', $settings, 'Should include missing keys' );
		$this->assertArrayHasKey( 'invalid_values', $settings, 'Should include invalid values' );
		$this->assertArrayHasKey( 'total_settings', $settings, 'Should include total settings count' );
		$this->assertArrayHasKey( 'expected_settings', $settings, 'Should include expected settings count' );

		$this->assertIsBool( $settings['valid'], 'Valid should be boolean' );
		$this->assertIsArray( $settings['missing_keys'], 'Missing keys should be array' );
		$this->assertIsArray( $settings['invalid_values'], 'Invalid values should be array' );
	}

	/**
	 * Test settings integrity with invalid data
	 * Requirements: 12.1, 12.2
	 */
	public function test_settings_integrity_with_invalid_data() {
		wp_set_current_user( $this->admin_user );

		// Save invalid settings
		update_option( 'mas_v2_settings', array(
			'menu_background' => 'invalid-color',
			'menu_width' => 'not-a-number',
		) );

		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );

		$data = $response->get_data();
		$diagnostics = $data['data'];
		$settings = $diagnostics['settings'];

		$this->assertFalse( $settings['valid'], 'Settings should be marked as invalid' );
		$this->assertNotEmpty( $settings['invalid_values'], 'Should detect invalid values' );
	}

	/**
	 * Test filesystem checks
	 * Requirements: 12.1, 12.2
	 */
	public function test_filesystem_checks() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );

		$data = $response->get_data();
		$diagnostics = $data['data'];

		// Verify filesystem check is present
		$this->assertArrayHasKey( 'filesystem', $diagnostics, 'Should contain filesystem check' );
		$filesystem = $diagnostics['filesystem'];

		$this->assertArrayHasKey( 'upload_dir_writable', $filesystem, 'Should check upload directory' );
		$this->assertArrayHasKey( 'upload_dir_path', $filesystem, 'Should include upload directory path' );
		$this->assertArrayHasKey( 'plugin_dir_readable', $filesystem, 'Should check plugin directory' );
		$this->assertArrayHasKey( 'plugin_dir_path', $filesystem, 'Should include plugin directory path' );
		$this->assertArrayHasKey( 'required_directories', $filesystem, 'Should check required directories' );

		$this->assertIsBool( $filesystem['upload_dir_writable'], 'Upload dir writable should be boolean' );
		$this->assertIsBool( $filesystem['plugin_dir_readable'], 'Plugin dir readable should be boolean' );
		$this->assertIsArray( $filesystem['required_directories'], 'Required directories should be array' );
	}

	/**
	 * Test conflict detection
	 * Requirements: 12.1, 12.2
	 */
	public function test_conflict_detection() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );

		$data = $response->get_data();
		$diagnostics = $data['data'];

		// Verify conflict detection is present
		$this->assertArrayHasKey( 'conflicts', $diagnostics, 'Should contain conflict detection' );
		$conflicts = $diagnostics['conflicts'];

		$this->assertArrayHasKey( 'potential_conflicts', $conflicts, 'Should check for potential conflicts' );
		$this->assertArrayHasKey( 'admin_menu_plugins', $conflicts, 'Should check for admin menu plugins' );
		$this->assertArrayHasKey( 'rest_api_conflicts', $conflicts, 'Should check for REST API conflicts' );

		$this->assertIsArray( $conflicts['potential_conflicts'], 'Potential conflicts should be array' );
		$this->assertIsArray( $conflicts['admin_menu_plugins'], 'Admin menu plugins should be array' );
		$this->assertIsArray( $conflicts['rest_api_conflicts'], 'REST API conflicts should be array' );
	}

	/**
	 * Test performance metrics collection
	 * Requirements: 12.1, 12.2
	 */
	public function test_performance_metrics_collection() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );

		$data = $response->get_data();
		$diagnostics = $data['data'];

		// Verify performance metrics are present
		$this->assertArrayHasKey( 'performance', $diagnostics, 'Should contain performance metrics' );
		$performance = $diagnostics['performance'];

		$this->assertArrayHasKey( 'memory_usage', $performance, 'Should include memory usage' );
		$this->assertArrayHasKey( 'execution_time', $performance, 'Should include execution time' );
		$this->assertArrayHasKey( 'database', $performance, 'Should include database metrics' );
		$this->assertArrayHasKey( 'cache', $performance, 'Should include cache metrics' );

		// Verify memory usage details
		$memory = $performance['memory_usage'];
		$this->assertArrayHasKey( 'current', $memory, 'Should include current memory usage' );
		$this->assertArrayHasKey( 'peak', $memory, 'Should include peak memory usage' );
		$this->assertArrayHasKey( 'limit', $memory, 'Should include memory limit' );
	}

	/**
	 * Test recommendations generation
	 * Requirements: 12.1, 12.2
	 */
	public function test_recommendations_generation() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );

		$data = $response->get_data();
		$diagnostics = $data['data'];

		// Verify recommendations are present
		$this->assertArrayHasKey( 'recommendations', $diagnostics, 'Should contain recommendations' );
		$recommendations = $diagnostics['recommendations'];

		$this->assertIsArray( $recommendations, 'Recommendations should be array' );

		// If there are recommendations, verify their structure
		if ( ! empty( $recommendations ) ) {
			$first_recommendation = $recommendations[0];
			$this->assertArrayHasKey( 'severity', $first_recommendation, 'Should include severity' );
			$this->assertArrayHasKey( 'category', $first_recommendation, 'Should include category' );
			$this->assertArrayHasKey( 'title', $first_recommendation, 'Should include title' );
			$this->assertArrayHasKey( 'description', $first_recommendation, 'Should include description' );
			$this->assertArrayHasKey( 'action', $first_recommendation, 'Should include action' );
		}
	}

	/**
	 * Test diagnostics metadata
	 * Requirements: 12.1, 12.2
	 */
	public function test_diagnostics_metadata() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );

		$data = $response->get_data();
		$diagnostics = $data['data'];

		// Verify metadata is present
		$this->assertArrayHasKey( '_metadata', $diagnostics, 'Should contain metadata' );
		$metadata = $diagnostics['_metadata'];

		$this->assertArrayHasKey( 'generated_at', $metadata, 'Should include generation timestamp' );
		$this->assertArrayHasKey( 'generated_timestamp', $metadata, 'Should include Unix timestamp' );
		$this->assertArrayHasKey( 'execution_time', $metadata, 'Should include execution time' );

		$this->assertNotEmpty( $metadata['generated_at'], 'Generation timestamp should not be empty' );
		$this->assertIsNumeric( $metadata['generated_timestamp'], 'Unix timestamp should be numeric' );
		$this->assertStringContainsString( 'ms', $metadata['execution_time'], 'Execution time should be in milliseconds' );
	}

	/**
	 * Test diagnostics with include parameter
	 * Requirements: 12.1, 12.2
	 */
	public function test_diagnostics_with_include_parameter() {
		wp_set_current_user( $this->admin_user );

		// Request only system and plugin sections
		$request = new WP_REST_Request( 'GET', $this->route );
		$request->set_param( 'include', 'system,plugin' );
		$response = rest_do_request( $request );

		$data = $response->get_data();
		$diagnostics = $data['data'];

		// Should include requested sections
		$this->assertArrayHasKey( 'system', $diagnostics, 'Should include system section' );
		$this->assertArrayHasKey( 'plugin', $diagnostics, 'Should include plugin section' );

		// Should not include other sections (except metadata)
		$this->assertArrayNotHasKey( 'settings', $diagnostics, 'Should not include settings section' );
		$this->assertArrayNotHasKey( 'filesystem', $diagnostics, 'Should not include filesystem section' );
		$this->assertArrayNotHasKey( 'conflicts', $diagnostics, 'Should not include conflicts section' );
	}

	/**
	 * Test diagnostics with invalid include parameter
	 * Requirements: 12.1, 12.2
	 */
	public function test_diagnostics_with_invalid_include_parameter() {
		wp_set_current_user( $this->admin_user );

		// Request with invalid section name
		$request = new WP_REST_Request( 'GET', $this->route );
		$request->set_param( 'include', 'invalid_section' );
		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status(), 'Invalid include parameter should return 400' );
	}

	/**
	 * Test health check endpoint
	 * Requirements: 12.1, 12.2
	 */
	public function test_health_check_endpoint() {
		wp_set_current_user( $this->admin_user );

		$health_route = '/mas-v2/v1/diagnostics/health';
		$request = new WP_REST_Request( 'GET', $health_route );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status(), 'Health check should return 200' );
		$data = $response->get_data();
		$this->assertTrue( $data['success'], 'Response should indicate success' );

		$health = $data['data'];
		$this->assertArrayHasKey( 'status', $health, 'Should include overall status' );
		$this->assertArrayHasKey( 'checks', $health, 'Should include individual checks' );

		$this->assertIsArray( $health['checks'], 'Checks should be array' );
		$this->assertNotEmpty( $health['checks'], 'Should have at least one check' );

		// Verify check structure
		foreach ( $health['checks'] as $check_name => $check ) {
			$this->assertArrayHasKey( 'status', $check, "Check {$check_name} should have status" );
			$this->assertArrayHasKey( 'message', $check, "Check {$check_name} should have message" );
			$this->assertContains( $check['status'], array( 'pass', 'fail', 'warning' ), "Check {$check_name} should have valid status" );
		}
	}

	/**
	 * Test health check status determination
	 * Requirements: 12.1, 12.2
	 */
	public function test_health_check_status_determination() {
		wp_set_current_user( $this->admin_user );

		$health_route = '/mas-v2/v1/diagnostics/health';
		$request = new WP_REST_Request( 'GET', $health_route );
		$response = rest_do_request( $request );

		$data = $response->get_data();
		$health = $data['data'];

		$this->assertContains( $health['status'], array( 'healthy', 'unhealthy', 'warning' ), 'Should have valid overall status' );
	}

	/**
	 * Test performance metrics endpoint
	 * Requirements: 12.1, 12.2
	 */
	public function test_performance_metrics_endpoint() {
		wp_set_current_user( $this->admin_user );

		$performance_route = '/mas-v2/v1/diagnostics/performance';
		$request = new WP_REST_Request( 'GET', $performance_route );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status(), 'Performance metrics should return 200' );
		$data = $response->get_data();
		$this->assertTrue( $data['success'], 'Response should indicate success' );

		$metrics = $data['data'];
		$this->assertArrayHasKey( 'memory_usage', $metrics, 'Should include memory usage' );
		$this->assertArrayHasKey( 'execution_time', $metrics, 'Should include execution time' );
		$this->assertArrayHasKey( 'database', $metrics, 'Should include database metrics' );
		$this->assertArrayHasKey( 'cache', $metrics, 'Should include cache metrics' );
	}

	/**
	 * Test diagnostics requires authentication
	 * Requirements: 12.3
	 */
	public function test_diagnostics_requires_authentication() {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status(), 'Unauthenticated request should return 403' );
		$data = $response->get_data();
		$this->assertEquals( 'rest_forbidden', $data['code'], 'Should return forbidden error code' );
	}

	/**
	 * Test diagnostics requires proper authorization
	 * Requirements: 12.3
	 */
	public function test_diagnostics_requires_proper_authorization() {
		// Editor doesn't have manage_options capability
		wp_set_current_user( $this->editor_user );

		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status(), 'Editor should not have permission' );
	}

	/**
	 * Test health check requires authentication
	 * Requirements: 12.3
	 */
	public function test_health_check_requires_authentication() {
		wp_set_current_user( 0 );

		$health_route = '/mas-v2/v1/diagnostics/health';
		$request = new WP_REST_Request( 'GET', $health_route );
		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status(), 'Unauthenticated health check should return 403' );
	}

	/**
	 * Test performance metrics requires authentication
	 * Requirements: 12.3
	 */
	public function test_performance_metrics_requires_authentication() {
		wp_set_current_user( 0 );

		$performance_route = '/mas-v2/v1/diagnostics/performance';
		$request = new WP_REST_Request( 'GET', $performance_route );
		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status(), 'Unauthenticated performance metrics should return 403' );
	}

	/**
	 * Test diagnostics error handling
	 * Requirements: 12.2
	 */
	public function test_diagnostics_error_handling() {
		wp_set_current_user( $this->admin_user );

		// Create a mock service that throws an exception
		$mock_service = $this->getMockBuilder( 'MAS_Diagnostics_Service' )
			->setMethods( array( 'get_diagnostics' ) )
			->getMock();

		$mock_service->method( 'get_diagnostics' )
			->willThrowException( new Exception( 'Test error' ) );

		$controller = new MAS_Diagnostics_Controller( $mock_service );
		$controller->register_routes();

		$request = new WP_REST_Request( 'GET', $this->route );
		$response = $controller->get_diagnostics( $request );

		$this->assertInstanceOf( 'WP_Error', $response, 'Should return WP_Error on exception' );
		$this->assertEquals( 'diagnostics_error', $response->get_error_code(), 'Should have correct error code' );
	}

	/**
	 * Test complete diagnostics workflow
	 * Requirements: 12.2
	 */
	public function test_complete_diagnostics_workflow() {
		wp_set_current_user( $this->admin_user );

		// Step 1: Get full diagnostics
		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'system', $data['data'] );
		$this->assertArrayHasKey( 'plugin', $data['data'] );
		$this->assertArrayHasKey( 'settings', $data['data'] );

		// Step 2: Get health check
		$health_route = '/mas-v2/v1/diagnostics/health';
		$request = new WP_REST_Request( 'GET', $health_route );
		$response = rest_do_request( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'status', $data['data'] );

		// Step 3: Get performance metrics
		$performance_route = '/mas-v2/v1/diagnostics/performance';
		$request = new WP_REST_Request( 'GET', $performance_route );
		$response = rest_do_request( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'memory_usage', $data['data'] );
	}

	/**
	 * Test diagnostics response format consistency
	 * Requirements: 12.2
	 */
	public function test_diagnostics_response_format_consistency() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );

		$data = $response->get_data();

		// Verify standard response format
		$this->assertArrayHasKey( 'success', $data, 'Should have success field' );
		$this->assertArrayHasKey( 'message', $data, 'Should have message field' );
		$this->assertArrayHasKey( 'data', $data, 'Should have data field' );

		$this->assertTrue( $data['success'], 'Success should be true' );
		$this->assertIsString( $data['message'], 'Message should be string' );
		$this->assertIsArray( $data['data'], 'Data should be array' );
	}

	/**
	 * Test settings integrity check with missing keys
	 * Requirements: 12.2
	 */
	public function test_settings_integrity_with_missing_keys() {
		wp_set_current_user( $this->admin_user );

		// Save incomplete settings
		update_option( 'mas_v2_settings', array(
			'menu_background' => '#1e1e2e',
			// Missing other required keys
		) );

		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );

		$data = $response->get_data();
		$settings = $data['data']['settings'];

		$this->assertNotEmpty( $settings['missing_keys'], 'Should detect missing keys' );
		$this->assertIsArray( $settings['missing_keys'], 'Missing keys should be array' );
	}

	/**
	 * Test diagnostics performance under load
	 * Requirements: 12.2
	 */
	public function test_diagnostics_performance() {
		wp_set_current_user( $this->admin_user );

		$start_time = microtime( true );

		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );

		$execution_time = microtime( true ) - $start_time;

		$this->assertEquals( 200, $response->get_status() );
		$this->assertLessThan( 2, $execution_time, 'Diagnostics should complete within 2 seconds' );
	}

	/**
	 * Test diagnostics caching behavior
	 * Requirements: 12.2
	 */
	public function test_diagnostics_no_caching() {
		wp_set_current_user( $this->admin_user );

		// First request
		$request1 = new WP_REST_Request( 'GET', $this->route );
		$response1 = rest_do_request( $request1 );
		$data1 = $response1->get_data();
		$timestamp1 = $data1['data']['_metadata']['generated_timestamp'];

		// Wait a moment
		sleep( 1 );

		// Second request
		$request2 = new WP_REST_Request( 'GET', $this->route );
		$response2 = rest_do_request( $request2 );
		$data2 = $response2->get_data();
		$timestamp2 = $data2['data']['_metadata']['generated_timestamp'];

		// Timestamps should be different (not cached)
		$this->assertNotEquals( $timestamp1, $timestamp2, 'Diagnostics should not be cached' );
	}

	/**
	 * Test conflict detection with active plugins
	 * Requirements: 12.2
	 */
	public function test_conflict_detection_with_plugins() {
		wp_set_current_user( $this->admin_user );

		// Simulate active plugins
		update_option( 'active_plugins', array(
			'admin-menu-editor/admin-menu-editor.php',
			'some-other-plugin/plugin.php',
		) );

		$request = new WP_REST_Request( 'GET', $this->route );
		$response = rest_do_request( $request );

		$data = $response->get_data();
		$conflicts = $data['data']['conflicts'];

		$this->assertIsArray( $conflicts['potential_conflicts'], 'Should detect potential conflicts' );
		$this->assertIsArray( $conflicts['admin_menu_plugins'], 'Should detect admin menu plugins' );

		// Clean up
		delete_option( 'active_plugins' );
	}
}

