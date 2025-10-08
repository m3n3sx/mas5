/**
 * APIClient - Progressive enhancement API client
 * 
 * Wraps MASRestClient with retry logic, request deduplication, timeout handling,
 * and AJAX fallback for maximum compatibility.
 * 
 * @class APIClient
 */
class APIClient {
    constructor(config = {}) {
        this.config = {
            baseUrl: config.baseUrl || (window.wpApiSettings?.root || '/wp-json/'),
            namespace: config.namespace || 'mas-v2/v1',
            nonce: config.nonce || (window.wpApiSettings?.nonce || ''),
            timeout: config.timeout || 30000,
            maxRetries: config.maxRetries || 3,
            retryDelay: config.retryDelay || 1000,
            useAjaxFallback: config.useAjaxFallback !== false,
            debug: config.debug || false
        };

        // Initialize REST client
        this.restClient = new MASRestClient({
            baseUrl: this.config.baseUrl,
            namespace: this.config.namespace,
            nonce: this.config.nonce,
            debug: this.config.debug
        });

        // Request deduplication map
        this.pendingRequests = new Map();

        // Request queue for retry
        this.requestQueue = [];

        // Response cache
        this.cache = new Map();
        this.cacheConfig = {
            enabled: config.cacheEnabled !== false,
            ttl: config.cacheTTL || 60000, // 1 minute default
            maxSize: config.cacheMaxSize || 100
        };

        // ETag storage for conditional requests
        this.etags = new Map();

        // Check if REST API is available
        this.restAvailable = this.checkRestAvailability();

        if (this.config.debug) {
            console.log('[APIClient] Initialized', {
                restAvailable: this.restAvailable,
                config: this.config
            });
        }
    }

    /**
     * Check if REST API is available
     * 
     * @returns {boolean} Whether REST API is available
     */
    checkRestAvailability() {
        return typeof window.wpApiSettings !== 'undefined' && 
               window.wpApiSettings.root &&
               typeof fetch !== 'undefined';
    }

    /**
     * Make API request with retry logic, caching, and fallback
     * 
     * @param {string} method - HTTP method
     * @param {string} endpoint - API endpoint
     * @param {Object} data - Request data
     * @param {Object} options - Additional options
     * @returns {Promise<Object>} Response data
     */
    async request(method, endpoint, data = null, options = {}) {
        const requestKey = `${method}:${endpoint}`;
        const cacheKey = this.generateCacheKey(method, endpoint, data);

        // Check cache for GET requests
        if (this.shouldCache(method) && !options.skipCache) {
            const cached = this.getCachedResponse(cacheKey);
            if (cached) {
                return cached;
            }
        }

        // Check for duplicate request
        if (this.pendingRequests.has(requestKey)) {
            if (this.config.debug) {
                console.log('[APIClient] Deduplicating request:', requestKey);
            }
            return this.pendingRequests.get(requestKey);
        }

        // Create request promise
        const requestPromise = this.executeRequest(method, endpoint, data, options, cacheKey);

        // Store in pending requests
        this.pendingRequests.set(requestKey, requestPromise);

        try {
            const result = await requestPromise;
            
            // Cache GET responses
            if (this.shouldCache(method) && result) {
                this.setCachedResponse(cacheKey, result, result.etag);
            }

            // Invalidate cache on write operations
            if (['POST', 'PUT', 'DELETE'].includes(method)) {
                this.invalidateCache(endpoint);
            }

            return result;
        } finally {
            // Remove from pending requests
            this.pendingRequests.delete(requestKey);
        }
    }

    /**
     * Execute request with retry logic
     * 
     * @private
     * @param {string} method - HTTP method
     * @param {string} endpoint - API endpoint
     * @param {Object} data - Request data
     * @param {Object} options - Additional options
     * @returns {Promise<Object>} Response data
     */
    async executeRequest(method, endpoint, data, options, cacheKey = null) {
        let lastError;
        const maxRetries = options.maxRetries || this.config.maxRetries;
        
        // Add ETag header for conditional requests if available
        if (cacheKey && this.shouldCache(method)) {
            const etag = this.getETag(cacheKey);
            if (etag) {
                options.headers = options.headers || {};
                options.headers['If-None-Match'] = etag;
            }
        }

        for (let attempt = 0; attempt <= maxRetries; attempt++) {
            try {
                // Add timeout handling
                const result = await this.requestWithTimeout(
                    method,
                    endpoint,
                    data,
                    options
                );

                if (this.config.debug && attempt > 0) {
                    console.log(`[APIClient] Request succeeded on attempt ${attempt + 1}`);
                }

                return result;

            } catch (error) {
                lastError = error;

                if (this.config.debug) {
                    console.log(`[APIClient] Request failed (attempt ${attempt + 1}/${maxRetries + 1}):`, error);
                }

                // Don't retry on certain errors
                if (this.shouldNotRetry(error)) {
                    break;
                }

                // Wait before retry with exponential backoff
                if (attempt < maxRetries) {
                    const delay = this.config.retryDelay * Math.pow(2, attempt);
                    await this.sleep(delay);
                }
            }
        }

        // All retries failed, try AJAX fallback if available
        if (this.config.useAjaxFallback && this.canUseAjaxFallback()) {
            if (this.config.debug) {
                console.log('[APIClient] Falling back to AJAX');
            }

            try {
                return await this.ajaxFallback(method, endpoint, data);
            } catch (ajaxError) {
                if (this.config.debug) {
                    console.error('[APIClient] AJAX fallback also failed:', ajaxError);
                }
            }
        }

        // All attempts failed
        throw lastError;
    }

    /**
     * Execute request with timeout
     * 
     * @private
     * @param {string} method - HTTP method
     * @param {string} endpoint - API endpoint
     * @param {Object} data - Request data
     * @param {Object} options - Additional options
     * @returns {Promise<Object>} Response data
     */
    async requestWithTimeout(method, endpoint, data, options) {
        const timeout = options.timeout || this.config.timeout;

        return Promise.race([
            this.executeRestRequest(method, endpoint, data, options),
            this.createTimeoutPromise(timeout)
        ]);
    }

    /**
     * Execute REST API request
     * 
     * @private
     * @param {string} method - HTTP method
     * @param {string} endpoint - API endpoint
     * @param {Object} data - Request data
     * @param {Object} options - Additional options
     * @returns {Promise<Object>} Response data
     */
    async executeRestRequest(method, endpoint, data, options) {
        const requestOptions = {
            method: method,
            ...options
        };

        if (data && (method === 'POST' || method === 'PUT')) {
            requestOptions.body = JSON.stringify(data);
        }

        return await this.restClient.request(endpoint, requestOptions);
    }

    /**
     * Create timeout promise
     * 
     * @private
     * @param {number} timeout - Timeout in milliseconds
     * @returns {Promise} Timeout promise
     */
    createTimeoutPromise(timeout) {
        return new Promise((_, reject) => {
            setTimeout(() => {
                reject(new Error(`Request timeout after ${timeout}ms`));
            }, timeout);
        });
    }

    /**
     * Check if error should not be retried
     * 
     * @private
     * @param {Error} error - Error object
     * @returns {boolean} Whether to skip retry
     */
    shouldNotRetry(error) {
        // Don't retry on authentication/authorization errors
        if (error.status === 401 || error.status === 403) {
            return true;
        }

        // Don't retry on validation errors
        if (error.status === 400 && error.code === 'validation_failed') {
            return true;
        }

        // Don't retry on not found
        if (error.status === 404) {
            return true;
        }

        return false;
    }

    /**
     * Check if AJAX fallback can be used
     * 
     * @private
     * @returns {boolean} Whether AJAX fallback is available
     */
    canUseAjaxFallback() {
        return typeof jQuery !== 'undefined' && 
               typeof window.ajaxurl !== 'undefined';
    }

    /**
     * AJAX fallback for REST API
     * 
     * @private
     * @param {string} method - HTTP method
     * @param {string} endpoint - API endpoint
     * @param {Object} data - Request data
     * @returns {Promise<Object>} Response data
     */
    async ajaxFallback(method, endpoint, data) {
        return new Promise((resolve, reject) => {
            // Map REST endpoint to AJAX action
            const action = this.mapEndpointToAction(endpoint, method);

            if (!action) {
                reject(new Error('No AJAX fallback available for this endpoint'));
                return;
            }

            jQuery.ajax({
                url: window.ajaxurl,
                type: 'POST',
                data: {
                    action: action,
                    nonce: this.config.nonce,
                    ...data
                },
                success: (response) => {
                    if (response.success) {
                        resolve(response.data);
                    } else {
                        reject(new Error(response.data?.message || 'AJAX request failed'));
                    }
                },
                error: (xhr, status, error) => {
                    reject(new Error(`AJAX error: ${error}`));
                }
            });
        });
    }

    /**
     * Map REST endpoint to AJAX action
     * 
     * @private
     * @param {string} endpoint - REST endpoint
     * @param {string} method - HTTP method
     * @returns {string|null} AJAX action name
     */
    mapEndpointToAction(endpoint, method) {
        const mapping = {
            'GET:/settings': 'mas_v2_get_settings',
            'POST:/settings': 'mas_v2_save_settings',
            'PUT:/settings': 'mas_v2_update_settings',
            'DELETE:/settings': 'mas_v2_reset_settings',
            'GET:/themes': 'mas_v2_get_themes',
            'POST:/preview': 'mas_v2_generate_preview'
        };

        return mapping[`${method}:${endpoint}`] || null;
    }

    /**
     * Sleep utility for retry delays
     * 
     * @private
     * @param {number} ms - Milliseconds to sleep
     * @returns {Promise} Sleep promise
     */
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    // ========== Public API Methods ==========

    /**
     * Get settings
     * 
     * @returns {Promise<Object>} Settings data
     */
    async getSettings() {
        return await this.request('GET', '/settings');
    }

    /**
     * Save settings
     * 
     * @param {Object} settings - Settings to save
     * @returns {Promise<Object>} Response data
     */
    async saveSettings(settings) {
        return await this.request('POST', '/settings', settings);
    }

    /**
     * Update settings (partial)
     * 
     * @param {Object} settings - Settings to update
     * @returns {Promise<Object>} Response data
     */
    async updateSettings(settings) {
        return await this.request('PUT', '/settings', settings);
    }

    /**
     * Reset settings
     * 
     * @returns {Promise<Object>} Response data
     */
    async resetSettings() {
        return await this.request('DELETE', '/settings');
    }

    /**
     * Get themes
     * 
     * @returns {Promise<Array>} Themes array
     */
    async getThemes() {
        return await this.request('GET', '/themes');
    }

    /**
     * Apply theme
     * 
     * @param {string} themeId - Theme ID
     * @returns {Promise<Object>} Response data
     */
    async applyTheme(themeId) {
        return await this.request('POST', `/themes/${themeId}/apply`);
    }

    /**
     * Generate preview
     * 
     * @param {Object} settings - Settings to preview
     * @returns {Promise<Object>} Preview data with CSS
     */
    async generatePreview(settings) {
        return await this.request('POST', '/preview', { settings });
    }

    /**
     * List backups
     * 
     * @returns {Promise<Array>} Backups array
     */
    async listBackups() {
        return await this.request('GET', '/backups');
    }

    /**
     * Create backup
     * 
     * @param {Object} options - Backup options
     * @returns {Promise<Object>} Response data
     */
    async createBackup(options = {}) {
        return await this.request('POST', '/backups', options);
    }

    /**
     * Restore backup
     * 
     * @param {number} backupId - Backup ID
     * @returns {Promise<Object>} Response data
     */
    async restoreBackup(backupId) {
        return await this.request('POST', `/backups/${backupId}/restore`);
    }

    /**
     * Delete backup
     * 
     * @param {number} backupId - Backup ID
     * @returns {Promise<Object>} Response data
     */
    async deleteBackup(backupId) {
        return await this.request('DELETE', `/backups/${backupId}`);
    }

    /**
     * Export settings
     * 
     * @returns {Promise<Object>} Export data
     */
    async exportSettings() {
        return await this.request('GET', '/export');
    }

    /**
     * Import settings
     * 
     * @param {Object} data - Import data
     * @param {boolean} createBackup - Whether to create backup
     * @returns {Promise<Object>} Response data
     */
    async importSettings(data, createBackup = true) {
        return await this.request('POST', '/import', { data, create_backup: createBackup });
    }

    /**
     * Cancel pending request
     * 
     * @param {string} method - HTTP method
     * @param {string} endpoint - API endpoint
     */
    cancelRequest(method, endpoint) {
        const requestKey = `${method}:${endpoint}`;
        this.pendingRequests.delete(requestKey);

        if (this.config.debug) {
            console.log('[APIClient] Request cancelled:', requestKey);
        }
    }

    /**
     * Clear all pending requests
     */
    clearPendingRequests() {
        this.pendingRequests.clear();

        if (this.config.debug) {
            console.log('[APIClient] All pending requests cleared');
        }
    }

    /**
     * Get pending request count
     * 
     * @returns {number} Number of pending requests
     */
    getPendingRequestCount() {
        return this.pendingRequests.size;
    }

    /**
     * Get cached response
     * 
     * @param {string} cacheKey - Cache key
     * @returns {Object|null} Cached response or null
     */
    getCachedResponse(cacheKey) {
        if (!this.cacheConfig.enabled) {
            return null;
        }

        const cached = this.cache.get(cacheKey);
        if (!cached) {
            return null;
        }

        // Check if cache is expired
        const now = Date.now();
        if (now - cached.timestamp > this.cacheConfig.ttl) {
            this.cache.delete(cacheKey);
            return null;
        }

        if (this.config.debug) {
            console.log('[APIClient] Cache hit:', cacheKey);
        }

        return cached.data;
    }

    /**
     * Set cached response
     * 
     * @param {string} cacheKey - Cache key
     * @param {Object} data - Response data
     * @param {string} etag - ETag header value
     */
    setCachedResponse(cacheKey, data, etag = null) {
        if (!this.cacheConfig.enabled) {
            return;
        }

        // Enforce cache size limit
        if (this.cache.size >= this.cacheConfig.maxSize) {
            // Remove oldest entry
            const firstKey = this.cache.keys().next().value;
            this.cache.delete(firstKey);
        }

        this.cache.set(cacheKey, {
            data,
            timestamp: Date.now()
        });

        // Store ETag if provided
        if (etag) {
            this.etags.set(cacheKey, etag);
        }

        if (this.config.debug) {
            console.log('[APIClient] Cached response:', cacheKey);
        }
    }

    /**
     * Invalidate cache
     * 
     * @param {string} pattern - Cache key pattern (optional, clears all if not provided)
     */
    invalidateCache(pattern = null) {
        if (!pattern) {
            this.cache.clear();
            this.etags.clear();
            if (this.config.debug) {
                console.log('[APIClient] All cache cleared');
            }
            return;
        }

        // Clear matching entries
        const regex = new RegExp(pattern);
        for (const key of this.cache.keys()) {
            if (regex.test(key)) {
                this.cache.delete(key);
                this.etags.delete(key);
            }
        }

        if (this.config.debug) {
            console.log('[APIClient] Cache invalidated:', pattern);
        }
    }

    /**
     * Get ETag for cache key
     * 
     * @param {string} cacheKey - Cache key
     * @returns {string|null} ETag value or null
     */
    getETag(cacheKey) {
        return this.etags.get(cacheKey) || null;
    }

    /**
     * Generate cache key
     * 
     * @param {string} method - HTTP method
     * @param {string} endpoint - API endpoint
     * @param {Object} data - Request data
     * @returns {string} Cache key
     */
    generateCacheKey(method, endpoint, data = null) {
        const dataStr = data ? JSON.stringify(data) : '';
        return `${method}:${endpoint}:${dataStr}`;
    }

    /**
     * Check if request should be cached
     * 
     * @param {string} method - HTTP method
     * @returns {boolean} Whether request should be cached
     */
    shouldCache(method) {
        // Only cache GET requests
        return this.cacheConfig.enabled && method === 'GET';
    }

    /**
     * Get cache statistics
     * 
     * @returns {Object} Cache statistics
     */
    getCacheStats() {
        return {
            enabled: this.cacheConfig.enabled,
            size: this.cache.size,
            maxSize: this.cacheConfig.maxSize,
            ttl: this.cacheConfig.ttl,
            etagCount: this.etags.size
        };
    }

    /**
     * Enable cache
     */
    enableCache() {
        this.cacheConfig.enabled = true;
        if (this.config.debug) {
            console.log('[APIClient] Cache enabled');
        }
    }

    /**
     * Disable cache
     */
    disableCache() {
        this.cacheConfig.enabled = false;
        this.cache.clear();
        this.etags.clear();
        if (this.config.debug) {
            console.log('[APIClient] Cache disabled');
        }
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = APIClient;
}

// Make available globally for WordPress
if (typeof window !== 'undefined') {
    window.APIClient = APIClient;
}
