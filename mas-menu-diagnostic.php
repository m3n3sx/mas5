<?php
/**
 * MAS Menu Diagnostic Tool
 * 
 * Narzędzie diagnostyczne do debugowania problemów z menu
 * 
 * @package Modern Admin Styler V2
 * @version 1.0.0
 */

// Zabezpieczenie przed bezpośrednim dostępem
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Klasa diagnostyczna dla menu
 */
class MAS_Menu_Diagnostic {
    
    /**
     * Uruchom diagnostykę
     */
    public static function run() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        add_action('admin_footer', [__CLASS__, 'outputDiagnosticPanel']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueueDiagnosticAssets']);
    }
    
    /**
     * Załaduj style i skrypty diagnostyczne
     */
    public static function enqueueDiagnosticAssets() {
        ?>
        <style>
        #mas-diagnostic-panel {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 400px;
            max-height: 600px;
            background: #fff;
            border: 2px solid #0073aa;
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
            z-index: 999999;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            transition: all 0.3s ease;
        }
        
        #mas-diagnostic-panel.collapsed {
            width: auto;
            height: auto;
        }
        
        #mas-diagnostic-panel.collapsed .mas-diagnostic-content {
            display: none;
        }
        
        .mas-diagnostic-header {
            background: #0073aa;
            color: white;
            padding: 12px 15px;
            border-radius: 6px 6px 0 0;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .mas-diagnostic-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }
        
        .mas-diagnostic-toggle {
            font-size: 20px;
            transition: transform 0.3s ease;
        }
        
        #mas-diagnostic-panel.collapsed .mas-diagnostic-toggle {
            transform: rotate(180deg);
        }
        
        .mas-diagnostic-content {
            padding: 15px;
            max-height: 520px;
            overflow-y: auto;
        }
        
        .mas-diagnostic-section {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e1e1e1;
        }
        
        .mas-diagnostic-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .mas-diagnostic-section h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            font-weight: 600;
            color: #23282d;
        }
        
        .mas-diagnostic-item {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            font-size: 13px;
            line-height: 1.5;
        }
        
        .mas-diagnostic-label {
            color: #666;
            flex-shrink: 0;
            margin-right: 10px;
        }
        
        .mas-diagnostic-value {
            text-align: right;
            font-family: monospace;
            word-break: break-all;
            flex: 1;
        }
        
        .mas-diagnostic-value.success {
            color: #46b450;
            font-weight: 600;
        }
        
        .mas-diagnostic-value.error {
            color: #dc3232;
            font-weight: 600;
        }
        
        .mas-diagnostic-value.warning {
            color: #ffb900;
            font-weight: 600;
        }
        
        .mas-diagnostic-code {
            background: #f1f1f1;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            margin-top: 10px;
            white-space: pre-wrap;
            word-break: break-all;
        }
        
        .mas-diagnostic-actions {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e1e1e1;
            display: flex;
            gap: 10px;
        }
        
        .mas-diagnostic-button {
            background: #0073aa;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: background 0.2s ease;
        }
        
        .mas-diagnostic-button:hover {
            background: #005a87;
        }
        
        .mas-diagnostic-button.secondary {
            background: #666;
        }
        
        .mas-diagnostic-button.secondary:hover {
            background: #555;
        }
        </style>
        <?php
    }
    
    /**
     * Wyświetl panel diagnostyczny
     */
    public static function outputDiagnosticPanel() {
        $settings = get_option('mas_v2_settings', []);
        ?>
        <div id="mas-diagnostic-panel">
            <div class="mas-diagnostic-header" onclick="toggleDiagnosticPanel()">
                <h3>🔍 MAS Menu Diagnostic</h3>
                <span class="mas-diagnostic-toggle">▼</span>
            </div>
            <div class="mas-diagnostic-content">
                <!-- Sekcja: Status CSS -->
                <div class="mas-diagnostic-section">
                    <h4>📄 CSS Files Status</h4>
                    <div class="mas-diagnostic-item">
                        <span class="mas-diagnostic-label">admin-menu-reset.css:</span>
                        <span class="mas-diagnostic-value" id="diag-css-reset">Checking...</span>
                    </div>
                    <div class="mas-diagnostic-item">
                        <span class="mas-diagnostic-label">admin-modern.css:</span>
                        <span class="mas-diagnostic-value" id="diag-css-modern">Checking...</span>
                    </div>
                    <div class="mas-diagnostic-item">
                        <span class="mas-diagnostic-label">admin-menu-modern.css:</span>
                        <span class="mas-diagnostic-value" id="diag-css-menu-modern">Checking...</span>
                    </div>
                    <div class="mas-diagnostic-item">
                        <span class="mas-diagnostic-label">quick-fix.css:</span>
                        <span class="mas-diagnostic-value" id="diag-css-quickfix">Checking...</span>
                    </div>
                </div>
                
                <!-- Sekcja: Menu Settings -->
                <div class="mas-diagnostic-section">
                    <h4>⚙️ Menu Settings</h4>
                    <?php
                    $menu_settings = array_filter($settings, function($key) {
                        return strpos($key, 'menu_') === 0 || in_array($key, ['modern_menu_style', 'auto_fold_menu']);
                    }, ARRAY_FILTER_USE_KEY);
                    
                    if (empty($menu_settings)) {
                        echo '<div class="mas-diagnostic-item"><span class="mas-diagnostic-value error">No menu settings found!</span></div>';
                    } else {
                        foreach ($menu_settings as $key => $value) {
                            if (!empty($value)) {
                                $display_value = is_bool($value) ? ($value ? 'true' : 'false') : $value;
                                echo '<div class="mas-diagnostic-item">';
                                echo '<span class="mas-diagnostic-label">' . esc_html($key) . ':</span>';
                                echo '<span class="mas-diagnostic-value">' . esc_html($display_value) . '</span>';
                                echo '</div>';
                            }
                        }
                    }
                    ?>
                </div>
                
                <!-- Sekcja: Body Classes -->
                <div class="mas-diagnostic-section">
                    <h4>🏷️ Body Classes</h4>
                    <div class="mas-diagnostic-item">
                        <span class="mas-diagnostic-label">mas-v2-menu-custom-enabled:</span>
                        <span class="mas-diagnostic-value" id="diag-class-custom">Checking...</span>
                    </div>
                    <div class="mas-diagnostic-item">
                        <span class="mas-diagnostic-label">mas-v2-menu-floating-enabled:</span>
                        <span class="mas-diagnostic-value" id="diag-class-floating">Checking...</span>
                    </div>
                    <div class="mas-diagnostic-item">
                        <span class="mas-diagnostic-label">mas-v2-submenu-custom-enabled:</span>
                        <span class="mas-diagnostic-value" id="diag-class-submenu">Checking...</span>
                    </div>
                </div>
                
                <!-- Sekcja: CSS Variables -->
                <div class="mas-diagnostic-section">
                    <h4>🎨 CSS Variables</h4>
                    <div class="mas-diagnostic-item">
                        <span class="mas-diagnostic-label">--mas-menu-enabled:</span>
                        <span class="mas-diagnostic-value" id="diag-var-enabled">Checking...</span>
                    </div>
                    <div class="mas-diagnostic-item">
                        <span class="mas-diagnostic-label">--mas-menu-bg-color:</span>
                        <span class="mas-diagnostic-value" id="diag-var-bg">Checking...</span>
                    </div>
                    <div class="mas-diagnostic-item">
                        <span class="mas-diagnostic-label">--mas-menu-floating-enabled:</span>
                        <span class="mas-diagnostic-value" id="diag-var-floating">Checking...</span>
                    </div>
                </div>
                
                <!-- Sekcja: JavaScript -->
                <div class="mas-diagnostic-section">
                    <h4>📜 JavaScript Status</h4>
                    <div class="mas-diagnostic-item">
                        <span class="mas-diagnostic-label">MenuManager loaded:</span>
                        <span class="mas-diagnostic-value" id="diag-js-manager">Checking...</span>
                    </div>
                    <div class="mas-diagnostic-item">
                        <span class="mas-diagnostic-label">MenuManager initialized:</span>
                        <span class="mas-diagnostic-value" id="diag-js-init">Checking...</span>
                    </div>
                    <div class="mas-diagnostic-item">
                        <span class="mas-diagnostic-label">masV2Global available:</span>
                        <span class="mas-diagnostic-value" id="diag-js-global">Checking...</span>
                    </div>
                </div>
                
                <!-- Akcje -->
                <div class="mas-diagnostic-actions">
                    <button class="mas-diagnostic-button" onclick="runFullDiagnostic()">
                        🔄 Refresh Diagnostic
                    </button>
                    <button class="mas-diagnostic-button secondary" onclick="copyDiagnosticReport()">
                        📋 Copy Report
                    </button>
                </div>
            </div>
        </div>
        
        <script>
        // Funkcja toggle panelu
        function toggleDiagnosticPanel() {
            const panel = document.getElementById('mas-diagnostic-panel');
            panel.classList.toggle('collapsed');
        }
        
        // Funkcja diagnostyczna
        function runFullDiagnostic() {
            console.log('🔍 Running MAS Menu Diagnostic...');
            
            // Test CSS Files
            const stylesheets = Array.from(document.styleSheets);
            
            // Check admin-menu-reset.css
            const resetCSS = stylesheets.find(s => s.href && s.href.includes('admin-menu-reset.css'));
            updateDiagnostic('diag-css-reset', resetCSS ? 'Loaded' : 'Not loaded', resetCSS ? 'success' : 'error');
            
            // Check admin-modern.css
            const modernCSS = stylesheets.find(s => s.href && s.href.includes('admin-modern.css'));
            updateDiagnostic('diag-css-modern', modernCSS ? 'Loaded' : 'Not loaded', modernCSS ? 'success' : 'error');
            
            // Check admin-menu-modern.css
            const menuModernCSS = stylesheets.find(s => s.href && s.href.includes('admin-menu-modern.css'));
            updateDiagnostic('diag-css-menu-modern', menuModernCSS ? 'Disabled ✓' : 'Not loaded ✓', 'success');
            
            // Check quick-fix.css
            const quickfixCSS = stylesheets.find(s => s.href && s.href.includes('quick-fix.css'));
            updateDiagnostic('diag-css-quickfix', quickfixCSS ? 'Loaded ⚠️' : 'Not loaded ✓', quickfixCSS ? 'warning' : 'success');
            
            // Test Body Classes
            const body = document.body;
            updateDiagnostic('diag-class-custom', body.classList.contains('mas-v2-menu-custom-enabled') ? 'Present' : 'Missing', 
                body.classList.contains('mas-v2-menu-custom-enabled') ? 'success' : 'warning');
            updateDiagnostic('diag-class-floating', body.classList.contains('mas-v2-menu-floating-enabled') ? 'Present' : 'Missing', 
                body.classList.contains('mas-v2-menu-floating-enabled') ? 'success' : 'warning');
            updateDiagnostic('diag-class-submenu', body.classList.contains('mas-v2-submenu-custom-enabled') ? 'Present' : 'Missing', 
                body.classList.contains('mas-v2-submenu-custom-enabled') ? 'success' : 'warning');
            
            // Test CSS Variables
            const root = document.documentElement;
            const menuEnabled = getComputedStyle(root).getPropertyValue('--mas-menu-enabled').trim();
            updateDiagnostic('diag-var-enabled', menuEnabled || 'Not set', menuEnabled === '1' ? 'success' : 'warning');
            
            const menuBg = getComputedStyle(root).getPropertyValue('--mas-menu-bg-color').trim();
            updateDiagnostic('diag-var-bg', menuBg || 'Not set', menuBg ? 'success' : 'warning');
            
            const floatingEnabled = getComputedStyle(root).getPropertyValue('--mas-menu-floating-enabled').trim();
            updateDiagnostic('diag-var-floating', floatingEnabled || 'Not set', floatingEnabled === '1' ? 'success' : 'warning');
            
            // Test JavaScript
            const menuManagerLoaded = typeof window.MenuManager !== 'undefined' || typeof MenuManager !== 'undefined';
            updateDiagnostic('diag-js-manager', menuManagerLoaded ? 'Loaded' : 'Not loaded', menuManagerLoaded ? 'success' : 'error');
            
            const menuManagerInit = window.masMenuManager && window.masMenuManager.initialized;
            updateDiagnostic('diag-js-init', menuManagerInit ? 'Initialized' : 'Not initialized', menuManagerInit ? 'success' : 'warning');
            
            const globalAvailable = typeof masV2Global !== 'undefined';
            updateDiagnostic('diag-js-global', globalAvailable ? 'Available' : 'Not available', globalAvailable ? 'success' : 'error');
            
            // Log dodatkowo w konsoli
            console.log('🔍 Diagnostic Results:', {
                css: {
                    resetLoaded: !!resetCSS,
                    modernLoaded: !!modernCSS,
                    menuModernDisabled: !menuModernCSS,
                    quickfixDisabled: !quickfixCSS
                },
                bodyClasses: {
                    customEnabled: body.classList.contains('mas-v2-menu-custom-enabled'),
                    floatingEnabled: body.classList.contains('mas-v2-menu-floating-enabled'),
                    submenuEnabled: body.classList.contains('mas-v2-submenu-custom-enabled')
                },
                cssVariables: {
                    menuEnabled,
                    menuBg,
                    floatingEnabled
                },
                javascript: {
                    menuManagerLoaded,
                    menuManagerInit,
                    globalAvailable
                }
            });
        }
        
        // Helper function
        function updateDiagnostic(id, value, status) {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value;
                element.className = 'mas-diagnostic-value ' + (status || '');
            }
        }
        
        // Copy diagnostic report
        function copyDiagnosticReport() {
            const report = generateDiagnosticReport();
            navigator.clipboard.writeText(report).then(() => {
                alert('Diagnostic report copied to clipboard!');
            });
        }
        
        // Generate report
        function generateDiagnosticReport() {
            const sections = document.querySelectorAll('.mas-diagnostic-section');
            let report = '=== MAS Menu Diagnostic Report ===\n\n';
            
            sections.forEach(section => {
                const title = section.querySelector('h4').textContent;
                report += title + '\n' + '-'.repeat(title.length) + '\n';
                
                const items = section.querySelectorAll('.mas-diagnostic-item');
                items.forEach(item => {
                    const label = item.querySelector('.mas-diagnostic-label')?.textContent || '';
                    const value = item.querySelector('.mas-diagnostic-value')?.textContent || '';
                    if (label) {
                        report += label + ' ' + value + '\n';
                    }
                });
                report += '\n';
            });
            
            report += 'Generated: ' + new Date().toLocaleString();
            return report;
        }
        
        // Auto-run on load
        setTimeout(runFullDiagnostic, 1000);
        </script>
        <?php
    }
}

// Uruchom diagnostykę jeśli użytkownik ma uprawnienia i jest włączony tryb debug
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('init', function() {
        if (isset($_GET['mas_diagnostic']) && current_user_can('manage_options')) {
            MAS_Menu_Diagnostic::run();
        }
    });
} 