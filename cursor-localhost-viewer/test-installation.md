# 🧪 Test Installation - Localhost Viewer Extension

## 🔍 Sprawdź czy extension się załadował:

### 1. Restart Cursor
Zamknij i otwórz ponownie Cursor.

### 2. Sprawdź powiadomienie
Po restarcie powinieneś zobaczyć powiadomienie:
**"🚀 Localhost Viewer Extension został załadowany!"**

### 3. Command Palette Test
- Otwórz **Command Palette** (`Ctrl+Shift+P`)
- Wpisz **"Localhost Viewer"**
- Powinieneś zobaczyć komendy:
  - `Localhost Viewer: Test Extension`
  - `Localhost Viewer: Open URL`
  - `Localhost Viewer: Open WordPress`
  - `Localhost Viewer: Quick Ports`

### 4. Test Extension
- Wybierz **"Localhost Viewer: Test Extension"**
- Powinieneś zobaczyć: **"✅ Extension działa poprawnie!"**

## 🔧 Jeśli nie widzisz komend:

### Sprawdź logi Cursor:
1. **Help** → **Toggle Developer Tools**
2. **Console** tab
3. Szukaj błędów lub komunikatów o "Localhost Viewer"

### Sprawdź czy extension jest w folderze:
```bash
ls -la ~/.config/Cursor/User/extensions/cursor-localhost-viewer/
```

### Sprawdź czy pliki są skompilowane:
```bash
ls -la ~/.config/Cursor/User/extensions/cursor-localhost-viewer/out/
```

## 🚨 Jeśli extension się nie ładuje:

### Problem 1: Cursor nie ładuje lokalnych extensions
**Rozwiązanie:** Spróbuj VSIX package
```bash
cd ~/.config/Cursor/User/extensions/cursor-localhost-viewer
npx vsce package --no-dependencies
# Następnie zainstaluj VSIX w Cursor
```

### Problem 2: Błędy kompilacji
**Rozwiązanie:** Sprawdź TypeScript
```bash
cd ~/.config/Cursor/User/extensions/cursor-localhost-viewer
npm run compile
```

### Problem 3: Brak uprawnień
**Rozwiązanie:** Sprawdź uprawnienia
```bash
chmod -R 755 ~/.config/Cursor/User/extensions/cursor-localhost-viewer/
```

## 📋 Co powinno działać:

- ✅ **Powiadomienie przy starcie** - "🚀 Localhost Viewer Extension został załadowany!"
- ✅ **Komendy w Command Palette** - 4 komendy dostępne
- ✅ **Test Extension** - "✅ Extension działa poprawnie!"
- ✅ **Logi w Console** - "Localhost Viewer extension is now active!"

## 🎯 Jeśli wszystko działa:

Gratulacje! Extension został poprawnie zainstalowany. Teraz możesz:

1. **Testować WordPress:** `Localhost Viewer: Open WordPress`
2. **Otwierać dowolne URL:** `Localhost Viewer: Open URL`
3. **Używać menu portów:** `Localhost Viewer: Quick Ports`

## 🌐 Alternatywne rozwiązanie:

Jeśli extension nie działa, możesz użyć **VSIX package**:
1. Skopiuj `cursor-localhost-viewer-0.1.0.vsix` z folderu extension
2. W Cursor: **Extensions** → **...** → **Install from VSIX**
3. Wybierz plik `.vsix` 