<?php
/**
 * Integration tests for Phase 2 Diagnostics and System Health features.
 *
 * Tests Requirements: 3.1, 3.2, 3.3, 3.4
 */

class TestPhase2Diagnostics extends MAS_REST_Test_Case {
	
	/**
	 * System health service instance.
	 *
	 * @var MAS_System_Health_Service
	 */
	private $system_health_service;
	
	/**
	 * Set up test case.
	 */
	public function setUp(): void {
		parent::setUp();
		
		$this->system_health_service = new MAS_System_Health_Service();
	}
	
	/**
	 * Test complete health check workflow.
	 * Requirement: 3.1
	 */
	public function test_complete_health_check_workflow() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Get system health status
		$response = $this->perform_rest_request(
			'GET',
			'/system/health',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$health_data = $response->get_data();
		$this->assertArrayHasKey( 'data', $health_data );
		$this->assertArrayHasKey( 'status', $health_data['data'] );
		$this->assertArrayHasKey( 'checks', $health_data['data'] );
		$this->assertArrayHasKey( 'recommendations', $health_data['data'] );
		
		// Verify status is one of: healthy, warning, critical
		$status = $health_data['data']['status'];
		$this->assertContains( $status, array( 'healthy', 'warning', 'critical' ) );
		
		// Verify all required checks are present
		$checks = $health_data['data']['checks'];
		$required_checks = array(
			'php_version',
			'wordpress_version',
			'settings_integrity',
			'file_permissions',
			'cache_status',
			'conflicts'
		);
		
		foreach ( $required_checks as $check_name ) {
			$this->assertArrayHasKey( $check_name, $checks, "Health check should include {$check_name}" );
			$this->assertArrayHasKey( 'status', $checks[ $check_name ] );
			$this->assertArrayHasKey( 'message', $checks[ $check_name ] );
		}
	}
	
	/**
	 * Test PHP version check.
	 * Requirement: 3.2
	 */
	public function test_php_version_check() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		$response = $this->perform_rest_request(
			'GET',
			'/system/health',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$health_data = $response->get_data();
		$php_check = $health_data['data']['checks']['php_version'];
		
		$this->assertArrayHasKey( 'status', $php_check );
		$this->assertArrayHasKey( 'current_version', $php_check );
		$this->assertArrayHasKey( 'required_version', $php_check );
		
		// PHP version should be valid
		$this->assertEquals( PHP_VERSION, $php_check['current_version'] );
		
		// Status should be pass if PHP version is adequate
		if ( version_compare( PHP_VERSION, '7.4', '>=' ) ) {
			$this->assertEquals( 'pass', $php_check['status'] );
		}
	}
	
	/**
	 * Test WordPress version check.
	 * Requirement: 3.2
	 */
	public function test_wordpress_version_check() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		$response = $this->perform_rest_request(
			'GET',
			'/system/health',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$health_data = $response->get_data();
		$wp_check = $health_data['data']['checks']['wordpress_version'];
		
		$this->assertArrayHasKey( 'status', $wp_check );
		$this->assertArrayHasKey( 'current_version', $wp_check );
		$this->assertArrayHasKey( 'required_version', $wp_check );
		
		global $wp_version;
		$this->assertEquals( $wp_version, $wp_check['current_version'] );
	}
	
	/**
	 * Test settings integrity check.
	 * Requirement: 3.3
	 */
	public function test_settings_integrity_check() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// First, ensure settings are valid
		update_option( 'mas_v2_settings', $this->default_settings );
		
		$response = $this->perform_rest_request(
			'GET',
			'/system/health',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$health_data = $response->get_data();
		$integrity_check = $health_data['data']['checks']['settings_integrity'];
		
		$this->assertEquals( 'pass', $integrity_check['status'] );
		
		// Now corrupt settings
		update_option( 'mas_v2_settings', array(
			'menu_background' => 'invalid-color',
			'menu_width' => 'invalid-width'
		) );
		
		$response = $this->perform_rest_request(
			'GET',
			'/system/health',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$health_data = $response->get_data();
		$integrity_check = $health_data['data']['checks']['settings_integrity'];
		
		$this->assertContains( $integrity_check['status'], array( 'warning', 'fail' ) );
		$this->assertArrayHasKey( 'errors', $integrity_check );
		$this->assertNotEmpty( $integrity_check['errors'] );
	}
	
	/**
	 * Test file permissions check.
	 * Requirement: 3.4
	 */
	public function test_file_permissions_check() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		$response = $this->perform_rest_request(
			'GET',
			'/system/health',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$health_data = $response->get_data();
		$permissions_check = $health_data['data']['checks']['file_permissions'];
		
		$this->assertArrayHasKey( 'status', $permissions_check );
		$this->assertArrayHasKey( 'writable_paths', $permissions_check );
		
		// Verify critical paths are checked
		$writable_paths = $permissions_check['writable_paths'];
		$this->assertArrayHasKey( 'uploads', $writable_paths );
		$this->assertArrayHasKey( 'cache', $writable_paths );
	}
	
	/**
	 * Test cache status check.
	 * Requirement: 3.5
	 */
	public function test_cache_status_check() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		$response = $this->perform_rest_request(
			'GET',
			'/system/health',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$health_data = $response->get_data();
		$cache_check = $health_data['data']['checks']['cache_status'];
		
		$this->assertArrayHasKey( 'status', $cache_check );
		$this->assertArrayHasKey( 'enabled', $cache_check );
		$this->assertArrayHasKey( 'type', $cache_check );
		
		// Cache should be enabled in test environment
		$this->assertTrue( $cache_check['enabled'] );
	}
	
	/**
	 * Test conflict detection.
	 * Requirement: 3.4
	 */
	public function test_conflict_detection() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		$response = $this->perform_rest_request(
			'GET',
			'/system/conflicts',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$conflicts_data = $response->get_data();
		$this->assertArrayHasKey( 'data', $conflicts_data );
		$this->assertArrayHasKey( 'conflicts', $conflicts_data['data'] );
		$this->assertArrayHasKey( 'plugin_conflicts', $conflicts_data['data'] );
		$this->assertArrayHasKey( 'theme_conflicts', $conflicts_data['data'] );
		$this->assertArrayHasKey( 'javascript_conflicts', $conflicts_data['data'] );
		
		$conflicts = $conflicts_data['data']['conflicts'];
		$this->assertIsArray( $conflicts );
		
		// Each conflict should have required fields
		foreach ( $conflicts as $conflict ) {
			$this->assertArrayHasKey( 'type', $conflict );
			$this->assertArrayHasKey( 'severity', $conflict );
			$this->assertArrayHasKey( 'description', $conflict );
			$this->assertArrayHasKey( 'recommendation', $conflict );
		}
	}
	
	/**
	 * Test performance metrics collection.
	 * Requirement: 3.5
	 */
	public function test_performance_metrics_collection() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		$response = $this->perform_rest_request(
			'GET',
			'/system/performance',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$perf_data = $response->get_data();
		$this->assertArrayHasKey( 'data', $perf_data );
		$this->assertArrayHasKey( 'metrics', $perf_data['data'] );
		
		$metrics = $perf_data['data']['metrics'];
		
		// Verify required metrics
		$required_metrics = array(
			'memory_usage',
			'memory_limit',
			'memory_percentage',
			'cache_hits',
			'cache_misses',
			'cache_hit_rate',
			'database_queries',
			'database_time',
			'php_version',
			'wordpress_version'
		);
		
		foreach ( $required_metrics as $metric ) {
			$this->assertArrayHasKey( $metric, $metrics, "Performance metrics should include {$metric}" );
		}
		
		// Verify metric values are reasonable
		$this->assertGreaterThan( 0, $metrics['memory_usage'] );
		$this->assertGreaterThan( 0, $metrics['memory_limit'] );
		$this->assertGreaterThanOrEqual( 0, $metrics['memory_percentage'] );
		$this->assertLessThanOrEqual( 100, $metrics['memory_percentage'] );
		$this->assertGreaterThanOrEqual( 0, $metrics['cache_hit_rate'] );
		$this->assertLessThanOrEqual( 100, $metrics['cache_hit_rate'] );
	}
	
	/**
	 * Test system info endpoint.
	 * Requirement: 3.2
	 */
	public function test_system_info_endpoint() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		$response = $this->perform_rest_request(
			'GET',
			'/system/info',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$info_data = $response->get_data();
		$this->assertArrayHasKey( 'data', $info_data );
		$this->assertArrayHasKey( 'system', $info_data['data'] );
		
		$system = $info_data['data']['system'];
		
		// Verify system information fields
		$required_fields = array(
			'php_version',
			'wordpress_version',
			'plugin_version',
			'server_software',
			'mysql_version',
			'max_execution_time',
			'memory_limit',
			'upload_max_filesize',
			'post_max_size',
			'active_plugins',
			'active_theme'
		);
		
		foreach ( $required_fields as $field ) {
			$this->assertArrayHasKey( $field, $system, "System info should include {$field}" );
		}
	}
	
	/**
	 * Test cache clearing functionality.
	 * Requirements: 3.6, 3.7
	 */
	public function test_cache_clearing_functionality() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Set some cache data
		wp_cache_set( 'test_key_1', 'test_value_1', 'mas_v2' );
		wp_cache_set( 'test_key_2', 'test_value_2', 'mas_v2' );
		wp_cache_set( 'generated_css', 'body { color: red; }', 'mas_v2' );
		
		// Verify cache is set
		$this->assertEquals( 'test_value_1', wp_cache_get( 'test_key_1', 'mas_v2' ) );
		$this->assertEquals( 'test_value_2', wp_cache_get( 'test_key_2', 'mas_v2' ) );
		
		// Clear cache
		$response = $this->perform_rest_request(
			'DELETE',
			'/system/cache',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$clear_data = $response->get_data();
		$this->assertArrayHasKey( 'data', $clear_data );
		$this->assertArrayHasKey( 'cleared', $clear_data['data'] );
		$this->assertTrue( $clear_data['data']['cleared'] );
		
		// Verify cache was cleared
		$this->assertFalse( wp_cache_get( 'test_key_1', 'mas_v2' ) );
		$this->assertFalse( wp_cache_get( 'test_key_2', 'mas_v2' ) );
		$this->assertFalse( wp_cache_get( 'generated_css', 'mas_v2' ) );
	}
	
	/**
	 * Test recommendations generation.
	 * Requirement: 3.5
	 */
	public function test_recommendations_generation() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		$response = $this->perform_rest_request(
			'GET',
			'/system/health',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$health_data = $response->get_data();
		$recommendations = $health_data['data']['recommendations'];
		
		$this->assertIsArray( $recommendations );
		
		// Each recommendation should have required fields
		foreach ( $recommendations as $recommendation ) {
			$this->assertArrayHasKey( 'type', $recommendation );
			$this->assertArrayHasKey( 'priority', $recommendation );
			$this->assertArrayHasKey( 'message', $recommendation );
			$this->assertArrayHasKey( 'action', $recommendation );
			
			// Priority should be: low, medium, high, critical
			$this->assertContains( $recommendation['priority'], array( 'low', 'medium', 'high', 'critical' ) );
		}
	}
	
	/**
	 * Test diagnostics with performance issues.
	 * Requirement: 3.5
	 */
	public function test_diagnostics_with_performance_issues() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Simulate high memory usage by creating large data
		$large_data = str_repeat( 'x', 1024 * 1024 ); // 1MB string
		wp_cache_set( 'large_data', $large_data, 'mas_v2' );
		
		$response = $this->perform_rest_request(
			'GET',
			'/system/performance',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$perf_data = $response->get_data();
		$metrics = $perf_data['data']['metrics'];
		
		// Memory usage should be tracked
		$this->assertGreaterThan( 0, $metrics['memory_usage'] );
		
		// Check if recommendations are provided for high memory usage
		if ( $metrics['memory_percentage'] > 80 ) {
			$response = $this->perform_rest_request(
				'GET',
				'/system/health',
				array(),
				array( 'X-WP-Nonce' => $nonce )
			);
			
			$health_data = $response->get_data();
			$recommendations = $health_data['data']['recommendations'];
			
			// Should have memory-related recommendation
			$has_memory_recommendation = false;
			foreach ( $recommendations as $rec ) {
				if ( stripos( $rec['message'], 'memory' ) !== false ) {
					$has_memory_recommendation = true;
					break;
				}
			}
			
			$this->assertTrue( $has_memory_recommendation, 'Should recommend memory optimization' );
		}
		
		// Clean up
		wp_cache_delete( 'large_data', 'mas_v2' );
	}
	
	/**
	 * Test plugin conflict detection.
	 * Requirement: 3.4
	 */
	public function test_plugin_conflict_detection() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Simulate conflicting plugin by setting option
		update_option( 'active_plugins', array(
			'known-conflicting-plugin/plugin.php',
			'modern-admin-styler-v2/modern-admin-styler-v2.php'
		) );
		
		$response = $this->perform_rest_request(
			'GET',
			'/system/conflicts',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		
		$conflicts_data = $response->get_data();
		$plugin_conflicts = $conflicts_data['data']['plugin_conflicts'];
		
		$this->assertIsArray( $plugin_conflicts );
		
		// Each plugin conflict should have details
		foreach ( $plugin_conflicts as $conflict ) {
			$this->assertArrayHasKey( 'plugin', $conflict );
			$this->assertArrayHasKey( 'severity', $conflict );
			$this->assertArrayHasKey( 'description', $conflict );
			$this->assertArrayHasKey( 'recommendation', $conflict );
		}
		
		// Clean up
		delete_option( 'active_plugins' );
	}
	
	/**
	 * Test diagnostics performance under load.
	 * Requirement: 3.1, 3.2
	 */
	public function test_diagnostics_performance() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Measure health check performance
		$start_time = microtime( true );
		
		$response = $this->perform_rest_request(
			'GET',
			'/system/health',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$health_check_time = microtime( true ) - $start_time;
		
		$this->assertRestResponseSuccess( $response );
		$this->assertLessThan( 0.5, $health_check_time, 'Health check should complete in under 0.5 seconds' );
		
		// Measure performance metrics collection
		$start_time = microtime( true );
		
		$response = $this->perform_rest_request(
			'GET',
			'/system/performance',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$perf_check_time = microtime( true ) - $start_time;
		
		$this->assertRestResponseSuccess( $response );
		$this->assertLessThan( 0.3, $perf_check_time, 'Performance metrics should be collected in under 0.3 seconds' );
		
		// Measure conflict detection
		$start_time = microtime( true );
		
		$response = $this->perform_rest_request(
			'GET',
			'/system/conflicts',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$conflict_check_time = microtime( true ) - $start_time;
		
		$this->assertRestResponseSuccess( $response );
		$this->assertLessThan( 0.4, $conflict_check_time, 'Conflict detection should complete in under 0.4 seconds' );
	}
	
	/**
	 * Test complete diagnostics workflow integration.
	 * Requirements: 3.1, 3.2, 3.3, 3.4, 3.5
	 */
	public function test_complete_diagnostics_workflow() {
		$nonce = $this->authenticate_user( $this->admin_user_id );
		
		// Step 1: Get system health
		$response = $this->perform_rest_request(
			'GET',
			'/system/health',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		$health_data = $response->get_data();
		$status = $health_data['data']['status'];
		
		// Step 2: If issues found, get detailed info
		if ( $status !== 'healthy' ) {
			// Get system info
			$response = $this->perform_rest_request(
				'GET',
				'/system/info',
				array(),
				array( 'X-WP-Nonce' => $nonce )
			);
			
			$this->assertRestResponseSuccess( $response );
			
			// Get performance metrics
			$response = $this->perform_rest_request(
				'GET',
				'/system/performance',
				array(),
				array( 'X-WP-Nonce' => $nonce )
			);
			
			$this->assertRestResponseSuccess( $response );
			
			// Check for conflicts
			$response = $this->perform_rest_request(
				'GET',
				'/system/conflicts',
				array(),
				array( 'X-WP-Nonce' => $nonce )
			);
			
			$this->assertRestResponseSuccess( $response );
		}
		
		// Step 3: Clear cache if recommended
		$recommendations = $health_data['data']['recommendations'];
		$should_clear_cache = false;
		
		foreach ( $recommendations as $rec ) {
			if ( stripos( $rec['action'], 'clear_cache' ) !== false ) {
				$should_clear_cache = true;
				break;
			}
		}
		
		if ( $should_clear_cache ) {
			$response = $this->perform_rest_request(
				'DELETE',
				'/system/cache',
				array(),
				array( 'X-WP-Nonce' => $nonce )
			);
			
			$this->assertRestResponseSuccess( $response );
		}
		
		// Step 4: Verify health improved after actions
		$response = $this->perform_rest_request(
			'GET',
			'/system/health',
			array(),
			array( 'X-WP-Nonce' => $nonce )
		);
		
		$this->assertRestResponseSuccess( $response );
		$final_health = $response->get_data();
		
		// Health status should be valid
		$this->assertContains( $final_health['data']['status'], array( 'healthy', 'warning', 'critical' ) );
	}
}
