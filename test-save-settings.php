<?php
/**
 * Test zapisywania i aplikowania ustawień
 */

require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Brak uprawnień');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Zapisywania Ustawień</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #fff; }
        .test { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .pass { background: #2d5016; border-left: 4px solid #4caf50; }
        .fail { background: #5c1a1a; border-left: 4px solid #f44336; }
        .info { background: #1a3a5c; border-left: 4px solid #2196f3; }
        h1 { color: #4fc3f7; }
        h2 { color: #81c784; margin-top: 30px; }
        pre { background: #000; padding: 10px; border-radius: 3px; overflow-x: auto; max-height: 300px; overflow-y: auto; }
        button { background: #007cba; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin: 5px; }
        button:hover { background: #005a87; }
        input { padding: 8px; border: 1px solid #ccc; border-radius: 4px; margin: 5px; }
    </style>
</head>
<body>
    <h1>🔍 Test Zapisywania i Aplikowania Ustawień</h1>
    
    <h2>1. Aktualne Ustawienia</h2>
    <?php
    $settings = get_option('mas_v2_settings', []);
    
    if (empty($settings)) {
        echo '<div class="test fail">✗ Brak ustawień w bazie danych!</div>';
        echo '<div class="test info">Uruchom: <a href="force-default-settings.php?force=yes" style="color: #4fc3f7;">force-default-settings.php</a></div>';
    } else {
        echo '<div class="test pass">✓ Ustawienia istnieją: ' . count($settings) . ' opcji</div>';
        
        // Pokaż kluczowe ustawienia menu
        $menu_keys = ['menu_background', 'menu_text_color', 'menu_width'];
        echo '<div class="test info"><strong>Kluczowe ustawienia menu:</strong><br>';
        foreach ($menu_keys as $key) {
            $value = isset($settings[$key]) ? $settings[$key] : 'NIE USTAWIONE';
            echo "$key = $value<br>";
        }
        echo '</div>';
    }
    ?>
    
    <h2>2. Test Generowania CSS</h2>
    <?php
    // Spróbuj wygenerować CSS
    $plugin = ModernAdminStylerV2::getInstance();
    
    // Użyj reflection aby dostać się do prywatnych metod
    $reflection = new ReflectionClass($plugin);
    
    try {
        // Generuj CSS Variables
        $method = $reflection->getMethod('generateCSSVariables');
        $method->setAccessible(true);
        $css_vars = $method->invoke($plugin, $settings);
        
        if (!empty($css_vars)) {
            echo '<div class="test pass">✓ generateCSSVariables() działa (' . strlen($css_vars) . ' znaków)</div>';
        } else {
            echo '<div class="test fail">✗ generateCSSVariables() zwraca pusty string!</div>';
        }
        
        // Generuj Menu CSS
        $method = $reflection->getMethod('generateMenuCSS');
        $method->setAccessible(true);
        $menu_css = $method->invoke($plugin, $settings);
        
        if (!empty($menu_css)) {
            echo '<div class="test pass">✓ generateMenuCSS() działa (' . strlen($menu_css) . ' znaków)</div>';
        } else {
            echo '<div class="test fail">✗ generateMenuCSS() zwraca pusty string!</div>';
        }
        
        // Pokaż wygenerowany CSS
        echo '<h3>Wygenerowany CSS:</h3>';
        echo '<pre>' . htmlspecialchars($css_vars . "\n\n" . $menu_css) . '</pre>';
        
    } catch (Exception $e) {
        echo '<div class="test fail">✗ Błąd: ' . $e->getMessage() . '</div>';
    }
    ?>
    
    <h2>3. Test Zapisywania</h2>
    <form method="post" action="">
        <input type="hidden" name="test_save" value="1">
        <label>Kolor tła menu:</label>
        <input type="color" name="menu_background" value="<?php echo $settings['menu_background'] ?? '#23282d'; ?>">
        <button type="submit">Zapisz Test</button>
    </form>
    
    <?php
    if (isset($_POST['test_save'])) {
        $new_color = sanitize_hex_color($_POST['menu_background']);
        $settings['menu_background'] = $new_color;
        
        $updated = update_option('mas_v2_settings', $settings);
        
        if ($updated) {
            echo '<div class="test pass">✓ Ustawienia zapisane! Nowy kolor: ' . $new_color . '</div>';
            echo '<div class="test info">Odśwież stronę aby zobaczyć zmiany</div>';
        } else {
            echo '<div class="test fail">✗ Nie udało się zapisać (lub wartość się nie zmieniła)</div>';
        }
    }
    ?>
    
    <h2>4. Sprawdź outputCustomStyles()</h2>
    <?php
    // Sprawdź czy outputCustomStyles() jest wywoływany
    echo '<div class="test info">Sprawdź w źródle strony czy jest tag &lt;style id="mas-v2-custom-styles"&gt;</div>';
    echo '<div class="test info">Jeśli nie ma - outputCustomStyles() nie działa!</div>';
    ?>
    
    <h2>5. Diagnostyka</h2>
    <div class="test info">
        <strong>Możliwe przyczyny problemu:</strong><br>
        1. CSS nie jest generowany (funkcje zwracają pusty string)<br>
        2. CSS jest generowany ale nie jest wstawiany do &lt;head&gt;<br>
        3. CSS jest wstawiany ale ma niski priorytet (nadpisywany)<br>
        4. Ustawienia nie są przekazywane do funkcji generujących CSS<br>
        5. Cache - stary CSS jest cachowany
    </div>
    
    <h2>6. Szybkie Akcje</h2>
    <button onclick="window.location.reload()">Odśwież Stronę</button>
    <button onclick="window.location.href='<?php echo admin_url('admin.php?page=mas-v2-menu'); ?>'">Przejdź do Ustawień</button>
    <button onclick="if(confirm('Wyczyścić cache?')) { fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=mas_v2_clear_cache').then(() => alert('Cache wyczyszczony!')); }">Wyczyść Cache</button>
    
</body>
</html>
