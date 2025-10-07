<?php
/**
 * Theme Service Class
 * 
 * Handles all theme business logic including CRUD operations for themes and palettes.
 * Implements predefined themes loading, protection, and custom theme management.
 *
 * @package ModernAdminStylerV2
 * @subpackage Services
 * @since 2.2.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * MAS Theme Service
 * 
 * Provides centralized theme management with predefined and custom themes.
 */
class MAS_Theme_Service {
    
    /**
     * Cache group for WordPress object cache
     * 
     * @var string
     */
    private $cache_group = 'mas_v2_themes';
    
    /**
     * Cache expiration time in seconds (1 hour)
     * 
     * @var int
     */
    private $cache_expiration = 3600;
    
    /**
     * Custom themes option name in database
     * 
     * @var string
     */
    private $option_name = 'mas_v2_custom_themes';
    
    /**
     * Singleton instance
     * 
     * @var MAS_Theme_Service
     */
    private static $instance = null;
    
    /**
     * Settings service instance
     * 
     * @var MAS_Settings_Service
     */
    private $settings_service;
    
    /**
     * Validation service instance
     * 
     * @var MAS_Validation_Service
     */
    private $validation_service;
    
    /**
     * Get singleton instance
     * 
     * @return MAS_Theme_Service
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
        $this->settings_service = MAS_Settings_Service::get_instance();
        
        // Load validation service if available
        if (class_exists('MAS_Validation_Service')) {
            $this->validation_service = new MAS_Validation_Service();
        }
    }
    
    /**
     * Get all themes (predefined + custom)
     * 
     * @return array Array of themes
     */
    public function get_themes() {
        // Try cache first
        $cached = wp_cache_get('all_themes', $this->cache_group);
        if ($cached !== false) {
            return $cached;
        }
        
        // Get predefined themes
        $predefined = $this->get_predefined_themes();
        
        // Get custom themes
        $custom = $this->get_custom_themes();
        
        // Merge themes
        $all_themes = array_merge($predefined, $custom);
        
        // Cache for future requests
        wp_cache_set('all_themes', $all_themes, $this->cache_group, $this->cache_expiration);
        
        return $all_themes;
    }
    
    /**
     * Get a specific theme by ID
     * 
     * @param string $theme_id Theme ID
     * @return array|WP_Error Theme data or error
     */
    public function get_theme($theme_id) {
        $themes = $this->get_themes();
        
        foreach ($themes as $theme) {
            if ($theme['id'] === $theme_id) {
                return $theme;
            }
        }
        
        return new WP_Error(
            'theme_not_found',
            sprintf(__('Theme with ID "%s" not found', 'modern-admin-styler-v2'), $theme_id),
            ['status' => 404]
        );
    }
    
    /**
     * Create a custom theme
     * 
     * @param array $theme_data Theme data
     * @return array|WP_Error Created theme or error
     */
    public function create_theme($theme_data) {
        // Validate theme data
        $validation_result = $this->validate_theme_data($theme_data);
        if (is_wp_error($validation_result)) {
            return $validation_result;
        }
        
        // Check for ID conflicts
        if ($this->theme_exists($theme_data['id'])) {
            return new WP_Error(
                'theme_exists',
                sprintf(__('Theme with ID "%s" already exists', 'modern-admin-styler-v2'), $theme_data['id']),
                ['status' => 409]
            );
        }
        
        // Check if trying to use a reserved theme ID
        if ($this->is_reserved_theme_id($theme_data['id'])) {
            return new WP_Error(
                'reserved_theme_id',
                sprintf(__('Theme ID "%s" is reserved for predefined themes', 'modern-admin-styler-v2'), $theme_data['id']),
                ['status' => 400]
            );
        }
        
        // Sanitize theme data
        $sanitized_theme = $this->sanitize_theme_data($theme_data);
        
        // Add metadata
        $sanitized_theme['type'] = 'custom';
        $sanitized_theme['readonly'] = false;
        $sanitized_theme['metadata'] = [
            'created' => current_time('mysql'),
            'modified' => current_time('mysql'),
            'author' => get_current_user_id(),
            'version' => '1.0'
        ];
        
        // Get existing custom themes
        $custom_themes = $this->get_custom_themes();
        
        // Add new theme
        $custom_themes[] = $sanitized_theme;
        
        // Save to database
        $result = update_option($this->option_name, $custom_themes);
        
        if (!$result && get_option($this->option_name) !== $custom_themes) {
            return new WP_Error(
                'save_failed',
                __('Failed to save theme to database', 'modern-admin-styler-v2'),
                ['status' => 500]
            );
        }
        
        // Clear cache
        $this->clear_cache();
        
        return $sanitized_theme;
    }
    
    /**
     * Update an existing theme
     * 
     * @param string $theme_id Theme ID
     * @param array $theme_data Updated theme data
     * @return array|WP_Error Updated theme or error
     */
    public function update_theme($theme_id, $theme_data) {
        // Check if theme exists
        $existing_theme = $this->get_theme($theme_id);
        if (is_wp_error($existing_theme)) {
            return $existing_theme;
        }
        
        // Check if theme is readonly
        if (!empty($existing_theme['readonly'])) {
            return new WP_Error(
                'theme_readonly',
                __('Cannot modify predefined themes', 'modern-admin-styler-v2'),
                ['status' => 403]
            );
        }
        
        // Validate theme data
        $validation_result = $this->validate_theme_data($theme_data, true);
        if (is_wp_error($validation_result)) {
            return $validation_result;
        }
        
        // Sanitize theme data
        $sanitized_theme = $this->sanitize_theme_data($theme_data);
        
        // Preserve original metadata and update modified time
        $sanitized_theme['id'] = $theme_id;
        $sanitized_theme['type'] = 'custom';
        $sanitized_theme['readonly'] = false;
        $sanitized_theme['metadata'] = $existing_theme['metadata'];
        $sanitized_theme['metadata']['modified'] = current_time('mysql');
        
        // Get custom themes
        $custom_themes = $this->get_custom_themes();
        
        // Find and update theme
        $updated = false;
        foreach ($custom_themes as $index => $theme) {
            if ($theme['id'] === $theme_id) {
                $custom_themes[$index] = $sanitized_theme;
                $updated = true;
                break;
            }
        }
        
        if (!$updated) {
            return new WP_Error(
                'update_failed',
                __('Failed to update theme', 'modern-admin-styler-v2'),
                ['status' => 500]
            );
        }
        
        // Save to database
        update_option($this->option_name, $custom_themes);
        
        // Clear cache
        $this->clear_cache();
        
        return $sanitized_theme;
    }
    
    /**
     * Delete a custom theme
     * 
     * @param string $theme_id Theme ID
     * @return bool|WP_Error True on success, error on failure
     */
    public function delete_theme($theme_id) {
        // Check if theme exists
        $existing_theme = $this->get_theme($theme_id);
        if (is_wp_error($existing_theme)) {
            return $existing_theme;
        }
        
        // Check if theme is readonly
        if (!empty($existing_theme['readonly'])) {
            return new WP_Error(
                'theme_readonly',
                __('Cannot delete predefined themes', 'modern-admin-styler-v2'),
                ['status' => 403]
            );
        }
        
        // Get custom themes
        $custom_themes = $this->get_custom_themes();
        
        // Find and remove theme
        $deleted = false;
        foreach ($custom_themes as $index => $theme) {
            if ($theme['id'] === $theme_id) {
                unset($custom_themes[$index]);
                $deleted = true;
                break;
            }
        }
        
        if (!$deleted) {
            return new WP_Error(
                'delete_failed',
                __('Failed to delete theme', 'modern-admin-styler-v2'),
                ['status' => 500]
            );
        }
        
        // Re-index array
        $custom_themes = array_values($custom_themes);
        
        // Save to database
        update_option($this->option_name, $custom_themes);
        
        // Clear cache
        $this->clear_cache();
        
        return true;
    }
    
    /**
     * Apply a theme to current settings
     * 
     * @param string $theme_id Theme ID to apply
     * @return bool|WP_Error True on success, error on failure
     */
    public function apply_theme($theme_id) {
        // Get theme
        $theme = $this->get_theme($theme_id);
        if (is_wp_error($theme)) {
            return $theme;
        }
        
        // Check if theme has settings
        if (empty($theme['settings'])) {
            return new WP_Error(
                'invalid_theme',
                __('Theme does not contain valid settings', 'modern-admin-styler-v2'),
                ['status' => 400]
            );
        }
        
        // Get current settings
        $current_settings = $this->settings_service->get_settings();
        
        // Merge theme settings with current settings (theme settings take precedence)
        $new_settings = array_merge($current_settings, $theme['settings']);
        
        // Update current theme identifier
        $new_settings['current_theme'] = $theme_id;
        
        // Save settings
        $result = $this->settings_service->save_settings($new_settings);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return true;
    }
    
    /**
     * Get predefined themes
     * 
     * @return array Array of predefined themes
     */
    private function get_predefined_themes() {
        return [
            [
                'id' => 'default',
                'name' => __('Default', 'modern-admin-styler-v2'),
                'description' => __('WordPress default color scheme', 'modern-admin-styler-v2'),
                'type' => 'predefined',
                'readonly' => true,
                'settings' => [
                    'menu_background' => '#23282d',
                    'menu_text_color' => '#ffffff',
                    'menu_hover_background' => '#32373c',
                    'menu_hover_text_color' => '#00a0d2',
                    'menu_active_background' => '#0073aa',
                    'menu_active_text_color' => '#ffffff',
                    'admin_bar_background' => '#23282d',
                    'admin_bar_text_color' => '#ffffff',
                    'admin_bar_hover_color' => '#00a0d2',
                ],
                'metadata' => [
                    'author' => 'MAS Team',
                    'version' => '1.0',
                    'created' => '2025-01-01'
                ]
            ],
            [
                'id' => 'dark-blue',
                'name' => __('Dark Blue', 'modern-admin-styler-v2'),
                'description' => __('Professional dark blue theme', 'modern-admin-styler-v2'),
                'type' => 'predefined',
                'readonly' => true,
                'settings' => [
                    'menu_background' => '#1e1e2e',
                    'menu_text_color' => '#ffffff',
                    'menu_hover_background' => '#2d2d44',
                    'menu_hover_text_color' => '#89b4fa',
                    'menu_active_background' => '#3d3d5c',
                    'menu_active_text_color' => '#89b4fa',
                    'admin_bar_background' => '#1e1e2e',
                    'admin_bar_text_color' => '#ffffff',
                    'admin_bar_hover_color' => '#89b4fa',
                ],
                'metadata' => [
                    'author' => 'MAS Team',
                    'version' => '1.0',
                    'created' => '2025-01-01'
                ]
            ],
            [
                'id' => 'light-modern',
                'name' => __('Light Modern', 'modern-admin-styler-v2'),
                'description' => __('Clean and modern light theme', 'modern-admin-styler-v2'),
                'type' => 'predefined',
                'readonly' => true,
                'settings' => [
                    'menu_background' => '#ffffff',
                    'menu_text_color' => '#2c3e50',
                    'menu_hover_background' => '#f8f9fa',
                    'menu_hover_text_color' => '#3498db',
                    'menu_active_background' => '#e9ecef',
                    'menu_active_text_color' => '#2980b9',
                    'admin_bar_background' => '#ffffff',
                    'admin_bar_text_color' => '#2c3e50',
                    'admin_bar_hover_color' => '#3498db',
                ],
                'metadata' => [
                    'author' => 'MAS Team',
                    'version' => '1.0',
                    'created' => '2025-01-01'
                ]
            ],
            [
                'id' => 'ocean',
                'name' => __('Ocean', 'modern-admin-styler-v2'),
                'description' => __('Calm ocean-inspired theme', 'modern-admin-styler-v2'),
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
                ],
                'metadata' => [
                    'author' => 'MAS Team',
                    'version' => '1.0',
                    'created' => '2025-01-01'
                ]
            ],
            [
                'id' => 'sunset',
                'name' => __('Sunset', 'modern-admin-styler-v2'),
                'description' => __('Warm sunset colors', 'modern-admin-styler-v2'),
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
                ],
                'metadata' => [
                    'author' => 'MAS Team',
                    'version' => '1.0',
                    'created' => '2025-01-01'
                ]
            ],
            [
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
                ],
                'metadata' => [
                    'author' => 'MAS Team',
                    'version' => '1.0',
                    'created' => '2025-01-01'
                ]
            ]
        ];
    }
    
    /**
     * Get custom themes from database
     * 
     * @return array Array of custom themes
     */
    private function get_custom_themes() {
        $custom_themes = get_option($this->option_name, []);
        
        // Ensure it's an array
        if (!is_array($custom_themes)) {
            $custom_themes = [];
        }
        
        return $custom_themes;
    }
    
    /**
     * Check if theme exists
     * 
     * @param string $theme_id Theme ID
     * @return bool True if exists
     */
    private function theme_exists($theme_id) {
        $theme = $this->get_theme($theme_id);
        return !is_wp_error($theme);
    }
    
    /**
     * Check if theme ID is reserved (predefined theme)
     * 
     * @param string $theme_id Theme ID
     * @return bool True if reserved
     */
    private function is_reserved_theme_id($theme_id) {
        $reserved_ids = ['default', 'dark-blue', 'light-modern', 'ocean', 'sunset', 'forest'];
        return in_array($theme_id, $reserved_ids, true);
    }
    
    /**
     * Validate theme data
     * 
     * @param array $theme_data Theme data to validate
     * @param bool $is_update Whether this is an update operation
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    private function validate_theme_data($theme_data, $is_update = false) {
        $errors = [];
        
        // Validate required fields (not required for updates)
        if (!$is_update) {
            if (empty($theme_data['id'])) {
                $errors['id'] = __('Theme ID is required', 'modern-admin-styler-v2');
            } elseif (!preg_match('/^[a-z0-9-]+$/', $theme_data['id'])) {
                $errors['id'] = __('Theme ID must contain only lowercase letters, numbers, and hyphens', 'modern-admin-styler-v2');
            }
            
            if (empty($theme_data['name'])) {
                $errors['name'] = __('Theme name is required', 'modern-admin-styler-v2');
            }
        }
        
        // Validate settings if provided
        if (isset($theme_data['settings'])) {
            if (!is_array($theme_data['settings'])) {
                $errors['settings'] = __('Theme settings must be an array', 'modern-admin-styler-v2');
            } else {
                // Use validation service if available for comprehensive validation
                if ($this->validation_service) {
                    $validation_result = $this->validation_service->validate_settings($theme_data['settings']);
                    if (!$validation_result['valid']) {
                        foreach ($validation_result['errors'] as $field => $error) {
                            $errors["settings.$field"] = $error;
                        }
                    }
                } else {
                    // Fallback to basic color validation
                    $color_fields = [
                        'menu_background', 'menu_text_color', 'menu_hover_background',
                        'menu_hover_text_color', 'menu_active_background', 'menu_active_text_color',
                        'admin_bar_background', 'admin_bar_text_color', 'admin_bar_hover_color'
                    ];
                    
                    foreach ($color_fields as $field) {
                        if (isset($theme_data['settings'][$field]) && !empty($theme_data['settings'][$field])) {
                            if (!$this->is_valid_color($theme_data['settings'][$field])) {
                                $errors["settings.$field"] = sprintf(
                                    __('Invalid color value for %s', 'modern-admin-styler-v2'),
                                    $field
                                );
                            }
                        }
                    }
                }
            }
        }
        
        if (!empty($errors)) {
            return new WP_Error(
                'validation_failed',
                __('Theme validation failed', 'modern-admin-styler-v2'),
                ['status' => 400, 'errors' => $errors]
            );
        }
        
        return true;
    }
    
    /**
     * Sanitize theme data
     * 
     * @param array $theme_data Theme data to sanitize
     * @return array Sanitized theme data
     */
    private function sanitize_theme_data($theme_data) {
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
                if ($this->is_color_field($key)) {
                    $sanitized['settings'][$key] = sanitize_hex_color($value);
                } elseif (is_numeric($value)) {
                    $sanitized['settings'][$key] = is_float($value) ? floatval($value) : intval($value);
                } elseif (is_bool($value)) {
                    $sanitized['settings'][$key] = (bool) $value;
                } else {
                    $sanitized['settings'][$key] = sanitize_text_field($value);
                }
            }
        }
        
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
     * Validate color value
     * 
     * @param string $color Color value to validate
     * @return bool True if valid
     */
    private function is_valid_color($color) {
        // Check for hex color
        if (preg_match('/^#[a-f0-9]{6}$/i', $color)) {
            return true;
        }
        
        // Check for short hex color
        if (preg_match('/^#[a-f0-9]{3}$/i', $color)) {
            return true;
        }
        
        // Check for rgb/rgba
        if (preg_match('/^rgba?\([\d\s,\.]+\)$/i', $color)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Clear themes cache
     * 
     * @return void
     */
    private function clear_cache() {
        wp_cache_delete('all_themes', $this->cache_group);
    }
}
