/**
 * Modern Admin Styler V2 - Simple Settings Page Handler
 * Prosty handler dla strony ustawień - bez skomplikowanych modułów
 * 
 * @deprecated 3.0.0 This file is deprecated and replaced by Phase 3 frontend architecture
 * @see assets/js/mas-admin-app.js
 * @see assets/js/mas-settings-form-handler.js (Phase 2 fallback)
 * 
 * DEPRECATION NOTICE:
 * This AJAX-based handler has been replaced with:
 * - Phase 3: Unified component-based architecture (mas-admin-app.js)
 * - Phase 2: REST API handler (mas-settings-form-handler.js)
 * 
 * This file eliminates dual handler conflicts and provides better error handling.
 * This file is kept for reference only and should not be loaded.
 * 
 * Migration Guide: See docs/PHASE3-MIGRATION-GUIDE.md
 */

(function($) {
    'use strict';
    
    // Show deprecation warning
    console.warn('⚠️ DEPRECATED: admin-settings-simple.js is deprecated since v3.0.0');
    console.warn('📖 Migration Guide: Use Phase 3 frontend (mas-admin-app.js) or Phase 2 fallback (mas-settings-form-handler.js)');
    console.warn('🔗 See: docs/PHASE3-MIGRATION-GUIDE.md');
    
    // Check if new frontend is active
    if (window.MASUseNewFrontend) {
        console.error('❌ ERROR: Legacy handler loaded while new frontend is active!');
        console.error('This should not happen. Please check your feature flags.');
        return; // Exit immediately
    }
    
    console.log('🎯 MAS Simple Settings: Initializing (LEGACY MODE)...');
    
    // Wyłącz modularny system
    window.MASDisableModules = true;
    
    $(document).ready(function() {
        
        // Usuń wszystkie inne handlery formularza (na wypadek konfliktu)
        $('#mas-v2-settings-form').off('submit');
        
        console.log('✅ Simple handler: Wszystkie poprzednie handlery usunięte');
        
        // Obsługa zapisywania ustawień
        $('#mas-v2-settings-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $button = $form.find('button[type="submit"]');
            const originalText = $button.text();
            
            $button.prop('disabled', true).text('Zapisywanie...');
            
            // Pobierz dane formularza jako obiekt
            const formData = $form.serializeArray();
            const postData = {
                action: 'mas_v2_save_settings',
                nonce: masV2Global.nonce
            };
            
            // Dodaj wszystkie pola formularza do postData
            $.each(formData, function(i, field) {
                postData[field.name] = field.value;
            });
            
            // WAŻNE: Dodaj checkboxy które nie są zaznaczone (nie są w serializeArray)
            // serializeArray() pomija niezaznaczone checkboxy, więc musimy je dodać ręcznie
            $form.find('input[type="checkbox"]').each(function() {
                const name = $(this).attr('name');
                if (name && !postData.hasOwnProperty(name)) {
                    postData[name] = '0'; // Niezaznaczony checkbox = 0
                }
            });
            
            // Debug: Pokaż co wysyłamy
            if (window.console && console.log) {
                console.log('🚀 Wysyłanie danych:', postData);
                console.log('📊 Liczba pól:', Object.keys(postData).length);
            }
            
            $.post(masV2Global.ajaxUrl, postData)
            .done(function(response) {
                if (response.success) {
                    $button.text('✓ Zapisano!');
                    setTimeout(function() {
                        $button.text(originalText).prop('disabled', false);
                    }, 2000);
                } else {
                    alert('Błąd: ' + (response.data ? response.data.message : 'Nieznany błąd'));
                    $button.text(originalText).prop('disabled', false);
                }
            })
            .fail(function() {
                alert('Błąd połączenia z serwerem');
                $button.text(originalText).prop('disabled', false);
            });
        });
        
        // Obsługa resetowania ustawień
        $('.mas-reset-settings').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('Czy na pewno chcesz przywrócić domyślne ustawienia?')) {
                return;
            }
            
            const $button = $(this);
            const originalText = $button.text();
            
            $button.prop('disabled', true).text('Resetowanie...');
            
            $.post(masV2Global.ajaxUrl, {
                action: 'mas_v2_reset_settings',
                nonce: masV2Global.nonce
            })
            .done(function(response) {
                if (response.success) {
                    $button.text('✓ Zresetowano!');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert('Błąd: ' + (response.data ? response.data.message : 'Nieznany błąd'));
                    $button.text(originalText).prop('disabled', false);
                }
            })
            .fail(function() {
                alert('Błąd połączenia z serwerem');
                $button.text(originalText).prop('disabled', false);
            });
        });
        
        // Obsługa zakładek
        $('.mas-tab-button').on('click', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const tab = $button.data('tab');
            
            // Ukryj wszystkie zakładki
            $('.mas-tab-content').hide();
            $('.mas-tab-button').removeClass('active');
            
            // Pokaż wybraną zakładkę
            $('#mas-tab-' + tab).show();
            $button.addClass('active');
        });
        
        console.log('✅ MAS Simple Settings: Initialized');
    });
    
})(jQuery);
