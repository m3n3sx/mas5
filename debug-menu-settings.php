<?php
/**
 * Debug script dla ustawień menu - Modern Admin Styler V2
 */

// Znajdź WordPress root
$wp_root = dirname(__FILE__);
while (!file_exists($wp_root . '/wp-config.php') && $wp_root !== '/') {
    $wp_root = dirname($wp_root);
}

if (!file_exists($wp_root . '/wp-config.php')) {
    die('Nie można znaleźć wp-config.php');
}

// Załaduj WordPress
require_once $wp_root . '/wp-config.php';
require_once $wp_root . '/wp-includes/wp-db.php';
require_once $wp_root . '/wp-includes/functions.php';

echo "<h1>🔍 Debug ustawień menu - Modern Admin Styler V2</h1>";

// Pobierz ustawienia z bazy
$settings = get_option('mas_v2_settings', []);

echo "<h2>📋 Wszystkie ustawienia wtyczki:</h2>";
echo "<pre>";
print_r($settings);
echo "</pre>";

echo "<h2>🎯 Ustawienia menu (filtrowane):</h2>";
$menu_settings = [];
foreach ($settings as $key => $value) {
    if (strpos($key, 'menu_') === 0 || $key === 'modern_menu_style' || $key === 'auto_fold_menu') {
        $menu_settings[$key] = $value;
    }
}

if (empty($menu_settings)) {
    echo "<p style='color: red;'>❌ BRAK USTAWIEŃ MENU!</p>";
    echo "<p>To dlatego menu wygląda jak default WordPress.</p>";
} else {
    echo "<pre>";
    print_r($menu_settings);
    echo "</pre>";
}

echo "<h2>🧪 Test logiki MenuManager:</h2>";
$hasMenuCustomizations = (
    !empty($settings['menu_background']) || 
    !empty($settings['menu_bg']) ||
    !empty($settings['menu_text_color']) || 
    !empty($settings['menu_hover_background']) ||
    !empty($settings['menu_hover_color']) ||
    !empty($settings['menu_width']) ||
    !empty($settings['menu_border_radius']) ||
    !empty($settings['modern_menu_style'])
);

echo "<p><strong>hasMenuCustomizations:</strong> " . ($hasMenuCustomizations ? "✅ TRUE" : "❌ FALSE") . "</p>";

if ($hasMenuCustomizations) {
    echo "<p style='color: green;'>✅ Powinny być dodane body classes: mas-v2-menu-custom-enabled</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Brak customizacji - menu pozostaje default WordPress</p>";
}

echo "<h2>🎨 CSS Variables które powinny być ustawione:</h2>";
if (!empty($settings['menu_background']) || !empty($settings['menu_bg'])) {
    $bg = $settings['menu_background'] ?? $settings['menu_bg'];
    echo "<p>--mas-menu-bg-color: {$bg}</p>";
}
if (!empty($settings['menu_text_color'])) {
    echo "<p>--mas-menu-text-color: {$settings['menu_text_color']}</p>";
}
if (!empty($settings['menu_width'])) {
    echo "<p>--mas-menu-width: {$settings['menu_width']}px</p>";
}

echo "<h2>🚀 Szybki test - ustaw podstawowe ustawienia:</h2>";
echo "<p><a href='?set_test_settings=1' style='background: #0073aa; color: white; padding: 10px; text-decoration: none; border-radius: 4px;'>Ustaw testowe ustawienia menu</a></p>";

if (isset($_GET['set_test_settings'])) {
    $test_settings = $settings;
    $test_settings['modern_menu_style'] = true;
    $test_settings['menu_background'] = '#2c3338';
    $test_settings['menu_text_color'] = '#ffffff';
    $test_settings['menu_width'] = 200;
    $test_settings['menu_border_radius'] = 8;
    
    update_option('mas_v2_settings', $test_settings);
    echo "<p style='color: green;'>✅ Ustawienia testowe zostały zapisane! Odśwież stronę wp-admin.</p>";
}

echo "<h2>🔄 Reset do WordPress default:</h2>";
echo "<p><a href='?reset_menu=1' style='background: #dc3232; color: white; padding: 10px; text-decoration: none; border-radius: 4px;'>Resetuj menu do WordPress default</a></p>";

if (isset($_GET['reset_menu'])) {
    $reset_settings = $settings;
    
    // Usuń wszystkie ustawienia menu
    $menu_keys = [
        'modern_menu_style', 'menu_background', 'menu_bg', 'menu_text_color', 
        'menu_hover_background', 'menu_hover_color', 'menu_width', 'menu_border_radius',
        'menu_floating', 'menu_glossy', 'auto_fold_menu', 'menu_icons_enabled'
    ];
    
    foreach ($menu_keys as $key) {
        unset($reset_settings[$key]);
    }
    
    update_option('mas_v2_settings', $reset_settings);
    echo "<p style='color: green;'>✅ Menu zostało zresetowane do WordPress default! Odśwież stronę wp-admin.</p>";
}
?> 