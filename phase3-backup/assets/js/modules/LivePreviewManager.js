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
            return Promise.resolve();
        }
        
        try {
            this.setupEventListeners();
            this.createToggleButton();
            
            // Get notification manager with fallback
            if (this.app && typeof this.app.getModule === 'function') {
                this.notificationManager = this.app.getModule('notificationManager');
            }
            
            this.restoreState();
            this.isInitialized = true;
            
            console.log('🎨 MAS Live Preview Manager: Initialized successfully');
            
            // Dispatch initialization event
            document.dispatchEvent(new CustomEvent('mas-live-preview-initialized'));
            
            return Promise.resolve();
        } catch (error) {
            console.error('MAS Live Preview Manager: Initialization failed:', error);
            return Promise.reject(error);
        }
    }
    
    initCSSVariables() {
        // Mapowanie pól formularza na CSS custom properties
        
        // Menu margins (floating mode)
        this.cssVariables.set('menu_margin_top', '--mas-menu-floating-margin-top');
        this.cssVariables.set('menu_margin_right', '--mas-menu-floating-margin-right');
        this.cssVariables.set('menu_margin_bottom', '--mas-menu-floating-margin-bottom');
        this.cssVariables.set('menu_margin_left', '--mas-menu-floating-margin-left');
        this.cssVariables.set('menu_margin', '--mas-menu-floating-margin-all');
        
        // Admin bar margins
        this.cssVariables.set('admin_bar_margin_top', '--mas-admin-bar-margin-top');
        this.cssVariables.set('admin_bar_margin_right', '--mas-admin-bar-margin-right');
        this.cssVariables.set('admin_bar_margin_bottom', '--mas-admin-bar-margin-bottom');
        this.cssVariables.set('admin_bar_margin_left', '--mas-admin-bar-margin-left');
        this.cssVariables.set('admin_bar_margin_all', '--mas-admin-bar-margin-all');
        
        // Menu border radius
        this.cssVariables.set('menu_border_radius_all', '--mas-menu-border-radius');
        this.cssVariables.set('corner_radius_all', '--mas-corner-radius-all');
        this.cssVariables.set('corner_radius_top_left', '--mas-corner-radius-top-left');
        this.cssVariables.set('corner_radius_top_right', '--mas-corner-radius-top-right');
        this.cssVariables.set('corner_radius_bottom_left', '--mas-corner-radius-bottom-left');
        this.cssVariables.set('corner_radius_bottom_right', '--mas-corner-radius-bottom-right');
        
        // Admin bar border radius
        this.cssVariables.set('admin_bar_corner_radius_all', '--mas-admin-bar-corner-radius-all');
        this.cssVariables.set('admin_bar_corner_radius_top_left', '--mas-admin-bar-corner-radius-top-left');
        this.cssVariables.set('admin_bar_corner_radius_top_right', '--mas-admin-bar-corner-radius-top-right');
        this.cssVariables.set('admin_bar_corner_radius_bottom_left', '--mas-admin-bar-corner-radius-bottom-left');
        this.cssVariables.set('admin_bar_corner_radius_bottom_right', '--mas-admin-bar-corner-radius-bottom-right');
        
        // Menu colors - primary mapping
        this.cssVariables.set('menu_background', '--mas-menu-bg-color');
        this.cssVariables.set('menu_bg', '--mas-menu-bg-color'); // Alternative name
        this.cssVariables.set('menu_background_color', '--mas-menu-bg-color'); // Alternative name
        this.cssVariables.set('menu_text_color', '--mas-menu-text-color');
        this.cssVariables.set('menu_hover_background', '--mas-menu-hover-color');
        this.cssVariables.set('menu_hover_color', '--mas-menu-hover-color'); // Alternative name
        this.cssVariables.set('menu_hover_text_color', '--mas-menu-hover-text-color');
        this.cssVariables.set('menu_active_background', '--mas-menu-active-bg');
        this.cssVariables.set('menu_active_text_color', '--mas-menu-active-text-color');
        
        // Menu dimensions
        this.cssVariables.set('menu_width', '--mas-menu-width');
        this.cssVariables.set('menu_item_height', '--mas-menu-item-height');
        
        // Admin bar colors
        this.cssVariables.set('admin_bar_background_color', '--mas-admin-bar-bg-color');
        this.cssVariables.set('admin_bar_text_color', '--mas-admin-bar-text-color');
        
        // Submenu colors
        this.cssVariables.set('submenu_background', '--mas-submenu-bg-color');
        this.cssVariables.set('submenu_text_color', '--mas-submenu-text-color');
        this.cssVariables.set('submenu_hover_background', '--mas-submenu-hover-bg');
        this.cssVariables.set('submenu_hover_text_color', '--mas-submenu-hover-text-color');
        this.cssVariables.set('submenu_active_background', '--mas-submenu-active-bg');
        this.cssVariables.set('submenu_active_text_color', '--mas-submenu-active-text-color');
        
        // Submenu dimensions
        this.cssVariables.set('submenu_width_value', '--mas-submenu-min-width');
        this.cssVariables.set('submenu_min_width', '--mas-submenu-min-width');
        this.cssVariables.set('submenu_max_width', '--mas-submenu-max-width');
        this.cssVariables.set('submenu_border_radius_all', '--mas-submenu-border-radius');
        
        // Effects
        this.cssVariables.set('animation_speed', '--mas-menu-transition-duration');
        this.cssVariables.set('enable_animations', '--mas-menu-animation-enabled');
        this.cssVariables.set('menu_glassmorphism', '--mas-menu-glassmorphism-enabled');
        this.cssVariables.set('menu_shadow', '--mas-menu-shadow-enabled');
        
        // Gradient backgrounds (if used)
        this.cssVariables.set('menu_gradient_start', '--mas-menu-gradient-start');
        this.cssVariables.set('menu_gradient_end', '--mas-menu-gradient-end');
        this.cssVariables.set('admin_bar_gradient_start', '--mas-admin-bar-gradient-start');
        this.cssVariables.set('admin_bar_gradient_end', '--mas-admin-bar-gradient-end');
        
        console.log(`🎨 MAS Live Preview: Initialized ${this.cssVariables.size} CSS variable mappings`);
    }
    
    setupEventListeners() {
        // Słuchaj zmian w formularzach
        document.addEventListener('change', (e) => this.handleFormChange(e));
        document.addEventListener('input', (e) => this.handleFormChange(e));
        document.addEventListener('keyup', (e) => this.handleFormChange(e));
        
        // Color picker events - WordPress color picker
        document.addEventListener('wpColorPickerChange', (e) => this.handleColorChange(e));
        
        // jQuery color picker events (fallback)
        if (typeof jQuery !== 'undefined') {
            jQuery(document).on('change', '.wp-color-picker', (e) => {
                this.handleColorChange(e);
            });
        }
        
        // Checkbox i radio changes
        document.addEventListener('change', (e) => {
            if (e.target.type === 'checkbox' || e.target.type === 'radio') {
                this.handleFormChange(e);
            }
        });
        
        // Listen for AJAX live preview responses
        document.addEventListener('mas-ajax-live-preview-response', (e) => {
            this.handleAjaxPreviewResponse(e.detail);
        });
    }
    
    handleFormChange(e) {
        if (!this.isEnabled) return;
        
        const input = e.target;
        const form = input.closest('#mas-v2-settings-form');
        if (!form) return;
        
        const fieldName = input.name;
        const fieldType = input.type;
        
        // Skip if no field name
        if (!fieldName) return;
        
        // Throttle dla różnych typów pól
        const delay = this.getThrottleDelay(fieldType);
        
        this.throttledUpdate(fieldName, () => {
            // Update CSS variable immediately for instant feedback
            this.updateCSSVariable(fieldName, input);
            
            // Also send AJAX request for server-side CSS generation
            this.sendAjaxPreviewRequest(fieldName, input);
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
        // Jeśli to checkbox i jest odznaczony, usuń CSS variable
        if (input.type === 'checkbox' && !value) {
            this.root.style.removeProperty(this.cssVariables.get(fieldName));
            return null;
        }
        
        // Jeśli to radio i nie jest zaznaczony, nie zmieniaj CSS
        if (input.type === 'radio' && !value) {
            return null;
        }
        
        // Handle special boolean fields
        if (input.type === 'checkbox' && value) {
            // For boolean CSS variables, set to 1 or specific value
            if (fieldName === 'menu_floating') {
                return '1';
            }
            if (fieldName === 'menu_glassmorphism') {
                return '1';
            }
            if (fieldName === 'menu_shadow') {
                return '1';
            }
            return '1';
        }
        
        // Dodaj jednostki dla wartości numerycznych
        if (typeof value === 'number' && this.needsPixelUnit(fieldName)) {
            return value + 'px';
        }
        
        // Handle range inputs
        if (input.type === 'range') {
            const numValue = parseInt(value, 10);
            if (this.needsPixelUnit(fieldName)) {
                return numValue + 'px';
            }
            // Special handling for animation speed (convert to milliseconds)
            if (fieldName === 'animation_speed') {
                return numValue + 'ms';
            }
            return numValue.toString();
        }
        
        // Handle animation speed specifically
        if (fieldName === 'animation_speed' && typeof value === 'number') {
            return value + 'ms';
        }
        
        // Dla kolorów - upewnij się że mają # i są prawidłowe
        if (input.type === 'color' || fieldName.includes('color') || fieldName.includes('background')) {
            if (typeof value === 'string' && value.length > 0) {
                if (!value.startsWith('#') && value.match(/^[0-9a-fA-F]{6}$/)) {
                    return '#' + value;
                }
                if (value.startsWith('#') || value.startsWith('rgb') || value.startsWith('hsl')) {
                    return value;
                }
            }
        }
        
        return value;
    }
    
    needsPixelUnit(fieldName) {
        const pixelFields = [
            // Menu margins
            'menu_margin_top', 'menu_margin_right', 'menu_margin_bottom', 'menu_margin_left', 'menu_margin',
            // Admin bar margins
            'admin_bar_margin_top', 'admin_bar_margin_right', 'admin_bar_margin_bottom', 'admin_bar_margin_left', 'admin_bar_margin_all',
            // Border radius
            'corner_radius_all', 'corner_radius_top_left', 'corner_radius_top_right', 'corner_radius_bottom_left', 'corner_radius_bottom_right',
            'admin_bar_corner_radius_all', 'admin_bar_corner_radius_top_left', 'admin_bar_corner_radius_top_right', 
            'admin_bar_corner_radius_bottom_left', 'admin_bar_corner_radius_bottom_right',
            'menu_border_radius_all', 'menu_border_radius', 'submenu_border_radius_all',
            // Dimensions
            'menu_width', 'menu_item_height',
            'submenu_width_value', 'submenu_min_width', 'submenu_max_width'
        ];
        
        return pixelFields.includes(fieldName);
    }
    
    enable() {
        this.isEnabled = true;
        localStorage.setItem('mas-v2-live-preview', 'enabled');
        this.updateToggleButton();
        
        // Show notification
        if (this.notificationManager) {
            this.notificationManager.show('Live Preview włączony', 'success', 2000, 'top-right');
        } else {
            console.log('🎨 MAS Live Preview: Enabled');
        }
        
        // Natychmiast zastosuj wszystkie wartości z formularza, jeśli formularz jest dostępny
        const form = document.querySelector('#mas-v2-settings-form');
        if (form) {
            // Small delay to ensure DOM is ready
            setTimeout(() => {
                this.applyAllFormValues();
            }, 100);
        }
        
        // Dispatch enable event
        document.dispatchEvent(new CustomEvent('mas-live-preview-enabled'));
    }
    
    disable() {
        this.isEnabled = false;
        localStorage.setItem('mas-v2-live-preview', 'disabled');
        this.updateToggleButton();
        
        // Clear all applied styles
        this.clearAllCSSVariables();
        this.clearAjaxStyles();
        
        // Clear any pending timeouts
        this.timeouts.forEach(timeoutId => clearTimeout(timeoutId));
        this.timeouts.clear();
        
        // Show notification
        if (this.notificationManager) {
            this.notificationManager.show('Live Preview wyłączony', 'info', 2000, 'top-right');
        } else {
            console.log('🎨 MAS Live Preview: Disabled');
        }
        
        // Dispatch disable event
        document.dispatchEvent(new CustomEvent('mas-live-preview-disabled'));
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
        if (!form) {
            console.warn('MAS Live Preview: Settings form not found');
            return;
        }
        
        const inputs = form.querySelectorAll('input, select, textarea');
        let appliedCount = 0;
        
        inputs.forEach(input => {
            if (input.name && this.cssVariables.has(input.name)) {
                try {
                    this.updateCSSVariable(input.name, input);
                    appliedCount++;
                } catch (error) {
                    console.warn(`MAS Live Preview: Error applying ${input.name}:`, error);
                }
            }
        });
        
        console.log(`🎨 MAS Live Preview: Applied ${appliedCount} CSS variables`);
        
        // Also trigger a full AJAX preview for complex styles
        this.sendFullAjaxPreview();
    }
    
    sendFullAjaxPreview() {
        const form = document.querySelector('#mas-v2-settings-form');
        if (!form || !window.masV2Global?.ajaxUrl) return;
        
        const formData = new FormData(form);
        formData.append('action', 'mas_v2_live_preview');
        formData.append('nonce', window.masV2Global.nonce || '');
        
        if (typeof jQuery !== 'undefined') {
            jQuery.post(window.masV2Global.ajaxUrl, Object.fromEntries(formData))
                .done((response) => {
                    if (response.success && response.data.css) {
                        this.handleAjaxPreviewResponse(response.data);
                    }
                })
                .fail((xhr, status, error) => {
                    console.warn('MAS Live Preview: Full AJAX preview failed:', error);
                });
        }
    }
    
    clearAllCSSVariables() {
        this.cssVariables.forEach((cssVar) => {
            this.root.style.removeProperty(cssVar);
        });
    }
    
    clearAjaxStyles() {
        const styleElement = document.getElementById('mas-live-preview-styles');
        if (styleElement) {
            styleElement.remove();
        }
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
    
    sendAjaxPreviewRequest(fieldName, input) {
        // Don't send AJAX for every single field change - only for complex settings
        const ajaxFields = [
            'menu_background', 'menu_text_color', 'menu_hover_background', 'menu_hover_text_color',
            'menu_active_background', 'menu_active_text_color', 'admin_bar_background_color', 
            'admin_bar_text_color', 'menu_floating', 'menu_glassmorphism', 'menu_shadow'
        ];
        
        if (!ajaxFields.includes(fieldName)) {
            return; // Only update CSS variables for simple fields
        }
        
        // Get all form data for comprehensive preview
        const form = document.querySelector('#mas-v2-settings-form');
        if (!form) return;
        
        const formData = new FormData(form);
        formData.append('action', 'mas_v2_live_preview');
        formData.append('nonce', window.masV2Global?.nonce || '');
        
        // Send AJAX request
        if (typeof jQuery !== 'undefined' && window.masV2Global?.ajaxUrl) {
            jQuery.post(window.masV2Global.ajaxUrl, Object.fromEntries(formData))
                .done((response) => {
                    if (response.success && response.data.css) {
                        this.handleAjaxPreviewResponse(response.data);
                    }
                })
                .fail((xhr, status, error) => {
                    console.warn('MAS Live Preview AJAX failed:', error);
                });
        }
    }
    
    handleAjaxPreviewResponse(data) {
        if (!data.css) return;
        
        // Update or create dynamic style element
        let styleElement = document.getElementById('mas-live-preview-styles');
        if (!styleElement) {
            styleElement = document.createElement('style');
            styleElement.id = 'mas-live-preview-styles';
            document.head.appendChild(styleElement);
        }
        
        // Update CSS content
        styleElement.textContent = data.css;
        
        // Dispatch event for other modules
        const event = new CustomEvent('mas-ajax-live-preview-updated', {
            detail: { css: data.css, timestamp: Date.now() }
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