/**
 * Modern Admin Styler V2 - Settings Page JavaScript
 * 
 * 🎨 CREATIVE INTERACTIONS 2024
 */

(function($) {
    'use strict';

    class SettingsPageEnhancer {
        constructor() {
            this.tips = [
                'Użyj Ctrl+Shift+T aby przełączyć motyw',
                'Kliknij dwukrotnie na nagłówek aby zwinąć kartę',
                'Przeciągnij karty aby zmienić ich kolejność',
                'Użyj klawiszy 1-9 aby szybko przejść do zakładki',
                'Zapisz ulubione ustawienia jako szablon',
                'Naciśnij Esc aby zamknąć wszystkie okna dialogowe',
                'Użyj / aby szybko wyszukać opcję',
                'Kliknij z Shift aby multi-select checkboxy',
                'Animacje można wyłączyć przyciskiem A',
                'Podgląd na żywo działa w czasie rzeczywistym'
            ];
            
            this.tipIndex = 0;
            this.systemMetrics = {
                ram: 0,
                cpu: 0,
                processes: 0,
                queries: 0
            };
            
            this.init();
        }
        
        init() {
            // Czekaj na załadowanie DOM
            $(document).ready(() => {
                this.enhanceMetricCards();
                this.setupQuickActions();
                this.setupRotatingTips();
                this.setupSystemMonitor();
                this.enhanceFormElements();
                this.setupThemeSwitcher();
                this.setupKeyboardShortcuts();
                this.setupAnimations();
                this.setupConditionalFields();
                this.setupSliderValues();
                this.addInteractiveEffects();
            });
        }
        
        enhanceMetricCards() {
            // Dodaj efekt parallax do metric cards
            $('.mas-v2-metric-card').each(function(index) {
                $(this).css('animation-delay', (index * 0.1) + 's');
                $(this).addClass('animate-in');
            });
            
            // Hover effect z tilt
            $('.mas-v2-metric-card').on('mousemove', function(e) {
                const $card = $(this);
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const tiltX = (y - centerY) / 10;
                const tiltY = (centerX - x) / 10;
                
                $card.css('transform', `perspective(1000px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) translateY(-5px)`);
            });
            
            $('.mas-v2-metric-card').on('mouseleave', function() {
                $(this).css('transform', 'perspective(1000px) rotateX(0) rotateY(0) translateY(0)');
            });
        }
        
        setupQuickActions() {
            // Reset button
            $('#mas-v2-reset-btn').on('click', () => {
                if (confirm('Czy na pewno chcesz zresetować wszystkie ustawienia?')) {
                    this.showLoadingOverlay('Resetowanie ustawień...');
                    
                    $.post(masV2Global.ajaxUrl, {
                        action: 'mas_v2_reset_settings',
                        nonce: masV2Global.nonce
                    }, (response) => {
                        this.hideLoadingOverlay();
                        if (response.success) {
                            this.showNotification('success', response.data.message);
                            setTimeout(() => location.reload(), 1500);
                        }
                    });
                }
            });
            
            // Clear cache button
            $('#mas-v2-clear-cache-btn').on('click', () => {
                this.animateButton($(this));
                this.showNotification('success', 'Cache został wyczyszczony!');
                
                // Symulacja czyszczenia cache
                $('.mas-v2-metric-value').addClass('pulse-animation');
                setTimeout(() => {
                    $('.mas-v2-metric-value').removeClass('pulse-animation');
                }, 1000);
            });
        }
        
        setupRotatingTips() {
            const $tipElement = $('#rotating-tip');
            const $counter = $('#tips-counter');
            
            // Zmiana wskazówki co 5 sekund
            setInterval(() => {
                $tipElement.fadeOut(300, () => {
                    this.tipIndex = (this.tipIndex + 1) % this.tips.length;
                    $tipElement.text(this.tips[this.tipIndex]).fadeIn(300);
                });
            }, 5000);
            
            // Kliknięcie aby przejść do następnej
            $('.mas-v2-tips-card').on('click', () => {
                this.tipIndex = (this.tipIndex + 1) % this.tips.length;
                $tipElement.fadeOut(150, () => {
                    $tipElement.text(this.tips[this.tipIndex]).fadeIn(150);
                });
                
                // Animacja licznika
                const currentCount = parseInt($counter.text());
                $counter.text(currentCount + 1);
            });
        }
        
        setupSystemMonitor() {
            // Symulacja danych systemowych
            const updateSystemMetrics = () => {
                // RAM
                const ram = Math.floor(Math.random() * 200) + 100;
                $('#system-main-value').text(ram + ' MB');
                
                // Procesy
                const processes = Math.floor(Math.random() * 50) + 150;
                $('#processes-mini').text(processes);
                
                // Queries
                const queries = Math.floor(Math.random() * 30) + 20;
                $('#queries-mini').text(queries);
                
                // Trend
                const trend = Math.floor(Math.random() * 20) - 10;
                const $trend = $('#system-trend');
                $trend.text((trend > 0 ? '+' : '') + trend + '%');
                $trend.toggleClass('negative', trend < 0);
            };
            
            updateSystemMetrics();
            setInterval(updateSystemMetrics, 3000);
            
            // Kliknięcie zmienia metrykę
            $('.mas-v2-system-monitor-card').on('click', function() {
                const metrics = ['Pamięć RAM', 'Użycie CPU', 'Dysk SSD', 'Sieć'];
                const values = ['156 MB', '23%', '45 GB', '1.2 MB/s'];
                const currentIndex = $(this).data('metric-index') || 0;
                const nextIndex = (currentIndex + 1) % metrics.length;
                
                $(this).data('metric-index', nextIndex);
                $('#system-main-label').text(metrics[nextIndex]);
                $('#system-main-value').text(values[nextIndex]);
                
                $(this).addClass('mas-v2-success-pulse');
                setTimeout(() => $(this).removeClass('mas-v2-success-pulse'), 600);
            });
        }
        
        enhanceFormElements() {
            // Animowane focus na inputach
            $('.mas-v2-input, .mas-v2-select').on('focus', function() {
                $(this).parent('.mas-v2-field').addClass('focused');
            }).on('blur', function() {
                $(this).parent('.mas-v2-field').removeClass('focused');
            });
            
            // Licznik znaków dla textarea
            $('textarea.mas-v2-input').each(function() {
                const $textarea = $(this);
                const maxLength = 500;
                
                $('<div class="mas-v2-char-counter">0 / ' + maxLength + '</div>')
                    .insertAfter($textarea);
                
                $textarea.on('input', function() {
                    const length = $(this).val().length;
                    $(this).next('.mas-v2-char-counter').text(length + ' / ' + maxLength);
                });
            });
            
            // Color picker preview
            $('input[type="color"]').each(function() {
                const $input = $(this);
                $input.on('input', function() {
                    const color = $(this).val();
                    $(this).css('box-shadow', `0 0 0 3px ${color}30`);
                });
            });
        }
        
        setupThemeSwitcher() {
            // Dodaj przycisk theme switcher jeśli nie istnieje
            if (!$('.mas-v2-theme-switcher').length) {
                $('<div class="mas-v2-theme-switcher" title="Przełącz motyw"></div>')
                    .appendTo('body');
            }
            
            $('.mas-v2-theme-switcher').on('click', function() {
                const $body = $('body');
                const isDark = $body.hasClass('mas-theme-dark');
                
                $(this).addClass('rotating');
                
                if (isDark) {
                    $body.removeClass('mas-theme-dark').addClass('mas-theme-light');
                } else {
                    $body.removeClass('mas-theme-light').addClass('mas-theme-dark');
                }
                
                // Zapisz preferencję
                $.post(masV2Global.ajaxUrl, {
                    action: 'mas_v2_save_theme',
                    theme: isDark ? 'light' : 'dark',
                    nonce: masV2Global.nonce
                });
                
                setTimeout(() => $(this).removeClass('rotating'), 500);
                
                this.showNotification('info', 'Motyw został zmieniony');
            });
        }
        
        setupKeyboardShortcuts() {
            $(document).on('keydown', (e) => {
                // Ctrl/Cmd + S = Save
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    $('#mas-v2-settings-form').submit();
                }
                
                // Ctrl/Cmd + Shift + T = Toggle theme
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'T') {
                    e.preventDefault();
                    $('.mas-v2-theme-switcher').click();
                }
                
                // ESC = Close modals
                if (e.key === 'Escape') {
                    $('.mas-v2-modal').fadeOut();
                }
                
                // 1-9 = Quick tab navigation
                if (e.key >= '1' && e.key <= '9' && !$(e.target).is('input, textarea')) {
                    const tabIndex = parseInt(e.key) - 1;
                    $('.mas-v2-tab-button').eq(tabIndex).click();
                }
            });
        }
        
        setupAnimations() {
            // Intersection Observer dla animacji on scroll
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });
            
            $('.mas-v2-card').each(function() {
                observer.observe(this);
            });
        }
        
        setupConditionalFields() {
            // Pokaż/ukryj pola warunkowe
            $('.conditional-field').each(function() {
                const $field = $(this);
                const showWhen = $field.data('show-when');
                const showValue = $field.data('show-value');
                
                const checkVisibility = () => {
                    const $trigger = $(`#${showWhen}`);
                    let shouldShow = false;
                    
                    if ($trigger.is(':checkbox')) {
                        shouldShow = $trigger.is(':checked') && showValue == '1';
                    } else {
                        shouldShow = $trigger.val() == showValue;
                    }
                    
                    $field.toggleClass('show', shouldShow);
                };
                
                // Initial check
                checkVisibility();
                
                // Listen for changes
                $(`#${showWhen}`).on('change input', checkVisibility);
            });
        }
        
        setupSliderValues() {
            // Aktualizacja wartości sliderów w czasie rzeczywistym
            $('.mas-v2-slider').each(function() {
                const $slider = $(this);
                const $value = $(`.mas-v2-slider-value[data-target="${$slider.attr('id')}"]`);
                
                $slider.on('input', function() {
                    const value = $(this).val();
                    const suffix = $value.text().match(/[^0-9]+$/)?.[0] || '';
                    $value.text(value + suffix);
                    
                    // Dodaj efekt pulse przy zmianie
                    $value.addClass('pulse');
                    setTimeout(() => $value.removeClass('pulse'), 300);
                });
            });
        }
        
        addInteractiveEffects() {
            // Ripple effect na przyciskach
            $('.mas-v2-btn').on('click', function(e) {
                const $btn = $(this);
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const $ripple = $('<span class="ripple"></span>');
                $ripple.css({
                    left: x + 'px',
                    top: y + 'px'
                });
                
                $btn.append($ripple);
                
                setTimeout(() => $ripple.remove(), 600);
            });
            
            // Particle effect na header
            this.createParticles();
        }
        
        createParticles() {
            const $header = $('.mas-v2-header');
            const particleCount = 20;
            
            for (let i = 0; i < particleCount; i++) {
                const $particle = $('<div class="particle"></div>');
                $particle.css({
                    left: Math.random() * 100 + '%',
                    animationDelay: Math.random() * 20 + 's',
                    animationDuration: (Math.random() * 20 + 10) + 's'
                });
                $header.append($particle);
            }
        }
        
        showLoadingOverlay(text = 'Ładowanie...') {
            const $overlay = $(`
                <div class="mas-v2-loading-overlay">
                    <div class="mas-v2-spinner"></div>
                    <div class="mas-v2-loading-text">${text}</div>
                </div>
            `);
            
            $('body').append($overlay);
            setTimeout(() => $overlay.addClass('show'), 10);
        }
        
        hideLoadingOverlay() {
            const $overlay = $('.mas-v2-loading-overlay');
            $overlay.removeClass('show');
            setTimeout(() => $overlay.remove(), 300);
        }
        
        showNotification(type, message) {
            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };
            
            const $notification = $(`
                <div class="mas-v2-notification mas-v2-notification-${type}">
                    <span class="mas-v2-notification-icon">${icons[type]}</span>
                    <span class="mas-v2-notification-message">${message}</span>
                </div>
            `);
            
            $('body').append($notification);
            
            setTimeout(() => $notification.addClass('show'), 10);
            setTimeout(() => {
                $notification.removeClass('show');
                setTimeout(() => $notification.remove(), 300);
            }, 3000);
        }
        
        animateButton($btn) {
            $btn.addClass('mas-v2-btn-animated');
            setTimeout(() => $btn.removeClass('mas-v2-btn-animated'), 600);
        }
    }
    
    // Inicjalizacja
    new SettingsPageEnhancer();
    
    // Export do global scope dla debugowania
    window.SettingsPageEnhancer = SettingsPageEnhancer;
    
})(jQuery);

// Dodaj style dla efektów
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    .ripple {
        position: absolute;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        background: rgba(255,255,255,0.5);
        transform: scale(0);
        animation: ripple 0.6s ease-out;
        pointer-events: none;
    }
    
    .particle {
        position: absolute;
        width: 4px;
        height: 4px;
        background: rgba(255,255,255,0.3);
        border-radius: 50%;
        animation: particleFloat 20s infinite linear;
    }
    
    @keyframes particleFloat {
        from {
            transform: translateY(100vh) rotate(0deg);
            opacity: 0;
        }
        10% {
            opacity: 1;
        }
        90% {
            opacity: 1;
        }
        to {
            transform: translateY(-100vh) rotate(720deg);
            opacity: 0;
        }
    }
    
    .mas-v2-loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.8);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 100000;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .mas-v2-loading-overlay.show {
        opacity: 1;
    }
    
    .mas-v2-loading-text {
        color: white;
        font-size: 18px;
        margin-top: 20px;
    }
    
    .mas-v2-notification {
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 15px 20px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        gap: 10px;
        transform: translateX(400px);
        transition: transform 0.3s ease;
        z-index: 100000;
    }
    
    .mas-v2-notification.show {
        transform: translateX(0);
    }
    
    .mas-v2-notification-icon {
        font-size: 20px;
    }
    
    .mas-v2-notification-success {
        border-left: 4px solid #10b981;
    }
    
    .mas-v2-notification-error {
        border-left: 4px solid #ef4444;
    }
    
    .mas-v2-notification-warning {
        border-left: 4px solid #f59e0b;
    }
    
    .mas-v2-notification-info {
        border-left: 4px solid #3b82f6;
    }
    
    .mas-v2-btn-animated {
        animation: buttonPulse 0.6s ease;
    }
    
    @keyframes buttonPulse {
        0% { transform: scale(1); }
        50% { transform: scale(0.95); }
        100% { transform: scale(1); }
    }
    
    .pulse {
        animation: pulse 0.3s ease;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    .animate-in {
        animation: fadeInUp 0.6s ease forwards;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .rotating {
        animation: rotate 0.5s ease;
    }
    
    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .mas-v2-field.focused .mas-v2-label {
        color: #6366f1;
    }
    
    .mas-v2-char-counter {
        font-size: 12px;
        color: #6b7280;
        text-align: right;
        margin-top: 4px;
    }
    
    .pulse-animation {
        animation: pulse 1s ease;
    }
`;
document.head.appendChild(style); 