<?php
/**
 * Feature Flags Service
 * 
 * Manages feature flags for gradual rollout and A/B testing.
 * Controls which frontend system is active (new vs old).
 * 
 * @package ModernAdminStylerV2
 * @subpackage Services
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class MAS_Feature_Flags_Service {
    
    /**
     * Option name for feature flags
     */
    const OPTION_NAME = 'mas_v2_feature_flags';
    
    /**
     * Default feature flags
     */
    private $defaults = [
        'use_new_frontend' => false,          // Use new Phase 3 frontend architecture
        'enable_live_preview' => true,        // Enable live preview functionality
        'enable_advanced_effects' => true,    // Enable advanced visual effects
        'enable_theme_presets' => true,       // Enable theme preset system
        'enable_backup_system' => true,       // Enable backup/restore system
        'enable_diagnostics' => true,         // Enable diagnostics tools
        'enable_analytics' => false,          // Enable analytics tracking
        'enable_webhooks' => false,           // Enable webhook system
        'debug_mode' => false,                // Enable debug logging
        'performance_mode' => false,          // Enable performance optimizations
    ];
    
    /**
     * Cached flags
     */
    private $flags = null;
    
    /**
     * Get instance (singleton)
     */
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_flags();
    }
    
    /**
     * Load feature flags from database
     */
    private function load_flags() {
        $saved_flags = get_option(self::OPTION_NAME, []);
        $this->flags = wp_parse_args($saved_flags, $this->defaults);
    }
    
    /**
     * Get all feature flags
     * 
     * @return array Feature flags
     */
    public function get_all_flags() {
        if ($this->flags === null) {
            $this->load_flags();
        }
        return $this->flags;
    }
    
    /**
     * Check if a feature is enabled
     * 
     * @param string $flag_name Feature flag name
     * @return bool Whether feature is enabled
     */
    public function is_enabled($flag_name) {
        if ($this->flags === null) {
            $this->load_flags();
        }
        
        // Check if flag exists
        if (!isset($this->flags[$flag_name])) {
            return false;
        }
        
        // Allow override via constant (for testing)
        $constant_name = 'MAS_V2_' . strtoupper($flag_name);
        if (defined($constant_name)) {
            return (bool) constant($constant_name);
        }
        
        // Allow override via query parameter (for admins only)
        if (current_user_can('manage_options') && isset($_GET['mas_' . $flag_name])) {
            return (bool) $_GET['mas_' . $flag_name];
        }
        
        return (bool) $this->flags[$flag_name];
    }
    
    /**
     * Enable a feature flag
     * 
     * @param string $flag_name Feature flag name
     * @return bool Success
     */
    public function enable($flag_name) {
        return $this->set_flag($flag_name, true);
    }
    
    /**
     * Disable a feature flag
     * 
     * @param string $flag_name Feature flag name
     * @return bool Success
     */
    public function disable($flag_name) {
        return $this->set_flag($flag_name, false);
    }
    
    /**
     * Set a feature flag value
     * 
     * @param string $flag_name Feature flag name
     * @param bool $value Flag value
     * @return bool Success
     */
    public function set_flag($flag_name, $value) {
        if ($this->flags === null) {
            $this->load_flags();
        }
        
        // Update in memory
        $this->flags[$flag_name] = (bool) $value;
        
        // Save to database
        $result = update_option(self::OPTION_NAME, $this->flags);
        
        // Clear cache
        wp_cache_delete(self::OPTION_NAME, 'options');
        
        // Log change
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'MAS V2: Feature flag "%s" %s',
                $flag_name,
                $value ? 'enabled' : 'disabled'
            ));
        }
        
        return $result;
    }
    
    /**
     * Reset all flags to defaults
     * 
     * @return bool Success
     */
    public function reset_to_defaults() {
        $this->flags = $this->defaults;
        return update_option(self::OPTION_NAME, $this->defaults);
    }
    
    /**
     * Get flag description
     * 
     * @param string $flag_name Feature flag name
     * @return string Description
     */
    public function get_flag_description($flag_name) {
        $descriptions = [
            'use_new_frontend' => __('Use new Phase 3 frontend architecture with unified component system', 'modern-admin-styler-v2'),
            'enable_live_preview' => __('Enable real-time preview of styling changes', 'modern-admin-styler-v2'),
            'enable_advanced_effects' => __('Enable advanced visual effects (glassmorphism, shadows, animations)', 'modern-admin-styler-v2'),
            'enable_theme_presets' => __('Enable theme preset management system', 'modern-admin-styler-v2'),
            'enable_backup_system' => __('Enable backup and restore functionality', 'modern-admin-styler-v2'),
            'enable_diagnostics' => __('Enable system diagnostics and health checks', 'modern-admin-styler-v2'),
            'enable_analytics' => __('Enable analytics tracking for usage insights', 'modern-admin-styler-v2'),
            'enable_webhooks' => __('Enable webhook notifications for events', 'modern-admin-styler-v2'),
            'debug_mode' => __('Enable debug logging and verbose error messages', 'modern-admin-styler-v2'),
            'performance_mode' => __('Enable performance optimizations (may reduce visual effects)', 'modern-admin-styler-v2'),
        ];
        
        return isset($descriptions[$flag_name]) ? $descriptions[$flag_name] : '';
    }
    
    /**
     * Check if new frontend should be used
     * 
     * ⚠️ EMERGENCY OVERRIDE: Always returns false until Phase 3 is fixed
     * 
     * Phase 3 frontend has critical issues:
     * - Broken dependencies (EventBus, StateManager, APIClient)
     * - Handler conflicts causing settings save failures
     * - Live preview not functioning
     * 
     * @return bool Always false during emergency stabilization
     */
    public function use_new_frontend() {
        // EMERGENCY STABILIZATION: Phase 3 frontend has broken dependencies
        // Force Phase 2 mode until proper fix is implemented
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MAS V2: Emergency mode active - Phase 3 frontend disabled');
        }
        
        return false;
    }
    
    /**
     * Check if emergency mode is active
     * 
     * Emergency mode disables Phase 3 frontend due to critical issues.
     * 
     * @return bool Always true during emergency stabilization
     */
    public function is_emergency_mode() {
        return true; // Hardcoded during emergency stabilization
    }
    
    /**
     * Get frontend mode (new or legacy)
     * 
     * @return string 'new' or 'legacy'
     */
    public function get_frontend_mode() {
        return $this->use_new_frontend() ? 'new' : 'legacy';
    }
    
    /**
     * Export flags for JavaScript
     * 
     * @return array Flags safe for JavaScript
     */
    public function export_for_js() {
        return [
            'useNewFrontend' => false, // Hardcoded false during emergency mode
            'enableLivePreview' => $this->is_enabled('enable_live_preview'),
            'enableAdvancedEffects' => $this->is_enabled('enable_advanced_effects'),
            'debugMode' => $this->is_enabled('debug_mode'),
            'performanceMode' => $this->is_enabled('performance_mode'),
            'frontendMode' => 'phase2-stable', // Explicit Phase 2 mode
            'emergencyMode' => true, // Emergency stabilization active
            'phase3Disabled' => true, // Phase 3 explicitly disabled
            'frontendVersion' => 'phase2-stable', // Version indicator
        ];
    }
}
