# Jak Debugować Zapisywanie Ustawień

## Krok 1: Włącz Debug Mode

Edytuj `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## Krok 2: Wyczyść Logi

```bash
> wp-content/debug.log
```

Lub usuń plik:
```bash
rm wp-content/debug.log
```

## Krok 3: Otwórz Logi w Czasie Rzeczywistym

```bash
tail -f wp-content/debug.log
```

## Krok 4: Testuj Zapisywanie

1. Przejdź do: WP Admin → MAS V2 → Settings
2. Otwórz Console (F12)
3. Zmień kilka ustawień:
   - Kolor tła menu
   - Szerokość menu
   - Wysokość admin bar
   - Zaznacz/odznacz checkboxy
4. Kliknij "Zapisz"

## Krok 5: Sprawdź Console

Powinieneś zobaczyć:
```
🚀 Wysyłanie danych: {action: "mas_v2_save_settings", ...}
📊 Liczba pól: 150+
```

## Krok 6: Sprawdź Logi PHP

W `debug.log` powinieneś zobaczyć:
```
MAS V2: ajaxSaveSettings called with 150+ POST values
MAS V2: First 10 POST keys: action, nonce, menu_bg, menu_width, ...
MAS V2: Menu fields: 50+, Admin Bar fields: 30+
MAS V2: Sanitization complete. Total settings: 150+
MAS V2: Settings save successful. Count: 150+
MAS V2: Saved - Menu: 50+, Admin Bar: 30+
MAS V2: menu_bg = #ff0000
MAS V2: menu_width = 250
MAS V2: admin_bar_height = 40
```

## Krok 7: Sprawdź Network Tab

1. Otwórz DevTools (F12)
2. Zakładka "Network"
3. Kliknij "Zapisz"
4. Znajdź request do `admin-ajax.php`
5. Sprawdź "Payload":
   - Powinno być 150+ pól
   - Sprawdź czy są wszystkie pola (menu_, admin_bar_, etc.)

## Krok 8: Sprawdź Bazę Danych

```bash
# Uruchom test
http://localhost/wp-content/plugins/mas3/test-current-save-status.php
```

Lub SQL:
```sql
SELECT option_value FROM wp_options WHERE option_name = 'mas_v2_settings';
```

## Co Sprawdzić Jeśli Nie Działa?

### Problem: Mało pól w Console
**Objaw**: Console pokazuje tylko 10-20 pól zamiast 150+

**Sprawdź**:
1. Czy wszystkie pola są w formularzu `#mas-v2-settings-form`?
2. Czy pola mają atrybut `name`?
3. Czy JavaScript jest załadowany?

**Test**:
```javascript
// W console
$('#mas-v2-settings-form input').length  // Powinno być 100+
$('#mas-v2-settings-form input[name]').length  // Powinno być 100+
```

### Problem: Mało pól w PHP Logach
**Objaw**: PHP logi pokazują tylko kilka pól

**Sprawdź**:
1. Network tab - co jest w Payload?
2. Czy AJAX request się udał (status 200)?
3. Czy nie ma błędów JavaScript?

### Problem: Pola są wysyłane ale nie zapisywane
**Objaw**: Console i Network pokazują wszystkie pola, ale nie są w bazie

**Sprawdź**:
1. PHP logi - czy są błędy sanityzacji?
2. Czy pola są w domyślnych ustawieniach (`getDefaultSettings()`)?
3. Czy nazwy pól w formularzu pasują do PHP?

**Test**:
```bash
# Uruchom
http://localhost/wp-content/plugins/mas3/debug-ajax-save.php
```

### Problem: Checkboxy nie działają
**Objaw**: Niezaznaczone checkboxy nie są wysyłane

**To jest normalne!** `serializeArray()` nie zawiera niezaznaczonych checkboxów.

**Rozwiązanie**: Kod w `admin-settings-simple.js` już to obsługuje:
```javascript
// Dodaj niezaznaczone checkboxy
$form.find('input[type="checkbox"]').each(function() {
    const name = $(this).attr('name');
    if (name && !postData.hasOwnProperty(name)) {
        postData[name] = '0';
    }
});
```

**Sprawdź**: Czy ten kod jest w pliku?

## Narzędzia Diagnostyczne

1. **test-current-save-status.php** - Kompleksowa diagnostyka
2. **debug-ajax-save.php** - Test formatu danych AJAX
3. **test-ajax-data-format.php** - Test serializeArray()

## Typowe Problemy

### 1. Tylko menu_bg się zapisuje
**Przyczyna**: Inne pola nie są w formularzu lub nie mają `name`

**Rozwiązanie**: Sprawdź HTML formularza

### 2. Wszystkie pola wysyłane ale tylko niektóre zapisane
**Przyczyna**: Nazwy pól nie pasują do domyślnych ustawień

**Rozwiązanie**: Sprawdź `getDefaultSettings()` vs nazwy w formularzu

### 3. Pola zapisane ale CSS nie działa
**Przyczyna**: Problem z generowaniem CSS

**Rozwiązanie**: Sprawdź `generateMenuCSS()` i podobne funkcje

## Kontakt

Jeśli problem nadal występuje, zbierz:
1. Console output (screenshot)
2. Network tab Payload (screenshot)
3. PHP debug.log (ostatnie 50 linii)
4. Wynik z test-current-save-status.php

I zgłoś problem z tymi informacjami.
