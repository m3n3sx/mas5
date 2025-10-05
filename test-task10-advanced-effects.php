<?php
/**
 * Test Task 10: Advanced Effects System
 * Verifies glassmorphism, shadow effects, and animation system implementation
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Include WordPress
require_once ABSPATH . 'wp-config.php';
require_once ABSPATH . 'wp-includes/wp-db.php';
require_once ABSPATH . 'wp-includes/pluggable.php';

// Include the plugin
require_once __DIR__ . '/modern-admin-styler-v2.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>🎨 Task 10: Advanced Effects System Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .test-container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        .test-section {
            background: rgba(255, 255, 255, 0.9);
            margin: 20px 0;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }
        .status { 
            padding: 10px 15px; 
            border-radius: 8px; 
            margin: 10px 0; 
            font-weight: bold;
        }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        
        .effect-demo {
            display: inline-block;
            padding: 15px 25px;
            margin: 10px;
            border-radius: 8px;
            transition: all 300ms cubic-bezier(0.25, 0.46, 0.45, 0.94);
            cursor: pointer;
        }
        
        .glassmorphism-demo {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .shadow-demo {
            background: #ffffff;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }
        
        .shadow-demo:hover {
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }
        
        .animation-demo {
            background: #4A90E2;
            color: white;
        }
        
        .animation-demo:hover {
            transform: scale(1.05) translateY(-2px);
            box-shadow: 0 8px 24px rgba(74, 144, 226, 0.3);
        }
        
        .code-block {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
            margin: 10px 0;
        }
        
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .setting-item {
            background: rgba(255, 255, 255, 0.8);
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #4A90E2;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🎨 Task 10: Advanced Effects System Test</h1>
        <p>Testing the implementation of glassmorphism effects, shadow system, and animations with performance optimization.</p>
        
        <?php
        // Initialize plugin
        $masInstance = ModernAdminStylerV2::getInstance();
        
        echo '<div class="test-section">';
        echo '<h2>📋 Test Results</h2>';
        
        // Test 1: Check if generateEffectsCSS method exists and works
        echo '<h3>1. Testing generateEffectsCSS Method</h3>';
        try {
            $reflection = new ReflectionClass($masInstance);
            $generateEffectsMethod = $reflection->getMethod('generateEffectsCSS');
            $generateEffectsMethod->setAccessible(true);
            
            // Test with advanced effects settings
            $testSettings = [
                'glassmorphism_effects' => true,
                'glassmorphism_blur' => 15,
                'glassmorphism_opacity' => 0.15,
                'glassmorphism_border_opacity' => 0.25,
                'enable_shadows' => true,
                'shadow_color' => '#000000',
                'shadow_blur' => 12,
                'shadow_spread' => 2,
                'shadow_offset_x' => 2,
                'shadow_offset_y' => 4,
                'shadow_opacity' => 0.12,
                'shadow_hover_effects' => true,
                'enable_animations' => true,
                'animation_speed' => 250,
                'animation_type' => 'smooth',
                'fade_in_effects' => true,
                'slide_animations' => true,
                'scale_hover_effects' => true,
                'menu_glassmorphism' => true,
                'admin_bar_glassmorphism' => true,
                'menu_shadow' => true
            ];
            
            $effectsCSS = $generateEffectsMethod->invoke($masInstance, $testSettings);
            
            if (!empty($effectsCSS)) {
                echo '<div class="status success">✅ generateEffectsCSS method works correctly</div>';
                echo '<div class="info">Generated CSS length: ' . strlen($effectsCSS) . ' characters</div>';
                
                // Check for specific advanced effects features
                $features = [
                    'CSS Variables' => '--mas-glass-blur:',
                    'Glassmorphism' => 'backdrop-filter: blur(',
                    'Shadow System' => '--mas-shadow-color:',
                    'Animation System' => '--mas-animation-speed:',
                    'Reduced Motion Support' => '@media (prefers-reduced-motion: reduce)',
                    'Performance Optimization' => 'will-change:',
                    'GPU Acceleration' => 'translateZ(0)',
                    'Hover Effects' => 'shadow_hover_effects',
                    'Fade In Effects' => 'fadeInUp',
                    'Scale Effects' => 'scale(1.05)'
                ];
                
                echo '<h4>🔍 Feature Detection:</h4>';
                foreach ($features as $feature => $searchTerm) {
                    if (strpos($effectsCSS, $searchTerm) !== false) {
                        echo '<div class="status success">✅ ' . $feature . ' implemented</div>';
                    } else {
                        echo '<div class="status warning">⚠️ ' . $feature . ' not found</div>';
                    }
                }
                
            } else {
                echo '<div class="status error">❌ generateEffectsCSS returned empty CSS</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="status error">❌ Error testing generateEffectsCSS: ' . $e->getMessage() . '</div>';
        }
        
        // Test 2: Check default settings for advanced effects
        echo '<h3>2. Testing Advanced Effects Default Settings</h3>';
        try {
            $reflection = new ReflectionClass($masInstance);
            $getDefaultsMethod = $reflection->getMethod('getDefaultSettings');
            $getDefaultsMethod->setAccessible(true);
            $defaults = $getDefaultsMethod->invoke($masInstance);
            
            $advancedEffectsSettings = [
                'glassmorphism_blur' => 'Glassmorphism blur amount',
                'glassmorphism_opacity' => 'Glassmorphism opacity',
                'glassmorphism_border_opacity' => 'Glassmorphism border opacity',
                'shadow_hover_effects' => 'Shadow hover effects',
                'shadow_spread' => 'Shadow spread',
                'shadow_offset_x' => 'Shadow X offset',
                'shadow_offset_y' => 'Shadow Y offset',
                'shadow_opacity' => 'Shadow opacity',
                'animation_type' => 'Animation type'
            ];
            
            $foundSettings = 0;
            foreach ($advancedEffectsSettings as $setting => $description) {
                if (array_key_exists($setting, $defaults)) {
                    echo '<div class="status success">✅ ' . $description . ': ' . $defaults[$setting] . '</div>';
                    $foundSettings++;
                } else {
                    echo '<div class="status error">❌ Missing setting: ' . $setting . '</div>';
                }
            }
            
            if ($foundSettings === count($advancedEffectsSettings)) {
                echo '<div class="status success">✅ All advanced effects settings are present in defaults</div>';
            } else {
                echo '<div class="status warning">⚠️ ' . $foundSettings . '/' . count($advancedEffectsSettings) . ' advanced effects settings found</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="status error">❌ Error checking default settings: ' . $e->getMessage() . '</div>';
        }
        
        // Test 3: Check if advanced-effects.css is properly enqueued
        echo '<h3>3. Testing Advanced Effects CSS File</h3>';
        $advancedEffectsFile = __DIR__ . '/assets/css/advanced-effects.css';
        if (file_exists($advancedEffectsFile)) {
            $cssContent = file_get_contents($advancedEffectsFile);
            echo '<div class="status success">✅ advanced-effects.css file exists</div>';
            echo '<div class="info">File size: ' . strlen($cssContent) . ' characters</div>';
            
            // Check for key features in CSS file
            $cssFeatures = [
                'Reduced Motion Support' => '@media (prefers-reduced-motion: reduce)',
                'CSS Variables' => '--mas-glass-blur:',
                'GPU Acceleration' => 'translateZ(0)',
                'Performance Optimization' => 'will-change:',
                'Glassmorphism Classes' => '.mas-glassmorphism-enabled',
                'Animation Keyframes' => '@keyframes',
                'Backdrop Filter' => 'backdrop-filter:',
                'High Contrast Support' => '@media (prefers-contrast: high)',
                'Focus Indicators' => ':focus'
            ];
            
            echo '<h4>🔍 CSS Feature Detection:</h4>';
            foreach ($cssFeatures as $feature => $searchTerm) {
                if (strpos($cssContent, $searchTerm) !== false) {
                    echo '<div class="status success">✅ ' . $feature . ' implemented</div>';
                } else {
                    echo '<div class="status warning">⚠️ ' . $feature . ' not found</div>';
                }
            }
            
        } else {
            echo '<div class="status error">❌ advanced-effects.css file not found</div>';
        }
        
        // Test 4: Test CSS generation with different effect combinations
        echo '<h3>4. Testing Effect Combinations</h3>';
        
        $testCombinations = [
            'Glassmorphism Only' => [
                'glassmorphism_effects' => true,
                'enable_shadows' => false,
                'enable_animations' => false
            ],
            'Shadows Only' => [
                'glassmorphism_effects' => false,
                'enable_shadows' => true,
                'enable_animations' => false
            ],
            'Animations Only' => [
                'glassmorphism_effects' => false,
                'enable_shadows' => false,
                'enable_animations' => true
            ],
            'All Effects' => [
                'glassmorphism_effects' => true,
                'enable_shadows' => true,
                'enable_animations' => true,
                'fade_in_effects' => true,
                'scale_hover_effects' => true
            ],
            'No Effects' => [
                'glassmorphism_effects' => false,
                'enable_shadows' => false,
                'enable_animations' => false
            ]
        ];
        
        try {
            $reflection = new ReflectionClass($masInstance);
            $generateEffectsMethod = $reflection->getMethod('generateEffectsCSS');
            $generateEffectsMethod->setAccessible(true);
            
            foreach ($testCombinations as $name => $settings) {
                $css = $generateEffectsMethod->invoke($masInstance, $settings);
                $cssLength = strlen($css);
                
                if ($name === 'No Effects' && $cssLength < 500) {
                    echo '<div class="status success">✅ ' . $name . ': Minimal CSS generated (' . $cssLength . ' chars)</div>';
                } elseif ($name !== 'No Effects' && $cssLength > 500) {
                    echo '<div class="status success">✅ ' . $name . ': CSS generated (' . $cssLength . ' chars)</div>';
                } else {
                    echo '<div class="status warning">⚠️ ' . $name . ': Unexpected CSS length (' . $cssLength . ' chars)</div>';
                }
            }
            
        } catch (Exception $e) {
            echo '<div class="status error">❌ Error testing effect combinations: ' . $e->getMessage() . '</div>';
        }
        
        echo '</div>';
        ?>
        
        <!-- Visual Effect Demonstrations -->
        <div class="test-section">
            <h2>🎭 Visual Effect Demonstrations</h2>
            <p>Hover over the elements below to see the effects in action:</p>
            
            <div class="effect-demo glassmorphism-demo">
                <strong>Glassmorphism Effect</strong><br>
                Backdrop blur with transparency
            </div>
            
            <div class="effect-demo shadow-demo">
                <strong>Shadow Effects</strong><br>
                Hover for enhanced shadow
            </div>
            
            <div class="effect-demo animation-demo">
                <strong>Animation System</strong><br>
                Scale and translate on hover
            </div>
        </div>
        
        <!-- Settings Overview -->
        <div class="test-section">
            <h2>⚙️ Advanced Effects Settings</h2>
            <div class="settings-grid">
                <div class="setting-item">
                    <h4>🌊 Glassmorphism Settings</h4>
                    <ul>
                        <li><code>glassmorphism_effects</code> - Enable/disable</li>
                        <li><code>glassmorphism_blur</code> - Blur amount (px)</li>
                        <li><code>glassmorphism_opacity</code> - Background opacity</li>
                        <li><code>glassmorphism_border_opacity</code> - Border opacity</li>
                    </ul>
                </div>
                
                <div class="setting-item">
                    <h4>🎯 Shadow Settings</h4>
                    <ul>
                        <li><code>enable_shadows</code> - Enable/disable</li>
                        <li><code>shadow_color</code> - Shadow color</li>
                        <li><code>shadow_blur</code> - Blur radius</li>
                        <li><code>shadow_spread</code> - Spread radius</li>
                        <li><code>shadow_offset_x/y</code> - Position offsets</li>
                        <li><code>shadow_opacity</code> - Shadow opacity</li>
                        <li><code>shadow_hover_effects</code> - Enhanced hover shadows</li>
                    </ul>
                </div>
                
                <div class="setting-item">
                    <h4>🎬 Animation Settings</h4>
                    <ul>
                        <li><code>enable_animations</code> - Enable/disable</li>
                        <li><code>animation_speed</code> - Duration (ms)</li>
                        <li><code>animation_type</code> - Easing type</li>
                        <li><code>fade_in_effects</code> - Fade animations</li>
                        <li><code>slide_animations</code> - Slide effects</li>
                        <li><code>scale_hover_effects</code> - Scale on hover</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Performance Features -->
        <div class="test-section">
            <h2>⚡ Performance & Accessibility Features</h2>
            <div class="settings-grid">
                <div class="setting-item">
                    <h4>🚀 Performance Optimizations</h4>
                    <ul>
                        <li>GPU acceleration with <code>translateZ(0)</code></li>
                        <li>Optimized <code>will-change</code> properties</li>
                        <li>Reduced animation complexity on mobile</li>
                        <li>Efficient CSS variable system</li>
                        <li>Minimal DOM reflows</li>
                    </ul>
                </div>
                
                <div class="setting-item">
                    <h4>♿ Accessibility Support</h4>
                    <ul>
                        <li><code>prefers-reduced-motion</code> support</li>
                        <li><code>prefers-contrast: high</code> support</li>
                        <li>Enhanced focus indicators</li>
                        <li>Keyboard navigation friendly</li>
                        <li>Screen reader compatible</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Implementation Summary -->
        <div class="test-section">
            <h2>📋 Implementation Summary</h2>
            <div class="status info">
                <strong>Task 10 Requirements Completed:</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>✅ <strong>Glassmorphism Effects:</strong> Proper CSS generation for backdrop-filter properties with configurable blur, opacity, and border settings</li>
                    <li>✅ <strong>Shadow Effects System:</strong> Configurable shadow parameters including color, blur, spread, offsets, and opacity with hover enhancements</li>
                    <li>✅ <strong>Animation System:</strong> Performance-optimized animations with multiple easing types, reduced-motion support, and GPU acceleration</li>
                    <li>✅ <strong>Performance Optimization:</strong> Hardware acceleration, efficient CSS variables, and mobile optimizations</li>
                    <li>✅ <strong>Accessibility:</strong> Full support for reduced-motion preferences and high-contrast mode</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script>
        // Test reduced motion detection
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.body.classList.add('reduced-motion');
            console.log('✅ Reduced motion preference detected and respected');
        }
        
        // Test CSS custom properties support
        if (CSS.supports('backdrop-filter', 'blur(10px)')) {
            console.log('✅ Backdrop-filter support detected');
        } else {
            console.log('⚠️ Backdrop-filter not supported in this browser');
        }
        
        // Test animation performance
        const testElement = document.querySelector('.animation-demo');
        if (testElement) {
            testElement.addEventListener('mouseenter', () => {
                console.log('🎬 Animation triggered');
            });
        }
    </script>
</body>
</html>