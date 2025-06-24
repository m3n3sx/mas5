<?php
/**
 * Test WOW Effects - Modern Admin Styler V2
 * Sprawdza czy wszystkie efekty działają poprawnie
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Only accessible to admins
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>🎨 WOW Effects Test - Modern Admin Styler V2</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 100vh;
        }
        .test-container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }
        .test-section {
            margin: 20px 0;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .status-ok { color: #10b981; }
        .status-error { color: #ef4444; }
        .test-button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            margin: 5px;
            transition: transform 0.2s;
        }
        .test-button:hover {
            transform: scale(1.05);
        }
        pre {
            background: rgba(0, 0, 0, 0.3);
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🎨 WOW Effects Test Page</h1>
        <p>Sprawdź czy wszystkie nowe efekty Modern Admin Styler V2 działają poprawnie!</p>
        
        <div class="test-section">
            <h2>📦 1. Status Plików</h2>
            <?php
            $plugin_dir = plugin_dir_path(__FILE__);
            $required_files = [
                'assets/css/advanced-effects.css' => '🌊 Advanced Effects CSS',
                'assets/css/color-palettes.css' => '🎨 Color Palettes CSS',
                'assets/css/palette-switcher.css' => '🎯 Palette Switcher CSS',
                'assets/js/modules/PaletteManager.js' => '🎨 Palette Manager JS'
            ];
            
            foreach ($required_files as $file => $name) {
                $exists = file_exists($plugin_dir . $file);
                $status = $exists ? '<span class="status-ok">✅ OK</span>' : '<span class="status-error">❌ MISSING</span>';
                echo "<div>{$name}: {$status}</div>";
            }
            ?>
        </div>
        
        <div class="test-section">
            <h2>⚡ 2. JavaScript API Test</h2>
            <button class="test-button" onclick="testModernAdminApp()">Test ModernAdminApp</button>
            <button class="test-button" onclick="testPaletteManager()">Test PaletteManager</button>
            <button class="test-button" onclick="testKeyboardShortcuts()">Test Keyboard Shortcuts</button>
            <div id="js-results"></div>
        </div>
        
        <div class="test-section">
            <h2>🎨 3. Palette Quick Switch</h2>
            <p>Kliknij aby przetestować palety:</p>
            <button class="test-button" onclick="switchPalette('professional-blue')">🌊 Professional Blue</button>
            <button class="test-button" onclick="switchPalette('creative-purple')">💜 Creative Purple</button>
            <button class="test-button" onclick="switchPalette('electric-cyber')">⚡ Cyber Electric</button>
            <button class="test-button" onclick="switchPalette('gaming-neon')">🎮 Gaming Neon</button>
        </div>
        
        <div class="test-section">
            <h2>⌨️ 4. Keyboard Shortcuts Guide</h2>
            <pre>
Ctrl+Shift+1 → 🌊 Professional Blue
Ctrl+Shift+2 → 💜 Creative Purple  
Ctrl+Shift+3 → 🌿 Energetic Green
Ctrl+Shift+4 → 🔥 Sunset Orange
Ctrl+Shift+5 → 🌸 Rose Gold
Ctrl+Shift+6 → 🌙 Midnight
Ctrl+Shift+7 → 🌊 Ocean Teal
Ctrl+Shift+8 → ⚡ Electric Cyber
Ctrl+Shift+9 → 🌅 Golden Sunrise
Ctrl+Shift+0 → 🎮 Gaming Neon
            </pre>
        </div>
        
        <div class="test-section">
            <h2>🔍 5. Debug Info</h2>
            <pre id="debug-info">Ładowanie informacji debug...</pre>
        </div>
        
        <div class="test-section">
            <h2>🚀 6. Go Back to Admin</h2>
            <a href="<?php echo admin_url(); ?>" class="test-button">← Powrót do panelu admin</a>
        </div>
    </div>

    <script>
        function testModernAdminApp() {
            const results = document.getElementById('js-results');
            let output = '<h3>ModernAdminApp Test Results:</h3>';
            
            if (typeof window.ModernAdminApp !== 'undefined') {
                output += '<div class="status-ok">✅ ModernAdminApp class available</div>';
                
                try {
                    const app = window.ModernAdminApp.getInstance();
                    if (app) {
                        output += '<div class="status-ok">✅ App instance available</div>';
                        
                        const paletteManager = app.getModule('paletteManager');
                        if (paletteManager) {
                            output += '<div class="status-ok">✅ PaletteManager module loaded</div>';
                        } else {
                            output += '<div class="status-error">❌ PaletteManager module not found</div>';
                        }
                    } else {
                        output += '<div class="status-error">❌ App instance not available</div>';
                    }
                } catch (e) {
                    output += `<div class="status-error">❌ Error: ${e.message}</div>`;
                }
            } else {
                output += '<div class="status-error">❌ ModernAdminApp not found</div>';
            }
            
            results.innerHTML = output;
        }
        
        function testPaletteManager() {
            const results = document.getElementById('js-results');
            let output = '<h3>PaletteManager Test Results:</h3>';
            
            if (typeof window.PaletteManager !== 'undefined') {
                output += '<div class="status-ok">✅ PaletteManager class available</div>';
                
                try {
                    const manager = new window.PaletteManager();
                    output += '<div class="status-ok">✅ PaletteManager can be instantiated</div>';
                    
                    const palettes = manager.getAllPalettes();
                    output += `<div class="status-ok">✅ ${Object.keys(palettes).length} palettes available</div>`;
                } catch (e) {
                    output += `<div class="status-error">❌ Error: ${e.message}</div>`;
                }
            } else {
                output += '<div class="status-error">❌ PaletteManager class not found</div>';
            }
            
            results.innerHTML = output;
        }
        
        function switchPalette(paletteId) {
            try {
                // Try via ModernAdminApp first
                if (window.ModernAdminApp) {
                    const app = window.ModernAdminApp.getInstance();
                    const paletteManager = app.getModule('paletteManager');
                    if (paletteManager) {
                        paletteManager.setPalette(paletteId);
                        return;
                    }
                }
                
                // Fallback: dispatch event
                document.dispatchEvent(new CustomEvent('mas-palette-change', {
                    detail: { palette: paletteId }
                }));
                
                // Direct DOM manipulation as last resort
                document.documentElement.setAttribute('data-palette', paletteId);
                
            } catch (e) {
                console.error('Palette switch error:', e);
                alert('Error switching palette: ' + e.message);
            }
        }
        
        function testKeyboardShortcuts() {
            alert('Naciśnij Ctrl+Shift+8 dla Cyber Electric lub Ctrl+Shift+0 dla Gaming Neon!');
        }
        
        // Auto-load debug info
        document.addEventListener('DOMContentLoaded', function() {
            const debugInfo = document.getElementById('debug-info');
            let info = '';
            
            info += `User Agent: ${navigator.userAgent}\n`;
            info += `Screen Size: ${screen.width}x${screen.height}\n`;
            info += `Viewport: ${window.innerWidth}x${window.innerHeight}\n`;
            info += `Current URL: ${window.location.href}\n`;
            info += `Document Ready State: ${document.readyState}\n`;
            
            // Check for MAS objects
            info += '\n=== MAS Objects ===\n';
            info += `window.ModernAdminApp: ${typeof window.ModernAdminApp}\n`;
            info += `window.PaletteManager: ${typeof window.PaletteManager}\n`;
            info += `window.MASLoader: ${typeof window.MASLoader}\n`;
            
            // Check current palette
            const currentPalette = document.documentElement.getAttribute('data-palette');
            info += `Current Palette: ${currentPalette || 'none'}\n`;
            
            debugInfo.textContent = info;
        });
    </script>
</body>
</html> 