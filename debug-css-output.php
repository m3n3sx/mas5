<?php
/**
 * Debug CSS Output - sprawdza czy opcje generują CSS
 * Użyj: /wp-admin/admin.php?page=mas-v2-settings&debug_css=1
 */

if (isset($_GET['debug_css']) && $_GET['debug_css'] == '1' && current_user_can('manage_options')) {
    
    echo "<style>
        .debug-container { max-width: 1200px; margin: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px; }
        .debug-section { margin: 20px 0; padding: 15px; background: white; border-radius: 5px; }
        .debug-css { background: #f1f3f4; padding: 15px; border-radius: 4px; font-family: monospace; white-space: pre-wrap; overflow-x: auto; }
        .debug-option { display: flex; justify-content: space-between; padding: 8px; border-bottom: 1px solid #eee; }
        .debug-success { color: #28a745; font-weight: bold; }
        .debug-error { color: #dc3545; font-weight: bold; }
    </style>";
    
    echo "<div class='debug-container'>";
    echo "<h1>🐛 Debug CSS Output - Modern Admin Styler V2</h1>";
    
    // Pobierz aktualne ustawienia
    $masInstance = ModernAdminStylerV2::getInstance();
    $currentSettings = $masInstance->getSettings();
    
    echo "<div class='debug-section'>";
    echo "<h2>📋 Aktualne ustawienia</h2>";
    
    $importantOptions = [
        'enable_animations', 'animation_type', 'animation_speed',
        'enable_shadows', 'shadow_color', 'shadow_blur',
        'global_border_radius', 'global_box_shadow',
        'primary_button_bg', 'secondary_button_bg',
        'hide_wp_logo', 'hide_howdy', 'compact_mode'
    ];
    
    foreach ($importantOptions as $option) {
        $value = isset($currentSettings[$option]) ? $currentSettings[$option] : 'BRAK';
        $valueDisplay = is_bool($value) ? ($value ? 'true' : 'false') : $value;
        echo "<div class='debug-option'>";
        echo "<span><strong>{$option}:</strong></span>";
        echo "<span>{$valueDisplay}</span>";
        echo "</div>";
    }
    echo "</div>";
    
    // Test generowania CSS
    echo "<div class='debug-section'>";
    echo "<h2>🎨 Wygenerowany CSS</h2>";
    
    try {
        // Użyj refleksji do wywołania prywatnej metody
        $reflection = new ReflectionClass($masInstance);
        $method = $reflection->getMethod('generateAdminCSS');
        $method->setAccessible(true);
        $generatedCSS = $method->invoke($masInstance, $currentSettings);
        
        if (empty($generatedCSS)) {
            echo "<div class='debug-error'>❌ Brak wygenerowanego CSS!</div>";
        } else {
            echo "<div class='debug-success'>✅ CSS został wygenerowany (" . strlen($generatedCSS) . " znaków)</div>";
            echo "<div class='debug-css'>" . htmlspecialchars($generatedCSS) . "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='debug-error'>❌ Błąd generowania CSS: " . $e->getMessage() . "</div>";
    }
    
    echo "</div>";
    
    // Test poszczególnych funkcji CSS
    echo "<div class='debug-section'>";
    echo "<h2>🔧 Test poszczególnych funkcji CSS</h2>";
    
    $cssFunctions = [
        'generateCSSVariables',
        'generateAdminBarCSS', 
        'generateMenuCSS',
        'generateContentCSS',
        'generateButtonCSS',
        'generateFormCSS',
        'generateAdvancedCSS',
        'generateEffectsCSS'
    ];
    
    foreach ($cssFunctions as $functionName) {
        try {
            $method = $reflection->getMethod($functionName);
            $method->setAccessible(true);
            $css = $method->invoke($masInstance, $currentSettings);
            
            $cssLength = strlen($css);
            if ($cssLength > 0) {
                echo "<div class='debug-success'>✅ {$functionName}: {$cssLength} znaków</div>";
            } else {
                echo "<div class='debug-error'>❌ {$functionName}: brak CSS</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='debug-error'>❌ {$functionName}: błąd - " . $e->getMessage() . "</div>";
        }
    }
    
    echo "</div>";
    
    // Test z przykładowymi ustawieniami
    echo "<div class='debug-section'>";
    echo "<h2>🧪 Test z przykładowymi ustawieniami</h2>";
    
    $testSettings = [
        'enable_animations' => false,
        'global_border_radius' => 15,
        'enable_shadows' => true,
        'shadow_color' => '#ff0000',
        'primary_button_bg' => '#00ff00',
        'compact_mode' => true,
        'hide_wp_logo' => true
    ];
    
    try {
        $testCSS = $method->invoke($masInstance, $testSettings);
        
        echo "<div class='debug-option'>";
        echo "<span><strong>Test settings CSS length:</strong></span>";
        echo "<span>" . strlen($testCSS) . " znaków</span>";
        echo "</div>";
        
        // Sprawdź czy zawiera oczekiwane fragmenty
        $expectedFragments = [
            'animation-duration: 0s' => 'Wyłączone animacje',
            'border-radius: 15px' => 'Globalne zaokrąglenia',
            'rgba(255, 0, 0' => 'Czerwone cienie',
            'background: #00ff00' => 'Zielone przyciski',
            'padding: 10px' => 'Tryb kompaktowy',
            '#wp-admin-bar-wp-logo' => 'Ukrycie logo WP'
        ];
        
        foreach ($expectedFragments as $fragment => $description) {
            if (strpos($testCSS, $fragment) !== false) {
                echo "<div class='debug-success'>✅ {$description}: ZNALEZIONO</div>";
            } else {
                echo "<div class='debug-error'>❌ {$description}: BRAK</div>";
            }
        }
        
        if (!empty($testCSS)) {
            echo "<h3>Wygenerowany test CSS:</h3>";
            echo "<div class='debug-css'>" . htmlspecialchars($testCSS) . "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='debug-error'>❌ Błąd testu CSS: " . $e->getMessage() . "</div>";
    }
    
    echo "</div>";
    echo "</div>";
    
    exit; // Zatrzymaj dalsze wykonywanie
}
?> 