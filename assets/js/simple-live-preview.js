/**
 * Modern Admin Styler V2 - Simple Live Preview
 * Inspired by working version from kopia/assets/js/live-preview.js
 */

(function($) {
    'use strict';
    
    console.log('🎨 MAS Simple Live Preview: Starting...');
    
    // Check if required data is available
    if (typeof masV2Global === 'undefined') {
        console.error('MAS: masV2Global not available');
        return;
    }
    
    console.log('MAS: masV2Global available');
    
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
        console.log('MAS: Updating preview for', setting, '=', value);
        
        $.post(masV2Global.ajaxUrl, {
            action: 'mas_v2_get_preview_css',
            nonce: masV2Global.nonce,
            setting: setting,
            value: value
        })
        .done(function(response) {
            console.log('MAS: AJAX response:', response);
            
            if (response.success && response.data && response.data.css) {
                // Remove old styles
                $('#mas-preview-styles').remove();
                
                // Add new styles
                if (response.data.css.trim()) {
                    $('<style id="mas-preview-styles">' + response.data.css + '</style>').appendTo('head');
                    console.log('MAS: CSS applied successfully');
                }
                
                // Show success message if available
                if (window.console && response.data.performance) {
                    console.log('MAS: Generated in ' + response.data.performance.execution_time_ms + 'ms');
                }
            } else {
                console.error('MAS: Invalid response:', response);
            }
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            console.error('MAS: AJAX failed:', textStatus, errorThrown);
        });
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        console.log('MAS: Initializing live preview...');
        
        // Color pickers
        $('.mas-v2-color').each(function() {
            var $input = $(this);
            
            if ($.fn.wpColorPicker) {
                $input.wpColorPicker({
                    change: function(event, ui) {
                        var name = $input.attr('name');
                        var matches = name.match(/\[([^\]]+)\]$/);
                        if (matches) {
                            var setting = matches[1];
                            var value = ui.color.toString();
                            console.log('Color changed:', setting, '=', value);
                            updatePreviewDebounced(setting, value);
                        }
                    }
                });
            } else {
                // Fallback for native color input
                $input.on('change', function() {
                    var name = $(this).attr('name');
                    var matches = name.match(/\[([^\]]+)\]$/);
                    if (matches) {
                        var setting = matches[1];
                        var value = $(this).val();
                        console.log('Color changed (native):', setting, '=', value);
                        updatePreviewDebounced(setting, value);
                    }
                });
            }
        });
        
        // Text inputs
        $('.mas-v2-input[type="text"], .mas-v2-input[type="number"]').on('input', function() {
            var name = $(this).attr('name');
            var matches = name.match(/\[([^\]]+)\]$/);
            if (matches) {
                var setting = matches[1];
                var value = $(this).val();
                console.log('Input changed:', setting, '=', value);
                updatePreviewDebounced(setting, value);
            }
        });
        
        // Checkboxes
        $('.mas-v2-checkbox').on('change', function() {
            var name = $(this).attr('name');
            var matches = name.match(/\[([^\]]+)\]$/);
            if (matches) {
                var setting = matches[1];
                var value = $(this).is(':checked');
                console.log('Checkbox changed:', setting, '=', value);
                updatePreviewDebounced(setting, value);
            }
        });
        
        // Select dropdowns
        $('.mas-v2-select').on('change', function() {
            var name = $(this).attr('name');
            var matches = name.match(/\[([^\]]+)\]$/);
            if (matches) {
                var setting = matches[1];
                var value = $(this).val();
                console.log('Select changed:', setting, '=', value);
                updatePreviewDebounced(setting, value);
            }
        });
        
        // Range sliders
        $('.mas-v2-slider').on('input change', function() {
            var name = $(this).attr('name');
            var matches = name.match(/\[([^\]]+)\]$/);
            if (matches) {
                var setting = matches[1];
                var value = $(this).val();
                console.log('Slider changed:', setting, '=', value);
                updatePreviewDebounced(setting, value);
            }
        });
        
        console.log('MAS: Live preview initialized');
    });
    
})(jQuery);
