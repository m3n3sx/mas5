<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>MAS V2 - Test Zapisu Ustawień</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #0073aa; padding-bottom: 10px; }
        .test-section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #0073aa; }
        .success { color: #46b450; font-weight: bold; }
        .error { color: #dc3232; font-weight: bold; }
        .warning { color: #ffb900; font-weight: bold; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .status-icon { font-size: 24px; margin-right: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #0073aa; color: white; }
        .test-form { margin: 20px 0; padding: 20px; background: #fff; border: 2px solid #0073aa; border-radius: 8px; }
        .test-form input { padding: 8px; margin: 5px; border: 1px solid #ddd; border-radius: 4px; }
        .test-form button { padding: 10px 20px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .test-form button:hover { background: #005a87; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 MAS V2 - Diagnostyka Zapisu Ustawień</h1>
        
        <?php
        // Load WordPress
        require_once('../../../wp-load.php');
        
        if (!current_user_can('manage_options')) {
            die('<p class="error">❌ Brak uprawnień administratora!</p>');
        }
        
        echo '<div class="test-section">';
        echo '<h2>📊 Status Bieżący</h2>';
        
        // 1. Sprawdź czy ustawienia istnieją
        $settings = get_option('mas_v2_settings', []);
        $settings_count = count($settings);
        
        if ($settings_count > 0) {
            echo '<p class="success"><span class="status-icon">✅</span>Ustawienia istnieją: ' . $settings_count . ' opcji</p>';
        } else {
            echo '<p class="error"><span class="status-icon">❌</span>BRAK USTAWIEŃ w bazie danych!</p>';
        }
        
        // 2. Sprawdź ustawienia menu
        $menu_settings = array_filter($settings, function($key) {
            return strpos($key, 'menu_') === 0;
        }, ARRAY_FILTER_USE_KEY);
        
        echo '<p>Ustawienia menu: ' . count($menu_settings) . '</p>';
        
        // 3. Sprawdź czy plugin jest włączony
        $plugin_enabled = isset($settings['enable_plugin']) ? $settings['enable_plugin'] : false;
        if ($plugin_enabled) {
            echo '<p class="success"><span class="status-icon">✅</span>Plugin WŁĄCZONY</p>';
        } else {
            echo '<p class="warning"><span class="status-icon">⚠️</span>Plugin WYŁĄCZONY</p>';
        }
        
        echo '</div>';
        
        // 4. Test zapisu
        if (isset($_POST['test_save'])) {
            echo '<div class="test-section">';
            echo '<h2>🧪 Wynik Testu Zapisu</h2>';
            
            $test_color = sanitize_hex_color($_POST['test_color']);
            $test_width = intval($_POST['test_width']);
            
            // Pobierz aktualne ustawienia
            $current = get_option('mas_v2_settings', []);
            
            // Dodaj testowe wartości
            $current['menu_background'] = $test_color;
            $current['menu_width'] = $test_width;
            $current['test_timestamp'] = current_time('mysql');
            
            // Zapisz
            $save_result = update_option('mas_v2_settings', $current);
            
            if ($save_result) {
                echo '<p class="success"><span class="status-icon">✅</span>Zapis UDANY!</p>';
                
                // Weryfikuj
                $verify = get_option('mas_v2_settings', []);
                if ($verify['menu_background'] === $test_color && $verify['menu_width'] === $test_width) {
                    echo '<p class="success"><span class="status-icon">✅</span>Weryfikacja UDANA!</p>';
                    echo '<p>Zapisano: menu_background = ' . $test_color . ', menu_width = ' . $test_width . '</p>';
                } else {
                    echo '<p class="error"><span class="status-icon">❌</span>Weryfikacja NIEUDANA!</p>';
                }
            } else {
                echo '<p class="warning"><span class="status-icon">⚠️</span>update_option zwróciło FALSE (może wartość się nie zmieniła)</p>';
            }
            
            echo '</div>';
        }
        
        // Formularz testowy
        echo '<div class="test-form">';
        echo '<h2>🧪 Test Zapisu</h2>';
        echo '<form method="POST">';
        echo '<p>Kolor tła menu: <input type="color" name="test_color" value="#ff0000"></p>';
        echo '<p>Szerokość menu: <input type="number" name="test_width" value="250" min="100" max="400"></p>';
        echo '<button type="submit" name="test_save">Testuj Zapis</button>';
        echo '</form>';
        echo '</div>';
        
        // 5. Pokaż aktualne ustawienia menu
        echo '<div class="test-section">';
        echo '<h2>📋 Aktualne Ustawienia Menu</h2>';
        
        if (!empty($menu_settings)) {
            echo '<table>';
            echo '<tr><th>Klucz</th><th>Wartość</th></tr>';
            foreach ($menu_settings as $key => $value) {
                $display_value = is_bool($value) ? ($value ? 'true' : 'false') : $value;
                echo '<tr><td>' . esc_html($key) . '</td><td>' . esc_html($display_value) . '</td></tr>';
            }
            echo '</table>';
        } else {
            echo '<p class="warning">Brak ustawień menu</p>';
        }
        
        echo '</div>';
        
        // 6. Test generowania CSS
        echo '<div class="test-section">';
        echo '<h2>🎨 Test Generowania CSS</h2>';
        
        if (class_exists('ModernAdminStylerV2')) {
            $plugin = ModernAdminStylerV2::getInstance();
            
            // Użyj reflection aby wywołać prywatną metodę
            $reflection = new ReflectionClass($plugin);
            $method = $reflection->getMethod('generateMenuCSS');
            $method->setAccessible(true);
            
            $css = $method->invoke($plugin, $settings);
            
            if (!empty($css) && strlen($css) > 50) {
                echo '<p class="success"><span class="status-icon">✅</span>CSS generowany poprawnie (' . strlen($css) . ' znaków)</p>';
                echo '<details><summary>Pokaż CSS (pierwsze 500 znaków)</summary>';
                echo '<pre>' . esc_html(substr($css, 0, 500)) . '...</pre>';
                echo '</details>';
            } else {
                echo '<p class="error"><span class="status-icon">❌</span>CSS NIE jest generowany lub jest pusty!</p>';
            }
        } else {
            echo '<p class="error"><span class="status-icon">❌</span>Klasa ModernAdminStylerV2 nie istnieje!</p>';
        }
        
        echo '</div>';
        
        // 7. Sprawdź AJAX endpoint
        echo '<div class="test-section">';
        echo '<h2>🔌 Test AJAX Endpoint</h2>';
        echo '<p>AJAX URL: <code>' . admin_url('admin-ajax.php') . '</code></p>';
        echo '<p>Nonce: <code>' . wp_create_nonce('mas_v2_nonce') . '</code></p>';
        echo '<p><a href="' . admin_url('admin.php?page=mas-v2-settings') . '" target="_blank">Otwórz stronę ustawień →</a></p>';
        echo '</div>';
        
        // 8. Pokaż ostatnie backupy
        echo '<div class="test-section">';
        echo '<h2>💾 Ostatnie Backupy</h2>';
        
        global $wpdb;
        $backups = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE 'mas_v2_settings_backup_%' 
             ORDER BY option_id DESC LIMIT 5"
        );
        
        if (!empty($backups)) {
            echo '<ul>';
            foreach ($backups as $backup) {
                echo '<li>' . esc_html($backup->option_name) . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>Brak backupów</p>';
        }
        
        echo '</div>';
        ?>
        
        <div class="test-section">
            <h2>📝 Instrukcje</h2>
            <ol>
                <li>Sprawdź czy wszystkie testy pokazują ✅</li>
                <li>Użyj formularza testowego aby sprawdzić zapis</li>
                <li>Przejdź do strony ustawień i spróbuj zapisać</li>
                <li>Sprawdź konsolę przeglądarki (F12) pod kątem błędów JavaScript</li>
                <li>Sprawdź zakładkę Network w DevTools aby zobaczyć odpowiedź AJAX</li>
            </ol>
        </div>
    </div>
</body>
</html>
