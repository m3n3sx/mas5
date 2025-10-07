/**
 * Legacy Bridge
 * 
 * Provides compatibility layer between new Phase 3 frontend and legacy code.
 * Ensures no conflicts between old and new systems during migration.
 * Includes polyfills for older browsers.
 * 
 * @package ModernAdminStylerV2
 * @subpackage JavaScript/Legacy
 * @since 3.0.0
 */

(function(window, document) {
    'use strict';
    
    /**
     * Legacy Bridge Class
     */
    class LegacyBridge {
        constructor() {
            this.initialized = false;
            this.legacyHandlers = [];
            this.polyfillsLoaded = false;
            
            this.log('LegacyBridge created');
        }
        
        /**
         * Initialize legacy bridge
         */
        init() {
            if (this.initialized) {
                this.log('Already initialized');
                return;
            }
            
            this.log('Initializing...');
            
            // Load polyfills for older browsers
            this.loadPolyfills();
            
            // Detect and disable legacy handlers
            this.detectLegacyHandlers();
            
            // Create compatibility shims
            this.createCompatibilityShims();
            
            // Setup event forwarding
            this.setupEventForwarding();
            
            this.initialized = true;
            this.log('Initialization complete');
        }
        
        /**
         * Load polyfills for older browsers
         */
        loadPolyfills() {
            if (this.polyfillsLoaded) {
                return;
            }
            
            this.log('Loading polyfills...');
            
            // Promise polyfill
            if (typeof Promise === 'undefined') {
                this.log('Promise not supported, loading polyfill');
                this.polyfillPromise();
            }
            
            // Fetch polyfill
            if (typeof fetch === 'undefined') {
                this.log('Fetch not supported, loading polyfill');
                this.polyfillFetch();
            }
            
            // Object.assign polyfill
            if (typeof Object.assign !== 'function') {
                this.log('Object.assign not supported, loading polyfill');
                this.polyfillObjectAssign();
            }
            
            // Array.from polyfill
            if (!Array.from) {
                this.log('Array.from not supported, loading polyfill');
                this.polyfillArrayFrom();
            }
            
            // Array.prototype.find polyfill
            if (!Array.prototype.find) {
                this.log('Array.prototype.find not supported, loading polyfill');
                this.polyfillArrayFind();
            }
            
            // CustomEvent polyfill
            if (typeof window.CustomEvent !== 'function') {
                this.log('CustomEvent not supported, loading polyfill');
                this.polyfillCustomEvent();
            }
            
            this.polyfillsLoaded = true;
            this.log('Polyfills loaded');
        }
        
        /**
         * Detect legacy handlers that might conflict
         */
        detectLegacyHandlers() {
            this.log('Detecting legacy handlers...');
            
            // Check for old global objects
            const legacyObjects = [
                'masFormHandler',
                'MASSettingsManager',
                'ModernAdminApp',
                'MASModuleLoader'
            ];
            
            for (const objName of legacyObjects) {
                if (window[objName]) {
                    this.log(`Found legacy object: ${objName}`);
                    this.legacyHandlers.push(objName);
                    
                    // Disable if possible
                    if (typeof window[objName].destroy === 'function') {
                        this.log(`Destroying legacy object: ${objName}`);
                        window[objName].destroy();
                    }
                    
                    // Mark as disabled
                    window[objName + '_DISABLED'] = true;
                }
            }
            
            // Check for jQuery handlers on form
            if (typeof jQuery !== 'undefined') {
                const $form = jQuery('#mas-v2-settings-form');
                if ($form.length) {
                    const events = jQuery._data($form[0], 'events');
                    if (events && events.submit) {
                        this.log(`Found ${events.submit.length} jQuery submit handlers`);
                        
                        // Remove all submit handlers
                        $form.off('submit');
                        this.log('Removed jQuery submit handlers');
                    }
                }
            }
        }
        
        /**
         * Create compatibility shims for legacy code
         */
        createCompatibilityShims() {
            this.log('Creating compatibility shims...');
            
            // Create shim for old AJAX handler
            if (!window.masLegacyAjax) {
                window.masLegacyAjax = {
                    saveSettings: (settings) => {
                        this.log('Legacy AJAX saveSettings called, forwarding to new API');
                        
                        if (window.MASAdminApp && window.MASAdminApp.apiClient) {
                            return window.MASAdminApp.apiClient.saveSettings(settings);
                        }
                        
                        // Fallback to jQuery AJAX
                        return this.fallbackAjaxSave(settings);
                    },
                    
                    getSettings: () => {
                        this.log('Legacy AJAX getSettings called, forwarding to new API');
                        
                        if (window.MASAdminApp && window.MASAdminApp.apiClient) {
                            return window.MASAdminApp.apiClient.getSettings();
                        }
                        
                        // Fallback to jQuery AJAX
                        return this.fallbackAjaxGet();
                    }
                };
            }
            
            // Create shim for old event system
            if (!window.masLegacyEvents) {
                window.masLegacyEvents = {
                    on: (event, callback) => {
                        this.log(`Legacy event listener registered: ${event}`);
                        
                        if (window.MASAdminApp && window.MASAdminApp.eventBus) {
                            return window.MASAdminApp.eventBus.on(event, callback);
                        }
                        
                        // Fallback to custom events
                        document.addEventListener('mas:' + event, callback);
                    },
                    
                    emit: (event, data) => {
                        this.log(`Legacy event emitted: ${event}`);
                        
                        if (window.MASAdminApp && window.MASAdminApp.eventBus) {
                            return window.MASAdminApp.eventBus.emit(event, data);
                        }
                        
                        // Fallback to custom events
                        const customEvent = new CustomEvent('mas:' + event, { detail: data });
                        document.dispatchEvent(customEvent);
                    }
                };
            }
        }
        
        /**
         * Setup event forwarding from legacy to new system
         */
        setupEventForwarding() {
            this.log('Setting up event forwarding...');
            
            // Forward jQuery events to new event bus
            if (typeof jQuery !== 'undefined' && window.MASAdminApp) {
                jQuery(document).on('mas:settings:changed', (e, data) => {
                    this.log('Forwarding jQuery event to new event bus');
                    window.MASAdminApp.eventBus.emit('settings:changed', data);
                });
            }
        }
        
        /**
         * Fallback AJAX save using jQuery
         */
        fallbackAjaxSave(settings) {
            this.log('Using fallback AJAX save');
            
            return new Promise((resolve, reject) => {
                if (typeof jQuery === 'undefined') {
                    reject(new Error('jQuery not available for fallback'));
                    return;
                }
                
                jQuery.ajax({
                    url: window.masV2Global?.ajaxUrl || ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mas_v2_save_settings',
                        nonce: window.masV2Global?.nonce,
                        settings: settings
                    },
                    success: (response) => {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(new Error(response.data?.message || 'Save failed'));
                        }
                    },
                    error: (xhr, status, error) => {
                        reject(new Error(error || 'Network error'));
                    }
                });
            });
        }
        
        /**
         * Fallback AJAX get using jQuery
         */
        fallbackAjaxGet() {
            this.log('Using fallback AJAX get');
            
            return new Promise((resolve, reject) => {
                if (typeof jQuery === 'undefined') {
                    reject(new Error('jQuery not available for fallback'));
                    return;
                }
                
                // Return cached settings if available
                if (window.masV2Global && window.masV2Global.settings) {
                    resolve(window.masV2Global.settings);
                    return;
                }
                
                reject(new Error('No settings available'));
            });
        }
        
        /**
         * Promise polyfill (simplified)
         */
        polyfillPromise() {
            // This is a very basic polyfill
            // In production, use a proper polyfill library
            if (typeof Promise === 'undefined') {
                window.Promise = function(executor) {
                    this.callbacks = [];
                    this.errbacks = [];
                    
                    const resolve = (value) => {
                        this.callbacks.forEach(cb => cb(value));
                    };
                    
                    const reject = (error) => {
                        this.errbacks.forEach(eb => eb(error));
                    };
                    
                    executor(resolve, reject);
                };
                
                window.Promise.prototype.then = function(callback) {
                    this.callbacks.push(callback);
                    return this;
                };
                
                window.Promise.prototype.catch = function(errback) {
                    this.errbacks.push(errback);
                    return this;
                };
            }
        }
        
        /**
         * Fetch polyfill (simplified)
         */
        polyfillFetch() {
            // Use jQuery AJAX as fallback
            if (typeof fetch === 'undefined' && typeof jQuery !== 'undefined') {
                window.fetch = function(url, options = {}) {
                    return new Promise((resolve, reject) => {
                        jQuery.ajax({
                            url: url,
                            type: options.method || 'GET',
                            data: options.body,
                            headers: options.headers,
                            success: (data, textStatus, xhr) => {
                                resolve({
                                    ok: xhr.status >= 200 && xhr.status < 300,
                                    status: xhr.status,
                                    json: () => Promise.resolve(data),
                                    text: () => Promise.resolve(JSON.stringify(data))
                                });
                            },
                            error: (xhr, status, error) => {
                                reject(new Error(error));
                            }
                        });
                    });
                };
            }
        }
        
        /**
         * Object.assign polyfill
         */
        polyfillObjectAssign() {
            Object.assign = function(target) {
                if (target === null || target === undefined) {
                    throw new TypeError('Cannot convert undefined or null to object');
                }
                
                const to = Object(target);
                
                for (let i = 1; i < arguments.length; i++) {
                    const nextSource = arguments[i];
                    
                    if (nextSource !== null && nextSource !== undefined) {
                        for (const key in nextSource) {
                            if (Object.prototype.hasOwnProperty.call(nextSource, key)) {
                                to[key] = nextSource[key];
                            }
                        }
                    }
                }
                
                return to;
            };
        }
        
        /**
         * Array.from polyfill
         */
        polyfillArrayFrom() {
            Array.from = function(arrayLike) {
                return Array.prototype.slice.call(arrayLike);
            };
        }
        
        /**
         * Array.prototype.find polyfill
         */
        polyfillArrayFind() {
            Array.prototype.find = function(predicate) {
                if (this === null) {
                    throw new TypeError('Array.prototype.find called on null or undefined');
                }
                if (typeof predicate !== 'function') {
                    throw new TypeError('predicate must be a function');
                }
                
                const list = Object(this);
                const length = list.length >>> 0;
                const thisArg = arguments[1];
                
                for (let i = 0; i < length; i++) {
                    const value = list[i];
                    if (predicate.call(thisArg, value, i, list)) {
                        return value;
                    }
                }
                
                return undefined;
            };
        }
        
        /**
         * CustomEvent polyfill
         */
        polyfillCustomEvent() {
            function CustomEvent(event, params) {
                params = params || { bubbles: false, cancelable: false, detail: null };
                const evt = document.createEvent('CustomEvent');
                evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
                return evt;
            }
            
            CustomEvent.prototype = window.Event.prototype;
            window.CustomEvent = CustomEvent;
        }
        
        /**
         * Check if browser is supported
         */
        isBrowserSupported() {
            // Check for minimum required features
            const requiredFeatures = [
                'querySelector',
                'addEventListener',
                'JSON'
            ];
            
            for (const feature of requiredFeatures) {
                if (typeof window[feature] === 'undefined' && typeof document[feature] === 'undefined') {
                    return false;
                }
            }
            
            return true;
        }
        
        /**
         * Get browser info
         */
        getBrowserInfo() {
            const ua = navigator.userAgent;
            let browser = 'Unknown';
            let version = 'Unknown';
            
            if (ua.indexOf('Chrome') > -1) {
                browser = 'Chrome';
                version = ua.match(/Chrome\/(\d+)/)[1];
            } else if (ua.indexOf('Firefox') > -1) {
                browser = 'Firefox';
                version = ua.match(/Firefox\/(\d+)/)[1];
            } else if (ua.indexOf('Safari') > -1) {
                browser = 'Safari';
                version = ua.match(/Version\/(\d+)/)[1];
            } else if (ua.indexOf('MSIE') > -1 || ua.indexOf('Trident') > -1) {
                browser = 'IE';
                version = ua.match(/(?:MSIE |rv:)(\d+)/)[1];
            }
            
            return { browser, version };
        }
        
        /**
         * Log message
         */
        log(...args) {
            if (window.masV2Global && window.masV2Global.debug_mode) {
                console.log('[LegacyBridge]', ...args);
            }
        }
        
        /**
         * Destroy and cleanup
         */
        destroy() {
            this.log('Destroying...');
            
            // Remove shims
            delete window.masLegacyAjax;
            delete window.masLegacyEvents;
            
            this.initialized = false;
            this.log('Destroyed');
        }
    }
    
    // Create global instance
    window.MASLegacyBridge = new LegacyBridge();
    
    // Auto-initialize if new frontend is active
    if (window.MASUseNewFrontend) {
        document.addEventListener('DOMContentLoaded', function() {
            window.MASLegacyBridge.init();
        });
    }
    
})(window, document);
