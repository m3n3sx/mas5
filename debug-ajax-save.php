<?php
/**
 * Debug AJAX Save - Sprawdź co jest wysyłane i odbierane
 */

// Load WordPress
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Brak uprawnień');
}

// Symuluj AJAX save
if (isset($_POST['test_ajax'])) {
    echo "<h2>🔍 Otrzymane dane POST:</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<h2>📊 Liczba pól:</h2>";
    echo "<p>Razem: " . count($_POST) . " pól</p>";
    
    // Policz różne typy
    $menu_fields = 0;
    $admin_bar_fields = 0;
    $other_fields = 0;
    
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'menu_') === 0) {
            $menu_fields++;
        } elseif (strpos($key, 'admin_bar_') === 0) {
            $admin_bar_fields++;
        } else {
            $other_fields++;
        }
    }
    
    echo "<p>Pola menu: {$menu_fields}</p>";
    echo "<p>Pola admin bar: {$admin_bar_fields}</p>";
    echo "<p>Inne pola: {$other_fields}</p>";
    
    echo "<h2>✅ Test zakończony</h2>";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Debug AJAX Save</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #333; }
        .test-form { margin: 20px 0; padding: 20px; background: #f9f9f9; border-radius: 4px; }
        .test-form input, .test-form select { margin: 5px 0; padding: 8px; width: 100%; }
        .test-form button { padding: 10px 20px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px; }
        .result { margin: 20px 0; padding: 15px; background: #e7f5ff; border-left: 4px solid #0073aa; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .field-group { margin: 15px 0; padding: 15px; background: white; border-radius: 4px; }
        .field-group h3 { margin-top: 0; color: #0073aa; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Debug AJAX Save</h1>
        <p>Ten test sprawdza co dokładnie jest wysyłane przez formularz</p>
        
        <div class="test-form">
            <h2>Formularz Testowy (Symulacja MAS V2)</h2>
            <form id="test-form">
                <div class="field-group">
                    <h3>Menu Settings</h3>
                    <label>Menu Background (menu_bg):</label>
                    <input type="color" name="menu_bg" value="#ff0000">
                    
                    <label>Menu Text Color (menu_text_color):</label>
                    <input type="color" name="menu_text_color" value="#ffffff">
                    
                    <label>Menu Width (menu_width):</label>
                    <input type="range" name="menu_width" value="250" min="140" max="300">
                    <span id="menu_width_value">250</span>
                    
                    <label>Menu Icons Enabled (menu_icons_enabled):</label>
                    <input type="checkbox" name="menu_icons_enabled" value="1" checked>
                    
                    <label>Menu Floating (menu_floating):</label>
                    <input type="checkbox" name="menu_floating" value="1">
                </div>
                
                <div class="field-group">
                    <h3>Admin Bar Settings</h3>
                    <label>Admin Bar Background (admin_bar_bg):</label>
                    <input type="color" name="admin_bar_bg" value="#23282d">
                    
                    <label>Admin Bar Height (admin_bar_height):</label>
                    <input type="range" name="admin_bar_height" value="40" min="25" max="60">
                    <span id="admin_bar_height_value">40</span>
                    
                    <label>Admin Bar Floating (admin_bar_floating):</label>
                    <input type="checkbox" name="admin_bar_floating" value="1" checked>
                    
                    <label>Hide WP Logo (hide_wp_logo):</label>
                    <input type="checkbox" name="hide_wp_logo" value="1">
                </div>
                
                <div class="field-group">
                    <h3>General Settings</h3>
                    <label>Enable Plugin (enable_plugin):</label>
                    <input type="checkbox" name="enable_plugin" value="1" checked>
                    
                    <label>Theme (theme):</label>
                    <select name="theme">
                        <option value="modern">Modern</option>
                        <option value="minimal">Minimal</option>
                        <option value="dark">Dark</option>
                    </select>
                    
                    <label>Auto Save (auto_save):</label>
                    <input type="checkbox" name="auto_save" value="1">
                </div>
                
                <button type="submit">Test Submit (jQuery)</button>
                <button type="button" id="test-native">Test Submit (Native)</button>
            </form>
        </div>
        
        <div class="result" id="result" style="display:none;">
            <h3>Wynik:</h3>
            <div id="result-content"></div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Update range values
        $('input[type="range"]').on('input', function() {
            $('#' + $(this).attr('name') + '_value').text($(this).val());
        });
        
        // Test 1: jQuery submit (jak w MAS V2)
        $('#test-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            
            // Metoda 1: serializeArray() + rozpakowanie (AKTUALNA)
            const formData = $form.serializeArray();
            const postData = {
                test_ajax: '1',
                method: 'jquery_serializeArray'
            };
            
            // Dodaj pola
            $.each(formData, function(i, field) {
                postData[field.name] = field.value;
            });
            
            // Dodaj niezaznaczone checkboxy
            $form.find('input[type="checkbox"]').each(function() {
                const name = $(this).attr('name');
                if (name && !postData.hasOwnProperty(name)) {
                    postData[name] = '0';
                }
            });
            
            console.log('📤 Wysyłanie (jQuery):', postData);
            console.log('📊 Liczba pól:', Object.keys(postData).length);
            
            // Wyślij
            $.post('debug-ajax-save.php', postData)
                .done(function(response) {
                    $('#result-content').html(response);
                    $('#result').show();
                })
                .fail(function() {
                    alert('Błąd AJAX');
                });
        });
        
        // Test 2: Native submit
        $('#test-native').on('click', function() {
            const form = document.getElementById('test-form');
            form.action = 'debug-ajax-save.php';
            form.method = 'POST';
            form.submit();
        });
    });
    </script>
</body>
</html>
