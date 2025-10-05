# MAS V2 - Przewodnik Konserwacji i Rozwoju

## Spis Treści
1. [Wprowadzenie](#1-wprowadzenie)
2. [Architektura Kodu](#2-architektura-kodu)
3. [Dodawanie Nowych Ustawień](#3-dodawanie-nowych-ustawień)
4. [Modyfikacja CSS](#4-modyfikacja-css)
5. [Debugging](#5-debugging)
6. [Najczęstsze Zadania](#6-najczęstsze-zadania)
7. [Bezpieczeństwo](#7-bezpieczeństwo)
8. [Wydajność](#8-wydajność)
9. [Troubleshooting](#9-troubleshooting)

---

## 1. Wprowadzenie

### 1.1 Cel Dokumentu

Ten dokument jest przewodnikiem dla deweloperów, którzy będą utrzymywać i rozwijać plugin Modern Admin Styler V2. Zawiera praktyczne instrukcje, najlepsze praktyki i wskazówki dotyczące modyfikacji kodu.

### 1.2 Wymagania

**Wiedza**:
- PHP 7.4+ (OOP, namespaces)
- WordPress API (hooks, filters, options)
- JavaScript ES6+ (jQuery, AJAX)
- CSS3 (variables, selectors)
- SQL (podstawy)

**Narzędzia**:
- IDE z PHP support (VS Code, PHPStorm)
- Browser DevTools
- Git
- WP-CLI (opcjonalnie)

### 1.3 Struktura Projektu

```
mas3/
├── modern-admin-styler-v2.php    # Główny plik pluginu
├── assets/
│   ├── css/                      # Style CSS
│   │   ├── admin-modern.css      # Główne style
│   │   ├── admin-menu-modern.css # Style menu
│   │   ├── quick-fix.css         # Szybkie poprawki
│   │   └── ...
│   └── js/                       # Skrypty JavaScript
│       ├── admin-settings-simple.js    # Obsługa formularza
│       ├── simple-live-preview.js      # Live preview
│       └── cross-browser-compatibility.js
├── src/
│   └── views/
│       └── admin-page.php        # Template strony ustawień
├── languages/                    # Tłumaczenia
├── tests/                        # Pliki testowe
└── docs/                         # Dokumentacja
```

---

## 2. Architektura Kodu

### 2.1 Główna Klasa

Plugin używa wzorca Singleton:

```php
class ModernAdminStylerV2 {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init();
    }
}
```

**Dlaczego Singleton?**
- Zapewnia jedną instancję pluginu
- Łatwy dostęp z innych części kodu
- Zapobiega konfliktom

### 2.2 Inicjalizacja

```php
private function init() {
    // Rejestracja hooków - TYLKO RAZ!
    add_action('admin_menu', [$this, 'addAdminMenu']);
    add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    
    // AJAX handlers
    add_action('wp_ajax_mas_v2_save_settings', [$this, 'ajaxSaveSettings']);
}
```

**WAŻNE**: Każdy hook rejestrowany tylko raz! Duplikacja powoduje problemy.

### 2.3 Przepływ Danych

```
User Input (Form)
    ↓
JavaScript (admin-settings-simple.js)
    ↓ AJAX
PHP (ajaxSaveSettings)
    ↓
Sanitization & Validation
    ↓
Database (wp_options)
    ↓
CSS Generation (generateMenuCSS)
    ↓
Output (<style> tag)
```

---

## 3. Dodawanie Nowych Ustawień

### 3.1 Krok 1: Dodaj Pole do Formularza

**Lokalizacja**: `src/views/admin-page.php`

```php
<!-- Przykład: Dodanie pola dla koloru tekstu menu -->
<div class="mas-setting-row">
    <label for="menu_text_color">
        <?php _e('Menu Text Color', 'modern-admin-styler-v2'); ?>
    </label>
    <input 
        type="color" 
        id="menu_text_color" 
        name="menu_text_color" 
        value="<?php echo esc_attr($settings['menu_text_color'] ?? '#ffffff'); ?>"
    >
    <p class="description">
        <?php _e('Color of menu item text', 'modern-admin-styler-v2'); ?>
    </p>
</div>
```

**Typy pól**:
- `type="color"` - Color picker
- `type="number"` - Liczba
- `type="range"` - Suwak
- `type="text"` - Tekst
- `type="checkbox"` - Checkbox
- `<select>` - Dropdown

### 3.2 Krok 2: Dodaj Domyślną Wartość

**Lokalizacja**: `modern-admin-styler-v2.php` - metoda `getDefaultSettings()`

```php
private function getDefaultSettings() {
    return [
        // ... istniejące ustawienia
        'menu_text_color' => '#ffffff',  // DODAJ TO
    ];
}
```

### 3.3 Krok 3: Dodaj Sanityzację

**Lokalizacja**: `modern-admin-styler-v2.php` - metoda `sanitizeSettings()`

```php
private function sanitizeSettings($input) {
    $sanitized = [];
    
    // ... istniejące sanityzacje
    
    // DODAJ TO:
    if (isset($input['menu_text_color'])) {
        $sanitized['menu_text_color'] = sanitize_hex_color($input['menu_text_color']);
    }
    
    return $sanitized;
}
```

**Funkcje sanityzacji**:
- `sanitize_hex_color()` - Kolory (#RRGGBB)
- `intval()` - Liczby całkowite
- `floatval()` - Liczby zmiennoprzecinkowe
- `sanitize_text_field()` - Tekst
- `esc_url_raw()` - URL
- `wp_kses_post()` - HTML (bezpieczny)

### 3.4 Krok 4: Dodaj Walidację (Opcjonalnie)

**Lokalizacja**: `modern-admin-styler-v2.php` - metoda `validateSettingsIntegrity()`

```php
private function validateSettingsIntegrity($settings) {
    // DODAJ TO:
    if (isset($settings['menu_text_color'])) {
        // Sprawdź czy to poprawny kolor
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $settings['menu_text_color'])) {
            $settings['menu_text_color'] = '#ffffff'; // Fallback
        }
    }
    
    return $settings;
}
```

### 3.5 Krok 5: Użyj w CSS

**Lokalizacja**: `modern-admin-styler-v2.php` - metoda `generateMenuCSS()`

```php
private function generateMenuCSS($settings) {
    $css = '';
    
    // ... istniejący CSS
    
    // DODAJ TO:
    if (isset($settings['menu_text_color'])) {
        $css .= '#adminmenu a { color: ' . $settings['menu_text_color'] . '; }';
    }
    
    return $css;
}
```

### 3.6 Krok 6: Przetestuj

```bash
# 1. Wyczyść cache
Ctrl+Shift+R

# 2. Przejdź do ustawień
WP Admin → MAS V2 → Settings

# 3. Zmień nowe ustawienie
# 4. Zapisz
# 5. Sprawdź czy działa
```

---

## 4. Modyfikacja CSS

### 4.1 Gdzie Modyfikować CSS?

**Dla ustawień dynamicznych** (zmieniane przez użytkownika):
- Modyfikuj w `generateMenuCSS()` lub podobnych funkcjach
- CSS generowany dynamicznie na podstawie ustawień

**Dla stylów statycznych** (nie zmieniane):
- Modyfikuj pliki w `assets/css/`
- Np. `admin-modern.css`, `admin-menu-modern.css`

### 4.2 Przykład: Dodanie Nowego Stylu Dynamicznego

```php
private function generateMenuCSS($settings) {
    $css = '';
    
    // Nowy styl: zaokrąglone rogi menu
    if (isset($settings['menu_border_radius'])) {
        $radius = intval($settings['menu_border_radius']);
        $css .= "#adminmenu { border-radius: {$radius}px; }";
    }
    
    return $css;
}
```

### 4.3 Przykład: Dodanie Nowego Pliku CSS

```php
public function enqueueGlobalAssets($hook) {
    // Dodaj nowy plik CSS
    wp_enqueue_style(
        'mas-v2-custom-styles',
        MAS_V2_PLUGIN_URL . 'assets/css/custom-styles.css',
        [],
        MAS_V2_VERSION
    );
}
```

### 4.4 CSS Variables

Plugin używa CSS Variables dla łatwiejszej modyfikacji:

```php
private function generateCSSVariables($settings) {
    $css = ':root {';
    $css .= '--mas-menu-bg: ' . ($settings['menu_background'] ?? '#1e1e2e') . ';';
    $css .= '--mas-menu-width: ' . ($settings['menu_width'] ?? 200) . 'px;';
    $css .= '}';
    return $css;
}
```

Użycie w CSS:

```css
#adminmenu {
    background: var(--mas-menu-bg);
    width: var(--mas-menu-width);
}
```

---

## 5. Debugging

### 5.1 Włączenie Debug Mode

**wp-config.php**:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
```

### 5.2 PHP Debugging

```php
// Logowanie do debug.log
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('MAS V2: Debug message');
    error_log('MAS V2: Variable value: ' . print_r($variable, true));
}

// Wyświetlanie zmiennej (tylko w dev!)
echo '<pre>';
var_dump($variable);
echo '</pre>';

// Die and dump (tylko w dev!)
dd($variable);  // Jeśli używasz Laravel-like helpers
```

### 5.3 JavaScript Debugging

```javascript
// Console logging
console.log('Debug message');
console.log('Variable:', variable);
console.error('Error:', error);
console.warn('Warning:', warning);

// Debugger breakpoint
debugger;

// Sprawdzenie czy funkcja istnieje
if (typeof myFunction === 'function') {
    console.log('Function exists');
}
```

### 5.4 AJAX Debugging

**Browser DevTools → Network Tab**:
1. Filtruj po "admin-ajax.php"
2. Kliknij request
3. Sprawdź:
   - **Headers** - Request headers
   - **Payload** - Wysłane dane
   - **Response** - Odpowiedź serwera
   - **Timing** - Czas wykonania

**PHP Side**:
```php
public function ajaxSaveSettings() {
    // Debug: Log request
    error_log('AJAX Request: ' . print_r($_POST, true));
    
    // ... kod ...
    
    // Debug: Log response
    error_log('AJAX Response: ' . print_r($response_data, true));
}
```

### 5.5 SQL Debugging

**Query Monitor Plugin**:
```bash
# Zainstaluj
wp plugin install query-monitor --activate

# Sprawdź w admin bar → Query Monitor
```

**Manual**:
```php
global $wpdb;
$wpdb->show_errors();

$result = $wpdb->get_results("SELECT * FROM wp_options WHERE option_name = 'mas_v2_settings'");
$wpdb->print_error();
```

---

## 6. Najczęstsze Zadania

### 6.1 Zmiana Domyślnych Wartości

**Lokalizacja**: `getDefaultSettings()`

```php
private function getDefaultSettings() {
    return [
        'menu_background' => '#2c3e50',  // Zmień to
        'menu_width' => 250,             // Zmień to
        // ...
    ];
}
```

### 6.2 Dodanie Nowej Zakładki

**Krok 1**: Dodaj przycisk zakładki w `admin-page.php`:
```php
<button class="mas-tab-button" data-tab="my-new-tab">
    <?php _e('My New Tab', 'modern-admin-styler-v2'); ?>
</button>
```

**Krok 2**: Dodaj zawartość zakładki:
```php
<div id="mas-tab-my-new-tab" class="mas-tab-content" style="display:none;">
    <h2><?php _e('My New Tab Settings', 'modern-admin-styler-v2'); ?></h2>
    <!-- Pola formularza -->
</div>
```

**Krok 3**: JavaScript już obsługuje nowe zakładki automatycznie!

### 6.3 Zmiana Koloru Motywu

**Lokalizacja**: `assets/css/admin-modern.css`

```css
/* Zmień główne kolory */
:root {
    --mas-primary-color: #0073aa;    /* Niebieski */
    --mas-secondary-color: #2c3e50;  /* Ciemny */
    --mas-accent-color: #00a0d2;     /* Jasny niebieski */
}
```

### 6.4 Dodanie Nowego AJAX Endpointu

**Krok 1**: Zarejestruj handler w `init()`:
```php
add_action('wp_ajax_mas_v2_my_action', [$this, 'ajaxMyAction']);
```

**Krok 2**: Stwórz metodę:
```php
public function ajaxMyAction() {
    // Walidacja bezpieczeństwa
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mas_v2_nonce')) {
        wp_send_json_error(['message' => 'Security error']);
    }
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }
    
    // Twój kod
    $result = $this->doSomething();
    
    // Odpowiedź
    wp_send_json_success(['data' => $result]);
}
```

**Krok 3**: Wywołaj z JavaScript:
```javascript
$.post(masV2Global.ajaxUrl, {
    action: 'mas_v2_my_action',
    nonce: masV2Global.nonce,
    // ... inne dane
}).done(function(response) {
    if (response.success) {
        console.log('Success:', response.data);
    }
});
```

### 6.5 Zmiana Czasu Debouncing Live Preview

**Lokalizacja**: `assets/js/simple-live-preview.js`

```javascript
// Zmień 300 na inną wartość (w milisekundach)
previewTimeout = setTimeout(function() {
    updateLivePreview();
}, 300);  // ZMIEŃ TO (np. 500 dla wolniejszego, 100 dla szybszego)
```

---

## 7. Bezpieczeństwo

### 7.1 Zawsze Waliduj Input

```php
// ZŁE
$color = $_POST['color'];
update_option('color', $color);

// DOBRE
$color = sanitize_hex_color($_POST['color'] ?? '');
if ($color) {
    update_option('color', $color);
}
```

### 7.2 Zawsze Escapuj Output

```php
// ZŁE
echo $user_input;

// DOBRE
echo esc_html($user_input);        // W HTML
echo esc_attr($user_input);        // W atrybutach
echo esc_url($user_input);         // W URL
echo esc_js($user_input);          // W JavaScript
```

### 7.3 Używaj Nonce

```php
// Generowanie
$nonce = wp_create_nonce('mas_v2_nonce');

// Weryfikacja
if (!wp_verify_nonce($_POST['nonce'], 'mas_v2_nonce')) {
    die('Security check failed');
}
```

### 7.4 Sprawdzaj Uprawnienia

```php
// Sprawdź czy użytkownik ma uprawnienia
if (!current_user_can('manage_options')) {
    wp_die('Insufficient permissions');
}
```

### 7.5 Używaj Prepared Statements

```php
// ZŁE
$wpdb->query("SELECT * FROM table WHERE id = {$_GET['id']}");

// DOBRE
$wpdb->prepare("SELECT * FROM table WHERE id = %d", $_GET['id']);
```

---

## 8. Wydajność

### 8.1 Cachowanie

```php
// Cachuj wyniki kosztownych operacji
$cache_key = 'mas_v2_expensive_operation';
$result = wp_cache_get($cache_key);

if ($result === false) {
    $result = $this->expensiveOperation();
    wp_cache_set($cache_key, $result, '', 3600); // 1 godzina
}

return $result;
```

### 8.2 Minimalizuj Zapytania SQL

```php
// ZŁE - 100 zapytań
for ($i = 0; $i < 100; $i++) {
    get_option("setting_{$i}");
}

// DOBRE - 1 zapytanie
$all_settings = get_option('all_settings');
```

### 8.3 Lazy Loading

```php
// Ładuj zasoby tylko gdy potrzebne
public function enqueueAssets($hook) {
    // Tylko na stronie ustawień
    if (!$this->isPluginPage($hook)) {
        return;
    }
    
    wp_enqueue_script('mas-v2-settings', ...);
}
```

### 8.4 Debouncing w JavaScript

```javascript
// Zapobiega nadmiernemu wywoływaniu funkcji
let timeout;
$input.on('input', function() {
    clearTimeout(timeout);
    timeout = setTimeout(function() {
        expensiveFunction();
    }, 300);
});
```

---

## 9. Troubleshooting

### 9.1 Ustawienia Nie Zapisują Się

**Sprawdź**:
1. Console (F12) - błędy JavaScript?
2. Network tab - AJAX request sukces?
3. debug.log - błędy PHP?
4. Nonce - czy jest poprawny?

**Rozwiązanie**:
```bash
# Uruchom diagnostykę
http://localhost/wp-content/plugins/mas3/test-current-save-status.php
```

### 9.2 CSS Nie Jest Generowany

**Sprawdź**:
1. Czy `generateMenuCSS()` zwraca niepusty string?
2. Czy ustawienia są przekazywane do funkcji?
3. Czy plugin jest włączony?

**Test**:
```php
$settings = get_option('mas_v2_settings', []);
$plugin = ModernAdminStylerV2::getInstance();
$css = $plugin->generateMenuCSS($settings);
var_dump(strlen($css)); // Powinno być > 50
```

### 9.3 Live Preview Nie Działa

**Sprawdź**:
1. Czy `simple-live-preview.js` jest załadowany?
2. Console - błędy JavaScript?
3. Network - AJAX request działa?

**Test**:
```javascript
// W console
console.log(typeof updateLivePreview); // Powinno być 'function'
```

### 9.4 Konflikt z Innym Pluginem

**Sprawdź**:
1. Dezaktywuj inne pluginy jeden po drugim
2. Sprawdź console - błędy JavaScript?
3. Sprawdź debug.log - błędy PHP?

**Rozwiązanie**:
```php
// Dodaj namespace do funkcji
if (!function_exists('mas_v2_my_function')) {
    function mas_v2_my_function() {
        // ...
    }
}
```

### 9.5 Błąd "Memory Exhausted"

**Rozwiązanie**:
```php
// wp-config.php
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');
```

---

## 10. Checklist Przed Commitem

- [ ] Kod sformatowany (PSR-12)
- [ ] Brak syntax errors
- [ ] Brak PHP warnings/notices
- [ ] Brak JavaScript errors w console
- [ ] Wszystkie funkcje przetestowane
- [ ] Dokumentacja zaktualizowana
- [ ] Changelog zaktualizowany
- [ ] Wersja zaktualizowana (jeśli release)
- [ ] Logi czyste (brak błędów)
- [ ] Testy przechodzą

---

## 11. Przydatne Komendy

### Git
```bash
# Status
git status

# Commit
git add .
git commit -m "Fix: Description of fix"

# Push
git push origin main
```

### WP-CLI
```bash
# Aktywuj plugin
wp plugin activate modern-admin-styler-v2

# Sprawdź opcje
wp option get mas_v2_settings

# Wyczyść cache
wp cache flush

# Sprawdź wersję WP
wp core version
```

### Debugging
```bash
# Ogląd logów na żywo
tail -f wp-content/debug.log

# Szukaj błędów
grep "MAS V2" wp-content/debug.log

# Wyczyść logi
> wp-content/debug.log
```

---

## 12. Zasoby

### Dokumentacja
- [WordPress Codex](https://codex.wordpress.org/)
- [WordPress Developer Resources](https://developer.wordpress.org/)
- [PHP Manual](https://www.php.net/manual/en/)
- [MDN Web Docs](https://developer.mozilla.org/)

### Narzędzia
- [Query Monitor](https://wordpress.org/plugins/query-monitor/)
- [Debug Bar](https://wordpress.org/plugins/debug-bar/)
- [WP-CLI](https://wp-cli.org/)

### Społeczność
- [WordPress Stack Exchange](https://wordpress.stackexchange.com/)
- [WordPress Support Forums](https://wordpress.org/support/)

---

**Wersja dokumentu**: 1.0  
**Data utworzenia**: 2025-01-06  
**Ostatnia aktualizacja**: 2025-01-06  
**Autor**: MAS V2 Development Team

**Pytania?** Sprawdź inne dokumenty:
- `DOCUMENTATION_SETTINGS_SAVE_SYSTEM.md` - System zapisu
- `TESTING_PROCEDURES.md` - Procedury testowania
- `TROUBLESHOOTING.md` - Rozwiązywanie problemów
