<?php
/**
 * Theme Preset Service Class
 * 
 * Handles advanced theme preset management including preview, import/export,
 * and version compatibility checking for Phase 2 enhanced theme features.
 *
 * @package ModernAdminStylerV2
 * @subpackage Services
 * @since 2.3.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * MAS Theme Preset Service
 * 
 * Provides advanced theme management with preview, import/export, and validation.
 */
class MAS_Theme_Preset_Service {
    
    /**
     * Singleton instance
     * 
     * @var MAS_Theme_Preset_Service
     */
    private static $instance = null;
    
    /**
     * CSS Generator service instance
     * 
     * @var MAS_CSS_Generator_Service
     */
    private $css_generator;
    
    /**
     * Settings service instance
     * 
     * @var MAS_Settings_Service
     */
    private $settings_service;
    
    /**
     * Current plugin version for compatibility checking
     * 
     * @var string
     */
    private $plugin_version = '2.3.0';
    
    /**
     * Minimum compatible version for imports
     * 
     * @var string
     */
    private $min_compatible_version = '2.0.0';
    
    /**
     * Predefined theme library
     * 
     * @var array
     */
    private $predefined_themes = [];
    
    /**
     * Get singleton instance
     * 
     * @return MAS_Theme_Preset_Service
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor to enforce singleton pattern
     */
    private function __construct() {
        // Load required services
        if (class_exists('MAS_CSS_Generator_Service')) {
            $this->css_generator = MAS_CSS_Generator_Service::get_instance();
        }
        
        if (class_exists('MAS_Settings_Service')) {
            $this->settings_service = MAS_Settings_Service::get_instance();
        }
        
        // Initialize predefined themes
        $this->init_predefined_themes();
    }
    
    /**
     * Initialize predefined theme library
     * 
     * @return void
     */
    private function init_predefined_themes() {
        $this->predefined_themes = [
            'dark' => [
                'id' => 'dark',
                'name' => __('Dark', 'modern-admin-styler-v2'),
                'description' => __('Professional dark theme with deep blacks', 'modern-admin-styler-v2'),
                'type' => 'predefined',
                'readonly' => true,
                'settings' => [
                    'menu_background' => '#0a0a0a',
                    'menu_text_color' => '#ffffff',
                    'menu_hover_background' => '#1a1a1a',
                    'menu_hover_text_color' => '#ffffff',
                    'menu_active_background' => '#2a2a2a',
                    'menu_active_text_color' => '#ffffff',
                    'admin_bar_background' => '#0a0a0a',
                    'admin_bar_text_color' => '#ffffff',
                    'admin_bar_hover_color' => '#ffffff',
                ]
            ],
            'light' => [
                'id' => 'light',
                'name' => __('Light', 'modern-admin-styler-v2'),
                'description' => __('Clean and bright light theme', 'modern-admin-styler-v2'),
                'type' => 'predefined',
                'readonly' => true,
                'settings' => [
                    'menu_background' => '#ffffff',
                    'menu_text_color' => '#1a1a1a',
                    'menu_hover_background' => '#f5f5f5',
                    'menu_hover_text_color' => '#0073aa',
                    'menu_active_background' => '#e5e5e5',
                    'menu_active_text_color' => '#0073aa',
                    'admin_bar_background' => '#ffffff',
                    'admin_bar_text_color' => '#1a1a1a',
                    'admin_bar_hover_color' => '#0073aa',
                ]
            ],
            'ocean' => [
                'id' => 'ocean',
                'name' => __('Ocean', 'modern-admin-styler-v2'),
                'description' => __('Calm ocean-inspired blue theme', 'modern-admin-styler-v2'),
                'type' => 'predefined',
                'readonly' => true,
                'settings' => [
                    'menu_background' => '#006994',
                    'menu_text_color' => '#ffffff',
                    'menu_hover_background' => '#007ba7',
                    'menu_hover_text_color' => '#e0f7fa',
                    'menu_active_background' => '#0097a7',
                    'menu_active_text_color' => '#ffffff',
                    'admin_bar_background' => '#006994',
                    'admin_bar_text_color' => '#ffffff',
                    'admin_bar_hover_color' => '#e0f7fa',
                ]
            ],
            'sunset' => [
                'id' => 'sunset',
                'name' => __('Sunset', 'modern-admin-styler-v2'),
                'description' => __('Warm sunset colors with orange tones', 'modern-admin-styler-v2'),
                'type' => 'predefined',
                'readonly' => true,
                'settings' => [
                    'menu_background' => '#d84315',
                    'menu_text_color' => '#ffffff',
                    'menu_hover_background' => '#e64a19',
                    'menu_hover_text_color' => '#fff3e0',
                    'menu_active_background' => '#f4511e',
                    'menu_active_text_color' => '#ffffff',
                    'admin_bar_background' => '#d84315',
                    'admin_bar_text_color' => '#ffffff',
                    'admin_bar_hover_color' => '#fff3e0',
                ]
            ],
            'forest' => [
                'id' => 'forest',
                'name' => __('Forest', 'modern-admin-styler-v2'),
                'description' => __('Natural forest green theme', 'modern-admin-styler-v2'),
                'type' => 'predefined',
                'readonly' => true,
                'settings' => [
                    'menu_background' => '#2e7d32',
                    'menu_text_color' => '#ffffff',
                    'menu_hover_background' => '#388e3c',
                    'menu_hover_text_color' => '#e8f5e9',
                    'menu_active_background' => '#43a047',
                    'menu_active_text_color' => '#ffffff',
                    'admin_bar_background' => '#2e7d32',
                    'admin_bar_text_color' => '#ffffff',
                    'admin_bar_hover_color' => '#e8f5e9',
                ]
            ],
            'midnight' => [
                'id' => 'midnight',
                'name' => __('Midnight', 'modern-admin-styler-v2'),
                'description' => __('Deep midnight blue theme', 'modern-admin-styler-v2'),
                'type' => 'predefined',
                'readonly' => true,
                'settings' => [
                    'menu_background' => '#1a237e',
                    'menu_text_color' => '#ffffff',
                    'menu_hover_background' => '#283593',
                    'menu_hover_text_color' => '#c5cae9',
                    'menu_active_background' => '#3949ab',
                    'menu_active_text_color' => '#ffffff',
                    'admin_bar_background' => '#1a237e',
                    'admin_bar_text_color' => '#ffffff',
                    'admin_bar_hover_color' => '#c5cae9',
                ]
            ]
        ];
    }
    
    /**
     * Get all predefined theme presets
     * 
     * @return array Array of predefined themes
     */
    public function get_presets() {
        return array_values($this->predefined_themes);
    }
    
    /**
     * Get a specific preset by ID
     * 
     * @param string $preset_id Preset ID
     * @return array|WP_Error Preset data or error
     */
    public function get_preset($preset_id) {
        if (isset($this->predefined_themes[$preset_id])) {
            return $this->predefined_themes[$preset_id];
        }
        
        return new WP_Error(
            'preset_not_found',
            sprintf(__('Preset with ID "%s" not found', 'modern-admin-styler-v2'), $preset_id),
            ['status' => 404]
        );
    }
    
    /**
     * Preview a theme without applying changes
     * 
     * Generates CSS for the theme settings without saving to database.
     * 
     * @param array $theme_data Theme data with settings
     * @return array|WP_Error Preview data with CSS or error
     */
    public function preview_theme($theme_data) {
        // Validate theme data structure
        if (empty($theme_data['settings']) || !is_array($theme_data['settings'])) {
            return new WP_Error(
                'invalid_theme_data',
                __('Theme data must include settings array', 'modern-admin-styler-v2'),
                ['status' => 400]
            );
        }
        
        try {
            // Get current settings as base
            $current_settings = $this->settings_service ? 
                $this->settings_service->get_settings() : 
                [];
            
            // Merge theme settings with current settings
            $preview_settings = array_merge($current_settings, $theme_data['settings']);
            
            // Generate CSS without saving
            $css = '';
            if ($this->css_generator) {
                $css = $this->css_generator->generate($preview_settings);
            } else {
                // Fallback: generate basic CSS
                $css = $this->generate_basic_css($preview_settings);
            }
            
            // Generate unique preview ID
            $preview_id = uniqid('preview_', true);
            
            // Calculate expiration (5 minutes from now)
            $expires = time() + 300;
            
            return [
                'preview_id' => $preview_id,
                'css' => $css,
                'settings' => $preview_settings,
                'expires' => $expires,
                'expires_human' => human_time_diff(time(), $expires),
                'timestamp' => time()
            ];
            
        } catch (Exception $e) {
            return new WP_Error(
                'preview_generation_failed',
                sprintf(__('Failed to generate preview: %s', 'modern-admin-styler-v2'), $e->getMessage()),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Export a theme with version metadata and checksum
     * 
     * @param string $theme_id Theme ID to export
     * @param array $theme_data Optional theme data (if not using ID)
     * @return array|WP_Error Export data or error
     */
    public function export_theme($theme_id = null, $theme_data = null) {
        // Get theme data
        if ($theme_data === null) {
            if ($theme_id === null) {
                return new WP_Error(
                    'missing_theme_data',
                    __('Either theme_id or theme_data must be provided', 'modern-admin-styler-v2'),
                    ['status' => 400]
                );
            }
            
            // Try to get from presets first
            $theme_data = $this->get_preset($theme_id);
            
            // If not found in presets, try theme service
            if (is_wp_error($theme_data) && class_exists('MAS_Theme_Service')) {
                $theme_service = MAS_Theme_Service::get_instance();
                $theme_data = $theme_service->get_theme($theme_id);
            }
            
            if (is_wp_error($theme_data)) {
                return $theme_data;
            }
        }
        
        // Build export data
        $export_data = [
            'version' => '2.0',
            'plugin_version' => $this->plugin_version,
            'exported_at' => current_time('mysql'),
            'exported_by' => get_current_user_id(),
            'theme' => $theme_data,
            'metadata' => [
                'wordpress_version' => get_bloginfo('version'),
                'php_version' => PHP_VERSION,
                'export_format' => 'mas-theme-v2'
            ]
        ];
        
        // Calculate checksum
        $export_data['checksum'] = $this->calculate_checksum($theme_data);
        
        return $export_data;
    }
    
    /**
     * Import a theme with version compatibility validation
     * 
     * @param array $import_data Import data with theme and metadata
     * @return array|WP_Error Imported theme or error
     */
    public function import_theme($import_data) {
        // Validate import data structure
        if (!is_array($import_data)) {
            return new WP_Error(
                'invalid_import_data',
                __('Import data must be an array', 'modern-admin-styler-v2'),
                ['status' => 400]
            );
        }
        
        // Check for required fields
        if (!isset($import_data['version']) || !isset($import_data['theme'])) {
            return new WP_Error(
                'invalid_import_format',
                __('Import data missing required fields (version, theme)', 'modern-admin-styler-v2'),
                ['status' => 400]
            );
        }
        
        // Validate version compatibility
        $compatibility_check = $this->is_compatible_version($import_data['version']);
        if (is_wp_error($compatibility_check)) {
            return $compatibility_check;
        }
        
        // Verify checksum if present
        if (isset($import_data['checksum'])) {
            $checksum_valid = $this->verify_checksum($import_data);
            if (is_wp_error($checksum_valid)) {
                return $checksum_valid;
            }
        }
        
        // Validate theme structure
        $theme_data = $import_data['theme'];
        if (empty($theme_data['settings']) || !is_array($theme_data['settings'])) {
            return new WP_Error(
                'invalid_theme_structure',
                __('Imported theme missing valid settings', 'modern-admin-styler-v2'),
                ['status' => 400]
            );
        }
        
        // Sanitize theme data
        $sanitized_theme = $this->sanitize_import_theme($theme_data);
        
        // Add import metadata
        $sanitized_theme['metadata'] = array_merge(
            $sanitized_theme['metadata'] ?? [],
            [
                'imported_at' => current_time('mysql'),
                'imported_by' => get_current_user_id(),
                'imported_from_version' => $import_data['plugin_version'] ?? 'unknown'
            ]
        );
        
        return $sanitized_theme;
    }
    
    /**
     * Check if import version is compatible
     * 
     * @param string $version Version to check
     * @return bool|WP_Error True if compatible, error if not
     */
    public function is_compatible_version($version) {
        // Validate version format
        if (!preg_match('/^\d+\.\d+(\.\d+)?$/', $version)) {
            return new WP_Error(
                'invalid_version_format',
                sprintf(__('Invalid version format: %s', 'modern-admin-styler-v2'), $version),
                ['status' => 400]
            );
        }
        
        // Check if version is compatible
        if (version_compare($version, $this->min_compatible_version, '<')) {
            return new WP_Error(
                'incompatible_version',
                sprintf(
                    __('Theme version %s is not compatible. Minimum required version: %s', 'modern-admin-styler-v2'),
                    $version,
                    $this->min_compatible_version
                ),
                [
                    'status' => 400,
                    'version' => $version,
                    'min_version' => $this->min_compatible_version,
                    'current_version' => $this->plugin_version
                ]
            );
        }
        
        return true;
    }
    
    /**
     * Verify checksum of imported theme
     * 
     * @param array $import_data Import data with checksum
     * @return bool|WP_Error True if valid, error if not
     */
    public function verify_checksum($import_data) {
        if (!isset($import_data['checksum']) || !isset($import_data['theme'])) {
            return new WP_Error(
                'missing_checksum_data',
                __('Checksum or theme data missing', 'modern-admin-styler-v2'),
                ['status' => 400]
            );
        }
        
        $expected_checksum = $import_data['checksum'];
        $calculated_checksum = $this->calculate_checksum($import_data['theme']);
        
        if ($expected_checksum !== $calculated_checksum) {
            return new WP_Error(
                'checksum_mismatch',
                __('Theme data integrity check failed. The file may be corrupted.', 'modern-admin-styler-v2'),
                [
                    'status' => 400,
                    'expected' => $expected_checksum,
                    'calculated' => $calculated_checksum
                ]
            );
        }
        
        return true;
    }
    
    /**
     * Calculate checksum for theme data
     * 
     * @param array $theme_data Theme data
     * @return string SHA256 checksum
     */
    private function calculate_checksum($theme_data) {
        // Create a consistent string representation
        $data_string = json_encode($theme_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        // Calculate SHA256 hash
        return hash('sha256', $data_string);
    }
    
    /**
     * Sanitize imported theme data
     * 
     * @param array $theme_data Theme data to sanitize
     * @return array Sanitized theme data
     */
    private function sanitize_import_theme($theme_data) {
        $sanitized = [];
        
        // Sanitize basic fields
        if (isset($theme_data['id'])) {
            $sanitized['id'] = sanitize_key($theme_data['id']);
        }
        
        if (isset($theme_data['name'])) {
            $sanitized['name'] = sanitize_text_field($theme_data['name']);
        }
        
        if (isset($theme_data['description'])) {
            $sanitized['description'] = sanitize_textarea_field($theme_data['description']);
        }
        
        // Sanitize settings
        if (isset($theme_data['settings']) && is_array($theme_data['settings'])) {
            $sanitized['settings'] = [];
            
            foreach ($theme_data['settings'] as $key => $value) {
                $sanitized_key = sanitize_key($key);
                
                if ($this->is_color_field($key)) {
                    $sanitized['settings'][$sanitized_key] = sanitize_hex_color($value);
                } elseif (is_numeric($value)) {
                    $sanitized['settings'][$sanitized_key] = is_float($value) ? floatval($value) : intval($value);
                } elseif (is_bool($value)) {
                    $sanitized['settings'][$sanitized_key] = (bool) $value;
                } else {
                    $sanitized['settings'][$sanitized_key] = sanitize_text_field($value);
                }
            }
        }
        
        // Preserve metadata if present
        if (isset($theme_data['metadata']) && is_array($theme_data['metadata'])) {
            $sanitized['metadata'] = array_map('sanitize_text_field', $theme_data['metadata']);
        }
        
        // Set type to custom for imported themes
        $sanitized['type'] = 'custom';
        $sanitized['readonly'] = false;
        
        return $sanitized;
    }
    
    /**
     * Check if field is a color field
     * 
     * @param string $field_name Field name
     * @return bool True if color field
     */
    private function is_color_field($field_name) {
        $color_keywords = ['color', 'background', 'bg'];
        foreach ($color_keywords as $keyword) {
            if (strpos($field_name, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Generate basic CSS from settings (fallback)
     * 
     * @param array $settings Settings array
     * @return string Generated CSS
     */
    private function generate_basic_css($settings) {
        $css = "/* MAS Theme Preview CSS */\n\n";
        
        // Menu styles
        if (!empty($settings['menu_background'])) {
            $css .= "#adminmenu { background: {$settings['menu_background']} !important; }\n";
        }
        
        if (!empty($settings['menu_text_color'])) {
            $css .= "#adminmenu a { color: {$settings['menu_text_color']} !important; }\n";
        }
        
        // Admin bar styles
        if (!empty($settings['admin_bar_background'])) {
            $css .= "#wpadminbar { background: {$settings['admin_bar_background']} !important; }\n";
        }
        
        if (!empty($settings['admin_bar_text_color'])) {
            $css .= "#wpadminbar * { color: {$settings['admin_bar_text_color']} !important; }\n";
        }
        
        return $css;
    }
}
