/**
 * Modern Admin Styler V2 - Theme Manager Module
 * Centralny moduł zarządzania motywami (light/dark)
 */

class ThemeManager {
    constructor(app) {
        this.app = app; // Reference to the main app
        this.currentTheme = this.getStoredTheme() || this.getSystemTheme();
        this.isInitialized = false;
        this.notificationManager = null; // Will be set after app init
    }
    
    init() {
        if (this.isInitialized) {
            console.warn('ThemeManager już został zainicjalizowany');
            return;
        }
        
        this.applyTheme(this.currentTheme);
        this.createThemeToggle();
        this.notificationManager = this.app.getModule('notificationManager');
        this.setupSystemThemeListener();
        this.isInitialized = true;
        
        // Dodaj event listener dla zewnętrznych komponentów
        document.addEventListener('mas-theme-changed', (e) => {
            this.handleExternalThemeChange(e.detail.theme);
        });
    }
    
    getSystemTheme() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    
    getStoredTheme() {
        return localStorage.getItem('mas-v2-theme');
    }
    
    setStoredTheme(theme) {
        localStorage.setItem('mas-v2-theme', theme);
    }
    
    applyTheme(theme) {
        const body = document.body;
        
        // Usuń wszystkie klasy motywów
        body.classList.remove('mas-v2-light', 'mas-v2-dark', 'mas-v2-auto');
        
        // Dodaj nową klasę motywu
        body.classList.add(`mas-v2-${theme}`);
        
        // Ustaw CSS custom properties
        this.setCSSProperties(theme);
        
        this.currentTheme = theme;
        this.setStoredTheme(theme);
        
        // Wyślij event dla innych modułów
        this.dispatchThemeEvent(theme);
    }
    
    setCSSProperties(theme) {
        const root = document.documentElement;
        
        if (theme === 'dark') {
            root.style.setProperty('--mas-bg-primary', '#1a1a1a');
            root.style.setProperty('--mas-bg-secondary', '#2d2d2d');
            root.style.setProperty('--mas-text-primary', '#ffffff');
            root.style.setProperty('--mas-text-secondary', '#cccccc');
            root.style.setProperty('--mas-border-color', '#444444');
        } else {
            root.style.setProperty('--mas-bg-primary', '#ffffff');
            root.style.setProperty('--mas-bg-secondary', '#f9f9f9');
            root.style.setProperty('--mas-text-primary', '#333333');
            root.style.setProperty('--mas-text-secondary', '#666666');
            root.style.setProperty('--mas-border-color', '#e0e0e0');
        }
    }
    
    toggleTheme() {
        const newTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
        this.applyTheme(newTheme);
        const message = newTheme === 'dark' ? 'Przełączono na tryb ciemny' : 'Przełączono na tryb jasny';
        this.notificationManager?.show(message, 'theme', 3000, 'top-right');
    }
    
    createThemeToggle() {
        // Sprawdź czy toggle już istnieje
        if (document.querySelector('.mas-theme-toggle')) {
            return;
        }
        
        const toggle = document.createElement('button');
        toggle.className = 'mas-theme-toggle';
        toggle.setAttribute('aria-label', 'Przełącz motyw');
        toggle.innerHTML = this.getThemeIcon();
        
        // Style
        Object.assign(toggle.style, {
            position: 'fixed',
            top: '60px',
            right: '20px',
            width: '44px',
            height: '44px',
            borderRadius: '50%',
            border: 'none',
            backgroundColor: 'var(--mas-bg-primary)',
            color: 'var(--mas-text-primary)',
            cursor: 'pointer',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            fontSize: '18px',
            boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
            transition: 'all 0.3s ease',
            zIndex: '999998'
        });
        
        toggle.addEventListener('click', () => this.toggleTheme());
        toggle.addEventListener('mouseenter', () => {
            toggle.style.transform = 'scale(1.1)';
        });
        toggle.addEventListener('mouseleave', () => {
            toggle.style.transform = 'scale(1)';
        });
        
        document.body.appendChild(toggle);
    }
    
    getThemeIcon() {
        return this.currentTheme === 'dark' ? '☀️' : '🌙';
    }
    
    updateThemeToggle() {
        const toggle = document.querySelector('.mas-theme-toggle');
        if (toggle) {
            toggle.innerHTML = this.getThemeIcon();
        }
    }
    
    setupSystemThemeListener() {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        mediaQuery.addEventListener('change', (e) => {
            if (!this.getStoredTheme()) { // Tylko jeśli nie ma zapisanego motywu
                const newTheme = e.matches ? 'dark' : 'light';
                this.applyTheme(newTheme);
            }
        });
    }
    
    dispatchThemeEvent(theme) {
        const event = new CustomEvent('mas-theme-changed', {
            detail: { theme, manager: this }
        });
        document.dispatchEvent(event);
    }
    
    handleExternalThemeChange(theme) {
        if (theme !== this.currentTheme) {
            this.applyTheme(theme);
            this.updateThemeToggle();
        }
    }
    
    // Publiczne API
    getCurrentTheme() {
        return this.currentTheme;
    }
    
    setTheme(theme) {
        if (['light', 'dark'].includes(theme)) {
            this.applyTheme(theme);
            this.updateThemeToggle();
        }
    }
}

// Eksport dla użycia w innych modułach
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThemeManager;
} else {
    window.ThemeManager = ThemeManager;
} 