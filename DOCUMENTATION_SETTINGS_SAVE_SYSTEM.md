# MAS V2 - Dokumentacja Systemu Zapisu Ustawień

## Przegląd

System zapisu ustawień w Modern Admin Styler V2 składa się z trzech głównych komponentów:
1. **Frontend (JavaScript)** - Zbiera dane z formularza i wysyła przez AJAX
2. **Backend (PHP)** - Przetwarza, waliduje i zapisuje ustawienia
3. **CSS Generation** - Generuje style na podstawie zapisanych ustawień

---

## 1. Frontend - JavaScript (admin-settings-simple.js)

### Lokalizacja
`assets/js/admin-settings-simple.js`

### Funkcjonalność

#### Obsługa Formularza
```javascript
$('#mas-v2-settings-form').on('submit', function(e) {
    e.preventDefault();
    
    // Zbierz dane formularza
    const formData = $form.serializeArray();
    const postData = {
        action: 'mas_v2_save_settings',
        nonce: masV2Global.nonce
    };
    
    // Dodaj wszystkie pola do postData
    $.each(formData, function(i, field) {
        postData[field.name] = field.value;
    });
    
    // Wyślij przez AJAX
    $.post(masV2Global.ajaxUrl, postData)
        .done(function(response) { /* ... */ })
        .fail(function() { /* ... */ });
});
```

### Kluczowe Punkty

1. **serializeArray()** - Konwertuje formularz na tablicę obiektów `{name, value}`
2. **Rozpakowanie danych** - Każde pole jest dodawane osobno do `postData`
3. **Format wysyłanych danych**:
   ```javascript
   {
       action: 'mas_v2_save_settings',
       nonce: 'abc123...',
       menu_background: '#ff0000',
       menu_width: '250',
       enable_plugin: 'on',
       // ... wszystkie inne pola
   }
   ```

### Dlaczego Ten Format?

**WAŻNE**: PHP oczekuje danych w formacie `$_POST['menu_background']`, a nie `$_POST['settings']['menu_background']`.

**ZŁY sposób** (nie używamy):
```javascript
settings: $form.serialize()  // Wysyła STRING: "menu_background=#ff0000&menu_width=250"
```

**DOBRY sposób** (używamy):
```javascript
// Każde pole osobno w $_POST
postData['menu_background'] = '#ff0000';
postData['menu_width'] = '250';
```

---

## 2. Backend - PHP (modern-admin-styler-v2.php)

### Lokalizacja
`modern-admin-styler-v2.php` - metoda `ajaxSaveSettings()`

### Przepływ Danych

```
1. Walidacja bezpieczeństwa
   ↓
2. Walidacja danych wejściowych
   ↓
3. Utworzenie backupu
   ↓
4. Sanityzacja ustawień
   ↓
5. Walidacja integralności
   ↓
6. Zapis do bazy danych
   ↓
7. Weryfikacja zapisu
   ↓
8. Test generowania CSS
   ↓
9. Czyszczenie cache
   ↓
10. Odpowiedź JSON
```

### Kluczowe Funkcje

#### 2.1 Walidacja Bezpieczeństwa
```php
$security_result = $this->validateAjaxSecurity('save_settings');
if (!$security_result['valid']) {
    wp_send_json_error($security_result['error']);
    return;
}
```

Sprawdza:
- Nonce (token bezpieczeństwa)
- Uprawnienia użytkownika (`manage_options`)
- Typ żądania (AJAX)

#### 2.2 Sanityzacja Ustawień
```php
$settings = $this->sanitizeSettingsWithErrorTracking($_POST, $sanitization_errors);
```

Dla każdego pola:
- **Kolory**: `sanitize_hex_color()` - zapewnia format `#RRGGBB`
- **Liczby**: `intval()` lub `floatval()`
- **Tekst**: `sanitize_text_field()`
- **Boolean**: Konwersja `'on'` → `true`, brak → `false`
- **URL**: `esc_url_raw()`

#### 2.3 Walidacja Integralności
```php
$settings = $this->validateSettingsIntegrityWithErrors($settings, $validation_errors);
```

Sprawdza:
- Zakresy wartości (np. szerokość menu 100-400px)
- Poprawność formatów
- Zależności między ustawieniami
- Bezpieczeństwo (XSS, SQL injection)

#### 2.4 Bezpieczny Zapis
```php
$save_result = $this->secureStoreSettings($settings);
```

- Używa `update_option()` z WordPress API
- Automatyczne escapowanie
- Transakcje bazodanowe
- Weryfikacja po zapisie

#### 2.5 Backup System
```php
$backup_key = 'mas_v2_settings_backup_' . time();
update_option($backup_key, $current_settings, false);
```

- Automatyczny backup przed każdym zapisem
- Przechowuje ostatnie 5 backupów
- Możliwość przywrócenia w razie błędu

### Obsługa Błędów

#### Błąd Krytyczny
```php
if (!empty($validation_errors['critical'])) {
    wp_send_json_error([
        'message' => 'Critical validation errors...',
        'code' => 'validation_failed',
        'errors' => $validation_errors['critical']
    ]);
}
```

#### Przywracanie z Backupu
```php
if (!$save_verified) {
    update_option('mas_v2_settings', $current_settings);
    wp_send_json_error([
        'message' => 'Failed to save... Settings restored from backup.',
        'code' => 'save_failed'
    ]);
}
```

---

## 3. Generowanie CSS

### Lokalizacja
`modern-admin-styler-v2.php` - metoda `outputCustomStyles()`

### Przepływ

```
1. Pobierz ustawienia z bazy
   ↓
2. Sprawdź czy plugin włączony
   ↓
3. Generuj CSS Variables
   ↓
4. Generuj CSS dla Menu
   ↓
5. Generuj CSS dla Admin Bar
   ↓
6. Generuj CSS dla Content
   ↓
7. Generuj CSS dla Buttons
   ↓
8. Generuj CSS dla Forms
   ↓
9. Generuj Advanced CSS
   ↓
10. Wstaw do <head>
```

### Funkcje Generujące

#### 3.1 CSS Variables
```php
private function generateCSSVariables($settings) {
    $css = ':root {';
    $css .= '--mas-menu-bg: ' . ($settings['menu_background'] ?? '#1e1e2e') . ';';
    $css .= '--mas-menu-width: ' . ($settings['menu_width'] ?? 200) . 'px;';
    // ... więcej zmiennych
    $css .= '}';
    return $css;
}
```

#### 3.2 Menu CSS
```php
private function generateMenuCSS($settings) {
    $css = '';
    
    // Tło menu
    if (isset($settings['menu_background'])) {
        $css .= '#adminmenu { background: ' . $settings['menu_background'] . '; }';
    }
    
    // Szerokość menu
    if (isset($settings['menu_width'])) {
        $css .= '#adminmenu { width: ' . $settings['menu_width'] . 'px; }';
    }
    
    // ... więcej stylów
    
    return $css;
}
```

### Optymalizacja

#### Brak Duplikacji
**PRZED (ZŁE)**:
```php
$css_variables = $this->generateCSSVariables($settings);
$admin_css = $this->generateAdminCSS($settings);  // To ZNOWU wywołuje generateCSSVariables!
```

**PO (DOBRE)**:
```php
$css = '';
$css .= $this->generateCSSVariables($settings);
$css .= $this->generateMenuCSS($settings);
$css .= $this->generateAdminBarCSS($settings);
// ... każda funkcja wywołana tylko raz
```

---

## 4. Live Preview System

### Lokalizacja
`assets/js/simple-live-preview.js`

### Funkcjonalność

```javascript
// Nasłuchuj zmian w formularzu
$('#mas-v2-settings-form').on('change input', ':input', function() {
    clearTimeout(previewTimeout);
    previewTimeout = setTimeout(function() {
        updateLivePreview();
    }, 300);  // Debouncing 300ms
});

function updateLivePreview() {
    // Pobierz dane formularza
    const formData = $form.serializeArray();
    const postData = {
        action: 'mas_v2_get_preview_css',
        nonce: masV2Global.nonce
    };
    
    // Wyślij AJAX
    $.post(masV2Global.ajaxUrl, postData)
        .done(function(response) {
            if (response.success && response.data.css) {
                // Wstaw CSS do <style> tag
                $('#mas-live-preview-styles').html(response.data.css);
            }
        });
}
```

### Debouncing

Zapobiega nadmiernemu wysyłaniu requestów:
- Czeka 300ms po ostatniej zmianie
- Jeśli użytkownik zmienia wartość szybko, wysyła tylko jeden request
- Oszczędza zasoby serwera i poprawia wydajność

---

## 5. Bezpieczeństwo

### 5.1 Nonce Verification
```php
if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mas_v2_nonce')) {
    wp_send_json_error(['message' => 'Security error']);
}
```

### 5.2 Capability Check
```php
if (!current_user_can('manage_options')) {
    wp_send_json_error(['message' => 'Insufficient permissions']);
}
```

### 5.3 Input Sanitization
```php
// Kolory
$color = sanitize_hex_color($input);

// Liczby
$number = intval($input);

// Tekst
$text = sanitize_text_field($input);

// URL
$url = esc_url_raw($input);
```

### 5.4 Output Escaping
```php
// W HTML
echo esc_html($value);

// W atrybutach
echo esc_attr($value);

// W CSS
echo esc_css($value);
```

### 5.5 SQL Injection Prevention
WordPress automatycznie escapuje dane w `update_option()` i `get_option()`.

---

## 6. Diagnostyka i Debugging

### 6.1 Narzędzia Diagnostyczne

#### test-current-save-status.php
Kompleksowy test systemu zapisu:
- Sprawdza czy ustawienia istnieją
- Testuje zapis do bazy
- Weryfikuje generowanie CSS
- Pokazuje aktualne ustawienia
- Listuje backupy

#### test-save-settings.php
Prosty test zapisu pojedynczego ustawienia.

### 6.2 Debug Logging

```php
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('MAS V2: ajaxSaveSettings called with ' . count($_POST) . ' POST values');
    error_log('MAS V2: Settings save successful. Count: ' . count($settings));
}
```

Logi zapisywane w `wp-content/debug.log`.

### 6.3 Console Logging (JavaScript)

```javascript
console.log('🎯 MAS Simple Settings: Initializing...');
console.log('✅ MAS Simple Settings: Initialized');
console.error('❌ Error:', error);
```

### 6.4 Browser DevTools

**Network Tab**:
- Sprawdź request do `admin-ajax.php`
- Zobacz wysyłane dane (Payload)
- Zobacz odpowiedź serwera (Response)

**Console Tab**:
- Sprawdź błędy JavaScript
- Zobacz logi z `console.log()`

---

## 7. Typowe Problemy i Rozwiązania

### Problem 1: Ustawienia nie zapisują się

**Objawy**: Kliknięcie "Zapisz" nie zmienia ustawień

**Możliwe przyczyny**:
1. Błąd JavaScript - sprawdź console (F12)
2. Błąd AJAX - sprawdź Network tab
3. Błąd PHP - sprawdź `debug.log`
4. Nieprawidłowy nonce - odśwież stronę
5. Brak uprawnień - zaloguj jako admin

**Rozwiązanie**:
```bash
# 1. Uruchom test diagnostyczny
http://localhost/wp-content/plugins/mas3/test-current-save-status.php

# 2. Sprawdź logi
tail -f wp-content/debug.log

# 3. Wyczyść cache
Ctrl+Shift+R (hard refresh)
```

### Problem 2: CSS nie jest generowany

**Objawy**: Ustawienia zapisane ale style nie działają

**Możliwe przyczyny**:
1. Funkcja `generateMenuCSS()` zwraca pusty string
2. Ustawienia nie są przekazywane do funkcji
3. Plugin wyłączony (`enable_plugin = false`)

**Rozwiązanie**:
```php
// Sprawdź czy CSS jest generowany
$settings = get_option('mas_v2_settings', []);
$plugin = ModernAdminStylerV2::getInstance();
$css = $plugin->generateMenuCSS($settings);
echo strlen($css);  // Powinno być > 50
```

### Problem 3: "Invalid request data detected"

**Objawy**: AJAX zwraca błąd walidacji

**Możliwe przyczyny**:
1. Zbyt restrykcyjna walidacja kluczy
2. Nieprawidłowy format danych
3. Brakujące pola wymagane

**Rozwiązanie**:
Uproszczono `isValidSettingKey()` aby akceptować wszystkie bezpieczne klucze:
```php
private function isValidSettingKey($key) {
    // Akceptuj wszystkie klucze pasujące do wzorca
    return preg_match('/^[a-zA-Z0-9_-]+$/', $key);
}
```

### Problem 4: Live Preview nie działa

**Objawy**: Zmiany nie są widoczne na żywo

**Możliwe przyczyny**:
1. Plik `simple-live-preview.js` nie jest załadowany
2. Błąd JavaScript
3. AJAX endpoint nie odpowiada

**Rozwiązanie**:
```javascript
// Sprawdź czy skrypt jest załadowany
console.log(typeof updateLivePreview);  // Powinno być 'function'

// Sprawdź czy AJAX działa
$.post(masV2Global.ajaxUrl, {
    action: 'mas_v2_get_preview_css',
    nonce: masV2Global.nonce
}).done(function(response) {
    console.log(response);
});
```

---

## 8. Najlepsze Praktyki

### 8.1 Zawsze Twórz Backup
```php
$backup_key = 'mas_v2_settings_backup_' . time();
update_option($backup_key, $current_settings, false);
```

### 8.2 Waliduj Wszystko
```php
// Przed zapisem
$settings = $this->sanitizeSettings($_POST);
$settings = $this->validateSettingsIntegrity($settings);

// Po zapisie
$saved = get_option('mas_v2_settings', []);
if (count($saved) < 10) {
    // Przywróć backup
}
```

### 8.3 Używaj Debouncing
```javascript
let timeout;
$input.on('input', function() {
    clearTimeout(timeout);
    timeout = setTimeout(function() {
        // Wykonaj akcję
    }, 300);
});
```

### 8.4 Loguj Ważne Operacje
```php
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('MAS V2: Important operation completed');
}
```

### 8.5 Testuj Po Każdej Zmianie
```bash
# Po każdej modyfikacji kodu
1. Wyczyść cache (Ctrl+Shift+R)
2. Sprawdź console (F12)
3. Przetestuj zapis ustawień
4. Sprawdź czy CSS działa
```

---

## 9. Architektura Systemu

```
┌─────────────────────────────────────────────────────────┐
│                    UŻYTKOWNIK                            │
│                  (WordPress Admin)                       │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│              FRONTEND (JavaScript)                       │
│  ┌──────────────────────────────────────────────────┐  │
│  │  admin-settings-simple.js                        │  │
│  │  - Obsługa formularza                            │  │
│  │  - Zbieranie danych (serializeArray)             │  │
│  │  - Wysyłanie AJAX                                │  │
│  └──────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────┐  │
│  │  simple-live-preview.js                          │  │
│  │  - Nasłuchiwanie zmian                           │  │
│  │  - Debouncing (300ms)                            │  │
│  │  - Aktualizacja CSS na żywo                      │  │
│  └──────────────────────────────────────────────────┘  │
└────────────────────┬────────────────────────────────────┘
                     │ AJAX Request
                     ▼
┌─────────────────────────────────────────────────────────┐
│              BACKEND (PHP)                               │
│  ┌──────────────────────────────────────────────────┐  │
│  │  ajaxSaveSettings()                              │  │
│  │  1. Walidacja bezpieczeństwa (nonce, caps)      │  │
│  │  2. Walidacja danych wejściowych                 │  │
│  │  3. Backup aktualnych ustawień                   │  │
│  │  4. Sanityzacja ($_POST → clean data)           │  │
│  │  5. Walidacja integralności                      │  │
│  │  6. Zapis do bazy (update_option)                │  │
│  │  7. Weryfikacja zapisu                           │  │
│  │  8. Test generowania CSS                         │  │
│  │  9. Czyszczenie cache                            │  │
│  │  10. Odpowiedź JSON                              │  │
│  └──────────────────────────────────────────────────┘  │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│              BAZA DANYCH (WordPress)                     │
│  ┌──────────────────────────────────────────────────┐  │
│  │  wp_options                                      │  │
│  │  - mas_v2_settings (główne ustawienia)          │  │
│  │  - mas_v2_settings_backup_* (backupy)           │  │
│  └──────────────────────────────────────────────────┘  │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│              CSS GENERATION                              │
│  ┌──────────────────────────────────────────────────┐  │
│  │  outputCustomStyles()                            │  │
│  │  1. Pobierz ustawienia z bazy                    │  │
│  │  2. Sprawdź czy plugin włączony                  │  │
│  │  3. Generuj CSS Variables                        │  │
│  │  4. Generuj Menu CSS                             │  │
│  │  5. Generuj Admin Bar CSS                        │  │
│  │  6. Generuj Content CSS                          │  │
│  │  7. Generuj Button CSS                           │  │
│  │  8. Generuj Form CSS                             │  │
│  │  9. Generuj Advanced CSS                         │  │
│  │  10. Wstaw do <head>                             │  │
│  └──────────────────────────────────────────────────┘  │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│              WYNIK (Stylowany Admin)                     │
└─────────────────────────────────────────────────────────┘
```

---

## 10. Historia Napraw

### Naprawa 1: Syntax Error (2025-01-05)
**Problem**: Duplikacja kodu CSS w `generateMenuCSS()`
**Rozwiązanie**: Usunięto duplikat

### Naprawa 2: Duplikacja Hooków (2025-01-05)
**Problem**: Hooki rejestrowane 2x (`init()` i `initLegacyMode()`)
**Rozwiązanie**: Usunięto `initLegacyMode()`

### Naprawa 3: ModernAdminApp Timeout (2025-01-05)
**Problem**: Skomplikowany system modułowy nie działał
**Rozwiązanie**: Wyłączono, zastąpiono prostym systemem

### Naprawa 4: Duplikacja CSS (2025-01-06)
**Problem**: `outputCustomStyles()` wywoływał funkcje wielokrotnie
**Rozwiązanie**: Każda funkcja wywołana tylko raz

### Naprawa 5: Restrykcyjna Walidacja (2025-01-06)
**Problem**: `isValidSettingKey()` odrzucał poprawne klucze
**Rozwiązanie**: Uproszczono walidację

### Naprawa 6: Nieprawidłowe Wysyłanie AJAX (2025-01-06)
**Problem**: `serialize()` wysyłał string zamiast obiektów
**Rozwiązanie**: Zmieniono na `serializeArray()` z rozpakowaniem

---

## 11. Kontakt i Wsparcie

### Logi
- PHP: `wp-content/debug.log`
- JavaScript: Console (F12)

### Narzędzia
- `test-current-save-status.php` - Kompleksowa diagnostyka
- `test-save-settings.php` - Prosty test zapisu
- Browser DevTools (F12) - Network, Console

### Dokumentacja
- Ten plik: `DOCUMENTATION_SETTINGS_SAVE_SYSTEM.md`
- `FINAL_FIX_SUMMARY.md` - Podsumowanie napraw
- `TROUBLESHOOTING.md` - Rozwiązywanie problemów

---

**Wersja dokumentu**: 1.0  
**Data utworzenia**: 2025-01-06  
**Ostatnia aktualizacja**: 2025-01-06  
**Autor**: MAS V2 Development Team
