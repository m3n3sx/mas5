/**
 * Modern Admin Styler V2 - Body Class Manager Module
 * Centralny moduł zarządzania klasami CSS na elemencie <body>
 */

class BodyClassManager {
    constructor() {
        this.settings = {};
        this.body = document.body;
        this.isInitialized = false;
    }
    
    init(settings = {}) {
        if (this.isInitialized) {
            console.warn('BodyClassManager już został zainicjalizowany');
            return;
        }
        
        this.settings = settings;
        this.applyAllClasses();
        this.setupEventListeners();
        this.isInitialized = true;
    }
    
    updateSettings(newSettings) {
        this.settings = { ...this.settings, ...newSettings };
        this.applyAllClasses();
    }
    
    applyAllClasses() {
        this.applyMenuClasses();
        this.applyAdminBarClasses();
        this.applyCornerRadiusClasses();
        this.applyThemeClasses();
    }
    
    applyMenuClasses() {
        // ✅ NAPRAWIONO: Nie nadpisuj klas ustawionych przez PHP!
        // Body classes są kontrolowane przez addAdminBodyClasses()
        
        // Tylko dodaj klasy które nie są ustawiane przez PHP
        if (this.settings.menu_detached) {
            // Legacy compatibility - dodaj starą klasę
            this.body.classList.add('mas-menu-floating');
        } else {
            // Dodaj mas-menu-normal tylko jako dodatkową informację
            this.body.classList.add('mas-menu-normal');
        }
        
        // NIE DODAWAJ 'mas-v2-menu-floating' - to robi PHP!
        // NIE DODAWAJ 'mas-v2-menu-glossy' - to robi PHP!
    }
    
    applyAdminBarClasses() {
        // ✅ NAPRAWIONO: Nie nadpisuj klas ustawionych przez PHP!
        // Body classes są kontrolowane przez addAdminBodyClasses()
        
        // Tylko dodaj legacy klasy dla backward compatibility
        if (this.settings.admin_bar_detached) {
            this.body.classList.add('mas-admin-bar-floating');
        }
        
        // NIE DODAWAJ 'mas-v2-admin-bar-floating' - to robi PHP!
        // NIE DODAWAJ 'mas-v2-admin-bar-glossy' - to robi PHP!
    }
    
    applyCornerRadiusClasses() {
        // Wyczyść poprzednie klasy corner radius
        this.body.classList.remove(
            'mas-corner-radius-all', 
            'mas-corner-radius-individual'
        );
        
        if (this.settings.corner_radius_type === 'all') {
            this.body.classList.add('mas-corner-radius-all');
        } else if (this.settings.corner_radius_type === 'individual') {
            this.body.classList.add('mas-corner-radius-individual');
        }
    }
    
    applyThemeClasses() {
        // Klasy motywów są zarządzane przez ThemeManager
        // Tutaj możemy dodać dodatkowe klasy związane z ustawieniami
        
        if (this.settings.dark_mode_enabled) {
            this.body.classList.add('mas-dark-mode-enabled');
        } else {
            this.body.classList.remove('mas-dark-mode-enabled');
        }
    }
    
    setupEventListeners() {
        // Słuchaj zmian w ustawieniach
        document.addEventListener('mas-settings-changed', (e) => {
            this.updateSettings(e.detail.settings);
        });
        
        // Słuchaj zmian motywu
        document.addEventListener('mas-theme-changed', (e) => {
            this.handleThemeChange(e.detail.theme);
        });
    }
    
    handleThemeChange(theme) {
        // Aktualizuj klasy związane z motywem jeśli potrzeba
        this.body.classList.remove('mas-theme-light', 'mas-theme-dark');
        this.body.classList.add(`mas-theme-${theme}`);
    }
    
    // Metody do ręcznego zarządzania klasami
    addClass(className) {
        this.body.classList.add(className);
        this.dispatchClassChangeEvent('add', className);
    }
    
    removeClass(className) {
        this.body.classList.remove(className);
        this.dispatchClassChangeEvent('remove', className);
    }
    
    toggleClass(className, force = null) {
        const result = this.body.classList.toggle(className, force);
        this.dispatchClassChangeEvent('toggle', className, result);
        return result;
    }
    
    hasClass(className) {
        return this.body.classList.contains(className);
    }
    
    // ✅ NAPRAWIONO: Metody floating nie nadpisują PHP classes
    enableFloatingMenu() {
        // Tylko legacy klasa
        this.addClass('mas-menu-floating');
        this.removeClass('mas-menu-normal');
        
        // NIE DOTYKAJ mas-v2-menu-floating - to kontroluje PHP!
        
        // Wyślij event o zmianie
        this.dispatchFloatingChangeEvent('menu', true);
    }
    
    disableFloatingMenu() {
        // Tylko legacy klasa
        this.removeClass('mas-menu-floating');
        this.addClass('mas-menu-normal');
        
        // NIE DOTYKAJ mas-v2-menu-floating - to kontroluje PHP!
        
        // Wyślij event o zmianie
        this.dispatchFloatingChangeEvent('menu', false);
    }
    
    enableFloatingAdminBar() {
        // Tylko legacy klasa
        this.addClass('mas-admin-bar-floating');
        
        // NIE DOTYKAJ mas-v2-admin-bar-floating - to kontroluje PHP!
        
        // Wyślij event o zmianie
        this.dispatchFloatingChangeEvent('adminbar', true);
    }
    
    disableFloatingAdminBar() {
        // Tylko legacy klasa
        this.removeClass('mas-admin-bar-floating');
        
        // NIE DOTYKAJ mas-v2-admin-bar-floating - to kontroluje PHP!
        
        // Wyślij event o zmianie
        this.dispatchFloatingChangeEvent('adminbar', false);
    }
    
    // Metody do debugowania
    getActiveClasses() {
        return Array.from(this.body.classList).filter(cls => cls.startsWith('mas-'));
    }
    
    logCurrentState() {
        console.log('BodyClassManager - aktualne klasy:', this.getActiveClasses());
        console.log('BodyClassManager - aktualne ustawienia:', this.settings);
    }
    
    // Eventy
    dispatchClassChangeEvent(action, className, result = null) {
        const event = new CustomEvent('mas-body-class-changed', {
            detail: {
                action,
                className,
                result,
                allClasses: this.getActiveClasses()
            }
        });
        document.dispatchEvent(event);
    }
    
    dispatchFloatingChangeEvent(type, isFloating) {
        const event = new CustomEvent('mas-floating-changed', {
            detail: {
                type, // 'menu' lub 'adminbar'
                isFloating,
                timestamp: Date.now()
            }
        });
        document.dispatchEvent(event);
    }
    
    // Publiczne API
    getCurrentState() {
        return {
            classes: this.getActiveClasses(),
            settings: { ...this.settings },
            isFloatingMenu: this.hasClass('mas-menu-floating'),
            isFloatingAdminBar: this.hasClass('mas-admin-bar-floating')
        };
    }
}

// Eksport dla użycia w innych modułach
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BodyClassManager;
} else {
    window.BodyClassManager = BodyClassManager;
} 