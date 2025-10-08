/**
 * Modern Admin Styler V2 - Settings Page UI (CLEANED & REFACTORED)
 * 
 * ✅ CZYSTA ARCHITEKTURA:
 * - Tylko UI i event handling
 * - ZERO hardcoded CSS
 * - Delegacja logiki do modułów
 * - Backward compatibility
 */

(function($) {
    "use strict";

    // Sprawdź dostępność modularnej architektury
    const hasModernApp = typeof window.ModernAdminApp !== 'undefined';
    
    // Główny obiekt aplikacji (cienka warstwa UI)
    const MAS = {
        app: null,
        modules: {},
        isModernMode: hasModernApp,
        
        init: function() {
            console.log(`🎯 Settings UI Init ${this.isModernMode ? '(modular)' : '(waiting...)'}`);
            
            if (this.isModernMode) {
                this.initModernMode();
            } else {
                this.waitForModules();
            }
        },
        
        waitForModules: function() {
            let attempts = 0;
            const maxAttempts = 50;
            
            const checkInterval = setInterval(() => {
                attempts++;
                
                if (typeof window.ModernAdminApp !== 'undefined') {
                    clearInterval(checkInterval);
                    this.isModernMode = true;
                    this.initModernMode();
                } else if (attempts >= maxAttempts) {
                    clearInterval(checkInterval);
                    console.error('❌ ModernAdminApp nie załadowane');
                }
            }, 100);
        },
        
        initModernMode: function() {
            try {
                this.app = window.ModernAdminApp.getInstance();
                
                // Pobierz moduły
                this.modules.settings = this.app.getModule('settingsManager');
                this.modules.livePreview = this.app.getModule('livePreviewManager');
                this.modules.theme = this.app.getModule('themeManager');
                this.modules.menu = this.app.getModule('menuManager');
                this.modules.bodyClass = this.app.getModule('bodyClassManager');
                
                console.log('✅ Settings UI connected to modules');
                
                // Inicjalizuj UI
                this.initUI();
                this.bindEvents();
                this.setupGlobalAccess();
                
            } catch (error) {
                console.error('❌ Module connection error:', error);
            }
        },
        
        initUI: function() {
            this.initTabs();
            this.initColorPickers();
            this.initSliders();
            this.initCornerRadius();
            this.initConditionalFields();
            this.initTooltips();
            this.initKeyboardShortcuts();
        },
        
        bindEvents: function() {
            const self = this;
            
            // Save settings
            $(document).on("click", "#mas-v2-save-btn", function(e) {
                e.preventDefault();
                self.modules.settings?.saveSettings();
            });
            
            // Reset settings
            $(document).on("click", "#mas-v2-reset-btn", function(e) {
                e.preventDefault();
                self.modules.settings?.resetSettings();
            });
            
            // Export/Import
            $(document).on("click", "#mas-v2-export-btn", function(e) {
                e.preventDefault();
                self.modules.settings?.exportSettings();
            });
            
            $(document).on("click", "#mas-v2-import-btn", function(e) {
                e.preventDefault();
                $("#mas-v2-import-file").click();
            });
            
            $(document).on("change", "#mas-v2-import-file", function(e) {
                if (self.modules.settings && e.target.files.length > 0) {
                    self.modules.settings.handleImportFile(e.target.files[0]);
                }
            });
            
            // Live preview toggle
            $(document).on("change", "#mas-v2-live-preview", function() {
                self.modules.livePreview?.toggle();
            });
            
            // All form changes for live preview
            $(document).on("change input", ".mas-v2-field", function() {
                if (self.modules.livePreview?.isEnabled()) {
                    const formData = self.gatherFormData();
                    self.modules.livePreview.updatePreview(formData);
                }
            });
        },
        
        setupGlobalAccess: function() {
            // Global API
            window.MAS = this;
            window.ModernAdminStyler = this;
            
            // Quick access functions
            window.masToggleTheme = () => this.modules.theme?.toggleTheme();
            window.masSaveSettings = () => this.modules.settings?.saveSettings();
            window.masToggleLivePreview = () => this.modules.livePreview?.toggle();
            window.masToggleFloatingMenu = () => this.modules.menu?.toggleFloating();
        },
        
        // ========== UI FUNCTIONS ==========
        
        initTabs: function() {
            $(".mas-v2-nav-tab").on("click", function(e) {
                e.preventDefault();
                
                const targetTab = $(this).data("tab");
                
                // Update tabs
                $(".mas-v2-nav-tab").removeClass("nav-tab-active");
                $(this).addClass("nav-tab-active");
                
                // Update content
                $(".mas-v2-tab-content").removeClass("active");
                $("#" + targetTab).addClass("active");
                
                // Store active tab
                localStorage.setItem('mas-v2-active-tab', targetTab);
                
                // Trigger tab change event
                $(document).trigger('mas-tab-changed', [targetTab]);
            });
            
            // Restore active tab
            const activeTab = localStorage.getItem('mas-v2-active-tab');
            if (activeTab && $("#" + activeTab).length) {
                $(`.mas-v2-nav-tab[data-tab="${activeTab}"]`).click();
            }
        },
        
        initColorPickers: function() {
            if ($.fn.wpColorPicker) {
                $(".mas-v2-color-picker").wpColorPicker({
                    change: function(event, ui) {
                        $(this).trigger('change');
                    },
                    clear: function() {
                        $(this).trigger('change');
                    }
                });
            }
        },
        
        initSliders: function() {
            if ($.fn.slider) {
                $(".mas-v2-slider").each(function() {
                    const $slider = $(this);
                    const $input = $slider.siblings('input');
                    const min = parseInt($input.attr('min')) || 0;
                    const max = parseInt($input.attr('max')) || 100;
                    const value = parseInt($input.val()) || 0;
                    
                    $slider.slider({
                        min: min,
                        max: max,
                        value: value,
                        slide: function(event, ui) {
                            $input.val(ui.value).trigger('change');
                            $slider.siblings('.mas-v2-slider-value').text(ui.value);
                        }
                    });
                    
                    // Initial value display
                    $slider.siblings('.mas-v2-slider-value').text(value);
                });
            }
        },
        
        initCornerRadius: function() {
            $(document).on('change', 'input[name$="_radius_type"]', function() {
                const $this = $(this);
                const type = $this.val();
                const prefix = $this.attr('name').replace('_radius_type', '');
                
                if (type === 'all') {
                    $(`.mas-v2-individual-radius[data-prefix="${prefix}"]`).hide();
                    $(`.mas-v2-all-radius[data-prefix="${prefix}"]`).show();
                } else if (type === 'individual') {
                    $(`.mas-v2-all-radius[data-prefix="${prefix}"]`).hide();
                    $(`.mas-v2-individual-radius[data-prefix="${prefix}"]`).show();
                }
            });
            
            // Initialize on page load
            $('input[name$="_radius_type"]:checked').trigger('change');
        },
        
        initConditionalFields: function() {
            // Generic conditional field system
            $(document).on('change', '[data-conditional-trigger]', function() {
                const $trigger = $(this);
                const targetSelector = $trigger.data('conditional-target');
                const showValue = $trigger.data('conditional-value');
                const $target = $(targetSelector);
                
                if ($trigger.is(':checkbox')) {
                    const isChecked = $trigger.is(':checked');
                    const shouldShow = showValue ? isChecked === showValue : isChecked;
                    $target.toggle(shouldShow);
                } else {
                    const currentValue = $trigger.val();
                    $target.toggle(currentValue === showValue);
                }
            });
            
            // Initialize on page load
            $('[data-conditional-trigger]').trigger('change');
        },
        
        initTooltips: function() {
            // Simple tooltip system
            $(document).on('mouseenter', '[data-tooltip]', function() {
                const $this = $(this);
                const text = $this.data('tooltip');
                
                const $tooltip = $('<div class="mas-v2-tooltip">')
                    .text(text)
                    .appendTo('body');
                
                const rect = this.getBoundingClientRect();
                $tooltip.css({
                    top: rect.bottom + 10,
                    left: rect.left + (rect.width / 2) - ($tooltip.outerWidth() / 2)
                });
            });
            
            $(document).on('mouseleave', '[data-tooltip]', function() {
                $('.mas-v2-tooltip').remove();
            });
        },
        
        initKeyboardShortcuts: function() {
            $(document).on('keydown', function(e) {
                // Ctrl+S - Save
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    $("#mas-v2-save-btn").click();
                }
                
                // Ctrl+Shift+L - Toggle Live Preview
                if (e.ctrlKey && e.shiftKey && e.key === 'L') {
                    e.preventDefault();
                    $("#mas-v2-live-preview").click();
                }
                
                // Ctrl+Shift+M - Toggle Floating Menu
                if (e.ctrlKey && e.shiftKey && e.key === 'M') {
                    e.preventDefault();
                    window.masToggleFloatingMenu?.();
                }
            });
        },
        
        gatherFormData: function() {
            const formData = {};
            
            // Gather all form fields
            $('.mas-v2-field').each(function() {
                const $field = $(this);
                const name = $field.attr('name');
                
                if (!name) return;
                
                if ($field.is(':checkbox')) {
                    formData[name] = $field.is(':checked');
                } else if ($field.is(':radio')) {
                    if ($field.is(':checked')) {
                        formData[name] = $field.val();
                    }
                } else {
                    formData[name] = $field.val();
                }
            });
            
            return formData;
        },
        
        // Public API methods
        showNotification: function(message, type = 'success') {
            const $notification = $(`
                <div class="mas-v2-notification ${type}">
                    <span>${message}</span>
                    <button class="mas-v2-notification-close">&times;</button>
                </div>
            `);
            
            $('body').append($notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                $notification.fadeOut(() => $notification.remove());
            }, 5000);
        },
        
        updateFieldValue: function(fieldName, value) {
            const $field = $(`[name="${fieldName}"]`);
            
            if ($field.is(':checkbox')) {
                $field.prop('checked', !!value);
            } else {
                $field.val(value);
            }
            
            $field.trigger('change');
        },
        
        getFieldValue: function(fieldName) {
            const $field = $(`[name="${fieldName}"]`);
            
            if ($field.is(':checkbox')) {
                return $field.is(':checked');
            } else if ($field.is(':radio')) {
                return $field.filter(':checked').val();
            } else {
                return $field.val();
            }
        }
    };
    
    // Initialize when document ready
    $(document).ready(function() {
        MAS.init();
    });
    
    // Close notifications
    $(document).on('click', '.mas-v2-notification-close', function() {
        $(this).parent().fadeOut(() => $(this).parent().remove());
    });

})(jQuery);