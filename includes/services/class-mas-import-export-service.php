<?php
/**
 * Import/Export Service Class
 * 
 * Handles all import and export operations including JSON export with version metadata,
 * import validation, and legacy format migration for backward compatibility.
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
 * MAS Import/Export Service
 * 
 * Provides centralized import/export management with validation and migration.
 */
class MAS_Import_Export_Service {
    
    /**
     * Current export format version
     * 
     * @var string
     */
    private $export_version = '2.2.0';
    
    /**
     * Minimum supported import version
     * 
     * @var string
     */
    private $min_import_version = '2.0.0';
    
    /**
     * Settings service instance
     * 
     * @var MAS_Settings_Service
     */
    private $settings_service;
    
    /**
     * Backup service instance
     * 
     * @var MAS_Backup_Service
     */
    private $backup_service;
    
    /**
     * Validation service instance
     * 
     * @var MAS_Validation_Service
     */
    private $validation_service;
    
    /**
     * Singleton instance
     * 
     * @var MAS_Import_Export_Service
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     * 
     * @return MAS_Import_Export_Service
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
        $this->backup_service = MAS_Backup_Service::get_instance();
        $this->validation_service = new MAS_Validation_Service();
    }
    
    /**
     * Export current settings as JSON
     * 
     * @param bool $include_metadata Whether to include metadata in export
     * @return array Export data with settings and metadata
     */
    public function export_settings($include_metadata = true) {
        // Get current settings
        $settings = $this->settings_service->get_settings();
        
        // Build export data structure
        $export_data = [
            'settings' => $settings
        ];
        
        // Add metadata if requested
        if ($include_metadata) {
            $export_data['metadata'] = [
                'export_version' => $this->export_version,
                'plugin_version' => defined('MAS_V2_VERSION') ? MAS_V2_VERSION : '2.2.0',
                'wordpress_version' => get_bloginfo('version'),
                'export_date' => current_time('mysql'),
                'export_timestamp' => time(),
                'site_url' => get_site_url(),
                'exported_by' => get_current_user_id(),
            ];
        }
        
        return $export_data;
    }
    
    /**
     * Import settings from JSON data
     * 
     * @param array $import_data Import data containing settings and metadata
     * @param bool $create_backup Whether to create backup before import
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function import_settings($import_data, $create_backup = true) {
        // Validate import data structure
        $validation_result = $this->validate_import_data($import_data);
        if (is_wp_error($validation_result)) {
            return $validation_result;
        }
        
        // Check version compatibility
        $compatibility_check = $this->check_version_compatibility($import_data);
        if (is_wp_error($compatibility_check)) {
            return $compatibility_check;
        }
        
        // Migrate legacy format if needed
        $settings = $this->migrate_legacy_format($import_data);
        
        // Validate settings data
        $settings_validation = $this->validation_service->validate_settings($settings);
        if (!$settings_validation['valid']) {
            return new WP_Error(
                'settings_validation_failed',
                __('Imported settings validation failed', 'modern-admin-styler-v2'),
                ['status' => 400, 'errors' => $settings_validation['errors']]
            );
        }
        
        // Create backup before import if requested
        if ($create_backup) {
            $backup_result = $this->backup_service->create_automatic_backup('Before settings import');
            
            if (is_wp_error($backup_result)) {
                return new WP_Error(
                    'pre_import_backup_failed',
                    __('Failed to create backup before import', 'modern-admin-styler-v2'),
                    ['status' => 500]
                );
            }
        }
        
        // Apply imported settings
        $result = $this->settings_service->save_settings($settings);
        
        if (is_wp_error($result)) {
            return new WP_Error(
                'import_failed',
                __('Failed to import settings', 'modern-admin-styler-v2'),
                ['status' => 500, 'original_error' => $result]
            );
        }
        
        return true;
    }
    
    /**
     * Validate import data structure
     * 
     * @param array $import_data Import data to validate
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    private function validate_import_data($import_data) {
        $errors = [];
        
        // Check if data is an array
        if (!is_array($import_data)) {
            return new WP_Error(
                'invalid_import_format',
                __('Import data must be a valid JSON object', 'modern-admin-styler-v2'),
                ['status' => 400]
            );
        }
        
        // Check for settings key
        if (!isset($import_data['settings'])) {
            $errors[] = __('Import data does not contain settings', 'modern-admin-styler-v2');
        }
        
        // Check if settings is an array
        if (isset($import_data['settings']) && !is_array($import_data['settings'])) {
            $errors[] = __('Settings data must be an object', 'modern-admin-styler-v2');
        }
        
        // Check if settings is not empty
        if (isset($import_data['settings']) && empty($import_data['settings'])) {
            $errors[] = __('Settings data cannot be empty', 'modern-admin-styler-v2');
        }
        
        // Validate metadata if present
        if (isset($import_data['metadata'])) {
            if (!is_array($import_data['metadata'])) {
                $errors[] = __('Metadata must be an object', 'modern-admin-styler-v2');
            }
        }
        
        if (!empty($errors)) {
            return new WP_Error(
                'import_validation_failed',
                __('Import data validation failed', 'modern-admin-styler-v2'),
                ['status' => 400, 'errors' => $errors]
            );
        }
        
        return true;
    }
    
    /**
     * Check version compatibility
     * 
     * @param array $import_data Import data with metadata
     * @return bool|WP_Error True if compatible, WP_Error if incompatible
     */
    private function check_version_compatibility($import_data) {
        // If no metadata, assume compatible (legacy format)
        if (!isset($import_data['metadata'])) {
            return true;
        }
        
        $metadata = $import_data['metadata'];
        
        // Check export version
        if (isset($metadata['export_version'])) {
            $export_version = $metadata['export_version'];
            
            // Check if version is too old
            if (version_compare($export_version, $this->min_import_version, '<')) {
                return new WP_Error(
                    'incompatible_version',
                    sprintf(
                        __('Import file version %s is too old. Minimum supported version is %s', 'modern-admin-styler-v2'),
                        $export_version,
                        $this->min_import_version
                    ),
                    ['status' => 400]
                );
            }
        }
        
        // Check plugin version
        if (isset($metadata['plugin_version'])) {
            $plugin_version = $metadata['plugin_version'];
            
            // Check if version is too old
            if (version_compare($plugin_version, $this->min_import_version, '<')) {
                // Warning but not error - we'll try to migrate
                error_log(sprintf(
                    'MAS Import: Plugin version %s is old, attempting migration',
                    $plugin_version
                ));
            }
        }
        
        return true;
    }
    
    /**
     * Migrate legacy format to current format
     * 
     * @param array $import_data Import data
     * @return array Migrated settings
     */
    private function migrate_legacy_format($import_data) {
        $settings = $import_data['settings'];
        
        // Apply field aliases for backward compatibility
        $settings = $this->validation_service->apply_field_aliases($settings);
        
        // Migrate old boolean formats
        $settings = $this->migrate_boolean_values($settings);
        
        // Migrate old color formats
        $settings = $this->migrate_color_values($settings);
        
        // Migrate old numeric formats
        $settings = $this->migrate_numeric_values($settings);
        
        // Ensure all default keys exist
        $defaults = $this->settings_service->get_defaults();
        $settings = wp_parse_args($settings, $defaults);
        
        return $settings;
    }
    
    /**
     * Migrate boolean values from various formats
     * 
     * @param array $settings Settings array
     * @return array Migrated settings
     */
    private function migrate_boolean_values($settings) {
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
            if (isset($settings[$field])) {
                $settings[$field] = filter_var($settings[$field], FILTER_VALIDATE_BOOLEAN);
            }
        }
        
        return $settings;
    }
    
    /**
     * Migrate color values to standard format
     * 
     * @param array $settings Settings array
     * @return array Migrated settings
     */
    private function migrate_color_values($settings) {
        $color_fields = [
            'menu_background', 'menu_text_color', 'menu_hover_background',
            'menu_hover_text_color', 'menu_active_background', 'menu_active_text_color',
            'admin_bar_background', 'admin_bar_text_color', 'admin_bar_hover_color',
            'submenu_background', 'submenu_text_color', 'submenu_hover_background',
            'submenu_hover_text_color', 'content_background', 'content_card_background',
            'content_text_color', 'content_link_color', 'button_primary_background',
            'button_primary_text_color', 'shadow_color'
        ];
        
        foreach ($color_fields as $field) {
            if (isset($settings[$field]) && !empty($settings[$field])) {
                // Ensure color has # prefix
                if (strpos($settings[$field], '#') !== 0) {
                    $settings[$field] = '#' . $settings[$field];
                }
                
                // Convert 3-digit hex to 6-digit
                if (strlen($settings[$field]) === 4) {
                    $settings[$field] = '#' . 
                        $settings[$field][1] . $settings[$field][1] .
                        $settings[$field][2] . $settings[$field][2] .
                        $settings[$field][3] . $settings[$field][3];
                }
                
                // Sanitize
                $settings[$field] = sanitize_hex_color($settings[$field]);
            }
        }
        
        return $settings;
    }
    
    /**
     * Migrate numeric values to proper types
     * 
     * @param array $settings Settings array
     * @return array Migrated settings
     */
    private function migrate_numeric_values($settings) {
        $numeric_fields = [
            'font_size', 'admin_bar_height', 'admin_bar_font_size', 'admin_bar_padding',
            'admin_bar_border_radius', 'menu_width', 'menu_item_height',
            'button_border_radius', 'animation_speed', 'glassmorphism_blur',
            'shadow_blur', 'global_border_radius'
        ];
        
        foreach ($numeric_fields as $field) {
            if (isset($settings[$field])) {
                // Remove any non-numeric characters except decimal point
                $value = preg_replace('/[^0-9.]/', '', $settings[$field]);
                
                // Convert to appropriate numeric type
                $settings[$field] = strpos($value, '.') !== false ? 
                    floatval($value) : intval($value);
            }
        }
        
        return $settings;
    }
    
    /**
     * Get export filename
     * 
     * @return string Filename for export
     */
    public function get_export_filename() {
        $site_name = sanitize_title(get_bloginfo('name'));
        $timestamp = date('Y-m-d-His');
        
        return sprintf(
            'mas-v2-settings-%s-%s.json',
            $site_name,
            $timestamp
        );
    }
    
    /**
     * Validate JSON string
     * 
     * @param string $json JSON string to validate
     * @return array|WP_Error Decoded data or error
     */
    public function validate_json($json) {
        if (empty($json)) {
            return new WP_Error(
                'empty_json',
                __('JSON data is empty', 'modern-admin-styler-v2'),
                ['status' => 400]
            );
        }
        
        // Attempt to decode JSON
        $data = json_decode($json, true);
        
        // Check for JSON errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error(
                'invalid_json',
                sprintf(
                    __('Invalid JSON: %s', 'modern-admin-styler-v2'),
                    json_last_error_msg()
                ),
                ['status' => 400]
            );
        }
        
        return $data;
    }
    
    /**
     * Get import/export statistics
     * 
     * @return array Statistics
     */
    public function get_statistics() {
        $settings = $this->settings_service->get_settings();
        
        return [
            'total_settings' => count($settings),
            'export_version' => $this->export_version,
            'min_import_version' => $this->min_import_version,
            'estimated_export_size' => strlen(json_encode($this->export_settings())),
        ];
    }
}
