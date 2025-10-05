# Martwy Kod Do Usunięcia

## Status: LISTA DO PRZEGLĄDU

---

## 📁 Moduły JavaScript (Nie Używane)

### Do Usunięcia:
- ❌ `assets/js/mas-loader.js` - Stary system ładowania modułów
- ❌ `assets/js/admin-global.js` - Stary bootstrap
- ❌ `assets/js/admin-modern.js` - Może zawierać użyteczny kod, SPRAWDZIĆ!
- ❌ `assets/js/modules/ModernAdminApp.js` - Główny orchestrator (nie działa)
- ❌ `assets/js/modules/LivePreviewManager.js` - Zastąpiony przez simple-live-preview.js
- ❌ `assets/js/modules/SettingsManager.js` - Nie używany
- ❌ `assets/js/modules/ThemeManager.js` - Nie używany
- ❌ `assets/js/modules/BodyClassManager.js` - Nie używany
- ❌ `assets/js/modules/MenuManagerFixed.js` - Nie używany
- ❌ `assets/js/modules/NotificationManager.js` - Nie używany

### Do Zachowania:
- ✅ `assets/js/simple-live-preview.js` - DZIAŁA!
- ✅ `assets/js/cross-browser-compatibility.js` - Task 16
- ✅ `assets/js/admin-settings-page.js` - Może być używany

---

## 📁 Pliki Testowe (Root Directory)

### Do Przeniesienia do tests/archived-tasks/:
- `verify-task*.php` (15 plików)
- `test-task*.php` (10 plików)
- `test-*.html` (większość)

### Do Zachowania w Root:
- ✅ `test-settings-check.php` - Użyteczna diagnostyka
- ✅ `test-simple-live-preview.html` - Użyteczna diagnostyka
- ✅ `test-module-loading.html` - Użyteczna diagnostyka
- ✅ `force-default-settings.php` - Użyteczne narzędzie

---

## 📁 CSS (Wszystkie Zachować)

Wszystkie pliki CSS są używane i potrzebne:
- ✅ `assets/css/*.css` - ZACHOWAĆ WSZYSTKIE

---

## 🔧 Funkcje PHP Do Usunięcia

### W modern-admin-styler-v2.php:

1. **autoload()** - Nie używany
```php
public function autoload($className) {
    // USUNĄĆ - nie jest używany
}
```

2. **Stary ajaxLivePreview()** - Zastąpiony przez ajaxGetPreviewCSS()
```php
public function ajaxLivePreview() {
    // USUNĄĆ - zastąpiony przez ajaxGetPreviewCSS()
}
```

---

## 📊 Statystyki

### Przed Czyszczeniem:
- Pliki JS: ~15 plików
- Pliki testowe w root: 46 plików
- Funkcje PHP: ~150 funkcji
- Rozmiar: ~5500 linii kodu

### Po Czyszczeniu (Szacowane):
- Pliki JS: ~5 plików (-67%)
- Pliki testowe w root: 4 pliki (-91%)
- Funkcje PHP: ~140 funkcji (-7%)
- Rozmiar: ~4000 linii kodu (-27%)

---

## ⚠️ UWAGA: Przed Usunięciem

1. **Zrób backup całego folderu**
2. **Sprawdź czy admin-modern.js nie jest używany**
3. **Przetestuj po każdym usunięciu**
4. **Zachowaj pliki w archiwum przez 30 dni**

---

## 🚀 Plan Wykonania

### Etap 1: Bezpieczne Przeniesienie (TERAZ)
```bash
bash cleanup-test-files.sh
```

### Etap 2: Sprawdzenie admin-modern.js (PRZED USUNIĘCIEM)
```bash
grep -r "admin-modern" modern-admin-styler-v2.php
```

### Etap 3: Usunięcie Modułów (PO TEŚCIE)
```bash
# Tylko jeśli wszystko działa!
rm assets/js/mas-loader.js
rm assets/js/admin-global.js
rm -rf assets/js/modules/
```

### Etap 4: Czyszczenie PHP (PO TEŚCIE)
- Usunąć autoload()
- Usunąć stary ajaxLivePreview()

---

**Ostatnia aktualizacja**: 2025-01-06 00:00
**Status**: GOTOWE DO WYKONANIA
**Priorytet**: ŚREDNI (po sprawdzeniu że wszystko działa)
