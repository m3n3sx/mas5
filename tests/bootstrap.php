<?php
/**
 * PHPUnit bootstrap file for Modern Admin Styler V2 tests.
 */

// Composer autoloader
if ( file_exists( dirname( dirname( __FILE__ ) ) . '/vendor/autoload.php' ) ) {
	require_once dirname( dirname( __FILE__ ) ) . '/vendor/autoload.php';
}

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL;
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	// Load the main plugin file
	require dirname( dirname( __FILE__ ) ) . '/modern-admin-styler-v2.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

/**
 * Set up test fixtures and data.
 */
function _setup_test_fixtures() {
	// Create test user with admin capabilities
	$admin_user = wp_create_user( 'testadmin', 'password', 'admin@test.com' );
	$user = new WP_User( $admin_user );
	$user->set_role( 'administrator' );
	
	// Create test user without admin capabilities
	$editor_user = wp_create_user( 'testeditor', 'password', 'editor@test.com' );
	$user = new WP_User( $editor_user );
	$user->set_role( 'editor' );
	
	// Set up default plugin options for testing
	update_option( 'mas_v2_settings', array(
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
	) );
	
	// Set up feature flags for testing
	update_option( 'mas_v2_feature_flags', array(
		'rest_api_enabled' => true,
		'dual_mode_enabled' => true,
		'deprecation_warnings' => true
	) );
}
tests_add_filter( 'wp_loaded', '_setup_test_fixtures' );

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";

// Include test helper classes
require_once dirname( __FILE__ ) . '/helpers/class-mas-test-case.php';
require_once dirname( __FILE__ ) . '/helpers/class-mas-rest-test-case.php';
require_once dirname( __FILE__ ) . '/helpers/class-mas-test-fixtures.php';
