/**
 * Modern Admin Styler V2 - Main Application Orchestrator
 * Główny koordynator wszystkich modułów aplikacji
 */

class ModernAdminApp {
    constructor() {
        this.modules = new Map();
        this.settings = {};
        this.isInitialized = false;
        this.initPromise = null;
    }
    
    async init(initialSettings = {}) {
        if (this.initPromise) {
            return this.initPromise;
        }
        
        this.initPromise = this._initializeApp(initialSettings);
        return this.initPromise;
    }
    
    async _initializeApp(initialSettings) {
        try {
            console.log('🚀 Inicjalizacja Modern Admin Styler V2...');
            
            this.settings = initialSettings;
            this.setupGlobalEventListeners();
            
            // Inicjalizuj moduły w odpowiedniej kolejności
            await this.initializeModules();
            
            // Ustaw początkowy stan
            this.applyInitialSettings();
            
            this.isInitialized = true;
            this.dispatchAppEvent('initialized');
            
            console.log('✅ Modern Admin Styler V2 zainicjalizowany pomyślnie');
            
        } catch (error) {
            console.error('❌ Błąd podczas inicjalizacji Modern Admin Styler V2:', error);
            this.dispatchAppEvent('init-error', { error });
            throw error;
        }
    }
    
    async initializeModules() {
        const moduleConfigs = [
            {
                name: 'themeManager',
                class: ThemeManager,
                priority: 1,
                global: true // Ładowane na wszystkich stronach
            },
            {
                name: 'bodyClassManager',
                class: BodyClassManager,
                priority: 2,
                global: true
            },
            {
                name: 'menuManager',
                class: MenuManager,
                priority: 3,
                global: true
            },
            {
                name: 'notificationManager',
                class: NotificationManager,
                priority: 3.5,
                global: true
            },
            {
                name: 'livePreviewManager',
                class: LivePreviewManager,
                priority: 4,
                global: false // Tylko na stronie ustawień
            },
            {
                name: 'settingsManager',
                class: SettingsManager,
                priority: 5,
                global: false // Tylko na stronie ustawień
            },
            {
                name: 'paletteManager',
                class: PaletteManager,
                priority: 2.5,
                global: true // Dostępne globalnie
            }
        ];
        
        // Sortuj według priorytetu
        moduleConfigs.sort((a, b) => a.priority - b.priority);
        
        // Sprawdź czy jesteśmy na stronie ustawień
        const isSettingsPage = this.isSettingsPage();
        
        for (const config of moduleConfigs) {
            try {
                // Sprawdź czy moduł powinien być ładowany
                if (!config.global && !isSettingsPage) {
                    continue;
                }
                
                // Sprawdź czy klasa jest dostępna
                if (typeof config.class !== 'function') {
                    console.warn(`Klasa ${config.class.name} nie jest dostępna`);
                    continue;
                }
                
                console.log(`📦 Inicjalizacja modułu: ${config.name}`);
                
                const moduleInstance = new config.class(this);
                
                // Moduły globalne używają ustawień, moduły lokalne mogą mieć własną logikę
                if (config.global) {
                    moduleInstance.init(this.settings);
                } else {
                    moduleInstance.init();
                }
                
                this.modules.set(config.name, moduleInstance);
                
                console.log(`✅ Moduł ${config.name} zainicjalizowany`);
                
            } catch (error) {
                console.error(`❌ Błąd podczas inicjalizacji modułu ${config.name}:`, error);
                this.dispatchAppEvent('module-error', { 
                    module: config.name, 
                    error 
                });
            }
        }
    }
    
    isSettingsPage() {
        // Sprawdź czy jesteśmy na stronie ustawień wtyczki
        const url = window.location.href;
        return url.includes('page=modern-admin-styler') || 
               url.includes('mas-v2-settings') ||
               document.querySelector('#mas-v2-settings-form') !== null;
    }
    
    setupGlobalEventListeners() {
        // Nasłuchuj zmian ustawień i propaguj do modułów
        document.addEventListener('mas-settings-changed', (e) => {
            this.settings = { ...this.settings, ...e.detail.settings };
            this.propagateSettingsToModules();
        });
        
        // Nasłuchuj błędów modułów
        document.addEventListener('mas-module-error', (e) => {
            console.error('Błąd modułu:', e.detail);
        });
        
        // Cleanup przy unload
        window.addEventListener('beforeunload', () => {
            this.destroy();
        });
    }
    
    applyInitialSettings() {
        // Zastosuj ustawienia początkowe do wszystkich modułów
        this.propagateSettingsToModules();
        
        // Wyślij event o załadowaniu ustawień
        this.dispatchAppEvent('settings-applied', this.settings);
    }
    
    propagateSettingsToModules() {
        this.modules.forEach((module, name) => {
            try {
                if (typeof module.updateSettings === 'function') {
                    module.updateSettings(this.settings);
                } else if (typeof module.applySettings === 'function') {
                    module.applySettings(this.settings);
                }
            } catch (error) {
                console.error(`Błąd podczas aktualizacji ustawień modułu ${name}:`, error);
            }
        });
    }
    
    // Publiczne API dla modułów
    getModule(name) {
        return this.modules.get(name);
    }
    
    hasModule(name) {
        return this.modules.has(name);
    }
    
    getAllModules() {
        return new Map(this.modules);
    }
    
    getSettings() {
        return { ...this.settings };
    }
    
    updateSettings(newSettings) {
        this.settings = { ...this.settings, ...newSettings };
        this.propagateSettingsToModules();
        this.dispatchAppEvent('settings-updated', this.settings);
    }
    
    // API dla zewnętrznych skryptów
    enableFloatingMenu() {
        const bodyManager = this.getModule('bodyClassManager');
        const menuManager = this.getModule('menuManager');
        
        if (bodyManager) {
            bodyManager.enableFloatingMenu();
        }
        
        if (menuManager) {
            menuManager.setFloating(true);
        }
        
        this.updateSettings({ menu_detached: true });
    }
    
    disableFloatingMenu() {
        const bodyManager = this.getModule('bodyClassManager');
        const menuManager = this.getModule('menuManager');
        
        if (bodyManager) {
            bodyManager.disableFloatingMenu();
        }
        
        if (menuManager) {
            menuManager.setFloating(false);
        }
        
        this.updateSettings({ menu_detached: false });
    }
    
    toggleTheme() {
        const themeManager = this.getModule('themeManager');
        if (themeManager) {
            themeManager.toggleTheme();
        }
    }
    
    enableLivePreview() {
        const livePreview = this.getModule('livePreviewManager');
        if (livePreview) {
            livePreview.enable();
        }
    }
    
    disableLivePreview() {
        const livePreview = this.getModule('livePreviewManager');
        if (livePreview) {
            livePreview.disable();
        }
    }
    
    saveSettings() {
        const settingsManager = this.getModule('settingsManager');
        if (settingsManager) {
            return settingsManager.saveSettings();
        }
        return Promise.reject(new Error('SettingsManager nie jest dostępny'));
    }
    
    exportSettings() {
        const settingsManager = this.getModule('settingsManager');
        if (settingsManager) {
            return settingsManager.exportSettings();
        }
        throw new Error('SettingsManager nie jest dostępny');
    }
    
    // Debugging i diagnostyka
    getSystemInfo() {
        const info = {
            isInitialized: this.isInitialized,
            isSettingsPage: this.isSettingsPage(),
            loadedModules: Array.from(this.modules.keys()),
            settings: this.settings,
            modules: {}
        };
        
        this.modules.forEach((module, name) => {
            try {
                if (typeof module.getCurrentState === 'function') {
                    info.modules[name] = module.getCurrentState();
                } else {
                    info.modules[name] = { status: 'active' };
                }
            } catch (error) {
                info.modules[name] = { status: 'error', error: error.message };
            }
        });
        
        return info;
    }
    
    logSystemInfo() {
        console.group('🔍 Modern Admin Styler V2 - System Info');
        console.log(this.getSystemInfo());
        console.groupEnd();
    }
    
    // Event system
    dispatchAppEvent(eventType, data = null) {
        const event = new CustomEvent(`mas-app-${eventType}`, {
            detail: {
                app: this,
                eventType,
                data,
                timestamp: Date.now()
            }
        });
        document.dispatchEvent(event);
    }
    
    // Cleanup
    destroy() {
        console.log('🧹 Czyszczenie Modern Admin Styler V2...');
        
        this.modules.forEach((module, name) => {
            try {
                if (typeof module.destroy === 'function') {
                    module.destroy();
                    console.log(`✅ Moduł ${name} wyczyszczony`);
                }
            } catch (error) {
                console.error(`❌ Błąd podczas czyszczenia modułu ${name}:`, error);
            }
        });
        
        this.modules.clear();
        this.isInitialized = false;
        this.initPromise = null;
        
        this.dispatchAppEvent('destroyed');
    }
    
    // Singleton pattern dla globalnego dostępu
    static getInstance() {
        if (!ModernAdminApp.instance) {
            ModernAdminApp.instance = new ModernAdminApp();
        }
        return ModernAdminApp.instance;
    }
}

// Globalna instancja
window.ModernAdminApp = ModernAdminApp;

// Auto-inicjalizacja jeśli mamy dane
if (typeof masV2Global !== 'undefined' && masV2Global.settings) {
    document.addEventListener('DOMContentLoaded', () => {
        const app = ModernAdminApp.getInstance();
        app.init(masV2Global.settings).catch(error => {
            console.error('Błąd auto-inicjalizacji:', error);
        });
    });
}

// Eksport dla użycia w innych modułach
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ModernAdminApp;
} 