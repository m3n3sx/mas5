<?php
/**
 * Base test case class for MAS tests.
 */

class MAS_Test_Case extends WP_UnitTestCase {
	
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
		
		parent::tearDown();
	}
	
	/**
	 * Assert that two arrays are equal, ignoring order.
	 *
	 * @param array $expected Expected array.
	 * @param array $actual Actual array.
	 * @param string $message Optional message.
	 */
	protected function assertArraysEqualIgnoreOrder( $expected, $actual, $message = '' ) {
		ksort( $expected );
		ksort( $actual );
		$this->assertEquals( $expected, $actual, $message );
	}
	
	/**
	 * Assert that a color is valid hex format.
	 *
	 * @param string $color Color to validate.
	 * @param string $message Optional message.
	 */
	protected function assertValidHexColor( $color, $message = '' ) {
		$this->assertMatchesRegularExpression( '/^#[a-f0-9]{6}$/i', $color, $message );
	}
	
	/**
	 * Assert that CSS is valid and contains expected properties.
	 *
	 * @param string $css CSS to validate.
	 * @param array $expected_properties Expected CSS properties.
	 * @param string $message Optional message.
	 */
	protected function assertValidCSS( $css, $expected_properties = array(), $message = '' ) {
		$this->assertNotEmpty( $css, 'CSS should not be empty' );
		
		foreach ( $expected_properties as $property ) {
			$this->assertStringContainsString( $property, $css, "CSS should contain property: {$property}" );
		}
	}
	
	/**
	 * Create a test backup.
	 *
	 * @param array $settings Optional settings to backup.
	 * @return array Backup data.
	 */
	protected function create_test_backup( $settings = null ) {
		if ( null === $settings ) {
			$settings = $this->default_settings;
		}
		
		$backup = array(
			'id' => time(),
			'timestamp' => time(),
			'date' => current_time( 'mysql' ),
			'type' => 'manual',
			'settings' => $settings,
			'metadata' => array(
				'plugin_version' => '2.2.0',
				'wordpress_version' => get_bloginfo( 'version' ),
				'user_id' => get_current_user_id(),
				'note' => 'Test backup'
			)
		);
		
		$backups = get_option( 'mas_v2_backups', array() );
		$backups[] = $backup;
		update_option( 'mas_v2_backups', $backups );
		
		return $backup;
	}
	
	/**
	 * Create a test theme.
	 *
	 * @param string $id Theme ID.
	 * @param array $settings Theme settings.
	 * @return array Theme data.
	 */
	protected function create_test_theme( $id = 'test-theme', $settings = null ) {
		if ( null === $settings ) {
			$settings = array(
				'menu_background' => '#ff0000',
				'menu_text_color' => '#ffffff'
			);
		}
		
		$theme = array(
			'id' => $id,
			'name' => 'Test Theme',
			'type' => 'custom',
			'readonly' => false,
			'settings' => $settings,
			'metadata' => array(
				'author' => 'Test User',
				'version' => '1.0',
				'created' => current_time( 'mysql' )
			)
		);
		
		$themes = get_option( 'mas_v2_themes', array() );
		$themes[ $id ] = $theme;
		update_option( 'mas_v2_themes', $themes );
		
		return $theme;
	}
}