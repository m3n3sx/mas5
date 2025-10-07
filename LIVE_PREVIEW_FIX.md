# 🎨 NAPRAWA LIVE PREVIEW

## 📋 PROBLEM

Tryb live preview nie działa - zmiany w ustawieniach nie są widoczne natychmiast.

## 🔍 DIAGNOSTYKA

### 1. Sprawdź czy masV2Global jest dostępny

Otwórz Console (F12) na stronie ustawień i wpisz:

```javascript
console.log('masV2Global:', typeof masV2Global);
console.log('AJAX URL:', masV2Global?.ajaxUrl);
console.log('Nonce:', masV2Global?.nonce);
```

**Oczekiwany wynik:**
```
masV2Global: object
AJAX URL: http://localhost/wp-admin/admin-ajax.php
Nonce: abc123...
```

**Jeśli masV2Global jest undefined:**
- Problem: JavaScript nie jest załadowany lub localized
- Rozwiązanie: Sprawdź czy `wp_localize_script` jest wywołany

### 2. Sprawdź czy AJAX handler działa

W Console:

```javascript
fetch(masV2Global.ajaxUrl, {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({
        action: 'mas_v2_get_preview_css',
        nonce: masV2Global.nonce
    })
})
.then(r => r.json())
.then(d => console.log('Response:', d));
```

**Oczekiwany wynik:**
```javascript
{
    success: true,
    data: {
        css: "...CSS code...",
        performance: {
            execution_time_ms: 15.23,
            memory_usage_mb: 2.5
        }
    }
}
```

**Jeśli success: false:**
- Sprawdź data.message dla szczegółów błędu
- Możliwe przyczyny:
  - Nonce verification failed
  - Insufficient permissions
  - CSS generation error

### 3. Sprawdź czy CSS jest wstrzykiwany

W Console:

```javascript
// Sprawdź czy style element istnieje
console.log('Preview styles:', document.getElementById('mas-preview-styles'));

// Sprawdź zawartość
const styles = document.getElementById('mas-preview-styles');
if (styles) {
    console.log('CSS length:', styles.textContent.length);
    console.log('CSS preview:', styles.textContent.substring(0, 200));
}
```

## ✅ ROZWIĄZANIA

### Problem 1: masV2Global nie jest zdefiniowany

**Przyczyna:** `wp_localize_script` nie jest wywołany lub jest wywołany dla złego script handle.

**Sprawdź w modern-admin-styler-v2.php:**

```php
// Powinno być w enqueueAssets()
wp_localize_script('mas-v2-admin-settings-simple', 'masV2Global', [
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('mas_v2_nonce'),
    'settings' => $this->getSettings()
]);
```

**Rozwiązanie:**
- Upewnij się że script handle jest poprawny
- Sprawdź czy script jest enqueued przed localize
- Sprawdź czy jesteś na stronie ustawień wtyczki

### Problem 2: AJAX handler zwraca błąd

**Możliwe błędy:**

#### A. "Security error" / "Invalid nonce"

```php
// Sprawdź czy nonce jest poprawnie weryfikowany
if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mas_v2_nonce')) {
    // Upewnij się że używasz tego samego nonce name
}
```

**Rozwiązanie:**
- Sprawdź czy nonce name jest taki sam w PHP i JS
- Odśwież stronę aby wygenerować nowy nonce

#### B. "Insufficient permissions"

```php
if (!current_user_can('manage_options')) {
    // User nie ma uprawnień
}
```

**Rozwiązanie:**
- Zaloguj się jako administrator
- Sprawdź uprawnienia użytkownika

#### C. "CSS generation failed"

**Przyczyna:** Metody generateCSS* nie istnieją lub rzucają wyjątek.

**Sprawdź czy istnieją:**
```php
private function generateCSSVariables($settings) { ... }
private function generateAdminCSS($settings) { ... }
private function generateMenuCSS($settings) { ... }
private function generateAdminBarCSS($settings) { ... }
```

**Rozwiązanie:**
- Sprawdź logi PHP dla szczegółów błędu
- Dodaj try-catch w metodach generujących CSS

### Problem 3: CSS nie jest wstrzykiwany

**Przyczyna:** JavaScript nie dodaje `<style>` do `<head>`.

**Sprawdź w simple-live-preview.js:**

```javascript
// Powinno być:
if (response.success && response.data && response.data.css) {
    $('#mas-preview-styles').remove();
    $('<style id="mas-preview-styles">' + response.data.css + '</style>')
        .appendTo('head');
}
```

**Rozwiązanie:**
- Sprawdź czy jQuery jest załadowany
- Sprawdź czy response.data.css nie jest pusty
- Sprawdź Console dla błędów JavaScript

### Problem 4: Zmiany nie są widoczne

**Przyczyna:** CSS ma niską specyficzność lub jest nadpisywany.

**Rozwiązanie:**

```css
/* Zwiększ specyficzność w generateMenuCSS() */
body.wp-admin #adminmenu {
    background: var(--mas-menu-bg-color);
}

/* Lub użyj !important (ostateczność) */
body.wp-admin #adminmenu {
    background: var(--mas-menu-bg-color) !important;
}
```

## 🧪 TESTOWANIE

### Test 1: Podstawowy test AJAX

Otwórz `test-live-preview-ajax.html` w przeglądarce i kliknij "Test AJAX Handler".

**Oczekiwany wynik:**
```
✅ AJAX handler działa poprawnie!
CSS length: 5000+ znaków
Execution time: 15ms
```

### Test 2: Test zmiany koloru

Kliknij "Test Zmiana Koloru".

**Oczekiwany wynik:**
```
✅ CSS wygenerowany pomyślnie
✅ CSS zastosowany do strony
✅ CSS zawiera testowy kolor
```

### Test 3: Test w rzeczywistym WordPress

1. Otwórz stronę ustawień wtyczki
2. Otwórz Console (F12)
3. Zmień kolor menu (np. menu_background)
4. Sprawdź Console:

```
Color changed: menu_background = #ff0000
MAS: Updating preview for menu_background = #ff0000
MAS: AJAX response: {success: true, data: {...}}
MAS: CSS applied successfully
MAS: Generated in 15.23ms
```

5. Sprawdź czy kolor menu się zmienił

## 📊 CHECKLIST

- [ ] masV2Global jest zdefiniowany
- [ ] AJAX URL jest poprawny
- [ ] Nonce jest ustawiony
- [ ] AJAX handler zwraca success: true
- [ ] Response zawiera data.css
- [ ] CSS nie jest pusty
- [ ] CSS jest wstrzykiwany do <head>
- [ ] Zmiany są widoczne natychmiast
- [ ] Nie ma błędów w Console
- [ ] Nie ma błędów w PHP logs

## 🎯 QUICK FIX

Jeśli live preview nadal nie działa, spróbuj tego prostego rozwiązania:

### 1. Upewnij się że wszystko jest załadowane

```php
// W modern-admin-styler-v2.php, metoda enqueueAssets()

// 1. Enqueue script
wp_enqueue_script(
    'mas-v2-simple-live-preview',
    MAS_V2_PLUGIN_URL . 'assets/js/simple-live-preview.js',
    ['jquery', 'wp-color-picker'],  // Dependencies
    MAS_V2_VERSION,
    true  // In footer
);

// 2. Localize AFTER enqueue
wp_localize_script('mas-v2-admin-settings-simple', 'masV2Global', [
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('mas_v2_nonce'),
    'settings' => $this->getSettings()
]);
```

### 2. Dodaj debug logging

```javascript
// W simple-live-preview.js, na początku updatePreview()
function updatePreview(setting, value) {
    console.log('🎨 UPDATE PREVIEW:', setting, '=', value);
    console.log('   AJAX URL:', masV2Global.ajaxUrl);
    console.log('   Nonce:', masV2Global.nonce.substring(0, 10) + '...');
    
    // ... reszta kodu
}
```

### 3. Sprawdź response

```javascript
// W simple-live-preview.js, w .done()
.done(function(response) {
    console.log('📥 RESPONSE:', response);
    console.log('   Success:', response.success);
    console.log('   CSS length:', response.data?.css?.length || 0);
    
    // ... reszta kodu
})
```

## 💡 NAJCZĘSTSZE PROBLEMY

### 1. "masV2Global is not defined"

**Rozwiązanie:** Sprawdź czy jesteś na stronie ustawień wtyczki (nie na innej stronie admin).

### 2. "Invalid nonce"

**Rozwiązanie:** Odśwież stronę (Ctrl+F5) aby wygenerować nowy nonce.

### 3. "CSS is empty"

**Rozwiązanie:** Sprawdź czy metody generateCSS* zwracają niepusty string.

### 4. "Changes not visible"

**Rozwiązanie:** Sprawdź czy CSS ma wystarczającą specyficzność lub użyj !important.

### 5. "AJAX request fails"

**Rozwiązanie:** Sprawdź czy handler jest zarejestrowany: `add_action('wp_ajax_mas_v2_get_preview_css', ...)`

## 📚 DODATKOWE ZASOBY

- **test-live-preview-ajax.html** - Interaktywny test AJAX
- **simple-live-preview.js** - Kod JavaScript live preview
- **modern-admin-styler-v2.php** - AJAX handler (linia ~1415)

---

**Data:** 2025-05-10
**Status:** 🔍 Diagnostyka i rozwiązania
