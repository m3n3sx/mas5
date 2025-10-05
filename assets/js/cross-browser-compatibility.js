/**
 * Modern Admin Styler V2 - Cross-Browser Compatibility & Feature Detection
 * Task 16: Cross-browser Compatibility and Testing
 * 
 * This script detects browser capabilities and applies appropriate fallbacks
 * for unsupported features, ensuring the plugin works across all browsers.
 */

(function() {
    'use strict';
    
    // Browser and feature detection results
    const browserSupport = {
        cssVariables: false,
        backdropFilter: false,
        transforms: false,
        transitions: false,
        flexbox: false,
        grid: false,
        es6: false,
        promises: false,
        fetch: false,
        intersectionObserver: false,
        resizeObserver: false
    };
    
    // Browser information
    const browserInfo = {
        name: 'unknown',
        version: 0,
        engine: 'unknown',
        isIE: false,
        isEdgeLegacy: false,
        isSafari: false,
        isFirefox: false,
        isChrome: false,
        isMobile: false,
        isTouch: false
    };
    
    // Initialize compatibility system
    function init() {
        console.log('🌐 MAS Cross-Browser Compatibility: Initializing...');
        
        try {
            detectBrowser();
            detectFeatures();
            applyCompatibilityClasses();
            setupFallbacks();
            setupPolyfills();
            
            console.log('✅ MAS Cross-Browser Compatibility: Initialized successfully');
            console.log('📊 Browser Support:', browserSupport);
            console.log('🔍 Browser Info:', browserInfo);
            
            // Dispatch ready event
            document.dispatchEvent(new CustomEvent('mas-compatibility-ready', {
                detail: { browserSupport, browserInfo }
            }));
            
        } catch (error) {
            console.error('❌ MAS Cross-Browser Compatibility: Initialization failed:', error);
            enableEmergencyMode();
        }
    }
    
    // Detect browser type and version
    function detectBrowser() {
        const userAgent = navigator.userAgent;
        const vendor = navigator.vendor || '';
        
        // Detect Internet Explorer
        if (userAgent.indexOf('MSIE') !== -1 || userAgent.indexOf('Trident/') !== -1) {
            browserInfo.name = 'Internet Explorer';
            browserInfo.isIE = true;
            browserInfo.engine = 'Trident';
            
            const ieVersion = userAgent.match(/(?:MSIE |rv:)(\d+(\.\d+)?)/);
            if (ieVersion) {
                browserInfo.version = parseFloat(ieVersion[1]);
            }
        }
        // Detect Edge Legacy
        else if (userAgent.indexOf('Edge/') !== -1) {
            browserInfo.name = 'Edge Legacy';
            browserInfo.isEdgeLegacy = true;
            browserInfo.engine = 'EdgeHTML';
            
            const edgeVersion = userAgent.match(/Edge\/(\d+(\.\d+)?)/);
            if (edgeVersion) {
                browserInfo.version = parseFloat(edgeVersion[1]);
            }
        }
        // Detect Chrome
        else if (userAgent.indexOf('Chrome') !== -1 && vendor.indexOf('Google') !== -1) {
            browserInfo.name = 'Chrome';
            browserInfo.isChrome = true;
            browserInfo.engine = 'Blink';
            
            const chromeVersion = userAgent.match(/Chrome\/(\d+(\.\d+)?)/);
            if (chromeVersion) {
                browserInfo.version = parseFloat(chromeVersion[1]);
            }
        }
        // Detect Safari
        else if (userAgent.indexOf('Safari') !== -1 && vendor.indexOf('Apple') !== -1) {
            browserInfo.name = 'Safari';
            browserInfo.isSafari = true;
            browserInfo.engine = 'WebKit';
            
            const safariVersion = userAgent.match(/Version\/(\d+(\.\d+)?)/);
            if (safariVersion) {
                browserInfo.version = parseFloat(safariVersion[1]);
            }
        }
        // Detect Firefox
        else if (userAgent.indexOf('Firefox') !== -1) {
            browserInfo.name = 'Firefox';
            browserInfo.isFirefox = true;
            browserInfo.engine = 'Gecko';
            
            const firefoxVersion = userAgent.match(/Firefox\/(\d+(\.\d+)?)/);
            if (firefoxVersion) {
                browserInfo.version = parseFloat(firefoxVersion[1]);
            }
        }
        
        // Detect mobile devices
        browserInfo.isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(userAgent);
        
        // Detect touch capability
        browserInfo.isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        
        console.log(`🔍 Detected browser: ${browserInfo.name} ${browserInfo.version} (${browserInfo.engine})`);
    }
    
    // Detect CSS and JavaScript feature support
    function detectFeatures() {
        // Test CSS Variables support
        browserSupport.cssVariables = CSS.supports('color', 'var(--test-var)');
        
        // Test backdrop-filter support
        browserSupport.backdropFilter = CSS.supports('backdrop-filter', 'blur(1px)') || 
                                       CSS.supports('-webkit-backdrop-filter', 'blur(1px)');
        
        // Test CSS transforms support
        browserSupport.transforms = CSS.supports('transform', 'translateX(0px)');
        
        // Test CSS transitions support
        browserSupport.transitions = CSS.supports('transition', 'all 0.3s ease');
        
        // Test flexbox support
        browserSupport.flexbox = CSS.supports('display', 'flex');
        
        // Test CSS Grid support
        browserSupport.grid = CSS.supports('display', 'grid');
        
        // Test ES6 support
        try {
            eval('const test = () => {}; let x = 1;');
            browserSupport.es6 = true;
        } catch (e) {
            browserSupport.es6 = false;
        }
        
        // Test Promises support
        browserSupport.promises = typeof Promise !== 'undefined';
        
        // Test Fetch API support
        browserSupport.fetch = typeof fetch !== 'undefined';
        
        // Test Intersection Observer support
        browserSupport.intersectionObserver = typeof IntersectionObserver !== 'undefined';
        
        // Test Resize Observer support
        browserSupport.resizeObserver = typeof ResizeObserver !== 'undefined';
        
        console.log('🔧 Feature detection completed');
    }
    
    // Apply CSS classes based on feature support
    function applyCompatibilityClasses() {
        const html = document.documentElement;
        const body = document.body;
        
        // Add browser-specific classes
        html.classList.add(`mas-browser-${browserInfo.name.toLowerCase().replace(/\s+/g, '-')}`);
        html.classList.add(`mas-engine-${browserInfo.engine.toLowerCase()}`);
        
        if (browserInfo.isMobile) {
            html.classList.add('mas-mobile');
        }
        
        if (browserInfo.isTouch) {
            html.classList.add('mas-touch');
        }
        
        // Add feature support classes
        Object.keys(browserSupport).forEach(feature => {
            const className = browserSupport[feature] ? `mas-${feature}` : `mas-no-${feature}`;
            html.classList.add(className);
        });
        
        // Add legacy browser classes for specific fixes
        if (browserInfo.isIE) {
            html.classList.add('mas-ie');
            if (browserInfo.version <= 11) {
                html.classList.add('mas-ie11-or-lower');
            }
        }
        
        if (browserInfo.isEdgeLegacy) {
            html.classList.add('mas-edge-legacy');
        }
        
        // Add version-specific classes for major browsers
        if (browserInfo.isChrome && browserInfo.version < 60) {
            html.classList.add('mas-chrome-old');
        }
        
        if (browserInfo.isSafari && browserInfo.version < 12) {
            html.classList.add('mas-safari-old');
        }
        
        if (browserInfo.isFirefox && browserInfo.version < 60) {
            html.classList.add('mas-firefox-old');
        }
        
        console.log('🎨 Applied compatibility CSS classes');
    }
    
    // Setup fallbacks for unsupported features
    function setupFallbacks() {
        // CSS Variables fallback
        if (!browserSupport.cssVariables) {
            setupCSSVariablesFallback();
        }
        
        // Backdrop filter fallback
        if (!browserSupport.backdropFilter) {
            setupBackdropFilterFallback();
        }
        
        // Flexbox fallback
        if (!browserSupport.flexbox) {
            setupFlexboxFallback();
        }
        
        // CSS Grid fallback
        if (!browserSupport.grid) {
            setupGridFallback();
        }
        
        // Transform fallback
        if (!browserSupport.transforms) {
            setupTransformFallback();
        }
        
        // Transition fallback
        if (!browserSupport.transitions) {
            setupTransitionFallback();
        }
        
        console.log('🔧 Fallbacks configured');
    }
    
    // CSS Variables fallback using JavaScript
    function setupCSSVariablesFallback() {
        console.log('🔧 Setting up CSS Variables fallback...');
        
        // Create a simple CSS variables polyfill
        window.MASCSSVariables = {
            variables: new Map(),
            
            setProperty: function(property, value) {
                this.variables.set(property, value);
                this.updateStyles();
            },
            
            getProperty: function(property) {
                return this.variables.get(property);
            },
            
            updateStyles: function() {
                // Apply variables to elements that need them
                const elements = document.querySelectorAll('[data-mas-css-var]');
                elements.forEach(element => {
                    const varName = element.getAttribute('data-mas-css-var');
                    const value = this.variables.get(varName);
                    if (value) {
                        element.style.setProperty(element.getAttribute('data-mas-css-prop'), value);
                    }
                });
            }
        };
        
        // Apply basic fallback styles
        const fallbackStyles = `
            body.wp-admin #adminmenu { background: #23282d !important; }
            body.wp-admin #adminmenu li.menu-top > a { color: #eee !important; }
            body.wp-admin #adminmenu li.menu-top:hover > a { background: rgba(255,255,255,0.1) !important; }
            body.wp-admin #adminmenu .wp-submenu { background: rgba(0,0,0,0.2) !important; }
        `;
        
        injectCSS(fallbackStyles, 'mas-css-variables-fallback');
    }
    
    // Backdrop filter fallback
    function setupBackdropFilterFallback() {
        console.log('🔧 Setting up backdrop-filter fallback...');
        
        const fallbackStyles = `
            .mas-glassmorphism-enabled .postbox,
            .mas-glassmorphism-enabled .meta-box-sortables .postbox,
            .mas-glassmorphism-enabled .mas-v2-card {
                background: rgba(255, 255, 255, 0.9) !important;
                border: 1px solid rgba(0, 0, 0, 0.1) !important;
            }
            
            .mas-menu-glassmorphism-enabled #adminmenuwrap {
                background: rgba(35, 40, 45, 0.95) !important;
            }
        `;
        
        injectCSS(fallbackStyles, 'mas-backdrop-filter-fallback');
    }
    
    // Flexbox fallback
    function setupFlexboxFallback() {
        console.log('🔧 Setting up flexbox fallback...');
        
        const fallbackStyles = `
            .mas-flex-container { display: block !important; }
            .mas-flex-item { 
                display: inline-block !important; 
                vertical-align: top !important; 
                width: 48% !important; 
                margin: 1% !important; 
            }
        `;
        
        injectCSS(fallbackStyles, 'mas-flexbox-fallback');
    }
    
    // CSS Grid fallback
    function setupGridFallback() {
        console.log('🔧 Setting up CSS Grid fallback...');
        
        const fallbackStyles = `
            .mas-settings-grid { 
                display: block !important; 
            }
            .mas-settings-grid > * { 
                display: inline-block !important; 
                width: 48% !important; 
                margin: 1% !important; 
                vertical-align: top !important; 
            }
        `;
        
        injectCSS(fallbackStyles, 'mas-grid-fallback');
    }
    
    // Transform fallback
    function setupTransformFallback() {
        console.log('🔧 Setting up transform fallback...');
        
        const fallbackStyles = `
            body.wp-admin #adminmenu li.menu-top:hover { margin-left: 2px !important; }
            body.wp-admin #adminmenu .wp-submenu li a:hover { margin-left: 4px !important; }
        `;
        
        injectCSS(fallbackStyles, 'mas-transform-fallback');
    }
    
    // Transition fallback
    function setupTransitionFallback() {
        console.log('🔧 Setting up transition fallback...');
        
        // Simply disable all transitions
        const fallbackStyles = `
            * { transition: none !important; animation: none !important; }
        `;
        
        injectCSS(fallbackStyles, 'mas-transition-fallback');
    }
    
    // Setup polyfills for missing JavaScript features
    function setupPolyfills() {
        // Promises polyfill
        if (!browserSupport.promises) {
            loadPolyfill('https://cdn.jsdelivr.net/npm/es6-promise@4/dist/es6-promise.auto.min.js');
        }
        
        // Fetch polyfill
        if (!browserSupport.fetch) {
            loadPolyfill('https://cdn.jsdelivr.net/npm/whatwg-fetch@3/dist/fetch.umd.js');
        }
        
        // Intersection Observer polyfill
        if (!browserSupport.intersectionObserver) {
            loadPolyfill('https://cdn.jsdelivr.net/npm/intersection-observer@0.12.0/intersection-observer.js');
        }
        
        // Custom polyfills for specific browsers
        if (browserInfo.isIE) {
            setupIEPolyfills();
        }
        
        console.log('📦 Polyfills configured');
    }
    
    // IE-specific polyfills
    function setupIEPolyfills() {
        console.log('🔧 Setting up IE polyfills...');
        
        // Array.from polyfill
        if (!Array.from) {
            Array.from = function(arrayLike) {
                return Array.prototype.slice.call(arrayLike);
            };
        }
        
        // Object.assign polyfill
        if (!Object.assign) {
            Object.assign = function(target) {
                for (var i = 1; i < arguments.length; i++) {
                    var source = arguments[i];
                    for (var key in source) {
                        if (source.hasOwnProperty(key)) {
                            target[key] = source[key];
                        }
                    }
                }
                return target;
            };
        }
        
        // CustomEvent polyfill
        if (typeof CustomEvent !== 'function') {
            function CustomEvent(event, params) {
                params = params || { bubbles: false, cancelable: false, detail: undefined };
                var evt = document.createEvent('CustomEvent');
                evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
                return evt;
            }
            CustomEvent.prototype = window.Event.prototype;
            window.CustomEvent = CustomEvent;
        }
        
        // classList polyfill for IE9
        if (!('classList' in document.documentElement)) {
            Object.defineProperty(HTMLElement.prototype, 'classList', {
                get: function() {
                    var self = this;
                    function update(fn) {
                        return function(value) {
                            var classes = self.className.split(/\s+/);
                            var index = classes.indexOf(value);
                            fn(classes, index, value);
                            self.className = classes.join(' ');
                        };
                    }
                    
                    return {
                        add: update(function(classes, index, value) {
                            if (index < 0) classes.push(value);
                        }),
                        remove: update(function(classes, index) {
                            if (index >= 0) classes.splice(index, 1);
                        }),
                        toggle: update(function(classes, index, value) {
                            if (index >= 0) classes.splice(index, 1);
                            else classes.push(value);
                        }),
                        contains: function(value) {
                            return self.className.split(/\s+/).indexOf(value) >= 0;
                        }
                    };
                }
            });
        }
    }
    
    // Load external polyfill
    function loadPolyfill(url) {
        const script = document.createElement('script');
        script.src = url;
        script.async = true;
        script.onload = function() {
            console.log(`📦 Polyfill loaded: ${url}`);
        };
        script.onerror = function() {
            console.warn(`⚠️ Failed to load polyfill: ${url}`);
        };
        document.head.appendChild(script);
    }
    
    // Inject CSS styles
    function injectCSS(css, id) {
        let style = document.getElementById(id);
        if (!style) {
            style = document.createElement('style');
            style.id = id;
            document.head.appendChild(style);
        }
        style.textContent = css;
    }
    
    // Enable emergency mode for critical failures
    function enableEmergencyMode() {
        console.error('🚨 Enabling emergency compatibility mode');
        
        document.documentElement.classList.add('mas-emergency-mode');
        document.body.classList.add('mas-emergency-mode');
        
        // Apply emergency styles
        const emergencyStyles = `
            body.wp-admin #adminmenu {
                position: static !important;
                background: #23282d !important;
                color: #eee !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                transform: none !important;
                transition: none !important;
                animation: none !important;
            }
            
            body.wp-admin #adminmenu li.menu-top > a {
                color: #eee !important;
                background: transparent !important;
                padding: 8px 12px !important;
                border-radius: 0 !important;
            }
            
            body.wp-admin #adminmenu li.menu-top:hover > a {
                background: rgba(255, 255, 255, 0.1) !important;
                color: #fff !important;
            }
            
            body.wp-admin #adminmenu .wp-submenu {
                position: static !important;
                background: rgba(0, 0, 0, 0.2) !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                transform: none !important;
                opacity: 1 !important;
                display: block !important;
            }
            
            * {
                transition: none !important;
                animation: none !important;
                transform: none !important;
                backdrop-filter: none !important;
                -webkit-backdrop-filter: none !important;
            }
        `;
        
        injectCSS(emergencyStyles, 'mas-emergency-mode');
        
        // Dispatch emergency mode event
        document.dispatchEvent(new CustomEvent('mas-emergency-mode-enabled'));
    }
    
    // Public API
    window.MASCompatibility = {
        browserSupport,
        browserInfo,
        init,
        detectBrowser,
        detectFeatures,
        applyCompatibilityClasses,
        setupFallbacks,
        setupPolyfills,
        enableEmergencyMode,
        injectCSS,
        
        // Utility functions
        isSupported: function(feature) {
            return browserSupport[feature] || false;
        },
        
        getBrowserInfo: function() {
            return Object.assign({}, browserInfo);
        },
        
        getSupportInfo: function() {
            return Object.assign({}, browserSupport);
        },
        
        // Test specific features
        testFeature: function(property, value) {
            try {
                return CSS.supports(property, value);
            } catch (e) {
                return false;
            }
        },
        
        // Apply browser-specific fixes
        applyBrowserFix: function(browserName, css) {
            if (browserInfo.name.toLowerCase().includes(browserName.toLowerCase())) {
                injectCSS(css, `mas-fix-${browserName.toLowerCase()}`);
            }
        }
    };
    
    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})();