# Localhost Viewer - Cursor Extension

🌐 **Embedded Chrome Browser dla Cursor** - Wyświetla strony localhost bezpośrednio w IDE

## ✨ Funkcje

- **Embedded Chrome** - Wyświetla strony w iframe z pełną funkcjonalnością
- **WordPress Support** - Automatyczne wykrywanie Local by Flywheel (port 10018)
- **Quick Ports** - Szybki dostęp do popularnych portów development
- **Auto-refresh** - Automatyczne odświeżanie przy problemach z połączeniem
- **Error Handling** - Inteligentne wykrywanie błędów i sugestie rozwiązań

## 🚀 Instalacja

### 1. Sklonuj repozytorium
```bash
git clone <repo-url>
cd cursor-localhost-viewer
```

### 2. Zainstaluj zależności
```bash
npm install
```

### 3. Skompiluj extension
```bash
npm run compile
```

### 4. Zainstaluj w Cursor
```bash
# Skopiuj folder do extensions Cursor
cp -r . ~/.config/Cursor/User/extensions/cursor-localhost-viewer
```

## 🎯 Użytkowanie

### Komendy dostępne w Command Palette (`Ctrl+Shift+P`):

1. **`Localhost Viewer: Open URL`** - Otwórz dowolny URL localhost
2. **`Localhost Viewer: Open WordPress`** - Otwórz WordPress (Local by Flywheel)
3. **`Localhost Viewer: Quick Ports`** - Menu popularnych portów

### Popularne porty:
- **WordPress** - 10018 (Local by Flywheel)
- **React Dev** - 3000
- **Vue Dev** - 8080
- **Next.js** - 3001
- **Laravel** - 8000
- **Django** - 8000

## ⚙️ Konfiguracja

W ustawieniach Cursor możesz zmienić:

```json
{
  "cursorLocalhostViewer.defaultPort": 3000,
  "cursorLocalhostViewer.wordPressPort": 10018,
  "cursorLocalhostViewer.commonPorts": [
    {
      "name": "WordPress",
      "port": 10018,
      "description": "Local by Flywheel"
    }
  ]
}
```

## 🔧 Funkcje

### Embedded Browser
- Pełna funkcjonalność Chrome w iframe
- Obsługa JavaScript, CSS, formularzy
- Sandbox security
- Responsive design

### WordPress Integration
- Automatyczne wykrywanie Local by Flywheel
- Sprawdzanie dostępności portu
- Fallback na inne porty

### Error Handling
- Wykrywanie nieaktywnych serwisów
- Auto-refresh przy problemach
- Informative error messages

## 🎨 UI Features

- **Dark Theme** - Pasuje do Cursor
- **Loading States** - Wskaźniki ładowania
- **Error States** - Czytelne komunikaty błędów
- **Controls** - Przyciski odświeżania, zewnętrznego otwarcia

## 🐛 Troubleshooting

### Biała strona
1. Sprawdź czy serwis działa: `curl http://localhost:PORT`
2. Sprawdź firewall/porty
3. Użyj "Otwórz zewnętrznie" żeby przetestować

### WordPress nie ładuje się
1. Sprawdź czy Local by Flywheel działa
2. Sprawdź port w konfiguracji
3. Restart Local by Flywheel

### Extension nie działa
1. Sprawdź czy jest skompilowany: `npm run compile`
2. Restart Cursor
3. Sprawdź logi: `Help > Toggle Developer Tools`

## 📝 Development

### Struktura plików:
```
cursor-localhost-viewer/
├── src/
│   └── extension.ts      # Główna logika
├── package.json          # Konfiguracja extension
├── tsconfig.json         # TypeScript config
└── README.md            # Dokumentacja
```

### Komendy development:
```bash
npm run compile          # Kompiluj TypeScript
npm run watch            # Auto-kompilacja
npm run lint             # Sprawdź kod
```

## 🤝 Contributing

1. Fork repo
2. Stwórz feature branch
3. Commit changes
4. Push branch
5. Stwórz Pull Request

## 📄 License

MIT License - zobacz LICENSE file

## 🙏 Credits

Stworzone dla społeczności Cursor/VS Code developers 