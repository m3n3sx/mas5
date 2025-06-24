/**
 * Modern Admin Styler V2 - Menu Manager Module (REFACTORED)
 * 
 * ✅ NOWA ARCHITEKTURA:
 * - Nie generuje CSS w JavaScript
 * - Zarządza tylko CSS Variables
 * - Style są w dedykowanym pliku admin-menu-modern.css
 * - Używa event listeners zamiast inline CSS
 */

console.log('🧪 DEBUG: MenuManager.js został załadowany!');

class MenuManager {
    constructor() {
        this.settings = {};
        this.isFloating = false;
        this.isGlossy = false;
        this.isInitialized = false;
        this.resizeObserver = null;
        this.activeSubmenu = null;
        
        console.log('🎯 MenuManager: Initialized (modern architecture)');
    }
    
    init(settings = {}) {
        if (this.isInitialized) {
            console.log('🎯 MenuManager: Already initialized, updating settings only');
            this.updateSettings(settings);
            return;
        }
        
        console.log('🎯 MenuManager: Starting initialization...');
        
        this.applySettings(settings);
        this.updateBodyClasses();
        this.updateCSSVariables(settings);
        this.setupEventListeners();
        this.initSubmenuBehavior();
        this.handleResize();
        
        // NOWY: Nasłuchuj zmian ustawień w czasie rzeczywistym
        this.setupLiveSettingsUpdates();
        
        this.isInitialized = true;
        console.log('🎯 MenuManager: ✅ Fully initialized');
    }
    
    /**
     * Aplikuje ustawienia do internal state
     */
    applySettings(settings) {
        this.settings = { ...this.settings, ...settings };
        
        // Sprawdź floating mode
        this.isFloating = settings.menu_floating === true || settings.menu_floating === 'true';
        this.isGlossy = settings.menu_glossy === true || settings.menu_glossy === 'true';
        
        console.log('🎯 MenuManager: Settings applied', {
            floating: this.isFloating,
            glossy: this.isGlossy
        });
    }
    
    /**
     * Aktualizuje klasy body na podstawie ustawień
     */
    updateBodyClasses() {
        const body = document.body;
        
        // Menu floating
        if (this.isFloating) {
            body.classList.add('mas-v2-menu-floating');
        } else {
            body.classList.remove('mas-v2-menu-floating');
        }
        
        // Menu glossy
        if (this.isGlossy) {
            body.classList.add('mas-v2-menu-glossy');
        } else {
            body.classList.remove('mas-v2-menu-glossy');
        }
        
        // Modern style - zawsze aktywne
        body.classList.add('mas-v2-modern-style');
        
        // Additional classes based on settings
        if (this.settings.menu_shadow) {
            body.classList.add('mas-v2-menu-shadows');
        } else {
            body.classList.remove('mas-v2-menu-shadows');
        }
        
        if (this.settings.menu_rounded_corners || this.settings.menu_border_radius_all > 0) {
            body.classList.add('mas-v2-menu-rounded');
        } else {
            body.classList.remove('mas-v2-menu-rounded');
        }
        
        if (this.settings.menu_compact_mode) {
            body.classList.add('mas-v2-menu-compact');
        } else {
            body.classList.remove('mas-v2-menu-compact');
        }
        
        console.log('🎯 MenuManager: Body classes updated', {
            floating: this.isFloating,
            glossy: this.isGlossy,
            shadow: this.settings.menu_shadow,
            rounded: this.settings.menu_rounded_corners,
            compact: this.settings.menu_compact_mode
        });
    }
    
    /**
     * NOWA METODA: Zarządza CSS Variables i body classes z ustawień wtyczki
     * Używa nowego systemu admin-menu-reset.css
     */
    updateCSSVariables(settings) {
        const root = document.documentElement;
        const body = document.body;
        
        console.log('🎯 MenuManager: Updating CSS Variables and body classes from settings', settings);
        console.log('🧪 DEBUG: MenuManager.updateCSSVariables() został wywołany!');
        
        // ========== RESET DO WORDPRESS DEFAULT ==========
        // Usuń wszystkie klasy custom menu
        body.classList.remove(
            'mas-v2-menu-custom-enabled',
            'mas-v2-menu-floating-enabled', 
            'mas-v2-submenu-custom-enabled',
            'mas-v2-menu-shadow-enabled',
            'mas-v2-menu-glossy-enabled'
        );
        
        // Reset CSS Variables do defaultów WordPress
        root.style.setProperty('--mas-menu-enabled', '0');
        root.style.setProperty('--mas-menu-floating-enabled', '0');
        root.style.setProperty('--mas-submenu-enabled', '0');
        root.style.setProperty('--mas-menu-shadow-enabled', '0');
        root.style.setProperty('--mas-menu-glossy-enabled', '0');
        root.style.setProperty('--mas-menu-animation-enabled', '0');
        
        // ========== SPRAWDŹ CZY WŁĄCZYĆ CUSTOM MENU ==========
        const hasMenuCustomizations = (
            settings.menu_background || 
            settings.menu_bg ||
            settings.menu_text_color || 
            settings.menu_hover_background ||
            settings.menu_hover_text_color ||
            settings.menu_active_background ||
            settings.menu_active_text_color ||
            settings.menu_width ||
            settings.menu_item_height ||
            settings.menu_border_radius ||
            settings.menu_border_radius_all ||
            settings.menu_detached_margin ||
            settings.menu_margin ||
            settings.modern_menu_style ||
            settings.auto_fold_menu
        );
        
        if (hasMenuCustomizations) {
            body.classList.add('mas-v2-menu-custom-enabled');
            root.style.setProperty('--mas-menu-enabled', '1');
            
            // ========== PODSTAWOWE KOLORY MENU ==========
            if (settings.menu_background || settings.menu_bg) {
                root.style.setProperty('--mas-menu-bg-color', settings.menu_background || settings.menu_bg);
            }
            
            if (settings.menu_text_color) {
                root.style.setProperty('--mas-menu-text-color', settings.menu_text_color);
            }
            
            if (settings.menu_hover_background || settings.menu_hover_color) {
                root.style.setProperty('--mas-menu-hover-bg', settings.menu_hover_background || settings.menu_hover_color);
            }
            
            if (settings.menu_hover_text_color) {
                root.style.setProperty('--mas-menu-hover-text', settings.menu_hover_text_color);
            }
            
            if (settings.menu_active_background) {
                root.style.setProperty('--mas-menu-active-bg', settings.menu_active_background);
            }
            
            if (settings.menu_active_text_color) {
                root.style.setProperty('--mas-menu-active-text', settings.menu_active_text_color);
            }
            
            // ========== WYMIARY MENU ==========
            if (settings.menu_width) {
                root.style.setProperty('--mas-menu-width', settings.menu_width + 'px');
            }
            
            if (settings.menu_item_height) {
                root.style.setProperty('--mas-menu-item-height', settings.menu_item_height + 'px');
            }
            
            if (settings.menu_item_padding) {
                root.style.setProperty('--mas-menu-item-padding', settings.menu_item_padding);
            } else {
                root.style.setProperty('--mas-menu-item-padding', '8px 12px');
            }
            
            // ========== MARGINS & SPACING ==========
            // Regular margins
            if (settings.menu_margin_top) {
                root.style.setProperty('--mas-menu-margin-top', settings.menu_margin_top + 'px');
            }
            if (settings.menu_margin_right) {
                root.style.setProperty('--mas-menu-margin-right', settings.menu_margin_right + 'px');
            }
            if (settings.menu_margin_bottom) {
                root.style.setProperty('--mas-menu-margin-bottom', settings.menu_margin_bottom + 'px');
            }
            if (settings.menu_margin_left) {
                root.style.setProperty('--mas-menu-margin-left', settings.menu_margin_left + 'px');
            }
            
            // Detached margins (floating style)
            if (settings.menu_detached_margin_top) {
                root.style.setProperty('--mas-menu-detached-margin-top', settings.menu_detached_margin_top + 'px');
            }
            if (settings.menu_detached_margin_right) {
                root.style.setProperty('--mas-menu-detached-margin-right', settings.menu_detached_margin_right + 'px');
            }
            if (settings.menu_detached_margin_bottom) {
                root.style.setProperty('--mas-menu-detached-margin-bottom', settings.menu_detached_margin_bottom + 'px');
            }
            if (settings.menu_detached_margin_left) {
                root.style.setProperty('--mas-menu-detached-margin-left', settings.menu_detached_margin_left + 'px');
            }
        }
        
        // ========== BORDER RADIUS ==========
        // Obsługa różnych typów border radius
        if (settings.menu_border_radius_type === 'all') {
            const radius = settings.menu_border_radius_all || settings.menu_border_radius || 0;
            root.style.setProperty('--mas-menu-border-radius', radius + 'px');
        } else if (settings.menu_border_radius_type === 'individual') {
            // Individual border radius for each corner
            const radiusValue = settings.menu_border_radius_all || 8; // fallback value
            
            const tl = settings.menu_radius_tl ? radiusValue : 0;
            const tr = settings.menu_radius_tr ? radiusValue : 0;
            const br = settings.menu_radius_br ? radiusValue : 0;
            const bl = settings.menu_radius_bl ? radiusValue : 0;
            
            root.style.setProperty('--mas-menu-border-radius', `${tl}px ${tr}px ${br}px ${bl}px`);
        } else {
            // Fallback to basic border radius
            const radius = settings.menu_border_radius || 0;
            root.style.setProperty('--mas-menu-border-radius', radius + 'px');
        }
        
        // ========== FLOATING MENU MARGINS ==========
        if (this.isFloating) {
            if (settings.menu_margin_type === 'all') {
                const margin = settings.menu_margin || settings.menu_detached_margin_all || 10;
                root.style.setProperty('--mas-menu-floating-margin-top', margin + 'px');
                root.style.setProperty('--mas-menu-floating-margin-right', margin + 'px');
                root.style.setProperty('--mas-menu-floating-margin-bottom', margin + 'px');
                root.style.setProperty('--mas-menu-floating-margin-left', margin + 'px');
            } else if (settings.menu_margin_type === 'individual') {
                root.style.setProperty('--mas-menu-floating-margin-top', (settings.menu_margin_top || settings.menu_detached_margin_top || 10) + 'px');
                root.style.setProperty('--mas-menu-floating-margin-right', (settings.menu_margin_right || settings.menu_detached_margin_right || 10) + 'px');
                root.style.setProperty('--mas-menu-floating-margin-bottom', (settings.menu_margin_bottom || settings.menu_detached_margin_bottom || 10) + 'px');
                root.style.setProperty('--mas-menu-floating-margin-left', (settings.menu_margin_left || settings.menu_detached_margin_left || 10) + 'px');
            }
        }
        
        // ========== SUBMENU CUSTOMIZATIONS ==========
        const hasSubmenuCustomizations = (
            settings.submenu_background || 
            settings.submenu_text_color || 
            settings.submenu_hover_background ||
            settings.submenu_border_radius ||
            settings.submenu_width ||
            settings.submenu_padding
        );
        
        if (hasSubmenuCustomizations) {
            body.classList.add('mas-v2-submenu-custom-enabled');
            root.style.setProperty('--mas-submenu-enabled', '1');
            
            // Submenu colors with fallbacks
            const submenuBg = settings.submenu_background || settings.menu_background || 'rgba(0, 0, 0, 0.4)';
            const submenuText = settings.submenu_text_color || settings.menu_text_color || '#ccc';
            const submenuHoverBg = settings.submenu_hover_background || settings.menu_hover_background || 'rgba(255, 255, 255, 0.1)';
            const submenuHoverText = settings.submenu_hover_text_color || settings.menu_hover_text_color || '#00a0d2';
            const submenuActiveBg = settings.submenu_active_background || settings.menu_active_background || '#0073aa';
            const submenuActiveText = settings.submenu_active_text_color || settings.menu_active_text_color || 'white';
            
            root.style.setProperty('--mas-submenu-bg-color', submenuBg);
            root.style.setProperty('--mas-submenu-text-color', submenuText);
            root.style.setProperty('--mas-submenu-hover-bg', submenuHoverBg);
            root.style.setProperty('--mas-submenu-hover-text', submenuHoverText);
            root.style.setProperty('--mas-submenu-active-bg', submenuActiveBg);
            root.style.setProperty('--mas-submenu-active-text', submenuActiveText);
        
            // Submenu border radius - obsługa różnych typów
            if (settings.submenu_border_radius_type === 'all' && settings.submenu_border_radius_all !== undefined) {
                root.style.setProperty('--mas-submenu-border-radius', settings.submenu_border_radius_all + 'px');
            } else if (settings.submenu_border_radius_type === 'individual') {
                const tl = settings.submenu_border_radius_top_left || 8;
                const tr = settings.submenu_border_radius_top_right || 8;
                const br = settings.submenu_border_radius_bottom_right || 8;
                const bl = settings.submenu_border_radius_bottom_left || 8;
                root.style.setProperty('--mas-submenu-border-radius', `${tl}px ${tr}px ${br}px ${bl}px`);
            } else {
                // Fallback - dziedzicz z menu lub użyj domyślne
                const submenuRadius = settings.submenu_border_radius || 8;
                root.style.setProperty('--mas-submenu-border-radius', submenuRadius + 'px');
            }
            
            // Submenu dimensions - obsługa różnych typów szerokości
            if (settings.submenu_width_type === 'fixed' && settings.submenu_width_value) {
                root.style.setProperty('--mas-submenu-min-width', settings.submenu_width_value + 'px');
            } else if (settings.submenu_width_type === 'min-max') {
                if (settings.submenu_min_width) {
                    root.style.setProperty('--mas-submenu-min-width', settings.submenu_min_width + 'px');
                }
                if (settings.submenu_max_width) {
                    root.style.setProperty('--mas-submenu-max-width', settings.submenu_max_width + 'px');
                }
            } else {
                // Auto width - użyj domyślne
                const submenuMinWidth = settings.submenu_min_width || 200;
                root.style.setProperty('--mas-submenu-min-width', submenuMinWidth + 'px');
            }
            
            const submenuPadding = settings.submenu_padding || '8px';
            root.style.setProperty('--mas-submenu-padding', submenuPadding);
            
            // Submenu item padding
            const submenuItemPadding = settings.submenu_item_padding || '8px 16px';
            root.style.setProperty('--mas-submenu-item-padding', submenuItemPadding);
        }
        
        // ========== EFEKTY WIZUALNE ==========
        // Glossy effect dla menu
        if (this.isGlossy) {
            const glossyBg = settings.menu_glossy_bg || 'rgba(35, 40, 45, 0.8)';
            root.style.setProperty('--mas-menu-glossy-bg', glossyBg);
        }
        
        // Shadow effect
        if (settings.menu_shadow) {
            body.classList.add('mas-v2-menu-shadow-enabled');
            root.style.setProperty('--mas-menu-shadow-enabled', '1');
            
            const shadowColor = settings.shadow_color || '#000000';
            const shadowBlur = settings.shadow_blur || 10;
            root.style.setProperty('--mas-menu-shadow', `0 8px ${shadowBlur}px rgba(0, 0, 0, 0.3)`);
        } else {
            root.style.setProperty('--mas-menu-shadow', 'none');
            root.style.setProperty('--mas-submenu-shadow', '0 8px 32px rgba(0, 0, 0, 0.3)');
        }
        
        // ========== LEGACY COMPATIBILITY ==========
        // Obsługa starych nazw ustawień dla backward compatibility
        if (settings.menu_bg && !settings.menu_background) {
            root.style.setProperty('--mas-menu-bg-color', settings.menu_bg);
        }
        
        if (settings.menu_detached && !this.isFloating) {
            // Legacy detached mode
            this.isFloating = true;
            this.updateBodyClasses();
        }
        
        // ========== ANIMATIONS & TRANSITIONS ==========
        const animationSpeed = settings.animation_speed || 300;
        root.style.setProperty('--mas-menu-transition-duration', animationSpeed + 'ms');
        
        // Animation effects
        if (settings.enable_animations) {
            root.style.setProperty('--mas-menu-animation-enabled', '1');
        } else {
            root.style.setProperty('--mas-menu-animation-enabled', '0');
        }
        
        // ========== COMPACT MODE ==========
        if (settings.menu_compact_mode) {
            root.style.setProperty('--mas-menu-item-height', '28px');
            root.style.setProperty('--mas-menu-item-padding', '6px 10px');
        } else {
            root.style.setProperty('--mas-menu-item-padding', '8px 12px');
        }
        
        // ========== FONT SETTINGS ==========
        if (settings.font_size || settings.body_font_size) {
            const fontSize = settings.font_size || settings.body_font_size || 14;
            root.style.setProperty('--mas-menu-font-size', fontSize + 'px');
        }
        
        if (settings.font_family && settings.font_family !== 'system') {
            root.style.setProperty('--mas-menu-font-family', settings.font_family);
        } else if (settings.google_font_primary) {
            root.style.setProperty('--mas-menu-font-family', settings.google_font_primary);
        }
        
        console.log('🎯 MenuManager: CSS Variables updated successfully');
    }
    
    /**
     * NOWA METODA: Smart submenu behavior using CSS + minimal JS
     */
    initSubmenuBehavior() {
        const adminMenu = document.getElementById('adminmenu');
        if (!adminMenu) return;
        
        // CSS już obsługuje wszystkie hover behaviors!
        // JavaScript tylko dla keyboard navigation i edge cases
        
        const menuItems = adminMenu.querySelectorAll('li.menu-top');
        
        menuItems.forEach(item => {
            const submenu = item.querySelector('.wp-submenu');
            if (!submenu) return;
            
            // Keyboard navigation support
            const menuLink = item.querySelector('a');
            if (menuLink) {
                menuLink.addEventListener('focus', () => {
                    if (this.isFloating || document.body.classList.contains('folded')) {
                        item.classList.add('mas-keyboard-focus');
                    }
                });
                
                menuLink.addEventListener('blur', () => {
                    item.classList.remove('mas-keyboard-focus');
                });
            }
            
            // Dynamic submenu positioning for floating menu
            if (this.isFloating) {
                item.addEventListener('mouseenter', () => {
                    this.positionFloatingSubmenu(item, submenu);
                });
            }
        });
        
        // Escape key support
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.clearKeyboardFocus();
            }
        });
        
        console.log('🎯 MenuManager: Smart submenu behavior initialized');
    }
    
    /**
     * NOWA METODA: Dynamiczne pozycjonowanie submenu dla floating menu
     */
    positionFloatingSubmenu(menuItem, submenu) {
        if (!this.isFloating) return;
        
        const menuRect = menuItem.getBoundingClientRect();
        const viewportHeight = window.innerHeight;
        
        // Calculate optimal position
        let topOffset = 0;
        
        // If submenu would go below viewport, position it higher
        const submenuHeight = submenu.offsetHeight || 200; // estimate
        if (menuRect.bottom + submenuHeight > viewportHeight) {
            topOffset = viewportHeight - menuRect.bottom - submenuHeight - 20;
        }
        
        // Update CSS variable for this specific submenu
        submenu.style.setProperty('--mas-submenu-offset-y', topOffset + 'px');
    }
    
    clearKeyboardFocus() {
        const focusedItems = document.querySelectorAll('#adminmenu .mas-keyboard-focus');
        focusedItems.forEach(item => {
            item.classList.remove('mas-keyboard-focus');
        });
    }
    
    setupEventListeners() {
        // Menu toggle handling
        const menuToggle = document.querySelector('#collapse-menu');
        if (menuToggle) {
            menuToggle.addEventListener('click', () => {
                this.handleMenuToggle();
            });
        }
        
        // Window resize handling
        window.addEventListener('resize', () => {
            this.handleResize();
        });
        
        console.log('🎯 MenuManager: Event listeners setup');
    }
    
    handleMenuToggle() {
        // WordPress handles the actual toggle, we just need to update our state
        setTimeout(() => {
            const isCollapsed = document.body.classList.contains('folded');
            console.log('🎯 MenuManager: Menu toggled, collapsed:', isCollapsed);
            
            // Additional logic if needed for collapsed state
            if (isCollapsed && this.isFloating) {
                // Floating collapsed menu specific logic
                document.documentElement.style.setProperty('--mas-menu-width', '36px');
            } else if (this.isFloating) {
                // Restore floating menu width
                const originalWidth = this.settings.menu_width || 'auto';
                document.documentElement.style.setProperty('--mas-menu-width', originalWidth + 'px');
            }
        }, 100);
    }
    
    toggleFloating() {
        this.isFloating = !this.isFloating;
        this.updateBodyClasses();
        
        // Trigger reinitialization of submenu behavior
        this.initSubmenuBehavior();
        
        console.log('🎯 MenuManager: Floating toggled to:', this.isFloating);
    }
    
    handleResize() {
        if (window.innerWidth <= 782) {
            // Mobile responsive adjustments
            if (this.isFloating) {
                document.body.classList.add('mas-v2-mobile-override');
            }
        } else {
            document.body.classList.remove('mas-v2-mobile-override');
        }
    }
    
    /**
     * WordPress menu observer for dynamic changes
     */
    observeMenuChanges() {
        const adminMenu = document.getElementById('adminmenu');
        if (!adminMenu) return;
        
        this.resizeObserver = new ResizeObserver(() => {
            if (this.isFloating) {
                // Recalculate submenu positions if needed
                this.initSubmenuBehavior();
            }
        });
        
        this.resizeObserver.observe(adminMenu);
    }
    
    setFloating(floating) {
        this.isFloating = floating;
        this.updateBodyClasses();
    }
    
    updateSettings(newSettings) {
        this.settings = { ...this.settings, ...newSettings };
        this.applySettings(newSettings);
        this.updateBodyClasses();
        this.updateCSSVariables(newSettings);
    }
    
    getCurrentState() {
        return {
            isFloating: this.isFloating,
            isGlossy: this.isGlossy,
            settings: this.settings
        };
    }
    
    destroy() {
        if (this.resizeObserver) {
            this.resizeObserver.disconnect();
        }
        
        // Remove event listeners
        window.removeEventListener('resize', this.handleResize);
        document.removeEventListener('keydown', this.clearKeyboardFocus);
        
        console.log('🎯 MenuManager: Destroyed');
    }
    
    /**
     * NOWA METODA: Nasłuchuje zmian ustawień w czasie rzeczywistym
     */
    setupLiveSettingsUpdates() {
        // Event listener dla live preview
        document.addEventListener('mas-live-preview-update', (e) => {
            if (e.detail && e.detail.settings) {
                console.log('🎯 MenuManager: Otrzymano live update settings', e.detail.settings);
                this.updateSettings(e.detail.settings);
            }
        });
        
        // Event listener dla zapisanych ustawień
        document.addEventListener('mas-settings-saved', (e) => {
            if (e.detail && e.detail.settings) {
                console.log('🎯 MenuManager: Otrzymano zapisane settings', e.detail.settings);
                this.updateSettings(e.detail.settings);
            }
        });
        
        // Obsługa zmian w formularzach na stronie ustawień
        if (typeof jQuery !== 'undefined') {
            jQuery(document).on('change input', '.mas-v2-field[name^="menu_"]', (e) => {
                const fieldName = e.target.name;
                const fieldValue = this.getFieldValue(e.target);
                
                console.log(`🎯 MenuManager: Field ${fieldName} changed to:`, fieldValue);
                
                // Stwórz partial settings object
                const partialSettings = {};
                partialSettings[fieldName] = fieldValue;
                
                // Aktualizuj tylko ten fragment ustawień
                this.updatePartialSettings(partialSettings);
            });
        }
        
        console.log('🎯 MenuManager: Live settings updates configured');
    }
    
    /**
     * NOWA METODA: Pobiera wartość z pola formularza
     */
    getFieldValue(field) {
        const $field = typeof jQuery !== 'undefined' ? jQuery(field) : null;
        
        if (field.type === 'checkbox') {
            return field.checked;
        } else if (field.type === 'radio') {
            return field.checked ? field.value : null;
        } else if (field.type === 'number' || field.type === 'range') {
            return parseInt(field.value) || 0;
        } else {
            return field.value;
        }
    }
    
    /**
     * NOWA METODA: Aktualizuje częściowe ustawienia (tylko zmienione pola)
     */
    updatePartialSettings(partialSettings) {
        // Merge z istniejącymi ustawieniami
        this.settings = { ...this.settings, ...partialSettings };
        
        // Sprawdź czy zmiany dotyczą floating/glossy mode
        if (partialSettings.hasOwnProperty('menu_floating')) {
            this.isFloating = partialSettings.menu_floating === true || partialSettings.menu_floating === 'true';
            this.updateBodyClasses();
        }
        
        if (partialSettings.hasOwnProperty('menu_glossy')) {
            this.isGlossy = partialSettings.menu_glossy === true || partialSettings.menu_glossy === 'true';
            this.updateBodyClasses();
        }
        
        // Aktualizuj CSS Variables
        this.updateCSSVariables(this.settings);
        
        // Trigger custom event dla innych modułów
        document.dispatchEvent(new CustomEvent('mas-menu-settings-updated', {
            detail: { 
                partialSettings: partialSettings,
                fullSettings: this.settings
            }
        }));
    }
}

// Export for module system
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MenuManager;
} else if (typeof window !== 'undefined') {
    window.MenuManager = MenuManager;
} 