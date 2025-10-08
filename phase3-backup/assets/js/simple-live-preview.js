/**
 * Modern Admin Styler V2 - Simple Live Preview
 * Inspired by working version from kopia/assets/js/live-preview.js
 */

(function($) {
    'use strict';
    
    // Diagnostic System
    var MASPreviewDiagnostics = {
        enabled: true,
        
        log: function(message, data) {
            if (!this.enabled) return;
            if (data !== undefined) {
                console.log('[MAS Preview] ' + message, data);
            } else {
                console.log('[MAS Preview] ' + message);
            }
        },
        
        error: function(message, error) {
            if (error !== undefined) {
                console.error('[MAS Preview ERROR] ' + message, error);
            } else {
                console.error('[MAS Preview ERROR] ' + message);
            }
        },
        
        test: function() {
            this.log('Running diagnostics...');
            
            // Test 1: masV2Global exists
            if (typeof masV2Global === 'undefined') {
                this.error('masV2Global is not defined');
                return false;
            }
            this.log('✓ masV2Global defined', masV2Global);
            
            // Test 2: Required properties
            var required = ['ajaxUrl', 'nonce', 'settings'];
            for (var i = 0; i < required.length; i++) {
                var prop = required[i];
                if (!masV2Global[prop]) {
                    this.error('masV2Global.' + prop + ' is missing');
                    return false;
                }
            }
            this.log('✓ All required properties present');
            
            // Test 3: jQuery available
            if (typeof jQuery === 'undefined') {
                this.error('jQuery is not loaded');
                return false;
            }
            this.log('✓ jQuery loaded');
            
            // Test 4: Form elements exist
            var elements = $('.mas-v2-color, .mas-v2-input, .mas-v2-checkbox, .mas-v2-select, .mas-v2-slider');
            this.log('✓ Found ' + elements.length + ' form elements');
            
            return true;
        }
    };
    
    MASPreviewDiagnostics.log('Script loading...');
    
    // Extract setting name from input name attribute
    function extractSettingName(name) {
        if (!name) {
            MASPreviewDiagnostics.error('extractSettingName: name is empty or undefined');
            return null;
        }
        
        // Handle format: mas_v2_settings[setting_name]
        var matches = name.match(/\[([^\]]+)\]$/);
        if (matches && matches[1]) {
            return matches[1];
        }
        
        // Handle format: setting_name (direct name)
        if (typeof name === 'string' && name.length > 0) {
            return name;
        }
        
        MASPreviewDiagnostics.error('extractSettingName: invalid name format', name);
        return null;
    }
    
    // Task 5.1: Implement injectPreviewCSS() function
    function injectPreviewCSS(css) {
        // Check if CSS is not empty
        if (!css || css.trim() === '') {
            MASPreviewDiagnostics.log('Empty CSS, skipping injection');
            return;
        }
        
        MASPreviewDiagnostics.log('Injecting CSS (' + css.length + ' characters)');
        
        // Remove old #mas-preview-styles element
        var oldStyles = $('#mas-preview-styles');
        if (oldStyles.length > 0) {
            MASPreviewDiagnostics.log('Removing old preview styles');
            oldStyles.remove();
        }
        
        // Create new <style> element with id="mas-preview-styles"
        var styleElement = $('<style>', {
            id: 'mas-preview-styles',
            type: 'text/css'
        }).text(css);
        
        // Append to <head>
        $('head').append(styleElement);
        
        // Log injection success
        MASPreviewDiagnostics.log('✓ CSS injected successfully');
        
        // Task 5.2: Add CSS injection verification
        // Wait 100ms after injection
        setTimeout(function() {
            // Check if #mas-preview-styles exists in DOM
            var injected = $('#mas-preview-styles');
            
            if (injected.length === 0) {
                // Log error if verification fails
                MASPreviewDiagnostics.error('CSS injection verification failed - element not found in DOM');
            } else {
                // Log verification result
                MASPreviewDiagnostics.log('✓ CSS injection verified - element exists in DOM');
            }
        }, 100);
    }
    
    // Debounced update function
    var updateTimeout;
    function updatePreviewDebounced(setting, value) {
        clearTimeout(updateTimeout);
        updateTimeout = setTimeout(function() {
            updatePreview(setting, value);
        }, 300);
    }
    
    // Main update preview function
    function updatePreview(setting, value) {
        // Task 4.1: Add validation to updatePreview() function
        
        // Validate setting name is not empty
        if (!setting || setting.trim() === '') {
            MASPreviewDiagnostics.error('Validation failed: setting name is empty');
            return;
        }
        
        // Validate masV2Global.ajaxUrl exists
        if (typeof masV2Global === 'undefined' || !masV2Global.ajaxUrl) {
            MASPreviewDiagnostics.error('Validation failed: masV2Global.ajaxUrl is not defined');
            return;
        }
        
        // Validate masV2Global.nonce exists
        if (!masV2Global.nonce) {
            MASPreviewDiagnostics.error('Validation failed: masV2Global.nonce is not defined');
            return;
        }
        
        MASPreviewDiagnostics.log('Updating preview for ' + setting + ' = ' + value);
        
        // Task 4.2: Enhance AJAX request logging
        var requestData = {
            action: 'mas_v2_get_preview_css',
            nonce: masV2Global.nonce,
            setting: setting,
            value: value
        };
        
        MASPreviewDiagnostics.log('Sending AJAX request', requestData);
        
        $.post(masV2Global.ajaxUrl, requestData)
        .done(function(response) {
            // Task 4.2: Log when response is received
            MASPreviewDiagnostics.log('AJAX response received', response);
            
            // Task 4.3: Improve AJAX error handling
            
            // Check if response exists
            if (!response) {
                MASPreviewDiagnostics.error('Response is empty or undefined');
                return;
            }
            
            // Check if response.success is true
            if (!response.success) {
                MASPreviewDiagnostics.error('Server returned error response', response.data || response);
                return;
            }
            
            // Check if response.data exists
            if (!response.data) {
                MASPreviewDiagnostics.error('Response missing data property', response);
                return;
            }
            
            // Check if response.data.css exists
            if (!response.data.css) {
                MASPreviewDiagnostics.error('Response missing CSS data', response.data);
                return;
            }
            
            // All checks passed - inject CSS using the dedicated function
            injectPreviewCSS(response.data.css);
            
            // Show performance metrics if available
            if (response.data.performance) {
                MASPreviewDiagnostics.log('CSS generated in ' + response.data.performance.execution_time_ms + 'ms');
            }
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            // Task 4.3: Log AJAX failures with status and error details
            MASPreviewDiagnostics.error('AJAX request failed', {
                status: textStatus,
                error: errorThrown,
                statusCode: jqXHR.status,
                statusText: jqXHR.statusText,
                response: jqXHR.responseText
            });
        });
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        MASPreviewDiagnostics.log('Initializing live preview...');
        
        // Run diagnostic tests
        var diagnosticsPassed = MASPreviewDiagnostics.test();
        
        if (!diagnosticsPassed) {
            MASPreviewDiagnostics.error('Critical diagnostic checks failed - exiting gracefully');
            return;
        }
        
        MASPreviewDiagnostics.log('All diagnostic checks passed - proceeding with initialization');
        
        // Color pickers
        $('.mas-v2-color').each(function() {
            var $input = $(this);
            var name = $input.attr('name');
            
            // Handle missing name attributes gracefully
            if (!name) {
                MASPreviewDiagnostics.error('Color input missing name attribute', $input[0]);
                return;
            }
            
            // Check if wpColorPicker is available
            if ($.fn.wpColorPicker) {
                $input.wpColorPicker({
                    change: function(_event, ui) {
                        var setting = extractSettingName(name);
                        if (setting) {
                            var value = ui.color.toString();
                            MASPreviewDiagnostics.log('Color changed: ' + setting + ' = ' + value);
                            updatePreviewDebounced(setting, value);
                        }
                    },
                    clear: function() {
                        var setting = extractSettingName(name);
                        if (setting) {
                            MASPreviewDiagnostics.log('Color cleared: ' + setting);
                            updatePreviewDebounced(setting, '');
                        }
                    }
                });
            } else {
                // Fallback for native color input
                $input.on('change', function() {
                    var setting = extractSettingName(name);
                    if (setting) {
                        var value = $(this).val();
                        MASPreviewDiagnostics.log('Color changed (native): ' + setting + ' = ' + value);
                        updatePreviewDebounced(setting, value);
                    }
                });
            }
        });
        
        // Text and number inputs - bind to 'input' event for real-time updates
        $('.mas-v2-input[type="text"], .mas-v2-input[type="number"]').on('input', function() {
            var name = $(this).attr('name');
            var setting = extractSettingName(name);
            
            if (setting) {
                var value = $(this).val();
                MASPreviewDiagnostics.log('Input changed: ' + setting + ' = ' + value);
                updatePreviewDebounced(setting, value);
            }
        });
        
        // Checkboxes - bind to 'change' event, get boolean value
        $('.mas-v2-checkbox').on('change', function() {
            var name = $(this).attr('name');
            var setting = extractSettingName(name);
            
            if (setting) {
                var value = $(this).is(':checked');
                MASPreviewDiagnostics.log('Checkbox changed: ' + setting + ' = ' + value);
                updatePreviewDebounced(setting, value);
            }
        });
        
        // Select dropdowns - bind to 'change' event, get selected value
        $('.mas-v2-select').on('change', function() {
            var name = $(this).attr('name');
            var setting = extractSettingName(name);
            
            if (setting) {
                var value = $(this).val();
                MASPreviewDiagnostics.log('Select changed: ' + setting + ' = ' + value);
                updatePreviewDebounced(setting, value);
            }
        });
        
        // Range sliders - bind to both 'input' and 'change' events, get slider value
        $('.mas-v2-slider').on('input change', function() {
            var name = $(this).attr('name');
            var setting = extractSettingName(name);
            
            if (setting) {
                var value = $(this).val();
                MASPreviewDiagnostics.log('Slider changed: ' + setting + ' = ' + value);
                updatePreviewDebounced(setting, value);
            }
        });
        
        MASPreviewDiagnostics.log('✓ Live preview initialized successfully');
    });
    
})(jQuery);
