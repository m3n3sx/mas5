# MAS V2 - Procedury Testowania

## Spis Treści
1. [Testy Podstawowe](#1-testy-podstawowe)
2. [Testy Funkcjonalne](#2-testy-funkcjonalne)
3. [Testy Bezpieczeństwa](#3-testy-bezpieczeństwa)
4. [Testy Wydajnościowe](#4-testy-wydajnościowe)
5. [Testy Kompatybilności](#5-testy-kompatybilności)
6. [Testy Regresji](#6-testy-regresji)
7. [Checklist Przed Wdrożeniem](#7-checklist-przed-wdrożeniem)

---

## 1. Testy Podstawowe

### 1.1 Test Instalacji Pluginu

**Cel**: Sprawdzić czy plugin instaluje się poprawnie

**Kroki**:
1. Przejdź do WordPress Admin → Wtyczki
2. Znajdź "Modern Admin Styler V2"
3. Kliknij "Aktywuj"
4. Sprawdź czy pojawił się komunikat sukcesu
5. Sprawdź czy w menu pojawił się "MAS V2"

**Oczekiwany rezultat**:
- ✅ Plugin aktywowany bez błędów
- ✅ Menu "MAS V2" widoczne w panelu admin
- ✅ Brak błędów w `debug.log`

**Narzędzia**:
```bash
# Sprawdź logi
tail -f wp-content/debug.log
```

---

### 1.2 Test Domyślnych Ustawień

**Cel**: Sprawdzić czy domyślne ustawienia są tworzone

**Kroki**:
1. Aktywuj plugin
2. Uruchom: `test-current-save-status.php`
3. Sprawdź sekcję "Status Bieżący"

**Oczekiwany rezultat**:
- ✅ Ustawienia istnieją (>50 opcji)
- ✅ Plugin włączony
- ✅ Ustawienia menu obecne

**SQL Test**:
```sql
SELECT * FROM wp_options WHERE option_name = 'mas_v2_settings';
```

---

### 1.3 Test Strony Ustawień

**Cel**: Sprawdzić czy strona ustawień ładuje się poprawnie

**Kroki**:
1. Przejdź do WP Admin → MAS V2 → Settings
2. Sprawdź czy wszystkie zakładki są widoczne
3. Sprawdź console (F12) pod kątem błędów

**Oczekiwany rezultat**:
- ✅ Strona ładuje się bez błędów
- ✅ Wszystkie zakładki widoczne (Menu, Admin Bar, Content, etc.)
- ✅ Formularz renderuje się poprawnie
- ✅ Console bez błędów JavaScript

**Console Check**:
```javascript
// Powinno być w console:
✅ MAS Simple Settings: Initialized
✅ MAS Simple Live Preview: Starting...
✅ MAS Cross-Browser Compatibility: Initialized
```

---

## 2. Testy Funkcjonalne

### 2.1 Test Zapisu Ustawień

**Cel**: Sprawdzić czy ustawienia zapisują się poprawnie

**Kroki**:
1. Przejdź do MAS V2 → Settings → Menu
2. Zmień "Menu Background Color" na `#ff0000` (czerwony)
3. Zmień "Menu Width" na `250`
4. Kliknij "Save Settings"
5. Sprawdź komunikat sukcesu
6. Odśwież stronę (Ctrl+Shift+R)
7. Sprawdź czy wartości się zachowały

**Oczekiwany rezultat**:
- ✅ Komunikat "✓ Zapisano!"
- ✅ Wartości zachowane po odświeżeniu
- ✅ Menu ma czerwone tło
- ✅ Menu ma szerokość 250px

**Diagnostyka**:
```bash
# Uruchom test
http://localhost/wp-content/plugins/mas3/test-current-save-status.php

# Użyj formularza testowego
# Sprawdź czy "Weryfikacja UDANA!"
```

---

### 2.2 Test Live Preview

**Cel**: Sprawdzić czy podgląd na żywo działa

**Kroki**:
1. Przejdź do MAS V2 → Settings → Menu
2. Zmień "Menu Background Color" używając color pickera
3. Obserwuj czy menu zmienia kolor natychmiast (bez zapisywania)
4. Zmień "Menu Width" suwakiem
5. Obserwuj czy szerokość menu zmienia się na żywo

**Oczekiwany rezultat**:
- ✅ Zmiany widoczne natychmiast (~300ms opóźnienie)
- ✅ Brak przeładowania strony
- ✅ Płynna animacja zmian
- ✅ Console bez błędów

**Network Tab Check**:
```
Request URL: /wp-admin/admin-ajax.php
Request Method: POST
Status: 200 OK
Response: {"success":true,"data":{"css":"..."}}
```

---

### 2.3 Test Resetowania Ustawień

**Cel**: Sprawdzić czy reset przywraca domyślne wartości

**Kroki**:
1. Zmień kilka ustawień (np. kolory, szerokości)
2. Zapisz zmiany
3. Kliknij "Reset to Defaults"
4. Potwierdź w dialogu
5. Sprawdź czy ustawienia wróciły do domyślnych

**Oczekiwany rezultat**:
- ✅ Komunikat "✓ Zresetowano!"
- ✅ Strona przeładowana automatycznie
- ✅ Ustawienia domyślne przywrócone
- ✅ Backup utworzony przed resetem

**Weryfikacja Backupu**:
```sql
SELECT option_name FROM wp_options 
WHERE option_name LIKE 'mas_v2_settings_backup_before_reset_%' 
ORDER BY option_id DESC LIMIT 1;
```

---

### 2.4 Test Eksportu Ustawień

**Cel**: Sprawdzić czy eksport generuje poprawny plik JSON

**Kroki**:
1. Przejdź do MAS V2 → Settings → Import/Export
2. Kliknij "Export Settings"
3. Zapisz plik JSON
4. Otwórz plik w edytorze tekstu
5. Sprawdź strukturę JSON

**Oczekiwany rezultat**:
- ✅ Plik JSON pobrany
- ✅ Poprawna struktura JSON
- ✅ Zawiera wszystkie ustawienia
- ✅ Zawiera metadane (wersja, data, etc.)

**Struktura Pliku**:
```json
{
  "format_version": "2.0",
  "plugin_version": "2.2.0",
  "export_date": "2025-01-06 12:00:00",
  "site_url": "http://localhost",
  "settings": {
    "menu_background": "#ff0000",
    "menu_width": 250,
    ...
  }
}
```

---

### 2.5 Test Importu Ustawień

**Cel**: Sprawdzić czy import przywraca ustawienia z pliku

**Kroki**:
1. Wyeksportuj aktualne ustawienia (backup)
2. Zmień kilka ustawień
3. Przejdź do Import/Export
4. Wybierz plik JSON z backupu
5. Kliknij "Import Settings"
6. Sprawdź czy ustawienia zostały przywrócone

**Oczekiwany rezultat**:
- ✅ Komunikat sukcesu z liczbą zaimportowanych ustawień
- ✅ Ustawienia przywrócone z pliku
- ✅ Backup utworzony przed importem
- ✅ Walidacja pliku JSON

**Test Błędnego Pliku**:
```json
// Spróbuj zaimportować niepoprawny JSON
{"invalid": "json"
```
**Oczekiwany rezultat**: Błąd walidacji, ustawienia nie zmienione

---

### 2.6 Test Wszystkich Zakładek

**Cel**: Sprawdzić czy wszystkie zakładki działają

**Zakładki do przetestowania**:
1. ✅ Menu
2. ✅ Admin Bar
3. ✅ Content
4. ✅ Buttons
5. ✅ Forms
6. ✅ Advanced
7. ✅ Import/Export

**Dla każdej zakładki**:
1. Kliknij zakładkę
2. Sprawdź czy zawartość się ładuje
3. Zmień jedno ustawienie
4. Zapisz
5. Sprawdź czy zmiana działa

---

## 3. Testy Bezpieczeństwa

### 3.1 Test Nonce Verification

**Cel**: Sprawdzić czy nonce chroni przed CSRF

**Kroki**:
1. Otwórz DevTools → Network
2. Zapisz ustawienia
3. Skopiuj request jako cURL
4. Zmień wartość `nonce` na niepoprawną
5. Wyślij request ponownie

**Oczekiwany rezultat**:
- ✅ Request z niepoprawnym nonce odrzucony
- ✅ Błąd: "Security verification failed"
- ✅ Ustawienia nie zmienione

**cURL Test**:
```bash
curl -X POST 'http://localhost/wp-admin/admin-ajax.php' \
  -d 'action=mas_v2_save_settings' \
  -d 'nonce=INVALID_NONCE' \
  -d 'menu_background=#ff0000'
```

---

### 3.2 Test Capability Check

**Cel**: Sprawdzić czy tylko administratorzy mogą zapisywać

**Kroki**:
1. Zaloguj się jako użytkownik bez uprawnień (Subscriber)
2. Spróbuj otworzyć stronę ustawień
3. Spróbuj wysłać AJAX request do zapisu

**Oczekiwany rezultat**:
- ✅ Strona ustawień niedostępna (403 lub redirect)
- ✅ AJAX request odrzucony
- ✅ Błąd: "Insufficient permissions"

---

### 3.3 Test Input Sanitization

**Cel**: Sprawdzić czy dane wejściowe są sanityzowane

**Testy**:

#### XSS Test
```javascript
// Spróbuj zapisać złośliwy kod
menu_background: '<script>alert("XSS")</script>'
```
**Oczekiwany rezultat**: Kod usunięty lub zescapowany

#### SQL Injection Test
```javascript
// Spróbuj SQL injection
menu_background: "'; DROP TABLE wp_options; --"
```
**Oczekiwany rezultat**: String sanityzowany, baza bezpieczna

#### Path Traversal Test
```javascript
// Spróbuj path traversal
custom_css_file: '../../../wp-config.php'
```
**Oczekiwany rezultat**: Ścieżka sanityzowana

---

### 3.4 Test Output Escaping

**Cel**: Sprawdzić czy output jest escapowany

**Kroki**:
1. Zapisz ustawienie z HTML: `<b>Test</b>`
2. Sprawdź źródło strony
3. Upewnij się że HTML jest zescapowany

**Oczekiwany rezultat**:
```html
<!-- DOBRE (escapowane) -->
&lt;b&gt;Test&lt;/b&gt;

<!-- ZŁE (nie escapowane) -->
<b>Test</b>
```

---

## 4. Testy Wydajnościowe

### 4.1 Test Czasu Generowania CSS

**Cel**: Sprawdzić czy CSS generuje się szybko

**Kroki**:
1. Uruchom: `test-current-save-status.php`
2. Sprawdź sekcję "Test Generowania CSS"
3. Zanotuj czas wykonania

**Oczekiwany rezultat**:
- ✅ CSS generowany < 100ms
- ✅ Rozmiar CSS < 50KB
- ✅ Brak błędów

**Benchmark**:
```php
$start = microtime(true);
$css = $plugin->generateMenuCSS($settings);
$time = (microtime(true) - $start) * 1000;
echo "Time: {$time}ms, Size: " . strlen($css) . " bytes";
```

---

### 4.2 Test Czasu Zapisu

**Cel**: Sprawdzić czy zapis jest szybki

**Kroki**:
1. Otwórz DevTools → Network
2. Zapisz ustawienia
3. Sprawdź czas odpowiedzi AJAX

**Oczekiwany rezultat**:
- ✅ Czas odpowiedzi < 500ms
- ✅ Brak timeoutów
- ✅ Response zawiera `execution_time_ms`

**Network Tab**:
```
Timing:
- Waiting (TTFB): < 300ms
- Content Download: < 50ms
- Total: < 500ms
```

---

### 4.3 Test Obciążenia Bazy Danych

**Cel**: Sprawdzić liczbę zapytań SQL

**Kroki**:
1. Zainstaluj plugin "Query Monitor"
2. Przejdź do strony ustawień
3. Sprawdź liczbę zapytań SQL

**Oczekiwany rezultat**:
- ✅ Liczba zapytań < 50
- ✅ Brak slow queries (> 0.05s)
- ✅ Brak duplicate queries

---

### 4.4 Test Zużycia Pamięci

**Cel**: Sprawdzić czy plugin nie zużywa za dużo pamięci

**Kroki**:
1. Sprawdź zużycie pamięci przed aktywacją
2. Aktywuj plugin
3. Sprawdź zużycie pamięci po aktywacji

**Oczekiwany rezultat**:
- ✅ Wzrost pamięci < 5MB
- ✅ Brak memory leaks
- ✅ Memory usage stabilny

**PHP Test**:
```php
$before = memory_get_usage(true);
// Aktywuj plugin
$after = memory_get_usage(true);
$diff = ($after - $before) / 1024 / 1024;
echo "Memory increase: {$diff}MB";
```

---

## 5. Testy Kompatybilności

### 5.1 Test Przeglądarek

**Przeglądarki do przetestowania**:
- ✅ Chrome (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Edge (latest)

**Dla każdej przeglądarki**:
1. Otwórz stronę ustawień
2. Sprawdź czy layout jest poprawny
3. Przetestuj zapis ustawień
4. Przetestuj live preview
5. Sprawdź console pod kątem błędów

**Oczekiwany rezultat**:
- ✅ Wszystkie funkcje działają we wszystkich przeglądarkach
- ✅ Layout poprawny
- ✅ Brak błędów JavaScript

---

### 5.2 Test Wersji WordPress

**Wersje do przetestowania**:
- ✅ WordPress 5.0
- ✅ WordPress 5.9
- ✅ WordPress 6.0
- ✅ WordPress 6.4
- ✅ WordPress 6.8 (latest)

**Dla każdej wersji**:
1. Zainstaluj WordPress
2. Aktywuj plugin
3. Przetestuj podstawowe funkcje
4. Sprawdź logi pod kątem błędów

---

### 5.3 Test Wersji PHP

**Wersje do przetestowania**:
- ✅ PHP 7.4
- ✅ PHP 8.0
- ✅ PHP 8.1
- ✅ PHP 8.2
- ✅ PHP 8.3

**Dla każdej wersji**:
1. Przełącz PHP
2. Aktywuj plugin
3. Przetestuj funkcje
4. Sprawdź deprecated warnings

---

### 5.4 Test Konfliktów z Innymi Pluginami

**Popularne pluginy do przetestowania**:
- ✅ Yoast SEO
- ✅ WooCommerce
- ✅ Contact Form 7
- ✅ Elementor
- ✅ Advanced Custom Fields

**Dla każdego pluginu**:
1. Aktywuj plugin
2. Aktywuj MAS V2
3. Sprawdź czy nie ma konfliktów
4. Przetestuj funkcje obu pluginów

---

## 6. Testy Regresji

### 6.1 Test Po Każdej Zmianie Kodu

**Checklist**:
- [ ] Wszystkie testy podstawowe przechodzą
- [ ] Zapis ustawień działa
- [ ] Live preview działa
- [ ] CSS generuje się poprawnie
- [ ] Brak błędów w console
- [ ] Brak błędów w debug.log

---

### 6.2 Test Przed Każdym Commitem

**Kroki**:
1. Uruchom wszystkie testy automatyczne
2. Przetestuj ręcznie kluczowe funkcje
3. Sprawdź logi
4. Sprawdź console
5. Commit tylko jeśli wszystko działa

---

## 7. Checklist Przed Wdrożeniem

### 7.1 Testy Funkcjonalne
- [ ] Instalacja pluginu działa
- [ ] Domyślne ustawienia tworzone
- [ ] Strona ustawień ładuje się
- [ ] Zapis ustawień działa
- [ ] Live preview działa
- [ ] Reset ustawień działa
- [ ] Eksport ustawień działa
- [ ] Import ustawień działa
- [ ] Wszystkie zakładki działają

### 7.2 Testy Bezpieczeństwa
- [ ] Nonce verification działa
- [ ] Capability check działa
- [ ] Input sanitization działa
- [ ] Output escaping działa
- [ ] Brak luk XSS
- [ ] Brak luk SQL injection

### 7.3 Testy Wydajnościowe
- [ ] CSS generuje się < 100ms
- [ ] Zapis < 500ms
- [ ] Zapytania SQL < 50
- [ ] Zużycie pamięci < 5MB

### 7.4 Testy Kompatybilności
- [ ] Działa w Chrome
- [ ] Działa w Firefox
- [ ] Działa w Safari
- [ ] Działa w Edge
- [ ] Działa z WordPress 5.0+
- [ ] Działa z PHP 7.4+
- [ ] Brak konfliktów z popularnymi pluginami

### 7.5 Dokumentacja
- [ ] README.md zaktualizowany
- [ ] CHANGELOG.md zaktualizowany
- [ ] Inline comments dodane
- [ ] Dokumentacja API zaktualizowana

### 7.6 Kod
- [ ] Brak syntax errors
- [ ] Brak deprecated functions
- [ ] Kod sformatowany (PSR-12)
- [ ] Brak TODO/FIXME w produkcji
- [ ] Wszystkie funkcje przetestowane

### 7.7 Finalne Sprawdzenie
- [ ] Wersja zaktualizowana w plugin header
- [ ] Wersja zaktualizowana w README
- [ ] Changelog zaktualizowany
- [ ] Backup utworzony
- [ ] Wszystkie testy przechodzą
- [ ] Logi czyste (brak błędów)

---

## 8. Narzędzia Testowe

### 8.1 Pliki Testowe
- `test-current-save-status.php` - Kompleksowa diagnostyka
- `test-save-settings.php` - Test zapisu
- `test-simple-live-preview.html` - Test live preview
- `verify-task*-completion.php` - Weryfikacja tasków

### 8.2 Browser DevTools
- **Console** - Błędy JavaScript
- **Network** - Requesty AJAX
- **Application** - LocalStorage, Cookies
- **Performance** - Profiling

### 8.3 WordPress Plugins
- **Query Monitor** - SQL queries, performance
- **Debug Bar** - Debug info
- **WP-CLI** - Command line testing

### 8.4 Komendy CLI

```bash
# Aktywuj plugin
wp plugin activate modern-admin-styler-v2

# Sprawdź status
wp plugin status modern-admin-styler-v2

# Sprawdź opcje
wp option get mas_v2_settings

# Wyczyść cache
wp cache flush

# Sprawdź logi
tail -f wp-content/debug.log
```

---

## 9. Raportowanie Błędów

### 9.1 Informacje do Zebrania
- Wersja WordPress
- Wersja PHP
- Wersja pluginu
- Przeglądarka i wersja
- Kroki do reprodukcji
- Oczekiwany rezultat
- Aktualny rezultat
- Logi (debug.log, console)
- Screenshots

### 9.2 Template Raportu

```markdown
## Opis Błędu
[Krótki opis problemu]

## Środowisko
- WordPress: 6.4.2
- PHP: 8.1.0
- Plugin: 2.2.0
- Przeglądarka: Chrome 120

## Kroki do Reprodukcji
1. Przejdź do...
2. Kliknij...
3. Sprawdź...

## Oczekiwany Rezultat
[Co powinno się stać]

## Aktualny Rezultat
[Co się dzieje]

## Logi
```
[Wklej logi]
```

## Screenshots
[Załącz screenshots]
```

---

**Wersja dokumentu**: 1.0  
**Data utworzenia**: 2025-01-06  
**Ostatnia aktualizacja**: 2025-01-06  
**Autor**: MAS V2 Development Team
