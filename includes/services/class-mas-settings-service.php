<?php
/**
 * Settings Service Class
 * 
 * Handles all settings business logic including get, save, update, and reset operations.
 * Implements caching with WordPress transients and automatic CSS regeneration.
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
 * MAS Settings Service
 * 
 * Provides centralized settings management with caching and validation.
 */
class MAS_Settings_Service {
    
    /**
     * Cache service instance
     * 
     * @var MAS_Cache_Service
     */
    private $cache_service;
    
    /**
     * Settings option name in database
     * 
     * @var string
     */
    private $option_name = 'mas_v2_settings';
    
    /**
     * Singleton instance
     * 
     * @var MAS_Settings_Service
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     * 
     * @return MAS_Settings_Service
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
        $this->cache_service = new MAS_Cache_Service();
    }
    
    /**
     * Get current settings
     * 
     * Retrieves settings from cache if available, otherwise from database.
     * 
     * @return array Settings array
     */
    public function get_settings() {
        return $this->cache_service->remember('current_settings', function() {
            // Get from database
            $settings = get_option($this->option_name, $this->get_defaults());
            
            // Ensure we have all default keys (for backward compatibility)
            $settings = wp_parse_args($settings, $this->get_defaults());
            
            return $settings;
        });
    }
    
    /**
     * Get last modified time for settings
     * 
     * Returns the timestamp when settings were last modified.
     * Used for Last-Modified header in conditional requests.
     * 
     * @return int Unix timestamp of last modification
     */
    public function get_last_modified_time() {
        // Try to get from cache first
        $last_modified = wp_cache_get('mas_v2_settings_last_modified', 'mas_v2');
        
        if ($last_modified === false) {
            // Get from option metadata if available
            global $wpdb;
            
            // Query for the last time the settings option was updated
            $query = $wpdb->prepare(
                "SELECT option_id FROM {$wpdb->options} WHERE option_name = %s",
                $this->option_name
            );
            
            $option_id = $wpdb->get_var($query);
            
            if ($option_id) {
                // Use current time as fallback if we can't determine exact time
                // In production, you might want to store this in a separate option
                $last_modified = get_option('mas_v2_settings_last_modified', time());
            } else {
                // Settings don't exist yet, use current time
                $last_modified = time();
            }
            
            // Cache for 5 minutes
            wp_cache_set('mas_v2_settings_last_modified', $last_modified, 'mas_v2', 300);
        }
        
        return (int) $last_modified;
    }
    
    /**
     * Update last modified time
     * 
     * Updates the timestamp tracking when settings were last modified.
     * 
     * @return void
     */
    private function update_last_modified_time() {
        $timestamp = time();
        update_option('mas_v2_settings_last_modified', $timestamp, false);
        wp_cache_set('mas_v2_settings_last_modified', $timestamp, 'mas_v2', 300);
    }
    
    /**
     * Save settings (complete replacement)
     * 
     * Saves settings to database, invalidates cache, and regenerates CSS.
     * 
     * @param array $settings Settings array to save
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function save_settings($settings) {
        // Validate settings
        $validation_result = $this->validate_settings($settings);
        if (is_wp_error($validation_result)) {
            return $validation_result;
        }
        
        // Sanitize settings
        $sanitized_settings = $this->sanitize_settings($settings);
        
        // Save to database
        $result = update_option($this->option_name, $sanitized_settings);
        
        if (!$result && get_option($this->option_name) !== $sanitized_settings) {
            return new WP_Error(
                'save_failed',
                __('Failed to save settings to database', 'modern-admin-styler-v2'),
                ['status' => 500]
            );
        }
        
        // Invalidate cache
        $this->clear_cache();
        
        // Update last modified time
        $this->update_last_modified_time();
        
        // Regenerate CSS
        $this->regenerate_css($sanitized_settings);
        
        // Fire action for cache invalidation
        do_action('mas_v2_settings_updated', $sanitized_settings);
        
        return true;
    }
    
    /**
     * Update settings (partial update)
     * 
     * Merges provided settings with existing settings.
     * 
     * @param array $settings Partial settings array to update
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function update_settings($settings) {
        // Get current settings
        $current_settings = $this->get_settings();
        
        // Merge with new settings
        $merged_settings = array_merge($current_settings, $settings);
        
        // Save merged settings
        return $this->save_settings($merged_settings);
    }
    
    /**
     * Reset settings to defaults
     * 
     * Creates a backup before resetting and regenerates CSS.
     * 
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function reset_settings() {
        // Create backup of current settings
        $current_settings = $this->get_settings();
        $this->create_backup($current_settings, 'before_reset');
        
        // Get default settings
        $defaults = $this->get_defaults();
        
        // Save defaults
        $result = update_option($this->option_name, $defaults);
        
        if (!$result && get_option($this->option_name) !== $defaults) {
            return new WP_Error(
                'reset_failed',
                __('Failed to reset settings to defaults', 'modern-admin-styler-v2'),
                ['status' => 500]
            );
        }
        
        // Invalidate cache
        $this->clear_cache();
        
        // Update last modified time
        $this->update_last_modified_time();
        
        // Regenerate CSS with defaults
        $this->regenerate_css($defaults);
        
        return true;
    }
    
    /**
     * Get default settings
     * 
     * @return array Default settings array
     */
    public function get_defaults() {
        return [
            // General
            'enable_plugin' => true,
            'theme' => 'modern',
            'color_scheme' => 'light',
            'color_palette' => 'professional-blue',
            'font_family' => 'system',
            'font_size' => 14,
            'enable_animations' => true,
            'animation_type' => 'smooth',
            'live_preview' => true,
            'auto_save' => false,
            'compact_mode' => false,
            'global_border_radius' => 8,
            'enable_shadows' => true,
            'shadow_color' => '#000000',
            'shadow_blur' => 10,
            
            // Admin Bar
            'custom_admin_bar_style' => true,
            'admin_bar_background' => '#23282d',
            'admin_bar_bg' => '#23282d',
            'admin_bar_text_color' => '#ffffff',
            'admin_bar_hover_color' => '#00a0d2',
            'admin_bar_height' => 32,
            'admin_bar_font_size' => 13,
            'admin_bar_padding' => 8,
            'admin_bar_border_radius' => 0,
            'admin_bar_shadow' => false,
            'admin_bar_glassmorphism' => false,
            'admin_bar_detached' => false,
            'admin_bar_floating' => true,
            'admin_bar_glossy' => true,
            
            // Menu
            'menu_background' => '#23282d',
            'menu_bg' => '#23282d',
            'menu_text_color' => '#ffffff',
            'menu_hover_background' => '#32373c',
            'menu_hover_color' => '#32373c',
            'menu_hover_text_color' => '#00a0d2',
            'menu_active_background' => '#0073aa',
            'menu_active_text_color' => '#ffffff',
            'menu_width' => 160,
            'menu_item_height' => 34,
            'menu_rounded_corners' => false,
            'menu_shadow' => false,
            'menu_compact_mode' => false,
            'menu_glassmorphism' => false,
            'menu_detached' => false,
            'menu_floating' => true,
            'menu_glossy' => true,
            
            // Submenu
            'submenu_background' => '#2c3338',
            'submenu_text_color' => '#ffffff',
            'submenu_hover_background' => '#32373c',
            'submenu_hover_text_color' => '#00a0d2',
            
            // Content
            'content_background' => '#f1f1f1',
            'content_card_background' => '#ffffff',
            'content_text_color' => '#333333',
            'content_link_color' => '#0073aa',
            
            // Buttons
            'button_primary_background' => '#0073aa',
            'button_primary_text_color' => '#ffffff',
            'button_border_radius' => 4,
            
            // Effects
            'animation_speed' => 300,
            'glassmorphism_effects' => false,
            'glassmorphism_blur' => 10,
            
            // Advanced
            'custom_css' => '',
            'custom_js' => '',
            'debug_mode' => false,
        ];
    }
    
    /**
     * Validate settings
     * 
     * @param array $settings Settings to validate
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    private function validate_settings($settings) {
        $errors = [];
        
        // Validate color fields
        $color_fields = [
            'menu_background', 'menu_bg', 'menu_text_color', 'menu_hover_background',
            'menu_hover_color', 'menu_hover_text_color', 'menu_active_background',
            'menu_active_text_color', 'admin_bar_background', 'admin_bar_bg',
            'admin_bar_text_color', 'admin_bar_hover_color', 'content_background',
            'content_card_background', 'content_text_color', 'content_link_color',
            'button_primary_background', 'button_primary_text_color', 'shadow_color'
        ];
        
        foreach ($color_fields as $field) {
            if (isset($settings[$field]) && !empty($settings[$field])) {
                if (!$this->is_valid_color($settings[$field])) {
                    $errors[$field] = sprintf(
                        __('Invalid color value for %s', 'modern-admin-styler-v2'),
                        $field
                    );
                }
            }
        }
        
        // Validate numeric fields
        $numeric_fields = [
            'font_size', 'admin_bar_height', 'admin_bar_font_size', 'admin_bar_padding',
            'menu_width', 'menu_item_height', 'button_border_radius', 'animation_speed',
            'glassmorphism_blur', 'shadow_blur', 'global_border_radius'
        ];
        
        foreach ($numeric_fields as $field) {
            if (isset($settings[$field]) && !is_numeric($settings[$field])) {
                $errors[$field] = sprintf(
                    __('Invalid numeric value for %s', 'modern-admin-styler-v2'),
                    $field
                );
            }
        }
        
        // Validate boolean fields
        $boolean_fields = [
            'enable_plugin', 'enable_animations', 'live_preview', 'auto_save',
            'compact_mode', 'enable_shadows', 'custom_admin_bar_style',
            'admin_bar_shadow', 'admin_bar_glassmorphism', 'admin_bar_detached',
            'admin_bar_floating', 'admin_bar_glossy', 'menu_rounded_corners',
            'menu_shadow', 'menu_compact_mode', 'menu_glassmorphism',
            'menu_detached', 'menu_floating', 'menu_glossy', 'glassmorphism_effects',
            'debug_mode'
        ];
        
        foreach ($boolean_fields as $field) {
            if (isset($settings[$field]) && !is_bool($settings[$field]) && !in_array($settings[$field], [0, 1, '0', '1', 'true', 'false'], true)) {
                $errors[$field] = sprintf(
                    __('Invalid boolean value for %s', 'modern-admin-styler-v2'),
                    $field
                );
            }
        }
        
        if (!empty($errors)) {
            return new WP_Error(
                'validation_failed',
                __('Settings validation failed', 'modern-admin-styler-v2'),
                ['status' => 400, 'errors' => $errors]
            );
        }
        
        return true;
    }
    
    /**
     * Sanitize settings
     * 
     * @param array $settings Settings to sanitize
     * @return array Sanitized settings
     */
    private function sanitize_settings($settings) {
        $sanitized = [];
        
        foreach ($settings as $key => $value) {
            // Handle different types of values
            if (is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false'], true)) {
                // Boolean values
                $sanitized[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } elseif (is_numeric($value)) {
                // Numeric values
                $sanitized[$key] = is_float($value) ? floatval($value) : intval($value);
            } elseif ($this->is_color_field($key)) {
                // Color values
                $sanitized[$key] = sanitize_hex_color($value);
            } elseif (in_array($key, ['custom_css', 'custom_js'])) {
                // Allow CSS/JS but strip tags
                $sanitized[$key] = wp_strip_all_tags($value);
            } else {
                // Text values
                $sanitized[$key] = sanitize_text_field($value);
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
     * Clear settings cache
     * 
     * @return void
     */
    private function clear_cache() {
        $this->cache_service->invalidate_settings_cache();
        
        // Also clear transients
        delete_transient('mas_v2_settings_cache');
        delete_transient('mas_v2_generated_css');
    }
    
    /**
     * Regenerate CSS after settings change
     * 
     * @param array $settings Settings to generate CSS from
     * @return void
     */
    private function regenerate_css($settings) {
        // Clear CSS cache
        delete_transient('mas_v2_generated_css');
        
        // Generate new CSS
        $css = $this->generate_css($settings);
        
        // Cache generated CSS for 1 hour
        set_transient('mas_v2_generated_css', $css, 3600);
        
        // Fire action for other components
        do_action('mas_v2_css_regenerated', $css, $settings);
    }
    
    /**
     * Generate CSS from settings
     * 
     * @param array $settings Settings array
     * @return string Generated CSS
     */
    private function generate_css($settings) {
        $css = "/* Modern Admin Styler V2 - Generated CSS */\n\n";
        
        // Admin Bar styles
        if (!empty($settings['admin_bar_background'])) {
            $css .= "#wpadminbar {\n";
            $css .= "    background: {$settings['admin_bar_background']} !important;\n";
            $css .= "}\n\n";
        }
        
        // Menu styles
        if (!empty($settings['menu_background'])) {
            $css .= "#adminmenu, #adminmenu .wp-submenu {\n";
            $css .= "    background: {$settings['menu_background']} !important;\n";
            $css .= "}\n\n";
        }
        
        if (!empty($settings['menu_text_color'])) {
            $css .= "#adminmenu a {\n";
            $css .= "    color: {$settings['menu_text_color']} !important;\n";
            $css .= "}\n\n";
        }
        
        // Custom CSS
        if (!empty($settings['custom_css'])) {
            $css .= "\n/* Custom CSS */\n";
            $css .= $settings['custom_css'] . "\n";
        }
        
        return $css;
    }
    
    /**
     * Create backup of settings
     * 
     * @param array $settings Settings to backup
     * @param string $type Backup type identifier
     * @return bool True on success
     */
    private function create_backup($settings, $type = 'manual') {
        $backup_key = 'mas_v2_settings_backup_' . $type . '_' . time();
        return update_option($backup_key, $settings, false);
    }
}
