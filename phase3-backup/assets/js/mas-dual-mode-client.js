/**
 * Modern Admin Styler V2 - Dual Mode Client
 * 
 * Provides backward compatibility by attempting REST API first,
 * then falling back to AJAX if REST API is unavailable or fails.
 * Ensures no duplicate operations occur during dual-mode operation.
 * 
 * @package ModernAdminStylerV2
 * @since 2.2.0
 */

(function(window, $) {
    'use strict';
    
    /**
     * MAS Dual Mode Client
     * 
     * Intelligently switches between REST API and AJAX based on availability
     * 
     * @class
     */
    class MASDualModeClient {
        /**
         * Constructor
         * 
         * @param {Object} config Configuration object
         */
        constructor(config = {}) {
            this.config = config;
            this.debug = config.debug || false;
            
            // Initialize REST client if available
            this.restClient = null;
            this.useRest = this.initRestClient();
            
            // AJAX configuration
            this.ajaxUrl = config.ajaxUrl || (window.ajaxurl || '/wp-admin/admin-ajax.php');
            this.ajaxNonce = config.ajaxNonce || (window.masV2Global && window.masV2Global.nonce) || '';
            
            // Operation lock to prevent duplicates
            this.operationLock = new Set();
            
            // Request deduplication cache
            this.requestCache = new Map();
            this.cacheTimeout = config.cacheTimeout || 60000; // 1 minute
            
            // Statistics
            this.stats = {
                restSuccess: 0,
                restFailed: 0,
                ajaxSuccess: 0,
                ajaxFailed: 0,
                cacheHits: 0,
                duplicatesPrevented: 0
            };
            
            if (this.debug) {
                console.log('[MAS Dual Mode] Initialized', {
                    useRest: this.useRest,
                    hasAjax: !!this.ajaxUrl && !!this.ajaxNonce
                });
            }
        }
        
        /**
         * Initialize REST client
         * 
         * @returns {boolean} True if REST is available
         */
        initRestClient() {
            // Check if REST API is available
            if (!this.isRestAvailable()) {
                if (this.debug) {
                    console.log('[MAS Dual Mode] REST API not available, using AJAX only');
                }
                return false;
            }
            
            // Create REST client
            try {
                this.restClient = new window.MASRestClient({
                    debug: this.debug
                });
                
                if (this.debug) {
                    console.log('[MAS Dual Mode] REST client initialized');
                }
                
                return true;
            } catch (error) {
                if (this.debug) {
                    console.error('[MAS Dual Mode] Failed to initialize REST client', error);
                }
                return false;
            }
        }
        
        /**
         * Check if REST API is available
         * 
         * @returns {boolean} True if available
         */
        isRestAvailable() {
            return !!(
                window.wpApiSettings &&
                window.wpApiSettings.root &&
                window.wpApiSettings.nonce &&
                window.MASRestClient
            );
        }
        
        /**
         * Acquire operation lock
         * 
         * @param {string} operation Operation identifier
         * @returns {boolean} True if lock acquired
         */
        acquireLock(operation) {
            if (this.operationLock.has(operation)) {
                if (this.debug) {
                    console.warn('[MAS Dual Mode] Operation already in progress:', operation);
                }
                return false;
            }
            
            this.operationLock.add(operation);
            return true;
        }
        
        /**
         * Release operation lock
         * 
         * @param {string} operation Operation identifier
         */
        releaseLock(operation) {
            this.operationLock.delete(operation);
        }
        
        /**
         * Generate request fingerprint for deduplication
         * 
         * @param {string} operation Operation name
         * @param {Object} data Request data
         * @returns {string} Fingerprint
         */
        generateFingerprint(operation, data = {}) {
            const normalizedData = this.normalizeRequestData(data);
            const fingerprintData = {
                operation,
                data: normalizedData,
                timestamp: Math.floor(Date.now() / 10000) // 10-second window
            };
            
            return btoa(JSON.stringify(fingerprintData)).replace(/[^a-zA-Z0-9]/g, '');
        }
        
        /**
         * Normalize request data for consistent fingerprinting
         * 
         * @param {Object} data Request data
         * @returns {Object} Normalized data
         */
        normalizeRequestData(data) {
            if (!data || typeof data !== 'object') {
                return data;
            }
            
            const normalized = { ...data };
            
            // Remove non-essential fields
            const excludeFields = ['_wpnonce', '_wp_http_referer', 'timestamp', 'request_id'];
            excludeFields.forEach(field => delete normalized[field]);
            
            // Sort keys for consistent ordering
            const sortedKeys = Object.keys(normalized).sort();
            const sortedData = {};
            sortedKeys.forEach(key => {
                sortedData[key] = normalized[key];
            });
            
            return sortedData;
        }
        
        /**
         * Get cached result
         * 
         * @param {string} fingerprint Request fingerprint
         * @returns {Object|null} Cached result or null
         */
        getCachedResult(fingerprint) {
            const cached = this.requestCache.get(fingerprint);
            
            if (!cached) {
                return null;
            }
            
            // Check if cache is expired
            if (Date.now() - cached.timestamp > this.cacheTimeout) {
                this.requestCache.delete(fingerprint);
                return null;
            }
            
            this.stats.cacheHits++;
            
            if (this.debug) {
                console.log('[MAS Dual Mode] Cache hit:', fingerprint);
            }
            
            return cached.result;
        }
        
        /**
         * Cache result
         * 
         * @param {string} fingerprint Request fingerprint
         * @param {Object} result Result to cache
         */
        cacheResult(fingerprint, result) {
            this.requestCache.set(fingerprint, {
                result: result,
                timestamp: Date.now()
            });
            
            // Clean up old cache entries
            this.cleanupCache();
        }
        
        /**
         * Clean up expired cache entries
         */
        cleanupCache() {
            const now = Date.now();
            
            for (const [fingerprint, cached] of this.requestCache.entries()) {
                if (now - cached.timestamp > this.cacheTimeout) {
                    this.requestCache.delete(fingerprint);
                }
            }
        }
        
        /**
         * Execute deduplicated request
         * 
         * @param {string} operation Operation name
         * @param {Object} data Request data
         * @param {Function} restFn REST API function
         * @param {Function} ajaxFn AJAX fallback function
         * @returns {Promise<Object>} Operation result
         */
        async executeDeduplicatedRequest(operation, data, restFn, ajaxFn) {
            const fingerprint = this.generateFingerprint(operation, data);
            
            // Check cache first
            const cachedResult = this.getCachedResult(fingerprint);
            if (cachedResult) {
                return cachedResult;
            }
            
            // Check if operation is already in progress
            if (!this.acquireLock(fingerprint)) {
                this.stats.duplicatesPrevented++;
                
                // Wait for operation to complete
                return this.waitForOperation(fingerprint);
            }
            
            try {
                const result = await this.executeWithFallback(operation, restFn, ajaxFn);
                
                // Cache the result
                this.cacheResult(fingerprint, result);
                
                return result;
            } finally {
                this.releaseLock(fingerprint);
            }
        }
        
        /**
         * Wait for operation to complete
         * 
         * @param {string} fingerprint Operation fingerprint
         * @param {number} maxWaitTime Maximum wait time in ms
         * @returns {Promise<Object>} Operation result
         */
        async waitForOperation(fingerprint, maxWaitTime = 30000) {
            const startTime = Date.now();
            const checkInterval = 500; // 500ms
            
            return new Promise((resolve, reject) => {
                const checkResult = () => {
                    const cachedResult = this.getCachedResult(fingerprint);
                    
                    if (cachedResult) {
                        if (this.debug) {
                            console.log('[MAS Dual Mode] Wait successful:', fingerprint);
                        }
                        resolve(cachedResult);
                        return;
                    }
                    
                    // Check timeout
                    if (Date.now() - startTime > maxWaitTime) {
                        reject(new Error('Timeout waiting for operation to complete'));
                        return;
                    }
                    
                    // Check again after interval
                    setTimeout(checkResult, checkInterval);
                };
                
                checkResult();
            });
        }
        
        /**
         * Execute operation with fallback
         * 
         * @param {string} operation Operation name
         * @param {Function} restFn REST API function
         * @param {Function} ajaxFn AJAX fallback function
         * @returns {Promise<Object>} Operation result
         */
        async executeWithFallback(operation, restFn, ajaxFn) {
            // Acquire lock to prevent duplicates
            if (!this.acquireLock(operation)) {
                throw new Error('Operation already in progress');
            }
            
            try {
                // Try REST API first if available
                if (this.useRest && this.restClient) {
                    try {
                        const result = await restFn();
                        this.stats.restSuccess++;
                        
                        if (this.debug) {
                            console.log('[MAS Dual Mode] REST success:', operation);
                        }
                        
                        return result;
                    } catch (error) {
                        this.stats.restFailed++;
                        
                        if (this.debug) {
                            console.warn('[MAS Dual Mode] REST failed, falling back to AJAX:', operation, error);
                        }
                        
                        // If REST fails with permission error, don't fallback
                        if (error.isPermissionError && error.isPermissionError()) {
                            throw error;
                        }
                        
                        // Disable REST for future requests if it keeps failing
                        if (this.stats.restFailed > 3) {
                            this.useRest = false;
                            console.warn('[MAS Dual Mode] REST API disabled due to repeated failures');
                        }
                    }
                }
                
                // Fallback to AJAX
                const result = await ajaxFn();
                this.stats.ajaxSuccess++;
                
                if (this.debug) {
                    console.log('[MAS Dual Mode] AJAX success:', operation);
                }
                
                return result;
            } catch (error) {
                this.stats.ajaxFailed++;
                
                if (this.debug) {
                    console.error('[MAS Dual Mode] Both REST and AJAX failed:', operation, error);
                }
                
                throw error;
            } finally {
                // Always release lock
                this.releaseLock(operation);
            }
        }
        
        /**
         * Get current settings
         * 
         * @returns {Promise<Object>} Settings data
         */
        async getSettings() {
            return this.executeWithFallback(
                'getSettings',
                // REST function
                () => this.restClient.getSettings(),
                // AJAX fallback
                () => this.ajaxGetSettings()
            );
        }
        
        /**
         * Save settings
         * 
         * @param {Object} settings Settings object
         * @returns {Promise<Object>} Response data
         */
        async saveSettings(settings) {
            return this.executeWithFallback(
                'saveSettings',
                // REST function
                () => this.restClient.saveSettings(settings),
                // AJAX fallback
                () => this.ajaxSaveSettings(settings)
            );
        }
        
        /**
         * Update settings (partial)
         * 
         * @param {Object} settings Partial settings object
         * @returns {Promise<Object>} Response data
         */
        async updateSettings(settings) {
            return this.executeWithFallback(
                'updateSettings',
                // REST function
                () => this.restClient.updateSettings(settings),
                // AJAX fallback (same as save for AJAX)
                () => this.ajaxSaveSettings(settings)
            );
        }
        
        /**
         * Reset settings to defaults
         * 
         * @returns {Promise<Object>} Response data
         */
        async resetSettings() {
            return this.executeWithFallback(
                'resetSettings',
                // REST function
                () => this.restClient.resetSettings(),
                // AJAX fallback
                () => this.ajaxResetSettings()
            );
        }
        
        /**
         * Get all themes
         * 
         * @returns {Promise<Array>} Array of themes
         */
        async getThemes() {
            return this.executeWithFallback(
                'getThemes',
                // REST function
                () => this.restClient.getThemes(),
                // AJAX fallback
                () => this.ajaxGetThemes()
            );
        }
        
        /**
         * Get a specific theme
         * 
         * @param {string} themeId Theme ID
         * @returns {Promise<Object>} Theme data
         */
        async getTheme(themeId) {
            return this.executeWithFallback(
                `getTheme_${themeId}`,
                // REST function
                () => this.restClient.getTheme(themeId),
                // AJAX fallback
                () => this.ajaxGetTheme(themeId)
            );
        }
        
        /**
         * Create a custom theme
         * 
         * @param {Object} theme Theme data
         * @returns {Promise<Object>} Response data
         */
        async createTheme(theme) {
            return this.executeWithFallback(
                'createTheme',
                // REST function
                () => this.restClient.createTheme(theme),
                // AJAX fallback
                () => this.ajaxCreateTheme(theme)
            );
        }
        
        /**
         * Update a custom theme
         * 
         * @param {string} themeId Theme ID
         * @param {Object} theme Updated theme data
         * @returns {Promise<Object>} Response data
         */
        async updateTheme(themeId, theme) {
            return this.executeWithFallback(
                `updateTheme_${themeId}`,
                // REST function
                () => this.restClient.updateTheme(themeId, theme),
                // AJAX fallback
                () => this.ajaxUpdateTheme(themeId, theme)
            );
        }
        
        /**
         * Delete a custom theme
         * 
         * @param {string} themeId Theme ID
         * @returns {Promise<Object>} Response data
         */
        async deleteTheme(themeId) {
            return this.executeWithFallback(
                `deleteTheme_${themeId}`,
                // REST function
                () => this.restClient.deleteTheme(themeId),
                // AJAX fallback
                () => this.ajaxDeleteTheme(themeId)
            );
        }
        
        /**
         * Apply a theme
         * 
         * @param {string} themeId Theme ID
         * @param {boolean} updateCSSVariables Whether to update CSS variables
         * @returns {Promise<Object>} Response data
         */
        async applyTheme(themeId, updateCSSVariables = true) {
            return this.executeWithFallback(
                `applyTheme_${themeId}`,
                // REST function
                () => this.restClient.applyThemeWithCSSUpdate(themeId, updateCSSVariables),
                // AJAX fallback
                () => this.ajaxApplyTheme(themeId)
            );
        }
        
        /**
         * AJAX: Get settings
         * 
         * @returns {Promise<Object>} Settings data
         */
        ajaxGetSettings() {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: this.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'mas_v2_get_settings',
                        nonce: this.ajaxNonce
                    },
                    success: (response) => {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(new Error(response.data || 'Failed to get settings'));
                        }
                    },
                    error: (xhr, status, error) => {
                        reject(new Error(error || 'AJAX request failed'));
                    }
                });
            });
        }
        
        /**
         * AJAX: Save settings
         * 
         * @param {Object} settings Settings object
         * @returns {Promise<Object>} Response data
         */
        ajaxSaveSettings(settings) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: this.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'mas_v2_save_settings',
                        nonce: this.ajaxNonce,
                        settings: settings
                    },
                    success: (response) => {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(new Error(response.data || 'Failed to save settings'));
                        }
                    },
                    error: (xhr, status, error) => {
                        reject(new Error(error || 'AJAX request failed'));
                    }
                });
            });
        }
        
        /**
         * AJAX: Reset settings
         * 
         * @returns {Promise<Object>} Response data
         */
        ajaxResetSettings() {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: this.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'mas_v2_reset_settings',
                        nonce: this.ajaxNonce
                    },
                    success: (response) => {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(new Error(response.data || 'Failed to reset settings'));
                        }
                    },
                    error: (xhr, status, error) => {
                        reject(new Error(error || 'AJAX request failed'));
                    }
                });
            });
        }
        
        /**
         * AJAX: Get all themes
         * 
         * @returns {Promise<Array>} Array of themes
         */
        ajaxGetThemes() {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: this.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'mas_v2_get_themes',
                        nonce: this.ajaxNonce
                    },
                    success: (response) => {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(new Error(response.data || 'Failed to get themes'));
                        }
                    },
                    error: (xhr, status, error) => {
                        reject(new Error(error || 'AJAX request failed'));
                    }
                });
            });
        }
        
        /**
         * AJAX: Get a specific theme
         * 
         * @param {string} themeId Theme ID
         * @returns {Promise<Object>} Theme data
         */
        ajaxGetTheme(themeId) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: this.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'mas_v2_get_theme',
                        nonce: this.ajaxNonce,
                        theme_id: themeId
                    },
                    success: (response) => {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(new Error(response.data || 'Failed to get theme'));
                        }
                    },
                    error: (xhr, status, error) => {
                        reject(new Error(error || 'AJAX request failed'));
                    }
                });
            });
        }
        
        /**
         * AJAX: Create a custom theme
         * 
         * @param {Object} theme Theme data
         * @returns {Promise<Object>} Response data
         */
        ajaxCreateTheme(theme) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: this.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'mas_v2_create_theme',
                        nonce: this.ajaxNonce,
                        theme: theme
                    },
                    success: (response) => {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(new Error(response.data || 'Failed to create theme'));
                        }
                    },
                    error: (xhr, status, error) => {
                        reject(new Error(error || 'AJAX request failed'));
                    }
                });
            });
        }
        
        /**
         * AJAX: Update a custom theme
         * 
         * @param {string} themeId Theme ID
         * @param {Object} theme Updated theme data
         * @returns {Promise<Object>} Response data
         */
        ajaxUpdateTheme(themeId, theme) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: this.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'mas_v2_update_theme',
                        nonce: this.ajaxNonce,
                        theme_id: themeId,
                        theme: theme
                    },
                    success: (response) => {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(new Error(response.data || 'Failed to update theme'));
                        }
                    },
                    error: (xhr, status, error) => {
                        reject(new Error(error || 'AJAX request failed'));
                    }
                });
            });
        }
        
        /**
         * AJAX: Delete a custom theme
         * 
         * @param {string} themeId Theme ID
         * @returns {Promise<Object>} Response data
         */
        ajaxDeleteTheme(themeId) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: this.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'mas_v2_delete_theme',
                        nonce: this.ajaxNonce,
                        theme_id: themeId
                    },
                    success: (response) => {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(new Error(response.data || 'Failed to delete theme'));
                        }
                    },
                    error: (xhr, status, error) => {
                        reject(new Error(error || 'AJAX request failed'));
                    }
                });
            });
        }
        
        /**
         * AJAX: Apply a theme
         * 
         * @param {string} themeId Theme ID
         * @returns {Promise<Object>} Response data
         */
        ajaxApplyTheme(themeId) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: this.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'mas_v2_apply_theme',
                        nonce: this.ajaxNonce,
                        theme_id: themeId
                    },
                    success: (response) => {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(new Error(response.data || 'Failed to apply theme'));
                        }
                    },
                    error: (xhr, status, error) => {
                        reject(new Error(error || 'AJAX request failed'));
                    }
                });
            });
        }
        
        /**
         * Get statistics
         * 
         * @returns {Object} Statistics object
         */
        getStats() {
            return {
                ...this.stats,
                mode: this.useRest ? 'REST' : 'AJAX',
                restAvailable: this.isRestAvailable()
            };
        }
        
        /**
         * Force REST mode
         * 
         * @param {boolean} enable Enable REST mode
         */
        forceRestMode(enable) {
            if (enable && !this.isRestAvailable()) {
                console.warn('[MAS Dual Mode] Cannot enable REST mode - not available');
                return;
            }
            
            this.useRest = enable;
            
            if (this.debug) {
                console.log('[MAS Dual Mode] REST mode', enable ? 'enabled' : 'disabled');
            }
        }
        
        /**
         * Check if using REST mode
         * 
         * @returns {boolean} True if using REST
         */
        isUsingRest() {
            return this.useRest;
        }
    }
    
    // Export to global scope
    window.MASDualModeClient = MASDualModeClient;
    
    // Create default instance
    if (typeof $ !== 'undefined') {
        window.masDualClient = new MASDualModeClient({
            debug: window.masV2Global && window.masV2Global.debug_mode
        });
        
        // Also expose as masClient for backward compatibility
        window.masClient = window.masDualClient;
    }
    
})(window, jQuery);
