<?php
/**
 * Force Default Settings - Emergency Script
 * Wymusza zapisanie domyślnych ustawień MAS V2
 */

// Load WordPress
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Brak uprawnień');
}

// Get current settings
$current_settings = get_option('mas_v2_settings', []);

echo '<h1>Force Default Settings</h1>';
echo '<p>Current settings count: ' . count($current_settings) . '</p>';

if (isset($_GET['force']) && $_GET['force'] === 'yes') {
    // Force default settings
    $defaults = [
        // Ogólne
        'enable_plugin' => true,
        'theme' => 'modern',
        'color_scheme' => 'light',
        'live_preview' => true,
        
        // Admin Bar
        'custom_admin_bar_style' => true,
        'admin_bar_background' => '#23282d',
        'admin_bar_text_color' => '#ffffff',
        'admin_bar_floating' => false,
        
        // Menu - PODSTAWOWE USTAWIENIA
        'menu_background' => '#23282d',
        'menu_text_color' => '#ffffff',
        'menu_hover_background' => '#32373c',
        'menu_hover_text_color' => '#00a0d2',
        'menu_active_background' => '#0073aa',
        'menu_active_text_color' => '#ffffff',
        'menu_width' => 160,
        'menu_item_height' => 34,
        'menu_floating' => false,
        'menu_detached' => false,
        
        // Submenu
        'submenu_background' => '#2c3338',
        'submenu_text_color' => '#ffffff',
        'submenu_hover_background' => '#32373c',
        'submenu_hover_text_color' => '#00a0d2',
    ];
    
    update_option('mas_v2_settings', $defaults);
    
    echo '<div style="background: #4caf50; color: white; padding: 15px; margin: 20px 0; border-radius: 5px;">';
    echo '✓ Domyślne ustawienia zostały zapisane!';
    echo '</div>';
    
    echo '<p><a href="' . admin_url('admin.php?page=mas-v2-menu') . '">→ Przejdź do ustawień menu</a></p>';
    echo '<p><a href="' . admin_url() . '">→ Przejdź do panelu admina</a></p>';
    
} else {
    echo '<div style="background: #ff9800; color: white; padding: 15px; margin: 20px 0; border-radius: 5px;">';
    echo '⚠ To wymusi zapisanie domyślnych ustawień. Obecne ustawienia zostaną nadpisane.';
    echo '</div>';
    
    echo '<p><a href="?force=yes" style="background: #f44336; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">Wymuś domyślne ustawienia</a></p>';
    echo '<p><a href="' . admin_url() . '">← Anuluj</a></p>';
}
?>
