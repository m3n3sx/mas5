<?php
/**
 * Test Floating Admin Bar
 * Prosta strona testowa do sprawdzenia czy floating admin bar działa
 * 
 * INSTRUKCJA:
 * 1. Dodaj do wp-config.php: define('WP_DEBUG', true);
 * 2. Odwiedź /wp-admin/admin.php?page=test-floating-admin-bar
 * 3. Sprawdź czy admin bar jest floating
 * 4. Sprawdź Console (F12) czy są błędy
 */

// Sprawdź czy to WordPress
if (!defined('ABSPATH')) {
    exit('Direct access not allowed');
}

// Tylko dla adminów
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

// Pobierz plugin
$plugin = ModernAdminStylerV2::getInstance();
$settings = $plugin->getSettings();

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Floating Admin Bar - MAS V2</title>
    <style>
        body { 
            margin: 0; 
            padding: 20px; 
            font-family: Arial, sans-serif;
            background: #f0f0f1;
        }
        .test-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status { 
            padding: 15px; 
            margin: 10px 0; 
            border-radius: 4px; 
            border-left: 4px solid;
        }
        .status.success { 
            background: #d4edda; 
            border-color: #28a745; 
            color: #155724;
        }
        .status.warning { 
            background: #fff3cd; 
            border-color: #ffc107; 
            color: #856404;
        }
        .status.error { 
            background: #f8d7da; 
            border-color: #dc3545; 
            color: #721c24;
        }
        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            white-space: pre-wrap;
        }
        .button {
            background: #0073aa;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .button:hover {
            background: #005a87;
        }
        .floating-test {
            height: 2000px;
            background: linear-gradient(to bottom, #e3f2fd, #bbdefb);
            margin: 20px -30px;
            padding: 30px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🧪 Test Floating Admin Bar</h1>
        <p><strong>Modern Admin Styler V2</strong> - Diagnostyka floating admin bar</p>

        <h2>Status wtyczki</h2>
        <?php if ($settings['enable_plugin'] ?? false): ?>
            <div class="status success">✅ Wtyczka włączona</div>
        <?php else: ?>
            <div class="status error">❌ Wtyczka wyłączona</div>
        <?php endif; ?>

        <?php if ($settings['custom_admin_bar_style'] ?? false): ?>
            <div class="status success">✅ Custom admin bar włączony</div>
        <?php else: ?>
            <div class="status error">❌ Custom admin bar wyłączony</div>
        <?php endif; ?>

        <?php if ($settings['admin_bar_floating'] ?? false): ?>
            <div class="status success">✅ Floating admin bar włączony</div>
        <?php else: ?>
            <div class="status warning">⚠️ Floating admin bar wyłączony</div>
        <?php endif; ?>

        <h2>Aktualne ustawienia Admin Bar</h2>
        <div class="debug-info"><?php 
            $adminBarSettings = array_filter($settings, function($key) {
                return strpos($key, 'admin_bar_') === 0;
            }, ARRAY_FILTER_USE_KEY);
            echo "Admin Bar Settings:\n";
            foreach ($adminBarSettings as $key => $value) {
                $displayValue = is_bool($value) ? ($value ? 'true' : 'false') : $value;
                echo "{$key}: {$displayValue}\n";
            }
        ?></div>

        <h2>CSS Debug</h2>
        <div class="debug-info"><?php 
            echo "Generated Admin Bar CSS:\n";
            echo "=======================\n";
            
            // Refleksja do wywołania prywatnej metody
            $reflection = new ReflectionClass($plugin);
            $method = $reflection->getMethod('generateAdminBarCSS');
            $method->setAccessible(true);
            $adminBarCSS = $method->invoke($plugin, $settings);
            
            if ($adminBarCSS) {
                echo htmlspecialchars($adminBarCSS);
            } else {
                echo "BRAK CSS - sprawdź czy custom_admin_bar_style jest włączony";
            }
        ?></div>

        <h2>JavaScript Test</h2>
        <button onclick="testFloating()" class="button">Test Force Floating</button>
        <button onclick="showAdminBarInfo()" class="button">Pokaż info Admin Bar</button>
        <button onclick="toggleConsole()" class="button">Toggle Console Log</button>

        <div id="js-results" class="debug-info" style="min-height: 100px;"></div>

        <h2>Visual Test</h2>
        <p>Przewiń w dół aby sprawdzić czy admin bar zostaje na górze lub się porusza z marginesami:</p>
        
        <div class="floating-test">
            <h3>Długa sekcja do scrollowania</h3>
            <p>Ta sekcja ma 2000px wysokości. Przewiń w górę i w dół aby sprawdzić zachowanie admin bara.</p>
            <p>Jeśli floating działa, admin bar powinien:</p>
            <ul>
                <li>Mieć marginesy po bokach</li>
                <li>Być zaokrąglony</li>
                <li>Mieć cień</li>
                <li>Być odsunięty od góry strony</li>
            </ul>
            <p>Sprawdź również czy strona ma odpowiedni margines na górze aby kompensować zmienioną pozycję admin bara.</p>
        </div>

        <h2>Przydatne linki</h2>
        <a href="/wp-admin/admin.php?page=mas-v2-settings" class="button">Ustawienia wtyczki</a>
        <a href="/wp-admin/admin.php?page=mas-v2-settings&debug_css=1" class="button">Debug CSS</a>
        <a href="javascript:window.location.reload()" class="button">Odśwież stronę</a>
    </div>

    <script>
        let consoleLogging = false;

        function log(message) {
            console.log('MAS V2 Test:', message);
            if (consoleLogging) {
                const results = document.getElementById('js-results');
                results.innerHTML += new Date().toLocaleTimeString() + ': ' + message + '\n';
            }
        }

        function testFloating() {
            log('Testowanie force floating...');
            const adminBar = document.getElementById('wpadminbar');
            
            if (!adminBar) {
                log('❌ Admin bar nie znaleziony!');
                return;
            }

            log('✅ Admin bar znaleziony');
            log('Current position: ' + getComputedStyle(adminBar).position);
            log('Current top: ' + getComputedStyle(adminBar).top);
            log('Current margin: ' + getComputedStyle(adminBar).margin);

            // Force floating
            adminBar.style.position = 'relative';
            adminBar.style.top = '10px';
            adminBar.style.left = '10px';
            adminBar.style.right = '10px';
            adminBar.style.margin = '10px';
            adminBar.style.width = 'calc(100% - 20px)';
            adminBar.style.borderRadius = '8px';
            adminBar.style.boxShadow = '0 4px 20px rgba(0,0,0,0.15)';
            adminBar.style.zIndex = '99999';

            log('✅ Floating style zastosowany');
            
            // Fix body
            const html = document.documentElement;
            if (html) {
                html.style.paddingTop = '0';
                html.style.marginTop = '60px';
                log('✅ HTML margin naprawiony');
            }

            setTimeout(() => {
                log('Position po 1s: ' + getComputedStyle(adminBar).position);
                log('Top po 1s: ' + getComputedStyle(adminBar).top);
            }, 1000);
        }

        function showAdminBarInfo() {
            log('=== ADMIN BAR INFO ===');
            const adminBar = document.getElementById('wpadminbar');
            
            if (!adminBar) {
                log('❌ Admin bar nie znaleziony');
                return;
            }

            const computed = getComputedStyle(adminBar);
            log('Element: ' + adminBar.tagName + '#' + adminBar.id);
            log('Position: ' + computed.position);
            log('Top: ' + computed.top);
            log('Left: ' + computed.left);
            log('Width: ' + computed.width);
            log('Height: ' + computed.height);
            log('Margin: ' + computed.margin);
            log('Border-radius: ' + computed.borderRadius);
            log('Box-shadow: ' + computed.boxShadow);
            log('Z-index: ' + computed.zIndex);
            log('Transform: ' + computed.transform);

            // Sprawdź klasy body
            const bodyClasses = document.body.className.split(' ').filter(c => c.includes('mas'));
            log('Body classes (MAS): ' + bodyClasses.join(', '));

            // Sprawdź style inline
            log('Inline styles: ' + (adminBar.style.cssText || 'brak'));
        }

        function toggleConsole() {
            consoleLogging = !consoleLogging;
            const results = document.getElementById('js-results');
            
            if (consoleLogging) {
                results.innerHTML = 'Console logging włączony...\n';
                log('Console logging włączony');
            } else {
                results.innerHTML = 'Console logging wyłączony';
                log('Console logging wyłączony');
            }
        }

        // Auto-test po załadowaniu
        document.addEventListener('DOMContentLoaded', function() {
            log('Strona załadowana');
            
            setTimeout(() => {
                showAdminBarInfo();
                
                <?php if ($settings['admin_bar_floating'] ?? false): ?>
                    log('Floating enabled - checking if it works...');
                    const adminBar = document.getElementById('wpadminbar');
                    if (adminBar && getComputedStyle(adminBar).position !== 'relative') {
                        log('⚠️ WARNING: Floating enabled but position is not relative!');
                        log('Trying to fix...');
                        testFloating();
                    }
                <?php endif; ?>
            }, 500);
        });

        // Observer na admin bar
        if (window.MutationObserver) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.target.id === 'wpadminbar') {
                        log('Admin bar changed: ' + mutation.attributeName);
                    }
                });
            });
            
            document.addEventListener('DOMContentLoaded', function() {
                const adminBar = document.getElementById('wpadminbar');
                if (adminBar) {
                    observer.observe(adminBar, { attributes: true, attributeFilter: ['style', 'class'] });
                    log('Observer attached to admin bar');
                }
            });
        }
    </script>
</body>
</html> 