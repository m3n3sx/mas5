/**
 * Preview Manager Module
 * 
 * Handles live preview functionality with debouncing, CSS injection,
 * and request cancellation for rapid changes.
 * 
 * @package ModernAdminStylerV2
 * @since 2.2.0
 */

(function(window) {
    'use strict';
    
    /**
     * Preview Manager Class
     * 
     * Manages live preview CSS generation and injection with debouncing
     * and request cancellation.
     */
    class PreviewManager {
        /**
         * Constructor
         * 
         * @param {MASRestClient} restClient REST API client instance
         * @param {Object} options Configuration options
         */
        constructor(restClient, options = {}) {
            if (!restClient) {
                throw new Error('PreviewManager requires a REST client instance');
            }
            
            this.restClient = restClient;
            
            // Configuration
            this.debounceDelay = options.debounceDelay || 500; // 500ms default
            this.styleElementId = options.styleElementId || 'mas-preview-styles';
            this.debug = options.debug || false;
            
            // State
            this.debounceTimer = null;
            this.currentAbortController = null;
            this.isPreviewActive = false;
            this.lastSettings = null;
            
            // Statistics
            this.stats = {
                requestCount: 0,
                cancelledCount: 0,
                errorCount: 0,
                lastUpdateTime: null
            };
            
            // Initialize
            this.init();
        }
        
        /**
         * Initialize preview manager
         */
        init() {
            // Create style element if it doesn't exist
            this.ensureStyleElement();
            
            if (this.debug) {
                console.log('[Preview Manager] Initialized', {
                    debounceDelay: this.debounceDelay,
                    styleElementId: this.styleElementId
                });
            }
        }
        
        /**
         * Ensure preview style element exists
         */
        ensureStyleElement() {
            let styleElement = document.getElementById(this.styleElementId);
            
            if (!styleElement) {
                styleElement = document.createElement('style');
                styleElement.id = this.styleElementId;
                styleElement.setAttribute('data-preview', 'true');
                document.head.appendChild(styleElement);
                
                if (this.debug) {
                    console.log('[Preview Manager] Created style element');
                }
            }
            
            return styleElement;
        }
        
        /**
         * Update preview with debouncing
         * 
         * @param {Object} settings Settings to preview
         * @returns {Promise<void>}
         */
        updatePreview(settings) {
            // Cancel existing timer
            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
                
                if (this.debug) {
                    console.log('[Preview Manager] Debounce timer cleared');
                }
            }
            
            // Cancel existing request
            if (this.currentAbortController) {
                this.currentAbortController.abort();
                this.stats.cancelledCount++;
                
                if (this.debug) {
                    console.log('[Preview Manager] Previous request cancelled');
                }
            }
            
            // Store settings for comparison
            this.lastSettings = settings;
            
            // Set new timer
            return new Promise((resolve, reject) => {
                this.debounceTimer = setTimeout(async () => {
                    try {
                        await this.generateAndApplyPreview(settings);
                        resolve();
                    } catch (error) {
                        reject(error);
                    }
                }, this.debounceDelay);
                
                if (this.debug) {
                    console.log('[Preview Manager] Debounce timer set', {
                        delay: this.debounceDelay
                    });
                }
            });
        }
        
        /**
         * Generate and apply preview CSS
         * 
         * @param {Object} settings Settings to preview
         * @returns {Promise<Object>} Preview response
         */
        async generateAndApplyPreview(settings) {
            // Create abort controller for this request
            this.currentAbortController = new AbortController();
            
            try {
                this.isPreviewActive = true;
                this.stats.requestCount++;
                
                if (this.debug) {
                    console.log('[Preview Manager] Generating preview', {
                        settingsCount: Object.keys(settings).length
                    });
                }
                
                // Generate preview CSS
                const startTime = performance.now();
                const response = await this.restClient.generatePreview(settings);
                const endTime = performance.now();
                
                // Check if request was aborted
                if (this.currentAbortController.signal.aborted) {
                    if (this.debug) {
                        console.log('[Preview Manager] Request was aborted');
                    }
                    return null;
                }
                
                // Apply CSS
                if (response && response.css) {
                    this.applyPreviewCSS(response.css);
                    
                    // Update statistics
                    this.stats.lastUpdateTime = Date.now();
                    
                    if (this.debug) {
                        console.log('[Preview Manager] Preview applied', {
                            cssLength: response.css.length,
                            duration: Math.round(endTime - startTime) + 'ms',
                            fallback: response.fallback || false
                        });
                    }
                    
                    // Dispatch custom event
                    this.dispatchPreviewEvent('preview-updated', {
                        settings,
                        css: response.css,
                        duration: endTime - startTime,
                        fallback: response.fallback || false
                    });
                }
                
                return response;
                
            } catch (error) {
                this.stats.errorCount++;
                
                if (this.debug) {
                    console.error('[Preview Manager] Preview error', error);
                }
                
                // Dispatch error event
                this.dispatchPreviewEvent('preview-error', {
                    settings,
                    error: error.message || 'Unknown error'
                });
                
                // Don't throw error for rate limiting (expected behavior)
                if (error.code === 'rate_limited') {
                    if (this.debug) {
                        console.log('[Preview Manager] Rate limited, will retry');
                    }
                    return null;
                }
                
                throw error;
                
            } finally {
                this.isPreviewActive = false;
                this.currentAbortController = null;
            }
        }
        
        /**
         * Apply preview CSS to the page
         * 
         * @param {string} css CSS to apply
         */
        applyPreviewCSS(css) {
            const styleElement = this.ensureStyleElement();
            styleElement.textContent = css;
            
            if (this.debug) {
                console.log('[Preview Manager] CSS injected', {
                    length: css.length
                });
            }
        }
        
        /**
         * Clear preview CSS
         */
        clearPreview() {
            const styleElement = document.getElementById(this.styleElementId);
            
            if (styleElement) {
                styleElement.textContent = '';
                
                if (this.debug) {
                    console.log('[Preview Manager] Preview cleared');
                }
            }
            
            // Cancel any pending requests
            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
                this.debounceTimer = null;
            }
            
            if (this.currentAbortController) {
                this.currentAbortController.abort();
                this.currentAbortController = null;
            }
            
            this.isPreviewActive = false;
            this.lastSettings = null;
            
            // Dispatch event
            this.dispatchPreviewEvent('preview-cleared');
        }
        
        /**
         * Cancel current preview request
         */
        cancelPreview() {
            if (this.currentAbortController) {
                this.currentAbortController.abort();
                this.stats.cancelledCount++;
                
                if (this.debug) {
                    console.log('[Preview Manager] Preview cancelled');
                }
            }
            
            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
                this.debounceTimer = null;
            }
            
            this.isPreviewActive = false;
        }
        
        /**
         * Check if preview is currently active
         * 
         * @returns {boolean} True if preview is active
         */
        isActive() {
            return this.isPreviewActive;
        }
        
        /**
         * Get preview statistics
         * 
         * @returns {Object} Statistics object
         */
        getStats() {
            return {
                ...this.stats,
                isActive: this.isPreviewActive,
                hasPendingRequest: !!this.currentAbortController
            };
        }
        
        /**
         * Reset statistics
         */
        resetStats() {
            this.stats = {
                requestCount: 0,
                cancelledCount: 0,
                errorCount: 0,
                lastUpdateTime: null
            };
            
            if (this.debug) {
                console.log('[Preview Manager] Statistics reset');
            }
        }
        
        /**
         * Set debounce delay
         * 
         * @param {number} delay Delay in milliseconds
         */
        setDebounceDelay(delay) {
            if (typeof delay === 'number' && delay >= 0) {
                this.debounceDelay = delay;
                
                if (this.debug) {
                    console.log('[Preview Manager] Debounce delay updated', {
                        delay
                    });
                }
            }
        }
        
        /**
         * Get current debounce delay
         * 
         * @returns {number} Delay in milliseconds
         */
        getDebounceDelay() {
            return this.debounceDelay;
        }
        
        /**
         * Dispatch custom preview event
         * 
         * @param {string} eventName Event name
         * @param {Object} detail Event detail data
         */
        dispatchPreviewEvent(eventName, detail = {}) {
            const event = new CustomEvent(`mas-${eventName}`, {
                detail: {
                    ...detail,
                    timestamp: Date.now()
                },
                bubbles: true,
                cancelable: true
            });
            
            document.dispatchEvent(event);
            
            if (this.debug) {
                console.log(`[Preview Manager] Event dispatched: ${eventName}`, detail);
            }
        }
        
        /**
         * Destroy preview manager
         */
        destroy() {
            // Clear preview
            this.clearPreview();
            
            // Remove style element
            const styleElement = document.getElementById(this.styleElementId);
            if (styleElement) {
                styleElement.remove();
            }
            
            // Reset state
            this.restClient = null;
            this.lastSettings = null;
            
            if (this.debug) {
                console.log('[Preview Manager] Destroyed');
            }
        }
    }
    
    // Export to global scope
    window.PreviewManager = PreviewManager;
    
    // Export as module if available
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = PreviewManager;
    }
    
})(window);
