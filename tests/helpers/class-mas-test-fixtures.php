<?php
/**
 * Test fixtures and data for MAS tests.
 */

class MAS_Test_Fixtures {
	
	/**
	 * Get default plugin settings.
	 *
	 * @return array Default settings.
	 */
	public static function get_default_settings() {
		return array(
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
			'menu_margin' => array(
				'top' => '20px',
				'left' => '20px',
				'bottom' => '20px'
			),
			'admin_bar_background' => '#1e1e2e',
			'admin_bar_text_color' => '#ffffff',
			'admin_bar_floating' => false,
			'glassmorphism_enabled' => true,
			'glassmorphism_blur' => '10px',
			'shadow_effects_enabled' => true,
			'shadow_intensity' => 'medium',
			'animations_enabled' => true,
			'animation_speed' => 'normal',
			'current_theme' => 'default',
			'custom_themes' => array(),
			'performance_mode' => false,
			'debug_mode' => false
		);
	}
	
	/**
	 * Get test settings with invalid data.
	 *
	 * @return array Invalid settings.
	 */
	public static function get_invalid_settings() {
		return array(
			'menu_background' => 'invalid-color',
			'menu_text_color' => '#gggggg',
			'menu_width' => 'invalid-width',
			'menu_detached' => 'not-boolean',
			'glassmorphism_blur' => 'invalid-blur'
		);
	}
	
	/**
	 * Get predefined themes.
	 *
	 * @return array Predefined themes.
	 */
	public static function get_predefined_themes() {
		return array(
			'default' => array(
				'id' => 'default',
				'name' => 'Default',
				'type' => 'predefined',
				'readonly' => true,
				'settings' => array(
					'menu_background' => '#1e1e2e',
					'menu_text_color' => '#ffffff',
					'menu_hover_background' => '#2d2d44',
					'menu_hover_text_color' => '#ffffff'
				),
				'metadata' => array(
					'author' => 'MAS Team',
					'version' => '1.0',
					'created' => '2025-01-01'
				)
			),
			'dark-blue' => array(
				'id' => 'dark-blue',
				'name' => 'Dark Blue',
				'type' => 'predefined',
				'readonly' => true,
				'settings' => array(
					'menu_background' => '#1a237e',
					'menu_text_color' => '#ffffff',
					'menu_hover_background' => '#283593',
					'menu_hover_text_color' => '#ffffff'
				),
				'metadata' => array(
					'author' => 'MAS Team',
					'version' => '1.0',
					'created' => '2025-01-01'
				)
			),
			'forest-green' => array(
				'id' => 'forest-green',
				'name' => 'Forest Green',
				'type' => 'predefined',
				'readonly' => true,
				'settings' => array(
					'menu_background' => '#1b5e20',
					'menu_text_color' => '#ffffff',
					'menu_hover_background' => '#2e7d32',
					'menu_hover_text_color' => '#ffffff'
				),
				'metadata' => array(
					'author' => 'MAS Team',
					'version' => '1.0',
					'created' => '2025-01-01'
				)
			)
		);
	}
	
	/**
	 * Get test backup data.
	 *
	 * @return array Test backup.
	 */
	public static function get_test_backup() {
		return array(
			'id' => 1234567890,
			'timestamp' => 1234567890,
			'date' => '2025-01-10 15:30:00',
			'type' => 'manual',
			'settings' => self::get_default_settings(),
			'metadata' => array(
				'plugin_version' => '2.2.0',
				'wordpress_version' => '6.8',
				'user_id' => 1,
				'note' => 'Test backup'
			)
		);
	}
	
	/**
	 * Get test export data.
	 *
	 * @return array Test export data.
	 */
	public static function get_test_export_data() {
		return array(
			'version' => '2.2.0',
			'export_date' => current_time( 'mysql' ),
			'settings' => self::get_default_settings(),
			'themes' => array(
				'custom-theme' => array(
					'id' => 'custom-theme',
					'name' => 'Custom Theme',
					'type' => 'custom',
					'settings' => array(
						'menu_background' => '#ff5722',
						'menu_text_color' => '#ffffff'
					)
				)
			),
			'metadata' => array(
				'site_url' => home_url(),
				'wordpress_version' => get_bloginfo( 'version' ),
				'plugin_version' => '2.2.0'
			)
		);
	}
	
	/**
	 * Get invalid export data.
	 *
	 * @return array Invalid export data.
	 */
	public static function get_invalid_export_data() {
		return array(
			'version' => '1.0.0', // Old version
			'settings' => array(
				'invalid_field' => 'invalid_value'
			)
		);
	}
	
	/**
	 * Get test CSS output.
	 *
	 * @return string Test CSS.
	 */
	public static function get_test_css() {
		return '
			:root {
				--mas-menu-background: #1e1e2e;
				--mas-menu-text-color: #ffffff;
				--mas-menu-hover-background: #2d2d44;
				--mas-menu-hover-text-color: #ffffff;
			}
			
			#adminmenu {
				background-color: var(--mas-menu-background);
				color: var(--mas-menu-text-color);
			}
			
			#adminmenu a:hover {
				background-color: var(--mas-menu-hover-background);
				color: var(--mas-menu-hover-text-color);
			}
		';
	}
	
	/**
	 * Get test diagnostics data.
	 *
	 * @return array Test diagnostics.
	 */
	public static function get_test_diagnostics() {
		return array(
			'system' => array(
				'php_version' => PHP_VERSION,
				'wordpress_version' => get_bloginfo( 'version' ),
				'plugin_version' => '2.2.0',
				'memory_limit' => ini_get( 'memory_limit' ),
				'max_execution_time' => ini_get( 'max_execution_time' )
			),
			'settings' => array(
				'integrity_check' => 'passed',
				'total_settings' => 20,
				'invalid_settings' => 0
			),
			'performance' => array(
				'memory_usage' => memory_get_usage( true ),
				'peak_memory' => memory_get_peak_usage( true ),
				'execution_time' => 0.1
			),
			'conflicts' => array(),
			'recommendations' => array(
				'Enable caching for better performance',
				'Consider using performance mode for large sites'
			)
		);
	}
	
	/**
	 * Get test error scenarios.
	 *
	 * @return array Error scenarios.
	 */
	public static function get_error_scenarios() {
		return array(
			'invalid_color' => array(
				'data' => array( 'menu_background' => 'invalid-color' ),
				'expected_code' => 'validation_failed',
				'expected_status' => 400
			),
			'missing_permission' => array(
				'user_role' => 'subscriber',
				'expected_code' => 'rest_forbidden',
				'expected_status' => 403
			),
			'invalid_nonce' => array(
				'nonce' => 'invalid-nonce',
				'expected_code' => 'rest_cookie_invalid_nonce',
				'expected_status' => 403
			),
			'not_found' => array(
				'endpoint' => '/nonexistent',
				'expected_code' => 'rest_no_route',
				'expected_status' => 404
			)
		);
	}
}