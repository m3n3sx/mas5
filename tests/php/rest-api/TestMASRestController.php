<?php
/**
 * Test MAS_REST_Controller base class
 *
 * @package Modern_Admin_Styler_V2
 * @subpackage Tests
 */

/**
 * Test case for MAS_REST_Controller
 */
class TestMASRestController extends WP_UnitTestCase {

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
	 * Test controller instance
	 *
	 * @var MAS_REST_Controller
	 */
	protected $controller;

	/**
	 * Set up test environment
	 */
	public function setUp() {
		parent::setUp();

		// Create test users with different roles
		$this->admin_user = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->editor_user = $this->factory->user->create( array( 'role' => 'editor' ) );
		$this->subscriber_user = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		// Load the REST API classes
		require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/includes/api/class-mas-rest-controller.php';

		// Create a concrete implementation for testing
		$this->controller = new class extends MAS_REST_Controller {
			public function register_routes() {
				// Not needed for base tests
			}
		};
	}

	/**
	 * Tear down test environment
	 */
	public function tearDown() {
		parent::tearDown();
		wp_set_current_user( 0 );
	}

	/**
	 * Test permission check with administrator user
	 */
	public function test_check_permission_with_admin_user() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'GET', '/mas-v2/v1/test' );
		$result = $this->controller->check_permission( $request );

		$this->assertTrue( $result, 'Administrator should have permission' );
	}

	/**
	 * Test permission check with editor user (no manage_options capability)
	 */
	public function test_check_permission_with_editor_user() {
		wp_set_current_user( $this->editor_user );

		$request = new WP_REST_Request( 'GET', '/mas-v2/v1/test' );
		$result = $this->controller->check_permission( $request );

		$this->assertInstanceOf( 'WP_Error', $result, 'Editor should not have permission' );
		$this->assertEquals( 'rest_forbidden', $result->get_error_code() );
	}

	/**
	 * Test permission check with subscriber user
	 */
	public function test_check_permission_with_subscriber_user() {
		wp_set_current_user( $this->subscriber_user );

		$request = new WP_REST_Request( 'GET', '/mas-v2/v1/test' );
		$result = $this->controller->check_permission( $request );

		$this->assertInstanceOf( 'WP_Error', $result, 'Subscriber should not have permission' );
		$this->assertEquals( 'rest_forbidden', $result->get_error_code() );
	}

	/**
	 * Test permission check with unauthenticated user
	 */
	public function test_check_permission_with_unauthenticated_user() {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', '/mas-v2/v1/test' );
		$result = $this->controller->check_permission( $request );

		$this->assertInstanceOf( 'WP_Error', $result, 'Unauthenticated user should not have permission' );
		$this->assertEquals( 'rest_forbidden', $result->get_error_code() );
	}

	/**
	 * Test error response formatting
	 */
	public function test_error_response_format() {
		$message = 'Test error message';
		$code = 'test_error';
		$status = 400;

		$response = $this->controller->error_response( $message, $code, $status );

		$this->assertInstanceOf( 'WP_Error', $response, 'Should return WP_Error instance' );
		$this->assertEquals( $code, $response->get_error_code(), 'Error code should match' );
		$this->assertEquals( $message, $response->get_error_message(), 'Error message should match' );

		$data = $response->get_error_data();
		$this->assertEquals( $status, $data['status'], 'Status code should match' );
	}

	/**
	 * Test error response with default parameters
	 */
	public function test_error_response_with_defaults() {
		$message = 'Default error';

		$response = $this->controller->error_response( $message );

		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'error', $response->get_error_code(), 'Should use default error code' );
		$this->assertEquals( $message, $response->get_error_message() );

		$data = $response->get_error_data();
		$this->assertEquals( 400, $data['status'], 'Should use default 400 status' );
	}

	/**
	 * Test success response formatting
	 */
	public function test_success_response_format() {
		$data = array(
			'setting1' => 'value1',
			'setting2' => 'value2',
		);
		$message = 'Operation successful';
		$status = 200;

		$response = $this->controller->success_response( $data, $message, $status );

		$this->assertInstanceOf( 'WP_REST_Response', $response, 'Should return WP_REST_Response instance' );
		$this->assertEquals( $status, $response->get_status(), 'Status code should match' );

		$response_data = $response->get_data();
		$this->assertTrue( $response_data['success'], 'Success flag should be true' );
		$this->assertEquals( $message, $response_data['message'], 'Message should match' );
		$this->assertEquals( $data, $response_data['data'], 'Data should match' );
	}

	/**
	 * Test success response with default parameters
	 */
	public function test_success_response_with_defaults() {
		$data = array( 'test' => 'data' );

		$response = $this->controller->success_response( $data );

		$this->assertInstanceOf( 'WP_REST_Response', $response );
		$this->assertEquals( 200, $response->get_status(), 'Should use default 200 status' );

		$response_data = $response->get_data();
		$this->assertTrue( $response_data['success'] );
		$this->assertEquals( '', $response_data['message'], 'Should have empty message by default' );
		$this->assertEquals( $data, $response_data['data'] );
	}

	/**
	 * Test success response with empty data
	 */
	public function test_success_response_with_empty_data() {
		$response = $this->controller->success_response( array() );

		$this->assertInstanceOf( 'WP_REST_Response', $response );
		$response_data = $response->get_data();
		$this->assertTrue( $response_data['success'] );
		$this->assertEmpty( $response_data['data'] );
	}

	/**
	 * Test namespace property
	 */
	public function test_namespace_property() {
		$reflection = new ReflectionClass( $this->controller );
		$property = $reflection->getProperty( 'namespace' );
		$property->setAccessible( true );

		$namespace = $property->getValue( $this->controller );
		$this->assertEquals( 'mas-v2/v1', $namespace, 'Namespace should be mas-v2/v1' );
	}

	/**
	 * Test error response with various HTTP status codes
	 */
	public function test_error_response_status_codes() {
		$test_cases = array(
			array( 'message' => 'Bad Request', 'code' => 'bad_request', 'status' => 400 ),
			array( 'message' => 'Unauthorized', 'code' => 'unauthorized', 'status' => 401 ),
			array( 'message' => 'Forbidden', 'code' => 'forbidden', 'status' => 403 ),
			array( 'message' => 'Not Found', 'code' => 'not_found', 'status' => 404 ),
			array( 'message' => 'Internal Server Error', 'code' => 'server_error', 'status' => 500 ),
		);

		foreach ( $test_cases as $test_case ) {
			$response = $this->controller->error_response(
				$test_case['message'],
				$test_case['code'],
				$test_case['status']
			);

			$this->assertInstanceOf( 'WP_Error', $response );
			$this->assertEquals( $test_case['code'], $response->get_error_code() );
			$this->assertEquals( $test_case['message'], $response->get_error_message() );

			$data = $response->get_error_data();
			$this->assertEquals( $test_case['status'], $data['status'] );
		}
	}

	/**
	 * Test success response with various HTTP status codes
	 */
	public function test_success_response_status_codes() {
		$test_cases = array(
			array( 'status' => 200, 'description' => 'OK' ),
			array( 'status' => 201, 'description' => 'Created' ),
			array( 'status' => 204, 'description' => 'No Content' ),
		);

		foreach ( $test_cases as $test_case ) {
			$response = $this->controller->success_response(
				array( 'test' => 'data' ),
				$test_case['description'],
				$test_case['status']
			);

			$this->assertInstanceOf( 'WP_REST_Response', $response );
			$this->assertEquals( $test_case['status'], $response->get_status() );
		}
	}

	/**
	 * Test permission check returns proper error message
	 */
	public function test_permission_error_message() {
		wp_set_current_user( $this->subscriber_user );

		$request = new WP_REST_Request( 'GET', '/mas-v2/v1/test' );
		$result = $this->controller->check_permission( $request );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertNotEmpty( $result->get_error_message(), 'Error should have a message' );
		$this->assertStringContainsString( 'permission', strtolower( $result->get_error_message() ) );
	}

	/**
	 * Test that permission check works with different request methods
	 */
	public function test_permission_check_with_different_methods() {
		wp_set_current_user( $this->admin_user );

		$methods = array( 'GET', 'POST', 'PUT', 'DELETE', 'PATCH' );

		foreach ( $methods as $method ) {
			$request = new WP_REST_Request( $method, '/mas-v2/v1/test' );
			$result = $this->controller->check_permission( $request );

			$this->assertTrue( $result, "Admin should have permission for {$method} requests" );
		}
	}
}
