<?php
/**
 * Validation Service for Modern Admin Styler V2
 * 
 * Provides comprehensive validation methods for REST API requests
 * including JSON Schema validation, color validation, CSS unit validation,
 * and field name alias support for backward compatibility.
 *
 * @package ModernAdminStylerV2
 * @subpackage Services
 * @since 2.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Validation Service Class
 * 
 * Handles all validation logic for REST API endpoints
 */
class MAS_Validation_Service {
    
    /**
     * Field name aliases for backward compatibility
     * Maps old field names to new field names
     * 
     * @var array
     */
    private $field_aliases = [
        'menu_bg' => 'menu_background',
        'menu_txt' => 'menu_text_color',
        'menu_hover_bg' => 'menu_hover_background',
        'menu_hover_txt' => 'menu_hover_text_color',
        'menu_active_bg' => 'menu_active_background',
        'menu_active_txt' => 'menu_active_text_color',
        'admin_bar_bg' => 'admin_bar_background',
        'admin_bar_txt' => 'admin_bar_text_color',
        'submenu_bg' => 'submenu_background',
        'submenu_txt' => 'submenu_text_color',
    ];
    
    /**
     * Valid CSS units
     * 
     * @var array
     */
    private $valid_css_units = ['px', 'em', 'rem', '%', 'vh', 'vw'];
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize validation service
    }
    
    /**
     * Validate color value
     * 
     * Supports hex colors (#RGB, #RRGGBB, #RRGGBBAA)
     * 
     * @param string $color Color value to validate
     * @return bool True if valid, false otherwise
     */
    public function validate_color($color) {
        if (empty($color)) {
            return false;
        }
        
        // Check for valid hex color format
        $pattern = '/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/';
        return preg_match($pattern, $color) === 1;
    }
    
    /**
     * Validate CSS unit value
     * 
     * Validates values like "10px", "2em", "50%", etc.
     * 
     * @param string $value CSS value to validate
     * @return bool True if valid, false otherwise
     */
    public function validate_css_unit($value) {
        if (empty($value)) {
            return false;
        }
        
        // Check if value matches pattern: number + unit
        $units_pattern = implode('|', $this->valid_css_units);
        $pattern = '/^[0-9]+(\.[0-9]+)?(' . $units_pattern . ')$/';
        
        return preg_match($pattern, $value) === 1;
    }
    
    /**
     * Validate boolean value
     * 
     * Accepts: true, false, 1, 0, "true", "false", "1", "0"
     * 
     * @param mixed $value Value to validate
     * @return bool True if valid boolean, false otherwise
     */
    public function validate_boolean($value) {
        return is_bool($value) || 
               $value === 1 || 
               $value === 0 || 
               $value === '1' || 
               $value === '0' || 
               $value === 'true' || 
               $value === 'false';
    }
    
    /**
     * Validate array value
     * 
     * @param mixed $value Value to validate
     * @return bool True if valid array, false otherwise
     */
    public function validate_array($value) {
        return is_array($value);
    }
    
    /**
     * Validate numeric value
     * 
     * @param mixed $value Value to validate
     * @param int|null $min Minimum value (optional)
     * @param int|null $max Maximum value (optional)
     * @return bool True if valid, false otherwise
     */
    public function validate_numeric($value, $min = null, $max = null) {
        if (!is_numeric($value)) {
            return false;
        }
        
        $num = (float) $value;
        
        if ($min !== null && $num < $min) {
            return false;
        }
        
        if ($max !== null && $num > $max) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate string value
     * 
     * @param mixed $value Value to validate
     * @param int|null $min_length Minimum length (optional)
     * @param int|null $max_length Maximum length (optional)
     * @return bool True if valid, false otherwise
     */
    public function validate_string($value, $min_length = null, $max_length = null) {
        if (!is_string($value)) {
            return false;
        }
        
        $length = strlen($value);
        
        if ($min_length !== null && $length < $min_length) {
            return false;
        }
        
        if ($max_length !== null && $length > $max_length) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate settings data against schema
     * 
     * @param array $data Settings data to validate
     * @param array $schema Validation schema
     * @return array Array with 'valid' (bool) and 'errors' (array) keys
     */
    public function validate_settings($data, $schema = []) {
        $errors = [];
        
        // Apply field aliases
        $data = $this->apply_field_aliases($data);
        
        // If no schema provided, use default schema
        if (empty($schema)) {
            $schema = $this->get_default_schema();
        }
        
        // Validate each field
        foreach ($schema as $field => $rules) {
            // Skip if field not in data and not required
            if (!isset($data[$field]) && empty($rules['required'])) {
                continue;
            }
            
            // Check required fields
            if (!isset($data[$field]) && !empty($rules['required'])) {
                $errors[$field] = sprintf(
                    __('Field %s is required', 'modern-admin-styler-v2'),
                    $field
                );
                continue;
            }
            
            $value = $data[$field];
            
            // Validate based on type
            if (isset($rules['type'])) {
                $valid = $this->validate_by_type($value, $rules['type'], $rules);
                
                if (!$valid) {
                    $errors[$field] = sprintf(
                        __('Invalid value for %s. Expected %s.', 'modern-admin-styler-v2'),
                        $field,
                        $rules['type']
                    );
                }
            }
            
            // Custom validation callback
            if (isset($rules['validate_callback']) && is_callable($rules['validate_callback'])) {
                $valid = call_user_func($rules['validate_callback'], $value);
                
                if (!$valid) {
                    $errors[$field] = sprintf(
                        __('Validation failed for %s', 'modern-admin-styler-v2'),
                        $field
                    );
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate value by type
     * 
     * @param mixed $value Value to validate
     * @param string $type Expected type
     * @param array $rules Additional validation rules
     * @return bool True if valid, false otherwise
     */
    private function validate_by_type($value, $type, $rules = []) {
        switch ($type) {
            case 'color':
                return $this->validate_color($value);
                
            case 'css_unit':
                return $this->validate_css_unit($value);
                
            case 'boolean':
                return $this->validate_boolean($value);
                
            case 'array':
                return $this->validate_array($value);
                
            case 'number':
            case 'integer':
            case 'float':
                $min = isset($rules['minimum']) ? $rules['minimum'] : null;
                $max = isset($rules['maximum']) ? $rules['maximum'] : null;
                return $this->validate_numeric($value, $min, $max);
                
            case 'string':
                $min_length = isset($rules['minLength']) ? $rules['minLength'] : null;
                $max_length = isset($rules['maxLength']) ? $rules['maxLength'] : null;
                return $this->validate_string($value, $min_length, $max_length);
                
            default:
                return true;
        }
    }
    
    /**
     * Apply field name aliases for backward compatibility
     * 
     * @param array $data Data with potentially old field names
     * @return array Data with new field names
     */
    public function apply_field_aliases($data) {
        $normalized = [];
        
        foreach ($data as $key => $value) {
            // Check if this is an aliased field
            if (isset($this->field_aliases[$key])) {
                $new_key = $this->field_aliases[$key];
                $normalized[$new_key] = $value;
            } else {
                $normalized[$key] = $value;
            }
        }
        
        return $normalized;
    }
    
    /**
     * Get default validation schema for settings
     * 
     * @return array Validation schema
     */
    private function get_default_schema() {
        return [
            // Menu settings
            'menu_background' => [
                'type' => 'color',
                'required' => false
            ],
            'menu_text_color' => [
                'type' => 'color',
                'required' => false
            ],
            'menu_hover_background' => [
                'type' => 'color',
                'required' => false
            ],
            'menu_hover_text_color' => [
                'type' => 'color',
                'required' => false
            ],
            'menu_active_background' => [
                'type' => 'color',
                'required' => false
            ],
            'menu_active_text_color' => [
                'type' => 'color',
                'required' => false
            ],
            'menu_width' => [
                'type' => 'css_unit',
                'required' => false
            ],
            'menu_item_height' => [
                'type' => 'css_unit',
                'required' => false
            ],
            'menu_border_radius' => [
                'type' => 'css_unit',
                'required' => false
            ],
            'menu_detached' => [
                'type' => 'boolean',
                'required' => false
            ],
            
            // Admin bar settings
            'admin_bar_background' => [
                'type' => 'color',
                'required' => false
            ],
            'admin_bar_text_color' => [
                'type' => 'color',
                'required' => false
            ],
            'admin_bar_floating' => [
                'type' => 'boolean',
                'required' => false
            ],
            
            // Submenu settings
            'submenu_background' => [
                'type' => 'color',
                'required' => false
            ],
            'submenu_text_color' => [
                'type' => 'color',
                'required' => false
            ],
            
            // Effects
            'glassmorphism_enabled' => [
                'type' => 'boolean',
                'required' => false
            ],
            'glassmorphism_blur' => [
                'type' => 'css_unit',
                'required' => false
            ],
            'shadow_effects_enabled' => [
                'type' => 'boolean',
                'required' => false
            ],
            'animations_enabled' => [
                'type' => 'boolean',
                'required' => false
            ],
            
            // Advanced
            'performance_mode' => [
                'type' => 'boolean',
                'required' => false
            ],
            'debug_mode' => [
                'type' => 'boolean',
                'required' => false
            ]
        ];
    }
    
    /**
     * Sanitize settings data
     * 
     * @param array $data Data to sanitize
     * @return array Sanitized data
     */
    public function sanitize_settings($data) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize_settings($value);
            } elseif (is_bool($value)) {
                $sanitized[$key] = (bool) $value;
            } elseif (is_numeric($value)) {
                $sanitized[$key] = is_float($value) ? (float) $value : (int) $value;
            } elseif ($this->validate_color($value)) {
                $sanitized[$key] = sanitize_hex_color($value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Add custom field alias
     * 
     * @param string $old_name Old field name
     * @param string $new_name New field name
     * @return void
     */
    public function add_field_alias($old_name, $new_name) {
        $this->field_aliases[$old_name] = $new_name;
    }
    
    /**
     * Get all field aliases
     * 
     * @return array Field aliases
     */
    public function get_field_aliases() {
        return $this->field_aliases;
    }
}
