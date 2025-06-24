/**
 * Modern Admin Styler V2 - Module Loader (FIXED)
 * Ładuje TYLKO moduły - NIE nadpisuje ich!
 */

(function() {
    'use strict';
    
    // NAPRAWIONO: Tylko moduły, bez main scripts!
    const moduleConfig = {
        // Core modules (zawsze ładowane)
        core: [
            'modules/ThemeManager.js',
            'modules/BodyClassManager.js',
            'modules/MenuManagerFixed.js', // NOWY MENU MANAGER!
            'modules/NotificationManager.js', // Dodano NotificationManager
            'modules/PaletteManager.js',
            'modules/ModernAdminApp.js'
        ],
        // Settings page modules (tylko na stronie ustawień)
        settings: [
            'modules/LivePreviewManager.js',
            'modules/SettingsManager.js'
        ]
        // USUNIĘTO: main scripts - będą załadowane przez WP!
    };
    
    // Wykryj typ strony
    const pageType = detectPageType();
    
    // Załaduj TYLKO moduły
    loadModules(pageType).then(() => {
        console.log('✅ Moduły Modern Admin Styler V2 załadowane');
        console.log('🔄 Oczekuję na admin-global.js/admin-modern.js...');
    }).catch(error => {
        console.error('❌ Błąd ładowania modułów:', error);
    });
    
    function detectPageType() {
        const url = window.location.href;
        const isSettingsPage = url.includes('page=modern-admin-styler') || 
                              url.includes('mas-v2-settings') ||
                              document.querySelector('#mas-v2-settings-form') !== null;
        
        return {
            isSettings: isSettingsPage,
            isGlobal: true
        };
    }
    
    async function loadModules(pageType) {
        const basePath = getBasePath();
        const modulesToLoad = [];
        
        // Core modules (zawsze)
        modulesToLoad.push(...moduleConfig.core.map(path => basePath + path));
        
        // Settings modules (tylko na stronie ustawień)
        if (pageType.isSettings) {
            modulesToLoad.push(...moduleConfig.settings.map(path => basePath + path));
        }
        
        // Załaduj moduły sekwencyjnie
        for (const modulePath of modulesToLoad) {
            try {
                await loadScript(modulePath);
                console.log(`📦 Moduł załadowany: ${modulePath.split('/').pop()}`);
            } catch (error) {
                console.warn(`⚠️ Nie udało się załadować moduł: ${modulePath}`, error);
            }
        }
        
        // USUNIĘTO: Ładowanie main scripts
        // admin-global.js i admin-modern.js będą załadowane przez WordPress
        // i połączą się z już załadowanymi modułami
    }
    
    function getBasePath() {
        // Próbuj znaleźć base path na podstawie aktualnie ładowanych skryptów
        const scripts = document.querySelectorAll('script[src*="mas-loader"]');
        if (scripts.length > 0) {
            const loaderSrc = scripts[0].src;
            return loaderSrc.substring(0, loaderSrc.lastIndexOf('/') + 1);
        }
        
        // Fallback - próbuj na podstawie innych skryptów wtyczki
        const masScripts = document.querySelectorAll('script[src*="modern-admin-styler"]');
        if (masScripts.length > 0) {
            const src = masScripts[0].src;
            const assetsIndex = src.indexOf('/assets/js/');
            if (assetsIndex !== -1) {
                return src.substring(0, assetsIndex + '/assets/js/'.length);
            }
        }
        
        // Ostatni fallback
        console.warn('⚠️ Nie można określić base path, używam relatywnej ścieżki');
        return './';
    }
    
    function loadScript(src) {
        return new Promise((resolve, reject) => {
            // Sprawdź czy skrypt już został załadowany
            const existingScript = document.querySelector(`script[src="${src}"]`);
            if (existingScript) {
                resolve();
                return;
            }
            
            const script = document.createElement('script');
            script.src = src;
            script.async = false; // Zachowaj kolejność
            script.onload = resolve;
            script.onerror = () => reject(new Error(`Failed to load ${src}`));
            
            document.head.appendChild(script);
        });
    }
    
    // Expose loader API dla debugowania
    window.MASLoader = {
        moduleConfig,
        detectPageType,
        loadModules,
        getBasePath,
        version: '2.0.0-fixed'
    };
    
})(); 