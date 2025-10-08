/**
 * LazyLoader Utility
 * 
 * Handles dynamic imports and lazy loading of components
 * with loading indicators and error handling.
 * 
 * @class LazyLoader
 */
class LazyLoader {
    constructor() {
        this.loadedModules = new Map();
        this.loadingPromises = new Map();
        this.loadingIndicators = new Map();
    }

    /**
     * Lazy load a component
     * 
     * @param {string} componentName - Name of the component
     * @param {string} modulePath - Path to the module
     * @param {Object} options - Loading options
     * @param {HTMLElement} options.container - Container to show loading indicator
     * @param {boolean} options.showLoader - Whether to show loading indicator
     * @param {Function} options.onProgress - Progress callback
     * @returns {Promise<Object>} Loaded module
     */
    async loadComponent(componentName, modulePath, options = {}) {
        const {
            container = null,
            showLoader = true,
            onProgress = null
        } = options;

        // Return cached module if already loaded
        if (this.loadedModules.has(componentName)) {
            return this.loadedModules.get(componentName);
        }

        // Return existing loading promise if already loading
        if (this.loadingPromises.has(componentName)) {
            return this.loadingPromises.get(componentName);
        }

        // Show loading indicator
        if (showLoader && container) {
            this.showLoadingIndicator(container, componentName);
        }

        // Create loading promise
        const loadingPromise = this.loadModule(modulePath, componentName, onProgress);

        // Store loading promise
        this.loadingPromises.set(componentName, loadingPromise);

        try {
            const module = await loadingPromise;

            // Cache loaded module
            this.loadedModules.set(componentName, module);

            // Remove loading promise
            this.loadingPromises.delete(componentName);

            // Hide loading indicator
            if (showLoader && container) {
                this.hideLoadingIndicator(container, componentName);
            }

            return module;

        } catch (error) {
            // Remove loading promise
            this.loadingPromises.delete(componentName);

            // Hide loading indicator
            if (showLoader && container) {
                this.hideLoadingIndicator(container, componentName);
            }

            // Show error
            if (container) {
                this.showLoadingError(container, componentName, error);
            }

            throw error;
        }
    }

    /**
     * Load module using dynamic import
     * 
     * @param {string} modulePath - Path to the module
     * @param {string} componentName - Name of the component
     * @param {Function} onProgress - Progress callback
     * @returns {Promise<Object>} Loaded module
     */
    async loadModule(modulePath, componentName, onProgress) {
        try {
            if (onProgress) {
                onProgress({ component: componentName, status: 'loading' });
            }

            // Dynamic import
            const module = await import(modulePath);

            if (onProgress) {
                onProgress({ component: componentName, status: 'loaded' });
            }

            return module;

        } catch (error) {
            if (onProgress) {
                onProgress({ component: componentName, status: 'error', error });
            }

            throw new Error(`Failed to load component ${componentName}: ${error.message}`);
        }
    }

    /**
     * Show loading indicator
     * 
     * @param {HTMLElement} container - Container element
     * @param {string} componentName - Component name
     */
    showLoadingIndicator(container, componentName) {
        const loaderId = `mas-loader-${componentName}`;

        // Check if loader already exists
        if (this.loadingIndicators.has(componentName)) {
            return;
        }

        // Create loading indicator
        const loader = document.createElement('div');
        loader.id = loaderId;
        loader.className = 'mas-lazy-loader';
        loader.innerHTML = `
            <div class="mas-lazy-loader-spinner"></div>
            <div class="mas-lazy-loader-text">Loading ${this.formatComponentName(componentName)}...</div>
        `;

        // Add styles if not already present
        this.injectLoaderStyles();

        // Insert loader
        container.style.position = 'relative';
        container.appendChild(loader);

        // Store reference
        this.loadingIndicators.set(componentName, loader);
    }

    /**
     * Hide loading indicator
     * 
     * @param {HTMLElement} container - Container element
     * @param {string} componentName - Component name
     */
    hideLoadingIndicator(container, componentName) {
        const loader = this.loadingIndicators.get(componentName);

        if (loader && loader.parentNode) {
            // Fade out animation
            loader.style.opacity = '0';
            setTimeout(() => {
                if (loader.parentNode) {
                    loader.parentNode.removeChild(loader);
                }
            }, 300);

            this.loadingIndicators.delete(componentName);
        }
    }

    /**
     * Show loading error
     * 
     * @param {HTMLElement} container - Container element
     * @param {string} componentName - Component name
     * @param {Error} error - Error object
     */
    showLoadingError(container, componentName, error) {
        const errorId = `mas-loader-error-${componentName}`;

        // Create error message
        const errorDiv = document.createElement('div');
        errorDiv.id = errorId;
        errorDiv.className = 'mas-lazy-loader-error';
        errorDiv.innerHTML = `
            <div class="mas-lazy-loader-error-icon">⚠️</div>
            <div class="mas-lazy-loader-error-text">
                Failed to load ${this.formatComponentName(componentName)}
            </div>
            <button class="mas-lazy-loader-retry" data-component="${componentName}">
                Retry
            </button>
        `;

        // Insert error message
        container.appendChild(errorDiv);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.parentNode.removeChild(errorDiv);
            }
        }, 5000);
    }

    /**
     * Inject loader styles
     */
    injectLoaderStyles() {
        if (document.getElementById('mas-lazy-loader-styles')) {
            return;
        }

        const styles = document.createElement('style');
        styles.id = 'mas-lazy-loader-styles';
        styles.textContent = `
            .mas-lazy-loader {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                text-align: center;
                z-index: 1000;
                transition: opacity 0.3s ease;
            }

            .mas-lazy-loader-spinner {
                width: 40px;
                height: 40px;
                margin: 0 auto 10px;
                border: 4px solid rgba(0, 0, 0, 0.1);
                border-top-color: #2271b1;
                border-radius: 50%;
                animation: mas-spin 1s linear infinite;
            }

            @keyframes mas-spin {
                to { transform: rotate(360deg); }
            }

            .mas-lazy-loader-text {
                color: #666;
                font-size: 14px;
            }

            .mas-lazy-loader-error {
                padding: 20px;
                background: #fff;
                border: 1px solid #dc3232;
                border-radius: 4px;
                text-align: center;
                margin: 20px 0;
            }

            .mas-lazy-loader-error-icon {
                font-size: 32px;
                margin-bottom: 10px;
            }

            .mas-lazy-loader-error-text {
                color: #dc3232;
                margin-bottom: 10px;
            }

            .mas-lazy-loader-retry {
                padding: 8px 16px;
                background: #2271b1;
                color: #fff;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }

            .mas-lazy-loader-retry:hover {
                background: #135e96;
            }
        `;

        document.head.appendChild(styles);
    }

    /**
     * Format component name for display
     * 
     * @param {string} componentName - Component name
     * @returns {string} Formatted name
     */
    formatComponentName(componentName) {
        return componentName
            .replace(/([A-Z])/g, ' $1')
            .replace(/^./, str => str.toUpperCase())
            .trim();
    }

    /**
     * Preload components
     * 
     * @param {Array<Object>} components - Array of {name, path} objects
     * @returns {Promise<Array>} Array of loaded modules
     */
    async preloadComponents(components) {
        const promises = components.map(({ name, path }) =>
            this.loadComponent(name, path, { showLoader: false })
        );

        return Promise.all(promises);
    }

    /**
     * Check if component is loaded
     * 
     * @param {string} componentName - Component name
     * @returns {boolean} Whether component is loaded
     */
    isLoaded(componentName) {
        return this.loadedModules.has(componentName);
    }

    /**
     * Check if component is loading
     * 
     * @param {string} componentName - Component name
     * @returns {boolean} Whether component is loading
     */
    isLoading(componentName) {
        return this.loadingPromises.has(componentName);
    }

    /**
     * Get loaded component
     * 
     * @param {string} componentName - Component name
     * @returns {Object|undefined} Loaded module
     */
    getLoadedComponent(componentName) {
        return this.loadedModules.get(componentName);
    }

    /**
     * Clear cache
     * 
     * @param {string} componentName - Component name (optional, clears all if not provided)
     */
    clearCache(componentName = null) {
        if (componentName) {
            this.loadedModules.delete(componentName);
        } else {
            this.loadedModules.clear();
        }
    }

    /**
     * Get loading stats
     * 
     * @returns {Object} Loading statistics
     */
    getStats() {
        return {
            loaded: this.loadedModules.size,
            loading: this.loadingPromises.size,
            components: Array.from(this.loadedModules.keys())
        };
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LazyLoader;
}

// Make available globally
if (typeof window !== 'undefined') {
    window.LazyLoader = LazyLoader;
}
