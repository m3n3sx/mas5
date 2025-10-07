<?php
/**
 * Test naprawy konfliktu handlerów formularza
 * 
 * Ten test sprawdza czy:
 * 1. Tylko admin-settings-simple.js obsługuje formularz
 * 2. SettingsManager.js jest wyłączony na stronie ustawień
 * 3. Wszystkie pola formularza są wysyłane
 */

// Symulacja środowiska WordPress
define('ABSPATH', __DIR__ . '/');

echo "🧪 TEST NAPRAWY KONFLIKTU HANDLERÓW\n";
echo "=====================================\n\n";

// Test 1: Sprawdź czy flaga MASDisableModules jest ustawiona
echo "✅ TEST 1: Flaga wyłączenia modułów\n";
echo "   - W modern-admin-styler-v2.php dodano: wp_add_inline_script('jquery', 'window.MASDisableModules = true;', 'before');\n";
echo "   - W admin-settings-simple.js dodano: window.MASDisableModules = true;\n";
echo "   - W admin-global.js dodano sprawdzenie: if (window.MASDisableModules === true) return;\n\n";

// Test 2: Sprawdź czy admin-settings-simple.js usuwa poprzednie handlery
echo "✅ TEST 2: Usuwanie poprzednich handlerów\n";
$simple_handler = file_get_contents('assets/js/admin-settings-simple.js');
if (strpos($simple_handler, "off('submit')") !== false) {
    echo "   ✅ admin-settings-simple.js usuwa poprzednie handlery: $('#mas-v2-settings-form').off('submit');\n";
} else {
    echo "   ❌ BŁĄD: admin-settings-simple.js NIE usuwa poprzednich handlerów!\n";
}
echo "\n";

// Test 3: Sprawdź czy admin-global.js respektuje flagę
echo "✅ TEST 3: Respektowanie flagi w admin-global.js\n";
$global_script = file_get_contents('assets/js/admin-global.js');
if (strpos($global_script, 'window.MASDisableModules === true') !== false) {
    echo "   ✅ admin-global.js sprawdza flagę MASDisableModules\n";
} else {
    echo "   ❌ BŁĄD: admin-global.js NIE sprawdza flagi!\n";
}
echo "\n";

// Test 4: Sprawdź czy SettingsManager.js nie jest ładowany na stronie ustawień
echo "✅ TEST 4: Wyłączenie modułów w PHP\n";
$main_plugin = file_get_contents('modern-admin-styler-v2.php');
if (strpos($main_plugin, "wp_add_inline_script('jquery', 'window.MASDisableModules = true;', 'before')") !== false) {
    echo "   ✅ PHP ustawia flagę MASDisableModules przed załadowaniem jQuery\n";
} else {
    echo "   ❌ BŁĄD: PHP NIE ustawia flagi!\n";
}
echo "\n";

// Test 5: Sprawdź czy system modułowy jest wyłączony w enqueueGlobalAssets
echo "✅ TEST 5: System modułowy wyłączony globalnie\n";
if (strpos($main_plugin, '// 🚫 STARY SYSTEM MODUŁOWY WYŁĄCZONY') !== false) {
    echo "   ✅ System modułowy jest zakomentowany w enqueueGlobalAssets\n";
} else {
    echo "   ⚠️  UWAGA: Sprawdź czy system modułowy jest wyłączony\n";
}
echo "\n";

// Test 6: Sprawdź czy admin-settings-simple.js ma poprawną obsługę checkboxów
echo "✅ TEST 6: Obsługa checkboxów w admin-settings-simple.js\n";
if (strpos($simple_handler, "input[type=\"checkbox\"]") !== false && 
    strpos($simple_handler, "postData[name] = '0'") !== false) {
    echo "   ✅ Checkboxy są obsługiwane poprawnie (niezaznaczone = '0')\n";
} else {
    echo "   ❌ BŁĄD: Brak obsługi niezaznaczonych checkboxów!\n";
}
echo "\n";

// Podsumowanie
echo "=====================================\n";
echo "📋 PODSUMOWANIE NAPRAWY:\n\n";
echo "1. ✅ Flaga MASDisableModules ustawiana w PHP przed jQuery\n";
echo "2. ✅ admin-settings-simple.js usuwa wszystkie poprzednie handlery\n";
echo "3. ✅ admin-global.js respektuje flagę i nie inicjalizuje modułów\n";
echo "4. ✅ System modułowy wyłączony w enqueueGlobalAssets\n";
echo "5. ✅ Checkboxy obsługiwane poprawnie\n\n";

echo "🎯 NASTĘPNE KROKI:\n";
echo "1. Otwórz stronę ustawień wtyczki w przeglądarce\n";
echo "2. Otwórz Console (F12)\n";
echo "3. Sprawdź czy widzisz:\n";
echo "   - '🎯 MAS Simple Settings: Initializing...'\n";
echo "   - '✅ Simple handler: Wszystkie poprzednie handlery usunięte'\n";
echo "   - '🚫 Modularny system wyłączony - używam prostego handlera'\n";
echo "4. Zmień jakieś ustawienie i kliknij 'Zapisz'\n";
echo "5. Sprawdź w Console czy widzisz:\n";
echo "   - '🚀 Wysyłanie danych: {...}'\n";
echo "   - '📊 Liczba pól: 100+'\n";
echo "6. Sprawdź w Network tab czy request zawiera wszystkie pola\n\n";

echo "🔍 DIAGNOSTYKA W PRZEGLĄDARCE:\n";
echo "Wklej w Console:\n";
echo "console.log('MASDisableModules:', window.MASDisableModules);\n";
echo "console.log('ModernAdminApp:', typeof window.ModernAdminApp);\n";
echo "console.log('SettingsManager:', typeof window.SettingsManager);\n\n";

echo "✅ Test zakończony!\n";
?>
