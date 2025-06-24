/**
 * Modern Admin Styler V2 - Live Preview Manager Module
 * Wydajny live preview oparty na CSS Custom Properties
 */

class LivePreviewManager {
    constructor(app) {
        this.app = app; // Reference to the main app
        this.isEnabled = false;
        this.root = document.documentElement;
        this.timeouts = new Map();
        this.isInitialized = false;
        this.cssVariables = new Map();
        this.notificationManager = null; // Will be set after app init
        this.initCSSVariables();
    }
    
    init() {
        if (this.isInitialized) {
            console.warn('LivePreviewManager już został zainicjalizowany');
            return;
        }
        
        this.setupEventListeners();
        this.createToggleButton();
        this.notificationManager = this.app.getModule('notificationManager');
        this.restoreState();
        this.isInitialized = true;
    }
    
    initCSSVariables() {
        // Mapowanie pól formularza na CSS custom properties
        this.cssVariables.set('menu_margin_top', '--mas-menu-margin-top');
        this.cssVariables.set('menu_margin_right', '--mas-menu-margin-right');
        this.cssVariables.set('menu_margin_bottom', '--mas-menu-margin-bottom');
        this.cssVariables.set('menu_margin_left', '--mas-menu-margin-left');
        this.cssVariables.set('menu_margin_all', '--mas-menu-margin-all');
        
        this.cssVariables.set('admin_bar_margin_top', '--mas-admin-bar-margin-top');
        this.cssVariables.set('admin_bar_margin_right', '--mas-admin-bar-margin-right');
        this.cssVariables.set('admin_bar_margin_bottom', '--mas-admin-bar-margin-bottom');
        this.cssVariables.set('admin_bar_margin_left', '--mas-admin-bar-margin-left');
        this.cssVariables.set('admin_bar_margin_all', '--mas-admin-bar-margin-all');
        
        this.cssVariables.set('corner_radius_all', '--mas-corner-radius-all');
        this.cssVariables.set('corner_radius_top_left', '--mas-corner-radius-top-left');
        this.cssVariables.set('corner_radius_top_right', '--mas-corner-radius-top-right');
        this.cssVariables.set('corner_radius_bottom_left', '--mas-corner-radius-bottom-left');
        this.cssVariables.set('corner_radius_bottom_right', '--mas-corner-radius-bottom-right');
        
        this.cssVariables.set('admin_bar_corner_radius_all', '--mas-admin-bar-corner-radius-all');
        this.cssVariables.set('admin_bar_corner_radius_top_left', '--mas-admin-bar-corner-radius-top-left');
        this.cssVariables.set('admin_bar_corner_radius_top_right', '--mas-admin-bar-corner-radius-top-right');
        this.cssVariables.set('admin_bar_corner_radius_bottom_left', '--mas-admin-bar-corner-radius-bottom-left');
        this.cssVariables.set('admin_bar_corner_radius_bottom_right', '--mas-admin-bar-corner-radius-bottom-right');
        
        // Kolory menu
        this.cssVariables.set('menu_bg', '--mas-menu-bg-color');
        this.cssVariables.set('menu_text_color', '--mas-menu-text-color');
        this.cssVariables.set('menu_hover_color', '--mas-menu-hover-color');
        this.cssVariables.set('menu_background_color', '--mas-menu-bg-color');
        
        // Wymiary menu
        this.cssVariables.set('menu_width', '--mas-menu-width');
        this.cssVariables.set('menu_border_radius_all', '--mas-menu-border-radius');
        
        // Admin bar kolory
        this.cssVariables.set('admin_bar_background_color', '--mas-admin-bar-bg-color');
        this.cssVariables.set('admin_bar_text_color', '--mas-admin-bar-text-color');
        
        // Gradient backgrounds
        this.cssVariables.set('menu_gradient_start', '--mas-menu-gradient-start');
        this.cssVariables.set('menu_gradient_end', '--mas-menu-gradient-end');
        this.cssVariables.set('admin_bar_gradient_start', '--mas-admin-bar-gradient-start');
        this.cssVariables.set('admin_bar_gradient_end', '--mas-admin-bar-gradient-end');
    }
    
    setupEventListeners() {
        // Słuchaj zmian w formularzach
        document.addEventListener('change', (e) => this.handleFormChange(e));
        document.addEventListener('input', (e) => this.handleFormChange(e));
        document.addEventListener('keyup', (e) => this.handleFormChange(e));
        
        // Color picker events
        document.addEventListener('wpColorPickerChange', (e) => this.handleColorChange(e));
        
        // Checkbox i radio changes
        document.addEventListener('change', (e) => {
            if (e.target.type === 'checkbox' || e.target.type === 'radio') {
                this.handleFormChange(e);
            }
        });
    }
    
    handleFormChange(e) {
        if (!this.isEnabled) return;
        
        const input = e.target;
        const form = input.closest('#mas-v2-settings-form');
        if (!form) return;
        
        const fieldName = input.name;
        const fieldType = input.type;
        
        // Throttle dla różnych typów pól
        const delay = this.getThrottleDelay(fieldType);
        
        this.throttledUpdate(fieldName, () => {
            this.updateCSSVariable(fieldName, input);
        }, delay);
    }
    
    handleColorChange(e) {
        if (!this.isEnabled) return;
        
        const input = e.target;
        const fieldName = input.name;
        
        this.throttledUpdate(fieldName, () => {
            this.updateCSSVariable(fieldName, input);
        }, 100);
    }
    
    getThrottleDelay(fieldType) {
        switch (fieldType) {
            case 'range':
                return 50; // Szybko dla sliderów
            case 'color':
                return 100; // Średnio dla kolorów
            case 'text':
            case 'number':
                return 200; // Wolniej dla tekstu
            default:
                return 150;
        }
    }
    
    throttledUpdate(key, callback, delay) {
        // Anuluj poprzedni timeout dla tego klucza
        if (this.timeouts.has(key)) {
            clearTimeout(this.timeouts.get(key));
        }
        
        // Ustaw nowy timeout
        const timeoutId = setTimeout(() => {
            callback();
            this.timeouts.delete(key);
        }, delay);
        
        this.timeouts.set(key, timeoutId);
    }
    
    updateCSSVariable(fieldName, input) {
        const cssVar = this.cssVariables.get(fieldName);
        if (!cssVar) return;
        
        let value = this.getInputValue(input);
        
        // Przetwórz wartość w zależności od typu
        value = this.processValue(fieldName, value, input);
        
        // Ustaw CSS custom property
        this.root.style.setProperty(cssVar, value);
        
        // Specjalne obsługiwanie dla menu floating checkbox
        if (fieldName === 'menu_floating' && input.type === 'checkbox') {
            this.handleMenuFloatingChange(input.checked);
        }
        
        // Wyślij event o zmianie
        this.dispatchPreviewEvent(fieldName, value, cssVar);
    }
    
    getInputValue(input) {
        switch (input.type) {
            case 'checkbox':
                return input.checked;
            case 'radio':
                return input.checked ? input.value : null;
            case 'color':
                return input.value;
            case 'range':
            case 'number':
                return parseInt(input.value, 10);
            default:
                return input.value;
        }
    }
    
    processValue(fieldName, value, input) {
        // Jeśli to checkbox i jest odznaczony, nie zmieniaj CSS
        if (input.type === 'checkbox' && !value) {
            return null; // Nie ustawi CSS variable
        }
        
        // Jeśli to radio i nie jest zaznaczony, nie zmieniaj CSS
        if (input.type === 'radio' && !value) {
            return null;
        }
        
        // Dodaj jednostki dla wartości numerycznych
        if (typeof value === 'number' && this.needsPixelUnit(fieldName)) {
            return value + 'px';
        }
        
        // Dla kolorów - upewnij się że mają #
        if (input.type === 'color' && !value.startsWith('#')) {
            return '#' + value;
        }
        
        return value;
    }
    
    needsPixelUnit(fieldName) {
        const pixelFields = [
            'menu_margin_top', 'menu_margin_right', 'menu_margin_bottom', 'menu_margin_left', 'menu_margin_all',
            'admin_bar_margin_top', 'admin_bar_margin_right', 'admin_bar_margin_bottom', 'admin_bar_margin_left', 'admin_bar_margin_all',
            'corner_radius_all', 'corner_radius_top_left', 'corner_radius_top_right', 'corner_radius_bottom_left', 'corner_radius_bottom_right',
            'admin_bar_corner_radius_all', 'admin_bar_corner_radius_top_left', 'admin_bar_corner_radius_top_right', 
            'admin_bar_corner_radius_bottom_left', 'admin_bar_corner_radius_bottom_right',
            'menu_width', 'menu_border_radius_all', 'menu_border_radius'
        ];
        
        return pixelFields.includes(fieldName);
    }
    
    enable() {
        this.isEnabled = true;
        localStorage.setItem('mas-v2-live-preview', 'enabled');
        this.updateToggleButton();
        this.notificationManager?.show('Live Preview włączony', 'success', 2000, 'top-right');
        
        // Natychmiast zastosuj wszystkie wartości z formularza, jeśli formularz jest dostępny
        if (document.querySelector('#mas-v2-settings-form')) {
            this.applyAllFormValues();
        }
    }
    
    disable() {
        this.isEnabled = false;
        localStorage.setItem('mas-v2-live-preview', 'disabled');
        this.updateToggleButton();
        this.clearAllCSSVariables(); // This should clear the applied styles
        this.notificationManager?.show('Live Preview wyłączony', 'info', 2000, 'top-right');
    }
    
    toggle() {
        if (this.isEnabled) {
            this.disable();
        } else {
            this.enable();
        }
    }
    
    applyAllFormValues() {
        const form = document.querySelector('#mas-v2-settings-form');
        if (!form) return;
        
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.name && this.cssVariables.has(input.name)) {
                this.updateCSSVariable(input.name, input);
            }
        });
    }
    
    clearAllCSSVariables() {
        this.cssVariables.forEach((cssVar) => {
            this.root.style.removeProperty(cssVar);
        });
    }
    
    createToggleButton() {
        if (document.querySelector('.mas-live-preview-toggle')) {
            return;
        }
        
        const toggle = document.createElement('button');
        toggle.className = 'mas-live-preview-toggle';
        toggle.setAttribute('aria-label', 'Przełącz Live Preview');
        toggle.innerHTML = this.getToggleIcon();
        
        Object.assign(toggle.style, {
            position: 'fixed',
            top: '110px',
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
            fontSize: '16px',
            boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
            transition: 'all 0.3s ease',
            zIndex: '999997'
        });
        
        toggle.addEventListener('click', () => this.toggle());
        
        document.body.appendChild(toggle);
        this.updateToggleButton();
    }
    
    getToggleIcon() {
        return this.isEnabled ? '👁️' : '👁️‍🗨️';
    }
    
    updateToggleButton() {
        const toggle = document.querySelector('.mas-live-preview-toggle');
        if (toggle) {
            toggle.innerHTML = this.getToggleIcon();
            toggle.style.backgroundColor = this.isEnabled ? '#4CAF50' : 'var(--mas-bg-primary)';
            toggle.title = this.isEnabled ? 'Live Preview włączony' : 'Live Preview wyłączony';
        }
    }
    
    restoreState() {
        const saved = localStorage.getItem('mas-v2-live-preview');
        if (saved === 'enabled') {
            this.enable();
        } else {
            this.disable();
        }
    }
    
    dispatchPreviewEvent(fieldName, value, cssVar) {
        const event = new CustomEvent('mas-live-preview-changed', {
            detail: {
                fieldName,
                value,
                cssVar,
                timestamp: Date.now()
            }
        });
        document.dispatchEvent(event);
    }
    
    // Publiczne API
    isActive() {
        return this.isEnabled;
    }
    
    getCurrentState() {
        return {
            enabled: this.isEnabled,
            activeVariables: Array.from(this.cssVariables.entries()),
            pendingTimeouts: this.timeouts.size
        };
    }
    
    handleMenuFloatingChange(isFloating) {
        // Notify MenuManager o zmianie floating
        const event = new CustomEvent('mas-live-preview-update', {
            detail: {
                settings: { menu_floating: isFloating },
                source: 'live-preview'
            }
        });
        document.dispatchEvent(event);
    }
}

// Eksport dla użycia w innych modułach
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LivePreviewManager;
} else {
    window.LivePreviewManager = LivePreviewManager;
} 