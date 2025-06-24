# 🧪 Test Localhost Viewer Extension

## ✅ Extension został zainstalowany!

**Lokalizacja:** `~/.config/Cursor/User/extensions/cursor-localhost-viewer`

## 🚀 Jak przetestować:

### 1. Restart Cursor
Zamknij i otwórz ponownie Cursor żeby załadować extension.

### 2. Otwórz Command Palette
- **Linux/Windows:** `Ctrl+Shift+P`
- **macOS:** `Cmd+Shift+P`

### 3. Wpisz komendy:
- `Localhost Viewer: Open WordPress` - Otwórz WordPress (port 10018)
- `Localhost Viewer: Open URL` - Otwórz dowolny URL
- `Localhost Viewer: Quick Ports` - Menu popularnych portów

## 🎯 Test WordPress:

1. Upewnij się że Local by Flywheel działa
2. Command Palette → `Localhost Viewer: Open WordPress`
3. Powinno otworzyć `http://localhost:10018` w embedded browser

## 🔧 Jeśli nie działa:

### Sprawdź logi:
1. **Help** → **Toggle Developer Tools**
2. **Console** tab
3. Szukaj błędów związanych z "Localhost Viewer"

### Sprawdź extension:
```bash
ls -la ~/.config/Cursor/User/extensions/cursor-localhost-viewer/
```

### Reinstalacja:
```bash
cd cursor-localhost-viewer
rm -rf ~/.config/Cursor/User/extensions/cursor-localhost-viewer
cp -r . ~/.config/Cursor/User/extensions/cursor-localhost-viewer
```

## 🌐 Alternatywne testy:

### Test prostego serwera:
```bash
# Terminal 1
python3 -m http.server 8000

# Terminal 2  
# Command Palette → Localhost Viewer: Open URL
# Wpisz: http://localhost:8000
```

### Test React:
```bash
# Jeśli masz React app
npx create-react-app test-app
cd test-app
npm start
# Command Palette → Localhost Viewer: Open URL
# Wpisz: http://localhost:3000
```

## 📋 Funkcje do przetestowania:

- ✅ **Embedded Browser** - Strona ładuje się w iframe
- ✅ **WordPress Support** - Automatyczne wykrywanie portu 10018
- ✅ **Error Handling** - Komunikaty gdy serwis nie działa
- ✅ **Controls** - Przyciski odświeżania, zewnętrznego otwarcia
- ✅ **Auto-refresh** - Automatyczne odświeżanie przy problemach

## 🎉 Sukces!

Jeśli wszystko działa, masz teraz **embedded Chrome browser** w Cursorze który:
- Wyświetla strony localhost bezpośrednio w IDE
- Obsługuje WordPress z Local by Flywheel
- Ma lepszą kompatybilność niż Simple Browser
- Automatycznie wykrywa popularne porty development 