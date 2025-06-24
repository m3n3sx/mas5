/**
 * MenuManager Fixed - Modern Admin Styler V2
 * 
 * 🎯 PROSTA IMPLEMENTACJA:
 * - Minimalna ingerencja w WordPress
 * - Tylko CSS Variables i klasy body
 * - Zero inline styles
 * - Pełna kompatybilność
 */

class MenuManagerFixed {
    constructor() {
        this.settings = {};
        this.initialized = false;
        
        console.log('🔧 MenuManagerFixed: Initialized');
    }
    
    init(settings = {}) {
        if (this.initialized) {
            console.log('🔧 MenuManagerFixed: Already initialized, updating only');
            this.updateSettings(settings);
            return;
        }
        
        this.settings = settings;
        this.applyMenuEnhancements();
        this.updateCSSVariables();
        this.setupEventListeners();
        
        this.initialized = true;
        console.log('🔧 MenuManagerFixed: Ready!', settings);
    }
    
    /**
     * Aplikuje podstawowe ulepszenia menu
     */
    applyMenuEnhancements() {
        const body = document.body;
        
        // Zawsze dodaj podstawową klasę jeśli włączone
        if (this.settings.enable_plugin) {
            body.classList.add('mas-v2-menu-enhanced');
        }
        
        // Floating mode
        if (this.settings.menu_floating || this.settings.menu_detached) {
            body.classList.add('mas-v2-menu-floating');
        } else {
            body.classList.remove('mas-v2-menu-floating');
        }
        
        // Detached mode (floating z marginesami)
        if (this.settings.menu_detached) {
            body.classList.add('mas-v2-menu-detached');
        } else {
            body.classList.remove('mas-v2-menu-detached');
        }
        
        // Rounded corners
        if (this.settings.menu_rounded_corners || this.settings.menu_border_radius > 0) {
            body.classList.add('mas-v2-menu-rounded');
        } else {
            body.classList.remove('mas-v2-menu-rounded');
        }
        
        // Shadow effect
        if (this.settings.menu_shadow) {
            body.classList.add('mas-v2-menu-shadow');
        } else {
            body.classList.remove('mas-v2-menu-shadow');
        }
        
        // Glassmorphism
        if (this.settings.menu_glassmorphism || this.settings.menu_glossy) {
            body.classList.add('mas-v2-menu-glass');
        } else {
            body.classList.remove('mas-v2-menu-glass');
        }
        
        // Smooth animations
        if (this.settings.enable_animations) {
            body.classList.add('mas-v2-menu-smooth');
        } else {
            body.classList.remove('mas-v2-menu-smooth');
        }
        
        // Active indicators
        if (this.settings.menu_active_indicator !== false) {
            body.classList.add('mas-v2-menu-indicators');
        } else {
            body.classList.remove('mas-v2-menu-indicators');
        }
    }
    
    /**
     * Aktualizuje CSS Variables
     */
    updateCSSVariables() {
        const root = document.documentElement;
        
        // Kolory menu
        if (this.settings.menu_background || this.settings.menu_bg) {
            root.style.setProperty('--mas-menu-bg', this.settings.menu_background || this.settings.menu_bg);
        }
        
        if (this.settings.menu_text_color) {
            root.style.setProperty('--mas-menu-text', this.settings.menu_text_color);
            root.style.setProperty('--mas-menu-icon-color', this.adjustBrightness(this.settings.menu_text_color, -20));
        }
        
        if (this.settings.menu_hover_background) {
            root.style.setProperty('--mas-menu-hover-bg', this.settings.menu_hover_background);
        }
        
        if (this.settings.menu_hover_text_color) {
            root.style.setProperty('--mas-menu-hover-text', this.settings.menu_hover_text_color);
        }
        
        if (this.settings.menu_active_background) {
            root.style.setProperty('--mas-menu-active-bg', this.settings.menu_active_background);
        }
        
        if (this.settings.menu_active_text_color) {
            root.style.setProperty('--mas-menu-active-text', this.settings.menu_active_text_color);
        }
        
        // Kolory submenu
        if (this.settings.submenu_background) {
            root.style.setProperty('--mas-submenu-bg', this.settings.submenu_background);
        }
        
        if (this.settings.submenu_text_color) {
            root.style.setProperty('--mas-submenu-text', this.settings.submenu_text_color);
        }
        
        if (this.settings.submenu_hover_background) {
            root.style.setProperty('--mas-submenu-hover-bg', this.settings.submenu_hover_background);
        }
        
        if (this.settings.submenu_hover_text_color) {
            root.style.setProperty('--mas-submenu-hover-text', this.settings.submenu_hover_text_color);
        }
        
        // Wymiary
        if (this.settings.menu_width) {
            root.style.setProperty('--mas-menu-width', this.settings.menu_width + 'px');
        }
        
        if (this.settings.menu_item_height) {
            root.style.setProperty('--mas-menu-item-height', this.settings.menu_item_height + 'px');
        }
        
        if (this.settings.menu_border_radius || this.settings.menu_border_radius_all) {
            const radius = this.settings.menu_border_radius_all || this.settings.menu_border_radius || 0;
            root.style.setProperty('--mas-menu-radius', radius + 'px');
        }
        
        // Transition speed
        if (this.settings.animation_speed) {
            root.style.setProperty('--mas-transition-speed', this.settings.animation_speed + 'ms');
        }
    }
    
    /**
     * Event listeners
     */
    setupEventListeners() {
        // Obsługa collapse menu
        const collapseButton = document.querySelector('#collapse-menu');
        if (collapseButton) {
            // WordPress już obsługuje collapse, my tylko synchronizujemy
            collapseButton.addEventListener('click', () => {
                setTimeout(() => {
                    this.handleMenuCollapse();
                }, 100);
            });
        }
        
        // Live preview dla strony ustawień
        if (window.location.href.includes('mas-v2')) {
            this.setupLivePreview();
        }
    }
    
    /**
     * Obsługa zwinięcia menu
     */
    handleMenuCollapse() {
        const isCollapsed = document.body.classList.contains('folded');
        console.log('🔧 Menu collapsed:', isCollapsed);
        
        // Możemy dodać dodatkowe akcje jeśli potrzeba
        if (isCollapsed) {
            localStorage.setItem('mas_menu_collapsed', 'true');
        } else {
            localStorage.removeItem('mas_menu_collapsed');
        }
    }
    
    /**
     * Live preview na stronie ustawień
     */
    setupLivePreview() {
        // Nasłuchuj na zmiany w formularzach
        document.addEventListener('input', (e) => {
            const input = e.target;
            if (!input.name || !input.name.startsWith('menu_')) return;
            
            // Aktualizuj ustawienie
            const settingName = input.name;
            let value = input.type === 'checkbox' ? input.checked : input.value;
            
            // Konwersja typów
            if (input.type === 'number') {
                value = parseInt(value) || 0;
            }
            
            this.settings[settingName] = value;
            
            // Zastosuj zmiany
            this.applyMenuEnhancements();
            this.updateCSSVariables();
            
            console.log('🔧 Live preview:', settingName, value);
        });
    }
    
    /**
     * Update settings
     */
    updateSettings(newSettings) {
        this.settings = { ...this.settings, ...newSettings };
        this.applyMenuEnhancements();
        this.updateCSSVariables();
    }
    
    /**
     * Helper: Adjust color brightness
     */
    adjustBrightness(color, percent) {
        // Konwertuj hex na RGB
        const hex = color.replace('#', '');
        const r = parseInt(hex.substr(0, 2), 16);
        const g = parseInt(hex.substr(2, 2), 16);
        const b = parseInt(hex.substr(4, 2), 16);
        
        // Adjust brightness
        const adjust = (val) => {
            val = val + (255 - val) * (percent / 100);
            return Math.min(255, Math.max(0, Math.round(val)));
        };
        
        // Convert back to hex
        const toHex = (val) => val.toString(16).padStart(2, '0');
        
        return `#${toHex(adjust(r))}${toHex(adjust(g))}${toHex(adjust(b))}`;
    }
    
    /**
     * Restore collapsed state
     */
    restoreCollapsedState() {
        const wasCollapsed = localStorage.getItem('mas_menu_collapsed') === 'true';
        if (wasCollapsed && !document.body.classList.contains('folded')) {
            // Symuluj kliknięcie collapse button
            const collapseButton = document.querySelector('#collapse-menu');
            if (collapseButton) {
                collapseButton.click();
            }
        }
    }
}

// Export dla modułów
export default MenuManagerFixed;

// Global export dla kompatybilności
window.MenuManagerFixed = MenuManagerFixed; 