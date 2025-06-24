#!/bin/bash

# Localhost Viewer - Cursor Extension Installer
echo "🚀 Instalowanie Localhost Viewer Extension dla Cursor..."

# Sprawdź czy jesteśmy w odpowiednim folderze
if [ ! -f "package.json" ]; then
    echo "❌ Błąd: Uruchom skrypt z folderu cursor-localhost-viewer"
    exit 1
fi

# Sprawdź czy Node.js jest zainstalowany
if ! command -v node &> /dev/null; then
    echo "❌ Błąd: Node.js nie jest zainstalowany"
    echo "Zainstaluj Node.js z: https://nodejs.org/"
    exit 1
fi

# Sprawdź czy npm jest zainstalowany
if ! command -v npm &> /dev/null; then
    echo "❌ Błąd: npm nie jest zainstalowany"
    exit 1
fi

echo "📦 Instalowanie zależności..."
npm install

if [ $? -ne 0 ]; then
    echo "❌ Błąd podczas instalacji zależności"
    exit 1
fi

echo "🔨 Kompilowanie extension..."
npm run compile

if [ $? -ne 0 ]; then
    echo "❌ Błąd podczas kompilacji"
    exit 1
fi

# Znajdź folder extensions Cursor
CURSOR_EXTENSIONS_DIR=""

# Linux
if [ -d "$HOME/.config/Cursor/User/extensions" ]; then
    CURSOR_EXTENSIONS_DIR="$HOME/.config/Cursor/User/extensions"
elif [ -d "$HOME/.config/Code/User/extensions" ]; then
    CURSOR_EXTENSIONS_DIR="$HOME/.config/Code/User/extensions"
fi

# macOS
if [ -z "$CURSOR_EXTENSIONS_DIR" ] && [ -d "$HOME/Library/Application Support/Cursor/User/extensions" ]; then
    CURSOR_EXTENSIONS_DIR="$HOME/Library/Application Support/Cursor/User/extensions"
fi

# Windows (jeśli uruchomiony przez WSL)
if [ -z "$CURSOR_EXTENSIONS_DIR" ] && [ -d "/mnt/c/Users/$USER/AppData/Roaming/Cursor/User/extensions" ]; then
    CURSOR_EXTENSIONS_DIR="/mnt/c/Users/$USER/AppData/Roaming/Cursor/User/extensions"
fi

if [ -z "$CURSOR_EXTENSIONS_DIR" ]; then
    echo "❌ Nie można znaleźć folderu extensions Cursor"
    echo "Ręcznie skopiuj folder do:"
    echo "  Linux: ~/.config/Cursor/User/extensions/"
    echo "  macOS: ~/Library/Application Support/Cursor/User/extensions/"
    echo "  Windows: %APPDATA%\\Cursor\\User\\extensions\\"
    exit 1
fi

echo "📁 Znaleziono folder extensions: $CURSOR_EXTENSIONS_DIR"

# Usuń starą instalację jeśli istnieje
if [ -d "$CURSOR_EXTENSIONS_DIR/cursor-localhost-viewer" ]; then
    echo "🗑️ Usuwanie starej instalacji..."
    rm -rf "$CURSOR_EXTENSIONS_DIR/cursor-localhost-viewer"
fi

# Skopiuj extension
echo "📋 Kopiowanie extension..."
cp -r . "$CURSOR_EXTENSIONS_DIR/cursor-localhost-viewer"

if [ $? -ne 0 ]; then
    echo "❌ Błąd podczas kopiowania"
    exit 1
fi

echo "✅ Extension został zainstalowany!"
echo ""
echo "🎯 Następne kroki:"
echo "1. Restart Cursor"
echo "2. Otwórz Command Palette (Ctrl+Shift+P)"
echo "3. Wpisz 'Localhost Viewer'"
echo "4. Wybierz komendę:"
echo "   - 'Localhost Viewer: Open WordPress' (dla WordPress)"
echo "   - 'Localhost Viewer: Open URL' (dla innych stron)"
echo "   - 'Localhost Viewer: Quick Ports' (menu portów)"
echo ""
echo "🌐 WordPress powinien być dostępny na: http://localhost:10018"
echo ""
echo "🔧 Jeśli coś nie działa:"
echo "- Sprawdź czy Local by Flywheel działa"
echo "- Sprawdź port w ustawieniach extension"
echo "- Użyj 'Otwórz zewnętrznie' żeby przetestować" 