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
    
    // Enhanced CSS injection with error recovery
    function injectPreviewCSS(css) {
        try {
            // Allow empty CSS (for clearing styles)
            if (css === null || css === undefined) {
                MASPreviewDiagnostics.error('CSS is null or undefined, cannot inject');
                return false;
            }
            
            if (css.trim() === '') {
                MASPreviewDiagnostics.log('Empty CSS provided, clearing preview styles');
                clearPreviewStyles();
                return true;
            }
            
            MASPreviewDiagnostics.log('Injecting CSS (' + css.length + ' characters)');
            
            // Validate CSS syntax (basic check)
            if (!isValidCSS(css)) {
                MASPreviewDiagnostics.error('Invalid CSS syntax detected, skipping injection');
                return false;
            }
            
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
            });
            
            // Set CSS content safely
            try {
                styleElement.text(css);
            } catch (e) {
                MASPreviewDiagnostics.error('Failed to set CSS content', e);
                return false;
            }
            
            // Append to <head> safely
            try {
                $('head').append(styleElement);
            } catch (e) {
                MASPreviewDiagnostics.error('Failed to append style element to head', e);
                return false;
            }
            
            // Log injection success
            MASPreviewDiagnostics.log('✓ CSS injected successfully');
            
            // Enhanced CSS injection verification
            setTimeout(function() {
                verifyCSSInjection(css);
            }, 100);
            
            return true;
            
        } catch (error) {
            MASPreviewDiagnostics.error('Critical error during CSS injection', error);
            return false;
        }
    }
    
    // CSS validation function
    function isValidCSS(css) {
        // Basic CSS validation - check for balanced braces
        var openBraces = (css.match(/\{/g) || []).length;
        var closeBraces = (css.match(/\}/g) || []).length;
        
        if (openBraces !== closeBraces) {
            MASPreviewDiagnostics.error('CSS validation failed: unbalanced braces');
            return false;
        }
        
        // Check for potentially dangerous content
        var dangerousPatterns = [
            /<script/i,
            /javascript:/i,
            /expression\(/i,
            /behavior:/i,
            /@import/i
        ];
        
        for (var i = 0; i < dangerousPatterns.length; i++) {
            if (dangerousPatterns[i].test(css)) {
                MASPreviewDiagnostics.error('CSS validation failed: potentially dangerous content detected');
                return false;
            }
        }
        
        return true;
    }
    
    // Enhanced CSS injection verification
    function verifyCSSInjection(originalCSS) {
        try {
            // Check if #mas-preview-styles exists in DOM
            var injected = $('#mas-preview-styles');
            
            if (injected.length === 0) {
                MASPreviewDiagnostics.error('CSS injection verification failed - element not found in DOM');
                
                // Attempt recovery
                MASPreviewDiagnostics.log('Attempting CSS injection recovery...');
                setTimeout(function() {
                    injectPreviewCSS(originalCSS);
                }, 500);
                
                return false;
            }
            
            // Verify content matches
            var injectedCSS = injected.text();
            if (injectedCSS !== originalCSS) {
                MASPreviewDiagnostics.error('CSS injection verification failed - content mismatch');
                MASPreviewDiagnostics.log('Expected length: ' + originalCSS.length + ', Actual length: ' + injectedCSS.length);
                return false;
            }
            
            // Check if styles are actually being applied
            var computedStyle = window.getComputedStyle(document.documentElement);
            MASPreviewDiagnostics.log('✓ CSS injection verified - element exists and content matches');
            
            return true;
            
        } catch (error) {
            MASPreviewDiagnostics.error('Error during CSS verification', error);
            return false;
        }
    }
    
    // Clear preview styles function
    function clearPreviewStyles() {
        try {
            var styleElement = $('#mas-preview-styles');
            if (styleElement.length > 0) {
                styleElement.remove();
                MASPreviewDiagnostics.log('✓ Preview styles cleared');
                return true;
            }
            return false;
        } catch (error) {
            MASPreviewDiagnostics.error('Error clearing preview styles', error);
            return false;
        }
    }
    
    // Debounced update function with fallback mode check
    var updateTimeout;
    function updatePreviewDebounced(setting, value) {
        clearTimeout(updateTimeout);
        
        // Skip updates if in fallback mode
        if (errorRecovery.fallbackMode) {
            MASPreviewDiagnostics.log('Preview update skipped - system in fallback mode');
            return;
        }
        
        updateTimeout = setTimeout(function() {
            updatePreview(setting, value);
        }, 300);
    }
    
    // System health check function
    function performHealthCheck() {
        MASPreviewDiagnostics.log('Performing system health check...');
        
        var healthScore = 0;
        var maxScore = 5;
        
        // Check 1: masV2Global availability
        if (typeof masV2Global !== 'undefined' && masV2Global.ajaxUrl && masV2Global.nonce) {
            healthScore++;
            MASPreviewDiagnostics.log('✓ masV2Global configuration healthy');
        } else {
            MASPreviewDiagnostics.error('✗ masV2Global configuration unhealthy');
        }
        
        // Check 2: jQuery availability
        if (typeof jQuery !== 'undefined') {
            healthScore++;
            MASPreviewDiagnostics.log('✓ jQuery available');
        } else {
            MASPreviewDiagnostics.error('✗ jQuery unavailable');
        }
        
        // Check 3: DOM readiness
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            healthScore++;
            MASPreviewDiagnostics.log('✓ DOM ready');
        } else {
            MASPreviewDiagnostics.error('✗ DOM not ready');
        }
        
        // Check 4: Recent successful requests
        var timeSinceLastSuccess = errorRecovery.lastSuccessfulRequest ? 
            Date.now() - errorRecovery.lastSuccessfulRequest : Infinity;
        if (timeSinceLastSuccess < 60000) { // Within last minute
            healthScore++;
            MASPreviewDiagnostics.log('✓ Recent successful requests');
        } else {
            MASPreviewDiagnostics.log('⚠ No recent successful requests');
        }
        
        // Check 5: Error recovery state
        if (errorRecovery.retryCount < errorRecovery.maxRetries && !errorRecovery.fallbackMode) {
            healthScore++;
            MASPreviewDiagnostics.log('✓ Error recovery state healthy');
        } else {
            MASPreviewDiagnostics.log('⚠ Error recovery state degraded');
        }
        
        var healthPercentage = Math.round((healthScore / maxScore) * 100);
        MASPreviewDiagnostics.log('System health: ' + healthScore + '/' + maxScore + ' (' + healthPercentage + '%)');
        
        // Auto-recovery if health is good but system is in fallback mode
        if (healthScore >= 4 && errorRecovery.fallbackMode) {
            MASPreviewDiagnostics.log('Health check passed - attempting to exit fallback mode');
            errorRecovery.fallbackMode = false;
            errorRecovery.retryCount = 0;
            showUserNotification('Live preview functionality restored');
        }
        
        return healthScore;
    }
    
    // Error recovery state
    var errorRecovery = {
        retryCount: 0,
        maxRetries: 3,
        retryDelay: 1000,
        fallbackMode: false,
        lastSuccessfulRequest: null
    };
    
    // Main update preview function with enhanced error recovery
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
            handleCriticalError('masV2Global configuration missing');
            return;
        }
        
        // Validate masV2Global.nonce exists
        if (!masV2Global.nonce) {
            MASPreviewDiagnostics.error('Validation failed: masV2Global.nonce is not defined');
            handleCriticalError('Security nonce missing');
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
            
            // Reset error recovery on successful response
            errorRecovery.retryCount = 0;
            errorRecovery.fallbackMode = false;
            errorRecovery.lastSuccessfulRequest = Date.now();
            
            // Task 4.3: Improve AJAX error handling
            
            // Check if response exists
            if (!response) {
                MASPreviewDiagnostics.error('Response is empty or undefined');
                handlePreviewError('empty_response', setting, value);
                return;
            }
            
            // Check if response.success is true
            if (!response.success) {
                MASPreviewDiagnostics.error('Server returned error response', response.data || response);
                handlePreviewError('server_error', setting, value, response.data);
                return;
            }
            
            // Check if response.data exists
            if (!response.data) {
                MASPreviewDiagnostics.error('Response missing data property', response);
                handlePreviewError('missing_data', setting, value);
                return;
            }
            
            // Check if response.data.css exists (allow empty CSS)
            if (typeof response.data.css === 'undefined') {
                MASPreviewDiagnostics.error('Response missing CSS data', response.data);
                handlePreviewError('missing_css', setting, value);
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
            
            handleNetworkError(setting, value, textStatus, errorThrown);
        });
    }
    
    // Enhanced error recovery functions
    function handlePreviewError(errorType, setting, value, errorData) {
        MASPreviewDiagnostics.error('Preview error: ' + errorType, {
            setting: setting,
            value: value,
            errorData: errorData
        });
        
        // Attempt recovery based on error type
        switch (errorType) {
            case 'empty_response':
            case 'missing_data':
            case 'missing_css':
                if (errorRecovery.retryCount < errorRecovery.maxRetries) {
                    retryPreviewUpdate(setting, value);
                } else {
                    enableFallbackMode();
                }
                break;
            case 'server_error':
                if (errorData && errorData.message) {
                    MASPreviewDiagnostics.log('Server error message: ' + errorData.message);
                }
                enableFallbackMode();
                break;
            default:
                enableFallbackMode();
        }
    }
    
    function handleNetworkError(setting, value, textStatus, errorThrown) {
        MASPreviewDiagnostics.error('Network error occurred', {
            status: textStatus,
            error: errorThrown,
            setting: setting,
            value: value
        });
        
        // Check if this is a network connectivity issue
        if (textStatus === 'timeout' || textStatus === 'error' || errorThrown === 'Network Error') {
            if (errorRecovery.retryCount < errorRecovery.maxRetries) {
                retryPreviewUpdate(setting, value);
            } else {
                MASPreviewDiagnostics.error('Max retries exceeded, enabling fallback mode');
                enableFallbackMode();
            }
        } else {
            enableFallbackMode();
        }
    }
    
    function retryPreviewUpdate(setting, value) {
        errorRecovery.retryCount++;
        var delay = errorRecovery.retryDelay * errorRecovery.retryCount;
        
        MASPreviewDiagnostics.log('Retrying preview update in ' + delay + 'ms (attempt ' + errorRecovery.retryCount + '/' + errorRecovery.maxRetries + ')');
        
        setTimeout(function() {
            updatePreview(setting, value);
        }, delay);
    }
    
    function enableFallbackMode() {
        if (!errorRecovery.fallbackMode) {
            errorRecovery.fallbackMode = true;
            MASPreviewDiagnostics.log('Fallback mode enabled - preview updates disabled temporarily');
            
            // Show user notification if possible
            showUserNotification('Live preview temporarily unavailable. Settings will still be saved.');
            
            // Attempt to restore after 30 seconds
            setTimeout(function() {
                if (errorRecovery.fallbackMode) {
                    MASPreviewDiagnostics.log('Attempting to restore preview functionality...');
                    errorRecovery.fallbackMode = false;
                    errorRecovery.retryCount = 0;
                }
            }, 30000);
        }
    }
    
    function handleCriticalError(reason) {
        MASPreviewDiagnostics.error('Critical error: ' + reason);
        enableFallbackMode();
        showUserNotification('Live preview system error: ' + reason);
    }
    
    function showUserNotification(message) {
        // Try to show WordPress admin notice if available
        if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
            try {
                wp.data.dispatch('core/notices').createNotice('warning', message, {
                    isDismissible: true,
                    type: 'snackbar'
                });
                return;
            } catch (e) {
                // WordPress notices not available, continue to fallback
            }
        }
        
        // Fallback to console log
        MASPreviewDiagnostics.log('User notification: ' + message);
        
        // Try to create a simple visual notification
        try {
            var notification = $('<div>')
                .css({
                    position: 'fixed',
                    top: '32px',
                    right: '20px',
                    background: '#f0ad4e',
                    color: '#fff',
                    padding: '10px 15px',
                    borderRadius: '4px',
                    zIndex: 999999,
                    fontSize: '13px',
                    maxWidth: '300px'
                })
                .text(message)
                .appendTo('body');
            
            setTimeout(function() {
                notification.fadeOut(500, function() {
                    notification.remove();
                });
            }, 5000);
        } catch (e) {
            // Visual notification failed, message already logged
        }
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
        
        // Set up periodic health checks (every 2 minutes)
        setInterval(function() {
            if (MASPreviewDiagnostics.enabled) {
                performHealthCheck();
            }
        }, 120000);
        
        // Set up connection test (every 5 minutes if in fallback mode)
        setInterval(function() {
            if (errorRecovery.fallbackMode) {
                MASPreviewDiagnostics.log('Testing connection recovery...');
                testConnectionRecovery();
            }
        }, 300000);
    });
    
    // Connection recovery test
    function testConnectionRecovery() {
        if (typeof masV2Global === 'undefined' || !masV2Global.ajaxUrl || !masV2Global.nonce) {
            MASPreviewDiagnostics.log('Connection test skipped - configuration unavailable');
            return;
        }
        
        MASPreviewDiagnostics.log('Testing AJAX connectivity...');
        
        $.post(masV2Global.ajaxUrl, {
            action: 'mas_v2_get_preview_css',
            nonce: masV2Global.nonce,
            setting: 'connection_test',
            value: 'test'
        })
        .done(function(response) {
            MASPreviewDiagnostics.log('Connection test successful');
            if (errorRecovery.fallbackMode) {
                MASPreviewDiagnostics.log('Exiting fallback mode - connection restored');
                errorRecovery.fallbackMode = false;
                errorRecovery.retryCount = 0;
                errorRecovery.lastSuccessfulRequest = Date.now();
                showUserNotification('Live preview connection restored');
            }
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            MASPreviewDiagnostics.log('Connection test failed: ' + textStatus);
        });
    }
    
    // Expose public API for external testing and debugging
    window.MASSimpleLivePreview = {
        // Diagnostic functions
        runDiagnostics: function() {
            return MASPreviewDiagnostics.test();
        },
        
        performHealthCheck: function() {
            return performHealthCheck();
        },
        
        // Control functions
        enableFallbackMode: function() {
            enableFallbackMode();
        },
        
        exitFallbackMode: function() {
            errorRecovery.fallbackMode = false;
            errorRecovery.retryCount = 0;
            MASPreviewDiagnostics.log('Fallback mode manually disabled');
        },
        
        // Testing functions
        testConnectionRecovery: function() {
            testConnectionRecovery();
        },
        
        injectTestCSS: function(css) {
            return injectPreviewCSS(css || 'body { border: 2px solid red; }');
        },
        
        clearStyles: function() {
            return clearPreviewStyles();
        },
        
        // Status functions
        getStatus: function() {
            return {
                fallbackMode: errorRecovery.fallbackMode,
                retryCount: errorRecovery.retryCount,
                lastSuccessfulRequest: errorRecovery.lastSuccessfulRequest,
                diagnosticsEnabled: MASPreviewDiagnostics.enabled
            };
        },
        
        // Enable/disable diagnostics
        enableDiagnostics: function() {
            MASPreviewDiagnostics.enabled = true;
            MASPreviewDiagnostics.log('Diagnostics enabled');
        },
        
        disableDiagnostics: function() {
            MASPreviewDiagnostics.enabled = false;
            console.log('[MAS Preview] Diagnostics disabled');
        }
    };
    
})(jQuery);
