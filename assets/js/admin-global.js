/**
 * Modern Admin Styler V2 - Global Admin Script (CLEANED)
 * Tylko bootstrap i fallback - ZERO duplikacji!
 */

(function() {
    'use strict';
    
    // Sprawdź dostępność modularnej architektury
    const hasModernApp = typeof window.ModernAdminApp !== 'undefined';
    
    // Inicjalizacja aplikacji - czekaj na załadowanie modułów
    document.addEventListener('mas-modules-ready', function(event) {
        console.log('📦 Modules ready event received:', event.detail);
        initializeApp();
    });
    
    // Fallback: jeśli event nie przyjdzie, spróbuj po DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function() {
        // Czekaj chwilę na event mas-modules-ready
        setTimeout(() => {
            if (typeof window.ModernAdminApp !== 'undefined' && !window.MASAppInitialized) {
                console.log('📦 Fallback initialization after DOMContentLoaded');
                initializeApp();
            }
        }, 500);
    });
    
    function initializeApp() {
        // Sprawdź czy mamy dostęp do głównego orchestratora
        if (typeof window.ModernAdminApp !== 'undefined') {
            initializeWithOrchestrator();
        } else {
            console.warn('⚠️ ModernAdminApp nie dostępne, czekam na modules...');
            // Czekaj na załadowanie modułów przez mas-loader.js
            waitForModules();
        }
    }
    
    function initializeWithOrchestrator() {
        // Prevent double initialization
        if (window.MASAppInitialized) {
            console.log('⚠️ App already initialized, skipping');
            return;
        }
        
        try {
            const app = window.ModernAdminApp.getInstance();
            
            // Check if already initialized
            if (app.isInitialized) {
                console.log('⚠️ ModernAdminApp already initialized');
                window.MASAppInitialized = true;
                return;
            }
            
            if (typeof masV2Global !== 'undefined' && masV2Global.settings) {
                window.MASAppInitialized = true;
                app.init(masV2Global.settings).then(() => {
                    console.log('✅ Modern Admin Styler V2 załadowany przez orchestrator');
                }).catch(error => {
                    console.error('❌ Błąd inicjalizacji przez orchestrator:', error);
                    window.MASAppInitialized = false;
                    // KRYTYCZNY: Nie ma już fallback do legacy!
                });
            } else {
                console.warn('⚠️ Brak ustawień masV2Global');
            }
        } catch (error) {
            console.error('❌ Błąd orchestratora:', error);
            window.MASAppInitialized = false;
        }
    }
    
    function waitForModules() {
        // Czekaj maksymalnie 5 sekund na załadowanie ModernAdminApp
        let attempts = 0;
        const maxAttempts = 50;
        
        const checkInterval = setInterval(() => {
            attempts++;
            
            if (typeof window.ModernAdminApp !== 'undefined') {
                clearInterval(checkInterval);
                console.log('✅ ModernAdminApp załadowane, inicjalizuję...');
                initializeWithOrchestrator();
            } else if (attempts >= maxAttempts) {
                clearInterval(checkInterval);
                console.error('❌ Timeout: ModernAdminApp nie załadowane w 5 sekund');
                // Brak legacy fallback - moduły MUSZĄ działać!
            }
        }, 100);
    }
    
    // Emergency fallback functionality when modules fail
    function setupEmergencyFallbacks() {
        // Ensure body classes are applied for basic functionality
        if (typeof masV2Global !== 'undefined' && masV2Global.settings) {
            const settings = masV2Global.settings;
            const body = document.body;
            
            // Add basic menu customization class
            if (settings.menu_background || settings.menu_text_color || 
                settings.menu_hover_background || settings.menu_detached || 
                settings.menu_floating) {
                body.classList.add('mas-v2-menu-custom-enabled');
            }
            
            // Add floating menu class
            if (settings.menu_detached || settings.menu_floating) {
                body.classList.add('mas-v2-menu-floating');
            }
            
            // Basic CSS variable injection
            const root = document.documentElement;
            if (settings.menu_background) {
                root.style.setProperty('--mas-menu-bg-color', settings.menu_background);
            }
            if (settings.menu_text_color) {
                root.style.setProperty('--mas-menu-text-color', settings.menu_text_color);
            }
            if (settings.submenu_background) {
                root.style.setProperty('--mas-submenu-bg-color', settings.submenu_background);
            }
            
            console.log('🚨 Emergency fallbacks applied');
        }
        
        // Emergency submenu visibility fix
        setupEmergencySubmenuFix();
    }
    
    function setupEmergencySubmenuFix() {
        // Ensure submenus are visible on hover in floating mode
        const adminMenu = document.getElementById('adminmenu');
        if (!adminMenu) return;
        
        // Add hover event listeners for submenu visibility
        const menuItems = adminMenu.querySelectorAll('li.menu-top');
        menuItems.forEach(item => {
            const submenu = item.querySelector('.wp-submenu');
            if (!submenu) return;
            
            item.addEventListener('mouseenter', () => {
                if (document.body.classList.contains('mas-v2-menu-floating')) {
                    submenu.style.display = 'block';
                    submenu.style.visibility = 'visible';
                    submenu.style.opacity = '1';
                }
            });
            
            item.addEventListener('mouseleave', () => {
                if (document.body.classList.contains('mas-v2-menu-floating')) {
                    // Only hide if not current/active
                    if (!item.classList.contains('wp-has-current-submenu') && 
                        !item.classList.contains('current')) {
                        submenu.style.display = 'none';
                    }
                }
            });
        });
        
        console.log('🚨 Emergency submenu fix applied');
    }
    
    // Apply emergency fallbacks immediately
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupEmergencyFallbacks);
    } else {
        setupEmergencyFallbacks();
    }
    
    // Also apply fallbacks after a delay in case modules fail to load
    setTimeout(() => {
        if (!window.ModernAdminApp?.getInstance()?.isInitialized) {
            console.warn('⚠️ Modules not initialized after 3 seconds, applying emergency fallbacks');
            setupEmergencyFallbacks();
        }
    }, 3000);
    
    // Ekspozycja API dla backward compatibility (jeśli ktoś tego używa)
    window.MASGlobal = {
        isModularMode: true,
        getApp: () => window.ModernAdminApp?.getInstance(),
        version: '2.0.0-modular',
        setupEmergencyFallbacks: setupEmergencyFallbacks
    };
    
})(); 