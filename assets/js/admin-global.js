/**
 * Modern Admin Styler V2 - Global Admin Script (CLEANED)
 * Tylko bootstrap i fallback - ZERO duplikacji!
 */

(function() {
    'use strict';
    
    // Sprawdź dostępność modularnej architektury
    const hasModernApp = typeof window.ModernAdminApp !== 'undefined';
    
    // Inicjalizacja aplikacji
    document.addEventListener('DOMContentLoaded', function() {
        initializeApp();
    });
    
    function initializeApp() {
        // Sprawdź czy mamy dostęp do głównego orchestratora
        if (hasModernApp) {
            initializeWithOrchestrator();
        } else {
            console.warn('⚠️ ModernAdminApp nie dostępne, czekam na modules...');
            // Czekaj na załadowanie modułów przez mas-loader.js
            waitForModules();
        }
    }
    
    function initializeWithOrchestrator() {
        try {
            const app = window.ModernAdminApp.getInstance();
            
            if (typeof masV2Global !== 'undefined' && masV2Global.settings) {
                app.init(masV2Global.settings).then(() => {
                    console.log('✅ Modern Admin Styler V2 załadowany przez orchestrator');
                }).catch(error => {
                    console.error('❌ Błąd inicjalizacji przez orchestrator:', error);
                    // KRYTYCZNY: Nie ma już fallback do legacy!
                });
            } else {
                console.warn('⚠️ Brak ustawień masV2Global');
            }
        } catch (error) {
            console.error('❌ Błąd orchestratora:', error);
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
    
    // Ekspozycja API dla backward compatibility (jeśli ktoś tego używa)
    window.MASGlobal = {
        isModularMode: true,
        getApp: () => window.ModernAdminApp?.getInstance(),
        version: '2.0.0-modular'
    };
    
})(); 