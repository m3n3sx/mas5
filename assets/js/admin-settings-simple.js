/**
 * Modern Admin Styler V2 - Simple Settings Page Handler
 * Prosty handler dla strony ustawień - bez skomplikowanych modułów
 */

(function($) {
    'use strict';
    
    console.log('🎯 MAS Simple Settings: Initializing...');
    
    $(document).ready(function() {
        
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
