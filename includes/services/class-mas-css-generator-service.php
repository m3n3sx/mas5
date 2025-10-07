<?php
/**
 * CSS Generator Service Class
 * 
 * Handles CSS generation from settings with caching support.
 * Generates CSS for all styling options including colors, effects, and animations.
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
 * MAS CSS Generator Service
 * 
 * Provides centralized CSS generation with caching and optimization.
 */
class MAS_CSS_Generator_Service {
    
    /**
     * Cache group for WordPress object cache
     * 
     * @var string
     */
    private $cache_group = 'mas_v2_css';
    
    /**
     * Cache expiration time in seconds (1 hour)
     * 
     * @var int
     */
    private $cache_expiration = 3600;
    
    /**
     * Singleton instance
     * 
     * @var MAS_CSS_Generator_Service
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     * 
     * @return MAS_CSS_Generator_Service
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
        // Constructor intentionally left empty
    }
    
    /**
     * Generate CSS from settings
     * 
     * @param array $settings Settings array
     * @param bool $use_cache Whether to use cached CSS (default: true)
     * @return string Generated CSS
     */
    public function generate($settings, $use_cache = true) {
        // Generate cache key based on settings
        $cache_key = 'generated_css_' . md5(serialize($settings));
        
        // Try cache first if enabled
        if ($use_cache) {
            $cached = wp_cache_get($cache_key, $this->cache_group);
            if ($cached !== false) {
                return $cached;
            }
        }
        
        // Generate CSS
        $css = $this->build_css($settings);
        
        // Cache the result
        wp_cache_set($cache_key, $css, $this->cache_group, $this->cache_expiration);
        
        return $css;
    }
    
    /**
     * Build CSS from settings
     * 
     * @param array $settings Settings array
     * @return string Generated CSS
     */
    private function build_css($settings) {
        $css = "/* Modern Admin Styler V2 - Generated CSS */\n\n";
        
        // Generate each section
        $css .= $this->generate_admin_bar_css($settings);
        $css .= $this->generate_menu_css($settings);
        $css .= $this->generate_submenu_css($settings);
        $css .= $this->generate_content_css($settings);
        $css .= $this->generate_button_css($settings);
        $css .= $this->generate_effects_css($settings);
        $css .= $this->generate_animations_css($settings);
        
        // Add custom CSS if provided
        if (!empty($settings['custom_css'])) {
            $css .= "\n/* Custom CSS */\n";
            $css .= $settings['custom_css'] . "\n";
        }
        
        return $css;
    }
    
    /**
     * Generate Admin Bar CSS
     * 
     * @param array $settings Settings array
     * @return string CSS for admin bar
     */
    private function generate_admin_bar_css($settings) {
        $css = "/* Admin Bar Styles */\n";
        
        if (!empty($settings['custom_admin_bar_style'])) {
            $bg = $this->get_setting($settings, ['admin_bar_background', 'admin_bar_bg'], '#23282d');
            $text_color = $this->get_setting($settings, 'admin_bar_text_color', '#ffffff');
            $hover_color = $this->get_setting($settings, 'admin_bar_hover_color', '#00a0d2');
            $height = $this->get_setting($settings, 'admin_bar_height', 32);
            $font_size = $this->get_setting($settings, 'admin_bar_font_size', 13);
            
            $css .= "#wpadminbar {\n";
            $css .= "    background: {$bg} !important;\n";
            $css .= "    height: {$height}px !important;\n";
            $css .= "}\n\n";
            
            $css .= "#wpadminbar * {\n";
            $css .= "    color: {$text_color} !important;\n";
            $css .= "    font-size: {$font_size}px !important;\n";
            $css .= "}\n\n";
            
            $css .= "#wpadminbar .ab-item:hover,\n";
            $css .= "#wpadminbar .ab-item:focus {\n";
            $css .= "    color: {$hover_color} !important;\n";
            $css .= "}\n\n";
            
            // Floating/Detached styles
            if (!empty($settings['admin_bar_floating']) || !empty($settings['admin_bar_detached'])) {
                $css .= "#wpadminbar {\n";
                $css .= "    position: fixed !important;\n";
                $css .= "    top: 10px !important;\n";
                $css .= "    left: 10px !important;\n";
                $css .= "    right: 10px !important;\n";
                $css .= "    border-radius: 8px !important;\n";
                $css .= "    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;\n";
                $css .= "}\n\n";
            }
            
            // Glassmorphism effect
            if (!empty($settings['admin_bar_glassmorphism'])) {
                $css .= "#wpadminbar {\n";
                $css .= "    backdrop-filter: blur(10px) !important;\n";
                $css .= "    background: rgba(35, 40, 45, 0.8) !important;\n";
                $css .= "}\n\n";
            }
        }
        
        return $css;
    }
    
    /**
     * Generate Menu CSS
     * 
     * @param array $settings Settings array
     * @return string CSS for menu
     */
    private function generate_menu_css($settings) {
        $css = "/* Menu Styles */\n";
        
        $bg = $this->get_setting($settings, ['menu_background', 'menu_bg'], '#23282d');
        $text_color = $this->get_setting($settings, 'menu_text_color', '#ffffff');
        $hover_bg = $this->get_setting($settings, ['menu_hover_background', 'menu_hover_color'], '#32373c');
        $hover_text = $this->get_setting($settings, 'menu_hover_text_color', '#00a0d2');
        $active_bg = $this->get_setting($settings, 'menu_active_background', '#0073aa');
        $active_text = $this->get_setting($settings, 'menu_active_text_color', '#ffffff');
        $width = $this->get_setting($settings, 'menu_width', 160);
        $item_height = $this->get_setting($settings, 'menu_item_height', 34);
        
        $css .= "#adminmenu,\n";
        $css .= "#adminmenu .wp-submenu {\n";
        $css .= "    background: {$bg} !important;\n";
        $css .= "}\n\n";
        
        $css .= "#adminmenuwrap {\n";
        $css .= "    width: {$width}px !important;\n";
        $css .= "}\n\n";
        
        $css .= "#adminmenu a {\n";
        $css .= "    color: {$text_color} !important;\n";
        $css .= "}\n\n";
        
        $css .= "#adminmenu .wp-menu-name {\n";
        $css .= "    line-height: {$item_height}px !important;\n";
        $css .= "}\n\n";
        
        $css .= "#adminmenu li.menu-top:hover,\n";
        $css .= "#adminmenu li.opensub > a.menu-top,\n";
        $css .= "#adminmenu li > a.menu-top:focus {\n";
        $css .= "    background: {$hover_bg} !important;\n";
        $css .= "    color: {$hover_text} !important;\n";
        $css .= "}\n\n";
        
        $css .= "#adminmenu .wp-has-current-submenu .wp-submenu,\n";
        $css .= "#adminmenu .wp-menu-arrow,\n";
        $css .= "#adminmenu .wp-has-current-submenu .wp-submenu.sub-open,\n";
        $css .= "#adminmenu .wp-has-current-submenu.opensub .wp-submenu,\n";
        $css .= "#adminmenu a.wp-has-current-submenu:focus + .wp-submenu {\n";
        $css .= "    background: {$active_bg} !important;\n";
        $css .= "}\n\n";
        
        $css .= "#adminmenu li.current a.menu-top,\n";
        $css .= "#adminmenu li.wp-has-current-submenu a.wp-has-current-submenu {\n";
        $css .= "    background: {$active_bg} !important;\n";
        $css .= "    color: {$active_text} !important;\n";
        $css .= "}\n\n";
        
        // Rounded corners
        if (!empty($settings['menu_rounded_corners'])) {
            $border_radius = $this->get_setting($settings, 'global_border_radius', 8);
            $css .= "#adminmenu li.menu-top {\n";
            $css .= "    border-radius: {$border_radius}px !important;\n";
            $css .= "    margin: 4px 8px !important;\n";
            $css .= "}\n\n";
        }
        
        // Shadow effects
        if (!empty($settings['menu_shadow'])) {
            $css .= "#adminmenu li.menu-top {\n";
            $css .= "    box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;\n";
            $css .= "}\n\n";
        }
        
        // Floating/Detached menu
        if (!empty($settings['menu_floating']) || !empty($settings['menu_detached'])) {
            $css .= "#adminmenuwrap {\n";
            $css .= "    position: fixed !important;\n";
            $css .= "    top: 60px !important;\n";
            $css .= "    left: 10px !important;\n";
            $css .= "    bottom: 10px !important;\n";
            $css .= "    border-radius: 12px !important;\n";
            $css .= "    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;\n";
            $css .= "    overflow: hidden !important;\n";
            $css .= "}\n\n";
        }
        
        // Glassmorphism effect
        if (!empty($settings['menu_glassmorphism'])) {
            $css .= "#adminmenu {\n";
            $css .= "    backdrop-filter: blur(10px) !important;\n";
            $css .= "    background: rgba(35, 40, 45, 0.8) !important;\n";
            $css .= "}\n\n";
        }
        
        return $css;
    }
    
    /**
     * Generate Submenu CSS
     * 
     * @param array $settings Settings array
     * @return string CSS for submenu
     */
    private function generate_submenu_css($settings) {
        $css = "/* Submenu Styles */\n";
        
        $bg = $this->get_setting($settings, 'submenu_background', '#2c3338');
        $text_color = $this->get_setting($settings, 'submenu_text_color', '#ffffff');
        $hover_bg = $this->get_setting($settings, 'submenu_hover_background', '#32373c');
        $hover_text = $this->get_setting($settings, 'submenu_hover_text_color', '#00a0d2');
        
        $css .= "#adminmenu .wp-submenu {\n";
        $css .= "    background: {$bg} !important;\n";
        $css .= "}\n\n";
        
        $css .= "#adminmenu .wp-submenu a {\n";
        $css .= "    color: {$text_color} !important;\n";
        $css .= "}\n\n";
        
        $css .= "#adminmenu .wp-submenu a:hover,\n";
        $css .= "#adminmenu .wp-submenu a:focus {\n";
        $css .= "    background: {$hover_bg} !important;\n";
        $css .= "    color: {$hover_text} !important;\n";
        $css .= "}\n\n";
        
        return $css;
    }
    
    /**
     * Generate Content CSS
     * 
     * @param array $settings Settings array
     * @return string CSS for content area
     */
    private function generate_content_css($settings) {
        $css = "/* Content Area Styles */\n";
        
        if (!empty($settings['content_background'])) {
            $css .= "#wpbody-content {\n";
            $css .= "    background: {$settings['content_background']} !important;\n";
            $css .= "}\n\n";
        }
        
        if (!empty($settings['content_card_background'])) {
            $css .= ".postbox,\n";
            $css .= ".wrap > .card {\n";
            $css .= "    background: {$settings['content_card_background']} !important;\n";
            $css .= "}\n\n";
        }
        
        if (!empty($settings['content_text_color'])) {
            $css .= "#wpbody-content {\n";
            $css .= "    color: {$settings['content_text_color']} !important;\n";
            $css .= "}\n\n";
        }
        
        if (!empty($settings['content_link_color'])) {
            $css .= "#wpbody-content a {\n";
            $css .= "    color: {$settings['content_link_color']} !important;\n";
            $css .= "}\n\n";
        }
        
        return $css;
    }
    
    /**
     * Generate Button CSS
     * 
     * @param array $settings Settings array
     * @return string CSS for buttons
     */
    private function generate_button_css($settings) {
        $css = "/* Button Styles */\n";
        
        if (!empty($settings['button_primary_background'])) {
            $css .= ".button-primary {\n";
            $css .= "    background: {$settings['button_primary_background']} !important;\n";
            $css .= "    border-color: {$settings['button_primary_background']} !important;\n";
            $css .= "}\n\n";
        }
        
        if (!empty($settings['button_primary_text_color'])) {
            $css .= ".button-primary {\n";
            $css .= "    color: {$settings['button_primary_text_color']} !important;\n";
            $css .= "}\n\n";
        }
        
        if (!empty($settings['button_border_radius'])) {
            $css .= ".button,\n";
            $css .= ".button-primary,\n";
            $css .= ".button-secondary {\n";
            $css .= "    border-radius: {$settings['button_border_radius']}px !important;\n";
            $css .= "}\n\n";
        }
        
        return $css;
    }
    
    /**
     * Generate Effects CSS (glassmorphism, shadows)
     * 
     * @param array $settings Settings array
     * @return string CSS for effects
     */
    private function generate_effects_css($settings) {
        $css = "/* Visual Effects */\n";
        
        // Glassmorphism effects
        if (!empty($settings['glassmorphism_effects'])) {
            $blur = $this->get_setting($settings, 'glassmorphism_blur', 10);
            
            $css .= ".postbox,\n";
            $css .= ".wrap > .card {\n";
            $css .= "    backdrop-filter: blur({$blur}px) !important;\n";
            $css .= "    background: rgba(255, 255, 255, 0.8) !important;\n";
            $css .= "}\n\n";
        }
        
        // Shadow effects
        if (!empty($settings['enable_shadows'])) {
            $shadow_color = $this->get_setting($settings, 'shadow_color', '#000000');
            $shadow_blur = $this->get_setting($settings, 'shadow_blur', 10);
            
            // Convert hex to rgba
            $rgba = $this->hex_to_rgba($shadow_color, 0.15);
            
            $css .= ".postbox,\n";
            $css .= ".wrap > .card,\n";
            $css .= "#adminmenu li.menu-top {\n";
            $css .= "    box-shadow: 0 2px {$shadow_blur}px {$rgba} !important;\n";
            $css .= "}\n\n";
        }
        
        return $css;
    }
    
    /**
     * Generate Animations CSS
     * 
     * @param array $settings Settings array
     * @return string CSS for animations
     */
    private function generate_animations_css($settings) {
        $css = "/* Animations */\n";
        
        if (!empty($settings['enable_animations'])) {
            $speed = $this->get_setting($settings, 'animation_speed', 300);
            $type = $this->get_setting($settings, 'animation_type', 'smooth');
            
            // Determine easing function
            $easing = 'ease';
            if ($type === 'smooth') {
                $easing = 'cubic-bezier(0.4, 0.0, 0.2, 1)';
            } elseif ($type === 'bounce') {
                $easing = 'cubic-bezier(0.68, -0.55, 0.265, 1.55)';
            }
            
            $css .= "#adminmenu li.menu-top,\n";
            $css .= "#adminmenu a,\n";
            $css .= ".button,\n";
            $css .= ".postbox {\n";
            $css .= "    transition: all {$speed}ms {$easing} !important;\n";
            $css .= "}\n\n";
            
            // Hover animations
            $css .= "#adminmenu li.menu-top:hover {\n";
            $css .= "    transform: translateX(4px) !important;\n";
            $css .= "}\n\n";
            
            $css .= ".button:hover {\n";
            $css .= "    transform: translateY(-2px) !important;\n";
            $css .= "}\n\n";
        }
        
        // Respect reduced motion preference
        $css .= "@media (prefers-reduced-motion: reduce) {\n";
        $css .= "    * {\n";
        $css .= "        animation: none !important;\n";
        $css .= "        transition: none !important;\n";
        $css .= "    }\n";
        $css .= "}\n\n";
        
        return $css;
    }
    
    /**
     * Get setting value with fallback support for aliases
     * 
     * @param array $settings Settings array
     * @param string|array $keys Setting key(s) to check
     * @param mixed $default Default value
     * @return mixed Setting value or default
     */
    private function get_setting($settings, $keys, $default = null) {
        // Handle single key
        if (is_string($keys)) {
            return isset($settings[$keys]) ? $settings[$keys] : $default;
        }
        
        // Handle multiple keys (aliases)
        if (is_array($keys)) {
            foreach ($keys as $key) {
                if (isset($settings[$key])) {
                    return $settings[$key];
                }
            }
        }
        
        return $default;
    }
    
    /**
     * Convert hex color to rgba
     * 
     * @param string $hex Hex color code
     * @param float $alpha Alpha value (0-1)
     * @return string RGBA color string
     */
    private function hex_to_rgba($hex, $alpha = 1.0) {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        return "rgba({$r}, {$g}, {$b}, {$alpha})";
    }
    
    /**
     * Clear CSS cache
     * 
     * @return void
     */
    public function clear_cache() {
        // Clear all cached CSS
        wp_cache_flush_group($this->cache_group);
        
        // Also clear transients
        delete_transient('mas_v2_generated_css');
    }
}
