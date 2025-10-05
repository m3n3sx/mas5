<?php
/**
 * Test sprawdzający czy ustawienia są zapisane w bazie danych
 */

// Load WordPress
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Brak uprawnień');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>MAS Settings Check</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #fff; }
        .test { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .pass { background: #2d5016; border-left: 4px solid #4caf50; }
        .fail { background: #5c1a1a; border-left: 4px solid #f44336; }
        .info { background: #1a3a5c; border-left: 4px solid #2196f3; }
        h1 { color: #4fc3f7; }
        h2 { color: #81c784; margin-top: 30px; }
        pre { background: #000; padding: 10px; border-radius: 3px; overflow-x: auto; max-height: 400px; overflow-y: auto; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #444; }
        th { background: #2a2a2a; color: #4fc3f7; }
        .empty { color: #f44336; font-style: italic; }
    </style>
</head>
<body>
    <h1>🔍 MAS Settings Database Check</h1>
    
    <?php
    // Get settings from database
    $settings = get_option('mas_v2_settings', []);
    $settings_count = is_array($settings) ? count($settings) : 0;
    
    echo '<h2>1. Settings Overview</h2>';
    
    if (empty($settings)) {
        echo '<div class="test fail">✗ No settings found in database (mas_v2_settings option is empty)</div>';
        echo '<div class="test info">ℹ This is why the plugin shows "BRAK USTAWIEŃ MENU!"</div>';
        echo '<div class="test info">ℹ You need to configure and save settings first</div>';
    } else {
        echo '<div class="test pass">✓ Settings found: ' . $settings_count . ' options</div>';
    }
    
    echo '<h2>2. Menu Settings</h2>';
    
    $menu_keys = [
        'menu_background',
        'menu_text_color',
        'menu_hover_background',
        'menu_hover_text_color',
        'menu_active_background',
        'menu_active_text_color',
        'menu_width',
        'menu_item_height',
        'menu_border_radius_all',
        'menu_detached',
        'menu_floating',
        'submenu_background',
        'submenu_text_color'
    ];
    
    echo '<table>';
    echo '<tr><th>Setting Key</th><th>Value</th><th>Status</th></tr>';
    
    foreach ($menu_keys as $key) {
        $value = isset($settings[$key]) ? $settings[$key] : null;
        $status = !empty($value) ? 'pass' : 'fail';
        $display_value = !empty($value) ? htmlspecialchars(print_r($value, true)) : '<span class="empty">not set</span>';
        
        echo '<tr>';
        echo '<td>' . htmlspecialchars($key) . '</td>';
        echo '<td>' . $display_value . '</td>';
        echo '<td class="' . $status . '">' . (!empty($value) ? '✓' : '✗') . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    
    echo '<h2>3. All Settings (Raw Data)</h2>';
    echo '<pre>' . htmlspecialchars(print_r($settings, true)) . '</pre>';
    
    echo '<h2>4. Database Option Info</h2>';
    
    global $wpdb;
    $option_row = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->options} WHERE option_name = %s",
        'mas_v2_settings'
    ));
    
    if ($option_row) {
        echo '<div class="test pass">✓ Option exists in database</div>';
        echo '<table>';
        echo '<tr><th>Field</th><th>Value</th></tr>';
        echo '<tr><td>option_id</td><td>' . $option_row->option_id . '</td></tr>';
        echo '<tr><td>option_name</td><td>' . htmlspecialchars($option_row->option_name) . '</td></tr>';
        echo '<tr><td>autoload</td><td>' . htmlspecialchars($option_row->autoload) . '</td></tr>';
        echo '<tr><td>option_value length</td><td>' . strlen($option_row->option_value) . ' bytes</td></tr>';
        echo '</table>';
    } else {
        echo '<div class="test fail">✗ Option does not exist in database</div>';
        echo '<div class="test info">ℹ The plugin has never been configured</div>';
    }
    
    echo '<h2>5. Recommendations</h2>';
    
    if (empty($settings)) {
        echo '<div class="test info">';
        echo '<strong>To fix "BRAK USTAWIEŃ MENU!" error:</strong><br>';
        echo '1. Go to WordPress Admin → MAS V2 → Menu<br>';
        echo '2. Configure at least one menu setting (e.g., menu background color)<br>';
        echo '3. Click "Zapisz ustawienia" (Save Settings)<br>';
        echo '4. Refresh the page<br>';
        echo '</div>';
    } else {
        $has_menu_settings = false;
        foreach ($menu_keys as $key) {
            if (!empty($settings[$key])) {
                $has_menu_settings = true;
                break;
            }
        }
        
        if (!$has_menu_settings) {
            echo '<div class="test info">';
            echo '<strong>Settings exist but no menu customizations:</strong><br>';
            echo 'Configure menu settings to see customizations applied.<br>';
            echo '</div>';
        } else {
            echo '<div class="test pass">';
            echo '✓ Menu settings are configured<br>';
            echo 'If customizations are not visible, check browser console for JavaScript errors.<br>';
            echo '</div>';
        }
    }
    
    ?>
    
    <h2>6. Quick Actions</h2>
    <div class="test info">
        <a href="<?php echo admin_url('admin.php?page=mas-v2-menu'); ?>" style="color: #4fc3f7;">→ Go to Menu Settings</a><br>
        <a href="<?php echo admin_url('admin.php?page=mas-v2-general'); ?>" style="color: #4fc3f7;">→ Go to General Settings</a><br>
        <a href="<?php echo admin_url('options-general.php'); ?>" style="color: #4fc3f7;">→ Go to WordPress Settings</a>
    </div>
    
</body>
</html>
