/**
 * Modern Admin Styler V2 - REST API Client
 * 
 * Provides a clean interface for interacting with the MAS REST API
 * using the Fetch API with proper error handling and nonce management.
 * 
 * @package ModernAdminStylerV2
 * @since 2.2.0
 */

(function(window) {
    'use strict';
    
    /**
     * MAS REST API Client
     * 
     * @class
     */
    class MASRestClient {
        /**
         * Constructor
         * 
         * @param {Object} config Configuration object
         */
        constructor(config = {}) {
            // Get REST API settings from WordPress
            this.baseUrl = config.baseUrl || (window.wpApiSettings && window.wpApiSettings.root) || '/wp-json/';
            this.namespace = config.namespace || 'mas-v2/v1';
            this.nonce = config.nonce || (window.wpApiSettings && window.wpApiSettings.nonce) || '';
            
            // API version (can be overridden)
            this.version = config.version || this.extractVersionFromNamespace(this.namespace);
            
            // Build full API URL
            this.apiUrl = this.baseUrl + this.namespace;
            
            // Debug mode
            this.debug = config.debug || false;
            
            // Cache for ETags and Last-Modified headers
            this.cache = {
                etags: new Map(),
                lastModified: new Map(),
                data: new Map()
            };
            
            // Deprecation warnings tracking
            this.deprecationWarnings = new Set();
            
            // Log initialization
            if (this.debug) {
                console.log('[MAS REST Client] Initialized', {
                    apiUrl: this.apiUrl,
                    version: this.version,
                    hasNonce: !!this.nonce
                });
            }
        }
        
        /**
         * Extract version from namespace
         * 
         * @param {string} namespace API namespace
         * @returns {string} Version identifier (e.g., 'v1', 'v2')
         */
        extractVersionFromNamespace(namespace) {
            const match = namespace.match(/v(\d+)$/);
            return match ? 'v' + match[1] : 'v1';
        }
        
        /**
         * Set API version
         * 
         * @param {string} version Version identifier (e.g., 'v1', 'v2')
         */
        setVersion(version) {
            // Normalize version (ensure 'v' prefix)
            if (!/^v/.test(version)) {
                version = 'v' + version;
            }
            
            this.version = version;
            
            // Update namespace and API URL
            this.namespace = this.namespace.replace(/v\d+$/, version);
            this.apiUrl = this.baseUrl + this.namespace;
            
            if (this.debug) {
                console.log('[MAS REST Client] Version changed', {
                    version: this.version,
                    apiUrl: this.apiUrl
                });
            }
        }
        
        /**
         * Get current API version
         * 
         * @returns {string} Current version
         */
        getVersion() {
            return this.version;
        }
        
        /**
         * Make a REST API request
         * 
         * @param {string} endpoint API endpoint (e.g., '/settings')
         * @param {Object} options Request options
         * @returns {Promise<Object>} Response data
         */
        async request(endpoint, options = {}) {
            const url = this.apiUrl + endpoint;
            const method = options.method || 'GET';
            
            // Build headers
            const headers = {
                'Content-Type': 'application/json',
                'X-WP-Nonce': this.nonce,
                ...options.headers
            };
            
            // Add conditional request headers for GET requests
            if (method === 'GET' && options.useConditionalRequest !== false) {
                const etag = this.cache.etags.get(endpoint);
                const lastModified = this.cache.lastModified.get(endpoint);
                
                if (etag) {
                    headers['If-None-Match'] = etag;
                }
                
                if (lastModified) {
                    headers['If-Modified-Since'] = lastModified;
                }
            }
            
            // Build request options
            const requestOptions = {
                ...options,
                method,
                headers,
                credentials: 'same-origin'
            };
            
            if (this.debug) {
                console.log('[MAS REST Client] Request', {
                    url,
                    method,
                    options: requestOptions,
                    hasETag: !!headers['If-None-Match'],
                    hasLastModified: !!headers['If-Modified-Since']
                });
            }
            
            try {
                const response = await fetch(url, requestOptions);
                
                // Check for deprecation warnings
                this.handleDeprecationWarnings(response, endpoint);
                
                // Check for version headers
                this.handleVersionHeaders(response);
                
                // Handle 304 Not Modified
                if (response.status === 304) {
                    if (this.debug) {
                        console.log('[MAS REST Client] 304 Not Modified - Using cached data');
                    }
                    
                    // Return cached data
                    const cachedData = this.cache.data.get(endpoint);
                    if (cachedData) {
                        return cachedData;
                    }
                    
                    // If no cached data, this is an error
                    throw new MASRestError(
                        'No cached data available for 304 response',
                        'cache_miss',
                        304
                    );
                }
                
                const data = await response.json();
                
                // Store ETag and Last-Modified headers for future requests
                if (method === 'GET' && response.ok) {
                    const etag = response.headers.get('ETag');
                    const lastModified = response.headers.get('Last-Modified');
                    const xCache = response.headers.get('X-Cache');
                    
                    if (etag) {
                        this.cache.etags.set(endpoint, etag);
                    }
                    
                    if (lastModified) {
                        this.cache.lastModified.set(endpoint, lastModified);
                    }
                    
                    // Cache the response data
                    this.cache.data.set(endpoint, data);
                    
                    if (this.debug) {
                        console.log('[MAS REST Client] Cached response headers', {
                            endpoint,
                            etag,
                            lastModified,
                            xCache
                        });
                    }
                }
                
                if (this.debug) {
                    console.log('[MAS REST Client] Response', {
                        status: response.status,
                        ok: response.ok,
                        data
                    });
                }
                
                // Check if response is OK
                if (!response.ok) {
                    throw new MASRestError(
                        data.message || 'Request failed',
                        data.code || 'request_failed',
                        response.status,
                        data
                    );
                }
                
                return data;
            } catch (error) {
                if (this.debug) {
                    console.error('[MAS REST Client] Error', error);
                }
                
                // Re-throw MASRestError as-is
                if (error instanceof MASRestError) {
                    throw error;
                }
                
                // Wrap other errors
                throw new MASRestError(
                    error.message || 'Network error occurred',
                    'network_error',
                    0,
                    { originalError: error }
                );
            }
        }
        
        /**
         * Get current settings
         * 
         * @returns {Promise<Object>} Settings data
         */
        async getSettings() {
            const response = await this.request('/settings', {
                method: 'GET'
            });
            
            return response.data;
        }
        
        /**
         * Save settings (complete replacement)
         * 
         * @param {Object} settings Settings object
         * @returns {Promise<Object>} Response data
         */
        async saveSettings(settings) {
            const response = await this.request('/settings', {
                method: 'POST',
                body: JSON.stringify(settings)
            });
            
            // Clear cache after successful save
            this.clearCache('/settings');
            
            return response.data;
        }
        
        /**
         * Update settings (partial update)
         * 
         * @param {Object} settings Partial settings object
         * @returns {Promise<Object>} Response data
         */
        async updateSettings(settings) {
            const response = await this.request('/settings', {
                method: 'PUT',
                body: JSON.stringify(settings)
            });
            
            // Clear cache after successful update
            this.clearCache('/settings');
            
            return response.data;
        }
        
        /**
         * Reset settings to defaults
         * 
         * @returns {Promise<Object>} Response data
         */
        async resetSettings() {
            const response = await this.request('/settings', {
                method: 'DELETE'
            });
            
            // Clear cache after successful reset
            this.clearCache('/settings');
            
            return response.data;
        }
        
        /**
         * Get all themes
         * 
         * @returns {Promise<Array>} Array of themes
         */
        async getThemes() {
            const response = await this.request('/themes', {
                method: 'GET'
            });
            
            return response.data;
        }
        
        /**
         * Apply a theme
         * 
         * @param {string} themeId Theme ID
         * @returns {Promise<Object>} Response data
         */
        async applyTheme(themeId) {
            const response = await this.request(`/themes/${themeId}/apply`, {
                method: 'POST'
            });
            
            return response.data;
        }
        
        /**
         * Get a specific theme
         * 
         * @param {string} themeId Theme ID
         * @returns {Promise<Object>} Theme data
         */
        async getTheme(themeId) {
            const response = await this.request(`/themes/${themeId}`, {
                method: 'GET'
            });
            
            return response.data;
        }
        
        /**
         * Create a custom theme
         * 
         * @param {Object} theme Theme data
         * @returns {Promise<Object>} Response data
         */
        async createTheme(theme) {
            const response = await this.request('/themes', {
                method: 'POST',
                body: JSON.stringify(theme)
            });
            
            return response.data;
        }
        
        /**
         * Update a custom theme
         * 
         * @param {string} themeId Theme ID
         * @param {Object} theme Updated theme data
         * @returns {Promise<Object>} Response data
         */
        async updateTheme(themeId, theme) {
            const response = await this.request(`/themes/${themeId}`, {
                method: 'PUT',
                body: JSON.stringify(theme)
            });
            
            return response.data;
        }
        
        /**
         * Delete a custom theme
         * 
         * @param {string} themeId Theme ID
         * @returns {Promise<Object>} Response data
         */
        async deleteTheme(themeId) {
            const response = await this.request(`/themes/${themeId}`, {
                method: 'DELETE'
            });
            
            return response.data;
        }
        
        /**
         * Get predefined theme presets
         * 
         * @returns {Promise<Array>} Array of theme presets
         */
        async getThemePresets() {
            const response = await this.request('/themes/presets', {
                method: 'GET'
            });
            
            return response.data;
        }
        
        /**
         * Preview a theme without applying changes
         * 
         * @param {Object} themeData Theme data with settings
         * @returns {Promise<Object>} Preview data with CSS
         */
        async previewTheme(themeData) {
            const response = await this.request('/themes/preview', {
                method: 'POST',
                body: JSON.stringify(themeData)
            });
            
            return response.data;
        }
        
        /**
         * Preview theme and apply CSS to page
         * 
         * @param {Object} themeData Theme data with settings
         * @param {boolean} applyCSS Whether to inject CSS into page
         * @returns {Promise<Object>} Preview data
         */
        async previewThemeWithCSS(themeData, applyCSS = true) {
            const preview = await this.previewTheme(themeData);
            
            if (applyCSS && preview && preview.css) {
                this.applyPreviewCSS(preview.css);
            }
            
            return preview;
        }
        
        /**
         * Apply preview CSS to the page with smooth transitions
         * 
         * @param {string} css CSS to apply
         * @param {boolean} withTransition Whether to apply with smooth transition
         */
        applyPreviewCSS(css, withTransition = true) {
            // Get or create preview style element
            let styleElement = document.getElementById('mas-preview-styles');
            
            if (!styleElement) {
                styleElement = document.createElement('style');
                styleElement.id = 'mas-preview-styles';
                styleElement.setAttribute('data-mas-preview', 'true');
                document.head.appendChild(styleElement);
            }
            
            // Add transition styles if requested
            if (withTransition) {
                const transitionCSS = `
                    #adminmenu,
                    #adminmenu a,
                    #wpadminbar,
                    #wpadminbar * {
                        transition: background-color 0.3s ease, color 0.3s ease !important;
                    }
                `;
                styleElement.textContent = transitionCSS + '\n' + css;
            } else {
                styleElement.textContent = css;
            }
            
            if (this.debug) {
                console.log('[MAS REST Client] Preview CSS applied', {
                    cssLength: css.length,
                    withTransition
                });
            }
        }
        
        /**
         * Clear preview CSS from the page
         */
        clearPreviewCSS() {
            const styleElement = document.getElementById('mas-preview-styles');
            if (styleElement) {
                styleElement.remove();
                
                if (this.debug) {
                    console.log('[MAS REST Client] Preview CSS cleared');
                }
            }
        }
        
        /**
         * Export a theme with metadata and checksum
         * 
         * @param {string} themeId Theme ID to export
         * @param {Object} themeData Optional theme data (alternative to themeId)
         * @param {boolean} triggerDownload Whether to trigger automatic download
         * @returns {Promise<Object>} Export data
         */
        async exportTheme(themeId = null, themeData = null, triggerDownload = true) {
            const body = {};
            
            if (themeId) {
                body.theme_id = themeId;
            }
            
            if (themeData) {
                body.theme_data = themeData;
            }
            
            const response = await this.request('/themes/export', {
                method: 'POST',
                body: JSON.stringify(body)
            });
            
            // Trigger file download if requested
            if (triggerDownload && response.data) {
                const filename = `mas-theme-${themeId || 'custom'}-${Date.now()}.json`;
                this.triggerDownload(
                    response.data,
                    filename,
                    'application/json'
                );
            }
            
            return response.data;
        }
        
        /**
         * Import a theme with version compatibility validation
         * 
         * @param {Object|File} importData Import data object or File object
         * @param {boolean} createTheme Whether to create the theme after import
         * @returns {Promise<Object>} Imported theme data
         */
        async importTheme(importData, createTheme = false) {
            // If importData is a File object, read it first
            if (importData instanceof File) {
                importData = await this.readFileAsJSON(importData);
            }
            
            const response = await this.request('/themes/import', {
                method: 'POST',
                body: JSON.stringify({
                    import_data: importData,
                    create_theme: createTheme
                })
            });
            
            return response.data;
        }
        
        /**
         * Import theme from file input
         * 
         * @param {HTMLInputElement} fileInput File input element
         * @param {boolean} createTheme Whether to create the theme after import
         * @returns {Promise<Object>} Imported theme data
         */
        async importThemeFromFile(fileInput, createTheme = false) {
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                throw new MASRestError(
                    'No file selected',
                    'no_file',
                    400
                );
            }
            
            const file = fileInput.files[0];
            
            // Validate file type
            if (!file.name.endsWith('.json')) {
                throw new MASRestError(
                    'Invalid file type. Please select a JSON file.',
                    'invalid_file_type',
                    400
                );
            }
            
            // Validate file size (max 5MB)
            const maxSize = 5 * 1024 * 1024; // 5MB
            if (file.size > maxSize) {
                throw new MASRestError(
                    'File is too large. Maximum size is 5MB.',
                    'file_too_large',
                    400
                );
            }
            
            return await this.importTheme(file, createTheme);
        }
        
        /**
         * Apply theme with CSS variable updates
         * 
         * @param {string} themeId Theme ID
         * @param {boolean} updateCSSVariables Whether to update CSS variables immediately
         * @returns {Promise<Object>} Response data
         */
        async applyThemeWithCSSUpdate(themeId, updateCSSVariables = true) {
            const response = await this.applyTheme(themeId);
            
            if (updateCSSVariables && response) {
                // Get the theme to access its settings
                const theme = await this.getTheme(themeId);
                
                if (theme && theme.settings) {
                    this.updateCSSVariables(theme.settings);
                }
            }
            
            return response;
        }
        
        /**
         * Update CSS variables from settings
         * 
         * @param {Object} settings Settings object with color values
         */
        updateCSSVariables(settings) {
            const root = document.documentElement;
            
            // Map settings to CSS variables
            const cssVariableMap = {
                'menu_background': '--mas-menu-bg',
                'menu_text_color': '--mas-menu-text',
                'menu_hover_background': '--mas-menu-hover-bg',
                'menu_hover_text_color': '--mas-menu-hover-text',
                'menu_active_background': '--mas-menu-active-bg',
                'menu_active_text_color': '--mas-menu-active-text',
                'admin_bar_background': '--mas-admin-bar-bg',
                'admin_bar_text_color': '--mas-admin-bar-text',
                'admin_bar_hover_color': '--mas-admin-bar-hover',
                'submenu_background': '--mas-submenu-bg',
                'submenu_text_color': '--mas-submenu-text',
                'submenu_hover_background': '--mas-submenu-hover-bg',
                'submenu_hover_text_color': '--mas-submenu-hover-text'
            };
            
            // Update CSS variables
            Object.keys(cssVariableMap).forEach(settingKey => {
                if (settings[settingKey]) {
                    const cssVar = cssVariableMap[settingKey];
                    root.style.setProperty(cssVar, settings[settingKey]);
                    
                    if (this.debug) {
                        console.log(`[MAS REST Client] Updated CSS variable: ${cssVar} = ${settings[settingKey]}`);
                    }
                }
            });
        }
        
        /**
         * Generate preview CSS
         * 
         * @param {Object} settings Settings to preview
         * @returns {Promise<Object>} Response with CSS
         */
        async generatePreview(settings) {
            const response = await this.request('/preview', {
                method: 'POST',
                body: JSON.stringify({ settings })
            });
            
            return response.data;
        }
        
        /**
         * List all backups
         * 
         * @returns {Promise<Array>} Array of backups
         */
        async listBackups() {
            const response = await this.request('/backups', {
                method: 'GET'
            });
            
            return response.data;
        }
        
        /**
         * Create a backup
         * 
         * @param {Object} options Backup options
         * @returns {Promise<Object>} Response data
         */
        async createBackup(options = {}) {
            const response = await this.request('/backups', {
                method: 'POST',
                body: JSON.stringify(options)
            });
            
            return response.data;
        }
        
        /**
         * Restore a backup
         * 
         * @param {number} backupId Backup ID
         * @returns {Promise<Object>} Response data
         */
        async restoreBackup(backupId) {
            const response = await this.request(`/backups/${backupId}/restore`, {
                method: 'POST'
            });
            
            return response.data;
        }
        
        /**
         * Delete a backup
         * 
         * @param {number} backupId Backup ID
         * @returns {Promise<Object>} Response data
         */
        async deleteBackup(backupId) {
            const response = await this.request(`/backups/${backupId}`, {
                method: 'DELETE'
            });
            
            return response.data;
        }
        
        /**
         * Download backup as JSON file (Phase 2)
         * 
         * @param {string} backupId Backup ID to download
         * @param {boolean} triggerDownload Whether to trigger automatic download
         * @returns {Promise<Object>} Download data
         */
        async downloadBackup(backupId, triggerDownload = true) {
            const response = await this.request(`/backups/${backupId}/download`, {
                method: 'GET'
            });
            
            if (triggerDownload && response.data) {
                // Create blob and trigger download
                const blob = new Blob([response.data], { type: 'application/json' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `mas-backup-${backupId}-${Date.now()}.json`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            }
            
            return response.data;
        }
        
        /**
         * Batch backup operations (Phase 2)
         * 
         * @param {Array} operations Array of operations to perform
         * @returns {Promise<Object>} Batch operation results
         */
        async batchBackupOperations(operations) {
            const response = await this.request('/backups/batch', {
                method: 'POST',
                body: JSON.stringify({ operations })
            });
            
            return response.data;
        }
        
        /**
         * Cleanup old backups (Phase 2)
         * 
         * @returns {Promise<Object>} Cleanup results
         */
        async cleanupOldBackups() {
            const response = await this.request('/backups/cleanup', {
                method: 'POST'
            });
            
            return response.data;
        }
        
        /**
         * Get backup statistics (Phase 2)
         * 
         * @returns {Promise<Object>} Backup statistics
         */
        async getBackupStatistics() {
            const response = await this.request('/backups/statistics', {
                method: 'GET'
            });
            
            return response.data;
        }
        
        /**
         * Export settings with automatic file download
         * 
         * @param {boolean} includeMetadata Whether to include metadata in export
         * @param {boolean} triggerDownload Whether to trigger automatic download
         * @returns {Promise<Object>} Export data
         */
        async exportSettings(includeMetadata = true, triggerDownload = true) {
            const params = new URLSearchParams({
                include_metadata: includeMetadata ? '1' : '0'
            });
            
            const response = await this.request(`/export?${params.toString()}`, {
                method: 'GET'
            });
            
            // Trigger file download if requested
            if (triggerDownload && response.data) {
                this.triggerDownload(
                    response.data,
                    response.filename || 'mas-v2-settings.json',
                    'application/json'
                );
            }
            
            return response.data;
        }
        
        /**
         * Import settings from data or file
         * 
         * @param {Object|File} data Import data object or File object
         * @param {boolean} createBackup Whether to create backup before import
         * @returns {Promise<Object>} Response data
         */
        async importSettings(data, createBackup = true) {
            // If data is a File object, read it first
            if (data instanceof File) {
                data = await this.readFileAsJSON(data);
            }
            
            const response = await this.request('/import', {
                method: 'POST',
                body: JSON.stringify({
                    data: data,
                    create_backup: createBackup
                })
            });
            
            return response.data;
        }
        
        /**
         * Import settings from file input
         * 
         * @param {HTMLInputElement} fileInput File input element
         * @param {boolean} createBackup Whether to create backup before import
         * @returns {Promise<Object>} Response data
         */
        async importSettingsFromFile(fileInput, createBackup = true) {
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                throw new MASRestError(
                    'No file selected',
                    'no_file',
                    400
                );
            }
            
            const file = fileInput.files[0];
            
            // Validate file type
            if (!file.name.endsWith('.json')) {
                throw new MASRestError(
                    'Invalid file type. Please select a JSON file.',
                    'invalid_file_type',
                    400
                );
            }
            
            // Validate file size (max 5MB)
            const maxSize = 5 * 1024 * 1024; // 5MB
            if (file.size > maxSize) {
                throw new MASRestError(
                    'File is too large. Maximum size is 5MB.',
                    'file_too_large',
                    400
                );
            }
            
            return await this.importSettings(file, createBackup);
        }
        
        /**
         * Trigger file download
         * 
         * @param {Object} data Data to download
         * @param {string} filename Filename
         * @param {string} mimeType MIME type
         */
        triggerDownload(data, filename, mimeType = 'application/json') {
            // Convert data to JSON string if it's an object
            const content = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
            
            // Create blob
            const blob = new Blob([content], { type: mimeType });
            
            // Create download link
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            
            // Trigger download
            document.body.appendChild(link);
            link.click();
            
            // Cleanup
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);
            
            if (this.debug) {
                console.log('[MAS REST Client] Download triggered', {
                    filename,
                    size: blob.size
                });
            }
        }
        
        /**
         * Clear cache for a specific endpoint or all endpoints
         * 
         * @param {string|null} endpoint Endpoint to clear cache for (null for all)
         */
        clearCache(endpoint = null) {
            if (endpoint) {
                this.cache.etags.delete(endpoint);
                this.cache.lastModified.delete(endpoint);
                this.cache.data.delete(endpoint);
                
                if (this.debug) {
                    console.log('[MAS REST Client] Cache cleared for endpoint:', endpoint);
                }
            } else {
                this.cache.etags.clear();
                this.cache.lastModified.clear();
                this.cache.data.clear();
                
                if (this.debug) {
                    console.log('[MAS REST Client] All cache cleared');
                }
            }
        }
        
        /**
         * Get cache statistics
         * 
         * @returns {Object} Cache statistics
         */
        getCacheStats() {
            return {
                etags: this.cache.etags.size,
                lastModified: this.cache.lastModified.size,
                cachedData: this.cache.data.size,
                endpoints: Array.from(this.cache.etags.keys())
            };
        }
        
        /**
         * Check if endpoint has cached data
         * 
         * @param {string} endpoint Endpoint to check
         * @returns {boolean} True if cached
         */
        hasCachedData(endpoint) {
            return this.cache.data.has(endpoint);
        }
        
        /**
         * Read file as JSON
         * 
         * @param {File} file File object
         * @returns {Promise<Object>} Parsed JSON data
         */
        readFileAsJSON(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                
                reader.onload = (event) => {
                    try {
                        const data = JSON.parse(event.target.result);
                        resolve(data);
                    } catch (error) {
                        reject(new MASRestError(
                            'Invalid JSON file',
                            'invalid_json',
                            400,
                            { originalError: error }
                        ));
                    }
                };
                
                reader.onerror = () => {
                    reject(new MASRestError(
                        'Failed to read file',
                        'file_read_error',
                        500
                    ));
                };
                
                reader.readAsText(file);
            });
        }
        
        /**
         * Validate import data before sending
         * 
         * @param {Object} data Import data
         * @returns {Object} Validation result with valid flag and errors
         */
        validateImportData(data) {
            const errors = [];
            
            // Check if data is an object
            if (!data || typeof data !== 'object') {
                errors.push('Import data must be a valid object');
            }
            
            // Check for settings key
            if (!data.settings) {
                errors.push('Import data must contain a "settings" property');
            }
            
            // Check if settings is an object
            if (data.settings && typeof data.settings !== 'object') {
                errors.push('Settings must be a valid object');
            }
            
            // Check if settings is not empty
            if (data.settings && Object.keys(data.settings).length === 0) {
                errors.push('Settings cannot be empty');
            }
            
            return {
                valid: errors.length === 0,
                errors: errors
            };
        }
        
        /**
         * Get diagnostics information
         * 
         * @param {Array<string>} sections Optional array of sections to include
         * @returns {Promise<Object>} Diagnostics data
         */
        async getDiagnostics(sections = null) {
            let endpoint = '/diagnostics';
            
            if (sections && sections.length > 0) {
                const params = new URLSearchParams({
                    include: sections.join(',')
                });
                endpoint += `?${params.toString()}`;
            }
            
            const response = await this.request(endpoint, {
                method: 'GET'
            });
            
            return response.data;
        }
        
        /**
         * Get quick health check
         * 
         * @returns {Promise<Object>} Health check data
         */
        async getHealthCheck() {
            const response = await this.request('/diagnostics/health', {
                method: 'GET'
            });
            
            return response.data;
        }
        
        /**
         * Get performance metrics
         * 
         * @returns {Promise<Object>} Performance metrics data
         */
        async getPerformanceMetrics() {
            const response = await this.request('/diagnostics/performance', {
                method: 'GET'
            });
            
            return response.data;
        }
        
        /**
         * Get system health status (Phase 2)
         * 
         * Returns comprehensive health check results including all system checks,
         * overall status calculation, and actionable recommendations.
         * 
         * @returns {Promise<Object>} Health status data
         */
        async getSystemHealth() {
            const response = await this.request('/system/health', {
                method: 'GET'
            });
            
            return response.data;
        }
        
        /**
         * Get system information (Phase 2)
         * 
         * Returns detailed system information including PHP version, WordPress version,
         * plugin version, server information, and configuration details.
         * 
         * @returns {Promise<Object>} System information
         */
        async getSystemInfo() {
            const response = await this.request('/system/info', {
                method: 'GET'
            });
            
            return response.data;
        }
        
        /**
         * Get performance metrics (Phase 2)
         * 
         * Returns detailed performance metrics including memory usage, cache statistics,
         * database query performance, and execution times.
         * 
         * @returns {Promise<Object>} Performance metrics
         */
        async getPerformanceMetrics() {
            const response = await this.request('/system/performance', {
                method: 'GET'
            });
            
            return response.data;
        }
        
        /**
         * Get conflict detection results (Phase 2)
         * 
         * Returns detailed conflict detection including plugin conflicts, theme conflicts,
         * JavaScript conflicts, and actionable recommendations.
         * 
         * @returns {Promise<Object>} Conflict detection results
         */
        async getConflicts() {
            const response = await this.request('/system/conflicts', {
                method: 'GET'
            });
            
            return response.data;
        }
        
        /**
         * Get cache status (Phase 2)
         * 
         * Returns cache status including object cache availability, transient counts,
         * cache statistics, and performance metrics.
         * 
         * @returns {Promise<Object>} Cache status
         */
        async getCacheStatus() {
            const response = await this.request('/system/cache', {
                method: 'GET'
            });
            
            return response.data;
        }
        
        /**
         * Clear all caches (Phase 2)
         * 
         * Clears all plugin caches including WordPress transients, object cache,
         * and cache service caches.
         * 
         * @returns {Promise<Object>} Clear cache results
         */
        async clearCache() {
            const response = await this.request('/system/cache', {
                method: 'DELETE'
            });
            
            return response.data;
        }
        
        /**
         * Batch update settings (Phase 2 - Task 6)
         * 
         * Performs multiple settings updates atomically with transaction support.
         * All operations succeed or all are rolled back on failure.
         * 
         * @param {Array} operations Array of operations to perform
         * @param {boolean} async Whether to process asynchronously (auto for > 50 operations)
         * @returns {Promise<Object>} Batch operation results
         */
        async batchUpdateSettings(operations, async = false) {
            const response = await this.request('/settings/batch', {
                method: 'POST',
                body: JSON.stringify({ 
                    operations,
                    async 
                })
            });
            
            return response.data;
        }
        
        /**
         * Batch apply theme with validation (Phase 2 - Task 6)
         * 
         * Applies a theme with validation before commit. Creates automatic backup
         * before applying changes.
         * 
         * @param {string} themeId Theme ID to apply
         * @param {boolean} validateOnly Only validate without applying
         * @returns {Promise<Object>} Theme application results
         */
        async batchApplyTheme(themeId, validateOnly = false) {
            const response = await this.request('/themes/batch-apply', {
                method: 'POST',
                body: JSON.stringify({ 
                    theme_id: themeId,
                    validate_only: validateOnly
                })
            });
            
            return response.data;
        }
        
        /**
         * Get batch operation status (Phase 2 - Task 6)
         * 
         * Retrieves the status of an asynchronous batch operation.
         * 
         * @param {string} jobId Batch job ID
         * @returns {Promise<Object>} Job status and progress
         */
        async getBatchStatus(jobId) {
            const response = await this.request(`/batch/status/${jobId}`, {
                method: 'GET'
            });
            
            return response.data;
        }
        
        /**
         * Poll batch operation status until complete (Phase 2 - Task 6)
         * 
         * Polls the batch operation status at regular intervals until the job
         * is complete or fails. Calls progress callback with status updates.
         * 
         * @param {string} jobId Batch job ID
         * @param {Function} onProgress Progress callback function
         * @param {number} interval Polling interval in milliseconds (default: 2000)
         * @param {number} timeout Maximum polling time in milliseconds (default: 300000 = 5 minutes)
         * @returns {Promise<Object>} Final job status
         */
        async pollBatchStatus(jobId, onProgress = null, interval = 2000, timeout = 300000) {
            const startTime = Date.now();
            
            return new Promise((resolve, reject) => {
                const poll = async () => {
                    try {
                        // Check timeout
                        if (Date.now() - startTime > timeout) {
                            reject(new MASRestError(
                                'Batch operation timed out',
                                'batch_timeout',
                                408
                            ));
                            return;
                        }
                        
                        // Get status
                        const status = await this.getBatchStatus(jobId);
                        
                        // Call progress callback
                        if (onProgress && typeof onProgress === 'function') {
                            onProgress(status);
                        }
                        
                        // Check if complete
                        if (status.status === 'completed') {
                            resolve(status);
                            return;
                        }
                        
                        // Check if failed
                        if (status.status === 'failed') {
                            reject(new MASRestError(
                                status.error || 'Batch operation failed',
                                'batch_failed',
                                500,
                                status
                            ));
                            return;
                        }
                        
                        // Continue polling
                        setTimeout(poll, interval);
                        
                    } catch (error) {
                        reject(error);
                    }
                };
                
                // Start polling
                poll();
            });
        }
        
        /**
         * Create batch operation helper (Phase 2 - Task 6)
         * 
         * Helper method to create properly formatted batch operations.
         * 
         * @param {string} type Operation type
         * @param {Object} data Operation data
         * @returns {Object} Formatted operation
         */
        createBatchOperation(type, data) {
            return {
                type,
                data
            };
        }
        
        /**
         * Batch update multiple settings (Phase 2 - Task 6)
         * 
         * Convenience method to update multiple settings in a batch.
         * 
         * @param {Object} settings Settings object with key-value pairs
         * @param {boolean} async Whether to process asynchronously
         * @returns {Promise<Object>} Batch operation results
         */
        async batchUpdateMultipleSettings(settings, async = false) {
            const operations = Object.entries(settings).map(([key, value]) => 
                this.createBatchOperation('update_setting', { key, value })
            );
            
            return await this.batchUpdateSettings(operations, async);
        }
        
        /**
         * Batch reset settings (Phase 2 - Task 6)
         * 
         * Convenience method to reset multiple settings to defaults in a batch.
         * 
         * @param {Array<string>} settingKeys Array of setting keys to reset
         * @param {boolean} async Whether to process asynchronously
         * @returns {Promise<Object>} Batch operation results
         */
        async batchResetSettings(settingKeys, async = false) {
            const operations = settingKeys.map(key => 
                this.createBatchOperation('reset_setting', { key })
            );
            
            return await this.batchUpdateSettings(operations, async);
        }
        
        /**
         * List all webhooks (Phase 2 - Task 7)
         * 
         * Retrieves all registered webhooks with optional filtering.
         * 
         * @param {Object} params Query parameters
         * @param {boolean} params.active Filter by active status
         * @param {number} params.limit Number of webhooks to return (default: 50)
         * @param {number} params.offset Offset for pagination (default: 0)
         * @returns {Promise<Array>} Array of webhooks
         */
        async listWebhooks(params = {}) {
            const queryParams = new URLSearchParams();
            
            if (params.active !== undefined) {
                queryParams.append('active', params.active);
            }
            if (params.limit) {
                queryParams.append('limit', params.limit);
            }
            if (params.offset) {
                queryParams.append('offset', params.offset);
            }
            
            const queryString = queryParams.toString();
            const endpoint = queryString ? `/webhooks?${queryString}` : '/webhooks';
            
            const response = await this.request(endpoint, {
                method: 'GET'
            });
            
            return response.data;
        }
        
        /**
         * Register a new webhook (Phase 2 - Task 7)
         * 
         * Registers a webhook to receive notifications for specific events.
         * 
         * @param {string} url Webhook URL
         * @param {Array<string>} events Array of event names to subscribe to
         * @param {string} secret Optional secret for HMAC signature (auto-generated if not provided)
         * @returns {Promise<Object>} Registered webhook data
         */
        async registerWebhook(url, events, secret = '') {
            const response = await this.request('/webhooks', {
                method: 'POST',
                body: JSON.stringify({
                    url,
                    events,
                    secret
                })
            });
            
            return response.data;
        }
        
        /**
         * Get a specific webhook (Phase 2 - Task 7)
         * 
         * Retrieves details for a specific webhook by ID.
         * 
         * @param {number} webhookId Webhook ID
         * @returns {Promise<Object>} Webhook data
         */
        async getWebhook(webhookId) {
            const response = await this.request(`/webhooks/${webhookId}`, {
                method: 'GET'
            });
            
            return response.data;
        }
        
        /**
         * Update a webhook (Phase 2 - Task 7)
         * 
         * Updates an existing webhook's URL, events, or active status.
         * 
         * @param {number} webhookId Webhook ID
         * @param {Object} data Update data
         * @param {string} data.url New webhook URL
         * @param {Array<string>} data.events New array of events
         * @param {boolean} data.active Whether webhook is active
         * @returns {Promise<Object>} Updated webhook data
         */
        async updateWebhook(webhookId, data) {
            const response = await this.request(`/webhooks/${webhookId}`, {
                method: 'PUT',
                body: JSON.stringify(data)
            });
            
            return response.data;
        }
        
        /**
         * Delete a webhook (Phase 2 - Task 7)
         * 
         * Deletes a webhook and all its delivery history.
         * 
         * @param {number} webhookId Webhook ID
         * @returns {Promise<Object>} Deletion confirmation
         */
        async deleteWebhook(webhookId) {
            const response = await this.request(`/webhooks/${webhookId}`, {
                method: 'DELETE'
            });
            
            return response.data;
        }
        
        /**
         * Get webhook deliveries (Phase 2 - Task 7)
         * 
         * Retrieves delivery history for a specific webhook.
         * 
         * @param {number} webhookId Webhook ID
         * @param {Object} params Query parameters
         * @param {string} params.status Filter by delivery status (pending, success, failed)
         * @param {number} params.limit Number of deliveries to return (default: 50)
         * @param {number} params.offset Offset for pagination (default: 0)
         * @returns {Promise<Array>} Array of delivery records
         */
        async getWebhookDeliveries(webhookId, params = {}) {
            const queryParams = new URLSearchParams();
            
            if (params.status) {
                queryParams.append('status', params.status);
            }
            if (params.limit) {
                queryParams.append('limit', params.limit);
            }
            if (params.offset) {
                queryParams.append('offset', params.offset);
            }
            
            const queryString = queryParams.toString();
            const endpoint = queryString 
                ? `/webhooks/${webhookId}/deliveries?${queryString}` 
                : `/webhooks/${webhookId}/deliveries`;
            
            const response = await this.request(endpoint, {
                method: 'GET'
            });
            
            return response.data;
        }
        
        /**
         * Get supported webhook events (Phase 2 - Task 7)
         * 
         * Retrieves the list of supported webhook events with descriptions.
         * 
         * @returns {Promise<Array>} Array of supported events
         */
        async getSupportedWebhookEvents() {
            const response = await this.request('/webhooks/events', {
                method: 'GET'
            });
            
            return response.data;
        }
        
        // ==================== Analytics Methods (Phase 2 - Task 8) ====================
        
        /**
         * Get usage statistics (Phase 2 - Task 8)
         * 
         * Retrieves API usage statistics for a given date range.
         * 
         * @param {Object} params Query parameters
         * @param {string} params.start_date Start date (Y-m-d H:i:s format)
         * @param {string} params.end_date End date (Y-m-d H:i:s format)
         * @returns {Promise<Object>} Usage statistics
         */
        async getUsageStats(params = {}) {
            const queryParams = new URLSearchParams();
            
            if (params.start_date) {
                queryParams.append('start_date', params.start_date);
            }
            if (params.end_date) {
                queryParams.append('end_date', params.end_date);
            }
            
            const queryString = queryParams.toString();
            const endpoint = queryString ? `/analytics/usage?${queryString}` : '/analytics/usage';
            
            const response = await this.request(endpoint, {
                method: 'GET'
            });
            
            return response.data;
        }
        
        /**
         * Get performance metrics (Phase 2 - Task 8)
         * 
         * Retrieves performance metrics including response time percentiles.
         * 
         * @param {Object} params Query parameters
         * @param {string} params.start_date Start date (Y-m-d H:i:s format)
         * @param {string} params.end_date End date (Y-m-d H:i:s format)
         * @returns {Promise<Object>} Performance metrics
         */
        async getPerformanceMetrics(params = {}) {
            const queryParams = new URLSearchParams();
            
            if (params.start_date) {
                queryParams.append('start_date', params.start_date);
            }
            if (params.end_date) {
                queryParams.append('end_date', params.end_date);
            }
            
            const queryString = queryParams.toString();
            const endpoint = queryString ? `/analytics/performance?${queryString}` : '/analytics/performance';
            
            const response = await this.request(endpoint, {
                method: 'GET'
            });
            
            return response.data;
        }
        
        /**
         * Get error statistics (Phase 2 - Task 8)
         * 
         * Retrieves error statistics and error rate analysis.
         * 
         * @param {Object} params Query parameters
         * @param {string} params.start_date Start date (Y-m-d H:i:s format)
         * @param {string} params.end_date End date (Y-m-d H:i:s format)
         * @returns {Promise<Object>} Error statistics
         */
        async getErrorStats(params = {}) {
            const queryParams = new URLSearchParams();
            
            if (params.start_date) {
                queryParams.append('start_date', params.start_date);
            }
            if (params.end_date) {
                queryParams.append('end_date', params.end_date);
            }
            
            const queryString = queryParams.toString();
            const endpoint = queryString ? `/analytics/errors?${queryString}` : '/analytics/errors';
            
            const response = await this.request(endpoint, {
                method: 'GET'
            });
            
            return response.data;
        }
        
        /**
         * Export analytics data as CSV (Phase 2 - Task 8)
         * 
         * Downloads analytics data as a CSV file.
         * 
         * @param {Object} params Query parameters
         * @param {string} params.start_date Start date (Y-m-d H:i:s format)
         * @param {string} params.end_date End date (Y-m-d H:i:s format)
         * @returns {Promise<void>} Triggers file download
         */
        async exportAnalytics(params = {}) {
            const queryParams = new URLSearchParams();
            
            if (params.start_date) {
                queryParams.append('start_date', params.start_date);
            }
            if (params.end_date) {
                queryParams.append('end_date', params.end_date);
            }
            
            const queryString = queryParams.toString();
            const endpoint = queryString ? `/analytics/export?${queryString}` : '/analytics/export';
            
            // Make request to get CSV content
            const url = this.apiUrl + endpoint;
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': this.nonce
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                throw new MASRestError(
                    'Failed to export analytics',
                    'export_failed',
                    response.status
                );
            }
            
            // Get CSV content
            const csvContent = await response.text();
            
            // Get filename from Content-Disposition header or generate one
            const contentDisposition = response.headers.get('Content-Disposition');
            let filename = 'mas-analytics.csv';
            
            if (contentDisposition) {
                const filenameMatch = contentDisposition.match(/filename="?([^"]+)"?/);
                if (filenameMatch) {
                    filename = filenameMatch[1];
                }
            }
            
            // Create blob and trigger download
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const downloadUrl = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(downloadUrl);
        }
        
        /**
         * Check if REST API is available
         * 
         * @returns {boolean} True if available
         */
        isAvailable() {
            return !!(this.baseUrl && this.nonce);
        }
        
        /**
         * Handle deprecation warnings from response
         * 
         * Checks response headers for deprecation warnings and logs them to console.
         * Tracks warnings to avoid duplicate logging.
         * 
         * @param {Response} response Fetch API response object
         * @param {string} endpoint Endpoint path
         */
        handleDeprecationWarnings(response, endpoint) {
            const deprecated = response.headers.get('X-API-Deprecated');
            
            if (deprecated === 'true') {
                const warningKey = `${this.version}:${endpoint}`;
                
                // Only warn once per endpoint
                if (!this.deprecationWarnings.has(warningKey)) {
                    this.deprecationWarnings.add(warningKey);
                    
                    const warning = response.headers.get('Warning');
                    const removalDate = response.headers.get('X-API-Removal-Date');
                    const replacement = response.headers.get('X-API-Replacement');
                    const migrationGuide = response.headers.get('X-API-Migration-Guide');
                    
                    console.warn(
                        `%c[MAS API Deprecation Warning]`,
                        'color: #ff9800; font-weight: bold;',
                        `\nEndpoint: ${endpoint}`,
                        `\nVersion: ${this.version}`,
                        removalDate ? `\nRemoval Date: ${removalDate}` : '',
                        replacement ? `\nReplacement: ${replacement}` : '',
                        migrationGuide ? `\nMigration Guide: ${migrationGuide}` : '',
                        warning ? `\n\n${warning}` : ''
                    );
                    
                    // Dispatch custom event for programmatic handling
                    window.dispatchEvent(new CustomEvent('mas-api-deprecated', {
                        detail: {
                            endpoint,
                            version: this.version,
                            removalDate,
                            replacement,
                            migrationGuide,
                            warning
                        }
                    }));
                }
            }
        }
        
        /**
         * Handle version headers from response
         * 
         * Logs version information and checks for version mismatches.
         * 
         * @param {Response} response Fetch API response object
         */
        handleVersionHeaders(response) {
            const responseVersion = response.headers.get('X-API-Version');
            
            if (responseVersion && this.debug) {
                console.log('[MAS REST Client] API Version', {
                    requested: this.version,
                    received: responseVersion,
                    match: responseVersion === this.version
                });
            }
            
            // Warn if version mismatch
            if (responseVersion && responseVersion !== this.version) {
                console.warn(
                    `[MAS REST Client] Version mismatch: requested ${this.version}, received ${responseVersion}`
                );
            }
        }
        
        /**
         * Get deprecation warnings
         * 
         * Returns all deprecation warnings encountered during this session.
         * 
         * @returns {Array<string>} Array of deprecated endpoint keys
         */
        getDeprecationWarnings() {
            return Array.from(this.deprecationWarnings);
        }
        
        /**
         * Clear deprecation warnings
         * 
         * Clears the deprecation warnings cache. Useful for testing or
         * when you want to see warnings again.
         */
        clearDeprecationWarnings() {
            this.deprecationWarnings.clear();
        }
        
        /**
         * Check if endpoint is deprecated
         * 
         * Checks if a specific endpoint has been marked as deprecated.
         * 
         * @param {string} endpoint Endpoint path
         * @returns {boolean} True if deprecated
         */
        isEndpointDeprecated(endpoint) {
            const warningKey = `${this.version}:${endpoint}`;
            return this.deprecationWarnings.has(warningKey);
        }
        
        /**
         * Migration helper: Get recommended replacement
         * 
         * Makes a request to get migration information for an endpoint.
         * This is a helper method for developers migrating their code.
         * 
         * @param {string} endpoint Endpoint path
         * @returns {Promise<Object|null>} Migration info or null if not deprecated
         */
        async getMigrationInfo(endpoint) {
            try {
                // Make a HEAD request to check headers without fetching data
                const url = this.apiUrl + endpoint;
                const response = await fetch(url, {
                    method: 'HEAD',
                    headers: {
                        'X-WP-Nonce': this.nonce
                    },
                    credentials: 'same-origin'
                });
                
                const deprecated = response.headers.get('X-API-Deprecated');
                
                if (deprecated === 'true') {
                    return {
                        deprecated: true,
                        removalDate: response.headers.get('X-API-Removal-Date'),
                        replacement: response.headers.get('X-API-Replacement'),
                        migrationGuide: response.headers.get('X-API-Migration-Guide'),
                        warning: response.headers.get('Warning')
                    };
                }
                
                return null;
            } catch (error) {
                console.error('[MAS REST Client] Failed to get migration info', error);
                return null;
            }
        }
    }
    
    /**
     * Custom error class for REST API errors
     * 
     * @class
     */
    class MASRestError extends Error {
        /**
         * Constructor
         * 
         * @param {string} message Error message
         * @param {string} code Error code
         * @param {number} status HTTP status code
         * @param {Object} data Additional error data
         */
        constructor(message, code, status, data = {}) {
            super(message);
            this.name = 'MASRestError';
            this.code = code;
            this.status = status;
            this.data = data;
        }
        
        /**
         * Get user-friendly error message
         * 
         * @returns {string} User-friendly message
         */
        getUserMessage() {
            const messages = {
                'rest_forbidden': 'You do not have permission to perform this action.',
                'rest_cookie_invalid_nonce': 'Security check failed. Please refresh the page.',
                'validation_failed': 'Please check your input and try again.',
                'save_failed': 'Failed to save settings. Please try again.',
                'reset_failed': 'Failed to reset settings. Please try again.',
                'network_error': 'Network error. Please check your connection.',
                'request_failed': 'Request failed. Please try again.'
            };
            
            return messages[this.code] || this.message || 'An unexpected error occurred.';
        }
        
        /**
         * Check if error is a permission error
         * 
         * @returns {boolean} True if permission error
         */
        isPermissionError() {
            return this.code === 'rest_forbidden' || this.status === 403;
        }
        
        /**
         * Check if error is a validation error
         * 
         * @returns {boolean} True if validation error
         */
        isValidationError() {
            return this.code === 'validation_failed' || this.status === 400;
        }
        
        /**
         * Check if error is a network error
         * 
         * @returns {boolean} True if network error
         */
        isNetworkError() {
            return this.code === 'network_error' || this.status === 0;
        }
    }
    
    // Export to global scope
    window.MASRestClient = MASRestClient;
    window.MASRestError = MASRestError;
    
    // Create default instance if wpApiSettings is available
    if (window.wpApiSettings) {
        window.masRestClient = new MASRestClient({
            debug: window.masV2Global && window.masV2Global.debug_mode
        });
    }
    
})(window);
