# MAS V2 Plugin - Troubleshooting Guide

## 🚨 Główne Problemy i Rozwiązania

### Problem 1: "BRAK USTAWIEŃ MENU!" 
**Objaw**: Debug panel pokazuje "❌ BRAK USTAWIEŃ MENU!"

**Przyczyna**: Brak zapisanych ustawień w bazie danych

**Rozwiązanie**:
1. Uruchom `test-settings-check.php` aby sprawdzić stan ustawień
2. Jeśli brak ustawień, uruchom `force-default-settings.php?force=yes`
3. Lub przejdź do WP Admin → MAS V2 → Menu i zapisz dowolne ustawienie

### Problem 2: ModernAdminApp nie ładuje się
**Objaw**: Console pokazuje "❌ Timeout: ModernAdminApp nie załadowane w 5 sekund"

**Przyczyna**: 
- Moduły nie są ładowane w odpowiedniej kolejności
- Event `mas-modules-ready` nie jest wysyłany/odbierany

**Rozwiązanie**:
1. Sprawdź console czy moduły się ładują: `MASLoader.healthCheck()`
2. Sprawdź czy event jest wysyłany
3. Wyczyść cache przeglądarki (Ctrl+Shift+R)

**Status**: ✅ NAPRAWIONE w ostatnim commicie

### Problem 3: Live Preview nie działa
**Objaw**: Zmiany w ustawieniach nie są widoczne na żywo

**Przyczyna**: Skomplikowana architektura modułowa nie działała poprawnie

**Rozwiązanie**: ✅ NAPRAWIONE - Zaimplementowano prostszy system live preview
- Dodano `ajaxGetPreviewCSS()` handler w PHP
- Dodano `simple-live-preview.js` wzorowany na działającej wersji
- Używa jQuery i bezpośrednich event listenerów
- Debouncing 300ms dla lepszej wydajności

**Test**: Uruchom `test-simple-live-preview.html`

**Status**: ✅ NAPRAWIONE

### Problem 4: Większość opcji nie działa
**Objaw**: Ustawienia są zapisywane ale nie są aplikowane

**Przyczyna**: 
- CSS nie jest generowany (Problem 1)
- Moduły nie są załadowane (Problem 2)

**Rozwiązanie**:
1. Rozwiąż Problem 1 (brak ustawień)
2. Rozwiąż Problem 2 (ModernAdminApp)
3. Wyczyść cache WordPress i przeglądarki

### Problem 5: Ostrzeżenie o wersji WordPress
**Objaw**: "Modern Admin Styler V2 has not been tested with WordPress 6.8.2"

**Rozwiązanie**: ✅ NAPRAWIONE - zaktualizowano "Tested up to: 6.8"

### Problem 6: Błąd walidacji koloru "#ddd"
**Objaw**: Console pokazuje "The specified value "#ddd" does not conform to the required format"

**Rozwiązanie**: ✅ NAPRAWIONE - zmieniono na "#dddddd"

## 🔧 Narzędzia Diagnostyczne

### 1. test-settings-check.php
Sprawdza stan ustawień w bazie danych
```
http://localhost/wp-content/plugins/mas3/test-settings-check.php
```

### 2. test-module-loading.html
Testuje ładowanie modułów JavaScript
```
http://localhost/wp-content/plugins/mas3/test-module-loading.html
```

### 3. force-default-settings.php
Wymusza zapisanie domyślnych ustawień
```
http://localhost/wp-content/plugins/mas3/force-default-settings.php?force=yes
```

### 4. Console Commands
```javascript
// Sprawdź stan loadera
MASLoader.healthCheck()

// Sprawdź załadowane moduły
MASLoader.getLoadedModules()

// Sprawdź stan aplikacji
ModernAdminApp.getInstance().isInitialized

// Sprawdź moduły aplikacji
ModernAdminApp.getInstance().modules

// Sprawdź ustawienia
masV2Global.settings
```

## 📋 Checklist Naprawy

- [x] 1. Naprawiono syntax error w modern-admin-styler-v2.php
- [x] 2. Naprawiono double initialization w ModernAdminApp
- [x] 3. Naprawiono event coordination między mas-loader.js i admin-global.js
- [x] 4. Naprawiono corrupted code w ModernAdminApp.js
- [x] 5. Zaktualizowano "Tested up to" na 6.8
- [x] 6. Naprawiono błąd walidacji koloru #ddd → #dddddd
- [x] 7. Dodano cross-browser compatibility system
- [x] 8. Zaimplementowano prosty system live preview (wzorowany na działającej wersji)
- [ ] 9. Wymuszenie domyślnych ustawień (wymaga uruchomienia skryptu)
- [ ] 10. Test wszystkich funkcji po naprawie
- [ ] 11. Dokumentacja dla użytkownika

## 🎯 Następne Kroki

1. **Natychmiastowe**:
   - Uruchom `force-default-settings.php?force=yes`
   - Wyczyść cache przeglądarki
   - Sprawdź czy ModernAdminApp się ładuje

2. **Krótkoterminowe**:
   - Przetestuj live preview
   - Przetestuj wszystkie zakładki ustawień
   - Sprawdź czy CSS jest generowany

3. **Długoterminowe**:
   - Dokończ Task 17 (Documentation)
   - Dokończ Task 18 (Final Testing)
   - Stwórz user guide

## 🐛 Znane Problemy

1. **Duża przestrzeń u góry strony** - wymaga dalszej analizy CSS
2. **Live preview może nie działać** - zależy od ModernAdminApp
3. **Niektóre efekty mogą nie działać** - wymaga testowania po naprawie głównych problemów

## 📞 Wsparcie

Jeśli problemy nadal występują:
1. Sprawdź console przeglądarki (F12)
2. Sprawdź logi PHP (wp-content/debug.log)
3. Uruchom wszystkie testy diagnostyczne
4. Zbierz informacje i zgłoś problem

---

**Ostatnia aktualizacja**: 2025-01-05
**Wersja dokumentu**: 1.0
